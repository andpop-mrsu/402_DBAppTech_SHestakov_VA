<?php
namespace Baklaniso\ColdHot\Game;


class Game
{
    public function generateSecret(): string
    {
        $digits = range(0, 9);
        // Первая цифра не может быть нулём
        do {
            $first = $digits[array_rand($digits)];
        } while ($first === 0);

        $available = array_values(array_diff($digits, [$first]));
        $second = $available[array_rand($available)];
        $available = array_values(array_diff($available, [$second]));
        $third = $available[array_rand($available)];

        return (string)$first . (string)$second . (string)$third;
    }

    public function evaluateGuess(string $secret, string $guess): array
    {
        $hints = [];
        for ($i = 0; $i < 3; $i++) {
            $g = $guess[$i];
            if ($g === $secret[$i]) {
                $hints[] = 'Горячо';
            } elseif (strpos($secret, $g) !== false) {
                $hints[] = 'Тепло';
            } else {
                $hints[] = 'Холодно';
            }
        }
        return $hints;
    }

    public static function sortHintsAlphabetically(array $hints): array
    {
        $priority = [
            'Горячо'  => 1,
            'Тепло'   => 2,
            'Холодно' => 3,
        ];

        usort($hints, fn($a, $b) => ($priority[$a] ?? 99) <=> ($priority[$b] ?? 99));
        return $hints;
    }
}
