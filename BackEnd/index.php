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

    case '/users/register':
        $controller = new Controllers\UserController();
        $controller->register(); // POST create user
        break;

    case '/users/login':
        $controller = new Controllers\UserController();
        $controller->login(); // POST login user
        break;

    case (preg_match('/^\/users\/\d+$/', $request) ? true : false):
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $user_id = explode('/', $request)[2];  // Lấy user_id từ URL
            $controller = new Controllers\UserController();
            $controller->show($user_id); // GET thông tin user theo ID
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Not Found"]);
        break;
}
