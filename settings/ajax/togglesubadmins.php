<?php

OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

$userManager = \OC::$server->getUserManager();
$groupManager = \OC::$server->getGroupManager();
$subAdminManager = \OC::$server->getSubAdminManager();
$user = $userManager->get($_POST['username']);
$group = $groupManager->get($_POST['group']);

// Toggle group
if($subAdminManager->isSubAdminOfGroup($user, $group)) {
	$subAdminManager->deleteSubAdmin($user, $group);
}else{
	$subAdminManager->createSubAdmin($user, $group);
}

OC_JSON::success();
