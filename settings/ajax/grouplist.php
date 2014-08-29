<?php
/**
 * ownCloud
 *
 * @author Arthur Schiwon
 * @copyright 2014 Arthur Schiwon <blizzz@owncloud.com>
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
if (isset($_GET['pattern']) && !empty($_GET['pattern'])) {
	$pattern = $_GET['pattern'];
} else {
	$pattern = '';
}
if (isset($_GET['filterGroups']) && !empty($_GET['filterGroups'])) {
	$filterGroups = intval($_GET['filterGroups']) === 1;
} else {
	$filterGroups = false;
}
$groupPattern = $filterGroups ? $pattern : '';
$groups = array();
$adminGroups = array();

$activeUser = \OC::$server->getUserSession()->getUser();
$userManager = \OC::$server->getUserManager();
$userSession = \OC::$server->getUserSession();
$groupManager = \OC::$server->getGroupManager();
$subAdminManager = \OC::$server->getSubAdminManager();

$isAdmin = $groupManager->get('admin')->inGroup($activeUser);

$groupsInfo = new \OC\Group\MetaData($activeUser, $isAdmin, $groupManager, $subAdminManager);
$groupsInfo->setSorting($groupsInfo::SORT_USERCOUNT);
list($adminGroups, $groups) = $groupsInfo->get($groupPattern, $pattern);

OC_JSON::success(
	array('data' => array('adminGroups' => $adminGroups, 'groups' => $groups)));
