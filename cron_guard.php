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


// BLOCKING THE IP ON SEVERAL ATTEMPTS
add_action('init', function() {

    $blocked = get_option('cron_guard_blocked_ips', []);

    if (in_array($_SERVER['REMOTE_ADDR'], $blocked)) {
        wp_die('Access denied.');
    }

});




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

    set_transient('cron_guard_404', 1, 1); // 1s dalayed



// INSERTING IN DB
global $wpdb;

$table = $wpdb->prefix . 'cron_guard_events';
$ip    = $_SERVER['REMOTE_ADDR'];
$url   = esc_url_raw($_SERVER['REQUEST_URI']);

// Check if IP already exists in db (last 1 hour)
$row = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT id, attempts FROM $table 
         WHERE ip = %s 
         AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
         LIMIT 1",
        $ip 
    )
);

if ($row) {

    // Update attempts when ip already exists
    $wpdb->update(
        $table,
        [   //change these
            'attempts'   => $row->attempts + 1,
            'created_at' => current_time('mysql')
        ],
        ['id' => $row->id], 
        ['%d','%s'],
        ['%d'] //int dt
    );

    ////////////// DOING BLOCK THINS//////////////////////////
   if (($row->attempts + 1) >= 5) { 

    $blocked = get_option('cron_guard_blocked_ips', []); // Get existing blocked IPs
    
    if (!in_array($row->ip, $blocked)) { // if blocked ip alrady there
     
        $blocked[] = $row->ip;
        update_option('cron_guard_blocked_ips', $blocked); 

            // UPDATING DB TABLE STATUS TO BLOCKED
            
                $wpdb->update(
                $table,
                [
                    'status' => 'blocked'
                ],
                ['id' => $row->id],
                ['%s'],   // status is string
                ['%d']    // id is integer
            );


    }

    
// sendiong email toa dmin
$to      = get_option('admin_email');
    $site    = get_bloginfo('name');
    $home    = home_url();

    $subject = '404 detected on site';
    $message = 'Hello,<br>
    We have blocked an IP in your site <strong>' . $site . '</strong>.<br>
    Please visit: <a href="' . $home . '">' . $home . '</a>';

    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail($to, $subject, $message, $headers);
}
} 


else {
    // Insert new row
    $wpdb->insert(
        $table,
        [
            'ip'          => $ip,
            'url'         => $url,
            'attempts'    => 1,
            'status'      => 'pending',
            'available_at'=> current_time('mysql'),
            'created_at'  => current_time('mysql')
        ],
        ['%s','%s','%d','%s','%s','%s']
    );
}





}




    