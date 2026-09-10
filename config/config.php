<?php
declare(strict_types=1);

define('BASE_URL', '/MesaReserva');
define('APP_NAME', 'MesaReserva');
define('APP_VERSION', '1.0.0');

date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
