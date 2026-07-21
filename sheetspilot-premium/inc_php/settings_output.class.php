<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if(!defined("SHEETSPILOT_INC")) die("restricted access");


	class SheetsPilotUniteSettingsOutput{
		
		protected static $arrIDs = array();
		
		const BR = "\n";
		const BR2 = "\n\n";
		const TAB = "	";
		const TAB2 = "		";
		const TAB3 = "			";
		const TAB4 = "				";
		const TAB5 = "					";
		const TAB6 = "						";
		
		protected $arrSettings = array(); 
		protected $settings;
		protected $formID;
		
		protected static $serial = 0;
		
		protected $showDescAsTips = false;
		protected $wrapperID = "";
		protected $addCss = "";
		protected $settingsMainClass = "";
		protected $isParent = false;		//variable that this class is parent
		protected $isSidebar = false;
		
		const INPUT_CLASS_NORMAL = "unite-input-regular";
		const INPUT_CLASS_NUMBER = "unite-input-number";
		const INPUT_CLASS_ALIAS = "unite-input-alias";
		const INPUT_CLASS_LONG = "unite-input-long";
		const INPUT_CLASS_SMALL = "unite-input-small";
		
		//saps related variables
		
		protected $showSaps = false;
		protected $sapsType = null;
		protected $activeSap = 0;		
		
		const SAPS_TYPE_INLINE = "saps_type_inline";	//inline sapts type
		const SAPS_TYPE_CUSTOM = "saps_type_custom";	//custom saps tyle
	    const SAPS_TYPE_ACCORDION = "saps_type_accordion";
			    
	    
		/**
		 * 
		 * init the output settings
		 */
		public function init(SheetsPilotUniteSettings $settings){
			
			if($this->isParent == false)
				SheetsPilotFunctions::throwError("The output class must be parent of some other class.");
			
			$this->settings = new SheetsPilotUniteSettings();
			$this->settings = $settings;
		}
		
		
		/**
		 * validate that the output class is inited with settings
		 */
		protected function validateInited(){
			if(empty($this->settings))
				SheetsPilotFunctions::throwError("The output class not inited. Please call init() function with some settings class");
		}
		
		
		/**
		 * set add css. work with placeholder
		 * [wrapperid]
		 */
		public function setAddCss($css){
		
			$replace = "#".$this->wrapperID;
			$this->addCss = str_replace("[wrapperid]", $replace, $css);
		}
		
		/**
		 *
		 * set show descriptions as tips true / false
		 */
		public function setShowDescAsTips($show){
			$this->showDescAsTips = $show;
		}
		
		
		/**
		 *
		 * show saps true / false
		 */
		public function setShowSaps($show = true, $type = null){
		        
			if($type === null)
				$type = self::SAPS_TYPE_INLINE;
			
			$this->showSaps = $show;
						
			
			switch($type){
				case self::SAPS_TYPE_CUSTOM:
				case self::SAPS_TYPE_INLINE:
				case self::SAPS_TYPE_ACCORDION:
				break;
				default:
					SheetsPilotFunctions::throwError("Wrong saps type: $type ");
				break;
			}
			
			$this->sapsType = $type;
			
		}
		
		
		/**
		 * get default value add html
		 * @param $setting
		 */
		protected function getDefaultAddHtml($setting, $implodeArray = false){
			
			$defaultValue = SheetsPilotFunctions::getVal($setting, "default_value");
			if(is_array($defaultValue))
				$defaultValue = wp_json_encode($defaultValue);
			
			$defaultValue = esc_attr( (string) $defaultValue );
			
			//SheetsPilotFunctions::showTrace();exit();
			
			$value = SheetsPilotFunctions::getVal($setting, "value");
			if(is_array($value) || is_object($value)){
				if($implodeArray == false)
					return("");
				else
					$value = implode(",", $value);
			}
						
			$value = esc_attr( (string) $value );
			
			$addHtml = ' data-default="' . $defaultValue . '" data-initval="' . $value . '" ';
			
			$addParams = SheetsPilotFunctions::getVal($setting, SheetsPilotUniteSettings::PARAM_ADDPARAMS);
			if(!empty($addParams))
				$addHtml .= ' ' . trim( $addParams );
			
			return($addHtml);
		}
		
		
		/**
		 * prepare draw setting text
		 */
		protected function drawSettingRow_getText($setting){
		
			//modify text:
			$text = SheetsPilotFunctions::getVal($setting, "text", "");
			
			if(empty($text))
				return("");
				
			// prevent line break (convert spaces to nbsp)
			$text = str_replace(" ","&nbsp;",$text);
		
			switch($setting["type"]){
				case SheetsPilotUniteSettings::TYPE_CHECKBOX:
					$text = "<label for='".$setting["id"]."' style='cursor:pointer;'>$text</label>";
					break;
			}
		
			return($text);
		}
		
		
		/**
		 *
		 * get text style
		 */
		protected function drawSettingRow_getTextStyle($setting){
		
			//set text style:
			$textStyle = SheetsPilotFunctions::getVal($setting, SheetsPilotUniteSettings::PARAM_TEXTSTYLE);
		
			if($textStyle != "")
				$textStyle = "style='".$textStyle."'";
		
			return($textStyle);
		}
		
		
		/**
		 * get row style
		 */
		protected function drawSettingRow_getRowHiddenClass($setting){
			
			//set hidden			
			$isHidden = isset($setting["hidden"]);
			
			if($isHidden == true && $setting["hidden"] === "false")
				$isHidden = false;
			
			//operate saps
			if($this->showSaps == true && $this->sapsType == self::SAPS_TYPE_INLINE){
				
				$sap = SheetsPilotFunctions::getVal($setting, "sap");
				$sap = (int)$sap;
				
				if($sap != $this->activeSap)
					$isHidden = true;
			}

			$class = "";
			if($isHidden == true)
				$class = "unite-setting-hidden";
			
			return($class);
		}
		
		
		/**
		 *
		 * get row class
		 */
		protected function drawSettingRow_getRowClass($setting, $basClass = ""){
			
			//set text class:
			$class = $basClass;
			
			if(isset($setting["disabled"])){
				if(!empty($class))
					$class .= " ";
				
				$class .= "setting-disabled";
			}
			
			//add saps class
			if($this->showSaps && $this->sapsType == self::SAPS_TYPE_INLINE){
				
				$sap = SheetsPilotFunctions::getVal($setting, "sap");
				$sap = (int)$sap;
				$sapClass = "unite-sap-element unite-sap-".$sap;
				
				if(!empty($class))
					$class .= " ";
				
				$class .= $sapClass;
			}
			
			$showin = SheetsPilotFunctions::getVal($setting, "showin");
			if(!empty($showin)){
				if(!empty($class))
					$class .= " ";
				
				$class .= "uc-showin-{$showin}";
			}
				
			$classHidden = $this->drawSettingRow_getRowHiddenClass($setting);
			if(!empty($classHidden)){
				
				if(!empty($class))
					$class .= " ";
				
				$class .= $classHidden;
			}
			
			if(!empty($class))
				$class = "class='{$class}'";
			
				
			return($class);
		}
		
		
		
		
		/**
		* draw after body additional settings accesories
		*/
		public function drawAfterBody(){
			$arrTypes = $this->settings->getArrTypes();
			foreach($arrTypes as $type){
				switch($type){
					case self::TYPE_COLOR:
						?>
							<div id='divPickerWrapper' style='position:absolute;display:none;'><div id='divColorPicker'></div></div>
						<?php
					break;
				}
			}
		}
				
		
		/**
		 * 
		 * do some operation before drawing the settings.
		 */
		protected function prepareToDraw(){
			
			$this->settings->setSettingsStateByControls();
			$this->settings->setPairedSettings();
		}


		/**
		 * get setting class attribute
		 */
		protected function getInputClassAttr($setting, $defaultClass="", $addClassParam="", $wrapClass = true){
						
			$class = SheetsPilotFunctions::getVal($setting, "class", $defaultClass);
			$classAdd = SheetsPilotFunctions::getVal($setting, SheetsPilotUniteSettings::PARAM_CLASSADD);
			
			switch($class){
				case "alias":
					$class = self::INPUT_CLASS_ALIAS;
				break;
				case "long":
					$class = self::INPUT_CLASS_LONG;
				break;
				case "normal":
					$class = self::INPUT_CLASS_NORMAL;
				break;
				case "number":
					$class = self::INPUT_CLASS_NUMBER;
				break;
				case "small":
					$class = self::INPUT_CLASS_SMALL;
				break;
				case "nothing":
					$class = "";
				break;
			}
			
			if(!empty($classAdd)){
				if(!empty($class))
					$class .= " ";
				$class .= $classAdd;
			}
			
			if(!empty($addClassParam)){
				if(!empty($class))
					$class .= " ";
				$class .= $addClassParam;
			}
			
			$isTransparent = SheetsPilotFunctions::getVal($setting, SheetsPilotUniteSettings::PARAM_MODE_TRANSPARENT);
			if(!empty($isTransparent)){
				if(!empty($class))
					$class .= " ";
				$class .= "unite-setting-transparent";
			}
			
			if(!empty($class) && $wrapClass == true)
				$class = "class='$class'";
			
			return($class);
		}


		/**
		 * Echo class="..." from getInputClassAttr-style fragment or bare class name.
		 *
		 * @param string $class Class attribute fragment or bare class list.
		 */
		protected function echo_class_attr_fragment( $class ) {
			$class = (string) $class;
			if ( '' === trim( $class ) ) {
				return;
			}
			if ( preg_match( "/^class=(['\"])(.*)\\1$/s", $class, $matches ) ) {
				printf( ' class="%s"', esc_attr( $matches[2] ) );
				return;
			}
			printf( ' class="%s"', esc_attr( $class ) );
		}


		/**
		 * Echo style="..." from a style='...' fragment or bare CSS.
		 *
		 * @param string $style Style attribute fragment or bare CSS.
		 */
		protected function echo_style_attr_fragment( $style ) {
			$style = (string) $style;
			if ( '' === trim( $style ) ) {
				return;
			}
			if ( preg_match( "/^style=(['\"])(.*)\\1$/s", $style, $matches ) ) {
				printf( ' style="%s"', esc_attr( $matches[2] ) );
				return;
			}
			printf( ' style="%s"', esc_attr( $style ) );
		}


		/**
		 * Echo rows="..." from a rows='N' fragment or bare number.
		 *
		 * @param string $rows Rows attribute fragment or bare number.
		 */
		protected function echo_rows_attr( $rows ) {
			$rows = (string) $rows;
			if ( '' === trim( $rows ) ) {
				return;
			}
			if ( preg_match( "/^rows=(['\"])(\\d+)\\1$/", $rows, $matches ) ) {
				printf( ' rows="%s"', esc_attr( $matches[2] ) );
				return;
			}
			printf( ' rows="%s"', esc_attr( $rows ) );
		}


		/**
		 * Echo cols="..." from a cols='N' fragment or bare number.
		 *
		 * @param string $cols Cols attribute fragment or bare number.
		 */
		protected function echo_cols_attr( $cols ) {
			$cols = (string) $cols;
			if ( '' === trim( $cols ) ) {
				return;
			}
			if ( preg_match( "/^cols=(['\"])(\\d+)\\1$/", $cols, $matches ) ) {
				printf( ' cols="%s"', esc_attr( $matches[2] ) );
				return;
			}
			printf( ' cols="%s"', esc_attr( $cols ) );
		}


		/**
		 * Echo data-*, placeholder, step, multiple, style, and class attributes with escaped values.
		 *
		 * @param string $attr_string Space-separated attribute string from plugin helpers.
		 */
		protected function echo_sanitized_extra_attributes( $attr_string ) {
			$attr_string = trim( (string) $attr_string );
			if ( '' === $attr_string ) {
				return;
			}

			if ( ! preg_match_all( '/\s*([a-zA-Z][a-zA-Z0-9:_-]*)\s*(?:=\s*(["\'])(.*?)\2)?/s', $attr_string, $matches, PREG_SET_ORDER ) ) {
				return;
			}

			$allowed = array( 'placeholder', 'step', 'multiple', 'style', 'class', 'colspan', 'width' );
			foreach ( $matches as $match ) {
				$name       = $match[1];
				$name_lower = strtolower( $name );
				$is_data    = ( 0 === strpos( $name_lower, 'data-' ) );
				if ( ! $is_data && ! in_array( $name_lower, $allowed, true ) ) {
					continue;
				}
				if ( isset( $match[3] ) ) {
					$value = html_entity_decode( $match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					printf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
				} elseif ( 'multiple' === $name_lower ) {
					echo ' multiple';
				}
			}
		}
		
		
		
		
		/**
		 * modify image setting values
		 */
		protected function modifyImageSetting($setting){
			
			$value = SheetsPilotFunctions::getVal($setting, "value");
			$value = trim($value);
			
			$urlBase = SheetsPilotFunctions::getVal($setting, "url_base", null);
			
			if(!empty($value) && is_numeric($value) == false)
				$value = SheetsPilotHelper::URLtoFull($value, $urlBase);
			
			$defaultValue = SheetsPilotFunctions::getVal($setting, "default_value");
			$defaultValue = trim($defaultValue);
			
			if(!empty($defaultValue) && is_numeric($defaultValue) == false)
				$defaultValue = SheetsPilotHelper::URLtoFull($defaultValue, $urlBase);
			
			$setting["value"] = $value;
			$setting["default_value"] = $defaultValue;
			
			
			return($setting);
		}
	
		
		/**
		 * 
		 * draw imaeg input:
		 * @param $setting
		 */
		protected function drawImageInput($setting){
			
			dmp("no image input for now");
		}

		
		/**
		 *
		 * draw image input:
		 * @param $setting
		 */
		protected function drawMp3Input($setting){
			
			$previewStyle = "display:none";
		
			$setting = $this->modifyImageSetting($setting);
			
			$value = SheetsPilotFunctions::getVal($setting, "value");
		
			$class = $this->getInputClassAttr($setting, "", "unite-setting-mp3-input unite-input-image");
			
			$addHtml = $this->getDefaultAddHtml($setting);
		
			//add source param
			$source = SheetsPilotFunctions::getVal($setting, "source");
			if(!empty($source))
				$addHtml .= " data-source='{$source}'";
		
			?>
				<div class="unite-setting-mp3">
					<input type="text" id="<?php echo esc_attr($setting["id"])?>" name="<?php echo esc_attr($setting["name"])?>" <?php $this->echo_class_attr_fragment( $class ); ?> value="<?php echo esc_attr($value)?>" <?php $this->echo_sanitized_extra_attributes( $addHtml ); ?> />
					<a href="javascript:void(0)" class="unite-button-secondary unite-button-choose"><?php esc_html_e("Choose", "sheetspilot")?></a>
				</div>
			<?php
		}
		
		/**
		 *
		 * draw icon picker input:
		 * @param $setting
		 */
		protected function drawIconPickerInput($setting){
			
			dmp("no icon picker");
			
		}
		
		
		/**
		 * special inputs
		 */
		private function a______SPECIAL_INPUTS_____(){}
		
		
		/**
		 * draw icon picker input:
		 * @param $setting
		 */
		protected function drawMapPickerInput($setting){
			dmp("no map picker");
		}
		
		
		/**
		 * draw icon picker input:
		 * @param $setting
		 */
		protected function drawPostPickerInput($setting){
			dmp("drawPostPickerInput: function for override");
			exit();
		}
		
				
		/**
		 * draw module picker input:
		 * @param $setting
		 */
		protected function drawModulePickerInput($setting){
			dmp("drawModulePickerInput: function for override");
			exit();
		}
		
		
		/**
		 * draw color picker
		 * @param $setting
		 */
		protected function drawColorPickerInput($setting){	
			dmp("no color picker");
		}
		
		
		/**
		 * draw the editor by provider
		 */
		protected function drawEditorInput($setting){
			
			dmp("provider settings output - function to override");
			exit();
		}
		
		/**
		 * draw fonts panel - function for override
		 */
		protected function drawFontsPanel($setting){
			
			dmp("draw fonts panel - function for override");
			exit();
		}
		
		/**
		 * draw fonts panel - function for override
		 */
		protected function drawItemsPanel($setting){
			
			dmp("draw items panel - function for override");
			exit();
		}
		
		
		/**
		 * draw setting input by type
		 */
		protected function drawInputs($setting){
			
			switch($setting["type"]){
				case SheetsPilotUniteSettings::TYPE_TEXT:
					$this->drawTextInput($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_COLOR:
					$this->drawColorPickerInput($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_SELECT:
					$this->drawSelectInput($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_MULTISELECT:
					$this->drawMultiSelectInput($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_CHECKBOX:
					$this->drawCheckboxInput($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_RADIO:
					$this->drawRadioInput($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_TEXTAREA:
					$this->drawTextAreaInput($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_IMAGE:
					$this->drawImageInput($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_MP3:
					$this->drawMp3Input($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_ICON:
					$this->drawIconPickerInput($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_ADDON:
					$this->drawAddonPickerInput($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_MAP:
					$this->drawMapPickerInput($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_POST:
					$this->drawPostPickerInput($setting);
				break;
				case SheetsPilotUniteSettings::TYPE_EDITOR:
					$this->drawEditorInput($setting);
				break;
				case UniteCreatorSettings::TYPE_FONT_PANEL:
					$this->drawFontsPanel($setting);
				break;
				case UniteCreatorSettings::TYPE_ITEMS:
					$this->drawItemsPanel($setting);
				break;
				case UniteCreatorSettings::TYPE_BUTTON:
					$this->drawButtonInput($setting);
				break;
				case UniteCreatorSettings::TYPE_RANGE:
					$this->drawRangeSliderInput($setting);
				break;
				case UniteCreatorSettings::TYPE_HIDDEN:
					$this->drawHiddenInput($setting);
				break;
				case UniteCreatorSettings::TYPE_REPEATER:
					
					$this->drawRepeaterInput($setting);
					
				break;
				case UniteCreatorSettings::TYPE_TYPOGRAPHY:
					
					$this->drawTypographySetting($setting);
					
				break;
				case UniteCreatorSettings::TYPE_DIMENTIONS:
					
					$this->drawDimentionsSetting($setting);
					
				break;
				case SheetsPilotUniteSettings::TYPE_CUSTOM:
					if(method_exists($this,"drawCustomInputs") == false){
						SheetsPilotFunctions::throwError("Method don't exists: drawCustomInputs, please override the class");
					}
					$this->drawCustomInputs($setting);
				break;
				default:
					throw new Exception("drawInputs error: wrong setting type - ".esc_html( $setting["type"] ) ); 
				break;
			}
			
		}		
		
		
		/**
		 * draw text input
		 * @param $setting
		 */
		protected function drawRangeSliderInput($setting) {
			
			
			$setting[SheetsPilotUniteSettings::PARAM_CLASSADD] = "unite-setting-range";
			$setting["class"] = "nothing";
			$setting["type_number"] = true;
			
			$value = SheetsPilotFunctions::getVal($setting, "value");
			
			$min = SheetsPilotFunctions::getVal($setting, "min");
			$max = SheetsPilotFunctions::getVal($setting, "max");
			$step = SheetsPilotFunctions::getVal($setting, "step");
			
			if(empty($step))
				$step = 1;
			
			if($min === "" || is_numeric($min) == false)
				SheetsPilotFunctions::throwError("range error: should be min value");
			
			if($max === "" || is_numeric($max) == false)
				SheetsPilotFunctions::throwError("range error: should be max value");
			
			$defaultValue = SheetsPilotFunctions::getVal($setting, "default_value");
			
			$unit = SheetsPilotFunctions::getVal($setting, "range_unit");
			
			if($unit == "__hide__")
				$unit = null;
			
			?>
			<div class="unite-setting-range-wrapper">
				
				<input type="range" min="<?php echo esc_attr($min)?>" max="<?php echo esc_attr($max)?>" step="<?php echo esc_attr($step)?>" value="<?php echo esc_attr($value)?>" >
			<?php 
					
				$this->drawTextInput($setting);
				
				if(!empty($unit)):
				?>
				<span class="setting_unit"><?php echo esc_html($unit)?></span>
				<?php 
				endif;
			?>
				
			</div>
			<?php
		}
		
		
		/**
		 * draw repeater input
		 */
		protected function drawRepeaterInput($setting){
			
			$itemsValues = SheetsPilotFunctions::getVal($setting, "items_values");
						
			$strData = SheetsPilotFunctions::jsonEncodeForHtmlData($itemsValues, "itemvalues");
			
			$addItemText = SheetsPilotFunctions::getVal($setting, "add_button_text");
			if(empty($addItemText))
				$addItemText = esc_html__("Add Item", "sheetspilot");
			
			//get empty text
			$emptyText = SheetsPilotFunctions::getVal($setting, "empty_text");
			
			if(empty($emptyText))
				$emptyText = esc_html__("No Items Found", "sheetspilot");
			
			$objSettingsItems = SheetsPilotFunctions::getVal($setting, "settings_items");
			SheetsPilotFunctions::validateNotEmpty($objSettingsItems, "settings items");
			
			$emptyTextAddHtml = "";
			if(!empty($value))
				$emptyTextAddHtml = "style='display:none'";
			
			$output = new SheetsPilotUniteSettingsOutputWide();
			
			$output->init($objSettingsItems);
			
			//get item title
			$itemTitle = SheetsPilotFunctions::getVal($setting, "item_title");
			if(empty($itemTitle))
				$itemTitle = esc_html__("Item", "sheetspilot");
				
			$itemTitle = htmlspecialchars($itemTitle);
			
			//delete button text
			$deleteButtonText = SheetsPilotFunctions::getVal($setting, "delete_button_text");
			if(empty($deleteButtonText))
				$deleteButtonText = esc_html__("Delete Item","sheetspilot");
			
			$duplicateButtonText = SheetsPilotFunctions::getVal($setting, "duplicate_button_text");
			if(empty($duplicateButtonText))
				$duplicateButtonText = esc_html__("Duplicate Item","sheetspilot");
			
			$deleteButtonText = htmlspecialchars($deleteButtonText);
			$duplicateButtonText = htmlspecialchars($duplicateButtonText);
			
			
			?>
		      <div id="<?php echo esc_attr($setting["id"])?>" data-settingtype="repeater" <?php $this->echo_sanitized_extra_attributes( $strData ); ?> class="unite-settings-repeater unite-setting-input-object" data-name="<?php echo esc_attr($setting["name"])?>" data-itemtitle='<?php echo esc_attr($itemTitle)?>' data-deletetext="<?php echo esc_attr($deleteButtonText)?>" data-duplicatext="<?php echo esc_attr($duplicateButtonText)?>" >
		      	 
		      	 <div class="unite-repeater-emptytext" <?php $this->echo_style_attr_fragment( $emptyTextAddHtml ); ?>>
		      	 	<?php echo esc_html($emptyText)?>
		      	 </div>
		      	 
		      	 <div class="unite-repeater-template" style="display:none">
		      	 	
		      	 		<?php $output->draw("settings_item_repeater", false); ?>
		      	 		
		      	 </div>
		      	 
		      	 <div class="unite-repeater-items"></div>
		      	 
		      	 <a class="unite-button-secondary unite-repeater-buttonadd" ><?php echo esc_html( $addItemText ); ?></a>
		      	 
			  </div>
			  			  
			<?php
			
		}
		
		
		/**
		 * special inputs
		 */
		private function a______REGULAR_INPUTS______(){}
		
		
		/**
		 * draw text input
		 * @param $setting
		 */
		protected function drawTextInput($setting) {
						
			$disabled = "";
			$style="";
			$readonly = "";
			
			if(isset($setting["style"])) 
				$style = "style='".$setting["style"]."'";
			if(isset($setting["disabled"])) 
				$disabled = 'disabled="disabled"';
				
			if(isset($setting["readonly"])){
				$readonly = "readonly='readonly'";
			}
			
			$defaultClass = self::INPUT_CLASS_NORMAL;
			
			$typeNumber = SheetsPilotFunctions::getVal($setting, "type_number");
			$typeNumber = SheetsPilotFunctions::strToBool($typeNumber);
			
			$unit = SheetsPilotFunctions::getVal($setting, "unit");
			if(!empty($unit)){
				$defaultClass = self::INPUT_CLASS_NUMBER;
				if($unit == "px")
					$typeNumber = true;
			}
			
			$class = $this->getInputClassAttr($setting, $defaultClass);
			
			$addHtml = $this->getDefaultAddHtml($setting);
						
			$placeholder = SheetsPilotFunctions::getVal($setting, "placeholder", null);
			
			if($placeholder !== null){
				$addHtml .= ' placeholder="' . esc_attr( (string) $placeholder ) . '"';
			}
			
			$value = $setting["value"];
			$value = htmlspecialchars($value);
						
			$typePass = SheetsPilotFunctions::getVal($setting, "ispassword");
			$typePass = SheetsPilotFunctions::strToBool($typePass);
			
			//set input type
			
			$inputType = "text";
			if($typeNumber == true){
				$inputType = "number";
				$step = SheetsPilotFunctions::getVal($setting, "step");
				if(!empty($step) && is_numeric($step))
					$addHtml .= " step=\"{$step}\"";
			}
			
			if($typePass === true){
				$inputType = "password";
			}
			
			?>
				<input type="<?php echo esc_attr($inputType)?>" <?php $this->echo_class_attr_fragment( $class ); ?> <?php $this->echo_style_attr_fragment( $style ); ?> <?php disabled( isset( $setting['disabled'] ) ); ?><?php wp_readonly( isset( $setting['readonly'] ) ); ?> id="<?php echo esc_attr($setting["id"])?>" name="<?php echo esc_attr($setting["name"])?>" value="<?php echo esc_attr($value)?>" <?php $this->echo_sanitized_extra_attributes( $addHtml ); ?> />
			<?php
		}
		
		
		/**
		 * draw hidden input
		 */
		protected function drawHiddenInput($setting){
			
			$value = SheetsPilotFunctions::getVal($setting, "value");
			$value = htmlspecialchars($value);
			$addHtml = $this->getDefaultAddHtml($setting);
			
			?>
				<input type="hidden" id="<?php echo esc_attr($setting["id"])?>" name="<?php echo esc_attr($setting["name"])?>" value="<?php echo esc_attr($value)?>" <?php $this->echo_sanitized_extra_attributes( $addHtml ); ?> />
			<?php 
		}
		
		
		
		/**
		 * draw button input
		 */
		protected function drawButtonInput($setting){
			
			$name = $setting["name"];
			$id = $setting["id"];
			$value = $setting["value"];
			$href = "javascript:void(0)";
			$gotoView = SheetsPilotFunctions::getVal($setting, "gotoview");
			
			if(!empty($gotoView))
				$href = SheetsPilotHelper::getViewUrl($gotoView);
			
			?>
			<a id="<?php echo esc_attr($id)?>" href="<?php echo esc_attr($href)?>" name="<?php echo esc_attr($name)?>" class="unite-button-secondary"><?php echo esc_html($value)?></a>
			<?php 
			
		}
		
		
		/**
		 * draw text area input
		 */
		protected function drawTextAreaInput($setting){
			
			$disabled = "";
			if (isset($setting["disabled"])) 
				$disabled = 'disabled="disabled"';
			
			$style = "";
			if(isset($setting["style"]))
				$style = "style='".$setting["style"]."'";
			
			$rows = SheetsPilotFunctions::getVal($setting, "rows");
			if(!empty($rows))
				$rows = "rows='$rows'";
			
			$cols = SheetsPilotFunctions::getVal($setting, "cols");
			if(!empty($cols))
				$cols = "cols='$cols'";
			
			$addHtml = $this->getDefaultAddHtml($setting);
			
			$class = $this->getInputClassAttr($setting);
			$use_codemirror = SheetsPilotFunctions::getVal($setting, "codemirror");
			$use_codemirror = SheetsPilotFunctions::strToBool($use_codemirror);
			if ($use_codemirror) {
				if (!empty($class)) {
					$class = preg_replace("/class='(.*)'/", "class='$1 ubai-codemirror'", $class);
				} else {
					$class = "class='ubai-codemirror'";
				}
				$codemirror_mode = SheetsPilotFunctions::getVal($setting, "codemirror_mode");
				if (!empty($codemirror_mode)) {
					$addHtml .= " data-codemirror-mode=\"" . esc_attr($codemirror_mode) . "\"";
				}
			}
			
			$value = $setting["value"];
			$value = htmlspecialchars($value);
			
			?>
				<textarea id="<?php echo esc_attr($setting["id"])?>" <?php $this->echo_class_attr_fragment( $class ); ?> name="<?php echo esc_attr($setting["name"])?>" <?php $this->echo_style_attr_fragment( $style ); ?> <?php disabled( isset( $setting['disabled'] ) ); ?> <?php $this->echo_rows_attr( $rows ); ?> <?php $this->echo_cols_attr( $cols ); ?> <?php $this->echo_sanitized_extra_attributes( $addHtml ); ?> ><?php echo esc_html($value)?></textarea>
			<?php
			if(!empty($cols))
				echo "<br>";	//break line on big textareas.
		}		
		
		
		/**
		 * draw radio input
		 */
		protected function drawRadioInput($setting){
			
			$items = $setting["items"];
			$counter = 0;
			$settingID = $setting["id"];
			$isDisabled = SheetsPilotFunctions::getVal($setting, "disabled");
			$isDisabled = SheetsPilotFunctions::strToBool($isDisabled);
			$settingName = $setting["name"];
			$defaultValue = SheetsPilotFunctions::getVal($setting, "default_value");
			$settingValue = SheetsPilotFunctions::getVal($setting, "value");
			
			$class = $this->getInputClassAttr($setting);
			
			$specialDesign = SheetsPilotFunctions::getVal($setting, "special_design");
			$specialDesign = SheetsPilotFunctions::strToBool($specialDesign);
			
			if($this->isSidebar == false)
				$specialDesign = false;
			
			$addClass = "";
			if($specialDesign == true){
				$addClass = " unite-radio-special";
				$numItems = count($items);
				switch($numItems){
					case 2:
						$addClass .= " split-two-columns";
					break;
					case 3:
						$addClass .= " split-three-columns";
					break;
					case 4:
						$addClass .= " split-four-columns";
					break;
					default:
						$addClass = "";
					break;
				}
				
				$designColor = SheetsPilotFunctions::getVal($setting, "special_design_color");
				if(!empty($designColor))
					$addClass .= " unite-radio-color-$designColor";
			
			}
			
			?>
			<span id="<?php echo esc_attr($settingID) ?>" class="radio_wrapper<?php echo esc_attr($addClass)?>">
			
			<?php 
			
			foreach($items as $text=>$value):
				$counter++;
				$radioID = $settingID."_".$counter;
				
				$classLabel = "unite-radio-item-label-$counter";
				
				$strChecked = "";				
				if($value == $settingValue) 
					$strChecked = " checked";
				
				$strDisabled = "";
				if($isDisabled)
					$strDisabled = 'disabled = "disabled"';
				
				$addHtml = "";
				if($value == $defaultValue)
					$addHtml .= " data-defaultchecked=\"true\"";
				
				if($value == $settingValue){
					$addHtml .= " data-initchecked=\"true\"";
				}
				
				?>					
					<input type="radio" id="<?php echo esc_attr($radioID)?>" value="<?php echo esc_attr($value)?>" name="<?php echo esc_attr($settingName)?>" style="<?php echo esc_attr( 'cursor:pointer;' ); ?>" <?php checked( $value, $settingValue ); ?> <?php disabled( $isDisabled ); ?> <?php $this->echo_sanitized_extra_attributes( $addHtml ); ?> <?php $this->echo_class_attr_fragment( $class ); ?>/>
					<label class="<?php echo esc_attr($classLabel)?>" for="<?php echo esc_attr($radioID)?>" ><?php echo esc_html($text)?></label>
					
					<?php if($specialDesign == false):?>
					&nbsp; &nbsp;
					<?php endif?>
				<?php				
			endforeach;
			
			?>
			</span>
			<?php 
		}
		
		
		/**
		 * draw checkbox
		 */
		protected function drawCheckboxInput($setting){
			
			$checked = "";
			
			$value = SheetsPilotFunctions::getVal($setting, "value");
			$value = SheetsPilotFunctions::strToBool($value);
			
			if($value == true) 
				$checked = 'checked="checked"';
			
				$textNear = SheetsPilotFunctions::getVal($setting, "text_near");
			
			$settingID = $setting["id"];
			
			if(!empty($textNear)){
				$textNearAddHtml = "";
				if($this->showDescAsTips == true){
					$description = SheetsPilotFunctions::getVal($setting, "description");
					$description = htmlspecialchars($description);
					$textNearAddHtml = " title='$description' class='uc-tip'";
				}
				$textNear = "<label for=\"{$settingID}\"{$textNearAddHtml}>$textNear</label>";
			}
			
			$defaultValue = SheetsPilotFunctions::getVal($setting, "default_value");
			$defaultValue = SheetsPilotFunctions::strToBool($defaultValue);
			
			$addHtml = "";
			if($defaultValue == true)
				$addHtml .= " data-defaultchecked=\"true\"";
			
			if($value)
				$addHtml .= " data-initchecked=\"true\"";
			
			$class = $this->getInputClassAttr($setting);
			
			?>
				<input type="checkbox" id="<?php echo esc_attr($settingID)?>" <?php $this->echo_class_attr_fragment( $class ); ?> name="<?php echo esc_attr($setting["name"])?>" <?php checked( $value ); ?> <?php $this->echo_sanitized_extra_attributes( $addHtml ); ?>/>
			<?php
			if(!empty($textNear))
				echo esc_html($textNear);
		}
		
		
		/**
		 * draw select input
		 */
		protected function drawSelectInput($setting){
			
			$type = SheetsPilotFunctions::getVal($setting, "type");
			
			$name = SheetsPilotFunctions::getVal($setting, "name");
						
			$isMultiple = false;
			if($type == "multiselect")
				$isMultiple = true;
			
			$disabled = "";
			if(isset($setting["disabled"])) 
				$disabled = 'disabled="disabled"';
			
			$args = SheetsPilotFunctions::getVal($setting, "args");
			
			$settingValue = $setting["value"];
						
			if(is_array($settingValue) == false && strpos($settingValue,",") !== false)
				$settingValue = explode(",", $settingValue);
						
			$addHtml = $this->getDefaultAddHtml($setting, true);
						
			if($isMultiple == true){
				$addHtml .= " multiple";
			}
			
			$class = $this->getInputClassAttr($setting);
			
			$arrItems = SheetsPilotFunctions::getVal($setting, "items",array());
			if(empty($arrItems))
				$arrItems = array();
			
			?>
			<select id="<?php echo esc_attr($setting["id"])?>" name="<?php echo esc_attr($setting["name"])?>" <?php disabled( isset( $setting['disabled'] ) ); ?> <?php $this->echo_class_attr_fragment( $class ); ?> <?php $this->echo_sanitized_extra_attributes( $args ); ?> <?php $this->echo_sanitized_extra_attributes( $addHtml ); ?>>
			<?php
			foreach($arrItems as $text=>$value):
				
				$addition = "";
				$is_selected = is_array( $settingValue )
					? ( array_search( $value, $settingValue, true ) !== false )
					: ( $value == $settingValue );
				
				?>
					<option <?php $this->echo_sanitized_extra_attributes( $addition ); ?> value="<?php echo esc_attr($value)?>" <?php selected( $is_selected ); ?>><?php echo esc_html($text)?></option>
				<?php
			endforeach
			?>
			</select>
			<?php
		}

		
		/**
		 * draw select input
		 */
		protected function drawMultiSelectInput($setting){
			
			$this->drawSelectInput($setting);
			
		}
		
		/**
		 * draw text row
		 * @param unknown_type $setting
		 */
		protected function drawTextRow($setting){
			echo "draw text row - override this function";
		}

		
		/**
		 * draw hr row - override
		 */
		protected function drawHrRow($setting){
			echo "draw hr row - override this function";
		}
		
		
		/**
		 * draw typography setting
		 */
		protected function drawTypographySetting($setting){
			?>
			<?php echo esc_html( __("The typography setting will be visible in Elementor Page Builder","sheetspilot") );?>
			<?php 
		}
		
		/**
		 * draw dimentions setting
		 */
		protected function drawDimentionsSetting($setting){
			
			dmp("draw dimentions setting - function for override");
			// function for override
			
		}
		
		
		/**
		 * draw input additinos like unit / description etc
		 */
		protected function drawInputAdditions($setting,$showDescription = true){
			
			$description = SheetsPilotFunctions::getVal($setting, "description");
			if($showDescription === false)
				$description = "";
			$unit = SheetsPilotFunctions::getVal($setting, "unit");
			$required = SheetsPilotFunctions::getVal($setting, "required");
			$addHtml = SheetsPilotFunctions::getVal($setting, SheetsPilotUniteSettings::PARAM_ADDTEXT);
			
			?>
			
			<?php if(!empty($unit)):?>
			<span class='setting_unit'><?php echo esc_html($unit)?></span>
			<?php endif?>
			<?php if(!empty($required)):?>
			<span class='setting_required'>*</span>
			<?php endif?>
			<?php if(!empty($addHtml)):?>
			<span class="settings_addhtml"><?php echo esc_html($addHtml)?></span>
			<?php endif?>					
			<?php if(!empty($description) && $this->showDescAsTips == false):?>
			<span class="description"><?php echo wp_kses_post( $description ); ?></span>
			<?php endif?>
			
			<?php 
		}
		
				
		
		/**
		 * get options
		 */
		protected function getOptions(){
			
			$idPrefix = $this->settings->getIDPrefix();
			
			$options = array();
			$options["show_saps"] = $this->showSaps;
			$options["saps_type"] = $this->sapsType;
			$options["id_prefix"] = $idPrefix;
			
			return($options);
		}
		
		
		/**
		* set form id
		 */
		public function setFormID($formID){
			
			if(isset(self::$arrIDs[$formID]))
				SheetsPilotFunctions::throwError("Can't output settings with the same ID: $formID");
			
			self::$arrIDs[$formID] = true;
			
			SheetsPilotFunctions::validateNotEmpty($formID, "formID");
			
			$this->formID = $formID;
			
		}
		
		
		/**
		 *
		 * insert settings into saps array
		 */
		private function groupSettingsIntoSaps(){
		    
		    $arrSaps = $this->settings->getArrSaps();
		    $arrSettings = $this->settings->getArrSettings();
		    
		    //group settings by saps
		    foreach($arrSettings as $key=>$setting){
		        
		        $sapID = $setting["sap"];
		        
		        if(isset($arrSaps[$sapID]["settings"]))
		            $arrSaps[$sapID]["settings"][] = $setting;
		            else
		                $arrSaps[$sapID]["settings"] = array($setting);
		    }
		    		    
		    return($arrSaps);
		}
		
		
		private function a______DRAW_GENENRAL_____(){}
		
		
		/**
		 * get controls for client side
		 * eliminate only one setting in children
		 */
		private function getControlsForJS(){
			
			$controls = $this->settings->getArrControls(true);
			$arrChildren = $controls["children"];
			
			if(empty($arrChildren))
				return($controls);
			
			$arrChildrenNew = array();
			
			foreach($arrChildren as $name=>$arrChild){
				if(count($arrChild)>1)
					$arrChildrenNew[$name] = $arrChild;
			}
			
			$controls["children"] = $arrChildrenNew;
			
			return($controls);
		}
		
		
		/**
		 * draw wrapper start
		 */
		public function drawWrapperStart(){
			
			
			SheetsPilotFunctions::validateNotEmpty($this->settingsMainClass, "settings main class not found, please use wide, inline or sidebar output");
			
			//get options
			$options = $this->getOptions();
			$strOptions = SheetsPilotFunctions::jsonEncodeForHtmlData($options);
			
			//get controls
			$controls = $this->getControlsForJS();
			
			
			/*
			if(!empty($controls["children"])){
				dmp($controls);exit();
			}
			*/
			
			$addHtml = "";
			if(!empty($controls)){
				$addHtml = ' data-controls="' . esc_attr( wp_json_encode( $controls ) ) . '"';
			}
			
			
			if(!empty($this->addCss)):
			?>
				<!-- settings add css -->
				<style type="text/css">
					<?php echo wp_strip_all_tags( $this->addCss ); ?>
				</style>
			<?php
			endif;
			
			?>
			<div id="<?php echo esc_attr($this->wrapperID)?>" data-options="<?php echo esc_attr($strOptions)?>" <?php $this->echo_sanitized_extra_attributes( $addHtml ); ?> autofocus="true" class="unite_settings_wrapper <?php echo esc_attr( $this->settingsMainClass ); ?> unite-settings unite-inputs">
			
			<?php
		}
		
		
		/**
		 * draw wrapper end
		 */
		public function drawWrapperEnd(){
			
			?>
			
			</div>
			<?php 
		}
		
		
		/**
		 * function for override
		 */
		protected function setDrawOptions(){}
		
		/**
		 * 
		 * draw settings function
		 * @param $drawForm draw the form yes / no
		 * if filter sapid present, will be printed only current sap settings
		 */
		public function draw($formID, $drawForm = false){
			
			if(empty($this->settings))
				SheetsPilotFunctions::throwError("No settings are inited. Please init the settings in output class");
			
			$this->setDrawOptions();
				
			$this->setFormID($formID);
			
			$this->drawWrapperStart();
			
			
			if($this->showSaps == true){
			     
			     switch($this->sapsType){
			         case self::SAPS_TYPE_INLINE:
			             $this->drawSapsTabs();
			         break;
			         case self::SAPS_TYPE_CUSTOM:
			             $this->drawSaps();
			         break;
			     }  
			     
			}
			
			
			if($drawForm == true){
				
				if(empty($formID))
					SheetsPilotFunctions::throwError("The form ID can't be empty. you must provide it");
				
				?>
				<form name="<?php echo esc_attr($formID)?>" id="<?php echo esc_attr($formID)?>">
					<?php $this->drawSettings() ?>
				</form>
				<?php 				
			}else
				$this->drawSettings();
			
			?>
			
			<?php 
			
			$this->drawWrapperEnd();
			
		}

		
		/**
		 * draw wrapper before settings
		 */
		protected function drawSettings_before(){
		}
		
		
		/**
		* draw wrapper end after settings
		*/
		protected function drawSettingsAfter(){
		}
		

		/**
		 * draw single setting
		 */
		public function drawSingleSetting($name){
			
			$arrSetting = $this->settings->getSettingByName($name);
			
			$this->drawInputs($arrSetting);
			$this->drawInputAdditions($arrSetting);
		}
		
		
		/**
		 * function for override
		 */
		protected function drawSaps(){}
		
		
		/**
		 * draw saps tabs
		 */
		protected function drawSapsTabs(){
			
			$arrSaps = $this->settings->getArrSaps();
			
			?>
			<div class="unite-settings-tabs">
				
				<?php foreach($arrSaps as $key=>$sap){
					$text = $sap["text"];
					SheetsPilotFunctions::validateNotEmpty($text,"sap $key text");
					
					$class = "";
					if($key == $this->activeSap)
						$class = "class='unite-tab-selected'";
					
					?>
					<a href="javascript:void(0)" <?php $this->echo_class_attr_fragment( $class ); ?> data-sapnum="<?php echo esc_attr($key)?>" onfocus="this.blur()"><?php echo esc_html($text)?></a>
					<?php 
					
				}
				?>
				
			</div>
			<?php 
			
		}
		
		/**
		 * draw setting row by type
		 *
		 */
		private function drawSettingsRowByType($setting, $mode){
		    		    
		    switch($setting["type"]){
		        case SheetsPilotUniteSettings::TYPE_HR:
		            $this->drawHrRow($setting);
		            break;
		        case SheetsPilotUniteSettings::TYPE_STATIC_TEXT:
		            $this->drawTextRow($setting);
		            break;
		        default:
		            $this->drawSettingRow($setting, $mode);
		        break;
		    }
		    
		}
		
		
		/**
		 * draw settings - all together
		 */
		private function drawSettings_settings($filterSapID = null, $mode=null, $arrSettings = null){
		    
			if(is_null($arrSettings))
				$arrSettings = $this->arrSettings;
			
		    $this->drawSettings_before();
		    
		    foreach($arrSettings as $key=>$setting){
		            
		            if(isset($setting[SheetsPilotUniteSettings::PARAM_NODRAW]))
		                continue;
		                
		                if($filterSapID !== null){
		                    $sapID = SheetsPilotFunctions::getVal($setting, "sap");
		                    if($sapID != $filterSapID)
		                        continue;
		                }
		                
		                $this->drawSettingsRowByType($setting, $mode);
		                
		        }
		        
		        $this->drawSettingsAfter();
		     
		}
		
		
		/**
		 * draw sap before override
		 * @param unknown $sap
		 */
		protected function drawSapBefore($sap, $key){
		    dmp("function for override");
		    
		}
		
		protected function drawSapAfter(){
		    dmp("function for override");
		}
		
		
		/**
		 * draw settings - all together
		 */
		private function drawSettings_saps($filterSapID = null, $mode=null){
		    
		    $arrSaps = $this->groupSettingsIntoSaps();
		    
		        //draw settings - advanced - with sections
		        foreach($arrSaps as $key=>$sap):
		        		
		        		$arrSettings = $sap["settings"];
		        		
		        		$nodraw = SheetsPilotFunctions::getVal($sap, "nodraw");
		        		if($nodraw === true)
		        			continue;
		        		
		                $this->drawSapBefore($sap, $key);
						
						$this->drawSettings_settings($filterSapID, $mode, $arrSettings);
						
						$this->drawSapAfter();
						
		        
		        endforeach;
		    
		}
		
		
		
		/**
		 * draw all settings
		 */
		public function drawSettings($filterSapID = null){
			
			$this->prepareToDraw();
			
			$arrSettings = $this->settings->getArrSettings();
			if(empty($arrSettings))
			    $arrSettings = array();
			    
			$this->arrSettings = $arrSettings;

			//set special mode
			$mode = "";
			if(count($arrSettings) == 1 && $arrSettings[0]["type"] == SheetsPilotUniteSettings::TYPE_EDITOR)
			    $mode = "single_editor";
			
			
			if($this->showSaps == true && $this->sapsType == self::SAPS_TYPE_ACCORDION)
			    $this->drawSettings_saps($filterSapID, $mode);
			else			     
			    $this->drawSettings_settings($filterSapID, $mode);
			
		  
		}
		
		
		
	}

?>