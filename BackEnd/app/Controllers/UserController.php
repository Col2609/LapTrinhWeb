<?php

namespace Controllers;

use Core\Controller;
use Models\User;
use Models\Report;
use Models\Friend;
use Models\FriendRequest;

use Core\JwtHelper;

class UserController extends Controller
{
    private $userModel;
    private $reportModel;
    private $friendModel;
    private $friendRequestModel;
    public function __construct()
    {
        $this->userModel = new User();
    }

    public function getUserInfo()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            return $this->response(['message' => 'Không được phép truy cập'], 401);
        }

        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $this->response(['message' => 'Token không hợp lệ'], 401);
        }

        $user = $this->userModel->getByUsername($decoded['username']);
        if (!$user) {
            return $this->response(['message' => 'Không tìm thấy người dùng'], 404);
        }

        return $this->response([
            'user_id' => (int)$user['user_id'],
            'username' => $user['username'],
            'nickname' => $user['nickname'],
            'email' => $user['email'],
            'avatar' => $user['avatar'],
            'is_admin' => $user['is_admin'],
            'last_active_UTC' => $user['last_active_UTC'],
            'created_at_UTC' => $user['created_at_UTC']
        ]);
    }

    public function updateUserInfo()
    {
        // Debug: Log request info
        error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);
        error_log('Content Type: ' . $_SERVER['CONTENT_TYPE']);
        error_log('POST data: ' . print_r($_POST, true));
        error_log('FILES data: ' . print_r($_FILES, true));

        $token = $this->getBearerToken();
        if (!$token) {
            return $this->response(['message' => 'Không được phép truy cập'], 401);
        }

        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $this->response(['message' => 'Token không hợp lệ'], 401);
        }

        $user = $this->userModel->getByUsername($decoded['username']);
        if (!$user) {
            return $this->response(['message' => 'Không tìm thấy người dùng'], 404);
        }

        // Kiểm tra nếu là admin
        if ($user['is_admin']) {
            return $this->response([
                'message' => 'Admin không được cập nhật thông tin cá nhân hoặc avatar.'
            ], 403);
        }

        $data = [];
        $hasUpdate = false;

        // Xử lý nickname
        if (isset($_POST['nickname'])) {
            $nickname = trim($_POST['nickname']);
            if (!empty($nickname)) {
                $data['nickname'] = $nickname;
                $hasUpdate = true;
            }
        }

        // Xử lý email
        if (isset($_POST['email'])) {
            $email = trim($_POST['email']);
            if (!empty($email)) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $this->response(['message' => 'Định dạng email không hợp lệ'], 400);
                }

                // Kiểm tra email đã tồn tại
                if ($email !== $user['email']) {
                    $existingUser = $this->userModel->getByEmail($email);
                    if ($existingUser) {
                        return $this->response([
                            'message' => 'Email đã được sử dụng bởi người dùng khác'
                        ], 400);
                    }
                }

                $data['email'] = $email;
                $hasUpdate = true;
            }
        }

        // Xử lý avatar
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];

            // Debug: Log file info
            error_log('File info: ' . print_r($file, true));

            // Kiểm tra định dạng file
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
                return $this->response([
                    'message' => 'Định dạng ảnh không hợp lệ! (Chỉ chấp nhận jpg, jpeg, png)'
                ], 400);
            }

            // Xóa avatar cũ nếu có
            if ($user['avatar']) {
                try {
                    if (file_exists($user['avatar'])) {
                        unlink($user['avatar']);
                    }
                } catch (\Exception $e) {
                    error_log("Lỗi khi xóa avatar cũ: " . $e->getMessage());
                }
            }

            // Lưu file mới
            $fileName = "userID_{$user['user_id']}.{$fileExtension}";
            $filePath = AVATARS_USER_DIR . '/' . $fileName;

            // Debug: Log upload path
            error_log('Upload path: ' . $filePath);

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                error_log('Upload error: ' . print_r(error_get_last(), true));
                return $this->response([
                    'message' => 'Failed to upload file',
                    'error' => error_get_last()
                ], 500);
            }

            // Chuẩn hóa đường dẫn
            $normalizedPath = str_replace('\\', '/', $filePath);
            $data['avatar'] = $normalizedPath;
            $hasUpdate = true;
        }

        if (!$hasUpdate) {
            return $this->response([
                'message' => 'Không có dữ liệu hợp lệ để cập nhật',
                'debug_info' => [
                    'method' => $_SERVER['REQUEST_METHOD'],
                    'content_type' => $_SERVER['CONTENT_TYPE'],
                    'post' => $_POST,
                    'files' => $_FILES
                ]
            ], 400);
        }

        if (!$this->userModel->update($user['user_id'], $data)) {
            return $this->response(['message' => 'Cập nhật thông tin người dùng thất bại'], 500);
        }

        $updatedUser = $this->userModel->getByUsername($user['username']);
        return $this->response([
            'message' => 'Cập nhật thông tin thành công',
            'user' => [
                'nickname' => $updatedUser['nickname'],
                'email' => $updatedUser['email'],
                'avatar_url' => $updatedUser['avatar']
            ]
        ]);
    }

    public function deleteUser()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            return $this->response(['message' => 'Không được phép truy cập'], 401);
        }

        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $this->response(['message' => 'Token không hợp lệ'], 401);
        }

        $user = $this->userModel->getByUsername($decoded['username']);
        if (!$user) {
            return $this->response(['message' => 'Không tìm thấy người dùng'], 404);
        }

        // Chặn admin tự xóa tài khoản
        if ($user['is_admin']) {
            return $this->response(['message' => 'Admin không thể xóa tài khoản.'], 403);
        }

        // Xóa avatar nếu có
        if (!empty($user['avatar']) && file_exists($user['avatar'])) {
            unlink($user['avatar']);
        }

        // Xóa tài khoản
        $this->userModel->delete($user['user_id']);

        return $this->response([
            'message' => 'Tài khoản đã bị xóa, tin nhắn vẫn còn nhưng không có thông tin người gửi/nhận.'
        ]);
    }

    public function searchUsers()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            return $this->response(['message' => 'Không được phép truy cập'], 401);
        }

        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $this->response(['message' => 'Token không hợp lệ'], 401);
        }

        $query = $_GET['query'] ?? '';
        $searchByNickname = isset($_GET['search_by_nickname']) && $_GET['search_by_nickname'] === 'true';

        if (empty($query)) {
            return $this->response(['message' => 'Search query is required'], 400);
        }

        $currentUser = $this->userModel->getByUsername($decoded['username']);
        if (!$currentUser) {
            return $this->response(['message' => 'Không tìm thấy người dùng'], 404);
        }

        // Tìm kiếm người dùng
        if ($searchByNickname) {
            $users = $this->userModel->searchByNickname($query, $currentUser['username']);
        } else {
            $users = $this->userModel->searchByUsername($query, $currentUser['username']);
        }

        $results = [];
        foreach ($users as $user) {
            $status = 'Chưa kết bạn';

            // Kiểm tra trạng thái bạn bè
            $isFriend = $this->friendModel->isFriend($currentUser['username'], $user['username']);
            if ($isFriend) {
                $status = 'Bạn bè';
            } else {
                // Kiểm tra lời mời đã gửi
                $sent = $this->friendRequestModel->isRequestSent($currentUser['username'], $user['username']);
                if ($sent) {
                    $status = 'Đã gửi lời mời';
                } else {
                    // Kiểm tra lời mời đã nhận
                    $received = $this->friendRequestModel->isRequestReceived($currentUser['username'], $user['username']);
                    if ($received) {
                        $status = 'Chờ xác nhận';
                    }
                }
            }

            $results[] = [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'email' => $user['email'],
                'avatar' => $user['avatar'],
                'created_at_UTC' => $user['created_at_UTC'],
                'last_active_UTC' => $user['last_active_UTC'],
                'status' => $status,
            ];
        }

        return $this->response(['results' => $results]);
    }

    public function changePassword()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            return $this->response(['message' => 'Không được phép truy cập'], 401);
        }

        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $this->response(['message' => 'Token không hợp lệ'], 401);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['current_password'], $data['new_password'])) {
            return $this->response(['message' => 'Missing required fields'], 400);
        }

        $user = $this->userModel->getByUsername($decoded['username']);
        if (!$this->userModel->verifyPassword($user['user_id'], $data['current_password'])) {
            return $this->response(['message' => 'Mật khẩu hiện tại không đúng!'], 400);
        }

        if (!\Helpers\PasswordHelper::validatePassword($data['new_password'])) {
            return $this->response([
                'message' => 'Mật khẩu không đáp ứng yêu cầu',
                'requirements' => \Helpers\PasswordHelper::getPasswordRequirements()
            ], 400);
        }

        $this->userModel->updatePassword($user['user_id'], $data['new_password']);
        return $this->response(['message' => 'Mật khẩu đã được thay đổi thành công!']);
    }

    private function getBearerToken()
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            return null;
        }

        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }

        return null;
    }
}
