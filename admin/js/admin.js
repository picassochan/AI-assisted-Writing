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
			$select.append($('<option>').val('').text('-- Select Model --'));

			for (var i = 0; i < models.length; i++) {
				$select.append($('<option>').val(models[i].id).text(models[i].name));
			}

			if (savedVal && !models.some(function(m) { return m.id === savedVal; })) {
				$select.append($('<option>').val(savedVal).text(savedVal));
			}

			$select.append($('<option>').val('__custom__').text('-- Custom --'));

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

			// Category change -> populate matching AI templates
			$('#aiaw-select-category').on('change', function() {
				var catId = parseInt($(this).val(), 10);
				var $template = $('#aiaw-select-template');
				$template.find('option:not(:first)').remove();

				if (!catId) {
					$template.prop('disabled', true);
					return;
				}

				$template.prop('disabled', false);
				var found = false;
				(aiawTemplates || []).forEach(function(cat) {
					if (parseInt(cat.wp_category_id, 10) === catId && cat.topics) {
						cat.topics.forEach(function(t) {
							$template.append($('<option>').val(t.id).text(t.name));
							found = true;
						});
					}
				});

				if (!found) {
					$template.append($('<option>').val('').text(aiaw.strings.no_templates));
				}
			});

			// Generate button
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

			var $status = $('#aiaw-generate-status');
			$('#aiaw-generate-btn').prop('disabled', true);
			$status.text(aiaw.strings.generating);

			$.post(aiaw.ajax_url, {
				action: 'aiaw_generate',
				nonce: aiaw.nonce,
				category_id: catId,
				template_id: templateId || '',
				title: title,
				description: $('#aiaw-input-description').val()
			}, function(response) {
				$('#aiaw-generate-btn').prop('disabled', false);
				$status.text('');
				if (response.success) {
					$('#aiaw-article-title').val(response.data.title || title);
					$('#aiaw-article-content').val(response.data.content || '');
					$('#aiaw-article-cat-id').val(response.data.category_id);
					if (response.data.tags && response.data.tags.length > 0) {
						$('#aiaw-article-tags').val(response.data.tags.join(', '));
					}
				} else {
					alert(response.data.message);
				}
			}).fail(function() {
				$('#aiaw-generate-btn').prop('disabled', false);
				$status.text('');
				alert(aiaw.strings.req_failed);
			});
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
