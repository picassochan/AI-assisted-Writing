(function($) {
	'use strict';

	var AIAW = {
		debug: false,

		dlog: function() {
			if (!this.debug) return;
			var args = Array.prototype.slice.call(arguments);
			var msg = '[AIAW] ' + args.join(' ');
			console.log(msg);
			var $log = $('#aiaw-debug-log');
			if ($log.length) {
				$log.append(msg + '\n');
				$log.scrollTop($log[0].scrollHeight);
			}
		},

		init: function() {
			var $jsStatus = $( '#aiaw-debug-js-status' );
			if ($jsStatus.length) { $jsStatus.text('YES').css('color','green'); }

			this.debug = !!(aiaw && aiaw.debug);
			this.dlog('init — debug mode ON');
			this.dlog('aiaw.templates =', JSON.stringify(aiaw.templates || []));

			// Show JS templates in debug panel
			var $jsTpl = $('#aiaw-debug-js-templates');
			if ($jsTpl.length) {
				$jsTpl.text(JSON.stringify(aiaw.templates || [], null, 2));
			}

			this.bindApiSettings();
			this.bindTemplateManager();
			this.bindWritingAssistant();
			this.populateModelDropdown();
			this.abortController = null;
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

				self.dlog('Category changed, catId=', catId);

				if (!catId) {
					$template.prop('disabled', true);
					self.dlog('No category selected, template dropdown disabled');
					return;
				}

				$template.prop('disabled', false);
				$template.append($('<option>').val('').text(aiaw.strings.loading || 'Loading...'));

				self.dlog('Sending AJAX aiaw_get_templates for category_id=', catId);

				$.post(aiaw.ajax_url, {
					action: 'aiaw_get_templates',
					nonce: aiaw.nonce,
					category_id: catId
				}, function(response) {
					self.dlog('AJAX response:', JSON.stringify(response));
					$template.find('option:not(:first)').remove();

					if (response.success && response.data.topics && response.data.topics.length > 0) {
						self.dlog('Found', response.data.topics.length, 'topics');
						for (var i = 0; i < response.data.topics.length; i++) {
							var t = response.data.topics[i];
							$template.append($('<option>').val(t.id).text(t.name));
						}
					} else {
						self.dlog('No topics found. response.success=', response.success, 'data=', JSON.stringify(response.data || {}));
						$template.append($('<option>').val('').text(aiaw.strings.no_templates));
					}
				}).fail(function(xhr, status, error) {
					self.dlog('AJAX FAILED:', status, error);
					$template.find('option:not(:first)').remove();
					$template.append($('<option>').val('').text(aiaw.strings.no_templates));
				});
			});

			// Generate button (streaming with fallback)
			$('#aiaw-generate-btn').on('click', function() {
				self.generateArticle();
			});

			$('#aiaw-stop-btn').on('click', function() {
				self.stopGeneration();
			});

			// Save / Publish
			$('#aiaw-save-draft-btn').on('click', function() {
				self.createPost('draft');
			});
			$('#aiaw-publish-btn').on('click', function() {
				self.createPost('publish');
			});

			// Generate SEO button
			$('#aiaw-generate-seo-btn').on('click', function() {
				var title = $('#aiaw-article-title').val();
				var content = $('#aiaw-article-content').val();
				if (!title || !content) {
					alert(aiaw.strings.title_required);
					return;
				}
				self.generateSEO(title, content);
			});

			// SEO character counters
			$('#aiaw-seo-title').on('input', function() {
				self.updateSEOCount('#aiaw-seo-title-count', $(this).val().length, 60);
			});
			$('#aiaw-seo-description').on('input', function() {
				self.updateSEOCount('#aiaw-seo-desc-count', $(this).val().length, 155);
			});
			$('#aiaw-seo-og-title').on('input', function() {
				self.updateSEOCount('#aiaw-seo-og-title-count', $(this).val().length, 95);
			});
			$('#aiaw-seo-og-description').on('input', function() {
				self.updateSEOCount('#aiaw-seo-og-desc-count', $(this).val().length, 200);
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

			self.dlog('Generate: catId=', catId, 'title=', title, 'templateId=', templateId);

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
				description: $('#aiaw-input-description').val(),
					model: $('#aiaw-select-model').val() || ''
			});

			self.dlog('Stream: starting fetch to', aiaw.ajax_url);

			fetch(aiaw.ajax_url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params.toString(),
				signal: self.abortController ? self.abortController.signal : undefined
			}).then(function(response) {
				self.dlog('Stream: response status=', response.status, 'ok=', response.ok);

				if (!response.ok) {
					self.dlog('Stream: non-200 response, falling back to AJAX');
					self.generateAjax(catId, title, templateId, $btn, $status, $content, $articleTitle);
					return;
				}

				if (!response.body || !response.body.getReader) {
					self.dlog('Stream: ReadableStream not available, falling back to AJAX');
					self.generateAjax(catId, title, templateId, $btn, $status, $content, $articleTitle);
					return;
				}

				var reader = response.body.getReader();
				var decoder = new TextDecoder();
				var buffer = '';

				function processChunk(result) {
					if (result.done) {
						self.dlog('Stream: reader done (no explicit [DONE])');
						self.finalizeGeneratedContent(fullContent, title, $content, $articleTitle);
						$status.text('');
						$btn.prop('disabled', false);
						// Auto-generate SEO
						var seoTitle = $articleTitle.val();
						var seoContent = $content.val();
						if (aiaw.seo_enabled && seoTitle && seoContent) { self.generateSEO(seoTitle, seoContent); }
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
							self.dlog('Stream: received [DONE], total chars=', fullContent.length);
							self.finalizeGeneratedContent(fullContent, title, $content, $articleTitle);
							$status.text('');
							$btn.prop('disabled', false);
							$('#aiaw-stop-btn').hide();
							// Auto-generate SEO
							var seoTitle = $articleTitle.val();
							var seoContent = $content.val();
							if (aiaw.seo_enabled && seoTitle && seoContent) { self.generateSEO(seoTitle, seoContent); }
							return;
						} else if (data.type === 'error') {
							self.dlog('Stream: error from server —', data.message);
							$status.text('');
							$btn.prop('disabled', false);
							alert(data.message);
							return;
						}
					}

					return reader.read().then(processChunk);
				}

				return reader.read().then(processChunk);
			}).catch(function(err) {
				if (err.name === 'AbortError') {
					self.dlog('Stream: aborted by user');
					$status.text('');
					$btn.prop('disabled', false);
					$('#aiaw-stop-btn').hide();
					return;
				}
				self.dlog('Stream: fetch failed —', err.message, '— falling back to AJAX');
				self.generateAjax(catId, title, templateId, $btn, $status, $content, $articleTitle);
			});
		},

		/**
		 * Fallback: non-streaming AJAX generation.
		 */
		generateAjax: function(catId, title, templateId, $btn, $status, $content, $articleTitle) {
			var self = this;
			$status.text(aiaw.strings.generating);
			self.dlog('AJAX fallback: sending request');

			$.post(aiaw.ajax_url, {
				action: 'aiaw_generate',
				nonce: aiaw.nonce,
				category_id: catId,
				template_id: templateId || '',
				title: title,
				description: $('#aiaw-input-description').val(),
				model: $('#aiaw-select-model').val() || ''
			}, function(response) {
				self.dlog('AJAX response:', JSON.stringify(response).substring(0, 500));
				$btn.prop('disabled', false);
				$('#aiaw-stop-btn').hide();
				$status.text('');
				if (response.success) {
					$articleTitle.val(response.data.title || title);
					$content.val(response.data.content || '');
					$('#aiaw-article-cat-id').val(response.data.category_id);
					if (response.data.tags && response.data.tags.length > 0) {
						$('#aiaw-article-tags').val(response.data.tags.join(', '));
					}
					// Auto-generate SEO
					var seoTitle = $articleTitle.val();
					var seoContent = $content.val();
					if (aiaw.seo_enabled && seoTitle && seoContent) { self.generateSEO(seoTitle, seoContent); }
				} else {
					self.dlog('AJAX error:', response.data.message);
					alert(response.data.message);
				}
			}).fail(function(xhr, status, error) {
				self.dlog('AJAX request failed:', status, error);
				$btn.prop('disabled', false);
				$('#aiaw-stop-btn').hide();
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

		/**
		 * Generate SEO metadata via AI.
		 */
		generateSEO: function(title, content) {
			var self = this;
			var $status = $('#aiaw-seo-status');
			var $btn = $('#aiaw-generate-seo-btn');

			$btn.prop('disabled', true);
			$status.text(aiaw.strings.generating_seo);

			self.dlog('SEO: generating for title=', title);

			$.post(aiaw.ajax_url, {
				action: 'aiaw_generate_seo',
				nonce: aiaw.nonce,
				title: title,
				content: content,
				model: $('#aiaw-select-model').val() || ''
			}, function(response) {
				$btn.prop('disabled', false);
				if (response.success && response.data) {
					$('#aiaw-seo-title').val(response.data.seo_title || '').trigger('input');
					$('#aiaw-seo-description').val(response.data.meta_description || '').trigger('input');
					$('#aiaw-seo-keywords').val(response.data.focus_keywords || '');
					$('#aiaw-seo-og-title').val(response.data.og_title || '').trigger('input');
					$('#aiaw-seo-og-description').val(response.data.og_description || '').trigger('input');
					$('#aiaw-seo-slug').val(response.data.slug || '');
					$status.text(aiaw.strings.seo_done);
					self.dlog('SEO: done');
				} else {
					$status.text('');
					self.dlog('SEO: error');
					alert(response.data ? response.data.message : aiaw.strings.seo_error);
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$status.text('');
				alert(aiaw.strings.seo_error);
			});
		},

		updateSEOCount: function(counterSelector, current, max) {
			$(counterSelector).text(current);
			if (current > max) {
				$(counterSelector).css('color', '#d63638');
			} else {
				$(counterSelector).css('color', '');
			}
		},

		populateModelDropdown: function() {
			var models = aiaw.models || [];
			var primary = aiaw.primary_model || '';
			var backup = aiaw.backup_model || '';
			var $select = $('#aiaw-select-model');

			for (var i = 0; i < models.length; i++) {
				$select.append($('<option>').val(models[i].id).text(models[i].id));
			}

			if (primary && !$select.find('option[value="' + primary + '"]').length) {
				$select.append($('<option>').val(primary).text(primary));
			}
			if (backup && !$select.find('option[value="' + backup + '"]').length) {
				$select.append($('<option>').val(backup).text(backup));
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
				status: status,
				seo_title: $('#aiaw-seo-title').val(),
				meta_description: $('#aiaw-seo-description').val(),
				focus_keywords: $('#aiaw-seo-keywords').val(),
				og_title: $('#aiaw-seo-og-title').val(),
				og_description: $('#aiaw-seo-og-description').val(),
				seo_slug: $('#aiaw-seo-slug').val()
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
