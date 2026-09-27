<?php

namespace Tests\Feature\School;

use App\Enums\EducationLevel;
use App\Enums\UserStatus;
use App\Models\Classroom;
use App\Models\Organization;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Struktur sekolah, filter bertingkat, dan foto profil.
 *
 * Menutup tiga risiko yang mudah lolos: relasi antar jenjang, filter yang
 * hanyaemonset di UI tanpa benar-benar menyaring, dan upload foto yang
 * meninggalkan file yatim setiap kali diganti.
 */
class SchoolStructureTest extends TestCase
{
    private User $admin;

    private Organization $org;

    private Program $sma;

    private Program $smk;

    private SchoolClass $ipa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = $this->makeUser('super-admin');

        $this->org = $this->makeOrg();

        // SMA punya dua jurusan (IPA, IPS); SMK punya RPL & TKJ — dipakai untuk
        // memastikan filter jurusan benar-benar membedakan.
        $this->sma = Program::create([
            'organization_id' => $this->org->id,
            'education_level' => EducationLevel::SMA,
            'name' => 'IPA',
            'code' => 'SMA-IPA',
        ]);
        Program::create([
            'organization_id' => $this->org->id,
            'education_level' => EducationLevel::SMA,
            'name' => 'IPS',
            'code' => 'SMA-IPS',
        ]);
        $this->smk = Program::create([
            'organization_id' => $this->org->id,
            'education_level' => EducationLevel::SMK,
            'name' => 'RPL',
            'code' => 'SMK-RPL',
        ]);

        $this->ipa = SchoolClass::create([
            'program_id' => $this->sma->id,
            'name' => 'X IPA 1',
            'school_year' => '2026/2027',
            'capacity' => 30,
        ]);
    }

    private function student(?SchoolClass $class = null, string $name = 'Siswa Uji'): User
    {
        return User::create([
            'organization_id' => $this->org->id,
            'school_class_id' => ($class ?? $this->ipa)->id,
            'name' => $name,
            'email' => str($name.'@siswa.test')->slug().'@siswa.test',
            'password' => 'password',
            'status' => UserStatus::Active,
            'identity_number' => 'SMA-0001-0001',
            'gender' => 'male',
        ])->assignRole('borrower');
    }

    /* ── Navbar per peran ────────────────────────────────────────────── */

    public function test_navbar_peran_memisahkan_pengguna(): void
    {
        $this->makeUser('staff', $this->org, 'staff1@lendora.test');
        $this->makeUser('technician', $this->org, 'tech1@lendora.test');
        $this->student();

        $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk();

        // Tab "staff" hanya menampilkan staff.
        $this->actingAs($this->admin)
            ->get(route('admin.users.byRole', 'staff'))
            ->assertOk()
            ->assertSee('staff1@lendora.test')
            ->assertDontSee('tech1@lendora.test');

        // Tab "borrower" hanya siswa.
        $this->actingAs($this->admin)
            ->get(route('admin.users.byRole', 'borrower'))
            ->assertOk()
            ->assertSee('Siswa Uji')
            ->assertDontSee('staff1@lendora.test');
    }

    public function test_role_tak_dikenal_di_tab_tidak_membocorkan_halaman(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/users/role/peran-ngawur')
            ->assertNotFound();
    }

    /* ── Filter bertingkat ───────────────────────────────────────────── */

    public function test_filter_jenjang_menyaring_siswa(): void
    {
        $rpl = SchoolClass::create(['program_id' => $this->smk->id, 'name' => 'X RPL 1', 'school_year' => '2026/2027']);
        $this->student($this->ipa, 'Siswa IPA');
        $this->student($rpl, 'Siswa RPL');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['level' => 'smk']))
            ->assertOk()
            ->assertSee('Siswa RPL')
            ->assertDontSee('Siswa IPA');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['level' => 'sma']))
            ->assertOk()
            ->assertSee('Siswa IPA')
            ->assertDontSee('Siswa RPL');
    }

    public function test_filter_jurusan_dan_kelas_menyaring(): void
    {
        $ips = Program::where('code', 'SMA-IPS')->firstOrFail();
        $ipsClass = SchoolClass::create(['program_id' => $ips->id, 'name' => 'X IPS 1', 'school_year' => '2026/2027']);

        $this->student($this->ipa, 'Siswa IPA');
        $this->student($ipsClass, 'Siswa IPS');

        // Jurusan
        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['program_id' => $ips->id]))
            ->assertOk()
            ->assertSee('Siswa IPS')
            ->assertDontSee('Siswa IPA');

        // Kelas spesifik
        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['school_class_id' => $ipsClass->id]))
            ->assertOk()
            ->assertSee('Siswa IPS')
            ->assertDontSee('Siswa IPA');
    }

    public function test_filter_kelas_menampilkan_jumlah_siswa(): void
    {
        $this->student($this->ipa, 'Satu');
        $this->student($this->ipa, 'Dua');

        // Lewati tab Borrower — di situ filter kelas dipakai untuk dicari siswa.
        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.byRole', 'borrower').'?'.http_build_query(['school_class_id' => $this->ipa->id]))
            ->assertOk();

        // assertSeeText menormalkan whitespace — angka & kata dipisah baris di blade.
        $response->assertSeeText('2 siswa ditemukan');
        $response->assertSee($this->ipa->name, false);
    }

    /* ── Detail siswa ────────────────────────────────────────────────── */

    public function test_halaman_detail_siswa_menampilkan_identitas(): void
    {
        $student = $this->student();
        $student->update(['birth_date' => '2010-05-06', 'address' => 'Jl. uji 9', 'bio' => 'Siswa teladan']);

        $this->actingAs($this->admin)
            ->get(route('admin.users.show', $student))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('SMA-0001-0001', false)
            ->assertSee('Laki-laki')
            ->assertSee('06 Mei 2010')
            ->assertSee('Jl. uji 9')
            ->assertSee('Siswa teladan')
            ->assertSee('X IPA 1', false);
    }

    public function test_organisasi_bukan_siswa_tidak_menampilkan_kelas_kosong(): void
    {
        $staff = $this->makeUser('staff', $this->org);

        $this->actingAs($this->admin)
            ->get(route('admin.users.show', $staff))
            ->assertOk()
            ->assertSee('Bukan siswa', false);
    }

    /* ── Foto profil ─────────────────────────────────────────────────── */

    public function test_pengguna_bisa_mengunggah_foto_profil(): void
    {
        Storage::fake('public');
        $user = $this->makeUser('borrower', $this->org);

        $this->actingAs($user)
            ->put(route('profile.photo'), [
                'photo' => UploadedFile::fake()->image('foto.jpg', 400, 400),
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->photo_path, 'photo_path harus terisi setelah upload.');
        Storage::disk('public')->assertExists($user->photo_path);
    }

    public function test_foto_lama_dihapus_saat_diganti(): void
    {
        Storage::fake('public');
        $user = $this->makeUser('borrower', $this->org);

        $this->actingAs($user)->put(route('profile.photo'), [
            'photo' => UploadedFile::fake()->image('pertama.jpg'),
        ]);
        $first = $user->refresh()->photo_path;

        $this->actingAs($user)->put(route('profile.photo'), [
            'photo' => UploadedFile::fake()->image('kedua.jpg'),
        ]);
        $second = $user->refresh()->photo_path;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first); // tidak ada file yatim
        Storage::disk('public')->assertExists($second);
    }

    public function test_foto_dapat_dihapus(): void
    {
        Storage::fake('public');
        $user = $this->makeUser('borrower', $this->org);

        $this->actingAs($user)->put(route('profile.photo'), [
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ]);
        $path = $user->refresh()->photo_path;

        $this->actingAs($user)
            ->put(route('profile.photo'), ['remove_photo' => 1])
            ->assertRedirect();

        $this->assertNull($user->refresh()->photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_upload_menolak_tipe_beratasan(): void
    {
        Storage::fake('public');
        $user = $this->makeUser('borrower', $this->org);

        $this->actingAs($user)
            ->put(route('profile.photo'), [
                'photo' => UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->refresh()->photo_path);
    }

    /* ── Detail organisasi ───────────────────────────────────────────── */

    public function test_detail_organisasi_menampilkan_jurusan_kelas_dan_ruang(): void
    {
        $this->student($this->ipa, 'Satu');
        $this->student($this->ipa, 'Dua');
        Classroom::create(['organization_id' => $this->org->id, 'name' => 'R. 1-A', 'capacity' => 36]);

        $this->actingAs($this->admin)
            ->get(route('admin.organizations.show', $this->org))
            ->assertOk()
            ->assertSee('Struktur Sekolah', false)
            ->assertSee('SMA', false)
            ->assertSee('IPA', false)
            ->assertSee('X IPA 1', false)
            ->assertSee('R. 1-A', false)
            ->assertSee('2/30', false); // jumlah siswa nyata, bukan 0
    }

    public function test_organisasi_tanpa_struktur_tidak_error(): void
    {
        $empty = $this->makeOrg();

        $this->actingAs($this->admin)
            ->get(route('admin.organizations.show', $empty))
            ->assertOk()
            ->assertSee('Belum ada struktur sekolah', false);
    }
}
