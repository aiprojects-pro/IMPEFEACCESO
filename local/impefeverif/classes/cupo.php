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
 * Calculo del cupo de acciones formativas.
 *
 * Reglas vigentes:
 *  - Maximo 5 matriculas por ventana de 12 meses contada desde la primera
 *    matricula de la persona. Al cumplirse el aniversario la ventana se cierra
 *    y la siguiente matricula abre una ventana nueva.
 *  - Una matricula consume plaza aunque la persona abandone el curso.
 *  - Maximo 2 cursos simultaneos. Una plaza simultanea se libera al finalizar
 *    el curso.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cupo {

    /**
     * Evalua el cupo de una persona a partir de sus filas del libro de matriculas.
     *
     * @param array $filas Filas de local_impefeverif_matr ordenadas por timeenrolled ascendente.
     * @param int|null $momento Momento de referencia. Por defecto, ahora.
     * @return \stdClass ventanainicio, ventanafin, ventanaabierta, consumidas,
     *                   restantes, encurso, puedematricularse, motivo
     */
    public static function evaluar(array $filas, ?int $momento = null): \stdClass {
        $momento = $momento ?? time();

        $maxanuales = (int) helper::ajuste('maxanuales', 5);
        $maxsimultaneos = (int) helper::ajuste('maxsimultaneos', 2);
        $liberarconbaja = (bool) helper::ajuste('liberarconbaja', 0);
        $caducidad = (int) helper::ajuste('caducidadencurso', 0);

        // Recorrido cronologico para localizar la ventana vigente.
        $inicio = 0;
        $consumidas = 0;

        foreach ($filas as $fila) {
            $t = (int) $fila->timeenrolled;
            if ($inicio === 0 || $t >= self::aniversario($inicio)) {
                $inicio = $t;
                $consumidas = 0;
            }
            $consumidas++;
        }

        $r = new \stdClass();
        $r->ventanainicio = $inicio;
        $r->ventanafin = $inicio ? self::aniversario($inicio) : 0;

        if ($inicio === 0 || $momento >= $r->ventanafin) {
            // Sin matriculas, o ventana ya vencida: el cupo esta intacto y la
            // proxima matricula abrira una ventana nueva.
            $r->ventanaabierta = false;
            $r->consumidas = 0;
            $r->restantes = $maxanuales;
        } else {
            $r->ventanaabierta = true;
            $r->consumidas = $consumidas;
            $r->restantes = max(0, $maxanuales - $consumidas);
        }

        // Cursos en curso. Solo la finalizacion libera plaza, salvo que se
        // activen expresamente la baja o la caducidad en los ajustes.
        $encurso = 0;
        foreach ($filas as $fila) {
            if ((int) $fila->timecompleted > 0) {
                continue;
            }
            if ($liberarconbaja && (int) $fila->timeunenrolled > 0) {
                continue;
            }
            if ($caducidad > 0 && (int) $fila->timeenrolled < ($momento - ($caducidad * DAYSECS))) {
                continue;
            }
            $encurso++;
        }

        $r->encurso = $encurso;
        $r->maxsimultaneos = $maxsimultaneos;
        $r->maxanuales = $maxanuales;

        if ($r->restantes <= 0) {
            $r->puedematricularse = false;
            $r->motivo = 'cupoanual';
        } else if ($encurso >= $maxsimultaneos) {
            $r->puedematricularse = false;
            $r->motivo = 'simultaneos';
        } else {
            $r->puedematricularse = true;
            $r->motivo = '';
        }

        return $r;
    }

    /**
     * Devuelve el aniversario de un momento, respetando el calendario.
     *
     * @param int $momento
     * @return int
     */
    public static function aniversario(int $momento): int {
        $resultado = strtotime('+1 year', $momento);
        return $resultado !== false ? $resultado : ($momento + YEARSECS);
    }

    /**
     * Devuelve el libro de matriculas de un conjunto de usuarios, agrupado.
     *
     * @param int[] $userids
     * @return array userid => array de filas ordenadas por timeenrolled
     */
    public static function libro(array $userids): array {
        global $DB;

        if (empty($userids)) {
            return [];
        }

        [$ensql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');

        $filas = $DB->get_records_select(
            'local_impefeverif_matr',
            "userid {$ensql}",
            $params,
            'userid ASC, timeenrolled ASC',
            'id, userid, courseid, timeenrolled, timecompleted, timeunenrolled'
        );

        $agrupado = array_fill_keys($userids, []);
        foreach ($filas as $fila) {
            $agrupado[(int) $fila->userid][] = $fila;
        }

        return $agrupado;
    }

    /**
     * Evalua el cupo de una sola persona.
     *
     * @param int $userid
     * @return \stdClass
     */
    public static function evaluar_usuario(int $userid): \stdClass {
        $libro = self::libro([$userid]);
        return self::evaluar($libro[$userid] ?? []);
    }

    /**
     * Indica si un curso pertenece al catalogo sujeto a limites.
     *
     * @param int $courseid
     * @return bool
     */
    public static function es_del_catalogo(int $courseid): bool {
        global $DB, $SITE;

        if ($courseid <= 0 || $courseid == $SITE->id) {
            return false;
        }

        $categoriasraiz = helper::lista_ids('categoriacatalogo');
        if (empty($categoriasraiz)) {
            return false;
        }

        $curso = $DB->get_record('course', ['id' => $courseid], 'id, category');
        if (!$curso) {
            return false;
        }

        if (in_array((int) $curso->category, $categoriasraiz, true)) {
            return true;
        }

        // Comprobar subcategorias mediante la ruta de la categoria.
        $categoria = $DB->get_record('course_categories', ['id' => $curso->category], 'id, path');
        if (!$categoria) {
            return false;
        }

        $trozos = array_filter(explode('/', (string) $categoria->path));

        foreach ($categoriasraiz as $categoriaraiz) {
            if (in_array((string) $categoriaraiz, $trozos, true)) {
                return true;
            }
        }

        return false;
    }
}
