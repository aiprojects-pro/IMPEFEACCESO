<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cadenas de idioma.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Verificación de requisitos de acceso';
$string['privacy:metadata'] = 'El plugin conserva, por cada acreditación comprobada, el usuario, la vía declarada, las fechas de envío y validación, y la huella criptográfica del documento. No conserva el documento ni su contenido una vez transcurrido el plazo de conservación.';

$string['task_autovalidar'] = 'Revisar acreditaciones de estudiante y trabajador';
$string['task_purgar'] = 'Revisar documentación acreditativa vencida';
$string['task_avisaracceso'] = 'Avisar al usuario de que su cuenta está validada';

$string['cohorteid'] = 'Cohorte de usuarios verificados';
$string['cohorteid_desc'] = 'Identificador de la cohorte «Verificados». Al incorporarse un usuario a esta cohorte se le envía el aviso de que ya puede matricularse. Vale igual para las vías de estudiante y trabajador y para el empadronamiento que revisa IMPEFE. El id aparece en la URL de edición de la cohorte.';

$string['urlcatalogo'] = 'URL del catálogo de cursos';
$string['urlcatalogo_desc'] = 'Dirección que se ofrece al usuario en el aviso de acceso. Si se deja vacía se usa la portada de cursos del sitio. Conviene apuntarla a la categoría del catálogo de cursos gratuitos.';

$string['activo'] = 'Activar la revisión de acreditaciones';
$string['activo_desc'] = 'Mientras esté desactivado, la tarea programada se ejecuta pero no califica nada. Déjalo desactivado hasta haber probado el circuito completo en preproducción.';

$string['cmidsauto'] = 'Tareas de revisión de estudiante y trabajador';
$string['cmidsauto_desc'] = 'Identificadores de módulo (cmid) de las tareas de Estudiante y Trabajador, separados por comas. El cmid aparece en la URL de la tarea.';

$string['cmidsmanual'] = 'Tareas de revisión humana';
$string['cmidsmanual_desc'] = 'Identificadores de módulo de las tareas que revisa una persona, separados por comas. Normalmente Empadronamiento. Estas tareas no se validan solas, pero sí entran en la purga.';

$string['horasespera'] = 'Horas de espera antes de revisar';
$string['horasespera_desc'] = 'Horas que deben transcurrir desde el envío antes de consignar la revisión.';

$string['horainicio'] = 'Hora de inicio del horario laboral';
$string['horainicio_desc'] = 'Hora en formato 24 h a partir de la cual se consignan revisiones.';

$string['horafin'] = 'Hora de fin del horario laboral';
$string['horafin_desc'] = 'Hora en formato 24 h a partir de la cual dejan de consignarse revisiones.';

$string['diassemana'] = 'Días laborables';
$string['diassemana_desc'] = 'Días de la semana en los que se consignan revisiones, separados por comas. 1 es lunes y 7 domingo.';

$string['festivos'] = 'Calendario de festivos';
$string['festivos_desc'] = 'Un festivo por línea, en formato AAAA-MM-DD. Incluye los festivos nacionales, autonómicos y locales de Ciudad Real.';

$string['zonahoraria'] = 'Zona horaria de referencia';
$string['zonahoraria_desc'] = 'Zona horaria con la que se interpreta el horario laboral, independientemente de la del servidor.';

$string['notaapto'] = 'Valor de la calificación Apto';
$string['notaapto_desc'] = 'Si la tarea usa una escala, indica la posición del valor Apto (con la escala «No apto, Apto», el valor es 2). Si usa puntuación numérica, indica la nota a consignar.';

$string['graderid'] = 'Cuenta calificadora';
$string['graderid_desc'] = 'Identificador de la cuenta con la que se consigna la calificación. Crea una cuenta específica para las revisiones de estudiante y trabajador.';

$string['extensiones'] = 'Extensiones admitidas';
$string['extensiones_desc'] = 'Extensiones de archivo consideradas válidas, separadas por comas. Un envío con cualquier archivo fuera de esta lista no se valida.';

$string['diasretencion'] = 'Días de conservación';
$string['diasretencion_desc'] = 'Días que se conserva el documento desde su validación. Transcurrido el plazo, se conserva únicamente el registro de la comprobación.';

$string['purgaactiva'] = 'Activar la gestión del plazo de conservación';
$string['purgaactiva_desc'] = 'Mientras esté desactivado, la tarea programada se ejecuta sin modificar documentos. Actívalo solo cuando IMPEFE haya firmado la instrucción del plazo de conservación.';

$string['messageprovider:validacion'] = 'Incidencias en la verificación de requisitos de acceso';
$string['messageprovider:acceso'] = 'Cuenta verificada y acceso al catálogo de cursos';

$string['avisoacceso_asunto'] = 'Documentación validada — acceso a los cursos habilitado';
$string['avisoacceso_cuerpo'] = '<p>Estimado/a {$a->nombre}:</p>
<p>A partir de este momento dispone de acceso a los cursos, a los que puede acceder desde el siguiente enlace:<br />
<a href="{$a->url}">{$a->url}</a></p>
<p>Para cualquier incidencia relacionada con el acceso, puede responder a este mensaje.</p>
<p>Atentamente,<br />IMPEFE</p>';

$string['avisoincompleto_asunto'] = 'Documentación no validada. Nueva aportación requerida';
$string['avisoincompleto_cuerpo'] = 'Estimado/a {$a->nombre}:

Le informamos de que la documentación aportada para acreditar su vinculación con Ciudad Real no ha podido ser validada.

Le rogamos que aporte de nuevo la documentación correspondiente desde la misma actividad.

Una vez recibida, procederemos a su revisión en el plazo indicado.

Atentamente,
IMPEFE';

$string['task_recalcularcupos'] = 'Recalcular el cupo de acciones formativas';

$string['cohortecupoid'] = 'Cohorte de personas con cupo disponible';
$string['cohortecupoid_desc'] = 'Identificador de la cohorte «Con cupo disponible». Debe ser distinta de la de verificados. Restringe la automatrícula de cada curso del catálogo a los miembros de esta cohorte: así el botón de matricularse se apaga solo cuando la persona agota el cupo, y vuelve cuando finaliza un curso.';

$string['categoriacatalogo'] = 'Categoría del catálogo';
$string['categoriacatalogo_desc'] = 'Identificadores de las categorías que contienen los cursos sujetos a límite, separados por comas, incluidas sus subcategorías. Las matrículas fuera de estas categorías no consumen cupo, de modo que el curso de verificación no cuenta.';

$string['maxanuales'] = 'Máximo de acciones formativas por ventana';
$string['maxanuales_desc'] = 'Número máximo de matrículas en la ventana de 12 meses. La ventana se cuenta desde la primera matrícula de la persona y se renueva en su aniversario.';

$string['maxsimultaneos'] = 'Máximo de cursos simultáneos';
$string['maxsimultaneos_desc'] = 'Número máximo de cursos que una persona puede tener en curso al mismo tiempo.';

$string['liberarconbaja'] = 'La baja libera plaza simultánea';
$string['liberarconbaja_desc'] = 'Desactivado, solo la finalización del curso libera una de las plazas simultáneas, conforme a la regla acordada con IMPEFE. Activarlo permite que dar de baja a una persona le devuelva la plaza, lo que sirve de válvula de escape si alguien se queda bloqueado por un curso que abandonó. En ningún caso devuelve la plaza anual.';

$string['caducidadencurso'] = 'Caducidad de los cursos sin finalizar (días)';
$string['caducidadencurso_desc'] = 'Cero desactiva la caducidad. Con un valor mayor que cero, un curso matriculado hace más de esos días deja de ocupar plaza simultánea aunque no se haya finalizado. Es la segunda válvula de escape contra los abandonos, desactivada por defecto.';

$string['avisocupoanual_asunto'] = 'Has agotado tus acciones formativas anuales';
$string['avisocupoanual_cuerpo'] = 'Has completado el máximo de {$a->maxanuales} acciones formativas de tu periodo actual, por lo que de momento no puedes matricularte en cursos nuevos.

Tu cupo se renueva el {$a->renovacion}. A partir de esa fecha podrás volver a matricularte.

Los cursos en los que ya estás matriculado siguen disponibles con normalidad.';

$string['avisosimultaneos_asunto'] = 'Ya tienes el máximo de cursos en marcha';
$string['avisosimultaneos_cuerpo'] = 'Ahora mismo tienes {$a->encurso} curso(s) sin finalizar, y el máximo permitido a la vez es de {$a->maxsimultaneos}.

Para matricularte en un curso nuevo tendrás que finalizar antes uno de los que tienes en marcha. En cuanto lo hagas, podrás matricularte de nuevo sin esperar.

Recuerda que esto no afecta a tu cupo anual, que sigue igual.';
