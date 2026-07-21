class SheetsPilot_AttributesEditor {

	constructor() {

		// classes
		this.g_doublyAdmin = new UniteAdminSheetsPilot();
		this.g_postEditorView = objPostsEditorView;
		this.g_cellProcessingObj = g_cellProcessingObj;


		// selectors
		this.singleAttributeSelectorNoIndex = 'single_attribute_selector';
		this.singleAttributeSelector = '.' + this.singleAttributeSelectorNoIndex;

		this.sideDrawerCollapseActionNoIndex = 'side_drawer_collapse_action';
		this.sideDrawerCollapseAction = '.' + this.sideDrawerCollapseActionNoIndex;

		this.sideDrawerAttributeContainerNoIndex = 'side_drawer_attribute_container';
		this.sideDrawerAttributeContainer = '.' + this.sideDrawerAttributeContainerNoIndex;

		this.sideDrawerBodyNoIndex = 'side_drawer_body';
		this.sideDrawerBody = '.' + this.sideDrawerBodyNoIndex;

		this.sideDrawerContainerFooterNoIndex = 'side_drawer_container_footer';
		this.sideDrawerContainerFooter = '.' + this.sideDrawerContainerFooterNoIndex;

		this.variationExpandButtonNoIndex = 'variation_expand_button';
		this.variationExpandButton = '.' + this.variationExpandButtonNoIndex;

		this.variationCloseButtonNoIndex = 'variation_close_button';
		this.variationCloseButton = '.' + this.variationCloseButtonNoIndex;

		this.addDefaultAttributeNoindex = 'add_default_attribute';
		this.addDefaultAttribute = '.' + this.addDefaultAttributeNoindex;

		this.sideDrawerInputInputNoIndex = 'side_drawer_input_input';
		this.sideDrawerInputInput = '.' + this.sideDrawerInputInputNoIndex;

		this.sideDrawerInputTextareaAttrValuesNoIndex = 'side_drawer_input_textarea_attr_values';
		this.sideDrawerInputTextareaAttrValues = '.' + this.sideDrawerInputTextareaAttrValuesNoIndex;
		
		this.sideDrawerCheckboxInputIsVisibleNoIndex = 'side_drawer_checkbox_input_is_visible';
		this.sideDrawerCheckboxInputIsVisible = '.' + this.sideDrawerCheckboxInputIsVisibleNoIndex;

		this.sideDrawerCheckboxInputUsedForVariationsNoIndex = 'side_drawer_checkbox_input_used_for_variations';
		this.sideDrawerCheckboxInputUsedForVariations = '.' + this.sideDrawerCheckboxInputUsedForVariationsNoIndex;

		this.addManualVariationNoIndex = 'add_manual_variation';
		this.addManualVariation = '.' + this.addManualVariationNoIndex;


	}

	initEvents() {
		var self = this;

		//this.generateInitialVariationSelectors();

		// acf_select related actions on click
		/*
		this.g_objPostsEditor.on("click", this.g_incellRelationEditor+'_external', (e) => {
			this.openPostTypeRelationsEditor(e);
		});	
		*/
		g_drawer.g_objDrawer.on('click', this.sideDrawerCollapseAction, function (e) {
			self.showHideAttrContent(e);
		})
		g_drawer.g_objDrawer.on('click', this.variationExpandButton, function (e) {
			self.expandAllAttrs(e);
		})
		g_drawer.g_objDrawer.on('click', this.variationCloseButton, function (e) {
			self.closeAllAttrs(e);
		})
 
		g_drawer.g_objDrawer.on('change', this.addDefaultAttribute, function (e) {
			
		})
		
	 

	}

	

	// make attributes sortable
	makeAttributesSortable(){
		var self = this;
		// make all attributes sortable
		jQuery('.unlimitedai-plugin__side-drawer__body').each(function () {

			const $container = jQuery(this);
			$container.sortable({
				items: self.sideDrawerAttributeContainer,
				axis: 'y',
				handle: '.side_drawer_header_row', // можно поменять на любой drag-handle
				tolerance: 'pointer',
				placeholder: 'ui-sortable-placeholder',
				forcePlaceholderSize: true,

				update: function (event, ui) {
					const order = [];

					$container.find( self.sideDrawerAttributeContainer ).each(function () {
						order.push( jQuery(this).data('id') || jQuery(this).index());
					});

				}
			});

		});	
	}

	// add new attribute in attribute editor
	addNewAttribute(e  ){
		if( jQuery(e.target).val() != '' ){
			this.generateAttributeBlock( jQuery(e.target).find('option:selected').text(), jQuery(e.target).find('option:selected').val(), [], false, false);			
		}
		
	}

	/** close all attributes */
	closeAllAttrs(e) {
		jQuery(this.sideDrawerCollapseAction).each(function () {
			if (jQuery(this).hasClass('is_opened')) {
				jQuery(this).trigger('click');
			}
		})
	}

	/** expand all attributes */
	expandAllAttrs(e) {
		jQuery(this.sideDrawerCollapseAction).each(function () {
			if (!jQuery(this).hasClass('is_opened')) {
				jQuery(this).trigger('click');
			}
		})
	}

	showHideAttrContent(e) {
		var current_item = jQuery(e.target);
		var parent_container = current_item.parents(this.sideDrawerAttributeContainer);
		if (!current_item.is(':button')) {
			current_item = current_item.parents('button');
		}
		if (current_item.hasClass('is_opened')) {
			current_item.removeClass('is_opened');
			current_item.html(`<svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>`);
			parent_container.find(this.sideDrawerBody).hide();
			parent_container.find(this.sideDrawerContainerFooter).hide();
		} else {
			current_item.addClass('is_opened');
			current_item.html(`<svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"></path></svg>`);
			parent_container.find(this.sideDrawerBody).show();
			parent_container.find(this.sideDrawerContainerFooter).show();
		}
	}

	generateAttributesInterface(existed_attributes) {

		var self = this;
		// control line
		var control_container = jQuery('<div>', {
			class: `${g_drawer.g_sideDraweRow} ${g_drawer.g_isFlex} gap-2 align-items-center`,
		});

		var control_button_container = jQuery('<div>', {
			class: `flex-shrink-0`,
		});

		var add_manually_btn = jQuery('<button>', {
			class: `${self.addManualVariationNoIndex} ${g_cellProcessingObj.g_generalBtnTextClass} height-32`,
			html: '<svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-3.5 h-3.5"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>' + sheetspilot.editor.add_new,
			click: function () {
				self.generateAttributeBlock( '', '', [], true,false );
			}
		});
		control_button_container.append(add_manually_btn);
		control_container.append(control_button_container);

		var control_button_container = jQuery('<div>', {
			class: `flex-shrink-0`,
		});
		var add_existing = jQuery('<select>', {
			class: `${self.addDefaultAttributeNoindex}`,
		});

		var $option = jQuery('<option>', {
			value: '',
			text: sheetspilot.editor.add_existing
		});
		add_existing.append($option);

		jQuery.each(existed_attributes._attributes.global_attributes, function (index, value) {
			var $option = jQuery('<option>', {
				value: value.slug,
				text: value.label
			});
			add_existing.append($option);
		})


		control_button_container.append(add_existing);

		add_existing.select2({
				minimumResultsForSearch: Infinity,
				width: '180px',
				dropdownParent:  control_button_container
		});

		control_container.append(control_button_container);


		var control_text_container = jQuery('<div>', {
			class: `text-right flex-grow-1 align-items-center`,
		});
		var vars_data_container = `<div class="expand_close_container">
		<a class="variation_expand_button">Expand</a> / <a class="variation_close_button">Close</a></div>`;
		control_text_container.append(vars_data_container);
		control_container.append(control_text_container);

		g_drawer.appendDrawerBody(control_container);

		 
		jQuery.each(existed_attributes._attributes.attributes_data, function (key, value) {
			self.generateAttributeBlock( value.name, key,  value.value, value.visible_on_product, value.used_for_variations );
		})

	}

	generateAttributeBlock(block_title, block_slug,  block_values, is_visible, used_for_variations) {

		var self = this;
		var container_wrap = jQuery('<div>', {
			class: `${this.sideDrawerAttributeContainerNoIndex}`,
		});
		var container_header = jQuery('<div>', {
			class: `side_drawer_header_row ${g_drawer.g_isFlex}`,
		});

		var container_header_col_2 = jQuery('<div>', {
			class: `is_flex align_items_center flex-grow-1`,
			text: 'Size'
		});

		var container_header_remove_action = jQuery('<a>', {
			class: `side_drawer_remove_action`,
			text: sheetspilot.editor.remove,
			click: function (e) {
				var pnt = jQuery(e.target);
				jQuery(e.target).parents(self.sideDrawerAttributeContainer).fadeOut(function () {
					jQuery(e.target).parents(self.sideDrawerAttributeContainer).replaceWith('');
				})
			}
		});
		var container_collapse_button = jQuery('<button>', {
			class: `side_drawer_collapse_action unlimitedai-plugin__btn is_opened`,
			html: `<span class="unlimitedai-plugin__btn-icon"><svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"></path></svg></span>`
		});

		var container_header_col_3 = jQuery('<div>', {
			class: `side_drawer_header_col_3 text-right is_flex align_items_center justify_content_end flex-shrink-0 gap-2`,
		});
		container_header_col_3.append(container_header_remove_action, container_collapse_button);


		var container_body = jQuery('<div>', {
			class: `side_drawer_body ${g_drawer.g_isFlex}`,
		});
		var container_body_left_col = jQuery('<div>', {
			class: `side_drawer_left_col flex-shrink-0`,
		});
		var container_body_right_col = jQuery('<div>', {
			class: `side_drawer_right_col flex-grow-1`,
		});
		var container_body_input_title = jQuery('<label>', {
			class: `side_drawer_label d-block`,
			text: sheetspilot.editor.name + ':'
		});
		var container_body_textarea_title = jQuery('<label>', {
			class: `side_drawer_label d-block`,
			text: sheetspilot.editor.values_attr
		});
		var container_body_input_input = jQuery('<input>', {
			class: `${this.sideDrawerInputInputNoIndex} mt-1 d-block`,
			type: `text`,
			value: block_title,
			'data-slug': block_slug,
		});
		var container_body_footer = jQuery('<div>', {
			class: `side_drawer_container_footer`,

		});
		var container_body_footer_2 = jQuery('<div>', {
			class: `side_drawer_container_footer`,

		});
 
		var container_body_input_textarea = jQuery('<textarea>', {
			class: `side_drawer_input_textarea ${this.sideDrawerInputTextareaAttrValuesNoIndex} mt-1 d-block`,
			rows: 3,
			html: block_values.join('|')
		});
		var container_body_checkbox_label = jQuery('<label>', {
			class: `side_drawer_checkbox_label`,
			text: sheetspilot.editor.visible_on_product_page
		});
		var container_body_checkbox_input = jQuery('<input>', {
			type: `checkbox`,
			class: `${this.sideDrawerCheckboxInputIsVisibleNoIndex}`,
			checked: is_visible
		});
		var container_body_checkbox_label_for_variations = jQuery('<label>', {
			class: `side_drawer_checkbox_label`,
			text: sheetspilot.editor.used_for_variations
		});
		var container_body_checkbox_input_for_variations = jQuery('<input>', {
			type: `checkbox`,
			class: `${this.sideDrawerCheckboxInputUsedForVariationsNoIndex}`,
			checked: used_for_variations
		});

		// creating element
		container_header.append(container_header_col_2, container_header_col_3);
		container_wrap.append(container_header);

		container_body_left_col.append(container_body_input_title, container_body_input_input);
		container_body_right_col.append(container_body_textarea_title, container_body_input_textarea);
		container_body_checkbox_label.prepend(container_body_checkbox_input);
		container_body_checkbox_label_for_variations.prepend(container_body_checkbox_input_for_variations);

		container_body_footer.append(container_body_checkbox_label);
		container_body_footer_2.append(container_body_checkbox_label_for_variations);

		container_body.append(container_body_left_col, container_body_right_col);
		container_wrap.append(container_header, container_body, container_body_footer, container_body_footer_2);
		container_wrap.hide();
		g_drawer.appendDrawerBody(container_wrap);
		container_wrap.fadeIn();

		// make sortable
		self.makeAttributesSortable();
	}

}