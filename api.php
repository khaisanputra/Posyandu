<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

$entities = [
    'balita' => [
        'table' => 'balita',
        'pk' => 'id_balita',
        'fields' => ['nama', 'tanggal_lahir', 'jenis_kelamin', 'nama_ibu'],
        'order' => 'id_balita DESC',
    ],
    'ibu' => [
        'table' => 'ibu_hamil',
        'pk' => 'id_ibu',
        'fields' => ['nama', 'usia_kehamilan_minggu', 'alamat'],
        'order' => 'id_ibu DESC',
    ],
    'imunisasi' => [
        'table' => 'imunisasi',
        'pk' => 'id_imunisasi',
        'fields' => ['nama_balita', 'jenis_imunisasi', 'tanggal'],
        'order' => 'id_imunisasi DESC',
    ],
    'pertumbuhan' => [
        'table' => 'pertumbuhan',
        'pk' => 'id_pertumbuhan',
        'fields' => ['nama_balita', 'berat_kg', 'tinggi_cm', 'tanggal'],
        'order' => 'id_pertumbuhan DESC',
    ],
    'jadwal' => [
        'table' => 'jadwal',
        'pk' => 'id_jadwal',
        'fields' => ['tanggal', 'tempat', 'keterangan'],
        'order' => 'tanggal ASC, id_jadwal ASC',
    ],
];

$userCreateAllowed = ['balita', 'ibu', 'imunisasi', 'pertumbuhan'];

function respond($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (!is_logged_in()) {
    respond(['ok' => false, 'message' => 'Unauthorized'], 401);
}

$entity = $_GET['entity'] ?? '';
$action = $_GET['action'] ?? 'list';

if (!isset($entities[$entity])) {
    respond(['ok' => false, 'message' => 'Entity tidak valid'], 400);
}

$cfg = $entities[$entity];
$method = $_SERVER['REQUEST_METHOD'];
$body = [];

if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    $raw = file_get_contents('php://input');
    $body = $raw ? json_decode($raw, true) : [];
    if ($raw && json_last_error() !== JSON_ERROR_NONE) {
        respond(['ok' => false, 'message' => 'JSON tidak valid'], 400);
    }
}

try {
    if ($method === 'GET' && $action === 'list') {
        $stmt = $db->query("SELECT {$cfg['pk']}, " . implode(', ', $cfg['fields']) . " FROM {$cfg['table']} ORDER BY {$cfg['order']}");
        respond(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if (!is_admin()) {
        if ($method === 'POST' && $action === 'create' && in_array($entity, $userCreateAllowed, true)) {
            // User biasa diizinkan menambah data tertentu.
        } else {
            respond(['ok' => false, 'message' => 'Aksi ini hanya untuk admin'], 403);
        }
    }

    if ($method === 'POST' && $action === 'create') {
        $payload = $body['data'] ?? [];
        $cols = [];
        $vals = [];
        $params = [];

        foreach ($cfg['fields'] as $field) {
            if (array_key_exists($field, $payload)) {
                $cols[] = $field;
                $vals[] = ':' . $field;
                $params[':' . $field] = $payload[$field];
            }
        }

        if (!$cols) {
            respond(['ok' => false, 'message' => 'Data kosong'], 400);
        }

        $sql = "INSERT INTO {$cfg['table']} (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ')';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        respond(['ok' => true, 'id' => $db->lastInsertId()]);
    }

    if ($method === 'PUT' && $action === 'update') {
        $id = $body['id'] ?? null;
        $payload = $body['data'] ?? [];

        if (!$id) {
            respond(['ok' => false, 'message' => 'ID wajib diisi'], 400);
        }

        $sets = [];
        $params = [':id' => $id];
        foreach ($cfg['fields'] as $field) {
            if (array_key_exists($field, $payload)) {
                $sets[] = $field . ' = :' . $field;
                $params[':' . $field] = $payload[$field];
            }
        }

        if (!$sets) {
            respond(['ok' => false, 'message' => 'Tidak ada perubahan data'], 400);
        }

        $sql = "UPDATE {$cfg['table']} SET " . implode(', ', $sets) . " WHERE {$cfg['pk']} = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        respond(['ok' => true]);
    }

    if ($method === 'DELETE' && $action === 'delete') {
        $id = $body['id'] ?? null;
        if (!$id) {
            respond(['ok' => false, 'message' => 'ID wajib diisi'], 400);
        }

        $stmt = $db->prepare("DELETE FROM {$cfg['table']} WHERE {$cfg['pk']} = :id");
        $stmt->execute([':id' => $id]);

        respond(['ok' => true]);
    }

    respond(['ok' => false, 'message' => 'Method/action tidak didukung'], 405);
} catch (Throwable $e) {
    respond(['ok' => false, 'message' => $e->getMessage()], 500);
}
?>
