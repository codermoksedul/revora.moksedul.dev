<?php
/**
 * Shortcodes Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Revora_Shortcodes {

	public function __construct() {
		add_shortcode( 'revora_reviews', array( $this, 'render_reviews_shortcode' ) );
		add_shortcode( 'revora_form', array( $this, 'render_form_shortcode' ) );
	}

	/**
	 * Render Reviews Shortcode
	 * [revora_reviews category="category-slug" limit="6"]
	 */
	public function render_reviews_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'category'   => '',
			'limit'      => 6,
			'columns'    => 3,
			'card_style' => 'classic',
		), $atts, 'revora_reviews' );

		$db = new Revora_DB();
		$reviews = $db->get_approved_reviews( $atts['category'], $atts['limit'] );
		$total_reviews = $db->get_total_approved_count( $atts['category'] );
		$settings = wp_parse_args( get_option( 'revora_settings', array() ), array(
			'primary_color' => '#d64e11',
			'star_color'    => '#fbbf24',
			'layout'        => 'grid',
			'show_stars'    => '1',
			'enable_schema' => '1',
		) );

		if ( empty( $reviews ) ) {
			return '<p class="revora-no-reviews">' . __( 'No reviews yet.', 'revora' ) . '</p>';
		}

		ob_start();

		// Inject Schema.org SEO Markup
		if ( '1' === $settings['enable_schema'] ) {
			$this->inject_schema( $reviews, $atts['category'] );
		}
		?>
		<div class="revora-reviews-container" 
			data-category="<?php echo esc_attr( $atts['category'] ); ?>" 
			data-limit="<?php echo esc_attr( $atts['limit'] ); ?>"
			data-columns="<?php echo esc_attr( $atts['columns'] ); ?>"
			data-card-style="<?php echo esc_attr( $atts['card_style'] ); ?>">
			
			<div class="revora-reviews-grid revora-grid-cols-<?php echo esc_attr( $atts['columns'] ); ?>" 
				style="--revora-primary: <?php echo esc_attr( $settings['primary_color'] ); ?>; --revora-star-filled: <?php echo esc_attr( $settings['star_color'] ); ?>;">
				
				<?php foreach ( $reviews as $review ) : ?>
					<div class="revora-review-card style-<?php echo esc_attr( $atts['card_style'] ); ?>">
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
				<?php endforeach; ?>
			</div>
			
			<?php if ( count( $reviews ) < $total_reviews ) : ?>
				<div class="revora-load-more-container">
					<button class="revora-load-more-btn" data-page="1">
						<span class="btn-text"><?php _e( 'Load More Reviews', 'revora' ); ?></span>
						<span class="revora-spinner"></span>
					</button>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Inject Schema.org JSON-LD
	 */
	private function inject_schema( $reviews, $category = 'All' ) {
		$total_rating = 0;
		$count = count( $reviews );
		foreach ( $reviews as $r ) $total_rating += $r->rating;
		$avg = $count > 0 ? round( $total_rating / $count, 1 ) : 0;

		$schema = array(
			'@context' => 'https://schema.org/',
			'@type'    => 'Product',
			'name'     => ! empty( $category ) ? $category : __( 'Service/Product Reviews', 'revora' ),
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $avg,
				'reviewCount' => $count,
				'bestRating'  => '5',
				'worstRating' => '1',
			),
			'review' => array(),
		);

		foreach ( $reviews as $review ) {
			$schema['review'][] = array(
				'@type'  => 'Review',
				'reviewRating' => array(
					'@type'       => 'Rating',
					'ratingValue' => $review->rating,
				),
				'author' => array(
					'@type' => 'Person',
					'name'  => $review->name,
				),
				'headline'     => $review->title,
				'reviewBody'   => $review->content,
				'datePublished' => $review->created_at,
			);
		}

		echo '<script type="application/ld+json">' . json_encode( $schema ) . '</script>';
	}

	/**
	 * Render Form Shortcode
	 * [revora_form category=""]
	 */
	public function render_form_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'category' => '',
		), $atts, 'revora_form' );

		$db = new Revora_DB();
		$categories = $db->get_categories();
		$settings = get_option( 'revora_settings' );
		$has_category = ! empty( $atts['category'] );

		ob_start();
		?>
		<div class="revora-form-container" style="--revora-primary: <?php echo esc_attr( $settings['primary_color'] ); ?>;">
			<h3><?php _e( 'Submit a Review', 'revora' ); ?></h3>
			<form id="revora-review-form" class="revora-form">
				<?php wp_nonce_field( 'revora_submit_nonce', 'nonce' ); ?>
				
				<?php 
				$category_slug = ! empty( $atts['category'] ) ? $atts['category'] : 'unknown';
				?>
				<input type="hidden" name="category_slug" value="<?php echo esc_attr( $category_slug ); ?>">
				
				<div class="revora-form-row">
					<div class="revora-form-field">
						<label for="revora_name"><?php _e( 'Your Name', 'revora' ); ?></label>
						<input type="text" name="name" id="revora_name" placeholder="<?php esc_attr_e( 'John Doe', 'revora' ); ?>" required>
					</div>
					<div class="revora-form-field">
						<label for="revora_email"><?php _e( 'Your Email', 'revora' ); ?></label>
						<input type="email" name="email" id="revora_email" placeholder="<?php esc_attr_e( 'john@example.com', 'revora' ); ?>" required>
					</div>
				</div>

				<div class="revora-form-field">
					<label><?php _e( 'Rating', 'revora' ); ?></label>
					<div class="revora-rating-input">
						<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
							<input type="radio" id="star-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php checked( $i, 5 ); ?> required />
							<label for="star-<?php echo $i; ?>" title="<?php echo $i; ?> stars">
								<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
							</label>
						<?php endfor; ?>
					</div>
				</div>

				<div class="revora-form-field">
					<label for="revora_title"><?php _e( 'Review Title', 'revora' ); ?></label>
					<input type="text" name="title" id="revora_title" placeholder="<?php esc_attr_e( 'Summarize your experience', 'revora' ); ?>" required>
				</div>

				<div class="revora-form-field">
					<label for="revora_content"><?php _e( 'Review Content', 'revora' ); ?></label>
					<textarea name="content" id="revora_content" rows="5" placeholder="<?php esc_attr_e( 'Share your detailed experience... (minimum 25 characters)', 'revora' ); ?>" required minlength="25"></textarea>
				</div>

				<div id="revora-form-message" class="revora-form-message"></div>
				
				<div class="revora-form-footer">
					<button type="submit" class="revora-submit-btn">
						<span class="btn-text"><?php _e( 'Submit Review', 'revora' ); ?></span>
						<span class="revora-spinner"></span>
					</button>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
}
