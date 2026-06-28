<?php

namespace App\Enums;

enum DatabaseDriver: string
{
    case Mysql = 'mysql';
    case Mariadb = 'mariadb';
    case Sqlite = 'sqlite';

    public function label(): string
    {
        return match ($this) {
            self::Mysql => 'MySQL',
            self::Mariadb => 'MariaDB',
            self::Sqlite => 'SQLite',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $driver) => [$driver->value => $driver->label()])
            ->all();
    }
}
