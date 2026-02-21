<?php

// STEP 1: LOG 404 HITS INTO DB
add_action('template_redirect', function () {

    if (is_404()) {
        // log only browser page (not css/js/image)
        if (!str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html'))
            return;

    global $wpdb;
    $table = $wpdb->prefix . 'cron_guard_events';

        $ip = $_SERVER['REMOTE_ADDR'];
        $url = esc_url_raw($_SERVER['REQUEST_URI']);
        $now = current_time('mysql');

        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (ip, url, attempts, status, available_at, created_at)
            VALUES (%s, %s, 1, 'pending', %s, %s)
            ON DUPLICATE KEY UPDATE
            attempts = attempts + 1,
            url = VALUES(url),
            status = 'pending',
            available_at = VALUES(available_at)",
            $ip,
            $url,
            $now,
            $now
        ));
    }

});


// STEP 2: CRON PROCESS (RUNS EVERY MIN )
add_action('log_404_cron', function () {

    global $wpdb;
    $table = $wpdb->prefix . 'cron_guard_events';

    $rows = $wpdb->get_results(
        "SELECT id, ip, attempts 
         FROM $table
         WHERE status = 'pending'
         AND available_at <= NOW()
         ORDER BY id ASC
         LIMIT 5"
    );

    if ($rows) {

        foreach ($rows as $row) {

            // If attempts reach 5 -> block
            if (($row->attempts + 1) >= 5) {

                $blocked = get_option('cron_guard_blocked_ips', []); //getting blocked list from wp

                if (!in_array($row->ip, $blocked)) { //not in list
                    $blocked[] = $row->ip;

                    update_option('cron_guard_blocked_ips', $blocked);
                }

                $wpdb->update(
                    $table,
                    ['status' => 'blocked'],
                    ['id' => $row->id],
                    ['%s'],
                    ['%d']
                );

            } else {

                // NOT IN LIST  increase attempts and mark processing
                $wpdb->update(
                    $table,
                    [
                        'status' => 'done',
                        'attempts' => $row->attempts + 1
                    ],
                    ['id' => $row->id],
                    ['%s', '%d'],
                    ['%d']
                );

            }
        }
    }

});

// DELETING FUNCTION FOR CRON JOB
add_action('delete_30_days', function () {

    global $wpdb;
    $table = $wpdb->prefix . 'cron_guard_events';

    $wpdb->query(
        "DELETE FROM $table 
         WHERE created_at < NOW() - INTERVAL 30 DAY"
    );

});

