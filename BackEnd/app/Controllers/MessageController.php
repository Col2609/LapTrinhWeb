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

    // Gửi tin nhắn
    public function sendMessage()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            return $this->response(['message' => 'Unauthorized'], 401);
        }

        $decoded = \Core\JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $this->response(['message' => 'Invalid token'], 401);
        }

        $user = $this->userModel->getByUsername($decoded['username']);
        if (!$user) {
            return $this->response(['message' => 'User not found'], 404);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['content'])) {
            return $this->response(['message' => 'Content is required'], 400);
        }

        $messageId = $this->messageModel->create([
            'sender_id' => $user['user_id'],
            'content' => $data['content']
        ]);

        // Tạo message data để trả về
        $messageData = [
            'message_id' => $messageId,
            'sender' => [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'avatar' => $user['avatar']
            ],
            'content' => $data['content'],
            'timestamp' => date('Y-m-d H:i:s')
        ];

        return $this->response([
            'message' => 'Message sent successfully',
            'data' => $messageData
        ]);
    }

    // Lấy lịch sử tin nhắn
    public function getMessages()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            return $this->response(['message' => 'Unauthorized'], 401);
        }

        $decoded = \Core\JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $this->response(['message' => 'Invalid token'], 401);
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

        $messages = $this->messageModel->getMessages($page, $limit);
        return $this->response(['messages' => $messages]);
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