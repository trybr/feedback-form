<?php
return [
    "smtp" => [
        "host" => "smtp.example.com",
        "port" => 587,
        "username" => "no-reply@example.com",
        "password" => "REAL_PASSWORD",
        "secure" => "tls",
    ],
    "from" => ["no-reply@example.com" => "Сайт"],
    "to" => ["manager@example.com" => "Менеджер"],
    "subject" => "Новое сообщение с формы обратной связи",
];
