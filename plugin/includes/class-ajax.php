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
		
		// Ensure category exists and get its ID
		$cat_slug = ! empty( $data['category_slug'] ) ? $data['category_slug'] : 'unknown';
		$cat_id = $db->ensure_category_exists( $cat_slug );
		
		$inserted = $db->insert_review( $data );

		if ( $inserted ) {
			// Link review to category
			$db->set_review_categories( $inserted, array( $cat_id ) );
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
		$category = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : '';
		$page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		$limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 6;
		$card_style = isset( $_POST['card_style'] ) ? sanitize_text_field( $_POST['card_style'] ) : 'classic';
		$offset = $page * $limit;

		$db = new Revora_DB();
		$reviews = $db->get_approved_reviews( $category, $limit, $offset );
		$total = $db->get_total_approved_count( $category );
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
			?>
			<div class="revora-review-card style-<?php echo esc_attr( $card_style ); ?>">
				<div class="revora-review-header">
					<div class="revora-review-meta">
						<span class="revora-review-author"><?php echo esc_html( $review->name ); ?></span>
						<span class="revora-review-date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ); ?></span>
					</div>
					<?php if ( '1' === $settings['show_stars'] ) : ?>
						<div class="revora-review-rating">
							<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
								<span class="dashicons dashicons-star-filled <?php echo $i <= $review->rating ? 'filled' : 'empty'; ?>"></span>
							<?php endfor; ?>
						</div>
					<?php endif; ?>
				</div>
				<h4 class="revora-review-title"><?php echo esc_html( $review->title ); ?></h4>
				<div class="revora-review-content">
					<?php echo wpautop( esc_html( $review->content ) ); ?>
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
