# local_impefeverif — Verificación de requisitos de acceso

Plugin local para el aula virtual de IMPEFE. Automatiza la validación de las
acreditaciones de **estudiante** y **trabajador de empresa de Ciudad Real**, y
destruye la documentación acreditativa transcurrido el plazo de conservación.

La acreditación por **empadronamiento** no se valida sola: la revisa IMPEFE.

---

## Qué hace y qué no hace

**Sí hace:**

- Comprueba que ha transcurrido el plazo de espera (24 h por defecto) desde el envío.
- Comprueba que la ejecución cae en horario laboral, en la zona horaria configurada,
  descontando fines de semana y el calendario de festivos de Ciudad Real.
- Comprueba que existe archivo y que su extensión está admitida.
- Consigna la calificación Apto con una cuenta identificable como automática.
- Recalcula la finalización de la actividad, que es lo que abre la puerta de acceso.
- Avisa al usuario de que falta documentación o el formato no es válido.
- Avisa al usuario de que su cuenta está validada y ya puede matricularse, **en el
  momento en que entra en la cohorte de verificados**, no al calificar. Esto vale
  también para el empadronamiento, que revisa IMPEFE a mano.
- Guarda la huella SHA-1 del documento, su extensión y su tamaño.
- Destruye el archivo a los 30 días de la validación, conservando calificación y huella.

- Controla el cupo de acciones formativas: máximo 5 por ventana de 12 meses
  contada desde la primera matrícula, y máximo 2 cursos simultáneos.

**No hace:**

- No lee el contenido del documento ni comprueba que diga lo que debe decir.
- No verifica el empadronamiento.

## Reglas de cupo acordadas con IMPEFE

- **5 matrículas por ventana de 12 meses**, contada desde la primera matrícula de
  la persona. Al cumplirse el aniversario la ventana se cierra y la siguiente
  matrícula abre una ventana nueva. No es año natural.
- **Una matrícula consume plaza aunque se abandone el curso.** Por eso existe un
  libro de matrículas propio: si se consultaran las matrículas vivas de Moodle,
  dar de baja a alguien le devolvería la plaza.
- **2 cursos simultáneos como máximo.** Una plaza simultánea se libera solo al
  finalizar el curso.

Cómo se aplica: la tarea `recalcular_cupos` mantiene la cohorte «Con cupo
disponible», y la automatrícula de cada curso del catálogo se restringe a esa
cohorte con la opción nativa **Solo miembros de cohorte**. Al agotarse el cupo el
botón de matricularse desaparece en todos los cursos a la vez, y vuelve al
liberarse plaza. No hace falta un método de matriculación propio.

---

## Instalación

1. Copiar la carpeta en `local/impefeverif` de la instalación de Moodle.
2. Visitar `Administración del sitio > Notificaciones` para completar la instalación.
3. Crear una cuenta de usuario dedicada, por ejemplo «Validación automática»
   (autenticación: cuenta manual, sin contraseña utilizable), y anotar su id.
4. Matricular esa cuenta en el curso de verificación con el rol de revisor,
   para que pueda calificar.
5. Configurar el plugin en `Administración del sitio > Plugins > Plugins locales >
   Verificación de requisitos de acceso`.

## Ajustes

| Ajuste | Valor recomendado |
|---|---|
| Activar la validación automática | desactivado hasta terminar las pruebas |
| Tareas de validación automática | cmid de Estudiante y Trabajador |
| Tareas de revisión humana | cmid de Empadronamiento |
| Cohorte de usuarios verificados | id de la cohorte Verificados |
| URL del catálogo de cursos | URL de la categoría del catálogo |
| Cohorte con cupo disponible | id de la cohorte Con cupo |
| Categoría del catálogo | id de la categoría de cursos gratuitos |
| Máximo anual / simultáneos | 5 / 2 |
| La baja libera plaza simultánea | desactivado |
| Caducidad de cursos sin finalizar | 0, desactivada |
| Horas de espera | 24 |
| Horario laboral | 8 a 15 |
| Días laborables | 1,2,3,4,5 |
| Calendario de festivos | festivos nacionales, de Castilla-La Mancha y de Ciudad Real |
| Zona horaria | Europe/Madrid |
| Valor de la calificación Apto | 2, con la escala «No apto, Apto» |
| Cuenta calificadora | id de la cuenta «Validación automática» |
| Extensiones admitidas | pdf,jpg,jpeg,png |
| Días de conservación | 30 |
| Activar la destrucción | desactivado hasta que IMPEFE firme la instrucción |

El cmid de cada tarea aparece en su URL: `/mod/assign/view.php?id=XXXX`.
El id de la cohorte aparece en su URL de edición: `/cohort/edit.php?id=XXXX`.
El id de la categoría, en `/course/index.php?categoryid=XXXX`.

En cada curso del catálogo, la automatrícula debe tener **Solo miembros de
cohorte** apuntando a «Con cupo disponible». Si un curso se salta esa restricción,
sus matrículas se contarán pero no se impedirán.

Si la cohorte no está configurada, no se envía el aviso de acceso. El resto del
circuito funciona igual, pero el usuario no se enterará de que ya puede entrar.

## Carga inicial del libro de matrículas

Los observadores solo capturan lo que ocurre desde su instalación. Para recuperar
el histórico:

```bash
php local/impefeverif/cli/backfill.php              # simula
php local/impefeverif/cli/backfill.php --ejecutar   # escribe
```

Las bajas anteriores a la instalación son irrecuperables: Moodle ya borró la fila.
Ese histórico perdido juega a favor del usuario, no en su contra.

## Pruebas antes de activar

Con los dos interruptores desactivados, las tareas se ejecutan sin efectos.
Para ver qué harían:

```bash
php local/impefeverif/cli/simular.php
php local/impefeverif/cli/simular.php --purga
php local/impefeverif/cli/simular.php --cupos
```

Las reglas de cupo tienen pruebas automatizadas:

```bash
vendor/bin/phpunit local/impefeverif/tests/cupo_test.php
```

Listado mínimo de pruebas en preproducción:

1. Usuario con vía Estudiante y archivo PDF, enviado hace 25 h → se valida.
2. El mismo caso pero enviado hace 2 h → no se valida.
3. Ejecución un sábado o a las 22:00 → no se valida nada.
4. Usuario que envía un `.docx` → no se valida y recibe aviso.
5. Usuario con vía Empadronamiento → no se toca nunca.
6. Envío ya calificado No apto por una persona → no se recalifica.
7. Tras validar, comprobar que aparece en la cohorte y que se le abre el catálogo.
8. Adelantar la fecha de calificación 31 días y ejecutar la purga → desaparece el
   archivo, la calificación sigue en pie y queda la huella en el registro.
9. Comprobar que el correo de «cuenta validada» llega **después** de que el enlace
   del catálogo funcione, no antes.
10. Añadir a mano un usuario a la cohorte Verificados → recibe el aviso una sola vez.
    Sacarlo y volverlo a meter → no lo recibe de nuevo.
11. Calificar a mano un empadronamiento → el usuario recibe el mismo aviso de acceso.
12. Matricular a alguien en 5 cursos → sale de la cohorte Con cupo y el botón de
    matricularse desaparece en todo el catálogo.
13. Dar de baja a esa persona de uno de los 5 → **no** recupera la plaza anual.
14. Matricular a alguien en 2 cursos sin finalizar → no puede coger un tercero.
    Finalizar uno → puede de nuevo, sin esperar al aniversario.

## Frecuencia de las tareas

En `Administración del sitio > Servidor > Tareas programadas`:

| Tarea | Frecuencia |
|---|---|
| Validar automáticamente acreditaciones | cada 15 min |
| Recalcular el cupo | cada 10 min |
| Destruir documentación vencida | diaria, 3:20 |
| Finalización del curso (`core`) | cada 10 min |
| Cohortes dinámicas (`tool_dynamic_cohorts`) | cada 5 min |
| Tareas ad hoc (`cron` general) | cada minuto |

Latencia total desde la validación hasta el acceso efectivo: entre 5 y 25 minutos.

## Consulta del cupo

```sql
SELECT u.lastname, u.firstname, COUNT(m.id) AS matriculas,
       MIN(m.timeenrolled) AS primera,
       SUM(CASE WHEN m.timecompleted > 0 THEN 1 ELSE 0 END) AS finalizados
  FROM {local_impefeverif_matr} m
  JOIN {user} u ON u.id = m.userid
 GROUP BY u.id, u.lastname, u.firstname
 ORDER BY matriculas DESC
```

## Consulta del registro

La tabla `local_impefeverif_reg` es la traza que sobrevive a la destrucción del
documento. Un informe configurable sobre ella cubre lo que IMPEFE necesitaría
en una fiscalización:

```sql
SELECT u.idnumber, u.lastname, u.firstname, r.via, r.modo, r.estado,
       r.timesubmitted, r.timevalidated, r.filehash, r.timepurged
  FROM {local_impefeverif_reg} r
  JOIN {user} u ON u.id = r.userid
 ORDER BY r.timevalidated DESC
```

## El aviso de acceso

Se envía mediante la API de mensajes, así que respeta las preferencias de
notificación del usuario y queda también en sus notificaciones de Moodle. Si
quieres garantizar la entrega por correo con independencia de esas preferencias,
sustituye `message_send` por `email_to_user` en `helper::notificar()`. Es una
línea, pero pierdes la traza en Moodle: decídelo a conciencia.

El cuerpo del aviso incluye los dos límites que la plataforma no controla por sí
sola (no simultaneidad y máximo de cinco al año). No es lo mismo que aplicarlos,
pero al menos quedan comunicados por escrito a cada usuario.

Para reenviar el aviso a alguien puntualmente, basta borrar su preferencia
`local_impefeverif_avisoacceso` y sacarlo y volverlo a meter en la cohorte.

## Supervisión

Revisar semanalmente:

- Que ambas tareas se ejecutan sin error en el registro de tareas programadas.
- Que no hay filas con estado `sin_archivo` antiguas y sin resolver.
- Que no se acumulan tareas ad hoc `avisar_acceso` sin ejecutar.
- Cuántas personas hay sin cupo por `simultaneos`. Si crece mucho, son abandonos
  bloqueando plazas: es el momento de plantear a IMPEFE la caducidad automática.
- Que el número de filas con `timepurged` a cero y validación de más de 31 días es cero.
  Si no lo es, la purga no está corriendo.
