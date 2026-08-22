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
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_elementor_styles' ) );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_elementor_styles' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_elementor_styles' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_elementor_scripts' ) );
	}

	/**
	 * Enqueue Styles for Elementor (Frontend & Editor Preview)
	 */
	public function enqueue_elementor_styles() {
		wp_enqueue_style( 'revora-google-material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200', array(), null );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'revora-frontend', REVORA_URL . 'assets/css/revora-frontend.css', array( 'dashicons', 'revora-google-material-symbols' ), REVORA_VERSION );
		wp_enqueue_style( 'revora-card-variants', REVORA_URL . 'assets/css/revora-card-variants.css', array(), REVORA_VERSION );
	}

	/**
	 * Enqueue Scripts for Elementor Editor Preview
	 */
	public function enqueue_elementor_scripts() {
		wp_enqueue_script( 'revora-frontend', REVORA_URL . 'assets/js/revora-frontend.js', array( 'jquery' ), REVORA_VERSION, true );
		wp_localize_script( 'revora-frontend', 'revora_vars', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'revora_nonce' ),
		) );
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
		require_once REVORA_PATH . 'includes/widgets/reviews-slider-widget.php';
		require_once REVORA_PATH . 'includes/widgets/featured-review-widget.php';

		// Register widgets
		$widgets_manager->register( new \Revora_Review_Form_Widget() );
		$widgets_manager->register( new \Revora_Reviews_Display_Widget() );
		$widgets_manager->register( new \Revora_Reviews_Slider_Widget() );
		$widgets_manager->register( new \Revora_Featured_Review_Widget() );
	}
}

// Initialize
if ( Revora_Elementor::is_elementor_active() ) {
	Revora_Elementor::get_instance();
}
