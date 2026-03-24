<?php
// TEST TEMPORAL — esborrar després
$info = [
    '__DIR__'      => __DIR__,
    'php_user'     => function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user(),
    'process_user' => get_current_user(),
];

$testFile = __DIR__ . '/data/users/_test_write.json';
$result = file_put_contents($testFile, '{"test":true}');
$info['write_data_users'] = $result !== false ? "OK ($result bytes)" : "FALLA: " . error_get_last()['message'];
if ($result !== false) unlink($testFile);

$testFile2 = __DIR__ . '/data/_test_write.json';
$result2 = file_put_contents($testFile2, '{"test":true}');
$info['write_data'] = $result2 !== false ? "OK ($result2 bytes)" : "FALLA: " . error_get_last()['message'];
if ($result2 !== false) unlink($testFile2);

$testFile3 = __DIR__ . '/_test_write.json';
$result3 = file_put_contents($testFile3, '{"test":true}');
$info['write_root'] = $result3 !== false ? "OK ($result3 bytes)" : "FALLA: " . error_get_last()['message'];
if ($result3 !== false) unlink($testFile3);

// Test LOCK_EX
$testFile4 = __DIR__ . '/data/users/_test_lock.json';
$result4 = file_put_contents($testFile4, '{"test":true}', LOCK_EX);
$info['write_lock_ex'] = $result4 !== false ? "OK ($result4 bytes)" : "FALLA: " . error_get_last()['message'];
if ($result4 !== false) unlink($testFile4);

header('Content-Type: application/json');
echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
