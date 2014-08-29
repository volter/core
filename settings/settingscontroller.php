<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Settings;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\IRequest;
use \OC\User\Session;
use \OC\User\Manager as UserManager;
use \OC\Group\Manager as GroupManager;
use \OCP\ISubAdmin;
use \OCP\AppFramework\Http;
use \OCP\ILogger;

class SettingsController extends Controller {

	/**
	 * @var \OC\User\Session
	 */
	protected $userSession;

	/**
	 * @var \OCP\IUserManager
	 */
	protected $userManager;

	/**
	 * @var \OC\Group\Manager
	 */
	protected $groupManager;

	/**
	 * @var \OCP\ISubAdmin
	 */
	protected $subAdminManager;

	/**
	 * @var \OCP\ILogger;
	 */
	protected $logger;

	/**
	 * @var \OC_L10N
	 */
	protected $l;

	public function __construct($appName,
								IRequest $request,
								Session $userSession,
								UserManager $userManager,
								GroupManager $groupManager,
								ISubAdmin $subAdminManager,
								ILogger $logger,
								\OC_L10N $l
	) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->subAdminManager = $subAdminManager;
		$this->logger = $logger;
		$this->l = $l;
	}

	/**
	 * Change the displayName of a user
	 *
	 * @NoAdminRequired
	 *
	 * @param string $username
	 * @param string $displayName
	 *
	 * @return JSONResponse
	 */
	public function changeDisplayName($username, $displayName) {
		$activeUser = $this->userSession->getUser();
		$username = isset($username) ? $username : $this->userSession->getUser()->getUID();
		$user = $this->userManager->get($username);

		if ($user !== null) {
			if ($this->groupManager->get('admin')->inGroup($activeUser)
				|| $this->subAdminManager->isUserAccessible($activeUser, $user)
				|| ($user === $activeUser && $user->canChangeDisplayName())) {
					$user->setDisplayName($displayName);
					return new JSONResponse(array(
						'status' => 'success',
						'data' => array(
							'message' => (string) $this->l->t('Displayname successfully changed.'),
							'username' => $username,
							'displayName' => $displayName
						)
					));
			}
		}

		return new JSONResponse('', Http::STATUS_FORBIDDEN);
	}

	/**
	 * Enables an application
	 *
	 * @param string $app
	 * @param string $groups
	 *
	 * @return JSONResponse
	 */
	public function enableApp($app, $groups = '') {
		try {
			\OC_App::enable($app, $groups);
			return new JSONResponse(
				array(
					'status' => 'success'
				)
			);
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), array('app' => 'core'));
			return new JSONResponse($e->getMessage(), HTTP::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Disables an application
	 *
	 * @param string $app
	 *
	 * @return JSONResponse
	 */
	public function disableApp($app) {
		\OC_App::disable($app);
		return new JSONResponse(
			array(
				'status' => 'success'
			)
		);
	}

}
