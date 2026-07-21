<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if(!defined("SHEETSPILOT_INC")) die("restricted access");

	if(!function_exists("dmp")){
		function dmp($str){
			echo "<div align='left' style='direction:ltr;'>";
			echo "<pre>";
			print_r($str);
			echo "</pre>";
			echo "</div>";
		}
	}

	if ( ! function_exists( 'dmpHTML' ) ) {
		function dmpHTML( $str ) {
			echo '<div align="left" style="direction:ltr;"><pre>' . esc_html( htmlspecialchars( print_r( $str, true ), ENT_QUOTES, 'UTF-8' )  ). '</pre></div>';
			
		}
	}


