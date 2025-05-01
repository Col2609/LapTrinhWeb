<?php
require __DIR__ . '/vendor/autoload.php';

// Autoload cho các class trong thư mục app
spl_autoload_register(function ($class) {
    $path = __DIR__ . '/app/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use WebSocket\ChatServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8080,
    '0.0.0.0'
);

echo "WebSocket Server started at port 8080\n";
$server->run();