<?php
/**
 * Revora Reviews Display Widget for Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Revora_Reviews_Display_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'revora_reviews_display';
	}

	public function get_title() {
		return __( 'Reviews Display', 'revora' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	public function get_categories() {
		return array( 'revora' );
	}

	public function get_keywords() {
		return array( 'review', 'reviews', 'rating', 'testimonial', 'revora' );
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
		$categories = $db->get_categories();
		$category_options = array( '' => __( 'All Categories', 'revora' ) );
		foreach ( $categories as $cat ) {
			$category_options[ $cat->slug ] = $cat->name;
		}

		$this->add_control(
			'category',
			array(
				'label'   => __( 'Category', 'revora' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $category_options,
				'default' => '',
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Number of Reviews', 'revora' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 6,
				'min'     => 1,
				'max'     => 100,
			)
		);

		$this->end_controls_section();

		// Content Tab - Layout Settings
		$this->start_controls_section(
			'layout_section',
			array(
				'label' => __( 'Layout Settings', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'           => __( 'Columns', 'revora' ),
				'type'            => \Elementor\Controls_Manager::SELECT,
				'default'         => '3',
				'tablet_default'  => '2',
				'mobile_default'  => '1',
				'options'         => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'selectors'       => array(
					'{{WRAPPER}} .revora-reviews-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				),
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'      => __( 'Column Gap', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'    => array(
					'size' => 24,
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-reviews-grid' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'show_load_more',
			array(
				'label'        => __( 'Show Load More Button', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'load_more_text',
			array(
				'label'     => __( 'Load More Text', 'revora' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Load More Reviews', 'revora' ),
				'condition' => array(
					'show_load_more' => 'yes',
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

		$this->start_controls_tabs( 'card_tabs' );

		$this->start_controls_tab(
			'card_normal',
			array(
				'label' => __( 'Normal', 'revora' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .revora-review-card',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'card_hover',
			array(
				'label' => __( 'Hover', 'revora' ),
			)
		);

		$this->add_control(
			'card_hover_border_color',
			array(
				'label'     => __( 'Border Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-review-card:hover' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_hover_box_shadow',
				'selector' => '{{WRAPPER}} .revora-review-card:hover',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

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
					'{{WRAPPER}} .revora-review-rating .dashicons' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'rating_filled_color',
			array(
				'label'     => __( 'Filled Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-review-rating .dashicons.filled' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'rating_empty_color',
			array(
				'label'     => __( 'Empty Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-review-rating .dashicons.empty' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'rating_spacing',
			array(
				'label'      => __( 'Spacing', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 20,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-review-rating' => 'gap: {{SIZE}}{{UNIT}};',
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

		$this->add_control(
			'title_spacing',
			array(
				'label'      => __( 'Spacing', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-review-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
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

		$this->end_controls_section();

		// Style Tab - Load More Button
		$this->start_controls_section(
			'style_load_more',
			array(
				'label'     => __( 'Load More Button', 'revora' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_load_more' => 'yes',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'load_more_typography',
				'selector' => '{{WRAPPER}} .revora-load-more-btn',
			)
		);

		$this->start_controls_tabs( 'load_more_tabs' );

		$this->start_controls_tab(
			'load_more_normal',
			array(
				'label' => __( 'Normal', 'revora' ),
			)
		);

		$this->add_control(
			'load_more_text_color',
			array(
				'label'     => __( 'Text Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-load-more-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'load_more_background',
			array(
				'label'     => __( 'Background Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-load-more-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'load_more_box_shadow',
				'selector' => '{{WRAPPER}} .revora-load-more-btn',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'load_more_hover',
			array(
				'label' => __( 'Hover', 'revora' ),
			)
		);

		$this->add_control(
			'load_more_hover_text_color',
			array(
				'label'     => __( 'Text Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-load-more-btn:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'load_more_hover_background',
			array(
				'label'     => __( 'Background Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-load-more-btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'load_more_hover_box_shadow',
				'selector' => '{{WRAPPER}} .revora-load-more-btn:hover',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'      => 'load_more_border',
				'selector'  => '{{WRAPPER}} .revora-load-more-btn',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'load_more_border_radius',
			array(
				'label'      => __( 'Border Radius', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-load-more-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'load_more_padding',
			array(
				'label'      => __( 'Padding', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-load-more-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'load_more_align',
			array(
				'label'     => __( 'Alignment', 'revora' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => __( 'Left', 'revora' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'revora' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'revora' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .revora-load-more-container' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$category = ! empty( $settings['category'] ) ? $settings['category'] : '';
		$limit = ! empty( $settings['limit'] ) ? $settings['limit'] : 6;
		$columns = ! empty( $settings['columns'] ) ? $settings['columns'] : 3;

		// Use shortcode to render reviews
		echo do_shortcode( '[revora_reviews category="' . esc_attr( $category ) . '" limit="' . esc_attr( $limit ) . '" columns="' . esc_attr( $columns ) . '"]' );

		// Hide elements based on settings
		?>
		<style>
			<?php if ( 'yes' !== $settings['show_author'] ) : ?>
				.elementor-element-<?php echo $this->get_id(); ?> .revora-review-author {
					display: none;
				}
			<?php endif; ?>
			<?php if ( 'yes' !== $settings['show_date'] ) : ?>
				.elementor-element-<?php echo $this->get_id(); ?> .revora-review-date {
					display: none;
				}
			<?php endif; ?>
			<?php if ( 'yes' !== $settings['show_rating'] ) : ?>
				.elementor-element-<?php echo $this->get_id(); ?> .revora-review-rating {
					display: none;
				}
			<?php endif; ?>
			<?php if ( 'yes' !== $settings['show_title'] ) : ?>
				.elementor-element-<?php echo $this->get_id(); ?> .revora-review-title {
					display: none;
				}
			<?php endif; ?>
			<?php if ( 'yes' !== $settings['show_load_more'] ) : ?>
				.elementor-element-<?php echo $this->get_id(); ?> .revora-load-more-container {
					display: none;
				}
			<?php endif; ?>
		</style>
		<?php

		// Override load more button text
		if ( ! empty( $settings['load_more_text'] ) && 'yes' === $settings['show_load_more'] ) {
			?>
			<script>
				jQuery(document).ready(function($) {
					$('.elementor-element-<?php echo $this->get_id(); ?> .revora-load-more-btn .btn-text').text('<?php echo esc_js( $settings['load_more_text'] ); ?>');
				});
			</script>
			<?php
		}
	}
}
