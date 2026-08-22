<?php
/**
 * Revora Review Form Widget for Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Revora_Review_Form_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'revora_review_form';
	}

	public function get_title() {
		return __( 'Review Form', 'revora' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return array( 'revora' );
	}

	public function get_keywords() {
		return array( 'review', 'form', 'rating', 'feedback', 'revora' );
	}

	protected function register_controls() {
		// Content Tab
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Form Settings', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$db = new Revora_DB();
		$forms = $db->get_forms();
		$form_options = array();
		if ( empty( $forms ) ) {
			$form_options['0'] = __( 'Default Form', 'revora' );
		} else {
			foreach ( $forms as $form ) {
				$form_options[ $form->id ] = $form->name;
			}
		}

		$this->add_control(
			'form_id',
			array(
				'label'   => __( 'Select Form', 'revora' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $form_options,
				'default' => ! empty( $forms ) ? strval( $forms[0]->id ) : '0',
			)
		);

		$this->end_controls_section();

		// Style Tab - Container
		$this->start_controls_section(
			'style_container',
			array(
				'label' => __( 'Form Container', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'container_background',
			array(
				'label'     => __( 'Background Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .revora-form-container' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'container_max_width',
			array(
				'label'      => __( 'Max Width', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min' => 300,
						'max' => 1200,
					),
					'%'  => array(
						'min' => 10,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 100,
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-form-container' => 'max-width: {{SIZE}}{{UNIT}}; margin-left: auto; margin-right: auto;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .revora-form-container',
			)
		);

		$this->add_control(
			'container_border_radius',
			array(
				'label'      => __( 'Border Radius', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-form-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'container_margin',
			array(
				'label'      => __( 'Margin', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-form-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'container_padding',
			array(
				'label'      => __( 'Padding', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-form-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'container_box_shadow',
				'selector' => '{{WRAPPER}} .revora-form-container',
			)
		);

		$this->end_controls_section();

		// Style Tab - Labels
		$this->start_controls_section(
			'style_labels',
			array(
				'label' => __( 'Labels', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .revora-form-field label, {{WRAPPER}} .revora-form-field label.revora-field-label',
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => __( 'Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-form-field label, {{WRAPPER}} .revora-form-field label.revora-field-label' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'label_spacing',
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
					'{{WRAPPER}} .revora-form-field label, {{WRAPPER}} .revora-form-field label.revora-field-label' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Input Fields
		$this->start_controls_section(
			'style_inputs',
			array(
				'label' => __( 'Input Fields', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'input_typography',
				'selector' => '{{WRAPPER}} .revora-form-field input, {{WRAPPER}} .revora-form-field textarea, {{WRAPPER}} .revora-form-field select',
			)
		);

		$this->start_controls_tabs( 'input_tabs' );

		$this->start_controls_tab(
			'input_normal',
			array(
				'label' => __( 'Normal', 'revora' ),
			)
		);

		$this->add_control(
			'input_text_color',
			array(
				'label'     => __( 'Text Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-form-field input, {{WRAPPER}} .revora-form-field textarea, {{WRAPPER}} .revora-form-field select' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'input_background',
			array(
				'label'     => __( 'Background Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-form-field input, {{WRAPPER}} .revora-form-field textarea, {{WRAPPER}} .revora-form-field select' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'input_border',
				'selector' => '{{WRAPPER}} .revora-form-field input, {{WRAPPER}} .revora-form-field textarea, {{WRAPPER}} .revora-form-field select',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'input_focus',
			array(
				'label' => __( 'Focus', 'revora' ),
			)
		);

		$this->add_control(
			'input_focus_border_color',
			array(
				'label'     => __( 'Border Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-form-field input:focus, {{WRAPPER}} .revora-form-field textarea:focus, {{WRAPPER}} .revora-form-field select:focus' => 'border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'input_border_radius',
			array(
				'label'      => __( 'Border Radius', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-form-field input, {{WRAPPER}} .revora-form-field textarea, {{WRAPPER}} .revora-form-field select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
				'separator'  => 'before',
			)
		);

		$this->add_control(
			'input_padding',
			array(
				'label'      => __( 'Padding', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-form-field input:not([type="file"]), {{WRAPPER}} .revora-form-field textarea, {{WRAPPER}} .revora-form-field select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'input_placeholder_color',
			array(
				'label'     => __( 'Placeholder Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-form-field input::placeholder, {{WRAPPER}} .revora-form-field textarea::placeholder' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'input_height',
			array(
				'label'      => __( 'Inputs Height', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 30,
						'max' => 90,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-form-field input:not([type="submit"]):not([type="checkbox"]):not([type="radio"]), {{WRAPPER}} .revora-form-field select' => 'height: {{SIZE}}{{UNIT}} !important; line-height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'textarea_height',
			array(
				'label'      => __( 'Textarea Height', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 60,
						'max' => 400,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-form-field textarea' => 'min-height: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'field_row_gap',
			array(
				'label'      => __( 'Fields Bottom Spacing (Row Gap)', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-form-field, {{WRAPPER}} .revora-form-col .revora-form-field' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'field_column_gap',
			array(
				'label'      => __( 'Columns Gap (2-Col Spacing)', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-form-row' => 'gap: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Star Rating
		$this->start_controls_section(
			'style_stars',
			array(
				'label' => __( 'Star Rating', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'star_size',
			array(
				'label'      => __( 'Star Size', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 16,
						'max' => 64,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-frontend-stars .revora-star-btn, {{WRAPPER}} .revora-rating-input label svg' => 'font-size: {{SIZE}}{{UNIT}} !important; width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important; line-height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'star_empty_color',
			array(
				'label'     => __( 'Empty Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-frontend-stars .revora-star-btn, {{WRAPPER}} .revora-rating-input label svg' => 'color: {{VALUE}} !important; fill: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'star_filled_color',
			array(
				'label'     => __( 'Filled Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-frontend-stars .revora-star-btn.active, {{WRAPPER}} .revora-frontend-stars .revora-star-btn.fill-1, {{WRAPPER}} .revora-rating-input label:hover svg, {{WRAPPER}} .revora-rating-input label:hover ~ label svg, {{WRAPPER}} .revora-rating-input input[type="radio"]:checked ~ label svg' => 'color: {{VALUE}} !important; fill: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'star_spacing',
			array(
				'label'      => __( 'Spacing', 'revora' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 30,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .revora-frontend-stars, {{WRAPPER}} .revora-rating-input' => 'gap: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'star_box_bg',
			array(
				'label'     => __( 'Rating Box Background', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-frontend-rating-picker' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'star_badge_color',
			array(
				'label'     => __( 'Score Badge Text Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-rating-score-badge' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'star_badge_bg',
			array(
				'label'     => __( 'Score Badge Background', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-rating-score-badge' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// Style Tab - Submit Button
		$this->start_controls_section(
			'style_button',
			array(
				'label' => __( 'Submit Button', 'revora' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .revora-submit-btn',
			)
		);

		$this->start_controls_tabs( 'button_tabs' );

		$this->start_controls_tab(
			'button_normal',
			array(
				'label' => __( 'Normal', 'revora' ),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Text Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-submit-btn' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'button_background',
			array(
				'label'     => __( 'Background Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-submit-btn' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'button_box_shadow',
				'selector' => '{{WRAPPER}} .revora-submit-btn',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button_hover',
			array(
				'label' => __( 'Hover', 'revora' ),
			)
		);

		$this->add_control(
			'button_hover_text_color',
			array(
				'label'     => __( 'Text Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-submit-btn:hover' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'button_hover_background',
			array(
				'label'     => __( 'Background Color', 'revora' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .revora-submit-btn:hover' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'button_hover_box_shadow',
				'selector' => '{{WRAPPER}} .revora-submit-btn:hover',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'      => 'button_border',
				'selector'  => '{{WRAPPER}} .revora-submit-btn',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'button_border_radius',
			array(
				'label'      => __( 'Border Radius', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-submit-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_margin',
			array(
				'label'      => __( 'Margin', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-submit-btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .revora-form-footer' => 'margin-top: {{TOP}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => __( 'Padding', 'revora' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .revora-submit-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'button_width',
			array(
				'label'     => __( 'Width', 'revora' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'100%' => __( 'Full Width', 'revora' ),
					'auto' => __( 'Auto', 'revora' ),
				),
				'default'   => '100%',
				'selectors' => array(
					'{{WRAPPER}} .revora-submit-btn' => 'width: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_align',
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
					'{{WRAPPER}} .revora-form-footer' => 'text-align: {{VALUE}};',
				),
				'condition' => array(
					'button_width' => 'auto',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$category = ! empty( $settings['category'] ) ? $settings['category'] : '';
		$form_id  = ! empty( $settings['form_id'] ) ? intval( $settings['form_id'] ) : 0;

		// Use shortcode to render form
		echo do_shortcode( '[revora_form id="' . esc_attr( $form_id ) . '" category="' . esc_attr( $category ) . '"]' );
	}
}
