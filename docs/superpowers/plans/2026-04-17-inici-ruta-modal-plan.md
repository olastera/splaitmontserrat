# Modal Inici Ruta - Pla d'Implementació

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Afegir modal obligatori per introduir codi secret abans d'activar la ruta dels participants.

**Architecture:** Un modal blocking (no es pot tancar sense codi) que valida contra `settings['checkin']['codi_inici']`. En èxit, es registra un check-in especial i es mostra la cartilla.

**Tech Stack:** PHP 8+, Bootstrap 5, Vanilla JS

---

## Estructura de Fitxers

| Acció | Fitxer | Responsabilitat |
|-------|--------|-----------------|
| Crear | `iniciar_ruta.php` | Endpoint API per validar codi i registrar inici |
| Modificar | `cartilla.php` | Afegir modal HTML, lògica PHP, JS |
| Modificar | `admin/configuracio.php` | Afegir camp per codi d'inici |

---

## Task 1: Crear endpoint API iniciar_ruta.php

**Files:**
- Create: `iniciar_ruta.php`

- [ ] **Step 1: Crear arxiu amb estructura bàsica**

```php
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticat']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$codi = strtoupper(trim($data['codi'] ?? ''));

$settings = get_settings();
$codi_correcte = strtoupper(trim($settings['checkin']['codi_inici'] ?? ''));

// Si no hi ha codi configurat, permetre pas
if (empty($codi_correcte)) {
    echo json_encode(['ok' => true, 'skip' => true]);
    exit;
}

// Validar codi
if ($codi !== $codi_correcte) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Codi incorrecte. Torna-ho a provar!']);
    exit;
}

// Registrar check-in d'inici
require_once __DIR__ . '/includes/user.php';
$user = get_user($_SESSION['user_id']);

$user['checkins'][] = [
    'parada_id' => -1,
    'timestamp' => date('c'),
    'inici' => true,
];

save_user($user);

echo json_encode(['ok' => true]);
```

- [ ] **Step 2: Verificar que l'arxiu es crea correctament**

Run: `ls -la iniciar_ruta.php`

---

## Task 2: Afegir camp a l'admin (configuracio.php)

**Files:**
- Modify: `admin/configuracio.php` (secció checkin)

- [ ] **Step 1: Identificar la secció checkin a configuracio.php**

Llegir el fitxer i trobar on està la secció checkin.

- [ ] **Step 2: Afegir camp per codi d'inici**

Després del camp `codi_mestre`, afegir:

```php
<div class="mb-3">
    <label for="codi_inici" class="form-label">
        <i class="bi bi-flag-fill me-1"></i>Codi d'inici de ruta
    </label>
    <input type="text" class="form-control" id="codi_inici" name="checkin[codi_inici]" 
           value="<?= htmlspecialchars($settings['checkin']['codi_inici'] ?? '') ?>"
           placeholder="Deixa buit per desactivar">
    <div class="form-text">Paraula clau que els participants han d'introduir per activar la seva ruta.</div>
</div>
```

- [ ] **Step 3: Verificar que el camp es guarda correctament**

El camp `checkin[codi_inici]` ja es processarà automàticament si l'admin utilitza `save_settings()`.

---

## Task 3: Afegir modal i lògica a cartilla.php

**Files:**
- Modify: `cartilla.php`

- [ ] **Step 1: Afegir funció helper ha_iniciat_ruta()**

A prop de les altres funcions helpers (abans de `require_login`):

```php
function ha_iniciat_ruta(array $user): bool {
    if (empty($user['checkins'])) return false;
    foreach ($user['checkins'] as $ci) {
        if (!empty($ci['inici'])) return true;
    }
    return false;
}
```

- [ ] **Step 2: Afegir variable PHP per al modal**

Després de `$gps_override`:

```php
$ha_iniciat = ha_iniciat_ruta($user);
$cal_modal_inici = !empty($settings['checkin']['codi_inici']) && !$ha_iniciat;
```

- [ ] **Step 3: Afegir variable JavaScript**

A la secció de dades JS:

```javascript
const CAL_MODAL_INICI = <?= $cal_modal_inici ? 'true' : 'false' ?>;
```

- [ ] **Step 4: Afegir modal HTML**

Abans del `<!-- MODAL CHECK-IN -->`:

```html
<!-- MODAL INICI RUTA -->
<div class="modal fade" id="modalInici" data-bs-backdrop="static" 
     data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header modal-header-spait">
        <h5 class="modal-title">
          <i class="bi bi-flag-fill me-2"></i>Activa la teva ruta!
        </h5>
      </div>
      <div class="modal-body text-center">
        <p class="text-muted">Introdueix el codi secret per començar la caminada.</p>
        <div class="mb-3">
          <input type="text" class="form-control form-control-lg text-center text-uppercase"
                 id="codi-inici" placeholder="CODI SECRET" 
                 style="letter-spacing:4px; font-size:1.3rem;" autocomplete="off">
        </div>
        <div id="inici-error" class="alert alert-danger d-none"></div>
        <button class="btn btn-spait btn-lg w-100" id="btn-iniciar-ruta">
          <i class="bi bi-play-fill me-2"></i>Comença la caminada!
        </button>
      </div>
    </div>
  </div>
</div>
```

- [ ] **Step 5: Afegir JavaScript per al modal**

Abans del `</script>` final:

```javascript
// ============= MODAL INICI RUTA =============
if (CAL_MODAL_INICI) {
    const modalInici = new bootstrap.Modal(document.getElementById('modalInici'));
    modalInici.show();
}

document.getElementById('btn-iniciar-ruta')?.addEventListener('click', function() {
    const codi = document.getElementById('codi-inici').value.trim().toUpperCase();
    const errorDiv = document.getElementById('inici-error');
    
    if (!codi) {
        errorDiv.textContent = 'Introdueix el codi secret.';
        errorDiv.classList.remove('d-none');
        return;
    }
    
    fetch('iniciar_ruta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ codi: codi })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) {
            location.reload();
        } else {
            errorDiv.textContent = data.error || 'Codi incorrecte. Torna-ho a provar!';
            errorDiv.classList.remove('d-none');
            document.getElementById('codi-inici').value = '';
            document.getElementById('codi-inici').focus();
        }
    })
    .catch(function() {
        errorDiv.textContent = 'Error de connexió. Torna-ho a provar.';
        errorDiv.classList.remove('d-none');
    });
});

// Enter key also submits
document.getElementById('codi-inici')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('btn-iniciar-ruta').click();
    }
});
```

- [ ] **Step 6: Verificar sintaxi PHP**

Run: `php -l cartilla.php`

---

## Task 4: Verificació manual

- [ ] **Test 1**: Accedir a cartilla.php amb usuari nou → veure modal obligatori
- [ ] **Test 2**: Introduir codi incorrecte → veure error
- [ ] **Test 3**: Introduir codi correcte → modal tanca, cartilla visible
- [ ] **Test 4**: Tornar a accedir (després d'haver iniciat) → NO veure modal
- [ ] **Test 5**: Sense codi configurat → comportament normal

---

## Resum Tasques

| Task | Acció | Fitxer |
|------|-------|--------|
| 1 | Crear endpoint API | `iniciar_ruta.php` |
| 2 | Afegir camp admin | `admin/configuracio.php` |
| 3 | Afegir modal | `cartilla.php` |
| 4 | Verificació | Manual |

---

**Pla complet.** Quan estiguis llest, puc executar-lo amb subagent-driven-development o execució inline.

