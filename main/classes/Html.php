<?php

/**
 * Хелпер для безопасного вывода в шаблонах.
 * Заменяет антипаттерн "функция e()".
 */
final class Html
{
    /**
     * Экранирует строку для вывода в HTML-контексте.
     */
    public static function esc(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}