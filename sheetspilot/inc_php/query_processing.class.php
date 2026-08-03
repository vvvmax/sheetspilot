<?php

/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if (! defined('ABSPATH')) exit;
if (!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilotQueryProcessing
{
	public $args = [];

	public $postType = 'post';
	public $is_new_row = false;
	public $new_post_title = false;
	public $duplicate_post_id = false;
	public $offset = 0;
	public $search_string = false;
	public $single_post_id = false;
	public $orderby = false;
	public $order = false;
	public $column_search = false;
	public $column_name = false;
	public $column_type = false;
	public $filtering_values = [];
	public $column_query = [];

	/**
	 * echo ajax success response
	 */
	//1					2							3								4					5								6							7	
	// $this->postType, $this->is_new_row = false, $this->duplicate_post_id = false, $this->offset = 0, $this->search_string = false, $this->single_post_id = false, $this->orderby = false, 
	//	8
	//$this->order = false 
	public function getPostTypeArray()
	{
		global $wpdb;

		$rowsPerPage = SheetsPilotHelper::getEditorPageSettings('rows_per_page');
		if (!$rowsPerPage) {
			$rowsPerPage = 10;
		}

		if (!$this->postType) {
			$this->postType = 'post';
		}

		// get all editable fiedls
		global $_wp_post_type_features;
		$mandatory_fields = ['post_name', 'post_status', 'post_date'];
		$supports_fiedls_relations = [
			'title' => 'post_title',
			"excerpt" => 'post_excerpt',
			"editor" => 'post_content',
			"thumbnail" => 'post_image',
			"author" => 'post_author'
		];
		$all_post_fields =  isset($_wp_post_type_features[$this->postType]) ? $_wp_post_type_features[$this->postType] : [];


		//media patch
		if( $this->postType == 'attachment' ){
			$all_post_fields['editor'] = true;
			$all_post_fields['excerpt'] = true;
		}
	
	 
		// get all post supported
		$fields_to_use = [];
		foreach ($all_post_fields as $key => $value) {
			if (isset($supports_fiedls_relations[$key])) {
				$fields_to_use[] = $supports_fiedls_relations[$key];
			}
		}
 
		$fields_to_use = array_merge( array( 'bulk', 'id', '_sheetspilot_row_meta', 'elementor_active' ), $fields_to_use );
		$fields_to_use = array_merge($fields_to_use, $mandatory_fields);

		


		$post_type_taxonomies = get_object_taxonomies($this->postType, 'names');

		// For products, remove WooCommerce attribute taxonomies from regular taxonomy columns.
		if( $this->postType == 'product' ){
			$tmp_tax = [];
			foreach( $post_type_taxonomies as $s_tax ){
				if( substr( $s_tax, 0, 3) != 'pa_' ){
					$tmp_tax[] = $s_tax;
				}
			}
			$post_type_taxonomies = $tmp_tax;
		}


		$fields_to_use = array_merge($fields_to_use, $post_type_taxonomies);

	

		// acf fields addition
		$acf_extra_fields = SheetsPilotCellEditor::get_acf_fields_for_post_type($this->postType);
		foreach ($acf_extra_fields as $s_field_data) {
			$fields_to_use[] = 'acf_' . $s_field_data['name'];
		}

		$offset_arg = 0;
		if ($this->offset > 0) {
			$offset_arg = $this->offset * $rowsPerPage;
		}
		$args = [
			'post_type' => $this->postType,
			'showposts' => $rowsPerPage,
			'post_status' => 'any',
			'offset' => $offset_arg,
			'orderby' => 'ID',
		];



		// ordering
		if ($this->orderby && $this->order) {
			if ($this->orderby === 'elementor_active') {
				$args = $this->applyElementorActiveSortToArgs($args, $this->order);
			} else {
				// Map editor column names to WP_Query orderby values.
				$orderby_map = array(
					'id'            => 'ID',
					'ID'            => 'ID',
					'post_title'    => 'title',
					'post_date'     => 'date',
					'post_name'     => 'name',
					'post_author'   => 'author',
					'post_modified' => 'modified',
				);
				$args['orderby'] = isset( $orderby_map[ $this->orderby ] )
					? $orderby_map[ $this->orderby ]
					: $this->orderby;
				$args['order'] = $this->order;

				if (substr_count($this->orderby, 'acf_') > 0 || substr_count($this->orderby, 'plugins_') > 0) {
					$filtered_feild_name = str_replace('acf_', '', $this->orderby);
					$filtered_feild_name = str_replace('plugins_', '', $filtered_feild_name);
					$args['orderby'] = 'meta_value';

					$args['meta_key'] = $filtered_feild_name;
				}
			}
		}

		// get existed post
		if ($this->single_post_id) {
			$args['post__in'] = [(int) $this->single_post_id];
			$args['posts_per_page'] = 1;
			$args['offset'] = 0;
		}

		if ($this->search_string) {
			$args['s'] = $this->search_string;
		}



		// custom incolumn search
		if ($this->column_search) {

			unset($args['s']);

			// getting type of search
			if (substr($this->column_name, 0, 4) == 'acf_') {
				// acf field
				$inner_column_name = preg_replace('/[^a-zA-Z0-9_]/', '', substr($this->column_name, 4));
				$inner_post_type = preg_replace('/[^a-zA-Z0-9_]/', '', $this->postType);

				$wpdb_results = $wpdb->get_col(
					$wpdb->prepare("SELECT p.ID
					FROM {$wpdb->posts} p
					JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
					WHERE p.post_type = %s
					AND pm.meta_key = %s
					AND pm.meta_value LIKE %s", 
					
					$inner_post_type,
					$inner_column_name,
					'%' . $wpdb->esc_like( $this->search_string ) . '%'
				
				)
				);
	
				$args['post__in'] = $wpdb_results;
			} else
			if (substr($this->column_name, 0, 8) == 'plugins_') {
				// custom plugin
				$inner_column_name = preg_replace('/[^a-zA-Z0-9_]/', '', substr($this->column_name, 8));
				$inner_post_type = preg_replace('/[^a-zA-Z0-9_]/', '', $this->postType);

				$wpdb_results = $wpdb->get_col(
					$wpdb->prepare("SELECT p.ID
				FROM {$wpdb->posts} p
				JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				WHERE p.post_type = %s
				AND pm.meta_key = %s
				AND pm.meta_value LIKE %s
				", 
				$inner_post_type,
				$inner_column_name,
				'%' . $wpdb->esc_like( $this->search_string ) . '%'
				)
				);
	
				$args['post__in'] = $wpdb_results;
			} else {
				// default fields
				$column_name = preg_replace('/[^a-zA-Z0-9_]/', '', $this->column_name);
				$wpdb_results = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->prefix}posts  WHERE %i LIKE %s ", $column_name,  '%' . $this->search_string . '%')); 
				$args['post__in'] = $wpdb_results;
			}
		}

		// column filtering
		// custom incolumn search
		if ($this->column_name && $this->column_type && $this->filtering_values) {
			unset($args['s']);

			if ($this->column_type == 'acf_select') {
				// filter by def
				if (in_array($this->column_name, ['post_status'])) {
					$args[$this->column_name] = $this->filtering_values;
				} else
				if (in_array($this->column_name, ['post_author'])) {
					$args['author__in'] = $this->filtering_values;
				} else {
					// cusotm fields
					$this->column_name = str_replace('acf_', '', $this->column_name);
					$this->column_name = str_replace('plugins_', '', $this->column_name);
					foreach ($this->filtering_values as $s_filter_value) {
						$args['meta_query']['relation'] = 'OR';
						$args['meta_query'][] = [
							'key' => $this->column_name,
							'value' => '"' . $s_filter_value . '"',
							'compare' => 'LIKE'
						];
						$args['meta_query'][] = [
							'key' => $this->column_name,
							'value' => $s_filter_value

						];
					}
				}
			}

			if ($this->column_type == 'tag' || $this->column_type == 'taxonomy') {
				$args['tax_query'][] = [
					'taxonomy' => $this->column_name,
					'field' => 'term_id',
					'terms' => $this->filtering_values,
				];
			}

			if ($this->column_name === 'elementor_active' && $this->column_type === 'switch') {
				$args = $this->applyElementorActiveFilterToArgs($args, $this->filtering_values);
			}
		}


		// column query filtering
		if (count($this->column_query) > 0) {
			$results_ids_list = [];
			foreach ($this->column_query as $s_column_query) {

				if ($s_column_query['type'] == 'text') {

					$search_value = preg_replace('/[^a-zA-Z0-9_]/', '',  $s_column_query['value']);

					// getting type of search
					if (substr($s_column_query['name'], 0, 4) == 'acf_') {
						// acf field
						$inner_column_name = preg_replace('/[^a-zA-Z0-9_]/', '', substr($s_column_query['name'], 4));
						$inner_post_type = preg_replace('/[^a-zA-Z0-9_]/', '', $this->postType);

						// phpcs:disable
						$wpdb_results = $wpdb->get_col($wpdb->prepare("SELECT p.ID
						FROM {$wpdb->posts} p
						JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
						WHERE p.post_type = %s
						AND pm.meta_key = %s
						AND pm.meta_value LIKE %s
						", 
						$inner_post_type,
						$inner_column_name,
						'%' . $wpdb->esc_like( $search_value ) . '%'
						
						));
						// phpcs:enable
						$results_ids_list[] = $wpdb_results;
					} else
					if (substr($s_column_query['name'], 0, 8) == 'plugins_') {
						// custom plugin
						$inner_column_name = preg_replace('/[^a-zA-Z0-9_]/', '', substr($s_column_query['name'], 8));
						$inner_post_type = preg_replace('/[^a-zA-Z0-9_]/', '', $this->postType);

						// phpcs:disable
						$wpdb_results = $wpdb->get_col($wpdb->prepare("SELECT p.ID
						FROM {$wpdb->posts} p
						JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
						WHERE p.post_type = %s
						AND pm.meta_key =  %s
						AND pm.meta_value LIKE %s
						", 
						
						$inner_post_type,
						$inner_column_name,
						'%' . $wpdb->esc_like( $search_value ) . '%'
						
						));
						// phpcs:enable
						$results_ids_list[] = $wpdb_results;
					} else {
						// default fields
						$column_name = preg_replace('/[^a-zA-Z0-9_]/', '', $s_column_query['name']);
						$wpdb_results = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->prefix}posts  WHERE %i LIKE %s ", $column_name, '%' . $search_value . '%')); 
						$results_ids_list[] = $wpdb_results;
					}
				}

				if ($s_column_query['type'] == 'filter' &&  $s_column_query['value'] !== null) {

					$inner_args = $args;

					if ($s_column_query['column_type'] == 'acf_select') {
						// filter by def
						if (in_array($s_column_query['name'], ['post_status'])) {
							$inner_args[$s_column_query['name']] = $s_column_query['value'];
						} else
						if (in_array($s_column_query['name'], ['post_author'])) {
							$inner_args['author__in'] = $s_column_query['value'];
						} else {
							// cusotm fields
							$s_column_query['name'] = str_replace('acf_', '', $s_column_query['name']);
							$s_column_query['name'] = str_replace('plugins_', '', $s_column_query['name']);
							foreach ($s_column_query['value'] as $s_filter_value) {
								$inner_args['meta_query']['relation'] = 'OR';
								$inner_args['meta_query'][] = [
									'key' => $s_column_query['name'],
									'value' => '"' . $s_filter_value . '"',
									'compare' => 'LIKE'
								];
								$inner_args['meta_query'][] = [
									'key' => $s_column_query['name'],
									'value' => $s_filter_value

								];
							}
						}
					}

					if ($s_column_query['column_type'] == 'tag' || $s_column_query['column_type'] == 'taxonomy') {
						$inner_args['tax_query'][] = [
							'taxonomy' => $s_column_query['name'],
							'field' => 'term_id',
							'terms' => $s_column_query['value'],
						];
					}

					if ($s_column_query['name'] === 'elementor_active' && $s_column_query['column_type'] === 'switch') {
						$inner_args = $this->applyElementorActiveFilterToArgs($inner_args, $s_column_query['value']);
					}
					$inner_args['fields'] = 'ids';
					$inner_args['showposts'] = -1;

					$results_ids_list[] = get_posts($inner_args);
				}

				if ($s_column_query['type'] == 'calendar' &&  $s_column_query['value'] !== null) {

					$inner_args = $args;
 

					$inner_args['date_query'] = $this->getDateQueryByRange($s_column_query['value']);
					$inner_args['fields'] = 'ids';
					$inner_args['showposts'] = -1;

					$results_ids_list[] = get_posts($inner_args);
				}


				// if all deselected
				if ($s_column_query['type'] == 'filter' && $s_column_query['value'] === null) {
					$results_ids_list[] = [];
				}
			}
			$intersected_ids = call_user_func_array('array_intersect', $results_ids_list);
			if (count($intersected_ids) == 0) {
				$intersected_ids = [0];
			}
			$args['post__in'] = $intersected_ids;
		}


		$this->args = $args;
		$all_posts = $this->runPostsQuery($args);

		// add new row functionality
		if ($this->is_new_row) {
			if ($this->duplicate_post_id) {
				$new_post_id = SheetsPilotCellEditor::duplicatePostProcessing($this->duplicate_post_id);
			} else {
				$post_type_obj = get_post_type_object($this->postType);
				$new_post_title = SheetsPilotGlobals::$newPostTitle . ' ' . $post_type_obj->labels->singular_name;

				$new_post_id = wp_insert_post([
					'post_type' => $this->postType,
					'post_title' => $new_post_title,
					'post_status' => 'publish',
				]);
				$new_post_slug = explode('-', get_post($new_post_id)->post_name);
				$new_post_title = SheetsPilotGlobals::$newPostTitle . ' ' . $post_type_obj->labels->singular_name . ' ' . $new_post_slug[count($new_post_slug) - 1];

				if( $this->new_post_title ){
					$new_post_title = sanitize_text_field( $this->new_post_title );
				}

				wp_update_post([
					'ID' => $new_post_id,
					'post_status' => 'draft',
					'post_title' => $new_post_title
				]);

				if ($this->postType == 'product') {
					update_post_meta($new_post_id, 'total_sales', 0);
					update_post_meta($new_post_id, '_tax_status', 'taxable');
					update_post_meta($new_post_id, '_tax_class', '');
					update_post_meta($new_post_id, '_manage_stock', 'no');
					update_post_meta($new_post_id, '_backorders', 'no');
					update_post_meta($new_post_id, '_sold_individually', 'no');
					update_post_meta($new_post_id, '_virtual', 'no');
					update_post_meta($new_post_id, '_downloadable', 'no');
					update_post_meta($new_post_id, '_download_limit', -1);
					update_post_meta($new_post_id, '_download_expiry', -1);
					update_post_meta($new_post_id, '_stock_status', 'instock');
					wp_set_object_terms($new_post_id, 'simple', 'product_type');
				}
			}

			$new_post = get_post($new_post_id);
			$all_posts = [];
			$all_posts[] = $new_post;
		}

		$postTypeOrder = SheetsPilotHelper::getEditorPageSettings($this->postType . '_columns_order');
		if (!is_array($postTypeOrder)) {
			$postTypeOrder = [];
		}

		$post_data_array = [];
		foreach ($all_posts as $s_post) {
			$thumbnail_id = get_post_thumbnail_id($s_post->ID);
			$thumbnail = get_the_post_thumbnail_url($s_post->ID, SheetsPilotUniteFunctionsWP::THUMB_MEDIUM);
			$thumbnailFull = get_the_post_thumbnail_url($s_post->ID, 'full');
			$is_placeholder = false;
			if (!$thumbnail) {
				$thumbnail = SheetsPilotGlobals::$urlImagePlaceholder;
				$is_placeholder = true;
			}


			$tmp_array_element = [
				['bulk' => $s_post->ID],
				['id' => $s_post->ID],
				['_sheetspilot_row_meta' => [
					'is_elementor' => SheetsPilotHelperElementor::isPostBuiltWithElementor( $s_post->ID ),
				]],
				['post_title' => $s_post->post_title],
				['post_excerpt' => $s_post->post_excerpt],
				['post_content' => SheetsPilotHelperElementor::getPostContentDisplayForEditor( $s_post->ID )],

				...( SheetsPilotGlobals::$isElementorActive ? 		
				[[
					'elementor_active' => ( get_post_meta( $s_post->ID,  '_elementor_edit_mode', true ) == 'builder' ? 'yes' : 'no' )
				]] : []				
				),

				['post_name' => urldecode($s_post->post_name)],
				['post_author' => ['values' => [$s_post->post_author], 'multiple' => 0]],
				['post_status' => ['values' => [$s_post->post_status], 'multiple' => 0]],
				['post_date' => $s_post->post_date],
				['post_image' => '<img src="' . $thumbnail . '" data-full="' . $thumbnailFull . '" data-img="' . $thumbnail . '" data-id="' . $thumbnail_id . '"' . SheetsPilotHelper::getAttachmentImagePreviewDataAttrs( $thumbnail_id ) . ' class="ubai_featured_image_uploader sp_hover_preview' . ($is_placeholder ? 'is_placeholder' : '') . '"  />'],
			];

			
	

			// all registered taxonomies
			foreach ($post_type_taxonomies as $s_reg_taxonomy) {
				$this_taxonomy = get_taxonomy($s_reg_taxonomy);

				/** fix for product to hide product type */
				if (($this->postType == 'product' && $this_taxonomy->name == 'product_type') || ($this->postType == 'product' && $this_taxonomy->name == 'product_visibility')  || ($this->postType == 'product' && $this_taxonomy->name == 'pos_product_visibility')) {
					continue;
				}

				if (is_taxonomy_hierarchical($s_reg_taxonomy)) {
					$tmp_array_element[] = [$this_taxonomy->name =>  SheetsPilotCellEditor::getCellCategoryContent($s_post->ID, $s_reg_taxonomy)];
				} else {
					$post_terms = wp_get_post_terms($s_post->ID, $s_reg_taxonomy, array('fields' => 'ids', 'hide_empty' => false));

					$inner_non_hierarhical_terms['values'] = $post_terms;
					$inner_non_hierarhical_terms['multiple'] = 1;
					$tmp_array_element[]  = [$this_taxonomy->name =>  $inner_non_hierarhical_terms];
				}
			}

			if ($this->postType == 'attachment') {
				foreach (SheetsPilotGlobals::$mediaPostTypeFields as $slug => $title) {
					if( $slug == '_wp_attachment_image_alt' ){
						$tmp_array_element[]['plugins_' . $slug] = get_post_meta( $s_post->ID, $slug, true );
						$fields_to_use[] = 'plugins_' . $slug;
						continue;	
					}		
					if( $slug == '_visual_output' ){
						$upload_dir   = wp_upload_dir();
						$attach_file_url = trailingslashit( $upload_dir['baseurl'] ).get_post_meta( $s_post->ID, '_wp_attached_file', true );
						$ext = pathinfo( trailingslashit( $upload_dir['basedir'] ).get_post_meta( $s_post->ID, '_wp_attached_file', true ), PATHINFO_EXTENSION);

						if( in_array( $ext, [ 'jpg', 'jpeg', 'png', 'webp' ] ) ){
							$preview = wp_get_attachment_image_url( $s_post->ID, 'medium' );
							$out_html = '
							<img src="'.esc_attr( $preview ).'" data-full="'.esc_attr( $attach_file_url ).'" data-img="'.esc_attr( $attach_file_url ).'"'.SheetsPilotHelper::getAttachmentImagePreviewDataAttrs( $s_post->ID ).' class="ubai_media_image_output sp_hover_preview">
							 ';
						}else{
							$out_html = '<span class="media_file_output dashicons dashicons-'.esc_attr( $ext ).'"></span>';
						}

						

						$tmp_array_element[]['plugins_' . $slug] = $out_html;
						$fields_to_use[] = 'plugins_' . $slug;
						continue;	
					}		
				}
			}

			// add ACF values
			foreach ($acf_extra_fields as $s_field_data) {

				$defaults = [
					'multiple'	=> 0,
				];

				$s_field_data = wp_parse_args($s_field_data, $defaults);

				// patch to set multiple = true for checkboxes
				if ($s_field_data['type'] == 'checkbox') {
					$s_field_data['multiple'] = 1;
				}

				// 
				$current_inner_value_out = [];
				$current_inner_value = get_field($s_field_data['name'], $s_post->ID);


				// custom content types processing
				if ($s_field_data['type'] == 'gallery') {
					$out_gallery_data = [];
					switch ($s_field_data['return_format']) {
						case "array":
							foreach ((array)$current_inner_value as $s_image) {
								if( !isset($s_image['ID']) ){ continue; }
								$out_gallery_data[] = ['id' => $s_image['ID'], 'url' => $s_image['url']];
							}
							break;
						case "url":
							foreach ($current_inner_value as $s_image_url) {
								$image_id = SheetsPilotCellEditor::get_image_id_by_url($s_image_url);
								$out_gallery_data[] = ['id' => (int)$image_id, 'url' => $s_image_url];
							}
							break;
						case "id":
							foreach ($current_inner_value as $s_image_id) {
								$out_gallery_data[] = ['id' => $s_image_id, 'url' => wp_get_attachment_url($s_image_id)];
							}
							break;
					}

					$current_inner_value_out['values'] = $out_gallery_data;
					$current_inner_value = $current_inner_value_out;
				}
				if ($s_field_data['type'] == 'image') {
					//###############
					if (!$current_inner_value) {
						$image_id = 0;
					} else {
						switch ($s_field_data['return_format']) {
							case "array":
								$image_id = $current_inner_value['ID'];
								break;
							case "url":
								$image_id = SheetsPilotCellEditor::get_image_id_by_url($current_inner_value);
								break;
							case "id":
								$image_id = $current_inner_value;
								break;
						}
					}


					$thumbnail = wp_get_attachment_url($image_id);
					$is_placeholder = false;
					if ($image_id == 0) {
						$thumbnail = SheetsPilotGlobals::$urlImagePlaceholder;
						$is_placeholder = true;
					}
					$default_placeholder = '<img src="' . $thumbnail . '" data-full="' . $thumbnail . '" data-id="' . $image_id . '"' . SheetsPilotHelper::getAttachmentImagePreviewDataAttrs( $image_id ) . ' class="ubai_featured_image_uploader sp_hover_preview ' . ($is_placeholder ? 'is_placeholder' : '') . '"  />';

					$current_inner_value = $default_placeholder;
				}


				// default values for acf
				if ($s_field_data['type'] == 'select' || $s_field_data['type'] == 'radio' || $s_field_data['type'] == 'checkbox') {

					if ($s_field_data['multiple'] == 0 && !$current_inner_value) {
						$current_inner_value = '';
					}
					if ($s_field_data['multiple'] == 1 && !$current_inner_value) {
						$current_inner_value = [];
					}


					// patch if switched from multi to single	 
					// -----
					// return array patch
					if ($s_field_data['return_format'] == 'array') {
						if (is_array($current_inner_value)) {

							if (isset($current_inner_value['value'])) {
								$current_inner_value = $current_inner_value['value'];
							} else {
								$current_inner_value_tmp = [];
								foreach ($current_inner_value as $s_cur_val) {
									$current_inner_value_tmp[] = $s_cur_val['value'];
								}
								$current_inner_value = $current_inner_value_tmp;
							}
						} else {
							$current_inner_value = [];
						}
					}

					
					

					// if return labels - get values from labels
					if ($s_field_data['return_format'] == 'label' && $s_field_data['multiple'] == 1) {
						$tmp_inner_values = [];
						foreach ($s_field_data['choices'] as $key => $value) {
							if (in_array($value, $current_inner_value)) {
								$tmp_inner_values[] = $key;
							}
						}

						$current_inner_value = $tmp_inner_values;
					}


					if ($s_field_data['return_format'] == 'label' && $s_field_data['multiple'] == 0) {
						$tmp_inner_values = [];
						foreach ($s_field_data['choices'] as $key => $value) {
							if ($value == $current_inner_value) {
								$tmp_inner_values = $key;
							}
						}

						$current_inner_value = $tmp_inner_values;
					}


					

					if ($s_field_data['multiple'] == 0) {
						$current_inner_value = [$current_inner_value];
					}
					if ($s_field_data['multiple'] == 1) {
						$current_inner_value = $current_inner_value;
					}

					/** def val try */
					if ($s_field_data['multiple'] == 0) {
						if (!$current_inner_value || $current_inner_value == '') {
							$current_inner_value = $s_field_data['default_value'];
						}
					}
					if ($s_field_data['multiple'] == 1) {
						if (!$current_inner_value || count($current_inner_value) == 0) {
							$current_inner_value = $s_field_data['default_value'];
						}
					}

					/** def val try END */


					$current_inner_value_out['values'] = $current_inner_value;
					$current_inner_value_out['multiple'] = $s_field_data['multiple'];


					$current_inner_value = $current_inner_value_out;
				}




				if ($s_field_data['type'] == 'post_object') {

					if ($s_field_data['multiple'] == 0 && !$current_inner_value) {
						$current_inner_value = '';
					}
					if ($s_field_data['multiple'] == 1 && !$current_inner_value) {
						$current_inner_value = [];
					}

					$filter_taxonomy_data = [];
					if ($s_field_data['taxonomy'] != '') {
						foreach ($s_field_data['taxonomy'] as $s_tax) {
							$s_tax_data = explode(':', $s_tax);
							$filter_taxonomy_data[$s_tax_data[0]][] = $s_tax_data[1];
						}
					}
					$posts_args = [
						'post_type' => ($s_field_data['post_type'] == '' ? 'any' : $s_field_data['post_type']),
						'post_status' => ($s_field_data['post_status'] == '' ? 'any' : $s_field_data['post_status']),
						'showposts' => -1,
					];

					if (count($filter_taxonomy_data) > 0) {
						foreach ($filter_taxonomy_data as $s_tax_name => $tax_value) {
							$posts_args['tax_query'][] = [
								'taxonomy' => $s_tax_name,
								'field'    => 'slug',
								'terms'    => $tax_value
							];
						}
					}

					$all_posts = get_posts($posts_args);

					$posts_list = [];
					$posts_list[] = ['id' => '', 'name' =>  SheetsPilotGlobals::$selectPost];
					foreach ($all_posts as $s_post_inner) {
						$posts_list[] = ['id' => $s_post_inner->ID, 'name' =>  $s_post_inner->post_title];
					}

					if ($s_field_data['multiple'] == 0) {

						if ($s_field_data['return_format'] == 'object') {
							if ($current_inner_value != '') {
								$current_inner_value_out['posts'][] = ['id' => $current_inner_value->ID, 'post_title' => $current_inner_value->post_title];
								$current_inner_value_out['postswname'][] = ['id' => $current_inner_value->ID, 'name' => $current_inner_value->post_title];
							}
						} else {

							$current_inner_value_out['posts'][] = ['id' => $current_inner_value, 'post_title' => get_post($current_inner_value)->post_title];
							$current_inner_value_out['postswname'][] = ['id' => $current_inner_value, 'name' => get_post($current_inner_value)->post_title];
						}
					} else {
						foreach ($current_inner_value as $single_current_value) {
							if ($s_field_data['return_format'] == 'object') {
								if ($current_inner_value != '') {
									$current_inner_value_out['posts'][]  = ['id' => $single_current_value->ID, 'post_title' => $single_current_value->post_title];
									$current_inner_value_out['postswname'][]  = ['id' => $single_current_value->ID, 'name' => $single_current_value->post_title];
								}
							} else {
								$current_inner_value_out['posts'][]  = ['id' => $single_current_value, 'post_title' => get_post($single_current_value)->post_title];
								$current_inner_value_out['postswname'][]  = ['id' => $single_current_value, 'name' => get_post($single_current_value)->post_title];
							}
						}
					}

					$current_inner_value_out['options'] = $posts_list;
					$current_inner_value_out['multiple'] = $s_field_data['multiple'];

					$current_inner_value = $current_inner_value_out;
				}



				if ($s_field_data['type'] == 'repeater') {


					$current_inner_value_out = [];
					$current_inner_value_out['values'] = SheetsPilotCellEditor::acf_repeater_get_items($s_field_data['name'], $s_post->ID);

					$current_inner_value =  $current_inner_value_out;
				}


				if ($s_field_data['type'] == "wysiwyg") {
					if ($current_inner_value) {
						$current_inner_value = substr(wp_strip_all_tags($current_inner_value), 0, 100);
					} else {
						$current_inner_value = '';
					}
				}

				$tmp_array_element[]['acf_' . $s_field_data['name']] = ($current_inner_value ? $current_inner_value : '');
			}

			// add filter for plugins

			$tmp_array_element = apply_filters('sheetspilot_filter_table_values', $tmp_array_element, $this->postType, $s_post->ID );
			$fields_to_use = apply_filters('sheetspilot_filter_table_fields', $fields_to_use, $this->postType, $s_post->ID );
		

			// custom fields from plugins
			foreach (SheetsPilotGlobals::$rankMathFields as $slug => $title) {
				$tmp_array_element[]['plugins_' . $slug] = get_post_meta($s_post->ID, $slug, true);
				$fields_to_use[] = 'plugins_' . $slug;
				continue;
			}
			// custom fields from plugins
			foreach (SheetsPilotGlobals::$yoastFields as $slug => $title) {
				$tmp_array_element[]['plugins_' . $slug] = get_post_meta($s_post->ID, $slug, true);
				$fields_to_use[] = 'plugins_' . $slug;
				continue;
			}
			// SEOPress
			foreach (SheetsPilotGlobals::$seoPress as $slug => $title) {
				$tmp_array_element[]['plugins_' . $slug] = get_post_meta($s_post->ID, $slug, true);
				$fields_to_use[] = 'plugins_' . $slug;
				continue;
			}


			

			if ($this->postType == 'product') {
				foreach (SheetsPilotGlobals::$wooCommerceFields as $slug => $title) {


					if ($slug == '_featured') {
						$product =  wc_get_product($s_post->ID);
						$tmp_array_element[]['plugins_' . $slug] = ($product->is_featured() ? 'yes' : 'no');
						$fields_to_use[] = 'plugins_' . $slug;
						continue;
					}
					if ($slug == '_visible_in_pos') {
						$tmp_array_element[]['plugins_' . $slug] = (has_term('pos-hidden', 'pos_product_visibility', $s_post->ID) ? 'no' : 'yes');
						$fields_to_use[] = 'plugins_' . $slug;
						continue;
					}
					if ($slug == '_product_visibility') {
						$tmp_array_element[]['plugins_' . $slug] = $product->get_catalog_visibility();
						$fields_to_use[] = 'plugins_' . $slug;
						continue;
					}
					if ($slug == 'product_type') {

						$out_product_type = 'simple';
						$product_type = wp_get_object_terms($s_post->ID, 'product_type');
						if (!empty($product_type) && !is_wp_error($product_type)) {
							$out_product_type =  $product_type[0]->slug;
						}
						$current_inner_value_out = [];
						$current_inner_value_out['values'] = [$out_product_type];
						$current_inner_value_out['multiple'] = 0;

						$tmp_array_element[]['plugins_' . $slug] = $current_inner_value_out;
						$fields_to_use[] = 'plugins_' . $slug;
						continue;
					}
					if ($slug == '_upsell_ids' || $slug == '_crosssell_ids') {

						$posts_list = get_post_meta($s_post->ID, $slug, true);

						$current_inner_value_out = [];
						foreach ($posts_list as $s_post_id) {
							$current_inner_value_out['posts'][]  = ['id' => $s_post_id, 'post_title' => get_post($s_post_id)->post_title];
							$current_inner_value_out['postswname'][]  = ['id' => $s_post_id, 'name' => get_post($s_post_id)->post_title];
						}

						$current_inner_value_out['values'] = get_post_meta($s_post->ID, $slug, true);
						$current_inner_value_out['multiple'] = 1;


						$tmp_array_element[]['plugins_' . $slug] = $current_inner_value_out;
						$fields_to_use[] = 'plugins_' . $slug;
						continue;
					}
					if ($slug == 'attributes') {
						$product = wc_get_product($s_post->ID);
						$attributes = $product->get_attributes();
						$count = count($attributes);


						$current_inner_value_out = [];
						$current_inner_value_out['values'] = $count;

						$tmp_array_element[]['plugins_' . $slug] = $current_inner_value_out;
						$fields_to_use[] = 'plugins_' . $slug;
						continue;
					}
					if ($slug == '_product_image_gallery') {
						$images = explode(',', get_post_meta($s_post->ID, '_product_image_gallery', true));
						$images = array_filter($images);

						$out_gallery_data = [];
						foreach ($images as $s_image_id) {
							$out_gallery_data[] = ['id' => $s_image_id, 'url' => wp_get_attachment_url($s_image_id)];
						}


						$current_inner_value_out = [];
						$current_inner_value_out['values'] = $out_gallery_data;

						$tmp_array_element[]['plugins_' . $slug] = $current_inner_value_out;
						$fields_to_use[] = 'plugins_' . $slug;
						continue;
					}

					$tmp_array_element[]['plugins_' . $slug] = get_post_meta($s_post->ID, $slug, true);
					$fields_to_use[] = 'plugins_' . $slug;
				}
			}


			if ($this->postType == 'tribe_events') {
				foreach (SheetsPilotGlobals::$theEventsCalendarFileds as $slug => $title) {

					$tmp_array_element[]['plugins_' . $slug] = get_post_meta($s_post->ID, $slug, true);
					$fields_to_use[] = 'plugins_' . $slug;
				}
			}


			// SheetsPilotFunctions::writeDebugFile( dirname( __FILE__ ) . '/$filterdata.txt', array(
			// 	'tmp_array_element' => $tmp_array_element,
			// 	'fields_to_use'     => $fields_to_use,
			// ) );

			// ordering fileterin
			$tmp_array_element = array_values(
				array_filter($tmp_array_element, function ($item) use ($fields_to_use) {
					$key = array_key_first($item);
					return in_array($key, $fields_to_use, true);
				})
			);



			// order elements if single
			/*
			if( $this->is_new_row ){

				$saved_columns_order = SheetsPilotHelper::getEditorPageSettings( SheetsPilotHelper::getEditorPageSettings( 'post_type' ).'_columns_order' );
				$this->ordered_array = [];
		 
				foreach ($saved_columns_order as $key) {
					foreach ($tmp_array_element as $item) {
						if (array_key_exists($key, $item)) {
							$this->ordered_array[] = $item;
							break;
						}
					}
				}
				$tmp_array_element = $this->ordered_array;
			}
				*/
			// order elements if single END


			$post_data_array[] = $tmp_array_element;
		}



		return $post_data_array;
	}

	/**
	 * get_total_post_count
	 */
	public  function getTotoalPostCount()
	{
		$this->args['showposts'] = -1;
		$this->args['fields'] = 'ids';



		return count(get_posts($this->args));
	}


	/**
	 * get posts by date  range
	 */
	function getDateQueryByRange($range)
	{

		// date range
		if (is_array($range)) {
			return [
				[
					'after'     => wp_date('Y-m-d', strtotime( $range[0] ) ),
					'before'    => wp_date('Y-m-d 23:59:59', strtotime( $range[1] ) ),
					'inclusive' => true,
				]
			];
		}

		$today = current_time('timestamp'); // важно для WP timezone

		switch ($range) {

			case 'today':
				return [
					[
						'year'  => wp_date('Y', $today),
						'month' => wp_date('m', $today),
						'day'   => wp_date('d', $today),
					]
				];

			case 'last_7_days':
				return [
					[
						'after'     => wp_date('Y-m-d', strtotime('-6 days', $today)),
						'before'    => wp_date('Y-m-d 23:59:59', $today),
						'inclusive' => true,
					]
				];

			case 'last_30_days':
				return [
					[
						'after'     => wp_date('Y-m-d', strtotime('-29 days', $today)),
						'before'    => wp_date('Y-m-d 23:59:59', $today),
						'inclusive' => true,
					]
				];

			case 'last_3_months':
				return [
					[
						'after'     => wp_date('Y-m-d', strtotime('-3 months', $today)),
						'before'    => wp_date('Y-m-d 23:59:59', $today),
						'inclusive' => true,
					]
				];

			case 'last_6_months':
				return [
					[
						'after'     => wp_date('Y-m-d', strtotime('-6 months', $today)),
						'before'    => wp_date('Y-m-d 23:59:59', $today),
						'inclusive' => true,
					]
				];

			case 'last_12_months':
				return [
					[
						'after'     => wp_date('Y-m-d', strtotime('-12 months', $today)),
						'before'    => wp_date('Y-m-d 23:59:59', $today),
						'inclusive' => true,
					]
				];

			case 'this_year':
				return [
					[
						'after'     => wp_date('Y-01-01', $today),
						'before'    => wp_date('Y-m-d 23:59:59', $today),
						'inclusive' => true,
					]
				];
		}

		return [];
	}

	/**
	 * Apply Elementor active column filter values to a WP_Query args array.
	 *
	 * @param array $args           Query args.
	 * @param array $filter_values  Selected values: 'yes' (Elementor) and/or 'no' (Non Elementor).
	 * @return array
	 */
	private function applyElementorActiveFilterToArgs($args, $filter_values)
	{
		if (empty($filter_values) || ! is_array($filter_values)) {
			return $args;
		}

		$filter_values = array_values(array_intersect($filter_values, ['yes', 'no']));
		if (count($filter_values) === 0 || count($filter_values) === 2) {
			return $args;
		}

		if (! isset($args['meta_query'])) {
			$args['meta_query'] = [];
		}

		if (in_array('yes', $filter_values, true)) {
			$args['meta_query'][] = [
				'key'     => '_elementor_edit_mode',
				'value'   => 'builder',
				'compare' => '=',
			];
		} else {
			$args['meta_query'][] = [
				'relation' => 'OR',
				[
					'key'     => '_elementor_edit_mode',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => '_elementor_edit_mode',
					'value'   => 'builder',
					'compare' => '!=',
				],
			];
		}

		return $args;
	}

	/**
	 * Sort by Elementor active column: ASC = Non Elementor first, DESC = Elementor first.
	 *
	 * @param array  $args  Query args.
	 * @param string $order Sort direction: asc|desc.
	 * @return array
	 */
	private function applyElementorActiveSortToArgs($args, $order)
	{
		$sort_order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
		$args['sheetspilot_elementor_active_sort'] = $sort_order;
		$args['orderby'] = 'ID';
		$args['order'] = 'DESC';
		$args['suppress_filters'] = false;

		return $args;
	}

	/**
	 * Run get_posts(), attaching a LEFT JOIN sort for Elementor when requested.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	private function runPostsQuery($args)
	{
		if (! empty($args['sheetspilot_elementor_active_sort'])) {
			add_filter('posts_clauses', array($this, 'filterPostsClausesForElementorActiveSort'), 10, 2);
		}

		$posts = get_posts($args);

		if (! empty($args['sheetspilot_elementor_active_sort'])) {
			remove_filter('posts_clauses', array($this, 'filterPostsClausesForElementorActiveSort'), 10);
		}

		return $posts;
	}

	/**
	 * Order all posts by Elementor status without excluding posts missing the meta key.
	 *
	 * @param array     $clauses SQL clauses.
	 * @param \WP_Query $query   Current query.
	 * @return array
	 */
	public function filterPostsClausesForElementorActiveSort($clauses, $query)
	{
		$sort_order = $query->get('sheetspilot_elementor_active_sort');
		if (! $sort_order) {
			return $clauses;
		}

		global $wpdb;

		$sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';
		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS sheetspilot_elementor_sort ON ({$wpdb->posts}.ID = sheetspilot_elementor_sort.post_id AND sheetspilot_elementor_sort.meta_key = '_elementor_edit_mode')";
		$clauses['groupby'] = "{$wpdb->posts}.ID";
		$clauses['orderby'] = "(CASE WHEN sheetspilot_elementor_sort.meta_value = 'builder' THEN 1 ELSE 0 END) {$sort_order}, {$wpdb->posts}.ID DESC";

		return $clauses;
	}
}
