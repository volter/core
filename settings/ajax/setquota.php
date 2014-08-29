<?php
/**
 * Copyright (c) 2014, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_JSON::checkSubAdminUser();
OCP\JSON::callCheck();

$username = isset($_POST["username"]) ? $_POST["username"] : '';

$activeUser = \OC::$server->getUserSession()->getUser();
$userManager = \OC::$server->getUserManager();
$groupManager = \OC::$server->getGroupManager();
$subAdminManager = \OC::$server->getSubAdminManager();
$config = \OC::$server->getConfig();
$user = $userManager->get($username);

//make sure the quota is in the expected format
$quota = $_POST["quota"];
if ($quota !== 'none' and $quota !== 'default') {
	$quota = OC_Helper::computerFileSize($quota);
	$quota = OC_Helper::humanFileSize($quota);
}

if ($groupManager->get('admin')->inGroup($activeUser) or
	($user and $subAdminManager->isUserAccessible($activeUser, $user))
) {
	if ($username) {
		$config->setUserValue($username, 'files', 'quota', $quota);
	} else { //set the default quota when no username is specified
		if ($quota === 'default') { //'default' as default quota makes no sense
			$quota = 'none';
		}
		$config->setAppValue('files', 'default_quota', $quota);
	}
	OC_JSON::success(array("data" => array("username" => $username, 'quota' => $quota)));
} else {
	$l = OC_L10N::get('core');
	OC_JSON::error(array('data' => array('message' => $l->t('Authentication error'))));
	exit();
}
