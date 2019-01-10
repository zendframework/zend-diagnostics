<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

if (class_exists(Doctrine\DBAL\Migrations\Configuration\Configuration::class)
    && ! class_exists(Doctrine\Migrations\Configuration\Configuration::class)
) {
    class_alias(
        Doctrine\DBAL\Migrations\Configuration\Configuration::class,
        Doctrine\Migrations\Configuration\Configuration::class
    );
}
