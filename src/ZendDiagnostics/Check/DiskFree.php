<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use InvalidArgumentException;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Warning;

/**
 * Check if there is enough remaining free disk space.
 *
 * String to byte size conversion borrowed from Jerity project:
 *     https://github.com/jerity/jerity/blob/master/src/Util/Number.php
 *     authors:   Dave Ingram <dave@dmi.me.uk>, Nick Pope <nick@nickpope.me.uk>
 *     license:   http://creativecommons.org/licenses/BSD/ CC-BSD
 *     copyright: Copyright (c) 2010, Dave Ingram, Nick Pope
 */
class DiskFree extends AbstractCheck implements CheckInterface
{
    /**
     * Minimum number of bytes
     *
     * @var int
     */
    protected $minDiskBytes;

    /**
     * SI prefix symbols for units of information.
     *
     * @var  array
     */
    public static $siPrefixSymbol = array('', 'k', 'M', 'G', 'T', 'P', 'E', 'Z');

    /**
     * SI prefix names for units of information.
     *
     * @var  array
     */
    public static $siPrefixName = array('', 'kilo', 'mega', 'giga', 'tera', 'peta', 'exa', 'zetta');

    /**
     * SI multiplier for units of information SI prefixes.
     *
     * @var  array
     */
    public static $siMultiplier = array(
        0 => 1e0, # 10^0  == 1000^0 (2^00 == 1024^0)
        1 => 1e3, # 10^3  == 1000^1 (2^10 == 1024^1)
        2 => 1e6, # 10^6  == 1000^2 (2^20 == 1024^2)
        3 => 1e9, # 10^9  == 1000^3 (2^30 == 1024^3)
        4 => 1e12, # 10^12 == 1000^4 (2^40 == 1024^4)
        5 => 1e15, # 10^15 == 1000^5 (2^50 == 1024^5)
        6 => 1e18, # 10^18 == 1000^6 (2^60 == 1024^6)
        7 => 1e21 # 10^21 == 1000^7 (2^70 == 1024^7)
    );

    /**
     * IEC binary prefix symbols for units of information.
     *
     * @var  array
     */
    public static $iecPrefixSymbol = array('', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei');

    /**
     * IEC binary prefix symbols for units of information.
     *
     * @var  array
     */
    public static $iecPrefixName = array('', 'kibi', 'mebi', 'gibi', 'tebi', 'pebi', 'exbi');

    /**
     * IEC multiplier for units of information IEC binary prefixes.
     *
     * @var  array
     */
    public static $iecMultiplier = array(
        0 => 1, # 2^00 == 1024^0
        1 => 1024, # 2^10 == 1024^1
        2 => 1048576, # 2^20 == 1024^2
        3 => 1073741824, # 2^30 == 1024^3
        4 => 1099511627776, # 2^40 == 1024^4
        5 => 1125899906842624, # 2^50 == 1024^5
        6 => 1152921504606846976 # 2^60 == 1024^6
    );

    /**
     * JEDEC memory standards prefixes for units of information.
     *
     * @var  array
     */
    public static $jedecPrefixSymbol = array('', 'K', 'M', 'G');

    /**
     * JEDEC memory standards prefixes for units of information.
     *
     * @var  array
     */
    public static $jedecPrefixName = array('', 'kilo', 'mega', 'giga');

    /**
     * JEDEC multiplier for units of information JEDEC memory standards prefixes.
     *
     * @var  array
     */
    public static $jedecMultiplier = array(
        0 => 1, # 2^00 == 1024^0
        1 => 1024, # 2^10 == 1024^1
        2 => 1048576, # 2^20 == 1024^2
        3 => 1073741824 # 2^30 == 1024^3
    );

    /**
     * @param  int|string                $size Minimum disk size in bytes or a valid byte string (IEC, SI or Jedec).
     * @param  string                    $path The disk path to check, i.e. '/tmp' or 'C:' (defaults to /)
     * @throws \InvalidArgumentException
     */
    public function __construct($size, $path = '/')
    {
        if (!is_scalar($size)) {
            throw new InvalidArgumentException('Invalid free disk space argument - expecting a positive number');
        }

        if (!is_string($path)) {
            throw new InvalidArgumentException('Invalid disk path argument - expecting a string');
        }

        if (is_numeric($size)) {
            $this->minDiskBytes = (int) $size;
        } else {
            $this->minDiskBytes = static::stringToBytes($size);
        }

        if ($size <= 0) {
            throw new InvalidArgumentException('Invalid free disk space argument - expecting a positive number');
        }

        $this->path = $path;
    }

    /**
     * Perform the check
     *
     * @see \ZendDiagnostics\Check\CheckInterface::check()
     * @return Failure|Success|Warning
     */
    public function check()
    {
        // We are using error suppression because the method will trigger a warning
        // in case of non-existent paths and other errors. We are more interested in
        // the potential return value of FALSE, which will tell us that free space
        // could not be obtained and we do not care about the real cause of this.
        $free = @ disk_free_space($this->path);

        if ($free === false || !is_float($free) || $free < 0) {
            return new Warning('Unable to determine free disk space at ' . $this->path);
        }

        $freeHumanReadable = static::bytesToString($free, 2);
        $description = sprintf('Remaining space at %s: %s', $this->path, $freeHumanReadable);

        if (disk_free_space($this->path) < $this->minDiskBytes) {
            return new Failure($description, $free);
        }

        return new Success($description, $free);
    }

    /**
     * Converts int bytes to a highest, rounded, multiplication that is IEC compliant.
     *
     * @link https://en.wikipedia.org/wiki/Binary_prefix
     *
     * @param  int    $size      Number of bytes to convert
     * @param  int    $precision Rounding precision (defaults to 0)
     * @return string Highest rounded multiplication with IEC suffix
     */
    public static function bytesToString($size, $precision = 0)
    {
        if ($size >= 1125899906842624) {
            $size /= 1125899906842624;
            $suffix = 'PiB';
        } elseif ($size >= 1099511627776) {
            $size /= 1099511627776;
            $suffix = 'TiB';
        } elseif ($size >= 1073741824) {
            $size /= 1073741824;
            $suffix = 'GiB';
        } elseif ($size >= 1048576) {
            $size /= 1048576;
            $suffix = 'MiB';
        } elseif ($size >= 1024) {
            $size /= 1024;
            $suffix = 'KiB';
        } else {
            $suffix = 'B';
        }

        return round($size, $precision) . ' ' . $suffix;
    }

    /**
     * Parses a string specifying a size of information and converts it to bytes.
     * Expects a string with a value followed by a symbol or named unit with an
     * optional space in between.
     *
     * @param  string                   $string The string to parse.
     * @param  bool                     $jedec  Whether to prefer JEDEC over SI units.
     * @throws InvalidArgumentException
     * @return int                      The number of bytes.
     */
    public static function stringToBytes($string, $jedec = false)
    {
        // Prepare regular expression.
        $symbol = '(?:([kKMGTPEZ])(i)?)?([Bb])?(?:ps)?';
        $name = '(' . implode('|', array_unique(array_merge(
            self::$siPrefixName,
            self::$iecPrefixName,
            self::$jedecPrefixName
        ))) . ')?(bytes?|bits?)?';

        // Attempt to match the string.
        if (!preg_match('/^(\d+(?:\.\d+)?) *(?:' . $symbol . '|' . $name . ')$/', $string, $match)) {
            throw new InvalidArgumentException('Invalid byte size provided - unable to parse.');
        }

        // The value in the provided units.
        $bytes = $match[1];
        if (isset($match[5]) && $match[5] || isset($match[2]) && $match[2]) {
            // Check for prefix (by name).
            if (isset($match[5]) && $match[5]) {
                $k = strtolower($match[5]);
                if (in_array($k, self::$iecPrefixName)) {
                    $a =& self::$iecPrefixName;
                    $x =& self::$iecMultiplier;
                } elseif (in_array($k, self::$jedecPrefixName) && $jedec) {
                    $a =& self::$jedecPrefixName;
                    $x =& self::$jedecMultiplier;
                } elseif (in_array($k, self::$siPrefixName)) {
                    $a =& self::$siPrefixName;
                    $x =& self::$siMultiplier;
                }
            }
            // Check for prefix (by symbol).
            if (isset($match[2]) && $match[2]) {
                $k = $match[2];
                if (isset($match[3]) && $match[3] == 'i') {
                    $a =& self::$iecPrefixSymbol;
                    $x =& self::$iecMultiplier;
                    $k .= $match[3];
                } elseif ($jedec) {
                    $a =& self::$jedecPrefixSymbol;
                    $x =& self::$jedecMultiplier;
                } else {
                    $a =& self::$siPrefixSymbol;
                    $x =& self::$siMultiplier;
                }
            }
            // Find the correct multiplier and apply it.
            $i = array_search($k, $a, true);
            if ($i === false || !isset($x[$i])) {
                throw new InvalidArgumentException('Invalid multiplier: ' . $k . ' not one of ' . implode(', ', $a) . '.');
            }
            $bytes *= $x[$i];
        }

        // Check whether we were provided with bits or bytes - divide if needed.
        if (
            isset($match[4]) && $match[4] == 'b' ||
            isset($match[6]) && substr(strtolower($match[6]), 0, 3) == 'bit'
        ) {
            $bytes /= 8;
        }

        // Return the number of bytes.
        return $bytes;
    }
}
