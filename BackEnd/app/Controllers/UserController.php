<?php

namespace Controllers;

use Core\Controller;
use Models\User;

class UserController extends Controller
{
    public function index()
    {
        $user = new User();
        $users = $user->getAll();
        $this->response(['message' => 'Successful', 'users' => $users]);
    }

    public function create()
    {
        // Nhận dữ liệu từ body request
        $data = json_decode(file_get_contents('php://input'), true);

        // Kiểm tra các trường bắt buộc: username, email và password
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            $this->response(['message' => 'Missing username, email, or password'], 400);
            return;
        }

        // Kiểm tra định dạng email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->response(['message' => 'Invalid email format'], 400);
            return;
        }

        // Tạo đối tượng User và gọi phương thức create
        $user = new User();
        $user->create($data['username'], $data['email'], $data['password']);

        // Trả về thông báo thành công
        $this->response(['message' => 'Successfully']);
    }

    // Đăng nhập người dùng
    public function login()
    {
        // Nhận dữ liệu từ body request
        $data = json_decode(file_get_contents('php://input'), true);

        // Kiểm tra các trường bắt buộc: email và password
        if (!isset($data['email']) || !isset($data['password'])) {
            $this->response(['message' => 'Missing email or password'], 400);
            return;
        }

        // Kiểm tra định dạng email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->response(['message' => 'Invalid email format'], 400);
            return;
        }

        // Gọi phương thức login từ User model
        $user = new User();
        $result = $user->login($data['email'], $data['password']);

        if ($result) {
            $this->response(['message' => 'Successful', 'user' => $result]);
        } else {
            $this->response(['message' => 'Invalid credentials'], 401);
        }
    }

    // Lấy thông tin người dùng theo ID hoặc email
    public function show($id)
    {
        $user = new User();
        $userData = $user->getById($id);

        if ($userData) {
            $this->response(['message' => 'Successful', 'user' => $userData]);
        } else {
            $this->response(['message' => 'User not found'], 404);
        }
    }
}
