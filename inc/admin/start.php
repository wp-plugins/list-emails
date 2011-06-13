<?php

global $pip_list_emails, $wpdb;

if (!current_user_can("edit_plugins")) {
	echo __("Sorry: you can not configure this plugin with your account.", "listemails");
	exit;
}

if ($_POST):
	check_admin_referer("listemails-list");

	$pip_list_emails->update_option("last_op_time", time());
	$pip_list_emails->update_option("status", "failed");
	
	require_once "wait.php";
	
	@flush();
	@ob_flush();
	
	$sources = (isset($_POST["source"]) && is_array($_POST["source"])) ? $_POST["source"] : array("users", "comments-approved");
	$data    = (isset($_POST["data"]) && is_array($_POST["data"]) && $_POST["data"])     ? $_POST["data"]   : array("email", "name");
	$format  = isset($_POST["format"]) ? $_POST["format"] : "comma-sep";
	$split   = isset($_POST["split"]) ? TRUE : FALSE;
	$split_length = isset($_POST["split-num-records"]) ? (int)$_POST["split-num-records"] : 100;
	
	$ext = "csv";
	
	switch ($format) {
		case "csv":
			$ext = "csv";
			break;
		case "comma-sep":
		case "line-sep":
			$ext = "txt";
			break;
	}
	
	$output_dir = $pip_list_emails->get_option("output_dir");
	
	foreach (glob($pip_list_emails->get_option("output_dir") . "/*.*") as $file) {
		unlink($file);
	}
	
	$tbl = $pip_list_emails->table_name("data");
	$wp_comments = $wpdb->prefix . "comments";
	$wp_users = $wpdb->prefix . "users";
	
	$select_queries = array();
	
	if (in_array("users", $sources)) {
		$select_queries[] = "SELECT user_email AS email, display_name AS name FROM {$wp_users}";
	}
	if (in_array("comments-approved", $sources)) {
		$select_queries[] = "SELECT comment_author_email AS email, comment_author AS name FROM {$wp_comments} WHERE comment_approved = 1";
	}
	if (in_array("comments-spam", $sources)) {
		$select_queries[] = "SELECT comment_author_email AS email, comment_author AS name FROM {$wp_comments} WHERE comment_approved = 'spam'";
	}
	if (in_array("comments-spam", $sources)) {
		$select_queries[] = "SELECT comment_author_email AS email, comment_author AS name FROM {$wp_comments} WHERE comment_approved = 'trash'";
	}
	
	$wpdb->query("DROP VIEW IF EXISTS {$tbl}");
	$wpdb->query("CREATE VIEW {$tbl} AS " . implode(" UNION ", $select_queries));
	
	$offset = 0;
	$has_results = true;
	
	if (!$split) {
		$split_length = 16777216;
	}
	
	$num_results = 0;
	
	while ($has_results) {		
		$results = $wpdb->get_results("SELECT email, name FROM {$tbl} GROUP BY email LIMIT {$offset}, {$split_length}");
		
		if (!$results) {
			$has_results = FALSE;
		} else {
			$writedata = "";
			
			if ($format == "csv") {
				if (in_array("email", $data)) {
					$writedata .= "Email,";
				}
				
				if (in_array("name", $data)) {
					$writedata .= "Name,";
				}
			}
			
			foreach ($results as $result) {
				if (!is_email($result->email)) {
					continue;
				}
				
				switch ($format) {
					case "csv":
						$writedata .= "\n";
						if (in_array("email", $data)) {
							$writedata .= $result->email . ",";
						}
						
						if (in_array("name", $data)) {
							$writedata .= $result->name . ",";
						}
						break;
					case "comma-sep":
						$writedata .= ($writedata ? "," : "") . $result->email;
						break;
					case "line-sep":
						$writedata .= ($writedata ? "\r\n" : "") . $result->email;
						break;
				}
			}
			
			$file = fopen($output_dir . "/file" . ($offset/$split_length) . ".{$ext}", "w");
			fwrite($file, $writedata);
			fclose($file);
		}
		
		$num_results += count($results);
		$offset += $split_length;
	}
	
	$pip_list_emails->update_option("last_op_time", "0");
	$pip_list_emails->update_option("status", "ok");
	$pip_list_emails->update_option("num_results", $num_results);
	
	require_once "download.php";
else:

?>
<div class="wrap">

	<h2><?php _e("List E-mails", "listemails"); ?></h2>
	
	<h3><?php _e("Export e-mail addresses from users, commenters, etc.", "listemails"); ?></h3>
	
	<form method="post" name="template" id="template" action="<?php echo esc_attr($_SERVER['REQUEST_URI']); ?>">
	<?php wp_nonce_field("listemails-list"); ?>
	
	<table class="form-table tml-form">

		<tbody>
			<tr valign="top">
				<th scope="row">
					<label for="source"><?php _e("Data sources:", "listemails"); ?></label>
				</th>
				<td>
					<div><input type="checkbox" name="source[]" value="users" checked="checked" /> <?php _e("Users", "listemails"); ?></div>
					<div><input type="checkbox" name="source[]" value="comments-approved" checked="checked" /> <?php _e("Approved Comments", "listemails"); ?></div>
					<div><input type="checkbox" name="source[]" value="comments-spam" /> <?php _e("Spam Comments", "listemails"); ?></div>
					<div><input type="checkbox" name="source[]" value="comments-deleted" /> <?php _e("Deleted Comments", "listemails"); ?></div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="source"><?php _e("Data:", "listemails"); ?></label>
				</th>
				<td>
					<div><input type="checkbox" name="data[]" value="email" checked="checked" /> <?php _e("E-mail address", "listemails"); ?></div>
					<div><input type="checkbox" name="data[]" value="name" checked="checked" /> <?php _e("Commenter's Name / User's Display Name", "listemails"); ?></div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="source"><?php _e("Format:", "listemails"); ?></label>
				</th>
				<td>
					<select name="format">
						<option value="csv"><?php echo _e("CSV", "listemails"); ?></option>
						<option value="comma-sep" selected="selected"><?php echo _e("Comma-separated list (emails only)", "listemails"); ?></option>
						<option value="line-sep"><?php echo _e("Text file; new-line separated (emails only)", "listemails"); ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="split"><?php _e("Split data:", "listemails"); ?></label>
				</th>
				<td>
					<div><input type="checkbox" name="split" value="yes" checked="checked" /> <?php printf(__("Split data into multiple files with %s records per file", "listemails"), '<input type="text" name="split-num-records" value="1000" class="small-text" />'); ?></div>
				</td>
			</tr>
		</tbody>
	</table>
	
	<p class="submit">
		<input type="submit" name="Submit" style="font-weight: bold;" tabindex="23" value="<?php _e("Export data", "listemails"); ?>" />	
	</p>
	
	</form>
	
	<h3><?php _e("Download lists", "listemails"); ?></h3>
	
	<?php $status = $pip_list_emails->get_option("status"); ?>
	
	<?php if ($status == "failed"): ?>
	<p><?php _e("There was an error exporting the data. Please try again.", "listemails"); ?></p>
	<?php elseif ($status == "ok"):
		require_once "download.php";
	endif; ?>
	
	<p>List Emails has been sponsored by <a href="http://www.pipvertise.co.uk/" target="_blank">

<img src="data:image/gif;base64,R0lGODlhQgAQAJEAAP8AZv////+PvP8/jCH5BAAHAP8ALAAAAABCABAAAALXHI4TcbIPo5zRNUoN
2gw4DIKC133hYyjLSJ7udL1RCqXCjQvD4OCPr9v9cBoecMf7IVEA1eKgYWkatKdneoFuNthL12G7
RRsIMBmwOpvRXCgLsRiTNIEutHS3ssy8w+OMVtKR0BPA8ncFhfShhnjTVFK00yioxaWjGFUVQVnp
4Se5GJM1t5WAxLMJcebk1QS5ydhCapSW8BMHKQG4Zhu4V+c3SrJlGxWMpupoGdPh9CWInGCqAO0j
gdMQZGJ4AxGU1G2y6A1uYi7TLLPOfqLeDh+vJ08/UQAAOw==
" alt="Pipvertise" title="Pipvertise" border="0" style="vertical-align: -4px;" /></a> - <a href="http://www.pipvertise.co.uk/" target="_blank">http://www.pipvertise.co.uk/</a></p>

</div>



<?php endif; ?>