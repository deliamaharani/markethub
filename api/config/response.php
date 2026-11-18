<?php
/** Helper respons JSON yang konsisten untuk seluruh endpoint API. */

function api_send($payload, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function api_ok($data = null, $message = 'OK', $status = 200, $meta = null)
{
    $out = ['success' => true, 'message' => $message, 'data' => $data];
    if ($meta !== null) {
        $out['meta'] = $meta;
    }
    api_send($out, $status);
}

function api_fail($message, $status = 400, $errors = null)
{
    $out = ['success' => false, 'message' => $message];
    if ($errors) {
        $out['errors'] = $errors;
    }
    api_send($out, $status);
}

/** Membaca body request: JSON, atau form-urlencoded (untuk PUT/DELETE). */
function api_input()
{
    static $in = null;
    if ($in !== null) {
        return $in;
    }
    $raw = file_get_contents('php://input');
    $in = [];
    if ($raw !== '' && $raw !== false) {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($ct, 'application/json') !== false) {
            $json = json_decode($raw, true);
            if (!is_array($json)) {
                api_fail('Body JSON tidak valid.', 400);
            }
            $in = $json;
        } else {
            parse_str($raw, $in);
        }
    }
    if (!$in && $_POST) {
        $in = $_POST;
    }
    return $in;
}

/** Pastikan method termasuk yang didukung; selain itu 405. */
function api_methods(array $allowed)
{
    $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($m === 'OPTIONS') {
        header('Allow: ' . implode(', ', $allowed));
        api_send(['success' => true, 'message' => 'OK'], 204);
    }
    if (!in_array($m, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        api_fail('Method ' . $m . ' tidak didukung. Gunakan: ' . implode(', ', $allowed) . '.', 405);
    }
    return $m;
}

function api_page_params($defaultLimit = 10, $maxLimit = 50)
{
    $limit = min($maxLimit, max(1, (int) ($_GET['limit'] ?? $defaultLimit)));
    return [max(1, (int) ($_GET['page'] ?? 1)), $limit];
}
