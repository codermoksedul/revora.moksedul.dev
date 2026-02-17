<?php
/**
 * AJAX Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Revora_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_revora_submit', array( $this, 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_revora_submit', array( $this, 'handle_submission' ) );
	}

	/**
	 * Handle Review Submission
	 */
	public function handle_submission() {
		// Nonce check
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'revora_submit_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'revora' ) ) );
		}

		// Sanitize and collect data
		$data = array(
			'category_slug'   => sanitize_text_field( $_POST['category_slug'] ),
			'name'            => sanitize_text_field( $_POST['name'] ),
			'email'           => sanitize_email( $_POST['email'] ),
			'rating'          => intval( $_POST['rating'] ),
			'title'           => sanitize_text_field( $_POST['title'] ),
			'content'         => sanitize_textarea_field( $_POST['content'] ),
			'ip_address'      => $_SERVER['REMOTE_ADDR'],
			'revora_honeypot' => $_POST['revora_honeypot'], // For spam check
		);

		// Basic validation
		if ( empty( $data['name'] ) || empty( $data['email'] ) || empty( $data['rating'] ) || empty( $data['content'] ) ) {
			wp_send_json_error( array( 'message' => __( 'All required fields must be filled.', 'revora' ) ) );
		}

		// Spam checks
		$spam = new Revora_Spam();
		$is_spam = $spam->is_spam( $data );

		if ( is_wp_error( $is_spam ) ) {
			wp_send_json_error( array( 'message' => $is_spam->get_error_message() ) );
		}

		// Prepare for DB
		unset( $data['revora_honeypot'] );
		
		$settings = get_option( 'revora_settings' );
		$data['status'] = ( isset( $settings['auto_approve'] ) && '1' === $settings['auto_approve'] ) ? 'approved' : 'pending';

		$db = new Revora_DB();
		$inserted = $db->insert_review( $data );

		if ( $inserted ) {
			// Trigger email notification
			$this->send_notifications( $data );

			$success_msg = ( 'approved' === $data['status'] ) 
				? __( 'Thank you! Your review has been published.', 'revora' )
				: __( 'Thank you! Your review has been submitted and is awaiting moderation.', 'revora' );

			wp_send_json_success( array(
				'message' => $success_msg,
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Something went wrong. Please try again.', 'revora' ) ) );
		}
	}

	/**
	 * Send Notifications
	 */
	private function send_notifications( $data ) {
		$settings = get_option( 'revora_settings' );
		$admin_email = ! empty( $settings['admin_email'] ) ? $settings['admin_email'] : get_option( 'admin_email' );

		$subject_template = ! empty( $settings['email_subject'] ) ? $settings['email_subject'] : __( 'New Review Submitted', 'revora' );
		$body_template    = ! empty( $settings['email_template'] ) ? $settings['email_template'] : __( "New review from {author}\nRating: {rating}\n\n{content}", 'revora' );

		$replace = array(
			'{author}'    => $data['name'],
			'{rating}'    => $data['rating'],
			'{content}'   => $data['content'],
			'{admin_url}' => admin_url( 'admin.php?page=revora' ),
		);

		$subject = str_replace( array_keys( $replace ), array_values( $replace ), $subject_template );
		$message = str_replace( array_keys( $replace ), array_values( $replace ), $body_template );

		wp_mail( $admin_email, $subject, $message );
	}
}
