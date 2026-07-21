jQuery(function ($) {



	var $previewId = '.sp_hover_preview';

	var sizeCache = {};

	var hoverToken = 0;

	var lastPointer = { x: 0, y: 0 };

	var PREVIEW_OFFSET = 20;

	var VIEWPORT_MARGIN = 10;



	const preview = jQuery(

		'<div class="image-hover-preview">' +

			'<div class="image-hover-preview__frame">' +

				'<img src="" alt="">' +

			'</div>' +

			'<span class="image-hover-preview__size"></span>' +

		'</div>'

	);



	function formatFileSize(bytes) {

		bytes = parseInt(bytes, 10);

		if (!bytes || bytes <= 0) {

			return '';

		}



		if (bytes >= 1048576) {

			var mb = bytes / 1048576;

			var mbText = mb >= 10 ? String(Math.round(mb)) : String(Math.round(mb * 10) / 10);

			return mbText + 'mb';

		}



		var kb = bytes / 1024;

		var kbText = kb >= 100 ? String(Math.round(kb)) : String(Math.round(kb * 10) / 10);

		return kbText + 'kb';

	}



	function normalizeImageType(typeOrExt) {

		var type = String(typeOrExt || '').toLowerCase().trim();

		if (type === '') {

			return '';

		}

		if (type.indexOf('/') !== -1) {

			type = type.split('/').pop();

		}

		if (type === 'jpeg') {

			return 'jpg';

		}

		return type;

	}



	function imageTypeFromUrl(url) {

		if (!url) {

			return '';

		}

		var path = String(url).split('?')[0].split('#')[0];

		var match = path.match(/\.([a-z0-9]+)$/i);

		if (!match || !match[1]) {

			return '';

		}

		return normalizeImageType(match[1]);

	}



	function formatDimensions(width, height) {

		width = parseInt(width, 10);

		height = parseInt(height, 10);

		if (width > 0 && height > 0) {

			return width + 'x' + height;

		}

		return '';

	}



	function formatPreviewLabel(meta) {

		var parts = [];

		var sizePart = formatFileSize(meta.bytes);

		var typePart = normalizeImageType(meta.type);

		var dimPart = formatDimensions(meta.width, meta.height);



		if (sizePart) {

			parts.push(sizePart);

		}

		if (typePart) {

			parts.push(typePart);

		}

		if (dimPart) {

			parts.push(dimPart);

		}



		return parts.join(', ');

	}



	function readCachedMeta(cacheKey) {

		if (!cacheKey || !sizeCache[cacheKey]) {

			return null;

		}

		var cached = sizeCache[cacheKey];

		if (typeof cached === 'number') {

			return { bytes: cached, type: '', width: 0, height: 0 };

		}

		if (typeof cached === 'object') {

			return cached;

		}

		return null;

	}



	function storeCachedMeta(cacheKey, meta, alsoKeys) {

		if (!cacheKey || !meta) {

			return;

		}

		sizeCache[cacheKey] = meta;

		if (alsoKeys && alsoKeys.length) {

			alsoKeys.forEach(function(key) {

				if (key) {

					sizeCache[key] = meta;

				}

			});

		}

	}



	function readDimensionsFromElement($el) {

		return {

			width: parseInt($el.attr('data-image-width'), 10) || 0,

			height: parseInt($el.attr('data-image-height'), 10) || 0

		};

	}



	function getPreviewTarget($target) {

		if ($target.is('img')) {

			return $target;

		}

		var $img = $target.find('img').first();

		return $img.length ? $img : $target;

	}



	function resolvePreviewUrl($target) {

		var $el = getPreviewTarget($target);

		var src = $el.attr('src') || '';

		var dataFull = $el.attr('data-full') || '';

		var dataImg = $el.attr('data-img') || '';



		if (src && dataImg && src !== dataImg) {

			return dataFull && src === dataFull ? dataFull : src;

		}



		return dataFull || dataImg || src || '';

	}



	function clearSizeCacheForElement($el) {

		if (!$el || !$el.length) {

			return;

		}

		var id = parseInt($el.attr('data-id'), 10);

		if (id > 0) {

			delete sizeCache['id:' + id];

		}

		[$el.attr('data-full'), $el.attr('data-img'), $el.attr('src')].forEach(function(url) {

			if (url) {

				delete sizeCache[url];

			}

		});

	}



	function resolveImageMeta($el, url, callback) {

		var attrSize = parseInt($el.attr('data-file-size'), 10);

		var attrType = normalizeImageType($el.attr('data-file-type') || $el.attr('data-mime') || '');

		var urlType = imageTypeFromUrl(url);

		var dims = readDimensionsFromElement($el);

		var id = parseInt($el.attr('data-id'), 10);

		var baseMeta = {

			bytes: attrSize > 0 ? attrSize : 0,

			type: attrType || urlType,

			width: dims.width,

			height: dims.height

		};



		var cachedById = id > 0 ? readCachedMeta('id:' + id) : null;

		if (cachedById) {

			callback(cachedById);

			return;

		}



		var cachedByUrl = url ? readCachedMeta(url) : null;

		if (cachedByUrl) {

			callback(cachedByUrl);

			return;

		}



		if (attrSize > 0) {

			callback(baseMeta);

			return;

		}



		if (!url) {

			callback(baseMeta);

			return;

		}



		$.ajax({

			url: url,

			type: 'HEAD',

			cache: true

		}).done(function(data, textStatus, jqXHR) {

			var len = parseInt(jqXHR.getResponseHeader('Content-Length'), 10) || 0;

			var mime = jqXHR.getResponseHeader('Content-Type') || '';

			var meta = {

				bytes: len,

				type: normalizeImageType(mime) || baseMeta.type,

				width: baseMeta.width,

				height: baseMeta.height

			};

			storeCachedMeta(url, meta, id > 0 ? ['id:' + id] : []);

			callback(meta);

		}).fail(function() {

			callback(baseMeta);

		});

	}



	function renderPreviewLabel(meta, token) {

		if (token !== hoverToken) {

			return;

		}

		var label = formatPreviewLabel(meta);

		if (label) {

			preview.find('.image-hover-preview__size').text(label);

			positionPreview(lastPointer.x, lastPointer.y);

		}

	}



	function positionPreview(clientX, clientY) {

		lastPointer.x = clientX;

		lastPointer.y = clientY;



		var previewWidth = preview.outerWidth();

		var previewHeight = preview.outerHeight();

		var viewportWidth = jQuery(window).width();

		var viewportHeight = jQuery(window).height();

		var maxX = viewportWidth - VIEWPORT_MARGIN;

		var maxY = viewportHeight - VIEWPORT_MARGIN;

		var left = clientX + PREVIEW_OFFSET;

		var top = clientY + PREVIEW_OFFSET;



		if (left + previewWidth > maxX) {

			left = clientX - previewWidth - PREVIEW_OFFSET;

		}

		if (left < VIEWPORT_MARGIN) {

			left = Math.max(VIEWPORT_MARGIN, maxX - previewWidth);

		}



		if (top + previewHeight > maxY) {

			top = clientY - previewHeight - PREVIEW_OFFSET;

		}

		if (top < VIEWPORT_MARGIN) {

			top = Math.max(VIEWPORT_MARGIN, maxY - previewHeight);

		}



		preview.css({

			top: top,

			left: left

		});

	}



	function updatePreviewSize($el, url) {

		var token = ++hoverToken;

		var $previewImg = preview.find('img');



		preview.find('.image-hover-preview__size').text('');

		$previewImg.off('load.spHoverPreview');



		resolveImageMeta($el, url, function(meta) {

			if (token !== hoverToken) {

				return;

			}



			renderPreviewLabel(meta, token);



			$previewImg.on('load.spHoverPreview', function() {

				if (token !== hoverToken) {

					return;

				}



				var naturalWidth = this.naturalWidth || 0;

				var naturalHeight = this.naturalHeight || 0;

				if (naturalWidth > 0 && naturalHeight > 0) {

					meta.width = naturalWidth;

					meta.height = naturalHeight;



					var id = parseInt($el.attr('data-id'), 10);

					storeCachedMeta(url, meta, id > 0 ? ['id:' + id] : []);

					renderPreviewLabel(meta, token);

					positionPreview(lastPointer.x, lastPointer.y);

				}

			});



			if ($previewImg[0] && $previewImg[0].complete && $previewImg[0].naturalWidth > 0) {

				$previewImg.trigger('load.spHoverPreview');

			}

		});

	}



	jQuery('body').append(preview);



	jQuery(document).on('sp:image-preview:invalidate', function(e, $img) {

		clearSizeCacheForElement($img);

		if ($img && $img.length && $img.is(':hover')) {

			var full = resolvePreviewUrl($img);

			preview.find('img').attr('src', full);

			updatePreviewSize($img, full);

			preview.show();

			positionPreview(lastPointer.x, lastPointer.y);

			return;

		}

		hoverToken++;

		preview.hide();

		preview.find('img').off('load.spHoverPreview').attr('src', '');

		preview.find('.image-hover-preview__size').text('');

	});



	jQuery(document).on('mouseenter', $previewId, function (e) {

		var $target = jQuery(this);

		var $previewTarget = getPreviewTarget($target);

		lastPointer.x = e.clientX;

		lastPointer.y = e.clientY;

		var full = resolvePreviewUrl($target);



		preview.find('img').attr('src', full);

		updatePreviewSize($previewTarget, full);

		preview.show();

		positionPreview(e.clientX, e.clientY);

	});



	jQuery(document).on('mousemove', $previewId, function (e) {

		positionPreview(e.clientX, e.clientY);

	});



	jQuery(document).on('mouseleave', $previewId, function () {

		hoverToken++;

		preview.hide();

		preview.find('img').off('load.spHoverPreview').attr('src', '');

		preview.find('.image-hover-preview__size').text('');

	});

});


