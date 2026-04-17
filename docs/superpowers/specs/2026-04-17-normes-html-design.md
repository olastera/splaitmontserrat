# Normes de la Ruta 2026 — Disseny pàgina HTML

## 1. Objectiu
- Publicar les normes de la Caminada Spai-T perquè qualsevol participant les llegeixi des del mòbil sense obrir el PDF.
- Respectar el look & feel de la cartilla (Nunito/Open Sans, paleta vermell/verd/groc + textures sorra) i fer-la accessible.
- La pàgina ha de ser pública (`normes.php`) però també accessible des de la cartilla, login i modal d'inici.

## 2. Estructura de continguts
- Convertir el PDF en un array PHP `NORMES_SECCIONS` (fitxer propi `includes/normes_data.php` o bloc a `normes.php`).
- Clau per secció:
  - `hero`: títol “Reunió Ruta 2026”, subtítol, botons.
  - `etapes`: llista ordenada de 10 trams. Camp `ruta` amb valors `llarga`, `curta`, `ambdues` per marcar icones.
  - `que_portem`: subllistes `caminar` i `pernocta`.
  - `consells`: array d’elements curts.
  - `normes`: dues llistes (`generals`, `tritons`).
  - `termos` i `preguntes`: text lliure + CTA (email/telèfon existent a settings `event.contacte`).
- Textos en català, minimitzar uppercase per llegibilitat.

## 3. Layout i estil
- Fitxer nou `normes.php` amb estructura base Bootstrap (ja s’utilitza CDN).
- S’estén `assets/css/spait.css` amb bloc específic `.normes-hero`, `.normes-section`, `.timeline`, etc. Es reutilitza paleta CSS variables.
- **Hero**: altura mínima 70vh, gradient suau (#F7E7D3 → #C08E64), il·lustració SVG muntanyes (inline). Tipografia Nunito per títol (4rem) i Open Sans per cos.
- **Botons**: CTA doble (`btn-spait` i `btn-outline-light`). El botó “Baixa el PDF” enllaça a `docs/Ruta 2026.pdf` (target `_blank`).
- **Timeline etapes**: llista `<ol>` amb pseudo-element vertical i badges per diferenciar ruta (`Ruta Llarga`, `Ruta Curta`, `Ambdues`). Animació `fade-up` amb `@keyframes`. Responsiu: canvia a cartes apilades en mòbil.
- **Seccions materials**: grid 2 columnes en `md`, targetes translúcides `backdrop-filter: blur(6px)` per reforçar l’estètica de sorra.
- **Consells**: targetes petites amb emoji + text curt, distribuïdes amb CSS grid.
- **Normes**: dues targetes (Generals, Tritons) amb fons contrastat (verd i blau). Icones `bi-people` i `bi-stars`.
- **Footer**: enllaços a Cartilla (`cartilla.php`) i Login (`index.php`).

## 4. Accessos
- `cartilla.php` navbar: nou botó `btn-outline-light` amb icona `bi-journal-text` → `normes.php` (target `_blank` no necessari perquè és la mateixa app, però es pot mantenir). Col·locat entre PDF i Ranking.
- `index.php` (login): sota targeta de formulari, afegir link botó “Consulta les normes de la ruta”.
- Modal d’inici (cartilla): dins el text del modal afegir enllaç `<a href="normes.php" target="_blank">Normes</a>` perquè els participants puguin revisar mentre esperen.
- Countdown (ja existeix abans d’activar ruta): fins que arribi l’hora, mostrar un banner dins el modal amb text “Mentrestant pots llegir les normes” i botó.

## 5. Accessibilitat i UX
- Textos grans (≥ 1rem), contrast AA.
- Totes les icones decoratives porten `aria-hidden="true"` i hi ha `aria-label` als botons.
- S’afegeix `lang="ca"` a `normes.php`.
- S’evita scroll horitzontal amb `max-width` i `overflow hidden` als fons absolut.

## 6. Fitxers nous/afectats
1. `normes.php` (nou)
2. `assets/css/spait.css` (nous estils)
3. `includes/normes_data.php` (opcional per separar contingut; si es fa inline, no es crea)
4. `cartilla.php` (botó navbar + link modal)
5. `index.php` (btn/link a normes)
6. `assets/js/cartilla.js` (si cal afegir comportament per countdown/modal; preferible només HTML)

## 7. Pendents
- Un cop implementat el disseny caldrà invocar l’skill `writing-plans` per planificar el desenvolupament i assegurar-se que el PDF es troba a `docs/Ruta 2026.pdf`.
