/* Main view controller for the posts editor. */
function SheetsPilot_PostsEditorView(action_prefix, action_name) {

	const self = this;

	//classes / ids
	var g_spreadsheetContainerID, g_spreadsheetID, g_ubaiColumnsSelectorID, g_columnSelectorToggleInputClass, g_classActive, g_postTypeSelectorSelect2DropdownClass, g_TagDropdownSelector, g_contextMenuClass, g_tagCounterClass, g_hiddenClass, g_drawerBulkEditClass, g_drawerBulkEditSelectClass, g_isOpenClass, g_isFixedPositionClass, g_ubaiTextContextMenuID, g_ubaiSidebarModeTabImage;

	var classSheetsPilotDrawer;
	var classSheetsPilotNotification;



	//selectors
	var g_ubaiFeaturedImageUploaderSelector, g_tagEditorSelector, g_imageUploderCellClass, g_columnSelectorPopupItemSelector, g_tagsEditorSelector;
	var g_ubaiTableSearchSelector;
	var g_ubaiSortableColSelector;
	var g_dropdownContainerSelector;
	var g_quickSearchWrapperSelector;
	var g_postObjectEditorInput;
	var g_acfSelectEditorInput;
	var g_tagSelectEditorInput;
	var g_editorContainer;
	var g_ubaiAddNewRowRrigger;
	var g_ajaxLoaderNoIndex;
	var g_ajaxLoaderSearch;
	var g_bodySelector;
	var g_taxQuickSearch;
	var g_categoryEditor;
	var g_bulkEditCheckboxInput;


	var g_applyDrawerAction;
	var g_drawerSaveLoader;
	var g_drawerSaveLoaderNoIndex;
	var g_editorPart;
	var g_visualPart;
	var g_singleTagBubbleNoPrefix;
	var g_addNewRowRriggerSelector;
	var g_addNewRowDropdownSelector;
	var g_generalDropdownSelector;
	var g_duplicateRowDropdownSelector;
	var g_duplicateDropdownItemSelector;
	var g_generalDropdownContainerSelector;
	var g_generalDropdownButtonSelector;
	var g_filterColumnSettingsIcon;
	var g_contextMenuOpenedClass;
	var g_generalPopupBtnSelector;
	var g_generalPopupSelector;
	var g_addNewRowPopupSelector;
	var g_renameColumnSelector;
	var g_renameColumnModalSubmit;
	var g_renameColumnModalReset;
	var g_duplicateColumnModalSubmit;
	var g_thTitle;
	var g_thColumn;

	// row selector
	var g_rowsAmountSelector;

	// pagination
	var g_pagiRowsFirst;
	var g_pagiRowsLast;
	var g_pagiRowsTotal;
	var g_pagiPagesFirst;
	var g_pagiPagesLast;
	var g_pagiArrowButtonPrev;
	var g_pagiArrowButtonNext;


	var g_deletePostButton;
	var g_duplicatePostButton;


	//objects
	var g_objForm, g_objPostsEditor, g_objWrapper, g_objPrompts;
	var g_objSpreadsheet, g_objUbaiSelectedColumnsInput, g_objUbaiSelectedColumnsOrderInput, g_objPostTypeSelector, g_objColumnSelector, g_objUploadFeaturedImage,
		g_objPostTypeSelectorCountNumber, g_objPostTypeSelectorCountLabel, g_objColumnSelectorPopupList, g_objColumnSelectorToggleInput, g_objColumnSelectorPopupBtn, g_objColumnSelectorPopup,
		g_objColumnSelectorPopupCloseBtn, g_objDropdownBtn, g_objSearchIcon, g_objQuickSearchCloseBtn, g_objSideBarToggleBtn, g_objSideBarHistoryBtn, g_objSideBar, g_objSideBarApplyBtn, g_objDrawer, g_objDrawerOverlay, g_objDrawerCloseBtn, g_objOpenDrawer,
		g_objRowsSelector, g_objRowsAmountSelector, g_objPostEditPopupBtn, g_objPostEditorPopup, g_objPostEditClosePopupBtn, g_objPostEditPopupOverlay, g_objBulkEditSelect, g_objSearchInput, g_objAddCustomNumberOfRowsButton, g_objAddCustomNumberOfRowsPopup,
		g_objAddcustoNumberOfRowsCloseBtn, g_objAddCustomNumberOfRowsCancelBtn, g_objAddRowDropdownItems, g_objAddCustomNumberOfRowsAddBtn, g_addRowNumberInput, g_objaddNewRowRrigger, g_objGeneralDropdownButton, g_objGeneralClosePopupBtn,
		g_objGeneralTablePopup, g_objMainManuBtn, g_objMainManuDropdown, g_objQuickSearchLocateBtn;

	//helpers
	var currentFeaturedImageLink;

	if (!g_doublyAdmin)
		g_doublyAdmin = new UniteAdminSheetsPilot();

	var this_prefix = action_prefix;
	var init_columns;
	var initialFilteredColumns;
	var saved_columns;
	var tableManipulator;
	var g_lastApplyPromptPayload = null;
	var g_lastImageActionSyncCellKey = null;
	var g_applyPromptResponseQueue = [];
	var g_applyPromptQueueBusy = false;
	var g_promptDialogQueueTotal = 0;
	var g_promptDialogQueueClosedCount = 0;
	var g_promptDialogUntouchedKeys = [];
	var g_promptDialogQueueNavigatingNext = false;
	var g_maxWordsForTooltip = 20;
	var bulkActionDataType = false;
	var bulkActionColumnName = false;
	var bulkActionValues = [];
	var bulkActionIDs = [];
	var columnModalPointer;
	var currentModalObject;
	var rightClickObjectPointer;
	var isPostTypeLinkClicked = false;
	var orderingColumn;
	var orderingDirection;

	var saveImageQueryRequest;

	var imageGenerationQuery = {};
	var imageQueryCheckInterval = {};

	var g_automateWorkflow = {
		running: false,
		stopped: false,
		planning: false,
		queue: [],
		currentIndex: 0,
		imagePollTimer: null,
		origAjaxRequest: null,
		selectedIds: []
	};


	/**
	* init the class
	*/
	this.init = function () {

		//classes / ids
		g_spreadsheetID = "new_output_table";
		g_spreadsheetContainerID = "spreadsheet_temporary";
		g_ubaiColumnsSelectorID = "ubai_columns_selector";
		g_columnSelectorToggleInputClass = "unlimitedai-plugin__columns-selector__toggle-input";
		g_classActive = "ue-active";
		g_postTypeSelectorSelect2DropdownClass = "ubai-select2-dropdown-post-type-selector";
		g_columnSelectorPopupItemSelector = ".unlimitedai-plugin__columns-selector__drawer-item";
		g_ubaiPopupFieldsList = "#ubai_popup_fields_list";
		g_contextMenuClass = "unlimitedai-plugin__context-menu";
		g_imageUploderCellClass = "ubai_featured_image_uploader-cell";
		g_tagCounterClass = "unlimitedai-plugin__tags-tag-counter";
		g_hiddenClass = "uai-hidden";
		g_drawerBulkEditClass = "unlimitedai-plugin__drawer-bulk-edit";
		g_drawerBulkEditSelectClass = "bulk_select_block";
		g_contextMenuOpenedClass = "context-menu-opened";
		g_isOpenClass = "is-open";
		g_drawerStockManageClass = "unlimitedai-plugin__drawer-manage-stock";
		g_isFixedPositionClass = "is-fixed";
		g_ubaiTextContextMenuID = "ubai_text_context_menu";
		g_ubaiSidebarModeTabImage = "ubai_sidebar_mode_tab_image";

		classSheetsPilotDrawer = g_drawer;
		classSheetsPilotNotification = g_notification;

		//css selectors
		g_categoryEditor = ".category_editor";
		g_bulkEditCheckboxInput = ".unlimitedai-plugin__td-bulk-edit__select-input";
		g_TagDropdownSelector = ".ubai-tag-dropdown";
		g_ubaiFeaturedImageUploaderSelector = ".ubai_featured_image_uploader";
		g_ubaiTableSearchSelector = "#ubai_quick_search";
		g_ubaiSortableColSelector = "th.orderable";
		g_dropdownContainerSelector = ".unlimitedai-plugin__dropdown-container";
		g_quickSearchWrapperSelector = ".unlimitedai-plugin__search-wrapper"
		g_tagEditorSelector = ".tag_editor";
		g_tagsEditorSelector = ".js-example-basic-multiple____";
		g_postObjectEditorInput = ".post_object_editor_input";
		g_acfSelectEditorInput = ".acf_select_editor_input";
		g_tagSelectEditorInput = ".tag_editor_input";
		g_editorContainer = ".editor_container";
		g_addNewRowRriggerSelector = "#ubai_add_new_row_trigger";
		g_bodySelector = 'body';
		g_taxQuickSearch = '.tax_quick_search';
		g_applyDrawerAction = '#apply_drawer_action';
		g_addNewRowDropdownSelector = ".unlimitedai-plugin__add-row__dropdown";
		g_generalDropdownSelector = ".unlimitedai-plugin__dropdown";
		g_duplicateRowDropdownSelector = ".unlimitedai-plugin__duplicate_row_dropdown";
		g_generalDropdownContainerSelector = ".unlimitedai-plugin__container";
		g_generalDropdownButtonSelector = ".unlimitedai-plugin__dropdown-button";
		g_ubaiQuickSearchLocateInput = "#ubai_quick_search_locate_input";

		jumpItemIsSearchableNoIndex = "jump_item_is_searchable";
		jumpItemIsSearchable = "." + jumpItemIsSearchableNoIndex;

		g_bulkEditSearchInput = ".unlimitedai-plugin__drawer-bulk-edit__search-input";
		g_bulkEditDrawerListItemClass = "unlimitedai-plugin__drawer-bulk-edit__list-item";

		g_columnsOrderingSearchInput = ".unlimitedai-plugin__columns-selector__live-search-input";
		g_addRowNumberInput = ".unlimitedai-plugin__add-row__number-input";
		g_filterColumnSettingsIcon = '.unlimitedai-plugin__filter-column-settings-icon';
		g_generalPopupBtnSelector = ".unlimitedai-plugin__dropdown-item--custom";

		g_duplicateDropdownItemSelector = ".unlimitedai-plugin__dropdown-item";

		g_generalPopupSelector = "#ubai_context_menus_content_popup";
		g_addNewRowPopupSelector = "#uai-add-new-row-popup";
		g_duplicateRowCancelBtnSelector = ".unlimitedai-plugin__popup__button.cancel";
		g_renameColumnSelector = ".unlimitedai-plugin__context-menu__rename_column";

		g_ColumnTypeInfoSelector = ".unlimitedai-plugin__context-menu__item_info";
		g_ColumnTypeInfoNameSelector = ".unlimitedai-plugin__context-menu__item_info-type";
		g_copyColumnInfoTypeTextSelector = ".unlimitedai-plugin__context-menu__item_copy_btn";

		g_deletePostButton = ".delete_post_button";
		g_duplicatePostButton = ".duplicate_post_button";



		g_drawerSaveLoader = '.unlimitedai-plugin_drawer_saving';
		g_drawerSaveLoaderNoIndex = 'unlimitedai-plugin_drawer_saving';


		g_rowsAmountSelector = "#ubai_rows_selector";

		g_pagiRowsFirst = ".unlimitedai-plugin__pagination-rows__current--first";
		g_pagiRowsLast = ".unlimitedai-plugin__pagination-rows__current--last";
		g_pagiRowsTotal = ".unlimitedai-plugin__pagination-rows--total";
		g_pagiPagesFirst = ".unlimitedai-plugin__pagination-pages__current--first";
		g_pagiPagesLast = ".unlimitedai-plugin__pagination-pages__current--last";
		g_pagiArrowButtonPrev = ".unlimitedai-plugin__pagination-pages__btn--prev";
		g_pagiArrowButtonNext = ".unlimitedai-plugin__pagination-pages__btn--next";
		g_ajaxLoaderNoIndex = "uba_loader_saving";
		g_ajaxLoaderSearch = "uba_loader_searching";
		g_ajaxLoaderError = "uba_loader_error";
		g_ajaxLoaderProcessing = "uba_loader_processing";

		g_editorPart = '.editor_part';
		g_visualPart = '.visual_part';
		g_singleTagBubbleNoPrefix = "single_tag_bubble";



		g_renameColumnModalSubmit = "uai_rename_column_button";
		g_renameColumnModalReset = "uai_reset_column_name_button";
		g_duplicateColumnModalSubmit = "uai_duplicate_column_button";
		g_thTitle = "unlimitedai-plugin__th-title";
		g_thColumn = "unlimitedai-plugin__th";
		g_columnFilterSearchInput = ".uai-column-filter-search-input";

		g_columnFilterTermSearchInput = '.uai-column-filter-term-inner-search-input';
		g_columnFilterTermmakeFilteringActionButton = '.unlimitedai-plugin__th-filter-dropdown__make_filtering';

		g_columnFilterDropdownFilterContainerClass = ".unlimitedai-plugin__th-filter-dropdown__term_container";
		g_columnFilterDropdownFilterContainerItemClass = "unlimitedai-plugin__th-filter-dropdown__term_filterable_item";
		g_selectAllCheckbox = ".select_all_checkbox";

		gup_QuickSearchDropdownItemNoIndex = "unlimitedai-plugin__quick_search__dropdown-item";
		gup_QuickSearchDropdownItem = "." + gup_QuickSearchDropdownItemNoIndex;

		gup_QuickSearchDropdownItemName = ".unlimitedai-plugin__quick_search__dropdown-item__name";
		g_hasTooltipInnerButtonSelector = ".has-tooltip__inner-button";


		//objects
		g_objPostsEditor = jQuery("#unlimitedai-plugin"); //general wrapper		
		g_objSpreadsheet = g_objPostsEditor.find("#" + g_spreadsheetID);
		g_objWrapper = jQuery("#uc_settings_page_wrapper");
		g_objSearchInput = jQuery(g_ubaiTableSearchSelector);
		g_objColumnFilterSearchInput = jQuery(g_columnFilterSearchInput);


		g_objPostTypeSelector = g_objPostsEditor.find("#ubai_post_type_selector");
		g_objColumnSelector = g_objPostsEditor.find("#" + g_ubaiColumnsSelectorID);
		//g_objUploadFeaturedImage = g_objPostsEditor.find(g_ubaiFeaturedImageUploaderSelector);
		g_objPostTypeSelectorCountNumber = g_objPostsEditor.find(".unlimitedai-plugin__post-type-selector__count-number");
		g_objPostTypeSelectorCountLabel = g_objPostsEditor.find(".unlimitedai-plugin__post-type-selector__count-label");
		g_objUbaiSelectedColumnsInput = g_objPostsEditor.find("#ubai_selected_columns");
		g_objUbaiSelectedColumnsOrderInput = g_objPostsEditor.find("#ubai_selected_columns_order");

		g_objColumnSelectorToggleInput = g_objPostsEditor.find("." + g_columnSelectorToggleInputClass);
		g_objColumnSelectorPopupBtn = g_objPostsEditor.find(".unlimitedai-plugin__columns-selector-btn");

		g_objColumnSelectorPopup = g_objPostsEditor.find(".unlimitedai-plugin__columns-selector__drawer");
		g_objColumnSelectorPopupCloseBtn = g_objPostsEditor.find(".unlimitedai-plugin__columns-selector__drawer-close");
		g_objColumnSelectorPopupList = g_objPostsEditor.find(".unlimitedai-plugin__columns-selector__drawer-list");

		g_objDropdownBtn = g_objPostsEditor.find(".unlimitedai-plugin__dropdown-btn");
		g_objSearchIcon = g_objPostsEditor.find(".unlimitedai-plugin__quick_search-icon");
		g_objQuickSearchCloseBtn = g_objPostsEditor.find(".unlimitedai-plugin__quick_search-close-btn");
		g_objSideBarToggleBtn = g_objPostsEditor.find(".unlimitedai-plugin__sidebar-toggle");
		g_objSideBarHistoryBtn = g_objPostsEditor.find("#ubai_sidebar_history");
		g_objSideBar = g_objPostsEditor.find(".unlimitedai-plugin__sidebar");
		g_objSideBarApplyBtn = g_objPostsEditor.find(".unlimitedai-plugin__sidebar-apply-btn");

		g_objDrawer = g_objPostsEditor.find(".unlimitedai-plugin__side-drawer");
		g_objDrawerOverlay = g_objPostsEditor.find(".unlimitedai-plugin__overlay");
		g_objDrawerCloseBtn = g_objPostsEditor.find(".unlimitedai-plugin__side-drawer__header-close-btn");
		g_objOpenDrawer = g_objPostsEditor.find(".unlimitedai-plugin__side-drawer__open-btn");

		g_objaddNewRowRrigger = g_objPostsEditor.find(g_addNewRowRriggerSelector);
		g_objRowsSelector = g_objPostsEditor.find(".unlimitedai-plugin__rows-selector__select-dropdown");

		g_objRowsAmountSelector = g_objPostsEditor.find(g_rowsAmountSelector);
		g_objPagiArrowButtonNext = g_objPostsEditor.find(g_pagiArrowButtonNext);
		g_objPagiArrowButtonPrev = g_objPostsEditor.find(g_pagiArrowButtonPrev);

		g_objDeletePostButton = g_objPostsEditor.find(g_deletePostButton);
		g_objDuplicatePostButton = g_objPostsEditor.find(g_duplicatePostButton);
		g_objPostEditPopupBtn = g_objPostsEditor.find(".edit_post_modal");
		g_objPostEditorPopup = g_objPostsEditor.find(".unlimitedai-plugin__post_editor__popup");
		g_objPostEditClosePopupBtn = g_objPostsEditor.find(".unlimitedai-plugin__post_editor__popup-close");
		g_objPostEditPopupOverlay = g_objPostsEditor.find(".unlimitedai-plugin__post_editor__popup");
		g_objBulkEditSelect = g_objPostsEditor.find(".unlimitedai-plugin__bulk-edit__select");
		g_objAddCustomNumberOfRowsButton = g_objPostsEditor.find(".unlimitedai-plugin__add-row__dropdown-item--custom");
		g_objAddRowDropdownItems = g_objPostsEditor.find(".unlimitedai-plugin__add-row__dropdown-item");
		g_objAddCustomNumberOfRowsPopup = g_objPostsEditor.find("#uai-add-new-row-popup");
		g_objAddcustoNumberOfRowsCloseBtn = g_objAddCustomNumberOfRowsPopup.find(".unlimitedai-plugin__popup-close");
		g_objGeneralClosePopupBtn = g_objPostsEditor.find(".unlimitedai-plugin__popup-close");
		g_objAddCustomNumberOfRowsCancelBtn = g_objAddCustomNumberOfRowsPopup.find(".unlimitedai-plugin__add-row__button.cancel");
		g_objAddCustomNumberOfRowsAddBtn = g_objAddCustomNumberOfRowsPopup.find(".unlimitedai-plugin__add-row__button.add");
		g_objGeneralDropdownButton = g_objPostsEditor.find(g_generalDropdownButtonSelector);
		g_objGeneralTablePopup = g_objPostsEditor.find(g_generalPopupSelector);
		g_objMainManuBtn = g_objPostsEditor.find(".unlimitedai-plugin__main-menu-btn");
		g_objMainManuDropdown = g_objPostsEditor.find(".unlimitedai-plugin__main-menu__dropdown");
		g_objQuickSearchLocateBtn = g_objPostsEditor.find(".unlimitedai-plugin__quick_search-locate_btn");
		var settingsWrapper = g_objWrapper.find(".unite_settings_wide");




		initSelect2ForPostTypeSelector();
		initSelect2ForRowsSelector();
		initSelect2ForBulkEdit();

		// initial visual columns init
		saved_columns = jQuery.parseJSON(g_objUbaiSelectedColumnsInput.val());

		//initEvents();
		initiatePopupColumnsOrder();

		// init error handler
		g_doublyAdmin.setErrorStatusID(g_ajaxLoaderError);

		// fix select button
		fixMediaSelectButton();
	}


	/**
	 * media select fix
	 */
	function fixMediaSelectButton() {
		setInterval(function () {
			if (jQuery('.button.media-button.button-large.media-button-select').html() == '') {
				jQuery('.button.media-button.button-large.media-button-select').html(sheetspilot.editor.select);
			}
		}, 500)
	}


	/**
	 * Escape string for HTML data-title attribute.
	 */
	function escapeAttrForTooltip(str) {

		return String(str)
			.replace(/&/g, "&amp;")
			.replace(/"/g, "&quot;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;");
	}

	/**
	 * Truncate column rule text to a word limit for tooltip preview (show-time only).
	 */
	function truncateColumnRuleForTooltipPreview(text, maxWords) {

		var words = String(text).trim().split(/\s+/).filter(Boolean);
		if (words.length <= maxWords) {
			return words.join(" ");
		}
		return words.slice(0, maxWords).join(" ") + "…";
	}

	/**
	* init events
	*/
	this.initEvents = function () {

		g_objPostTypeSelector.on("change", onPostTypeChange);
		g_objPostTypeSelector.on('select2:open', onPostTypeSelectorSelect2Open);
		g_objPostTypeSelector.on('select2:close', onPostTypeSelectorSelect2Close);
		g_objPostTypeSelector.on('select2:select', function (e) {
			const text = e.params.data.text;
			document.title = text;
		});
		g_objColumnSelectorPopupBtn.on("click", onColumnsSelectorOpenPopupBtnClick);
		g_objColumnSelectorPopupCloseBtn.on("click", onColumnsSelectorClosePopupBtnClick);
		g_objColumnSelectorPopup.on("click", function (e) { onColumnsSelectorPopupOverlayClick(e) });
		g_objPostsEditor.on("change", "." + g_columnSelectorToggleInputClass, onColumnSelectorInputChange);
		g_objDropdownBtn.on("click", onDropdownButtonClick);
		g_objSearchIcon.on("click", onQuickSearchIconClick);
		g_objQuickSearchCloseBtn.on("click", onQuickSearchCloseBtnClick);
		g_objSideBarToggleBtn.on("click", onSideBarToggleBtnClick);
		// History button (open/close prompt history panel) is bound in unlimited_ai_prompts.js
		g_objSideBarApplyBtn.on("click", onSideBarApplyBtnClick);

		g_objPostsEditor.on("mouseenter", ".unlimitedai-plugin__ai-column-settings-icon[data-ubai-column-rule-full]", function () {

			var $icon = jQuery(this);
			var full = $icon.attr("data-ubai-column-rule-full");
			if (!full) {
				return;
			}
			var preview = truncateColumnRuleForTooltipPreview(full, g_maxWordsForTooltip);
			$icon.attr("data-title", escapeAttrForTooltip(preview));
		});
		g_objPostsEditor.on("mouseleave", ".unlimitedai-plugin__ai-column-settings-icon[data-ubai-column-rule-full]", function () {

			jQuery(this).attr("data-title", "");
		});



		g_objRowsAmountSelector.on("change", onRowsPerPageChange);
		g_objPagiArrowButtonNext.on("click", function () {
			onPaginationPrevNextButton('next')
		});
		g_objPagiArrowButtonPrev.on("click", function () {
			onPaginationPrevNextButton('prev')
		});

		g_objPostsEditor.on("click", ".edit_post_modal", onPostEditOpenPopupBtnClick);
		g_objPostEditClosePopupBtn.on("click", onPostEditClosePopupBtnClick);
		g_objPostEditPopupOverlay.on("click", function (e) { onPostEditClosePopupBtnClick(e) });
		// Quick Prompts trigger opens same context menu as cell right-click g_ubaiTextContextMenuID
		initQuickActionsDropdown();
		//initQuickActionsDropdownImages();

		jQuery(document).on('keydown', onDocumentKeydown);

		jQuery(document).on('contextmenu', '.unlimitedai-plugin__th .' + g_thTitle, function (e) { onTHRightClick(e) })

		// Posts editor: catch "run prompt" event from cell context menu; sidebar Apply spinner only if prompt was copied to sidebar.
		// Use namespaced off/on so repeated init does not stack duplicate handlers.
		jQuery(document).off('ubai_run_prompt.ubaiPostsEditor').on('ubai_run_prompt.ubaiPostsEditor', function (e, promptText, contextMenuAction) {
			self.dispatchRunPromptFromContextMenu(promptText, contextMenuAction);
		});

		// Regenerate: rerun latest prompt from prompt replace dialog.
		// Use namespaced off/on so repeated init does not stack duplicate handlers.
		jQuery(document).off('ubai_regenerate_prompt.ubaiPostsEditor').on('ubai_regenerate_prompt.ubaiPostsEditor', function (e, payload) {
			if (!payload || !payload.prompt || !payload.table) {
				return;
			}
			if (window.ubaiPrompts && window.ubaiPrompts.setRegenerateLoading) {
				window.ubaiPrompts.setRegenerateLoading(true);
			}
			attachApplyPromptSidebarOptionsToTableData(payload.table);
			g_lastApplyPromptPayload = payload;
			promptRequestsPanelAdd(payload);
			g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderNoIndex);
			clearApplyPromptDebug();
			clearApplyPromptResponse();
			if (tableManipulator && tableManipulator.setCellApplyPromptLoading) {
				tableManipulator.setCellApplyPromptLoading(true, true, payload.table);
			}
			if (sheetspilot.editor.g_isLogOn == 1) {
				console.log('apply_prompt2');
			}
			g_doublyAdmin.ajaxRequest('apply_prompt', payload, function (response) {
				handleApplyPromptResponse(response, payload);
			}, function (response) {
				finishApplyPromptRequest(response, payload, true);
			});
		});
		jQuery(document).off('ubai_prompt_replace_dialog_closed.ubaiApplyPromptQueue').on('ubai_prompt_replace_dialog_closed.ubaiApplyPromptQueue', function (e, closeMeta) {
			closeMeta = closeMeta || {};
			if (g_promptDialogQueueNavigatingNext) {
				g_applyPromptQueueBusy = false;
				return;
			}

			function removeClosedItemFromPendingPile() {
				if (closeMeta.cellKey) {
					g_promptDialogUntouchedKeys = g_promptDialogUntouchedKeys.filter(function (key) {
						return key !== closeMeta.cellKey;
					});
				}
				if (g_promptDialogQueueTotal > 0) {
					g_promptDialogQueueTotal--;
				}
				g_promptDialogQueueClosedCount = 0;
			}

			if (closeMeta.keepPendingCount) {
				g_applyPromptQueueBusy = false;
				updatePromptReplaceDialogQueueCounter();
				updatePromptResultsToolbarButton();
				return;
			}

			if (closeMeta.dismissedViaCloseButton) {
				removeClosedItemFromPendingPile();
				g_applyPromptQueueBusy = false;
				updatePromptReplaceDialogQueueCounter();
				updatePromptResultsToolbarButton();
				return;
			}

			if (closeMeta.resolved) {
				removeClosedItemFromPendingPile();
				promptRequestsPanelRemoveReadyByKey(closeMeta.cellKey);
			} else if (g_promptDialogQueueClosedCount < g_promptDialogQueueTotal) {
				g_promptDialogQueueClosedCount++;
			}
			if (g_promptDialogQueueTotal <= 0) {
				g_promptDialogQueueTotal = 0;
				g_promptDialogQueueClosedCount = 0;
			} else if (g_promptDialogQueueClosedCount >= g_promptDialogQueueTotal && g_applyPromptResponseQueue.length === 0) {
				g_promptDialogQueueClosedCount = 0;
			}
			g_applyPromptQueueBusy = false;
			if (closeMeta.resolved) {
				processApplyPromptDialogQueue();
			}
			updatePromptReplaceDialogQueueCounter();
			updatePromptResultsToolbarButton();
		});
		jQuery(document).off('ubai_prompt_replace_dialog_opened.ubaiApplyPromptQueue').on('ubai_prompt_replace_dialog_opened.ubaiApplyPromptQueue', function () {
			showPromptResultFocusMode();
			updatePromptReplaceDialogQueueCounter();
			updatePromptResultsToolbarButton();
			renderPromptRequestsPanel();
		});
		jQuery(document).off('ubai_prompt_replace_dialog_closed.ubaiPromptRequestsPanel').on('ubai_prompt_replace_dialog_closed.ubaiPromptRequestsPanel', function () {
			setTimeout(function () {
				if (!isPromptReplaceDialogOpen()) {
					hidePromptResultFocusMode();
				}
			}, 0);
			renderPromptRequestsPanel();
		});
		jQuery(document).off('click.ubaiPendingPromptResultsToolbar', '#ubai_pending_prompt_results_trigger').on('click.ubaiPendingPromptResultsToolbar', '#ubai_pending_prompt_results_trigger', function (e) {
			e.preventDefault();
			// the toolbar counter now opens the requests panel — one calm place
			// to see what is loading and what came back, instead of jumping
			// straight into the next dialog
			togglePromptRequestsPanel();
		});
		jQuery(document).off('ubai_prompt_replace_dialog_queue_next.ubaiApplyPromptQueue').on('ubai_prompt_replace_dialog_queue_next.ubaiApplyPromptQueue', function () {
			goToNextUntouchedPromptDialog();
		});

		// quick search
		g_objSearchInput.on("input", debounce(onMakeingAjaxSearch, 400));

		jQuery(document).on('click', function (e) { onDocumentClick(e) });

		g_objPostsEditor.on("click", g_generalDropdownButtonSelector, function (e) {
			e.preventDefault();
			onRightClickAction(e);
		});

		// sorting col
		jQuery('body').on("click", 'th.orderable .unlimitedai-plugin__th-filter-dropdown__sorting-item', function (e) {
			onTableColSort(e)
		});

		// drop column filtering
		jQuery('body').on("click", 'th .unlimitedai-plugin__th-filter-dropdown__clear_filtering', function (e) {
			clearCustomColumnFiltering(jQuery(e.target));
		});

		// Delete table post
		jQuery(document).on('click', g_deletePostButton, (e) => {
			deleteTablePost(e);
		});
		// Duplicate table post
		jQuery(document).on('click', g_duplicatePostButton, (e) => {
			duplicateTablePost(jQuery(e.currentTarget), 1);
		});
		// Tax filter
		jQuery(document).on('keyup', g_taxQuickSearch, (e) => {
			taxonomyQuickSearch(e);
		});

		// Bulk Editor Search
		jQuery(document).on('keyup', g_bulkEditSearchInput, (e) => {
			bulkEditorQuickSearch(e);
		});

		// Column visibility and order search
		jQuery(document).on('keyup', g_columnsOrderingSearchInput, (e) => {
			columnsOrdersOrderingQuickSearch(e);
		});

		// Content Rules: when Save clicked, get data from dialog and save via AJAX
		g_objPostsEditor.on("ubai_contentrules_save_clicked", function () {
			var dialog = g_objPostsEditor.data("ubaiContentRulesDialog");
			if (!dialog) return;
			var data = dialog.getGeneralData();
			g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderNoIndex);
			g_doublyAdmin.ajaxRequest("save_content_rules", data, function () {

			});
		});



		// replace editor page title
		jQuery(window).on("load", function () {
			replacePageTitle()
		})

		// bulk actions
		g_objBulkEditSelect.on('select2:close', (e) => {
			processBulkActions(e);

			jQuery(classSheetsPilotDrawer.g_objDrawer).addClass(g_drawerBulkEditClass);
			jQuery(classSheetsPilotDrawer.g_objDrawerOverlay).addClass(g_drawerBulkEditClass);
		});
		if (sheetspilot.editor.isPro && sheetspilot.editor.enableAutomateWorkflow) {
			initAutomateWorkflowUI();
			g_objPostsEditor.on('click', '#ubai_automate_workflow_btn', onAutomateWorkflowBtnClick);
			g_objPostsEditor.on('click', '#ubai_automate_workflow_stop_btn', onAutomateWorkflowStopClick);
			g_objPostsEditor.on('click', '#ubai_workflow_plan_start_btn', onAutomateWorkflowPlanStartClick);
			g_objPostsEditor.on('click', '#ubai_workflow_plan_close_btn', onAutomateWorkflowPlanCloseClick);
			g_objPostsEditor.on('click', '#ubai_workflow_plan_stop_btn', onAutomateWorkflowStopClick);
			g_objPostsEditor.on('click', '#ubai_workflow_plan_panel .ubai-wfp__item', onAutomateWorkflowPlanItemClick);

			// Automate workflow works best as a 1-row action (predictable plan + cell order).
			// If selection count != 1: hide the button and hide any existing plan panel.
			function updateAutomateWorkflowButtonVisibilityFromSelection() {
				var selectedCount = jQuery(g_bulkEditCheckboxInput + ':checked').length;
				var $btn = g_objPostsEditor.find('#ubai_automate_workflow_btn');

				// If the workflow is already running we keep the button hidden anyway.
				var shouldShow = sheetspilot.editor.isPro && sheetspilot.editor.enableAutomateWorkflow && selectedCount === 1 && !g_automateWorkflow.running;
				if ($btn && $btn.length) {
					$btn.toggle(shouldShow);
				}

				if (!shouldShow && !g_automateWorkflow.running) {
					// Do not leave the plan panel visible when the corresponding button is hidden.
					g_automateWorkflow.planning = false;
					g_automateWorkflow.queue = [];
					g_automateWorkflow.selectedIds = [];
					g_automateWorkflow.currentIndex = 0;
					hideAutomateWorkflowPlanPanel();
				}
			}

			// Initial sync.
			updateAutomateWorkflowButtonVisibilityFromSelection();

			// Keep UI synced when checkboxes change.
			// setTimeout(0) so :checked is updated when the handler runs.
			jQuery(document).off('change.ubaiAutomateWorkflowVisibility', g_bulkEditCheckboxInput)
				.on('change.ubaiAutomateWorkflowVisibility', g_bulkEditCheckboxInput, function () {
					setTimeout(updateAutomateWorkflowButtonVisibilityFromSelection, 0);
				});
			jQuery(document).off('change.ubaiAutomateWorkflowVisibility', g_selectAllCheckbox)
				.on('change.ubaiAutomateWorkflowVisibility', g_selectAllCheckbox, function () {
					setTimeout(updateAutomateWorkflowButtonVisibilityFromSelection, 0);
				});
		}
		jQuery(document).on('click', g_applyDrawerAction, (e) => {
			applyDrawerBulkActions(e);
		});

		// Debug panel stays hidden at start; shown only after apply_prompt when updateApplyPromptDebug runs
		g_objPostsEditor.find(".unlimitedai-plugin__sidebar-debug").hide();

		g_objPostsEditor.on("click", '.unlimitedai-plugin__drawer-bulk-edit__list-item', (e) => {
			onBulkEditDrawerListItemClick(e);
		});

		g_objAddCustomNumberOfRowsButton.on("click", function (e) { onAddCustomNumberOfRowsClick(e) });
		g_objAddcustoNumberOfRowsCloseBtn.on("click", function (e) { closeAddCustomNumberOfRowsPopups(e) });
		g_objAddCustomNumberOfRowsCancelBtn.on("click", function (e) { closeAddCustomNumberOfRowsPopups(e) });
		g_objAddCustomNumberOfRowsPopup.on("click", function (e) { onAddCustomNumberOfRowsPopupsOverlayClick(e) });

		g_objGeneralClosePopupBtn.on("click", function (e) { closeGeneralPopups(e) });
		g_objPostsEditor.on("click", g_generalPopupBtnSelector, function (e) { onOpenGeenralPopupsClick(e) });
		g_objGeneralTablePopup.on("click", function (e) { onGeneralTablePopupOverlayClick(e) });
		g_objPostsEditor.on("click", g_duplicateRowCancelBtnSelector, function (e) { closeGeneralPopups(e) });
		g_objPostsEditor.on("click", ".unlimitedai-plugin__main-menu__version-btn", onMainMenuVersionBtnClick);

		//add fixed number of rows
		g_objAddRowDropdownItems.on("click", function (e) {
			if (!jQuery(this).hasClass("unlimitedai-plugin__add-row__dropdown-item--custom")) {
				var this_number = jQuery(this).attr('data-value');
				onAddNewRow(this_number);
				jQuery(document).trigger('click');
			}

		});

		// add custom numbers of rows
		g_objAddCustomNumberOfRowsAddBtn.on("click", function (e) {
			var this_number = parseInt(jQuery(g_addRowNumberInput).val());
			onAddNewRow(this_number);
			closeAddCustomNumberOfRowsPopups(e);
		});



		// trace columns width change
		jQuery(document).on('mouseup', function (e) {
			if (jQuery(e.target).hasClass('unlimitedai-plugin__th-resizer')) {
				saveTableColumnWidths();
			}
		});

		// multiduplicate button click
		jQuery(document).on('click', g_duplicateRowDropdownSelector + ' ' + g_duplicateDropdownItemSelector, function (e) {
			var row_number = jQuery(this).attr('data-value');
			if (row_number == 'custom') {
				var modal_data = {
					'title': sheetspilot.editor.duplicate_row,
					'subtitle': sheetspilot.editor.number_of_copies,
					'placeholder': sheetspilot.editor.enter_number,
					'button_text': sheetspilot.editor.duplicate,
					'input_id': g_duplicateColumnModalSubmit + '_input',
					'button_id': g_duplicateColumnModalSubmit,
					'input_type': 'number',
				};
				currentModalObject = new SheetPilotSmallModal(modal_data);
				currentModalObject.showModal();
			} else {
				duplicateTablePost(jQuery(e.currentTarget), row_number);
			}

		});

		// add custom number of duplicated rows
		jQuery(document).on('click', '#' + g_duplicateColumnModalSubmit, function (e) {
			var value = jQuery('#' + g_duplicateColumnModalSubmit + '_input').val();

			duplicateTablePost(rightClickObjectPointer, value);
			currentModalObject.hideModal();
			currentModalObject.hideReset();
		});


		// rename button click
		jQuery(document).on('click', g_renameColumnSelector, function (e) {

			var modal_data = {
				'title': sheetspilot.editor.rename_column,
				'subtitle': sheetspilot.editor.new_column_name,
				'placeholder': sheetspilot.editor.enter_column_name,
				'button_text': sheetspilot.editor.rename_column,
				'input_id': g_renameColumnModalSubmit + '_input',
				'button_id': g_renameColumnModalSubmit,
				'reset_id': g_renameColumnModalReset,
				'input_type': 'text',
			};
			currentModalObject = new SheetPilotSmallModal(modal_data);
			currentModalObject.showReset();
			currentModalObject.setInputvalue(jQuery('.' + g_thTitle, columnModalPointer).html());
			currentModalObject.showModal();
			g_objPostsEditor.find('.' + g_contextMenuClass).remove();
		});

		// copy button click
		jQuery(document).on('click', g_copyColumnInfoTypeTextSelector, function (e) {
			copyText(e, g_ColumnTypeInfoSelector, g_ColumnTypeInfoNameSelector);
		});

		// rename column acton
		jQuery(document).on('click', '#' + g_renameColumnModalSubmit, function (e) {
			var post_type = g_objPostTypeSelector.val();
			var data_name = columnModalPointer.attr('data-name');
			var column_name_value = jQuery('#' + g_renameColumnModalSubmit + '_input').val();

			try {
				var current_pt_names = jQuery.parseJSON(localStorage.getItem('uai_column_name_' + post_type));
			} catch (e) {
				var current_pt_names = {};
			}

			if (!current_pt_names || current_pt_names == '') {
				current_pt_names = {};
			}
			current_pt_names[data_name] = column_name_value;

			jQuery('.' + g_thTitle, columnModalPointer).html(column_name_value);
			jQuery(g_columnSelectorPopupItemSelector + '[data-index="' + data_name + '"] .unlimitedai-plugin__columns-selector__drawer-label').html(column_name_value);

			localStorage.setItem('uai_column_name_' + post_type, JSON.stringify(current_pt_names));
			currentModalObject.hideModal();
			currentModalObject.hideReset();
		})

		// reset column name
		jQuery(document).on('click', '#' + g_renameColumnModalReset, function (e) {
			var post_type = g_objPostTypeSelector.val();
			var data_name = columnModalPointer.attr('data-name');
			var default_name = columnModalPointer.attr('data-default');

			try {
				var current_pt_names = jQuery.parseJSON(localStorage.getItem('uai_column_name_' + post_type));
			} catch (e) {
				var current_pt_names = {};
			}

			if (!current_pt_names || current_pt_names == '') {
				current_pt_names = {};
			}
			current_pt_names[data_name] = default_name;

			jQuery('.' + g_thTitle, columnModalPointer).html(default_name);
			jQuery(g_columnSelectorPopupItemSelector + '[data-index="' + data_name + '"] .unlimitedai-plugin__columns-selector__drawer-label').html(default_name);

			localStorage.setItem('uai_column_name_' + post_type, JSON.stringify(current_pt_names));
			currentModalObject.hideModal();
			currentModalObject.hideReset();
		})

		// column search
		const debouncedColumnSearch = debounce(onMakeingColumnAjaxSearch, 400);
		jQuery(document).on("input", g_columnFilterSearchInput, function (e) {
			debouncedColumnSearch(e);
		});

		// column filter filter search
		jQuery(document).on("input", g_columnFilterTermSearchInput, (e) => {
			columnFilterTermFilterQuickSearch(e);
		});

		// column filter filter action
		jQuery(document).on("click", g_columnFilterTermmakeFilteringActionButton, (e) => {
			columnFilterTermFilterAction(e);
		});

		//main menu handler
		g_objMainManuBtn.on("click", onMainMenuClick);

		// in filter select all 
		jQuery(document).on("change", g_selectAllCheckbox, function (e) {
			columnFilterSelectAllAction(e, jQuery(this));
		});
		jQuery(document).on("change", '.' + g_columnFilterDropdownFilterContainerItemClass + ' input[type="checkbox"] ', function (e) {
			columnFilterTermFilterAction(e);
		});
		// radio for date picker
		jQuery(document).on("change", '.' + g_columnFilterDropdownFilterContainerItemClass + ' input[type="radio"] ', function (e) {
			columnFilterTermFilterAction(e);
		});
		// trace radio custom date range picker
		jQuery(document).on("change", '#range_date_filter_start, #range_date_filter_end', function (e) {
			if (jQuery('#range_date_filter_start').val() != '' && jQuery('#range_date_filter_end').val()) {
				columnFilterTermFilterAction(e);
			}
		});

		g_objPostsEditor.on("click", ".unlimitedai-plugin__quick_search-locate_btn", function (e) { onQuickSearchLocateBtnClick(e) });



		// jump to column
		jQuery(document).on("click", gup_QuickSearchDropdownItem, function (e) {
			jumpToColumn(e);
		});

		jQuery(document).on("keydown", gup_QuickSearchDropdownItem, function (e) {
			onQuickSearchKeydown(e);
		});
		// search in colum jumper
		jQuery(document).on("input", g_ubaiQuickSearchLocateInput, function (e) {
			searchInColumnJumper(e);
		});
		g_objPostsEditor.on("mouseenter", g_columnSelectorPopupItemSelector, function (e) { onColumnSelectorItemMouseOver(e); })
		g_objPostsEditor.on("click", g_hasTooltipInnerButtonSelector, function (e) { onColumnSelectorItemTooltipCopyBtnClick(e); })

		// drop ID width	
		/*	
		jQuery(document).on("dblclick", 'th', function(e){
			saveTableColumnWidths( jQuery(this).attr('data-name') );
			restoreTableColumnWidths( );
		});
		*/


		// on post type link click
		jQuery('#ubai_post_type_selector').on('select2:selecting', function (e) {
			if (jQuery(e.params.args)) {
				if (jQuery(e.params.args.originalEvent.target).closest('.open-link').length) {
					window.open(jQuery(e.params.args.originalEvent.target).closest('.open-link').attr('data-url'), '_blank');
					isPostTypeLinkClicked = true;
					setTimeout(function () {
						isPostTypeLinkClicked = false;
					}, 500);
					e.preventDefault();
				}
			}
		});

		// on post type link click
		jQuery(document).on('mouseup', '.open-link', function (e) {
			e.preventDefault();
			e.stopPropagation();
			if (!isPostTypeLinkClicked) {
				const targetUrl = jQuery(this).attr('data-url');

				if (targetUrl) {
					window.open(targetUrl, '_blank'); // Opens a duplicate tab cleanly
				}

				isPostTypeLinkClicked = true;
				setTimeout(function () {
					isPostTypeLinkClicked = false;
				}, 500);
			}

		});

		updatePromptResultsToolbarButton();
	}


	/**
	 * searchin column jumper
	 */
	function searchInColumnJumper(e) {
		var current_val = jQuery(e.target).val().toLowerCase();

		jQuery(gup_QuickSearchDropdownItem + g_duplicateDropdownItemSelector + jumpItemIsSearchable).each(function () {

			var local_text = jQuery(gup_QuickSearchDropdownItemName, this).html().toLowerCase();

			if (!local_text.includes(current_val)) {
				jQuery(this).hide();
			} else {
				jQuery(this).show();
			}
		})
	}

	/**
	 * Jump to column
	 */
	function jumpToColumn(e) {

		var index = jQuery(e.target).attr('data-index');

		if (!jQuery(e.target).hasClass(gup_QuickSearchDropdownItemNoIndex)) {
			var index = jQuery(e.target).parents(gup_QuickSearchDropdownItem).attr('data-index');
		}

		jQuery(gup_QuickSearchDropdownItem).removeClass(g_classActive);
		jQuery(e.target).addClass(g_classActive);

		const $container = jQuery('#' + g_spreadsheetContainerID);
		const $th = jQuery('#' + g_spreadsheetID + ' th').eq(index);

		const containerOffset = $container.offset().left;
		const thOffset = $th.offset().left;

		let stickyWidth = 0;
		jQuery('th:lt(3)').each(function () {
			stickyWidth += jQuery(this).outerWidth();
		});

		const scrollTo = thOffset - containerOffset + $container.scrollLeft() - stickyWidth;

		$container.animate({
			scrollLeft: scrollTo
		}, 300);
	}

	this.clearSingleFilter = function (element) {
		clearCustomColumnFiltering(element);
	}
	this.clearAllFilters = function (element) {
		g_objSpreadsheet.find(g_filterColumnSettingsIcon + '.is_active').each(function () {
			var parent_th = jQuery(this).parents('th');
			var search_type = parent_th.attr('data-column-search-type');

			if (search_type == 'text') {
				parent_th.find(g_columnFilterSearchInput).val('');

			}
			if (search_type == 'filter') {
				jQuery(g_columnFilterDropdownFilterContainerClass + ' .' + g_columnFilterDropdownFilterContainerItemClass + ' input[type="checkbox"]', parent_th).prop('checked', true)
			}
			jQuery(this).removeClass('is_active');
		})

		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderProcessing);
		var data =
		{
			col_filtering_query: globalTableFilterinByColumnsReturnFilters(),
			post_type: g_objPostTypeSelector.val(),
			rows_per_page: g_objRowsAmountSelector.val(),
		};

		g_doublyAdmin.ajaxRequest('make_column_ajax_search', data, function (response) {

			let tableData = response.message.postslist;
			let tableStructure = response.message.table_structure;


			self.processPagination(response.message.total_count, g_objRowsAmountSelector.val(), response.message.current_page)

			tableManipulator = new SheetsPilot_CellProcessing('#new_output_table', tableStructure);
			tableManipulator.fillCellsWithContent(tableData);

			bindAllTableActionsAfterReload( initialFilteredColumns );

			// set top bar filters
			g_topFilteringBar.processFiltersAndShowHideMenu();

		});


	}


	// clean custom column filtering
	function clearCustomColumnFiltering(element) {

		var parent_th = element.parents('th');
		const type = parent_th.attr('data-type');
		const column = parent_th.attr('data-name');

		var search_type = parent_th.attr('data-column-search-type');

		if (search_type == 'text') {
			parent_th.find(g_columnFilterSearchInput).val('');

		}
		if (search_type == 'filter') {
			jQuery(g_columnFilterDropdownFilterContainerClass + ' .' + g_columnFilterDropdownFilterContainerItemClass + ' input[type="checkbox"]', parent_th).prop('checked', true)
		}

		parent_th.find(g_filterColumnSettingsIcon + '.is_active').removeClass('is_active');
		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderProcessing);
		var data =
		{
			col_filtering_query: globalTableFilterinByColumnsReturnFilters(),
			post_type: g_objPostTypeSelector.val(),
			rows_per_page: g_objRowsAmountSelector.val(),
			column_name: column,
			type: type,
		};

		g_doublyAdmin.ajaxRequest('make_column_ajax_search', data, function (response) {

			let tableData = response.message.postslist;
			let tableStructure = response.message.table_structure;


			var pagination_info = self.processPagination(response.message.total_count, g_objRowsAmountSelector.val(), response.message.current_page);
			pagination_info['global_counter'] = response.message.global_posts_number;

			tableManipulator = new SheetsPilot_CellProcessing('#new_output_table', tableStructure);
			tableManipulator.fillCellsWithContent(tableData);

			bindAllTableActionsAfterReload( initialFilteredColumns );

			// set top bar filters
			g_topFilteringBar.processFiltersAndShowHideMenu();
			g_topFilteringBar.modifyPostsCounter(pagination_info);

		})

	}


	function globalTableFilterinByColumnsReturnFilters() {
		var all_filters = [];

		g_objSpreadsheet.find(g_filterColumnSettingsIcon + '.is_active').each(function () {

			var parent_th = jQuery(this).parents('th');

			const type = parent_th.attr('data-type');

			var data_container = parent_th.find;
			var search_type = parent_th.attr('data-column-search-type');
			if (search_type == 'text') {
				var filter_search_query = parent_th.find(g_columnFilterSearchInput).val().toLowerCase();
				var column_name = parent_th.attr('data-name');

				all_filters.push({ 'type': search_type, 'name': column_name, 'value': filter_search_query, 'column_type': type });
			}
			if (search_type == 'calendar') {

				var column_name = parent_th.attr('data-name');

				if (jQuery(' .' + g_columnFilterDropdownFilterContainerItemClass + ' input[type="radio"]:checked', parent_th).val() == 'custom_range') {

					var dates_range = [];
					dates_range.push(jQuery('#range_date_filter_start', parent_th).val());
					dates_range.push(jQuery('#range_date_filter_end', parent_th).val());

					all_filters.push({ 'type': search_type, 'name': column_name, 'value': dates_range, 'column_type': type });
				} else {
					all_filters.push({ 'type': search_type, 'name': column_name, 'value': jQuery(' .' + g_columnFilterDropdownFilterContainerItemClass + ' input[type="radio"]:checked', parent_th).val(), 'column_type': type });
				}


			}
			if (search_type == 'filter') {

				var check_values = [];
				jQuery(g_columnFilterDropdownFilterContainerClass + ' .' + g_columnFilterDropdownFilterContainerItemClass + ' input[type="checkbox"]:checked', parent_th).each(function () {
					check_values.push(jQuery(this).val())
				})

				var column_name = parent_th.attr('data-name');

				all_filters.push({ 'type': search_type, 'name': column_name, 'value': check_values, 'column_type': type });
			}
		})
		return all_filters;
	}


	// column filter select all action
	function columnFilterSelectAllAction(e, this_pointer) {
		var parent_th = jQuery(e.target).parents('th');
		if (this_pointer.is(':checked')) {
			jQuery('.' + g_columnFilterDropdownFilterContainerItemClass + ' input ', parent_th).prop('checked', true);
		} else {
			jQuery('.' + g_columnFilterDropdownFilterContainerItemClass + ' input ', parent_th).prop('checked', false);
		}
		columnFilterTermFilterAction(e);
	}

	// make columns based term filtering action
	function columnFilterTermFilterAction(e) {
		var parent_th = jQuery(e.target).closest('th');

		jQuery('th[data-name="' + parent_th.attr('data-name') + '"] ' + g_filterColumnSettingsIcon).addClass('is_active');

		var this_col_index = parent_th.index();
		var first_data_row = g_objSpreadsheet.find('tbody').find('tr').first();

		const type = parent_th.attr('data-type');
		const column = parent_th.attr('data-name');

		// custom range patch
		if (jQuery(e.target).attr('type') == 'radio') {
			if (jQuery(e.target).val() == 'custom_range') {
				jQuery('.is_custom_range', parent_th).addClass('is_active');
				return true;
			} else {
				jQuery('.is_custom_range', parent_th).removeClass('is_active');
			}
		}

		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderProcessing);

		var data =
		{
			col_filtering_query: globalTableFilterinByColumnsReturnFilters(),
			post_type: g_objPostTypeSelector.val(),
			rows_per_page: g_objRowsAmountSelector.val(),
			column_name: column,
			type: type,
		};

		g_doublyAdmin.ajaxRequest('make_column_ajax_search', data, function (response) {

			let tableData = response.message.postslist;
			let tableStructure = response.message.table_structure;


			var pagination_info = self.processPagination(response.message.total_count, g_objRowsAmountSelector.val(), response.message.current_page);
			pagination_info['global_counter'] = response.message.global_posts_number;

			tableManipulator = new SheetsPilot_CellProcessing('#new_output_table', tableStructure);
			tableManipulator.fillCellsWithContent(tableData);

			bindAllTableActionsAfterReload( initialFilteredColumns );

			// set top bar filters
			g_topFilteringBar.processFiltersAndShowHideMenu();
			g_topFilteringBar.modifyPostsCounter(pagination_info);

		})
	}

	// search inside column filter
	function columnFilterTermFilterQuickSearch(e) {
		var parent_th = jQuery(e.target).closest('th');
		var current_query = jQuery(e.target).val();

		jQuery(g_columnFilterDropdownFilterContainerClass + ' .' + g_columnFilterDropdownFilterContainerItemClass, parent_th).each(function () {
			var row_text = jQuery(this).find('label').text().trim().toLowerCase();

			if (row_text.indexOf(current_query) !== -1) {
				jQuery(this).show();
			} else {
				jQuery(this).hide();
			}
		})

	}

	// set column custom names
	function setCustomColumnNames() {
		var post_type = g_objPostTypeSelector.val();
		try {
			var current_pt_names = jQuery.parseJSON(localStorage.getItem('uai_column_name_' + post_type));
		} catch (e) {
			var current_pt_names = {};
		}
		// parseJSON can return null for missing or "null" storage; "in" operator requires an object
		if (current_pt_names == null || typeof current_pt_names !== 'object') {
			current_pt_names = {};
		}

		jQuery('.' + g_thColumn).each(function () {
			var column_name = jQuery(this).attr('data-name');

			if (column_name in current_pt_names) {
				jQuery('.' + g_thTitle, this).html(current_pt_names[column_name]);
			}
		})
	}

	// save table width
	function saveTableColumnWidths(drop_col = false) {

		var tableId = g_objPostTypeSelector.val();
		const widths = {};

		const $cells = g_objSpreadsheet.find('thead th').length
			? g_objSpreadsheet.find('thead th')
			: g_objSpreadsheet.find('tr:first td');

		$cells.each(function () {
			if (drop_col != jQuery(this).attr('data-name')) {
				widths[jQuery(this).attr('data-name')] = jQuery(this).outerWidth();
			} else {
				widths[jQuery(this).attr('data-name')] = 100;
			}

		});

		localStorage.setItem('tableWidths_' + tableId, JSON.stringify(widths));
	}

	// restore table width
	function restoreTableColumnWidths() {

		var tableId = g_objPostTypeSelector.val();

		const saved = localStorage.getItem('tableWidths_' + tableId);
		if (!saved) return;

		const widths = JSON.parse(saved);


		jQuery.each(widths, function (index, value) {

			var column_index = g_objSpreadsheet.find('thead tr th[data-name="' + index + '"]').index();

			if (column_index !== -1) {

				g_objSpreadsheet.find('tr').each(function () {

					jQuery(this).children().eq(column_index).css({
						'width': widths[index] + 'px',
						'min-width': widths[index] + 'px',
						// 'max-width': widths[index] + 'px'
					});
				});
			}
		})


	}

	// Set column width
	function setColumnWidth(column_name, width) {


		var column_index = g_objSpreadsheet.find('thead tr th[data-name="' + column_name + '"]').index();

		if (column_index !== -1) {

			g_objSpreadsheet.find('tr').each(function () {

				jQuery(this).children().eq(column_index).css({
					'width': width + 'px',
					'min-width': width + 'px',
					// 'max-width': widths[index] + 'px'
				});
			});
		}

	}

	/**
	 * Save cell rule (prompt for column) via AJAX. Called from Cell Rules panel.
	 * Icon state is updated immediately when dialog closes, before AJAX response.
	 */
	this.saveCellRule = function (data, successCallback) {

		var cellRulesTooltipLabel = (typeof g_ubaiPromptsStrings !== "undefined" && g_ubaiPromptsStrings.cellRules) ? g_ubaiPromptsStrings.cellRules : "AI Column Settings";

		if (typeof g_ubaiCellRules === "undefined" || g_ubaiCellRules === null || g_ubaiCellRules === "") {
			g_ubaiCellRules = {};
		} else if (typeof g_ubaiCellRules === "string") {
			try {
				g_ubaiCellRules = JSON.parse(g_ubaiCellRules);
			} catch (e) {
				g_ubaiCellRules = {};
			}
		}
		if (typeof g_ubaiCellRules !== "object" || g_ubaiCellRules === null) {
			g_ubaiCellRules = {};
		}
		g_ubaiCellRules[data.column] = data.prompt;

		if (data.apply_prompt_on_paste) {
			g_ubaiCellRules[data.column + "__prompt_on_paste"] = "true";
		} else {
			delete g_ubaiCellRules[data.column + "__prompt_on_paste"];
		}
		if (data.auto_apply_response) {
			g_ubaiCellRules[data.column + "__auto_apply_response"] = "true";
		} else {
			delete g_ubaiCellRules[data.column + "__auto_apply_response"];
		}

		if (data.cell_rule_image && typeof data.cell_rule_image === "object") {
			if (data.cell_rule_image.aspect_ratio) {
				g_ubaiCellRules[data.column + "__aspect_ratio"] = data.cell_rule_image.aspect_ratio;
			} else {
				delete g_ubaiCellRules[data.column + "__aspect_ratio"];
			}
			if (data.cell_rule_image.quality) {
				var qSave = String(data.cell_rule_image.quality).toLowerCase();
				if (qSave === "0.5k") qSave = "low";
				else if (qSave === "1k") qSave = "medium";
				else if (qSave === "1.5k" || qSave === "2k") qSave = "high";
				if (qSave === "default") {
					delete g_ubaiCellRules[data.column + "__quality"];
				} else if (qSave === "low" || qSave === "medium" || qSave === "high") {
					g_ubaiCellRules[data.column + "__quality"] = qSave;
				} else {
					delete g_ubaiCellRules[data.column + "__quality"];
				}
			} else {
				delete g_ubaiCellRules[data.column + "__quality"];
			}
			if (data.cell_rule_image.format) {
				var f = String(data.cell_rule_image.format).toLowerCase();
				if (f === "jpg") f = "jpeg";
				if (f === "default") {
					delete g_ubaiCellRules[data.column + "__format"];
				} else if (f === "png" || f === "jpeg" || f === "webp") {
					g_ubaiCellRules[data.column + "__format"] = f;
				} else {
					delete g_ubaiCellRules[data.column + "__format"];
				}
			} else {
				delete g_ubaiCellRules[data.column + "__format"];
			}
			if (data.cell_rule_image.resolution) {
				var res = String(data.cell_rule_image.resolution).toLowerCase();
				if (res === "default") {
					delete g_ubaiCellRules[data.column + "__resolution"];
				} else if (res === "1k" || res === "2k" || res === "3k" || res === "4k") {
					g_ubaiCellRules[data.column + "__resolution"] = res;
				} else {
					delete g_ubaiCellRules[data.column + "__resolution"];
				}
			} else {
				delete g_ubaiCellRules[data.column + "__resolution"];
			}
		}
		delete g_ubaiCellRules[data.column + "__targets"];
		var $icon = g_objPostsEditor.find('th[data-name="' + data.column + '"] .unlimitedai-plugin__ai-column-settings-icon');
		var hasImgMeta = false;
		if (data.cell_rule_image) {
			var img = data.cell_rule_image;
			if (img.aspect_ratio && String(img.aspect_ratio) !== "auto") {
				hasImgMeta = true;
			} else if (img.quality && String(img.quality) !== "default") {
				hasImgMeta = true;
			} else if (img.format && String(img.format) !== "default") {
				hasImgMeta = true;
			} else if (img.resolution && String(img.resolution) !== "default") {
				hasImgMeta = true;
			}
		}
		var hasRulesIndicator = !!(String(data.prompt || "").trim()) || !!hasImgMeta;
		if (hasRulesIndicator) {
			$icon.addClass("unlimitedai-plugin__ai-column-settings-icon--has-rules");
			var full = String(data.prompt || "").trim();
			if (hasImgMeta && data.cell_rule_image) {
				full = (full ? full + "\n\n" : "")
					+ "aspect_ratio: " + String(data.cell_rule_image.aspect_ratio || "")
					+ "\nquality: " + String(data.cell_rule_image.quality || "")
					+ "\nformat: " + String(data.cell_rule_image.format || "");
			}
			$icon.attr("data-ubai-column-rule-full", escapeAttrForTooltip(full));
			$icon.attr("data-title", "");
			$icon.removeAttr("title");
		} else {
			$icon.removeClass("unlimitedai-plugin__ai-column-settings-icon--has-rules");
			$icon.removeAttr("data-ubai-column-rule-full");
			$icon.attr("data-title", cellRulesTooltipLabel);
			$icon.removeAttr("title");
		}

		// prompt on paste
		$icon.attr("data-apply-prompt-on-paste", data.apply_prompt_on_paste);
		$icon.attr("data-auto-apply-response", data.auto_apply_response);

		if (typeof updateApplyPromptButtonState === 'function') {
			updateApplyPromptButtonState();
		}
		var $cellRulesInput = g_objPostsEditor.find("#g_ubaiCellRules");
		if ($cellRulesInput.length) {
			$cellRulesInput.val(JSON.stringify(g_ubaiCellRules));
		}

		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderNoIndex);
		g_doublyAdmin.ajaxRequest("save_cell_rule", data, function (response) {
			if (response && response.success && data.column) {
				var $sidebarPanel = g_objPostsEditor.find('#ubai_sidebar_image_panel');
				if ($sidebarPanel.length && window.ubaiPrompts && typeof window.ubaiPrompts.updateImageDefaultOptionLabels === 'function') {
					window.ubaiPrompts.updateImageDefaultOptionLabels($sidebarPanel, data.column, true);
				}
				var $cellRulesPanel = g_objPostsEditor.find('#ubai_cell_rules_panel .ubai-cell-rules-panel__image-options');
				if ($cellRulesPanel.length && window.ubaiPrompts && typeof window.ubaiPrompts.updateImageDefaultOptionLabels === 'function') {
					window.ubaiPrompts.updateImageDefaultOptionLabels($cellRulesPanel, data.column, false);
				}
			}
			if (typeof successCallback === "function") {
				successCallback(response);
			}
		});
	};

	// bulk editor quick search
	function bulkEditorQuickSearch(e) {

		var this_pointer = e.target;
		var parent_editor = jQuery('.unlimitedai-plugin__drawer-bulk-edit__list-container .category_cell_td_list');

		var current_val = jQuery(g_bulkEditSearchInput).val().toLowerCase();

		if (current_val.length > 0) {
			parent_editor.addClass('is_tax_searching');
		} else {
			parent_editor.removeClass('is_tax_searching');
		}

		jQuery('.unlimitedai-plugin__drawer-bulk-edit__list-container .drawer_searchable_item').each(function () {
			var this_text = jQuery(this).text().toLowerCase();
			if (this_text.indexOf(current_val) === -1) {
				jQuery(this).hide();
			} else {
				jQuery(this).show();
			}
		})
	}

	// bulk editor quick search
	function columnsOrdersOrderingQuickSearch(e) {

		var this_pointer = e.target;
		var current_val = jQuery(g_columnsOrderingSearchInput).val().toLowerCase();
		var parent_editor = jQuery(g_ubaiPopupFieldsList);


		jQuery(g_columnSelectorPopupItemSelector, parent_editor).each(function () {
			var this_text = jQuery(this).text().toLowerCase();
			if (this_text.indexOf(current_val) === -1) {
				jQuery(this).hide();
			} else {
				jQuery(this).show();
			}
		})
	}

	// apply drawer bulk action
	function applyDrawerBulkActions(e) {
		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderProcessing);

		if (bulkActionDataType == 'text') {
			var this_val = jQuery('.' + g_drawerBulkEditSelectClass).val();
			bulkActionValues = [];
			bulkActionValues.push(this_val);
		}

		if (bulkActionDataType == 'category') {
			var this_val = bulkActionValues = jQuery('.sidebar_category_container input[type="checkbox"]:checked').map(function () {
				return jQuery(this).val(); // Get the value of each checked item
			}).get();
		}

		if (bulkActionDataType == 'tag') {
			var this_val = bulkActionValues = jQuery('.unlimitedai-plugin__drawer-bulk-edit__list-container input[type="checkbox"]:checked').map(function () {
				return jQuery(this).val(); // Get the value of each checked item
			}).get();
		}
		if (bulkActionDataType == 'multicheck') {
			var this_val = bulkActionValues = jQuery('.unlimitedai-plugin__drawer-bulk-edit__list-container input[type="checkbox"]:checked').map(function () {
				return jQuery(this).val(); // Get the value of each checked item
			}).get();
		}

		var data =
		{
			ids: bulkActionIDs,
			bulkActionDataType: bulkActionDataType,
			bulkActionColumnName: bulkActionColumnName,
			bulkActionValues: bulkActionValues
		};
		classSheetsPilotDrawer.onDrawerClose();
		g_doublyAdmin.ajaxRequest('bulk_action', data, function (response) {
			jQuery.each(bulkActionIDs, function (index, row_id) {
				tableManipulator.onCellContentSetUp(row_id, bulkActionColumnName, this_val);
			})


		});
	}

	/**
	 * Inject automate-workflow toolbar + plan-panel styles (JS-only; no PHP/CSS file changes).
	 */
	function injectAutomateWorkflowStyles() {
		if (document.getElementById('ubai-automate-workflow-styles')) {
			return;
		}
		var css = ''
			+ '#unlimitedai-plugin .unlimitedai-plugin__automate-workflow-status{display:none !important;}'
			+ '#new_output_table.ubai-automate-workflow-blocking{pointer-events:none !important;cursor:not-allowed;}'
			+ '#unlimitedai-plugin .unlimitedai-plugin__automate-workflow-status{display:none;align-items:center;gap:8px;}'
			+ '#unlimitedai-plugin .unlimitedai-plugin__automate-workflow-status.is-active{display:flex;}'
			+ '#unlimitedai-plugin .unlimitedai-plugin__automate-workflow-status__label{font-size:12px;color:#525252;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}'
			+ '#unlimitedai-plugin .unlimitedai-plugin__automate-workflow-stop-btn{height:30px;padding:0 12px;font-size:12px;font-weight:500;}'
			+ '#unlimitedai-plugin .unlimitedai-plugin__bulk-edit__wrapper.is-automate-running #ubai_automate_workflow_btn{display:none !important;}'
			+ '#unlimitedai-plugin .unlimitedai-plugin__sidebar.is-workflow-plan #ubai_sidebar_body{display:flex;flex-direction:column;overflow:hidden;}'
			+ '#unlimitedai-plugin .unlimitedai-plugin__sidebar.is-workflow-plan #ubai_sidebar_body > .unlimitedai-plugin__sidebar-content{display:none !important;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel{display:none;flex-direction:column;flex:1;min-height:0;background:#fff;border-top:1px solid #f3f4f6;}'
			+ '#unlimitedai-plugin .unlimitedai-plugin__sidebar.is-workflow-plan #ubai_workflow_plan_panel{display:flex;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__header{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid #f3f4f6;background:#fafafa;flex-shrink:0;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__title{font-weight:600;color:#111827;font-size:13px;display:flex;align-items:center;gap:7px;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__badge{display:none;min-width:18px;height:18px;border-radius:999px;background:#2271b1;color:#fff;font-size:11px;font-weight:600;line-height:18px;text-align:center;padding:0 5px;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__close{border:0;background:none;cursor:pointer;color:#6b7280;font-size:15px;line-height:1;padding:4px;border-radius:6px;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__close:hover{background:#f3f4f6;color:#111827;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__actions{display:flex;align-items:center;gap:8px;padding:12px 14px;border-bottom:1px solid #f3f4f6;flex-shrink:0;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__start{flex:1;height:34px;border:0;border-radius:8px;background:#2271b1;color:#fff;font-size:13px;font-weight:600;cursor:pointer;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__start:hover{background:#135e96;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__start:disabled{opacity:.55;cursor:default;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__stop{display:none;height:34px;padding:0 14px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;font-size:12px;font-weight:600;cursor:pointer;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__stop:hover{background:#f9fafb;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel.is-running .ubai-wfp__start{display:none;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel.is-running .ubai-wfp__stop{display:inline-flex;align-items:center;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__hint{padding:0 14px 10px;font-size:12px;color:#6b7280;flex-shrink:0;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__list{overflow-y:auto;padding:6px;flex:1;min-height:0;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__empty{padding:18px 12px;text-align:center;color:#9ca3af;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__item{display:flex;align-items:center;gap:9px;padding:8px 9px;border-radius:8px;margin-bottom:2px;cursor:pointer;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__item:hover{background:#eff6ff;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__item--active{background:#eff6ff;box-shadow:inset 0 0 0 2px #2271b1;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__item--done{opacity:.92;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__status{flex:none;width:auto;height:16px;display:flex;align-items:center;gap:6px;justify-content:flex-start;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__dot,'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__spinner,'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__check{flex:none;width:16px;height:16px;display:flex;align-items:center;justify-content:center;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__spinner{width:13px;height:13px;border:2px solid #e5e7eb;border-top-color:#2271b1;border-radius:50%;animation:ubaiWfpSpin .8s linear infinite;}'
			+ '@keyframes ubaiWfpSpin{to{transform:rotate(360deg);}}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__dot{width:9px;height:9px;border-radius:50%;background:#d1d5db;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__check{color:#16a34a;font-size:14px;font-weight:700;line-height:1;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__state-text{font-size:11px;font-weight:700;color:#6b7280;white-space:nowrap;line-height:1;display:inline-block;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__item--active .ubai-wfp__state-text{color:#2271b1;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__item--done .ubai-wfp__state-text{color:#16a34a;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__item--skipped .ubai-wfp__state-text{color:#9ca3af;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__item--error .ubai-wfp__state-text{color:#dc2626;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__meta{flex:1;min-width:0;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__label{font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}'
			+ '#unlimitedai-plugin #ubai_workflow_plan_panel .ubai-wfp__sub{color:#6b7280;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}';
		jQuery('<style id="ubai-automate-workflow-styles">' + css + '</style>').appendTo('head');
	}

	/**
	 * Create status label + Stop button in the bulk-edit toolbar, and the sidebar plan panel.
	 */
	function initAutomateWorkflowUI() {
		injectAutomateWorkflowStyles();
		var $wrapper = g_objPostsEditor.find('.unlimitedai-plugin__bulk-edit__wrapper');
		if ($wrapper.length && !$wrapper.find('.unlimitedai-plugin__automate-workflow-status').length) {
			var $status = jQuery(
				'<span class="unlimitedai-plugin__automate-workflow-status" aria-live="polite">'
				+ '<span class="unlimitedai-plugin__automate-workflow-status__label"></span>'
				+ '<button type="button" id="ubai_automate_workflow_stop_btn" class="unlimitedai-plugin__automate-workflow-stop-btn unlimitedai-plugin__text-btn">Stop</button>'
				+ '</span>'
			);
			$wrapper.find('#ubai_automate_workflow_btn').after($status);
		}
		ensureAutomateWorkflowPlanPanel();
	}

	function ensureAutomateWorkflowPlanPanel() {
		var $sidebarBody = g_objPostsEditor.find('#ubai_sidebar_body');
		if (!$sidebarBody.length || $sidebarBody.find('#ubai_workflow_plan_panel').length) {
			return $sidebarBody.find('#ubai_workflow_plan_panel');
		}
		var $panel = jQuery(
			'<div id="ubai_workflow_plan_panel" role="region" aria-label="Automate workflow plan">'
			+ '<div class="ubai-wfp__header">'
			+ '<span class="ubai-wfp__title">Workflow plan <span class="ubai-wfp__badge"></span></span>'
			+ '<button type="button" class="ubai-wfp__close" id="ubai_workflow_plan_close_btn" aria-label="Close">&#10005;</button>'
			+ '</div>'
			+ '<div class="ubai-wfp__actions">'
			+ '<button type="button" class="ubai-wfp__start" id="ubai_workflow_plan_start_btn">Start</button>'
			+ '<button type="button" class="ubai-wfp__stop" id="ubai_workflow_plan_stop_btn">Stop</button>'
			+ '</div>'
			+ '<div class="ubai-wfp__hint">Steps run left-to-right by column order. Press Start to begin.</div>'
			+ '<div class="ubai-wfp__list"></div>'
			+ '</div>'
		);
		$sidebarBody.append($panel);
		return $panel;
	}

	function expandAiSidebarForWorkflowPlan() {
		if (!g_objSideBar || !g_objSideBar.length) {
			return;
		}
		if (!g_objSideBar.hasClass(g_classActive)) {
			g_objSideBar.addClass(g_classActive);
			var $tip = g_objSideBarToggleBtn && g_objSideBarToggleBtn.length
				? g_objSideBarToggleBtn.find('.has-tooltip')
				: jQuery();
			if ($tip.length) {
				var titleExpanded = $tip.attr('data-title-expanded');
				if (titleExpanded) {
					$tip.attr('data-title', titleExpanded);
				}
				if (g_objSideBarToggleBtn && g_objSideBarToggleBtn.length) {
					g_objSideBarToggleBtn.attr(
						'aria-label',
						titleExpanded || sheetspilot.editor.collapse_ai_sidebar
					);
				}
			}
		}
	}

	function showAutomateWorkflowPlanPanel() {
		ensureAutomateWorkflowPlanPanel();
		expandAiSidebarForWorkflowPlan();
		if (g_objSideBar && g_objSideBar.length) {
			g_objSideBar.addClass('is-workflow-plan');
		}
		renderAutomateWorkflowPlanPanel();
	}

	function hideAutomateWorkflowPlanPanel() {
		if (g_objSideBar && g_objSideBar.length) {
			g_objSideBar.removeClass('is-workflow-plan');
		}
		var $panel = g_objPostsEditor.find('#ubai_workflow_plan_panel');
		$panel.removeClass('is-running');
		$panel.find('#ubai_workflow_plan_start_btn').prop('disabled', false);
	}

	function getAutomateWorkflowPlanItemKey(item) {
		if (!item) {
			return '';
		}
		return String(item.postId) + ':' + String(item.columnIndex);
	}

	function renderAutomateWorkflowPlanPanel() {
		var $panel = ensureAutomateWorkflowPlanPanel();
		if (!$panel.length) {
			return;
		}
		var queue = g_automateWorkflow.queue || [];
		var $list = $panel.find('.ubai-wfp__list');
		var $badge = $panel.find('.ubai-wfp__badge');
		var $start = $panel.find('#ubai_workflow_plan_start_btn');

		$list.empty();
		if (!queue.length) {
			$list.append(jQuery('<div class="ubai-wfp__empty"></div>').text('Nothing to automate for the selected rows.'));
			$badge.hide().text('');
			$start.prop('disabled', true);
			$panel.removeClass('is-running');
			return;
		}

		$badge.text(String(queue.length)).show();
		$start.prop('disabled', !!g_automateWorkflow.running);
		if (g_automateWorkflow.running) {
			$panel.addClass('is-running');
		} else {
			$panel.removeClass('is-running');
		}

		queue.forEach(function (item, index) {
			var state = item.planState || 'pending';
			var statusLabel = 'Pending';
			var statusHtml = '<span class="ubai-wfp__dot"></span><span class="ubai-wfp__state-text">' + statusLabel + '</span>';
			if (state === 'active') {
				statusLabel = 'Running';
				statusHtml = '<span class="ubai-wfp__spinner" aria-hidden="true"></span><span class="ubai-wfp__state-text">' + statusLabel + '</span>';
			} else if (state === 'done') {
				statusLabel = 'Done';
				statusHtml = '<span class="ubai-wfp__check" aria-label="Done">✓</span><span class="ubai-wfp__state-text">' + statusLabel + '</span>';
			} else if (state === 'skipped') {
				statusLabel = 'Skipped';
				statusHtml = '<span class="ubai-wfp__check" style="color:#9ca3af;" aria-label="Skipped">–</span><span class="ubai-wfp__state-text">' + statusLabel + '</span>';
			} else if (state === 'error') {
				statusLabel = 'Error';
				statusHtml = '<span class="ubai-wfp__check" style="color:#dc2626;" aria-label="Error">!</span><span class="ubai-wfp__state-text">' + statusLabel + '</span>';
			}

			var $item = jQuery(
				'<div class="ubai-wfp__item ubai-wfp__item--' + state + '" data-index="' + index + '" data-key="' + getAutomateWorkflowPlanItemKey(item) + '">'
				+ '<span class="ubai-wfp__status">' + statusHtml + '</span>'
				+ '<div class="ubai-wfp__meta">'
				+ '<div class="ubai-wfp__label"></div>'
				+ '<div class="ubai-wfp__sub"></div>'
				+ '</div>'
				+ '</div>'
			);
			$item.find('.ubai-wfp__label').text(item.columnTitle || item.column || 'Cell');
			$item.find('.ubai-wfp__sub').text(item.rowTitle || ('Post #' + item.postId));
			$list.append($item);
		});
	}

	function markAutomateWorkflowPlanItemState(index, state) {
		if (!g_automateWorkflow.queue || !g_automateWorkflow.queue[index]) {
			return;
		}
		g_automateWorkflow.queue[index].planState = state || 'pending';
		renderAutomateWorkflowPlanPanel();
	}

	function showAutomateWorkflowRunningUI(initialLabel) {
		var $wrapper = g_objPostsEditor.find('.unlimitedai-plugin__bulk-edit__wrapper');
		$wrapper.addClass('is-automate-running');
		var $status = $wrapper.find('.unlimitedai-plugin__automate-workflow-status');
		$status.addClass('is-active');
		updateAutomateWorkflowStatusLabel(initialLabel || 'Starting…');
		var $panel = ensureAutomateWorkflowPlanPanel();
		$panel.addClass('is-running');
		$panel.find('#ubai_workflow_plan_start_btn').prop('disabled', true);
	}

	function hideAutomateWorkflowRunningUI() {
		var $wrapper = g_objPostsEditor.find('.unlimitedai-plugin__bulk-edit__wrapper');
		$wrapper.removeClass('is-automate-running');
		$wrapper.find('.unlimitedai-plugin__automate-workflow-status').removeClass('is-active');
		updateAutomateWorkflowStatusLabel('');
		var $panel = g_objPostsEditor.find('#ubai_workflow_plan_panel');
		$panel.removeClass('is-running');
		$panel.find('#ubai_workflow_plan_start_btn').prop('disabled', !((g_automateWorkflow.queue || []).length));
	}

	function updateAutomateWorkflowStatusLabel(text) {
		g_objPostsEditor.find('.unlimitedai-plugin__automate-workflow-status__label').text(text || '');
	}

	function getAutomateWorkflowRowTitle(postId) {
		var $row = tableManipulator.$table.find('tr[data-id="' + postId + '"]').first();
		if (!$row.length) {
			return 'Row #' + postId;
		}
		var title = $row.find('.editor_container[data-column="post_title"] .visual_part').text().replace(/\s+/g, ' ').trim();
		return title || ('Row #' + postId);
	}

	/**
	 * Debug helper for workflow plan suitability (open browser console).
	 */
	function logAutomateWorkflowDebug(label, data) {
		if (typeof console !== 'undefined' && console.log) {
			console.log('[AutomateWorkflow]', label, data);
		}
	}

	/**
	 * Resolve a workflow cell by column name (td data-col may differ from thead th.index()).
	 */
	function getAutomateWorkflowCellTdByColumn(postId, columnName) {
		if (!tableManipulator || postId == null || !columnName) {
			return jQuery();
		}
		if (typeof tableManipulator.getCellFromApplyPromptTable === 'function') {
			return tableManipulator.getCellFromApplyPromptTable({
				isSelected: true,
				postId: postId,
				column: columnName
			});
		}
		var $row = tableManipulator.$table.find('tr[data-id="' + postId + '"]').first();
		if (!$row.length) {
			return jQuery();
		}
		return $row.find(tableManipulator.g_editorContainer + '[data-column="' + columnName + '"]').closest('td').first();
	}

	/**
	 * td data-col for sorting / snapshots (from a real row cell, not thead index).
	 */
	function resolveAutomateWorkflowColumnIndex(postId, columnName) {
		var $td = getAutomateWorkflowCellTdByColumn(postId, columnName);
		if ($td.length && $td.data('col') != null) {
			return Number($td.data('col'));
		}
		var $th = jQuery('#new_output_table thead th[data-name="' + columnName + '"]').first();
		return $th.length ? $th.index() : null;
	}

	function getAutomatableColumnsForWorkflow() {
		var cols = [];
		if (!columnsListGlobal || !columnsListGlobal.length) {
			return cols;
		}
		var samplePostId = null;
		if (tableManipulator && tableManipulator.$table) {
			var $sampleRow = tableManipulator.$table.find('tbody tr[data-id]').first();
			if ($sampleRow.length) {
				samplePostId = $sampleRow.data('id');
			}
		}
		jQuery('#new_output_table thead th[data-name]').each(function () {
			var name = jQuery(this).attr('data-name') || jQuery(this).data('name');
			if (!name || name === 'bulk') {
				return;
			}
			var hasRules = typeof ubaiColumnHasSavedCellRules === 'function' && ubaiColumnHasSavedCellRules(name);
			if (!hasRules) {
				logAutomateWorkflowDebug('column skipped (no AI rules)', {
					column: name,
					hasRules: false
				});
				return;
			}
			var colDef = columnsListGlobal.find(function (c) { return c && c.name === name; });
			var colType = colDef && colDef.type ? String(colDef.type) : '';
			if (colType === 'acf_gallery' || colType === 'acf_woo_gallery') {
				logAutomateWorkflowDebug('column skipped (gallery type)', {
					column: name,
					columnType: colType
				});
				return;
			}
			var thIndex = jQuery(this).index();
			var colIdx = samplePostId != null ? resolveAutomateWorkflowColumnIndex(samplePostId, name) : thIndex;
			if (colIdx == null) {
				colIdx = thIndex;
			}
			var title = jQuery(this).find('.unlimitedai-plugin__th-title').text().replace(/\s+/g, ' ').trim() || name;
			logAutomateWorkflowDebug('column included in workflow', {
				column: name,
				columnIndex: colIdx,
				columnOrder: thIndex,
				columnTitle: title,
				columnType: colType,
				hasRules: true
			});
			cols.push({
				column: name,
				columnIndex: colIdx,
				columnOrder: thIndex,
				columnTitle: title,
				columnType: colType
			});
		});
		return cols;
	}

	/**
	 * True when the cell has no user content (workflow should only fill empty cells).
	 */
	function isAutomateWorkflowCellEmpty($td) {
		if (!$td || !$td.length || !tableManipulator) {
			logAutomateWorkflowDebug('cell empty check', {
				isEmpty: false,
				reason: 'missing-td-or-tableManipulator'
			});
			return false;
		}

		var $container = $td.find(tableManipulator.g_editorContainer).first();
		var postId = $td.closest('tr').data('id') || $td.data('row');
		var colIdx = $td.data('col');
		var cellType = String($container.data('type') || $container.attr('data-type') || '');
		var column = String($container.data('column') || $container.attr('data-column') || '');

		function done(isEmpty, reason, extra) {
			var storedVal = $container.length ? ($container.attr('data-value') !== undefined ? $container.attr('data-value') : $container.data('value')) : null;
			var $visual = $container.length ? $container.find(tableManipulator.g_visualPart) : jQuery();
			logAutomateWorkflowDebug('cell empty check', Object.assign({
				postId: postId,
				colIdx: colIdx,
				column: column,
				cellType: cellType,
				isEmpty: isEmpty,
				reason: reason,
				dataValue: storedVal,
				dataValueType: typeof storedVal,
				dataValueString: storedVal === null || storedVal === undefined ? '' : String(storedVal).substring(0, 500),
				visualText: $visual.length ? $visual.text().replace(/\u00a0/g, ' ').trim() : '',
				visualHtml: $visual.length ? String($visual.html() || '').substring(0, 500) : ''
			}, extra || {}));
			return isEmpty;
		}

		if (!$container.length) {
			return done(true, 'no-editor-container');
		}

		if (cellType === 'image' || column === 'post_image') {
			var $img = $container.find(tableManipulator.g_ubaiFeaturedImageUploader).first();
			if (!$img.length) {
				$img = $container.find(tableManipulator.g_visualPart + ' img').first();
			}
			if (!$img.length) {
				return done(true, 'image-no-img-element');
			}
			if ($img.hasClass(tableManipulator.g_isPlaceholderNoIndex)) {
				return done(true, 'image-placeholder-class');
			}
			var imgSrc = String($img.attr('src') || '').toLowerCase();
			if (imgSrc.indexOf('placeholder.png') !== -1) {
				return done(true, 'image-placeholder-src', { imgSrc: imgSrc });
			}
			var attachId = String($img.attr('data-id') || '').trim();
			if (attachId && attachId !== '0') {
				return done(false, 'image-has-attachment-id', { attachId: attachId });
			}
			var imageDataVal = String($container.attr('data-value') || $container.data('value') || '').trim();
			return done(imageDataVal === '' || imageDataVal === '0', 'image-data-value', { imageDataVal: imageDataVal });
		}

		if (cellType === 'tag') {
			var $tagSel = $container.find(tableManipulator.g_tagSelectEditorInput);
			if ($tagSel.length) {
				var tagVal = $tagSel.val();
				if (Array.isArray(tagVal)) {
					var tagEmpty = tagVal.filter(function (v) {
						return v !== null && v !== undefined && String(v).trim() !== '';
					}).length === 0;
					return done(tagEmpty, 'tag-array', { tagVal: tagVal });
				}
				var tagScalarEmpty = tagVal === null || tagVal === undefined || String(tagVal).trim() === '';
				return done(tagScalarEmpty, 'tag-scalar', { tagVal: tagVal });
			}
		}

		if (cellType === 'taxonomy') {
			var taxChecked = $container.find('.category_cell_td_list input[type="checkbox"]:checked').length;
			if (taxChecked > 0) {
				return done(false, 'taxonomy-has-checked', { taxChecked: taxChecked });
			}
			var $taxHidden = $container.find('.ubai_tax_value');
			if ($taxHidden.length) {
				var taxParts = String($taxHidden.val() || '').split(',').map(function (s) {
					return String(s).trim();
				}).filter(Boolean);
				return done(taxParts.length === 0, 'taxonomy-hidden-value', { taxValue: $taxHidden.val() });
			}
			return done(true, 'taxonomy-no-selection');
		}

		if (cellType === 'select' || cellType === 'acf_select') {
			var selectLabel = $container.find(tableManipulator.g_visualPart).text().replace(/\u00a0/g, ' ').trim();
			var selectDataVal = $container.attr('data-value');
			if (selectDataVal === undefined) {
				selectDataVal = $container.data('value');
			}
			// For select2 / acf_select the underlying data-value can be an object, so
			// don't trust it for emptiness. Use the rendered label instead.
			if (selectLabel === '') {
				return done(true, 'select-empty-label', { selectDataVal: selectDataVal });
			}
			var normalized = String(selectLabel).toLowerCase().trim();
			// Common placeholder-ish values.
			if (normalized === '-' || normalized === '—' || normalized === '–' || normalized === 'none' || normalized === 'null') {
				return done(true, 'select-placeholder-dash', { selectLabel: selectLabel, selectDataVal: selectDataVal });
			}
			if (/^(select|choose)\b/.test(normalized)) {
				return done(true, 'select-placeholder-text', { selectLabel: selectLabel, selectDataVal: selectDataVal });
			}
			return done(false, 'select-has-value', { selectLabel: selectLabel, selectDataVal: selectDataVal });
		}

		// Repeater cells: the UI may show "0 items" which currently looks like "non-empty"
		// to the generic visual-text check. For workflow planning, treat counters with
		// 0 as empty so the repeater steps still appear in the plan list.
		if (tableManipulator && typeof tableManipulator.isACFRepeaterCellType === 'function' && tableManipulator.isACFRepeaterCellType(cellType)) {
			var $counter = $container.find('.countable_cell_inner_counter').first();
			if (!$counter.length) {
				$counter = $td.find('.countable_cell_inner_counter').first();
			}
			if ($counter.length) {
				var counterText = String($counter.text() || '').replace(/\u00a0/g, ' ').trim();
				var m = counterText.match(/(\d+)/);
				var count = m ? parseInt(m[1], 10) : 0;
				var counterEmpty = !isNaN(count) ? count === 0 : true;
				return done(counterEmpty, 'repeater-counter', { counterText: counterText, count: count });
			}
			// Fallback: if we have a stored value and it looks empty, treat as empty.
			var storedRepeaterVal = $container.attr('data-value');
			if (storedRepeaterVal === undefined) {
				storedRepeaterVal = $container.data('value');
			}
			if (storedRepeaterVal === null || storedRepeaterVal === undefined) {
				return done(true, 'repeater-no-stored-value');
			}
			var storedStr2 = String(storedRepeaterVal).replace(/\u00a0/g, ' ').trim();
			if (storedStr2 === '' || storedStr2 === '0') {
				return done(true, 'repeater-stored-empty', { storedRepeaterVal: storedRepeaterVal });
			}
			// Treat "[]" as empty if AI stored JSON array.
			if (storedStr2 === '[]') {
				return done(true, 'repeater-stored-empty-array', { storedRepeaterVal: storedRepeaterVal });
			}
			// Otherwise fall through to the generic visual/text check.
		}

		var storedVal = $container.attr('data-value');
		if (storedVal === undefined) {
			storedVal = $container.data('value');
		}
		if (storedVal !== null && storedVal !== undefined) {
			var storedStr = String(storedVal).replace(/\u00a0/g, ' ').trim();
			var normalizedContentText = '';
			// New rows (especially post_content / textarea) may already contain empty
			// editor scaffolding like <p></p> or Gutenberg comments. Treat markup-only
			// values as empty so Content still appears in the workflow plan.
			if (column === 'post_content' || cellType === 'textarea' || cellType === 'calendar') {
				normalizedContentText = storedStr
					.replace(/<!--[\s\S]*?-->/g, ' ')
					.replace(/<[^>]*>/g, ' ')
					.replace(/&nbsp;/gi, ' ')
					.replace(/\u00a0/g, ' ')
					.replace(/\s+/g, ' ')
					.trim();
				if (normalizedContentText === '') {
					storedStr = '';
				}
			}
			if (storedStr !== '' && storedStr !== '0') {
				if (storedStr.indexOf('<img') === -1) {
					return done(false, 'stored-data-value-nonempty', {
						storedStr: storedStr.substring(0, 500),
						normalizedContentText: normalizedContentText
					});
				}
				if (storedStr.indexOf('is_placeholder') === -1 && storedStr.toLowerCase().indexOf('placeholder.png') === -1) {
					var storedIdMatch = storedStr.match(/data-id\s*=\s*["'](\d+)["']/i);
					if (storedIdMatch && storedIdMatch[1] && storedIdMatch[1] !== '0') {
						return done(false, 'stored-html-has-image-id', { storedIdMatch: storedIdMatch[1] });
					}
				}
			}
		}

		var $visual = $container.find(tableManipulator.g_visualPart);
		if (!$visual.length) {
			return done(true, 'no-visual-part');
		}

		// `post_content` may render as `cellType === "text"` with HTML scaffolding
		// (e.g. "<p></p>" / Gutenberg comments). Treat markup-only as empty.
		if (column === 'post_content') {
			var visualRaw = String($visual.html() || $visual.text() || '');
			var normalizedVisual = visualRaw
				.replace(/<!--[\s\S]*?-->/g, ' ')
				.replace(/<[^>]*>/g, ' ')
				.replace(/&nbsp;/gi, ' ')
				.replace(/\u00a0/g, ' ')
				.replace(/\s+/g, ' ')
				.trim();
			if (normalizedVisual === '') {
				return done(true, 'post_content-markup-only-empty', {
					visualRaw: visualRaw.substring(0, 500),
					normalizedVisual: normalizedVisual
				});
			}
		}

		var visualText = $visual.text().replace(/\u00a0/g, ' ').trim();
		if (visualText !== '') {
			return done(false, 'visual-text-nonempty', { visualText: visualText });
		}

		var visualHtml = String($visual.html() || '').replace(/<[^>]*>/g, '').replace(/\u00a0/g, ' ').trim();
		return done(visualHtml === '', 'visual-html-fallback', { visualHtml: visualHtml });
	}

	/**
	 * The Slug (post_name) column has no AI cell rules, so it's handled as a
	 * special workflow step: its value is regenerated from the post title
	 * whenever the Slug column is present and visible — inserted in visual
	 * left-to-right column order (not always first).
	 */
	function buildAutomateWorkflowSlugItem(postId, rowTitle) {
		var $th = jQuery('#new_output_table thead th[data-name="post_name"]').first();
		if (!$th.length || $th.is(':hidden')) {
			return null;
		}
		var $td = getAutomateWorkflowCellTdByColumn(postId, 'post_name');
		if (!$td.length || $td.hasClass('is_for_pro') || $td.is(':hidden')) {
			return null;
		}
		var colIdx = $td.data('col');
		var title = $th.find('.unlimitedai-plugin__th-title').text().replace(/\s+/g, ' ').trim() || 'Slug';
		return {
			postId: String(postId),
			column: 'post_name',
			columnIndex: colIdx != null ? Number(colIdx) : $th.index(),
			columnOrder: $th.index(),
			columnTitle: title,
			columnType: 'text',
			rowTitle: rowTitle,
			isSlug: true,
			planState: 'pending'
		};
	}

	/**
	 * Build the ordered plan for selected rows.
	 * Order = selected-row order × visible table columns left-to-right
	 * (slug in its natural column position, then rule-backed empty cells).
	 */
	function buildAutomateWorkflowQueue(selectedIds) {
		var queue = [];
		var columns = getAutomatableColumnsForWorkflow();
		logAutomateWorkflowDebug('build queue start', {
			selectedIds: selectedIds,
			automatableColumnCount: columns.length,
			automatableColumns: columns.map(function (c) { return c.column; })
		});
		jQuery.each(selectedIds, function (_, postId) {
			var rowTitle = getAutomateWorkflowRowTitle(postId);
			var rowSteps = [];

			var slugItem = buildAutomateWorkflowSlugItem(postId, rowTitle);
			if (slugItem) {
				rowSteps.push(slugItem);
			}

			jQuery.each(columns, function (__, col) {
				if (col.column === 'post_name') {
					return;
				}
				var $td = getAutomateWorkflowCellTdByColumn(postId, col.column);
				var colIdx = $td.length && $td.data('col') != null
					? Number($td.data('col'))
					: resolveAutomateWorkflowColumnIndex(postId, col.column);
				if (!$td.length) {
					logAutomateWorkflowDebug('queue step skipped', {
						postId: postId,
						column: col.column,
						reason: 'td-not-found',
						columnIndex: colIdx
					});
					return;
				}
				if ($td.hasClass('is_for_pro')) {
					logAutomateWorkflowDebug('queue step skipped', {
						postId: postId,
						column: col.column,
						reason: 'is_for_pro'
					});
					return;
				}
				if ($td.is(':hidden')) {
					logAutomateWorkflowDebug('queue step skipped', {
						postId: postId,
						column: col.column,
						reason: 'td-hidden'
					});
					return;
				}
				if (!isAutomateWorkflowCellEmpty($td)) {
					logAutomateWorkflowDebug('queue step skipped', {
						postId: postId,
						column: col.column,
						reason: 'cell-not-empty'
					});
					return;
				}
				logAutomateWorkflowDebug('queue step added', {
					postId: postId,
					column: col.column,
					columnIndex: colIdx,
					columnOrder: col.columnOrder
				});
				rowSteps.push({
					postId: String(postId),
					column: col.column,
					columnIndex: colIdx != null ? colIdx : col.columnIndex,
					columnOrder: col.columnOrder != null ? col.columnOrder : colIdx,
					columnTitle: col.columnTitle,
					columnType: col.columnType,
					rowTitle: rowTitle,
					planState: 'pending'
				});
			});

			// Left-to-right by thead position (data-col order can differ from visual columns).
			rowSteps.sort(function (a, b) {
				return Number(a.columnOrder) - Number(b.columnOrder);
			});
			Array.prototype.push.apply(queue, rowSteps);
		});
		logAutomateWorkflowDebug('build queue done', {
			stepCount: queue.length,
			steps: queue.map(function (s) {
				return s.column + ' (order:' + s.columnOrder + ', col:' + s.columnIndex + ')';
			})
		});
		return queue;
	}

	function getAutomateWorkflowCellTd(item) {
		if (!item || !tableManipulator) {
			return jQuery();
		}
		return tableManipulator.getCellFromApplyPromptTable({
			isSelected: true,
			postId: item.postId,
			columnIndex: item.columnIndex,
			column: item.column
		});
	}

	function buildWorkflowApplyPromptPayload(item) {
		var $td = getAutomateWorkflowCellTd(item);
		if (!$td.length) {
			return null;
		}
		tableManipulator.selectCellFromApplyPromptTable({
			isSelected: true,
			postId: item.postId,
			columnIndex: item.columnIndex,
			column: item.column
		});
		var tableData = tableManipulator.getTableData();
		if (!tableData || !tableData.isSelected) {
			return null;
		}
		tableData.post_type = g_objPostTypeSelector.val() || '';
		tableData.context_menu_action = 'apply_column_rules';
		tableData.include_rules = true;
		delete tableData.image_settings;

		var isImageCell = tableData.cellType === 'image' || tableData.column === 'post_image';
		if (isImageCell) {
			tableData.image_action = 'create';
			attachCellRulesToTableData(tableData);
		} else {
			delete tableData.image_action;
			attachCellRulesToTableData(tableData);
		}

		return {
			prompt: '',
			table: tableData
		};
	}

	function workflowAjaxRequest(action, data) {
		return new Promise(function (resolve) {
			if (g_automateWorkflow.running) {
				g_doublyAdmin.setAjaxLoaderID(function () { });
			}
			g_doublyAdmin.ajaxRequest(action, data, function (response) {
				resolve(response);
			}, function (response) {
				resolve(response);
			});
		});
	}

	/**
	 * Keep / restore the per-cell apply-prompt spinner for Automate Workflow
	 * (e.g. after a queued image response path that would otherwise clear it).
	 */
	function ensureWorkflowCellLoadingVisible(payload) {
		if (!payload || !payload.table || !tableManipulator) {
			return;
		}
		var $cell = typeof tableManipulator.getCellFromApplyPromptTable === 'function'
			? tableManipulator.getCellFromApplyPromptTable(payload.table)
			: jQuery();
		if (!$cell.length && payload.table.postId != null && payload.table.columnIndex != null) {
			$cell = jQuery('.cell_' + payload.table.postId + '_' + payload.table.columnIndex);
		}
		if (!$cell.length) {
			return;
		}
		var key = typeof tableManipulator.getApplyPromptLoadingKeyFromTable === 'function'
			? tableManipulator.getApplyPromptLoadingKeyFromTable(payload.table)
			: null;
		var hasKeyedCount = key && tableManipulator.applyPromptLoadingCounts &&
			(tableManipulator.applyPromptLoadingCounts[key] || 0) > 0;
		if (!hasKeyedCount && typeof tableManipulator.setCellApplyPromptLoading === 'function') {
			tableManipulator.setCellApplyPromptLoading(true, false, payload.table);
		} else {
			$cell.addClass('ubai-cell-apply-prompt-loading');
		}
	}

	/** After workflow auto-applies a result: drop cell spinner + AI-requests panel row. */
	function workflowClearRequestAfterApply(payload) {
		clearCellPromptActivityForPayload(payload);
		var key = payload && payload.table ? promptRequestsPanelKeyFromTable(payload.table) : null;
		if (key && promptRequestsPanelRemoveByKey(key)) {
			renderPromptRequestsPanel();
		}
	}

	function workflowAjaxApplyPrompt(payload) {
		return new Promise(function (resolve) {
			if (g_automateWorkflow.stopped) {
				resolve(null);
				return;
			}
			g_doublyAdmin.setAjaxLoaderID(function () { });
			promptRequestsPanelAdd(payload);
			if (tableManipulator && tableManipulator.setCellApplyPromptLoading) {
				tableManipulator.setCellApplyPromptLoading(true, false, payload.table);
			}
			// Use complete-only so we don't double-finish (success + complete both fire).
			g_doublyAdmin.ajaxRequest('apply_prompt', payload, null, function (response) {
				if (!response || response.success === false) {
					finishApplyPromptRequest(response, payload, false);
					resolve(response);
					return;
				}
				if (response.status === 'queued' || response.status === 'in_progress') {
					// Keep AI-requests row in loading; reinstate cell spinner (finish would clear it).
					ensureWorkflowCellLoadingVisible(payload);
					if (typeof updateApplyPromptDebug === 'function') {
						updateApplyPromptDebug(response);
					}
					resolve(response);
					return;
				}
				// Final result arrived — leave panel loading until auto-apply clears it.
				ensureWorkflowCellLoadingVisible(payload);
				if (typeof updateApplyPromptDebug === 'function') {
					updateApplyPromptDebug(response);
				}
				resolve(response);
			});
		});
	}

	function workflowWaitForImagePromptResult(payload) {
		return new Promise(function (resolve) {
			if (g_automateWorkflow.stopped) {
				resolve(null);
				return;
			}
			ensureWorkflowCellLoadingVisible(payload);
			g_automateWorkflow.imagePollTimer = setTimeout(function () {
				g_automateWorkflow.imagePollTimer = null;
				if (g_automateWorkflow.stopped) {
					resolve(null);
					return;
				}
				g_doublyAdmin.setAjaxLoaderID(function () { });
				ensureWorkflowCellLoadingVisible(payload);
				g_doublyAdmin.ajaxRequest('apply_prompt', payload, null, function (response) {
					if (response && (response.status === 'queued' || response.status === 'in_progress')) {
						ensureWorkflowCellLoadingVisible(payload);
						workflowWaitForImagePromptResult(payload).then(resolve);
						return;
					}
					ensureWorkflowCellLoadingVisible(payload);
					resolve(response);
				});
			}, 20000);
		});
	}

	function workflowApplyAndWaitForSave(applyFn) {
		return new Promise(function (resolve) {
			var admin = g_doublyAdmin;
			var orig = admin.ajaxRequest;
			var settled = false;
			var saveSeen = false;

			function finish(result) {
				if (settled) {
					return;
				}
				settled = true;
				if (admin.ajaxRequest === patched) {
					admin.ajaxRequest = orig;
				}
				resolve(result);
			}

			var patched = function (action, data, success, complete) {
				if (action === 'save_edited_posts' && g_automateWorkflow.running) {
					saveSeen = true;
					return orig.call(admin, action, data, success, function (resp) {
						if (typeof complete === 'function') {
							complete(resp);
						}
						finish(resp);
					});
				}
				return orig.call(admin, action, data, success, complete);
			};

			admin.ajaxRequest = patched;
			try {
				applyFn();
			} catch (err) {
				admin.ajaxRequest = orig;
				finish(null);
				return;
			}
			setTimeout(function () {
				if (!saveSeen) {
					finish(null);
				}
			}, 1200);
		});
	}

	function extractWorkflowReplacementFromResponse(data) {
		var replacementText = '';
		var replacementSaveValue = undefined;
		var replaceOptions = {};

		if (Array.isArray(data)) {
			replacementSaveValue = data;
			replacementText = formatRepeaterRowsForPromptDisplay(data);
		} else if (data && typeof data === 'object') {
			var insertText = typeof data.insert === 'string' ? data.insert : '';
			replacementText = typeof data.show === 'string' ? data.show : insertText;
			if (data.blocks && typeof data.blocks === 'object') {
				replaceOptions.blocks = data.blocks;
			}
			if (insertText !== '') {
				replacementSaveValue = insertText;
			} else if (replaceOptions.blocks) {
				replacementSaveValue = insertText;
			}
		} else {
			replacementText = getApplyPromptReplacementValue(data) || '';
			if (replacementText !== '') {
				replacementSaveValue = replacementText;
			}
		}

		return {
			replacementText: replacementText,
			replacementSaveValue: replacementSaveValue,
			replaceOptions: replaceOptions
		};
	}

	function workflowApplyPendingImageResponse(response, $td, item, requestPayload) {
		var imageData = response && response.data ? response.data : null;
		if (!imageData || !imageData.request_id || !$td.length) {
			workflowClearRequestAfterApply(requestPayload);
			return Promise.resolve();
		}

		var previewUrl = imageData.preview_url || '';
		var pendingMeta = {
			file_size: parseInt(imageData.file_size, 10) || 0,
			file_type: imageData.file_type || '',
			width: parseInt(imageData.width, 10) || 0,
			height: parseInt(imageData.height, 10) || 0
		};
		var previewOptions = {
			fullUrl: previewUrl,
			fileSize: pendingMeta.file_size,
			fileType: pendingMeta.file_type,
			width: pendingMeta.width,
			height: pendingMeta.height
		};

		var $container = $td.find(tableManipulator.g_editorContainer).first();
		if (previewUrl) {
			tableManipulator.syncImageCellAttachment($container, '', previewUrl, previewOptions);
			// Preview sync treats the cell as "has a real image" and drops the spinner —
			// keep it visible until apply_pending_image finishes.
			ensureWorkflowCellLoadingVisible(requestPayload);
		}

		var colDef = columnsListGlobal.find(function (c) { return c && c.name === item.column; });
		var columnType = colDef && colDef.type ? colDef.type : 'image';

		return workflowAjaxRequest('apply_pending_image', {
			request_id: imageData.request_id,
			post_id: item.postId,
			column_type: columnType,
			column: item.column || 'post_image'
		}).then(function (applyResponse) {
			if (!applyResponse || applyResponse.success === false || !applyResponse.data) {
				workflowClearRequestAfterApply(requestPayload);
				return;
			}
			var resultData = applyResponse.data.data || applyResponse.data;
			var attachmentId = resultData.attachment_id;
			var thumbnailUrl = resultData.thumbnail_url;
			if (!attachmentId || !thumbnailUrl) {
				workflowClearRequestAfterApply(requestPayload);
				return;
			}
			var finalOptions = {
				fullUrl: resultData.full_url || resultData.thumbnail_url || thumbnailUrl,
				fileSize: parseInt(resultData.file_size, 10) || 0,
				fileType: resultData.file_type || '',
				width: parseInt(resultData.width, 10) || 0,
				height: parseInt(resultData.height, 10) || 0,
				filename: resultData.filename || ''
			};
			var $cellContainer = $td.find(tableManipulator.g_editorContainer).first();
			tableManipulator.syncImageCellAttachment($cellContainer, attachmentId, thumbnailUrl, finalOptions);
			// `apply_pending_image` already promotes the pending file to a WP attachment and
			// sets the thumbnail for `post_image`. For this column we can skip an extra
			// `save_edited_posts` round-trip (and the "saving" loader).
			if (item && item.column === 'post_image') {
				workflowClearRequestAfterApply(requestPayload);
				return Promise.resolve();
			}
			return workflowApplyAndWaitForSave(function () {
				tableManipulator.onCellContentSave($cellContainer, false, false, { suppressAjaxLoader: true });
			}).then(function () {
				workflowClearRequestAfterApply(requestPayload);
			});
		});
	}

	function workflowAutoApplyResponse(response, $td, item, requestPayload) {
		if (!response || response.success === false) {
			// Poll/final failures: settle panel to error (initial AJAX failures already settled).
			finishApplyPromptRequest(response, requestPayload, false);
			return Promise.resolve();
		}

		if (response.action === 'show_message') {
			workflowClearRequestAfterApply(requestPayload);
			return Promise.resolve();
		}

		if (response.action === 'pending_image') {
			return workflowApplyPendingImageResponse(response, $td, item, requestPayload);
		}

		if (response.action !== 'replace_text') {
			workflowClearRequestAfterApply(requestPayload);
			return Promise.resolve();
		}

		var extracted = extractWorkflowReplacementFromResponse(response.data);
		if (!extracted.replacementText && (extracted.replacementSaveValue === undefined || extracted.replacementSaveValue === null || extracted.replacementSaveValue === '')) {
			workflowClearRequestAfterApply(requestPayload);
			return Promise.resolve();
		}

		if (typeof tableManipulator.clearDiscardedPendingPromptResult === 'function') {
			tableManipulator.clearDiscardedPendingPromptResult($td);
		}

		var replaceOptions = Object.assign({}, extracted.replaceOptions || {}, {
			// Keep cell spinner; hide global "Saving..." during Automate Workflow.
			suppressAjaxLoader: true
		});

		return workflowApplyAndWaitForSave(function () {
			tableManipulator.applyPromptReplacementToCell(
				$td,
				extracted.replacementText,
				extracted.replacementSaveValue,
				replaceOptions
			);
		}).then(function () {
			workflowClearRequestAfterApply(requestPayload);
		});
	}

	/**
	 * Slug step: ask the server to build a unique slug from the post title and
	 * save it (post_name). The server is authoritative, so we only reflect the
	 * result in the cell here (visualOnly = no second save, no dialog).
	 */
	function runAutomateWorkflowSlugCell(item, $td) {
		if (tableManipulator && typeof tableManipulator.selectCellFromApplyPromptTable === 'function') {
			tableManipulator.selectCellFromApplyPromptTable({
				isSelected: true,
				postId: item.postId,
				columnIndex: item.columnIndex,
				column: item.column
			}, { suppressPromptDialogClose: true });
		}
		return workflowAjaxRequest('generate_slug_from_title', { post_id: item.postId }).then(function (response) {
			if (g_automateWorkflow.stopped) {
				return;
			}
			if (!response || response.success === false || !response.slug) {
				return; // empty title / skipped — leave the cell as-is
			}
			if (typeof tableManipulator.applyPromptReplacementToCell === 'function') {
				tableManipulator.applyPromptReplacementToCell($td, response.slug, response.slug, { visualOnly: true });
			}
			if (tableManipulator && typeof tableManipulator.selectCellFromApplyPromptTable === 'function') {
				tableManipulator.selectCellFromApplyPromptTable({
					isSelected: true,
					postId: item.postId,
					columnIndex: item.columnIndex,
					column: item.column
				}, { suppressPromptDialogClose: true });
			}
		});
	}

	function runAutomateWorkflowForCell(item, planIndex) {
		var $td = getAutomateWorkflowCellTd(item);
		if (!$td.length) {
			if (typeof planIndex === 'number') {
				markAutomateWorkflowPlanItemState(planIndex, 'skipped');
			}
			return Promise.resolve();
		}
		if (item.isSlug) {
			updateAutomateWorkflowStatusLabel(item.rowTitle + ' — ' + item.columnTitle);
			if (typeof planIndex === 'number') {
				markAutomateWorkflowPlanItemState(planIndex, 'active');
			}
			return runAutomateWorkflowSlugCell(item, $td).then(function () {
				if (typeof planIndex === 'number') {
					markAutomateWorkflowPlanItemState(planIndex, g_automateWorkflow.stopped ? 'pending' : 'done');
				}
			});
		}
		if (!isAutomateWorkflowCellEmpty($td)) {
			if (typeof planIndex === 'number') {
				markAutomateWorkflowPlanItemState(planIndex, 'skipped');
			}
			return Promise.resolve();
		}

		updateAutomateWorkflowStatusLabel(item.rowTitle + ' — ' + item.columnTitle);
		if (typeof planIndex === 'number') {
			markAutomateWorkflowPlanItemState(planIndex, 'active');
		}

		var payload = buildWorkflowApplyPromptPayload(item);
		if (!payload) {
			if (typeof planIndex === 'number') {
				markAutomateWorkflowPlanItemState(planIndex, 'skipped');
			}
			return Promise.resolve();
		}

		return workflowAjaxApplyPrompt(payload).then(function (response) {
			if (g_automateWorkflow.stopped) {
				workflowClearRequestAfterApply(payload);
				return { outcome: 'stopped' };
			}
			if (!response || response.success === false) {
				return { outcome: 'error' };
			}
			var applyPromise;
			if (response.status === 'queued' || response.status === 'in_progress') {
				applyPromise = workflowWaitForImagePromptResult(payload).then(function (polledResponse) {
					if (g_automateWorkflow.stopped) {
						workflowClearRequestAfterApply(payload);
						return { outcome: 'stopped' };
					}
					if (!polledResponse || polledResponse.success === false) {
						return workflowAutoApplyResponse(polledResponse, $td, item, payload).then(function () {
							return { outcome: 'error' };
						});
					}
					return workflowAutoApplyResponse(polledResponse, $td, item, payload).then(function () {
						return { outcome: 'done' };
					});
				});
			} else {
				applyPromise = workflowAutoApplyResponse(response, $td, item, payload).then(function () {
					return { outcome: 'done' };
				});
			}
			return applyPromise;
		}).then(function (result) {
			if (typeof planIndex !== 'number' || g_automateWorkflow.stopped) {
				return;
			}
			var outcome = result && result.outcome ? result.outcome : 'done';
			if (outcome === 'stopped') {
				return;
			}
			markAutomateWorkflowPlanItemState(planIndex, outcome === 'error' ? 'error' : 'done');
		}).catch(function () {
			if (typeof planIndex === 'number' && !g_automateWorkflow.stopped) {
				markAutomateWorkflowPlanItemState(planIndex, 'error');
			}
		});
	}

	function finishAutomateWorkflow() {
		if (g_automateWorkflow.imagePollTimer) {
			clearTimeout(g_automateWorkflow.imagePollTimer);
			g_automateWorkflow.imagePollTimer = null;
		}
		g_automateWorkflow.running = false;
		g_automateWorkflow.currentIndex = 0;
		hideAutomateWorkflowRunningUI();
		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderNoIndex);
		// Workflow finished — show the plan again.
		showAutomateWorkflowPlanPanel();

		// Re-enable table clicks after workflow completes.
		jQuery('#' + g_spreadsheetID).removeClass('ubai-automate-workflow-blocking');

		// Restore auto-open behavior for the AI requests panel.
		if (typeof g_automateWorkflow.prevPromptRequestsPanelUserClosed !== 'undefined') {
			g_promptRequestsPanelUserClosed = g_automateWorkflow.prevPromptRequestsPanelUserClosed;
			delete g_automateWorkflow.prevPromptRequestsPanelUserClosed;
		}
	}

	function processNextAutomateWorkflowCell() {
		if (g_automateWorkflow.stopped || g_automateWorkflow.currentIndex >= g_automateWorkflow.queue.length) {
			finishAutomateWorkflow();
			return;
		}

		var planIndex = g_automateWorkflow.currentIndex;
		var item = g_automateWorkflow.queue[planIndex];
		g_automateWorkflow.currentIndex += 1;

		runAutomateWorkflowForCell(item, planIndex).then(function () {
			if (g_automateWorkflow.stopped) {
				finishAutomateWorkflow();
				return;
			}
			processNextAutomateWorkflowCell();
		}).catch(function () {
			if (!g_automateWorkflow.stopped) {
				processNextAutomateWorkflowCell();
			} else {
				finishAutomateWorkflow();
			}
		});
	}

	/**
	 * Show the ordered plan in the sidebar. Does not run until Start.
	 */
	function openAutomateWorkflowPlan(selectedIds) {
		var queue = buildAutomateWorkflowQueue(selectedIds);
		if (!queue.length) {
			g_doublyAdmin.showErrorMessage('Nothing to automate — no empty slug or column-rule cells found in the selected rows.');
			return;
		}

		g_automateWorkflow.planning = true;
		g_automateWorkflow.running = false;
		g_automateWorkflow.stopped = false;
		g_automateWorkflow.selectedIds = selectedIds.slice();
		g_automateWorkflow.queue = queue;
		g_automateWorkflow.currentIndex = 0;

		showAutomateWorkflowPlanPanel();
	}

	/**
	 * Begin executing the already-built plan (clicked Start).
	 */
	function startAutomateWorkflowFromPlan() {
		if (g_automateWorkflow.running) {
			return;
		}
		var queue = g_automateWorkflow.queue || [];
		if (!queue.length) {
			g_doublyAdmin.showErrorMessage('Nothing to automate — no empty slug or column-rule cells found in the selected rows.');
			return;
		}

		queue.forEach(function (item) {
			item.planState = 'pending';
		});
		g_automateWorkflow.running = true;
		g_automateWorkflow.stopped = false;
		g_automateWorkflow.planning = false;
		g_automateWorkflow.currentIndex = 0;

		showAutomateWorkflowRunningUI(queue[0].rowTitle + ' — ' + queue[0].columnTitle);
		// Automate mode: keep the workflow plan panel visible.
		// Also keep the AI requests panel closed (but still track rows internally).
		g_automateWorkflow.prevPromptRequestsPanelUserClosed = g_promptRequestsPanelUserClosed;
		g_promptRequestsPanelUserClosed = true;
		jQuery('#ubai_prompt_requests_panel').removeClass('is-open');

		// Block table clicks while workflow is running.
		jQuery('#' + g_spreadsheetID).addClass('ubai-automate-workflow-blocking');
		renderAutomateWorkflowPlanPanel();
		g_doublyAdmin.setAjaxLoaderID(function () { });
		processNextAutomateWorkflowCell();
	}

	function startAutomateWorkflow(selectedIds) {
		openAutomateWorkflowPlan(selectedIds);
	}

	function stopAutomateWorkflow() {
		if (!g_automateWorkflow.running) {
			return;
		}
		g_automateWorkflow.stopped = true;
		if (g_automateWorkflow.imagePollTimer) {
			clearTimeout(g_automateWorkflow.imagePollTimer);
			g_automateWorkflow.imagePollTimer = null;
		}
		finishAutomateWorkflow();
	}

	function onAutomateWorkflowStopClick(e) {
		e.preventDefault();
		stopAutomateWorkflow();
	}

	function onAutomateWorkflowPlanStartClick(e) {
		e.preventDefault();
		startAutomateWorkflowFromPlan();
	}

	function onAutomateWorkflowPlanCloseClick(e) {
		e.preventDefault();
		if (g_automateWorkflow.running) {
			stopAutomateWorkflow();
		}
		g_automateWorkflow.planning = false;
		g_automateWorkflow.queue = [];
		g_automateWorkflow.selectedIds = [];
		g_automateWorkflow.currentIndex = 0;
		hideAutomateWorkflowPlanPanel();
	}

	function onAutomateWorkflowPlanItemClick(e) {
		e.preventDefault();
		var index = Number(jQuery(this).attr('data-index'));
		var item = g_automateWorkflow.queue && g_automateWorkflow.queue[index];
		if (!item || !tableManipulator) {
			return;
		}
		if (typeof tableManipulator.selectCellFromApplyPromptTable === 'function') {
			tableManipulator.selectCellFromApplyPromptTable({
				isSelected: true,
				postId: item.postId,
				columnIndex: item.columnIndex,
				column: item.column
			}, { suppressPromptDialogClose: true });
		}
	}

	/**
	 * Open automate workflow plan for bulk-selected rows (does not run until Start).
	 */
	function onAutomateWorkflowBtnClick(e) {
		e.preventDefault();

		if (!sheetspilot.editor.isPro || !sheetspilot.editor.enableAutomateWorkflow || g_automateWorkflow.running) {
			return;
		}

		var selectedIds = jQuery(g_bulkEditCheckboxInput + ':checked').map(function () {
			return this.value;
		}).get();

		if (!selectedIds.length) {
			return;
		}

		openAutomateWorkflowPlan(selectedIds);
	}

	// process bulk actions
	function processBulkActions(e) {

		var selected = g_objBulkEditSelect.select2('data');
		var isMultiple;
		var exitProcessor;
		var selectedCheckboxesValues = jQuery(g_bulkEditCheckboxInput + ':checked').map(function () {
			return this.value; // or $(this).val()
		}).get();

		// check if seletor is multliple
		var exploded_id = selected[0].id.split('_');
		exploded_id.splice(0, 2);
		bulkActionColumnName = 'acf_' + exploded_id.join('_');
		isMultiple = jQuery('tr[data-id="' + selectedCheckboxesValues[0] + '"] .editor_container[data-column="' + bulkActionColumnName + '"] .editor_input').prop('multiple');

		if (selected[0].id == 'bulk_trash') {
			if (confirm(sheetspilot.editor.are_you_sure_delete)) {

				g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderProcessing);
				var data =
				{
					ids: selectedCheckboxesValues,
					post_type: g_objPostTypeSelector.val(),
					action_type: 'bulk'
				};

				g_doublyAdmin.ajaxRequest('remove_table_post', data, function (response) {

					/*
					jQuery.each(selectedCheckboxesValues, function(index, value){
						var this_pnt = jQuery('tr[data-id="'+value+'"]');
						this_pnt.fadeOut(200, function(){
							this_pnt.replaceWith('');
						})	
					})
					*/

					tableManipulator.fillCellsWithContent(response.message.postslist);
					self.processPagination(response.message.total_count, g_objRowsAmountSelector.val(), parseInt(jQuery(g_pagiPagesFirst).html()));

					jQuery(tableManipulator.g_bulkEditDropdownSelector).removeClass(g_classActive);

					bindAllTableActionsAfterReload( initialFilteredColumns );

					tableManipulator.undoStackArchive.push({ 'cell': 'bulk_is_post_delete', 'type': 'is_post_delete', 'value': selectedCheckboxesValues });
				});
			}
		}
		if (selected[0].id == 'bulk_author' || selected[0].id == 'bulk_status' || ((selected[0].id.match(/bulk_select_/g) || []).length > 0 && !isMultiple) || (selected[0].id.match(/bulk_radio_/g) || []).length > 0) {



			if (selected[0].id == 'bulk_author') {
				bulkActionColumnName = 'post_author';
			}
			if (selected[0].id == 'bulk_status') {
				bulkActionColumnName = 'post_status';
			}

			bulkActionDataType = 'text';

			// process select and radio from ACF
			if (
				(selected[0].id.match(/bulk_select_/g) || []).length > 0 || (selected[0].id.match(/bulk_radio_/g) || []).length > 0
			) {
				var exploded_id = selected[0].id.split('_');
				exploded_id.splice(0, 2);
				bulkActionColumnName = 'acf_' + exploded_id.join('_');

			}




			// get all initial values
			let inital_values = [];
			jQuery.each(selectedCheckboxesValues, function (index, value) {
				var this_value = jQuery('tr[data-id="' + value + '"] .editor_container[data-column="' + bulkActionColumnName + '"] .editor_input').select2('data');

				if (!this_value)
					return (true);

				if (this_value.length > 0) {
					inital_values.push(this_value[0].id);
				}

			})



			tableManipulator.undoStackArchive.push({ 'action_type': 'bulk_is_bulk_change', 'columnType': bulkActionDataType, 'columnName': bulkActionColumnName, 'rows': selectedCheckboxesValues, 'values': inital_values });



			bulkActionIDs = selectedCheckboxesValues;

			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerTitle).append(
				'Bulk ' + selected[0].text +
				'<div class="unlimitedai-plugin__drawer-bulk-edit__affected-count">' + sheetspilot.editor.affected + ' ' + selectedCheckboxesValues.length + ' ' + sheetspilot.editor.rows + '</div>'
			);
			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerTitle).append('<span class="' + classSheetsPilotDrawer.g_drawerSaveLoaderNoIndex + '" id="' + classSheetsPilotDrawer.g_drawerSaveLoaderNoIndex + '"><span class="loader_round"></span></span>');


			var selector_to_use = jQuery('.editor_container[data-column="' + bulkActionColumnName + '"] .editor_part .acf_select_editor_input').first().clone().attr('class', g_drawerBulkEditSelectClass).attr('id', '');

			selector_to_use.prepend('<option value="" selected >' + sheetspilot.editor.select + '</option>');
			selector_to_use.val('');

			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerBody).append(selector_to_use);

			var searchBarHTML = `
					<div class="unlimitedai-plugin__drawer-bulk-edit__search">
							<div class="unlimitedai-plugin__drawer-bulk-edit__search-container">
									<span class="unlimitedai-plugin__drawer-bulk-edit__search-icon">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
									</span>
									<input type="text" class="unlimitedai-plugin__drawer-bulk-edit__search-input" placeholder="${sheetspilot.editor.search_status}">
							</div>
					</div>
			`;

			var $sourceSelect = jQuery('.' + g_drawerBulkEditSelectClass);
			var listItemsHTML = '';

			// Loop through each option to build the drawer list
			$sourceSelect.find('option').each(function () {
				var val = jQuery(this).val();
				var text = jQuery(this).text();

				listItemsHTML += `
							<div class="unlimitedai-plugin__drawer-bulk-edit__list-item drawer_searchable_item" data-value="${val}">
									<span class="unlimitedai-plugin__drawer-bulk-edit__list-item__icon"></span>
									<span class="unlimitedai-plugin__drawer-bulk-edit__list-item__label">${text}</span>
							</div>`;
			});

			var actionListHTML = `
					<div class="unlimitedai-plugin__drawer-bulk-edit__list">
							
							<div class="unlimitedai-plugin__drawer-bulk-edit__list-container">
									${listItemsHTML}
							</div>
					</div>
			`;



			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerBody).append(searchBarHTML);
			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerBody).append(actionListHTML);

			var footerHTML = `
					<div class="unlimitedai-plugin__side-drawer__footer-bulk-edit">
							<button class="unlimitedai-plugin__side-drawer__footer-bulk-edit__btn cancel" id="cancel_drawer_action">${sheetspilot.editor.cancel}</button>
							<button class="unlimitedai-plugin__side-drawer__footer-bulk-edit__btn unlimitedai-plugin__sidebar-apply-btn apply" id="apply_drawer_action">${sheetspilot.editor.apply_to} ${selectedCheckboxesValues.length} ${sheetspilot.editor.rows}							</button>
					</div>
			`;

			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerFooter).append(footerHTML);

			classSheetsPilotDrawer.onDrawerOpen();

		}


		if (selected[0].id.toLowerCase().split('bulk_tax_').length - 1 > 0) {

			bulkActionDataType = 'category';

			let tax_name = selected[0].id.replace("bulk_tax_", "");
			var tax_list;
			bulkActionColumnName = tax_name;

			// get all initial values
			let inital_values = {};
			jQuery.each(selectedCheckboxesValues, function (index, value) {

				var cloned_list = jQuery('tr[data-id="' + value + '"] .editor_container[data-column="' + bulkActionColumnName + '"] .category_cell_td_list').clone();
				jQuery('input[type="checkbox"]:checked', cloned_list).attr('checked', false);

				jQuery('li label', cloned_list).addClass('drawer_searchable_item');

				var this_cat_val = jQuery('tr[data-id="' + value + '"] .editor_container[data-column="' + bulkActionColumnName + '"] .category_cell_td_list input[type="checkbox"]:checked').map(function () {
					return jQuery(this).val(); // Get the value of each checked item
				}).get();

				inital_values[value] = this_cat_val;

				tax_list = cloned_list.html();
			})


			tableManipulator.undoStackArchive.push({ 'action_type': 'bulk_is_bulk_change', 'columnType': bulkActionDataType, 'columnName': bulkActionColumnName, 'rows': selectedCheckboxesValues, 'values': inital_values });

			bulkActionIDs = selectedCheckboxesValues;

			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerTitle).append(
				'Bulk ' + selected[0].text +
				'<div class="unlimitedai-plugin__drawer-bulk-edit__affected-count">' + sheetspilot.editor.affected + ' ' + selectedCheckboxesValues.length + ' ' + sheetspilot.editor.rows + '</div>'
			);
			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerTitle).append('<span class="' + classSheetsPilotDrawer.g_drawerSaveLoaderNoIndex + '" id="' + classSheetsPilotDrawer.g_drawerSaveLoaderNoIndex + '"><span class="loader_round"></span></span>');



			//jQuery(g_pluginSideDrawerBody).append( '<div class="category_cell_td_list">'+tax_list+'</div>');

			var searchBarHTML = `
					<div class="unlimitedai-plugin__drawer-bulk-edit__search">
							<div class="unlimitedai-plugin__drawer-bulk-edit__search-container">
									<span class="unlimitedai-plugin__drawer-bulk-edit__search-icon">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
									</span>
									<input type="text" class="unlimitedai-plugin__drawer-bulk-edit__search-input" placeholder="${sheetspilot.editor.search_status}">
							</div>
					</div>
			`;

			var $sourceSelect = jQuery('.' + g_drawerBulkEditSelectClass);
			var listItemsHTML = '<div class="category_cell_td_list sidebar_category_container">' + tax_list + '</div>';

			var actionListHTML = `
					<div class="unlimitedai-plugin__drawer-bulk-edit__list">							
							<div class="unlimitedai-plugin__drawer-bulk-edit__list-container">
									${listItemsHTML}
							</div>
					</div>
			`;



			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerBody).append(searchBarHTML);
			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerBody).append(actionListHTML);

			var footerHTML = `
					<div class="unlimitedai-plugin__side-drawer__footer-bulk-edit">
							<button class="unlimitedai-plugin__side-drawer__footer-bulk-edit__btn cancel" id="cancel_drawer_action">${sheetspilot.editor.cancel}</button>
							<button class="unlimitedai-plugin__side-drawer__footer-bulk-edit__btn unlimitedai-plugin__sidebar-apply-btn apply" id="apply_drawer_action">${sheetspilot.editor.apply_to} ${selectedCheckboxesValues.length} ${sheetspilot.editor.rows}							</button>
					</div>
			`;

			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerFooter).append(footerHTML);

			classSheetsPilotDrawer.onDrawerOpen();


		}



		if (selected[0].id.toLowerCase().split('bulk_tag_').length - 1 > 0 || ((selected[0].id.match(/bulk_select_/g) || []).length > 0 && isMultiple) || (selected[0].id.match(/bulk_checkbox_/g) || []).length > 0) {
			bulkActionDataType = 'tag';

			let tax_name = selected[0].id.replace("bulk_tag_", "");
			var tax_list;
			bulkActionColumnName = tax_name;

			// if multiselect or checkbox
			if (
				(
					(selected[0].id.match(/bulk_select_/g) || []).length > 0
				) ||
				(selected[0].id.match(/bulk_checkbox_/g) || []).length > 0
			) {
				bulkActionDataType = 'multicheck';
				let tax_name = selected[0].id.replace("bulk_select_", "");
				tax_name = tax_name.replace("bulk_checkbox_", "");
				bulkActionColumnName = 'acf_' + tax_name;
			}


			// get all initial values
			var inital_values = {};
			jQuery.each(selectedCheckboxesValues, function (index, value) {

				var this_value = jQuery('tr[data-id="' + value + '"] .editor_container[data-column="' + bulkActionColumnName + '"] .editor_input').select2('data');
				let tmp_arr = [];
				jQuery.each(this_value, function (index, this_data) {
					tmp_arr.push(this_data.id);
				})
				inital_values[value] = tmp_arr;

			})



			tableManipulator.undoStackArchive.push({ 'action_type': 'bulk_is_bulk_change', 'columnType': bulkActionDataType, 'columnName': bulkActionColumnName, 'rows': selectedCheckboxesValues, 'values': inital_values });

			bulkActionIDs = selectedCheckboxesValues;

			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerTitle).append(
				sheetspilot.editor.bulk + ' ' + selected[0].text +
				'<div class="unlimitedai-plugin__drawer-bulk-edit__affected-count">Affected: ' + selectedCheckboxesValues.length + ' ' + sheetspilot.editor.rows + '</div>'
			);
			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerTitle).append('<span class="' + classSheetsPilotDrawer.g_drawerSaveLoaderNoIndex + '" id="' + classSheetsPilotDrawer.g_drawerSaveLoaderNoIndex + '"><span class="loader_round"></span></span>');


			var selector_to_use = jQuery('.editor_container[data-column="' + bulkActionColumnName + '"] .editor_input').first();

			var searchBarHTML = `
					<div class="unlimitedai-plugin__drawer-bulk-edit__search">
							<div class="unlimitedai-plugin__drawer-bulk-edit__search-container">
									<span class="unlimitedai-plugin__drawer-bulk-edit__search-icon">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
									</span>
									<input type="text" class="unlimitedai-plugin__drawer-bulk-edit__search-input" placeholder="${sheetspilot.editor.search_status}">
							</div>
					</div>
			`;

			var listItemsHTML = '';

			// Loop through each option to build the drawer list
			selector_to_use.find('option').each(function () {


				var val = jQuery(this).val();
				var text = jQuery(this).text();

				listItemsHTML += `
							<div class="unlimitedai-plugin__drawer-bulk-edit__list-item drawer_searchable_item" data-value="${val}">
									<label class="side_drawer_checkbox_label"><input type="checkbox" value="${val}" />${text}</label>								 
							</div>`;
			});


			var actionListHTML = `
					<div class="unlimitedai-plugin__drawer-bulk-edit__list">							
							<div class="unlimitedai-plugin__drawer-bulk-edit__list-container">
									${listItemsHTML}
							</div>
					</div>
			`;



			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerBody).append(searchBarHTML);
			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerBody).append(actionListHTML);

			var footerHTML = `
					<div class="unlimitedai-plugin__side-drawer__footer-bulk-edit">
							<button class="unlimitedai-plugin__side-drawer__footer-bulk-edit__btn cancel" id="cancel_drawer_action">${sheetspilot.editor.cancel}</button>
							<button class="unlimitedai-plugin__side-drawer__footer-bulk-edit__btn unlimitedai-plugin__sidebar-apply-btn apply" id="apply_drawer_action">${sheetspilot.editor.apply_to} ${selectedCheckboxesValues.length} ${sheetspilot.editor.rows}							</button>
					</div>
			`;

			jQuery(classSheetsPilotDrawer.g_pluginSideDrawerFooter).append(footerHTML);

			classSheetsPilotDrawer.onDrawerOpen();


		}


		g_objBulkEditSelect.val(null).trigger('change');
		/*
		jQuery(g_pluginSideDrawerTitle).html('');		
		jQuery(g_pluginSideDrawerBody).html('');		 
		jQuery(g_pluginSideDrawerFooter).html('');
		*/
	}

	function taxonomyQuickSearch(e) {

		var this_pointer = e.target;
		var parent_editor = jQuery(e.target).parents(g_categoryEditor);
		var this_val = jQuery(e.target).val();
		if (this_val.length > 0) {
			parent_editor.addClass('is_tax_searching');
		} else {
			parent_editor.removeClass('is_tax_searching');
		}

		jQuery('.category_cell_td_list li label', parent_editor).each(function () {
			var current_text = jQuery(this).clone().children().remove().end().text().trim();
			if (current_text.indexOf(this_val) === -1) {
				jQuery(this).hide();
			} else {
				jQuery(this).show();
			}
		})
	}

	function replacePageTitle() {
		var current_hash = window.location.hash;
		current_hash = current_hash.replace("#", "");

		var optionText = jQuery('option[value="' + current_hash + '"]', g_objPostTypeSelector).text();
		if (current_hash != '') {
			document.title = optionText + ' - ' + g_pluginTitle;
		}

	}

	// debounce for ajax search input
	function debounce(fn, delay) {

		let timer = null;

		return function (...args) {

			const context = this;

			clearTimeout(timer);

			timer = setTimeout(() => {
				fn.apply(context, args);
			}, delay);
		};
	}

	/**
	* ajax column search functionality
	*/
	function onMakeingColumnAjaxSearch(e) {

		var currentObj = jQuery(e.target);
		var parent_th = currentObj.parents('th');

		jQuery('th[data-name="' + parent_th.attr('data-name') + '"] ' + g_filterColumnSettingsIcon).addClass('is_active');

		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderProcessing);

		var data =
		{
			col_filtering_query: globalTableFilterinByColumnsReturnFilters(),
			post_type: g_objPostTypeSelector.val(),
			rows_per_page: g_objRowsAmountSelector.val(),
			column_name: currentObj.parents('th').attr('data-name'),
		};

		g_doublyAdmin.ajaxRequest('make_column_ajax_search', data, function (response) {

			let tableData = response.message.postslist;
			let tableStructure = response.message.table_structure;


			var pagination_info = self.processPagination(response.message.total_count, g_objRowsAmountSelector.val(), response.message.current_page);
			pagination_info['global_counter'] = response.message.global_posts_number;

			tableManipulator = new SheetsPilot_CellProcessing('#new_output_table', tableStructure);
			tableManipulator.fillCellsWithContent(tableData);

			bindAllTableActionsAfterReload( initialFilteredColumns );

			currentObj.focus();

			// set top bar filters
			g_topFilteringBar.processFiltersAndShowHideMenu();
			g_topFilteringBar.modifyPostsCounter(pagination_info);
		})
	}
	/**
	* ajax search functionality
	*/
	function onMakeingAjaxSearch() {

		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderSearch);
		var data =
		{
			search_query: g_objSearchInput.val().toLowerCase(),
			post_type: g_objPostTypeSelector.val(),
			rows_per_page: g_objRowsAmountSelector.val(),
		};
		g_doublyAdmin.ajaxRequest('make_ajax_search', data, function (response) {
			let tableData = response.message.postslist;
			let tableStructure = response.message.table_structure;

			/*
			jQuery(g_pagiPagesFirst).html( response.message.current_page );
			jQuery(g_pagiRowsFirst).html( response.message.post_from );
			jQuery(g_pagiRowsLast).html( response.message.post_to );
			jQuery(g_pagiRowsTotal).html( response.message.total_count );
			jQuery(g_pagiPagesLast).html( response.message.total_pages );

			jQuery(g_pagiArrowButtonPrev).attr('disabled', false);

			if( jQuery(g_pagiPagesFirst).html() == jQuery(g_pagiPagesLast).html() ){
				jQuery(g_pagiArrowButtonNext).attr('disabled', true);
				jQuery(g_pagiArrowButtonPrev).attr('disabled', false);
			}
			if( jQuery(g_pagiPagesFirst).html() == 1 &&  jQuery(g_pagiPagesLast).html() == 1 ){
				jQuery(g_pagiArrowButtonNext).attr('disabled', true);
				jQuery(g_pagiArrowButtonPrev).attr('disabled', true);
			}
			*/
			self.processPagination(response.message.total_count, g_objRowsAmountSelector.val(), response.message.current_page)

			tableManipulator = new SheetsPilot_CellProcessing('#new_output_table', tableStructure);
			tableManipulator.fillCellsWithContent(tableData);

			bindAllTableActionsAfterReload( initialFilteredColumns );
		})
	}

	// pagination : click prev button
	function onPaginationPrevNextButton($direction) {

		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderProcessing);
		var data =
		{
			search_query: g_objSearchInput.val().toLowerCase(),
			post_type: g_objPostTypeSelector.val(),
			rows_per_page: g_objRowsAmountSelector.val(),
			current_page: jQuery(g_pagiPagesFirst).html(),
			direction: $direction
		};
		g_doublyAdmin.ajaxRequest('pagination_button_click', data, function (response) {

			let tableData = response.message.postslist;
			let tableStructure = response.message.table_structure;


			self.processPagination(response.message.total_count, g_objRowsAmountSelector.val(), response.message.current_page);

			tableManipulator = new SheetsPilot_CellProcessing('#new_output_table', tableStructure);
			tableManipulator.fillCellsWithContent(tableData);

			bindAllTableActionsAfterReload( initialFilteredColumns );
			
		})
	}



	// on rows per page change
	function onRowsPerPageChange(e) {

		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderProcessing);
		var data =
		{
			search_query: g_objSearchInput.val().toLowerCase(),
			post_type: g_objPostTypeSelector.val(),
			rows_per_page: g_objRowsAmountSelector.val(),
		};
		g_doublyAdmin.ajaxRequest('save_rows_page_number', data, function (response) {

			let pages_number = response.message.pages_number;
			let total_pages_to_show = parseInt(g_objRowsAmountSelector.val());
			let total_count = parseInt(response.message.total_count);
			let tableData = response.message.postslist;
			let tableStructure = response.message.table_structure;
			if (total_count < total_pages_to_show) {
				total_pages_to_show = total_count;
			}

			self.processPagination(total_count, g_objRowsAmountSelector.val(), 1)

			tableManipulator = new SheetsPilot_CellProcessing('#new_output_table', tableStructure);
			tableManipulator.fillCellsWithContent(tableData);

			bindAllTableActionsAfterReload( initialFilteredColumns );
		});
	}

	// initiate pagination
	function initiatePagination($posts_per_page, $total_posts, $total_pages) {

		jQuery(g_pagiRowsFirst).html('1');


		jQuery(g_pagiRowsLast).html($posts_per_page);
		jQuery(g_pagiRowsTotal).html($total_posts);

		jQuery(g_pagiPagesFirst).html('1');
		jQuery(g_pagiPagesLast).html($total_pages);
		jQuery(g_pagiArrowButtonNext).attr('disabled', false);
		if ($total_pages == 1) {
			jQuery(g_pagiArrowButtonNext).attr('disabled', true);
		}

	}

	// initiate pagination
	this.processPagination = function ($total_posts, $posts_per_page, $current_page) {

		$total_posts = parseInt($total_posts);
		$posts_per_page = parseInt($posts_per_page);
		$current_page = parseInt($current_page);

		// set totol posts as mandatory
		jQuery(g_pagiRowsTotal).html($total_posts);

		// calculate posts from
		$posts_from = ($current_page - 1) * $posts_per_page + 1;
		jQuery(g_pagiRowsFirst).html($posts_from);



		jQuery(g_pagiPagesFirst).html($current_page);

		// calculate total pages
		var $total_pages = parseInt($total_posts / $posts_per_page);
		if ($total_posts % $posts_per_page > 0) {
			$total_pages++;
		}
		jQuery(g_pagiPagesLast).html($total_pages);


		// calc posts to 
		$posts_to = ($current_page - 1) * $posts_per_page + $posts_per_page;

		if ($total_pages == $current_page && $posts_to > $total_posts) {
			$posts_to = $total_posts;
		}

		jQuery(g_pagiRowsLast).html($posts_to);

		// process of block arrows
		// if is first page
		if ($current_page == 1) {
			jQuery(g_pagiArrowButtonPrev).attr('disabled', true);
		} else {
			jQuery(g_pagiArrowButtonPrev).attr('disabled', false);
		}

		// if current page = total pages
		if ($current_page == $total_pages) {
			jQuery(g_pagiArrowButtonNext).attr('disabled', true);
			if ($total_pages > 1) {
				jQuery(g_pagiArrowButtonPrev).attr('disabled', false);
			}
		} else {
			jQuery(g_pagiArrowButtonNext).attr('disabled', false);
		}

		if ($posts_per_page > $total_posts) {
			$posts_per_page = $total_posts;
		}
		return {
			'total': $total_posts,
			'current': $posts_per_page,
		}

	}

	// delete post from table
	function deleteTablePost(e, action_type = false) {

		if (confirm(sheetspilot.editor.are_you_sure_delete)) {
			const $clicked = jQuery(e.currentTarget);
			var parent_tr = $clicked.parents('tr');
			var post_id = parent_tr.data('id');
			var data =
			{
				post_id: post_id,
			};

			g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderProcessing);
			g_doublyAdmin.ajaxRequest('remove_table_post', data, function (response) {
				parent_tr.fadeOut(200, function () {
					parent_tr.replaceWith('');
				})
				tableManipulator.undoStackArchive.push({ 'cell': 'is_post_delete', 'type': 'is_post_delete', 'value': post_id });
			});

		}
	}

	// duplicate post
	function duplicateTablePost(object_link, duplicate_number) {

		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderProcessing);
		const $clicked = object_link;
		var parent_tr = $clicked.parents('tr');
		var post_id = parent_tr.data('id');
		var data =
		{
			post_id: post_id,
			post_type: g_objPostTypeSelector.val(),
			duplicate_number: duplicate_number,
		};

		g_doublyAdmin.ajaxRequest('duplicate_table_post', data, function (response) {

			tableManipulator.fillCellsWithContent(response.message.postslist);

			self.processPagination(response.message.total_count, g_objRowsAmountSelector.val(), parseInt(jQuery(g_pagiPagesFirst).html()));

			tableManipulator.undoStackArchive.push({ 'cell': 'is_post_duplicate', 'type': 'is_post_duplicate', 'value': response.message.rowdata[0][1].id });

			bindAllTableActionsAfterReload( initialFilteredColumns );


		});
	}

	// duplicate post
	this.geneatePostsByTitleActions = function (list_names) {

		var data =
		{
			titles_list: list_names,
			post_type: jQuery(g_cellProcessingObj.g_ubaiPostTypeSelector).val(),
		};

		g_doublyAdmin.ajaxRequest('generate_posts_by_title', data, function (response) {

			let tableData = response.message.postslist;
			let tableStructure = response.message.table_structure;

			tableManipulator.fillCellsWithContent(response.message.postslist);

			self.processPagination(response.message.total_count, g_objRowsAmountSelector.val(), parseInt(jQuery(g_pagiPagesFirst).html()))

			bindAllTableActionsAfterReload( initialFilteredColumns );

			g_ajaxRunningFlag = false;
		});
	}

	/**
	 * add table empty row
	 */
	function onAddNewRow($rows_number) {

		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderProcessing);
		var data = {
			'post_type': g_objPostTypeSelector.val(),
			'rows_number': $rows_number
		};
		g_doublyAdmin.ajaxRequest('add_new_table_row', data, function (response) {

			tableManipulator.fillCellsWithContent(response.message.postslist);


			self.processPagination(response.message.total_count, g_objRowsAmountSelector.val(), parseInt(jQuery(g_pagiPagesFirst).html()))

			bindAllTableActionsAfterReload( initialFilteredColumns );

		});


	}


	/**
	* sidebar apply button click
	*/
	/**
	 * Initialize Quick Prompts trigger: opens the same context menu as cell right-click g_ubaiTextContextMenuID.
	 * Same actions and handlers; runs prompt on selected cell. No duplicate menu or logic.
	 */
	function initQuickActionsDropdown() {

		var disabledClass = "unlimitedai-plugin__quick-actions-combo--disabled";
		var comboSelector = "#ubai_quick_actions_combo";
		var triggerSelector = "#ubai_quick_actions_trigger";
		var $combo = g_objPostsEditor.find(comboSelector);
		var $trigger = g_objPostsEditor.find(triggerSelector);

		// Start disabled; updateApplyPromptButtonState() will enable when a cell is selected
		if ($combo.length) {
			$combo.addClass(disabledClass).attr("aria-disabled", "true").attr("tabindex", "-1");
		}

		jQuery(document).off("mousedown.ubaiQuickPromptsText contextmenu.ubaiQuickPromptsText dblclick.ubaiQuickPromptsText");
		jQuery(document).on("mousedown.ubaiQuickPromptsText contextmenu.ubaiQuickPromptsText dblclick.ubaiQuickPromptsText", function (e) {
			var $target = jQuery(e.target);
			if ($target.closest(comboSelector).length) {
				return;
			}
			// Keep sidebar-fixed positioning while interacting with menu items (mousedown runs before click).
			if ($target.closest("#" + g_ubaiTextContextMenuID).length) {
				return;
			}
			jQuery("#" + g_ubaiTextContextMenuID).removeClass(g_isFixedPositionClass);
			$combo.attr("aria-expanded", "false");
		});

		function openPromptContextMenu(isComboClick) {
			if (!tableManipulator) return;
			if ($combo.attr("aria-disabled") === "true" || $combo.hasClass(disabledClass)) return;

			var $menu = jQuery("#" + g_ubaiTextContextMenuID);

			// If the combo triggered this, and the menu is visible AND already has g_isFixedPositionClass, close it cleanly
			if (isComboClick && $menu.length && $menu.is(":visible") && $menu.hasClass(g_isFixedPositionClass)) {
				tableManipulator.hideContextMenu();
				$menu.removeClass(g_isFixedPositionClass);
				$combo.attr("aria-expanded", "false");
				return;
			}

			jQuery(g_dropdownContainerSelector).removeClass(g_classActive);
			var el = $combo.length ? $combo[0] : $trigger[0];
			if (!el) return;
			var rect = el.getBoundingClientRect();

			// Open the context menu via your standard manipulator position method
			tableManipulator.showContextMenuAtPosition(rect.left, rect.bottom, rect.width);

			// Target the menu element right after creation to apply/remove the class
			$menu = jQuery("#" + g_ubaiTextContextMenuID);
			if (isComboClick) {
				$menu.addClass(g_isFixedPositionClass);
				$combo.attr("aria-expanded", "true");
			} else {
				$menu.removeClass(g_isFixedPositionClass);
				$combo.attr("aria-expanded", "false");
			}
		}

		$combo.on("click", function (e) {
			e.preventDefault();
			e.stopPropagation();
			openPromptContextMenu(true);
		});

		$trigger.on("keydown", function (e) {
			if (e.key === "Enter" || e.key === " ") {
				e.preventDefault();
				openPromptContextMenu(false);
			}
		});
	}

	/**
	 * quick actions images
	 */
	function initQuickActionsDropdownImages() {

		var disabledClass = "unlimitedai-plugin__quick-actions-combo--disabled";
		var comboSelector = "#ubai_quick_actions_combo_images";
		var triggerSelector = "#ubai_quick_actions_trigger_images";
		var $combo = g_objPostsEditor.find(comboSelector);
		var $trigger = g_objPostsEditor.find(triggerSelector);

		// Start disabled; updateApplyPromptButtonState() will enable when a cell is selected
	 

		jQuery(document).off("mousedown.ubaiQuickPromptsImages contextmenu.ubaiQuickPromptsImages dblclick.ubaiQuickPromptsImages");
		jQuery(document).on("mousedown.ubaiQuickPromptsImages contextmenu.ubaiQuickPromptsImages dblclick.ubaiQuickPromptsImages", function (e) {
			var $target = jQuery(e.target);
			if ($target.closest(comboSelector).length) {
				return;
			}
			if ($target.closest("#" + g_ubaiTextContextMenuID).length) {
				return;
			}
			jQuery("#" + g_ubaiTextContextMenuID).removeClass(g_isFixedPositionClass);
			$combo.attr("aria-expanded", "false");
		});

		function openPromptContextMenu(isComboClick) {
			if (!tableManipulator) return;
			if ($combo.attr("aria-disabled") === "true" || $combo.hasClass(disabledClass)) return;

			var $menu = jQuery("#" + g_ubaiTextContextMenuID);

			// If the combo triggered this, and the menu is visible AND already has g_isFixedPositionClass, close it cleanly
			if (isComboClick && $menu.length && $menu.is(":visible") && $menu.hasClass(g_isFixedPositionClass)) {
				tableManipulator.hideContextMenu();
				$menu.removeClass(g_isFixedPositionClass);
				$combo.attr("aria-expanded", "false");
				return;
			}

			jQuery(g_dropdownContainerSelector).removeClass(g_classActive);
			var el = $combo.length ? $combo[0] : $trigger[0];
			if (!el) return;
			var rect = el.getBoundingClientRect();

			// Open the context menu via your standard manipulator position method
			tableManipulator.showContextMenuAtPosition(rect.left, rect.bottom, rect.width);

			// Target the menu element right after creation to apply/remove the class
			$menu = jQuery("#" + g_ubaiTextContextMenuID);
			if (isComboClick) {
				$menu.addClass(g_isFixedPositionClass);
				$combo.attr("aria-expanded", "true");
			} else {
				$menu.removeClass(g_isFixedPositionClass);
				$combo.attr("aria-expanded", "false");
			}
		}

		$combo.on("click", function (e) {
			e.preventDefault();
			e.stopPropagation();
			openPromptContextMenu(true);
		});

		$trigger.on("keydown", function (e) {
			if (e.key === "Enter" || e.key === " ") {
				e.preventDefault();
				openPromptContextMenu(false);
			}
		});
	}

	function onSideBarApplyBtnClick() {

		if (!tableManipulator) {
			return;
		}
		if (window.ubaiPrompts && window.ubaiPrompts.closePromptHistoryPanel) {
			window.ubaiPrompts.closePromptHistoryPanel();
		}

		var isImageTabActive = g_objPostsEditor.find("#" + g_ubaiSidebarModeTabImage).hasClass("active");
		var promptText = '';
		var $promptInput = null;

		var tableDataPreview = tableManipulator.getTableData();
		var isImageCell = tableDataPreview && tableDataPreview.isSelected
			&& (tableDataPreview.cellType === 'image' || tableDataPreview.column === 'post_image');
		var shouldUseImageSidebar = isImageTabActive || isImageCell;

		var image_settings = null;
		if (shouldUseImageSidebar) {
			$promptInput = g_objPostsEditor.find("#ubai_image_prompt_text");
			promptText = (window.ubaiPrompts && window.ubaiPrompts.getPromptInputValue)
				? window.ubaiPrompts.getPromptInputValue('#ubai_image_prompt_text')
				: (($promptInput && $promptInput.length) ? $promptInput.val().trim() : '');
			if (getSidebarImageActionValue() !== 'edit') {
				image_settings = collectSidebarImageSettings();
			}
		} else {
			promptText = (window.ubaiPrompts && window.ubaiPrompts.getPromptInputValue)
				? window.ubaiPrompts.getPromptInputValue('#ubai_prompt_input')
				: g_objPostsEditor.find('#ubai_prompt_input').val().trim();
			$promptInput = g_objPostsEditor.find('#ubai_prompt_input');
		}

		// Image mode: prompt is optional (generation/edit can use context only). Text mode: require prompt.
		if (!promptText && !shouldUseImageSidebar) {
			alert("Please enter a prompt.");
			if (window.ubaiPrompts && window.ubaiPrompts.focusPromptInput) {
				window.ubaiPrompts.focusPromptInput();
			} else {
				if ($promptInput && $promptInput.length) {
					$promptInput.focus();
				}
			}
			return;
		}

		var tableData = tableDataPreview || tableManipulator.getTableData();
		tableData.post_type = g_objPostTypeSelector.val() || '';
		if (shouldUseImageSidebar) {
			tableData.image_action = getSidebarImageActionValue();
			if (tableData.image_action !== 'edit' && image_settings) {
				tableData.image_settings = image_settings;
			}
		}
		attachApplyPromptSidebarOptionsToTableData(tableData);
		var payload = {
			prompt: promptText,
			table: tableData
		};
		scheduleApplyPromptDispatch(payload, function () {
			g_lastApplyPromptPayload = payload;
			promptRequestsPanelAdd(payload);

			g_doublyAdmin.setAjaxLoaderID(function (action) {
				if (action === "show_loader") {
					g_objSideBarApplyBtn.addClass('is-loading');
				} else {
					g_objSideBarApplyBtn.removeClass('is-loading');
				}
			});

			clearApplyPromptDebug();
			clearApplyPromptResponse();

			if (tableManipulator && tableManipulator.setCellApplyPromptLoading) {
				tableManipulator.setCellApplyPromptLoading(true, false, payload.table);
			}
			if (sheetspilot.editor.g_isLogOn == 1) {
				console.log('apply_prompt1');
			}
			g_doublyAdmin.ajaxRequest('apply_prompt', payload, function (response) {
				handleApplyPromptResponse(response, payload);

				if (response.status) {
					if (response.status == 'queued' || response.status == 'in_progress') {
						jQuery(`.cell_${payload.table.postId}_${payload.table.columnIndex}`).addClass('ubai-cell-apply-prompt-loading');
						intervalCallToCheckIfImageCreated(payload);
					}
				}

				// on apply response On — only when the dialog really shows THIS request's cell
				var autoApplyResponse = jQuery('#new_output_table th[data-name="' + tableData.column + '"] .unlimitedai-plugin__ai-column-settings-icon ').attr('data-auto-apply-response');
				if (autoApplyResponse == 'true' && promptDialogTargetMatchesPayload(payload)) {
					window.ubaiPrompts.onPromptReplaceDialogReplaceClick();
				}
			}, function (response) {
				finishApplyPromptRequest(response, payload, false);

			});
		});
	}

	/* Clears the apply prompt debug panel. */
	function clearApplyPromptDebug() {

		var debugContainer = g_objPostsEditor.find(".unlimitedai-plugin__sidebar-debug");
		if (!debugContainer.length) {
			return;
		}

		debugContainer.find(".unlimitedai-plugin__sidebar-debug__request .unlimitedai-plugin__sidebar-debug__block-body").empty();
		debugContainer.find(".unlimitedai-plugin__sidebar-debug__response .unlimitedai-plugin__sidebar-debug__block-body").empty();
		debugContainer.find(".unlimitedai-plugin__sidebar-debug__metadata .unlimitedai-plugin__sidebar-debug__block-body").empty();
		// Debug: keep panel visible (do not hide)
	}

	/* Clears the apply prompt response panel. */
	function clearApplyPromptResponse() {

		var responseContainer = g_objPostsEditor.find(".prompt_text_response");
		if (!responseContainer.length) {
			return;
		}

		responseContainer.find(".prompt_text_response__content").text("");
		responseContainer.hide();
	}

	/* Renders debug request/response (already formatted by PHP). */
	function updateApplyPromptDebug(response) {

		var debugContainer = g_objPostsEditor.find(".unlimitedai-plugin__sidebar-debug");
		if (!debugContainer.length) {
			return;
		}

		var debugRequest = response && response.debugRequest != null ? response.debugRequest : "";
		var debugResponse = response && response.debugResponse != null ? response.debugResponse : "";
		var debugMetadata = response && response.debugMetadata != null ? response.debugMetadata : "";

		debugContainer.find(".unlimitedai-plugin__sidebar-debug__request .unlimitedai-plugin__sidebar-debug__block-body").html(debugRequest);
		debugContainer.find(".unlimitedai-plugin__sidebar-debug__response .unlimitedai-plugin__sidebar-debug__block-body").html(debugResponse);
		var metadataBody = debugContainer.find(".unlimitedai-plugin__sidebar-debug__metadata .unlimitedai-plugin__sidebar-debug__block-body");
		if (metadataBody.length) {
			metadataBody.html(debugMetadata);
		} else if (debugMetadata) {
			var requestBody = debugContainer.find(".unlimitedai-plugin__sidebar-debug__request .unlimitedai-plugin__sidebar-debug__block-body");
			if (requestBody.length) {
				requestBody.append("<hr><strong>Metadata:</strong><br>" + debugMetadata);
			}
		}
		debugContainer.show();
	}

	/* Updates the AI response panel content. */
	function updatePromptTextResponse(messageText) {

		var responseContainer = g_objPostsEditor.find(".prompt_text_response");
		if (!responseContainer.length) {
			return;
		}

		responseContainer.find(".prompt_text_response__content").text(messageText);
		responseContainer.show();
	}

	/**
	 * Run the given prompt on the currently selected cell. Shows replace dialog on success.
	 * Called from context menu item click. Uses apply_prompt and handles response like sidebar apply.
	 * Sidebar Apply button gets is-loading only when promptText is non-empty (prompt was mirrored to the sidebar).
	 * Otherwise only the cell loading state runs (e.g. generate-image with no prompt string).
	 * @param {string} promptText Prompt to run.
	 * @param {string} [contextMenuAction] data-action from context menu (e.g. apply_column_rules, generate-image).
	 */
	this.runPromptOnSelectedCell = function (promptText, contextMenuAction, pasteValue = null) {

		var showDebug = true;

		if (!tableManipulator) {
			return;
		}
		var tableData = tableManipulator.getTableData();

		if (!tableData || !tableData.isSelected) {
			g_doublyAdmin.showErrorMessage(sheetspilot.editor.please_select_a_cell);
			return;
		}
		tableData.post_type = g_objPostTypeSelector.val() || '';

		var menuAct = (typeof contextMenuAction === 'string') ? contextMenuAction.trim() : '';
		if (menuAct !== '') {
			tableData.context_menu_action = menuAct;
		}

		var isImageCell = (tableData.cellType === 'image' || tableData.column === 'post_image');
		if (isImageCell) {
			var normalizedImageAction = normalizeContextMenuImageAction(menuAct);
			if (normalizedImageAction !== '') {
				tableData.image_action = normalizedImageAction;
			} else {
				tableData.image_action = getSidebarImageActionValue();
			}
		}

		attachApplyPromptSidebarOptionsToTableData(tableData);

		// Context menu: column rules for prompt + image options; do not send sidebar image_settings.
		// Ratio change uses only the simple submenu prompt (no cell rules).
		if (isImageCell) {
			tableData.include_rules = !isChangeImageRatioContextAction(menuAct) && menuAct !== 'enhance-image';
			delete tableData.image_settings;
			attachCellRulesToTableData(tableData);
		}
		var payload = {
			prompt: promptText.trim(),
			table: tableData
		};
		g_lastApplyPromptPayload = payload;

		scheduleApplyPromptDispatch(payload, function () {
			promptRequestsPanelAdd(payload);

			if (menuAct === 'apply_column_rules') {
				//g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderNoIndex);
			} else if (promptText.trim() !== '') {
				g_doublyAdmin.setAjaxLoaderID(function (action) {

					if (action === "show_loader") {
						g_objSideBarApplyBtn.addClass('is-loading');
					} else {
						g_objSideBarApplyBtn.removeClass('is-loading');
					}
				});
			} else {
				g_doublyAdmin.setAjaxLoaderID(function () { });
			}

			clearApplyPromptDebug();
			clearApplyPromptResponse();

			if (tableManipulator && tableManipulator.setCellApplyPromptLoading) {
				tableManipulator.setCellApplyPromptLoading(true, false, payload.table);
			}

			// on Paste action

			if (pasteValue) {
				payload.table.value = pasteValue;
			}
			if (sheetspilot.editor.g_isLogOn == 1) {
				console.log('apply_prompt3');
			}
			g_doublyAdmin.ajaxRequest('apply_prompt', payload, function (response) {

				handleApplyPromptResponse(response, payload);
				if (response.status) {
					if (response.status == 'queued' || response.status == 'in_progress') {
						jQuery(`.cell_${payload.table.postId}_${payload.table.columnIndex}`).addClass('ubai-cell-apply-prompt-loading');
						intervalCallToCheckIfImageCreated(payload);
					}
				}

				// on apply response On — only when the dialog really shows THIS request's cell
				var autoApplyResponse = jQuery('#new_output_table th[data-name="' + tableData.column + '"] .unlimitedai-plugin__ai-column-settings-icon ').attr('data-auto-apply-response');
				if (autoApplyResponse == 'true' && promptDialogTargetMatchesPayload(payload)) {
					window.ubaiPrompts.onPromptReplaceDialogReplaceClick();
					clearCellPromptActivityForPayload(payload);
				}
				if (sheetspilot.editor.g_isLogOn == 1) {
					console.log(response);
				}
			}, function (response) {

				finishApplyPromptRequest(response, payload, false);

			});
		});
	}

	function clearCellPromptActivityForPayload(payload) {
		if (!payload || !payload.table || !tableManipulator || !tableManipulator.clearCellPromptActivityIndicators) {
			return;
		}
		tableManipulator.clearCellPromptActivityIndicators(payload.table);
	}

	function buildImageQueueResumePayload(tableSnapshot) {
		if (!tableManipulator || !tableSnapshot) {
			return null;
		}
		tableManipulator.selectCellFromApplyPromptTable(tableSnapshot, { suppressPromptDialogClose: true });
		var tableData = tableManipulator.getTableData();
		if (!tableData || !tableData.isSelected) {
			return null;
		}
		tableData.post_type = g_objPostTypeSelector.val() || '';
		tableData.context_menu_action = 'generate-image';
		var isImageCell = (tableData.cellType === 'image' || tableData.column === 'post_image');
		if (isImageCell) {
			tableData.image_action = getSidebarImageActionValue();
			tableData.include_rules = true;
			delete tableData.image_settings;
			attachCellRulesToTableData(tableData);
		}
		attachApplyPromptSidebarOptionsToTableData(tableData);
		return {
			prompt: '',
			table: tableData
		};
	}

	/* 
	After initial image generation run run timeout task to check status of image generation
	*/
	function intervalCallToCheckIfImageCreated(payload) {
		if (!payload || !payload.table) {
			return;
		}
		if (tableManipulator && tableManipulator.getCellFromApplyPromptTable) {
			var $resumeCell = tableManipulator.getCellFromApplyPromptTable(payload.table);
			if ($resumeCell.length && tableManipulator.cellHasRealImage($resumeCell)) {
				clearCellPromptActivityForPayload(payload);
				return;
			}
		}

		setTimeout(function () {
			jQuery(`.cell_${payload.table.postId}_${payload.table.columnIndex}`).addClass('ubai-cell-apply-prompt-loading');
		}, 100);
		
			g_doublyAdmin.consoleLog( 'runquery'+`.cell_${payload.table.postId}_${payload.table.columnIndex}` );
			

			jQuery(`.cell_${payload.table.postId}_${payload.table.columnIndex}`).addClass('ubai-cell-apply-prompt-loading');

			// ajax query
			g_doublyAdmin.ajaxRequest('apply_prompt', payload, null, function (response) {

				if (sheetspilot.editor.g_isLogOn == 1) {
					console.log(response);
				}

				// Poll failed (null/parse error): stop this cell and free its slot so a
				// waiting request can start, instead of leaking the concurrency slot forever.
				if (!response) {
					clearCellPromptActivityForPayload(payload);
					promptRequestsPanelSettle(payload, null);
					return;
				}

				// we return image
				if (!response.status) {
					clearCellPromptActivityForPayload(payload);
					handleApplyPromptResponse(response, payload);
					promptRequestsPanelSettle(payload, response);
					//clearInterval(imageQueryCheckInterval[`col_${payload.table.columnIndex}_row_${payload.table.postId}`]);

					// on apply response On

					var autoApplyResponse = jQuery('#new_output_table th[data-name="' + payload.table.column + '"] .unlimitedai-plugin__ai-column-settings-icon ').attr('data-auto-apply-response');
					if (autoApplyResponse == 'true' && promptDialogTargetMatchesPayload(payload)) {
						window.ubaiPrompts.onPromptReplaceDialogApplyImageClick();
						clearCellPromptActivityForPayload(payload);
					}

				}else{
					// we still need to run
					g_doublyAdmin.consoleLog( 'timeouted run' );
					g_doublyAdmin.consoleLog( payload );
					setTimeout(function () {
						intervalCallToCheckIfImageCreated(payload);
					}, Math.floor(Math.random() * ( ( 12 - 7 + 1)) + 7 )* 1000);
				}
			});

	
	}


	/**
	 * On table content loaded, resume queued image generation polls.
	 * @param {Object.<string, string>} queueRequests
	 */
	function continueIntervalCallToCheckIfImageCreated(queueRequests) {
		if (!queueRequests || typeof queueRequests !== 'object') {
			queueRequests = {};
		}

		var activeLoadingKeys = {};
		jQuery.each(queueRequests, function (queueKey) {
			var snapshot = tableManipulator && tableManipulator.tableSnapshotFromImageQueueKey
				? tableManipulator.tableSnapshotFromImageQueueKey(queueKey)
				: null;
			if (snapshot) {
				activeLoadingKeys[String(snapshot.postId) + ':' + String(snapshot.columnIndex)] = true;
			}
		});

		if (tableManipulator && tableManipulator.clearStaleImageCellPromptIndicators) {
			tableManipulator.clearStaleImageCellPromptIndicators(activeLoadingKeys);
		}

		jQuery.each(queueRequests, function (queueKey) {
			if (!tableManipulator) {
				return;
			}
			var snapshot = tableManipulator.tableSnapshotFromImageQueueKey(queueKey);
			if (!snapshot) {
				return;
			}
			var $cell = tableManipulator.getCellFromApplyPromptTable(snapshot);
			if (!$cell.length) {
				return;
			}
			if (tableManipulator.cellHasRealImage($cell)) {
				tableManipulator.clearCellPromptActivityIndicators(snapshot);
				return;
			}

			var payload = buildImageQueueResumePayload(snapshot);
			if (payload) {
				intervalCallToCheckIfImageCreated(payload);
			}
		});
	}



	/**
	 * Run context-menu prompt flow (used by ubai_run_prompt and by SheetsPilot_CellProcessing direct call).
	 * Does not copy the prompt into the sidebar textarea — runs apply_prompt with data-prompt as-is.
	 * apply_column_rules: send empty prompt (server fills from Globals + column rules).
	 */
	this.dispatchRunPromptFromContextMenu = function (promptText, contextMenuAction) {

		var menuAction = (typeof contextMenuAction === 'string') ? contextMenuAction.trim() : '';
		var pt = (typeof promptText === 'string') ? promptText.trim() : '';
		if (menuAction === 'apply_column_rules') {
			pt = '';
		}
		if (!pt && !menuAction) {
			return;
		}
		if (window.ubaiPrompts && window.ubaiPrompts.closePromptHistoryPanel) {
			window.ubaiPrompts.closePromptHistoryPanel();
		}
		this.runPromptOnSelectedCell(pt, menuAction);
	};

	function isPromptReplaceDialogOpen() {
		if (window.ubaiPrompts && typeof window.ubaiPrompts.isPromptReplaceDialogOpen === "function") {
			return window.ubaiPrompts.isPromptReplaceDialogOpen();
		}
		return jQuery("#ubai_prompt_replace_dialog").is(":visible");
	}

	/**
	 * True only when the prompt replace dialog is open AND currently targets the same
	 * cell the given apply_prompt payload was sent for. Auto-apply flows must check this
	 * before clicking the shared dialog's Replace/Apply buttons — with several requests
	 * in flight the dialog may be showing a different cell's result, and blindly clicking
	 * would apply the wrong content to the wrong cell.
	 */
	function promptDialogTargetMatchesPayload(payload) {
		if (!payload || !payload.table || payload.table.postId == null || payload.table.columnIndex == null) {
			return false;
		}
		if (!isPromptReplaceDialogOpen()) {
			return false;
		}
		if (!window.ubaiPrompts || typeof window.ubaiPrompts.getPromptReplaceDialogTargetCell !== "function") {
			return false;
		}
		var $cell = window.ubaiPrompts.getPromptReplaceDialogTargetCell();
		if (!$cell || !$cell.length) {
			return false;
		}
		var postId = $cell.closest('tr').data('id');
		if (postId == null) {
			postId = $cell.data('row');
		}
		var colIdx = $cell.data('col');
		return String(postId) === String(payload.table.postId) &&
			Number(colIdx) === Number(payload.table.columnIndex);
	}

	function getPromptDialogCellKeyFromTableSnapshot(tableSnapshot) {
		if (!tableSnapshot || tableSnapshot.postId == null || tableSnapshot.columnIndex == null) {
			return null;
		}
		return String(tableSnapshot.postId) + ':' + String(tableSnapshot.columnIndex);
	}

	function trackPromptDialogUntouchedKey(tableSnapshot) {
		var key = getPromptDialogCellKeyFromTableSnapshot(tableSnapshot);
		if (!key) {
			return;
		}
		if (g_promptDialogUntouchedKeys.indexOf(key) === -1) {
			g_promptDialogUntouchedKeys.push(key);
		}
	}

	function getPromptRequestsToolbarCount() {
		var seen = {};
		var count = 0;
		g_promptRequestsPanelItems.forEach(function (item) {
			if (!item || !item.key || seen[item.key]) {
				return;
			}
			seen[item.key] = true;
			count++;
		});
		g_promptDialogUntouchedKeys.forEach(function (key) {
			if (!key || seen[key]) {
				return;
			}
			seen[key] = true;
			count++;
		});
		return count;
	}

	function updatePromptResultsToolbarButton() {
		if (!sheetspilot.editor || !sheetspilot.editor.showPromptResultsToolbarButton) {
			return;
		}
		var $btn = jQuery('#ubai_pending_prompt_results_trigger');
		if (!$btn.length) {
			return;
		}
		var count = getPromptRequestsToolbarCount();
		var $count = $btn.find('.ubai-pending-prompt-results-trigger__count');
		var tooltip = sheetspilot.editor.pendingPromptResultsToolbar || 'Open pending prompt results';
		if (count > 0) {
			$count.text(String(count));
			$btn.find('.has-tooltip').attr('data-title', tooltip + ' (' + count + ')');
		} else {
			$count.text('');
			$btn.find('.has-tooltip').attr('data-title', tooltip);
		}
		$btn.show();
	}

	/* ==================================================================
	   AI Requests panel — a calm, stable home for every prompt request.
	   Shows requests that are still loading (spinner) and results that
	   came back (clickable -> opens the prompt result dialog on the right
	   cell, scrolled into view). Opened from the toolbar wand button and
	   auto-opens when a request starts.
	================================================================== */
	var g_promptRequestsPanelItems = [];
	var g_promptRequestsPanelSeq = 0;
	var g_promptRequestsPanelUserClosed = false;
	var g_promptRequestsPanelFocusKey = null;

	// --- apply_prompt concurrency cap ---------------------------------------
	// At most APPLY_PROMPT_MAX_CONCURRENT cells generate at once (image + text alike).
	// Extra triggers wait in g_applyPromptWaitingQueue and start as slots free up.
	// A slot is held from dispatch until the cell reaches a terminal state
	// (result ready, settled, or errored) — for images that includes the whole
	// server-side poll loop, so it is not released on the initial 'queued' reply.
	var APPLY_PROMPT_MAX_CONCURRENT = parseInt(sheetspilot.editor.g_applyPromptMaxConcurrent, 10) || 4;
	var g_applyPromptActiveKeys = {};
	var g_applyPromptWaitingQueue = [];

	function applyPromptActiveCount() {
		return Object.keys(g_applyPromptActiveKeys).length;
	}

	function removeApplyPromptWaiting(key) {
		if (!key) {
			return false;
		}
		var removed = false;
		for (var i = g_applyPromptWaitingQueue.length - 1; i >= 0; i--) {
			if (g_applyPromptWaitingQueue[i].key === key) {
				g_applyPromptWaitingQueue.splice(i, 1);
				removed = true;
			}
		}
		if (removed) {
			clearCellApplyPromptWaitingForKey(key);
		}
		return removed;
	}

	function clearCellApplyPromptWaitingForKey(key) {
		if (!key || !tableManipulator || typeof tableManipulator.setCellApplyPromptWaiting !== 'function') {
			return;
		}
		var parts = String(key).split(':');
		if (parts.length < 2) {
			return;
		}
		tableManipulator.setCellApplyPromptWaiting(false, {
			isSelected: true,
			postId: parts[0],
			columnIndex: parseInt(parts[1], 10)
		});
	}

	function setCellApplyPromptWaitingForPayload(payload, show) {
		if (!payload || !payload.table || !tableManipulator || typeof tableManipulator.setCellApplyPromptWaiting !== 'function') {
			return;
		}
		tableManipulator.setCellApplyPromptWaiting(!!show, payload.table);
	}

	function pumpApplyPromptWaitingQueue() {
		while (g_applyPromptWaitingQueue.length && applyPromptActiveCount() < APPLY_PROMPT_MAX_CONCURRENT) {
			var next = g_applyPromptWaitingQueue.shift();
			if (!next || typeof next.run !== 'function') {
				continue;
			}
			clearCellApplyPromptWaitingForKey(next.key);
			g_applyPromptActiveKeys[next.key] = true;
			next.run();
		}
	}

	/**
	 * Fire an apply_prompt dispatch now if a slot is free, otherwise park it as
	 * "waiting" in the AI-requests panel until an earlier request finishes.
	 * @param {Object} payload apply_prompt payload (has payload.table).
	 * @param {Function} runFn does the actual loader setup + ajaxRequest.
	 */
	function scheduleApplyPromptDispatch(payload, runFn) {
		var key = payload && payload.table ? promptRequestsPanelKeyFromTable(payload.table) : null;
		if (!key) {
			// No stable cell key — can't queue/track it, just run.
			runFn();
			return;
		}
		// A fresh trigger for a cell supersedes any earlier waiting entry for it.
		removeApplyPromptWaiting(key);
		if (g_applyPromptActiveKeys[key]) {
			// Already generating for this cell (e.g. quick re-trigger) — it holds a slot.
			runFn();
			return;
		}
		if (applyPromptActiveCount() < APPLY_PROMPT_MAX_CONCURRENT) {
			g_applyPromptActiveKeys[key] = true;
			runFn();
			return;
		}
		g_applyPromptWaitingQueue.push({ key: key, run: runFn });
		promptRequestsPanelAddWaiting(payload);
		setCellApplyPromptWaitingForPayload(payload, true);
	}

	/** Free the slot a cell held and start the next waiting request (if any). Idempotent. */
	function releaseApplyPromptSlot(key) {
		if (!key) {
			return;
		}
		if (g_applyPromptActiveKeys[key]) {
			delete g_applyPromptActiveKeys[key];
		}
		pumpApplyPromptWaitingQueue();
	}

	function promptRequestsPanelKeyFromTable(table) {
		return getPromptDialogCellKeyFromTableSnapshot(table);
	}

	function getApplyPromptLogIdFromResponse(response) {
		if (!response || typeof response !== 'object') {
			return 0;
		}
		var id = parseInt(response.log_id, 10);
		return id > 0 ? id : 0;
	}

	function getApplyPromptErrorMessage(response) {
		if (!response) {
			return 'Request failed.';
		}
		if (typeof response.message === 'string' && response.message.trim() !== '') {
			return response.message.trim();
		}
		return 'Request failed.';
	}

	function getApplyPromptErrorDetailText(response) {
		if (!response || typeof response !== 'object') {
			return '';
		}
		if (typeof response.error_detail_text === 'string') {
			return response.error_detail_text.trim();
		}
		return '';
	}

	function bindPromptRequestsPanelEvents($panel) {
		if (!$panel || !$panel.length || $panel.data('ubaiPrqEventsBound')) {
			return;
		}
		$panel.data('ubaiPrqEventsBound', true);
		$panel.on('click', '.ubai-prq__close', function () {
			g_promptRequestsPanelUserClosed = true;
			$panel.removeClass('is-open');
		});
		$panel.on('click', '.ubai-prq__clear-all', function (e) {
			e.preventDefault();
			// Cancel every parked request too, so none of them fire after a clear-all.
			g_applyPromptWaitingQueue = [];
			if (tableManipulator && typeof tableManipulator.clearAllApplyPromptWaiting === 'function') {
				tableManipulator.clearAllApplyPromptWaiting();
			}
			promptRequestsPanelClearAll();
		});
		$panel.on('click', '.ubai-prq__clear', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var key = jQuery(this).closest('.ubai-prq__item').attr('data-key');
			if (key) {
				var item = getPromptRequestsPanelItem(key);
				if (item && item.state === 'waiting') {
					// Waiting request: just drop it from the queue — it never starts.
					removeApplyPromptWaiting(key);
				} else if (item && item.state === 'loading') {
					// In-flight request: free its slot so a waiting one can take its place.
					releaseApplyPromptSlot(key);
				}
				promptRequestsPanelRemoveByKey(key);
				renderPromptRequestsPanel();
			}
		});
		$panel.on('click', '.ubai-prq__item', function (e) {
			if (jQuery(e.target).closest('.ubai-prq__clear').length) {
				return;
			}
			var key = jQuery(this).attr('data-key');
			if (!key) {
				return;
			}
			if (jQuery(this).hasClass('ubai-prq__item--loading') || jQuery(this).hasClass('ubai-prq__item--waiting') || jQuery(this).hasClass('ubai-prq__item--error')) {
				selectPromptRequestCellFromPanelByKey(key);
				return;
			}
			if (jQuery(this).hasClass('ubai-prq__item--ready')) {
				g_promptRequestsPanelFocusKey = null;
				openPromptResultFromPanelByKey(key);
			}
		});
	}

	function ensurePromptRequestsPanel() {
		var $panel = jQuery('#ubai_prompt_requests_panel');
		if ($panel.length) {
			if (!$panel.find('.ubai-prq__clear-all').length) {
				var strings = (typeof sheetspilot !== 'undefined' && sheetspilot.editor) ? sheetspilot.editor : {};
				var $close = $panel.find('.ubai-prq__close').first();
				var $clearAll = jQuery('<button type="button" class="ubai-prq__clear-all"></button>')
					.text(strings.promptRequestClearAll || 'Clear all');
				if ($close.length) {
					$close.before($clearAll);
					$close.parent().addClass('ubai-prq__header-actions');
				} else {
					$panel.find('.ubai-prq__header').append(
						jQuery('<div class="ubai-prq__header-actions"></div>').append($clearAll)
					);
				}
			}
			bindPromptRequestsPanelEvents($panel);
			return $panel;
		}
		if (!document.getElementById('ubai_prompt_requests_panel_style')) {
			var css = [
				'#ubai_prompt_requests_panel{position:fixed;top:78px;right:16px;width:308px;max-height:64vh;display:none;flex-direction:column;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 12px 32px rgba(16,24,40,.16);z-index:100002;font-size:13px;color:#374151;overflow:hidden;}',
				'#ubai_prompt_requests_panel.is-open{display:flex;}',
				'#ubai_prompt_requests_panel .ubai-prq__header{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid #f3f4f6;background:#fafafa;}',
				'#ubai_prompt_requests_panel .ubai-prq__title{font-weight:600;color:#111827;font-size:13px;display:flex;align-items:center;gap:7px;}',
				'#ubai_prompt_requests_panel .ubai-prq__header-actions{display:flex;align-items:center;gap:10px;}',
				'#ubai_prompt_requests_panel .ubai-prq__clear-all{border:0;background:none;padding:0;font-size:12px;font-weight:500;color:#6b7280;cursor:pointer;opacity:0;transition:opacity .15s;}',
				'#ubai_prompt_requests_panel .ubai-prq__header:hover .ubai-prq__clear-all{opacity:1;}',
				'#ubai_prompt_requests_panel .ubai-prq__clear-all:hover{color:#111827;text-decoration:underline;}',
				'#ubai_prompt_requests_panel .ubai-prq__badge{display:none;min-width:18px;height:18px;border-radius:999px;background:#2271b1;color:#fff;font-size:11px;font-weight:600;line-height:18px;text-align:center;padding:0 5px;}',
				'#ubai_prompt_requests_panel .ubai-prq__close{border:0;background:none;cursor:pointer;color:#6b7280;font-size:15px;line-height:1;padding:4px;border-radius:6px;}',
				'#ubai_prompt_requests_panel .ubai-prq__close:hover{background:#f3f4f6;color:#111827;}',
				'#ubai_prompt_requests_panel .ubai-prq__list{overflow-y:auto;padding:6px;flex:1;}',
				'#ubai_prompt_requests_panel .ubai-prq__empty{padding:18px 12px;text-align:center;color:#9ca3af;}',
				'#ubai_prompt_requests_panel .ubai-prq__item{display:flex;align-items:center;gap:9px;padding:8px 9px;border-radius:8px;margin-bottom:2px;}',
				'#ubai_prompt_requests_panel .ubai-prq__item--ready,#ubai_prompt_requests_panel .ubai-prq__item--loading,#ubai_prompt_requests_panel .ubai-prq__item--waiting,#ubai_prompt_requests_panel .ubai-prq__item--error{cursor:pointer;}',
				'#ubai_prompt_requests_panel .ubai-prq__item--ready:hover,#ubai_prompt_requests_panel .ubai-prq__item--loading:hover,#ubai_prompt_requests_panel .ubai-prq__item--waiting:hover,#ubai_prompt_requests_panel .ubai-prq__item--error:hover{background:#eff6ff;}',
				'#ubai_prompt_requests_panel .ubai-prq__item--waiting{opacity:.8;}',
				'#ubai_prompt_requests_panel .ubai-prq__item--waiting:hover .ubai-prq__clear{opacity:1;}',
				'#ubai_prompt_requests_panel .ubai-prq__item--error{background:#fef2f2;}',
				'#ubai_prompt_requests_panel .ubai-prq__item--error .ubai-prq__label{color:#991b1b;}',
				'#ubai_prompt_requests_panel .ubai-prq__item--active{background:#eff6ff;box-shadow:inset 0 0 0 2px #2271b1;}',
				'#ubai_prompt_requests_panel .ubai-prq__item--active .ubai-prq__clear{opacity:1;}',
				'#ubai_prompt_requests_panel .ubai-prq__status{flex:none;width:16px;height:16px;display:flex;align-items:center;justify-content:center;}',
				'#ubai_prompt_requests_panel .ubai-prq__spinner{width:13px;height:13px;border:2px solid #e5e7eb;border-top-color:#2271b1;border-radius:50%;animation:ubaiPrqSpin .8s linear infinite;}',
				'#ubai_prompt_requests_panel .ubai-prq__spinner--queued{border-top-color:#f59e0b;animation-duration:1.1s;}',
				'@keyframes ubaiPrqSpin{to{transform:rotate(360deg);}}',
				'#ubai_prompt_requests_panel .ubai-prq__dot{width:9px;height:9px;border-radius:50%;background:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.18);}',
				'#ubai_prompt_requests_panel .ubai-prq__dot--error{background:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.18);}',
				'#ubai_prompt_requests_panel .ubai-prq__meta{flex:1;min-width:0;}',
				'#ubai_prompt_requests_panel .ubai-prq__label{font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}',
				'#ubai_prompt_requests_panel .ubai-prq__field{color:#6b7280;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px;}',
				'#ubai_prompt_requests_panel .ubai-prq__footer{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:3px;min-width:0;}',
				'#ubai_prompt_requests_panel .ubai-prq__state{color:#6b7280;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0;}',
				'#ubai_prompt_requests_panel .ubai-prq__item--error .ubai-prq__state{color:#b91c1c;}',
				'#ubai_prompt_requests_panel .ubai-prq__log-id{flex:none;color:#9ca3af;font-size:10px;line-height:1.35;white-space:nowrap;}',
				'#ubai_prompt_requests_panel .ubai-prq__clear{flex:none;border:0;background:none;padding:0;font-size:12px;font-weight:600;color:#6b7280;cursor:pointer;opacity:0;transition:opacity .15s;}',
				'#ubai_prompt_requests_panel .ubai-prq__clear:hover{color:#111827;text-decoration:underline;}',
				'#ubai_prompt_requests_panel .ubai-prq__item:hover .ubai-prq__clear{opacity:1;}'
			].join('');
			var style = document.createElement('style');
			style.id = 'ubai_prompt_requests_panel_style';
			style.textContent = css;
			document.head.appendChild(style);
		}
		var strings = (typeof sheetspilot !== 'undefined' && sheetspilot.editor) ? sheetspilot.editor : {};
		$panel = jQuery(
			'<div id="ubai_prompt_requests_panel" role="region" aria-label="AI prompt requests">' +
				'<div class="ubai-prq__header">' +
					'<span class="ubai-prq__title">' + (strings.promptRequestsPanelTitle || 'AI requests') +
						'<span class="ubai-prq__badge"></span></span>' +
					'<div class="ubai-prq__header-actions">' +
						'<button type="button" class="ubai-prq__clear-all">' + (strings.promptRequestClearAll || 'Clear all') + '</button>' +
						'<button type="button" class="ubai-prq__close" aria-label="Close">&#10005;</button>' +
					'</div>' +
				'</div>' +
				'<div class="ubai-prq__list"></div>' +
			'</div>'
		);
		jQuery('body').append($panel);
		bindPromptRequestsPanelEvents($panel);
		return $panel;
	}

	function promptRequestsPanelCellInfo(table) {
		if (tableManipulator && typeof tableManipulator.getPromptCellDisplayInfo === 'function') {
			return tableManipulator.getPromptCellDisplayInfo(table);
		}
		return { label: 'Cell', sub: '' };
	}

	function ensurePromptResultToolbarShield() {
		var $wrap = jQuery('#unlimitedai-plugin .unlimitedai-plugin__buttons_wrap').first();
		if (!$wrap.length) {
			return jQuery();
		}
		var $shield = $wrap.children('.ubai-prompt-result-toolbar-shield');
		if (!$shield.length) {
			$shield = jQuery('<div class="ubai-prompt-result-toolbar-shield" aria-hidden="true"></div>');
			if ($wrap.css('position') === 'static') {
				$wrap.css('position', 'relative');
			}
			$wrap.prepend($shield);
		}
		return $shield;
	}

	function showPromptResultFocusMode() {
		jQuery('body').addClass('ubai-prompt-result-focus-mode');
		ensurePromptResultToolbarShield();
	}

	function hidePromptResultFocusMode() {
		jQuery('body').removeClass('ubai-prompt-result-focus-mode');
	}

	function getPromptRequestsPanelActiveKey() {
		return getOpenPromptDialogCellKey() || g_promptRequestsPanelFocusKey;
	}

	/** Loading request: focus the target cell in the grid only (no result dialog). */
	function selectPromptRequestCellFromPanelByKey(key) {
		if (!tableManipulator || !key) {
			return false;
		}
		if (isPromptReplaceDialogOpen() && window.ubaiPrompts && typeof window.ubaiPrompts.hidePromptReplaceDialog === 'function') {
			window.ubaiPrompts.hidePromptReplaceDialog({ keepPendingCount: true });
		}
		var parts = String(key).split(':');
		if (parts.length < 2) {
			return false;
		}
		var tableSnapshot = {
			isSelected: true,
			postId: parts[0],
			columnIndex: parts[1]
		};
		var selected = typeof tableManipulator.selectCellFromApplyPromptTable === 'function' &&
			tableManipulator.selectCellFromApplyPromptTable(tableSnapshot, { suppressPromptDialogClose: true });
		if (!selected) {
			return false;
		}
		g_promptRequestsPanelFocusKey = key;
		renderPromptRequestsPanel();
		return true;
	}

	function getOpenPromptDialogCellKey() {
		if (!isPromptReplaceDialogOpen()) {
			return null;
		}
		if (!window.ubaiPrompts || typeof window.ubaiPrompts.getPromptReplaceDialogTargetCell !== 'function') {
			return null;
		}
		var $cell = window.ubaiPrompts.getPromptReplaceDialogTargetCell();
		if (!$cell || !$cell.length) {
			return null;
		}
		var postId = $cell.closest('tr').data('id');
		if (postId == null) {
			postId = $cell.data('row');
		}
		var columnIndex = $cell.data('col');
		if (postId == null || columnIndex == null) {
			return null;
		}
		return String(postId) + ':' + String(columnIndex);
	}

	function renderPromptRequestsPanel(options) {
		options = options || {};
		var $panel = ensurePromptRequestsPanel();
		var $list = $panel.find('.ubai-prq__list');
		var strings = (typeof sheetspilot !== 'undefined' && sheetspilot.editor) ? sheetspilot.editor : {};
		var activeKey = getPromptRequestsPanelActiveKey();
		$list.empty();
		if (!g_promptRequestsPanelItems.length) {
			$list.append(jQuery('<div class="ubai-prq__empty"></div>')
				.text(strings.promptRequestsPanelEmpty || 'No AI requests right now.'));
			$panel.find('.ubai-prq__badge').hide();
			// Auto-hide when the last request is gone; manual open keeps the empty state visible.
			if (options.hideIfEmpty !== false) {
				$panel.removeClass('is-open');
			}
			updatePromptResultsToolbarButton();
			return;
		}
		g_promptRequestsPanelItems.forEach(function (item) {
			var ready = item.state === 'ready';
			var waiting = item.state === 'waiting';
			var errored = item.state === 'error';
			var isActive = activeKey && item.key === activeKey;
			var stateClass = ready ? ' ubai-prq__item--ready' : (waiting ? ' ubai-prq__item--waiting' : (errored ? ' ubai-prq__item--error' : ' ubai-prq__item--loading'));
			var itemClass = 'ubai-prq__item' + stateClass +
				(isActive ? ' ubai-prq__item--active' : '');
			var $item = jQuery('<div class="' + itemClass + '" data-key="' + item.key + '"></div>');
			var $status = jQuery('<span class="ubai-prq__status"></span>');
			$status.append(ready
				? '<span class="ubai-prq__dot"></span>'
				: (waiting
					? '<span class="ubai-prq__spinner ubai-prq__spinner--queued" title="Waiting for slot"></span>'
					: (errored
						? '<span class="ubai-prq__dot ubai-prq__dot--error"></span>'
						: '<span class="ubai-prq__spinner"></span>')));
			var $meta = jQuery('<span class="ubai-prq__meta"></span>');
			$meta.append(jQuery('<div class="ubai-prq__label"></div>').text(item.label || 'Post'));
			$meta.append(jQuery('<div class="ubai-prq__field"></div>').text(item.sub || 'Cell'));
			var stateText = ready
				? (strings.promptRequestReady || 'Result ready')
				: (waiting ? (strings.promptRequestWaiting || 'Waiting') : (errored ? 'Error' : (strings.promptRequestLoading || 'Generating…')));
			if (errored && item.errorMessage) {
				stateText = item.errorMessage;
			}
			var tooltipText = errored ? (item.errorDetailText || item.errorMessage || '') : '';
			var $footer = jQuery('<div class="ubai-prq__footer"></div>');
			var $state = jQuery('<div class="ubai-prq__state"></div>').text(stateText);
			if (tooltipText) {
				$state.attr('title', tooltipText);
				$item.attr('title', tooltipText);
			}
			$footer.append($state);
			if (item.logId) {
				$footer.append(
					jQuery('<div class="ubai-prq__log-id"></div>').text('Log #' + item.logId)
				);
			}
			$meta.append($footer);
			$item.append($status).append($meta);
			$item.append(
				jQuery('<button type="button" class="ubai-prq__clear"></button>')
					.text(strings.promptRequestClear || 'Clear')
			);
			$list.append($item);
		});
		var $badge = $panel.find('.ubai-prq__badge');
		$badge.text(String(g_promptRequestsPanelItems.length)).show();
		updatePromptResultsToolbarButton();
	}

	function openPromptRequestsPanel() {
		var $panel = ensurePromptRequestsPanel();
		renderPromptRequestsPanel({ hideIfEmpty: false });
		$panel.addClass('is-open');
	}

	function togglePromptRequestsPanel() {
		var $panel = ensurePromptRequestsPanel();
		if ($panel.hasClass('is-open')) {
			g_promptRequestsPanelUserClosed = true;
			$panel.removeClass('is-open');
			return;
		}
		syncPromptRequestsPanelFromUntouched();
		g_promptRequestsPanelUserClosed = false;
		openPromptRequestsPanel();
	}

	/** Make sure every untouched (icon-saved) result also appears in the panel as ready. */
	function syncPromptRequestsPanelFromUntouched() {
		g_promptDialogUntouchedKeys.forEach(function (key) {
			var exists = g_promptRequestsPanelItems.some(function (i) { return i.key === key; });
			if (!exists) {
				var parts = String(key).split(':');
				var info = promptRequestsPanelCellInfo({
					postId: parts[0],
					columnIndex: parts[1],
					isSelected: true
				});
				g_promptRequestsPanelItems.push({
					seq: ++g_promptRequestsPanelSeq,
					key: key,
					label: info.label,
					sub: info.sub,
					state: 'ready'
				});
			}
		});
	}

	/** Remove every panel row for the same cell (only one request per cell). */
	function promptRequestsPanelRemoveByKey(key) {
		if (!key) {
			return false;
		}
		var removed = false;
		for (var i = g_promptRequestsPanelItems.length - 1; i >= 0; i--) {
			if (g_promptRequestsPanelItems[i].key === key) {
				g_promptRequestsPanelItems.splice(i, 1);
				removed = true;
			}
		}
		return removed;
	}

	function promptRequestsPanelClearAll() {
		g_promptRequestsPanelItems = [];
		if (tableManipulator && typeof tableManipulator.clearAllApplyPromptWaiting === 'function') {
			tableManipulator.clearAllApplyPromptWaiting();
		}
		renderPromptRequestsPanel();
	}

	function getPromptRequestsPanelItem(key) {
		if (!key) {
			return null;
		}
		for (var i = 0; i < g_promptRequestsPanelItems.length; i++) {
			if (g_promptRequestsPanelItems[i].key === key) {
				return g_promptRequestsPanelItems[i];
			}
		}
		return null;
	}

	/** Parked behind the concurrency cap — show it as "waiting". Auto-opens the panel. */
	function promptRequestsPanelAddWaiting(payload) {
		var key = payload && payload.table ? promptRequestsPanelKeyFromTable(payload.table) : null;
		if (!key) {
			return;
		}
		promptRequestsPanelRemoveByKey(key);
		var info = promptRequestsPanelCellInfo(payload.table);
		g_promptRequestsPanelItems.push({
			seq: ++g_promptRequestsPanelSeq,
			key: key,
			label: info.label,
			sub: info.sub,
			state: 'waiting'
		});
		if (!g_promptRequestsPanelUserClosed) {
			openPromptRequestsPanel();
		} else {
			renderPromptRequestsPanel();
		}
	}

	/** A request left the building — show it as loading. Auto-opens the panel. */
	function promptRequestsPanelAdd(payload) {
		var key = payload && payload.table ? promptRequestsPanelKeyFromTable(payload.table) : null;
		if (!key) {
			return;
		}
		promptRequestsPanelRemoveByKey(key);
		var info = promptRequestsPanelCellInfo(payload.table);
		g_promptRequestsPanelItems.push({
			seq: ++g_promptRequestsPanelSeq,
			key: key,
			label: info.label,
			sub: info.sub,
			state: 'loading'
		});
		if (!g_promptRequestsPanelUserClosed) {
			openPromptRequestsPanel();
		} else {
			renderPromptRequestsPanel();
		}
	}

	/** Its response arrived and waits for the user. */
	function promptRequestsPanelMarkReady(tableSnapshot, response) {
		var key = promptRequestsPanelKeyFromTable(tableSnapshot);
		if (!key) {
			return;
		}
		var logId = getApplyPromptLogIdFromResponse(response);
		var item = null;
		for (var i = 0; i < g_promptRequestsPanelItems.length; i++) {
			if (g_promptRequestsPanelItems[i].key === key && g_promptRequestsPanelItems[i].state === 'loading') {
				item = g_promptRequestsPanelItems[i];
				break;
			}
		}
		if (item) {
			item.state = 'ready';
			if (logId) {
				item.logId = logId;
			}
		} else {
			promptRequestsPanelRemoveByKey(key);
			var info = promptRequestsPanelCellInfo(tableSnapshot);
			g_promptRequestsPanelItems.push({
				seq: ++g_promptRequestsPanelSeq,
				key: key,
				label: info.label,
				sub: info.sub,
				state: 'ready',
				logId: logId || 0
			});
		}
		// Generation done for this cell — free its slot so a waiting request can start.
		releaseApplyPromptSlot(key);
		renderPromptRequestsPanel();
	}

	function promptRequestsPanelMarkError(payload, response) {
		var key = payload && payload.table ? promptRequestsPanelKeyFromTable(payload.table) : null;
		if (!key) {
			return;
		}
		var item = getPromptRequestsPanelItem(key);
		var info = payload && payload.table ? promptRequestsPanelCellInfo(payload.table) : { label: 'Cell', sub: '' };
		if (!item) {
			item = {
				seq: ++g_promptRequestsPanelSeq,
				key: key,
				label: info.label,
				sub: info.sub
			};
			g_promptRequestsPanelItems.push(item);
		}
		item.label = item.label || info.label;
		item.sub = item.sub || info.sub;
		item.state = 'error';
		item.errorMessage = getApplyPromptErrorMessage(response);
		item.errorDetailText = getApplyPromptErrorDetailText(response);
		item.logId = getApplyPromptLogIdFromResponse(response);
		releaseApplyPromptSlot(key);
		renderPromptRequestsPanel();
	}

	/**
	 * Request finished without a pending result (error / plain message / applied straight
	 * into the open dialog): drop its loading row. Image jobs still cooking on the server
	 * (status queued/in_progress) keep spinning.
	 */
	function promptRequestsPanelSettle(payload, response) {
		if (response && (response.status === 'queued' || response.status === 'in_progress')) {
			return;
		}
		if (!response || response.success === false) {
			promptRequestsPanelMarkError(payload, response);
			return;
		}
		var key = payload && payload.table ? promptRequestsPanelKeyFromTable(payload.table) : null;
		if (!key) {
			return;
		}
		for (var i = 0; i < g_promptRequestsPanelItems.length; i++) {
			if (g_promptRequestsPanelItems[i].key === key && g_promptRequestsPanelItems[i].state === 'loading') {
				g_promptRequestsPanelItems.splice(i, 1);
				// Terminal (error / plain message) — free its slot for the next waiting request.
				releaseApplyPromptSlot(key);
				renderPromptRequestsPanel();
				return;
			}
		}
	}

	/** The user acted on a result (Replace / Insert / Apply / Discard) — remove it. */
	function promptRequestsPanelRemoveReadyByKey(key) {
		if (!key) {
			return;
		}
		if (promptRequestsPanelRemoveByKey(key)) {
			renderPromptRequestsPanel();
		}
	}

	/** Panel row click: open the prompt result dialog for that cell (queued or icon-saved). */
	function openPromptResultFromPanelByKey(key) {
		if (!tableManipulator) {
			return;
		}
		g_promptRequestsPanelFocusKey = null;
		if (isPromptReplaceDialogOpen() && window.ubaiPrompts && typeof window.ubaiPrompts.hidePromptReplaceDialog === 'function') {
			window.ubaiPrompts.hidePromptReplaceDialog({ keepPendingCount: true });
		}
		// still waiting in the show-queue? take it out and show it directly
		for (var i = 0; i < g_applyPromptResponseQueue.length; i++) {
			var queued = g_applyPromptResponseQueue[i];
			if (promptRequestsPanelKeyFromTable(queued.tableSnapshot) === key) {
				g_applyPromptResponseQueue.splice(i, 1);
				g_applyPromptQueueBusy = false;
				var opened = showQueuedApplyPromptDialogForItem(queued);
				if (opened && tableManipulator.clearPromptDialogPendingForTable && queued.tableSnapshot) {
					tableManipulator.clearPromptDialogPendingForTable(queued.tableSnapshot);
				}
				updatePromptReplaceDialogQueueCounter();
				updatePromptResultsToolbarButton();
				renderPromptRequestsPanel();
				return;
			}
		}
		// otherwise it is stored on the cell (reopen icon data)
		var $cell = tableManipulator.findCellByPendingPromptResultKey(key);
		if ($cell.length && typeof tableManipulator.openDiscardedPendingPromptResult === 'function') {
			tableManipulator.openDiscardedPendingPromptResult($cell);
		}
		renderPromptRequestsPanel();
	}

	function openUntouchedPromptDialogFromToolbar() {
		if (!tableManipulator || isPromptReplaceDialogOpen()) {
			return;
		}
		if (g_applyPromptResponseQueue.length) {
			processApplyPromptDialogQueue();
			updatePromptResultsToolbarButton();
			return;
		}
		var index = g_promptDialogQueueClosedCount;
		if (index < 0) {
			index = 0;
		}
		openUntouchedPromptDialogAtIndex(index);
	}

	function openUntouchedPromptDialogAtIndex(index) {
		if (!tableManipulator) {
			return;
		}
		if (index < 0) {
			index = 0;
		}
		var key = g_promptDialogUntouchedKeys[index];
		if (!key) {
			return;
		}
		var $cell = tableManipulator.findCellByPendingPromptResultKey(key);
		if (!$cell.length) {
			return;
		}
		g_promptDialogQueueClosedCount = index;
		if (typeof tableManipulator.openDiscardedPendingPromptResult === 'function') {
			tableManipulator.openDiscardedPendingPromptResult($cell);
		}
		updatePromptReplaceDialogQueueCounter();
		updatePromptResultsToolbarButton();
	}

	function goToNextUntouchedPromptDialog() {
		var untouchedTotal = getPromptDialogUntouchedCount();
		if (untouchedTotal <= 1) {
			return;
		}

		var nextIndex = isPromptReplaceDialogOpen()
			? (g_promptDialogQueueClosedCount + 1)
			: g_promptDialogQueueClosedCount;
		if (nextIndex >= untouchedTotal) {
			nextIndex = 0;
		}

		g_promptDialogQueueNavigatingNext = true;
		if (isPromptReplaceDialogOpen() && window.ubaiPrompts && typeof window.ubaiPrompts.hidePromptReplaceDialog === 'function') {
			window.ubaiPrompts.hidePromptReplaceDialog();
		}
		g_promptDialogQueueNavigatingNext = false;

		g_promptDialogQueueClosedCount = nextIndex;
		g_applyPromptQueueBusy = false;

		if (g_applyPromptResponseQueue.length) {
			processApplyPromptDialogQueue();
			updatePromptReplaceDialogQueueCounter();
			updatePromptResultsToolbarButton();
			return;
		}

		openUntouchedPromptDialogAtIndex(nextIndex);
	}

	function getPromptDialogUntouchedCount() {
		var total = Number(g_promptDialogQueueTotal || 0);
		var waiting = g_applyPromptResponseQueue ? g_applyPromptResponseQueue.length : 0;
		if (isPromptReplaceDialogOpen()) {
			return Math.max(total, waiting + 1);
		}
		return Math.max(total, waiting);
	}

	function updatePromptReplaceDialogQueueCounter() {
		if (!window.ubaiPrompts || typeof window.ubaiPrompts.setPromptReplaceDialogQueueCounter !== "function") {
			updatePromptResultsToolbarButton();
			return;
		}
		var untouchedTotal = getPromptDialogUntouchedCount();
		if (untouchedTotal <= 1) {
			if (typeof window.ubaiPrompts.clearPromptReplaceDialogQueueCounter === "function") {
				window.ubaiPrompts.clearPromptReplaceDialogQueueCounter();
			}
			updatePromptResultsToolbarButton();
			return;
		}
		var current = isPromptReplaceDialogOpen() ? (g_promptDialogQueueClosedCount + 1) : g_promptDialogQueueClosedCount;
		if (current < 1) {
			current = 1;
		}
		if (current > untouchedTotal) {
			current = untouchedTotal;
		}
		window.ubaiPrompts.setPromptReplaceDialogQueueCounter(current, untouchedTotal);
		updatePromptResultsToolbarButton();
	}

	function registerPromptDialogQueueItem(tableSnapshot) {
		if (g_promptDialogQueueTotal <= g_promptDialogQueueClosedCount) {
			g_promptDialogQueueTotal = 0;
			g_promptDialogQueueClosedCount = 0;
		}
		g_promptDialogQueueTotal++;
		trackPromptDialogUntouchedKey(tableSnapshot);
		updatePromptReplaceDialogQueueCounter();
	}

	function extractApplyPromptTextFromPayload(payload) {
		var insertText = '';
		var displayText = '';
		var blocks = null;

		if (Array.isArray(payload)) {
			insertText = JSON.stringify(payload);
			displayText = formatRepeaterRowsForPromptDisplay(payload);
		} else if (payload && typeof payload === 'object') {
			insertText = typeof payload.insert === 'string' ? payload.insert : '';
			displayText = typeof payload.show === 'string' ? payload.show : insertText;
			if (payload.blocks && typeof payload.blocks === 'object') {
				blocks = payload.blocks;
			}
		} else {
			insertText = getApplyPromptReplacementValue(payload) || '';
			displayText = insertText;
		}

		if (insertText === null || insertText === '') {
			return null;
		}

		return {
			insertText: insertText,
			displayText: displayText,
			blocks: blocks
		};
	}

	function buildDiscardedPromptResultData(type, response, tableSnapshot) {
		if (!tableSnapshot) {
			return null;
		}

		if (type === 'pending_image') {
			var imageData = response && response.data ? response.data : {};
			if (!imageData.request_id || !imageData.preview_url) {
				return null;
			}
			return {
				type: 'image',
				requestId: imageData.request_id,
				previewUrl: imageData.preview_url,
				postId: imageData.post_id,
				column: imageData.column,
				tableSnapshot: tableSnapshot
			};
		}

		if (type === 'replace_text') {
			var textParts = extractApplyPromptTextFromPayload(response ? response.data : null);
			if (!textParts) {
				return null;
			}
			return {
				type: 'text',
				displayText: textParts.displayText,
				insertText: textParts.insertText,
				blocks: textParts.blocks,
				tableSnapshot: tableSnapshot
			};
		}

		return null;
	}

	function storeApplyPromptDialogResultForCell(type, response, tableSnapshot) {
		if (!tableManipulator || !tableSnapshot || typeof tableManipulator.getCellFromApplyPromptTable !== 'function') {
			return;
		}
		var discardedData = buildDiscardedPromptResultData(type, response, tableSnapshot);
		if (!discardedData || typeof tableManipulator.setDiscardedPendingPromptResult !== 'function') {
			return;
		}
		var $cell = tableManipulator.getCellFromApplyPromptTable(tableSnapshot);
		if (!$cell || !$cell.length) {
			return;
		}
		tableManipulator.setDiscardedPendingPromptResult($cell, discardedData);
	}

	function showQueuedApplyPromptDialogForItem(item) {
		if (!window.ubaiPrompts || !item) {
			return false;
		}

		var tableSnapshot = item.tableSnapshot;
		var response = item.response || {};
		var $cell = tableSnapshot && tableManipulator && typeof tableManipulator.getCellFromApplyPromptTable === 'function'
			? tableManipulator.getCellFromApplyPromptTable(tableSnapshot)
			: jQuery();

		if (!$cell.length) {
			return false;
		}

		if (tableManipulator && typeof tableManipulator.selectCellFromApplyPromptTable === 'function') {
			tableManipulator.selectCellFromApplyPromptTable(tableSnapshot, { suppressPromptDialogClose: true });
		}

		if (item.applyPayload && window.ubaiPrompts.setLastApplyPromptPayload) {
			window.ubaiPrompts.setLastApplyPromptPayload(item.applyPayload);
		}

		if (item.type === 'pending_image') {
			if (response.data && response.data.request_id && response.data.preview_url) {
				window.ubaiPrompts.setPromptReplaceDialogImagePreview(
					response.data.request_id,
					response.data.preview_url,
					response.data.post_id,
					response.data.column,
					response.data
				);
				window.ubaiPrompts.showPromptReplaceDialogForCell($cell, { reopenDiscarded: true });
				return window.ubaiPrompts.isPromptReplaceDialogOpen();
			}
			return false;
		}

		if (item.type === 'replace_text') {
			var textParts = extractApplyPromptTextFromPayload(response.data);
			if (!textParts) {
				g_doublyAdmin.showErrorMessage('Apply prompt did not return replacement text.');
				return false;
			}
			setApplyPromptReplaceDialogFromPayload(response.data);
			window.ubaiPrompts.showPromptReplaceDialogForCell($cell, { reopenDiscarded: true });
			return window.ubaiPrompts.isPromptReplaceDialogOpen();
		}

		return false;
	}

	function processApplyPromptDialogQueue() {
		if (g_applyPromptQueueBusy || !window.ubaiPrompts || isPromptReplaceDialogOpen()) {
			return;
		}
		if (!g_applyPromptResponseQueue.length) {
			return;
		}

		var item = g_applyPromptResponseQueue.shift();
		var tableSnapshot = item.tableSnapshot;

		g_applyPromptQueueBusy = true;
		var dialogOpened = showQueuedApplyPromptDialogForItem(item);

		if (dialogOpened) {
			updatePromptReplaceDialogQueueCounter();
			if (tableManipulator && tableManipulator.clearPromptDialogPendingForTable && tableSnapshot) {
				tableManipulator.clearPromptDialogPendingForTable(tableSnapshot);
			}
		}

		g_applyPromptQueueBusy = false;
		if (!isPromptReplaceDialogOpen()) {
			processApplyPromptDialogQueue();
		}
	}

	function enqueueApplyPromptDialogResponse(type, response, requestPayload) {
		var applyPayload = requestPayload || g_lastApplyPromptPayload || null;
		var tableSnapshot = applyPayload && applyPayload.table ? applyPayload.table : null;

		// If the replace dialog is already open for the same target cell (e.g. "regenerate" action),
		// update it immediately instead of queueing until the dialog closes.
		if (isPromptReplaceDialogOpen() && window.ubaiPrompts && typeof window.ubaiPrompts.getPromptReplaceDialogTargetCell === "function") {
			var $dlgCell = window.ubaiPrompts.getPromptReplaceDialogTargetCell();
			var dlgPostId = $dlgCell && $dlgCell.length ? ($dlgCell.closest('tr').data('id') || $dlgCell.data('row') || null) : null;
			var dlgColIdx = $dlgCell && $dlgCell.length ? $dlgCell.data('col') || null : null;

			var snapPostId = tableSnapshot ? tableSnapshot.postId : null;
			var snapColIdx = tableSnapshot ? tableSnapshot.columnIndex : null;

			var matchesDlgCell = (
				snapPostId != null &&
				snapColIdx != null &&
				dlgPostId != null &&
				dlgColIdx != null &&
				String(snapPostId) === String(dlgPostId) &&
				Number(snapColIdx) === Number(dlgColIdx)
			);

			if (matchesDlgCell) {
				if (tableManipulator && tableManipulator.clearPromptDialogPendingForTable && tableSnapshot) {
					tableManipulator.clearPromptDialogPendingForTable(tableSnapshot);
				}
				storeApplyPromptDialogResultForCell(type, response, tableSnapshot);
				// result went straight into the open dialog — its loading row is done
				promptRequestsPanelSettle(applyPayload, null);
				var $snapCell = tableManipulator && typeof tableManipulator.getCellFromApplyPromptTable === 'function'
					? tableManipulator.getCellFromApplyPromptTable(tableSnapshot)
					: jQuery();
				if ($snapCell.length && tableManipulator && typeof tableManipulator.selectCellFromApplyPromptTable === 'function') {
					tableManipulator.selectCellFromApplyPromptTable(tableSnapshot, { suppressPromptDialogClose: true });
				}
				if (window.ubaiPrompts && typeof window.ubaiPrompts.setLastApplyPromptPayload === "function") {
					window.ubaiPrompts.setLastApplyPromptPayload(applyPayload);
				}

				if (type === "pending_image") {
					if (response && response.data && response.data.request_id && response.data.preview_url) {
						window.ubaiPrompts.setPromptReplaceDialogImagePreview(
							response.data.request_id,
							response.data.preview_url,
							response.data.post_id,
							response.data.column,
							response.data
						);
						if ($snapCell.length && typeof window.ubaiPrompts.showPromptReplaceDialogForCell === 'function') {
							window.ubaiPrompts.showPromptReplaceDialogForCell($snapCell, { reopenDiscarded: true });
						} else {
							window.ubaiPrompts.showPromptReplaceDialogForSelection();
						}
					}
					return;
				}

				if (type === "replace_text") {
					var payload = response ? response.data : null;
					if (!extractApplyPromptTextFromPayload(payload)) {
						g_doublyAdmin.showErrorMessage("Apply prompt did not return replacement text.");
						return;
					}

					setApplyPromptReplaceDialogFromPayload(payload);
					if ($snapCell.length && typeof window.ubaiPrompts.showPromptReplaceDialogForCell === 'function') {
						window.ubaiPrompts.showPromptReplaceDialogForCell($snapCell, { reopenDiscarded: true });
					} else {
						window.ubaiPrompts.showPromptReplaceDialogForSelection();
					}
					return;
				}
			}
		}
		storeApplyPromptDialogResultForCell(type, response, tableSnapshot);
		registerPromptDialogQueueItem(tableSnapshot);
		g_applyPromptResponseQueue.push({
			type: type,
			response: response,
			applyPayload: applyPayload,
			tableSnapshot: tableSnapshot
		});
		promptRequestsPanelMarkReady(tableSnapshot, response);
		processApplyPromptDialogQueue();
	}

	/**
	 * Clear apply-prompt cell loader after request finishes (success or error).
	 * @param {Object} payload - apply_prompt payload with table snapshot.
	 * @param {boolean} useDialogCell - true when targeting the replace-dialog cell (regenerate).
	 */
	function clearApplyPromptCellLoading(payload, useDialogCell) {
		if (!payload || !payload.table) {
			return;
		}
		if (tableManipulator && tableManipulator.setCellApplyPromptLoading) {
			tableManipulator.setCellApplyPromptLoading(false, !!useDialogCell, payload.table);
		}
	}

	function clearApplyPromptRegenerateLoading() {
		if (window.ubaiPrompts && window.ubaiPrompts.setRegenerateLoading) {
			window.ubaiPrompts.setRegenerateLoading(false);
		}
	}

	/**
	 * Runs after every apply_prompt AJAX request (success or error).
	 * @param {Object|null} response - Parsed AJAX response when available.
	 * @param {Object} payload - Original apply_prompt payload.
	 * @param {boolean} useDialogCell - true when targeting the replace-dialog cell (regenerate).
	 */
	function finishApplyPromptRequest(response, payload, useDialogCell) {
		if (response && (response.status === 'queued' || response.status === 'in_progress')) {
			clearApplyPromptCellLoading(payload, useDialogCell);
		} else {
			clearCellPromptActivityForPayload(payload);
		}
		if (useDialogCell) {
			clearApplyPromptRegenerateLoading();
		}
		// request ended: if no pending result arrived for it (error / plain message),
		// drop its loading row from the requests panel; queued image jobs keep spinning
		promptRequestsPanelSettle(payload, response);
		if (response) {
			updateApplyPromptDebug(response);
		}
	}

	/* Routes apply prompt response actions.
	 * @param {Object} response - AJAX response.
	 * @param {Object} [requestPayload] - Original apply_prompt payload (so selection matches the cell the request was for).
	 */
	function handleApplyPromptResponse(response, requestPayload) {

		if (!response || response.success === false) {
			return;
		}
		if (response.promptHistory && window.ubaiPrompts && window.ubaiPrompts.updatePromptHistoryFromResponse) {
			window.ubaiPrompts.updatePromptHistoryFromResponse(response.promptHistory);
		}

		var tableSnapshot = (requestPayload && requestPayload.table) ? requestPayload.table : (g_lastApplyPromptPayload && g_lastApplyPromptPayload.table);

		switch (response.action) {
			case "show_message":
				var messageText = response.data || "";
				var reasonText = response.reason || "";
				if (typeof messageText !== "string") {
					messageText = "";
				}
				if (typeof reasonText !== "string") {
					reasonText = "";
				}
				var displayText = reasonText || messageText;
				if (displayText) {
					updatePromptTextResponse(displayText);
					g_doublyAdmin.showSuccessMessage(displayText);
				}

				return;
			case "pending_image":
				// Always queue the replace dialog when we have a pending preview.
				// Do not skip when the cell already has a real image — edit-image
				// always starts from an existing attachment, and that used to hide
				// the Apply/Discard dialog after a successful edit response.
				if (tableManipulator && tableManipulator.markPromptDialogPendingForTable && tableSnapshot) {
					tableManipulator.markPromptDialogPendingForTable(tableSnapshot);
				}
				enqueueApplyPromptDialogResponse("pending_image", response, requestPayload);
				return;
			case "replace_text":
				if (tableManipulator && tableManipulator.markPromptDialogPendingForTable && tableSnapshot) {
					tableManipulator.markPromptDialogPendingForTable(tableSnapshot);
				}
				enqueueApplyPromptDialogResponse("replace_text", response, requestPayload);
				return;
		}
	}

	/* Extracts replacement text from apply prompt response data. */
	function getApplyPromptReplacementValue(data) {

		if (typeof data !== "string") {
			return null;
		}
		return data;
	}

	function formatRepeaterRowsForPromptDisplay(rows) {
		if (!Array.isArray(rows) || !rows.length) {
			return "";
		}

		var lines = [rows.length + (rows.length === 1 ? " repeater row:" : " repeater rows:"), ""];
		rows.forEach(function (row, index) {
			if (!row || typeof row !== "object" || Array.isArray(row)) {
				return;
			}

			lines.push("Row " + (index + 1));
			Object.keys(row).forEach(function (key) {
				var value = row[key];
				if (value !== null && typeof value === "object") {
					value = JSON.stringify(value);
				}
				lines.push("  " + key + ": " + String(value));
			});

			if (index < rows.length - 1) {
				lines.push("");
			}
		});

		return lines.join("\n");
	}

	function setApplyPromptReplaceDialogFromPayload(payload) {
		if (!window.ubaiPrompts || typeof window.ubaiPrompts.setPromptReplaceDialogText !== 'function') {
			return;
		}

		var textParts = extractApplyPromptTextFromPayload(payload);
		if (!textParts) {
			return;
		}

		window.ubaiPrompts.setPromptReplaceDialogText(textParts.displayText, textParts.insertText, textParts.blocks);
	}

	/**
	 * Put a column immediately after another column in a saved-order array.
	 *
	 * @param {string[]} order
	 * @param {string} columnName
	 * @param {string} afterName
	 * @param {boolean} forceAdd
	 * @return {string[]}
	 */
	function placeColumnAfter(order, columnName, afterName, forceAdd) {
		if (!Array.isArray(order) || !columnName) {
			return Array.isArray(order) ? order.slice() : [];
		}

		var exists = order.indexOf(columnName) !== -1;
		var next = order.filter(function (name) {
			return name !== columnName;
		});
		var afterIndex = next.indexOf(afterName);

		if (afterIndex !== -1) {
			next.splice(afterIndex + 1, 0, columnName);
			return next;
		}

		if (forceAdd || exists) {
			next.push(columnName);
		}

		return next;
	}

	/**
	* Sort modal list based on column order
	*/
	function sortByNameOrder(items, order) {

		const orderMap = {};
		order.forEach((name, index) => {
			orderMap[name] = index;
		});

		return items
			.filter(item => orderMap[item.name] !== undefined)
			.sort((a, b) => orderMap[a.name] - orderMap[b.name]);
	}

	/*
	* Function to initiate columns popup orderable
	*/
	function initiatePopupColumnsOrder() {

		jQuery(g_ubaiPopupFieldsList).sortable({
			//items: g_columnSelectorPopupItemSelector,
			axis: 'y',          // vertical only
			items: 'div.unlimitedai-plugin__columns-selector__drawer-item:not(:nth-child(1)):not(:nth-child(2)):not(:nth-child(3)):not(:nth-child(4))',
			cursor: 'move',
			helper: "clone",
			//cancel: ':nth-child(-n+3)',
			scroll: true,
			tolerance: "pointer",
			update: function () {
				setTimeout(function () {
					var result = [];

					jQuery(g_ubaiPopupFieldsList + ' ' + g_columnSelectorPopupItemSelector).each(function (index) {
						result.push(jQuery(this).data('index'));
					});
					g_objUbaiSelectedColumnsOrderInput.val(JSON.stringify(result));

					applyColumnOrder(init_columns);
					saveColumnOrder(g_objSpreadsheet)
				}, 100)

			}
		});
		
	}



	/**
	* on th right click
	*/
	function onTHRightClick(e) {
		if (g_isContextOff == '1') { return; }
		e.preventDefault();

		var objTH = columnModalPointer = jQuery(e.currentTarget).parents('th');
		var objTable = jQuery(`#${g_spreadsheetContainerID}`);

		var dataName = objTH.data("name");
		var columnIndex = objTH.index();

		const result_structure_info = g_tableStructure.find(item => item.name === dataName);

		// Remove any existing custom menus (body children only; avoid full-DOM scan)
		objTable.children('.' + g_contextMenuClass).remove();

		if (result_structure_info.name == 'bulk')
			return (true)

		// Create the menu element
		var menu_string = `
        <div class="${g_contextMenuClass}">
			<div class="${g_contextMenuClass}__item ${g_contextMenuClass}__rename_column">
                <span class="${g_contextMenuClass}__item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil w-4 h-4 mr-2"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>
                <span class="${g_contextMenuClass}__item-text">${sheetspilot.editor.rename_column}</span>
            </div>`;

		if (result_structure_info.switchable != false) {
			menu_string += `
            <div class="${g_contextMenuClass}__item  ${g_contextMenuClass}__hide_column">
                <span class="${g_contextMenuClass}__item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"></path><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"></path><path d="m2 2 20 20"></path></svg></span>
                <span class="${g_contextMenuClass}__item-text">${sheetspilot.editor.hide_column}</span>
            </div>`;
		}


		if (jQuery(e.currentTarget).closest('th').outerWidth() != result_structure_info.width) {

			menu_string += `
            <div class="${g_contextMenuClass}__item  ${g_contextMenuClass}__reset_width">
                <span class="${g_contextMenuClass}__item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg></span>
                <span class="${g_contextMenuClass}__item-text">${sheetspilot.editor.reset_width}</span>
            </div>`;
		}


		const column_data = init_columns.find(obj => obj.name === dataName);
		menu_string += `
            <div class="${g_contextMenuClass}__item_info d-flex justify-content-between align-items-center">
								<div class="${g_contextMenuClass}__item_info-inner d-flex flex-column">
									<span class="${g_contextMenuClass}__item_info-type d-block">${columnSlugBeauty(dataName)}</span>
									<span class="${g_contextMenuClass}__item_info-meta d-block">${columnTypeBeauty(column_data.dev_type)}</span>
								</div>
                <button class="unlimitedai-plugin__btn ${g_contextMenuClass}__item_copy_btn copy_text"><svg class="size-14" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-copy w-3.5 h-3.5"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path></svg></button>
            </div>`;

		menu_string += `
        </div>`;

		var objMenu = jQuery(menu_string);

		// jQuery('body').append(objMenu);
		objTable.append(objMenu);

		var containerOffset = objTable.offset();

		// Calculate raw mouse position relative to the scrollable table container canvas
		var mouseX = e.pageX - containerOffset.left + objTable.scrollLeft();
		var mouseY = e.pageY - containerOffset.top + objTable.scrollTop();

		var menuWidth = objMenu.outerWidth();
		var containerWidth = objTable.outerWidth();
		var scrollLeft = objTable.scrollLeft();

		// Define strict boundaries based on the visible scrolling pane
		var visibleLeftEdge = scrollLeft;
		var visibleRightEdge = scrollLeft + containerWidth;

		// Right Edge Collision: If it goes past the visible right view, push it left
		if (mouseX + menuWidth > visibleRightEdge) {
			mouseX = mouseX - menuWidth;
		}

		// Left Edge/RTL Collision Safeguard: Ensure it never clips past the visible left view
		if (mouseX < visibleLeftEdge) {
			mouseX = visibleLeftEdge;
		}

		// Apply the bounded coordinates safely
		objMenu.css({
			position: 'absolute',
			top: mouseY + 'px',
			left: mouseX + 'px',
			zIndex: 9999
		});

		// Handle the click on "Hide column"
		objMenu.on('click', `.${g_contextMenuClass}__hide_column`, function () {
			var objColumnSelectorItemToUncheck = g_objPostsEditor.find(".unlimitedai-plugin__columns-selector__drawer-item [data-index='" + dataName + "']");

			objColumnSelectorItemToUncheck.prop('checked', false);

			syncColumnVisibilityAndSave();

			objMenu.remove();
		});

		// Handle the click on "Drow Width"
		objMenu.on('click', `.${g_contextMenuClass}__reset_width`, function () {
			var objColumnSelectorItemToUncheck = g_objPostsEditor.find(".unlimitedai-plugin__columns-selector__drawer-item [data-index='" + dataName + "']");

			objColumnSelectorItemToUncheck.prop('checked', false);
			const column_data = g_tableStructure.find(obj => obj.name === dataName);
			setColumnWidth(dataName, column_data.width)

			saveTableColumnWidths();

			objMenu.remove();
		});
	}

	/**
	 * function to initialize posts table
	 */
	this.initiateDataTable = function ($data_array, $table_structure) {

		if (sheetspilot.editor.g_isLogOn == 1) {
			console.log( $data_array );
			console.log( $table_structure );
		}

		// IDs of keys of data returned
		let allowedKeys = [];
		jQuery.each($data_array[0], function (key, value) {
			var key = Object.keys(value)[0];
			allowedKeys.push(key);
		})

		if (sheetspilot.editor.g_isLogOn == 1) {
			console.log( allowedKeys );
		}

		// check for new fields and add to order
		let savedOrder = jQuery.parseJSON(g_objUbaiSelectedColumnsOrderInput.val());
		const visible_columns = jQuery.parseJSON(g_objUbaiSelectedColumnsInput.val());
		if (!Array.isArray(savedOrder)) {
			savedOrder = [];
		}

		if (savedOrder.length <= 3 ) {
			savedOrder = allowedKeys.slice();
			g_objUbaiSelectedColumnsOrderInput.val(JSON.stringify(savedOrder));
		}
		if ( visible_columns.length <= 3) {
			g_objUbaiSelectedColumnsInput.val(JSON.stringify(allowedKeys));
		}

		const active_keys = allowedKeys;

		if (allowedKeys.length > 0) {
			$table_structure = $table_structure.filter(col =>
				allowedKeys.includes(col.name)
			);
		}

		initialFilteredColumns = $table_structure;

		const diff = active_keys.filter(x => !savedOrder.includes(x));

		if (diff.length > 0) {
			jQuery.each(diff, function (index, value) {
				if (value === 'elementor_active') {
					return;
				}
				savedOrder.push(value);
			});
		}

		savedOrder = placeColumnAfter(
			savedOrder,
			'elementor_active',
			'post_content',
			active_keys.indexOf('elementor_active') !== -1
		);
		g_objUbaiSelectedColumnsOrderInput.val(JSON.stringify(savedOrder));
		if (diff.length > 0) {
			saveColumnOrder(g_objSpreadsheet);
		}
 
		// for global usage
		columnsListGlobal = init_columns = $table_structure;
		// generate header row

		tableManipulator = new SheetsPilot_CellProcessing('#new_output_table', $table_structure);
		tableManipulator.generateHeader();
		tableManipulator.fillCellsWithContent($data_array);


		tableManipulator.onEvent(tableManipulator.g_events.SELECTION_CHANGE, updateApplyPromptButtonState);
		updateApplyPromptButtonState();
		g_objPostsEditor.on('ubai-sidebar-mode-changed', updateApplyPromptButtonState);
		g_objPostsEditor.off('change.ubaiImageActionSelect', '#ubai_image_action_select');
		g_objPostsEditor.on('change.ubaiImageActionSelect', '#ubai_image_action_select', updateApplyPromptButtonState);

		initColumnsSelectorPopupList();
 
		makeColumnsSortable();
 
		bindAllTableActionsAfterReload( $table_structure );

		//tableManipulator.initUploadFeaturedImageCells();
		//initUploadFeaturedImageCells();

		jQuery("#new_output_table").trigger("table_initialized");

	}

	function initiateSelect2Inputs() {

		// initiate post object editor select2
		var obj_postObjectEditorInput = g_objPostsEditor.find(g_postObjectEditorInput);
		var obj_acfSelectEditorInput = g_objPostsEditor.find(g_acfSelectEditorInput);
		var obj_tagSelectEditorInput = g_objPostsEditor.find(g_tagSelectEditorInput);

		obj_postObjectEditorInput.each(function () {


			var parent = jQuery(this).parents(g_editorContainer);
			var parent_td = jQuery(this).parents('td');

			jQuery(this).select2({
				width: '100%',
				templateSelection: formatState,
				allowClear: true,
				placeholder: 'Select option',
				dropdownParent: parent_td,
				ajax: {
					url: g_urlAjaxActionsSheetsPilot,
					type: 'POST',
					//dataType: 'json',
					data: function (params) {
						var select2Data = {
							action: "sheetspilot_ajax_actions",
							search_post_type: jQuery(this).attr('data-search_post_type'),
							client_action: 'get_posts_select2',
							field_name: parent.data('column'),
							post_id: parent_td.data('row'),
							q: params.term,
							page: params.page || 1,
							nonce: g_doublyNonce,
							data: {
								field_name: parent.data('column'),
								post_id: parent_td.data('row'),
								q: params.term,
								page: params.page || 1,
							}
						};
						if (typeof g_showdebug !== 'undefined' && g_showdebug) {
							select2Data.showdebug = true;
						}
						return select2Data;
					},
					nonce: g_doublyNonce,
					placeholder: 'Search for post',

					processResults: function (response, params) {
						params.page = params.page || 1;
						return {
							results: response.items,
							pagination: {
								more: response.more
							}
						};
					}
				},
				minimumInputLength: 1
			});
			function formatState(state) {
				if (!state.id) {
					return state.text;
				}
				var $state = jQuery(
					'<span></span>'
				);

				// Use .text() instead of HTML string concatenation to avoid script injection issues
				$state.text(state.text);
				$state.addClass('stat_id_patch');
				$state.attr('data-id', state.id);

				return $state;
			};




		});
		obj_acfSelectEditorInput.each(function () {
			var parent = jQuery(this).parents(g_editorContainer);
			var parent_td = jQuery(this).parents('td');

			jQuery(this).select2({
				dropdownParent: parent_td,
				dropdownCssClass: 'acf_select_editor_input_dropdown',
				width: '100%',
				minimumResultsForSearch: Infinity,
				closeOnSelect: false,
			});

		});
		obj_tagSelectEditorInput.each(function () {
			var parent = jQuery(this).parents(g_editorContainer);
			var parent_td = jQuery(this).parents('td');

			jQuery(this).select2({
				dropdownParent: parent_td,
				tags: true,
				dropdownCssClass: 'post_tag_cell_td__dropdown',
				selectionCssClass: 'post_tag_cell_td__select',
				width: '100%',
				closeOnSelect: false,
			}).on('select2:select', function (e) {
				jQuery(this).parent().find('.select2-search__field').val('').trigger('change');
			});

		});


	}

	/**
	 * Sidebar Image tab: create vs edit action from #ubai_image_action_select.
	 * @return {string} 'create'|'edit'
	 */
	function getSidebarImageActionValue() {
		var $sel = g_objPostsEditor.find('#ubai_image_action_select');
		if (!$sel.length) {
			return 'create';
		}
		return $sel.val() === 'edit' ? 'edit' : 'create';
	}

	/**
	 * Parsed prompts strings object (hidden input is JSON).
	 * @return {Object}
	 */
	function getUbaiPromptsStringsObject() {
		var raw = typeof g_ubaiPromptsStrings !== 'undefined' ? g_ubaiPromptsStrings : null;
		if (raw && typeof raw === 'object') {
			return raw;
		}
		if (typeof raw === 'string' && raw.length) {
			try {
				return JSON.parse(raw);
			} catch (err) {
				return {};
			}
		}
		return {};
	}

	/**
	 * Read all four image sidebar controls (ratio, quality, format, resolution).
	 * @return {Object|null}
	 */
	function collectSidebarImageSettings() {
		var $panel = g_objPostsEditor.find('#ubai_sidebar_image_panel');
		if (!$panel.length) {
			return null;
		}
		var $ratioBox = $panel.find('.ubai-ratio-box').first();
		var $qualityBtn = $panel.find('.ubai-quality-selector__btn').first();
		var $formatBtn = $panel.find('.ubai-format-selector__btn').first();
		var $resolutionBtn = $panel.find('.ubai-resolution-selector__btn').first();

		var ratio = ($ratioBox.length && $ratioBox.attr('data-ratio')) ? String($ratioBox.attr('data-ratio')) : 'auto';
		var quality = ($qualityBtn.length && $qualityBtn.attr('data-quality')) ? String($qualityBtn.attr('data-quality')) : '';
		var format = ($formatBtn.length && $formatBtn.attr('data-format')) ? String($formatBtn.attr('data-format')) : '';
		var resolution = ($resolutionBtn.length && $resolutionBtn.attr('data-resolution')) ? String($resolutionBtn.attr('data-resolution')) : '';

		return {
			ratio: ratio || 'auto',
			quality: quality || 'default',
			format: format || 'default',
			resolution: resolution || 'default'
		};
	}

	/**
	 * Apply saved cell-rule image presets over sidebar values when set.
	 * @param {Object} imageSettings
	 * @param {string} columnName
	 * @return {Object}
	 */
	function applyCellRuleImagePresetsToSettings(imageSettings, columnName) {
		if (!imageSettings || !columnName || typeof g_ubaiCellRules === 'undefined') {
			return imageSettings;
		}
		var arKey = columnName + '__aspect_ratio';
		var qKey = columnName + '__quality';
		var fKey = columnName + '__format';
		var resKey = columnName + '__resolution';
		if (g_ubaiCellRules[arKey] && String(g_ubaiCellRules[arKey]).trim() !== '') {
			imageSettings.ratio = String(g_ubaiCellRules[arKey]);
		}
		if (g_ubaiCellRules[qKey] && String(g_ubaiCellRules[qKey]).trim() !== '' && String(g_ubaiCellRules[qKey]) !== 'default') {
			imageSettings.quality = String(g_ubaiCellRules[qKey]);
		}
		if (g_ubaiCellRules[fKey] && String(g_ubaiCellRules[fKey]).trim() !== '' && String(g_ubaiCellRules[fKey]) !== 'default') {
			imageSettings.format = String(g_ubaiCellRules[fKey]);
		}
		if (g_ubaiCellRules[resKey] && String(g_ubaiCellRules[resKey]).trim() !== '' && String(g_ubaiCellRules[resKey]) !== 'default') {
			imageSettings.resolution = String(g_ubaiCellRules[resKey]);
		}
		return imageSettings;
	}

	/**
	 * Sync sidebar image controls from saved cell-rule image settings of a column.
	 * Missing saved values intentionally map to defaults (auto/default/default/default).
	 *
	 * @param {string} columnName
	 */
	function syncSidebarImageControlsFromColumnRules(columnName) {
		if (!columnName) {
			return;
		}
		var $panel = g_objPostsEditor.find('#ubai_sidebar_image_panel');
		if (!$panel.length) {
			return;
		}

		var settings = applyCellRuleImagePresetsToSettings({
			ratio: 'auto',
			quality: 'default',
			format: 'default',
			resolution: 'default'
		}, String(columnName));

		var ratio = String(settings.ratio || 'auto');
		var quality = String(settings.quality || 'default').toLowerCase();
		var format = String(settings.format || 'default').toLowerCase();
		var resolution = String(settings.resolution || 'default').toLowerCase();

		if (quality === '0.5k') quality = 'low';
		else if (quality === '1k') quality = 'medium';
		else if (quality === '1.5k' || quality === '2k') quality = 'high';
		if (['default', 'low', 'medium', 'high'].indexOf(quality) < 0) {
			quality = 'default';
		}

		if (format === 'jpg') format = 'jpeg';
		if (['default', 'png', 'jpeg', 'webp'].indexOf(format) < 0) {
			format = 'default';
		}

		if (['default', '1k', '2k', '3k', '4k'].indexOf(resolution) < 0) {
			resolution = 'default';
		}

		var $ratioItem = $panel.find('.ubai-ratio-dropdown__item[data-ratio]').filter(function () {
			return String(jQuery(this).attr('data-ratio') || '') === ratio;
		}).first();
		if ($ratioItem.length) {
			$ratioItem.trigger('click');
		}

		var $qualityItem = $panel.find('.ubai-quality-dropdown__item[data-quality]').filter(function () {
			return String(jQuery(this).attr('data-quality') || '') === quality;
		}).first();
		if ($qualityItem.length) {
			$qualityItem.trigger('click');
		}

		var $formatItem = $panel.find('.ubai-format-dropdown__item[data-format]').filter(function () {
			return String(jQuery(this).attr('data-format') || '') === format;
		}).first();
		if ($formatItem.length) {
			$formatItem.trigger('click');
		}

		var $resolutionItem = $panel.find('.ubai-resolution-dropdown__item[data-resolution]').filter(function () {
			return String(jQuery(this).attr('data-resolution') || '') === resolution;
		}).first();
		if ($resolutionItem.length) {
			$resolutionItem.trigger('click');
		}

		if (window.ubaiPrompts && typeof window.ubaiPrompts.updateImageDefaultOptionLabels === 'function') {
			window.ubaiPrompts.updateImageDefaultOptionLabels($panel, columnName, true);
		}
	}

	/**
	 * Map legacy context-menu image_action values to create/edit.
	 * @param {string} menuAct
	 * @return {string} 'create'|'edit'|''
	 */
	function normalizeContextMenuImageAction(menuAct) {
		if (menuAct === 'generate-image') {
			return 'create';
		}
		if (menuAct === 'enhance-image' || menuAct === 'apply_column_rules') {
			return 'edit';
		}
		if (isChangeImageRatioContextAction(menuAct)) {
			return 'edit';
		}
		return '';
	}

	/**
	 * Context menu action for "Change Image Ratio" submenu (change-image-ratio-16-9, etc.).
	 * @param {string} menuAct
	 * @return {boolean}
	 */
	function isChangeImageRatioContextAction(menuAct) {
		return typeof menuAct === 'string' && menuAct.indexOf('change-image-ratio-') === 0;
	}

	/**
	 * On Image sidebar with "Edit image", hide ratio/quality/format/resolution controls (create-only).
	 * @param {boolean} isImageSidebarContext Image tab active, or active cell is an image column.
	 */
	function syncImageSidebarParametersVisibility(isImageSidebarContext) {
		var $params = g_objPostsEditor.find('#ubai_sidebar_image_panel .ubai-image-parameters__section');
		if (!$params.length) {
			return;
		}
		var hideForEdit = isImageSidebarContext === true && getSidebarImageActionValue() === 'edit';
		$params.toggle(!hideForEdit);
	}

	/**
	 * Swap image prompt placeholder for create vs edit action.
	 * @param {boolean} isImageSidebarContext Image tab active, or active cell is an image column.
	 */
	function syncImageSidebarPromptPlaceholder(isImageSidebarContext) {
		var $textarea = g_objPostsEditor.find('#ubai_image_prompt_text');
		if (!$textarea.length) {
			return;
		}
		var strings = getUbaiPromptsStringsObject();
		var createPlaceholder = strings.imagePromptPlaceholder || '';
		var editPlaceholder = strings.imagePromptEditPlaceholder || createPlaceholder;
		var useEdit = isImageSidebarContext === true && getSidebarImageActionValue() === 'edit';
		var placeholder = useEdit ? editPlaceholder : createPlaceholder;
		$textarea.attr('placeholder', placeholder);
		if (window.ubaiPrompts && typeof window.ubaiPrompts.updatePromptInputPlaceholder === 'function') {
			window.ubaiPrompts.updatePromptInputPlaceholder(placeholder, '#ubai_image_prompt_text');
		}
		if (window.ubaiPrompts && typeof window.ubaiPrompts.initPromptCodeMirror === 'function') {
			window.ubaiPrompts.initPromptCodeMirror('#ubai_image_prompt_text');
		}
	}

	/**
	 * On Image sidebar (tab or image cell selected) with "Edit image", hide shared apply checkboxes.
	 * @param {boolean} isImageSidebarContext Image tab active, or active cell is an image column.
	 * @return {boolean} True when the shared checkbox block is hidden.
	 */
	function syncImageSidebarSharedApplyOptionsVisibility(isImageSidebarContext) {
		var $sharedApplyOptions = g_objPostsEditor.find('#ubai_sidebar_shared_apply_options');
		if (!$sharedApplyOptions.length) {
			return false;
		}
		var hideForEdit = isImageSidebarContext === true && getSidebarImageActionValue() === 'edit';
		$sharedApplyOptions.toggle(!hideForEdit);
		return hideForEdit;
	}

	/**
	 * Resolve in-memory cell rules (object from AJAX or JSON string from hidden input).
	 *
	 * @return {Object|null}
	 */
	function getUbaiCellRulesObject() {
		if (typeof g_ubaiCellRules === 'undefined' || g_ubaiCellRules === null || g_ubaiCellRules === '') {
			g_ubaiCellRules = {};
			return g_ubaiCellRules;
		}
		if (typeof g_ubaiCellRules === 'object') {
			return g_ubaiCellRules;
		}
		if (typeof g_ubaiCellRules === 'string') {
			try {
				var parsed = JSON.parse(g_ubaiCellRules);
				g_ubaiCellRules = (parsed && typeof parsed === 'object') ? parsed : {};
				return g_ubaiCellRules;
			} catch (e) {
				g_ubaiCellRules = {};
				return g_ubaiCellRules;
			}
		}
		g_ubaiCellRules = {};
		return g_ubaiCellRules;
	}

	/**
	 * Raw cell rules for one column (storage keys: column + column__*).
	 *
	 * @param {string} columnName Column key (e.g. post_excerpt).
	 * @return {Object|null}
	 */
	function getUbaiCellRulesForColumn(columnName) {
		var rules = getUbaiCellRulesObject();
		if (!rules || !columnName) {
			return null;
		}
		columnName = String(columnName);
		var metaPrefix = columnName + '__';
		var filtered = {};
		Object.keys(rules).forEach(function (key) {
			if (key === columnName || key.indexOf(metaPrefix) === 0) {
				filtered[key] = rules[key];
			}
		});
		return Object.keys(filtered).length ? filtered : null;
	}

	/**
	 * Apply-prompt payload shape: unprefixed keys (rule, targets, prompt_on_paste, aspect_ratio, …).
	 *
	 * @param {string} columnName Column key (e.g. post_excerpt).
	 * @return {Object|null}
	 */
	function normalizeUbaiCellRulesForPayload(columnName) {
		var prefixed = getUbaiCellRulesForColumn(columnName);
		if (!prefixed) {
			return null;
		}
		columnName = String(columnName);
		var metaPrefix = columnName + '__';
		var out = {};
		if (prefixed[columnName] != null) {
			out.rule = prefixed[columnName];
		}
		Object.keys(prefixed).forEach(function (key) {
			if (key === columnName) {
				return;
			}
			if (key.indexOf(metaPrefix) === 0) {
				var field = key.slice(metaPrefix.length);
				if (field === 'targets') {
					return;
				}
				out[field] = prefixed[key];
			}
		});
		return Object.keys(out).length ? out : null;
	}

	/**
	 * When include_rules is true and the active column has saved rules, attach cell_rules for PHP.
	 *
	 * @param {Object} tableData getTableData()-like object (mutated in place).
	 * @return {Object} tableData
	 */
	function attachCellRulesToTableData(tableData) {
		if (!tableData || typeof tableData !== 'object') {
			return tableData;
		}
		delete tableData.cell_rules;
		if (tableData.include_rules !== true) {
			return tableData;
		}
		var columnName = tableData.column ? String(tableData.column) : '';
		if (!columnName) {
			return tableData;
		}
		var hasColumnRules = false;
		if (typeof ubaiColumnHasSavedCellRules === 'function') {
			hasColumnRules = ubaiColumnHasSavedCellRules(columnName);
		} else {
			hasColumnRules = !!getUbaiCellRulesForColumn(columnName);
		}
		if (!hasColumnRules) {
			return tableData;
		}
		var columnRules = normalizeUbaiCellRulesForPayload(columnName);
		if (columnRules) {
			tableData.cell_rules = jQuery.extend(true, {}, columnRules);
		}
		return tableData;
	}

	/**
	 * Attach sidebar checkbox flags to apply_prompt table payload only when each option is visible.
	 * Hidden checkboxes: key is removed so PHP does not apply that behavior.
	 *
	 * @param {Object} tableData getTableData()-like object (mutated in place).
	 * @return {Object} tableData
	 */
	function attachApplyPromptSidebarOptionsToTableData(tableData) {
		if (!tableData || typeof tableData !== 'object') {
			return tableData;
		}
		delete tableData.include_rules;
		delete tableData.use_current_cell_data;
		delete tableData.cell_rules;

		var $includeRulesLabel = g_objPostsEditor.find('#ubai_include_rules_label');
		if ($includeRulesLabel.length && $includeRulesLabel.is(':visible')) {
			tableData.include_rules = g_objPostsEditor.find('#ubai_include_rules').prop('checked') === true;
		}

		var $useCurrentCellLabel = g_objPostsEditor.find('#ubai_use_current_cell_data_label');
		if ($useCurrentCellLabel.length && $useCurrentCellLabel.is(':visible')) {
			tableData.use_current_cell_data = g_objPostsEditor.find('#ubai_use_current_cell_data').prop('checked') === true;
		}

		attachCellRulesToTableData(tableData);

		return tableData;
	}

	/**
	 * Stable key for the currently selected table cell (post + column).
	 * @return {string|null}
	 */
	function getActiveCellSyncKey() {
		if (!tableManipulator || typeof tableManipulator.getTableData !== 'function') {
			return null;
		}
		var d = tableManipulator.getTableData();
		if (!d || !d.isSelected) {
			return null;
		}
		return String(d.postId != null ? d.postId : '') + ':' + String(d.column != null ? d.column : '');
	}

	/**
	 * Sync key for sidebar image action select — includes cell identity and current image state.
	 * @return {string|null}
	 */
	function getImageActionSyncKey() {
		var cellKey = getActiveCellSyncKey();
		if (!cellKey || !tableManipulator || typeof tableManipulator.getTableData !== 'function') {
			return null;
		}
		var d = tableManipulator.getTableData();
		if (!d || !d.isSelected) {
			return null;
		}
		return cellKey + ':' + String(d.imageAttachmentId || '') + ':' + (d.imageIsPlaceholder ? '1' : '0');
	}

	/**
	 * Whether the active cell has content for sidebar options (text: "Use current cell data";
	 * image: enables "Edit image" in the action select). Uses getTableData() normalized fields.
	 */
	function activeCellHasUsableValueForCurrentDataOption() {

		if (!tableManipulator || typeof tableManipulator.getTableData !== 'function') {
			return false;
		}
		var d = tableManipulator.getTableData();
		if (!d || !d.isSelected) {
			return false;
		}

		var isImageCell = (d.cellType === 'image' || d.column === 'post_image');

		if (isImageCell) {
			if (d.imageIsPlaceholder) {
				return false;
			}

			if (d.imageAttachmentId) {
				return true;
			}
			return (false);
		}

		var v = d.value;
		if (v == null || v === '') {
			return false;
		}
		if (typeof v === 'string') {
			return jQuery.trim(String(v).replace(/\u00a0/g, ' ')) !== '';
		}
		if (typeof v === 'number' || typeof v === 'boolean') {
			return true;
		}
		if (typeof v === 'object' && jQuery.isArray(v.values)) {
			return v.values.length > 0;
		}
		return false;
	}

	/**
	 * Update apply prompt button and Quick Actions combo enabled state (both require a selected cell).
	 */
	function updateApplyPromptButtonState(e) {

		var hasActiveCell = false;
		var isImageCell = false;
		var activeColumnName = '';
		var $activeCells = null;
		if (tableManipulator && tableManipulator.$table && tableManipulator.g_isActiveCellNoIndex) {
			$activeCells = tableManipulator.$table.find('td.' + tableManipulator.g_isActiveCellNoIndex);
			hasActiveCell = $activeCells && $activeCells.length > 0;
			if (hasActiveCell) {
				// If multiple cells are marked active, prefer the most recently activated (last in DOM order).
				var $selectedCell = $activeCells.last();
				var $container = $selectedCell.find(tableManipulator.g_editorContainer).first();
				var cellType = $container && $container.length ? $container.data('type') : null;
				var column = $container && $container.length ? $container.data('column') : null;
				activeColumnName = column ? String(column) : '';
				isImageCell = (cellType === 'image' || column === 'post_image' || cellType === 'acf_gallery' || cellType === 'acf_woo_gallery');
			}
		}

		if (hasActiveCell && isImageCell && activeColumnName) {
			syncSidebarImageControlsFromColumnRules(activeColumnName);
		}

		var isImageTabActive = g_objPostsEditor.find("#" + g_ubaiSidebarModeTabImage).hasClass("active");
		var isImageSidebarContext = isImageTabActive || (hasActiveCell && isImageCell);
		if (g_objSideBarApplyBtn && g_objSideBarApplyBtn.length) {
			// Enforce sidebar mode based on the active selected cell.
			// This should apply regardless of how the tab was activated (click/keyboard).
			// When the selected cell is an image cell, switch sidebar to Image tab.
			if (hasActiveCell && isImageCell && !isImageTabActive && g_objPostsEditor && g_objPostsEditor.length) {
				g_objPostsEditor.trigger("ubai-sidebar-mode-set", ["image"]);
				isImageSidebarContext = true;
			}

			// When the selected cell is NOT an image cell, switch sidebar back to Text tab.
			if (hasActiveCell && !isImageCell && isImageTabActive && g_objPostsEditor && g_objPostsEditor.length) {
				g_objPostsEditor.trigger("ubai-sidebar-mode-set", ["text"]);
				isImageSidebarContext = false;
			}

			// In image mode: enable only for image-type cells.
			// In text mode: enable for any active cell.
			g_objSideBarApplyBtn.prop(
				"disabled",
				!hasActiveCell || (isImageTabActive && !isImageCell)
			);
		}

		var $imageTabNotice = g_objPostsEditor.find("#ubai_sidebar_apply_image_tab_notice");
		if ($imageTabNotice.length) {
			if (isImageTabActive) {
				$imageTabNotice.show().attr("aria-hidden", "false");
			} else {
				$imageTabNotice.hide().attr("aria-hidden", "true");
			}
		}

		var $combo = g_objPostsEditor.find("#ubai_quick_actions_combo");
		if ($combo.length) {
			if (hasActiveCell) {
				$combo.removeClass("unlimitedai-plugin__quick-actions-combo--disabled").attr("aria-disabled", "false").attr("tabindex", "0");
			} else {
				$combo.addClass("unlimitedai-plugin__quick-actions-combo--disabled").attr("aria-disabled", "true").attr("tabindex", "-1");
			}
		}

		var $imageActionSelect = g_objPostsEditor.find('#ubai_image_action_select');
		if ($imageActionSelect.length) {
			var canEditImage = isImageCell && activeCellHasUsableValueForCurrentDataOption();
			$imageActionSelect.find('option[value="edit"]').prop('disabled', !canEditImage);

			var imageActionSyncKey = isImageCell ? getImageActionSyncKey() : null;
			if (isImageCell && imageActionSyncKey && imageActionSyncKey !== g_lastImageActionSyncCellKey) {
				g_lastImageActionSyncCellKey = imageActionSyncKey;
				$imageActionSelect.val(canEditImage ? 'edit' : 'create');
			} else if (!isImageCell) {
				g_lastImageActionSyncCellKey = null;
			}

			if (!canEditImage && $imageActionSelect.val() === 'edit') {
				$imageActionSelect.val('create');
			}
		}

		var hideIncludeRulesForImageEdit = isImageSidebarContext && getSidebarImageActionValue() === 'edit';
		syncImageSidebarParametersVisibility(isImageSidebarContext);
		syncImageSidebarPromptPlaceholder(isImageSidebarContext);
		var hideApplyCheckboxesForImageEdit = syncImageSidebarSharedApplyOptionsVisibility(isImageSidebarContext);

		if (!hideApplyCheckboxesForImageEdit) {
			var $useCurrentCellLabel = g_objPostsEditor.find('#ubai_use_current_cell_data_label');
			if ($useCurrentCellLabel.length) {
				// Image tab/cells: only "Include rules" — not "Use current cell data".
				var showUseCurrentCell = !isImageTabActive && !isImageCell && activeCellHasUsableValueForCurrentDataOption();
				var $useCurrentCellInput = $useCurrentCellLabel.find('input[type="checkbox"]');
				$useCurrentCellLabel.toggle(showUseCurrentCell);
				if (showUseCurrentCell) {
					$useCurrentCellInput.prop('checked', true);
				} else {
					$useCurrentCellInput.prop('checked', false);
				}
			}

			var $includeRulesLabel = g_objPostsEditor.find('#ubai_include_rules_label');
			if ($includeRulesLabel.length) {
				var columnName = null;
				if (tableManipulator && typeof tableManipulator.getTableData === 'function') {
					var tableData = tableManipulator.getTableData();
					if (tableData && tableData.isSelected && tableData.column) {
						columnName = tableData.column;
					}
				}
				var showIncludeRules = typeof ubaiColumnHasSavedCellRules === 'function' && ubaiColumnHasSavedCellRules(columnName);
				if (!showIncludeRules && isImageSidebarContext && isImageCell) {
					showIncludeRules = true;
				}
				if (hideIncludeRulesForImageEdit) {
					showIncludeRules = false;
				}
				var $includeRulesInput = $includeRulesLabel.find('input[type="checkbox"]');
				$includeRulesLabel.toggle(showIncludeRules);
				if (showIncludeRules) {
					$includeRulesInput.prop('checked', true);
				} else {
					$includeRulesInput.prop('checked', false);
				}
			}
		} else if (hideIncludeRulesForImageEdit) {
			g_objPostsEditor.find('#ubai_include_rules_label').hide();
		}
	}


	/*
	* Apply current order to table
	*/
	function applyColumnOrder(tableStructure) {

		const savedOrder = jQuery.parseJSON(g_objUbaiSelectedColumnsOrderInput.val());

		const $table = g_objSpreadsheet;
		const $theadRow = $table.find('thead tr');

		const indexMap = {};
		/*
		$theadRow.children('th').each(function (i) {
			indexMap[jQuery(this).data('name')] = i;
		});
		*/

		jQuery.each(tableStructure, function (index, element) {
			indexMap[element.name] = index;
		});

		// --- Reorder HEADER ---
		savedOrder.forEach(function (name) {
			const $th = $theadRow.children('th[data-name="' + name + '"]');
			if ($th.length) {
				$theadRow.append($th); // move existing node
			}
		});



		// --- Reorder BODY ---
		$table.find('tbody tr').each(function () {
			const $row = jQuery(this);
			savedOrder.forEach(function (name) {
				const $cell = $row.children('td[data-name="' + name + '"]');
				if ($cell.length) {
					$row.append($cell);
				}
			});
		});


	}

	/**
	* apply sortable functionality to columns
	*/
	function makeColumnsSortable() {

		const $table = g_objSpreadsheet;
		const $theadRow = $table.find('thead tr');

		$theadRow.sortable({

			axis: 'x',
			items: 'th:not(:nth-child(1)):not(:nth-child(2)):not(:nth-child(3))',
			cancel: '.unlimitedai-plugin__th-resizer, input, textarea, button, select',
			cursor: 'move',
			tolerance: 'pointer',

			start: function (e, ui) {

				ui.item.data('startIndex', ui.item.index());
			},

			update: function (e, ui) {

				const oldIndex = ui.item.data('startIndex');
				const newIndex = ui.item.index();

				const order = [];

				$table.find('thead th').each(function () {
					order.push(jQuery(this).data('name'));
				});
				g_objUbaiSelectedColumnsOrderInput.val(JSON.stringify(order));

				reorderTableColumns($table, oldIndex, newIndex);

				saveColumnOrder($table);
				initColumnsSelectorPopupList();
			}
		});
	}

	/**
	* Visual ordr change
	*/
	function reorderTableColumns($table, oldIndex, newIndex) {

		$table.find('tbody tr').each(function () {
			const $row = jQuery(this);
			const $cells = $row.children('td');

			if (newIndex > oldIndex) {
				$cells.eq(oldIndex).insertAfter($cells.eq(newIndex));
			} else {
				$cells.eq(oldIndex).insertBefore($cells.eq(newIndex));
			}
		});
	}

	function saveColumnOrder($table) {

		const order = [];

		/*
		$table.find('thead th').each(function () {
			order.push(jQuery(this).data('name'));
		});*/
		const savedOrder = jQuery.parseJSON(g_objUbaiSelectedColumnsOrderInput.val());
		var currentSelection = [];
		currentSelection = syncSelectorsToColumns();

		//g_objUbaiSelectedColumnsOrderInput.val(JSON.stringify(order));

		// AJAX Save
		var post_type_col_option = 'columns_' + g_objPostTypeSelector.val();
		var data = {
			'post_type': g_objPostTypeSelector.val(),
			'columns': currentSelection,
			[g_objPostTypeSelector.val() + '_columns']: currentSelection,
			[g_objPostTypeSelector.val() + '_columns_order']: savedOrder,
		};
		g_doublyAdmin.ajaxRequest('save_editor_table_columns', data, function (response) {

		});

	}

	function onTableColSort(e) {

		const $clicked = jQuery(e.currentTarget);
		const $th = $clicked.closest('th');
		const cell_name = $th.attr('data-name');
		const cell_type = $th.attr('data-type');

		orderingColumn = cell_name;
		orderingColumnType = cell_type;

		var sortingDirection = 'asc';
		if ($clicked.hasClass('desc') == true) {
			sortingDirection = 'desc';
		}
		orderingDirection = sortingDirection;

		g_doublyAdmin.setAjaxLoaderID(g_ajaxLoaderSearch);
		var data =
		{
			col_filtering_query: globalTableFilterinByColumnsReturnFilters(),
			search_query: g_objSearchInput.val().toLowerCase(),
			post_type: g_objPostTypeSelector.val(),
			cell_type: cell_type,
			rows_per_page: g_objRowsAmountSelector.val(),
			order_by: cell_name,
			order: sortingDirection
		};

		g_doublyAdmin.ajaxRequest('make_column_sorting', data, function (response) {

			let tableData = response.message.postslist;
			let tableStructure = response.message.table_structure;

			var pagination_info = self.processPagination(response.message.total_count, g_objRowsAmountSelector.val(), response.message.current_page)


			tableManipulator = new SheetsPilot_CellProcessing('#new_output_table', tableStructure);
			tableManipulator.fillCellsWithContent(tableData);


			applyColumnOrder(initialFilteredColumns);
			syncSelectorsToColumns();


			// reiniti custom inputs
			initiateSelect2Inputs();
			//tableManipulator.initUploadFeaturedImageCells();
			tableManipulator.initCategorySelectorSelect2();
			tableManipulator.initStickyPostTitleandID();
			tableManipulator.onEvent(tableManipulator.g_events.SELECTION_CHANGE, updateApplyPromptButtonState);

			// set width
			restoreTableColumnWidths();
			jQuery('th[data-name="' + cell_name + '"] ' + g_filterColumnSettingsIcon).addClass('is_active');
		})

	}
	function onTableColSort_old(e) {

		const $clicked = jQuery(e.currentTarget);
		const $th = $clicked.closest('th');
		const $table = $th.closest('table');
		const $tbody = $table.find('tbody');
		const colIndex = $th.index();

		var sortingDirection;

		if ($clicked.hasClass('asc') == true)
			sortingDirection = 'asc';

		if ($clicked.hasClass('desc') == true)
			sortingDirection = 'desc';

		// Toggle direction
		const asc = !$th.hasClass('sort-asc');

		if (sortingDirection == 'asc' && $th.hasClass('sort-asc') == true)
			return (true);

		if (sortingDirection == 'desc' && $th.hasClass('sort-asc') == false)
			return (true);

		// Reset other headers
		$th
			.siblings('.sortable')
			.removeClass('sort-asc sort-desc');

		$th
			.toggleClass('sort-asc', asc)
			.toggleClass('sort-desc', !asc);

		const rows = $tbody.find('tr').get();

		rows.sort(function (a, b) {

			const $cellA = jQuery(a).children('td').eq(colIndex);
			const $cellB = jQuery(b).children('td').eq(colIndex);

			let A = $cellA.find('.visual_part').text().trim();
			let B = $cellB.find('.visual_part').text().trim();

			// Fallback if visual_part is missing
			if (!A) A = $cellA.text().trim();
			if (!B) B = $cellB.text().trim();

			// Numeric support
			if (jQuery.isNumeric(A) && jQuery.isNumeric(B)) {
				A = parseFloat(A);
				B = parseFloat(B);
			}

			if (A < B) return asc ? -1 : 1;
			if (A > B) return asc ? 1 : -1;
			return 0;
		});

		jQuery.each(rows, function (_, row) {
			$tbody.append(row);
		});
	}

	/**
	* quick search functionality
	*/
	function onMakeingQuickSearch() {

		const $table = g_objSpreadsheet;
		const $rows = $table.find('tbody tr');

		// Get indexes of searchable columns
		const searchableIndexes = [];

		$table.find('thead th').each(function (index) {
			if (jQuery(this).hasClass('searchable')) {
				searchableIndexes.push(index);
			}
		});

		const query = g_objSearchInput.val().toLowerCase();

		$rows.each(function () {
			const $row = jQuery(this);
			let match = false;

			searchableIndexes.forEach(function (colIndex) {
				const cellText = $row.find('td .visual_part').eq(colIndex).text().toLowerCase();

				if (cellText.indexOf(query) !== -1) {
					match = true;
				}
			});

			$row.toggle(match);
		});

	}

	/**
	* on save settings click
	*/
	function onTranslateButtonClick() {

		var objButton = jQuery(this);

		data = spreadsheet[0].getData();

		g_doublyAdmin.setAjaxLoaderID(this_prefix + "_loader_save");
		g_doublyAdmin.setSuccessMessageID(this_prefix + "_message_saved");
		g_doublyAdmin.setAjaxHideButtonID(this_prefix + "_button_save_settings");
		g_doublyAdmin.setErrorMessageID(this_prefix + "_save_settings_error");
		g_doublyAdmin.ajaxRequest('process_posts_via_gpt', data, function (response) {

			const map = {};

			jQuery.each(response.message, function (_, item) {
				map[item[0]] = item;
			});

			g_objSpreadsheet.find('tr').each(function (index) {

				if (index === 0) return;
				const $cells = jQuery(this).find('td');
				if ($cells.length < 4) return;

				const rowId = jQuery.trim($cells.eq(1).text());

				if (map[rowId]) {
					no_head_index = index - 1;
					spreadsheet[0].setValueFromCoords(1, no_head_index, map[rowId][1], true);
					spreadsheet[0].setValueFromCoords(2, no_head_index, map[rowId][2], true);
				}
			});

		});

	}

	/**
	* on post type change processing
	*/
	function onPostTypeChange() {

		window.location.hash = '#' + g_objPostTypeSelector.val();

		var data = {
			post_type: g_objPostTypeSelector.val()
		}
		g_doublyAdmin.setAjaxLoaderID("post_type_change_loader_save");
		g_doublyAdmin.ajaxRequest('return_post_type_table', data, function (response) {

			var tableStructure = g_tableStructure = response.message.structure;
			var bulk_actions = response.message.bulk_actions;
			var tableData = response.message;
			var count = 0;
			var label = "";
			var columns_order = [];

			if (typeof response.message === 'object' && response.message.message) {
				tableData = response.message.message;
				count = response.message.count;
				label = response.message.label;
				columns_order = response.message.columns_order;
			}

			// load column rules for this post type so headers and context menu show correct state
			if (typeof response.message.cell_rules !== 'undefined') {
				g_ubaiCellRules = response.message.cell_rules;
			} else {
				g_ubaiCellRules = {};
			}

			// set columns data from saved options
			g_objUbaiSelectedColumnsInput.val(JSON.stringify(response.message.columns));
			g_objUbaiSelectedColumnsOrderInput.val(JSON.stringify(response.message.columns_order));

			//initColumnsSelectorPopupList();

			self.initiateDataTable(tableData, tableStructure);

			syncSelectorsToColumns();

			//initiatePagination( response.message.posts_per_page, response.message.total_count, response.message.pages_number );

			self.processPagination(response.message.total_count, response.message.posts_per_page, 1);

			// fill in bulk actions

			g_objBulkEditSelect.empty();
			jQuery.each(bulk_actions, function (index, value) {
				var $option = jQuery('<option>', {
					value: index,
					text: value
				});
				g_objBulkEditSelect.append($option);
			})


			if (count !== undefined) {
				g_objPostTypeSelectorCountNumber.text(`(${count})`);
			}

			// set column  widths
			restoreTableColumnWidths();
			setCustomColumnNames();
			tableManipulator.onGeneralCheckboxBulkEditChange();
			jQuery(g_filterColumnSettingsIcon).removeClass('is_active');
			if (sheetspilot.editor.g_isLogOn == 1) {
				console.log( response.message.image_query_requests );
			}
			continueIntervalCallToCheckIfImageCreated(response.message.image_query_requests || {});
		});


	}


	/**
	* sync checkboxes and columns
	*/
	function syncSelectorsToColumns() {

		var currentSelection = [];
		currentSelection.push('bulk');
		currentSelection.push('id');
		currentSelection.push('post_title');
		// Loop through checkboxes to see what's visible
		g_objColumnSelectorPopupList.find('.' + g_columnSelectorToggleInputClass).each(function () {

			const index = jQuery(this).data('index').toString();
			if (this.checked) {
				currentSelection.push(index);
				processColumn(index, 'show')
			} else {
				processColumn(index, 'hide')
			}
		});

		return currentSelection;
	}

	/**
	* show column (batched DOM updates to avoid freeze)
	*/
	function processColumn(column_name, action) {

		var $th = g_objSpreadsheet.find('thead th[data-name="' + column_name + '"]');

		if (!$th.length) return;

		var colIndex = $th.index();

		if (action === 'hide') {
			$th.hide();
			$th.addClass(g_hiddenClass);

			var $cells = jQuery();
			g_objSpreadsheet.find('tbody tr').each(function () {
				$cells = $cells.add(jQuery(this).children('td').eq(colIndex));
			});
			$cells.hide();
		}

		if (action === 'show') {
			$th.show();
			$th.removeClass(g_hiddenClass);

			var $cells = jQuery();
			g_objSpreadsheet.find('tbody tr').each(function () {
				$cells = $cells.add(jQuery(this).children('td').eq(colIndex));
			});
			$cells.show();
		}
	}



	/**
	* Syncs the JSpreadsheet columns with the current selection state.
	*/
	function syncColumnVisibilityAndSave(instance = false) {

		var currentSelection = [];

		currentSelection = syncSelectorsToColumns();

		// Update the Hidden Input so it stays in sync
		g_objUbaiSelectedColumnsInput.val(JSON.stringify(currentSelection));

		// get order of columns
		let col_names_list = [];
		if (instance) {
			const columns = instance.options.columns;

			jQuery.each(columns, function (index, value) {
				col_names_list.push(value.name)
			})

			g_objUbaiSelectedColumnsOrderInput.val(JSON.stringify(col_names_list));
		}
		col_names_list = jQuery.parseJSON(g_objUbaiSelectedColumnsOrderInput.val());


		// AJAX Save
		var post_type_col_option = 'columns_' + g_objPostTypeSelector.val();
		var data = {
			'post_type': g_objPostTypeSelector.val(),
			'columns': currentSelection,
			[g_objPostTypeSelector.val() + '_columns']: currentSelection,
			[g_objPostTypeSelector.val() + '_columns_order']: col_names_list,
		};


		g_doublyAdmin.ajaxRequest('save_editor_table_columns', data, function (response) {

		});
	}

	/**
	* Initialize Select2 on the selectors
	*/
	function initSelect2ForPostTypeSelector() {

		if (!jQuery().select2) {
			console.error("Select2 library not loaded");
			return;
		}

		// Init Post Type Selector
		g_objPostTypeSelector.select2({
			width: 'auto',
			dropdownAutoWidth: true,
			minimumResultsForSearch: 0,
			selectionCssClass: 'ubai-select2-trigger',
			dropdownCssClass: g_postTypeSelectorSelect2DropdownClass,

			templateResult: formatOption,
			templateSelection: formatOption,
			escapeMarkup: function (m) { return m; }

		});
		var current_hash = window.location.hash;
		current_hash = current_hash.replace("#", "");
		if (current_hash != '') {
			g_objPostTypeSelector.val(current_hash).trigger('change');
		}


	}

	/**
	 * post type selector insert icon
	 */
	function formatOption(option) {
		if (!option.id) return option.text;

		const url = jQuery(option.element).data('posttypeurl');

		return `
			<span class="select2-option">
				${option.text}
				<span class="open-link" 
					data-url="${url}" 
					style="cursor:pointer;">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"></path><path d="m21 3-9 9"></path><path d="M15 3h6v6"></path></svg>
				</span>
			</span>
		`;
	}


	/**
	* close column selector popup
	*/
	function closeColumnSelectorPopup() {

		g_objColumnSelectorPopup.removeClass(g_classActive);
		g_objColumnSelectorPopupBtn.attr('aria-expanded', 'false');
	}


	/**
	* open column selector popup
	*/
	function openColumnSelectorPopup() {

		g_objColumnSelectorPopup.addClass(g_classActive);
		g_objColumnSelectorPopupBtn.attr('aria-expanded', 'true');
	}

	/*
	* on column selector open popup btn click
	*/
	function onColumnsSelectorOpenPopupBtnClick() {

		var isActive = g_objColumnSelectorPopup.hasClass(g_classActive);
		if (isActive == true) {
			closeColumnSelectorPopup();
		} else {
			openColumnSelectorPopup();
		}
	}

	/*
	* on column selector close popup btn click
	*/
	function onColumnsSelectorClosePopupBtnClick() {

		closeColumnSelectorPopup();
	}

	/*
	* on column selector overlay click
	*/
	function onColumnsSelectorPopupOverlayClick(e) {

		if (e.target.id === 'ubai_column_popup') {
			closeColumnSelectorPopup();
		}
	}

	/**
	* on post type selector select2 open event
	*/
	function onPostTypeSelectorSelect2Open() {

		// Find the search field using jQuery because select2 appends new dropdown element each time it's opened
		jQuery('.' + g_postTypeSelectorSelect2DropdownClass + ' .select2-search__field').attr('placeholder', sheetspilot.editor.search_post_types);

		setTimeout(function () {
			jQuery('.' + g_postTypeSelectorSelect2DropdownClass).addClass(g_classActive);
		}, 10);
	}

	/**
	* on post type selector select2 close event
	*/
	function onPostTypeSelectorSelect2Close() {

		jQuery('.' + g_postTypeSelectorSelect2DropdownClass).removeClass(g_classActive);
	}

	/**
	* Render the list of toggles inside the popup
	*/
	function initColumnsSelectorPopupList() {
		var post_type = g_objPostTypeSelector.val();
		try {
			var current_pt_names = jQuery.parseJSON(localStorage.getItem('uai_column_name_' + post_type));
		} catch (e) {
			var current_pt_names = {};
		}
		// parseJSON can return null for missing or "null" storage; "in" operator requires an object
		if (current_pt_names == null || typeof current_pt_names !== 'object') {
			current_pt_names = {};
		}

		if (!current_pt_names || current_pt_names == '') {
			current_pt_names = {};
		}

		g_objColumnSelectorPopupList.empty();

		// Get currently saved indices from the hidden input
		const savedValue = g_objUbaiSelectedColumnsInput.val();
		const savedOrder = g_objUbaiSelectedColumnsOrderInput.val();
		let selectedIndices = [];

		try {
			selectedIndices = JSON.parse(savedValue) || [];
		} catch (e) {
			selectedIndices = [];
		}

		const parsedSavedOrders = jQuery.parseJSON(savedOrder);
		if (parsedSavedOrders.length > 0) {
			init_columns = sortByNameOrder(init_columns, parsedSavedOrders);
		}

		const liveSearchHtml = `
			<div class="unlimitedai-plugin__columns-selector__live-search">
				<span class="unlimitedai-plugin__columns-selector__live-search-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg></span>
				<input class="unlimitedai-plugin__columns-selector__live-search-input" placeholder="${sheetspilot.editor.search_status}"/>
			</div>
		`;

		g_objColumnSelectorPopupList.append(liveSearchHtml);

		//prefill quick column jump
		var quick_jump_col_index = 0;

		jQuery('.unlimitedai-plugin__quick_search__dropdown-item').replaceWith('');
		init_columns.forEach((col, index) => {
			var string_quick_go_col = `
						<span data-column="${col.name}" data-index="${quick_jump_col_index}" class="unlimitedai-plugin__quick_search__dropdown-item unlimitedai-plugin__dropdown-item" role="option" tabindex="0">
							<span class="unlimitedai-plugin__quick_search__dropdown-item__name">${col.title}</span>
							<span class="unlimitedai-plugin__quick_search__dropdown-item__type">${columnTypeBeauty((col.dev_type) ? col.dev_type : col.type)}</span>
						</span>
						`;
			jQuery('.unlimitedai-plugin__quick_search__dropdown__list').append(string_quick_go_col);
			quick_jump_col_index++;
		})

		init_columns.forEach((col, index) => {

			const strIndex = col.name.toString();
			// Check if index is in the hidden input list OR if list is empty (default show all)
			const isVisible = selectedIndices.includes(strIndex) || selectedIndices.length === 0;
			const checked = isVisible ? 'checked' : '';

			let rowHtml = `
            <div class="unlimitedai-plugin__columns-selector__drawer-item has-tooltip has-tooltip--drawer" data-title="${col.name}" data-index="${col.name}">
							<span class="has-tooltip__inner">
							  <span class="has-tooltip__inner-content">
									<span class="has-tooltip__inner-content-name">${columnSlugBeauty(col.name)}</span>
									<span class="has-tooltip__inner-content-type">${columnTypeBeauty((col.dev_type) ? col.dev_type : col.type)}</span>
								</span>
								<button class="has-tooltip__inner-button copy_text unlimitedai-plugin__btn">
									<span class="has-tooltip__inner-icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FFFFFFB3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path></svg>
									</span>
								</button>
							</span>
					`;

			if (col.orderable !== false) {
				rowHtml = rowHtml + `
                <span class="unlimitedai-plugin__columns-selector__drawer-drag-handle">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-grip-vertical w-4 h-4"><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>
				</span>`;
			} else {
				rowHtml = rowHtml + `
                <span class="unlimitedai-plugin__columns-selector__drawer-drag-placeholder"></span>`;
			}

			// Patch for custom titles: apply user-renamed column labels from localStorage when present
			if (current_pt_names && typeof current_pt_names === 'object' && col.name in current_pt_names) {
				col.title = current_pt_names[col.name];
			}

			rowHtml = rowHtml + `
                <span class="unlimitedai-plugin__columns-selector__drawer-label">${col.title}</span>`;
			if (col.switchable !== false) {
				rowHtml = rowHtml + `
				<label class="unlimitedai-plugin__columns-selector__toggle">
                    <input type="checkbox" class="${g_columnSelectorToggleInputClass}" data-index="${col.name}" ${checked}>
                    <span class="unlimitedai-plugin__columns-selector__toggle-slider"></span>
                </label>`;
			}
			rowHtml = rowHtml + `
            </div>`;

			g_objColumnSelectorPopupList.append(rowHtml);
		});
	}

	/**
	* on column selector input change
	*/
	function onColumnSelectorInputChange() {

		syncColumnVisibilityAndSave();
	}


	/**
	* on dropdown button click
	*/
	function onDropdownButtonClick() {

		var objBtn = jQuery(this);
		var objContainer = objBtn.next();

		if (!objContainer || objContainer.length == 0)
			return (false);

		var isActive = objContainer.hasClass(g_classActive);

		if (isActive == true) {
			objContainer.removeClass(g_classActive);
			objBtn.attr("aria-expanded", "false");
		} else {
			//remove active state from other dropdowns on the page
			jQuery(g_dropdownContainerSelector).removeClass(g_classActive);
			jQuery(g_dropdownContainerSelector).prev().attr("aria-expanded", "false");

			objContainer.addClass(g_classActive);
			objBtn.attr("aria-expanded", "true");
		}
	}

	/**
	* switch between free/pro mode from main menu and reload the page
	*/
	function onMainMenuVersionBtnClick(e) {

		e.preventDefault();
		e.stopPropagation();

		var objBtn = jQuery(e.currentTarget);
		var targetVersion = String(objBtn.attr("data-ubai-version") || "").toLowerCase();
		if (targetVersion !== "pro" && targetVersion !== "free") {
			return;
		}

		var isPro = (targetVersion === "pro") ? 1 : 0;
		objBtn.prop("disabled", true);

		g_doublyAdmin.ajaxRequest("set_pro_mode", { is_pro: isPro }, function () {
			window.location.reload();
		});
	}

	/**
	 * Hide the text context menu (cell right-click menu). Call when user clicks outside.
	 */
	function hideTextContextMenu() {

		jQuery('#' + g_ubaiTextContextMenuID).hide().removeClass(g_isFixedPositionClass);
		g_objPostsEditor.find("#ubai_quick_actions_combo").attr("aria-expanded", "false");
		g_objPostsEditor.find("#ubai_quick_actions_combo_images").attr("aria-expanded", "false");
	}

	/**
	* on document click
	*/
	function onDocumentClick(event) {

		// on add row menu click - do not close

		if (jQuery(event.target).parents(g_addNewRowRriggerSelector).length != 0) {
			return;
		}

		// Hide the text context menu when clicking outside it
		var objTextContextMenu = jQuery('#' + g_ubaiTextContextMenuID);
		if (objTextContextMenu.length && objTextContextMenu.is(':visible')) {
			var isClickOutside = !objTextContextMenu.is(event.target) && objTextContextMenu.has(event.target).length === 0;
			if (isClickOutside) {
				hideTextContextMenu();
			}
		}
		// Remove floating context menus (body direct children only; skip full-DOM scan)
		var objContextMenus = g_objPostsEditor.find('.' + g_contextMenuClass).filter(function () {
			return jQuery(this).closest('#' + g_ubaiTextContextMenuID).length === 0;
		});
		if (objContextMenus.length > 0) {
			var isClickOutside = !objContextMenus.is(event.target) && objContextMenus.has(event.target).length === 0;
			if (isClickOutside) {
				objContextMenus.remove();
			}
		}

		//close opened dropdowns
		var objActiveContainer = g_objPostsEditor.find(g_dropdownContainerSelector + '.' + g_classActive);
		var objActiveBtn = objActiveContainer.prev();

		if (objActiveBtn && objActiveBtn.length > 0) {
			var isClickOutside = !objActiveBtn.is(event.target) && objActiveBtn.has(event.target).length === 0 && !objActiveContainer.is(event.target) && objActiveContainer.has(event.target).length === 0;

			if (isClickOutside == true) {

				objActiveContainer.removeClass(g_classActive);
				objActiveBtn.attr("aria-expanded", "false");

			}
		}

		//close opened dropdowns
		var objOpeneDropdowns = g_objPostsEditor.find(g_generalDropdownSelector + '.' + g_classActive);

		if (objOpeneDropdowns && objOpeneDropdowns.length > 0) {
			var isClickOutside = !objOpeneDropdowns.is(event.target) && objOpeneDropdowns.has(event.target).length === 0 && !g_objMainManuBtn.is(event.target) && g_objMainManuBtn.has(event.target).length === 0 && !g_objQuickSearchLocateBtn.is(event.target) && g_objQuickSearchLocateBtn.has(event.target).length === 0;

			if (isClickOutside == true) {

				objOpeneDropdowns.removeClass(g_classActive);

				//remove context menu opened class from td
				var objOpenedContextMenuTDs = g_objPostsEditor.find("." + g_contextMenuOpenedClass);

				if (objOpenedContextMenuTDs && objOpenedContextMenuTDs.length > 0)
					objOpenedContextMenuTDs.removeClass(g_contextMenuOpenedClass);
			}
		}
	}

	/**
	* on quick search icon click
	*/
	function onQuickSearchIconClick() {

		var objIcon = jQuery(this);
		var objParentContainer = objIcon.closest(g_quickSearchWrapperSelector);

		objParentContainer.addClass(g_classActive);
	}

	/**
	* on quick search close btn click
	*/
	function onQuickSearchCloseBtnClick() {

		var objBtn = jQuery(this);
		var objParentContainer = objBtn.closest(g_quickSearchWrapperSelector);
		var objInput = objParentContainer.find("input");

		if (objInput.val() == '') {
			objParentContainer.removeClass(g_classActive);
			return;
		}

		objInput.val("");
		onMakeingAjaxSearch();
		//onMakeingQuickSearch();
		objParentContainer.removeClass(g_classActive);

		//onPostTypeChange();
	}

	/**
	* on side bar toggle btn click
	*/
	function onSideBarToggleBtnClick() {

		var isActive = g_objSideBar.hasClass(g_classActive);

		if (isActive == true)
			g_objSideBar.removeClass(g_classActive);
		else
			g_objSideBar.addClass(g_classActive);

		var $tip = g_objSideBarToggleBtn.find(".has-tooltip");
		if ($tip.length) {
			var titleExpanded = $tip.attr("data-title-expanded");
			var titleCollapsed = $tip.attr("data-title-collapsed");
			var newTitle = g_objSideBar.hasClass(g_classActive) ? titleExpanded : titleCollapsed;
			if (newTitle) {
				$tip.attr("data-title", newTitle);
			}
			g_objSideBarToggleBtn.attr("aria-label", newTitle || (g_objSideBar.hasClass(g_classActive) ? sheetspilot.editor.collapse_ai_sidebar : sheetspilot.editor.expand_ai_sidebar));
		}
	}

	/**
	 * on select2 tags editor open
	 */
	function onTagEditorOpen() {

		var objSelect = jQuery(this);
		var objContainer = objSelect.closest(g_editorContainer);
		var objCounter = objContainer.find("." + g_tagCounterClass);

		if (objCounter && objCounter.length > 0)
			objCounter.hide();
	}




	/** Hide body scroll */
	function hideBodyScroll() {
		jQuery(g_bodySelector).addClass('overflow_hidden');
	}
	/** Show body scroll */
	function showBodyScroll() {
		jQuery(g_bodySelector).removeClass('overflow_hidden');
	}

	/**
	* on document keydown
	*/
	function onDocumentKeydown(e) {

		if (e.key === "Escape" && g_objDrawer.hasClass(g_isOpenClass)) {
			g_drawer.onCloseDrawer();
		}
	}

	/**
	* Initialize Select2 on the selectors
	*/
	function initSelect2ForRowsSelector() {

		if (!jQuery().select2) {
			console.error("Select2 library not loaded");
			return;
		}

		// Init Post Type Selector
		g_objRowsSelector.select2({
			width: '130px',
			dropdownAutoWidth: true,
			minimumResultsForSearch: Infinity,
			selectionCssClass: 'unlimitedai-plugin__rows-selector__select-dropdown-trigger',
			dropdownCssClass: 'unlimitedai-plugin__rows-selector__select-dropdown-custom'
		});

	}


	this.runInitialTableContentLoad = function () {

		onPostTypeChange();
	}

	/**
	* close post edit popup
	*/
	this.closePostEditPopupOuter = function () {

		g_objPostEditorPopup.removeClass(g_classActive);
		g_objPostEditPopupBtn.attr('aria-expanded', 'false');
	}
	function closePostEditPopup() {
		g_objPostEditorPopup.removeClass(g_classActive);
		g_objPostEditPopupBtn.attr('aria-expanded', 'false');
	}


	/**
	* open postdit popup
	*/
	this.openPostEditPopupOuter = function () {
		g_objPostEditorPopup.addClass(g_classActive);
		g_objPostEditPopupBtn.attr('aria-expanded', 'true');
	}
	function openPostEditPopup() {
		g_objPostEditorPopup.addClass(g_classActive);
		g_objPostEditPopupBtn.attr('aria-expanded', 'true');
	}

	/**
	 * on post edit btn click
	 */
	function onPostEditOpenPopupBtnClick() {

		return false;

		var isActive = g_objPostEditorPopup.hasClass(g_classActive);
		if (isActive == true) {
			closePostEditPopup();
		} else {
			openPostEditPopup();
		}
	}

	/*
	* on post edit close popup btn click
	*/
	function onPostEditClosePopupBtnClick() {

		closePostEditPopup();
	}

	/*
	* on post edit overlay click
	*/
	function onPostEditPopupOverlayClick(e) {

		if (e.target.id === 'ubai_post_editing_popup') {
			closePostEditPopup();
		}
	}

	/**
	* Initialize Select2 on the bulk edit
	*/
	function initSelect2ForBulkEdit() {

		if (!jQuery().select2) {
			console.error("Select2 library not loaded");
			return;
		}

		// Init Post Type Selector
		g_objBulkEditSelect.select2({
			placeholder: sheetspilot.editor.bulk_actions,
			width: '210px',
			dropdownAutoWidth: true,
			minimumResultsForSearch: Infinity,
			selectionCssClass: 'unlimitedai-plugin__bulk_edit-trigger',
			dropdownCssClass: 'unlimitedai-plugin__bulk_edit-dropdown'
		});

	}

	/**
	 * on bulk edit drawer list item click
	 */
	function onBulkEditDrawerListItemClick(e) {
		var objTarget = jQuery(e.currentTarget);
		var value = objTarget.data('value'); // The value (e.g., 'publish')
		var $sourceSelect = jQuery('.' + g_drawerBulkEditSelectClass);

		var objAllBulkEditDrawerListItems = g_objPostsEditor.find("." + this.g_bulkEditDrawerListItemClass);
		var isAlreadyActive = objTarget.hasClass(g_classActive);

		objAllBulkEditDrawerListItems.removeClass(g_classActive);

		if (!isAlreadyActive) {
			objTarget.addClass(g_classActive);
			// Sync to Select: Set value and trigger change for Select2 compatibility
			$sourceSelect.val(value).trigger('change');
		} else {
			// If toggled off, reset select to empty/default
			$sourceSelect.val("").trigger('change');
		}
	}

	/**
	 * on add row right click
	 */
	function onRightClickAction(e) {

		var objTarget = rightClickObjectPointer = jQuery(e.currentTarget);
		var objParentContainer = objTarget.closest(g_generalDropdownContainerSelector);
		var objDropdown = objParentContainer.find(g_generalDropdownSelector);

		//check if in td, and add class to make context menu visible untill clicked outside
		var objParentTD = objTarget.closest('td');
		var isInsideTD = objParentTD && objParentTD.length > 0;

		if (isInsideTD == true) {
			objParentTD.addClass(g_contextMenuOpenedClass);
		}

		objDropdown.addClass(g_classActive);
	}

	/**
	 * open add custom number of rows popup
	 */
	function openAddCustomNumberOfRowsPopup(e) {
		var objTarget = jQuery(e.currentTarget);
		var objParentContainer = objTarget.closest(g_generalDropdownContainerSelector);
		var objPopup = objParentContainer.find(g_addNewRowPopupSelector);

		objTarget.attr('aria-expanded', 'true');
		objPopup.addClass(g_classActive)
	}

	/**
	 * open genral table popup
	 */
	function openGeneralTablePopup(e) {
		var objTarget = jQuery(e.currentTarget);

		objTarget.attr('aria-expanded', 'true');
		g_objGeneralTablePopup.addClass(g_classActive)
	}

	/**
	 * close add custom number of rows popup
	 */
	function closeAddCustomNumberOfRowsPopups(e) {
		var objTarget = jQuery(e.currentTarget);
		var objParentContainer = objTarget.closest(g_generalDropdownContainerSelector);
		var objPopup = objParentContainer.find(g_addNewRowPopupSelector);

		g_objAddCustomNumberOfRowsButton.attr('aria-expanded', 'false');
		objPopup.removeClass(g_classActive)
	}

	/**
	 * close add custom number of rows popup
	 */
	function closeGeneralPopups(e) {
		var objTarget = jQuery(e.currentTarget);
		var objParentContainer = objTarget.closest(g_generalDropdownContainerSelector);
		var objPopupOpenButton = objParentContainer.find(g_generalPopupBtnSelector);

		objPopupOpenButton.attr('aria-expanded', 'false');
		g_objGeneralTablePopup.removeClass(g_classActive)
	}

	/*
	* on column selector overlay click
	*/
	function onAddCustomNumberOfRowsPopupsOverlayClick(e) {

		if (e.target.id === 'uai-add-new-row-popup') {
			closeAddCustomNumberOfRowsPopups(e);
		}
	}

	/*
	* on general table popup overlay click
	*/
	function onGeneralTablePopupOverlayClick(e) {

		if (e.target.id === 'ubai_context_menus_content_popup') {
			closeGeneralPopups(e);
		}
	}

	/**
	 * on add custom number of rows click
	 */
	function onAddCustomNumberOfRowsClick(e) {
		openAddCustomNumberOfRowsPopup(e);
	}

	/**
	 * on add custom number of rows click
	 */
	function onOpenGeenralPopupsClick(e) {
		openGeneralTablePopup(e);
	}

	/**
	 * on main manu click
	 */
	function onMainMenuClick() {
		var isActive = g_objMainManuDropdown.hasClass(g_classActive);

		if (isActive == true) {
			g_objMainManuDropdown.removeClass(g_classActive);
		} else {
			g_objMainManuDropdown.addClass(g_classActive);
		}
	}

	/**
	 * on quick search locate btn click
	 */
	function onQuickSearchLocateBtnClick(e) {
		var objBtn = jQuery(e.currentTarget);
		var objDrop = objBtn.next();

		// check columns visibility
		jQuery(jumpItemIsSearchable).removeClass(jumpItemIsSearchableNoIndex);

		jQuery("." + g_columnSelectorToggleInputClass).each(function () {

			if (jQuery(this).is(':checked')) {
				var cell_pointer = jQuery(this).attr('data-index');
				jQuery(g_duplicateDropdownItemSelector + '[data-column="' + cell_pointer + '"]').addClass(jumpItemIsSearchableNoIndex);
			}
		})


		var isActive = objDrop.hasClass(g_classActive);


		if (isActive == true) {
			objDrop.removeClass(g_classActive);
		}

		if (isActive == false) {
			objDrop.addClass(g_classActive);
		}
	}




	/*
	* on column selector mouse over show tooltip
	*/
	function onColumnSelectorItemMouseOver(e) {
		var objItem = jQuery(e.currentTarget);
		var objAllItems = g_objPostsEditor.find(g_columnSelectorPopupItemSelector);

		objAllItems.removeClass(g_classActive);
		objItem.addClass(g_classActive);

		objItem.off('mouseleave').on('mouseleave', function () {
			setTimeout(function () {

				if (!objItem.is(':hover')) {

					objItem.removeClass(g_classActive);
				}
			}, 800);
		});
	}

	/**
	 * on copy text helper func
	 */
	function copyText(e, parentSelector, textSelector) {
		var objBtn = jQuery(e.currentTarget);
		var objItem = objBtn.closest(parentSelector);
		var objName = objItem.find(textSelector);
		var nameText = objName.text();

		if (nameText) {
			var textArea = document.createElement("textarea");
			textArea.value = nameText;

			textArea.style.position = "fixed";
			textArea.style.left = "-9999px";
			textArea.style.top = "0";
			document.body.appendChild(textArea);

			textArea.focus();
			textArea.select();

			try {
				document.execCommand('copy');
				classSheetsPilotNotification.insertText(`"${nameText}" copied`);
			} catch (err) {
				console.error('Fallback copy failed', err);
			}
		}
	}

	/*
	* on column selector tooltip copy btn click
	*/
	function onColumnSelectorItemTooltipCopyBtnClick(e) {
		copyText(e, g_columnSelectorPopupItemSelector, ".has-tooltip__inner-content-name");
	}

	/**
	 * on quick search dropdown item keydown
	 */
	function onQuickSearchKeydown(e) {
		var objItem = jQuery(e.currentTarget);

		if (e.key === 'Enter' || e.key === ' ') {

			objItem.trigger('click');
		}
	}


	/** column type make beautifull */
	function columnTypeBeauty($col_type) {
		if ($col_type == 'acf_select') {
			return 'select';
		} else
			if ($col_type == 'bulk_checkbox') {
				return 'checkbox';
			} else
				if ($col_type == 'items_counter') {
					return 'attribute';
				}
		return $col_type;
	}

	/** column type make beautifull */
	function columnSlugBeauty($col_slug) {
		$col_slug = $col_slug.replace('acf_', '');
		$col_slug = $col_slug.replace('plugins_', '');
		return $col_slug;
	}

	/**
	 * make all cell bindings after table reload
	 */
	function bindAllTableActionsAfterReload( initialFilteredColumns ) {

		applyColumnOrder(initialFilteredColumns);
		syncSelectorsToColumns();
		// reiniti custom inputs
		initiateSelect2Inputs();
		//tableManipulator.initUploadFeaturedImageCells();
		tableManipulator.initCategorySelectorSelect2();
		tableManipulator.initStickyPostTitleandID();
		tableManipulator.hideDeleteForEmptyGalleries();
		tableManipulator.hideEditImageForNoFeatured();
		tableManipulator.onEvent(tableManipulator.g_events.SELECTION_CHANGE, updateApplyPromptButtonState);
	}
}
