<?php

namespace Core;

class Controller
{
    protected function response($data, $status = 200)
    {
        http_response_code($status);                  // Set mã trạng thái HTTP
        header('Content-Type: application/json');     // Định dạng trả về là JSON
        echo json_encode($data);                      // In ra JSON
    }
}
