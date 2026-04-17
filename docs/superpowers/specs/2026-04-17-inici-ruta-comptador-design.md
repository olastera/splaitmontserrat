# Spec: Comptador d'Inici + Logout al Modal

**Data**: 2026-04-17
**Estat**: Aprovat
**Branca**: admin-universal

---

## Objectiu

Millorar el modal d'inici de ruta amb:
1. **Comptador regressiu** que mostra el temps restant fins l'inici de la caminada
2. **Botó de logout** integrat dins del modal

---

## Funcionalitat

### 1. Configuració (Admin)

**Ubicació**: `admin/configuracio.php` → secció "Event" (ja existeix `dates_actives`)

**Nou camp a `settings.json`**:
```json
{
  "event": {
    "nom": "Caminada a Montserrat 2026",
    "data_esdeveniment": "2026-04-19",
    "inici_ruta_hora": "07:00",
    ...
  }
}
```

**Camps existents a utilitzar**:
- `data_esdeveniment` — dia de la caminada
- `inici_ruta_hora` — hora d'inici de la ruta (nou)

---

### 2. Lògica PHP (cartilla.php)

```php
// Hora d'inici de la ruta
$inici_ruta = null;
if (!empty($settings['event']['data_esdeveniment']) && !empty($settings['event']['inici_ruta_hora'])) {
    $inici_ruta = $settings['event']['data_esdeveniment'] . 'T' . $settings['event']['inici_ruta_hora'] . ':00';
}

$inici_ruta_iso = $inici_ruta; // per JS
$mostrar_comptador = false;

if ($cal_modal_inici && $inici_ruta) {
    $ara = new DateTime();
    $inici = new DateTime($inici_ruta);
    $mostrar_comptador = $ara < $inici;
}
```

---

### 3. Variables JavaScript

```javascript
const INICI_RUTA = <?= json_encode($inici_ruta_iso) ?>;  // ISO string o null
const CAL_MODAL_INICI = <?= $cal_modal_inici ? 'true' : 'false' ?>;
```

---

### 4. Comptador Regressiu (JS)

```javascript
function actualitzarComptador() {
    if (!INICI_RUTA) return;
    
    const ara = new Date();
    const inici = new Date(INICI_RUTA);
    const diff = inici - ara;
    
    if (diff <= 0) {
        document.getElementById('comptador-container').style.display = 'none';
        document.getElementById('codi-inici').disabled = false;
        document.getElementById('btn-iniciar-ruta').disabled = false;
        return;
    }
    
    const hores = Math.floor(diff / (1000 * 60 * 60));
    const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const segs = Math.floor((diff % (1000 * 60)) / 1000);
    
    document.getElementById('comptador-val').textContent =
        `${hores.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
}
```

---

### 5. Modal HTML Actualitzat

```html
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
        <div id="comptador-container" class="mb-3">
          <p class="text-muted mb-1">La caminada comença en:</p>
          <div class="comptador-display">
            <span id="comptador-val" class="fs-2 fw-bold text-spait">--:--:--</span>
          </div>
        </div>
        
        <!-- Formulari codi (deshabilitat si comptador actiu) -->
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
```

---

### 6. Estils CSS

```css
.comptador-display {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid var(--spait-vermell);
    border-radius: 12px;
    padding: 1rem 2rem;
    display: inline-block;
}

.text-spait {
    color: var(--spait-vermell);
}
```

---

### 7. Inicialització JS

```javascript
if (CAL_MODAL_INICI) {
    const modalInici = new bootstrap.Modal(document.getElementById('modalInici'));
    modalInici.show();
    
    if (INICI_RUTA) {
        actualitzarComptador();
        setInterval(actualitzarComptador, 1000);
        
        // Deshabilitar input mentre no sigui l'hora
        const codiInput = document.getElementById('codi-inici');
        const btnIniciar = document.getElementById('btn-iniciar-ruta');
        codiInput.disabled = true;
        btnIniciar.disabled = true;
    }
}
```

---

## Components afectats

| Arxiu | Canvi |
|--------|-------|
| `cartilla.php` | Lògica PHP, modal HTML, JS comptador |
| `admin/configuracio.php` | Camp per hora d'inici ruta |
| `assets/css/spait.css` | Estils comptador |

---

## Flux complet

```
1. usuari entra a cartilla.php
2. PHP detecta: !$ha_iniciat && codi_inici configurat
3. Si hi ha hora d'inici i encara no és:
   → Modal amb comptador actiu
   → Input deshabilitat
   → Cada segon s'actualitza el comptador
4. Quan arriba l'hora:
   → Comptador desapareix
   → Input s'habilita
5. usuari introdueix codi → AJAX → èxit → cartilla
   → o bé clica "Sortir" → logout
```

---

## Consideracions

### Comptador
- S'actualitza cada segon
- Quan arriba a 0, desapareix i s'habilita el formulari
- Si no hi ha hora configurada, comportament actual (només codi)

### Logout
- Enllaç directe a `logout.php`
- No cal AJAX ni confirmació
- Simplement tanca la sessió PHP

### Cas: sense hora configurada
- Si `inici_ruta_hora` està buit, el modal funciona igual que ara (només codi)

---

## Testos

1. **Amb hora passada**: comptador no visible, input habilitat
2. **Amb hora futura**: comptador visible, input deshabilitat
3. **Quan arriba l'hora**: comptador desapareix, input s'habilita
4. **Botó logout**: clica i redirigeix a login
5. **Sense hora configurada**: comportament actual

