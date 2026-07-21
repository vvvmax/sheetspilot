<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/

namespace  SheetsPilotUnlimitedElements\Sheetspilot;

/**
 * Load plugin PHP dependencies (core and optional Pro).
 *
 * Declared inside the `Sheetspilot` namespace so WordPress Coding Standards
 * (PrefixAllGlobals) does not treat this as an unprefixed global function.
 *
 * @param string $main_file_path Absolute path to the main plugin file (sheetspilot.php).
 */
function load_plugin( $main_file_path ) {
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	if ( ! defined( 'SHEETSPILOT_INC' ) ) {
		die( 'restricted access' );
	}
	
	$debugDefined = false;

	$current_folder     = dirname( $main_file_path );
	$inc_php_folder     = $current_folder . '/inc_php/';
	$pro_inc_php_folder = $current_folder . '/pro/inc_php/';
	$has_pro_folder     = is_dir( $current_folder . '/pro/' );
 
	$pro_plugin_file       = 'sheetspilot-premium/sheetspilot-pro.php';
	$active_plugins        = get_option( 'active_plugins', array() );
	$is_pro_plugin_active  = in_array( $pro_plugin_file, $active_plugins, true );
	$is_pro_version_match  = true;
	$free_version          = '';
	$pro_version           = '';

	if ( false === $is_pro_plugin_active && function_exists( 'is_multisite' ) && is_multisite() ) {
		$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
		$is_pro_plugin_active   = isset( $network_active_plugins[ $pro_plugin_file ] );
	}

	if ( $is_pro_plugin_active ) {
		$free_plugin_header = get_file_data( $main_file_path, array( 'Version' => 'Version' ), 'plugin' );
		$free_version       = isset( $free_plugin_header['Version'] ) ? (string) $free_plugin_header['Version'] : '';

		$pro_main_file = SHEETS_WPPLUGIN_DIR . $pro_plugin_file;
		if ( file_exists( $pro_main_file ) ) {
			$pro_plugin_header = get_file_data( $pro_main_file, array( 'Version' => 'Version' ), 'plugin' );
			$pro_version       = isset( $pro_plugin_header['Version'] ) ? (string) $pro_plugin_header['Version'] : '';
		}

		$is_pro_version_match = ( $free_version !== '' && $pro_version !== '' && $free_version === $pro_version );

		$external_pro_plugin_folder = SHEETS_WPPLUGIN_DIR . 'sheetspilot-premium/';
		$external_pro_inc_php_folder = $external_pro_plugin_folder . 'inc_php/';
		if ( $is_pro_version_match && is_dir( $external_pro_inc_php_folder ) && file_exists( $external_pro_inc_php_folder . 'request_log.class.php' ) ) {
			$pro_inc_php_folder = $external_pro_inc_php_folder;
			$has_pro_folder     = true;
		} else {
			$has_pro_folder = false;
		}
	}

	if ( $debugDefined ) {
		require_once $inc_php_folder . 'functions.php';
		$debug_sheetspilot_includes = array(
			'main_file_path'                    => $main_file_path,
			'current_folder'                    => $current_folder,
			'inc_php_folder'                    => $inc_php_folder,
			'pro_inc_php_folder'                => $pro_inc_php_folder,
			'has_pro_folder'                    => $has_pro_folder,
			'pro_plugin_file'                   => $pro_plugin_file,
			'is_pro_plugin_active'             => $is_pro_plugin_active,
			'is_pro_version_match'             => $is_pro_version_match,
			'free_version'                     => $free_version,
			'pro_version'                      => $pro_version,
			'active_plugins (count)'            => is_array( $active_plugins ) ? count( $active_plugins ) : 0,
			'active_plugins (has pro file)'     => is_array( $active_plugins ) && in_array( $pro_plugin_file, $active_plugins, true ),
		);

		dmp($debug_sheetspilot_includes);
		exit();
	}


	if ( ! defined( 'SHEETSPILOT_HAS_PRO_FOLDER' ) ) {
		define( 'SHEETSPILOT_HAS_PRO_FOLDER', $has_pro_folder );
	}

	if ( ! defined( 'SHEETSPILOT_PRO_PLUGIN_ACTIVE' ) ) {
		define( 'SHEETSPILOT_PRO_PLUGIN_ACTIVE', $is_pro_plugin_active );
	}
	if ( ! defined( 'SHEETSPILOT_PRO_VERSION_MATCH' ) ) {
		define( 'SHEETSPILOT_PRO_VERSION_MATCH', $is_pro_version_match );
	}
	if ( ! defined( 'SHEETSPILOT_FREE_VERSION' ) ) {
		define( 'SHEETSPILOT_FREE_VERSION', $free_version );
	}
	if ( ! defined( 'SHEETSPILOT_PRO_VERSION' ) ) {
		define( 'SHEETSPILOT_PRO_VERSION', $pro_version );
	}

	

	require $inc_php_folder . 'globals.class.php';
	require $inc_php_folder . 'plugins_jetengine_class.php';
 
	require $inc_php_folder . 'acf_repeater_functionality.php';
	require $inc_php_folder . 'front_processing_class.php';
	
	require_once $inc_php_folder . 'functions.php';
	require $inc_php_folder . 'functions.class.php';
	require $inc_php_folder . 'functions_wp.class.php';


	

	require $inc_php_folder . 'helper.class.php';
	require $inc_php_folder . 'helper_elementor.class.php';
	require $inc_php_folder . 'ajax_session_log.class.php';
	require $inc_php_folder . 'content_blocks.class.php';
	require $inc_php_folder . 'query_processing.class.php';
	require $inc_php_folder . 'cell_editor_functionality.class.php';
	require $inc_php_folder . 'actions.class.php';
	require $inc_php_folder . 'admin.class.php';
	require $inc_php_folder . 'provider_db.class.php';
	require $inc_php_folder . 'db.class.php';
	require $inc_php_folder . 'image_processing.class.php';

	if ( $has_pro_folder ) {
		require $pro_inc_php_folder . 'openai.responseapi.class.php';

		
		require $pro_inc_php_folder . 'request_log.class.php';
		require $pro_inc_php_folder . 'libs/openai/vendor/autoload.php';
		

		require $pro_inc_php_folder . 'useChatGPT.class.php';
		
		require $pro_inc_php_folder . 'prompt_history.class.php';
		require $pro_inc_php_folder . 'general_settings.class.php';
		require $pro_inc_php_folder . 'settings.class.php';
		require $pro_inc_php_folder . 'settings_output.class.php';
		require $pro_inc_php_folder . 'settings_output_wide.class.php';
		require $pro_inc_php_folder . 'prompts.class.php';
		require $pro_inc_php_folder . 'prompts_ui.class.php';
		require $pro_inc_php_folder . 'php_error_trace.class.php';
	}
}
