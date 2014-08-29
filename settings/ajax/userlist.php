<?php
/**
 * ownCloud
 *
 * @author Michael Gapczynski
 * @copyright 2012 Michael Gapczynski mtgap@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

OC_JSON::callCheck();
OC_JSON::checkSubAdminUser();
if (isset($_GET['offset'])) {
	$offset = $_GET['offset'];
} else {
	$offset = 0;
}
if (isset($_GET['limit'])) {
	$limit = $_GET['limit'];
} else {
	$limit = 10;
}
if (isset($_GET['gid']) && !empty($_GET['gid'])) {
	$gid = $_GET['gid'];
	if ($gid === '_everyone') {
		$gid = false;
	}
} else {
	$gid = false;
}
if (isset($_GET['pattern']) && !empty($_GET['pattern'])) {
	$pattern = $_GET['pattern'];
} else {
	$pattern = '';
}

$activeUser = \OC::$server->getUserSession()->getUser();
$userManager = \OC::$server->getUserManager();
$groupManager = \OC::$server->getGroupManager();
$subAdminManager = \OC::$server->getSubAdminManager();
$config = \OC::$server->getConfig();

$users = array();
if ($groupManager->get('admin')->inGroup($activeUser)) {
	if ($gid !== false) {
		$group = $groupManager->get($gid);
		$batch = $group->searchDisplayName($pattern, $limit, $offset);
	} else {
		$batch = $userManager->search($pattern, $limit, $offset);
	}
	foreach ($batch as $user) {
		$groups = $groupManager->getUserGroups($user);
		$groupIds = array_map(function ($group) {
			/** @var \OCP\IGroup $group */
			return $group->getGID();
		}, $groups);

		$subAdminGroups = $subAdminManager->getSubAdminsGroups($user);
		$subAdminGroupIds = array_map(function ($group) {
			/** @var \OCP\IGroup $group */
			return $group->getGID();
		}, $subAdminGroups);

		$users[] = array(
			'name' => $user->getUID(),
			'displayname' => $user->getDisplayName(),
			'groups' => $groupIds,
			'subadmin' => $subAdminGroupIds,
			'quota' => $config->getUserValue($user->getUID(), 'files', 'quota', 'default'),
			'storageLocation' => $user->getHome(),
			'lastLogin' => $user->getLastLogin(),
		);
	}
} else {
	$group = $groupManager->get($gid);
	$subAdminGroups = $subAdminManager->getSubAdminsGroups($activeUser);
	if ($gid) {
		if (in_array($gid, $subAdminGroups)) {
			$groups = array($group);
		} else {
			$groups = array();
		}
	} else {
		$groups = $subAdminGroups;
	}

	/** @var \OC\User\User[] $batch */
	$batch = array();
	foreach ($groups as $group) {
		$groupUsers = $group->searchUsers($pattern, $limit, $offset);
		$batch = array_merge($batch, $groupUsers);
	}
	$batch = array_unique($batch);
	foreach ($batch as $user) {
		// Only add the groups, this user is a sub admin of
		$userGroups = array_intersect($groupManager->getUserGroups($user), $subAdminGroups);
		$groupIds = array_map(function ($group) {
			/** @var \OCP\IGroup $group */
			return $group->getGID();
		}, $userGroups);


		$users[] = array(
			'name' => $user->getUID(),
			'displayname' => $user->getDisplayName(),
			'groups' => $groupIds,
			'quota' => $config->getUserValue($user->getUID(), 'files', 'quota', 'default'),
			'storageLocation' => $user->getHome(),
			'lastLogin' => $user->getLastLogin(),
		);
	}
}
OC_JSON::success(array('data' => $users));
