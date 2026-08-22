<?php

$files = array(
    'includes/widgets/reviews-slider-widget.php',
    'includes/widgets/reviews-display-widget.php',
    'includes/widgets/featured-review-widget.php',
);

$controls_to_add = "
		\$this->add_control(
			'show_avatar',
			array(
				'label'        => __( 'Show Profile Image', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		\$this->add_control(
			'show_subtitle',
			array(
				'label'        => __( 'Show Phone/Email', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		\$this->add_control(
			'show_content',
			array(
				'label'        => __( 'Show Review Content', 'revora' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'revora' ),
				'label_off'    => __( 'Hide', 'revora' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
";

$render_classes = "
		if ( 'yes' !== (\$settings['show_avatar'] ?? 'yes') ) \$container_class .= ' revora-hide-avatar';
		if ( 'yes' !== (\$settings['show_subtitle'] ?? 'yes') ) \$container_class .= ' revora-hide-subtitle';
		if ( 'yes' !== (\$settings['show_content'] ?? 'yes') ) \$container_class .= ' revora-hide-content';
";

foreach ( $files as $file ) {
    $path = __DIR__ . '/' . $file;
    if ( ! file_exists( $path ) ) continue;
    
    $content = file_get_contents( $path );
    
    // Add controls after show_title
    if ( strpos( $content, "'show_content'" ) === false ) {
        // Find show_title control block
        $pattern = '/(\$this->add_control\(\s*\'show_title\'.*?\)\s*;)/s';
        $content = preg_replace( $pattern, "$1\n" . $controls_to_add, $content );
    }

    // Replace render classes
    if ( strpos( $content, 'revora-hide-avatar' ) === false ) {
        if ( strpos( $content, 'revora-hide-title\';' ) !== false ) {
            $content = str_replace( "revora-hide-title';\n", "revora-hide-title';\n" . $render_classes, $content );
        } else {
            // For slider and featured, they might not have container_class setup yet.
            // Actually slider and featured don't use container_class for hiding yet.
            // Let's check how they start their HTML.
            // Slider starts with <div class="revora-reviews-slider-widget
            // Featured starts with <div class="revora-featured-review-widget
            
            // For Slider
            $content = str_replace( 
                '<div class="revora-reviews-slider-widget', 
                '<?php $container_class = "revora-reviews-slider-widget";' . "\n" . 
                'if ( "yes" !== ($settings["show_author"] ?? "yes") ) $container_class .= " revora-hide-author";' . "\n" . 
                'if ( "yes" !== ($settings["show_date"] ?? "yes") ) $container_class .= " revora-hide-date";' . "\n" . 
                'if ( "yes" !== ($settings["show_rating"] ?? "yes") ) $container_class .= " revora-hide-rating";' . "\n" . 
                'if ( "yes" !== ($settings["show_title"] ?? "yes") ) $container_class .= " revora-hide-title";' . "\n" . 
                'if ( "yes" !== ($settings["show_avatar"] ?? "yes") ) $container_class .= " revora-hide-avatar";' . "\n" . 
                'if ( "yes" !== ($settings["show_subtitle"] ?? "yes") ) $container_class .= " revora-hide-subtitle";' . "\n" . 
                'if ( "yes" !== ($settings["show_content"] ?? "yes") ) $container_class .= " revora-hide-content";' . "\n" . 
                '?>' . "\n" . 
                '<div class="<?php echo esc_attr($container_class); ?>"', 
                $content 
            );

            // For Featured
            $content = str_replace( 
                '<div class="revora-featured-review-widget', 
                '<?php $container_class = "revora-featured-review-widget";' . "\n" . 
                'if ( "yes" !== ($settings["show_author"] ?? "yes") ) $container_class .= " revora-hide-author";' . "\n" . 
                'if ( "yes" !== ($settings["show_date"] ?? "yes") ) $container_class .= " revora-hide-date";' . "\n" . 
                'if ( "yes" !== ($settings["show_rating"] ?? "yes") ) $container_class .= " revora-hide-rating";' . "\n" . 
                'if ( "yes" !== ($settings["show_title"] ?? "yes") ) $container_class .= " revora-hide-title";' . "\n" . 
                'if ( "yes" !== ($settings["show_avatar"] ?? "yes") ) $container_class .= " revora-hide-avatar";' . "\n" . 
                'if ( "yes" !== ($settings["show_subtitle"] ?? "yes") ) $container_class .= " revora-hide-subtitle";' . "\n" . 
                'if ( "yes" !== ($settings["show_content"] ?? "yes") ) $container_class .= " revora-hide-content";' . "\n" . 
                '?>' . "\n" . 
                '<div class="<?php echo esc_attr($container_class); ?>"', 
                $content 
            );
        }
    }
    
    file_put_contents( $path, $content );
    echo "Updated $file\n";
}
echo "Done";
