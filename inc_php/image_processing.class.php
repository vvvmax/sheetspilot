<?php

/**
 * Image processing for AI-generated images: pending storage, quality adjustment, promote/discard.
 * Stores files in uploads dir and tracks with transients.
 * User can apply (promote to attachment, set as featured) or discard (delete file + transient).
 *
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if (! defined('ABSPATH')) exit;
if (! defined('SHEETSPILOT_INC')) {
	die('restricted access');
}

class SheetsPilot_ImageProcessing
{

	const PENDING_DIR = 'unlimited-ai-pending';
	const TRANSIENT_PREFIX = 'sheetspilot_pending_image_';
	const PENDING_CONTEXT_PREFIX = 'sheetspilot_pending_image_ctx_';
	const PENDING_RETRIED_PREFIX = 'sheetspilot_pending_image_retried_';
	const TRANSIENT_TTL = 86400; // 24 hours
	const THUMB_MAX_WIDTH = 400;
	const LARGE_PNG_TO_JPEG_BYTES = 819200; // 800 KB
	const LOSSLESS_JPEG_QUALITY = 95;
	const PNG_COMPRESSION_LEVEL = 9;

	/** @var string Last compression engine used (imagick|gd|wp_image_editor). */
	private static $lastCompressEngine = '';

	/** @var string Preferred engine for current compress run (gd|imagick|'' = auto). */
	private static $compressPreferredEngine = '';


	private static function a_______IMAGE_GENERATION_SETTINGS________(){}


	/**
	 * Default image sidebar keys when the client omits them.
	 *
	 * @return array<string,string>
	 */
	public static function getDefaultImageSettings() {
		return array(
			'ratio'      => SheetsPilotGlobals::OPENAI_IMAGE_SIZE,
			'quality'    => SheetsPilotGlobals::DEFAULT_IMAGE_QUALITY,
			'format'     => SheetsPilotGlobals::DEFAULT_IMAGE_FORMAT,
			'resolution' => SheetsPilotGlobals::DEFAULT_IMAGE_RESOLUTION,
		);
	}

	/**
	 * Canonical aspect ratios, API pixel sizes, and display names (keep in sync with editor ratio UI).
	 *
	 * @return array<int,array{ratio:string,size:string,name:string}>
	 */
	public static function getImageSizeDefinitions() {
		// Order: square, then horizontal (landscape), then vertical (portrait). Dropdown also prepends "auto".
		return array(
			array(
				'ratio' => '1:1',
				'size'  => '1024x1024',
				'name'  => __( 'Square', 'sheetspilot' ),
			),
			array(
				'ratio' => '2:1',
				'size'  => '2048x1024',
				'name'  => __( 'Horizontal', 'sheetspilot' ),
			),
			array(
				'ratio' => '3:1',
				'size'  => '1536x512',
				'name'  => __( 'Banner', 'sheetspilot' ),
			),
			array(
				'ratio' => '3:2',
				'size'  => '1536x1024',
				'name'  => __( 'Standard', 'sheetspilot' ),
			),
			array(
				'ratio' => '4:3',
				'size'  => '1024x768',
				'name'  => __( 'Classic', 'sheetspilot' ),
			),
			array(
				'ratio' => '16:9',
				'size'  => '1792x1024',
				'name'  => __( 'Widescreen', 'sheetspilot' ),
			),
			array(
				'ratio' => '21:9',
				'size'  => '2352x1008',
				'name'  => __( 'Ultrawide', 'sheetspilot' ),
			),
			array(
				'ratio' => '2:3',
				'size'  => '1024x1536',
				'name'  => __( 'Portrait', 'sheetspilot' ),
			),
			array(
				'ratio' => '3:4',
				'size'  => '768x1024',
				'name'  => __( 'Traditional', 'sheetspilot' ),
			),
			array(
				'ratio' => '9:16',
				'size'  => '1024x1792',
				'name'  => __( 'Social Story', 'sheetspilot' ),
			),
		);
	}

	/**
	 * Aspect ratio => OpenAI Images API size (excludes "auto").
	 *
	 * @return array<string,string>
	 */
	public static function getAspectRatioToSizeMap() {
		$map = array();
		foreach ( self::getImageSizeDefinitions() as $def ) {
			$map[ $def['ratio'] ] = $def['size'];
		}
		return $map;
	}

	/**
	 * Allowed aspect ratio keys for cell rules and the image sidebar (includes "auto").
	 *
	 * @return array<int,string>
	 */
	public static function getAllowedAspectRatios() {
		$ratios = array( 'auto' );
		foreach ( self::getImageSizeDefinitions() as $def ) {
			$ratios[] = $def['ratio'];
		}
		return $ratios;
	}

	/**
	 * Context-menu action slug for a given aspect ratio (e.g. 16:9 → change-image-ratio-16-9).
	 *
	 * @param string $ratio Aspect ratio key.
	 * @return string
	 */
	public static function getChangeImageRatioContextAction( $ratio ) {
		$ratio = trim( (string) $ratio );
		if ( $ratio === '' || $ratio === 'auto' ) {
			return '';
		}
		return 'change-image-ratio-' . str_replace( ':', '-', $ratio );
	}

	/**
	 * Parse aspect ratio from a change-image-ratio-* context menu action.
	 *
	 * @param string $action Context menu data-action value.
	 * @return string Ratio key (e.g. 16:9) or empty string.
	 */
	public static function parseRatioFromChangeImageRatioAction( $action ) {
		$prefix = 'change-image-ratio-';
		$action = trim( (string) $action );
		if ( strpos( $action, $prefix ) !== 0 ) {
			return '';
		}
		$slug = substr( $action, strlen( $prefix ) );
		if ( $slug === '' ) {
			return '';
		}
		foreach ( self::getImageSizeDefinitions() as $def ) {
			$ratio = isset( $def['ratio'] ) ? (string) $def['ratio'] : '';
			if ( $ratio !== '' && str_replace( ':', '-', $ratio ) === $slug ) {
				return $ratio;
			}
		}
		return '';
	}

	/**
	 * Display size for ratio-change prompts (1024x768 → 1024×768).
	 *
	 * @param string $size API size string (e.g. 768x1024).
	 * @return string
	 */
	public static function formatImageSizeForPrompt( $size ) {
		$size = strtolower( trim( (string) $size ) );
		if ( $size === '' ) {
			return '';
		}
		return str_replace( 'x', '×', $size );
	}

	/**
	 * Portrait / landscape / square label from pixel dimensions.
	 *
	 * @param string $size API size string (e.g. 768x1024).
	 * @return string
	 */
	public static function getAspectRatioOrientationLabel( $size ) {
		$size = strtolower( trim( (string) $size ) );
		if ( preg_match( '/^(\d+)x(\d+)$/', $size, $m ) ) {
			$w = (int) $m[1];
			$h = (int) $m[2];
			if ( $w === $h ) {
				return __( 'square', 'sheetspilot' );
			}
			if ( $h > $w ) {
				return __( 'portrait', 'sheetspilot' );
			}
			return __( 'landscape', 'sheetspilot' );
		}
		return __( 'landscape', 'sheetspilot' );
	}

	/**
	 * Full outpaint-style prompt for context-menu ratio change (includes target pixel size).
	 *
	 * @param string $ratio Aspect ratio key (e.g. 3:4).
	 * @return string
	 */
	public static function buildChangeImageRatioPrompt( $ratio ) {
		$ratio = trim( (string) $ratio );
		if ( $ratio === '' || $ratio === 'auto' ) {
			return '';
		}
		$size = self::mapAspectRatioToImageSize( $ratio );
		if ( $size === '' || $size === 'auto' ) {
			return '';
		}
		$orientation  = self::getAspectRatioOrientationLabel( $size );
		$size_display = self::formatImageSizeForPrompt( $size );

		return sprintf(
			/* translators: 1: aspect ratio (e.g. 3:4), 2: orientation (portrait/landscape/square), 3: pixel size (e.g. 768×1024) */
			__(
				"Convert this image to a %1\$s %2\$s aspect ratio (%3\$s).\n\nKeep the original image unchanged.\nOnly expand the missing areas beyond the current borders.\nMatch the existing lighting, perspective, colors, and style.\nDo not alter the main subject or existing content.",
				'sheetspilot'
			),
			$ratio,
			$orientation,
			$size_display
		);
	}

	/**
	 * Sub-menu items for "Change Image Ratio" on the image cell context menu.
	 *
	 * @return array<int,array{action:string,text:string,prompt:string,visible_for_cell_types:string}>
	 */
	public static function getChangeImageRatioContextSubItems() {
		$items = array();
		foreach ( self::getImageSizeDefinitions() as $def ) {
			$ratio = isset( $def['ratio'] ) ? trim( (string) $def['ratio'] ) : '';
			if ( $ratio === '' ) {
				continue;
			}
			$action = self::getChangeImageRatioContextAction( $ratio );
			if ( $action === '' ) {
				continue;
			}
			$name = isset( $def['name'] ) ? trim( (string) $def['name'] ) : '';
			$text = $ratio;
			if ( $name !== '' ) {
				$text = $ratio . ' — ' . $name;
			}
			$items[] = array(
				'action'                   => $action,
				'text'                     => $text,
				'prompt'                   => self::buildChangeImageRatioPrompt( $ratio ),
				'visible_for_cell_types'   => 'image',
			);
		}
		return $items;
	}

	/**
	 * Map editor aspect ratio to OpenAI Images API `size` (or "auto").
	 *
	 * @param string $ratio Aspect ratio key.
	 * @return string
	 */
	public static function mapAspectRatioToImageSize( $ratio ) {
		$ratio = strtolower( trim( (string) $ratio ) );
		if ( $ratio === '' || $ratio === 'auto' ) {
			return 'auto';
		}
		$map = self::getAspectRatioToSizeMap();
		if ( isset( $map[ $ratio ] ) ) {
			return $map[ $ratio ];
		}
		return '1024x1024';
	}


	/**
	 * Resolve sidebar/API quality (default uses plugin constant, not general settings).
	 *
	 * @param string $value Raw UI value.
	 * @return string low|medium|high|auto
	 */
	public static function resolveApiImageQuality( $value ) {
		$v = strtolower( trim( (string) $value ) );
		if ( $v === '' || $v === 'default' ) {
			return SheetsPilotGlobals::DEFAULT_IMAGE_QUALITY;
		}
		$legacy_map = array(
			'0.5k' => 'low',
			'1k'   => 'medium',
			'1.5k' => 'high',
			'2k'   => 'high',
		);
		if ( isset( $legacy_map[ $v ] ) ) {
			$v = $legacy_map[ $v ];
		}
		if ( in_array( $v, array( 'low', 'medium', 'high', 'auto' ), true ) ) {
			return $v;
		}
		return SheetsPilotGlobals::DEFAULT_IMAGE_QUALITY;
	}

	/**
	 * Resolve sidebar/API format (default uses plugin constant, not general settings).
	 *
	 * @param string $value Raw UI value.
	 * @return string png|jpeg|webp
	 */
	public static function resolveApiImageFormat( $value ) {
		$v = strtolower( trim( (string) $value ) );
		if ( $v === 'jpg' ) {
			$v = 'jpeg';
		}
		if ( $v === '' || $v === 'default' ) {
			return SheetsPilotGlobals::DEFAULT_IMAGE_FORMAT;
		}
		if ( in_array( $v, array( 'png', 'jpeg', 'webp' ), true ) ) {
			return $v;
		}
		return SheetsPilotGlobals::DEFAULT_IMAGE_FORMAT;
	}

	/**
	 * Resolve sidebar/API resolution tier (default uses plugin constant).
	 *
	 * @param string $value Raw UI value.
	 * @return string 1k|2k|3k|4k
	 */
	public static function resolveApiImageResolution( $value ) {
		$r = strtolower( trim( (string) $value ) );
		if ( $r === '' || $r === 'default' ) {
			return SheetsPilotGlobals::DEFAULT_IMAGE_RESOLUTION;
		}
		if ( in_array( $r, array( '1k', '2k', '3k', '4k' ), true ) ) {
			return $r;
		}
		return SheetsPilotGlobals::DEFAULT_IMAGE_RESOLUTION;
	}

	/**
	 * Allowed resolution tiers for the image sidebar / cell rules UI.
	 *
	 * @return array<int,string>
	 */
	public static function getAllowedImageResolutions() {
		return array( 'default', '1k', '2k', '3k', '4k' );
	}

	/**
	 * Linear scale multiplier for a resolution tier.
	 *
	 * @param string $resolution 1k|2k|3k|4k or UI value (normalized internally).
	 * @return int
	 */
	public static function getImageResolutionMultiplier( $resolution ) {
		$map = array(
			'1k' => 1,
			'2k' => 2,
			'3k' => 3,
			'4k' => 4,
		);
		$r = self::resolveApiImageResolution( $resolution );
		return isset( $map[ $r ] ) ? (int) $map[ $r ] : 1;
	}

	/**
	 * Scale a WxH size string by the resolution tier (1K = base, 2K = 2×, etc.).
	 *
	 * @param string $size       e.g. 1024x1024 or "auto".
	 * @param string $resolution 1k|2k|3k|4k.
	 * @return string
	 */
	public static function scaleImageSizeByResolution( $size, $resolution ) {

		$size = trim( (string) $size );
		if ( $size === '' || strtolower( $size ) === 'auto' ) {
			return $size;
		}
		if ( ! preg_match( '/^(\d+)x(\d+)$/i', $size, $m ) ) {
			return $size;
		}
		$mult = self::getImageResolutionMultiplier( $resolution );
		if ( $mult <= 1 ) {
			return strtolower( $size );
		}
		$w = (int) $m[1] * $mult;
		$h = (int) $m[2] * $mult;
		$w = max( 64, (int) round( $w / 2 ) * 2 );
		$h = max( 64, (int) round( $h / 2 ) * 2 );
		return $w . 'x' . $h;
	}



	/**
	 * Read image option values from cell rules for the given column (when include_rules is enabled).
	 *
	 * @param array<string,mixed> $table_data Table/cell data (post_type, include_rules).
	 * @param string              $column_key Normalized column key (e.g. post_image).
	 * @return array<string,string> Only keys set in cell rules (ratio, quality, format, resolution).
	 */
	public static function getImageSettingsFromRules( $table_data, $column_key ) {
		$rules_settings = array();

		$post_type_key = sanitize_key( (string) SheetsPilotFunctions::getVal( $table_data, 'post_type', '' ) );
		$column_key    = (string) $column_key;

		$include_rules = SheetsPilot_Prompts::isApplyPromptTableOptionEnabled( $table_data, 'include_rules' );
		if ( ! $include_rules || $post_type_key === '' || $column_key === '' || SheetsPilotGlobals::$isPro != true ) {
			return $rules_settings;
		}

		$cell_rules = SheetsPilot_PromptsUI::get_cell_rules( $post_type_key );
		$ar_key     = $column_key . '__aspect_ratio';
		$q_key      = $column_key . '__quality';
		$f_key      = $column_key . '__format';
		$res_key    = $column_key . '__resolution';

		$rule_ratio      = strtolower( trim( (string) SheetsPilotFunctions::getVal( $cell_rules, $ar_key, '' ) ) );
		$rule_quality    = trim( (string) SheetsPilotFunctions::getVal( $cell_rules, $q_key, '' ) );
		$rule_format     = trim( (string) SheetsPilotFunctions::getVal( $cell_rules, $f_key, '' ) );
		$rule_resolution = trim( (string) SheetsPilotFunctions::getVal( $cell_rules, $res_key, '' ) );

		if ( $rule_ratio !== '' ) {
			$rules_settings['ratio'] = $rule_ratio;
		}
		if ( $rule_quality !== '' && strtolower( $rule_quality ) !== 'default' ) {
			$rules_settings['quality'] = $rule_quality;
		}
		if ( $rule_format !== '' && strtolower( $rule_format ) !== 'default' ) {
			$rules_settings['format'] = $rule_format;
		}
		if ( $rule_resolution !== '' && strtolower( $rule_resolution ) !== 'default' ) {
			$rules_settings['resolution'] = strtolower( $rule_resolution );
		}

		return $rules_settings;
	}

	/**
	 * Whether an image setting value means "use rules or plugin default" (sidebar ---).
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private static function isImageSettingDefault( $value ) {
		return trim( (string) $value ) === '' || strtolower( trim( (string) $value ) ) === 'default';
	}

	/**
	 * Merge defaults, cell rules, then client: explicit sidebar values override rules and defaults.
	 *
	 * @param array<string,string> $defaults Plugin default image_settings.
	 * @param array<string,mixed>  $client   Client/sidebar payload.
	 * @param array<string,string> $rules    Values from getImageSettingsFromRules().
	 * @return array<string,string>
	 */
	public static function mergeImageSettings( $defaults, $client, $rules ) {
		$keys   = array( 'ratio', 'quality', 'format', 'resolution' );
		$merged = array();

		foreach ( $keys as $key ) {
			$merged[ $key ] = isset( $defaults[ $key ] ) ? (string) $defaults[ $key ] : '';

			if ( isset( $rules[ $key ] ) && (string) $rules[ $key ] !== '' ) {
				$merged[ $key ] = (string) $rules[ $key ];
			}

			$client_val = isset( $client[ $key ] ) ? $client[ $key ] : '';
			if ( ! self::isImageSettingDefault( $client_val ) ) {
				$merged[ $key ] = 'ratio' === $key
					? strtolower( trim( (string) $client_val ) )
					: trim( (string) $client_val );
			}
		}

		return $merged;
	}

	/**
	 * Merge client image_settings with defaults and cell-rule overrides for the given column.
	 *
	 * @param array<string,mixed> $table_data  Table/cell data (post_type, image_settings, include_rules).
	 * @param string              $column_key Normalized column key (e.g. post_image).
	 * @return array{image_size:string,quality_override:string,format_override:string}
	 */
	public static function resolveImageSettingsForTable( $table_data, $column_key ) {

		//get the settings from 3 sources and merge them. client then rules then defaults.

		$image_settings_defaults = self::getDefaultImageSettings();
		$image_settings_client   = (array) SheetsPilotFunctions::getVal( $table_data, 'image_settings', array() );

		$image_settings_rules    = self::getImageSettingsFromRules( $table_data, $column_key );
		$image_settings          = self::mergeImageSettings( $image_settings_defaults, $image_settings_client, $image_settings_rules );

		$ratio = strtolower( trim( (string) SheetsPilotFunctions::getVal( $image_settings, 'ratio', 'auto' ) ) );
		$ratio = $ratio === '' ? 'auto' : $ratio;

		//map the image settings to the image size.
		$image_size = self::mapAspectRatioToImageSize( $ratio );

		$quality_raw    = (string) SheetsPilotFunctions::getVal( $image_settings, 'quality', 'default' );
		$format_raw     = (string) SheetsPilotFunctions::getVal( $image_settings, 'format', 'default' );
		$resolution_raw = (string) SheetsPilotFunctions::getVal( $image_settings, 'resolution', 'default' );

		$quality_override = self::resolveApiImageQuality( $quality_raw );
		$format_override  = self::resolveApiImageFormat( $format_raw );
		$resolution       = self::resolveApiImageResolution( $resolution_raw );
		$image_size       = self::scaleImageSizeByResolution( $image_size, $resolution );

		$output = array(
			'image_size'       => $image_size,
			'quality_override' => $quality_override,
			'format_override'  => $format_override,
		);


		return $output;
	}
	

	private static function a_______PENDING_IMAGE________(){}

	/**
	 * Get the pending directory path and ensure it exists.
	 *
	 * @return string Absolute path to pending dir.
	 */
	public static function getPendingDir()
	{
		$upload_dir = wp_upload_dir();
		if (! empty($upload_dir['error'])) {
			SheetsPilotFunctions::throwError($upload_dir['error']);
		}
		$dir = $upload_dir['basedir'] . '/' . self::PENDING_DIR;
		if (! is_dir($dir)) {
			wp_mkdir_p($dir);
		}
		if (! is_dir($dir) || ! wp_is_writable($dir)) {
			SheetsPilotFunctions::throwError(__('Could not create or write to pending images directory.',  'sheetspilot'));
		}
		return $dir;
	}


	/**
	 * Get the base URL for pending images (for preview).
	 *
	 * @return string Base URL.
	 */
	public static function getPendingUrl()
	{
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'] . '/' . self::PENDING_DIR . '/';
	}

	/**
	 * Save image from API result (URL or data URL) to pending dir and store metadata in transient.
	 *
	 * @param string $image_url_or_data URL (http/https) or data URL (data:image/png;base64,...).
	 * @param int    $post_id           Post ID this image is for.
	 * @param string $column            Column name (e.g. post_image).
	 * @param string $quality           Image quality label.
	 * @param array  $args              Optional request_id, generation_context for one-time retry.
	 * @return array { request_id, preview_url }.
	 */
	public static function savePending($image_url_or_data, $post_id, $column, $quality, $args = array())
	{

		$args = is_array( $args ) ? $args : array();
		$post_title = get_post($post_id)->post_title;
		$request_id = '';
		if ( ! empty( $args['request_id'] ) ) {
			$request_id = sanitize_file_name( (string) $args['request_id'] );
		}
		if ( $request_id === '' ) {
			$request_id = 'uba_' . sanitize_file_name($post_title) . '_' . wp_generate_password(22, false);
		}
		$dir        = self::getPendingDir();
		$ext        = 'png';
		$filename   = $request_id . '.' . $ext;
		$filepath   = $dir . '/' . $filename;

		// Malformed data URL from API handler (e.g. missing mime): data:;base64,...
		if ( preg_match( '#^data:;base64,(.+)$#s', $image_url_or_data, $m_bare ) ) {
			$image_url_or_data = 'data:image/png;base64,' . $m_bare[1];
		}

		if (preg_match('#^data:image/(\w+);base64,(.+)$#s', $image_url_or_data, $m)) {
			$data = base64_decode($m[2], true);
			if ($data === false) {
				SheetsPilotFunctions::throwError(__('Invalid base64 image data.',  'sheetspilot'));
			}
			if ($m[1] === 'jpeg' || $m[1] === 'jpg') {
				$ext      = 'jpg';
				$filename = $request_id . '.' . $ext;
				$filepath = $dir . '/' . $filename;
			}
			if ($m[1] === 'webp') {
				$ext      = 'webp';
				$filename = $request_id . '.' . $ext;
				$filepath = $dir . '/' . $filename;
			}
			$written = file_put_contents($filepath, $data);
		} else {
			// Remote URL: download to temp file.
			$tmp = download_url($image_url_or_data);
			if (is_wp_error($tmp)) {
				SheetsPilotFunctions::throwError($tmp->get_error_message());
			}

			$detected_ext = self::detectImageFormatExt($tmp);
			if ($detected_ext !== '') {
				$ext      = $detected_ext;
				$filename = $request_id . '.' . $ext;
				$filepath = $dir . '/' . $filename;
			}

			$written = copy($tmp, $filepath);
			@unlink($tmp);
		}

		if (! $written || ! is_file($filepath)) {
			SheetsPilotFunctions::throwError(__('Could not save pending image file.',  'sheetspilot'));
		}

		$filepath_before_compress = $filepath;
		$ext_before_compress       = $ext;
		$source_backup_path        = '';
		if ( self::isValidPendingImageFile( $filepath ) ) {
			$source_backup_path = $dir . '/' . $request_id . '_source.' . $ext_before_compress;
			if ( ! @copy( $filepath, $source_backup_path ) ) {
				$source_backup_path = '';
			}
		}

		$filepath_after_compress = self::compressImageForMedia( $filepath );
		$filepath                = self::isValidPendingImageFile( $filepath_after_compress )
			? $filepath_after_compress
			: $filepath_before_compress;
		if ( ! self::isValidPendingImageFile( $filepath ) && $source_backup_path !== '' ) {
			$filepath = $source_backup_path;
		}

		$filename = basename( $filepath );
		$ext      = strtolower( pathinfo( $filepath, PATHINFO_EXTENSION ) );
		if ( $ext === '' ) {
			$ext = $ext_before_compress !== '' ? $ext_before_compress : 'png';
		}
		if ( $ext === 'jpeg' ) {
			$ext = 'jpg';
		}

		$thumb_path = self::createThumbnail( $filepath, $dir, $request_id, $ext );
		if ( ! $thumb_path && $source_backup_path !== '' && self::isValidPendingImageFile( $source_backup_path ) ) {
			$source_ext = strtolower( pathinfo( $source_backup_path, PATHINFO_EXTENSION ) );
			if ( $source_ext === 'jpeg' ) {
				$source_ext = 'jpg';
			}
			$thumb_path = self::createThumbnail( $source_backup_path, $dir, $request_id, $source_ext !== '' ? $source_ext : $ext );
		}

		$baseurl     = self::getPendingUrl();
		$preview_url = self::resolvePendingPreviewUrl( $baseurl, $thumb_path, $filepath, $source_backup_path );

		$file_meta = self::probePendingImageFileMeta(
			array(
				$thumb_path,
				$filepath,
				$source_backup_path,
				$filepath_before_compress,
			)
		);

		// Thumb missing or unreadable: use compressed or source backup as the single preview URL.
		if ( $file_meta['file_size'] <= 0 || $preview_url === '' ) {
			$preview_url = self::resolvePendingPreviewUrl( $baseurl, '', $filepath, $source_backup_path );
			if ( $preview_url === '' ) {
				$preview_url = self::resolvePendingPreviewUrl( $baseurl, '', $filepath_before_compress, $source_backup_path );
			}
			$file_meta = self::probePendingImageFileMeta(
				array(
					$filepath,
					$source_backup_path,
					$filepath_before_compress,
				)
			);
		}

		if ( $preview_url === '' ) {
			SheetsPilotFunctions::throwError( __( 'Could not save pending image file.', 'sheetspilot' ) );
		}

		$meta = array(
			'path'               => $filepath,
			'thumb_path'         => $thumb_path,
			'source_backup_path' => $source_backup_path,
			'url'                => $preview_url,
			'post_id'            => (int) $post_id,
			'column'             => $column,
			'created'            => time(),
			'quality'            => $quality,
			'file_size'          => (int) $file_meta['file_size'],
			'file_type'          => (string) $file_meta['file_type'],
			'width'              => (int) $file_meta['width'],
			'height'             => (int) $file_meta['height'],
		);
		set_transient(self::TRANSIENT_PREFIX . $request_id, $meta, self::TRANSIENT_TTL);

		if ( ! empty( $args['generation_context'] ) && is_array( $args['generation_context'] ) ) {
			set_transient(
				self::PENDING_CONTEXT_PREFIX . $request_id,
				$args['generation_context'],
				self::TRANSIENT_TTL
			);
		}

		return array(
			'request_id'  => $request_id,
			'preview_url' => $preview_url,
			'file_size'   => (int) $file_meta['file_size'],
			'file_type'   => (string) $file_meta['file_type'],
			'width'       => (int) $file_meta['width'],
			'height'      => (int) $file_meta['height'],
		);
	
	}

	/**
	 * Build promote-pending AJAX payload with hover-preview metadata.
	 *
	 * @param int         $attachment_id      Attachment post ID.
	 * @param string      $thumbnail_url      Medium thumbnail URL.
	 * @param string|null $thumbnail_url_full Optional full-size thumbnail URL (galleries).
	 * @return array
	 */
	private static function buildPromotePendingResponse( $attachment_id, $thumbnail_url, $thumbnail_url_full = null ) {
		$preview_meta = SheetsPilotHelper::getAttachmentImagePreviewMeta( $attachment_id );

		if ( ! is_string( $thumbnail_url ) || $thumbnail_url === '' ) {
			$thumbnail_url = self::resolveAttachmentThumbnailUrl( $attachment_id, 'medium' );
		}
		if ( $thumbnail_url_full !== null && ( ! is_string( $thumbnail_url_full ) || $thumbnail_url_full === '' ) ) {
			$thumbnail_url_full = self::resolveAttachmentThumbnailUrl( $attachment_id, 'full' );
		}
		if ( $preview_meta['full_url'] === '' ) {
			$preview_meta['full_url'] = self::resolveAttachmentThumbnailUrl( $attachment_id, 'full' );
		}

		$response = array(
			'attachment_id' => $attachment_id,
			'thumbnail_url' => $thumbnail_url,
			'file_size'     => $preview_meta['file_size'],
			'file_type'     => $preview_meta['file_type'],
			'width'         => $preview_meta['width'],
			'height'        => $preview_meta['height'],
			'full_url'      => $preview_meta['full_url'],
			'filename'      => $preview_meta['filename'],
		);

		if ( $thumbnail_url_full !== null ) {
			$response['thumbnail_url_full'] = $thumbnail_url_full;
		}

		return $response;
	}

	/**
	 * Best-effort attachment preview URL when WordPress size URLs are missing.
	 *
	 * @param int    $attachment_id Attachment post ID.
	 * @param string $size          Image size name or 'full'.
	 * @return string
	 */
	private static function resolveAttachmentThumbnailUrl( $attachment_id, $size = 'medium' ) {
		$attachment_id = absint( $attachment_id );
		if ( $attachment_id <= 0 ) {
			return '';
		}

		if ( $size !== 'full' ) {
			$url = wp_get_attachment_image_url( $attachment_id, $size );
			if ( is_string( $url ) && $url !== '' ) {
				return $url;
			}
		}

		$url = wp_get_attachment_url( $attachment_id );
		if ( is_string( $url ) && $url !== '' ) {
			return $url;
		}

		$file = get_attached_file( $attachment_id );
		if ( ! is_string( $file ) || $file === '' || ! is_file( $file ) ) {
			return '';
		}

		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) || empty( $upload_dir['basedir'] ) || empty( $upload_dir['baseurl'] ) ) {
			return '';
		}

		$basedir    = wp_normalize_path( $upload_dir['basedir'] );
		$normalized = wp_normalize_path( $file );
		if ( strpos( $normalized, $basedir ) !== 0 ) {
			return '';
		}

		$relative = ltrim( substr( $normalized, strlen( $basedir ) ), '/\\' );

		return $upload_dir['baseurl'] . '/' . str_replace( '\\', '/', $relative );
	}

	/**
	 * @param string $filepath Candidate image path.
	 * @return string|null Path when the file exists and is non-empty.
	 */
	private static function rememberValidImagePath( $filepath ) {
		return self::isValidPendingImageFile( $filepath ) ? $filepath : null;
	}

	/**
	 * Whether a pending image path exists and is non-empty.
	 *
	 * @param string $path Absolute file path.
	 * @return bool
	 */
	private static function isValidPendingImageFile( $path ) {
		if ( ! is_string( $path ) || $path === '' ) {
			return false;
		}
		clearstatcache( true, $path );
		if ( ! is_file( $path ) ) {
			return false;
		}
		$bytes = filesize( $path );

		return $bytes !== false && $bytes > 0;
	}

	/**
	 * Read image width/height from disk with WebP-friendly fallbacks.
	 *
	 * @param string $filepath Absolute image path.
	 * @return array{width:int,height:int}
	 */
	private static function readImageDimensions( $filepath ) {
		$width  = 0;
		$height = 0;

		if ( ! self::isValidPendingImageFile( $filepath ) ) {
			return array(
				'width'  => 0,
				'height' => 0,
			);
		}

		$info = wp_getimagesize( $filepath );
		if ( is_array( $info ) && ! empty( $info[0] ) && ! empty( $info[1] ) ) {
			return array(
				'width'  => (int) $info[0],
				'height' => (int) $info[1],
			);
		}

		if ( function_exists( 'getimagesize' ) ) {
			$info = @getimagesize( $filepath );
			if ( is_array( $info ) && ! empty( $info[0] ) && ! empty( $info[1] ) ) {
				return array(
					'width'  => (int) $info[0],
					'height' => (int) $info[1],
				);
			}
		}

		$ext = strtolower( pathinfo( $filepath, PATHINFO_EXTENSION ) );
		if ( $ext === 'webp' && function_exists( 'imagecreatefromwebp' ) ) {
			$img = @imagecreatefromwebp( $filepath );
			if ( $img !== false ) {
				$width  = (int) imagesx( $img );
				$height = (int) imagesy( $img );
				imagedestroy( $img );
			}
		}

		if ( ( $width <= 0 || $height <= 0 ) && class_exists( 'Imagick' ) ) {
			try {
				$imagick = new Imagick( $filepath );
				$width   = (int) $imagick->getImageWidth();
				$height  = (int) $imagick->getImageHeight();
				$imagick->clear();
				$imagick->destroy();
			} catch ( Exception $e ) { 
			}
		}

		return array(
			'width'  => $width,
			'height' => $height,
		);
	}

	/**
	 * Probe the first readable pending image path for preview metadata.
	 *
	 * @param array<int,string|null> $paths Candidate paths in priority order.
	 * @return array{file_size:int,width:int,height:int,file_type:string}
	 */
	private static function probePendingImageFileMeta( $paths ) {
		$empty = array(
			'file_size' => 0,
			'width'     => 0,
			'height'    => 0,
			'file_type' => '',
		);

		if ( ! is_array( $paths ) ) {
			return $empty;
		}

		foreach ( $paths as $path ) {
			if ( ! self::isValidPendingImageFile( $path ) ) {
				continue;
			}

			clearstatcache( true, $path );
			$bytes = filesize( $path );
			if ( $bytes === false || $bytes <= 0 ) {
				continue;
			}

			$dims = self::readImageDimensions( $path );
			$ext  = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
			if ( $ext === 'jpeg' ) {
				$ext = 'jpg';
			}

			return array(
				'file_size' => (int) $bytes,
				'width'     => (int) $dims['width'],
				'height'    => (int) $dims['height'],
				'file_type' => $ext !== '' ? $ext : 'png',
			);
		}

		return $empty;
	}

	/**
	 * Pick a single client-facing preview URL (thumb, compressed, or source backup).
	 *
	 * @param string $baseurl           Pending images base URL.
	 * @param string|null $thumb_path   Optional thumbnail path.
	 * @param string $primary_path      Primary image path after compression.
	 * @param string $fallback_path     Optional uncompressed backup path.
	 * @return string Public URL or empty string when no readable file exists.
	 */
	private static function resolvePendingPreviewUrl( $baseurl, $thumb_path, $primary_path, $fallback_path = '' ) {
		$candidates = array();
		if ( is_string( $thumb_path ) && $thumb_path !== '' ) {
			$candidates[] = $thumb_path;
		}
		if ( is_string( $primary_path ) && $primary_path !== '' ) {
			$candidates[] = $primary_path;
		}
		if ( is_string( $fallback_path ) && $fallback_path !== '' ) {
			$candidates[] = $fallback_path;
		}

		foreach ( $candidates as $path ) {
			if ( ! self::isValidPendingImageFile( $path ) ) {
				continue;
			}

			return $baseurl . basename( $path );
		}

		return '';
	}

	/**
	 * Create a thumbnail (max width THUMB_MAX_WIDTH) from the full-size image. Same extension.
	 *
	 * @param string $filepath   Full path to source image.
	 * @param string $dir       Directory to save thumb in.
	 * @param string $request_id Request ID for naming.
	 * @param string $ext       File extension (png, jpg, etc).
	 * @return string|null Thumb path or null on failure.
	 */
	private static function createThumbnail($filepath, $dir, $request_id, $ext)
	{
		$isDebug = false;

		if ($isDebug) {
			dmp('createThumbnail: start filepath=' . $filepath . ' dir=' . $dir . ' request_id=' . $request_id . ' ext=' . $ext);
		}
		$thumb_filename = $request_id . '_thumb.' . $ext;
		$thumb_path     = $dir . '/' . $thumb_filename;
		if ($isDebug) {
			dmp('createThumbnail: thumb_path=' . $thumb_path);
		}
		$editor = wp_get_image_editor($filepath);
		if ($isDebug) {
			dmp('createThumbnail: wp_get_image_editor done' . (is_wp_error($editor) ? ' ERROR=' . $editor->get_error_message() : ''));
		}
		if (! $editor || is_wp_error($editor) || ! method_exists($editor, 'load')) {
			return null;
		}

		$loaded = $editor->load();
		if ($isDebug) {
			dmp('createThumbnail: editor->load done' . (is_wp_error($loaded) ? ' ERROR=' . $loaded->get_error_message() : ''));
		}
		if (is_wp_error($loaded)) {
			return null;
		}
		// Max width 400; use large height so only width is constrained (proportional scale).
		$resized = $editor->resize(self::THUMB_MAX_WIDTH, 9999, false);
		if ($isDebug) {
			dmp('createThumbnail: editor->resize done' . (is_wp_error($resized) ? ' ERROR=' . $resized->get_error_message() : ''));
		}
		if (is_wp_error($resized)) {
			return null;
		}
		$saved = $editor->save($thumb_path);
		if ($isDebug) {
			dmp('createThumbnail: editor->save done' . (is_wp_error($saved) ? ' ERROR=' . $saved->get_error_message() : ' path=' . (is_array($saved) && ! empty($saved['path']) ? $saved['path'] : $thumb_path)));
		}
		if (is_wp_error($saved)) {
			return null;
		}
		$thumb_path = is_array($saved) && ! empty($saved['path']) ? $saved['path'] : $thumb_path;
		if ($isDebug) {
			dmp('createThumbnail: final thumb_path=' . $thumb_path . ' is_file=' . (is_file($thumb_path) ? 'yes' : 'no'));
		}
		if (! is_file($thumb_path)) {
			return null;
		}
		if ($isDebug) {
			dmp('createThumbnail: success');
		}
		return $thumb_path;
	}

	/**
	 * Get pending image metadata by request_id.
	 *
	 * @param string $request_id Request ID from savePending().
	 * @return array|null Meta array or null if not found/expired.
	 */
	public static function getPending($request_id)
	{
		$meta = get_transient(self::TRANSIENT_PREFIX . $request_id);
		if (! is_array($meta) || empty($meta['path']) || ! is_file($meta['path'])) {
			return null;
		}
		return $meta;
	}

	/**
	 * Detect image format from file contents (mime), falling back to extension.
	 *
	 * @param string $filepath Absolute path to an image file.
	 * @return string jpg|png|webp|'' when unknown.
	 */
	private static function detectImageFormatExt( $filepath ) {
		$mime = '';
		if ( function_exists( 'wp_get_image_mime' ) ) {
			$mime = (string) wp_get_image_mime( $filepath );
		} elseif ( function_exists( 'mime_content_type' ) ) {
			$mime = (string) mime_content_type( $filepath );
		}

		$mime = strtolower( trim( $mime ) );
		if ( $mime === 'image/jpeg' || $mime === 'image/jpg' ) {
			return 'jpg';
		}
		if ( $mime === 'image/png' ) {
			return 'png';
		}
		if ( $mime === 'image/webp' ) {
			return 'webp';
		}

		$info = wp_check_filetype( $filepath );
		$ext  = strtolower( (string) ( $info['ext'] ?? '' ) );
		if ( $ext === 'jpeg' ) {
			return 'jpg';
		}
		if ( in_array( $ext, array( 'jpg', 'png', 'webp' ), true ) ) {
			return $ext;
		}

		return '';
	}

	/**
	 * Copy the image to a sidecar backup before in-place compression.
	 *
	 * @param string $filepath Absolute path to the image.
	 * @return string Backup file path.
	 */
	private static function createCompressBackup( $filepath ) {
		$backup_path = $filepath . '.sheetspilot-compress-backup';
		if ( ! @copy( $filepath, $backup_path ) ) {
			self::logLosslessCompressImageNote( 'skipped compression: could not create backup' );
			return '';
		}

		return $backup_path;
	}

	/**
	 * @param string $backup_path Sidecar backup path.
	 */
	private static function removeCompressBackup( $backup_path ) {
		if ( is_string( $backup_path ) && $backup_path !== '' && is_file( $backup_path ) ) {
			@unlink( $backup_path );
		}
	}

	/**
	 * Restore the original image from a compression backup.
	 *
	 * @param string $filepath    Absolute path to the image.
	 * @param string $backup_path Sidecar backup path.
	 * @param string $format      Image format label for logging (jpg|png|webp|unknown).
	 */
	private static function restoreCompressBackup( $filepath, $backup_path, $format = '' ) {
		if ( ! is_string( $backup_path ) || $backup_path === '' || ! is_file( $backup_path ) ) {
			return;
		}

		@copy( $backup_path, $filepath );
		$restored_size = is_file( $filepath ) ? (int) filesize( $filepath ) : 0;
		$message       = 'backup restored';
		if ( $format !== '' ) {
			$message .= ', format ' . $format;
		}
		$message .= ', file size after restore: ' . $restored_size . ' bytes';
		self::logLosslessCompressImageNote( $message );
		self::removeCompressBackup( $backup_path );
	}

	/**
	 * Ensure compression produced a non-empty file; otherwise restore the backup.
	 *
	 * @param string $filepath    Absolute path to the image.
	 * @param string $backup_path Sidecar backup path.
	 * @param string $format      Image format label for logging (jpg|png|webp|unknown).
	 * @return bool True when the compressed file is valid; false when the original was restored.
	 */
	private static function assertValidCompressedImage( $filepath, $backup_path, $format = '' ) {
		$size = is_file( $filepath ) ? (int) filesize( $filepath ) : 0;
		if ( $size <= 0 ) {
			self::restoreCompressBackup( $filepath, $backup_path, $format );
			self::logLosslessCompressImageNote( 'compression produced empty file; kept original' );
			return false;
		}

		self::removeCompressBackup( $backup_path );
		return true;
	}

	/**
	 * Temp path used for safe in-place image rewrites (avoid truncating the source file).
	 *
	 * @param string $filepath Target image path.
	 * @return string
	 */
	private static function getImageTempWritePath( $filepath ) {
		return $filepath . '.sheetspilot-compress-tmp';
	}

	/**
	 * Replace an image file with a validated temp write (non-empty temp required).
	 *
	 * @param string $filepath  Destination path.
	 * @param string $temp_path Temp file path.
	 * @return bool
	 */
	private static function replaceImageFileFromTemp( $filepath, $temp_path ) {
		if ( ! is_file( $temp_path ) || (int) filesize( $temp_path ) <= 0 ) {
			@unlink( $temp_path );
			return false;
		}

		if ( @rename( $temp_path, $filepath ) ) {
			return is_file( $filepath ) && (int) filesize( $filepath ) > 0;
		}

		if ( @copy( $temp_path, $filepath ) ) {
			@unlink( $temp_path );
			return is_file( $filepath ) && (int) filesize( $filepath ) > 0;
		}

		@unlink( $temp_path );
		return false;
	}

	/**
	 * @return bool
	 */
	private static function imagickSupportsWebp() {
		if ( ! class_exists( 'Imagick' ) ) {
			return false;
		}

		$formats = Imagick::queryFormats( 'WEBP' );
		return is_array( $formats ) && ! empty( $formats );
	}

	/**
	 * @return bool
	 */
	private static function gdSupportsWebp() {
		return function_exists( 'imagecreatetruecolor' )
			&& function_exists( 'imagecreatefromwebp' )
			&& function_exists( 'imagewebp' );
	}

	/**
	 * Imagick — lossless WebP with maximum compression effort.
	 *
	 * @param string $source Absolute path to source WebP.
	 * @param string $dest   Absolute path for output WebP.
	 * @return bool
	 */
	private static function compressWebpWithImagick( $source, $dest ) {
		if ( ! self::imagickSupportsWebp() ) {
			return false;
		}

		$image = self::loadImagickFromFile( $source );
		if ( $image === null ) {
			return false;
		}

		try {
			$image->setImageFilename( $dest );
			$image->setImageFormat( 'webp' );
			$image->setOption( 'webp:lossless', 'true' );
			$image->setOption( 'webp:method', '6' );
			$image->setOption( 'webp:alpha-quality', '100' );
			$image->stripImage();
			$written = $image->writeImage( $dest );
			$image->clear();
			$image->destroy();
		} catch ( Exception $e ) {
			@unlink( $dest );
			self::logLosslessCompressImageNote( 'imagick WebP write error (lossless): ' . $e->getMessage() );
			return false;
		}

		return ! empty( $written ) && is_file( $dest ) && (int) filesize( $dest ) > 0;
	}

	/**
	 * GD — lossless WebP (PHP 8.1+ IMG_WEBP_LOSSLESS flag).
	 *
	 * @param string $source Absolute path to source WebP.
	 * @param string $dest   Absolute path for output WebP.
	 * @return bool
	 */
	private static function compressWebpWithGd( $source, $dest ) {
		if ( ! self::gdSupportsWebp() ) {
			return false;
		}

		$image = @imagecreatefromwebp( $source );
		if ( $image === false ) {
			return false;
		}

		if ( function_exists( 'imagepalettetotruecolor' ) ) {
			imagepalettetotruecolor( $image );
		}
		imagesavealpha( $image, true );

		$quality = defined( 'IMG_WEBP_LOSSLESS' ) ? IMG_WEBP_LOSSLESS : 100;
		$ok      = imagewebp( $image, $dest, $quality );
		imagedestroy( $image );

		return $ok && is_file( $dest ) && (int) filesize( $dest ) > 0;
	}

	/**
	 * @return bool
	 */
	private static function imagickSupportsPng() {
		if ( ! class_exists( 'Imagick' ) ) {
			return false;
		}

		$formats = Imagick::queryFormats( 'PNG' );
		return is_array( $formats ) && ! empty( $formats );
	}

	/**
	 * @return bool
	 */
	private static function gdSupportsPng() {
		return function_exists( 'imagecreatefrompng' ) && function_exists( 'imagepng' );
	}

	/**
	 * Lossless PNG via Imagick using a temp file (never overwrite source in place).
	 *
	 * @param string $filepath Absolute path to the image.
	 * @return bool
	 */
	private static function losslessCompressPngImagick( $filepath, $backup_path = '' ) {
		$read_path = self::getCompressReadPath( $filepath, $backup_path );
		$temp_path = self::getImageTempWritePath( $filepath );
		@unlink( $temp_path );

		$image = self::loadImagickFromFile( $read_path );
		if ( $image === null ) {
			return false;
		}

		try {
			$image->setImageFilename( $temp_path );
			$image->setImageFormat( 'png' );
			$image->setImageCompression( Imagick::COMPRESSION_ZIP );
			$image->setOption( 'png:compression-level', (string) self::PNG_COMPRESSION_LEVEL );
			$image->stripImage();
			$written = $image->writeImage( $temp_path );
			$image->clear();
			$image->destroy();
		} catch ( Exception $e ) {
			@unlink( $temp_path );
			self::logLosslessCompressImageNote( 'imagick PNG write error: ' . $e->getMessage() );
			return false;
		}

		if ( empty( $written ) ) {
			@unlink( $temp_path );
			return false;
		}

		return self::replaceImageFileFromTemp( $filepath, $temp_path );
	}

	/**
	 * Re-encode PNG via GD at max compression using a temp file.
	 *
	 * @param string $filepath Absolute path to the image.
	 * @return bool
	 */
	private static function losslessCompressPngGd( $filepath ) {
		$temp_path = self::getImageTempWritePath( $filepath );
		@unlink( $temp_path );

		$image = @imagecreatefrompng( $filepath );
		if ( $image === false ) {
			return false;
		}

		imagealphablending( $image, false );
		imagesavealpha( $image, true );
		$written = @imagepng( $image, $temp_path, self::PNG_COMPRESSION_LEVEL );
		imagedestroy( $image );

		if ( ! $written ) {
			@unlink( $temp_path );
			self::logLosslessCompressImageNote( 'gd imagepng() returned false' );
			return false;
		}

		return self::replaceImageFileFromTemp( $filepath, $temp_path );
	}

	/**
	 * Re-encode PNG via WordPress image editor (fallback when direct Imagick/GD paths fail).
	 *
	 * @param WP_Image_Editor $editor   Image editor instance.
	 * @param string          $filepath Absolute path to the image.
	 * @return bool
	 */
	private static function losslessCompressPngWpEditor( $editor, $filepath ) {
		$temp_path = self::getImageTempWritePath( $filepath );
		@unlink( $temp_path );

		$editor->set_quality( self::PNG_COMPRESSION_LEVEL );
		$saved = $editor->save( $temp_path, 'image/png' );
		if ( is_wp_error( $saved ) ) {
			@unlink( $temp_path );
			self::logLosslessCompressImageNote( 'wp_image_editor PNG save error: ' . $saved->get_error_message() );
			return false;
		}

		$output_path = $temp_path;
		if ( is_array( $saved ) && ! empty( $saved['path'] ) && is_file( $saved['path'] ) ) {
			$output_path = $saved['path'];
		}

		$ok = self::replaceImageFileFromTemp( $filepath, $output_path );
		if ( $output_path !== $filepath && is_file( $output_path ) ) {
			@unlink( $output_path );
		}
		@unlink( $temp_path );

		return $ok;
	}

	/**
	 * Restore the compress target from backup when missing or empty (silent, no throw).
	 *
	 * @param string $filepath    Absolute path to the image.
	 * @param string $backup_path Sidecar backup path.
	 * @return bool True when the target exists and is non-empty afterward.
	 */
	private static function ensureCompressTargetFromBackup( $filepath, $backup_path ) {
		if ( ! is_string( $backup_path ) || $backup_path === '' || ! is_file( $backup_path ) ) {
			return is_file( $filepath ) && (int) filesize( $filepath ) > 0;
		}

		if ( ! is_file( $filepath ) || (int) filesize( $filepath ) <= 0 ) {
			if ( ! @copy( $backup_path, $filepath ) ) {
				return false;
			}
			self::logLosslessCompressImageNote(
				'restored missing compress target from backup (' . (int) filesize( $filepath ) . ' bytes)'
			);
		}

		return is_file( $filepath ) && (int) filesize( $filepath ) > 0;
	}

	/**
	 * Best path to read image bytes for Imagick/GD (prefer unchanged backup).
	 *
	 * @param string $filepath    Current image path.
	 * @param string $backup_path Sidecar backup path.
	 * @return string
	 */
	private static function getCompressReadPath( $filepath, $backup_path = '' ) {
		if ( is_string( $backup_path ) && $backup_path !== '' && is_file( $backup_path ) ) {
			return $backup_path;
		}

		return $filepath;
	}

	/**
	 * Load an image into Imagick from disk without binding/mutating the source file path.
	 *
	 * Using new Imagick( $path ) can unlink or truncate the source on some servers when a
	 * subsequent write fails (seen with WebP lossless on Cloudways). readImageBlob avoids that.
	 *
	 * @param string $path Absolute path to an image file.
	 * @return Imagick|null
	 */
	private static function loadImagickFromFile( $path ) {
		if ( ! is_string( $path ) || $path === '' || ! is_file( $path ) ) {
			return null;
		}

		$bytes = @file_get_contents( $path );
		if ( ! is_string( $bytes ) || $bytes === '' ) {
			return null;
		}

		try {
			$image = new Imagick();
			$image->readImageBlob( $bytes );
			return $image;
		} catch ( Exception $e ) {
			self::logLosslessCompressImageNote( 'imagick read error (' . basename( $path ) . '): ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Recreate the sidecar backup from the current target when Imagick consumed/deleted it.
	 *
	 * @param string $filepath    Absolute path to the image.
	 * @param string $backup_path Sidecar backup path.
	 * @return bool
	 */
	private static function recreateCompressBackupIfMissing( $filepath, $backup_path ) {
		if ( ! is_string( $backup_path ) || $backup_path === '' ) {
			return false;
		}

		if ( is_file( $backup_path ) && (int) filesize( $backup_path ) > 0 ) {
			return true;
		}

		if ( ! is_file( $filepath ) || (int) filesize( $filepath ) <= 0 ) {
			return false;
		}

		if ( ! @copy( $filepath, $backup_path ) ) {
			return false;
		}

		self::logLosslessCompressImageNote(
			'recreated missing sidecar backup from compress target (' . (int) filesize( $backup_path ) . ' bytes)'
		);

		return true;
	}

	/**
	 * Losslessly re-compress WebP using GD or Imagick.
	 *
	 * Reads from the sidecar backup when present. Replaces the target only when the
	 * encoded output is smaller than the source; otherwise the original is kept.
	 *
	 * @param string $filepath    Absolute path to the image.
	 * @param string $backup_path Sidecar backup path.
	 * @return bool True when an encoder ran (file may be unchanged if output was not smaller).
	 */
	private static function losslessCompressWebp( $filepath, $backup_path, $preferred_engine = '' ) {
		self::ensureCompressTargetFromBackup( $filepath, $backup_path );
		$read_path      = self::getCompressReadPath( $filepath, $backup_path );
		$original_bytes = (int) filesize( $read_path );
		if ( $original_bytes <= 0 ) {
			return false;
		}

		$temp_path = self::getImageTempWritePath( $filepath ) . '.' . bin2hex( random_bytes( 4 ) );
		@unlink( $temp_path );
		$method = null;

		if ( $preferred_engine === 'gd' ) {
			if ( self::compressWebpWithGd( $read_path, $temp_path ) ) {
				$method = 'gd';
			}
		} elseif ( $preferred_engine === 'imagick' ) {
			if ( self::compressWebpWithImagick( $read_path, $temp_path ) ) {
				$method = 'imagick';
			}
		} elseif ( self::compressWebpWithGd( $read_path, $temp_path ) ) {
			$method = 'gd';
		} elseif ( self::compressWebpWithImagick( $read_path, $temp_path ) ) {
			$method = 'imagick';
		} else {
			@unlink( $temp_path );
			return false;
		}

		$compressed_bytes = (int) filesize( $temp_path );
		if ( $compressed_bytes <= 0 ) {
			@unlink( $temp_path );
			return false;
		}

		if ( $compressed_bytes < $original_bytes ) {
			$ok = self::replaceImageFileFromTemp( $filepath, $temp_path );
			if ( ! $ok ) {
				@unlink( $temp_path );
				return false;
			}

			$saved = $original_bytes - $compressed_bytes;
			$pct   = round( ( $saved / $original_bytes ) * 100, 2 );
			self::logCompressImageAction( 'webp', $method, 'lossless, saved ' . $saved . ' bytes (' . $pct . '%)' );

			return true;
		}

		@unlink( $temp_path );
		self::logLosslessCompressImageNote(
			'WebP ' . $method . ' output not smaller (' . $compressed_bytes . ' vs ' . $original_bytes . ' bytes), keeping original'
		);

		return true;
	}

	/**
	 * Re-encode WebP via WordPress image editor (fallback when direct Imagick/GD paths fail).
	 *
	 * @param WP_Image_Editor $editor   Image editor instance.
	 * @param string          $filepath Absolute path to the image.
	 * @return bool
	 */
	private static function losslessCompressWebpWpEditor( $editor, $filepath ) {
		$temp_path = self::getImageTempWritePath( $filepath );
		@unlink( $temp_path );

		$editor->set_quality( 100 );
		$saved = $editor->save( $temp_path, 'image/webp' );
		if ( is_wp_error( $saved ) ) {
			@unlink( $temp_path );
			self::logLosslessCompressImageNote( 'wp_image_editor WebP save error: ' . $saved->get_error_message() );
			return false;
		}

		$output_path = $temp_path;
		if ( is_array( $saved ) && ! empty( $saved['path'] ) && is_file( $saved['path'] ) ) {
			$output_path = $saved['path'];
		}

		$ok = self::replaceImageFileFromTemp( $filepath, $output_path );
		if ( $output_path !== $filepath && is_file( $output_path ) ) {
			@unlink( $output_path );
		}
		@unlink( $temp_path );

		return $ok;
	}

	/**
	 * Losslessly compress an image file in place.
	 *
	 * Rewrites the file on disk with metadata stripped and format-specific lossless
	 * settings (max PNG/WebP deflate, high-quality JPEG). Used before media insert so
	 * AI-generated images are smaller without visible quality loss.
	 *
	 * Flow:
	 * 1. Validate file and resolve format (jpg|png|webp).
	 * 2. Detect available engine via WordPress image editor (Imagick preferred, GD fallback).
	 * 3. Copy original to a sidecar backup ({filepath}.sheetspilot-compress-backup).
	 * 4. Compress in place with the first matching engine for the format.
	 * 5. Verify output is non-empty; on failure restore backup and return false.
	 * 6. On success remove the sidecar backup.
	 *
	 * Compression by format:
	 * - PNG  — Imagick / GD / WP image editor; temp-file write to avoid zero-byte overwrites.
	 * - WebP — GD (lossless) / Imagick (lossless);
	 *          always written via temp file; original kept when output is not smaller.
	 * - JPEG — Imagick or GD re-encode at LOSSLESS_JPEG_QUALITY (metadata stripped).
	 *
	 * @param string      $filepath Absolute path to the image.
	 * @param string|null $ext      Optional format hint (jpg|png|webp); auto-detected when omitted.
	 * @return bool True when compression ran and produced a valid file; false when skipped
	 *              (missing file, unknown format, no supported engine, backup failure,
	 *              or compression yields a zero-byte / missing file — original is restored).
	 */
	private static function losslessCompressImage( $filepath, $ext = null, $preferred_engine = '' ) {

		// --- Early exit: file must exist on disk ---
		if ( ! is_file( $filepath ) ) {
			return false;
		}

		$preferred_engine = in_array( $preferred_engine, array( 'gd', 'imagick' ), true ) ? $preferred_engine : '';

		// --- Resolve target format (caller hint or mime/extension detection) ---
		if ( $ext === null || $ext === '' ) {
			$ext = self::detectImageFormatExt( $filepath );
		}
		if ( $ext === 'jpeg' ) {
			$ext = 'jpg';
		}

		self::$lastCompressEngine = '';

		$format_label = ( $ext !== '' ) ? $ext : 'unknown';
		$size_before  = (int) filesize( $filepath );
		self::logLosslessCompressImageNote(
			'format ' . $format_label . ', size before compress: ' . $size_before . ' bytes'
		);

		// --- Pick compression engine (Imagick when available, otherwise GD) ---
		self::beginPreferredCompressEngine( $preferred_engine );
		$editor = wp_get_image_editor( $filepath );
		if ( is_wp_error( $editor ) ) {
			self::endPreferredCompressEngine();
			return false;
		}

		$is_imagick = ( $editor instanceof WP_Image_Editor_Imagick );
		$is_gd      = ( $editor instanceof WP_Image_Editor_GD );

		if ( $preferred_engine === 'gd' ) {
			$is_imagick = false;
			$is_gd      = self::isGdExtensionAvailable();
		} elseif ( $preferred_engine === 'imagick' ) {
			$is_gd      = false;
			$is_imagick = self::isImagickExtensionAvailable();
		}

		// --- Safety: backup original before any in-place write ---
		$backup_path = self::createCompressBackup( $filepath );
		if ( $backup_path === '' ) {
			self::endPreferredCompressEngine();
			return false;
		}
		$compressed  = false;

		// --- Format-specific lossless compression (first matching engine wins) ---
		if ( $ext === 'png' ) {
			if ( $preferred_engine !== 'gd' && $is_imagick && self::imagickSupportsPng() ) {
				if ( self::losslessCompressPngImagick( $filepath, $backup_path ) ) {
					self::logCompressImageAction( 'png', 'imagick', 'lossless level ' . self::PNG_COMPRESSION_LEVEL );
					$compressed = true;
				}
			}
			if ( ! $compressed && $preferred_engine !== 'imagick' && $is_gd && self::gdSupportsPng() ) {
				if ( self::losslessCompressPngGd( $filepath ) ) {
					self::logCompressImageAction( 'png', 'gd', 'lossless level ' . self::PNG_COMPRESSION_LEVEL );
					$compressed = true;
				}
			}
			if ( ! $compressed && $preferred_engine === '' && ! is_wp_error( $editor ) ) {
				if ( self::losslessCompressPngWpEditor( $editor, $filepath ) ) {
					self::logCompressImageAction( 'png', 'wp_image_editor', 'level ' . self::PNG_COMPRESSION_LEVEL );
					$compressed = true;
				}
			}
			if ( ! $compressed ) {
				self::logLosslessCompressImageNote(
					'no working PNG compress engine on this server (imagick png=' . ( self::imagickSupportsPng() ? 'yes' : 'no' )
					. ', gd png=' . ( self::gdSupportsPng() ? 'yes' : 'no' ) . ')'
				);
			}
		} elseif ( $ext === 'webp' ) {
			if ( self::losslessCompressWebp( $filepath, $backup_path, $preferred_engine ) ) {
				$compressed = true;
			}
			if ( ! $compressed && $preferred_engine === '' && ! is_wp_error( $editor ) ) {
				if ( self::losslessCompressWebpWpEditor( $editor, $filepath ) ) {
					self::logCompressImageAction( 'webp', 'wp_image_editor', 'quality 100' );
					$compressed = true;
				}
			}
			if ( ! $compressed ) {
				self::logLosslessCompressImageNote(
					'no working WebP compress engine on this server (gd webp=' . ( self::gdSupportsWebp() ? 'yes' : 'no' )
					. ', imagick webp=' . ( self::imagickSupportsWebp() ? 'yes' : 'no' ) . ')'
				);
			}
		} elseif ( in_array( $ext, array( 'jpg', 'jpeg' ), true ) ) {
			$jpeg_quality = self::LOSSLESS_JPEG_QUALITY;

			if ( $preferred_engine !== 'gd' && $is_imagick && class_exists( 'Imagick' ) ) {
				$image = new Imagick( $filepath );
				$image->setImageCompression( Imagick::COMPRESSION_JPEG );
				$image->setImageCompressionQuality( $jpeg_quality );
				$image->stripImage();
				$image->writeImage( $filepath );
				$image->clear();
				$image->destroy();

				self::logCompressImageAction( 'jpg', 'imagick', 'quality ' . $jpeg_quality );
				$compressed = true;
			} elseif ( $preferred_engine !== 'imagick' && $is_gd && function_exists( 'imagecreatefromjpeg' ) ) {
				$image = @imagecreatefromjpeg( $filepath );
				if ( $image !== false ) {
					imagejpeg( $image, $filepath, $jpeg_quality );
					imagedestroy( $image );

					self::logCompressImageAction( 'jpg', 'gd', 'quality ' . $jpeg_quality );
					$compressed = true;
				}
			}
		}

		// --- No engine ran: discard unused backup and leave file unchanged ---
		if ( ! $compressed ) {
			self::removeCompressBackup( $backup_path );
			self::endPreferredCompressEngine();
			return false;
		}

		// --- Validate output size; restore original when compression yields an empty file ---
		if ( ! self::assertValidCompressedImage( $filepath, $backup_path, $format_label ) ) {
			self::endPreferredCompressEngine();
			return false;
		}

		$size_after = is_file( $filepath ) ? (int) filesize( $filepath ) : 0;
		self::logLosslessCompressImageNote(
			'format ' . $format_label . ', size after compress: ' . $size_after . ' bytes'
		);

		self::endPreferredCompressEngine();

		return true;
	}

	/**
	 * Restrict WordPress image editor choice while a preferred engine is active.
	 *
	 * @param string[] $editors Editor class names.
	 * @return string[]
	 */
	public static function filterWpImageEditorsForCompress( $editors ) {
		if ( self::$compressPreferredEngine === 'gd' ) {
			return array( 'WP_Image_Editor_GD' );
		}
		if ( self::$compressPreferredEngine === 'imagick' ) {
			return array( 'WP_Image_Editor_Imagick' );
		}

		return $editors;
	}

	/**
	 * Begin forcing a preferred compression engine for wp_get_image_editor().
	 *
	 * @param string $preferred_engine gd|imagick|''.
	 */
	private static function beginPreferredCompressEngine( $preferred_engine ) {
		self::$compressPreferredEngine = in_array( $preferred_engine, array( 'gd', 'imagick' ), true ) ? $preferred_engine : '';
		if ( self::$compressPreferredEngine !== '' ) {
			add_filter( 'wp_image_editors', array( __CLASS__, 'filterWpImageEditorsForCompress' ), 999 );
		}
	}

	/**
	 * Stop forcing a preferred compression engine.
	 */
	private static function endPreferredCompressEngine() {
		if ( self::$compressPreferredEngine !== '' ) {
			remove_filter( 'wp_image_editors', array( __CLASS__, 'filterWpImageEditorsForCompress' ), 999 );
		}
		self::$compressPreferredEngine = '';
	}

	/**
	 * Normalize a Prompt Tester engine choice against installed libraries.
	 *
	 * @param string $preferred_engine Requested engine (auto|gd|imagick).
	 * @param array  $library_status   Output from getCompressionLibraryStatus().
	 * @return string gd|imagick|'' (auto).
	 */
	private static function normalizeCompressPreferredEngine( $preferred_engine, $library_status ) {
		$preferred_engine = sanitize_key( (string) $preferred_engine );
		if ( $preferred_engine === '' || $preferred_engine === 'auto' ) {
			return '';
		}

		$gd_installed      = ! empty( $library_status['gd_installed'] );
		$imagick_installed = ! empty( $library_status['imagick_installed'] );

		if ( $preferred_engine === 'gd' ) {
			if ( ! $gd_installed ) {
				SheetsPilotFunctions::throwError( __( 'GD is not installed on this server.', 'sheetspilot' ) );
			}
			return 'gd';
		}

		if ( $preferred_engine === 'imagick' ) {
			if ( ! $imagick_installed ) {
				SheetsPilotFunctions::throwError( __( 'Imagick is not installed on this server.', 'sheetspilot' ) );
			}
			return 'imagick';
		}

		SheetsPilotFunctions::throwError( __( 'Invalid compression library selection.', 'sheetspilot' ) );
	}

	/**
	 * Resolve a media attachment ID from an editor table snapshot.
	 *
	 * @param array $table_data Client table payload.
	 * @return int Attachment ID or 0 when none.
	 */
	public static function resolveAttachmentIdFromTableData( $table_data ) {
		$attachment_id = 0;

		if ( ! empty( $table_data['imageAttachmentId'] ) ) {
			$attachment_id = absint( $table_data['imageAttachmentId'] );
		}

		$current_value = isset( $table_data['value'] ) ? (string) $table_data['value'] : '';
		if ( $current_value !== '' ) {
			if ( preg_match( '/^\d+$/', trim( $current_value ) ) ) {
				$attachment_id = absint( trim( $current_value ) );
			} elseif ( preg_match( '/data-id\s*=\s*["\'](\d+)["\']/i', $current_value, $id_m ) ) {
				$attachment_id = absint( $id_m[1] );
			}
		}

		if ( ! empty( $table_data['imageIsPlaceholder'] ) && $attachment_id <= 0 ) {
			return 0;
		}

		if ( $attachment_id > 0 && get_post_type( $attachment_id ) !== 'attachment' ) {
			return 0;
		}

		return $attachment_id;
	}

	/**
	 * Whether the PHP GD extension is available (same criteria as WP_Image_Editor_GD::test).
	 *
	 * @return bool
	 */
	private static function isGdExtensionAvailable() {
		return extension_loaded( 'gd' ) && function_exists( 'gd_info' );
	}

	/**
	 * Whether the PHP Imagick extension is available for image processing.
	 *
	 * @return bool
	 */
	private static function isImagickExtensionAvailable() {
		if ( ! extension_loaded( 'imagick' ) || ! class_exists( 'Imagick', false ) ) {
			return false;
		}

		$version = phpversion( 'imagick' );
		if ( $version !== false && version_compare( $version, '2.2.0', '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Whether GD and/or Imagick are available for image compression.
	 *
	 * @return array{gd_installed:bool,imagick_installed:bool,has_any:bool,wp_preferred:string}
	 */
	public static function getCompressionLibraryStatus() {
		$gd_installed      = self::isGdExtensionAvailable();
		$imagick_installed = self::isImagickExtensionAvailable();
		$wp_preferred      = '';

		if ( $imagick_installed ) {
			$wp_preferred = 'imagick';
		} elseif ( $gd_installed ) {
			$wp_preferred = 'gd';
		}

		return array(
			'gd_installed'      => $gd_installed,
			'imagick_installed' => $imagick_installed,
			'has_any'           => ( $gd_installed || $imagick_installed ),
			'wp_preferred'      => $wp_preferred,
		);
	}

	/**
	 * Copy an attachment file to uploads for a Prompt Tester before-preview URL.
	 *
	 * @param string $filepath      Source file path.
	 * @param int    $attachment_id Attachment post ID.
	 * @param string $ext           File extension without dot.
	 * @return string Public URL to the copied file.
	 */
	private static function createPromptTesterBeforePreviewUrl( $filepath, $attachment_id, $ext ) {
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			SheetsPilotFunctions::throwError( __( 'Upload directory is not writable.', 'sheetspilot' ) );
		}

		$safe_ext = preg_replace( '/[^a-z0-9]/i', '', (string) $ext );
		if ( $safe_ext === '' ) {
			$safe_ext = 'jpg';
		}

		$filename = 'sheetspilot-compress-before-' . absint( $attachment_id ) . '-' . wp_generate_password( 8, false ) . '.' . $safe_ext;
		$dest     = trailingslashit( $upload_dir['path'] ) . $filename;

		if ( ! @copy( $filepath, $dest ) ) {
			SheetsPilotFunctions::throwError( __( 'Failed to create before-preview copy.', 'sheetspilot' ) );
		}

		return trailingslashit( $upload_dir['url'] ) . $filename;
	}

	/**
	 * Compress an attachment for the Prompt Tester and return before/after preview data.
	 *
	 * @param int    $attachment_id    Attachment post ID.
	 * @param string $preferred_engine Optional gd|imagick|auto.
	 * @return array<string,mixed>
	 */
	public static function compressAttachmentImageForPromptTester( $attachment_id, $preferred_engine = '' ) {
		$library_status = self::getCompressionLibraryStatus();
		if ( empty( $library_status['has_any'] ) ) {
			SheetsPilotFunctions::throwError(
				__( 'No image library is installed. Please install GD or Imagick (ImageMagick) on your server.', 'sheetspilot' )
			);
		}

		$preferred_engine = self::normalizeCompressPreferredEngine( $preferred_engine, $library_status );

		$attachment_id = absint( $attachment_id );
		if ( $attachment_id <= 0 ) {
			SheetsPilotFunctions::throwError( __( 'No image attachment found.', 'sheetspilot' ) );
		}

		$filepath = get_attached_file( $attachment_id );
		if ( ! is_string( $filepath ) || $filepath === '' || ! is_file( $filepath ) ) {
			SheetsPilotFunctions::throwError( __( 'Attachment file not found on disk.', 'sheetspilot' ) );
		}

		$ext = self::detectImageFormatExt( $filepath );
		if ( $ext === 'jpeg' ) {
			$ext = 'jpg';
		}
		if ( $ext === '' ) {
			SheetsPilotFunctions::throwError( __( 'Unsupported image format.', 'sheetspilot' ) );
		}

		$size_before = (int) filesize( $filepath );
		$before_url  = self::createPromptTesterBeforePreviewUrl( $filepath, $attachment_id, $ext );

		self::$lastCompressEngine = '';
		$result                   = self::compressAttachmentImage( $attachment_id, $preferred_engine );

		$after_url = self::resolveAttachmentThumbnailUrl( $attachment_id, 'full' );
		if ( $after_url === '' && ! empty( $result['thumbnail_url'] ) ) {
			$after_url = (string) $result['thumbnail_url'];
		}
		if ( $after_url === '' && is_string( $before_url ) && $before_url !== '' ) {
			$after_url = $before_url;
		}
		if ( ! empty( $result['cache_bust'] ) && $after_url !== '' ) {
			$after_url = add_query_arg( 'v', (int) $result['cache_bust'], $after_url );
		}

		$engine      = self::$lastCompressEngine !== '' ? self::$lastCompressEngine : 'none';
		$size_after  = isset( $result['size_after'] ) ? (int) $result['size_after'] : (int) filesize( $filepath );
		$size_saved  = max( 0, $size_before - $size_after );

		return array(
			'before_url'        => $before_url,
			'after_url'         => $after_url,
			'size_before'       => $size_before,
			'size_after'        => $size_after,
			'size_saved'        => $size_saved,
			'engine'            => $engine,
			'preferred_engine'  => $preferred_engine !== '' ? $preferred_engine : 'auto',
			'attachment_id'     => $attachment_id,
			'library_status'    => $library_status,
		);
	}

	/**
	 * Losslessly compress an existing attachment in place and regenerate thumbnails.
	 *
	 * @param int    $attachment_id    Attachment post ID.
	 * @param string $preferred_engine Optional gd|imagick|'' for auto.
	 * @return array Preview metadata for the client (same attachment ID and URLs).
	 */
	public static function compressAttachmentImage( $attachment_id, $preferred_engine = '' ) {
		$attachment_id = absint( $attachment_id );
		if ( $attachment_id <= 0 ) {
			SheetsPilotFunctions::throwError( __( 'No image attachment found.', 'sheetspilot' ) );
		}

		$filepath = get_attached_file( $attachment_id );
		if ( ! is_string( $filepath ) || $filepath === '' || ! is_file( $filepath ) ) {
			SheetsPilotFunctions::throwError( __( 'Attachment file not found on disk.', 'sheetspilot' ) );
		}

		$size_before = (int) filesize( $filepath );
		$ext         = self::detectImageFormatExt( $filepath );
		if ( $ext === 'jpeg' ) {
			$ext = 'jpg';
		}

		if ( $ext !== '' ) {
			self::losslessCompressImage( $filepath, $ext, $preferred_engine );
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$attach_data = wp_generate_attachment_metadata( $attachment_id, $filepath );
		if ( is_array( $attach_data ) && ! empty( $attach_data ) ) {
			wp_update_attachment_metadata( $attachment_id, $attach_data );
		}

		$thumbnail_url = self::resolveAttachmentThumbnailUrl( $attachment_id, 'medium' );

		$response                = self::buildPromotePendingResponse( $attachment_id, $thumbnail_url );
		$response['size_before'] = $size_before;
		$response['size_after']  = (int) filesize( $filepath );
		$response['cache_bust']  = (int) @filemtime( $filepath );
		$response['engine']      = self::$lastCompressEngine !== '' ? self::$lastCompressEngine : 'none';

		return $response;
	}

	/**
	 * Prepare an AI-generated image for WordPress media library insert.
	 *
	 * Runs the full pre-insert pipeline on a file already saved to disk:
	 * lossless compression, optional large PNG/WebP → JPEG conversion, then a
	 * final size guard. The returned path may differ from the input when a
	 * large PNG/WebP is converted to .jpg.
	 *
	 * Flow:
	 * 1. Validate path and log original file size (prompt request metadata).
	 * 2. Detect format (jpg|png|webp) and run losslessCompressImage() in place.
	 * 3. If PNG/WebP exceeds LARGE_PNG_TO_JPEG_BYTES (800 KB), convert to JPEG.
	 * 4. When conversion happened, lossless-compress the new JPEG as well.
	 * 5. Log final size; return original path when the file is missing or zero bytes.
	 *
	 * @param string $filepath Absolute path to the image file.
	 * @return string Path to use for attachment insert (unchanged, or .jpg after conversion).
	 *              Returns the input unchanged when the file is missing or invalid.
	 */
	public static function compressImageForMedia( $filepath ) {

		// --- Early exit: nothing to process ---
		if ( ! is_string( $filepath ) || $filepath === '' || ! is_file( $filepath ) ) {
			return $filepath;
		}

		// --- Log pipeline start size (overall, before any step) ---
		$size_before = (int) filesize( $filepath );
		SheetsPilot_Prompts::addNoteToLastPromptRequestMetadata(
			'image size before compress: ' . $size_before . ' bytes'
		);

		$last_good_filepath = $filepath;

		// --- Step 1: lossless compress in original format ---
		$ext = self::detectImageFormatExt( $filepath );
		if ( $ext === 'jpeg' ) {
			$ext = 'jpg';
		}

		if ( $ext !== '' ) {
			self::losslessCompressImage( $filepath, $ext );
		}
		$valid_path = self::rememberValidImagePath( $filepath );
		if ( $valid_path ) {
			$last_good_filepath = $valid_path;
		}

		// --- Step 2: convert oversized PNG/WebP to JPEG (path may change) ---
		$filepath_before_convert = $filepath;
		$filepath = self::convertLargePngOrWebpToJpegBeforeInsert( $filepath );
		$valid_path = self::rememberValidImagePath( $filepath );
		if ( $valid_path ) {
			$last_good_filepath = $valid_path;
		}

		// --- Step 3: compress the new JPEG when conversion ran ---
		if ( $filepath !== $filepath_before_convert ) {
			SheetsPilot_Prompts::addNoteToLastPromptRequestMetadata(
				'image converted to JPEG (over 800 KB): ' . basename( $filepath )
			);
			self::losslessCompressImage( $filepath, 'jpg' );
			$valid_path = self::rememberValidImagePath( $filepath );
			if ( $valid_path ) {
				$last_good_filepath = $valid_path;
			}
		}

		// --- Final guard: file must exist and be non-empty ---
		$size_after = is_file( $filepath ) ? (int) filesize( $filepath ) : 0;

		SheetsPilot_Prompts::addNoteToLastPromptRequestMetadata(
			'image size after compress: ' . $size_after . ' bytes'
		);

		if ( $size_after <= 0 ) {
			self::logLosslessCompressImageNote( 'compress pipeline ended with empty file; keeping last known good file' );
			if ( is_file( $last_good_filepath ) && (int) filesize( $last_good_filepath ) > 0 ) {
				return $last_good_filepath;
			}
		}

		return $filepath;
	}

	/**
	 * Append a losslessCompressImage note to the last prompt request metadata.
	 *
	 * @param string $message Log detail (format, sizes, restore status, etc.).
	 */
	private static function logLosslessCompressImageNote( $message ) {
		SheetsPilot_Prompts::addNoteToLastPromptRequestMetadata( 'losslessCompressImage: ' . $message );
	}

	/**
	 * Append a compress-image action note to the last prompt request metadata.
	 *
	 * @param string $format jpg|png|webp.
	 * @param string $engine imagick|gd.
	 * @param string $detail Optional extra detail.
	 */
	private static function logCompressImageAction( $format, $engine, $detail = '' ) {
		self::$lastCompressEngine = (string) $engine;
		$message = 'compressImageForMedia ' . $format . ': engine ' . $engine;
		if ( $detail !== '' ) {
			$message .= ', ' . $detail;
		}
		SheetsPilot_Prompts::addNoteToLastPromptRequestMetadata( $message );
	}

	/**
	 * Append a changeImageQuality action note to the last prompt request metadata.
	 *
	 * @param string $format jpg|png|webp.
	 * @param string $level  low|medium|high.
	 * @param string $engine imagick|gd|wp_image_editor.
	 * @param string $detail Optional extra detail (numeric quality, compression, etc.).
	 */
	private static function logChangeImageQualityAction( $format, $level, $engine, $detail = '' ) {
		$message = 'changeImageQuality ' . $format . ': level ' . $level . ', engine ' . $engine;
		if ( $detail !== '' ) {
			$message .= ', ' . $detail;
		}
		SheetsPilot_Prompts::addNoteToLastPromptRequestMetadata( $message );
	}

	/**
	 * change image quality
	 */
	public static function changeImageQuality($filepath, $level = 'medium', $target_format = null)
	{
		$editor = wp_get_image_editor($filepath);

		$is_imagick = ($editor instanceof WP_Image_Editor_Imagick);
		$is_gd      = ($editor instanceof WP_Image_Editor_GD);
 
		if (is_wp_error($editor)) {
			return $editor;
		}

		$level = self::resolveApiImageQuality( $level );
		if ( $level === 'auto' ) {
			$level = 'medium';
		}

		$ext = $target_format ? strtolower( (string) $target_format ) : self::detectImageFormatExt( $filepath );
		if ( $ext === 'jpeg' ) {
			$ext = 'jpg';
		}

		// Quality level mapping.
		$quality_map = [
			'low'    => 40,
			'medium' => 70,
			'high'   => 90,
		];

		$quality = $quality_map[ $level ] ?? $quality_map['medium'];

		// JPEG — re-encode directly (WP_Image_Editor::save() alone often leaves size unchanged).
		if ( in_array( $ext, array( 'jpg', 'jpeg' ), true ) ) {
			if ( $is_imagick && class_exists( 'Imagick' ) ) {
				$image = new Imagick( $filepath );
				$image->setImageCompression( Imagick::COMPRESSION_JPEG );
				$image->setImageCompressionQuality( $quality );
				$image->stripImage();
				$image->writeImage( $filepath );
				$image->clear();
				$image->destroy();

				self::logChangeImageQualityAction( 'jpg', $level, 'imagick', 'quality ' . $quality );

				return true;
			}

			if ( $is_gd && function_exists( 'imagecreatefromjpeg' ) ) {
				$image = @imagecreatefromjpeg( $filepath );
				if ( $image !== false ) {
					$gd_quality = max( 10, $quality - 10 );
					imagejpeg( $image, $filepath, $gd_quality );
					imagedestroy( $image );

					self::logChangeImageQualityAction( 'jpg', $level, 'gd', 'quality ' . $gd_quality );

					return true;
				}
			}

			$loaded = $editor->load();
			if ( ! is_wp_error( $loaded ) ) {
				$editor->set_quality( $quality );

				self::logChangeImageQualityAction( 'jpg', $level, 'wp_image_editor', 'quality ' . $quality );

				return $editor->save( $filepath );
			}
		}

		// webp processing
		if ($ext === 'webp') {
			if ($is_imagick) {
				$image = new Imagick($filepath);

				if ($level == 'low') {
					// Strong lossy compression.
					$webp_quality = 40;
					$image->setImageCompressionQuality( $webp_quality );
				} elseif ($level == 'medium') {
					// Medium compression.
					$webp_quality = 70;
					$image->setImageCompressionQuality( $webp_quality );
				} else {
					// High quality.
					$webp_quality = 85;
					$image->setImageCompressionQuality( $webp_quality );
				}

				$image->setOption('webp:method', '6');
				$image->stripImage();
				$image->writeImage($filepath);
				$image->clear();
				$image->destroy();

				self::logChangeImageQualityAction( 'webp', $level, 'imagick', 'quality ' . $webp_quality );

				return true;
			} elseif ($is_gd) {
	 
				$image = imagecreatefromwebp($filepath);

				// Preserve transparency.
				imagealphablending($image, false);
				imagesavealpha($image, true);

				if ($level == 'low') {
					$new_quality = 20;
				} elseif ($level == 'medium') {
					$new_quality = 60;
				} else {
					$new_quality = 100;
				}

				imagewebp($image, $filepath, $new_quality);
				imagedestroy($image);

				self::logChangeImageQualityAction( 'webp', $level, 'gd', 'quality ' . $new_quality );

				return true;
			}
		}


		if ($ext === 'png') {

			$compression_map = [
				'low'    => 1,
				'medium' => 5,
				'high'   => 9,
			];

			$compression = $compression_map[$level] ?? 6;

			if ($editor instanceof WP_Image_Editor_Imagick) {

				$image = new Imagick($filepath);
				// Set compression and quality
				if ($level == 'low') {
					$png_colors = 32;
					$image->quantizeImage( $png_colors, Imagick::COLORSPACE_SRGB, 0, false, false );
				} elseif ($level == 'medium') {
					$png_colors = 128;
					$image->quantizeImage( $png_colors, Imagick::COLORSPACE_SRGB, 0, false, false );
				} else {
					$png_colors = 256;
					$image->quantizeImage( $png_colors, Imagick::COLORSPACE_SRGB, 0, false, false );
				}

				$image->setImageCompression(Imagick::COMPRESSION_ZIP);
				$image->setImageCompressionQuality(95);

				// Preserve size and format while reducing quality
				$image->stripImage();

				$image->writeImage($filepath);
				$image->clear();
				$image->destroy();

				self::logChangeImageQualityAction( 'png', $level, 'imagick', 'colors ' . $png_colors . ', compression ' . $compression );

				return;
			} elseif ($editor instanceof WP_Image_Editor_GD) {

				$image = imagecreatefrompng($filepath);

				imagealphablending($image, false);
				imagesavealpha($image, true);

				// Reduce color count (quantize).
				if ($level == 'low') {
					$png_colors = 32;
					imagetruecolortopalette($image, true, $png_colors);
				} elseif ($level == 'medium') {
					$png_colors = 128;
					imagetruecolortopalette($image, true, $png_colors);
				} else {
					// For PNG, 256 is the palette maximum in GD.
					$png_colors = 256;
					imagetruecolortopalette($image, true, $png_colors);
				}

				imagepng($image, $filepath, 9);
				imagedestroy($image);

				self::logChangeImageQualityAction( 'png', $level, 'gd', 'colors ' . $png_colors . ', compression ' . $compression );

				return;
			}
		}

		$new_path = $filepath;

		if ($target_format) {
			$new_path = preg_replace('/\.\w+$/', '.' . $target_format, $filepath);
		}

		$fallback_format = $ext !== '' ? $ext : 'unknown';
		self::logChangeImageQualityAction( $fallback_format, $level, 'wp_image_editor', 'quality ' . $quality );

		return $editor->save($new_path);
	}

	
	/**
	 * Convert PNG or WebP images over 800 KB to high-quality JPEG.
	 *
	 * @param string $filepath Absolute path to the image.
	 * @return string Updated filepath (extension may change to .jpg).
	 */
	public static function convertLargePngOrWebpToJpegBeforeInsert( $filepath ) {
		
		if ( ! is_string( $filepath ) || $filepath === '' || ! is_file( $filepath ) ) {
			return $filepath;
		}

		if ( (int) filesize( $filepath ) <= self::LARGE_PNG_TO_JPEG_BYTES ) {
			return $filepath;
		}

		$info = wp_check_filetype( $filepath );
		$ext  = strtolower( (string) ( $info['ext'] ?? '' ) );
		$mime = strtolower( (string) ( $info['type'] ?? '' ) );

		$is_png  = ( $ext === 'png' || $mime === 'image/png' );
		$is_webp = ( $ext === 'webp' || $mime === 'image/webp' );

		if ( ! $is_png && ! $is_webp ) {
			return $filepath;
		}

		$editor = wp_get_image_editor( $filepath );
		if ( is_wp_error( $editor ) ) {
			return $filepath;
		}

		$editor->set_quality( self::LOSSLESS_JPEG_QUALITY );

		$jpg_path = preg_replace( '/\.(png|webp)$/i', '.jpg', $filepath );
		if ( $jpg_path === $filepath ) {
			$jpg_path = $filepath . '.jpg';
		}

		$saved = $editor->save( $jpg_path, 'image/jpeg' );
		if ( is_wp_error( $saved ) ) {
			return $filepath;
		}

		$new_path = ( is_array( $saved ) && ! empty( $saved['path'] ) ) ? $saved['path'] : $jpg_path;
		if ( ! is_file( $new_path ) ) {
			return $filepath;
		}

		if ( $new_path !== $filepath && is_file( $filepath ) ) {
			$pathinfo   = pathinfo( $filepath );
			$thumb_orig = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_thumb.' . $pathinfo['extension'];

			wp_delete_file( $filepath );
			if ( is_file( $thumb_orig ) ) {
				wp_delete_file( $thumb_orig );
			}
		}

		return $new_path;
	}

	/**
	 * @deprecated Use convertLargePngOrWebpToJpegBeforeInsert().
	 */
	public static function convertLargePngToJpegBeforeInsert( $filepath ) {
		return self::convertLargePngOrWebpToJpegBeforeInsert( $filepath );
	}

	/**
	 * Delete pending image files (full + thumb) and transient.
	 *
	 * @param string $request_id Request ID.
	 * @return bool True if deleted or already missing.
	 */
	public static function deletePending($request_id)
	{

		$meta = get_transient(self::TRANSIENT_PREFIX . $request_id);
		if (is_array($meta)) {
			if (! empty($meta['path']) && is_file($meta['path'])) {
				@unlink($meta['path']);
			}
			if (! empty($meta['thumb_path']) && is_file($meta['thumb_path'])) {
				@unlink($meta['thumb_path']);
			}
			if (! empty($meta['source_backup_path']) && is_file($meta['source_backup_path'])) {
				@unlink($meta['source_backup_path']);
			}
		}
		delete_transient(self::TRANSIENT_PREFIX . $request_id);
		delete_transient(self::PENDING_CONTEXT_PREFIX . $request_id);
		delete_transient(self::PENDING_RETRIED_PREFIX . $request_id);
		return true;

	}

	/**
	 * Resolve pending metadata for promote: transient, on-disk file, or one regeneration attempt.
	 *
	 * @param string $request_id Request ID.
	 * @param int    $post_id    Post ID.
	 * @param string $column     Column name.
	 * @return array|null
	 */
	private static function resolvePromotePendingMeta( $request_id, $post_id, $column ) {
		$meta = self::getPending( $request_id );
		if ( $meta ) {
			return $meta;
		}

		$meta = self::resolvePendingMetaFromDisk( $request_id, $post_id, $column );
		if ( $meta ) {
			return $meta;
		}

		return self::retryPendingImageGenerationOnce( $request_id, $post_id, $column );
	}

	/**
	 * Rebuild pending metadata when the file still exists but the transient expired.
	 *
	 * @param string $request_id Request ID.
	 * @param int    $post_id    Post ID.
	 * @param string $column     Column name.
	 * @return array|null
	 */
	private static function resolvePendingMetaFromDisk( $request_id, $post_id, $column ) {
		$request_id = sanitize_file_name( (string) $request_id );
		if ( $request_id === '' ) {
			return null;
		}

		$dir = self::getPendingDir();
		foreach ( array( 'png', 'jpg', 'jpeg', 'webp' ) as $ext ) {
			$filepath = $dir . '/' . $request_id . '.' . $ext;
			if ( ! is_file( $filepath ) || (int) filesize( $filepath ) <= 0 ) {
				continue;
			}

			$thumb_ext = ( $ext === 'jpeg' ) ? 'jpg' : $ext;
			$thumb_path = $dir . '/' . $request_id . '_thumb.' . $thumb_ext;
			if ( ! is_file( $thumb_path ) ) {
				$thumb_path = self::createThumbnail( $filepath, $dir, $request_id, $thumb_ext );
			}

			$source_backup_path = $dir . '/' . $request_id . '_source.' . ( ( $ext === 'jpeg' ) ? 'jpg' : $ext );
			if ( ! is_file( $source_backup_path ) ) {
				$source_backup_path = '';
			}

			$baseurl     = self::getPendingUrl();
			$preview_url = self::resolvePendingPreviewUrl( $baseurl, $thumb_path, $filepath, $source_backup_path );
			if ( $preview_url === '' ) {
				continue;
			}

			$file_meta = self::probePendingImageFileMeta(
				array(
					$thumb_path,
					$filepath,
					$source_backup_path,
				)
			);

			$meta = array(
				'path'               => $filepath,
				'thumb_path'         => $thumb_path,
				'source_backup_path' => $source_backup_path,
				'url'                => $preview_url,
				'post_id'            => (int) $post_id,
				'column'             => $column,
				'created'            => time(),
				'quality'            => 'default',
				'file_size'          => (int) $file_meta['file_size'],
				'file_type'          => (string) $file_meta['file_type'],
				'width'              => (int) $file_meta['width'],
				'height'             => (int) $file_meta['height'],
			);
			set_transient( self::TRANSIENT_PREFIX . $request_id, $meta, self::TRANSIENT_TTL );

			return $meta;
		}

		return null;
	}

	/**
	 * Re-run the original image generation request once, then resolve pending metadata.
	 *
	 * @param string $request_id Request ID.
	 * @param int    $post_id    Post ID.
	 * @param string $column     Column name.
	 * @return array|null
	 */
	private static function retryPendingImageGenerationOnce( $request_id, $post_id, $column ) {
		if ( get_transient( self::PENDING_RETRIED_PREFIX . $request_id ) ) {
			return null;
		}

		$context = get_transient( self::PENDING_CONTEXT_PREFIX . $request_id );
		if ( ! is_array( $context ) || empty( $context['table_data'] ) || ! is_array( $context['table_data'] ) ) {
			return null;
		}

		if ( SheetsPilotGlobals::$isPro != true || ! class_exists( 'SheetsPilotCellEditor', false ) ) {
			return null;
		}

		set_transient( self::PENDING_RETRIED_PREFIX . $request_id, 1, self::TRANSIENT_TTL );

		$table_data = $context['table_data'];
		$prompt_text = isset( $context['prompt_text'] ) ? (string) $context['prompt_text'] : '';
		$table_data['pending_request_id'] = $request_id;
		$table_data['postId'] = (int) $post_id;
		if ( ! isset( $table_data['column'] ) || (string) $table_data['column'] === '' ) {
			$table_data['column'] = $column;
		}

		try {
			$result = SheetsPilotCellEditor::applyImageGenerationToTable( $table_data, $prompt_text );
		} catch ( Throwable $e ) {
			return null;
		}

		if ( ! is_array( $result ) ) {
			return null;
		}

		if ( isset( $result['status'] ) && in_array( $result['status'], array( 'queued', 'in_progress' ), true ) ) {
			return null;
		}

		if ( ( $result['type'] ?? '' ) !== 'pending_image' || empty( $result['request_id'] ) ) {
			return null;
		}

		return self::getPending( $request_id );
	}

	/**
	 * Promote pending image to WordPress attachment and set as post thumbnail (for post_image column).
	 * Deletes the pending file and transient on success.
	 *
	 * @param string $request_id Request ID.
	 * @param int    $post_id    Post ID to attach to.
	 * @param string $column     Column name (e.g. post_image).
	 * @return array { attachment_id, thumbnail_url } for client to update cell.
	 */
	public static function promotePendingToPost($request_id, $post_id, $column)
	{

		$meta = self::resolvePromotePendingMeta( $request_id, $post_id, $column );
		if (! $meta) {
			SheetsPilotFunctions::throwError(__('Pending image not found or expired.',  'sheetspilot'));
		}

		$filepath = $meta['path'];
		$post_id  = (int) $post_id;

		$filename   = basename( $filepath );
		$upload_dir = wp_upload_dir();
		$dest_file  = wp_unique_filename( $upload_dir['path'], $filename );
		$dest_path  = $upload_dir['path'] . '/' . $dest_file;

		if ( ! copy( $filepath, $dest_path ) ) {
			SheetsPilotFunctions::throwError( __( 'Could not copy image to uploads.', 'sheetspilot' ) );
		}

		$filetype = wp_check_filetype( $dest_file, null );
		$attachment = array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_file_name( pathinfo( $dest_file, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attachment_id = wp_insert_attachment( $attachment, $dest_path, $post_id );
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $dest_path );
			SheetsPilotFunctions::throwError( $attachment_id->get_error_message() );
		}

		$attach_data = array(
			'file' => _wp_relative_upload_path( $dest_path ),
		);
		$imagesize = wp_getimagesize( $dest_path );
		if ( $imagesize ) {
			$attach_data['width']  = (int) $imagesize[0];
			$attach_data['height'] = (int) $imagesize[1];
		}
		$file_bytes = (int) filesize( $dest_path );
		if ( $file_bytes > 0 ) {
			$attach_data['filesize'] = $file_bytes;
		}
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		if ( $column === 'post_image' ) {
			update_post_meta($post_id, '_thumbnail_id', $attachment_id);
		}

		self::deletePending($request_id);

		$thumbnail_url = self::resolveAttachmentThumbnailUrl( $attachment_id, SheetsPilotUniteFunctionsWP::THUMB_MEDIUM );

		return self::buildPromotePendingResponse( $attachment_id, $thumbnail_url );

	}


	/**
	 * Promote pending image to WordPress attachment and set as post thumbnail (for post_image column).
	 * Deletes the pending file and transient on success.
	 *
	 * @param string $request_id Request ID.
	 * @param int    $post_id    Post ID to attach to.
	 * @param string $column     Column name (e.g. post_image).
	 * @param string $column_type     Column type (e.g. acf_gallery).
	 * @return array { attachment_id, thumbnail_url } for client to update cell.
	 */
	public static function promotePendingToPostGallery($request_id, $post_id, $column, $column_type )
	{

		$meta = self::resolvePromotePendingMeta( $request_id, $post_id, $column );
		if (! $meta) {
			SheetsPilotFunctions::throwError(__('Pending image not found or expired.',  'sheetspilot'));
		}

		$filepath = $meta['path'];
		$post_id  = (int) $post_id;

		$filename   = basename( $filepath );
		$upload_dir = wp_upload_dir();
		$dest_file  = wp_unique_filename( $upload_dir['path'], $filename );
		$dest_path  = $upload_dir['path'] . '/' . $dest_file;

		if ( ! copy( $filepath, $dest_path ) ) {
			SheetsPilotFunctions::throwError( __( 'Could not copy image to uploads.', 'sheetspilot' ) );
		}

		$filetype = wp_check_filetype( $dest_file, null );
		$attachment = array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_file_name( pathinfo( $dest_file, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attachment_id = wp_insert_attachment( $attachment, $dest_path, $post_id );
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $dest_path );
			SheetsPilotFunctions::throwError( $attachment_id->get_error_message() );
		}

		$attach_data = array(
			'file' => _wp_relative_upload_path( $dest_path ),
		);
		$imagesize = wp_getimagesize( $dest_path );
		if ( $imagesize ) {
			$attach_data['width']  = (int) $imagesize[0];
			$attach_data['height'] = (int) $imagesize[1];
		}
		$file_bytes = (int) filesize( $dest_path );
		if ( $file_bytes > 0 ) {
			$attach_data['filesize'] = $file_bytes;
		}
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		self::deletePending( $request_id );

		$thumbnail_url      = self::resolveAttachmentThumbnailUrl( $attachment_id, SheetsPilotUniteFunctionsWP::THUMB_MEDIUM );
		$thumbnail_url_full = self::resolveAttachmentThumbnailUrl( $attachment_id, SheetsPilotUniteFunctionsWP::THUMB_FULL );

		return self::buildPromotePendingResponse( $attachment_id, $thumbnail_url, $thumbnail_url_full );

	}

	/**
	 * Clean up expired pending images (full + thumb files and transients). Call from cron or on editor load.
	 */
	public static function cleanupExpired()
	{

		$upload_dir = wp_upload_dir();
		$dir        = $upload_dir['basedir'] . '/' . self::PENDING_DIR;
		if (! is_dir($dir)) {
			return;
		}
		$scan = @scandir($dir);
		if (! is_array($scan)) {
			return;
		}
		$prefix = self::TRANSIENT_PREFIX;
		$cutoff = time() - self::TRANSIENT_TTL;
		$processed_bases = array();
		foreach ($scan as $name) {
			if ($name === '.' || $name === '..') {
				continue;
			}
			$file = $dir . '/' . $name;
			if (! is_file($file)) {
				continue;
			}
			$stem   = pathinfo($name, PATHINFO_FILENAME);
			$ext    = pathinfo($name, PATHINFO_EXTENSION);
			$base_id = preg_replace('/_thumb$/', '', $stem);
			if (isset($processed_bases[$base_id . '.' . $ext])) {
				continue;
			}
			$processed_bases[$base_id . '.' . $ext] = true;
			$transient_key = $prefix . $base_id;
			$meta          = get_transient($transient_key);
			if (! is_array($meta) || empty($meta['created']) || $meta['created'] < $cutoff) {
				$main_file  = $dir . '/' . $base_id . '.' . $ext;
				$thumb_file = $dir . '/' . $base_id . '_thumb.' . $ext;
				if (is_file($main_file)) {
					@unlink($main_file);
				}
				if (is_file($thumb_file)) {
					@unlink($thumb_file);
				}
				delete_transient($transient_key);
			}
		}

	}
}
