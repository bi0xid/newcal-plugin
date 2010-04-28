<?php
/*
Plugin Name: CalendarAula
Plugin URI: http://www.kieranoshea.com
Description: This plugin allows you to display a calendar of all your events and appointments as a page on your site.
Author: Kieran O'Shea
Author URI: http://www.kieranoshea.com
Version: 1.2.2
*/

/*  Copyright 2008  Kieran O'Shea  (email : kieran@kieranoshea.com)

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

// Enable internationalisation
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'calendarAula','wp-content/plugins/'.$plugin_dir, $plugin_dir);

// Define the tables used in Calendar
define('WP_CALENDAR_TABLE_Aula', $table_prefix . 'calendarAula');
define('WP_CALENDAR_CONFIG_TABLE_Aula', $table_prefix . 'calendar_configAula');
define('WP_CALENDAR_CATEGORIES_TABLE_Aula', $table_prefix . 'calendar_categoriesAula');

// Create a master category for Calendar and its sub-pages
add_action('admin_menu', 'calendar_menuAula');

// Enable the ability for the calendar to be loaded from pages
add_filter('the_content','calendar_insertAula');

// Add the function that puts style information in the header
add_action('wp_head', 'calendar_wp_headAula');

// Add the function that deals with deleted users
add_action('delete_user', 'deal_with_deleted_userAula');

// Add the widgets if we are using version 2.8
add_action('widgets_init', 'widget_init_calendar_todayAula');
add_action('widgets_init', 'widget_init_calendar_upcomingAula');

// Before we get on with the functions, we need to define the initial style used for Calendar

// Function to deal with events posted by a user when that user is deleted
function deal_with_deleted_userAula($id)
{
  global $wpdb;

  // This wouldn't work unless the database was up to date. Lets check.
  check_calendarAula();

  // Do the query
  $wpdb->get_results("UPDATE ".WP_CALENDAR_TABLE_Aula." SET event_author=".$wpdb->get_var("SELECT MIN(ID) FROM ".$wpdb->prefix."users",0,0)." WHERE event_author=".$id);
}

// Function to add the calendar style into the header
function calendar_wp_headAula()
{
  global $wpdb;

  // If the calendar isn't installed or upgraded this won't work
  check_calendarAula();

  $styles = $wpdb->get_results("SELECT config_value FROM " . WP_CALENDAR_CONFIG_TABLE_Aula . " WHERE config_item='calendar_style'");
  if (!empty($styles))
    {
      foreach ($styles as $style)
        {
	  echo '<style type="text/css">
<!--
';
          echo $style->config_value.'
';
	  echo '//-->
</style>
';
        }
    }
}

// Function to deal with adding the calendar menus
function calendar_menuAula() 
{
  global $wpdb;

  // We make use of the Calendar tables so we must have installed Calendar
  check_calendarAula();

  // Set admin as the only one who can use Calendar for security
  $allowed_group = 'manage_options';

  // Use the database to *potentially* override the above if allowed
  $configs = $wpdb->get_results("SELECT config_value FROM " . WP_CALENDAR_CONFIG_TABLE_Aula . " WHERE config_item='can_manage_events'");
  if (!empty($configs))
    {
      foreach ($configs as $config)
	{
	  $allowed_group = $config->config_value;
	}
    }

  // Add the admin panel pages for Calendar. Use permissions pulled from above
   if (function_exists('add_menu_page')) 
     {
       add_menu_page(__('Reserva de Aulas','calendarAula'), __('Reserva de Aulas','calendarAula'), $allowed_group, 'calendarAula', 'edit_calendarAula');
     }
   if (function_exists('add_submenu_page')) 
     {
       add_submenu_page('calendarAula', __('Gestionar Reserva de Aulas','calendarAula'), __('Gestionar Reserva de Aulas','calendarAula'), $allowed_group, 'calendarAula', 'edit_calendarAula');
       add_action( "admin_head", 'calendar_add_javascriptAula');
       // Note only admin can change calendar options
       
       add_submenu_page('calendarAula', __('Gestionar Categor&iacute;as Aula','calendarAula'), __('Gestionar Categor&iacute;as Aula','calendarAula'), $allowed_group, 'calendar-categoriesAula', 'manage_categoriesAula');
       add_submenu_page('calendarAula', __('Opciones Reserva de Aulas','calendarAula'), __('Opciones Reserva de Aulas','calendarAula'), $allowed_group, 'calendar-configAula', 'edit_calendar_configAula');
     }
}

// Function to add the javascript to the admin header
function calendar_add_javascriptAula()
{ 
  echo '<script type="text/javascript" src="';
  bloginfo('wpurl');
  echo '/wp-content/plugins/calendarAula/javascriptAula.js"></script>
<script type="text/javascript">document.write(getCalendarStylesAula());</script>
';
}

// Function to deal with loading the calendar into pages
function calendar_insertAula($content)
{
  if (preg_match('{CALENDARAULA}',$content))
    {
      $cal_output = calendarAula();
      $content = str_replace('{CALENDARAULA}',$cal_output,$content);
    }
  return $content;
}

// Function to check what version of Calendar is installed and install if needed
function check_calendarAula()
{
  // Checks to make sure Calendar is installed, if not it adds the default
  // database tables and populates them with test data. If it is, then the 
  // version is checked through various means and if it is not up to date 
  // then it is upgraded.

  // Lets see if this is first run and create us a table if it is!
  global $wpdb, $initial_style;

  // All this style info will go into the database on a new install
  // This looks nice in the Kubrick theme
  $initial_style = "    .calnk a:hover {
         background-position: 0 0;
         text-decoration:none;  
         color:#000000;
         border-bottom:1px dotted #000000;
         }
    .calnk a:visited {
         text-decoration:none;
         color:#000000;
         border-bottom:1px dotted #000000;
        }
    .calnk a {
        text-decoration:none; 
        color:#000000; 
        border-bottom:1px dotted #000000;
        }
    .calnk a span { 
        display:none; 
        }
    .calnk a:hover span {
        color:#333333; 
        background:#F6F79B; 
        display:block;
        position:absolute; 
        margin-top:1px; 
        padding:5px; 
        width:150px; 
        z-index:100;
        }
     .calendar-table {
        border:none;
        width:100%;
     }
     .calendar-heading {
        height:25px;
        text-align:center;
        border:1px solid #D6DED5;
        background-color:#E4EBE3;
     }
     .calendar-next {
        width:25%;
        text-align:center;
     }
     .calendar-prev {
        width:25%;
        text-align:center;
     }
     .calendar-month {
        width:50%;
        text-align:center;
        font-weight:bold;
     }
     .normal-day-heading {
        text-align:center;
        width:25px;
        height:25px;
        font-size:0.8em;
        border:1px solid #DFE6DE;
        background-color:#EBF2EA;
     }
     .weekend-heading {
        text-align:center;
        width:25px;
        height:25px;
        font-size:0.8em;
        border:1px solid #DFE6DE;
        background-color:#EBF2EA;
        color:#FF0000;
     }
     .day-with-date {
        vertical-align:text-top;
        text-align:left;
        width:60px;
        height:60px;
        border:1px solid #DFE6DE;
     }
     .no-events {

     }
     .day-without-date {
        width:60px;
        height:60px;
        border:1px solid #E9F0E8;
     }
     span.weekend {
        color:#FF0000;
     }
     span.negro {
        color:black;
     }
     .current-day {
        vertical-align:text-top;
        text-align:left;
        width:60px;
        height:60px;
        border:1px solid #BFBFBF;
        background-color:#E4EBE3;
     }
     .bank-holiday-day {
        vertical-align:text-top;
        text-align:left;
        width:60px;
        height:60px;
        border:1px solid #BFBFBF;
        background-color:#A9A9A9;
     }
     .half-holiday-day{
     	vertical-align:text-top;
        text-align:left;
        width:60px;
        height:30px;
        border:1px solid #BFBFBF;
        background:#C0C0C0;
     }
     span.event {
        font-size:0.75em;
     }
     .kjo-link {
        font-size:0.75em;
        text-align:center;
     }
     .event-title {
        text-align:center;
        font-weight:bold;
        font-size:1.2em;
     }
     .event-title-break {
        width:96%;
        margin-left:2%;
        margin-right:2%;
        margin-top:5px;
        margin-bottom:5px;
        text-align:center;
        height:1px;
        background-color:#000000;
     }
     .event-content-break {
        width:96%;
        margin-left:2%;
        margin-right:2%;
        margin-top:5px;
        margin-bottom:5px;
        text-align:center;
        height:1px;
        background-color:#000000;
     }
     .calendar-date-switcher {
        height:25px;
        text-align:center;
        border:1px solid #D6DED5;
        background-color:#E4EBE3;
     }
     .calendar-date-switcher form {
        margin:0;
        padding:0;
     }
     .calendar-date-switcher input {
        border:1px #D6DED5 solid;
     }
     .calendar-date-switcher select {
        border:1px #D6DED5 solid;
     }
     .cat-key {
        width:100%;
        margin-top:10px;
        padding:5px;
        border:1px solid #D6DED5;
     }";
     

  // Assume this is not a new install until we prove otherwise
  $new_install = false;
  $vone_point_one_upgrade = false;
  $vone_point_two_beta_upgrade = false;

  $wp_calendar_exists = false;
  $wp_calendar_config_exists = false;
  $wp_calendar_config_version_number_exists = false;

  // Determine the calendar version
  $tables = $wpdb->get_results("show tables;");
  foreach ( $tables as $table )
    {
      foreach ( $table as $value )
        {
	  if ( $value == WP_CALENDAR_TABLE_Aula )
	    {
	      $wp_calendar_exists = true;
	    }
	  if ( $value == WP_CALENDAR_CONFIG_TABLE_Aula )
            {
              $wp_calendar_config_exists = true;
              
	      // We now try and find the calendar version number
              // This will be a lot easier than finding other stuff 
              // in the future.
	      $version_number = $wpdb->get_var("SELECT config_value FROM " . WP_CALENDAR_CONFIG_TABLE_Aula . " WHERE config_item='calendar_version'"); 
	      if ($version_number == "1.2")
		{
		  $wp_calendar_config_version_number_exists = true;
		}
            }
        }
    }

  if ($wp_calendar_exists == false && $wp_calendar_config_exists == false)
    {
      $new_install = true;
    }
  else if ($wp_calendar_exists == true && $wp_calendar_config_exists == false)
    {
      $vone_point_one_upgrade = true;
    }
  else if ($wp_calendar_exists == true && $wp_calendar_config_exists == true && $wp_calendar_config_version_number_exists == false)
    {
      $vone_point_two_beta_upgrade = true;
    }

  // Now we've determined what the current install is or isn't 
  // we perform operations according to the findings
  if ( $new_install == true )
    {
      $sql = "CREATE TABLE " . WP_CALENDAR_TABLE_Aula . " (
                                event_id INT(11) NOT NULL AUTO_INCREMENT ,
                                event_begin DATE NOT NULL ,
                                event_end DATE NOT NULL ,
                                event_title VARCHAR(30) NOT NULL ,
                                event_desc TEXT NOT NULL ,
                                event_time_begin TIME ,
                                event_time_end TIME ,
                                event_recur CHAR(1) ,
                                event_repeats INT(3) ,
                                event_author BIGINT(20) UNSIGNED,
                                event_valid ENUM('y', 'n') NOT NULL DEFAULT 'n',
                                event_email VARCHAR(100) NULL,
                                event_phone VARCHAR(20) NULL,
                                event_dni VARCHAR(9) NULL,
                                event_department VARCHAR(100) NULL,
                                event_sector VARCHAR(100) NULL,
                                event_material TEXT NULL,
                                event_obs TEXT NULL,
                                PRIMARY KEY (event_id)
                        )";
      $wpdb->get_results($sql);
      $sql = "CREATE TABLE " . WP_CALENDAR_CONFIG_TABLE_Aula . " (
                                config_item VARCHAR(30) NOT NULL ,
                                config_value TEXT NOT NULL ,
                                PRIMARY KEY (config_item)
                        )";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='can_manage_events', config_value='edit_posts'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='calendar_style', config_value='".$initial_style."'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='display_author', config_value='false'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='display_jump', config_value='false'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='display_todays', config_value='true'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='display_upcoming', config_value='true'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='display_upcoming_days', config_value=7";
      $wpdb->get_results($sql);

      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='calendar_version', config_value='1.2'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='enable_categories', config_value='false'";
      $wpdb->get_results($sql);
      $sql = "ALTER TABLE ".WP_CALENDAR_TABLE_Aula." ADD COLUMN event_category BIGINT(20) UNSIGNED";
      $wpdb->get_results($sql);
      $sql = "UPDATE " . WP_CALENDAR_TABLE_Aula . " SET event_category=1";
      $wpdb->get_results($sql);
      $sql = "ALTER TABLE ".WP_CALENDAR_TABLE_Aula." ADD COLUMN event_link TEXT";
      $wpdb->get_results($sql);
      $sql = "UPDATE " . WP_CALENDAR_TABLE_Aula . " SET event_link=''";
      $wpdb->get_results($sql);
      $sql = "CREATE TABLE " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " ( 
                                category_id INT(11) NOT NULL AUTO_INCREMENT, 
                                category_name VARCHAR(30) NOT NULL , 
                                category_colour VARCHAR(30) NOT NULL , 
                                PRIMARY KEY (category_id) 
                             )";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " SET category_id=1, category_name='General', category_colour='#F6F79B'";
      $wpdb->get_results($sql);
    }
  else if ($vone_point_one_upgrade == true)
    {
      $sql = "ALTER TABLE ".WP_CALENDAR_TABLE_Aula." ADD COLUMN event_author BIGINT(20) UNSIGNED";
      $wpdb->get_results($sql);
      $sql = "UPDATE ".WP_CALENDAR_TABLE_Aula." SET event_author=".$wpdb->get_var("SELECT MIN(ID) FROM ".$wpdb->prefix."users",0,0);
      $wpdb->get_results($sql);
      $sql = "ALTER TABLE ".WP_CALENDAR_TABLE_Aula." MODIFY event_desc TEXT NOT NULL";
      $wpdb->get_results($sql);
      $sql = "CREATE TABLE " . WP_CALENDAR_CONFIG_TABLE_Aula . " (
                                config_item VARCHAR(30) NOT NULL ,
                                config_value TEXT NOT NULL ,
                                PRIMARY KEY (config_item)
                        )";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='can_manage_events', config_value='edit_posts'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='calendar_style', config_value='".$initial_style."'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='display_author', config_value='false'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='display_jump', config_value='false'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='display_todays', config_value='true'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='display_upcoming', config_value='true'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='display_upcoming_days', config_value=7";
      $wpdb->get_results($sql);

      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='calendar_version', config_value='1.2'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='enable_categories', config_value='false'";
      $wpdb->get_results($sql);
      $sql = "ALTER TABLE ".WP_CALENDAR_TABLE_Aula." ADD COLUMN event_category BIGINT(20) UNSIGNED";
      $wpdb->get_results($sql);
      $sql = "UPDATE " . WP_CALENDAR_TABLE_Aula . " SET event_category=1";
      $wpdb->get_results($sql);
      $sql = "ALTER TABLE ".WP_CALENDAR_TABLE_Aula." ADD COLUMN event_link TEXT";
      $wpdb->get_results($sql);
      $sql = "UPDATE " . WP_CALENDAR_TABLE_Aula . " SET event_link=''";
      $wpdb->get_results($sql);
      $sql = "CREATE TABLE " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " ( 
                                category_id INT(11) NOT NULL AUTO_INCREMENT, 
                                category_name VARCHAR(30) NOT NULL , 
                                category_colour VARCHAR(30) NOT NULL , 
                                PRIMARY KEY (category_id) 
                              )";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " SET category_id=1, category_name='General', category_colour='#F6F79B'";
      $wpdb->get_results($sql);
    }
  else if ($vone_point_two_beta_upgrade == true)
    {
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='calendar_version', config_value='1.2'";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO ".WP_CALENDAR_CONFIG_TABLE_Aula." SET config_item='enable_categories', config_value='false'";
      $wpdb->get_results($sql);
      $sql = "ALTER TABLE ".WP_CALENDAR_TABLE_Aula." ADD COLUMN event_category BIGINT(20) UNSIGNED";
      $wpdb->get_results($sql);
      $sql = "UPDATE " . WP_CALENDAR_TABLE_Aula . " SET event_category=1";
      $wpdb->get_results($sql);
      $sql = "ALTER TABLE ".WP_CALENDAR_TABLE_Aula." ADD COLUMN event_link TEXT";
      $wpdb->get_results($sql);
      $sql = "UPDATE " . WP_CALENDAR_TABLE_Aula . " SET event_link=''";
      $wpdb->get_results($sql);
      $sql = "CREATE TABLE " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " (
                                category_id INT(11) NOT NULL AUTO_INCREMENT, 
                                category_name VARCHAR(30) NOT NULL , 
                                category_colour VARCHAR(30) NOT NULL , 
                                PRIMARY KEY (category_id) 
                             )";
      $wpdb->get_results($sql);
      $sql = "INSERT INTO " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " SET category_id=1, category_name='General', category_colour='#F6F79B'";
      $wpdb->get_results($sql);
      $sql = "UPDATE " . WP_CALENDAR_CONFIG_TABLE_Aula . " SET config_value='".$initial_style."' WHERE config_item='calendar_style'";
      $wpdb->get_results($sql);
    }
}

// Used on the manage events admin page to display a list of events
function wp_events_display_listAula()
{
	global $wpdb;
	
	$events = $wpdb->get_results("SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " ORDER BY event_begin DESC");
	
	if ( !empty($events) )
	{
		?>
		<table class="widefat page fixed" width="100%" cellpadding="3" cellspacing="3">
		        <thead>
			    <tr>
				<th width="4%" class="manage-column" scope="col"><?php _e('ID','calendarAula') ?></th>
				<th width="6%" class="manage-column" scope="col"><?php _e('Title','calendarAula') ?></th>
				<th width="45%" class="manage-column" scope="col"><?php _e('Description','calendarAula') ?></th>
				<th width="6%" class="manage-column" scope="col"><?php _e('Start Date','calendarAula') ?></th>
				<th width="6%" class="manage-column" scope="col"><?php _e('End Date','calendarAula') ?></th>
				<th width="6%" class="manage-column" scope="col"><?php _e('Hora inicio','calendarAula') ?></th>
				<th width="5%" class="manage-column" scope="col"><?php _e('Hora fin','calendarAula') ?></th>
				<?php /*?><th width="5%" class="manage-column" scope="col"><?php _e('Recurs','calendarAula') ?></th>
				<th width="4%" class="manage-column" scope="col"><?php _e('Repeats','calendarAula') ?></th><?php */?>
		        <th width="5%" class="manage-column" scope="col"><?php _e('Author','calendarAula') ?></th>
		        <th width="6%" class="manage-column" scope="col"><?php _e('Category','calendarAula') ?></th>
		        <th width="3%" class="manage-column" scope="col"><?php _e('Válido','calendarAula') ?></th>
				<th width="4%" class="manage-column" scope="col"><?php _e('Edit','calendarAula') ?></th>
				<th width="4%" class="manage-column" scope="col"><?php _e('Delete','calendarAula') ?></th>
			    </tr>
		        </thead>
		<?php
		$class = '';
		foreach ( $events as $event )
		{
			$class = ($class == 'alternate') ? '' : 'alternate';
			?>
			<tr class="<?php echo $class; ?>">
				<th scope="row"><?php echo $event->event_id; ?></th>
				<td><?php echo $event->event_title; ?></td>
				<td><?php echo $event->event_desc; ?></td>
				<td><?php echo $event->event_begin; ?></td>
				<td><?php echo $event->event_end; ?></td>
				<td><?php echo $event->event_time_begin; ?></td>
				<td><?php echo $event->event_time_end; ?></td>
				<?php /*?><td>
				<?php 
					// Interpret the DB values into something human readable
					if ($event->event_recur == 'S') { echo __('Never','calendarAula'); } 
					else if ($event->event_recur == 'W') { echo __('Weekly','calendarAula'); }
					else if ($event->event_recur == 'M') { echo __('Monthly','calendarAula'); }
					else if ($event->event_recur == 'Y') { echo __('Yearly','calendarAula'); }
				?>
				</td>
				<td>
				<?php
				        // Interpret the DB values into something human readable
					if ($event->event_recur == 'S') { echo __('N/A','calendarAula'); }
					else if ($event->event_repeats == 0) { echo __('Forever','calendarAula'); }
					else if ($event->event_repeats > 0) { echo $event->event_repeats.' '.__('Times','calendarAula'); }					
				?>
				</td><?php */?>
				<td><?php $e = get_userdata($event->event_author); echo $e->display_name; ?></td>
                                <?php
				$sql = "SELECT * FROM " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " WHERE category_id=".$event->event_category;
                                $this_cat = $wpdb->get_row($sql);
                                ?>
				<td style="background-color:<?php echo $this_cat->category_colour;?>;"><?php echo $this_cat->category_name; ?></td>
				<?php unset($this_cat); ?>
				<td><?php if($event->event_valid == 'y') echo 'Sí'; else echo 'No'; ?></td>
				<td><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=calendarAula&amp;action=edit&amp;event_id=<?php echo $event->event_id;?>" class='edit'><?php echo __('Edit','calendarAula'); ?></a></td>
				<td><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=calendarAula&amp;action=delete&amp;event_id=<?php echo $event->event_id;?>" class="delete" onclick="return confirm('<?php _e('Are you sure you want to delete this event?','calendarAula'); ?>')"><?php echo __('Delete','calendarAula'); ?></a></td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php
	}
	else
	{
		?>
		<p><?php _e("There are no events in the database!",'calendarAula')	?></p>
		<?php	
	}
}


// The event edit form for the manage events admin page
function wp_events_edit_formAula($mode='add', $event_id=false)
{
	global $wpdb,$users_entries;
	$data = false;
	
	if ( $event_id !== false )
	{
		if ( intval($event_id) != $event_id )
		{
			echo "<div class=\"error\"><p>".__('Bad Monkey! No banana!','calendarAula')."</p></div>";
			return;
		}
		else
		{
			$data = $wpdb->get_results("SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_id='" . mysql_escape_string($event_id) . "' LIMIT 1");
			if ( empty($data) )
			{
				echo "<div class=\"error\"><p>".__("An event with that ID couldn't be found",'calendarAula')."</p></div>";
				return;
			}
			$data = $data[0];
		}
		// Recover users entries if they exist; in other words if editing an event went wrong
		if (!empty($users_entries))
		  {
		    $data = $users_entries;
		  }
	}
	// Deal with possibility that form was submitted but not saved due to error - recover user's entries here
	else
	  {
	    $data = $users_entries;
	  }
	
	?>
        <div id="pop_up_cal" style="position:absolute;margin-left:150px;visibility:hidden;background-color:white;layer-background-color:white;z-index:1;"></div>
	<form name="quoteform" id="quoteform" class="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=calendarAula">
		<input type="hidden" name="action" value="<?php echo $mode; ?>">
		<input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
		<input type="hidden" name="event_repeats" value="0">
		<input type="hidden" name="event_recur" value="S">
	
		<div id="linkadvanceddiv" class="postbox">
			<div style="float: left; width: 98%; clear: both;" class="inside">
                                <table cellpadding="5" cellspacing="5">
                                <tr>				
				<td><legend><?php _e('Event Title','calendarAula'); ?></legend></td>
				<td><input type="text" name="event_title" class="input" size="40" maxlength="30"
					value="<?php if ( !empty($data) ) echo htmlspecialchars($data->event_title); ?>" /></td>
                                </tr>
                                <tr>
				<td style="vertical-align:top;"><legend><?php _e('Event Description','calendarAula'); ?></legend></td>
				<td><textarea name="event_desc" class="input" rows="5" cols="50"><?php if ( !empty($data) ) echo htmlspecialchars($data->event_desc); ?></textarea></td>
                                </tr>
                                <tr>
				<td><legend><?php _e('Event Category','calendarAula'); ?></legend></td>
				<td>
					<script language="javascript" type="text/javascript">
						function deshabilitar(selectTag){
							/* Deshabilita el tag si el valor es la categoría Día Festivo o Día no lectivo*/
							if(selectTag.value == 4){
								document.getElementById('event_time_begin').value = '00:00';
								document.getElementById('event_time_begin').disabled = true;
								document.getElementById('event_time_end').value = '00:00';
								document.getElementById('event_time_end').disabled = true;
							}else{
								if(selectTag.value == 5){
									document.getElementById('event_time_begin').value = '14:00';
									document.getElementById('event_time_begin').disabled = true;
									document.getElementById('event_time_end').value = '23:59';
									document.getElementById('event_time_end').disabled = true;
								}else{
									document.getElementById('event_time_begin').disabled = false;
									document.getElementById('event_time_end').disabled = false;
								}

							}
						}
					</script>
					 <select name="event_category" onchange="deshabilitar(this)">
					     <?php
					         // Grab all the categories and list them
						 $sql = "SELECT * FROM " . WP_CALENDAR_CATEGORIES_TABLE_Aula;
	                                         $cats = $wpdb->get_results($sql);
                                                 foreach($cats as $cat)
						   {
						     echo '<option value="'.$cat->category_id.'"';
                                                     if (!empty($data))
						       {
							 if ($data->event_category == $cat->category_id)
							   {
							     echo 'selected="selected"';
							   }
						       }
                                                     echo '>'.$cat->category_name.'</option>
';
						   }
                                             ?>
                                         </select>
                                </td>
                                </tr>
                                <?php /*?><tr>
				<td><legend><?php _e('Event Link (Optional)','calendarAula'); ?></legend></td>
                                <td><input type="text" name="event_link" class="input" size="40" value="<?php if ( !empty($data) ) echo htmlspecialchars($data->event_link); ?>" /></td>
                                </tr><?php */?>
                                <tr>
				<td><legend><?php _e('Start Date','calendarAula'); ?></legend></td>
                                <td>        <script type="text/javascript">
					var cal_begin = new CalendarPopupAula('pop_up_cal');
					function unifydatesAula() {
					  document.forms['quoteform'].event_end.value = document.forms['quoteform'].event_begin.value;
					}
					</script>
					<input type="text" name="event_begin" class="input" size="12"
					value="<?php 
					if ( !empty($data) ) 
					{
						echo htmlspecialchars($data->event_begin);
					}
					else
					{
						echo date("Y-m-d");
					} 
					?>" /> <a href="#" onClick="cal_begin.select(document.forms['quoteform'].event_begin,'event_begin_anchor','yyyy-MM-dd'); return false;" name="event_begin_anchor" id="event_begin_anchor"><?php _e('Select Date','calendarAula'); ?></a>
				</td>
                                </tr>
                                <tr>
				<td><legend><?php _e('End Date','calendarAula'); ?></legend></td>
                                <td>    <script type="text/javascript">
					function check_and_printAula() {
					unifydatesAula();
					var cal_end = new CalendarPopupAula('pop_up_cal');
					var newDate = new Date();
					newDate.setFullYear(document.forms['quoteform'].event_begin.value.split('-')[0],document.forms['quoteform'].event_begin.value.split('-')[1]-1,document.forms['quoteform'].event_begin.value.split('-')[2]);
					newDate.setDate(newDate.getDate()-1);
                                        cal_end.addDisabledDates(null, formatDateAula(newDate, "yyyy-MM-dd"));
                                        cal_end.select(document.forms['quoteform'].event_end,'event_end_anchor','yyyy-MM-dd');
					}
                                        </script>
					<input type="text" name="event_end" class="input" size="12"
					value="<?php 
					if ( !empty($data) ) 
					{
						echo htmlspecialchars($data->event_end);
					}
					else
					{
						echo date("Y-m-d");
					}
					?>" />  <a href="#" onClick="check_and_printAula(); return false;" name="event_end_anchor" id="event_end_anchor"><?php _e('Select Date','calendarAula'); ?></a>
				</td>
                                </tr>
                                <tr>
				<td><legend><?php _e('Hora Principio (hh:mm)','calendarAula'); ?></legend></td>
				<td>	<input type="text" id="event_time_begin" name="event_time_begin" class="input" size=12
					value="<?php 
					if ( !empty($data) ) 
					{
						if ($data->event_time_begin == "00:00:00")
						{
							echo '';
						}
						else
						{
							echo date("H:i",strtotime(htmlspecialchars($data->event_time_begin)));
						}
					}
					else
					{
						echo date("H:i");
					}
					?>" /> <?php _e('Optional, set blank if not required.','calendarAula'); ?>
				</td>
                                </tr>
                                <tr>
				<td><legend><?php _e('Hora Fin (hh:mm)','calendarAula'); ?></legend></td>
				<td>	<input type="text" id="event_time_end" name="event_time_end" class="input" size=12
					value="<?php 
					if ( !empty($data) ) 
					{
						if ($data->event_time_end == "00:00:00")
						{
							echo '';
						}
						else
						{
							echo date("H:i",strtotime(htmlspecialchars($data->event_time_end)));
						}
					}
					else
					{
						echo date("H:i");
					}
					?>" /> <?php _e('Optional, set blank if not required.','calendarAula'); ?>
				</td>
                                </tr>
                                <?php /*?><tr>
				<td><legend><?php _e('Recurring Events','calendarAula'); ?></legend></td>
				<td>	<?php
					if ($data->event_repeats != NULL)
					{
						$repeats = $data->event_repeats;
					}
					else
					{
						$repeats = 0;
					}

					if ($data->event_recur == "S")
					{
						$selected_s = 'selected="selected"';
					}
					else if ($data->event_recur == "W")
					{
						$selected_w = 'selected="selected"';
					}
					else if ($data->event_recur == "M")
					{
						$selected_m = 'selected="selected"';
					}
					else if ($data->event_recur == "Y")
					{
						$selected_y = 'selected="selected"';
					}
					?>
					  <?php _e('Repeats for','calendarAula'); ?> 
					<input type="text" name="event_repeats" class="input" size="1" value="<?php echo $repeats; ?>" /> 
					<select name="event_recur" class="input">
						<option class="input" <?php echo $selected_s; ?> value="S">Ninguno</option>
						<option class="input" <?php echo $selected_w; ?> value="W">Semanas</option>
						<option class="input" <?php echo $selected_m; ?> value="M">Meses</option>
						<option class="input" <?php echo $selected_y; ?> value="Y">Años</option>
					</select><br />
					<?php _e('Entering 0 means forever. Where the recurrance interval is left at none, the event will not reoccur.','calendarAula'); ?>
				</td>
				</tr><?php */?>
				<tr>
				<td><legend><?php _e('Evento válido','calendarAula'); ?></legend></td>
				<td>	<?php
					if ($data->event_valid != NULL)
					{
						$valid = $data->event_valid;
					}
					else
					{
						$valid = 'n';
					}

					if ($data->event_valid == "y")
					{
						$selected_valid_y = 'selected="selected"';
					}
					else if ($data->event_valid == "n")
					{
						$selected_valid_n = 'selected="selected"';
					}
					?>
					<select name="event_valid" class="input">
						<option class="input" <?php echo $selected_valid_y; ?> value="y">Sí</option>
						<option class="input" <?php echo $selected_valid_n; ?> value="n">No</option>
					</select><br />
				</td>

              </tr>
                                
          </table>
			</div>
			<div style="clear:both; height:1px;">&nbsp;</div>
		</div>
                <input type="submit" name="save" class="button bold" value="<?php _e('Save','calendarAula'); ?> &raquo;" />
	</form>
	<?php
}

// The actual function called to render the manage events page and 
// to deal with posts
function edit_calendarAula()
{
    global $current_user, $wpdb, $users_entries;
  ?>
  <style type="text/css">
<!--
	.error {
	  background: lightcoral;
	  border: 1px solid #e64f69;
	  margin: 1em 5% 10px;
	  padding: 0 1em 0 1em;
	}

	.center { 
	  text-align: center;	
	}
	.right { text-align: right;	
	}
        .left { 
	  text-align: left;		
	}
	.top { 
	  vertical-align: top;	
	}
	.bold { 
	  font-weight: bold; 
	}
	.private { 
	  color: #e64f69;		
	}
//-->
</style>

<?php

// First some quick cleaning up 
$edit = $create = $save = $delete = false;

// Make sure we are collecting the variables we need to select years and months
$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
$event_id = !empty($_REQUEST['event_id']) ? $_REQUEST['event_id'] : '';


// Lets see if this is first run and create us a table if it is!
check_calendarAula();

// Deal with adding an event to the database
if ( $action == 'add' )
{
	$title = !empty($_REQUEST['event_title']) ? $_REQUEST['event_title'] : '';
	$desc = !empty($_REQUEST['event_desc']) ? $_REQUEST['event_desc'] : '';
	$begin = !empty($_REQUEST['event_begin']) ? $_REQUEST['event_begin'] : '';
	$end = !empty($_REQUEST['event_end']) ? $_REQUEST['event_end'] : '';
	$time_begin = !empty($_REQUEST['event_time_begin']) ? $_REQUEST['event_time_begin'] : '';
	$time_end = !empty($_REQUEST['event_time_end']) ? $_REQUEST['event_time_end'] : '';
	$recur = !empty($_REQUEST['event_recur']) ? $_REQUEST['event_recur'] : '';
	$repeats = !empty($_REQUEST['event_repeats']) ? $_REQUEST['event_repeats'] : '';
	$category = !empty($_REQUEST['event_category']) ? $_REQUEST['event_category'] : '';
    $linky = !empty($_REQUEST['event_link']) ? $_REQUEST['event_link'] : '';
    $valid = !empty($_REQUEST['event_valid']) ? $_REQUEST['event_valid'] : '';

	// Deal with the fools who have left magic quotes turned on
	if ( ini_get('magic_quotes_gpc') )
	{
		$title = stripslashes($title);
		$desc = stripslashes($desc);
		$begin = stripslashes($begin);
		$end = stripslashes($end);
		$time_begin = stripslashes($time_begin);
		$time_end = stripslashes($time_end);
		$recur = stripslashes($recur);
		$repeats = stripslashes($repeats);
        $category = stripslashes($category);
        $linky = stripslashes($linky);
        $valid = stripslashes($valid);
	}	

	// Perform some validation on the submitted dates - this checks for valid years and months
	$date_format_one = '/^([0-9]{4})-([0][1-9])-([0-3][0-9])$/';
    $date_format_two = '/^([0-9]{4})-([1][0-2])-([0-3][0-9])$/';
	if ((preg_match($date_format_one,$begin) || preg_match($date_format_two,$begin)) && (preg_match($date_format_one,$end) || preg_match($date_format_two,$end)))
	  {
            // We know we have a valid year and month and valid integers for days so now we do a final check on the date
            $begin_split = split('-',$begin);
	    $begin_y = $begin_split[0]; 
	    $begin_m = $begin_split[1];
	    $begin_d = $begin_split[2];
            $end_split = split('-',$end);
	    $end_y = $end_split[0];
	    $end_m = $end_split[1];
	    $end_d = $end_split[2];
            if (checkdate($begin_m,$begin_d,$begin_y) && checkdate($end_m,$end_d,$end_y))
	     {
	       // Ok, now we know we have valid dates, we want to make sure that they are either equal or that the end date is later than the start date
	       if (strtotime($end) >= strtotime($begin))
		 {
		   $start_date_ok = 1;
		   $end_date_ok = 1;
		 }
	       else
		 {
		   ?>
		   <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('Your event end date must be either after or the same as your event begin date','calendarAula'); ?></p></div>
		   <?php
		 }
	     } 
	    else
	      {
		?>
                <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('Your date formatting is correct but one or more of your dates is invalid. Check for number of days in month and leap year related errors.','calendarAula'); ?></p></div>
                <?php
	      }
	  }
	else
	  {
	    ?>
            <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('Both start and end dates must be entered and be in the format YYYY-MM-DD','calendarAula'); ?></p></div>
            <?php
	  }
	  
	  //BANK-HOLIDAY
	//Perform some validation on the submitted category - this checks for valid category (it can't be bank-holiday-day to submit a new event)
	$is_bank_holiday = 'n';
	
	if(($start_date_ok == 1) && ($end_date_ok == 1))
	{
		$is_bank_holiday = is_bank_holiday($begin,$end);
		
		if($is_bank_holiday == 'y')
		{
			?>
            <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('Lo siento, el día elegido es día festivo.','calendarAula'); ?></p></div>
           <?php
		}
	}	
	
        $time_begin_ok = 0;
        // We check for a valid time_begin, or an empty one
        $time_begin_format_one = '/^([0-1][0-9]):([0-5][0-9])$/';
		$time_begin_format_two = '/^([2][0-3]):([0-5][0-9])$/';
        if (preg_match($time_begin_format_one,$time_begin) || preg_match($time_begin_format_two,$time_begin) || $time_begin == '' || $time_end == '')
        {
        	// Ok, now we know we have valid times, we want to make sure that they are either equal or that the end time is later than the start time
	       	if (strtotime($time_end) >= strtotime($time_begin))
		 	{
		   		//To check if already exists a event on this day, time and category
            	$query_event_time = "SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_category='" . mysql_escape_string($category) . "' ORDER BY event_begin DESC";
	    		$query_event_time_results = $wpdb->get_results($query_event_time);
	    		
	    		//If there aren't events of this category
		 		if(empty($query_event_time_results)):
	    				$time_begin_ok = 1;		
	    		endif;
	    		
	    		foreach ($query_event_time_results as $query_event_time_result)
	    		{
	    			
	    			if(is_the_same_day($begin, $end, $query_event_time_result->event_begin, $query_event_time_result->event_end) == 1)
					{
						if(is_the_same_time($time_begin, $time_end, $query_event_time_result->event_time_begin, $query_event_time_result->event_time_end) == 1)
		 				{
		 					?><div class="error">
            					<p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('El aula no está disponible esa hora','calendarAula'); ?>
            					</p>
            				</div><?php
            				$time_begin_ok = 0;
            				break;
		 				}
		 				else
		 				{
		 					$time_begin_ok = 1;
		 				}
					}
					else
					{
						$time_begin_ok = 1;
					}			
	    		}//end foreach	
		 	}
	       	else
		 	{
		   		?><div class="error">
		   			<p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('La hora de inicio debe ser menor o igual que la fecha fin','calendarAula'); ?>
		   			</p>
		   		</div><?php
		 	}            
        }
        else
        {
            ?>
            <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('La hora de inicio debe ser menor o igual que la fecha fin, estar en blanco o tener el formato hh:mm','calendarAula'); ?></p></div>
            <?php
	  	}
	  // We check for a valid time_end, or an empty one
        $time_end_format_one = '/^([0-1][0-9]):([0-5][0-9])$/';
		$time_end_format_two = '/^([2][0-3]):([0-5][0-9])$/';
        if (preg_match($time_end_format_one,$time_end) || preg_match($time_end_format_two,$time_end) || $time_begin == '' || $time_end == '')
          {
            
            // Ok, now we know we have valid times, we want to make sure that they are either equal or that the end time is later than the start time
	       	if (strtotime($time_end) >= strtotime($time_begin))
		 	{
            	$time_end_ok = 1; 	
		 	}
	     	else
		 	{
		   		?>
		   		<div class="error">
		   			<p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('La hora de inicio debe ser menor o igual que la fecha fin','calendarAula'); ?>
		   			</p>
		   		</div>
		   		<?php
		 	}
	
          }
        else
          {
            ?>
            <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('The time field must either be blank or be entered in the format hh:mm','calendarAula'); ?></p></div>
            <?php
	  }
	  
	  //HALF-HOLIDAY 
	//Perform some validation on the submitted category - this checks for valid category (it can't be bank-holiday-day to submit a new event)
	$is_half_holiday = 'n';
	
	if(($start_date_ok == 1) && ($end_date_ok == 1) && ($is_bank_holiday == 'n') && ($time_begin_ok == 1) && ($time_end_ok == 1))
	{
		$is_half_holiday = is_half_holiday($begin,$end,$time_begin,$time_end);
		
		if($is_half_holiday == 'y')
		{
			?>
            <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('Lo siento, el día elegido es no lectivo, por lo que sólo se puede reservar de 9 a 2.','calendarAula'); ?></p></div>
           <?php
		}
	}


	// We check to make sure the URL is alright                                                        
	if (preg_match('/^(http)(s?)(:)\/\//',$linky) || $linky == '')
	  {
	    $url_ok = 1;
	  }
	else
	  {
              ?>
              <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('The URL entered must either be prefixed with http:// or be completely blank','calendarAula'); ?></p></div>
              <?php
	  }
	// The title must be at least one character in length and no more than 30 - no non-standard characters allowed
	if (preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\ ]{0,29}$/',$title))
	  {
	    $title_ok =1;
	  }
	else
	  {
              ?>
              <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('The event title must be between 1 and 30 characters in length and contain no punctuation. Spaces are allowed but the title must not start with one.','calendarAula'); ?></p></div>
              <?php
	  }
	// We run some checks on recurrance                                                                        
	if (($repeats == 0 && $recur == 'S') || (($repeats >= 0) && ($recur == 'W' || $recur == 'M' || $recur == 'Y')))
	  {
	    $recurring_ok = 1;
	  }
	else
	  {
              ?>
              <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('The repetition value must be 0 unless a type of recurrance is selected in which case the repetition value must be 0 or higher','calendarAula'); ?></p></div>
              <?php
	  }



	if (($start_date_ok == 1) && ($end_date_ok == 1) && ($time_begin_ok == 1) && ($time_end_ok == 1) && ($url_ok == 1) && ($title_ok == 1) && ($recurring_ok == 1) && ($is_bank_holiday == 'n') && ($is_half_holiday == 'n'))
	  {
	    $sql = "INSERT INTO " . WP_CALENDAR_TABLE_Aula . " SET event_title='" . mysql_escape_string($title)
	     . "', event_desc='" . mysql_escape_string($desc) . "', event_begin='" . mysql_escape_string($begin) 
             . "', event_end='" . mysql_escape_string($end) . "', event_time_begin='" . mysql_escape_string($time_begin) . "', event_time_end='" . mysql_escape_string($time_end) . "', event_recur='" . mysql_escape_string($recur) . "', event_repeats='" . mysql_escape_string($repeats) . "', event_author=".$current_user->ID.", event_category=".mysql_escape_string($category).", event_link='".mysql_escape_string($linky)."', event_valid='".mysql_escape_string($valid)."'";
	     
	    $wpdb->get_results($sql);
	    
	    //To send an email with the event notification
	    $author_event_email = $current_user->ID;
	    //sendEmailPsico($title, $desc, $begin, $end, $category);
	    
	   
	
	    $sql = "SELECT event_id FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_title='" . mysql_escape_string($title) . "'"
		. " AND event_desc='" . mysql_escape_string($desc) . "' AND event_begin='" . mysql_escape_string($begin) . "' AND event_end='" . mysql_escape_string($end) . "' AND event_recur='" . mysql_escape_string($recur) . "' AND event_repeats='" . mysql_escape_string($repeats) . "' LIMIT 1";
	    $result = $wpdb->get_results($sql);
	
	    if ( empty($result) || empty($result[0]->event_id) )
	      {
                ?>
		<div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('An event with the details you submitted could not be found in the database. This may indicate a problem with your database or the way in which it is configured.','calendarAula'); ?></p></div>
		<?php
	      }
	    else
	      {
		?>
		<div class="updated"><p><?php _e('Event added. It will now show in your calendar.','calendarAula'); ?></p></div>
		<?php
	      }
	  }
	else
	  {
	    // The form is going to be rejected due to field validation issues, so we preserve the users entries here
	    $users_entries->event_title = $title;
	    $users_entries->event_desc = $desc;
	    $users_entries->event_begin = $begin;
	    $users_entries->event_end = $end;
	    $users_entries->event_time_begin = $time_begin;
	    $users_entries->event_time_end = $time_end;
	    $users_entries->event_recur = $recur;
	    $users_entries->event_repeats = $repeats;
	    $users_entries->event_category = $category;
	    $users_entries->event_link = $linky;
	    $users_entries->event_valid = $valid;
	  }
}
// Permit saving of events that have been edited
elseif ( $action == 'edit_save' )
{	
	$title = !empty($_REQUEST['event_title']) ? $_REQUEST['event_title'] : '';
	$desc = !empty($_REQUEST['event_desc']) ? $_REQUEST['event_desc'] : '';
	$begin = !empty($_REQUEST['event_begin']) ? $_REQUEST['event_begin'] : '';
	$end = !empty($_REQUEST['event_end']) ? $_REQUEST['event_end'] : '';
	$time_begin = !empty($_REQUEST['event_time_begin']) ? $_REQUEST['event_time_begin'] : '';
	$time_end = !empty($_REQUEST['event_time_end']) ? $_REQUEST['event_time_end'] : '';
	$recur = !empty($_REQUEST['event_recur']) ? $_REQUEST['event_recur'] : '';
	$repeats = !empty($_REQUEST['event_repeats']) ? $_REQUEST['event_repeats'] : '';
	$category = !empty($_REQUEST['event_category']) ? $_REQUEST['event_category'] : '';
    $linky = !empty($_REQUEST['event_link']) ? $_REQUEST['event_link'] : '';
    $valid = !empty($_REQUEST['event_valid']) ? $_REQUEST['event_valid'] : '';

	// Deal with the fools who have left magic quotes turned on
	if ( ini_get('magic_quotes_gpc') )
	{
		$title = stripslashes($title);
		$desc = stripslashes($desc);
		$begin = stripslashes($begin);
		$end = stripslashes($end);
		$time_begin = stripslashes($time_begin);
		$time_end = stripslashes($time_end);
		$recur = stripslashes($recur);
		$repeats = stripslashes($repeats);
        $category = stripslashes($category);
        $linky = stripslashes($linky);
        $valid = stripslashes($valid);
	}
	
	if ( empty($event_id) )
	{
		?>
		<div class="error"><p><strong><?php _e('Failure','calendarAula'); ?>:</strong> <?php _e("You can't update an event if you haven't submitted an event id",'calendarAula'); ?></p></div>
		<?php		
	}
	else
	{
	  // Perform some validation on the submitted dates - this checks for valid years and months
      $date_format_one = '/^([0-9]{4})-([0][1-9])-([0-3][0-9])$/';
	  $date_format_two = '/^([0-9]{4})-([1][0-2])-([0-3][0-9])$/';
	  if ((preg_match($date_format_one,$begin) || preg_match($date_format_two,$begin)) && (preg_match($date_format_one,$end) || preg_match($date_format_two,$end)))
	    {
	      // We know we have a valid year and month and valid integers for days so now we do a final check on the date
          $begin_split = split('-',$begin);
	      $begin_y = $begin_split[0];
	      $begin_m = $begin_split[1];
	      $begin_d = $begin_split[2];
	      $end_split = split('-',$end);
	      $end_y = $end_split[0];
	      $end_m = $end_split[1];
	      $end_d = $end_split[2];
	      if (checkdate($begin_m,$begin_d,$begin_y) && checkdate($end_m,$end_d,$end_y))
		{
		  // Ok, now we know we have valid dates, we want to make sure that they are either equal or that the end date is later than the start date
                  if (strtotime($end) >= strtotime($begin))
		    {
		      $start_date_ok = 1;
		      $end_date_ok = 1;
		    }
		  else
		    {
                      ?>
                      <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('Your event end date must be either after or the same as your event begin date','calendarAula'); ?></p></div>
                      <?php
                    }
		}
	      else
		{
                ?>
                <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('Your date formatting is correct but one or more of your dates is invalid. Check for number of days in month and leap year related errors.','calendarAula'); ?></p></div>
                <?php
                }
	    }
	  else
	    {
            ?>
            <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('Both start and end dates must be entered and be in the format YYYY-MM-DD','calendarAula'); ?></p></div>
            <?php
	    }
	    
	    //BANK-HOLIDAY
	//Perform some validation on the submitted category - this checks for valid category (it can't be bank-holiday-day to submit a new event)
	$is_bank_holiday = 'n';
	
	if(($start_date_ok == 1) && ($end_date_ok == 1))
	{
		$is_bank_holiday = is_bank_holiday($begin,$end);
		
		if($is_bank_holiday == 'y')
		{
			?>
            <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('Lo siento, el día elegido es día festivo.','calendarAula'); ?></p></div>
           <?php
		}
	}	
	  
	  $time_begin_ok = 0;
	  // We check for a valid time_begin, or an empty one
	  $time_begin_format_one = '/^([0-1][0-9]):([0-5][0-9])$/';
	  $time_begin_format_two = '/^([2][0-3]):([0-5][0-9])$/';
	  if (preg_match($time_begin_format_one,$time_begin) || preg_match($time_begin_format_two,$time_begin) || $time_begin == '')
	    {
	      	// Ok, now we know we have valid times, we want to make sure that they are either equal or that the end time is later than the start time
	       	if (strtotime($time_end) >= strtotime($time_begin))
		 	{
		   		//To check if already exists a event on this day, time and category
            	$query_event_time2 = "SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_category='" . mysql_escape_string($category) . "' ORDER BY event_begin DESC EXCEPT SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_id='".mysql_escape_string($event_id)."'";
	    		$query_event_time_results2 = $wpdb->get_results($query_event_time2);
	    		
	    		//If there aren't events of this category
		 		if(empty($query_event_time_results2)):
	    				$time_begin_ok = 1;		
	    		endif;
	    		
	    		foreach ($query_event_time_results2 as $query_event_time_result2)
	    		{
	    			
	    			if(is_the_same_day($begin, $end, $query_event_time_result2->event_begin, $query_event_time_result2->event_end) == 1)
					{
						if(is_the_same_time($time_begin, $time_end, $query_event_time_result2->event_time_begin, $query_event_time_result2->event_time_end) == 1)
		 				{
		 					?><div class="error">
            					<p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('El aula no está disponible esa hora','calendarAula'); ?>
            					</p>
            				</div><?php
            				$time_begin_ok = 0;
            				break;
		 				}
		 				else
		 				{
		 					$time_begin_ok = 1;
		 				}
					}
					else
					{
						$time_begin_ok = 1;
					}			
	    		}//end foreach	
		 	}
	       	else{
		   		?><div class="error">
		   			<p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('La hora de inicio debe ser menor o igual que la fecha fin','calendarAula'); ?>
		   			</p>
		   		</div><?php
		 	} 	            
	    }
	  else
	    {
            ?>
            <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('La hora de inicio debe ser menor o igual que la fecha fin o estar en blanco','calendarAula'); ?></p></div>
            <?php
	    }
	    // We check for a valid time_end, or an empty one
	  $time_end_format_one = '/^([0-1][0-9]):([0-5][0-9])$/';
	  $time_end_format_two = '/^([2][0-3]):([0-5][0-9])$/';
	  if (preg_match($time_end_format_one,$time_end) || preg_match($time_end_format_two,$time_end) || $time_end == '')
	    {
	      // Ok, now we know we have valid times, we want to make sure that they are either equal or that the end time is later than the start time
	       	if (strtotime($time_end) >= strtotime($time_begin))
		 	{
            	$time_end_ok = 1;
		 	}
	     	else
		 	{
		   		?>
		   		<div class="error">
		   			<p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('La hora de inicio debe ser menor o igual que la fecha fin','calendarAula'); ?>
		   			</p>
		   		</div>
		   		<?php
		 	}

        	     
		 	
	    }
	  else
	    {
            ?>
            <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('La hora de inicio debe ser menor o igual que la fecha fin o estar en blanco con el formato hh:mm','calendarAula'); ?></p></div>
            <?php
	    }
	    
	    //HALF-HOLIDAY 
	//Perform some validation on the submitted category - this checks for valid category (it can't be bank-holiday-day to submit a new event)
	$is_half_holiday = 'n';
	
	if(($start_date_ok == 1) && ($end_date_ok == 1) && ($is_bank_holiday == 'n') && ($time_begin_ok == 1) && ($time_end_ok == 1))
	{
		$is_half_holiday = is_half_holiday($begin,$end,$time_begin,$time_end);
		
		if($is_half_holiday == 'y')
		{
			?>
            <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('Lo siento, el día elegido es no lectivo, por lo que sólo se puede reservar de 9 a 2.','calendarAula'); ?></p></div>
           <?php
		}
	}
	
	
          // We check to make sure the URL is alright
	  if (preg_match('/^(http)(s?)(:)\/\//',$linky) || $linky == '')
	    {
	      $url_ok = 1;
	    }
	  else
	    {
	      ?>
	      <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('The URL entered must either be prefixed with http:// or be completely blank','calendarAula'); ?></p></div>
	      <?php
	    }
	  // The title must be at least one character in length and no more than 30 - no non-standard characters allowed
	  if (preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\ ]{0,29}$/',$title))
            {
	      $title_ok =1;
	    }
          else
            {
	      ?>
              <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('The event title must be between 1 and 30 characters in length and contain no punctuation. Spaces are allowed but the title must not start with one.','calendarAula'); ?></p></div>
              <?php
	    }
	  // We run some checks on recurrance              
          if (($repeats == 0 && $recur == 'S') || (($repeats >= 0) && ($recur == 'W' || $recur == 'M' || $recur == 'Y')))
            {
              $recurring_ok = 1;
            }
          else
            {
              ?>
              <div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('The repetition value must be 0 unless a type of recurrance is selected in which case the repetition value must be 0 or higher','calendarAula'); ?></p></div>
              <?php
	    }
	  

	  if (($start_date_ok == 1) && ($end_date_ok == 1) && ($time_begin_ok == 1) && ($time_end_ok == 1) && ($url_ok == 1) && ($title_ok == 1) && ($recurring_ok == 1) && ($is_bank_holiday == 'n') && ($is_half_holiday == 'n'))
	    {
		$sql = "UPDATE " . WP_CALENDAR_TABLE_Aula . " SET event_title='" . mysql_escape_string($title)
		     . "', event_desc='" . mysql_escape_string($desc) . "', event_begin='" . mysql_escape_string($begin) 
                     . "', event_end='" . mysql_escape_string($end) . "', event_time_begin='" . mysql_escape_string($time_begin) . "', event_time_end='" . mysql_escape_string($time_end) . "', event_recur='" . mysql_escape_string($recur) . "', event_repeats='" . mysql_escape_string($repeats) . "', event_author=".$current_user->ID . ", event_category=".mysql_escape_string($category).", event_link='".mysql_escape_string($linky)."', event_valid='".mysql_escape_string($valid)."' WHERE event_id='" . mysql_escape_string($event_id) . "'";
		     
		$wpdb->get_results($sql);
		
		$sql = "SELECT event_id FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_title='" . mysql_escape_string($title) . "'"
		     . " AND event_desc='" . mysql_escape_string($desc) . "' AND event_begin='" . mysql_escape_string($begin) . "' AND event_end='" . mysql_escape_string($end) . "' AND event_recur='" . mysql_escape_string($recur) . "' AND event_repeats='" . mysql_escape_string($repeats) . "' LIMIT 1";
		$result = $wpdb->get_results($sql);
		
		if ( empty($result) || empty($result[0]->event_id) )
		{
			?>
			<div class="error"><p><strong><?php _e('Failure','calendarAula'); ?>:</strong> <?php _e('The database failed to return data to indicate the event has been updated sucessfully. This may indicate a problem with your database or the way in which it is configured.','calendarAula'); ?></p></div>
			<?php
		}
		else
		{
			?>
			<div class="updated"><p><?php _e('Event updated successfully','calendarAula'); ?></p></div>
			<?php
		}
	    }
          else
	    {
	      // The form is going to be rejected due to field validation issues, so we preserve the users entries here
          $users_entries->event_title = $title;
	      $users_entries->event_desc = $desc;
	      $users_entries->event_begin = $begin;
	      $users_entries->event_end = $end;
	      $users_entries->event_time_begin = $time_begin;
	      $users_entries->event_time_end = $time_end;
	      $users_entries->event_recur = $recur;
	      $users_entries->event_repeats = $repeats;
	      $users_entries->event_category = $category;
	      $users_entries->event_link = $linky;
	      $users_entries->event_valid = $valid;
	      $error_with_saving = 1;
	    }		
	}
}
// Deal with deleting an event from the database
elseif ( $action == 'delete' )
{
	if ( empty($event_id) )
	{
		?>
		<div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e("You can't delete an event if you haven't submitted an event id",'calendarAula'); ?></p></div>
		<?php			
	}
	else
	{
		$sql = "DELETE FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_id='" . mysql_escape_string($event_id) . "'";
		$wpdb->get_results($sql);
		
		$sql = "SELECT event_id FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_id='" . mysql_escape_string($event_id) . "'";
		$result = $wpdb->get_results($sql);
		
		if ( empty($result) || empty($result[0]->event_id) )
		{
			?>
			<div class="updated"><p><?php _e('Event deleted successfully','calendarAula'); ?></p></div>
			<?php
		}
		else
		{
			?>
			<div class="error"><p><strong><?php _e('Error','calendarAula'); ?>:</strong> <?php _e('Despite issuing a request to delete, the event still remains in the database. Please investigate.','calendarAula'); ?></p></div>
			<?php

		}		
	}
}

// Now follows a little bit of code that pulls in the main 
// components of this page; the edit form and the list of events
?>

<div class="wrap">
	<?php
	if ( $action == 'edit' || ($action == 'edit_save' && $error_with_saving == 1))
	{
		?>
		<h2><?php _e('Edit Event','calendarAula'); ?></h2>
		<?php
		if ( empty($event_id) )
		{
			echo "<div class=\"error\"><p>".__("You must provide an event id in order to edit it",'calendarAula')."</p></div>";
		}
		else
		{
			wp_events_edit_formAula('edit_save', $event_id);
		}	
	}
	else
	{
		?>
		<h2><?php _e('Add Event','calendarAula'); ?></h2>
		<?php wp_events_edit_formAula(); ?>
	
		<h2><?php _e('Manage Events','calendarAula'); ?></h2>
		<?php
			wp_events_display_listAula();
	}
	?>
</div>

<?php
 
}

// Display the admin configuration page
function edit_calendar_configAula()
{
  global $wpdb, $initial_style;

  // We can't use this page unless Calendar is installed/upgraded
  check_calendarAula();

  if (isset($_POST['permissions']) && isset($_POST['style']))
    {
      if ($_POST['permissions'] == 'subscriber') { $new_perms = 'read'; }
      else if ($_POST['permissions'] == 'contributor') { $new_perms = 'edit_posts'; }
      else if ($_POST['permissions'] == 'author') { $new_perms = 'publish_posts'; }
      else if ($_POST['permissions'] == 'editor') { $new_perms = 'moderate_comments'; }
      else if ($_POST['permissions'] == 'admin') { $new_perms = 'manage_options'; }
      else { $new_perms = 'manage_options'; }

      $calendar_style = mysql_escape_string($_POST['style']);
      $display_upcoming_days = mysql_escape_string($_POST['display_upcoming_days']);

      if (mysql_escape_string($_POST['display_author']) == 'on')
	{
	  $disp_author = 'true';
	}
      else
	{
	  $disp_author = 'false';
	}

      if (mysql_escape_string($_POST['display_jump']) == 'on')
        {
          $disp_jump = 'true';
        }
      else
        {
          $disp_jump = 'false';
        }

      if (mysql_escape_string($_POST['display_todays']) == 'on')
        {
          $disp_todays = 'true';
        }
      else
        {
          $disp_todays = 'false';
        }

      if (mysql_escape_string($_POST['display_upcoming']) == 'on')
        {
          $disp_upcoming = 'true';
        }
      else
        {
          $disp_upcoming = 'false';
        }

      if (mysql_escape_string($_POST['enable_categories']) == 'on')
        {
          $enable_categories = 'true';
        }
      else
        {
	  $enable_categories = 'false';
        }

      $wpdb->get_results("UPDATE " . WP_CALENDAR_CONFIG_TABLE_Aula . " SET config_value = '".$new_perms."' WHERE config_item='can_manage_events'");
      $wpdb->get_results("UPDATE " . WP_CALENDAR_CONFIG_TABLE_Aula . " SET config_value = '".$calendar_style."' WHERE config_item='calendar_style'");
      $wpdb->get_results("UPDATE " . WP_CALENDAR_CONFIG_TABLE_Aula . " SET config_value = '".$disp_author."' WHERE config_item='display_author'");
      $wpdb->get_results("UPDATE " . WP_CALENDAR_CONFIG_TABLE_Aula . " SET config_value = '".$disp_jump."' WHERE config_item='display_jump'");
      $wpdb->get_results("UPDATE " . WP_CALENDAR_CONFIG_TABLE_Aula . " SET config_value = '".$disp_todays."' WHERE config_item='display_todays'");
      $wpdb->get_results("UPDATE " . WP_CALENDAR_CONFIG_TABLE_Aula . " SET config_value = '".$disp_upcoming."' WHERE config_item='display_upcoming'");
      $wpdb->get_results("UPDATE " . WP_CALENDAR_CONFIG_TABLE_Aula . " SET config_value = '".$display_upcoming_days."' WHERE config_item='display_upcoming_days'");
      $wpdb->get_results("UPDATE " . WP_CALENDAR_CONFIG_TABLE_Aula . " SET config_value = '".$enable_categories."' WHERE config_item='enable_categories'");

      // Check to see if we are replacing the original style
      if (mysql_escape_string($_POST['reset_styles']) == 'on')
        {
          $wpdb->get_results("UPDATE " . WP_CALENDAR_CONFIG_TABLE_Aula . " SET config_value = '".$initial_style."' WHERE config_item='calendar_style'");
        }

      echo "<div class=\"updated\"><p><strong>".__('Settings saved','calendarAula').".</strong></p></div>";
    }

  // Pull the values out of the database that we need for the form
  $configs = $wpdb->get_results("SELECT config_value FROM " . WP_CALENDAR_CONFIG_TABLE_Aula . " WHERE config_item='can_manage_events'");
  if (!empty($configs))
    {
      foreach ($configs as $config)
        {
          $allowed_group = $config->config_value;
        }
    }

  $configs = $wpdb->get_results("SELECT config_value FROM " . WP_CALENDAR_CONFIG_TABLE_Aula . " WHERE config_item='calendar_style'");
  if (!empty($configs))
    {
      foreach ($configs as $config)
        {
          $calendar_style = $config->config_value;
        }
    }
  $configs = $wpdb->get_results("SELECT config_value FROM " . WP_CALENDAR_CONFIG_TABLE_Aula . " WHERE config_item='display_author'");
  if (!empty($configs))
    {
      foreach ($configs as $config)
        {
	  if ($config->config_value == 'true')
	    {
	      $yes_disp_author = 'selected="selected"';
	    }
	  else
	    {
	      $no_disp_author = 'selected="selected"';
	    }
        }
    }
  $configs = $wpdb->get_results("SELECT config_value FROM " . WP_CALENDAR_CONFIG_TABLE_Aula . " WHERE config_item='display_jump'");
  if (!empty($configs))
    {
      foreach ($configs as $config)
        {
          if ($config->config_value == 'true')
            {
              $yes_disp_jump = 'selected="selected"';
            }
          else
            {
              $no_disp_jump = 'selected="selected"';
            }
        }
    }
  $configs = $wpdb->get_results("SELECT config_value FROM " . WP_CALENDAR_CONFIG_TABLE_Aula . " WHERE config_item='display_todays'");
  if (!empty($configs))
    {
      foreach ($configs as $config)
        {
          if ($config->config_value == 'true')
            {
              $yes_disp_todays = 'selected="selected"';
            }
          else
            {
              $no_disp_todays = 'selected="selected"';
            }
        }
    }
  $configs = $wpdb->get_results("SELECT config_value FROM " . WP_CALENDAR_CONFIG_TABLE_Aula . " WHERE config_item='display_upcoming'");
  if (!empty($configs))
    {
      foreach ($configs as $config)
        {
          if ($config->config_value == 'true')
            {
              $yes_disp_upcoming = 'selected="selected"';
            }
          else
            {
              $no_disp_upcoming = 'selected="selected"';
            }
        }
    }
  $configs = $wpdb->get_results("SELECT config_value FROM " . WP_CALENDAR_CONFIG_TABLE_Aula . " WHERE config_item='display_upcoming_days'");
  if (!empty($configs))
    {
      foreach ($configs as $config)
        {
          $upcoming_days = $config->config_value;
        }
    }
  $configs = $wpdb->get_results("SELECT config_value FROM " . WP_CALENDAR_CONFIG_TABLE_Aula . " WHERE config_item='enable_categories'");
  if (!empty($configs))
    {
      foreach ($configs as $config)
        {
          if ($config->config_value == 'true')
            {
              $yes_enable_categories = 'selected="selected"';
            }
          else
            {
              $no_enable_categories = 'selected="selected"';
            }
        }
    }
  if ($allowed_group == 'read') { $subscriber_selected='selected="selected"';}
  else if ($allowed_group == 'edit_posts') { $contributor_selected='selected="selected"';}
  else if ($allowed_group == 'publish_posts') { $author_selected='selected="selected"';}
  else if ($allowed_group == 'moderate_comments') { $editor_selected='selected="selected"';}
  else if ($allowed_group == 'manage_options') { $admin_selected='selected="selected"';}

  // Now we render the form
  ?>
  <style type="text/css">
  <!--
        .error {
	  background: lightcoral;
	  border: 1px solid #e64f69;
	  margin: 1em 5% 10px;
	  padding: 0 1em 0 1em;
	}

        .center { 
	  text-align: center; 
	}
        .right { 
	  text-align: right; 
	}
        .left { 
	  text-align: left; 
	}
        .top { 
	  vertical-align: top; 
	}
        .bold { 
	  font-weight: bold; 
	}
        .private { 
	  color: #e64f69; 
	}
  //-->                                                                                                                                                        
  </style>

  <div class="wrap">
  <h2><?php _e('Opciones calendario','calendarAula'); ?></h2>
  <form name="quoteform" id="quoteform" class="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=calendar-configAula">
                <div id="linkadvanceddiv" class="postbox">
                        <div style="float: left; width: 98%; clear: both;" class="inside">
                                <table cellpadding="5" cellspacing="5">
				<tr>
                                <td><legend><?php _e('Choose the lowest user group that may manage events','calendarAula'); ?></legend></td>
				<td>        <select name="permissions">
				            <option value="subscriber"<?php echo $subscriber_selected ?>><?php _e('Subscriber','calendarAula')?></option>
				            <option value="contributor" <?php echo $contributor_selected ?>><?php _e('Contributor','calendarAula')?></option>
				            <option value="author" <?php echo $author_selected ?>><?php _e('Author','calendarAula')?></option>
				            <option value="editor" <?php echo $editor_selected ?>><?php _e('Editor','calendarAula')?></option>
				            <option value="admin" <?php echo $admin_selected ?>><?php _e('Administrator','calendarAula')?></option>
				        </select>
                                </td>
                                </tr>
                                <tr>
				<td><legend><?php _e('Do you want to display the author name on events?','calendarAula'); ?></legend></td>
                                <td>    <select name="display_author">
                                        <option value="on" <?php echo $yes_disp_author ?>><?php _e('Yes','calendarAula') ?></option>
                                        <option value="off" <?php echo $no_disp_author ?>><?php _e('No','calendarAula') ?></option>
                                    </select>
                                </td>
                                </tr>
                                <tr>
				<td><legend><?php _e('Display a jumpbox for changing month and year quickly?','calendarAula'); ?></legend></td>
                                <td>    <select name="display_jump">
                                         <option value="on" <?php echo $yes_disp_jump ?>><?php _e('Yes','calendarAula') ?></option>
                                         <option value="off" <?php echo $no_disp_jump ?>><?php _e('No','calendarAula') ?></option>
                                    </select>
                                </td>
                                </tr>
                                <tr>
				<td><legend><?php _e('Display todays events?','calendarAula'); ?></legend></td>
                                <td>    <select name="display_todays">
						<option value="on" <?php echo $yes_disp_todays ?>><?php _e('Yes','calendarAula') ?></option>
						<option value="off" <?php echo $no_disp_todays ?>><?php _e('No','calendarAula') ?></option>
                                    </select>
                                </td>
                                </tr>
                                <tr>
				<td><legend><?php _e('Display upcoming events?','calendarAula'); ?></legend></td>
                                <td>    <select name="display_upcoming">
						<option value="on" <?php echo $yes_disp_upcoming ?>><?php _e('Yes','calendarAula') ?></option>
						<option value="off" <?php echo $no_disp_upcoming ?>><?php _e('No','calendarAula') ?></option>
                                    </select>
				    <?php _e('for','calendarAula'); ?> <input type="text" name="display_upcoming_days" value="<?php echo $upcoming_days ?>" size="1" maxlength="2" /> <?php _e('days into the future','calendarAula'); ?>
                                </td>
                                </tr>
                                <tr>
				<td><legend><?php _e('Enable event categories?','calendarAula'); ?></legend></td>
                                <td>    <select name="enable_categories">
				                <option value="on" <?php echo $yes_enable_categories ?>><?php _e('Yes','calendarAula') ?></option>
						<option value="off" <?php echo $no_enable_categories ?>><?php _e('No','calendarAula') ?></option>
                                    </select>
                                </td>
                                </tr>
                                <tr>
				<td style="vertical-align:top;"><legend><?php _e('Configure the stylesheet for Calendar','calendarAula'); ?></legend></td>
				<td><textarea name="style" rows="10" cols="60" tabindex="2"><?php echo $calendar_style; ?></textarea><br />
                                <input type="checkbox" name="reset_styles" /> <?php _e('Tick this box if you wish to reset the Calendar style to default','calendarAula'); ?></td>
                                </tr>
                                </table>
			</div>
                        <div style="clear:both; height:1px;">&nbsp;</div>
	        </div>
                <input type="submit" name="save" class="button bold" value="<?php _e('Save','calendarAula'); ?> &raquo;" />
  </form>
  </div>
  <?php


}

// Function to handle the management of categories
function manage_categoriesAula()
{
  global $wpdb;

  // Calendar must be installed and upgraded before this will work
  check_calendarAula();

?>
<style type="text/css">
  <!--
   .error {
     background: lightcoral;
     border: 1px solid #e64f69;
     margin: 1em 5% 10px;
     padding: 0 1em 0 1em;
   }

  .center {
    text-align: center;
  }
  .right {
    text-align: right;
  }
  .left {
    text-align: left;
  }
  .top {
    vertical-align: top;
  }
  .bold {
    font-weight: bold;
  }
  .private {
  color: #e64f69;
  }
  //-->                                                                                                                                                                               
</style>
<?php
  // We do some checking to see what we're doing
  if (isset($_POST['mode']) && $_POST['mode'] == 'add')
    {
      $sql = "INSERT INTO " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " SET category_name='".mysql_escape_string($_POST['category_name'])."', category_colour='".mysql_escape_string($_POST['category_colour'])."'";
      $wpdb->get_results($sql);
      echo "<div class=\"updated\"><p><strong>".__('Category added successfully','calendarAula')."</strong></p></div>";
    }
  else if (isset($_GET['mode']) && isset($_GET['category_id']) && $_GET['mode'] == 'delete')
    {
      $sql = "DELETE FROM " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " WHERE category_id=".mysql_escape_string($_GET['category_id']);
      $wpdb->get_results($sql);
      $sql = "UPDATE " . WP_CALENDAR_TABLE_Aula . " SET event_category=1 WHERE event_category=".mysql_escape_string($_GET['category_id']);
      $wpdb->get_results($sql);
      echo "<div class=\"updated\"><p><strong>".__('Category deleted successfully','calendarAula')."</strong></p></div>";
    }
  else if (isset($_GET['mode']) && isset($_GET['category_id']) && $_GET['mode'] == 'edit' && !isset($_POST['mode']))
    {
      $sql = "SELECT * FROM " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " WHERE category_id=".mysql_escape_string($_GET['category_id']);
      $cur_cat = $wpdb->get_row($sql);
      ?>
<div class="wrap">
   <h2><?php _e('Edit Category','calendarAula'); ?></h2>
    <form name="catform" id="catform" class="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=calendar-categoriesAula">
                <input type="hidden" name="mode" value="edit" />
                <input type="hidden" name="category_id" value="<?php echo $cur_cat->category_id ?>" />
                <div id="linkadvanceddiv" class="postbox">
                        <div style="float: left; width: 98%; clear: both;" class="inside">
				<table cellpadding="5" cellspacing="5">
                                <tr>
				<td><legend><?php _e('Category Name','calendarAula'); ?>:</legend></td>
                                <td><input type="text" name="category_name" class="input" size="30" maxlength="30" value="<?php echo $cur_cat->category_name ?>" /></td>
				</tr>
                                <tr>
				<td><legend><?php _e('Category Colour (Hex format)','calendarAula'); ?>:</legend></td>
                                <td><input type="text" name="category_colour" class="input" size="10" maxlength="7" value="<?php echo $cur_cat->category_colour ?>" /></td>
                                </tr>
                                </table>
                        </div>
                        <div style="clear:both; height:1px;">&nbsp;</div>
                </div>
                <input type="submit" name="save" class="button bold" value="<?php _e('Save','calendarAula'); ?> &raquo;" />
    </form>
</div>
      <?php
    }
  else if (isset($_POST['mode']) && isset($_POST['category_id']) && isset($_POST['category_name']) && isset($_POST['category_colour']) && $_POST['mode'] == 'edit')
    {
      $sql = "UPDATE " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " SET category_name='".mysql_escape_string($_POST['category_name'])."', category_colour='".mysql_escape_string($_POST['category_colour'])."' WHERE category_id=".mysql_escape_string($_POST['category_id']);
      $wpdb->get_results($sql);
      echo "<div class=\"updated\"><p><strong>".__('Category edited successfully','calendarAula')."</strong></p></div>";
    }

  if ($_GET['mode'] != 'edit' || $_POST['mode'] == 'edit')
    {
?>

  <div class="wrap">
    <h2><?php _e('Add Category','calendarAula'); ?></h2>
    <form name="catform" id="catform" class="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=calendar-categoriesAula">
                <input type="hidden" name="mode" value="add" />
                <input type="hidden" name="category_id" value="">
                <div id="linkadvanceddiv" class="postbox">
                        <div style="float: left; width: 98%; clear: both;" class="inside">
       				<table cellspacing="5" cellpadding="5">
                                <tr>
                                <td><legend><?php _e('Category Name','calendarAula'); ?>:</legend></td>
                                <td><input type="text" name="category_name" class="input" size="30" maxlength="30" value="" /></td>
                                </tr>
                                <tr>
                                <td><legend><?php _e('Category Colour (Hex format)','calendarAula'); ?>:</legend></td>
                                <td><input type="text" name="category_colour" class="input" size="10" maxlength="7" value="" /></td>
                                </tr>
                                </table>
                        </div>
		        <div style="clear:both; height:1px;">&nbsp;</div>
                </div>
                <input type="submit" name="save" class="button bold" value="<?php _e('Save','calendarAula'); ?> &raquo;" />
    </form>
    <h2><?php _e('Manage Categories','calendarAula'); ?></h2>
<?php
    
    // We pull the categories from the database	
    $categories = $wpdb->get_results("SELECT * FROM " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " ORDER BY category_id ASC");

 if ( !empty($categories) )
   {
     ?>
     <table class="widefat page fixed" width="50%" cellpadding="3" cellspacing="3">
       <thead> 
       <tr>
         <th class="manage-column" scope="col"><?php _e('ID','calendarAula') ?></th>
	 <th class="manage-column" scope="col"><?php _e('Category Name','calendarAula') ?></th>
	 <th class="manage-column" scope="col"><?php _e('Category Colour','calendarAula') ?></th>
	 <th class="manage-column" scope="col"><?php _e('Edit','calendarAula') ?></th>
	 <th class="manage-column" scope="col"><?php _e('Delete','calendarAula') ?></th>
       </tr>
       </thead>
       <?php
       $class = '';
       foreach ( $categories as $category )
         {
	   $class = ($class == 'alternate') ? '' : 'alternate';
           ?>
           <tr class="<?php echo $class; ?>">
	     <th scope="row"><?php echo $category->category_id; ?></th>
	     <td><?php echo $category->category_name; ?></td>
	     <td style="background-color:<?php echo $category->category_colour; ?>;">&nbsp;</td>
	     <td><a href="<?php echo $_SERVER['PHP_SELF']  ?>?page=calendar-categoriesAula&amp;mode=edit&amp;category_id=<?php echo $category->category_id;?>" class='edit'><?php echo __('Edit','calendarAula'); ?></a></td>
	     <?php
	     if ($category->category_id == 1)
	       {
		 echo '<td>'.__('N/A','calendarAula').'</td>';
	       }
             else
	       {
               ?>
               <td><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page='calendarAula'&amp;mode=delete&amp;category_id=<?php echo $category->category_id;?>" class="delete" onclick="return confirm('<?php echo __('Are you sure you want to delete this category?','calendarAula'); ?>')"><?php echo __('Delete','calendarAula'); ?></a></td>
               <?php
	       }
                ?>
              </tr>
                <?php
          }
      ?>
      </table>
      <?php
   }
 else
   {
     echo '<p>'.__('There are no categories in the database - something has gone wrong!','calendarAula').'</p>';
   }

?>
  </div>

<?php
      } 
}

// Function to return a prefix which will allow the correct 
// placement of arguments into the query string.
function permalink_prefixAula()
{
  // Get the permalink structure from WordPress
  $p_link = get_permalink();

  // Work out what the real URL we are viewing is
  $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; 
  $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")).$s;
  $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
  $real_link = $protocol.'://'.$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];

  // Now use all of that to get the correctly craft the Calendar link prefix
  if (strstr($p_link, '?') && $p_link == $real_link)
    {
      $link_part = $p_link.'&';
    }
  else if ($p_link == $real_link)
    {
      $link_part = $p_link.'?';
    }
  else if (strstr($real_link, '?'))  
    {
      if (isset($_GET['month']) && isset($_GET['yr']))
	{
	  $new_tail = split("&", $real_link);
	  foreach ($new_tail as $item)
	    {
	      if (!strstr($item, 'month') && !strstr($item, 'yr'))
		{
		  $link_part .= $item.'&';
		}
	    }
	  if (!strstr($link_part, '?'))
	    {
	      $new_tail = split("month", $link_part);
	      $link_part = $new_tail[0].'?'.$new_tail[1];
	    }
	}
      else
	{
	  $link_part = $real_link.'&';
	}
    }
  else
    {
      $link_part = $real_link.'?';
    }

  return $link_part;
}

// Configure the "Next" link in the calendar
function next_linkAula($cur_year,$cur_month)
{
  $mod_rewrite_months = array(1=>'jan','feb','mar','apr','may','jun','jul','aug','sept','oct','nov','dec');
  $next_year = $cur_year + 1;

  if ($cur_month == 12)
    {
      return '<a href="' . permalink_prefixAula() . 'month=jan&yr=' . $next_year . '">'.__('Next','calendarAula').' &raquo;</a>';
    }
  else
    {
      $next_month = $cur_month + 1;
      $month = $mod_rewrite_months[$next_month];
      return '<a href="' . permalink_prefixAula() . 'month='.$month.'&yr=' . $cur_year . '">'.__('Next','calendarAula').' &raquo;</a>';
    }
}

// Configure the "Previous" link in the calendar
function prev_linkAula($cur_year,$cur_month)
{
  $mod_rewrite_months = array(1=>'jan','feb','mar','apr','may','jun','jul','aug','sept','oct','nov','dec');
  $last_year = $cur_year - 1;

  if ($cur_month == 1)
    {
      return '<a href="' . permalink_prefixAula() . 'month=dec&yr='. $last_year .'">&laquo; '.__('Prev','calendarAula').'</a>';
    }
  else
    {
      $next_month = $cur_month - 1;
      $month = $mod_rewrite_months[$next_month];
      return '<a href="' . permalink_prefixAula() . 'month='.$month.'&yr=' . $cur_year . '">&laquo; '.__('Prev','calendarAula').'</a>';
    }
}

// Print upcoming events
function upcoming_eventsAula()
{
  global $wpdb;

  // This function cannot be called unless calendar is up to date
  check_calendarAula();
 
  // Find out if we should be displaying upcoming events
  $display = $wpdb->get_var("SELECT config_value FROM ".WP_CALENDAR_CONFIG_TABLE_Aula." WHERE config_item='display_upcoming'",0,0);

  if ($display == 'true')
    {
      // Get number of days we should go into the future 
      $future_days = $wpdb->get_var("SELECT config_value FROM ".WP_CALENDAR_CONFIG_TABLE_Aula." WHERE config_item='display_upcoming_days'",0,0);
      $day_count = 1;

      while ($day_count < $future_days+1)
	{
	  list($y,$m,$d) = split("-",date("Y-m-d",mktime($day_count*24,0,0,date("m"),date("d"),date("Y"))));
	  $events = grab_eventsAula($y,$m,$d);
	  usort($events, "time_cmpAula");
	  if (count($events) != 0) {
	    $output .= '<li>'.date(get_option('date_format'),mktime($day_count*24,0,0,date("m"),date("d"),date("Y"))).'<ul>';
	  }
	  foreach($events as $event)
	    {
	      		//Hora de comienzo del evento
	      		if ($event->event_time_begin == '00:00:00') {
					$time_begin_string = ' '.__('all day','calendarAula');
	      		}
	      		else {
					$time_begin_string = ' '.__('at','calendarAula').' '.date(get_option('time_format'), strtotime($event->event_time_begin));
	      		}
              	$output .= '<li>'.draw_widget_eventAula($event).$time_begin_string.'</li>';
          		//Hora fin del evento
         		if ($event->event_time_end == '00:00:00') {
					$time_end_string = ' '.__('all day','calendarAula');
	      		}
	      		else {
					$time_end_string = ' '.__('at','calendarAula').' '.date(get_option('time_format'), strtotime($event->event_time_end));
	      		}
              	$output .= '<li>'.draw_widget_eventAula($event).$time_end_string.'</li>';
	    }
	  if (count($events) != 0) {
	    $output .= '</ul></li>';
	  }
	  $day_count = $day_count+1;
	}

      if ($output != '')
	{
	  $visual = '<li class="upcoming-events"><h2>'.__('Upcoming Events','calendarAula').'</h2><ul>';
	  $visual .= $output;
	  $visual .= '</ul></li>';
	  return $visual;
	}
    }
}

// Print todays events
function todays_eventsAula()
{
  global $wpdb;

  // This function cannot be called unless calendar is up to date
  check_calendarAula();

  // Find out if we should be displaying todays events
  $display = $wpdb->get_var("SELECT config_value FROM ".WP_CALENDAR_CONFIG_TABLE_Aula." WHERE config_item='display_todays'",0,0);

  if ($display == 'true')
    {
      $output = '<li class="todays-events"><h2>'.__('Today\'s Events','calendarAula').'</h2><ul>';
      $events = grab_eventsAula(date("Y"),date("m"),date("d"));
      usort($events, "time_cmpAula");
      foreach($events as $event)
	{
	  //Time_begin
	  if ($event->event_time_begin == '00:00:00') {
	    $time_begin_string = ' '.__('all day','calendarAula');
	  }
	  else {
	    $time_begin_string = ' '.__('at','calendarAula').' '.date(get_option('time_format'), strtotime($event->event_time_begin));
	  }
	  //Time end
	  if ($event->event_time_end == '00:00:00') {
	    $time_end_string = ' '.__('all day','calendarAula');
	  }
	  else {
	    $time_end_string = ' '.__('at','calendarAula').' '.date(get_option('time_format'), strtotime($event->event_time_end));
	  }
	  $output .= '<li>'.draw_widget_eventAula($event).$time_begin_string.'</li>';
	  $output .= '<li>'.draw_widget_eventAula($event).$time_end_string.'</li>';
	}
      $output .= '</ul></li>';
      if (count($events) != 0)
	{
	  return $output;
	}
    }
}

// Function to compare time in event objects
function time_cmpAula($a, $b)
{
  if ($a->event_time_begin == $b->event_time_begin) {
    return 0;
  }
  return ($a->event_time_begin < $b->event_time_begin) ? -1 : 1;
}

// Used to draw multiple events
function draw_eventsAula($events)
{
  // We need to sort arrays of objects by time
  usort($events, "time_cmpAula");

  // Now process the events
  foreach($events as $event)
    {
      $output .= draw_eventAula($event);
    }
  return $output;
}

// Widget todays events
function todays_events_widgetAula() {
  global $wpdb;

  // This function cannot be called unless calendar is up to date
  check_calendarAula();

  // Find out if we should be displaying todays events
  $display = $wpdb->get_var("SELECT config_value FROM ".WP_CALENDAR_CONFIG_TABLE_Aula." WHERE config_item='display_todays'",0,0);

  if ($display == 'true')
    {
      $output = '<ul>';
      $events = grab_eventsAula(date("Y"),date("m"),date("d"));
      usort($events, "time_cmpAula");
      foreach($events as $event)
        {
          //Time_begin
          if ($event->event_time_begin == '00:00:00') {
            $time_begin_string = ' '.__('all day','calendarAula');
          }
          else {
            $time_begin_string = ' '.__('at','calendarAula').' '.date(get_option('time_format'), strtotime($event->event_time_begin));
          }
          $output .= '<li>'.draw_widget_eventAula($event).$time_begin_string.'</li>';
          //Time_end
          if ($event->event_time_end == '00:00:00') {
            $time_end_string = ' '.__('all day','calendarAula');
          }
          else {
            $time_end_string = ' '.__('at','calendarAula').' '.date(get_option('time_format'), strtotime($event->event_time_end));
          }
          $output .= '<li>'.draw_widget_eventAula($event).$time_end_string.'</li>';
        }
      $output .= '</ul>';
      if (count($events) != 0)
        {
          return $output;
        }
    }
}

// Widget upcoming events
function upcoming_events_widgetAula() {
  global $wpdb;

  // This function cannot be called unless calendar is up to date
  check_calendarAula();

  // Find out if we should be displaying upcoming events
  $display = $wpdb->get_var("SELECT config_value FROM ".WP_CALENDAR_CONFIG_TABLE_Aula." WHERE config_item='display_upcoming'",0,0);

  if ($display == 'true')
    {
      // Get number of days we should go into the future
      $future_days = $wpdb->get_var("SELECT config_value FROM ".WP_CALENDAR_CONFIG_TABLE_Aula." WHERE config_item='display_upcoming_days'",0,0);
      $day_count = 1;

      while ($day_count < $future_days+1)
        {
          list($y,$m,$d) = split("-",date("Y-m-d",mktime($day_count*24,0,0,date("m"),date("d"),date("Y"))));
          $events = grab_eventsAula($y,$m,$d);
          usort($events, "time_cmpAula");
          if (count($events) != 0) {
            $output .= '<li>'.date(get_option('date_format'),mktime($day_count*24,0,0,date("m"),date("d"),date("Y"))).'<ul>';
          }
          foreach($events as $event)
            {
              //Time_begin
              if ($event->event_time_begin == '00:00:00') {
                $time_begin_string = ' '.__('all day','calendarAula');
              }
              else {
                $time_begin_string = ' '.__('at','calendarAula').' '.date(get_option('time_format'), strtotime($event->event_time_begin));
              }
              $output .= '<li>'.draw_widget_eventAula($event).$time_begin_string.'</li>';
              
              //Time_end
              if ($event->event_time_end == '00:00:00') {
                $time_end_string = ' '.__('all day','calendarAula');
              }
              else {
                $time_end_string = ' '.__('at','calendarAula').' '.date(get_option('time_format'), strtotime($event->event_time_end));
              }
              $output .= '<li>'.draw_widget_eventAula($event).$time_end_string.'</li>';
            }
          if (count($events) != 0) {
            $output .= '</ul></li>';
          }
          $day_count = $day_count+1;
        }

      if ($output != '')
        {
          $visual = '<ul>';
          $visual .= $output;
          $visual .= '</ul>';
          return $visual;
        }
    }
}

// The widget to show todays events in the sidebar
function widget_init_calendar_todayAula() {
  // Check for required functions
  if (!function_exists('register_sidebar_widget'))
    return;

  function widget_calendar_todayAula($args) {
    extract($args);
    $the_title = get_option('calendar_today_widget_title');
    $widget_title = empty($the_title) ? __('Today\'s Events','calendarAula') : $the_title;
    $the_events = todays_events_widgetAula();
    if ($the_events != '') {
      echo $before_widget;
      echo $before_title . $widget_title . $after_title;
      echo $the_events;
      echo $after_widget;
    }
  }

  function widget_calendar_today_controlAula() {
    $widget_title = get_option('calendar_today_widget_title');
    if (isset($_POST['calendar_today_widget_title'])) {
      update_option('calendar_today_widget_title',strip_tags($_POST['calendar_today_widget_title']));
    }
    ?>
    <p>
       <label for="calendar_today_widget_title"><?php _e('Title','calendarAula'); ?>:<br />
       <input class="widefat" type="text" id="calendar_today_widget_title" name="calendar_today_widget_title" value="<?php echo $widget_title; ?>"/></label>
    </p>
    <?php
  }

  register_sidebar_widget(__('Today\'s Events','calendarAula'),'widget_calendar_todayAula');
  register_widget_control(__('Today\'s Events','calendarAula'),'widget_calendar_today_controlAula');
  }

// The widget to show todays events in the sidebar                                              
function widget_init_calendar_upcomingAula() {
  // Check for required functions                                                               
  if (!function_exists('register_sidebar_widget'))
    return;

  function widget_calendar_upcomingAula($args) {
    extract($args);
    $the_title = get_option('calendar_upcoming_widget_title');
    $widget_title = empty($the_title) ? __('Upcoming Events','calendarAula') : $the_title;
    $the_events = upcoming_events_widgetAula();
    if ($the_events != '') {
      echo $before_widget;
      echo $before_title . $widget_title . $after_title;
      echo $the_events;
      echo $after_widget;
    }
  }

  function widget_calendar_upcoming_controlAula() {
    $widget_title = get_option('calendar_upcoming_widget_title');
    if (isset($_POST['calendar_upcoming_widget_title'])) {
      update_option('calendar_upcoming_widget_title',strip_tags($_POST['calendar_upcoming_widget_title']));
    }
    ?>
    <p>
       <label for="calendar_upcoming_widget_title"><?php _e('Title','calendarAula'); ?>:<br />
       <input class="widefat" type="text" id="calendar_upcoming_widget_title" name="calendar_upcoming_widget_title" value="<?php echo $widget_title; ?>"/></label>
    </p>
    <?php
  }

  register_sidebar_widget(__('Upcoming Events','calendarAula'),'widget_calendar_upcomingAula');
  register_widget_control(__('Upcoming Events','calendarAula'),'widget_calendar_upcoming_controlAula');
}


// Used to draw an event to the screen
function draw_eventAula($event)
{
  global $wpdb;

  // Calendar must be updated to run this function
  check_calendarAula();

  // Before we do anything we want to know if we                                             
  // should display the author and/or show categories. 
  // We check for this later                                      
  $display_author = $wpdb->get_var("SELECT config_value FROM ".WP_CALENDAR_CONFIG_TABLE_Aula." WHERE config_item='display_author'",0,0);
  $show_cat = $wpdb->get_var("SELECT config_value FROM ".WP_CALENDAR_CONFIG_TABLE_Aula." WHERE config_item='enable_categories'",0,0);

  if ($show_cat == 'true')
    {
      $sql = "SELECT * FROM " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " WHERE category_id=".$event->event_category;
      $cat_details = $wpdb->get_row($sql);
      $style = "background-color:".$cat_details->category_colour.";";
    }

  $header_details .=  '<div class="event-title">'.$event->event_title.'</div><div class="event-title-break"></div>';
  if ($event->event_time_begin != "00:00:00")
    {
      $header_details .= '<strong>'.__('Hora Inicio','calendarAula').':</strong> ' . date("H:i", strtotime($event->event_time_begin)) . '<br />';
    }
  if ($event->event_time_end != "00:00:00")
    {
      $header_details .= '<strong>'.__('Hora Fin','calendarAula').':</strong> ' . date("H:i", strtotime($event->event_time_end)) . '<br />';
    }
  if ($display_author == 'true')
    {
      $e = get_userdata($event->event_author);
      $header_details .= '<strong>'.__('Posted by', 'calendarAula').':</strong> '.$e->display_name.'<br />';
    }
  if ($display_author == 'true' || $event->event_time_begin != "00:00:00" || $event->event_time_end != "00:00:00")
    {
      $header_details .= '<div class="event-content-break"></div>';
    }
  if ($event->event_link != '') { $linky = $event->event_link; }
  else { $linky = '#'; }

  
  $details = '<br />
* <span class="calnk" nowrap="nowrap" style="font-size:1.2em;"><a href="'.$linky.'" style="'.$style.'">' . date("H:i", strtotime($event->event_time_begin)) .' '. $event->event_title . '<span style="'.$style.'margin-left:-50px;">' . $header_details . '' . $event->event_desc . '</span></a></span>';

  return $details;
}

// Draw an event but customise the HTML for use in the widget
function draw_widget_eventAula($event)
{
  global $wpdb;

  // Calendar must be updated to run this function
  check_calendarAula();

  // Before we do anything we want to know if we
  // should display the author and/or show categories.
  // We check for this later
  $display_author = $wpdb->get_var("SELECT config_value FROM ".WP_CALENDAR_CONFIG_TABLE_Aula." WHERE config_item='display_author'",0,0);
  $show_cat = $wpdb->get_var("SELECT config_value FROM ".WP_CALENDAR_CONFIG_TABLE_Aula." WHERE config_item='enable_categories'",0,0);

  if ($show_cat == 'true')
    {
      $sql = "SELECT * FROM " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " WHERE category_id=".$event->event_category;
      $cat_details = $wpdb->get_row($sql);
      $style = "background-color:".$cat_details->category_colour.";";
    }

  $header_details .=  '<div class="event-title">'.$event->event_title.'</div><div class="event-title-break"></div>';
  if ($event->event_time_begin != "00:00:00")
    {
      $header_details .= '<strong>'.__('Hora inicio','calendarAula').':</strong> ' . date(get_option('time_format'), strtotime($event->event_time_begin)) . '<br />';
    }
  if ($event->event_time_end != "00:00:00")
    {
      $header_details .= '<strong>'.__('Hora fin','calendarAula').':</strong> ' . date(get_option('time_format'), strtotime($event->event_time_end)) . '<br />';
    }
  if ($display_author == 'true')
    {
      $e = get_userdata($event->event_author);
      $header_details .= '<strong>'.__('Posted by','calendarAula').':</strong> '.$e->display_name.'<br />';
    }
  if ($display_author == 'true' || $event->event_time_begin != "00:00:00" || $event->event_time_end != "00:00:00")
    {
      $header_details .= '<div class="event-content-break"></div>';
    }
  if ($event->event_link != '') { $linky = $event->event_link; }
  else { $linky = '#'; }

  
  $day_of_the_week = date('l dS \of F Y h:i:s A',mktime(0,0,0,$m,$d,$y));
  //echo $day_of_the_week;
  
  
  $details = '<span class="calnk" nowrap="nowrap"><a href="'.$linky.'">' . $event->event_title . '<span style="'.$style.'margin-left:-50px;">' . $header_details . '' . $event->event_desc . '</span></a></span>';

  return $details;
}

// Grab all events for the requested date from calendar
function grab_eventsAula($y,$m,$d)
{
     global $wpdb;

     $arr_events = array();

     // Get the date format right
     $date = $y . '-' . $m . '-' . $d;
     
     // Firstly we check for conventional events. These will form the first instance of a recurring event
     // or the only instance of a one-off event
     $events = $wpdb->get_results("SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_begin <= '$date' AND event_end >= '$date' AND event_recur = 'S' AND (event_valid = 'y' OR event_valid = 'v') ORDER BY event_id");
     if (!empty($events))
     {
         foreach($events as $event)
         {
	   array_push($arr_events, $event);
         }
     }

	// Even if there were results for that query, we may still have events recurring 
	// from the past on this day. We now methodically check the for these events

	/* 
	 The yearly code - easy because the day and month will be the same, so we return all yearly
	 events that match the date part. Out of these we show those with a repeat of 0, and fast-foward
	 a number of years for those with a value more than 0. Those that land in the future are displayed.
	*/

	
	// Deal with forever recurring year events
	$events = $wpdb->get_results("SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_recur = 'Y' AND EXTRACT(YEAR FROM '$date') >= EXTRACT(YEAR FROM event_begin) AND event_repeats = 0 AND event_valid = 'y' ORDER BY event_id");

	if (!empty($events))
     	{
       	  foreach($events as $event)
          {
	    // This is going to get complex so lets setup what we would place in for 
	    // an event so we can drop it in with ease

	    // Technically we don't care about the years, but we need to find out if the 
	    // event spans the turn of a year so we can deal with it appropriately.
	    $year_begin = date('Y',strtotime($event->event_begin));
	    $year_end = date('Y',strtotime($event->event_end));

	    if ($year_begin == $year_end)
	    {
		if (date('m-d',strtotime($event->event_begin)) <= date('m-d',strtotime($date)) && 
			date('m-d',strtotime($event->event_end)) >= date('m-d',strtotime($date)))
		{
	      		array_push($arr_events, $event);
		}
	    }
	    else if ($year_begin < $year_end)
	    {
		if (date('m-d',strtotime($event->event_begin)) <= date('m-d',strtotime($date)) || 
			date('m-d',strtotime($event->event_end)) >= date('m-d',strtotime($date)))
		{
	      		array_push($arr_events, $event);
		}
	    }
          }
     	}
	
	// Now the ones that happen a finite number of times
	$events = $wpdb->get_results("SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_recur = 'Y' AND EXTRACT(YEAR FROM '$date') >= EXTRACT(YEAR FROM event_begin) AND event_repeats != 0 AND (EXTRACT(YEAR FROM '$date')-EXTRACT(YEAR FROM event_begin)) <= event_repeats AND event_valid = 'y' ORDER BY event_id");
	if (!empty($events))
     	{
       	  foreach($events as $event)
          {
	    // This is going to get complex so lets setup what we would place in for 
	    // an event so we can drop it in with ease

	    // Technically we don't care about the years, but we need to find out if the 
	    // event spans the turn of a year so we can deal with it appropriately.
	    $year_begin = date('Y',strtotime($event->event_begin));
	    $year_end = date('Y',strtotime($event->event_end));

	    if ($year_begin == $year_end)
	    {
		if (date('m-d',strtotime($event->event_begin)) <= date('m-d',strtotime($date)) && 
			date('m-d',strtotime($event->event_end)) >= date('m-d',strtotime($date)))
		{
	      		array_push($arr_events, $event);
		}
	    }
	    else if ($year_begin < $year_end)
	    {
		if (date('m-d',strtotime($event->event_begin)) <= date('m-d',strtotime($date)) || 
			date('m-d',strtotime($event->event_end)) >= date('m-d',strtotime($date)))
		{
	      		array_push($arr_events, $event);
		}
	    }
          }
     	}	

	/* 
	  The monthly code - just as easy because as long as the day of the month is correct, then we 
	  show the event
	*/

	// The monthly events that never stop recurring
	$events = $wpdb->get_results("SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_recur = 'M' AND EXTRACT(YEAR FROM '$date') >= EXTRACT(YEAR FROM event_begin) AND event_repeats = 0 AND event_valid = 'y' ORDER BY event_id");
	if (!empty($events))
     	{
       	  foreach($events as $event)
          {
	    // This is going to get complex so lets setup what we would place in for 
	    // an event so we can drop it in with ease

	    // Technically we don't care about the years or months, but we need to find out if the 
	    // event spans the turn of a year or month so we can deal with it appropriately.
	    $month_begin = date('m',strtotime($event->event_begin));
	    $month_end = date('m',strtotime($event->event_end));

	    if ($month_begin == $month_end)
	    {
		if (date('d',strtotime($event->event_begin)) <= date('d',strtotime($date)) && 
			date('d',strtotime($event->event_end)) >= date('d',strtotime($date)))
		{
	      		array_push($arr_events, $event);
		}
	    }
	    else if ($month_begin < $month_end)
	    {
		if ( ($event->event_begin <= date('Y-m-d',strtotime($date))) && (date('d',strtotime($event->event_begin)) <= date('d',strtotime($date)) || 
			date('d',strtotime($event->event_end)) >= date('d',strtotime($date))) )
		{
	      		array_push($arr_events, $event);
		}
	    }
          }
     	}


	// Now the ones that happen a finite number of times
	$events = $wpdb->get_results("SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_recur = 'M' AND EXTRACT(YEAR FROM '$date') >= EXTRACT(YEAR FROM event_begin) AND event_repeats != 0 AND (PERIOD_DIFF(EXTRACT(YEAR_MONTH FROM '$date'),EXTRACT(YEAR_MONTH FROM event_begin))) <= event_repeats event_valid = 'y' ORDER BY event_id");
	if (!empty($events))
     	{
       	  foreach($events as $event)
          {
	    // This is going to get complex so lets setup what we would place in for 
	    // an event so we can drop it in with ease

	    // Technically we don't care about the years or months, but we need to find out if the 
	    // event spans the turn of a year or month so we can deal with it appropriately.
	    $month_begin = date('m',strtotime($event->event_begin));
	    $month_end = date('m',strtotime($event->event_end));

	    if ($month_begin == $month_end)
	    {
		if (date('d',strtotime($event->event_begin)) <= date('d',strtotime($date)) && 
			date('d',strtotime($event->event_end)) >= date('d',strtotime($date)))
		{
		        array_push($arr_events, $event);
		}
	    }
	    else if ($month_begin < $month_end)
	    {
		if ( ($event->event_begin <= date('Y-m-d',strtotime($date))) && (date('d',strtotime($event->event_begin)) <= date('d',strtotime($date)) || 
			date('d',strtotime($event->event_end)) >= date('d',strtotime($date))) )
		{
	      		array_push($arr_events, $event);
		}
	    }
          }
     	}


	/* 
	  Weekly - well isn't this fun! We need to scan all weekly events, find what day they fell on
	  and see if that matches the current day. If it does, we check to see if the repeats are 0. 
	  If they are, display the event, if not, we fast forward from the original day in week blocks 
	  until the number is exhausted. If the date we arrive at is in the future, display the event.
	*/

	// The weekly events that never stop recurring
	$events = $wpdb->get_results("SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_recur = 'W' AND '$date' >= event_begin AND event_repeats = 0 AND event_valid = 'y' ORDER BY event_id");
	if (!empty($events))
     	{
       	  foreach($events as $event)
          {
	    // This is going to get complex so lets setup what we would place in for 
	    // an event so we can drop it in with ease

	    // Now we are going to check to see what day the original event
	    // fell on and see if the current date is both after it and on 
	    // the correct day. If it is, display the event!
	    $day_start_event = date('D',strtotime($event->event_begin));
	    $day_end_event = date('D',strtotime($event->event_end));
	    $current_day = date('D',strtotime($date));

	    $plan = array();
	    $plan['Mon'] = 1;
	    $plan['Tue'] = 2;
	    $plan['Wed'] = 3;
	    $plan['Thu'] = 4;
	    $plan['Fri'] = 5;
	    $plan['Sat'] = 6;
	    $plan['Sun'] = 7;

	    if ($plan[$day_start_event] > $plan[$day_end_event])
	    {
		if (($plan[$day_start_event] <= $plan[$current_day]) || ($plan[$current_day] <= $plan[$day_end_event]))
	    	{
			array_push($arr_events, $event);
	    	}
	    }
	    else if (($plan[$day_start_event] < $plan[$day_end_event]) || ($plan[$day_start_event]== $plan[$day_end_event]))
	    {
		if (($plan[$day_start_event] <= $plan[$current_day]) && ($plan[$current_day] <= $plan[$day_end_event]))
	    	{
			array_push($arr_events, $event);
	    	}		
	    }
	    
          }
     	}

	// The weekly events that have a limit on how many times they occur
	$events = $wpdb->get_results("SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_recur = 'W' AND '$date' >= event_begin AND event_repeats != 0 AND (event_repeats*7) >= (TO_DAYS('$date') - TO_DAYS(event_end)) event_valid = 'y' ORDER BY event_id");
	if (!empty($events))
     	{
       	  foreach($events as $event)
          {
	    // This is going to get complex so lets setup what we would place in for 
	    // an event so we can drop it in with ease

	    // Now we are going to check to see what day the original event
	    // fell on and see if the current date is both after it and on 
	    // the correct day. If it is, display the event!
	    $day_start_event = date('D',strtotime($event->event_begin));
	    $day_end_event = date('D',strtotime($event->event_end));
	    $current_day = date('D',strtotime($date));

	    $plan = array();
	    $plan['Mon'] = 1;
	    $plan['Tue'] = 2;
	    $plan['Wed'] = 3;
	    $plan['Thu'] = 4;
	    $plan['Fri'] = 5;
	    $plan['Sat'] = 6;
	    $plan['Sun'] = 7;

	    if ($plan[$day_start_event] > $plan[$day_end_event])
	    {
		if (($plan[$day_start_event] <= $plan[$current_day]) || ($plan[$current_day] <= $plan[$day_end_event]))
	    	{
			array_push($arr_events, $event);
	    	}
	    }
	    else if (($plan[$day_start_event] < $plan[$day_end_event]) || ($plan[$day_start_event]== $plan[$day_end_event]))
	    {
		if (($plan[$day_start_event] <= $plan[$current_day]) && ($plan[$current_day] <= $plan[$day_end_event]))
	    	{
			array_push($arr_events, $event);
	    	}		
	    }

          }
     	}
 
     return $arr_events;
}


// Actually do the printing of the calendar
// Compared to searching for and displaying events
// this bit is really rather easy!
function calendarAula()
{
    global $wpdb;

    // First things first, make sure calendar is up to date
    check_calendarAula();

    // Deal with the week not starting on a monday
    if (get_option('start_of_week') == 0)
      {
	$name_days = array(1=>__('Sunday','calendarAula'),__('Monday','calendarAula'),__('Tuesday','calendarAula'),__('Wednesday','calendarAula'),__('Thursday','calendarAula'),__('Friday','calendarAula'),__('Saturday','calendarAula'));
      }
    // Choose Monday if anything other than Sunday is set
    else
      {
	$name_days = array(1=>__('Monday','calendarAula'),__('Tuesday','calendarAula'),__('Wednesday','calendarAula'),__('Thursday','calendarAula'),__('Friday','calendarAula'),__('Saturday','calendarAula'),__('Sunday','calendarAula'));
      }

    // Carry on with the script
    $name_months = array(1=>__('January','calendarAula'),__('February','calendarAula'),__('March','calendarAula'),__('April','calendarAula'),__('May','calendarAula'),__('June','calendarAula'),__('July','calendarAula'),__('August','calendarAula'),__('September','calendarAula'),__('October','calendarAula'),__('November','calendarAula'),__('December','calendarAula'));

    // If we don't pass arguments we want a calendar that is relevant to today
    if (empty($_GET['month']) || empty($_GET['yr']))
    {
        $c_year = date("Y");
        $c_month = date("m");
        $c_day = date("d");
    }

    // Years get funny if we exceed 3000, so we use this check
    if ($_GET['yr'] <= 3000 && $_GET['yr'] >= 0)
    {
        // This is just plain nasty and all because of permalinks
        // which are no longer used, this will be cleaned up soon
        if ($_GET['month'] == 'jan' || $_GET['month'] == 'feb' || $_GET['month'] == 'mar' || $_GET['month'] == 'apr' || $_GET['month'] == 'may' || $_GET['month'] == 'jun' || $_GET['month'] == 'jul' || $_GET['month'] == 'aug' || $_GET['month'] == 'sept' || $_GET['month'] == 'oct' || $_GET['month'] == 'nov' || $_GET['month'] == 'dec')
	  {

	       // Again nasty code to map permalinks into something
	       // databases can understand. This will be cleaned up
               $c_year = mysql_escape_string($_GET['yr']);
               if ($_GET['month'] == 'jan') { $t_month = 1; }
               else if ($_GET['month'] == 'feb') { $t_month = 2; }
               else if ($_GET['month'] == 'mar') { $t_month = 3; }
               else if ($_GET['month'] == 'apr') { $t_month = 4; }
               else if ($_GET['month'] == 'may') { $t_month = 5; }
               else if ($_GET['month'] == 'jun') { $t_month = 6; }
               else if ($_GET['month'] == 'jul') { $t_month = 7; }
               else if ($_GET['month'] == 'aug') { $t_month = 8; }
               else if ($_GET['month'] == 'sept') { $t_month = 9; }
               else if ($_GET['month'] == 'oct') { $t_month = 10; }
               else if ($_GET['month'] == 'nov') { $t_month = 11; }
               else if ($_GET['month'] == 'dec') { $t_month = 12; }
               $c_month = $t_month;
               $c_day = date("d");
        }
	// No valid month causes the calendar to default to today
        else
        {
               $c_year = date("Y");
               $c_month = date("m");
               $c_day = date("d");
        }
    }
    // No valid year causes the calendar to default to today
    else
    {
        $c_year = date("Y");
        $c_month = date("m");
        $c_day = date("d");
    }

    // Fix the days of the week if week start is not on a monday
    if (get_option('start_of_week') == 0)
      {
	$first_weekday = date("w",mktime(0,0,0,$c_month,1,$c_year));
        $first_weekday = ($first_weekday==0?1:$first_weekday+1);
      }
    // Otherwise assume the week starts on a Monday. Anything other 
    // than Sunday or Monday is just plain odd
    else
      {
	$first_weekday = date("w",mktime(0,0,0,$c_month,1,$c_year));
	$first_weekday = ($first_weekday==0?7:$first_weekday);
      }

    $days_in_month = date("t", mktime (0,0,0,$c_month,1,$c_year));

    // Start the table and add the header and naviagtion
    $calendar_body .= '
<table cellspacing="1" cellpadding="0" class="calendar-table">
';

    // We want to know if we should display the date switcher
    $date_switcher = $wpdb->get_var("SELECT config_value FROM ".WP_CALENDAR_CONFIG_TABLE_Aula." WHERE config_item='display_jump'",0,0);

    if ($date_switcher == 'true')
      {
	$calendar_body .= '<tr>
        <td colspan="7" class="calendar-date-switcher">
            <form method="GET" action="'.$_SERVER['REQUEST_URI'].'">
';
	$qsa = array();
	parse_str($_SERVER['QUERY_STRING'],$qsa);
	foreach ($qsa as $name => $argument)
	  {
	    if ($name != 'month' && $name != 'yr')
	      {
		$calendar_body .= '<input type="hidden" name="'.$name.'" value="'.$argument.'" />
';
	      }
	  }
	function month_comparisonAula($month)
	  {
	    $current_month = strtolower(date("M", time()));
	    if (isset($_GET['yr']) && isset($_GET['month']))
	      {
		if ($month == $_GET['month'])
		  {
		    return ' selected="selected"';
		  }
	      }
	    elseif ($month == $current_month) 
	      { 
		return ' selected="selected"'; 
	      }
	  }
	// We build the months in the switcher
	$calendar_body .= '
            '.__('Month','calendarAula').': <select name="month" style="width:100px;">
            <option value="jan"'.month_comparisonAula('jan').'>'.__('January','calendarAula').'</option>
            <option value="feb"'.month_comparisonAula('feb').'>'.__('February','calendarAula').'</option>
            <option value="mar"'.month_comparisonAula('mar').'>'.__('March','calendarAula').'</option>
            <option value="apr"'.month_comparisonAula('apr').'>'.__('April','calendarAula').'</option>
            <option value="may"'.month_comparisonAula('may').'>'.__('May','calendarAula').'</option>
            <option value="jun"'.month_comparisonAula('jun').'>'.__('June','calendarAula').'</option>
            <option value="jul"'.month_comparisonAula('jul').'>'.__('July','calendarAula').'</option> 
            <option value="aug"'.month_comparisonAula('aug').'>'.__('August','calendarAula').'</option> 
            <option value="sept"'.month_comparisonAula('sept').'>'.__('September','calendarAula').'</option> 
            <option value="oct"'.month_comparisonAula('oct').'>'.__('October','calendarAula').'</option> 
            <option value="nov"'.month_comparisonAula('nov').'>'.__('November','calendarAula').'</option> 
            <option value="dec"'.month_comparisonAula('dec').'>'.__('December','calendarAula').'</option> 
            </select>
            '.__('Year','calendarAula').': <select name="yr" style="width:60px;">
';

	// The year builder is string mania. If you can make sense of this, 
        // you know your PHP!
	function year_comparisonAula($year)
          {
            $current_year = strtolower(date("Y", time()));
            if (isset($_GET['yr']) && isset($_GET['month']))
              {
                if ($year == $_GET['yr'])
                  {
                    return ' selected="selected"';
                  }
              }
            else if ($year == $current_year)
              {
                return ' selected="selected"';
              }
          }

	$past = 30;
	$future = 30;
	$fut = 1;
	while ($past > 0)
	  {
	    $p .= '            <option value="';
	    $p .= date("Y",time())-$past;
	    $p .= '"'.year_comparisonAula(date("Y",time())-$past).'>';
	    $p .= date("Y",time())-$past.'</option>
';
	    $past = $past - 1;
	  }
	while ($fut < $future) 
	  {
	    $f .= '            <option value="';
	    $f .= date("Y",time())+$fut;
	    $f .= '"'.year_comparisonAula(date("Y",time())+$fut).'>';
	    $f .= date("Y",time())+$fut.'</option>
';
	    $fut = $fut + 1;
	  } 
	$calendar_body .= $p;
	$calendar_body .= '            <option value="'.date("Y",time()).'"'.year_comparisonAula(date("Y",time())).'>'.date("Y",time()).'</option>
';
	$calendar_body .= $f;
        $calendar_body .= '</select>
            <input type="submit" value="Ir" />
            </form>
        </td>
</tr>
';
      }

    // The header of the calendar table and the links. Note calls to link functions
    $calendar_body .= '<tr>
                <td colspan="7" class="calendar-heading">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                    <td class="calendar-prev">' . prev_linkAula($c_year,$c_month) . '</td>
                    <td class="calendar-month">'.$name_months[(int)$c_month].' '.$c_year.'</td>
                    <td class="calendar-next">' . next_linkAula($c_year,$c_month) . '</td>
                    </tr>
                    </table>
                </td>
</tr>
';

    // Print the headings of the days of the week
    $calendar_body .= '<tr>
';
    for ($i=1; $i<=7; $i++) 
      {
	// Colours need to be different if the starting day of the week is different
	if (get_option('start_of_week') == 0)
	  {
	    $calendar_body .= '        <td class="'.($i<7&&$i>1?'normal-day-heading':'weekend-heading').'">'.$name_days[$i].'</td>
';
	  }
	else
	  {
	    $calendar_body .= '        <td class="'.($i<6?'normal-day-heading':'weekend-heading').'">'.$name_days[$i].'</td>
';
	  }
      }
    $calendar_body .= '</tr>
';

    for ($i=1; $i<=$days_in_month;)
      {
        $calendar_body .= '<tr>
';
        for ($ii=1; $ii<=7; $ii++)
	  {
            if ($ii==$first_weekday && $i==1)
	      {
		$go = TRUE;
	      }
            elseif ($i > $days_in_month ) 
	      {
		$go = FALSE;
	      }

          if ($go) 
	      {
			// Colours again, this time for the day numbers
			if (get_option('start_of_week') == 0)
		  	{
		   		 // This bit of code is for styles believe it or not.
		    	$grabbed_events = grab_eventsAula($c_year,$c_month,$i);
		    	$no_events_class = '';
		    	if (!count($grabbed_events))
		      	{
					$no_events_class = ' no-events';
		      	}
		    		$calendar_body .= '        <td class="'.(date("Ymd", mktime (0,0,0,$c_month,$i,$c_year))==date("Ymd")?'current-day':'day-with-date').$no_events_class.'"><span '.($ii<7&&$ii>1?'':'class="weekend"').'>'.$i++.'</span><span class="event">' . draw_eventsAula($grabbed_events) . '</span></td>
';
		  	}
			else
		  	{
		    		$grabbed_events = grab_eventsAula($c_year,$c_month,$i);
		    		$how_many_events = count($grabbed_events);
		    		$bank_holiday = 'no';
		    		foreach($grabbed_events as $grabbed_event)
		    		{
		    			if($grabbed_event->event_category == 4){
		    				$bank_holiday = 'allday';
		    				break;
		    			}
		    			elseif($grabbed_event->event_category == 5){
		    				$bank_holiday = 'halfday';
		    				break;
		    			}
		    			$bank_holyday = 'no';
		    		}
		    		//$calendar_body .= '<span style="display:none;">'.$bank_holiday.'-'.$how_many_events.'</span>';
		    		$no_events_class = '';
	            	if (!count($grabbed_events))
		      		{
						$no_events_class = ' no-events';
		      		}
		      		
		      		if(($no_events_class != ' no-events') && ($bank_holiday == 'allday'))
		      		{
		      			$calendar_body .= '        <td class="bank-holiday-day"><span class="weekend">'.$i++.'</span><span class="event">' . draw_eventsAula($grabbed_events) . '</span></td>';
		      		}
		      		elseif(($no_events_class != ' no-events') && ($bank_holiday == 'halfday'))
		      		{
		      			$calendar_body .= '        <td class="half-holiday-day"><span '.($ii<6?'class="negro"':'class="weekend"').'>'.$i++.'</span><span class="event">' . draw_eventsAula($grabbed_events) . '</span></td>';
		      		}
		      		else
		      		{
		      			$calendar_body .= '        <td class="'.(date("Ymd", mktime (0,0,0,$c_month,$i,$c_year))==date("Ymd")?'current-day':'day-with-date').$no_events_class.'"><span '.($ii<6?'':'class="weekend"').'>'.$i++.'</span><span class="event">' . draw_eventsAula($grabbed_events) . '</span></td>';
		      		}
		  	}
	     }//endif($go)
         else 
	     {
			$calendar_body .= '        <td class="day-without-date">&nbsp;</td>';
	     }
      }//endfor($ii)
      $calendar_body .= '</tr>';
    }//endfor($i)
    
    //To show the last update event date
    $last_update = $wpdb->get_var("SELECT event_update FROM ".WP_CALENDAR_TABLE_Aula." WHERE event_valid='v' OR event_valid='r' ORDER BY event_update DESC LIMIT 1",0,0);
    if(!empty($last_update))
    	$calendar_body .= '<table><tr><td><span>Calendario actualizado a fecha: '.$last_update.'</span></td></tr></table>';
    
    
    $show_cat = $wpdb->get_var("SELECT config_value FROM ".WP_CALENDAR_CONFIG_TABLE_Aula." WHERE config_item='enable_categories'",0,0);
    
    //To show the categories table
    if ($show_cat == 'true')
      {
	$sql = "SELECT * FROM " . WP_CALENDAR_CATEGORIES_TABLE_Aula . " ORDER BY category_name ASC";
	$cat_details = $wpdb->get_results($sql);
        $calendar_body .= '<tr><td colspan="7">
<table class="cat-key">
<tr><td colspan="2"><strong>'.__('Category Key','calendarAula').'</strong></td></tr>';
        foreach($cat_details as $cat_detail)
	  {
	    $calendar_body .= '<tr><td style="background-color:'.$cat_detail->category_colour.'; width:20px; height:20px;"></td><td>'.$cat_detail->category_name.'</td></tr>';
	  }
        $calendar_body .= '</table>
</td></tr>';
      }
    $calendar_body .= '</table>';



    // Phew! After that bit of string building, spit it all out.
    // The actual printing is done by the calling function.
    return $calendar_body;
}

//Function to now if a day is a bank-holiday-day or not
function is_bank_holiday($begin,$end)
{
	global $wpdb;
	
	$is_bank_holiday = 'n';
	
	$bank_holiday_days = "SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_category=4 AND event_valid='y' AND event_begin<='" . $begin . "' AND event_end>='" . $end . "'";
	$result = $wpdb->get_results($bank_holiday_days);
	
	if(!empty($result))
	{
		$is_bank_holiday = 'y';
	}
	
	return $is_bank_holiday;
	
}

//Function to now if a day is a half-holiday-day or not
function is_half_holiday($begin,$end,$time_begin,$time_end)
{
	global $wpdb;
	
	$is_half_holiday = 'n';
	
	$half_holiday_days = "SELECT * FROM " . WP_CALENDAR_TABLE_Aula . " WHERE event_category=5 AND event_valid='y' AND event_begin<='" . $begin . "' AND event_end>='" . $end . "'";
	$result = $wpdb->get_results($half_holiday_days);
	
	if(!empty($result))
	{
		if(($time_begin >= '14:00') || ($time_end >= '14:00'))
		{
			$is_half_holiday = 'y';	
		}
	}
	
	return $is_half_holiday;
	
}

//Function to check if these events are at the same time
function is_the_same_time($begin_new_event, $end_new_event, $query_begin, $query_end)
{
		if ((strtotime($end_new_event) > strtotime($query_begin)) && (strtotime($end_new_event) <= strtotime($query_end)))
		{
			return 1;
		}
		else
		{
			if((strtotime($begin_new_event) >= strtotime($query_begin)) && (strtotime($begin_new_event) < strtotime($query_end)))
			{
				return 1;
			}
			else
			{
				if((strtotime($begin_new_event) <= strtotime($query_begin)) && (strtotime($end_new_event) >= strtotime($query_end)))
				{
					return 1;
				}
				else
				{
					return 0;
				}
			}
		}	
}

function is_the_same_day($begin_new_event, $end_new_event, $query_begin, $query_end)
{
		if ((strtotime($end_new_event) >= strtotime($query_begin)) && (strtotime($end_new_event) <= strtotime($query_end)))
		{
			return 1;
		}
		else
		{
			if((strtotime($begin_new_event) >= strtotime($query_begin)) && (strtotime($begin_new_event) <= strtotime($query_end)))
			{
				return 1;
			}
			else
			{
				if((strtotime($begin_new_event) <= strtotime($query_begin)) && (strtotime($end_new_event) >= strtotime($query_end)))
				{
					return 1;
				}
				else
				{
					return 0;
				}
			}
		}	
}
//Function to send an email notification
function sendEmailPsico($title, $desc, $begin, $end, $category)
{
	$to = "rocvaltor@gmail.com";
		
	$body = "Notificación de Reserva de espacios de la Facultad de Psicología.\n\n Título del evento: ".$title."\n Descripción: ".$desc."\n\n Hora de inicio: ".$begin."\n Hora fin: ".$end.".\n\n";
	$body .= "Categoría del evento: ".$category."\n\n\n";
	
	$subject = "Nueva reserva de espacio.";				
	
	$headers = "From: Facultad de Psicología de Sevilla <lab@mecus.es>\r\n" ."X-Mailer: php";
	
	mail($to, $subject, $body, $headers);
}

?>