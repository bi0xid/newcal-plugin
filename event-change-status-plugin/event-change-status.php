<?php
/*
Plugin Name: Event Change Status
Description: Change event status with one only click on e-mail
Version: 0.1
Author: Rocío Valdivia y Raven (Mecus)
Author URI: http://mecus.es/

0.1
	- Primera Beta del Plugin
*/


### Use WordPress 2.6 Constants
if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH.'wp-content');
if ( !defined('WP_CONTENT_URL') )
	define('WP_CONTENT_URL', get_option('siteurl').'/wp-content');

// Cogemos la ruta
$event_change_status__wp_dirname = basename(dirname(dirname(__FILE__))); // for "plugins" or "mu-plugins"
$event_change_status__pi_dirname = basename(dirname(__FILE__)); // plugin name

$event_change_status__path = WP_CONTENT_DIR.'/'.$event_change_status__wp_dirname.'/'.$event_change_status__pi_dirname;
$event_change_status__url = WP_CONTENT_URL.'/'.$event_change_status__wp_dirname.'/'.$event_change_status__pi_dirname;


### Create Text Domain For Translations
function event_change_status__init() {
	global $event_change_status__pi_dirname;
	
	// Load the location file
	load_plugin_textdomain('event-change-status', false, $event_change_status__pi_dirname.'/langs');
}
add_action('init', 'event_change_status__init');


function event_change_status__activate() {
	global $wpdb;
	
	if(@is_file(ABSPATH.'/wp-admin/upgrade-functions.php')) {
		include_once(ABSPATH.'/wp-admin/upgrade-functions.php');
	} elseif(@is_file(ABSPATH.'/wp-admin/includes/upgrade.php')) {
		include_once(ABSPATH.'/wp-admin/includes/upgrade.php');
	} else {
		die('We have problem finding your \'/wp-admin/upgrade-functions.php\' and \'/wp-admin/includes/upgrade.php\'');
	}
	
}
register_activation_hook( __FILE__,'event_change_status__activate');



/* Opciones del formulario de discusion */
function event_change_status__admin_init() {
	
	register_setting('discussion', 'change-event-status-mail');
	
	add_settings_field(
		'change-event-status-mail',
		__('Lista de correo de Reserva de Aulas: ', 'event-change-status'),
		'event_change_status__change_event_status',
		'discussion'
	);
	
}
add_action('admin_init', 'event_change_status__admin_init');


function event_change_status__change_event_status() {
	
	echo '<fieldset>';
		
		echo ' <input name="change-event-status-mail" type="text" id="change-event-status-mail" value="' . attribute_escape(get_option('change-event-status-mail')) . '" class="regular-text" />';
		echo '<br />';
		echo '<label for="change-event-status-mail">'.__('Send messages to this e-mails', 'event-change-status').'</label>';
		
	echo '</fieldset>';
	
}


require_once('event-change-status-mail.php');

?>