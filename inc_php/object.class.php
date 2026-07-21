<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if(!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilot_Object{
	
	private $type;
	private $id;
	private $object;
	
	
	/**
	 * init object by id and type
	 */
	public function init($type, $id){
		
		$objIntegrations = new SheetsPilot_Integrations();

		$this->id = $id;
		$this->type = $type;
		
		switch($type){
			case SheetsPilotGlobals::EXPORT_TYPE_SNIPPET:
				
				$this->object = $objIntegrations->getSnippetById($id);
				
			break;
			default:
				
				$type = esc_html($type);
				$id = (int)$id;
				
				SheetsPilotFunctions::throwError("Object $type with id: $id not found");
				
			break;
		}
				
		
	}
	
}