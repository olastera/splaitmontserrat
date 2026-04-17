# Spec: Modal d'Inici Obligatori per Activar la Ruta

**Data**: 2026-04-17
**Estat**: Aprovat
**Branca**: admin-universal

---

## Objectiu

Afegir un modal obligatori que requereixi introduir un codi secret per activar la ruta dels participants. Tots els usuaris (ruta llarga i curta) han de validar-se abans de poder fer check-ins.

---

## Funcionalitat

### 1. Configuració (Admin)

**Ubicació**: `admin/configuracio.php` → secció "Check-in"

**Nou camp a `settings.json`**:
```json
{
  "checkin": {
    "require_gps": false,
    "radi_metres": 200,
    "codi_mestre": "",
    "codi_inici": "MUNTANYA2026"
  }
}
```

**Lògica**:
- Si `codi_inici` està buit → no cal validar, comportament actual
- Si `codi_inici` té valor → modal obligatori per usuaris que no han iniciat

---

### 2. Detecció PHP (cartilla.php)

```php
// Verificar si l'usuari ja ha iniciat la ruta
function ha_iniciat_ruta(array $user): bool {
    if (empty($user['checkins'])) return false;
    foreach ($user['checkins'] as $ci) {
        if (!empty($ci['inici'])) return true;
    }
    return false;
}

$settings = get_settings();
$ha_iniciat = ha_iniciat_ruta($user);
$cal_modal_inici = !empty($settings['checkin']['codi_inici']) && !$ha_iniciat;
```

---

### 3. Modal HTML (cartilla.php)

**Atributs clau**:
- `data-bs-backdrop="static"` — no es pot tancar clicant fora
- `data-bs-keyboard="false"` — no es tanca amb Escape
- No hi ha botó de tancar (X)

**Contingut**:
- Títol: "Activa la teva ruta!"
- Icona: Bandera (bi-flag-fill)
- Text: "Introdueix el codi secret per començar la caminada."
- Input: text majúscules, lletres espaiades (estil codi)
- Botó: "Comença la caminada!"
- Div per errors

**Disseny**: Mateix estil que modal check-in (modal-header-spait, btn-spait)

---

### 4. Endpoint API

**Arxiu**: `iniciar_ruta.php`

**Request**: POST amb JSON body `{ "codi": "MUNTANYA2026" }`

**Response èxit**:
```json
{ "ok": true }
```

**Response error** (codi incorrecte):
```json
{ "ok": false, "error": "Codi incorrecte. Torna-ho a provar!" }
```

**Response skip** (codi no configurat):
```json
{ "ok": true, "skip": true }
```

**Lògica**:
1. Validar sessió usuari
2. Comparar codi amb `settings['checkin']['codi_inici']`
3. Si codi buit → skip (permitir pas)
4. Si codi coincideix → registrar check-in d'inici
5. Si codi no coincideix → error 401

---

### 5. Registre de Check-in d'Inici

**Quan l'usuari valida correctament**:
```json
{
  "parada_id": -1,
  "timestamp": "2026-04-19T06:00:00+02:00",
  "inici": true
}
```

**Nota**: `parada_id: -1` és un identificador especial. A la cartilla NO es mostra com a parada, només activa l'estat.

---

### 6. JavaScript (cartilla.php)

**Inicialització**:
```javascript
// Si cal modal, mostrar-lo automàticament
if (CAL_MODAL_INICI) {
    const modal = new bootstrap.Modal(document.getElementById('modalInici'));
    modal.show();
}
```

**Validació**:
```javascript
document.getElementById('btn-iniciar-ruta').addEventListener('click', () => {
    const codi = document.getElementById('codi-inici').value.trim().toUpperCase();
    fetch('iniciar_ruta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ codi })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) location.reload();
        else mostrarError(data.error);
    });
});
```

---

## Components afectats

| Arxiu | Canvi |
|--------|-------|
| `admin/configuracio.php` | Afegir camp per codi d'inici |
| `cartilla.php` | Afegir lògica PHP, modal HTML, JS |
| `iniciar_ruta.php` | Nou arxiu (endpoint API) |
| `includes/config.php` | No canvia (llegir settings existent) |
| `data/settings.json` | Nou camp `codi_inici` |

---

## Consideracions

### Seguretat
- Codi no emmagatzemat en codi font (configs a JSON)
- No limitació d'intents (caminada, no atac)
- Sessió PHP validada abans de processar

### UX
- Modal blocking (no es pot tancar sense codi)
- Error visible en vermell sota l'input
- Missatge d'ànim: "Endavant, pelegrí!" en èxit

### Compatibilitat enrere
- Si no hi ha codi configurat → comportament actual (sense modal)

---

## Out of Scope

- Codi diferent per ruta (decidit: un sol codi per tothom)
- Limitació d'intents
- Notificacions recordatori

---

## Testos

1. **Amb codi configurat**: usuari nou → veu modal obligatori
2. **Codi incorrecte**: mostra error, modal segueix obert
3. **Codi correcte**: tanca modal, usuari veu cartilla
4. **Amb codi buit**: comportament actual (cap canvi)
5. **Usuari que ja ha iniciat**: no veu modal

