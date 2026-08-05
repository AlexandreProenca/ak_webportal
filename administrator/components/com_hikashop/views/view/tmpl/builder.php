<?php
/**
 * @package	HikaShop
 * @version	6.5.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2026 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or defined('ABSPATH') or die('Restricted access');
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?><div id="shared-lists" class="hk-builder-card">
    <div class="shared-lists-container">
    <h2 class="hk-builder-title"><?php echo JText::_('VIEW_BUILDER'); ?></h2>
<?php
$grid = false;
$pos = '';
$forbidden_ids = array();
hikashop_loadJsLib('tooltip');
foreach($this->element->structure as $k => $structure) {
    foreach($structure->blocks as $block) {
        if(in_array($block->type, array('block', 'empty'))) {
            $this->element->structure[$k]->has_blocks = true;
            break;
        }
    }
    if(isset($structure->id))
        $forbidden_ids[] = $structure->id;
}
$count = 0;
foreach($this->element->structure as $k => $structure) {
    if(empty($structure->has_blocks))
        continue;
    if($count>=12) {
        $count = 0;
        $pos = 'left';
        $grid = false;
?>
        </div>
    </div>
<?php
    }
    if(is_numeric($structure->width)) {
        if($grid == false) {
            $grid = true;
?>
    <div class="hk-row-fluid">
<?php
        $count += $structure->width;
        }else {
?>
        </div>
<?php
}
?>
        <div class="hkc-sm-<?php echo $structure->width; ?>">
<?php
    }
    if($structure->width == 'left' && $grid == false) {
        $grid = true;
        $pos = 'left';
?>
    <div class="hk-row-fluid">
        <div class="hkc-sm-6 ">
<?php
    }
    if($structure->width == 'right' && $pos == 'left') {
        $pos = 'right';
?>
        </div>
        <div class="hkc-sm-6 ">
<?php
    }
    if($structure->width == 'full' && $grid) {
        $count = 0;
        $pos = 'left';
        $grid = false;
?>
        </div>
    </div>
<?php
    }
    if(isset($structure->id))
        $id = $structure->id;
    else {
        $id = $k;
        while(in_array($id, $forbidden_ids)) {
            $id++;
        }
        $forbidden_ids[] = $id;
    }
?>
    <div id="builder-group<?php echo $id; ?>" class="builder-list-group"><?php
    foreach($structure->blocks as $i => $block) {
        if($block->type == 'separator') {
            $keys = array_keys($structure->blocks);
            if($i != end($keys) && $i != reset($keys)) {
                $id = max($forbidden_ids)+1;
                $forbidden_ids[] = $id;
?>
    </div>
    <div id="builder-group<?php echo $id; ?>" class="builder-list-group">
<?php
            }
            continue;
        }
        if($block->type == 'normal')
            continue;
        if($block->type == 'empty')
            continue;

        $tooltip = JText::sprintf('VIEW_BUILDER_DELETE', $block->name);
        ?><div class="list-group-item" data-id="<?php echo $block->name; ?>">
            <span class="hk-item-name"><?php echo $block->name; ?></span>
            <div class="hk-item-actions">
                <a href="#" class="hk-item-action" onclick="scrollToBlock(this); return false;" data-toggle="hk-tooltip" data-title="<?php echo JText::_('SCROLL_TO_CODE'); ?>">
                    <i class="fas fa-file-code" aria-hidden="true"></i><span class="element-invisible"><?php echo JText::_('SCROLL_TO_CODE'); ?></span>
                </a>
                <button type="button" class="hk-item-action hk-move-btn" onclick="moveBlockBtn(this, 'prev');" data-toggle="hk-tooltip" data-title="<?php echo JText::_('HIKA_MOVE_PREV_POSITION'); ?>">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i><span class="element-invisible"><?php echo JText::_('HIKA_MOVE_PREV_POSITION'); ?></span>
                </button>
                <button type="button" class="hk-item-action hk-move-btn" onclick="moveBlockBtn(this, 'up');" data-toggle="hk-tooltip" data-title="<?php echo JText::_('HIKA_MOVE_UP'); ?>">
                    <i class="fas fa-arrow-up" aria-hidden="true"></i><span class="element-invisible"><?php echo JText::_('HIKA_MOVE_UP'); ?></span>
                </button>
                <button type="button" class="hk-item-action hk-move-btn" onclick="moveBlockBtn(this, 'down');" data-toggle="hk-tooltip" data-title="<?php echo JText::_('HIKA_MOVE_DOWN'); ?>">
                    <i class="fas fa-arrow-down" aria-hidden="true"></i><span class="element-invisible"><?php echo JText::_('HIKA_MOVE_DOWN'); ?></span>
                </button>
                <button type="button" class="hk-item-action hk-move-btn" onclick="moveBlockBtn(this, 'next');" data-toggle="hk-tooltip" data-title="<?php echo JText::_('HIKA_MOVE_NEXT_POSITION'); ?>">
                    <i class="fas fa-arrow-right" aria-hidden="true"></i><span class="element-invisible"><?php echo JText::_('HIKA_MOVE_NEXT_POSITION'); ?></span>
                </button>
                <a href="#" class="hk-item-delete" onclick="removeBlock(this); return false;" data-toggle="hk-tooltip" data-title="<?php echo $tooltip; ?>">
                    <i class="fas fa-times" aria-hidden="true"></i><span class="element-invisible"><?php echo $tooltip; ?></span>
                </a>
            </div>
         </div><?php
    }
    ?></div>
<?php
}
if($grid) {
?>
        </div>
    </div>
<?php
}
$changes_text = " Please first save your modifications so that the drag & drop interface can be refreshed.";
?>
</div>
</div>
<?php hikashop_loadJslib('sortable'); ?>
<script>
var userModifications = false;
var sortableModifications = false;
window.hikashop.ready( function() {
    var textarea = document.getElementById('jform_articletext');

    textarea.addEventListener('input', function(evt) {
        if(!window.sortableModifications)
            window.userModifications = true;
    });

    var editor = document.querySelector('.CodeMirror');
    if(editor) {
        editor = editor.CodeMirror;
        editor.on('change', function(instance, changeObj) {
            if(!window.sortableModifications)
                window.userModifications = true;
        });
    }
});
function scrollToBlock(target) {
    var id;
    if (typeof target === 'string') {
        id = target;
    } else {
        id = target.closest('.list-group-item').getAttribute('data-id');
    }
    var search = '<!-- '+id+' -->';

    var textarea = document.getElementById('jform_articletext');
    if (textarea && textarea.hikaCM) {
        var cm = textarea.hikaCM;
        var content = cm.getValue();
        var lines = content.split('\n');
        for (var i = 0; i < lines.length; i++) {
            if (lines[i].indexOf(search) !== -1) {
                cm.focus();
                cm.setCursor(i, 0);
                try {
                    var coords = cm.charCoords({line: i, ch: 0}, "local");
                    cm.scrollTo(null, coords.top);
                } catch(e) {}
                return;
            }
        }
    }

    var cmEl = null;
    if (textarea) {

        var parent = textarea.parentNode;
        if (parent.tagName === 'JOOMLA-EDITOR-CODEMIRROR') {
             console.log('Found Joomla CM6 Wrapper:', parent);
             if (parent.editor) { // Check if the element has an 'editor' property exposing the CM instance
             }
        }
    }

    if (window.JoomlaEditor && window.JoomlaEditor.get) {
        var jEditor = window.JoomlaEditor.get('jform_articletext');
    }

    if (cmEl && cmEl.CodeMirror) {
        var cm = cmEl.CodeMirror;
        var content = cm.getValue();
        var lines = content.split('\n');
        for (var i = 0; i < lines.length; i++) {
            if (lines[i].indexOf(search) !== -1) {
                cm.focus(); 
                cm.setCursor(i, 0);

                try {
                     var coords = cm.charCoords({line: i, ch: 0}, "local");
                     cm.scrollTo(null, coords.top);
                } catch(e) { console.error('CM Scroll Error', e); }
                return;
            }
        }
    }

    var textarea = document.getElementById('jform_articletext');
    if (textarea) {
        var parent = textarea.parentNode;
        var scroller = parent.querySelector('.cm-scroller');
        if (scroller) {
            var val = textarea.value;
            var index = val.indexOf(search);
            if (index !== -1) {
                var linesBefore = val.substr(0, index).split('\n').length;
                var content = scroller.querySelector('.cm-content');
                var lineHeight = 23; 
                if (content) {
                     var styles = window.getComputedStyle(content);
                     var lh = parseInt(styles.lineHeight);
                     if (!isNaN(lh)) lineHeight = lh;
                }

                var top = (linesBefore - 1) * lineHeight;

                scroller.scrollTop = top;

                setTimeout(function() {
                    var treeWalker = document.createTreeWalker(content, NodeFilter.SHOW_TEXT, null, false);
                    var node;
                    while(node = treeWalker.nextNode()) {
                        if(node.nodeValue && node.nodeValue.indexOf(search) !== -1) {
                            var range = document.createRange();
                            var start = node.nodeValue.indexOf(search);
                            range.setStart(node, start);
                            range.setEnd(node, start + search.length);
                            var sel = window.getSelection();
                            sel.removeAllRanges();
                            sel.addRange(range);
                            break;
                        }
                    }
                }, 100);
                return;
            }
        }
    }

    if (textarea) {
        var val = textarea.value;
        var index = val.indexOf(search);
        if (index !== -1) {
            var linesBefore = val.substr(0, index).split('\n').length;
            var lineHeight = 18; 
            textarea.scrollTop = (linesBefore - 5) * lineHeight;
            try {
                textarea.focus();
                if (textarea.setSelectionRange) {
                    textarea.setSelectionRange(index, index + search.length);
                }
            } catch(e) {}
        }
    }
}
function removeBlock(link) {
    if(confirm('Are you sure you want to delete this block ?')) {
        var item = link.closest('.list-group-item');
        if(!item) return;
        var group = item.closest('.builder-list-group');
        var evt = {item: item, from: group || item.parentNode};
        if(moveBlockCode(evt))
            item.remove();
    }
}
function checkCodeMove(evt) {
    if(window.userModifications) {
        alert("The move was not possible as the code has been manually changed in the code editor. <?php echo $changes_text; ?>");
        return false;
    }
    return true;
}
function moveBlockCode(evt) {
    window.sortableModifications = true;
    var textarea = document.getElementById('jform_articletext');
    var regex = new RegExp('([\t\t\n ]*)<!-- ' + evt.item.getAttribute('data-id') + ' -->(.*?)<!-- EO ' + evt.item.getAttribute('data-id') +' -->', 'gs');
    var code = regex.exec(textarea.value);
    var codeleft = '';
    var empty_pos_code = [];

    if(!code || !code.length) {
        alert("The move was not possible tags for the block being moved could not be found in the code.<?php echo $changes_text; ?>");
        window.sortableModifications = false;
        return false;
    }

    var blocks_left = evt.from.querySelectorAll('.list-group-item');
    if(!blocks_left || blocks_left.length == 0 || (blocks_left.length == 1 && blocks_left[0].getAttribute('data-id') == evt.item.getAttribute('data-id'))) {
        var id = evt.from.id.replace('builder-group','');
        codeleft = code[1] + '<!-- POSITION '+id+' -->';
        evt.from.innerHTML = '';
    }

    if(evt.to) {
        var blocks_on_arrival = evt.to.querySelectorAll('.list-group-item');
        if(blocks_on_arrival.length <= 1) {
            var id = evt.to.id.replace('builder-group','');
            var regex = new RegExp('([\t\t\n ]*)<!-- POSITION '+id+' -->', 'gs');
            var empty_pos_code = regex.exec(textarea.value);
            if(!empty_pos_code || !empty_pos_code.length) {
                alert('The move was not possible as the empty position tag could not be found in the destination position');
                window.sortableModifications = false;
                return false;
            }
        } else {
            var sibling = null;
            var after = false;
            if(blocks_on_arrival[evt.newIndex+1]) {
                sibling = blocks_on_arrival[evt.newIndex+1];
            } else {
                sibling = blocks_on_arrival[evt.newIndex-1];
                after = true;
            }
            var regex = new RegExp('([\t\t\n ]*)<!-- ' + sibling.getAttribute('data-id') + ' -->(.*?)<!-- EO ' + sibling.getAttribute('data-id') +' -->', 'gs');
            var sibling_code = regex.exec(textarea.value);
            if(!sibling_code || !sibling_code.length) {
                alert('The move was not possible as the ' + sibling.getAttribute('data-id') + ' tag could not be found in the destination position');
                window.sortableModifications = false;
                return false;
            }
            var id = evt.to.id.replace('builder-group','');
            empty_pos_code.push(sibling_code[1] + '<!-- POSITION '+id+' -->');
            var result = empty_pos_code[0] + sibling_code[0];
            if(after) {
                result = sibling_code[0] + empty_pos_code[0];
            }
            textarea.value = textarea.value.replace(sibling_code[0], result);

        }
    }
    textarea.value = textarea.value.replace(code[0], codeleft);

    if(evt.to) {
        textarea.value = textarea.value.replace(empty_pos_code[0], code[0]);
    }

    if(textarea.hikaCM) {
        var scrollInfo = textarea.hikaCM.getScrollInfo();
        textarea.hikaCM.setValue(textarea.value);
        textarea.hikaCM.scrollTo(scrollInfo.left, scrollInfo.top);
    } else if(Joomla && Joomla.editors) {
        var editor = Joomla.editors.instances['jform_articletext'];
        editor.setValue(textarea.value);
    } else {
        var editor = document.querySelector('.CodeMirror');
        if(editor) {
            editor = editor.CodeMirror;
            var scrollInfo = editor.getScrollInfo();

            editor.setValue(textarea.value);

            editor.scrollTo(scrollInfo.left, scrollInfo.top);
        }
    }

    setTimeout(function() {
        scrollToBlock(evt.item.getAttribute('data-id'));
    }, 50);

    window.sortableModifications = false;
    return true;
}
function moveBlockBtn(btn, direction) {
	if(!checkCodeMove({})) return;

	var item = btn.closest('.list-group-item');
	var group = item.closest('.builder-list-group');
	var items = Array.prototype.slice.call(group.querySelectorAll('.list-group-item'));
	var allGroups = Array.prototype.slice.call(document.querySelectorAll('.builder-list-group'));
	var itemIndex = items.indexOf(item);
	var groupIndex = allGroups.indexOf(group);

	var evt = {item: item, from: group};

	if(direction === 'up') {
		if(itemIndex === 0) return;
		group.insertBefore(item, items[itemIndex - 1]);
		evt.to = group;
		evt.newIndex = itemIndex - 1;
	} else if(direction === 'down') {
		if(itemIndex >= items.length - 1) return;
		var next = items[itemIndex + 1];
		if(next.nextSibling) {
			group.insertBefore(item, next.nextSibling);
		} else {
			group.appendChild(item);
		}
		evt.to = group;
		evt.newIndex = itemIndex + 1;
	} else if(direction === 'prev') {
		if(groupIndex === 0) return;
		var targetGroup = allGroups[groupIndex - 1];
		targetGroup.appendChild(item);
		evt.to = targetGroup;
		evt.newIndex = targetGroup.querySelectorAll('.list-group-item').length - 1;
	} else if(direction === 'next') {
		if(groupIndex >= allGroups.length - 1) return;
		var targetGroup = allGroups[groupIndex + 1];
		targetGroup.appendChild(item);
		evt.to = targetGroup;
		evt.newIndex = targetGroup.querySelectorAll('.list-group-item').length - 1;
	}

	moveBlockCode(evt);
	btn.focus();
}
var elements = document.querySelectorAll('.builder-list-group'), groups = [];
for(var i = 0; i < elements.length; i++) {
    groups.push(
        new Sortable(elements[i], {
            group: 'shared',
            animation: 150,
            dataIdAttr: 'data-id',
            filter: '.hk-move-btn',
            preventOnFilter: false,
            onMove: function (evt) {
                return checkCodeMove(evt);
            },
            onEnd: function (evt) {
                return moveBlockCode(evt);
            }
        })
    );
}
</script>
</script>
</script>
<style>

#shared-lists {
    background: transparent;
    padding: 0;
}

.hk-builder-card {
    background: #fff;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    border-radius: 8px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    margin: 15px auto;
    max-width: 1400px; 
    box-sizing: border-box;
}


.shared-lists-container {
    border: none;
    padding: 0;
    background: transparent;
}

.hk-builder-title {
    margin: 0 0 15px 0;
    color: #333;
    font-weight: 600;
    font-size: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}


#shared-lists .hk-row-fluid {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -5px; 
    align-items: stretch;
    width: 100%;
    box-sizing: border-box;
}


#shared-lists .hk-row-fluid:before, 
#shared-lists .hk-row-fluid:after {
    display: none;
}

#shared-lists [class*="hkc-sm-"] {
    padding: 0 5px; 
    margin-bottom: 10px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}


#shared-lists .hkc-sm-12 { width: 100%; flex: 0 0 100%; max-width: 100%; }
#shared-lists .hkc-sm-6 { width: 50%; flex: 0 0 50%; max-width: 50%; }
#shared-lists .hkc-sm-4 { width: 33.333%; flex: 0 0 33.333%; max-width: 33.333%; }


.builder-list-group {
    background: #f8f9fa;
    border: 1px dashed #ced4da; 
    border-radius: 6px;
    padding: 8px;
    min-height: 40px;
    display: flex;
    flex-direction: column;
    gap: 4px; 
    transition: all 0.2s ease;
    flex-grow: 1;
    height: auto;
}

.builder-list-group:hover {
    border-color: #adb5bd;
    background: #f1f3f5;
}

.sortable-ghost {
    background: #e2e6ea !important;
    border: 1px dashed #adb5bd !important;
    opacity: 0.5;
}


.list-group-item {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 4px 8px; 
    min-height: 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    cursor: grab;
    transition: transform 0.2s, box-shadow 0.2s;
    user-select: none;
    margin-bottom: 0px !important;
    font-size: 13px; 
}

.list-group-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    border-color: #dee2e6;
    z-index: 2;
}

.list-group-item:active {
    cursor: grabbing;
}


.hk-item-name {
    font-size: 13px;
    font-weight: 500;
    color: #495057;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
    min-width: 0;
}


.hk-item-actions {
    display: flex;
    gap: 1px;
    margin-left: auto;
    opacity: 0;
    transition: opacity 0.15s;
}
.list-group-item:hover .hk-item-actions,
.list-group-item:focus-within .hk-item-actions {
    opacity: 1;
}

.hk-item-action, .hk-item-delete {
    color: #adb5bd;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 3px;
    transition: color 0.2s, background 0.2s;
    text-decoration: none !important;
    font-size: 12px;
}
.hk-move-btn {
    border: none;
    background: none;
    padding: 0;
    cursor: pointer;
    font: inherit;
}
.hk-item-action:hover {
    color: #0d6efd;
    background: #e7f1ff;
}
.hk-item-delete:hover {
    color: #dc3545;
    background: #ffe6e6;
}
.hk-item-action:focus, .hk-item-delete:focus {
    outline: 2px solid #3288e6;
    outline-offset: 1px;
}


.builder-list-group:empty {
    align-items: center;
    justify-content: center;
    min-height: 40px;
}
.builder-list-group:empty:after {
    content: '<?php echo JText::_('EMPTY_POSITION'); ?>';
    color: #adb5bd;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    pointer-events: none;
    border: none;
    padding: 0 !important;
    margin: 0 !important;
}
</style>
