<?php

OC_JSON::checkSubAdminUser();
OCP\JSON::callCheck();

$activeUser = \OC::$server->getUserSession()->getUser();
$userManager = \OC::$server->getUserManager();
$groupManager = \OC::$server->getGroupManager();
$subAdminManager = \OC::$server->getSubAdminManager();
$user = $userManager->get($_POST['username']);
$group = $groupManager->get($_POST['group']);

if ($user === $activeUser && $group->getGID() === 'admin' &&
	$group->inGroup($activeUser)
) {
	$l = OC_L10N::get('core');
	OC_JSON::error(array('data' => array('message' => $l->t('Admins can\'t remove themself from the admin group'))));
	exit();
}

if (!$groupManager->get('admin')->inGroup($activeUser)
	&& (!$subAdminManager->isUserAccessible($activeUser, $user)
		|| !$subAdminManager->isSubAdminOfGroup($activeUser, $group))
) {
	$l = OC_L10N::get('core');
	OC_JSON::error(array('data' => array('message' => $l->t('Authentication error'))));
	exit();
}

if (is_null($group)) {
	$group = $groupManager->createGroup($_POST['group']);
}

$l = OC_L10N::get('settings');

$error = $l->t("Unable to add user to group %s", $group->getGID());
$action = "add";

// Toggle group
if ($group->inGroup($user)) {
	$action = "remove";
	$error = $l->t("Unable to remove user from group %s", $group->getGID());
	$group->removeUser($user);
	if ($group->count() === 0) {
		$group->delete();
	}
} else {
	$group->addUser($user);
}

OC_JSON::success(array("data" => array("username" => $user->getUID(), "action" => $action, "groupname" => $group->getGID())));
