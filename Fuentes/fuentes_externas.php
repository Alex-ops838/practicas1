<?php
/**
 * ============================================================
 * EMPRENDE CUSCO
 * FUENTES EXTERNAS DE INFORMACIÓN
 * ============================================================
 *
 * Fuentes:
 * - Gobierno Regional del Cusco
 * - Municipalidad Provincial del Cusco
 * - CIP Consejo Departamental Cusco
 * - MINEDU
 * - UGEL Cusco
 * - GEREDU Cusco
 * - Talento Perú / SERVIR
 * - PROMPERÚ
 *
 * IMPORTANTE:
 * Este archivo NO modifica tu base de datos.
 * Solo proporciona enlaces y fuentes externas verificadas.
 * ============================================================
 */

if (!function_exists('fuentes_externas')) {

    function fuentes_externas()
    {
        return [

            // ==================================================
            // CUSCO - GOBIERNO REGIONAL
            // ==================================================

            [
                'id' => 'grc',
                'titulo' => 'Convocatorias del Gobierno Regional del Cusco',
                'descripcion' =>
                    'Consulta convocatorias laborales, CAS, reemplazos y otras oportunidades publicadas por el Gobierno Regional del Cusco.',
                'fuente' => 'Gobierno Regional del Cusco',
                'tipo' => 'Empleo',
                'ubicacion' => 'Cusco',
                'categoria' => 'Gobierno',
                'fecha' => '2026',
                'url' =>
                    'https://www.gob.pe/institucion/regioncusco/tema/convocatorias',
                'icono' => '🏛️',
                'color' => 'red'
            ],

            // ==================================================
            // MUNICIPALIDAD PROVINCIAL DEL CUSCO
            // ==================================================

            [
                'id' => 'mpc',
                'titulo' => 'Convocatorias CAS y 728 - Municipalidad del Cusco',
                'descripcion' =>
                    'Revisa las convocatorias laborales de la Municipalidad Provincial del Cusco, incluyendo procesos CAS y régimen 728.',
                'fuente' => 'Municipalidad Provincial del Cusco',
                'tipo' => 'Empleo',
                'ubicacion' => 'Cusco',
                'categoria' => 'Gobierno',
                'fecha' => '2026',
                'url' =>
                    'https://www.cusco.gob.pe/convocatorias-cas-y-728/',
                'icono' => '🏢',
                'color' => 'red'
            ],

            // ==================================================
            // CIP CUSCO
            // ==================================================

            [
                'id' => 'cip',
                'titulo' => 'Bolsa de Trabajo 2026 - CIP Cusco',
                'descripcion' =>
                    'Bolsa laboral con convocatorias de empresas, universidades, municipalidades, constructoras, consultoras y entidades de Cusco.',
                'fuente' => 'Colegio de Ingenieros del Perú - Consejo Departamental Cusco',
                'tipo' => 'Bolsa de Trabajo',
                'ubicacion' => 'Cusco',
                'categoria' => 'Ingeniería',
                'fecha' => '2026',
                'url' =>
                    'https://www.cipcusco.org.pe/servicios/bolsa-de-trabajo-2026/',
                'icono' => '👷',
                'color' => 'gold'
            ],

            // ==================================================
            // MINEDU
            // ==================================================

            [
                'id' => 'minedu',
                'titulo' => 'Admisión 2026-II - MINEDU',
                'descripcion' =>
                    'Información oficial sobre admisión a institutos y escuelas de educación superior pedagógica del Perú.',
                'fuente' => 'Ministerio de Educación',
                'tipo' => 'Educación',
                'ubicacion' => 'Perú',
                'categoria' => 'Educación',
                'fecha' => '2026-II',
                'url' =>
                    'https://www.minedu.gob.pe/superiortecnologica/admision-2026-2/',
                'icono' => '🎓',
                'color' => 'blue'
            ],

            // ==================================================
            // UGEL CUSCO
            // ==================================================

            [
                'id' => 'ugel',
                'titulo' => 'Convocatorias CAP - Decreto Legislativo 276',
                'descripcion' =>
                    'Convocatorias y procesos de selección de personal administrativo de la UGEL Cusco.',
                'fuente' => 'UGEL Cusco',
                'tipo' => 'Empleo',
                'ubicacion' => 'Cusco',
                'categoria' => 'Educación',
                'fecha' => '2026',
                'url' =>
                    'https://www.gob.pe/institucion/ugelcusco/colecciones/convocatorias-cap-dl-276-2026',
                'icono' => '📚',
                'color' => 'blue'
            ],

            // ==================================================
            // GEREDU CUSCO
            // ==================================================

            [
                'id' => 'geredu',
                'titulo' => 'Convocatorias GEREDU Cusco',
                'descripcion' =>
                    'Procesos CAS y convocatorias laborales de la Gerencia Regional de Educación del Cusco.',
                'fuente' => 'Gerencia Regional de Educación Cusco',
                'tipo' => 'Empleo',
                'ubicacion' => 'Cusco',
                'categoria' => 'Educación',
                'fecha' => '2026',
                'url' =>
                    'https://www.gereducusco.gob.pe/convocatorias-cas/',
                'icono' => '🏫',
                'color' => 'blue'
            ],

            // ==================================================
            // HOSPITAL REGIONAL
            // ==================================================

            [
                'id' => 'hospital',
                'titulo' => 'Convocatorias Laborales 2026',
                'descripcion' =>
                    'Consulta procesos de selección y convocatorias laborales del Hospital Regional del Cusco.',
                'fuente' => 'Hospital Regional del Cusco',
                'tipo' => 'Empleo',
                'ubicacion' => 'Cusco',
                'categoria' => 'Salud',
                'fecha' => '2026',
                'url' =>
                    'https://www.hospitalregionalcusco.gob.pe/convocatorias-laborales/',
                'icono' => '🏥',
                'color' => 'green'
            ],

            // ==================================================
            // TALENTO PERÚ
            // ==================================================

            [
                'id' => 'talento',
                'titulo' => 'Talento Perú - Empleos y Prácticas',
                'descripcion' =>
                    'Portal oficial de SERVIR para encontrar empleos y prácticas en instituciones públicas de todo el Perú.',
                'fuente' => 'SERVIR - Talento Perú',
                'tipo' => 'Empleo Nacional',
                'ubicacion' => 'Perú',
                'categoria' => 'Empleo',
                'fecha' => '2026',
                'url' =>
                    'https://www.gob.pe/talento-peru',
                'icono' => '🇵🇪',
                'color' => 'red'
            ],

            // ==================================================
            // PROMPERÚ
            // ==================================================

            [
                'id' => 'promperu',
                'titulo' => 'Oportunidades de negocio - PROMPERÚ',
                'descripcion' =>
                    'Información para empresas y emprendedores sobre sectores productivos, exportaciones, turismo, alimentos, manufacturas y servicios.',
                'fuente' => 'PROMPERÚ',
                'tipo' => 'Emprendimiento',
                'ubicacion' => 'Perú',
                'categoria' => 'Negocios',
                'fecha' => '2026',
                'url' =>
                    'https://exportemos.pe/',
                'icono' => '🚀',
                'color' => 'gold'
            ],

            // ==================================================
            // SERVICIOS BASADOS EN CONOCIMIENTO
            // ==================================================

            [
                'id' => 'software',
                'titulo' => 'Software y servicios tecnológicos',
                'descripcion' =>
                    'Información sobre oportunidades del sector de software y servicios basados en conocimiento.',
                'fuente' => 'PROMPERÚ',
                'tipo' => 'Emprendimiento',
                'ubicacion' => 'Perú',
                'categoria' => 'Tecnología',
                'fecha' => '2026',
                'url' =>
                    'https://exportemos.pe/sectores/software',
                'icono' => '💻',
                'color' => 'blue'
            ],

            // ==================================================
            // TURISMO
            // ==================================================

            [
                'id' => 'turismo',
                'titulo' => 'Oportunidades del sector turismo',
                'descripcion' =>
                    'Información y oportunidades relacionadas con turismo, servicios y actividades económicas.',
                'fuente' => 'PROMPERÚ',
                'tipo' => 'Emprendimiento',
                'ubicacion' => 'Perú',
                'categoria' => 'Turismo',
                'fecha' => '2026',
                'url' =>
                    'https://exportemos.pe/',
                'icono' => '🏔️',
                'color' => 'green'
            ],

            // ==================================================
            // ALIMENTOS Y BEBIDAS
            // ==================================================

            [
                'id' => 'alimentos',
                'titulo' => 'Alimentos y bebidas',
                'descripcion' =>
                    'Información sobre oportunidades comerciales y productivas para alimentos y bebidas.',
                'fuente' => 'PROMPERÚ',
                'tipo' => 'Emprendimiento',
                'ubicacion' => 'Perú',
                'categoria' => 'Alimentos',
                'fecha' => '2026',
                'url' =>
                    'https://exportemos.pe/',
                'icono' => '🍽️',
                'color' => 'gold'
            ],

            // ==================================================
            // ENERGÍAS RENOVABLES
            // ==================================================

            [
                'id' => 'energia',
                'titulo' => 'Energías renovables',
                'descripcion' =>
                    'Información sobre oportunidades y sectores vinculados con energías renovables.',
                'fuente' => 'PROMPERÚ',
                'tipo' => 'Emprendimiento',
                'ubicacion' => 'Perú',
                'categoria' => 'Medio Ambiente',
                'fecha' => '2026',
                'url' =>
                    'https://investperu.peru.info/',
                'icono' => '🌱',
                'color' => 'green'
            ],

            // ==================================================
            // MINERÍA / PROVEEDORES
            // ==================================================

            [
                'id' => 'mineria',
                'titulo' => 'Oportunidades para proveedores mineros',
                'descripcion' =>
                    'Información comercial y empresarial relacionada con proveedores y servicios para minería.',
                'fuente' => 'PROMPERÚ',
                'tipo' => 'Negocios',
                'ubicacion' => 'Perú',
                'categoria' => 'Minería',
                'fecha' => '2026',
                'url' =>
                    'https://exportemos.pe/',
                'icono' => '⛏️',
                'color' => 'gold'
            ]

        ];
    }
}


/**
 * ============================================================
 * BUSCADOR DE FUENTES
 * ============================================================
 */
if (!function_exists('buscar_fuentes_externas')) {

    function buscar_fuentes_externas($texto = '', $ubicacion = '')
    {
        $fuentes = fuentes_externas();

        $texto = trim(mb_strtolower($texto));
        $ubicacion = trim(mb_strtolower($ubicacion));

        if ($texto === '' && $ubicacion === '') {
            return $fuentes;
        }

        $resultado = [];

        foreach ($fuentes as $fuente) {

            $contenido = mb_strtolower(
                $fuente['titulo'] . ' ' .
                $fuente['descripcion'] . ' ' .
                $fuente['fuente'] . ' ' .
                $fuente['tipo'] . ' ' .
                $fuente['categoria'] . ' ' .
                $fuente['ubicacion']
            );

            $coincideTexto =
                $texto === '' ||
                mb_strpos($contenido, $texto) !== false;

            $coincideUbicacion =
                $ubicacion === '' ||
                mb_strpos(
                    mb_strtolower($fuente['ubicacion']),
                    $ubicacion
                ) !== false;

            if ($coincideTexto && $coincideUbicacion) {
                $resultado[] = $fuente;
            }
        }

        return $resultado;
    }
}