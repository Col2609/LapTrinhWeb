<?php
// Cấu hình CORS
require_once __DIR__ . '/cors.php';

require_once __DIR__ . '/config/upload_paths.php';

// Tự động load class
spl_autoload_register(function ($class) {
    $path = __DIR__ . '/app/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});


// Load autoloader của Composer
require_once __DIR__ . '/vendor/autoload.php';

// Tạo admin mặc định nếu chưa có
require_once __DIR__ . '/config/create_admin.php';

// Tạo các thư mục upload khi khởi động server
createUploadDirectories();

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

    case '/auth/check-admin':
        $db = require __DIR__ . '/config/database.php';
        $stmt = $db->prepare('SELECT * FROM users WHERE is_admin = 1 LIMIT 1');
        $stmt->execute();
        $admin = $stmt->fetch(\PDO::FETCH_ASSOC);
        echo json_encode(['exists' => $admin ? true : false]);
        break;

    // API User Management
    case '/users':
        $controller = new Controllers\UserController();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $controller->getUserInfo(); // GET thông tin user
                break;
            case 'POST':
                $controller->updateUserInfo(); // POST cập nhật thông tin user
                break;
            case 'DELETE':
                $controller->deleteUser(); // DELETE xóa user
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Method Not Allowed"]);
                break;
        }
        break;

    case '/users/search':
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $controller = new Controllers\UserController();
            $controller->searchUsers(); // GET tìm kiếm user
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/users/password/change':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $controller = new Controllers\UserController();
            $controller->changePassword(); // POST đổi mật khẩu
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/messages':
        $controller = new Controllers\MessageController();
        $controller->sendMessage(); // POST gửi tin nhắn
        break;

    case '/messages':
        $controller = new Controllers\MessageController();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $controller->getMessages(); // Xử lý GET request
                break;
            case 'POST':
                $controller->sendMessage(); // Xử lý POST request
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Method Not Allowed"]);
                break;
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(["message" => "Not Found"]);
        break;
}
