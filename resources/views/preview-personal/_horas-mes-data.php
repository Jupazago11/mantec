<?php

// Horas por dia por empleado en septiembre 2026 (de "Bitacora Septiembre
// 2026.xlsx") — fuente unica compartida por Bitacora (bitacora.blade.php,
// tabla dia a dia + "Total horas" del pie) y Programacion
// (programacion.blade.php, "horas acumuladas este mes" en el buscador de
// personas). Antes 'totales' vivia como un mapa aparte, hardcodeado sin
// relacion con estos valores dia a dia — eso hacia que el total del pie de
// la Bitacora NO coincidiera con la suma de lo que se ve en las celdas de
// los dias 1-6 (unicos con datos de ejemplo). Corregido 2026-09-16: ahora
// 'totales' SIEMPRE se calcula sumando 'valores', asi nunca pueden
// desalinearse.
//
// Archivo PHP plano (no .blade.php) por el mismo motivo que
// _empleados-data.php: un @include de Blade no comparte variables hacia
// quien lo incluye, un `include` de PHP con `return` si.
//
// Claves: 'codigo_bitacora' de _empleados-data.php cuando existe, si no el
// 'nickname' — misma resolucion que ya usaba bitacora.blade.php.

$valores = [
    1 => ['Norman' => 9.5, 'Alais' => 10, 'Espinosa 1' => 9.5, 'Jeison' => 7, 'Wilmar' => 9.5, 'Sara' => 9.5, 'Conrado' => 10, 'Espinosa 2' => 10, 'Monsalve' => 12, 'Alex Sierra' => 12, 'Geferson' => 12, 'Alejandro' => 'L', 'Herrera' => 12, 'Luis Fdo M' => 9.5, 'Yesid' => 10, 'Geronimo' => 12, 'J Manuel' => 12, 'Camila' => 9.5, 'Pachon' => 10, 'Cristian M' => 12, 'Evelio' => 10, 'Brayan R' => 8, 'Brahian Q' => 10, 'Omar' => 10, 'Nedy Johana' => 12, 'Danilo' => 10, 'Brayam P' => 9.5, 'Valbuena' => 9.5, 'Ana C' => 9.5, 'Diego O' => 10, 'Brayan C' => 10, 'Diego S' => 12, 'Wilmar Guzman' => 12, 'Jose Alberto' => 12, 'Neider' => 12, 'Luis M' => 12, 'Diana Suan' => 8],
    2 => ['Bonilla' => 12, 'Norman' => 9.5, 'Alais' => 13.5, 'Espinosa 1' => 9.5, 'Jeison' => 7, 'Wilmar' => 9.5, 'Sara' => 9.5, 'Conrado' => 10, 'Espinosa 2' => 9.5, 'Monsalve' => 12, 'Alex Sierra' => 13.5, 'Geferson' => 12, 'Alejandro' => 'L', 'Herrera' => 12, 'Luis Fdo M' => 9.5, 'Alan' => 12, 'Yesid' => 10, 'Geronimo' => 10, 'J Manuel' => 12, 'Camila' => 9.5, 'Pachon' => 10, 'Cristian M' => 12, 'Evelio' => 10, 'Brayan R' => 12, 'Anderson' => 13.5, 'Brahian Q' => 10, 'Omar' => 10, 'Nedy Johana' => 12, 'Danilo' => 13.5, 'Brayam P' => 9.5, 'Valbuena' => 9.5, 'Ana C' => 9.5, 'Eder' => 12, 'Diego O' => 10, 'Brayan C' => 13.5, 'Diego S' => 12, 'Wilmar Guzman' => 12, 'Jose Alberto' => 12, 'Neider' => 12, 'Luis M' => 12],
    3 => ['Bonilla' => 12, 'Norman' => 9.5, 'Alais' => 10, 'Espinosa 1' => 10, 'Jeison' => [null, 7, 'jornada nocturna-1 ED-11EN'], 'Wilmar' => 9.5, 'Sara' => 9.5, 'Conrado' => 10, 'Espinosa 2' => 9.5, 'Monsalve' => 12, 'Alex Sierra' => 10, 'Alejandro' => 'L', 'Herrera' => 12, 'Luis Fdo M' => 9.5, 'Alan' => 12, 'Yesid' => 10, 'Geronimo' => 10, 'J Manuel' => 10, 'Camila' => 9.5, 'Pachon' => 10, 'Cristian M' => 10, 'Brayan R' => 10, 'Anderson' => 10, 'Brahian Q' => 12, 'Omar' => 10, 'Nedy Johana' => 10, 'Danilo' => 10, 'Brayam P' => 13.5, 'Valbuena' => 9.5, 'Ana C' => 9.5, 'Eder' => 12, 'Diego O' => 10, 'Brayan C' => 10, 'Diego S' => 12, 'Jose Alberto' => 12, 'Luis M' => 12],
    4 => ['Bonilla' => 10.5, 'Norman' => 13.5, 'Alais' => 22.5, 'Espinosa 1' => 8, 'Jeison' => 10.5, 'Wilmar' => 6, 'Sara' => 6, 'Conrado' => 16, 'Espinosa 2' => 8, 'Monsalve' => 11, 'Alex Sierra' => 16, 'Geferson' => 9, 'Alejandro' => 'L', 'Herrera' => 10.5, 'Luis Fdo M' => 12, 'Alan' => 10.5, 'Yesid' => 10.5, 'Pachon' => 10.5, 'Evelio' => 13.5, 'Anderson' => 7, 'Brahian Q' => 10.5, 'Omar' => 12, 'Danilo' => 13.5, 'Ana C' => 12.5, 'Eder' => 13.5, 'Wilmar Guzman' => 10.5, 'Jose Alberto' => 17, 'Neider' => 12],
    5 => ['Bonilla' => 12, 'Alais' => 4, 'Jeison' => 10.5, 'Sara' => 7, 'Alex Sierra' => 12.5, 'Geferson' => 8.5, 'Yesid' => 12, 'Pachon' => 12, 'Brayan R' => 13.5, 'Brahian Q' => 7, 'Omar' => 10.5, 'Valbuena' => 12],
    6 => ['Luis Fdo M' => 12, 'Yesid' => 12, 'Pachon' => 12],
];

$totales = [];
foreach ($valores as $porEmpleado) {
    foreach ($porEmpleado as $clave => $v) {
        // Igual regla que "valor final" de la Bitacora: la corregida manda
        // sobre la reportada. En el shape [programada, corregida, comentario]
        // el numero que cuenta para el total es la corregida ($v[1]).
        $num = is_array($v) ? ($v[1] ?? null) : $v;
        $totales[$clave] = ($totales[$clave] ?? 0) + (is_numeric($num) ? (float) $num : 0);
    }
}

return ['valores' => $valores, 'totales' => $totales];
