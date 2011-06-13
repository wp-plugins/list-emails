<?php

global $pip_list_emails;

if (!current_user_can("edit_plugins")) {
	echo __("Sorry: you can not configure this plugin with your account.", "listemails");
	exit;
}

if ($pip_list_emails->get_option("last_op_time") > time() - 60) {
	require_once "wait.php";
} else {
	require_once "start.php";
}
