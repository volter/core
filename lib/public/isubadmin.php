<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

interface ISubAdmin {
	/**
	 * Add a sub admin for a group
	 *
	 * @param \OCP\IUser $user
	 * @param \OCP\IGroup $group
	 */
	public function createSubAdmin($user, $group);

	/**
	 * Remove a sub admin for a group
	 *
	 * @param \OCP\IUser $user
	 * @param \OCP\IGroup $group
	 */
	public function deleteSubAdmin($user, $group);


	/**
	 * Get all groups a user is sub admin for
	 *
	 * @param \OCP\IUser $user
	 * @return \OCP\IGroup[]
	 */
	public function getSubAdminsGroups($user);

	/**
	 * Get all sub admins of a group
	 *
	 * @param \OCP\IGroup $group
	 * @return \OCP\IUser[]
	 */
	public function getGroupSubAdmins($group);

	/**
	 * Get all sub admins
	 *
	 * @return \OCP\IUser[]
	 */
	public function getAllSubAdmins();

	/**
	 * Check if a user is sub admin of a group
	 *
	 * @param \OCP\IUser $user
	 * @param \OCP\IGroup $group
	 * @return bool
	 */
	public function isSubAdminOfGroup($user, $group);

	/**
	 * Check if a user is sub admin of any group or the user is an admin
	 *
	 * @param \OCP\IUser $user
	 * @return bool
	 */
	public function isSubAdmin($user);

	/**
	 * checks if a user in a group of the sub admin
	 *
	 * @param \OCP\IUser $subAdmin
	 * @param \OCP\IUser $user
	 * @return bool
	 */
	public function isUserAccessible($subAdmin, $user);

	/**
	 * Remove all sub admin groups for a user
	 *
	 * @param \OCP\IUser $user
	 */
	public function deleteUser($user);

	/**
	 * Remove all sub admins of a group
	 *
	 * @param \OCP\IGroup $group
	 */
	public function deleteGroup($group);
}
