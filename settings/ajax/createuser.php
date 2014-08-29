<?php

OCP\JSON::callCheck();
OC_JSON::checkSubAdminUser();

$activeUser = \OC::$server->getUserSession()->getUser();
$userManager = \OC::$server->getUserManager();
$groupManager = \OC::$server->getGroupManager();
$subAdminManager = \OC::$server->getSubAdminManager();
$isAdmin = $groupManager->get('admin')->inGroup($activeUser);

$targetGroups = array();
if (!empty($_POST['groups'])) {
	foreach ($_POST['groups'] as $group) {
		$groupObject = $groupManager->get($group);
		if (is_null($groupObject) and $isAdmin) {
			$groupObject = $groupManager->createGroup($group);
		}
		$targetGroups[] = $groupObject;
	}
}

if ($isAdmin) {
	$groups = $targetGroups;
} else {
	$groups = array();
	foreach ($targetGroups as $targetGroup) {
		if ($subAdminManager->isSubAdminOfGroup($activeUser, $targetGroup)) {
			$groups[] = $targetGroup;
		}
	}
	if (count($groups) === 0) {
		$groups = $subAdminManager->getSubAdminsGroups($activeUser);
	}
}
$username = $_POST["username"];
$password = $_POST["password"];

// Return Success story
try {
	// check whether the user's files home exists
	$userDirectory = OC_User::getHome($username) . '/files/';
	$homeExists = file_exists($userDirectory);

	$user = $userManager->createUser($username, $password);
	if (!$user) {
		OC_JSON::error(array('data' => array('message' => 'User creation failed for ' . $username)));
		exit();
	}
	foreach ($groups as $group) {
		$group->addUser($user);
	}

	OCP\JSON::success(array("data" =>
		array(
			// returns whether the home already existed
			"homeExists" => $homeExists,
			"username" => $username,
			"groups" => $groupManager->getUserGroupIds($user),
			'storageLocation' => $user->getHome())));
} catch (Exception $exception) {
	OCP\JSON::error(array("data" => array("message" => $exception->getMessage())));
}
