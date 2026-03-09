<?php
defined( 'ABSPATH' ) || exit;

class Simple_Mailer_SMTP {

    private $options;

    public function __construct( $options ) {
        $this->options = $options;
    }

    public function send( $to, $subject, $message, $headers = array(), $attachments = array() ) {
        // 暫時覆蓋 phpmailer 設定
        add_action( 'phpmailer_init', array( $this, 'configure_phpmailer' ) );
        $result = wp_mail( $to, $subject, $message, $headers, $attachments );
        remove_action( 'phpmailer_init', array( $this, 'configure_phpmailer' ) );
        if ( ! $result ) {
            throw new Exception( 'SMTP 寄送失敗' );
        }
        return true;
    }

    public function configure_phpmailer( $phpmailer ) {
        $o = $this->options;
        $phpmailer->isSMTP();
        $phpmailer->Host       = isset( $o['smtp_host'] ) ? $o['smtp_host'] : '';
        $phpmailer->Port       = isset( $o['smtp_port'] ) ? (int) $o['smtp_port'] : 587;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = isset( $o['smtp_user'] ) ? $o['smtp_user'] : '';
        $phpmailer->Password   = isset( $o['smtp_pass'] ) ? $o['smtp_pass'] : '';
        $phpmailer->SMTPSecure = isset( $o['smtp_secure'] ) ? $o['smtp_secure'] : 'tls';
        $phpmailer->From       = isset( $o['from_email'] ) ? $o['from_email'] : get_option( 'admin_email' );
        $phpmailer->FromName   = isset( $o['from_name'] ) ? $o['from_name'] : get_bloginfo( 'name' );
    }
}
