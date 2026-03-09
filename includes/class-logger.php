<?php
defined( 'ABSPATH' ) || exit;

class Simple_Mailer_Logger {

    public static function create_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'simple_mailer_logs';
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE IF NOT EXISTS {$table} (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            subject    VARCHAR(255)    NOT NULL DEFAULT '',
            sent_to    TEXT            NOT NULL,
            status     VARCHAR(20)     NOT NULL DEFAULT 'unknown',
            message    TEXT,
            created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function insert( $subject, $sent_to, $status, $message = '' ) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'simple_mailer_logs',
            array(
                'subject'    => $subject,
                'sent_to'    => is_array( $sent_to ) ? implode( ', ', $sent_to ) : $sent_to,
                'status'     => $status,
                'message'    => $message,
                'created_at' => current_time( 'mysql' ),
            )
        );
    }

    public static function get_logs( $limit = 50, $offset = 0 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'simple_mailer_logs';
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
    }

    public static function count() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}simple_mailer_logs" );
    }

    public static function delete_log( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'simple_mailer_logs', array( 'id' => absint( $id ) ) );
    }

    public static function delete_all() {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}simple_mailer_logs" );
    }
}
