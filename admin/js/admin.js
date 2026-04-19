(function($) {
	'use strict';

	var AIAW = {
		init: function() {
			this.bindApiSettings();
			this.bindTemplateManager();
			this.bindWritingAssistant();
		},

		// ========================================
		// API Settings
		// ========================================
		bindApiSettings: function() {
			var self = this;

			$('#aiaw-test-api').on('click', function() {
				self.testConnection();
			});

			$('#aiaw-fetch-models').on('click', function() {
				self.fetchModels();
			});

			$(document).on('change', '.aiaw-model-select', function() {
				var type = $(this).data('type');
				var val = $(this).val();
				var $input = $('#aiaw_' + type + '_model_input');
				var $hidden = $('#aiaw_' + type + '_model');

				if (val === '__custom__') {
					$input.show().focus();
					$hidden.val($input.val());
				} else {
					$input.hide();
					$hidden.val(val);
				}
			});

			$(document).on('input', '.aiaw-model-custom', function() {
				var type = $(this).data('type');
				$('#aiaw_' + type + '_model').val($(this).val());
			});
		},

		testConnection: function() {
			var $btn = $('#aiaw-test-api');
			var $status = $('#aiaw-api-status');

			$btn.prop('disabled', true);
			$status.removeClass('notice notice-success notice-error')
				.text(aiaw.strings.testing).show();

			$.post(aiaw.ajax_url, {
				action: 'aiaw_test_api',
				nonce: aiaw.nonce,
				api_url: $('#aiaw_api_url').val(),
				api_key: $('#aiaw_api_key').val()
			}, function(response) {
				$btn.prop('disabled', false);
				if (response.success) {
					$status.addClass('notice notice-success').text(response.data.message);
				} else {
					$status.addClass('notice notice-error')
						.text(aiaw.strings.failed + ' ' + (response.data.message || ''));
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$status.addClass('notice notice-error').text(aiaw.strings.failed);
			});
		},

		fetchModels: function() {
			var self = this;
			var $btn = $('#aiaw-fetch-models');
			var $status = $('#aiaw-api-status');

			$btn.prop('disabled', true);
			$status.removeClass('notice notice-success notice-error')
				.text(aiaw.strings.fetching).show();

			$.post(aiaw.ajax_url, {
				action: 'aiaw_fetch_models',
				nonce: aiaw.nonce,
				api_url: $('#aiaw_api_url').val(),
				api_key: $('#aiaw_api_key').val()
			}, function(response) {
				$btn.prop('disabled', false);
				if (response.success) {
					var models = response.data.models || [];
					if (models.length > 0) {
						$status.addClass('notice notice-success')
							.text(models.length + ' ' + aiaw.strings.fetch_ok);
					} else {
						$status.addClass('notice notice-success')
							.text(aiaw.strings.fetch_empty);
					}
					self.populateModels(models);
				} else {
					$status.addClass('notice notice-error')
						.text(aiaw.strings.failed + ' ' + (response.data.message || ''));
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$status.addClass('notice notice-error').text(aiaw.strings.failed);
			});
		},

		populateModels: function(models) {
			this.buildModelSelect('primary', models);
			this.buildModelSelect('backup', models);
		},

		buildModelSelect: function(type, models) {
			var $select = $('#aiaw_' + type + '_model_select');
			var $hidden = $('#aiaw_' + type + '_model');
			var $input = $('#aiaw_' + type + '_model_input');
			var savedVal = $hidden.val() || '';

			$select.empty();
			$select.append($('<option>').val('').text(aiaw.strings.select_model));

			for (var i = 0; i < models.length; i++) {
				$select.append($('<option>').val(models[i].id).text(models[i].name));
			}

			if (savedVal && !models.some(function(m) { return m.id === savedVal; })) {
				$select.append($('<option>').val(savedVal).text(savedVal));
			}

			$select.append($('<option>').val('__custom__').text(aiaw.strings.custom_model));

			if (savedVal && $select.find('option[value="' + savedVal + '"]').length) {
				$select.val(savedVal);
			}

			$hidden.val($select.val());
		},

		// ========================================
		// Template Manager
		// ========================================
		bindTemplateManager: function() {
			var self = this;

			$('#aiaw-add-category').on('click', function() {
				self.saveTemplate('add_category', {
					name: $('#aiaw-new-cat-name').val(),
					wp_category_id: $('#aiaw-new-cat-wpid').val()
				});
			});

			$(document).on('click', '.aiaw-delete-cat', function() {
				if (confirm(aiaw.strings.confirm_del_cat)) {
					self.deleteTemplate('delete_category', { id: $(this).data('id') });
				}
			});

			$(document).on('click', '.aiaw-edit-cat', function() {
				$('#aiaw-edit-cat-id').val($(this).data('id'));
				$('#aiaw-edit-cat-name').val($(this).data('name'));
				$('#aiaw-edit-cat-wpid').val($(this).data('wpid'));
				$('#aiaw-edit-cat-modal').show();
			});

			$('#aiaw-save-edit-cat').on('click', function() {
				self.saveTemplate('update_category', {
					id: $('#aiaw-edit-cat-id').val(),
					name: $('#aiaw-edit-cat-name').val(),
					wp_category_id: $('#aiaw-edit-cat-wpid').val()
				});
				$('#aiaw-edit-cat-modal').hide();
			});

			$(document).on('click', '.aiaw-add-topic', function() {
				var $form = $(this).closest('.aiaw-add-topic-form');
				self.saveTemplate('add_topic', {
					category_id: $(this).data('cat-id'),
					name: $form.find('.aiaw-topic-name').val(),
					prompt: $form.find('.aiaw-topic-prompt-input').val()
				});
			});

			$(document).on('click', '.aiaw-delete-topic', function() {
				if (confirm(aiaw.strings.confirm_del_topic)) {
					self.deleteTemplate('delete_topic', {
						category_id: $(this).data('cat-id'),
						id: $(this).data('id')
					});
				}
			});

			$(document).on('click', '.aiaw-edit-topic', function() {
				$('#aiaw-edit-topic-cat-id').val($(this).data('cat-id'));
				$('#aiaw-edit-topic-id').val($(this).data('id'));
				$('#aiaw-edit-topic-name').val($(this).data('name'));
				$('#aiaw-edit-topic-prompt').val($(this).data('prompt'));
				$('#aiaw-edit-topic-modal').show();
			});

			$('#aiaw-save-edit-topic').on('click', function() {
				self.saveTemplate('update_topic', {
					category_id: $('#aiaw-edit-topic-cat-id').val(),
					id: $('#aiaw-edit-topic-id').val(),
					name: $('#aiaw-edit-topic-name').val(),
					prompt: $('#aiaw-edit-topic-prompt').val()
				});
				$('#aiaw-edit-topic-modal').hide();
			});

			$('.aiaw-close-modal').on('click', function() {
				$(this).closest('.aiaw-modal').hide();
			});
		},

		saveTemplate: function(action, data) {
			data.template_action = action;
			data.nonce = aiaw.nonce;
			data.action = 'aiaw_save_template';

			$.post(aiaw.ajax_url, data, function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert(aiaw.strings.save_err + ' ' + (response.data.message || ''));
				}
			});
		},

		deleteTemplate: function(action, data) {
			data.template_action = action;
			data.nonce = aiaw.nonce;
			data.action = 'aiaw_delete_template';

			$.post(aiaw.ajax_url, data, function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert(aiaw.strings.del_err + ' ' + (response.data.message || ''));
				}
			});
		},

		// ========================================
		// Writing Assistant
		// ========================================
		bindWritingAssistant: function() {
			var self = this;

			// Category change -> AJAX load matching AI templates
			$('#aiaw-select-category').on('change', function() {
				var catId = $(this).val();
				var $template = $('#aiaw-select-template');
				$template.find('option:not(:first)').remove();

				if (!catId) {
					$template.prop('disabled', true);
					return;
				}

				$template.prop('disabled', false);
				$template.append($('<option>').val('').text(aiaw.strings.loading || 'Loading...'));

				$.post(aiaw.ajax_url, {
					action: 'aiaw_get_templates',
					nonce: aiaw.nonce,
					category_id: catId
				}, function(response) {
					$template.find('option:not(:first)').remove();

					if (response.success && response.data.topics && response.data.topics.length > 0) {
						for (var i = 0; i < response.data.topics.length; i++) {
							var t = response.data.topics[i];
							$template.append($('<option>').val(t.id).text(t.name));
						}
					} else {
						$template.append($('<option>').val('').text(aiaw.strings.no_templates));
					}
				}).fail(function() {
					$template.find('option:not(:first)').remove();
					$template.append($('<option>').val('').text(aiaw.strings.no_templates));
				});
			});

			// Generate button (streaming with fallback)
			$('#aiaw-generate-btn').on('click', function() {
				self.generateArticle();
			});

			// Save / Publish
			$('#aiaw-save-draft-btn').on('click', function() {
				self.createPost('draft');
			});
			$('#aiaw-publish-btn').on('click', function() {
				self.createPost('publish');
			});
		},

		generateArticle: function() {
			var catId = $('#aiaw-select-category').val();
			var title = $('#aiaw-input-title').val();
			var templateId = $('#aiaw-select-template').val();

			if (!catId) { alert(aiaw.strings.select_cat); return; }
			if (!title) { alert(aiaw.strings.enter_title); return; }

			var self = this;
			var $status = $('#aiaw-generate-status');
			var $content = $('#aiaw-article-content');
			var $articleTitle = $('#aiaw-article-title');
			var $btn = $('#aiaw-generate-btn');

			$btn.prop('disabled', true);
			$status.text(aiaw.strings.generating);
			$content.val('');
			$articleTitle.val(title);
			$('#aiaw-article-cat-id').val(catId);

			// Try streaming first
			self.generateStream(catId, title, templateId, $btn, $status, $content, $articleTitle);
		},

		generateStream: function(catId, title, templateId, $btn, $status, $content, $articleTitle) {
			var self = this;
			var fullContent = '';

			var params = new URLSearchParams({
				action: 'aiaw_generate_stream',
				nonce: aiaw.nonce,
				category_id: catId,
				template_id: templateId || '',
				title: title,
				description: $('#aiaw-input-description').val()
			});

			fetch(aiaw.ajax_url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params.toString()
			}).then(function(response) {
				if (!response.ok) {
					// Non-200 response - fall back to AJAX
					self.generateAjax(catId, title, templateId, $btn, $status, $content, $articleTitle);
					return;
				}

				var reader = response.body.getReader();
				var decoder = new TextDecoder();
				var buffer = '';

				function processChunk(result) {
					if (result.done) {
						self.finalizeGeneratedContent(fullContent, title, $content, $articleTitle);
						$status.text('');
						$btn.prop('disabled', false);
						return;
					}

					buffer += decoder.decode(result.value, { stream: true });
					var lines = buffer.split('\n');
					buffer = lines.pop();

					for (var i = 0; i < lines.length; i++) {
						var line = lines[i].trim();
						if (line.indexOf('data: ') !== 0) continue;

						var data;
						try { data = JSON.parse(line.substring(6)); } catch(e) { continue; }

						if (data.type === 'content') {
							fullContent += data.content;
							$content.val(fullContent);
							$content.scrollTop($content[0].scrollHeight);
						} else if (data.type === 'done') {
							self.finalizeGeneratedContent(fullContent, title, $content, $articleTitle);
							$status.text('');
							$btn.prop('disabled', false);
							return;
						} else if (data.type === 'error') {
							$status.text('');
							$btn.prop('disabled', false);
							alert(data.message);
							return;
						}
					}

					return reader.read().then(processChunk);
				}

				return reader.read().then(processChunk);
			}).catch(function() {
				// Fetch failed (no ReadableStream support or network error) - fall back to AJAX
				self.generateAjax(catId, title, templateId, $btn, $status, $content, $articleTitle);
			});
		},

		/**
		 * Fallback: non-streaming AJAX generation.
		 */
		generateAjax: function(catId, title, templateId, $btn, $status, $content, $articleTitle) {
			var self = this;
			$status.text(aiaw.strings.generating);

			$.post(aiaw.ajax_url, {
				action: 'aiaw_generate',
				nonce: aiaw.nonce,
				category_id: catId,
				template_id: templateId || '',
				title: title,
				description: $('#aiaw-input-description').val()
			}, function(response) {
				$btn.prop('disabled', false);
				$status.text('');
				if (response.success) {
					$articleTitle.val(response.data.title || title);
					$content.val(response.data.content || '');
					$('#aiaw-article-cat-id').val(response.data.category_id);
					if (response.data.tags && response.data.tags.length > 0) {
						$('#aiaw-article-tags').val(response.data.tags.join(', '));
					}
				} else {
					alert(response.data.message);
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$status.text('');
				alert(aiaw.strings.req_failed);
			});
		},

		/**
		 * After streaming finishes, extract title from H1 if present.
		 */
		finalizeGeneratedContent: function(content, fallbackTitle, $content, $articleTitle) {
			if (!content) return;

			var titleMatch = content.match(/^#\s+(.+)$/m);
			if (titleMatch) {
				$articleTitle.val(titleMatch[1]);
				var cleaned = content.replace(/^#\s+.+[\r]?\n?/, '').trim();
				$content.val(cleaned);
			} else {
				$articleTitle.val(fallbackTitle);
				$content.val(content);
			}
		},

		createPost: function(status) {
			var title = $('#aiaw-article-title').val();
			var content = $('#aiaw-article-content').val();
			var catId = $('#aiaw-article-cat-id').val();
			var tagsStr = $('#aiaw-article-tags').val();
			var tags = tagsStr ? tagsStr.split(',').map(function(t) { return t.trim(); }).filter(Boolean) : [];

			if (!title || !content) {
				alert(aiaw.strings.title_required);
				return;
			}

			var $status = $('#aiaw-save-status');
			$status.text(aiaw.strings.saving);

			$.post(aiaw.ajax_url, {
				action: 'aiaw_create_post',
				nonce: aiaw.nonce,
				title: title,
				content: content,
				category_id: catId,
				tags: tags,
				status: status
			}, function(response) {
				if (response.success) {
					$status.text(aiaw.strings.saved);
					if (response.data.edit_url) {
						window.open(response.data.edit_url, '_blank');
					}
				} else {
					$status.text('');
					alert(response.data.message);
				}
			}).fail(function() {
				$status.text('');
				alert(aiaw.strings.req_failed);
			});
		}
	};

	$(document).ready(function() {
		AIAW.init();
	});
})(jQuery);
