<?php
/**
 * Database Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Revora_DB {

	/**
	 * Table name
	 */
	private $table_name;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'revora_reviews';
	}

	/**
	 * Create Custom Table
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $this->table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			category_slug varchar(255) NOT NULL,
			name varchar(255) NOT NULL,
			email varchar(255) NOT NULL,
			rating tinyint(1) NOT NULL,
			title varchar(255) NOT NULL,
			content text NOT NULL,
			ip_address varchar(100) NOT NULL,
			status varchar(20) DEFAULT 'pending' NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY (id),
			KEY category_slug (category_slug),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert Review
	 */
	public function insert_review( $data ) {
		global $wpdb;
		return $wpdb->insert( $this->table_name, $data );
	}

	/**
	 * Get Reviews
	 */
	public function get_reviews( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'category_slug' => '',
			'status'        => 'approved',
			'limit'         => 10,
			'offset'        => 0,
			'orderby'       => 'created_at',
			'order'         => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$query = "SELECT * FROM $this->table_name WHERE 1=1";
		$params = array();

		if ( ! empty( $args['category_slug'] ) ) {
			$query .= " AND category_slug = %s";
			$params[] = $args['category_slug'];
		}

		if ( ! empty( $args['status'] ) ) {
			$query .= " AND status = %s";
			$params[] = $args['status'];
		}

		$query .= " ORDER BY {$args['orderby']} {$args['order']}";
		$query .= " LIMIT %d OFFSET %d";
		$params[] = $args['limit'];
		$params[] = $args['offset'];

		return $wpdb->get_results( $wpdb->prepare( $query, $params ) );
	}

	/**
	 * Get Stats (Average Rating & Count)
	 */
	public function get_stats( $category_slug ) {
		global $wpdb;

		$query = "SELECT AVG(rating) as average, COUNT(id) as total FROM $this->table_name WHERE category_slug = %s AND status = 'approved'";
		return $wpdb->get_row( $wpdb->prepare( $query, $category_slug ) );
	}

	/**
	 * Get Rating Breakdown
	 */
	public function get_rating_breakdown( $category_slug ) {
		global $wpdb;

		$query = "SELECT rating, COUNT(id) as count FROM $this->table_name WHERE category_slug = %s AND status = 'approved' GROUP BY rating";
		return $wpdb->get_results( $wpdb->prepare( $query, $category_slug ) );
	}

	/**
	 * Update Review Status
	 */
	public function update_status( $id, $status ) {
		global $wpdb;
		return $wpdb->update(
			$this->table_name,
			array( 'status' => $status ),
			array( 'id' => $id )
		);
	}

	/**
	 * Delete Review
	 */
	public function delete_review( $id ) {
		global $wpdb;
		return $wpdb->delete( $this->table_name, array( 'id' => $id ) );
	}
}
