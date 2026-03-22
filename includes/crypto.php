<?php
function encrypt_password(string $plain): string {
    $key = substr(hash('sha256', CRYPTO_KEY), 0, 32);
    $iv  = openssl_random_pseudo_bytes(16);
    $enc = openssl_encrypt($plain, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . '::' . $enc);
}

function decrypt_password(string $encrypted): string {
    $key   = substr(hash('sha256', CRYPTO_KEY), 0, 32);
    $parts = explode('::', base64_decode($encrypted), 2);
    if (count($parts) !== 2) return '';
    [$iv, $enc] = $parts;
    return openssl_decrypt($enc, 'AES-256-CBC', $key, 0, $iv);
}
