<?php	
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if(!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilot_PluginViewSettings{

	
	/**
	 * constructor
	 */
	public function __construct(){
		
		$this->putViewHtml();
		
	}

	/**
	 * draw save settings button
	 */
	protected function drawSaveSettingsButton(){
				
		$prefix="unlimitedai_settings";
		 
		$buttonText = esc_html__("Save Settings", 'sheetspilot');
		
		?>
			<div class="uc-button-action-wrapper">
			
				<a id="<?php echo esc_attr($prefix)?>_button_save_settings" data-prefix="<?php echo esc_attr($prefix)?>" class="unite-button-primary doubly-button-save-settings" href="javascript:void(0)"><?php echo esc_html($buttonText)?></a>
				
				<div style="padding-top:6px;">
					
					<span id="<?php echo esc_attr($prefix)?>_loader_save" class="loader_text" style="display:none"><?php esc_html_e("Saving...", 'sheetspilot')?></span>
					<span id="<?php echo esc_attr($prefix)?>_message_saved" class="unite-color-green" style="display:none"></span>
					
				</div>
			</div>
			
			<div class="unite-clear"></div>
			
			<div id="<?php echo esc_attr($prefix)?>_save_settings_error" class="unite_error_message" style="display:none"></div>
			
		<?php 
	}
	
	
	/**
	 * put view html
	 */
	private function putViewHtml(){
		
		$nonce = SheetsPilotHelper::getNonce();
		
		$settingsName = SheetsPilotGlobals::OPTIONS_GROUP_NAME;
				
		$settings = SheetsPilotHelper::getGeneralSettingsObject();
		
		$formID = "sheetspilot_general_settings";
		
		$output = new SheetsPilotUniteSettingsOutputWide();
		$output->init($settings);
		$output->setFormID($formID);
		
		//$output->setShowSaps();
		
		$title = __("Unlimited AI Settings",'sheetspilot');
				
		?>
			<div class="wrap" id="uc_settings_page_wrapper">
		  
		    <div id="div_debug" class="unite-div-debug" style="display:none"></div>
			
			<h1><?php echo esc_html($title)?></h1>		
				
				<br><br>
								
				<div class="unlimited-ai-main-settings-wrapper">
					<?php 
					$output->draw("unlimited_ai_main_settings",true);
					?>
				</div>				
				
				<br>
				<?php
				$this->drawSaveSettingsButton();
				?>
				
			</div>
<script>

var g_doublyNonce = "<?php echo esc_js( $nonce ); ?>";
var g_urlAjaxActionsSheetsPilot = "<?php echo esc_js( esc_url( SheetsPilotGlobals::$urlAjax ) ); ?>";

jQuery(document).ready(function(){
	
	var objSettingsView = new SheetsPilot_SettingsView();
	objSettingsView.init();
	
});

</script>

<?php 
	}
	
}

new SheetsPilot_PluginViewSettings();