<?php

/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\ModuleManager\Feature;

/**
 * @author userator
 */
interface DiagnosticsProviderInterface
{

	/**
	 * Expected to return \Zend\ServiceManager\Config object or array to
	 * seed such an object.
	 *
	 * @return array|\Zend\ServiceManager\Config
	 */
	public function getDiagnosticsConfig();
}