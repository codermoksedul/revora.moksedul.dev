<?php
/**
 * Frontend Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Revora_Frontend {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_shortcode( 'revora_reviews', array( $this, 'render_reviews' ) );
		add_shortcode( 'revora_form', array( $this, 'render_form' ) );
		add_action( 'wp_head', array( $this, 'inject_schema' ) );
	}

	/**
	 * Render Reviews Shortcode
	 */
	public function render_reviews( $atts ) {
		$atts = shortcode_atts( array(
			'category' => 'default',
			'limit'    => 10,
		), $atts, 'revora_reviews' );

		$db = new Revora_DB();
		$reviews = $db->get_reviews( array(
			'category_slug' => $atts['category'],
			'limit'         => $atts['limit'],
		) );
		$stats = $db->get_stats( $atts['category'] );

		ob_start();
		?>
		<div class="revora-reviews-container" data-category="<?php echo esc_attr( $atts['category'] ); ?>">
			<?php if ( $stats && $stats->total > 0 ) : ?>
				<div class="revora-summary">
					<div class="revora-average">
						<span class="revora-average-number"><?php echo number_format( $stats->average, 1 ); ?></span>
						<div class="revora-stars-display">
							<?php echo $this->render_stars( $stats->average ); ?>
						</div>
						<span class="revora-total-count"><?php printf( _n( '%s review', '%s reviews', $stats->total, 'revora' ), number_format_i18n( $stats->total ) ); ?></span>
					</div>
				</div>
			<?php endif; ?>

			<div class="revora-reviews-list">
				<?php if ( $reviews ) : ?>
					<?php foreach ( $reviews as $review ) : ?>
						<div class="revora-review-card">
							<div class="revora-review-header">
								<div class="revora-review-stars">
									<?php echo $this->render_stars( $review->rating ); ?>
								</div>
								<h3 class="revora-review-title"><?php echo esc_html( $review->title ); ?></h3>
							</div>
							<div class="revora-review-meta">
								<span class="revora-review-author"><?php echo esc_html( $review->name ); ?></span>
								<span class="revora-review-date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ); ?></span>
							</div>
							<div class="revora-review-content">
								<?php echo wpautop( esc_html( $review->content ) ); ?>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="revora-no-reviews"><?php _e( 'No reviews yet for this category.', 'revora' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Review Form Shortcode
	 */
	public function render_form( $atts ) {
		$atts = shortcode_atts( array(
			'category' => 'default',
		), $atts, 'revora_form' );

		ob_start();
		?>
		<div class="revora-form-container">
			<form id="revora-submission-form" class="revora-form">
				<input type="hidden" name="action" value="revora_submit">
				<input type="hidden" name="category_slug" value="<?php echo esc_attr( $atts['category'] ); ?>">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'revora_submit_nonce' ); ?>">
				
				<div class="revora-form-field honeypot-field" style="display:none !important;">
					<input type="text" name="revora_honeypot" value="">
				</div>

				<div class="revora-form-row">
					<div class="revora-form-field">
						<label for="revora-name"><?php _e( 'Name', 'revora' ); ?></label>
						<input type="text" id="revora-name" name="name" required>
					</div>
					<div class="revora-form-field">
						<label for="revora-email"><?php _e( 'Email', 'revora' ); ?></label>
						<input type="email" id="revora-email" name="email" required>
					</div>
				</div>

				<div class="revora-form-field">
					<label><?php _e( 'Rating', 'revora' ); ?></label>
					<div class="revora-rating-input">
						<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
							<input type="radio" id="star-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required />
							<label for="star-<?php echo $i; ?>" title="<?php echo $i; ?> stars">
								<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
							</label>
						<?php endfor; ?>
					</div>
				</div>

				<div class="revora-form-field">
					<label for="revora-title"><?php _e( 'Review Title', 'revora' ); ?></label>
					<input type="text" id="revora-title" name="title" required>
				</div>

				<div class="revora-form-field">
					<label for="revora-content"><?php _e( 'Review Content', 'revora' ); ?></label>
					<textarea id="revora-content" name="content" rows="5" required minlength="25"></textarea>
				</div>

				<div class="revora-form-footer">
					<button type="submit" class="revora-submit-btn">
						<span class="btn-text"><?php _e( 'Submit Review', 'revora' ); ?></span>
						<span class="revora-spinner"></span>
					</button>
				</div>
				<div class="revora-form-message"></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Helper to render stars SVG
	 */
	public function render_stars( $rating ) {
		$rating = round( $rating );
		$output = '<div class="revora-stars">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$active = ( $i <= $rating ) ? 'active' : '';
			$output .= '<svg class="' . $active . '" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
		}
		$output .= '</div>';
		return $output;
	}

	/**
	 * Inject JSON-LD Schema
	 */
	public function inject_schema() {
		// Only inject if there's a shortcode on the page
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) || ( ! has_shortcode( $post->post_content, 'revora_reviews' ) && ! has_shortcode( $post->post_content, 'revora_form' ) ) ) {
			return;
		}

		// Extract category from shortcode if possible, otherwise use 'default'
		preg_match( '/category=["\']([^"\']+)["\']/', $post->post_content, $matches );
		$category = isset( $matches[1] ) ? $matches[1] : 'default';

		$db = new Revora_DB();
		$reviews = $db->get_reviews( array( 'category_slug' => $category, 'limit' => 5 ) );
		$stats = $db->get_stats( $category );

		if ( ! $stats || $stats->total == 0 ) {
			return;
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Product',
			'name'     => get_the_title(),
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => number_format( $stats->average, 1 ),
				'reviewCount' => $stats->total,
				'bestRating'  => '5',
				'worstRating' => '1',
			),
			'review' => array(),
		);

		foreach ( $reviews as $review ) {
			$schema['review'][] = array(
				'@type' => 'Review',
				'reviewRating' => array(
					'@type'       => 'Rating',
					'ratingValue' => $review->rating,
				),
				'author' => array(
					'@type' => 'Person',
					'name'  => $review->name,
				),
				'datePublished' => date( 'c', strtotime( $review->created_at ) ),
				'reviewBody'    => $review->content,
				'name'          => $review->title,
			);
		}

		echo '<script type="application/ld+json">' . json_encode( $schema ) . '</script>';
	}
}
