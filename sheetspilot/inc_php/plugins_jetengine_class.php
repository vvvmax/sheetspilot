<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if (!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilotPluginsJetEngine
{
	function __construct(){
		add_filter('sheetspilot_filter_table_columns', function( $columns, $postType ){
			if ( function_exists( 'jet_engine' ) ) {
				$fields = jet_engine()->meta_boxes->get_fields_for_context( 'post_type', $postType );
	
				foreach ( $fields as $slug => $field_data) {

					$source = [];
					if( $field_data['type'] == 'select' ){
						foreach( $field_data['options'] as $s_option ){
							$source[] = [ 'id' => $s_option['key'], 'name' => $s_option['value'] ];
						}
					}

					$out_type = $field_data['type'];
					if( $field_data['type'] == 'select' ){
						$out_type = 'acf_select';
					}
					if( $field_data['type'] == 'text' || $field_data['type'] == 'textarea' ){
						$out_type = 'textarea';
					}
					if( $field_data['type'] == 'media'){
						$out_type = 'image';
					}
					

					$args = [
						'title'    => $field_data['title'],
						'name'     => 'plugins_' . $field_data['name'],
						'width'    => 100,
						'type'     => $out_type,
						'readonly' => ($field_data['readonly'] ? true : false),
						'orderable'   => true,
						'source'   => $source,
						'switchable'   => true,
						'column_search'  => 'text',
						'is_pro' => (in_array($field_data['type'], SheetsPilotGlobals::$proFilesList) ? true : false)
					];
					$columns[] = $args;
				}
			
			}
			return $columns;
		}, 10, 2);

		add_filter('sheetspilot_filter_table_values', function( $values, $postType, $postId ){
			if ( function_exists( 'jet_engine' ) ) {	
				$fields = jet_engine()->meta_boxes->get_fields_for_context( 'post_type', $postType, $postId );
				foreach ( $fields as $slug => $field_data) {

					if( $field_data['type'] == 'media' ){
						$image_id = (int)get_post_meta( $postId, $field_data['name'], true);
						$thumbnail = wp_get_attachment_url($image_id);
						$is_placeholder = false;
						if ($image_id == 0) {
							$thumbnail = SheetsPilotGlobals::$urlImagePlaceholder;
							$is_placeholder = true;
						}
						$default_placeholder = '<img src="' . $thumbnail . '" data-full="' . $thumbnail . '" data-id="' . $image_id . '"' . SheetsPilotHelper::getAttachmentImagePreviewDataAttrs( $image_id ) . ' class="ubai_featured_image_uploader sp_hover_preview ' . ($is_placeholder ? 'is_placeholder' : '') . '"  />';
						$values[]['plugins_' . $field_data['name']] = $default_placeholder;
					}else{
						$values[]['plugins_' . $field_data['name']] = get_post_meta( $postId, $field_data['name'], true);
					}
					
				}
			}
			return $values;
		}, 10, 3);

		add_filter('sheetspilot_filter_table_fields', function( $values, $postType, $postId ){
			if ( function_exists( 'jet_engine' ) ) {
				$fields = jet_engine()->meta_boxes->get_fields_for_context( 'post_type', $postType, $postId );
				foreach ( $fields as $slug => $field_data) {
					$values[] = 'plugins_' . $field_data['name'] ;
				}
			}
			return $values;
		}, 10, 3);

	}					

}
new SheetsPilotPluginsJetEngine();