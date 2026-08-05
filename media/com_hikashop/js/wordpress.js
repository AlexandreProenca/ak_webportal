/**
 * @package    HikaShop
 * @version    6.5.0
 * @author     hikashop.com
 * @copyright  (C) 2010-2026 HIKARI SOFTWARE. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
/**
 * HikaShop WordPress - Joomla API stubs
 * Provides the window.Joomla object that HikaShop's JS expects,
 * normally provided by the Joomla CMS framework.
 */
if(typeof Joomla === "undefined") {
	window.Joomla = {
		optionsStorage: {},
		getOptions: function(key, def) {
			return this.optionsStorage[key] || def || {};
		},
		loadOptions: function(options) {
			if(options) {
				for(var key in options) {
					this.optionsStorage[key] = options[key];
				}
			}
		},
		submitform: function(task, form, validate) {
			if(!form) form = document.getElementById("adminForm") || document.forms.adminForm;
			if(!form) return;
			if(task) {
				var taskField = form.querySelector("input[name=task]");
				if(taskField) taskField.value = task;
			}
			if(typeof form.submit === "function") form.submit();
		},
		submitbutton: function(task) { Joomla.submitform(task); },
		checkAll: function(checkbox) {
			var f = checkbox.form;
			if(!f) return;
			var c = 0, cb = f.querySelectorAll("input[type=checkbox][name^=cid]");
			for(var i = 0; i < cb.length; i++) {
				cb[i].checked = checkbox.checked;
				if(cb[i].checked) c++;
			}
			if(f.boxchecked) f.boxchecked.value = c;
		},
		isChecked: function(isitchecked, form) {
			if(!form) form = document.getElementById("adminForm") || document.forms.adminForm;
			if(!form || !form.boxchecked) return;
			form.boxchecked.value = parseInt(form.boxchecked.value) + (isitchecked ? 1 : -1);
		},
		tableOrdering: function(order, dir) {
			var form = document.getElementById("adminForm");
			if(!form) return;
			var filterOrder = form.querySelector("input[name=filter_order]");
			var filterDir = form.querySelector("input[name=filter_order_Dir]");
			if(filterOrder) filterOrder.value = order;
			if(filterDir) filterDir.value = (dir === "asc") ? "desc" : "asc";
			Joomla.submitform();
		},
		editors: {
			instances: new Proxy({}, {
				get: function(target, id) {
					if(id in target) return target[id];
					return {
						getValue: function() {
							if(typeof tinymce !== "undefined") {
								var ed = tinymce.get(id);
								if(ed) return ed.getContent();
							}
							var el = document.getElementById(id);
							return el ? el.value : "";
						},
						setValue: function(v) {
							if(typeof tinymce !== "undefined") {
								var ed = tinymce.get(id);
								if(ed) { ed.setContent(v); return; }
							}
							var el = document.getElementById(id);
							if(el) el.value = v;
						}
					};
				}
			})
		},
		Text: { strings: {}, _: function(key) { return this.strings[key] || key; } },
		JText: { _: function(key) { return Joomla.Text._(key); } },
		renderMessages: function(messages) {
			if(!messages) return;
			for(var type in messages) {
				var msgs = messages[type];
				for(var i = 0; i < msgs.length; i++) {
					alert(msgs[i]);
				}
			}
		}
	};
}

// Suppress TinyMCE setBaseAndExtent error that occurs when the editor
// initializes inside a buffered or tabbed context where the iframe
// selection API is not yet available.
window.addEventListener("error", function(e) {
	if(e && e.message && e.message.indexOf("setBaseAndExtent") !== -1) {
		e.preventDefault();
		return true;
	}
});
