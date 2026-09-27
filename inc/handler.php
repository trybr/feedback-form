<?php
declare(strict_types=1);

session_start();
require __DIR__ . "/../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Content-Type: application/json; charset=utf-8");

$config = require __DIR__ . "/config.php";

function jsonOut(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function fail(
    array $errors,
    string $message = "Проверьте правильность заполнения полей.",
): void {
    jsonOut(["success" => false, "message" => $message, "errors" => $errors]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    fail([], "Некорректный метод запроса.");
}

$action = trim((string) ($_POST["action"] ?? ""));
$topic = trim((string) ($_POST["topic"] ?? ""));
$fio = trim((string) ($_POST["fio"] ?? ""));
$phone = trim((string) ($_POST["phone"] ?? ""));
$email = trim((string) ($_POST["email"] ?? ""));
$message = trim((string) ($_POST["message"] ?? ""));
$captcha = strtoupper(trim((string) ($_POST["captcha"] ?? "")));
$agree = isset($_POST["agree"]) ? 1 : 0;

$errors = [];

// CSRF токен
$csrf = $_POST["csrf_token"] ?? "";
if (
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $csrf)
) {
    $errors["csrf_token"] = "Сессия устарела, обновите страницу.";
}

// honeypot
if (!empty($_POST["website"])) {
    // бот заполнил поле — молча отбрасываем
    jsonOut(["success" => true, "message" => "Сообщение отправлено."]);
}

// action
if ($action !== "message") {
    $errors["action"] = "Некорректное действие.";
}

// тема
$allowedTopics = ["order", "support", "partner", "other"];
if ($topic === "" || !in_array($topic, $allowedTopics, true)) {
    $errors["topic"] = "Выберите тему обращения.";
}

// ФИО
if ($fio === "") {
    $errors["fio"] = "Укажите ФИО.";
} elseif (mb_strlen($fio) > 255) {
    $errors["fio"] = "Максимум 255 символов.";
}

// телефон
if ($phone === "") {
    $errors["phone"] = "Укажите телефон.";
} else {
    $digits = preg_replace("/\D/", "", $phone);
    if (!preg_match('/^7\d{10}$/', $digits)) {
        $errors["phone"] = "Телефон в формате +7 (___) ___-__-__.";
    }
}

// email
if ($email === "") {
    $errors["email"] = "Укажите e-mail.";
} elseif (mb_strlen($email) > 255) {
    $errors["email"] = "Максимум 255 символов.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors["email"] = "Некорректный e-mail.";
}

// сообщение
if ($message === "") {
    $errors["message"] = "Введите сообщение.";
} elseif (mb_strlen($message) > 4096) {
    $errors["message"] = "Максимум 4096 символов.";
}

// captcha
$expected = $_SESSION["captcha_code"] ?? "";
if ($captcha === "") {
    $errors["captcha"] = "Введите код с картинки.";
} elseif ($expected === "" || $captcha !== strtoupper($expected)) {
    $errors["captcha"] = "Неверный код с картинки.";
}

// сбрасываем код
unset($_SESSION["captcha_code"]);

// согласие
if (!$agree) {
    $errors["agree"] = "Необходимо согласие на обработку персональных данных.";
}

if (!empty($errors)) {
    fail($errors);
}

// Письмо
$topicLabels = [
    "order" => "Вопрос по заказу",
    "support" => "Техподдержка",
    "partner" => "Сотрудничество",
    "other" => "Другое",
];

$body =
    "Новое сообщение с формы обратной связи\n\n" .
    "Тема: " .
    $topicLabels[$topic] .
    "\n" .
    "ФИО: " .
    $fio .
    "\n" .
    "Телефон: " .
    $phone .
    "\n" .
    "E-mail: " .
    $email .
    "\n" .
    "Сообщение:\n" .
    $message .
    "\n\n" .
    "IP: " .
    ($_SERVER["REMOTE_ADDR"] ?? "-") .
    "\n" .
    "Дата: " .
    date("Y-m-d H:i:s");

// отправка сообщения на почту (нужен SMTP)

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $config["smtp"]["host"];
    $mail->Port = (int) $config["smtp"]["port"];
    $mail->SMTPAuth = true;
    $mail->Username = $config["smtp"]["username"];
    $mail->Password = $config["smtp"]["password"];
    $mail->SMTPSecure = $config["smtp"]["secure"];
    $mail->CharSet = "UTF-8";

    foreach ($config["from"] as $addr => $name) {
        $mail->setFrom($addr, $name);
    }
    foreach ($config["to"] as $addr => $name) {
        $mail->addAddress($addr, $name);
    }

    $mail->addReplyTo($email, $fio);
    $mail->Subject = $config["subject"];
    $mail->Body = $body;
    $mail->send();

    jsonOut([
        "success" => true,
        "message" =>
            "Сообщение отправлено. Мы свяжемся с вами в ближайшее время.",
    ]);
} catch (Exception $e) {
    jsonOut([
        "success" => false,
        "message" => "Не удалось отправить сообщение. Попробуйте позже.",
        "errors" => [],
    ]);
}

// отправка сообщения в лог
// file_put_contents(__DIR__ . "/../mail.log", $body . "\n\n---\n\n", FILE_APPEND);

jsonOut([
    "success" => true,
    "message" => "Сообщение отправлено (в лог).",
]);
