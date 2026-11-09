EMPRENDE CUSCO - PROYECTO ACTUALIZADO
=====================================

Esta versión fue revisada tomando como referencia el Plan de Trabajo de Prácticas
Preprofesionales y el proyecto entregado en el ZIP.

MÓDULOS PRINCIPALES
--------------------
1. Inicio
2. Ideas de Negocio
3. Empleos / Bolsa de Trabajo
4. Registro e inicio de sesión
5. Mi Perfil / Mi cuenta
6. Publicar Idea
7. Publicar Empleo
8. Panel de usuario
9. Gestión de base de datos MySQL
10. Diseño responsive

CAMBIOS REALIZADOS
------------------
- Se mantuvo la identidad visual roja institucional.
- ideas.php ahora está integrado con el header y footer del proyecto.
- El catálogo de ideas usa MySQL y permite búsqueda y filtros.
- Se amplió la ficha de cada idea: inversión, rentabilidad, cliente, modalidad,
  crecimiento, riesgos, ventajas, competencia, beneficios y primeros pasos.
- Se eliminó Recursos de la interfaz de ideas.
- Se integran ideas publicadas por usuarios desde ideas_negocio cuando tienen
  estado publicado/activo/aprobado.
- Se agregaron perfil.php y logout.php porque el header y panel los utilizaban.
- Se corrigió la referencia inexistente 2.jpg del inicio.
- Se agregó base_datos/mejora_ideas_emprende_cusco.sql.
- Se corrigió el título de las páginas para que sea contextual.

INSTALACIÓN LOCAL
-----------------
1. Extraer la carpeta "emprende_cusco_db" directamente dentro de C:\xampp2\htdocs\.
2. Iniciar Apache y MySQL en XAMPP.
3. Usar la base de datos emprende_cusco_db.
4. Si ya tienes la base de datos cargada, no elimines ninguna tabla.
5. Si necesitas preparar el módulo de ideas, importar:
   base_datos/mejora_ideas_emprende_cusco.sql
6. Abrir:
   http://localhost/emprende_cusco_db/emprende%20cusco/emprende%20cusco/index.php

IMPORTANTE
----------
El archivo config/conexion.php usa:
- host: localhost
- usuario: root
- contraseña: vacía
- base de datos: emprende_cusco_db

Si tu XAMPP usa otra contraseña o base de datos, modifica únicamente ese archivo.
