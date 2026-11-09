<?php
include __DIR__ . '/includes/header.php';
?>

<style>
/* =========================================================
   VARIABLES
========================================================= */
:root {
    --rojo: #a90000;
    --rojo-oscuro: #790000;
    --dorado: #f6c445;
    --texto: #252525;
    --gris: #666;
    --gris-claro: #f5f6f8;
    --borde: #e5e5e5;
    --blanco: #ffffff;
}

/* =========================================================
   GENERAL
========================================================= */
.empleos-page {
    width: 100%;
    overflow: hidden;
    background: #f5f6f8;
    color: var(--texto);
}
.empleos-page * {
    box-sizing: border-box;
}

/* =========================================================
   CABECERA
========================================================= */
.cabecera-empleos {
    background: linear-gradient(100deg, #650000 0%, #850000 50%, #a9002d 100%);
    color: white;
    padding: 45px 5%;
    text-align: center;
}
.cabecera-empleos h1 {
    margin: 0 0 10px;
    font-size: clamp(28px, 4vw, 40px);
    font-weight: 900;
    letter-spacing: -1px;
}
.cabecera-empleos h1 span {
    color: var(--dorado);
}
.cabecera-empleos p {
    max-width: 700px;
    margin: 0 auto;
    font-size: 14px;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.9);
}

/* =========================================================
   CONTENEDOR
========================================================= */
.contenedor {
    width: 1000px;
    max-width: 92%;
    margin: 40px auto;
}

/* =========================================================
   BLOQUE INFO
========================================================= */
.bloque {
    background: white;
    border: 1px solid var(--borde);
    border-radius: 14px;
    padding: 30px;
    margin-bottom: 22px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
}
.bloque h2 {
    margin: 0 0 8px;
    font-size: 22px;
    color: var(--texto);
    display: flex;
    align-items: center;
    gap: 10px;
}
.bloque h2 i {
    color: var(--rojo);
    font-size: 20px;
}
.bloque > p {
    margin: 0 0 20px;
    color: #777;
    font-size: 13px;
    line-height: 1.6;
}

/* =========================================================
   FAQ - PREGUNTAS Y RESPUESTAS
========================================================= */
.faq {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.faq-item {
    border: 1px solid var(--borde);
    border-radius: 10px;
    overflow: hidden;
    transition: 0.2s;
}
.faq-item:hover {
    border-color: #d9a1a1;
}
.faq-item input {
    display: none;
}
.faq-pregunta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 15px 18px;
    background: #fafafa;
    cursor: pointer;
    font-size: 14px;
    font-weight: 700;
    color: var(--texto);
    transition: 0.2s;
}
.faq-pregunta:hover {
    background: #fff1f1;
    color: var(--rojo);
}
.faq-pregunta i {
    color: var(--rojo);
    transition: 0.3s;
    flex-shrink: 0;
}
.faq-respuesta {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    background: white;
}
.faq-respuesta-contenido {
    padding: 15px 18px;
    font-size: 13px;
    line-height: 1.7;
    color: #555;
    border-top: 1px solid var(--borde);
}
.faq-respuesta-contenido ul {
    margin: 8px 0 0;
    padding-left: 20px;
}
.faq-respuesta-contenido li {
    margin-bottom: 5px;
}
.faq-item input:checked ~ .faq-respuesta {
    max-height: 600px;
}
.faq-item input:checked ~ .faq-pregunta i {
    transform: rotate(180deg);
}
.faq-item input:checked ~ .faq-pregunta {
    background: #fff1f1;
    color: var(--rojo);
}

/* =========================================================
   GRID DATOS
========================================================= */
.grid-datos {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 8px;
}
.dato {
    background: #f9f9fa;
    border: 1px solid var(--borde);
    border-radius: 10px;
    padding: 18px;
    text-align: center;
}
.dato i {
    color: var(--rojo);
    font-size: 22px;
    margin-bottom: 8px;
    display: block;
}
.dato strong {
    display: block;
    font-size: 22px;
    color: var(--rojo);
    margin-bottom: 4px;
}
.dato span {
    font-size: 11px;
    color: #777;
    line-height: 1.4;
}

/* =========================================================
   LISTA TIPOS
========================================================= */
.tipos-lista {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-top: 8px;
}
.tipo-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    background: #f9f9fa;
    border: 1px solid var(--borde);
    border-radius: 10px;
}
.tipo-item i {
    color: var(--rojo);
    font-size: 18px;
    margin-top: 2px;
    flex-shrink: 0;
}
.tipo-item strong {
    display: block;
    font-size: 13px;
    margin-bottom: 3px;
    color: var(--texto);
}
.tipo-item span {
    font-size: 11px;
    color: #777;
    line-height: 1.4;
}

/* =========================================================
   ENLACE FINAL
========================================================= */
.enlace-final {
    background: linear-gradient(100deg, #790000, #a90000);
    border-radius: 14px;
    padding: 28px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    color: white;
    box-shadow: 0 6px 20px rgba(169, 0, 0, 0.15);
    margin-bottom: 20px;
}
.enlace-final-info {
    display: flex;
    align-items: center;
    gap: 16px;
}
.enlace-final-icono {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}
.enlace-final-info strong {
    display: block;
    font-size: 16px;
    margin-bottom: 4px;
}
.enlace-final-info p {
    margin: 0;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.4;
}
.enlace-final-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 22px;
    background: white;
    color: var(--rojo);
    text-decoration: none;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 800;
    white-space: nowrap;
    transition: 0.2s;
    flex-shrink: 0;
}
.enlace-final-btn:hover {
    background: #f6f6f6;
    transform: translateY(-2px);
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 750px) {
    .cabecera-empleos {
        padding: 35px 5%;
    }
    .bloque {
        padding: 22px 18px;
    }
    .grid-datos {
        grid-template-columns: 1fr;
    }
    .tipos-lista {
        grid-template-columns: 1fr;
    }
    .enlace-final {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    .enlace-final-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="empleos-page">

    <!-- =====================================================
         CABECERA
    ===================================================== -->
    <section class="cabecera-empleos">
        <h1>El Empleo en <span>Cusco</span></h1>
        <p>
            Toda la información que necesitas saber sobre el empleo en la región Cusco:
            qué es, cuántos hay, qué tipos existen y cómo funciona el mercado laboral.
        </p>
    </section>

    <!-- =====================================================
         CONTENEDOR
    ===================================================== -->
    <div class="contenedor">

        <!-- =============================================
             BLOQUE 1: ¿QUÉ ES UN EMPLEO?
        ============================================= -->
        <div class="bloque">
            <h2><i class="fas fa-briefcase"></i> ¿Qué es un empleo?</h2>
            <p>Conceptos básicos para entender el mundo laboral en Cusco.</p>

            <div class="faq">

                <div class="faq-item">
                    <input type="checkbox" id="faq1">
                    <label for="faq1" class="faq-pregunta">
                        ¿Qué es un empleo?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            Un empleo es una actividad laboral que una persona realiza a cambio de una
                            remuneración económica. Es la principal fuente de ingresos para la mayoría
                            de las familias cusqueñas y puede ser formal o informal, dependiendo de si
                            cumple con las leyes laborales del Perú.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <input type="checkbox" id="faq2">
                    <label for="faq2" class="faq-pregunta">
                        ¿Cuál es la diferencia entre empleo y trabajo?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            Aunque suelen usarse como sinónimos, <strong>trabajo</strong> es toda actividad
                            física o intelectual que realiza una persona, mientras que <strong>empleo</strong>
                            es un trabajo formal, con contrato, horario y remuneración establecida.
                            En Cusco, muchas personas tienen trabajo pero no un empleo formal.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <input type="checkbox" id="faq3">
                    <label for="faq3" class="faq-pregunta">
                        ¿Qué es la PEA en Cusco?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            La <strong>PEA</strong> (Población Económicamente Activa) es el conjunto de
                            personas en edad de trabajar (14 años a más) que están trabajando o buscando
                            trabajo. En Cusco, la PEA supera el medio millón de personas, y una gran parte
                            se dedica al comercio, turismo, agricultura y servicios.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <input type="checkbox" id="faq4">
                    <label for="faq4" class="faq-pregunta">
                        ¿Qué es el subempleo?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            El subempleo ocurre cuando una persona trabaja menos horas de las que
                            desearía, gana menos del sueldo mínimo, o realiza un trabajo para el cual
                            está sobrecalificada. En Cusco es común en el sector turismo durante la
                            temporada baja.
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- =============================================
             BLOQUE 2: CIFRAS DEL EMPLEO EN CUSCO
        ============================================= -->
        <div class="bloque">
            <h2><i class="fas fa-chart-simple"></i> Cifras del empleo en Cusco</h2>
            <p>Datos aproximados del mercado laboral cusqueño.</p>

            <div class="grid-datos">
                <div class="dato">
                    <i class="fas fa-users"></i>
                    <strong>+500,000</strong>
                    <span>Personas en edad de trabajar en Cusco</span>
                </div>
                <div class="dato">
                    <i class="fas fa-briefcase"></i>
                    <strong>+250,000</strong>
                    <span>Empleos formales e informales en la región</span>
                </div>
                <div class="dato">
                    <i class="fas fa-percent"></i>
                    <strong>~70%</strong>
                    <span>De la PEA trabaja en el sector informal</span>
                </div>
                <div class="dato">
                    <i class="fas fa-hotel"></i>
                    <strong>35%</strong>
                    <span>Del empleo se concentra en turismo y comercio</span>
                </div>
                <div class="dato">
                    <i class="fas fa-tractor"></i>
                    <strong>25%</strong>
                    <span>Se dedica a agricultura y ganadería</span>
                </div>
                <div class="dato">
                    <i class="fas fa-building"></i>
                    <strong>15%</strong>
                    <span>Trabaja en servicios, construcción y minería</span>
                </div>
            </div>
        </div>

        <!-- =============================================
             BLOQUE 3: TIPOS DE EMPLEO
        ============================================= -->
        <div class="bloque">
            <h2><i class="fas fa-layer-group"></i> Tipos de empleo en Cusco</h2>
            <p>Los sectores que concentran la mayor cantidad de puestos laborales.</p>

            <div class="tipos-lista">
                <div class="tipo-item">
                    <i class="fas fa-hotel"></i>
                    <div>
                        <strong>Turismo y hotelería</strong>
                        <span>Guías, recepcionistas, cocineros, agencias de viaje.</span>
                    </div>
                </div>
                <div class="tipo-item">
                    <i class="fas fa-store"></i>
                    <div>
                        <strong>Comercio</strong>
                        <span>Vendedores, cajeros, atención al cliente, mercados.</span>
                    </div>
                </div>
                <div class="tipo-item">
                    <i class="fas fa-tractor"></i>
                    <div>
                        <strong>Agricultura y ganadería</strong>
                        <span>Producción de papa, maíz, quinua, crianza de animales.</span>
                    </div>
                </div>
                <div class="tipo-item">
                    <i class="fas fa-helmet-safety"></i>
                    <div>
                        <strong>Construcción y minería</strong>
                        <span>Albañiles, operarios, ingenieros, personal técnico.</span>
                    </div>
                </div>
                <div class="tipo-item">
                    <i class="fas fa-graduation-cap"></i>
                    <div>
                        <strong>Educación y salud</strong>
                        <span>Docentes, enfermeros, médicos, personal administrativo.</span>
                    </div>
                </div>
                <div class="tipo-item">
                    <i class="fas fa-laptop-code"></i>
                    <div>
                        <strong>Tecnología y servicios</strong>
                        <span>Diseño, programación, marketing, contabilidad, transporte.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- =============================================
             BLOQUE 4: MODALIDADES DE TRABAJO
        ============================================= -->
        <div class="bloque">
            <h2><i class="fas fa-house-laptop"></i> Modalidades de trabajo</h2>
            <p>Formas en que se puede desempeñar un empleo en la actualidad.</p>

            <div class="faq">

                <div class="faq-item">
                    <input type="checkbox" id="faq5">
                    <label for="faq5" class="faq-pregunta">
                        ¿Qué es el trabajo presencial?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            Es el trabajo que se realiza en las instalaciones de la empresa o negocio.
                            Es la modalidad más común en Cusco, sobre todo en turismo, comercio y
                            construcción.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <input type="checkbox" id="faq6">
                    <label for="faq6" class="faq-pregunta">
                        ¿Qué es el trabajo remoto?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            Es el trabajo que se realiza desde casa o cualquier otro lugar fuera de
                            la oficina, usando internet. En Cusco ha crecido en áreas como diseño,
                            programación, marketing digital y atención al cliente.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <input type="checkbox" id="faq7">
                    <label for="faq7" class="faq-pregunta">
                        ¿Qué es el trabajo híbrido?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            Combina días de trabajo presencial en la oficina con días de trabajo
                            remoto desde casa. Es una modalidad cada vez más frecuente en empresas
                            modernas de Cusco.
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- =============================================
             BLOQUE 5: PREGUNTAS FRECUENTES
        ============================================= -->
        <div class="bloque">
            <h2><i class="fas fa-circle-question"></i> Preguntas frecuentes</h2>
            <p>Dudas comunes sobre el empleo en Cusco.</p>

            <div class="faq">

                <div class="faq-item">
                    <input type="checkbox" id="faq8">
                    <label for="faq8" class="faq-pregunta">
                        ¿Cuál es el sueldo mínimo en Cusco?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            La Remuneración Mínima Vital (RMV) en Perú es de <strong>S/ 1,130</strong>
                            mensuales, y aplica para todos los trabajadores formales del país, incluido
                            Cusco. Sin embargo, en el sector informal muchos trabajadores ganan menos.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <input type="checkbox" id="faq9">
                    <label for="faq9" class="faq-pregunta">
                        ¿Qué carreras tienen más salida laboral en Cusco?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            Las carreras con mayor demanda en Cusco son:
                            <ul>
                                <li>Turismo y hotelería</li>
                                <li>Administración de empresas</li>
                                <li>Contabilidad</li>
                                <li>Ingeniería civil y minas</li>
                                <li>Educación y salud</li>
                                <li>Diseño y tecnología</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <input type="checkbox" id="faq10">
                    <label for="faq10" class="faq-pregunta">
                        ¿Cuáles son los distritos con más empleo en Cusco?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            Los distritos con mayor concentración de empleo son:
                            <ul>
                                <li><strong>Cusco</strong> (centro histórico y comercio)</li>
                                <li><strong>Wanchaq</strong> (servicios y oficinas)</li>
                                <li><strong>San Sebastián</strong> (comercio y vivienda)</li>
                                <li><strong>San Jerónimo</strong> (industria y servicios)</li>
                                <li><strong>Santiago</strong> (comercio popular)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <input type="checkbox" id="faq11">
                    <label for="faq11" class="faq-pregunta">
                        ¿Qué documentos necesito para trabajar formalmente?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            Para trabajar formalmente en Cusco necesitas:
                            <ul>
                                <li>DNI vigente</li>
                                <li>Currículum vitae (CV)</li>
                                <li>Certificados de estudios</li>
                                <li>Contrato de trabajo firmado</li>
                                <li>Número de RUC (si eres independiente)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <input type="checkbox" id="faq12">
                    <label for="faq12" class="faq-pregunta">
                        ¿Qué es el currículum vitae y por qué es importante?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            El <strong>CV</strong> es un documento que resume tu formación académica,
                            experiencia laboral y habilidades. Es la principal herramienta para
                            postular a un empleo, ya que permite a la empresa conocer tu perfil
                            antes de una entrevista.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <input type="checkbox" id="faq13">
                    <label for="faq13" class="faq-pregunta">
                        ¿Cómo prepararse para una entrevista de trabajo?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            Algunos consejos clave:
                            <ul>
                                <li>Investiga sobre la empresa o negocio</li>
                                <li>Practica tus respuestas a preguntas comunes</li>
                                <li>Viste de manera formal y puntual</li>
                                <li>Lleva tu CV impreso y documentos</li>
                                <li>Muestra actitud positiva y disposición</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <input type="checkbox" id="faq14">
                    <label for="faq14" class="faq-pregunta">
                        ¿Qué es el trabajo informal?
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="faq-respuesta">
                        <div class="faq-respuesta-contenido">
                            El trabajo informal es aquel que no cumple con las leyes laborales: no hay
                            contrato, no hay seguro de salud, no hay beneficios sociales ni aportes a
                            la jubilación. En Cusco, más del 70% de la población trabaja en la
                            informalidad, especialmente en comercio y agricultura.
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- =============================================
             ENLACE FINAL
        ============================================= -->
        <section class="enlace-final">
            <div class="enlace-final-info">
                <div class="enlace-final-icono">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div>
                    <strong>Más información sobre empleos en Cusco</strong>
                    <p>Visita Convocatorias de Trabajo y consulta las oportunidades disponibles en la región.</p>
                </div>
            </div>
            <a href="https://www.convocatoriasdetrabajo.com/buscar-trabajos-en-CUSCO-7.html"
               target="_blank"
               rel="noopener noreferrer"
               class="enlace-final-btn">
                <i class="fas fa-external-link-alt"></i> Ver convocatorias
            </a>
        </section>

    </div>

</div>

<?php
include __DIR__ . '/includes/footer.php';
?>