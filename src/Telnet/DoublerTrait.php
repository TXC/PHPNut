<?php

declare(strict_types=1);

namespace TXC\NUT\Telnet;

trait DoublerTrait
{
    /**
     * Doubling any characters.
     */
    public function doubleCharacter(string $buffer, CharacterInterface $character): string
    {
        if (str_contains($buffer, $character->chr())) {
            $buffer = str_replace(
                $character->chr(),
                $character->chr() . $character->chr(),
                $buffer
            );
        }
        return $buffer;
    }
}
