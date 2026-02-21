<?php
/*
Plugin Name: Cron Guard
Description: Simple learning plugin example.
Version: 1.0
Author: You
*/

if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path(__FILE__) . 'cron_guard_activate.php';
require_once plugin_dir_path(__FILE__) . 'task.php';
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


// BLOCKING THE IP ON SEVERAL ATTEMPTS
add_action('init', function() {

    $blocked = get_option('cron_guard_blocked_ips', []);

    if (in_array($_SERVER['REMOTE_ADDR'], $blocked)) {
        wp_die('Access denied.');
    }

});


