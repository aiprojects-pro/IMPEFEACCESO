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

namespace local_impefeverif;

/**
 * Logica compartida por las tareas programadas.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /** @var string Area de ficheros de los envios de tipo archivo. */
    const FILEAREA = 'submission_files';

    /** @var string Componente del area de ficheros. */
    const COMPONENT = 'assignsubmission_file';

    /**
     * Devuelve un ajuste del plugin con valor por defecto.
     *
     * @param string $nombre
     * @param mixed $defecto
     * @return mixed
     */
    public static function ajuste(string $nombre, $defecto = null) {
        $valor = get_config('local_impefeverif', $nombre);
        if ($valor === false || $valor === '') {
            return $defecto;
        }
        return $valor;
    }

    /**
     * Convierte un ajuste de lista separada por comas en array de enteros.
     *
     * @param string $nombre
     * @return int[]
     */
    public static function lista_ids(string $nombre): array {
        $bruto = (string) self::ajuste($nombre, '');
        if (trim($bruto) === '') {
            return [];
        }
        $ids = array_map('intval', explode(',', $bruto));
        return array_values(array_filter($ids));
    }

    /**
     * Indica si el momento dado cae dentro del horario laboral configurado.
     *
     * Comprueba dia de la semana, franja horaria y calendario de festivos,
     * siempre en la zona horaria configurada (no la del servidor).
     *
     * @param int|null $momento Timestamp. Por defecto, ahora.
     * @return bool
     */
    public static function en_horario_laboral(?int $momento = null): bool {
        $momento = $momento ?? time();
        $zona = (string) self::ajuste('zonahoraria', 'Europe/Madrid');

        try {
            $tz = new \DateTimeZone($zona);
        } catch (\Exception $e) {
            $tz = new \DateTimeZone('Europe/Madrid');
        }

        $fecha = new \DateTime('@' . $momento);
        $fecha->setTimezone($tz);

        // Dia de la semana: 1 lunes ... 7 domingo.
        $dia = (int) $fecha->format('N');
        $diaspermitidos = array_map('intval', explode(',', (string) self::ajuste('diassemana', '1,2,3,4,5')));
        if (!in_array($dia, $diaspermitidos, true)) {
            return false;
        }

        // Festivos, uno por linea en formato AAAA-MM-DD.
        if (in_array($fecha->format('Y-m-d'), self::festivos(), true)) {
            return false;
        }

        $hora = (int) $fecha->format('G');
        $inicio = (int) self::ajuste('horainicio', 8);
        $fin = (int) self::ajuste('horafin', 15);

        return ($hora >= $inicio && $hora < $fin);
    }

    /**
     * Devuelve el calendario de festivos configurado.
     *
     * @return string[]
     */
    public static function festivos(): array {
        $bruto = (string) self::ajuste('festivos', '');
        if (trim($bruto) === '') {
            return [];
        }
        $lineas = preg_split('/\R/', $bruto);
        $salida = [];
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $linea)) {
                $salida[] = $linea;
            }
        }
        return $salida;
    }

    /**
     * Carga el trio cm, curso y contexto de una tarea a partir del cmid.
     *
     * @param int $cmid
     * @return array|null [cm, course, context] o null si no es una tarea valida.
     */
    public static function cargar_tarea(int $cmid): ?array {
        global $DB;

        try {
            $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
        } catch (\moodle_exception $e) {
            mtrace("  [aviso] cmid {$cmid} no corresponde a una tarea valida. Se omite.");
            return null;
        }

        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);

        return [$cm, $course, $context];
    }

    /**
     * Devuelve la via de acreditacion declarada por el usuario.
     *
     * @param int $userid
     * @return string
     */
    public static function via_declarada(int $userid): string {
        global $DB;

        $sql = "SELECT d.data
                  FROM {user_info_data} d
                  JOIN {user_info_field} f ON f.id = d.fieldid
                 WHERE d.userid = :userid AND f.shortname = :shortname";
        $via = $DB->get_field_sql($sql, ['userid' => $userid, 'shortname' => 'via_acreditacion']);

        return $via ? (string) $via : '';
    }

    /**
     * Devuelve la via asociada a una tarea de acreditacion configurada.
     *
     * @param int $cmid
     * @return string
     */
    public static function via_por_tarea(int $cmid): string {
        if ($cmid === 11469 || $cmid === 11498) {
            return 'Empadronamiento';
        }
        if ($cmid === 11495 || $cmid === 11499) {
            return 'Estudiante';
        }
        if ($cmid === 11496 || $cmid === 11500) {
            return 'Trabajador';
        }
        return '';
    }

    /**
     * Inserta o actualiza la fila de registro de un usuario en una tarea.
     *
     * @param \stdClass $datos Debe incluir userid y cmid.
     * @return int Id de la fila.
     */
    public static function registrar(\stdClass $datos): int {
        global $DB;

        $existente = $DB->get_record('local_impefeverif_reg', [
            'userid' => $datos->userid,
            'cmid' => $datos->cmid,
        ]);

        if ($existente) {
            $datos->id = $existente->id;
            $DB->update_record('local_impefeverif_reg', $datos);
            return (int) $existente->id;
        }

        return (int) $DB->insert_record('local_impefeverif_reg', $datos);
    }

    /**
     * Inspecciona los ficheros de un envio.
     *
     * @param int $contextid
     * @param int $submissionid
     * @return \stdClass numfiles, fileext, filesize, filehash, admitido
     */
    public static function inspeccionar_ficheros(int $contextid, int $submissionid): \stdClass {
        $fs = get_file_storage();
        $ficheros = $fs->get_area_files(
            $contextid,
            self::COMPONENT,
            self::FILEAREA,
            $submissionid,
            'id',
            false
        );

        $info = new \stdClass();
        $info->numfiles = count($ficheros);
        $info->fileext = '';
        $info->filesize = 0;
        $info->filehash = '';
        $info->admitido = false;

        if ($info->numfiles === 0) {
            return $info;
        }

        $permitidas = array_map(
            'trim',
            explode(',', strtolower((string) self::ajuste('extensiones', 'pdf,jpg,jpeg,png')))
        );

        $exts = [];
        $hashes = [];
        $todasadmitidas = true;

        foreach ($ficheros as $fichero) {
            $nombre = $fichero->get_filename();
            $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            $exts[] = $ext;
            $hashes[] = $fichero->get_contenthash();
            $info->filesize += (int) $fichero->get_filesize();
            if (!in_array($ext, $permitidas, true)) {
                $todasadmitidas = false;
            }
        }

        $info->fileext = implode(',', array_unique($exts));
        $info->filehash = substr(sha1(implode('|', $hashes)), 0, 40);
        $info->admitido = $todasadmitidas;

        return $info;
    }

    /**
     * Destruye los ficheros de un envio conservando la calificacion.
     *
     * @param int $contextid
     * @param int $submissionid
     * @return int Numero de ficheros eliminados.
     */
    public static function destruir_ficheros(int $contextid, int $submissionid): int {
        global $DB;

        $fs = get_file_storage();
        $ficheros = $fs->get_area_files(
            $contextid,
            self::COMPONENT,
            self::FILEAREA,
            $submissionid,
            'id',
            false
        );
        $total = count($ficheros);

        foreach ($ficheros as $fichero) {
            $fichero->delete();
        }

        // Mantener coherente el contador del subplugin de envio por archivo.
        $registro = $DB->get_record('assignsubmission_file', ['submission' => $submissionid]);
        if ($registro) {
            $registro->numfiles = 0;
            $DB->update_record('assignsubmission_file', $registro);
        }

        return $total;
    }

    /**
     * Devuelve la URL del catalogo de cursos que se ofrece al usuario verificado.
     *
     * @return string
     */
    public static function url_catalogo(): string {
        global $CFG;

        $configurada = trim((string) self::ajuste('urlcatalogo', ''));
        if ($configurada !== '') {
            return $configurada;
        }

        return $CFG->wwwroot . '/course/index.php';
    }

    /**
     * Notifica al usuario el resultado de la comprobacion.
     *
     * No invocar dentro de una transaccion de base de datos: message_send no lo
     * admite. Para eventos, encolar antes una tarea ad hoc.
     *
     * @param int $userid
     * @param string $asunto
     * @param string $cuerpo
     * @param string $proveedor Nombre del proveedor de mensajes.
     * @return void
     */
    public static function notificar(int $userid, string $asunto, string $cuerpo,
            string $proveedor = 'validacion'): void {
        $usuario = \core_user::get_user($userid);
        if (!$usuario || !empty($usuario->deleted) || !empty($usuario->suspended)) {
            return;
        }

        $mensaje = new \core\message\message();
        $mensaje->component = 'local_impefeverif';
        $mensaje->name = $proveedor;
        $mensaje->userfrom = \core_user::get_noreply_user();
        $mensaje->userto = $usuario;
        $mensaje->subject = $asunto;
        $mensaje->fullmessage = html_to_text($cuerpo);
        $mensaje->fullmessageformat = FORMAT_PLAIN;
        $mensaje->fullmessagehtml = $cuerpo;
        $mensaje->smallmessage = $asunto;
        $mensaje->notification = 1;

        try {
            message_send($mensaje);
        } catch (\Exception $e) {
            mtrace("  [aviso] no se pudo notificar al usuario {$userid}: " . $e->getMessage());
        }
    }

    /**
     * Avisa a la persona de que se ha quedado sin cupo, una vez por motivo y ventana.
     *
     * @param int $userid
     * @param \stdClass $evaluacion Resultado de cupo::evaluar().
     * @return void
     */
    public static function notificar_cupo_agotado(int $userid, \stdClass $evaluacion): void {
        $clave = 'local_impefeverif_avisocupo_' . $evaluacion->motivo;

        // No repetir el aviso mientras siga vigente la misma ventana.
        $previo = (int) get_user_preferences($clave, 0, $userid);
        if ($previo >= (int) $evaluacion->ventanainicio && $previo > 0) {
            return;
        }

        $a = new \stdClass();
        $a->maxanuales = $evaluacion->maxanuales;
        $a->maxsimultaneos = $evaluacion->maxsimultaneos;
        $a->encurso = $evaluacion->encurso;
        $a->renovacion = $evaluacion->ventanafin
            ? userdate($evaluacion->ventanafin, get_string('strftimedaydate', 'langconfig'))
            : '-';

        if ($evaluacion->motivo === 'cupoanual') {
            $asunto = get_string('avisocupoanual_asunto', 'local_impefeverif', $a);
            $cuerpo = get_string('avisocupoanual_cuerpo', 'local_impefeverif', $a);
        } else {
            $asunto = get_string('avisosimultaneos_asunto', 'local_impefeverif', $a);
            $cuerpo = get_string('avisosimultaneos_cuerpo', 'local_impefeverif', $a);
        }

        self::notificar($userid, $asunto, $cuerpo, 'cupo');

        set_user_preference($clave, max(1, (int) $evaluacion->ventanainicio), $userid);
    }
}
