<?php
defined( 'ABSPATH' ) || exit;

class Simple_Mailer_Core {

    public function __construct() {
        add_filter( 'pre_wp_mail', array( $this, 'send' ), 10, 2 );
    }

    public function send( $return, $atts ) {
        $options  = get_option( 'simple_mailer_settings', array() );
        $type     = isset( $options['type'] ) ? $options['type'] : 'smtp';

        $to      = $atts['to'];
        $subject = $atts['subject'];
        $message = $atts['message'];
        $headers = $atts['headers'];
        $attachments = $atts['attachments'];

        try {
            switch ( $type ) {
                case 'resend':
                    $mailer = new Simple_Mailer_Resend( $options );
                    break;
                case 'brevo':
                    $mailer = new Simple_Mailer_Brevo( $options );
                    break;
                default:
                    $mailer = new Simple_Mailer_SMTP( $options );
                    break;
            }

            $result = $mailer->send( $to, $subject, $message, $headers, $attachments );

            Simple_Mailer_Logger::insert( $subject, $to, 'success', '寄送成功' );
            return true;

        } catch ( Exception $e ) {
            Simple_Mailer_Logger::insert( $subject, $to, 'failed', $e->getMessage() );
            return false;
        }
    }
}
