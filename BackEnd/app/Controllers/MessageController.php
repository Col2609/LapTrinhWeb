<?php

namespace Controllers;

use Core\Controller;
use Models\Message;
use Models\User;

class MessageController extends Controller
{
    private $messageModel;
    private $userModel;

    public function __construct()
    {
        $this->messageModel = new Message();
        $this->userModel = new User();
    }

    // Phương thức GET - Lấy danh sách tin nhắn
    public function getMessages()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            return $this->response(['message' => 'Không được phép truy cập'], 401);
        }

        $decoded = \Core\JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $this->response(['message' => 'Token không hợp lệ'], 401);
        }

        // Không cần kiểm tra content ở đây vì đây là GET request
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

        try {
            $messages = $this->messageModel->getMessages($page, $limit);

            $formattedMessages = array_map(function ($message) {
                return [
                    'message_id' => (int)$message['message_id'],
                    'content' => $message['content'],
                    'timestamp' => $message['timestamp'],
                    'sender' => [
                        'user_id' => (int)$message['user_id'],
                        'username' => $message['username'],
                        'nickname' => $message['nickname'],
                        'avatar' => $message['avatar']
                    ]
                ];
            }, $messages);

            return $this->response(['messages' => $formattedMessages]);
        } catch (\Exception $e) {
            return $this->response(['message' => 'Lỗi khi lấy tin nhắn'], 500);
        }
    }

    // Phương thức POST - Gửi tin nhắn mới
    public function sendMessage()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            return $this->response(['message' => 'Không được phép truy cập'], 401);
        }

        $decoded = \Core\JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $this->response(['message' => 'Token không hợp lệ'], 401);
        }

        $user = $this->userModel->getByUsername($decoded['username']);
        if (!$user) {
            return $this->response(['message' => 'Không tìm thấy người dùng'], 404);
        }

        // Chỉ kiểm tra content trong POST request
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['content'])) {
            return $this->response(['message' => 'Nội dung tin nhắn là bắt buộc'], 400);
        }

        $messageId = $this->messageModel->create([
            'sender_id' => $user['user_id'],
            'content' => $data['content']
        ]);

        $messageData = [
            'message_id' => $messageId,
            'content' => $data['content'],
            'timestamp' => date('Y-m-d H:i:s'),
            'sender' => [
                'user_id' => (int)$user['user_id'],
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'avatar' => $user['avatar']
            ]
        ];

        return $this->response([
            'message' => 'Gửi tin nhắn thành công',
            'data' => $messageData
        ]);
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
