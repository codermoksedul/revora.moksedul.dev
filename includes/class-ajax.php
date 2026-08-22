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
		add_action( 'wp_ajax_revora_load_more', array( $this, 'handle_load_more' ) );
		add_action( 'wp_ajax_nopriv_revora_load_more', array( $this, 'handle_load_more' ) );
	}

	/**
	 * Handle Review Submission
	 */
	public function handle_submission() {
		// Nonce check
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'revora_submit_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'revora' ) ) );
		}

		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;

		// Check file sizes (Limit: 1MB = 1048576 bytes)
		if ( ! empty( $_FILES ) ) {
			foreach ( $_FILES as $file ) {
				if ( $file['size'] > 1048576 ) {
					wp_send_json_error( array( 'message' => __( 'File size exceeds the 1MB limit. Please upload a smaller image.', 'revora' ) ) );
				}
			}
		}

		// Sanitize and collect standard data
		$data = array(
			'user_id'         => get_current_user_id(),
			'form_id'         => $form_id,
			'name'            => sanitize_text_field( wp_unslash( $_POST['name'] ?? 'Anonymous' ) ),
			'email'           => sanitize_email( wp_unslash( $_POST['email'] ?? 'noreply@example.com' ) ),
			'rating'          => floatval( wp_unslash( $_POST['rating'] ?? 5 ) ),
			'title'           => sanitize_text_field( wp_unslash( $_POST['title'] ?? 'Review' ) ),
			'content'         => sanitize_textarea_field( wp_unslash( $_POST['content'] ?? '' ) ),
			'ip_address'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'revora_honeypot' => isset( $_POST['revora_honeypot'] ) ? sanitize_text_field( wp_unslash( $_POST['revora_honeypot'] ) ) : '',
		);

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
			// Handle Custom Fields & File Uploads if form_id provided
			if ( $form_id ) {
				$form = $db->get_form( $form_id );
				if ( $form ) {
					$fields = json_decode( $form->fields, true );
					$standard_keys = array( 'name', 'email', 'rating', 'title', 'content', 'form_id', 'nonce' );
					
					$this->process_custom_fields( $fields, $inserted, $standard_keys, $db );
				}
			}

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

	private function process_custom_fields( $fields, $inserted, $standard_keys, $db ) {
		if ( ! is_array( $fields ) ) return;
		
		foreach ( $fields as $field ) {
			if ( 'row' === $field['type'] ) {
				if ( ! empty( $field['columns'] ) && is_array( $field['columns'] ) ) {
					foreach ( $field['columns'] as $col_fields ) {
						$this->process_custom_fields( $col_fields, $inserted, $standard_keys, $db );
					}
				}
				continue;
			}
			
			$key = $field['key'] ?? '';
			if ( empty( $key ) || in_array( $key, $standard_keys ) ) {
				continue;
			}
			
			if ( ( 'file' === $field['type'] || 'avatar' === $field['type'] ) && isset( $_FILES[ $key ] ) && ! empty( $_FILES[ $key ]['name'] ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				
				$allowed_raw = ! empty( $field['allowed_types'] ) ? $field['allowed_types'] : ( 'avatar' === $field['type'] ? 'jpg, jpeg, png, webp' : '' );
				
				// Validate file extension if configured
				if ( ! empty( $allowed_raw ) ) {
					$filename = sanitize_file_name( $_FILES[ $key ]['name'] );
					$file_ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
					$allowed_exts = array_map( 'trim', explode( ',', strtolower( str_replace( '.', '', $allowed_raw ) ) ) );
					if ( ! in_array( $file_ext, $allowed_exts, true ) ) {
						continue;
					}
				}

				$upload_overrides = array( 'test_form' => false );
				$movefile = wp_handle_upload( $_FILES[ $key ], $upload_overrides );
				
				if ( $movefile && ! isset( $movefile['error'] ) ) {
					$file_url = esc_url_raw( $movefile['url'] );
					$db->update_review_meta( $inserted, $key, $file_url );
					if ( 'avatar' === $field['type'] || 'avatar' === $key || 'profile_image' === $key ) {
						$db->update_review_meta( $inserted, 'avatar_url', $file_url );
					}
				}
			} elseif ( isset( $_POST[ $key ] ) ) {
				$raw_val = wp_unslash( $_POST[ $key ] );
				if ( is_array( $raw_val ) ) {
					$value = array_map( 'sanitize_textarea_field', $raw_val );
					$value = implode( ', ', $value );
				} else {
					$value = sanitize_textarea_field( $raw_val );
				}
				$db->update_review_meta( $inserted, $key, $value );
			}
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
			'{author}'     => $data['name'],
			'{name}'       => $data['name'],
			'{title}'      => $data['title'],
			'{content}'    => $data['content'],
			'{rating}'     => $data['rating'],
			'{admin_url}' => admin_url( 'admin.php?page=revora' ),
		);

		$subject = str_replace( array_keys( $replace ), array_values( $replace ), $subject_template );
		$message = str_replace( array_keys( $replace ), array_values( $replace ), $body_template );

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Handle Load More Reviews
	 */
	public function handle_load_more() {
		check_ajax_referer( 'revora_nonce', 'nonce' );

		$form_id    = isset( $_POST['form_id'] ) ? intval( wp_unslash( $_POST['form_id'] ) ) : 0;
		$page          = isset( $_POST['page'] ) ? intval( wp_unslash( $_POST['page'] ) ) : 1;
		$initial_limit = isset( $_POST['initial_limit'] ) ? intval( wp_unslash( $_POST['initial_limit'] ) ) : 6;
		$limit         = isset( $_POST['limit'] ) ? intval( wp_unslash( $_POST['limit'] ) ) : 6;
		$card_style    = isset( $_POST['card_style'] ) ? sanitize_text_field( wp_unslash( $_POST['card_style'] ) ) : 'classic';
		
		$offset = $initial_limit + ( ( $page - 1 ) * $limit );

		$db = new Revora_DB();
		$reviews = $db->get_approved_reviews( $form_id, $limit, $offset );
		$total = $db->get_total_approved_count( $form_id );
		$settings = wp_parse_args( get_option( 'revora_settings', array() ), array(
			'star_color' => '#fbbf24',
			'show_stars' => '1',
		) );

		if ( empty( $reviews ) ) {
			wp_send_json_error( array( 'message' => __( 'No more reviews.', 'revora' ) ) );
			return;
		}

		ob_start();
		foreach ( $reviews as $review ) :
			$avatar_url = ! empty( $review->id ) ? $db->get_review_meta( $review->id, 'avatar_url' ) : '';
			if ( empty( $avatar_url ) && ! empty( $review->id ) ) {
				$avatar_url = $db->get_review_meta( $review->id, 'avatar' );
			}
			$name_parts = explode( ' ', trim( $review->name ) );
			$initials   = '';
			if ( ! empty( $name_parts[0] ) ) {
				$initials .= mb_strtoupper( mb_substr( $name_parts[0], 0, 1 ) );
			}
			if ( isset( $name_parts[1] ) && ! empty( $name_parts[1] ) ) {
				$initials .= mb_strtoupper( mb_substr( $name_parts[1], 0, 1 ) );
			}
			if ( empty( $initials ) ) {
				$initials = 'R';
			}
			$masked_contact = '';
			$meta = ! empty( $review->id ) ? $db->get_review_meta( $review->id ) : array();
			if ( ! empty( $meta ) && is_array( $meta ) ) {
				foreach ( $meta as $k => $v ) {
					$k_lower = strtolower( $k );
					if ( ( false !== strpos( $k_lower, 'phone' ) || 'tel' === $k_lower || false !== strpos( $k_lower, 'contact' ) || false !== strpos( $k_lower, 'mobile' ) || false !== strpos( $k_lower, 'number' ) || false !== strpos( $k_lower, 'whatsapp' ) ) && ! empty( $v ) && ( is_string( $v ) || is_numeric( $v ) ) ) {
						$masked_contact = trim( (string) $v );
						break;
					}
				}
			}
			if ( empty( $masked_contact ) && ! empty( $review->email ) ) {
				$masked_contact = trim( $review->email );
			}
			if ( ! empty( $masked_contact ) ) {
				$len = mb_strlen( $masked_contact );
				if ( $len <= 6 ) {
					$user_subtitle = mb_substr( $masked_contact, 0, 1 ) . '******' . mb_substr( $masked_contact, -1 );
				} else {
					$user_subtitle = mb_substr( $masked_contact, 0, 3 ) . '******' . mb_substr( $masked_contact, -3 );
				}
			} else {
				$user_subtitle = esc_html__( 'Verified Customer', 'revora' );
			}
			?>
			<div class="revora-review-card style-<?php echo esc_attr( $card_style ); ?>">
				<div class="revora-review-header">
					<div class="revora-review-author-wrap">
						<div class="revora-review-avatar">
							<?php if ( ! empty( $avatar_url ) ) : ?>
								<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $review->name ); ?>" />
							<?php else : ?>
								<span class="revora-avatar-initials"><?php echo esc_html( $initials ); ?></span>
							<?php endif; ?>
						</div>
						<div class="revora-review-meta">
							<span class="revora-review-author"><?php echo esc_html( $review->name ); ?></span>
							<span class="revora-review-subtitle"><?php echo esc_html( $user_subtitle ); ?></span>
							<span class="revora-review-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ) ); ?></span>
						</div>
					</div>
					<div class="revora-review-header-right">
						<?php if ( '1' === $settings['show_stars'] ) : ?>
							<div class="revora-review-rating">
								<?php for ( $i = 1; $i <= 5; $i++ ) : 
									$r_val = floatval( $review->rating );
									if ( $r_val >= $i ) {
										$icon_text = 'star';
										$icon_class = 'filled fill-1';
									} elseif ( $r_val >= ( $i - 0.5 ) ) {
										$icon_text = 'star_half';
										$icon_class = 'filled fill-1';
									} else {
										$icon_text = 'star';
										$icon_class = 'empty';
									}
								?>
									<span class="material-symbols-outlined revora-star <?php echo esc_attr( $icon_class ); ?>"><?php echo esc_html( $icon_text ); ?></span>
								<?php endfor; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<?php if ( ! empty( $review->title ) ) : ?>
					<h4 class="revora-review-title"><?php echo esc_html( $review->title ); ?></h4>
				<?php endif; ?>
				<div class="revora-review-content">
					<?php echo wp_kses_post( wpautop( esc_html( $review->content ) ) ); ?>
				</div>
				<div class="revora-review-footer">
					<span class="revora-footer-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ) ); ?></span>
					<span class="revora-footer-quote">”</span>
				</div>
			</div>
			<?php
		endforeach;
		$html = ob_get_clean();

		$loaded = $offset + count( $reviews );
		$has_more = $loaded < $total;

		wp_send_json_success( array(
			'html' => $html,
			'has_more' => $has_more,
			'loaded' => $loaded,
			'total' => $total,
		) );
	}
}
