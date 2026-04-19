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
		</div>
	</div>

	<?php endif; ?>
</div>

<!-- Templates data for JS -->
<script type="text/javascript">
	var aiawTemplates = <?php echo wp_json_encode( $templates ); ?>;
</script>
