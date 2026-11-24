<?php

/**
 * Простой автозагрузчик для классов из main/classes.
 * Регистрируется один раз в точке входа.
 */
spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});