<?php
/**
 * Copyright (c) 2014, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$users = array();
$userManager = \OC::$server->getUserManager();
$userSession = \OC::$server->getUserSession();
$groupManager = \OC::$server->getGroupManager();
$subAdminManager = \OC::$server->getSubAdminManager();
$config = \OC::$server->getConfig();
$user = $userSession->getUser();

if (!$subAdminManager->isSubAdmin($user)) {
	exit;
}

// We have some javascript foo!
OC_Util::addScript('settings', 'users/deleteHandler');
OC_Util::addScript('settings', 'users/filter');
OC_Util::addScript('settings', 'users/users');
OC_Util::addScript('settings', 'users/groups');
OC_Util::addScript('core', 'multiselect');
OC_Util::addScript('core', 'singleselect');
OC_Util::addScript('core', 'jquery.inview');
OC_Util::addStyle('settings', 'settings');
OC_App::setActiveNavigationEntry('core_users');


$isAdmin = $groupManager->get('admin')->inGroup($user);

$groupsInfo = new \OC\Group\MetaData($user, $isAdmin, $groupManager, $subAdminManager);
$groupsInfo->setSorting($groupsInfo::SORT_USERCOUNT);
/**
 * @var \OCP\IGroup[] $groups
 */
list($adminGroup, $groups) = $groupsInfo->get();

$recoveryAdminEnabled = OC_App::isEnabled('files_encryption') &&
	$config->getAppValue('files_encryption', 'recoveryAdminEnabled');

if ($isAdmin) {
	$accessibleUsers = $userManager->searchDisplayName('', 30);
	$subAdmins = $subAdminManager->getAllSubAdmins();
} else {
	/* Retrieve group IDs from $groups array, so we can pass that information into OC_Group::displayNamesInGroups() */
	$accessibleUsers = array();
	foreach ($groups as $group) {
		$accessibleUsers = array_merge($accessibleUsers, $group->searchDisplayName('', 30));
	}
	$subAdmins = false;
}

// load preset quotas
$quotaPreset = $config->getAppValue('files', 'quota_preset', '1 GB, 5 GB, 10 GB');
$quotaPreset = explode(',', $quotaPreset);
foreach ($quotaPreset as &$preset) {
	$preset = trim($preset);
}
$quotaPreset = array_diff($quotaPreset, array('default', 'none'));

$defaultQuota = $config->getAppValue('files', 'default_quota', 'none');
$defaultQuotaIsUserDefined = array_search($defaultQuota, $quotaPreset) === false
	&& array_search($defaultQuota, array('none', 'default')) === false;

// load users and quota
foreach ($accessibleUsers as $accessibleUser) {
	$quota = $config->getUserValue($uid, 'files', 'quota', 'default');
	$isQuotaUserDefined = array_search($quota, $quotaPreset) === false
		&& array_search($quota, array('none', 'default')) === false;

	$name = $accessibleUser->getDisplayName();
	if ($name !== $accessibleUser->getUID()) {
		$name = $name . ' (' . $accessibleUser->getUID() . ')';
	}

	$groups = $groupManager->getUserGroups($accessibleUser);
	$subAdminGroups = $subAdminManager->getSubAdminsGroups($accessibleUser);

	$groupIds = array_map(function($group){
		/** @var \OCP\IGroup $group */
		return $group->getGID();
	}, $groups);

	$subAdminGroupIds = array_map(function($group){
		/** @var \OCP\IGroup $group */
		return $group->getGID();
	}, $subAdminGroups);

	$user = $userManager->get($uid);
	$users[] = array(
		"name" => $uid,
		"displayName" => $accessibleUser->getDisplayName(),
		"groups" => $groupIds,
		'quota' => $quota,
		'isQuotaUserDefined' => $isQuotaUserDefined,
		'subadmin' => $subAdminGroupIds,
		'storageLocation' => $accessibleUser->getHome(),
		'lastLogin' => $accessibleUser->getLastLogin(),
	);
}

$tmpl = new OC_Template("settings", "users/main", "user");
$tmpl->assign('users', $users);
$tmpl->assign('groups', $groups);
$tmpl->assign('adminGroup', $adminGroup);
$tmpl->assign('isAdmin', (int)$isAdmin);
$tmpl->assign('subadmins', $subAdmins);
$tmpl->assign('usercount', count($users));
$tmpl->assign('numofgroups', count($groups) + count($adminGroup));
$tmpl->assign('quota_preset', $quotaPreset);
$tmpl->assign('default_quota', $defaultQuota);
$tmpl->assign('defaultQuotaIsUserDefined', $defaultQuotaIsUserDefined);
$tmpl->assign('recoveryAdminEnabled', $recoveryAdminEnabled);
$tmpl->assign('enableAvatars', \OC_Config::getValue('enable_avatars', true));
$tmpl->printPage();
