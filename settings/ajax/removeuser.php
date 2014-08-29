<?php

OC_JSON::checkSubAdminUser();
OCP\JSON::callCheck();

$username = $_POST["username"];

$activeUser = \OC::$server->getUserSession()->getUser();
$userManager = \OC::$server->getUserManager();
$groupManager = \OC::$server->getGroupManager();
$subAdminManager = \OC::$server->getSubAdminManager();
$user = $userManager->get($username);

// A user shouldn't be able to delete his own account
if ($user === $username) {
	exit;
}

if (!$groupManager->get('admin')->inGroup($activeUser) &&
	!$subAdminManager->isUserAccessible($activeUser, $user)
) {
	$l = OC_L10N::get('core');
	OC_JSON::error(array('data' => array('message' => $l->t('Authentication error'))));
	exit();
}

// Return Success story
if (OC_User::deleteUser($username)) {
	OC_JSON::success(array("data" => array("username" => $username)));
} else {
	$l = OC_L10N::get('core');
	OC_JSON::error(array("data" => array("message" => $l->t("Unable to delete user"))));
}
