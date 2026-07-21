<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if(!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilot_PluginGeneralSettings{
	
	/**
	 * Central source of available chat models (label => model id).
	 *
	 * @return array<string,string>
	 */
	public static function getOpenAIModelOptions() {
		return array(
			"GPT-5.5 ($5 / $30)"               => "gpt-5.5",
			"GPT-5.4 pro ($30 / $180)"         => "gpt-5.4-pro",
			"GPT-5.4 ($2.50 / $15)"            => "gpt-5.4",
			"GPT-5.4 mini ($0.75 / $4.50)"     => "gpt-5.4-mini",
			"GPT-5.4 nano ($0.20 / $1.25)"     => "gpt-5.4-nano",
			"GPT-5.2 pro ($21 / $168)"         => "gpt-5.2-pro",
			"GPT-5.2 ($1.75 / $14)"            => "gpt-5.2",
			"GPT-5.1 ($1.25 / $10)"            => "gpt-5.1",
			"GPT-5 ($1.25 / $10)"              => "gpt-5",
			"GPT-5 pro ($15 / $120)"           => "gpt-5-pro",
			"GPT-5 mini ($0.25 / $2)"          => "gpt-5-mini",
			"GPT-5 nano ($0.05 / $0.40)"       => "gpt-5-nano",
			"GPT-4.1 ($2 / $8)"                => "gpt-4.1",
			"GPT-4.1 mini ($0.40 / $1.60)"     => "gpt-4.1-mini",
			"GPT-4.1 nano ($0.10 / $0.40)"     => "gpt-4.1-nano",
			"GPT-4o ($2.50 / $10)"             => "gpt-4o",
			"GPT-4o mini ($0.15 / $0.60)"      => "gpt-4o-mini",
			"o3 ($2 / $8)"                     => "o3",
			"o4-mini ($1.10 / $4.40)"          => "o4-mini",
			"o3-mini ($1.10 / $4.40)"          => "o3-mini",
		);
	}

	/**
	 * Default chat model for settings and tools.
	 *
	 * @return string
	 */
	public static function getDefaultOpenAIModel() {
		return 'gpt-5.4-nano';
	}

	/**
	 * Resolve sidebar quality value (default = plugin constant).
	 *
	 * @param string                   $value    UI value.
	 * @param array<string,mixed>|null $settings Optional general settings.
	 * @return string
	 */
	public static function resolveSidebarImageQuality( $value, $settings = null ) {
		$v = strtolower( trim( (string) $value ) );
		if ( $v === '' || $v === 'default' ) {
			return self::getResolvedImageQuality( $settings );
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
		return self::getResolvedImageQuality( $settings );
	}

	/**
	 * Resolve sidebar format value (default = general settings).
	 *
	 * @param string                   $value    UI value.
	 * @param array<string,mixed>|null $settings Optional general settings.
	 * @return string
	 */
	public static function resolveSidebarImageFormat( $value, $settings = null ) {
		$v = strtolower( trim( (string) $value ) );
		if ( $v === 'jpg' ) {
			$v = 'jpeg';
		}
		if ( $v === '' || $v === 'default' ) {
			return self::getResolvedImageFormat( $settings );
		}
		if ( in_array( $v, array( 'png', 'jpeg', 'webp' ), true ) ) {
			return $v;
		}
		return self::getResolvedImageFormat( $settings );
	}

	/**
	 * Default image size when the editor uses "default" (sidebar / cell rules).
	 *
	 * @param array<string,mixed>|null $settings Unused; kept for call-site compatibility.
	 * @return string
	 */
	public static function getResolvedImageSize( $settings = null ) {
		unset( $settings );
		return SheetsPilotGlobals::OPENAI_IMAGE_SIZE;
	}

	/**
	 * Default image quality when the editor uses "default" (sidebar / cell rules).
	 *
	 * @param array<string,mixed>|null $settings Unused; kept for call-site compatibility.
	 * @return string
	 */
	public static function getResolvedImageQuality( $settings = null ) {
		unset( $settings );
		return SheetsPilotGlobals::DEFAULT_IMAGE_QUALITY;
	}

	/**
	 * Default image format when the editor uses "default" (sidebar / cell rules).
	 *
	 * @param array<string,mixed>|null $settings Unused; kept for call-site compatibility.
	 * @return string
	 */
	public static function getResolvedImageFormat( $settings = null ) {
		unset( $settings );
		return SheetsPilotGlobals::DEFAULT_IMAGE_FORMAT;
	}


	/**
	 * get general settings
	 */
	public static function getSettingsObject(){
		
		$settings = new SheetsPilotUniteSettings();
		
		$params = array();
		$params["description"] = "Enter the OpenAI (Chat GPT) key, will use for the ai capabilities";
		
		$settings->addTextBox("openai_key", "", __("Open AI Key",'sheetspilot'), $params );

		$model_params = array();
		$model_params["description"] = 'Choose the OpenAI model for chat completions. Prices are Standard tier per 1M tokens (input / output). See <a href="https://platform.openai.com/docs/pricing" target="_blank" rel="noopener noreferrer">platform.openai.com/docs/pricing</a>';
		$default_openai_model = self::getDefaultOpenAIModel();
		$openai_models = self::getOpenAIModelOptions();
		$settings->addSelect( "openai_model", $openai_models, __( "Open AI Model", 'sheetspilot' ), $default_openai_model, $model_params );

		$debug_options = array(
      __("Disabled", 'sheetspilot') => "0",
      __("Enabled", 'sheetspilot') => "1"
    );
    
    $debug_params = array();
    $debug_params["description"] = __("Show or hide the inner debug prompt tool element inside the table workspace layout.", 'sheetspilot');
    
    $settings->addSelect(
        "enable_debug_prompt_tool", 
        $debug_options, 
        __("Enable Debug Prompt Tool", 'sheetspilot'), 
        "0", 
        $debug_params
    );

		$debug_request_params = array();
		$debug_request_params["description"] = __("When enabled, turns on debug prompt request mode (SheetsPilotGlobals::debug_prompt_request) and shows request/response data in the Apply Prompt debug panel.", 'sheetspilot');

		$settings->addSelect(
			"enable_debug_prompt_request",
			$debug_options,
			__( "Show Debug Request Window", 'sheetspilot' ),
			"0",
			$debug_request_params
		);

		if ( SheetsPilotGlobals::$enableAjaxSessionLog === true ) {
			$session_log_params = array();
			$session_log_params["description"] = __( "When enabled, shows the AJAX session log table and toggle button on the Request / Response Log page.", 'sheetspilot' );

			$settings->addSelect(
				"showSessionLog",
				$debug_options,
				__( "Show Session Log", 'sheetspilot' ),
				"0",
				$session_log_params
			);
		}

		return($settings);
	}
	
}