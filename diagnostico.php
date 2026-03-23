<?php
/**
 * SCRIPT DE DIAGNÓSTICO - Cartilla Virtual Spai-T
 * Acceso: www.iespai.com/diagnostico.php
 */

echo "<h1>🔍 Diagnóstico - Cartilla Virtual Spai-T</h1>";
echo "<hr>";

// 1. PHP Info básica
echo "<h2>PHP y Servidor</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Servidor: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido') . "\n";
echo "User ejecutando PHP: " . get_current_user() . "\n";
echo "PID: " . getmypid() . "\n";
echo "</pre>";

// 2. Directorios
echo "<h2>Permisos de Directorios</h2>";
$dirs = [
    __DIR__ => 'Raíz del proyecto',
    __DIR__ . '/data' => 'data/',
    __DIR__ . '/data/users' => 'data/users/',
    __DIR__ . '/includes' => 'includes/',
    __DIR__ . '/assets' => 'assets/',
];

echo "<table border='1'>";
echo "<tr><th>Directorio</th><th>Existe</th><th>Permisos</th><th>Propietario</th><th>Escribible</th></tr>";
foreach ($dirs as $path => $label) {
    $exists = is_dir($path) ? '✅' : '❌';
    $perms = is_dir($path) ? substr(sprintf('%o', fileperms($path)), -4) : '-';
    $writable = is_writable($path) ? '✅' : '❌';

    // Obtener propietario
    $owner = 'N/A';
    if (is_dir($path)) {
        $uid = fileowner($path);
        $owner = posix_getpwuid($uid)['name'] ?? $uid;
    }

    echo "<tr>";
    echo "<td><strong>$label</strong></td>";
    echo "<td>$exists</td>";
    echo "<td>$perms</td>";
    echo "<td>$owner</td>";
    echo "<td>$writable</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Archivos críticos
echo "<h2>Archivos Críticos</h2>";
$files = [
    'index.php',
    'cartilla.php',
    'includes/config.php',
    'includes/auth.php',
    'includes/user.php',
    'includes/crypto.php',
];

echo "<table border='1'>";
echo "<tr><th>Archivo</th><th>Existe</th><th>Legible</th></tr>";
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path) ? '✅' : '❌';
    $readable = is_readable($path) ? '✅' : '❌';
    echo "<tr><td>$file</td><td>$exists</td><td>$readable</td></tr>";
}
echo "</table>";

// 4. Extensions PHP
echo "<h2>Extensiones PHP Requeridas</h2>";
$extensions = ['json', 'session', 'openssl', 'mbstring'];
echo "<ul>";
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "<li>$status $ext</li>";
}
echo "</ul>";

// 5. Prueba de lectura/escritura
echo "<h2>Prueba de Lectura/Escritura</h2>";
$test_file = __DIR__ . '/data/users/.test_' . time() . '.txt';
$write_ok = @file_put_contents($test_file, 'test', LOCK_EX);
$read_ok = @file_get_contents($test_file);
@unlink($test_file);

echo "<pre>";
echo "Escritura en data/users/: " . ($write_ok ? '✅ OK' : '❌ FALLÓ') . "\n";
echo "Lectura en data/users/: " . ($read_ok ? '✅ OK' : '❌ FALLÓ') . "\n";
echo "</pre>";

// 6. Prueba de Session
echo "<h2>Prueba de Sessions</h2>";
if (session_status() === PHP_SESSION_NONE) {
    $session_start = @session_start();
} else {
    $session_start = true;
}
echo "<pre>";
echo "session_start(): " . ($session_start ? '✅ OK' : '❌ FALLÓ') . "\n";
echo "Session ID: " . (session_id() ? session_id() : '(vacío)') . "\n";
echo "Session save_path: " . ini_get('session.save_path') . "\n";
echo "</pre>";

// 7. Logs de PHP
echo "<h2>Configuración PHP</h2>";
echo "<pre>";
echo "error_reporting: " . error_reporting() . "\n";
echo "display_errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "\n";
echo "log_errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n";
echo "error_log: " . (ini_get('error_log') ?? '(default)') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "s\n";
echo "</pre>";

echo "<hr>";
echo "<p style='color: green;'>✅ Si ves este mensaje, PHP se está ejecutando correctamente.</p>";
echo "<p>Para problemas específicos, revisa los logs del servidor:</p>";
echo "<code>/var/log/apache2/error.log</code> (Apache)<br>";
echo "<code>/var/log/nginx/error.log</code> (Nginx)<br>";
?>
