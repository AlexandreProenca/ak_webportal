/**
 * @package    HikaShop
 * @version    6.5.0
 * @author     hikashop.com
 * @copyright  (C) 2010-2026 HIKARI SOFTWARE. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
/**
 * HikaShop AI Assist — client-side module.
 *
 * Manages the textarea + generate button + preview/diff + apply/discard UI
 * for product descriptions (TinyMCE), translations (TinyMCE), and
 * view overrides (CodeMirror).
 */
window.hikashopAi = {

	instances: {},

	/**
	 * Initialise one AI-assist instance.
	 *
	 * @param {Object} config
	 *   containerId  - wrapper div id
	 *   editorId     - linked editor textarea id
	 *   type         - 'description' | 'translation' | 'view'
	 *   previewMode  - 'preview' | 'diff'
	 *   context      - {type, product_id, language_id, field, view_id}
	 *   ajaxUrl      - HikaShop controller URL (includes CSRF token)
	 */
	init: function(config) {
		var container = document.getElementById(config.containerId);
		if(!container)
			return;

		var inst = {
			config: config,
			container: container,
			generatedContent: '',
			previousContent: ''
		};

		this.instances[config.containerId] = inst;

		var self = this;
		var btn = container.querySelector('.hikashop_ai_generate_btn');
		if(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				self.generate(inst);
			});
		}

		var applyBtn = container.querySelector('.hikashop_ai_apply_btn');
		if(applyBtn) {
			applyBtn.addEventListener('click', function(e) {
				e.preventDefault();
				self.apply(inst);
			});
		}

		var discardBtn = container.querySelector('.hikashop_ai_discard_btn');
		if(discardBtn) {
			discardBtn.addEventListener('click', function(e) {
				e.preventDefault();
				self.discard(inst);
			});
		}
	},

	/**
	 * Get content from the linked editor (TinyMCE or CodeMirror).
	 */
	getEditorContent: function(editorId) {
		// TinyMCE
		if(typeof tinymce !== 'undefined') {
			var ed = tinymce.get(editorId);
			if(ed)
				return ed.getContent();
		}

		// Joomla editors proxy
		if(typeof Joomla !== 'undefined' && Joomla.editors && Joomla.editors.instances && Joomla.editors.instances[editorId]) {
			var v = Joomla.editors.instances[editorId].getValue();
			if(v)
				return v;
		}

		// CodeMirror 5 (WordPress: textarea.hikaCM)
		var ta = document.getElementById(editorId);
		if(ta) {
			if(ta.hikaCM)
				return ta.hikaCM.getValue();

			var cmWrapper = ta.nextElementSibling;
			if(cmWrapper && cmWrapper.classList && cmWrapper.classList.contains('CodeMirror') && cmWrapper.CodeMirror)
				return cmWrapper.CodeMirror.getValue();

			return ta.value;
		}

		return '';
	},

	/**
	 * Set content to the linked editor.
	 */
	setEditorContent: function(editorId, content) {
		if(typeof tinymce !== 'undefined') {
			var ed = tinymce.get(editorId);
			if(ed) {
				ed.setContent(content);
				return;
			}
		}

		if(typeof Joomla !== 'undefined' && Joomla.editors && Joomla.editors.instances && Joomla.editors.instances[editorId]) {
			Joomla.editors.instances[editorId].setValue(content);
			return;
		}

		var ta = document.getElementById(editorId);
		if(ta) {
			if(ta.hikaCM) {
				ta.hikaCM.setValue(content);
				return;
			}
			var cmWrapper = ta.nextElementSibling;
			if(cmWrapper && cmWrapper.classList && cmWrapper.classList.contains('CodeMirror') && cmWrapper.CodeMirror) {
				cmWrapper.CodeMirror.setValue(content);
				return;
			}
			ta.value = content;
		}
	},

	/**
	 * Main generate action — AJAX call to the backend.
	 */
	generate: function(inst) {
		var self = this;
		var cfg = inst.config;
		var textarea = inst.container.querySelector('.hikashop_ai_instructions');
		var instructions = textarea ? textarea.value.trim() : '';

		if(!instructions && cfg.type !== 'translation') {
			textarea.focus();
			return;
		}

		var currentContent = self.getEditorContent(cfg.editorId);
		inst.previousContent = currentContent;

		self.setLoading(inst, true);
		self.hideResult(inst);
		self.hideError(inst);

		var data = 'type=' + encodeURIComponent(cfg.type)
			+ '&instructions=' + encodeURIComponent(instructions)
			+ '&current_content=' + encodeURIComponent(currentContent)
			+ '&product_id=' + encodeURIComponent(cfg.context.product_id || 0)
			+ '&language_id=' + encodeURIComponent(cfg.context.language_id || 0)
			+ '&field=' + encodeURIComponent(cfg.context.field || '')
			+ '&view_id=' + encodeURIComponent(cfg.context.view_id || '');

		hikashop.xRequest(cfg.ajaxUrl, {mode: 'POST', data: data}, function(xhr) {
			self.setLoading(inst, false);
			try {
				var resp = JSON.parse(xhr.responseText);
			} catch(e) {
				self.showError(inst, 'Invalid response from server.');
				return;
			}

			if(resp.error) {
				self.showError(inst, resp.error);
				return;
			}

			inst.generatedContent = resp.result || '';

			if(cfg.previewMode === 'diff' && resp.diff_html) {
				self.showDiff(inst, resp.diff_html);
			} else {
				self.showPreview(inst, resp.result);
			}
		}, function(xhr) {
			self.setLoading(inst, false);
			self.showError(inst, 'Request failed (HTTP ' + xhr.status + ').');
		});
	},

	/**
	 * Show a preview of the generated content (description / translation mode).
	 */
	showPreview: function(inst, html) {
		var resultDiv = inst.container.querySelector('.hikashop_ai_result');
		var previewDiv = inst.container.querySelector('.hikashop_ai_preview');
		if(!resultDiv || !previewDiv)
			return;

		if(inst.config.type === 'view') {
			previewDiv.textContent = html;
		} else {
			previewDiv.innerHTML = html;
		}

		resultDiv.classList.add('active');
	},

	/**
	 * Show a diff table (view override mode).
	 */
	showDiff: function(inst, diffHtml) {
		var resultDiv = inst.container.querySelector('.hikashop_ai_result');
		var previewDiv = inst.container.querySelector('.hikashop_ai_preview');
		if(!resultDiv || !previewDiv)
			return;

		previewDiv.innerHTML = diffHtml;
		previewDiv.style.maxHeight = '500px';
		resultDiv.classList.add('active');
	},

	/**
	 * Apply the generated content to the editor.
	 */
	apply: function(inst) {
		if(inst.generatedContent) {
			this.setEditorContent(inst.config.editorId, inst.generatedContent);
		}
		this.hideResult(inst);
	},

	/**
	 * Discard the generated content.
	 */
	discard: function(inst) {
		inst.generatedContent = '';
		this.hideResult(inst);
	},

	setLoading: function(inst, loading) {
		var btn = inst.container.querySelector('.hikashop_ai_generate_btn');
		var label = inst.container.querySelector('.hikashop_ai_btn_label');
		var spinner = inst.container.querySelector('.hikashop_ai_spinner');
		if(btn) btn.disabled = loading;
		if(label) label.style.display = loading ? 'none' : '';
		if(spinner) spinner.classList.toggle('active', loading);
	},

	hideResult: function(inst) {
		var el = inst.container.querySelector('.hikashop_ai_result');
		if(el) el.classList.remove('active');
	},

	showError: function(inst, msg) {
		var el = inst.container.querySelector('.hikashop_ai_error');
		if(el) {
			el.textContent = msg;
			el.style.display = 'block';
		}
	},

	hideError: function(inst) {
		var el = inst.container.querySelector('.hikashop_ai_error');
		if(el) el.style.display = 'none';
	}
};
