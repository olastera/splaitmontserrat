<?php
// ADMIN
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'spait2026');
define('CRYPTO_KEY', 'SpaiT_2026_SecretKey_Montserrat');

// RUTES
define('DATA_PATH', __DIR__ . '/../data/users/');

// Assegurar que la carpeta existeix
if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0755, true);
}

// PUNTS DE PARADA
$PARADES = [
  [
    'id'        => 0,
    'nom'       => 'Inici — Mundet (Barcelona)',
    'ruta'      => 'llarga',
    'lat'       => 41.439356,
    'lng'       => 2.147705,
    'codi'      => null,
    'inici'     => true,
  ],
  [
    'id'        => 1,
    'nom'       => '1a Parada — Sant Cugat',
    'ruta'      => 'llarga',
    'lat'       => 41.4731,
    'lng'       => 2.0862,
    'codi'      => 'CUGAT1',
  ],
  [
    'id'        => 2,
    'nom'       => '2a Parada — Can Barata',
    'ruta'      => 'llarga',
    'lat'       => 41.510952,
    'lng'       => 2.066352,
    'codi'      => 'BARATA2',
  ],
  [
    'id'        => 3,
    'nom'       => '3a Parada — Les Fonts (Inici Ruta Curta)',
    'ruta'      => 'ambdues',
    'lat'       => 41.527771,
    'lng'       => 2.037094,
    'codi'      => 'FONTS3',
    'inici_curt' => true,
  ],
  [
    'id'        => 4,
    'nom'       => '4a Parada — Quatre Vents',
    'ruta'      => 'ambdues',
    'lat'       => 41.541902,
    'lng'       => 1.992529,
    'codi'      => 'VENTS4',
  ],
  [
    'id'        => 5,
    'nom'       => '5a Parada — Can Cabassa',
    'ruta'      => 'ambdues',
    'lat'       => 41.535821,
    'lng'       => 1.963115,
    'codi'      => 'CABASSA5',
  ],
  [
    'id'        => 6,
    'nom'       => '6a Parada — Oasi',
    'ruta'      => 'ambdues',
    'lat'       => 41.538307,
    'lng'       => 1.931132,
    'codi'      => 'OASI6',
  ],
  [
    'id'        => 7,
    'nom'       => '7a Parada — Olesa de Montserrat',
    'ruta'      => 'ambdues',
    'lat'       => 41.543889,
    'lng'       => 1.886111,
    'codi'      => 'OLESA7',
  ],
  [
    'id'        => 8,
    'nom'       => '8a Parada — Aeri',
    'ruta'      => 'ambdues',
    'lat'       => 41.591353,
    'lng'       => 1.852986,
    'codi'      => 'AERI8',
  ],
  [
    'id'        => 9,
    'nom'       => '9a Parada — Monistrol',
    'ruta'      => 'ambdues',
    'lat'       => 41.609691,
    'lng'       => 1.842395,
    'codi'      => 'MONISTROL9',
  ],
  [
    'id'        => 10,
    'nom'       => 'MONTSERRAT!!!',
    'ruta'      => 'ambdues',
    'lat'       => 41.593338,
    'lng'       => 1.837625,
    'codi'      => 'MORENETA2026',
    'final'     => true,
  ],
];

// Llegir configuració global (amb cache estàtica)
function get_settings(): array {
    static $settings = null;
    if ($settings !== null) return $settings;

    $file = __DIR__ . '/../data/settings.json';
    if (file_exists($file)) {
        $loaded = json_decode(file_get_contents($file), true);
        if ($loaded) {
            $settings = $loaded;
            return $settings;
        }
    }

    $settings = get_default_settings();
    return $settings;
}

function get_default_settings(): array {
    global $PARADES, $TESTS;

    // Convertir format antic de parades al format nou
    $parades_new = [];
    foreach ($PARADES as $p) {
        $ruta = $p['ruta'] ?? 'ambdues';
        $rutes = ($ruta === 'ambdues') ? ['llarga', 'curta'] : [$ruta];
        $id = $p['id'];
        $preguntes = [];
        if (isset($TESTS[$id])) {
            $idx = 1;
            foreach ($TESTS[$id] as $key => $q) {
                $preguntes[] = [
                    'id'     => 'p' . $id . '_' . $idx,
                    'text'   => $q['pregunta'],
                    'tipus'  => $q['tipus'],
                    'opcions' => $q['opcions'] ?? [],
                ];
                $idx++;
            }
        }
        $np = [
            'id'                => $id,
            'nom'               => $p['nom'],
            'rutes'             => $rutes,
            'lat'               => $p['lat'],
            'lng'               => $p['lng'],
            'codi'              => $p['codi'],
            'radi_metres'       => null,
            'es_inici'          => !empty($p['inici']),
            'es_final'          => !empty($p['final']),
            'missatge_arribada' => '',
            'preguntes'         => $preguntes,
        ];
        if (!empty($p['inici_curt'])) {
            $np['es_inici_ruta'] = 'curta';
        }
        $parades_new[] = $np;
    }

    return [
        'gps_override' => false,
        'event' => [
            'nom'                 => 'Caminada a Montserrat 2026',
            'organitzacio'        => 'splaiT',
            'data_esdeveniment'   => '2026-04-19',
            'web'                 => 'https://esplaispait.com',
            'contacte'            => '722 313 772',
            'missatge_benvinguda' => 'Benvingut/da a la caminada!',
            'missatge_final'      => 'HO HAS ACONSEGUIT!',
            'avis_global'         => '',
            'mode_prova'          => false,
            'registre_obert'      => true,
        ],
        'visual' => [
            'logo_url'        => 'https://esplaispait.com/wp-content/uploads/2024/11/cropped-cropped-cropped-logo_splait-removebg-preview-1.png',
            'logo_local'      => '',
            'color_primari'   => '#C0392B',
            'color_secundari' => '#27AE60',
            'color_accent'    => '#F1C40F',
            'nom_app'         => 'Cartilla del Pelegrí',
        ],
        'checkin' => [
            'require_gps'  => false,
            'radi_metres'  => 200,
            'codi_mestre'  => '',
        ],
        'rutes' => [
            ['id' => 'llarga', 'nom' => 'Ruta Llarga (Barcelona)', 'descripcio' => ''],
            ['id' => 'curta',  'nom' => 'Ruta Curta (Terrassa)',   'descripcio' => ''],
        ],
        'parades' => $parades_new,
    ];
}

function save_settings(array $settings): bool {
    $file = __DIR__ . '/../data/settings.json';
    return file_put_contents(
        $file,
        json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    ) !== false;
}

function is_gps_override(): bool {
    $s = get_settings();
    return !empty($s['gps_override']);
}

// TESTS PER PARADA
$TESTS = [
  1 => [
    'p1' => ['pregunta' => 'Com et trobes físicament?',           'opcions' => ['Genial', 'Bé', 'Regular', 'Cansat/da'], 'tipus' => 'opcions'],
    'p2' => ['pregunta' => 'Com has trobat el camí fins aquí?',   'opcions' => ['Fàcil', 'Moderat', 'Dur'],               'tipus' => 'opcions'],
    'p3' => ['pregunta' => 'Una paraula per descriure com et sents ara',  'tipus' => 'text'],
  ],
  2 => [
    'p1' => ['pregunta' => 'Com va l\'energia?',                  'opcions' => ['Al 100%', 'Bé', 'Necessito descans'],    'tipus' => 'opcions'],
    'p2' => ['pregunta' => 'T\'has perdut en algun moment?',       'opcions' => ['No', 'Una mica', 'Sí jaja'],            'tipus' => 'opcions'],
    'p3' => ['pregunta' => 'Quin ha estat el millor moment fins ara?',    'tipus' => 'text'],
  ],
  3 => [
    'p1' => ['pregunta' => 'Com t\'has sentit incorporant-te / rebent la gent de Terrassa?', 'opcions' => ['Alegre', 'Emocionat/da', 'Normal'], 'tipus' => 'opcions'],
    'p2' => ['pregunta' => 'Estàs gaudint del paisatge?',          'opcions' => ['Molt', 'Bastant', 'Estic massa cansat/da per mirar'], 'tipus' => 'opcions'],
    'p3' => ['pregunta' => 'Dedica una paraula a algú que portes al cor avui', 'tipus' => 'text'],
  ],
  4 => [
    'p1' => ['pregunta' => 'Portem la meitat del camí. Com et sents?', 'opcions' => ['Fort/a', 'Bé', 'Aguantant', 'Dur'], 'tipus' => 'opcions'],
    'p2' => ['pregunta' => 'Has menjat i begut prou?',              'opcions' => ['Sí perfecte', 'Podria menjar més', 'He oblidat beure'], 'tipus' => 'opcions'],
    'p3' => ['pregunta' => 'Anècdota del dia fins ara',             'tipus' => 'text'],
  ],
  5 => [
    'p1' => ['pregunta' => 'Les cames com les tens?',               'opcions' => ['Com nous', 'Bé', 'Una mica carregades', 'Pesades'], 'tipus' => 'opcions'],
    'p2' => ['pregunta' => 'L\'ambient del grup, com és?',           'opcions' => ['Increïble', 'Molt bo', 'Bé', 'Silenciós jaja'], 'tipus' => 'opcions'],
    'p3' => ['pregunta' => 'Missatge per als que venen darrere',     'tipus' => 'text'],
  ],
  6 => [
    'p1' => ['pregunta' => 'Ja es veu Montserrat! Quina emoció sents?', 'opcions' => ['Emoció pura', 'Alegria', 'Alivio', 'Incredul/a'], 'tipus' => 'opcions'],
    'p2' => ['pregunta' => 'Si poguessis tornar enrere, faries la caminada?', 'opcions' => ['100% sí', 'Probablement sí', 'Preguntem quan arribem'], 'tipus' => 'opcions'],
    'p3' => ['pregunta' => 'Quina és la teva motivació per acabar?',  'tipus' => 'text'],
  ],
  7 => [
    'p1' => ['pregunta' => 'Última parada urbana. Com et trobes?',   'opcions' => ['Estic volant', 'Bé', 'Cansad/a però content/a', 'Molt cansat/a'], 'tipus' => 'opcions'],
    'p2' => ['pregunta' => 'Has après alguna cosa avui?',             'opcions' => ['Sí, molt', 'Una mica', 'Estic massa cansat per pensar'], 'tipus' => 'opcions'],
    'p3' => ['pregunta' => 'Dedica una frase a la muntanya que ja veus', 'tipus' => 'text'],
  ],
  8 => [
    'p1' => ['pregunta' => 'Ja som a la falda de Montserrat! Quin sentiment tens?', 'opcions' => ['Sagrat', 'Emocionat/da', 'Orgullós/a', 'Tot alhora'], 'tipus' => 'opcions'],
    'p2' => ['pregunta' => 'Com tens els peus?',                      'opcions' => ['Perfectes', 'Alguna ampolla', 'Molts embenats'], 'tipus' => 'opcions'],
    'p3' => ['pregunta' => 'Per a qui fas aquesta pujada final?',      'tipus' => 'text'],
  ],
  9 => [
    'p1' => ['pregunta' => 'Últim tram! Quina velocitat duus?',       'opcions' => ['Esprint final!', 'Ritme constant', 'A poc a poc però segur/a'], 'tipus' => 'opcions'],
    'p2' => ['pregunta' => 'Com descriuries avui en una paraula?',     'tipus' => 'text'],
    'p3' => ['pregunta' => 'Quin consell donaries a algú que volgués fer-ho l\'any que ve?', 'tipus' => 'text'],
  ],
  10 => [
    'p1' => ['pregunta' => 'HO HEM ACONSEGUIT! Quin sentiment predomina?', 'opcions' => ['Alegria', 'Emoció', 'Orgull', 'Pau interior', 'Tot alhora'], 'tipus' => 'opcions'],
    'p2' => ['pregunta' => 'Repetiràs l\'any que ve?',                 'opcions' => ['Sí sense dubte!', 'Crec que sí', 'Pregunta\'m d\'aquí uns dies'], 'tipus' => 'opcions'],
    'p3' => ['pregunta' => 'Un missatge per guardar per sempre',        'tipus' => 'text'],
  ],
];
