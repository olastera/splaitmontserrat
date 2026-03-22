# Prompt Claude Code — Compatibilitat amb usuaris existents (migració suau)

## Problema

Els fitxers JSON dels usuaris ja registrats no tenen els camps nous:
- `share_location` (afegit al prompt PRIVACY_V2)
- `last_position` (afegit al prompt TRACKING)

Quan el codi nou intenti llegir aquests camps, pot donar errors o
comportaments inesperats.

---

## Solució: valors per defecte a `get_user()`

No cal cap script de migració. Afegir valors per defecte en el moment
de llegir qualsevol usuari. Així els JSONs vells funcionen sense tocar-los.

### `includes/user.php` — Funció `get_user()`

Localitza la funció `get_user()` i afegeix normalització després de llegir el JSON:

```php
function get_user(string $id): ?array {
    $file = DATA_PATH . $id . '.json';
    if (!file_exists($file)) return null;

    $data = json_decode(file_get_contents($file), true);
    if (!$data) return null;

    // ── Normalització camps nous (compatibilitat vers enrere) ──
    // Usuaris registrats abans de la funcionalitat de tracking
    // no tindran aquests camps → assignar valors per defecte segurs

    if (!isset($data['share_location'])) {
        $data['share_location'] = false; // per defecte: no comparteix fins que ho activi
    }

    if (!isset($data['checkins'])) {
        $data['checkins'] = []; // per si algun JSON vell no en té
    }

    // last_position NO s'inicialitza — si no en té, és que mai ha obert l'app
    // get_active_positions() ja gestiona el cas de last_position buit

    return $data;
}
```

---

## Verificació

Després d'aplicar el canvi, tots els usuaris existents:

| Camp | Valor per defecte | Efecte |
|---|---|---|
| `share_location` | `false` | No apareixen al mapa fins que activin el tracking |
| `checkins` | `[]` | Cap parada completada (correcte si eren de prova) |
| `last_position` | No existeix | No apareixen al mapa (correcte, mai han obert l'app nova) |

---

## Notes

- Cap fitxer JSON existent es modifica fins que l'usuari faci alguna acció
- Els valors per defecte només existeixen en memòria durant l'execució
- Si l'usuari activa el tracking i es mou → el JSON s'actualitza automàticament amb els camps nous
- Zero downtime, zero pèrdua de dades
