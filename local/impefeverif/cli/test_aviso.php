<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_impefeverif\helper;

list($options) = cli_get_params(
    ['userid' => 1774, 'send' => false, 'help' => false],
    ['u' => 'userid', 's' => 'send', 'h' => 'help']
);

if ($options['help']) {
    cli_writeln("Dispara el aviso de acceso y muestra lo que recibe el usuario.

  -u, --userid=N   destinatario (por defecto 1774)
  -s, --send       entrega el correo de verdad (por defecto se suprime)
  -h, --help
");
    exit(0);
}

$userid = (int)$options['userid'];

if (!$options['send']) {
    $CFG->noemailever = true;   // la notificacion se registra, el correo no sale
}

$antes = time();
$usuario = \core_user::get_user($userid);

$a = new \stdClass();
$a->nombre = fullname($usuario);
$a->url = helper::url_catalogo();
$a->sitio = format_string(get_site()->fullname);

helper::notificar(
    $userid,
    get_string('avisoacceso_asunto', 'local_impefeverif'),
    get_string('avisoacceso_cuerpo', 'local_impefeverif', $a),
    'acceso'
);

$fila = $DB->get_record_select(
    'notifications',
    'useridto = :uid AND timecreated >= :desde',
    ['uid' => $userid, 'desde' => $antes],
    '*',
    IGNORE_MULTIPLE
);

if (!$fila) {
    cli_error('No se registro ninguna notificacion. Revisa las preferencias del usuario o db/messages.php.');
}

cli_separator();
cli_writeln("ASUNTO: {$fila->subject}");
cli_separator();
cli_writeln("LO QUE VE EN TEXTO PLANO:\n{$fila->fullmessage}");
cli_separator();

$destino = $CFG->tempdir . "/aviso-{$fila->id}.html";
file_put_contents($destino, $fila->fullmessagehtml);
cli_writeln("HTML guardado en: {$destino}");
cli_writeln('Enlaces encontrados:');
preg_match_all('/<a\s[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', $fila->fullmessagehtml, $m, PREG_SET_ORDER);
if (!$m) {
    cli_writeln('  (ninguno — el ancla no ha llegado)');
}
foreach ($m as $enlace) {
    cli_writeln('  "' . trim(strip_tags($enlace[2])) . '" -> ' . $enlace[1]);
}
cli_separator();
cli_writeln($options['send'] ? 'Correo entregado.' : 'Correo suprimido (noemailever). Anade --send para enviarlo.');
