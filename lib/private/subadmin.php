<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

use OCP\ISubAdmin;

class SubAdmin implements ISubAdmin {
	/**
	 * @var \OCP\IDB
	 */
	protected $connection;

	/**
	 * @var \OCP\IGroupManager
	 */
	protected $groupManager;

	/**
	 * @var \OCP\IUserManager
	 */
	protected $userManager;

	/**
	 * @param \OCP\IDB $connection
	 * @param \OCP\IGroupManager $groupManager
	 * @param \OCP\IUserManager $userManager ;
	 */
	public function __construct($connection, $groupManager, $userManager) {
		$this->connection = $connection;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
	}

	/**
	 * @param \OCP\IUser $user
	 * @param \OCP\IGroup $group
	 */
	public function createSubAdmin($user, $group) {
		$query = $this->connection->prepareQuery('INSERT INTO `*PREFIX*group_admin` (`gid`,`uid`) VALUES(?,?)');
		$query->execute(array($group->getGID(), $user->getUID()));
	}

	/**
	 * @param \OCP\IUser $user
	 * @param \OCP\IGroup $group
	 */
	public function deleteSubAdmin($user, $group) {
		$query = $this->connection->prepareQuery('DELETE FROM `*PREFIX*group_admin` WHERE `gid` = ? AND `uid` = ?');
		$query->execute(array($group->getGID(), $user->getUID()));
	}


	/**
	 * @param \OCP\IUser $user
	 * @return \OCP\IGroup[]
	 */
	public function getSubAdminsGroups($user) {
		$query = $this->connection->prepareQuery('SELECT `gid` FROM `*PREFIX*group_admin` WHERE `uid` = ?');
		$result = $query->execute(array($user->getUID()));

		$groups = array();
		while ($row = $result->fetchRow()) {
			$groups[$row['gid']] = $this->groupManager->get($row['gid']);
		}
		return $groups;
	}

	/**
	 * @param \OCP\IGroup $group
	 * @return \OCP\IUser[]
	 */
	public function getGroupSubAdmins($group) {
		$query = $this->connection->prepareQuery('SELECT `uid` FROM `*PREFIX*group_admin` WHERE `gid` = ?');
		$result = $query->execute(array($group->getGID()));

		$users = array();
		while ($row = $result->fetchRow()) {
			$users[] = $this->userManager->get($row['uid']);
		}
		return $users;
	}

	/**
	 * @return \OCP\IUser[]
	 */
	public function getAllSubAdmins() {
		$query = $this->connection->prepareQuery('SELECT * FROM `*PREFIX*group_admin`');
		$result = $query->execute();

		$users = array();
		while ($row = $result->fetchRow()) {
			$users[] = $this->userManager->get($row['uid']);
		}
		return $users;
	}

	/**
	 * @param \OCP\IUser $user
	 * @param \OCP\IGroup $group
	 * @return bool
	 */
	public function isSubAdminOfGroup($user, $group) {
		if (is_null($group)) {
			return false;
		}
		$query = $this->connection->prepareQuery('SELECT `uid` FROM `*PREFIX*group_admin` WHERE `uid` = ? AND `gid` = ?');
		$result = $query->execute(array($user->getUID(), $group->getGID()));
		return (bool)$result->fetchRow();
	}

	/**
	 * @param \OCP\IUser $user
	 * @return bool
	 */
	public function isSubAdmin($user) {
		if ($this->groupManager->get('admin')->inGroup($user)) {
			return true;
		}

		$query = $this->connection->prepareQuery('SELECT `uid` FROM `*PREFIX*group_admin` WHERE `uid` = ?');
		$result = $query->execute(array($user->getUID()));
		return (bool)$result->fetchRow();
	}

	/**
	 * checks if a user is a accessible by a sub admin
	 *
	 * @param \OCP\IUser $subAdmin
	 * @param \OCP\IUser $user
	 * @return bool
	 */
	public function isUserAccessible($subAdmin, $user) {
		if (!$this->isSubAdmin($subAdmin)) {
			return false;
		}
		if ($this->groupManager->get('admin')->inGroup($user)) {
			return false;
		}
		$accessibleGroups = $this->getSubAdminsGroups($subAdmin);
		foreach ($accessibleGroups as $accessibleGroup) {
			if ($accessibleGroup->inGroup($user)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param \OCP\IUser $user
	 */
	public function deleteUser($user) {
		$query = $this->connection->prepareQuery('DELETE FROM `*PREFIX*group_admin` WHERE `uid` = ?');
		$query->execute(array($user->getUID()));
	}

	/**
	 * @param \OCP\IGroup $group
	 */
	public function deleteGroup($group) {
		$query = $this->connection->prepareQuery('DELETE FROM `*PREFIX*group_admin` WHERE `gid` = ?');
		$query->execute(array($group->getGID()));
	}
}
