<?php
/**
 * AI Writing Assistant page.
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;
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
						<?php foreach ( $templates as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat['id'] ); ?>"
								data-wp-cat="<?php echo esc_attr( $cat['wp_category_id'] ); ?>">
								<?php echo esc_html( $cat['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<label for="aiaw-select-topic"><?php esc_html_e( 'Topic', 'ai-assisted-writing' ); ?></label><br />
					<select id="aiaw-select-topic" class="regular-text" disabled>
						<option value=""><?php esc_html_e( '-- Select Topic --', 'ai-assisted-writing' ); ?></option>
					</select>
				</p>

				<p>
					<label for="aiaw-keywords"><?php esc_html_e( 'Keywords (optional)', 'ai-assisted-writing' ); ?></label><br />
					<input type="text" id="aiaw-keywords" class="regular-text"
						placeholder="<?php esc_attr_e( 'e.g. technology, AI trends', 'ai-assisted-writing' ); ?>" />
				</p>

				<p>
					<label><?php esc_html_e( 'Generation Mode', 'ai-assisted-writing' ); ?></label><br />
					<label>
						<input type="radio" name="aiaw_mode" value="oneshot" checked />
						<?php esc_html_e( 'One-shot (full article)', 'ai-assisted-writing' ); ?>
					</label><br />
					<label>
						<input type="radio" name="aiaw_mode" value="stepbystep" />
						<?php esc_html_e( 'Step-by-step (outline first)', 'ai-assisted-writing' ); ?>
					</label>
				</p>

				<p>
					<button type="button" id="aiaw-generate-btn" class="button button-primary button-large">
						<?php esc_html_e( 'Generate', 'ai-assisted-writing' ); ?>
					</button>
					<span id="aiaw-generate-status"></span>
				</p>
			</div>

			<!-- Outline Panel (hidden until step-by-step mode generates an outline) -->
			<div id="aiaw-outline-panel" class="aiaw-card" style="display:none;">
				<h3><?php esc_html_e( 'Outline', 'ai-assisted-writing' ); ?></h3>
				<div id="aiaw-outline-content"></div>
				<p>
					<button type="button" id="aiaw-expand-all-btn" class="button button-primary">
						<?php esc_html_e( 'Expand All Sections', 'ai-assisted-writing' ); ?>
					</button>
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
					<button type="button" id="aiaw-auto-tags-btn" class="button button-small">
						<?php esc_html_e( 'Auto-generate Tags', 'ai-assisted-writing' ); ?>
					</button>
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
		</div>
	</div>

	<?php endif; ?>
</div>

<!-- Templates data for JS -->
<script type="text/javascript">
	var aiawTemplates = <?php echo wp_json_encode( $templates ); ?>;
</script>
