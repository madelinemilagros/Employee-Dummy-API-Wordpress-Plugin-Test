<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              MadelineMilagros.com
 * @since             1.0.0
 * @package           Employeetestingmmm
 *
 * @wordpress-plugin
 * Plugin Name:       EmployeeTestingMMM
 * Plugin URI:        dummyemployeeetc
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Madeline Milagros Merced
 * Author URI:        MadelineMilagros.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       employeetestingmmm
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EMPLOYEETESTINGMMM_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-employeetestingmmm-activator.php
 */
function activate_employeetestingmmm() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-employeetestingmmm-activator.php';
	Employeetestingmmm_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-employeetestingmmm-deactivator.php
 */
function deactivate_employeetestingmmm() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-employeetestingmmm-deactivator.php';
	Employeetestingmmm_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_employeetestingmmm' );
register_deactivation_hook( __FILE__, 'deactivate_employeetestingmmm' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-employeetestingmmm.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_employeetestingmmm() {

	$plugin = new Employeetestingmmm();
	$plugin->run();

}


function register_employees_cpt() {
  register_post_type( 'employee', array(
    'label' => 'Employees',
    'public' => true,
    'capability_type' => 'post',
  ));
}


add_action( 'init', 'register_employees_cpt' );
add_action( 'wp_ajax_nopriv_get_employees_from_api', 'get_employees_from_api' );
add_action( 'wp_ajax_get_employees_from_api', 'get_employees_from_api' );

function get_employees_from_api() {
  $current_page = ( ! empty( $_POST['current_page'] ) ) ? $_POST['current_page'] : 1;
  $employees = [];

  // Should return an array of objects
  $results = wp_remote_retrieve_body( wp_remote_get('http://dummy.restapiexample.com/api/v1/employees') );
  // turn it into a PHP array from JSON string
  $results = json_decode( $results );   
  
  // Either the API is down or something else spooky happened. Just be done.
  if( ! is_array( $results ) || empty( $results ) ){
    return false;
  }

  $employees[] = $results;
  
  foreach( $employees[0] as $employee ){
    
    $employee_slug = sanitize_title($employee->employee_name . '-' . $employee_id);
    $existing_employee = get_page_by_path( $employee_slug, 'OBJECT', 'employee' );

  	 if( $existing_employee === null  ){
      
      $inserted_employee = wp_insert_post( [
        'post_name' => $employee_slug,
        'post_title' => $employee_slug,
        'post_type' => 'employee',
        'post_status' => 'publish'
      ] );

      if( is_wp_error( $inserted_employee ) || $inserted_employee === 0 ) {
     
        continue;
      }
      // add meta fields
      $fillable = [
        'field_5d8b79740f8fe' => 'id',
        'field_5d8b79890f8ff' => 'employee_name',
        'field_5d8b79940f900' => 'employee_salary',
        'field_5d8b799e0f901' => 'employee_age',
        'field_5d8b79a80f902' => 'profile_image',
      ];

      foreach( $fillable as $key => $name ) {
        update_field( $key, $employee->$name, $inserted_employee );
      }

       } else {
      
      $existing_employee_id = $existing_employee->ID;
      $exisiting_employee_timestamp = get_field('updated_at', $existing_employee_id);
      if( $employee->updated_at >= $exisiting_employee_timestamp ){
        $fillable = [
		'field_5d8b79740f8fe' => 'id',
        'field_5d8b79890f8ff' => 'employee_name',
        'field_5d8b79940f900' => 'employee_salary',
        'field_5d8b799e0f901' => 'employee_age',
        'field_5d8b79a80f902' => 'profile_image',
        ];
        foreach( $fillable as $key => $name ){
          update_field( $name, $employee->$name, $existing_employee_id);
        }
      }
    }
  }
  
 $current_page = $current_page + 1;
  wp_remote_post( admin_url('admin-ajax.php?action=get_employees_from_api'), [
    'blocking' => false,
    'sslverify' => false, // we are sending this to ourselves, so trust it.
    'body' => [
      'current_page' => $current_page
    ]
  ] );
}




run_employeetestingmmm();
