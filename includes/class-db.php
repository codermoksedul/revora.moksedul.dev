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
	private $rel_table;
	private $form_table;
	private $meta_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'revora_reviews';
		$this->cat_table  = $wpdb->prefix . 'revora_categories';
		$this->rel_table  = $wpdb->prefix . 'revora_review_categories';
		$this->form_table = $wpdb->prefix . 'revora_forms';
		$this->meta_table = $wpdb->prefix . 'revora_reviewmeta';

		// Auto-migrate column types if necessary
		$migrated = get_option( 'revora_db_rating_decimal_v2', false );
		if ( ! $migrated ) {
			// Alter rating to decimal(3,1)
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$this->table_name} MODIFY rating decimal(3,1) DEFAULT 5.0 NOT NULL;" );
			
			// Check if user_id column exists
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$col = $wpdb->get_results( "SHOW COLUMNS FROM {$this->table_name} LIKE 'user_id'" );
			if ( empty( $col ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "ALTER TABLE {$this->table_name} ADD user_id bigint(20) DEFAULT 0 NOT NULL AFTER id;" );
			}
			update_option( 'revora_db_rating_decimal_v2', true );
		}
	}

	/**
	 * Create Custom Table
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $this->table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) DEFAULT 0 NOT NULL,
			form_id bigint(20) DEFAULT 0 NOT NULL,
			name varchar(255) NOT NULL,
			email varchar(255) NOT NULL,
			rating decimal(3,1) DEFAULT 5.0 NOT NULL,
			title varchar(255) NOT NULL,
			content text NOT NULL,
			ip_address varchar(100) NOT NULL,
			status varchar(20) DEFAULT 'pending' NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY form_id (form_id),
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

		// Create Forms Table
		$form_sql = "CREATE TABLE $this->form_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			fields longtext,
			settings longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY (id)
		) $charset_collate;";

		dbDelta( $form_sql );

		// Create Review Meta Table
		$meta_sql = "CREATE TABLE $this->meta_table (
			meta_id bigint(20) NOT NULL AUTO_INCREMENT,
			review_id bigint(20) NOT NULL,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext,
			PRIMARY KEY (meta_id),
			KEY review_id (review_id),
			KEY meta_key (meta_key(191))
		) $charset_collate;";

		dbDelta( $meta_sql );

		// Seed Default Form
		$forms_count = $wpdb->get_var( "SELECT COUNT(*) FROM $this->form_table" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( '0' === $forms_count || 0 === $forms_count || null === $forms_count ) {
			$default_fields = array(
				array( 'type' => 'text', 'label' => 'Your Name', 'key' => 'name', 'required' => true ),
				array( 'type' => 'email', 'label' => 'Your Email', 'key' => 'email', 'required' => true ),
				array( 'type' => 'rating', 'label' => 'Rating', 'key' => 'rating', 'required' => true ),
				array( 'type' => 'text', 'label' => 'Review Title', 'key' => 'title', 'required' => true ),
				array( 'type' => 'textarea', 'label' => 'Review Content', 'key' => 'content', 'required' => true ),
			);
			$default_settings = array( 'submit_text' => 'Submit Review' );
			
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( $this->form_table, array(
				'name'     => 'Default Form',
				'fields'   => wp_json_encode( $default_fields ),
				'settings' => wp_json_encode( $default_settings ),
			) );
		}
	}

	/**
	 * Insert Review
	 */
	public function insert_review( $data ) {
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert( $this->table_name, $data );
		return $inserted ? $wpdb->insert_id : false;
	}

	/**
	 * Get Reviews
	 */
	public function get_reviews( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'form_id'       => 0,
			'category_slug' => '',
			'status'        => 'approved',
			'limit'         => 10,
			'offset'        => 0,
			'orderby'       => 'created_at',
			'order'         => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$params = array();
		$query = "SELECT r.* FROM $this->table_name r WHERE 1=1";

		if ( ! empty( $args['form_id'] ) && 'all' !== $args['form_id'] && -1 !== (int) $args['form_id'] ) {
			$query .= " AND r.form_id = %d";
			$params[] = intval( $args['form_id'] );
		} elseif ( ! empty( $args['category_slug'] ) ) {
			$query .= " AND r.form_id IN (SELECT id FROM $this->form_table WHERE name = %s)";
			$params[] = $args['category_slug'];
		}

		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$query .= " AND r.status = %s";
			$params[] = $args['status'];
		}

		if ( ! empty( $args['min_rating'] ) ) {
			$query .= " AND r.rating >= %f";
			$params[] = floatval( $args['min_rating'] );
		}

		if ( ! empty( $args['start_date'] ) ) {
			$query .= " AND r.created_at >= %s";
			$params[] = sanitize_text_field( $args['start_date'] ) . ' 00:00:00';
		}

		if ( ! empty( $args['end_date'] ) ) {
			$query .= " AND r.created_at <= %s";
			$params[] = sanitize_text_field( $args['end_date'] ) . ' 23:59:59';
		}

		// Sanitize order direction
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Sanitizer orderby (allow alphanumeric, underscore, dot)
		$orderby = preg_replace( '/[^a-zA-Z0-9_.]/', '', $args['orderby'] );

		// Ensure orderby column is prefixed with table alias to avoid ambiguity
		if ( strpos( $orderby, '.' ) === false ) {
			$orderby = 'r.' . $orderby;
		}

		$query .= " ORDER BY {$orderby} {$order}";
		$query .= " LIMIT %d OFFSET %d";
		$params[] = $args['limit'];
		$params[] = $args['offset'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return $wpdb->get_results( $wpdb->prepare( $query, $params ) );
	}

	public function get_approved_reviews( $form_id = 0, $limit = 10, $offset = 0 ) {
		return $this->get_reviews( array(
			'form_id' => $form_id,
			'status'  => 'approved',
			'limit'   => $limit,
			'offset'  => $offset,
		) );
	}

	public function get_total_approved_count( $form_id = 0 ) {
		global $wpdb;
		
		if ( ! empty( $form_id ) ) {
			/* phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$results = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'approved' AND form_id = %d",
				intval( $form_id )
			) );
			/* phpcs:enable */
			return (int) $results;
		}
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name WHERE status = 'approved'" );
	}

	public function get_stats( $form_id = null ) {
		global $wpdb;

		if ( ! empty( $form_id ) ) {
			/* phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT AVG(rating) as average, COUNT(id) as total 
				 FROM {$this->table_name} 
				 WHERE form_id = %d AND status = 'approved'",
				intval( $form_id )
			) );
			/* phpcs:enable */
			return $row;
		}

		$stats = array(
			'total'    => 0,
			'approved' => 0,
			'pending'  => 0,
			'rejected' => 0,
			'average'  => 0,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results( "SELECT status, COUNT(*) as count, AVG(rating) as avg_rating FROM $this->table_name GROUP BY status" );

		foreach ( $results as $row ) {
			if ( isset( $stats[ $row->status ] ) ) {
				$stats[ $row->status ] = intval( $row->count );
			}
			$stats['total'] += intval( $row->count );
			
			if ( 'approved' === $row->status ) {
				$stats['average'] = round( floatval( $row->avg_rating ), 1 );
			}
		}

		return (object) $stats;
	}

	/**
	 * Get Rating Breakdown
	 */
	public function get_rating_breakdown( $category_slug ) {
		global $wpdb;

		/* phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT r.rating, COUNT(DISTINCT r.id) as count 
			 FROM {$this->table_name} r
			 INNER JOIN {$this->rel_table} rc ON r.id = rc.review_id
			 INNER JOIN {$this->cat_table} c ON rc.cat_id = c.id
			 WHERE c.slug = %s AND r.status = 'approved' 
			 GROUP BY r.rating",
			$category_slug
		) );
		/* phpcs:enable */
		return $results;
	}

	/**
	 * Update Review Status
	 */
	public function update_status( $id, $status ) {
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
		// Delete relationships
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $this->rel_table, array( 'review_id' => $id ) );
		// Delete meta
		$this->delete_review_meta( $id );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->delete( $this->table_name, array( 'id' => $id ) );
	}

	public function get_review( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function update_review( $id, $data ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->update( $this->table_name, $data, array( 'id' => $id ) );
	}

	/**
	 * Get Total Counts by Status
	 */
	public function get_counts() {
		global $wpdb;
		$results = $wpdb->get_results( "SELECT status, COUNT(id) as count FROM $this->table_name GROUP BY status", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		
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
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert( $this->cat_table, $data );
		return $inserted ? $wpdb->insert_id : false;
	}

	public function get_categories( $args = array() ) {
		global $wpdb;
		/* phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
		return $wpdb->get_results( "SELECT * FROM {$this->cat_table} ORDER BY name ASC" );
		/* phpcs:enable */
	}

	public function get_category( $id ) {
		global $wpdb;
		/* phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->cat_table} WHERE id = %d", $id ) );
		/* phpcs:enable */
	}

	public function update_category( $id, $data ) {
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->update( $this->cat_table, $data, array( 'id' => $id ) );
	}

	public function delete_category( $id ) {
		global $wpdb;
		// Also delete relationships
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $this->rel_table, array( 'cat_id' => $id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->delete( $this->cat_table, array( 'id' => $id ) );
	}

	/**
	 * Get category by slug
	 */
	public function get_category_by_slug( $slug ) {
		global $wpdb;
		/* phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->cat_table} WHERE slug = %s", $slug ) );
		/* phpcs:enable */
	}

	/**
	 * Ensure category exists and return its ID
	 */
	public function ensure_category_exists( $slug, $name = '' ) {
		$cat = $this->get_category_by_slug( $slug );
		if ( $cat ) {
			return $cat->id;
		}

		if ( empty( $name ) ) {
			$name = ucwords( str_replace( '-', ' ', $slug ) );
		}

		return $this->insert_category( array(
			'name' => $name,
			'slug' => $slug,
		) );
	}

	/**
	 * MULTIPLE CATEGORY RELATIONSHIPS
	 */

	public function set_review_categories( $review_id, $cat_ids ) {
		global $wpdb;

		// Clear old relationships
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $this->rel_table, array( 'review_id' => $review_id ) );

		if ( empty( $cat_ids ) ) {
			return true;
		}

		if ( ! is_array( $cat_ids ) ) {
			$cat_ids = array( $cat_ids );
		}

		foreach ( $cat_ids as $cat_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( $this->rel_table, array(
				'review_id' => $review_id,
				'cat_id'    => $cat_id
			) );
		}

		return true;
	}

	public function get_review_categories( $review_id ) {
		global $wpdb;
		/* phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */
		return $wpdb->get_col( $wpdb->prepare( "SELECT cat_id FROM {$this->rel_table} WHERE review_id = %d", $review_id ) );
		/* phpcs:enable */
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

	/**
	 * FORMS METHODS
	 */

	public function insert_form( $data ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert( $this->form_table, $data );
		return $inserted ? $wpdb->insert_id : false;
	}

	public function get_forms() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( "SELECT * FROM {$this->form_table} ORDER BY name ASC" );
	}

	public function get_form( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->form_table} WHERE id = %d", $id ) );
	}

	public function update_form( $id, $data ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->update( $this->form_table, $data, array( 'id' => $id ) );
	}

	public function delete_form( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->delete( $this->form_table, array( 'id' => $id ) );
	}

	/**
	 * META METHODS
	 */
	public function update_review_meta( $review_id, $meta_key, $meta_value ) {
		global $wpdb;
		
		$meta_value = maybe_serialize( $meta_value );
		
		// Check if exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$this->meta_table} WHERE review_id = %d AND meta_key = %s", $review_id, $meta_key ) );
		
		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->update( $this->meta_table, array( 'meta_value' => $meta_value ), array( 'meta_id' => $existing ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->insert( $this->meta_table, array(
				'review_id'  => $review_id,
				'meta_key'   => $meta_key,
				'meta_value' => $meta_value
			) );
		}
	}

	public function get_review_meta( $review_id, $meta_key = '' ) {
		global $wpdb;
		
		if ( ! empty( $meta_key ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$this->meta_table} WHERE review_id = %d AND meta_key = %s", $review_id, $meta_key ) );
			return maybe_unserialize( $value );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$this->meta_table} WHERE review_id = %d", $review_id ) );
			$meta = array();
			foreach ( $results as $row ) {
				$meta[ $row->meta_key ] = maybe_unserialize( $row->meta_value );
			}
			return $meta;
		}
	}

	public function delete_review_meta( $review_id, $meta_key = '' ) {
		global $wpdb;
		$where = array( 'review_id' => $review_id );
		if ( ! empty( $meta_key ) ) {
			$where['meta_key'] = $meta_key;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->delete( $this->meta_table, $where );
	}
}
