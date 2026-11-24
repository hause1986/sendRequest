<?php

declare(strict_types=1);

require_once __DIR__ . '/main/config.cfg';
require_once __DIR__ . '/main/autoload.php';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title><?= Html::esc(Config::appName()) ?></title>
    <link rel="icon" href="./img/favicon.ico">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="site-header__inner">
            <span class="logo-dot">RQ</span>
            <div>
                <h1><?= Html::esc(Config::appName()) ?></h1>
                <p class="site-subtitle">Проверка HTTP-запросов через серверный cURL</p>
            </div>
        </div>
    </header>

    <main class="site-main">
        <section class="card">
            <h2 class="card__title">1. URL запроса</h2>
            <div class="field">
                <label for="request-url" class="field__label">HTML-ссылка (без GET-параметров)</label>
                <input
                    type="text"
                    id="request-url"
                    class="field__input"
                    placeholder="https://api.example.com/endpoint"
                    autocomplete="off"
                >
            </div>

            <div class="field">
                <span class="field__label">Метод запроса</span>
                <div class="radio-group">
                    <label class="radio"><input type="radio" name="http-method" value="GET" checked><span>GET</span></label>
                    <label class="radio"><input type="radio" name="http-method" value="POST"><span>POST</span></label>
                    <label class="radio"><input type="radio" name="http-method" value="PUT"><span>PUT</span></label>
                    <label class="radio"><input type="radio" name="http-method" value="PATCH"><span>PATCH</span></label>
                    <label class="radio"><input type="radio" name="http-method" value="DELETE"><span>DELETE</span></label>
                </div>
            </div>
        </section>

        <section class="card">
            <h2 class="card__title">2. Параметры запроса</h2>
            <p class="card__hint">
                GET/DELETE/HEAD — параметры уходят в URL. POST/PUT/PATCH — в тело запроса (JSON).
            </p>
            <div id="params-list" class="kv-list"></div>
            <button type="button" id="add-param" class="btn btn--secondary">+ Добавить параметр</button>
        </section>

        <section class="card">
            <h2 class="card__title">3. Заголовки</h2>
            <p class="card__hint">
                Список «ключ — значение» для токенов и авторизации.
                Текстовое поле ниже — для «сырых» заголовков в формате <code>Name: value</code>.
            </p>
            <div id="headers-list" class="kv-list"></div>
            <button type="button" id="add-header" class="btn btn--secondary">+ Добавить заголовок</button>

            <div class="field field--spaced">
                <label for="raw-headers" class="field__label">Сырые заголовки (необязательно)</label>
                <textarea
                    id="raw-headers"
                    class="field__textarea"
                    rows="4"
                    placeholder="Authorization: Bearer YOUR_TOKEN&#10;X-Custom-Header: value"
                ></textarea>
            </div>
        </section>

        <section class="card">
            <div class="actions">
                <button type="button" id="send-request" class="btn btn--primary">Отправить запрос</button>
                <button type="button" id="clear-form" class="btn btn--ghost">Очистить</button>
            </div>
        </section>

        <section class="card">
            <h2 class="card__title">4. Ответ</h2>
            <div class="response-meta">
                <span id="response-status" class="response-status">—</span>
                <span id="response-time" class="response-time">—</span>
            </div>
            <pre id="response-output" class="response-output">Здесь появится ответ сервера…</pre>
        </section>
    </main>

    <script src="./js/script.js"></script>
</body>
</html>