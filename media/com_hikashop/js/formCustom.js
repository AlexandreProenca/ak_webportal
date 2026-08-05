/**
 * @package    HikaShop
 * @version    6.5.0
 * @author     hikashop.com
 * @copyright  (C) 2010-2026 HIKARI SOFTWARE. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
(function() {
var formCustom = {
	defaultOptions: {
		mainArea: '#hikashop_product_backend_page_edition .hk-container-fluid',
		type: 'product',
		handle: '.hikashop_product_part_title',
		namePrefix: 'hikashop_product_edit_',
		elements: '.hikashop_product_block:not(.hikashop_product_new_block)',
		fieldsElements: '.hika_options',
		labels: 'dd',
		labelPrefix: 'hikashop_product_',
		fields: true,
		blocks: true,
		skipEmpty: false,
		index: 0,
		customize: 1,
		hide: 1,
		template: '<div class="hkc-xl-4 hkc-lg-6 hikashop_product_block hikashop_product_edit_{NAMEKEY}"><div><div class="hikashop_product_part_title hikashop_product_edit_{NAMEKEY}_title"><span class="hikashop_tile_title">{TITLE}</span><a href="#" onclick="window.formCustom.removeBlock(\'{NAMEKEY}\',{KEY}); return false;" title="Remove block" class="hikabtn hikabtn-danger btn-small block-remove-btn" style="display:inline;"><i class="fa fa-trash"></i></a><button type="button" onclick="blockWidth(\'{NAMEKEY}\', \'{DELAY}\'); return false;" class="hikashop_block_width hikabtn hikabtn-primary" style="display:inline-block;" data-toggle="hk-tooltip" title="{WIDTHTIP}"><i class="fas fa-chevron-left" aria-hidden="true"></i><i class="fas fa-chevron-right" aria-hidden="true"></i><span class="element-invisible">{WIDTHTIP}</span></button><button type="button" onclick="blockCols(\'{NAMEKEY}\', \'{DELAY}\'); return false;" class="hikashop_block_cols hikabtn hikabtn-primary" style="display:inline-block;" data-toggle="hk-tooltip" title="{COLSTIP}"><i class="fas fa-columns" aria-hidden="true"></i><span class="element-invisible">{COLSTIP}</span></button><a href="#" onclick="window.formCustom.toggleBlock(\'{NAMEKEY}\',{KEY}, true); return false;" title="{TOGGLETIP}" class="hikabtn hikabtn-primary btn-small block-hide-btn" style="display:inline;" data-toggle="hk-tooltip"><i class="fa fa-eye"></i></a></div><dl class="hika_options"></dl></div></div>',
	},
	options: [],
	initDragAndDrop: function(options) {
		for (var key in this.defaultOptions) {
			if (!options.hasOwnProperty(key)) {
				options[key] = this.defaultOptions[key];
			}
		}
		this.options.push(options);
		options.index = this.options.length - 1;

		if(options.fields) {
			if(options.customize) {
				var settings = document.querySelectorAll(options.mainArea+' '+options.fieldsElements);
				for(var i=0; i < settings.length; i++) {
					new Sortable(settings[i], {
						group: options.type+'_fields',
						filter: options.labels + ', .hk-move-btn',
						animation: 150,
						preventOnFilter: false,
						emptyInsertThreshold: 20,
						// Element dragging started
						onChoose: function (/**Event*/evt) {
							evt.oldIndex;  // element index within parent
							var active_parent = document.querySelector('.hikashop_customize_area');
							active_parent.classList.add('hikashop_customize_area_options');
						},
						// Element is unchosen
						onUnchoose: function(/**Event*/evt) {
							// same properties as onEnd
							var active_parent = document.querySelector('.hikashop_customize_area');
							active_parent.classList.remove('hikashop_customize_area_options');
						},
						onEnd: function (evt) {
							// Remove class parent :

							if(evt.item.nodeName == 'DT') {
								var corresponding = document.querySelector(options.mainArea+' '+'dd.'+evt.item.classList);
								if(evt.item.nextSibling && evt.item.nextSibling.nodeName == 'DD') {
									evt.item.parentNode.insertBefore(evt.item.nextSibling, evt.item);
								}
								evt.item.parentNode.insertBefore(corresponding, evt.item.nextSibling);
							}
							window.formCustom.setFieldsOrder(options);
						},
					});
				}
			}
			// reorder the fields and create extra blocks on page load
			this.sortFields(options);

			// refresh the input with the structure, just in case
			if(options.customize) {
				this.setFieldsOrder(options);
				this.addFieldMoveHandles(options);
			}
		}

		if(options.blocks) {
			if(options.customize) {
				var list = document.querySelector(options.mainArea);
				new Sortable(list, {
					group: options.type+'_shared',
					animation: 150,
					handle: options.handle,
					filter: '.hikashop_block_width, .hikashop_block_cols, .block-hide-btn, .block-remove-btn, .hk-move-btn',
					preventOnFilter: false,
					forceAutoScrollFallback: true,
					forceFallback : true,
					// Element dragging started
					onChoose: function (/**Event*/evt) {
						evt.oldIndex;  // element index within parent
						var active_parent = document.querySelector('.hikashop_customize_area');
						active_parent.classList.add('hikashop_customize_area_blocks');
						// Save editors and convert CSS order to DOM order before Sortable captures positions
						var mainArea = document.querySelector(options.mainArea);
						window.formCustom._dragSavedEditors = window.formCustom.saveEditors(mainArea);
						window.formCustom.applyOrderToDOM(options);
					},
					// Element is unchosen
					onUnchoose: function(/**Event*/evt) {
						// same properties as onEnd
						var active_parent = document.querySelector('.hikashop_customize_area');
						active_parent.classList.remove('hikashop_customize_area_blocks');
						// Restore editors if no drag occurred
						if(window.formCustom._dragSavedEditors) {
							window.formCustom.restoreEditors(window.formCustom._dragSavedEditors);
							window.formCustom._dragSavedEditors = null;
						}
					},
					onEnd: function (evt) {
						// Restore editors after drag
						if(window.formCustom._dragSavedEditors) {
							window.formCustom.restoreEditors(window.formCustom._dragSavedEditors);
							window.formCustom._dragSavedEditors = null;
						}
						window.formCustom.setBlocksOrder(options);
					},
				});
			}

			// sort the blocks on page load
			this.initBlocks(options);
		}
		return options.index;
	},
	setFieldsOrder: function(options) {
		var areas = document.querySelectorAll(options.mainArea+' '+options.elements);
		var structure = [];
		for (var i = 0; i < areas.length; i++) {
			var classList = areas[i].className.split(/\s+/);

			var block = {name : '', title : '', hide : 0, fields: []};
			for (var j = 0; j < classList.length; j++) {
				if(classList[j].startsWith(options.namePrefix)) {
					block.name = classList[j].substring(options.namePrefix.length);
				}

				if(classList[j] == 'hikashop_hide_block') {
					block.hide = 1;
				}
			}
			if(block.name == '')
				continue;

			var titleEl = areas[i].querySelector(options.handle);
			if(titleEl) {
				// The title bar (.hikashop_product_part_title) contains the
				// title text alongside the block action buttons (width, cols,
				// hide-toggle, remove). Each button carries an accessibility
				// label inside <span class="element-invisible">...</span>.
				// Reading textContent on the whole bar concatenated those
				// labels onto the title every save, so each save round
				// appended another "Modifier la largeur du blocModifier le
				// nombre de colonnes" copy to the saved title; after a few
				// saves the original title was buried under dozens of those
				// copies. Read the inner .hikashop_tile_title span only;
				// fall back to a clone of the bar with all buttons stripped
				// for legacy blocks created before the inner span was
				// introduced.
				var titleSpan = titleEl.querySelector('.hikashop_tile_title');
				if(titleSpan) {
					block.title = titleSpan.textContent;
				} else {
					var clone = titleEl.cloneNode(true);
					var btns = clone.querySelectorAll('button, a');
					for (var b = 0; b < btns.length; b++) {
						btns[b].parentNode.removeChild(btns[b]);
					}
					block.title = clone.textContent;
				}
			}

			var labels = areas[i].querySelectorAll(options.fieldsElements+' '+options.labels);
			for (var j = 0; j < labels.length; j++) {
				var field = '';
				var classList = labels[j].className.split(/\s+/);
				for (var k = 0; k < classList.length; k++) {
					if(classList[k].startsWith(options.labelPrefix)) {
						field = classList[k].substring(options.labelPrefix.length);
						break;
					}
				}
				if(field == '')
					continue;
				block.fields.push(field);
			}
			structure.push(block);
		}
		document.getElementById(options.type+'_areas_fields').value =  JSON.stringify(structure);
	},
	sortFields: function(options) {
		var structureInput = document.getElementById(options.type+'_areas_fields');
		if(!structureInput || structureInput.value == '')
			return;
		var resetBtn = document.querySelector('.reset_block_button');
		if(resetBtn)
			resetBtn.style.display = 'block';

		var structure = null;
		try {
			structure = JSON.parse(structureInput.value);
		} catch(e) {
			console.err(e);
			return;
		}
		var areas = document.querySelectorAll(options.mainArea+' '+options.elements);
		var mainArea = document.querySelector(options.mainArea);
		// loop on the areas in the structure
		for (var i = 0; i < structure.length; i++) {
			var areaData = structure[i];
			var area = null;
			// search the matching area in the DOM
			for (var j = 0; j < areas.length; j++) {
				var classList = areas[j].className.split(/\s+/);
				var name = '';
				for (var k = 0; k < classList.length; k++) {
					if(classList[k].startsWith(options.namePrefix)) {
						name = classList[k].substring(options.namePrefix.length);
						break;
					}
				}
				if(name == '')
					continue;
				if(name == areaData.name) {
					// match
					area = areas[j];
					break;
				}
			}
			if(!area) {
				// create a new area in the DOM
				area = this.addBlock(options, areaData, mainArea);
			}

			if(!area) {
				continue;
			}

			if(areaData.hide && options.hide) {
				this.toggleBlock(areaData.name, options.index, false);
			}

			var optionsList = area.querySelector(options.fieldsElements);

			// reorder the fields in the area
			var found = 0;
			for(var j = 0; j < areaData.fields.length; j++) {
				var field = areaData.fields[j];
				els = mainArea.querySelectorAll(options.fieldsElements+' .'+options.labelPrefix+field);
				if(els && els.length) {
					found += els.length;
					for(var k = 0; k < els.length; k++) {
						if(els[k].parentNode.style.display == 'none') {
							optionsList.appendChild(els[k].parentNode);
						} else {
							optionsList.appendChild(els[k]);
						}
					}
				}
			}
			if(areaData.fields.length > 0 && found == 0 && options.skipEmpty) {
				this.removeBlock(areaData.name, options.index);
			}

		}
	},
	reset: function(key) {
		if(!this.options[key]) {
			console.log('options key '+ key + ' is invalid. Please check your new block HTML');
			return;
		}
		var input = document.getElementById(this.options[key].type+'_reset_custom');
		input.value = 1;
		input.form.querySelector('input[name="task"]').value = 'apply';
		input.form.submit();
	},
	removeBlock: function(name, key) {
		if(!this.options[key]) {
			console.log('options key '+ key + ' is invalid. Please check your new block HTML');
			return;
		}

		var block = document.querySelector(this.options[key].mainArea+' '+this.options[key].elements+'.'+this.options[key].namePrefix+name);
		if(!block) {
			console.log('Could not find block with name '+name);
			return;
		}
		block.remove();
		/*
		var fields = block.querySelectorAll(this.options[key].fieldsElements);
		var blocks = document.querySelectorAll(this.options[key].mainArea+' '+this.options[key].elements+ ' '+this.options[key].fieldsElements);
		for (var i = 0; i < fields.length; i++) {
			while(fields[i].children.length) {
				blocks[blocks.length-1].appendChild(fields[i].children[0]);
			}
		}
		*/
		this.setFieldsOrder(this.options[key]);
	},
	addNewBlock: function(inputId, key) {
		var input = document.getElementById(inputId);
		if(!input) {
			console.log(inputId + ' not found. Please check your new block HTML');
			return;
		}
		if(input.value == '') {
			alert('Please enter a title first');
			return;
		}
		var name = input.value.replace(/[\u0250-\ue007]/g, '').replace(/ /g, '_');
		if(name == '') {
			alert('Please use latin letters');
			return;
		}

		if(!this.options[key]) {
			console.log('options key '+ key + ' is invalid. Please check your new block HTML');
			return;
		}

		var elementsOrder = document.getElementById(this.options[key].type+'_areas_order').value.split(',');
		if(elementsOrder.length && elementsOrder.includes(name)) {
			alert('The title ' + input.value + ' cannot be used. Please enter another one');
			return;
		}

		var parent = document.querySelector(this.options[key].mainArea);

		var data = {title : input.value, name : name};

		var block = this.addBlock(this.options[key], data, parent);

		this.addHideBtn(block, name, this.options[key]);

		input.value = '';
	},
	addBlock: function(options, data, parent) {
		var dv = document.createElement("div");
		var html = options.template.replace(/{TITLE}/g, data.title).replace(/{NAMEKEY}/g, data.name).replace(/{KEY}/g, options.index).replace(/{DELAY}/g, options.delay || '').replace(/{WIDTHTIP}/g, options.widthTip || '').replace(/{COLSTIP}/g, options.colsTip || '').replace(/{TOGGLETIP}/g, options.toggleTip || '');
		if(!options.customize || !options.hide) {
			html.replace(/display:inline;/g, 'display:none;');
		}
		dv.innerHTML = html;
		var block = dv.childNodes[0];
		parent.appendChild(block);
		var area = block.querySelector(options.fieldsElements);

		// make its fields area drag&drop
		if(options.customize) {
			new Sortable(area, {
				group: options.type+'_fields',
				filter: options.labels + ', .hk-move-btn',
				animation: 150,
				preventOnFilter: false,
				emptyInsertThreshold: 20,
				onEnd: function (evt) {
					if(evt.item.nodeName == 'DT') {
						var corresponding = document.querySelector(options.mainArea+' '+'dd.'+evt.item.classList);
						if(evt.item.nextSibling && evt.item.nextSibling.nodeName == 'DD') {
							evt.item.parentNode.insertBefore(evt.item.nextSibling, evt.item);
						}
						evt.item.parentNode.insertBefore(corresponding, evt.item.nextSibling);
					}
					window.formCustom.setFieldsOrder(options);
				},
			});
		}
		return block;
	},
	initBlocks: function(options) {
		if(!this.sortBlocks(options)) {
			this.setBlocksOrder(options);
		}
	},
	setBlocksOrder: function(options) {
		var newElements = document.querySelectorAll(options.mainArea+' '+options.elements);
		var elementsOrder = [];
		for (var i = 0; i < newElements.length; i++) {
			var classList = newElements[i].className.split(/\s+/);
			var name = '';
			for (var j = 0; j < classList.length; j++) {
				if(classList[j].startsWith(options.namePrefix)) {
					name = classList[j].substring(options.namePrefix.length);
					break;
				}
			}
			if(name == '' || elementsOrder.includes(name))
				continue;
			elementsOrder.push(name);
		}
		document.getElementById(options.type+'_areas_order').value =  elementsOrder.join(',');
	},
	toggleBlock: function (name, key, save) {
		if(!this.options[key]) {
			console.log('options key '+ key + ' is invalid. Please check your new block HTML');
			return;
		}

		var block = document.querySelector(this.options[key].mainArea+' '+this.options[key].elements+'.'+this.options[key].namePrefix+name);
		if(!block) {
			console.log('Could not find block with name '+name);
			return;
		}

		if(block.classList.contains('hikashop_hide_block')) {
			block.classList.remove('hikashop_hide_block');
			if(this.options[key].customize)
				block.classList.remove('hikashop_customize_block');
		} else {
			block.classList.add('hikashop_hide_block');
			if(this.options[key].customize)
				block.classList.add('hikashop_customize_block');
		}

		if(save)
			this.setFieldsOrder(this.options[key]);
	},
	sortBlocks: function(options) {
		var elementsOrder = document.getElementById(options.type+'_areas_order').value.split(',');
		if(elementsOrder.length <=1)
			return false;

		// Give all blocks a high default order so any new/unknown blocks appear at the end
		var allBlocks = document.querySelectorAll(options.mainArea+' '+options.elements);
		for(var i = 0; i < allBlocks.length; i++) {
			allBlocks[i].style.order = elementsOrder.length + i;
		}

		// Non-block children default to order 0 and would be grid-placed right after the first
		// ordered block, punching holes in the first row; send them to the end instead
		var mainArea = document.querySelector(options.mainArea);
		if(mainArea) {
			for(var i = 0; i < mainArea.children.length; i++) {
				if(!mainArea.children[i].style.order)
					mainArea.children[i].style.order = 1000 + i;
			}
		}

		// Assign CSS order to known blocks - no DOM manipulation, editors stay intact
		for (var i = 0; i < elementsOrder.length; i++) {
			var src = document.querySelector(options.mainArea+' '+options.elements+'[data-id="' + elementsOrder[i]+'"]');
			if(!src) {
				src = document.querySelector(options.mainArea+' '+options.elements+'.'+ options.namePrefix + elementsOrder[i]);
				if(!src)
					continue;
			}

			this.addHideBtn(src, elementsOrder[i], options);
			this.addMoveButtons(src, elementsOrder[i], options);
			src.style.order = i;
		}

		return true;
	},
	moveBlock: function(name, key, direction) {
		if(!this.options[key]) return;
		var options = this.options[key];
		var mainArea = document.querySelector(options.mainArea);
		var blocks = Array.prototype.slice.call(mainArea.querySelectorAll(options.elements));

		// Sort by CSS order (visual order)
		blocks.sort(function(a, b) {
			return (parseInt(a.style.order) || 0) - (parseInt(b.style.order) || 0);
		});

		var currentIndex = -1;
		for(var i = 0; i < blocks.length; i++) {
			if(blocks[i].classList.contains(options.namePrefix + name)) {
				currentIndex = i;
				break;
			}
		}
		if(currentIndex === -1) return;

		var targetIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;
		if(targetIndex < 0 || targetIndex >= blocks.length) return;

		// Swap CSS order values
		var tmp = blocks[currentIndex].style.order;
		blocks[currentIndex].style.order = blocks[targetIndex].style.order;
		blocks[targetIndex].style.order = tmp;

		// Re-serialize based on CSS order
		blocks.sort(function(a, b) {
			return (parseInt(a.style.order) || 0) - (parseInt(b.style.order) || 0);
		});
		var elementsOrder = [];
		for(var i = 0; i < blocks.length; i++) {
			var classList = blocks[i].className.split(/\s+/);
			for(var j = 0; j < classList.length; j++) {
				if(classList[j].startsWith(options.namePrefix)) {
					var n = classList[j].substring(options.namePrefix.length);
					if(n && !elementsOrder.includes(n)) elementsOrder.push(n);
					break;
				}
			}
		}
		document.getElementById(options.type+'_areas_order').value = elementsOrder.join(',');
		this.setFieldsOrder(options);
	},
	moveField: function(dt, key, direction) {
		if(!this.options[key]) return;
		var options = this.options[key];
		var dl = dt.parentNode;
		var block = dl.closest(options.elements);
		var dd = dt.nextElementSibling;
		if(dd && dd.nodeName !== 'DD') dd = null;

		var dts = Array.prototype.slice.call(dl.querySelectorAll('dt'));
		var dtIdx = dts.indexOf(dt);

		if(direction === 'up') {
			if(dtIdx > 0) {
				var prevDt = dts[dtIdx - 1];
				dl.insertBefore(dt, prevDt);
				if(dd) dl.insertBefore(dd, prevDt);
			} else {
				// Move to previous block
				var blocks = Array.prototype.slice.call(document.querySelectorAll(options.mainArea + ' ' + options.elements));
				blocks.sort(function(a, b) { return (parseInt(a.style.order) || 0) - (parseInt(b.style.order) || 0); });
				var blockIdx = blocks.indexOf(block);
				if(blockIdx <= 0) return;
				var prevDl = blocks[blockIdx - 1].querySelector(options.fieldsElements);
				if(!prevDl) return;
				prevDl.appendChild(dt);
				if(dd) prevDl.appendChild(dd);
			}
		} else if(direction === 'down') {
			if(dtIdx < dts.length - 1) {
				var nextDt = dts[dtIdx + 1];
				var nextDd = nextDt.nextElementSibling;
				var ref = (nextDd && nextDd.nodeName === 'DD') ? nextDd.nextSibling : nextDt.nextSibling;
				if(ref) {
					dl.insertBefore(dt, ref);
					if(dd) dl.insertBefore(dd, ref);
				} else {
					dl.appendChild(dt);
					if(dd) dl.appendChild(dd);
				}
			} else {
				// Move to next block
				var blocks = Array.prototype.slice.call(document.querySelectorAll(options.mainArea + ' ' + options.elements));
				blocks.sort(function(a, b) { return (parseInt(a.style.order) || 0) - (parseInt(b.style.order) || 0); });
				var blockIdx = blocks.indexOf(block);
				if(blockIdx >= blocks.length - 1) return;
				var nextDl = blocks[blockIdx + 1].querySelector(options.fieldsElements);
				if(!nextDl) return;
				if(nextDl.firstChild) {
					nextDl.insertBefore(dt, nextDl.firstChild);
					if(dd) {
						if(dt.nextSibling) nextDl.insertBefore(dd, dt.nextSibling);
						else nextDl.appendChild(dd);
					}
				} else {
					nextDl.appendChild(dt);
					if(dd) nextDl.appendChild(dd);
				}
			}
		}
		this.setFieldsOrder(options);
	},
	addMoveButtons: function(src, name, options) {
		if(!options.customize || src.querySelector('.hk-block-move-btn')) return;
		var div = src.querySelector(options.handle);
		if(!div) return;

		// Find or create a title span to position buttons around
		var titleSpan = div.querySelector('.hikashop_tile_title');
		if(!titleSpan) {
			// PHP-rendered blocks have raw text — wrap the first text node in a span
			for(var i = 0; i < div.childNodes.length; i++) {
				if(div.childNodes[i].nodeType === 3 && div.childNodes[i].textContent.trim()) {
					titleSpan = document.createElement('span');
					titleSpan.className = 'hikashop_tile_title';
					titleSpan.textContent = div.childNodes[i].textContent;
					div.replaceChild(titleSpan, div.childNodes[i]);
					break;
				}
			}
		}

		// Wrap right-side buttons (width, cols, hide, remove) in an abs-positioned container
		if(!div.querySelector('.hk-block-right-btns')) {
			var rightBtns = div.querySelectorAll('.hikashop_block_width, .hikashop_block_cols, .block-hide-btn, .block-remove-btn');
			if(rightBtns.length) {
				var container = document.createElement('div');
				container.className = 'hk-block-right-btns';
				for(var i = 0; i < rightBtns.length; i++) {
					container.appendChild(rightBtns[i]);
				}
				div.appendChild(container);
			}
		}

		var self = this;
		var upBtn = document.createElement('button');
		upBtn.type = 'button';
		upBtn.title = options.moveUpTip || 'Move up';
		upBtn.className = 'hk-move-btn hk-block-move-btn hk-block-move-up';
		upBtn.innerHTML = '<i class="fas fa-arrow-up" aria-hidden="true"></i>';
		upBtn.onclick = function() { self.moveBlock(name, options.index, 'up'); return false; };

		var downBtn = document.createElement('button');
		downBtn.type = 'button';
		downBtn.title = options.moveDownTip || 'Move down';
		downBtn.className = 'hk-move-btn hk-block-move-btn hk-block-move-down';
		downBtn.innerHTML = '<i class="fas fa-arrow-down" aria-hidden="true"></i>';
		downBtn.onclick = function() { self.moveBlock(name, options.index, 'down'); return false; };

		if(titleSpan) {
			div.insertBefore(upBtn, titleSpan);
			titleSpan.parentNode.insertBefore(downBtn, titleSpan.nextSibling);
		} else {
			div.appendChild(upBtn);
			div.appendChild(downBtn);
		}
	},
	addFieldMoveHandles: function(options) {
		if(!options.customize) return;
		var mainArea = document.querySelector(options.mainArea);
		var dts = mainArea.querySelectorAll(options.fieldsElements + ' dt');
		var self = this;
		for(var i = 0; i < dts.length; i++) {
			if(dts[i].querySelector('.hk-field-grip')) continue;
			(function(dt) {
				var grip = document.createElement('span');
				grip.className = 'hk-field-grip';
				grip.setAttribute('tabindex', '0');
				grip.setAttribute('role', 'button');
				grip.setAttribute('aria-label', (options.moveUpTip || 'Move') + ' — ' + dt.textContent.trim());
				grip.title = dt.textContent.trim() + ' — ↑↓';
				grip.innerHTML = '<i class="fas fa-grip-vertical" aria-hidden="true"></i>';
				grip.addEventListener('keydown', function(e) {
					if(e.keyCode === 38) { // Up
						e.preventDefault();
						self.moveField(dt, options.index, 'up');
						grip.focus();
					} else if(e.keyCode === 40) { // Down
						e.preventDefault();
						self.moveField(dt, options.index, 'down');
						grip.focus();
					}
				});
				dt.insertBefore(grip, dt.firstChild);
			})(dts[i]);
		}
	},
	addHideBtn: function (src, name, options) {
		if(options.customize && options.hide && !src.querySelector('.block-hide-btn')) {
			var div = src.querySelector('.hikashop_product_part_title');
			if(div) {
				var a = document.createElement('button');
				a.type = 'button';
				a.title = options.toggleTip || 'Toggle block';
				a.className = 'hikabtn hikabtn-primary btn-small block-hide-btn';
				a.style.display = 'inline';
				a.innerHTML = '<i class="fa fa-eye" aria-hidden="true"></i>';
				a.onclick = function() { window.formCustom.toggleBlock(name, options.index, true); return false; };
				div.appendChild(a);
			}
		}
	},
	applyOrderToDOM: function(options) {
		var mainArea = document.querySelector(options.mainArea);
		var blocks = Array.prototype.slice.call(mainArea.querySelectorAll(options.elements));
		// Sort by CSS order value
		blocks.sort(function(a, b) {
			return (parseInt(a.style.order) || 0) - (parseInt(b.style.order) || 0);
		});
		// Physically reorder DOM to match CSS order, then clear CSS order
		for(var i = 0; i < blocks.length; i++) {
			blocks[i].style.order = '';
			mainArea.appendChild(blocks[i]);
		}
	},
	saveEditors: function(container) {
		var saved = [];
		// TinyMCE (also covers JCE which is TinyMCE-based)
		if(window.tinymce) {
			var allEditors = tinymce.get();
			for(var i = allEditors.length - 1; i >= 0; i--) {
				var editor = allEditors[i];
				var el = editor.getElement();
				if(el && container.contains(el)) {
					editor.save();
					saved.push({type: 'tinymce', id: editor.id, settings: editor.settings});
					editor.remove();
				}
			}
		}
		// CKEditor 4 (covers ckeditor, jckeditor, artofeditor)
		if(window.CKEDITOR && CKEDITOR.instances) {
			for(var name in CKEDITOR.instances) {
				var editor = CKEDITOR.instances[name];
				if(editor.element && editor.element.$) {
					var el = editor.element.$;
					if(container.contains(el)) {
						editor.updateElement();
						saved.push({type: 'ckeditor', id: el.id, config: editor.config});
						editor.destroy();
					}
				}
			}
		}
		// CodeMirror
		var cmElements = container.querySelectorAll('.CodeMirror');
		for(var i = cmElements.length - 1; i >= 0; i--) {
			var cm = cmElements[i].CodeMirror;
			if(cm) {
				cm.save();
				var textarea = cm.getTextArea();
				var cmOptions = {};
				var optionKeys = ['mode','theme','lineNumbers','lineWrapping','readOnly','indentUnit','tabSize','matchBrackets','autoCloseBrackets','autoCloseTags','foldGutter','gutters','keyMap','extraKeys','direction'];
				for(var j = 0; j < optionKeys.length; j++) {
					try { cmOptions[optionKeys[j]] = cm.getOption(optionKeys[j]); } catch(e) {}
				}
				saved.push({type: 'codemirror', textareaId: textarea.id, options: cmOptions});
				cm.toTextArea();
			}
		}
		return saved;
	},
	restoreEditors: function(saved) {
		if(!saved || !saved.length)
			return;
		for(var i = 0; i < saved.length; i++) {
			var entry = saved[i];
			if(entry.type == 'tinymce' && window.tinymce) {
				tinymce.init(entry.settings);
			} else if(entry.type == 'ckeditor' && window.CKEDITOR) {
				CKEDITOR.replace(entry.id, entry.config);
			} else if(entry.type == 'codemirror' && window.CodeMirror) {
				var textarea = document.getElementById(entry.textareaId);
				if(textarea) {
					CodeMirror.fromTextArea(textarea, entry.options);
				}
			}
		}
	},
	swapNodes: function(n1, n2) {
		var p1 = n1.parentNode;
		var p2 = n2.parentNode;
		var i1, i2;

		if ( !p1 || !p2 || p1.isEqualNode(n2) || p2.isEqualNode(n1) ) return;

		for (var i = 0; i < p1.children.length; i++) {
			if (p1.children[i].isEqualNode(n1)) {
				i1 = i;
			}
		}
		for (var i = 0; i < p2.children.length; i++) {
			if (p2.children[i].isEqualNode(n2)) {
				i2 = i;
			}
		}

		if ( p1.isEqualNode(p2) && i1 < i2 ) {
			i2++;
		}
		p1.insertBefore(n2, p1.children[i1]);
		p2.insertBefore(n1, p2.children[i2]);
	}
};

window.formCustom = formCustom;

})();
