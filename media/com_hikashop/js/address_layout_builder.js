/**
 * @package    HikaShop
 * @version    6.5.0
 * @author     hikashop.com
 * @copyright  (C) 2010-2026 HIKARI SOFTWARE. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
window.hikashopAddressLayoutBuilder = {
    container: null,
    unassignedContainer: null,
    config: null,
    saveUrl: null,

    init: function(options) {
        this.container = document.getElementById(options.containerId);
        this.unassignedContainer = document.getElementById(options.unassignedId);
        this.config = options.config || { enabled: false, columns: 4, rows: [], label_position: 'above' };
        this.saveUrl = options.saveUrl;

        this.initSortable();
        this.initDropZone();
    },

    initSortable: function() {
        var self = this;
        // Rows sortable
        Sortable.create(this.container, {
            animation: 150,
            handle: '.address_layout_row', // Sort by row itself? Or handled by a handle?
            // Row sorting is tricky if we want to sort fields inside rows AND rows themselves.
            // Let's assume for now we sort fields within rows and between rows.
        });

        // Fields sortable (within rows)
        var rows = document.querySelectorAll('.address_layout_row');
        rows.forEach(function(row) {
            self.makeRowSortable(row);
        });

        // Unassigned fields sortable
        Sortable.create(this.unassignedContainer, {
            group: 'shared',
            animation: 150,
            sort: false // Disable sorting within unassigned? Maybe allow it.
        });
    },

    makeRowSortable: function(row) {
        Sortable.create(row, {
            group: 'shared',
            animation: 150,
            filter: '.hk-move-btn',
            preventOnFilter: false,
            onAdd: function(evt) {
                // Ensure field not marked as unassigned anymore
                evt.item.classList.remove('address_layout_field_unassigned');
                window.hikashopAddressLayoutBuilder.adjustRowWidths(row, evt.item);
            },
            onEnd: function(evt) {
                 // Check if moved within same row or to another
            },
            onRemove: function(evt) {
                 // Keep empty rows during editing - they will be discarded upon save
                 // This allows users to drag fields back into empty rows
                 if (evt.from.children.length > 0) {
                    // Row not empty, fields remaining. They might leave a gap.
                    // "I would like the remaining fields in the initial row to adjust to fill the gap"
                    window.hikashopAddressLayoutBuilder.autoFillGap(evt.from);
                }
            },
             // Prevent drop if row would be overfull? 
             // SortableJS doesn't easily allow cancelling a drop based on logic BEFORE drop, 
             // but we can revert or handle it in onAdd.
             // Actually, the requirements say "Not allow more than 4 per row OR sum > 4". 
             // Implementing "Not allow" might be tricky with just configuration. 
             // Better to auto-resize existing to make space or reject if impossible.
             // Strategy: On drop, recalculate widths to fit.

             // Sortable doesn't provide a clean "canDrop" check for mixed lists.
             // We will rely on adjustRowWidths to enforce constraints.
        });
    },

    initDropZone: function() {
        var dropZone = document.querySelector('.address_layout_drop_zone');
        if(dropZone) {
            dropZone.addEventListener('click', function() {
                // Add new row logic
                window.hikashopAddressLayoutBuilder.createNewRow();
            });

            // Allow dropping fields onto the drop zone to create a new row
             Sortable.create(dropZone, {
                group: 'shared',
                animation: 150,
                onAdd: function(evt) {
                    var field = evt.item;
                    var self = window.hikashopAddressLayoutBuilder;

                    // Use setTimeout to ensure DOM operations happen after Sortable finishes
                    setTimeout(function() {
                        // Create new row
                        var newRow = self.createNewRow();
                        // Move field to new row
                        newRow.appendChild(field);
                        // Adjust width to fill the row (single field = 4/4)
                        self.adjustRowWidths(newRow, field);
                    }, 0);
                }
            });
        }
    },

    createNewRow: function() {
        var row = document.createElement('div');
        row.className = 'address_layout_row';
        this.container.insertBefore(row, document.querySelector('.address_layout_drop_zone'));
        this.makeRowSortable(row);
        return row;
    },

    reset: function() {
        if(confirm(Joomla.JText._('Are you sure you want to reset to default?'))) {
             var formData = new FormData();
             formData.append('address_form_layout', 'null');

             fetch(this.saveUrl, {
                 method: 'POST',
                 body: formData
             })
             .then(function(response) { return response.json(); })
             .then(function(data) {
                 if(data.success) {
                     window.location.reload();
                 } else {
                     alert(Joomla.JText._('ERROR_SAVING'));
                 }
             });
        }
    },

    save: function() {
        var newConfig = this.buildConfigFromDOM();

        var formData = new FormData();
        formData.append('address_form_layout', JSON.stringify(newConfig));

        fetch(this.saveUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Close popup or notify success
                 if(window.parent && window.parent.hikashop) { 
                     // Maybe refresh parent?
                     window.parent.hikashop.closeBox(); 
                 }
            } else {
                alert('Error saving: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error saving: ' + error);
        });
    },

    buildConfigFromDOM: function() {
        var rows = [];
        var rowEls = this.container.querySelectorAll('.address_layout_row');

        rowEls.forEach(function(rowEl) {
            var fields = [];
            var fieldEls = rowEl.querySelectorAll('.address_layout_field');
            fieldEls.forEach(function(fieldEl) {
                fields.push({
                    namekey: fieldEl.dataset.field,
                    width: parseInt(fieldEl.dataset.width),
                    label_position: fieldEl.dataset.labelPosition === 'inherit' ? null : fieldEl.dataset.labelPosition
                });
            });
            if(fields.length > 0) {
                rows.push({ fields: fields });
            }
        });

        return {
            enabled: this.config.enabled,
            columns: 4,
            label_position: document.getElementById('address_layout_global_label_position').value,
            rows: rows
        };
    },

    toggleEnabled: function(checked) {
        // Visual toggle if needed
    },

    cycleWidth: function(btn) {
        var field = btn.closest('.address_layout_field');
        var row = field.closest('.address_layout_row');
        var current = parseInt(field.dataset.width);
        // Unassigned fields have no row to rebalance
        if(!row) { this.setFieldWidth(field, (current % 4) + 1); return; }
        // Cap the cycle at the widest this field can reach (others keep at least 1)
        var others = row.querySelectorAll('.address_layout_field').length - 1;
        var maxW = Math.max(1, 4 - others);
        var next = current + 1;
        if(next > maxW) next = 1;
        this.resizeFieldTo(field, row, next);
    },

    // Resize a field to a target column count, rebalancing the row like a drag
    resizeFieldTo: function(field, row, newCols) {
        var startCols = parseInt(field.dataset.width);
        if(newCols < 1) newCols = 1;
        if(newCols > 4) newCols = 4;
        if(newCols === startCols) return;

        var otherFields = [];
        row.querySelectorAll('.address_layout_field').forEach(function(f) {
            if(f !== field) otherFields.push(f);
        });
        var startWidths = {};
        var startTotal = 0;
        row.querySelectorAll('.address_layout_field').forEach(function(f) {
            var w = parseInt(f.dataset.width);
            startWidths[f.dataset.field] = w;
            startTotal += w;
        });
        var delta = newCols - startCols;

        if(delta > 0) {
            var needed = delta;
            for(var i = 0; i < otherFields.length; i++) {
                var other = otherFields[i];
                var otherW = parseInt(other.dataset.width);
                if(otherW > 1) {
                    var take = Math.min(needed, otherW - 1);
                    this.setFieldWidth(other, otherW - take);
                    needed -= take;
                    if(needed <= 0) break;
                }
            }
            var freeSpace = 4 - startTotal;
            var canGrow = Math.min(delta, (delta - needed) + freeSpace);
            this.setFieldWidth(field, startCols + canGrow);
        } else {
            this.setFieldWidth(field, newCols);
            if(otherFields.length > 0) {
                var recipient = otherFields[otherFields.length - 1];
                this.setFieldWidth(recipient, parseInt(recipient.dataset.width) + (-delta));
            }
        }
    },

    // Resizing logic
    startResize: function(evt, handle) {
        evt.preventDefault();
        evt.stopPropagation();

        var field = handle.closest('.address_layout_field');
        var row = field.parentNode;
        var startX = evt.clientX;
        var startWidth = field.offsetWidth;
        var startCols = parseInt(field.dataset.width);
        var parentWidth = row.offsetWidth;
        var colWidth = parentWidth / 4;

        var otherFields = [];
        row.querySelectorAll('.address_layout_field').forEach(function(f) {
            if(f !== field) otherFields.push(f);
        });

        // Store original widths of all fields at drag start
        var startWidths = {};
        row.querySelectorAll('.address_layout_field').forEach(function(f) {
            startWidths[f.dataset.field] = parseInt(f.dataset.width);
        });
        var startTotal = 0;
        for(var k in startWidths) startTotal += startWidths[k];

        var moved = false;

        var onMove = function(moveEvt) {
            var diff = moveEvt.clientX - startX;
            if(Math.abs(diff) > 4) moved = true;
            var colsDiff = Math.round(diff / colWidth);
            var newCols = startCols + colsDiff;

            if(newCols < 1) newCols = 1;
            if(newCols > 4) newCols = 4;
            if(newCols === parseInt(field.dataset.width)) return;

            var delta = newCols - startCols;

            // Reset all other fields to their start widths first
            for(var i = 0; i < otherFields.length; i++) {
                var f = otherFields[i];
                window.hikashopAddressLayoutBuilder.setFieldWidth(f, startWidths[f.dataset.field]);
            }

            if(delta > 0) {
                // Growing: try to take from others, then use free space
                var needed = delta;
                for(var i = 0; i < otherFields.length; i++) {
                    var other = otherFields[i];
                    var otherW = parseInt(other.dataset.width);
                    if(otherW > 1) {
                        var take = Math.min(needed, otherW - 1);
                        window.hikashopAddressLayoutBuilder.setFieldWidth(other, otherW - take);
                        needed -= take;
                        if(needed <= 0) break;
                    }
                }
                // Grow by what we took + any free space in the row
                var freeSpace = 4 - startTotal;
                var canGrow = Math.min(delta, (delta - needed) + freeSpace);
                window.hikashopAddressLayoutBuilder.setFieldWidth(field, startCols + canGrow);
            } else {
                // Shrinking: give freed space to last other field
                window.hikashopAddressLayoutBuilder.setFieldWidth(field, newCols);
                if(otherFields.length > 0) {
                    var recipient = otherFields[otherFields.length - 1];
                    var recipientW = startWidths[recipient.dataset.field];
                    window.hikashopAddressLayoutBuilder.setFieldWidth(recipient, recipientW + (-delta));
                }
            }
        };

        var onUp = function() {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
            // A press without a drag acts as a click: cycle the width
            if(!moved) window.hikashopAddressLayoutBuilder.cycleWidth(handle);
            // Re-sync state for next drag
            startCols = parseInt(field.dataset.width);
        };

        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    },

    cycleLabelPosition: function(btn) {
        var field = btn.closest('.address_layout_field');
        var positions = ['inherit', 'above', 'yes', 'no'];
        var current = field.dataset.labelPosition || 'inherit';
        var idx = positions.indexOf(current);
        var next = positions[(idx + 1) % positions.length];

        field.dataset.labelPosition = next;

        // Update visual class
        field.classList.remove('hk-label-hidden', 'hk-label-above', 'hk-label-inline');

        var effective = next;
        if(next === 'inherit') {
            effective = document.getElementById('address_layout_global_label_position').value;
        }

        if(effective === 'no') field.classList.add('hk-label-hidden');
        else if(effective === 'above') field.classList.add('hk-label-above');
        else if(effective === 'yes') field.classList.add('hk-label-inline');
    },

    setGlobalLabelPosition: function(val) {
        // Update all fields set to inherit
        // Select fields from both main container and unassigned container
        var fields = document.querySelectorAll('.address_layout_field');
        fields.forEach(function(field) {
            if(!field.dataset.labelPosition || field.dataset.labelPosition === 'inherit') {
                field.classList.remove('hk-label-hidden', 'hk-label-above', 'hk-label-inline');
                if(val === 'no') field.classList.add('hk-label-hidden');
                else if(val === 'above') field.classList.add('hk-label-above');
                else if(val === 'yes') field.classList.add('hk-label-inline');
            }
        });
    },
    adjustRowWidths: function(row, newItem) {
        var fields = row.querySelectorAll('.address_layout_field');
        var count = fields.length;

        if(count > 4) {
             // Too many fields, reject drop
             alert(Joomla.JText._('ADDRESS_LAYOUT_TOO_MANY_FIELDS') || 'Maximum 4 fields per row');
             // Move back to unassigned? or previous container?
             // Since we don't track 'previous', we move to unassigned for safety, or just leave it and let user fix.
             // Better: Move to new row below.
             var newRow = this.createNewRow();
             // Insert after this row
             row.parentNode.insertBefore(newRow, row.nextSibling);
             newRow.appendChild(newItem);
             this.adjustRowWidths(newRow, newItem); // Recursive adjust for new row
             return; 
        }

        // Logic:
        // 1 field: 4/4 (100%)
        // 2 fields: 2/4 each (50%)
        // 3 fields: 
        //    - If 2 existing were 2/4 & 2/4 -> New one comes in? 
        //    - Require usage of 1/4 widths.
        //    - Try to make them equal? 4 / 3 is not integer.
        //    - Maybe 1, 1, 2? or 1, 2, 1?
        //    - User asked: "takes 1/3 of the space if 2 already there" -> 4/3 is impossible with 4 columns.
        //    - Maybe user meant visual 1/3? Standard grid is 12 cols usually, but here we use 4 cols.
        //    - Let's assume standard behavior:
        //      1 item -> 4
        //      2 items -> 2, 2
        //      3 items -> 1, 1, 2 (or fit available space)
        //      4 items -> 1, 1, 1, 1

        var targetWidth = 4;

        if (count === 1) {
            this.setFieldWidth(fields[0], 4);
        } else if (count === 2) {
             fields.forEach(f => this.setFieldWidth(f, 2));
        } else if (count === 3) {
             // 1, 1, 2 is total 4.
             // Can we preserve existing large ones?
             // Simplest is equal distribution approximation
             fields[0].dataset.width = 1; this.updateFieldVisual(fields[0]);
             fields[1].dataset.width = 1; this.updateFieldVisual(fields[1]);
             fields[2].dataset.width = 2; this.updateFieldVisual(fields[2]);
        } else if (count === 4) {
             fields.forEach(f => this.setFieldWidth(f, 1));
        }

        // Check if sum > 4, if so, compress
        var total = 0;
        fields.forEach(f => total += parseInt(f.dataset.width));

        if(total > 4) {
            // Compress largest fields first
            // Naive compression: set all to Math.floor(4/count) which for 3 is 1, total 3.
            // Then distribute remainder.
            var base = Math.floor(4 / count);
            var remainder = 4 % count;

            fields.forEach((f, index) => {
                var w = base + (index < remainder ? 1 : 0);
                this.setFieldWidth(f, w);
            });
        }
    },

    setFieldWidth: function(field, width) {
        field.dataset.width = width;
        this.updateFieldVisual(field);
    },

    // Auto-fill gap logic
    autoFillGap: function(row) {
        var fields = row.querySelectorAll('.address_layout_field');
        if(fields.length === 0) return;

        var total = 0;
        fields.forEach(f => total += parseInt(f.dataset.width));

        if (total < 4) {
            var gap = 4 - total;
            // Distribute gap.
            // Requirement: "adjust to fill the gap"
            // Simplest strategy: Give it to the last field? Or distribute evenly?
            // Usually expanding the last field is most intuitive or expected behavior.
            // Or maybe the largest field?
            // Let's expand equally starting from last?
            // Giving it to the last field is simplest and usually what's wanted (e.g. removed left col, right col expands).

            // Wait, if I have 2 fields (2, 2) and remove 1st. 2nd becomes 4. Correct.
            // If I have 3 fields (1, 1, 2) and remove middle. 1st (1), 3rd (2). Total 3. Gap 1.
            // 3rd becomes 3? -> 1, 3. Or 1st becomes 2? -> 2, 2.
            // Let's give to the last element for stability.

            var lastField = fields[fields.length - 1];
            var currentW = parseInt(lastField.dataset.width);
            this.setFieldWidth(lastField, currentW + gap);
        }
    },
    moveField: function(btn, direction) {
        var field = btn.closest('.address_layout_field');
        var row = field.closest('.address_layout_row');
        if(!row) return;
        var fields = Array.prototype.slice.call(row.querySelectorAll('.address_layout_field'));
        var idx = fields.indexOf(field);
        var allRows = Array.prototype.slice.call(this.container.querySelectorAll('.address_layout_row'));
        var rowIdx = allRows.indexOf(row);

        if(direction === 'left' && idx > 0) {
            row.insertBefore(field, fields[idx - 1]);
        } else if(direction === 'right' && idx < fields.length - 1) {
            var next = fields[idx + 1];
            if(next.nextSibling) row.insertBefore(field, next.nextSibling);
            else row.appendChild(field);
        } else if(direction === 'up' && rowIdx > 0) {
            allRows[rowIdx - 1].appendChild(field);
            this.adjustRowWidths(allRows[rowIdx - 1], field);
            this.autoFillGap(row);
        } else if(direction === 'down') {
            if(rowIdx < allRows.length - 1) {
                allRows[rowIdx + 1].appendChild(field);
                this.adjustRowWidths(allRows[rowIdx + 1], field);
                this.autoFillGap(row);
            }
        } else return;

        btn.focus();
    },

    updateFieldVisual: function(field) {
        var width = field.dataset.width;
        var indicator = field.querySelector('.address_layout_width_indicator');
        if(indicator) indicator.innerText = width + '/4';
        var resizeBtn = field.querySelector('.address_layout_resize_handle');
        if(resizeBtn) {
            var srSpan = resizeBtn.querySelector('.element-invisible');
            var baseText = resizeBtn.title.replace(/\s*\(\d+\/4\)/, '');
            resizeBtn.title = baseText + ' (' + width + '/4)';
            if(srSpan) srSpan.textContent = resizeBtn.title;
        }
    }
};
