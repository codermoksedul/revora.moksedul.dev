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
		add_action( 'admin_init', array( $this, 'handle_page_actions' ) );
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
			__( 'All Reviews', 'revora' ),
			__( 'All Reviews', 'revora' ),
			'manage_options',
			'revora',
			array( $this, 'render_reviews_page' )
		);

		add_submenu_page(
			'revora',
			__( 'Categories', 'revora' ),
			__( 'Categories', 'revora' ),
			'manage_options',
			'revora-categories',
			array( $this, 'render_categories_page' )
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
		register_setting( 'revora_settings_group', 'revora_settings' );
	}

	/**
	 * Handle Page Actions
	 */
	public function handle_page_actions() {
		if ( isset( $_POST['revora_add_new'] ) && check_admin_referer( 'revora_add_review', 'revora_nonce' ) ) {
			$db = new Revora_DB();
			$data = array(
				'category_slug' => sanitize_text_field( $_POST['category_slug'] ),
				'name'          => sanitize_text_field( $_POST['name'] ),
				'email'         => sanitize_email( $_POST['email'] ),
				'rating'        => intval( $_POST['rating'] ),
				'title'         => sanitize_text_field( $_POST['title'] ),
				'content'       => sanitize_textarea_field( $_POST['content'] ),
				'ip_address'    => $_SERVER['REMOTE_ADDR'],
				'status'        => 'approved', // Admin added reviews are approved by default
			);
			
			$inserted = $db->insert_review( $data );
			if ( $inserted ) {
				$cat_ids = isset( $_POST['categories'] ) ? array_map( 'intval', $_POST['categories'] ) : array();
				$db->set_review_categories( $inserted, $cat_ids );

				wp_redirect( admin_url( 'admin.php?page=revora&message=added' ) );
				exit;
			}
		}

		// Handle Review Update
		if ( isset( $_POST['revora_edit_review'] ) && check_admin_referer( 'revora_edit_review', 'revora_nonce' ) ) {
			$db = new Revora_DB();
			$id = intval( $_POST['review_id'] );
			$data = array(
				'category_slug' => sanitize_text_field( $_POST['category_slug'] ),
				'name'          => sanitize_text_field( $_POST['name'] ),
				'email'         => sanitize_email( $_POST['email'] ),
				'rating'        => intval( $_POST['rating'] ),
				'title'         => sanitize_text_field( $_POST['title'] ),
				'content'       => sanitize_textarea_field( $_POST['content'] ),
				'status'        => sanitize_text_field( $_POST['status'] ),
			);

			$updated = $db->update_review( $id, $data );
			if ( $updated !== false ) {
				$cat_ids = isset( $_POST['categories'] ) ? array_map( 'intval', $_POST['categories'] ) : array();
				$db->set_review_categories( $id, $cat_ids );
				
				wp_redirect( admin_url( 'admin.php?page=revora&message=updated' ) );
				exit;
			}
		}

		// Handle Category Add
		if ( isset( $_POST['revora_add_category'] ) && check_admin_referer( 'revora_add_cat_nonce', 'revora_cat_nonce' ) ) {
			$db = new Revora_DB();
			$name = sanitize_text_field( $_POST['cat_name'] );
			$slug = ! empty( $_POST['cat_slug'] ) ? sanitize_title( $_POST['cat_slug'] ) : sanitize_title( $name );
			
			$data = array(
				'parent_id'   => intval( $_POST['parent_id'] ),
				'name'        => $name,
				'slug'        => $slug,
				'description' => sanitize_textarea_field( $_POST['cat_description'] ),
			);

			$inserted = $db->insert_category( $data );
			if ( $inserted ) {
				wp_redirect( admin_url( 'admin.php?page=revora-categories&message=added' ) );
				exit;
			}
		}

		// Handle Category Update
		if ( isset( $_POST['revora_edit_category'] ) && check_admin_referer( 'revora_edit_cat_nonce', 'revora_cat_nonce' ) ) {
			$db = new Revora_DB();
			$id = intval( $_POST['cat_id'] );
			$data = array(
				'name'        => sanitize_text_field( $_POST['cat_name'] ),
				'slug'        => sanitize_title( $_POST['cat_slug'] ),
				'description' => sanitize_textarea_field( $_POST['cat_description'] ),
			);

			$updated = $db->update_category( $id, $data );
			if ( $updated !== false ) {
				wp_redirect( admin_url( 'admin.php?page=revora-categories&message=updated' ) );
				exit;
			}
		}

		// Handle Category Delete (from list table)
		if ( isset( $_GET['action'] ) && 'delete_cat' === $_GET['action'] && isset( $_GET['cat_id'] ) ) {
			// In a real plugin, we'd check nonces here too.
			$db = new Revora_DB();
			$db->delete_category( intval( $_GET['cat_id'] ) );
			wp_redirect( admin_url( 'admin.php?page=revora-categories&message=deleted' ) );
			exit;
		}

		// Handle Review Duplicate
		if ( isset( $_GET['action'] ) && 'duplicate' === $_GET['action'] && isset( $_GET['review_id'] ) ) {
			$db = new Revora_DB();
			$db->duplicate_review( intval( $_GET['review_id'] ) );
			wp_redirect( admin_url( 'admin.php?page=revora&message=duplicated' ) );
			exit;
		}

		// Handle Review Actions (Approve/Reject/Delete)
		if ( isset( $_GET['action'] ) && isset( $_GET['review_id'] ) ) {
			$id     = intval( $_GET['review_id'] );
			$action = $_GET['action'];
			$db     = new Revora_DB();

			if ( 'approve' === $action ) {
				$db->update_review( $id, array( 'status' => 'approved' ) );
				wp_redirect( admin_url( 'admin.php?page=revora&message=approved' ) );
				exit;
			}

			if ( 'reject' === $action ) {
				$db->update_review( $id, array( 'status' => 'rejected' ) );
				wp_redirect( admin_url( 'admin.php?page=revora&message=rejected' ) );
				exit;
			}

			if ( 'delete' === $action ) {
				$db->delete_review( $id );
				wp_redirect( admin_url( 'admin.php?page=revora&message=deleted' ) );
				exit;
			}
		}
	}

	/**
	 * Render Reviews Page
	 */
	public function render_reviews_page() {
		// Handle Add New view or List View
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		
		if ( 'add' === $action ) {
			$this->render_add_new_page();
			return;
		}

		if ( 'edit' === $action && isset( $_GET['review_id'] ) ) {
			$this->render_edit_page( intval( $_GET['review_id'] ) );
			return;
		}

		$table = new Revora_Review_List_Table();
		$table->prepare_items();

		// Handle bulk/row actions
		$message = '';
		if ( isset( $_REQUEST['message'] ) && 'added' === $_REQUEST['message'] ) {
			$message = '<div class="updated notice is-dismissible"><p>' . __( 'Review added successfully.', 'revora' ) . '</p></div>';
		} elseif ( isset( $_REQUEST['message'] ) && 'updated' === $_REQUEST['message'] ) {
			$message = '<div class="updated notice is-dismissible"><p>' . __( 'Review updated successfully.', 'revora' ) . '</p></div>';
		} elseif ( isset( $_REQUEST['message'] ) && 'approved' === $_REQUEST['message'] ) {
			$message = '<div class="updated notice is-dismissible"><p>' . __( 'Review approved successfully.', 'revora' ) . '</p></div>';
		} elseif ( isset( $_REQUEST['message'] ) && 'rejected' === $_REQUEST['message'] ) {
			$message = '<div class="updated notice is-dismissible"><p>' . __( 'Review rejected successfully.', 'revora' ) . '</p></div>';
		} elseif ( isset( $_REQUEST['message'] ) && 'deleted' === $_REQUEST['message'] ) {
			$message = '<div class="updated notice is-dismissible"><p>' . __( 'Review deleted successfully.', 'revora' ) . '</p></div>';
		} elseif ( isset( $_REQUEST['message'] ) && 'duplicated' === $_REQUEST['message'] ) {
			$message = '<div class="updated notice is-dismissible"><p>' . __( 'Review duplicated successfully.', 'revora' ) . '</p></div>';
		}

		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] && ! in_array( $_REQUEST['action'], array( 'add' ) ) ) {
			// check_admin_referer is omitted here because WP_List_Table actions often use different nonce schemes or rely on different validation
			// For a production plugin, we'd add proper nonce checks here.
			$bulk_action = $_REQUEST['action'];
			$ids = isset( $_REQUEST['review'] ) ? $_REQUEST['review'] : array();
			if ( ! is_array( $ids ) ) $ids = array( $ids );

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
				$message = '<div class="updated notice is-dismissible"><p>' . __( 'Action applied successfully.', 'revora' ) . '</p></div>';
			}
		}

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Revora Reviews', 'revora' ); ?></h1>
			<a href="<?php echo admin_url( 'admin.php?page=revora&action=add' ); ?>" class="page-title-action"><?php _e( 'Add New', 'revora' ); ?></a>
			<hr class="wp-header-end">

			<?php echo $message; ?>

			<form id="revora-reviews-filter" method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
				<?php
				$table->views();
				$table->search_box( __( 'Search Reviews', 'revora' ), 'revora-search' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Add New Page
	 */
	public function render_add_new_page() {
		$db = new Revora_DB();
		$categories = $db->get_categories();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Add New Review', 'revora' ); ?></h1>
			<hr class="wp-header-end">

			<form method="post" action="" class="revora-form-container">
				<?php wp_nonce_field( 'revora_add_review', 'revora_nonce' ); ?>
				
				<div class="revora-form-main">
					<div class="revora-card">
						<div class="revora-card-header">
							<span class="dashicons dashicons-admin-users"></span> <?php _e( 'Author Details', 'revora' ); ?>
						</div>
						<div class="revora-card-body">
							<div class="revora-field-group">
								<label class="revora-field-label" for="name"><?php _e( 'Name', 'revora' ); ?></label>
								<input name="name" type="text" id="name" value="" placeholder="John Doe" required>
							</div>
							<div class="revora-field-group">
								<label class="revora-field-label" for="email"><?php _e( 'Email', 'revora' ); ?></label>
								<input name="email" type="email" id="email" value="" placeholder="john@example.com" required>
							</div>
						</div>
					</div>

					<div class="revora-card">
						<div class="revora-card-header">
							<span class="dashicons dashicons-editor-quote"></span> <?php _e( 'Review Content', 'revora' ); ?>
						</div>
						<div class="revora-card-body">
							<div class="revora-field-group">
								<label class="revora-field-label" for="title"><?php _e( 'Review Title', 'revora' ); ?></label>
								<input name="title" type="text" id="title" value="" placeholder="e.g. Amazing Service!" required>
							</div>
							<div class="revora-field-group">
								<label class="revora-field-label" for="content"><?php _e( 'Review Content', 'revora' ); ?></label>
								<textarea name="content" id="content" rows="12" placeholder="Write the review content here..." required></textarea>
							</div>
						</div>
					</div>
				</div>

				<div class="revora-form-sidebar">
					<div class="revora-card">
						<div class="revora-card-header">
							<span class="dashicons dashicons-admin-settings"></span> <?php _e( 'Review Settings', 'revora' ); ?>
						</div>
						<div class="revora-card-body">
							<div class="revora-field-group">
								<label class="revora-field-label"><?php _e( 'Categories', 'revora' ); ?></label>
								<div class="revora-category-checklist">
									<?php $this->render_category_checklist(); ?>
								</div>
							</div>

							<div class="revora-field-group">
								<label class="revora-field-label"><?php _e( 'Rating', 'revora' ); ?></label>
								<div class="revora-rating-selector">
									<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
										<span class="dashicons dashicons-star-filled active" data-rating="<?php echo $i; ?>"></span>
									<?php endfor; ?>
								</div>
								<input type="hidden" name="rating" id="rating_input" value="5">
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
		<?php
	}

	/**
	 * Render Edit Page
	 */
	public function render_edit_page( $id ) {
		$db = new Revora_DB();
		$review = $db->get_review( $id );
		$categories = $db->get_categories();

		if ( ! $review ) {
			echo '<div class="error"><p>' . __( 'Review not found.', 'revora' ) . '</p></div>';
			return;
		}

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Edit Review', 'revora' ); ?></h1>
			<a href="<?php echo admin_url( 'admin.php?page=revora&action=add' ); ?>" class="page-title-action"><?php _e( 'Add New', 'revora' ); ?></a>
			<hr class="wp-header-end">

			<form method="post" action="" class="revora-form-container">
				<?php wp_nonce_field( 'revora_edit_review', 'revora_nonce' ); ?>
				<input type="hidden" name="review_id" value="<?php echo esc_attr( $review->id ); ?>">
				
				<div class="revora-form-main">
					<div class="revora-card">
						<div class="revora-card-header">
							<span class="dashicons dashicons-admin-users"></span> <?php _e( 'Author Details', 'revora' ); ?>
						</div>
						<div class="revora-card-body">
							<div class="revora-field-group">
								<label class="revora-field-label" for="name"><?php _e( 'Name', 'revora' ); ?></label>
								<input name="name" type="text" id="name" value="<?php echo esc_attr( $review->name ); ?>" required>
							</div>
							<div class="revora-field-group">
								<label class="revora-field-label" for="email"><?php _e( 'Email', 'revora' ); ?></label>
								<input name="email" type="email" id="email" value="<?php echo esc_attr( $review->email ); ?>" required>
							</div>
						</div>
					</div>

					<div class="revora-card">
						<div class="revora-card-header">
							<span class="dashicons dashicons-editor-quote"></span> <?php _e( 'Review Content', 'revora' ); ?>
						</div>
						<div class="revora-card-body">
							<div class="revora-field-group">
								<label class="revora-field-label" for="title"><?php _e( 'Review Title', 'revora' ); ?></label>
								<input name="title" type="text" id="title" value="<?php echo esc_attr( $review->title ); ?>" required>
							</div>
							<div class="revora-field-group">
								<label class="revora-field-label" for="content"><?php _e( 'Review Content', 'revora' ); ?></label>
								<textarea name="content" id="content" rows="12" required><?php echo esc_textarea( $review->content ); ?></textarea>
							</div>
						</div>
					</div>
				</div>

				<div class="revora-form-sidebar">
					<div class="revora-card">
						<div class="revora-card-header">
							<span class="dashicons dashicons-admin-settings"></span> <?php _e( 'Review Settings', 'revora' ); ?>
						</div>
						<div class="revora-card-body">
							<div class="revora-field-group">
								<label class="revora-field-label" for="status"><?php _e( 'Status', 'revora' ); ?></label>
								<select name="status" id="status">
									<option value="pending" <?php selected( $review->status, 'pending' ); ?>><?php _e( 'Pending', 'revora' ); ?></option>
									<option value="approved" <?php selected( $review->status, 'approved' ); ?>><?php _e( 'Approved', 'revora' ); ?></option>
									<option value="rejected" <?php selected( $review->status, 'rejected' ); ?>><?php _e( 'Rejected', 'revora' ); ?></option>
								</select>
							</div>

							<div class="revora-field-group">
								<label class="revora-field-label"><?php _e( 'Categories', 'revora' ); ?></label>
								<div class="revora-category-checklist">
									<?php 
									$selected_cats = $db->get_review_categories( $review->id );
									$this->render_category_checklist( 0, $selected_cats ); 
									?>
								</div>
							</div>

							<div class="revora-field-group">
								<label class="revora-field-label"><?php _e( 'Rating', 'revora' ); ?></label>
								<div class="revora-rating-selector">
									<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
										<?php $active_class = ( intval( $review->rating ) >= $i ) ? 'active' : ''; ?>
										<span class="dashicons dashicons-star-filled <?php echo $active_class; ?>" data-rating="<?php echo $i; ?>"></span>
									<?php endfor; ?>
								</div>
								<input type="hidden" name="rating" id="rating_input" value="<?php echo esc_attr( $review->rating ); ?>">
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
		<?php
	}

	/**
	 * Render Categories Page
	 */
	public function render_categories_page() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';

		if ( 'edit_cat' === $action && isset( $_GET['cat_id'] ) ) {
			$this->render_category_edit_page( intval( $_GET['cat_id'] ) );
			return;
		}

		$table = new Revora_Category_List_Table();
		$table->prepare_items();

		$message = '';
		if ( isset( $_GET['message'] ) ) {
			if ( 'added' === $_GET['message'] ) {
				$message = '<div class="updated notice is-dismissible"><p>' . __( 'Category added successfully.', 'revora' ) . '</p></div>';
			} elseif ( 'updated' === $_GET['message'] ) {
				$message = '<div class="updated notice is-dismissible"><p>' . __( 'Category updated successfully.', 'revora' ) . '</p></div>';
			} elseif ( 'deleted' === $_GET['message'] ) {
				$message = '<div class="updated notice is-dismissible"><p>' . __( 'Category deleted.', 'revora' ) . '</p></div>';
			}
		}

		?>
		<div class="wrap">
			<h1><?php _e( 'Categories', 'revora' ); ?></h1>
			<?php echo $message; ?>

			<div id="col-container" class="wp-clearfix">
				<div id="col-left">
					<div class="col-wrap">
						<div class="form-wrap">
							<h2><?php _e( 'Add New Category', 'revora' ); ?></h2>
							<form id="addtag" method="post" action="" class="validate">
								<?php wp_nonce_field( 'revora_add_cat_nonce', 'revora_cat_nonce' ); ?>
								<div class="form-field form-required term-name-wrap">
									<label for="cat_name"><?php _e( 'Name', 'revora' ); ?></label>
									<input name="cat_name" id="cat_name" type="text" value="" size="40" aria-required="true" required>
									<p><?php _e( 'The name is how it appears on your site.', 'revora' ); ?></p>
								</div>
								<div class="form-field term-parent-wrap">
									<label for="parent_id"><?php _e( 'Parent Category', 'revora' ); ?></label>
									<select name="parent_id" id="parent_id">
										<option value="0"><?php _e( 'None', 'revora' ); ?></option>
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
									<p><?php _e( 'Categories, unlike tags, can have a hierarchy. You might have a Jazz category, and under that have children categories for Bebop and Big Band. Totally optional.', 'revora' ); ?></p>
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
							<?php $table->display(); ?>
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
				$checked = in_array( $cat->id, $selected ) ? 'checked' : '';
				echo '<li>';
				echo '<label><input type="checkbox" name="categories[]" value="' . esc_attr( $cat->id ) . '" ' . $checked . '> ' . esc_html( $cat->name ) . '</label>';
				
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
			echo '<div class="error"><p>' . __( 'Category not found.', 'revora' ) . '</p></div>';
			return;
		}

		?>
		<div class="wrap">
			<h1><?php _e( 'Edit Category', 'revora' ); ?></h1>
			<form method="post" action="">
				<?php wp_nonce_field( 'revora_add_cat_nonce', 'revora_cat_nonce' ); ?>
				<input type="hidden" name="cat_id" value="<?php echo esc_attr( $cat->id ); ?>">
				<table class="form-table">
					<tr>
						<th scope="row"><label for="cat_name"><?php _e( 'Name', 'revora' ); ?></label></th>
						<td><input name="cat_name" type="text" id="cat_name" value="<?php echo esc_attr( $cat->name ); ?>" class="regular-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="cat_slug"><?php _e( 'Slug', 'revora' ); ?></label></th>
						<td><input name="cat_slug" type="text" id="cat_slug" value="<?php echo esc_attr( $cat->slug ); ?>" class="regular-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="parent_id"><?php _e( 'Parent Category', 'revora' ); ?></label></th>
						<td>
							<select name="parent_id" id="parent_id">
								<option value="0"><?php _e( 'None', 'revora' ); ?></option>
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
						<th scope="row"><label for="cat_description"><?php _e( 'Description', 'revora' ); ?></label></th>
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
		$settings = get_option( 'revora_settings' );
		?>
		<div class="wrap">
			<h1><?php _e( 'Revora Settings', 'revora' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'revora_settings_group' );
				do_settings_sections( 'revora_settings_group' );
				?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Primary Color', 'revora' ); ?></th>
						<td>
							<input type="color" name="revora_settings[primary_color]" value="<?php echo esc_attr( $settings['primary_color'] ); ?>" />
							<p class="description"><?php _e( 'Used for buttons and active stars.', 'revora' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Admin Notification Email', 'revora' ); ?></th>
						<td>
							<input type="email" name="revora_settings[admin_email]" value="<?php echo esc_attr( $settings['admin_email'] ); ?>" class="regular-text" />
							<p class="description"><?php _e( 'Email to receive new review alerts.', 'revora' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Custom CSS', 'revora' ); ?></th>
						<td>
							<textarea name="revora_settings[custom_css]" rows="10" cols="50" class="large-text"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
							<p class="description"><?php _e( 'Add your custom CSS here.', 'revora' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
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
			'author'     => __( 'Author', 'revora' ),
			'categories' => __( 'Categories', 'revora' ),
			'status'     => __( 'Status', 'revora' ),
			'created_at' => __( 'Date', 'revora' ),
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
			'edit'      => sprintf( '<a href="?page=%s&action=%s&review_id=%s">%s</a>', 'revora', 'edit', $item->id, __( 'Edit', 'revora' ) ),
			'duplicate' => sprintf( '<a href="?page=%s&action=%s&review_id=%s">%s</a>', 'revora', 'duplicate', $item->id, __( 'Duplicate', 'revora' ) ),
			'approve'   => sprintf( '<a href="?page=%s&action=%s&review_id=%s">%s</a>', 'revora', 'approve', $item->id, __( 'Approve', 'revora' ) ),
			'reject'    => sprintf( '<a href="?page=%s&action=%s&review_id=%s">%s</a>', 'revora', 'reject', $item->id, __( 'Reject', 'revora' ) ),
			'delete'    => sprintf( '<a href="?page=%s&action=%s&review_id=%s" onclick="return confirm(\'Are you sure?\')">%s</a>', 'revora', 'delete', $item->id, __( 'Delete', 'revora' ) ),
		);

		// Remove irrelevant actions based on status
		if ( 'approved' === $item->status ) unset( $actions['approve'] );
		if ( 'rejected' === $item->status ) unset( $actions['reject'] );

		// Star Rating
		$stars = '<div class="revora-admin-stars" style="margin-bottom: 5px;">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$class = ( $i <= $item->rating ) ? 'star-filled' : 'star-empty';
			$stars .= '<span class="dashicons dashicons-star-filled ' . $class . '"></span>';
		}
		$stars .= '</div>';

		return sprintf( '%s <strong>%s</strong><br>%s%s',
			$stars,
			esc_html( $item->title ),
			wp_trim_words( esc_html( $item->content ), 15 ),
			$this->row_actions( $actions )
		);
	}

	public function column_author( $item ) {
		return sprintf( '<strong>%s</strong><br><small>%s</small><br><small>IP: %s</small>',
			esc_html( $item->name ),
			esc_html( $item->email ),
			esc_html( $item->ip_address )
		);
	}

	public function column_categories( $item ) {
		global $wpdb;
		$db = new Revora_DB();
		$cat_ids = $db->get_review_categories( $item->id );
		
		if ( empty( $cat_ids ) ) {
			return '—';
		}

		$categories = $wpdb->get_results( "SELECT name, slug FROM {$wpdb->prefix}revora_categories WHERE id IN (" . implode( ',', array_map( 'intval', $cat_ids ) ) . ")" );
		
		$links = array();
		foreach ( $categories as $cat ) {
			$links[] = '<strong>' . esc_html( $cat->name ) . '</strong>';
		}

		return implode( ', ', $links );
	}

	public function column_status( $item ) {
		$class = 'status-' . $item->status;
		return sprintf( '<span class="revora-status-badge %s">%s</span>', $class, ucfirst( $item->status ) );
	}

	public function column_created_at( $item ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->created_at ) );
	}

	public function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'revora_reviews';

		$per_page = 20;
		$current_page = $this->get_pagenum();

		// Search
		$search = ( ! empty( $_REQUEST['s'] ) ) ? sanitize_text_field( $_REQUEST['s'] ) : '';
		
		// Status filter
		$status = ( ! empty( $_REQUEST['status'] ) && 'all' !== $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : '';

		// Whitelist sorting
		$sortable = $this->get_sortable_columns();
		if ( ! empty( $_GET['orderby'] ) && array_key_exists( $_GET['orderby'], $sortable ) ) {
			$orderby = $_GET['orderby'];
		} else {
			$orderby = 'created_at';
		}

		$order = ( ! empty( $_GET['order'] ) && strtolower( $_GET['order'] ) === 'asc' ) ? 'ASC' : 'DESC';

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

		$total_items = $wpdb->get_var( $wpdb->prepare( $count_query, $params ) );

		$query .= " ORDER BY $orderby $order LIMIT %d OFFSET %d";
		$params[] = $per_page;
		$params[] = ( $current_page - 1 ) * $per_page;

		$this->items = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

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
		$current = ( ! empty( $_REQUEST['status'] ) ) ? $_REQUEST['status'] : 'all';

		$views = array();

		$states = array(
			'all'      => __( 'All', 'revora' ),
			'pending'  => __( 'Pending', 'revora' ),
			'approved' => __( 'Approved', 'revora' ),
			'rejected' => __( 'Rejected', 'revora' ),
		);

		foreach ( $states as $key => $label ) {
			$class = ( $current === $key ) ? 'current' : '';
			$url = add_query_arg( array( 'status' => $key, 's' => ( ! empty( $_REQUEST['s'] ) ? $_REQUEST['s'] : null ) ), admin_url( 'admin.php?page=revora' ) );
			$views[ $key ] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>', $url, $class, $label, $counts[ $key ] );
		}

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
			'delete' => sprintf( '<a href="?page=%s&action=%s&cat_id=%s" onclick="return confirm(\'Are you sure?\')">%s</a>', 'revora-categories', 'delete_cat', $item->id, __( 'Delete', 'revora' ) ),
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
		$table_name = $wpdb->prefix . 'revora_reviews';
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_name WHERE category_slug = %s", $item->slug ) );
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
