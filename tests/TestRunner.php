<?php
/**
 * TEST RUNNER — Cartilla Virtual Spai-T
 * Ejecuta todas las pruebas y verifica que las funcionalidades principales funcionan
 *
 * Uso: php tests/TestRunner.php
 */

define('TEST_MODE', true);
define('PROJECT_ROOT', __DIR__ . '/..');

// Colores para terminal
class Colors {
    const GREEN = "\033[92m";
    const RED = "\033[91m";
    const YELLOW = "\033[93m";
    const BLUE = "\033[94m";
    const WHITE = "\033[97m";
    const RESET = "\033[0m";
}

// Clase base para tests
abstract class TestCase {
    protected $passed = 0;
    protected $failed = 0;
    protected $warnings = 0;

    abstract public function run(): void;

    protected function assert($condition, $message) {
        if ($condition) {
            $this->passed++;
            echo Colors::GREEN . "  ✅ " . Colors::RESET . $message . "\n";
        } else {
            $this->failed++;
            echo Colors::RED . "  ❌ " . Colors::RESET . $message . "\n";
        }
    }

    protected function warn($message) {
        $this->warnings++;
        echo Colors::YELLOW . "  ⚠️  " . Colors::RESET . $message . "\n";
    }

    protected function info($message) {
        echo Colors::BLUE . "  ℹ️  " . Colors::RESET . $message . "\n";
    }

    public function getStats() {
        return [
            'passed'  => $this->passed,
            'failed'  => $this->failed,
            'warnings' => $this->warnings,
            'total'   => $this->passed + $this->failed + $this->warnings,
        ];
    }
}

// ============= TEST SUITE =============

echo "\n" . Colors::BLUE . "════════════════════════════════════════" . Colors::RESET . "\n";
echo Colors::BLUE . "  🧪 TEST SUITE — Cartilla Virtual Spai-T" . Colors::RESET . "\n";
echo Colors::BLUE . "════════════════════════════════════════" . Colors::RESET . "\n\n";

// Incluir tests
require_once 'PermissionTest.php';
require_once 'AuthTest.php';
require_once 'FeaturesTest.php';

// Ejecutar tests
$tests = [
    'PermissionTest' => new PermissionTest(),
    'AuthTest'       => new AuthTest(),
    'FeaturesTest'   => new FeaturesTest(),
];

$total_stats = [
    'passed'   => 0,
    'failed'   => 0,
    'warnings' => 0,
];

foreach ($tests as $name => $test) {
    echo Colors::BLUE . "\n📋 " . $name . "\n" . str_repeat("─", 50) . Colors::RESET . "\n";
    $test->run();
    $stats = $test->getStats();

    $total_stats['passed'] += $stats['passed'];
    $total_stats['failed'] += $stats['failed'];
    $total_stats['warnings'] += $stats['warnings'];

    echo "\n";
}

// Resumen final
echo Colors::BLUE . "\n════════════════════════════════════════" . Colors::RESET . "\n";
echo Colors::BLUE . "📊 RESUMEN FINAL" . Colors::RESET . "\n";
echo Colors::BLUE . "════════════════════════════════════════\n" . Colors::RESET;
echo Colors::GREEN . "  ✅ Pasadas: " . $total_stats['passed'] . Colors::RESET . "\n";
echo Colors::RED . "  ❌ Fallidas: " . $total_stats['failed'] . Colors::RESET . "\n";
echo Colors::YELLOW . "  ⚠️  Advertencias: " . $total_stats['warnings'] . Colors::RESET . "\n";
echo "\n";

if ($total_stats['failed'] === 0) {
    echo Colors::GREEN . "✨ ¡TODAS LAS PRUEBAS PASARON! ✨\n" . Colors::RESET;
    exit(0);
} else {
    echo Colors::RED . "⚠️  HAY PRUEBAS QUE FALLARON\n" . Colors::RESET;
    echo Colors::YELLOW . "Revisa los errores arriba para más detalles.\n" . Colors::RESET;
    exit(1);
}
?>
