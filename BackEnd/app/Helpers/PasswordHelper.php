<?php

namespace Helpers;

class PasswordHelper
{
    public static function validatePassword($password)
    {
        // Kiểm tra độ dài tối thiểu 8 ký tự
        if (strlen($password) < 8) {
            return false;
        }

        // Kiểm tra có chứa ít nhất 1 chữ hoa
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Kiểm tra có chứa ít nhất 1 chữ thường
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Kiểm tra có chứa ít nhất 1 số
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Kiểm tra có chứa ít nhất 1 ký tự đặc biệt
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return false;
        }

        return true;
    }

    public static function getPasswordRequirements()
    {
        return [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_number' => true,
            'require_special_char' => true
        ];
    }
} 