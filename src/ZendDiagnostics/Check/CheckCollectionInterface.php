<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use Traversable;

interface CheckCollectionInterface
{
    /**
     * Return a list of CheckInterface's.
     *
     * @return array|Traversable
     */
    public function getChecks();
}
