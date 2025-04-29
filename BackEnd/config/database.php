<?php

return new PDO('mysql:host=localhost;dbname=appchat;charset=utf8', 'root', '123456', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
