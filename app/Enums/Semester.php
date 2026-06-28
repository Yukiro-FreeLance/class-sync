<?php

namespace App\Enums;

enum Semester: string
{
    case First = 'first';
    case Second = 'second';
    case Summer = 'summer';

    public function label(): string
    {
        return match ($this) {
            self::First => 'First Semester',
            self::Second => 'Second Semester',
            self::Summer => 'Summer',
        };
    }

    /**
     * @return list<string>
     */
    public static function defaultValues(): array
    {
        return [self::First->value, self::Second->value];
    }

    /**
     * @param  list<string>  $values
     * @return array<string, string>
     */
    public static function optionsForValues(array $values): array
    {
        if ($values === []) {
            return self::options();
        }

        return collect(self::cases())
            ->filter(fn (self $semester) => in_array($semester->value, $values, true))
            ->mapWithKeys(fn (self $semester) => [$semester->value => $semester->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $semester) => [$semester->value => $semester->label()])
            ->all();
    }
}
