<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Runner\Reporter;

abstract class ConsoleColor
{
    const NORMAL = 0;
    const RESET = 0;

    const BLACK = 1;
    const RED = 2;
    const GREEN = 3;
    const YELLOW = 4;
    const BLUE = 5;
    const MAGENTA = 6;
    const CYAN = 7;
    const WHITE = 8;

    const GRAY = 9;
    const LIGHT_RED = 10;
    const LIGHT_GREEN = 11;
    const LIGHT_YELLOW = 12;
    const LIGHT_BLUE = 13;
    const LIGHT_MAGENTA = 14;
    const LIGHT_CYAN = 15;
    const LIGHT_WHITE = 16;

    public static $ansiColorMap = array(
        'fg' => array(
            self::NORMAL        => '22;39',
            self::RESET         => '22;39',

            self::BLACK         => '0;30',
            self::RED           => '0;31',
            self::GREEN         => '0;32',
            self::YELLOW        => '0;33',
            self::BLUE          => '0;34',
            self::MAGENTA       => '0;35',
            self::CYAN          => '0;36',
            self::WHITE         => '0;37',

            self::GRAY          => '1;30',
            self::LIGHT_RED     => '1;31',
            self::LIGHT_GREEN   => '1;32',
            self::LIGHT_YELLOW  => '1;33',
            self::LIGHT_BLUE    => '1;34',
            self::LIGHT_MAGENTA => '1;35',
            self::LIGHT_CYAN    => '1;36',
            self::LIGHT_WHITE   => '1;37',
        ),
        'bg' => array(
            self::NORMAL        => '0;49',
            self::RESET         => '0;49',

            self::BLACK         => '40',
            self::RED           => '41',
            self::GREEN         => '42',
            self::YELLOW        => '43',
            self::BLUE          => '44',
            self::MAGENTA       => '45',
            self::CYAN          => '46',
            self::WHITE         => '47',

            self::GRAY          => '40',
            self::LIGHT_RED     => '41',
            self::LIGHT_GREEN   => '42',
            self::LIGHT_YELLOW  => '43',
            self::LIGHT_BLUE    => '44',
            self::LIGHT_MAGENTA => '45',
            self::LIGHT_CYAN    => '46',
            self::LIGHT_WHITE   => '47',
        ),
    );
}
