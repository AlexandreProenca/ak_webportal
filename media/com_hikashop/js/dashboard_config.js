/**
 * @package    HikaShop
 * @version    6.5.0
 * @author     hikashop.com
 * @copyright  (C) 2010-2026 HIKARI SOFTWARE. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
/**
 * HikaShop Dashboard Configuration
 */
var hikashop_dashboard = {
	layout: [],

	widthMap: {
		'hkc-sm-12': '100%',
		'hkc-lg-8 hkc-sm-12': '66%',
		'hkc-lg-6 hkc-sm-12': '50%',
		'hkc-sm-6': '50%',
		'hkc-lg-4 hkc-sm-12': '33%',
		'hkc-lg-3 hkc-sm-6': '25%',
		'hkc-lg-2 hkc-sm-3': '16%'
	},

	sizes: [
		'hkc-sm-12', // 100%
		'hkc-lg-8 hkc-sm-12', // 66%
		'hkc-lg-6 hkc-sm-12', // 50% (desktop)
		'hkc-lg-4 hkc-sm-12', // 33%

		'hkc-lg-3 hkc-sm-6', // 25%
		'hkc-lg-2 hkc-sm-3' // 16%
	],

	resizeState: null,


	init: function () {
		if (window.hikashop_dashboard_data && window.hikashop_dashboard_data.layout) {
			this.layout = window.hikashop_dashboard_data.layout;
		}
		this.render();

		// Bind handlers to "this" so they can be added/removed properly
		this.resizeMoveHandler = this.resizeMove.bind(this);
		this.resizeEndHandler = this.resizeEnd.bind(this);

		// Normalize layout from DOM to satisfy checks
		this.layout = this.serialize();
		this.initialLayout = JSON.stringify(this.layout);

		this.initSortable();
		this.checkChanges();

		// Hook into Joomla save
		if (typeof Joomla !== 'undefined') {
			var originalSubmitbutton = Joomla.submitbutton;
			Joomla.submitbutton = function (task) {
				if (task == 'save' || task == 'dashboard.save' || task == 'apply') {
					var input = document.getElementById('hikashop_dashboard_layout_input');
					if (input) {
						input.value = JSON.stringify(hikashop_dashboard.serialize());
					}
				}
				if (originalSubmitbutton && typeof originalSubmitbutton === 'function') {
					originalSubmitbutton(task);
				} else {
					Joomla.submitform(task, document.getElementById('adminForm'));
				}
			};
		}
	},

	checkChanges: function () {
		var currentLayout = JSON.stringify(this.serialize());
		var btn = document.getElementById('hikashop_dashboard_reset_btn');
		if (btn) {
			if (currentLayout !== this.initialLayout) {
				btn.style.display = 'inline-block';
			} else {
				btn.style.display = 'none';
			}
		}
	},

	initSortable: function () {
		var self = this;

		// Main Dashboard Layout Sortable (Rows)
		new Sortable(document.getElementById('dashboard-layout'), {
			animation: 150,
			handle: '.row-handle',
			filter: '.hk-move-btn',
			preventOnFilter: false,
			onSort: function (evt) {
				self.layout = self.serialize();
				self.checkChanges();
			}
		});

		// Available widgets list
		new Sortable(document.getElementById('available-widgets'), {
			group: {
				name: 'shared',
				pull: 'clone',
				put: false
			},
			sort: false,
			animation: 150,
			handle: '.handle'
		});

		// Init Sortable for existing rows
		self.initRowsSortable();
	},

	initRowsSortable: function () {
		var self = this;
		var rows = document.querySelectorAll('.dashboard-row-content');
		rows.forEach(function (row) {
			new Sortable(row, {
				group: 'shared',
				animation: 150,
				handle: '.handle',
				filter: '.hk-move-btn',
				preventOnFilter: false,
				onAdd: function (evt) {
					// Widget added from sidebar
					var item = evt.item;

					// Check if it's a raw clone from sidebar (has widget-actions but not widget-controls)
					if (item.querySelector('.widget-actions') && !item.querySelector('.widget-controls')) {
						var key = item.dataset.id;
						var width = item.dataset.width;
						var label = item.querySelector('.widget-title').innerText;

						// Replace with full widget element
						var newEl = self.createWidgetElement({
							key: key,
							width: width,
							label: label
						});

						item.parentNode.replaceChild(newEl, item);
					}

					self.layout = self.serialize();
					self.checkChanges();
				},
				onSort: function (evt) {
					// Called when updated (order changed, item added/removed)
					self.layout = self.serialize();
					self.checkChanges();
				}
			});
		});
	},

	render: function () {
		var container = document.getElementById('dashboard-layout');
		container.innerHTML = '';

		if (!this.layout || this.layout.length === 0) {
			// Add at least one empty row
			this.addRow();
			return;
		}

		var self = this;
		this.layout.forEach(function (row, index) {
			self.renderRow(container, row, index);
		});

		// Re-init sortable for new elements
		this.initRowsSortable();
	},

	moveRow: function (el, direction) {
		var row = el.closest('.dashboard-row');
		var container = document.getElementById('dashboard-layout');
		var rows = Array.prototype.slice.call(container.querySelectorAll('.dashboard-row'));
		var idx = rows.indexOf(row);

		if (direction === 'up' && idx > 0) {
			container.insertBefore(row, rows[idx - 1]);
		} else if (direction === 'down' && idx < rows.length - 1) {
			var next = rows[idx + 1];
			if (next.nextSibling) container.insertBefore(row, next.nextSibling);
			else container.appendChild(row);
		} else return;

		this.layout = this.serialize();
		this.checkChanges();
		el.focus();
	},

	moveWidget: function (el, direction) {
		var widget = el.closest('.dashboard-widget-item');
		var row = widget.closest('.dashboard-row-content');
		var widgets = Array.prototype.slice.call(row.querySelectorAll('.dashboard-widget-item'));
		var idx = widgets.indexOf(widget);
		var allRows = Array.prototype.slice.call(document.querySelectorAll('.dashboard-row-content'));
		var rowIdx = allRows.indexOf(row);

		if (direction === 'left' && idx > 0) {
			row.insertBefore(widget, widgets[idx - 1]);
		} else if (direction === 'right' && idx < widgets.length - 1) {
			var next = widgets[idx + 1];
			if (next.nextSibling) row.insertBefore(widget, next.nextSibling);
			else row.appendChild(widget);
		} else if (direction === 'up' && rowIdx > 0) {
			allRows[rowIdx - 1].appendChild(widget);
		} else if (direction === 'down' && rowIdx < allRows.length - 1) {
			allRows[rowIdx + 1].appendChild(widget);
		} else return;

		this.layout = this.serialize();
		this.checkChanges();
		el.focus();
	},

	renderRow: function (container, rowData, index) {
		var rowHtml = document.createElement('div');
		rowHtml.className = 'dashboard-row';
		rowHtml.dataset.rowIndex = index;

		var txtStart = window.hikashop_dashboard_data.lang || {};

		var actions = `
			<div class="row-actions">
				<button type="button" class="hk-move-btn" onclick="hikashop_dashboard.moveRow(this, 'up');" title="${txtStart.move_up || 'Move up'}"><i class="fas fa-arrow-up" aria-hidden="true"></i></button>
				<button type="button" class="hk-move-btn" onclick="hikashop_dashboard.moveRow(this, 'down');" title="${txtStart.move_down || 'Move down'}"><i class="fas fa-arrow-down" aria-hidden="true"></i></button>
				<i class="fas fa-arrows-alt row-handle" title="${txtStart.move || 'Move'}"></i>
				<button type="button" class="hk-move-btn" onclick="hikashop_dashboard.removeRow(this);" title="${txtStart.delete || 'Delete'}" style="color: red;"><i class="fas fa-times" aria-hidden="true"></i></button>
			</div>
		`;

		var contentHtml = document.createElement('div');
		contentHtml.className = 'dashboard-row-content hk-row-fluid';
		// contentHtml.id = 'row-' + index;

		if (rowData.widgets) {
			var self = this;
			rowData.widgets.forEach(function (widget) {
				var widgetEl = self.createWidgetElement(widget);
				contentHtml.appendChild(widgetEl);
			});
		}

		rowHtml.innerHTML = actions;
		rowHtml.appendChild(contentHtml);
		container.appendChild(rowHtml);
	},

	createWidgetElement: function (widgetData) {
		var el = document.createElement('div');
		el.className = 'dashboard-widget-item ' + (widgetData.width || 'hkc-sm-12');
		el.dataset.id = widgetData.key;
		el.dataset.width = widgetData.width || 'hkc-sm-12';
		// Store params in dataset or a global cache?
		// Better stash in a global cache keyed by something unique? 
		// Or just stick to the data model.
		// For simplicity, we trust the `this.layout` model, BUT drag and drop mess up the model sync if we don't rebuild.

		// We'll store stringified params in a data attribute for retrieval during rebuilding
		if (widgetData.params) {
			el.dataset.params = JSON.stringify(widgetData.params);
		}

		var label = (widgetData.params && widgetData.params.custom_title) ? widgetData.params.custom_title : (widgetData.label || this.getLabel(widgetData.key));
		var widthClass = widgetData.width || 'hkc-sm-12';
		var widthPercent = this.widthMap[widthClass] || '100%';

		var txt = window.hikashop_dashboard_data.lang || {};
		var html = `
			<div class="widget-header">
				<div class="widget-title-row">
					<span class="widget-title">${label} <span class="widget-width-display">(${widthPercent})</span></span>
				</div>
				<div class="widget-controls">
					<button type="button" class="hk-move-btn" onclick="hikashop_dashboard.settings(this);" title="${txt.settings || 'Settings'}"><i class="fas fa-cog" aria-hidden="true"></i></button>
					<button type="button" class="hk-move-btn" onmousedown="hikashop_dashboard.startResize(event, this);" onkeydown="if(event.keyCode===13||event.keyCode===32){event.preventDefault();hikashop_dashboard.cycleWidth(this);}" title="${txt.resize || 'Resize'}" style="cursor: ew-resize;"><i class="fas fa-arrows-alt-h" aria-hidden="true"></i></button>
					<button type="button" class="hk-move-btn" onclick="hikashop_dashboard.moveWidget(this, 'left');" title="${txt.move_prev || 'Move left'}"><i class="fas fa-arrow-left" aria-hidden="true"></i></button>
					<button type="button" class="hk-move-btn" onclick="hikashop_dashboard.moveWidget(this, 'right');" title="${txt.move_next || 'Move right'}"><i class="fas fa-arrow-right" aria-hidden="true"></i></button>
					<button type="button" class="hk-move-btn" onclick="hikashop_dashboard.moveWidget(this, 'up');" title="${txt.move_up || 'Move to previous row'}"><i class="fas fa-arrow-up" aria-hidden="true"></i></button>
					<button type="button" class="hk-move-btn" onclick="hikashop_dashboard.moveWidget(this, 'down');" title="${txt.move_down || 'Move to next row'}"><i class="fas fa-arrow-down" aria-hidden="true"></i></button>
					<button type="button" class="hk-move-btn" onclick="hikashop_dashboard.removeWidget(this);" title="${txt.delete || 'Delete'}" style="color: red;"><i class="fas fa-times" aria-hidden="true"></i></button>
					<i class="fas fa-arrows-alt handle" title="${txt.move || 'Move'}"></i>
				</div>
			</div>
		`;
		el.innerHTML = html;
		return el;
	},

	getLabel: function (key) {
		// Try to find label in available widgets
		var available = document.querySelector('#available-widgets .dashboard-widget-item[data-id="' + key + '"] .widget-title');
		return available ? available.innerText : key;
	},

	addRow: function () {
		var container = document.getElementById('dashboard-layout');
		this.renderRow(container, { widgets: [] }, document.querySelectorAll('.dashboard-row').length);
		this.initRowsSortable();
		this.checkChanges();
	},

	removeRow: function (el) {
		el.closest('.dashboard-row').remove();
		this.layout = this.serialize(); // Update internal layout to check changes
		this.checkChanges();
	},

	removeWidget: function (el) {
		el.closest('.dashboard-widget-item').remove();
		this.layout = this.serialize(); // Update internal layout to check changes
		this.checkChanges();
	},

	cycleWidth: function (el) {
		var widget = el.closest('.dashboard-widget-item');
		var currentWidth = widget.dataset.width;

		var idx = this.sizes.indexOf(currentWidth);
		if (idx === -1) idx = 0;
		// Cycle
		var nextIdx = (idx + 1) % this.sizes.length;
		this.applyWidth(widget, nextIdx);
	},

	applyWidth: function (widget, idx) {
		if (idx < 0) idx = 0;
		if (idx >= this.sizes.length) idx = this.sizes.length - 1;

		var currentWidth = widget.dataset.width;
		var nextWidth = this.sizes[idx];

		if (currentWidth === nextWidth) return;

		// Remove old class
		widget.classList.remove.apply(widget.classList, currentWidth.split(' '));
		// Add new class
		widget.classList.add.apply(widget.classList, nextWidth.split(' '));

		// Update width display
		var widthDisplay = widget.querySelector('.widget-width-display');
		if (widthDisplay) {
			var pct = this.widthMap[nextWidth] || '100%';
			widthDisplay.innerText = '(' + pct + ')';
		}

		widget.dataset.width = nextWidth;
		this.layout = this.serialize(); // Update internal layout
		this.checkChanges();
	},

	startResize: function (e, el) {
		e.preventDefault(); // Prevent text selection
		var widget = el.closest('.dashboard-widget-item');
		var currentWidth = widget.dataset.width;
		var idx = this.sizes.indexOf(currentWidth);
		if (idx === -1) idx = 0;

		this.resizeState = {
			startX: e.clientX,
			startIdx: idx,
			currentIdx: idx,
			widget: widget,
			dragged: false
		};

		// Bind events to document
		document.addEventListener('mousemove', this.resizeMoveHandler);
		document.addEventListener('mouseup', this.resizeEndHandler);
	},

	resizeMove: function (e) {
		if (!this.resizeState) return;

		var deltaX = e.clientX - this.resizeState.startX;

		// Threshold to change size (e.g. 1/10 of window width)
		// On 1920px -> ~192px
		// On 1024px -> ~102px
		var threshold = Math.max(50, window.innerWidth / 10);
		var steps = Math.round(deltaX / threshold);

		// Drag Right (positive) -> Decrease Index (Bigger size) -> Because Array is 100%...16%
		// Drag Left (negative) -> Increase Index (Smaller size)

		var newIdx = this.resizeState.startIdx - steps;

		// Clamp
		if (newIdx < 0) newIdx = 0;
		if (newIdx >= this.sizes.length) newIdx = this.sizes.length - 1;

		if (newIdx !== this.resizeState.currentIdx) {
			this.resizeState.dragged = true;
			this.applyWidth(this.resizeState.widget, newIdx);
			this.resizeState.currentIdx = newIdx;
		}

		if (Math.abs(deltaX) > 5) {
			this.resizeState.dragged = true;
		}
	},

	resizeEnd: function (e) {
		if (!this.resizeState) return;

		document.removeEventListener('mousemove', this.resizeMoveHandler);
		document.removeEventListener('mouseup', this.resizeEndHandler);

		if (!this.resizeState.dragged) {
			// Treated as a click
			this.cycleWidth(this.resizeState.widget.querySelector('.fa-arrows-alt-h'));
		}

		this.resizeState = null;
	},

	serialize: function () {
		var layout = [];
		var rows = document.querySelectorAll('.dashboard-row');
		rows.forEach(function (row) {
			var rowData = { widgets: [] };
			var widgets = row.querySelectorAll('.dashboard-widget-item');
			widgets.forEach(function (widget) {
				var params = null;
				if (widget.dataset.params) {
					try {
						params = JSON.parse(widget.dataset.params);
					} catch (e) { }
				}

				rowData.widgets.push({
					key: widget.dataset.id,
					width: widget.dataset.width,
					params: params
				});
			});
			if (rowData.widgets.length > 0) {
				layout.push(rowData);
			}
		});
		return layout;
	},


	checkChanges: function () {
		var input = document.getElementById('hikashop_dashboard_layout_input');
		if (input) {
			input.value = JSON.stringify(this.layout);
		}
	},

	reset: function () {
		this.loadPreset('current', true);
	},

	// We need to define the saveSettings function globally or attach it to the window 
	// because the modal iframe will call it or we need a way to handle the form submit.
	// HikaShop modals often submit to an iframe. 
	// But here we want to capture the form data and update the JS model, NOT submit to the server directly (unless we save the config).

	// Actually, the settings view returns a FORM. 
	// If we use hikashop.openBox, it opens an iframe. 
	// The form inside the iframe will submit to the controller.
	// But we want to INTERCEPT that save to update our local JS model.

	// APPROACH: 
	// We'll use the Vex-specific openBox_vex logic if possible or just rely on openBox to handle it.
	// User said "use window.hikashop.openBox to load a modal".
	// And "use vex".

	// If we look at hikashop.js openBox implementation:
	// It checks data-hk-popup attribute.
	// If we want Vex, we should use data-hk-popup="vex".

	settings: function (el) {
		var widget = el.closest('.dashboard-widget-item');
		var key = widget.dataset.id;
		var params = widget.dataset.params || '{}';

		window.currentWidgetSettings = widget; // Keep reference to update it after save

		// Optimize params for URL to avoid GET length limits (especially for HTML templates)
		var safeParams = {};
		var fullParams = {};
		try {
			fullParams = JSON.parse(params);
			// Shallow copy
			for (var k in fullParams) safeParams[k] = fullParams[k];

			// Remove potentially large fields from URL params
			delete safeParams.prefix;
			delete safeParams.dynamic;
			delete safeParams.suffix;
		} catch (e) {
			safeParams = {};
		}

		var url = window.hikashop_dashboard_data.settingsUrl + '&widget=' + key + '&params=' + encodeURIComponent(JSON.stringify(safeParams));

		// Create a temporary link element to trigger openBox
		var link = document.createElement('a');
		link.href = url;
		// Add data-vex attribute for configuration (dimensions can be adjusted)
		link.setAttribute('data-vex', '{"x":800, "y":600}');
		link.setAttribute('data-hk-popup', 'vex');


		if (window.hikashop && window.hikashop.openBox) {
			window.hikashop.openBox(link, url);
		} else {
			// Fallback
			window.open(url, 'settings', 'width=800,height=600');
		}
	},

	saveSettings: function (data) {
		console.log('HIKA DEBUG: saveSettings called with:', data);
		if (window.currentWidgetSettings) {
			var widget = window.currentWidgetSettings;


			// Update params in dataset
			var currentParams = {};
			try {
				currentParams = JSON.parse(widget.dataset.params || '{}');
			} catch (e) { }

			// Merge new data
			for (var k in data) {
				currentParams[k] = data[k];
			}

			widget.dataset.params = JSON.stringify(currentParams);
			console.log('HIKA DEBUG: saveSettings merged params:', currentParams);
			console.log('HIKA DEBUG: saveSettings widget dataset:', widget.dataset.params);


			// Re-render widget header if title changed
			// Actually simplest is to rebuild the widget element completely
			// But we need to keep its width and position
			// Let's just update the title in DOM for now
			if (data.custom_title) {
				var titleEl = widget.querySelector('.widget-title');
				if (titleEl) {
					// We need to preserve the width display
					var widthDisplay = widget.querySelector('.widget-width-display');
					var widthText = widthDisplay ? widthDisplay.outerHTML : '';
					titleEl.innerHTML = data.custom_title + ' ' + widthText;
				}
			}

			// Or better, recreate the element to rely on createWidgetElement logic (which handles custom_title preference)
			var newWidget = this.createWidgetElement({
				key: widget.dataset.id,
				width: widget.dataset.width,
				params: currentParams,
				label: null // Force re-eval of label
			});
			widget.parentNode.replaceChild(newWidget, widget);

			// Update layout model
			this.layout = this.serialize();
			this.checkChanges();

			if (window.hikashop && window.hikashop.closeBox) {
				window.hikashop.closeBox();
			}
		}
	},

	export: function () {
		var layout = this.serialize();
		var json = JSON.stringify(layout);
		var blob = new Blob([json], { type: "application/json" });
		var url = URL.createObjectURL(blob);

		var a = document.createElement('a');
		a.href = url;
		a.download = "hikashop_dashboard_layout.json";
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	},

	import: function () {
		var input = document.createElement('input');
		input.type = 'file';
		input.accept = '.json';
		var self = this;
		input.onchange = function (e) {
			var file = e.target.files[0];
			if (!file) return;

			var reader = new FileReader();
			reader.onload = function (e) {
				try {
					var content = e.target.result;
					var layout = JSON.parse(content);
					if (layout && Array.isArray(layout)) {
						self.layout = layout;
						self.render();
						self.checkChanges();
						alert('Import successful');
					} else {
						alert('Invalid layout file');
					}
				} catch (e) {
					alert('Error parsing JSON: ' + e.message);
				}
			};
			reader.readAsText(file);
		};
		input.click();
	},

	loadPreset: function (preset, skipConfirm) {
		if (!preset) return;

		var url = 'index.php?option=com_hikashop&ctrl=dashboard&task=loadPreset&tmpl=component&name=' + preset;
		var self = this;

		var xhr = new XMLHttpRequest();
		xhr.open('GET', url, true);
		xhr.onreadystatechange = function () {
			if (xhr.readyState == 4 && xhr.status == 200) {
				try {
					var layout = JSON.parse(xhr.responseText);
					if (layout && Array.isArray(layout)) {
						self.layout = layout;
						self.render();

						if (preset == 'current') {
							// Normalize structure for comparison
							var normalized = self.serialize();
							self.initialLayout = JSON.stringify(normalized);
						}
						self.checkChanges();
					} else {
						alert('Invalid preset data');
					}
				} catch (e) {
					alert('Error parsing preset');
				}
			}
		};
		xhr.send();
	}
};


if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', function () {
		hikashop_dashboard.init();
	});
} else {
	hikashop_dashboard.init();
}
