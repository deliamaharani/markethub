<?php
define('API_REQUEST', true);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/config/response.php';
require_once __DIR__ . '/middleware/auth.php';

$method = api_methods(['GET', 'POST', 'PUT', 'DELETE']);
$id = (int) ($_GET['id'] ?? 0);

function kategori_out(array $k)
{
    return ['id' => (int) $k['id'], 'nama_kategori' => $k['nama_kategori'], 'slug' => $k['slug'], 'jumlah_produk' => (int) ($k['jml'] ?? 0)];
}
function kategori_get($id)
{
    return db_row('SELECT k.*, (SELECT COUNT(*) FROM produk p WHERE p.kategori_id = k.id) AS jml FROM kategori k WHERE k.id = ?', [(int) $id]);
}
function kategori_check(array $in, $exceptId = 0)
{
    $nama = trim((string) ($in['nama_kategori'] ?? ''));
    $slug = slugify(($in['slug'] ?? '') !== '' ? $in['slug'] : $nama);
    $err = [];
    if (mb_strlen($nama) < 3 || mb_strlen($nama) > 100) {
        $err['nama_kategori'] = 'Nama kategori harus 3–100 karakter.';
    }
    if ($slug === '' || strlen($slug) > 120) {
        $err['slug'] = 'Slug tidak valid.';
    } elseif (db_val('SELECT id FROM kategori WHERE slug = ? AND id <> ?', [$slug, (int) $exceptId])) {
        $err['slug'] = 'Slug sudah dipakai.';
    }
    return [$nama, $slug, $err];
}

if ($method === 'GET') {
    if ($id) {
        $k = kategori_get($id);
        $k ? api_ok(kategori_out($k)) : api_fail('Kategori tidak ditemukan.', 404);
    }
    api_ok(array_map('kategori_out', kategori_all()));
}

api_require(['admin']);

if ($method === 'POST') {
    [$nama, $slug, $err] = kategori_check(api_input());
    if ($err) {
        api_fail('Validasi gagal.', 422, $err);
    }
    db_exec('INSERT INTO kategori (nama_kategori, slug) VALUES (?, ?)', [$nama, $slug]);
    api_ok(kategori_out(kategori_get(db_id())), 'Kategori berhasil dibuat.', 201);
}

if (!$id || !($k = kategori_get($id))) {
    api_fail('Kategori tidak ditemukan. Sertakan parameter ?id=.', 404);
}
if ($method === 'PUT') {
    $in = api_input();
    $in += ['nama_kategori' => $k['nama_kategori'], 'slug' => $k['slug']];
    [$nama, $slug, $err] = kategori_check($in, $id);
    if ($err) {
        api_fail('Validasi gagal.', 422, $err);
    }
    db_exec('UPDATE kategori SET nama_kategori = ?, slug = ? WHERE id = ?', [$nama, $slug, $id]);
    api_ok(kategori_out(kategori_get($id)), 'Kategori berhasil diperbarui.');
}
// DELETE
if ((int) $k['jml'] > 0) {
    api_fail('Kategori masih memiliki ' . (int) $k['jml'] . ' produk sehingga tidak dapat dihapus.', 409);
}
db_exec('DELETE FROM kategori WHERE id = ?', [$id]);
api_ok(null, 'Kategori berhasil dihapus.');
