<?php

use PHPUnit\Framework\TestCase;
use function AndrewBreksa\RSMQ\{formatZeroPad, makeID};

class FunctionsTest extends TestCase
{

    public function testMakeID(): void
    {
        $size = 20;
        $this->assertSame($size, strlen(makeID($size)));
    }

    /**
     * @param string $expected
     * @param int    $num
     * @param int    $count
     * @dataProvider providerFormatZeroPad
     */
    public function testFormatZeroPad($expected, $num, $count): void
    {
        $this->assertSame($expected, formatZeroPad($num, $count));
    }

    /**
     * @return array<int, array>
     */
    public function providerFormatZeroPad(): array
    {
        return [
            ['01', 1, 2],
            ['001', 1, 3],
            ['0001', 1, 4],
            ['00001', 1, 5],
            ['000001', 1, 6],
            ['000451', 451, 6],
            ['123456', 123456, 6],
            ['0000123456', 123456, 10],
        ];
    }
}