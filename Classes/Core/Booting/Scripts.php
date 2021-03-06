<?php
namespace Helhum\Typo3Console\Core\Booting;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Error\ErrorHandler;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class Scripts
 */
class Scripts {

	/**
	 * @var array
	 */
	static protected $earlyCachesConfiguration = array();

	/**
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function initializeConfigurationManagement(ConsoleBootstrap $bootstrap) {
		$bootstrap->initializeConfigurationManagement();

		// TODO: check if it is smart to load configuration from required extensions (e.g. Extbase) here

		self::$earlyCachesConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
		$bootstrap->disableCachesForObjectManagement();
	}

	/**
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function initializeErrorHandling(ConsoleBootstrap $bootstrap) {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = '';
		$errorHandler = new ErrorHandler();
//		$errorHandler->setExceptionalErrors(array(E_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_STRICT, E_RECOVERABLE_ERROR));
		$errorHandler->setExceptionalErrors(array(E_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_RECOVERABLE_ERROR));
		ini_set('display_errors', 1);
		if (((bool)ini_get('display_errors') && strtolower(ini_get('display_errors')) !== 'on' && strtolower(ini_get('display_errors')) !== '1') || !(bool)ini_get('display_errors')) {
			echo 'WARNING: Fatal errors will be suppressed due to your PHP config. You should consider enabling display_errors in your php.ini file!' . chr(10);
		}
	}

	/**
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function disableCoreCaches(ConsoleBootstrap $bootstrap) {
		$bootstrap->disableCoreCaches();
	}

	/**
	 * Reset the internal caches array in the object manager to
	 * make it rebuild the caches with new configuration.
	 *
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function reEnableOriginalCoreCaches(ConsoleBootstrap $bootstrap) {
		ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'], self::$earlyCachesConfiguration);

		/** @var CacheManager $cacheManager */
		$cacheManager = $bootstrap->getEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager');
		$cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);

		$reflectionObject = new \ReflectionObject($cacheManager);
		$property = $reflectionObject->getProperty('caches');
		$property->setAccessible(TRUE);
		$property->setValue($cacheManager, array());
	}

	/**
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function initializeCachingFramework(ConsoleBootstrap $bootstrap) {
		$bootstrap->setEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager', \TYPO3\CMS\Core\Cache\Cache::initializeCachingFramework());
		// @deprecated since 6.2 will be removed in two versions
		$GLOBALS['typo3CacheManager'] = new \TYPO3\CMS\Core\Compatibility\GlobalObjectDeprecationDecorator('TYPO3\\CMS\\Core\\Cache\\CacheManager');
		$GLOBALS['typo3CacheFactory'] = new \TYPO3\CMS\Core\Compatibility\GlobalObjectDeprecationDecorator('TYPO3\\CMS\\Core\\Cache\\CacheFactory');
	}

	/**
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function initializeDatabaseConnection(ConsoleBootstrap $bootstrap) {
		$bootstrap->initializeDatabaseConnection();
	}

	/**
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function initializeClassLoaderCaches(ConsoleBootstrap $bootstrap) {
		$bootstrap->initializeClassLoaderCaches();
		$bootstrap->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader')
			->setCacheIdentifier(md5_file(PATH_typo3conf . 'PackageStates.php'))
			->setPackages($bootstrap->getEarlyInstance('TYPO3\\Flow\\Package\\PackageManager')->getActivePackages());
	}

	/**
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function initializeExtensionConfiguration(ConsoleBootstrap $bootstrap) {
		require_once PATH_site . 'typo3/sysext/core/Classes/Core/GlobalDebugFunctions.php';
		ExtensionManagementUtility::loadExtLocalconf();
		$bootstrap->applyAdditionalConfigurationSettings();
	}

	/**
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function applyAdditionalConfigurationSettings(ConsoleBootstrap $bootstrap) {
		$bootstrap->applyAdditionalConfigurationSettings();
	}

	/**
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function initializePersistence(ConsoleBootstrap $bootstrap) {
		$bootstrap->loadExtensionTables();
	}

	/**
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function initializeAuthenticatedOperations(ConsoleBootstrap $bootstrap) {
		$bootstrap->initializeBackendUser();
		$bootstrap->initializeBackendAuthentication();
		$bootstrap->initializeBackendUserMounts();

		// Global language object on CLI? rly? but seems to be needed by some scheduler tasks :(
		$bootstrap->initializeLanguageObject();
	}

	/**
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function runLegacyBootstrap(ConsoleBootstrap $bootstrap) {
		$bootstrap->runLegacyBootstrap();
	}

	/**
	 * Provide cleaned imlementation of TYPO3 CMS core classes.
	 * Can only be called *after* extension configuration is loaded (needs extbase configuration)!
	 *
	 * @param ConsoleBootstrap $bootstrap
	 */
	static public function provideCleanClassImplementations(ConsoleBootstrap $bootstrap) {
		self::overrideImplementation('TYPO3\CMS\Extbase\Mvc\Controller\Argument', 'Helhum\Typo3Console\Mvc\Controller\Argument');
	}

	/**
	 * Tell Extbase, TYPO3 and PHP that we have another implementation
	 */
	static public function overrideImplementation($originalClassName, $overrideClassName) {
		/** @var $extbaseObjectContainer \TYPO3\CMS\Extbase\Object\Container\Container */
		$extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\Container\\Container');
		$extbaseObjectContainer->registerImplementation($originalClassName, $overrideClassName);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$originalClassName]['className'] = $overrideClassName;
		class_alias($overrideClassName, $originalClassName);
	}
}
