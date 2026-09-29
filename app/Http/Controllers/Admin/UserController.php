<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Organization;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

/**
 * Manajemen pengguna.
 *
 * Halaman daftar dibagi per peran (tab navbar): Semua, Admin, Staff, Teknisi,
 * Borrower. Untuk borrower tersedia filter bertingkat
 * sekolah → jenjang → jurusan → kelas.
 */
class UserController extends Controller
{
    /** Peran yang punya tab tersendiri di navbar. */
    public const ROLE_TABS = ['super-admin', 'admin', 'staff', 'technician', 'borrower'];

    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request, ?string $role = null)
    {
        // Tab peran di navbar; nilai tak dikenal → semua.
        $activeRole = in_array($role, self::ROLE_TABS, true) ? $role : null;

        $users = User::with([
            'roles',
            'organization',
            'schoolClass.program',
        ])
            ->when($activeRole, fn ($q) => $q->role($activeRole))
            ->when($request->filled('organization_id'), fn ($q) => $q->where('organization_id', $request->integer('organization_id')))
            ->when($request->filled('level'), fn ($q) => $q->whereHas(
                'schoolClass.program',
                fn ($p) => $p->where('education_level', $request->string('level'))
            ))
            ->when($request->filled('program_id'), fn ($q) => $q->whereHas(
                'schoolClass',
                fn ($c) => $c->where('program_id', $request->integer('program_id'))
            ))
            ->when($request->filled('school_class_id'), fn ($q) => $q->where('school_class_id', $request->integer('school_class_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $sub->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('identity_number', 'like', $term);
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'activeRole' => $activeRole,
            'organizations' => Organization::orderBy('name')->pluck('name', 'id'),
            'programs' => Program::orderBy('name')->get(['id', 'name', 'education_level', 'organization_id']),
            'schoolClasses' => SchoolClass::with('program')->orderBy('name')->get(['id', 'name', 'program_id']),
            'levelCounts' => $this->roleCounts(),
        ]);
    }

    /** Jumlah pengguna per peran untuk badge di navbar. */
    private function roleCounts(): array
    {
        // Satu query: hitung per role lewat pivot, bukan 5 query terpisah.
        //
        // Agregatnya WAJIB diberi alias. pluck() membaca properti hasil query
        // memakai nama kolom itu apa adanya, jadi pluck(DB::raw('count(*)'), …)
        // berakhir mengakses $row->{'count(*)'} yang tidak pernah ada →
        // "Undefined property: stdClass::$count(*)". Bug ini bukan soal dialect,
        // dia gagal di driver apa pun. pluck() juga men-strip prefix tabel dari
        // key, jadi 'roles.name' terbaca sebagai 'name'.
        $tallies = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->whereNull('users.deleted_at')
            ->where('model_has_roles.model_type', User::class)
            ->whereIn('roles.name', self::ROLE_TABS)
            ->select('roles.name')
            ->selectRaw('count(*) as total')
            ->groupBy('roles.name')
            ->pluck('total', 'name');

        $out = [];
        foreach (self::ROLE_TABS as $role) {
            $out[$role] = (int) ($tallies[$role] ?? 0);
        }

        return $out;
    }

    /** Detail siswa: foto, identitas, kelas, dan transaksinya. */
    public function show(User $user)
    {
        $user->load(['roles', 'organization', 'schoolClass.program', 'borrowings', 'reservations']);

        return view('admin.users.show', ['user' => $user]);
    }

    public function create()
    {
        return view('admin.users.create', $this->formOptions());
    }

    public function store(StoreUserRequest $request)
    {
        // `photo` bukan kolom DB — jangan ikut tersalin ke mass-assignment.
        $data = collect($request->validated())->except(['role', 'photo'])->all();

        $user = User::create($data); // password auto-hash via cast 'hashed'
        $user->assignRole($request->validated('role'));

        if ($request->hasFile('photo')) {
            $user->update(['photo_path' => $request->file('photo')->store('avatars')]);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Pengguna {$user->name} berhasil dibuat.");
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', $this->formOptions() + compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = collect($request->validated())
            ->except(['role', 'email_notifications', 'photo'])
            ->all();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        // PDF 4N: notification preference per user
        $preferences = $user->notification_preferences ?? [];
        $preferences['email_enabled'] = $request->boolean('email_notifications');
        $data['notification_preferences'] = $preferences;

        $user->update($data);
        $user->syncRoles($request->validated('role'));

        if ($request->hasFile('photo')) {
            $this->replacePhoto($user, $request);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $user->delete(); // soft delete

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna dihapus (soft delete).');
    }

    private function replacePhoto(User $user, Request $request): void
    {
        if ($user->photo_path) {
            Storage::delete($user->photo_path);
        }

        $user->update([
            'photo_path' => $request->file('photo')->store('avatars'),
        ]);
    }

    private function formOptions(): array
    {
        return [
            'roles' => Role::orderBy('name')->pluck('name'),
            'organizations' => Organization::orderBy('name')->pluck('name', 'id'),
            'programs' => Program::orderBy('name')->get(['id', 'name', 'education_level', 'organization_id']),
            // `organization_id` TIDAK ada di school_classes — organisasi hanya
            // bisa dicapai lewat program. Jangan	select kolom itu.
            'schoolClasses' => SchoolClass::with('program')
                ->orderBy('name')
                ->get(['id', 'name', 'program_id']),
        ];
    }
}
