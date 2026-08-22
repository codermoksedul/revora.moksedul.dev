<?php
/**
 * Revora Featured Review Widget for Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
class Revora_Featured_Review_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'revora_featured_review';
	}

	public function get_title() {
		return __( 'Featured Review', 'revora' );
	}

	public function get_icon() {
		return 'eicon-star-o';
	}

	public function get_categories() {
		return array( 'revora' );
	}

	public function get_keywords() {
		return array( 'featured', 'review', 'top', '5 star', 'testimonial', 'quote', 'showcase', 'revora' );
	}

	public function get_script_depends() {
		return array( 'swiper' );
	}

	public function get_style_depends() {
		return array( 'swiper' );
	}

	protected function register_controls() {
		// Content Tab - Query & Filtering
		$this->start_controls_section(
			'section_query',
			array(
				'label' => __( 'Query & Filter', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$db = new Revora_DB();
		$forms = $db->get_forms();
		$form_options = array( '0' => __( 'All Forms', 'revora' ) );
		foreach ( $forms as $form ) {
			$form_options[ $form->id ] = $form->name;
		}

		$this->add_control(
			'form_id',
			array(
				'label'   => __( 'Select Form', 'revora' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $form_options,
				'default' => '0',
			)
		);

		$this->add_control(
			'min_rating',
			array(
				'label'   => __( 'Rating Filter', 'revora' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'5'   => __( '5 Stars Only (Top Rated)', 'revora' ),
					'4.5' => __( '4.5 Stars & Above', 'revora' ),
					'4.0' => __( '4.0 Stars & Above', 'revora' ),
					'0'   => __( 'All Ratings', 'revora' ),
				),
				'default' => '5',
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'       => __( 'Reviews Limit (Count)', 'revora' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 5,
				'min'         => 1,
				'max'         => 50,
				'description' => __( 'Set how many top reviews to load in the loop/slider.', 'revora' ),
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => __( 'Order By', 'revora' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'created_at' => __( 'Latest First', 'revora' ),
					'rating'     => __( 'Highest Rating', 'revora' ),
					'rand'       => __( 'Random', 'revora' ),
				),
				'default' => 'created_at',
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => __( 'Sort Order', 'revora' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'DESC' => __( 'Descending', 'revora' ),
					'ASC'  => __( 'Ascending', 'revora' ),
				),
				'default' => 'DESC',
			)
		);

		$this->end_controls_section();

		// Content Tab - Card Elements
		$this->start_controls_section(
			'section_elements',
			array(
				'label' => __( 'Card Elements', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_badge',
			array(
				'label'        => __( 'Show Badge', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'badge_text',
			array(
				'label'     => __( 'Badge Text', 'revora' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'FEATURED REVIEW', 'revora' ),
				'condition' => array(
					'show_badge' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_watermark_quote',
			array(
				'label'        => __( 'Show Background Quote Mark', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_avatar',
			array(
				'label'        => __( 'Show Avatar / Initials', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_author',
			array(
				'label'        => __( 'Show Author Name', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_subtitle',
			array(
				'label'        => __( 'Show Role / Subtitle', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'default_subtitle',
			array(
				'label'     => __( 'Default Subtitle / Role', 'revora' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Verified Student', 'revora' ),
				'condition' => array(
					'show_subtitle' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_stars',
			array(
				'label'        => __( 'Show Star Rating', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// Content Tab - Slider / Loop Settings
		$this->start_controls_section(
			'section_slider',
			array(
				'label' => __( 'Loop / Slider Settings', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => __( 'Autoplay', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'revora' ),
				'label_off'    => __( 'No', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'autoplay_speed',
			array(
				'label'     => __( 'Autoplay Delay (ms)', 'revora' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 5000,
				'step'      => 500,
				'condition' => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'pause_on_hover',
			array(
				'label'        => __( 'Pause on Hover', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'revora' ),
				'label_off'    => __( 'No', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'        => __( 'Infinite Loop', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'revora' ),
				'label_off'    => __( 'No', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'effect',
			array(
				'label'   => __( 'Transition Effect', 'revora' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'fade'  => __( 'Fade', 'revora' ),
					'slide' => __( 'Slide', 'revora' ),
				),
				'default' => 'fade',
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'   => __( 'Transition Duration (ms)', 'revora' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 600,
				'step'    => 100,
			)
		);

		$this->add_control(
			'show_arrows',
			array(
				'label'        => __( 'Show Navigation Arrows', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->end_controls_section();

		// Style Tab - Card Container
		$this->start_controls_section(
			'style_card',
			array(
				'label' => __( 'Card Container', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => 'card_background',
				'label'    => __( 'Background', 'revora' ),
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .revora-featured-card',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .revora-featured-card',
			)
		);

		$this->add_responsive_control(
			'card_border_radius',
			array(
				'label'      => __( 'Border Radius', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-featured-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .revora-featured-card',
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => __( 'Padding', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-featured-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'card_min_height',
			array(
				'label'      => __( 'Min Height', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min' => 200,
						'max' => 800,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-featured-card' => 'min-height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Badge
		$this->start_controls_section(
			'style_badge_section',
			array(
				'label'     => __( 'Badge', 'revora' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_badge' => 'yes',
				),
			)
		);

		$this->add_control(
			'badge_dot_color',
			array(
				'label'     => __( 'Dot Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-featured-badge-dot' => 'background-color: {{VALUE}} !important; box-shadow: 0 0 8px {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_text_color',
			array(
				'label'     => __( 'Text Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-featured-badge-text' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'badge_bg_color',
			array(
				'label'     => __( 'Badge Background', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-featured-badge-wrap' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'badge_typography',
				'selector' => '{{WRAPPER}} .revora-featured-badge-text',
			)
		);

		$this->add_responsive_control(
			'badge_margin_bottom',
			array(
				'label'      => __( 'Bottom Spacing', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-featured-badge-wrap' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Watermark Quote
		$this->start_controls_section(
			'style_watermark_section',
			array(
				'label'     => __( 'Watermark Quote', 'revora' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_watermark_quote' => 'yes',
				),
			)
		);

		$this->add_control(
			'watermark_color',
			array(
				'label'     => __( 'Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-featured-watermark-quote' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'watermark_size',
			array(
				'label'      => __( 'Font Size', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 60,
						'max' => 300,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-featured-watermark-quote' => 'font-size: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'watermark_opacity',
			array(
				'label'     => __( 'Opacity', 'revora' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 1,
						'step' => 0.05,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .revora-featured-watermark-quote' => 'opacity: {{SIZE}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Review Quote / Content
		$this->start_controls_section(
			'style_content_section',
			array(
				'label' => __( 'Review Quote / Text', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'content_color',
			array(
				'label'     => __( 'Text Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-featured-quote' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'content_typography',
				'selector' => '{{WRAPPER}} .revora-featured-quote',
			)
		);

		$this->add_responsive_control(
			'content_margin_bottom',
			array(
				'label'      => __( 'Bottom Spacing', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-featured-content-wrap' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Author & Subtitle
		$this->start_controls_section(
			'style_author_section',
			array(
				'label' => __( 'Author & Avatar', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'avatar_size',
			array(
				'label'      => __( 'Avatar Size', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 30,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-featured-avatar' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important; min-width: {{SIZE}}{{UNIT}} !important; font-size: calc({{SIZE}}{{UNIT}} * 0.38) !important;',
				),
			)
		);

		$this->add_control(
			'avatar_bg_color',
			array(
				'label'     => __( 'Initials Avatar Background', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-featured-avatar-initials' => 'background: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'avatar_text_color',
			array(
				'label'     => __( 'Initials Text Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-featured-avatar-initials' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'author_name_heading',
			array(
				'label'     => __( 'Author Name', 'revora' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'author_name_color',
			array(
				'label'     => __( 'Name Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-featured-author-name' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'author_name_typography',
				'selector' => '{{WRAPPER}} .revora-featured-author-name',
			)
		);

		$this->add_control(
			'author_subtitle_heading',
			array(
				'label'     => __( 'Subtitle / Role', 'revora' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'author_subtitle_color',
			array(
				'label'     => __( 'Subtitle Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-featured-author-subtitle' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'author_subtitle_typography',
				'selector' => '{{WRAPPER}} .revora-featured-author-subtitle',
			)
		);

		$this->end_controls_section();

		// Style Tab - Star Rating
		$this->start_controls_section(
			'style_stars_section',
			array(
				'label'     => __( 'Star Rating', 'revora' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_stars' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'star_size',
			array(
				'label'      => __( 'Star Size', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 12,
						'max' => 48,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-featured-stars .material-symbols-outlined' => 'font-size: {{SIZE}}{{UNIT}} !important; line-height: 1 !important;',
				),
			)
		);

		$this->add_control(
			'star_color',
			array(
				'label'     => __( 'Star Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-featured-stars .material-symbols-outlined' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'star_gap',
			array(
				'label'      => __( 'Star Gap', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 20,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-featured-stars' => 'gap: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Slider Controls (Arrows & Pagination)
		$this->start_controls_section(
			'style_slider_controls',
			array(
				'label' => __( 'Slider Navigation & Dots', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => __( 'Arrow Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .swiper-button-next, {{WRAPPER}} .swiper-button-prev' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'arrow_bg_color',
			array(
				'label'     => __( 'Arrow Background', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .swiper-button-next, {{WRAPPER}} .swiper-button-prev' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$form_id    = ! empty( $settings['form_id'] ) ? intval( $settings['form_id'] ) : 0;
		$min_rating = isset( $settings['min_rating'] ) ? floatval( $settings['min_rating'] ) : 5.0;
		$limit      = ! empty( $settings['limit'] ) ? intval( $settings['limit'] ) : 5;
		$orderby    = ! empty( $settings['orderby'] ) ? sanitize_text_field( $settings['orderby'] ) : 'created_at';
		$order      = ! empty( $settings['order'] ) ? sanitize_text_field( $settings['order'] ) : 'DESC';

		wp_enqueue_style( 'revora-google-material-symbols' );
		wp_enqueue_style( 'revora-frontend' );
		wp_enqueue_style( 'swiper' );
		wp_enqueue_script( 'swiper' );
		wp_enqueue_script( 'revora-frontend' );

		$db = new Revora_DB();
		$query_args = array(
			'form_id'    => $form_id,
			'status'     => 'approved',
			'limit'      => $limit,
			'offset'     => 0,
			'orderby'    => $orderby,
			'order'      => $order,
		);

		if ( $min_rating > 0 ) {
			$query_args['min_rating'] = $min_rating;
		}

		$reviews = $db->get_reviews( $query_args );

		// Fallback if no reviews match strict min_rating
		if ( empty( $reviews ) && $min_rating > 0 ) {
			unset( $query_args['min_rating'] );
			$reviews = $db->get_reviews( $query_args );
		}

		// Fallback dummy preview for empty state in editor
		if ( empty( $reviews ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				$dummy_review = (object) array(
					'id'         => 0,
					'name'       => 'Ahmed Rahman',
					'rating'     => 5.0,
					'content'    => 'The biggest difference for me was the clarity. Things that seemed complicated before suddenly started making sense. The entire learning experience felt practical and genuinely useful.',
					'created_at' => current_time( 'mysql' ),
				);
				$reviews = array( $dummy_review );
			} else {
				echo '<p class="revora-no-reviews">' . esc_html__( 'No featured reviews found.', 'revora' ) . '</p>';
				return;
			}
		}

		$widget_id = $this->get_id();
		$is_multiple = count( $reviews ) > 1;

		$slider_settings = array(
			'slidesPerView'  => 1,
			'spaceBetween'   => 20,
			'autoplay'       => 'yes' === $settings['autoplay'],
			'autoplaySpeed'  => ! empty( $settings['autoplay_speed'] ) ? intval( $settings['autoplay_speed'] ) : 5000,
			'pauseOnHover'   => 'yes' === $settings['pause_on_hover'],
			'loop'           => 'yes' === $settings['loop'] && $is_multiple,
			'speed'          => ! empty( $settings['speed'] ) ? intval( $settings['speed'] ) : 600,
			'effect'         => $settings['effect'] ?? 'fade',
			'showArrows'     => 'yes' === $settings['show_arrows'] && $is_multiple,
			'showPagination' => false,
		);
		?>

		<style>
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> {
			position: relative;
			width: 100%;
			overflow: hidden;
			border-radius: 28px;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .swiper-slide {
			height: auto;
			display: flex;
			box-sizing: border-box;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-card {
			position: relative;
			width: 100%;
			background: radial-gradient(circle at 85% 20%, #152449 0%, #0b1328 60%, #070d1e 100%);
			border-radius: 28px;
			padding: 48px 52px;
			color: #ffffff;
			box-sizing: border-box;
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			overflow: hidden;
			box-shadow: 0 20px 45px -15px rgba(2, 6, 23, 0.5);
			border: 1px solid rgba(255, 255, 255, 0.06);
			transition: transform 0.3s ease, box-shadow 0.3s ease;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-watermark-quote {
			position: absolute;
			top: -15px;
			left: 36px;
			font-size: 140px;
			font-family: Georgia, serif;
			line-height: 1;
			color: #1e3a8a;
			opacity: 0.22;
			pointer-events: none;
			user-select: none;
			z-index: 1;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-badge-wrap {
			position: relative;
			z-index: 2;
			display: inline-flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 24px;
			padding: 0;
			width: fit-content;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-badge-dot {
			width: 7px;
			height: 7px;
			border-radius: 50%;
			background-color: #3b82f6;
			box-shadow: 0 0 10px #3b82f6;
			display: inline-block;
			flex-shrink: 0;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-badge-text {
			font-size: 11.5px;
			font-weight: 700;
			letter-spacing: 0.12em;
			text-transform: uppercase;
			color: #94a3b8;
			line-height: 1;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-content-wrap {
			position: relative;
			z-index: 2;
			margin-bottom: 36px;
			flex-grow: 1;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-quote {
			margin: 0;
			padding: 0;
			border: none;
			background: none;
			font-size: 26px;
			font-weight: 600;
			line-height: 1.48;
			color: #f8fafc;
			letter-spacing: -0.015em;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-footer {
			position: relative;
			z-index: 2;
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 16px;
			margin-top: auto;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-author-info {
			display: flex;
			align-items: center;
			gap: 14px;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-avatar {
			width: 44px;
			height: 44px;
			min-width: 44px;
			border-radius: 50%;
			overflow: hidden;
			flex-shrink: 0;
			background: #1e293b;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-avatar img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-avatar-initials {
			width: 100%;
			height: 100%;
			display: flex;
			align-items: center;
			justify-content: center;
			background: #1e293b;
			color: #93c5fd;
			font-weight: 700;
			font-size: 16px;
			text-transform: uppercase;
			user-select: none;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-author-meta {
			display: flex;
			flex-direction: column;
			gap: 3px;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-author-name {
			font-size: 16px;
			font-weight: 700;
			color: #ffffff;
			line-height: 1.2;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-author-subtitle {
			font-size: 13px;
			font-weight: 400;
			color: #94a3b8;
			line-height: 1.2;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-stars {
			display: flex;
			align-items: center;
			gap: 4px;
			color: #fbbf24;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-stars .material-symbols-outlined {
			font-size: 20px;
			line-height: 1;
			color: #fbbf24;
			display: inline-block;
		}
		.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-stars .material-symbols-outlined.fill-1 {
			font-variation-settings: 'FILL' 1;
		}
		@media (max-width: 767px) {
			.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-card {
				padding: 32px 24px;
				border-radius: 20px;
			}
			.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-quote {
				font-size: 19px;
				line-height: 1.42;
			}
			.revora-featured-slider-<?php echo esc_attr( $widget_id ); ?> .revora-featured-watermark-quote {
				font-size: 100px;
				left: 20px;
				top: -10px;
			}
		}
		</style>

		<div class="revora-featured-widget-wrap" data-slider-settings='<?php echo wp_json_encode( $slider_settings ); ?>'>
			<div class="swiper revora-featured-slider revora-featured-slider-<?php echo esc_attr( $widget_id ); ?>">
				<div class="swiper-wrapper">
					<?php foreach ( $reviews as $review ) : 
						$avatar_url = ! empty( $review->id ) ? $db->get_review_meta( $review->id, 'avatar_url' ) : '';
						if ( empty( $avatar_url ) && ! empty( $review->id ) ) {
							$avatar_url = $db->get_review_meta( $review->id, 'avatar' );
						}
						
						// Name initials calculation
						$name_parts = explode( ' ', trim( $review->name ) );
						$initials   = '';
						if ( ! empty( $name_parts[0] ) ) {
							$initials .= mb_strtoupper( mb_substr( $name_parts[0], 0, 1 ) );
						}
						if ( isset( $name_parts[1] ) && ! empty( $name_parts[1] ) ) {
							$initials .= mb_strtoupper( mb_substr( $name_parts[1], 0, 1 ) );
						}
						if ( empty( $initials ) ) {
							$initials = 'AR';
						}

						$masked_contact = '';
						$meta = ! empty( $review->id ) ? $db->get_review_meta( $review->id ) : array();
						if ( ! empty( $meta ) && is_array( $meta ) ) {
							foreach ( $meta as $k => $v ) {
								$k_lower = strtolower( $k );
								if ( ( false !== strpos( $k_lower, 'phone' ) || 'tel' === $k_lower || false !== strpos( $k_lower, 'contact' ) || false !== strpos( $k_lower, 'mobile' ) || 'number' === $k_lower ) && ! empty( $v ) && is_string( $v ) ) {
									$masked_contact = trim( $v );
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
								$contact_masked = mb_substr( $masked_contact, 0, 1 ) . '******' . mb_substr( $masked_contact, -1 );
							} else {
								$contact_masked = mb_substr( $masked_contact, 0, 3 ) . '******' . mb_substr( $masked_contact, -3 );
							}
						} else {
							$contact_masked = esc_html__( 'Verified Customer', 'revora' );
						}
						$subtitle = ! empty( $settings['default_subtitle'] ) && __( 'Verified Student', 'revora' ) !== $settings['default_subtitle'] ? $settings['default_subtitle'] : $contact_masked;
					?>
						<div class="swiper-slide">
							<div class="revora-featured-card">
								<?php if ( 'yes' === $settings['show_watermark_quote'] ) : ?>
									<div class="revora-featured-watermark-quote">“</div>
								<?php endif; ?>

								<?php if ( 'yes' === $settings['show_badge'] ) : ?>
									<div class="revora-featured-badge-wrap">
										<span class="revora-featured-badge-dot"></span>
										<span class="revora-featured-badge-text"><?php echo esc_html( $settings['badge_text'] ); ?></span>
									</div>
								<?php endif; ?>

								<div class="revora-featured-content-wrap">
									<blockquote class="revora-featured-quote">
										&ldquo;<?php echo esc_html( trim( $review->content ) ); ?>&rdquo;
									</blockquote>
								</div>

								<div class="revora-featured-footer">
									<div class="revora-featured-author-info">
										<?php if ( 'yes' === $settings['show_avatar'] ) : ?>
											<div class="revora-featured-avatar">
												<?php if ( ! empty( $avatar_url ) ) : ?>
													<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $review->name ); ?>" />
												<?php else : ?>
													<div class="revora-featured-avatar-initials"><?php echo esc_html( $initials ); ?></div>
												<?php endif; ?>
											</div>
										<?php endif; ?>

										<div class="revora-featured-author-meta">
											<?php if ( 'yes' === $settings['show_author'] ) : ?>
												<div class="revora-featured-author-name"><?php echo esc_html( $review->name ); ?></div>
											<?php endif; ?>
											<?php if ( 'yes' === $settings['show_subtitle'] && ! empty( $subtitle ) ) : ?>
												<div class="revora-featured-author-subtitle"><?php echo esc_html( $subtitle ); ?></div>
											<?php endif; ?>
										</div>
									</div>

									<?php if ( 'yes' === $settings['show_stars'] ) : 
										$rating_val = floatval( $review->rating );
									?>
										<div class="revora-featured-stars">
											<?php for ( $i = 1; $i <= 5; $i++ ) : 
												$star_icon = 'star';
												if ( $rating_val >= $i ) {
													$star_icon = 'star';
												} elseif ( $rating_val >= ( $i - 0.5 ) ) {
													$star_icon = 'star_half';
												}
											?>
												<span class="material-symbols-outlined fill-1"><?php echo esc_html( $star_icon ); ?></span>
											<?php endfor; ?>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( 'yes' === $settings['show_arrows'] && $is_multiple ) : ?>
					<div class="swiper-button-prev"></div>
					<div class="swiper-button-next"></div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
