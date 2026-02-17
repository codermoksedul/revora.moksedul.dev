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
	 * [revora_reviews category="category-slug"]
	 */
	public function render_reviews_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'category' => '',
			'limit'    => 10,
		), $atts, 'revora_reviews' );

		$db = new Revora_DB();
		$reviews = $db->get_approved_reviews( $atts['category'], $atts['limit'] );
		$settings = wp_parse_args( get_option( 'revora_settings', array() ), array(
			'primary_color' => '#d64e11',
			'star_color'    => '#ffb400',
			'layout'        => 'list',
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
		<div class="revora-reviews-list layout-<?php echo esc_attr( $settings['layout'] ); ?>" 
			 style="--revora-primary: <?php echo esc_attr( $settings['primary_color'] ); ?>; --revora-star-filled: <?php echo esc_attr( $settings['star_color'] ); ?>;">
			
			<?php foreach ( $reviews as $review ) : ?>
				<div class="revora-review-item">
					<div class="revora-review-header">
						<span class="revora-review-author"><?php echo esc_html( $review->name ); ?></span>
						<?php if ( '1' === $settings['show_stars'] ) : ?>
							<div class="revora-review-rating">
								<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
									<span class="dashicons dashicons-star-filled <?php echo $i <= $review->rating ? 'filled' : 'empty'; ?>"></span>
								<?php endfor; ?>
							</div>
						<?php endif; ?>
						<span class="revora-review-date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ); ?></span>
					</div>
					<h4 class="revora-review-title"><?php echo esc_html( $review->title ); ?></h4>
					<div class="revora-review-content">
						<?php echo wpautop( esc_html( $review->content ) ); ?>
					</div>
				</div>
			<?php endforeach; ?>
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
	 * [revora_form]
	 */
	public function render_form_shortcode() {
		$db = new Revora_DB();
		$categories = $db->get_categories();
		$settings = get_option( 'revora_settings' );

		ob_start();
		?>
		<div class="revora-frontend-form" style="--revora-primary: <?php echo esc_attr( $settings['primary_color'] ); ?>;">
			<h3><?php _e( 'Submit a Review', 'revora' ); ?></h3>
			<form id="revora-review-form">
				<?php wp_nonce_field( 'revora_review_nonce', 'nonce' ); ?>
				
				<div class="revora-form-row">
					<div class="revora-form-group">
						<label for="revora_name"><?php _e( 'Your Name', 'revora' ); ?></label>
						<input type="text" name="name" id="revora_name" required>
					</div>
					<div class="revora-form-group">
						<label for="revora_email"><?php _e( 'Your Email', 'revora' ); ?></label>
						<input type="email" name="email" id="revora_email" required>
					</div>
				</div>

				<div class="revora-form-group">
					<label for="revora_cat"><?php _e( 'Category', 'revora' ); ?></label>
					<select name="category_slug" id="revora_cat" required>
						<option value=""><?php _e( 'Select Category', 'revora' ); ?></option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="revora-form-group">
					<label><?php _e( 'Rating', 'revora' ); ?></label>
					<div class="revora-star-rating" id="revora-frontend-rating">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<span class="dashicons dashicons-star-filled" data-rating="<?php echo $i; ?>"></span>
						<?php endfor; ?>
					</div>
					<input type="hidden" name="rating" id="revora_rating_val" value="5">
				</div>

				<div class="revora-form-group">
					<label for="revora_title"><?php _e( 'Review Title', 'revora' ); ?></label>
					<input type="text" name="title" id="revora_title" required>
				</div>

				<div class="revora-form-group">
					<label for="revora_content"><?php _e( 'Review Content', 'revora' ); ?></label>
					<textarea name="content" id="revora_content" rows="5" required></textarea>
				</div>

				<div id="revora-form-message"></div>
				
				<button type="submit" class="revora-submit-btn"><?php _e( 'Submit Review', 'revora' ); ?></button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
}
