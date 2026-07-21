<?php

/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if (! defined('ABSPATH')) exit;
if (!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilotHelper
{

	private static $cacheGeneralSettings;
	private static $db;
	private static $arrDebug = array();

	function __construct() {}


	/**
	 * get database
	 */
	public static function getDB()
	{

		if (empty(self::$db))
			self::$db = new SheetsPilot_PluginDB();

		return (self::$db);
	}


	/**
	 * get nonce
	 */
	public static function getNonce()
	{

		$nonce = wp_create_nonce('sheetspilot' . "_actions");

		return ($nonce);
	}

	/**
	 * veryfy nonce
	 */
	public static function verifyNonce($nonce)
	{

		$verified = wp_verify_nonce($nonce, 'sheetspilot' . "_actions");
		if ($verified == false)
			SheetsPilotFunctions::throwError("Action security failed, please refresh the page and repeat action");
	}

	/**
	 * get ajax url for export
	 */
	public static function getUrlRemoteAjax($urlAjax, $action, $params = "")
	{

		$urlAjax = SheetsPilotFunctions::addUrlParams($urlAjax, "action=" . 'sheetspilot' . "_ajax_actions&client_action={$action}");

		if (!empty($params))
			$urlAjax .= "&" . $params;

		return ($urlAjax);
	}

	/**
	 * get local ajax url
	 */
	public static function getUrlAjax($action, $params = "")
	{

		$urlAjax = SheetsPilotGlobals::$urlAjax;

		$nonce = self::getNonce();

		$urlAjax = SheetsPilotFunctions::addUrlParams($urlAjax, "action=" . 'sheetspilot' . "_ajax_actions&nonce={$nonce}&client_action={$action}");

		if (!empty($params))
			$urlAjax .= "&" . $params;

		return ($urlAjax);
	}


	/**
	 * add global localization
	 */
	public static function addInlineLocalization()
	{
		wp_add_inline_script(
			'jquery',
			'var sheetspilot = window.sheetspilot || {};
			sheetspilot.editor = ' . wp_json_encode(SheetsPilotGlobals::$editorScriptLocalization) . ';',
			'before'
		);
	}

	/**
	 * add script absolute url
	 */
	public static function addScriptAbsoluteUrl($url, $handle, $inFooter = false, $deps = array())
	{

		if (empty($url))
			SheetsPilotFunctions::throwError("empty script url, handle: $handle");

		$version = SHEETSPILOT_VERSION . time();

		wp_register_script($handle, $url, $deps, $version, $inFooter);
		wp_enqueue_script($handle);
	}

	/**
	 * add wp library scripts
	 */
	public static function addLibraryScript($handle)
	{
		wp_enqueue_script($handle);
	}
	/**
	 * add wp library іендуі
	 */
	public static function addLibraryStyle($handle)
	{
		wp_enqueue_style($handle);
	}

	/**
	 * add style absolute url
	 */
	public static function addStyleAbsoluteUrl($url, $handle)
	{

		if (empty($url))
			SheetsPilotFunctions::throwError("empty style url, handle: $handle");

		$version = SHEETSPILOT_VERSION . time();

		$deps = array();

		wp_register_style($handle, $url, $deps, $version);
		wp_enqueue_style($handle);
	}


	/**
	 *
	 * register script helper function
	 */
	public static function addScript($scriptName, $handle = null, $folder = "assets/js", $inFooter = false, $isPro = false )
	{

		if ($handle == null)
			$handle = 'sheetspilot' . "-" . $scriptName;

		

		if( $isPro ){
			$url = SheetsPilotGlobals::$urlPluginPro . $folder . "/" . $scriptName . ".js";
		}else{
			$url = SheetsPilotGlobals::$urlPlugin . $folder . "/" . $scriptName . ".js";
		}
  
		if( SHEETS_PLUGIN_IS_GLOBAL_DEV ){
			$url = SheetsPilotGlobals::$urlPlugin . $folder . "/" . $scriptName . ".js";
		}
		if( SHEETS_PLUGIN_IS_GLOBAL_DEV && $isPro){
			$url = SheetsPilotGlobals::$urlPlugin.'pro/' . $folder . "/" . $scriptName . ".js";
		}

		self::addScriptAbsoluteUrl($url, $handle, $inFooter);
	}


	/**
	 *
	 * register style helper function
	 */
	public static function addStyle($styleName, $handle = null, $folder = "assets/css")
	{

		if ($handle == null)
			$handle = 'sheetspilot' . "-" . $styleName;

		$url = SheetsPilotGlobals::$urlPlugin . $folder . "/" . $styleName . ".css";

		self::addStyleAbsoluteUrl($url, $handle);
	}


	/**
	 * get file path
	 * @param  $filename
	 */
	public static function getPathFile($filename, $path, $defaultPath, $validateName, $ext = "php")
	{

		if (empty($path))
			$path = $defaultPath;

		$filepath = $path . $filename . "." . $ext;
		SheetsPilotFunctions::validateFilepath($filepath, $validateName);

		return ($filepath);
	}


	/**
	 * Get URL to a view. Most views use page=prefix_viewName; only Prompt History uses log page with &view=prompt_history.
	 *
	 * @param string $viewName   View name (e.g. settings, postseditor, log, prompt_history).
	 * @param string $urlParams  Optional extra query params to append.
	 * @return string Admin URL.
	 */
	public static function getViewUrl($viewName, $urlParams = "")
	{
		$prefix = 'sheetspilot';

		if ($viewName === SheetsPilotGlobals::VIEW_PROMPT_HISTORY) {
			$url = admin_url("admin.php?page=" . $prefix . "_log&view=" . rawurlencode($viewName));
		} else {
			$url = admin_url("admin.php?page=" . $prefix . "_" . $viewName);
		}

		if (! empty($urlParams)) {
			$url = SheetsPilotFunctions::addUrlParams($url, $urlParams);
		}

		return $url;
	}


	/**
	 * require some template from "templates" folder
	 */
	public static function getPathView($viewName, $path = null)
	{

		return self::getPathFile($viewName, $path, SheetsPilotGlobals::$pathViews, "View");
	}

	/**
	 * Get list of languages: site languages from plugins first, then default list (always included).
	 * Supports WPML, Polylang, TranslatePress, Weglot, WPGlobus.
	 *
	 * @return array Array of language name strings.
	 */
	public static function getSiteLanguages()
	{
		$default_list = array('English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese', 'Dutch', 'Polish', 'Russian', 'Chinese', 'Japanese', 'Korean', 'Arabic', 'Hebrew', 'Turkish', 'Hindi', 'Indonesian', 'Vietnamese', 'Thai', 'Greek', 'Custom');
		$languages = array();

		// WPML
		if ((did_action('wpml_loaded') || class_exists('SitePress')) && function_exists('apply_filters')) {

			$active = apply_filters('wpml_active_languages', null);
			if (is_array($active) && ! empty($active)) {
				foreach ($active as $lang) {
					$name = isset($lang['native_name']) ? $lang['native_name'] : (isset($lang['translated_name']) ? $lang['translated_name'] : '');
					if ($name && ! in_array($name, $languages, true)) {
						$languages[] = $name;
					}
				}
			}
		}

		// Polylang
		if (function_exists('pll_languages_list')) {
			$names = pll_languages_list(array('fields' => 'name'));
			if (is_array($names) && ! empty($names)) {
				foreach ($names as $name) {
					if ($name && ! in_array($name, $languages, true)) {
						$languages[] = $name;
					}
				}
			}
		}

		// TranslatePress
		if (function_exists('trp_custom_language_switcher')) {
			$switcher = trp_custom_language_switcher();
			if (is_array($switcher)) {
				foreach ($switcher as $item) {
					$name = isset($item['language_name']) ? $item['language_name'] : (isset($item['short_language_name']) ? $item['short_language_name'] : '');
					if ($name && ! in_array($name, $languages, true)) {
						$languages[] = $name;
					}
				}
			}
		}

		// Weglot
		if (function_exists('weglot_get_destination_languages')) {
			$dest = weglot_get_destination_languages();
			if (is_array($dest) && ! empty($dest)) {
				foreach ($dest as $lang) {
					$name = is_string($lang) ? $lang : (isset($lang['language_to']) ? $lang['language_to'] : (isset($lang['english_name']) ? $lang['english_name'] : ''));
					if ($name && ! in_array($name, $languages, true)) {
						$languages[] = $name;
					}
				}
			}
		}

		// Weglot via options
		$opts = get_option('weglot_settings', array());
		if (is_array($opts) && ! empty($opts['destination_language'])) {
			$dest = $opts['destination_language'];
			if (is_array($dest)) {
				foreach ($dest as $code) {
					if (is_string($code) && strlen($code) >= 2) {
						$name = ucfirst(strtolower($code));
						if (! in_array($name, $languages, true)) {
							$languages[] = $name;
						}
					}
				}
			}
		}

		// WPGlobus
		if (defined('WPGLOBUS_VERSION') && class_exists('WPGlobus')) {
			$enabled = WPGlobus::Config()->enabled_languages;
			if (is_array($enabled)) {
				$all = WPGlobus::Config()->language_name;
				foreach ($enabled as $code) {
					if (isset($all[$code]) && ! in_array($all[$code], $languages, true)) {
						$languages[] = $all[$code];
					}
				}
			}
		}

		// Always add default list (any not already present)
		foreach ($default_list as $name) {
			if (! in_array($name, $languages, true)) {
				$languages[] = $name;
			}
		}

		return $languages;
	}




	/**
	 * get error message html
	 */
	public static function getErrorMessageHtml($message, $trace = "")
	{

		$html = '<div class="unite-error-message">';
		$html .= '<div class="unite-error-message-inner">';
		$html .= esc_html($message);
		if (! empty($trace)) {
			$html .= '<div class="unite-error-message-trace">';
			$html .= '<pre>' . esc_html($trace) . '</pre>';
			$html .= '</div>';
		}
		$html .= '</div></div>';
		return $html;
	}


	/**
	 * add debug
	 */
	public static function addDebug($str)
	{

		self::$arrDebug[] = $str;
	}

	/**
	 * print debug
	 */
	public static function printDebug()
	{

		dmp(self::$arrDebug);
	}

	/**
	 * Whether inline PHP backtraces are enabled for SheetsPilot AJAX (admins only).
	 *
	 * @return bool
	 */
	public static function isAjaxInlineErrorTraceEnabled() {
		if ( ! is_admin() || ! current_user_can( SheetsPilotGlobals::$capability ) ) {
			return false;
		}

		if ( SheetsPilotGlobals::DEBUG_ERRORS === true ) {
			return true;
		}

		return SheetsPilotGlobals::$showTrace === true;
	}

	/**
	 * Enable trace mode when showtrace=true is passed on an AJAX request.
	 */
	public static function applyShowTraceFromAjaxRequest() {
		if ( ! current_user_can( SheetsPilotGlobals::$capability ) ) {
			return;
		}

		$showtrace = SheetsPilotFunctions::getGetVar( 'showtrace', '', SheetsPilotFunctions::SANITIZE_TEXT_FIELD );
		if ( false === $showtrace || '' === $showtrace ) {
			$showtrace = SheetsPilotFunctions::getPostGetVariable( 'showtrace', '', SheetsPilotFunctions::SANITIZE_TEXT_FIELD );
		}
		if ( false === $showtrace ) {
			return;
		}

		if ( in_array( strtolower( (string) $showtrace ), array( 'true', '1' ), true ) ) {
			SheetsPilotGlobals::$showTrace = true;
		}
	}

	/** @var callable|null */
	private static $ajax_inline_error_handler_previous = null;

	/** @var bool */
	private static $ajax_inline_error_handler_active = false;

	/**
	 * During SheetsPilot AJAX, print stack traces directly after each PHP warning/notice.
	 */
	public static function registerAjaxInlineErrorTraceHandler() {
		if ( self::$ajax_inline_error_handler_active || ! self::isAjaxInlineErrorTraceEnabled() ) {
			return;
		}

		self::$ajax_inline_error_handler_active = true;
		self::$ajax_inline_error_handler_previous = set_error_handler( array( __CLASS__, 'handleAjaxInlinePhpError' ) );
	}

	/**
	 * @param int    $errno   Error level.
	 * @param string $errstr  Message.
	 * @param string $errfile File.
	 * @param int    $errline Line.
	 * @return bool
	 */
	public static function handleAjaxInlinePhpError( $errno, $errstr, $errfile, $errline ) {
		if ( ! self::$ajax_inline_error_handler_active ) {
			return self::callPreviousAjaxInlineErrorHandler( $errno, $errstr, $errfile, $errline );
		}

		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}

		$tracked = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED;
		if ( ! ( $errno & $tracked ) ) {
			return self::callPreviousAjaxInlineErrorHandler( $errno, $errstr, $errfile, $errline );
		}

		$label = self::getPhpErrorLabel( $errno );
		$line  = esc_html( $label . ': ' . $errstr . ' in ' . $errfile . ' on line ' . $errline );
		$trace = esc_html( self::formatPhpErrorBacktrace( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) ) );

		echo '<div class="sheetspilot-php-warning">' . $line . '<pre class="sheetspilot-php-error-trace">' . $trace . '</pre></div>';

		return true;
	}

	/**
	 * @param array<string, mixed> $error Error array from wp_php_error_args filter.
	 * @return string
	 */
	public static function formatPhpErrorBacktraceFromError( $error ) {
		$backtrace = array();
		if ( isset( $error['backtrace'] ) && is_array( $error['backtrace'] ) ) {
			$backtrace = $error['backtrace'];
		}

		return self::formatPhpErrorBacktrace( $backtrace );
	}

	/**
	 * @param array<int, array<string, mixed>> $backtrace debug_backtrace() result.
	 * @return string
	 */
	private static function formatPhpErrorBacktrace( $backtrace ) {
		$lines = array();
		$skip  = true;

		foreach ( $backtrace as $frame ) {
			if ( $skip ) {
				$function = isset( $frame['function'] ) ? (string) $frame['function'] : '';
				if ( in_array( $function, array( 'handleAjaxInlinePhpError', 'formatPhpErrorBacktraceFromError' ), true ) ) {
					continue;
				}
				$skip = false;
			}

			$file = isset( $frame['file'] ) ? self::relativePluginPath( (string) $frame['file'] ) : '';
			$line = isset( $frame['line'] ) ? (int) $frame['line'] : 0;
			$func = isset( $frame['function'] ) ? (string) $frame['function'] : '';

			if ( isset( $frame['class'] ) ) {
				$func = (string) $frame['class'] . ( isset( $frame['type'] ) ? (string) $frame['type'] : '::' ) . $func;
			}

			$location = $file !== '' ? $file . ( $line > 0 ? ':' . $line : '' ) : '(internal)';
			$lines[]  = $location . ' ' . $func . '()';

			if ( count( $lines ) >= 20 ) {
				break;
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * @param string $path Absolute path.
	 * @return string
	 */
	private static function relativePluginPath( $path ) {
		if ( defined( 'ABSPATH' ) && strpos( $path, ABSPATH ) === 0 ) {
			return substr( $path, strlen( ABSPATH ) );
		}

		return $path;
	}

	/**
	 * @param int $errno Error level.
	 * @return string
	 */
	private static function getPhpErrorLabel( $errno ) {
		switch ( $errno ) {
			case E_WARNING:
			case E_USER_WARNING:
				return 'Warning';
			case E_NOTICE:
			case E_USER_NOTICE:
				return 'Notice';
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				return 'Deprecated';
			default:
				return 'PHP';
		}
	}

	/**
	 * @param int    $errno   Error level.
	 * @param string $errstr  Message.
	 * @param string $errfile File.
	 * @param int    $errline Line.
	 * @return bool
	 */
	private static function callPreviousAjaxInlineErrorHandler( $errno, $errstr, $errfile, $errline ) {
		if ( is_callable( self::$ajax_inline_error_handler_previous ) ) {
			return (bool) call_user_func( self::$ajax_inline_error_handler_previous, $errno, $errstr, $errfile, $errline );
		}

		return false;
	}

	/**
	 * on php error message
	 */
	public static function onPHPErrorMessage($message, $error)
	{

		$errorMessage = SheetsPilotFunctions::getVal($error, "message");

		$file = SheetsPilotFunctions::getVal($error, "file");
		$line = SheetsPilotFunctions::getVal($error, "line");

		if (is_string($errorMessage))
			$message .= "Unlimited AI Troubleshooting: \n<br><pre>{$errorMessage}</pre>";

		if (!empty($file))
			$message .= "in : <b>$file</b>";

		if (!empty($line))
			$message .= " on line <b>$line</b>";

		if ( self::isAjaxInlineErrorTraceEnabled() ) {
			$trace = self::formatPhpErrorBacktraceFromError( is_array( $error ) ? $error : array() );
			if ( $trace !== '' ) {
				$message .= '<pre class="sheetspilot-php-error-trace">' . esc_html( $trace ) . '</pre>';
			}
		}

		return ($message);
	}


	/**
	 * get post type titles
	 * try first hard coded to avoid language issues
	 */
	public static function getPostTypeTitles($postType)
	{

		if (empty($postType))
			$postType = "post";

		$single = $postType;
		$plural = $postType;

		$isOutput = true;

		switch ($postType) {
			case "post":
				$single = "Post";
				$plural = "Posts";
				break;
			case "page":
				$single = "Page";
				$plural = "Pages";
				break;
			case "product":
				$single = "Product";
				$plural = "Products";
				break;
			case "attachment":
				$single = "Image";
				$plural = "Images";
				break;
			case "snippet":
				$single = "Snippet";
				$plural = "Snippets";
				break;
			case "media":
				$single = "Media";
				$plural = "Medias";
				break;
			default:
				$isOutput = false;
				break;
		}

		$output = array();

		if ($isOutput == true) {

			$output["single"] = $single;
			$output["plural"] = $plural;

			return ($output);
		}

		//get names by object

		$objType = get_post_type_object($postType);

		if (!empty($objType)) {

			$arrLabels = $objType->labels;

			$arrLabels = (array)$arrLabels;

			$plural = SheetsPilotFunctions::getVal($arrLabels, "name");
			$single = SheetsPilotFunctions::getVal($arrLabels, "singular_name");
		}

		$output["single"] = $single;
		$output["plural"] = $plural;


		return ($output);
	}


		/**
	 * get post type titles
	 * try first hard coded to avoid language issues
	 */
	public static function getPostTypeList($postType)
	{
		$all_posts = get_posts([
			'post_type' => $postType,
			'showposts' => -1,
			'order' => 'ASC',
			'orderby' => 'post_title',
		]);	
		
		$full_list = [];
		foreach( $all_posts as $s_post ){
			$full_list[] = [ 'id' => $s_post->ID, 'name' => $s_post->post_title];
		}

		return $full_list;
	}

	/**
	 * check if url is local - inside base url
	 */
	public static function isUrlUnderBase($url)
	{

		$pos = strpos($url, SheetsPilotGlobals::$urlBase);

		if ($pos !== false)
			return (true);

		return (false);
	}

	/**
	 * try to get attachment id and site from url (include thumb url's)
	 */
	public static function getAttachmentDataFromUrl($url)
	{

		$isUnderBase = self::isUrlUnderBase($url);

		if ($isUnderBase == false)
			return (null);

		$postID = attachment_url_to_postid($url);

		if (!empty($postID)) {

			$output = array();
			$output["id"] = $postID;
			$output["size"] = SheetsPilotUniteFunctionsWP::THUMB_FULL;

			return ($output);
		}

		$arrImage = SheetsPilotUniteFunctionsWP::getAttachmentIDFromImageUrl($url, true);

		if (empty($arrImage))
			return (null);


		return ($arrImage);
	}


	/**
	 * try to get attachment id from attachment url
	 */
	public static function getAttachmentIDFromUrl($url)
	{

		$isUnderBase = self::isUrlUnderBase($url);

		if ($isUnderBase == false)
			return (null);

		$postID = attachment_url_to_postid($url);

		if (empty($postID))
			return (null);

		return ($postID);
	}

	/**
	 * File size in bytes for a media attachment (metadata or disk).
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return int
	 */
	public static function getAttachmentFileSizeBytes( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		if ( $attachment_id <= 0 ) {
			return 0;
		}

		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( is_array( $meta ) && ! empty( $meta['filesize'] ) ) {
			return (int) $meta['filesize'];
		}

		$file = get_attached_file( $attachment_id );
		if ( is_string( $file ) && $file !== '' && file_exists( $file ) ) {
			return (int) filesize( $file );
		}

		return 0;
	}

	/**
	 * data-file-size attribute for image hover previews.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return string
	 */
	public static function getAttachmentFileSizeDataAttr( $attachment_id ) {
		$bytes = self::getAttachmentFileSizeBytes( $attachment_id );
		if ( $bytes <= 0 ) {
			return '';
		}

		return ' data-file-size="' . esc_attr( (string) $bytes ) . '"';
	}

	/**
	 * Short image type label for hover previews (png, jpg, webp, …).
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return string
	 */
	public static function getAttachmentFileTypeLabel( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		if ( $attachment_id <= 0 ) {
			return '';
		}

		$mime = get_post_mime_type( $attachment_id );
		if ( ! is_string( $mime ) || $mime === '' ) {
			return '';
		}

		$type = strtolower( $mime );
		if ( strpos( $type, '/' ) !== false ) {
			$type = substr( $type, strrpos( $type, '/' ) + 1 );
		}
		if ( $type === 'jpeg' ) {
			$type = 'jpg';
		}

		return $type;
	}

	/**
	 * data-file-type attribute for image hover previews.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return string
	 */
	public static function getAttachmentFileTypeDataAttr( $attachment_id ) {
		$type = self::getAttachmentFileTypeLabel( $attachment_id );
		if ( $type === '' ) {
			return '';
		}

		return ' data-file-type="' . esc_attr( $type ) . '"';
	}

	/**
	 * Full image dimensions from attachment metadata.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return array{width:int,height:int}
	 */
	public static function getAttachmentImageDimensions( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		$width         = 0;
		$height        = 0;

		if ( $attachment_id <= 0 ) {
			return array(
				'width'  => 0,
				'height' => 0,
			);
		}

		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( is_array( $meta ) ) {
			if ( ! empty( $meta['width'] ) ) {
				$width = (int) $meta['width'];
			}
			if ( ! empty( $meta['height'] ) ) {
				$height = (int) $meta['height'];
			}
		}

		return array(
			'width'  => $width,
			'height' => $height,
		);
	}

	/**
	 * data-image-width / data-image-height attributes for image hover previews.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return string
	 */
	public static function getAttachmentImageDimensionsDataAttr( $attachment_id ) {
		$dims = self::getAttachmentImageDimensions( $attachment_id );
		if ( $dims['width'] <= 0 || $dims['height'] <= 0 ) {
			return '';
		}

		return ' data-image-width="' . esc_attr( (string) $dims['width'] ) . '" data-image-height="' . esc_attr( (string) $dims['height'] ) . '"';
	}

	/**
	 * data-filename attribute from the attachment file on disk.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return string
	 */
	public static function getAttachmentFilenameDataAttr( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		if ( $attachment_id <= 0 ) {
			return '';
		}

		$file = get_attached_file( $attachment_id );
		if ( ! $file ) {
			return '';
		}

		$filename = basename( $file );
		if ( $filename === '' ) {
			return '';
		}

		return ' data-filename="' . esc_attr( $filename ) . '"';
	}

	/**
	 * data-file-size, data-file-type, and dimension attributes for image hover previews.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return string
	 */
	public static function getAttachmentImagePreviewDataAttrs( $attachment_id ) {
		return self::getAttachmentFileSizeDataAttr( $attachment_id )
			. self::getAttachmentFileTypeDataAttr( $attachment_id )
			. self::getAttachmentImageDimensionsDataAttr( $attachment_id )
			. self::getAttachmentFilenameDataAttr( $attachment_id );
	}

	/**
	 * Preview metadata for client-side image cells (file size, type, dimensions).
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return array{file_size:int,file_type:string,width:int,height:int,full_url:string,filename:string}
	 */
	public static function getAttachmentImagePreviewMeta( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		$dims          = self::getAttachmentImageDimensions( $attachment_id );
		$full_url      = wp_get_attachment_url( $attachment_id );
		$file          = get_attached_file( $attachment_id );
		$filename      = is_string( $file ) && $file !== '' ? basename( $file ) : '';

		return array(
			'file_size' => self::getAttachmentFileSizeBytes( $attachment_id ),
			'file_type' => self::getAttachmentFileTypeLabel( $attachment_id ),
			'width'     => (int) $dims['width'],
			'height'    => (int) $dims['height'],
			'full_url'  => is_string( $full_url ) ? $full_url : '',
			'filename'  => $filename,
		);
	}



	/**
	 * return true if exits base url key in text
	 */
	public static function hasBaseUrl($text)
	{

		$pos = strpos($text, SheetsPilot_PluginExporterBase::KEY_BASE_URL);

		if ($pos !== false)
			return (true);

		return (false);
	}

	/**
	 * remote base url from url's
	 */
	public static function removeBaseUrl($text)
	{

		$text = str_replace(SheetsPilot_PluginExporterBase::KEY_BASE_URL, SheetsPilotGlobals::$urlBase, $text);

		return ($text);
	}


	/**
	 * convert some url to relative
	 */
	public static function URLtoRelative($url, $isAssets = false)
	{

		$replaceString = SheetsPilotGlobals::$urlBase;
		if ($isAssets == true)
			$replaceString = SheetsPilotGlobals::$urlBase;

		//change the protocol
		if (strpos($url, "http://") !== false && strpos($replaceString, "https://") !== false)
			$replaceString = str_replace("https://", "http://", $replaceString);

		//in case of array take "url" from the array
		if (is_array($url)) {

			$strUrl = SheetsPilotFunctions::getVal($url, "url");
			if (empty($strUrl))
				return ($url);

			$url["url"] = str_replace($replaceString, "", $strUrl);

			return ($url);
		}

		$url = str_replace($replaceString, "", $url);

		return ($url);
	}


 


	/**
	 * convert url to full url
	 */
	public static function URLtoFull($url, $urlBase = null)
	{

		if (is_numeric($url))		//protection for image id
			return ($url);

		if (getType($urlBase) == "boolean")
			SheetsPilotFunctions::throwError("the url base should be null or string");

		if (is_array($url))
			SheetsPilotFunctions::throwError("url can't be array");

		$url = trim($url);

		if (empty($url))
			return ("");

		$urlLower = strtolower($url);

		if (strpos($urlLower, "http://") !== false || strpos($urlLower, "https://") !== false)
			return ($url);

		if (empty($urlBase))
			$url = SheetsPilotGlobals::$urlBase . $url;
		else {

			$convertUrl = SheetsPilotGlobals::$urlBase;

			//preserve old format:
			$filepath = self::pathToAbsolute($url);
			if (file_exists($filepath) == false)
				$convertUrl = $urlBase;

			$url = $convertUrl . $url;
		}

		$url = SheetsPilotFunctions::cleanUrl($url);

		return ($url);
	}



	/**
	 * convert title to handle
	 */
	public static function convertTitleToHandle($title, $removeNonAscii = true)
	{

		$handle = strtolower($title);

		$handle = str_replace(array("�", "�"), "a", $handle);
		$handle = str_replace(array("�", "�"), "a", $handle);
		$handle = str_replace(array("�", "�"), "o", $handle);

		if ($removeNonAscii == true) {

			// Remove any character that is not alphanumeric, white-space, or a hyphen
			$handle = preg_replace("/[^a-z0-9\s\_]/i", " ", $handle);
		}

		// Replace multiple instances of white-space with a single space
		$handle = preg_replace("/\s\s+/", " ", $handle);
		// Replace all spaces with underscores
		$handle = preg_replace("/\s/", "_", $handle);
		// Replace multiple underscore with a single underscore
		$handle = preg_replace("/\_\_+/", "_", $handle);
		// Remove leading and trailing underscores
		$handle = trim($handle, "_");

		return ($handle);
	}


	/**
	 * convert title to alias
	 */
	public static function convertTitleToAlias($title)
	{

		$handle = self::convertTitleToHandle($title, false);
		$alias = str_replace("_", "-", $handle);

		return ($alias);
	}

	/**
	 * get host url without extension
	 */
	public static function getUrlHostNoExtension()
	{
	
		$urlInfo = parse_url(SheetsPilotGlobals::$urlBase);
		$host = SheetsPilotFunctions::getVal($urlInfo, "host");

		if (empty($host))
			return ("");

		$host = SheetsPilotFunctions::getDomainWithoutExtension($host);

		return ($host);
	
	}

	private static function ___________GENERAL_SETTINGS___________() {}

	/**
	 * get general settings object (Pro only; requires general_settings.class.php).
	 *
	 * @return object|null Settings object, or null when Pro is not loaded.
	 */
	public static function getGeneralSettingsObject($withValues = true)
	{

		if (! class_exists('SheetsPilot_PluginGeneralSettings')) {
			return null;
		}

		$settings = SheetsPilot_PluginGeneralSettings::getSettingsObject();

		if ($withValues == false) {
			return $settings;
		}

		$settingsSavedValue = get_option(SheetsPilotGlobals::OPTION_GENERAL_SETTINGS);

		if (empty($settingsSavedValue)) {
			$settingsSavedValue = array();
		}

		$settings->setStoredValues($settingsSavedValue);

		return $settings;
	}


	/**
	 * get general settings (Pro only; empty array on free / when Pro files are missing).
	 */
	public static function getGeneralSettings()
	{

		if (!empty(self::$cacheGeneralSettings)) {
			return self::$cacheGeneralSettings;
		}

		if (! class_exists('SheetsPilot_PluginGeneralSettings')) {
			self::$cacheGeneralSettings = array();
			return self::$cacheGeneralSettings;
		}

		$objSettings = self::getGeneralSettingsObject();

		if ($objSettings === null) {
			self::$cacheGeneralSettings = array();
			return self::$cacheGeneralSettings;
		}

		$arrValues = $objSettings->getArrValues();

		self::$cacheGeneralSettings = $arrValues;

		return $arrValues;
	}


	/**
	 * get general setting
	 */
	public static function getGeneralSetting($name)
	{

		$arrSettings = self::getGeneralSettings();

		if (array_key_exists($name, $arrSettings) == false)
			SheetsPilotFunctions::throwError("General Setting: $name not found");


		$value = SheetsPilotFunctions::getVal($arrSettings, $name);

		return ($value);
	}



	/**
	 * save the settings from data
	 */
	public static function saveGeneralSettingsFromData($data)
	{

		$arrValues = SheetsPilotFunctions::getVal($data, "settings_values");

		if (empty($arrValues))
			$arrValues = array();

		update_option(SheetsPilotGlobals::OPTION_GENERAL_SETTINGS, $arrValues);
	}

	/**
	 * Apply Pro/Free mode from AJAX payload: capability check, validate, persist option and SheetsPilotGlobals::$isPro.
	 *
	 * @param array $data Request data (expects is_pro).
	 * @return array Success payload with is_pro (bool).
	 */
	public static function saveProModeFromAjaxData($data)
	{

		if (! current_user_can(SheetsPilotGlobals::$capability)) {
			SheetsPilotFunctions::throwError(__('You do not have permission to change this setting.', 'sheetspilot'));
		}

		$is_pro_raw = SheetsPilotFunctions::getVal($data, 'is_pro', null);
		if ($is_pro_raw === null || $is_pro_raw === '') {
			SheetsPilotFunctions::throwError(__('Invalid request.', 'sheetspilot'));
		}

		$is_pro = (bool) (int) $is_pro_raw;
		if ($is_pro && SheetsPilotGlobals::hasProFolder() === false) {
			SheetsPilotFunctions::throwError(__('Pro files are not installed.', 'sheetspilot'));
		}

		update_option(SheetsPilotGlobals::OPTION_PRO_MODE, $is_pro ? '1' : '0', false);
		SheetsPilotGlobals::$isPro = $is_pro;

		$output = array(
			'is_pro' => $is_pro,
		);

		return $output;
	}

	/**
	 * save post editor settings - post type
	 */
	public static function saveEditorPageSettings($data, $option_name)
	{

		$settings_data = get_option(SheetsPilotGlobals::OPTION_EDITOR_SETTINGS);
		if (isset($data[$option_name])) {
			$settings_data[$option_name] = $data[$option_name];
			update_option(SheetsPilotGlobals::OPTION_EDITOR_SETTINGS, $settings_data);
		}
	}
	/**
	 * delete post editor settings - post type
	 */
	public static function deleteEditorPageSettings_global()
	{
		delete_option(SheetsPilotGlobals::OPTION_EDITOR_SETTINGS);
	}
	public static function deleteEditorPageSettings($data, $option_name)
	{
		$settings_data = get_option(SheetsPilotGlobals::OPTION_EDITOR_SETTINGS);
		if (isset($data[$option_name])) {
			unset($settings_data[$option_name]);
			update_option(SheetsPilotGlobals::OPTION_EDITOR_SETTINGS, $settings_data);
		}
	}

	/**
	 * save post editor settings - post type
	 */
	public static function getEditorPageSettings($option_name)
	{
		$settings_data = get_option(SheetsPilotGlobals::OPTION_EDITOR_SETTINGS);
		$arrValue = SheetsPilotFunctions::getVal($settings_data, $option_name);

		if (isset($arrValue)) {
			return $arrValue;
		} else {
			return false;
		}
	}

	/**
	 * Save content rules option (General tab data).
	 *
	 * @param array $rules Associative array with contentTone, contentLanguage, targetAudience, brandVoice.
	 */
	public static function saveContentRules($rules)
	{
		$sanitized = array(
			'contentTone'     => isset($rules['contentTone']) ? sanitize_text_field($rules['contentTone']) : '',
			'contentLanguage' => isset($rules['contentLanguage']) ? sanitize_text_field($rules['contentLanguage']) : '',
			'customLanguage' => isset($rules['customLanguage']) ? sanitize_text_field($rules['customLanguage']) : '',
			'targetAudience'  => isset($rules['targetAudience']) ? sanitize_textarea_field($rules['targetAudience']) : '',
			'brandVoice'      => isset($rules['brandVoice']) ? sanitize_textarea_field($rules['brandVoice']) : '',
		);
		update_option(SheetsPilotGlobals::OPTION_CONTENT_RULES, $sanitized);
	}

	/**
	 * Default content rules when none are set (first option / sensible WordPress defaults).
	 *
	 * @return array Associative array with contentTone, contentLanguage, targetAudience, brandVoice.
	 */
	public static function getDefaultContentRules()
	{
		$site_languages = self::getSiteLanguages();
		$first_language = ! empty($site_languages) ? $site_languages[0] : 'English';
		return array(
			'contentTone'     => 'Professional',
			'contentLanguage' => $first_language,
			'customLanguage' => '',
			'targetAudience'  => __('WordPress site visitors',  'sheetspilot'),
			'brandVoice'      => __('Clear, helpful, and professional',  'sheetspilot'),
		);
	}

	/**
	 * Get content rules option (General tab data).
	 * When a rule is not filled, the default is used (first option / WordPress default audience).
	 *
	 * @return array Associative array with contentTone, contentLanguage, targetAudience, brandVoice.
	 */
	public static function getContentRules()
	{
		$rules = get_option(SheetsPilotGlobals::OPTION_CONTENT_RULES, array());
		if (! is_array($rules)) {
			$rules = array();
		}
		$rules = wp_parse_args($rules, array(
			'contentTone'     => '',
			'contentLanguage' => '',
			'customLanguage' => '',
			'targetAudience'  => '',
			'brandVoice'      => '',
		));
		$defaults = self::getDefaultContentRules();
		foreach (array('contentTone', 'contentLanguage', 'customLanguage', 'targetAudience', 'brandVoice') as $key) {
			$val = isset($rules[$key]) ? trim((string) $rules[$key]) : '';
			if ($val === '' && isset($defaults[$key]) && $defaults[$key] !== '') {
				$rules[$key] = $defaults[$key];
			}
		}
		return $rules;
	}

	/**
	 * Resolve author display name by author ID.
	 * Returns empty string when ID is invalid or user is not found.
	 *
	 * @param mixed $author_id Author ID value to validate and resolve.
	 * @return string
	 */
	public static function getAuthorDisplayNameById($author_id)
	{

		if (! is_numeric($author_id)) {
			$output = '';
			return $output;
		}

		$author_id_int = (int) $author_id;
		if ($author_id_int <= 0) {
			$output = '';
			return $output;
		}

		$user = get_user_by('id', $author_id_int);
		if (! $user || empty($user->display_name)) {
			$output = '';
			return $output;
		}

		$output = trim((string) $user->display_name);
		return $output;
	}

	/**
	 * Resolve post status display name by status ID.
	 * Returns empty string when status does not exist.
	 *
	 * @param mixed $status_id Status key such as draft/publish.
	 * @return string
	 */
	public static function getPostStatusDisplayNameById($status_id)
	{

		$status_id = trim((string) $status_id);
		if ($status_id === '') {
			$output = '';
			return $output;
		}

		$status_object = get_post_status_object($status_id);
		if (! $status_object) {
			$output = '';
			return $output;
		}

		$status_label = '';
		if (isset($status_object->label)) {
			$status_label = trim((string) $status_object->label);
		}
		if ($status_label === '') {
			$status_label = $status_id;
		}

		$output = $status_label;
		return $output;
	}

	private static function ___________AJAX___________() {}

	/**
	 * output exception in a box
	 */
	public static function outputExceptionBox($e, $prefix = "")
	{

		$message = $e->getMessage();

		if (!empty($prefix))
			$message = $prefix . ":  " . $message;

		$trace = "";
		if (SheetsPilotGlobals::$showTrace || SheetsPilotGlobals::DEBUG_ERRORS == true)
			$trace = $e->getTraceAsString();

		$html = self::getErrorMessageHtml($message, $trace);
 
		echo wp_kses_post( $html );
	}

	/**
	 *
	 * echo json ajax response
	 */
	public static function ajaxResponse($success, $message, $arrData = null)
	{

		$response = array();
		$response["success"] = $success;
		$response["message"] = $message;

		if (!empty($arrData)) {

			if (gettype($arrData) == "string")
				$arrData = array("data" => $arrData);

			$response = array_merge($response, $arrData);
		}

		$response = SheetsPilotHelper::sanitize_utf8ize($response);
		
		wp_send_json($response);
	}

	/**
	 *
	 * echo json ajax response, without message, only data
	 */
	public static function ajaxResponseData($arrData)
	{
		if (gettype($arrData) == "string")
			$arrData = array("data" => $arrData);

		self::ajaxResponse(true, "", $arrData);
	}

	/**
	 *
	 * echo json ajax response
	 */
	public static function ajaxResponseError($message, $arrData = null)
	{

		self::ajaxResponse(false, $message, $arrData, true);
	}

	/**
	 * echo ajax success response
	 */
	public static function ajaxResponseSuccess($message, $arrData = null)
	{

		self::ajaxResponse(true, $message, $arrData, true);
	}

	/**
	 * echo ajax success response
	 */
	public static function ajaxResponseSuccessRedirect($message, $url)
	{
		$arrData = array("is_redirect" => true, "redirect_url" => $url);

		self::ajaxResponse(true, $message, $arrData, true);
	}

	/**
	 * sanitize bad characters
	 */
	public static function sanitize_utf8ize($mixed)
	{
		if (is_array($mixed)) {
			foreach ($mixed as $key => $value) {
				$mixed[$key] = SheetsPilotHelper::sanitize_utf8ize($value);
			}
		} elseif (is_string($mixed)) {
			return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
		}
		return $mixed;
	}

	/**
	 * verify if plugin installed and active
	 */
	public static function isPluginInstalledAndActive($plugin_slug)
	{
		// подключаем функции (если вдруг не в админке)
		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed_plugins = get_plugins();

		$is_installed = false;
		$plugin_file = '';

		// ищем по slug (папке)
		foreach ($installed_plugins as $file => $plugin) {
			if (strpos($file, $plugin_slug . '/') === 0) {
				$is_installed = true;
				$plugin_file = $file;
				break;
			}
		}

		if (!$is_installed) {
			return [
				'installed' => false,
				'active' => false,
			];
		}

		$is_active = is_plugin_active($plugin_file);

		return [
			'installed' => true,
			'active' => $is_active,
			'plugin_file' => $plugin_file
		];
	}

	/**
	 * Allowed tags for inline SVG markup.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function get_svg_allowed_tags() {
		return array(
			'svg'      => array(
				'xmlns'           => true,
				'width'           => true,
				'height'          => true,
				'viewbox'         => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'aria-hidden'     => true,
				'class'           => true,
				'style'           => true,
			),
			'path'     => array(
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'fill-rule'       => true,
				'clip-rule'       => true,
			),
			'rect'     => array(
				'x'            => true,
				'y'            => true,
				'width'        => true,
				'height'       => true,
				'rx'           => true,
				'ry'           => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
			),
			'circle'   => array(
				'cx'     => true,
				'cy'     => true,
				'r'      => true,
				'fill'   => true,
				'stroke' => true,
			),
			'polyline' => array(
				'points'          => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
			),
			'polygon'  => array(
				'points' => true,
				'fill'   => true,
				'stroke' => true,
			),
		);
	}

	/**
	 * wp_kses allowlist for plugin-built editor UI HTML (toolbars, popups, selects).
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function get_editor_ui_allowed_html() {
		static $allowed = null;

		if ( null !== $allowed ) {
			return $allowed;
		}

		$common_attrs = array(
			'class' => true,
			'id'    => true,
			'style' => true,
		);

		$allowed = array_merge(
			self::get_svg_allowed_tags(),
			array(
				'div'    => array_merge(
					$common_attrs,
					array(
						'data-title'      => true,
						'role'            => true,
						'aria-modal'      => true,
						'aria-labelledby' => true,
						'aria-hidden'     => true,
						'aria-label'      => true,
						'aria-expanded'   => true,
					)
				),
				'span'   => array_merge(
					$common_attrs,
					array(
						'data-title'        => true,
						'data-value'        => true,
						'data-posttypeurl'  => true,
						'data-posttypeicon' => true,
						'data-url'          => true,
						'role'              => true,
						'aria-label'        => true,
						'aria-hidden'       => true,
						'aria-haspopup'     => true,
						'aria-expanded'     => true,
					)
				),
				'select' => $common_attrs,
				'option' => array(
					'value'             => true,
					'selected'          => true,
					'data-posttypeurl'  => true,
					'data-posttypeicon' => true,
					'data-url'          => true,
				),
				'button' => array_merge(
					$common_attrs,
					array(
						'type'          => true,
						'title'         => true,
						'aria-label'    => true,
						'aria-haspopup' => true,
						'aria-expanded' => true,
					)
				),
				'input'  => array_merge(
					$common_attrs,
					array(
						'type'        => true,
						'value'       => true,
						'placeholder' => true,
						'min'         => true,
					)
				),
				'h2'     => $common_attrs,
				'p'      => $common_attrs,
			)
		);

		return $allowed;
	}

	/**
	 * Echo plugin-built HTML through wp_kses (escape late, preserve markup).
	 *
	 * @param string $html HTML from generate_* helpers or other trusted plugin output.
	 */
	public static function echo_escape_editor_html( $html ) {
		echo wp_kses( (string) $html, self::get_editor_ui_allowed_html() ); 
	}

	/**
	 * Sanitize inline SVG markup for safe output.
	 *
	 * @param string $svg Raw SVG HTML.
	 * @return string
	 */
	public static function sanitize_svg( $svg ) {
		return wp_kses( $svg, self::get_svg_allowed_tags() );
	}
}
