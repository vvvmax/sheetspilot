/**
 * Unlimited AI Prompts - cell rules panel and other prompt-related UI.
 *
 * @package Unlimited AI
 */

(function ($) {
	'use strict';

	/**
	 * Prompts module: cell rules panel and more.
	 */
	function SheetsPilot_Prompts() {

		var $plugin;
		// cell rules panel refs
		var $cellRulesPanel, $cellRulesTitle, $cellRulesSubtitle, $cellRulesClose, $cellRulesBackdrop, $cellRulesCancel, $cellRulesSave, $cellRulesTextarea, $cellRulesApplyOnPromptPaste, $cellRulesAutoApply;
		// sidebar tabs refs (Text / Image)
		var $sidebarTabsWrapper, $sidebarTextTab, $sidebarImageTab, $sidebarTextModePanel, $sidebarImageModePanel;
		var currentSidebarMode = 'text';
		// current column when panel is open (for save)
		var currentCellRuleColumnName = '';
		var currentCellRuleColumnTitle = '';
		// sidebar prompt CodeMirror instance
		var sidebarPromptSelector = '#ubai_prompt_input';
		var imagePromptSelector = '#ubai_image_prompt_text';
		var cellRulesPromptSelector = '#ubai_cell_rules_prompt';
		var activeCellClassSelector = 'is-active-cell';
		// prompt replace dialog: before/after compare slider instance (assets/js/before_after_image.js)
		var prBeforeAfterInstance = null;
		var prCompareBtnDefaultLabel = '';
		var prCompareBtnDefaultTitle = '';
		// prompt history panel refs
		var $promptHistoryPanel, $promptHistoryBack, $promptHistorySearch, $promptHistoryList, $promptHistoryListWrap, $promptHistoryCount, $filterAll, $filterSaved, $promptHistoryClearBtn;
		var promptHistoryFilter = 'all';
		var promptHistorySearchDebounce = null;
		var savePromptPendingId = null;
		var $savePromptDialog, $savePromptClose, $savePromptBackdrop, $savePromptCancel, $savePromptSave, $savePromptTitleInput, $savePromptTextarea;

		// Prompt replace dialog (#ubai_prompt_replace_dialog): refs + table manipulator bridge
		var prTableManipulator = null;
		var prLastApplyPromptPayload = null;
		var $prDialog = $();
		var $prCloseBtn, $prReplaceBtn, $prInsertBtn, $prRegenerateBtn, $prCopyBtn, $prImagePreview, $prTextSection, $prImageButtons, $prApplyImageBtn, $prDiscardImageBtn, $prCompareImageBtn, $prQueueCounter, $prTargetMeta;
		var prLayoutBound = false;
		var prResizeTimer = null;
		var prWindowAdjust = null;
		var prWindowAdjustVv = null;
		var prResizeObserver = null;
		var $prHeader = $();
		var prDialogUserDragged = false;
		var prDialogDrag = { active: false, origClientX: 0, origClientY: 0, origLeft: 0, origTop: 0 };

		/**
		 * Initialize the prompts module: bind cell rules, CodeMirror, prompt history, and Save Prompt dialog.
		 */
		function init() {

			$plugin = $('#unlimitedai-plugin');
			if (!$plugin.length) return;

			initSidebarModeTabs();
			initCellRules();
			initPromptCodeMirror();
			initPromptCodeMirror(imagePromptSelector);
			initPromptHistoryPanel();
			initPromptHistorySidebarButton();
			initSavePromptDialog();
		}

		/**
		 * Initialize the sidebar Text/Image tabs and split the sidebar content into mode panels.
		 */
		function initSidebarModeTabs() {

			var strings = typeof g_ubaiPromptsStrings !== 'undefined' ? g_ubaiPromptsStrings : {};
			var $sidebarContent = $plugin.find('.unlimitedai-plugin__sidebar-content').first();
			if (!$sidebarContent.length) return;

			ensureSidebarModeTabsStyle();
			ensureSidebarModeTabsMarkup($sidebarContent, strings);

			$sidebarTabsWrapper = $sidebarContent.find('.unlimitedai-plugin__sidebar-tabs-wrapper').first();
			$sidebarTextTab = $sidebarTabsWrapper.find('.unlimitedai-plugin__sidebar-tabs-item[data-mode="text"]').first();
			$sidebarImageTab = $sidebarTabsWrapper.find('.unlimitedai-plugin__sidebar-tabs-item[data-mode="image"]').first();
			$sidebarTextModePanel = $sidebarContent.children('#ubai_sidebar_text_panel');
			$sidebarImageModePanel = $sidebarContent.children('#ubai_sidebar_image_panel');

			if ($sidebarTabsWrapper.length) {
				$sidebarTabsWrapper.css('display', 'block');
			}

			$plugin.off('click.ubaiSidebarModeTabs', '.unlimitedai-plugin__sidebar-tabs-item[data-mode]');
			$plugin.on('click.ubaiSidebarModeTabs', '.unlimitedai-plugin__sidebar-tabs-item[data-mode]', onSidebarModeTabClick);

			// Ensure keyboard activation (Enter/Space) behaves like click.
			$plugin.off('keydown.ubaiSidebarModeTabs', '.unlimitedai-plugin__sidebar-tabs-item[data-mode]');
			$plugin.on('keydown.ubaiSidebarModeTabs', '.unlimitedai-plugin__sidebar-tabs-item[data-mode]', function (e) {
				var key = e.key || '';
				if (key === 'Enter' || key === ' ') {
					e.preventDefault();
					e.stopPropagation();
					var mode = $(e.currentTarget).attr('data-mode') || 'text';
					setSidebarMode(mode);
				}
			});

			initImagePanelControls();
			setSidebarMode('text');
		}

		/**
		 * Add the sidebar tab styles and image prompt UI styles (self-contained in prompts file).
		 */
		function ensureSidebarModeTabsStyle() {

			var styleId = 'ubai_sidebar_mode_tabs_style_v4';
			var existingStyle = document.getElementById(styleId);
			if (existingStyle) {
				return;
			}
			['ubai_sidebar_mode_tabs_style', 'ubai_sidebar_mode_tabs_style_v2', 'ubai_sidebar_mode_tabs_style_v3'].forEach(function (legacyId) {
				var legacy = document.getElementById(legacyId);
				if (legacy) legacy.remove();
			});

			var style = document.createElement('style');
			style.id = styleId;
			style.textContent = [
				'#unlimitedai-plugin .unlimitedai-plugin__sidebar-tabs-wrapper.ubai-sidebar-mode-tabs{display:block;padding-bottom:16px;}',
				'#unlimitedai-plugin .unlimitedai-plugin__sidebar-tabs.ubai-sidebar-mode-tabs__list{gap:6px;padding:3px;border:1px solid #e5e7eb;border-radius:12px;background:#f5f5f5;}',
				'#unlimitedai-plugin .unlimitedai-plugin__sidebar-tabs-item.ubai-sidebar-mode-tab{min-height:32px;border-radius:6px;padding:4px 8px;font-size:12px;font-weight:500;opacity:.65;color:#6b7280;}',
				'#unlimitedai-plugin .unlimitedai-plugin__sidebar-tabs-item.ubai-sidebar-mode-tab:hover{opacity:1;color:#111827;}',
				'#unlimitedai-plugin .unlimitedai-plugin__sidebar-tabs-item.ubai-sidebar-mode-tab.active{background:#fff;color:#111827;opacity:1;box-shadow:0 1px 2px rgba(16,24,40,.08);}',
				'#unlimitedai-plugin .ubai-sidebar-mode-panel[hidden]{display:none !important;}',
				'#unlimitedai-plugin .ubai-image-prompt__section{margin-top:16px;display:flex;flex-direction:column;gap:6px;}',
				'#unlimitedai-plugin .ubai-image-prompt__section label{font-size:14px;font-weight:500;color:#171717;}',
				'#unlimitedai-plugin .ubai-image-prompt__textarea{width:100%;min-height:100px;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;line-height:1.5;resize:vertical;box-sizing:border-box;}',
				'#unlimitedai-plugin .ubai-image-parameters__section,#unlimitedai-plugin .ubai-image-parameters__section .ubai-ratio-selector,#unlimitedai-plugin .ubai-cell-rules-panel__image-options .ubai-ratio-selector{overflow:visible;}',
				'#unlimitedai-plugin .ubai-ratio-selector{position:relative;margin-top:4px;overflow:visible;}',
				'#unlimitedai-plugin .ubai-ratio-selector__row{display:flex;flex-wrap:nowrap;gap:4px;align-items:center;width:100%;overflow:visible;}',
				'#unlimitedai-plugin .ubai-ratio-selector__row>.ubai-ratio-box,#unlimitedai-plugin .ubai-ratio-selector__row>.ubai-quality-selector,#unlimitedai-plugin .ubai-ratio-selector__row>.ubai-format-selector,#unlimitedai-plugin .ubai-ratio-selector__row>.ubai-resolution-selector{flex:1 1 0;min-width:0;}',
				'#unlimitedai-plugin .ubai-image-prompt__aspect-btn{border:1px solid #e5e7eb;border-radius:8px;padding:5px 6px;background:#fff;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:4px;font-size:12px;color:#374151;box-sizing:border-box;width:100%;min-width:0;}',
				'#unlimitedai-plugin .ubai-image-prompt__aspect-btn:hover{background:#f9fafb;}',
				'#unlimitedai-plugin .ubai-image-prompt__aspect-btn.active{background:#eff6ff;border-color:#3b82f6;color:#1d4ed8;}',
				'#unlimitedai-plugin .ubai-quality-selector{position:relative;display:flex;flex:1 1 0;min-width:0;}',
				'#unlimitedai-plugin .ubai-quality-selector__btn{border:1px solid #e5e7eb;border-radius:8px;padding:5px 6px;background:#fff;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:4px;font-size:12px;color:#374151;box-sizing:border-box;width:100%;min-width:0;}',
				'#unlimitedai-plugin .ubai-quality-selector__btn:hover{background:#f9fafb;}',
				'#unlimitedai-plugin .ubai-quality-selector__label{min-width:0;text-align:center;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}',
				'#unlimitedai-plugin .ubai-quality-selector__dropdown{display:none;position:absolute;top:calc(100% + 4px);left:calc(-100% - 4px);right:auto;margin-top:0;padding:6px 0;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);z-index:60;min-width:max-content;width:max-content;max-width:min(280px,calc(100vw - 24px));}',
				'#unlimitedai-plugin .ubai-quality-selector.open .ubai-quality-selector__dropdown{display:block;}',
				'#unlimitedai-plugin .ubai-quality-dropdown__item{display:grid;grid-template-columns: minmax(50px, max-content) 1fr;align-items:center;justify-content:space-between;gap:10px;padding:8px 12px;cursor:pointer;font-size:13px;color:#374151;}',
				'#unlimitedai-plugin .ubai-quality-dropdown__item:hover{background:#f9fafb;}',
				'#unlimitedai-plugin .ubai-quality-dropdown__item.active{background:#f3f4f6;}',
				'#unlimitedai-plugin .ubai-quality-dropdown__label{font-weight:500;}',
				'#unlimitedai-plugin .ubai-quality-dropdown__size{font-size:12px;color:#6b7280;}',
				'#unlimitedai-plugin .ubai-format-selector{position:relative;display:flex;flex:1 1 0;min-width:0;}',
				'#unlimitedai-plugin .ubai-format-selector__btn{border:1px solid #e5e7eb;border-radius:8px;padding:5px 6px;background:#fff;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:4px;font-size:12px;color:#374151;box-sizing:border-box;width:100%;min-width:0;}',
				'#unlimitedai-plugin .ubai-format-selector__btn:hover{background:#f9fafb;}',
				'#unlimitedai-plugin .ubai-format-selector__label{min-width:0;text-align:center;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}',
				'#unlimitedai-plugin .ubai-format-selector__dropdown{display:none;position:absolute;top:calc(100% + 4px);left:auto;right:0;margin-top:0;padding:6px 0;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);z-index:60;min-width:100%;width:max-content;max-width:min(240px,calc(100vw - 32px));}',
				'#unlimitedai-plugin .ubai-format-selector.open .ubai-format-selector__dropdown{display:block;}',
				'#unlimitedai-plugin .ubai-format-dropdown__item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 12px;cursor:pointer;font-size:13px;color:#374151;}',
				'#unlimitedai-plugin .ubai-format-dropdown__item:hover{background:#f9fafb;}',
				'#unlimitedai-plugin .ubai-format-dropdown__item.active{background:#f3f4f6;}',
				'#unlimitedai-plugin .ubai-format-dropdown__label{font-weight:500;}',
				'#unlimitedai-plugin .ubai-format-dropdown__size{font-size:12px;color:#6b7280;}',
				'#unlimitedai-plugin .ubai-resolution-selector{position:relative;display:flex;flex:1 1 0;min-width:0;}',
				'#unlimitedai-plugin .ubai-resolution-selector__btn{border:1px solid #e5e7eb;border-radius:8px;padding:5px 6px;background:#fff;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:4px;font-size:12px;color:#374151;box-sizing:border-box;width:100%;min-width:0;}',
				'#unlimitedai-plugin .ubai-resolution-selector__btn:hover{background:#f9fafb;}',
				'#unlimitedai-plugin .ubai-resolution-selector__label{min-width:0;text-align:center;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}',
				'#unlimitedai-plugin .ubai-default-placeholder{color:#9ca3af !important;}',
				'#unlimitedai-plugin .ubai-quality-dropdown__item.ubai-image-option-default,#unlimitedai-plugin .ubai-format-dropdown__item.ubai-image-option-default,#unlimitedai-plugin .ubai-resolution-dropdown__item.ubai-image-option-default{background:#f3f4f6;}',
				'#unlimitedai-plugin .ubai-quality-dropdown__item.ubai-image-option-default:hover,#unlimitedai-plugin .ubai-format-dropdown__item.ubai-image-option-default:hover,#unlimitedai-plugin .ubai-resolution-dropdown__item.ubai-image-option-default:hover{background:#e5e7eb;}',
				'#unlimitedai-plugin .ubai-quality-dropdown__item.ubai-image-option-default.active,#unlimitedai-plugin .ubai-format-dropdown__item.ubai-image-option-default.active,#unlimitedai-plugin .ubai-resolution-dropdown__item.ubai-image-option-default.active{background:#e5e7eb;}',
				'#unlimitedai-plugin .ubai-image-option-default .ubai-quality-dropdown__label,#unlimitedai-plugin .ubai-image-option-default .ubai-format-dropdown__label,#unlimitedai-plugin .ubai-image-option-default .ubai-resolution-dropdown__label{color:#9ca3af;}',
				'#unlimitedai-plugin .ubai-resolution-selector__dropdown{display:none;position:absolute;top:calc(100% + 4px);left:auto;right:0;margin-top:0;padding:6px 0;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);z-index:70;min-width:100%;width:max-content;}',
				'#unlimitedai-plugin .ubai-resolution-selector.open .ubai-resolution-selector__dropdown{display:block;}',
				'#unlimitedai-plugin .ubai-resolution-dropdown__item{padding:8px 12px;cursor:pointer;font-size:13px;color:#374151;white-space:nowrap;}',
				'#unlimitedai-plugin .ubai-resolution-dropdown__item:hover{background:#f9fafb;}',
				'#unlimitedai-plugin .ubai-resolution-dropdown__item.active{background:#f3f4f6;}',
				'#unlimitedai-plugin .ubai-resolution-dropdown__label{font-weight:500;}',
				'#unlimitedai-plugin .ubai-quality-selector.open,#unlimitedai-plugin .ubai-format-selector.open,#unlimitedai-plugin .ubai-resolution-selector.open{z-index:20;}',
				'#unlimitedai-plugin .ubai-ratio-box .ubai-ratio-selector__icon{flex-shrink:0;width:18px;height:18px;display:flex;align-items:center;justify-content:center;}',
				'#unlimitedai-plugin .ubai-ratio-dropdown__icon-a{display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;width:20px;height:20px;min-width:20px;min-height:20px;border-radius:50%;border:1.5px solid currentColor;box-sizing:border-box;font-size:11px;font-weight:700;line-height:1;padding:0;}',
				'#unlimitedai-plugin .ubai-ratio-box .ubai-ratio-box__value{font-size:12px;line-height:1.25;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}',
				'#unlimitedai-plugin .ubai-ratio-selector__dropdown{display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;margin-top:0;padding:6px 0;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);z-index:55;max-height:320px;overflow-y:auto;}',
				'#unlimitedai-plugin .ubai-ratio-selector.open .ubai-ratio-selector__dropdown{display:block;}',
				'#unlimitedai-plugin .ubai-ratio-dropdown__list{padding:4px 0;}',
				'#unlimitedai-plugin .ubai-ratio-dropdown__item{display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;font-size:13px;color:#374151;}',
				'#unlimitedai-plugin .ubai-ratio-dropdown__item:hover{background:#f9fafb;}',
				'#unlimitedai-plugin .ubai-ratio-dropdown__item.active{background:#f3f4f6;}',
				'#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap{flex-shrink:0;width:20px;height:20px;display:flex;align-items:center;justify-content:center;}',
				'#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap svg{width:14px;height:14px;}',
				'#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-dropdown__icon-a{width:20px;height:20px;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon,#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon{display:block;width:16px;min-height:4px;border-radius:1px;border:1.5px solid currentColor;box-sizing:border-box;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon[data-ratio-shape="1:1"],#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon[data-ratio-shape="1:1"]{aspect-ratio:1/1;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon[data-ratio-shape="2:1"],#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon[data-ratio-shape="2:1"]{aspect-ratio:2/1;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon[data-ratio-shape="3:1"],#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon[data-ratio-shape="3:1"]{aspect-ratio:3/1;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon[data-ratio-shape="21:9"],#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon[data-ratio-shape="21:9"]{aspect-ratio:21/9;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon[data-ratio-shape="16:9"],#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon[data-ratio-shape="16:9"]{aspect-ratio:16/9;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon[data-ratio-shape="9:16"],#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon[data-ratio-shape="9:16"]{aspect-ratio:9/16;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon[data-ratio-shape="3:4"],#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon[data-ratio-shape="3:4"]{aspect-ratio:3/4;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon[data-ratio-shape="4:5"],#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon[data-ratio-shape="4:5"]{aspect-ratio:4/5;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon[data-ratio-shape="5:4"],#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon[data-ratio-shape="5:4"]{aspect-ratio:5/4;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon[data-ratio-shape="4:3"],#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon[data-ratio-shape="4:3"]{aspect-ratio:4/3;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon[data-ratio-shape="3:2"],#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon[data-ratio-shape="3:2"]{aspect-ratio:3/2;}',
				'#unlimitedai-plugin .ubai-ratio-selector__icon .ubai-ratio-icon[data-ratio-shape="2:3"],#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__icon-wrap .ubai-ratio-icon[data-ratio-shape="2:3"]{aspect-ratio:2/3;}',
				'#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__ratio{min-width:32px;font-size:13px;}',
				'#unlimitedai-plugin .ubai-ratio-dropdown__item .ubai-ratio-dropdown__label{margin-left:auto;font-size:13px;color:#6b7280;text-align:right;}',
				'#unlimitedai-plugin .ubai-image-prompt__refs-header{display:flex;align-items:center;justify-content:space-between;margin-top:16px;margin-bottom:8px;}',
				'#unlimitedai-plugin .ubai-image-prompt__refs-title{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;}',
				'#unlimitedai-plugin .ubai-image-prompt__refs-add{font-size:12px;padding:4px 8px;border:1px dashed #d1d5db;border-radius:6px;background:transparent;color:#6b7280;cursor:pointer;}',
				'#unlimitedai-plugin .ubai-image-prompt__refs-row{display:flex;flex-wrap:wrap;gap:8px;}',
				'#unlimitedai-plugin .ubai-image-prompt__ref-btn{border:1px dashed #d1d5db;border-radius:8px;padding:8px 12px;background:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#374151;}',
				'#unlimitedai-plugin .ubai-image-prompt__ref-btn:hover{background:#f9fafb;}',
				'#unlimitedai-plugin .ubai-image-prompt__use-btn{width:100%;margin-top:16px;padding:10px 16px;border:0;border-radius:8px;background:#111827;color:#fff;font-size:14px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;}',
				'#unlimitedai-plugin .ubai-image-prompt__use-btn:hover{background:#1f2937;}'
			].join('');
			document.head.appendChild(style);
		}

		/**
		 * Ensure the sidebar has tab buttons and dedicated Text/Image content panels.
		 */
		function ensureSidebarModeTabsMarkup($sidebarContent, strings) {

			var textLabel = strings.sidebarTabText || '';
			var imageLabel = strings.sidebarTabImage || '';

			$sidebarTabsWrapper = $sidebarContent.find('.unlimitedai-plugin__sidebar-tabs-wrapper').first();
			if (!$sidebarTabsWrapper.length) {
				$sidebarTabsWrapper = $('<div class="unlimitedai-plugin__sidebar-tabs-wrapper"></div>');
				$sidebarContent.prepend($sidebarTabsWrapper);
			}

			$sidebarTabsWrapper.addClass('ubai-sidebar-mode-tabs');

			var $sidebarTabs = $sidebarTabsWrapper.find('.unlimitedai-plugin__sidebar-tabs').first();
			if (!$sidebarTabs.length) {
				$sidebarTabs = $('<div class="unlimitedai-plugin__sidebar-tabs"></div>');
				$sidebarTabsWrapper.append($sidebarTabs);
			}

			$sidebarTabs.addClass('ubai-sidebar-mode-tabs__list').attr('role', 'tablist');
			var $buttons = $sidebarTabs.find('.unlimitedai-plugin__sidebar-tabs-item');
			if ($buttons.length < 2) {
				$sidebarTabs.html(getSidebarModeTabsButtonsHtml(textLabel, imageLabel));
				$buttons = $sidebarTabs.find('.unlimitedai-plugin__sidebar-tabs-item');
			}

			$buttons.eq(0).attr('type', 'button').attr('data-mode', 'text').attr('role', 'tab').attr('id', 'ubai_sidebar_mode_tab_text').attr('aria-controls', 'ubai_sidebar_text_panel').addClass('ubai-sidebar-mode-tab').find('.unlimitedai-plugin__sidebar-tabs-item-text').text(textLabel);
			$buttons.eq(1).attr('type', 'button').attr('data-mode', 'image').attr('role', 'tab').attr('id', 'ubai_sidebar_mode_tab_image').attr('aria-controls', 'ubai_sidebar_image_panel').addClass('ubai-sidebar-mode-tab').find('.unlimitedai-plugin__sidebar-tabs-item-text').text(imageLabel);

			$sidebarTextModePanel = $sidebarContent.children('#ubai_sidebar_text_panel');
			if (!$sidebarTextModePanel.length) {
				$sidebarTextModePanel = $('<div id="ubai_sidebar_text_panel" class="ubai-sidebar-mode-panel" role="tabpanel" aria-labelledby="ubai_sidebar_mode_tab_text"></div>');
				$sidebarTabsWrapper.after($sidebarTextModePanel);
			}

			var $textSections = $sidebarContent.children('.unlimitedai-plugin__sidebar-section');
			if ($textSections.length) {
				$sidebarTextModePanel.append($textSections);
			}

			// Image panel is normally rendered by PHP (UnlimitedAI_PromptsUI::render_image_prompt_panel); fallback to JS-built HTML if missing
			$sidebarImageModePanel = $sidebarContent.children('#ubai_sidebar_image_panel');
			if (!$sidebarImageModePanel.length) {
				$sidebarImageModePanel = $(getSidebarImageModePanelHtml(strings));
				$sidebarTextModePanel.after($sidebarImageModePanel);
			}

			// Apply options (Include rules; Use current cell data for Text tab only) live outside Text/Image panels.
			var $includeRulesLabel = $sidebarContent.find('#ubai_include_rules_label').first();
			var $useCurrentCellLabel = $sidebarContent.find('#ubai_use_current_cell_data_label').first();
			if ($includeRulesLabel.length || $useCurrentCellLabel.length) {
				var $sharedApplyOptions = $sidebarContent.children('#ubai_sidebar_shared_apply_options');
				if (!$sharedApplyOptions.length) {
					$sharedApplyOptions = $('<div id="ubai_sidebar_shared_apply_options" class="unlimitedai-plugin__sidebar-section unlimitedai-plugin__sidebar-section--extra_rules unlimitedai-plugin__sidebar-section--shared_apply_options"></div>');
				}
				if ($includeRulesLabel.length) {
					$sharedApplyOptions.append($includeRulesLabel.detach());
				}
				if ($useCurrentCellLabel.length) {
					$sharedApplyOptions.append($useCurrentCellLabel.detach());
				}
				$sidebarContent.find('.unlimitedai-plugin__sidebar-section--extra_rules').filter(function () {
					return jQuery(this).children().length === 0;
				}).remove();
				var $applyBtn = $sidebarContent.find('.unlimitedai-plugin__sidebar-apply-btn').first();
				if ($applyBtn.length) {
					$applyBtn.before($sharedApplyOptions);
				} else {
					$sidebarContent.append($sharedApplyOptions);
				}
			}
		}

		function getSidebarModeTabsButtonsHtml(textLabel, imageLabel) {
			var icons = getPromptsIcons();
			var iconTabText = icons.iconTabText || '';
			var iconTabImage = icons.iconTabImage || '';
			return '<button type="button" class="unlimitedai-plugin__sidebar-tabs-item active"><span class="unlimitedai-plugin__btn-icon">' + iconTabText + '</span><span class="unlimitedai-plugin__sidebar-tabs-item-text">' + escapeHtml(textLabel) + '</span></button>'
				+ '<button type="button" class="unlimitedai-plugin__sidebar-tabs-item"><span class="unlimitedai-plugin__btn-icon">' + iconTabImage + '</span><span class="unlimitedai-plugin__sidebar-tabs-item-text">' + escapeHtml(imageLabel) + '</span></button>';
		}

		function getSidebarImageModePanelHtml(strings) {

			var actionLabel = strings.imageActionLabel || '';
			var actionCreate = strings.imageActionCreate || '';
			var actionEdit = strings.imageActionEdit || '';
			var promptLabel = strings.imagePromptLabel || '';
			var promptPlaceholder = strings.imagePromptPlaceholder || '';
			var promptEditPlaceholder = strings.imagePromptEditPlaceholder || promptPlaceholder;
			var panelHtml = ''
				+ '<div id="ubai_sidebar_image_panel" class="ubai-sidebar-mode-panel ubai-sidebar-mode-panel--image" role="tabpanel" aria-labelledby="ubai_sidebar_mode_tab_image" hidden>'
				+ '<div class="unlimitedai-plugin__sidebar-section ubai-image-prompt__section ubai-image-action__section"><label for="ubai_image_action_select">' + escapeHtml(actionLabel) + '</label>'
				+ '<select id="ubai_image_action_select" class="ubai-image-action-select">'
				+ '<option value="create">' + escapeHtml(actionCreate) + '</option>'
				+ '<option value="edit">' + escapeHtml(actionEdit) + '</option>'
				+ '</select></div>'
				+ '<div class="unlimitedai-plugin__sidebar-section ubai-image-prompt__section"><label for="ubai_image_prompt_text">' + escapeHtml(promptLabel) + '</label><textarea id="ubai_image_prompt_text" class="ubai-image-prompt__textarea" rows="4" placeholder="' + escapeHtml(promptPlaceholder) + '" data-placeholder-create="' + escapeHtml(promptPlaceholder) + '" data-placeholder-edit="' + escapeHtml(promptEditPlaceholder) + '"></textarea></div>'
				+ '</div>';
			return panelHtml;
		}

		function escapeHtml(value) {

			var text = value == null ? '' : String(value);
			return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
		}

		var parsedPromptsIcons = null;

		/**
		 * g_ubaiPromptsIcons is stored as a JSON string in a hidden input; parse once for icon HTML.
		 *
		 * @return {Object}
		 */
		function getPromptsIcons() {
			if (parsedPromptsIcons !== null) {
				return parsedPromptsIcons;
			}
			var raw = typeof g_ubaiPromptsIcons !== 'undefined' ? g_ubaiPromptsIcons : null;
			if (raw && typeof raw === 'object') {
				parsedPromptsIcons = raw;
				return parsedPromptsIcons;
			}
			if (typeof raw === 'string' && raw.length) {
				try {
					parsedPromptsIcons = JSON.parse(raw);
				} catch (err) {
					parsedPromptsIcons = {};
				}
			} else {
				parsedPromptsIcons = {};
			}
			return parsedPromptsIcons;
		}

		/**
		 * Circular "A" icon for aspect ratio "auto" (ratio box + dropdown).
		 *
		 * @return {string}
		 */
		function getAutoRatioIconHtml() {
			var icons = getPromptsIcons();
			if (icons.iconAuto) {
				return icons.iconAuto;
			}
			return '<span class="ubai-ratio-dropdown__icon-a" aria-hidden="true">A</span>';
		}

		/**
		 * Set ratio-box icon from data-ratio (auto = "A" circle, else shape icon).
		 *
		 * @param {jQuery} $ratioBox
		 * @param {string} [ratio]
		 */
		function updateRatioBoxIcon($ratioBox, ratio) {
			if (!$ratioBox || !$ratioBox.length) {
				return;
			}
			var $iconWrap = $ratioBox.find('.ubai-ratio-selector__icon');
			if (!$iconWrap.length) {
				return;
			}
			ratio = ratio != null ? String(ratio) : String($ratioBox.attr('data-ratio') || 'auto');
			if (ratio === 'auto') {
				$iconWrap.html(getAutoRatioIconHtml());
			} else {
				$iconWrap.html('<span class="ubai-ratio-icon" data-ratio-shape="' + escapeHtml(ratio) + '"></span>');
			}
		}

		function syncAllRatioBoxIcons() {
			$plugin.find('.ubai-ratio-box').each(function () {
				updateRatioBoxIcon($(this));
			});
		}

		function onSidebarModeTabClick(e) {

			e.preventDefault();
			var mode = $(e.currentTarget).attr('data-mode') || 'text';
			setSidebarMode(mode);
		}

		function setSidebarMode(mode) {

			currentSidebarMode = mode === 'image' ? 'image' : 'text';
			var isTextMode = currentSidebarMode === 'text';

			if ($sidebarTextTab && $sidebarTextTab.length) {
				$sidebarTextTab.toggleClass('active', isTextMode).attr('aria-selected', isTextMode ? 'true' : 'false').attr('tabindex', isTextMode ? '0' : '-1');
			}
			if ($sidebarImageTab && $sidebarImageTab.length) {
				$sidebarImageTab.toggleClass('active', !isTextMode).attr('aria-selected', !isTextMode ? 'true' : 'false').attr('tabindex', !isTextMode ? '0' : '-1');
			}
			if ($sidebarTextModePanel && $sidebarTextModePanel.length) {
				$sidebarTextModePanel.prop('hidden', !isTextMode);
			}
			if ($sidebarImageModePanel && $sidebarImageModePanel.length) {
				$sidebarImageModePanel.prop('hidden', isTextMode);
			}
			if (!isTextMode) {
				initPromptCodeMirror(imagePromptSelector);
			}
			var sidebarCm = getPromptCodeMirrorFor(isTextMode ? sidebarPromptSelector : imagePromptSelector);
			if (sidebarCm && typeof sidebarCm.refresh === 'function') {
				setTimeout(function () { sidebarCm.refresh(); }, 0);
			}
			$plugin.trigger('ubai-sidebar-mode-changed');
		}

		// Allow other modules to set sidebar mode via events (e.g. when clicking an image cell).
		// Register on document so it doesn't depend on $plugin timing/availability.
		jQuery(document).off('ubai-sidebar-mode-set.ubaiPrompts').on('ubai-sidebar-mode-set.ubaiPrompts', function (e, mode) {
			setSidebarMode(mode);
		});

		/**
		 * Bind image panel controls: aspect ratio toggle, references, Use Selected Image.
		 */
		function initImagePanelControls() {

			$plugin.off('click.ubaiImagePrompt');
			$plugin.on('click.ubaiImagePrompt', '.ubai-image-prompt__aspect-btn', onImageAspectClick);
			$plugin.on('click.ubaiImagePrompt', '.ubai-ratio-dropdown__item', onRatioDropdownItemClick);
			$plugin.on('click.ubaiImagePrompt', '.ubai-quality-selector__btn', onQualityButtonClick);
			$plugin.on('click.ubaiImagePrompt', '.ubai-quality-dropdown__item', onQualityDropdownItemClick);
			$plugin.on('click.ubaiImagePrompt', '.ubai-format-selector__btn', onFormatButtonClick);
			$plugin.on('click.ubaiImagePrompt', '.ubai-format-dropdown__item', onFormatDropdownItemClick);
			$plugin.on('click.ubaiImagePrompt', '.ubai-resolution-selector__btn', onResolutionButtonClick);
			$plugin.on('click.ubaiImagePrompt', '.ubai-resolution-dropdown__item', onResolutionDropdownItemClick);
			syncAllRatioBoxIcons();
			$plugin.on('click.ubaiImagePrompt', '.ubai-image-prompt__refs-add', function (e) { e.preventDefault(); });
			$plugin.on('click.ubaiImagePrompt', '.ubai-image-prompt__ref-btn', function (e) { e.preventDefault(); });
			$(document).on('click.ubaiImagePrompt', function (e) {
				$plugin.find('.ubai-ratio-selector').each(function () {
					var $sel = $(this);
					if ($sel.length && !$sel[0].contains(e.target)) {
						$sel.removeClass('open');
						$sel.find('.ubai-ratio-box').attr('aria-expanded', 'false');
					}
				});
				$plugin.find('.ubai-quality-selector').each(function () {
					var $qSel = $(this);
					if ($qSel.length && !$qSel[0].contains(e.target)) {
						$qSel.removeClass('open');
						$qSel.find('.ubai-quality-selector__btn').attr('aria-expanded', 'false');
					}
				});
				$plugin.find('.ubai-format-selector').each(function () {
					var $fSel = $(this);
					if ($fSel.length && !$fSel[0].contains(e.target)) {
						$fSel.removeClass('open');
						$fSel.find('.ubai-format-selector__btn').attr('aria-expanded', 'false');
					}
				});
				$plugin.find('.ubai-resolution-selector').each(function () {
					var $rSel = $(this);
					if ($rSel.length && !$rSel[0].contains(e.target)) {
						$rSel.removeClass('open');
						$rSel.find('.ubai-resolution-selector__btn').attr('aria-expanded', 'false');
					}
				});
			});
		}

		function closeAllOpenedModals() {
			$plugin.find('.ubai-ratio-selector').removeClass('open').find('.ubai-ratio-box').attr('aria-expanded', 'false');
			$plugin.find('.ubai-quality-selector').removeClass('open').find('.ubai-quality-selector__btn').attr('aria-expanded', 'false');
			$plugin.find('.ubai-format-selector').removeClass('open').find('.ubai-format-selector__btn').attr('aria-expanded', 'false');
			$plugin.find('.ubai-resolution-selector').removeClass('open').find('.ubai-resolution-selector__btn').attr('aria-expanded', 'false');
		}

		function onImageAspectClick(e) {
			var $btn = $(e.currentTarget);
			// Quality/format buttons share ubai-image-prompt__aspect-btn but have dedicated handlers.
			if (!$btn.hasClass('ubai-ratio-box')) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			var $selector = $btn.closest('.ubai-ratio-selector');
			if (!$selector.length) {
				return;
			}
			var wasOpen = $selector.hasClass('open');
			closeAllOpenedModals();
			if (!wasOpen) {
				$selector.addClass('open');
				$btn.attr('aria-expanded', 'true');
			}
		}

		function onRatioDropdownItemClick(e) {

			e.preventDefault();
			e.stopPropagation();
			var $item = $(e.currentTarget);
			var ratio = $item.attr('data-ratio');
			var label = $item.find('.ubai-ratio-dropdown__label').text();
			if (typeof label !== 'string') label = '';
			label = label.trim();
			var strings = typeof g_ubaiPromptsStrings !== 'undefined' ? g_ubaiPromptsStrings : {};
			var autoLabel = strings.imageRatioAutoLabel || '';
			// For "auto" only, show just the label from PHP; other items show "ratio label"
			var displayText = (ratio === 'auto') ? (label || autoLabel) : ((ratio || '') + (label ? ' ' + label : ''));

			var $selector = $item.closest('.ubai-ratio-selector');
			var $ratioBox = $selector.find('.ubai-ratio-box');
			if ($ratioBox.length) {
				$ratioBox.attr('data-ratio', ratio || '');
				$ratioBox.find('.ubai-ratio-box__value').text(displayText || ratio || '');
				updateRatioBoxIcon($ratioBox, ratio || 'auto');
			}

			$selector.removeClass('open');
			$selector.find('.ubai-ratio-box').attr('aria-expanded', 'false');

			var $list = $item.closest('.ubai-ratio-dropdown__item');
			if ($list.length) {
				$('.ubai-ratio-dropdown__item.active').removeClass('active');
				$item.addClass('active');
			}
		}

		function onQualityButtonClick(e) {
			e.preventDefault();
			e.stopPropagation();
			var $btn = $(e.currentTarget);
			var $selector = $btn.closest('.ubai-quality-selector');
			if (!$selector.length) {
				return;
			}
			var wasOpen = $selector.hasClass('open');
			closeAllOpenedModals();
			if (!wasOpen) {
				$selector.addClass('open');
				$btn.attr('aria-expanded', 'true');
			}
		}

		function onQualityDropdownItemClick(e) {

			e.preventDefault();
			e.stopPropagation();
			var $item = $(e.currentTarget);
			var quality = $item.attr('data-quality') || '';
			var label = $item.find('.ubai-quality-dropdown__label').text();
			if (typeof label !== 'string') label = '';
			label = label.trim();

			var $selector = $item.closest('.ubai-quality-selector');
			var $btn = $selector.find('.ubai-quality-selector__btn');
			if ($btn.length) {
				$btn.attr('data-quality', quality);
				$btn.find('.ubai-quality-selector__label').text(label || quality);
				if (String(quality) === 'default') $btn.addClass('ubai-default-placeholder');
				else $btn.removeClass('ubai-default-placeholder');
			}

			var $list = $item.closest('.ubai-quality-selector__dropdown');
			if ($list.length) {
				$list.find('.ubai-quality-dropdown__item').removeClass('active');
				$item.addClass('active');
			}

			$selector.removeClass('open');
			$btn.attr('aria-expanded', 'false');
		}

		function onFormatButtonClick(e) {
			e.preventDefault();
			e.stopPropagation();
			var $btn = $(e.currentTarget);
			var $selector = $btn.closest('.ubai-format-selector');
			if (!$selector.length) {
				return;
			}
			var wasOpen = $selector.hasClass('open');
			closeAllOpenedModals();
			if (!wasOpen) {
				$selector.addClass('open');
				$btn.attr('aria-expanded', 'true');
			}
		}

		function onFormatDropdownItemClick(e) {

			e.preventDefault();
			e.stopPropagation();
			var $item = $(e.currentTarget);
			var format = $item.attr('data-format') || '';
			var label = $item.find('.ubai-format-dropdown__label').text();
			if (typeof label !== 'string') label = '';
			label = label.trim();

			var $selector = $item.closest('.ubai-format-selector');
			var $btn = $selector.find('.ubai-format-selector__btn');
			if ($btn.length) {
				$btn.attr('data-format', format);
				$btn.find('.ubai-format-selector__label').text(label || format);
				if (String(format) === 'default') $btn.addClass('ubai-default-placeholder');
				else $btn.removeClass('ubai-default-placeholder');
			}

			var $list = $item.closest('.ubai-format-selector__dropdown');
			if ($list.length) {
				$list.find('.ubai-format-dropdown__item').removeClass('active');
				$item.addClass('active');
			}

			$selector.removeClass('open');
			$btn.attr('aria-expanded', 'false');
		}

		function onResolutionButtonClick(e) {
			e.preventDefault();
			e.stopPropagation();
			var $btn = $(e.currentTarget);
			var $selector = $btn.closest('.ubai-resolution-selector');
			if (!$selector.length) {
				return;
			}
			var wasOpen = $selector.hasClass('open');
			closeAllOpenedModals();
			if (!wasOpen) {
				$selector.addClass('open');
				$btn.attr('aria-expanded', 'true');
			}
		}

		function onResolutionDropdownItemClick(e) {
			e.preventDefault();
			e.stopPropagation();
			var $item = $(e.currentTarget);
			var resolution = $item.attr('data-resolution') || '';
			var label = $item.find('.ubai-resolution-dropdown__label').text();
			if (typeof label !== 'string') label = '';
			label = label.trim();

			var $selector = $item.closest('.ubai-resolution-selector');
			var $btn = $selector.find('.ubai-resolution-selector__btn');
			if ($btn.length) {
				$btn.attr('data-resolution', resolution);
				$btn.find('.ubai-resolution-selector__label').text(label || resolution);
				if (String(resolution) === 'default') $btn.addClass('ubai-default-placeholder');
				else $btn.removeClass('ubai-default-placeholder');
			}

			var $list = $item.closest('.ubai-resolution-selector__dropdown');
			if ($list.length) {
				$list.find('.ubai-resolution-dropdown__item').removeClass('active');
				$item.addClass('active');
			}

			$selector.removeClass('open');
			$btn.attr('aria-expanded', 'false');
		}

		/**
		 * Return table columns with name and title for prompt @ hints (excludes id, bulk).
		 */
		function getTableColumnsWithTitles() {
			var $table = $plugin.find('#new_output_table');
			if (!$table.length) return [];
			var skip = { 'id': true, 'bulk': true };
			var cols = [];
			$table.find('thead th[data-name]').each(function () {
				var $th = $(this);
				var n = $th.attr('data-name');
				if (n && !skip[n.toLowerCase()]) {
					var title = $th.find('.unlimitedai-plugin__th-title').text().trim() || n;
					cols.push({ name: n, title: title });
				}
			});
			return cols;
		}

		/**
		 * CodeMirror hint: show table columns when cursor is after @ or #, filter by typed text.
		 */
		function ubaiPromptHint(cm) {
			var cur = cm.getCursor();
			var line = cm.getLine(cur.line);
			var ch = cur.ch;
			if (ch < 1) return null;
			var triggerCh = -1;
			for (var i = ch - 1; i >= 0; i--) {
				var c = line.charAt(i);
				if (c === '@' || c === '#') {
					triggerCh = i;
					break;
				}
			}
			if (triggerCh === -1) return null;
			var from = { line: cur.line, ch: triggerCh };
			var to = cur;
			var searchTerm = line.substring(triggerCh + 1, ch).toLowerCase().trim();
			var columns = getTableColumnsWithTitles();
			var list = [];
			for (var j = 0; j < columns.length; j++) {
				var name = columns[j].name;
				var title = columns[j].title;
				if (searchTerm) {
					var nameLower = name.toLowerCase();
					var titleLower = (title || '').toLowerCase();
					if (nameLower.indexOf(searchTerm) === -1 && titleLower.indexOf(searchTerm) === -1) continue;
				}
				list.push({
					text: '@' + name,
					displayText: '@' + name,
					title: title,
					render: function (elt, data, curItem) {
						elt.appendChild(document.createTextNode(curItem.text));
						var span = document.createElement('span');
						span.className = 'ubai-hint-title';
						span.textContent = (curItem.title != null ? curItem.title : '');
						elt.appendChild(span);
					}
				});
			}
			return { list: list, from: from, to: to };
		}

		/**
		 * CodeMirror instance bound to a specific prompt textarea (via jQuery .data).
		 *
		 * @param {string} prompt_input_selector jQuery selector for the textarea.
		 * @return {object|null}
		 */
		function getPromptCodeMirrorFor(prompt_input_selector) {
			var $el = $plugin.find(prompt_input_selector);
			if (!$el.length) {
				return null;
			}
			var cm = $el.data('codemirror');
			return cm || null;
		}

		/**
		 * Init CodeMirror on a prompt textarea with @/# hints and placeholder.
		 */
		function initPromptCodeMirror(prompt_input_selector) {
			if (!prompt_input_selector) {
				prompt_input_selector = sidebarPromptSelector;
			}
			if (typeof window.CodeMirror === 'undefined') return;
			var $ta = $plugin.find(prompt_input_selector);
			if (!$ta.length || $ta[0].getAttribute('data-cm-inited') === '1') return;
			var ta = $ta[0];
			var placeholderText = (ta.getAttribute('placeholder') || '').trim();
			var opts = {
				mode: 'text/plain',
				lineNumbers: false,
				lineWrapping: true,
				placeholder: placeholderText || undefined,
				extraKeys: {
					'@': function (cm) {
						var cur = cm.getCursor();
						cm.replaceRange('@', cur, cur);
						cm.setCursor({ line: cur.line, ch: cur.ch + 1 });
						if (typeof cm.showHint === 'function') cm.showHint({ hint: ubaiPromptHint });
					},
					'#': function (cm) {
						var cur = cm.getCursor();
						cm.replaceRange('#', cur, cur);
						cm.setCursor({ line: cur.line, ch: cur.ch + 1 });
						if (typeof cm.showHint === 'function') cm.showHint({ hint: ubaiPromptHint });
					}
				},
				hintOptions: { hint: ubaiPromptHint }
			};
			var cm = window.CodeMirror.fromTextArea(ta, opts);
			ta.setAttribute('data-cm-inited', '1');
			$(ta).data('codemirror', cm);
			cm.addOverlay({
				token: function (stream) {
					if (stream.match(/^@[\w\-]+/, false)) {
						stream.match(/^@[\w\-]+/);
						return 'ubai-ref';
					}
					stream.next();
					return null;
				}
			});
			if (placeholderText) {
				var wrapper = cm.getWrapperElement();
				var placeholderEl = document.createElement('div');
				placeholderEl.className = 'ubai-cm-placeholder';
				placeholderEl.textContent = placeholderText;
				placeholderEl.setAttribute('aria-hidden', 'true');
				wrapper.style.position = 'relative';
				placeholderEl.style.cssText = 'position:absolute;top:0;left:0;right:0;padding:8px 12px;margin:0;color:#999;pointer-events:none;white-space:pre-wrap;word-wrap:break-word;font:inherit;line-height:1.5;';
				wrapper.appendChild(placeholderEl);
				function updatePlaceholder() {
					placeholderEl.style.display = cm.getValue().trim() === '' ? '' : 'none';
				}
				cm.on('change', updatePlaceholder);
				cm.on('focus', updatePlaceholder);
				cm.on('blur', updatePlaceholder);
				updatePlaceholder();
			}
			cm.on('change', function () {
				ta.value = cm.getValue();
			});
			cm.on('inputRead', function (cmInst, change) {
				if (change.origin === '+input' && change.text && change.text.length > 0) {
					var ch = change.text[0];
					if (ch === '@' || ch === '#') {
						setTimeout(function () {
							if (typeof cm.showHint === 'function') cm.showHint({ hint: ubaiPromptHint });
						}, 10);
					}
				}
			});
		}

		/**
		 * Get the current value from a prompt textarea (CodeMirror or plain textarea).
		 */
		function getPromptInputValue(prompt_input_selector) {
			if (!prompt_input_selector) {
				prompt_input_selector = sidebarPromptSelector;
			}
			var $el = $plugin.find(prompt_input_selector);
			if (!$el.length) {
				return '';
			}
			var cm = getPromptCodeMirrorFor(prompt_input_selector);
			if (cm) {
				return cm.getValue().trim();
			}
			return $el.val().trim();
		}

		/**
		 * Set a prompt textarea value (CodeMirror or plain textarea); refreshes CodeMirror display.
		 */
		function setPromptInputValue(text, prompt_input_selector) {
			if (!prompt_input_selector) {
				prompt_input_selector = sidebarPromptSelector;
			}
			var $el = $plugin.find(prompt_input_selector);
			if (!$el.length) {
				return;
			}
			var cm = getPromptCodeMirrorFor(prompt_input_selector);
			if (cm) {
				cm.setValue(typeof text === 'string' ? text : '');
				$el[0].value = cm.getValue();
				if (typeof cm.refresh === 'function') {
					cm.refresh();
				}
			} else {
				$el.val(typeof text === 'string' ? text : '');
			}
		}

		/**
		 * Update placeholder on a prompt textarea (CodeMirror custom placeholder or native attribute).
		 */
		function updatePromptInputPlaceholder(placeholderText, prompt_input_selector) {
			if (!prompt_input_selector) {
				prompt_input_selector = sidebarPromptSelector;
			}
			var $el = $plugin.find(prompt_input_selector);
			if (!$el.length) {
				return;
			}
			var text = typeof placeholderText === 'string' ? placeholderText : '';
			$el[0].setAttribute('placeholder', text);
			var cm = getPromptCodeMirrorFor(prompt_input_selector);
			if (!cm) {
				return;
			}
			var wrapper = cm.getWrapperElement();
			if (!wrapper) {
				return;
			}
			var placeholderEl = wrapper.querySelector('.ubai-cm-placeholder');
			if (placeholderEl) {
				placeholderEl.textContent = text;
				placeholderEl.style.display = cm.getValue().trim() === '' ? '' : 'none';
			}
		}

		/**
		 * Focus a prompt textarea (CodeMirror or plain textarea).
		 */
		function focusPromptInput(prompt_input_selector) {
			if (!prompt_input_selector) {
				prompt_input_selector = sidebarPromptSelector;
			}
			var $el = $plugin.find(prompt_input_selector);
			if (!$el.length) {
				return;
			}
			var cm = getPromptCodeMirrorFor(prompt_input_selector);
			if (cm) {
				cm.focus();
			} else {
				$el[0].focus();
			}
		}

		/**
		 * Ensure g_ubaiCellRules is a plain object (hidden input is JSON on first load).
		 *
		 * @return {Object}
		 */
		function ensureUbaiCellRulesObject() {
			if (typeof g_ubaiCellRules === 'undefined' || g_ubaiCellRules === null || g_ubaiCellRules === '') {
				g_ubaiCellRules = {};
				return g_ubaiCellRules;
			}
			if (typeof g_ubaiCellRules === 'string') {
				try {
					var parsed = JSON.parse(g_ubaiCellRules);
					g_ubaiCellRules = (parsed && typeof parsed === 'object') ? parsed : {};
				} catch (e) {
					g_ubaiCellRules = {};
				}
			}
			if (typeof g_ubaiCellRules !== 'object') {
				g_ubaiCellRules = {};
			}
			return g_ubaiCellRules;
		}

		function getUbaiPromptsStringsObject() {
			var raw = typeof g_ubaiPromptsStrings !== 'undefined' ? g_ubaiPromptsStrings : {};
			if (typeof raw === 'string' && raw.length) {
				try {
					var parsed = JSON.parse(raw);
					return (parsed && typeof parsed === 'object') ? parsed : {};
				} catch (e) {
					return {};
				}
			}
			return (raw && typeof raw === 'object') ? raw : {};
		}

		function qualityValueToDisplayLabel(value, strings) {
			var v = String(value || '').toLowerCase();
			if (v === 'low' || v === '0.5k') {
				return strings.imageQuality05K || 'Low';
			}
			if (v === 'medium' || v === '1k') {
				return strings.imageQuality1K || 'Medium';
			}
			if (v === 'high' || v === '1.5k' || v === '2k') {
				return strings.imageQuality2K || 'High';
			}
			return strings.imageOptionDefault || '---';
		}

		function formatValueToDisplayLabel(value, strings) {
			var v = String(value || '').toLowerCase();
			if (v === 'jpg') {
				v = 'jpeg';
			}
			if (v === 'png') {
				return strings.imageFormatPng || 'PNG';
			}
			if (v === 'jpeg') {
				return strings.imageFormatJpeg || 'JPEG';
			}
			if (v === 'webp') {
				return strings.imageFormatWebp || 'WebP';
			}
			return strings.imageOptionDefault || '---';
		}

		function resolutionValueToDisplayLabel(value, strings) {
			var v = String(value || '').toLowerCase();
			if (v === '1k') {
				return strings.imageResolution1K || '1K';
			}
			if (v === '2k') {
				return strings.imageResolution2K || '2K';
			}
			if (v === '3k') {
				return strings.imageResolution3K || '3K';
			}
			if (v === '4k') {
				return strings.imageResolution4K || '4K';
			}
			return strings.imageOptionDefault || '---';
		}

		/**
		 * Labels for the "default" dropdown row (plugin defaults, optionally merged with column rules).
		 *
		 * @param {string} columnName
		 * @param {boolean} mergeColumnRules When true, column rule keys define what "default" resolves to (sidebar).
		 * @return {Object}
		 */
		function getResolvedDefaultImageOptionLabels(columnName, mergeColumnRules) {
			var strings = getUbaiPromptsStringsObject();
			var labels = {
				quality: qualityValueToDisplayLabel(strings.imageQualityDefault || '', strings),
				format: formatValueToDisplayLabel(strings.imageFormatDefault || '', strings),
				resolution: resolutionValueToDisplayLabel(strings.imageResolutionDefault || '', strings)
			};

			if (!mergeColumnRules || !columnName) {
				return labels;
			}

			var rules = ensureUbaiCellRulesObject();
			var qKey = columnName + '__quality';
			var fKey = columnName + '__format';
			var resKey = columnName + '__resolution';

			if (rules[qKey] && String(rules[qKey]).trim() !== '' && String(rules[qKey]).toLowerCase() !== 'default') {
				labels.quality = qualityValueToDisplayLabel(rules[qKey], strings);
			}
			if (rules[fKey] && String(rules[fKey]).trim() !== '' && String(rules[fKey]).toLowerCase() !== 'default') {
				labels.format = formatValueToDisplayLabel(rules[fKey], strings);
			}
			if (rules[resKey] && String(rules[resKey]).trim() !== '' && String(rules[resKey]).toLowerCase() !== 'default') {
				labels.resolution = resolutionValueToDisplayLabel(rules[resKey], strings);
			}

			return labels;
		}

		/**
		 * Refresh gray "default" dropdown row labels (and default buttons) after rule changes.
		 *
		 * @param {jQuery} $root Container with image selectors.
		 * @param {string} columnName Active column key.
		 * @param {boolean} mergeColumnRules Sidebar: true; column settings dialog: false.
		 */
		function updateImageDefaultOptionLabels($root, columnName, mergeColumnRules) {
			if (!$root || !$root.length) {
				return;
			}
			var labels = getResolvedDefaultImageOptionLabels(columnName, mergeColumnRules === true);

			var $qDefault = $root.find('.ubai-quality-dropdown__item[data-quality="default"]').first();
			if ($qDefault.length) {
				$qDefault.find('.ubai-quality-dropdown__label').text(labels.quality);
			}
			var $fDefault = $root.find('.ubai-format-dropdown__item[data-format="default"]').first();
			if ($fDefault.length) {
				$fDefault.find('.ubai-format-dropdown__label').text(labels.format);
			}
			var $rDefault = $root.find('.ubai-resolution-dropdown__item[data-resolution="default"]').first();
			if ($rDefault.length) {
				$rDefault.find('.ubai-resolution-dropdown__label').text(labels.resolution);
			}

			var $qBtn = $root.find('.ubai-quality-selector__btn').first();
			if ($qBtn.length && String($qBtn.attr('data-quality') || '') === 'default') {
				$qBtn.find('.ubai-quality-selector__label').text(labels.quality);
			}
			var $fBtn = $root.find('.ubai-format-selector__btn').first();
			if ($fBtn.length && String($fBtn.attr('data-format') || '') === 'default') {
				$fBtn.find('.ubai-format-selector__label').text(labels.format);
			}
			var $rBtn = $root.find('.ubai-resolution-selector__btn').first();
			if ($rBtn.length && String($rBtn.attr('data-resolution') || '') === 'default') {
				$rBtn.find('.ubai-resolution-selector__label').text(labels.resolution);
			}
		}

		/**
		 * Read image control values from a ratio/quality/format/resolution widget root.
		 *
		 * @param {jQuery} $imgOpts
		 * @return {Object}
		 */
		function collectImageSettingsFromRoot($imgOpts) {
			var $rb = $imgOpts.find('.ubai-ratio-box').first();
			var $qb = $imgOpts.find('.ubai-quality-selector__btn').first();
			var $fb = $imgOpts.find('.ubai-format-selector__btn').first();
			var $resb = $imgOpts.find('.ubai-resolution-selector__btn').first();

			var $ratioActive = $imgOpts.find('.ubai-ratio-dropdown__item.active').first();
			var $qualityActive = $imgOpts.find('.ubai-quality-dropdown__item.active').first();
			var $formatActive = $imgOpts.find('.ubai-format-dropdown__item.active').first();
			var $resolutionActive = $imgOpts.find('.ubai-resolution-dropdown__item.active').first();

			var ratio = ($ratioActive.length && $ratioActive.attr('data-ratio'))
				? String($ratioActive.attr('data-ratio'))
				: (($rb.length && $rb.attr('data-ratio')) ? String($rb.attr('data-ratio')) : 'auto');
			var quality = ($qualityActive.length && $qualityActive.attr('data-quality'))
				? String($qualityActive.attr('data-quality'))
				: (($qb.length && $qb.attr('data-quality')) ? String($qb.attr('data-quality')) : 'default');
			var format = ($formatActive.length && $formatActive.attr('data-format'))
				? String($formatActive.attr('data-format'))
				: (($fb.length && $fb.attr('data-format')) ? String($fb.attr('data-format')) : 'default');
			var resolution = ($resolutionActive.length && $resolutionActive.attr('data-resolution'))
				? String($resolutionActive.attr('data-resolution'))
				: (($resb.length && $resb.attr('data-resolution')) ? String($resb.attr('data-resolution')) : 'default');

			return {
				aspect_ratio: ratio || 'auto',
				quality: quality || 'default',
				format: format || 'default',
				resolution: resolution || 'default'
			};
		}

		/**
		 * Whether the column is an image type (Featured Image or other image columns).
		 */
		function isCellRulesImageColumn(columnName) {

			if (!columnName) {
				return false;
			}
			if (columnName === 'post_image') {
				return true;
			}
			var $th = $plugin.find('#new_output_table thead th[data-name]').filter(function () {
				return $(this).attr('data-name') === columnName;
			}).first();
			return $th.length > 0 && String($th.attr('data-type') || '') === 'image';
		}

		/**
		 * Apply saved g_ubaiCellRules aspect/quality to the shared ratio widgets in the cell rules panel.
		 */
		function getCellRulesImageOptionsRoot() {
			if ($cellRulesPanel && $cellRulesPanel.length) {
				return $cellRulesPanel.find('.ubai-cell-rules-panel__image-options').first();
			}
			return $plugin.find('.ubai-cell-rules-panel__image-options').first();
		}

		function syncCellRulesImageControlsFromStorage(columnName) {

			if (!columnName || !isCellRulesImageColumn(columnName)) {
				return;
			}
			var rules = ensureUbaiCellRulesObject();
			var $imgOpts = getCellRulesImageOptionsRoot();
			if (!$imgOpts.length) {
				return;
			}
			var arKey = columnName + '__aspect_ratio';
			var qKey = columnName + '__quality';
			var fKey = columnName + '__format';
			var resKey = columnName + '__resolution';
			var ar = rules[arKey] ? String(rules[arKey]) : 'auto';
			var defaultQuality = 'default';
			var defaultFormat = 'default';
			var defaultResolution = 'default';

			var q = rules[qKey] ? String(rules[qKey]) : defaultQuality;
			// Back-compat: old UI stored 0.5K/1K/1.5K/2K; normalize to low/medium/high.
			var qLower = (q || '').toLowerCase();
			if (qLower === '0.5k') q = 'low';
			else if (qLower === '1k') q = 'medium';
			else if (qLower === '1.5k' || qLower === '2k') q = 'high';
			else if (qLower !== 'default' && qLower !== 'low' && qLower !== 'medium' && qLower !== 'high') {
				q = defaultQuality;
			}

			var format = rules[fKey] ? String(rules[fKey]) : defaultFormat;
			var formatLower = (format || '').toLowerCase();
			if (formatLower === 'jpg') formatLower = 'jpeg';
			if (formatLower !== 'default' && formatLower !== 'png' && formatLower !== 'jpeg' && formatLower !== 'webp') {
				formatLower = defaultFormat;
			}
			format = formatLower;

			var $ratioItem = $imgOpts.find('.ubai-ratio-dropdown__item[data-ratio]').filter(function () {
				return $(this).attr('data-ratio') === ar;
			}).first();
			if ($ratioItem.length) {
				$ratioItem.trigger('click');
			}

			var $qItem = $imgOpts.find('.ubai-quality-dropdown__item[data-quality]').filter(function () {
				return $(this).attr('data-quality') === q;
			}).first();
			if ($qItem.length) {
				$qItem.trigger('click');
			}

			var $fItem = $imgOpts.find('.ubai-format-dropdown__item[data-format]').filter(function () {
				return $(this).attr('data-format') === format;
			}).first();
			if ($fItem.length) {
				$fItem.trigger('click');
			}

			var resolution = rules[resKey] ? String(rules[resKey]) : defaultResolution;
			resolution = (resolution || '').toLowerCase();
			if (resolution !== 'default' && ['1k', '2k', '3k', '4k'].indexOf(resolution) < 0) {
				resolution = defaultResolution;
			}

			var $resItem = $imgOpts.find('.ubai-resolution-dropdown__item[data-resolution]').filter(function () {
				return $(this).attr('data-resolution') === resolution;
			}).first();
			if ($resItem.length) {
				$resItem.trigger('click');
			}

			updateImageDefaultOptionLabels($imgOpts, columnName, false);
		}

		/**
		 * Cell rules panel: bind events and open trigger.
		 */
		function initCellRules() {

			$cellRulesPanel = $plugin.find('#ubai_cell_rules_panel');
			$cellRulesTitle = $plugin.find('#ubai_cell_rules_title');
			$cellRulesSubtitle = $plugin.find('#ubai_cell_rules_subtitle');
			$cellRulesClose = $plugin.find('.ubai-cell-rules-panel__close');
			$cellRulesBackdrop = $plugin.find('.ubai-cell-rules-panel__backdrop');
			$cellRulesCancel = $plugin.find('.ubai-cell-rules-panel__btn--cancel');
			$cellRulesSave = $plugin.find('.ubai-cell-rules-panel__btn--save');
			$cellRulesTextarea = $plugin.find('#ubai_cell_rules_prompt');
			$cellRulesApplyOnPromptPaste = $plugin.find('#ubai_cell_rules_apply_on_paste');
			$cellRulesAutoApply = $plugin.find('#ubai_cell_rules_autoapply');

			if (!$cellRulesPanel.length) return;

			$cellRulesClose.on('click', closeCellRules);
			$cellRulesBackdrop.on('click', closeCellRules);
			$cellRulesCancel.on('click', closeCellRules);
			$cellRulesSave.on('click', onCellRulesSaveClick);

			$plugin.on('click', '.unlimitedai-plugin__ai-column-settings-icon', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var $th = $(e.currentTarget).closest('.unlimitedai-plugin__th');
				currentCellRuleColumnName = $th.attr('data-name') || '';
				currentCellRuleColumnTitle = $th.find('.unlimitedai-plugin__th-title').text().trim();
				showCellRules(currentCellRuleColumnTitle);
			});
		}

		/**
		 * Save the current cell rule from the cell rules panel and close it.
		 */
		function onCellRulesSaveClick() {

			var promptText = getPromptInputValue(cellRulesPromptSelector);
			var postType = $plugin.find('#ubai_post_type_selector').val() || '';
			var applyPromptOnPaste = $plugin.find('#ubai_cell_rules_apply_on_paste').is(':checked');
			var autoApplyResponse = $plugin.find('#ubai_cell_rules_autoapply').is(':checked');
			var data = {
				post_type: postType,
				column: currentCellRuleColumnName,
				prompt: promptText,
				apply_prompt_on_paste: applyPromptOnPaste,
				auto_apply_response: autoApplyResponse,
			};

			if (isCellRulesImageColumn(currentCellRuleColumnName)) {
				var $imgOpts = getCellRulesImageOptionsRoot();
				if ($imgOpts.length) {
					data.cell_rule_image = collectImageSettingsFromRoot($imgOpts);
				}
			}
			closeCellRules();
			if (typeof objPostsEditorView !== 'undefined' && objPostsEditorView.saveCellRule) {
				objPostsEditorView.saveCellRule(data, function () {
					var $sidebarPanel = $plugin.find('#ubai_sidebar_image_panel');
					if ($sidebarPanel.length && currentCellRuleColumnName) {
						updateImageDefaultOptionLabels($sidebarPanel, currentCellRuleColumnName, true);
					}
				});
			}
		}

		/**
		 * Show the cell rules panel with title and saved prompt for the current column.
		 */
		function showCellRules(columnTitle) {

			var strings = typeof g_ubaiPromptsStrings !== 'undefined' ? g_ubaiPromptsStrings : {};
			if ($cellRulesTitle.length) {
				$cellRulesTitle.text(strings.cellRules || 'AI Column Settings');
			}
			if ($cellRulesSubtitle.length) {
				if (columnTitle && strings.applyForColumn) {
					var escapedColumn = $('<div>').text(columnTitle).html();
					var subtitleHtml = strings.applyForColumn.replace('%s', '<span class="ubai-cell-rules-panel__subtitle-column">' + escapedColumn + '</span>');
					$cellRulesSubtitle.html(subtitleHtml).removeAttr('aria-hidden').show();
				} else {
					$cellRulesSubtitle.text('').attr('aria-hidden', 'true').hide();
				}
			}
			var rules = ensureUbaiCellRulesObject();
			var savedPrompt = rules[currentCellRuleColumnName] ? String(rules[currentCellRuleColumnName]) : '';
			var promptOnPaste = !!(rules[currentCellRuleColumnName + '__prompt_on_paste'] && String(rules[currentCellRuleColumnName + '__prompt_on_paste']) === 'true');
			var autoApplyResponse = !!(rules[currentCellRuleColumnName + '__auto_apply_response'] && String(rules[currentCellRuleColumnName + '__auto_apply_response']) === 'true');





			if ($cellRulesTextarea.length) {
				$cellRulesTextarea.val(savedPrompt);
			}
			if ($cellRulesApplyOnPromptPaste.length) {
				$cellRulesApplyOnPromptPaste.prop('checked', promptOnPaste);
			}
			if ($cellRulesAutoApply.length) {
				$cellRulesAutoApply.prop('checked', autoApplyResponse);
			}
			var $cellRulesImgOpts = $plugin.find('.ubai-cell-rules-panel__image-options');
			if ($cellRulesImgOpts.length) {
				$cellRulesImgOpts.toggle(isCellRulesImageColumn(currentCellRuleColumnName));
			}
			$cellRulesPanel.addClass('ubai-cell-rules-panel--visible');
			setTimeout(function () {
				syncCellRulesImageControlsFromStorage(currentCellRuleColumnName);
				if ($cellRulesTextarea.length) {
					$cellRulesTextarea[0].focus();

					//init prompt
					initPromptCodeMirror(cellRulesPromptSelector);
					setPromptInputValue(savedPrompt, cellRulesPromptSelector);

				}
			}, 100);
		}

		/**
		 * Hide the cell rules panel.
		 */
		function closeCellRules() {

			$cellRulesPanel.removeClass('ubai-cell-rules-panel--visible');
		}

		/**
		 * Open the cell rules panel for a given column (e.g. from context menu "Apply column rules").
		 * @param {string} columnName - Column data-name value.
		 */
		function openCellRulesForColumn(columnName) {
			if (!columnName || typeof columnName !== 'string') return;
			currentCellRuleColumnName = columnName;
			var $th = $plugin.find('#new_output_table th[data-name]').filter(function () {
				return $(this).attr('data-name') === columnName;
			});
			currentCellRuleColumnTitle = $th.find('.unlimitedai-plugin__th-title').text().trim() || columnName;
			showCellRules(currentCellRuleColumnTitle);
		}

		/**
		 * Init Prompt History panel: cache refs, bind events, render demo data.
		 */
		function initPromptHistoryPanel() {

			$promptHistoryPanel = $plugin.find('#ubai_prompt_history_panel');
			$promptHistoryBack = $plugin.find('#ubai_prompt_history_back');
			$promptHistorySearch = $plugin.find('#ubai_prompt_history_search');
			$promptHistoryList = $plugin.find('#ubai_prompt_history_list');
			$promptHistoryListWrap = $plugin.find('#ubai_prompt_history_list_wrap');
			$promptHistoryCount = $plugin.find('#ubai_prompt_history_count');
			$filterAll = $plugin.find('#ubai_prompt_history_filter_all');
			$filterSaved = $plugin.find('#ubai_prompt_history_filter_saved');
			$promptHistoryClearBtn = $plugin.find('#ubai_prompt_history_clear_all');

			if (!$promptHistoryPanel.length) return;

			$promptHistoryBack.on('click', closePromptHistoryPanel);
			$promptHistoryClearBtn.on('click', onPromptHistoryClearAllClick);
			$promptHistorySearch.on('input', onPromptHistorySearchInput);
			$filterAll.on('click', function () { setPromptHistoryFilter('all'); });
			$filterSaved.on('click', function () { setPromptHistoryFilter('saved'); });
			$promptHistoryList.on('click', '.unlimitedai-plugin__prompt-history-item-edit', onPromptHistoryEditClick);
			$promptHistoryList.on('click', '.unlimitedai-plugin__prompt-history-item-star', onPromptHistoryStarClick);
			$promptHistoryList.on('click', '.unlimitedai-plugin__prompt-history-item-copy', onPromptHistoryCopyClick);
			$promptHistoryList.on('click', '.unlimitedai-plugin__prompt-history-item-run', onPromptHistoryRunClick);
			$promptHistoryList.on('click', '.unlimitedai-plugin__prompt-history-item-delete', onPromptHistoryDeleteClick);
			$(document).on('keydown.ubaiPromptHistory', function (e) {
				if (e.key === 'Escape' && $promptHistoryPanel.closest('.unlimitedai-plugin__sidebar').hasClass('ubai-sidebar-showing-history')) {
					closePromptHistoryPanel();
				}
			});
		}

		/**
		 * Filter the pre-rendered prompt history list by current filter and search (show/hide DOM items).
		 */
		function filterDomPromptHistoryList() {

			if (!$promptHistoryList.length) return;

			var search = ($promptHistorySearch && $promptHistorySearch.length) ? $promptHistorySearch.val() : '';
			if (typeof search !== 'string') search = '';
			search = search.trim().toLowerCase();

			// cell type filter
			var activeCellType = $(`.${activeCellClassSelector}`).find('.editor_container').data('type');

			var $items = $promptHistoryList.find('.unlimitedai-plugin__prompt-history-item');
			var visibleCount = 0;
			$items.each(function () {
				var $el = $(this);
				var isSaved = $el.attr('data-is-saved') === '1';
				var title = ($el.find('.unlimitedai-plugin__prompt-history-item-title').text() || '').toLowerCase();
				var text = ($el.find('.unlimitedai-plugin__prompt-history-item-text').text() || '').toLowerCase();
				var show = true;
				var promptType = $el.data('prompt-type');
				var showByType = true;

				//cell type filter
				if( activeCellType ){
					if( activeCellType == 'image' || activeCellType == 'acf_woo_gallery'   ){
						if (promptType === 'pending_image' ) {
							showByType = true;
						} else {
							showByType = false;
						}
					}else{
						if (promptType !== 'pending_image' ) {
							showByType = true;
						} else {
							showByType = false;
						}
					}
				}

				if (promptHistoryFilter === 'saved' && !isSaved) show = false; 
				if (promptHistoryFilter === 'all' && isSaved) show = false;
				if (show && search && title.indexOf(search) === -1 && text.indexOf(search) === -1) show = false;
				if ( showByType == false ) show = false;

				$el.toggle(show);
				if (show) visibleCount++;
			});
			$promptHistoryCount.text(visibleCount);
			if ($promptHistoryListWrap.length) {
				$promptHistoryListWrap.toggleClass('ubai-filter-saved', promptHistoryFilter === 'saved');
			}
		}

		/**
		 * Filter the prompt history list based on cell type.
		 */
		function filterDomPromptHistoryListBasedOnCellType( showItems ) {
			console.log(  showItems );
			if (!$promptHistoryList.length) return;

			if (!$(`.${activeCellClassSelector}`).length) return;

			var currentCellType = $(`.${activeCellClassSelector}`).find('.editor_container').data('type');
	 
			var $items = $promptHistoryList.find('.unlimitedai-plugin__prompt-history-item');
			var visibleCount = 0;
			$items.each(function () {
				var $el = $(this);
				$el.addClass('processed');
				var promptType = $el.data('prompt-type');

				var show = true;

				if( showItems == 'image'  ){
					if (promptType === 'pending_image' ) {
						show = true;
					} else {
						show = false;
					}
				}
				

				if( showItems == 'data' ){
					if (promptType !== 'pending_image'  ) {
						show = true;
					} else {
						show = false;
					}
				}
				

				$el.toggle(show);
				if (show) visibleCount++;
			});
			$promptHistoryCount.text(visibleCount);



		}

		/**
		 * Open the Prompt History panel (list HTML is already in the page from PHP).
		 */
		function openPromptHistoryPanel() {

			if (!$promptHistoryPanel.length) return;

			$promptHistoryPanel.closest('.unlimitedai-plugin__sidebar').addClass('ubai-sidebar-showing-history');
			$promptHistoryPanel.attr('aria-hidden', 'false');
			promptHistoryFilter = 'all';
			$filterAll.addClass('active');
			$filterSaved.removeClass('active');
			$promptHistorySearch.val('');
			filterDomPromptHistoryList();
			if ($promptHistorySearch.length) {
				$promptHistorySearch[0].focus();
			}
		}

		/**
		 * Close the Prompt History panel.
		 */
		function closePromptHistoryPanel() {

			if (!$promptHistoryPanel.length) return;

			$promptHistoryPanel.closest('.unlimitedai-plugin__sidebar').removeClass('ubai-sidebar-showing-history');
			$promptHistoryPanel.attr('aria-hidden', 'true');
		}

		/**
		 * Set prompt history filter to 'all' or 'saved'; filter the existing list in the DOM (no AJAX).
		 */
		function setPromptHistoryFilter(filter) {

			promptHistoryFilter = filter;
			$filterAll.toggleClass('active', filter === 'all');
			$filterSaved.toggleClass('active', filter === 'saved');
			filterDomPromptHistoryList();
		}

		/**
		 * Debounced handler for prompt history search input; re-filters the list.
		 */
		function onPromptHistorySearchInput() {

			if (promptHistorySearchDebounce) clearTimeout(promptHistorySearchDebounce);
			promptHistorySearchDebounce = setTimeout(function () {
				filterDomPromptHistoryList();
			}, 250);
		}

		/**
		 * Fetch prompt history from server and render. Uses current filter and search.
		 * @param {Function} [callback] Optional callback after render.
		 * @param {boolean} [showLoader=true] If true, show loader in the list area; if false, refresh silently (e.g. after save/delete).
		 */
		function loadPromptHistoryList(callback, showLoader) {

			var search = ($promptHistorySearch && $promptHistorySearch.length) ? $promptHistorySearch.val() : '';
			if (typeof search !== 'string') search = '';
			search = search.trim();

			var ajaxRequest = (typeof g_doublyAdmin !== 'undefined' && g_doublyAdmin.ajaxRequest)
				? function (action, data, done) { g_doublyAdmin.ajaxRequest(action, data, done); }
				: null;
			if (!ajaxRequest) {
				if ($promptHistoryList.length) $promptHistoryList.html('<div class="unlimitedai-plugin__prompt-history-empty">No prompts found.</div>');
				if (typeof callback === 'function') callback();
				return;
			}

			if (showLoader !== false && $promptHistoryListWrap.length) {
				$promptHistoryListWrap.addClass('is-loading');
			}

			g_doublyAdmin.setAjaxLoaderID(g_cellProcessingObj.g_ajaxLoaderProcessing);
			ajaxRequest('get_prompt_history', {
				filter: 'all',
				search: search,
				limit: 100
			}, function (response) {
				if (showLoader !== false && $promptHistoryListWrap.length) {
					$promptHistoryListWrap.removeClass('is-loading');
				}
				var msg = response && response.message;
				var items = (msg && Array.isArray(msg.items)) ? msg.items : [];
				var totalRecent = (msg && typeof msg.totalRecent === 'number') ? msg.totalRecent : 0;
				var totalSaved = (msg && typeof msg.totalSaved === 'number') ? msg.totalSaved : 0;

				$filterAll.find('.unlimitedai-plugin__prompt-history-filter-num[data-num="all"]').text(totalRecent);
				$filterSaved.find('.unlimitedai-plugin__prompt-history-filter-num[data-num="saved"]').text(totalSaved);

				var itemsHtml = (msg && typeof msg.itemsHtml === 'string') ? msg.itemsHtml : '';
				$promptHistoryList.html(itemsHtml || '<div class="unlimitedai-plugin__prompt-history-empty">No prompts found.</div>');
				filterDomPromptHistoryList();
				if (typeof callback === 'function') callback();
			});
		}

		/**
		 * Update the prompt history panel list and counts from a payload (e.g. apply_prompt response).
		 * No loader; used when run-prompt AJAX returns promptHistory (recent list).
		 */
		function updatePromptHistoryFromResponse(payload) {
			if (!payload || typeof payload !== 'object') return;
			if (!$promptHistoryList.length || !$filterAll.length || !$filterSaved.length) return;
			var totalRecent = typeof payload.totalRecent === 'number' ? payload.totalRecent : 0;
			var totalSaved = typeof payload.totalSaved === 'number' ? payload.totalSaved : 0;
			$filterAll.find('.unlimitedai-plugin__prompt-history-filter-num[data-num="all"]').text(totalRecent);
			$filterSaved.find('.unlimitedai-plugin__prompt-history-filter-num[data-num="saved"]').text(totalSaved);
			var itemsHtml = typeof payload.itemsHtml === 'string' ? payload.itemsHtml : '';
			$promptHistoryList.html(itemsHtml || '<div class="unlimitedai-plugin__prompt-history-empty">No prompts found.</div>');
			filterDomPromptHistoryList();
		}

		/**
		 * Rebuild the Saved Prompts submenu in the text context menu (#ubai_text_context_menu_template).
		 * @param {Array<{action: string, text: string, prompt: string}>} items - Sub-items from server.
		 * @param {string} [menuLabel] - Parent row label (translated).
		 */
		function updateTextContextMenuSavedPromptsSubItems(items, menuLabel) {

			var $menu = $('#ubai_text_context_menu_template');
			if (!$menu.length) {
				return;
			}

			var label = (typeof menuLabel === 'string' && menuLabel.trim()) ? menuLabel.trim() : 'Saved Prompts';
			var $row = $menu.find('[data-action="saved-prompts"]').first();
			var heartSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path></svg>';

			if (!items.length) {
				if ($row.length) {
					$row.remove();
				}
				if (typeof g_savedPromptsText !== 'undefined') {
					g_savedPromptsText = {};
				}
				return;
			}

			var $sub;
			if (!$row.length) {
				var $chevronTpl = $menu.find('[data-action="translate"] .unlimitedai-plugin__context-menu__item-chevron').first();
				$row = $('<div>', {
					class: 'unlimitedai-plugin__context-menu__item',
					'data-action': 'saved-prompts',
					'data-prompt': '',
					'data-visible-for': 'text',
					'data-visible-in-columns': ''
				});
				$row.append($('<span class="unlimitedai-plugin__context-menu__item-icon"></span>').html(heartSvg));
				$row.append($('<span class="unlimitedai-plugin__context-menu__item-text "></span>').text(label));
				if ($chevronTpl.length) {
					$row.append($chevronTpl.clone());
				}
				$sub = $('<div>', { class: 'unlimitedai-plugin__context-menu__sub', role: 'menu', 'aria-hidden': 'true' });
				$row.append($sub);
				var $after = $menu.find('[data-action="fix-grammar"]').first();
				if ($after.length) {
					$after.after($row);
				} else {
					$menu.append($row);
				}
			} else {
				$row.find('.unlimitedai-plugin__context-menu__item-text').first().text(label);
				$sub = $row.find('.unlimitedai-plugin__context-menu__sub').first();
				if (!$sub.length) {
					$sub = $('<div>', { class: 'unlimitedai-plugin__context-menu__sub', role: 'menu', 'aria-hidden': 'true' });
					$row.append($sub);
				}
				$sub.empty();
			}

			$.each(items, function (_, it) {
				if (!it || !it.action || typeof it.prompt !== 'string') {
					return;
				}
				var $item = $('<div>', {
					class: 'unlimitedai-plugin__context-menu__item unlimitedai-plugin__context-menu__item--sub',
					'data-action': it.action,
					'data-visible-for': 'text',
					'data-visible-in-columns': ''
				});
				$item.attr('data-prompt', it.prompt);
				$item.append($('<span class="unlimitedai-plugin__context-menu__item-icon"></span>'));
				$item.append($('<span class="unlimitedai-plugin__context-menu__item-text "></span>').text(typeof it.text === 'string' ? it.text : ''));
				$sub.append($item);
			});

			if (typeof g_savedPromptsText !== 'undefined') {
				var map = {};
				$.each(items, function (_, it) {
					if (!it || !it.action) {
						return;
					}
					var id = String(it.action).replace(/^saved-prompt-/, '');
					if (id && typeof it.prompt === 'string') {
						map[id] = it.prompt;
					}
				});
				g_savedPromptsText = map;
			}
		}

		/**
		 * Update Latest prompts dropdown from panel action response.
		 * All panel-related UI lives in this file; we update the sidebar dropdowns directly.
		 */
		function refreshDropdownsFromResponse(response) {

			if (!response || typeof response !== 'object') return;
			var $root = $plugin;
			if (!$root.length) return;
			if (Array.isArray(response.savedPromptsSubItems)) {
				updateTextContextMenuSavedPromptsSubItems(response.savedPromptsSubItems, response.savedPromptsMenuLabel);
			}
		}

		/**
		 * Bind sidebar history button to open/close the prompt history panel.
		 */
		function initPromptHistorySidebarButton() {
			var $btn = $plugin.find('#ubai_sidebar_history');
			var $sidebar = $plugin.find('.unlimitedai-plugin__sidebar');
			if (!$btn.length || !$sidebar.length) return;
			$btn.off('click.ubaiPromptHistory').on('click.ubaiPromptHistory', function (e) {
				e.preventDefault();
				if ($sidebar.hasClass('ubai-sidebar-showing-history') && closePromptHistoryPanel) {
					closePromptHistoryPanel();
				} else if (openPromptHistoryPanel) {
					openPromptHistoryPanel();
				} else {
					var url = $btn.attr('data-prompt-history-url');
					if (url) window.location.href = url;
				}
			});
		}

		/**
		 * Initialize the Save Prompt side dialog: cache refs, bind close/cancel/save/backdrop. Shown at start via CSS class.
		 */
		function initSavePromptDialog() {
			$savePromptDialog = $plugin.find('#ubai_save_prompt_dialog');
			$savePromptClose = $savePromptDialog.find('.ubai-save-prompt-dialog__close');
			$savePromptBackdrop = $savePromptDialog.find('.ubai-save-prompt-dialog__backdrop');
			$savePromptCancel = $savePromptDialog.find('.ubai-save-prompt-dialog__btn--cancel');
			$savePromptSave = $savePromptDialog.find('.ubai-save-prompt-dialog__btn--save');
			$savePromptTitleInput = $savePromptDialog.find('#ubai_save_prompt_title');
			$savePromptTextarea = $savePromptDialog.find('#ubai_save_prompt_text');
			if (!$savePromptDialog.length) return;
			$savePromptClose.on('click', closeSavePromptDialog);
			$savePromptBackdrop.on('click', closeSavePromptDialog);
			$savePromptCancel.on('click', closeSavePromptDialog);
			$savePromptSave.on('click', onSavePromptSaveClick);
		}

		/**
		 * Show the Save Prompt dialog and optionally pre-fill title and prompt text (e.g. when opening from heart icon).
		 */
		function openSavePromptDialog(promptId, title, text) {
			if (!$savePromptDialog || !$savePromptDialog.length) return;
			savePromptPendingId = promptId || null;
			if ($savePromptTitleInput && $savePromptTitleInput.length) {
				$savePromptTitleInput.val(typeof title === 'string' ? title : '');
			}
			if ($savePromptTextarea && $savePromptTextarea.length) {
				$savePromptTextarea.val(typeof text === 'string' ? text : '');
			}
			$savePromptDialog.addClass('ubai-save-prompt-dialog--visible');
		}

		/**
		 * Hide the Save Prompt dialog (remove visible class) and clear pending prompt id.
		 */
		function closeSavePromptDialog() {
			if ($savePromptDialog && $savePromptDialog.length) {
				$savePromptDialog.removeClass('ubai-save-prompt-dialog--visible');
			}
			savePromptPendingId = null;
		}

		/**
		 * Handle Save button in Save Prompt dialog: optimistic UI (color heart, bump Saved count), close dialog, then run save_prompt_to_saved AJAX; revert on failure.
		 */
		function onSavePromptSaveClick() {
			var promptId = savePromptPendingId;
			closeSavePromptDialog();
			if (!promptId) return;

			var $item = $promptHistoryList.find('.unlimitedai-plugin__prompt-history-item[data-id="' + promptId + '"]');
			var $savedNumEl = $filterSaved.find('.unlimitedai-plugin__prompt-history-filter-num[data-num="saved"]');
			var strings = typeof g_ubaiPromptsStrings !== 'undefined' ? g_ubaiPromptsStrings : {};
			var labelSave = strings.promptHistoryStarSave || 'Save';
			var labelRemoveFromSaved = strings.promptHistoryStarRemoveFromSaved || 'Remove From Saved';
			var postType = $plugin.find('#ubai_post_type_selector').val() || '';


			if (typeof g_doublyAdmin !== 'undefined' && g_doublyAdmin.ajaxRequest) {
				g_doublyAdmin.setAjaxLoaderID(g_cellProcessingObj.g_ajaxLoaderProcessing);
				var data = {
					prompt_id: promptId,
					prompt_title: $savePromptTitleInput.val(),
					post_type: postType,
					prompt_content: $savePromptTextarea.val(),
					prompt_type: $item.data('prompt-type'),
				};
		 
				g_doublyAdmin.ajaxRequest('save_prompt_to_saved', data, function (response) {

					if (response && response.success) {
						var totalSaved = typeof response.totalSaved === 'number' ? response.totalSaved : 0;
						if ($filterSaved.length && totalSaved >= 0) $filterSaved.find('.unlimitedai-plugin__prompt-history-filter-num[data-num="saved"]').text(totalSaved);
						refreshDropdownsFromResponse(response);


						if ($item.length) {
							var newItemId = response.savedPromptsSubItems[0].id;
							// create new
							if (newItemId != promptId) {
								var clonedItem = $item.clone();
								clonedItem.attr('data-is-saved', '1');
								clonedItem.hide();
								$('.unlimitedai-plugin__prompt-history-list').prepend(clonedItem);

								clonedItem.attr('data-id', newItemId);
								$('.unlimitedai-plugin__prompt-history-item-title', clonedItem).html($savePromptTitleInput.val());
								$('.unlimitedai-plugin__prompt-history-item-text', clonedItem).html($savePromptTextarea.val());
							} else {
								// update item
								$('.unlimitedai-plugin__prompt-history-item-title', $item).html($savePromptTitleInput.val());
								$('.unlimitedai-plugin__prompt-history-item-text', $item).html($savePromptTextarea.val());
							}

						}
					} else if ($item.length) {
						var $star = $item.find('.unlimitedai-plugin__prompt-history-item-star');
						$star.removeClass('is-saved');
						$star.attr('aria-label', labelSave).attr('title', labelSave);
						$item.attr('data-is-saved', '0');
						var savedCount = parseInt($savedNumEl.text(), 10) || 1;
						$savedNumEl.text(Math.max(0, savedCount - 1));
					}
				});
			}
		}

		/**
		 * Toggle saved state for a prompt history item (star). If not saved, open Save Prompt dialog; if saved, remove from saved via AJAX.
		 */
		function onPromptHistoryStarClick(e) {

			e.preventDefault();
			e.stopPropagation();
			var $item = $(e.currentTarget).closest('.unlimitedai-plugin__prompt-history-item');
			var id = $item.attr('data-id');
			if (!id) return;
			var $star = $item.find('.unlimitedai-plugin__prompt-history-item-star');
			var isSaved = $star.hasClass('is-saved');
			if (isSaved) {
				if (typeof g_doublyAdmin === 'undefined' || !g_doublyAdmin.ajaxRequest) return;
				var strings = typeof g_ubaiPromptsStrings !== 'undefined' ? g_ubaiPromptsStrings : {};
				var labelSave = strings.promptHistoryStarSave || 'Save';
				$star.toggleClass('is-saved', false);
				$star.attr('aria-label', labelSave).attr('title', labelSave);
				$item.attr('data-is-saved', '0');

				g_doublyAdmin.setAjaxLoaderID(g_cellProcessingObj.g_ajaxLoaderProcessing);
				g_doublyAdmin.ajaxRequest('remove_prompt_from_saved', { prompt_id: id }, function (response) {
					if (response && response.success) {
						$item.remove();
						var visibleCount = $promptHistoryList.find('.unlimitedai-plugin__prompt-history-item').length;
						$promptHistoryCount.text(visibleCount);
						var totalSaved = typeof response.totalSaved === 'number' ? response.totalSaved : 0;
						if ($filterSaved.length && totalSaved >= 0) $filterSaved.find('.unlimitedai-plugin__prompt-history-filter-num[data-num="saved"]').text(totalSaved);
						refreshDropdownsFromResponse(response);
					} else {
						var labelRemove = (typeof g_ubaiPromptsStrings !== 'undefined' && g_ubaiPromptsStrings.promptHistoryStarRemoveFromSaved) ? g_ubaiPromptsStrings.promptHistoryStarRemoveFromSaved : 'Remove From Saved';
						$star.addClass('is-saved');
						$star.attr('aria-label', labelRemove).attr('title', labelRemove);
						$item.attr('data-is-saved', '1');
					}
				});
			} else {
				var title = $item.find('.unlimitedai-plugin__prompt-history-item-title').text();
				var text = $item.find('.unlimitedai-plugin__prompt-history-item-text').text();
				if (typeof text !== 'string') text = '';
				if (typeof title !== 'string' || title.trim() === '') title = text.length > 50 ? text.substring(0, 50).trim() + '…' : text;
				openSavePromptDialog(id, title, text);
			}
		}
		/**
		 * Edit saved prompt with sidebar drawer
		 */
		function onPromptHistoryEditClick(e) {

			e.preventDefault();
			e.stopPropagation();
			var $item = $(e.currentTarget).closest('.unlimitedai-plugin__prompt-history-item');
			var id = $item.attr('data-id');
			if (!id) return;
			var $star = $item.find('.unlimitedai-plugin__prompt-history-item-star');
			var isSaved = $star.hasClass('is-saved');

			var title = $item.find('.unlimitedai-plugin__prompt-history-item-title').text();
			var text = $item.find('.unlimitedai-plugin__prompt-history-item-text').text();
			if (typeof text !== 'string') text = '';
			if (typeof title !== 'string' || title.trim() === '') title = text.length > 50 ? text.substring(0, 50).trim() + '…' : text;
			openSavePromptDialog(id, title, text);
		}

		/**
		 * Copy the prompt history item text to the clipboard and show green check feedback for 1 second.
		 */
		function onPromptHistoryCopyClick(e) {

			e.preventDefault();
			e.stopPropagation();
			var $btn = $(e.currentTarget);
			var $item = $btn.closest('.unlimitedai-plugin__prompt-history-item');
			var text = $item.find('.unlimitedai-plugin__prompt-history-item-text').text();
			if (text) {
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text);
				} else {
					var $ta = $('<textarea>').val(text).appendTo('body').select();
					document.execCommand('copy');
					$ta.remove();
				}
				flashPromptHistoryCopyIcon($btn);
			}
		}

		/**
		 * Show green check on the copy button for 1 second, then revert to copy icon.
		 */
		function flashPromptHistoryCopyIcon($btn) {
			if (!$btn || !$btn.length) return;
			var $copyIcon = $btn.find('.unlimitedai-plugin__prompt-history-item-copy-icon');
			var $checkIcon = $btn.find('.unlimitedai-plugin__prompt-history-item-copy-check');
			if (!$copyIcon.length || !$checkIcon.length) return;
			var existingTimer = $btn.data('ubaiCopyRestoreTimer');
			if (existingTimer) clearTimeout(existingTimer);
			$copyIcon.hide();
			$checkIcon.show().css('display', '');
			$btn.addClass('ubai-copy-done');
			var t = setTimeout(function () {
				$checkIcon.hide();
				$copyIcon.show();
				$btn.removeClass('ubai-copy-done');
				$btn.removeData('ubaiCopyRestoreTimer');
			}, 1000);
			$btn.data('ubaiCopyRestoreTimer', t);
		}

		/**
		 * Put prompt history item text into the sidebar prompt input, close panel, then focus input.
		 */
		function onPromptHistoryRunClick(e) {

			e.preventDefault();
			e.stopPropagation();
			var $item = $(e.currentTarget).closest('.unlimitedai-plugin__prompt-history-item');
			var text = $item.find('.unlimitedai-plugin__prompt-history-item-text').text();

			var promptType = $item.data('prompt-type');
			if (!text || !setPromptInputValue) return;
			closePromptHistoryPanel();
			setTimeout(function () {
				if (promptType == 'data' || promptType == '') {
					setPromptInputValue(text, sidebarPromptSelector);
					focusPromptInput(sidebarPromptSelector);
					setSidebarMode('text');
				}
				if (promptType == 'pending_image') {
					setPromptInputValue(text, imagePromptSelector);
					focusPromptInput(imagePromptSelector);
					setSidebarMode('image');
				}

			}, 0);
		}

		/**
		 * On Saved tab: remove from saved (remove_prompt_from_saved) and remove item from list. On Recent tab: delete from history (delete_prompt_from_history) and remove item.
		 */
		function onPromptHistoryDeleteClick(e) {

			e.preventDefault();
			e.stopPropagation();
			var $item = $(e.currentTarget).closest('.unlimitedai-plugin__prompt-history-item');
			var id = $item.attr('data-id');
			if (!id) return;
			var wasSaved = $item.attr('data-is-saved') === '1';
			var isSavedTab = promptHistoryFilter === 'saved';

			if (isSavedTab) {
				$item.remove();
				var visibleCount = $promptHistoryList.find('.unlimitedai-plugin__prompt-history-item').length;
				$promptHistoryCount.text(visibleCount);
				if (typeof g_doublyAdmin !== 'undefined' && g_doublyAdmin.ajaxRequest) {

					g_doublyAdmin.setAjaxLoaderID(g_cellProcessingObj.g_ajaxLoaderProcessing);
					g_doublyAdmin.ajaxRequest('remove_prompt_from_saved', { prompt_id: id }, function (response) {
						if (response && response.success) {
							var totalSaved = typeof response.totalSaved === 'number' ? response.totalSaved : 0;
							if ($filterSaved.length && totalSaved >= 0) $filterSaved.find('.unlimitedai-plugin__prompt-history-filter-num[data-num="saved"]').text(totalSaved);
						} else {
							loadPromptHistoryList(undefined, false);
						}
					});
				}
				return;
			}

			$item.remove();
			var $items = $promptHistoryList.find('.unlimitedai-plugin__prompt-history-item');
			var visibleCount = $items.length;
			$promptHistoryCount.text(visibleCount);
			var $allNumEl = $filterAll.find('.unlimitedai-plugin__prompt-history-filter-num[data-num="all"]');
			var allNum = parseInt($allNumEl.text(), 10) || 1;
			$allNumEl.text(Math.max(0, allNum - 1));
			if (wasSaved && $filterSaved.length) {
				var $savedNumEl = $filterSaved.find('.unlimitedai-plugin__prompt-history-filter-num[data-num="saved"]');
				var n = parseInt($savedNumEl.text(), 10) || 1;
				$savedNumEl.text(Math.max(0, n - 1));
			}
			if (typeof g_doublyAdmin !== 'undefined' && g_doublyAdmin.ajaxRequest) {
				g_doublyAdmin.setAjaxLoaderID(g_cellProcessingObj.g_ajaxLoaderProcessing);
				g_doublyAdmin.ajaxRequest('delete_prompt_from_history', { prompt_id: id }, function (response) {
					if (response && response.success) {
						refreshDropdownsFromResponse(response);
					} else {
						loadPromptHistoryList(undefined, false);
					}
				});
			}
		}

		/**
		 * Clear all prompt history via AJAX after confirm; then refresh the list.
		 */
		function onPromptHistoryClearAllClick() {

			if (typeof window.confirm !== 'undefined' && !window.confirm('Clear all prompt history?')) return;
			if (typeof g_doublyAdmin === 'undefined' || !g_doublyAdmin.ajaxRequest) return;

			g_doublyAdmin.setAjaxLoaderID(g_cellProcessingObj.g_ajaxLoaderProcessing);
			g_doublyAdmin.ajaxRequest('clear_all_prompt_history', {}, function (response) {
				if (response && response.success) {
					refreshDropdownsFromResponse(response);
					loadPromptHistoryList(undefined, false);
				}
			});
		}

		// ----- Prompt replace dialog (#ubai_prompt_replace_dialog) -----
		/**
		 * Current dialog target cell. Self-healing: when the table re-renders,
		 * the stored jQuery reference detaches from the DOM — in that case the
		 * cell is re-resolved by its stable "postId:columnIndex" identity so
		 * Replace/Apply always writes into the live cell, never a detached node.
		 */
		function getPromptReplaceDialogTargetCell() {

			if (!$prDialog.length) {
				return $();
			}
			var $cell = $prDialog.data('targetCell');
			if ($cell && $cell.length && $cell[0] && $cell[0].isConnected) {
				return $cell;
			}
			// stale reference — re-find by identity
			var postId = $prDialog.data('targetPostId');
			var columnIndex = $prDialog.data('targetColumnIndex');
			if (postId == null || columnIndex == null ||
				!prTableManipulator || typeof prTableManipulator.findCellByPendingPromptResultKey !== 'function') {
				return $cell && $cell.length ? $cell : $();
			}
			var $fresh = prTableManipulator.findCellByPendingPromptResultKey(String(postId) + ':' + String(columnIndex));
			if ($fresh && $fresh.length) {
				$prDialog.data('targetCell', $fresh);
				return $fresh;
			}
			return $cell && $cell.length ? $cell : $();
		}

		function isPromptReplaceDialogImageMode() {

			return !!($prDialog.length && $prDialog.data('pendingImageRequestId'));
		}

		/**
		 * Build table snapshot from dialog target metadata (fallback: last apply payload).
		 */
		function getPromptReplaceDialogTableSnapshot() {

			if (!$prDialog.length) {
				return null;
			}
			var postId = $prDialog.data('targetPostId');
			var columnIndex = $prDialog.data('targetColumnIndex');
			var column = $prDialog.data('targetColumn');
			var $targetCell = getPromptReplaceDialogTargetCell();
			if ((postId == null || columnIndex == null) && $targetCell && $targetCell.length) {
				var $row = $targetCell.closest('tr');
				if (postId == null) {
					postId = $row.data('id') || $targetCell.data('row') || null;
				}
				if (columnIndex == null) {
					columnIndex = $targetCell.data('col') || null;
				}
			}
			if (postId != null && columnIndex != null) {
				return {
					isSelected: true,
					postId: postId,
					columnIndex: columnIndex,
					column: column || null
				};
			}
			return prLastApplyPromptPayload && prLastApplyPromptPayload.table ? prLastApplyPromptPayload.table : null;
		}

		/**
		 * Persist current dialog content on the target cell so it can be reopened from the cell icon.
		 */
		function savePromptReplaceDialogAsDiscardedIfNeeded() {

			if (!prTableManipulator || typeof prTableManipulator.setDiscardedPendingPromptResult !== 'function') {
				return;
			}
			var $targetCell = getPromptReplaceDialogTargetCell();
			if (!$targetCell || !$targetCell.length) {
				return;
			}
			var tableSnapshot = getPromptReplaceDialogTableSnapshot();

			if (isPromptReplaceDialogImageMode()) {
				var requestId = $prDialog.data('pendingImageRequestId');
				var previewUrl = $prImagePreview.find('.unlimitedai-plugin__prompt-replace-dialog__preview-img').attr('src') || '';
				if (!requestId) {
					return;
				}
				prTableManipulator.setDiscardedPendingPromptResult($targetCell, {
					type: 'image',
					requestId: requestId,
					previewUrl: previewUrl,
					postId: $prDialog.data('pendingImagePostId'),
					column: $prDialog.data('pendingImageColumn'),
					tableSnapshot: tableSnapshot
				});
				return;
			}

			var replacementText = $prDialog.data('replacementText');
			if (typeof replacementText !== 'string' || !replacementText.length) {
				return;
			}
			var insertText = replacementText;
			var replacementSaveValue = getPromptReplaceDialogSaveValue();
			if (typeof replacementSaveValue === 'string' || typeof replacementSaveValue === 'number') {
				insertText = String(replacementSaveValue);
			}
			prTableManipulator.setDiscardedPendingPromptResult($targetCell, {
				type: 'text',
				displayText: replacementText,
				insertText: insertText,
				blocks: getPromptReplaceDialogBlocks(),
				tableSnapshot: tableSnapshot
			});
		}

		/**
		 * @param {Object} [options]
		 * @param {boolean} [options.skipDiscardSave] Skip persisting content (Replace/Apply/Insert already applied).
		 * @param {boolean} [options.resolved] User pressed Apply/Replace/Discard/Insert (removes item from untouched pile).
		 * @param {boolean} [options.dismissedViaCloseButton] Text dialog X: save icon, drop from pending count, do not auto-open next.
		 * @param {boolean} [options.keepPendingCount] External close (e.g. cell selection): save icon, pending count unchanged.
		 */
		function hidePromptReplaceDialog(options) {

			options = options || {};
			if (!$prDialog.length) {
				return;
			}
			var wasVisible = $prDialog.is(':visible');
			if (wasVisible && !options.skipDiscardSave) {
				savePromptReplaceDialogAsDiscardedIfNeeded();
			}
			unbindPromptReplaceDialogDrag();
			unbindPromptReplaceDialogLayoutListeners();
			switchPromptReplaceDialogToTextMode();

			// tear down any active before/after compare slider
			resetPromptReplaceDialogCompare();

			prDialogUserDragged = false;
			$prDialog.css({ maxHeight: '' }).hide();
			$prDialog.removeData('pendingImageRequestId pendingImagePostId pendingImageColumn');
			if (wasVisible) {
				var closeMeta = {
					resolved: !!options.resolved,
					dismissedViaCloseButton: !!options.dismissedViaCloseButton,
					keepPendingCount: !!options.keepPendingCount
				};
				if (prTableManipulator && typeof prTableManipulator.getPendingPromptResultCellKey === 'function') {
					var $resolvedCell = getPromptReplaceDialogTargetCell();
					if ($resolvedCell && $resolvedCell.length) {
						closeMeta.cellKey = prTableManipulator.getPendingPromptResultCellKey($resolvedCell);
					}
				}
				$(document).trigger('ubai_prompt_replace_dialog_closed', [closeMeta]);
			}
		}

		function getPromptReplaceDialogBlocks() {
			if (!$prDialog.length) {
				return null;
			}

			var stored = $prDialog.data('replacementBlocks');
			if (stored && typeof stored === 'object') {
				return stored;
			}

			return null;
		}

		function getPromptReplaceDialogSaveValue() {
			if (!$prDialog.length) {
				return undefined;
			}

			var raw = $prDialog.attr('data-replacement-save-value');
			if (typeof raw === 'string' && raw !== '') {
				return raw;
			}

			var stored = $prDialog.data('replacementSaveValue');
			if (typeof stored === 'string' || typeof stored === 'number') {
				return String(stored);
			}
			if (stored && typeof stored === 'object') {
				try {
					return JSON.stringify(stored);
				} catch (e) {
					return undefined;
				}
			}

			return undefined;
		}

		/**
		 * Close text prompt result via X: save reopen icon, remove from pending count.
		 */
		function onPromptReplaceDialogCloseClick() {

			if (isPromptReplaceDialogImageMode()) {
				hidePromptReplaceDialog({ dismissedViaCloseButton: true });
				return;
			}
			hidePromptReplaceDialog({ dismissedViaCloseButton: true });
		}

		function switchPromptReplaceDialogToTextMode() {

			if (!$prDialog.length) {
				return;
			}
			$prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__text').show();
			if ($prImagePreview.length) {
				$prImagePreview.hide();
			}
			if ($prTextSection.length) {
				$prTextSection.show();
			}
			if ($prImageButtons.length) {
				$prImageButtons.hide();
			}
		}

		function unbindPromptReplaceDialogLayoutListeners() {

			if (!prLayoutBound) {
				return;
			}
			prLayoutBound = false;
			if (prResizeTimer) {
				clearTimeout(prResizeTimer);
				prResizeTimer = null;
			}
			if (prWindowAdjust) {
				$(window).off('resize.ubaiPrDlg orientationchange.ubaiPrDlg', prWindowAdjust);
				prWindowAdjust = null;
			}
			if (window.visualViewport && prWindowAdjustVv) {
				window.visualViewport.removeEventListener('resize', prWindowAdjustVv);
				window.visualViewport.removeEventListener('scroll', prWindowAdjustVv);
				prWindowAdjustVv = null;
			}
			if (prResizeObserver) {
				prResizeObserver.disconnect();
				prResizeObserver = null;
			}
		}

		function bindPromptReplaceDialogLayoutListeners() {

			if (prLayoutBound) {
				return;
			}
			prLayoutBound = true;
			prWindowAdjust = function () {
				schedulePromptReplaceDialogViewportAdjust();
			};
			$(window).on('resize.ubaiPrDlg orientationchange.ubaiPrDlg', prWindowAdjust);
			if (window.visualViewport && prWindowAdjust) {
				prWindowAdjustVv = prWindowAdjust;
				window.visualViewport.addEventListener('resize', prWindowAdjustVv);
				window.visualViewport.addEventListener('scroll', prWindowAdjustVv);
			}
			if (typeof ResizeObserver !== 'undefined' && $prDialog.length) {
				prResizeObserver = new ResizeObserver(function () {
					schedulePromptReplaceDialogViewportAdjust();
				});
				prResizeObserver.observe($prDialog[0]);
			}
		}

		function schedulePromptReplaceDialogViewportAdjust() {

			if (prResizeTimer) {
				clearTimeout(prResizeTimer);
			}
			prResizeTimer = setTimeout(function () {
				prResizeTimer = null;
				adjustPromptReplaceDialogToViewport();
			}, 60);
		}

		function getPromptReplaceViewportBounds() {

			var margin = 8;
			var vv = window.visualViewport;
			if (vv && typeof vv.width === 'number' && vv.width > 0 && vv.height > 0) {
				return {
					minX: vv.offsetLeft + margin,
					minY: vv.offsetTop + margin,
					maxX: vv.offsetLeft + vv.width - margin,
					maxY: vv.offsetTop + vv.height - margin
				};
			}
			return {
				minX: margin,
				minY: margin,
				maxX: window.innerWidth - margin,
				maxY: window.innerHeight - margin
			};
		}

		function clampPromptReplaceDialogToViewport() {

			if (!$prDialog.length || !$prDialog.is(':visible')) {
				return;
			}
			var b = getPromptReplaceViewportBounds();
			var rect = $prDialog[0].getBoundingClientRect();
			var left = rect.left;
			var top = rect.top;
			var w = rect.width;
			var h = rect.height;
			if (left < b.minX) {
				left = b.minX;
			}
			if (top < b.minY) {
				top = b.minY;
			}
			if (left + w > b.maxX) {
				left = b.maxX - w;
			}
			if (top + h > b.maxY) {
				top = b.maxY - h;
			}
			if (left < b.minX) {
				left = b.minX;
			}
			if (top < b.minY) {
				top = b.minY;
			}
			$prDialog.css({
				left: left + 'px',
				top: top + 'px',
				visibility: 'visible',
				position: 'fixed',
				right: 'auto'
			});
		}

		function adjustPromptReplaceDialogToViewport() {

			if (!$prDialog.length || !$prDialog.is(':visible')) {
				return;
			}
			if (prDialogUserDragged) {
				clampPromptReplaceDialogToViewport();
				return;
			}
			var $cell = getPromptReplaceDialogTargetCell();
			if (!$cell.length && prTableManipulator) {
				$cell = prTableManipulator.$table.find('td.' + prTableManipulator.g_isActiveCellNoIndex).first();
			}
			if (!$cell.length) {
				return;
			}
			positionPromptReplaceDialog($cell);
		}

		function unbindPromptReplaceDialogDrag() {

			prDialogDrag.active = false;
			$(document).off('mousemove.ubaiPrDlgDrag mouseup.ubaiPrDlgDrag');
		}

		function onPromptReplaceDialogDragMove(e) {

			if (!prDialogDrag.active) {
				return;
			}
			var left = prDialogDrag.origLeft + (e.clientX - prDialogDrag.origClientX);
			var top = prDialogDrag.origTop + (e.clientY - prDialogDrag.origClientY);
			$prDialog.css({ left: left + 'px', top: top + 'px', visibility: 'visible', position: 'fixed' });
			prDialogUserDragged = true;
		}

		function onPromptReplaceDialogDragEnd() {

			if (!prDialogDrag.active) {
				return;
			}
			prDialogDrag.active = false;
			$(document).off('mousemove.ubaiPrDlgDrag mouseup.ubaiPrDlgDrag');
			if (prDialogUserDragged) {
				clampPromptReplaceDialogToViewport();
			}
		}

		function onPromptReplaceDialogDragStart(e) {

			if (e.button !== 0) {
				return;
			}
			if (!$prDialog.length || !$prDialog.is(':visible')) {
				return;
			}
			if ($(e.target).closest('button, a, input, textarea, select').length) {
				return;
			}
			var rect = $prDialog[0].getBoundingClientRect();
			prDialogDrag.active = true;
			prDialogDrag.origClientX = e.clientX;
			prDialogDrag.origClientY = e.clientY;
			prDialogDrag.origLeft = rect.left;
			prDialogDrag.origTop = rect.top;
			e.preventDefault();
			$(document).on('mousemove.ubaiPrDlgDrag', onPromptReplaceDialogDragMove);
			$(document).on('mouseup.ubaiPrDlgDrag', onPromptReplaceDialogDragEnd);
		}

		function positionPromptReplaceDialog($cell) {
			if (!$cell || !$cell.length || !$prDialog.length) {
				return;
			}

			var isRTL = $('html').attr('dir') === 'rtl';
			var bounds = getPromptReplaceViewportBounds();
			var cellRect = $cell[0].getBoundingClientRect();
			var gap = 4;

			// Find the scrolling parent container
			var $container = $('.unlimitedai-plugin__table');
			if (!$container.length) return;
			var containerRect = $container[0].getBoundingClientRect();

			// Measure off-screen using absolute positioning
			$prDialog.css({
				display: 'block',
				visibility: 'hidden',
				position: 'absolute',
				left: -99999,
				top: 0,
				right: 'auto'
			});

			var w = $prDialog.outerWidth();
			var h = $prDialog.outerHeight();

			// 1. Calculate positions exactly in VIEWPORT space (just like your working fixed version)
			var targetLeft = cellRect.left;
			var targetTop = cellRect.bottom + gap;

			if (isRTL) {
				targetLeft = cellRect.right - w;
			}

			// Clamping using the exact viewport bounds
			if (targetLeft + w > bounds.maxX) {
				targetLeft = bounds.maxX - w;
			}
			if (targetLeft < bounds.minX) {
				targetLeft = bounds.minX;
			}

			// Vertical Flipping/Clamping
			if (targetTop + h > bounds.maxY) {
				var aboveTop = cellRect.top - h - gap;
				if (aboveTop >= bounds.minY) {
					targetTop = aboveTop;
				} else {
					targetTop = Math.max(bounds.minY, Math.min(targetTop, bounds.maxY - h));
				}
			}

			// 2. CONVERT Viewport coordinates to Absolute Coordinates inside the container
			// Formula: ViewportPosition - ContainerViewportPosition + ContainerInternalScroll
			var finalLeft = targetLeft - containerRect.left + $container.scrollLeft();
			var finalTop = targetTop - containerRect.top + $container.scrollTop();

			// Apply absolute styling relative to the container
			$prDialog.css({
				left: finalLeft + 'px',
				top: finalTop + 'px',
				visibility: 'visible',
				position: 'absolute',
				right: 'auto'
			});

			prDialogUserDragged = false;
		}

		/**
		 * Persist current dialog target cell and basic identity metadata.
		 */
		function setPromptReplaceDialogTarget($cell) {

			if (!$prDialog.length || !$cell || !$cell.length || !prTableManipulator) {
				return;
			}
			var $row = $cell.closest('tr');
			var postId = $row.data('id') || $cell.data('row') || null;
			var columnIndex = $cell.data('col') || null;
			var $container = $cell.find(prTableManipulator.g_editorContainer).first();
			var column = $container.length ? $container.data('column') : null;
			var originalText = prTableManipulator.getPromptDialogTargetCellValue($cell);
			$prDialog
				.data('targetCell', $cell)
				.data('targetPostId', postId)
				.data('targetColumn', column)
				.data('targetColumnIndex', columnIndex)
				.data('originalText', originalText);
			updatePromptReplaceDialogTargetMeta($cell, column, columnIndex, postId);
		}

		/**
		 * Trim and shorten text for compact dialog meta display.
		 */
		function shortenDialogMetaText(text, maxLen) {
			var s = typeof text === 'string' ? text.trim() : '';
			if (!s) return '';
			var m = Number(maxLen || 80);
			if (s.length <= m) return s;
			return s.substring(0, m).trim() + '...';
		}

		/**
		 * Limit dialog meta text to a maximum number of words.
		 */
		function limitDialogMetaWords(text, maxWords) {
			var s = typeof text === 'string' ? text.trim() : '';
			if (!s) {
				return '';
			}
			var words = s.split(/\s+/).filter(function (w) { return w !== ''; });
			var limit = Number(maxWords || 3);
			if (words.length <= limit) {
				return words.join(' ');
			}
			return words.slice(0, limit).join(' ') + '...';
		}

		/**
		 * Render "post title - column" context under the dialog title.
		 */
		function updatePromptReplaceDialogTargetMeta($cell, column, columnIndex, postId) {
			if (!$prTargetMeta || !$prTargetMeta.length) {
				return;
			}

			var displayInfo = { label: '', sub: '' };
			if (prTableManipulator && typeof prTableManipulator.getPromptCellDisplayInfo === 'function') {
				displayInfo = prTableManipulator.getPromptCellDisplayInfo({
					column: column,
					columnIndex: columnIndex,
					postId: postId,
					isSelected: true
				}, $cell);
			}

			var columnName = shortenDialogMetaText(displayInfo.label || '', 40);
			var postTitle = shortenDialogMetaText(displayInfo.sub || '', 70);
			if (!postTitle && postId != null) {
				postTitle = 'Post #' + postId;
			}

			if (!postTitle && !columnName) {
				$prTargetMeta.text('').hide();
				return;
			}
			postTitle = limitDialogMetaWords(postTitle, 3);
			var safeColumn = escapeHtml(columnName || '');
			var safePostTitle = escapeHtml(postTitle || '');
			var metaText = 'for: ';
			if (safeColumn) {
				metaText += '<strong>' + safeColumn + '</strong>';
			}
			if (safePostTitle) {
				metaText += (safeColumn ? ', ' : '') + safePostTitle;
			}
			$prTargetMeta.html(metaText).show();
		}

		/**
		 * Show check icon briefly after copy action.
		 */
		function flashPromptReplaceCopyIcon() {

			if (!$prCopyBtn || !$prCopyBtn.length) {
				return;
			}
			var $btn = $prCopyBtn;
			var $copyIcon = $btn.find('.unlimitedai-plugin__prompt-replace-dialog__icon-copy');
			var $checkIcon = $btn.find('.unlimitedai-plugin__prompt-replace-dialog__icon-check');
			if (!$copyIcon.length || !$checkIcon.length) {
				return;
			}
			$copyIcon.hide();
			$checkIcon.show();
			var existingTimer = $btn.data('restoreIconTimer');
			if (existingTimer) {
				clearTimeout(existingTimer);
			}
			var restoreTimer = setTimeout(function () {
				$checkIcon.hide();
				$copyIcon.show();
				$btn.removeData('restoreIconTimer');
			}, 2000);
			$btn.data('restoreIconTimer', restoreTimer);
		}

		/**
		 * Copy the current dialog response text to clipboard.
		 */
		function onPromptReplaceDialogCopyClick() {

			if (!$prDialog.length) {
				return;
			}
			var $textContainer = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__text');
			if (!$textContainer.length) {
				return;
			}
			var textToCopy = $textContainer.text() || '';
			if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(textToCopy);
				flashPromptReplaceCopyIcon();
				return;
			}
			var $temp = $('<textarea readonly></textarea>');
			$temp.val(textToCopy);
			$('body').append($temp);
			$temp[0].select();
			document.execCommand('copy');
			$temp.remove();
			flashPromptReplaceCopyIcon();
		}

		/**
		 * Re-run latest apply_prompt payload for current dialog target.
		 */
		function onPromptReplaceDialogRegenerateClick() {

			if (!$prDialog.length) {
				return;
			}
			var payload = prLastApplyPromptPayload;
			if (!payload || !payload.prompt || !payload.table) {
				return;
			}
			$(document).trigger('ubai_regenerate_prompt', [payload]);
		}

		/**
		 * Insert generated text below existing target cell content.
		 */
		function onPromptReplaceDialogInsertBelowClick() {

			if (!$prDialog.length || !prTableManipulator) {
				return;
			}
			var $targetCell = getPromptReplaceDialogTargetCell();
			if (!$targetCell || !$targetCell.length) {
				return;
			}
			var replacementText = $prDialog.data('replacementText');
			if (typeof replacementText !== 'string') {
				replacementText = '';
			}
			var currentValue = prTableManipulator.getPromptDialogTargetCellValue($targetCell);
			var combinedValue = currentValue ? currentValue + '\n' + replacementText : replacementText;
			prTableManipulator.applyPromptReplacementToCell($targetCell, combinedValue);
			hidePromptReplaceDialog({ skipDiscardSave: true, resolved: true });
		}

		/**
		 * Replace target cell content with generated text.
		 */
		function onPromptReplaceDialogReplaceClick() {

			if (!$prDialog.length || !prTableManipulator) {
				return;
			}
			var $targetCell = getPromptReplaceDialogTargetCell();
			if (!$targetCell || !$targetCell.length) {
				return;
			}
			var replacementText = $prDialog.data('replacementText');
			if (typeof replacementText !== 'string') {
				replacementText = '';
			}
			var replacementSaveValue = getPromptReplaceDialogSaveValue();
			if (typeof replacementSaveValue !== 'string' && typeof replacementSaveValue !== 'number') {
				replacementSaveValue = undefined;
			}
			var replacementBlocks = getPromptReplaceDialogBlocks();
			var replaceOptions = {};
			if (replacementBlocks) {
				replaceOptions.blocks = replacementBlocks;
			}
			if (typeof prTableManipulator.clearDiscardedPendingPromptResult === 'function') {
				prTableManipulator.clearDiscardedPendingPromptResult($targetCell);
			}
			prTableManipulator.applyPromptReplacementToCell($targetCell, replacementText, replacementSaveValue, replaceOptions);
			hidePromptReplaceDialog({ skipDiscardSave: true, resolved: true });
		}

		/**
		 * Build syncImageCellAttachment options from apply_pending_image / pending_image metadata.
		 */
		function imagePreviewOptionsFromResponse(data, fallbackUrl) {
			data = data || {};
			return {
				fullUrl: data.full_url || data.thumbnail_url_full || data.thumbnail_url || fallbackUrl || '',
				fileSize: parseInt(data.file_size, 10) || 0,
				fileType: data.file_type || '',
				width: parseInt(data.width, 10) || 0,
				height: parseInt(data.height, 10) || 0,
				filename: data.filename || ''
			};
		}

		/**
		 * Read pending-image preview metadata stored on the replace dialog.
		 */
		function getPendingImagePreviewMetaFromDialog() {
			return {
				file_size: parseInt($prDialog.data('pendingImageFileSize'), 10) || 0,
				file_type: $prDialog.data('pendingImageFileType') || '',
				width: parseInt($prDialog.data('pendingImageWidth'), 10) || 0,
				height: parseInt($prDialog.data('pendingImageHeight'), 10) || 0
			};
		}

		/**
		 * Apply pending generated image to target cell and persist it.
		 */
		function onPromptReplaceDialogApplyImageClick() {

			if (!$prDialog.length || !prTableManipulator) {
				return;
			}
			var requestId = $prDialog.data('pendingImageRequestId');
			var postId = $prDialog.data('pendingImagePostId');
			var column = $prDialog.data('pendingImageColumn');


			var $targetCell = getPromptReplaceDialogTargetCell();


			if (!requestId || !$targetCell || !$targetCell.length) {
				return;
			}
			if (typeof prTableManipulator.clearCellPromptActivityIndicators === 'function') {
				var targetPostId = $targetCell.data('row');
				var targetColIdx = $targetCell.data('col');
				if (targetPostId != null && targetColIdx != null) {
					prTableManipulator.clearCellPromptActivityIndicators({
						isSelected: true,
						postId: targetPostId,
						columnIndex: targetColIdx
					});
				}
			}
			if (typeof prTableManipulator.clearDiscardedPendingPromptResult === 'function') {
				prTableManipulator.clearDiscardedPendingPromptResult($targetCell);
			}
			var previewUrl = $prImagePreview.find('.unlimitedai-plugin__prompt-replace-dialog__preview-img').attr('src') || '';
			if (!previewUrl) {
				return;
			}
			var pendingMeta = getPendingImagePreviewMetaFromDialog();
			switchPromptReplaceDialogToTextMode();
			hidePromptReplaceDialog({ skipDiscardSave: true, resolved: true });
			var $container = $targetCell.find(prTableManipulator.g_editorContainer).first();

			// find column type (may be missing for e.g. the featured image pseudo-column)
			var rowData = (typeof columnsListGlobal !== 'undefined' && Array.isArray(columnsListGlobal))
				? columnsListGlobal.find(item => item.name === column)
				: null;
			var columnType = rowData && rowData.type ? rowData.type : '';
			var isGalleryColumn = (columnType == 'acf_woo_gallery' || columnType == 'acf_gallery');

			// galleries: skip instant preview swap, image is appended after the server confirms
			if (!isGalleryColumn) {
				prTableManipulator.syncImageCellAttachment($container, '', previewUrl, imagePreviewOptionsFromResponse(pendingMeta, previewUrl));
			}


			var tm = prTableManipulator;




			tm.g_doublyAdmin.ajaxRequest('apply_pending_image', {
				request_id: requestId,
				post_id: postId,
				column_type: columnType,
				column: column || 'post_image'
			}, function (response) {
				if (response && response.success !== false && response.data) {
					var imageData = response.data.data || response.data;
					var previewOptions = imagePreviewOptionsFromResponse(imageData);

					// if gallery - append to gallery
					if (isGalleryColumn) {
						var attachmentId = imageData.attachment_id;
						var thumbnailUrlMedium = imageData.thumbnail_url;
						var thumbnailUrlFull = imageData.thumbnail_url_full;
						onPromptReplaceDialogApplyImageToGallery($targetCell, attachmentId, thumbnailUrlFull, thumbnailUrlMedium, postId, column, previewOptions);

					} else {
						var attachmentId = imageData.attachment_id;
						var thumbnailUrl = imageData.thumbnail_url;
						if (attachmentId && thumbnailUrl) {
							var $cellContainer = $targetCell.find(tm.g_editorContainer).first();
							tm.syncImageCellAttachment($cellContainer, attachmentId, thumbnailUrl, previewOptions);
							tm.onCellContentSave($cellContainer);
						}
					}


				}
			});
		}

		/**
		 * append image to gallery
		 */
		function onPromptReplaceDialogApplyImageToGallery($targetCell, attachmentId, thumbnailUrl, thumbnailPreviewUrl, post_id, column, previewOptions) {

			previewOptions = previewOptions || {};
			var $single_image_container = jQuery('<div>', {
				class: 'single_image_container  sp_hover_preview',
				'data-id': attachmentId,
				'data-img': thumbnailPreviewUrl,
				'data-full': thumbnailUrl,
				css: {
					backgroundImage: 'url(' + thumbnailPreviewUrl + ')',
				}
			});
			if (previewOptions.fileSize > 0) {
				$single_image_container.attr('data-file-size', previewOptions.fileSize);
			}
			if (previewOptions.fileType) {
				$single_image_container.attr('data-file-type', previewOptions.fileType);
			}
			if (previewOptions.width > 0 && previewOptions.height > 0) {
				$single_image_container.attr('data-image-width', previewOptions.width);
				$single_image_container.attr('data-image-height', previewOptions.height);
			}
			var $container = $targetCell.find('.editor_container').first();

			if ($container.data('value') != '') {
				var current_ids = $container.data('value').split(',');
			} else {
				var current_ids = [];
			}


			var images_counter = current_ids.length;

			if (images_counter < 3) {
				$('.gallery_images_container .single_image_counter', $container).before($single_image_container);
			}

			images_counter++;

			var diff = images_counter - 3;
			if (images_counter > 3) {
				var $extra_container = jQuery('<div>', {
					class: 'single_image_container',
					'data-id': 0,
					css: {
						backgroundImage: 'url()',
					},
					text: '+' + diff
				});
				$('.gallery_images_container .single_image_container[data-id="0"]', $container).replaceWith('');
				$('.gallery_images_container .single_image_counter', $container).before($extra_container);
			}


			//$('.gallery_images_container .single_image_counter', $container ).before( $single_image_container );
			jQuery(document).trigger('sp:image-preview:invalidate', [$single_image_container]);

			if (images_counter > 1 || images_counter == 0) {
				var images_text = sheetspilot.editor.images;
			} else {
				var images_text = sheetspilot.editor.image;
			}

			$('.gallery_images_container .single_image_counter', $container).html(images_counter + ' ' + images_text);

			current_ids.push(parseInt(attachmentId));
			$container.data('value', current_ids.join(','));

			g_cellProcessingObj.onCellContentSave($('.gallery_images_container', $container));

			g_cellProcessingObj.hideDeleteForEmptyGalleries();
		}

		/**
		 * Soft-dismiss pending generated image: keep server transient, show reopen star on cell.
		 */
		function onPromptReplaceDialogDiscardImageClick() {

			if (!$prDialog.length) {
				return;
			}
			hidePromptReplaceDialog({ resolved: true });
		}

		/**
		 * Build the before/after drag-compare slider inside the image preview:
		 * prepends the original (pre-edit) image as the "before" layer in front
		 * of the untouched .preview-img ("after" layer), then hands the pair to
		 * SheetsPilotBeforeAfter (assets/js/before_after_image.js).
		 */
		function buildPromptReplaceDialogCompareView() {

			if (!$prImagePreview.length || typeof window.SheetsPilotBeforeAfter !== 'function') {
				return;
			}

			var $targetCell = getPromptReplaceDialogTargetCell();
			var afterUrl = $prImagePreview.find('.unlimitedai-plugin__prompt-replace-dialog__preview-img').attr('src') || '';
			var beforeUrl = $targetCell && $targetCell.length ? ($targetCell.find('.editor_container img').attr('src') || '') : '';

			if (!beforeUrl || !afterUrl || beforeUrl === afterUrl) {
				return;
			}

			resetPromptReplaceDialogCompare();

			$prImagePreview.prepend($('<img/>', { src: beforeUrl, alt: '' }));
			$prImagePreview.addClass('cocoen');

			prBeforeAfterInstance = new window.SheetsPilotBeforeAfter($prImagePreview.get(0), {
				direction: 'horizontal',
				trigger: 'click',
				initialPosition: 50
			});

			if (!prBeforeAfterInstance.beforeElement) {
				// build failed (e.g. markup not as expected) - bail out cleanly
				resetPromptReplaceDialogCompare();
				return;
			}

			if ($prCompareImageBtn && $prCompareImageBtn.length) {
				if (!prCompareBtnDefaultLabel) {
					prCompareBtnDefaultLabel = $prCompareImageBtn.text();
					prCompareBtnDefaultTitle = $prCompareImageBtn.attr('title') || '';
				}
				var exitLabel = (typeof sheetspilot !== 'undefined' && sheetspilot.editor && sheetspilot.editor.exitCompareImage)
					? sheetspilot.editor.exitCompareImage
					: 'Exit Compare';
				$prCompareImageBtn.addClass('is-active').text(exitLabel).attr('title', exitLabel);
			}
		}

		/**
		 * Tear down the before/after compare slider (if active) and restore the
		 * single-image preview. Safe to call unconditionally.
		 */
		function resetPromptReplaceDialogCompare() {

			if (prBeforeAfterInstance && typeof prBeforeAfterInstance.destroy === 'function') {
				prBeforeAfterInstance.destroy();
			}
			prBeforeAfterInstance = null;

			if ($prImagePreview && $prImagePreview.length) {
				$prImagePreview.removeClass('cocoen');
				$prImagePreview.children('div').remove();
				$prImagePreview.children('.cocoen-drag').remove();
			}

			if ($prCompareImageBtn && $prCompareImageBtn.length && prCompareBtnDefaultLabel) {
				$prCompareImageBtn.removeClass('is-active').text(prCompareBtnDefaultLabel).attr('title', prCompareBtnDefaultTitle);
			}
		}

		/**
		 * Compare button click: toggle the before/after drag-compare slider.
		 */
		function onPromptReplaceDialogCompareImageClick() {

			if (!$prDialog.length) {
				return;
			}

			if (prBeforeAfterInstance) {
				resetPromptReplaceDialogCompare();
			} else {
				buildPromptReplaceDialogCompareView();
			}
		}

		/**
		 * Cache prompt-replace dialog refs and bind dialog action handlers.
		 */
		function initPromptReplaceDialog(tableManipulator) {

			prTableManipulator = tableManipulator;
			$prDialog = $('#ubai_prompt_replace_dialog');
			$prCloseBtn = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__close');
			$prReplaceBtn = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__btn--replace');
			$prInsertBtn = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__btn--insert');
			$prRegenerateBtn = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__icon-btn--regenerate');
			$prCopyBtn = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__icon-btn--copy');
			$prImagePreview = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__image-preview');
			$prTextSection = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__actions--text, .unlimitedai-plugin__prompt-replace-dialog__buttons--text');
			$prImageButtons = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__buttons--image');
			$prApplyImageBtn = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__btn--apply-image');
			$prDiscardImageBtn = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__btn--discard-image');
			$prCompareImageBtn = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__btn--compare-image');
			$prQueueCounter = $prDialog.find('.ubai-prompt-replace-dialog__queue-counter, .unlimitedai-plugin__prompt-replace-dialog__queue-counter').first();
			$prTargetMeta = $prDialog.find('.ubai-prompt-replace-dialog__target-meta').first();
			if (!$prTargetMeta.length) {
				var $header = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__header').first();
				if ($header.length) {
					$prTargetMeta = $('<div class="ubai-prompt-replace-dialog__target-meta" style="display:none;"></div>');
					$header.append($prTargetMeta);
				}
			}
			if (!$prQueueCounter.length) {
				var $footer = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__footer').first();
				if ($footer.length) {
					$prQueueCounter = $('<button type="button" class="ubai-prompt-replace-dialog__queue-counter unlimitedai-plugin__prompt-replace-dialog__queue-counter" style="display:none;" aria-live="polite"></button>');
					$footer.append($prQueueCounter);
				}
			}
			if ($prQueueCounter.length) {
				var queueNextLabel = (typeof sheetspilot !== 'undefined' && sheetspilot.editor && sheetspilot.editor.nextPendingPromptResult)
					? sheetspilot.editor.nextPendingPromptResult
					: 'Next pending prompt result';
				$prQueueCounter.attr('title', queueNextLabel).attr('aria-label', queueNextLabel);
				$prQueueCounter.off('click.ubaiPrDlgQueue').on('click.ubaiPrDlgQueue', function (e) {
					e.preventDefault();
					e.stopPropagation();
					$(document).trigger('ubai_prompt_replace_dialog_queue_next');
				});
			}
			if ($prCloseBtn.length) {
				$prCloseBtn.off('click.ubaiPrDlg').on('click.ubaiPrDlg', onPromptReplaceDialogCloseClick);
			}
			if ($prReplaceBtn.length) {
				$prReplaceBtn.off('click.ubaiPrDlg').on('click.ubaiPrDlg', onPromptReplaceDialogReplaceClick);
			}
			if ($prInsertBtn.length) {
				$prInsertBtn.off('click.ubaiPrDlg').on('click.ubaiPrDlg', onPromptReplaceDialogInsertBelowClick);
			}
			if ($prRegenerateBtn.length) {
				$prRegenerateBtn.off('click.ubaiPrDlg').on('click.ubaiPrDlg', onPromptReplaceDialogRegenerateClick);
			}
			if ($prCopyBtn.length) {
				$prCopyBtn.off('click.ubaiPrDlg').on('click.ubaiPrDlg', onPromptReplaceDialogCopyClick);
			}
			if ($prApplyImageBtn.length) {
				$prApplyImageBtn.off('click.ubaiPrDlg').on('click.ubaiPrDlg', onPromptReplaceDialogApplyImageClick);
			}
			if ($prDiscardImageBtn.length) {
				$prDiscardImageBtn.off('click.ubaiPrDlg').on('click.ubaiPrDlg', onPromptReplaceDialogDiscardImageClick);
			}
			if ($prCompareImageBtn.length) {
				$prCompareImageBtn.off('click.ubaiPrDlg').on('click.ubaiPrDlg', onPromptReplaceDialogCompareImageClick);
			}
			$prHeader = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__header');
			if ($prHeader.length) {
				$prHeader.off('mousedown.ubaiPrDlgDrag').on('mousedown.ubaiPrDlgDrag', onPromptReplaceDialogDragStart);
			}
		}

		/**
		 * Show and position prompt-replace dialog for the active table cell.
		 */
		function showPromptReplaceDialogForSelection() {

			if (!$prDialog.length || !prTableManipulator) {
				return;
			}
			var $activeCell = prTableManipulator.$table.find('td.' + prTableManipulator.g_isActiveCellNoIndex).first();
			if (!$activeCell.length) {
				hidePromptReplaceDialog();
				return;
			}
			showPromptReplaceDialogForCell($activeCell);
		}

		/**
		 * Show and position prompt-replace dialog for a specific table cell.
		 * @param {jQuery} $cell Target cell.
		 * @param {Object} [options]
		 * @param {boolean} [options.reopenDiscarded] Keep discarded-prompt star when reopening from cell icon.
		 */
		function showPromptReplaceDialogForCell($cell, options) {

			options = options || {};
			if (!$prDialog.length || !prTableManipulator || !$cell || !$cell.length) {
				return;
			}
			unbindPromptReplaceDialogDrag();
			prDialogUserDragged = false;
			setPromptReplaceDialogTarget($cell);
			// Make sure the target cell is on screen before anchoring the dialog to it.
			if (typeof prTableManipulator.scrollCellIntoView === 'function') {
				prTableManipulator.scrollCellIntoView($cell);
			} else if ($cell[0] && $cell[0].isConnected && typeof $cell[0].scrollIntoView === 'function') {
				try { $cell[0].scrollIntoView({ block: 'nearest', inline: 'nearest' }); } catch (e) { /* noop */ }
			}
			positionPromptReplaceDialog($cell);
			$prDialog.show();
			// After the dialog is placed, nudge scroll again so sticky chrome / the dialog
			// itself do not cover the target cell.
			if (typeof prTableManipulator.scrollCellIntoView === 'function') {
				requestAnimationFrame(function () {
					if (!$prDialog.is(':visible') || !$cell[0] || !$cell[0].isConnected) {
						return;
					}
					var cellRect = $cell[0].getBoundingClientRect();
					var dialogRect = $prDialog[0].getBoundingClientRect();
					var $container = $('.unlimitedai-plugin__table');
					if (!$container.length) {
						prTableManipulator.scrollCellIntoView($cell);
						return;
					}
					var containerRect = $container[0].getBoundingClientRect();
					var scrollOpts = {};
					var edgeGap = 6;
					if (dialogRect.top >= cellRect.bottom - 2) {
						var bottomOverflow = dialogRect.bottom - containerRect.bottom;
						if (bottomOverflow > 0) {
							scrollOpts.extraBottom = bottomOverflow + edgeGap;
						}
					} else if (dialogRect.bottom > cellRect.top + 2 && dialogRect.top < cellRect.top) {
						scrollOpts.extraTop = Math.max(0, dialogRect.bottom - cellRect.top + edgeGap);
					}
					prTableManipulator.scrollCellIntoView($cell, scrollOpts);
				});
			}
			bindPromptReplaceDialogLayoutListeners();
			$(document).trigger('ubai_prompt_replace_dialog_opened');
		}

		/**
		 * Convert display text to safe prompt dialog HTML.
		 */
		function getPromptReplaceDialogDisplayHtml(displayText) {

			var allowedTags = {
				BR: true,
				B: true,
				STRONG: true,
				I: true,
				EM: true,
				U: true
			};
			var $output = $('<div></div>');
			var nodes = $.parseHTML(String(displayText || ''), document, false);

			/**
			 * Append one sanitized node.
			 */
			function appendSanitizedNode(node, $target) {

				if (node.nodeType === 3) {
					$target.append(document.createTextNode(node.nodeValue));
					return;
				}

				if (node.nodeType !== 1) {
					return;
				}

				var tagName = node.nodeName.toUpperCase();
				if (!allowedTags[tagName]) {
					$target.append(document.createTextNode($(node).text()));
					return;
				}

				var cleanNode = document.createElement(tagName.toLowerCase());
				if (tagName !== 'BR') {
					$.each(node.childNodes, function () {
						appendSanitizedNode(this, $(cleanNode));
					});
				}

				$target.append(cleanNode);
			}

			if (!nodes || !nodes.length) {
				$output.text(String(displayText || ''));
				return $output.html();
			}

			$.each(nodes, function () {
				appendSanitizedNode(this, $output);
			});

			var html = $output.html();
			return html;
		}

		/**
		 * Set dialog text payload (display, insert value, optional Elementor blocks).
		 */
		function setPromptReplaceDialogText(text, rawTextOptional, blocksOptional) {

			if (!$prDialog.length) {
				return;
			}
			switchPromptReplaceDialogToTextMode();
			var $textContainer = $prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__text');
			if (!$textContainer.length) {
				return;
			}
			var displayText = '';
			var raw = '';
			var blocks = blocksOptional || null;
			if (text && typeof text === 'object') {
				displayText = typeof text.show === 'string' ? text.show : '';
				raw = typeof text.insert === 'string' ? text.insert : displayText;
				if (text.blocks && typeof text.blocks === 'object') {
					blocks = text.blocks;
				}
			} else {
				displayText = typeof text === 'string' ? text : '';
				raw = typeof rawTextOptional === 'string' ? rawTextOptional : displayText;
				if (!blocks && rawTextOptional && typeof rawTextOptional === 'object' && rawTextOptional.blocks) {
					blocks = rawTextOptional.blocks;
				}
			}
			$textContainer.html(getPromptReplaceDialogDisplayHtml(displayText));
			$prDialog.data('replacementText', displayText);
			$prDialog.attr('data-replacement-save-value', raw);
			$prDialog.removeData('replacementSaveValue');
			if (blocks) {
				$prDialog.data('replacementBlocks', blocks);
			} else {
				$prDialog.removeData('replacementBlocks');
			}
		}

		/**
		 * Switch dialog into image-preview mode for pending image apply/discard.
		 */
		function setPromptReplaceDialogImagePreview(requestId, previewUrl, postId, column, previewMeta) {

			if (!$prDialog.length || !$prImagePreview.length) {
				return;
			}
			previewMeta = previewMeta && typeof previewMeta === 'object' ? previewMeta : {};
			resetPromptReplaceDialogCompare();
			$prDialog.find('.unlimitedai-plugin__prompt-replace-dialog__text').hide();
			var $previewImg = $prImagePreview.show().find('.unlimitedai-plugin__prompt-replace-dialog__preview-img');
			// Drop the previous request's image immediately: a slow or failed load of the
			// new preview must never leave another cell's image visible in this dialog.
			if (($previewImg.attr('src') || '') !== (previewUrl || '')) {
				$previewImg.removeAttr('src');
			}
			$previewImg.off('load.ubaiPrDlg error.ubaiPrDlg').on('load.ubaiPrDlg', function () {
				$prImagePreview.removeClass('ubai-preview-loading');
				if (!$prDialog.is(':visible')) {
					return;
				}
				if (prDialogUserDragged) {
					clampPromptReplaceDialogToViewport();
					return;
				}
				var $t = getPromptReplaceDialogTargetCell();
				if ($t.length) {
					positionPromptReplaceDialog($t);
				}
			}).on('error.ubaiPrDlg', function () {
				$prImagePreview.removeClass('ubai-preview-loading');
			});
			$prImagePreview.addClass('ubai-preview-loading');
			$previewImg.attr('src', previewUrl || '');
			if ($prTextSection.length) {
				$prTextSection.hide();
			}
			if ($prImageButtons.length) {
				$prImageButtons.show();
			}
			$prDialog
				.data('pendingImageRequestId', requestId)
				.data('pendingImagePostId', postId)
				.data('pendingImageColumn', column)
				.data('pendingImageFileSize', parseInt(previewMeta.file_size, 10) || 0)
				.data('pendingImageFileType', previewMeta.file_type || '')
				.data('pendingImageWidth', parseInt(previewMeta.width, 10) || 0)
				.data('pendingImageHeight', parseInt(previewMeta.height, 10) || 0);
		}

		/**
		 * Save latest apply_prompt payload used by regenerate action.
		 */
		function setLastApplyPromptPayload(payload) {

			prLastApplyPromptPayload = payload || null;
		}

		/**
		 * Return latest apply_prompt payload used by regenerate action.
		 */
		function getLastApplyPromptPayload() {

			return prLastApplyPromptPayload || null;
		}

		/**
		 * Toggle regenerate icon loading state.
		 */
		function setRegenerateLoading(show) {

			if (!$prRegenerateBtn || !$prRegenerateBtn.length) {
				return;
			}
			if (show) {
				$prRegenerateBtn.addClass('is-loading').prop('disabled', true);
			} else {
				$prRegenerateBtn.removeClass('is-loading').prop('disabled', false);
			}
		}

		/**
		 * Update queue progress text (e.g. 2/3) in the dialog footer.
		 */
		function setPromptReplaceDialogQueueCounter(currentIndex, totalCount) {
			if (!$prQueueCounter || !$prQueueCounter.length) {
				$prQueueCounter = $prDialog.find('.ubai-prompt-replace-dialog__queue-counter, .unlimitedai-plugin__prompt-replace-dialog__queue-counter').first();
			}
			if (!$prQueueCounter || !$prQueueCounter.length) {
				return;
			}
			var current = Number(currentIndex || 0);
			var total = Number(totalCount || 0);
			if (total > 1 && current >= 1 && current <= total) {
				var queueNextLabel = (typeof sheetspilot !== 'undefined' && sheetspilot.editor && sheetspilot.editor.nextPendingPromptResult)
					? sheetspilot.editor.nextPendingPromptResult
					: 'Next pending prompt result';
				$prQueueCounter
					.text(current + '/' + total)
					.attr('title', queueNextLabel + ' (' + current + '/' + total + ')')
					.attr('aria-label', queueNextLabel + ' (' + current + '/' + total + ')')
					.css('display', '');
			} else {
				$prQueueCounter.text('').hide();
			}
		}

		/**
		 * Hide and reset queue progress text.
		 */
		function clearPromptReplaceDialogQueueCounter() {
			setPromptReplaceDialogQueueCounter(0, 0);
		}

		/**
		 * Check whether prompt-replace dialog is currently visible.
		 */
		function isPromptReplaceDialogOpen() {
			return !!($prDialog && $prDialog.length && $prDialog.is(':visible'));
		}

		return {
			init: init,
			showCellRules: showCellRules,
			closeCellRules: closeCellRules,
			openCellRulesForColumn: openCellRulesForColumn,
			initPromptCodeMirror: initPromptCodeMirror,
			updatePromptInputPlaceholder: updatePromptInputPlaceholder,
			getPromptInputValue: getPromptInputValue,
			setPromptInputValue: setPromptInputValue,
			focusPromptInput: focusPromptInput,
			openPromptHistoryPanel: openPromptHistoryPanel,
			closePromptHistoryPanel: closePromptHistoryPanel,
			updatePromptHistoryFromResponse: updatePromptHistoryFromResponse,
			initPromptReplaceDialog: initPromptReplaceDialog,
			hidePromptReplaceDialog: hidePromptReplaceDialog,
			showPromptReplaceDialogForSelection: showPromptReplaceDialogForSelection,
			showPromptReplaceDialogForCell: showPromptReplaceDialogForCell,
			setPromptReplaceDialogText: setPromptReplaceDialogText,
			setPromptReplaceDialogImagePreview: setPromptReplaceDialogImagePreview,
			getPromptReplaceDialogTargetCell: getPromptReplaceDialogTargetCell,
			isPromptReplaceDialogOpen: isPromptReplaceDialogOpen,
			setPromptReplaceDialogQueueCounter: setPromptReplaceDialogQueueCounter,
			clearPromptReplaceDialogQueueCounter: clearPromptReplaceDialogQueueCounter,
			setLastApplyPromptPayload: setLastApplyPromptPayload,
			getLastApplyPromptPayload: getLastApplyPromptPayload,
			setRegenerateLoading: setRegenerateLoading,
			updateImageDefaultOptionLabels: updateImageDefaultOptionLabels,
			onPromptReplaceDialogReplaceClick: onPromptReplaceDialogReplaceClick,
			onPromptReplaceDialogApplyImageClick: onPromptReplaceDialogApplyImageClick,
			filterDomPromptHistoryListBasedOnCellType: filterDomPromptHistoryListBasedOnCellType,
			filterDomPromptHistoryList: filterDomPromptHistoryList
		};
	}

	var ubaiPromptsInstance = SheetsPilot_Prompts();
	window.ubaiPrompts = ubaiPromptsInstance;

	$(document).ready(function () {

		ubaiPromptsInstance.init();
		$(document).on('ubai_open_cell_rules', function (e, columnName) {
			if (columnName && typeof columnName === 'string') {
				ubaiPromptsInstance.openCellRulesForColumn(columnName);
			}
		});
	});

})(jQuery);
