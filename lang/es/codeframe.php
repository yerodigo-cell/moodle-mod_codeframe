<?php
/**
 * Spanish strings for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename']              = 'Codeframe';
$string['modulename_help']         = 'El módulo de actividad Codeframe permite a los profesores incrustar contenido externo, como presentaciones interactivas, mediante un iframe y rastrear automáticamente la finalización a través de window postMessages.';
$string['modulenameplural']        = 'Actividades Codeframe';
$string['pluginname']              = 'Codeframe';
$string['pluginadministration']    = 'Administración de Codeframe';
$string['codeframe:addinstance']   = 'Añadir una nueva actividad Codeframe';
$string['codeframe:view']          = 'Ver actividad Codeframe';
$string['embedcode']               = 'URL de la presentación / Código';
$string['embedcode_help']          = 'Pega la URL directa del contenido interactivo (ej. https://ejemplo.com/slide). El plugin generará automáticamente el código iframe. También puedes pegar el código iframe directamente.';
$string['uploadfiles']             = 'O subir paquete HTML5';
$string['uploadfiles_help']        = 'Sube una carpeta o un archivo zip que contenga tu contenido HTML5 (imágenes, JS, CSS, audio, etc.). El archivo principal debe llamarse "index.html" en el nivel raíz.';
$string['error_url_or_files']      = 'Debes proporcionar una URL o subir archivos de presentación HTML5.';
$string['nohtmlfile']              = 'Error: No se encontró ningún archivo HTML (como index.html) en los archivos subidos. Por favor, asegúrate de que haya un archivo HTML en la raíz de la carpeta subida.';
$string['completioncomplete']      = 'Requerir finalización del iframe';
$string['completioncomplete_help'] = 'Si está habilitado, el estudiante debe completar la actividad interactiva (el iframe debe enviar el mensaje "codeframe_completed") para marcar esta actividad de Moodle como completada.';
$string['completioninfo']          = '&#128274; <strong>Rastreo de finalización:</strong> Esta actividad se marcará como completada automáticamente cuando el estudiante <em>vea la página</em> Y el <em>contenido incrustado envíe la señal de finalización</em>. Ambas condiciones ya están pre-habilitadas abajo en la sección de "Finalización de actividad".<br><br><strong>Nota para creadores de contenido:</strong> Para que la calificación automática funcione, tu presentación interactiva o paquete HTML debe ejecutar el siguiente código JavaScript cuando consideres que el estudiante finalizó:<br><code>&lt;script&gt;window.parent.postMessage(\'codeframe_completed\', \'*\');&lt;/script&gt;</code><br><br><em>Si vas a pegar este código dentro de una presentación de <strong>Genially</strong> (usando la opción Insertar &gt; Otros), usa esta versión alterna para evitar que Genially bloquee el mensaje:</em><br><code>&lt;script&gt;window.top.postMessage(\'codeframe_completed\', \'*\');&lt;/script&gt;</code>';

// Report strings
$string['progressreport'] = 'Reporte de Progreso';
$string['student'] = 'Estudiante';
$string['status'] = 'Estado';
$string['completed'] = 'Completado';
$string['notcompleted'] = 'No completado';
$string['timecompleted'] = 'Fecha de finalización';
$string['nostudents'] = 'No hay estudiantes matriculados en este curso.';

// Privacy / GDPR strings
$string['privacy:metadata:codeframe_completion'] = 'Información sobre la finalización de las actividades interactivas por parte de los estudiantes.';
$string['privacy:metadata:codeframe_completion:userid'] = 'El ID del usuario que completó la actividad.';
$string['privacy:metadata:codeframe_completion:timecompleted'] = 'La marca de tiempo exacta en que el usuario completó la actividad.';
