/* global jQuery, g_doublyNonce, g_urlAjaxActionsSheetsPilot, g_doublyAdmin, wp, g_promptTesterCompressLibs */



(function(window, $){

	'use strict';



	window.sheetspilotPromptTesterInit = function(){

		var $btn = $('#ubai_prompt_tester_send');

		var $loader = $('#ubai_prompt_tester_loader');

		var $imageBtn = $('#ubai_prompt_tester_image_send');

		var $imageLoader = $('#ubai_prompt_tester_image_loader');

		var $req = $('#ubai_prompt_tester_request');

		var $res = $('#ubai_prompt_tester_response');

		var $debugWrap = $('#ubai_prompt_tester_debug_wrap');

		var $debug = $('#ubai_prompt_tester_debug');

		var $imageResultWrap = $('#ubai_prompt_tester_image_result_wrap');

		var $imageResult = $('#ubai_prompt_tester_image_result');

		var $imageMode = $('#ubai_prompt_tester_image_mode');

		var $generateFields = $('#ubai_prompt_tester_image_generate_fields');

		var $editFields = $('#ubai_prompt_tester_image_edit_fields');

		var $selectImageBtn = $('#ubai_prompt_tester_select_image');

		var $clearImageBtn = $('#ubai_prompt_tester_clear_image');

		var $imageThumbWrap = $('#ubai_prompt_tester_image_thumb_wrap');

		var $imageThumb = $('#ubai_prompt_tester_image_thumb');

		var $imageAttachmentId = $('#ubai_prompt_tester_image_attachment_id');

		var $imageUrl = $('#ubai_prompt_tester_image_url');

		var $compressBtn = $('#ubai_prompt_tester_compress_btn');

		var $compressLoader = $('#ubai_prompt_tester_compress_loader');

		var $compressSelectBtn = $('#ubai_prompt_tester_compress_select_image');

		var $compressClearBtn = $('#ubai_prompt_tester_compress_clear_image');

		var $compressThumbWrap = $('#ubai_prompt_tester_compress_thumb_wrap');

		var $compressThumb = $('#ubai_prompt_tester_compress_thumb');

		var $compressAttachmentId = $('#ubai_prompt_tester_compress_attachment_id');

		var $compressImageUrl = $('#ubai_prompt_tester_compress_image_url');

		var $compressSelectedSize = $('#ubai_prompt_tester_compress_selected_size');

		var $compressLibs = $('#ubai_prompt_tester_compress_libs');

		var $compressEngineWrap = $('#ubai_prompt_tester_compress_engine_wrap');

		var $compressEngineSelect = $('#ubai_prompt_tester_compress_engine_select');

		var $compressResultWrap = $('#ubai_prompt_tester_compress_result_wrap');

		var $compressBeforeImg = $('#ubai_prompt_tester_compress_before_img');

		var $compressAfterImg = $('#ubai_prompt_tester_compress_after_img');

		var $compressBeforeSize = $('#ubai_prompt_tester_compress_before_size');

		var $compressAfterSize = $('#ubai_prompt_tester_compress_after_size');

		var $compressEngine = $('#ubai_prompt_tester_compress_engine');

		var $compressSaved = $('#ubai_prompt_tester_compress_saved');



		if ($btn.length === 0){

			return;

		}



		function showAjaxErr(msg){

			if(window.g_doublyAdmin && typeof window.g_doublyAdmin.showErrorMessage === 'function'){

				window.g_doublyAdmin.showErrorMessage(msg);

				return;

			}

			/* eslint-disable no-alert */

			window.alert(typeof msg === 'string' ? msg : 'Ajax error');

			/* eslint-enable no-alert */

		}



		function formatBytesToKb(bytes){

			var n = parseInt(bytes, 10);

			if(isNaN(n) || n < 0){

				return '—';

			}

			return (n / 1024).toFixed(2) + ' KB';

		}



		function renderCompressLibraryStatus(){

			if(!$compressLibs.length){

				return;

			}

			var libs = window.g_promptTesterCompressLibs;

			if(!libs || typeof libs !== 'object'){

				return;

			}

			var gdOk = libs.gd_installed === true;

			var imagickOk = libs.imagick_installed === true;

			var hasAny = libs.has_any === true;

			var html = '<p><strong>Installed image libraries</strong></p>';

			html += '<p class="ubai-prompt-tester-compress-libs__item ' + (gdOk ? 'is-ok' : 'is-missing') + '">GD: ' + (gdOk ? 'installed' : 'not installed') + '</p>';

			html += '<p class="ubai-prompt-tester-compress-libs__item ' + (imagickOk ? 'is-ok' : 'is-missing') + '">Imagick: ' + (imagickOk ? 'installed' : 'not installed') + '</p>';

			if(!hasAny){

				html += '<p class="ubai-prompt-tester-compress-libs__item is-missing"><strong>Please install GD or Imagick (ImageMagick) on your server to use image compression.</strong></p>';

				$compressLibs.addClass('is-warning');

				$compressBtn.prop('disabled', true);

			}else{

				$compressLibs.removeClass('is-warning');

				$compressBtn.prop('disabled', false);

				if(gdOk && imagickOk){

					$compressEngineWrap.show();

					if(typeof libs.wp_preferred === 'string' && (libs.wp_preferred === 'gd' || libs.wp_preferred === 'imagick')){

						$compressEngineSelect.val(libs.wp_preferred);

					}

				}else{

					$compressEngineWrap.hide();

					if(gdOk){

						$compressEngineSelect.val('gd');

					}else if(imagickOk){

						$compressEngineSelect.val('imagick');

					}

				}

				if(typeof libs.wp_preferred === 'string' && libs.wp_preferred !== '' && !(gdOk && imagickOk)){

					html += '<p class="description">WordPress preferred editor: ' + libs.wp_preferred + '</p>';

				}

			}

			$compressLibs.html(html);

		}



		function hideCompressResults(){

			$compressResultWrap.hide();

			$compressBeforeImg.attr('src', '');

			$compressAfterImg.attr('src', '');

			$compressBeforeSize.text('');

			$compressAfterSize.text('');

			$compressEngine.text('');

			$compressSaved.text('');

		}



		function formatSavedKb(sizeBefore, sizeAfter, sizeSaved){

			var before = parseInt(sizeBefore, 10);

			var after = parseInt(sizeAfter, 10);

			var saved = parseInt(sizeSaved, 10);

			if(isNaN(saved)){

				if(!isNaN(before) && !isNaN(after)){

					saved = before - after;

				}else{

					return '';

				}

			}

			if(saved > 0){

				return 'Saved: ' + formatBytesToKb(saved);

			}

			if(saved === 0){

				return 'Saved: 0 KB (file size unchanged)';

			}

			return 'Saved: ' + formatBytesToKb(0) + ' (file grew by ' + formatBytesToKb(Math.abs(saved)) + ')';

		}



		function getCompressPreferredEngine(){

			var libs = window.g_promptTesterCompressLibs;

			if(libs && libs.gd_installed === true && libs.imagick_installed === true){

				return $compressEngineSelect.val() || 'gd';

			}

			if(libs && libs.gd_installed === true){

				return 'gd';

			}

			if(libs && libs.imagick_installed === true){

				return 'imagick';

			}

			return 'auto';

		}



		function showCompressResults(resp){

			if(typeof resp.before_url === 'string' && resp.before_url !== ''){

				$compressBeforeImg.attr('src', resp.before_url);

			}

			if(typeof resp.after_url === 'string' && resp.after_url !== ''){

				$compressAfterImg.attr('src', resp.after_url);

			}

			$compressBeforeSize.text('Size: ' + formatBytesToKb(resp.size_before));

			$compressAfterSize.text('Size: ' + formatBytesToKb(resp.size_after));

			var engine = typeof resp.engine === 'string' && resp.engine !== '' ? resp.engine : 'none';

			$compressEngine.text('Library used: ' + engine);

			$compressSaved.text(formatSavedKb(resp.size_before, resp.size_after, resp.size_saved));

			$compressResultWrap.show();

		}



		function buildAjaxRequestJson(clientAction, payload){

			return JSON.stringify({

				action: 'sheetspilot_ajax_actions',

				client_action: clientAction,

				data: payload

			}, null, 2);

		}



		function formatAjaxResponseText(resp, respText){

			if(resp && typeof resp.response_json === 'string' && resp.response_json !== ''){

				return resp.response_json;

			}

			try{

				return JSON.stringify(resp, null, 2);

			}catch(e){

				return typeof respText === 'string' ? respText : String(resp);

			}

		}



		function showAjaxErrorResponse(resp, respText, requestJson){

			if(typeof requestJson === 'string' && requestJson !== ''){

				$req.text(requestJson);

			}else if(resp && typeof resp.request_json === 'string' && resp.request_json !== ''){

				$req.text(resp.request_json);

			}

			$res.text(formatAjaxResponseText(resp, respText));

		}



		function hideDebug(){

			$debugWrap.hide();

			$debug.text('');

		}



		function showDebug(text){

			if(typeof text !== 'string' || text.replace(/^\s+|\s+$/g, '') === ''){

				return;

			}

			$debug.text(text);

			$debugWrap.show();

		}



		function clearOutputPanels(){

			$req.text('');

			$res.text('');

			$imageResultWrap.hide();

			$imageResult.attr('src', '');

			hideCompressResults();

			hideDebug();

		}



		function showAjaxSuccess(resp, options){

			options = options || {};

			if(typeof options.requestJson === 'string' && options.requestJson !== ''){

				$req.text(options.requestJson);

			}else{

				$req.text(typeof resp.request_json === 'string' ? resp.request_json : '');

			}

			var outResp = '';



			if(typeof resp.response_json === 'string'){

				outResp = resp.response_json;

			}

			if(!outResp && typeof resp.response_body === 'string'){

				outResp = resp.response_body;

			}

			if(!outResp){

				try{

					outResp = JSON.stringify(resp, null, 2);

				}catch(e){

					outResp = String(resp);

				}

			}

			$res.text(outResp);



			if(typeof resp.image_url === 'string' && resp.image_url !== ''){

				$imageResult.attr('src', resp.image_url);

				$imageResultWrap.show();

			}

		}



		function handleAjaxResponseText(respText, clientAction, options){

			options = options || {};

			if(typeof respText !== 'string' || respText === ''){

				showAjaxErr('Empty ajax response');

				return;

			}



			var resp = null;

			try{

				resp = JSON.parse(respText);

			}catch(parseErr){

				$req.text(typeof options.requestJson === 'string' ? options.requestJson : '');

				$res.text(respText);

				showDebug(respText);

				if(window.console && typeof console.error === 'function'){

					console.error('prompt_tester non-JSON response', respText);

				}

				return;

			}



			if(!resp || resp.success === undefined){

				$req.text(typeof options.requestJson === 'string' ? options.requestJson : '');

				$res.text(respText);

				showDebug(respText);

				return;

			}



			if(resp.success === false){

				showAjaxErrorResponse(resp, respText, options.requestJson);

				if(typeof resp.debug === 'string' && resp.debug !== ''){

					showDebug(resp.debug);

				}else if(typeof resp.debug_text === 'string' && resp.debug_text !== ''){

					showDebug(resp.debug_text);

				}

				showAjaxErr(resp.message || 'Request failed');

				return;

			}



			if(typeof resp.debug === 'string' && resp.debug !== ''){

				showDebug(resp.debug);

			}else if(typeof resp.debug_text === 'string' && resp.debug_text !== ''){

				showDebug(resp.debug_text);

			}



			showAjaxSuccess(resp, options);



			if(window.console && typeof console.debug === 'function'){

				console.debug(clientAction + ' OK', resp);

			}

		}



		function runAjax(clientAction, payload, $activeLoader){

			clearOutputPanels();

			$activeLoader.show();

			var requestJson = '';

			try{

				requestJson = buildAjaxRequestJson(clientAction, payload);

			}catch(e){

				requestJson = '';

			}



			return $.ajax({

				type: 'post',

				url: window.g_urlAjaxActionsSheetsPilot,

				dataType: 'text',

				data: {

					action: 'sheetspilot_ajax_actions',

					client_action: clientAction,

					nonce: window.g_doublyNonce,

					data: JSON.stringify(payload)

				},

				complete: function(){

					$activeLoader.hide();

				},

				success: function(respText){

					handleAjaxResponseText(respText, clientAction, { requestJson: requestJson });

				},

				error: function(jqXHR, textStatus){

					if(jqXHR && typeof jqXHR.responseText === 'string' && jqXHR.responseText !== ''){

						handleAjaxResponseText(jqXHR.responseText, clientAction, { requestJson: requestJson });

						return;

					}

					showAjaxErr('Ajax error: ' + textStatus);

				}

			});

		}



		$('.ubai-prompt-tester-tabs .nav-tab').on('click', function(e){

			e.preventDefault();

			var tab = $(this).data('tab');



			$('.ubai-prompt-tester-tabs .nav-tab').removeClass('nav-tab-active');

			$(this).addClass('nav-tab-active');

			$('.ubai-prompt-tester-tab-panel').hide();

			$('.ubai-prompt-tester-tab-panel[data-tab-panel="' + tab + '"]').show();

		});



		$imageMode.on('change', function(){

			var mode = $(this).val();

			if(mode === 'edit'){

				$generateFields.hide();

				$editFields.show();

			}else{

				$generateFields.show();

				$editFields.hide();

			}

		});



		function setSelectedImage(url, attachmentId){

			$imageUrl.val(typeof url === 'string' ? url : '');

			$imageAttachmentId.val(attachmentId ? String(attachmentId) : '');

			if(url){

				$imageThumb.attr('src', url);

				$imageThumbWrap.show();

				$clearImageBtn.show();

			}else{

				$imageThumb.attr('src', '');

				$imageThumbWrap.hide();

				$clearImageBtn.hide();

			}

		}



		$selectImageBtn.on('click', function(){

			if(typeof window.wp === 'undefined' || typeof window.wp.media === 'undefined'){

				showAjaxErr('WordPress media library is not available.');

				return;

			}



			var frame = window.wp.media({

				title: 'Select image',

				multiple: false,

				library: { type: 'image' },

				button: { text: 'Use this image' }

			});



			frame.on('select', function(){

				var attachment = frame.state().get('selection').first().toJSON();

				if(!attachment || !attachment.url){

					return;

				}

				setSelectedImage(attachment.url, attachment.id);

			});



			frame.open();

		});



		$clearImageBtn.on('click', function(){

			setSelectedImage('', '');

		});



		function setSelectedCompressImage(url, attachmentId, fileSizeBytes){

			$compressImageUrl.val(typeof url === 'string' ? url : '');

			$compressAttachmentId.val(attachmentId ? String(attachmentId) : '');

			hideCompressResults();

			if(url){

				$compressThumb.attr('src', url);

				$compressThumbWrap.show();

				$compressClearBtn.show();

				if(typeof fileSizeBytes === 'number' && !isNaN(fileSizeBytes) && fileSizeBytes > 0){

					$compressSelectedSize.text('Current file size: ' + formatBytesToKb(fileSizeBytes));

				}else{

					$compressSelectedSize.text('');

				}

			}else{

				$compressThumb.attr('src', '');

				$compressThumbWrap.hide();

				$compressClearBtn.hide();

				$compressSelectedSize.text('');

			}

		}



		$compressSelectBtn.on('click', function(){

			if(typeof window.wp === 'undefined' || typeof window.wp.media === 'undefined'){

				showAjaxErr('WordPress media library is not available.');

				return;

			}



			var frame = window.wp.media({

				title: 'Select image',

				multiple: false,

				library: { type: 'image' },

				button: { text: 'Use this image' }

			});



			frame.on('select', function(){

				var attachment = frame.state().get('selection').first().toJSON();

				if(!attachment || !attachment.url){

					return;

				}

				var fileSize = 0;

				if(typeof attachment.filesizeInBytes === 'number'){

					fileSize = attachment.filesizeInBytes;

				}else if(typeof attachment.filesize === 'number'){

					fileSize = attachment.filesize;

				}

				setSelectedCompressImage(attachment.url, attachment.id, fileSize);

			});



			frame.open();

		});



		$compressClearBtn.on('click', function(){

			setSelectedCompressImage('', '', 0);

		});



		renderCompressLibraryStatus();

		function activatePromptTesterTab(tab){
			$('.ubai-prompt-tester-tabs .nav-tab').removeClass('nav-tab-active');
			$('.ubai-prompt-tester-tabs .nav-tab[data-tab="' + tab + '"]').addClass('nav-tab-active');
			$('.ubai-prompt-tester-tab-panel').hide();
			$('.ubai-prompt-tester-tab-panel[data-tab-panel="' + tab + '"]').show();
		}

		function prettyJson(value){
			try{
				return JSON.stringify(value, null, 2);
			}catch(e){
				return String(value);
			}
		}

		function setCheckerStatus(ok, message){
			var $status = $('#ubai_response_checker_status');
			$status.show().text(message || '');
			$status.css({
				background: ok ? '#edfaef' : '#fcf0f1',
				border: ok ? '1px solid #46b450' : '1px solid #d63638',
				color: ok ? '#1d6f42' : '#8a2424'
			});
		}

		$('#ubai_response_checker_run').on('click', function(){
			var aiResponse = $('#ubai_response_checker_ai').val() || '';
			var metadata = $('#ubai_response_checker_meta').val() || '';
			var $loader = $('#ubai_response_checker_loader');
			var $result = $('#ubai_response_checker_result');

			if($.trim(aiResponse) === ''){
				setCheckerStatus(false, 'Paste an AI Response first.');
				return;
			}

			$loader.show();
			$result.text('');
			$('#ubai_response_checker_status').hide();

			$.ajax({
				url: g_urlAjaxActionsSheetsPilot,
				method: 'POST',
				dataType: 'json',
				data: {
					action: 'sheetspilot_ajax_actions',
					client_action: 'response_checker_run',
					nonce: g_doublyNonce,
					data: JSON.stringify({
						ai_response: aiResponse,
						metadata: metadata
					})
				}
			}).done(function(resp){
				var payload = resp || {};
				// ajaxResponseSuccess merges fields onto the top-level response.
				if(payload.ok !== true && payload.ok !== false && payload.data && (payload.data.ok === true || payload.data.ok === false)){
					payload = payload.data;
				}

				$result.text(prettyJson(payload));
				if(payload && payload.ok){
					setCheckerStatus(true, 'OK — actions would return action="' + (payload.client && payload.client.action ? payload.client.action : '') + '" (mapping: ' + (payload.mapping_path || '') + ')');
				}else{
					setCheckerStatus(false, (payload && payload.error) ? payload.error : 'Check failed.');
				}
			}).fail(function(xhr){
				var msg = 'Request failed.';
				try{
					var parsed = JSON.parse(xhr.responseText);
					if(parsed && parsed.message){
						msg = parsed.message;
					}
				}catch(e){}
				setCheckerStatus(false, msg);
				$result.text(xhr.responseText || msg);
			}).always(function(){
				$loader.hide();
			});
		});

		function applyPreloadFromLog(){
			var preload = window.g_promptTesterPreload;
			if(!preload || typeof preload !== 'object'){
				return;
			}

			var tab = preload.tab === 'image' ? 'image' : (preload.tab === 'checker' ? 'checker' : 'text');
			activatePromptTesterTab(tab);

			if(tab === 'checker'){
				if(typeof preload.ai_response === 'string' && preload.ai_response !== ''){
					$('#ubai_response_checker_ai').val(preload.ai_response);
				}
				if(typeof preload.metadata === 'string' && preload.metadata !== ''){
					$('#ubai_response_checker_meta').val(preload.metadata);
				}
				return;
			}

			if(typeof preload.user_message === 'string' && preload.user_message !== ''){
				$('#ubai_prompt_tester_user').val(preload.user_message);
			}
			if(typeof preload.system_message === 'string' && preload.system_message !== ''){
				$('#ubai_prompt_tester_system').val(preload.system_message);
			}
			if(typeof preload.model === 'string' && preload.model !== ''){
				$('#ubai_prompt_tester_model').val(preload.model);
			}
			if(typeof preload.tool === 'string' && preload.tool !== ''){
				$('#ubai_prompt_tester_tool').val(preload.tool);
			}

			if(tab === 'image'){
				var imageMode = preload.image_mode === 'edit' ? 'edit' : 'generate';
				$imageMode.val(imageMode).trigger('change');

				if(imageMode === 'edit'){
					if(typeof preload.image_edit_prompt === 'string' && preload.image_edit_prompt !== ''){
						$('#ubai_prompt_tester_image_edit_prompt').val(preload.image_edit_prompt);
					}
					if(typeof preload.image_url === 'string' && preload.image_url !== ''){
						setSelectedImage(preload.image_url, preload.attachment_id || '');
					}
				}else{
					if(typeof preload.image_prompt === 'string' && preload.image_prompt !== ''){
						$('#ubai_prompt_tester_image_prompt').val(preload.image_prompt);
					}
					if(typeof preload.aspect_ratio === 'string' && preload.aspect_ratio !== ''){
						$('#ubai_prompt_tester_image_ratio').val(preload.aspect_ratio);
					}
					if(typeof preload.quality === 'string' && preload.quality !== ''){
						$('#ubai_prompt_tester_image_quality').val(preload.quality);
					}
					if(typeof preload.format === 'string' && preload.format !== ''){
						$('#ubai_prompt_tester_image_format').val(preload.format);
					}
				}
			}
		}

		applyPreloadFromLog();

		$btn.on('click', function(){

			var userMessage = $('#ubai_prompt_tester_user').val();

			var systemMessage = $('#ubai_prompt_tester_system').val();

			var model = $('#ubai_prompt_tester_model').val();

			var selectedTool = $('#ubai_prompt_tester_tool').val();



			if(typeof userMessage !== 'string' || userMessage.replace(/^\s+|\s+$/g,'') === ''){

				showAjaxErr('Please enter a user message.');

				return;

			}



			var payload = {

				user_message: userMessage,

				system_message: typeof systemMessage === 'string' ? systemMessage : '',

				model: typeof model === 'string' ? model : '',

				tool: typeof selectedTool === 'string' ? selectedTool : ''

			};



			runAjax('prompt_tester_run', payload, $loader);

		});



		$imageBtn.on('click', function(){

			var mode = $imageMode.val();

			var payload = {

				mode: mode

			};



			if(mode === 'edit'){

				var editPrompt = $('#ubai_prompt_tester_image_edit_prompt').val();

				var imageUrl = $imageUrl.val();

				var attachmentId = $imageAttachmentId.val();



				if(!attachmentId && (!imageUrl || String(imageUrl).replace(/^\s+|\s+$/g,'') === '')){

					showAjaxErr('Please select an image from the Media Library.');

					return;

				}

				if(typeof editPrompt !== 'string' || editPrompt.replace(/^\s+|\s+$/g,'') === ''){

					showAjaxErr('Please enter an edit prompt.');

					return;

				}



				payload.prompt = editPrompt;

				payload.image_url = typeof imageUrl === 'string' ? imageUrl : '';

				payload.attachment_id = attachmentId ? parseInt(attachmentId, 10) : 0;

			}else{

				var imagePrompt = $('#ubai_prompt_tester_image_prompt').val();

				if(typeof imagePrompt !== 'string' || imagePrompt.replace(/^\s+|\s+$/g,'') === ''){

					showAjaxErr('Please enter an image prompt.');

					return;

				}



				payload.prompt = imagePrompt;

				payload.aspect_ratio = $('#ubai_prompt_tester_image_ratio').val();

				payload.quality = $('#ubai_prompt_tester_image_quality').val();

				payload.format = $('#ubai_prompt_tester_image_format').val();

			}



			runAjax('prompt_tester_image_run', payload, $imageLoader);

		});



		$compressBtn.on('click', function(){

			var attachmentId = $compressAttachmentId.val();

			if(!attachmentId){

				showAjaxErr('Please select an image from the Media Library.');

				return;

			}



			hideCompressResults();

			$req.text('');

			$res.text('');

			$compressLoader.show();

			var compressPayload = {
				attachment_id: parseInt(attachmentId, 10),
				preferred_engine: getCompressPreferredEngine()
			};

			var compressRequestJson = '';

			try{

				compressRequestJson = buildAjaxRequestJson('prompt_tester_compress_image', compressPayload);

			}catch(e){

				compressRequestJson = '';

			}



			$.ajax({

				type: 'post',

				url: window.g_urlAjaxActionsSheetsPilot,

				dataType: 'text',

				data: {

					action: 'sheetspilot_ajax_actions',

					client_action: 'prompt_tester_compress_image',

					nonce: window.g_doublyNonce,

					data: JSON.stringify(compressPayload)

				},

				complete: function(){

					$compressLoader.hide();

				},

				success: function(respText){

					handleAjaxResponseText(respText, 'prompt_tester_compress_image', { requestJson: compressRequestJson });

					try{

						var resp = JSON.parse(respText);

						if(resp && resp.success === true){

							showCompressResults(resp);

						}

					}catch(e){

						// handled above

					}

				},

				error: function(jqXHR, textStatus){

					if(jqXHR && typeof jqXHR.responseText === 'string' && jqXHR.responseText !== ''){

						handleAjaxResponseText(jqXHR.responseText, 'prompt_tester_compress_image', { requestJson: compressRequestJson });

						return;

					}

					showAjaxErr('Ajax error: ' + textStatus);

				}

			});

		});

	};

})(window, jQuery);

