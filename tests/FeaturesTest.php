<?php
/**
 * FEATURES TESTS — Verifica funcionalidades principales
 */

class FeaturesTest extends TestCase {
    private $test_user = null;

    public function run(): void {
        $this->test_user = null;
        $this->setup();
        $this->testConfigFunctions();
        $this->testCheckinSystem();
        $this->testRankingFunctions();
        $this->testPagesLoad();
        $this->cleanup();
    }

    private function setup(): void {
        $this->info("Preparando datos de prueba...");

        require_once PROJECT_ROOT . '/includes/user.php';
        require_once PROJECT_ROOT . '/includes/config.php';

        // Crear usuario de prueba
        $this->test_user = create_user([
            'nom' => 'Features Test User',
            'email' => 'features-' . time() . '@example.com',
            'telefon' => '622222222',
            'password' => 'FeaturesTest123',
            'ruta' => 'llarga',
            'motivacio' => 'Testing features',
        ]);

        $this->assert($this->test_user !== null, "Usuario de prueba creado");
    }

    private function testConfigFunctions(): void {
        $this->info("Verificando funciones de configuración...");

        require_once PROJECT_ROOT . '/includes/config.php';

        try {
            $settings = get_settings();
            $this->assert(is_array($settings), "get_settings() retorna un array");
            $this->assert(!empty($settings['event']), "Configuración de evento presente");
            $this->assert(isset($settings['parades']) && is_array($settings['parades']), "Configuración de parades presente");

            // Verificar que existen las funciones nuevas de ranking
            $this->assert(function_exists('get_overall_ranking'), "Función get_overall_ranking existe");
            $this->assert(function_exists('get_ranking_by_stop'), "Función get_ranking_by_stop existe");
            $this->assert(function_exists('get_medal'), "Función get_medal existe");
        } catch (Exception $e) {
            $this->assert(false, "Error en configuración: " . $e->getMessage());
        }
    }

    private function testCheckinSystem(): void {
        $this->info("Verificando sistema de check-in...");

        require_once PROJECT_ROOT . '/includes/user.php';

        try {
            // Agregar check-in
            $result = add_checkin($this->test_user['id'], 0, []);
            $this->assert($result === true, "Check-in agregado a parada 0 (Inici)");

            // Verificar que se guardó
            $user = get_user($this->test_user['id']);
            $checkins = $user['checkins'] ?? [];
            $this->assert(count($checkins) > 0, "Check-ins se guardaron en el usuario");

            // Probar has_checkin
            $has = has_checkin($this->test_user['id'], 0);
            $this->assert($has === true, "has_checkin() detecta check-in");

            // Agregar más check-ins para ranking
            add_checkin($this->test_user['id'], 1, []);
            add_checkin($this->test_user['id'], 2, []);

            $user = get_user($this->test_user['id']);
            $this->assert(count($user['checkins']) === 3, "Múltiples check-ins se guardan correctamente");
        } catch (Exception $e) {
            $this->assert(false, "Error en check-in: " . $e->getMessage());
        }
    }

    private function testRankingFunctions(): void {
        $this->info("Verificando funciones de ranking...");

        require_once PROJECT_ROOT . '/includes/config.php';
        require_once PROJECT_ROOT . '/includes/user.php';

        try {
            // Ranking general
            $ranking = get_overall_ranking();
            $this->assert(is_array($ranking), "get_overall_ranking() retorna un array");

            // Buscar nuestro usuario de prueba
            $found = false;
            foreach ($ranking as $rank_user) {
                if ($rank_user['id'] === $this->test_user['id']) {
                    $found = true;
                    $this->assert($rank_user['parades'] === 3, "Usuario aparece en ranking con 3 parades");
                    break;
                }
            }
            $this->assert($found, "Usuario de prueba aparece en ranking general");

            // Ranking por parada
            $ranking_p1 = get_ranking_by_stop(1);
            $this->assert(is_array($ranking_p1), "get_ranking_by_stop(1) retorna un array");

            $found_in_p1 = false;
            foreach ($ranking_p1 as $rank_user) {
                if ($rank_user['id'] === $this->test_user['id']) {
                    $found_in_p1 = true;
                    break;
                }
            }
            $this->assert($found_in_p1, "Usuario aparece en ranking de parada");

            // Medallas
            $medal1 = get_medal(1);
            $medal2 = get_medal(2);
            $medal3 = get_medal(3);
            $medal4 = get_medal(4);

            $this->assert($medal1 === '🥇', "Medalla 1ª correcta");
            $this->assert($medal2 === '🥈', "Medalla 2ª correcta");
            $this->assert($medal3 === '🥉', "Medalla 3ª correcta");
            $this->assert($medal4 === '  ', "Sin medalla para posición >3");
        } catch (Exception $e) {
            $this->assert(false, "Error en ranking: " . $e->getMessage());
        }
    }

    private function testPagesLoad(): void {
        $this->info("Verificando que las páginas principales cargan...");

        $pages = [
            'index.php' => 'Login/Registro',
            'cartilla.php' => 'Cartilla (requiere login)',
            'ranking.php' => 'Ranking público',
            'checkin.php' => 'API Check-in',
        ];

        foreach ($pages as $file => $desc) {
            $path = PROJECT_ROOT . '/' . $file;
            if (file_exists($path)) {
                $this->assert(true, "$file existe ($desc)");
            } else {
                $this->assert(false, "$file NO existe ($desc)");
            }
        }

        // Verificar carpetas admin
        $admin_files = [
            'admin/index.php' => 'Admin login',
            'admin/dashboard.php' => 'Admin dashboard',
            'admin/parades.php' => 'Gestión parades',
        ];

        foreach ($admin_files as $file => $desc) {
            $path = PROJECT_ROOT . '/' . $file;
            if (file_exists($path)) {
                $this->assert(true, "$file existe ($desc)");
            } else {
                $this->assert(false, "$file NO existe ($desc)");
            }
        }
    }

    private function cleanup(): void {
        $this->info("Limpiando datos de prueba...");

        if ($this->test_user) {
            $file = PROJECT_ROOT . '/data/users/' . $this->test_user['id'] . '.json';
            if (file_exists($file)) {
                @unlink($file);
                $this->assert(true, "Usuario de prueba eliminado");
            }
        }
    }
}
?>
