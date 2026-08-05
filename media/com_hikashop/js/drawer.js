/**
 * @package    HikaShop
 * @version    6.5.0
 * @author     hikashop.com
 * @copyright  (C) 2010-2026 HIKARI SOFTWARE. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
/*!
* By https://github.com/tomaszbujnowicz/vanilla-js-drawer
* Based on articles on https://gomakethings.com
*/

var hkDrawer = {

//
// Settings
//
settings : {
    speedOpen: 50,
    speedClose: 350,
    activeClass: 'hk-is-active',
    visibleClass: 'hk-is-visible',
    selectorTarget: '[data-drawer-target]',
    selectorTrigger: '[data-drawer-trigger]',
    selectorClose: '[data-drawer-close]',
},


/**
* Element.closest() polyfill
* https://developer.mozilla.org/en-US/docs/Web/API/Element/closest#Polyfill
*/
init: function() {
    if (!Element.prototype.closest) {
        if (!Element.prototype.matches) {
        Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
        }
        Element.prototype.closest = function (s) {
        var el = this;
        var ancestor = this;
        if (!document.documentElement.contains(el)) return null;
        do {
            if (ancestor.matches(s)) return ancestor;
            ancestor = ancestor.parentElement;
        } while (ancestor !== null);
        return null;
        };
    }

    //
    // Inits & Event Listeners
    //
    document.addEventListener('click', this.clickHandler, false);
    document.addEventListener('keydown', this.keydownHandler, false);
},


// Trap Focus 
// https://hiddedevries.nl/en/blog/2017-01-29-using-javascript-to-trap-focus-in-an-element
//
trapFocus: function(element) {
    var focusableEls = element.querySelectorAll('a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]), select:not([disabled])');
    var firstFocusableEl = focusableEls[0];  
    var lastFocusableEl = focusableEls[focusableEls.length - 1];
    var KEYCODE_TAB = 9;

    element.addEventListener('keydown', function(e) {
    var isTabPressed = (e.key === 'Tab' || e.keyCode === KEYCODE_TAB);

    if (!isTabPressed) { 
        return; 
    }

    if ( e.shiftKey ) /* shift + tab */ {
        if (document.activeElement === firstFocusableEl) {
        lastFocusableEl.focus();
            e.preventDefault();
        }
        } else /* tab */ {
        if (document.activeElement === lastFocusableEl) {
        firstFocusableEl.focus();
            e.preventDefault();
        }
        }
    });
},  



//
// Methods
//

// Toggle accessibility
toggleAccessibility: function (event) {
    if (event.getAttribute('aria-expanded') === 'true') {
    event.setAttribute('aria-expanded', false);
    } else {
    event.setAttribute('aria-expanded', true);
    }
},
// Compensate for a containing-block trap: transform / filter / perspective /
// backdrop-filter (or the matching will-change) on an ancestor turn that
// ancestor into the containing block for position:fixed descendants, so the
// overlay and wrapper end up sized and offset relative to the ancestor's box
// (commonly a frosted-glass navbar in UIkit / Bootstrap 5 / sticky headers)
// instead of the viewport. We measure the nearest trapping ancestor and
// patch the wrapper / overlay with inline offsets that bring them back to
// the viewport edges. No-op when no such ancestor exists.
findContainingBlockAncestor: function (el) {
    var n = el.parentElement;
    while (n && n !== document.documentElement) {
        var c = getComputedStyle(n);
        if (c.transform !== 'none' || c.filter !== 'none'
            || c.perspective !== 'none' || c.backdropFilter !== 'none'
            || /transform|filter|perspective/.test(c.willChange)) {
            return n;
        }
        n = n.parentElement;
    }
    return null;
},
applyEscape: function (target) {
    var wrapper = target.querySelector('.hikashop_drawer__wrapper');
    var overlay = target.querySelector('.hikashop_drawer__overlay');
    var anchor = wrapper || overlay;
    if (!anchor) return;
    var trap = hkDrawer.findContainingBlockAncestor(anchor);
    if (!trap) return;
    var r = trap.getBoundingClientRect();
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    if (wrapper) {
        wrapper.style.top    = (-r.top) + 'px';
        wrapper.style.right  = (-(vw - r.right)) + 'px';
        wrapper.style.height = vh + 'px';
    }
    if (overlay) {
        overlay.style.top    = (-r.top) + 'px';
        overlay.style.left   = (-r.left) + 'px';
        overlay.style.width  = vw + 'px';
        overlay.style.height = vh + 'px';
    }
},
clearEscape: function (target) {
    var wrapper = target.querySelector('.hikashop_drawer__wrapper');
    var overlay = target.querySelector('.hikashop_drawer__overlay');
    if (wrapper) {
        wrapper.style.top = wrapper.style.right = wrapper.style.height = '';
    }
    if (overlay) {
        overlay.style.top = overlay.style.left = overlay.style.width = overlay.style.height = '';
    }
},
// Open Drawer
openDrawer: function (trigger) {

    // Find target
    var target = document.getElementById(trigger.getAttribute('aria-controls'));
    // Make it active
    target.classList.add(hkDrawer.settings['activeClass']);

    // Patch positioning if an ancestor is trapping our position:fixed
    // (e.g. backdrop-filter on the navbar). Re-apply on resize so the
    // offsets follow the viewport while the drawer stays open.
    hkDrawer.applyEscape(target);
    target.hkDrawerResizeHandler = function () { hkDrawer.applyEscape(target); };
    window.addEventListener('resize', target.hkDrawerResizeHandler);

    target.toggleOpen = !target.toggleOpen;

    // Make body overflow hidden so it's not scrollable
    var scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
    var isRTL = getComputedStyle(document.documentElement).direction === 'rtl';
    document.documentElement.style.overflow = 'hidden';
    document.documentElement.style[isRTL ? 'paddingLeft' : 'paddingRight'] = scrollbarWidth + 'px';

    // Toggle accessibility
    hkDrawer.toggleAccessibility(trigger);

    // Make it visible
    setTimeout(function () {
    target.classList.add(hkDrawer.settings['visibleClass']);
    hkDrawer.trapFocus(target);
    }, hkDrawer.settings['speedOpen']);

},
// Close Drawer
closeDrawer: function (event) {

    // Find target
    var closestParent = event.closest(hkDrawer.settings['selectorTarget']);
    if(!closestParent) return;
    var childrenTrigger = document.querySelector('[aria-controls="' + closestParent.id + '"');

    closestParent.toggleOpen = !closestParent.toggleOpen;

    // Make it not visible
    closestParent.classList.remove(hkDrawer.settings['visibleClass']);

    // Remove body overflow hidden
    document.documentElement.style.overflow = '';
    document.documentElement.style.paddingLeft = '';
    document.documentElement.style.paddingRight = '';

    // Toggle accessibility
    hkDrawer.toggleAccessibility(childrenTrigger);

    // Make it not active
    setTimeout(function () {
    closestParent.classList.remove(hkDrawer.settings['activeClass']);
    // Clear the containing-block compensation applied on open and stop
    // listening for resize so we do not leak handlers across opens.
    hkDrawer.clearEscape(closestParent);
    if (closestParent.hkDrawerResizeHandler) {
        window.removeEventListener('resize', closestParent.hkDrawerResizeHandler);
        closestParent.hkDrawerResizeHandler = null;
    }
    }, hkDrawer.settings['speedClose']);

},

// Click Handler
clickHandler: function (event) {

    // Find elements
    var toggle = event.target,
    open = toggle.closest(hkDrawer.settings['selectorTrigger']),
    close = toggle.closest(hkDrawer.settings['selectorClose']);

    // Open drawer when the open button is clicked
    if (open) {
        hkDrawer.openDrawer(open);
    }

    // Close drawer when the close button (or overlay area) is clicked
    if (close) {
        hkDrawer.closeDrawer(close);
    }

    // Prevent default link behavior
    if (open || close) {
    event.preventDefault();
    }

},

// Keydown Handler, handle Escape button
keydownHandler: function (event) {

    if (event.key === 'Escape' || event.keyCode === 27) {

    // Find all possible drawers
    var drawers = document.querySelectorAll(hkDrawer.settings['selectorTarget']),
        i;

    // Find active drawers and close them when escape is clicked
    for (i = 0; i < drawers.length; ++i) {
        if (drawers[i].classList.contains(hkDrawer.settings['activeClass'])) {
            hkDrawer.closeDrawer(drawers[i]);
        }
    }

    }

},




};

hkDrawer.init();
