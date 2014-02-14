/**
 * NetteQ
 *
 * This file is part of the Nette Framework.
 * Copyright (c) 2004, 2012 David Grudl (http://davidgrudl.com)
 */

var Nette = Nette || {};

(function(){

// simple class builder
Nette.Class = function(def) {
	var cl = def.constructor || function(){}, nm, __hasProp = Object.prototype.hasOwnProperty;
	delete def.constructor;

	if (def.Extends) {
		var foo = function() { this.constructor = cl; };
		foo.prototype = def.Extends.prototype;
		cl.prototype = new foo();
		delete def.Extends;
	}

	if (def.Static) {
		for (nm in def.Static) { if (__hasProp.call(def.Static, nm)) cl[nm] = def.Static[nm]; }
		delete def.Static;
	}

	for (nm in def) { if (__hasProp.call(def, nm)) cl.prototype[nm] = def[nm]; }
	return cl;
};


// supported cross-browser selectors: #id  |  div  |  div.class  |  .class
Nette.Q = Nette.Class({

	Static: {
		factory: function(selector) {
			return new Nette.Q(selector);
		},

		implement: function(methods) {
			var nm, fn = Nette.Q.implement, prot = Nette.Q.prototype, __hasProp = Object.prototype.hasOwnProperty;
			for (nm in methods) {
				if (!__hasProp.call(methods, nm)) {
					continue;
				}
				fn[nm] = methods[nm];
				prot[nm] = (function(nm){
					return function() { return this.each(fn[nm], arguments); };
				}(nm));
			}
		}
	},

	constructor: function(selector) {
		if (typeof selector === "string") {
			selector = this._find(document, selector);

		} else if (!selector || selector.nodeType || selector.length === undefined || selector === window) {
			selector = [selector];
		}

		for (var i = 0, len = selector.length; i < len; i++) {
			if (selector[i]) { this[this.length++] = selector[i]; }
		}
	},

	length: 0,

	find: function(selector) {
		return new Nette.Q(this._find(this[0], selector));
	},

	_find: function(context, selector) {
		if (!context || !selector) {
			return [];

		} else if (document.querySelectorAll) {
			return context.querySelectorAll(selector);

		} else if (selector.charAt(0) === '#') { // #id
			return [document.getElementById(selector.substring(1))];

		} else { // div  |  div.class  |  .class
			selector = selector.split('.');
			var elms = context.getElementsByTagName(selector[0] || '*');

			if (selector[1]) {
				var list = [], pattern = new RegExp('(^|\\s)' + selector[1] + '(\\s|$)');
				for (var i = 0, len = elms.length; i < len; i++) {
					if (pattern.test(elms[i].className)) { list.push(elms[i]); }
				}
				return list;
			} else {
				return elms;
			}
		}
	},

	dom: function() {
		return this[0];
	},

	each: function(callback, args) {
		for (var i = 0, res; i < this.length; i++) {
			if ((res = callback.apply(this[i], args || [])) !== undefined) { return res; }
		}
		return this;
	}
});


var $ = Nette.Q.factory, fn = Nette.Q.implement;

fn({
	// cross-browser event attach
	bind: function(event, handler) {
		if (document.addEventListener && (event === 'mouseenter' || event === 'mouseleave')) { // simulate mouseenter & mouseleave using mouseover & mouseout
			var old = handler;
			event = event === 'mouseenter' ? 'mouseover' : 'mouseout';
			handler = function(e) {
				for (var target = e.relatedTarget; target; target = target.parentNode) {
					if (target === this) { return; } // target must not be inside this
				}
				old.call(this, e);
			};
		}

		var data = fn.data.call(this),
			events = data.events = data.events || {}; // use own handler queue

		if (!events[event]) {
			var el = this, // fixes 'this' in iE
				handlers = events[event] = [],
				generic = fn.bind.genericHandler = function(e) { // dont worry, 'e' is passed in IE
					if (!e.target) {
						e.target = e.srcElement;
					}
					if (!e.preventDefault) {
						e.preventDefault = function() { e.returnValue = false; };
					}
					if (!e.stopPropagation) {
						e.stopPropagation = function() { e.cancelBubble = true; };
					}
					e.stopImmediatePropagation = function() { this.stopPropagation(); i = handlers.length; };
					for (var i = 0; i < handlers.length; i++) {
						handlers[i].call(el, e);
					}
				};

			if (document.addEventListener) { // non-IE
				this.addEventListener(event, generic, false);
			} else if (document.attachEvent) { // IE < 9
				this.attachEvent('on' + event, generic);
			}
		}

		events[event].push(handler);
	},

	// adds class to element
	addClass: function(className) {
		this.className = this.className.replace(/^|\s+|$/g, ' ').replace(' '+className+' ', ' ') + ' ' + className;
	},

	// removes class from element
	removeClass: function(className) {
		this.className = this.className.replace(/^|\s+|$/g, ' ').replace(' '+className+' ', ' ');
	},

	// tests whether element has given class
	hasClass: function(className) {
		return this.className.replace(/^|\s+|$/g, ' ').indexOf(' '+className+' ') > -1;
	},

	show: function() {
		var dsp = fn.show.display = fn.show.display || {}, tag = this.tagName;
		if (!dsp[tag]) {
			var el = document.body.appendChild(document.createElement(tag));
			dsp[tag] = fn.css.call(el, 'display');
		}
		this.style.display = dsp[tag];
	},

	hide: function() {
		this.style.display = 'none';
	},

	css: function(property) {
		return this.currentStyle ? this.currentStyle[property]
			: (window.getComputedStyle ? document.defaultView.getComputedStyle(this, null).getPropertyValue(property) : undefined);
	},

	data: function() {
		return this.nette ? this.nette : this.nette = {};
	},

	val: function() {
		var i;
		if (!this.nodeName) { // radio
			for (i = 0, len = this.length; i < len; i++) {
				if (this[i].checked) { return this[i].value; }
			}
			return null;
		}

		if (this.nodeName.toLowerCase() === 'select') {
			var index = this.selectedIndex, options = this.options;

			if (index < 0) {
				return null;

			} else if (this.type === 'select-one') {
				return options[index].value;
			}

			for (i = 0, values = [], len = options.length; i < len; i++) {
				if (options[i].selected) { values.push(options[i].value); }
			}
			return values;
		}

		if (this.type === 'checkbox') {
			return this.checked;
		}

		return this.value.replace(/^\s+|\s+$/g, '');
	},

	_trav: function(el, selector, fce) {
		selector = selector.split('.');
		while (el && !(el.nodeType === 1 &&
			(!selector[0] || el.tagName.toLowerCase() === selector[0]) &&
			(!selector[1] || fn.hasClass.call(el, selector[1])))) {
			el = el[fce];
		}
		return $(el);
	},

	closest: function(selector) {
		return fn._trav(this, selector, 'parentNode');
	},

	prev: function(selector) {
		return fn._trav(this.previousSibling, selector, 'previousSibling');
	},

	next: function(selector) {
		return fn._trav(this.nextSibling, selector, 'nextSibling');
	},

	// returns total offset for element
	offset: function(coords) {
		var el = this, ofs = coords ? {left: -coords.left || 0, top: -coords.top || 0} : fn.position.call(el);
		while (el = el.offsetParent) { ofs.left += el.offsetLeft; ofs.top += el.offsetTop; }

		if (coords) {
			fn.position.call(this, {left: -ofs.left, top: -ofs.top});
		} else {
			return ofs;
		}
	},

	// returns current position or move to new position
	position: function(coords) {
		if (coords) {
			if (this.nette && this.nette.onmove) {
				this.nette.onmove.call(this, coords);
			}
			this.style.left = (coords.left || 0) + 'px';
			this.style.top = (coords.top || 0) + 'px';
		} else {
			return {left: this.offsetLeft, top: this.offsetTop, width: this.offsetWidth, height: this.offsetHeight};
		}
	},

	// makes element draggable
	draggable: function(options) {
		var $el = $(this), dE = document.documentElement, started;
		options = options || {};

		$(options.handle || this).bind('mousedown', function(e) {
			e.preventDefault();
			e.stopPropagation();

			if (fn.draggable.binded) { // missed mouseup out of window?
				return dE.onmouseup(e);
			}

			var deltaX = $el[0].offsetLeft - e.clientX, deltaY = $el[0].offsetTop - e.clientY;
			fn.draggable.binded = true;
			started = false;

			dE.onmousemove = function(e) {
				e = e || event;
				if (!started) {
					if (options.draggedClass) {
						$el.addClass(options.draggedClass);
					}
					if (options.start) {
						options.start(e, $el);
					}
					started = true;
				}
				$el.position({left: e.clientX + deltaX, top: e.clientY + deltaY});
				return false;
			};

			dE.onmouseup = function(e) {
				if (started) {
					if (options.draggedClass) {
						$el.removeClass(options.draggedClass);
					}
					if (options.stop) {
						options.stop(e || event, $el);
					}
				}
				fn.draggable.binded = dE.onmousemove = dE.onmouseup = null;
				return false;
			};

		}).bind('click', function(e) {
			if (started) {
				e.stopImmediatePropagation();
				preventClick = false;
			}
		});
	}
});

})();
