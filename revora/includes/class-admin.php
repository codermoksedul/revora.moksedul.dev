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
			__( 'Reviews', 'revora' ),
			'manage_options',
			'revora',
			array( $this, 'render_reviews_page' )
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
	 * Render Reviews Page
	 */
	public function render_reviews_page() {
		$table = new Revora_Review_List_Table();
		$table->prepare_items();

		// Handle actions
		$message = '';
		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
			$action = $_REQUEST['action'];
			$ids = isset( $_REQUEST['review'] ) ? $_REQUEST['review'] : array();
			if ( ! is_array( $ids ) ) $ids = array( $ids );

			$db = new Revora_DB();
			foreach ( $ids as $id ) {
				if ( 'approve' === $action ) {
					$db->update_status( $id, 'approved' );
				} elseif ( 'reject' === $action ) {
					$db->update_status( $id, 'rejected' );
				} elseif ( 'delete' === $action ) {
					$db->delete_review( $id );
				}
			}
			$message = '<div class="updated notice is-dismissible"><p>' . __( 'Bulk action applied.', 'revora' ) . '</p></div>';
		}

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Revora Reviews', 'revora' ); ?></h1>
			<?php echo $message; ?>
			<form id="revora-reviews-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
				<?php $table->display(); ?>
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
			'cb'            => '<input type="checkbox" />',
			'rating'        => __( 'Rating', 'revora' ),
			'content'       => __( 'Review', 'revora' ),
			'author'        => __( 'Author', 'revora' ),
			'category_slug' => __( 'Category', 'revora' ),
			'status'        => __( 'Status', 'revora' ),
			'created_at'    => __( 'Date', 'revora' ),
		);
	}

	protected function get_bulk_actions() {
		return array(
			'approve' => __( 'Approve', 'revora' ),
			'reject'  => __( 'Reject', 'revora' ),
			'delete'  => __( 'Delete Permanently', 'revora' ),
		);
	}

	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="review[]" value="%s" />', $item->id );
	}

	public function column_rating( $item ) {
		$output = '<div class="revora-admin-stars">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$class = ( $i <= $item->rating ) ? 'star-filled' : 'star-empty';
			$output .= '<span class="dashicons dashicons-star-filled ' . $class . '"></span>';
		}
		$output .= '</div>';
		return $output;
	}

	public function column_content( $item ) {
		$actions = array(
			'approve' => sprintf( '<a href="?page=%s&action=%s&review=%s">%s</a>', $_REQUEST['page'], 'approve', $item->id, __( 'Approve', 'revora' ) ),
			'reject'  => sprintf( '<a href="?page=%s&action=%s&review=%s">%s</a>', $_REQUEST['page'], 'reject', $item->id, __( 'Reject', 'revora' ) ),
			'delete'  => sprintf( '<a href="?page=%s&action=%s&review=%s">%s</a>', $_REQUEST['page'], 'delete', $item->id, __( 'Delete', 'revora' ) ),
		);

		// Remove irrelevant actions based on status
		if ( 'approved' === $item->status ) unset( $actions['approve'] );
		if ( 'rejected' === $item->status ) unset( $actions['reject'] );

		return sprintf( '<strong>%s</strong><p>%s</p>%s',
			esc_html( $item->title ),
			esc_html( $item->content ),
			$this->row_actions( $actions )
		);
	}

	public function column_author( $item ) {
		return sprintf( '<strong>%s</strong><br>%s<br><small>IP: %s</small>',
			esc_html( $item->name ),
			esc_html( $item->email ),
			esc_html( $item->ip_address )
		);
	}

	public function column_category_slug( $item ) {
		return '<code>' . esc_html( $item->category_slug ) . '</code>';
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

		// Sorting
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'created_at';
		$order = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'DESC';

		// Pagination
		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );

		$this->items = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
			$per_page,
			( $current_page - 1 ) * $per_page
		) );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
		) );
	}
}
