<?php

namespace Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    protected static $config;

    public static function init()
    {
        if (!self::$config) {
            self::$config = require __DIR__ . '/../../config/jwt.php';
        }
    }

    public static function generateToken($user, $type = 'access')
    {
        self::init();

        $now = time();
        // Xác định thời gian hết hạn dựa trên loại token
        $exp = $type === 'access' ? ($now + (self::$config['expiration'])) : ($now + (self::$config['expiration'] * 24 * 60 * 60));

        // Payload với các thông tin đơn giản như yêu cầu
        $payload = [
            'username' => $user['username'],
            'user_id' => $user['user_id'],
            'exp' => $exp
        ];

        // Mã hóa payload thành JWT
        return JWT::encode($payload, self::$config['secret'], 'HS256');
    }

    public static function verifyToken($token)
    {
        self::init();

        try {
            // Giải mã token và trả về payload
            $decoded = JWT::decode($token, new Key(self::$config['secret'], 'HS256'));
            return (array) $decoded; // Chuyển đổi đối tượng thành mảng nếu cần
        } catch (\Exception $e) {
            // Lỗi khi giải mã token
            return ['error' => 'Invalid token: ' . $e->getMessage()];
        }
    }
}
