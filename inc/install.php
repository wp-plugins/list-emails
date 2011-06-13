<?php

if (!current_user_can("edit_plugins")) {
	echo __("Sorry: you can not configure this plugin with your account.", "listemails");
	exit;
}
	
require_once ABSPATH . 'wp-admin/upgrade-functions.php';

global $pip_list_emails;

$pip_list_emails->update_option("installed_version", $pip_list_emails->version);
