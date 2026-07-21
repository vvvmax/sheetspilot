<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if(!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilot_PluginAdmin{

	private static $arrMenuPages = array();
	private static $arrSubMenuPages = array();
	public static $view;
	public static $isInsidePlugin = false;
	private $screen;

	const PRO_PLUGIN_BASENAME = 'sheetspilot-premium/sheetspilot-pro.php';

	/** Used for DB schema upgrades on plugin update (match unlimited-elements pattern). */
	const DB_VERSION = '9';
	const OPTION_DB_VERSION = 'sheetspilot_db_version';

	const DEBUG_SCREEN_ID = false;

	/**
	 * Runs on plugin activation. Creates the logs table if it does not exist.
	 */
	public static function onPluginActivation(){
		if ( ! class_exists( 'SheetsPilotGlobals' ) ) {
			return;
		}
		self::createLogsTable();
		self::createPromptsTable();
	}

	/**
	 * Check DB version on admin_init; run createLogsTable( true ) when version changes so dbDelta can update schema.
	 */
	public static function checkDBUpgrade(){
		if ( ! class_exists( 'SheetsPilotGlobals' ) ) {
			return;
		}
		$saved = get_option( self::OPTION_DB_VERSION );

		if ( $saved !== self::DB_VERSION ) {
			self::createLogsTable( true );
			self::createPromptsTable( true );
			update_option( self::OPTION_DB_VERSION, self::DB_VERSION );
		}
	}

	/**
	 * Create the logs table for request/response logging.
	 * When $isForce is false, does nothing if table already exists (like unlimited-elements createTable).
	 * When $isForce is true, runs dbDelta anyway so schema can be updated on plugin upgrade.
	 *
	 * @param bool $isForce If true, run dbDelta even when table exists (for upgrades).
	 */
	public static function createLogsTable( $isForce = false ){

		global $wpdb;

		$table_name      = SheetsPilotGlobals::$tableLogs;
		$charset_collate = $wpdb->get_charset_collate();

		if ( $isForce === false ) {
			$existing_table = $wpdb->get_var(
				$wpdb->prepare(
					"SHOW TABLES LIKE %s",
					$table_name
				)
			);
			if ( $existing_table === $table_name ) {
				return;
			}
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// dbDelta format: PRIMARY KEY with two spaces before (id); use KEY not INDEX; one field per line.
		$sql = "CREATE TABLE " . $table_name . " (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			prompt TEXT NULL,
			cell_value LONGTEXT NULL,
			request LONGTEXT NULL,
			response LONGTEXT NULL,
			response_data LONGTEXT NULL,
			response_action VARCHAR(32) NULL,
			metadata LONGTEXT NULL,
			userid BIGINT(20) UNSIGNED NULL,
			date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			comments TEXT NULL,
			PRIMARY KEY  (id),
			KEY userid (userid)
		) " . $charset_collate . ";";

		dbDelta( $sql );
	}

	/**
	 * Create the prompts table.
	 * When $isForce is false, does nothing if table already exists.
	 * When $isForce is true, runs dbDelta anyway so schema can be updated on plugin upgrade.
	 *
	 * @param bool $isForce If true, run dbDelta even when table exists (for upgrades).
	 */
	public static function createPromptsTable( $isForce = false ){

		global $wpdb;

		$table_name      = SheetsPilotGlobals::$tablePrompts;
		$charset_collate = $wpdb->get_charset_collate();

		if ( $isForce === false ) {
			$existing_table = $wpdb->get_var(
				$wpdb->prepare(
					"SHOW TABLES LIKE %s",
					$table_name
				)
			);
			if ( $existing_table === $table_name ) {
				return;
			}
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE " . $table_name . " (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			text TEXT NULL,
			description VARCHAR(255) NULL,
			is_latest TINYINT(1) NOT NULL DEFAULT 0,
			is_saved TINYINT(1) NOT NULL DEFAULT 0,
			is_favorite TINYINT(1) NOT NULL DEFAULT 0,
			userid BIGINT(20) UNSIGNED NULL,
			post_type VARCHAR(64) NULL,
			postid BIGINT(20) UNSIGNED NULL,
			date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			comments TEXT NULL,
			prompt_type TEXT NULL,
			PRIMARY KEY  (id),
			KEY userid (userid)
		) " . $charset_collate . ";";

		dbDelta( $sql );
	}

	/**
	 * call init
	 */
	public function __construct(){
		
		$this->init();

		// remove notifications from editor page
		add_action('admin_init', [ $this, 'removeNotificationsFromEditorPage' ]);

		// drop image generation query
		if( isset( $_GET['drop_query'] ) ){
			SheetsPilot_PluginAdmin::dropImageQueueRequests();
		}
 
	}

	
	/**
	 * add admin menus from the list.
	 */
	public function addAdminMenu(){
		
		$pageTitle = "SheetsPilot";
		if ( SheetsPilotGlobals::$isPro ) {
			$pageTitle = "SheetsPilot Pro";
		}
				
		$menuTitle = $pageTitle;
		$menuSlug = "sheetspilot";
		$function = array($this, "adminPages");
		
		//SheetsPilotGlobals::$urlImages."unlimited-ai-menu-icon.svg"
		$svg_icon = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 0.315432V7.49984L13.1962 7.51684C13.1998 7.52818 13.1925 7.50551 13.1962 7.51684C12.0605 7.71454 11.2004 8.76033 11.2004 9.99933C11.2004 10.9097 10.9665 11.7642 10.5579 12.4995C10.3977 12.7879 10.2103 13.058 10 13.3061C9.4651 13.9382 8.7797 14.428 8.00121 14.7145C7.50076 14.8989 6.96162 14.9991 6.40012 14.9991V17.4992C6.6709 17.4992 6.93805 17.4835 7.20036 17.4532C8.21154 17.3367 9.15926 17.0024 10 16.4956C10.663 16.0964 11.259 15.5902 11.7661 14.9997C11.8459 14.9072 11.9238 14.8121 11.9988 14.7151C12.5089 14.0591 12.9145 13.3117 13.1901 12.5002C13.1937 12.4888 13.1979 12.4775 13.2016 12.4655C13.4597 11.6937 13.5998 10.8638 13.5998 10H16C16 10.0214 16 10.0428 15.9994 10.0648C15.9946 10.9053 15.89 11.7207 15.6972 12.5002C15.4935 13.3268 15.1901 14.1126 14.8014 14.8422C14.7737 14.8952 14.7446 14.9481 14.7156 15.0003C14.3953 15.5777 14.0211 16.1178 13.5998 16.6146C13.349 16.9105 13.0819 17.1907 12.7996 17.4539C11.9807 18.2176 11.0354 18.8365 10 19.2728C8.88849 19.7412 7.67302 20 6.40012 20C5.57147 20 4.7676 19.8904 4 19.6846V12.5002L6.80387 12.4832C6.8075 12.4945 6.80024 12.4718 6.80387 12.4832C7.93956 12.2855 8.79964 11.2397 8.79964 10.0007C8.79964 9.09025 9.03354 8.23585 9.44213 7.50048C9.6023 7.21212 9.78966 6.94202 10 6.69395C10.5349 6.06182 11.2203 5.57199 11.9988 5.28552C12.4993 5.10105 13.0384 5.00094 13.5998 5.00094V2.50078C13.3291 2.50078 13.0619 2.51652 12.7996 2.54675C11.7885 2.66322 10.8407 2.99754 10 3.50437C9.33696 3.90354 8.74101 4.40975 8.23391 5.00032C8.15413 5.09287 8.07616 5.18793 8.00121 5.2849C7.49109 5.94094 7.08553 6.68828 6.80991 7.49984C6.80629 7.51118 6.80206 7.52251 6.79843 7.53447C6.54034 8.30637 6.40012 9.13617 6.40012 10H4.0006C4.0006 9.97858 4.0006 9.95717 4.00121 9.93517C4.00604 9.09467 4.11061 8.27929 4.30342 7.49984C4.5071 6.67318 4.81052 5.88742 5.19915 5.15772C5.22696 5.10482 5.25597 5.05194 5.28498 4.99968C5.60532 4.42234 5.97945 3.88214 6.40073 3.38538C6.65156 3.08947 6.9187 2.80929 7.20097 2.54612C8.01994 1.78241 8.96525 1.16351 10.0006 0.727192C11.1121 0.258138 12.327 0 13.5998 0C14.4286 0 15.2324 0.109551 16 0.315432Z" fill="#F0F0F1"/></svg>';

		$icon_data = 'data:image/svg+xml;base64,' . base64_encode($svg_icon);
		
		add_menu_page($pageTitle, $pageTitle, SheetsPilotGlobals::$capability, $menuSlug, $function, $icon_data);
		
		$prefix = 'sheetspilot';
		
		//add sub menu page
		
		add_submenu_page( $menuSlug, "Posts Editor", "Posts Editor", SheetsPilotGlobals::$capability, $prefix, $function);
		if ( SheetsPilotGlobals::$isPro ) {
			add_submenu_page( $menuSlug, "Settings", "Settings", SheetsPilotGlobals::$capability, $prefix."_settings", $function);
			add_submenu_page( $menuSlug, "Log", "Log", SheetsPilotGlobals::$capability, $prefix."_log", $function);
			add_submenu_page(
				$menuSlug,
				__( 'Tools', 'sheetspilot' ),
				__( 'Tools', 'sheetspilot' ),
				SheetsPilotGlobals::$capability,
				$prefix . '_prompt_tester',
				$function
			);
		}

	}
		
	
	/**
	 * init view
	 */
	private function initView(){
		
		
		$defaultView = SheetsPilotGlobals::DEFAULT_VIEW;
		
		//set view
		$viewInput = SheetsPilotFunctions::getGetVar("view","",SheetsPilotFunctions::SANITIZE_KEY);
		$page = SheetsPilotFunctions::getGetVar("page","",SheetsPilotFunctions::SANITIZE_KEY);

		if(strpos($page, 'sheetspilot') === 0)
			self::$isInsidePlugin = true;

		// Legacy URL from the old standalone "Tools" menu (kept so bookmarks keep working).
		if ( $page === 'sheetspilot_tools_prompt_tester' ) {
			self::$view = SheetsPilotGlobals::VIEW_PROMPT_TESTER;
			return false;
		}

		//get the view out of the page
		if(!empty($viewInput)){
			self::$view = $viewInput;
			return(false);
		}

		if ( $page === 'sheetspilot' ) {
			self::$view = SheetsPilotGlobals::VIEW_POSTEDITOR;
			return(false);
		}
		
		//check bottom devider
		$deviderPos = strpos($page,"_");
				
		if($deviderPos !== false){
			
			self::$view = substr($page, $deviderPos+1);
			return(false);
		}
		
		//check middle devider
		$deviderPos = strpos($page, "-");
		if($deviderPos !== false){
			self::$view = substr($page, $deviderPos+1);
			
			return(false);
		}
		
		self::$view = $defaultView;
		
	}
	
	
	/**
	 * open admin pages
	 */
	public function adminPages(){
				
		try{
			
			if (
				SheetsPilotGlobals::$isPro === false &&
				(
					self::$view === SheetsPilotGlobals::VIEW_SETTINGS ||
					self::$view === SheetsPilotGlobals::VIEW_LOG ||
					self::$view === SheetsPilotGlobals::VIEW_PROMPT_HISTORY ||
					self::$view === SheetsPilotGlobals::VIEW_PROMPT_TESTER
				)
			) {
				self::$view = SheetsPilotGlobals::VIEW_POSTEDITOR;
			}

			// its global full version
			if( SHEETS_PLUGIN_IS_GLOBAL_DEV ){
				$path_to_view = SheetsPilotGlobals::$pathPlugin.'/pro/';
			}else{
				$path_to_view = SheetsPilotGlobals::$pathPluginPro;
			}

			if ( self::$view === SheetsPilotGlobals::VIEW_SETTINGS ) {
				$pathView = $path_to_view . 'views/settings.php';
			} elseif ( self::$view === SheetsPilotGlobals::VIEW_LOG ) {
				$pathView = $path_to_view . 'views/log.php';
				if ( ! class_exists( 'SheetsPilot_PluginViewLog', false ) ) {
					require_once $pathView;
				}
				new SheetsPilot_PluginViewLog();
				return;
			} elseif ( self::$view === SheetsPilotGlobals::VIEW_PROMPT_HISTORY ) {
				$pathView = $path_to_view . 'views/prompt_history.php';
			} elseif ( self::$view === SheetsPilotGlobals::VIEW_PROMPT_TESTER ) {
				$pathView = $path_to_view . 'views/prompt_tester.php';
			} else {
				$pathView = SheetsPilotHelper::getPathView( self::$view );
			}

			require $pathView;
			
		}catch(Exception $e){
			
			echo "<br>";
			
			SheetsPilotHelper::outputExceptionBox($e, SheetsPilotGlobals::PLUGIN_TITLE." error");
			
		}
	}
	
	
	/**
	 * add inside scripts
	 */
	public function onAddScripts(){
				
		//---- add js scripts
		
		switch(self::$view){
			case SheetsPilotGlobals::VIEW_WELCOME:
			case SheetsPilotGlobals::VIEW_SETTINGS:
								
				SheetsPilotHelper::addStyle("unlimited_ai_admin");
				SheetsPilotHelper::addStyle("unlimited_ai_styles");
				$codemirror_css_url = SheetsPilotGlobals::$urlPlugin . 'assets/libraries/codemirror-custom/codemirror-custom.css';
				SheetsPilotHelper::addStyleAbsoluteUrl($codemirror_css_url, 'sheetspilot' . '-codemirror');
				SheetsPilotHelper::addScript("unlimited_ai_provider_admin");
				SheetsPilotHelper::addScript("unlimited_ai_admin");
				$codemirror_js_url = SheetsPilotGlobals::$urlPlugin . 'assets/libraries/codemirror-custom/codemirror-custom.min.js';
				SheetsPilotHelper::addScriptAbsoluteUrl($codemirror_js_url, 'sheetspilot' . '-codemirror', true, array());
				SheetsPilotHelper::addScript("unlimited_ai_settings");
				SheetsPilotHelper::addScript("unlimited_ai_view_settings");
				
			break;
			case SheetsPilotGlobals::VIEW_LOG:
			case SheetsPilotGlobals::VIEW_PROMPT_HISTORY:
				SheetsPilotHelper::addStyle("unlimited_ai_admin");
				SheetsPilotHelper::addStyle("unlimited_ai_styles");
			break;
			case SheetsPilotGlobals::VIEW_PROMPT_TESTER:
				wp_enqueue_media();
				SheetsPilotHelper::addStyle("unlimited_ai_admin");
				SheetsPilotHelper::addStyle("unlimited_ai_styles");
				$prompt_tester_js_url = SHEETS_PLUGIN_IS_GLOBAL_DEV
					? SheetsPilotGlobals::$urlPlugin . 'pro/assets/js/prompt_tester.js'
					: SheetsPilotGlobals::$urlPluginPro . 'assets/js/prompt_tester.js';
				SheetsPilotHelper::addScriptAbsoluteUrl(
					$prompt_tester_js_url,
					'sheetspilot-prompt-tester',
					true,
					array( 'jquery' )
				);
			break;
			case SheetsPilotGlobals::VIEW_POSTEDITOR:
				wp_enqueue_media();	
				wp_enqueue_editor();				

				SheetsPilotHelper::addScript("unlimited_ai_image_preview" );
				SheetsPilotHelper::addScript("sheetspilot_posteditor_variables", null, "assets/js",  true );
				SheetsPilotHelper::addScript("sheetspilot_posteditor_main", null, "assets/js",  true );

				SheetsPilotHelper::addInlineLocalization( [] );

				SheetsPilotHelper::addStyle("unlimited_ai_admin");
				SheetsPilotHelper::addStyle("unlimited_ai_postseditor_styles");
				SheetsPilotHelper::addStyle("content-rules-dialog");
				$codemirror_css_url_pe = SheetsPilotGlobals::$urlPlugin . 'assets/libraries/codemirror-custom/codemirror-custom.css';
				SheetsPilotHelper::addStyleAbsoluteUrl($codemirror_css_url_pe, 'sheetspilot' . '-codemirror');
				SheetsPilotHelper::addScript("unlimited_ai_provider_admin");
				SheetsPilotHelper::addScript("unlimited_ai_admin");
				$codemirror_js_url_pe = SheetsPilotGlobals::$urlPlugin . 'assets/libraries/codemirror-custom/codemirror-custom.min.js';
				SheetsPilotHelper::addScriptAbsoluteUrl($codemirror_js_url_pe, 'sheetspilot' . '-codemirror', true, array());
				$showhint_js_url = SheetsPilotGlobals::$urlPlugin . 'assets/libraries/codemirror-custom/addon/hint/show-hint.js';
				SheetsPilotHelper::addScriptAbsoluteUrl($showhint_js_url, 'sheetspilot' . '-codemirror-show-hint', true, array('sheetspilot' . '-codemirror'));
				$showhint_css_url = SheetsPilotGlobals::$urlPlugin . 'assets/libraries/codemirror-custom/addon/hint/show-hint.css';
				SheetsPilotHelper::addStyleAbsoluteUrl($showhint_css_url, 'sheetspilot' . '-codemirror-show-hint');

				SheetsPilotHelper::addLibraryScript('thickbox');
				SheetsPilotHelper::addLibraryStyle('thickbox');

				SheetsPilotHelper::addLibraryScript('jquery-ui-tooltip');
				SheetsPilotHelper::addLibraryStyle('wp-jquery-ui-dialog');

				SheetsPilotHelper::addLibraryScript('jquery-ui-core');
				SheetsPilotHelper::addLibraryScript('jquery-ui-widget');
				SheetsPilotHelper::addLibraryScript('jquery-ui-mouse');
				SheetsPilotHelper::addLibraryScript('jquery-ui-position');
				SheetsPilotHelper::addLibraryScript('jquery-effects-core');

				SheetsPilotHelper::addScript("unlimited_ai_small_modal");

				SheetsPilotHelper::addScript("sheetspilot_drawer_fn");			
				SheetsPilotHelper::addScript("sheetspilot_attributes_editor");		
				SheetsPilotHelper::addScript("sheetspilot_variable_product");				
				SheetsPilotHelper::addScript("sheetspilot_notification_fn");				
				SheetsPilotHelper::addScript("sheetspilot_repeater_editor");				
				SheetsPilotHelper::addScript("sheetspilot_top_filtering_bar");				
				SheetsPilotHelper::addScript("unlimited_ai_postseditor");
				SheetsPilotHelper::addScript( "unlimited_ai_prompts", null, "assets/js", false, true );
				SheetsPilotHelper::addScript("unlimited_ai_cell_processing");
				SheetsPilotHelper::addScript("unlimited_ai_content_rules");
 
	

				SheetsPilotHelper::addScript("select2.min");
				SheetsPilotHelper::addStyle("select2.min");
				SheetsPilotHelper::addStyle("bootstrap.min");

				
 
				
			break;
		}
		
		//---- add css styles
		
		if(SheetsPilotGlobals::$enableCopy == true || SheetsPilotGlobals::$enablePaste == true)
			$this->onIncludeFrontScripts();
		
		
	}
	
	
	/**
	 * add outside scripts
	 */
	public function onAddOutsideScripts(){
		
		//add scripts on outside 
		
	}
	
	
	/**
	 * register settings for the settings page
	 */
	public function onAdminInit(){
		//register settings to save
		register_setting( SheetsPilotGlobals::OPTIONS_GROUP_NAME, 'sheetspilot_general_settings',
    	[
        	'type'              => 'array',
        	'sanitize_callback' => [self::class, 'sanitizeGeneralSettings'],
    	]
		);
		
	}

	/**
	 * sanitize settings
	 */
	public static function sanitizeGeneralSettings($input)
	{
		if (!is_array($input)) {
			return [];
		}

		$output = [];

	 
		$output['openai_key'] = isset($input['openai_key'])
			? sanitize_text_field($input['openai_key'])
			: '';
		$output['openai_model'] = isset($input['openai_model'])
			? sanitize_text_field($input['openai_model'])
			: '';

		$output['enable_debug_prompt_tool'] = ( isset( $input['enable_debug_prompt_tool'] ) && '1' === (string) $input['enable_debug_prompt_tool'] )
			? '1' : '0';

		$output['enable_debug_prompt_request'] = ( isset( $input['enable_debug_prompt_request'] ) && '1' === (string) $input['enable_debug_prompt_request'] )
			? '1' : '0';

		$output['showSessionLog'] = ( isset( $input['showSessionLog'] ) && '1' === (string) $input['showSessionLog'] )
			? '1' : '0';

		return $output;
	}
	
	/**
	 * on ajax actions
	 */
	public function onAjaxActions(){

		$objActions = new SheetsPilot_AjaxActions();
		$objActions->onAjaxActions();		
	}
	
	
	/**
	 * init the class
	 */
	public function init(){

		$this->initView();

		$main_plugin_file = SheetsPilotGlobals::$pathPlugin . 'unlimited-ai.php';
		register_activation_hook( $main_plugin_file, array( __CLASS__, 'onPluginActivation' ) );

		add_action("admin_menu", array($this, "addAdminMenu"));
				
		if(self::$isInsidePlugin == true)
			add_action("admin_enqueue_scripts", array($this,"onAddScripts"), true);
		else
			add_action("admin_enqueue_scripts", array($this,"onAddOutsideScripts"), true);
		
		//register settings
		add_action("admin_init", array($this,"onAdminInit"));
		// DB version check: create/update logs table on first load or plugin update (same pattern as unlimited-elements)
		add_action("admin_init", array( __CLASS__, 'checkDBUpgrade' ));
		add_action( 'after_plugin_row_' . self::PRO_PLUGIN_BASENAME, array( $this, 'renderProVersionMismatchRowNotice' ), 10, 3 );
		
		//register ajax
		add_action('wp_ajax_'.'sheetspilot'."_ajax_actions"."", array($this,"onAjaxActions"), true);
		add_action('wp_ajax_nopriv_'.'sheetspilot'."_ajax_actions", array($this,"onAjaxActions"), true);

		add_filter( 'safe_style_css', function( $styles ) {
			$styles[] = 'display';
			return $styles;
		});
	 	
	}

	/**
	 * Render a warning row under SheetsPilot Pro when active and versions mismatch.
	 *
	 * @param string $plugin_file Plugin basename.
	 * @param array  $plugin_data Plugin header data.
	 * @param string $status      Row status.
	 * @return void
	 */
	public function renderProVersionMismatchRowNotice( $plugin_file, $plugin_data, $status ) {
		if ( ! defined( 'SHEETSPILOT_PRO_PLUGIN_ACTIVE' ) || SHEETSPILOT_PRO_PLUGIN_ACTIVE !== true ) {
			return;
		}
		if ( ! defined( 'SHEETSPILOT_PRO_VERSION_MATCH' ) || SHEETSPILOT_PRO_VERSION_MATCH === true ) {
			return;
		}

		$free_version = defined( 'SHEETSPILOT_FREE_VERSION' ) ? (string) SHEETSPILOT_FREE_VERSION : '';
		$pro_version  = defined( 'SHEETSPILOT_PRO_VERSION' ) ? (string) SHEETSPILOT_PRO_VERSION : '';
		$message = sprintf(
			/* translators: 1: free version, 2: pro version */
			__( 'SheetsPilot and SheetsPilot Pro versions must match. Free: %1$s, Pro: %2$s. Pro is disabled until versions match.', 'sheetspilot' ),
			$free_version !== '' ? $free_version : 'unknown',
			$pro_version !== '' ? $pro_version : 'unknown'
		);
		?>
		<tr class="plugin-update-tr active">
			<td colspan="3" class="plugin-update colspanchange">
				<div class="update-message notice inline notice-error notice-alt">
					<p><?php echo esc_html( $message ); ?></p>
				</div>
			</td>
		</tr>
		<?php
	}
	
	/**
	 * remove page notifications
	 */
	function removeNotificationsFromEditorPage(){

		if ( isset($_GET['page']) && $_GET['page'] === 'sheetspilot' ) {
			remove_all_actions('admin_notices');
			remove_all_actions('all_admin_notices');
			remove_all_actions('network_admin_notices');
		}

	}
 
	
	public static function dropImageQueueRequests(){				
		delete_option('sheetspilot_image_queue_list'  );
	}

}
