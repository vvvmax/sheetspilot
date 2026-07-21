class SheetsPilot_VariableProducts {

	constructor() {

		// classes
		this.g_doublyAdmin = new UniteAdminSheetsPilot();
		this.g_postEditorView = objPostsEditorView;
		this.g_cellProcessingObj = g_cellProcessingObj;


		// selectors
		this.singleAttributeSelectorNoIndex = 'single_attribute_selector';
		this.singleAttributeSelector = '.' + this.singleAttributeSelectorNoIndex;

		this.variableAttributeSelectorsContainerNoIndex = 'variable_attribute_selectors_container';
		this.variableAttributeSelectorsContainer = '.' + this.variableAttributeSelectorsContainerNoIndex;

		this.g_sideDraweControlButtonContNoIndex = 'variable_control_button_container';
		this.g_sideDraweControlButtonCont = '.' + this.g_sideDraweControlButtonContNoIndex;

		this.g_sideDraweControlRightBlockContNoIndex = 'variable_control_text_container';
		this.g_sideDraweControlRightBlockCont = '.' + this.g_sideDraweControlRightBlockContNoIndex;

		this.singleBgBlockNoIndex = 'single_bg_block';
		this.singleBgBlock = '.' + this.singleBgBlockNoIndex;

		this.removeSingleVariationNoIndex = 'remove_single_variation ';
		this.removeSingleVariation = '.' + this.removeSingleVariationNoIndex;

		this.singleVariationBlockNoIndex = 'single_variation_block';
		this.singleVariationBlockNoIndexItem = 'single_variation_block_item';
		this.singleVariationBlockNoIndexItemNum = 'single_variation_block_item_num';
		this.singleVariationBlock = '.' + this.singleVariationBlockNoIndex;

		this.variationBodyContainerNoIndex = 'variation_body_container';
		this.variationBodyContainer = '.' + this.variationBodyContainerNoIndex;

		this.editSingleVariationNoIndex = 'edit_single_variation';
		this.editSingleVariation = '.' + this.editSingleVariationNoIndex;

		this.noPricesForVariationsNoIndex = 'no_prices_for_variations';
		this.noPricesForVariations = '.' + this.noPricesForVariationsNoIndex;

		this.addManualVariationNoIndex = 'add_manual_variation';
		this.addManualVariation = '.' + this.addManualVariationNoIndex;

		this.variationsListContainerNoIndex = 'variations_list_container';
		this.variationsListContainer = '.' + this.variationsListContainerNoIndex;

		this.deleteFileNoIndex = 'delete_file';
		this.chooseFileNoIndex = 'choose_file';
		this.variationFeaturedImageBlockNoIndex = 'variation_featured_image_block';


		this.currentModalObject;
	}

	initEvents() {
		var self = this;


		g_drawer.objPluginSideDrawer.on("click", this.removeSingleVariation, (e) => {
			e.preventDefault();
			this.removeSingleVariationFn(e);
		});

		g_drawer.objPluginSideDrawer.on("click", this.editSingleVariation, (e) => {
			e.preventDefault();
			var parent_container = jQuery(e.target).parents(this.singleVariationBlock);
			jQuery(this.variationBodyContainer, parent_container).fadeToggle();
		});

		g_drawer.objPluginSideDrawer.on('click', '.' + this.deleteFileNoIndex, function (e) {
			jQuery(e.target).parents('.single_file_row').fadeOut(function () {
				jQuery(e.target).parents('.single_file_row').replaceWith('');
			})
		})

		g_drawer.objPluginSideDrawer.on('click', '.' + this.chooseFileNoIndex, function (e) {
			self.downloadableFileUpload(e);
		})

		// show hide downloadable
		g_drawer.objPluginSideDrawer.on('change', '.downloadable', function (e) {

			var parent_container = jQuery(e.target).parents(this.singleVariationBlock);
			if (jQuery(this).is(':checked')) {
				jQuery('.downloads_container', parent_container).show();
			} else {
				jQuery('.downloads_container', parent_container).hide();
			}
		})

		// show hide virtual
		g_drawer.objPluginSideDrawer.on('change', '.virtual', function (e) {
			var parent_container = jQuery(e.target).parents(this.singleVariationBlock);
			if (jQuery(this).is(':checked')) {
				jQuery('.hide_for_virtual', parent_container).hide();
			} else {
				jQuery('.hide_for_virtual', parent_container).show();
			}
		})

		// show hide show stock 
		g_drawer.objPluginSideDrawer.on('change', '.manage_stock', function (e) {
			var parent_container = jQuery(e.target).parents(this.singleVariationBlock);
			if (jQuery(this).is(':checked')) {
				jQuery('.hide_for_stock_manage', parent_container).hide();
				jQuery('.show_for_stock_manage', parent_container).show();
			} else {
				jQuery('.hide_for_stock_manage', parent_container).show();
				jQuery('.show_for_stock_manage', parent_container).hide();
			}
		})

		// set featured image
		g_drawer.objPluginSideDrawer.on('click', '.' + this.variationFeaturedImageBlockNoIndex, function (e) {
			self.setVariationFeaturedImage(e);
		})

		// expand all variation
		g_drawer.objPluginSideDrawer.on('click', '.variation_expand_button', function (e) {
			self.expandAllVariations(e);
		})

		//  collapse all variations
		g_drawer.objPluginSideDrawer.on('click', '.variation_close_button', function (e) {
			self.collpaseAllVariations(e);
		})

		//  setprice for no price
		jQuery(document).on('click', '#variation_no_price_button', function (e) {
			self.setNoPricePrices(e, self);
		})
	}



	// functions

	recalculateVariationsNumber(){		
		jQuery('.variations_number').html( jQuery(this.singleVariationBlock).length );

		// get no price items
		var no_price_counter = 0;
		jQuery(this.variationBodyContainer, g_drawer.objPluginSideDrawer).each(function () {
			if (jQuery('._regular_price', this).val() == '' && jQuery('._sale_price', this).val() == '') {
				no_price_counter++;
			}

		})
		jQuery('.no_price_counter').html( no_price_counter );
		if( no_price_counter > 0 ){
			jQuery(this.noPricesForVariations).fadeIn();
		}else{
			jQuery(this.noPricesForVariations).fadeOut();
		}
	}

	// set no price items
	setNoPricePrices(e, self) {
		jQuery(this.variationBodyContainer, g_drawer.objPluginSideDrawer).each(function () {
			if (jQuery('._regular_price', this).val() == '' && jQuery('._sale_price', this).val() == '') {
				jQuery('._regular_price', this).val(jQuery('#variation_no_price_input').val());
			}

		})
		jQuery(self.noPricesForVariations).fadeOut();
		this.currentModalObject = new SheetPilotSmallModal({});
		this.currentModalObject.hideModal();
		jQuery('#save_drawer_edition').click();
	}


	expandAllVariations() {
		jQuery(this.variationBodyContainer, g_drawer.objPluginSideDrawer).fadeToggle();
	}
	collpaseAllVariations() {
		jQuery(this.variationBodyContainer, g_drawer.objPluginSideDrawer).fadeToggle();
	}

	setVariationFeaturedImage(e) {
		var file_frame;
		let attachment;
		var this_block = jQuery(e.target);

		if (file_frame) {
			file_frame.open();
			return;
		}

		// Create the media frame.		
		file_frame = wp.media.frames.file_frame = wp.media({

			multiple: false  // Set to true to allow multiple files to be selected
		});

		// When an image is selected, run a callback.
		file_frame.on('select', function () {
			attachment = file_frame.state().get('selection').first().toJSON();
			this_block.css('background-image', 'url(' + attachment.url + ')');
			this_block.attr('data-id', attachment.id);


		});

		// Finally, open the modal
		file_frame.open();
	}


	downloadableFileUpload(e) {
		var file_frame;
		let attachment;
		var parent_row = jQuery(e.target).parents('.single_file_row');

		if (file_frame) {
			file_frame.open();
			return;
		}

		// Create the media frame.		
		file_frame = wp.media.frames.file_frame = wp.media({
	
			multiple: false  // Set to true to allow multiple files to be selected
		});

		// When an image is selected, run a callback.
		file_frame.on('select', function () {
			attachment = file_frame.state().get('selection').first().toJSON();
			jQuery('.file_url', parent_row).val(attachment.url);
			jQuery('.file_url', parent_row).attr('data-id', attachment.id);


		});

		// Finally, open the modal
		file_frame.open();
	}

	removeSingleVariationFn(e) {
		var self = this;
		var this_pnt = jQuery(e.target).parents(this.singleVariationBlock);
		var variation_id = this_pnt.attr('data-id');
		this_pnt.fadeOut(function () {

			var data =
				{
					post_id: variation_id
				}
			 
				g_drawer.g_doublyAdmin.ajaxRequest('remove_single_variation', data, function (response) {

				})
			

			this_pnt.replaceWith('');
			self.recalculateVariationsNumber();
		});
	}

	// helpres
	generateInitialVariationSelectors(variable_selectors) {

		var self = this;

		var container = jQuery('<div>', {
			class: `${g_drawer.g_sideDraweInputContainerNoIndex} `,
			html: sheetspilot.editor.default_variations
		});
		g_drawer.appendDrawerBody(container);


		// variables list
		var vars_container = jQuery('<div>', {
			class: `${g_drawer.g_sideDraweInputContainerNoIndex}  ${this.variableAttributeSelectorsContainerNoIndex}`,
		});
		jQuery.each(variable_selectors.attributes_data, function (index, value) {

			if( value.used_for_variations == false ){ return true; }

			var $select = jQuery('<select>', {
				class: self.singleAttributeSelectorNoIndex + '  single_attribute_selector_' + index.toLowerCase(),
				'data-attrname': index.toLowerCase()
			});

			var $option = jQuery('<option>', {
				value: '',
				text: value.name,
			});
			$select.append($option);

			// add options
			value.value.forEach(function (opt) {
				var $option = jQuery('<option>', {
					value: opt,
					text: opt,
				});
				$select.append($option);
			});

			vars_container.append($select);
		})

		g_drawer.appendDrawerBody(vars_container);

		vars_container.find('select').select2({
			dropdownParent: vars_container,
			dropdownCssClass: 'uai_select2_dropdown',
			selectionCssClass: 'uai_select2_selection',
			width: '100%',
			minimumResultsForSearch: Infinity,
		});

		//hide container if no children inside
		var childrenNum = vars_container.children().length;

		if(childrenNum == 0)
			vars_container.hide();

		// control line
		var control_container = jQuery('<div>', {
			class: `${g_drawer.g_sideDraweInputContainerNoIndex} ${this.variableAttributeSelectorsContainerNoIndex}`,
		});

		var control_button_container = jQuery('<div>', {
			class: this.g_sideDraweControlButtonContNoIndex,
		});
		var control_text_container = jQuery('<div>', {
			class: `${this.g_sideDraweControlRightBlockContNoIndex} flex-grow-1 d-flex align-items-center justify-content-end`,
		});

		var generate_variation = jQuery('<button>', {
			class: `${self.geneate_variation_button} ${g_cellProcessingObj.g_generalBtnTextClass} ${g_cellProcessingObj.g_generalDarkBtnTextClass} `,
			html: '<svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72"></path><path d="m14 7 3 3"></path><path d="M5 6v4"></path><path d="M19 14v4"></path><path d="M10 2v2"></path><path d="M7 8H3"></path><path d="M21 16h-4"></path><path d="M11 3H9"></path></svg>' + sheetspilot.editor.generate_variation,

			click: function () {
				var button_self = jQuery(this);
				button_self.append(g_drawer.g_loaderProcessingIcon);

				var post_data = [];
				post_data.push({ 'data_table': 'postmeta', 'name': '_attributes' })
				var data =
				{
					post_id: variable_selectors.product_id,
					get_post_data: post_data
				}
			 
				g_drawer.g_doublyAdmin.ajaxRequest('generate_all_variations', data, function (response) {

					jQuery(self.variationsListContainer).html('');
					//var reversed = response.message.rowdata._attributes.variations_info.reverse(); 
					jQuery.each( response.message.rowdata._attributes.variations_info , function (index, value) {
							self.generateSingleVariation( response.message.rowdata._attributes.attributes_data, value);
					})
					jQuery( g_drawer.g_drawerSaveLoader ).replaceWith('');
					self.recalculateVariationsNumber();
				})
			}
		});
		control_button_container.append(generate_variation);
		control_container.append(control_button_container);

		var add_manually_btn = jQuery('<button>', {
			class: `${self.add_manual_variation} ${g_cellProcessingObj.g_generalBtnTextClass} `,
			html: '<svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>' + sheetspilot.editor.add_manually,
			click: function () {
				var button_self = jQuery(this);
				button_self.append(g_drawer.g_loaderProcessingIcon);

				var post_data = [];
				post_data.push({ 'data_table': 'postmeta', 'name': '_attributes' })
				var data =
				{
					post_id: variable_selectors.product_id,
					get_post_data: post_data
				}
			 
				g_drawer.g_doublyAdmin.ajaxRequest('manual_add_variation', data, function (response) {
 
					var cnt = 0;
					var reversed = response.message.rowdata._attributes.variations_info.reverse(); 
					jQuery.each( reversed, function (index, value) {
						if( cnt == 0 ){
							self.generateSingleVariation( response.message.rowdata._attributes.attributes_data, value);
						}
						cnt++;
					})
					jQuery( g_drawer.g_drawerSaveLoader ).replaceWith('');
					self.recalculateVariationsNumber();
				})
			}
		});


		control_button_container = jQuery('<div>', {
			class: this.g_sideDraweControlButtonContNoIndex,
		});

		control_button_container.append(add_manually_btn);
		control_container.append(control_button_container);
 
		var variations_info = variable_selectors.variations_info;
		var vars_data_container = `<div class="variable_control_text_container_item">
		<span class="variations_number">${variations_info.length}</span> variations<span class="var_data_block">
		(<a class="variation_expand_button cursor-pointer">Expand</a> / <a class="variation_close_button cursor-pointer">Close</a>)</span>
		</div>`;
		control_text_container.append(vars_data_container);
		control_container.append(control_text_container);

		g_drawer.appendDrawerBody(control_container);

		// no prices block
		var control_no_price = jQuery('<div>', {
			class: `${g_drawer.g_sideDraweInputContainerNoIndex}`,
		});
		var no_price_container = jQuery('<div>', {
			class: `${this.singleBgBlockNoIndex} ${this.noPricesForVariationsNoIndex} ${this.variableAttributeSelectorsContainerNoIndex} ${g_drawer.g_isFlex} flex-nowrap`,
		});
		var no_price_text_block = jQuery('<div>', {
			class: `flex-grow-1`,
			html: sheetspilot.editor.no_price_text.replace('%', `<span class="no_price_counter">${variable_selectors.no_price_attributes}</span>`)
		});
		var no_price_btn_block = jQuery('<div>', {
			class: `d_flex justify_content_end d_flex flex-shrink-0`,
		});
		var add_price_btn = jQuery('<button>', {
			class: `${self.addPriceBtn}  ${g_cellProcessingObj.g_generalBtnTextClass}`,
			html: sheetspilot.editor.add_price,
			click: function () {

				var modal_data = {
					'title': sheetspilot.editor.set_variation_price,
					'subtitle': sheetspilot.editor.enter_variation_price,
					'placeholder': '',
					'button_text': sheetspilot.editor.set_price,
					'input_id': 'variation_no_price_input',
					'button_id': 'variation_no_price_button',
					'input_type': 'number',
				};
				this.currentModalObject = new SheetPilotSmallModal(modal_data);
				this.currentModalObject.showModal();
			}
		});
		no_price_btn_block.append(add_price_btn);

		no_price_container.append(no_price_text_block, no_price_btn_block);

		g_drawer.appendDrawerBody(no_price_container);

		var variations_list_container = jQuery('<div>', {
			class: `${this.variationsListContainerNoIndex} `,
		});
		g_drawer.appendDrawerBody(variations_list_container);

		jQuery.each(variable_selectors.variations_info, function (index, value) {
			self.generateSingleVariation(variable_selectors.attributes_data, value);
		})

		// emulate checkbox changes
		jQuery('.downloadable').each(function () {
			var parent_container = jQuery(this).parents(self.singleVariationBlock);
			if (jQuery(this).is(':checked')) {
				jQuery('.downloads_container', parent_container).show();
			} else {
				jQuery('.downloads_container', parent_container).hide();
			}
		})
		jQuery('.virtual').each(function () {
			var parent_container = jQuery(this).parents(self.singleVariationBlock);
			if (jQuery(this).is(':checked')) {
				jQuery('.hide_for_virtual', parent_container).hide();
			} else {
				jQuery('.hide_for_virtual', parent_container).show();
			}
		})
		jQuery('.manage_stock').each(function () {
			var parent_container = jQuery(this).parents(self.singleVariationBlock);
			if (jQuery(this).is(':checked')) {
				jQuery('.hide_for_stock_manage', parent_container).hide();
				jQuery('.show_for_stock_manage', parent_container).show();
			} else {
				jQuery('.hide_for_stock_manage', parent_container).show();
				jQuery('.show_for_stock_manage', parent_container).hide();
			}
		})

		
	}

	generateSingleVariation(attributes_info, variation_info) {
		var self = this;

		//single variation block
		var variation_container = jQuery('<div>', {
			class: `border border-radius-8 mb-10 ${this.singleVariationBlockNoIndex}`,
			'data-id': variation_info.data.variation_id
		});
		var variation_header = jQuery('<div>', {
			class: `${this.singleVariationBlockNoIndexItem} bg-light-gray is_flex align-items-center`,
		});
		var variation_left_col = jQuery('<div>', {
			class: `${this.singleVariationBlockNoIndexItemNum} flex-shrink-0 fs-14 text-center`,
			html: '#' + variation_info.data.variation_id
		});
		var variation_right_col = jQuery('<div>', {
			class: `flex-shrink-0 is_flex gap-2`,
		});
		var variation_right_col_remove = jQuery('<a>', {
			class: `text-red remove_single_variation fs-12`,
			href: '#',
			text: sheetspilot.editor.remove
		});
		var variation_right_col_edit = jQuery('<a>', {
			class: `text-gray edit_single_variation fs-12`,
			href: '#',
			text: sheetspilot.editor.edit
		});
		var variation_header_content_col = jQuery('<div>', {
			class: `flex-grow-1 is_flex`,
		});

		// fill in selects
		jQuery.each(attributes_info, function (index, value) {
		 
			if( value.used_for_variations == false ){ return true; }

			var $select = jQuery('<select>', {
				class: `flex_1 variation_attribute_selector variation_attribute_selector_` + index.toLowerCase(),
				'data-attrname': index.toLowerCase()
			});

			var $option = jQuery('<option>', {
				value: '',
				text: value.name,
			});
			$select.append($option);

			// add options
			value.value.forEach(function (opt) {
				var $option = jQuery('<option>', {
					value: opt,
					text: opt,
					selected: (variation_info.attributes[index.toLowerCase()] == opt) ? true : false
				});
				$select.append($option);
			});

			variation_header_content_col.append($select);
		})



		// assemble header

		variation_right_col.append(variation_right_col_remove, variation_right_col_edit);

		//variation_header_content_col.append ( variation_header_dummy_select.clone(), variation_header_dummy_select.clone(), variation_header_dummy_select.clone() );

		variation_header.append(variation_left_col, variation_header_content_col, variation_right_col);

		// assemble body
		var variation_body = jQuery('<div>', {
			class: `border-top ${this.variationBodyContainerNoIndex} p-3`,
			css: {
				display: 'none'
			}
		});

		var body_row = jQuery('<div>', {
			class: `body-row is_flex mb-3 gap-3`,
		});

		var body_col = jQuery('<div>', {
		});

		var body_col_l = jQuery('<div>', {
			class: `flex-shrink-0`,
		});
		var image_place = jQuery('<div>', {
			class: `image_place`,
		});
		body_col_l.append(image_place);


		var body_col_r = jQuery('<div>', {
			class: `flex-grow-1`,
		});

		var input_block_label = jQuery('<label>', {
			class: `fs-14 is_flex mb-1 variation_body_container_label`,
		});
		var input_block_input = jQuery('<input>', {
			class: `fs-14 style_input variation_body_container_input`,
		});




		var select_block_input = jQuery('<select>', {
			class: `style_input`
		});


		var textarea_block_input = jQuery('<textarea>', {
			class: `style_textarea`,
			rows: 5
		});
		var hr = jQuery('<hr>', {
			class: `my-3`,
		});


		var input_check_label = jQuery('<label>', {
			class: `fs-12 flex-shrink-0 side_drawer_checkbox_label`,
		});
		var input_checkbox = jQuery('<input>', {
			type: `checkbox`,
			class: `mr-5`
		});
		var button = jQuery('<button>', {
			type: `button`,
			class: `unlimitedai-plugin__text-btn unlimitedai-plugin__text-btn--dark`
		});

		var fatured_image_block = jQuery('<div>', {
			class: this.variationFeaturedImageBlockNoIndex,
			css: {
				'background-image': 'url(' + variation_info.data.featured_image + ')'
			},
			'data-id': variation_info.data.featured_image_id
		});
		body_col_l.append(fatured_image_block);
		body_col_r.append(input_block_label.clone().html(sheetspilot.editor.sku), input_block_input.clone().addClass('_sku').val(variation_info.meta._sku));
		body_col_r.append(input_block_label.clone().html(sheetspilot.editor.gtin), input_block_input.clone().addClass('_global_unique_id').val(variation_info.meta._global_unique_id));
		var cloned_body = body_row.clone().append(body_col_l, body_col_r);
		variation_body.append(cloned_body);

		var new_body_row = body_row.clone().append(
			input_check_label.clone().text(sheetspilot.editor.enabled).prepend(input_checkbox.clone().addClass('enabled').prop('checked', (variation_info.data.enabled == 'publish') ? true : false)),
			input_check_label.clone().text(sheetspilot.editor.downloadable).prepend(input_checkbox.clone().addClass('downloadable').prop('checked', (variation_info.meta._downloadable == 'yes') ? true : false)),
			input_check_label.clone().text(sheetspilot.editor.virtual).prepend(input_checkbox.clone().addClass('virtual').prop('checked', (variation_info.meta._virtual == 'yes') ? true : false)),
			input_check_label.clone().text(sheetspilot.editor.manage_stock).prepend(input_checkbox.clone().addClass('manage_stock').prop('checked', (variation_info.meta._manage_stock == 'yes') ? true : false)),
		);
		variation_body.append(new_body_row);
		variation_body.append(hr);

		// price row
		var cloned_body = body_row.clone().append(
			body_col.clone().addClass(`flex-grow-1`).append(input_block_label.clone().html(sheetspilot.editor.regular_price), input_block_input.clone().addClass('_regular_price').val(variation_info.meta._regular_price)),
			body_col.clone().addClass(`flex-grow-1`).append(input_block_label.clone().html(sheetspilot.editor.sale_price), input_block_input.clone().addClass('_sale_price').val(variation_info.meta._sale_price)),
		);
		variation_body.append(cloned_body);

		// manage stock row
		var cloned_body = body_row.clone().addClass('show_for_stock_manage row row-cols-2 g-3').removeClass('gap-3').append(
			body_col.clone().addClass(`flex-grow-1 col`).append(input_block_label.clone().html(sheetspilot.editor.stock_quantitiy), input_block_input.clone().addClass('_stock').val(variation_info.meta._stock)),
			body_col.clone().addClass(`flex-grow-1 col`).append(input_block_label.clone().html(sheetspilot.editor.allow_backorders),
				select_block_input.clone().addClass('allow_backorders').append(jQuery('<option>', { value: 'no', text: sheetspilot.editor.do_not_allow, selected: (variation_info.meta._backorders == 'no') ? true : false }))
					.append(jQuery('<option>', { value: 'notify', text: sheetspilot.editor.allow_but_notify, selected: (variation_info.meta._backorders == 'notify') ? true : false }))
					.append(jQuery('<option>', { value: 'yes', text: sheetspilot.editor.allow, selected: (variation_info.meta._backorders == 'yes') ? true : false }))
			),
		);
		variation_body.append(cloned_body);
		// low stock threshold
		var cloned_body = body_row.clone().addClass('show_for_stock_manage').append(
			body_col.clone().addClass(`flex-shrink-0 `).append(input_block_label.clone().html(sheetspilot.editor.low_stock_threshold), input_block_input.clone().addClass('_low_stock_amount').val(variation_info.meta._low_stock_amount)),

		);
		variation_body.append(cloned_body);

		var cloned_body = body_row.clone().addClass('hide_for_stock_manage').append(
			body_col.clone().addClass(`flex-shrink-0`).append(
				input_block_label.clone().html(sheetspilot.editor.stock_status),
				select_block_input.clone().addClass('stock_status').append(jQuery('<option>', { value: 'instock', text: sheetspilot.editor.in_stock, selected: (variation_info.meta._stock_status == 'instock') ? true : false }))
					.append(jQuery('<option>', { value: 'outofstock', text: sheetspilot.editor.out_of_stock, selected: (variation_info.meta._stock_status == 'outofstock') ? true : false }))
					.append(jQuery('<option>', { value: 'onbackorder', text: sheetspilot.editor.on_backorder, selected: (variation_info.meta._stock_status == 'onbackorder') ? true : false }))
			),
		);
		variation_body.append(cloned_body);
		variation_body.append(hr);

		var cloned_dimension = body_row.clone().append(
			input_block_input.clone().addClass('_length').val(variation_info.meta._length),
			input_block_input.clone().addClass('_width').val(variation_info.meta._width),
			input_block_input.clone().addClass('_height').val(variation_info.meta._height),
		);

		var cloned_body = body_row.clone().addClass('hide_for_virtual row row-cols-2 g-3').removeClass('gap-3').append(
			body_col.clone().addClass(`col`).append(input_block_label.clone().html(sheetspilot.editor.weight), input_block_input.clone().addClass('_weight').val(variation_info.meta._weight)),
			body_col.clone().addClass(`col`).append(input_block_label.clone().html(sheetspilot.editor.dimensions), cloned_dimension),
		);
		variation_body.append(cloned_body);

		var cloned_body = body_row.clone().append(
			body_col.clone().addClass(`flex-grow-1 hide_for_virtual`).append(input_block_label.clone().html(sheetspilot.editor.shipping_class), select_block_input.clone().addClass('shipping_class')),
		);


		variation_body.append(cloned_body);
		var cloned_body = body_row.clone().append(
			body_col.clone().addClass(`flex-grow-1`).append(
				input_block_label.clone().html(sheetspilot.editor.tax_class),
				select_block_input.clone().addClass('tax_class').append(jQuery('<option>', { value: 'parent', text: sheetspilot.editor.same_as_parent, selected: (variation_info.meta.tax_class == 'parent') ? true : false })).append(jQuery('<option>', { value: '', text: sheetspilot.editor.standard, selected: (variation_info.meta.tax_class == '') ? true : false })).append(jQuery('<option>', { value: 'reduced-rate', text: sheetspilot.editor.reduced_rate, selected: (variation_info.meta.tax_class == 'reduced-rate') ? true : false })).append(jQuery('<option>', { value: 'zero-rate', text: sheetspilot.editor.zero_rate, selected: (variation_info.meta.tax_class == 'zero-rate') ? true : false }))
			),
		);
		variation_body.append(cloned_body);

		var cloned_body = body_row.clone().append(
			body_col.clone().addClass(`flex-grow-1`).append(input_block_label.clone().html(sheetspilot.editor.description), textarea_block_input.clone().addClass('description').val(variation_info.meta._variation_description)),
		);
		variation_body.append(cloned_body);

		// Downloadable 
		var body_row_files_cont = body_row.clone().removeClass('is_flex').addClass('downloads_container');

		var download_head = body_row.clone().removeClass('mb-10').append(
			body_col.clone().addClass(`flex-grow-1`).append(input_block_label.clone().removeClass('mb-10').html(sheetspilot.editor.name)),
			body_col.clone().addClass(`flex-grow-1`).append(input_block_label.clone().removeClass('mb-10').html(sheetspilot.editor.file_url)),
		);
		body_row_files_cont.append(download_head);

		var files_list_container = body_row.clone().removeClass('is_flex').addClass('files_rows_container');

		var choose_file_button = button.clone().addClass('choose_file').html(sheetspilot.editor.choose_file);
		choose_file_button.on('click', function (e) {

		})
		var delete_file_buton = button.clone().addClass('delete_file').html(sheetspilot.editor.delete);


		var download_single_item = body_row.clone().addClass('single_file_row').append(
			body_col.clone().addClass(`flex-shrink-0`).append(input_block_input.clone().addClass('file_name')),
			body_col.clone().addClass(`flex-shrink-0`).append(input_block_input.clone().addClass('file_url')),
			body_col.clone().addClass(`flex-shrink-0`).append(choose_file_button),
			body_col.clone().addClass(`flex-shrink-0`).append(delete_file_buton),
		);
		jQuery.each(variation_info.meta._downloadable_files, function (index, value) {
			var cloned_row = download_single_item.clone();
			jQuery('.file_name', cloned_row).val(value.name)
			jQuery('.file_url', cloned_row).val(value.file)

			files_list_container.append(cloned_row);
		})

		body_row_files_cont.append(files_list_container);



		var add_file_button = body_col.clone().addClass(`flex_1 text-right`).append(
			button.clone().addClass('add_file_input').html(sheetspilot.editor.add_file)
		);
		add_file_button.on('click', function () {
			var cloned_row = download_single_item.clone();
			cloned_row.show();
			jQuery('input', cloned_row).val('');
			files_list_container.append(cloned_row);
		})
		var add_single_item = body_row.clone().append(
			add_file_button
		);
		body_row_files_cont.append(add_single_item);

		// Downloadable END


		// downloadable attrs
		var cloned_body = body_row.clone().append(
			body_col.clone().addClass(`flex-grow-1`).append(input_block_label.clone().html(sheetspilot.editor.download_limit), input_block_input.clone().addClass('download_limit').val(variation_info.meta._download_limit)),
			body_col.clone().addClass(`flex-grow-1`).append(input_block_label.clone().html(sheetspilot.editor.download_expiry), input_block_input.clone().addClass('download_expiry').val(variation_info.meta._download_expiry)),
		);
		body_row_files_cont.append(cloned_body);
		variation_body.append(body_row_files_cont);

		//######
		variation_container.append(variation_header, variation_body);
 
		jQuery( self.variationsListContainer ).prepend( variation_container );


	}



}