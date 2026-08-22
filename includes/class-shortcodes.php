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
		add_action( 'wp_head', array( $this, 'maybe_inject_schema' ) );
	}

	/**
	 * Render Reviews Shortcode
	 * [revora_reviews category="category-slug" limit="6"]
	 */
	public function render_reviews_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'form_id'         => 0,
			'form'            => 0,
			'category'        => '',
			'limit'           => 6,
			'load_more_limit' => '',
			'columns'         => 3,
			'card_style'      => 'classic',
		), $atts, 'revora_reviews' );

		$form_id = ! empty( $atts['form_id'] ) ? intval( $atts['form_id'] ) : intval( $atts['form'] );

		$db = new Revora_DB();
		$reviews = $db->get_approved_reviews( $form_id, $atts['limit'] );
		$total_reviews = $db->get_total_approved_count( $form_id );
		$settings = wp_parse_args( get_option( 'revora_settings', array() ), array(
			'primary_color' => '#d64e11',
			'star_color'    => '#fbbf24',
			'layout'        => 'grid',
			'show_stars'    => '1',
			'enable_schema' => '1',
		) );

		if ( empty( $reviews ) ) {
			return '<p class="revora-no-reviews">' . esc_html__( 'No reviews yet.', 'revora' ) . '</p>';
		}

		ob_start();

		// Schema is now handled via maybe_inject_schema() hooked to wp_head
		?>
		<?php
		// Dynamic Styles for this shortcode instance
		$widget_id = uniqid( 'revora-' );
		$custom_css = "
			.{$widget_id} {
				--revora-primary: " . esc_attr( $settings['primary_color'] ) . ";
				--revora-star-filled: " . esc_attr( $settings['star_color'] ) . ";
			}
		";
		wp_add_inline_style( 'revora-frontend', $custom_css );
		?>
		<div class="revora-reviews-container <?php echo esc_attr( $widget_id ); ?>" 
			data-form-id="<?php echo esc_attr( $form_id ); ?>"
			data-category="<?php echo esc_attr( $atts['category'] ); ?>" 
			data-limit="<?php echo esc_attr( $atts['limit'] ); ?>"
			data-load-more-limit="<?php echo esc_attr( ! empty( $atts['load_more_limit'] ) ? $atts['load_more_limit'] : $atts['limit'] ); ?>"
			data-columns="<?php echo esc_attr( $atts['columns'] ); ?>"
			data-card-style="<?php echo esc_attr( $atts['card_style'] ); ?>">
			
			<div class="revora-reviews-grid revora-grid-cols-<?php echo esc_attr( $atts['columns'] ); ?>">
				
				<?php foreach ( $reviews as $review ) : 
					$db = new Revora_DB();
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
					<div class="revora-review-card style-<?php echo esc_attr( $atts['card_style'] ); ?>">
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
				<?php endforeach; ?>
			</div>
			
			<?php if ( count( $reviews ) < $total_reviews ) : ?>
				<div class="revora-load-more-container">
					<button class="revora-load-more-btn" data-page="1">
						<span class="btn-text"><?php esc_html_e( 'Load More Reviews', 'revora' ); ?></span>
						<span class="revora-spinner"></span>
					</button>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Maybe inject Schema.org JSON-LD in head
	 */
	public function maybe_inject_schema() {
		global $post;
		
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		$settings = get_option( 'revora_settings' );
		if ( empty( $settings['enable_schema'] ) || '1' !== $settings['enable_schema'] ) {
			return;
		}

		if ( has_shortcode( $post->post_content, 'revora_reviews' ) ) {
			// Extract category from shortcode
			preg_match( '/\[revora_reviews[^\]]*category=["\']([^"\']+)["\']/', $post->post_content, $matches );
			$category = isset( $matches[1] ) ? $matches[1] : '';
			
			$db = new Revora_DB();
			$reviews = $db->get_approved_reviews( $category, 6 ); // Last 6 for schema
			
			if ( ! empty( $reviews ) ) {
				$this->inject_schema( $reviews, $category );
			}
		}
	}

	/**
	 * Output the actual JSON-LD script
	 */
	private function inject_schema( $reviews, $category = 'All' ) {
		$total_rating = 0;
		$count = count( $reviews );
		foreach ( $reviews as $r ) {
			$total_rating += $r->rating;
		}
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
				'headline'      => $review->title,
				'reviewBody'    => $review->content,
				'datePublished' => $review->created_at,
			);
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>';

	}

	/**
	 * Render Form Shortcode
	 * [revora_form id="1" category=""]
	 */
	public function render_form_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id'       => 0,
			'category' => '',
		), $atts, 'revora_form' );

		ob_start();
		$db = new Revora_DB();
		
		$form_id = intval( $atts['id'] );
		$form = $form_id ? $db->get_form( $form_id ) : null;
		
		if ( $form_id && ! $form ) {
			return '<p>' . esc_html__( 'Form not found.', 'revora' ) . '</p>';
		}
		
		$fields = $form ? json_decode( $form->fields, true ) : $this->get_default_fields();
		$submit_btn_text = $settings['submit_text'] ?? __( 'Submit Review', 'revora' );
		if ( is_array( $fields ) ) {
			foreach ( $fields as $f ) {
				if ( 'submit' === ( $f['type'] ?? '' ) ) {
					if ( ! empty( $f['submitLabel'] ) ) {
						$submit_btn_text = $f['submitLabel'];
					} elseif ( ! empty( $f['label'] ) ) {
						$submit_btn_text = $f['label'];
					}
					break;
				}
			}
		}

		$plugin_settings = wp_parse_args( get_option( 'revora_settings', array() ), array(
			'primary_color' => '#4566f9',
			'star_color'    => '#fbbf24',
		) );

		// Dynamic Styles for this shortcode instance
		$widget_id = uniqid( 'revora-form-' );
		$custom_css = "
			.{$widget_id} {
				--revora-primary: " . esc_attr( $plugin_settings['primary_color'] ) . ";
				--revora-star-filled: " . esc_attr( $plugin_settings['star_color'] ) . ";
			}
			.{$widget_id} .revora-form-row {
				display: flex;
				flex-direction: row;
				flex-wrap: nowrap;
				gap: 16px;
				margin-bottom: 0;
				width: 100%;
				box-sizing: border-box;
			}
			.{$widget_id} .revora-form-col {
				flex: 1 1 0%;
				min-width: 0;
				width: 50%;
				box-sizing: border-box;
			}
			.{$widget_id} .revora-form-col .revora-form-field {
				width: 100%;
				margin-bottom: 16px;
			}
			@media (max-width: 767px) {
				.{$widget_id} .revora-form-row {
					flex-direction: column !important;
					flex-wrap: wrap !important;
					gap: 0 !important;
					width: 100% !important;
				}
				.{$widget_id} .revora-form-col {
					flex: 1 1 100% !important;
					width: 100% !important;
					max-width: 100% !important;
					min-width: 100% !important;
					display: block !important;
				}
			}
			.{$widget_id} .revora-custom-file-wrap {
				position: relative !important;
				width: 100% !important;
				height: 46px !important;
				box-sizing: border-box !important;
			}
			.{$widget_id} .revora-custom-file-wrap input[type=\"file\"] {
				position: absolute !important;
				top: 0 !important;
				left: 0 !important;
				width: 100% !important;
				height: 100% !important;
				opacity: 0 !important;
				cursor: pointer !important;
				z-index: 2 !important;
				margin: 0 !important;
				padding: 0 !important;
			}
			.{$widget_id} .revora-file-fake {
				position: absolute !important;
				top: 0 !important;
				left: 0 !important;
				width: 100% !important;
				height: 100% !important;
				display: flex !important;
				align-items: stretch !important;
				border: 1px solid #d1d5db;
				border-radius: 4px;
				background: #ffffff;
				box-sizing: border-box !important;
				z-index: 1 !important;
				overflow: hidden !important;
				padding: 0 !important;
			}
			.{$widget_id} .revora-custom-file-wrap input[type=\"file\"]:focus + .revora-file-fake {
				border-color: var(--revora-primary, #2563eb);
				box-shadow: 0 0 0 3.5px rgba(37, 99, 235, 0.12);
			}
			.{$widget_id} .revora-file-text {
				flex: 1 !important;
				padding: 0 16px !important;
				line-height: 44px !important;
				color: #1f2937;
				font-size: 15px;
				font-family: inherit;
				white-space: nowrap !important;
				overflow: hidden !important;
				text-overflow: ellipsis !important;
			}
			.{$widget_id} .revora-file-btn {
				background: #f3f4f6 !important;
				padding: 0 24px !important;
				line-height: 44px !important;
				border-left: 1px solid #d1d5db !important;
				color: #4b5563 !important;
				font-weight: 500 !important;
				font-size: 15px !important;
				font-family: inherit !important;
			}
			.{$widget_id} .revora-frontend-stars {
				display: flex !important;
				gap: 8px !important;
				align-items: center !important;
			}
			.{$widget_id} .revora-star-btn {
				color: #cbd5e1 !important;
				font-size: 24px !important;
				transition: all 0.2s ease !important;
				padding: 0 !important;
				width: 36px !important;
				height: 36px !important;
				border: 1.5px solid #e2e8f0 !important;
				border-radius: 6px !important;
				display: flex !important;
				align-items: center !important;
				justify-content: center !important;
				background-color: transparent !important;
			}
			.{$widget_id} .revora-star-btn.active,
			.{$widget_id} .revora-star-btn.hover-active {
				color: var(--revora-star-filled, #fbbf24) !important;
				border-color: var(--revora-star-filled, #fbbf24) !important;
			}
			#revora-success-modal-overlay .revora-modal-close-btn {
				display: inline-block !important;
				width: 100% !important;
				background: var(--revora-primary, #2563eb) !important;
				color: #ffffff !important;
				border: none !important;
				border-radius: 10px !important;
				padding: 14px 24px !important;
				font-size: 16px !important;
				font-weight: 600 !important;
				cursor: pointer !important;
				box-sizing: border-box !important;
				box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25) !important;
			}
		";
		echo '<style type="text/css">' . $custom_css . '</style>';
		?>
		<div class="revora-form-container <?php echo esc_attr( $widget_id ); ?>">
			<form id="revora-review-form" class="revora-form" enctype="multipart/form-data">
				<?php wp_nonce_field( 'revora_submit_nonce', 'nonce' ); ?>
				
				<?php 
				$category_slug = ! empty( $atts['category'] ) ? $atts['category'] : 'unknown';
				?>
				<input type="hidden" name="category_slug" value="<?php echo esc_attr( $category_slug ); ?>">
				<?php if ( $form ) : ?>
					<input type="hidden" name="form_id" value="<?php echo esc_attr( $form->id ); ?>">
				<?php endif; ?>
				
				<div class="revora-form-fields-wrap">
					<?php 
					$this->render_frontend_fields( $fields, $widget_id );
					?>
				</div>

				<div id="revora-form-message" class="revora-form-message"></div>
				
				<div class="revora-form-footer">
					<button type="submit" class="revora-submit-btn">
						<span class="btn-text"><?php echo esc_html( $submit_btn_text ); ?></span>
						<span class="revora-spinner"></span>
					</button>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
	
	private function render_frontend_fields( $fields, $widget_id ) {
		if ( ! is_array( $fields ) ) return;
		
		foreach ( $fields as $field ) {
			if ( 'submit' === ( $field['type'] ?? '' ) ) {
				continue;
			}

			if ( 'row' === $field['type'] ) {
				echo '<div class="revora-form-row">';
				if ( ! empty( $field['columns'] ) && is_array( $field['columns'] ) ) {
					foreach ( $field['columns'] as $col_fields ) {
						echo '<div class="revora-form-col">';
						$this->render_frontend_fields( $col_fields, $widget_id );
						echo '</div>';
					}
				}
				echo '</div>';
			} else {
				$required_attr = ! empty( $field['required'] ) ? 'required' : ''; 
				$name = esc_attr( $field['key'] ?? '' );
				$label = esc_html( $field['label'] ?? '' );
				$placeholder = esc_attr( $field['placeholder'] ?? '' );
				
				// Auto-fill for logged-in users
				$value = '';
				if ( is_user_logged_in() ) {
					$current_user = wp_get_current_user();
					if ( 'name' === $name || 'author' === $name ) {
						$value = $current_user->display_name;
					} elseif ( 'email' === $name ) {
						$value = $current_user->user_email;
					} elseif ( 'phone' === $name || 'number' === $name ) {
						// Check common meta keys for phone numbers (WooCommerce, LearnPress, etc.)
						$phone_keys = array( 'phone', 'billing_phone', '_billing_phone', '_lp_billing_phone', 'lp_billing_phone' );
						foreach ( $phone_keys as $meta_key ) {
							$value = get_user_meta( $current_user->ID, $meta_key, true );
							if ( ! empty( $value ) ) {
								break;
							}
						}
					}
				}
				?>
				<div class="revora-form-field">
					<label class="revora-field-label"><?php echo $label; ?> <?php if ( $required_attr ) echo '<span class="revora-req-star" style="color:#ef4444 !important; margin-left:3px; font-weight:700; font-size:18px; line-height:1; vertical-align: middle; display: inline-block; position: relative; top: 2px;">*</span>'; ?></label>
					
					<?php if ( 'textarea' === $field['type'] ) : ?>
						<textarea name="<?php echo $name; ?>" rows="5" placeholder="<?php echo $placeholder; ?>" <?php echo $required_attr; ?>></textarea>
						
					<?php elseif ( 'rating' === $field['type'] ) : ?>
						<div class="revora-frontend-rating-picker" data-rating="0" style="cursor:pointer !important;">
							<div class="revora-frontend-stars" style="cursor:pointer !important;">
								<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
									<span class="material-symbols-outlined revora-star-btn" data-rating="<?php echo esc_attr( $i ); ?>" style="cursor:pointer !important;">star</span>
								<?php endfor; ?>
							</div>
							<input type="hidden" name="<?php echo $name; ?>" class="revora-frontend-rating-val" value="0" <?php echo $required_attr; ?> />
						</div>
						
					<?php elseif ( 'file' === $field['type'] || 'avatar' === $field['type'] ) : 
						$allowed_raw = ! empty( $field['allowed_types'] ) ? $field['allowed_types'] : ( 'avatar' === $field['type'] ? 'jpg, jpeg, png, webp' : '' );
						$accept_attr = '';
						if ( ! empty( $allowed_raw ) ) {
							$types = array_map( 'trim', explode( ',', $allowed_raw ) );
							$exts = array();
							foreach ( $types as $t ) {
								if ( empty( $t ) ) continue;
								$exts[] = ( strpos( $t, '.' ) === 0 || strpos( $t, '/' ) !== false ) ? $t : '.' . $t;
							}
							if ( ! empty( $exts ) ) {
								$accept_attr = 'accept="' . esc_attr( implode( ',', $exts ) ) . '"';
							}
						}
					?>
						<div class="revora-custom-file-wrap">
							<input type="file" name="<?php echo $name; ?>" <?php echo $accept_attr; ?> <?php echo $required_attr; ?> class="revora-file-input">
							<div class="revora-file-fake">
								<span class="revora-file-text"><?php echo $placeholder ? $placeholder : esc_html__( 'Choose file...', 'revora' ); ?></span>
								<span class="revora-file-btn"><?php esc_html_e( 'Browse', 'revora' ); ?></span>
							</div>
						</div>
						
					<?php elseif ( 'select' === $field['type'] ) : ?>
						<select name="<?php echo $name; ?>" <?php echo $required_attr; ?>>
							<option value=""><?php echo $placeholder ? $placeholder : esc_html__( 'Select...', 'revora' ); ?></option>
							<?php 
							$options = ! empty( $field['options'] ) ? explode( "\n", $field['options'] ) : array();
							foreach ( $options as $opt ) {
								$opt = trim( $opt );
								if ( empty( $opt ) ) continue;
								echo '<option value="' . esc_attr( $opt ) . '">' . esc_html( $opt ) . '</option>';
							}
							?>
						</select>
						
					<?php elseif ( 'radio' === $field['type'] ) : ?>
						<div class="revora-radio-group" style="display:flex; flex-direction:column; gap:5px;">
							<?php 
							$options = ! empty( $field['options'] ) ? explode( "\n", $field['options'] ) : array();
							foreach ( $options as $opt ) {
								$opt = trim( $opt );
								if ( empty( $opt ) ) continue;
								echo '<label><input type="radio" name="' . $name . '" value="' . esc_attr( $opt ) . '" ' . $required_attr . '> ' . esc_html( $opt ) . '</label>';
							}
							?>
						</div>
						
					<?php elseif ( 'checkbox' === $field['type'] ) : ?>
						<div class="revora-checkbox-group" style="display:flex; flex-direction:column; gap:5px;">
							<?php 
							$options = ! empty( $field['options'] ) ? explode( "\n", $field['options'] ) : array();
							foreach ( $options as $opt ) {
								$opt = trim( $opt );
								if ( empty( $opt ) ) continue;
								echo '<label><input type="checkbox" name="' . $name . '[]" value="' . esc_attr( $opt ) . '"> ' . esc_html( $opt ) . '</label>';
							}
							?>
						</div>
						
					<?php else : // text, email, number, url, date, etc. ?>
						<input type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo $name; ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo $placeholder; ?>" <?php echo $required_attr; ?>>
					<?php endif; ?>
				</div>
				<?php
			}
		}
	}

	private function get_default_fields() {
		return array(
			array(
				'type'    => 'row',
				'columns' => array(
					array( array( 'type' => 'text', 'label' => __( 'Your Name', 'revora' ), 'key' => 'name', 'required' => true, 'placeholder' => __( 'John Doe', 'revora' ) ) ),
					array( array( 'type' => 'email', 'label' => __( 'Your Email', 'revora' ), 'key' => 'email', 'required' => true, 'placeholder' => __( 'john@example.com', 'revora' ) ) ),
				),
			),
			array( 'type' => 'tel', 'label' => __( 'Phone Number', 'revora' ), 'key' => 'phone', 'required' => false, 'placeholder' => __( '+1 (555) 000-0000', 'revora' ) ),
			array( 'type' => 'rating', 'label' => __( 'Rating', 'revora' ), 'key' => 'rating', 'required' => true ),
			array( 'type' => 'text', 'label' => __( 'Review Title', 'revora' ), 'key' => 'title', 'required' => true, 'placeholder' => __( 'Summarize your experience', 'revora' ) ),
			array( 'type' => 'textarea', 'label' => __( 'Review Content', 'revora' ), 'key' => 'content', 'required' => true, 'placeholder' => __( 'Tell us more about your experience...', 'revora' ) ),
		);
	}
}
