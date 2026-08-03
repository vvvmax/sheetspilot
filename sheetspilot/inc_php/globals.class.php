<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if(!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilotGlobals{
	
	const DEBUG_ERRORS = false; // When true, also enables PHP warning stack traces in SheetsPilot AJAX (admins only).
	
	public static $isPro = false;
	public static $hasProFolder = false;
	
	public static $debug_prompt_request = false;
	public static $debug_prompt_response = false;

	/** When true: generate_image response is cached for 1 hour; debug panel shows "Image response caching is ON" in red. */
	public static $enable_cache_image_response = false;

	/** When true (Pro only): show toolbar button that opens the AI requests panel. */
	public static $showPromptResultsToolbarButton = true;

	/** When true: AJAX requests are recorded via SheetsPilot_AjaxSessionLog (see inc_php/ajax_session_log.class.php). */
	public static $enableAjaxSessionLog = true;
	
	public static $showTrace = false; 
	
	public static $showDebugByUrl = false;
	
	public static $enableAutomateWorkflow = true;

	const PLUGIN_TITLE = "SheetsPilot";
	const PLUGIN_SLUG = "sheetspilot";
	const DIR_CACHE = "sheetspilot_ai_cache";
	const DEFAULT_VIEW = "welcome";
	const VIEW_WELCOME = "welcome";
	const VIEW_SETTINGS = "settings";
	const VIEW_POSTEDITOR = "postseditor";
	const VIEW_LOG = "log";
	const VIEW_PROMPT_HISTORY = "prompt_history";
	const VIEW_PROMPT_TESTER = "prompt_tester";
	const PROMPT_HISTORY_ROTATION = 30;
	/** Max request/response log rows to keep. */
	const REQUEST_LOG_KEEP = 100;
	/** Rotate only after this many extra rows beyond REQUEST_LOG_KEEP (e.g. keep 100, rotate at 110). */
	const REQUEST_LOG_ROTATION_BUFFER = 10;
	const OPTIONS_GROUP_NAME = "sheetspilot-settings"; 
	const OPTION_GENERAL_SETTINGS = "sheetspilot_general_settings";

	const PRO_VERSION_URL = "http://sheetspilot.ai";
	
	/** OpenAI Images API model (fixed; not exposed in plugin settings). */
	const OPENAI_IMAGE_MODEL = 'gpt-image-2';
	const OPENAI_RESPONSE_IMAGE_MODEL = 'gpt-image-1';
	/** Default image size when the request does not pass an explicit size (OpenAI Images API `size` or omit when `auto`). */
	const OPENAI_IMAGE_SIZE = 'auto';
	// Default image quality for the ubai_quality_selector (OpenAI Images API quality: low|medium|high).
	const DEFAULT_IMAGE_QUALITY = 'low';
	// Default image format for the ubai_format_selector (OpenAI Images API output_format: png|jpeg|webp).
	const DEFAULT_IMAGE_FORMAT = 'webp';
	/** Default output resolution tier for image sidebar / cell rules (1k|2k|3k|4k). */
	const DEFAULT_IMAGE_RESOLUTION = '1k';

	const OPTION_EDITOR_SETTINGS = "sheetspilot_editor_settings"; //namemod
	const OPTION_CONTENT_RULES = "ubai_content_rules";

	/** Stored in wp_options: "1" = Pro behavior, "0" = Free (dev/testing switch). */
	const OPTION_PRO_MODE = "unlimitedai_pro_mode_enabled";

	const POSTS_EDITOR_PAGINATION_POSTS_PER_PAGE = 100;

	/** Max apply_prompt requests running at once (image + text). */
	const APPLY_PROMPT_MAX_CONCURRENT = 4;

	const CHATGPT_MODEL = 'gpt-5.4-nano';
	const PROCESS_CONTENT_PROMPT = 'Use this JSON "%s" and translate each part from english to Ukrainnian. Please, return result as JSON without any extra markup';
	const APPLY_PROMPT_ASSISTANT_MESSAGE =
		'You are a content assistant for WordPress post content. You edit and improve post content based on the user request. '
		. 'You must respond with valid JSON in this exact format only: '
		. '{"type":"data","data":"<edited text>","instruction_summary":"<short summary>"}. '
		. "Rules: "
		. "(1) Output nothing before or after the JSON—no markdown, no code fences, no explanation. "
		. '(2) In "data" put the edited result as a single string; escape quotes and newlines for JSON '
		. '(e.g. \\n for newline, \\" for double quote). '
		. '(3) In "instruction_summary" put a short label in English '
		. '(e.g. "Summarize title", "Fix grammar", "Translate to Spanish") so it can be shown as a label later. '
		. "(4) If the content to edit is empty or missing, use the post_title from the post data provided in the user message "
		. "as the source, apply the user's instruction to it, and put the result in \"data\". "
		. "(5) Only if the user's instruction is completely unclear or not an editing task, "
		. "respond with a single short plain text sentence instead of JSON.";
		
	public static $capability = "manage_options";
	public static $pathPlugin;
	public static $pathPluginPro;
	public static $pathBase;
	public static $pathUploads;
	public static $pathCache;
	public static $pathViews;
	
	public static $urlBase;
	public static $urlAjax;
	public static $urlPlugin;
	public static $urlPluginPro;
	public static $urlImages;
	public static $urlImagePlaceholder;
	
	public static $isAdmin;

	// messages
 
	public static $selectPost;
	public static $editPostTooltipText;
	public static $previewPostText;
	public static $addTaxonomyText;
	public static $addGalleryImageText;
	public static $dropGalleryText;
	public static $dropRepeaterText;
	public static $editRepeaterText;
 
 
	public static $editWyswygText;
	public static $wyswygSave;
	public static $newPostTitle;
	public static $deletePostText;
	public static $deleteImageText;
	 
	public static $addNewRow;
	public static $editColumnsOrderAndVisisbility;
	public static $duplicatePost;
	 
	public static $addImageText;
	public static $inlineEditImageText;
	public static $downloadImageText;
 
	public static $undoActionText;
	public static $editPostInNewWindowTooltipText;
	public static $editPostInElementorTooltipText;

	/** Context menu: command (action) => prompt text for the cell context menu. */
	public static $contextMenuPrompts = array();

	public static $dbPrefix;
	public static $tablePosts, $tableLogs, $tablePrompts, $tableSavedPrompts;
	public static $enableCopy = false;
	public static $enablePaste = false;

	//custom fields from RANK MATH
	public static $rankMathFields = [];
	public static $yoastFields = [];
	public static $wooCommerceFields = [];
	public static $theEventsCalendarFileds = [];
	public static $seoPress = [];

	public static $isElementorActive = false;

	//media extra fields
	public static $mediaPostTypeFields = [];

	// PRO fields
	public static $proFilesList;

	//editor script localization
	public static $editorScriptLocalization;
		

	
	public static function hasProFolder(){

		$output = ( defined( 'SHEETSPILOT_HAS_PRO_FOLDER' ) && SHEETSPILOT_HAS_PRO_FOLDER === true );
		return $output;
	}

	/**
	 * Set {@see self::$isPro} from wp_options (after options load).
	 */
	public static function refreshProModeFromOption(){

		if ( self::hasProFolder() === false ) {
			self::$isPro = false;
			return;
		}

		if ( defined( 'SHEETSPILOT_PRO_PLUGIN_ACTIVE' ) && SHEETSPILOT_PRO_PLUGIN_ACTIVE === true ) {
			self::$isPro = true;
			return;
		}

		$stored = get_option( self::OPTION_PRO_MODE, null );

		if ( $stored === null ) {
			self::$isPro = true;
			return;
		}

		self::$isPro = ( $stored === '1' || $stored === 1 || $stored === true );
	}

	
	/**
	 * When general settings enable debug request, set SheetsPilotGlobals::$debug_prompt_request.
	 */
	private static function applyDebugPromptRequestFromSettings() {
		if ( ! class_exists( 'SheetsPilotHelper' ) || ! class_exists( 'SheetsPilot_PluginGeneralSettings' ) ) {
			return;
		}

		//bushfix to remove error
		if ( ! class_exists( 'SheetsPilot_PluginGeneralSettings' ) ) {
			return;
		}

		$settings = SheetsPilotHelper::getGeneralSettings();
		$enabled  = isset( $settings['enable_debug_prompt_request'] ) ? (string) $settings['enable_debug_prompt_request'] : '0';
		if ( '1' === $enabled ) {
			self::$debug_prompt_request = true;
		}
	}

	/**
	 * When an admin passes showdebug=true (URL query or AJAX POST), turn on debug flags.
	 */
	private static function applyShowDebugFromUrl() {
		if ( ! current_user_can( self::$capability ) ) {
			return;
		}

		$showdebug = SheetsPilotFunctions::getGetVar( 'showdebug', '', SheetsPilotFunctions::SANITIZE_TEXT_FIELD );
		if ( false === $showdebug || '' === $showdebug ) {
			$showdebug = SheetsPilotFunctions::getPostGetVariable( 'showdebug', '', SheetsPilotFunctions::SANITIZE_TEXT_FIELD );
		}
		if ( false === $showdebug ) {
			return;
		}

		if ( self::isShowdebugRequestValueEnabled( $showdebug ) ) {
			self::$showDebugByUrl       = true;
			self::$debug_prompt_request = true;
		}
	}

	/**
	 * When an admin passes showtrace=true (URL query), enable PHP stack traces
	 * (SheetsPilot AJAX; Pro also enables site-wide PHP error traces).
	 */
	private static function applyShowTraceFromUrl() {
		if ( ! current_user_can( self::$capability ) ) {
			return;
		}

		$showtrace = SheetsPilotFunctions::getGetVar( 'showtrace', '', SheetsPilotFunctions::SANITIZE_TEXT_FIELD );
		if ( false === $showtrace || '' === $showtrace ) {
			$showtrace = SheetsPilotFunctions::getPostGetVariable( 'showtrace', '', SheetsPilotFunctions::SANITIZE_TEXT_FIELD );
		}
		if ( false === $showtrace ) {
			return;
		}

		if ( self::isShowdebugRequestValueEnabled( $showtrace ) ) {
			self::$showTrace = true;
		}
	}

	/**
	 * @param mixed $value Raw showdebug value from request.
	 */
	private static function isShowdebugRequestValueEnabled( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( (string) $value ), array( 'true', '1' ), true );
	}

	/**
	 * init the globals
	 */
	public static function initGlobals(){

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		WP_Filesystem();
		global $wp_filesystem;	

		//paths
		self::$pathPlugin = SheetsPilotFunctions::pathToUnix( SHEETS_PLUGIN_DIR );
 
		if ( SHEETS_PLUGIN_IS_GLOBAL_DEV ) {
			self::$pathPluginPro = SheetsPilotFunctions::pathToUnix( SHEETS_PLUGIN_DIR . 'pro/' );
			self::$urlPluginPro  = plugin_dir_url( SHEETS_PLUGIN_FILE ) . 'pro/';
		} else {
			self::$pathPluginPro = SheetsPilotFunctions::pathToUnix( plugin_dir_path( SHEETS_PRO_PLUGIN_FILE ) );
			self::$urlPluginPro  = plugin_dir_url( SHEETS_PRO_PLUGIN_FILE );
		}
		

		// verify if elementor active
		if ( is_plugin_active('elementor/elementor.php') ) {
			self::$isElementorActive = true;
		}
		
		
		self::$pathViews = self::$pathPlugin."views/";

		$arrUploadDir = wp_upload_dir();		
		self::$pathUploads = $arrUploadDir["basedir"]."/";
		
		//cache path
		self::$pathCache = self::$pathUploads.self::DIR_CACHE."/";
		if(is_dir(self::$pathCache) == false){
			@$wp_filesystem->mkdir( self::$pathCache, FS_CHMOD_DIR);
			
			if(is_dir(self::$pathCache) == false)
				self::$pathCache = self::$pathPlugin."cache/";
		}
		
		//urls
		self::$urlPlugin = SHEETS_PLUGIN_URL;
		
 
		self::$urlImages = self::$urlPlugin."assets/images/";
		self::$urlImagePlaceholder = plugins_url('/assets/images/placeholder.png',  dirname(__FILE__) );
			
		self::$urlAjax = admin_url("admin-ajax.php");
		self::$urlBase = site_url()."/";
		
		self::$isAdmin = is_admin();
		self::$hasProFolder = self::hasProFolder();

		self::refreshProModeFromOption();

		self::applyDebugPromptRequestFromSettings();

		// apply show debug from url (overrides general setting when showdebug=true)
		self::applyShowDebugFromUrl();

		// apply PHP stack trace for SheetsPilot AJAX when showtrace=true
		self::applyShowTraceFromUrl();


		//init tables
		global $wpdb;
		self::$dbPrefix = $wpdb->prefix;
		
		self::$tablePosts = self::$dbPrefix."posts";
		self::$tableLogs = self::$dbPrefix."unlimited_ai_logs";
		self::$tablePrompts = self::$dbPrefix."unlimited_ai_prompts";
		self::$tableSavedPrompts = self::$dbPrefix."unlimited_ai_saved_prompts";
		
		// textual variables
		 
		self::$selectPost =  __("Select Post", 'sheetspilot' );
		self::$editPostTooltipText =  __("Edit post in popup", 'sheetspilot' );
		self::$previewPostText =  __("View", 'sheetspilot' );
		self::$addTaxonomyText =  __("Add Taxonomy", 'sheetspilot' );
		self::$addGalleryImageText =  __("Add Gallery Image", 'sheetspilot' );
		self::$editRepeaterText =  __("Edit Repeater Field", 'sheetspilot' );
		
	 
		self::$deleteImageText =  __("Delete Image", 'sheetspilot');
	 
		self::$dropGalleryText =  __("Delete Images", 'sheetspilot' );
		self::$dropRepeaterText =  __("Drop repeater", 'sheetspilot' );
		self::$editWyswygText =  __("Edit WYSIWYG Field", 'sheetspilot' );
		self::$wyswygSave =  __("Save and Close", 'sheetspilot' );
		self::$newPostTitle =  __("New", 'sheetspilot' );
		self::$deletePostText =  __("Delete", 'sheetspilot' );
		
		self::$addNewRow =  __("Add Row", 'sheetspilot' );
		self::$editColumnsOrderAndVisisbility =  __("Field visibility", 'sheetspilot' );
		self::$duplicatePost =  __("Duplicate", 'sheetspilot' );
		
		self::$addImageText =  __("Add/Edit Image", 'sheetspilot' );
		self::$inlineEditImageText =  __("Inline Edit Image", 'sheetspilot' );
		self::$downloadImageText =  __("Download Image", 'sheetspilot' );
		
		self::$undoActionText =  __("Undo (Ctrl+Z)", 'sheetspilot' );
		self::$editPostInNewWindowTooltipText =  __("Edit in new window", 'sheetspilot' );
		self::$editPostInElementorTooltipText = __( 'edit in elementor', 'sheetspilot' );

		// Context menu prompts: command => prompt text (key-value only)
		self::$contextMenuPrompts = array(
			'change-length-shorten' => __( 'Make this text shorter while preserving the main message and key information.', 'sheetspilot'),
			'change-length-expand'  => __( 'Expand this text with more detail while keeping the same tone and message.', 'sheetspilot' ),
			'translate-en'          => __( 'Translate the following text to English.', 'sheetspilot' ),
			'translate-es'          => __( 'Translate the following text to Spanish.', 'sheetspilot' ),
			'translate-fr'          => __( 'Translate the following text to French.', 'sheetspilot' ),
			'translate-de'          => __( 'Translate the following text to German.', 'sheetspilot' ),
			'translate-it'          => __( 'Translate the following text to Italian.', 'sheetspilot' ),
			'translate-pt'          => __( 'Translate the following text to Portuguese.', 'sheetspilot' ),
			'translate-zh'          => __( 'Translate the following text to Chinese.', 'sheetspilot' ),
			'translate-ja'          => __( 'Translate the following text to Japanese.', 'sheetspilot' ),
			'translate-ko'          => __( 'Translate the following text to Korean.', 'sheetspilot' ),
			'translate-ar'          => __( 'Translate the following text to Arabic.', 'sheetspilot' ),
			'translate-he'          => __( 'Translate the following text to Hebrew.', 'sheetspilot' ),
			'translate-ru'          => __( 'Translate the following text to Russian.', 'sheetspilot' ),
			'improve-text'          => __( 'Improve this text: make it clearer, more engaging, and better structured. Keep the same meaning and tone.', 'sheetspilot' ),
			'optimize-seo'          => __( 'Optimize this text for SEO: improve headings, keywords, and readability while keeping it natural.', 'sheetspilot' ),
			'fix-grammar'           => __( 'Fix grammar, spelling, and punctuation in the following text. Keep the same meaning and style.', 'sheetspilot' ),
			'generate-image'        => __( 'Generate a new featured image for this post based on its content and context.', 'sheetspilot' ),
			'enhance-image'         => __( 'Enhance this image while preserving the exact original photograph. This is a photo restoration and quality enhancement task, NOT an image generation task. Preserve 100%: original composition, original camera angle, original framing and crop, original people and identities, original facial features, original clothing, original objects and their positions, original food presentation, original architecture and background, original lighting direction, original mood and atmosphere. Improve only: sharpness and fine details, noise and grain reduction, dynamic range, shadow recovery, highlight recovery, color accuracy, local contrast, texture clarity, overall image quality. Apply: professional DSLR quality, selective subject sharpening, natural skin tones, clean whites, rich but realistic colors, premium Instagram travel photography look, subtle depth enhancement, micro contrast enhancement, high-end travel influencer aesthetic. Do NOT: change faces, change expressions, change body shapes, change clothing, add or remove objects, change composition, change food styling, change architecture, change weather, change time of day, change colors dramatically, create new details that were not present. The final result must look like the exact same photo captured with a professional full-frame camera and premium lens, not a newly generated image. Ultra realistic, natural, authentic, high-resolution, crisp detail, clean image, 4K quality.', 'sheetspilot' ),
			'apply_column_rules'    => __( 'Apply the saved AI column rules to this cell. Return only the new value for this column.', 'sheetspilot' ),
			'restore-previous'      => '',
		);

		// yast fields
		if( SheetsPilotHelper::isPluginInstalledAndActive( 'wordpress-seo' )['installed'] && SheetsPilotHelper::isPluginInstalledAndActive( 'wordpress-seo' )['active'] )
		self::$yoastFields = [

			// Basic SEO
			'_yoast_wpseo_title' => [ 
				'label' => __('SEO Title', 'sheetspilot'),
				'type' => 'textarea'
				],
			'_yoast_wpseo_metadesc' => [ 
				'label' =>  __('Meta Description', 'sheetspilot' ),
				'type' => 'textarea'
			],
			'_yoast_wpseo_focuskw' => [ 
				'label' =>  __('Focus Keyphrase', 'sheetspilot' ),
				'type' => 'textarea'
			],
		];

		// randmath init
		if( SheetsPilotHelper::isPluginInstalledAndActive( 'seo-by-rank-math' )['installed'] && SheetsPilotHelper::isPluginInstalledAndActive( 'seo-by-rank-math' )['active'] )
		self::$rankMathFields = [

			// Basic SEO
			'rank_math_title' => [ 
				'label' => __('Title', 'sheetspilot'),
				'type' => 'textarea'
				],
			'rank_math_description' => [ 
				'label' =>  __('Description', 'sheetspilot' ),
				'type' => 'textarea'
			],
			'rank_math_focus_keyword' => [ 
				'label' =>  __('Focus Keyword', 'sheetspilot' ),
				'type' => 'textarea'
			],

		];

		//media files data
		self::$mediaPostTypeFields = [
 
			'_visual_output' => [ 
				'label' =>  __('Attachment Preview', 'sheetspilot' ),
				'dev_type'        => 'meta_field',
				'type' => 'htmlout'
			],
			'_wp_attachment_image_alt' => [ 
				'label' =>  __('Alt text', 'sheetspilot' ),
				'dev_type'        => 'meta_field',
				'type' => 'text'
			],

		];


		// the events calendar
		if( SheetsPilotHelper::isPluginInstalledAndActive( 'the-events-calendar' )['installed'] && SheetsPilotHelper::isPluginInstalledAndActive( 'the-events-calendar' )['active'] )
		self::$theEventsCalendarFileds = [

			// Basic SEO
			'_EventStartDate' => [ 
				'label' => __('Start Date', 'sheetspilot'),
				'dev_type'        => 'meta_field',
				'type' => 'calendar'
				],
			'_EventEndDate' => [ 
				'label' =>  __('End Date', 'sheetspilot' ),
				'dev_type'        => 'meta_field',
				'type' => 'calendar'
			],
			'_EventAllDay' => [ 
				'label' =>  __('All Day Event', 'sheetspilot' ),
				'dev_type'        => 'meta_field',
				'type' => 'switch',
				'source' => [	 
					['id' => '0',   'name' => __('No', 'sheetspilot' ) ],
					['id' => 'yes', 'name' => __('Yes', 'sheetspilot' ) ],					
				],
			],
			'_EventCost' => [ 
				'label' =>  __('Event Cost', 'sheetspilot' ),
				'dev_type'        => 'meta_field',
				'type' => 'text'
			],
			'_EventURL' => [ 
				'label' =>  __('Event Website', 'sheetspilot' ),
				'dev_type'        => 'meta_field',
				'type' => 'text'
			],
			'_EventVenueID' => [ 
				'label' =>  __('Venue', 'sheetspilot' ),
				'dev_type'        => 'meta_field',
				'type' => 'acf_select',
				'source' => SheetsPilotHelper::getPostTypeList('tribe_venue')
			],
			'_EventOrganizerID' => [ 
				'label' =>  __('Organizer', 'sheetspilot' ),
				'dev_type'        => 'meta_field',
				'type' => 'acf_select',
				'source' => SheetsPilotHelper::getPostTypeList('tribe_organizer')
			],
			'_tribe_featured' => [ 
				'label' =>  __('Featured Event', 'sheetspilot' ),
				'dev_type'        => 'meta_field',
				'type' => 'switch',
				'source' => [	 
					['id' => '0',   'name' => __('No', 'sheetspilot' ) ],
					['id' => '1', 'name' => __('Yes', 'sheetspilot' ) ],					
				],
			],

		];

		// seopress
		if( SheetsPilotHelper::isPluginInstalledAndActive( 'wp-seopress' )['installed'] && SheetsPilotHelper::isPluginInstalledAndActive( 'wp-seopress' )['active'] )
		self::$seoPress = [

			// Basic SEO
			'_seopress_titles_title' => [ 
				'label' => __('Title', 'sheetspilot'),
				'type' => 'textarea'
				],
			'_seopress_titles_desc' => [ 
				'label' =>  __('Description', 'sheetspilot' ),
				'type' => 'textarea'
			],
		];


		// get woo fields
		if( SheetsPilotHelper::isPluginInstalledAndActive( 'woocommerce' )['installed'] && SheetsPilotHelper::isPluginInstalledAndActive( 'woocommerce' )['active'] )
		self::$wooCommerceFields = [

			'_stock_status' => [ 
				'label' =>  __('Stock Status', 'sheetspilot' ),
				'type' => 'acf_select',
				'dev_type'        => 'meta_field',
				'column_search' => 'filter',
				'source' => [
		 
					['id' => 'instock',   'name' => __('In stock', 'sheetspilot') ],
					['id' => 'outofstock', 'name' => __('Out of stock', 'sheetspilot' ) ],
					['id' => 'onbackorder',  'name' => __('On backorder', 'sheetspilot' ) ],	
				],
			],
			'_featured' => [ 
				'label' =>  __('Featured', 'sheetspilot' ),
				'type' => 'switch',
				'dev_type'        => 'meta_field',
				'column_search' => 'filter',
				'source' => [	 
					['id' => 'no',   'name' => __('No', 'sheetspilot' ) ],
					['id' => 'yes', 'name' => __('Yes', 'sheetspilot' ) ],					
				],
			],
			'_manage_stock' => [ 
				'label' =>  __('Manage Stock', 'sheetspilot' ),
				'type' => 'switch',
				'dev_type'        => 'meta_field',
				'column_search' => 'filter',
				'source' => [	 
					['id' => 'no',   'name' => __('No', 'sheetspilot' ) ],
					['id' => 'yes', 'name' => __('Yes', 'sheetspilot' ) ],			
				],
				'related_editor_fields' => [
			 
					[
						'editor_type' => 'section_title',
						'title' => __('Inventory', 'sheetspilot' ),
		
					],
					[
						'editor_type' => 'numerical',
						'data_table' => 'postmeta',
						'title' => __('Quantity', 'sheetspilot' ),
						'name' => '_stock',
						'placeholder' => '',
						'subtitle' => __('Stock quantity. If this is a variable product this value will be used to control stock for all variations, unless you define stock at variation level.', 'sheetspilot'),
					],
					[
						'editor_type' => 'numerical',
						'data_table' => 'postmeta',
						'title' => __('Low Stock Threshold', 'sheetspilot' ),
						'name' => '_low_stock_amount',
						'placeholder' => '',
						'subtitle' => __('When product stock reaches this amount you will be notified by email. It is possible to define different values for each variation individually. The shop default value can be set in Settings > Products > Inventory.', 'sheetspilot'),
					],
				
					 
				]
			],
			'_backorders' => [ 
				'label' =>  __('Backorders', 'sheetspilot'),
				'type' => 'acf_select',
				'dev_type'        => 'meta_field',
				'column_search' => 'filter',
				'source' => [	 
					['id' => 'no',   'name' => __('Do not allow', 'sheetspilot')],
					['id' => 'notify', 'name' => __('Allow, but notify customer', 'sheetspilot')],					
					['id' => 'yes', 'name' => __('Allow', 'sheetspilot')],					
				],
			],
			'_product_visibility' => [ 
				'label' =>  __('Product Visibility', 'sheetspilot'),
				'type' => 'acf_select',
				'dev_type'        => 'taxonomy',
				'column_search' => 'filter',
				'source' => [	 
					['id' => 'visible',   'name' => __('Catalog & search', 'sheetspilot')],
					['id' => 'catalog', 'name' => __('Catalog', 'sheetspilot')],					
					['id' => 'search', 'name' => __('Search', 'sheetspilot')],					
					['id' => 'hidden', 'name' => __('Hidden', 'sheetspilot')],					
				],
			],
			'_upsell_ids' => [ 
				'label' =>  __('Upsells', 'sheetspilot'),
				'type' => 'woo_post_object',
				'dev_type'        => 'meta_field',
				'column_search' => false,
				'search_post_type' => 'product',
			 
			],
			'_crosssell_ids' => [ 
				'label' =>  __('Cross-sells', 'sheetspilot'),
				'type' => 'woo_post_object',
				'dev_type'        => 'meta_field',
				'column_search' => false,
			 	'search_post_type' => 'product',
			],
			'_children' => [ 
				'label' =>  __('Grouped products', 'sheetspilot'),
				'type' => 'woo_post_object',
				'dev_type'        => 'meta_field',
				'column_search' => false,
			 	'search_post_type' => 'product',
			],
			'_virtual' => [ 
				'label' =>  __('Virtual', 'sheetspilot'),
				'type' => 'switch',
				'dev_type'        => 'meta_field',
				'column_search' => 'filter',
				'source' => [	 
					['id' => 'no',   'name' => __('No', 'sheetspilot' ) ],
					['id' => 'yes', 'name' => __('Yes', 'sheetspilot' ) ],						
				],
			],
			'attributes' => [ 
				'label' =>  __('Attributes', 'sheetspilot'),
				'type' => 'product_attribute',
				'dev_type'        => 'taxonomy',
				'column_search' => false,
				'readonly' => true,
	 			'bottom_manage'	 => '<span class="edit_product_attribute has-tooltip" data-title="'.__('Edit attributes', 'sheetspilot' ).'"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>'
			],
			'_downloadable' => [ 
				'label' =>  __('Downloadable',  'sheetspilot' ),
				'type' => 'switch',
				'dev_type'        => 'taxonomy',
				'column_search' => 'filter',
				'source' => [	 
					['id' => 'no',   'name' => __('No', 'sheetspilot' ) ],
					['id' => 'yes', 'name' => __('Yes', 'sheetspilot' ) ],						
				],
				'related_editor_fields' => [
			 		[
						'editor_type' => 'section_title',
						'title' => __('Downloadable settings', 'sheetspilot' ),
						
					],
					[
						'editor_type' => 'number',
						'data_table' => 'postmeta',
						'title' => __('Download expiry', 'sheetspilot' ),
						'name' => '_download_expiry',
						'placeholder' => __('Never expire', 'sheetspilot' ),
						'subtitle' => __('Leave empty for no expiration', 'sheetspilot' ),
					],
					[
						'editor_type' => 'number',
						'data_table' => 'postmeta',
						'title' => __('Download limit', 'sheetspilot' ),
						'name' => '_download_limit',
						'placeholder' => __('Unlimited', 'sheetspilot' ),
						'subtitle' => __('Leave empty for unlimited downloads', 'sheetspilot' ),
					],

					[
						'editor_type' => 'section_title',
						'title' => __('Downloadable files', 'sheetspilot' ),
						'extra_right' => '<div class="file_counter_output"></div>'
					],
	 
					[
						'editor_type' => 'file_downloads',
						'data_table' => 'postmeta',
						'title' => __('Downloadable files', 'sheetspilot' ),
						'name' => '_low_stock_amount',
						'placeholder' => '',
						'subtitle' => '',
					],
				
					 
				]
			],
			
			'_sold_individually' => [ 
				'label' =>  __('Sold Individually',  'sheetspilot' ),
				'type' => 'switch',
				'dev_type'        => 'meta_field',
				'column_search' => 'filter',
				'source' => [	 
					['id' => 'no',   'name' => __('No', 'sheetspilot' ) ],
					['id' => 'yes', 'name' => __('Yes', 'sheetspilot' ) ],					
				],
			],
			'_regular_price' => [ 
				'label' =>  __('Regular Price', 'sheetspilot' ),
				'type' => 'text',
				'column_search' => 'text',

			],
			'_sale_price' => [ 
				'label' =>  __('Sale Price', 'sheetspilot' ),
				'type' => 'text',
				'column_search' => 'text',
			],
		
			'_visible_in_pos' => [ 
				'label' =>  __('Visible in POS',  'sheetspilot' ),
				'type' => 'switch',
				'dev_type'        => 'meta_field',
				'column_search' => 'filter',
				'source' => [	 
					['id' => 'no',   'name' => __('No', 'sheetspilot' ) ],
					['id' => 'yes', 'name' => __('Yes', 'sheetspilot' ) ],					
				],
			],
			'product_type' => [ 
				'label' =>  __('Product type',  'sheetspilot' ),
				'type' => 'acf_select',
				'dev_type'        => 'taxonomy',
				'column_search' => 'filter',
				'source' => [	 
					[
						'id' => 'simple',   
						'name' => __('Simple', 'sheetspilot' ),
						'block_columns' => [ '_children' ],
						'show_columns' => [ '_crosssell_ids', '_upsell_ids'  ],
						],
					[
						'id' => 'variable', 
						'name' => __('Variable', 'sheetspilot' ),
						'block_columns' => [ '_children' ],
						'show_columns' => [ '_crosssell_ids', '_upsell_ids'  ],
					],					
					[
						'id' => 'grouped', 
						'name' => __('Grouped', 'sheetspilot' ),
						'block_columns' => [ '_crosssell_ids' ],
						'show_columns' => [ '_children', '_upsell_ids'  ],
					],					
					[
						'id' => 'external', 
						'name' => __('External', 'sheetspilot' ),
						'block_columns' => [ '_children', '_crosssell_ids' ],
						'show_columns' => [ '_upsell_ids' ],
					],					
				],
				'has_multirelations' => true,
				'relation_external' => [
			 		[
						'editor_type' => 'section_title',
						'title' => __('Link to an external product page. Visitors will be directed to the provided URL to make their purchase.', 'sheetspilot' ),
						
					],
			 
					[
						'editor_type' => 'text',
						'data_table' => 'postmeta',
						'title' => __('Product URL', 'sheetspilot' ),
						'name' => '_product_url',
						'placeholder' => __('http://', 'sheetspilot' ),
						'subtitle' => __('The destination URL where customers will be sent to view or purchase this product.', 'sheetspilot' ),
					],
					[
						'editor_type' => 'text',
						'data_table' => 'postmeta',
						'title' => __('Button text', 'sheetspilot' ),
						'name' => '_button_text',
						'placeholder' => __('Buy product', 'sheetspilot' ),
						'subtitle' => __('The label displayed on the call-to-action button that links to the external product.', 'sheetspilot' ),
					],	 
				],
				'relation_grouped' => [
			 		 
			 
					[
						'editor_type' => 'grouped_functionality',
						'data_table' => 'postmeta',
				 
						'name' => '_product_url',
						 
					],
					 	 
				],
				'relation_variable' => [
			 		 
			 
					[
						'editor_type' => 'variable_functionality',
						'data_table' => 'postmeta',		 
						'name' => '_product_url',					 
					],
					 	 
				],
			],

			'_sku' => [ 
				'label' =>  __('SKU', 'sheetspilot' ),
				'type' => 'text',
				'column_search' => 'text',

			],
			'_global_unique_id' => [ 
				'label' =>  __('GTIN, UPC, EAN, or ISBN', 'sheetspilot' ),
				'type' => 'text',
				'column_search' => 'text',

			],
			'_product_image_gallery' => [ 
				'label' =>  __('Product Gallery', 'sheetspilot' ),
				'type' => 'acf_woo_gallery',
				'dev_type' => 'gallery',
			 	'bottom_manage' =>
					'<span class="add_gallery_image has-tooltip" data-title="' . SheetsPilotGlobals::$addGalleryImageText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ><path d="M5 12h14"></path><path d="M12 5v14"></path></svg></span>
					<span class="delete_all_images has-tooltip" data-title="' . SheetsPilotGlobals::$dropGalleryText . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-3.5 h-3.5 text-muted-foreground"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg></span>'
			],

			/*
			'_sku' => __('Sku', 'sheetspilot'),
			'_regular_price' => __('Regular Price', 'sheetspilot'),
			'_sale_price' => __('Sale Price', 'sheetspilot'),
			'_price' => __('Price', 'sheetspilot'),

			'_stock' => __('Stock', 'sheetspilot'),

			
			'_weight' => __('Weight', 'sheetspilot'),
			'_length' => __('Length', 'sheetspilot'),
			'_width' => __('Width', 'sheetspilot'),
			'_height' => __('Height', 'sheetspilot'),

			
			'_download_limit' => __('Download Limit', 'sheetspilot'),
			'_download_expiry' => __('Download Expiry', 'sheetspilot'),

			

			'_purchase_note' => __('Purchase Note', 'sheetspilot'),

			'_featured' => __('Featured', 'sheetspilot'),

			

			'_upsell_ids' => __('Upsell Ids', 'sheetspilot'),
			'_crosssell_ids' => __('Crosssell Ids', 'sheetspilot'),

			'_product_attributes' => __('Product Attributes', 'sheetspilot'),
			'_default_attributes' => __('Default Attributes', 'sheetspilot'),
			*/
		];

		// mark pro fields
		self::$proFilesList = [ 'acf_gallery', 'repeater', 'post_object' ];

		if( get_option( 'woocommerce_calc_taxes' ) == 'yes' ){
			self::$wooCommerceFields['_tax_status'] = [ 
				'label' =>  __('Tax Status', 'sheetspilot' ),
				'type' => 'acf_select',
				'dev_type'        => 'meta_field',
				'column_search' => 'filter',
				'source' => [	 
					['id' => 'taxable',   'name' => __('Taxable', 'sheetspilot' ) ],
					['id' => 'shipping', 'name' => __('Shipping only', 'sheetspilot' ) ],					
					['id' => 'none', 'name' => __('None', 'sheetspilot' ) ],					
				],
			];
			self::$wooCommerceFields['_tax_class'] = [ 
				'label' =>  __('Tax Class', 'sheetspilot' ),
				'type' => 'acf_select',
				'dev_type'        => 'meta_field',
				'column_search' => 'filter',
				'source' => [	 
					['id' => '',   'name' => __('Standard', 'sheetspilot' ) ],
					['id' => 'reduced-rate', 'name' => __('Reduced rate', 'sheetspilot') ],					
					['id' => 'zero-rate', 'name' => __('Zero rate', 'sheetspilot' ) ],					
				],
			];

		}


		// initiate editor localization
		self::$editorScriptLocalization = self::buildEditorScriptLocalization();
	}

	/**
	 * Editor script strings and assets passed to sheetspilot.editor in JS.
	 *
	 * @return array<string, mixed>
	 */
	private static function buildEditorScriptLocalization() {
		return [
			'isPro' => self::$isPro,
			'enableAutomateWorkflow' => self::$enableAutomateWorkflow,
			'showPromptResultsToolbarButton' => self::$isPro && self::$showPromptResultsToolbarButton,
			'pendingPromptResultsToolbar' => __( 'Open pending prompt results', 'sheetspilot' ),
			'nextPendingPromptResult' => __( 'Next pending prompt result', 'sheetspilot' ),
			'pro' => __( 'Pro', 'sheetspilot'  ),
			'duplicate_row' => __( 'Duplicate Row', 'sheetspilot'  ),
			'number_of_copies' => __( 'Number of copies', 'sheetspilot'  ),
			'enter_number' => __( 'Enter number...', 'sheetspilot'  ),
			'duplicate' => __( 'Duplicate', 'sheetspilot'  ),

			'rename_column' => __( 'Rename Column', 'sheetspilot'  ),
			'new_column_name' => __( 'New Column Name', 'sheetspilot'  ),
			'enter_column_name' => __( 'Enter Column Name', 'sheetspilot'  ),
			 
			'hide_column' => __( 'Hide column', 'sheetspilot'  ),
			'reset_width' => __( 'Reset Width', 'sheetspilot'  ),

			'ai_column_settings' => __( 'AI Column Settings', 'sheetspilot'  ),

			'select' => __( 'Select', 'sheetspilot'  ),
			'bulk' => __( 'Bulk', 'sheetspilot'  ),
			'search_status' => __( 'Search status...', 'sheetspilot'  ),
			'search' => __( 'Search', 'sheetspilot'  ),
			'cancel' => __( 'Cancel', 'sheetspilot'  ),
			'apply_to' => __( 'Apply to', 'sheetspilot'  ),
			'rows' => __( 'rows', 'sheetspilot'  ),
			'affected' => __( 'Affected:', 'sheetspilot'  ),
			'please_select_a_cell' => __( "Please select a cell first.", 'sheetspilot'  ),

			'apply_prompt_text_1' => __( 'Apply prompt did not return replacement text.', 'sheetspilot'  ),
			
			'collapse_ai_sidebar' => __( 'Collapse AI Sidebar', 'sheetspilot'  ),
			'expand_ai_sidebar' => __( 'Expand AI Sidebar', 'sheetspilot'  ),
			
			'search_post_types' => __( 'Search post types...', 'sheetspilot'  ),
			'bulk_actions' => __( "Bulk Actions", 'sheetspilot'  ),
			'automate_workflow' => __( 'Automate Workflow', 'sheetspilot' ),

			'ok' => __( "OK", 'sheetspilot'  ),
			'sort_a_to_z' => __( "Sort A to Z", 'sheetspilot'  ),
			'sort_z_to_a' => __( "Sort Z to A", 'sheetspilot'  ),
			'sort_old_to_new' => __( "Sort old to new", 'sheetspilot'  ),
			'sort_new_to_old' => __( "Sort new to old", 'sheetspilot'  ),
			'filter_by_date' => __( "Filter by date", 'sheetspilot'  ),
			'filter_by_values' => __( "Filter by values", 'sheetspilot'  ),
			'select_all' => __( "Select All", 'sheetspilot'  ),
			'loading' => __( "Loading...", 'sheetspilot'  ),
			'drawer_loader_icon_url' => self::$urlImages . 'drawer-loader-icon.svg',
			'save' => __( "Save", 'sheetspilot'  ),
			'save_and_close' => __( "Save and Close", 'sheetspilot'  ),
			'stock_management_settings' => __( 'Stock management settings', 'sheetspilot'  ),
			'downloadable_subtitle' => __( 'Downloadable files & settings', 'sheetspilot'  ),
			'edit_image' => __( 'Edit Image', 'sheetspilot'  ),
			'copy_to_clipboard' => __( 'Copy to clipboard', 'sheetspilot'  ),
			'no_posts_found' => __("No Posts Found", 'sheetspilot' ),
			'images' =>  __("images", 'sheetspilot'),
			'image' =>  __("image", 'sheetspilot'),
			'are_you_sure_delete' =>  __("Are you sure, you want to delete post?", 'sheetspilot' ),
			'delete_image' =>  __("Delete Image", 'sheetspilot' ),
			'wrong_cell_type' => __("Wrong Cell Type", 'sheetspilot' ),
			'locate_column' => __("Locate column", 'sheetspilot' ),
			'fileupload_placeholder_h1' => __("No files added yet", 'sheetspilot' ),
			'fileupload_placeholder_h2' => __("Add fiels that customer can download after purchase", 'sheetspilot' ),
			'add_file' => __("Add file", 'sheetspilot' ),
			'file_x' => __("File %", 'sheetspilot' ),
			'file_name' => __("File Name", 'sheetspilot' ),
			'file_url' => __("File URL", 'sheetspilot' ),
			'file_url_placeholder' => __("https://site.com/file.pdf", 'sheetspilot' ),
			'file_name_placeholder' => __("e.g. Product Manual", 'sheetspilot' ),
			'file_single' => __("file", 'sheetspilot' ),
			'file_multi' => __("files", 'sheetspilot' ),
			'clear' => __("Clear", 'sheetspilot' ),
			'selected' => __("selected", 'sheetspilot' ),
			'linked_products' => __("Linked products settings", 'sheetspilot' ),
			'select_prodcts_to_add' => __('Select products to add...', 'sheetspilot' ),
			'name' => __('Name', 'sheetspilot' ),
			'price' => __('price', 'sheetspilot' ),
			'variations' => __('Variations', 'sheetspilot' ),
			'default_variations' => __('Default Variations', 'sheetspilot' ),
			'generate_variation' => __('Generate Variations', 'sheetspilot' ),
			'add_manually' => __('Add manually', 'sheetspilot' ),
			'add_new' => __('Add new', 'sheetspilot' ),
			'add_existing' => __('Add existing', 'sheetspilot' ),
			'product_attributes' => __('Product attributes', 'sheetspilot' ),
			'no_products_added_yet' => __('No products added yet. Use the search above to add products.', 'sheetspilot' ),
			'no_price_text' => __('% variations do not have prices. Variations without prices will not be shown in your store.', 'sheetspilot' ),
			'values_attr' => __('Value(s):', 'sheetspilot' ),
			'visible_on_product_page' => __('Visible on the product page', 'sheetspilot' ),
			'remove' => __('Remove', 'sheetspilot' ),
			'used_for_variations' => __('Used for variations', 'sheetspilot' ),
			'items' => __('items', 'sheetspilot' ),
			'edit_attributes' => __('Edit attributes', 'sheetspilot' ),
			'add_price' => __('Add Price', 'sheetspilot' ),
			'edit' => __('Edit', 'sheetspilot' ),
			'sku' => __('SKU', 'sheetspilot' ),
			'gtin' => __('GTIN, UPC, EAN, or ISBN', 'sheetspilot' ),
			'enabled' => __('Enabled', 'sheetspilot' ),
			'downloadable' => __('Downloadable', 'sheetspilot' ),
			'virtual' => __('Virtual', 'sheetspilot' ),
			'manage_stock' => __('Manage stock ?', 'sheetspilot' ),
			'regular_price' => __('Regular price', 'sheetspilot' ),
			'sale_price' => __('Sale price', 'sheetspilot' ),
			'stock_status' => __('Stock status', 'sheetspilot' ),
			'weight' => __('Weight (kg)', 'sheetspilot' ),
			'dimensions' => __('Dimensions (L×W×H) (cm)', 'sheetspilot' ),
			'shipping_class' => __('Shipping class', 'sheetspilot' ),
			'tax_class' => __('Tax class', 'sheetspilot' ),
			'description' => __('Description', 'sheetspilot' ),

			'in_stock' => __('In Stock', 'sheetspilot' ),
			'out_of_stock' => __('Out of Stock', 'sheetspilot' ),
			'on_backorder' => __('On backorder', 'sheetspilot' ),

			'stock_quantitiy' => __('Stock quantity', 'sheetspilot' ),
			'allow_backorders' => __('Allow backorders?', 'sheetspilot' ),

			'do_not_allow' => __('Do not allow', 'sheetspilot' ),
			'allow_but_notify' => __('Allow, but notify customer', 'sheetspilot' ),
			'allow' => __('Allow', 'sheetspilot' ),
			'low_stock_threshold' => __('Low stock threshold', 'sheetspilot' ),
			'download_limit' => __('Download limit', 'sheetspilot' ),
			'download_expiry' => __('Download expiry', 'sheetspilot' ),
			'choose_file' => __('Choose file', 'sheetspilot' ),
			'delete' => __('Delte', 'sheetspilot' ),
			'same_as_parent' => __('Same as parent', 'sheetspilot' ),
			'standard' => __('Standard', 'sheetspilot' ),
			'reduced_rate' => __('Reduced rate', 'sheetspilot' ),
			'zero_rate' => __('Zero rate', 'sheetspilot' ),

			'set_variation_price' => __('Set variation price', 'sheetspilot' ),
			'set_price' => __('Set price', 'sheetspilot' ),
			'enter_variation_price' => __('Enter variation price', 'sheetspilot' ),
			'add_existing' => __('Add Existing', 'sheetspilot' ),
			'content_rules' => __('Content Rules', 'sheetspilot' ),
			'configure_ai_settings' => __('Configure AI settings, content rules, and custom actions.', 'sheetspilot' ),

			'content_tone' => __('Content tone', 'sheetspilot' ),
			'content_language' => __('Content Language', 'sheetspilot' ),
			'target_audience' => __('Target Audience', 'sheetspilot' ),
			'brand_voice' => __('Brand voice', 'sheetspilot' ),

			'not_selected' => __('Not selected', 'sheetspilot' ),
			'professional' => __('Professional', 'sheetspilot' ),
			'casual' => __('Casual', 'sheetspilot' ),
			'friendly' => __('Friendly', 'sheetspilot' ),
			'formal' => __('Formal', 'sheetspilot' ),
			'formal' => __('Formal', 'sheetspilot' ),
			'persuasive' => __('Persuasive', 'sheetspilot' ),
			'urgent' => __('Urgent', 'sheetspilot' ),
			'informative' => __('Informative', 'sheetspilot' ),
			'confident' => __('Confident', 'sheetspilot' ),
			'humorous' => __('Humorous', 'sheetspilot' ),
			'inspirational' => __('Inspirational', 'sheetspilot' ),
			'sarcastic' => __('Sarcastic', 'sheetspilot' ),
			'analytical' => __('Analytical', 'sheetspilot' ),
			'concise' => __('Concise', 'sheetspilot' ),

			'english' => __('English', 'sheetspilot' ),
			'spanish' => __('Spanish', 'sheetspilot' ),
			'french' => __('French', 'sheetspilot' ),
			'german' => __('German', 'sheetspilot' ),
			'italian' => __('Italian', 'sheetspilot' ),
			'portuguese' => __('Portuguese', 'sheetspilot' ),
			'dutch' => __('Dutch', 'sheetspilot' ),
			'polish' => __('Polish', 'sheetspilot' ),
			'russian' => __('Russian', 'sheetspilot' ),
			'chinese' => __('Chinese', 'sheetspilot' ),
			'japanese' => __('Japanese', 'sheetspilot' ),
			'korean' => __('Korean', 'sheetspilot' ),
			'arabic' => __('Arabic', 'sheetspilot' ),
			'hebrew' => __('Hebrew', 'sheetspilot' ),
			'turkish' => __('Turkish', 'sheetspilot' ),
			'hindi' => __('Hindi', 'sheetspilot' ),
			'indonesian' => __('Indonesian', 'sheetspilot' ),
			'vietnamese' => __('Vietnamese', 'sheetspilot' ),
			'thai' => __('Thai', 'sheetspilot' ),
			'greek' => __('Greek', 'sheetspilot' ),
			'custom' => __('Custom', 'sheetspilot' ),
			'custom_language' => __('Custom Language', 'sheetspilot' ),
			'custom_language_name' => __('Custom Language Name', 'sheetspilot' ),

			'wine_enthusiast' => __('Example: Wine enthusiasts and collectors aged 30-60', 'sheetspilot' ),
			'placehodler_soph' => __('Example: Sophisticated, knowledgeable, and approachable', 'sheetspilot' ),

			'repeater_editor' => __('Repeater editor', 'sheetspilot' ),
			'add_item' => __('Add Item', 'sheetspilot' ),
			'no_items_yet' => __('No items yet. Click "Add Item" to start.', 'sheetspilot' ),
			'item' => __('Item', 'sheetspilot' ),
			// translators: 1: list of unsupported field types, 2: URL to documentation.
			'repeater_fields_not_supported' => __('Sorry, the current functionality doesn\'t support the following field types: %1$s. Visit <a target="_blank" href="%2$s">here</a>', 'sheetspilot' ),
			'visit_repeater_page' => __('You can edit repeater inside page %', 'sheetspilot' ),

			'g_isPro' => SheetsPilotGlobals::$isPro,
			'g_showdebug' => (bool) self::$debug_prompt_request,
			'g_showtrace' => (bool) self::$showTrace,
			'g_urlAjaxActionsSheetsPilot' => SheetsPilotGlobals::$urlAjax,
			'g_paginationPostsPerPage' => SheetsPilotGlobals::POSTS_EDITOR_PAGINATION_POSTS_PER_PAGE,
			'g_applyPromptMaxConcurrent' => self::APPLY_PROMPT_MAX_CONCURRENT,
			'g_baseURL' => SheetsPilotGlobals::$urlBase,
			'g_urlImagePlaceholder' => SheetsPilotGlobals::$urlImagePlaceholder,

			'g_isContextOff' => ( isset( $_GET['nomenu'] ) ? '1' : '0' ),
			'g_isLogOn' => ( isset( $_GET['console'] ) ? '1' : '0' ),
	
			'g_postEditLink' => admin_url( 'post.php?post=%PID&action=edit' ),
			'g_postElementorEditLink' => admin_url( 'post.php?post=%PID&action=elementor' ),
			'g_postContentEditIconHtml' => SheetsPilotHelperElementor::getPostContentEditManageIconHtml( false ),
			'g_postContentEditIconElementorHtml' => SheetsPilotHelperElementor::getPostContentEditManageIconHtml( true ),
			'g_pluginTitle' => SheetsPilotGlobals::PLUGIN_TITLE,

			'all_dates' => __('All Dates', 'sheetspilot' ),
			'today' => __('Today', 'sheetspilot' ),
			'last_7_days' => __('Last 7 days', 'sheetspilot' ),
			'last_30_days' => __('Last 30 days', 'sheetspilot' ),
			'last_3_months' => __('Last 3 months', 'sheetspilot' ),
			'last_6_months' => __('Last 6 months', 'sheetspilot' ),
			'last_12_months' => __('Last 12 months', 'sheetspilot' ),
			'this_year' => __('This Year', 'sheetspilot' ),
			'custom_range' => __('Custom range', 'sheetspilot' ),
			'start' => __('Start', 'sheetspilot' ),
			'end' => __('End', 'sheetspilot' ),
			'paste_list' => __('Paste list', 'sheetspilot' ),
			'paste_list_subtext' => __('Each line becomes a new post ( one row = one post )', 'sheetspilot' ),
			'list_of_titles' => __('List of titles', 'sheetspilot' ),
			'list_titles_placeholder' => __('Paste one title per line. Each line will create a new post', 'sheetspilot' ),
			'list_titles_posts_created_text' => __(' new posts will be created', 'sheetspilot' ),
		];
	}

	/**
	 * continue set some settings
	 */
	public static function onWPInit(){

		if(defined("DISABLE_SHEETSPILOT"))
			return(false);
		
	}
	
	/**
	 * print all globals variables
	 */
	public static function printVars(){
		
		$methods = get_class_vars( "SheetsPilotGlobals" );
		dmp($methods);
		exit();
	}

	
	
}

 
