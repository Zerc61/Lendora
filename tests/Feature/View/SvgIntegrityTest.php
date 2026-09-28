<?php

namespace Tests\Feature\View;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integritas path SVG di view.
 *
 * Path data yang rusak TIDAK gagal saat render atau saat compile blade — error
 * baru muncul di browser dan sering kali hanya sebagai peringatan di console.
 * Contoh nyata: path `eye-off` di form login kehilangan `M` di awal, sehingga
 * ikon tidak pernah tampil dan halaman hanya diam-diam emits
 * "Expected moveto path command". Tidak ada test yang menangkapnya.
 *
 * Karena itu setiap <path d="…"> diperiksa di sini: harus ada command path
 * yang valid sebagai karakter pertama.
 */
class SvgIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /** Perintah path SVG yang sah di awal data. */
    private const COMMANDS = 'MmZzLlHhVvCcSsQqTtAa';

    /**
     * Halaman yang diperiksa. `guest: true` diambil tanpa login — /login
     * berada di middleware `guest` jadi akan redirect kalau sudah terautentikasi.
     *
     * @return array<string, array{path: string, guest: bool}>
     */
    public static function pages(): array
    {
        return [
            'halaman login' => ['path' => '/login', 'guest' => true],
            'halaman dashboard' => ['path' => '/dashboard', 'guest' => false],
        ];
    }

    public function test_semua_path_svg_dimulai_dengan_command_yang_valid(): void
    {
        // makeUser() menyeed role/permission otomatis bila belum ada.
        $admin = $this->makeUser('super-admin', $this->makeOrg(), 'svg-guard@test.local');

        $checked = 0;

        foreach (static::pages() as $label => $page) {
            $request = $page['guest'] ? $this->get($page['path']) : $this->actingAs($admin)->get($page['path']);
            $html = $request->assertOk()->getContent();

            preg_match_all('/<path\b[^>]*\bd="([^"]*)"/i', $html, $matches);

            foreach ($matches[1] as $index => $d) {
                $checked++;

                $first = ltrim($d)[0] ?? '';

                $this->assertContains(
                    $first,
                    str_split(self::COMMANDS),
                    sprintf(
                        '%s: <path> ke-%d punya data tidak valid — "%s". '
                        .'Harus diawali perintah path (M/m/Z/L/H/V/C/S/Q/T/A), bukan angka.',
                        $label,
                        $index + 1,
                        substr($d, 0, 60),
                    ),
                );
            }
        }

        $this->assertGreaterThan(0, $checked, 'Tidak ada <path> sama sekali — regex mungkin tidak cocok.');
    }

    public function test_tidak_ada_atribut_path_kosong(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<path\b[^>]*\bd="\s*"/i',
            $html,
            'Ada <path> dengan atribut d kosong.',
        );
    }
}
