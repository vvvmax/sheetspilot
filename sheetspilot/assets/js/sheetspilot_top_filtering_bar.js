class SheetsPilot_TopFilteringBar {

	constructor() {
		// classes
		this.table = g_cellProcessingObj.$table;

		// variables
		this.g_objSpreadsheet =  jQuery( "#new_output_table");
		this.g_filterColumnSettingsIcon =  '.unlimitedai-plugin__filter-column-settings-icon';
		this.g_filterToolsRow =  '.unlimitedai-plugin__tools--row__item';
		this.g_filterToolsRowSeparator =  '.unlimitedai-plugin__buttons-separator';

		this.g_toolsFilter =  '.unlimitedai-plugin__tools-filters';
		this.objToolsFilter =  jQuery(this.g_toolsFilter );

		this.g_filterToolsFiltersContainer =  '.unlimitedai-plugin__tools-filters-container';

		this.g_filterToolsFiltersItemNoIndex =  'unlimitedai-plugin__tools-filters__item';
		this.g_filterToolsFiltersItem =  '.'+this.g_filterToolsFiltersItemNoIndex;
		
		this.g_filterToolsFiltersItemNameNoIndex =  'unlimitedai-plugin__tools-filters__item-name';
		this.g_filterToolsFiltersItemName =  '.'+this.g_filterToolsFiltersItemNameNoIndex;
		
		this.g_filterToolsFiltersItemValNoIndex =  'unlimitedai-plugin__tools-filters__item-val';
		this.g_filterToolsFiltersItemVal =  '.'+this.g_filterToolsFiltersItemValNoIndex;
		
		this.g_filterToolsFiltersItemDeleteNoIndex =  'unlimitedai-plugin__tools-filters__item-del-icon';
		this.g_filterToolsFiltersItemDelete =  '.'+this.g_filterToolsFiltersItemDeleteNoIndex;

		this.g_filterToolsFiltersClearAllNoIndex =  'unlimitedai-plugin__tools-filters__clear-all';
		this.g_filterToolsFiltersClearAll =  '.'+this.g_filterToolsFiltersClearAllNoIndex;

		this.g_filterToolsFiltersPostsCountNumCurrentNoIndex =  'unlimitedai-plugin__tools-filters__post-count__nums-current';
		this.g_filterToolsFiltersPostsCountNumCurrent =  '.'+this.g_filterToolsFiltersPostsCountNumCurrentNoIndex;
		
		this.g_filterToolsFiltersPostsCountNumTotalNoIndex =  'unlimitedai-plugin__tools-filters__post-count__nums-total';
		this.g_filterToolsFiltersPostsCountNumTotal =  '.'+this.g_filterToolsFiltersPostsCountNumTotalNoIndex;
	 
	}

	initEvents() {
 
		// inline edit featured image
		this.objToolsFilter.on("click", this.g_filterToolsFiltersItemDelete, (e) => {
			this.dropSingleFilter(e);
		});
		
		// inline edit featured image
		this.objToolsFilter.on("click", this.g_filterToolsFiltersClearAll, (e) => {
			this.clearAllFilters(e);
		});

	 
	}

	/**
	 * drop all filters
	 */
	clearAllFilters(){		
		jQuery(this.g_filterToolsFiltersContainer).empty();
		objPostsEditorView.clearAllFilters();
	}

	/**
	 * on apply filter process all filters and show filtering menu
	 */
	processFiltersAndShowHideMenu(){
		var self = this;
	 
		if( this.g_objSpreadsheet.find(this.g_filterColumnSettingsIcon+'.is_active').length > 0 ){
			jQuery(this.g_toolsFilter).fadeIn();
		}else{
			jQuery(this.g_toolsFilter).fadeOut();
		}
		
		jQuery(this.g_filterToolsFiltersContainer).empty();

		this.g_objSpreadsheet.find(this.g_filterColumnSettingsIcon+'.is_active').each(function(){
			var filter_block_title = '';
			var filter_block_value = '';
			var parent_th = jQuery(this).parents('th');
			const type = parent_th.attr('data-type');

			const column_title = jQuery('.unlimitedai-plugin__th-title', parent_th ).html();

			var column_name = parent_th.attr('data-name');
		 
			var data_container = parent_th.find;
			var search_type = parent_th.attr('data-column-search-type');

			if( search_type == 'text' ){
				var filter_search_query = parent_th.find(g_columnFilterSearchInput).val().toLowerCase();
				filter_block_value = filter_search_query;
			}
			if( search_type == 'filter' ){
				
				var filter_values = [];
				jQuery(g_columnFilterDropdownFilterContainerClass+' .'+g_columnFilterDropdownFilterContainerItemClass+' input[type="checkbox"]:checked', parent_th).each(function(){
					filter_values.push( jQuery(this).parents('label').text() );
				})

				if( filter_values.length <= 2 ){				 
					var diff = filter_values.length - 2;
					filter_block_value = filter_values.join(' | ');
				}else{
					filter_block_value = filter_values.length+' values';
				}

			}
	 
			self.createSingleFilterData( column_title, filter_block_value, column_name );

		})
	}

	createSingleFilterData( filter_title, filter_value, filter_column  ){

		var filter_container = jQuery('<span>', {
			class: this.g_filterToolsFiltersItemNoIndex
		 
		})
	 
 
		var filter_item_name = jQuery('<span>', {
			class: this.g_filterToolsFiltersItemNameNoIndex,
			html: filter_title
		})
		var filter_item_value = jQuery('<span>', {
			class: this.g_filterToolsFiltersItemValNoIndex,
			html: filter_value
		})
		var filter_item_delete = jQuery('<span>', {
			'data-column': filter_column,
			class: this.g_filterToolsFiltersItemDeleteNoIndex,
			html: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>'
		})
		filter_container.append( filter_item_name, filter_item_value, filter_item_delete );

		jQuery(this.g_filterToolsFiltersContainer).append( filter_container );
	}


	/*
	drop single filter
	*/
	dropSingleFilter(e){
		var this_pnt = jQuery(e.target).parents( this.g_filterToolsFiltersItem );
		var column_name =  jQuery(e.target).parents(this.g_filterToolsFiltersItemDelete).attr('data-column');
		this_pnt.fadeOut(function(){
			this_pnt.replaceWith('');
		})
		objPostsEditorView.clearSingleFilter( this.g_objSpreadsheet.find( 'th[data-name="'+column_name+'"] .unlimitedai-plugin__filter-column-settings-icon')  );
	}

	/**
	 * modify posts counter
	 */
	modifyPostsCounter( pagination_data ){
		jQuery(this.g_filterToolsFiltersPostsCountNumCurrent).html( pagination_data.total );
		jQuery(this.g_filterToolsFiltersPostsCountNumTotal).html( pagination_data.global_counter );
	}
}