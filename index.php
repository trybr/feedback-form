<?php
session_start();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION["csrf_token"];

$captchaCode = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 5);
$_SESSION["captcha_code"] = $captchaCode;

// Список тем для select
$topics = [
    "order" => "Вопрос по заказу",
    "support" => "Техподдержка",
    "partner" => "Сотрудничество",
    "other" => "Другое",
];

$oldTopic = $_POST["topic"] ?? "";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Форма обратной связи">
    <title>Обратная связь</title>

    <link rel="icon" type="image/png" href="/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="TestForm" />

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<main class="page">
    <h1 class="page__title">Обратная связь</h1>

    <form id="feedbackForm"
          class="form"
          action="inc/handler.php"
          method="post"
          novalidate>

        <!-- Действие (hidden) -->
        <input type="hidden" name="action" value="message">

        <!-- CSRF токен (hidden) -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
            $csrfToken,
            ENT_QUOTES,
            "UTF-8",
        ) ?>">

        <!-- Honeypot (hidden) -->
        <div class="form__honeypot" aria-hidden="true">
            <label for="website">Специальное поле</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <!-- Тема -->
        <div class="form__row">
            <label class="form__label" for="topic">
                Тема <span class="req" aria-hidden="true">*</span>
            </label>
            <select id="topic" name="topic" class="form__control" required>
                <option value="">— выберите тему —</option>
                <?php foreach ($topics as $value => $label): ?>
                    <option value="<?= htmlspecialchars(
                        $value,
                        ENT_QUOTES,
                        "UTF-8",
                    ) ?>"
                        <?= $oldTopic === $value ? "selected" : "" ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="form__error" data-error-for="topic" aria-live="polite"></span>
        </div>

        <!-- ФИО -->
        <div class="form__row">
            <div class="form__header">
                <label class="form__label" for="fio">
                    ФИО <span class="req" aria-hidden="true">*</span>
                </label>
                <span class="form__counter" data-counter-for="fio">0 / 255</span>
            </div>
            <input type="text"
                   id="fio"
                   name="fio"
                   class="form__control"
                   maxlength="255"
                   data-max="255"
                   data-counter="255"
                   autocomplete="name"
                   required>
            <span class="form__error" data-error-for="fio" aria-live="polite"></span>
        </div>

        <!-- Телефон -->
        <div class="form__row">
            <label class="form__label" for="phone">
                Телефон <span class="req" aria-hidden="true">*</span>
            </label>
            <input type="tel"
                   id="phone"
                   name="phone"
                   class="form__control"
                   placeholder="+7 (___) ___-__-__"
                   autocomplete="tel"
                   maxlength="11"
                   required>
            <span class="form__error" data-error-for="phone" aria-live="polite"></span>
        </div>

        <!-- E-mail -->
        <div class="form__row">
            <label class="form__label" for="email">
                E-mail <span class="req" aria-hidden="true">*</span>
            </label>
            <input type="email"
                   id="email"
                   name="email"
                   class="form__control"
                   maxlength="255"
                   data-email
                   autocomplete="email"
                   required>
            <span class="form__error" data-error-for="email" aria-live="polite"></span>
        </div>

        <!-- Сообщение -->
        <div class="form__row">
            <div class="form__header">
                <label class="form__label" for="message">
                    Сообщение <span class="req" aria-hidden="true">*</span>
                </label>
                <span class="form__counter" data-counter-for="message">0 / 4096</span>
            </div>
            <textarea id="message"
                      name="message"
                      class="form__control"
                      rows="6"
                      maxlength="4096"
                      data-max="4096"
                      data-counter="4096"
                      required></textarea>
            <span class="form__error" data-error-for="message" aria-live="polite"></span>
        </div>

        <div class="form__row form__row--captcha">
            <div class="form__header">
                <label class="form__label" for="captcha">
                    Captcha <span class="req" aria-hidden="true">*</span>
                </label>
            </div>
            <div class="captcha">
                <img src="inc/captcha.php?<?= time() ?>"
                     alt="Код с картинки"
                     id="captchaImg"
                     class="captcha__img"
                     width="130"
                     height="44">
                <button type="button"
                        class="captcha__refresh"
                        id="captchaRefresh"
                        title="Обновить код"
                        aria-label="Обновить код с картинки">↻</button>
                <input type="text"
                       id="captcha"
                       name="captcha"
                       class="form__control captcha__input"
                       maxlength="5"
                       autocomplete="off"
                       required>
            </div>
            <span class="form__error" data-error-for="captcha" aria-live="polite"></span>
        </div>

        <!-- Согласие на обработку персональных данных -->
        <div class="form__row form__row--checkbox">
            <label class="checkbox">
                <input type="checkbox"
                       id="agree"
                       name="agree"
                       value="1"
                       required>
                <span>
                    Согласен на обработку персональных данных
                    <span class="req" aria-hidden="true">*</span>
                </span>
            </label>
            <span class="form__error" data-error-for="agree" aria-live="polite"></span>
        </div>

        <!-- Кнопка -->
        <div class="form__row form__row--submit">
            <button type="submit" class="btn" id="submitBtn">Отправить</button>
        </div>

        <!-- Общее уведомление -->
        <div class="form__result" id="formResult" role="status" aria-live="polite"></div>
    </form>
</main>

<script src="assets/js/mask.js"></script>
<script src="assets/js/validate.js"></script>
<script src="assets/js/ajax.js"></script>
</body>
</html>
