<?php
/*
Plugin Name: Cron Guard
Description: Simple learning plugin example.
Version: 1.0
Author: You
*/

if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path(__FILE__) . 'cron_guard_activate.php';

/* Activate */
register_activation_hook(__FILE__, 'cron_guard_activate');

/* Enqueue JS */
function cron_guard_enqueue_scripts() {
    wp_enqueue_script(
        'cron-guard-js',
        plugin_dir_url(__FILE__) . 'js/script.js',
        array('jquery'),
        '1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'cron_guard_enqueue_scripts');






// ////////////////// WHEN USER HIT 404 //////////////////////////////////////////////
// STEP 2: CHECKING 404 HITS
add_action('template_redirect', function(){

if(is_404()){
    if(!wp_next_scheduled( 'log_404_cron' ))
        {
            wp_schedule_single_event(  time(),'log_404_cron' );
        }
}
} );

// triggering cron
add_action( 'log_404_cron', 'hit_404');
function hit_404(){

// It checks temporary stored data in WordPress and blocks repeat runs
 if ( get_transient('cron_guard_404') ) return;

    set_transient('cron_guard_404', 1, 60); // 1 minute lock

// INSERTING IN DB
global $wpdb;

$wpdb->insert(
      $wpdb->prefix . 'cron_guard_events',
    [
        'event_type' => '404',
        'ip'         => $_SERVER['REMOTE_ADDR'],
        'url'        => esc_url_raw($_SERVER['REQUEST_URI']),
        'created_at' => current_time('mysql')
    ],
    [ '%s', '%s', '%s', '%s' ]
);



// sendiong email toa dmin

$to      = get_option('admin_email');
    $site    = get_bloginfo('name');
    $home    = home_url();

    $subject = '404 detected on site';
    $message = 'Hello,<br>
    We have detected a 404 error on your site <strong>' . $site . '</strong>.<br>
    Please visit: <a href="' . $home . '">' . $home . '</a>';

    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail($to, $subject, $message, $headers);
}




    