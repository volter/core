<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Settings;

use \OCP\AppFramework\App;

class Application extends App {

	public function __construct(array $urlParams=array()){
		parent::__construct('settings', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('SettingsController', function($c) {
			/** @var $c \OCP\IContainer  */

			return new SettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('ServerContainer')->getUserSession(),
				$c->query('ServerContainer')->getUserManager(),
				$c->query('ServerContainer')->getGroupManager(),
				$c->query('ServerContainer')->getSubAdminManager(),
				$c->query('ServerContainer')->getLogger(),
				$c->query('ServerContainer')->getConfig(),
				$c->query('ServerContainer')->getL10N('settings')
			);
		});
	}


}
