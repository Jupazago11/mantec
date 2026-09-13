<?php

// Fuente unica de empleados (confirmado 2026-09-11): la Bitacora ya NO
// mantiene su propia lista de nombres por separado — sus columnas se
// derivan de estos mismos registros (empleados con bitacora=true que
// registren horas ese mes), igual que pasaria en el sistema real.
//
// Archivo PHP plano (no .blade.php) a proposito: un @include de Blade
// renderiza el parcial en un scope aislado, asi que las variables que
// definiera adentro (como $empleados) no vuelven al include-r. Un
// `include` de PHP con `return` si comparte el resultado sin ese problema.
//
// Los primeros 10 tienen datos completos y ya se usaban antes en
// Empleados. El resto completa el roster real de 40 que aparece en el
// Excel de Bitacora ("Bitacora Septiembre 2026.xlsx") — se generan con
// el mismo shape de campos pero informacion minima/de ejemplo (nombre
// completo, cedula y fechas inventadas), porque el objetivo aqui es que
// la Bitacora deje de tener una lista fantasma, no redactar una biografia
// rica para 40 personas en un mockup.

$empleados = [
    ['nombre' => 'Jhon Alexander Pachón Ríos', 'nickname' => 'Pachón', 'codigo_bitacora' => 'Pachon', 'abreviatura' => null, 'cedula' => '10345678', 'categoria' => 'Campo', 'nacimiento' => '1992-05-14', 'ingreso' => '2022-03-01', 'contrato_inicio' => '2022-03-01', 'contrato_fin' => null, 'usuario' => false, 'bitacora' => true, 'activo' => true,
        'certs' => [
            ['label' => 'Alturas', 'estado' => 'vigente', 'detalle' => 'Vigente hasta 03/2027'],
            ['label' => 'Espacios confinados', 'estado' => 'no_posee', 'detalle' => 'No registra este certificado'],
        ],
        'accesos' => [
            ['empresa' => 'ARGOS', 'completo' => true, 'detalle' => 'Todas las inducciones vigentes'],
            ['empresa' => 'CORONA', 'completo' => true, 'detalle' => 'Todas las inducciones vigentes'],
            ['empresa' => 'CALIDRA', 'completo' => true, 'detalle' => 'Sin inducciones obligatorias registradas: acceso automático'],
        ]],
    ['nombre' => 'Brayan Alexis Quintero Salazar', 'nickname' => 'Brayan Q', 'abreviatura' => null, 'cedula' => '10456789', 'categoria' => 'Campo', 'nacimiento' => '1998-11-02', 'ingreso' => '2023-06-15', 'contrato_inicio' => '2023-06-15', 'contrato_fin' => null, 'usuario' => false, 'bitacora' => true, 'activo' => true,
        'certs' => [
            ['label' => 'Alturas', 'estado' => 'vencido', 'detalle' => 'Vencido desde 01/08/2026'],
            ['label' => 'Espacios confinados', 'estado' => 'vigente', 'detalle' => 'Vigente hasta 10/2026'],
        ],
        'accesos' => [
            ['empresa' => 'CORONA', 'completo' => false, 'detalle' => 'Falta: Inducción seguridad Corona (vencida)'],
            ['empresa' => 'CALIDRA', 'completo' => true, 'detalle' => 'Sin inducciones obligatorias registradas: acceso automático'],
        ]],
    ['nombre' => 'Anderson Steven Ortiz Bedoya', 'nickname' => 'Anderson', 'abreviatura' => null, 'cedula' => '10567890', 'categoria' => 'Campo', 'nacimiento' => '1999-02-20', 'ingreso' => '2024-01-10', 'contrato_inicio' => '2024-07-10', 'contrato_fin' => '2026-10-01', 'usuario' => false, 'bitacora' => true, 'activo' => true,
        'certs' => [
            ['label' => 'Alturas', 'estado' => 'no_posee', 'detalle' => 'No registra este certificado'],
            ['label' => 'Espacios confinados', 'estado' => 'no_posee', 'detalle' => 'No registra este certificado'],
        ],
        'accesos' => [
            ['empresa' => 'ARGOS', 'completo' => false, 'detalle' => 'Falta: Inducción SISO Argos, Inducción trabajo en alturas Argos'],
            ['empresa' => 'CALIDRA', 'completo' => true, 'detalle' => 'Sin inducciones obligatorias registradas: acceso automático'],
        ]],
    ['nombre' => 'Carlos Andrés Valbuena Pérez', 'nickname' => 'Valbuena', 'abreviatura' => null, 'cedula' => '10678901', 'categoria' => 'Campo', 'nacimiento' => '1990-08-30', 'ingreso' => '2021-11-20', 'contrato_inicio' => '2021-11-20', 'contrato_fin' => null, 'usuario' => false, 'bitacora' => true, 'activo' => true,
        'certs' => [
            ['label' => 'Alturas', 'estado' => 'vigente', 'detalle' => 'Vigente hasta 05/2027'],
            ['label' => 'Espacios confinados', 'estado' => 'vigente', 'detalle' => 'Vigente hasta 05/2027'],
        ],
        'accesos' => [
            ['empresa' => 'ARGOS', 'completo' => true, 'detalle' => 'Todas las inducciones vigentes'],
            ['empresa' => 'CORONA', 'completo' => true, 'detalle' => 'Todas las inducciones vigentes'],
            ['empresa' => 'CALIDRA', 'completo' => true, 'detalle' => 'Sin inducciones obligatorias registradas: acceso automático'],
        ]],
    ['nombre' => 'Gerónimo Antonio Restrepo Vélez', 'nickname' => 'Gerónimo', 'codigo_bitacora' => 'Geronimo', 'abreviatura' => 'GR', 'cedula' => '20123456', 'categoria' => 'Administrativos', 'nacimiento' => '1985-01-12', 'ingreso' => '2019-02-01', 'contrato_inicio' => '2019-02-01', 'contrato_fin' => null, 'usuario' => true, 'bitacora' => true, 'activo' => true,
        'certs' => [
            ['label' => 'Alturas', 'estado' => 'vigente', 'detalle' => 'Vigente hasta 09/2027'],
            ['label' => 'Espacios confinados', 'estado' => 'vigente', 'detalle' => 'Vigente hasta 09/2027'],
        ],
        'accesos' => [
            ['empresa' => 'ARGOS', 'completo' => true, 'detalle' => 'Todas las inducciones vigentes'],
            ['empresa' => 'CORONA', 'completo' => true, 'detalle' => 'Todas las inducciones vigentes'],
            ['empresa' => 'CALIDRA', 'completo' => true, 'detalle' => 'Sin inducciones obligatorias registradas: acceso automático'],
        ]],
    ['nombre' => 'Camila Andrea Restrepo Gómez', 'nickname' => 'Camila', 'abreviatura' => 'CA', 'cedula' => '20234567', 'categoria' => 'Administrativos', 'nacimiento' => '1993-09-12', 'ingreso' => '2020-07-05', 'contrato_inicio' => '2020-07-05', 'contrato_fin' => null, 'usuario' => true, 'bitacora' => true, 'activo' => true,
        'certs' => [
            ['label' => 'Alturas', 'estado' => 'vigente', 'detalle' => 'Vigente hasta 07/2027'],
            ['label' => 'Espacios confinados', 'estado' => 'vigente', 'detalle' => 'Vigente hasta 07/2027'],
        ],
        'accesos' => [
            ['empresa' => 'CORONA', 'completo' => true, 'detalle' => 'Todas las inducciones vigentes'],
            ['empresa' => 'CALIDRA', 'completo' => true, 'detalle' => 'Sin inducciones obligatorias registradas: acceso automático'],
        ]],
    ['nombre' => 'Luis Alberto Meza Cárdenas', 'nickname' => 'Luis Meza', 'codigo_bitacora' => 'Luis M', 'abreviatura' => 'LM', 'cedula' => '20345678', 'categoria' => 'Administrativos', 'nacimiento' => '1988-09-05', 'ingreso' => '2018-09-12', 'contrato_inicio' => '2018-09-12', 'contrato_fin' => null, 'usuario' => true, 'bitacora' => true, 'activo' => true,
        'certs' => [
            ['label' => 'Alturas', 'estado' => 'vigente', 'detalle' => 'Vigente hasta 12/2026'],
            ['label' => 'Espacios confinados', 'estado' => 'no_posee', 'detalle' => 'No registra este certificado'],
        ],
        'accesos' => [
            ['empresa' => 'ARGOS', 'completo' => true, 'detalle' => 'Todas las inducciones vigentes'],
            ['empresa' => 'CALIDRA', 'completo' => true, 'detalle' => 'Sin inducciones obligatorias registradas: acceso automático'],
        ]],
    ['nombre' => 'Luis Fernando Montoya Zuluaga', 'nickname' => 'Fernando', 'codigo_bitacora' => 'Luis Fdo M', 'abreviatura' => 'F', 'cedula' => '20567890', 'categoria' => 'Administrativos', 'nacimiento' => '1987-06-18', 'ingreso' => '2019-05-01', 'contrato_inicio' => '2019-05-01', 'contrato_fin' => null, 'usuario' => true, 'bitacora' => true, 'activo' => true,
        'certs' => [
            ['label' => 'Alturas', 'estado' => 'vigente', 'detalle' => 'Vigente hasta 04/2027'],
            ['label' => 'Espacios confinados', 'estado' => 'vigente', 'detalle' => 'Vigente hasta 04/2027'],
        ],
        'accesos' => [
            ['empresa' => 'CORONA', 'completo' => true, 'detalle' => 'Todas las inducciones vigentes'],
            ['empresa' => 'CALIDRA', 'completo' => true, 'detalle' => 'Sin inducciones obligatorias registradas: acceso automático'],
        ]],
    ['nombre' => 'Diana Patricia Suan Restrepo', 'nickname' => 'Diana Suan', 'abreviatura' => 'DS', 'cedula' => '20456789', 'categoria' => 'Administrativos', 'nacimiento' => '1980-12-01', 'ingreso' => '2017-04-10', 'contrato_inicio' => '2017-04-10', 'contrato_fin' => null, 'usuario' => true, 'bitacora' => false, 'activo' => true,
        'certs' => [
            ['label' => 'Alturas', 'estado' => 'no_posee', 'detalle' => 'No registra este certificado'],
            ['label' => 'Espacios confinados', 'estado' => 'no_posee', 'detalle' => 'No registra este certificado'],
        ],
        'accesos' => [
            ['empresa' => 'CALIDRA', 'completo' => true, 'detalle' => 'Sin inducciones obligatorias registradas: acceso automático'],
        ]],
    ['nombre' => 'Ana María Cárdenas Ruiz', 'nickname' => 'Ana Cárdenas', 'abreviatura' => null, 'cedula' => '10234567', 'categoria' => 'Campo', 'nacimiento' => '1989-04-22', 'ingreso' => '2019-08-01', 'contrato_inicio' => '2024-01-01', 'contrato_fin' => '2025-01-15', 'usuario' => false, 'bitacora' => true, 'activo' => false,
        'certs' => [
            ['label' => 'Alturas', 'estado' => 'vencido', 'detalle' => 'Vencido desde 01/2025'],
            ['label' => 'Espacios confinados', 'estado' => 'vencido', 'detalle' => 'Vencido desde 01/2025'],
        ],
        'accesos' => [
            ['empresa' => 'CALIDRA', 'completo' => true, 'detalle' => 'Sin inducciones obligatorias registradas: acceso automático'],
        ]],
];

// Resto del roster real de 40 de la Bitacora (capturas de
// "Bitacora Septiembre 2026.xlsx"). Nickname => overrides puntuales que
// ya conociamos de otras vistas (categoria/abreviatura del catalogo de
// Programacion; acceso a CORONA para el equipo real de esa empresa
// observado en la Programacion — Wilmar, Espinosa 2, Herrera, Ana C,
// Norman — el resto queda con ARGOS+CALIDRA por defecto).
$rosterAdicional = [
    'Bonilla' => ['nombre' => 'Bonilla Restrepo Ospina'],
    'Norman' => ['nombre' => 'Norman Andrés Gómez Ruiz', 'corona' => true],
    'Alais' => ['nombre' => 'Alais Fernanda Ospina Ríos'],
    'Espinosa 1' => ['nombre' => 'Carlos Espinosa Muñoz'],
    'Jeison' => ['nombre' => 'Jeison Alexander Correa Vélez'],
    'Wilmar' => ['nombre' => 'Wilmar Patiño Londoño', 'corona' => true],
    'Sara' => ['nombre' => 'Sara Milena Arango Betancur'],
    'Espinosa 2' => ['nombre' => 'Andrés Espinosa Muñoz', 'corona' => true],
    'Monsalve' => ['nombre' => 'Jorge Monsalve Restrepo'],
    'Alex Sierra' => ['nombre' => 'Alex Sierra Correa'],
    'Geferson' => ['nombre' => 'Geferson Ospina Bedoya'],
    'Alejandro' => ['nombre' => 'Alejandro Zapata Ríos'],
    'Herrera' => ['nombre' => 'Fabián Herrera Gómez', 'corona' => true],
    'Alan' => ['nombre' => 'Alan Rúa Salazar'],
    'Yesid' => ['nombre' => 'Yesid Cárdenas Muñoz'],
    'J Manuel' => ['nombre' => 'Juan Manuel Ospina Vélez'],
    'Cristian M' => ['nombre' => 'Cristian Marín Betancur'],
    'Evelio' => ['nombre' => 'Evelio Arango Ríos'],
    'Brayan R' => ['nombre' => 'Brayan Ramírez Gómez'],
    'Brahian Q' => ['nombre' => 'Brahian Quiroga Salazar'],
    'Omar' => ['nombre' => 'Omar Betancur Londoño'],
    'Nedy Johana' => ['nombre' => 'Nedy Johana Restrepo Arango'],
    'Danilo' => ['nombre' => 'Danilo Correa Ospina'],
    'Brayam P' => ['nombre' => 'Brayam Peña Muñoz'],
    'Ana C' => ['nombre' => 'Ana Cecilia Vélez Ríos', 'corona' => true],
    'Eder' => ['nombre' => 'Eder Salazar Gómez'],
    'Diego O' => ['nombre' => 'Diego Ortiz Betancur'],
    'Brayan C' => ['nombre' => 'Brayan Cárdenas Arango'],
    'Diego S' => ['nombre' => 'Diego Sánchez Muñoz'],
    'Wilmar Guzman' => ['nombre' => 'Wilmar Guzmán Ríos'],
    'Jose Alberto' => ['nombre' => 'José Alberto Londoño Ospina'],
    'Neider' => ['nombre' => 'Neider Bedoya Salazar'],
    'J David' => ['nombre' => 'José David Muñoz Correa'],
    'Alberto Zurbaran' => ['nombre' => 'Alberto Zurbarán Ríos'],
    'Conrado' => ['nombre' => 'Conrado Isaza Restrepo', 'categoria' => 'Administrativos', 'abreviatura' => 'CO', 'usuario' => true],
];

$i = 0;
foreach ($rosterAdicional as $nickname => $datos) {
    $i++;
    $categoria = $datos['categoria'] ?? 'Campo';

    // Variedad deliberada (confirmado 2026-09-11): antes TODOS los
    // generados tenian ARGOS vigente, asi que el modal de "Nueva
    // actividad" mostraba el mismo patron de acceso para practicamente
    // todo el mundo. En la realidad no todos tienen la induccion vigente
    // — unos pocos la tienen vencida y otros nunca la tomaron (no_posee,
    // que se modela omitiendo la entrada por completo).
    $accesos = [
        ['empresa' => 'CALIDRA', 'completo' => true, 'detalle' => 'Sin inducciones obligatorias registradas: acceso automático'],
    ];
    $patronArgos = $i % 9;
    if ($patronArgos === 0) {
        $accesos[] = ['empresa' => 'ARGOS', 'completo' => false, 'detalle' => 'Falta: Inducción SISO Argos (vencida)'];
    } elseif ($patronArgos !== 4) {
        // patronArgos === 4 se deja fuera del array a proposito: nunca
        // tomo la induccion de Argos (no_posee).
        $accesos[] = ['empresa' => 'ARGOS', 'completo' => true, 'detalle' => 'Todas las inducciones vigentes'];
    }
    if (!empty($datos['corona'])) {
        $accesos[] = ['empresa' => 'CORONA', 'completo' => true, 'detalle' => 'Todas las inducciones vigentes'];
    }

    // Certificados: mismo espiritu de variedad, con modulos distintos a los
    // de ARGOS arriba para que no coincidan siempre en las mismas personas.
    $patronAlturas = $i % 5;
    $estadoAlturas = $patronAlturas === 0 ? 'vencido' : ($patronAlturas === 3 ? 'no_posee' : 'vigente');
    $detalleAlturas = match ($estadoAlturas) {
        'vencido' => 'Vencido desde 06/2026',
        'no_posee' => 'No registra este certificado',
        default => 'Vigente hasta 12/2027',
    };
    $patronEspacios = $i % 6;
    $estadoEspacios = $patronEspacios === 0 ? 'vencido' : ($patronEspacios === 4 ? 'no_posee' : 'vigente');
    $detalleEspacios = match ($estadoEspacios) {
        'vencido' => 'Vencido desde 03/2026',
        'no_posee' => 'No registra este certificado',
        default => 'Vigente hasta 12/2027',
    };

    $empleados[] = [
        'nombre' => $datos['nombre'],
        'nickname' => $nickname,
        'codigo_bitacora' => $nickname,
        'abreviatura' => $datos['abreviatura'] ?? null,
        'cedula' => (string) (10700000 + $i),
        'categoria' => $categoria,
        'nacimiento' => sprintf('19%02d-%02d-%02d', 82 + ($i % 15), 1 + ($i % 12), 1 + ($i % 27)),
        'ingreso' => sprintf('20%02d-%02d-01', 17 + ($i % 8), 1 + ($i % 12)),
        'contrato_inicio' => sprintf('20%02d-%02d-01', 17 + ($i % 8), 1 + ($i % 12)),
        'contrato_fin' => null,
        'usuario' => $datos['usuario'] ?? false,
        'bitacora' => true,
        'activo' => true,
        'certs' => [
            ['label' => 'Alturas', 'estado' => $estadoAlturas, 'detalle' => $detalleAlturas],
            ['label' => 'Espacios confinados', 'estado' => $estadoEspacios, 'detalle' => $detalleEspacios],
        ],
        'accesos' => $accesos,
    ];
}

return $empleados;
