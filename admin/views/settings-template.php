<?php
/**
 * Settings page - Template tab content.
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;
?>

<h2><?php esc_html_e( 'AI Templates', 'ai-assisted-writing' ); ?></h2>

<div id="aiaw-template-manager">
	<!-- Add Category -->
	<div class="aiaw-card">
		<h3><?php esc_html_e( 'Add Category', 'ai-assisted-writing' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'Category Name', 'ai-assisted-writing' ); ?></th>
				<td><input type="text" id="aiaw-new-cat-name" class="regular-text" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'WordPress Category', 'ai-assisted-writing' ); ?></th>
				<td>
					<select id="aiaw-new-cat-wpid">
						<option value=""><?php esc_html_e( '-- Select --', 'ai-assisted-writing' ); ?></option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<button type="button" id="aiaw-add-category" class="button button-primary">
			<?php esc_html_e( 'Add Category', 'ai-assisted-writing' ); ?>
		</button>
	</div>

	<!-- Template List -->
	<div id="aiaw-template-list">
		<?php if ( empty( $templates ) ) : ?>
			<p class="aiaw-empty"><?php esc_html_e( 'No templates yet. Add a category above to get started.', 'ai-assisted-writing' ); ?></p>
		<?php else : ?>
			<?php foreach ( $templates as $cat ) : ?>
			<div class="aiaw-category-card" data-cat-id="<?php echo esc_attr( $cat['id'] ); ?>">
				<div class="aiaw-category-header">
					<h3>
						<span class="aiaw-cat-name"><?php echo esc_html( $cat['name'] ); ?></span>
						<span class="aiaw-cat-wp">(<?php
							$wp_cat = get_category( $cat['wp_category_id'] );
							echo $wp_cat ? esc_html( $wp_cat->name ) : esc_html__( 'Unassigned', 'ai-assisted-writing' );
						?>)</span>
					</h3>
					<div class="aiaw-category-actions">
						<button type="button" class="button button-small aiaw-edit-cat"
							data-id="<?php echo esc_attr( $cat['id'] ); ?>"
							data-name="<?php echo esc_attr( $cat['name'] ); ?>"
							data-wpid="<?php echo esc_attr( $cat['wp_category_id'] ); ?>">
							<?php esc_html_e( 'Edit', 'ai-assisted-writing' ); ?>
						</button>
						<button type="button" class="button button-small button-link-delete aiaw-delete-cat"
							data-id="<?php echo esc_attr( $cat['id'] ); ?>">
							<?php esc_html_e( 'Delete', 'ai-assisted-writing' ); ?>
						</button>
					</div>
				</div>

				<!-- Topics -->
				<div class="aiaw-topics">
					<?php if ( ! empty( $cat['topics'] ) ) : ?>
						<?php foreach ( $cat['topics'] as $topic ) : ?>
						<div class="aiaw-topic-item" data-topic-id="<?php echo esc_attr( $topic['id'] ); ?>">
							<strong><?php echo esc_html( $topic['name'] ); ?></strong>
							<details>
								<summary><?php esc_html_e( 'View Prompt', 'ai-assisted-writing' ); ?></summary>
								<p class="aiaw-topic-prompt"><?php echo esc_html( $topic['prompt'] ); ?></p>
							</details>
							<div class="aiaw-topic-actions">
								<button type="button" class="button button-small aiaw-edit-topic"
									data-cat-id="<?php echo esc_attr( $cat['id'] ); ?>"
									data-id="<?php echo esc_attr( $topic['id'] ); ?>"
									data-name="<?php echo esc_attr( $topic['name'] ); ?>"
									data-prompt="<?php echo esc_attr( $topic['prompt'] ); ?>">
									<?php esc_html_e( 'Edit', 'ai-assisted-writing' ); ?>
								</button>
								<button type="button" class="button button-small button-link-delete aiaw-delete-topic"
									data-cat-id="<?php echo esc_attr( $cat['id'] ); ?>"
									data-id="<?php echo esc_attr( $topic['id'] ); ?>">
									<?php esc_html_e( 'Delete', 'ai-assisted-writing' ); ?>
								</button>
							</div>
						</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="aiaw-no-topics"><?php esc_html_e( 'No topics yet.', 'ai-assisted-writing' ); ?></p>
					<?php endif; ?>

					<!-- Add Topic -->
					<div class="aiaw-add-topic-form">
						<h4><?php esc_html_e( 'Add Topic', 'ai-assisted-writing' ); ?></h4>
						<p>
							<label><?php esc_html_e( 'Topic Name', 'ai-assisted-writing' ); ?><br />
							<input type="text" class="aiaw-topic-name regular-text" /></label>
						</p>
						<p>
							<label><?php esc_html_e( 'Prompt', 'ai-assisted-writing' ); ?><br />
							<textarea class="aiaw-topic-prompt-input large-text" rows="3"></textarea></label>
						</p>
						<button type="button" class="button aiaw-add-topic"
							data-cat-id="<?php echo esc_attr( $cat['id'] ); ?>">
							<?php esc_html_e( 'Add Topic', 'ai-assisted-writing' ); ?>
						</button>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<!-- Edit Category Modal -->
<div id="aiaw-edit-cat-modal" class="aiaw-modal" style="display:none;">
	<div class="aiaw-modal-content">
		<h3><?php esc_html_e( 'Edit Category', 'ai-assisted-writing' ); ?></h3>
		<p>
			<label><?php esc_html_e( 'Category Name', 'ai-assisted-writing' ); ?><br />
			<input type="text" id="aiaw-edit-cat-name" class="regular-text" /></label>
		</p>
		<p>
			<label><?php esc_html_e( 'WordPress Category', 'ai-assisted-writing' ); ?><br />
			<select id="aiaw-edit-cat-wpid">
				<option value=""><?php esc_html_e( '-- Select --', 'ai-assisted-writing' ); ?></option>
				<?php foreach ( $categories as $cat ) : ?>
					<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
				<?php endforeach; ?>
			</select></label>
		</p>
		<input type="hidden" id="aiaw-edit-cat-id" />
		<p>
			<button type="button" id="aiaw-save-edit-cat" class="button button-primary"><?php esc_html_e( 'Save', 'ai-assisted-writing' ); ?></button>
			<button type="button" class="button aiaw-close-modal"><?php esc_html_e( 'Cancel', 'ai-assisted-writing' ); ?></button>
		</p>
	</div>
</div>

<!-- Edit Topic Modal -->
<div id="aiaw-edit-topic-modal" class="aiaw-modal" style="display:none;">
	<div class="aiaw-modal-content">
		<h3><?php esc_html_e( 'Edit Topic', 'ai-assisted-writing' ); ?></h3>
		<p>
			<label><?php esc_html_e( 'Topic Name', 'ai-assisted-writing' ); ?><br />
			<input type="text" id="aiaw-edit-topic-name" class="regular-text" /></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Prompt', 'ai-assisted-writing' ); ?><br />
			<textarea id="aiaw-edit-topic-prompt" class="large-text" rows="5"></textarea></label>
		</p>
		<input type="hidden" id="aiaw-edit-topic-cat-id" />
		<input type="hidden" id="aiaw-edit-topic-id" />
		<p>
			<button type="button" id="aiaw-save-edit-topic" class="button button-primary"><?php esc_html_e( 'Save', 'ai-assisted-writing' ); ?></button>
			<button type="button" class="button aiaw-close-modal"><?php esc_html_e( 'Cancel', 'ai-assisted-writing' ); ?></button>
		</p>
	</div>
</div>
