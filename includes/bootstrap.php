<?php
/**
 * Bootstrap: inclua no topo de TODA página/endpoint.
 *   require __DIR__ . '/includes/bootstrap.php';
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/scoring.php';
require __DIR__ . '/pools.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
