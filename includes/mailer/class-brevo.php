<?php
defined( 'ABSPATH' ) || exit;

class Simple_Mailer_Brevo {

    private $api_key;
    private $from_name;
    private $from_email;

    public function __construct( $options ) {
        $this->api_key    = isset( $options['brevo_api_key'] ) ? trim( $options['brevo_api_key'] ) : '';
        $this->from_email = isset( $options['from_email'] ) ? trim( $options['from_email'] ) : get_option( 'admin_email' );
        $this->from_name  = isset( $options['from_name'] ) ? trim( $options['from_name'] ) : get_bloginfo( 'name' );
    }

    public function send( $to, $subject, $message, $headers = array(), $attachments = array() ) {
        if ( empty( $this->api_key ) ) {
            throw new Exception( 'Brevo API 金鑰未設定' );
        }

        $recipients = array();
        $tos = is_array( $to ) ? $to : array( $to );
        foreach ( $tos as $email ) {
            $recipients[] = array( 'email' => trim( $email ) );
        }

        $is_html = $this->is_html( $headers );
        $payload = array(
            'sender'  => array( 'name' => $this->from_name, 'email' => $this->from_email ),
            'to'      => $recipients,
            'subject' => $subject,
        );
        if ( $is_html ) {
            $payload['htmlContent'] = $message;
        } else {
            $payload['textContent'] = $message;
        }

        $response = wp_remote_post( 'https://api.brevo.com/v3/smtp/email', array(
            'headers' => array(
                'api-key'      => $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body'    => wp_json_encode( $payload ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            throw new Exception( $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            $body = wp_remote_retrieve_body( $response );
            throw new Exception( 'Brevo API 錯誤 (' . $code . '): ' . $body );
        }

        return true;
    }

    private function is_html( $headers ) {
        if ( is_array( $headers ) ) {
            foreach ( $headers as $h ) {
                if ( stripos( $h, 'content-type' ) !== false && stripos( $h, 'text/html' ) !== false ) {
                    return true;
                }
            }
        } elseif ( is_string( $headers ) ) {
            return stripos( $headers, 'text/html' ) !== false;
        }
        return false;
    }
}
