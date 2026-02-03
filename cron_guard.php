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

// AVOIDE TWICE DATA ENTRY AS DUROING CRON IT DOES ADD TWICE
 if ( defined('DOING_CRON') && DOING_CRON ) {
        return;
    }

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
}
