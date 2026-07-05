<?php

namespace Database\Seeders\Support;

class DemoCopyrightParser
{
    public static function parse(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = (string) preg_replace('/^pexels-/', '', $name);

        while (preg_match('/-\d+$/', $name)) {
            $name = (string) preg_replace('/-\d+$/', '', $name);
        }

        $name = str_replace('-', ' ', $name);

        return ucwords($name).' / Pexels';
    }
}
