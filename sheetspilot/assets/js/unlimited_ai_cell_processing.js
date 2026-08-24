/**
 * True if g_ubaiCellRules has a prompt or image options for this column.
 * Keeps context menu "Apply column rules" in sync with the column header AI icon (same conditions).
 *
 * @param {string} columnName Column key (data-column / rowData.name).
 * @returns {boolean}
 */
function ubaiColumnHasSavedCellRules(columnName) {
	if (!columnName || typeof g_ubaiCellRules === 'undefined') {
		return false;
	}
	var rules = g_ubaiCellRules;
	if (typeof rules === 'string') {
		try {
			rules = JSON.parse(rules);
		} catch (e) {
			rules = {};
		}
	}
	if (!rules || typeof rules !== 'object') {
		return false;
	}
	var pr = rules[columnName];
	if (pr != null && String(pr).trim() !== '') {
		return true;
	}
	if (rules[columnName + '__aspect_ratio'] || rules[columnName + '__quality']) {
		return true;
	}
	if (rules[columnName + '__format']) {
		return true;
	}
	if (rules[columnName + '__resolution']) {
		return true;
	}
	return false;
}

class SheetsPilot_CellProcessing {

	constructor(tableSelector, data) {
		// classes
		this.g_doublyAdmin = new UniteAdminSheetsPilot();
		this.g_postEditorView = objPostsEditorView;

		this.editorDrawer = g_drawer;

		// selectors
		this.g_editorContainer = '.editor_container';
		this.g_previewPost = '.preview_post';
		this.g_editPostModal = '.edit_post_modal';
		this.g_editInNewWindow = '.edit_in_new_window';
		this.g_editorContainerNoPrefix = 'editor_container';
		this.g_categoryEditor = '.category_editor';
		this.g_categoryEditorCloseBtnClass = 'category_editor-close';
		this.g_categoryEditorCloseBtn = '.' + this.g_categoryEditorCloseBtnClass;
		this.g_addCategoryButton = '.category_editor-footer__title';
		this.g_visualPart = '.visual_part';
		this.g_dblclickEditableCell = '.dblclick_editable_cell';
		this.g_dblclickEditableCellNoIndex = 'dblclick_editable_cell';
		this.g_visualPartNoPrefix = 'visual_part';
		this.g_ubaiFeaturedImageUploader = '.ubai_featured_image_uploader';
		this.g_editImageField = '.edit_image_field';
		this.g_editorPart = '.editor_part';
		this.g_editorPartNoPrefix = 'editor_part';
		this.g_editorInput = '.editor_input';
		this.g_editorInputNoPrefix = 'editor_input';
		this.g_newTaxAdd = '.new_tax_add';
		this.g_addTaxonomy = '.add_taxonomy';
		this.g_taxContainer = '.ubai_tax_container';
		this.g_taxContainerInput = '.ubai_tax_container input[type="checkbox"]';
		this.g_addCategoryLoaderSave = '.add_category_loader_save';
		this.g_newTaxValue = '.new_tax_value';
		this.g_categorySelector = '.category_selector';
		this.g_textEditorInput = '.text_editor_input';
		this.g_featuredImageUploader = ".ubai_featured_image_uploader";
		this.g_isPlaceholder = ".is_placeholder";
		this.g_ubaiTaxBlock = ".ubai_tax_block";
		this.g_isPlaceholderNoIndex = "is_placeholder";
		this.g_inlineEditImageFieldNoIndex = "inline_edit_image_field";

		this.g_isCurrentlyEditionNoIndex = "is_currently_edition";
		this.g_categoryTd = ".category_cell_td";
		this.g_categoryTdActivated = ".category_cell_td.is-active-cell";
		this.g_tbCloseWindowButton = "#TB_closeWindowButton";
		this.g_tbIframeContent = "#TB_iframeContent";
		this.g_tbIframeModalContent = "#TB_iframeModalContent";
		this.g_ubaiPostTypeSelector = "#ubai_post_type_selector";
		this.g_tbOverlay = "#TB_overlay";
		this.g_uploadFeaturedImage;



		this.g_ajaxLoaderNoIndex = "uba_loader_saving";
		this.g_ajaxLoaderProcessing = "uba_loader_processing";

		this.g_taxCounterBlockNoIndex = "tax_counter_block";
		this.g_taxCounterBlock = ".tax_counter_block";

		this.g_galleryAddImageNoindex = "add_gallery_image";
		this.g_galleryAddImage = ".add_gallery_image";

		this.g_deleteAllImages = ".delete_all_images";
		this.g_deleteAllImagesNoIndex = "delete_all_images";

		this.g_dropRepeaterContent = ".delete_repeater_data";
		this.g_dropRepeaterContentNoIndex = "delete_repeater_data";

		this.g_editRepeaterField = ".edit_repeater_field";
		this.g_editRepeaterFieldNoIndex = "edit_repeater_field";

		this.g_editPostContent = ".edit_post_content";
		this.g_editPostContentNoIndex = "edit_post_content";

		this.g_ubaiContextMenuCopyActionNoIndex = "ubai_context_menu_copy_action";
		this.g_ubaiContextMenuCopyAction = "#ubai_context_menu_copy_action";

		this.g_ubaiContextMenuPasteActionNoIndex = "ubai_context_menu_paste_action";
		this.g_ubaiContextMenuPasteAction = "#ubai_context_menu_paste_action";

		this.g_ubaiContextMenuAutofillFormTitleNoIndex = "ubai_context_menu_autofill_from_title";
		this.g_ubaiContextMenuAutofillFormTitle = "#ubai_context_menu_autofill_from_title";

		this.g_ubaiContextMenuCompressImageNoIndex = "ubai_context_menu_compress_image";
		this.g_ubaiContextMenuCompressImage = "#ubai_context_menu_compress_image";

		this.g_galleryRemoveImageButtonNoIndex = "gallery_remove_image_button";
		this.g_galleryRemoveImageButton = ".gallery_remove_image_button";

		this.g_singleImageCounterNoIndex = "single_image_counter";
		this.g_singleImageCounter = ".single_image_counter";


		this.g_singleImageContainer = ".single_image_container";
		this.g_singleImageContainerNoIndex = "single_image_container";

		this.g_galleryImagesContainerNoIndex = "gallery_images_container";
		this.g_galleryImagesContainer = ".gallery_images_container";

		this.g_postObjectEditorInput = ".post_object_editor_input";
		this.g_acfSelectEditorInput = ".acf_select_editor_input";
		this.g_tagSelectEditorInput = ".tag_editor_input";
		this.g_tagSelectEditorInputNoIndex = "tag_editor_input";

		this.g_selectEditorInput = ".select_editor_input";
		this.g_selectEditorInputNoIndex = "select_editor_input";

		this.g_singleTagBubbleNoPrefix = "single_tag_bubble";
		this.g_singleTagBubble = ".single_tag_bubble";

		this.g_isActiveCell = ".is-active-cell";
		this.g_isActiveCellNoIndex = "is-active-cell";

		this.g_ubaiUndoTrigger = "#ubai_undo_trigger";

		this.g_sidebarApplyBtn = ".unlimitedai-plugin__sidebar-apply-btn";

		this.g_events = {
			SELECTION_CHANGE: "ubai_selection_change",
			RUN_PROMPT: "ubai_run_prompt"
		};

		this.g_isSelect2EditorActive = ".is_select2_editor_active";
		this.g_isSelect2EditorActiveNoIndex = "is_select2_editor_active";



		this.g_postEditorPopupBodyNoPrefix = "unlimitedai-plugin__post_editor__popup-body";
		this.g_postEditorPopupBody = ".unlimitedai-plugin__post_editor__popup-body";


		this.g_wyswygContentSave = ".wyswyg_content_save";
		this.g_wyswygContentSaveNoIndex = "wyswyg_content_save";

		this.g_featuredImageUploaderResetBtn = ".ubai_featured_image_uploader--reset-btn";
		this.g_deleteImageField = ".delete_image_field";
		this.g_downloadImageField = '.download_image_field';
		this.g_downloadImageFieldNoIndex = 'download_image_field';
		this.g_pendingPromptResultIcon = '.ubai_pending_prompt_result_icon';
		this.g_pendingPromptResultIconNoIndex = 'ubai_pending_prompt_result_icon';

		this.g_featuredImageUploaderCell = ".ubai_featured_image_uploader-cell";
		this.g_featuredImageUploaderCellNoIndex = "ubai_featured_image_uploader-cell";

		this.g_deletePostButton = ".delete_post_button";
		this.g_deletePostButtonNoIndex = "delete_post_button";

		this.g_duplicatePostButton = ".duplicate_post_button";
		this.g_duplicatePostButtonNoIndex = "duplicate_post_button";

		this.edgeRightClass = "edge-right";
		this.g_objFilterColumnBtnClass = "unlimitedai-plugin__filter-column-settings-icon";
		this.g_objFilterColumnBtn = "." + this.g_objFilterColumnBtnClass;
		this.g_columnFilterDropdownClass = "unlimitedai-plugin__th-filter-dropdown";
		this.g_columnFilterDropdownFilterContainerClass = ".unlimitedai-plugin__th-filter-dropdown__term_container";
		this.g_columnFilterDropdownFilterContainerItemClass = "unlimitedai-plugin__th-filter-dropdown__term_filterable_item";
		this.g_classActive = "ue-active";
		this.g_bulkEditTHCheckboxSelector = '.unlimitedai-plugin__th-bulk-edit__select-input';
		this.g_bulkEditTDCheckboxSelector = '.unlimitedai-plugin__td-bulk-edit__select-input';
		this.g_bulkEditDropdownSelector = '.unlimitedai-plugin__bulk-edit__wrapper';
		this.g_bulkEditSelectedCheckboxesCount = '.unlimitedai-plugin__bulk-edit__counter-num';
		this.g_bulkEditSelectedClass = 'unlimitedai-plugin__bulk-edit__selected';

		this.g_singleSwitchSliderClass = 'unlimitedai-plugin__switch-selector__toggle-slider';
		this.g_singleSwitchInputClass = 'unlimitedai-plugin__switch-selector__toggle-input';

		this.g_columnFilterTermSearchInput = '.uai-column-filter-term-inner-search-input';
		this.g_columnFilterTermmakeFilteringActionButton = '.unlimitedai-plugin__th-filter-dropdown__make_filtering';
		this.g_hasToolTipSelector = ".has-tooltip";



		this.g_generalBtnTextClass = 'unlimitedai-plugin__text-btn';
		this.g_generalBtnTextAccentClass = 'unlimitedai-plugin__text-accent-btn';
		this.g_generalDarkBtnTextClass = 'unlimitedai-plugin__text-btn--dark';
		this.g_imageEditingClass = 'unlimitedai-plugin__drawer-image-edit';

		this.g_openWooCellDetailsNoIndex = 'sheetpilot_open_woo_cell_details';

		this.spEditProductAttributesNoIndex = 'edit_product_attribute';






		this.$table = jQuery(tableSelector);
		this.$tableBody = jQuery('tbody', this.$table);
		this.$tableHead = jQuery('thead', this.$table);

		
		this.$colData = data; // store your table data here
		//this.initReplaceDialog();

		if (typeof window !== 'undefined' && window.ubaiPrompts && window.ubaiPrompts.initPromptReplaceDialog) {
			window.ubaiPrompts.initPromptReplaceDialog(this);
		}
		this.initEvents();

		//this.fillCellsWithContent();		



		// variables

		this.isCellSelect2Tag = false;
		this.isCellSelect2TagTarget = false;




		this.undoPrevCellValue = false;
		this.undoCellAddress = false;
		this.undoPrevCellValueArray = [];
		this.undoStackArchive = [];

		this.editedPostID = false;
		this.fbModalVisible = false;

		this.select2Reorder = [];

		this.lastBulkChecked = null;

		this.lastTDSelected = null;
		this.lastTDSelectedColName = null;

		this.firstSelectedCell = null;

		/** Selector for td that owns the apply-prompt AJAX spinner (e.g. ".cell_123_4") so hide targets that cell after selection moves. */
		this.applyPromptLoadingAddress = null;
		/** Fallback jQuery ref when row/col are missing (rare). */
		this.$applyPromptLoadingCellFallback = null;
		/** Per-cell concurrent apply-prompt loading counters (key: "postId:columnIndex"). */
		this.applyPromptLoadingCounts = {};
		/** Per-cell queued-behind-cap waiting counters (key: "postId:columnIndex"). */
		this.applyPromptWaitingCounts = {};
		/** Per-cell pending-dialog counters (set when response arrived, before dialog is shown). */
		this.promptDialogPendingCounts = {};
		/** Dismissed prompt results (text or image) keyed by "postId:columnIndex". */
		this.discardedPendingPromptResults = {};
		/** When true, SELECTION_CHANGE must not auto-close the prompt result dialog (queue/programmatic focus). */
		this.suppressPromptDialogCloseOnSelection = false;
	}

	initEvents() {
		var self = this;

		// classes init
		const nameSpace = '.cellAction';

		// remove old handlers
		this.$table.off(nameSpace);
		jQuery(document).off(nameSpace);

		// кастомные события тоже чистим
		jQuery(document).off(this.g_events.SELECTION_CHANGE, null);
		// Do not off(ubai_run_prompt): PostsEditor registers that on document; CellProcessing never binds it — clearing would remove the run-prompt handler.

		// remove old handlers END

		// Stability: the prompt result dialog is NOT closed on selection change anymore.
		// It only closes through its own controls (X / Replace / Insert / Apply / Discard / next-in-queue),
		// so clicking around the table, keyboard navigation or programmatic selection can no longer
		// dismiss an unread result. (Previously any SELECTION_CHANGE hid the dialog, which felt like
		// it "closed by itself".)

		this.$table.on('dblclick' + nameSpace, this.g_dblclickEditableCell, (e) => {
			this.onCellEdit(e);
		});
		this.$table.on('blur' + nameSpace, this.g_editorPart + ' ' + this.g_editorInput, (e) => {
			this.onCellContentSave(e.currentTarget);
		});

		this.$table.on('click' + nameSpace, this.g_ubaiFeaturedImageUploader, (e) => {
			this.showImageUploader(jQuery(e.currentTarget));
		});
		this.$table.on('click' + nameSpace, this.g_editImageField, (e) => {
			this.showImageUploader(jQuery(e.currentTarget));
		});
		this.$table.on('dblclick' + nameSpace, this.g_dblclickEditableCell + '' + this.g_featuredImageUploaderCell, (e) => {
			this.showImageUploader(jQuery(e.currentTarget).find(this.g_editorContainer));
		});

		this.$table.on('click' + nameSpace, this.g_newTaxAdd, (e) => {
			this.saveCategoryInsertion(e);
		});

		// show hide tax editor
		this.$table.on('click' + nameSpace, this.g_categoryTdActivated, (e) => {

			this.showTaxonomyInsertField(e);
		});
		this.$table.on('change' + nameSpace, this.g_taxContainerInput, (e) => {
			this.onTaxChange(e);
		});
		this.$table.on('mousedown' + nameSpace, '.unlimitedai-plugin__th-resizer', (e) => {
			this.onTHMouseDown(e);
		});
		jQuery(document).on('click' + nameSpace, (e) => {
			this.onDucumentClick(e);
		});

		// select2 tags on change
		/*
		this.$table.on('select2:select', '.js-example-basic-multiple', (e) => {
			this.onTagChange(e);
		});
		this.$table.on('select2:unselect', '.js-example-basic-multiple', (e) => {
			this.onTagChange(e);
		});
		*/

		// select2 Post Object on change
		this.$table.on('select2:select' + nameSpace, this.g_postObjectEditorInput, (e) => {
			this.onPostObjectChange(e);
		});
		this.$table.on('select2:close' + nameSpace, this.g_postObjectEditorInput, (e) => {
			this.onPostObjectChange(e);
		});
		this.$table.on('select2:unselect' + nameSpace, this.g_postObjectEditorInput, (e) => {
			this.onPostObjectChange(e);
		});


		// select2 acf_select, radio, checkbox
		this.$table.on('select2:open' + nameSpace, this.g_acfSelectEditorInput, (e) => {
			document.querySelector('.select2-container--open')?.focus();
			setTimeout(() => {
				const input = document.querySelector('.select2-container--open .select2-search__field');

				if (input) {
					input.focus();
				}
			}, 10);
		});

		this.$table.on('select2:select' + nameSpace, this.g_acfSelectEditorInput, (e) => {
			this.onAcfSelectChange(e);
			jQuery(e.target).parents('td').focus();
		});
		this.$table.on('select2:close' + nameSpace, this.g_acfSelectEditorInput, (e) => {
			this.onAcfSelectChange(e);
			jQuery(e.target).parents('td').focus();
		});

		this.$table.on('select2:unselect' + nameSpace, this.g_acfSelectEditorInput, (e) => {
			this.onAcfSelectChange(e);
		});

		// select2 acf_select, radio, checkbox
		this.$table.on('select2:select' + nameSpace, this.g_tagSelectEditorInput, (e) => {
			this.onTagSelectChange(e);
		});
		this.$table.on('select2:unselect' + nameSpace, this.g_tagSelectEditorInput, (e) => {
			this.onTagSelectChange(e);
		});
		this.$table.on('select2:close' + nameSpace, this.g_tagSelectEditorInput, (e) => {
			this.onTagSelectChange(e);
		});
		this.$table.on('change' + nameSpace, this.g_tagSelectEditorInput, (e) => {
			this.onTagSelectChange(e);
		});


		// post preview
		this.$table.on('click' + nameSpace, this.g_previewPost, (e) => {
			this.goToPostPreview(e);
		});
		this.$table.on('click' + nameSpace, this.g_editPostModal, (e) => {
			this.editPostModal(e);
		});
		this.$table.on('click' + nameSpace, this.g_editInNewWindow, (e) => {
			this.editInNewWindow(e);
		});
		// Featured Image remove
		this.$table.on('click' + nameSpace, this.g_featuredImageUploaderResetBtn, (e) => {
			this.onFeaturedImageUploaderResetButtonClick(e);
		});

		this.$table.on('click' + nameSpace, this.g_deleteImageField, (e) => {
			this.onFeaturedImageUploaderResetButtonClick(e);
		});
		this.$table.on('click' + nameSpace, this.g_downloadImageField, (e) => {
			this.onDownloadImageFieldClick(e);
		});
		this.$table.on('click' + nameSpace, this.g_pendingPromptResultIcon, (e) => {
			this.onPendingPromptResultIconClick(e);
		});
		// Category taxes out
		this.$table.on('click' + nameSpace, this.g_taxCounterBlock, (e) => {
			this.toggelCategries(e);
		});
		this.$table.on('keydown' + nameSpace, 'th, td', (e) => {
			this.onTHTDKeydown(e);
		});

		// global keydown
		jQuery(document).on('keydown' + nameSpace, (e) => {
			this.onGlobalKeydown(e);
		});


		// gallery Image add
		this.$table.on('click' + nameSpace, this.g_galleryAddImage, (e) => {
			var parent = jQuery(e.target).parents('td');
			parent.trigger('dblclick');
			//this.onGalleryAddImage(e.currentTarget);
		});

		// delete all images
		this.$table.on('click' + nameSpace, this.g_deleteAllImages, (e) => {
			this.onGalleryDrop(e);
		});

		// drop repeater content
		this.$table.on('click' + nameSpace, this.g_dropRepeaterContent, (e) => {
			this.onRepeaterDrop(e);
		});

		// gallery image remove
		this.$table.on('click' + nameSpace, this.g_galleryRemoveImageButton, (e) => {
			this.onGalleryRemoveImage(e);
		});

		/*
		this.$table.on('click' + nameSpace, this.g_editRepeaterField, (e) => {
			this.editRepeaterFieldSidebar(e.currentTarget);
		});
		*/
		this.$table.on('click' + nameSpace, this.g_editPostContent, (e) => {
			jQuery(e.currentTarget).parents('td').trigger('dblclick');
		});

		// undo button click
		jQuery(document).on('click' + nameSpace, this.g_ubaiUndoTrigger, (e) => {
			this.undoCellEditions();
		});

		// make table active cell have class (left click)
		this.$table.on('click' + nameSpace, 'td', function (e) {


			//backup of cell value in mem
			const parent_container = jQuery(this).find(self.g_editorContainer);
			const type = parent_container.data('type');
			const colname = parent_container.data('column');
			const row = jQuery(this).data('row');
			const col = jQuery(this).data('col');


			if (e.ctrlKey || e.metaKey) {
				if (colname == self.lastTDSelectedColName) {
					if (jQuery(this).hasClass(self.g_isActiveCellNoIndex)) {
						jQuery(this).removeClass(self.g_isActiveCellNoIndex);
					} else {
						jQuery(this).addClass(self.g_isActiveCellNoIndex);
					}
				}
				return;
			}

			// shift selection processing
			let $cell = jQuery(this);
			let columnIndex = $cell.index();
			let rowIndex = $cell.closest('tr').index();

			if (!e.shiftKey || !self.firstSelectedCell) {
				// первый клик
				self.firstSelectedCell = {
					column: columnIndex,
					row: rowIndex
				};

				//return;
			}


			// если shift зажат
			if (e.shiftKey) {

				let startRow = Math.min(self.firstSelectedCell.row, rowIndex);
				let endRow = Math.max(self.firstSelectedCell.row, rowIndex);

				for (let i = startRow + 1; i <= endRow + 1; i++) {
					let $row = self.$table.find('tr').eq(i);
					let $targetCell = $row.children().eq(columnIndex);

					$targetCell.addClass(self.g_isActiveCellNoIndex);
				}
				return;
			}
			// shift selection processing END;

			// store cell
			self.lastTDSelected = e.target;
			self.lastTDSelectedColName = colname;


			if (!jQuery(this).hasClass(self.g_isActiveCellNoIndex)) {

				jQuery('td.' + self.g_isActiveCellNoIndex).removeClass(self.g_isActiveCellNoIndex);
				jQuery(this).addClass(self.g_isActiveCellNoIndex);

				let innerUndoStackItem = [];

				self.undoCellAddress = '.cell_' + row + '_' + col;

				if (type == 'textarea' || type == 'text' || type == 'calendar') {
					// get current editor content					
					self.undoPrevCellValue = jQuery(self.g_editorPart + ' ' + self.g_editorInput, parent_container).val();
					self.undoStackArchive.push({ 'cell': '.cell_' + row + '_' + col, 'type': type, 'value': self.undoPrevCellValue });
				}
				if (type == 'acf_select' || type == 'post_object' || type == 'woo_post_object') {
					self.undoPrevCellValueArray = jQuery(self.g_editorPart + ' ' + self.g_editorInput, parent_container).val();
					// check if multiple
					if (!jQuery.isArray(self.undoPrevCellValueArray)) {
						self.undoPrevCellValueArray = [self.undoPrevCellValueArray];
					}
					self.undoStackArchive.push({ 'cell': '.cell_' + row + '_' + col, 'type': type, 'value': self.undoPrevCellValueArray });

				}


				//backup of cell value END

				// patch to hide select2 editors
				if (self.isCellSelect2Tag) {
					self.isCellSelect2Tag = false;
					jQuery(self.g_visualPart, self.isCellSelect2TagTarget).show();
					jQuery(self.g_editorPart, self.isCellSelect2TagTarget).hide();
				}

				// filter propmpt history data
				window.ubaiPrompts.filterDomPromptHistoryList();


				self.triggerEvent(self.g_events.SELECTION_CHANGE);

				
			}
		});

		// on select bubble click
		this.$table.on('click' + nameSpace, this.g_singleTagBubble, function (e) {
			var parent_td = jQuery(e.target).parents('td');
			var cell_type = parent_td.find(self.g_editorContainer).attr('data-type');
			//patch in single click for select open it
			if (cell_type == 'acf_select') {
				parent_td.dblclick();
			}

		})

		// right-click: select the cell same as left-click, then show context menu
		this.$table.on('contextmenu' + nameSpace, 'td', function (e) {
			if (jQuery(e.target).closest(self.g_duplicatePostButton).length > 0) {
				return;
			}

			// turn off menu for debugging/coding
			if (g_isContextOff == '1') { return; }

			if (!jQuery(this).hasClass(self.g_isActiveCellNoIndex)) {
				jQuery('td.' + self.g_isActiveCellNoIndex).removeClass(self.g_isActiveCellNoIndex);
				jQuery(this).addClass(self.g_isActiveCellNoIndex);

				if (self.isCellSelect2Tag) {
					self.isCellSelect2Tag = false;
					jQuery(self.g_visualPart, self.isCellSelect2TagTarget).show();
					jQuery(self.g_editorPart, self.isCellSelect2TagTarget).hide();
				}

				self.triggerEvent(self.g_events.SELECTION_CHANGE);
			}
			self.showContextMenu(e, jQuery(this));
		});



		// save wyswig
		jQuery('body').on('click' + nameSpace, this.g_wyswygContentSave, (e) => {
			this.saveWyswygContent(e);
		});

		this.$table.on('table_initialized' + nameSpace, function () {
			self.initStickyPostTitleandID();
			self.initCategorySelectorSelect2();
			self.triggerEvent(self.g_events.SELECTION_CHANGE);
		});

		this.$table.on('click' + nameSpace, this.g_addCategoryButton, (e) => {
			this.onExpandCategoryAddingContainerButtonClick(e);
		});

		//pre process copy paste
		// cell copy processing
		jQuery(document).on('copy' + nameSpace, (e) => {
			this.onCellCopy(e);
			this.hideContextMenu();
		});
		// menu copy
		jQuery(document).on("click" + nameSpace, this.g_ubaiContextMenuCopyAction, (e) => {
			this.onCellCopy(e);
			this.hideContextMenu();
		});

		// cell past processing
		jQuery(document).on('paste' + nameSpace, (e) => {
			this.onCellPaste(e);
			this.hideContextMenu();
		});

		// menu copy
		jQuery(document).on("click" + nameSpace, this.g_ubaiContextMenuPasteAction, async (e) => {
			await this.onCellPaste(e);
			this.hideContextMenu();
		});

		// Context menu item click: run prompt (same for all items including Apply column rules).
		// Use namespaced off/on to avoid duplicate apply_prompt calls after re-init.
		jQuery(document)
			.off('click.ubaiContextRunPrompt', '#ubai_text_context_menu .unlimitedai-plugin__context-menu__item:not(.unlimitedai-plugin__context-menu__item--disabled)')
			.on('click.ubaiContextRunPrompt', '#ubai_text_context_menu .unlimitedai-plugin__context-menu__item:not(.unlimitedai-plugin__context-menu__item--disabled)', (e) => {

				// Use currentTarget (matched delegate element), not closest(e.target),
				// so submenu clicks don't resolve to the same child item twice.
				var $item = jQuery(e.currentTarget);
				if (!$item.length) {
					return;
				}

				// Parent rows (Translate, Change length, Saved prompts) use hover for the submenu; no prompt run on the row itself.
				if ($item.children('.unlimitedai-plugin__context-menu__sub').length > 0) {
					return;
				}

				var menuAction = $item.attr('data-action');
				if (typeof menuAction !== 'string') {
					menuAction = '';
				}
				menuAction = menuAction.trim();

				var promptText = $item.attr('data-prompt');
				if (typeof promptText !== 'string') {
					promptText = '';
				}
				promptText = promptText.trim();

				// Copy / paste / autofill use their own handlers; keep empty-prompt guard for those actions only.
				var skipEmptyPromptActions = { 'copy-action': true, 'paste-action': true, 'autofill-from-title': true, 'compress-image': true };
				if (!promptText && (!menuAction || skipEmptyPromptActions[menuAction])) {
					return;
				}
				if (!promptText && !menuAction) {
					return;
				}

				this.hideContextMenu();
				if (this.g_postEditorView && typeof this.g_postEditorView.dispatchRunPromptFromContextMenu === 'function') {
					this.g_postEditorView.dispatchRunPromptFromContextMenu(promptText, menuAction);
				} else {
					this.triggerEvent(this.g_events.RUN_PROMPT, [promptText, menuAction]);
				}
			});





		this.$table.on("click" + nameSpace, this.g_objFilterColumnBtn, function (e) { self.onFilterColumnBtnClick(e) });
		this.$table.on("change" + nameSpace, this.g_bulkEditTHCheckboxSelector, function (e) { self.onGeneralCheckboxBulkEditChange(e) });
		this.$table.on("click" + nameSpace, this.g_bulkEditTDCheckboxSelector, function (e) { self.onCheckboxBulkEditChange(e) });



		jQuery(document).on("click" + nameSpace, this.g_ubaiContextMenuAutofillFormTitle, (e) => {
			this.onFillSlugFromTitle(e);
			this.hideContextMenu();
		});

		jQuery(document).on("click" + nameSpace, this.g_ubaiContextMenuCompressImage, (e) => {
			this.onCompressImageFromContextMenu(e);
			this.hideContextMenu();
		});


		// switch click
		this.$table.on("change" + nameSpace, '.' + this.g_singleSwitchInputClass, (e) => {
			this.toggleColumnChangeSwitch(e);
		});

		// edit attribute
		this.$table.on("click" + nameSpace, '.' + this.spEditProductAttributesNoIndex, (e) => {
			g_drawer.openProductAttributesEditor(e);
		});

		this.$table.on("mouseover" + nameSpace, "th " + this.g_hasToolTipSelector, (e) => {
			this.onTooltipMouseover(e);
		});

		this.$table.on("click" + nameSpace, this.g_categoryEditorCloseBtn, (e) => {
			this.closeCategoryEditorOnCloseBtnClick(e);
		});

		this.$table.on("click" + nameSpace, ".search_tax_close_icon", (e) => {
			this.onResetTaxQuickSearchBtnClick(e);
		});

	}

	// init end functions 

	toggleColumnChangeSwitch(e) {
		var parent_td = jQuery(e.target).parents('td');
		if (jQuery(e.target).is(':checked')) {
			jQuery('.' + this.g_openWooCellDetailsNoIndex, parent_td).show();
		} else {
			jQuery('.' + this.g_openWooCellDetailsNoIndex, parent_td).hide();
		}
		this.onCellContentSave(jQuery(e.target));
	}

	// generate random_string
	generateRandomString(length) {
		var result = '';
		var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		var charactersLength = characters.length;
		for (var i = 0; i < length; i++) {
			result += characters.charAt(Math.floor(Math.random() * charactersLength));
		}
		return result;
	}

	// gallery column - if no images - hide delete
	hideDeleteForEmptyGalleries() {
		var self = this;
		jQuery(this.g_editorContainer + '[data-type="acf_gallery"]').each(function () {

			var parent_td = jQuery(this).parents('td');
			if (jQuery(this).data('value') == '') {
				jQuery(self.g_deleteAllImages, parent_td).hide();
			} else {
				jQuery(self.g_deleteAllImages, parent_td).show();
			}
		})

		jQuery(this.g_editorContainer + '[data-type="acf_woo_gallery"]').each(function () {

			var parent_td = jQuery(this).parents('td');
			if (jQuery(this).data('value') == '') {
				jQuery(self.g_deleteAllImages, parent_td).hide();
			} else {
				jQuery(self.g_deleteAllImages, parent_td).show();
			}
		})
	}

	// featured image - if no image - hide editor
	hideEditImageForNoFeatured() {

		var self = this;
		jQuery(this.g_editorContainer + '[data-column="post_image"], ' + this.g_editorContainer + '[data-type="image"]').each(function () {

			var parent_td = jQuery(this).parents('td');
			var isPlaceholder = jQuery(self.g_isPlaceholder, this).length > 0;

			jQuery('.' + self.g_inlineEditImageFieldNoIndex, parent_td).toggle(!isPlaceholder);
			jQuery('.' + self.g_downloadImageFieldNoIndex, parent_td).toggle(!isPlaceholder);
		})
	}

	getFilenameFromImageUrl(url) {
		if (!url) {
			return 'image';
		}
		try {
			var pathname = new URL(url, window.location.href).pathname;
			var parts = pathname.split('/').filter(Boolean);
			return parts.length ? decodeURIComponent(parts[parts.length - 1]) : 'image';
		} catch (err) {
			var fallback = String(url).split('?')[0].split('/').pop();
			return fallback ? decodeURIComponent(fallback) : 'image';
		}
	}

	downloadImageFile(url, filename) {
		if (!url) {
			return;
		}
		var safeName = filename || this.getFilenameFromImageUrl(url);
		fetch(url, { credentials: 'same-origin' })
			.then(function (response) {
				if (!response.ok) {
					throw new Error('download failed');
				}
				return response.blob();
			})
			.then(function (blob) {
				var objectUrl = URL.createObjectURL(blob);
				var link = document.createElement('a');
				link.href = objectUrl;
				link.download = safeName;
				document.body.appendChild(link);
				link.click();
				link.remove();
				setTimeout(function () {
					URL.revokeObjectURL(objectUrl);
				}, 1000);
			})
			.catch(function () {
				var link = document.createElement('a');
				link.href = url;
				link.download = safeName;
				link.target = '_blank';
				link.rel = 'noopener';
				document.body.appendChild(link);
				link.click();
				link.remove();
			});
	}

	onDownloadImageFieldClick(e) {
		e.preventDefault();
		e.stopPropagation();

		var $td = jQuery(e.currentTarget).closest('td');
		var $img = jQuery(this.g_ubaiFeaturedImageUploader, $td).first();
		if (!$img.length) {
			$img = $td.find(this.g_visualPart + ' img').first();
		}
		if (!$img.length || $img.hasClass(this.g_isPlaceholderNoIndex)) {
			return;
		}

		var fullUrl = $img.attr('data-full') || $img.attr('src') || '';
		if (!fullUrl) {
			return;
		}

		var filename = $img.attr('data-filename') || '';
		var attachmentId = $img.attr('data-id');
		var self = this;

		function startDownload(url, name) {
			self.downloadImageFile(url, name || self.getFilenameFromImageUrl(url));
		}

		if (filename) {
			startDownload(fullUrl, filename);
			return;
		}

		if (attachmentId && attachmentId !== '0' && typeof wp !== 'undefined' && wp.media && wp.media.attachment) {
			var attachment = wp.media.attachment(attachmentId);
			attachment.fetch().done(function () {
				var json = attachment.toJSON();
				var url = (json.sizes && json.sizes.full && json.sizes.full.url) ? json.sizes.full.url : (json.url || fullUrl);
				var name = json.filename || self.getFilenameFromImageUrl(url);
				if (name) {
					$img.attr('data-filename', name);
				}
				startDownload(url, name);
			}).fail(function () {
				startDownload(fullUrl, '');
			});
			return;
		}

		startDownload(fullUrl, '');
	}

	//sanitize post name
	sanitizePostName(str) {
		return str
			.toString()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.toLowerCase()
			.trim()
			.replace(/[^\p{L}\p{N}\s-]/gu, '')  // 🔥 любые буквы (включая Hebrew) + цифры
			.replace(/\s+/g, '-')
			.replace(/-+/g, '-')
			.replace(/^-+|-+$/g, '');
	}

	// fill slug from title
	onFillSlugFromTitle(e) {
		var $activeCell = jQuery(this.g_isActiveCell);
		var objCurrent = jQuery(this.g_isActiveCell);
		var parent_tr = objCurrent.parents('tr');
		var current_title = jQuery(this.g_editorContainer + '[data-column="post_title"] ' + this.g_editorPart + ' ' + this.g_editorInput, parent_tr).val();

		var new_slug = this.sanitizePostName(current_title);
		/*
		if (window.ubaiPrompts) {
			window.ubaiPrompts.setPromptReplaceDialogText(new_slug);
			window.ubaiPrompts.showPromptReplaceDialogForSelection();
		}
		*/
		jQuery(this.g_editorInput, objCurrent).val(new_slug);
		this.onCellContentSave(jQuery(this.g_editorInput, objCurrent));
	}

	// open edit link in new tab
	editInNewWindow(e) {
		var $icon = jQuery(e.currentTarget).closest('.edit_in_new_window, .edit_in_elementor');
		var objCurrent = $icon.length ? $icon.closest('td') : jQuery(e.target).parents('td');
		var post_id = objCurrent.attr('data-row');
		var isElementor = $icon.hasClass('edit_in_elementor') || objCurrent.closest('tr').attr('data-is-elementor') === '1';
		var edit_url = g_postEditLink.replace('%PID', post_id);
		if (isElementor && typeof g_postElementorEditLink !== 'undefined' && g_postElementorEditLink) {
			edit_url = g_postElementorEditLink.replace('%PID', post_id);
		}
		window.open(edit_url, '_blank');
	}

	reloadRowInfo() {
		this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderNoIndex);

		var self = this;
		var data =
		{
			post_id: this.editedPostID,
			post_type: jQuery(this.g_ubaiPostTypeSelector).val()
		}
		this.g_doublyAdmin.ajaxRequest('update_post_row', data, function (response) {
			self.$table.find('tr[data-id=' + self.editedPostID + ']').replaceWith('');
			self.fillCellsWithContent(response.message.rowdata, false, false);
			//self.initCategorySelectorSelect2();
			//self.initStickyPostTitleandID();
			//self.setCategoryClassInTD();
		});

	}



	onCellContentSetUp($row_id, $column_name, $value) {
		var self = this;
		var this_editor_content = this.$table.find('tr[data-id="' + $row_id + '"] ' + this.g_editorContainer + '[data-column="' + $column_name + '"]');

		var this_tr = this.$table.find('tr[data-id="' + $row_id + '"]');

		let type = this_editor_content.attr('data-type');

		// process slug
		if (type == 'acf_select') {

			jQuery(this.g_editorPart + ' .' + type + '_editor_input', this_editor_content).val($value);

			var current_selection = jQuery(this.g_editorPart + ' .' + type + '_editor_input', this_editor_content).select2('data');
			if (current_selection.length == 1) {
				var $single_tab_bubble = jQuery('<div>', {
					class: this.g_singleTagBubbleNoPrefix,
					text: current_selection[0].text
				});
				jQuery(this.g_visualPart, this_editor_content).empty().append($single_tab_bubble);
			} else {
				jQuery(this.g_visualPart, this_editor_content).empty();
				jQuery.each(current_selection, function (index, val) {
					var $single_tab_bubble = jQuery('<div>', {
						class: self.g_singleTagBubbleNoPrefix,
						text: val.text
					});
					jQuery(self.g_visualPart, this_editor_content).append($single_tab_bubble);
				})
			}



		}

		if (type == 'taxonomy') {

			//jQuery(this.g_visualPart, this_editor_content).empty();
			jQuery(self.g_visualPart + ' .category_cell_td_list input[type="checkbox"]', this_editor_content).prop('checked', false);
			jQuery(self.g_ubaiTaxBlock + ' ' + this.g_singleTagBubble, this_editor_content).replaceWith();

			jQuery.each($value, function (index, value) {
				var current_checkbox = jQuery(self.g_visualPart + ' .category_cell_td_list input[type="checkbox"][value="' + value + '"]', this_editor_content);
				current_checkbox.prop('checked', true);
			})

			var definde_items_count = 0;
			// Get the text inside the label
			jQuery(self.g_categoryEditor + ' input[type="checkbox"]:checked', this_editor_content).each(function () {


				if (definde_items_count < 2) {
					var text = jQuery(this).closest('label').text().trim();
					var $single_tab_bubble = jQuery('<div>', {
						class: self.g_singleTagBubbleNoPrefix,
						text: text
					});
					jQuery(self.g_ubaiTaxBlock, this_editor_content).prepend($single_tab_bubble);
				}
				definde_items_count++
			});

			// visual counter modifications
			let diff = 0;
			if ($value.length > 2) {
				diff = $value.length - 2;
				jQuery(self.g_taxCounterBlock, this_editor_content).fadeIn();
				jQuery(self.g_taxCounterBlock, this_editor_content).html('+' + diff);
			} else {
				jQuery(self.g_taxCounterBlock, this_editor_content).fadeOut();
			}

		}

		if (type == 'tag') {
			var this_select = jQuery(this.g_editorPart + ' .editor_input', this_editor_content);

			this_select.val($value);
			var selectedData = this_select.select2('data');
			var names = selectedData.map(item => item.text).join(', ');
			var names_array = selectedData.map(item => item.text);


			// add tags to visual
			jQuery(self.g_visualPart, this_editor_content).empty();

			var tags_items_counter = 0;
			names_array.forEach(function (s_text) {

				if (tags_items_counter >= 5) {
					return;
				}
				tags_items_counter++;

				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: s_text
				});
				jQuery(self.g_visualPart, this_editor_content).append($single_tab_bubble);
			})

			if (names_array.length > 5) {
				var tags_difference = names_array.length - 5;
				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: '+' + tags_difference
				});
				jQuery(self.g_visualPart, this_editor_content).append($single_tab_bubble);
			}

		}

	}

	onCellCopy(e) {
		// make work only on cell
		if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') {
			return false;
		}
		var objCurrent = jQuery(this.g_isActiveCell);
		let self = this;


		var type = objCurrent.find(this.g_editorContainer).data('type');
		var parent_container = objCurrent.find(this.g_editorContainer);
		var this_row = objCurrent.data('row');
		var post_id = this_row;
		var this_col = objCurrent.data('col');
		var target_class = '.cell_' + this_row + '_' + this_col;
		var parent_editor_container = objCurrent.find(this.g_editorContainer);
		var cell_column_name = objCurrent.find(this.g_editorContainer).data('column');

		navigator.clipboard.writeText('')
			.then(() => {

			})
			.catch(err => {

			});

		g_copyPastType = type;

		if (type == 'textarea' || type == 'text') {
			g_copyPastSelectName = '';
			g_copyPastValue = jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val();
			navigator.clipboard.writeText(g_copyPastValue);

		}
		if (type == 'acf_select' || type == 'tag' || type == 'post_object' || type == 'woo_post_object') {
			g_copyPastValueArray = [];
			g_copyPastValueTextArray = [];
			var is_multiple = jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).attr('multiple');

			if (!is_multiple) {
				// single value
				g_copyPastValueArray.push(jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val());
				g_copyPastValueTextArray.push(jQuery(this.g_editorPart + ' .' + type + '_editor_input option:selected', objCurrent).text());
			} else {
				jQuery(this.g_editorPart + ' .' + type + '_editor_input option:selected', objCurrent).each(function () {
					g_copyPastValueArray.push(jQuery(this).val());
					g_copyPastValueTextArray.push(jQuery(this).text());
				});
			}

			g_copyPastSelectName = jQuery(this.g_editorContainer, objCurrent).data('column');

		}
		if (type == 'image') {
			g_copyPastSelectName = '';
			g_copyPastValue = jQuery(this.g_ubaiFeaturedImageUploader, objCurrent).attr('data-id');
			g_copyPastImageURL = jQuery(this.g_ubaiFeaturedImageUploader, objCurrent).attr('src');
		}
		if (type == 'product_attribute') {
			g_copyPastValue = post_id;
		}
		if (type == 'calendar') {
			g_copyPastSelectName = '';
			g_copyPastValue = jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val();
		}
		if (type == 'color_picker') {
			g_copyPastSelectName = '';
			g_copyPastValue = jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val();
		}
		if (type == 'acf_gallery' || type == 'acf_woo_gallery') {

			g_copyPastSelectName = '';
			g_copyPastValue = jQuery(this.g_editorContainer, objCurrent).data('value');
			g_copyPastHTML = jQuery(this.g_galleryImagesContainer, objCurrent).html();

			jQuery(this.g_singleImageContainer, objCurrent).each(function () {
				g_copyPastValueArray.push(jQuery(this).data('id'));
				g_copyPastValueImgArray.push(jQuery(this).data('img'));
			})

		}
		if (type == 'wysiwyg' || type == 'repeater') {

			g_copyPastSelectName = '';
			g_wyswygPostID = objCurrent.attr('data-row');
			g_copyPastValue = jQuery(this.g_visualPart, objCurrent).html();

		}
		if (type == 'taxonomy') {

			g_copyPastValueArray = [];

			g_copyPastSelectName = '';

			jQuery(this.g_categoryEditor + ' input[type="checkbox"]:checked', parent_container).each(function () {
				g_copyPastValueArray.push(jQuery(this).val());
			})
			g_copyPastValueArray = g_copyPastValueArray.filter((value, index, self) => {
				return self.indexOf(value) === index;
			});

		}

		return;
	}

	async getClipboardText() {
		return await navigator.clipboard.readText();
	}
	// paste in cell
	async onCellPaste(e) {
		var self = this;
		
	
		if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') {
			return false;
		}

		var objCurrent = jQuery(this.g_isActiveCell);
		if (e.target.tagName === 'TD') {
			objCurrent = jQuery(e.target);
		}

		var type = objCurrent.find(this.g_editorContainer).data('type');
		var parent_container = objCurrent.find(this.g_editorContainer);
		var this_row =  objCurrent.data('row');
		var post_id =  objCurrent.data('row');
		var this_col = objCurrent.data('col');
		var target_class = '.cell_' + this_row + '_' + this_col;
		var parent_editor_container = objCurrent.find(this.g_editorContainer);
		var cell_column_name = objCurrent.find(this.g_editorContainer).data('column');
		var clipboardText = '';


		// image instrt
		 try {
			const items = await navigator.clipboard.read();
 
			for (const item of items) {
				for (const type of item.types) {
					if (type.startsWith('image/')) {
						const blob = await item.getType(type);

						const reader = new FileReader();

						reader.onload = function (e) {
							const base64 = e.target.result; 
							// выглядит так: data:image/png;base64,iVBORw0...
							self.g_doublyAdmin.setAjaxLoaderID(self.g_ajaxLoaderNoIndex);
							
							var data = {
								post_id: post_id,
								field_name: cell_column_name,
								image: base64,
							};

							self.g_doublyAdmin.ajaxRequest('paste_image_from_clipboard', data, function (response) {
									var msg = response && response.message ? response.message : {};
									self.syncImageCellAttachment(parent_editor_container, msg.id, msg.url);
									self.onCellContentSave(parent_editor_container, true);
							});
						};

						reader.readAsDataURL(blob);

						return;
					}
				}
			}
		} catch (err) {
			console.error(err);
		}

		// image instrt end

		if (e.originalEvent.clipboardData) {
			clipboardText = e.originalEvent.clipboardData.getData('text/plain');
		}

		const text = await this.getClipboardText();
		if (clipboardText == '') {
			clipboardText = text;
		}

		if (clipboardText != '') {
			g_copyPastValue = clipboardText;
			g_copyPastType = 'textarea';

			// patch for text not textarea
			if (type == 'text') {
				g_copyPastType = 'text';
			}

			const hexRegex = /^#?([a-f0-9]{6}|[a-f0-9]{3}|[a-f0-9]{8}|[a-f0-9]{4})$/i;
			if (hexRegex.test(clipboardText)) {
				g_copyPastType = 'color_picker';
			}
		}

		// do not run when empty
		if (type == 'acf_select' || type == 'tag' || type == 'taxonomy' || type == 'post_object' || type == 'woo_post_object' || type == 'color_picker' || type == 'product_attribute') {

		} else {
			if (g_copyPastValue === '') {
				return;
			}
		}

	
	 
		if (g_copyPastType != type || (g_copyPastSelectName && g_copyPastSelectName != cell_column_name)) {
			var $errorMessage = jQuery('<div>', {
				class: 'incell_error_message',
				text: sheetspilot.editor.wrong_cell_type
			});
			jQuery(objCurrent).append($errorMessage);
			setInterval(function () {
				$errorMessage.fadeOut(function () {
					$errorMessage.replaceWith('');
				})
			}, 2000);
			return;
		}

		// process slug
		if (type == 'text' && cell_column_name == 'post_name') {
			let slug_new_value = g_copyPastValue; // + '-copy-' + this_row;
			jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val(slug_new_value);
			//jQuery(this.g_visualPart, objCurrent).html( slug_new_value );
		}


		if (type == 'textarea' || (type == 'text' && cell_column_name != 'post_name')) {
			jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val(g_copyPastValue)
			jQuery(this.g_visualPart, objCurrent).html(g_copyPastValue);
		}


		if (type == 'acf_select' || type == 'tag') {

			let self = this;
			jQuery(this.g_visualPart, objCurrent).empty();
			jQuery.each(g_copyPastValueTextArray, function (index, value) {
				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: value
				});

				jQuery(self.g_visualPart, objCurrent).append($single_tab_bubble);
			})

			jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val(g_copyPastValueArray).trigger('change');

			parent_editor_container = jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent);

		}
		if (type == 'post_object' || type == 'woo_post_object') {

			let self = this;
			jQuery(this.g_visualPart, objCurrent).empty();
			jQuery.each(g_copyPastValueTextArray, function (index, value) {
				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: value
				});

				jQuery(self.g_visualPart, objCurrent).append($single_tab_bubble);
			})

			let cnt = 0;
			jQuery.each(g_copyPastValueTextArray, function (index, value) {
				var $option = jQuery('<option>', {
					value: g_copyPastValueArray[cnt],
					text: g_copyPastValueTextArray[cnt],
					selected: true,
				});

				jQuery(self.g_editorPart + ' .' + type + '_editor_input', objCurrent).append($option);
				cnt++;
			})


			jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val(g_copyPastValueArray).trigger('change');
			parent_editor_container = jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent);
		}
	 
		if (type == 'image') {
			this.syncImageCellAttachment(parent_editor_container, g_copyPastValue, g_copyPastImageURL);
		}
		if (type == 'calendar') {
			jQuery(this.g_visualPart, objCurrent).html(g_copyPastValue);
			jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val(g_copyPastValue);
			jQuery(this.g_editorContainer, objCurrent).data('value', g_copyPastValue);
		}

		if (type == 'color_picker') {
			var $single_tab_bubble = jQuery('<div>', {
				class: this.g_singleTagBubbleNoPrefix,
				text: g_copyPastValue,
				css: { background: g_copyPastValue }
			});

			jQuery(this.g_visualPart + ' ' + this.g_singleTagBubble, objCurrent).replaceWith('');
			jQuery(this.g_visualPart, objCurrent).append($single_tab_bubble);
			jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val(g_copyPastValue);

			parent_container.attr('data-value', g_copyPastValue);

		}
		if (type == 'acf_gallery' || type == 'acf_woo_gallery') {
			jQuery(this.g_editorContainer, objCurrent).data('value', g_copyPastValue);
			jQuery(this.g_galleryImagesContainer, objCurrent).html(g_copyPastHTML);
		}
		if (type == 'wysiwyg' || type == 'repeater') {

			g_copyPastSelectName = '';

			jQuery(this.g_visualPart, objCurrent).html(g_copyPastValue);
			var target_id = objCurrent.attr('data-row');
			var field_name = objCurrent.find(this.g_editorContainer).data('column');
			var data =
			{
				source_id: g_wyswygPostID,
				target_id: target_id,
				field_name: field_name,
			};

			this.g_doublyAdmin.ajaxRequest('copy_acf_content', data, function (response) {
			});

		}
		if (type == 'taxonomy') {
			let self = this;
			g_copyPastSelectName = '';
			//jQuery( this.g_visualPart, objCurrent).empty(  ); 
			//jQuery( this.g_visualPart, objCurrent).html( g_copyPastValue ); 

			jQuery('input[type="checkbox"]', parent_container).each(function () {
				jQuery(this).prop('checked', false);

				if (jQuery.inArray(jQuery(this).val(), g_copyPastValueArray) !== -1) {
					jQuery(this).prop('checked', true);
				}
			});

		}

		if (type == 'product_attribute') {

			var target_id = objCurrent.attr('data-row');
			var field_name = objCurrent.find(this.g_editorContainer).data('column');

			var data =
			{
				source_id: g_copyPastValue,
				target_id: target_id,
				field_name: field_name,
			};

			this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderNoIndex);
			this.g_doublyAdmin.ajaxRequest('copy_product_attribute', data, function (response) {
				jQuery('.countable_cell_inner_counter', parent_container).html(response.message.count + ' ' + sheetspilot.editor.items);
			});
			return;
		}

		// on prompt past
		var dataApplyPromptOnPaste =  jQuery('#new_output_table th[data-name="'+cell_column_name+'"] .unlimitedai-plugin__ai-column-settings-icon ').attr('data-apply-prompt-on-paste');
		if( dataApplyPromptOnPaste == 'true' ){
			objPostsEditorView.runPromptOnSelectedCell( '', 'apply_column_rules', g_copyPastValue );
		}

		this.onCellContentSave(parent_editor_container, true);


	}

	/**
	 * undo cell editions
	 */
	undoCellEditions() {
		let self = this;
		let last_array_element = this.undoStackArchive.pop();
		const type = last_array_element.type;
		const value = last_array_element.value;
		const cell_address = last_array_element.cell;

		const parent_container = jQuery(cell_address).find(this.g_editorContainer);


		 

		if (last_array_element.action_type) {
			// for selects
			if (last_array_element.action_type == 'bulk_is_bulk_change' && last_array_element.columnType == 'text') {
				let inital_rows = last_array_element.rows;
				let inital_values = last_array_element.values;

				let index_counter = 0;
				jQuery.each(inital_rows, function (index, value) {

					var this_parent_container = jQuery('tr[data-id="' + value + '"] .editor_container[data-column="' + last_array_element.columnName + '"] ');
					jQuery(self.g_editorPart + ' .editor_input ', this_parent_container).val(inital_values[index_counter]);
					self.onCellContentSave(jQuery('.editor_input', this_parent_container));
					index_counter++;

				})
			}

			// for category
			if (last_array_element.action_type == 'bulk_is_bulk_change' && last_array_element.columnType == 'category') {
				let inital_rows = last_array_element.rows;
				let inital_values = last_array_element.values;

				let index_counter = 0;
				jQuery.each(inital_rows, function (index, value) {

					var this_parent_container = jQuery('tr[data-id="' + value + '"] .editor_container[data-column="' + last_array_element.columnName + '"] ');

					// drop selectors				
					jQuery(self.g_visualPart + ' .category_cell_td_list input[type="checkbox"]:checked', this_parent_container).prop('checked', false);

					jQuery.each(inital_values[value], function (index_inner, value_inner) {
						var current_checkbox = jQuery(self.g_visualPart + ' .category_cell_td_list input[type="checkbox"][value="' + value_inner + '"]', this_parent_container);
						current_checkbox.prop('checked', true);
					})

					self.onCellContentSave(this_parent_container);
					index_counter++;


				})
			}

			// for category
			if (last_array_element.action_type == 'bulk_is_bulk_change' && last_array_element.columnType == 'tag') {
				let inital_rows = last_array_element.rows;
				let inital_values = last_array_element.values;

				let index_counter = 0;
				jQuery.each(inital_rows, function (index, value) {

					var this_parent_container = jQuery('tr[data-id="' + value + '"] .editor_container[data-column="' + last_array_element.columnName + '"] ');

					// drop selectors				
					//jQuery( self.g_visualPart + ' .category_cell_td_list input[type="checkbox"]:checked', this_parent_container).prop('checked', false);

					var this_parent_container = jQuery('tr[data-id="' + value + '"] .editor_container[data-column="' + last_array_element.columnName + '"] ');

					jQuery(self.g_editorPart + ' .editor_input ', this_parent_container).val(inital_values[value]);

					self.onCellContentSave(jQuery('.editor_input', this_parent_container));
					index_counter++;


				})
			}

		}


		if (type == 'textarea' || type == 'text') {
			// get current editor content	
			jQuery(this.g_editorPart + ' .' + type + '_editor_input', parent_container).val(value);
			jQuery(this.g_visualPart + ' .' + type + '_editor_input', parent_container).val(value);
			this.onCellContentSave(parent_container);
		}
		if (type == 'acf_select' || type == 'post_object' || type == 'woo_post_object') {
			jQuery(this.g_editorPart + ' .' + type + '_editor_input', parent_container).val(value);
			const selector_pointer = parent_container.find('.' + type + '_editor_input');
			this.onCellContentSave(selector_pointer);
		}

		if (type == 'is_post_delete'   ) {

			// we restore deleted post
			var data =
			{
				post_id: value,
				post_type: jQuery(this.g_ubaiPostTypeSelector).val(),
			};
			g_doublyAdmin.setAjaxLoaderID( this.g_ajaxLoaderProcessing );
			g_doublyAdmin.ajaxRequest('get_restored_post', data, function (response) {

				self.fillCellsWithContent(response.message.postslist);
				
				self.g_postEditorView.processPagination(response.message.total_count, response.message.posts_per_page, parseInt(jQuery( self.g_postEditorView.g_pagiPagesFirst ).html()))

				self.g_postEditorView.applyColumnOrder(initialFilteredColumns);
				self.g_postEditorView.syncSelectorsToColumns();

				// reiniti custom inputs
				self.g_postEditorView.initiateSelect2Inputs();
				self.initCategorySelectorSelect2();
				self.initStickyPostTitleandID();
				self.hideDeleteForEmptyGalleries();
				self.hideEditImageForNoFeatured();

			});
		}
		if (type == 'is_post_duplicate') {
			// we remove duplicated post
			this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderNoIndex);
			var data =
			{
				post_id: value,
			};

			this.g_doublyAdmin.ajaxRequest('remove_table_post', data, function (response) {
				var this_row_pointer = jQuery('tr[data-id="' + value + '"]');
				this_row_pointer.fadeOut(200, function () {
					this_row_pointer.replaceWith('');
				})
			});
		}


	}


	/**
	 * Uses document as the event bus so any module can listen.
	 */
	triggerEvent(eventName, params) {
		// Normalize optional params for jQuery trigger
		var payload = (typeof params === "undefined") ? null : params;

		jQuery(document).trigger(eventName, payload);
	}

	onEvent(eventName, func) {

		jQuery(document).on(eventName, func);

	}

	/**
	 * Show the cell context menu at the given position. Shared by right-click and Quick Prompts trigger.
	 * @param {number} left - CSS left (px).
	 * @param {number} top - CSS top (px).
	 * @param {jQuery} cell - The cell (td) for context (column rules visibility).
	 */
	/**
	 * @param {number} left - CSS left (px).
	 * @param {number} top - CSS top (px).
	 * @param {jQuery} cell - The cell (td) for context (column rules visibility).
	 * @param {number} [menuWidth] - If set (e.g. from Quick Actions combo), menu width is fixed to this; otherwise menu sizes to content.
	 */
	showContextMenuAt(left, top, cell, menuWidth) {
		var $menu = jQuery('#ubai_text_context_menu');
		if (!$menu.length) return;

		// Ensure Copy/Paste (and their separators) are visible for "normal" context menu usage
		// (e.g. right-click). Sidebar/Quick Prompts may hide them after this call.
		var $copyItem = $menu.find('#ubai_context_menu_copy_action');
		var $pasteItem = $menu.find('#ubai_context_menu_paste_action');
		if ($copyItem.length) {
			$copyItem.show();
			var $prevSep = $copyItem.prev('.unlimitedai-plugin__context-menu__separator');
			if ($prevSep.length) $prevSep.show();
		}
		if ($pasteItem.length) {
			$pasteItem.show();
			var $nextSep = $pasteItem.next('.unlimitedai-plugin__context-menu__separator');
			if ($nextSep.length) $nextSep.show();
		}

		var $container = cell && cell.length ? cell.find(this.g_editorContainer).first() : jQuery();

		const index = cell.index();
		const $th = cell.closest('table').find('thead th').eq(index);

		if ($th.hasClass('modal_off')) {
			return;
		}

		var columnName = $container.length ? ($container.data('column') || $container.attr('data-column') || '') : '';
		var rawType = $container.length ? ($container.attr('data-type') || $container.data('type') || '') : '';
		var cellType = 'nothing';
		
		if (rawType === 'image' || rawType === 'acf_gallery' || rawType === 'acf_woo_gallery' ) {
			cellType = 'image';
		} else if (rawType === 'repeater' || rawType === 'acf_repeater') {
			cellType = 'repeater';
		} else if (rawType === 'text' || rawType === 'textarea' || rawType === 'wysiwyg') {
			cellType = 'text';
		}
	 
		var hasColumnRules = ubaiColumnHasSavedCellRules(columnName);
		var $applyRulesItem = jQuery('#ubai_context_menu_apply_column_rules');
		// Keep the item visible, but gray/disable it when there are no rules for this column.
		// The click handler already ignores items with `--disabled`. (Absent in Free tier menu.)
		if ($applyRulesItem.length) {
			$applyRulesItem.show();
			if (hasColumnRules) {
				$applyRulesItem.removeClass('unlimitedai-plugin__context-menu__item--disabled').attr('aria-disabled', 'false');
			} else {
				$applyRulesItem.addClass('unlimitedai-plugin__context-menu__item--disabled').attr('aria-disabled', 'true');
			}
		}
		var $compressItem = jQuery('#ubai_context_menu_compress_image');
		if ($compressItem.length && cellType === 'image' && cell && cell.length) {
			var $compressContainer = cell.find(this.g_editorContainer).first();
			var $compressImg = $compressContainer.find(this.g_ubaiFeaturedImageUploader).first();
			if (!$compressImg.length) {
				$compressImg = $compressContainer.find(this.g_visualPart + ' img').first();
			}
			var compressAttachId = $compressImg.length ? String($compressImg.attr('data-id') || '').trim() : '';
			var compressIsPlaceholder = $compressImg.length && (
				$compressImg.hasClass(this.g_isPlaceholderNoIndex)
				|| String($compressImg.attr('src') || '').toLowerCase().indexOf('placeholder.png') !== -1
			);
			var compressHasImage = compressAttachId !== '' && compressAttachId !== '0' && !compressIsPlaceholder;
			if (!compressHasImage) {
				var compressValue = String($compressContainer.data('value') || '').trim();
				compressHasImage = /^\d+$/.test(compressValue) && compressValue !== '0';
			}
			if (compressHasImage) {
				$compressItem.removeClass('unlimitedai-plugin__context-menu__item--disabled').attr('aria-disabled', 'false');
			} else {
				$compressItem.addClass('unlimitedai-plugin__context-menu__item--disabled').attr('aria-disabled', 'true');
			}
		}
		var css = { left: left + 'px', top: top + 'px' };

		// Show/hide menu items by cell type and optional column filter (data from PHP)
		jQuery('.unlimitedai-plugin__context-menu__item', $menu).each(function () {
			var $item = jQuery(this);
			var visibleFor = $item.attr('data-visible-for') || 'all';
			var visibleInColumns = $item.attr('data-visible-in-columns') || '';
			var showByType;
			if (visibleFor === 'all') {
				showByType = true;
			} else {
				var types = visibleFor.split(',').map(function (s) { return s.trim(); });
				showByType = types.indexOf(cellType) !== -1;
			}
			var showByColumn = !visibleInColumns || (columnName && visibleInColumns.split(',').indexOf(columnName) !== -1);
			var invisibleFor = $item.attr('data-invisible-for-cell-type') || 'all';
			var hiddenByInvisible = false;
			if (invisibleFor && invisibleFor !== 'all') {
				var invTypes = invisibleFor.split(',').map(function (s) { return s.trim(); });
				hiddenByInvisible = invTypes.indexOf(cellType) !== -1;
			}
			if (showByType && showByColumn && !hiddenByInvisible) {
				$item.show();
			} else {
				$item.hide();
			}
		});
		var isRTL = jQuery('html').attr('dir') === 'rtl';
    var $tableWrapper = jQuery('#new_output_table');
    var margin = 15;
    
    // Prepare for measurement
    $menu.css({ 
        visibility: 'hidden', 
        display: 'block', 
        position: 'absolute'
    });
    
    // Target the first child that actually has dimensions
    var $actualMenu = $menu.children().filter(':visible').first();
    if (!$actualMenu.length) $actualMenu = $menu.children().first();

    var menuW = $actualMenu.outerWidth() || 220; 
    var menuH = $actualMenu.outerHeight() || 350;

    // Viewport-relative click point
    var visualX = left - window.pageXOffset;
    var visualY = top - window.pageYOffset;
    var containerRect = $tableWrapper[0].getBoundingClientRect();

    // Viewport Clamping
    // We adjust visualX and visualY so the child menu fits in the viewport
    if (isRTL) {
        // Grow left: Click at 50, Menu 200 -> Offscreen.
        if (visualX - menuW < margin) {
            visualX = menuW + margin; 
        }
        if (visualX > window.innerWidth - margin) {
            visualX = window.innerWidth - margin;
        }
    } else {
        // Grow right: Click at 1200, Menu 200, Viewport 1300 -> Offscreen.
        if (visualX + menuW > window.innerWidth - margin) {
            visualX = window.innerWidth - menuW - margin;
        }
        if (visualX < margin) {
            visualX = margin;
        }
    }

    // Vertical Clamp
    if (visualY + menuH > window.innerHeight - margin) {
        visualY = window.innerHeight - menuH - margin;
    }
    if (visualY < margin) visualY = margin;

    var finalTop = visualY - containerRect.top + $tableWrapper.scrollTop();

    if (isRTL) {
        var visualRight = window.innerWidth - visualX;
        var containerVisualRight = window.innerWidth - containerRect.right;
        var finalRight = (visualRight - containerVisualRight) - $tableWrapper.scrollLeft();

        $menu.css({
            left: 'auto',
            right: finalRight + 'px',
            top: finalTop + 'px',
            visibility: 'visible'
        });
    } else {
        var finalLeft = visualX - containerRect.left + $tableWrapper.scrollLeft();
        
        $menu.css({
            right: 'auto',
            left: finalLeft + 'px',
            top: finalTop + 'px',
            visibility: 'visible'
        });
    }

    $menu.show();
	}

	/**
	 * Show the cell context menu at the cursor. Called on cell contextmenu (right-click).
	 * @param {Event} event - Native contextmenu event.
	 * @param {jQuery} cell - The clicked cell (td).
	 */
	showContextMenu(event, cell) {
		event.preventDefault();
		this.showContextMenuAt(event.clientX, event.clientY, cell);
	}

	/**
	 * Show the cell context menu at a position (e.g. below Quick Prompts trigger). Uses current active cell for context.
	 * Same menu and handlers as right-click; runs prompt on selected cell.
	 * @param {number} [menuWidth] - If set (e.g. from Quick Actions combo), menu width matches the trigger.
	 */
	showContextMenuAtPosition(left, top, menuWidth) {
		var $activeCell = this.$table.find('td.' + this.g_isActiveCellNoIndex).first();
		this.showContextMenuAt(left, top, $activeCell, menuWidth);

		// This method is used by the sidebar "Quick Prompts" dropdown.
		// For Pro, hide Copy/Paste so only prompt actions show. Free tier has only Copy/Paste — keep them visible.
		var editorLoc = (typeof sheetspilot !== 'undefined' && sheetspilot.editor) ? sheetspilot.editor : {};
		if (!editorLoc.isPro) {
			return;
		}

		var $menu = jQuery('#ubai_text_context_menu');
		var $copyItem = $menu.find('#ubai_context_menu_copy_action');
		var $pasteItem = $menu.find('#ubai_context_menu_paste_action');

		if ($copyItem.length) {
			$copyItem.hide();
			// Hide separator immediately before copy item (if present)
			var $prevSep = $copyItem.prev('.unlimitedai-plugin__context-menu__separator');
			if ($prevSep.length) $prevSep.hide();
		}
		if ($pasteItem.length) {
			$pasteItem.hide();
			// Hide separator immediately after paste item (if present)
			var $nextSep = $pasteItem.next('.unlimitedai-plugin__context-menu__separator');
			if ($nextSep.length) $nextSep.hide();
		}
	}

	/**
	 * Hide the cell context menu. Also resets Quick Prompts trigger aria-expanded when menu was opened from there.
	 */
	hideContextMenu() {
		jQuery('#ubai_text_context_menu').hide().removeClass('is-fixed');
		jQuery('#ubai_quick_actions_combo').attr("aria-expanded", "false");
		jQuery('#ubai_quick_actions_combo_images').attr("aria-expanded", "false");
	}

	/*
	// trace save of guttenberg sidebar
	hookGutenbergRepeaterSave(win) {
		const self = this;
		let wasSaving = false;

		win.wp.data.subscribe(() => {
			const editor = win.wp.data.select('core/editor');

			if (!editor) return;

			const isSaving = editor.isSavingPost();
			const isDirty = editor.isEditedPostDirty();

			if (wasSaving && !isSaving && !isDirty) {
				this.editorDrawer.onDrawerClose();
				// ajax to update
				var data =
				{
					post_id: g_wyswygPostID,
					field_name: g_wyswygFieldName,
				};

				this.g_doublyAdmin.ajaxRequest('get_repeater_content', data, function (response) {
					jQuery(g_wyswygCellID + ' .rerepater_visual_center').html(response.message);
				});

			}

			wasSaving = isSaving;
		});
	}
	*/
	// trace save of guttenberg modal
	hookGutenbergModalSave(win) {
		const self = this;
		let wasSaving = false;

		win.wp.data.subscribe(() => {

			const editor = win.wp.data.select('core/editor');

			if (!editor) return;

			const isSaving = editor.isSavingPost();
			const isDirty = editor.isEditedPostDirty();

			if (wasSaving && !isSaving && !isDirty) {

				if (document.cookie.includes('closeAfterSave=1')) {
					self.g_postEditorView.closePostEditPopupOuter();
					document.cookie = "closeAfterSave=0; path=/";
				}

			}

			wasSaving = isSaving;
		});
	}


	editWyswygSidebar(clicked_object) {
		var self = this;

		const $clicked = jQuery(clicked_object);

		var parent_td = $clicked.parents('td');
		var parent_container = parent_td.find(this.g_editorContainer);
		var post_id = parent_td.data('row');
		var field_name = parent_container.data('column');
		var col_id = parent_td.data('col');


		this.editorDrawer.dropDrawerBody();
		self.editorDrawer.onDrawerOpen();
		this.editorDrawer.showLoader();


		g_wyswygPostID = post_id;
		g_wyswygFieldName = field_name;
		g_wyswygCellID = '.cell_' + post_id + '_' + col_id;

		var data =
		{
			post_id: post_id,
			field_name: field_name,
			cell_address: '.cell_' + post_id + '_' + col_id,
		}
			;
		this.g_doublyAdmin.ajaxRequest('get_wyswyg_content', data, function (response) {
			self.editorDrawer.dropDrawerBody();
			self.editorDrawer.setDrawerBody(response.message.html);

			if (typeof tinymce !== 'undefined') {
				var editor_id = '#' + response.message.id;
				g_wyswygEditorID = response.message.id;
				tinymce.init({
					selector: editor_id,
					menubar: false,
					//plugins: 'lists link image',
					//toolbar: 'bold italic | bullist numlist | link | code',
					height: 500
				});
			}

			self.editorDrawer.onDrawerOpen();
		});
	}

	saveWyswygContent(e) {
		var self = this;
		const content = wp.editor.getContent(g_wyswygEditorID);

		var data =
		{
			post_id: g_wyswygPostID,
			field_name: g_wyswygFieldName,
			content: content,
		};

		this.g_doublyAdmin.ajaxRequest('save_wyswyg_content', data, function (response) {
			jQuery(g_wyswygCellID + ' ' + self.g_visualPart).html(response.message.text);
			self.editorDrawer.onDrawerClose();
		});

	}
	// drop repeater cotnent
	onRepeaterDrop(e) {
		var self = this;
		var empty_ids = [];
		let attachment;
		const $clicked = jQuery(e.currentTarget);
		var parent_td = $clicked.parents('td');
		var parent_container = parent_td.find(this.g_editorContainer);
		var post_id = parent_td.attr('data-row');

		var repeater_name = parent_container.attr('data-column');

		var data =
		{
			post_id: post_id,
			repeater_name: repeater_name,
		};

		this.g_doublyAdmin.ajaxRequest('drop_repeater_content', data, function (response) {
			jQuery('.countable_cell_inner_counter', parent_container).html(0);
		});

		this.hideDeleteForEmptyGalleries();
	}

	// drop gallery images
	onGalleryDrop(e) {
		var self = this;
		var empty_ids = [];
		let attachment;
		const $clicked = jQuery(e.currentTarget);
		var parent_td = $clicked.parents('td');
		var parent_container = parent_td.find(this.g_editorContainer);
		var parent_gallery_images_container = parent_td.find(this.g_galleryImagesContainer);

		parent_gallery_images_container.empty();
		parent_container.data('value', empty_ids.join(','));

		// total images counter
		var $single_image_counter = jQuery('<div>', {
			class: self.g_singleImageCounterNoIndex,
			text: '0 ' + sheetspilot.editor.images
		});
		parent_gallery_images_container.find(self.g_singleImageCounter).replaceWith('');
		parent_gallery_images_container.append($single_image_counter);

		self.onCellContentSave(parent_gallery_images_container);
		this.hideDeleteForEmptyGalleries();

	}

	editRepeaterFieldSidebar(clicked_object) {
		var self = this;
		jQuery('#TB_iframeSidebarContent').replaceWith('');
		const $clicked = jQuery(clicked_object);
		var parent_td = $clicked.parents('td');
		var post_id = parent_td.attr('data-row');
		var col_id = parent_td.data('col');
		var parent_container = parent_td.find(this.g_editorContainer);
		var field_name = parent_container.data('column');

		var col_index = parent_td.index();
		var col_header = jQuery('th', this.$tableHead).eq(col_index);
		var col_header_text = jQuery('.unlimitedai-plugin__th-title', col_header).html();

		g_wyswygPostID = post_id;
		g_wyswygFieldName = field_name;
		g_wyswygCellID = '.cell_' + post_id + '_' + col_id;

		this.editorDrawer.dropDrawerBody();


		// new sidebar	
		var $iframe = jQuery('<iframe>', {
			src: g_urlAjaxActionsSheetsPilot.replace('admin-ajax.php', '') +
				'post.php?post=' + post_id + '&action=edit&TB_iframe=true&loadedinsidebar=true&isrepeatereditor=true',
			id: 'TB_iframeSidebarContent',
			frameborder: 0,
			allowfullscreen: true
		});

		$iframe.on('load', function () {
			const iframe = this;
			const win = iframe.contentWindow;
			const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

			const style = iframeDoc.createElement('style');

			let non_acfname = field_name.replace("acf_", "");
			style.innerHTML = '\
			.acf-field.acf-field-repeater{ display:none; }\
			.acf-field.acf-field-repeater[data-name="'+ non_acfname + '"]{ display:block  !important; }';
			iframeDoc.head.appendChild(style);

			// waiting for Gutenberg
			const waitForGutenberg = setInterval(() => {
				if (win.wp && win.wp.data && win.wp.data.select) {
					clearInterval(waitForGutenberg);
					self.hookGutenbergRepeaterSave(win);
				}
			}, 200);

			var side_interval = setInterval(function () {
				if (document.cookie.includes('closeAfterSave=1')) {
					document.cookie = "closeAfterSave=0; path=/";
					self.editorDrawer.onDrawerClose();
					clearInterval(side_interval);
				}
			}, 1000);
		});
		this.editorDrawer.setDrawerTitle(col_header_text);
		//this.inititateSidebarLoader();


		this.editorDrawer.appendDrawerBody($iframe);



		this.editorDrawer.onDrawerOpen();

		const parentMarker = setInterval(function () {
			const iframe = document.getElementById('TB_iframeSidebarContent');
			if (!iframe) return;

			const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
			jQuery(iframeDoc.body).addClass('ubai_repeater_editor_sidebar');
			//clearInterval(parentMarker);
		}, 100)

	}

	onGalleryAddImage(clicked_element) {
		var self = this;
		let attachment;
		const $clicked = jQuery(clicked_element);

		var parent_td = $clicked.parents('td');
		var parent_container = parent_td.find(this.g_editorContainer);
		var parent_gallery_images_container = parent_td.find(this.g_galleryImagesContainer);

		if (parent_container.data('value') == '') {
			var used_ids = [];
		} else {
			var used_ids = parent_container.data('value').split(',');
		}

		/*
		jQuery(this.g_singleImageContainer, parent_container).each(function () {
			if (jQuery(this).data('id') != '0') {
				used_ids.push(jQuery(this).data('id'));
			}

		})
		*/

		var file_frame;

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if (file_frame) {
			file_frame.open();
			return;
		}




		if (used_ids.length == 0) {
			// Create the media frame.		
			file_frame = wp.media.frames.file_frame = wp.media({
				frame: 'post',
				state: 'gallery',
				multiple: true
			});

			file_frame.on('ready', function () {
				file_frame.states.remove('audio-playlist');
				file_frame.states.remove('video-playlist');
			});

			// get image container lbock
			var parent_gallery_images_container = jQuery(this.g_galleryImagesContainer, parent_container);
			// When an image is selected, run a callback.


			file_frame.on('update', function () {
				var gallery = file_frame.state('gallery-edit').get('library');
				var all_ids = file_frame.state('gallery-edit').get('library').pluck('id');
				var show_item_counter = 0;
				var images_count = gallery.length - 3;
				if (gallery.length > 1 || gallery.length == 0) {
					var images_text = sheetspilot.editor.images;
				} else {
					var images_text = sheetspilot.editor.image;
				}


				gallery.each(function (attachment) {

					if (show_item_counter >= 3) {
						return;
					}
					show_item_counter++;

					var data = attachment.toJSON();
					if (used_ids.includes(data.id)) {
						return;
					}

					var $single_image_container = jQuery('<div>', {
						class: self.g_singleImageContainerNoIndex+'  sp_hover_preview',
						'data-id': data.id,
						'data-img': data.url,
						'data-full': data.url,
						css: {
							backgroundImage: 'url(' + data.url + ')',
						}
					});
					parent_gallery_images_container.append($single_image_container);

				});

				if (images_count > 0) {
					var $single_image_container = jQuery('<div>', {
						class: self.g_singleImageContainerNoIndex,
						'data-id': 0,
						css: {
							backgroundImage: 'url()',
						},
						text: '+' + images_count
					});
					parent_gallery_images_container.append($single_image_container);
				}

				// total images counter
				var $single_image_counter = jQuery('<div>', {
					class: self.g_singleImageCounterNoIndex,
					text: gallery.length + ' ' + images_text
				});
				parent_gallery_images_container.find(self.g_singleImageCounter).replaceWith('');
				parent_gallery_images_container.append($single_image_counter);

				parent_container.data('value', all_ids.join(','));

		
				self.onCellContentSave(parent_gallery_images_container);
				self.hideDeleteForEmptyGalleries();

			});

		} else {

			// Create the media frame.		
			file_frame = wp.media({
				frame: 'post',
				state: 'gallery-edit',
				multiple: true,
				editing: true
			});

			var parent_gallery_images_container = jQuery(this.g_galleryImagesContainer, parent_container);

			file_frame.on('update', function () {
				var gallery = file_frame.state('gallery-edit').get('library');
				var all_ids = file_frame.state('gallery-edit').get('library').pluck('id');
				parent_gallery_images_container.empty();

				if (gallery.length > 1 || gallery.length == 0) {
					var images_text = sheetspilot.editor.images;
				} else {
					var images_text = sheetspilot.editor.images;
				}

				var show_item_counter = 0;
				var images_count = gallery.length - 3;



				gallery.each(function (attachment) {

					if (show_item_counter >= 3) {
						return;
					}
					show_item_counter++;

					var data = attachment.toJSON();
					var $single_image_container = jQuery('<div>', {
						class: self.g_singleImageContainerNoIndex+' sp_hover_preview',
						'data-id': data.id,
						'data-full': data.url,
						css: {
							backgroundImage: 'url(' + data.url + ')',
						}
					});
					parent_gallery_images_container.append($single_image_container);

				});

				if (images_count > 0) {
					var $single_image_container = jQuery('<div>', {
						class: self.g_singleImageContainerNoIndex,
						'data-id': 0,
						css: {
							backgroundImage: 'url()',
						},
						text: '+' + images_count
					});
					parent_gallery_images_container.append($single_image_container);
				}

				// total images counter
				var $single_image_counter = jQuery('<div>', {
					class: self.g_singleImageCounterNoIndex,
					text: gallery.length + ' ' + images_text
				});
				parent_gallery_images_container.find(self.g_singleImageCounter).replaceWith('');
				parent_gallery_images_container.append($single_image_counter);
				parent_container.data('value', all_ids.join(','));

			 
				self.onCellContentSave(parent_gallery_images_container);
			});

		}

		// Finally, open the modal
		file_frame.open();

		// Optional: Pre-select existing images in the gallery when the frame opens
		var lib = file_frame.state().get('library');
		var existing_ids = used_ids; // Replace with your actual existing gallery image IDs
		if (existing_ids.length > 0) {
			existing_ids.forEach(function (id) {
				var attachment = wp.media.attachment(id);
				attachment.fetch();
				lib.add(attachment);
			});
		}

	}
	onGalleryRemoveImage(e) {
		const $clicked = jQuery(e.currentTarget);
		const this_image_id = $clicked.data('id');
		const parent = $clicked.parents(this.g_singleImageContainer);
		const parent_gallery_images_container = $clicked.parents(this.g_galleryImagesContainer);
		parent.replaceWith('');
		this.onCellContentSave(parent_gallery_images_container);
	}

	toggelCategries(e) {
		const $clicked = jQuery(e.currentTarget);
		var parent_container = $clicked.parents(this.g_ubaiTaxBlock);
		parent_container.toggleClass('is_fill_height_category');
	}

	editPostModal_old(e) {
		const self = this;
		const $clicked = jQuery(e.currentTarget);
		var parent_td = $clicked.parents('td');
		var post_id = parent_td.attr('data-row');
		this.editedPostID = post_id;


		tb_show(
			'Edit post',
			g_urlAjaxActionsSheetsPilot.replace('admin-ajax.php', '') +
			'post.php?post=' + post_id + '&action=edit&TB_iframe=true'
		);

		this.inititateModalLoader();

		let checkIfRemoved;
		let makeVisible = setInterval(function () {
			if (jQuery(self.g_tbIframeContent).length != 0) {

				self.fbModalVisible = true;
				clearInterval(makeVisible);
				checkIfRemoved = setInterval(function () {
					if (jQuery(self.g_tbIframeContent).length == 0) {
						clearInterval(checkIfRemoved);
						self.reloadRowInfo();
					}
				}, 100)
			}
		}, 100)
	}

	editPostModal(e) {
		const self = this;
		const $clicked = jQuery(e.currentTarget);
		var parent_td = $clicked.parents('td');
		var post_id = parent_td.attr('data-row');
		this.editedPostID = post_id;


		this.editorDrawer.dropDrawerBody();

		// new sidebar	
		var $iframe = jQuery('<iframe>', {
			src: g_urlAjaxActionsSheetsPilot.replace('admin-ajax.php', '') +
				'post.php?post=' + post_id + '&action=edit&TB_iframe=true&is_fullpost_editor=true',
			id: 'TB_iframeModalContent',
			frameborder: 0,
			allowfullscreen: true
		});

		$iframe.on('load', function () {
			const iframe = this;
			const win = iframe.contentWindow;
			const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;


			// waiting for Gutenberg
			const waitForGutenberg = setInterval(() => {
				if (win.wp && win.wp.data && win.wp.data.select) {
					clearInterval(waitForGutenberg);
					self.hookGutenbergModalSave(win);
				}
			}, 200);
			jQuery('#tb-loader').remove();
		});




		jQuery(this.g_postEditorPopupBody).empty();
		jQuery(this.g_postEditorPopupBody).append($iframe);
		this.g_postEditorView.openPostEditPopupOuter();
		jQuery(this.g_postEditorPopupBody).append(`
			<div id="tb-loader">
				<span class="spinner is-active"></span>
			</div>`);



	}

	inititateModalLoader() {

		// tb loader content tracer
		const tbWatcher = setInterval(function () {
			if (jQuery('#TB_window').length && jQuery(this.g_tbIframeModalContent).length) {
				if (!jQuery('#TB_window').length || jQuery('#tb-loader').length) return;

				jQuery(this.g_postEditorPopupBody).append(`
					<div id="tb-loader">
						<span class="spinner is-active"></span>
					</div>
				`);
				jQuery(this.g_tbIframeModalContent).css('visibility', 'hidden');

				jQuery(this.g_tbIframeModalContent).on('load', function () {
					jQuery('#tb-loader').remove();
					jQuery(this).css('visibility', 'visible');
				});
				clearInterval(tbWatcher);
			}
		}, 50);
	}
	inititateSidebarLoader() {

		// tb loader content tracer
		const tbWatcher = setInterval(function () {
			if (jQuery('#tb-loader').length == 0) {


				jQuery(this.editorDrawer.g_pluginSideDrawerBody).append(`
					<div id="tb-loader">
						<span class="spinner is-active"></span>
					</div>
				`);

				jQuery('#TB_iframeSidebarContent').on('load', function () {
					jQuery('#tb-loader').remove();
				});


				clearInterval(tbWatcher);
			}
		}, 200);
	}

	goToPostPreview(e) {
		const $clicked = jQuery(e.currentTarget);
		const parent_row = $clicked.parents('tr');
		const post_id = parent_row.attr('data-id');

		window.open(g_baseURL + '?post_preview=' + post_id+'&nonce='+jQuery('#g_doublyNonce').val(), '_blank', 'noopener,noreferrer');
	}

	onTagChange(e) {
		this.onCellContentSave(e.currentTarget);
	}
	onPostObjectChange(e) {
		this.onCellContentSave(e.currentTarget);
	}
	onAcfSelectChange(e) {
		this.onCellContentSave(e.currentTarget);
		this.generateProductTypeSettingsIcon(jQuery(e.target));
	}

	enableDisableColumnsPostType() {
		var self = this;
		jQuery('div[data-column="plugins_product_type"] ' + this.g_editorInput).each(function () {
			self.detectDisabledColumns(jQuery(this));
		})

	}

	detectDisabledColumns(pointer) {
		var current_value = pointer.val();
		var related_row = g_tableStructure.find(item => item.name === "plugins_product_type");
		if (related_row && related_row.hasOwnProperty('source')) {
			jQuery.each(related_row.source, function (index, value) {
				if (value.id == current_value) {
					var block_columns = value.block_columns
					var show_columns = value.show_columns
					// block input cells
					var parent_row = pointer.parents('tr');

					jQuery.each(block_columns, function (index, value) {
						jQuery('.editor_container[data-column="plugins_' + value + '"] ', parent_row).parents('td').addClass('is_disabled');
					})
					jQuery.each(show_columns, function (index, value) {
						jQuery('.editor_container[data-column="plugins_' + value + '"] ', parent_row).parents('td').removeClass('is_disabled');
					})


				}
			})
		}
	}

	generateProductTypeSettingsIcon(pointer) {

		// if acf_select check relations for related drawer data		
		var current_value = pointer.val();
		var editor_container = pointer.parents(this.g_editorContainer);

		this.detectDisabledColumns(pointer);



		var column_name = editor_container.attr('data-column');

		// custom processing for product type
		if (column_name == 'plugins_product_type') {
			const result_structure_info = g_tableStructure.find(item => item.name === column_name);

			if (result_structure_info.has_multirelations) {
				var current_relation_name = 'relation_' + current_value;

				if (result_structure_info[current_relation_name]) {

					var edit_span = jQuery('<span>', {
						class: `${this.editorDrawer.g_incellRelationEditorNoIndex} ${this.editorDrawer.g_incellRelationEditorNoIndex}_${current_value}`,
						html: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings w-3.5 h-3.5"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
					});
					jQuery(this.g_visualPart, editor_container).append(edit_span);
				}
			}
		}
	}

	onTagSelectChange(e) {
		this.onCellContentSave(e.currentTarget);
	}

	showTaxonomyInsertField(e) {
		var objClicked = jQuery(e.currentTarget);
		var objEditor = objClicked.find(this.g_categoryEditor).removeClass(this.edgeRightClass);

		jQuery(this.g_categoryEditor + ':visible').hide();

		objEditor.show();

		var dropdownWidth = 320;
		var offset = objEditor.offset();
		var windowWidth = jQuery(window).width();

		// Check if the left position + width exceeds the screen width
		if (offset.left + dropdownWidth > windowWidth) {
			objEditor.addClass(this.edgeRightClass);
		} else {
			objEditor.removeClass(this.edgeRightClass);
		}
	}

	saveCategoryInsertion(e) {
		var self = this;
		const $clicked = jQuery(e.currentTarget);
		var parent_container = $clicked.parents(this.g_editorContainer);
		var category_name = jQuery(this.g_newTaxValue, parent_container).val();
		var category_parent = jQuery(this.g_categorySelector, parent_container).val();
		var post_id = parent_container.parents('tr').data('id');
		var row_id = parent_container.parents('td').data('row');
		var col_id = parent_container.parents('td').data('col');
		var taxonomy = parent_container.data('column');

		if (category_name == '') { return; }

		jQuery(this.g_addCategoryLoaderSave, parent_container).show();
		var data =
		{
			post_id: post_id,
			category_parent: category_parent,
			category_name: category_name,
			taxonomy: taxonomy,
			row_id: row_id,
			col_id: col_id,
		};

		this.g_doublyAdmin.ajaxRequest('add_post_taxonomy', data, function (response) {

			//set updated cell content
			jQuery('.cell_' + row_id + '_' + col_id + ' ' + self.g_visualPart).html(response.message.content);
			var selectedValues = jQuery('.cell_' + row_id + '_' + col_id + ' ' + self.g_visualPart + ' .category_cell_td_list input[type="checkbox"]:checked ')
				.map(function () { return this.value; })
				.get();

			jQuery('.cell_' + row_id + '_' + col_id + ' ' + self.g_visualPart + ' .category_cell_td_list input[type="checkbox"]').each(function () {
				if (selectedValues.includes(this.value)) {
					jQuery(this).prop('checked', true);
				} else {
					jQuery(this).prop('checked', false);
				}

			});

			var associated_ids = [];
			jQuery('#new_output_table tr').each(function () {
				var this_id = jQuery(this).attr('data-id');

				if (this_id == row_id && response.message.col == col_id) { return true; }

				//get tax name
				var tax_name = jQuery('.cell_' + this_id + '_' + response.message.col + self.g_editorContainer).attr('data-column');

				var selectedValues = jQuery('.cell_' + this_id + '_' + response.message.col + ' ' + self.g_visualPart + ' .category_cell_td_list input[type="checkbox"]:checked ')
					.map(function () { return this.value; })
					.get();
				associated_ids[this_id] = selectedValues;

				jQuery('.cell_' + this_id + '_' + response.message.col + ' ' + self.g_visualPart).html(response.message.content);


				jQuery('.cell_' + this_id + '_' + response.message.col + ' ' + self.g_visualPart + ' .category_cell_td_list input[type="checkbox"]').each(function () {
					if (selectedValues.includes(this.value)) {
						jQuery(this).prop('checked', true);
					} else {
						jQuery(this).prop('checked', false);
					}
				});
			})


			jQuery(self.g_addCategoryLoaderSave, parent_container).hide();
			self.limitToTwoRows()


		});
	}

	onTaxChange(e) {
		this.onCellContentSave(e.currentTarget);
	}

	/**
	 * Cell editon
	 */
	onCellEdit(e) {
		var self = this;
		const $clicked = jQuery(e.currentTarget);


		if ($clicked.hasClass('is_disabled')) { return; }

		var parent_container = jQuery(this.g_editorContainer, $clicked);
		var parent_td = $clicked.parents('td');

		// patch for non td click
		if ($clicked.is('td')) {
			parent_td = $clicked;
		}

		const type = parent_container.data('type');
		const value = parent_container.data('value');
		const readonly = parent_container.data('readonly');


		// verify for pro		
		if (type == 'post_object') {
			if (g_drawer.verifyProCell(parent_td)) { return true; };
		}


		// check if cell is gallery
		if (type == 'acf_gallery' || type == 'acf_woo_gallery') {
			// verify for pro		
			if (type == 'acf_gallery') {
				if (g_drawer.verifyProCell(parent_td)) { return true; };
			}

			const inner_clicked = jQuery(e.currentTarget);
			const pseudo_add_button = inner_clicked.find(this.g_galleryAddImage);
			this.onGalleryAddImage(pseudo_add_button);
		}

		// check if select2tags
		if (type == 'tag' || type == 'acf_select' || type == 'post_object' || type == 'woo_post_object') {
			this.isCellSelect2Tag = true;
			this.isCellSelect2TagTarget = parent_container;
		}

		//for repeater open sidebar editor
		if (type == 'repeater') {
			g_drawer.editACFRepeater($clicked.find(this.g_editorContainer));
		}

		//for repeater open sidebar editor
		if (type == 'product_attribute') {
			g_drawer.openProductAttributesEditor(e);
		}
		 
		// wyswyg edit on doubleclick
		if (type == 'wysiwyg') {
			g_drawer.editWYSIWYGField($clicked.find(this.g_editorContainer));
		}

		// open2select
		/*
		if ( type == 'acf_select' ) {
			var objSelect = parent_container.find( this.g_acfSelectEditorInput );
			objSelect.select2('open');
		}
		*/


		if (readonly) {
			return true;
		}

		// edd is edited class
		if (type == 'text' || type == 'textarea' || type == 'calendar' || type == 'select') {
			$clicked.addClass(this.g_isCurrentlyEditionNoIndex);
		}

		jQuery('.manage_container', parent_container).fadeOut();


		if (type == 'tag') {
			jQuery(this.g_visualPart, parent_container).hide();
			jQuery(this.g_editorPart, parent_container).show();
			var objSelect = parent_container.find(this.g_tagSelectEditorInput);
			objSelect.select2('open');
		} else if (type == 'acf_select') {
			jQuery(this.g_visualPart, parent_container).hide();
			jQuery(this.g_editorPart, parent_container).show();
			var objSelect = parent_container.find(this.g_acfSelectEditorInput);

			objSelect.select2('open');

		} else if (type == 'post_object' || type == 'woo_post_object') {
			jQuery(this.g_visualPart, parent_container).hide();
			jQuery(this.g_editorPart, parent_container).show();
			var objSelect = parent_container.find(this.g_postObjectEditorInput);
			objSelect.select2('open');

			jQuery("ul.select2-selection__rendered").sortable({
				containment: 'parent',
				update: function () {

					let orderedValues = [];
					jQuery(this).find('.select2-selection__choice .stat_id_patch').each(function () {

						let data_id = jQuery(this).attr('data-id');
						orderedValues.push(data_id);
					});

					// send order via variable

					self.select2Reorder = orderedValues;
					objSelect.trigger('select2:select');
				}
			});


		} else if (type == 'color_picker') {
			jQuery(this.g_visualPart, parent_container).hide();
			jQuery(this.g_editorPart, parent_container).show();
			jQuery(this.g_editorPart + ' ' + this.g_editorInput, parent_container).focus();
			jQuery(this.g_editorPart + ' ' + this.g_editorInput, parent_container).click();
		} else {
			jQuery(this.g_visualPart, parent_container).hide();
			jQuery(this.g_editorPart + ' ' + this.g_textEditorInput, parent_container).val(value);
			jQuery(this.g_editorPart, parent_container).show();
			jQuery(this.g_editorPart + ' ' + this.g_editorInput, parent_container).focus();
		}

		// focust and put curosr to the end
		if (type == 'text' || type == 'textarea') {
			const len = jQuery(this.g_editorPart + ' ' + this.g_editorInput, parent_container).val().length;
			var selected_input_editor = jQuery(this.g_editorPart + ' ' + this.g_editorInput, parent_container);
			selected_input_editor[0].setSelectionRange(len, len);
		}

		if (type == 'calendar') {
			var objCalendarInput = parent_td.find("input");

		}

	}

	/**
	 * After paste or assignment: sync attachment id/url, clear placeholder state, refresh sidebar image action.
	 *
	 * @param {jQuery} $container .editor_container in the image cell.
	 * @param {string|number} attachmentId Media attachment ID.
	 * @param {string} [imageUrl] Thumbnail URL shown in the cell.
	 * @param {Object} [options] Optional preview metadata.
	 * @param {string} [options.fullUrl] Full-size URL for hover preview.
	 * @param {number} [options.fileSize] File size in bytes.
	 * @param {string} [options.fileType] File type label (jpg, png, …).
	 * @param {number} [options.width] Image width in pixels.
	 * @param {number} [options.height] Image height in pixels.
	 * @param {string} [options.filename] Original attachment filename.
	 */
	syncImageCellAttachment($container, attachmentId, imageUrl, options) {
		if (!$container || !$container.length) {
			return;
		}

		options = options || {};
		attachmentId = attachmentId === null || attachmentId === undefined ? '' : String(attachmentId).trim();
		imageUrl = imageUrl === null || imageUrl === undefined ? '' : String(imageUrl).trim();
		var fullUrl = options.fullUrl === null || options.fullUrl === undefined ? imageUrl : String(options.fullUrl).trim();

		if (attachmentId !== '' && attachmentId !== '0') {
			$container.attr('data-value', attachmentId);
			$container.data('value', attachmentId);
		} else {
			$container.attr('data-value', '');
			$container.data('value', '');
		}

		var $img = jQuery(this.g_ubaiFeaturedImageUploader, $container).first();
		if (!$img.length) {
			$img = $container.find(this.g_visualPart + ' img').first();
		}

		if ($img.length) {
			if (imageUrl !== '') {
				$img.attr('src', imageUrl);
				$img.attr('data-img', imageUrl);
			}
			if (fullUrl !== '') {
				$img.attr('data-full', fullUrl);
			}
			if (attachmentId !== '' && attachmentId !== '0') {
				$img.attr('data-id', attachmentId);
			} else {
				$img.removeAttr('data-id');
			}

			var fileSize = parseInt(options.fileSize, 10) || 0;
			if (fileSize > 0) {
				$img.attr('data-file-size', fileSize);
			} else {
				$img.removeAttr('data-file-size');
			}

			if (options.fileType) {
				$img.attr('data-file-type', options.fileType);
			} else {
				$img.removeAttr('data-file-type');
			}

			var width = parseInt(options.width, 10) || 0;
			var height = parseInt(options.height, 10) || 0;
			if (width > 0 && height > 0) {
				$img.attr('data-image-width', width);
				$img.attr('data-image-height', height);
			} else {
				$img.removeAttr('data-image-width');
				$img.removeAttr('data-image-height');
			}

			if (options.filename) {
				$img.attr('data-filename', String(options.filename));
			} else if (!attachmentId || attachmentId === '0') {
				$img.removeAttr('data-filename');
			}

			$img.removeClass(this.g_isPlaceholderNoIndex);
			$img.addClass('sp_hover_preview');
		}

		jQuery(document).trigger('sp:image-preview:invalidate', [$img]);
		this.triggerEvent(this.g_events.SELECTION_CHANGE);

		var $cell = $container.closest('td');
		if ($cell.length) {
			var postId = $cell.data('row');
			var colIdx = $cell.data('col');
			if (postId != null && colIdx != null && this.cellHasRealImage($cell)) {
				this.clearCellPromptActivityIndicators({
					isSelected: true,
					postId: postId,
					columnIndex: colIdx
				});
			}
		}
	}

	appendImageCacheBust(url, bust) {
		if (!url || !bust) {
			return url;
		}
		var clean = String(url).trim();
		if (!clean) {
			return clean;
		}
		return clean + (clean.indexOf('?') >= 0 ? '&' : '?') + 'spv=' + encodeURIComponent(String(bust));
	}

	onCompressImageFromContextMenu(e) {
		if (e && typeof e.preventDefault === 'function') {
			e.preventDefault();
		}
		if (e && typeof e.stopPropagation === 'function') {
			e.stopPropagation();
		}

		var $item = jQuery(e.currentTarget);
		if ($item.hasClass('unlimitedai-plugin__context-menu__item--disabled')) {
			return;
		}

		var tableData = this.getTableData();
		if (!tableData || !tableData.isSelected) {
			this.g_doublyAdmin.showErrorMessage(sheetspilot.editor.please_select_a_cell);
			return;
		}

		var $cell = this.getCellFromApplyPromptTable(tableData);
		if (!$cell || !$cell.length) {
			$cell = jQuery(this.g_isActiveCell).first();
		}
		if (!$cell.length) {
			this.g_doublyAdmin.showErrorMessage(sheetspilot.editor.please_select_a_cell);
			return;
		}

		var self = this;
		var payload = { table: tableData };
		this.incrementApplyPromptLoadingForCell($cell, 'compress-image');
		this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderNoIndex);

		this.g_doublyAdmin.ajaxRequest('compress_image', payload, function (response) {
			var resultData = response && response.data ? response.data : null;
			if (response && response.data && response.data.data) {
				resultData = response.data.data;
			}
			if (!resultData || !resultData.attachment_id) {
				self.g_doublyAdmin.showErrorMessage('Image compression did not return attachment data.');
				return;
			}

			var attachmentId = resultData.attachment_id;
			var thumbnailUrl = resultData.thumbnail_url || '';
			var cacheBust = parseInt(resultData.cache_bust, 10) || 0;
			if (thumbnailUrl && cacheBust > 0) {
				thumbnailUrl = self.appendImageCacheBust(thumbnailUrl, cacheBust);
			}

			var $container = $cell.find(self.g_editorContainer).first();
			self.syncImageCellAttachment($container, attachmentId, thumbnailUrl, {
				fullUrl: resultData.full_url || resultData.thumbnail_url || thumbnailUrl,
				fileSize: parseInt(resultData.file_size, 10) || parseInt(resultData.size_after, 10) || 0,
				fileType: resultData.file_type || '',
				width: parseInt(resultData.width, 10) || 0,
				height: parseInt(resultData.height, 10) || 0,
				filename: resultData.filename || ''
			});
		}, function () {
			self.decrementApplyPromptLoadingForCell($cell, 'compress-image');
		});
	}

	onCellContentSave(element, $is_past_action = false, $is_backspace = false, options) {
		var make_save = 1;
		options = options && typeof options === 'object' ? options : {};
		// set ajax loader (unless caller suppresses global "Saving...")
		if (options.suppressAjaxLoader === true) {
			this.g_doublyAdmin.setAjaxLoaderID(function () { });
		} else {
			this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderNoIndex);
		}

		var self = this;
		const $clicked = jQuery(element);
	
		var parent_td = $clicked.parents('td');

		var parent_container = parent_td.find(this.g_editorContainer);
		var parent_row = $clicked.parents('tr');

		var current_value;
		var oldValue;

		oldValue = jQuery(this.g_visualPart, parent_container).html();


		const type = parent_container.data('type');
		const column = parent_container.data('column');
		const post_id = parent_row.data('id');
		const column_id = parent_td.data('col');

		let manage = parent_container.data('manage');


		


		parent_td.removeClass(this.g_isCurrentlyEditionNoIndex);

		jQuery('.manage_container', parent_container).fadeIn();

		if (!manage) {
			manage = '';
		}
		manage = manage.replace(/\\'/g, "'");

		if (!manage) {
			manage = '';
		}

		if (type == 'switch') {
			if (jQuery(' .' + type + '_editor_input', parent_container).is(':checked')) {
				current_value = 'yes';
			} else {
				current_value = 'no';
			}

		}
		if (type == 'text') {
			current_value = jQuery(this.g_editorPart + ' .' + type + '_editor_input', parent_container).val();
			jQuery(this.g_visualPart, parent_container).html(current_value + manage);
			parent_container.data('value', current_value);
		}



		if (type == 'textarea') {

			current_value = jQuery(this.g_editorPart + ' .' + type + '_editor_input', parent_container).val();
			// filter tags and comments
			let current_value_visual = this.stripHTMLContent(current_value);
			jQuery(this.g_visualPart, parent_container).html(current_value_visual);
			parent_container.data('value', current_value);
		}

		if (type == 'color_picker') {

			current_value = jQuery(this.g_editorPart + ' .' + type + '_editor_input', parent_container).val();
			// filter tags and comments
			var $single_tab_bubble = jQuery('<div>', {
				class: self.g_singleTagBubbleNoPrefix,
				text: current_value,
				css: { background: current_value }
			});
			jQuery(this.g_visualPart + ' ' + this.g_singleTagBubble, parent_container).replaceWith('');
			jQuery(this.g_visualPart, parent_container).append($single_tab_bubble);
			parent_container.data('value', current_value);
		}
		if (type == 'select') {
			current_value = jQuery(this.g_editorPart + ' .' + type + '_editor_input', parent_container).val();
			const selectedText = jQuery(this.g_editorPart + ' .' + type + '_editor_input option:selected', parent_container).text();
			parent_container.data('value', current_value);
			jQuery(this.g_visualPart, parent_container).html(selectedText + manage);
		}
		if (type == 'calendar') {
			current_value = jQuery(this.g_editorPart + ' .' + type + '_editor_input', parent_container).val();
			jQuery(this.g_visualPart, parent_container).html(current_value);
			parent_container.data('value', current_value);
		}
		if (type == 'image') {
			current_value = parent_container.attr('data-value');
			if (current_value !== undefined && current_value !== null && String(current_value).trim() !== '') {
				parent_container.data('value', current_value);
				jQuery(this.g_ubaiFeaturedImageUploader, parent_container).removeClass(this.g_isPlaceholderNoIndex);
			}
		}
		if (type == 'taxonomy') {
			
			let selected = [];
			var definde_items_count = 0;
			jQuery(this.g_singleTagBubble, parent_container).replaceWith('');

			if( jQuery(this.g_categoryEditor + ' input[type="checkbox"]:checked', parent_container).length > 0 ){
				jQuery(this.g_categoryEditor + ' input[type="checkbox"]:checked', parent_container).each(function () {
					selected.push(jQuery(this).val());
					if (definde_items_count < 2) {

						

						var text = jQuery(this).closest('label').text().trim();
						var $single_tab_bubble = jQuery('<div>', {
							class: self.g_singleTagBubbleNoPrefix,
							text: text
						});
			
						jQuery(self.g_ubaiTaxBlock, parent_container).prepend($single_tab_bubble);
					}
					definde_items_count++
				});
			}
			

			selected = selected.filter((value, index, self) => {
				return self.indexOf(value) === index;
			});



			current_value = selected.join(',');


			// visual counter modifications
			let diff = 0;
			if (selected.length > 2) {
				diff = selected.length - 2;
				jQuery(this.g_taxCounterBlock, parent_container).fadeIn();
				jQuery(this.g_taxCounterBlock, parent_container).html('+' + diff);
			} else {
				jQuery(this.g_taxCounterBlock, parent_container).fadeOut();
			}


		}
		if (type == 'tag') {
			// patch if backspace
			var active_select =  jQuery( '.editor_input', parent_container );


			const selected = [];
			var selectedData = active_select.select2('data');
			var names = selectedData.map(item => item.text).join(', ');
			var names_array = selectedData.map(item => item.text);

			// add tags to visual
			jQuery(this.g_visualPart, parent_container).empty();

			var tags_items_counter = 0;
			names_array.forEach(function (s_text) {

				if (tags_items_counter >= 5) {
					return;
				}
				tags_items_counter++;

				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: s_text
				});
				jQuery(self.g_visualPart, parent_container).append($single_tab_bubble);
			})

			if (names_array.length > 5) {
				var tags_difference = names_array.length - 5;
				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: '+' + tags_difference
				});
				jQuery(self.g_visualPart, parent_container).append($single_tab_bubble);
			}

			current_value = names;
		}
		if (type == 'post_object' || type == 'woo_post_object') {
			const selected = [];
			var selectedData = $clicked.select2('data');


			if ($clicked.attr('multiple') == 'multiple') {

				if (self.select2Reorder.length > 0) {
					let orderMap = {};

					self.select2Reorder.forEach((id, index) => {
						orderMap[id] = index;
					});

					selectedData.sort((a, b) => {
						return (orderMap[a.id] ?? Infinity) - (orderMap[b.id] ?? Infinity);
					});
				}

				var ids = selectedData.map(item => item.id);
				var names = selectedData.map(item => item.text);
			} else {
				var ids = selectedData.map(item => item.id);
				var names = selectedData.map(item => item.text);
				ids = ids[0];
			}
			jQuery(self.g_visualPart, parent_container).empty();
			names.forEach(function (s_text) {
				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: s_text
				});
				jQuery(self.g_visualPart, parent_container).append($single_tab_bubble);
			})



			current_value = ids;
			parent_container.data('value', current_value);
			parent_container.attr(
				'data-value',
				Array.isArray(current_value) ? current_value.join(',') : (current_value == null ? '' : String(current_value))
			);
		}

		if (type == 'acf_select') {

			var active_select =  jQuery( '.editor_input', parent_container );

			const selected = [];
			var selectedData = active_select.select2('data');

			if (active_select.attr('multiple') == 'multiple') {
				var ids = selectedData.map(item => item.id);
				var names = selectedData.map(item => item.text);
			} else {
				var ids = selectedData.map(item => item.id);
				var names = selectedData.map(item => item.text);
				ids = ids[0];
			}
			jQuery(self.g_visualPart, parent_container).empty();
			names.forEach(function (s_text) {
				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: s_text
				});
				jQuery(self.g_visualPart, parent_container).append($single_tab_bubble);
			})

			current_value = ids;
		}

		if (type == 'acf_gallery' || type == 'acf_woo_gallery') {
			var data_value = parent_container.data('value');

			if (data_value == '') {
				var this_data = [];
			} else {
				var this_data = data_value.split(',');
			}

			current_value = this_data;
		}

		//patch slug spaces
		if (type == 'text' && column == 'post_name') {
			current_value = jQuery(this.g_editorPart + ' .' + type + '_editor_input', parent_container).val();
			current_value = self.sanitizePostName(current_value);

			jQuery(this.g_editorPart + ' .' + type + '_editor_input', parent_container).val(current_value);


			oldValue = self.sanitizePostName(oldValue);
			jQuery(this.g_visualPart, parent_container).html(oldValue);
		}


		// slug patch: Do not run if value is same
		if (column == 'post_name' && oldValue == current_value) {
			jQuery(this.g_visualPart, parent_container).show();
			jQuery(this.g_editorPart, parent_container).hide();
			jQuery(this.g_visualPart, parent_container).html(oldValue + manage);
			return true;
		}

		// patch for post_name visual

		if (type == 'text' && column == 'post_name') {
			current_value = jQuery(this.g_editorPart + ' .' + type + '_editor_input', parent_container).val();
			current_value = this.sanitizePostName(current_value);
			jQuery(this.g_visualPart, parent_container).html(current_value);

		}

 
		// if on click value and on blur are same - dont save
		if ( ( type == 'text' || type == 'textarea' || type == 'post_name' ) && (current_value == this.undoPrevCellValue ) ) {
			make_save = 0;
		}

		// is backspace
		if( $is_backspace ){
			make_save = 1;
		}


		if (make_save == 1) {
			var contentSave = self.preparePostContentSaveFields(parent_row, parent_container, column, current_value);
			var saveItem = {
				post_id: post_id,
				value: contentSave.value,
				column: column,
				type: type,
				cell_address: '.cell_' + post_id + '_' + column_id,
				is_elementor: contentSave.is_elementor,
			};
			if (contentSave.elementor_data) {
				saveItem.elementor_data = contentSave.elementor_data;
			}
			var data = [saveItem];

			if (sheetspilot.editor.g_isLogOn == 1) {
				console.log( data );
			}

			this.g_doublyAdmin.ajaxRequest('save_edited_posts', data, function (response) {

			});
		}


		// patch for select2tag data to do not hide editor
		if (type == 'tag') {
			return;
		}

		jQuery(this.g_visualPart, parent_container).show();
		jQuery(this.g_editorPart, parent_container).hide();


	}

	generateHeader() {
		var self = this;
		var $head_tr = jQuery('<tr></tr>');
		var aiIconBaseClass = 'unlimitedai-plugin__ai-column-settings-icon';
		var aiIconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path><path d="M4 17v2"></path><path d="M5 18H3"></path></svg>';
		var filterIconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>';
		var aiColumnSettingsLabel = (typeof g_ubaiPromptsStrings !== 'undefined' && g_ubaiPromptsStrings.cellRules) ? g_ubaiPromptsStrings.cellRules : 'AI Column Settings';
		var escapedAiColumnSettingsLabel = aiColumnSettingsLabel.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

		jQuery.each(this.$colData, function (cellIndex, rowData) {
			var orderable_class = '';
			var searchable_class = '';
			var modal_menu_class = '';
			var column_search_type = '';


			let dragBtnHTML = '';

			if (rowData.orderable !== false) {
				orderable_class = 'orderable';
				dragBtnHTML = `<span class="unlimitedai-plugin__drag-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-grip-vertical w-3.5 h-3.5 text-muted-foreground/50  group-hover:opacity-100 cursor-grab"><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg></span>`;
			}
			if (rowData.searchable) {
				searchable_class = 'searchable';
			}
			if (rowData.modal_off) {
				modal_menu_class = 'modal_off';
			}
			if (rowData.column_search) {
				column_search_type = rowData.column_search;
			}

			var okbutonHTML = `
				<div class="${self.g_columnFilterDropdownClass}__control dropdow_filter">
					<div class="${self.g_columnFilterDropdownClass}__filter-block" role="menuitem" tabindex="0"  >	
						<button type="button" value="Filter" class="${self.g_columnFilterDropdownClass}__button ${self.g_columnFilterDropdownClass}__make_filtering" >${sheetspilot.editor.ok}</button>					 
					</div>		
			</div>`

			if (column_search_type == 'calendar') {
				var filterDropdownHTML = `
				<div class="${self.g_columnFilterDropdownClass}" role="menu">
					<div class="${self.g_columnFilterDropdownClass}__sortig">
							<div class="${self.g_columnFilterDropdownClass}__sorting-item asc" role="menuitem" tabindex="0" aria-label="Sort old to new">
									<span class="${self.g_columnFilterDropdownClass}__sorting-item__icon">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 8 4-4 4 4"></path><path d="M7 4v16"></path><path d="M20 8h-5"></path><path d="M15 10V6.5a2.5 2.5 0 0 1 5 0V10"></path><path d="M15 14h5l-5 6h5"></path></svg>
									</span>
									<span class="${self.g_columnFilterDropdownClass}__sorting-item__text">${sheetspilot.editor.sort_old_to_new}</span>
							</div>
							<div class="${self.g_columnFilterDropdownClass}__sorting-item desc" role="menuitem" tabindex="0" aria-label="Sort new to old">
									<span class="${self.g_columnFilterDropdownClass}__sorting-item__icon">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 16 4 4 4-4"></path><path d="M7 4v16"></path><path d="M15 4h5l-5 6h5"></path><path d="M15 20v-3.5a2.5 2.5 0 0 1 5 0V20"></path><path d="M20 18h-5"></path></svg>
									</span>
									<span class="${self.g_columnFilterDropdownClass}__sorting-item__text">${sheetspilot.editor.sort_new_to_old}</span>
							</div>
					</div>
					<div class="${self.g_columnFilterDropdownClass}__divider" role="separator"></div>`;
			} else {
				var filterDropdownHTML = `
			<div class="${self.g_columnFilterDropdownClass}" role="menu">
					<div class="${self.g_columnFilterDropdownClass}__sortig">
							<div class="${self.g_columnFilterDropdownClass}__sorting-item asc" role="menuitem" tabindex="0" aria-label="Sort column A to Z ascending">
									<span class="${self.g_columnFilterDropdownClass}__sorting-item__icon">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 8 4-4 4 4"></path><path d="M7 4v16"></path><path d="M20 8h-5"></path><path d="M15 10V6.5a2.5 2.5 0 0 1 5 0V10"></path><path d="M15 14h5l-5 6h5"></path></svg>
									</span>
									<span class="${self.g_columnFilterDropdownClass}__sorting-item__text">${sheetspilot.editor.sort_a_to_z}</span>
							</div>
							<div class="${self.g_columnFilterDropdownClass}__sorting-item desc" role="menuitem" tabindex="0" aria-label="Sort column Z to A descending">
									<span class="${self.g_columnFilterDropdownClass}__sorting-item__icon">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 16 4 4 4-4"></path><path d="M7 4v16"></path><path d="M15 4h5l-5 6h5"></path><path d="M15 20v-3.5a2.5 2.5 0 0 1 5 0V20"></path><path d="M20 18h-5"></path></svg>
									</span>
									<span class="${self.g_columnFilterDropdownClass}__sorting-item__text">${sheetspilot.editor.sort_z_to_a}</span>
							</div>
					</div>
					<div class="${self.g_columnFilterDropdownClass}__divider" role="separator"></div>`;
			}

			if (column_search_type == 'calendar') {
				filterDropdownHTML += `
						<div class="${self.g_columnFilterDropdownClass}_filter_values ${self.g_columnFilterDropdownClass}_filter_values_calendar">
								<div class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item-title ">
									<div class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item-title__text">
										${sheetspilot.editor.filter_by_date}
									</div>
								</div>
								<div class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item ">
									<label class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item__label">
										<input name="date_filter" value="all_dates" type="radio"  >
										${sheetspilot.editor.all_dates}
									</label>
								</div>
								<div class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item ">
									<label class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item__label">
										<input name="date_filter" value="today" type="radio"  >
										${sheetspilot.editor.today}
									</label>
								</div>
								<div class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item ">
									<label class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item__label">
										<input name="date_filter" value="last_7_days" type="radio"  >
										${sheetspilot.editor.last_7_days}
									</label>
								</div>
								<div class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item ">
									<label class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item__label">
										<input name="date_filter" value="last_30_days" type="radio"  >
										${sheetspilot.editor.last_30_days}
									</label>
								</div>
								<div class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item ">
									<label class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item__label">
										<input name="date_filter" value="last_3_months" type="radio"  >
										${sheetspilot.editor.last_3_months}
									</label>
								</div>
								<div class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item ">
									<label class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item__label">
										<input name="date_filter" value="last_6_months" type="radio"  >
										${sheetspilot.editor.last_6_months}
									</label>
								</div>								
								<div class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item ">
									<label class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item__label">
										<input name="date_filter" value="last_12_months" type="radio"  >
										${sheetspilot.editor.last_12_months}
									</label>
								</div>
								<div class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item ">
									<label class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item__label">
										<input name="date_filter" value="this_year" type="radio"  >
										${sheetspilot.editor.this_year}
									</label>
								</div>
								<div class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item ">
									<label class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item__label">
										<input name="date_filter" value="custom_range" type="radio"  >
										${sheetspilot.editor.custom_range}
									</label>
								</div>
								<div class="unlimitedai-plugin__th-filter-dropdown__term_filterable_item is_custom_range">
									<div class="row row-cols-2 gx-1">
										<div class="is_custom_range_item col">
											<label class="is_custom_range_item_label">${sheetspilot.editor.start}</label>
											<input class="form-control" id="range_date_filter_start" name="range_date_filter_start" value="" placeholder="" type="date" />																					
										</div>
										<div class="is_custom_range_item col">
											<label class="is_custom_range_item_label">${sheetspilot.editor.end}</label>
											<input class="form-control" id="range_date_filter_end" name="range_date_filter_end" value="" placeholder="" type="date" />																					
										</div>
									</div>
									
								</div>
								
						</div>
						<div class="${self.g_columnFilterDropdownClass}__divider" role="separator"></div>
						<div class="${self.g_columnFilterDropdownClass}__control-container">
							<div class="${self.g_columnFilterDropdownClass}__control dropdow_clear">
								<div class="${self.g_columnFilterDropdownClass}__search-block" role="menuitem" tabindex="0"  >
									<button type="button" value="${sheetspilot.editor.clear}" class="${self.g_columnFilterDropdownClass}__button ${self.g_columnFilterDropdownClass}__clear_filtering" >${sheetspilot.editor.clear}</button>
								</div>
							</div>
						 
						</div>
						`;
			}

			if (column_search_type == 'text') {
				filterDropdownHTML += `
						<div class="${self.g_columnFilterDropdownClass}__search">
								<label class="${self.g_columnFilterDropdownClass}__search-label" >Filter by values</label>
								<div class="${self.g_columnFilterDropdownClass}__search-block" role="menuitem" tabindex="0"  >
			
									<input class="uai-column-filter-search-input ${self.g_columnFilterDropdownClass}__search-input" type="text" placeholder="${sheetspilot.editor.search}" />
									<span class="unlimitedai-plugin__column_menu__search-icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
									</span>
									<span class="unlimitedai-plugin__column_menu__reset-icon">
										<svg class="size-14" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
									</span>
								</div>
				
						</div>
						<div class="${self.g_columnFilterDropdownClass}__divider" role="separator"></div>
						<div class="${self.g_columnFilterDropdownClass}__control-container">
							<div class="${self.g_columnFilterDropdownClass}__control dropdow_clear">
								<div class="${self.g_columnFilterDropdownClass}__search-block" role="menuitem" tabindex="0"  >
									<button type="button" value="${sheetspilot.editor.clear}" class="${self.g_columnFilterDropdownClass}__button ${self.g_columnFilterDropdownClass}__clear_filtering" >${sheetspilot.editor.clear}</button>
								</div>
							</div>
						 
						</div>
						`;
			}
			if (column_search_type == 'filter') {
				filterDropdownHTML += `
						<div class="${self.g_columnFilterDropdownClass}__search">
								
							<label class="${self.g_columnFilterDropdownClass}__search-label" >${sheetspilot.editor.filter_by_values}</label>
							<div class="${self.g_columnFilterDropdownClass}__search-block" role="menuitem" tabindex="0"  >
								<input class="uai-column-filter-term-inner-search-input ${self.g_columnFilterDropdownClass}__search-input" type="text" placeholder="${sheetspilot.editor.search}" />
								<span class="unlimitedai-plugin__column_menu__search-icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
								</span>
							</div>
							<div class="unlimitedai-plugin__th-filter-dropdown__term_select_all_item is_select_all_row">
								<label class="unlimitedai-plugin__th-filter-dropdown__term_select_all_item-label">
									<input value="select_all" class="select_all_checkbox" type="checkbox" checked="checked">
									${sheetspilot.editor.select_all}
								</label>
							</div>

							<div class="${self.g_columnFilterDropdownClass}__term_container" role="menuitem" tabindex="0"  ></div>
				
						</div>
						<div class="${self.g_columnFilterDropdownClass}__divider" role="separator"></div>
						<div class="${self.g_columnFilterDropdownClass}__control-container">
							<div class="${self.g_columnFilterDropdownClass}__control dropdow_clear">
								<div class="${self.g_columnFilterDropdownClass}__search-block" role="menuitem" tabindex="0"  >
									<button type="button" value="Clear" class="${self.g_columnFilterDropdownClass}__button ${self.g_columnFilterDropdownClass}__clear_filtering" >${sheetspilot.editor.clear}</button>
								</div>
							</div>
						 
						</div>`;
			}
			filterDropdownHTML += `</div>`;



			var cnHdr = rowData.name;
			var hasRules = ubaiColumnHasSavedCellRules(cnHdr);
			var aiIconClass = aiIconBaseClass + (hasRules ? ' ' + aiIconBaseClass + '--has-rules' : '');
			var aiIconAttrs = '';

			var promptOnPaste = ( g_ubaiCellRules[cnHdr+'__prompt_on_paste']  != null ) ? g_ubaiCellRules[cnHdr+'__prompt_on_paste'] : false;
			
			var autoApplyResponse = ( g_ubaiCellRules[cnHdr+'__auto_apply_response']  != null ) ? g_ubaiCellRules[cnHdr+'__auto_apply_response'] : false;

			if (hasRules) {
 
				var ruleTextHdr = (g_ubaiCellRules[cnHdr] != null) ? String(g_ubaiCellRules[cnHdr]).trim() : '';
				var arHdr = g_ubaiCellRules[cnHdr + '__aspect_ratio'];
				
				var qHdr = g_ubaiCellRules[cnHdr + '__quality'];
				var fHdr = g_ubaiCellRules[cnHdr + '__format'];
				var resHdr = g_ubaiCellRules[cnHdr + '__resolution'];
				if (arHdr || qHdr) {
					ruleTextHdr = (ruleTextHdr ? ruleTextHdr + '\n\n' : '') + 'aspect_ratio: ' + String(arHdr || '') + '\nquality: ' + String(qHdr || '');
				}
				if (fHdr) {
					ruleTextHdr = (ruleTextHdr ? ruleTextHdr + '\n\n' : '') + 'format: ' + String(fHdr || '');
				}
				if (resHdr) {
					ruleTextHdr = (ruleTextHdr ? ruleTextHdr + '\n\n' : '') + 'resolution: ' + String(resHdr || '');
				}
				var escapedFull = ruleTextHdr.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
				aiIconAttrs = ' data-ubai-column-rule-full="' + escapedFull + '" data-title=""   data-apply-prompt-on-paste="'+promptOnPaste+'" data-auto-apply-response="'+autoApplyResponse+'" ';
			} else {
				aiIconAttrs = ' data-title="' + escapedAiColumnSettingsLabel + '"   data-apply-prompt-on-paste="'+promptOnPaste+'" data-auto-apply-response="'+autoApplyResponse+'" ';
			}
			var aiColumnSettingsIconHTML = '<span class="' + aiIconClass + ' has-tooltip has-tooltip--bottom"' + aiIconAttrs + ' role="button" tabindex="0" aria-label="' + escapedAiColumnSettingsLabel + '">' + aiIconSvg + '</span>';
			var filterColumnSettingsIconHTML = '<button class="' + self.g_objFilterColumnBtnClass + ' has-tooltip has-tooltip--bottom" data-title="Filter & sort" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Filter And Sorting Settings">' + filterIconSvg + '</button>';

			var column_default_name = (rowData.name != 'bulk') ? rowData.title : '';
			var thTypeAttr = rowData.type != null ? String(rowData.type).replace(/"/g, '&quot;') : '';
			jQuery('<th tabindex="0" data-default="' + column_default_name + '" data-column-search-type="' + column_search_type + '" data-name="' + rowData.name + '" data-type="' + thTypeAttr + '" style="width:' + rowData.width + 'px;" class="unlimitedai-plugin__th ' + modal_menu_class + ' ' + orderable_class + ' ' + searchable_class + '"></th>').html('<div class="unlimitedai-plugin__th-inner">' + '<span class="unlimitedai-plugin__th-title"  >' + rowData.title + '</span>' + ((rowData.is_pro && !g_isPro) ? '<span class="badge bg-dark">' + sheetspilot.editor.pro + '</span>' : '') + '<span class="unlimitedai-plugin__th-icons">' + aiColumnSettingsIconHTML + filterColumnSettingsIconHTML + filterDropdownHTML + dragBtnHTML + '</span>' + '</div>' + '<div class="unlimitedai-plugin__th-resizer"></div>').appendTo($head_tr);
		})
		jQuery('thead', this.$table).empty().append($head_tr);
	}

	fillCellsWithContent(cellContent, append = true, drop_content = true) {

		const self = this;
		const savedOrder = jQuery.parseJSON(jQuery('#ubai_selected_columns_order').val());
 
		const visibleColumns = jQuery.parseJSON(jQuery('#ubai_selected_columns').val());
		var $tableWrapper = jQuery('#new_output_table');

		// if single insert - do not drop content
		if (drop_content) {
			this.$tableBody.empty();
		}

	 
		if (cellContent.length == 0) {
			var $tr = jQuery('<tr data-id="" ></tr>');
			jQuery('<td tabindex="0" class="no_posts_placeholder" colspan="' + self.$colData.length + '" >' + sheetspilot.editor.no_posts_found + '</td>').appendTo($tr);
			self.$tableBody.append($tr);
			jQuery('thead', $tableWrapper).hide();
			return true;
		} else {
			jQuery('thead', $tableWrapper).show();
		}





		jQuery.each(cellContent, function (rowIndex, row) {
	
			var rowMeta = { is_elementor: false };
			jQuery.each(row, function (_, cellValue) {
				if (cellValue && cellValue._sheetspilot_row_meta) {
					rowMeta = cellValue._sheetspilot_row_meta;
					return false;
				}
			});
			var isElementorRow = !!(rowMeta && (rowMeta.is_elementor === true || rowMeta.is_elementor === 1 || rowMeta.is_elementor === '1'));

			var $tr = jQuery('<tr data-id="' + row[1].id + '" data-is-elementor="' + (isElementorRow ? '1' : '0') + '"></tr>');

			// if single row make ordering
			if (!append) {

				var ordered = [];

				jQuery.each(savedOrder, function (_, key) {
					jQuery.each(row, function (_, item) {
						if (item.hasOwnProperty(key)) {
							ordered.push(item);
							return false; // break inner loop
						}
					});
				});
				row = ordered;

			}
 
			jQuery.each(row, function (cellIndex, cellValue) {

				var key = Object.keys(cellValue)[0];
				if (key === '_sheetspilot_row_meta') {
					return true;
				}
				var value = cellValue[key];

				var init_columns_row = self.$colData.find(item => item.name === key);

				var cell_content = self.getCellTypeContent(init_columns_row, value, key, init_columns_row.source);

				var $bottom_manage;
				var bottom_manage_content = '';
				if (init_columns_row.bottom_manage) {
					bottom_manage_content = init_columns_row.bottom_manage;
					if (init_columns_row.name === 'post_content' && isElementorRow && sheetspilot.editor.g_postContentEditIconElementorHtml) {
						bottom_manage_content = sheetspilot.editor.g_postContentEditIconElementorHtml;
					} else if (init_columns_row.name === 'post_content' && sheetspilot.editor.g_postContentEditIconHtml) {
						bottom_manage_content = sheetspilot.editor.g_postContentEditIconHtml;
					}
					$bottom_manage = jQuery('<div>', {
						class: 'bottom_manage_container post_manage',
						html: bottom_manage_content
					});
				}

				var extra_image_class = '';
				if (init_columns_row.type == 'image') {
					extra_image_class = self.g_featuredImageUploaderCellNoIndex;
				}

				var taxonomy_class = '';
				if (init_columns_row.type == 'taxonomy') {
					taxonomy_class = 'category_cell_td';
				}

				var post_title_class = '';
				if (init_columns_row.name == 'post_title') {
					post_title_class = 'post_title_cell_td';
				}

				var post_content_class = '';
				if (init_columns_row.name == 'post_content') {
					post_content_class = 'post_content_cell_td';
				}

				var post_excerpt_class = '';
				if (init_columns_row.name == 'post_excerpt') {
					post_excerpt_class = 'post_excerpt_cell_td';
				}

				var post_tag_class = '';
				if (init_columns_row.name == 'post_tag') {
					post_tag_class = 'post_tag_cell_td';
				}

				var post_acf_select_field_class = '';
				if (init_columns_row.name == 'acf_select_field') {
					post_acf_select_field_class = 'post_acf_seelct_cell_td';
				}

				var post_acf_checkbox_field_class = '';
				if (init_columns_row.acf_type == 'checkbox') {
					post_acf_checkbox_field_class = 'post_acf_checkbox_cell_td';
				}

				var post_taxonomy_field_class = '';
				if (init_columns_row.dev_type == 'taxonomy') {
					post_taxonomy_field_class = 'post_taxonomy_cell_td';
				}

				var post_acf_url_field_class = ''
				if (init_columns_row.acf_type == "url")
					post_acf_url_field_class = 'post_acf_url_cell_td'

				var product_up_cross_sell_class = '';

				if (init_columns_row.name == 'plugins__upsell_ids' || init_columns_row.name == 'plugins__crosssell_ids') {
					product_up_cross_sell_class = 'product_up_cross_sell_td';
				}

				var post_calendar_class = '';
				if (init_columns_row.type == 'calendar') {
					post_calendar_class = 'post_calendar_cell_td';
				}

				var post_acf_post_object_class = '';
				if (init_columns_row.acf_type == 'post_object') {
					post_acf_post_object_class = 'post_acf_post_object_cell_td';
				}

				var post_acf_wysiwyg_class = '';
				if (init_columns_row.acf_type == "wysiwyg") {
					post_acf_wysiwyg_class = 'post_acf_wysiwyg_cell_td';
				}

				var post_acf_text_class = '';
				if (init_columns_row.acf_type == "text") {
					post_acf_text_class = 'post_acf_text_cell_td';
				}

				var post_name_class = '';
				if (init_columns_row.name == "post_name") {
					post_name_class = 'post_name_cell_td';
				}

				// if single row insertion
				let make_cell_hidden = '';
				if (!append && jQuery.inArray(key, visibleColumns) === -1) {
					make_cell_hidden = ' style="display:none;" ';
				}

				jQuery('<td tabindex="0"   data-row="' + row[1].id + '" data-name="'+key+'" data-col="' + cellIndex + '" class="' + (init_columns_row.is_pro && !g_isPro ? ' is_for_pro ' : '') + '  cell_' + row[1].id + '_' + cellIndex + '  ' + self.g_dblclickEditableCellNoIndex + ' ' + extra_image_class + ' ' + post_title_class + ' ' + post_content_class + ' ' + post_excerpt_class + ' ' + post_tag_class + ' ' + post_acf_select_field_class + ' ' + taxonomy_class + ' ' + post_acf_checkbox_field_class + ' ' + post_taxonomy_field_class + ' ' + product_up_cross_sell_class + ' ' + post_calendar_class + ' ' + post_acf_url_field_class + ' ' + post_acf_post_object_class + post_acf_wysiwyg_class + post_name_class + post_acf_text_class + '" ' + make_cell_hidden + ' ></td>')
					.html(cell_content).append($bottom_manage)
					.appendTo($tr);
			});

			if (append) {
				self.$tableBody.append($tr);
			} else {
				self.$tableBody.prepend($tr);
			}

		});
		this.limitToTwoRows();
		this.restoreDiscardedPendingPromptResultIcons();
	}
	renderInputEditor() {

	}

	getCellTypeContent(rowData, value, variable_name,) {

		var self = this;
		var cellType = rowData.type;
		var acfCellType = rowData.acf_type;
		var is_acf = rowData.is_acf;
		var options = [];
		if (rowData.source) {
			options = rowData.source;
		}
		var readonly = false;
		if (rowData.readonly) {
			readonly = true;
		}
		// Objects (post_object / acf_select payloads) must not go into data-value —
		// jQuery stringifies them as "[object Object]". Complex types set IDs below.
		var initialDataValue = (value !== null && typeof value === 'object' && !Array.isArray(value))
			? ''
			: value;
		var $div_container = jQuery('<div>', {
			class: this.g_editorContainerNoPrefix,
			'data-readonly': readonly,
			'data-type': cellType,
			'data-column': variable_name,
			'data-value': initialDataValue,
			'data-manage': rowData.manage,

		});

		if (cellType == 'switch') {

			let input_type = 'switch';



			var $toggle_container = jQuery('<div>', {
				class: 'unlimitedai-plugin__switch-selector__container',
			});
			var $outer = jQuery('<label>', {
				class: 'unlimitedai-plugin__switch-selector__toggle',
			});
			var $input = jQuery('<input>', {
				type: 'checkbox',
				class: 'unlimitedai-plugin__switch-selector__toggle-input switch_editor_input',
				checked: (value == 'yes') ? true : false,
				value: 'yes',
			});

			if (rowData.related_editor_fields) {
				var $editor_btn = jQuery('<span>', {
					class: this.g_openWooCellDetailsNoIndex + ' sheetpilot_open_' + variable_name,
					html: '<button class="unlimitedai-plugin__managestock-btn unlimitedai-plugin__btn"><span class="unlimitedai-plugin__btn-icon"><svg class="size-14" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7h-9"></path><path d="M14 17H5"></path><circle cx="17" cy="17" r="3"></circle><circle cx="7" cy="7" r="3"></circle></svg></span></button>',
				});
				if (value != 'yes') {
					$editor_btn.hide();
				}
			}


			var $slider = jQuery('<span>', {
				class: 'unlimitedai-plugin__switch-selector__toggle-slider',
			});


			$outer.append($input, $slider);
			if (rowData.related_editor_fields) {
				$toggle_container.append($outer, $editor_btn);
			} else {
				$toggle_container.append($outer);
			}

			$div_container.append($toggle_container);

			$div_container.addClass("unlimitedai-plugin__switch-editor__container");

			return $div_container;
		}
		if (cellType == 'text') {
			let input_type = 'text';
			if (is_acf) {
				input_type = acfCellType;
			}
			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
				text: value
			});
			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,
				css: { display: 'none' }
			});

			var htmlElem = '<input>';
			if (acfCellType == 'url')
				htmlElem = '<textarea>';

			var $input = jQuery(htmlElem, {
				type: input_type,
				class: 'form-control ' + this.g_editorInputNoPrefix + ' text_editor_input',
				val: value
			});

			$editor.append($input);
			$div_container.append($visual, $editor);

			return $div_container;
		}

		if (cellType == 'textarea') {

			// strip html for 
			let value_visual = this.stripHTMLContent(value);
			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
				html: value_visual
			});
			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,
				css: { display: 'none' }
			});

			var rowsNum;

			if (!rowsNum || rowsNum < 5)
				rowsNum = 5;
			else
				rowsNum = rowData.rows;

			var $input = jQuery('<textarea>', {
				type: 'text',
				rows: rowsNum,
				class: 'form-control ' + this.g_editorInputNoPrefix + ' textarea_editor_input',
				text: value
			});

			$editor.append($input);
			$div_container.append($visual, $editor, $manage);

			return $div_container;
		}
		if (cellType == 'calendar') {


			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
				text: value
			});
			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,
				css: { display: 'none' }
			});
			var $input = jQuery('<input>', {
				type: 'datetime-local',
				class: 'form-control ' + this.g_editorInputNoPrefix + ' calendar_editor_input ',
				val: value
			});

			$editor.append($input);
			$div_container.append($visual, $editor);

			return $div_container;
		}
		if (cellType == 'select_old') {

			var cell_option_text = '';
			var $select = jQuery('<select>', {
				class: this.g_editorInputNoPrefix + ' select_editor_input'
			});

			// add options
			options.forEach(function (opt) {

				if (opt.id == value) {
					cell_option_text = opt.name;
				}
				var $option = jQuery('<option>', {
					value: opt.id,
					text: opt.name,
					selected: opt.id == value
				});
				$select.append($option);
			});


			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
				text: cell_option_text
			});
			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,
				css: { display: 'none' }
			});

			$editor.append($select);
			$div_container.append($visual, $editor);

			return $div_container;
		}
		if (cellType == 'image') {


			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
				html: value
			});


			$div_container.append($visual);

			return $div_container;
		}
		if (cellType == 'taxonomy') {

			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
				html: value
			});
			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,
				css: { display: 'none' }
			});

			$div_container.append($visual, $editor, $manage);

			return $div_container;
		}
		if (cellType == 'tag_OLD') {

			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
				html: value
			});
			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,

			});

			$div_container.append($visual, $editor, $manage);

			return $div_container;
		}

		// acf processing
		if (cellType == 'post_object' || cellType == 'woo_post_object') {




			var selected_posts = [];
			var selected_posts_ids = [];

			var cell_option_text = [];
			if (value.multiple == 1) {
				var multiple = true;
			} else {
				var multiple = false;
			}

			var $select = jQuery('<select>', {
				class: 'form-control ' + this.g_editorInputNoPrefix + ' post_object_editor_input',
				multiple: multiple,
				'data-search_post_type': rowData.search_post_type
			});

			if (value.posts) {
				var selected_posts = value.posts;
			} else {
				var selected_posts = [];
			}

			selected_posts.forEach(function (opt) {
				selected_posts_ids.push(opt.id);
			});

			var postObjectDataValue = multiple
				? selected_posts_ids.join(',')
				: (selected_posts_ids.length ? selected_posts_ids[0] : '');
			$div_container.attr('data-value', postObjectDataValue);
			$div_container.data('value', multiple ? selected_posts_ids.slice() : postObjectDataValue);


			// add options
			var current_options = value.postswname;
			if (!current_options) {
				current_options = [];
			}

			current_options.forEach(function (opt) {

				if (opt.id === value) {
					cell_option_text.push(opt.name);
				}
				var $option = jQuery('<option>', {
					value: opt.id,
					text: opt.name,
					selected: selected_posts_ids.includes(opt.id)
				});
				$select.append($option);

				if (selected_posts_ids.includes(opt.id)) {
					cell_option_text.push(opt.name);
				}
			});


			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
				text: value.post_title
			});

			cell_option_text.forEach(function (s_text) {
				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: s_text
				});
				$visual.append($single_tab_bubble);
			})

			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,
				css: { display: 'none' }
			});

			$editor.append($select);
			$div_container.append($visual, $editor);

			return $div_container;


		}
		if (cellType == 'acf_select') {

			var selected_posts = [];
			var selected_posts_ids = [];

			var cell_option_text = [];
			if (value.multiple == 1) {
				var multiple = true;
				$div_container.attr('data-multiselect', true);
			} else {
				var multiple = false;
			}


			var $select = jQuery('<select>', {
				class: 'form-control ' + this.g_editorInputNoPrefix + ' acf_select_editor_input',
				multiple: multiple
			});

			if (value.values) {
				var selected_posts = value.values;
			} else {
				var selected_posts = [];
			}

			selected_posts.forEach(function (opt) {
				selected_posts_ids.push(opt);
			});
 
			// add options
			options.forEach(function (opt) {

				if (opt.id === value) {
					cell_option_text.push(opt.name);
				}
				var $option = jQuery('<option>', {
					value: opt.id,
					text: opt.name,
					selected: selected_posts_ids.includes(opt.id)
				});
				$select.append($option);

				if (selected_posts_ids.includes(opt.id)) {
					cell_option_text.push(opt.name);
				}
			});


			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
			});



			cell_option_text.forEach(function (s_text) {
				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: s_text
				});
				$visual.append($single_tab_bubble);
			})


			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,
				css: { display: 'none' }
			});


			$editor.append($select);

			$div_container.append($visual, $editor);

			// on load show Settings button
			this.generateProductTypeSettingsIcon($select);

			return $div_container;


		}
		if (cellType == 'tag') {
			var self = this;
			var selected_posts = [];
			var selected_posts_ids = [];
			var selected_posts_names = [];

			var cell_option_text = '';
			if (value.multiple == 1) {
				var multiple = true;
			} else {
				var multiple = false;
			}


			var $select = jQuery('<select>', {
				class: 'form-control ' + this.g_editorInputNoPrefix + ' ' + this.g_tagSelectEditorInputNoIndex,
				multiple: multiple
			});

			if (value.values) {
				var selected_posts = value.values;
			} else {
				var selected_posts = [];
			}

			selected_posts.forEach(function (opt) {
				selected_posts_ids.push(opt);
			});



			// add options
			options.forEach(function (opt) {

				if (opt.id === value) {
					cell_option_text = opt.name;
				}
				// get selected options
				if (selected_posts_ids.includes(opt.id)) {
					selected_posts_names.push(opt.name);
				}

				var $option = jQuery('<option>', {
					value: opt.id,
					text: opt.name,
					'data-slug': opt.slug,
					selected: selected_posts_ids.includes(opt.id)
				});
				$select.append($option);
			});

			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
			});
			var tags_items_counter = 0;
			selected_posts_names.forEach(function (s_text) {

				if (tags_items_counter >= 5) {
					return;
				}
				tags_items_counter++;

				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: s_text
				});
				$visual.append($single_tab_bubble);
			})

			if (selected_posts_names.length > 5) {
				var tags_difference = selected_posts_names.length - 5;
				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: '+' + tags_difference
				});
				$visual.append($single_tab_bubble);
			}

			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,
				css: { display: 'none' }
			});

			$editor.append($select);
			$div_container.append($visual, $editor);

			return $div_container;


		}

		if (cellType == 'acf_gallery' || cellType == 'acf_woo_gallery') {
			var self = this;
			var current_images = value.values;
			var visual_html = '';
			var show_item_counter = 0;
			var images_count = current_images.length - 3;
			var used_images_ids = [];

			// getting current IDs and update parewnt_container
			jQuery.each(current_images, function (index, val) {
				used_images_ids.push(val.id);
			})
			$div_container.data('value', used_images_ids.join(','));

			if (current_images.length > 1 || current_images.length == 0) {
				var images_text = sheetspilot.editor.images;
			} else {
				var images_text = sheetspilot.editor.images;
			}

			var $images_container = jQuery('<div>', {
				class: this.g_galleryImagesContainerNoIndex,
			});
			var $manage = jQuery('<div>', {
				class: 'manage_container gallery_image_manage',
				html: rowData.manage
			});


			jQuery.each(current_images, function (index, val) {
				if (show_item_counter >= 3) {
					return;
				}
				show_item_counter++;
				var $single_image_container = jQuery('<div>', {
					class: self.g_singleImageContainerNoIndex+' sp_hover_preview',
					'data-id': val.id,
					'data-full': val.url,
					css: {
						backgroundImage: 'url(' + val.url + ')',
					}
				});
				$images_container.append($single_image_container);
			})
			if (images_count > 0) {
				var $single_image_container = jQuery('<div>', {
					class: self.g_singleImageContainerNoIndex,
					'data-id': 0,
					css: {
						backgroundImage: 'url()',
					},
					text: '+' + images_count
				});
				$images_container.append($single_image_container);
			}

			// total images counter
			var $single_image_counter = jQuery('<div>', {
				class: self.g_singleImageCounterNoIndex,
				text: current_images.length + ' ' + images_text
			});
			$images_container.find(self.g_singleImageCounter).replaceWith('');
			$images_container.append($single_image_counter);


			let input_type = 'gallery';
			if (is_acf) {
				input_type = acfCellType;
			}

			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,
				css: { display: 'none' }
			});
			var $input = jQuery('<input>', {
				type: input_type,
				class: 'form-control ' + this.g_editorInputNoPrefix + ' gallery_editor_input',
				val: value
			});


			$editor.append($input);
			$div_container.append($images_container, $editor, $manage);


			return $div_container;
		}
		if (cellType == 'repeater') {

			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix + ' rerepater_visual_center ',
			});
			var inner_part = jQuery('<span>', {
				class: `d-flex`,
				html: '<svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h.01"></path><path d="M3 18h.01"></path><path d="M3 6h.01"></path><path d="M8 12h13"></path><path d="M8 18h13"></path><path d="M8 6h13"></path></svg>'
			});
			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,
				css: { display: 'none' }
			});
			var $manage = jQuery('<div>', {
				class: 'manage_container repeater_manage',
				html: rowData.manage
			});
			var counter_text = jQuery('<span>', {
				class: `countable_cell_inner_counter`,
				text: value.values + ' ' + sheetspilot.editor.items
			});

			$visual.append(inner_part, counter_text);

			$div_container.append($visual, $editor, $manage);
			return $div_container;
		}
		if (cellType == 'product_attribute') {

			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix + ' rerepater_visual_center ',

			});
			var inner_part = jQuery('<span>', {
				class: `d-flex`,
				html: '<svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h.01"></path><path d="M3 18h.01"></path><path d="M3 6h.01"></path><path d="M8 12h13"></path><path d="M8 18h13"></path><path d="M8 6h13"></path></svg>'
			});
			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,
				css: { display: 'none' }
			});
			var $manage = jQuery('<div>', {
				class: 'manage_container repeater_manage',
				html: rowData.manage
			});

			var counter_text = jQuery('<span>', {
				class: `countable_cell_inner_counter`,
				text: value.values + ' ' + sheetspilot.editor.items
			});

			$visual.append(inner_part, counter_text);
			$div_container.append($visual, $editor, $manage);
			return $div_container;
		}
		if (cellType == 'wysiwyg') {

			var out_value = jQuery('<div>').html( value ).text();;
			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
				text: out_value.substr(0, 100)
			});

			$div_container.append($visual, $manage);

			return $div_container;
		}
		if (cellType == 'htmlout') {

			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
				html: value
			});

			$div_container.append($visual, $manage);

			return $div_container;
		}
		if (cellType == 'color_picker') {


			if (value != '') {
				var $single_tab_bubble = jQuery('<div>', {
					class: self.g_singleTagBubbleNoPrefix,
					text: value,
					css: { background: value }
				});
			} else {
				var $single_tab_bubble = '&nbsp;';
			}

			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix,
			});
			$visual.append($single_tab_bubble);

			var $editor = jQuery('<div>', {
				class: this.g_editorPartNoPrefix,
				css: { display: 'none' }
			});
			var $input = jQuery('<input>', {
				type: 'color',
				class: 'form-control ' + this.g_editorInputNoPrefix + ' color_picker_editor_input ',
				val: value
			});

			$editor.append($input);
			$div_container.append($visual, $editor);

			return $div_container;
		}
		if (cellType == "bulk_checkbox") {

			var $visual = jQuery('<div>', {
				class: this.g_visualPartNoPrefix + ' bulk-checkbox-wrapper',
			});


			// Add the actual checkbox input
			var $checkbox = `
				<label class="unlimitedai-plugin__td-bulk-edit__label">
					<input type="checkbox" class="unlimitedai-plugin__td-bulk-edit__select-input" value="`+ value + `">
				</label>
			`;

			$visual.append($checkbox);
			$div_container.append($visual);

			return $div_container;
		}

	}
	/**
	* function to show media upload
	*/
	showImageUploader(element) {

		var self = this;
		let attachment;
		const $clicked = element;
		

		var parent_td = $clicked.parents('td');

		var parent_container = parent_td.find(this.g_editorContainer);

		var file_frame;

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if (file_frame) {
			file_frame.open();
			return;
		}

		// Create the media frame.		
		file_frame = wp.media.frames.file_frame = wp.media({
			title: jQuery(this).data('uploader_title'),
			button: {
				text: jQuery(this).data('uploader_button_text'),
			},
			multiple: false  // Set to true to allow multiple files to be selected
		});

		// When an image is selected, run a callback.
		file_frame.on('select', function () {
			attachment = file_frame.state().get('selection').first().toJSON();

			var fullUrl = (attachment.sizes && attachment.sizes.full) ? attachment.sizes.full.url : attachment.url;
			var thumbUrl = (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url
				: ((attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url);
			var fileType = attachment.subtype || '';
			if (!fileType && attachment.mime) {
				fileType = String(attachment.mime).split('/').pop();
			}
			if (fileType === 'jpeg') {
				fileType = 'jpg';
			}

			self.syncImageCellAttachment(parent_container, attachment.id, thumbUrl, {
				fullUrl: fullUrl,
				fileSize: attachment.filesizeInBytes || 0,
				fileType: fileType,
				width: attachment.width || 0,
				height: attachment.height || 0,
				filename: attachment.filename || ''
			});

			self.onCellContentSave(parent_container);
			//self.initUploadFeaturedImageCells();
			self.hideEditImageForNoFeatured();
		});

		file_frame.on('open', function () {

			let selectedId = jQuery( '.ubai_featured_image_uploader', parent_container ).attr('data-id'); // или img.data('id')
	
			if (!selectedId) return;

			const selection = file_frame.state().get('selection');

			const attachment = wp.media.attachment(selectedId);
			attachment.fetch(); // важно!

			selection.reset([attachment]); // выделяем
		});

		// Finally, open the modal
		file_frame.open();

	}

	/**
	* click on reset button of the image uploader
	*/
	onFeaturedImageUploaderResetButtonClick(e) {
		const $clicked = jQuery(e.currentTarget);

		let parent_td = $clicked.parents('td');
		let parent_container = parent_td.find(this.g_editorContainer);

		this.syncImageCellAttachment(parent_container, '', g_urlImagePlaceholder, {
			fullUrl: g_urlImagePlaceholder
		});

		jQuery(this.g_ubaiFeaturedImageUploader, parent_container).addClass(this.g_isPlaceholderNoIndex);

		this.onCellContentSave(e.currentTarget);
		//this.initUploadFeaturedImageCells();

	}

	/**
	* init image column
	*/
	initUploadFeaturedImageCells() {
		var self = this;
		jQuery(this.g_featuredImageUploaderResetBtn).replaceWith();
		jQuery(this.g_featuredImageUploaderCell + ' ' + this.g_visualPart).each(function () {
			var objUploadFeaturedImage = jQuery(this);

			if (!jQuery(self.g_ubaiFeaturedImageUploader, objUploadFeaturedImage).hasClass(self.g_isPlaceholderNoIndex)) {
				var featuredImageResetButtonHTML = `<button class="ubai_featured_image_uploader--reset-btn unlimitedai-plugin__btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg></button>`;

				objUploadFeaturedImage.prepend(featuredImageResetButtonHTML);
			}



		});
	}

	limitToTwoRows() {
		var self = this;
		var hiddenClass = 'ubai_tax_block_item-hidden';


		jQuery(this.g_taxContainer).each(function () {

			var definde_items_count = 0;

			var objUl = jQuery(self.g_ubaiTaxBlock, this);
			objUl.empty();

			// Find EVERY li, including nested ones
			var objAllItems = objUl.find('li');
			var totalItems = objAllItems.length;


			let selected = [];
			jQuery(self.g_categoryEditor + ' input[type="checkbox"]:checked', this).each(function () {
				selected.push(jQuery(this).val());
				if (definde_items_count < 2) {
					var text = jQuery(this).closest('label').text().trim();
					var $single_tab_bubble = jQuery('<div>', {
						class: self.g_singleTagBubbleNoPrefix,
						text: text
					});
					objUl.append($single_tab_bubble);
				}

				definde_items_count++;
			});
			totalItems = selected.length;

			// Clean up previous counters to avoid duplicates
			objUl.find('.' + self.g_taxCounterBlockNoIndex).remove();

			/*
			if (totalItems <= 2) {
				objAllItems.removeClass(hiddenClass);
				return;
			}

			//Hide every item after the first two
			objAllItems.each(function (index) {
				if (index >= 2) {
					jQuery(this).addClass(hiddenClass);
				} else {
					jQuery(this).removeClass(hiddenClass);
				}
			});
			*/

			// Calculate how many are actually hidden
			var hiddenCount = totalItems - 2;

			if (hiddenCount > 0) {
				var objCounter = jQuery('<div>', {
					class: self.g_taxCounterBlockNoIndex,
					text: '+' + hiddenCount
				});
				objUl.append(objCounter);
			} else {
				var objCounter = jQuery('<div>', {
					class: self.g_taxCounterBlockNoIndex,
					text: '+' + hiddenCount,
					css: { display: 'none' }
				});
				objUl.append(objCounter);
			}

		});

	}

	/**
	 * on Global keydown
	 */
	onGlobalKeydown(e) {
		// process ctrl+Z
		if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
			this.undoCellEditions();
		}
		if (e.key === 'Escape') {
			this.closeFilterColumnDropdown();
		}
	}



	/**
	 * on table keydown
	 */
	onTHTDKeydown(e) {

		// если открыт Select2 — игнорим
		if (jQuery('.select2-container--open').length) {
			return;
		}

		let self = this;
		var objCurrent = jQuery(e.currentTarget);
		var objNext;
		var index = objCurrent.index();
		var objActive = this.$table.find(this.g_isActiveCell);

		var isRTL = objCurrent.closest('table').css('direction') === 'rtl';

		let ctrlShiftPressed = false;
		if (e.ctrlKey || e.metaKey) {
			ctrlShiftPressed = true;
		}
		if (e.shiftKey) {
			ctrlShiftPressed = true;
		}

		if (!ctrlShiftPressed && (e.which != 13)) {
			objActive.removeClass(this.g_isActiveCellNoIndex);
		}


		// get object container data
		var type = objCurrent.find(this.g_editorContainer).data('type');
		var parent_container = objCurrent.find(this.g_editorContainer);
		var this_row = objCurrent.data('row');
		var this_col = objCurrent.data('col');
		var target_class = '.cell_' + this_row + '_' + this_col;
		var parent_editor_container = objCurrent.find(this.g_editorContainer);
		var cell_column_name = objCurrent.find(this.g_editorContainer).data('column');

		// process ctrl+Z
		if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
			this.undoCellEditions();
		}

		// process ctrl+c
		if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c!') {


			navigator.clipboard.writeText('')
				.then(() => {

				})
				.catch(err => {

				});



			g_copyPastType = type;



			if (type == 'textarea' || type == 'text') {
				g_copyPastSelectName = '';
				g_copyPastValue = jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val();
				navigator.clipboard.writeText(g_copyPastValue);

			}
			if (type == 'acf_select' || type == 'tag' || type == 'post_object' || type == 'woo_post_object') {
				g_copyPastValueArray = [];
				g_copyPastValueTextArray = [];
				var is_multiple = jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).attr('multiple');

				if (!is_multiple) {
					// single value
					g_copyPastValueArray.push(jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val());
					g_copyPastValueTextArray.push(jQuery(this.g_editorPart + ' .' + type + '_editor_input option:selected', objCurrent).text());
				} else {
					jQuery(this.g_editorPart + ' .' + type + '_editor_input option:selected', objCurrent).each(function () {
						g_copyPastValueArray.push(jQuery(this).val());
						g_copyPastValueTextArray.push(jQuery(this).text());
					});
				}

				g_copyPastSelectName = jQuery(this.g_editorContainer, objCurrent).data('column');

			}
			if (type == 'image') {
				g_copyPastSelectName = '';
				g_copyPastValue = jQuery(this.g_ubaiFeaturedImageUploader, objCurrent).attr('data-id');
				g_copyPastImageURL = jQuery(this.g_ubaiFeaturedImageUploader, objCurrent).attr('src');

			}
			if (type == 'calendar') {
				g_copyPastSelectName = '';
				g_copyPastValue = jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val();

			}
			if (type == 'color_picker') {
				g_copyPastSelectName = '';
				g_copyPastValue = jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val();

			}
			if (type == 'acf_gallery' || type == 'acf_woo_gallery') {

				g_copyPastSelectName = '';
				g_copyPastValue = jQuery(this.g_editorContainer, objCurrent).data('value');
				g_copyPastHTML = jQuery(this.g_galleryImagesContainer, objCurrent).html();

				jQuery(this.g_singleImageContainer, objCurrent).each(function () {
					g_copyPastValueArray.push(jQuery(this).data('id'));
					g_copyPastValueImgArray.push(jQuery(this).data('img'));
				})

			}
			if (type == 'wysiwyg' || type == 'repeater') {

				g_copyPastSelectName = '';
				g_wyswygPostID = objCurrent.attr('data-row');
				g_copyPastValue = jQuery(this.g_visualPart, objCurrent).html();

			}
			if (type == 'taxonomy') {

				g_copyPastValueArray = [];

				g_copyPastSelectName = '';

				jQuery(this.g_categoryEditor + ' input[type="checkbox"]:checked', parent_container).each(function () {
					g_copyPastValueArray.push(jQuery(this).val());
				})
				g_copyPastValueArray = g_copyPastValueArray.filter((value, index, self) => {
					return self.indexOf(value) === index;
				});

			}

			return;
		}



		switch (e.which) {
			case 8: // escape

				// process only textarea or text
				if( !jQuery(e.target).hasClass('is_currently_edition') && e.target.tagName == 'TD' ){
						this.dropCellContent(e);
					}
				break;
			case 27: // escape

				// process only textarea or text
				if (type == 'textarea' || type == 'text') {
					this.restoreCellPrevState(e);
				}
				break;
			case 9:

				objActive.removeClass(this.g_isActiveCellNoIndex);
				break;
			case 37: // Left Arrow
				if (objCurrent.hasClass(this.g_isCurrentlyEditionNoIndex)) { return; }
				if (isRTL) {
					objNext = objCurrent.nextAll('td:visible').first();
				} else {
					objNext = objCurrent.prevAll('td:visible').first();
				}
				objNext.addClass(this.g_isActiveCellNoIndex);
				this.triggerEvent(this.g_events.SELECTION_CHANGE);
				break;

			case 38: // Up Arrow
				if (objCurrent.hasClass(this.g_isCurrentlyEditionNoIndex)) { return; }

				var objPrevRow = objCurrent.closest('tr').prev();

				// If no previous row in current section (tbody), look in thead
				if (objPrevRow.length === 0 && objCurrent.closest('tbody').length > 0) {
					objNext = objCurrent.closest('table').find('thead tr').last().find('th, td').eq(index);
				} else {
					objNext = objPrevRow.find('td, th').eq(index);
				}
				objNext.addClass(this.g_isActiveCellNoIndex);
				this.triggerEvent(this.g_events.SELECTION_CHANGE);
				break;

			case 39: // Right Arrow
				if (objCurrent.hasClass(this.g_isCurrentlyEditionNoIndex)) { return; }
				if (isRTL) {
					objNext = objCurrent.prevAll('td:visible').first();
				} else {
					objNext = objCurrent.nextAll('td:visible').first();
				}

				objNext.addClass(this.g_isActiveCellNoIndex);
				this.triggerEvent(this.g_events.SELECTION_CHANGE);
				break;

			case 40: // Down Arrow
				if (objCurrent.hasClass(this.g_isCurrentlyEditionNoIndex)) { return; }
				var objNextRow = objCurrent.closest('tr').next();

				// If no next row in current section (thead), look in tbody
				if (objNextRow.length === 0 && objCurrent.closest('thead').length > 0) {
					objNext = objCurrent.closest('table').find('tbody tr').first().find('td, th').eq(index);
				} else {
					objNext = objNextRow.find('td, th').eq(index);
				}
				objNext.addClass(this.g_isActiveCellNoIndex);
				this.triggerEvent(this.g_events.SELECTION_CHANGE);
				break;

			case 13: // Enter Key

				if (objCurrent.hasClass(this.g_isActiveCellNoIndex)) {
					e.preventDefault();
					objCurrent.dblclick();
				}

				if (jQuery(e.target).hasClass(this.g_editorInputNoPrefix)) {
					this.onCellContentSave(jQuery(e.target).parents(this.g_editorContainer));

					self.undoPrevCellValue = jQuery(self.g_editorPart + ' ' + self.g_editorInput, parent_container).val();
					jQuery(e.target).parents('td').addClass(this.g_isActiveCellNoIndex);
					jQuery(e.target).parents('td').focus();

				}
				break;
			case 113: // Enter Key
				e.preventDefault();
				objCurrent.dblclick();
				break;
			default: return;
		}

		if (objNext && objNext.length) {
			e.preventDefault();
			objNext.focus();
		}
	}

	/**
	 * on mouse down
	 */
	onTHMouseDown(e) {
		var self = this;
		var th = jQuery(e.currentTarget).parents('th');
		var startX = e.pageX;
		var startWidth = th.outerWidth();
		var colIndex = th.index();

		//block first three cols
		if (colIndex < 3) {
			return;
		}

		// Detect if we are in RTL mode
		var isRTL = jQuery('html').attr('dir') === 'rtl' || jQuery('body').hasClass('rtl');

		jQuery(document).on('mousemove.resizer', function (e) {
			// In RTL, we subtract the movement because the X-axis is inverted
			var movement = isRTL ? (startX - e.pageX) : (e.pageX - startX);
			var newWidth = startWidth + movement;

			if (newWidth > 50) {
				th.css({
					'width': newWidth + 'px',
					'min-width': newWidth + 'px',
				});

				self.$table.find('tbody tr').each(function () {
					jQuery(this).find('td').eq(colIndex).css({
						'width': newWidth + 'px',
						'min-width': newWidth + 'px',
					});
				});
			}
		});

		jQuery(document).on('mouseup.resizer', function () {
			jQuery(document).off('mousemove.resizer mouseup.resizer');
		});
	}

	/**
	 * iit sticky post title column
	 */
	initStickyPostTitleandID() {
		var objFirstTh = this.$table.find('thead th').first();
		var firstColWidth = Math.floor(objFirstTh.outerWidth());
		var objSecondTh = objFirstTh.next();
		var secondColumnWidth = Math.floor(objSecondTh.outerWidth());
		var objThirdTh = objSecondTh.next();
		var objSEcondTds = this.$table.find('tbody td:nth-child(2)');
		var objThirdTds = this.$table.find('tbody td:nth-child(3)');

		objSecondTh.css("min-width", secondColumnWidth + "px");
		objSecondTh.css("width", secondColumnWidth + "px");
		objSecondTh.css("max-width", secondColumnWidth + "px");
		objSecondTh.css("inset-inline-start", firstColWidth + "px");

		objSEcondTds.css("min-width", secondColumnWidth + "px");
		objSEcondTds.css("width", secondColumnWidth + "px");
		objSEcondTds.css("max-width", secondColumnWidth + "px");
		objSEcondTds.css("inset-inline-start", firstColWidth + "px");

		objThirdTh.css("inset-inline-start", (firstColWidth + secondColumnWidth) + "px");
		objThirdTds.css("inset-inline-start", (firstColWidth + secondColumnWidth) + "px");
	}

	/**
	 * Document click: close floating category editor / column filters (does not change table cell selection).
	 */
	onDucumentClick(e) {
		var objOpenEditor = jQuery(this.g_categoryEditor + ':visible');

		if (objOpenEditor && objOpenEditor.length) {
			var objCurrentParentCell = objOpenEditor.closest(this.g_categoryTd);
			var isClickInsideActiveCell = objCurrentParentCell.is(e.target) || objCurrentParentCell.has(e.target).length !== 0;
			var isCloseButtonClick = jQuery(e.target).hasClass(this.g_categoryEditorCloseBtnClass) || jQuery(e.target).closest(this.g_categoryEditorCloseBtn).length !== 0;

			if (isCloseButtonClick == true) {
				isClickInsideActiveCell = false;
			}

			if (!isClickInsideActiveCell) {
				this.closeCategoryEditor(objOpenEditor);
			}
		}

		//click on document should close column filter dropdown
		var objOpenedColumnFilterDropdown = jQuery(`.${this.g_columnFilterDropdownClass}.${this.g_classActive}`);

		if (objOpenedColumnFilterDropdown && objOpenedColumnFilterDropdown.length > 0) {
			var isClickInsideActiveColumnFilterDropdown = objOpenedColumnFilterDropdown.is(e.target) || objOpenedColumnFilterDropdown.has(e.target).length !== 0;
			var isClickOnColumnFilterDropdownBtn = jQuery(e.target).closest(this.g_objFilterColumnBtn).length > 0;

			if (!isClickInsideActiveColumnFilterDropdown && !isClickOnColumnFilterDropdownBtn)
				this.closeFilterColumnDropdown();
		}
	}

	/**
	 * Debug: show apply-prompt loader on the Post Title cell of the first row.
	 */
	debugShowInitialCellLoader() {

		var $row = this.$tableBody.find('tr').first();
		if (!$row.length) {
			return;
		}

		// Prefer the Post Title column cell if present and visible
		var $cell = $row.find('td.post_title_cell_td').filter(function () {
			return jQuery(this).is(':visible');
		}).first();

		// Fallback: try by editor_container[data-column="post_title"]
		if (!$cell.length) {
			var $fromEditor = $row.find('td .editor_container[data-column="post_title"]').first();
			if ($fromEditor.length) {
				$cell = $fromEditor.closest('td');
			}
		}

		// Final fallback: first visible data cell
		if (!$cell.length) {
			$cell = $row.children('td:visible').first();
		}

		if (!$cell.length) {
			return;
		}

		$cell.addClass('ubai-cell-apply-prompt-loading');
	}

	/**
	 * Return the currently selected table cell (jQuery), or empty set.
	 */
	getSelectedCell() {
		return this.$table.find('td.' + this.g_isActiveCellNoIndex).first();
	}

	/**
	 * Remove apply-prompt loading from the cell that started the request (not necessarily the currently selected cell).
	 */
	clearApplyPromptLoadingTracked() {
		if (this.applyPromptLoadingAddress) {
			this.$table.find('td' + this.applyPromptLoadingAddress).removeClass('ubai-cell-apply-prompt-loading');
			this.applyPromptLoadingAddress = null;
		}
		if (this.$applyPromptLoadingCellFallback && this.$applyPromptLoadingCellFallback.length) {
			this.$applyPromptLoadingCellFallback.removeClass('ubai-cell-apply-prompt-loading');
			this.$applyPromptLoadingCellFallback = null;
		}
		this.applyPromptLoadingCounts = {};
	}

	/**
	 * Clear pending prompt-dialog markers and counters.
	 * This is independent from apply-prompt loading (ubai-cell-apply-prompt-loading).
	 */
	clearPromptDialogPendingTracked() {
		this.promptDialogPendingCounts = {};
		this.$table.find('td.ubai-cell-prompt-dialog-pending').removeClass('ubai-cell-prompt-dialog-pending');
	}

	/**
	 * True when the image cell already shows a real image (not the placeholder).
	 *
	 * @param {jQuery} $cell Table cell.
	 * @return {boolean}
	 */
	cellHasRealImage($cell) {
		if (!$cell || !$cell.length) {
			return false;
		}

		var $container = $cell.find(this.g_editorContainer).first();
		if (!$container.length) {
			return false;
		}

		var cellType = $container.data('type');
		if (cellType !== 'image' && $container.data('column') !== 'post_image') {
			return false;
		}

		var $img = $container.find(this.g_ubaiFeaturedImageUploader).first();
		if (!$img.length) {
			$img = $container.find(this.g_visualPart + ' img').first();
		}
		if (!$img.length) {
			return false;
		}

		var attachmentId = String($img.attr('data-id') || '').trim();
		if (attachmentId && attachmentId !== '0') {
			return true;
		}

		var dataValue = String($container.attr('data-value') || $container.data('value') || '').trim();
		if (/^\d+$/.test(dataValue) && dataValue !== '0') {
			return true;
		}

		var src = String($img.attr('src') || '').toLowerCase();
		if (src === '' || $img.hasClass(this.g_isPlaceholderNoIndex) || src.indexOf('placeholder.png') !== -1) {
			return false;
		}

		return true;
	}

	/**
	 * Remove apply-prompt loading / pending-dialog spinners for one cell.
	 *
	 * @param {Object} table getTableData()-like snapshot with postId and columnIndex.
	 */
	clearCellPromptActivityIndicators(table) {
		var snapshot = table ? Object.assign({ isSelected: true }, table) : null;
		var $cell = snapshot ? this.getCellFromApplyPromptTable(snapshot) : jQuery();
		var key = this.getApplyPromptLoadingKeyFromTable(snapshot);

		if (key) {
			delete this.applyPromptLoadingCounts[key];
			delete this.promptDialogPendingCounts[key];
			delete this.applyPromptWaitingCounts[key];
		}

		if ($cell.length) {
			$cell.removeClass('ubai-cell-apply-prompt-loading');
			$cell.removeClass('ubai-cell-apply-prompt-waiting');
			$cell.removeClass('ubai-cell-prompt-dialog-pending');
			$cell.removeData('ubaiApplyPromptLoadingCount');
			$cell.removeData('ubaiPromptDialogPendingCount');
		}
	}

	/**
	 * Drop stale spinners on image cells that already contain a real image.
	 *
	 * @param {Object.<string, boolean>} [activeLoadingKeys] Keys still waiting on the server.
	 */
	clearStaleImageCellPromptIndicators(activeLoadingKeys) {
		var self = this;
		activeLoadingKeys = activeLoadingKeys || {};

		this.$table.find('td').each(function () {
			var $cell = jQuery(this);
			if (
				!$cell.hasClass('ubai-cell-prompt-dialog-pending') &&
				!$cell.hasClass('ubai-cell-apply-prompt-loading')
			) {
				return;
			}
			if (!self.cellHasRealImage($cell)) {
				return;
			}

			var key = self.getPendingPromptResultCellKey($cell);
			if (key && activeLoadingKeys[key]) {
				return;
			}

			self.clearCellPromptActivityIndicators({
				postId: $cell.data('row'),
				columnIndex: $cell.data('col')
			});
		});
	}

	/**
	 * Build a table snapshot from an image queue key (cell_{postId}_{columnIndex}).
	 *
	 * @param {string} queueKey Queue map key.
	 * @return {Object|null}
	 */
	tableSnapshotFromImageQueueKey(queueKey) {
		var match = /^cell_(\d+)_(\d+)$/.exec(String(queueKey || ''));
		if (!match) {
			return null;
		}
		return {
			isSelected: true,
			postId: parseInt(match[1], 10),
			columnIndex: parseInt(match[2], 10)
		};
	}

	getPromptDialogPendingKeyFromTable(table) {
		return this.getApplyPromptLoadingKeyFromTable(table);
	}

	/**
	 * Mark that this cell has a replace dialog response ready but the dialog is not shown yet.
	 * @param {Object} table - getTableData()-like snapshot
	 */
	markPromptDialogPendingForTable(table) {
		var $cell = this.getCellFromApplyPromptTable(table);
		if (!$cell || !$cell.length) {
			return;
		}
		var key = this.getPromptDialogPendingKeyFromTable(table);
		if (key) {
			this.promptDialogPendingCounts[key] = (this.promptDialogPendingCounts[key] || 0) + 1;
		} else {
			var cnt = Number($cell.data('ubaiPromptDialogPendingCount') || 0);
			$cell.data('ubaiPromptDialogPendingCount', cnt + 1);
		}
		$cell.addClass('ubai-cell-prompt-dialog-pending');
	}

	/**
	 * Remove pending prompt-dialog marker when the dialog is actually displayed.
	 * @param {Object} table - getTableData()-like snapshot
	 */
	clearPromptDialogPendingForTable(table) {
		var $cell = this.getCellFromApplyPromptTable(table);
		if (!$cell || !$cell.length) {
			return;
		}
		var key = this.getPromptDialogPendingKeyFromTable(table);
		if (key) {
			var next = (this.promptDialogPendingCounts[key] || 0) - 1;
			if (next <= 0) {
				delete this.promptDialogPendingCounts[key];
				$cell.removeClass('ubai-cell-prompt-dialog-pending');
			} else {
				this.promptDialogPendingCounts[key] = next;
			}
			return;
		}

		var cnt = Number($cell.data('ubaiPromptDialogPendingCount') || 0) - 1;
		if (cnt <= 0) {
			$cell.removeData('ubaiPromptDialogPendingCount');
			$cell.removeClass('ubai-cell-prompt-dialog-pending');
		} else {
			$cell.data('ubaiPromptDialogPendingCount', cnt);
		}
	}

	getPendingPromptResultCellKey($cell) {
		if (!$cell || !$cell.length) {
			return null;
		}
		var $row = $cell.closest('tr');
		var postId = $row.data('id') || $cell.data('row');
		var colIdx = $cell.data('col');
		if (postId == null || colIdx == null) {
			return null;
		}
		return String(postId) + ':' + String(colIdx);
	}

	ensurePromptResultIconPanel($cell) {
		if (!$cell || !$cell.length) {
			return jQuery();
		}
		var $panel = $cell.find('.bottom_manage_container.post_manage').first();
		if ($panel.length) {
			return $panel;
		}
		$panel = jQuery('<div>', {
			class: 'bottom_manage_container post_manage ubai-prompt-result-icon-panel'
		});
		$cell.append($panel);
		return $panel;
	}

	getPendingPromptResultIconLabel() {
		var label = 'Reopen prompt result';
		if (typeof g_ubaiPromptsStrings === 'string' && g_ubaiPromptsStrings.length) {
			try {
				var parsed = JSON.parse(g_ubaiPromptsStrings);
				if (parsed && parsed.reopenPromptResult) {
					label = parsed.reopenPromptResult;
				}
			} catch (e) { /* use default */ }
		} else if (g_ubaiPromptsStrings && g_ubaiPromptsStrings.reopenPromptResult) {
			label = g_ubaiPromptsStrings.reopenPromptResult;
		}
		return label;
	}

	getPendingPromptResultIconHtml() {
		var label = this.getPendingPromptResultIconLabel();
		return '<span class="' + this.g_pendingPromptResultIconNoIndex + ' has-tooltip" data-title="' + label + '">' +
			'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
			'<path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72"></path>' +
			'<path d="m14 7 3 3"></path><path d="M5 6v4"></path><path d="M19 14v4"></path><path d="M10 2v2"></path>' +
			'<path d="M7 8H3"></path><path d="M21 16h-4"></path><path d="M11 3H9"></path></svg></span>';
	}

	showDiscardedPendingPromptResultIcon($cell) {
		if (!$cell || !$cell.length) {
			return;
		}
		var $panel = this.ensurePromptResultIconPanel($cell);
		if (!$panel.length) {
			return;
		}
		$panel.find(this.g_pendingPromptResultIcon).remove();
		$panel.append(this.getPendingPromptResultIconHtml());
		$cell.addClass('ubai-cell-has-discarded-prompt-result');
	}

	clearDiscardedPendingPromptResultIcon($cell) {
		if (!$cell || !$cell.length) {
			return;
		}
		$cell.find(this.g_pendingPromptResultIcon).remove();
		$cell.removeClass('ubai-cell-has-discarded-prompt-result');
		var $panel = $cell.find('.ubai-prompt-result-icon-panel').first();
		if ($panel.length && !$panel.children().length) {
			$panel.remove();
		}
	}

	setDiscardedPendingPromptResult($cell, data) {
		var key = this.getPendingPromptResultCellKey($cell);
		if (!key || !data) {
			return;
		}
		this.discardedPendingPromptResults[key] = data;
		this.showDiscardedPendingPromptResultIcon($cell);
	}

	clearDiscardedPendingPromptResult($cell) {
		var key = this.getPendingPromptResultCellKey($cell);
		if (key) {
			delete this.discardedPendingPromptResults[key];
		}
		this.clearDiscardedPendingPromptResultIcon($cell);
	}

	getDiscardedPendingPromptResult($cell) {
		var key = this.getPendingPromptResultCellKey($cell);
		return key ? (this.discardedPendingPromptResults[key] || null) : null;
	}

	findCellByPendingPromptResultKey(key) {
		if (!key) {
			return jQuery();
		}
		var parts = String(key).split(':');
		if (parts.length !== 2) {
			return jQuery();
		}
		var postId = parts[0];
		var colIdx = parts[1];
		return this.$table.find('td').filter(function () {
			var $c = jQuery(this);
			return String($c.data('row')) === String(postId) && Number($c.data('col')) === Number(colIdx);
		}).first();
	}

	restoreDiscardedPendingPromptResultIcons() {
		var self = this;
		jQuery.each(this.discardedPendingPromptResults, function (key) {
			var $cell = self.findCellByPendingPromptResultKey(key);
			if ($cell.length) {
				self.showDiscardedPendingPromptResultIcon($cell);
			}
		});
	}

	onPendingPromptResultIconClick(e) {
		e.preventDefault();
		e.stopPropagation();
		var $cell = jQuery(e.currentTarget).closest('td');
		this.openDiscardedPendingPromptResult($cell);
	}

	openDiscardedPendingPromptResult($cell) {
		if (!$cell || !$cell.length) {
			return;
		}
		var data = this.getDiscardedPendingPromptResult($cell);
		if (!data || !window.ubaiPrompts) {
			return;
		}
		if (data.tableSnapshot && typeof this.selectCellFromApplyPromptTable === 'function') {
			this.selectCellFromApplyPromptTable(data.tableSnapshot, { suppressPromptDialogClose: true });
		} else {
			this.suppressPromptDialogCloseOnSelection = true;
			try {
				this.$table.find('td.' + this.g_isActiveCellNoIndex).removeClass(this.g_isActiveCellNoIndex);
				$cell.addClass(this.g_isActiveCellNoIndex);
				this.scrollCellIntoView($cell);
				this.triggerEvent(this.g_events.SELECTION_CHANGE);
			} finally {
				this.suppressPromptDialogCloseOnSelection = false;
			}
		}
		if (data.type === 'image') {
			if (typeof window.ubaiPrompts.setPromptReplaceDialogImagePreview === 'function') {
				window.ubaiPrompts.setPromptReplaceDialogImagePreview(data.requestId, data.previewUrl, data.postId, data.column);
			}
		} else if (typeof window.ubaiPrompts.setPromptReplaceDialogText === 'function') {
			window.ubaiPrompts.setPromptReplaceDialogText(data.displayText, data.insertText, data.blocks || null);
		}
		if (typeof window.ubaiPrompts.showPromptReplaceDialogForCell === 'function') {
			window.ubaiPrompts.showPromptReplaceDialogForCell($cell, { reopenDiscarded: true });
		}
	}

	/**
	 * Resolve cell from apply_prompt table snapshot without changing active selection.
	 * @param {Object} table - Object like getTableData() with isSelected, postId, columnIndex, optional column.
	 * @return {jQuery} Matched cell or empty set.
	 */
	getCellFromApplyPromptTable(table) {

		if (!table || table.isSelected !== true) {
			return jQuery();
		}
		var postId = table.postId;
		var colIdx = table.columnIndex;
		var $td = jQuery();
		var $row = jQuery();
		if (postId !== null && postId !== undefined) {
			$row = this.$table.find('tr[data-id="' + postId + '"]').first();
			if (!$row.length) {
				$row = this.$table.find('tr').filter(function () {
					return String(jQuery(this).data('id')) === String(postId);
				}).first();
			}
		}
		// Prefer data-column: td data-col comes from row data order, not thead th.index().
		if ($row.length && table.column) {
			$td = $row.find(this.g_editorContainer + '[data-column="' + table.column + '"]').closest('td').first();
		}
		if (!$td.length && $row.length && colIdx !== null && colIdx !== undefined) {
			$td = $row.find('td').filter(function () {
				return Number(jQuery(this).data('col')) === Number(colIdx);
			}).first();
		}
		if (!$td.length && postId !== null && postId !== undefined && colIdx !== null && colIdx !== undefined) {
			$td = this.$table.find('td').filter(function () {
				var $c = jQuery(this);
				return String($c.data('row')) === String(postId) && Number($c.data('col')) === Number(colIdx);
			}).first();
		}
		return $td;
	}

	/**
	 * Human-readable column + post labels for prompt result UI (dialog meta, requests panel).
	 *
	 * @param {Object} table getTableData()-like snapshot (postId, columnIndex, column).
	 * @param {jQuery} [$cell] Optional cell to read data-column when table.column is missing.
	 * @return {{label: string, sub: string}}
	 */
	getPromptCellDisplayInfo(table, $cell) {
		var info = { label: '', sub: '' };
		table = table || {};
		$cell = $cell || jQuery();

		var columnKey = table.column ? String(table.column) : '';

		if (!columnKey && $cell.length) {
			var $container = $cell.find(this.g_editorContainer).first();
			columnKey = String($container.data('column') || $cell.attr('data-name') || '').trim();
		}

		if (!columnKey && table.postId != null && table.columnIndex != null) {
			var $resolvedCell = this.getCellFromApplyPromptTable(jQuery.extend({ isSelected: true }, table));
			if ($resolvedCell.length) {
				var $resolvedContainer = $resolvedCell.find(this.g_editorContainer).first();
				columnKey = String($resolvedContainer.data('column') || $resolvedCell.attr('data-name') || '').trim();
			}
		}

		var columnName = '';
		if (columnKey && this.$table && this.$table.length) {
			var $thByName = this.$table.find('thead th').filter(function () {
				return String(jQuery(this).attr('data-name') || '') === columnKey;
			}).first();
			if ($thByName.length) {
				columnName = ($thByName.find('.unlimitedai-plugin__th-title').first().text() || '').trim() || columnKey;
			} else {
				columnName = columnKey;
			}
		}

		if (!columnName && table.columnIndex != null && this.$table && this.$table.length) {
			var $th = this.$table.find('thead th').eq(Number(table.columnIndex));
			if ($th.length) {
				columnName = ($th.find('.unlimitedai-plugin__th-title').first().text() || '').trim() || ($th.attr('data-name') || '').trim();
			}
		}

		var postId = table.postId;
		if (postId == null && $cell.length) {
			postId = $cell.closest('tr').data('id') || $cell.data('row') || null;
		}

		var postTitle = '';
		if (postId != null && this.$table && this.$table.length) {
			var $row = this.$table.find('tr[data-id="' + postId + '"]').first();
			if ($row.length) {
				var $titleCell = $row.find('td.post_title_cell_td').first();
				if (!$titleCell.length && $row.children('td').length > 1) {
					$titleCell = $row.children('td').eq(1);
				}
				if ($titleCell.length) {
					postTitle = this.getPromptDialogTargetCellValue($titleCell) || '';
					if (!postTitle) {
						postTitle = ($titleCell.text() || '').trim();
					}
				}
			}
		}

		if (postTitle.length > 34) {
			postTitle = postTitle.substring(0, 34).trim() + '\u2026';
		}

		info.label = postTitle || (postId != null ? 'Post #' + postId : 'Post');
		info.sub = columnName || 'Cell';
		return info;
	}

	getApplyPromptLoadingKeyFromTable(table) {
		if (!table) {
			return null;
		}
		if (table.postId === null || table.postId === undefined || table.columnIndex === null || table.columnIndex === undefined) {
			return null;
		}
		return String(table.postId) + ':' + String(table.columnIndex);
	}

	incrementApplyPromptLoadingForCell($cell, key) {
		if (!$cell || !$cell.length) {
			return;
		}
		if (key) {
			this.applyPromptLoadingCounts[key] = (this.applyPromptLoadingCounts[key] || 0) + 1;
		} else {
			var count = Number($cell.data('ubaiApplyPromptLoadingCount') || 0);
			$cell.data('ubaiApplyPromptLoadingCount', count + 1);
		}
		$cell.addClass('ubai-cell-apply-prompt-loading');
		$cell.removeClass('ubai-cell-apply-prompt-waiting');
	}

	incrementApplyPromptWaitingForCell($cell, key) {
		if (!$cell || !$cell.length) {
			return;
		}
		if (key) {
			this.applyPromptWaitingCounts[key] = (this.applyPromptWaitingCounts[key] || 0) + 1;
		}
		$cell.addClass('ubai-cell-apply-prompt-waiting');
	}

	decrementApplyPromptWaitingForCell($cell, key) {
		if (!$cell || !$cell.length) {
			return;
		}
		if (key) {
			var waitNext = (this.applyPromptWaitingCounts[key] || 0) - 1;
			if (waitNext <= 0) {
				delete this.applyPromptWaitingCounts[key];
				$cell.removeClass('ubai-cell-apply-prompt-waiting');
			} else {
				this.applyPromptWaitingCounts[key] = waitNext;
			}
			return;
		}
		$cell.removeClass('ubai-cell-apply-prompt-waiting');
	}

	clearAllApplyPromptWaiting() {
		this.applyPromptWaitingCounts = {};
		this.$table.find('td.ubai-cell-apply-prompt-waiting').removeClass('ubai-cell-apply-prompt-waiting');
	}

	decrementApplyPromptLoadingForCell($cell, key) {
		if (!$cell || !$cell.length) {
			return;
		}
		if (key) {
			var next = (this.applyPromptLoadingCounts[key] || 0) - 1;
			if (next <= 0) {
				delete this.applyPromptLoadingCounts[key];
				$cell.removeClass('ubai-cell-apply-prompt-loading');
			} else {
				this.applyPromptLoadingCounts[key] = next;
			}
			return;
		}
		var count = Number($cell.data('ubaiApplyPromptLoadingCount') || 0) - 1;
		if (count <= 0) {
			$cell.removeData('ubaiApplyPromptLoadingCount');
			$cell.removeClass('ubai-cell-apply-prompt-loading');
		} else {
			$cell.data('ubaiApplyPromptLoadingCount', count);
		}
	}

	/**
	 * Show or hide the apply-prompt loading overlay on a cell.
	 * @param {boolean} show - true to show overlay, false to remove.
	 * @param {boolean} useDialogCell - if true, use the replace dialog target cell (e.g. regenerate); otherwise use selected cell.
	 * @param {Object} [tableSnapshot] - Optional getTableData()-like snapshot for stable per-request targeting.
	 */
	setCellApplyPromptLoading(show, useDialogCell, tableSnapshot) {
		var $baseCell = jQuery();
		var key = this.getApplyPromptLoadingKeyFromTable(tableSnapshot);

		if (tableSnapshot && tableSnapshot.isSelected === true) {
			$baseCell = this.getCellFromApplyPromptTable(tableSnapshot);
		}
		if ((!$baseCell || !$baseCell.length) && useDialogCell && typeof window !== 'undefined' && window.ubaiPrompts && window.ubaiPrompts.getPromptReplaceDialogTargetCell) {
			$baseCell = window.ubaiPrompts.getPromptReplaceDialogTargetCell();
		}
		if ((!$baseCell || !$baseCell.length)) {
			$baseCell = this.getSelectedCell();
		}
		if (!$baseCell || !$baseCell.length) {
			return;
		}

		if (show) {
			this.incrementApplyPromptLoadingForCell($baseCell, key);
		} else {
			this.decrementApplyPromptLoadingForCell($baseCell, key);
		}
	}

	/**
	 * Show or hide the queued-waiting overlay on a cell (concurrency cap — not started yet).
	 * @param {boolean} show
	 * @param {Object} tableSnapshot getTableData()-like snapshot with postId and columnIndex.
	 */
	setCellApplyPromptWaiting(show, tableSnapshot) {
		if (!tableSnapshot || tableSnapshot.isSelected !== true) {
			return;
		}
		var $cell = this.getCellFromApplyPromptTable(tableSnapshot);
		if (!$cell.length) {
			return;
		}
		var key = this.getApplyPromptLoadingKeyFromTable(tableSnapshot);
		if (show) {
			this.incrementApplyPromptWaitingForCell($cell, key);
		} else {
			this.decrementApplyPromptWaitingForCell($cell, key);
		}
	}

	/**
	 * Extract current value from target cell.
	 */
	getPromptDialogTargetCellValue($cell) {
		if (!$cell || !$cell.length) {
			return "";
		}

		var $container = $cell.find(this.g_editorContainer).first();
		if (!$container.length) {
			return "";
		}

		var value = $container.data('value');
		if (typeof value === 'undefined') {
			value = $container.attr('data-value');
		}

		if (value === null || value === '' || typeof value === 'undefined') {
			var $visual = $container.find(this.g_visualPart);
			if ($visual.length) {
				value = $visual.text();
			}
		}

		return (typeof value === "string") ? value : "";
	}

	/**
	 * Check whether a cell type is an ACF repeater cell.
	 */
	isACFRepeaterCellType(type) {

		return type === 'repeater' || type === 'acf_repeater';
	}

	/**
	 * Update the visible repeater item counter for a table cell.
	 */
	updateRepeaterCellCounter($cell, count) {

		if (!$cell || !$cell.length) {
			return;
		}

		var itemCount = parseInt(count, 10);
		if (isNaN(itemCount) || itemCount < 0) {
			itemCount = 0;
		}

		jQuery('.countable_cell_inner_counter', $cell).html(itemCount + ' ' + sheetspilot.editor.items);
	}

	/**
	 * Parse repeater rows returned by AI.
	 */
	parsePromptRepeaterRows(value) {

		if (Array.isArray(value)) {
			return value;
		}

		if (typeof value !== 'string') {
			return null;
		}

		var valueTrimmed = value.trim();
		if (!valueTrimmed) {
			return null;
		}

		try {
			var parsed = JSON.parse(valueTrimmed);
			if (Array.isArray(parsed)) {
				return parsed;
			}
			if (parsed && typeof parsed === 'object' && Array.isArray(parsed.data)) {
				return parsed.data;
			}
			if (parsed && typeof parsed === 'object' && Array.isArray(parsed.output)) {
				return parsed.output;
			}
		} catch (e) {
			return null;
		}

		return null;
	}

	/**
	 * Convert simple repeater row objects to save_post_multidata rows.
	 */
	convertRepeaterRowsToSaveMultidataRows(rows) {

		var repeaterInfo = [];
		jQuery.each(rows, function (rowIndex, row) {
			if (!row || typeof row !== 'object' || Array.isArray(row)) {
				return;
			}

			jQuery.each(row, function (fieldName, fieldValue) {
				repeaterInfo.push({
					block: rowIndex,
					parent: rowIndex,
					repeater_name: '',
					field_name: fieldName,
					value: fieldValue
				});
			});
		});

		return repeaterInfo;
	}

	/**
	 * Save AI-generated repeater rows and update the visible item count.
	 */
	savePromptRepeaterCellData($cell, rows, fieldName, postId, callback, options) {

		var repeaterInfo = this.convertRepeaterRowsToSaveMultidataRows(rows);
		var data = {
			post_id: postId,
			post_data: [{
				data_table: 'postmeta',
				name: '_repeater_data',
				repeater_name: fieldName,
				value: repeaterInfo
			}]
		};

		this.updateRepeaterCellCounter($cell, rows.length);
		options = options && typeof options === 'object' ? options : {};
		if (options.suppressAjaxLoader === true) {
			this.g_doublyAdmin.setAjaxLoaderID(function () { });
		} else {
			this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderNoIndex);
		}
		this.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {
			if (typeof callback === 'function') {
				callback(response);
			}
		});
	}

	/**
	 * Whether a string looks like Elementor layout JSON.
	 *
	 * @param {string} value Candidate value.
	 * @return {boolean}
	 */
	isElementorLayoutJson(value) {
		if (typeof value !== 'string') {
			return false;
		}
		var trimmed = value.trim();
		if (!trimmed || trimmed.charAt(0) !== '[') {
			return false;
		}
		try {
			var parsed = JSON.parse(trimmed);
			return Array.isArray(parsed) && parsed.length > 0 && parsed[0] && parsed[0].elType;
		} catch (e) {
			return false;
		}
	}

	/**
	 * Loose check for Elementor layout JSON (used when strict JSON.parse fails).
	 */
	looksLikeElementorJson(value) {
		if (typeof value !== 'string') {
			return false;
		}
		var trimmed = value.trim();
		if (!trimmed) {
			return false;
		}
		var first = trimmed.charAt(0);
		if (first !== '[' && first !== '{') {
			return false;
		}
		return trimmed.indexOf('"elType"') !== -1;
	}

	/**
	 * Whether a value is an AI content-blocks payload (not Elementor layout JSON).
	 *
	 * @param {string|Object} value Candidate value.
	 * @return {boolean}
	 */
	isContentBlocksPayload(value) {
		if (value === null || value === undefined || value === '') {
			return false;
		}

		var data = value;
		if (typeof value === 'string') {
			var trimmed = value.trim();
			if (!trimmed || (trimmed.charAt(0) !== '{' && trimmed.charAt(0) !== '[')) {
				return false;
			}
			if (this.looksLikeElementorJson(trimmed)) {
				return false;
			}
			try {
				data = JSON.parse(trimmed);
			} catch (e) {
				return false;
			}
		}

		if (!data || typeof data !== 'object') {
			return false;
		}

		if (Array.isArray(data.blocks) && data.blocks.length > 0) {
			return true;
		}

		if (Array.isArray(data) && data.length > 0 && data[0] && data[0].type && !data[0].elType) {
			return true;
		}

		return false;
	}

	/**
	 * Normalize Elementor save payload to a JSON string.
	 */
	normalizeElementorSaveJson(value) {
		if (value === null || typeof value === 'undefined' || value === '') {
			return '';
		}
		if (typeof value === 'string') {
			return value;
		}
		if (typeof value === 'object') {
			try {
				return JSON.stringify(value);
			} catch (e) {
				return '';
			}
		}
		return String(value);
	}

	/**
	 * Split post_content save payload for Elementor rows: plain text in value, layout JSON in elementor_data.
	 * Preserves arrays (e.g. multi post_object / gallery IDs) so ACF receives the correct type.
	 *
	 * @param {jQuery} $row Table row.
	 * @param {jQuery} $container Editor container.
	 * @param {string} column Column name.
	 * @param {string|number|Array} value Current cell value.
	 * @return {{value:string|number|Array,is_elementor:number,elementor_data:string}}
	 */
	preparePostContentSaveFields($row, $container, column, value) {
		var isElementorRow = $row.attr('data-is-elementor') === '1';
		var payloadValue;
		if (Array.isArray(value)) {
			payloadValue = value;
		} else if (typeof value === 'string' || typeof value === 'number') {
			payloadValue = value;
		} else {
			payloadValue = String(value || '');
		}
		var payload = {
			value: payloadValue,
			is_elementor: isElementorRow ? 1 : 0,
			elementor_data: ''
		};

		if (column !== 'post_content') {
			return payload;
		}

		var elementorData = $container.data('elementor-value') || '';
		elementorData = this.normalizeElementorSaveJson(elementorData);
		if (!elementorData && (this.isElementorLayoutJson(payload.value) || this.looksLikeElementorJson(payload.value))) {
			elementorData = payload.value;
			payload.value = '';
			payload.is_elementor = 1;
		}
		if (elementorData) {
			payload.elementor_data = String(elementorData);
			payload.is_elementor = 1;
		}

		return payload;
	}

	/**
	 * Apply replacement text (or HTML for image) to a cell and persist it.
	 * @param {jQuery} $cell - Target cell.
	 * @param {string} replacementText - Content to display in cell (e.g. text or img tag).
	 * @param {string|number|array} [saveValue] - Optional. If provided, this value is sent to save_edited_posts (e.g. attachment ID for post_image).
	 * @param {Object} [options] - Optional. { visualOnly: true } to update cell display only, skip save_edited_posts.
	 *                             { suppressAjaxLoader: true } to skip the global "Saving..." AJAX loader.
	 */
	applyPromptReplacementToCell($cell, replacementText, saveValue, options) {

		if (!$cell || !$cell.length) {
			return;
		}

		var $container = $cell.find(this.g_editorContainer).first();
		if (!$container.length) {
			return;
		}

		var type = $container.data('type');
		var column = $container.data('column');
		var $row = $cell.closest('tr');
		var post_id = $row.data('id') || $cell.data('row') || null;
		var column_id = $cell.data('col') || null;
		var isElementorRow = $row.attr('data-is-elementor') === '1';
		var isElementorPostContent = isElementorRow && column === 'post_content';
		var manage = $container.data('manage') || '';

		if (typeof manage === "string") {
			manage = manage.replace(/\\'/g, "'");
		}

		var displayContent = (typeof replacementText === "string") ? replacementText : '';
		var blocksPayload = '';
		var value = displayContent;

		if (options && options.blocks) {
			blocksPayload = this.normalizeElementorSaveJson(options.blocks);
		}

		if (typeof saveValue !== "undefined" && saveValue !== null && saveValue !== "") {
			if (!blocksPayload && column === 'post_content' && this.isContentBlocksPayload(saveValue)) {
				blocksPayload = this.normalizeElementorSaveJson(saveValue);
				value = displayContent;
			} else {
				value = this.normalizeElementorSaveJson(saveValue);
				if (!displayContent) {
					displayContent = value;
				}
			}
		}

		var visualOnly = options && options.visualOnly === true;
		var elementorData = '';

		if (isElementorPostContent) {
			if (blocksPayload) {
				elementorData = blocksPayload;
			} else if (this.isElementorLayoutJson(value) || this.looksLikeElementorJson(value)) {
				elementorData = value;
				value = displayContent;
			}
		}

		if (this.isACFRepeaterCellType(type)) {
			var repeaterRows = this.parsePromptRepeaterRows(value);
			if (!repeaterRows) {
				this.g_doublyAdmin.showErrorMessage('Apply prompt did not return valid repeater data.');
				return;
			}

			this.savePromptRepeaterCellData($cell, repeaterRows, column, post_id, null, options);
			$container.data('value', value);
			$container.attr('data-value', value);
			jQuery(this.g_visualPart, $container).show();
			jQuery(this.g_editorPart, $container).hide();
			return;
		}

		if (type === 'text') {
			jQuery(this.g_editorPart + ' .text_editor_input', $container).val(value);
			jQuery(this.g_visualPart, $container).html(displayContent + manage);
			$container.data('value', value);
			$container.attr('data-value', value);
		} else if (type === 'acf_select' || type === 'select') {
			var $selectInput = jQuery(this.g_editorPart + ' .' + type + '_editor_input', $container);
			if (!$selectInput.length) {
				$selectInput = jQuery(this.g_editorPart + ' .editor_input', $container);
			}
			$selectInput.val(value).trigger('change');

			var selectedText = '';
			if ($selectInput.data('select2')) {
				var selectedData = $selectInput.select2('data');
				if (selectedData && selectedData[0] && typeof selectedData[0].text === 'string') {
					selectedText = selectedData[0].text;
				}
			}
			if (!selectedText) {
				selectedText = $selectInput.find('option:selected').text() || value;
			}

			var $singleTagBubble = jQuery('<div>', {
				class: this.g_singleTagBubbleNoPrefix,
				text: selectedText
			});
			jQuery(this.g_visualPart, $container).empty().append($singleTagBubble);
			$container.data('value', value);
			$container.attr('data-value', value);
		} else if (type === 'textarea' || type === 'calendar' || type === 'calendar') {
			jQuery(this.g_editorPart + ' .' + type + '_editor_input', $container).val(value);
			jQuery(this.g_visualPart, $container).html(displayContent);

			$container.data('value', value);
			$container.attr('data-value', value);
			if (elementorData) {
				$container.data('elementor-value', elementorData);
			} else if (isElementorPostContent) {
				$container.removeData('elementor-value');
			}

		} else if (type === 'taxonomy' /*|| type === 'tag' */ ) {

			
			var $splited_values = value.split(',');

			jQuery(this.g_visualPart + ' input[type="checkbox"]', $container).each(function( in_index, in_value ){
			
				if( jQuery(this).attr('data-slug')  ==  value || jQuery.inArray( jQuery(this).attr('data-slug'), $splited_values) !== -1 ){				
					jQuery(this).prop('checked', true).trigger('change');
				}
			})
			return;
		} else if (  type === 'tag' ) {

			var $splited_values = value.split(',');

			const $select = jQuery(this.g_editorPart + ' .' + type + '_editor_input', $container);

			let current_values = $select.val() || [];

			jQuery(this.g_editorPart + ' .' + type + '_editor_input option', $container).each(function(){
				if( jQuery(this).attr('data-slug') == value || jQuery.inArray( jQuery(this).attr('data-slug'), $splited_values) !== -1 ){
					current_values.push( jQuery(this).val() );
				}
			})

			
			jQuery(this.g_editorPart + ' .' + type + '_editor_input', $container).val( current_values ).trigger('change');
			jQuery(this.g_editorPart + ' .' + type + '_editor_input', $container).val( current_values ).trigger('change.cellAction');

			return;
		} else {
			jQuery(this.g_visualPart, $container).html(displayContent);
			$container.data('value', value);
			$container.attr('data-value', value);
			if (type === 'image' || column === 'post_image') {
				var $previewImg = jQuery(this.g_ubaiFeaturedImageUploader, $container).first();
				if (!$previewImg.length) {
					$previewImg = $container.find(this.g_visualPart + ' img').first();
				}
				if ($previewImg.length) {
					$previewImg.addClass('sp_hover_preview');
					jQuery(document).trigger('sp:image-preview:invalidate', [$previewImg]);
				}
			}
		}


		if (!visualOnly) {
			var contentSave = this.preparePostContentSaveFields($row, $container, column, value);
			if (blocksPayload) {
				if (isElementorPostContent) {
					contentSave.elementor_data = String(blocksPayload);
					contentSave.is_elementor = 1;
					contentSave.value = value || contentSave.value;
				}
			} else if (!contentSave.elementor_data && elementorData) {
				contentSave.elementor_data = String(elementorData);
				contentSave.is_elementor = 1;
				if (this.isElementorLayoutJson(contentSave.value) || this.looksLikeElementorJson(contentSave.value)) {
					contentSave.value = displayContent || '';
				}
			}
			var saveItem = {
				post_id: post_id,
				value: contentSave.value,
				column: column,
				type: type,
				cell_address: '.cell_' + post_id + '_' + column_id,
				is_elementor: contentSave.is_elementor,
			};
			if (contentSave.elementor_data) {
				saveItem.elementor_data = contentSave.elementor_data;
			}
			if (blocksPayload && displayContent) {
				saveItem.display_value = displayContent;
			}
			var data = [saveItem];
			var self = this;
			this.incrementApplyPromptLoadingForCell($cell, null);
			if (options && options.suppressAjaxLoader === true) {
				this.g_doublyAdmin.setAjaxLoaderID(function () { });
			} else {
				this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderNoIndex);
			}

			if( sheetspilot.editor.g_isLogOn == 1 ){
				console.log( data );
			}

			this.g_doublyAdmin.ajaxRequest('save_edited_posts', data, function (response) {
			}, function () {
				self.decrementApplyPromptLoadingForCell($cell, null);
			});
		}

		jQuery(this.g_visualPart, $container).show();
		jQuery(this.g_editorPart, $container).hide();
	}

	/**
	 * expand category adding container
	 */
	onExpandCategoryAddingContainerButtonClick(e) {
		var objClicked = jQuery(e.currentTarget);
		var objContainer = objClicked.next();
		var activeClass = 'is_active';
		var isActive = objContainer.hasClass(activeClass);

		if (isActive == true) {
			objContainer.removeClass(activeClass);
		} else {
			objContainer.addClass(activeClass);
		}
	}

	/**
	 * Get current table selection data
	 */
	getTableData() {
		const $selectedCell = this.$table.find('td.' + this.g_isActiveCellNoIndex).first();
		if (!$selectedCell.length) {
			return {
				isSelected: false
			};
		}

		const $row = $selectedCell.closest('tr');
		const $container = $selectedCell.find(this.g_editorContainer).first();
		let value = null;

		if ($container.length) {
			value = $container.data('value');
			if (typeof value === 'undefined') {
				value = $container.attr('data-value');
			}
		}

		if (value === null || value === '' || typeof value === 'undefined') {
			const $visual = $container.length ? $container.find(this.g_visualPart) : jQuery();
			if ($visual.length) {
				value = $visual.text();
			}
		}

		if ($container.length && $container.data('column') === 'post_content') {
			value = this.getPostContentValueForPrompt($container, value);
		}

		const cellType = $container.length ? $container.data('type') : null;

		if ($container.length && cellType === 'tag') {
			const $tagSel = $container.find(this.g_tagSelectEditorInput);
			if ($tagSel.length) {
				const tv = $tagSel.val();
				if (Array.isArray(tv)) {
					const ids = tv.filter(function (x) {
						return x !== null && x !== undefined && String(x).trim() !== '';
					});
					value = ids.length ? ids.join(',') : '';
				} else if (tv !== null && tv !== undefined && String(tv).trim() !== '') {
					value = String(tv);
				} else {
					value = '';
				}
			} else if (value && typeof value === 'object' && !Array.isArray(value) && Array.isArray(value.values)) {
				value = value.values.length ? value.values.join(',') : '';
			} else if (typeof value === 'string' && value.replace(/\u00a0/g, ' ').trim() === '[object Object]') {
				value = '';
			}
		}

		if ($container.length && cellType === 'taxonomy') {
			const $taxHidden = $container.find('.ubai_tax_value');
			if ($taxHidden.length && typeof $taxHidden.val() === 'string') {
				const parts = $taxHidden.val().split(',').map(function (s) {
					return String(s).trim();
				}).filter(Boolean);
				value = parts.join(',');
			} else {
				const checkedCount = $container.find('.category_cell_td_list input[type="checkbox"]:checked').length;
				value = checkedCount ? String(checkedCount) : '';
			}
		}

		let imageAttachmentId = '';
		let imageIsPlaceholder = false;

		if ($container.length && (cellType === 'image' || $container.data('column') === 'post_image')) {
			const $img = $container.find(this.g_ubaiFeaturedImageUploader).first();
			const $imgEl = $img.length ? $img : $container.find(this.g_visualPart + ' img').first();

			if ($imgEl.length) {
				const id = String($imgEl.attr('data-id') || '').trim();
				if (id && id !== '0') {
					imageAttachmentId = id;
				}
				const src = String($imgEl.attr('src') || '').toLowerCase();
				imageIsPlaceholder = $imgEl.hasClass(this.g_isPlaceholderNoIndex)
					|| src.indexOf('placeholder.png') !== -1;
			}

			const strVal = value === null || value === undefined ? '' : String(value);
			const trimmedVal = strVal.replace(/\u00a0/g, ' ').trim();
			if (trimmedVal === '') {
				if (imageAttachmentId) {
					value = imageAttachmentId;
				}
			} else if (/^\d+$/.test(trimmedVal) && trimmedVal !== '0') {
				imageAttachmentId = imageAttachmentId || trimmedVal;
				imageIsPlaceholder = false;
				value = trimmedVal;
			} else if (trimmedVal.indexOf('<img') !== -1) {
				const idMatch = trimmedVal.match(/data-id\s*=\s*["'](\d+)["']/i);
				if (idMatch && idMatch[1] && idMatch[1] !== '0') {
					imageAttachmentId = imageAttachmentId || idMatch[1];
				}
				if (trimmedVal.indexOf('is_placeholder') !== -1 || trimmedVal.toLowerCase().indexOf('placeholder.png') !== -1) {
					imageIsPlaceholder = true;
				} else if (imageAttachmentId) {
					imageIsPlaceholder = false;
				}
			}
		}

		const isElementorRow = $row.attr('data-is-elementor') === '1';

		const tableData = {
			isSelected: true,
			cellType: cellType,
			postId: $row.data('id') || $selectedCell.data('row') || null,
			column: $container.length ? $container.data('column') : null,
			columnIndex: $selectedCell.data('col') || null,
			value: value,
			is_elementor: isElementorRow
		};

		if ($container.length && (cellType === 'image' || $container.data('column') === 'post_image')) {
			tableData.imageAttachmentId = imageAttachmentId;
			tableData.imageIsPlaceholder = imageIsPlaceholder;
		}

		return tableData;
	}

	/**
	 * Move the active cell to the one described by an apply_prompt table snapshot (e.g. after AJAX if selection changed).
	 *
	 * @param {Object} table - Object like getTableData() with isSelected, postId, columnIndex, optional column.
	 * @return {boolean} True if a matching cell was found and selected.
	 */
	selectCellFromApplyPromptTable(table, options) {

		options = options || {};
		var suppressClose = !!options.suppressPromptDialogClose;
		if (suppressClose) {
			this.suppressPromptDialogCloseOnSelection = true;
		}
		try {
			var $td = this.getCellFromApplyPromptTable(table);
			if (!$td.length) {
				return false;
			}
			this.$table.find('td.' + this.g_isActiveCellNoIndex).removeClass(this.g_isActiveCellNoIndex);
			$td.addClass(this.g_isActiveCellNoIndex);
			this.scrollCellIntoView($td);
			this.triggerEvent(this.g_events.SELECTION_CHANGE);
			return true;
		} finally {
			if (suppressClose) {
				this.suppressPromptDialogCloseOnSelection = false;
			}
		}
	}

	/**
	 * Scroll a table cell into the visible area of the spreadsheet container,
	 * accounting for sticky header row and sticky left columns (ID, post title).
	 * Used before showing the prompt result dialog so the target cell is not
	 * hidden under fixed table chrome.
	 *
	 * @param {jQuery} $cell
	 * @param {Object} [options]
	 * @param {number} [options.extraTop] Extra top inset (e.g. dialog above the cell).
	 * @param {number} [options.extraBottom] Extra bottom inset (e.g. dialog below the cell).
	 */
	scrollCellIntoView($cell, options) {
		if (!$cell || !$cell.length) {
			return;
		}
		var el = $cell[0];
		if (!el || !el.isConnected) {
			return;
		}

		options = options || {};
		var extraTop = Number(options.extraTop) || 0;
		var extraBottom = Number(options.extraBottom) || 0;
		var gap = 4;
		var maxPasses = 6;

		var $container = this.$table.closest('.unlimitedai-plugin__table');
		if (!$container.length) {
			$container = jQuery('#spreadsheet_temporary');
		}
		if (!$container.length) {
			return;
		}
		var container = $container[0];

		var getObstructions = () => {
			var theadHeight = this.$tableHead.outerHeight() || 0;
			var stickyLeft = 0;
			this.$tableHead.find('th').slice(0, 3).each(function () {
				stickyLeft += jQuery(this).outerWidth(true);
			});
			return { theadHeight: theadHeight, stickyLeft: stickyLeft };
		};

		var nudgeIntoView = () => {
			var obstructions = getObstructions();
			var cellRect = el.getBoundingClientRect();
			var containerRect = container.getBoundingClientRect();
			var minVisibleTop = containerRect.top + obstructions.theadHeight + extraTop + gap;
			var minVisibleLeft = containerRect.left + obstructions.stickyLeft + gap;
			var maxVisibleBottom = containerRect.bottom - extraBottom - gap;
			var maxVisibleRight = containerRect.right - gap;
			var scrollTopDelta = 0;
			var scrollLeftDelta = 0;

			if (cellRect.top < minVisibleTop) {
				scrollTopDelta = cellRect.top - minVisibleTop;
			} else if (cellRect.bottom > maxVisibleBottom) {
				scrollTopDelta = cellRect.bottom - maxVisibleBottom;
			}

			if (cellRect.left < minVisibleLeft) {
				scrollLeftDelta = cellRect.left - minVisibleLeft;
			} else if (cellRect.right > maxVisibleRight) {
				scrollLeftDelta = cellRect.right - maxVisibleRight;
			}

			if (scrollTopDelta !== 0) {
				container.scrollTop += scrollTopDelta;
			}
			if (scrollLeftDelta !== 0) {
				container.scrollLeft += scrollLeftDelta;
			}

			return scrollTopDelta !== 0 || scrollLeftDelta !== 0;
		};

		for (var i = 0; i < maxPasses; i++) {
			if (!nudgeIntoView()) {
				break;
			}
		}
	}

	/**
	 * make category selector seledt2
	 */
	initCategorySelectorSelect2() {
		var self = this;
		var objCategorySelectors = jQuery(this.g_categorySelector);

		if (objCategorySelectors.length === 0) {
			console.warn("Select2: Target element not found:", this.g_categorySelector);
			return;
		}

		objCategorySelectors.each(function () {
			var objSelect = jQuery(this);
			var footerContainerSelector = ".category_editor-footer__container";
			var objContainer = jQuery(this).closest(footerContainerSelector);
			var objInput = objContainer.find(self.g_newTaxValue);
			var objButton = objContainer.find(self.g_newTaxAdd);

			const validate = () => {
				// Check if a parent is selected (not the placeholder value -1)
				var isSelectValid = objSelect.val() !== "-1" && objSelect.val() !== null;

				// Check if the name input has text (trimmed of whitespace)
				var isInputValid = objInput.val().trim() !== "";

				// Toggle the disabled attribute: 
				// If both are valid, disabled = false.
				//objButton.prop('disabled', !(isSelectValid && isInputValid));
			};

			objSelect.select2({
				width: '100%',
				placeholder: '— Parent Category —',
				selectionCssClass: "category_editor-footer__selector",
				dropdownCssClass: "category_editor-footer__selector-dropdown",
				// Custom renderer to handle the "—" and indentation
				templateResult: function (data) {

					if (!data.id)
						return data.text;

					// Check if the original option has a level class
					var level = 0;

					if (data.element && data.element.className) {
						var match = data.element.className.match(/level-(\d+)/);

						if (match)
							level = match[1];
					}

					// Create the element with a dash if it's a child
					var prefix = level > 0 ? '— ' : '';
					var $result = jQuery(
						'<span style="padding-left:' + (level * 15) + 'px">' + prefix + data.text.replace(/&nbsp;/g, '').trim() + '</span>'
					);
					return $result;
				}
			});

			// Listen for Select2 changes (select, unselect, or general change)
			objSelect.on('select2:select select2:unselect change', validate);

			// Listen for typing, deleting, or pasting in the text input
			objInput.on('input propertychange', validate);

			// Run once on load to ensure button matches initial state
			validate();
		});
	}

	/**
	 * Plain-text post_content for apply_prompt (strip Gutenberg/HTML; use grid preview when available).
	 *
	 * @param {jQuery} $container Editor container.
	 * @param {string} rawValue  Raw data-value from the cell.
	 * @return {string}
	 */
	getPostContentValueForPrompt($container, rawValue) {
		var $visual = $container.find(this.g_visualPart);
		if ($visual.length) {
			var visualText = $visual.text().replace(/\s+/g, ' ').trim();
			if (visualText) {
				return visualText;
			}
		}
		if (typeof rawValue === 'string' && rawValue.trim() !== '') {
			return this.stripHTMLContent(rawValue);
		}
		return (typeof rawValue === 'string') ? rawValue : '';
	}

	/**
	 * strip html content from string
	 */
	stripHTMLContent(html) {
		const $tmp = jQuery('<div>').html(html);

		$tmp.find('script, style, link, noscript').remove();

		$tmp.contents().each(function () {
			if (this.nodeType === 8) {
				jQuery(this).remove();
			}
		});

		return $tmp
			.text()
			.replace(/\s+/g, ' ')
			.trim();
	}

	/**
	 * set td class (single batch addClass instead of per-element loop)
	 */
	setClassInTD(innerTDSelector, className) {
		this.$table.find(innerTDSelector).closest('td').addClass(className);
	}

	/**
	 * Restore cell prev satete
	 */
	restoreCellPrevState(e) {
		let self = this;
		let objCurrent = jQuery(e.currentTarget);
		let parent_container = objCurrent.find(this.g_editorContainer);
		let type = objCurrent.find(this.g_editorContainer).data('type');
		jQuery(this.g_editorPart + ' .' + type + '_editor_input', parent_container).val(this.undoPrevCellValue);
		jQuery(this.g_visualPart, parent_container).show();
		jQuery(this.g_editorPart, parent_container).hide();
	}

	/**
	 * Drop cell content
	 */
	dropCellContent(e) {
	 
		let self = this;
		let objCurrent = jQuery(e.currentTarget);
		let parent_td = objCurrent.closest('td');
		
		let parent_container = objCurrent.find(this.g_editorContainer);
		let type = objCurrent.find(this.g_editorContainer).data('type');


		const row = parent_td.data('row');
		const col = parent_td.data('col');

	
		
		
		
		if ( type == 'text' || type == 'textarea' || type == 'calendar' || type == 'wysiwyg' ){

			self.undoPrevCellValue = jQuery(self.g_editorPart + ' ' + self.g_editorInput, objCurrent).val();
			self.undoStackArchive.push({ 'cell': '.cell_' + row + '_' + col, 'type': type, 'value': self.undoPrevCellValue });

			jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val('')
			jQuery(this.g_visualPart, objCurrent).html('');
			this.onCellContentSave( parent_container, true, true);
		}
		
		if( type == 'acf_select' || type == 'tag' ){
			jQuery(this.g_editorPart + ' .' + type + '_editor_input', objCurrent).val([]);
			this.onCellContentSave( parent_container, true, true);
		}
		 
	}




	closeFilterColumnDropdown() {
		var objOpenedColumnFilterDropdown = jQuery(`.${this.g_columnFilterDropdownClass}.${this.g_classActive}`);

		objOpenedColumnFilterDropdown.attr('aria-expanded', false);
		objOpenedColumnFilterDropdown.removeClass(this.g_classActive);
	}

	/**
	 * correct obj positioning
	 */
	positionObj(obj, width) {
		self = this;
		obj.each(function () {
			var objSingle = jQuery(this);
			var offset = objSingle.offset();
			var windowWidth = jQuery(window).width();

			// If the element is within 'width' of the right edge
			if (windowWidth - offset.left < width) {
				objSingle.addClass(self.edgeRightClass);
			} else {
				objSingle.removeClass(self.edgeRightClass);
			}
		});
	}

	/**
	 * click on filter column icon
	 */
	onFilterColumnBtnClick(e) {
		var self = this;
		var objTarget = jQuery(e.currentTarget);
		var objParentTH = objTarget.closest('th');
		var objFilterDropdown = objParentTH.find(`.${this.g_columnFilterDropdownClass}`);
		var isActive = objFilterDropdown.hasClass(this.g_classActive);



		const filtering_type = objParentTH.attr('data-column-search-type');

		if (filtering_type == 'filter' && jQuery(self.g_columnFilterDropdownFilterContainerClass, objParentTH).html() == '') {
			var this_col_index = objParentTH.index();
			var first_data_row = this.$table.find('tbody').find('tr').first();
			const td_editor_container = first_data_row.find('td').eq(this_col_index).find(this.g_editorContainer);
			const type = td_editor_container.attr('data-type');
			const column = td_editor_container.attr('data-column');

			objParentTH.attr('data-type', td_editor_container.attr('data-type'));


			var select_list = [];

			if (type == 'acf_select' || type == 'tag') {
				var editor_select = td_editor_container.find(this.g_editorInput);
				select_list = [];
				jQuery('option', editor_select).each(function (index, value) {
					select_list.push({ 'label': jQuery(this).text(), 'index': jQuery(this).attr('value'), 'text': jQuery(this).text() });
				})

			}
			if (type == 'taxonomy') {
				var editor_select = td_editor_container.find('.category_cell_td_list');
				select_list = [];
				jQuery('label', editor_select).each(function (index, value) {
					var $label = jQuery(this);
					var level = $label.parents('ul').length - 1;
					select_list.push({ 'label': jQuery(this).text(), 'index': jQuery('input[type="checkbox"]', this).attr('value'), 'text': jQuery(this).text(), 'level': level });
				})

			}
			if (objParentTH.attr('data-name') === 'elementor_active') {
				objParentTH.attr('data-type', 'switch');
				select_list = [];
				var elementorColDef = (self.$colData || []).find(function (item) {
					return item.name === 'elementor_active';
				});
				if (elementorColDef && elementorColDef.source) {
					jQuery.each(elementorColDef.source, function (index, option) {
						select_list.push({ 'label': option.name, 'index': option.id, 'text': option.name });
					});
				}
			}

			jQuery(self.g_columnFilterDropdownFilterContainerClass, objParentTH).empty();


			jQuery.each(select_list, function (index, value) {

				var div_cont = jQuery('<div>', {
					class: self.g_columnFilterDropdownFilterContainerItemClass + ' level_' + value.level
				});
				var label = jQuery('<label>', {
					text: value.text,
					class: self.g_columnFilterDropdownFilterContainerItemClass + '__label'
				});
				var single_input = jQuery('<input>', {
					value: value.index,
					type: 'checkbox',
					checked: true
				});
				label.prepend(single_input);
				div_cont.append(label);


				jQuery(self.g_columnFilterDropdownFilterContainerClass, objParentTH).append(div_cont);
			})
		}
		if (filtering_type == 'text') {
			var this_col_index = objParentTH.index();
			var first_data_row = this.$table.find('tbody').find('tr').first();
			const td_editor_container = first_data_row.find('td').eq(this_col_index).find(this.g_editorContainer);
			const type = td_editor_container.attr('data-type');
			const column = td_editor_container.attr('data-column');

			objParentTH.attr('data-type', td_editor_container.attr('data-type'));
		}

		this.closeFilterColumnDropdown();

		objFilterDropdown.attr('aria-expanded', true);
		objFilterDropdown.addClass(this.g_classActive);
	}

	/*
	* on general bulk edit checkbox change
	*/
	onGeneralCheckboxBulkEditChange() {
		var isGeneralChecked = jQuery(this.g_bulkEditTHCheckboxSelector).is(":checked");

		jQuery(this.g_bulkEditTDCheckboxSelector).prop('checked', isGeneralChecked);

		var objAllCheckboxes = jQuery(this.g_bulkEditTDCheckboxSelector);
		var objChecked = jQuery(this.g_bulkEditTDCheckboxSelector + ':checked');

		if (isGeneralChecked == true) {
			jQuery(this.g_bulkEditDropdownSelector).addClass(this.g_classActive);

			//update bulk edit checkboxes count in ui
			var checkedCount = objChecked.length;

			jQuery(this.g_bulkEditSelectedCheckboxesCount).text(checkedCount);
			objChecked.closest('tr').addClass(this.g_bulkEditSelectedClass);
		} else {
			jQuery(this.g_bulkEditDropdownSelector).removeClass(this.g_classActive);
			objAllCheckboxes.closest('tr').removeClass(this.g_bulkEditSelectedClass);
		}
	}

	/**
	 * on bulk edit checkbox change
	 */
	onCheckboxBulkEditChange(e) {

		var checkbox_pointer = e.target;
		var objAllCheckboxes = jQuery(this.g_bulkEditTDCheckboxSelector);
		var objChecked = jQuery(this.g_bulkEditTDCheckboxSelector + ':checked');
		var checkedCount = objChecked.length;

		//style selected rows
		objAllCheckboxes.closest('tr').removeClass(this.g_bulkEditSelectedClass);
		objChecked.closest('tr').addClass(this.g_bulkEditSelectedClass);

		// like shift select
		let $checkboxes = this.$table.find('input.unlimitedai-plugin__td-bulk-edit__select-input');

		// if (!this.lastBulkChecked) {
		// 	this.lastBulkChecked = checkbox_pointer;
		// 	return;
		// }

		if (e.shiftKey) {
			let start = $checkboxes.index(checkbox_pointer);
			let end = $checkboxes.index(this.lastBulkChecked);
			let from = Math.min(start, end);
			let to = Math.max(start, end);

			$checkboxes.slice(from, to + 1)
				.prop('checked', this.lastBulkChecked.checked);
		}

		this.lastBulkChecked = checkbox_pointer;

		//update bulk edit checkboxes count in ui
		jQuery(this.g_bulkEditSelectedCheckboxesCount).text(checkedCount);

		// like shift select END

		if (checkedCount > 0) {
			// If 1 or more are checked, show the dropdown
			jQuery(this.g_bulkEditDropdownSelector).addClass(this.g_classActive);
		} else {
			// If 0 are checked, hide it
			jQuery(this.g_bulkEditDropdownSelector).removeClass(this.g_classActive);
		}

	}

	/**
	 * on tooltip mouse hover in header
	 */
	onTooltipMouseover(e) {
		var objTarget = jQuery(e.currentTarget);

		var objTarget = jQuery(e.currentTarget);
		var tooltipText = objTarget.attr('data-title');

		if (!tooltipText)
			return;

		if (jQuery('#uai-global-tooltip').length > 0) {
			return;
		}

		var globalTooltip = jQuery('<div id="uai-global-tooltip"></div>').appendTo('body');

		globalTooltip.text(tooltipText);

		var updatePos = () => {
			var rect = objTarget[0].getBoundingClientRect();
			var tooltipWidth = globalTooltip.outerWidth();
			var viewportWidth = jQuery(window).width();
			var margin = 10; // Minimum distance from screen edge

			// Calculate the centered position
			var leftPos = rect.left + (rect.width / 2);

			// 1. Constraint for Right Edge
			if (leftPos + (tooltipWidth / 2) > viewportWidth - margin) {
				leftPos = viewportWidth - (tooltipWidth / 2) - margin;
			}

			// 2. Constraint for Left Edge
			if (leftPos - (tooltipWidth / 2) < margin) {
				leftPos = (tooltipWidth / 2) + margin;
			}

			globalTooltip.css({
				top: (rect.top - 8) + 'px',
				left: leftPos + 'px'
			});
		};

		updatePos();

		setTimeout(() => {
			globalTooltip.addClass('is-active');
		}, 10);

		// 4. Smooth Removal
		objTarget.one('mouseleave', function () {
			globalTooltip.removeClass('is-active');

			// Wait for the .15s CSS transition to finish before deleting from DOM
			setTimeout(() => {
				globalTooltip.remove();
			}, 10);
		});

	}

	/**
	 * close category edito
	 */
	closeCategoryEditor(objOpenEditor) {
		objOpenEditor.hide();
	}

	/*
	* close category editor
	*/
	closeCategoryEditorOnCloseBtnClick(e) {
		var objClicked = jQuery(e.currentTarget);

		var objOpenEditor = objClicked.closest(this.g_categoryEditor);

		if (objOpenEditor && objOpenEditor.length) {
			this.closeCategoryEditor(objOpenEditor);
		}
	}

	/*
	* reset quick search btn click
	*/
	onResetTaxQuickSearchBtnClick(e) {
		var objClicked = jQuery(e.currentTarget);
		var objInput = objClicked.prev('input');

		if (objInput.length) {
			objInput.val('');

			var parent_editor = objClicked.parents(this.g_categoryEditor);
			parent_editor.removeClass('is_tax_searching');
			jQuery('.category_cell_td_list li label', parent_editor).each(function () {
				jQuery(this).show();
			})
		}
	}
}
