<?php
// app/Http/Controllers/Admin/UserController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index()
    {
        $users = User::with(['roles', 'organization'])->latest()->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create', $this->formOptions());
    }

    public function store(StoreUserRequest $request)
    {
        $data = collect($request->validated())->except('role')->all();

        $user = User::create($data); // password auto-hash via cast 'hashed'
        $user->assignRole($request->validated('role'));

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
            ->except(['role', 'email_notifications'])
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

    private function formOptions(): array
    {
        return [
            'roles' => Role::orderBy('name')->pluck('name'),
            'organizations' => Organization::orderBy('name')->pluck('name', 'id'),
        ];
    }
}