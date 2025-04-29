<?php
// Cấu hình CORS
require_once __DIR__ . '/cors.php';

// Tự động load class
spl_autoload_register(function ($class) {
    $path = __DIR__ . '/app/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});


// Load autoloader của Composer
require_once __DIR__ . '/vendor/autoload.php';

// Lấy URL
$request = $_SERVER['REQUEST_URI'];
$request = explode('?', $request)[0]; // bỏ query string

// Routing đơn giản
switch ($request) {
    case '/':
        echo json_encode(['message' => 'Welcome to My API']);
        break;

    case '/auth':
        $controller = new Controllers\AuthController();
        $controller->index(); // GET list users
        break;

    case '/auth/register':
        $controller = new Controllers\AuthController();
        $controller->register(); // POST create user
        break;

    case '/auth/login':
        $controller = new Controllers\AuthController();
        $controller->login(); // POST login user
        break;

    case '/auth/refresh-token':
        $controller = new Controllers\AuthController();
        $controller->refreshToken(); // POST refresh token
        break;

    case '/auth/password/reset-request':
        $controller = new Controllers\AuthController();
        $controller->resetPasswordRequest(); // POST request password reset
        break;

    case '/auth/password/reset-confirm':
        $controller = new Controllers\AuthController();
        $controller->resetPasswordConfirm(); // POST confirm password reset
        break;

    case (preg_match('/^\/auth\/\d+$/', $request) ? true : false):
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $user_id = explode('/', $request)[2];  // Lấy user_id từ URL
            $controller = new Controllers\AuthController();
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
