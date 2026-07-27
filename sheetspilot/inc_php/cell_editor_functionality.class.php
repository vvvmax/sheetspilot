<?php

/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if (! defined('ABSPATH')) exit;
if (!defined("SHEETSPILOT_INC")) die("restricted access");


class SheetsPilotCellEditor
{

	public static $debugApplyPrompt = false;

	/**
	 * Bottom-manage download icon for image cells (full-size file download).
	 *
	 * @return string
	 */
	public static function get_image_cell_download_icon_html() {
		return '<span class="download_image_field has-tooltip" data-title="' . SheetsPilotGlobals::$downloadImageText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" x2="12" y1="15" y2="3"></line></svg></span>';
	}

	/**
	 * save the settings from data
	 */
	public static function saveEditedPostsDataBulk($data)
	{

		switch ($data['bulkActionDataType']) {
			case "text":
				foreach ($data['ids'] as $s_id) {
					if (substr($data['bulkActionColumnName'], 0, 4) == 'acf_') {
						update_field(substr($data['bulkActionColumnName'], 4),  $data['bulkActionValues'][0], $s_id);
						continue;
					}

					if (substr($data['bulkActionColumnName'], 0, 8) == 'plugins_') {
						update_post_meta($s_id,  substr($data['bulkActionColumnName'], 8),  $data['bulkActionValues'][0]);
						continue;
					}

					// patch for woocommerce price update
					if (substr($data['bulkActionColumnName'], 0, 8) == '_regular_price'  || substr($data['bulkActionColumnName'], 0, 8) == '_sale_price') {
						$regular_price = get_post_meta($s_id, '_regular_price', true);
						$sale_price    = get_post_meta($s_id, '_sale_price', true);

						if (!empty($sale_price) && floatval($sale_price) > 0) {
							update_post_meta($s_id, '_price', $sale_price);
						} else {
							update_post_meta($s_id, '_price', $regular_price);
						}
						continue;
					}

					if ( $data['bulkActionColumnName'] === 'post_content' ) {
						self::savePostContentValue(
							(int) $s_id,
							$data['bulkActionValues'][0],
							array( 'is_elementor' => SheetsPilotHelperElementor::isPostBuiltWithElementor( (int) $s_id ) )
						);
						continue;
					}

					wp_update_post([
						'ID' => $s_id,
						$data['bulkActionColumnName'] => wp_strip_all_tags(wp_unslash($data['bulkActionValues'][0])),
					]);
				}

				break;
			case "category":
				foreach ($data['ids'] as $s_id) {
					wp_set_post_terms((int)$s_id, array_map('intval', $data['bulkActionValues']), $data['bulkActionColumnName']);
				}
				break;
			case "tag":
				foreach ($data['ids'] as $s_id) {
					wp_set_post_terms((int)$s_id, array_map('intval', $data['bulkActionValues']), $data['bulkActionColumnName']);
				}
				break;
				//OLD CODE BELOW
		}


		return ['save_result' => true, 'success' => true];
	}

	/**
	 * Convert AI content-blocks insert value to Elementor/Gutenberg for cell save.
	 *
	 * @param mixed  $insert_value Original blocks payload or JSON string.
	 * @param string $display_text Plain preview text.
	 * @param array  $context      Optional postId, is_elementor, column.
	 * @return array{insert:string,show:string,is_elementor?:bool}|null Null when not a blocks payload.
	 */
	public static function convertPostContentInsertForCell( $insert_value, $display_text = '', $context = array() ) {
		if ( ! class_exists( 'SheetsPilot_ContentBlocks' ) ) {
			return null;
		}

		$blocks = SheetsPilot_ContentBlocks::resolve_blocks_payload_from_prompt_data( $insert_value );
		if ( ! is_array( $blocks ) && SheetsPilot_ContentBlocks::is_blocks_payload( $insert_value ) && is_array( $insert_value ) ) {
			$blocks = $insert_value;
		}
		if ( ! is_array( $blocks ) ) {
			return null;
		}

		$post_id = absint( SheetsPilotFunctions::getVal( $context, 'postId', 0 ) );
		$is_elementor = ! empty( $context['is_elementor'] );
		if ( ! $is_elementor && $post_id > 0 && class_exists( 'SheetsPilotHelperElementor' ) ) {
			$is_elementor = SheetsPilotHelperElementor::isPostBuiltWithElementor( $post_id );
		}

		return SheetsPilot_ContentBlocks::process_post_content_response( $blocks, $display_text, $is_elementor, $post_id );
	}

	/**
	 * Save post_content for standard posts, or Elementor meta for Elementor-built posts.
	 *
	 * Elementor layout is stored in Elementor meta (_elementor_data, etc.).
	 * post_content is replaced with a plain-text fallback (clears legacy JSON/HTML).
	 *
	 * @param int    $post_id Post ID.
	 * @param string $value   Cell value (plain text or legacy Elementor JSON).
	 * @param array  $args    Optional is_elementor, elementor_data, display_value.
	 * @return bool
	 */
	public static function savePostContentValue( $post_id, $value, $args = array() ) {
		
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			self::logSavePostContentValue(
				array(
					'post_id' => $post_id,
					'mode'    => 'invalid_post_id',
					'success' => false,
				)
			);
			return false;
		}

		$is_elementor = ! empty( $args['is_elementor'] );
		if ( ! $is_elementor && class_exists( 'SheetsPilotHelperElementor' ) ) {
			$is_elementor = SheetsPilotHelperElementor::isPostBuiltWithElementor( $post_id );
		}

		if ( class_exists( 'SheetsPilot_ContentBlocks' ) ) {
			$blocks_source = ( isset( $args['elementor_data'] ) && $args['elementor_data'] !== '' )
				? $args['elementor_data']
				: $value;
			$converted = self::convertPostContentInsertForCell(
				$blocks_source,
				self::resolveElementorFallbackDisplayText( $value, $args ),
				array(
					'postId'       => $post_id,
					'is_elementor' => $is_elementor,
				)
			);
			if ( is_array( $converted ) && ! empty( $converted['insert'] ) ) {
				if ( ! empty( $converted['is_elementor'] ) ) {
					$args['elementor_data'] = $converted['insert'];
					if ( ! empty( $converted['show'] ) ) {
						$value = $converted['show'];
					}
					if ( empty( $args['display_value'] ) && ! empty( $converted['show'] ) ) {
						$args['display_value'] = $converted['show'];
					}
				} else {
					$value = $converted['insert'];
				}
			}
		}

		$elementor_data = null;
		if ( isset( $args['elementor_data'] ) && $args['elementor_data'] !== '' ) {
			$elementor_data = class_exists( 'SheetsPilot_ContentBlocks' )
				? SheetsPilot_ContentBlocks::normalize_elementor_layout( $args['elementor_data'] )
				: null;
		}
		if ( $elementor_data === null && class_exists( 'SheetsPilot_ContentBlocks' ) ) {
			$elementor_data = SheetsPilot_ContentBlocks::normalize_elementor_layout( $value );
		}

		$save_mode      = 'elementor_meta';
		$used_fallback  = false;
		$elementor_raw  = isset( $args['elementor_data'] ) ? (string) $args['elementor_data'] : '';

		if ( $is_elementor && ( ! is_array( $elementor_data ) || empty( $elementor_data ) ) && class_exists( 'SheetsPilot_ContentBlocks' ) ) {
			$fallback_text = self::resolveElementorFallbackDisplayText( $value, $args );
			if ( $fallback_text !== '' ) {
				$elementor_data = SheetsPilot_ContentBlocks::fallback_elementor_layout_from_text( $fallback_text, $post_id );
				if ( is_array( $elementor_data ) && ! empty( $elementor_data ) ) {
					$used_fallback = true;
					$save_mode     = 'elementor_fallback_text';
				}
			}
		}

		if ( $is_elementor ) {
			if ( is_array( $elementor_data ) && ! empty( $elementor_data ) && class_exists( 'SheetsPilotHelper' ) ) {
				
				$post_content_fallback = self::resolveElementorPostContentFallbackText( $value, $args, $elementor_data );
				$saved                 = SheetsPilotHelperElementor::savePostData( $post_id, $elementor_data, $post_content_fallback );
				$log_data = array(
					'post_id'        => $post_id,
					'mode'           => $save_mode,
					'success'        => (bool) $saved,
					'is_elementor'   => true,
					'value_len'      => is_string( $value ) ? strlen( $value ) : 0,
					'elementor_len'  => strlen( wp_json_encode( $elementor_data ) ),
					'post_content_len' => strlen( $post_content_fallback ),
					'value_preview'  => self::getSavePostContentLogPreview( $value ),
				);
				if ( $used_fallback ) {
					$log_data['fallback']              = true;
					$log_data['elementor_json_error']  = self::getJsonDecodeErrorMessage( $elementor_raw );
				}
				self::logSavePostContentValue( $log_data );
				return $saved;
			}

			self::logSavePostContentValue(
				array(
					'post_id'              => $post_id,
					'mode'                 => 'elementor_no_data',
					'success'              => false,
					'is_elementor'         => true,
					'value_len'            => is_string( $value ) ? strlen( (string) $value ) : 0,
					'elementor_len'        => strlen( $elementor_raw ),
					'value_type'           => gettype( $value ),
					'value_preview'        => self::getSavePostContentLogPreview( $value ),
					'value_json_error'     => self::getJsonDecodeErrorMessage( $value ),
					'elementor_json_error' => self::getJsonDecodeErrorMessage( $elementor_raw ),
					'value_has_eltype'     => is_string( $value ) && strpos( $value, '"elType"' ) !== false,
				)
			);
			return false;
		}

		$post_content_text = (string) $value;
		if (
			class_exists( 'SheetsPilot_ContentBlocks' )
			&& SheetsPilot_ContentBlocks::is_elementor_insert_value( $post_content_text )
		) {
			self::logSavePostContentValue(
				array(
					'post_id'       => $post_id,
					'mode'          => 'skipped_elementor_json',
					'success'       => true,
					'is_elementor'  => false,
					'value_len'     => strlen( $post_content_text ),
					'value_preview' => self::getSavePostContentLogPreview( $post_content_text ),
				)
			);
			return true;
		}

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => wp_unslash( $post_content_text ),
			)
		);

		self::logSavePostContentValue(
			array(
				'post_id'       => $post_id,
				'mode'          => 'post_content',
				'success'       => true,
				'is_elementor'  => false,
				'value_len'     => strlen( $post_content_text ),
				'value_preview' => self::getSavePostContentLogPreview( $post_content_text ),
			)
		);

		return true;
	}

	/**
	 * Append savePostContentValue details to the AJAX session log.
	 *
	 * @param array $data Log payload.
	 */
	private static function logSavePostContentValue( $data ) {
		SheetsPilot_AjaxSessionLog::addData( 'savePostContentValue', $data );
	}

	/**
	 * Short preview for session log (avoid storing full Elementor JSON).
	 *
	 * @param mixed $value Cell value.
	 * @return string
	 */
	private static function getSavePostContentLogPreview( $value ) {
		$text = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );
		$text = preg_replace( '/\s+/', ' ', trim( $text ) );
		if ( strlen( $text ) > 120 ) {
			return substr( $text, 0, 120 ) . '…';
		}

		return $text;
	}

	/**
	 * @param mixed $value Candidate JSON string.
	 * @return string
	 */
	private static function getJsonDecodeErrorMessage( $value ) {
		if ( ! is_string( $value ) || trim( $value ) === '' ) {
			return '';
		}

		json_decode( trim( $value ), true );
		$error = json_last_error_msg();
		return $error === 'No error' ? '' : $error;
	}

	/**
	 * Plain display text for Elementor fallback when layout JSON is invalid.
	 *
	 * @param mixed $value Cell value.
	 * @param array $args  Save args (display_value, elementor_data, etc.).
	 * @return string
	 */
	private static function resolveElementorFallbackDisplayText( $value, $args ) {
		if ( isset( $args['display_value'] ) && is_string( $args['display_value'] ) && trim( $args['display_value'] ) !== '' ) {
			return trim( $args['display_value'] );
		}

		if ( ! is_string( $value ) || trim( $value ) === '' ) {
			return '';
		}

		$value = trim( $value );
		if ( class_exists( 'SheetsPilot_ContentBlocks' ) && SheetsPilot_ContentBlocks::normalize_elementor_layout( $value ) !== null ) {
			return '';
		}

		$first = ltrim( $value );
		if ( $first !== '' && ( $first[0] === '[' || $first[0] === '{' ) && strpos( $value, '"elType"' ) !== false ) {
			return '';
		}

		return $value;
	}

	/**
	 * Plain-text fallback for post_content when saving Elementor layout.
	 *
	 * @param mixed $value          Cell value.
	 * @param array $args           Save args.
	 * @param array $elementor_data Saved Elementor elements tree.
	 * @return string
	 */
	private static function resolveElementorPostContentFallbackText( $value, $args, $elementor_data ) {
		$text = self::resolveElementorFallbackDisplayText( $value, $args );

		if ( $text === '' && class_exists( 'SheetsPilot_ContentBlocks' ) && is_array( $elementor_data ) ) {
			$text = SheetsPilot_ContentBlocks::elementor_data_to_display_text( $elementor_data );
		}

		return class_exists( 'SheetsPilotHelper' )
			? SheetsPilotHelperElementor::plainTextForPostContent( $text )
			: trim( wp_strip_all_tags( (string) $text ) );
	}

	/**
	 * save the settings from data
	 */
	public static function saveEditedPostsData($data)
	{

		foreach ($data as $s_post_info) {

			if ($s_post_info['column'] == 'post_name') {
				$result = SheetsPilotCellEditor::check_slug_exists($s_post_info['value']);
				if ($result) {
					return ['save_result' => false, 'message' => __('Slug already exists!', 'sheetspilot'), 'cell_address' => $s_post_info['cell_address']];
				}
			}

			switch ($s_post_info['type']) {
				case "switch":

					//save elementor active
					if ($s_post_info['column'] ==  "elementor_active") {

						if ($s_post_info['value'] == 'yes') {							
							update_post_meta($s_post_info['post_id'],  '_elementor_edit_mode',  'builder');
							update_post_meta($s_post_info['post_id'],  '_elementor_template_type',  'wp-post');
							update_post_meta($s_post_info['post_id'],  '_elementor_version',  ELEMENTOR_VERSION );
						}
						if ($s_post_info['value'] == 'no') {							
							delete_post_meta($s_post_info['post_id'],  '_elementor_edit_mode' );
							delete_post_meta($s_post_info['post_id'],  '_elementor_template_type' );
							delete_post_meta($s_post_info['post_id'],  '_elementor_version' );
						}
						break;
					}
					// featured custom save
					if ($s_post_info['column'] ==  "plugins__featured") {



						$product = wc_get_product($s_post_info['post_id']);


						if ($product) {
							if ($s_post_info['value'] == 'yes') {
								$product->set_featured(true);
							}
							if ($s_post_info['value'] == 'no') {
								$product->set_featured(false);
							}
							$product->save();
						}
						break;
					}
					if ($s_post_info['column'] ==  "plugins__visible_in_pos") {

						if ($s_post_info['value'] == 'yes') {
							wp_remove_object_terms($s_post_info['post_id'], 'pos-hidden', 'pos_product_visibility', true);
						}
						if ($s_post_info['value'] == 'no') {
							wp_set_object_terms($s_post_info['post_id'], 'pos-hidden', 'pos_product_visibility', true);
						}
						break;
					}

					if ($s_post_info['column'] ==  "plugins__tribe_featured") {

						if ($s_post_info['value'] == 'yes') {
							update_post_meta($s_post_info['post_id'],  substr($s_post_info['column'], 8),  1);
						} else {
							delete_post_meta($s_post_info['post_id'],  substr($s_post_info['column'], 8));
						}
						break;
					}

					if (substr($s_post_info['column'], 0, 4) == 'acf_') {
						update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
						break;
					}
					if (substr($s_post_info['column'], 0, 8) == 'plugins_') {
						update_post_meta($s_post_info['post_id'],  substr($s_post_info['column'], 8),  $s_post_info['value']);
						break;
					}
					wp_update_post([
						'ID' => $s_post_info['post_id'],
						$s_post_info['column'] => wp_strip_all_tags(wp_unslash($s_post_info['value'])),
					]);
					break;
				case "text":

					if (substr($s_post_info['column'], 0, 4) == 'acf_') {
						update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
						break;
					}

					// patch for woocommerce price update
					if ( substr($s_post_info['column'],  8) == '_regular_price'  || substr($s_post_info['column'],  8) == '_sale_price') {
 
						update_post_meta($s_post_info['post_id'],  substr($s_post_info['column'], 8),  $s_post_info['value']);

						$regular_price = get_post_meta($s_post_info['post_id'], '_regular_price', true);
						$sale_price    = get_post_meta($s_post_info['post_id'], '_sale_price', true);

						if (!empty($sale_price) && floatval($sale_price) > 0) {
							update_post_meta($s_post_info['post_id'], '_price', $sale_price);
						} else {
							update_post_meta($s_post_info['post_id'], '_price', $regular_price);
						}

						// SheetsPilotFunctions::writeDebugFile( dirname( __FILE__ ) . '/$1111txt', array(
						// 	's_post_info'   => $s_post_info,
						// 	'column_suffix' => substr( $s_post_info['column'], 8 ),
						// 	'sale_price'    => $sale_price,
						// 	'regular_price' => $regular_price,
						// ) );
						break;
					}

					if (substr($s_post_info['column'], 0, 8) == 'plugins_') {
						update_post_meta($s_post_info['post_id'],  substr($s_post_info['column'], 8),  $s_post_info['value']);
						break;
					}

					

					wp_update_post([
						'ID' => $s_post_info['post_id'],
						$s_post_info['column'] => wp_strip_all_tags(wp_unslash($s_post_info['value'])),
					]);

					break;
				case "textarea":
					if (substr($s_post_info['column'], 0, 4) == 'acf_') {
						update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
						break;
					}
					if (substr($s_post_info['column'], 0, 8) == 'plugins_') {
						update_post_meta($s_post_info['post_id'],  substr($s_post_info['column'], 8),  $s_post_info['value']);
						break;
					}
					if ( $s_post_info['column'] === 'post_content' ) {
						self::savePostContentValue(
							(int) $s_post_info['post_id'],
							$s_post_info['value'],
							array(
								'is_elementor'   => ! empty( $s_post_info['is_elementor'] ),
								'elementor_data' => SheetsPilotFunctions::getVal( $s_post_info, 'elementor_data', '' ),
								'display_value'  => SheetsPilotFunctions::getVal( $s_post_info, 'display_value', '' ),
							)
						);
						break;
					}
					wp_update_post([
						'ID' => $s_post_info['post_id'],
						$s_post_info['column'] =>  wp_unslash($s_post_info['value']),
					]);

					break;
				case "wysiwyg":

					// prepare wysywyg content
					$modifiedWYSYWYG = str_replace('\n', "\n", wp_unslash($s_post_info['value']) );
					$paragraphs = preg_split('/\R{2,}/u', $modifiedWYSYWYG );
					$html = '';

					foreach ($paragraphs as $paragraph) {
						$html .= '<p>' . esc_html(trim($paragraph)) . '</p>';
					}

					if (substr($s_post_info['column'], 0, 4) == 'acf_') {
						update_field(substr($s_post_info['column'], 4),  $html, $s_post_info['post_id']);
						break;
					}
					wp_update_post([
						'ID' => $s_post_info['post_id'],
						$s_post_info['column'] =>  $html,
					]);

					break;
				case "acf_select":


					// product visibility
					if ($s_post_info['column'] ==  "plugins__product_visibility") {
						$product = wc_get_product($s_post_info['post_id']);
						$product->set_catalog_visibility($s_post_info['value']);
						$product->save();
						break;
					}


					if ($s_post_info['column'] ==  "plugins_product_type") {
						wp_set_object_terms($s_post_info['post_id'], $s_post_info['value'], 'product_type');
						wc_delete_product_transients($s_post_info['post_id']);

						break;
					}

					if (substr($s_post_info['column'], 0, 4) == 'acf_') {
						update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
						break;
					}
					if (substr($s_post_info['column'], 0, 8) == 'plugins_') {
						update_post_meta($s_post_info['post_id'],  substr($s_post_info['column'], 8),  $s_post_info['value']);
						break;
					}
					wp_update_post([
						'ID' => $s_post_info['post_id'],
						$s_post_info['column'] => wp_strip_all_tags(wp_unslash($s_post_info['value'])),
					]);
					break;
				case "calendar":
					wp_update_post([
						'ID' => $s_post_info['post_id'],
						$s_post_info['column'] => wp_strip_all_tags(wp_unslash($s_post_info['value'])),
					]);
					break;
				case "image":
					if ($s_post_info['column'] == 'post_image') {
						if ($s_post_info['value'] != '') {
							update_post_meta((int)$s_post_info['post_id'], '_thumbnail_id', (int)$s_post_info['value']);;
						} else {
							delete_post_meta((int)$s_post_info['post_id'], '_thumbnail_id');;
						}
					}
					if (substr($s_post_info['column'], 0, 4) == 'acf_') {
						update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
					}
					if (substr($s_post_info['column'], 0, 8) == 'plugins_') {
						update_field(substr($s_post_info['column'], 8),  $s_post_info['value'], $s_post_info['post_id']);
					}
					break;
				case "taxonomy":
					wp_set_post_terms((int)$s_post_info['post_id'], array_map('intval',  explode(',', $s_post_info['value'])), $s_post_info['column']);
					break;
				case "tag":
					$exploded_tags = explode(',', $s_post_info['value']);


					$existed_ids = [];
					foreach ($exploded_tags as $s_tag) {
						$s_tag = trim($s_tag);
						if ($s_tag === '') {
							continue;
						}
						$term = term_exists($s_tag, $s_post_info['column']);
						if ($term === 0 || $term === null) {
							$term = wp_insert_term($s_tag, $s_post_info['column']);
						}
						if (! is_wp_error($term)) {
							$existed_ids[] = (int) $term['term_id'];
						}
					}


					$res = wp_set_post_terms((int)$s_post_info['post_id'], $existed_ids, $s_post_info['column']);

					break;
				case "post_object":



					if (substr($s_post_info['column'], 0, 4) == 'acf_') {
						update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
						break;
					}
					if (substr($s_post_info['column'], 0, 8) == 'plugins_') {
						update_post_meta($s_post_info['post_id'],  substr($s_post_info['column'], 8),  $s_post_info['value']);
						break;
					}
					update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
					break;
				case "woo_post_object":



					if (substr($s_post_info['column'], 0, 4) == 'acf_') {
						update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
						break;
					}
					if (substr($s_post_info['column'], 0, 8) == 'plugins_') {
						update_post_meta($s_post_info['post_id'],  substr($s_post_info['column'], 8),  $s_post_info['value']);
						break;
					}
					update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
					break;
				case "acf_select":
					update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
					break;
				case "acf_gallery":


					if (substr($s_post_info['column'], 0, 8) == 'plugins_') {
						if ($s_post_info['column'] == 'plugins__product_image_gallery') {
							update_post_meta($s_post_info['post_id'],  substr($s_post_info['column'], 8),  implode(',', $s_post_info['value']));
							break;
						}
					}
					update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
					break;
				case "acf_woo_gallery":


					if (substr($s_post_info['column'], 0, 8) == 'plugins_') {
						if ($s_post_info['column'] == 'plugins__product_image_gallery') {

							// on delete patch					 
							update_post_meta($s_post_info['post_id'],  substr($s_post_info['column'], 8),  $s_post_info['value'] );
							break;
						}
					}
					update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
					break;
				case "color_picker":
					update_field(substr($s_post_info['column'], 4),  $s_post_info['value'], $s_post_info['post_id']);
					break;
			}
		}

		return ['save_result' => true, 'success' => true];
	}

	/**
	 * save the settings from data
	 */
	public static function processPostsWithGPT($data, $prompt)
	{
		if (SheetsPilotGlobals::$isPro != true) {
			SheetsPilotFunctions::throwError(__('AI assistant is available in Pro version.', 'sheetspilot'));
		}
		if (! class_exists('SheetsPilot_UseChatGPT', false)) {
			SheetsPilotFunctions::throwError(__('Pro AI module is not loaded.', 'sheetspilot'));
		}

		$gpt_prcessing_obj = new SheetsPilot_UseChatGPT();
		$result = $gpt_prcessing_obj->makeChatGTPCall($data, $prompt);

		return $result;
	}

	/**
	 * apply a custom prompt to table data
	 */
	public static function applyPromptToTable($table_data, $prompt_text)
	{
		$column_name = isset($table_data['column']) ? $table_data['column'] : '';
		$cell_type   = isset($table_data['cellType']) ? $table_data['cellType'] : '';
		$is_image_column = ($column_name === 'post_image' || $cell_type === 'image' || $cell_type === 'acf_gallery' || $cell_type === 'acf_woo_gallery' );

		if ($is_image_column) {
			$res = self::applyImageGenerationToTable($table_data, $prompt_text);
			return $res;
		}

		$prepared_prompt_text = $prompt_text;
		$cell_content_type = null;
		if (SheetsPilotGlobals::$isPro == true) {
			$prepared_prompt_text = SheetsPilot_Prompts::getPromptFromTable($table_data, $prompt_text, $cell_content_type);
		}

		if (self::$debugApplyPrompt == true) {
			dmp($prepared_prompt_text);
			exit();
		}

		$response = self::processPostsWithGPT($table_data, $prepared_prompt_text);

		if (is_array($response)) {
			$response['cell_content_type'] = $cell_content_type;
		}

		return $response;
	}

	/**
	 * Generate an image via OpenAI Images API. Saves to pending storage and returns request_id + preview_url
	 * so the client can show a preview and let the user apply or discard.
	 *
	 * @param array  $table_data  Table/cell data (postId, column, etc.).
	 * @param string $prompt_text User prompt for image generation.
	 * @return array type => 'pending_image', request_id, preview_url, post_id, column.
	 */
	public static function applyImageGenerationToTable($table_data, $prompt_text)
	{
 
		if (SheetsPilotGlobals::$isPro != true) {
			SheetsPilotFunctions::throwError(__('AI image generation is available in Pro version.', 'sheetspilot'));
		}
		if (! class_exists('SheetsPilot_UseChatGPT', false)) {
			SheetsPilotFunctions::throwError(__('Pro AI module is not loaded.', 'sheetspilot'));
		}

		SheetsPilot_ImageProcessing::cleanupExpired();

		$current_value     = isset($table_data['value']) ? (string) $table_data['value'] : '';
		$current_image_src = '';
		$attachment_id     = 0;

		if ( ! empty( $table_data['imageAttachmentId'] ) ) {
			$attachment_id = absint( $table_data['imageAttachmentId'] );
		}

		if ($current_value !== '') {
			if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $current_value, $m)) {
				$current_image_src = isset($m[1]) ? (string) $m[1] : '';
			} elseif (filter_var($current_value, FILTER_VALIDATE_URL)) {
				$current_image_src = $current_value;
			} elseif (preg_match('/^\d+$/', trim($current_value))) {
				$attachment_id = absint(trim($current_value));
			}

			if ($attachment_id === 0 && preg_match('/data-id\s*=\s*["\'](\d+)["\']/i', $current_value, $id_m)) {
				$attachment_id = absint($id_m[1]);
			}
		}

		if ($attachment_id > 0 && $current_image_src === '') {
			$url_from_id = wp_get_attachment_url($attachment_id);
			if (is_string($url_from_id) && $url_from_id !== '') {
				$current_image_src = $url_from_id;
			}
		}

		$ctx_action = trim( (string) SheetsPilotFunctions::getVal( $table_data, 'context_menu_action', '' ) );
		$is_change_ratio_action = SheetsPilot_ImageProcessing::parseRatioFromChangeImageRatioAction( $ctx_action ) !== '';

		$image_action = trim( (string) SheetsPilotFunctions::getVal( $table_data, 'image_action', 'create' ) );
		if ( $is_change_ratio_action ) {
			$image_action = 'edit';
		} elseif ( $image_action === 'generate-image' || $image_action !== 'edit' ) {
			$image_action = 'create';
		}

		if (
			! $is_change_ratio_action
			&& $image_action === 'edit'
			&& ( $current_image_src === '' || stripos( $current_image_src, 'placeholder.png' ) !== false )
		) {
			$image_action = 'create';
		}

		$table_data['image_action'] = $image_action;

		$prepared_prompt_text = SheetsPilot_Prompts::getPromptFromTable($table_data, $prompt_text);

		$gpt_obj = new SheetsPilot_UseChatGPT();

		$column_key = 'post_image';
		if (isset($table_data['column'])) {
			$normalized_column = SheetsPilotFunctions::normalize_cell_rule_field_key((string) $table_data['column']);
			if ($normalized_column !== '') {
				$column_key = $normalized_column;
			}
		}

		if ($image_action === 'edit') {
			$edit_size = null;
			$ratio_from_menu = SheetsPilot_ImageProcessing::parseRatioFromChangeImageRatioAction( $ctx_action );
			if ($ratio_from_menu !== '') {
				$mapped_size = SheetsPilot_ImageProcessing::mapAspectRatioToImageSize($ratio_from_menu);
				if ($mapped_size !== '' && $mapped_size !== 'auto') {
					$edit_size = $mapped_size;
				}
			}
			$image_url        = $gpt_obj->makeImageEditCall($current_image_src, $prepared_prompt_text, $edit_size, $attachment_id);
			$quality_override = 'default';
		} else {
			$resolved         = SheetsPilot_ImageProcessing::resolveImageSettingsForTable($table_data, $column_key);
			$image_size       = $resolved['image_size'];
			$quality_override = $resolved['quality_override'];
			$format_override  = $resolved['format_override'];
			$image_url        = $gpt_obj->makeImageGenerationCall($prepared_prompt_text, $image_size, $quality_override, $format_override, $table_data );

		}

		// check if image is in_progress
		if( $image_url == 'in_progress' || $image_url == 'queued' ){
			return array(
				'type'  => 'pending_image',
				'status' => $image_url
			);
		}
	 

		$post_id = isset($table_data['postId']) ? (int) $table_data['postId'] : 0;

		$context_table_data = $table_data;
		unset( $context_table_data['pending_request_id'] );

		$save_args = array(
			'generation_context' => array(
				'table_data'  => $context_table_data,
				'prompt_text' => $prompt_text,
			),
		);
		if ( ! empty( $table_data['pending_request_id'] ) ) {
			$save_args['request_id'] = sanitize_file_name( (string) $table_data['pending_request_id'] );
		}

		$pending = SheetsPilot_ImageProcessing::savePending($image_url, $post_id, $column_key, $quality_override, $save_args);

		return array(
			'type'        => 'pending_image',
			'request_id'  => $pending['request_id'],
			'preview_url' => $pending['preview_url'],
			'post_id'     => $post_id,
			'column'      => $column_key,
			'file_size'   => isset( $pending['file_size'] ) ? (int) $pending['file_size'] : 0,
			'file_type'   => isset( $pending['file_type'] ) ? (string) $pending['file_type'] : '',
			'width'       => isset( $pending['width'] ) ? (int) $pending['width'] : 0,
			'height'      => isset( $pending['height'] ) ? (int) $pending['height'] : 0,
		);
	}

	/**
	 * Losslessly compress the attachment in an image cell (context menu action).
	 *
	 * @param array $table_data Editor table snapshot from the client.
	 * @return array Compressed attachment preview metadata.
	 */
	public static function compressImageFromTable( $table_data ) {
		if ( ! is_array( $table_data ) ) {
			SheetsPilotFunctions::throwError( __( 'Table data is invalid.', 'sheetspilot' ) );
		}

		$attachment_id = SheetsPilot_ImageProcessing::resolveAttachmentIdFromTableData( $table_data );
		if ( $attachment_id <= 0 ) {
			SheetsPilotFunctions::throwError( __( 'No image to compress in this cell.', 'sheetspilot' ) );
		}

		return SheetsPilot_ImageProcessing::compressAttachmentImage( $attachment_id );
	}

	/**
	 * echo post type structure
	 */
	public static function getPostTypeStructure($postType)
	{
		if (!$postType) {
			$postType = 'post';
		}
		$users_list = [];
		$all_authors = get_users();
		foreach ($all_authors as $s_author) {

			// generate post name
			$first_name = get_user_meta($s_author->data->ID, 'first_name', true);
			$last_name = get_user_meta($s_author->data->ID, 'last_name', true);

			$name2use = [];
			if (trim($first_name) != '') {
				$name2use[] = $first_name;
			}
			if (trim($last_name) != '') {
				$name2use[] = $last_name;
			}

			if (count($name2use) == 0) {
				$name2use[] = $s_author->data->user_login;
			}



			$users_list[] = ['id' => $s_author->data->ID, 'name' => implode(' ', $name2use)];
		}
		$init_columns = [
			[
				'title'    => '<span class="unlimitedai-plugin__th-bulk-edit"><span class="unlimitedai-plugin__th-bulk-edit__text">Bulk Edit</span><label class="unlimitedai-plugin__th-bulk-edit__label"><input type="checkbox" class="unlimitedai-plugin__th-bulk-edit__select-input"></label></span>',
				'name'     => 'bulk',
				'width'    => 40,
				'type'     => 'bulk_checkbox',
				'readonly' => true,
				'orderable'   => false,
				'switchable'   => false,
				'modal_off'   => true,
			],
			[
				'title'    => 'ID',
				'name'     => 'id',
				'width'    => 40,
				'type'     => 'text',
				'readonly' => true,
				'orderable'   => false,
				'switchable'   => false,
				'modal_off'   => true,
			],
			[
				'title'       => __('Post Title', 'sheetspilot'),
				'name'        => 'post_title',
				'width'       => 250,
				'type'        => 'textarea',
				'dev_type'        => 'text',
				'rows'        => 2,
				'orderable'   => false,
				'switchable'   => false,
				'searchable'  => true,
				'column_search'  => 'text',
				'bottom_manage' =>
				'<span class="unlimitedai-plugin__container">
					<span class="post_manage_icon has-tooltip duplicate_post_button unlimitedai-plugin__dropdown-button"  data-title="' . SheetsPilotGlobals::$duplicatePost . '" ><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path></svg></span>
					<span class="unlimitedai-plugin__dropdown unlimitedai-plugin__duplicate_row_dropdown">
							<span class="unlimitedai-plugin__dropdown-item unlimitedai-plugin__dropdown-item--custom" data-value="custom" aria-haspopup="dialog" aria-expanded="false">' . __('Custom number...', 'sheetspilot') . '</span>
							<span class="unlimitedai-plugin__dropdown-item" data-value="3">' . __('Duplicate', 'sheetspilot') . ' 3 ' . __('copies', 'sheetspilot') . '</span>
							<span class="unlimitedai-plugin__dropdown-item" data-value="5">' . __('Duplicate', 'sheetspilot') . ' 5 ' . __('copies', 'sheetspilot') . '</span>
							<span class="unlimitedai-plugin__dropdown-item" data-value="10">' . __('Duplicate', 'sheetspilot') . ' 10 ' . __('copies', 'sheetspilot') . '</span>
					</span>
					<!--
					<div id="uai-add-duplicate-post-popup" class="unlimitedai-plugin__popup" role="dialog" aria-modal="true" aria-labelledby="ubai_popup_title">
						<div class="unlimitedai-plugin__popup-container">
								<div class="unlimitedai-plugin__popup-header">
										<h2 class="unlimitedai-plugin__popup-header__title">' . __('Duplicate Row', 'sheetspilot') . '</h2>
										<p class="unlimitedai-plugin__popup-header__subtitle">' . __('Number of copies', 'sheetspilot') . '</p>
										<button class="unlimitedai-plugin__popup-close" aria-label="Close">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
										</button>
								</div>
								<div class="unlimitedai-plugin__popup-body">
									<div class="unlimitedai-plugin__add-row__number">
										<input class="unlimitedai-plugin__add-row__number-input" type="number" placeholder="' . __('Enter number...', 'sheetspilot') . '" min="1">
									</div>
									<div class="unlimitedai-plugin__add-row__buttons">
										<button class="unlimitedai-plugin__add-row__button cancel">' . __('Cancel', 'sheetspilot') . '</button>
										<button class="unlimitedai-plugin__add-row__button add">' . __('Duplicate', 'sheetspilot') . '</button>
									</div>
								</div>
						</div>
					</div>
					-->
				</span>' .
					'<span class="post_manage_icon has-tooltip delete_post_button"  data-title="' . SheetsPilotGlobals::$deletePostText . '" ><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-3.5 h-3.5 text-muted-foreground"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg></span>' .
					'<span class="post_manage_icon preview_post preview_post_icon has-tooltip" data-title="' . SheetsPilotGlobals::$previewPostText . '" ><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg></span>' .
					/*'<span class="post_manage_icon edit_post_modal edit_post_modal_icon has-tooltip" aria-expanded="false" aria-haspopup="dialog" data-title="' . SheetsPilotGlobals::$editPostTooltipText . '" ><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>' . */
					'<span class="post_manage_icon edit_in_new_window has-tooltip" data-title="' . SheetsPilotGlobals::$editPostInNewWindowTooltipText . '" ><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"></path><path d="m21 3-9 9"></path><path d="M15 3h6v6"></path></svg></span>',
			],
			[
				'title' => __('Excerpt', 'sheetspilot'),
				'switchable'   => true,
				'name'  => 'post_excerpt',
				'width' => 300,
				'type'  => 'textarea',
				'dev_type'        => 'text',
				'rows'  => 5,
				'column_search'  => 'text',
			],
			[
				'title' => __('Content', 'sheetspilot'),
				'orderable'   => true,
				'switchable'   => true,
				'searchable'  => true,
				'name'  => 'post_content',
				'width' => 300,
				'type'  => 'textarea',
				'dev_type'        => 'text',
				'rows'  => 5,
				'column_search'  => 'text',
				'bottom_manage' => SheetsPilotHelperElementor::getPostContentEditManageIconHtml( false ),
			],
			...( SheetsPilotGlobals::$isElementorActive ? 
			
			[[
				'title' => __('Elementor', 'sheetspilot'),
				'orderable'   => true,
				'switchable'   => true,
				'searchable'  => false,
				'name'  => 'elementor_active',
				'type'  => 'switch',
				'dev_type'        => 'meta_field',
				'column_search'  => 'filter',
				'source' => [	 
					['id' => 'no',   'name' => __('Non Elementor', 'sheetspilot' ) ],
					['id' => 'yes', 'name' => __('Elementor', 'sheetspilot' ) ],					
				],
			]] : []
			
			),
			[
				'title' => __('Slug', 'sheetspilot'),
				'name'  => 'post_name',
				'dev_type'        => 'text',
				'width' => 150,
				'type'  => 'text',
				'column_search'  => 'text',
			],
			[
				'title'  => __('Author', 'sheetspilot'),
				'name'   => 'post_author',
				'dev_type'        => 'text',
				'width'  => 150,
				'type'   => 'acf_select',
				'source' => $users_list,
				'column_search'  => 'filter',
				// or populate manually / dynamically
			],
			[
				'title' => __('Status', 'sheetspilot'),
				'name'  => 'post_status',
				'dev_type'        => 'text',
				'width' => 150,
				'type'  => 'acf_select',
				'column_search'  => 'filter',
				'source' => [
					['id' => 'draft',   'name' => __('Draft', 'sheetspilot')],
					['id' => 'pending', 'name' => __('Pending', 'sheetspilot')],
					['id' => 'future',  'name' => __('Future', 'sheetspilot')],
					['id' => 'private', 'name' => __('Private', 'sheetspilot')],
					['id' => 'publish', 'name' => __('Published', 'sheetspilot')],
					['id' => 'inherit', 'name' => __('Inherit', 'sheetspilot')],
					// ['id' => 'trash',   'name' => __('Trash', 'sheetspilot')],
				],
			],
			[
				'title' => __('Date', 'sheetspilot'),
				'name'  => 'post_date',
				'dev_type'        => 'text',
				'width' => 180,
				'column_search'  => 'calendar',
				'type'  => 'calendar',

			],
			[
				'title'    => __('Featured Image', 'sheetspilot'),
				'name'     => 'post_image',
				'dev_type'        => 'meta_field',
				'width'    => 200,
				'type'     => 'image',
				'column_search'  => 'text',
				'readonly' => true,
				'bottom_manage' => '
				' . self::get_image_cell_download_icon_html() . '
				<span class="inline_edit_image_field has-tooltip" data-title="' . SheetsPilotGlobals::$inlineEditImageText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>
				<span class="edit_image_field has-tooltip" data-title="' . SheetsPilotGlobals::$addImageText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image w-3.5 h-3.5 text-muted-foreground"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path></svg></span>
				<span class="has-tooltip delete_image_field" data-title="' . SheetsPilotGlobals::$deleteImageText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-3.5 h-3.5 text-muted-foreground"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg></span>
				',
				'related_editor_fields' => [
					[
						'data_table' => 'image_preview',

					],
					[
						'editor_type' => 'textarea',
						'data_table' => 'postmeta',
						'title' => __('Alternative Text', 'sheetspilot'),
						'name' => '_wp_attachment_image_alt',
						'placeholder' => __('Describe image for accessibility', 'sheetspilot'),
						'subtitle' => __('Leave empty if the image is purely decorative', 'sheetspilot'),
					],

					[
						'editor_type' => 'text',
						'data_table' => 'posts',
						'title' => __('Title', 'sheetspilot'),
						'name' => 'post_title',
						'placeholder' => __('Image title', 'sheetspilot'),
					],
					[
						'editor_type' => 'textarea',
						'data_table' => 'posts',
						'title' => __('Caption', 'sheetspilot'),
						'name' => 'post_excerpt',
						'placeholder' => __('Caption displayed with the image', 'sheetspilot'),
					],
					[
						'editor_type' => 'textarea',
						'data_table' => 'posts',
						'title' => __('Description', 'sheetspilot'),
						'name' => 'post_content',
						'placeholder' => __('Detailed description of the image', 'sheetspilot'),
					],
					[
						'editor_type' => 'text',
						'data_table' => 'posts',
						'title' => __('File URL', 'sheetspilot'),
						'name' => 'guid',
						'has_copy' => true
					],
				]
			],

		];


		// acf fields addition
		$acf_extra_fields = SheetsPilotCellEditor::get_acf_fields_for_post_type($postType);


		// process acf taxonomies
		$taxonomies = get_object_taxonomies($postType, 'names');

		foreach ($taxonomies as $s_tax_slug) {

			/** fix for product to hide product type */
			if (($postType == 'product' && $s_tax_slug == 'product_type') || ($postType == 'product' &&  $s_tax_slug == 'product_visibility') || ($postType == 'product' &&  $s_tax_slug == 'pos_product_visibility')) {
				continue;
			}


			$taxonomy_info = get_taxonomy($s_tax_slug);

			if ($taxonomy_info->hierarchical == true) {
				$init_columns[] = [
					'title'    => $taxonomy_info->label,
					'name'     => $taxonomy_info->name,
					'width'    => 250,
					'type'     => 'taxonomy',
					'manage'   => '<span class="dashicons dashicons-plus add_taxonomy has-tooltip" data-title="' . SheetsPilotGlobals::$addTaxonomyText . '" ></span>',
					'readonly' => true,
					'column_search'  => 'filter',
					'is_multiselect'  => true,
					'is_pro' => (in_array('taxonomy', SheetsPilotGlobals::$proFilesList) ? true : false)
				];
			} else {
				$init_columns[] = [
					'title'    => $taxonomy_info->label,
					'name'     => $taxonomy_info->name,
					'width'    => 250,
					'type'     => 'tag',
					'dev_type'        => 'taxonomy',
					'manage'   => '',
					'readonly' => false,
					'source'   => SheetsPilotCellEditor::getNonHierarhicalTaxOptions($taxonomy_info->name),
					'column_search'  => 'filter',
					'is_multiselect'  => true,
					'is_pro' => (in_array('tag', SheetsPilotGlobals::$proFilesList) ? true : false)
				];
			}
		}

		foreach ($acf_extra_fields as $s_field_data) {
			$readonly = false;
			$editor_type = '';
			$bottom_manage = '';
			$manage = '';
			// acf type to editor relations
			if (in_array($s_field_data["type"], ['text', 'number', 'range', 'email', 'url', 'password'])) {
				$editor_type = 'textarea';
				$inner_column_search_type = 'text';
			}

			if (in_array($s_field_data["type"], ['select', 'radio', 'checkbox'])) {
				$editor_type = 'acf_select';
				$inner_column_search_type = 'filter';
			}

			if (in_array($s_field_data["type"], ['textarea'])) {
				$editor_type = 'textarea';
				$inner_column_search_type = 'text';
			}
			if (in_array($s_field_data["type"], ['post_object'])) {
				$editor_type = 'post_object';
				$inner_column_search_type = 'filter';
			}
			if (in_array($s_field_data["type"], ['gallery'])) {
				$editor_type = 'acf_gallery';
				$readonly = true;
				$inner_column_search_type = 'text';
				$bottom_manage =
					'<span class="add_gallery_image has-tooltip" data-title="' . SheetsPilotGlobals::$addGalleryImageText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg></span>
					<span class="delete_all_images has-tooltip" data-title="' . SheetsPilotGlobals::$dropGalleryText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-3.5 h-3.5 text-muted-foreground"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg></span>';
			}
			if (in_array($s_field_data["type"], ['repeater'])) {
				$readonly = true;
				$editor_type = 'repeater';
				$bottom_manage =
					'<span class="new_edit_repeater_field has-tooltip" data-title="' . SheetsPilotGlobals::$editRepeaterText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>
					<span class="delete_repeater_data has-tooltip" data-title="' . SheetsPilotGlobals::$dropRepeaterText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-3.5 h-3.5 text-muted-foreground"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg></span>
				';
				$inner_column_search_type = 'text';
			}
			if (in_array($s_field_data["type"], ['wysiwyg'])) {
				$readonly = true;
				$editor_type = 'wysiwyg';
				$bottom_manage =
					'<span class="edit_wysiwyg_field has-tooltip" data-title="' . SheetsPilotGlobals::$editWyswygText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>
				';
				$inner_column_search_type = 'text';
			}
			if (in_array($s_field_data["type"], ['image'])) {
				$readonly = true;
				$inner_column_search_type = 'text';
				$bottom_manage =
					self::get_image_cell_download_icon_html() .
					'<span class="edit_image_field has-tooltip" data-title="' . SheetsPilotGlobals::$addImageText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>
					<span class="has-tooltip delete_image_field" data-title="' . SheetsPilotGlobals::$deleteImageText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-3.5 h-3.5 text-muted-foreground"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg></span>';

				$bottom_manage = '
				' . self::get_image_cell_download_icon_html() . '
				<span class="inline_edit_image_field has-tooltip" data-title="' . SheetsPilotGlobals::$inlineEditImageText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>
				<span class="edit_image_field has-tooltip" data-title="' . SheetsPilotGlobals::$addImageText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image w-3.5 h-3.5 text-muted-foreground"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path></svg></span>
				<span class="has-tooltip delete_image_field" data-title="' . SheetsPilotGlobals::$deleteImageText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-3.5 h-3.5 text-muted-foreground"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg></span>
				';
			}

			$args = [
				'title'    => $s_field_data["label"],
				'name'     => 'acf_' . $s_field_data["name"],
				'width'    => 100,
				'type'     => ($editor_type != '' ? $editor_type : $s_field_data["type"]),
				'dev_type' => 'meta_field',
				'acf_type' => $s_field_data["type"],
				'manage'   => $manage,
				'source'   => SheetsPilotCellEditor::getSelectOptions($s_field_data["key"]),
				'is_acf'   => true,
				'bottom_manage'   => $bottom_manage,
				'readonly' => $readonly,
				'column_search'  => $inner_column_search_type,
				'is_pro' => (in_array($editor_type, SheetsPilotGlobals::$proFilesList) ? true : false)
			];

			$init_columns[] = $args;
		}


		// add filter for plugins
		$init_columns = apply_filters('sheetspilot_filter_table_columns', $init_columns, $postType );
		

		// process Custom plugin fields
		foreach (SheetsPilotGlobals::$rankMathFields as $slug => $field_data) {
			$args = [
				'title'    => __('Rank Math ', 'sheetspilot') . $field_data['label'],
				'name'     => 'plugins_' . $slug,
				'width'    => 100,
				'type'     => $field_data['type'],
				'readonly' => ( isset( $field_data['readonly'] ) && $field_data['readonly'] ? true : false),
				'orderable'   => true,
				'switchable'   => true,
				'column_search'  => 'text',
				'is_pro' => (in_array($field_data['type'], SheetsPilotGlobals::$proFilesList) ? true : false)
			];
			$init_columns[] = $args;
		}

		// process Custom plugin fields
		foreach (SheetsPilotGlobals::$yoastFields as $slug => $field_data) {
			$args = [
				'title'    => __('Yoast ', 'sheetspilot') . $field_data['label'],
				'name'     => 'plugins_' . $slug,
				'width'    => 100,
				'type'     => $field_data['type'],
				'readonly' => ( isset( $field_data['readonly'] ) && $field_data['readonly'] ? true : false),
				'orderable'   => true,
				'switchable'   => true,
				'column_search'  => 'text',
				'is_pro' => (in_array($field_data['type'], SheetsPilotGlobals::$proFilesList) ? true : false)
			];
			$init_columns[] = $args;
		}

		// SEO press
		foreach (SheetsPilotGlobals::$seoPress as $slug => $field_data) {
			$args = [
				'title'    => __('SEOPress ', 'sheetspilot') . $field_data['label'],
				'name'     => 'plugins_' . $slug,
				'width'    => 100,
				'type'     => $field_data['type'],
				'readonly' => ( isset( $field_data['readonly'] ) && $field_data['readonly'] ? true : false),
				'orderable'   => true,
				'switchable'   => true,
				'column_search'  => 'text',
				'is_pro' => (in_array($field_data['type'], SheetsPilotGlobals::$proFilesList) ? true : false)
			];
			$init_columns[] = $args;
		}

		if ( $postType == 'attachment') {
			foreach (SheetsPilotGlobals::$mediaPostTypeFields as $slug => $field_data) {
				
				$args = [
					'title'    => $field_data['label'],
					'name'     => 'plugins_' . $slug,
					'width'    => 100,
					'type'     => $field_data['type'],
					'dev_type'     => $field_data['dev_type'],
					'readonly' => ( isset( $field_data['readonly'] ) && $field_data['readonly'] ? true : false),
					'orderable'   => true,
					'switchable'   => true,
					'source'   => $field_data['source'],
					'search_post_type'   => $field_data['search_post_type'],
					'column_search'  => $field_data['column_search'],
					'related_editor_fields' => $field_data['related_editor_fields'],

					// product type fields
					'has_multirelations' => $field_data['has_multirelations'],
					'bottom_manage' => $field_data['bottom_manage'],
					'is_pro' => (in_array($field_data['type'], SheetsPilotGlobals::$proFilesList) ? true : false)
				];	
				$init_columns[] = $args;	
			}
		}

		if ($postType == 'product') {

			// patch column names for products
			$post_excerpt_index = array_search('post_excerpt', array_column($init_columns, 'name'));
			$init_columns[$post_excerpt_index]['title'] = __('Short Description', 'sheetspilot');
			$post_content_index = array_search('post_content', array_column($init_columns, 'name'));
			$init_columns[$post_content_index]['title'] = __('Description', 'sheetspilot');


			foreach (SheetsPilotGlobals::$wooCommerceFields as $slug => $field_data) {
				$args = [
					'title'    => $field_data['label'],
					'name'     => 'plugins_' . $slug,
					'width'    => 100,
					'type'     => $field_data['type'],
					'dev_type'     => $field_data['dev_type'],
					'readonly' => ( isset( $field_data['readonly'] ) && $field_data['readonly'] ? true : false),
					'orderable'   => true,
					'switchable'   => true,
					'source'   => $field_data['source'],
					'search_post_type'   => $field_data['search_post_type'],
					'column_search'  => $field_data['column_search'],
					'related_editor_fields' => $field_data['related_editor_fields'],

					// product type fields
					'has_multirelations' => $field_data['has_multirelations'],
					'bottom_manage' => $field_data['bottom_manage'],
					'is_pro' => (in_array($field_data['type'], SheetsPilotGlobals::$proFilesList) ? true : false)
				];

				// product type fields
				foreach ($field_data['source'] as $s_source) {
					if (isset($field_data['relation_' . $s_source['id']])) {
						$args['relation_' . $s_source['id']] = $field_data['relation_' . $s_source['id']];
					}
				}

				$init_columns[] = $args;
			}
		}

		// event tribe
		if ($postType == 'tribe_events') {

			// patch column names for products


			foreach (SheetsPilotGlobals::$theEventsCalendarFileds as $slug => $field_data) {
				$args = [
					'title'    => $field_data['label'],
					'name'     => 'plugins_' . $slug,
					'width'    => 100,
					'type'     => $field_data['type'],
					'dev_type'     => $field_data['dev_type'],
					'readonly' => ( isset( $field_data['readonly'] ) && $field_data['readonly'] ? true : false),
					'orderable'   => true,
					'switchable'   => true,
					'source'   => $field_data['source'],
					'search_post_type'   => $field_data['search_post_type'],
					'column_search'  => $field_data['column_search'],
					'related_editor_fields' => $field_data['related_editor_fields'],

					// product type fields
					'has_multirelations' => $field_data['has_multirelations'],
					'bottom_manage' => $field_data['bottom_manage'],
					'is_pro' => (in_array($field_data['type'], SheetsPilotGlobals::$proFilesList) ? true : false)
				];



				$init_columns[] = $args;
			}
		}

		return $init_columns;
	}

	/**
	 * get taxonomy content html
	 */
	public static function getCellCategoryContent($post_id, $taxonomy)
	{
		ob_start();
		$tax_data = get_taxonomy($taxonomy);
		$out_data =  wp_terms_checklist(
			$post_id,
			[
				'checked_ontop' => false,
				'echo' => false,
				'taxonomy' => $taxonomy,
			]
		);

		// add slugs
		$all_terms = get_terms(array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		));
		foreach ($all_terms as $s_inner_term) {
			$out_data = str_replace(' value="' . $s_inner_term->term_id . '" ', ' value="' . $s_inner_term->term_id . '" data-slug="' . $s_inner_term->slug . '"', $out_data);
		}

		$get_existed_terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));
		echo '<div class="ubai_tax_container">';
		echo '<div class="ubai_tax_block">';
		echo '</div>
			<input type="hidden" value="' . esc_attr(implode(',', $get_existed_terms)) . '" class="ubai_tax_value" />';
		echo '<div class="category_editor">';
		echo '<div class="category_editor-header"><h4 class="category_editor-title">' . esc_html($tax_data->labels->name) . '</h4><span class="category_editor-close is_flex align_items_center justify-content-c cursor-pointer"><svg class="size-14" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg></span></div>';
		echo '<div class="category_editor-body">';
		echo '<div class="category_search_list"><input type="text" class="search_tax tax_quick_search" placeholder="' . esc_attr(__('Search', 'sheetspilot')) . '"><span class="search_tax_close_icon d-flex">
		
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
		</div>';
		echo '<ul class="category_cell_td_list">';
	
		echo wp_kses($out_data, array(
			'li' => array(
				'id'    => true,
				'class' => true,
			),
			'label' => array(
				'class' => true,
			),
			'input' => array(
				'value'      => true,
				'data-slug'  => true,
				'type'       => true,
				'name'       => true,
				'id'         => true,
				'checked'    => true,
				'disabled'   => true,
			),
			'ul' => array(
				'class' => true,
			),
		));


		echo '</ul>';
		echo '</div>';
		echo '<div class="category_editor-footer">';
		echo '<div class="category_editor-footer__title">Add ' . esc_html($tax_data->labels->name) . '</div>';
		echo '<div class="category_editor-footer__container">';
		echo '<label class="category_editor-footer__container-label" for="new_tax_value_input">' . esc_html(__('New', 'sheetspilot')) . ' ' . esc_html($tax_data->labels->name) . ' ' . esc_html(__('Name', 'sheetspilot')) . '</label>';
		echo '<input id="new_tax_value_input" type="text" class="new_tax_value">';
		echo '<label class="category_editor-footer__container-label" for="category_selector_element">' . esc_html(__('Parent', 'sheetspilot')) . ' ' . esc_html($tax_data->labels->name) . '</label>';
		$out_data =  wp_dropdown_categories([
			'show_option_none'  => __('— Parent —', 'sheetspilot'),
			'taxonomy' => $taxonomy,
			'id' => 'category_selector_element' . $post_id, //id must be unique
			'class' => 'category_selector',
			'echo' => 0,
			'hide_if_empty'     => false,
			'hide_empty'     => false,
			'hierarchical' => true
		]);

		echo wp_kses($out_data, array(
			'select' => array(
				'name'  => true,
				'id'    => true,
				'class' => true,
			),
			'option' => array(
				'value'    => true,
				'class'    => true,
				'selected' => true,
			),
		));


		echo '<button class="new_tax_add unlimitedai-plugin__btn" >' . esc_html(__('Add', 'sheetspilot')) . ' ' . esc_html($tax_data->labels->name) . '<span class="add_category_loader_save loader_round" style="display: none;"></span></button>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		$categories_html = ob_get_clean();

		return $categories_html;
	}

	/**
	 * get select possuble options
	 */
	public static function getSelectOptions($field_name)
	{
		$field_data = acf_get_field($field_name);

		$data_array = [];
		if (isset($field_data['choices']))
			foreach ($field_data['choices'] as $key => $value) {
				$data_array[] = ['id' => $key,   'name' => $value];
			}

		return $data_array;
	}

	/**
	 * get non hierarhical taxonomy options
	 */
	public static function getNonHierarhicalTaxOptions($tax_name)
	{


		$all_terms = get_terms([
			'taxonomy' => $tax_name,
			'hide_empty' => 0
		]);
		$data_array = [];
		foreach ($all_terms as $single_term) {
			$data_array[] = ['id' => $single_term->term_id,   'name' => $single_term->name,   'slug' => $single_term->slug];
		}

		return $data_array;
	}
	/**
	 * get non hierarhical taxonomy content html
	 */
	public static function getCellTagContent($post_id, $taxonomy)
	{
		ob_start();

		echo '<div class="tag_editor" multiple="multiple">';
		$out_data = self::generateSelect2Input([
			'taxonomy' => $taxonomy,
			'post_id'  => $post_id,
			'name'     => 'tags_' . $post_id . '[]',
			'id' => 'tagpicker_' . $post_id,
			'class'    => 'js-example-basic-multiple',
			'echo' => false
		]);

		echo wp_kses($out_data,  array(
			'select' => array(
				'name'  => true,
				'id'    => true,
				'class' => true,
			),
			'option' => array(
				'value'    => true,
				'class'    => true,
				'selected' => true,
			),
		));


		echo '</div>';
		$categories_html = ob_get_clean();


		return $categories_html;
	}

	/**
	 * generate input for slect2
	 */
	public static function generateSelect2Input($args = [])
	{

		$defaults = [
			'taxonomy'        => 'category',
			'post_id'         => 0,
			'name'            => 'terms[]',
			'id'              => '',
			'class'           => '',
			'selected'        => [],
			'show_option_all' => '',
			'hide_empty'      => false,
			'orderby'         => 'name',
			'order'           => 'ASC',
			'echo'            => false,
		];

		$args = wp_parse_args($args, $defaults);

		// Get selected terms from post if post_id provided
		if ($args['post_id']) {
			$args['selected'] = wp_get_post_terms(
				$args['post_id'],
				$args['taxonomy'],
				['fields' => 'ids']
			);
		}

		$terms = get_terms([
			'taxonomy'   => $args['taxonomy'],
			'hide_empty' => $args['hide_empty'],
			'orderby'    => $args['orderby'],
			'order'      => $args['order'],
		]);

		if (is_wp_error($terms)) {
			return '';
		}

		$id = $args['id'] ?: $args['name'];

		ob_start();
?>
		<select
			name="<?php echo esc_attr($args['name']); ?>"
			id="<?php echo esc_attr($id); ?>"
			class="<?php echo esc_attr($args['class']); ?> is_debug"
			multiple>
			<?php if ($args['show_option_all']) : ?>
				<option value=""><?php echo esc_html($args['show_option_all']); ?></option>
			<?php endif; ?>

			<?php foreach ($terms as $term) : ?>
				<option data-slug="<?php echo esc_attr($term->slug); ?>" value="<?php echo esc_attr($term->term_id); ?>" <?php selected(in_array($term->term_id, (array) $args['selected'], true)); ?>><?php echo esc_html($term->name); ?></option>
			<?php endforeach; ?>
		</select>
<?php

		$html = ob_get_clean();

		if ($args['echo']) {

			echo wp_kses($html, array(
				'select' => array(
					'name'  => true,
					'id'    => true,
					'class' => true,
				),
				'option' => array(
					'value'    => true,
					'class'    => true,
					'selected' => true,
				),
			));
		}
		
		return $html;
	}


	/**
	 * echo update post category
	 */
	public static function addPostTaxonomy($post_id, $category_parent, $category_name, $taxonomy, $row, $col)
	{
		$extra_options = [];
		if (is_taxonomy_hierarchical($taxonomy)) {
			$extra_options = array(
				'parent'  => $category_parent,
			);
		}
		$new_term = wp_insert_term(
			$category_name,   // the term 
			$taxonomy, // the taxonomy
			$extra_options
		);
		if (!is_wp_error($new_term)) {
			wp_set_post_terms($post_id, [$new_term['term_id']], $taxonomy, true);
		}


		if (is_taxonomy_hierarchical($taxonomy)) {
			return ['content' => self::getCellCategoryContent($post_id, $taxonomy), 'row' => $row, 'col' => $col];
		} else {
			return ['content' => self::getCellTagContent($post_id, $taxonomy), 'row' => $row, 'col' => $col];
		}
	}


	/**
	 * get ACF post type fields
	 */
	public static function get_acf_fields_for_post_type($post_type)
	{

		if (! function_exists('acf_get_field_groups')) {
			return [];
		}

		$fields = [];

		// 1. Get all field groups assigned to this post type
		$field_groups = acf_get_field_groups([
			'post_type' => $post_type
		]);




		// 2. Loop field groups
		foreach ($field_groups as $group) {

			// 3. Get fields inside the group
			$group_fields = acf_get_fields($group['key']);

			if (empty($group_fields)) {
				continue;
			}
		 
		 
			foreach ($group_fields as $field) {
				$fields[] = $field;
			}
		}

		return $fields;
	}


	/**
	 * verify if slug exists
	 */
	public static function check_slug_exists($post_name)
	{
		global $wpdb;
	
		$results = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE post_name = %s", $post_name), 'ARRAY_A');

		if ($results) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Build a slug from a post's title (WordPress-correct: sanitize_title +
	 * wp_unique_post_slug for uniqueness/unicode), save it as the post_name, and
	 * return it. Used by the Automate Workflow so the slug follows the title
	 * without opening any dialog.
	 *
	 * @param int $post_id
	 * @return array { ok: bool, slug?: string, skipped?: bool, message?: string }
	 */
	public static function generateSlugFromTitle($post_id)
	{
		$post_id = absint($post_id);
		$post    = $post_id ? get_post($post_id) : null;
		if (!$post) {
			return array('ok' => false, 'message' => __('Post not found.', 'sheetspilot'));
		}
		if (!current_user_can('edit_post', $post_id)) {
			return array('ok' => false, 'message' => __('You are not allowed to edit this post.', 'sheetspilot'));
		}

		$base = sanitize_title((string) $post->post_title);
		if ($base === '') {
			// No usable title yet — skip quietly (not an error during bulk automation).
			return array('ok' => true, 'slug' => '', 'skipped' => true);
		}

		$slug = wp_unique_post_slug($base, $post_id, $post->post_status, $post->post_type, $post->post_parent);

		// Persist only the slug; wp_update_post merges with the existing post.
		wp_update_post(array('ID' => $post_id, 'post_name' => $slug));

		return array('ok' => true, 'slug' => $slug);
	}


	/**
	 * get posts list
	 */
	public static function getPostsList($inner_attrs)
	{

		$field_name = $inner_attrs['field_name'];
		$search_string = $inner_attrs['q'];
		$post_id = (int)$inner_attrs['post_id'];

		$current_field_data = acf_get_field(substr($field_name, 4));



		$filter_taxonomy_data = [];
		if ($current_field_data['taxonomy'] != '') {
			foreach ($current_field_data['taxonomy'] as $s_tax) {
				$s_tax_data = explode(':', $s_tax);
				$filter_taxonomy_data[$s_tax_data[0]][] = $s_tax_data[1];
			}
		}
		$posts_args = [
			'post_type' => ($current_field_data['post_type'] == '' ? 'any' : $current_field_data['post_type']),
			'post_status' => ($current_field_data['post_status'] == '' ? 'any' : $current_field_data['post_status']),
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
		$posts_args['s'] = $search_string;
		$posts_args['posts_per_page'] = -1;
		$posts_args['orderby'] = 'name';
		$posts_args['order'] = 'ASC';

		// if set search post type set it 
		if (isset($inner_attrs['search_post_type']) && $inner_attrs['search_post_type'] != '') {
			$posts_args['post_type'] = $inner_attrs['search_post_type'];
		}


		$query = new WP_Query($posts_args);

		$items = [];
		$items[] = [
			'id'   => '',
			'text' => SheetsPilotGlobals::$selectPost,
		];
		foreach ($query->posts as $post) {
			$items[] = [
				'id'   => $post->ID,
				'text' => $post->post_title,
			];
		}
		wp_send_json([
			'items' => $items,
			'more'  => false,
		]);
	}

	/**
	 * check if array is array of arrays
	 */
	public static function is_array_of_arrays(array $arr): bool
	{
		foreach ($arr as $value) {
			if (is_array($value)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * get image ID from URL
	 */
	public static function  get_image_id_by_url($url)
	{
		global $wpdb;

		$url = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $url);

		$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid=%s", $url));

		if (! empty($attachment)) {
			return $attachment[0];
		}

		return false;
	}

	/**
	 * get structurized repeater data
	 */
	public static function acf_repeater_to_json($field_name, $post_id = null)
	{
		if (! function_exists('have_rows')) {
			return wp_json_encode([]);
		}

		if (! $post_id) {
			$post_id = get_the_ID();
		}

		$result = [];

		if (have_rows($field_name, $post_id)) {
			while (have_rows($field_name, $post_id)) {
				the_row();

				$row = [];

				// получаем все сабполя текущей строки
				$sub_fields = get_row(true);

				foreach ($sub_fields as $sub_key => $sub_value) {
					$row[$sub_key] = $sub_value;
				}

				$result[] = $row;
			}
		}

		return wp_json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
	/**
	 * get structurized repeater data
	 */
	public static function acf_repeater_get_items($field_name, $post_id = null)
	{
		if (! function_exists('have_rows')) {
			return wp_json_encode([]);
		}

		$items_counter = 0;

		if (! $post_id) {
			$post_id = get_the_ID();
		}

		$result = [];

		if (have_rows($field_name, $post_id)) {
			while (have_rows($field_name, $post_id)) {
				the_row();

				$row = [];

				// получаем все сабполя текущей строки
				$sub_fields = get_row(true);

				foreach ($sub_fields as $sub_key => $sub_value) {
					$row[$sub_key] = $sub_value;
				}

				$result[] = $row;
				$items_counter++;
			}
		}
		return $items_counter;
		return '<span class="d-flex"><svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h.01"></path><path d="M3 18h.01"></path><path d="M3 6h.01"></path><path d="M8 12h13"></path><path d="M8 18h13"></path><path d="M8 6h13"></path></svg></span>' . $items_counter . ' items ';
	}

	/**
	 * get content for wyswyg
	 */
	public static function getWyswygContainer($data)
	{
		$current_inner_value = get_field(substr($data['field_name'], 4), $data['post_id']);
		$editor_id = 'dynamic_editor_' . time();
		$content   = $current_inner_value;

		ob_start();
		wp_editor(
			$current_inner_value,
			$editor_id,
			[
				'textarea_name' => 'dynamic_content',
				'media_buttons' => true,
				'teeny'         => false,
				'quicktags'     => true,
			]
		);
		$editor_html = ob_get_clean();
		$editor_html = $editor_html . '
			<div class="save_block_line textright edit-tag-actions">
				<input type="button"    class="button wyswyg_content_save" value="' . SheetsPilotGlobals::$wyswygSave . '">
			</div>
		';
		return ['html' => $editor_html, 'id' => $editor_id];
	}


	/**
	 * Drop repeater contnet
	 */
	public static function dropRepeaterContent($data)
	{
		$post_id = $data['post_id'];
		$repeater_name = substr($data['repeater_name'], 4);

		delete_field($repeater_name, $post_id);

		return ['result' => 'success'];
	}
	/**
	 * save content for wyswyg
	 */
	public static function saveWyswygContainer($data)
	{
		$post_id = $data['post_id'];
		$content = $data['content'];
		update_field(substr($data['field_name'], 4),  htmlspecialchars_decode($content, ENT_QUOTES), $post_id);
		return ['result' => 'success', 'text' => wp_strip_all_tags($content)];
	}

	/**
	 * get table empty row
	 */
	public static function getEmptyTableRow($data)
	{
		$post_type = $data['post_type'];
		$rows_number = (int)$data['rows_number'];
		$empty_row_content = [];
		for ($i = 0; $i < $rows_number; $i++) {
			$postQueryObj = new SheetsPilotQueryProcessing();
			$postQueryObj->postType = $post_type;
			$postQueryObj->is_new_row = true;
			$empty_row_content[] = $postQueryObj->getPostTypeArray();
		}

		return ['result' => 'success', 'rowdata' => $empty_row_content];
	}
	/**
	 * get table empty row
	 */
	public static function generatePostsByTitles($data)
	{
		$post_type = $data['post_type'];
		$rows_number = count($data['titles_list']);
		$empty_row_content = [];
		for ($i = 0; $i < $rows_number; $i++) {
			$postQueryObj = new SheetsPilotQueryProcessing();
			$postQueryObj->postType = $post_type;
			$postQueryObj->is_new_row = true;
			$postQueryObj->new_post_title = $data['titles_list'][$i];
			$empty_row_content[] = $postQueryObj->getPostTypeArray();
		}

		return ['result' => 'success', 'rowdata' => $empty_row_content];
	}

	/**
	 * get table duplicated row
	 */
	public static function getDuplicatedTableRow($data)
	{
		$post_type = $data['post_type'];
		$duplicate_number = (int)$data['duplicate_number'];

		$duplicated_row_content = [];

		for ($i = 0; $i < $duplicate_number; $i++) {
			$postQueryObj = new SheetsPilotQueryProcessing();
			$postQueryObj->postType = $post_type;
			$postQueryObj->is_new_row = true;
			$postQueryObj->duplicate_post_id = $data['post_id'];

			$duplicated_row_content[] = $postQueryObj->getPostTypeArray();
		}

		return ['result' => 'success', 'rowdata' => $duplicated_row_content];
	}

	/**
	 * get restored post
	 */
	public static function getRestoredTableRow($data)
	{
		$post_id = $data['post_id'];
		$post_type = $data['post_type'];

		$postQueryObj = new SheetsPilotQueryProcessing();
		$postQueryObj->postType = $data['post_type'];
		$postQueryObj->single_post_id = $data['post_id'];

		$duplicated_row_content =  $postQueryObj->getPostTypeArray();
		return ['result' => 'success', 'rowdata' => $duplicated_row_content];
	}

	/**
	 * restore posts
	 */
	public static function restoreTablePosts($data)
	{
		$post_id = $data['post_id'];
		$post_type = $data['post_type'];

		if (is_array($post_id)) {
			foreach ($post_id as $s_id) {
				wp_untrash_post($s_id);
			}
		} else {
			wp_untrash_post($post_id);
		}

		return ['result' => 'success'];
	}

	/**
	 * get tableedited row
	 */
	public static function getTableEditedRow($data)
	{
		$postQueryObj = new SheetsPilotQueryProcessing();
		$postQueryObj->postType = $data['post_type'];
		$postQueryObj->single_post_id = $data['post_id'];

		$duplicated_row_content = $postQueryObj->getPostTypeArray();
		return ['result' => 'success', 'rowdata' => $duplicated_row_content];
	}

	/**
	 * delete table post
	 */
	public static function removeTablePost($data)
	{
		if (isset($data['action_type'])) {
			if ($data['action_type'] == 'bulk') {
				foreach ($data['ids'] as $s_id) {
					wp_delete_post($s_id, false);
				}
			}
		} else {
			wp_delete_post($data['post_id'], false);
		}
	}

	/**
	 * duplicate posts
	 */
	public static function duplicatePostProcessing($post_id)
	{
		$post = get_post($post_id);
		if (! $post) {
			return false;
		}

		$new_post_id = wp_insert_post([
			'post_type'    => $post->post_type,
			'post_status'  => 'publish',
			'post_title'   => $post->post_title . ' (Copy)',
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
			'post_author'  => get_current_user_id(),
			'post_parent'  => $post->post_parent,
			'menu_order'   => $post->menu_order,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
		]);
		wp_update_post([
			'ID' => $new_post_id,
			'post_status'  => $post->post_status,
		]);

		if (is_wp_error($new_post_id)) {
			return false;
		}

		$meta = get_post_meta($post_id);
		foreach ($meta as $meta_key => $values) {

			if (in_array($meta_key, ['_wp_old_slug'], true)) {
				continue;
			}

			foreach ($values as $value) {
				add_post_meta(
					$new_post_id,
					$meta_key,
					maybe_unserialize($value)
				);
			}
		}

		$taxonomies = get_object_taxonomies($post->post_type);
		foreach ($taxonomies as $taxonomy) {
			$terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
			if (! empty($terms) && ! is_wp_error($terms)) {
				wp_set_object_terms($new_post_id, $terms, $taxonomy);
			}
		}

		return $new_post_id;
	}



	/**
	 * duplicate wyswyg ACF content
	 */
	public static function duplicateACFFieldContent($data)
	{
		$source_id = $data['source_id'];
		$target_id = $data['target_id'];
		$field_name = substr($data['field_name'], 4);

		$value = get_field($field_name, $source_id);
		update_field($field_name, $value, $target_id);

		return true;
	}


	/**
	 * get post multidata
	 */
	public static function getPostMultidata($data)
	{
		$post_id = $data['post_id'];
		$get_post_data = $data['get_post_data'];

		$allowed_feilds = ['post_title', '_wp_attachment_image_alt', 'post_excerpt', 'post_content', 'guid', '_stock', '_low_stock_amount', '_download_limit', '_download_expiry', '_downloadable_files', '_product_url', '_button_text', '_children', '_upsell_ids', '_attributes', '_repeater_data', '_wysiwyg'];

		$output_data = [];

		foreach ($get_post_data as $s_post_info) {

			if (!in_array($s_post_info['name'], $allowed_feilds)) {
				continue;
			}

			if ($s_post_info['data_table'] == 'posts') {
				if ( $s_post_info['name'] === 'post_content' && class_exists( 'SheetsPilotHelper' ) ) {
					$output_data[ $s_post_info['name'] ] = SheetsPilotHelperElementor::getPostContentDisplayForEditor( $post_id );
				} else {
					$post_info = get_post( $post_id );
					$output_data[ $s_post_info['name'] ] = $post_info->{$s_post_info['name']};
				}
			}
			if ($s_post_info['data_table'] == 'postmeta') {

				if ($s_post_info['name']  == '_repeater_data') {

					$repeater_processing = new SheetsPilotACFRepeaterProcessing();
					$output_data[$s_post_info['name']] = [
						'structure' => $repeater_processing->get_acf_repeater_structure(substr($data['filed_name'], 4), $post_id),
						'values' => $repeater_processing->get_acf_repeater_values(substr($data['filed_name'], 4), $post_id)
					];
				} elseif ($s_post_info['name']  == '_downloadable_files') {
					$downloadable_fiels = get_post_meta($post_id, $s_post_info['name'], true);
					$new_data_files = [];

					foreach ($downloadable_fiels as $s_file) {
						$new_data_files[] = ['name' => $s_file['name'], 'file' => $s_file['file']];
					}
					$output_data[$s_post_info['name']] = $new_data_files;
				} elseif ($s_post_info['name']  == '_children' || $s_post_info['name']  == '_upsell_ids') {
					$products_ids = get_post_meta($post_id, $s_post_info['name'], true);

					$new_data_files = [];
					foreach ($products_ids as $s_product_id) {
						$product = wc_get_product($s_product_id);
						$image_id = $product->get_image_id();
						$image_url = wp_get_attachment_image_url($image_id, 'full');
						if (!$image_url) {
							$image_url = wc_placeholder_img_src();
						}
						$new_data_files[] = ['id' => $s_product_id, 'post_title' => $product->get_title(), 'price' => $product->get_price(), 'image' => $image_url];
					}

					$output_data[$s_post_info['name']] = $new_data_files;
				} elseif ($s_post_info['name']  == '_attributes') {

					$product = wc_get_product($post_id);
					$attributes_data = [];

					$visible = false;
					$variation = false;

					if (!$product) {
						return $attributes_data;
					}
					$attributes = $product->get_attributes();
					uasort($attributes, function ($a, $b) {
						return $a->get_position() <=> $b->get_position();
					});


					foreach ($attributes as $attribute) {

						if ($attribute->is_taxonomy()) {
							$taxonomy = $attribute->get_name();
							$label = wc_attribute_label($taxonomy);

							$terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'names']);
							//if( $attribute->get_variation() ){
							$attributes_data[$taxonomy] = ['name' => $label, 'value' => $terms, 'visible_on_product' => $attribute->get_visible(), 'used_for_variations' => $attribute->get_variation(), 'position' => $attribute->get_position()];
							//}
						} else {
							$name = $attribute->get_name();

							$options = $attribute->get_options();
							//if( $attribute->get_variation() ){
							$attributes_data[sanitize_title($name)] =  ['name' => $name, 'value' => $options, 'visible_on_product' => $attribute->get_visible(), 'used_for_variations' => $attribute->get_variation(), 'position' => $attribute->get_position()];
							//}

						}
					}

					// order by position
					$position = array_column($attributes_data, 'position');
					array_multisort($position, SORT_ASC, $attributes_data);

					// variantions visibility asnd uswed for variations
					if ($attribute) {
						$visible = $attribute->get_visible(); // true / false
						$variation = $attribute->get_variation(); // true / false
					}


					// get all attributes
					$global_attributes = wc_get_attribute_taxonomies();

					$result = [];

					foreach ($global_attributes as $attr) {
						$result[] = [
							'id'    => $attr->attribute_id,
							'slug'  => 'pa_' . $attr->attribute_name,
							'label' => $attr->attribute_label,
							'type'  => $attr->attribute_type,
						];
					}

					// get all existed variations

					$output_data[$s_post_info['name']] = [
						'attributes_data' => $attributes_data,
						'is_visible' => $visible,
						'used_for_variations' => $variation,
						'global_attributes' => $result,
						'product_id' => $post_id,
						'variations_info' => SheetsPilotCellEditor::getProductVariationsFull($post_id),
						'no_price_attributes' => count(SheetsPilotCellEditor::get_variations_without_price($post_id)),
					];
				} elseif ($s_post_info['name']  == '_wysiwyg') {
					if (substr($s_post_info['field_name'], 0, 4) == 'acf_') {
						$output_data[$s_post_info['name']] = get_post_meta($post_id, substr($s_post_info['field_name'], 4), true);
					}
					if (substr($s_post_info['field_name'], 0, 8) == 'plugins_') {
						$output_data[$s_post_info['name']] = get_post_meta($post_id, substr($s_post_info['field_name'], 8), true);
					}
					
				} else {
					$output_data[$s_post_info['name']] = get_post_meta($post_id, $s_post_info['name'], true);
				}
			}
		}

		return ['result' => 'success', 'rowdata' =>  $output_data];
	}
	/**
	 * save post multidata
	 */
	public static function savePostMultidata($data)
	{
		$post_id = $data['post_id'];
		$post_data = $data['post_data'];

		$allowed_feilds = ['post_title', '_wp_attachment_image_alt', 'post_excerpt', 'post_content', 'guid', '_stock', '_low_stock_amount', '_download_limit', '_download_expiry', '_downloadable_files', '_product_url', '_button_text', '_children', '_upsell_ids', '_attributes', '_variations', '_repeater_data', '_wysiwyg'];



		$output_data = [];

		foreach ($post_data as $s_post_info) {


			if (!in_array($s_post_info['name'], $allowed_feilds)) {
				continue;
			}

			if ($s_post_info['data_table'] == 'posts') {
				if ( $s_post_info['name'] === 'post_content' ) {
					self::savePostContentValue(
						(int) $post_id,
						$s_post_info['value'],
						array(
							'is_elementor'   => ! empty( $data['is_elementor'] ),
							'elementor_data' => SheetsPilotFunctions::getVal( $s_post_info, 'elementor_data', SheetsPilotFunctions::getVal( $data, 'elementor_data', '' ) ),
							'display_value'  => SheetsPilotFunctions::getVal( $s_post_info, 'display_value', '' ),
						)
					);
					continue;
				}

				$args = [
					'ID' => $post_id,
				];
				$args[$s_post_info['name']] = $s_post_info['value'];
				wp_update_post($args);
			}
			if ($s_post_info['data_table'] == 'postmeta') {

				// custom
				if ($s_post_info['name'] == '_downloadable_files') {


					$new_file_data = [];
					foreach ($s_post_info['value'] as $s_file) {
						$new_file_data[md5($s_file['name'])] = [
							'name' => $s_file['name'],
							'file' => $s_file['url'],
						];
					}
					update_post_meta($post_id, $s_post_info['name'], $new_file_data);
				} else if ($s_post_info['name'] == '_attributes') {

					$product = wc_get_product($post_id);
					if (!$product) return false;

					$attributes = $product->get_attributes();

					$new_keys = [];

					foreach ($s_post_info['value'] as $attr) {

						$slug_raw = $attr['attr_slug'];
						$label    = $attr['attr_title'];

						// нормализуем slug
						$slug = wc_sanitize_taxonomy_name($slug_raw);

						// если уже pa_ — не дублируем
						if (strpos($slug, 'pa_') === 0) {
							$taxonomy = $slug;
							$slug_clean = str_replace('pa_', '', $slug);
						} else {
							$taxonomy = wc_attribute_taxonomy_name($slug); // pa_color
							$slug_clean = $label; //$slug;
						}

						$is_taxonomy = taxonomy_exists($taxonomy);

						// значения
						$values = is_array($attr['attr_values'])
							? $attr['attr_values']
							: array_map('trim', explode('|', $attr['attr_values']));

						$attribute = new WC_Product_Attribute();

						if ($is_taxonomy) {
							$term_ids = [];

							foreach ($values as $value) {

								if (!term_exists($value, $taxonomy)) {
									wp_insert_term($value, $taxonomy);
								}

								$term = get_term_by('name', $value, $taxonomy);

								if ($term) {
									$term_ids[] = (int)$term->term_id;
								}
							}

							$attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
							$attribute->set_name($taxonomy);
							$attribute->set_options($term_ids);

							$key = $taxonomy;
						} else {

							// --- CUSTOM ATTRIBUTE ---

							$attribute->set_name($slug_clean);
							$attribute->set_options($values);

							$key = $slug_clean;
						}

						$attribute->set_visible($attr['is_visible']);
						$attribute->set_variation($attr['use_for_variations']);
						$attribute->set_position($attr['position']);

						$new_keys[] = (string) $key;

						// UPSERT
						$attributes[$key] = $attribute;
					}


					// remove unneded attributes
					foreach ($attributes as $key => $attr) {
						if (!in_array((string)$key, $new_keys, true)) {
							unset($attributes[$key]);
						}
					}
					$product->set_attributes($attributes);
					$product->save();

					###########	

					update_post_meta($post_id, $s_post_info['name'], $s_post_info['value']);
				} else if ($s_post_info['name'] == '_variations') {

					foreach ($s_post_info['value'] as $single_variation) {

						$variation_id = $single_variation['id'];
						update_post_meta($variation_id, '_thumbnail_id', $single_variation['featured_image']);

						// attributes
						foreach ($single_variation['attributes'] as $key => $value) {
							// WooCommerce требует префикс attribute_
							$meta_key = 'attribute_' . sanitize_title($value['name']);

							update_post_meta($variation_id, $meta_key, sanitize_title($value['value']));
						}

						update_post_meta($variation_id, '_sku', $single_variation['_sku']);
						update_post_meta($variation_id, '_global_unique_id', $single_variation['_global_unique_id']);
						// enabled
						if ($single_variation['enabled'] == 'yes') {
							wp_update_post([
								'ID' => $variation_id,
								'post_status' => 'publish'
							]);
						} else {
							wp_update_post([
								'ID' => $variation_id,
								'post_status' => 'private'
							]);
						}


						update_post_meta($variation_id, '_downloadable', $single_variation['downloadable']);
						update_post_meta($variation_id, '_virtual', $single_variation['virtual']);
						update_post_meta($variation_id, '_manage_stock', $single_variation['manage_stock']);
						update_post_meta($variation_id, '_regular_price', $single_variation['_regular_price']);
						update_post_meta($variation_id, '_sale_price', $single_variation['_sale_price']);
						update_post_meta($variation_id, '_stock', $single_variation['_stock']);
						update_post_meta($variation_id, '_backorders', $single_variation['allow_backorders']);
						update_post_meta($variation_id, '_stock_status', $single_variation['stock_status']);
						update_post_meta($variation_id, '_low_stock_amount', $single_variation['_low_stock_amount']);
						update_post_meta($variation_id, '_weight', $single_variation['_weight']);
						update_post_meta($variation_id, '_length', $single_variation['_length']);
						update_post_meta($variation_id, '_width', $single_variation['_width']);
						update_post_meta($variation_id, '_height', $single_variation['_height']);
						update_post_meta($variation_id, 'shipping_class', $single_variation['shipping_class']);
						update_post_meta($variation_id, '_tax_class', $single_variation['tax_class']);
						// description
						update_post_meta($variation_id, '_variation_description', $single_variation['description']);

						// download files
						$new_file_data = [];
						foreach ($single_variation['download_files'] as $s_file) {
							$new_file_data[md5($s_file['name'])] = [
								'name' => $s_file['name'],
								'file' => $s_file['url'],
							];
						}
						update_post_meta($variation_id, '_downloadable_files', $new_file_data);

						update_post_meta($variation_id, '_download_limit', $single_variation['download_limit']);
						update_post_meta($variation_id, '_download_expiry', $single_variation['download_expiry']);
					}
				} else if ($s_post_info['name'] == '_repeater_data') {


					$repeater_structure = [];
					foreach ($s_post_info['value'] as $single_repeater_data) {

						$block_number = (int)$single_repeater_data['block'];

						// is top level block
						if (!isset($single_repeater_data['parent']) || (int)$single_repeater_data['parent'] === $block_number) {

							if (!$single_repeater_data['repeater_name']) {
								$repeater_structure['block_' . $block_number][$single_repeater_data['field_name']] = $single_repeater_data['value'];
							} else {
								/*
								$repeater_structure['block_'.$block_number][$single_repeater_data['repeater_name']][$single_repeater_data['block'].$single_repeater_data['parent']] = [
									$single_repeater_data['field_name'] => $single_repeater_data['value'] 
								];
								*/
							}
						}
						if ((isset($single_repeater_data['block']) && isset($single_repeater_data['parent'])) && $single_repeater_data['block'] != $single_repeater_data['parent']) {
							// not parent element but repeater
							$repeater_structure['block_' . $single_repeater_data['parent']][$single_repeater_data['repeater_name']]['_' . $single_repeater_data['parent'] . $single_repeater_data['block']][$single_repeater_data['field_name']] = $single_repeater_data['value'];
						}
					}
					$repeater_structure = SheetsPilotCellEditor::normalizeArrayKeys($repeater_structure);
					update_field(substr($s_post_info['repeater_name'], 4), $repeater_structure, $post_id);
				} else {

					if (substr($s_post_info['field_name'], 0, 4) == 'acf_') {
						update_post_meta($post_id,  substr($s_post_info['field_name'], 4), $s_post_info['value']);
					}
					if (substr($s_post_info['field_name'], 0, 8) == 'plugins_') {
						update_post_meta($post_id,  substr($s_post_info['field_name'], 8), $s_post_info['value']);
					}
 
				}
			}
		}

		return ['result' => 'success'];
	}

	//get all product attributes
	public static function getProductVariationsFull($product_id)
	{
		wc_delete_product_transients($product_id);

		$product = wc_get_product($product_id);

		if (!$product || !$product->is_type('variable')) {
			return [];
		}

		$variations_data = [];

		$children = $product->get_children();
		sort($children);

		foreach ($children as $variation_id) {

			$variation = wc_get_product($variation_id);

			if (!$variation) continue;

			// 🔹 Атрибуты вариации
			$attributes = $variation->get_attributes();

			// 🔹 Все meta поля
			$meta = get_post_meta($variation_id);

			$meta['_downloadable_files'] = get_post_meta($variation_id, '_downloadable_files', true);

			// 🔹 Базовые данные (по желанию)
			$data = [
				'parent_id' => $product_id,
				'variation_id' => $variation_id,
				'sku'          => $variation->get_sku(),
				'price'        => $variation->get_price(),
				'stock'        => $variation->get_stock_quantity(),
				'enabled'      => get_post($variation_id)->post_status,
				'featured_image'      => wp_get_attachment_image_url($variation->get_image_id(), 'full'),
				'featured_image_id'      => $variation->get_image_id(),

			];

			$variations_data[] = [
				'data'       => $data,
				'attributes' => $attributes,
				'meta'       => $meta,
			];
		}

		return $variations_data;
	}

	// get all variations without price
	public static function get_variations_without_price($product_id)
	{

		$product = wc_get_product($product_id);

		if (!$product || !$product->is_type('variable')) {
			return [];
		}

		$result = [];

		$children = $product->get_children();
		sort($children);

		foreach ($children as $variation_id) {

			$variation = wc_get_product($variation_id);

			if (!$variation) continue;

			$price = $variation->get_price();

			// Проверка: нет цены
			if ($price === '' || $price === null) {
				$result[] = $variation_id;
			}
		}

		return $result;
	}

	// add single product variation
	public static function  addSingleVariation($data)
	{



		$product_id = $data['post_id'];

		$product = wc_get_product($product_id);

		if (!$product || !$product->is_type('variable')) {
			return false;
		}


		$variation_post = [
			'post_title'  => $product->get_name(),
			'post_name'   => 'product-' . $product_id . '-variation',
			'post_status' => 'publish',
			'post_parent' => $product_id,
			'post_type'   => 'product_variation',
			'menu_order'  => -1
		];

		$variation_id = wp_insert_post($variation_post);


		if (!$variation_id) {
			return false;
		}

		clean_post_cache($product_id);
		wc_delete_product_transients($product_id);
		return $variation_id;
	}

	public static function generateAllProductVariations($data)
	{
		$product_id = $data['post_id'];
		$product = wc_get_product($product_id);

		$data_store = $product->get_data_store();
		$data_store->create_all_product_variations($product);

		return false;


		if (!$product || !$product->is_type('variable')) {
			return;
		}



		// Получаем атрибуты продукта
		$attributes = $product->get_attributes();

		$variation_attributes = [];

		foreach ($attributes as $attribute) {

			if (!$attribute->get_variation()) continue;

			if ($attribute->is_taxonomy()) {
				$terms = wp_get_post_terms($product_id, $attribute->get_name(), ['fields' => 'slugs']);
				$variation_attributes[$attribute->get_name()] = $terms;
			} else {
				$variation_attributes[$attribute->get_name()] = $attribute->get_options();
			}
		}

		// Генерируем все комбинации
		$combinations = SheetsPilotCellEditor::array_cartesian($variation_attributes);


		foreach ($combinations as $combo) {

			$combo = SheetsPilotCellEditor::normalize_combo($combo, $product_id);

			// Проверяем, есть ли уже такая вариация
			if (SheetsPilotCellEditor::variation_exists($product_id, $combo)) {
				continue;
			}

			$variation = new WC_Product_Variation();
			$variation->set_parent_id($product_id);
			$variation->set_attributes($combo);




			// можно задать дефолтные значения
			$variation->set_regular_price('');
			$variation->set_price('');

			$variation->set_status('publish');

			$variation->save();
		}

		// Очистка кеша
		wc_delete_product_transients($product_id);
	}

	public static function array_cartesian($input)
	{

		$result = [[]];

		foreach ($input as $key => $values) {

			$append = [];

			foreach ($result as $product) {
				foreach ($values as $value) {
					$product[$key] = $value;
					$append[] = $product;
				}
			}

			$result = $append;
		}

		return $result;
	}

	public static function variation_exists($product_id, $attributes)
	{

		$args = [
			'post_type'   => 'product_variation',
			'post_parent' => $product_id,
			'numberposts' => -1,
			'fields'      => 'ids'
		];

		$variations = get_posts($args);

		foreach ($variations as $variation_id) {

			$match = true;

			foreach ($attributes as $key => $value) {
				$meta = get_post_meta($variation_id, 'attribute_' . $key, true);

				if ($meta != $value) {
					$match = false;
					break;
				}
			}

			if ($match) {
				return true;
			}
		}

		return false;
	}

	public static function normalize_combo($combo, $product_id)
	{

		$normalized = [];

		foreach ($combo as $name => $value) {

			// 👉 делаем slug атрибута
			$taxonomy = wc_attribute_taxonomy_name(sanitize_title($name));

			// 👉 получаем slug значения
			$term = get_term_by('name', $value, $taxonomy);

			if ($term) {
				$normalized[$taxonomy] = $term->slug;
			} else {
				// если кастомный атрибут
				$normalized[sanitize_title($name)] = sanitize_title($value);
			}
		}

		return $normalized;
	}

	public static function copyProductAttributes($from_product_id, $to_product_id)
	{
		$from_product = wc_get_product($from_product_id);
		$to_product   = wc_get_product($to_product_id);

		if (!$from_product || !$to_product) {
			return false;
		}

		$attributes = $from_product->get_attributes();

		$new_attributes = [];
		$attributes_counter = 0;
		foreach ($attributes as $attribute) {
			$new_attr = new WC_Product_Attribute();

			$new_attr->set_id($attribute->get_id());
			$new_attr->set_name($attribute->get_name());
			$new_attr->set_options($attribute->get_options());
			$new_attr->set_position($attribute->get_position());
			$new_attr->set_visible($attribute->get_visible());
			$new_attr->set_variation($attribute->get_variation());

			$new_attributes[] = $new_attr;
			$attributes_counter++;
		}

		$to_product->set_attributes($new_attributes);
		$to_product->save();

		return $attributes_counter;
	}


	public static function normalizeArrayKeys($array)
	{
		if (!is_array($array)) {
			return $array;
		}

		$result = [];

		foreach ($array as $key => $value) {
			$value = SheetsPilotCellEditor::normalizeArrayKeys($value);

			// block_0, block_1, _01, _02 и тд — всё это в индекс
			if (
				(is_string($key) && preg_match('/^(block_\d+|_\d+)$/', $key))
			) {
				$result[] = $value;
			} else {
				$result[$key] = $value;
			}
		}

		return $result;
	}


	/**
	 * create image from clipboard
	 */
	public static function createImageFromClipboard($data)
	{

		if (empty($data['image'])) {
			wp_send_json_error('No image data');
		}

		$base64 = $data['image'];


		if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
			wp_send_json_error('Invalid base64 format');
		}

		$base64 = substr($base64, strpos($base64, ',') + 1);
		$file_type = strtolower($type[1]); // png, jpeg, jpg...



		// нормализуем расширение
		if ($file_type === 'jpeg') {
			$file_type = 'jpg';
		}

		$decoded = base64_decode($base64);

		if ($decoded === false) {
			wp_send_json_error('Decode failed');
		}

		$upload_dir = wp_upload_dir();

		if (!wp_mkdir_p($upload_dir['path'])) {
			wp_send_json_error('Cannot create upload dir');
		}

		$filename = 'clipboard_' . time() . '.' . $file_type;
		$file_path = $upload_dir['path'] . '/' . $filename;

		file_put_contents($file_path, $decoded);

		$file_path = SheetsPilot_ImageProcessing::compressImageForMedia( $file_path );
		$filename  = basename( $file_path );

		$wp_filetype = wp_check_filetype($filename, null);


		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name($filename),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		$attach_id = wp_insert_attachment($attachment, $file_path);

		if (is_wp_error($attach_id)) {
			wp_send_json_error('Attachment error');
		}

		$attach_data = array(
			'file' => _wp_relative_upload_path($file_path),
		);
		$imagesize = wp_getimagesize($file_path);
		if ($imagesize) {
			$attach_data['width']  = (int) $imagesize[0];
			$attach_data['height'] = (int) $imagesize[1];
		}
		wp_update_attachment_metadata($attach_id, $attach_data);

		return ([
			'id' => $attach_id,
			'url' => wp_get_attachment_url($attach_id)
		]);
	}
}
