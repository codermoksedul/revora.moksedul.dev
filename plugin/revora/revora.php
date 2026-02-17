<?php
/**
 * Plugin Name: Revora
 * Plugin URI:  https://revora.moksedul.dev
 * Description: Smart Category-Based Review System for WordPress. Lightweight, custom DB, and AJAX-powered.
 * Version:     1.0.0
 * Author:      Moksedul Islam
 * Author URI:  https://moksedul.dev
 * License:     GPL2
 * Text Domain: revora
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constants
define( 'REVORA_VERSION', '1.0.0' );
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
		$this->init_hooks();
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
		if ( 'toplevel_page_revora' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'revora-admin', REVORA_URL . 'assets/css/revora-admin.css', array(), REVORA_VERSION );
	}
}

/**
 * Initialize Plugin
 */
function revora_init() {
	return Revora::get_instance();
}
revora_init();
