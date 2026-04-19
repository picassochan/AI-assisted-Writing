<?php
/**
 * Settings page - API tab content.
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;
?>

<form method="post" action="options.php">
	<?php settings_fields( 'aiaw_settings_group' ); ?>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<label for="aiaw_api_url"><?php esc_html_e( 'API URL', 'ai-assisted-writing' ); ?></label>
			</th>
			<td>
				<input type="url" id="aiaw_api_url"
					name="aiaw_api_settings[api_url]"
					value="<?php echo esc_attr( $settings['api_url'] ?? 'https://api.openai.com' ); ?>"
					class="regular-text" />
				<p class="description"><?php esc_html_e( 'OpenAI-compatible API endpoint (e.g. https://api.openai.com).', 'ai-assisted-writing' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="aiaw_api_key"><?php esc_html_e( 'API Key', 'ai-assisted-writing' ); ?></label>
			</th>
			<td>
				<input type="password" id="aiaw_api_key"
					name="aiaw_api_settings[api_key]"
					value="<?php echo esc_attr( $settings['api_key'] ?? '' ); ?>"
					class="regular-text" />
				<button type="button" id="aiaw-test-api" class="button button-secondary">
					<?php esc_html_e( 'Test Connection', 'ai-assisted-writing' ); ?>
				</button>
				<button type="button" id="aiaw-fetch-models" class="button button-secondary">
					<?php esc_html_e( 'Fetch Models', 'ai-assisted-writing' ); ?>
				</button>
				<span id="aiaw-api-status"></span>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Primary Model', 'ai-assisted-writing' ); ?>
			</th>
			<td>
				<select id="aiaw_primary_model_select" class="regular-text aiaw-model-select" data-type="primary">
					<option value=""><?php esc_html_e( '-- Select Model --', 'ai-assisted-writing' ); ?></option>
					<?php if ( ! empty( $saved_primary ) && ! in_array( $saved_primary, $cached_models, true ) ) : ?>
						<option value="<?php echo esc_attr( $saved_primary ); ?>" selected><?php echo esc_html( $saved_primary ); ?></option>
					<?php endif; ?>
					<?php foreach ( $cached_models as $m ) : ?>
						<option value="<?php echo esc_attr( $m ); ?>"<?php selected( $m, $saved_primary ); ?>><?php echo esc_html( $m ); ?></option>
					<?php endforeach; ?>
					<option value="__custom__"><?php esc_html_e( '-- Custom --', 'ai-assisted-writing' ); ?></option>
				</select>
				<input type="text" id="aiaw_primary_model_input"
					class="regular-text aiaw-model-custom" data-type="primary"
					placeholder="<?php esc_attr_e( 'Enter model ID', 'ai-assisted-writing' ); ?>"
					value="<?php echo esc_attr( $saved_primary ); ?>"
					style="display:none;" />
				<input type="hidden" id="aiaw_primary_model"
					name="aiaw_api_settings[primary_model]"
					value="<?php echo esc_attr( $saved_primary ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Backup Model', 'ai-assisted-writing' ); ?>
			</th>
			<td>
				<select id="aiaw_backup_model_select" class="regular-text aiaw-model-select" data-type="backup">
					<option value=""><?php esc_html_e( '-- Select Model --', 'ai-assisted-writing' ); ?></option>
					<?php if ( ! empty( $saved_backup ) && ! in_array( $saved_backup, $cached_models, true ) ) : ?>
						<option value="<?php echo esc_attr( $saved_backup ); ?>" selected><?php echo esc_html( $saved_backup ); ?></option>
					<?php endif; ?>
					<?php foreach ( $cached_models as $m ) : ?>
						<option value="<?php echo esc_attr( $m ); ?>"<?php selected( $m, $saved_backup ); ?>><?php echo esc_html( $m ); ?></option>
					<?php endforeach; ?>
					<option value="__custom__"><?php esc_html_e( '-- Custom --', 'ai-assisted-writing' ); ?></option>
				</select>
				<input type="text" id="aiaw_backup_model_input"
					class="regular-text aiaw-model-custom" data-type="backup"
					placeholder="<?php esc_attr_e( 'Enter model ID', 'ai-assisted-writing' ); ?>"
					value="<?php echo esc_attr( $saved_backup ); ?>"
					style="display:none;" />
				<input type="hidden" id="aiaw_backup_model"
					name="aiaw_api_settings[backup_model]"
					value="<?php echo esc_attr( $saved_backup ); ?>" />
				<p class="description"><?php esc_html_e( 'Used as fallback when the primary model fails.', 'ai-assisted-writing' ); ?></p>
			</td>
		</tr>
	</table>

	<hr />

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Debug Mode', 'ai-assisted-writing' ); ?>
			</th>
			<td>
				<label>
					<input type="checkbox" id="aiaw_debug_mode"
						name="aiaw_api_settings[debug_mode]"
						value="1" <?php checked( ! empty( $settings['debug_mode'] ) ); ?> />
					<?php esc_html_e( 'Enable debug mode (shows debug panel on Writing Assistant page)', 'ai-assisted-writing' ); ?>
				</label>
			</td>
		</tr>
	</table>

	<hr />

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<?php esc_html_e( 'SEO Integration', 'ai-assisted-writing' ); ?>
			</th>
			<td>
				<label>
					<input type="checkbox" id="aiaw_seo_enabled"
						name="aiaw_api_settings[seo_enabled]"
						value="1" <?php checked( ! empty( $settings['seo_enabled'] ) ); ?> />
					<?php esc_html_e( 'Enable AI SEO metadata generation', 'ai-assisted-writing' ); ?>
				</label>
				<?php
				$detected = AIAW_Settings::detect_seo_plugin();
				if ( $detected ) :
					$plugin_name = AIAW_Settings::get_seo_plugin_name( $detected );
				?>
					<p class="description" style="margin-top:6px;">
						<span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span>
						<?php printf( esc_html__( 'Detected: %s — SEO data will be saved to this plugin.', 'ai-assisted-writing' ), '<strong>' . esc_html( $plugin_name ) . '</strong>' ); ?>
					</p>
				<?php else : ?>
					<p class="description" style="margin-top:6px;">
						<span class="dashicons dashicons-warning" style="color:#dba617;"></span>
						<?php esc_html_e( 'No SEO plugin detected. Install Rank Math, Yoast SEO, or All in One SEO for best results.', 'ai-assisted-writing' ); ?>
					</p>
				<?php endif; ?>
			</td>
		</tr>
	</table>

	<?php submit_button(); ?>
</form>
