<?php

namespace Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper
{
    private static function getMailer()
    {
        $mail = new PHPMailer(true);
        
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'appchat.noreply@gmail.com'; // Thay bằng email của bạn
        $mail->Password = 'kgfhjevwwdpfiygm'; // Thay bằng app password của bạn
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        return $mail;
    }

    public static function sendEmail($to, $subject, $body)
    {
        try {
            $mail = self::getMailer();
            
            // Người gửi
            $mail->setFrom($mail->Username, 'Chat App');
            
            // Người nhận
            $mail->addAddress($to);
            
            // Nội dung
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            throw new \Exception("Không thể gửi email: " . $e->getMessage());
        }
    }
} 