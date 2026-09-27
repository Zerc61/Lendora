<?php

// database/seeders/SchoolStructureSeeder.php
namespace Database\Seeders;

use App\Enums\EducationLevel;
use App\Enums\UserStatus;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Category;
use App\Models\Classroom;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Struktur sekolah + siswa.
 *
 * Requirement: minimal satu organisasi punya 6 kelas, 25 siswa per kelas.
 * Semua seeder ini idempotent (firstOrCreate / updateOrCreate) supaya aman
 * dijalankan berulang kali.
 */
class SchoolStructureSeeder extends Seeder
{
    /** Jenjang → jurusan/peminatan. */
    private const PROGRAMS = [
        'sd' => ['Umum'],
        'smp' => ['Umum'],
        'sma' => ['IPA', 'IPS', 'Bahasa'],
        'smk' => ['RPL', 'TKJ', 'TKR', 'TBS', 'TAS', 'AKL'],
        'ma' => ['IPS', 'IIM'],
    ];

    /** Jumlahsiswa per kelas. */
    private const STUDENTS_PER_CLASS = 25;

    private const SCHOOL_YEAR = '2026/2027';

    /** Cache hash password agar bcrypt tidak dijalankan berulang. */
    private static ?string $passwordHash = null;

    public function run(): void
    {
        $org = Organization::updateOrCreate(
            ['code' => 'SMK-NUS'],
            [
                'name' => 'SMK Nusantara',
                'status' => 'active',
                'description' => 'Sekolah menengah kejuruan — 5 jenjang, 6 ruang kelas, 150+ siswa.',
            ],
        );

        $second = Organization::updateOrCreate(
            ['code' => 'SMP-MERDEKA'],
            [
                'name' => 'SMP Merdeka Belajar',
                'status' => 'active',
                'description' => 'SMP negeri — 3 kelas, 75 siswa.',
            ],
        );

        $this->seedStructure($org, [
            'sd' => 2,
            'smp' => 3,
            'sma' => 2,
            'smk' => 2,
            'ma' => 1,
        ], self::STUDENTS_PER_CLASS);

        $this->seedStructure($second, [
            'smp' => 3,
        ], self::STUDENTS_PER_CLASS);

        $this->seedClassrooms($org, ['R. 1-A', 'R. 1-B', 'R. 2-A', 'Lab Komputer', 'Lab RPL', 'Ruang Multimedia']);
        $this->seedClassrooms($second, ['R. 7-A', 'R. 7-B', 'R. 8-A']);

        $this->seedAssetsPerClassroom($org);
    }

    /**
     * Untuk tiap jenjang: buat program, kelas, dan siswa di tiap kelas.
     * Nilai $classesPerProgram = jumlah kelas PER program.
     */
    private function seedStructure(Organization $org, array $classesPerLevel, int $studentsPerClass): void
    {
        foreach ($classesPerLevel as $level => $classesPerProgram) {
            $levelEnum = EducationLevel::from($level);

            foreach (self::PROGRAMS[$level] as $programName) {
                $program = Program::updateOrCreate(
                    ['organization_id' => $org->id, 'code' => Str::upper($level.'-'.$programName)],
                    [
                        'education_level' => $levelEnum,
                        'name' => $programName,
                        'description' => "Program {$programName} ({$levelEnum->label()})",
                    ],
                );

                for ($n = 1; $n <= $classesPerProgram; $n++) {
                    $className = $levelEnum->hasNumberedClasses()
                        ? $this->className($level, $programName, $n)
                        : $levelEnum->label().' Kelas '.$n;

                    $class = SchoolClass::updateOrCreate(
                        ['program_id' => $program->id, 'name' => $className, 'school_year' => self::SCHOOL_YEAR],
                        ['capacity' => 30],
                    );

                    $this->seedStudents($org, $class, $program, $studentsPerClass);
                }
            }
        }
    }

    private function className(string $level, string $program, int $n): string
    {
        return match ($level) {
            'smp' => 'VIII '.$program.' '.$n,
            'sma' => 'X '.$program.' '.$n,
            'smk' => 'X '.$program.' '.$n,
            'ma' => 'X '.$program.' '.$n,
            default => $program.' '.$n,
        };
    }

    /** Bulk insert siswa — 150 baris per organisasi, satu per statement. */
    private function seedStudents(Organization $org, SchoolClass $class, Program $program, int $count): void
    {
        $existing = $class->students()->pluck('email')->all();
        if (count($existing) >= $count) {
            return; // sudah lengkap
        }

        $rows = [];
        $now = now();
        // bcrypt 150× terlalu lambat (~45s). Satu hash cukup: password sama,
        // hash berbeda tidak menambah keamanan untuk data demo.
        $hashed = self::$passwordHash ??= bcrypt('password');

        for ($i = 1; $i <= $count; $i++) {
            // Email harus unik lintas jenjang DAN lintas organisasi:
            // program "SMA · IPS" vs "MA · IPS" menghasilkan kelas "X IPS 1",
            // dan dua sekolah bisa punya kelas "VIII Umum 1" yang sama.
            // Karena itu kode organisasi + kode program ikut masuk.
            $slug = Str::slug($class->name);
            $email = Str::lower($org->code.'-'.$program->code).'-'.$slug.'-'.$i.'@siswa.lendora.test';

            if (in_array($email, $existing, true)) {
                continue;
            }

            $gender = $i % 2 === 0 ? 'female' : 'male';
            $first = $this->firstName($gender);
            $last = $this->lastName();

            $rows[] = [
                'organization_id' => $org->id,
                'school_class_id' => $class->id,
                'name' => $first.' '.$last,
                'email' => $email,
                'password' => $hashed,
                'status' => UserStatus::Active->value,
                'identity_number' => $this->identityNumber($program, $i),
                'birth_date' => $now->copy()->subYears(random_int(15, 19))->subDays(random_int(0, 364))->toDateString(),
                'gender' => $gender,
                'phone' => '08'.random_int(10, 99).str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
                'address' => 'Jl. Merdeka No '.random_int(1, 200).', '.$org->name,
                'bio' => 'Siswa '.$program->fullLabel().' semester ini.',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            DB::table('users')->insert($rows);

            // Borrower role untuk seluruh siswa (bulk, tanpa N+1)
            $borrowerRoleId = DB::table('roles')->where('name', 'borrower')->value('id');
            if ($borrowerRoleId) {
                $userIds = DB::table('users')->whereIn('email', array_column($rows, 'email'))->pluck('id');
                $pivot = [];
                foreach ($userIds as $id) {
                    $pivot[] = [
                        'role_id' => $borrowerRoleId,
                        'model_type' => 'App\Models\User',
                        'model_id' => $id,
                    ];
                }
                if ($pivot) {
                    DB::table('model_has_roles')->insert($pivot);
                }
            }
        }
    }

    private function seedClassrooms(Organization $org, array $names): void
    {
        foreach ($names as $name) {
            Classroom::updateOrCreate(
                ['organization_id' => $org->id, 'name' => $name],
                ['capacity' => 36],
            );
        }
    }

    /** Satu aset contoh per ruang kelas agar org punya inventaris nyata. */
    private function seedAssetsPerClassroom(Organization $org): void
    {
        $category = Category::firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'Perlengkapan Kelas'],
        );
        $type = AssetType::firstOrCreate(
            ['organization_id' => $org->id, 'category_id' => $category->id, 'name' => 'Proyektor Kelas'],
        );

        $seq = 0;
        foreach ($org->classrooms()->get() as $room) {
            $seq++;
            $location = Location::firstOrCreate(
                ['organization_id' => $org->id, 'name' => $room->name],
            );

            Asset::firstOrCreate(
                ['organization_id' => $org->id, 'asset_code' => 'LND-'.$org->code.'-PRJ-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT)],
                [
                    'asset_type_id' => $type->id,
                    'location_id' => $location->id,
                    'notes' => 'Proyektor kelas di '.$room->name,
                    'status' => 'available',
                    'condition' => 'good',
                ],
            );
        }
    }

    private function identityNumber(Program $program, int $i): string
    {
        $prefix = match ($program->education_level) {
            EducationLevel::SD => 'SD',
            EducationLevel::SMP => 'SMP',
            EducationLevel::SMA => 'SMA',
            EducationLevel::SMK => 'SMK',
            EducationLevel::MA => 'MA',
        };

        return sprintf('%s-%04d-%04d', $prefix, random_int(1000, 9999), $i);
    }

    private function firstName(string $gender): string
    {
        $male = ['Ahmad', 'Budi', 'Candra', 'Dimas', 'Eko', 'Fajar', 'Galih', 'Hendra', 'Irfan', 'Joko',
            'Krisna', 'Lukman', 'Miko', 'Nanda', 'Oscar', 'Panji', 'Rizky', 'Sigit', 'Taufik', 'Umar'];
        $female = ['Ayu', 'Bella', 'Citra', 'Dinda', 'Elsa', 'Fitri', 'Gita', 'Hesti', 'Indah', 'Julia',
            'Kartika', 'Lina', 'Maya', 'Nadia', 'Olivia', 'Putri', 'Rina', 'Sari', 'Tari', 'Wulan'];

        $pool = $gender === 'male' ? $male : $female;

        return $pool[random_int(0, count($pool) - 1)];
    }

    private function lastName(): string
    {
        $pool = ['Pratama', 'Wijaya', 'Saputra', 'Ramadhan', 'Nugroho', 'Kusuma', 'Hidayat', 'Maulana',
            'Setiawan', 'Permana', 'Anggraini', 'Safitri', 'Lestari', 'Utami', 'Handayani', 'Puspita'];

        return $pool[random_int(0, count($pool) - 1)];
    }
}
