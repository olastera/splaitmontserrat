<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/crypto.php';

function generate_uuid(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function user_file(string $id): string {
    return DATA_PATH . $id . '.json';
}

function save_user(array $user): bool {
    $file = user_file($user['id']);
    $json = json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($file, $json, LOCK_EX) !== false;
}

function create_user(array $data): array {
    $user = [
        'id'           => generate_uuid(),
        'nom'          => trim($data['nom']),
        'email'        => strtolower(trim($data['email'] ?? '')),
        'telefon'      => trim($data['telefon'] ?? ''),
        'password_enc' => encrypt_password($data['password']),
        'ruta'           => $data['ruta'] ?? 'curta',
        'motivacio'      => trim($data['motivacio'] ?? ''),
        'created_at'     => date('c'),
        'share_location' => false,
        'checkins'       => [],
    ];
    save_user($user);
    return $user;
}

function get_user(string $id): ?array {
    $file = user_file($id);
    if (!file_exists($file)) return null;
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    if (!is_array($data)) return null;

    // Normalització camps nous (compatibilitat vers enrere)
    if (!isset($data['share_location'])) {
        $data['share_location'] = false;
    }
    if (!isset($data['checkins'])) {
        $data['checkins'] = [];
    }
    // last_position no s'inicialitza — get_active_positions() ja gestiona el cas buit

    return $data;
}

function get_user_by_email(string $email): ?array {
    $email = strtolower(trim($email));
    foreach (get_all_users() as $user) {
        if (($user['email'] ?? '') === $email) return $user;
        if (($user['telefon'] ?? '') === $email) return $user;
    }
    return null;
}

function update_user(string $id, array $data): bool {
    $user = get_user($id);
    if (!$user) return false;
    foreach ($data as $k => $v) {
        $user[$k] = $v;
    }
    return save_user($user);
}

function get_all_users(): array {
    $files = glob(DATA_PATH . '*.json');
    if (!$files) return [];
    $users = [];
    foreach ($files as $file) {
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if (is_array($data)) {
            $users[] = $data;
        }
    }
    return $users;
}

function add_checkin(string $id, int $parada_id, array $test = []): bool {
    $user = get_user($id);
    if (!$user) return false;
    $checkin = [
        'parada_id' => $parada_id,
        'timestamp' => date('c'),
    ];
    if (!empty($test)) {
        $checkin['test'] = $test;
    }
    if ($parada_id === 0) {
        $checkin['tipus'] = 'inici';
    }
    $user['checkins'][] = $checkin;
    return save_user($user);
}

function has_checkin(string $id, int $parada_id): bool {
    $user = get_user($id);
    if (!$user) return false;
    foreach ($user['checkins'] as $c) {
        if ($c['parada_id'] === $parada_id) return true;
    }
    return false;
}

function reset_password(string $id, string $new_plain = ''): string {
    if (empty($new_plain)) {
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $new_plain = '';
        for ($i = 0; $i < 8; $i++) {
            $new_plain .= $chars[random_int(0, strlen($chars) - 1)];
        }
    }
    update_user($id, ['password_enc' => encrypt_password($new_plain)]);
    return $new_plain;
}

function user_shares_location(array $user): bool {
    return !empty($user['share_location']);
}

function set_share_location(string $id, bool $share): bool {
    $user = get_user($id);
    if (!$user) return false;
    $user['share_location'] = $share;
    // NO esborrar last_position mai — seguretat per menors
    return save_user($user);
}

function update_user_position(string $id, float $lat, float $lng, float $accuracy): bool {
    $user = get_user($id);
    if (!$user) return false;
    $user['last_position'] = [
        'lat'         => $lat,
        'lng'         => $lng,
        'accuracy'    => $accuracy,
        'timestamp'   => date('c'),
        'tracking_on' => user_shares_location($user),
    ];
    return save_user($user);
}

function get_last_checkin_name(array $user): string {
    if (empty($user['checkins'])) return 'Cap parada encara';
    $last = end($user['checkins']);
    $settings = get_settings();
    $parades = $settings['parades'] ?? [];
    if (empty($parades)) { global $PARADES; $parades = $PARADES; }
    foreach ($parades as $p) {
        if ($p['id'] === $last['parada_id']) return $p['nom'];
    }
    return 'Parada ' . $last['parada_id'];
}

function get_active_positions(): array {
    $users = get_all_users();
    $result = [];

    foreach ($users as $user) {
        if (empty($user['last_position'])) continue;

        $ts          = strtotime($user['last_position']['timestamp']);
        $minutes_ago = (time() - $ts) / 60;
        $tracking_on = $user['share_location'] ?? false;
        $finished    = has_checkin($user['id'], 10);

        if ($finished) {
            $status = 'finished';
        } elseif ($tracking_on && $minutes_ago <= 10) {
            $status = 'actiu';
        } elseif (!$tracking_on) {
            $status = 'tracking_off';
        } elseif ($minutes_ago <= 10) {
            $status = 'actiu';
        } elseif ($minutes_ago <= 30) {
            $status = 'desconnectat';
        } else {
            $status = 'perdut';
        }

        $result[] = [
            'id'           => $user['id'],
            'nom'          => $user['nom'],
            'ruta'         => $user['ruta'],
            'lat'          => $user['last_position']['lat'],
            'lng'          => $user['last_position']['lng'],
            'accuracy'     => $user['last_position']['accuracy'],
            'timestamp'    => $user['last_position']['timestamp'],
            'minutes_ago'  => round($minutes_ago),
            'tracking_on'  => $tracking_on,
            'status'       => $status,
            'parades_fetes' => count($user['checkins'] ?? []),
            'ultima_parada' => get_last_checkin_name($user),
        ];
    }

    return $result;
}

function get_user_progress(array $user, array $parades): array {
    $ruta = $user['ruta'] ?? 'curta';

    // Suportar format nou (rutes[]) i format antic (ruta string)
    $parades_ruta = array_filter($parades, function($p) use ($ruta) {
        if (isset($p['rutes'])) {
            return in_array($ruta, $p['rutes']);
        }
        return ($p['ruta'] ?? '') === 'ambdues' || ($p['ruta'] ?? '') === $ruta;
    });
    $parades_ruta = array_values($parades_ruta);

    $checkin_ids = array_column($user['checkins'] ?? [], 'parada_id');

    // No comptar punts d'inici en el progrés
    $parades_comptables = array_filter($parades_ruta, function($p) {
        return empty($p['inici']) && empty($p['es_inici']);
    });
    $total_comptables = count($parades_comptables);
    $completades = 0;
    foreach ($parades_comptables as $p) {
        if (in_array($p['id'], $checkin_ids)) $completades++;
    }

    // Detectar parada final (es_final o final)
    $id_final = null;
    foreach ($parades as $p) {
        if (!empty($p['es_final']) || !empty($p['final'])) {
            $id_final = $p['id'];
            break;
        }
    }
    $acabat = $id_final !== null
        ? in_array($id_final, $checkin_ids)
        : in_array(10, $checkin_ids);

    return [
        'total'       => $total_comptables,
        'completades' => $completades,
        'percent'     => $total_comptables > 0 ? round($completades / $total_comptables * 100) : 0,
        'acabat'      => $acabat,
    ];
}
