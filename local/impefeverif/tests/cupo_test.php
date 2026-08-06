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
 * Pruebas del calculo de cupo.
 *
 * Ejecutar con:
 *   vendor/bin/phpunit local/impefeverif/tests/cupo_test.php
 *
 * @package    local_impefeverif
 * @covers     \local_impefeverif\cupo
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cupo_test extends \advanced_testcase {

    /**
     * Configuracion acordada con IMPEFE.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        set_config('maxanuales', 5, 'local_impefeverif');
        set_config('maxsimultaneos', 2, 'local_impefeverif');
        set_config('liberarconbaja', 0, 'local_impefeverif');
        set_config('caducidadencurso', 0, 'local_impefeverif');
    }

    /**
     * Construye una fila del libro de matriculas.
     *
     * @param string $matricula Fecha en formato aceptado por strtotime.
     * @param string $finalizacion
     * @param string $baja
     * @return \stdClass
     */
    protected function fila(string $matricula, string $finalizacion = '', string $baja = ''): \stdClass {
        $fila = new \stdClass();
        $fila->timeenrolled = strtotime($matricula);
        $fila->timecompleted = $finalizacion ? strtotime($finalizacion) : 0;
        $fila->timeunenrolled = $baja ? strtotime($baja) : 0;
        return $fila;
    }

    /**
     * Cinco matriculas el mismo dia agotan el cupo hasta el aniversario.
     *
     * @return void
     */
    public function test_cupo_agotado_hasta_el_aniversario(): void {
        $filas = [
            $this->fila('2026-09-01', '2026-09-20'),
            $this->fila('2026-09-01', '2026-10-01'),
            $this->fila('2026-09-01', '2026-10-05'),
            $this->fila('2026-09-01', '2026-10-09'),
            $this->fila('2026-09-01', '2026-10-12'),
        ];

        $antes = cupo::evaluar($filas, strtotime('2027-03-15'));
        $this->assertSame(5, $antes->consumidas);
        $this->assertSame(0, $antes->restantes);
        $this->assertFalse($antes->puedematricularse);
        $this->assertSame('cupoanual', $antes->motivo);
        $this->assertSame(strtotime('2027-09-01'), $antes->ventanafin);

        $enelaniversario = cupo::evaluar($filas, strtotime('2027-09-01'));
        $this->assertSame(5, $enelaniversario->restantes);
        $this->assertTrue($enelaniversario->puedematricularse);
    }

    /**
     * Las matriculas repartidas a lo largo del ano cuentan en la misma ventana.
     *
     * @return void
     */
    public function test_ventana_unica_con_matriculas_repartidas(): void {
        $filas = [
            $this->fila('2026-09-01', '2026-09-20'),
            $this->fila('2026-10-01', '2026-10-20'),
            $this->fila('2026-11-01', '2026-11-20'),
            $this->fila('2026-12-01', '2026-12-20'),
            $this->fila('2027-01-15', '2027-02-01'),
        ];

        $enmarzo = cupo::evaluar($filas, strtotime('2027-03-01'));
        $this->assertSame(5, $enmarzo->consumidas);
        $this->assertFalse($enmarzo->puedematricularse);

        // La ventana sigue anclada en la primera matricula, no en la ultima.
        $this->assertSame(strtotime('2026-09-01'), $enmarzo->ventanainicio);

        $trasaniversario = cupo::evaluar($filas, strtotime('2027-09-02'));
        $this->assertSame(5, $trasaniversario->restantes);
        $this->assertTrue($trasaniversario->puedematricularse);
    }

    /**
     * Dos cursos sin finalizar bloquean, aunque quede cupo anual.
     *
     * @return void
     */
    public function test_limite_de_simultaneos(): void {
        $filas = [
            $this->fila('2027-01-10'),
            $this->fila('2027-01-12'),
        ];

        $r = cupo::evaluar($filas, strtotime('2027-02-01'));
        $this->assertSame(2, $r->encurso);
        $this->assertSame(3, $r->restantes);
        $this->assertFalse($r->puedematricularse);
        $this->assertSame('simultaneos', $r->motivo);
    }

    /**
     * Finalizar libera una plaza simultanea, pero no la anual.
     *
     * @return void
     */
    public function test_finalizar_libera_plaza_simultanea(): void {
        $filas = [
            $this->fila('2027-01-10', '2027-01-30'),
            $this->fila('2027-01-12'),
        ];

        $r = cupo::evaluar($filas, strtotime('2027-02-01'));
        $this->assertSame(1, $r->encurso);
        $this->assertSame(2, $r->consumidas);
        $this->assertTrue($r->puedematricularse);
    }

    /**
     * La baja no devuelve la plaza anual, y por defecto tampoco la simultanea.
     *
     * @return void
     */
    public function test_la_baja_no_devuelve_la_plaza_anual(): void {
        $filas = [
            $this->fila('2027-01-10', '', '2027-01-20'),
            $this->fila('2027-01-12'),
        ];

        $r = cupo::evaluar($filas, strtotime('2027-02-01'));
        $this->assertSame(2, $r->consumidas);
        $this->assertSame(2, $r->encurso);
        $this->assertFalse($r->puedematricularse);

        // Con la valvula de escape activada, la baja si libera la plaza simultanea.
        set_config('liberarconbaja', 1, 'local_impefeverif');
        $con = cupo::evaluar($filas, strtotime('2027-02-01'));
        $this->assertSame(1, $con->encurso);
        $this->assertSame(2, $con->consumidas);
        $this->assertTrue($con->puedematricularse);
    }

    /**
     * Un curso abandonado hace anos sigue ocupando plaza mientras no caduque.
     *
     * @return void
     */
    public function test_abandono_antiguo_bloquea_salvo_caducidad(): void {
        $filas = [
            $this->fila('2026-03-01'),
            $this->fila('2028-01-05'),
        ];

        $sincaducidad = cupo::evaluar($filas, strtotime('2028-02-01'));
        $this->assertSame(2, $sincaducidad->encurso);
        $this->assertFalse($sincaducidad->puedematricularse);

        set_config('caducidadencurso', 90, 'local_impefeverif');
        $concaducidad = cupo::evaluar($filas, strtotime('2028-02-01'));
        $this->assertSame(1, $concaducidad->encurso);
        $this->assertTrue($concaducidad->puedematricularse);
    }

    /**
     * Quien no tiene historial dispone del cupo completo.
     *
     * @return void
     */
    public function test_persona_sin_historial(): void {
        $r = cupo::evaluar([], strtotime('2027-05-01'));
        $this->assertSame(0, $r->ventanainicio);
        $this->assertFalse($r->ventanaabierta);
        $this->assertSame(5, $r->restantes);
        $this->assertTrue($r->puedematricularse);
    }

    /**
     * El aniversario respeta el calendario, incluidos los anos bisiestos.
     *
     * @return void
     */
    public function test_aniversario_en_ano_bisiesto(): void {
        $this->assertSame(strtotime('2029-03-01'), cupo::aniversario(strtotime('2028-02-29')));
        $this->assertSame(strtotime('2028-02-29'), cupo::aniversario(strtotime('2027-02-28')) + DAYSECS);
    }
}
