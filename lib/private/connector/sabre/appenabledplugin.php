<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Connector\Sabre;

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ServerPlugin;

class AppEnabledPlugin extends ServerPlugin {

	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var string
	 */
	private $app;

	/**
	 * @var \OCP\IUserSession
	 */
	private $userSession;

	/**
	 * @var \OCP\IConfig
	 */
	private $config;

	/**
	 * @var \OCP\IGroupManager
	 */
	private $groupManager;

	/**
	 * @param string $app
	 * @param \OCP\IUserSession $userSession
	 * @param \OCP\IConfig $config
	 * @param \OCP\IGroupManager $groupManager
	 */
	public function __construct($app, $userSession, $config, $groupManager) {
		$this->app = $app;
		$this->userSession = $userSession;
		$this->config = $config;
		$this->groupManager = $groupManager;
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param \Sabre\DAV\Server $server
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {

		$this->server = $server;
		$this->server->subscribeEvent('beforeMethod', array($this, 'checkAppEnabled'), 30);
	}

	/**
	 * This method is called before any HTTP after auth and checks if the user has access to the app
	 *
	 * @throws \Sabre\DAV\Exception\Forbidden
	 * @return bool
	 */
	public function checkAppEnabled() {
		$user = $this->userSession->getUser();
		if (!$user) {
			throw new Forbidden();
		}

		$enabled = $this->config->getAppValue($this->app, 'enabled', 'no');
		if ($enabled === 'yes') {
			return true;
		} else if ($enabled === 'no') {
			throw new Forbidden();
		} else {
			$groups = json_decode($enabled);
			if (is_array($groups)) {
				foreach ($groups as $groupId) {
					if ($this->groupManager->get($groupId)->inGroup($user)) {
						return true;
					}
				}
			}
			throw new Forbidden();
		}
	}
}
