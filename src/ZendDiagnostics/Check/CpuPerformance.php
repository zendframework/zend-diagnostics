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
 * Calculate CPU Performance by performing a Gauss-Legendre decimal expansion of PI.
 *
 * The baseline has been calculated as the average time needed to calculate 1000 digits of PI
 * on an Amazon AWS EC2 Micro Instance.
 */
class CpuPerformance extends AbstractCheck implements CheckInterface
{
    /**
     * The baseline performance for PI calculation, in seconds
     *
     * @var float
     */
    protected $baseline = 1.0;

    /**
     * Decimal precision of PI calculation
     *
     * @var int
     */
    protected $precision = 1000;

    /**
     * Minimum performance for the check to result in a success
     *
     * @var float
     */
    protected $minPerformance = 1;

    /**
     * Expected result from calculating a PI of given decimal $precision
     *
     * @var string
     */
    protected $expectedResult = '3.1415926535897932384626433832795028841971693993751058209749445923078164062862089986280348253421170679821480865132823066470938446095505822317253594081284811174502841027019385211055596446229489549303819644288109756659334461284756482337867831652712019091456485669234603486104543266482133936072602491412737245870066063155881748815209209628292540917153643678925903600113305305488204665213841469519415116094330572703657595919530921861173819326117931051185480744623799627495673518857527248912279381830119491298336733624406566430860213949463952247371907021798609437027705392171762931767523846748184676694051320005681271452635608277857713427577896091736371787214684409012249534301465495853710507922796892589235420199561121290219608640344181598136297747713099605187072113499999983729780499510597317328160963185950244594553469083026425223082533446850352619311881710100031378387528865875332083814206171776691473035982534904287554687311595628638823537875937519577818577805321712268066130019278766111959092164201989';

    /**
     *
     * @param float $minPerformance The minimum performance ratio, where 1 is equal the computational
     *                              performance of AWS EC2 Micro Instance. For example, a value of 2 means
     *                              at least double the baseline experience, value of 0.5 means at least
     *                              half the performance. Defaults to 0.5
     * @throws InvalidArgumentException
     */
    public function __construct($minPerformance = 0.5)
    {
        $minPerformance = (float) $minPerformance;
        if ($minPerformance < 0) {
            throw new InvalidArgumentException('Invalid minimum performance - expected a positive float');
        }

        $this->minPerformance = $minPerformance;
    }

    /**
     * Run CPU benchmark and return a Success if the result is higher than minimum performance,
     * Failure if below and a warning if there was a problem with calculating the value of PI.
     *
     * @return Failure|Success|Warning
     */
    public function check()
    {
        $timeStart = microtime(true);
        $result = static::calcPi(1000);
        $duration = microtime(true) - $timeStart;
        $performance = $duration / $this->baseline;

        // Check if bcmath extension is present
        // @codeCoverageIgnoreStart
        if (!extension_loaded('bcmath')) {
            return new Warning('Check\CpuPerformance requires BCMath extension to be loaded.');
        }
        // @codeCoverageIgnoreEnd

        if ($result != $this->expectedResult) {
            // Ignore code coverage here because it's impractical to test against faulty calculations.
            // @codeCoverageIgnoreStart
            return new Warning('PI calculation failed. This might mean CPU or RAM failure', $result);
            // @codeCoverageIgnoreEnd
        } elseif ($performance > $this->minPerformance) {
            return new Success(null, $performance);
        } else {
            return new Failure(null, $performance);
        }
    }

    /**
     * Get decimal expansion of PI using Gauss-Lagendre algorithm.
     *
     * @link https://github.com/natmchugh/pi/blob/master/gauss-legendre.php
     * @link http://en.wikipedia.org/wiki/Calculate_pi#Modern_algorithms
     * @param $precision
     * @return string
     */
    public static function calcPi($precision)
    {
        $limit = ceil(log($precision) / log(2)) - 1;
        bcscale($precision + 6);
        $a = 1;
        $b = bcdiv(1, bcsqrt(2));
        $t = 1 / 4;
        $p = 1;
        for ($n = 0; $n < $limit; $n++) {
            $x = bcdiv(bcadd($a, $b), 2);
            $y = bcsqrt(bcmul($a, $b));
            $t = bcsub($t, bcmul($p, bcpow(bcsub($a, $x), 2)));
            $a = $x;
            $b = $y;
            $p = bcmul(2, $p);
        }

        return bcdiv(bcpow(bcadd($a, $b), 2), bcmul(4, $t), $precision);
    }
}
