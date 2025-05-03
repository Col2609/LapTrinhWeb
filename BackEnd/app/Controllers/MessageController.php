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
                    ],
                    'attachments' => $message['attachments'] ?? []
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

        // Lấy content nếu có
        $content = null;
        if (isset($_POST['content'])) {
            $content = $_POST['content'];
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['content'])) {
                $content = $data['content'];
            }
        }

        // Cho phép gửi file mà không cần content
        if (empty($content) && !isset($_FILES['file'])) {
            return $this->response(['message' => 'Phải có nội dung hoặc file đính kèm'], 400);
        }

        $messageData = [
            'sender_id' => $user['user_id'],
            'content' => $content !== null ? $content : ''
        ];

        $attachments = [];
        if (isset($_FILES['file'])) {
            $file = $_FILES['file'];
            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'video/mp4', 'audio/mpeg'];
            $maxSize = 10 * 1024 * 1024; // 10MB

            if (!in_array($file['type'], $allowedTypes)) {
                return $this->response(['message' => 'Loại file không được hỗ trợ'], 400);
            }

            if ($file['size'] > $maxSize) {
                return $this->response(['message' => 'File quá lớn, tối đa 10MB'], 400);
            }

            $uploadDir = 'uploads/conversations/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $extension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $attachments[] = [
                    'file_url' => $filePath,
                    'file_type' => $file['type']
                ];
            }
        }

        $messageId = $this->messageModel->create($messageData);

        // Lưu file vào bảng attachments nếu có file đính kèm
        if (!empty($attachments)) {
            foreach ($attachments as $att) {
                $stmt = $this->messageModel->getDb()->prepare('INSERT INTO attachments (message_id, file_url, file_type) VALUES (?, ?, ?)');
                $stmt->execute([$messageId, $att['file_url'], $att['file_type']]);
            }
        }

        $messageData = [
            'message_id' => $messageId,
            'content' => $content,
            'timestamp' => date('Y-m-d H:i:s'),
            'sender' => [
                'user_id' => (int)$user['user_id'],
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'avatar' => $user['avatar']
            ],
            'attachments' => $attachments
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
