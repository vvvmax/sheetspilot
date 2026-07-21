/**
 * Content Rules dialog - open/close, tabs, vocabulary add/remove.
 *
 * @package Unlimited AI
 */
(function ($) {
	"use strict";

	/**
	 * Content Rules Dialog constructor.
	 */
	function SheetsPilot_ContentRulesDialog() {

		const self = this;

		var $dialog, $trigger, $close, $cancel, $backdrop, $plugin;

		/**
		 * Initialize the dialog: cache refs, bind events, setup custom actions.
		 */
		this.init = function () {

			$plugin = $("#unlimitedai-plugin");
			if (!$plugin.length) return;

			// cache dialog elements
			$dialog = $plugin.find("#ubai_contentrules_dialog");
			$trigger = $plugin.find(".ubai_content_rules_trigger");
			$close = $plugin.find(".ubai-content-rules-dialog__close");
			$cancel = $plugin.find(".ubai-content-rules-dialog__btn--cancel");
			$backdrop = $plugin.find(".ubai-content-rules-dialog__backdrop");

			if (!$dialog.length) return;

			// open/close triggers
			//$trigger.on("click", onOpen); // patch to use sidebar editor
			$close.on("click", onClose);
			$cancel.on("click", onClose);
			$backdrop.on("click", onClose);

			// tab and vocabulary handlers
			$plugin.on("click", ".ubai-content-rules-dialog__tab", onTabClick);
			$plugin.on("click", ".ubai-content-rules-dialog__vocab-add-btn", onVocabAddClick);
			$plugin.on("click", ".ubai-content-rules-dialog__vocab-tag-remove", onVocabTagRemove);
			$plugin.on("keydown", "#ubai_contentrules_vocab_avoid_input, #ubai_contentrules_vocab_prefer_input", onVocabInputKeydown);
			// custom actions handlers
			$plugin.on("click", "#ubai_contentrules_custom_action_add_btn", onCustomActionAddClick);
			$plugin.on("click", ".ubai-content-rules-dialog__custom-action-delete", onCustomActionDeleteClick);
			// content language dropdown
			$plugin.on("click", "#ubai_contentrules_content_language_choose_btn", onContentLanguageChooseClick);
			$plugin.on("click", ".ubai-content-rules-dialog__language-item", onContentLanguageItemClick);
			$plugin.on("select2:select", "#drawer_content_language", onDrawerLanguageItemClick);
			$(document).on("click", onContentLanguageOutsideClick);
			$plugin.on("input", "#ubai_contentrules_custom_action_name, #ubai_contentrules_custom_action_prompt", updateCustomActionAddBtn);
			$plugin.on("click", ".ubai-content-rules-dialog__btn--save", onSaveRulesClick);

			initCustomActionsAddBtn();

			// expose dialog so posts editor can get data and close
			$plugin.data("ubaiContentRulesDialog", self);
		};

		/**
		 * Return General tab form data. Used by posts editor when Save is clicked.
		 */
		this.getGeneralData = function () {

			return {
				contentTone: $("#ubai_contentrules_content_tone").val() || "",
				contentLanguage: $("#ubai_contentrules_content_language").val() || "",
				customLanguage: $("#ubai_contentrules_custom_language").val() || "",
				targetAudience: $("#ubai_contentrules_target_audience").val() || "",
				brandVoice: $("#ubai_contentrules_brand_voice").val() || ""
			};
		};

		/**
		 * Close the dialog. Used by posts editor after handling save.
		 */
		this.close = function () {

			if ($dialog && $dialog.length) {
				$dialog.css("display", "none");
			}
		};

		/**
		 * Fire save event for posts editor, then close the dialog.
		 */
		function onSaveRulesClick(e) {

			e.preventDefault();
			$plugin.trigger("ubai_contentrules_save_clicked");
			onClose();
		}

		/**
		 * Show the dialog.
		 */
		function onOpen() {

			if ($dialog.length) {
				$dialog.css("display", "flex");
			}
		}

		/**
		 * Hide the dialog.
		 */
		function onClose() {

			if ($dialog.length) {
				$dialog.css("display", "none");
			}
		}

		/**
		 * Add a word tag to the given container (avoid or prefer). No duplicates.
		 */
		function addVocabTag($container, word) {

			if (!word || typeof word !== "string") return;
			word = word.trim();
			if (!word) return;

			// check for duplicates (case-insensitive)
			var existing = $container.find(".ubai-content-rules-dialog__vocab-tag-text").map(function () {

				return $(this).text().toLowerCase();
			}).get();
			if (existing.indexOf(word.toLowerCase()) !== -1) return;

			// build and append tag with remove button
			var $tag = $("<span class=\"ubai-content-rules-dialog__vocab-tag\">" +
				"<span class=\"ubai-content-rules-dialog__vocab-tag-text\">" + $("<div>").text(word).html() + "</span>" +
				"<button type=\"button\" class=\"ubai-content-rules-dialog__vocab-tag-remove\" aria-label=\"Remove\"><svg xmlns=\"http://www.w3.org/2000/svg\" width=\"12\" height=\"12\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\"><path d=\"M18 6 6 18\"></path><path d=\"m6 6 12 12\"></path></svg></button>" +
				"</span>");
			$container.append($tag);
		}

		/**
		 * Switch active tab and show corresponding panel.
		 */
		function onTabClick(e) {

			var $tab = $(e.currentTarget);
			var tabId = $tab.data("tab");
			if (!tabId) return;

			// update tab active state
			$dialog.find(".ubai-content-rules-dialog__tab").removeClass("ubai-content-rules-dialog__tab--active");
			$tab.addClass("ubai-content-rules-dialog__tab--active");

			// show matching panel
			$dialog.find(".ubai-content-rules-dialog__panel").hide();
			$dialog.find(".ubai-content-rules-dialog__panel[data-tab=\"" + tabId + "\"]").show();
		}

		/**
		 * Add word from input to avoid/prefer list.
		 */
		function onVocabAddClick(e) {

			var $btn = $(e.currentTarget);
			var target = $btn.data("target");
			var $input, $container;
			if (target === "avoid") {
				$input = $("#ubai_contentrules_vocab_avoid_input");
				$container = $("#ubai_contentrules_vocab_avoid_tags");
			} else if (target === "prefer") {
				$input = $("#ubai_contentrules_vocab_prefer_input");
				$container = $("#ubai_contentrules_vocab_prefer_tags");
			} else return;
			if (!$input.length || !$container.length) return;

			var word = $input.val();
			addVocabTag($container, word);
			$input.val("");
		}

		/**
		 * Handle Enter key in vocab inputs to add word.
		 */
		function onVocabInputKeydown(e) {

			if (e.keyCode !== 13) return;
			e.preventDefault();

			var $input = $(e.currentTarget);
			var id = $input.attr("id");
			var $btn;
			if (id === "ubai_contentrules_vocab_avoid_input") {
				$btn = $(".ubai-content-rules-dialog__vocab-add-btn[data-target=\"avoid\"]");
			} else if (id === "ubai_contentrules_vocab_prefer_input") {
				$btn = $(".ubai-content-rules-dialog__vocab-add-btn[data-target=\"prefer\"]");
			} else return;

			if ($btn.length) $btn.trigger("click");
		}

		/**
		 * Remove a vocabulary tag.
		 */
		function onVocabTagRemove(e) {

			e.stopPropagation();

			var $btn = $(e.currentTarget);
			var $tag = $btn.closest(".ubai-content-rules-dialog__vocab-tag");
			if ($tag.length) $tag.remove();
		}

		/**
		 * Setup Add Action button state and empty-state message.
		 */
		function initCustomActionsAddBtn() {

			updateCustomActionAddBtn();
			updateCustomActionsEmptyState();
		}

		/**
		 * Enable Add Action button only when both name and prompt are filled.
		 */
		function updateCustomActionAddBtn() {

			var $btn = $("#ubai_contentrules_custom_action_add_btn");
			var $name = $("#ubai_contentrules_custom_action_name");
			var $prompt = $("#ubai_contentrules_custom_action_prompt");
			if (!$btn.length || !$name.length || !$prompt.length) return;

			var name = ($name.val() || "").trim();
			var prompt = ($prompt.val() || "").trim();
			var bothFilled = name.length > 0 && prompt.length > 0;

			$btn.prop("disabled", !bothFilled);
			$btn.toggleClass("ubai-content-rules-dialog__custom-add-btn--disabled", !bothFilled);
			$btn.attr("title", bothFilled ? "" : "Fill The Action");
		}

		/**
		 * Show "No actions" message when list is empty, hide when it has items.
		 */
		function updateCustomActionsEmptyState() {

			var $list = $("#ubai_contentrules_custom_actions_list");
			var $empty = $("#ubai_contentrules_custom_actions_empty");
			if (!$list.length || !$empty.length) return;

			var isEmpty = $list.children(".ubai-content-rules-dialog__custom-action-card").length === 0;
			$empty.toggle(isEmpty);
		}

		/**
		 * Append a custom action card to the list.
		 */
		function addCustomActionCard($list, name, prompt) {

			if (!name || typeof name !== "string") return;
			name = name.trim();
			if (!name) return;
			prompt = (prompt && typeof prompt === "string") ? prompt.trim() : "";

			var iconSvg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z\"></path><path d=\"m15 5 4 4\"></path></svg>";
			var deleteSvg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\"><path d=\"M18 6 6 18\"></path><path d=\"m6 6 12 12\"></path></svg>";
			var $card = $("<div class=\"ubai-content-rules-dialog__custom-action-card\">" +
				"<span class=\"ubai-content-rules-dialog__custom-action-icon\" aria-hidden=\"true\">" + iconSvg + "</span>" +
				"<div class=\"ubai-content-rules-dialog__custom-action-content\">" +
				"<p class=\"ubai-content-rules-dialog__custom-action-title\">" + $("<div>").text(name).html() + "</p>" +
				"<p class=\"ubai-content-rules-dialog__custom-action-desc\">" + $("<div>").text(prompt).html() + "</p>" +
				"</div>" +
				"<button type=\"button\" class=\"ubai-content-rules-dialog__custom-action-delete\" aria-label=\"Remove\">" + deleteSvg + "</button>" +
				"</div>");

			$list.append($card);
			updateCustomActionsEmptyState();
		}

		/**
		 * Add new custom action from form fields and clear inputs.
		 */
		function onCustomActionAddClick(e) {

			e.preventDefault();

			var $nameInput = $("#ubai_contentrules_custom_action_name");
			var $promptInput = $("#ubai_contentrules_custom_action_prompt");
			var $list = $("#ubai_contentrules_custom_actions_list");
			if (!$nameInput.length || !$promptInput.length || !$list.length) return;

			var name = $nameInput.val();
			var prompt = $promptInput.val();
			addCustomActionCard($list, name, prompt);
			$nameInput.val("");
			$promptInput.val("");
		}

		/**
		 * Remove custom action card and update empty state.
		 */
		function onCustomActionDeleteClick(e) {

			e.stopPropagation();

			var $btn = $(e.currentTarget);
			var $card = $btn.closest(".ubai-content-rules-dialog__custom-action-card");
			if ($card.length) {
				$card.remove();
				updateCustomActionsEmptyState();
			}
		}

		/**
		 * Toggle content language dropdown open/closed.
		 */
		function onContentLanguageChooseClick(e) {

			e.preventDefault();
			e.stopPropagation();

			var $list = $("#ubai_contentrules_content_language_list");
			var $btn = $("#ubai_contentrules_content_language_choose_btn");
			if ($list.length) {
				$list.toggleClass("ubai-content-rules-dialog__language-list--open");
				$btn.attr("aria-expanded", $list.hasClass("ubai-content-rules-dialog__language-list--open"));
			}
		}

		/**
		 * on drawer language change
		 */
		function onDrawerLanguageItemClick(e) {
			var value = $('#drawer_content_language').val();
			if( value == 'Custom' ){
				$('#drawer_custom_language').closest('.unlimitedai-plugin__side_drawer_input_container').show();
			}else{
				$('#drawer_custom_language').closest('.unlimitedai-plugin__side_drawer_input_container').hide();
			}
			
		}
		/**
		 * Select a language from the dropdown and close it.
		 */
		function onContentLanguageItemClick(e) {

			e.stopPropagation();

			var $item = $(e.currentTarget);
			var lang = $item.data("lang");
			var $input = $("#ubai_contentrules_content_language");
			if (lang && $input.length) {
				$input.val(lang);
			}

			$("#ubai_contentrules_content_language_list").removeClass("ubai-content-rules-dialog__language-list--open");
			$("#ubai_contentrules_content_language_choose_btn").attr("aria-expanded", false);
		}

		/**
		 * Close language dropdown when clicking outside.
		 */
		function onContentLanguageOutsideClick(e) {

			var $list = $("#ubai_contentrules_content_language_list");
			var $btn = $("#ubai_contentrules_content_language_choose_btn");
			if (!$list.length) return;

			if ($list.hasClass("ubai-content-rules-dialog__language-list--open") &&
				!$(e.target).closest(".ubai-content-rules-dialog__language-wrapper").length) {
				$list.removeClass("ubai-content-rules-dialog__language-list--open");
				$btn.attr("aria-expanded", false);
			}
		}
	}

	// bootstrap on DOM ready
	$(document).ready(function () {

		var dialog = new SheetsPilot_ContentRulesDialog();
		dialog.init();
	});

})(jQuery);
