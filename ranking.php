<?php
require_once __DIR__ . '/includes/config.php';

$tab = $_GET['tab'] ?? 'general';
$parada_id = intval($_GET['parada'] ?? -1);

// Obtener configuración
$settings = get_settings();
$parades = $settings['parades'] ?? [];
$event = $settings['event'] ?? [];
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking — Cartilla Virtual Spai-T</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/spait.css">
    <style>
        .ranking-header {
            background: linear-gradient(135deg, #C0392B 0%, #27AE60 100%);
            color: white;
            padding: 2rem 1rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .medal-position {
            font-size: 2rem;
            font-weight: bold;
            min-width: 4rem;
        }

        .ranking-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }

        .ranking-item:hover {
            background-color: #f9f9f9;
        }

        .ranking-item:nth-child(1) { background: #fff9e6; }
        .ranking-item:nth-child(2) { background: #f5f5f5; }
        .ranking-item:nth-child(3) { background: #fff8f0; }

        .ranking-item.top-3 {
            border-left: 4px solid #F1C40F;
            padding-left: calc(1rem - 4px);
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: bold;
            font-size: 1.1rem;
            color: #2C3E50;
        }

        .user-detail {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-top: 0.25rem;
        }

        .parada-selector {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .parada-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .parada-btn:hover {
            border-color: #C0392B;
            color: #C0392B;
        }

        .parada-btn.active {
            background: #C0392B;
            color: white;
            border-color: #C0392B;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #95a5a6;
        }

        .nav-pills-spait {
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 1rem;
        }

        .nav-link {
            color: #7f8c8d;
            border: none;
            border-radius: 0;
            border-bottom: 3px solid transparent;
            padding: 0.5rem 1rem;
            font-weight: 600;
        }

        .nav-link.active {
            background: none;
            color: #C0392B;
            border-bottom-color: #C0392B;
        }

        .nav-link:hover {
            background: none;
            color: #C0392B;
        }
    </style>
</head>
<body>
    <div class="ranking-header">
        <h1><i class="bi bi-trophy-fill"></i> Ranking</h1>
        <p class="mb-0"><?= htmlspecialchars($event['nom'] ?? 'Caminada a Montserrat 2026') ?></p>
    </div>

    <div class="container" style="max-width: 800px; margin-bottom: 3rem;">
        <!-- PESTAÑAS -->
        <ul class="nav nav-pills-spait justify-content-center mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $tab === 'general' ? 'active' : '' ?>"
                        id="tab-general" data-bs-toggle="pill" data-bs-target="#pane-general"
                        type="button" role="tab">
                    <i class="bi bi-people-fill me-1"></i>Ranking General
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $tab === 'parades' ? 'active' : '' ?>"
                        id="tab-parades" data-bs-toggle="pill" data-bs-target="#pane-parades"
                        type="button" role="tab">
                    <i class="bi bi-geo-alt-fill me-1"></i>Por Parada
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- RANKING GENERAL -->
            <div class="tab-pane fade <?= $tab === 'general' ? 'show active' : '' ?>" id="pane-general" role="tabpanel">
                <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <?php
                    $ranking = get_overall_ranking();

                    if (empty($ranking)):
                    ?>
                        <div class="empty-state">
                            <i class="bi bi-hourglass-split" style="font-size: 3rem;"></i>
                            <p style="margin-top: 1rem;">Cap check-in registrat encara</p>
                            <small>Els usuaris que facin check-in apareixeran aquí!</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($ranking as $index => $user):
                            $posicion = $index + 1;
                            $medal = get_medal($posicion);
                            $is_top3 = $posicion <= 3;
                        ?>
                            <div class="ranking-item <?= $is_top3 ? 'top-3' : '' ?>">
                                <div class="medal-position">
                                    <?= $medal !== '  ' ? $medal : '<small>#' . $posicion . '</small>' ?>
                                </div>
                                <div class="user-info">
                                    <div class="user-name"><?= htmlspecialchars($user['nom']) ?></div>
                                    <div class="user-detail">
                                        <i class="bi bi-pin-map-fill"></i> <?= $user['parades'] ?> parada<?= $user['parades'] !== 1 ? 's' : '' ?>
                                        completada<?= $user['parades'] !== 1 ? 's' : '' ?>
                                    </div>
                                </div>
                                <div style="text-align: right; font-size: 0.85rem; color: #95a5a6;">
                                    <?php
                                    if ($user['ruta'] === 'llarga') {
                                        echo '🟢 Ruta Llarga';
                                    } else {
                                        echo '🔵 Ruta Curta';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RANKING PER PARADA -->
            <div class="tab-pane fade <?= $tab === 'parades' ? 'show active' : '' ?>" id="pane-parades" role="tabpanel">
                <!-- Selector de paradas -->
                <div class="parada-selector">
                    <?php foreach ($parades as $p):
                        $is_active = ($parada_id === $p['id']) ? 'active' : '';
                    ?>
                        <a href="?tab=parades&parada=<?= $p['id'] ?>"
                           class="parada-btn <?= $is_active ?>"
                           title="<?= htmlspecialchars($p['nom']) ?>">
                            <?= htmlspecialchars(substr($p['nom'], 0, 20)) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Ranking de parada seleccionada -->
                <?php if ($parada_id >= 0): ?>
                    <?php
                    $parada_name = get_parada_name($parada_id);
                    $ranking_parada = get_ranking_by_stop($parada_id);
                    ?>
                    <h4 class="mb-3">🏁 <?= htmlspecialchars($parada_name) ?></h4>
                    <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <?php if (empty($ranking_parada)): ?>
                            <div class="empty-state">
                                <i class="bi bi-exclamation-circle" style="font-size: 3rem;"></i>
                                <p style="margin-top: 1rem;">Cap check-in en aquesta parada</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($ranking_parada as $index => $user):
                                $posicion = $index + 1;
                                $medal = get_medal($posicion);
                                $is_top3 = $posicion <= 3;
                            ?>
                                <div class="ranking-item <?= $is_top3 ? 'top-3' : '' ?>">
                                    <div class="medal-position">
                                        <?= $medal !== '  ' ? $medal : '<small>#' . $posicion . '</small>' ?>
                                    </div>
                                    <div class="user-info">
                                        <div class="user-name"><?= htmlspecialchars($user['nom']) ?></div>
                                        <div class="user-detail">
                                            <i class="bi bi-clock-fill"></i> <?= $user['hora'] ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-hand-index-fill" style="font-size: 3rem;"></i>
                        <p style="margin-top: 1rem;">Selecciona una parada!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center py-4" style="border-top: 1px solid #e0e0e0; color: #95a5a6; font-size: 0.9rem;">
        <p class="mb-0">
            <a href="index.php" style="color: #7f8c8d; text-decoration: none;">← Tornar al login</a>
        </p>
        <p style="margin-top: 1rem; font-size: 0.8rem;">
            <i class="bi bi-info-circle"></i> Actualitzat en temps real
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
