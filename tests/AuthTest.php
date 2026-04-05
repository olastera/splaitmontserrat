<?php
/**
 * AUTH TESTS — Verifica funcionalidades de autenticación
 */

class AuthTest extends TestCase {
    private $test_user_id = '';

    public function run(): void {
        $this->test_user_id = 'test-user-' . time();
        $this->testIncludesLoad();
        $this->testUserCreation();
        $this->testUserLookup();
        $this->testPasswordEncryption();
        $this->testLogin();
        $this->testUserDeletion();
    }

    private function testIncludesLoad(): void {
        $this->info("Verificando que los archivos include cargan correctamente...");

        try {
            require_once PROJECT_ROOT . '/includes/config.php';
            $this->assert(true, "config.php cargado");
        } catch (Exception $e) {
            $this->assert(false, "Error en config.php: " . $e->getMessage());
            return;
        }

        try {
            require_once PROJECT_ROOT . '/includes/auth.php';
            $this->assert(true, "auth.php cargado");
        } catch (Exception $e) {
            $this->assert(false, "Error en auth.php: " . $e->getMessage());
        }

        try {
            require_once PROJECT_ROOT . '/includes/user.php';
            $this->assert(true, "user.php cargado");
        } catch (Exception $e) {
            $this->assert(false, "Error en user.php: " . $e->getMessage());
        }

        try {
            require_once PROJECT_ROOT . '/includes/crypto.php';
            $this->assert(true, "crypto.php cargado");
        } catch (Exception $e) {
            $this->assert(false, "Error en crypto.php: " . $e->getMessage());
        }
    }

    private function testUserCreation(): void {
        $this->info("Probando creación de usuarios...");

        require_once PROJECT_ROOT . '/includes/user.php';

        try {
            $user = create_user([
                'nom' => 'Test User',
                'email' => 'test-' . time() . '@example.com',
                'telefon' => '600000000',
                'password' => 'TestPass123',
                'ruta' => 'llarga',
                'motivacio' => 'Prueba',
            ]);

            $this->assert(!empty($user['id']), "Usuario creado con ID: " . substr($user['id'], 0, 8) . "...");
            $this->test_user_id = $user['id'];

            $file_exists = file_exists(PROJECT_ROOT . '/data/users/' . $user['id'] . '.json');
            $this->assert($file_exists, "Archivo del usuario guardado en data/users/");
        } catch (Exception $e) {
            $this->assert(false, "Error creando usuario: " . $e->getMessage());
        }
    }

    private function testUserLookup(): void {
        $this->info("Probando búsqueda de usuarios...");

        require_once PROJECT_ROOT . '/includes/user.php';

        try {
            $user = get_user($this->test_user_id);
            $this->assert($user !== null, "Usuario encontrado por ID");
            $this->assert($user['nom'] === 'Test User', "Datos del usuario correctos");
        } catch (Exception $e) {
            $this->assert(false, "Error buscando usuario: " . $e->getMessage());
        }
    }

    private function testPasswordEncryption(): void {
        $this->info("Probando cifrado de contraseñas...");

        require_once PROJECT_ROOT . '/includes/crypto.php';

        try {
            $plain = 'TestPassword123';
            $encrypted = encrypt_password($plain);

            $this->assert($encrypted !== $plain, "Contraseña cifrada (no es texto plano)");

            $decrypted = decrypt_password($encrypted);
            $this->assert($decrypted === $plain, "Contraseña descifrada correctamente");
        } catch (Exception $e) {
            $this->assert(false, "Error en cifrado: " . $e->getMessage());
        }
    }

    private function testLogin(): void {
        $this->info("Probando login...");

        require_once PROJECT_ROOT . '/includes/auth.php';
        require_once PROJECT_ROOT . '/includes/user.php';

        try {
            // Crear usuario de prueba
            $user = create_user([
                'nom' => 'Login Test',
                'email' => 'login-test-' . time() . '@example.com',
                'telefon' => '611111111',
                'password' => 'LoginTest123',
                'ruta' => 'curta',
            ]);

            // Probar login con email
            $result = login_user($user['email'], 'LoginTest123');
            $this->assert($result === true, "Login con email funciona");

            // Probar login con email incorrecto
            session_destroy();
            $_SESSION = [];
            $result = login_user($user['email'], 'PasswordWrong');
            $this->assert($result === false, "Login rechaza contraseña incorrecta");

            // Limpiar
            @unlink(PROJECT_ROOT . '/data/users/' . $user['id'] . '.json');
        } catch (Exception $e) {
            $this->assert(false, "Error en login: " . $e->getMessage());
        }
    }

    private function testUserDeletion(): void {
        $this->info("Probando eliminación de usuarios...");

        require_once PROJECT_ROOT . '/includes/user.php';

        try {
            $file = PROJECT_ROOT . '/data/users/' . $this->test_user_id . '.json';

            if (file_exists($file)) {
                @unlink($file);
                $this->assert(!file_exists($file), "Usuario de prueba eliminado");
            } else {
                $this->warn("Usuario de prueba ya no existe");
            }
        } catch (Exception $e) {
            $this->assert(false, "Error eliminando usuario: " . $e->getMessage());
        }
    }
}
?>
