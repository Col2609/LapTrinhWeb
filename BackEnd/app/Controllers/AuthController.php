<?php

namespace Controllers;

use Core\Controller;
use Models\User;
use Models\ResetToken;
use Core\JwtHelper;
use Core\EmailHelper;
use Helpers\PasswordHelper;

class AuthController extends Controller
{
    public function register()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['username'], $data['nickname'], $data['email'], $data['password'])) {
            return $this->response(['message' => 'Thiếu thông tin'], 400);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->response(['message' => 'Email không hợp lệ'], 400);
        }

        // Kiểm tra mật khẩu
        if (!PasswordHelper::validatePassword($data['password'])) {
            return $this->response([
                'message' => 'Mật khẩu không đáp ứng yêu cầu',
                'requirements' => PasswordHelper::getPasswordRequirements()
            ], 400);
        }

        $userModel = new User();

        // Kiểm tra xem username đã tồn tại chưa
        $existingUser = $userModel->getByUsername($data['username']);
        if ($existingUser) {
            return $this->response(['message' => 'Tên đăng nhập đã được sử dụng!'], 400);
        }

        // Kiểm tra xem email đã tồn tại chưa
        $existingEmail = $userModel->getByEmail($data['email']);
        if ($existingEmail) {
            return $this->response(['message' => 'Email đã được sử dụng!'], 400);
        }

        $userModel->create($data['username'], $data['nickname'], $data['email'], $data['password']);

        $newUser = $userModel->getByUsername($data['username']);

        $accessToken = JwtHelper::generateToken($newUser);
        $refreshToken = JwtHelper::generateToken($newUser);

        return $this->response([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'access_token_time' => '15 phút',
            'refresh_token_time' => '7 ngày',
            'user' => [
                'user_id' => (int)$newUser['user_id'],
                'username' => $newUser['username'],
                'nickname' => $newUser['nickname'],
                'email' => $newUser['email'],
                'avatar' => $newUser['avatar'],
                'is_admin' => $newUser['is_admin'],
                'last_active_UTC' => $newUser['last_active_UTC'],
                'created_at_UTC' => $newUser['created_at_UTC'],
            ]
        ]);
    }

    public function login()
    {
        // Kiểm tra Content-Type
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

        // Lấy dữ liệu từ request
        if (strpos($contentType, "application/json") !== false) {
            // Nếu là JSON
            $data = json_decode(file_get_contents('php://input'), true);
        } else {
            // Nếu là form data
            $data = $_POST;
        }

        if (!isset($data['username'], $data['password'])) {
            return $this->response(['message' => 'Thiếu tên đăng nhập hoặc mật khẩu'], 400);
        }

        $userModel = new User();
        $user = $userModel->login($data['username'], $data['password']);

        if ($user) {
            $accessToken = JwtHelper::generateToken($user);
            $refreshToken = JwtHelper::generateToken($user);

            // Cập nhật last_active_UTC
            $userModel->updateLastActive($user['user_id']);

            return $this->response([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'access_token_time' => '15 phút',
                'refresh_token_time' => '7 ngày',
                'user' => [
                    'user_id' => (int)$user['user_id'],
                    'username' => $user['username'],
                    'nickname' => $user['nickname'],
                    'email' => $user['email'],
                    'avatar' => $user['avatar'],
                    'is_admin' => $user['is_admin'],
                    'last_active_UTC' => $user['last_active_UTC'],
                    'created_at_UTC' => $user['created_at_UTC'],
                ]
            ]);
        } else {
            return $this->response(['message' => 'Tên đăng nhập, email hoặc mật khẩu không đúng!'], 401);
        }
    }

    public function show($username)
    {
        $user = new User();
        $userData = $user->getByUsername($username);

        if ($userData) {
            $this->response(['message' => 'Successful', 'user' => $userData]);
        } else {
            $this->response(['message' => 'Không tìm thấy người dùng'], 404);
        }
    }

    public function refreshToken()
    {
        // Kiểm tra Content-Type
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

        // Lấy dữ liệu từ request
        if (strpos($contentType, "application/json") !== false) {
            // Nếu là JSON
            $data = json_decode(file_get_contents('php://input'), true);
        } else {
            // Nếu là form data
            $data = $_POST;
        }

        if (!isset($data['refresh_token'])) {
            return $this->response(['message' => 'Thiếu refresh token'], 400);
        }

        try {
            // Verify refresh token
            $decoded = JwtHelper::verifyToken($data['refresh_token']);

            if (!$decoded) {
                return $this->response(['message' => 'Refresh token không hợp lệ!'], 401);
            }

            // Kiểm tra thời gian hết hạn
            if (isset($decoded['exp']) && $decoded['exp'] < time()) {
                return $this->response(['message' => 'Refresh token đã hết hạn!'], 401);
            }

            // Lấy thông tin user từ database
            $userModel = new User();
            $user = $userModel->getByUsername($decoded['username']);

            if (!$user) {
                return $this->response(['message' => 'Người dùng không tồn tại!'], 401);
            }

            // Cập nhật last_active_UTC
            $userModel->updateLastActive($user['user_id']);

            // Tạo access token mới
            $accessToken = JwtHelper::generateToken([
                'username' => $user['username'],
                'user_id' => $user['user_id']
            ]);

            // Lấy thông tin user đã cập nhật
            $updatedUser = $userModel->getByUsername($user['username']);

            return $this->response([
                'access_token' => $accessToken,
                'refresh_token' => $data['refresh_token'],
                'token_type' => 'Bearer',
                'access_token_time' => '15 phút',
                'refresh_token_time' => '7 ngày',
                'user' => [
                    'user_id' => (int)$updatedUser['user_id'],
                    'username' => $updatedUser['username'],
                    'nickname' => $updatedUser['nickname'],
                    'email' => $updatedUser['email'],
                    'avatar' => $updatedUser['avatar'],
                    'is_admin' => $updatedUser['is_admin'],
                    'last_active_UTC' => $updatedUser['last_active_UTC'],
                    'created_at_UTC' => $updatedUser['created_at_UTC'],
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response(['message' => 'Refresh token không hợp lệ!'], 401);
        }
    }

    public function resetPasswordRequest()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['email']) || !isset($data['base_url'])) {
            return $this->response(['message' => 'Thiếu thông tin cần thiết'], 400);
        }

        $userModel = new User();
        $user = $userModel->getByEmail($data['email']);

        if (!$user) {
            return $this->response(['message' => 'Không tìm thấy người dùng!'], 404);
        }

        if ($user['is_admin']) {
            return $this->response(['message' => 'Admin không thể sử dụng chức năng này!'], 403);
        }

        // Xóa các token reset cũ của user
        $resetTokenModel = new ResetToken();
        $resetTokenModel->deleteByUserId($user['user_id']);

        // Tạo token mới
        $resetUuid = uniqid('', true);
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = ResetToken::hashToken($rawToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // Lưu token vào database
        $resetTokenModel->create($user['user_id'], $resetUuid, $tokenHash, $expiresAt);

        try {
            // Gửi email
            $resetLink = $data['base_url'] . "/Frontend/html/auth/resetpass.html?uuid=" . $resetUuid;
            $subject = "Reset your password";
            $emailContent = "
                <html>
                <body>
                    <h2>Password Reset</h2>
                    <p>Click the link below to reset your password:</p>
                    <a href='{$resetLink}'>{$resetLink}</a>
                    <p>This link will expire in 5 minutes.</p>
                </body>
                </html>
            ";

            EmailHelper::sendEmail($data['email'], $subject, $emailContent);
            return $this->response(['message' => 'Email đặt lại mật khẩu đã được gửi.']);
        } catch (\Exception $e) {
            return $this->response(['message' => 'Không thể gửi email, vui lòng thử lại sau!'], 500);
        }
    }

    public function resetPasswordConfirm()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['reset_uuid']) || !isset($data['new_password'])) {
                return $this->response(['message' => 'Thiếu thông tin cần thiết'], 400);
            }

            $resetTokenModel = new ResetToken();
            $resetEntry = $resetTokenModel->getByUuid($data['reset_uuid']);

            if (!$resetEntry) {
                return $this->response(['message' => 'Liên kết đặt lại mật khẩu không hợp lệ!'], 400);
            }

            // Kiểm tra thời gian hết hạn
            if (strtotime($resetEntry['expires_at_UTC']) < time()) {
                return $this->response(['message' => 'Liên kết đặt lại mật khẩu đã hết hạn!'], 400);
            }

            $userModel = new User();
            $user = $userModel->getById($resetEntry['user_id']);

            if (!$user) {
                return $this->response(['message' => 'Người dùng không tồn tại!'], 404);
            }

            if ($user['is_admin']) {
                return $this->response(['message' => 'Admin không thể sử dụng chức năng này!'], 403);
            }

            // Cập nhật mật khẩu mới
            $userModel->updatePassword($user['user_id'], $data['new_password']);

            // Cập nhật thời gian hoạt động
            $userModel->updateLastActive($user['user_id']);

            // Xóa reset token
            $resetTokenModel->deleteByUuid($data['reset_uuid']);

            return $this->response(['message' => 'Mật khẩu đã được đặt lại thành công!']);
        } catch (\PDOException $e) {
            error_log("Database error in resetPasswordConfirm: " . $e->getMessage());
            return $this->response(['message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            error_log("Error in resetPasswordConfirm: " . $e->getMessage());
            return $this->response(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }
}
