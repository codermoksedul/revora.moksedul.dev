<?php
/**
 * Revora Reviews Slider Widget for Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
class Revora_Reviews_Slider_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'revora_reviews_slider';
	}

	public function get_title() {
		return __( 'Reviews Slider', 'revora' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_categories() {
		return array( 'revora' );
	}

	public function get_keywords() {
		return array( 'review', 'slider', 'carousel', 'testimonial', 'revora' );
	}

	public function get_script_depends() {
		return array( 'swiper' );
	}

	public function get_style_depends() {
		return array( 'swiper' );
	}

	protected function register_controls() {
		// Content Tab - Query Settings
		$this->start_controls_section(
			'query_section',
			array(
				'label' => __( 'Query Settings', 'revora' ),
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
			'limit',
			array(
				'label'   => __( 'Number of Reviews', 'revora' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 1,
				'max'     => 100,
			)
		);

		$this->end_controls_section();

		// Content Tab - Slider Settings
		$this->start_controls_section(
			'slider_section',
			array(
				'label' => __( 'Slider Settings', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'card_style',
			array(
				'label'   => __( 'Card Style', 'revora' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'classic'      => __( 'Classic', 'revora' ),
					'modern'       => __( 'Modern', 'revora' ),
					'boxed'        => __( 'Boxed', 'revora' ),
					'horizontal'   => __( 'Horizontal', 'revora' ),
					'testimonial'  => __( 'Testimonial', 'revora' ),
				),
				'default' => 'classic',
			)
		);

		$this->add_responsive_control(
			'slides_to_show',
			array(
				'label'          => __( 'Slides to Show', 'revora' ),
				'type'           => \Elementor\Controls_Manager::NUMBER,
				'default'        => 3,
				'tablet_default' => 2,
				'mobile_default' => 1,
				'min'            => 1,
				'max'            => 6,
			)
		);

		$this->add_control(
			'slides_to_scroll',
			array(
				'label'   => __( 'Slides to Scroll', 'revora' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 1,
				'min'     => 1,
				'max'     => 6,
			)
		);

		$this->add_responsive_control(
			'space_between',
			array(
				'label'          => __( 'Space Between', 'revora' ),
				'type'           => \Elementor\Controls_Manager::NUMBER,
				'default'        => 24,
				'tablet_default' => 20,
				'mobile_default' => 16,
				'min'            => 0,
				'max'            => 100,
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
				'label'     => __( 'Autoplay Speed (ms)', 'revora' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 3000,
				'min'       => 1000,
				'max'       => 10000,
				'step'      => 100,
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
			'speed',
			array(
				'label'   => __( 'Animation Speed (ms)', 'revora' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 500,
				'min'     => 100,
				'max'     => 3000,
				'step'    => 100,
			)
		);

		$this->add_control(
			'effect',
			array(
				'label'   => __( 'Effect', 'revora' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'slide'     => __( 'Slide', 'revora' ),
					'fade'      => __( 'Fade', 'revora' ),
					'cube'      => __( 'Cube', 'revora' ),
					'coverflow' => __( 'Coverflow', 'revora' ),
				),
				'default' => 'slide',
			)
		);

		$this->end_controls_section();

		// Content Tab - Navigation
		$this->start_controls_section(
			'navigation_section',
			array(
				'label' => __( 'Navigation', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_arrows',
			array(
				'label'        => __( 'Show Arrows', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'arrow_position',
			array(
				'label'     => __( 'Arrow Position', 'revora' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'inside'  => __( 'Inside', 'revora' ),
					'outside' => __( 'Outside', 'revora' ),
				),
				'default'   => 'inside',
				'condition' => array(
					'show_arrows' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_pagination',
			array(
				'label'        => __( 'Show Pagination', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'pagination_type',
			array(
				'label'     => __( 'Pagination Type', 'revora' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'bullets'     => __( 'Bullets', 'revora' ),
					'fraction'    => __( 'Fraction', 'revora' ),
					'progressbar' => __( 'Progress Bar', 'revora' ),
				),
				'default'   => 'bullets',
				'condition' => array(
					'show_pagination' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// Content Tab - Display Options
		$this->start_controls_section(
			'display_section',
			array(
				'label' => __( 'Display Options', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
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
			'show_date',
			array(
				'label'        => __( 'Show Date', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_rating',
			array(
				'label'        => __( 'Show Rating Stars', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'        => __( 'Show Review Title', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// Style Tab - Review Cards
		$this->start_controls_section(
			'style_cards',
			array(
				'label' => __( 'Review Cards', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_background',
			array(
				'label'     => __( 'Background Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-review-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .revora-review-card',
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => __( 'Border Radius', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-review-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'card_padding',
			array(
				'label'      => __( 'Padding', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-review-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .revora-review-card',
			)
		);

		$this->end_controls_section();

		// Style Tab - Author Name
		$this->start_controls_section(
			'style_author',
			array(
				'label'     => __( 'Author Name', 'revora' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_author' => 'yes',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'author_typography',
				'selector' => '{{WRAPPER}} .revora-review-author',
			)
		);

		$this->add_control(
			'author_color',
			array(
				'label'     => __( 'Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-review-author' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'author_margin',
			array(
				'label'      => __( 'Margin', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-review-author' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Date
		$this->start_controls_section(
			'style_date',
			array(
				'label'     => __( 'Date', 'revora' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_date' => 'yes',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'date_typography',
				'selector' => '{{WRAPPER}} .revora-review-date',
			)
		);

		$this->add_control(
			'date_color',
			array(
				'label'     => __( 'Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-review-date' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'date_margin',
			array(
				'label'      => __( 'Margin', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-review-date' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Rating Stars
		$this->start_controls_section(
			'style_rating',
			array(
				'label'     => __( 'Rating Stars', 'revora' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_rating' => 'yes',
				),
			)
		);

		$this->add_control(
			'rating_star_size',
			array(
				'label'      => __( 'Star Size', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 10,
						'max' => 32,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-review-rating .revora-star, {{WRAPPER}} .revora-review-rating .material-symbols-outlined, {{WRAPPER}} .revora-review-rating .dashicons' => 'font-size: {{SIZE}}{{UNIT}} !important; width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important; line-height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'rating_filled_color',
			array(
				'label'     => __( 'Filled Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-review-rating .revora-star.filled, {{WRAPPER}} .revora-review-rating .revora-star.fill-1, {{WRAPPER}} .revora-review-rating .material-symbols-outlined.fill-1, {{WRAPPER}} .revora-review-rating .dashicons.filled' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'rating_empty_color',
			array(
				'label'     => __( 'Empty Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-review-rating .revora-star.empty, {{WRAPPER}} .revora-review-rating .material-symbols-outlined:not(.fill-1), {{WRAPPER}} .revora-review-rating .dashicons.empty' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'rating_margin',
			array(
				'label'      => __( 'Margin', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-review-rating' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Review Title
		$this->start_controls_section(
			'style_title',
			array(
				'label'     => __( 'Review Title', 'revora' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_title' => 'yes',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .revora-review-title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-review-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_margin',
			array(
				'label'      => __( 'Margin', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-review-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Review Content
		$this->start_controls_section(
			'style_content',
			array(
				'label' => __( 'Review Content', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'content_typography',
				'selector' => '{{WRAPPER}} .revora-review-content',
			)
		);

		$this->add_control(
			'content_color',
			array(
				'label'     => __( 'Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-review-content' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'content_margin',
			array(
				'label'      => __( 'Margin', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-review-content' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Navigation Arrows
		$this->start_controls_section(
			'style_arrows',
			array(
				'label'     => __( 'Navigation Arrows', 'revora' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_arrows' => 'yes',
				),
			)
		);

		$this->add_control(
			'arrow_size',
			array(
				'label'      => __( 'Size', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 20,
						'max' => 80,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .swiper-button-next, {{WRAPPER}} .swiper-button-prev' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .swiper-button-next:after, {{WRAPPER}} .swiper-button-prev:after' => 'font-size: calc({{SIZE}}{{UNIT}} / 2);',
				),
			)
		);

		$this->start_controls_tabs( 'arrow_tabs' );

		$this->start_controls_tab(
			'arrow_normal',
			array(
				'label' => __( 'Normal', 'revora' ),
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => __( 'Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .swiper-button-next, {{WRAPPER}} .swiper-button-prev' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_background',
			array(
				'label'     => __( 'Background Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .swiper-button-next, {{WRAPPER}} .swiper-button-prev' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'arrow_hover',
			array(
				'label' => __( 'Hover', 'revora' ),
			)
		);

		$this->add_control(
			'arrow_hover_color',
			array(
				'label'     => __( 'Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .swiper-button-next:hover, {{WRAPPER}} .swiper-button-prev:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_hover_background',
			array(
				'label'     => __( 'Background Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .swiper-button-next:hover, {{WRAPPER}} .swiper-button-prev:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'arrow_border_radius',
			array(
				'label'      => __( 'Border Radius', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .swiper-button-next, {{WRAPPER}} .swiper-button-prev' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->end_controls_section();

		// Style Tab - Pagination
		$this->start_controls_section(
			'style_pagination',
			array(
				'label'     => __( 'Pagination', 'revora' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_pagination' => 'yes',
				),
			)
		);

		$this->add_control(
			'pagination_size',
			array(
				'label'      => __( 'Size', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 6,
						'max' => 20,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'pagination_type' => 'bullets',
				),
			)
		);

		$this->add_control(
			'pagination_color',
			array(
				'label'     => __( 'Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .swiper-pagination-progressbar-fill' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .swiper-pagination-fraction' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pagination_active_color',
			array(
				'label'     => __( 'Active Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
				),
				'condition' => array(
					'pagination_type' => 'bullets',
				),
			)
		);

		$this->add_control(
			'pagination_spacing',
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
					'{{WRAPPER}} .swiper-pagination' => 'bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$form_id = ! empty( $settings['form_id'] ) ? intval( $settings['form_id'] ) : 0;
		$limit = ! empty( $settings['limit'] ) ? $settings['limit'] : 10;
		$card_style = ! empty( $settings['card_style'] ) ? $settings['card_style'] : 'classic';

		$db = new Revora_DB();
		$reviews = $db->get_approved_reviews( $form_id, $limit );
		$plugin_settings = wp_parse_args( get_option( 'revora_settings', array() ), array(
			'star_color' => '#fbbf24',
			'show_stars' => '1',
		) );

		if ( empty( $reviews ) ) {
			echo '<p class="revora-no-reviews">' . esc_html__( 'No reviews yet.', 'revora' ) . '</p>';
			return;
		}

		$widget_id = $this->get_id();
		$settings_opts = get_option( 'revora_settings' );
		$primary_color = ! empty( $settings_opts['primary_color'] ) ? $settings_opts['primary_color'] : '#4566f9';
		$star_color = ! empty( $settings_opts['star_color'] ) ? $settings_opts['star_color'] : '#fbbf24';

		$custom_css = "
			.revora-slider-{$widget_id} {
				--revora-primary: " . esc_attr( $primary_color ) . ";
				--revora-star-filled: " . esc_attr( $star_color ) . ";
			}
		";
		wp_add_inline_style( 'revora-frontend', $custom_css );
		// Slider Settings for JS
		$slider_settings = array(
			'slidesPerView'       => ! empty( $settings['slides_to_show'] ) ? intval( $settings['slides_to_show'] ) : 3,
			'slidesToScroll'      => ! empty( $settings['slides_to_scroll'] ) ? intval( $settings['slides_to_scroll'] ) : 1,
			'spaceBetween'        => ! empty( $settings['space_between']['size'] ) ? intval( $settings['space_between']['size'] ) : 24,
			'autoplay'            => 'yes' === $settings['autoplay'],
			'autoplaySpeed'       => ! empty( $settings['autoplay_speed'] ) ? intval( $settings['autoplay_speed'] ) : 3000,
			'pauseOnHover'        => 'yes' === $settings['pause_on_hover'],
			'loop'                => 'yes' === $settings['loop'],
			'speed'               => ! empty( $settings['speed'] ) ? intval( $settings['speed'] ) : 500,
			'effect'              => $settings['effect'],
			'showArrows'          => 'yes' === $settings['show_arrows'],
			'showPagination'      => 'yes' === $settings['show_pagination'],
			'paginationType'      => $settings['pagination_type'],
			'slidesToShowTablet'  => ! empty( $settings['slides_to_show_tablet'] ) ? intval( $settings['slides_to_show_tablet'] ) : 2,
			'spaceBetweenTablet'  => ! empty( $settings['space_between_tablet']['size'] ) ? intval( $settings['space_between_tablet']['size'] ) : 20,
			'slidesToShowMobile'  => ! empty( $settings['slides_to_show_mobile'] ) ? intval( $settings['slides_to_show_mobile'] ) : 1,
			'spaceBetweenMobile'  => ! empty( $settings['space_between_mobile']['size'] ) ? intval( $settings['space_between_mobile']['size'] ) : 16,
		);

		$container_class = 'revora-slider-widget-container';
		if ( 'outside' === $settings['arrow_position'] ) {
			$container_class .= ' arrows-outside';
		}
		if ( 'yes' !== $settings['show_author'] ) {
			$container_class .= ' revora-hide-author';
		}
		if ( 'yes' !== $settings['show_date'] ) {
			$container_class .= ' revora-hide-date';
		}
		if ( 'yes' !== $settings['show_rating'] ) {
			$container_class .= ' revora-hide-rating';
		}
		if ( 'yes' !== $settings['show_title'] ) {
			$container_class .= ' revora-hide-title';
		}
		?>
		<div class="<?php echo esc_attr( $container_class ); ?>" data-slider-settings='<?php echo wp_json_encode( $slider_settings ); ?>'>
			<div class="revora-slider-container <?php echo esc_attr( $settings['arrow_position'] ); ?>-arrows">
				<div class="swiper revora-reviews-slider revora-slider-<?php echo esc_attr( $widget_id ); ?>">
					<div class="swiper-wrapper">
						<?php foreach ( $reviews as $review ) : ?>
							<div class="swiper-slide">
								<div class="revora-review-card style-<?php echo esc_attr( $settings['card_style'] ); ?>">
									<div class="revora-review-header">
										<div class="revora-review-meta">
											<?php if ( 'yes' === $settings['show_author'] ) : ?>
												<span class="revora-review-author"><?php echo esc_html( $review->name ); ?></span>
											<?php endif; ?>
											<?php if ( 'yes' === $settings['show_date'] ) : ?>
												<span class="revora-review-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ) ); ?></span>
											<?php endif; ?>
										</div>
										<?php if ( 'yes' === $settings['show_rating'] && '1' === $plugin_settings['show_stars'] ) : ?>
											<div class="revora-review-rating">
												<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
													<span class="dashicons dashicons-star-filled <?php echo esc_attr( $i <= $review->rating ? 'filled' : 'empty' ); ?>"></span>
												<?php endfor; ?>
											</div>
										<?php endif; ?>
									</div>
									<?php if ( 'yes' === $settings['show_title'] ) : ?>
										<h4 class="revora-review-title"><?php echo esc_html( $review->title ); ?></h4>
									<?php endif; ?>
									<div class="revora-review-content">
										<?php echo wp_kses_post( wpautop( esc_html( $review->content ) ) ); ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<?php if ( 'yes' === $settings['show_arrows'] ) : ?>
						<div class="swiper-button-next"></div>
						<div class="swiper-button-prev"></div>
					<?php endif; ?>

					<?php if ( 'yes' === $settings['show_pagination'] ) : ?>
						<div class="swiper-pagination"></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}
}
