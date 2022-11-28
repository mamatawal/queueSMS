<?php declare(strict_types=1);

namespace AndrewBreksa\RSMQ;

/**
 * @param int $length
 * @return string
 */
function makeID(int $length): string
{
    $text  = '';
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    for ($i = 0; $i < $length; $i++) {
        $text .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $text;
}

/**
 * @param int $num
 * @param int $count
 * @return string
 */
function formatZeroPad(int $num, int $count): string
{
    $numStr = (string)((10 ** $count) + $num);
    return substr($numStr, 1);
}