# Comptador + Logout al Modal - Pla d'Implementació

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Afegir comptador regressiu d'inici de ruta i botó de logout dins del modal.

**Architecture:** Modal existent a cartilla.php amb comptador JS que s'actualitza cada segon. Admin afegeix camp hora d'inici a settings.json.

**Tech Stack:** PHP 8+, Bootstrap 5, Vanilla JS

---

## Estructura de Fitxers

| Acció | Fitxer | Responsabilitat |
|-------|--------|-----------------|
| Modificar | `cartilla.php` | Comptador HTML/JS, lògica PHP |
| Modificar | `admin/configuracio.php` | Camp hora inici ruta |
| Modificar | `assets/css/spait.css` | Estils comptador |

---

## Task 1: Afegir camp hora d'inici a l'admin

**Files:**
- Modify: `admin/configuracio.php` (secció event)

- [ ] **Step 1: Identificar la secció event a configuracio.php**

Llegir el fitxer i trobar on està el camp `data_esdeveniment`.

- [ ] **Step 2: Afegir camp hora d'inici ruta**

Després del camp `data_esdeveniment`, afegir:

```html
<div class="mb-3">
    <label for="ev_inici_ruta_hora" class="form-label">
        <i class="bi bi-clock me-1"></i>Hora d'inici de la ruta
    </label>
    <input type="time" class="form-control" id="ev_inici_ruta_hora" 
           value="<?= htmlspecialchars($ev['inici_ruta_hora'] ?? '') ?>">
    <div class="form-text">Hora en què els participants poden iniciar la caminada.</div>
</div>
```

- [ ] **Step 3: Actualitzar la funció desarEvent()**

Afegir el camp al objecte que es guarda:

```javascript
function desarEvent() {
  saveSection('event', {
    // ... camps existents ...
    inici_ruta_hora: document.getElementById('ev_inici_ruta_hora').value,
  });
}
```

---

## Task 2: Afegir CSS del comptador

**Files:**
- Modify: `assets/css/spait.css`

- [ ] **Step 1: Afegir estils del comptador**

Al final del fitxer CSS:

```css
/* Comptador d'inici ruta */
.comptador-container {
    margin-bottom: 1.5rem;
}

.comptador-display {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid var(--spait-vermell, #C0392B);
    border-radius: 12px;
    padding: 1rem 2rem;
    display: inline-block;
}

.text-spait {
    color: var(--spait-vermell, #C0392B);
}
```

---

## Task 3: Actualitzar modal HTML a cartilla.php

**Files:**
- Modify: `cartilla.php` (modal modalInici)

- [ ] **Step 1: Substituir el modal Integro**

Substituir tot el modal existent (de `<!-- MODAL INICI RUTA -->` a `<!-- MODAL CHECK-IN -->`) per:

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
        
        <!-- Comptador (visible si encara no és l'hora) -->
        <div id="comptador-container" class="comptador-container" style="display:none;">
          <p class="text-muted mb-1">La caminada comença en:</p>
          <div class="comptador-display">
            <span id="comptador-val" class="fs-2 fw-bold text-spait">--:--:--</span>
          </div>
        </div>
        
        <!-- Formulari codi -->
        <p class="text-muted" id="missatge-codi">Introdueix el codi secret per començar la caminada.</p>
        <div class="mb-3">
          <input type="text" class="form-control form-control-lg text-center text-uppercase"
                 id="codi-inici" placeholder="CODI SECRET" 
                 style="letter-spacing:4px; font-size:1.3rem;" autocomplete="off">
        </div>
        <div id="inici-error" class="alert alert-danger d-none"></div>
        <button class="btn btn-spait btn-lg w-100" id="btn-iniciar-ruta">
          <i class="bi bi-play-fill me-2"></i>Comença la caminada!
        </button>
        
        <!-- Separador -->
        <div class="text-muted my-3">
          <small>o bé</small>
        </div>
        
        <!-- Botó logout -->
        <a href="logout.php" class="btn btn-outline-secondary btn-sm w-100">
          <i class="bi bi-box-arrow-left me-1"></i>Sortir / Tancar sessió
        </a>
        
      </div>
    </div>
  </div>
</div>

<!-- MODAL CHECK-IN -->
```

---

## Task 4: Actualitzar lògica PHP a cartilla.php

**Files:**
- Modify: `cartilla.php` (secció PHP, abans de JavaScript)

- [ ] **Step 1: Actualitzar variable INICI_RUTA**

Canviar la secció on s'afegeix `CAL_MODAL_INICI`:

```php
// Hora d'inici de la ruta
$inici_ruta_iso = null;
if (!empty($settings['event']['data_esdeveniment']) && !empty($settings['event']['inici_ruta_hora'])) {
    $inici_ruta_iso = $settings['event']['data_esdeveniment'] . 'T' . $settings['event']['inici_ruta_hora'] . ':00';
}

$gps_override = is_gps_override();
$ha_iniciat = ha_iniciat_ruta($user);
$cal_modal_inici = !empty($settings['checkin']['codi_inici']) && !$ha_iniciat;

// Calcular progrés
```

- [ ] **Step 2: Actualitzar variable JavaScript INICI_RUTA**

Canviar la línia on s'afegeix `CAL_MODAL_INICI`:

```javascript
const CAL_MODAL_INICI = <?= $cal_modal_inici ? 'true' : 'false' ?>;
const INICI_RUTA = <?= json_encode($inici_ruta_iso) ?>;
```

---

## Task 5: Actualitzar JavaScript del modal

**Files:**
- Modify: `cartilla.php` (secció JavaScript, al final)

- [ ] **Step 1: Actualitzar inicialització del modal**

Substituir el codi existent `if (CAL_MODAL_INICI)`:

```javascript
// ============= MODAL INICI RUTA =============
if (CAL_MODAL_INICI) {
    const modalInici = new bootstrap.Modal(document.getElementById('modalInici'));
    modalInici.show();
    
    if (INICI_RUTA) {
        actualitzarComptador();
        setInterval(actualitzarComptador, 1000);
    }
}

function actualitzarComptador() {
    if (!INICI_RUTA) return;
    
    const ara = new Date();
    const inici = new Date(INICI_RUTA);
    const diff = inici - ara;
    
    const comptadorContainer = document.getElementById('comptador-container');
    const codiInput = document.getElementById('codi-inici');
    const btnIniciar = document.getElementById('btn-iniciar-ruta');
    const missatgeCodi = document.getElementById('missatge-codi');
    
    if (diff <= 0) {
        if (comptadorContainer) comptadorContainer.style.display = 'none';
        if (codiInput) codiInput.disabled = false;
        if (btnIniciar) btnIniciar.disabled = false;
        if (missatgeCodi) missatgeCodi.textContent = 'Introdueix el codi secret per començar la caminada.';
        return;
    }
    
    if (comptadorContainer) comptadorContainer.style.display = 'block';
    if (codiInput) codiInput.disabled = true;
    if (btnIniciar) btnIniciar.disabled = true;
    if (missatgeCodi) missatgeCodi.textContent = 'Encara no és l\'hora! Esperem una mica...';
    
    const hores = Math.floor(diff / (1000 * 60 * 60));
    const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const segs = Math.floor((diff % (1000 * 60)) / 1000);
    
    const comptadorVal = document.getElementById('comptador-val');
    if (comptadorVal) {
        comptadorVal.textContent =
            hores.toString().padStart(2, '0') + ':' +
            mins.toString().padStart(2, '0') + ':' +
            segs.toString().padStart(2, '0');
    }
}
```

- [ ] **Step 2: Actualitzar el event listener del form**

El codi existent del fetch a `iniciar_ruta.php` es manté igual, però cal afegir la lògica de disabled:

```javascript
document.getElementById('btn-iniciar-ruta')?.addEventListener('click', function() {
    const codi = document.getElementById('codi-inici').value.trim().toUpperCase();
    const errorDiv = document.getElementById('inici-error');
    
    if (!codi) {
        errorDiv.textContent = 'Introdueix el codi secret.';
        errorDiv.classList.remove('d-none');
        return;
    }
    
    this.disabled = true;
    
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
            errorDiv.textContent = data.error || 'Codi incorrecte. Torna\'ho a provar!';
            errorDiv.classList.remove('d-none');
            document.getElementById('codi-inici').value = '';
            document.getElementById('codi-inici').focus();
            document.getElementById('btn-iniciar-ruta').disabled = false;
        }
    })
    .catch(function() {
        errorDiv.textContent = 'Error de connexió. Torna\'ho a provar.';
        errorDiv.classList.remove('d-none');
        document.getElementById('btn-iniciar-ruta').disabled = false;
    });
});
```

---

## Resum Tasques

| Task | Acció | Fitxer |
|------|-------|--------|
| 1 | Camp hora admin | `admin/configuracio.php` |
| 2 | CSS comptador | `assets/css/spait.css` |
| 3 | Modal HTML | `cartilla.php` |
| 4 | Lògica PHP | `cartilla.php` |
| 5 | JavaScript | `cartilla.php` |

---

**Pla complet.** Quan estiguis llest, puc executar-lo.

