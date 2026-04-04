<?php
/**
 * PERMISSION TESTS — Verifica permisos de carpetas
 */

class PermissionTest extends TestCase {
    private $fix_requested = false;
    private $fixes_applied = [];

    public function run(): void {
        $this->testDirectoriesExist();
        $this->testDirectoriesWritable();
        $this->testFilesReadable();
        $this->testHtaccessPresent();
    }

    private function testDirectoriesExist(): void {
        $this->info("Verificando que existen directorios clave...");

        $directories = [
            PROJECT_ROOT . '/data' => 'data/',
            PROJECT_ROOT . '/data/users' => 'data/users/',
            PROJECT_ROOT . '/includes' => 'includes/',
            PROJECT_ROOT . '/admin' => 'admin/',
            PROJECT_ROOT . '/assets' => 'assets/',
        ];

        foreach ($directories as $path => $label) {
            if (is_dir($path)) {
                $this->assert(true, "Directorio existe: $label");
            } else {
                $this->assert(false, "Directorio NO existe: $label");
                $this->tryCreateDirectory($path, $label);
            }
        }
    }

    private function testDirectoriesWritable(): void {
        $this->info("Verificando permisos de escritura...");

        $writable_dirs = [
            PROJECT_ROOT . '/data/users' => 'data/users/ (para guardar usuarios)',
            PROJECT_ROOT . '/data' => 'data/ (para settings)',
        ];

        foreach ($writable_dirs as $path => $label) {
            if (is_writable($path)) {
                $this->assert(true, "Es escribible: $label");
            } else {
                $this->assert(false, "NO es escribible: $label");
                $this->tryFixPermissions($path, $label, 0755);
            }
        }
    }

    private function testFilesReadable(): void {
        $this->info("Verificando que archivos críticos se pueden leer...");

        $critical_files = [
            PROJECT_ROOT . '/index.php',
            PROJECT_ROOT . '/cartilla.php',
            PROJECT_ROOT . '/includes/config.php',
            PROJECT_ROOT . '/includes/auth.php',
            PROJECT_ROOT . '/includes/user.php',
            PROJECT_ROOT . '/includes/crypto.php',
        ];

        foreach ($critical_files as $file) {
            if (is_readable($file)) {
                $this->assert(true, "Legible: " . basename($file));
            } else {
                $this->assert(false, "NO legible: " . basename($file));
            }
        }
    }

    private function testHtaccessPresent(): void {
        $this->info("Verificando .htaccess de seguridad...");

        $htaccess_files = [
            PROJECT_ROOT . '/.htaccess' => 'raíz',
            PROJECT_ROOT . '/data/.htaccess' => 'data/ (protección)',
        ];

        foreach ($htaccess_files as $file => $label) {
            if (file_exists($file)) {
                $this->assert(true, ".htaccess presente ($label)");
            } else {
                $this->warn(".htaccess faltante ($label)");
                $this->tryCreateHtaccess($file, $label);
            }
        }
    }

    private function tryCreateDirectory($path, $label): void {
        $this->info("Intentando crear $label...");

        if (@mkdir($path, 0755, true)) {
            $this->assert(true, "Directorio creado: $label");
            $this->fixes_applied[] = "Crear directorio: $label";
        } else {
            $this->assert(false, "No se pudo crear: $label");
        }
    }

    private function tryFixPermissions($path, $label, $perms): void {
        if (!$this->fix_requested) {
            echo "\n";
            echo Colors::YELLOW . "  ⚠️  HAY PROBLEMAS DE PERMISOS" . Colors::RESET . "\n";
            echo Colors::YELLOW . "  ¿Deseas que intente arreglarlo automáticamente? [s/n]: " . Colors::RESET;
            $response = trim(fgets(STDIN));
            $this->fix_requested = true;

            if (strtolower($response) !== 's') {
                $this->info("Se saltaron las correcciones de permisos");
                return;
            }
        }

        if (@chmod($path, $perms)) {
            $this->assert(true, "Permisos arreglados: $label (755)");
            $this->fixes_applied[] = "Arreglar permisos: $label";
        } else {
            $this->warn("No se pudieron arreglar permisos: $label");
            $this->info("Intenta: sudo chmod 755 $path");
        }
    }

    private function tryCreateHtaccess($file, $label): void {
        if ($label === 'raíz') {
            $content = $this->getMainHtaccess();
        } else {
            $content = "Deny from all\n";
        }

        if (@file_put_contents($file, $content)) {
            $this->assert(true, ".htaccess creado ($label)");
            $this->fixes_applied[] = "Crear .htaccess: $label";
        } else {
            $this->warn("No se pudo crear .htaccess: $label");
        }
    }

    private function getMainHtaccess(): string {
        return <<<'EOT'
# Protecció carpeta data
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteRule ^data/ - [F,L]
</IfModule>

# Opcions de seguretat
Options -Indexes
ServerSignature Off

# Capçaleres de seguretat
<IfModule mod_headers.c>
  Header always set X-Content-Type-Options nosniff
  Header always set X-Frame-Options SAMEORIGIN
  Header always set X-XSS-Protection "1; mode=block"
</IfModule>

EOT;
    }
}
?>
