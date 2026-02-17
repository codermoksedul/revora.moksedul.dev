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
	private $cat_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'revora_reviews';
		$this->cat_table  = $wpdb->prefix . 'revora_categories';
		$this->rel_table  = $wpdb->prefix . 'revora_review_categories';
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

		// Create Categories Table
		$cat_sql = "CREATE TABLE $this->cat_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			parent_id bigint(20) DEFAULT 0 NOT NULL,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			description text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug),
			KEY parent_id (parent_id)
		) $charset_collate;";

		dbDelta( $cat_sql );

		// Create Relationships Table for Multiple Categories
		$rel_sql = "CREATE TABLE $this->rel_table (
			review_id bigint(20) NOT NULL,
			cat_id bigint(20) NOT NULL,
			PRIMARY KEY (review_id, cat_id),
			KEY review_id (review_id),
			KEY cat_id (cat_id)
		) $charset_collate;";

		dbDelta( $rel_sql );
	}

	/**
	 * Insert Review
	 */
	public function insert_review( $data ) {
		global $wpdb;
		$inserted = $wpdb->insert( $this->table_name, $data );
		return $inserted ? $wpdb->insert_id : false;
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
	 * Get Approved Reviews helper
	 */
	public function get_approved_reviews( $category_slug = '', $limit = 10 ) {
		return $this->get_reviews( array(
			'category_slug' => $category_slug,
			'status'        => 'approved',
			'limit'         => $limit,
		) );
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

	public function get_review( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE id = %d", $id ) );
	}

	public function update_review( $id, $data ) {
		global $wpdb;
		return $wpdb->update( $this->table_name, $data, array( 'id' => $id ) );
	}

	/**
	 * Get Total Counts by Status
	 */
	public function get_counts() {
		global $wpdb;
		$results = $wpdb->get_results( "SELECT status, COUNT(id) as count FROM $this->table_name GROUP BY status", ARRAY_A );
		
		$counts = array(
			'all'      => 0,
			'pending'  => 0,
			'approved' => 0,
			'rejected' => 0,
		);

		foreach ( $results as $row ) {
			if ( isset( $counts[ $row['status'] ] ) ) {
				$counts[ $row['status'] ] = (int) $row['count'];
			}
			$counts['all'] += (int) $row['count'];
		}

		return $counts;
	}

	/**
	 * CATEGORIES METHODS
	 */

	public function insert_category( $data ) {
		global $wpdb;
		$inserted = $wpdb->insert( $this->cat_table, $data );
		return $inserted ? $wpdb->insert_id : false;
	}

	public function get_categories( $args = array() ) {
		global $wpdb;
		$query = "SELECT * FROM $this->cat_table ORDER BY name ASC";
		return $wpdb->get_results( $query );
	}

	public function get_category( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->cat_table WHERE id = %d", $id ) );
	}

	public function update_category( $id, $data ) {
		global $wpdb;
		return $wpdb->update( $this->cat_table, $data, array( 'id' => $id ) );
	}

	public function delete_category( $id ) {
		global $wpdb;
		// Also delete relationships
		$wpdb->delete( $this->rel_table, array( 'cat_id' => $id ) );
		return $wpdb->delete( $this->cat_table, array( 'id' => $id ) );
	}

	/**
	 * MULTIPLE CATEGORY RELATIONSHIPS
	 */

	public function set_review_categories( $review_id, $cat_ids ) {
		global $wpdb;

		// Clear old relationships
		$wpdb->delete( $this->rel_table, array( 'review_id' => $review_id ) );

		if ( empty( $cat_ids ) ) {
			return true;
		}

		if ( ! is_array( $cat_ids ) ) {
			$cat_ids = array( $cat_ids );
		}

		foreach ( $cat_ids as $cat_id ) {
			$wpdb->insert( $this->rel_table, array(
				'review_id' => $review_id,
				'cat_id'    => $cat_id
			) );
		}

		return true;
	}

	public function get_review_categories( $review_id ) {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT cat_id FROM $this->rel_table WHERE review_id = %d", $review_id ) );
	}

	public function duplicate_review( $id ) {
		global $wpdb;
		
		$review = $this->get_review( $id );
		if ( ! $review ) {
			return false;
		}

		$data = array(
			'category_slug' => $review->category_slug,
			'name'          => $review->name,
			'email'         => $review->email,
			'rating'        => $review->rating,
			'title'         => $review->title . ' (Copy)',
			'content'       => $review->content,
			'ip_address'    => $review->ip_address,
			'status'        => $review->status,
		);

		$inserted = $this->insert_review( $data );
		if ( $inserted ) {
			// Duplicate category relationships
			$categories = $this->get_review_categories( $id );
			$this->set_review_categories( $inserted, $categories );
			return $inserted;
		}

		return false;
	}
}
