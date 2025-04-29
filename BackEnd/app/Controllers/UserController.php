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
        $this->response($users);
    }

    public function create()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['name']) || !isset($data['email'])) {
            $this->response(['message' => 'Missing name or email'], 400);
            return;
        }

        $user = new User();
        $user->create($data['name'], $data['email']);
        $this->response(['message' => 'User created successfully']);
    }
}
