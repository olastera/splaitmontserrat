# 🏔️ Cartilla Virtual — Caminada Spai-T a Montserrat

![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?style=flat&logo=php)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat&logo=bootstrap)
![Leaflet](https://img.shields.io/badge/Leaflet.js-OpenStreetMap-199900?style=flat&logo=leaflet)
![License](https://img.shields.io/badge/Llicència-MIT-green?style=flat)

Web app per gestionar la caminada anual de l'**Esplai Spai-T** de Barcelona/Terrassa a Montserrat. Substitueix la cartilla física de pelegrí per una versió digital amb mapa en temps real, check-ins per codi secret i certificat PDF descàrregable.

> *"Som d'esplai, res no ens atura!"* 🔴

---

## ✨ Funcionalitats

### 👤 Participant
- Registre amb nom, e-mail o telèfon, ruta i motivació personal
- Mapa interactiu amb posició GPS en temps real (OpenStreetMap + Leaflet)
- Distància i temps estimat fins a la propera parada
- Check-in a cada parada mitjançant codi secret
- Test de 3 preguntes a cada parada
- Cartilla virtual amb segells de cada punt completat
- Control de privacitat GPS (compartir o no la ubicació)
- Descàrrega de la cartilla en PDF al final

### 🔐 Administrador
- Mapa en temps real de tots els participants
- Gestió completa de participants (veure, reset de contrasenya, eliminar)
- Configuració de punts de parada amb mapa interactiu
- Preguntes del test editables per cada parada
- Configuració visual (logo, colors, nom de l'app)
- Toggle GPS: activar/desactivar requisit de proximitat per al check-in
- Exportació CSV de participants
- Zona de perill: eliminar usuaris de prova / tots / reset per nou any

---

## 🗺️ La ruta 2026

| # | Punt | Ruta |
|---|------|------|
| 0 | Inici — Mundet (Barcelona) | Llarga |
| 1 | Sant Cugat | Llarga |
| 2 | Can Barata | Llarga |
| 3 | Les Fonts ⭐ | Ambdues |
| 4 | Quatre Vents | Ambdues |
| 5 | Can Cabassa | Ambdues |
| 6 | Oasi | Ambdues |
| 7 | Olesa de Montserrat | Ambdues |
| 8 | Aeri | Ambdues |
| 9 | Monistrol | Ambdues |
| 10 | 🏆 Montserrat | Ambdues |

⭐ Les Fonts és el punt d'incorporació de la ruta curta (Terrassa)

---

## 🛠️ Stack tècnic

- **Backend**: PHP 8+ pur (sense frameworks)
- **Frontend**: Bootstrap 5 + Vanilla JS
- **Mapes**: Leaflet.js + OpenStreetMap (sense API key)
- **Persistència**: Fitxers JSON (sense base de dades)
- **PDF**: FPDF / mPDF
- **Drag & drop**: SortableJS

> ℹ️ No cal base de dades. Tot es guarda en fitxers `.json` a `/data/`.

---

## 🚀 Instal·lació

### Requisits
- PHP 8.0+
- Servidor web (Apache / Nginx)
- Extensió PHP: `openssl`

### Amb Docker (recomanat)
```bash
git clone https://github.com/el-teu-usuari/splaitmontserrat.git
cd splaitmontserrat
docker-compose up -d
```

### Manual
```bash
git clone https://github.com/el-teu-usuari/splaitmontserrat.git
cd splaitmontserrat

# Crear carpetes de dades
mkdir -p data/users
chmod 755 data/users

# Configurar l'admin
cp includes/config.example.php includes/config.php
nano includes/config.php
```

### Configuració mínima (`includes/config.php`)
```php
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'la-teva-contrasenya');
define('CRYPTO_KEY', 'clau-secreta-unica');
define('DATA_PATH',  __DIR__ . '/../data/users/');
```

---

## ⚙️ Configuració des de l'admin

Un cop instal·lat, accedeix a `/admin` i configura:

1. **Configuració** → nom de l'app, logo, colors corporatius
2. **Parades** → afegir/editar/reordenar punts amb mapa interactiu
3. **Preguntes** → personalitzar el test de cada parada

L'app és **universal**: qualsevol esplai o grup pot adaptar-la a la seva caminada sense tocar codi.

---

## 📁 Estructura del projecte

```
/
├── index.php              ← registre / login participant
├── cartilla.php           ← app principal
├── checkin.php            ← validació codi
├── download_pdf.php       ← generació PDF
├── update_position.php    ← endpoint GPS
├── toggle_location.php    ← endpoint privacitat
├── admin/                 ← panel d'administració
├── includes/              ← lògica PHP
│   ├── config.php         ← configuració (NO al git)
│   ├── auth.php
│   ├── user.php
│   └── crypto.php
├── data/                  ← dades (NO al git)
│   ├── users/
│   └── settings.json
└── assets/
```

---

## 🔒 Seguretat i privacitat

- Contrasenyes encriptades amb **AES-256-CBC** reversible
- Carpeta `/data/` protegida amb `.htaccess`
- Tracking GPS opcional per al participant
- L'última posició coneguda es conserva per seguretat (caminada mixta menors/adults)
- Sessions PHP amb `session_regenerate_id()` al login

---

## 🌍 Fer-la universal

L'app està dissenyada per ser reutilitzable per qualsevol esplai:

1. Canvia el logo i colors des de l'admin
2. Configura els punts de parada de la teva ruta
3. Personalitza les preguntes del test
4. Canvia el nom de l'app i els missatges

---

## 📄 Llicència

MIT — Lliure per usar, modificar i distribuir.

Si l'uses per a la teva caminada, ens alegrem molt! 🏔️

---

## 🙏 Crèdits

Desenvolupat per i per a l'**Esplai Spai-T** — La Marina de Port, Barcelona.

🌐 [esplaispait.com](https://esplaispait.com)
