<?php

final class Config
{
    /**
     * Возвращает значение константы или дефолт.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return defined($key) ? constant($key) : $default;
    }

    public static function appName(): string
    {
        return (string) self::get('APP_NAME', 'App');
    }

    public static function isDebug(): bool
    {
        return (bool) self::get('APP_DEBUG', false);
    }
}