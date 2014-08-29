<?php
// Check if we are a user

OCP\JSON::callCheck();
OC_JSON::checkLoggedIn();

$l = OC_L10N::get('settings');

$username = isset($_POST["username"]) ? $_POST["username"] : OC_User::getUser();
$displayName = $_POST["displayName"];

$activeUser = \OC::$server->getUserSession()->getUser();
$user = \OC::$server->getUserManager()->get($username);
if (\OC::$server->getGroupManager()->get('admin')->inGroup($activeUser) or
	\OC::$server->getSubAdminManager()->isUserAccessible($activeUser, $user) or
	($activeUser === $user && $user->canChangeDisplayName())
) {
	if ($user->setDisplayName($displayName)) {
		OC_JSON::success(array("data" => array("message" => $l->t('Your full name has been changed.'), "username" => $username, 'displayName' => $displayName)));
	} else {
		OC_JSON::error(array("data" => array("message" => $l->t("Unable to change full name"), 'displayName' => $user->getDisplayName())));
	}
} else {
	OC_JSON::error(array("data" => array("message" => $l->t("Authentication error"))));
	exit();
}
