<?php
if ( ! defined( 'ABSPATH' ) ) exit;

//  Add interval for CRON
add_filter('cron_schedules', function($schedules){
    $schedules['every_minute'] = [
        'interval' => 60,
        'display'  => 'Every Minute'
    ];
    return $schedules;
});

// ADDIBNT INTERVAL FRO DELETING OF CRON EVERYDAY
add_filter( 'cron_schedules', function($sche){
    $sche['every_day']=[
          'interval' => 86400,
        'display'  => 'Every Day'
    ];
    return $sche;
});



// 2 Activation
function cron_guard_activate() {

    wp_cron_guard_create_tables(); //create function calling

        // main cron 
    if ( ! wp_next_scheduled('log_404_cron') ) {
        wp_schedule_event( time(), 'every_minute', 'log_404_cron' );
    }

// CRON JOBS FOR DELETING OLDER THAN 30DAYS
 if(!wp_next_scheduled('delete_30_days')){
    wp_schedule_event( time(), 'every_day', 'delete_30_days' );
 }

//    BLOCKING THE BLOCKED IP USER  // 
 add_action('init', function () {
    $blocked = get_option('cron_guard_blocked_ips', []);
    if ( in_array($_SERVER['REMOTE_ADDR'], $blocked) ) {
        wp_die('Access Denied');
    }
});
}
register_activation_hook(__FILE__, 'cron_guard_activate');







// 3 Create table
function wp_cron_guard_create_tables() {
    global $wpdb;

    $table = $wpdb->prefix . 'cron_guard_events';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ip VARCHAR(50) NOT NULL,
        url TEXT NULL,
        attempts INT NOT NULL DEFAULT 1,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        available_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
