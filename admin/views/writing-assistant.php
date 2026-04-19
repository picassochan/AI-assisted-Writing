<?php
/**
 * AI Writing Assistant page.
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;

$debug_mode = ! empty( $settings['debug_mode'] );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'AI Writing Assistant', 'ai-assisted-writing' ); ?></h1>

	<?php if ( ! $has_api_key ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'Please configure your API settings first.', 'ai-assisted-writing' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-assisted-writing&tab=api' ) ); ?>">
					<?php esc_html_e( 'Go to Settings', 'ai-assisted-writing' ); ?>
				</a>
			</p>
		</div>
	<?php else : ?>

	<div class="aiaw-writing-layout">
		<!-- Left Panel: Controls -->
		<div class="aiaw-control-panel">
			<div class="aiaw-card">
				<h3><?php esc_html_e( 'Generate Article', 'ai-assisted-writing' ); ?></h3>

				<p>
					<label for="aiaw-select-category"><?php esc_html_e( 'Category', 'ai-assisted-writing' ); ?></label><br />
					<select id="aiaw-select-category" class="regular-text">
						<option value=""><?php esc_html_e( '-- Select Category --', 'ai-assisted-writing' ); ?></option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->term_id ); ?>">
								<?php echo esc_html( $cat->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<label for="aiaw-select-template"><?php esc_html_e( 'AI Template', 'ai-assisted-writing' ); ?></label><br />
					<select id="aiaw-select-template" class="regular-text" disabled>
						<option value=""><?php esc_html_e( '-- Select Template --', 'ai-assisted-writing' ); ?></option>
					</select>
				</p>

				<p>
					<label for="aiaw-input-title"><?php esc_html_e( 'Title', 'ai-assisted-writing' ); ?></label><br />
					<input type="text" id="aiaw-input-title" class="large-text"
						placeholder="<?php esc_attr_e( 'Enter article title', 'ai-assisted-writing' ); ?>" />
				</p>

				<p>
					<label for="aiaw-input-description"><?php esc_html_e( 'Description / Outline', 'ai-assisted-writing' ); ?></label><br />
					<textarea id="aiaw-input-description" class="large-text" rows="4"
						placeholder="<?php esc_attr_e( 'Briefly describe what the article should cover...', 'ai-assisted-writing' ); ?>"></textarea>
				</p>

				<p>
					<button type="button" id="aiaw-generate-btn" class="button button-primary button-large">
						<?php esc_html_e( 'Generate', 'ai-assisted-writing' ); ?>
					</button>
					<span id="aiaw-generate-status"></span>
				</p>
			</div>
		</div>

		<!-- Right Panel: Editor -->
		<div class="aiaw-editor-panel">
			<div class="aiaw-card">
				<h3><?php esc_html_e( 'Article Editor', 'ai-assisted-writing' ); ?></h3>

				<p>
					<label for="aiaw-article-title"><?php esc_html_e( 'Title', 'ai-assisted-writing' ); ?></label><br />
					<input type="text" id="aiaw-article-title" class="large-text" />
				</p>

				<p>
					<label for="aiaw-article-content"><?php esc_html_e( 'Content', 'ai-assisted-writing' ); ?></label><br />
					<textarea id="aiaw-article-content" class="large-text" rows="20"></textarea>
				</p>

				<p>
					<label for="aiaw-article-tags"><?php esc_html_e( 'Tags', 'ai-assisted-writing' ); ?></label><br />
					<input type="text" id="aiaw-article-tags" class="large-text"
						placeholder="<?php esc_attr_e( 'Comma-separated tags', 'ai-assisted-writing' ); ?>" />
				</p>

				<p>
					<input type="hidden" id="aiaw-article-cat-id" value="" />
					<button type="button" id="aiaw-save-draft-btn" class="button">
						<?php esc_html_e( 'Save as Draft', 'ai-assisted-writing' ); ?>
					</button>
					<button type="button" id="aiaw-publish-btn" class="button button-primary">
						<?php esc_html_e( 'Publish', 'ai-assisted-writing' ); ?>
					</button>
					<span id="aiaw-save-status"></span>
				</p>
			</div>

			<!-- SEO Settings -->
			<div class="aiaw-card aiaw-seo-section">
				<h3><?php esc_html_e( 'SEO Settings', 'ai-assisted-writing' ); ?>
					<button type="button" id="aiaw-generate-seo-btn" class="button button-small" style="margin-left:10px;">
						<?php esc_html_e( 'Generate SEO', 'ai-assisted-writing' ); ?>
					</button>
					<span id="aiaw-seo-status"></span>
				</h3>

				<p>
					<label for="aiaw-seo-title"><?php esc_html_e( 'SEO Title', 'ai-assisted-writing' ); ?></label><br />
					<input type="text" id="aiaw-seo-title" class="large-text" />
					<span class="aiaw-seo-hint"><span id="aiaw-seo-title-count">0</span>/60</span>
				</p>

				<p>
					<label for="aiaw-seo-description"><?php esc_html_e( 'Meta Description', 'ai-assisted-writing' ); ?></label><br />
					<textarea id="aiaw-seo-description" class="large-text" rows="3"></textarea>
					<span class="aiaw-seo-hint"><span id="aiaw-seo-desc-count">0</span>/155</span>
				</p>

				<p>
					<label for="aiaw-seo-keywords"><?php esc_html_e( 'Focus Keywords', 'ai-assisted-writing' ); ?></label><br />
					<input type="text" id="aiaw-seo-keywords" class="large-text"
						placeholder="<?php esc_attr_e( 'Comma-separated keywords', 'ai-assisted-writing' ); ?>" />
				</p>

				<p>
					<label for="aiaw-seo-og-title"><?php esc_html_e( 'OG Title', 'ai-assisted-writing' ); ?></label><br />
					<input type="text" id="aiaw-seo-og-title" class="large-text" />
					<span class="aiaw-seo-hint"><span id="aiaw-seo-og-title-count">0</span>/95</span>
				</p>

				<p>
					<label for="aiaw-seo-og-description"><?php esc_html_e( 'OG Description', 'ai-assisted-writing' ); ?></label><br />
					<textarea id="aiaw-seo-og-description" class="large-text" rows="2"></textarea>
					<span class="aiaw-seo-hint"><span id="aiaw-seo-og-desc-count">0</span>/200</span>
				</p>

				<p>
					<label for="aiaw-seo-slug"><?php esc_html_e( 'SEO Slug', 'ai-assisted-writing' ); ?></label><br />
					<input type="text" id="aiaw-seo-slug" class="large-text" />
				</p>
			</div>
		</div>
	</div>

	<?php endif; ?>

	<?php if ( $debug_mode ) : ?>
	<!-- Debug Panel -->
	<div class="aiaw-debug-panel" style="margin-top:20px;">
		<div class="aiaw-card" style="border-color:#dba617;">
			<h3 style="color:#dba617;">Debug Panel</h3>
			<p>
				<strong>PHP screen_id:</strong> <code><?php echo esc_html( get_current_screen()->id ); ?></code>
				&nbsp; <strong>hook_suffix:</strong> <code><?php echo esc_html( $GLOBALS['hook_suffix'] ?? 'N/A' ); ?></code>
				&nbsp; <strong>_GET[page]:</strong> <code><?php echo esc_html( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'N/A' ); ?></code>
			</p>
			<p><strong>JS loaded?</strong> <code id="aiaw-debug-js-status" style="color:red;">NO</code></p>
			<p><strong>Templates from DB:</strong> <code id="aiaw-debug-templates" style="display:block;white-space:pre-wrap;max-height:200px;overflow:auto;background:#f0f0f1;padding:8px;font-size:12px;"><?php echo esc_html( wp_json_encode( $templates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></code></p>
			<p><strong>WP Categories:</strong> <code id="aiaw-debug-categories" style="display:block;white-space:pre-wrap;max-height:150px;overflow:auto;background:#f0f0f1;padding:8px;font-size:12px;"><?php
				$cat_debug = array();
				foreach ( $categories as $c ) {
					$cat_debug[] = array( 'term_id' => $c->term_id, 'name' => $c->name );
				}
				echo esc_html( wp_json_encode( $cat_debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
			?></code></p>
			<p><strong>JS aiaw object:</strong> <code id="aiaw-debug-js-templates" style="display:block;white-space:pre-wrap;max-height:200px;overflow:auto;background:#e0f0e0;padding:8px;font-size:12px;">(waiting for JS...)</code></p>
			<p><strong>Log:</strong></p>
			<pre id="aiaw-debug-log" style="background:#1d2327;color:#50c878;padding:12px;font-size:12px;max-height:300px;overflow:auto;white-space:pre-wrap;"></pre>
		</div>
	</div>
	<?php endif; ?>
</div>
