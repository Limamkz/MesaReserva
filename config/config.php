<?php
declare(strict_types=1);

define('BASE_URL', '/MesaReserva');
define('APP_NAME', 'MesaReserva');
define('APP_VERSION', '1.1.0');

// URL completa usada nos links enviados por e-mail.
// Em produção, troque pelo domínio HTTPS do sistema.
define('APP_URL', 'http://localhost/MesaReserva');

date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
session_start();
}
