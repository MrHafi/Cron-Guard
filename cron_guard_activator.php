<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function cron_guard_activate() {
    wp_cron_guard_create_tables();
    error_log('Cron Guard activated');
}

    
    // CREATING A DB TABLE ON ACTIVATIONa
function wp_cron_guard_create_tables() {
    global $wpdb;

    $table = $wpdb->prefix . 'cron_guard_events';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        event_type VARCHAR(20) NOT NULL,
        ip VARCHAR(50) NOT NULL,
        url TEXT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY ip (ip),
        KEY event_type (event_type),
        KEY created_at (created_at)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}




  