<?php
// Tự động load class
spl_autoload_register(function ($class) {
    $path = __DIR__ . '/app/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

// Lấy URL
$request = $_SERVER['REQUEST_URI'];
$request = explode('?', $request)[0]; // bỏ query string

// Routing đơn giản
switch ($request) {
    case '/':
        echo json_encode(['message' => 'Welcome to My API']);
        break;

    case '/users':
        $controller = new Controllers\UserController();
        $controller->index(); // GET list users
        break;

    case '/users/create':
        $controller = new Controllers\UserController();
        $controller->create(); // POST create user
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Not Found"]);
        break;
}
