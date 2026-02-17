<?php
/**
 * Elementor Integration Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Revora_Elementor {

	/**
	 * Instance
	 */
	private static $instance = null;

	/**
	 * Get Instance
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'add_elementor_category' ) );
	}

	/**
	 * Check if Elementor is Active
	 */
	public static function is_elementor_active() {
		return did_action( 'elementor/loaded' );
	}

	/**
	 * Add Custom Elementor Category
	 */
	public function add_elementor_category( $elements_manager ) {
		$elements_manager->add_category(
			'revora',
			array(
				'title' => __( 'Revora', 'revora' ),
				'icon'  => 'fa fa-star',
			)
		);
	}

	/**
	 * Register Widgets
	 */
	public function register_widgets( $widgets_manager ) {
		// Load widget files
		require_once REVORA_PATH . 'includes/widgets/review-form-widget.php';
		require_once REVORA_PATH . 'includes/widgets/reviews-display-widget.php';

		// Register widgets
		$widgets_manager->register( new \Revora_Review_Form_Widget() );
		$widgets_manager->register( new \Revora_Reviews_Display_Widget() );
	}
}

// Initialize
if ( Revora_Elementor::is_elementor_active() ) {
	Revora_Elementor::get_instance();
}
