# Prompt Claude Code — Model de Privacitat Revisat (Seguretat + Menors)

## Problema a corregir

Els prompts anteriors de privacitat feien `unset($user['last_position'])` en
desactivar el tracking. Això és incorrecte perquè la caminada és MIXTA
(menors i adults) i la seguretat és prioritària.

**Aquest prompt substitueix i corregeix** els prompts PRIVACY i PRIVACY_FIX anteriors.

---

## Nou model mental

> L'última posició coneguda MAI s'esborra.
> El toggle de privacitat només controla si la posició s'actualitza en temps real.

```
Tracking ON  → posició s'actualitza cada 30s  → admin veu temps real
Tracking OFF → posició NO s'actualitza         → admin veu última coneguda (congelada)
Sense bateria/GPS → posició deixa d'arribar   → admin veu última coneguda + alerta
```

---

## Fitxers a modificar

### 1. `includes/user.php`

**Corregir `set_share_location()`** — eliminar l'unset de last_position:

```php
function set_share_location(string $id, bool $share): bool {
    $user = get_user($id);
    if (!$user) return false;

    $user['share_location'] = $share;

    // ⚠️ NO esborrar last_position mai — seguretat per menors
    // unset($user['last_position']); ← ELIMINAR aquesta línia si existeix

    return save_user($id, $user);
}
```

**Corregir `update_user_position()`** — guardar sempre, però respectar tracking:

```php
function update_user_position(string $id, float $lat, float $lng, float $accuracy): bool {
    $user = get_user($id);
    if (!$user) return false;

    // Guardar SEMPRE l'última posició (seguretat)
    $user['last_position'] = [
        'lat'           => $lat,
        'lng'           => $lng,
        'accuracy'      => $accuracy,
        'timestamp'     => date('c'),
        'tracking_on'   => user_shares_location($user), // indica si era tracking actiu o no
    ];

    return save_user($id, $user);
}
```

**Corregir `get_active_positions()`** — afegir estat segons tracking i temps:

```php
function get_active_positions(): array {
    $users = get_all_users();
    $result = [];

    foreach ($users as $user) {
        // Participants sense cap posició mai registrada → no mostrar al mapa
        if (empty($user['last_position'])) continue;

        $ts          = strtotime($user['last_position']['timestamp']);
        $minutes_ago = (time() - $ts) / 60;
        $tracking_on = $user['share_location'] ?? false;
        $finished    = has_checkin($user['id'], 10);

        // Determinar estat visual del marcador
        if ($finished) {
            $status = 'finished';       // daurat 🏆
        } elseif ($tracking_on && $minutes_ago <= 10) {
            $status = 'actiu';          // verd 🟢 temps real
        } elseif (!$tracking_on) {
            $status = 'tracking_off';   // blau 🔵 congelat voluntàriament
        } elseif ($minutes_ago <= 10) {
            $status = 'actiu';          // verd (tracking on, recent)
        } elseif ($minutes_ago <= 30) {
            $status = 'desconnectat';   // groc 🟡 possible bateria/cobertura
        } else {
            $status = 'perdut';         // vermell 🔴 alerta
        }

        $result[] = [
            'id'            => $user['id'],
            'nom'           => $user['nom'],
            'ruta'          => $user['ruta'],
            'lat'           => $user['last_position']['lat'],
            'lng'           => $user['last_position']['lng'],
            'accuracy'      => $user['last_position']['accuracy'],
            'timestamp'     => $user['last_position']['timestamp'],
            'minutes_ago'   => round($minutes_ago),
            'tracking_on'   => $tracking_on,
            'status'        => $status,
            'parades_fetes' => count($user['checkins'] ?? []),
            'ultima_parada' => get_last_checkin_name($user),
        ];
    }

    return $result;
}
```

---

### 2. `update_position.php`

**Corregir** — eliminar la guarda que bloquejava l'enviament si tracking era OFF.
Ara s'ha d'acceptar sempre (és el servidor qui decideix gravar, no el client):

```php
// ELIMINAR o comentar aquest bloc si existeix:
// if (!$fresh_user || !user_shares_location($fresh_user)) {
//     echo json_encode(['ok' => true, 'skipped' => true]);
//     exit;
// }

// SUBSTITUIR per:
// Gravar sempre (seguretat), independentment del toggle de l'usuari
$ok = update_user_position($current_user['id'], $lat, $lng, $accuracy);
echo json_encode(['ok' => $ok]);
```

---

### 3. `toggle_location.php`

**Corregir** la resposta — ja no cal `position_cleared`:

```php
$ok = set_share_location($current_user['id'], $share);

echo json_encode([
    'ok'    => $ok,
    'share' => $share,
    // Missatge honest per si el client vol mostrar-lo
    'note'  => $share
        ? 'Posició en temps real activada'
        : 'Actualitzacions pausades. L\'última posició és visible pels organitzadors per seguretat.',
]);
```

---

### 4. `cartilla.php` — JS i missatge honest a l'usuari

**Corregir el missatge del toggle** per ser transparent:

```html
<!-- Switch compartir ubicació — missatge honest -->
<div class="d-flex align-items-center gap-2 my-2" id="location-sharing-control">
  <span class="text-muted small">📍 Ubicació:</span>
  <div class="form-check form-switch mb-0">
    <input class="form-check-input" type="checkbox"
           id="toggle-share-location"
           <?= user_shares_location($current_user) ? 'checked' : '' ?>>
    <label class="form-check-label small" for="toggle-share-location"
           id="share-location-label">
      <?= user_shares_location($current_user)
          ? 'Temps real activat'
          : 'Actualitzacions pausades' ?>
    </label>
  </div>
</div>

<!-- Tooltip informatiu (Bootstrap tooltip o popover) -->
<button type="button"
        class="btn btn-link btn-sm p-0 text-muted"
        data-bs-toggle="popover"
        data-bs-placement="bottom"
        data-bs-content="Per seguretat, l'última posició coneguda sempre és visible pels organitzadors. Pots pausar les actualitzacions en temps real.">
  <i class="bi bi-info-circle"></i>
</button>
```

**Corregir el JS** — eliminar el `pendingPosition = null`:

```javascript
document.getElementById('toggle-share-location')
  .addEventListener('change', function() {
    sharingLocation = this.checked;

    // Actualitzar etiqueta
    const label = document.getElementById('share-location-label');
    label.textContent = sharingLocation
        ? 'Temps real activat'
        : 'Actualitzacions pausades';

    // ⚠️ NO esborrar pendingPosition — deixar que s'enviï
    // pendingPosition = null; ← ELIMINAR si existeix

    // Desar preferència al servidor
    fetch('toggle_location.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ share: sharingLocation })
    }).then(r => r.json()).then(data => {
        if (data.note) console.info(data.note);
    });
});

// La funció sendPosition SÍ respecta el toggle — no enviar si OFF
// (estalviar bateria i dades, però l'última posició ja queda guardada)
function sendPosition(lat, lng, accuracy) {
    if (!sharingLocation) return; // no enviar actualitzacions si tracking OFF

    fetch('update_position.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ lat, lng, accuracy })
    }).catch(function() {
        pendingPosition = { lat, lng, accuracy };
    });
}
```

---

### 5. `admin/mapa.php` — Colors i llegenda actualitzats

**Llegenda del mapa** (afegir o substituir l'existent):

```html
<div class="map-legend card p-2 small">
  <strong>Llegenda</strong>
  <div><span class="dot bg-success"></span> Temps real (actiu &lt;10min)</div>
  <div><span class="dot bg-primary"></span> Pausat voluntàriament (última pos. coneguda)</div>
  <div><span class="dot bg-warning"></span> Sense connexió 10-30min ⚠️</div>
  <div><span class="dot bg-danger"></span> Sense connexió +30min 🚨</div>
  <div><span class="dot bg-gold"></span> Ha arribat a Montserrat 🏆</div>
</div>
```

**Popup del marcador** — afegir context segons estat:

```javascript
function buildPopupContent(p) {
    let statusText = '';
    let statusClass = '';

    switch(p.status) {
        case 'actiu':
            statusText = '🟢 Temps real';
            statusClass = 'text-success';
            break;
        case 'tracking_off':
            statusText = '🔵 Ha pausat les actualitzacions';
            statusClass = 'text-primary';
            break;
        case 'desconnectat':
            statusText = `🟡 Sense connexió fa ${p.minutes_ago} min`;
            statusClass = 'text-warning';
            break;
        case 'perdut':
            statusText = `🔴 Sense connexió fa ${p.minutes_ago} min — Verificar!`;
            statusClass = 'text-danger fw-bold';
            break;
        case 'finished':
            statusText = '🏆 Ha arribat a Montserrat!';
            statusClass = 'text-warning fw-bold';
            break;
    }

    return `
        <div class="popup-content">
          <strong>${p.nom}</strong><br>
          <span class="${statusClass} small">${statusText}</span><br>
          <small class="text-muted">📍 ${p.ultima_parada}</small><br>
          <small class="text-muted">✅ ${p.parades_fetes}/10 parades</small><br>
          <small class="text-muted">🕐 Última pos: fa ${p.minutes_ago} min</small><br>
          <a href="participant_detail.php?id=${p.id}" class="btn btn-xs btn-outline-primary mt-1">
            Veure fitxa →
          </a>
        </div>`;
}
```

---

## Taula de comportament final

| Situació | Posició guardada | Admin la veu | Estat al mapa |
|---|---|---|---|
| Tracking ON, connectat | ✅ Actualitzada | ✅ Temps real | 🟢 Verd |
| Tracking OFF (voluntari) | ✅ Congelada | ✅ Última coneguda | 🔵 Blau |
| Sense bateria / GPS | ✅ Congelada | ✅ Última coneguda | 🟡→🔴 segons temps |
| Mai ha obert l'app | ❌ No en té | ❌ No apareix | — |
| Ha arribat a Montserrat | ✅ Montserrat | ✅ Montserrat | 🏆 Daurat |

---

## Missatge a l'usuari al registre (afegir al formulari index.php)

```html
<div class="alert alert-info small mt-2">
  <i class="bi bi-shield-check"></i>
  <strong>Sobre la teva ubicació:</strong>
  Durant la caminada, l'última posició coneguda del teu dispositiu és
  visible pels organitzadors per motius de seguretat, especialment
  si hi ha participants menors d'edat. Pots pausar les actualitzacions
  en temps real des de la cartilla, però l'última posició sempre
  quedarà guardada.
</div>
```

---

## Resum de canvis respecte prompts anteriors

| Fitxer | Canvi |
|--------|-------|
| `user.php` → `set_share_location()` | ~~unset last_position~~ → **NO esborrar mai** |
| `user.php` → `update_user_position()` | Guardar sempre, afegir camp `tracking_on` |
| `user.php` → `get_active_positions()` | Nou estat `tracking_off` (blau) |
| `update_position.php` | ~~Bloquejar si tracking OFF~~ → **Acceptar sempre** |
| `toggle_location.php` | Eliminar `position_cleared` de la resposta |
| `cartilla.php` JS | ~~pendingPosition = null~~ + missatge honest |
| `cartilla.php` HTML | Canviar text toggle per ser transparent |
| `admin/mapa.php` | Nou color blau + llegenda actualitzada |
| `index.php` | Afegir avís de privacitat honest al registre |
