<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if (!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilotFrontProcessing
{
	function __construct(){
		add_action('template_redirect', function(){
			if( isset( $_GET['post_preview'] )  && isset( $_GET['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'sheetspilot_actions' ) ){
				wp_redirect( get_preview_post_link( (int)$_GET['post_preview'] ), 302 );
				die();
			}
		});

	}					

}
new SheetsPilotFrontProcessing();
