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
	/**
	 * JetEngine layout rows that are not stored meta fields.
	 *
	 * @var string[]
	 */
	private static $skipObjectTypes = array( 'html', 'tab', 'accordion', 'endpoint' );

	function __construct(){
		add_filter('sheetspilot_filter_table_columns', array( $this, 'filter_table_columns' ), 10, 2);
		add_filter('sheetspilot_filter_table_values', array( $this, 'filter_table_values' ), 10, 3);
		add_filter('sheetspilot_filter_table_fields', array( $this, 'filter_table_fields' ), 10, 3);
	}

	/**
	 * Register JetEngine meta fields as table columns.
	 *
	 * @param array  $columns
	 * @param string $postType
	 * @return array
	 */
	public function filter_table_columns( $columns, $postType ){
		foreach ( self::get_fields( $postType ) as $field_data ) {
			$columns[] = self::get_column_args( $field_data );
		}

		return $columns;
	}

	/**
	 * Add JetEngine cell values for one post.
	 *
	 * @param array  $values
	 * @param string $postType
	 * @param int    $postId
	 * @return array
	 */
	public function filter_table_values( $values, $postType, $postId ){
		foreach ( self::get_fields( $postType ) as $field_data ) {
			$values[][ 'plugins_' . $field_data['name'] ] = self::get_cell_value( $postId, $field_data );
		}

		return $values;
	}

	/**
	 * Allow JetEngine column keys through the row field whitelist.
	 *
	 * @param array  $values
	 * @param string $postType
	 * @param int    $postId
	 * @return array
	 */
	public function filter_table_fields( $values, $postType, $postId ){
		foreach ( self::get_fields( $postType ) as $field_data ) {
			$values[] = 'plugins_' . $field_data['name'];
		}

		return $values;
	}

	/**
	 * @param string $column
	 * @return bool
	 */
	public static function is_column( $column ){
		return is_string( $column ) && strpos( $column, 'plugins_' ) === 0;
	}

	/**
	 * Convert a SheetsPilot cell value to the format JetEngine stores, then save.
	 * Non-JetEngine `plugins_` keys pass through unchanged.
	 *
	 * @param int    $post_id
	 * @param string $column Column name including the plugins_ prefix.
	 * @param mixed  $value
	 * @return bool
	 */
	public static function save_column_meta( $post_id, $column, $value ){
		if ( ! self::is_column( $column ) ) {
			return false;
		}

		$meta_key  = substr( $column, 8 );
		$post_type = get_post_type( $post_id );
		$field     = $post_type ? self::get_field( $post_type, $meta_key ) : null;

		// Repeater rows are saved from the drawer, not from the table cell.
		if ( $field && 'repeater' === $field['type'] ) {
			return false;
		}

		$value = self::prepare_save_value( $post_id, $meta_key, $value );
		update_post_meta( $post_id, $meta_key, $value );

		return true;
	}

	/**
	 * Convert a table cell value into JetEngine post meta.
	 *
	 * @param int    $post_id
	 * @param string $meta_key
	 * @param mixed  $value
	 * @return mixed
	 */
	public static function prepare_save_value( $post_id, $meta_key, $value ){
		$post_type = get_post_type( $post_id );
		$field     = $post_type ? self::get_field( $post_type, $meta_key ) : null;

		if ( ! $field ) {
			return $value;
		}

		$type = $field['type'];

		if ( 'switcher' === $type ) {
			return self::is_switcher_on( $value ) ? 'true' : '';
		}

		if ( in_array( $type, array( 'radio', 'select' ), true ) ) {
			$multiple = self::is_multiple_select( $field );

			if ( is_array( $value ) ) {
				$value = array_values( $value );
				return $multiple ? $value : ( isset( $value[0] ) ? $value[0] : '' );
			}

			return $value;
		}

		if ( 'media' === $type ) {
			$ids = self::ids_from_save_value( $value );
			return self::format_media_for_save( isset( $ids[0] ) ? $ids[0] : 0, self::get_value_format( $field ) );
		}

		if ( 'gallery' === $type ) {
			return self::format_gallery_for_save( self::ids_from_save_value( $value ), self::get_value_format( $field ) );
		}

		return $value;
	}

	/**
	 * Fields registered for a post type, excluding layout-only rows.
	 *
	 * @param string $post_type
	 * @return array
	 */
	private static function get_fields( $post_type ){
		if ( ! function_exists( 'jet_engine' ) || ! jet_engine()->meta_boxes ) {
			return array();
		}

		$fields = jet_engine()->meta_boxes->get_fields_for_context( 'post_type', $post_type );
		if ( ! is_array( $fields ) ) {
			return array();
		}

		$out = array();
		foreach ( $fields as $field_data ) {
			if ( self::is_real_field( $field_data ) ) {
				$out[] = $field_data;
			}
		}

		return $out;
	}

	/**
	 * @param string $post_type
	 * @param string $field_name
	 * @return array|null
	 */
	private static function get_field( $post_type, $field_name ){
		foreach ( self::get_fields( $post_type ) as $field_data ) {
			if ( $field_data['name'] === $field_name ) {
				return $field_data;
			}
		}

		return null;
	}

	/**
	 * @param array $field_data
	 * @return bool
	 */
	private static function is_real_field( $field_data ){
		if ( empty( $field_data['name'] ) || empty( $field_data['type'] ) ) {
			return false;
		}

		$object_type = isset( $field_data['object_type'] ) ? $field_data['object_type'] : 'field';
		if ( in_array( $object_type, self::$skipObjectTypes, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param array $field_data
	 * @return array
	 */
	private static function get_column_args( $field_data ){
		$type         = $field_data['type'];
		$out_type     = self::map_column_type( $type );
		$source       = self::get_column_source( $field_data );
		$column_search = 'text';
		$readonly     = ! empty( $field_data['readonly'] );
		$bottom_manage = '';

		if ( in_array( $type, array( 'select', 'radio' ), true ) ) {
			$column_search = 'filter';
		}

		if ( 'switcher' === $type ) {
			$column_search = 'filter';
			$source        = array(
				array( 'id' => 'no',  'name' => __( 'Off', 'sheetspilot' ) ),
				array( 'id' => 'yes', 'name' => __( 'On', 'sheetspilot' ) ),
			);
		}

		if ( 'wysiwyg' === $type ) {
			$readonly      = true;
			$column_search = 'text';
			$bottom_manage =
				'<span class="edit_wysiwyg_field has-tooltip" data-title="' . esc_attr( SheetsPilotGlobals::$editWyswygText ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>
				';
		}

		if ( 'repeater' === $type ) {
			$readonly      = true;
			$column_search = 'text';
			$bottom_manage =
				'<span class="new_edit_repeater_field has-tooltip" data-title="' . esc_attr( SheetsPilotGlobals::$editRepeaterText ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>
				<span class="delete_repeater_data has-tooltip" data-title="' . esc_attr( SheetsPilotGlobals::$dropRepeaterText ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-3.5 h-3.5 text-muted-foreground"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg></span>
			';
		}

		if ( 'media' === $type ) {
			$readonly      = true;
			$column_search = 'text';
			$bottom_manage =
				SheetsPilotCellEditor::get_image_cell_download_icon_html() . '
				<span class="inline_edit_image_field has-tooltip" data-title="' . esc_attr( SheetsPilotGlobals::$inlineEditImageText ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>
				<span class="edit_image_field has-tooltip" data-title="' . esc_attr( SheetsPilotGlobals::$addImageText ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image w-3.5 h-3.5 text-muted-foreground"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path></svg></span>
				<span class="has-tooltip delete_image_field" data-title="' . esc_attr( SheetsPilotGlobals::$deleteImageText ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-3.5 h-3.5 text-muted-foreground"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg></span>
				';
		}

		if ( 'gallery' === $type ) {
			$readonly      = true;
			$column_search = 'text';
			$bottom_manage =
				'<span class="add_gallery_image has-tooltip" data-title="' . esc_attr( SheetsPilotGlobals::$addGalleryImageText ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg></span>
				<span class="delete_all_images has-tooltip" data-title="' . esc_attr( SheetsPilotGlobals::$dropGalleryText ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-3.5 h-3.5 text-muted-foreground"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg></span>';
		}

		$title = ! empty( $field_data['title'] ) ? $field_data['title'] : $field_data['name'];

		return array(
			'title'          => $title,
			'name'           => 'plugins_' . $field_data['name'],
			'width'          => 100,
			'type'           => $out_type,
			'dev_type'       => 'meta_field',
			'readonly'       => $readonly,
			'orderable'      => true,
			'source'         => $source,
			'switchable'     => true,
			'is_acf'         => false,
			'bottom_manage'  => $bottom_manage,
			'column_search'  => $column_search,
			'is_pro'         => ( in_array( $out_type, SheetsPilotGlobals::$proFilesList, true ) ? true : false ),
		);
	}

	/**
	 * Map a JetEngine field type to a SheetsPilot cell type.
	 *
	 * @param string $type
	 * @return string
	 */
	private static function map_column_type( $type ){
		if ( in_array( $type, array( 'select', 'radio' ), true ) ) {
			return 'acf_select';
		}
		if ( in_array( $type, array( 'text', 'textarea' ), true ) ) {
			return 'textarea';
		}
		if ( 'media' === $type ) {
			return 'image';
		}
		if ( 'gallery' === $type ) {
			return 'acf_gallery';
		}
		if ( 'switcher' === $type ) {
			return 'switch';
		}
		if ( 'wysiwyg' === $type ) {
			return 'wysiwyg';
		}
		if ( 'repeater' === $type ) {
			return 'repeater';
		}

		return $type;
	}

	/**
	 * @param array $field_data
	 * @return array
	 */
	private static function get_column_source( $field_data ){
		if ( ! in_array( $field_data['type'], array( 'select', 'radio' ), true ) ) {
			return array();
		}

		$source  = array();
		$options = isset( $field_data['options'] ) && is_array( $field_data['options'] ) ? $field_data['options'] : array();

		foreach ( $options as $s_option ) {
			if ( ! is_array( $s_option ) || ! isset( $s_option['key'] ) ) {
				continue;
			}

			$source[] = array(
				'id'   => $s_option['key'],
				'name' => isset( $s_option['value'] ) ? $s_option['value'] : $s_option['key'],
			);
		}

		return $source;
	}

	/**
	 * @param int   $post_id
	 * @param array $field_data
	 * @return mixed
	 */
	private static function get_cell_value( $post_id, $field_data ){
		$type     = $field_data['type'];
		$meta_key = $field_data['name'];
		$raw      = self::get_jet_meta( $post_id, $meta_key );

		if ( 'media' === $type ) {
			return self::format_media_cell_html( self::parse_attachment_id( $raw ) );
		}

		if ( 'gallery' === $type ) {
			return array(
				'values' => self::parse_gallery_items( $raw ),
			);
		}

		if ( in_array( $type, array( 'select', 'radio' ), true ) ) {
			return self::format_select_value( $raw, self::is_multiple_select( $field_data ) );
		}

		if ( 'switcher' === $type ) {
			return self::is_switcher_on( $raw ) ? 'yes' : 'no';
		}

		if ( 'wysiwyg' === $type ) {
			if ( $raw ) {
				return substr( wp_strip_all_tags( (string) $raw ), 0, 100 );
			}

			return '';
		}

		if ( 'repeater' === $type ) {
			return array(
				'values' => is_array( $raw ) ? count( $raw ) : 0,
			);
		}

		if ( is_array( $raw ) || is_object( $raw ) ) {
			return '';
		}

		if ( $raw === false || $raw === null ) {
			return '';
		}

		return (string) $raw;
	}

	/**
	 * Read a JetEngine meta value. Never uses ACF get_field().
	 *
	 * @param int    $post_id
	 * @param string $meta_key
	 * @return mixed
	 */
	private static function get_jet_meta( $post_id, $meta_key ){
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		if ( function_exists( 'jet_engine' ) ) {
			$jet = jet_engine();
			if ( $jet && ! empty( $jet->listings ) && ! empty( $jet->listings->data ) ) {
				return $jet->listings->data->get_meta( $meta_key, $post );
			}
		}

		return get_post_meta( $post_id, $meta_key, true );
	}

	/**
	 * Shape a radio/select value for the acf_select cell renderer.
	 *
	 * @param mixed $raw
	 * @param bool  $multiple
	 * @return array
	 */
	private static function format_select_value( $raw, $multiple ){
		if ( $multiple ) {
			$values = is_array( $raw ) ? array_values( $raw ) : ( ( $raw === '' || $raw === false || $raw === null ) ? array() : array( $raw ) );
		} else {
			if ( is_array( $raw ) ) {
				$raw = isset( $raw[0] ) ? $raw[0] : '';
			}
			$values = ( $raw === '' || $raw === false || $raw === null ) ? array() : array( $raw );
		}

		return array(
			'values'   => $values,
			'multiple' => $multiple ? 1 : 0,
		);
	}

	/**
	 * @param array $field_data
	 * @return bool
	 */
	private static function is_multiple_select( $field_data ){
		if ( 'select' !== $field_data['type'] ) {
			return false;
		}

		return ! empty( $field_data['is_multiple'] ) && filter_var( $field_data['is_multiple'], FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * JetEngine switcher stores true / 'true' / 1 when on.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	private static function is_switcher_on( $value ){
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_array( $value ) ) {
			return false;
		}

		$value = strtolower( trim( (string) $value ) );

		return in_array( $value, array( 'true', '1', 'yes', 'on' ), true );
	}

	/**
	 * @param array $field
	 * @return string id|url|both
	 */
	private static function get_value_format( $field ){
		$format = isset( $field['value_format'] ) ? $field['value_format'] : 'id';
		if ( ! in_array( $format, array( 'id', 'url', 'both' ), true ) ) {
			return 'id';
		}

		return $format;
	}

	/**
	 * @param mixed $raw
	 * @return mixed
	 */
	private static function maybe_decode_media_value( $raw ){
		if ( ! is_string( $raw ) ) {
			return $raw;
		}

		$trim = trim( $raw );
		if ( $trim === '' ) {
			return $raw;
		}

		$first = $trim[0];
		if ( '{' !== $first && '[' !== $first ) {
			return $raw;
		}

		$decoded = json_decode( wp_unslash( $trim ), true );
		return is_array( $decoded ) ? $decoded : $raw;
	}

	/**
	 * @param string $url
	 * @return int
	 */
	private static function attachment_id_from_url( $url ){
		if ( ! $url || ! is_string( $url ) ) {
			return 0;
		}

		if ( class_exists( 'SheetsPilotCellEditor' ) ) {
			$id = SheetsPilotCellEditor::get_image_id_by_url( $url );
			if ( $id ) {
				return (int) $id;
			}
		}

		return (int) attachment_url_to_postid( $url );
	}

	/**
	 * Resolve a JetEngine media value (id, url, or both) to an attachment ID.
	 *
	 * @param mixed $raw
	 * @return int
	 */
	private static function parse_attachment_id( $raw ){
		$raw = self::maybe_decode_media_value( $raw );

		if ( $raw === '' || $raw === false || $raw === null ) {
			return 0;
		}

		if ( is_array( $raw ) ) {
			if ( isset( $raw['id'] ) ) {
				return (int) $raw['id'];
			}
			if ( isset( $raw['ID'] ) ) {
				return (int) $raw['ID'];
			}
			if ( ! empty( $raw['url'] ) ) {
				return self::attachment_id_from_url( $raw['url'] );
			}

			return 0;
		}

		if ( is_numeric( $raw ) ) {
			return (int) $raw;
		}

		if ( is_string( $raw ) && preg_match( '#^https?://#i', $raw ) ) {
			return self::attachment_id_from_url( $raw );
		}

		return 0;
	}

	/**
	 * @param mixed $raw
	 * @return array<int, array{id:int,url:string}>
	 */
	private static function parse_gallery_items( $raw ){
		$raw   = self::maybe_decode_media_value( $raw );
		$items = array();

		if ( $raw === '' || $raw === false || $raw === null ) {
			return $items;
		}

		if ( is_string( $raw ) ) {
			$parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
			$raw   = array_values( $parts );
		}

		if ( ! is_array( $raw ) ) {
			$raw = array( $raw );
		}

		foreach ( $raw as $entry ) {
			$id  = 0;
			$url = '';

			if ( is_array( $entry ) ) {
				$id  = self::parse_attachment_id( $entry );
				$url = ! empty( $entry['url'] ) ? $entry['url'] : '';
			} elseif ( is_numeric( $entry ) ) {
				$id = (int) $entry;
			} elseif ( is_string( $entry ) && preg_match( '#^https?://#i', $entry ) ) {
				$id  = self::attachment_id_from_url( $entry );
				$url = $entry;
			}

			if ( $id <= 0 ) {
				continue;
			}

			if ( $url === '' ) {
				$url = (string) wp_get_attachment_url( $id );
			}

			$items[] = array(
				'id'  => $id,
				'url' => $url,
			);
		}

		return $items;
	}

	/**
	 * @param int $image_id
	 * @return string
	 */
	private static function format_media_cell_html( $image_id ){
		$image_id       = (int) $image_id;
		$thumbnail      = $image_id > 0 ? wp_get_attachment_url( $image_id ) : '';
		$is_placeholder = false;

		if ( $image_id <= 0 || ! $thumbnail ) {
			$thumbnail      = SheetsPilotGlobals::$urlImagePlaceholder;
			$is_placeholder = true;
			$image_id       = 0;
		}

		return '<img src="' . esc_url( $thumbnail ) . '" data-full="' . esc_url( $thumbnail ) . '" data-id="' . $image_id . '"' . SheetsPilotHelper::getAttachmentImagePreviewDataAttrs( $image_id ) . ' class="ubai_featured_image_uploader sp_hover_preview ' . ( $is_placeholder ? 'is_placeholder' : '' ) . '"  />';
	}

	/**
	 * @param mixed $value
	 * @return int[]
	 */
	private static function ids_from_save_value( $value ){
		if ( is_array( $value ) ) {
			$ids = array();
			foreach ( $value as $item ) {
				if ( is_array( $item ) ) {
					$id = self::parse_attachment_id( $item );
				} else {
					$id = (int) $item;
				}
				if ( $id > 0 ) {
					$ids[] = $id;
				}
			}

			return array_values( array_unique( $ids ) );
		}

		if ( $value === '' || $value === null || $value === false ) {
			return array();
		}

		if ( is_numeric( $value ) ) {
			$id = (int) $value;
			return $id > 0 ? array( $id ) : array();
		}

		if ( is_string( $value ) && strpos( $value, ',' ) !== false ) {
			return self::ids_from_save_value( explode( ',', $value ) );
		}

		$id = self::parse_attachment_id( $value );
		return $id > 0 ? array( $id ) : array();
	}

	/**
	 * @param int    $image_id
	 * @param string $format
	 * @return mixed
	 */
	private static function format_media_for_save( $image_id, $format ){
		$image_id = (int) $image_id;
		if ( $image_id <= 0 ) {
			return '';
		}

		$url = (string) wp_get_attachment_url( $image_id );

		if ( 'url' === $format ) {
			return $url;
		}

		if ( 'both' === $format ) {
			return array(
				'id'  => $image_id,
				'url' => $url,
			);
		}

		return $image_id;
	}

	/**
	 * @param int[]  $ids
	 * @param string $format
	 * @return mixed
	 */
	private static function format_gallery_for_save( $ids, $format ){
		$ids = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
		if ( empty( $ids ) ) {
			return '';
		}

		if ( 'url' === $format ) {
			$urls = array();
			foreach ( $ids as $id ) {
				$url = wp_get_attachment_url( $id );
				if ( $url ) {
					$urls[] = $url;
				}
			}

			return implode( ',', $urls );
		}

		if ( 'both' === $format ) {
			$items = array();
			foreach ( $ids as $id ) {
				$items[] = array(
					'id'  => $id,
					'url' => (string) wp_get_attachment_url( $id ),
				);
			}

			return $items;
		}

		return implode( ',', $ids );
	}

	/**
	 * Structure + values for the shared repeater drawer (ACF-shaped payload).
	 *
	 * @param string $column
	 * @param int    $post_id
	 * @return array
	 */
	public static function get_repeater_drawer_data( $column, $post_id ){
		$meta_key  = self::column_to_meta_key( $column );
		$post_type = get_post_type( $post_id );
		$field     = ( $meta_key && $post_type ) ? self::get_field( $post_type, $meta_key ) : null;

		$structure = $field
			? self::field_to_drawer_structure( $field )
			: array(
				'id'         => md5( (string) $meta_key ),
				'key'        => $meta_key,
				'name'       => $meta_key,
				'label'      => $meta_key,
				'type'       => 'repeater',
				'sub_fields' => array(),
			);

		return array(
			'structure' => $structure,
			'values'    => self::meta_to_drawer_values( self::get_jet_meta( $post_id, $meta_key ) ),
		);
	}

	/**
	 * Save drawer rows as JetEngine item-0 / item-1 meta.
	 *
	 * @param string $column
	 * @param int    $post_id
	 * @param array  $rows Numeric list of row field maps from the drawer.
	 * @return bool
	 */
	public static function save_repeater_from_drawer( $column, $post_id, $rows ){
		if ( ! self::is_column( $column ) ) {
			return false;
		}

		$meta_key = self::column_to_meta_key( $column );
		$meta     = self::rows_to_jetengine_meta( is_array( $rows ) ? $rows : array() );

		if ( empty( $meta ) ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, $meta );
		}

		return true;
	}

	/**
	 * @param string $column
	 * @param int    $post_id
	 * @return bool
	 */
	public static function delete_repeater_meta( $column, $post_id ){
		if ( ! self::is_column( $column ) ) {
			return false;
		}

		delete_post_meta( $post_id, self::column_to_meta_key( $column ) );

		return true;
	}

	/**
	 * @param string $column
	 * @return string
	 */
	private static function column_to_meta_key( $column ){
		if ( ! self::is_column( $column ) ) {
			return '';
		}

		return substr( $column, 8 );
	}

	/**
	 * Convert a JetEngine field (and nested repeater-fields) to the drawer schema.
	 *
	 * @param array  $field
	 * @param string $parent_path
	 * @return array
	 */
	private static function field_to_drawer_structure( $field, $parent_path = '' ){
		$name  = isset( $field['name'] ) ? $field['name'] : '';
		$label = ! empty( $field['title'] ) ? $field['title'] : $name;
		$type  = self::map_repeater_subfield_type( isset( $field['type'] ) ? $field['type'] : 'text' );
		$path  = $parent_path === '' ? $name : $parent_path . '/' . $name;

		$item = array(
			'id'         => md5( $path ),
			'key'        => $name,
			'name'       => $name,
			'label'      => $label,
			'type'       => $type,
			'sub_fields' => array(),
		);

		if ( in_array( $type, array( 'select', 'radio', 'checkbox' ), true ) ) {
			$item['choices'] = self::get_repeater_choices( $field );
			if ( 'select' === $type ) {
				$item['multiple'] = self::is_multiple_select( $field );
			}
		}

		$sub_fields = isset( $field['repeater-fields'] ) && is_array( $field['repeater-fields'] )
			? $field['repeater-fields']
			: array();

		if ( $sub_fields ) {
			foreach ( $sub_fields as $sub_field ) {
				if ( ! self::is_real_field( $sub_field ) ) {
					continue;
				}
				$item['sub_fields'][] = self::field_to_drawer_structure( $sub_field, $path );
			}
		}

		return $item;
	}

	/**
	 * Map JetEngine subfield types to names the drawer already renders.
	 *
	 * @param string $type
	 * @return string
	 */
	private static function map_repeater_subfield_type( $type ){
		$map = array(
			'colorpicker'    => 'color_picker',
			'date'           => 'text',
			'datetime-local' => 'text',
			'time'           => 'text',
			'switcher'       => 'text',
		);

		return isset( $map[ $type ] ) ? $map[ $type ] : $type;
	}

	/**
	 * @param array $field
	 * @return array
	 */
	private static function get_repeater_choices( $field ){
		$choices = array();
		$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();

		foreach ( $options as $key => $option ) {
			if ( is_array( $option ) ) {
				$stored = isset( $option['key'] ) ? $option['key'] : $key;
				$label  = isset( $option['value'] ) ? $option['value'] : $stored;
				$choices[ $stored ] = $label;
				continue;
			}

			$choices[ $key ] = $option;
		}

		return $choices;
	}

	/**
	 * Convert stored JetEngine repeater meta to drawer values.
	 *
	 * @param mixed $raw
	 * @return array
	 */
	private static function meta_to_drawer_values( $raw ){
		$rows   = self::repeater_meta_to_rows( $raw );
		$result = array();

		foreach ( $rows as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$fields = array();
			foreach ( $row as $key => $value ) {
				if ( self::is_item_keyed_repeater( $value ) || self::is_numeric_assoc_rows( $value ) ) {
					$fields[ $key ] = self::meta_to_drawer_values( $value );
				} else {
					$fields[ $key ] = $value;
				}
			}

			$result[] = array(
				'row_index' => (int) $index,
				'fields'    => $fields,
			);
		}

		return $result;
	}

	/**
	 * @param mixed $raw
	 * @return array
	 */
	private static function repeater_meta_to_rows( $raw ){
		if ( ! is_array( $raw ) || $raw === array() ) {
			return array();
		}

		if ( self::is_item_keyed_repeater( $raw ) ) {
			uksort(
				$raw,
				function( $a, $b ) {
					return (int) substr( $a, 5 ) - (int) substr( $b, 5 );
				}
			);

			return array_values( $raw );
		}

		if ( self::is_numeric_assoc_rows( $raw ) ) {
			return array_values( $raw );
		}

		return array();
	}

	/**
	 * Convert drawer numeric rows back to JetEngine item-N meta.
	 *
	 * @param array $rows
	 * @return array
	 */
	private static function rows_to_jetengine_meta( $rows ){
		$out   = array();
		$index = 0;

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$item = array();
			foreach ( $row as $key => $value ) {
				if ( self::is_numeric_assoc_rows( $value ) ) {
					$item[ $key ] = self::rows_to_jetengine_meta( $value );
				} else {
					$item[ $key ] = $value;
				}
			}

			$out[ 'item-' . $index ] = $item;
			$index++;
		}

		return $out;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	private static function is_item_keyed_repeater( $value ){
		if ( ! is_array( $value ) || $value === array() ) {
			return false;
		}

		foreach ( $value as $key => $row ) {
			if ( ! is_string( $key ) || ! preg_match( '/^item-\d+$/', $key ) || ! is_array( $row ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Numeric list of associative row arrays (drawer / nested save shape).
	 *
	 * @param mixed $value
	 * @return bool
	 */
	private static function is_numeric_assoc_rows( $value ){
		if ( ! is_array( $value ) || $value === array() ) {
			return false;
		}

		if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
			return false;
		}

		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				return false;
			}
		}

		return true;
	}

}
new SheetsPilotPluginsJetEngine();
