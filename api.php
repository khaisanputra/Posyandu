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
        'owner_field' => 'created_by_user_id',
    ],
    'ibu' => [
        'table' => 'ibu_hamil',
        'pk' => 'id_ibu',
        'fields' => ['nama', 'usia_kehamilan_minggu', 'alamat'],
        'order' => 'id_ibu DESC',
        'owner_field' => 'created_by_user_id',
    ],
    'imunisasi' => [
        'table' => 'imunisasi',
        'pk' => 'id_imunisasi',
        'fields' => ['nama_balita', 'jenis_imunisasi', 'tanggal'],
        'order' => 'id_imunisasi DESC',
        'owner_field' => 'created_by_user_id',
    ],
    'pertumbuhan' => [
        'table' => 'pertumbuhan',
        'pk' => 'id_pertumbuhan',
        'fields' => ['nama_balita', 'berat_kg', 'tinggi_cm', 'tanggal'],
        'order' => 'id_pertumbuhan DESC',
        'owner_field' => 'created_by_user_id',
    ],
    'jadwal' => [
        'table' => 'jadwal',
        'pk' => 'id_jadwal',
        'fields' => ['tanggal', 'tempat', 'keterangan'],
        'order' => 'tanggal ASC, id_jadwal ASC',
        'owner_field' => 'created_by_user_id',
    ],
];

$userCreateAllowed = ['balita', 'ibu', 'imunisasi', 'pertumbuhan'];

function respond($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function ensureOwnerColumns(PDO $db, array $entities): void {
    $dbNameStmt = $db->query('SELECT DATABASE()');
    $dbName = (string) $dbNameStmt->fetchColumn();
    if ($dbName === '') {
        return;
    }

    $checkSql = 'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name';
    $checkStmt = $db->prepare($checkSql);

    foreach ($entities as $cfg) {
        $table = $cfg['table'];
        $ownerField = $cfg['owner_field'] ?? null;
        if (!$ownerField) {
            continue;
        }

        $checkStmt->execute([
            ':schema' => $dbName,
            ':table_name' => $table,
            ':column_name' => $ownerField,
        ]);
        $exists = (int) $checkStmt->fetchColumn() > 0;

        if (!$exists) {
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$ownerField}` INT NULL");
        }
    }
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
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$currentRole = current_user_role();

if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    $raw = file_get_contents('php://input');
    $body = $raw ? json_decode($raw, true) : [];
    if ($raw && json_last_error() !== JSON_ERROR_NONE) {
        respond(['ok' => false, 'message' => 'JSON tidak valid'], 400);
    }
}

try {
    ensureOwnerColumns($db, $entities);

    if ($method === 'GET' && $action === 'list') {
        $ownerField = $cfg['owner_field'] ?? null;
        $baseFields = array_map(static fn($f) => "{$cfg['table']}.{$f}", $cfg['fields']);
        $selectFields = "{$cfg['table']}.{$cfg['pk']}, " . implode(', ', $baseFields);
        $sql = "SELECT {$selectFields}";
        if ($ownerField) {
            $sql .= ", u.nama_lengkap AS input_oleh";
        }
        $sql .= " FROM {$cfg['table']}";
        if ($ownerField) {
            $sql .= " LEFT JOIN users u ON {$cfg['table']}.{$ownerField} = u.id_user";
        }
        $params = [];

        // Data milik user biasa hanya boleh dilihat oleh user penginput.
        if ($currentRole === 'warga' && $ownerField) {
            $sql .= " WHERE {$cfg['table']}.{$ownerField} = :current_user_id";
            $params[':current_user_id'] = $currentUserId;
        }

        $sql .= " ORDER BY {$cfg['order']}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        respond(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if (!is_pegawai()) {
        if ($method === 'POST' && $action === 'create' && in_array($entity, $userCreateAllowed, true)) {
            // User biasa diizinkan menambah data tertentu.
        } else {
            respond(['ok' => false, 'message' => 'Aksi ini hanya untuk pegawai'], 403);
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

        $ownerField = $cfg['owner_field'] ?? null;
        if ($ownerField) {
            $cols[] = $ownerField;
            $vals[] = ':' . $ownerField;
            $params[':' . $ownerField] = $currentUserId;
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

