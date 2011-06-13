<?php
/*
Plugin Name: List e-mails
Description: Export e-mail addresses from users, commentators, etc.
Author: Brendon Boshell
Version: 1.0
Author URI: http://www.pipvertise.co.uk/
Text Domain: listemails
*/

/*  Copyright 2010  Brendon Boshell  (email : brendon@22talk.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class PipListEmails {
	public $version = "1.0";
	public $includes_dir = "inc";
	
	public $option_prefix = "_piplistemails_";
	public $allowed_options = array(
		"installed_version" => true,
		"job_time" => true,
		"last_op_time" => true,
		"status" => true,
		"output_dir" => true,
		"num_results" => true
	);
	public $default_options = array();
	public $options_autoloads = array();
	
	public $table_prefix = "piplistemails_";
	public $tables = array(
		"data" => "data"
	);
	
	public function load_default_options() {	
		$this->default_options = array(
			"output_dir" => dirname(__FILE__) . "/output"			   
		);
	}
	
	public function get_option($name) {
		$opt = get_option( $this->option_prefix . $name );
		
		if ($opt === FALSE) {
			if (isset($this->default_options[$name])) {
				$opt = $this->default_options[$name];
			}
			
			if($opt !== FALSE) {
				$this->update_option($name, $opt); // ensure the option exists in the database
			}
		}
		
		if (is_array($opt)) {
			$opt = serialize($opt);
		}

		return $opt;
	}
	
	public function update_option($name, $val, $autoload = "default") {	
		if (!isset($this->allowed_options[$name])) {
			return false;
		}
			
		if ($autoload == "default") { // different options have different autoload requirements; save accordingly
			if (isset($this->options_autoloads[$name])) {
				$autoload = $this->options_autoloads[$name];
			} else {
				$autoload = "no";
			}
		}
	
		$opt_name = $this->option_prefix . $name;
		if (is_object($val) || is_array($val)) {
			$opt_val = serialize($val);
		} else {
			$opt_val = $val;
		}
		
		if (get_option($opt_name) !== FALSE) {
			return update_option($opt_name, $opt_val);
		} else {
			return add_option($opt_name, $opt_val, "", $autoload);
		}
	}
	
	public function delete_option($name) {
		if (!isset( $this->allowed_options[$name])) {
			return false;
		}
			
		$name = $this->option_prefix . $name; // add prefix
			
		delete_option($name);
	}
	
	public function table_name($name) {
		global $wpdb;
		return $wpdb->prefix // wp_
		     . $this->table_prefix  // tml_
		     . (isset($this->tables[$name]) 
		         ? $this->tables[$name] // map name, if mapped, or
		         : $name); // use name provided: $name
	}
	
	public function __construct() {
		$this->load_default_options();
		add_action("init", array(&$this, "after_init"));
		add_action("admin_menu", array(&$this, "add_admin_menu_option"), 20);
	}
	
	public function after_init() {
		if (isset($_GET["page"]) && $_GET["page"] == "list-emails") {
			@set_time_limit(0);
		}
		
		// language:
		load_plugin_textdomain("listemails", "", dirname(plugin_basename(__FILE__)) . "/locale");
		
		// need to install?
		if ($this->get_option('installed_version') !== $this->version) {
			$this->install();
		}
	}
	
	
	public function add_admin_menu_option() {
		add_options_page(__("List E-mails", "listemails"), __("List E-mails", "thankmelater"), 'manage_options', 'list-emails', array($this, "admin_screen"));
	}
	
	public function admin_screen() {
		require_once $this->includes_dir . "/admin/admin.php";
	}
	
	public function install() {
		global $pip_install;
		require_once $this->includes_dir . "/install.php";
	}
}

$pip_list_emails = new PipListEmails();
	
?>