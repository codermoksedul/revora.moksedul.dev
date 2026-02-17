<?php
/**
 * Plugin Name: Revora
 * Plugin URI:  https://revora.moksedul.dev
 * Description: Smart Category-Based Review System for WordPress. Lightweight, custom DB, and AJAX-powered.
 * Version:     1.0.1
 * Author:      Moksedul Islam
 * Author URI:  https://moksedul.dev
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
		require_once REVORA_INC . 'class-elementor.php';
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
		
		// Privacy policy
		add_action( 'admin_init', array( $this, 'add_privacy_policy' ) );
		
		// Plugin action links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );
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
		wp_enqueue_style( 'revora-card-variants', REVORA_URL . 'assets/css/revora-card-variants.css', array(), REVORA_VERSION );
		wp_enqueue_script( 'revora-frontend', REVORA_URL . 'assets/js/revora-frontend.js', array( 'jquery' ), REVORA_VERSION, true );
		
		wp_localize_script( 'revora-frontend', 'revora_vars', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
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
	
	/**
	 * Add Privacy Policy Content
	 */
	public function add_privacy_policy() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			'<h2>%s</h2>' .
			'<p>%s</p>' .
			'<h3>%s</h3>' .
			'<ul>' .
			'<li>%s</li>' .
			'<li>%s</li>' .
			'<li>%s</li>' .
			'<li>%s</li>' .
			'<li>%s</li>' .
			'<li>%s</li>' .
			'</ul>' .
			'<h3>%s</h3>' .
			'<p>%s</p>' .
			'<h3>%s</h3>' .
			'<p>%s</p>' .
			'<h3>%s</h3>' .
			'<p>%s</p>',
			__( 'Revora - Review System', 'revora' ),
			__( 'When users submit reviews through Revora, we collect and store the following information:', 'revora' ),
			__( 'Data Collected', 'revora' ),
			__( '<strong>Name</strong> - To display the reviewer\'s identity', 'revora' ),
			__( '<strong>Email Address</strong> - For verification and notifications (not publicly displayed)', 'revora' ),
			__( '<strong>Review Content</strong> - Title and detailed review text', 'revora' ),
			__( '<strong>Star Rating</strong> - Rating from 1 to 5 stars', 'revora' ),
			__( '<strong>IP Address</strong> - For spam detection and security purposes', 'revora' ),
			__( '<strong>Submission Date</strong> - Timestamp of when the review was submitted', 'revora' ),
			__( 'How We Use This Data', 'revora' ),
			__( 'The collected data is used to display reviews on your website, send email notifications to administrators, detect and prevent spam submissions, and moderate reviews.', 'revora' ),
			__( 'Data Retention', 'revora' ),
			__( 'Review data is stored indefinitely until manually deleted by a site administrator through the Revora admin panel.', 'revora' ),
			__( 'User Rights', 'revora' ),
			__( 'Users can request deletion of their review data by contacting the site administrator. Administrators can delete reviews from the Revora admin panel at any time.', 'revora' )
		);

		wp_add_privacy_policy_content(
			'Revora',
			wp_kses_post( wpautop( $content, false ) )
		);
	}
	
	/**
	 * Add Plugin Action Links
	 */
	public function add_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=revora-settings' ) . '">' . __( 'Settings', 'revora' ) . '</a>',
			'<a href="https://revora.moksedul.dev/upgrade" target="_blank" style="color: #39b54a; font-weight: bold;">' . __( 'Go Premium', 'revora' ) . '</a>',
		);
		
		return array_merge( $plugin_links, $links );
	}
}

/**
 * Initialize Plugin
 */
function revora_init() {
	return Revora::get_instance();
}
revora_init();
