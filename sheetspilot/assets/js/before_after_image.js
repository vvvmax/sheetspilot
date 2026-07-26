/**
 * SheetsPilotBeforeAfter - draggable before/after image comparison slider.
 *
 * Usage:
 *   var instance = new SheetsPilotBeforeAfter(containerEl, {
 *       direction: 'horizontal', // or 'vertical'
 *       trigger: 'click',        // or 'hover'
 *       initialPosition: 50      // percent
 *   });
 *   instance.destroy(); // unbind + remove generated nodes
 *
 * containerEl must have exactly two <img> (or <picture>) children when the
 * instance is created: the first one is treated as "before" (gets wrapped
 * and revealed/hidden by the drag handle), the second one is treated as
 * "after" (stays as a plain, untouched child).
 */
(function (window, $) {
	'use strict';

	function SheetsPilotBeforeAfter(element, options) {

		this.options = $.extend({}, SheetsPilotBeforeAfter.defaults, options || {});
		this.element = element || document.querySelector('.sp-before-after');

		if (!this.element) {
			return;
		}

		this.init();
	}

	SheetsPilotBeforeAfter.defaults = {
		dragElementSelector: '.cocoen-drag',
		dragCallback: null,
		direction: 'horizontal',
		trigger: 'click',
		initialPosition: 50
	};

	var proto = SheetsPilotBeforeAfter.prototype;

	proto.init = function () {

		this.lazyLoadProtection();
		this.createElements();

		if (!this.beforeElement) {
			return;
		}

		this.addEventListeners();
		this.dimensions();
	};

	/**
	 * Some themes/plugins lazy-load images via a "loading" attribute; force
	 * eager rendering so drag math (computed width/height) is correct right away.
	 */
	proto.lazyLoadProtection = function () {

		$(this.element).find('img').each(function () {

			var $img = $(this);
			var loadingAttr = $img.attr('loading');

			if (loadingAttr === undefined || loadingAttr === false || loadingAttr === 'lazy') {
				$img.removeAttr('loading');
			}
		});
	};

	proto.createElements = function () {

		var dragEl = document.createElement('span');
		dragEl.className = this.options.dragElementSelector.replace('.', '');
		this.element.appendChild(dragEl);

		dragEl.setAttribute('role', 'slider');
		dragEl.setAttribute('tabindex', '0');
		dragEl.setAttribute('aria-valuemin', '0');
		dragEl.setAttribute('aria-valuemax', '100');
		dragEl.setAttribute('aria-valuenow', this.options.initialPosition);
		dragEl.setAttribute('aria-label', 'Reveal after image');

		if (this.element.querySelector('img:first-child')) {
			this.defineElements('img:first-child');
		} else if (this.element.querySelector('picture:first-child')) {
			this.defineElements('picture:first-child');
		}
	};

	proto.defineElements = function (firstChildSelector) {

		var wrapper = document.createElement('div');
		var firstChild = this.element.querySelector(firstChildSelector);

		if (!firstChild) {
			return;
		}

		wrapper.appendChild(firstChild.cloneNode(true));
		firstChild.parentNode.replaceChild(wrapper, firstChild);

		this.dragElement = this.element.querySelector(this.options.dragElementSelector);
		this.beforeElement = this.element.querySelector('div:first-child');
		this.beforeImage = this.beforeElement.querySelector('img');
		this.labelBefore = this.element.querySelector('.cocoen-label-before');
		this.labelAfter = this.element.querySelector('.cocoen-label-after');
	};

	proto.addEventListeners = function () {

		this._onTap = this.onTap.bind(this);
		this._onMouseover = this.onMouseover.bind(this);
		this._onMousemove = this.onMousemove.bind(this);
		this._onMouseleave = this.onMouseleave.bind(this);
		this._onTouchDrag = this.onTouchDrag.bind(this);
		this._onKeydown = this.onKeydown.bind(this);
		this._onMouseDragStart = this.onMouseDragStart.bind(this);
		this._onTouchDragStart = this.onTouchDragStart.bind(this);
		this._onDragEnd = this.onDragEnd.bind(this);
		this._onDimensions = this.dimensions.bind(this);

		this.element.addEventListener('click', this._onTap);
		this.element.addEventListener('mouseover', this._onMouseover);
		this.element.addEventListener('mousemove', this._onMousemove);
		this.element.addEventListener('mouseleave', this._onMouseleave);
		this.element.addEventListener('touchmove', this._onTouchDrag);
		this.dragElement.addEventListener('keydown', this._onKeydown);
		this.dragElement.addEventListener('mousedown', this._onMouseDragStart);
		this.dragElement.addEventListener('touchstart', this._onTouchDragStart);
		this.dragElement.addEventListener('touchend', this._onDragEnd);

		window.addEventListener('mouseup', this._onDragEnd);
		window.addEventListener('resize', this._onDimensions);

		$(this.element).on('delayed_start', this._onDimensions);
		$(this.labelBefore).on('click', this.onBeforeLabelClick.bind(this));
		$(this.labelAfter).on('click', this.onAfterLabelClick.bind(this));
	};

	proto.dimensions = function () {

		this.elementWidth = parseInt(window.getComputedStyle(this.element).width, 10);
		this.elementHeight = parseInt(window.getComputedStyle(this.element).height, 10);
		this.elementOffsetLeft = this.element.getBoundingClientRect().left + document.body.scrollLeft;
		this.elementOffsetTop = window.pageYOffset + this.element.getBoundingClientRect().top + document.body.scrollTop;
		this.beforeImage.style.width = this.elementWidth + 'px';
		this.dragElementWidth = parseInt(window.getComputedStyle(this.dragElement).width, 10);
		this.dragElementHeight = parseInt(window.getComputedStyle(this.dragElement).height, 10);
		this.minLeftPos = this.elementOffsetLeft;
		this.minTopPos = this.elementOffsetTop;
		this.maxLeftPos = this.elementOffsetLeft + this.elementWidth - this.dragElementWidth;
		this.maxTopPos = this.elementOffsetTop + this.elementHeight - this.dragElementHeight;
		this.startX = 0;
		this.startY = 0;
		this.isOverElement = false;
	};

	proto.onBeforeLabelClick = function () {

		var $drag = $(this.dragElement);
		var $before = $(this.beforeElement);

		$drag.css('transition', 'all .3s');
		$before.css('transition', 'all .3s');

		if (this.options.direction === 'horizontal') {
			this.leftPos = this.maxLeftPos;
			this.requestDrag();
		}
		if (this.options.direction === 'vertical') {
			this.topPos = this.maxTopPos;
			this.requestDrag();
		}

		setTimeout(function () {
			$drag.css('transition', '');
			$before.css('transition', '');
		}, 400);
	};

	proto.onAfterLabelClick = function () {

		var $drag = $(this.dragElement);
		var $before = $(this.beforeElement);

		$drag.css('transition', 'all .3s');
		$before.css('transition', 'all .3s');

		if (this.options.direction === 'horizontal') {
			this.leftPos = this.minLeftPos;
			this.requestDrag();
		}
		if (this.options.direction === 'vertical') {
			this.topPos = this.minTopPos;
			this.requestDrag();
		}

		setTimeout(function () {
			$drag.css('transition', '');
			$before.css('transition', '');
		}, 400);
	};

	proto.onMouseover = function (e) {

		if (this.options.trigger === 'hover' && this.isOverElement === false) {

			if (this.options.direction === 'horizontal') {
				this.leftPos = e.pageX ? e.pageX : e.touches[0].pageX;
				this.requestDrag();
			}
			if (this.options.direction === 'vertical') {
				this.topPos = e.pageY ? e.pageY : e.touches[0].pageY;
				this.requestDrag();
			}
			this.isOverElement = true;
		}
	};

	proto.onMousemove = function (e) {

		if (this.options.trigger === 'hover') {

			if (this.options.direction === 'horizontal') {
				this.leftPos = e.pageX ? e.pageX : e.touches[0].pageX;
				this.requestDrag();
			}
			if (this.options.direction === 'vertical') {
				this.topPos = e.pageY ? e.pageY : e.touches[0].pageY;
				this.requestDrag();
			}
		} else {
			this.onMouseDrag(e);
		}
	};

	proto.onMouseleave = function () {

		if (this.options.trigger === 'hover') {
			this.startX = 0;
			this.startY = 0;
			this.isDragging = false;
			this.isOverElement = false;
		}
	};

	proto.onTap = function (e) {

		e.preventDefault();

		if ($(e.target).hasClass('cocoen-label-before') || $(e.target).hasClass('cocoen-label-after')) {
			return;
		}

		if (this.options.direction === 'horizontal') {
			this.leftPos = e.pageX ? e.pageX : e.touches[0].pageX;
			this.requestDrag();
		}
		if (this.options.direction === 'vertical') {
			this.topPos = e.pageY ? e.pageY : e.touches[0].pageY;
			this.requestDrag();
		}
	};

	proto.onTouchDragStart = function (e) {

		if (e.touches === undefined) {
			return;
		}

		this.startX = e.touches[0].pageX;
		this.startY = e.touches[0].pageY;

		e.preventDefault();

		if (this.options.direction === 'horizontal') {
			var t = e.pageX ? e.pageX : e.touches[0].pageX;
			var n = this.dragElement.getBoundingClientRect().left + document.body.scrollLeft;
			this.posX = n + this.dragElementWidth - t;
			this.isDragging = true;
		}

		if (this.options.direction === 'vertical') {
			var startY = e.pageY ? e.pageY : e.touches[0].pageY;
			var dragTop = window.pageYOffset + this.dragElement.getBoundingClientRect().top + document.body.scrollTop;
			this.posY = dragTop + this.dragElementHeight - startY;
			this.isDragging = true;
		}

		$(this.labelBefore).css('opacity', 0);
		$(this.labelAfter).css('opacity', 0);
	};

	proto.onMouseDragStart = function (e) {

		e.preventDefault();

		if (this.options.direction === 'horizontal') {
			var t = e.pageX ? e.pageX : e.touches[0].pageX;
			var n = this.dragElement.getBoundingClientRect().left + document.body.scrollLeft;
			this.posX = n + this.dragElementWidth - t;
			this.isDragging = true;
		}

		if (this.options.direction === 'vertical') {
			var startY = e.pageY ? e.pageY : e.touches[0].pageY;
			var dragTop = window.pageYOffset + this.dragElement.getBoundingClientRect().top + document.body.scrollTop;
			this.posY = dragTop + this.dragElementHeight - startY;
			this.isDragging = true;
		}

		$(this.labelBefore).css('opacity', 0);
		$(this.labelAfter).css('opacity', 0);
	};

	proto.onDragEnd = function (e) {

		this.startX = 0;
		this.startY = 0;
		e.preventDefault();
		this.isDragging = false;

		$(this.labelBefore).css('opacity', '');
		$(this.labelAfter).css('opacity', '');
	};

	proto.onTouchDrag = function (e) {

		if (e.touches === undefined) {
			return;
		}

		var deltaX = Math.abs((e.touches[0].pageX - this.startX) / e.touches[0].pageX);
		var deltaY = Math.abs((e.touches[0].pageY - this.startY) / e.touches[0].pageY);

		if (this.options.direction === 'horizontal') {
			if (deltaX > deltaY && e.cancelable) {
				e.preventDefault();
				if (this.isDragging) {
					this.moveX = e.pageX ? e.pageX : e.touches[0].pageX;
					this.leftPos = this.moveX + this.posX - this.dragElementWidth;
					this.requestDrag();
				}
			}
		}

		if (this.options.direction === 'vertical') {
			if (e.cancelable) {
				e.preventDefault();
				if (this.isDragging) {
					this.moveY = e.pageY ? e.pageY : e.touches[0].pageY;
					this.topPos = this.moveY + this.posY - this.dragElementHeight;
					this.requestDrag();
				}
			}
		}
	};

	proto.onMouseDrag = function (e) {

		e.preventDefault();

		if (this.options.direction === 'horizontal' && this.isDragging) {
			this.moveX = e.pageX ? e.pageX : e.touches[0].pageX;
			this.leftPos = this.moveX + this.posX - this.dragElementWidth;
			this.requestDrag();
		}

		if (this.options.direction === 'vertical' && this.isDragging) {
			this.moveY = e.pageY ? e.pageY : e.touches[0].pageY;
			this.topPos = this.moveY + this.posY - this.dragElementHeight;
			this.requestDrag();
		}
	};

	proto.onKeydown = function (ev) {

		var step = 5;
		var current = parseInt(this.dragElement.getAttribute('aria-valuenow'), 10);
		var key = ev.key || ev.keyCode;

		if (this.options.direction === 'horizontal') {
			if (key === 'ArrowLeft' || key === 'Left' || key === 'PageDown') {
				current = Math.max(0, current - step);
			} else if (key === 'ArrowRight' || key === 'Right' || key === 'PageUp') {
				current = Math.min(100, current + step);
			} else {
				return;
			}
			this.beforeElement.style.width = current + '%';
			this.dragElement.style.left = current + '%';
		}

		if (this.options.direction === 'vertical') {
			if (key === 'ArrowUp' || key === 'Up') {
				current = Math.min(100, current - step);
			} else if (key === 'ArrowDown' || key === 'Down') {
				current = Math.max(0, current + step);
			} else {
				return;
			}
			this.beforeElement.style.height = current + '%';
			this.dragElement.style.top = current + '%';
		}

		this.dragElement.setAttribute('aria-valuenow', current);
		ev.preventDefault();
	};

	proto.drag = function () {

		if (this.options.direction === 'horizontal') {

			if (this.leftPos < this.minLeftPos) {
				this.leftPos = this.minLeftPos;
			} else if (this.leftPos > this.maxLeftPos) {
				this.leftPos = this.maxLeftPos;
			}

			var ratio = (this.leftPos + this.dragElementWidth / 2 - this.elementOffsetLeft) / this.elementWidth;
			var pct = (100 * ratio) + '%';

			this.dragElement.style.left = pct;
			this.beforeElement.style.width = pct;

			if (this.options.dragCallback) {
				this.options.dragCallback(ratio);
			}

			this.dragElement.setAttribute('aria-valuenow', Math.round(parseFloat(pct)));
		}

		if (this.options.direction === 'vertical') {

			if (this.topPos < this.minTopPos) {
				this.topPos = this.minTopPos;
			} else if (this.topPos > this.maxTopPos) {
				this.topPos = this.maxTopPos;
			}

			var ratioV = (this.topPos + this.dragElementHeight / 2 - this.elementOffsetTop) / this.elementHeight;
			var pctV = (100 * ratioV) + '%';

			this.dragElement.style.top = pctV;
			this.beforeElement.style.height = pctV;

			if (this.options.dragCallback) {
				this.options.dragCallback(ratioV);
			}

			this.dragElement.setAttribute('aria-valuenow', Math.round(parseFloat(pctV)));
		}
	};

	proto.requestDrag = function () {
		window.requestAnimationFrame(this.drag.bind(this));
	};

	/**
	 * Unbind all listeners and remove the drag handle + before-image wrapper
	 * this instance created, restoring the element to its pre-init markup
	 * (the original "after" image is left untouched).
	 */
	proto.destroy = function () {

		if (!this.element) {
			return;
		}

		this.element.removeEventListener('click', this._onTap);
		this.element.removeEventListener('mouseover', this._onMouseover);
		this.element.removeEventListener('mousemove', this._onMousemove);
		this.element.removeEventListener('mouseleave', this._onMouseleave);
		this.element.removeEventListener('touchmove', this._onTouchDrag);

		if (this.dragElement) {
			this.dragElement.removeEventListener('keydown', this._onKeydown);
			this.dragElement.removeEventListener('mousedown', this._onMouseDragStart);
			this.dragElement.removeEventListener('touchstart', this._onTouchDragStart);
			this.dragElement.removeEventListener('touchend', this._onDragEnd);
		}

		window.removeEventListener('mouseup', this._onDragEnd);
		window.removeEventListener('resize', this._onDimensions);

		$(this.element).off('delayed_start', this._onDimensions);
		$(this.labelBefore).off('click');
		$(this.labelAfter).off('click');

		if (this.beforeElement && this.beforeElement.parentNode) {
			this.beforeElement.parentNode.removeChild(this.beforeElement);
		}
		if (this.dragElement && this.dragElement.parentNode) {
			this.dragElement.parentNode.removeChild(this.dragElement);
		}

		this.beforeElement = null;
		this.beforeImage = null;
		this.dragElement = null;
	};

	window.SheetsPilotBeforeAfter = SheetsPilotBeforeAfter;

})(window, jQuery);
