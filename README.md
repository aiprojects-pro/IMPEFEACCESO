# IMPEFE Acceso Moodle

Plugin local de Moodle para mantener el circuito de acceso IMPEFE:

- Alta con consentimiento a las Condiciones de acceso.
- Fecha de nacimiento en formato `DD/MM/AAAA` y validación de edad mínima de 16 años.
- Vías de acreditación: empadronamiento, estudiante y trabajador.
- Validación automática de estudiantes/trabajadores y purga de documentación.
- Control de cupo: 5 acciones formativas por ventana de 12 meses y 2 cursos simultáneos.
- Footer con enlaces `Aviso legal | Condiciones de acceso`.
- Script idempotente para reponer textos, política, escala y tareas de documentación.

## Instalación en Moodle

Copiar el plugin a:

```bash
MOODLE_ROOT/local/impefeverif
```

Ejecutar:

```bash
php MOODLE_ROOT/admin/cli/upgrade.php --non-interactive --lang=es
php MOODLE_ROOT/local/impefeverif/cli/apply_access_setup.php
php MOODLE_ROOT/admin/cli/purge_caches.php
```

## Comprobaciones

```bash
php MOODLE_ROOT/admin/cli/checks.php
php -r 'define("CLI_SCRIPT", true); require "MOODLE_ROOT/config.php"; echo get_config("core", "sitepolicyhandler"), PHP_EOL;'
```

El `sitepolicyhandler` debe ser:

```text
local_impefeverif
```

## URLs a revisar

- `/login/signup.php`: debe entrar directamente al formulario de alta y mostrar el checkbox `Doy mi consentimiento a las Condiciones de acceso`.
- `/admin/tool/policy/view.php?policyid=1`: debe mostrar la política de condiciones.
- Portada: debe mostrar el bloque `Cómo acceder a los cursos`.
- Footer: debe mostrar `Aviso legal | Condiciones de acceso`.

## Reaplicar después de actualizar Moodle

Si una actualización pisa configuraciones de base de datos, ejecutar:

```bash
php MOODLE_ROOT/local/impefeverif/cli/apply_access_setup.php
php MOODLE_ROOT/admin/cli/purge_caches.php
```

El script es idempotente: se puede ejecutar varias veces. Reaplica:

- `sitepolicyhandler = local_impefeverif`
- Contenido de la política `Condiciones de acceso`
- Texto de la sección de portada `Solicitud de Acceso`
- Escala `No válido / Válido`
- Escala en las tareas `11469`, `11495` y `11496`

## Estado base del aula IMPEFE

- Moodle root usado al preparar este repositorio: `/var/www/aula/public`
- Tema activo: `moove`
- Email de soporte usado por el bloque de acceso: valor de `supportemail`, actualmente `soporte@cgdformacion.com`
- Política de condiciones: `tool_policy_versions.id = 1`
- Sección de portada: `course_sections.id = 3403`
- Tareas de documentación:
  - `11469`: empadronados
  - `11495`: estudiantes
  - `11496`: trabajadores
