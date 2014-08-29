<?php

/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\Group\Group;
use OC\User\User;

class SubAdmin extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \OCP\IDB
	 */
	protected $connection;

	/**
	 * @var \OCP\IUserManager | \PHPUnit_Framework_MockObject_MockObject
	 */
	protected $userManager;

	/**
	 * @var \OCP\IGroupManager | \PHPUnit_Framework_MockObject_MockObject
	 */
	protected $groupManager;

	/**
	 * @var \OCP\IGroup | \PHPUnit_Framework_MockObject_MockObject
	 */
	protected $adminGroup;

	/**
	 * @var \OCP\IUser[]
	 */
	protected $users = array();

	/**
	 * @var \OCP\IGroup[]
	 */
	protected $groups = array();

	public function setUp() {
		$this->userManager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager = $this->getMockBuilder('\OC\Group\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->connection = \OC::$server->getDb();
		$this->adminGroup = $this->getMockBuilder('\OC\Group\Group')
			->disableOriginalConstructor()
			->getMock();
		$this->groups['admin'] = $this->adminGroup;

		$users = & $this->users;
		$groups = & $this->groups;
		$this->groupManager->expects($this->any())
			->method('get')
			->will($this->returnCallback(function ($id) use (&$groups) {
				if (isset($groups[$id])) {
					return $groups[$id];
				} else {
					return null;
				}
			}));
		$this->userManager->expects($this->any())
			->method('get')
			->will($this->returnCallback(function ($id) use (&$users) {
				if (isset($users[$id])) {
					return $users[$id];
				} else {
					return null;
				}
			}));
	}

	/**
	 * @return \OCP\IUser | \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getTestUser() {
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()
			->getMock();
		$id = uniqid();
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue($id));
		$this->users[$id] = $user;
		return $user;
	}

	/**
	 * @return \OCP\IGroup | \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getTestGroup() {
		$group = $this->getMockBuilder('\OC\Group\Group')
			->disableOriginalConstructor()
			->getMock();
		$id = uniqid();
		$group->expects($this->any())
			->method('getGID')
			->will($this->returnValue($id));
		$this->groups[$id] = $group;
		return $group;
	}

	public function testRemoveSubAdmin() {
		$user = $this->getTestUser();
		$group = $this->getTestGroup();
		$subAdminManager = new \OC\SubAdmin($this->connection, $this->groupManager, $this->userManager);

		$subAdminManager->createSubAdmin($user, $group);
		$this->assertTrue($subAdminManager->isSubAdminOfGroup($user, $group));
		$subAdminManager->deleteSubAdmin($user, $group);
		$this->assertFalse($subAdminManager->isSubAdminOfGroup($user, $group));
	}

	public function testIsSubAdminOfGroup() {
		$user = $this->getTestUser();
		$user2 = $this->getTestUser();
		$group = $this->getTestGroup();
		$group2 = $this->getTestGroup();
		$subAdminManager = new \OC\SubAdmin($this->connection, $this->groupManager, $this->userManager);

		$this->assertFalse($subAdminManager->isSubAdminOfGroup($user, $group));
		$subAdminManager->createSubAdmin($user, $group);
		$this->assertTrue($subAdminManager->isSubAdminOfGroup($user, $group));
		$this->assertFalse($subAdminManager->isSubAdminOfGroup($user2, $group));
		$this->assertFalse($subAdminManager->isSubAdminOfGroup($user, $group2));
		$this->assertFalse($subAdminManager->isSubAdminOfGroup($user2, $group2));

		$subAdminManager->deleteUser($user);
	}

	public function testIsSubAdminNonAdmin() {
		$user = $this->getTestUser();
		$user2 = $this->getTestUser();
		$group = $this->getTestGroup();
		$subAdminManager = new \OC\SubAdmin($this->connection, $this->groupManager, $this->userManager);

		$this->adminGroup->expects($this->any())
			->method('inGroup')
			->will($this->returnValue(false));

		$this->assertFalse($subAdminManager->isSubAdmin($user));
		$this->assertFalse($subAdminManager->isSubAdmin($user2));
		$subAdminManager->createSubAdmin($user, $group);
		$this->assertTrue($subAdminManager->isSubAdmin($user));
		$this->assertFalse($subAdminManager->isSubAdmin($user2));

		$subAdminManager->deleteUser($user);
	}

	public function testIsSubAdminAdmin() {
		$user = $this->getTestUser();
		$group = $this->getTestGroup();
		$subAdminManager = new \OC\SubAdmin($this->connection, $this->groupManager, $this->userManager);

		$this->adminGroup->expects($this->any())
			->method('inGroup')
			->will($this->returnValue(true));

		$this->assertTrue($subAdminManager->isSubAdmin($user));
		$subAdminManager->createSubAdmin($user, $group);
		$this->assertTrue($subAdminManager->isSubAdmin($user));

		$subAdminManager->deleteUser($user);
	}

	public function testGetSubAdminGroups() {
		$user = $this->getTestUser();
		$user2 = $this->getTestUser();
		$group = $this->getTestGroup();
		$group2 = $this->getTestGroup();
		$subAdminManager = new \OC\SubAdmin($this->connection, $this->groupManager, $this->userManager);

		$this->assertEquals(array(), $subAdminManager->getSubAdminsGroups($user));
		$this->assertEquals(array(), $subAdminManager->getSubAdminsGroups($user2));

		$subAdminManager->createSubAdmin($user, $group);
		$this->assertEquals(array($group->getGID() => $group), $subAdminManager->getSubAdminsGroups($user));
		$this->assertEquals(array(), $subAdminManager->getSubAdminsGroups($user2));

		$subAdminManager->createSubAdmin($user, $group2);
		$this->assertEquals(array($group->getGID() => $group, $group2->getGID() => $group2), $subAdminManager->getSubAdminsGroups($user));
		$this->assertEquals(array(), $subAdminManager->getSubAdminsGroups($user2));

		$subAdminManager->deleteUser($user);
	}

	public function testGetGroupSubAdmins() {
		$user = $this->getTestUser();
		$user2 = $this->getTestUser();
		$group = $this->getTestGroup();
		$group2 = $this->getTestGroup();
		$subAdminManager = new \OC\SubAdmin($this->connection, $this->groupManager, $this->userManager);

		$this->assertEquals(array(), $subAdminManager->getGroupSubAdmins($group));
		$this->assertEquals(array(), $subAdminManager->getGroupSubAdmins($group2));

		$subAdminManager->createSubAdmin($user, $group);
		$this->assertEquals(array($user), $subAdminManager->getGroupSubAdmins($group));
		$this->assertEquals(array(), $subAdminManager->getGroupSubAdmins($group2));

		$subAdminManager->createSubAdmin($user2, $group);
		$this->assertEquals(array($user, $user2), $subAdminManager->getGroupSubAdmins($group));
		$this->assertEquals(array(), $subAdminManager->getGroupSubAdmins($group2));

		$subAdminManager->deleteGroup($group);
	}

	public function testGetAllSubAdmins() {
		$user = $this->getTestUser();
		$user2 = $this->getTestUser();
		$group = $this->getTestGroup();
		$subAdminManager = new \OC\SubAdmin($this->connection, $this->groupManager, $this->userManager);

		$baseCount = count($subAdminManager->getAllSubAdmins());

		$subAdminManager->createSubAdmin($user, $group);
		$this->assertCount($baseCount + 1, $subAdminManager->getAllSubAdmins());

		$subAdminManager->createSubAdmin($user2, $group);
		$this->assertCount($baseCount + 2, $subAdminManager->getAllSubAdmins());

		$subAdminManager->deleteGroup($group);
	}

	public function testIsUserAccessible() {
		$user = $this->getTestUser();
		$user2 = $this->getTestUser();
		$user3 = $this->getTestUser();
		$group = $this->getTestGroup();
		$group2 = $this->getTestGroup();
		$subAdminManager = new \OC\SubAdmin($this->connection, $this->groupManager, $this->userManager);

		$group->expects($this->any())
			->method('inGroup')
			->will($this->returnCallback(function ($user) use ($user2) {
				return $user == $user2;
			}));
		$group2->expects($this->any())
			->method('inGroup')
			->with($user3)
			->will($this->returnCallback(function ($user) use ($user3) {
				return $user == $user3;
			}));

		$this->assertFalse($subAdminManager->isUserAccessible($user, $user2));
		$this->assertFalse($subAdminManager->isUserAccessible($user, $user3));

		$subAdminManager->createSubAdmin($user, $group);
		$this->assertTrue($subAdminManager->isUserAccessible($user, $user2));
		$this->assertFalse($subAdminManager->isUserAccessible($user, $user3));

		$subAdminManager->createSubAdmin($user, $group2);
		$this->assertTrue($subAdminManager->isUserAccessible($user, $user2));
		$this->assertTrue($subAdminManager->isUserAccessible($user, $user3));

		$subAdminManager->deleteUser($user);
	}
}
