(function ($) {

	class SheetsPilotRepeaterEditor {

		constructor() {

			// classes
			this.g_doublyAdmin = new UniteAdminSheetsPilot();
			this.g_postEditorView = objPostsEditorView;
			this.g_cellProcessingObj = g_cellProcessingObj;

			this.drawerBody = '.unlimitedai-plugin__side-drawer__body';
			this.blockRemoveButtonNoIndex = 'block_remove_button';
			this.blockCopyButtonNoIndex = 'block_copy_button';
			this.blockDragButtonNoIndex = 'block_drag_button';
			this.blockShowHideButtonNoIndex = 'block_show_hide_button';

			this.blockAddRepeaterButton = 'block_add_repeater_button';
			this.blockAddRepeaterButtonRow = 'block_add_repeater_button_row';
			this.repeaterFieldNoIndex = 'repeater_field';
			this.topBlockAddButtonNoIndex = 'top_block_add_button';
			this.topBlockAddBtnNoIndexOrigin = 'unlimitedai-plugin__drawer-downloadable_edit-repeater-add-btn';
			this.topBlockNoItemsNoIndex = 'unlimitedai-plugin__drawer-downloadable_edit-repeater-no-items';
			this.topLevelRepeaterNoIndex = 'top_level_repeater';
			this.topLevelRepeaterHeaderNoIndex = 'top_level_repeater_header';
			this.topLevelRepeaterHeaderInnerNoIndex = 'top_level_repeater_header_inner';
			this.topLevelRepeaterHeaderButtonsNoIndex = 'top_level_repeater_header_buttons';
			this.topLevelRepeaterHeaderNavNoIndex = 'top_level_repeater_header_nav';
			this.topLevelRepeaterHeaderNavTitleNoIndex = 'top_level_repeater_header_nav_title';
			this.topLevelRepeaterHeaderNavTitleNumNoIndex = 'top_level_repeater_header_nav_title_num';
			this.topLevelRepeaterContentNoIndex = 'top_level_repeater_content';
			this.topLevelRepeaterContentFieldLabelNoIndex = 'top_level_repeater_content_label';
			this.topLevelRepeaterContentFieldInputNoIndex = 'top_level_repeater_content_input';

			this.level = 0;
			this.index_counter = 0;
			this.repeater_header;
			this.repeater_content;
			this.repeater_add;

			this.repeaterStructure;
			this.repeaterValues;

			this.repeaterSupportedFields = [ 'text', 'select', 'textarea', 'number', 'email', 'url', 'password', 'checkbox', 'radio', 'range', 'color_picker', 'wysiwyg', 'repeater' ];
		}

		initEvents() {
			var self = this;

			// on block remove
			$(this.drawerBody).on('click', '.' + self.blockRemoveButtonNoIndex + ' svg ', function () {
				var objNoItems = $(self.drawerBody).find(`.${self.topBlockNoItemsNoIndex}`);
				var fieldsNum = $(self.drawerBody).find('.' + self.repeaterFieldNoIndex).length;

				if (!fieldsNum || fieldsNum == 0 || fieldsNum == 1)
					objNoItems.show();

				var parent = $(this).closest('.' + self.repeaterFieldNoIndex);
				parent.hide();
				parent.replaceWith('');
				self.refreshItemNumbers();
			})

			// on add top block
			$(this.drawerBody).on('click', '.' + self.topBlockAddButtonNoIndex, function () {
				var $clickedButton = $(this)
				var objNoItems = $(self.drawerBody).find(`.${self.topBlockNoItemsNoIndex}`);
				objNoItems.hide();

				//var nextIndex = $(self.drawerBody).find('.' + self.topLevelRepeaterNoIndex).length + 1;

				var outer_block = $('<div>', {
					class: `mb-2 repeater_field is_sortable_block ${self.topLevelRepeaterNoIndex} active`
				});

				var $header = self.repeater_header.clone();
				//$header.find('.' + self.topLevelRepeaterHeaderNavTitleNumNoIndex).text(nextIndex);

				outer_block.append($header);

				var $content = self.repeater_content.clone();

				$.each(self.repeaterStructure.sub_fields, function (index, single_field) {
					var structure_block = self.generateSingleFiled(single_field);
					$content.append(structure_block)
				})
				outer_block.append($content);
				$('.top_level_sortable_parent').append(outer_block);

				outer_block.after($clickedButton);

				self.initiateWYSIWYG();
				self.recalculateRepeaterCounters();
			})

			// on block add
			$(this.drawerBody).on('click', '.' + self.blockAddRepeaterButton, function () {
				var parent = $(this).closest('.' + self.repeaterFieldNoIndex);
				var repeater_field_name = parent.attr('data-name');

				$.each(self.repeaterStructure.sub_fields, function (index, single_value) {

					if (single_value.name == repeater_field_name && single_value.type == 'repeater') {
						var outer_block = $('<div>', {
							class: `mb-2 repeater_field `
						});

						outer_block.append(self.repeater_header.clone());

						$.each(single_value.sub_fields, function (index, local_field) {
							var structure_block = self.generateSingleFiled(local_field);
							outer_block.append(structure_block);
						})

						$('.' + self.blockAddRepeaterButtonRow, parent).before(outer_block);
					}
				})
			})

			$(this.drawerBody).on('click', '.' + self.topLevelRepeaterHeaderNavNoIndex, function () {
				var parent = $(this).closest('.' + self.topLevelRepeaterNoIndex);
				var content = parent.find('.' + self.topLevelRepeaterContentNoIndex);
				var classActive = 'active';
				var isActive = parent.hasClass(classActive);

				if (isActive == true) {
					content.hide();
					parent.removeClass(classActive);
				} else {
					content.show();
					parent.addClass(classActive);
				}
			});

			// duplicate block
			$(this.drawerBody).on('click', '.' + self.blockCopyButtonNoIndex, function (e) {
				self.cloneSingleBlock(e);
				self.recalculateRepeaterCounters();
			})

		}

		// Functions Below

		// recalculate block indexes
		recalculateRepeaterCounters() {
			var self = this;

			//for each repeater_no_childs order repeater_field
			$('.repeater_no_childs').each(function () {
				var counter = 1;
				$(' > .' + self.repeaterFieldNoIndex, this).each(function () {
					$('.' + self.topLevelRepeaterHeaderNavTitleNumNoIndex, this).html(counter);
					counter++;
				})
			})


		}

		// clone single block
		cloneSingleBlock(e) {
			var cloned_container = jQuery(e.target).closest('.repeater_field');
			var cloned_element = cloned_container.clone();
			cloned_container.after(cloned_element);
		}

		refreshItemNumbers() {
			var self = this;

			$(this.drawerBody).find('.' + self.topLevelRepeaterNoIndex).each(function (index) {
				$(this).find('.' + self.topLevelRepeaterHeaderNavTitleNumNoIndex).text(index + 1);
			});
		}

		generateRepeaterInterface( repeater_data, post_id ) {
			var self = this;
			const { __, sprintf } = wp.i18n;

		 
			this.repeaterStructure = repeater_data.structure
			this.repeaterValues = repeater_data.values;

			var block_title = $('<div>', {
				class: `mb-2`,
			});

			this.repeater_header = $('<div>', {
				class: `${self.topLevelRepeaterHeaderNoIndex} text-right`,
				html: `
				<div class="${self.topLevelRepeaterHeaderInnerNoIndex} d-flex align-items-center">
					<div class="d-flex align-items-center flex-shrink-0">
						<span class="${self.blockDragButtonNoIndex} d-flex size-28 align-items-center justify-content-center border-radius-6">
							<svg class="size-14" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>
						</span>
					</div>
					<div class="${self.topLevelRepeaterHeaderNavNoIndex} d-flex align-items-center flex-grow-1">
						<span class="${self.blockShowHideButtonNoIndex} d-flex align-items-center justify-content-center">
							<svg class="size-14" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>
						</span>
						<span class="${self.topLevelRepeaterHeaderNavTitleNoIndex}">${sheetspilot.editor.item} #<span class="${self.topLevelRepeaterHeaderNavTitleNumNoIndex}"></span></span>
					</div>
					<div class="${self.topLevelRepeaterHeaderButtonsNoIndex} d-flex align-items-center flex-shrink-0">
						<span class="${self.blockCopyButtonNoIndex} d-flex size-28 bg-hover-f5f5f5 align-items-center justify-content-center border-radius-6">
							<svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path></svg>
						</span>
						<span class="${self.blockRemoveButtonNoIndex} d-flex size-28 bg-hover-f5f5f5 align-items-center justify-content-center border-radius-6">
							<svg class="size-16" role="button" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4343" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg>
						</span>
					</div>
				</div>
				`
			});

			this.repeater_content = $('<div>', {
				class: `${self.topLevelRepeaterContentNoIndex}`
			});

			this.repeater_add = $('<div>', {
				class: `${self.blockAddRepeaterButtonRow}  text-right `,
				html: `<span  role="button" class="block_add_repeater_button"><svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg></span>`
			});
			this.repeater_top_add = $('<div>', {
				class: `unlimitedai-plugin__drawer-downloadable_edit-repeater-add-btn d-flex align-items-center justify-content-center top_block_add_button`,
				html: `<span class="top_block_add_button_icon"><svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg></span><span class="top_block_add_button_text">${sheetspilot.editor.add_item}</span>`,
			});

			var top_level_cont = $('<div>', {
				class: `is_sortable_parent top_level_sortable_parent repeater_no_childs`,
			});

			var repeater_title = block_title.clone().addClass('top_level_title').html(repeater_data.structure.label);
			g_drawer.appendDrawerBody(repeater_title);

			// verify if have unsupported fields
			var repeater_fields = self.getRepeaterFiledTypes();
			var diff = repeater_fields.filter(function(x) {
				return self.repeaterSupportedFields.indexOf(x) === -1;
			});
			if( diff.length > 0 ){
				// generate warning message
				var edit_url = g_postEditLink.replace('%PID', post_id);
				var warningMessage = $('<div>', {
					class: `alert alert-danger`,
					html: sprintf( sheetspilot.editor.repeater_fields_not_supported, diff.join(', '), edit_url ),
				});

				g_drawer.appendDrawerBody(warningMessage);
			}

			$.each(repeater_data.values, function (index, single_value) {
				self.level = 0;

				var outer_block = $('<div>', {
					class: `mb-2 repeater_field is_sortable_block ${self.topLevelRepeaterNoIndex} active`
				});

				var $header = self.repeater_header.clone();

				//add item index
				$header.find('.' + self.topLevelRepeaterHeaderNavTitleNumNoIndex).text(index + 1);

				outer_block.append($header);

				var $content = self.repeater_content.clone();


				$.each(repeater_data.structure.sub_fields, function (index, single_field) {
					var structure_block = self.generateSingleFiled(single_field, single_value);
					$content.append(structure_block);
				})

				outer_block.append($content);
				top_level_cont.append(outer_block);

			})

			var childrenNum = top_level_cont.children().length;
			var noItemsHTML = $('<div>', {
				class: `${this.topBlockNoItemsNoIndex}`,
				text: `${sheetspilot.editor.no_items_yet}`,
			}).hide();

			g_drawer.appendDrawerBody(noItemsHTML);

			if (!childrenNum || childrenNum == 0)
				noItemsHTML.show();

			g_drawer.appendDrawerBody(top_level_cont);
			g_drawer.appendDrawerBody(self.repeater_top_add.clone());

			this.initiateWYSIWYG();
			this.makeRepeaterBlocksSortable();
		}

		initiateWYSIWYG() {

			$('.wysiwyg').each(function () {
				let unique_id = $(this).attr('id');
				var $res = wp.editor.initialize(unique_id, {
					tinymce: {
						height: 300,
						menubar: false,
						plugins: 'lists link image ',
						toolbar: 'bold italic underline | bullist numlist | link | code'
					},
					quicktags: true
				});
			});
		}


		generateSingleFiled(field_data, field_value) {
			var self = this;

			var field_row = $('<div>', {
				class: `mb-2 field_row`,
			});

			var input_label = $('<label>', {
				class: `mb-1 ${self.topLevelRepeaterContentFieldLabelNoIndex}`,
			});

			if (field_data.type == 'text' || field_data.type == 'password' || field_data.type == 'number' || field_data.type == 'url' || field_data.type == 'email' || field_data.type == 'range' || field_data.type == 'color_picker') {

				if (field_data.type == 'range') {
					field_data.type = 'number';
				}
				if (field_data.type == 'color_picker') {
					field_data.type = 'color';
				}

				var input_field = $('<input>', {
					class: `${self.topLevelRepeaterContentFieldInputNoIndex} mb-2 form-control border-radius-6`,
					'data-name': field_data.name,
					'data-fullname': field_data.name,
					type: field_data.type
				});

				if (field_value) {
					if (typeof field_value !== undefined) {
						if (typeof field_value.fields !== undefined) {
							if (typeof field_value.fields[field_data.name] !== undefined) {
								input_field.val(field_value.fields[field_data.name]);
							}
						}
					}
				}

				var new_row = field_row.clone().addClass(`level_${this.level}`).append(
					input_label.clone().html(field_data.label),
					input_field.clone()
				);
			}
			if (field_data.type == 'wysiwyg') {

				var unique_id = 'editor_' + crypto.randomUUID();

				var input_field = $('<textarea>', {
					class: `mb-2 form-control wysiwyg`,
					'data-name': field_data.name,
					'data-fullname': field_data.name,
					id: unique_id,
					rows: 10
				});

				if (field_value) {
					if (typeof field_value !== undefined) {
						if (typeof field_value.fields !== undefined) {
							if (typeof field_value.fields[field_data.name] !== undefined) {
								input_field.val(field_value.fields[field_data.name]);
							}
						}
					}
				}

				var new_row = field_row.clone().addClass(`level_${this.level}`).append(
					input_label.clone().html(field_data.label),
					input_field.clone().uniqueId()
				);


			}
			if (field_data.type == 'textarea') {
				var input_field = $('<textarea>', {
					class: `mb-2 form-control`,
					'data-name': field_data.name,
					'data-fullname': field_data.name,
					rows: 5
				});

				if (field_value) {
					if (typeof field_value !== undefined) {
						if (typeof field_value.fields !== undefined) {
							if (typeof field_value.fields[field_data.name] !== undefined) {
								input_field.val(field_value.fields[field_data.name]);
							}
						}
					}
				}

				var new_row = field_row.clone().addClass(`level_${this.level}`).append(
					input_label.clone().html(field_data.label),
					input_field.clone()
				);
			}
			if (field_data.type == 'select' || field_data.type == 'checkbox' || field_data.type == 'radio') {

				// patch set multiselect for checkbox
				if (field_data.type == 'checkbox') {
					field_data.multiple = true;
				}

				var $select = $('<select>', {
					class: `mb-2 form-control`,
					multiple: field_data.multiple,
					'data-name': field_data.name,
					'data-fullname': self.level + '_' + field_data.name,
				});

				if (!field_data.multiple) {
					var $option = $('<option>', {
						value: '',
						text: sheetspilot.editor.select,
					});
					$select.append($option);
				}

				$.each(field_data.choices, function (index, value) {
					if (field_data.multiple) {
						var this_value_check = false;
						if (field_value) {
							if (typeof field_value !== undefined) {
								if (typeof field_value.fields !== undefined) {
									if (typeof field_value.fields[field_data.name] !== undefined) {
										this_value_check = ($.inArray(value, field_value.fields[field_data.name]) ? true : false);
									}
								}
							}
						}

						var $option = $('<option>', {
							value: value,
							text: index,
							selected: this_value_check
						});
						$select.append($option);
					} else {

						var this_value_check = false;
						if (field_value) {
							if (typeof field_value !== undefined) {
								if (typeof field_value.fields !== undefined) {
									if (typeof field_value.fields[field_data.name] !== undefined) {
										this_value_check = (value == field_value.fields[field_data.name] ? true : false);
									}
								}
							}
						}

						var $option = $('<option>', {
							value: value,
							text: index,
							selected: this_value_check
						});
						$select.append($option);
					}

				})


				var new_row = field_row.clone().addClass(`level_${this.level}`).append(
					input_label.clone().html(field_data.label),
					$select.clone()
				);
			}
			if (field_data.type == 'repeater') {
				this.level++;
				var block_title = $('<div>', {
					class: `mb-2 `,
				});


				var repeater_field_outer = $('<div>', {
					class: `mb-2  repeater_field repeater_no_childs is_sortable_parent`,
					'data-name': field_data.name,

				});


				var repeater_title = block_title.clone().addClass(`level_${this.level}`).html(field_data.label);
				//g_drawer.appendDrawerBody( repeater_title );

				repeater_field_outer.append(repeater_title);


				if (!field_value || !field_value.fields || !field_value.fields[field_data.name]) {
					field_value = field_value || {};
					field_value.fields = field_value.fields || {};
					field_value.fields[field_data.name] = field_value.fields[field_data.name] || {};
				}

				$.each(field_value.fields[field_data.name], function (index, single_inner_value) {

					var repeater_field = $('<div>', {
						class: `mb-2  repeater_field  is_sortable_block bg-body-tertiary`
					});


					repeater_field.append(self.repeater_header.clone());

					$.each(field_data.sub_fields, function (index, single_field) {
						var structure_block = self.generateSingleFiled(single_field, single_inner_value);
						repeater_field.append(structure_block);
					})
					//g_drawer.appendDrawerBody( repeater_field );

					repeater_field_outer.append(repeater_field);

				})

				repeater_field_outer.append(self.repeater_add.clone());


				//g_drawer.appendDrawerBody( repeater_field_outer );
				new_row = repeater_field_outer;
				this.level--;

			}

			return new_row;

		}

		// random bg
		getRandomBg() {
			const r = Math.floor(Math.random() * 256);
			const g = Math.floor(Math.random() * 256);
			const b = Math.floor(Math.random() * 256);

			return `rgba(${r}, ${g}, ${b}, 0.05)`;
		}

		// make attributes sortable
		makeRepeaterBlocksSortable() {
			var self = this;
			// make all attributes sortable
			jQuery('.is_sortable_parent').each(function () {
				const $container = jQuery(this);
				$container.sortable({
					items: '.is_sortable_block',
					axis: 'y',
					handle: '.' + self.blockDragButtonNoIndex,
					tolerance: 'pointer',
					placeholder: 'ui-sortable-placeholder',
					forcePlaceholderSize: true,

					update: function (event, ui) {
						const order = [];

						$container.find(self.sideDrawerAttributeContainer).each(function () {
							order.push(jQuery(this).data('id') || jQuery(this).index());
						});

						self.recalculateRepeaterCounters();
						// тут можешь отправлять AJAX
					}
				});

			});
		}

		// generate lis of repeater fields
		getRepeaterFiledTypes() {
			var allTypes = [];
			var currentStructure = this.repeaterStructure;

			if (currentStructure.sub_fields) {
				$.each(currentStructure.sub_fields, function (index, value) {
					if (value.type == 'repeater') {
						$.each(value.sub_fields, function (index, value) {
							allTypes.push(value.type);
						})
					}
					allTypes.push(value.type);
				})
			}

			var uniqueList = [];

			$.each(allTypes, function(i, el) {
				if ($.inArray(el, uniqueList) === -1) {
					uniqueList.push(el);
				}
			});
 
			return uniqueList;
		}
	}
	window.SheetsPilotRepeaterEditor = SheetsPilotRepeaterEditor;
})(jQuery)