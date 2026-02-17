<?php
/**
 * Plugin Name: Revora
 * Plugin URI:  https://revora.moksedul.dev
 * Description: Smart Category-Based Review System for WordPress. Lightweight, custom DB, and AJAX-powered.
 * Version:     1.0.1
 * Author:      Moksedul Islam
 * Author URI:  https://moksedul.dev
 * License:     GPL2
 * Text Domain: revora
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constants
define( 'REVORA_VERSION', '1.0.1' );
define( 'REVORA_PATH', plugin_dir_path( __FILE__ ) );
define( 'REVORA_URL', plugin_dir_url( __FILE__ ) );
define( 'REVORA_INC', REVORA_PATH . 'includes/' );

/**
 * Main Revora Class
 */
class Revora {

	/**
	 * Instance of this class
	 */
	private static $instance = null;

	/**
	 * Get instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->includes();
		$this->init_classes();
		$this->init_hooks();
		$this->check_version();
	}

	/**
	 * Check Version & Run Updates
	 */
	private function check_version() {
		if ( get_option( 'revora_db_version' ) !== REVORA_VERSION ) {
			$this->activate();
			update_option( 'revora_db_version', REVORA_VERSION );
		}
	}

	/**
	 * Initialize Classes
	 */
	private function init_classes() {
		if ( is_admin() ) {
			new Revora_Admin();
		}
		new Revora_Ajax();
		new Revora_Frontend();
		new Revora_Shortcodes();
	}

	/**
	 * Include files
	 */
	private function includes() {
		require_once REVORA_INC . 'class-db.php';
		require_once REVORA_INC . 'class-spam.php';
		require_once REVORA_INC . 'class-ajax.php';
		require_once REVORA_INC . 'class-frontend.php';
		require_once REVORA_INC . 'class-admin.php';
		require_once REVORA_INC . 'class-shortcodes.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Activation & Deactivation
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Load assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Plugin Activation
	 */
	public function activate() {
		$db = new Revora_DB();
		$db->create_table();
		
		// Set default settings if needed
		if ( ! get_option( 'revora_settings' ) ) {
			update_option( 'revora_settings', array(
				'primary_color' => '#0073aa',
				'custom_css'    => '',
				'admin_email'   => get_option( 'admin_email' ),
			) );
		}

		flush_rewrite_rules();
	}

	/**
	 * Plugin Deactivation
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Enqueue Frontend Assets
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'revora-frontend', REVORA_URL . 'assets/css/revora-frontend.css', array(), REVORA_VERSION );
		wp_enqueue_script( 'revora-frontend', REVORA_URL . 'assets/js/revora-frontend.js', array( 'jquery' ), REVORA_VERSION, true );

		wp_localize_script( 'revora-frontend', 'revora_vars', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'revora_submit_nonce' ),
		) );
	}

	/**
	 * Enqueue Admin Assets
	 */
	public function enqueue_admin_assets( $hook ) {
		// Enqueue for Revora pages and main dashboard (for widget)
		if ( strpos( $hook, 'revora' ) !== false || 'index.php' === $hook ) {
			wp_enqueue_style( 'revora-admin', REVORA_URL . 'assets/css/revora-admin.css', array(), REVORA_VERSION );
			wp_enqueue_script( 'revora-admin', REVORA_URL . 'assets/js/revora-admin.js', array( 'jquery' ), REVORA_VERSION, true );
			
			wp_localize_script( 'revora-admin', 'revoraAdmin', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'revora_admin_nonce' ),
			) );
		}

		// Enqueue deactivation survey assets only on plugins page
		if ( 'plugins.php' === $hook ) {
			wp_enqueue_style( 'revora-deactivation', REVORA_URL . 'assets/css/revora-deactivation.css', array(), REVORA_VERSION );
			wp_enqueue_script( 'revora-deactivation', REVORA_URL . 'assets/js/revora-deactivation.js', array( 'jquery' ), REVORA_VERSION, true );
			
			wp_localize_script( 'revora-deactivation', 'revoraDeactivation', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'revora_deactivation_nonce' ),
			) );
		}
	}
}

/**
 * Initialize Plugin
 */
function revora_init() {
	return Revora::get_instance();
}
revora_init();
