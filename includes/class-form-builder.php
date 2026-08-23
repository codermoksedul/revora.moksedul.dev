<?php
/**
 * Form Builder Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Revora_Form_Builder {

	public function render_page() {
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

		if ( 'add' === $action ) {
			$this->render_edit_page();
			return;
		}

		if ( 'edit' === $action && isset( $_GET['form_id'] ) ) {
			$this->render_edit_page( intval( wp_unslash( $_GET['form_id'] ) ) );
			return;
		}

		$this->render_list_page();
	}

	private function render_list_page() {
		$table = new Revora_Form_List_Table();
		$table->prepare_items();
		
		$message = '';
		if ( isset( $_GET['message'] ) ) {
			if ( 'added' === $_GET['message'] ) {
				$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Form added.', 'revora' ) . '</p></div>';
			} elseif ( 'updated' === $_GET['message'] ) {
				$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Form updated.', 'revora' ) . '</p></div>';
			} elseif ( 'deleted' === $_GET['message'] ) {
				$message = '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Form deleted.', 'revora' ) . '</p></div>';
			}
		}

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Revora Forms', 'revora' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=revora-forms&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Form', 'revora' ); ?></a>
			<hr class="wp-header-end">
			<?php echo wp_kses_post( $message ); ?>
			<form id="revora-forms-filter" method="get">
				<input type="hidden" name="page" value="revora-forms" />
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}

	private function render_edit_page( $id = 0 ) {
		$db = new Revora_DB();
		$form = $id ? $db->get_form( $id ) : null;
		
		$name = $form ? $form->name : '';
		$fields = $form ? json_decode( $form->fields, true ) : array();
		$settings = $form ? json_decode( $form->settings, true ) : array( 'submit_text' => 'Submit Review' );

		?>
		<div class="wrap revora-builder-wrap">
			<h1 class="wp-heading-inline"><?php echo $id ? esc_html__( 'Edit Form', 'revora' ) : esc_html__( 'Add New Form', 'revora' ); ?></h1>
			<hr class="wp-header-end">
			
			<form method="post" action="" id="revora-builder-form">
				<?php wp_nonce_field( 'revora_save_form', 'revora_form_nonce' ); ?>
				<?php if ( $id ) : ?>
					<input type="hidden" name="form_id" value="<?php echo esc_attr( $id ); ?>">
				<?php endif; ?>
				<input type="hidden" name="revora_save_form_action" value="1">
				
				<!-- Hidden fields container to submit JSON payload -->
				<div id="revora-hidden-payloads"></div>

			<!-- Top Bar: Form Name -->
			<div class="revora-builder-topbar">
				<div class="revora-topbar-left">
					<span class="revora-topbar-label"><?php esc_html_e( 'Form Name:', 'revora' ); ?></span>
					<input type="text" name="form_name" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php esc_attr_e( 'Enter form name...', 'revora' ); ?>" class="revora-topbar-name-input" required>
				</div>
				<div class="revora-topbar-right">
					<button type="button" id="revora-save-form-btn" class="revora-topbar-save-btn">
						<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Form', 'revora' ); ?>
					</button>
				</div>
			</div>

			<div class="revora-form-container revora-fluent-layout">
				
				<!-- LEFT: Canvas -->
				<div class="revora-builder-canvas-wrap">
					<div class="revora-form-main revora-builder-canvas">
						
						<div id="revora-sortable-canvas" class="revora-sortable-area">
							<!-- Fields injected via JS -->
						</div>
						
						<div class="revora-canvas-add-area">
							<button type="button" class="revora-canvas-add-btn">
								<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Field', 'revora' ); ?>
							</button>
						</div>
						
					</div><!-- /.revora-builder-canvas -->
				</div><!-- /.revora-builder-canvas-wrap -->

				<!-- RIGHT: Sidebar -->
				<div class="revora-form-sidebar revora-builder-sidebar">
					
					<div class="revora-sidebar-tabs">
						<div class="revora-sidebar-tab active" data-target="tab-add-fields"><?php esc_html_e( 'Fields', 'revora' ); ?></div>
						<div class="revora-sidebar-tab" data-target="tab-field-settings" id="tab-nav-field-settings"><?php esc_html_e( 'Settings', 'revora' ); ?></div>
						<div class="revora-sidebar-tab" data-target="tab-form-settings"><?php esc_html_e( 'Form', 'revora' ); ?></div>
					</div>

					<div class="revora-sidebar-content">
						
						<!-- Add Fields Tab -->
						<div id="tab-add-fields" class="revora-tab-pane active">
							<h4 class="revora-sidebar-heading"><?php esc_html_e( 'General Fields', 'revora' ); ?></h4>
							<div class="revora-field-buttons-grid">
								<button type="button" class="revora-add-field-btn" data-type="text" data-label="Text Field"><span class="dashicons dashicons-text-page"></span> Text</button>
								<button type="button" class="revora-add-field-btn" data-type="email" data-label="Email Address"><span class="dashicons dashicons-email"></span> Email</button>
								<button type="button" class="revora-add-field-btn" data-type="textarea" data-label="Text Area"><span class="dashicons dashicons-editor-justify"></span> Textarea</button>
								<button type="button" class="revora-add-field-btn" data-type="rating" data-label="Rating"><span class="dashicons dashicons-star-filled"></span> Rating</button>
								<button type="button" class="revora-add-field-btn" data-type="avatar" data-label="Profile Image"><span class="dashicons dashicons-admin-users"></span> Profile Image</button>
								<button type="button" class="revora-add-field-btn" data-type="file" data-label="File Upload"><span class="dashicons dashicons-upload"></span> File</button>
								
								<button type="button" class="revora-add-field-btn" data-type="number" data-label="Number"><span class="dashicons dashicons-editor-ol"></span> Number</button>
								<button type="button" class="revora-add-field-btn" data-type="tel" data-label="Phone Number"><span class="dashicons dashicons-phone"></span> Phone</button>
								<button type="button" class="revora-add-field-btn" data-type="url" data-label="Website URL"><span class="dashicons dashicons-admin-links"></span> URL</button>
								<button type="button" class="revora-add-field-btn" data-type="date" data-label="Date"><span class="dashicons dashicons-calendar-alt"></span> Date</button>
								
								<button type="button" class="revora-add-field-btn" data-type="select" data-label="Dropdown"><span class="dashicons dashicons-menu-alt3"></span> Dropdown</button>
								<button type="button" class="revora-add-field-btn" data-type="radio" data-label="Radio Buttons"><span class="dashicons dashicons-marker"></span> Radio</button>
								<button type="button" class="revora-add-field-btn" data-type="checkbox" data-label="Checkboxes"><span class="dashicons dashicons-yes-alt"></span> Checkbox</button>
								<button type="button" class="revora-add-field-btn" data-type="submit" data-label="Submit Button"><span class="dashicons dashicons-button"></span> Submit</button>
							</div>
								
								<h4 class="revora-sidebar-heading" style="margin-top:25px;"><?php esc_html_e( 'Container Fields', 'revora' ); ?></h4>
								<div class="revora-field-buttons-grid">
									<button type="button" class="revora-add-field-btn" data-type="row" data-label="2 Column Row"><span class="dashicons dashicons-columns"></span> 2-Cols</button>
								</div>
							</div>

							<!-- Field Settings Tab -->
							<div id="tab-field-settings" class="revora-tab-pane">
								<div id="no-field-selected" class="revora-empty-state">
									<p><?php esc_html_e( 'Click a field to edit its settings.', 'revora' ); ?></p>
								</div>
								<div id="active-field-settings" style="display:none;">
									<!-- Label -->
									<div class="revora-field-group" id="s-label-wrap">
										<label class="revora-field-label"><?php esc_html_e( 'Label', 'revora' ); ?></label>
										<input type="text" id="setting-label" class="widefat">
									</div>
									<!-- Meta Key -->
									<div class="revora-field-group" id="s-key-wrap">
										<label class="revora-field-label"><?php esc_html_e( 'Meta Key', 'revora' ); ?></label>
										<input type="text" id="setting-key" class="widefat" placeholder="e.g. phone_number">
										<small class="description"><?php esc_html_e( 'Used to save data. Must be unique.', 'revora' ); ?></small>
									</div>
									<!-- Placeholder -->
									<div class="revora-field-group" id="s-placeholder-wrap">
										<label class="revora-field-label"><?php esc_html_e( 'Placeholder', 'revora' ); ?></label>
										<input type="text" id="setting-placeholder" class="widefat">
									</div>
									<!-- Allowed File Types -->
									<div class="revora-field-group" id="s-allowed-types-wrap" style="display:none;">
										<label class="revora-field-label"><?php esc_html_e( 'Allowed File Types', 'revora' ); ?></label>
										<input type="text" id="setting-allowed-types" class="widefat" placeholder="jpg, png, webp, pdf">
										<small class="description"><?php esc_html_e( 'Comma-separated file extensions (e.g. jpg, png, webp, pdf).', 'revora' ); ?></small>
									</div>
									<!-- Options -->
									<div class="revora-field-group" id="s-options-wrap" style="display:none;">
										<label class="revora-field-label"><?php esc_html_e( 'Options', 'revora' ); ?></label>
										<textarea id="setting-options" class="widefat" rows="5" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
										<small class="description"><?php esc_html_e( 'One option per line.', 'revora' ); ?></small>
									</div>
									<!-- Required toggle -->
									<div class="revora-field-group" id="s-required-wrap">
										<label class="revora-toggle-label">
											<span><?php esc_html_e( 'Required Field', 'revora' ); ?></span>
											<label class="revora-toggle">
												<input type="checkbox" id="setting-required">
												<span class="revora-toggle-slider"></span>
											</label>
										</label>
									</div>
									<!-- Submit Button specific settings -->
									<div id="s-submit-wrap" style="display:none;">
										<div class="revora-field-group">
											<label class="revora-field-label"><?php esc_html_e( 'Button Text', 'revora' ); ?></label>
											<input type="text" id="setting-submit-label" class="widefat" placeholder="Submit Review">
										</div>
									</div>
								</div>
							</div>

							<!-- Form Settings Tab -->
							<div id="tab-form-settings" class="revora-tab-pane">
								<div class="revora-field-group">
									<label class="revora-field-label"><?php esc_html_e( 'Success Message', 'revora' ); ?></label>
									<textarea name="settings[success_message]" class="widefat" rows="3" placeholder="<?php esc_attr_e( 'Thank you for your review!', 'revora' ); ?>"><?php echo esc_textarea( $settings['success_message'] ?? '' ); ?></textarea>
								</div>
								
								<div class="revora-field-group" style="margin-top: 25px;">
									<label class="revora-toggle-label">
										<span><?php esc_html_e( 'Enable Share Card', 'revora' ); ?></span>
										<label class="revora-toggle">
											<input type="checkbox" name="settings[enable_share_card]" value="1" <?php checked( isset( $settings['enable_share_card'] ) ? $settings['enable_share_card'] : '', '1' ); ?>>
											<span class="revora-toggle-slider"></span>
										</label>
									</label>
									<p class="description" style="margin-top: 8px;"><?php esc_html_e( 'If enabled, users can download a customized Share Card featuring their name and rating upon successful submission.', 'revora' ); ?></p>
								</div>
							</div>

						</div><!-- /.revora-sidebar-content -->

					</div><!-- /.revora-builder-sidebar -->
				</div><!-- /.revora-fluent-layout -->
			</form>
		</div>

		<style>
			/* Premium Form Builder Styles */
			.revora-fluent-layout {
				display: flex;
				gap: 24px;
				background: #f1f5f9;
				margin-top: 15px;
				align-items: flex-start;
			}
			.revora-builder-canvas-wrap {
				flex: 1;
			}
			.revora-form-sidebar {
				width: 300px;
				flex-shrink: 0;
			}
			.revora-field-group {
				margin-bottom: 18px;
			}
			.revora-field-group .revora-field-label {
				display: block;
				font-weight: 600;
				font-size: 12px;
				color: #475569;
				margin-bottom: 6px;
				text-transform: uppercase;
				letter-spacing: 0.4px;
			}
			.revora-field-group input[type="text"],
			.revora-field-group textarea.widefat {
				width: 100%;
				box-sizing: border-box;
				border: 1px solid #e2e8f0;
				border-radius: 6px;
				padding: 10px 12px;
				font-size: 14px;
				color: #1e293b;
				background: #f8fafc;
				transition: all 0.2s;
				box-shadow: 0 1px 2px 0 rgba(0,0,0,0.02);
			}
			.revora-field-group input[type="text"]:focus,
			.revora-field-group textarea.widefat:focus {
				border-color: #3b82f6;
				background: #fff;
				outline: none;
				box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
			}
			.revora-field-group .description {
				color: #94a3b8;
				font-size: 11px;
				margin-top: 5px;
				display: block;
			}
			.revora-empty-state {
				text-align: center;
				color: #94a3b8;
				padding: 40px 20px;
			}
			.revora-empty-state p {
				font-size: 13px;
				line-height: 1.6;
			}
			.revora-canvas-footer {
				padding-top: 20px;
			}
			
			/* Toggle Switch */
			.revora-toggle-label {
				display: flex;
				justify-content: space-between;
				align-items: center;
				cursor: pointer;
				font-weight: 500;
				color: #334155;
				font-size: 13px;
			}
			.revora-toggle {
				position: relative;
				display: inline-block;
				width: 36px;
				height: 20px;
				flex-shrink: 0;
			}
			.revora-toggle input {
				opacity: 0;
				width: 0;
				height: 0;
			}
			.revora-toggle-slider {
				position: absolute;
				cursor: pointer;
				top: 0; left: 0; right: 0; bottom: 0;
				background: #cbd5e1;
				border-radius: 20px;
				transition: 0.2s;
			}
			.revora-toggle-slider:before {
				position: absolute;
				content: "";
				height: 14px;
				width: 14px;
				left: 3px;
				bottom: 3px;
				background: #fff;
				border-radius: 50%;
				transition: 0.2s;
				box-shadow: 0 1px 2px rgba(0,0,0,0.2);
			}
			.revora-toggle input:checked + .revora-toggle-slider {
				background: #2563eb;
			}
			.revora-toggle input:checked + .revora-toggle-slider:before {
				transform: translateX(16px);
			}
			
			/* Alignment Picker */
			.revora-btn-align-picker {
				display: flex;
				gap: 6px;
				margin-top: 6px;
			}
			.revora-align-btn {
				background: #f1f5f9;
				border: 1px solid #e2e8f0;
				border-radius: 6px;
				width: 36px;
				height: 36px;
				display: flex;
				align-items: center;
				justify-content: center;
				cursor: pointer;
				transition: all 0.15s;
				color: #64748b;
			}
			.revora-align-btn:hover {
				border-color: #94a3b8;
				color: #334155;
			}
			.revora-align-btn.active {
				background: #2563eb;
				border-color: #2563eb;
				color: #fff;
			}
			.revora-align-btn .dashicons {
				font-size: 18px;
				width: 18px;
				height: 18px;
			}
			
			/* Sidebar select input */
			.revora-sidebar-content select.widefat {
				width: 100%;
				border: 1px solid #e2e8f0;
				border-radius: 6px;
				padding: 9px 12px;
				font-size: 13px;
				color: #1e293b;
				background: #f8fafc;
				cursor: pointer;
			}
			.revora-sidebar-content select.widefat:focus {
				border-color: #3b82f6;
				outline: none;
				box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
			}
			
			/* Builder Topbar */
			.revora-builder-topbar {
				display: flex;
				align-items: center;
				justify-content: space-between;
				background: #fff;
				border: 1px solid #e2e8f0;
				border-radius: 8px;
				padding: 12px 20px;
				margin-bottom: 16px;
				box-shadow: 0 1px 3px rgba(0,0,0,0.04);
			}
			.revora-topbar-left {
				display: flex;
				align-items: center;
				gap: 12px;
				flex: 1;
			}
			.revora-topbar-label {
				font-size: 13px;
				font-weight: 600;
				color: #64748b;
				white-space: nowrap;
			}
			.revora-topbar-name-input {
				flex: 1;
				border: 1px solid #e2e8f0 !important;
				border-radius: 6px !important;
				padding: 8px 12px !important;
				font-size: 15px !important;
				font-weight: 600 !important;
				color: #0f172a !important;
				background: #f8fafc !important;
				box-shadow: none !important;
				transition: all 0.2s !important;
				max-width: 400px;
			}
			.revora-topbar-name-input:focus {
				border-color: #3b82f6 !important;
				background: #fff !important;
				outline: none !important;
				box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important;
			}
			.revora-topbar-right {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-left: 20px;
			}
			.revora-topbar-save-btn {
				background: #2563eb;
				color: #fff;
				border: none;
				padding: 9px 18px;
				border-radius: 6px;
				font-size: 13px;
				font-weight: 600;
				cursor: pointer;
				display: flex;
				align-items: center;
				gap: 6px;
				transition: all 0.2s;
				box-shadow: 0 2px 4px rgba(37,99,235,0.25);
				white-space: nowrap;
			}
			.revora-topbar-save-btn:hover {
				background: #1d4ed8;
				box-shadow: 0 4px 8px rgba(37,99,235,0.3);
			}
			.revora-topbar-save-btn .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
			}

			/* Canvas Area */
			.revora-builder-canvas {
				background: #f8fafc;
				border-radius: 8px;
				padding: 30px;
				min-height: 500px;
				border: 1px solid #e2e8f0;
				box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.01);
			}
			
			.revora-form-title-input {
				font-size: 24px !important;
				font-weight: 700 !important;
				border: none !important;
				border-bottom: 2px solid transparent !important;
				background: transparent !important;
				box-shadow: none !important;
				width: 100%;
				padding: 0 0 10px 0 !important;
				margin-bottom: 20px;
				transition: all 0.2s;
				color: #0f172a;
				font-family: inherit;
			}
			
			.revora-form-title-input:focus {
				border-bottom: 2px solid #3b82f6 !important;
				outline: none !important;
			}
			
			.revora-sortable-area {
				min-height: 100px;
				padding-bottom: 20px;
			}
			
			.revora-canvas-add-area {
				text-align: center;
				margin-bottom: 20px;
			}
			.revora-canvas-add-btn {
				background: transparent;
				border: 1px dashed #cbd5e1;
				color: #3b82f6;
				padding: 12px 24px;
				border-radius: 6px;
				cursor: pointer;
				font-weight: 600;
				display: inline-flex;
				align-items: center;
				gap: 6px;
				transition: all 0.2s;
			}
			.revora-canvas-add-btn:hover {
				background: #eff6ff;
				border-color: #3b82f6;
			}
			
			/* Field Blocks */
			.revora-field-block {
				background: #fff;
				border: 1px dashed #cbd5e1;
				border-radius: 8px;
				padding: 16px 20px;
				margin-bottom: 16px;
				position: relative;
				cursor: move;
				transition: all 0.2s ease;
				box-shadow: 0 1px 2px 0 rgba(0,0,0,0.02);
				box-sizing: border-box;
			}
			
			.revora-field-block:hover {
				border: 1px solid #3b82f6;
				box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
			}
			
			.revora-field-block.active-field {
				border: 1px solid #3b82f6;
				background: #fff;
				box-shadow: 0 0 0 1px #3b82f6;
			}
			
			/* Columns System */
			.revora-field-block.revora-row-block {
				background: #f8fafc;
				border: 1px solid #cbd5e1;
			}
			.revora-row-cols {
				display: flex;
				gap: 16px;
				margin-top: 12px;
			}
			.revora-col {
				flex: 1;
				background: #fff;
				border: 1px dashed #cbd5e1;
				border-radius: 6px;
				min-height: 80px;
				padding: 12px;
				transition: background 0.2s;
			}
			.revora-col.ui-sortable-hover {
				background: #eff6ff;
				border-color: #3b82f6;
			}
			
			.revora-field-block-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 12px;
			}
			
			.revora-field-label-preview {
				font-weight: 600;
				color: #334155;
				font-size: 14px;
				line-height: 20px;
			}
			
			.revora-field-label-preview .req-star {
				color: #ef4444;
			}
			
			.revora-field-actions {
				opacity: 0;
				visibility: hidden;
				transition: opacity 0.2s;
				height: 20px;
				display: flex;
				align-items: center;
			}
			
			.revora-field-block:hover .revora-field-actions,
			.revora-field-block.active-field .revora-field-actions {
				opacity: 1;
				visibility: visible;
			}
			
			.revora-field-actions .dashicons {
				cursor: pointer;
				color: #ef4444;
				width: 20px;
				height: 20px;
				font-size: 20px;
				transition: color 0.2s;
			}
			
			.revora-field-actions .dashicons:hover {
				color: #dc2626;
			}
			
			/* Fake Inputs for preview */
			.revora-fake-input {
				background: #f1f5f9;
				border: 1px solid #cbd5e1;
				border-radius: 6px;
				padding: 0 14px;
				color: #64748b;
				pointer-events: none;
				width: 100%;
				box-sizing: border-box;
				height: 42px;
				line-height: 42px;
				display: flex;
				align-items: center;
			}
			
			.revora-fake-textarea {
				height: auto;
				min-height: 80px;
				line-height: 1.5;
				padding: 10px 14px;
				display: block;
			}
			
			.revora-fake-submit-btn {
				background: #2563eb;
				color: #fff;
				padding: 12px 24px;
				border-radius: 6px;
				display: block;
				width: 100%;
				text-align: center;
				font-weight: 600;
				box-sizing: border-box;
				box-shadow: 0 4px 6px -1px rgba(37,99,235,0.2);
			}
			
			/* Sidebar */
			.revora-builder-sidebar {
				background: #fff;
				border-radius: 8px;
				border: 1px solid #e2e8f0;
				box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
				display: flex;
				flex-direction: column;
				height: calc(100vh - 100px);
				position: sticky;
				top: 40px;
				overflow: hidden;
				min-width: 300px;
			}
			
			.revora-sidebar-tabs {
				display: flex;
				border-bottom: 1px solid #e2e8f0;
				background: #f8fafc;
			}
			
			.revora-sidebar-tab {
				flex: 1;
				text-align: center;
				padding: 16px 8px;
				font-weight: 600;
				color: #64748b;
				cursor: pointer;
				border-bottom: 2px solid transparent;
				font-size: 13px;
				transition: all 0.2s;
			}
			
			.revora-sidebar-tab:hover {
				color: #334155;
			}
			
			.revora-sidebar-tab.active {
				color: #2563eb;
				border-bottom: 2px solid #2563eb;
				background: #fff;
			}
			
			.revora-sidebar-content {
				padding: 24px;
				flex: 1;
				overflow-y: auto;
			}
			
			.revora-tab-pane {
				display: none;
			}
			
			.revora-tab-pane.active {
				display: block;
			}
			
			.revora-sidebar-heading {
				margin: 0 0 16px 0;
				font-size: 14px;
				font-weight: 600;
				color: #334155;
				text-transform: uppercase;
				letter-spacing: 0.5px;
			}
			
			/* Field Buttons */
			.revora-field-buttons-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 12px;
			}
			
			.revora-add-field-btn {
				background: #fff;
				border: 1px solid #e2e8f0;
				border-radius: 8px;
				padding: 16px 10px;
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				gap: 8px;
				cursor: pointer;
				color: #475569;
				transition: all 0.2s ease;
				font-size: 12px;
				font-weight: 500;
				box-shadow: 0 1px 2px rgba(0,0,0,0.02);
			}
			
			.revora-add-field-btn:hover {
				border-color: #3b82f6;
				color: #2563eb;
				box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
				transform: translateY(-1px);
			}
			
			.revora-add-field-btn .dashicons {
				color: #94a3b8;
				font-size: 24px;
				width: 24px;
				height: 24px;
				transition: color 0.2s;
			}
			
			.revora-add-field-btn:hover .dashicons {
				color: #3b82f6;
			}
			
			.revora-empty-state {
				text-align: center;
				color: #8c8f94;
				padding: 40px 20px;
				font-style: italic;
			}
			
			.revora-sidebar-actions {
				padding: 20px;
				border-top: 1px solid #e2e8f0;
				background: #f8fafc;
				border-radius: 0 0 8px 8px;
			}
			
			#revora-save-form-btn {
				width: 100%;
				background: #2563eb;
				border: none;
				color: #fff;
				padding: 12px 24px;
				border-radius: 6px;
				font-size: 14px;
				font-weight: 600;
				cursor: pointer;
				transition: all 0.2s;
				box-shadow: 0 4px 6px -1px rgba(37,99,235,0.2);
			}
			
			#revora-save-form-btn:hover {
				background: #1d4ed8;
				box-shadow: 0 6px 8px -1px rgba(37,99,235,0.3);
			}
			
			.ui-sortable-placeholder {
				border: 2px dashed #3b82f6;
				background: #eff6ff;
				border-radius: 8px;
				visibility: visible !important;
				height: 70px !important;
				margin-bottom: 16px;
			}


		</style>
		
		<script>
			var revoraExistingFields = <?php echo wp_json_encode( $fields ); ?>;
			var revoraActiveFieldId = null;
			var revoraFieldsData = {};
			var revoraFieldCounter = 0;

			jQuery(document).ready(function($) {
				
				// 1. Initialize Sortable
				function initSortable() {
					$('.revora-sortable-area').sortable({
						connectWith: '.revora-sortable-area',
						placeholder: 'ui-sortable-placeholder',
						cursor: 'move',
						opacity: 0.9,
						tolerance: 'pointer',
						appendTo: 'body',
						helper: 'clone',
						start: function(e, ui) {
							ui.placeholder.height(ui.item.outerHeight());
						},
						stop: function(event, ui) {
							// Prevent rows from being dragged into columns
							if ( ui.item.hasClass('revora-row-block') && ui.item.parent().hasClass('revora-col') ) {
								$(this).sortable('cancel');
								alert('Rows cannot be nested inside rows.');
							}
						}
					});
				}
				initSortable();

				// 2. Tab Switching
				$('.revora-sidebar-tab').on('click', function() {
					$('.revora-sidebar-tab').removeClass('active');
					$(this).addClass('active');
					
					var target = $(this).data('target');
					$('.revora-tab-pane').removeClass('active');
					$('#' + target).addClass('active');
				});

				// 3. Render Field Block
				function renderFieldBlock(id, data) {
					if ( data.type === 'row' ) {
						return `
							<div class="revora-field-block revora-row-block" id="field-${id}" data-id="${id}" data-type="row">
								<div class="revora-field-block-header">
									<div class="revora-field-label-preview">2 Column Row</div>
									<div class="revora-field-actions">
										<span class="dashicons dashicons-trash delete-field"></span>
									</div>
								</div>
								<div class="revora-row-cols">
									<div class="revora-col revora-sortable-area"></div>
									<div class="revora-col revora-sortable-area"></div>
								</div>
							</div>
						`;
					}

					var reqStar = data.required ? '<span class="req-star">*</span>' : '';
					var previewHtml = '';
					
					if ( data.type === 'textarea' ) {
						previewHtml = '<div class="revora-fake-input revora-fake-textarea">' + (data.placeholder || '') + '</div>';
					} else if ( data.type === 'rating' ) {
						previewHtml = '<div class="revora-fake-input" style="border:none; padding:0; display:flex; gap:5px;"><span class="dashicons dashicons-star-filled" style="color:#ffb400;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb400;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb400;"></span><span class="dashicons dashicons-star-empty" style="color:#ccc;"></span><span class="dashicons dashicons-star-empty" style="color:#ccc;"></span></div>';
					} else if ( data.type === 'avatar' ) {
						previewHtml = '<div class="revora-fake-input" style="background:#f9f9f9; text-align:center; display:flex; align-items:center; justify-content:center; gap:8px;"><span class="dashicons dashicons-admin-users" style="color:#64748b;"></span> <span>' + (data.placeholder || 'Choose Profile Image...') + '</span></div>';
					} else if ( data.type === 'file' ) {
						previewHtml = '<div class="revora-fake-input" style="background:#f9f9f9; text-align:center;">' + (data.placeholder || 'Choose File...') + '</div>';
					} else if ( data.type === 'select' ) {
						previewHtml = '<div class="revora-fake-input" style="display:flex; justify-content:space-between;"><span>Select an option...</span><span class="dashicons dashicons-arrow-down-alt2"></span></div>';
					} else if ( data.type === 'radio' ) {
						previewHtml = '<div style="display:flex; flex-direction:column; gap:5px;"><label><input type="radio" disabled> Option 1</label><label><input type="radio" disabled> Option 2</label></div>';
					} else if ( data.type === 'checkbox' ) {
						previewHtml = '<div style="display:flex; flex-direction:column; gap:5px;"><label><input type="checkbox" disabled> Option 1</label><label><input type="checkbox" disabled> Option 2</label></div>';
					} else if ( data.type === 'submit' ) {
						previewHtml = '<div style="margin-top:5px;"><div class="revora-fake-submit-btn">' + (data.submitLabel || data.label || 'Submit Review') + '</div></div>';
					} else {
						previewHtml = '<div class="revora-fake-input">' + (data.placeholder || '') + '</div>';
					}

					var labelText = data.label || (data.type === 'submit' ? (data.submitLabel || 'Submit Button') : (data.type === 'row' ? '2 Column Row' : 'Field'));

					return `
						<div class="revora-field-block" id="field-${id}" data-id="${id}" data-type="${data.type}">
							<div class="revora-field-block-header">
								<div class="revora-field-label-preview"><span class="lbl-text">${labelText}</span> ${reqStar}</div>
								<div class="revora-field-actions">
									<span class="dashicons dashicons-trash delete-field"></span>
								</div>
							</div>
							<div class="revora-field-preview">
								${previewHtml}
							</div>
						</div>
					`;
				}

				// 4. Add New Field to Canvas or specific target
				function addField(data, target, noSelect) {
					var id = 'f_' + (++revoraFieldCounter);
					revoraFieldsData[id] = data;
					
					var html = renderFieldBlock(id, data);
					var $el = $(html);
					
					if ( target ) {
						$(target).append($el);
					} else {
						$('#revora-sortable-canvas').append($el);
					}
					
					initSortable();
					
					if ( data.type !== 'row' && ! noSelect ) {
						selectField(id);
					}
					
					return $el;
				}

				// 5. Select Field to Edit
				function selectField(id) {
					var data = revoraFieldsData[id];
					if ( data.type === 'row' ) return; // Rows don't have settings
					
					revoraActiveFieldId = id;
					
					// Highlight canvas block
					$('.revora-field-block').removeClass('active-field');
					$('#field-' + id).addClass('active-field');
					
					// Switch to Settings Tab
					$('#tab-nav-field-settings').click();
					
					// Show settings panel
					$('#no-field-selected').hide();
					$('#active-field-settings').show();
					
					// Types that have no placeholder
					var noPlaceholder = ['rating', 'file', 'avatar', 'radio', 'checkbox', 'select', 'submit'];
					// Types that have options
					var hasOptions = ['select', 'radio', 'checkbox'];
					// Types that have allowed file types
					var hasAllowedTypes = ['file', 'avatar'];
					// Types that have label+key+required
					var isSubmit = data.type === 'submit';
					
					// Show/hide sections
					$('#s-label-wrap').toggle( !isSubmit );
					$('#s-key-wrap').toggle( !isSubmit );
					$('#s-placeholder-wrap').toggle( !isSubmit && noPlaceholder.indexOf(data.type) === -1 );
					$('#s-allowed-types-wrap').toggle( hasAllowedTypes.indexOf(data.type) !== -1 );
					$('#s-options-wrap').toggle( hasOptions.indexOf(data.type) !== -1 );
					$('#s-required-wrap').toggle( !isSubmit );
					$('#s-submit-wrap').toggle( isSubmit );
					
					if ( isSubmit ) {
						// Populate submit settings
						$('#setting-submit-label').val(data.submitLabel || data.label || 'Submit Review');
					} else {
						// Populate regular field settings
						$('#setting-label').val(data.label);
						$('#setting-key').val(data.key);
						$('#setting-placeholder').val(data.placeholder || '');
						$('#setting-required').prop('checked', data.required ? true : false);
						
						if ( hasAllowedTypes.indexOf(data.type) !== -1 ) {
							$('#setting-allowed-types').val(data.allowed_types || (data.type === 'avatar' ? 'jpg, jpeg, png, webp' : 'jpg, jpeg, png, webp, pdf, zip'));
						}

						if ( hasOptions.indexOf(data.type) !== -1 ) {
							$('#s-options-wrap').show();
							$('#setting-options').val(data.options || "Option 1\nOption 2\nOption 3");
						}
					}
				}

				// 6. Bind Sidebar Buttons
				$('.revora-add-field-btn').on('click', function() {
					var type = $(this).data('type');
					var label = $(this).data('label');
					var key = label.toLowerCase().replace(/[^a-z0-9]/g, '_');
					
					addField({
						type: type,
						label: label,
						key: key,
						placeholder: '',
						required: false
					});
				});
				
				$('.revora-canvas-add-btn').on('click', function() {
					$('.revora-sidebar-tab[data-target="tab-add-fields"]').click();
					revoraActiveFieldId = null;
					$('.revora-field-block').removeClass('active-field');
					$('#no-field-selected').show();
					$('#active-field-settings').hide();
					
					// Flash effect to draw attention
					$('.revora-sidebar-tab[data-target="tab-add-fields"]').css('background', '#e0f0ff');
					setTimeout(function() {
						$('.revora-sidebar-tab[data-target="tab-add-fields"]').css('background', '#fff');
					}, 500);
				});

				// 7. Click on canvas field
				$(document).on('click', '.revora-field-block', function(e) {
					if ( $(e.target).closest('.delete-field').length ) return; // Skip if deleting
					var id = $(this).data('id');
					selectField(id);
					e.stopPropagation(); // Prevent row click when clicking inner field
				});

				// 8. Delete Field
				$(document).on('click', '.delete-field', function(e) {
					e.stopPropagation();
					var $block = $(this).closest('.revora-field-block');
					var id = $block.data('id');
					
					// If it's a row, clean up child data
					if ( $block.data('type') === 'row' ) {
						$block.find('.revora-field-block').each(function() {
							var childId = $(this).data('id');
							delete revoraFieldsData[childId];
						});
					}
					
					delete revoraFieldsData[id];
					$block.remove();
					
					if ( revoraActiveFieldId === id ) {
						revoraActiveFieldId = null;
						$('#no-field-selected').show();
						$('#active-field-settings').hide();
					}
				});

				// 9. Live Update Settings -> Canvas
				$('#setting-label, #setting-key, #setting-placeholder, #setting-required, #setting-options, #setting-allowed-types').on('input change', function() {
					if ( ! revoraActiveFieldId ) return;
					var data = revoraFieldsData[revoraActiveFieldId];
					if ( data.type === 'submit' ) return;
					
					data.label = $('#setting-label').val();
					data.key = $('#setting-key').val();
					data.placeholder = $('#setting-placeholder').val();
					data.required = $('#setting-required').is(':checked');
					if ( data.type === 'select' || data.type === 'radio' || data.type === 'checkbox' ) {
						data.options = $('#setting-options').val();
					}
					if ( data.type === 'file' || data.type === 'avatar' ) {
						data.allowed_types = $('#setting-allowed-types').val();
					}
					
					var $block = $('#field-' + revoraActiveFieldId);
					var reqStar = data.required ? '<span class="req-star">*</span>' : '';
					$block.find('.revora-field-label-preview').html('<span class="lbl-text">' + data.label + '</span> ' + reqStar);
					
					if ( data.type === 'textarea' || data.type === 'text' || data.type === 'email' || data.type === 'number' || data.type === 'tel' || data.type === 'url' || data.type === 'date' ) {
						$block.find('.revora-fake-input').text(data.placeholder);
					}
				});
				
				// Submit live update
				$('#setting-submit-label').on('input change', function() {
					if ( !revoraActiveFieldId ) return;
					var data = revoraFieldsData[revoraActiveFieldId];
					if ( data.type !== 'submit' ) return;
					data.submitLabel = $('#setting-submit-label').val();
					var $block = $('#field-' + revoraActiveFieldId);
					$block.find('.revora-fake-submit-btn').text(data.submitLabel || 'Submit Review');
					$block.find('.lbl-text').text(data.submitLabel || 'Submit Button');
				});

				// 10. Recursively load fields
				function renderFieldsRecursive(fieldsToRender, target) {
					$.each(fieldsToRender, function(i, field) {
						var $el = addField(field, target, true);
						if ( field.type === 'row' && field.columns ) {
							var cols = $el.find('.revora-col');
							$.each(field.columns, function(colIndex, colFields) {
								if ( cols[colIndex] && colFields.length > 0 ) {
									renderFieldsRecursive(colFields, cols[colIndex]);
								}
							});
						}
					});
				}

				if ( revoraExistingFields && revoraExistingFields.length > 0 ) {
					renderFieldsRecursive(revoraExistingFields, null);
					revoraActiveFieldId = null;
					$('.revora-field-block').removeClass('active-field');
					$('.revora-sidebar-tab[data-target="tab-add-fields"]').click();
					$('#no-field-selected').show();
					$('#active-field-settings').hide();
				} else {
					addField({type: 'text', label: 'Name', key: 'name', required: true});
					addField({type: 'email', label: 'Email', key: 'email', required: true});
					addField({type: 'rating', label: 'Rating', key: 'rating', required: true});
					addField({type: 'text', label: 'Review Title', key: 'title', required: true});
					addField({type: 'textarea', label: 'Review Content', key: 'content', required: true});
					
					revoraActiveFieldId = null;
					$('.revora-field-block').removeClass('active-field');
					$('.revora-sidebar-tab[data-target="tab-add-fields"]').click();
					$('#no-field-selected').show();
					$('#active-field-settings').hide();
				}

				// 11. Build Nested JSON for Save
				$('#revora-save-form-btn').on('click', function() {
					var $container = $('#revora-hidden-payloads');
					$container.empty();
					
					function buildFieldsArray($parent) {
						var fields = [];
						$parent.children('.revora-field-block').each(function() {
							var id = $(this).data('id');
							var data = revoraFieldsData[id];
							
							var fieldObj = {
								type: data.type
							};
							
							if ( data.type === 'row' ) {
								fieldObj.columns = [];
								$(this).find('.revora-col').each(function() {
									fieldObj.columns.push( buildFieldsArray($(this)) );
								});
							} else {
								fieldObj.label = data.label;
								fieldObj.key = data.key;
								fieldObj.placeholder = data.placeholder;
								fieldObj.required = data.required;
								if ( data.type === 'select' || data.type === 'radio' || data.type === 'checkbox' ) {
									fieldObj.options = data.options || "Option 1\nOption 2\nOption 3";
								}
								if ( data.type === 'file' || data.type === 'avatar' ) {
									fieldObj.allowed_types = data.allowed_types || (data.type === 'avatar' ? 'jpg, jpeg, png, webp' : '');
								}
							}
							
							fields.push(fieldObj);
						});
						return fields;
					}
					
					var finalData = buildFieldsArray($('#revora-sortable-canvas'));
					
					// Insert as a single JSON string hidden input
					$('<input>').attr({
						type: 'hidden',
						name: 'fields_json',
						value: JSON.stringify(finalData)
					}).appendTo($container);
					
					$('#revora-builder-form').submit();
				});

			});
		</script>
		<?php
	}
}

class Revora_Form_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => 'form',
			'plural'   => 'forms',
			'ajax'     => false,
		) );
	}

	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'name'       => __( 'Form Name', 'revora' ),
			'shortcode'  => __( 'Shortcode', 'revora' ),
			'created_at' => __( 'Date', 'revora' ),
		);
	}

	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="form[]" value="%s" />', $item->id );
	}

	public function column_name( $item ) {
		$actions = array(
			'edit'   => sprintf( '<a href="?page=%s&action=%s&form_id=%s">%s</a>', 'revora-forms', 'edit', $item->id, __( 'Edit', 'revora' ) ),
			'delete' => sprintf( '<a href="%s" onclick="return confirm(\'Are you sure?\')">%s</a>', wp_nonce_url( '?page=revora-forms&action=delete&form_id=' . $item->id, 'revora_delete_form_' . $item->id ), __( 'Delete', 'revora' ) ),
		);
		return sprintf( '<strong>%s</strong>%s', esc_html( $item->name ), $this->row_actions( $actions ) );
	}

	public function column_shortcode( $item ) {
		return '<code>[revora_form id="' . esc_html( $item->id ) . '"]</code>';
	}

	public function column_created_at( $item ) {
		return date_i18n( get_option( 'date_format' ), strtotime( $item->created_at ) );
	}

	public function prepare_items() {
		$db = new Revora_DB();
		$this->items = $db->get_forms();
		$this->_column_headers = array( $this->get_columns(), array(), array() );
	}
}
