<?php

namespace Database\Seeders\Support\Handbook;

class Chapters
{
    /**
     * All handbook chapters keyed by their position within the handbook issue.
     *
     * @return array<int, array{title: string, articles: list<array{title: string, content: list<array<string, mixed>>}>}>
     */
    public static function all(): array
    {
        return [
            1 => Chapter01Einfuehrung::chapter(),
            2 => Chapter02ErsteSchritte::chapter(),
            3 => Chapter03RollenUndRechte::chapter(),
            4 => Chapter04Workflow::chapter(),
            5 => Chapter05Autoren::chapter(),
            6 => Chapter06Produktmanager::chapter(),
            7 => Chapter07Lektorat::chapter(),
            8 => Chapter08Editor::chapter(),
            9 => Chapter09Administratoren::chapter(),
            10 => Chapter10Entwickler::chapter(),
        ];
    }
}
