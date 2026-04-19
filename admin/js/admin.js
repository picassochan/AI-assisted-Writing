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

			$('#aiaw-select-category').on('change', function() {
				var catId = $(this).val();
				var $topic = $('#aiaw-select-topic');
				$topic.find('option:not(:first)').remove();

				if (!catId) {
					$topic.prop('disabled', true);
					return;
				}

				$topic.prop('disabled', false);
				var cat = (aiawTemplates || []).find(function(c) { return c.id === catId; });
				if (cat && cat.topics) {
					cat.topics.forEach(function(t) {
						$topic.append($('<option>').val(t.id).text(t.name));
					});
				}
			});

			$('#aiaw-generate-btn').on('click', function() {
				var mode = $('input[name="aiaw_mode"]:checked').val();
				if (mode === 'stepbystep') {
					self.generateOutline();
				} else {
					self.generateArticle();
				}
			});

			$('#aiaw-expand-all-btn').on('click', function() {
				self.expandAllSections();
			});

			$('#aiaw-auto-tags-btn').on('click', function() {
				self.generateTags();
			});

			$('#aiaw-save-draft-btn').on('click', function() {
				self.createPost('draft');
			});
			$('#aiaw-publish-btn').on('click', function() {
				self.createPost('publish');
			});
		},

		generateArticle: function() {
			var catId = $('#aiaw-select-category').val();
			var topicId = $('#aiaw-select-topic').val();

			if (!catId) { alert(aiaw.strings.select_cat); return; }
			if (!topicId) { alert(aiaw.strings.select_topic); return; }

			var $status = $('#aiaw-generate-status');
			$('#aiaw-generate-btn').prop('disabled', true);
			$status.text(aiaw.strings.generating);

			$.post(aiaw.ajax_url, {
				action: 'aiaw_generate',
				nonce: aiaw.nonce,
				category_id: catId,
				topic_id: topicId,
				keywords: $('#aiaw-keywords').val()
			}, function(response) {
				$('#aiaw-generate-btn').prop('disabled', false);
				$status.text('');
				if (response.success) {
					var content = response.data.content;
					$('#aiaw-article-content').val(content);
					$('#aiaw-article-cat-id').val(response.data.category_id);
					var titleMatch = content.match(/^#\s+(.+)$/m);
					if (titleMatch) {
						$('#aiaw-article-title').val(titleMatch[1]);
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

		generateOutline: function() {
			var catId = $('#aiaw-select-category').val();
			var topicId = $('#aiaw-select-topic').val();

			if (!catId) { alert(aiaw.strings.select_cat); return; }
			if (!topicId) { alert(aiaw.strings.select_topic); return; }

			var $status = $('#aiaw-generate-status');
			$('#aiaw-generate-btn').prop('disabled', true);
			$status.text(aiaw.strings.gen_outline);

			$.post(aiaw.ajax_url, {
				action: 'aiaw_generate_outline',
				nonce: aiaw.nonce,
				category_id: catId,
				topic_id: topicId,
				keywords: $('#aiaw-keywords').val()
			}, function(response) {
				$('#aiaw-generate-btn').prop('disabled', false);
				$status.text('');
				if (response.success) {
					$('#aiaw-outline-panel').show();
					$('#aiaw-outline-content').empty().append(
						$('<textarea>').attr({
							id: 'aiaw-outline-text',
							class: 'large-text',
							rows: 10
						}).val(response.data.outline)
					);
					$('#aiaw-article-cat-id').val(response.data.category_id);
				} else {
					alert(response.data.message);
				}
			}).fail(function() {
				$('#aiaw-generate-btn').prop('disabled', false);
				$status.text('');
				alert(aiaw.strings.req_failed);
			});
		},

		expandAllSections: function() {
			var outline = $('#aiaw-outline-text').val();
			var lines = outline.split('\n');
			var sections = [];
			var content = '';

			lines.forEach(function(line) {
				var match = line.match(/^##\s+(.+)$/);
				if (match) {
					sections.push(match[1]);
				}
			});

			if (sections.length === 0) {
				alert(aiaw.strings.no_sections);
				return;
			}

			var $status = $('#aiaw-generate-status');
			var $btn = $('#aiaw-expand-all-btn');
			$btn.prop('disabled', true);
			$status.text(aiaw.strings.expanding);

			var index = 0;

			var expandNext = function() {
				if (index >= sections.length) {
					$btn.prop('disabled', false);
					$status.text('');
					$('#aiaw-article-content').val(content);
					var titleMatch = outline.match(/^#\s+(.+)$/m);
					if (titleMatch) {
						$('#aiaw-article-title').val(titleMatch[1]);
					}
					return;
				}

				var section = sections[index];
				$status.text(aiaw.strings.expanding + ' (' + (index + 1) + '/' + sections.length + ')');

				$.post(aiaw.ajax_url, {
					action: 'aiaw_expand_section',
					nonce: aiaw.nonce,
					outline: outline,
					section_index: index,
					section_title: section,
					keywords: $('#aiaw-keywords').val()
				}, function(response) {
					if (response.success) {
						content += '## ' + section + '\n\n' + response.data.content + '\n\n';
					}
					index++;
					expandNext();
				}).fail(function() {
					index++;
					expandNext();
				});
			};

			expandNext();
		},

		generateTags: function() {
			var content = $('#aiaw-article-content').val();
			if (!content) { alert(aiaw.strings.gen_content_first); return; }

			var $btn = $('#aiaw-auto-tags-btn');
			$btn.prop('disabled', true);

			$.post(aiaw.ajax_url, {
				action: 'aiaw_generate_tags',
				nonce: aiaw.nonce,
				content: content
			}, function(response) {
				$btn.prop('disabled', false);
				if (response.success && response.data.tags) {
					$('#aiaw-article-tags').val(response.data.tags.join(', '));
				} else {
					alert(response.data.message || aiaw.strings.tags_err);
				}
			}).fail(function() {
				$btn.prop('disabled', false);
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
