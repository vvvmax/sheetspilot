class SheetsPilot_Drawer {

	constructor() {

		// classes
		this.g_doublyAdmin = new UniteAdminSheetsPilot();
		this.g_postEditorView = objPostsEditorView;

		this.g_cellProcessingObj = g_cellProcessingObj;


		// selectors
		this.g_objPostsEditor = jQuery("#unlimitedai-plugin");

		this.g_bodySelector = 'body';

		this.g_pluginSideDrawer = ".unlimitedai-plugin__side-drawer";
		this.g_pluginSideDrawerBody = ".unlimitedai-plugin__side-drawer__body";
		this.g_pluginSideDrawerHeader = ".unlimitedai-plugin__side-drawer__header";
		this.g_pluginSideDrawerTitle = ".unlimitedai-plugin__side-drawer__header-title";
		this.g_pluginSideDrawerFooter = ".unlimitedai-plugin__side-drawer__footer";

		this.g_loaderProcessingIcon = `<span class="unlimitedai-plugin_drawer_saving d-flex" id="unlimitedai-plugin_drawer_saving"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span></span>`;



		//this.g_applyDrawerAction = '.edit_post_modal';
		this.g_drawerSaveLoader = '.unlimitedai-plugin_drawer_saving';
		this.g_drawerSaveLoaderNoIndex = 'unlimitedai-plugin_drawer_saving';

		this.g_editInNewWindow = '.edit_in_new_window';
		this.g_editorContainerNoPrefix = 'editor_container';
		this.g_categoryEditor = '.category_editor';

		this.g_drawerBulkEditClass = "unlimitedai-plugin__drawer-bulk-edit";
		this.g_drawerManageStockClass = "unlimitedai-plugin__drawer-manage-stock";
		this.g_drawerDownloadableEditClass = "unlimitedai-plugin__drawer-downloadable_edit";
		this.g_drawerDownloadableEditClassGrouped = "unlimitedai-plugin__drawer-downloadable_edit-grouped";
		this.g_drawerDownloadableEditClassExternal = "unlimitedai-plugin__drawer-downloadable_edit-external";
		this.g_drawerDownloadableEditClassVariable = "unlimitedai-plugin__drawer-downloadable_edit-variable";
		this.g_drawerDownloadableEditClassRepeater = "unlimitedai-plugin__drawer-downloadable_edit-repeater";
		this.g_drawerDownloadableEditClassRepeaterAddBtn = "unlimitedai-plugin__drawer-downloadable_edit-repeater-add-btn";
		this.g_drawerDownloadableEditClassAttributes = "unlimitedai-plugin__drawer-downloadable_edit-attributes";
		this.g_drawerGeneralSettingsClass = "unlimitedai-plugin__drawer-general_settings";
		this.g_drawerBulkEditSelectClass = "bulk_select_block";
		this.listOfTitleClass = "list_of_titles";

		this.g_imageEditingClass = 'unlimitedai-plugin__drawer-image-edit'

		//this.g_applyDrawerAction = '#apply_drawer_action';

		this.g_objDrawer = this.g_objPostsEditor.find(".unlimitedai-plugin__side-drawer");
		this.g_objDrawerOverlay = this.g_objPostsEditor.find(".unlimitedai-plugin__overlay");
		this.g_objDrawerCloseBtn = this.g_objPostsEditor.find(".unlimitedai-plugin__side-drawer__header-close-btn");
		this.g_objOpenDrawer = this.g_objPostsEditor.find(".unlimitedai-plugin__side-drawer__open-btn");
		this.g_isOpenClass = "is-open";
		this.g_isOpeningClass = "is-opening";
		this.g_drawerLoaderClass = "unlimitedai-plugin__side-drawer__loader";
		this.g_drawerLoaderIconClass = "unlimitedai-plugin__side-drawer__loader-icon";
		this.g_drawerLoaderTextClass = "unlimitedai-plugin__side-drawer__loader-text";
		this.g_objBulkEditSelect = this.g_objPostsEditor.find(".unlimitedai-plugin__bulk-edit__select");


		this.g_inlineEditFeaturedImage = ".inline_edit_image_field";
		this.openWooManageStock = '.sheetpilot_open_plugins__manage_stock';
		this.openWooEditDownloadable = '.sheetpilot_open_plugins__downloadable';

		this.g_sideDraweInputContainerNoIndex = 'unlimitedai-plugin__side_drawer_input_container';

		this.g_sideDraweRow = 'unlimitedai-plugin__side_drawer_row';
		this.g_isFlex = 'is_flex';
		this.g_flex25 = 'flex_25';
		this.g_flex50 = 'flex_50';
		this.g_flex75 = 'flex_75';
		this.g_flex1 = 'flex_1';

		this.g_sideDrawerNumericalContainerNoIndex = 'unlimitedai-plugin__side_drawer_numerical_container';
		this.g_sideDraweInputImagePreviewNoIndex = 'unlimitedai-plugin__side_image_preview';
		this.g_sideDraweInputImagePreviewWrapNoIndex = 'unlimitedai-plugin__side_image_preview_wrap';
		this.g_sideDraweInputTitleNoIndex = 'unlimitedai-plugin__side_drawer_input_title';
		this.g_sideDraweInputHeadingNoIndex = 'unlimitedai-plugin__side_drawer_input_heading';
		this.g_sideDraweInputSubtitleNoIndex = 'unlimitedai-plugin__side_drawer_input_subtitle';
		this.g_sideDraweInputElementNoIndex = 'unlimitedai-plugin__side_drawer_input_element';
		this.g_sideDraweInputElementModifierNoIndex = 'unlimitedai-plugin__side_drawer_input_modifier';
		this.g_sideDraweInputElementTextareaNoIndex = 'unlimitedai-plugin__side_drawer_textarea_element';
		this.g_sideDraweInputCopyClipboardNoIndex = 'unlimitedai-plugin__side_drawer_copy_clipboard_element';
		this.g_unlimitedai__pluginBtnClass = "unlimitedai-plugin__btn";
		this.g_unlimitedai__pluginBtnIconClass = "unlimitedai-plugin__btn";

		this.fileUploaderPlaceholder = "drawer_file_uploader_placeholder";
		this.fileUploaderPlaceholderImage = "drawer_file_uploader_placeholder_image";
		this.fileUploaderPlaceholderH1 = "drawer_file_uploader_placeholder_h1";
		this.fileUploaderPlaceholderH2 = "drawer_file_uploader_placeholder_h2";
		this.fileUploaderPlaceholderH2 = "drawer_file_uploader_placeholder_h2";
		this.g_sideDraweAddFileButton = "drawer_add_file_button";

		this.g_singleImageUploadBlock = "single_image_upload_block";
		this.g_singleImageFieldLabel = "single_image_filed_label";
		this.g_singleImageFieldInput = "single_image_filed_input";
		this.g_drawerSectionTitleExtraRight = "drawer_section_extra_right";
		this.g_drawerFlexSpaceBetween = "flex_space_between";
		this.g_drawerFileCounterOutput = "file_counter_output";

		this.g_sideDraweFileListingContainer = "file_listing_container";
		this.drawerSingleFileBlockRemove = "single_file_block_remove";

		this.g_singleImageUploadFileName = "single_file_upload_file_name";
		this.g_singleImageUploadFileURL = "single_file_upload_file_url";


		this.g_incellRelationEditorNoIndex = 'incell_relation_editor';
		this.g_incellRelationEditor = '.' + this.g_incellRelationEditorNoIndex;

		this.g_groupedProductsSingleTabNoIndex = 'grouped_products_single_tab';
		this.g_groupedProductsSingleTab = '.' + this.g_groupedProductsSingleTabNoIndex;

		this.g_groupedSingleProductTabNoIndex = 'single_product_tab';
		this.g_groupedSingleProductTab = '.' + this.g_groupedSingleProductTabNoIndex;

		this.g_groupedSearchItemInputNoIndex = 'grouped_product_search_item_input';
		this.g_groupedSearchItemInput = '.' + this.g_groupedSearchItemInputNoIndex;

		this.g_groupedSearchResultsSingleItemNoIndex = 'grouped_product_search_results_single_item';
		this.g_groupedSearchResultsSingleItem = '.' + this.g_groupedSearchResultsSingleItemNoIndex;

		this.g_groupedTableBodyCellDeleteNoIndex = 'table_body_cell_delete';
		this.g_groupedTableBodyCellDelete = '.' + this.g_groupedTableBodyCellDeleteNoIndex;

		this.listOfTitlesInputNoIndex = 'list_of_titles_input';

		this.g_editWysiwygField = ".edit_wysiwyg_field";
		this.g_editWysiwygFieldNoIndex = "edit_wysiwyg_field";

		// past list of titles
		this.g_pasteListOfTitlesNoIndex = "unlimitedai-plugin__paste_list";

		// objects
		this.objPluginSideDrawer = jQuery(this.g_pluginSideDrawer);


		// variables
		this.productSearchDebounce = false;
	}

	initEvents() {
		var self = this;

		this.g_objDrawerCloseBtn.on("click", function () {
			self.onDrawerClose()
		});
		this.g_objDrawerOverlay.on("click", function () {
			self.onDrawerClose()
		});

		// inline edit featured image
		this.g_objPostsEditor.on("click", this.g_inlineEditFeaturedImage, (e) => {
			this.inlineModifyFeaturedImage(e);
		});

		// inline edit stock editor
		this.g_objPostsEditor.on("click", this.openWooManageStock, (e) => {
			this.openWooManageStockFn(e);
		});

		// inline edit downloadable
		this.g_objPostsEditor.on("click", this.openWooEditDownloadable, (e) => {
			this.openWooEditDownloadableFn(e);
		});

		// inline edit downloadable
		this.objPluginSideDrawer.on("click", '.' + this.g_sideDraweAddFileButton, (e) => {
			this.addSingleFileUploadFiled(e);
		});

		// remove single file in drawer
		this.objPluginSideDrawer.on("click", '.' + this.drawerSingleFileBlockRemove, (e) => {
			this.removeSingleuploadFileBlock(e);
		});

		// acf_select related External on click
		this.g_objPostsEditor.on("click", this.g_incellRelationEditor + '_external', (e) => {
			this.openPostTypeRelationsExternalEditor(e);
		});

		// acf_select related Grouped on click
		this.g_objPostsEditor.on("click", this.g_incellRelationEditor + '_grouped', (e) => {
			this.openPostTypeRelationsGroupedEditor(e);
		});
		// acf_select related Variable on click
		this.g_objPostsEditor.on("click", this.g_incellRelationEditor + '_variable', (e) => {
			this.openPostTypeRelationsVariableEditor(e);
		});

		// acf_select grouped products tabs switch
		this.objPluginSideDrawer.on("click", this.g_groupedProductsSingleTab, (e) => {
			this.onGroupedTypeTabClick(e);
		});

		// on grouped products search
		this.objPluginSideDrawer.on("input", this.g_groupedSearchItemInput, (e) => {
			var self = this;
			clearTimeout(this.productSearchDebounce);
			this.productSearchDebounce = setTimeout(function () {
				self.onGroupedProductSearch(e);
			}, 400);

		});

		// on grouped products item click
		this.objPluginSideDrawer.on("click", this.g_groupedSearchResultsSingleItem, function (e) {
			self.selectGroupedSingleItem(jQuery(this));
		});

		// on grouped delete item click
		this.objPluginSideDrawer.on("click", this.g_groupedTableBodyCellDelete, function (e) {
			self.selectGroupedDeleteSingleItem(jQuery(this));
		});

		// showsettings
		jQuery(document).on("click", '.ubai_content_rules_trigger', function (e) {
			self.openSettingsEditor();
		});

		// new acf editor
		jQuery(document).on("click", '.new_edit_repeater_field', function (e) {
			self.editACFRepeater(jQuery(e.target));
		});

		// edit wyswig
		jQuery(document).on('click', this.g_editWysiwygField, (e) => {
			this.editWYSIWYGField(jQuery(e.target));
		});

		// paste lsit of titles
		jQuery(document).on('click', '.' + this.g_pasteListOfTitlesNoIndex, (e) => {

			this.pasteListOfTitles(jQuery(e.target));
		});

	}



	// grouped select single item
	selectGroupedDeleteSingleItem(current_element) {
		var parent_row = current_element.parents('.table_body_row');
		var post_id = parent_row.attr('data-id');
		var item_type = jQuery('.grouped_products_single_tab.is_active').attr('data-type');

		parent_row.replaceWith('');
		jQuery('.grouped_product_search_results_container .search_result_' + post_id).show();
		var recalc_result = this.recalcualteUpsellAndGroupedCounters();

		if (recalc_result[item_type] == 0) {
			jQuery('.single_product_tab.' + item_type + '_contnet_tab').html(sheetspilot.editor.no_products_added_yet);
		}
	}

	// grouped select single item
	selectGroupedSingleItem(current_element) {
		current_element.hide();
		var product_id = current_element.attr('data-id');
		var product_image = jQuery(this.g_groupedSearchResultsSingleItem + '_image ', current_element).attr('data-image');
		var product_title = jQuery(this.g_groupedSearchResultsSingleItem + '_title ', current_element).attr('data-title');
		var product_price = jQuery(this.g_groupedSearchResultsSingleItem + '_price ', current_element).attr('data-price');
		var data_row = this.generateGroupedProductsTableItem(product_id, product_image, product_title, product_price);

		//get active cell
		var item_type = jQuery('.grouped_products_single_tab.is_active').attr('data-type');
		var arcive_tab = jQuery('.single_product_tab.' + item_type + '_contnet_tab');

		// check if needed table
		if (jQuery('.table_header_row', arcive_tab).length == 0) {
			var tab_top = this.generateGroupedProductsTableHeaderRow();
			arcive_tab.empty();
			arcive_tab.append(tab_top);
		}

		arcive_tab.append(data_row);
		this.recalcualteUpsellAndGroupedCounters();
	}

	// on grouped product search
	onGroupedProductSearch(e) {
		var self = this;
		var this_pnt = jQuery(e.target);
		var this_val = this_pnt.val();
		jQuery('.drawer_grouped_search_loader').show();
		var data =
		{
			search_query: this_val,
		}
		this.g_doublyAdmin.ajaxRequest('search_products_action', data, function (response) {
			jQuery('.drawer_grouped_search_loader').hide();


			jQuery('.grouped_product_search_results').empty();

			var search_input_results_container = jQuery('<div>', {
				class: 'grouped_product_search_results_container',
			});

			// if empty

			if (response.message.postslist.length == 0) {
				jQuery('.grouped_product_search_results_container').html(sheetspilot.editor.no_posts_found);
				return true;
			}

			jQuery.each(response.message.postslist, function (index, value) {

				var item = value.find(obj => obj.id !== undefined);
				var post_id = item ? item.id : null;

				var search_input_results_single_item = jQuery('<div>', {
					class: `${self.g_groupedSearchResultsSingleItemNoIndex} search_result_${post_id}`,
					'data-id': post_id
				});

				var item = value.find(obj => obj.post_image !== undefined);
				var html = item ? item.post_image : null;
				var src = jQuery(html).attr('src');

				var search_input_results_single_item_image = jQuery('<div>', {
					class: `${self.g_groupedSearchResultsSingleItemNoIndex}_image`,
					'data-image': src
				});
				var search_input_results_single_item_image_out = jQuery('<div>', {
					class: `${self.g_groupedSearchResultsSingleItemNoIndex}_image_out`,
					css: {
						'background-image': 'url(' + src + ')',
						'background-size': 'cover',
						'background-position': 'center center',
					},

				});
				search_input_results_single_item_image.append(search_input_results_single_item_image_out);

				item = value.find(obj => obj.post_title !== undefined);
				var search_input_results_single_item_title = jQuery('<div>', {
					class: `${self.g_groupedSearchResultsSingleItemNoIndex}_title`,
					html: item ? item.post_title : null,
					'data-title': item ? item.post_title : null,
				});

				item = value.find(obj => obj.plugins__price !== undefined);
				var search_input_results_single_item_price = jQuery('<div>', {
					class: `${self.g_groupedSearchResultsSingleItemNoIndex}_price`,
					html: item ? item.plugins__price : null,
					'data-price': item ? item.plugins__price : null,
				});
				search_input_results_single_item.append(
					search_input_results_single_item_image,
					search_input_results_single_item_title,
					search_input_results_single_item_price,

				);
				search_input_results_container.append(search_input_results_single_item);
			})
			jQuery('.grouped_product_search_results').append(search_input_results_container);
		})
	}

	// on grouped tab type change
	onGroupedTypeTabClick(e) {
		var current_tab = jQuery(e.target);
		var tab_type = current_tab.attr('data-type');

		if (!current_tab.hasClass(this.g_groupedProductsSingleTabNoIndex)) {
			var tab_type = current_tab.parents(this.g_groupedProductsSingleTab).attr('data-type');
			current_tab = jQuery(e.target).parents(this.g_groupedProductsSingleTab);
		}

		jQuery(this.g_groupedProductsSingleTab).removeClass('is_active');
		jQuery(this.g_groupedSingleProductTab).removeClass('is_active');
		current_tab.addClass('is_active');

		if (tab_type == 'upsell') {
			jQuery('.upsell_contnet_tab ').addClass('is_active');
		}
		if (tab_type == 'grouped') {
			jQuery('.grouped_contnet_tab ').addClass('is_active');
		}

		// drop search 
		jQuery('.grouped_product_search_results').empty();
		jQuery(this.g_groupedSearchItemInput).val('');

		this.recalcualteUpsellAndGroupedCounters();
	}

	// remove single load 
	removeSingleuploadFileBlock(e) {
		var parent = jQuery(e.target).parents('.' + this.g_sideDraweInputContainerNoIndex);
		parent.replaceWith('');

		var current_count = jQuery('.' + this.g_singleImageUploadBlock, this.objPluginSideDrawer).length;

		if (current_count == 1) {
			var counter_line = sheetspilot.editor.file_single;
		} else {
			var counter_line = sheetspilot.editor.file_multi;
		}

		jQuery('.' + this.g_drawerFileCounterOutput, this.objPluginSideDrawer).html(current_count + ' ' + counter_line);

		this.recalculateFileInputCounters();

	}

	// add single fiel upload field
	addSingleFileUploadFiled() {
		var current_count = jQuery('.' + this.g_singleImageUploadBlock, this.objPluginSideDrawer).length;
		current_count++;



		if (current_count == 1) {
			var counter_line = sheetspilot.editor.file_single;
		} else {
			var counter_line = sheetspilot.editor.file_multi;
		}


		jQuery('.' + this.g_drawerFileCounterOutput, this.objPluginSideDrawer).html(current_count + ' ' + counter_line);
		this.appendSingleFileUploadBlock();
		this.recalculateFileInputCounters();
	}

	// init end functions netx
	showLoader() {
		this.g_objDrawer.addClass(this.g_isOpeningClass);

		var loaderIconUrl = (typeof sheetspilot !== 'undefined' && sheetspilot.editor && sheetspilot.editor.drawer_loader_icon_url)
			? sheetspilot.editor.drawer_loader_icon_url
			: '';
		var loaderIcon = loaderIconUrl
			? '<img src="' + loaderIconUrl + '" width="32" height="32" alt="" aria-hidden="true" />'
			: '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';

		var loaderHTML = `
      <div class="${this.g_drawerLoaderClass}">
        <span class="${this.g_drawerLoaderIconClass}">${loaderIcon}</span>
        <span class="${this.g_drawerLoaderTextClass}">${sheetspilot.editor.loading}</span>
      </div>
    `;

		jQuery(this.g_pluginSideDrawerBody).html(loaderHTML);
	}

	hideLoader() {
		this.g_objDrawer.removeClass(this.g_isOpeningClass);
	}

	onDrawerOpen() {
		this.g_objDrawerOverlay.fadeIn(500);
		this.g_objDrawer.addClass(this.g_isOpenClass);
		this.hideBodyScroll();
	}

	/** Hide body scroll */
	hideBodyScroll() {
		jQuery(this.g_bodySelector).addClass('overflow_hidden');
	}

	onDrawerClose() {
		var self = this;
		this.g_objDrawerOverlay.fadeOut(500);
		this.g_objDrawer.removeClass(this.g_isOpenClass);
		jQuery(this.g_pluginSideDrawerTitle).html('');
		jQuery(this.g_pluginSideDrawerBody).html('');
		jQuery(this.g_pluginSideDrawerFooter).html('');
		this.g_objBulkEditSelect.val('').trigger('change');

		//remove bulk edit class with timeout to allow animation to finish
		setTimeout(function () {
			self.g_objDrawer.removeClass(self.g_drawerBulkEditClass);
			self.g_objDrawer.removeClass(self.g_drawerManageStockClass);
			self.g_objDrawer.removeClass(self.g_imageEditingClass);
			self.g_objDrawer.removeClass(self.g_drawerDownloadableEditClass);
			self.g_objDrawer.removeClass(self.g_drawerDownloadableEditClassGrouped);
			self.g_objDrawer.removeClass(self.g_drawerDownloadableEditClassVariable);
			self.g_objDrawer.removeClass(self.g_drawerDownloadableEditClassExternal);
			self.g_objDrawer.removeClass(self.g_drawerDownloadableEditClassRepeater);
			self.g_objDrawer.removeClass(self.g_drawerDownloadableEditClassAttributes);
			self.g_objDrawer.removeClass(self.g_drawerGeneralSettingsClass);
			self.g_objDrawer.removeClass(self.listOfTitleClass);

		}, 500);
	}

	setDrawerTitle($title) {
		jQuery(this.g_pluginSideDrawerTitle).html($title);
	}
	appendDrawerTitle($title) {
		jQuery(this.g_pluginSideDrawerTitle).append($title);
	}
	appendDrawerBody($body_contnet) {
		jQuery(this.g_pluginSideDrawerBody).append($body_contnet);
	}
	prependDrawerBody($body_contnet) {
		jQuery(this.g_pluginSideDrawerBody).prepend($body_contnet);
	}
	setDrawerBody($body_contnet) {
		jQuery(this.g_pluginSideDrawerBody).append($body_contnet);
	}
	setDrawerFooter($footer_contnet) {
		jQuery(this.g_pluginSideDrawerFooter).html($footer_contnet);
	}
	appendDrawerFooter($footer_contnet) {
		jQuery(this.g_pluginSideDrawerFooter).append($footer_contnet);
	}
	dropDrawerBody($footer_contnet) {
		jQuery(this.g_pluginSideDrawerBody).empty();
	}
	setDrawerWidth(drawer_width) {
		jQuery(this.g_pluginSideDrawer).css('width', drawer_width + '%');
	}

	// Drawer data processing below

	// WooManageStock action
	// edit post type VARIABLE relations 
	pasteListOfTitles(e) {

		var self = this;
		self.g_objDrawer.addClass(self.listOfTitleClass);
		self.dropDrawerBody();
		self.onDrawerOpen();
		var title_image = `
			<span class="drawer_icon_container d-flex"><svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 12H3"></path><path d="M16 6H3"></path><path d="M16 18H3"></path><path d="M18 9v6"></path><path d="M21 12h-6"></path></svg></span>
		`;
		self.generateCustomDrawerImageTitle( sheetspilot.editor.paste_list, sheetspilot.editor.paste_list_subtext, title_image );

		var cancel_edition_button = jQuery('<button>', {
			class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} `,
			text: sheetspilot.editor.cancel,
			id: 'cancel_drawer_edition',
			click: function () {
				self.onDrawerClose();
			}
		});
		self.appendDrawerFooter(cancel_edition_button);

		var container = jQuery('<div>', {
			class: self.g_sideDraweInputContainerNoIndex,

		});
		var input_title = jQuery('<label>', {
			class: self.g_sideDraweInputTitleNoIndex,
			text: sheetspilot.editor.list_of_titles,

		});
		var input_subtitle = jQuery('<p>', {
			class: self.g_sideDraweInputSubtitleNoIndex+' paste_titles_list_subtitle',
			html: '<span id="post_titles_counter">0</span>'+sheetspilot.editor.list_titles_posts_created_text, 
		});
		var input = jQuery('<textarea>', {
			class: self.g_sideDraweInputElementTextareaNoIndex,
			placeholder: sheetspilot.editor.list_titles_placeholder,
			id: self.listOfTitlesInputNoIndex,
			rows: 11,
			css: {
				'height': 'auto'
			}

		});
		container.append(input_title, input,input_subtitle);
		self.appendDrawerBody(container);

		// add tracing of titles number
		jQuery(document).on('keyup', '#'+self.listOfTitlesInputNoIndex, function(){
			var list_names = jQuery(this).val().split("\n");
 
			list_names = jQuery.grep(list_names, function(n) {
				return n !== "" && n != null;
			});
	 
			jQuery('#post_titles_counter').html( list_names.length );
		})


		var drawer_bottom_buttons = [
			{ 'id': 'save_drawer_edition', 'text': sheetspilot.editor.save, 'class': `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass}`, 'have_close': false },
			{ 'id': 'save_and_close_drawer_edition', 'text': sheetspilot.editor.save_and_close, 'class': `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} ${self.g_cellProcessingObj.g_generalBtnTextAccentClass}`, 'have_close': true },
		];

		jQuery.each(drawer_bottom_buttons, function (index, value) {
			self.appendDrawerFooter(
				jQuery('<button>', {
					class: value.class,
					text: value.text,
					id: value.id,
					click: function () {
						var button_self = jQuery(this);
						button_self.append(self.g_loaderProcessingIcon);

						var list_names = jQuery('#'+self.listOfTitlesInputNoIndex).val().split("\n");
 
						list_names = jQuery.grep(list_names, function(n) {
							return n !== "" && n != null;
						});
						g_ajaxRunningFlag = true;
						objPostsEditorView.geneatePostsByTitleActions( list_names );

						//button_self.html(sheetspilot.editor.save);

						var intervalAjax = setInterval(function(){
							if( !g_ajaxRunningFlag  ){
								if (value.have_close) {
									self.onDrawerClose();
									clearInterval( intervalAjax );
								}
							}
						},500)
						

					}
				})
			);
		})
		self.hideLoader();


	}


	// WooManageStock action
	openWooManageStockFn(e) {
		var self = this;
		self.g_objDrawer.addClass(self.g_drawerManageStockClass);
		var custom_drawer_title = '';


		var clicked = jQuery(e.target);
		var parent_td = clicked.parents('td');
		var parent_tr = clicked.parents('tr');



		var post_id = parent_tr.attr('data-id');

		var post_data = [];

		post_data.push({ 'data_table': 'postmeta', 'name': '_stock' })
		post_data.push({ 'data_table': 'postmeta', 'name': '_low_stock_amount' })

		self.onDrawerOpen();
		self.showLoader();
		var data =
		{
			post_id: post_id,
			get_post_data: post_data
		}

		this.g_doublyAdmin.ajaxRequest('get_post_multidata', data, function (response) {
			self.dropDrawerBody();

			self.generateDrawerImageTitle(sheetspilot.editor.stock_management_settings, parent_tr);

			var cancel_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} `,
				text: sheetspilot.editor.cancel,
				id: 'cancel_drawer_edition',
				click: function () {
					self.onDrawerClose();
				}
			});
			var save_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass}`,
				text: sheetspilot.editor.save,
				id: 'save_drawer_edition',
				click: function () {
					var button_self = jQuery(this);
					button_self.append(self.g_loaderProcessingIcon);
					var input_info = [];
					input_info.push({ 'data_table': 'postmeta', 'name': '_stock', 'value': jQuery('input[data-name="_stock"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'postmeta', 'name': '_low_stock_amount', 'value': jQuery('input[data-name="_low_stock_amount"]', self.g_pluginSideDrawerBody).val() });

					var data =
					{
						post_id: post_id,
						post_data: input_info
					};
	
					self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {
						button_self.html('Save');
					});

				}
			});
			var save_and_close_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} ${self.g_cellProcessingObj.g_generalBtnTextAccentClass}`,
				text: sheetspilot.editor.save_and_close,
				id: 'save_and_close_drawer_edition',
				click: function () {
					var button_self = jQuery(this);
					button_self.append(self.g_loaderProcessingIcon);
					var input_info = [];
					input_info.push({ 'data_table': 'postmeta', 'name': '_stock', 'value': jQuery('input[data-name="_stock"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'postmeta', 'name': '_low_stock_amount', 'value': jQuery('input[data-name="_low_stock_amount"]', self.g_pluginSideDrawerBody).val() });

					var data =
					{
						post_id: post_id,
						post_data: input_info
					};

					self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {
						button_self.html(sheetspilot.editor.save);
						self.onDrawerClose();
					});

				}
			});
			self.appendDrawerFooter(cancel_edition_button);
			self.appendDrawerFooter(save_edition_button);
			self.appendDrawerFooter(save_and_close_button);

			jQuery.each(g_tableStructure, function (index, value) {

				if (value.name == 'plugins__manage_stock') {

					if (value.related_editor_fields) {

						var isHeadingAdded = false;

						jQuery.each(value.related_editor_fields, function (index_inner, single_field) {

							var input_block_id = '_' + self.g_cellProcessingObj.generateRandomString(10);

							var container = jQuery('<div>', {
								class: self.g_sideDraweInputContainerNoIndex
							});



							var input_title = jQuery('<label>', {
								class: self.g_sideDraweInputTitleNoIndex,
								text: single_field.title,

							});
							var input_subtitle = jQuery('<p>', {
								class: self.g_sideDraweInputSubtitleNoIndex,
								text: single_field.subtitle
							});


							if (single_field.editor_type == 'section_title') {
								var heading = jQuery('<h3>', {
									class: self.g_sideDraweInputHeadingNoIndex,
									text: single_field.title,
								});

							}

							if (single_field.editor_type == 'numerical') {

								var input_container = jQuery('<div>', {
									class: self.g_sideDrawerNumericalContainerNoIndex + '  !!!!'
								});

								var input = jQuery('<input>', {
									class: self.g_sideDraweInputElementNoIndex + ' ' + '_copy_class_' + input_block_id,
									type: 'number',
									placeholder: single_field.placeholder,
									'data-table': single_field.data_table,
									'data-name': single_field.name,
									value: (response.message.rowdata[single_field.name]) ? response.message.rowdata[single_field.name] : 0
								});
								var input_plus = jQuery('<button>', {
									class: self.g_sideDraweInputElementNoIndex + ' ' + self.g_sideDraweInputElementModifierNoIndex + ' ' + self.g_unlimitedai__pluginBtnClass + ' ' + '_copy_class_' + input_block_id,
									type: 'button',
									value: '+',
									click: function () {
										var button_self = jQuery(this);
										var parent_row = button_self.parents('.' + self.g_sideDrawerNumericalContainerNoIndex);
										var current_val = parseInt(jQuery('.' + self.g_sideDraweInputElementNoIndex + '[type="number"]', parent_row).val());
										current_val++;
										jQuery('.' + self.g_sideDraweInputElementNoIndex + '[type="number"]', parent_row).val(current_val);
									}
								})
									.html(`<span class="${self.g_unlimitedai__pluginBtnIconClass}"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg></span>`)
									.wrap(`<span class="${self.g_sideDraweInputElementModifierNoIndex}-container"></span>`).parent();
								var input_minus = jQuery('<button>', {
									class: self.g_sideDraweInputElementNoIndex + ' ' + self.g_sideDraweInputElementModifierNoIndex + ' ' + self.g_unlimitedai__pluginBtnClass + ' ' + + '_copy_class_' + input_block_id,
									type: 'button',
									value: '-',
									click: function () {
										var button_self = jQuery(this);
										var parent_row = button_self.parents('.' + self.g_sideDrawerNumericalContainerNoIndex);
										var current_val = parseInt(jQuery('.' + self.g_sideDraweInputElementNoIndex + '[type="number"]', parent_row).val());
										current_val--;
										if (current_val < 0) {
											current_val = 0;
										}
										jQuery('.' + self.g_sideDraweInputElementNoIndex + '[type="number"]', parent_row).val(current_val);
									}
								})
									.html(`<span class="${self.g_unlimitedai__pluginBtnIconClass}"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-minus w-3.5 h-3.5"><path d="M5 12h14"></path></svg></span>`)
									.wrap(`<span class="${self.g_sideDraweInputElementModifierNoIndex}-container"></span>`).parent();

								input_container.append(input_minus, input, input_plus);
								container.append(input_title, input_container, input_subtitle);

								self.appendDrawerBody(container);
							}





						})


					}
				}
			})

			self.hideLoader();


		})

	}


	// edit downloadable
	openWooEditDownloadableFn(e) {

		var self = this;
		self.g_objDrawer.addClass(this.g_drawerDownloadableEditClass);
		self.onDrawerOpen();
		self.showLoader();

		var clicked = jQuery(e.target);
		var parent_td = clicked.parents('td');
		var parent_tr = clicked.parents('tr');
		var image_url = jQuery(this.g_cellProcessingObj.g_ubaiFeaturedImageUploader, parent_td).attr('src');
		var post_id = parent_tr.attr('data-id');

		// gettgin post current data
		//this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderProcessing);

		var post_data = [];

		post_data.push({ 'data_table': 'postmeta', 'name': '_download_limit' })
		post_data.push({ 'data_table': 'postmeta', 'name': '_download_expiry' })
		post_data.push({ 'data_table': 'postmeta', 'name': '_downloadable_files' })


		var data =
		{
			post_id: post_id,
			get_post_data: post_data
		}

		this.g_doublyAdmin.ajaxRequest('get_post_multidata', data, function (response) {




			self.dropDrawerBody();

			self.generateDrawerImageTitle(sheetspilot.editor.downloadable_subtitle, parent_tr);




			var cancel_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} `,
				text: sheetspilot.editor.cancel,
				id: 'cancel_drawer_edition',
				click: function () {
					self.onDrawerClose();
				}
			});
			var save_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass}`,
				text: sheetspilot.editor.save,
				id: 'save_drawer_edition',
				click: function () {
					var button_self = jQuery(this);
					button_self.append(self.g_loaderProcessingIcon);

					var input_info = [];
					input_info.push({ 'data_table': 'postmeta', 'name': '_download_limit', 'value': jQuery('input[data-name="_download_limit"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'postmeta', 'name': '_download_expiry', 'value': jQuery('input[data-name="_download_expiry"]', self.g_pluginSideDrawerBody).val() });

					//process files
					var files_data = [];

					jQuery('.' + self.g_sideDraweFileListingContainer + ' .' + self.g_sideDraweInputContainerNoIndex, self.g_pluginSideDrawerBody).each(function () {
						files_data.push({ 'name': jQuery('.' + self.g_singleImageUploadFileName, this).val(), 'url': jQuery('.' + self.g_singleImageUploadFileURL, this).val(), })
					})
					input_info.push({ 'data_table': 'postmeta', 'name': '_downloadable_files', 'value': files_data });

					var data =
					{
						post_id: post_id,
						post_data: input_info
					};

					self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {
						button_self.html(sheetspilot.editor.save);
					});

				}
			});
			var save_and_close_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} ${self.g_cellProcessingObj.g_generalBtnTextAccentClass}`,
				text: sheetspilot.editor.save_and_close,
				id: 'save_and_close_drawer_edition',
				click: function () {
					var button_self = jQuery(this);
					button_self.append(self.g_loaderProcessingIcon);

					var input_info = [];
					input_info.push({ 'data_table': 'postmeta', 'name': '_download_limit', 'value': jQuery('input[data-name="_download_limit"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'postmeta', 'name': '_download_expiry', 'value': jQuery('input[data-name="_download_expiry"]', self.g_pluginSideDrawerBody).val() });

					//process files
					var files_data = [];
					jQuery('.' + self.g_sideDraweFileListingContainer + ' .' + self.g_sideDraweInputContainerNoIndex, self.g_pluginSideDrawerBody).each(function () {
						files_data.push({ 'name': jQuery('.' + self.g_singleImageUploadFileName, this).val(), 'url': jQuery('.' + self.g_singleImageUploadFileURL, this).val(), })
					})
					input_info.push({ 'data_table': 'postmeta', 'name': '_downloadable_files', 'value': files_data });

					var data =
					{
						post_id: post_id,
						post_data: input_info
					};

					self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {
						button_self.html(sheetspilot.editor.save);
						self.onDrawerClose();
					});

				}
			});
			self.appendDrawerFooter(cancel_edition_button);
			self.appendDrawerFooter(save_edition_button);
			self.appendDrawerFooter(save_and_close_button);

			jQuery.each(g_tableStructure, function (index, value) {

				if (value.name == 'plugins__downloadable') {

					if (value.related_editor_fields) {



						jQuery.each(value.related_editor_fields, function (index_inner, single_field) {


							var input_block_id = '_' + self.g_cellProcessingObj.generateRandomString(10);

							var extra_calss = '';
							if (single_field.editor_type == 'section_title') {
								extra_calss = self.g_drawerFlexSpaceBetween;
							}

							var container = jQuery('<div>', {
								class: `${self.g_sideDraweInputContainerNoIndex} ${extra_calss}`
							});

							var image = jQuery('<div>', {
								class: self.g_sideDraweInputImagePreviewNoIndex,
								src: image_url
							});
							var input_title = jQuery('<label>', {
								class: self.g_sideDraweInputTitleNoIndex,
								text: single_field.title,

							});
							var input_subtitle = jQuery('<p>', {
								class: self.g_sideDraweInputSubtitleNoIndex,
								text: single_field.subtitle
							});
							var copy_to_clipboard = jQuery('<button>', {
								class: `${self.g_sideDraweInputCopyClipboardNoIndex}  ${self.g_cellProcessingObj.g_generalBtnTextClass}`,
								text: sheetspilot.editor.copy_to_clipboard,
								'data-id': input_block_id,
								click: function () {
									var content = jQuery('._copy_class_' + input_block_id).val();
									navigator.clipboard.writeText(content).then(() => {
									}).catch(err => {

									})
								}
							});

							if (single_field.editor_type == 'section_title') {
								var heading = jQuery('<h3>', {
									class: `${self.g_sideDraweInputHeadingNoIndex} ${self.g_drawerFlexSpaceBetween}`,
									text: single_field.title,
								});
								container.append(heading);


								if (single_field.extra_right) {
									var heading_right = jQuery('<div>', {
										class: `${self.g_drawerSectionTitleExtraRight} ${self.g_drawerSectionTitleExtraRight}`,
										html: single_field.extra_right,
									});
									container.append(heading_right);
								}

							}

							if (single_field.editor_type == 'number') {
								var input = jQuery('<input>', {
									class: self.g_sideDraweInputElementNoIndex + ' ' + '_copy_class_' + input_block_id,
									type: 'number',
									placeholder: single_field.placeholder,
									'data-table': single_field.data_table,
									'data-name': single_field.name,
									value: response.message.rowdata[single_field.name]
								});
								container.append(input_title, input, input_subtitle);
							}



							self.appendDrawerBody(container);
						})



						jQuery.each(value.related_editor_fields, function (index_inner, single_field) {


							if (single_field.editor_type == 'file_downloads') {
								var input_block_id = '_' + self.g_cellProcessingObj.generateRandomString(10);

								var container = jQuery('<div>', {
									class: self.g_sideDraweInputContainerNoIndex
								});
								var no_files_placeholder = jQuery('<div>', {
									class: self.fileUploaderPlaceholder,
								});
								var no_files_placeholder_image = jQuery('<div>', {
									class: `${self.g_sideDraweInputContainerNoIndex}  ${self.fileUploaderPlaceholderImage}`,
									html: '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-down w-8 h-8 text-muted-foreground/40 mb-2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M12 18v-6"></path><path d="m9 15 3 3 3-3"></path></svg>'
								});
								var no_files_placeholder_h1 = jQuery('<div>', {
									class: `${self.g_sideDraweInputContainerNoIndex} ${self.fileUploaderPlaceholderH1}`,
									text: sheetspilot.editor.fileupload_placeholder_h1
								});
								var no_files_placeholder_h2 = jQuery('<div>', {
									class: `${self.g_sideDraweInputContainerNoIndex} ${self.fileUploaderPlaceholderH2}`,
									text: sheetspilot.editor.fileupload_placeholder_h2
								});
								no_files_placeholder.append(no_files_placeholder_image, no_files_placeholder_h1, no_files_placeholder_h2);
								container.append(no_files_placeholder);


								self.appendDrawerBody(container);



								var file_listing_container = jQuery('<div>', {
									class: self.g_sideDraweFileListingContainer
								});
								self.appendDrawerBody(file_listing_container);

								/**single upload block */
								// append add new file button 
								var container = jQuery('<div>', {
									class: self.g_sideDraweInputContainerNoIndex
								});
								var add_file_button = jQuery('<div>', {
									class: self.g_sideDraweAddFileButton,
									html: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-3.5 h-3.5"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>' + sheetspilot.editor.add_file
								});

								container.append(add_file_button);
								self.appendDrawerBody(container);



							}
						})


					}
				}
			})

			jQuery.each(response.message.rowdata._downloadable_files, function (index, value) {

				// generate file inputs
				self.appendSingleFileUploadBlock(value.name, value.file);
			});
			self.recalculateFileInputCounters();
			self.hideLoader();

		});

	}

	// edit post type External relations 
	openPostTypeRelationsExternalEditor(e) {

		var self = this;
		self.g_objDrawer.addClass(this.g_drawerDownloadableEditClass + ' ' + this.g_drawerDownloadableEditClassExternal);
		self.onDrawerOpen();
		self.showLoader();

		var clicked = jQuery(e.target);
		var parent_td = clicked.parents('td');
		var parent_tr = clicked.parents('tr');
		var image_url = jQuery(this.g_cellProcessingObj.g_ubaiFeaturedImageUploader, parent_td).attr('src');
		var post_id = parent_tr.attr('data-id');

		var select_val = jQuery(this.g_cellProcessingObj.g_editorInput, parent_td).val();
		// gettgin post current data
		//this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderProcessing);

		var post_data = [];

		post_data.push({ 'data_table': 'postmeta', 'name': '_product_url' })
		post_data.push({ 'data_table': 'postmeta', 'name': '_button_text' })

		var data =
		{
			post_id: post_id,
			get_post_data: post_data
		}
		this.g_doublyAdmin.ajaxRequest('get_post_multidata', data, function (response) {

			self.dropDrawerBody();

			self.generateDrawerImageTitle(sheetspilot.editor.downloadable_subtitle, parent_tr);

			var cancel_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} `,
				text: sheetspilot.editor.cancel,
				id: 'cancel_drawer_edition',
				click: function () {
					self.onDrawerClose();
				}
			});
			var save_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass}`,
				text: sheetspilot.editor.save,
				id: 'save_drawer_edition',
				click: function () {
					var button_self = jQuery(this);
					button_self.append(self.g_loaderProcessingIcon);

					var input_info = [];
					input_info.push({ 'data_table': 'postmeta', 'name': '_product_url', 'value': jQuery('input[data-name="_product_url"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'postmeta', 'name': '_button_text', 'value': jQuery('input[data-name="_button_text"]', self.g_pluginSideDrawerBody).val() });

					var data =
					{
						post_id: post_id,
						post_data: input_info
					};

					self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {
						button_self.html(sheetspilot.editor.save);
						if (typeof onSuccess === 'function') {
							onSuccess();
						}
					});

				}
			});

			var save_and_close_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} ${self.g_cellProcessingObj.g_generalBtnTextAccentClass}`,
				text: sheetspilot.editor.save_and_close,
				id: 'save_and_close_drawer_edition',
				click: function () {
					var button_self = jQuery(this);
					button_self.append(self.g_loaderProcessingIcon);

					var input_info = [];
					input_info.push({ 'data_table': 'postmeta', 'name': '_product_url', 'value': jQuery('input[data-name="_product_url"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'postmeta', 'name': '_button_text', 'value': jQuery('input[data-name="_button_text"]', self.g_pluginSideDrawerBody).val() });

					var data =
					{
						post_id: post_id,
						post_data: input_info
					};

					self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {
						button_self.html(sheetspilot.editor.save);
						self.onDrawerClose();
					});

				}
			});


			self.appendDrawerFooter(cancel_edition_button);
			self.appendDrawerFooter(save_edition_button);
			self.appendDrawerFooter(save_and_close_button);

			jQuery.each(g_tableStructure, function (index, value) {

				if (value.name == 'plugins_product_type') {

					var relatin_fields_name = 'relation_external';
					if (value[relatin_fields_name]) {



						jQuery.each(value[relatin_fields_name], function (index_inner, single_field) {


							var input_block_id = '_' + self.g_cellProcessingObj.generateRandomString(10);

							var extra_calss = '';
							if (single_field.editor_type == 'section_title') {
								extra_calss = self.g_drawerFlexSpaceBetween;
							}

							var container = jQuery('<div>', {
								class: `${self.g_sideDraweInputContainerNoIndex} ${extra_calss}`
							});

							var image = jQuery('<div>', {
								class: self.g_sideDraweInputImagePreviewNoIndex,
								src: image_url
							});
							var input_title = jQuery('<label>', {
								class: self.g_sideDraweInputTitleNoIndex,
								text: single_field.title,

							});
							var input_subtitle = jQuery('<p>', {
								class: self.g_sideDraweInputSubtitleNoIndex,
								text: single_field.subtitle
							});
							var copy_to_clipboard = jQuery('<button>', {
								class: `${self.g_sideDraweInputCopyClipboardNoIndex}  ${self.g_cellProcessingObj.g_generalBtnTextClass}`,
								text: sheetspilot.editor.copy_to_clipboard,
								'data-id': input_block_id,
								click: function () {
									var content = jQuery('._copy_class_' + input_block_id).val();
									navigator.clipboard.writeText(content).then(() => {
									}).catch(err => {

									})
								}
							});

							if (single_field.editor_type == 'section_title') {
								var heading = jQuery('<h3>', {
									class: `${self.g_sideDraweInputHeadingNoIndex} ${self.g_drawerFlexSpaceBetween}`,
									text: single_field.title,
								});
								container.append(heading);


								if (single_field.extra_right) {
									var heading_right = jQuery('<div>', {
										class: `${self.g_drawerSectionTitleExtraRight} ${self.g_drawerSectionTitleExtraRight}`,
										html: single_field.extra_right,
									});
									container.append(heading_right);
								}

							}

							if (single_field.editor_type == 'text') {
								var input = jQuery('<input>', {
									class: self.g_sideDraweInputElementNoIndex + ' ' + '_copy_class_' + input_block_id,
									type: 'text',
									placeholder: single_field.placeholder,
									'data-table': single_field.data_table,
									'data-name': single_field.name,
									value: response.message.rowdata[single_field.name]
								});
								container.append(input_title, input, input_subtitle);
							}



							self.appendDrawerBody(container);
						})



						jQuery.each(value.related_editor_fields, function (index_inner, single_field) {


							if (single_field.editor_type == 'file_downloads') {
								var input_block_id = '_' + self.g_cellProcessingObj.generateRandomString(10);

								var container = jQuery('<div>', {
									class: self.g_sideDraweInputContainerNoIndex
								});
								var no_files_placeholder = jQuery('<div>', {
									class: self.fileUploaderPlaceholder,
								});
								var no_files_placeholder_image = jQuery('<div>', {
									class: `${self.g_sideDraweInputContainerNoIndex}  ${self.fileUploaderPlaceholderImage}`,
									html: '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-down w-8 h-8 text-muted-foreground/40 mb-2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M12 18v-6"></path><path d="m9 15 3 3 3-3"></path></svg>'
								});
								var no_files_placeholder_h1 = jQuery('<div>', {
									class: `${self.g_sideDraweInputContainerNoIndex} ${self.fileUploaderPlaceholderH1}`,
									text: sheetspilot.editor.fileupload_placeholder_h1
								});
								var no_files_placeholder_h2 = jQuery('<div>', {
									class: `${self.g_sideDraweInputContainerNoIndex} ${self.fileUploaderPlaceholderH2}`,
									text: sheetspilot.editor.fileupload_placeholder_h2
								});
								no_files_placeholder.append(no_files_placeholder_image, no_files_placeholder_h1, no_files_placeholder_h2);
								container.append(no_files_placeholder);


								self.appendDrawerBody(container);



								var file_listing_container = jQuery('<div>', {
									class: self.g_sideDraweFileListingContainer
								});
								self.appendDrawerBody(file_listing_container);

								/**single upload block */
								// append add new file button 
								var container = jQuery('<div>', {
									class: self.g_sideDraweInputContainerNoIndex
								});
								var add_file_button = jQuery('<div>', {
									class: self.g_sideDraweAddFileButton,
									html: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-3.5 h-3.5"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>' + sheetspilot.editor.add_file
								});

								container.append(add_file_button);
								self.appendDrawerBody(container);



							}
						})


					}
					// external end

				}
			})

			jQuery.each(response.message.rowdata._downloadable_files, function (index, value) {

				// generate file inputs
				self.appendSingleFileUploadBlock(value.name, value.file);
			});
			self.recalculateFileInputCounters();
			self.hideLoader();

		});

	}


	// edit post type GROUPED relations 
	openPostTypeRelationsGroupedEditor(e) {

		var self = this;
		self.g_objDrawer.addClass(this.g_drawerDownloadableEditClass + ' ' + this.g_drawerDownloadableEditClassGrouped);
		self.onDrawerOpen();
		self.showLoader();

		var clicked = jQuery(e.target);
		var parent_td = clicked.parents('td');
		var parent_tr = clicked.parents('tr');
		var image_url = jQuery(this.g_cellProcessingObj.g_ubaiFeaturedImageUploader, parent_td).attr('src');
		var post_id = parent_tr.attr('data-id');

		var select_val = jQuery(this.g_cellProcessingObj.g_editorInput, parent_td).val();

		// gettgin post current data
		//this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderProcessing);

		var post_data = [];

		post_data.push({ 'data_table': 'postmeta', 'name': '_children' })
		post_data.push({ 'data_table': 'postmeta', 'name': '_upsell_ids' })

		var data =
		{
			post_id: post_id,
			get_post_data: post_data
		}
		this.g_doublyAdmin.ajaxRequest('get_post_multidata', data, function (response) {
			self.dropDrawerBody();
			self.generateDrawerImageTitle(sheetspilot.editor.linked_products, parent_tr);

			var cancel_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} `,
				text: sheetspilot.editor.cancel,
				id: 'cancel_drawer_edition',
				click: function () {
					self.onDrawerClose();
				}
			});
			var save_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass}`,
				text: sheetspilot.editor.save,
				id: 'save_drawer_edition',
				click: function () {
					var button_self = jQuery(this);
					button_self.append(self.g_loaderProcessingIcon);

					var grouped_list = [];
					jQuery('.grouped_contnet_tab .table_body_row').each(function () {
						grouped_list.push(jQuery(this).attr('data-id'));
					})
					var upsell_list = [];
					jQuery('.upsell_contnet_tab .table_body_row').each(function () {
						upsell_list.push(jQuery(this).attr('data-id'));
					})


					var input_info = [];
					input_info.push({ 'data_table': 'postmeta', 'name': '_children', 'value': grouped_list });
					input_info.push({ 'data_table': 'postmeta', 'name': '_upsell_ids', 'value': upsell_list });

					var data =
					{
						post_id: post_id,
						post_data: input_info
					};

					self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {
						button_self.html(sheetspilot.editor.save);
						if (typeof onSuccess === 'function') {
							onSuccess();
						}
					});

				}
			});

			var save_and_close_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} ${self.g_cellProcessingObj.g_generalBtnTextAccentClass}`,
				text: sheetspilot.editor.save_and_close,
				id: 'save_and_close_drawer_edition',
				click: function () {
					var button_self = jQuery(this);
					button_self.append(self.g_loaderProcessingIcon);

					var grouped_list = [];
					jQuery('.grouped_contnet_tab .table_body_row').each(function () {
						grouped_list.push(jQuery(this).attr('data-id'));
					})
					var upsell_list = [];
					jQuery('.upsell_contnet_tab .table_body_row').each(function () {
						upsell_list.push(jQuery(this).attr('data-id'));
					})


					var input_info = [];
					input_info.push({ 'data_table': 'postmeta', 'name': '_children', 'value': grouped_list });
					input_info.push({ 'data_table': 'postmeta', 'name': '_upsell_ids', 'value': upsell_list });


					var data =
					{
						post_id: post_id,
						post_data: input_info
					};

					self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {
						button_self.html(sheetspilot.editor.save);
						self.onDrawerClose();
					});

				}
			});


			self.appendDrawerFooter(cancel_edition_button);
			self.appendDrawerFooter(save_edition_button);
			self.appendDrawerFooter(save_and_close_button);

			jQuery.each(g_tableStructure, function (index, value) {

				if (value.name == 'plugins_product_type') {

					var relatin_fields_name = 'relation_grouped';

					if (value[relatin_fields_name]) {

						jQuery.each(value[relatin_fields_name], function (index_inner, single_field) {


							if (single_field.editor_type == 'grouped_functionality') {

								var container = jQuery('<div>', {
									class: self.g_sideDraweInputContainerNoIndex
								});
								var tabs_container = jQuery('<div>', {
									class: 'grouped_products_tabs_container',
								});

								var upsells_container = jQuery('<div>', {
									class: `${self.g_groupedProductsSingleTabNoIndex} upsell_container is_active`,
									'data-type': 'upsell',
									html: 'Upsells <span class="upsell_counter">(0)</span>'
								});
								var grouped_container = jQuery('<div>', {
									class: `${self.g_groupedProductsSingleTabNoIndex}  grouped_container`,
									'data-type': 'grouped',
									html: 'Grouped <span class="grouped_counter">(0)</span>'
								});
								tabs_container.append(upsells_container, grouped_container);
								container.append(tabs_container);


								self.appendDrawerBody(container);

								var container = jQuery('<div>', {
									class: self.g_sideDraweInputContainerNoIndex + ' unlimitedai-plugin__search'
								});
								var iconHTML = '<span class="unlimitedai-plugin__search-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg></span>';
								var search_input = jQuery('<input>', {
									type: 'text',
									placeholder: sheetspilot.editor.select_prodcts_to_add,
									class: self.g_groupedSearchItemInputNoIndex + ' ' + self.g_sideDraweInputElementNoIndex + ' unlimitedai-plugin__search-input',

								});
								var loader = jQuery('<span>', {
									class: 'drawer_grouped_search_loader loader_round',
									css: {
										'display': 'none',
										'position': 'absolute',
										'right': '0px',
										'top': '7px',
									}
								});


								container.append(search_input, loader, iconHTML);
								self.appendDrawerBody(container);


								var container = jQuery('<div>', {
									class: self.g_sideDraweInputContainerNoIndex
								});
								var search_input = jQuery('<div>', {
									class: 'grouped_product_search_results',

								});
								container.append(search_input);
								self.appendDrawerBody(container);


								var container = jQuery('<div>', {
									class: self.g_sideDraweInputContainerNoIndex
								});

								var upsell_contnet_tab = jQuery('<div>', {
									class: 'single_product_tab upsell_contnet_tab is_active',
									html: sheetspilot.editor.no_products_added_yet
								});




								self.appendDrawerBody(upsell_contnet_tab);


								var container = jQuery('<div>', {
									class: self.g_sideDraweInputContainerNoIndex
								});

								var grouped_contnet_tab = jQuery('<div>', {
									class: 'single_product_tab grouped_contnet_tab',
									html: sheetspilot.editor.no_products_added_yet
								});
								self.appendDrawerBody(grouped_contnet_tab);


								var arcive_tab = jQuery('.single_product_tab.grouped_contnet_tab');
								// fill in postdata
								var _children = '_children';
								var tab_top = self.generateGroupedProductsTableHeaderRow();
								arcive_tab.empty();
								arcive_tab.append(tab_top)

								jQuery.each(response.message.rowdata[_children], function (index, value) {
									var data_row = self.generateGroupedProductsTableItem(value.id, value.image, value.post_title, value.price);
									arcive_tab.append(data_row);
								})


								var arcive_tab = jQuery('.single_product_tab.upsell_contnet_tab');
								var tab_top = self.generateGroupedProductsTableHeaderRow();
								arcive_tab.empty();
								arcive_tab.append(tab_top)
								var _upsell_ids = '_upsell_ids';
								jQuery.each(response.message.rowdata[_upsell_ids], function (index, value) {
									var data_row = self.generateGroupedProductsTableItem(value.id, value.image, value.post_title, value.price);
									arcive_tab.append(data_row);
								})

								self.recalcualteUpsellAndGroupedCounters();
							}
						})


					}
					// external end

				}
			})

			jQuery.each(response.message.rowdata._downloadable_files, function (index, value) {

				// generate file inputs
				self.appendSingleFileUploadBlock(value.name, value.file);
			});
			self.recalculateFileInputCounters();
			self.hideLoader();

		});

	}

	// edit post type VARIABLE relations 
	openPostTypeRelationsVariableEditor(e) {

		var self = this;
		self.g_objDrawer.addClass(this.g_drawerDownloadableEditClass);
		self.g_objDrawer.addClass(this.g_drawerDownloadableEditClass + ' ' + this.g_drawerDownloadableEditClassVariable);
		self.onDrawerOpen();
		self.showLoader();

		var clicked = jQuery(e.target);
		var parent_td = clicked.parents('td');
		var parent_tr = clicked.parents('tr');
		var image_url = jQuery(this.g_cellProcessingObj.g_ubaiFeaturedImageUploader, parent_td).attr('src');
		var post_id = parent_tr.attr('data-id');

		var select_val = jQuery(this.g_cellProcessingObj.g_editorInput, parent_td).val();

		// gettgin post current data
		//this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderProcessing);

		var post_data = [];

		post_data.push({ 'data_table': 'postmeta', 'name': '_attributes' })

		var data =
		{
			post_id: post_id,
			get_post_data: post_data
		}

		this.g_doublyAdmin.ajaxRequest('get_post_multidata', data, function (response) {
			self.dropDrawerBody();
			self.generateDrawerImageTitle(sheetspilot.editor.variations, parent_tr);

			var cancel_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} `,
				text: sheetspilot.editor.cancel,
				id: 'cancel_drawer_edition',
				click: function () {
					self.onDrawerClose();
				}
			});
			self.appendDrawerFooter(cancel_edition_button);

			var drawer_bottom_buttons = [
				{ 'id': 'save_drawer_edition', 'text': sheetspilot.editor.save, 'class': `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass}`, 'have_close': false },
				{ 'id': 'save_and_close_drawer_edition', 'text': sheetspilot.editor.save_and_close, 'class': `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} ${self.g_cellProcessingObj.g_generalBtnTextAccentClass}`, 'have_close': true },
			];

			jQuery.each(drawer_bottom_buttons, function (index, value) {
				self.appendDrawerFooter(
					jQuery('<button>', {
						class: value.class,
						text: value.text,
						id: value.id,
						click: function () {
							var button_self = jQuery(this);
							button_self.append(self.g_loaderProcessingIcon);

							var variation_info_global = [];
							jQuery(g_prodTypeVariable.singleVariationBlock).each(function () {

								var this_variation = jQuery(this);

								// get all attributes
								var attributes = [];
								jQuery('.variation_attribute_selector', this_variation).each(function (index, value) {
									attributes.push({ 'name': jQuery(this).attr('data-attrname'), 'value': jQuery(this).val() });
								})

								var download_files = [];
								jQuery('.single_file_row', this_variation).each(function (index, value) {
									download_files.push({ 'id': jQuery('.file_name', this).attr('data-id'), 'name': jQuery('.file_name', this).val(), 'url': jQuery('.file_url', this).val() });
								})


								variation_info_global.push({
									id: this_variation.attr('data-id'),
									featured_image: jQuery('.variation_featured_image_block', this_variation).attr('data-id'),
									attributes: attributes,
									_sku: jQuery('._sku', this_variation).val(),
									_global_unique_id: jQuery('._global_unique_id', this_variation).val(),
									enabled: jQuery('.enabled', this_variation).is(':checked') ? 'yes' : 'no',
									downloadable: jQuery('.downloadable', this_variation).is(':checked') ? 'yes' : 'no',
									virtual: jQuery('.virtual', this_variation).is(':checked') ? 'yes' : 'no',
									manage_stock: jQuery('.manage_stock', this_variation).is(':checked') ? 'yes' : 'no',

									_regular_price: jQuery('._regular_price', this_variation).val(),
									_sale_price: jQuery('._sale_price', this_variation).val(),
									_stock: jQuery('._stock', this_variation).val(),
									allow_backorders: jQuery('.allow_backorders', this_variation).val(),
									stock_status: jQuery('.stock_status', this_variation).val(),
									_low_stock_amount: jQuery('._low_stock_amount', this_variation).val(),
									_weight: jQuery('._weight', this_variation).val(),
									_length: jQuery('._length', this_variation).val(),
									_width: jQuery('._width', this_variation).val(),
									_height: jQuery('._height', this_variation).val(),

									shipping_class: jQuery('.shipping_class', this_variation).val(),
									tax_class: jQuery('.tax_class', this_variation).val(),
									description: jQuery('.description', this_variation).val(),

									download_files: download_files,
									download_limit: jQuery('.download_limit', this_variation).val(),
									download_expiry: jQuery('.download_expiry', this_variation).val()
								});
							})
							var input_info = [];
							input_info.push({ 'data_table': 'postmeta', 'name': '_variations', 'value': variation_info_global });
							var data =
							{
								post_id: post_id,
								post_data: input_info
							};
							self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {
								button_self.html(sheetspilot.editor.save);
								if (value.have_close) {
									self.onDrawerClose();
								}
							});

						}
					})
				);
			})


			jQuery.each(g_tableStructure, function (index, value) {

				if (value.name == 'plugins_product_type') {

					var relatin_fields_name = 'relation_variable';

					if (value[relatin_fields_name]) {

						jQuery.each(value[relatin_fields_name], function (index_inner, single_field) {

							if (single_field.editor_type == 'variable_functionality') {

								// geberate top drops
								g_prodTypeVariable.generateInitialVariationSelectors(response.message.rowdata._attributes);

								//####

							}
						})


					}
					// external end

				}
			})

			jQuery.each(response.message.rowdata._downloadable_files, function (index, value) {
				// generate file inputs
				self.appendSingleFileUploadBlock(value.name, value.file);
			});
			self.recalculateFileInputCounters();
			self.hideLoader();

		});

	}

	// inline modify featured image
	inlineModifyFeaturedImage(e) {
		var self = this;

		self.g_objDrawer.addClass(this.g_imageEditingClass);
		self.onDrawerOpen();
		self.showLoader();

		var clicked = jQuery(e.target);
		var parent_td = clicked.parents('td');
		var parent_td_index = parent_td.index();
		var column_name_tr = jQuery('tr th', self.g_objPostsEditor).eq(parent_td_index);

		var column_name = jQuery('.unlimitedai-plugin__th-title', column_name_tr).html();
		var parent_tr = clicked.parents('tr');
		var image_url = jQuery(this.g_cellProcessingObj.g_ubaiFeaturedImageUploader, parent_td).attr('src');

		// gettgin post current data
		//this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderProcessing);

		var post_data = [];

		post_data.push({ 'data_table': 'postmeta', 'name': '_wp_attachment_image_alt' })
		post_data.push({ 'data_table': 'posts', 'name': 'post_title' })
		post_data.push({ 'data_table': 'posts', 'name': 'post_excerpt' })
		post_data.push({ 'data_table': 'posts', 'name': 'post_content' })
		post_data.push({ 'data_table': 'posts', 'name': 'guid' })

		var data =
		{
			post_id: jQuery(this.g_cellProcessingObj.g_ubaiFeaturedImageUploader, parent_td).attr('data-id'),
			get_post_data: post_data
		}

		this.g_doublyAdmin.ajaxRequest('get_post_multidata', data, function (response) {
			self.dropDrawerBody();
			self.generateDrawerImageTitle(column_name, parent_tr);

			var cancel_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} `,
				text: sheetspilot.editor.cancel,
				id: 'cancel_drawer_edition',
				click: function () {
					self.onDrawerClose();
				}
			});
			var save_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass}`,
				text: sheetspilot.editor.save,
				id: 'save_drawer_edition',
				click: function () {
					var button_self = jQuery(this);
					button_self.append(self.g_loaderProcessingIcon);
					var input_info = [];
					input_info.push({ 'data_table': 'postmeta', 'name': '_wp_attachment_image_alt', 'value': jQuery('textarea[data-name="_wp_attachment_image_alt"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'posts', 'name': 'post_title', 'value': jQuery('input[data-name="post_title"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'posts', 'name': 'post_excerpt', 'value': jQuery('textarea[data-name="post_excerpt"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'posts', 'name': 'post_content', 'value': jQuery('textarea[data-name="post_content"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'posts', 'name': 'guid', 'value': jQuery('input[data-name="guid"]', self.g_pluginSideDrawerBody).val() });

					var data =
					{
						post_id: jQuery(self.g_cellProcessingObj.g_ubaiFeaturedImageUploader, parent_td).attr('data-id'),
						post_data: input_info
					};

					self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {
						button_self.html('Save');
					});

				}
			});
			var save_and_close_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} ${self.g_cellProcessingObj.g_generalBtnTextAccentClass}`,
				text: sheetspilot.editor.save_and_close,
				id: 'save_and_close_drawer_edition',
				click: function () {
					var button_self = jQuery(this);
					button_self.append(self.g_loaderProcessingIcon);
					var input_info = [];
					input_info.push({ 'data_table': 'postmeta', 'name': '_wp_attachment_image_alt', 'value': jQuery('textarea[data-name="_wp_attachment_image_alt"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'posts', 'name': 'post_title', 'value': jQuery('input[data-name="post_title"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'posts', 'name': 'post_excerpt', 'value': jQuery('textarea[data-name="post_excerpt"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'posts', 'name': 'post_content', 'value': jQuery('textarea[data-name="post_content"]', self.g_pluginSideDrawerBody).val() });
					input_info.push({ 'data_table': 'posts', 'name': 'guid', 'value': jQuery('input[data-name="guid"]', self.g_pluginSideDrawerBody).val() });

					var data =
					{
						post_id: jQuery(self.g_cellProcessingObj.g_ubaiFeaturedImageUploader, parent_td).attr('data-id'),
						post_data: input_info
					};

					self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {
						button_self.html(sheetspilot.editor.save);
						self.onDrawerClose();
					});

				}
			});
			self.appendDrawerFooter(cancel_edition_button);
			self.appendDrawerFooter(save_edition_button);
			self.appendDrawerFooter(save_and_close_button);

			jQuery.each(g_tableStructure, function (index, value) {

				if (value.name == 'post_image') {

					if (value.related_editor_fields) {

						jQuery.each(value.related_editor_fields, function (index_inner, single_field) {
							if (single_field.data_table == 'image_preview') {
								var container = jQuery('<div>', {
									class: self.g_sideDraweInputContainerNoIndex
								});
								var image_wrapper = jQuery('<div>', {
									class: self.g_sideDraweInputImagePreviewWrapNoIndex
								});
								var image = jQuery('<img>', {
									class: self.g_sideDraweInputImagePreviewNoIndex,
									src: image_url
								});
								image_wrapper.append(image);
								container.append(image_wrapper);
								self.appendDrawerBody(container);
							}

						})

						jQuery.each(value.related_editor_fields, function (index_inner, single_field) {

							if (single_field.data_table == 'image_preview') { return; }

							var input_block_id = '_' + self.g_cellProcessingObj.generateRandomString(10);

							var container = jQuery('<div>', {
								class: self.g_sideDraweInputContainerNoIndex
							});
							var image = jQuery('<div>', {
								class: self.g_sideDraweInputImagePreviewNoIndex,
								src: image_url
							});
							var input_title = jQuery('<label>', {
								class: self.g_sideDraweInputTitleNoIndex,
								text: single_field.title,

							});
							var input_subtitle = jQuery('<p>', {
								class: self.g_sideDraweInputSubtitleNoIndex,
								text: single_field.subtitle
							});
							var copy_to_clipboard = jQuery('<button>', {
								class: `${self.g_sideDraweInputCopyClipboardNoIndex}  ${self.g_cellProcessingObj.g_generalBtnTextClass}`,
								text: sheetspilot.editor.copy_to_clipboard,
								'data-id': input_block_id,
								click: function () {
									var content = jQuery('._copy_class_' + input_block_id).val();

									navigator.clipboard.writeText(content).then(() => {

									}).catch(err => {

									})
								}
							});

							if (single_field.editor_type == 'text') {
								var input = jQuery('<input>', {
									class: self.g_sideDraweInputElementNoIndex + ' ' + '_copy_class_' + input_block_id,
									type: 'text',
									placeholder: single_field.placeholder,
									'data-table': single_field.data_table,
									'data-name': single_field.name,
									value: response.message.rowdata[single_field.name]
								});
							}
							if (single_field.editor_type == 'textarea') {
								var input = jQuery('<textarea>', {
									class: self.g_sideDraweInputElementTextareaNoIndex + ' ' + '_copy_class_' + input_block_id,
									placeholder: single_field.placeholder,
									'data-table': single_field.data_table,
									'data-name': single_field.name,
									text: response.message.rowdata[single_field.name]
								});
							}
							container.append(input_title, input, input_subtitle);

							if (single_field.has_copy) {
								container.append(copy_to_clipboard);
							}

							self.appendDrawerBody(container);
						})


					}
				}
			})

			self.hideLoader();

		});
	}

	// edit product attributes
	openProductAttributesEditor(e) {
		var self = this;
		self.g_objDrawer.addClass(this.g_drawerDownloadableEditClass);
		self.g_objDrawer.addClass(this.g_drawerDownloadableEditClassAttributes);

		self.onDrawerOpen();
		self.showLoader();

		var clicked = jQuery(e.target);
		var parent_td = clicked.parents('td');
		var parent_tr = clicked.parents('tr');
		var image_url = jQuery(this.g_cellProcessingObj.g_ubaiFeaturedImageUploader, parent_td).attr('src');
		var post_id = parent_tr.attr('data-id');

		var select_val = jQuery(this.g_cellProcessingObj.g_editorInput, parent_td).val();

		// gettgin post current data
		//this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderProcessing);

		var post_data = [];

		post_data.push({ 'data_table': 'postmeta', 'name': '_attributes' })

		var data =
		{
			post_id: post_id,
			get_post_data: post_data
		}

		this.g_doublyAdmin.ajaxRequest('get_post_multidata', data, function (response) {
			self.dropDrawerBody();

			self.generateDrawerImageTitle(sheetspilot.editor.product_attributes, parent_tr);

			var cancel_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} `,
				text: sheetspilot.editor.cancel,
				id: 'cancel_drawer_edition',
				click: function () {
					self.onDrawerClose();
				}
			});
			self.appendDrawerFooter(cancel_edition_button);

			var drawer_bottom_buttons = [
				{ 'id': 'save_drawer_edition', 'text': sheetspilot.editor.save, 'class': `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass}`, 'have_close': false },
				{ 'id': 'save_and_close_drawer_edition', 'text': sheetspilot.editor.save_and_close, 'class': `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} ${self.g_cellProcessingObj.g_generalBtnTextAccentClass}`, 'have_close': true },
			];

			jQuery.each(drawer_bottom_buttons, function (index, value) {
				self.appendDrawerFooter(
					jQuery('<button>', {
						class: value.class,
						text: value.text,
						id: value.id,
						click: function () {
							var button_self = jQuery(this);
							button_self.append(self.g_loaderProcessingIcon);

							var attributes_list = [];

							var position = 1;
							jQuery(spAttributesEditor.sideDrawerAttributeContainer).each(function () {

								// check if slug exists
								if (jQuery(spAttributesEditor.sideDrawerInputInput, this).attr('data-slug') == '') {
									jQuery(spAttributesEditor.sideDrawerInputInput, this).attr('data-slug', jQuery(spAttributesEditor.sideDrawerInputInput, this).val());
								}

								attributes_list.push({
									'attr_title': jQuery(spAttributesEditor.sideDrawerInputInput, this).val(),
									'attr_slug': jQuery(spAttributesEditor.sideDrawerInputInput, this).attr('data-slug'),
									'attr_values': jQuery(spAttributesEditor.sideDrawerInputTextareaAttrValues, this).val(),
									'is_visible': jQuery(spAttributesEditor.sideDrawerCheckboxInputIsVisible, this).is(':checked'),
									'use_for_variations': jQuery(spAttributesEditor.sideDrawerCheckboxInputUsedForVariations, this).is(':checked'),
									'position': position
								})

								position++;
							})

							// update cell
							jQuery('.countable_cell_inner_counter', parent_td).html(attributes_list.length + ' ' + sheetspilot.editor.items);

							var data =
							{
								post_id: post_id,
								post_data: [{ 'data_table': 'postmeta', 'name': '_attributes', 'value': attributes_list }]
							};

							self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {

								button_self.html(sheetspilot.editor.save);
								if (value.have_close) {
									self.onDrawerClose();
								}

								// set counter


							});

						}
					})
				);
			})

			jQuery.each(g_tableStructure, function (index, value) {
				if (value.name == 'plugins_attributes') {
					spAttributesEditor.generateAttributesInterface(response.message.rowdata);

				}
			})


			self.hideLoader();

		});

	}

	// generate drawer image title
	generateDrawerImageTitle(subtitle_text, parent_tr) {
		var image_url = jQuery(this.g_cellProcessingObj.g_ubaiFeaturedImageUploader, parent_tr).attr('src');
		var product_title = jQuery('.' + this.g_editorContainerNoPrefix + '[data-column="post_title"] ' + this.g_cellProcessingObj.g_visualPart, parent_tr).html();

		var drawer_container = jQuery('<div>', {
			class: 'sheetpilot_side_drawer_title_container'
		});
		var drawer_image_container = jQuery('<div>', {
			class: 'sheetpilot_side_drawer_title_image_container'
		});
		var drawer_image = jQuery('<div>', {
			class: 'sheetpilot_side_drawer_title_image',
			css: {
				'background-image': 'url(' + image_url + ')',
				'background-size': 'cover',
				'background-position': 'center'
			}
		});
		var drawer_info_col = jQuery('<div>', {
			class: 'sheetpilot_side_drawer_title_info_col'
		});
		var drawer_title = jQuery('<div>', {
			class: 'sheetpilot_side_drawer_product_stock_manage_title',
			html: product_title
		});
		var drawer_subtitle = jQuery('<p>', {
			class: 'sheetpilot_side_drawer_product_stock_manage_subtitle',
			html: subtitle_text
		});
		drawer_image_container.append(drawer_image);
		drawer_info_col.append(drawer_title, drawer_subtitle);
		drawer_container.append(drawer_image_container, drawer_info_col);

		this.setDrawerTitle(drawer_container);
	}

	// generate custom drawer image title
	generateCustomDrawerImageTitle(title, subtitle_text, image_url) {
	 
		var product_title = title;

		var drawer_container = jQuery('<div>', {
			class: 'sheetpilot_side_drawer_title_container'
		});
		var drawer_image_container = jQuery('<div>', {
			class: 'sheetpilot_side_drawer_title_image_container'
		});
		var drawer_image = jQuery('<div>', {
			class: 'sheetpilot_side_drawer_title_image',
			html: image_url
		});
		var drawer_info_col = jQuery('<div>', {
			class: 'sheetpilot_side_drawer_title_info_col'
		});
		var drawer_title = jQuery('<div>', {
			class: 'sheetpilot_side_drawer_product_stock_manage_title',
			html: product_title
		});
		var drawer_subtitle = jQuery('<p>', {
			class: 'sheetpilot_side_drawer_product_stock_manage_subtitle',
			html: subtitle_text
		});
		drawer_image_container.append(drawer_image);
		drawer_info_col.append(drawer_title, drawer_subtitle);
		drawer_container.append(drawer_image_container, drawer_info_col);

		this.setDrawerTitle(drawer_container);
	}

	appendSingleFileUploadBlock(name = '', file = '') {
		var self = this;
		var container = jQuery('<div>', {
			class: self.g_sideDraweInputContainerNoIndex
		});
		var single_file_upload_cont = jQuery('<div>', {
			class: self.g_singleImageUploadBlock
		});

		var single_upload_title_cont = jQuery('<div>', {
			class: ` ${self.g_drawerFlexSpaceBetween}`,
		});
		var single_uplaod_title_delete = jQuery('<div>', {
			class: `${self.drawerSingleFileBlockRemove}`,
			html: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-3.5 h-3.5"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg>`
		});

		var single_file_upload_title = jQuery('<h3>', {
			class: `${self.g_sideDraweInputHeadingNoIndex}`,
			html: sheetspilot.editor.file_x
		});

		single_upload_title_cont.append(single_file_upload_title, single_uplaod_title_delete);

		var file_name_label = jQuery('<div>', {
			class: self.g_singleImageFieldLabel,
			html: sheetspilot.editor.file_name
		});
		var file_name_input = jQuery('<input>', {
			type: 'text',
			class: `${self.g_singleImageFieldInput}  ${self.g_singleImageUploadFileName}`,
			placeholder: sheetspilot.editor.file_name_placeholder,
			value: name
		});

		single_file_upload_cont.append(single_upload_title_cont, file_name_label, file_name_input);
		container.append(single_file_upload_cont);

		var file_url_label = jQuery('<div>', {
			class: self.g_singleImageFieldLabel,
			html: sheetspilot.editor.file_url
		});
		var file_name_input = jQuery('<input>', {
			type: 'text',
			class: `${self.g_singleImageFieldInput}  ${self.g_singleImageUploadFileURL}`,
			placeholder: sheetspilot.editor.file_url_placeholder,
			value: file
		});

		single_file_upload_cont.append(file_url_label, file_name_input);


		container.append(single_file_upload_cont);
		jQuery('.' + self.g_sideDraweFileListingContainer).append(container);

	}

	recalculateFileInputCounters() {

		var self = this;
		var count = 1;
		var this_file_name;

		jQuery('.' + this.g_sideDraweFileListingContainer + ' .' + this.g_sideDraweInputContainerNoIndex, this.objPluginSideDrawer).each(function () {
			jQuery(' .' + self.g_sideDraweInputHeadingNoIndex, this).html(sheetspilot.editor.file_single + ' ' + count);
			count++;
		})

		if (count > 1) {
			jQuery('.' + this.fileUploaderPlaceholder).hide();
		} else {
			jQuery('.' + this.fileUploaderPlaceholder).show();
		}

	}

	generateGroupedProductsTableItem(post_id, post_image, post_title, post_price) {
		var upsell_body_row_tab = jQuery('<div>', {
			class: 'table_body_row',
			'data-id': post_id
		});
		var upsell_body_cell_image = jQuery('<div>', {
			class: 'table_body_cell_image',
		});
		var upsell_body_cell_image_inner = jQuery('<div>', {
			class: 'table_body_cell_image_inner',
			css: {
				'background-image': 'url(' + post_image + ')',
				'background-size': 'cover',
				'background-position': 'center center',
			},
		});

		upsell_body_cell_image.append(upsell_body_cell_image_inner);

		var upsell_body_cell_name = jQuery('<div>', {
			class: 'table_body_cell_name',
			html: post_title
		});
		var upsell_body_cell_price = jQuery('<div>', {
			class: 'table_body_cell_price',
			html: post_price
		});
		var upsell_body_cell_delete = jQuery('<div>', {
			class: this.g_groupedTableBodyCellDeleteNoIndex,
			html: '<span class="table_body_cell_delete_wrap"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg></span>'
		});

		upsell_body_row_tab.append(upsell_body_cell_image, upsell_body_cell_name, upsell_body_cell_price, upsell_body_cell_delete);
		return upsell_body_row_tab;
	}
	generateGroupedProductsTableHeaderRow() {
		var upsell_header_row_tab = jQuery('<div>', {
			class: 'table_header_row',
		});
		var upsell_header_cell_image = jQuery('<div>', {
			class: 'table_header_cell_image',
			html: sheetspilot.editor.image
		});
		var upsell_header_cell_name = jQuery('<div>', {
			class: 'table_header_cell_name',
			html: sheetspilot.editor.name
		});
		var upsell_header_cell_price = jQuery('<div>', {
			class: 'table_header_cell_price',
			html: sheetspilot.editor.price
		});
		var upsell_header_cell_delete = jQuery('<div>', {
			class: 'table_header_cell_delete',
			html: ''
		});
		upsell_header_row_tab.append(upsell_header_cell_image, upsell_header_cell_name, upsell_header_cell_price, upsell_header_cell_delete);
		return upsell_header_row_tab;
	}


	recalcualteUpsellAndGroupedCounters() {
		var grouped = jQuery('.single_product_tab.grouped_contnet_tab .table_body_row').length;
		var upsells = jQuery('.single_product_tab.upsell_contnet_tab .table_body_row').length;
		jQuery('.upsell_counter').html('(' + upsells + ')');
		jQuery('.grouped_counter').html('(' + grouped + ')');
		return {
			'upsell': upsells,
			'grouped': grouped
		};
	}

	// edit post type External relations 
	openSettingsEditor(e) {

		var self = this;
		self.g_objDrawer.addClass(this.g_drawerDownloadableEditClass + ' ' + this.g_drawerDownloadableEditClassExternal + ' ' + this.g_drawerGeneralSettingsClass);
		self.onDrawerOpen();
		self.showLoader();



		// gettgin post current data
		//this.g_doublyAdmin.setAjaxLoaderID(this.g_ajaxLoaderProcessing);


		if (1 == 1) {

			self.dropDrawerBody();

			self.setDrawerTitle(sheetspilot.editor.content_rules);

			var cancel_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} `,
				text: sheetspilot.editor.cancel,
				id: 'cancel_drawer_edition',
				click: function () {
					self.onDrawerClose();
				}
			});
			self.appendDrawerFooter(cancel_edition_button);

			var drawer_bottom_buttons = [
				{ 'id': 'save_drawer_edition', 'text': sheetspilot.editor.save, 'class': `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass}`, 'have_close': false },
				{ 'id': 'save_and_close_drawer_edition', 'text': sheetspilot.editor.save_and_close, 'class': `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} ${self.g_cellProcessingObj.g_generalBtnTextAccentClass}`, 'have_close': true },
			];

			jQuery.each(drawer_bottom_buttons, function (index, value) {
				self.appendDrawerFooter(
					jQuery('<button>', {
						class: value.class,
						text: value.text,
						id: value.id,
						click: function () {

							jQuery('#ubai_contentrules_content_tone').val(jQuery('#drawer_content_tone').val());
							jQuery('#ubai_contentrules_content_language').val(jQuery('#drawer_content_language').val());
							jQuery('#ubai_contentrules_custom_language').val(jQuery('#drawer_custom_language').val());
							jQuery('#ubai_contentrules_target_audience').val(jQuery('#drawer_target_audience').val());
							jQuery('#ubai_contentrules_brand_voice').val(jQuery('#drawer_brand_voice').val());
							jQuery('.ubai-content-rules-dialog__btn--save').trigger('click');

							if (value.have_close) {
								self.onDrawerClose();
							}
						}
					})
				);
			})

			var settings = [
				{
					'title': sheetspilot.editor.content_tone,
					'type': 'select',
					'id': 'drawer_content_tone',
					'values': {
						'': sheetspilot.editor.not_selected,
						'Professional': sheetspilot.editor.professional,
						'Casual': sheetspilot.editor.casual,
						'Friendly': sheetspilot.editor.friendly,
						'Formal': sheetspilot.editor.formal,
						'Persuasive': sheetspilot.editor.persuasive,
						'Urgent': sheetspilot.editor.urgent,
						'Informative': sheetspilot.editor.informative,
						'Confident': sheetspilot.editor.confident,
						'Humorous': sheetspilot.editor.humorous,
						'Inspirational': sheetspilot.editor.inspirational,
						'Sarcastic': sheetspilot.editor.sarcastic,
						'Analytical': sheetspilot.editor.analytical,
						'Concise': sheetspilot.editor.concise,
					}
				},
				{
					'title': sheetspilot.editor.content_language,
					'type': 'select',
					'id': 'drawer_content_language',
					'values': {
						'English': sheetspilot.editor.english,
						'Spanish': sheetspilot.editor.spanish,
						'French': sheetspilot.editor.french,
						'German': sheetspilot.editor.german,
						'Italian': sheetspilot.editor.italian,
						'Portuguese': sheetspilot.editor.portuguese,
						'Dutch': sheetspilot.editor.dutch,
						'Polish': sheetspilot.editor.polish,
						'Russian': sheetspilot.editor.russian,
						'Chinese': sheetspilot.editor.chinese,
						'Japanese': sheetspilot.editor.japanese,
						'Korean': sheetspilot.editor.korean,
						'Arabic': sheetspilot.editor.arabic,
						'Hebrew': sheetspilot.editor.hebrew,
						'Turkish': sheetspilot.editor.turkish,
						'Hindi': sheetspilot.editor.hindi,
						'Indonesian': sheetspilot.editor.indonesian,
						'Vietnamese': sheetspilot.editor.vietnamese,
						'Thai': sheetspilot.editor.thai,
						'Greek': sheetspilot.editor.greek,
						'Custom': sheetspilot.editor.custom,
					}
				},
				{
					'title': sheetspilot.editor.custom_language,
					'type': 'text',
					'id': 'drawer_custom_language',
					'placeholder': sheetspilot.editor.custom_language_name

				},
				{
					'title': sheetspilot.editor.target_audience,
					'type': 'textarea',
					'id': 'drawer_target_audience',
					'placeholder': sheetspilot.editor.wine_enthusiast

				},
				{
					'title': sheetspilot.editor.brand_voice,
					'type': 'textarea',
					'id': 'drawer_brand_voice',
					'placeholder': sheetspilot.editor.placehodler_soph
				},

			];
			jQuery.each(settings, function (index, field) {
				var container = jQuery('<div>', {
					class: self.g_sideDraweInputContainerNoIndex,

				});
				var input_title = jQuery('<label>', {
					class: self.g_sideDraweInputTitleNoIndex,
					text: field.title,
				});
				/*
				var input_subtitle = jQuery('<p>', {
					class: self.g_sideDraweInputSubtitleNoIndex,
					text: single_field.subtitle
				});
				*/
				if (field.type == 'text') {
					var input = jQuery('<input>', {
						class: ' form-control ',
						type: 'text',
						id: field.id,
						placeholder: field.placeholder,
					});
				}
				if (field.type == 'textarea') {
					var input = jQuery('<textarea>', {
						class: ' form-control ',
						id: field.id,
						rows: 5,
						placeholder: field.placeholder,
					});
				}
				if (field.type == 'select') {
					var input = jQuery('<select>', {
						class: ' form-control ',
						id: field.id,
					});
					// add options
					jQuery.each(field.values, function (index, value) {
						var $option = jQuery('<option>', {
							value: index,
							text: value,
						});
						input.append($option);
					});

				}
				container.append(input_title, input);
				self.appendDrawerBody(container);

				if (field.type == 'select') {
					input.select2({
						width: '100%',
						dropdownParent: container,
						minimumResultsForSearch: 10
					});
				}
			})





			jQuery('#drawer_content_tone').val(jQuery('#ubai_contentrules_content_tone').val()).trigger('change');;
			jQuery('#drawer_content_language').val(jQuery('#ubai_contentrules_content_language').val()).trigger('change');;
			jQuery('#drawer_custom_language').val(jQuery('#ubai_contentrules_custom_language').val());
			jQuery('#drawer_target_audience').val(jQuery('#ubai_contentrules_target_audience').val());
			jQuery('#drawer_brand_voice').val(jQuery('#ubai_contentrules_brand_voice').val());

			var value = jQuery('#drawer_content_language').val();
			if (value == 'Custom') {
				jQuery('#drawer_custom_language').closest('.unlimitedai-plugin__side_drawer_input_container').show();
			} else {
				jQuery('#drawer_custom_language').closest('.unlimitedai-plugin__side_drawer_input_container').hide();
			}

			self.hideLoader();

		}

	}

	// edit product attributes
	editACFRepeater(pointer) {

		var self = this;
		var clicked = pointer;
		var parent_td = clicked.parents('td');
		var parent_tr = clicked.parents('tr');
		var field_name = jQuery('.' + this.g_editorContainerNoPrefix, parent_td).attr('data-column')
		var post_id = parent_tr.attr('data-id');

		self.g_objDrawer.addClass(this.g_drawerDownloadableEditClass);
		self.g_objDrawer.addClass(this.g_drawerDownloadableEditClassAttributes);
		self.g_objDrawer.addClass(this.g_drawerDownloadableEditClassRepeater);


		// verify if is pro
		if (self.verifyProCell(parent_td)) { return true; };

		self.onDrawerOpen();
		self.showLoader();

		var post_data = [];

		post_data.push({ 'data_table': 'postmeta', 'name': '_repeater_data' })

		var data =
		{
			post_id: post_id,
			filed_name: field_name,
			get_post_data: post_data
		}

		this.g_doublyAdmin.ajaxRequest('get_post_multidata', data, function (response) {

			self.dropDrawerBody();

			self.generateDrawerImageTitle(sheetspilot.editor.repeater_editor, parent_tr);

			var cancel_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} `,
				text: sheetspilot.editor.cancel,
				id: 'cancel_drawer_edition',
				click: function () {
					self.onDrawerClose();
				}
			});
			self.appendDrawerFooter(cancel_edition_button);

			var drawer_bottom_buttons = [
				{ 'id': 'save_drawer_edition', 'text': sheetspilot.editor.save, 'class': `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass}`, 'have_close': false },
				{ 'id': 'save_and_close_drawer_edition', 'text': sheetspilot.editor.save_and_close, 'class': `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} ${self.g_cellProcessingObj.g_generalBtnTextAccentClass}`, 'have_close': true },
			];

			jQuery.each(drawer_bottom_buttons, function (index, value) {
				self.appendDrawerFooter(
					jQuery('<button>', {
						class: value.class,
						text: value.text,
						id: value.id,
						click: function () {
							var button_self = jQuery(this);
							button_self.append(self.g_loaderProcessingIcon);

							var top_level_counter = jQuery('.top_level_repeater', self.g_pluginSideDrawerBody).length;
							// update cell
							self.g_cellProcessingObj.updateRepeaterCellCounter(parent_td, top_level_counter);

							var element_index = 0;
							var repeaterInfo = [];

							var repeater_block_counter = 0;

							// save tinymce
							tinymce.triggerSave();

							jQuery('.' + g_Repeater.repeaterFieldNoIndex + ':not(.repeater_no_childs)', self.g_pluginSideDrawerBody).each(function () {

								var block_parent_pointer = jQuery(this);
								jQuery(this).attr('data-blocknumber', repeater_block_counter);


								jQuery(this).addClass('repeater_block_counter_' + repeater_block_counter);

								// process top level repeaters
								jQuery('.field_row input, .field_row select, .field_row textarea', this).each(function () {

									//get parent container of that block
									var parent_block_of_this_element = jQuery(this).parents('.repeater_field:not(.repeater_no_childs)').parents('.repeater_field:not(.repeater_no_childs)').attr('data-blocknumber');

									let level = jQuery(this).parents('.' + g_Repeater.repeaterFieldNoIndex + ':not(.repeater_no_childs)').length;
									var repeater_name = jQuery(this).parents('.' + g_Repeater.repeaterFieldNoIndex + '.repeater_no_childs').attr('data-name');
									repeaterInfo.push({ 'block': repeater_block_counter, 'parent': parent_block_of_this_element, 'repeater_name': repeater_name, 'field_name': jQuery(this).attr('data-name'), 'value': jQuery(this).val() })
								})
								element_index++;
								repeater_block_counter++;

							})

							var data =
							{
								post_id: post_id,
								post_data: [{ 'data_table': 'postmeta', 'name': '_repeater_data', 'repeater_name': field_name, 'value': repeaterInfo }]
							};

							self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {

								button_self.html(sheetspilot.editor.save);
								if (value.have_close) {
									self.onDrawerClose();
								}

							});

						}
					})
				);
			})

			g_Repeater.generateRepeaterInterface(response.message.rowdata._repeater_data, post_id);


			self.hideLoader();

		});

	}

	// edit WYSWYG field
	editWYSIWYGField(pointer) {

		var self = this;
		var clicked = pointer;
		var parent_td = clicked.parents('td');
		var parent_tr = clicked.parents('tr');
		var field_name = jQuery('.' + this.g_editorContainerNoPrefix, parent_td).attr('data-column');
		var parent_td_index = parent_td.index();
		var column_name_tr = jQuery('tr th', self.g_objPostsEditor).eq(parent_td_index);

		var column_name = jQuery('.unlimitedai-plugin__th-title', column_name_tr).html();

		var post_id = parent_tr.attr('data-id');



		// verify if is pro
		if (self.verifyProCell(parent_td)) { return true; };

		self.onDrawerOpen();
		self.showLoader();

		var post_data = [];

		post_data.push({ 'data_table': 'postmeta', 'name': '_wysiwyg', 'field_name': field_name })

		var data =
		{
			post_id: post_id,
			filed_name: field_name,
			get_post_data: post_data
		}

		this.g_doublyAdmin.ajaxRequest('get_post_multidata', data, function (response) {

			self.dropDrawerBody();
			self.generateDrawerImageTitle(column_name, parent_tr);

			var cancel_edition_button = jQuery('<button>', {
				class: `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} `,
				text: sheetspilot.editor.cancel,
				id: 'cancel_drawer_edition',
				click: function () {
					self.onDrawerClose();
				}
			});
			self.appendDrawerFooter(cancel_edition_button);

			var drawer_bottom_buttons = [
				{ 'id': 'save_drawer_edition', 'text': sheetspilot.editor.save, 'class': `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass}`, 'have_close': false },
				{ 'id': 'save_and_close_drawer_edition', 'text': sheetspilot.editor.save_and_close, 'class': `${self.g_sideDraweInputCopyClipboardNoIndex} ${self.g_cellProcessingObj.g_generalBtnTextClass} ${self.g_cellProcessingObj.g_generalBtnTextAccentClass}`, 'have_close': true },
			];

			jQuery.each(drawer_bottom_buttons, function (index, value) {
				self.appendDrawerFooter(
					jQuery('<button>', {
						class: value.class,
						text: value.text,
						id: value.id,
						click: function () {
							var button_self = jQuery(this);
							button_self.append(self.g_loaderProcessingIcon);


							var element_index = 0;
							var repeaterInfo = [];

							var repeater_block_counter = 0;

							// save tinymce
							tinymce.triggerSave();



							var data =
							{
								post_id: post_id,
								post_data: [{ 'data_table': 'postmeta', 'name': '_wysiwyg', 'field_name': field_name, 'value': jQuery('#drawer_wysiwygeditor').val() }]
							};

							self.g_doublyAdmin.ajaxRequest('save_post_multidata', data, function (response) {

								// on save set preview
								var local_val = jQuery('<div>').html(jQuery('#drawer_wysiwygeditor').val()).text();;

								jQuery(self.g_cellProcessingObj.g_visualPart, parent_td).html(local_val.substr(0, 100));
								jQuery(self.g_cellProcessingObj.g_visualPart, parent_td).show();

								button_self.html(sheetspilot.editor.save);
								if (value.have_close) {
									self.onDrawerClose();
								}

							});

						}
					})
				);
			})
			var editorId = 'drawer_wysiwygeditor';
			var $textarea = jQuery('<textarea>', {
				class: 'form-control',
				id: 'drawer_wysiwygeditor'
			});
 
			self.appendDrawerBody($textarea.clone().val(response.message.rowdata._wysiwyg));

			// init tinemce
			if (tinymce.get(editorId)) {
				tinymce.get(editorId).remove();
			}

			var $res = wp.editor.initialize(editorId, {
				tinymce: {
					height: 300,
					menubar: false,
					plugins: 'charmap colorpicker compat3x directionality fullscreen hr image link lists media paste tabfocus textcolor wordpress wpautoresize wpeditimage wpemoji wpgallery wplink wptextpattern wpview',
					toolbar1: 'formatselect bold italic bullist numlist blockquote alignleft aligncenter alignright link unlink wp_more spellchecker fullscreen wp_adv',
					toolbar2: 'strikethrough hr forecolor pastetext removeformat charmap outdent indent undo redo wp_help'
				},
				quicktags: true
			});

			self.hideLoader();

		});

	}


	// verify if is pro
	verifyProCell(parent_td) {

		if (parent_td.hasClass('is_for_pro')) {
			this.currentModalObject = new SheetPilotSmallModal({});
			this.currentModalObject.setModalContent(jQuery('#sheetspilot_upgrade_to_pro_text').html());
			this.currentModalObject.showModal();
			return true;
		}
		return false;
	}

	// passing repeater recursiveley
	getInputsFromContainer($container) {
		let result = [];

		// 1. Берём только СВОИ input (не из вложенных container)
		$container.children('.input_cont').each(function () {
			const input = jQuery(this).find('input')[0];
			if (input) result.push(input);
		});

		// 2. Рекурсивно обходим вложенные container
		$container.children('.single.container').each(function () {
			result = result.concat(getInputsFromContainer(jQuery(this)));
		});

		return result;
	}
}