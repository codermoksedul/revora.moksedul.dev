<?php
/**
 * Spam Control Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Revora_Spam {

	/**
	 * Run all spam checks
	 */
	public function is_spam( $data ) {
		// 1. Honeypot check
		if ( ! $this->check_honeypot( $data ) ) {
			return new WP_Error( 'spam_honeypot', __( 'Spam detected (Honeypot).', 'revora' ) );
		}

		// 2. Numeric-only name check
		if ( $this->is_numeric_name( $data['name'] ) ) {
			return new WP_Error( 'spam_numeric_name', __( 'Numbers-only names are not allowed.', 'revora' ) );
		}

		// 3. Minimum length check
		if ( $this->is_too_short( $data['content'] ) ) {
			return new WP_Error( 'spam_too_short', __( 'Reviews must be at least 25 characters long.', 'revora' ) );
		}

		// 4. IP Throttling (Max 3 per hour)
		if ( $this->is_throttled( $data['ip_address'] ) ) {
			return new WP_Error( 'spam_throttled', __( 'Too many submissions from your IP. Please try again later.', 'revora' ) );
		}

		// 5. Disposable email check
		if ( $this->is_disposable_email( $data['email'] ) ) {
			return new WP_Error( 'spam_disposable_email', __( 'Disposable email domains are not allowed.', 'revora' ) );
		}

		// 6. Duplicate check
		if ( $this->is_duplicate( $data ) ) {
			return new WP_Error( 'spam_duplicate', __( 'Duplicate review detected.', 'revora' ) );
		}

		return false;
	}

	/**
	 * Check Honeypot field
	 */
	private function check_honeypot( $data ) {
		return empty( $data['revora_honeypot'] );
	}

	/**
	 * Check if name is numeric only
	 */
	private function is_numeric_name( $name ) {
		return is_numeric( str_replace( ' ', '', $name ) );
	}

	/**
	 * Check if review is too short
	 */
	private function is_too_short( $content ) {
		return strlen( trim( $content ) ) < 25;
	}

	/**
	 * Check IP throttling
	 */
	private function is_throttled( $ip ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'revora_reviews';

		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(id) FROM $table_name WHERE ip_address = %s AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
			$ip
		) );

		return (int) $count >= 3;
	}

	/**
	 * Check for disposable email domains
	 */
	private function is_disposable_email( $email ) {
		$domain = substr( strrchr( $email, "@" ), 1 );
		$disposable_domains = array(
			'mailinator.com',
			'10minutemail.com',
			'guerrillamail.com',
			'yopmail.com',
			'temp-mail.org',
			// Add more as needed
		);

		return in_array( strtolower( $domain ), $disposable_domains );
	}

	/**
	 * Check for duplicate reviews (same email/content in last 24 hours)
	 */
	private function is_duplicate( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'revora_reviews';

		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(id) FROM $table_name WHERE email = %s AND content = %s AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
			$data['email'],
			$data['content']
		) );

		return (int) $count > 0;
	}
}
