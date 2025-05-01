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
            return $this->response(['message' => 'Unauthorized'], 401);
        }

        $decoded = \Core\JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $this->response(['message' => 'Invalid token'], 401);
        }

        // Không cần kiểm tra content ở đây vì đây là GET request
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

        try {
            $messages = $this->messageModel->getMessages($page, $limit);
            
            $formattedMessages = array_map(function($message) {
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
            return $this->response(['message' => 'Error fetching messages'], 500);
        }
    }

    // Phương thức POST - Gửi tin nhắn mới
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

        // Chỉ kiểm tra content trong POST request
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['content'])) {
            return $this->response(['message' => 'Content is required'], 400);
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
            'message' => 'Message sent successfully',
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