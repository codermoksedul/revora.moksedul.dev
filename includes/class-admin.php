<?php
/**
 * Admin Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Revora_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_page_actions' ) );

		// AJAX Handler for Quick Edit
		add_action( 'wp_ajax_revora_quick_edit', array( $this, 'ajax_quick_edit' ) );

		// Dashboard Widget
		add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );

		// Footer Copyright Credit
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_copyright' ) );
	}

	public function enqueue_admin_assets( $hook ) {
		// Only load on plugin pages
		if ( strpos( $hook, 'revora' ) === false ) {
			return;
		}

		wp_enqueue_style( 'revora-google-material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200', array(), null );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'revora-admin', REVORA_URL . 'assets/css/revora-admin.css', array( 'revora-google-material-symbols', 'wp-color-picker' ), REVORA_VERSION );
		wp_enqueue_media();
		wp_enqueue_script( 'revora-admin', REVORA_URL . 'assets/js/revora-admin.js', array( 'jquery', 'wp-color-picker' ), REVORA_VERSION, true );
		
		if ( 'revora_page_revora-forms' === $hook ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
		}

		wp_localize_script( 'revora-admin', 'revora_admin', array(
			'nonce' => wp_create_nonce( 'revora_admin_nonce' ),
		) );
	}

	/**
	 * Admin Footer Copyright Text
	 */
	public function admin_footer_copyright( $text ) {
		$screen = get_current_screen();
		if ( $screen && false !== strpos( $screen->id, 'revora' ) ) {
			return sprintf(
				/* translators: %s: Author link */
				__( 'Thank you for creating with <strong>Revora</strong>. Developed with passion by <a href="%s" target="_blank" rel="noopener noreferrer">Moksedul Islam</a> &bull; v%s', 'revora' ),
				'https://moksedul.com',
				REVORA_VERSION
			);
		}
		return $text;
	}

	/**
	 * Add Menu Pages
	 */
	public function add_menu_pages() {
		add_menu_page(
			__( 'Revora Reviews', 'revora' ),
			__( 'Revora', 'revora' ),
			'manage_options',
			'revora',
			array( $this, 'render_reviews_page' ),
			'dashicons-star-half',
			30
		);

		add_submenu_page(
			'revora',
			__( 'Reviews', 'revora' ),
			__( 'All Reviews', 'revora' ),
			'manage_options',
			'revora',
			array( $this, 'render_reviews_page' )
		);

		add_submenu_page(
			'revora',
			__( 'Forms', 'revora' ),
			__( 'Forms', 'revora' ),
			'manage_options',
			'revora-forms',
			array( $this, 'render_forms_page' )
		);

		add_submenu_page(
			'revora',
			__( 'Revora Settings', 'revora' ),
			__( 'Settings', 'revora' ),
			'manage_options',
			'revora-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register Settings
	 */
	public function register_settings() {
		register_setting( 'revora_settings_group', 'revora_settings', array(
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
			'default'           => $this->get_settings_defaults(),
		) );
	}

	/**
	 * Get Settings Defaults
	 */
	private function get_settings_defaults() {
		return array(
			'website_name'   => get_bloginfo( 'name' ),
			'website_logo'   => '',
			'primary_color'  => '#2563eb',
			'star_color'     => '#f59e0b',
			'card_style'     => 'classic',
			'layout'         => 'grid',
			'enable_schema'  => '1',
			'admin_email'    => get_option( 'admin_email' ),
			'auto_approve'   => '0',
			'show_stars'     => '1',
			'email_subject'  => __( 'New Review Received on {site_title}', 'revora' ),
			'email_template' => __( "Hello Admin,\n\nA new customer review has been submitted for your approval.\n\nAuthor: {author}\nRating: {rating} / 5.0\nTitle: {title}\nReview: {content}\n\nYou can moderate this review here: {admin_url}", 'revora' ),
		);
	}

	/**
	 * Sanitize Settings
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['website_name'] = isset( $input['website_name'] )
			? sanitize_text_field( $input['website_name'] )
			: get_bloginfo( 'name' );

		$sanitized['website_logo'] = isset( $input['website_logo'] )
			? esc_url_raw( $input['website_logo'] )
			: '';

		$sanitized['primary_color'] = isset( $input['primary_color'] )
			? ( sanitize_hex_color( $input['primary_color'] ) ?? '#2563eb' )
			: '#2563eb';

		$sanitized['star_color'] = isset( $input['star_color'] )
			? ( sanitize_hex_color( $input['star_color'] ) ?? '#f59e0b' )
			: '#f59e0b';

		$allowed_card_styles = array( 'classic', 'verified', 'modern', 'boxed', 'horizontal', 'testimonial' );
		$sanitized['card_style'] = isset( $input['card_style'] ) && in_array( $input['card_style'], $allowed_card_styles, true )
			? $input['card_style']
			: 'classic';

		$allowed_layouts = array( 'list', 'grid', 'masonry' );
		$sanitized['layout'] = isset( $input['layout'] ) && in_array( $input['layout'], $allowed_layouts, true )
			? $input['layout']
			: 'grid';

		$sanitized['admin_email'] = isset( $input['admin_email'] )
			? sanitize_email( $input['admin_email'] )
			: get_option( 'admin_email' );

		$sanitized['email_subject'] = isset( $input['email_subject'] )
			? sanitize_text_field( $input['email_subject'] )
			: '';

		$sanitized['email_template'] = isset( $input['email_template'] )
			? sanitize_textarea_field( $input['email_template'] )
			: '';

		// Checkboxes — '1' if checked, '0' if absent
		$sanitized['enable_schema'] = ! empty( $input['enable_schema'] ) ? '1' : '0';
		$sanitized['auto_approve']  = ! empty( $input['auto_approve'] )  ? '1' : '0';
		$sanitized['show_stars']    = ! empty( $input['show_stars'] )    ? '1' : '0';

		return $sanitized;
	}

	private function sanitize_form_fields( $fields ) {
		$sanitized = array();
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) continue;
			
			if ( isset( $field['type'] ) && 'row' === $field['type'] ) {
				$cols = array();
				if ( isset( $field['columns'] ) && is_array( $field['columns'] ) ) {
					foreach ( $field['columns'] as $col ) {
						$cols[] = $this->sanitize_form_fields( is_array( $col ) ? $col : array() );
					}
				}
				$sanitized[] = array(
					'type'    => 'row',
					'columns' => $cols,
				);
			} else {
				$field_type = sanitize_key( $field['type'] ?? 'text' );
				$field_arr = array(
					'type'        => $field_type,
					'label'       => sanitize_text_field( $field['label'] ?? '' ),
					'key'         => sanitize_key( $field['key'] ?? '' ),
					'placeholder' => sanitize_text_field( $field['placeholder'] ?? '' ),
					'required'    => ! empty( $field['required'] ) ? true : false,
				);
				if ( isset( $field['options'] ) ) {
					$field_arr['options'] = sanitize_textarea_field( $field['options'] );
				}
				if ( isset( $field['allowed_types'] ) ) {
					$field_arr['allowed_types'] = sanitize_text_field( $field['allowed_types'] );
				}
				if ( 'submit' === $field_type ) {
					$field_arr['submitLabel'] = sanitize_text_field( $field['submitLabel'] ?? '' );
					$field_arr['submitSize']  = sanitize_text_field( $field['submitSize'] ?? 'medium' );
					$field_arr['submitAlign'] = sanitize_text_field( $field['submitAlign'] ?? 'left' );
				}
				$sanitized[] = $field_arr;
			}
		}
		return $sanitized;
	}

	/**
	 * Handle Form Submissions (Add/Edit/Delete Categories & Settings)
	 */
	public function handle_page_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['revora_add_new'] ) && check_admin_referer( 'revora_add_review', 'revora_nonce' ) ) {
			$db = new Revora_DB();
			$assigned_user_id = isset( $_POST['assigned_user_id'] ) ? intval( $_POST['assigned_user_id'] ) : get_current_user_id();
			$data = array(
				'user_id'       => $assigned_user_id,
				'form_id'       => intval( $_POST['form_id'] ?? 0 ),
				'name'          => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'email'         => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
				'rating'        => floatval( wp_unslash( $_POST['rating'] ?? 5 ) ),
				'title'         => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
				'content'       => sanitize_textarea_field( wp_unslash( $_POST['content'] ?? '' ) ),
				'ip_address'    => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				'status'        => 'approved', // Admin added reviews are approved by default
			);
			
			$inserted = $db->insert_review( $data );
			if ( $inserted ) {
				// Save custom meta fields
				if ( isset( $_POST['meta'] ) && is_array( $_POST['meta'] ) ) {
					foreach ( $_POST['meta'] as $meta_key => $meta_val ) {
						$meta_key = sanitize_key( $meta_key );
						if ( is_array( $meta_val ) ) {
							$clean_val = array_map( 'sanitize_textarea_field', wp_unslash( $meta_val ) );
							$clean_val = implode( ', ', $clean_val );
						} else {
							$clean_val = sanitize_textarea_field( wp_unslash( $meta_val ) );
						}
						$db->update_review_meta( $inserted, $meta_key, $clean_val );
					}
				}

				// Save file uploads
				if ( ! empty( $_FILES ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					$upload_overrides = array( 'test_form' => false );
					foreach ( $_FILES as $file_key => $file_array ) {
						if ( 0 === strpos( $file_key, 'meta_file_' ) && ! empty( $file_array['name'] ) ) {
							$meta_key = str_replace( 'meta_file_', '', $file_key );
							$movefile = wp_handle_upload( $file_array, $upload_overrides );
							if ( $movefile && ! isset( $movefile['error'] ) ) {
								$db->update_review_meta( $inserted, $meta_key, $movefile['url'] );
							}
						}
					}
				}

				wp_safe_redirect( admin_url( 'admin.php?page=revora&message=added' ) );
				exit;
			}
		}

		// Handle Review Update
		if ( isset( $_POST['revora_edit_review'] ) && check_admin_referer( 'revora_edit_review', 'revora_nonce' ) ) {
			$db = new Revora_DB();
			$id = isset( $_POST['review_id'] ) ? intval( wp_unslash( $_POST['review_id'] ) ) : 0;
			$assigned_user_id = isset( $_POST['assigned_user_id'] ) ? intval( $_POST['assigned_user_id'] ) : 0;
			$data = array(
				'user_id'       => $assigned_user_id,
				'form_id'       => intval( $_POST['form_id'] ?? 0 ),
				'name'          => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'email'         => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
				'rating'        => floatval( wp_unslash( $_POST['rating'] ?? 5 ) ),
				'title'         => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
				'content'       => sanitize_textarea_field( wp_unslash( $_POST['content'] ?? '' ) ),
				'status'        => sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) ),
			);

			$updated = $db->update_review( $id, $data );

			// Save custom meta fields
			if ( isset( $_POST['meta'] ) && is_array( $_POST['meta'] ) ) {
				foreach ( $_POST['meta'] as $meta_key => $meta_val ) {
					$meta_key = sanitize_key( $meta_key );
					if ( is_array( $meta_val ) ) {
						$clean_val = array_map( 'sanitize_textarea_field', wp_unslash( $meta_val ) );
						$clean_val = implode( ', ', $clean_val );
					} else {
						$clean_val = sanitize_textarea_field( wp_unslash( $meta_val ) );
					}
					$db->update_review_meta( $id, $meta_key, $clean_val );
				}
			}

			// Save file uploads
			if ( ! empty( $_FILES ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				$upload_overrides = array( 'test_form' => false );
				foreach ( $_FILES as $file_key => $file_array ) {
					if ( 0 === strpos( $file_key, 'meta_file_' ) && ! empty( $file_array['name'] ) ) {
						$meta_key = str_replace( 'meta_file_', '', $file_key );
						$movefile = wp_handle_upload( $file_array, $upload_overrides );
						if ( $movefile && ! isset( $movefile['error'] ) ) {
							$db->update_review_meta( $id, $meta_key, $movefile['url'] );
						}
					}
				}
			}

			if ( $updated !== false ) {
				wp_safe_redirect( admin_url( 'admin.php?page=revora&message=updated' ) );
				exit;
			}
		}

		// Handle Category Add
		if ( isset( $_POST['revora_add_category'] ) && check_admin_referer( 'revora_add_cat_nonce', 'revora_cat_nonce' ) ) {
			$db = new Revora_DB();
			$name = sanitize_text_field( wp_unslash( $_POST['cat_name'] ?? '' ) );
			$slug = ! empty( $_POST['cat_slug'] ) ? sanitize_title( wp_unslash( $_POST['cat_slug'] ) ) : sanitize_title( $name );
			
			$data = array(
				'parent_id'   => intval( wp_unslash( $_POST['parent_id'] ?? 0 ) ),
				'name'        => $name,
				'slug'        => $slug,
				'description' => sanitize_textarea_field( wp_unslash( $_POST['cat_description'] ?? '' ) ),
			);

			$inserted = $db->insert_category( $data );
			if ( $inserted ) {
				wp_safe_redirect( admin_url( 'admin.php?page=revora-categories&message=added' ) );
				exit;
			}
		}

		// Handle Category Update
		if ( isset( $_POST['revora_edit_category'] ) && check_admin_referer( 'revora_edit_cat_nonce', 'revora_cat_nonce' ) ) {
			$db = new Revora_DB();
			$id = isset( $_POST['cat_id'] ) ? intval( wp_unslash( $_POST['cat_id'] ) ) : 0;
			$data = array(
				'name'        => sanitize_text_field( wp_unslash( $_POST['cat_name'] ?? '' ) ),
				'slug'        => sanitize_title( wp_unslash( $_POST['cat_slug'] ?? '' ) ),
				'description' => sanitize_textarea_field( wp_unslash( $_POST['cat_description'] ?? '' ) ),
			);

			$updated = $db->update_category( $id, $data );
			if ( $updated !== false ) {
				wp_safe_redirect( admin_url( 'admin.php?page=revora-categories&message=updated' ) );
				exit;
			}
		}

		// Handle Form Save
		if ( isset( $_POST['revora_save_form_action'] ) && check_admin_referer( 'revora_save_form', 'revora_form_nonce' ) ) {
			$db = new Revora_DB();
			
			$id = isset( $_POST['form_id'] ) ? intval( wp_unslash( $_POST['form_id'] ) ) : 0;
			$name = sanitize_text_field( wp_unslash( $_POST['form_name'] ?? '' ) );
			
			// Fields are now sent as a JSON string to support nested structures (columns)
			$fields_json = isset( $_POST['fields_json'] ) ? wp_unslash( $_POST['fields_json'] ) : '[]';
			$fields_array = json_decode( $fields_json, true );
			if ( ! is_array( $fields_array ) ) {
				$fields_array = array();
			}

			// Recursive sanitization for fields
			$sanitized_fields = $this->sanitize_form_fields( $fields_array );

			$settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();
			$sanitized_settings = array(
				'submit_text'       => sanitize_text_field( $settings['submit_text'] ?? 'Submit Review' ),
				'success_message'   => sanitize_textarea_field( $settings['success_message'] ?? 'Thank you for your review!' ),
				'enable_share_card' => isset( $settings['enable_share_card'] ) ? '1' : '0',
			);

			$data = array(
				'name'     => $name,
				'fields'   => wp_json_encode( $sanitized_fields ),
				'settings' => wp_json_encode( $sanitized_settings ),
			);

			if ( $id ) {
				$db->update_form( $id, $data );
				wp_safe_redirect( admin_url( 'admin.php?page=revora-forms&message=updated' ) );
			} else {
				$db->insert_form( $data );
				wp_safe_redirect( admin_url( 'admin.php?page=revora-forms&message=added' ) );
			}
			exit;
		}

		// Handle Form Delete
		if ( isset( $_GET['action'], $_GET['form_id'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['action'] ) ) && isset( $_GET['page'] ) && 'revora-forms' === $_GET['page'] ) {
			$form_id = intval( wp_unslash( $_GET['form_id'] ) );
			check_admin_referer( 'revora_delete_form_' . $form_id );
			$db = new Revora_DB();
			$db->delete_form( $form_id );
			wp_safe_redirect( admin_url( 'admin.php?page=revora-forms&message=deleted' ) );
			exit;
		}

		// Handle Category Delete (from list table)
		if ( isset( $_GET['action'], $_GET['cat_id'] ) && 'delete_cat' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			$cat_id = intval( wp_unslash( $_GET['cat_id'] ) );
			check_admin_referer( 'revora_delete_cat_' . $cat_id );
			$db = new Revora_DB();
			$db->delete_category( $cat_id );
			wp_safe_redirect( admin_url( 'admin.php?page=revora-categories&message=deleted' ) );
			exit;
		}

		// Handle Review Duplicate
		if ( isset( $_GET['action'], $_GET['review_id'] ) && 'duplicate' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			$review_id = intval( wp_unslash( $_GET['review_id'] ) );
			check_admin_referer( 'revora_duplicate_' . $review_id );
			$db = new Revora_DB();
			$db->duplicate_review( $review_id );
			wp_safe_redirect( admin_url( 'admin.php?page=revora&message=duplicated' ) );
			exit;
		}

		// Handle Review Actions (Approve/Reject/Delete)
		if ( isset( $_GET['action'], $_GET['review_id'] ) ) {
			$id     = intval( wp_unslash( $_GET['review_id'] ) );
			$action = sanitize_key( wp_unslash( $_GET['action'] ) );
			$db     = new Revora_DB();

			if ( 'approve' === $action ) {
				check_admin_referer( 'revora_approve_' . $id );
				$db->update_review( $id, array( 'status' => 'approved' ) );
				wp_safe_redirect( admin_url( 'admin.php?page=revora&message=approved' ) );
				exit;
			}

			if ( 'reject' === $action ) {
				check_admin_referer( 'revora_reject_' . $id );
				$db->update_review( $id, array( 'status' => 'rejected' ) );
				wp_safe_redirect( admin_url( 'admin.php?page=revora&message=rejected' ) );
				exit;
			}

			if ( 'delete' === $action ) {
				check_admin_referer( 'revora_delete_' . $id );
				$db->delete_review( $id );
				wp_safe_redirect( admin_url( 'admin.php?page=revora&message=deleted' ) );
				exit;
			}
		}

		// Handle Load Demo Reviews
		if ( isset( $_GET['action'] ) && 'load_demo' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			check_admin_referer( 'revora_load_demo' );
			$this->load_demo_reviews();
			wp_safe_redirect( admin_url( 'admin.php?page=revora&message=demo_loaded' ) );
			exit;
		}

		// Handle Filtered Export (CSV / JSON via Modal)
		if ( isset( $_POST['revora_export_reviews'] ) && check_admin_referer( 'revora_export_reviews_action', 'revora_export_nonce' ) ) {
			$form_id    = isset( $_POST['export_form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['export_form_id'] ) ) : '-1';
			$status     = isset( $_POST['export_status'] ) ? sanitize_text_field( wp_unslash( $_POST['export_status'] ) ) : 'all';
			$min_rating = isset( $_POST['export_rating'] ) ? floatval( $_POST['export_rating'] ) : 0;
			$start_date = ! empty( $_POST['export_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['export_start_date'] ) ) : '';
			$end_date   = ! empty( $_POST['export_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['export_end_date'] ) ) : '';
			$format     = isset( $_POST['export_format'] ) && 'json' === $_POST['export_format'] ? 'json' : 'csv';

			$db = new Revora_DB();
			$reviews = $db->get_reviews( array(
				'form_id'    => $form_id,
				'status'     => $status,
				'min_rating' => $min_rating,
				'start_date' => $start_date,
				'end_date'   => $end_date,
				'limit'      => 100000,
			) );

			if ( 'json' === $format ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename=revora-reviews-' . gmdate( 'Y-m-d' ) . '.json' );
				
				$export_data = array();
				foreach ( $reviews as $r ) {
					$export_data[] = array(
						'id'         => (int) $r->id,
						'form_id'    => (int) $r->form_id,
						'author'     => $r->name,
						'email'      => $r->email,
						'rating'     => (float) $r->rating,
						'title'      => $r->title,
						'content'    => $r->content,
						'status'     => $r->status,
						'created_at' => $r->created_at,
					);
				}
				echo wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
				exit;
			} else {
				header( 'Content-Type: text/csv; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename=revora-reviews-' . gmdate( 'Y-m-d' ) . '.csv' );
				
				$output = fopen( 'php://output', 'w' );
				fputcsv( $output, array( 'ID', 'Form ID', 'Author Name', 'Author Email', 'Rating', 'Review Title', 'Review Content', 'Status', 'Date' ) );
				
				foreach ( $reviews as $r ) {
					fputcsv( $output, array(
						$r->id,
						$r->form_id,
						$r->name,
						$r->email,
						$r->rating,
						$r->title,
						$r->content,
						$r->status,
						$r->created_at,
					) );
				}
				fclose( $output );
				exit;
			}
		}

		// Handle Direct CSV Export fallback
		if ( isset( $_GET['action'] ) && 'export_csv' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			check_admin_referer( 'revora_export_csv' );
			$db = new Revora_DB();
			$reviews = $db->get_reviews( array( 'status' => '', 'limit' => 100000 ) );
			
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=revora-reviews-' . gmdate( 'Y-m-d' ) . '.csv' );
			
			$output = fopen( 'php://output', 'w' );
			fputcsv( $output, array( 'ID', 'Form ID', 'Author Name', 'Author Email', 'Rating', 'Review Title', 'Review Content', 'Status', 'Date' ) );
			
			foreach ( $reviews as $r ) {
				fputcsv( $output, array(
					$r->id,
					$r->form_id,
					$r->name,
					$r->email,
					$r->rating,
					$r->title,
					$r->content,
					$r->status,
					$r->created_at,
				) );
			}
			fclose( $output );
			exit;
		}

		// Handle Sample CSV Download
		if ( isset( $_GET['action'] ) && 'download_sample_csv' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			check_admin_referer( 'revora_sample_csv' );
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=revora-sample-reviews.csv' );
			
			$output = fopen( 'php://output', 'w' );
			fputcsv( $output, array( 'Author Name', 'Author Email', 'Rating', 'Review Title', 'Review Content', 'Status' ) );
			fputcsv( $output, array( 'John Doe', 'john@example.com', '5.0', 'Outstanding Service!', 'The service exceeded all my expectations.', 'approved' ) );
			fputcsv( $output, array( 'Sarah Smith', 'sarah@example.com', '4.5', 'Great Quality Product', 'Really liked the fast support and product build.', 'approved' ) );
			fputcsv( $output, array( 'Michael Brown', 'michael@example.com', '4.0', 'Very Good Experience', 'Smooth and easy to work with.', 'approved' ) );
			fclose( $output );
			exit;
		}

		// Handle Review Import (CSV / JSON)
		if ( isset( $_POST['revora_import_reviews'] ) && check_admin_referer( 'revora_import_reviews_action', 'revora_import_nonce' ) ) {
			if ( ! empty( $_FILES['import_file']['tmp_name'] ) ) {
				$file = $_FILES['import_file']['tmp_name'];
				$filename = $_FILES['import_file']['name'];
				$file_ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
				$target_form_id = isset( $_POST['import_form_id'] ) ? intval( $_POST['import_form_id'] ) : 0;
				$imported_count = 0;
				$db = new Revora_DB();

				if ( 'csv' === $file_ext ) {
					$handle = fopen( $file, 'r' );
					if ( false !== $handle ) {
						$headers = fgetcsv( $handle, 2000, ',' );
						if ( is_array( $headers ) ) {
							$headers = array_map( function( $h ) {
								return strtolower( trim( $h ) );
							}, $headers );

							while ( ( $row = fgetcsv( $handle, 4000, ',' ) ) !== false ) {
								if ( empty( $row ) || ( count( $row ) === 1 && empty( $row[0] ) ) ) continue;
								
								$data = array(
									'user_id'    => get_current_user_id(),
									'form_id'    => $target_form_id,
									'name'       => '',
									'email'      => '',
									'rating'     => 5.0,
									'title'      => '',
									'content'    => '',
									'status'     => 'approved',
									'ip_address' => '',
								);

								foreach ( $headers as $idx => $header_name ) {
									$val = isset( $row[ $idx ] ) ? trim( $row[ $idx ] ) : '';
									if ( in_array( $header_name, array( 'name', 'author', 'author name', 'author_name' ), true ) ) {
										$data['name'] = sanitize_text_field( $val );
									} elseif ( in_array( $header_name, array( 'email', 'author email', 'author_email' ), true ) ) {
										$data['email'] = sanitize_email( $val );
									} elseif ( in_array( $header_name, array( 'rating', 'stars', 'score' ), true ) ) {
										$data['rating'] = floatval( $val );
									} elseif ( in_array( $header_name, array( 'title', 'review title', 'headline' ), true ) ) {
										$data['title'] = sanitize_text_field( $val );
									} elseif ( in_array( $header_name, array( 'content', 'review', 'review content', 'body', 'comment' ), true ) ) {
										$data['content'] = sanitize_textarea_field( $val );
									} elseif ( in_array( $header_name, array( 'status' ), true ) ) {
										$data['status'] = in_array( strtolower( $val ), array( 'approved', 'pending', 'rejected' ), true ) ? strtolower( $val ) : 'approved';
									} elseif ( in_array( $header_name, array( 'form_id', 'form id' ), true ) && empty( $target_form_id ) ) {
										$data['form_id'] = intval( $val );
									}
								}

								if ( empty( $data['name'] ) ) $data['name'] = 'Anonymous';
								if ( empty( $data['email'] ) ) $data['email'] = 'user@example.com';
								if ( empty( $data['title'] ) ) $data['title'] = 'Customer Review';

								if ( $db->insert_review( $data ) ) {
									$imported_count++;
								}
							}
						}
						fclose( $handle );
					}
				} elseif ( 'json' === $file_ext ) {
					$content = file_get_contents( $file );
					$json_data = json_decode( $content, true );
					if ( is_array( $json_data ) ) {
						foreach ( $json_data as $item ) {
							$data = array(
								'user_id'    => get_current_user_id(),
								'form_id'    => ! empty( $target_form_id ) ? $target_form_id : intval( $item['form_id'] ?? 0 ),
								'name'       => sanitize_text_field( $item['name'] ?? $item['author'] ?? 'Anonymous' ),
								'email'      => sanitize_email( $item['email'] ?? 'user@example.com' ),
								'rating'     => floatval( $item['rating'] ?? $item['stars'] ?? 5.0 ),
								'title'      => sanitize_text_field( $item['title'] ?? 'Customer Review' ),
								'content'    => sanitize_textarea_field( $item['content'] ?? $item['review'] ?? '' ),
								'status'     => in_array( strtolower( $item['status'] ?? '' ), array( 'approved', 'pending', 'rejected' ), true ) ? strtolower( $item['status'] ) : 'approved',
								'ip_address' => '',
							);
							if ( $db->insert_review( $data ) ) {
								$imported_count++;
							}
						}
					}
				}

				wp_safe_redirect( admin_url( 'admin.php?page=revora&message=imported&count=' . $imported_count ) );
				exit;
			}
		}
	}

	/**
	 * Load Demo Reviews
	 */
	private function load_demo_reviews() {
		$db = new Revora_DB();
		$forms = $db->get_forms();
		$form_id = ! empty( $forms ) ? $forms[0]->id : 0;

		$first_names = array( 'John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'Daniel', 'Jessica', 'James', 'Ashley' );
		$last_names  = array( 'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez' );
		$adjectives  = array( 'Amazing', 'Great', 'Good', 'Excellent', 'Fantastic', 'Awesome', 'Outstanding', 'Superb', 'Wonderful', 'Brilliant' );
		$nouns       = array( 'Service', 'Product', 'Experience', 'Support', 'Quality', 'Value', 'Performance', 'Design', 'Features', 'Reliability' );
		
		for ( $i = 0; $i < 50; $i++ ) {
			$name = $first_names[ array_rand( $first_names ) ] . ' ' . $last_names[ array_rand( $last_names ) ];
			$title = $adjectives[ array_rand( $adjectives ) ] . ' ' . $nouns[ array_rand( $nouns ) ] . '!';
			
			$data = array(
				'user_id'    => get_current_user_id(),
				'form_id'    => $form_id,
				'name'       => $name,
				'email'      => strtolower( str_replace( ' ', '.', $name ) ) . rand( 1, 999 ) . '@example.com',
				'rating'     => rand( 4, 5 ),
				'title'      => $title,
				'content'    => 'I was really impressed with the ' . strtolower( $nouns[ array_rand( $nouns ) ] ) . '. The ' . strtolower( $nouns[ array_rand( $nouns ) ] ) . ' was also ' . strtolower( $adjectives[ array_rand( $adjectives ) ] ) . '. Highly recommended!',
				'ip_address' => '127.0.0.1',
				'status'     => 'approved',
			);

			$db->insert_review( $data );
		}
	}

	/**
	 * AJAX Quick Edit Handler
	 */
	public function ajax_quick_edit() {
		check_ajax_referer( 'revora_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$id = isset( $_POST['review_id'] ) ? intval( $_POST['review_id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( 'Invalid review ID' );
		}

		$data = array();
		if ( isset( $_POST['status'] ) ) {
			$data['status'] = sanitize_text_field( wp_unslash( $_POST['status'] ) );
		}
		if ( isset( $_POST['rating'] ) ) {
			$data['rating'] = intval( wp_unslash( $_POST['rating'] ) );
		}

		if ( empty( $data ) ) {
			wp_send_json_error( 'No data to update' );
		}

		$db      = new Revora_DB();
		$updated = $db->update_review( $id, $data );

		if ( $updated !== false ) {
			wp_send_json_success( 'Review updated successfully' );
		} else {
			wp_send_json_error( 'Failed to update review' );
		}
	}

	/**
	 * Render Reviews Page
	 */
	public function render_reviews_page() {
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

		if ( 'add' === $action ) {
			$this->render_add_new_page();
			return;
		}

		if ( 'edit' === $action && isset( $_GET['review_id'] ) ) {
			$this->render_edit_page( intval( wp_unslash( $_GET['review_id'] ) ) );
			return;
		}

		$table = new Revora_Review_List_Table();
		$table->prepare_items();

		// Handle bulk/row actions
		$message = '';
		$msg_type = isset( $_REQUEST['message'] ) ? sanitize_key( wp_unslash( $_REQUEST['message'] ) ) : '';
		if ( 'added' === $msg_type ) {
			$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Review added successfully.', 'revora' ) . '</p></div>';
		} elseif ( 'updated' === $msg_type ) {
			$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Review updated successfully.', 'revora' ) . '</p></div>';
		} elseif ( 'approved' === $msg_type ) {
			$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Review approved successfully.', 'revora' ) . '</p></div>';
		} elseif ( 'rejected' === $msg_type ) {
			$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Review rejected successfully.', 'revora' ) . '</p></div>';
		} elseif ( 'deleted' === $msg_type ) {
			$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Review deleted successfully.', 'revora' ) . '</p></div>';
		} elseif ( 'duplicated' === $msg_type ) {
			$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Review duplicated successfully.', 'revora' ) . '</p></div>';
		} elseif ( 'demo_loaded' === $msg_type ) {
			$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( '50 demo reviews have been loaded successfully.', 'revora' ) . '</p></div>';
		}

		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] && ! in_array( $_REQUEST['action'], array( 'add' ) ) ) {
			// Verify nonce for bulk actions
			check_admin_referer( 'bulk-reviews' );

			$bulk_action = sanitize_key( wp_unslash( $_REQUEST['action'] ) );
			$ids         = isset( $_REQUEST['review'] ) ? array_map( 'intval', wp_unslash( (array) $_REQUEST['review'] ) ) : array();

			if ( ! empty( $ids ) ) {
				$db = new Revora_DB();
				foreach ( $ids as $id ) {
					if ( 'approve' === $bulk_action ) {
						$db->update_status( $id, 'approved' );
					} elseif ( 'reject' === $bulk_action ) {
						$db->update_status( $id, 'rejected' );
					} elseif ( 'delete' === $bulk_action ) {
						$db->delete_review( $id );
					}
				}
				$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Action applied successfully.', 'revora' ) . '</p></div>';
			}
		}

		$db = new Revora_DB();
		$stats = $db->get_stats();
		$total_reviews = isset( $stats->total ) ? (int) $stats->total : 0;
		?>
		<div class="wrap revora-admin-wrap">
			<div class="revora-topbar-header">
				<div class="revora-topbar-title-wrap">
					<h1 class="wp-heading-inline"><?php esc_html_e( 'Revora Reviews', 'revora' ); ?></h1>
				</div>
				<div class="revora-topbar-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=revora&action=add' ) ); ?>" class="button button-primary revora-btn-primary">
						<span class="material-symbols-outlined">add</span> <?php esc_html_e( 'Add Review', 'revora' ); ?>
					</a>
					<button type="button" class="button revora-btn-secondary" id="revora-open-import-modal">
						<span class="material-symbols-outlined">file_upload</span> <?php esc_html_e( 'Import', 'revora' ); ?>
					</button>
					<button type="button" class="button revora-btn-secondary" id="revora-open-export-modal">
						<span class="material-symbols-outlined">download</span> <?php esc_html_e( 'Export', 'revora' ); ?>
					</button>
					<?php if ( 0 === $total_reviews ) : ?>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=revora&action=load_demo' ), 'revora_load_demo' ) ); ?>" class="button revora-btn-secondary">
							<span class="material-symbols-outlined">auto_awesome</span> <?php esc_html_e( 'Load Demo', 'revora' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
			<hr class="wp-header-end">

			<?php echo wp_kses_post( $message ); ?>

			<form id="revora-reviews-filter" method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['page'] ?? '' ) ) ); ?>" />
				<?php
				wp_nonce_field( 'bulk-reviews' );
				$table->views();
				$table->search_box( esc_html__( 'Search Reviews', 'revora' ), 'revora-search' );
				$table->display();
				?>
			</form>
		</div>

		<!-- Import Reviews Modal -->
		<div id="revora-import-modal" class="revora-modal-backdrop" style="display:none;">
			<div class="revora-modal-dialog">
				<div class="revora-modal-header">
					<div class="revora-modal-title">
						<span class="material-symbols-outlined">file_upload</span> <?php esc_html_e( 'Import Reviews', 'revora' ); ?>
					</div>
					<button type="button" class="revora-modal-close" id="revora-close-import-modal">&times;</button>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=revora' ) ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( 'revora_import_reviews_action', 'revora_import_nonce' ); ?>
					<input type="hidden" name="revora_import_reviews" value="1">
					<div class="revora-modal-body">
						<div class="revora-field-group">
							<label class="revora-field-label" for="import_form_id"><?php esc_html_e( 'Assign Imported Reviews To Form', 'revora' ); ?></label>
							<select name="import_form_id" id="import_form_id" class="widefat">
								<option value="0"><?php esc_html_e( 'Auto-detect from CSV / Default Form', 'revora' ); ?></option>
								<?php 
								$all_forms = $db->get_forms();
								foreach ( $all_forms as $af ) : 
								?>
									<option value="<?php echo esc_attr( $af->id ); ?>"><?php echo esc_html( $af->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="revora-field-group">
							<label class="revora-field-label"><?php esc_html_e( 'Choose File (CSV or JSON)', 'revora' ); ?></label>
							<div class="revora-file-dropzone">
								<input type="file" name="import_file" id="import_file" accept=".csv, .json" required>
								<div class="revora-dropzone-inner">
									<span class="material-symbols-outlined" style="font-size:32px; color:#3b82f6;">cloud_upload</span>
									<span class="revora-dropzone-text"><?php esc_html_e( 'Click to browse or drag & drop .CSV or .JSON file', 'revora' ); ?></span>
								</div>
							</div>

							<!-- Live Data Preview Container -->
							<div id="revora-import-preview-wrap" style="display:none; margin-top: 14px;">
								<div class="revora-preview-header">
									<div class="revora-preview-info">
										<span class="material-symbols-outlined" style="color:#10b981; font-size:18px;">check_circle</span>
										<span id="revora-preview-filename" class="revora-preview-filename"></span>
										<span id="revora-preview-count" class="revora-preview-count-badge"></span>
									</div>
									<button type="button" id="revora-preview-reset-btn" class="revora-preview-reset-btn"><?php esc_html_e( 'Change File', 'revora' ); ?></button>
								</div>
								<div class="revora-preview-table-container">
									<table class="revora-preview-table">
										<thead>
											<tr>
												<th><?php esc_html_e( 'Author', 'revora' ); ?></th>
												<th><?php esc_html_e( 'Rating', 'revora' ); ?></th>
												<th><?php esc_html_e( 'Title / Content', 'revora' ); ?></th>
												<th><?php esc_html_e( 'Status', 'revora' ); ?></th>
											</tr>
										</thead>
										<tbody id="revora-preview-tbody">
										</tbody>
									</table>
								</div>
								<div id="revora-preview-more-note" class="revora-preview-more-note"></div>
							</div>
						</div>

						<div class="revora-sample-download">
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=revora&action=download_sample_csv' ), 'revora_sample_csv' ) ); ?>" class="revora-link-sample">
								<span class="material-symbols-outlined" style="font-size:16px;">description</span> <?php esc_html_e( 'Download Sample CSV Template', 'revora' ); ?>
							</a>
						</div>
					</div>
					<div class="revora-modal-footer">
						<button type="button" class="button" id="revora-cancel-import-modal"><?php esc_html_e( 'Cancel', 'revora' ); ?></button>
						<button type="submit" id="revora-start-import-btn" class="button button-primary">
							<span class="revora-btn-text"><?php esc_html_e( 'Start Import', 'revora' ); ?></span>
						</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Export Reviews Modal -->
		<div id="revora-export-modal" class="revora-modal-backdrop" style="display:none;">
			<div class="revora-modal-dialog">
				<div class="revora-modal-header">
					<div class="revora-modal-title">
						<span class="material-symbols-outlined">download</span> <?php esc_html_e( 'Export Reviews', 'revora' ); ?>
					</div>
					<button type="button" class="revora-modal-close" id="revora-close-export-modal">&times;</button>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=revora' ) ); ?>">
					<?php wp_nonce_field( 'revora_export_reviews_action', 'revora_export_nonce' ); ?>
					<input type="hidden" name="revora_export_reviews" value="1">
					<div class="revora-modal-body">
						<div class="revora-field-group">
							<label class="revora-field-label" for="export_form_id"><?php esc_html_e( 'Select Form', 'revora' ); ?></label>
							<select name="export_form_id" id="export_form_id" class="widefat">
								<option value="-1"><?php esc_html_e( 'All Forms', 'revora' ); ?></option>
								<option value="0"><?php esc_html_e( 'Default Form (Unassigned)', 'revora' ); ?></option>
								<?php 
								$all_forms = $db->get_forms();
								foreach ( $all_forms as $af ) : 
								?>
									<option value="<?php echo esc_attr( $af->id ); ?>"><?php echo esc_html( $af->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="revora-form-row-2col" style="display:flex; gap:12px; margin-bottom:12px;">
							<div class="revora-field-group" style="flex:1;">
								<label class="revora-field-label" for="export_status"><?php esc_html_e( 'Review Status', 'revora' ); ?></label>
								<select name="export_status" id="export_status" class="widefat">
									<option value="all"><?php esc_html_e( 'All Statuses', 'revora' ); ?></option>
									<option value="approved"><?php esc_html_e( 'Approved Only', 'revora' ); ?></option>
									<option value="pending"><?php esc_html_e( 'Pending Only', 'revora' ); ?></option>
									<option value="rejected"><?php esc_html_e( 'Rejected Only', 'revora' ); ?></option>
								</select>
							</div>
							<div class="revora-field-group" style="flex:1;">
								<label class="revora-field-label" for="export_rating"><?php esc_html_e( 'Rating Filter', 'revora' ); ?></label>
								<select name="export_rating" id="export_rating" class="widefat">
									<option value="0"><?php esc_html_e( 'All Ratings', 'revora' ); ?></option>
									<option value="5.0"><?php esc_html_e( '5 Stars Only', 'revora' ); ?></option>
									<option value="4.0"><?php esc_html_e( '4 Stars & Above', 'revora' ); ?></option>
									<option value="3.0"><?php esc_html_e( '3 Stars & Above', 'revora' ); ?></option>
									<option value="2.0"><?php esc_html_e( '2 Stars & Above', 'revora' ); ?></option>
									<option value="1.0"><?php esc_html_e( '1 Star & Above', 'revora' ); ?></option>
								</select>
							</div>
						</div>

						<div class="revora-form-row-2col" style="display:flex; gap:12px; margin-bottom:12px;">
							<div class="revora-field-group" style="flex:1;">
								<label class="revora-field-label" for="export_start_date"><?php esc_html_e( 'From Date', 'revora' ); ?></label>
								<input type="date" name="export_start_date" id="export_start_date" class="widefat">
							</div>
							<div class="revora-field-group" style="flex:1;">
								<label class="revora-field-label" for="export_end_date"><?php esc_html_e( 'To Date', 'revora' ); ?></label>
								<input type="date" name="export_end_date" id="export_end_date" class="widefat">
							</div>
						</div>

						<div class="revora-field-group">
							<label class="revora-field-label"><?php esc_html_e( 'Export Format', 'revora' ); ?></label>
							<div class="revora-export-format-group">
								<label class="revora-format-radio-label">
									<input type="radio" name="export_format" value="csv" checked>
									<span class="revora-format-pill"><strong>CSV</strong> (.csv)</span>
								</label>
								<label class="revora-format-radio-label">
									<input type="radio" name="export_format" value="json">
									<span class="revora-format-pill"><strong>JSON</strong> (.json)</span>
								</label>
							</div>
						</div>
					</div>
					<div class="revora-modal-footer">
						<button type="button" class="button" id="revora-cancel-export-modal"><?php esc_html_e( 'Cancel', 'revora' ); ?></button>
						<button type="submit" id="revora-start-export-btn" class="button button-primary revora-btn-primary">
							<span class="material-symbols-outlined" style="font-size:16px; margin-right:4px;">download</span>
							<span><?php esc_html_e( 'Download Export', 'revora' ); ?></span>
						</button>
					</div>
				</form>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Import Modal Handlers
			$('#revora-open-import-modal').on('click', function(e) {
				e.preventDefault();
				$('#revora-import-modal').fadeIn(150);
			});
			$('#revora-close-import-modal, #revora-cancel-import-modal').on('click', function() {
				$('#revora-import-modal').fadeOut(150);
			});

			// Export Modal Handlers
			$('#revora-open-export-modal').on('click', function(e) {
				e.preventDefault();
				$('#revora-export-modal').fadeIn(150);
			});
			$('#revora-close-export-modal, #revora-cancel-export-modal').on('click', function() {
				$('#revora-export-modal').fadeOut(150);
			});

			$(document).on('keyup', function(e) {
				if (e.key === "Escape") {
					$('#revora-import-modal').fadeOut(150);
					$('#revora-export-modal').fadeOut(150);
				}
			});

			// Form submit loading state
			$('form[action*="admin.php?page=revora"]').has('#revora-start-import-btn').on('submit', function() {
				var $btn = $('#revora-start-import-btn');
				$btn.prop('disabled', true).addClass('is-loading');
				$btn.html('<span class="revora-spinner"></span> ' + <?php echo wp_json_encode( esc_html__( 'Importing...', 'revora' ) ); ?>);
				$('#revora-cancel-import-modal').prop('disabled', true);
			});

			// Export Form submit loading state & auto-hide
			$('form[action*="admin.php?page=revora"]').has('#revora-start-export-btn').on('submit', function() {
				var $btn = $('#revora-start-export-btn');
				var origHtml = $btn.html();
				$btn.prop('disabled', true).addClass('is-loading');
				$btn.html('<span class="revora-spinner"></span> ' + <?php echo wp_json_encode( esc_html__( 'Exporting...', 'revora' ) ); ?>);
				$('#revora-cancel-export-modal').prop('disabled', true);

				setTimeout(function() {
					$('#revora-export-modal').fadeOut(200, function() {
						$btn.prop('disabled', false).removeClass('is-loading').html(origHtml);
						$('#revora-cancel-export-modal').prop('disabled', false);
					});
				}, 1000);
			});

			function escapeHtml(str) {
				if (!str) return '';
				return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
			}

			// Live File Preview
			$('#import_file').on('change', function() {
				var file = this.files[0];
				if (!file) return;

				var filename = file.name;
				var ext = filename.split('.').pop().toLowerCase();

				if (ext !== 'csv' && ext !== 'json') {
					alert('Please choose a valid .CSV or .JSON file.');
					return;
				}

				var reader = new FileReader();
				reader.onload = function(evt) {
					var content = evt.target.result;
					var rows = [];

					if (ext === 'csv') {
						rows = parseCSVContent(content);
					} else if (ext === 'json') {
						try {
							var json = JSON.parse(content);
							if (Array.isArray(json)) {
								rows = json.map(function(item) {
									return {
										name: item.name || item.author || 'Anonymous',
										rating: item.rating || item.stars || '5.0',
										title: item.title || item.headline || 'Customer Review',
										content: item.content || item.review || '',
										status: item.status || 'approved'
									};
								});
							}
						} catch(err) {
							alert('Invalid JSON file format.');
							return;
						}
					}

					if (rows && rows.length > 0) {
						renderImportPreview(filename, rows);
					} else {
						alert('No valid review records found in this file.');
					}
				};
				reader.readAsText(file);
			});

			function parseCSVContent(text) {
				var lines = text.split(/\r\n|\n/);
				if (lines.length < 2) return [];

				var headers = lines[0].split(',').map(function(h) {
					return h.replace(/^["']|["']$/g, '').trim().toLowerCase();
				});

				var parsedRows = [];
				for (var i = 1; i < lines.length; i++) {
					var line = lines[i].trim();
					if (!line) continue;

					var values = line.split(',');
					var rowData = {
						name: 'Anonymous',
						rating: '5.0',
						title: 'Customer Review',
						content: '',
						status: 'approved'
					};

					headers.forEach(function(h, idx) {
						var val = values[idx] ? values[idx].replace(/^["']|["']$/g, '').trim() : '';
						if (h.indexOf('name') !== -1 || h.indexOf('author') !== -1) rowData.name = val;
						else if (h.indexOf('rating') !== -1 || h.indexOf('star') !== -1) rowData.rating = val;
						else if (h.indexOf('title') !== -1 || h.indexOf('head') !== -1) rowData.title = val;
						else if (h.indexOf('content') !== -1 || h.indexOf('review') !== -1 || h.indexOf('body') !== -1) rowData.content = val;
						else if (h.indexOf('status') !== -1) rowData.status = val;
					});

					parsedRows.push(rowData);
				}
				return parsedRows;
			}

			function renderImportPreview(filename, rows) {
				$('#revora-preview-filename').text(filename);
				$('#revora-preview-count').text(rows.length + ' ' + (rows.length === 1 ? 'review found' : 'reviews found'));
				
				var $tbody = $('#revora-preview-tbody').empty();
				var previewSlice = rows.slice(0, 4);

				previewSlice.forEach(function(r) {
					var statusClass = (r.status.toLowerCase() === 'approved') ? 'preview-status-approved' : 'preview-status-pending';
					var tr = '<tr>' +
						'<td><strong class="revora-preview-author-text">' + escapeHtml(r.name) + '</strong></td>' +
						'<td><span class="revora-preview-star-badge">★ ' + escapeHtml(r.rating) + '</span></td>' +
						'<td><div class="revora-preview-title">' + escapeHtml(r.title) + '</div><div class="revora-preview-snippet">' + escapeHtml(r.content).substring(0, 40) + (r.content.length > 40 ? '...' : '') + '</div></td>' +
						'<td><span class="revora-preview-status ' + statusClass + '">' + escapeHtml(r.status) + '</span></td>' +
					'</tr>';
					$tbody.append(tr);
				});

				if (rows.length > 4) {
					$('#revora-preview-more-note').text('+ ' + (rows.length - 4) + ' more reviews will be imported');
				} else {
					$('#revora-preview-more-note').text('');
				}

				$('.revora-file-dropzone').hide();
				$('.revora-sample-download').hide();
				$('#revora-import-preview-wrap').slideDown(150);
				$('.revora-modal-dialog').addClass('has-preview');
			}

			$('#revora-preview-reset-btn').on('click', function() {
				$('#import_file').val('');
				$('#revora-import-preview-wrap').hide();
				$('.revora-file-dropzone').show();
				$('.revora-sample-download').show();
				$('.revora-modal-dialog').removeClass('has-preview');
			});
		});
		</script>

		<?php
		// Quick edit template is output via JS (see revora-admin.js)
		$quick_edit_template = '<tr class="revora-quick-row" id="revora-quick-edit-{{id}}"><td colspan="6"><form class="revora-quick-edit-form"><input type="hidden" name="review_id" value="{{id}}"><div class="revora-field-group"><label class="revora-field-label">' . esc_html__( 'Status', 'revora' ) . '</label><select name="status"><option value="pending" {{status_pending}}>' . esc_html__( 'Pending', 'revora' ) . '</option><option value="approved" {{status_approved}}>' . esc_html__( 'Approved', 'revora' ) . '</option><option value="rejected" {{status_rejected}}>' . esc_html__( 'Rejected', 'revora' ) . '</option></select></div><div class="revora-field-group"><label class="revora-field-label">' . esc_html__( 'Rating', 'revora' ) . '</label><div class="revora-rating-selector" data-initial="{{rating}}"><span class="dashicons dashicons-star-filled" data-rating="1"></span><span class="dashicons dashicons-star-filled" data-rating="2"></span><span class="dashicons dashicons-star-filled" data-rating="3"></span><span class="dashicons dashicons-star-filled" data-rating="4"></span><span class="dashicons dashicons-star-filled" data-rating="5"></span></div><input type="hidden" name="rating" value="{{rating}}"></div><div class="revora-quick-actions"><button type="button" class="button button-primary revora-quick-save">' . esc_html__( 'Update', 'revora' ) . '</button><button type="button" class="button revora-quick-cancel">' . esc_html__( 'Cancel', 'revora' ) . '</button></div></form></td></tr>';
		wp_add_inline_script( 'revora-admin', 'var revoraQuickEditTemplate = ' . wp_json_encode( $quick_edit_template ) . ';', 'before' );
	}

	/**
	 * Render Forms Page
	 */
	public function render_forms_page() {
		$form_builder = new Revora_Form_Builder();
		$form_builder->render_page();
	}

	/**
	 * Render Add New Page
	 */
	public function render_add_new_page() {
		$db = new Revora_DB();
		$forms = $db->get_forms();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected_form_id = isset( $_GET['form_id'] ) ? intval( wp_unslash( $_GET['form_id'] ) ) : ( ! empty( $forms ) ? $forms[0]->id : 0 );

		// Dummy review object for render_admin_form_field
		$dummy_review = (object) array(
			'name'    => '',
			'email'   => '',
			'title'   => '',
			'content' => '',
			'rating'  => 5,
		);

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Add New Review', 'revora' ); ?></h1>
			<hr class="wp-header-end">

			<form method="post" action="" class="revora-form-container" enctype="multipart/form-data">
				<?php wp_nonce_field( 'revora_add_review', 'revora_nonce' ); ?>
				
				<div class="revora-form-main">
					<?php if ( empty( $forms ) ) : ?>
						<div class="revora-card">
							<div class="revora-card-header">
								<span class="dashicons dashicons-forms"></span> <?php esc_html_e( 'Form Fields', 'revora' ); ?>
							</div>
							<div class="revora-card-body">
								<?php 
								$default_fields = array(
									array( 'type' => 'text', 'label' => 'Name', 'key' => 'name', 'required' => true ),
									array( 'type' => 'email', 'label' => 'Email', 'key' => 'email', 'required' => true ),
									array( 'type' => 'rating', 'label' => 'Rating', 'key' => 'rating', 'required' => true ),
									array( 'type' => 'text', 'label' => 'Review Title', 'key' => 'title', 'required' => true ),
									array( 'type' => 'textarea', 'label' => 'Review Content', 'key' => 'content', 'required' => true ),
								);
								foreach ( $default_fields as $field ) {
									$this->render_admin_form_field( $field, $dummy_review, array() );
								}
								?>
							</div>
						</div>
					<?php else : ?>
						<?php foreach ( $forms as $f_idx => $f_item ) : 
							$f_fields = ! empty( $f_item->fields ) ? json_decode( $f_item->fields, true ) : array();
							$is_active = ( (int) $selected_form_id === (int) $f_item->id ) || ( 0 === $f_idx && empty( $selected_form_id ) );
						?>
						<div class="revora-form-fields-container revora-card" data-form-id="<?php echo esc_attr( $f_item->id ); ?>" style="<?php echo $is_active ? '' : 'display:none;'; ?>">
							<div class="revora-card-header">
								<span class="dashicons dashicons-forms"></span> <?php echo esc_html( $f_item->name ); ?>
							</div>
							<div class="revora-card-body">
								<?php 
								if ( ! empty( $f_fields ) ) {
									foreach ( $f_fields as $field ) {
										$this->render_admin_form_field( $field, $dummy_review, array() );
									}
								}
								?>
							</div>
						</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<div class="revora-form-sidebar">
					<div class="revora-card">
						<div class="revora-card-header">
							<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Review Settings', 'revora' ); ?>
						</div>
						<div class="revora-card-body">
							<div class="revora-field-group">
								<label class="revora-field-label" for="form_id"><?php esc_html_e( 'Assign to Form', 'revora' ); ?></label>
								<select name="form_id" id="form_id" class="widefat">
									<?php if ( empty( $forms ) ) : ?>
										<option value="0"><?php esc_html_e( 'Default Form', 'revora' ); ?></option>
									<?php else : ?>
										<?php foreach ( $forms as $f ) : ?>
											<option value="<?php echo esc_attr( $f->id ); ?>" <?php selected( (int) $selected_form_id, (int) $f->id ); ?>><?php echo esc_html( $f->name ); ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>

							<div class="revora-field-group">
								<label class="revora-field-label" for="assigned_user_id"><?php esc_html_e( 'Assigned Admin / Creator', 'revora' ); ?></label>
								<select name="assigned_user_id" id="assigned_user_id" class="widefat">
									<option value="0"><?php esc_html_e( 'Public Visitor / Unassigned', 'revora' ); ?></option>
									<?php 
									$admins = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author' ) ) );
									$current_uid = get_current_user_id();
									foreach ( $admins as $adm ) : 
									?>
										<option value="<?php echo esc_attr( $adm->ID ); ?>" <?php selected( $current_uid, (int) $adm->ID ); ?>><?php echo esc_html( $adm->display_name ); ?> (<?php echo esc_html( ucfirst( $adm->roles[0] ?? 'User' ) ); ?>)</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div class="revora-sidebar-actions">
							<input type="hidden" name="revora_add_new" value="1">
							<?php submit_button( __( 'Save Review', 'revora' ), 'primary', 'submit', false ); ?>
						</div>
					</div>
				</div>
			</form>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('#form_id').on('change', function() {
				var selectedId = $(this).val();
				$('.revora-form-fields-container').hide();
				var $target = $('.revora-form-fields-container[data-form-id="' + selectedId + '"]');
				if ($target.length) {
					$target.show();
				} else {
					$('.revora-form-fields-container').first().show();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Render Edit Page
	 */
	public function render_edit_page( $id ) {
		$db = new Revora_DB();
		$review = $db->get_review( $id );
		$forms = $db->get_forms();

		if ( ! $review ) {
			echo '<div class="error"><p>' . esc_html__( 'Review not found.', 'revora' ) . '</p></div>';
			return;
		}

		$meta_data = $db->get_review_meta( $review->id );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Edit Review', 'revora' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=revora&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'revora' ); ?></a>
			<hr class="wp-header-end">

			<form method="post" action="" class="revora-form-container" enctype="multipart/form-data">
				<?php wp_nonce_field( 'revora_edit_review', 'revora_nonce' ); ?>
				<input type="hidden" name="review_id" value="<?php echo esc_attr( $review->id ); ?>">
				
				<div class="revora-form-main">
					<?php if ( empty( $forms ) ) : ?>
						<div class="revora-card">
							<div class="revora-card-header">
								<span class="dashicons dashicons-forms"></span> <?php esc_html_e( 'Form Fields', 'revora' ); ?>
							</div>
							<div class="revora-card-body">
								<?php 
								$default_fields = array(
									array( 'type' => 'text', 'label' => 'Name', 'key' => 'name', 'required' => true ),
									array( 'type' => 'email', 'label' => 'Email', 'key' => 'email', 'required' => true ),
									array( 'type' => 'rating', 'label' => 'Rating', 'key' => 'rating', 'required' => true ),
									array( 'type' => 'text', 'label' => 'Review Title', 'key' => 'title', 'required' => true ),
									array( 'type' => 'textarea', 'label' => 'Review Content', 'key' => 'content', 'required' => true ),
								);
								foreach ( $default_fields as $field ) {
									$this->render_admin_form_field( $field, $review, $meta_data );
								}
								?>
							</div>
						</div>
					<?php else : ?>
						<?php foreach ( $forms as $f_idx => $f_item ) : 
							$f_fields = ! empty( $f_item->fields ) ? json_decode( $f_item->fields, true ) : array();
							$is_active = ( (int) ( $review->form_id ?? 0 ) === (int) $f_item->id ) || ( 0 === $f_idx && empty( $review->form_id ) );
						?>
						<div class="revora-form-fields-container revora-card" data-form-id="<?php echo esc_attr( $f_item->id ); ?>" style="<?php echo $is_active ? '' : 'display:none;'; ?>">
							<div class="revora-card-header">
								<span class="dashicons dashicons-forms"></span> <?php echo esc_html( $f_item->name ); ?>
							</div>
							<div class="revora-card-body">
								<?php 
								if ( ! empty( $f_fields ) ) {
									foreach ( $f_fields as $field ) {
										$this->render_admin_form_field( $field, $review, $meta_data );
									}
								}
								?>
							</div>
						</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<div class="revora-form-sidebar">
					<div class="revora-card">
						<div class="revora-card-header">
							<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Review Settings', 'revora' ); ?>
						</div>
						<div class="revora-card-body">
							<div class="revora-field-group">
								<label class="revora-field-label" for="status"><?php esc_html_e( 'Status', 'revora' ); ?></label>
								<select name="status" id="status">
									<option value="pending" <?php selected( $review->status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'revora' ); ?></option>
									<option value="approved" <?php selected( $review->status, 'approved' ); ?>><?php esc_html_e( 'Approved', 'revora' ); ?></option>
									<option value="rejected" <?php selected( $review->status, 'rejected' ); ?>><?php esc_html_e( 'Rejected', 'revora' ); ?></option>
								</select>
							</div>

							<div class="revora-field-group">
								<label class="revora-field-label" for="form_id"><?php esc_html_e( 'Assign to Form', 'revora' ); ?></label>
								<select name="form_id" id="form_id" class="widefat">
									<?php if ( empty( $forms ) ) : ?>
										<option value="0"><?php esc_html_e( 'Default Form', 'revora' ); ?></option>
									<?php else : ?>
										<?php foreach ( $forms as $f ) : ?>
											<option value="<?php echo esc_attr( $f->id ); ?>" <?php selected( (int) ( $review->form_id ?? 0 ), (int) $f->id ); ?>><?php echo esc_html( $f->name ); ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>

							<div class="revora-field-group">
								<label class="revora-field-label" for="assigned_user_id"><?php esc_html_e( 'Assigned Admin / Moderator', 'revora' ); ?></label>
								<select name="assigned_user_id" id="assigned_user_id" class="widefat">
									<option value="0" <?php selected( (int) ( $review->user_id ?? 0 ), 0 ); ?>><?php esc_html_e( 'Public Visitor / Unassigned', 'revora' ); ?></option>
									<?php 
									$admins = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author' ) ) );
									$current_assigned = isset( $review->user_id ) ? (int) $review->user_id : 0;
									foreach ( $admins as $adm ) : 
									?>
										<option value="<?php echo esc_attr( $adm->ID ); ?>" <?php selected( $current_assigned, (int) $adm->ID ); ?>><?php echo esc_html( $adm->display_name ); ?> (<?php echo esc_html( ucfirst( $adm->roles[0] ?? 'User' ) ); ?>)</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div class="revora-sidebar-actions">
							<input type="hidden" name="revora_edit_review" value="1">
							<?php submit_button( __( 'Update Review', 'revora' ), 'primary', 'submit', false ); ?>
						</div>
					</div>
				</div>
			</form>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('#form_id').on('change', function() {
				var selectedId = $(this).val();
				$('.revora-form-fields-container').hide();
				var $target = $('.revora-form-fields-container[data-form-id="' + selectedId + '"]');
				if ($target.length) {
					$target.show();
				} else {
					$('.revora-form-fields-container').first().show();
				}
			});
		});
		</script>
		<?php
	}

	private function render_admin_form_field( $field, $review, $meta_data ) {
		if ( empty( $field['type'] ) ) return;
		if ( 'submit' === $field['type'] ) return;

		if ( 'row' === $field['type'] ) {
			echo '<div style="display:flex; gap:20px; margin-bottom:15px;">';
			if ( ! empty( $field['columns'] ) && is_array( $field['columns'] ) ) {
				foreach ( $field['columns'] as $col_fields ) {
					echo '<div style="flex:1; min-width:0;">';
					foreach ( $col_fields as $col_field ) {
						$this->render_admin_form_field( $col_field, $review, $meta_data );
					}
					echo '</div>';
				}
			}
			echo '</div>';
			return;
		}

		$key = $field['key'] ?? '';
		$label = $field['label'] ?? ucfirst( $key );
		$type = $field['type'];
		$placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';

		// Smart default placeholders
		if ( empty( $placeholder ) ) {
			if ( 'name' === $key ) {
				$placeholder = __( 'e.g. John Doe', 'revora' );
			} elseif ( 'email' === $key ) {
				$placeholder = __( 'e.g. john@example.com', 'revora' );
			} elseif ( 'title' === $key ) {
				$placeholder = __( 'e.g. Amazing Experience!', 'revora' );
			} elseif ( 'content' === $key ) {
				$placeholder = __( 'Write your review here...', 'revora' );
			} elseif ( 'tel' === $type || false !== strpos( $key, 'phone' ) ) {
				$placeholder = __( 'e.g. +1 (555) 000-0000', 'revora' );
			} elseif ( 'number' === $type ) {
				$placeholder = __( 'e.g. 10', 'revora' );
			} elseif ( 'url' === $type || false !== strpos( $key, 'url' ) || false !== strpos( $key, 'website' ) ) {
				$placeholder = __( 'https://example.com', 'revora' );
			} elseif ( 'date' === $type ) {
				$placeholder = __( 'YYYY-MM-DD', 'revora' );
			} elseif ( 'textarea' === $type ) {
				$placeholder = __( 'Enter details here...', 'revora' );
			} else {
				$placeholder = sprintf( __( 'Enter %s...', 'revora' ), esc_html( strtolower( $label ) ) );
			}
		}

		// Standard fields
		if ( in_array( $key, array( 'name', 'email', 'title', 'content', 'rating' ), true ) ) {
			echo '<div class="revora-field-group">';
			echo '<label class="revora-field-label" for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
			if ( 'name' === $key ) {
				echo '<input name="name" type="text" id="name" value="' . esc_attr( $review->name ) . '" placeholder="' . esc_attr( $placeholder ) . '">';
			} elseif ( 'email' === $key ) {
				echo '<input name="email" type="email" id="email" value="' . esc_attr( $review->email ) . '" placeholder="' . esc_attr( $placeholder ) . '">';
			} elseif ( 'title' === $key ) {
				echo '<input name="title" type="text" id="title" value="' . esc_attr( $review->title ) . '" placeholder="' . esc_attr( $placeholder ) . '">';
			} elseif ( 'content' === $key ) {
				echo '<textarea name="content" id="content" rows="6" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( $review->content ) . '</textarea>';
			} elseif ( 'rating' === $key ) {
				$r_val = floatval( $review->rating ?? 5.0 );
				echo '<div class="revora-rating-selector" data-rating="' . esc_attr( $r_val ) . '">';
				for ( $i = 1; $i <= 5; $i++ ) {
					if ( $r_val >= $i ) {
						$icon_text = 'star';
						$active_class = 'active fill-1';
					} elseif ( $r_val >= ( $i - 0.5 ) ) {
						$icon_text = 'star_half';
						$active_class = 'active fill-1';
					} else {
						$icon_text = 'star';
						$active_class = '';
					}
					echo '<span class="material-symbols-outlined revora-star-icon ' . esc_attr( $active_class ) . '" data-rating="' . esc_attr( $i ) . '">' . esc_html( $icon_text ) . '</span>';
				}
				echo '<span class="revora-rating-value-display">' . esc_html( number_format( $r_val, 1 ) ) . '</span>';
				echo '<input type="hidden" name="rating" class="revora-rating-input" value="' . esc_attr( $r_val ) . '">';
				echo '</div>';
			}
			echo '</div>';
			return;
		}

		// Custom Meta Field
		$val = $meta_data[ $key ] ?? '';
		$field_name = 'meta[' . esc_attr( $key ) . ']';

		echo '<div class="revora-field-group">';
		echo '<label class="revora-field-label" for="meta_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';

		if ( 'textarea' === $type ) {
			echo '<textarea name="' . $field_name . '" id="meta_' . esc_attr( $key ) . '" rows="4" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( $val ) . '</textarea>';
		} elseif ( 'select' === $type ) {
			echo '<select name="' . $field_name . '" id="meta_' . esc_attr( $key ) . '" class="widefat">';
			echo '<option value="">' . esc_html__( 'Select...', 'revora' ) . '</option>';
			$options = ! empty( $field['options'] ) ? explode( "\n", $field['options'] ) : array();
			foreach ( $options as $opt ) {
				$opt = trim( $opt );
				if ( empty( $opt ) ) continue;
				echo '<option value="' . esc_attr( $opt ) . '" ' . selected( $val, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'radio' === $type ) {
			echo '<div style="display:flex; flex-direction:column; gap:6px; margin-top:5px;">';
			$options = ! empty( $field['options'] ) ? explode( "\n", $field['options'] ) : array();
			foreach ( $options as $opt ) {
				$opt = trim( $opt );
				if ( empty( $opt ) ) continue;
				echo '<label><input type="radio" name="' . $field_name . '" value="' . esc_attr( $opt ) . '" ' . checked( $val, $opt, false ) . '> ' . esc_html( $opt ) . '</label>';
			}
			echo '</div>';
		} elseif ( 'checkbox' === $type ) {
			echo '<div style="display:flex; flex-direction:column; gap:6px; margin-top:5px;">';
			$selected_vals = is_array( $val ) ? $val : ( is_string( $val ) ? array_map( 'trim', explode( ',', $val ) ) : array() );
			$options = ! empty( $field['options'] ) ? explode( "\n", $field['options'] ) : array();
			foreach ( $options as $opt ) {
				$opt = trim( $opt );
				if ( empty( $opt ) ) continue;
				echo '<label><input type="checkbox" name="' . $field_name . '[]" value="' . esc_attr( $opt ) . '" ' . ( in_array( $opt, $selected_vals, true ) ? 'checked' : '' ) . '> ' . esc_html( $opt ) . '</label>';
			}
			echo '</div>';
		} elseif ( 'file' === $type ) {
			if ( ! empty( $val ) ) {
				echo '<div style="margin-bottom:8px;">';
				if ( preg_match( '/\.(jpg|jpeg|png|gif|webp)$/i', $val ) ) {
					echo '<img src="' . esc_url( $val ) . '" style="max-height:80px; border-radius:4px; display:block; margin-bottom:5px;">';
				}
				echo '<a href="' . esc_url( $val ) . '" target="_blank" class="button button-small">' . esc_html__( 'View Current File', 'revora' ) . '</a>';
				echo '</div>';
			}
			echo '<input type="file" name="meta_file_' . esc_attr( $key ) . '" id="meta_' . esc_attr( $key ) . '">';
			if ( ! empty( $val ) ) {
				echo '<input type="hidden" name="' . $field_name . '" value="' . esc_attr( $val ) . '">';
			}
		} else {
			// text, email, number, tel, url, date, etc.
			echo '<input type="' . esc_attr( $type ) . '" name="' . $field_name . '" id="meta_' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $placeholder ) . '">';
		}

		echo '</div>';
	}

	private function get_all_field_keys( $fields ) {
		$keys = array();
		if ( ! is_array( $fields ) ) return $keys;
		foreach ( $fields as $field ) {
			if ( 'row' === $field['type'] && ! empty( $field['columns'] ) ) {
				foreach ( $field['columns'] as $col_fields ) {
					$keys = array_merge( $keys, $this->get_all_field_keys( $col_fields ) );
				}
			} elseif ( ! empty( $field['key'] ) ) {
				$keys[] = $field['key'];
			}
		}
		return $keys;
	}

	/**
	 * Render Categories Page
	 */
	public function render_categories_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'edit_cat' === $action && isset( $_GET['cat_id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->render_category_edit_page( intval( wp_unslash( $_GET['cat_id'] ) ) );
			return;
		}

		$table = new Revora_Category_List_Table();
		$table->prepare_items();

		$message = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['message'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$msg_type = sanitize_key( wp_unslash( $_GET['message'] ) );
			if ( 'added' === $msg_type ) {
				$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Category added successfully.', 'revora' ) . '</p></div>';
			} elseif ( 'updated' === $msg_type ) {
				$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Category updated successfully.', 'revora' ) . '</p></div>';
			} elseif ( 'deleted' === $msg_type ) {
				$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Category deleted.', 'revora' ) . '</p></div>';
			}
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Categories', 'revora' ); ?></h1>
			<?php echo wp_kses_post( $message ); ?>

			<div id="col-container" class="wp-clearfix">
				<div id="col-left">
					<div class="col-wrap">
						<div class="form-wrap">
							<h2><?php esc_html_e( 'Add New Category', 'revora' ); ?></h2>
							<form id="addtag" method="post" action="" class="validate">
								<?php wp_nonce_field( 'revora_add_cat_nonce', 'revora_cat_nonce' ); ?>
								<div class="form-field form-required term-name-wrap">
									<label for="cat_name"><?php esc_html_e( 'Name', 'revora' ); ?></label>
									<input name="cat_name" id="cat_name" type="text" value="" size="40" aria-required="true" required>
									<p><?php esc_html_e( 'The name is how it appears on your site.', 'revora' ); ?></p>
								</div>
								<div class="form-field term-parent-wrap">
									<label for="parent_id"><?php esc_html_e( 'Parent Category', 'revora' ); ?></label>
									<select name="parent_id" id="parent_id">
										<option value="0"><?php esc_html_e( 'None', 'revora' ); ?></option>
										<?php
										$db = new Revora_DB();
										$categories = $db->get_categories();
										foreach ( $categories as $cat ) {
											if ( $cat->parent_id == 0 ) {
												echo '<option value="' . esc_attr( $cat->id ) . '">' . esc_html( $cat->name ) . '</option>';
											}
										}
										?>
									</select>
									<p><?php esc_html_e( 'Categories, unlike tags, can have a hierarchy. You might have a Jazz category, and under that have children categories for Bebop and Big Band. Totally optional.', 'revora' ); ?></p>
								</div>
								<input type="hidden" name="revora_add_category" value="1">
								<?php submit_button( __( 'Add New Category', 'revora' ) ); ?>
							</form>
						</div>
					</div>
				</div>

				<div id="col-right">
					<div class="col-wrap">
						<form id="posts-filter" method="get">
							<input type="hidden" name="page" value="revora-categories" />
							<?php
							wp_nonce_field( 'bulk-categories' );
							$table->display();
							?>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Category Checklist Helper
	 */
	private function render_category_checklist( $parent_id = 0, $selected = array() ) {
		$db = new Revora_DB();
		$categories = $db->get_categories();
		
		echo '<ul id="revora-category-checklist">';
		foreach ( $categories as $cat ) {
			$cat_parent = isset( $cat->parent_id ) ? intval( $cat->parent_id ) : 0;
			if ( $cat_parent == $parent_id ) {
				$checked_val = in_array( $cat->id, $selected ) ? 'checked' : '';
				echo '<li>';
				echo '<label><input type="checkbox" name="categories[]" value="' . esc_attr( $cat->id ) . '" ' . esc_attr( $checked_val ) . '> ' . esc_html( $cat->name ) . '</label>';
				
				// Recursive call for children
				echo '<ul class="children">';
				$this->render_category_checklist( $cat->id, $selected );
				echo '</ul>';
				
				echo '</li>';
			}
		}
		echo '</ul>';
	}

	/**
	 * Render Category Edit Page
	 */
	public function render_category_edit_page( $id ) {
		$db = new Revora_DB();
		$cat = $db->get_category( $id );

		if ( ! $cat ) {
			echo '<div class="error"><p>' . esc_html__( 'Category not found.', 'revora' ) . '</p></div>';
			return;
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Edit Category', 'revora' ); ?></h1>
			<form method="post" action="">
				<?php wp_nonce_field( 'revora_add_cat_nonce', 'revora_cat_nonce' ); ?>
				<input type="hidden" name="cat_id" value="<?php echo esc_attr( $cat->id ); ?>">
				<table class="form-table">
					<tr>
						<th scope="row"><label for="cat_name"><?php esc_html_e( 'Name', 'revora' ); ?></label></th>
						<td><input name="cat_name" type="text" id="cat_name" value="<?php echo esc_attr( $cat->name ); ?>" class="regular-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="cat_slug"><?php esc_html_e( 'Slug', 'revora' ); ?></label></th>
						<td><input name="cat_slug" type="text" id="cat_slug" value="<?php echo esc_attr( $cat->slug ); ?>" class="regular-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="parent_id"><?php esc_html_e( 'Parent Category', 'revora' ); ?></label></th>
						<td>
							<select name="parent_id" id="parent_id">
								<option value="0"><?php esc_html_e( 'None', 'revora' ); ?></option>
								<?php
								$all_cats = $db->get_categories();
								foreach ( $all_cats as $other_cat ) {
									if ( $other_cat->id == $cat->id ) continue;
									if ( $other_cat->parent_id == 0 ) {
										echo '<option value="' . esc_attr( $other_cat->id ) . '" ' . selected( $cat->parent_id, $other_cat->id, false ) . '>' . esc_html( $other_cat->name ) . '</option>';
									}
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cat_description"><?php esc_html_e( 'Description', 'revora' ); ?></label></th>
						<td><textarea name="cat_description" id="cat_description" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $cat->description ); ?></textarea></td>
					</tr>
				</table>
				<input type="hidden" name="revora_edit_category" value="1">
				<?php submit_button( __( 'Update Category', 'revora' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Settings Page
	 */
	public function render_settings_page() {
		$settings = wp_parse_args( get_option( 'revora_settings', array() ), $this->get_settings_defaults() );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'moderation';
		// phpcs:enable
		
		$tabs = array(
			'moderation' => array( 'label' => __( 'Moderation', 'revora' ), 'icon' => 'tune' ),
			'appearance' => array( 'label' => __( 'Appearance', 'revora' ), 'icon' => 'palette' ),
			'emails'     => array( 'label' => __( 'Emails', 'revora' ), 'icon' => 'mail' ),
			'shortcodes' => array( 'label' => __( 'Shortcodes', 'revora' ), 'icon' => 'code' ),
		);
		?>
		<div class="wrap revora-settings-wrap">
			<h1><?php esc_html_e( 'Revora Settings', 'revora' ); ?></h1>
			
			<div class="revora-settings-container">
				<nav class="revora-settings-tabs">
					<?php foreach ( $tabs as $id => $tab ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'tab', $id, admin_url( 'admin.php?page=revora-settings' ) ) ); ?>" class="revora-tab-link <?php echo esc_attr( $active_tab === $id ? 'active' : '' ); ?>">
							<span class="material-symbols-outlined"><?php echo esc_html( $tab['icon'] ); ?></span>
							<?php echo esc_html( $tab['label'] ); ?>
						</a>
					<?php endforeach; ?>
				</nav>

				<form method="post" action="options.php" class="revora-settings-form">
					<?php
					settings_fields( 'revora_settings_group' );
					?>

					<div class="revora-settings-content">
						<?php if ( 'moderation' === $active_tab ) : ?>
							<div class="revora-card">
								<div class="revora-card-header">
									<span class="material-symbols-outlined">tune</span>
									<span><?php esc_html_e( 'Moderation & Workflow', 'revora' ); ?></span>
								</div>
								<div class="revora-card-body">
									<div class="revora-field-group">
										<label class="revora-field-label"><?php esc_html_e( 'Auto-Publish Reviews', 'revora' ); ?></label>
										<label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
											<input type="checkbox" name="revora_settings[auto_approve]" value="1" <?php checked( $settings['auto_approve'], '1' ); ?>>
											<span><?php esc_html_e( 'Automatically approve and publish newly submitted reviews', 'revora' ); ?></span>
										</label>
										<p class="description"><?php esc_html_e( 'When disabled, all new reviews are kept in "Pending" status until an admin approves them.', 'revora' ); ?></p>
									</div>

									<div class="revora-field-group">
										<label class="revora-field-label" for="revora_admin_email"><?php esc_html_e( 'Admin Notification Email', 'revora' ); ?></label>
										<input type="email" name="revora_settings[admin_email]" id="revora_admin_email" value="<?php echo esc_attr( $settings['admin_email'] ); ?>" class="regular-text" placeholder="admin@example.com" />
										<p class="description"><?php esc_html_e( 'New review submission email alerts will be sent to this email address.', 'revora' ); ?></p>
									</div>
								</div>
							</div>

						<?php elseif ( 'appearance' === $active_tab ) : ?>
							<div class="revora-card">
								<div class="revora-card-header">
									<span class="material-symbols-outlined">palette</span>
									<span><?php esc_html_e( 'Design & Display Settings', 'revora' ); ?></span>
								</div>
								<div class="revora-card-body">
									<div class="revora-field-group">
										<label class="revora-field-label" for="revora_website_name"><?php esc_html_e( 'Brand/Website Name', 'revora' ); ?></label>
										<input type="text" name="revora_settings[website_name]" id="revora_website_name" value="<?php echo esc_attr( $settings['website_name'] ); ?>" class="regular-text" />
										<p class="description"><?php esc_html_e( 'Used on the Share Card.', 'revora' ); ?></p>
									</div>

									<div class="revora-field-group">
										<label class="revora-field-label" for="revora_website_logo"><?php esc_html_e( 'Brand Logo', 'revora' ); ?></label>
										<div class="revora-media-upload-wrap" style="display:flex; align-items:center; gap:14px; margin-top:6px;">
											<input type="hidden" name="revora_settings[website_logo]" id="revora_website_logo" value="<?php echo esc_url( $settings['website_logo'] ); ?>" />
											<div id="revora-logo-preview" style="width:70px; height:50px; border:1px dashed #cbd5e1; border-radius:8px; display:flex; align-items:center; justify-content:center; background:#f8fafc; overflow:hidden; padding:4px; box-sizing:border-box;">
												<?php if ( ! empty( $settings['website_logo'] ) ) : ?>
													<img src="<?php echo esc_url( $settings['website_logo'] ); ?>" style="max-width:100%; max-height:100%; object-fit:contain;" />
												<?php else : ?>
													<span class="material-symbols-outlined" style="color:#94a3b8; font-size:24px;">image</span>
												<?php endif; ?>
											</div>
											<div>
												<button type="button" class="button button-secondary" id="revora-upload-logo-btn" style="display:inline-flex; align-items:center; gap:6px;">
													<span class="dashicons dashicons-upload" style="margin:0;"></span>
													<?php esc_html_e( 'Upload / Select Logo', 'revora' ); ?>
												</button>
												<button type="button" class="button" id="revora-remove-logo-btn" style="<?php echo empty( $settings['website_logo'] ) ? 'display:none;' : 'display:inline-block;'; ?> margin-left:6px; color:#dc2626; border-color:#fca5a5;">
													<?php esc_html_e( 'Remove', 'revora' ); ?>
												</button>
											</div>
										</div>
										<p class="description"><?php esc_html_e( 'Upload your company/website logo. Displayed above the profile photo on the Share Card.', 'revora' ); ?></p>
									</div>

									<div class="revora-field-group">
										<label class="revora-field-label" for="revora_primary_color"><?php esc_html_e( 'Primary Brand Color', 'revora' ); ?></label>
										<input type="text" name="revora_settings[primary_color]" id="revora_primary_color" value="<?php echo esc_attr( $settings['primary_color'] ); ?>" class="revora-color-picker" data-default-color="#2563eb" />
										<p class="description"><?php esc_html_e( 'Main accent color used for buttons, links, and badges.', 'revora' ); ?></p>
									</div>

									<div class="revora-field-group">
										<label class="revora-field-label" for="revora_star_color"><?php esc_html_e( 'Star Rating Color', 'revora' ); ?></label>
										<input type="text" name="revora_settings[star_color]" id="revora_star_color" value="<?php echo esc_attr( $settings['star_color'] ); ?>" class="revora-color-picker" data-default-color="#f59e0b" />
										<p class="description"><?php esc_html_e( 'Color applied to active filled stars and rating badges.', 'revora' ); ?></p>
									</div>

									<div class="revora-field-group">
										<label class="revora-field-label" for="revora_card_style"><?php esc_html_e( 'Default Review Card Style', 'revora' ); ?></label>
										<select name="revora_settings[card_style]" id="revora_card_style" class="widefat" style="max-width:320px;">
											<option value="classic" <?php selected( $settings['card_style'], 'classic' ); ?>><?php esc_html_e( 'Classic (Clean & Minimal)', 'revora' ); ?></option>
											<option value="verified" <?php selected( $settings['card_style'], 'verified' ); ?>><?php esc_html_e( 'Verified Badge / Student (Modern Showcase)', 'revora' ); ?></option>
											<option value="modern" <?php selected( $settings['card_style'], 'modern' ); ?>><?php esc_html_e( 'Modern (Soft Shadow & Accent Line)', 'revora' ); ?></option>
											<option value="boxed" <?php selected( $settings['card_style'], 'boxed' ); ?>><?php esc_html_e( 'Boxed (Card with Light Gray Border)', 'revora' ); ?></option>
											<option value="horizontal" <?php selected( $settings['card_style'], 'horizontal' ); ?>><?php esc_html_e( 'Horizontal (Wide Side-by-Side)', 'revora' ); ?></option>
											<option value="testimonial" <?php selected( $settings['card_style'], 'testimonial' ); ?>><?php esc_html_e( 'Testimonial (Centered with Quote Badge)', 'revora' ); ?></option>
										</select>
										<p class="description"><?php esc_html_e( 'Can be overridden on specific pages using [revora_reviews card_style="..."]', 'revora' ); ?></p>
									</div>

									<div class="revora-field-group">
										<label class="revora-field-label"><?php esc_html_e( 'Review Badges & Stars', 'revora' ); ?></label>
										<label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
											<input type="checkbox" name="revora_settings[show_stars]" value="1" <?php checked( $settings['show_stars'], '1' ); ?>>
											<span><?php esc_html_e( 'Display Star Rating on Review Cards', 'revora' ); ?></span>
										</label>
									</div>

									<div class="revora-field-group">
										<label class="revora-field-label"><?php esc_html_e( 'SEO & Structured Data', 'revora' ); ?></label>
										<label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
											<input type="checkbox" name="revora_settings[enable_schema]" value="1" <?php checked( $settings['enable_schema'], '1' ); ?>>
											<span><?php esc_html_e( 'Enable Schema.org AggregateRating (JSON-LD Rich Snippets for Google Search)', 'revora' ); ?></span>
										</label>
									</div>
								</div>
							</div>

						<?php elseif ( 'emails' === $active_tab ) : ?>
							<div class="revora-card">
								<div class="revora-card-header">
									<span class="material-symbols-outlined">mail</span>
									<span><?php esc_html_e( 'Admin Email Notifications', 'revora' ); ?></span>
								</div>
								<div class="revora-card-body">
									<div class="revora-field-group">
										<label class="revora-field-label"><?php esc_html_e( 'Email Subject', 'revora' ); ?></label>
										<input type="text" name="revora_settings[email_subject]" value="<?php echo esc_attr( $settings['email_subject'] ); ?>" class="regular-text" style="width:100%; max-width:500px;">
									</div>
									<div class="revora-field-group">
										<label class="revora-field-label"><?php esc_html_e( 'Email Message Template', 'revora' ); ?></label>
										<textarea name="revora_settings[email_template]" rows="9" class="large-text" style="font-family:monospace; font-size:13px;"><?php echo esc_textarea( $settings['email_template'] ); ?></textarea>
										<p class="description" style="margin-top:6px;">
											<strong><?php esc_html_e( 'Available Smart Tags:', 'revora' ); ?></strong>
											<code>{author}</code>, <code>{rating}</code>, <code>{title}</code>, <code>{content}</code>, <code>{site_title}</code>, <code>{admin_url}</code>
										</p>
									</div>
								</div>
							</div>

						<?php elseif ( 'shortcodes' === $active_tab ) : ?>
							<div class="revora-card">
								<div class="revora-card-header">
									<span class="material-symbols-outlined">code</span>
									<span><?php esc_html_e( 'Shortcodes & Usage Guide', 'revora' ); ?></span>
								</div>
								<div class="revora-card-body">
									<div class="revora-shortcode-box" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin-bottom:16px;">
										<h3 style="margin-top:0; font-size:15px; color:#0f172a;"><?php esc_html_e( '1. Review Submission Form', 'revora' ); ?></h3>
										<p style="margin-bottom:8px; color:#64748b; font-size:13px;"><?php esc_html_e( 'Embed a custom review submission form on any page or post:', 'revora' ); ?></p>
										<div style="display:flex; align-items:center; gap:8px;">
											<code style="padding:6px 12px; font-size:14px; background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; flex:1;">[revora_form id="0"]</code>
										</div>
										<p class="description" style="margin-top:6px;"><?php esc_html_e( 'Replace id="0" with your specific Form ID from the Revora Forms page.', 'revora' ); ?></p>
									</div>

									<div class="revora-shortcode-box" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px;">
										<h3 style="margin-top:0; font-size:15px; color:#0f172a;"><?php esc_html_e( '2. Display Published Reviews', 'revora' ); ?></h3>
										<p style="margin-bottom:8px; color:#64748b; font-size:13px;"><?php esc_html_e( 'Display approved customer reviews in a responsive grid or list:', 'revora' ); ?></p>
										<div style="display:flex; align-items:center; gap:8px;">
											<code style="padding:6px 12px; font-size:14px; background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; flex:1;">[revora_reviews form_id="0" limit="6" card_style="classic" columns="3"]</code>
										</div>
										<ul style="margin:10px 0 0 18px; list-style:disc; font-size:12.5px; color:#475569;">
											<li><code>form_id</code>: <?php esc_html_e( 'Filter reviews by specific form (e.g. form_id="1")', 'revora' ); ?></li>
											<li><code>limit</code>: <?php esc_html_e( 'Number of reviews to display (default: 6)', 'revora' ); ?></li>
											<li><code>columns</code>: <?php esc_html_e( 'Grid columns: 1, 2, 3, or 4', 'revora' ); ?></li>
											<li><code>card_style</code>: <code>classic</code>, <code>modern</code>, <code>boxed</code>, <code>horizontal</code>, <code>testimonial</code></li>
										</ul>
									</div>
								</div>
							</div>
						<?php endif; ?>

						<?php if ( 'shortcodes' !== $active_tab ) : ?>
							<div class="revora-settings-actions">
								<?php submit_button( __( 'Save All Changes', 'revora' ), 'primary', 'submit', false ); ?>
							</div>
						<?php endif; ?>
					</div>
				</form>
			</div>

			<!-- Footer Copyright Bar -->
			<div class="revora-admin-footer-bar" style="margin-top: 25px; padding: 12px 16px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12.5px; color: #64748b; display: flex; justify-content: space-between; align-items: center;">
				<div>
					<strong>Revora</strong> <?php echo esc_html( REVORA_VERSION ); ?> &bull; <?php esc_html_e( 'Smart Category Review System', 'revora' ); ?>
				</div>
				<div>
					<?php esc_html_e( 'Developed with passion by', 'revora' ); ?> <a href="https://moksedul.com" target="_blank" rel="noopener noreferrer" style="color: #2563eb; font-weight: 600; text-decoration: none;">Moksedul Islam</a>
				</div>
			</div>
		</div>
		<script>
		jQuery(document).ready(function($) {
			if ($.fn.wpColorPicker) {
				$('.revora-color-picker').wpColorPicker();
			}
		});
		</script>
		<?php
	}

	public function register_dashboard_widget() {
		wp_add_dashboard_widget(
			'revora_dashboard_stats',
			__( 'Revora – Review Insights', 'revora' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	public function render_dashboard_widget() {
		$db = new Revora_DB();
		$stats = $db->get_stats();
		?>
		<div class="revora-dashboard-widget">
			<div class="revora-stats-overview">
				<div class="revora-stat-box revora-box-total">
					<div class="revora-stat-number"><?php echo esc_html( number_format_i18n( $stats->total ) ); ?></div>
					<div class="revora-stat-text"><?php esc_html_e( 'Total Reviews', 'revora' ); ?></div>
				</div>
				<div class="revora-stat-box revora-box-approved">
					<div class="revora-stat-number"><?php echo esc_html( number_format_i18n( $stats->approved ) ); ?></div>
					<div class="revora-stat-text"><?php esc_html_e( 'Approved', 'revora' ); ?></div>
				</div>
				<div class="revora-stat-box revora-box-pending <?php echo esc_attr( $stats->pending > 0 ? 'alert' : '' ); ?>">
					<div class="revora-stat-number"><?php echo esc_html( number_format_i18n( $stats->pending ) ); ?></div>
					<div class="revora-stat-text"><?php esc_html_e( 'Pending', 'revora' ); ?></div>
				</div>
				<div class="revora-stat-box revora-box-rating">
					<div class="revora-stat-number"><?php echo number_format( $stats->average, 1 ); ?><span class="revora-rating-scale">/5</span></div>
					<div class="revora-stat-text"><?php esc_html_e( 'Avg Rating', 'revora' ); ?></div>
				</div>
			</div>
			<div class="revora-widget-links">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=revora' ) ); ?>" class="revora-link-primary">
					<?php esc_html_e( 'View All Reviews', 'revora' ); ?> →
				</a>
			</div>
		</div>
		<?php
	}
}

/**
 * Review List Table Class
 */
class Revora_Review_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => 'review',
			'plural'   => 'reviews',
			'ajax'     => false,
		) );
	}

	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'content'    => __( 'Review', 'revora' ),
			'customer'   => __( 'Customer Info', 'revora' ),
			'form'       => __( 'Form', 'revora' ),
			'status'     => __( 'Status', 'revora' ),
			'created_at' => __( 'Date', 'revora' ),
			'author'     => __( 'Assigned', 'revora' ),
		);
	}

	protected function get_bulk_actions() {
		return array(
			'approve' => __( 'Approve', 'revora' ),
			'reject'  => __( 'Reject', 'revora' ),
			'delete'  => __( 'Delete Permanently', 'revora' ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'rating'     => array( 'rating', false ),
			'created_at' => array( 'created_at', true ),
		);
	}

	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="review[]" value="%s" />', $item->id );
	}


	public function column_content( $item ) {
		$actions = array(
			'edit'       => sprintf( '<a href="?page=%s&action=%s&review_id=%s">%s</a>', 'revora', 'edit', $item->id, __( 'Edit', 'revora' ) ),
			'duplicate'  => sprintf( '<a href="%s">%s</a>', wp_nonce_url( add_query_arg( array( 'page' => 'revora', 'action' => 'duplicate', 'review_id' => $item->id ) ), 'revora_duplicate_' . $item->id ), esc_html__( 'Duplicate', 'revora' ) ),
			'delete'     => sprintf( '<a href="%s" onclick="return confirm(\'Are you sure?\')">%s</a>', wp_nonce_url( '?page=revora&action=delete&review_id=' . $item->id, 'revora_delete_' . $item->id ), __( 'Delete', 'revora' ) ),
		);

		// Star Rating with Google Material Symbols
		$stars = '<div class="revora-admin-stars" style="margin-bottom: 3px;">';
		$r_val = floatval( $item->rating );
		for ( $i = 1; $i <= 5; $i++ ) {
			if ( $r_val >= $i ) {
				$stars .= '<span class="material-symbols-outlined revora-star-filled fill-1">star</span>';
			} elseif ( $r_val >= ( $i - 0.5 ) ) {
				$stars .= '<span class="material-symbols-outlined revora-star-filled fill-1">star_half</span>';
			} else {
				$stars .= '<span class="material-symbols-outlined revora-star-empty">star</span>';
			}
		}
		$stars .= '<span class="revora-admin-rating-badge">' . number_format( $r_val, 1 ) . '</span>';
		$stars .= '</div>';
		$display_name = ! empty( $item->name ) ? $item->name : ( ! empty( $item->title ) ? $item->title : __( 'Anonymous', 'revora' ) );

		return sprintf( '%s<div class="revora-table-review-title"><strong>%s</strong></div>%s',
			$stars,
			esc_html( $display_name ),
			$this->row_actions( $actions )
		);
	}

	public function column_customer( $item ) {
		$db = new Revora_DB();
		$meta = $db->get_review_meta( $item->id );
		$phone = '';
		if ( is_array( $meta ) ) {
			foreach ( $meta as $k => $v ) {
				if ( false !== strpos( strtolower( $k ), 'phone' ) || 'tel' === strtolower( $k ) || false !== strpos( strtolower( $k ), 'contact' ) || false !== strpos( strtolower( $k ), 'mobile' ) ) {
					$phone = $v;
					break;
				}
			}
		}

		$output = '<div class="revora-customer-cell">';
		$output .= sprintf( '<strong class="revora-customer-name">%s</strong>', esc_html( $item->name ) );
		if ( ! empty( $item->email ) ) {
			$output .= sprintf( '<small class="revora-customer-email">%s</small>', esc_html( $item->email ) );
		}
		if ( ! empty( $phone ) ) {
			$output .= sprintf( '<small class="revora-customer-phone"><span class="material-symbols-outlined revora-phone-icon">call</span> %s</small>', esc_html( $phone ) );
		}
		$output .= '</div>';

		return $output;
	}

	public function column_author( $item ) {
		$user_id = ! empty( $item->user_id ) ? intval( $item->user_id ) : 0;
		$avatar_html = '';
		$author_label = ! empty( $item->name ) ? $item->name : __( 'Anonymous', 'revora' );
		$email_label = ! empty( $item->email ) ? ' (' . $item->email . ')' : '';

		if ( $user_id > 0 ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$roles = ! empty( $user->roles ) ? implode( ', ', array_map( 'ucfirst', $user->roles ) ) : 'User';
				$is_admin = in_array( 'administrator', (array) $user->roles, true ) || in_array( 'editor', (array) $user->roles, true );
				$assigned_note = $is_admin ? sprintf( __( 'Admin: %s', 'revora' ), $user->display_name ) : sprintf( __( 'User: %s', 'revora' ), $user->display_name );
				$tooltip_text = $author_label . $email_label . ' • ' . $assigned_note;
				
				$avatar_html = sprintf(
					'<div class="revora-avatar-wrap" data-tooltip="%s">%s<span class="revora-role-indicator %s"></span></div>',
					esc_attr( $tooltip_text ),
					get_avatar( $user_id, 32, '', '', array( 'class' => 'revora-avatar-img' ) ),
					$is_admin ? 'indicator-admin' : 'indicator-user'
				);
			} else {
				$tooltip_text = $author_label . $email_label . ' • ' . __( 'Public Visitor', 'revora' );
				$avatar_html = sprintf(
					'<div class="revora-avatar-wrap" data-tooltip="%s">%s<span class="revora-role-indicator indicator-guest"></span></div>',
					esc_attr( $tooltip_text ),
					get_avatar( $item->email, 32, 'mystery', '', array( 'class' => 'revora-avatar-img' ) )
				);
			}
		} else {
			$tooltip_text = $author_label . $email_label . ' • ' . __( 'Public Guest', 'revora' );
			$avatar_html = sprintf(
				'<div class="revora-avatar-wrap" data-tooltip="%s">%s<span class="revora-role-indicator indicator-guest"></span></div>',
				esc_attr( $tooltip_text ),
				get_avatar( $item->email, 32, 'mystery', '', array( 'class' => 'revora-avatar-img' ) )
			);
		}

		return '<div class="revora-author-avatar-only">' . $avatar_html . '</div>';
	}

	public function column_form( $item ) {
		$db = new Revora_DB();
		if ( ! empty( $item->form_id ) ) {
			$form = $db->get_form( $item->form_id );
			if ( $form ) {
				return sprintf( '<strong><a href="%s">%s</a></strong>', esc_url( admin_url( 'admin.php?page=revora-forms&action=edit&form_id=' . $form->id ) ), esc_html( $form->name ) );
			}
		}
		return '<em>' . esc_html__( 'Default Form', 'revora' ) . '</em>';
	}

	public function column_status( $item ) {
		$status_class = 'status-' . $item->status;
		$output = '<div class="revora-status-col">';
		$output .= sprintf( '<select class="revora-inline-status %s" data-id="%d">', $status_class, $item->id );
		$output .= sprintf( '<option value="pending" %s>%s</option>', selected( $item->status, 'pending', false ), __( 'Pending', 'revora' ) );
		$output .= sprintf( '<option value="approved" %s>%s</option>', selected( $item->status, 'approved', false ), __( 'Approved', 'revora' ) );
		$output .= sprintf( '<option value="rejected" %s>%s</option>', selected( $item->status, 'rejected', false ), __( 'Rejected', 'revora' ) );
		$output .= '</select>';
		$output .= '</div>';
		
		return $output;
	}

	public function column_created_at( $item ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->created_at ) );
	}

	protected function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			$db = new Revora_DB();
			$forms = $db->get_forms();
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$selected_form = isset( $_REQUEST['form_filter'] ) && '' !== $_REQUEST['form_filter'] ? intval( wp_unslash( $_REQUEST['form_filter'] ) ) : -1;
			?>
			<div class="alignleft actions revora-table-filters">
				<div class="revora-filter-input-group">
					<svg class="revora-filter-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
					</svg>
					<select name="form_filter" id="filter-by-form" class="revora-filter-select" onchange="this.form.submit()">
						<option value="-1" <?php selected( $selected_form, -1 ); ?>><?php esc_html_e( 'All Forms', 'revora' ); ?></option>
						<option value="0" <?php selected( $selected_form, 0 ); ?>><?php esc_html_e( 'Default Form (Unassigned)', 'revora' ); ?></option>
						<?php foreach ( $forms as $f ) : ?>
							<option value="<?php echo esc_attr( $f->id ); ?>" <?php selected( $selected_form, (int) $f->id ); ?>><?php echo esc_html( $f->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<?php
		}
	}

	public function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'revora_reviews';

		$per_page = 20;
		$current_page = $this->get_pagenum();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// Search
		$search = ( ! empty( $_REQUEST['s'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		
		// Status filter
		$status = ( ! empty( $_REQUEST['status'] ) && 'all' !== $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : '';

		// Form filter
		$form_filter = isset( $_REQUEST['form_filter'] ) && '' !== $_REQUEST['form_filter'] ? intval( wp_unslash( $_REQUEST['form_filter'] ) ) : -1;

		// Whitelist sorting
		$sortable = $this->get_sortable_columns();
		if ( ! empty( $_GET['orderby'] ) && array_key_exists( sanitize_key( wp_unslash( $_GET['orderby'] ) ), $sortable ) ) {
			$orderby = sanitize_key( wp_unslash( $_GET['orderby'] ) );
		} else {
			$orderby = 'created_at';
		}

		$order = ( ! empty( $_GET['order'] ) && strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) === 'asc' ) ? 'ASC' : 'DESC';
		// phpcs:enable

		// Set column headers (CRITICAL for rendering)
		$this->_column_headers = array( $this->get_columns(), array(), $sortable );

		// Base query
		$query = "SELECT * FROM $table_name WHERE 1=1";
		$count_query = "SELECT COUNT(id) FROM $table_name WHERE 1=1";
		$params = array();

		if ( $status ) {
			$query .= " AND status = %s";
			$count_query .= " AND status = %s";
			$params[] = $status;
		}

		if ( $form_filter >= 0 ) {
			$query .= " AND form_id = %d";
			$count_query .= " AND form_id = %d";
			$params[] = $form_filter;
		}

		if ( $search ) {
			$search_like = '%' . $wpdb->esc_like( $search ) . '%';
			$sql_search = " AND (name LIKE %s OR email LIKE %s OR title LIKE %s OR content LIKE %s)";
			$query .= $sql_search;
			$count_query .= $sql_search;
			$params[] = $search_like;
			$params[] = $search_like;
			$params[] = $search_like;
			$params[] = $search_like;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total_items = $wpdb->get_var( $wpdb->prepare( $count_query, $params ) );

		$query .= " ORDER BY $orderby $order LIMIT %d OFFSET %d";
		$params[] = $per_page;
		$params[] = ( $current_page - 1 ) * $per_page;

		$this->items = $wpdb->get_results( $wpdb->prepare( $query, $params ) );
		// phpcs:enable

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
		) );
	}

	/**
	 * Get Status Views (Tabs)
	 */
	protected function get_views() {
		$db = new Revora_DB();
		$counts = $db->get_counts();
		
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$current = ( ! empty( $_REQUEST['status'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : 'all';

		$views = array();

		$states = array(
			'all'      => __( 'All', 'revora' ),
			'pending'  => __( 'Pending', 'revora' ),
			'approved' => __( 'Approved', 'revora' ),
			'rejected' => __( 'Rejected', 'revora' ),
		);

		foreach ( $states as $key => $label ) {
			$class = ( $current === $key ) ? 'current' : '';
			$url = add_query_arg( array( 'status' => $key, 's' => ( ! empty( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : null ) ), admin_url( 'admin.php?page=revora' ) );
			$views[ $key ] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>', $url, $class, $label, $counts[ $key ] );
		}
		// phpcs:enable

		return $views;
	}
}

/**
 * Category List Table Class
 */
class Revora_Category_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => 'category',
			'plural'   => 'categories',
			'ajax'     => false,
		) );
	}

	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'name'        => __( 'Name', 'revora' ),
			'description' => __( 'Description', 'revora' ),
			'slug'        => __( 'Slug', 'revora' ),
			'count'       => __( 'Reviews', 'revora' ),
		);
	}

	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="cat[]" value="%s" />', $item->id );
	}

	public function column_name( $item ) {
		$actions = array(
			'edit'   => sprintf( '<a href="?page=%s&action=%s&cat_id=%s">%s</a>', 'revora-categories', 'edit_cat', $item->id, __( 'Edit', 'revora' ) ),
			'delete' => sprintf( '<a href="%s" onclick="return confirm(\'Are you sure?\')">%s</a>', wp_nonce_url( '?page=revora-categories&action=delete_cat&cat_id=' . $item->id, 'revora_delete_cat_' . $item->id ), __( 'Delete', 'revora' ) ),
		);

		$prefix = ( $item->parent_id > 0 ) ? '— ' : '';

		return sprintf( '<strong>%s%s</strong>%s',
			$prefix,
			esc_html( $item->name ),
			$this->row_actions( $actions )
		);
	}

	public function column_description( $item ) {
		return esc_html( $item->description );
	}

	public function column_slug( $item ) {
		return '<code>' . esc_html( $item->slug ) . '</code>';
	}

	public function column_count( $item ) {
		global $wpdb;
		
		// Count distinct reviews associated with this category via the relationship table
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(DISTINCT r.id) 
			FROM {$wpdb->prefix}revora_reviews r
			INNER JOIN {$wpdb->prefix}revora_review_categories rc ON r.id = rc.review_id
			INNER JOIN {$wpdb->prefix}revora_categories c ON rc.cat_id = c.id
			WHERE c.slug = %s
		", $item->slug ) );
		// phpcs:enable
		
		return (int) $count;
	}

	public function prepare_items() {
		$db = new Revora_DB();
		$categories = $db->get_categories();

		// Hierarchical Sorting
		$hierarchical = array();
		$parents = array();
		foreach ( $categories as $cat ) {
			$cat_parent = isset( $cat->parent_id ) ? intval( $cat->parent_id ) : 0;
			if ( $cat_parent == 0 ) {
				$parents[] = $cat;
			}
		}

		foreach ( $parents as $parent ) {
			$hierarchical[] = $parent;
			foreach ( $categories as $child ) {
				$child_parent = isset( $child->parent_id ) ? intval( $child->parent_id ) : 0;
				if ( $child_parent == $parent->id ) {
					$hierarchical[] = $child;
				}
			}
		}

		$this->items = ! empty( $hierarchical ) ? $hierarchical : $categories;

		$this->_column_headers = array( $this->get_columns(), array(), array() );
	}
}

/**
 * Handle Deactivation Survey (Outside Main Admin Class to avoid complexity)
 */
add_action( 'wp_ajax_revora_submit_deactivation_feedback', function() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permission denied' );
	}

	check_ajax_referer( 'revora_deactivation_nonce', 'nonce' );
	
	$reason  = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );
	$details = sanitize_textarea_field( wp_unslash( $_POST['details'] ?? '' ) );
	$email   = get_option( 'admin_email' );

	// For now, we'll just send an email to the admin with the feedback
	$message = "Revora Deactivation Feedback\n\n";
	$message .= "Reason: " . $reason . "\n";
	$message .= "Details: " . $details . "\n";
	
	wp_mail( $email, 'Revora - Deactivation Feedback', $message );

	wp_send_json_success();
});

add_action( 'admin_footer', function() {
	$screen = get_current_screen();
	if ( ! $screen || 'plugins' !== $screen->id ) {
		return;
	}
	?>
	<div id="revora-deactivation-modal" class="revora-modal" style="display:none;">
		<div class="revora-modal-container">
			<div class="revora-modal-header">
				<div class="revora-modal-logo">
					<span class="dashicons dashicons-star-filled"></span>
					<h2><?php esc_html_e( 'We\'re sorry to see you go', 'revora' ); ?></h2>
				</div>
				<p><?php esc_html_e( 'If you have a moment, please let us know why you are deactivating Revora. Your feedback helps us improve.', 'revora' ); ?></p>
			</div>
			<div class="revora-modal-body">
				<form id="revora-deactivation-form">
					<ul class="revora-deactivation-reasons">
						<?php
						$reasons = array(
							'sudden-issue'   => __( 'I suddenly encountered a bug or technical issue', 'revora' ),
							'feature-missing' => __( 'I couldn\'t find a specific feature I needed', 'revora' ),
							'interface'      => __( 'The interface is difficult to use', 'revora' ),
							'temporary'      => __( 'It\'s only a temporary deactivation', 'revora' ),
							'another-plugin' => __( 'I found another plugin that works better', 'revora' ),
							'other'          => __( 'Other', 'revora' ),
						);
						foreach ( $reasons as $id => $label ) : ?>
							<li>
								<input type="radio" name="reason" id="revora-reason-<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $id ); ?>" required>
								<label for="revora-reason-<?php echo esc_attr( $id ); ?>">
									<?php echo esc_html( $label ); ?>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
					<div class="revora-other-reason" style="display:none;">
						<textarea name="details" placeholder="<?php esc_html_e( 'Please share more details...', 'revora' ); ?>" rows="3"></textarea>
					</div>
				</form>
			</div>
			<div class="revora-modal-footer">
				<button type="button" class="revora-modal-skip" id="revora-deactivate-skip"><?php esc_html_e( 'Skip & Deactivate', 'revora' ); ?></button>
				<button type="submit" form="revora-deactivation-form" class="revora-modal-submit" id="revora-deactivate-submit">
					<span class="revora-btn-text"><?php esc_html_e( 'Submit & Deactivate', 'revora' ); ?></span>
					<span class="revora-spinner" style="display:none;"></span>
				</button>
			</div>
		</div>
	</div>
	<?php
});
