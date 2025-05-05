<?php

namespace Controllers;

use Core\Controller;
use Models\User;
use Core\JwtHelper;

class AdminController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    private function checkAdmin()
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
        if (!$user || !$user['is_admin']) {
            return $this->response(['message' => 'Yêu cầu quyền admin'], 403);
        }

        return true;
    }

    public function getAllUsers()
    {
        if (!$this->checkAdmin()) return;

        $users = $this->userModel->getAll();
        return $this->response(['message' => 'Thành công', 'users' => $users]);
    }

    public function setAdmin($user_id = null)
    {
        if (!$this->checkAdmin()) return;

        if (!$user_id) {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['user_id'])) {
                return $this->response(['message' => 'Thiếu thông tin user_id'], 400);
            }
            $user_id = $data['user_id'];
        }

        $targetUser = $this->userModel->getById($user_id);
        if (!$targetUser) {
            return $this->response(['message' => 'Người dùng không tồn tại'], 404);
        }

        if ($targetUser['is_admin']) {
            return $this->response(['message' => 'Người dùng đã là admin'], 400);
        }

        try {
            $result = $this->userModel->update($user_id, ['is_admin' => 1]);
            if ($result) {
                return $this->response(['message' => 'Đã cấp quyền admin thành công']);
            } else {
                return $this->response([
                    'message' => 'Có lỗi xảy ra khi cập nhật quyền admin',
                    'error' => 'Không thể cập nhật cơ sở dữ liệu'
                ], 500);
            }
        } catch (\Exception $e) {
            return $this->response([
                'message' => 'Có lỗi xảy ra khi cập nhật quyền admin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteUser($user_id = null)
    {
        if (!$this->checkAdmin()) return;

        if (!$user_id) {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['user_id'])) {
                return $this->response(['message' => 'Thiếu thông tin user_id'], 400);
            }
            $user_id = $data['user_id'];
        }

        $targetUser = $this->userModel->getById($user_id);
        if (!$targetUser) {
            return $this->response(['message' => 'Người dùng không tồn tại'], 404);
        }

        if ($targetUser['is_admin']) {
            return $this->response(['message' => 'Không thể xóa tài khoản admin'], 403);
        }

        try {
            if (!empty($targetUser['avatar']) && file_exists($targetUser['avatar'])) {
                unlink($targetUser['avatar']);
            }

            $result = $this->userModel->delete($user_id);
            if ($result) {
                return $this->response(['message' => 'Đã xóa tài khoản người dùng thành công']);
            } else {
                return $this->response([
                    'message' => 'Có lỗi xảy ra khi xóa tài khoản',
                    'error' => 'Không thể xóa trong cơ sở dữ liệu'
                ], 500);
            }
        } catch (\Exception $e) {
            return $this->response([
                'message' => 'Có lỗi xảy ra khi xóa tài khoản',
                'error' => $e->getMessage()
            ], 500);
        }
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
