<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if(!defined("SHEETSPILOT_INC")) die("restricted access");


class SheetsPilotUniteFunctionsWP{
	
	private static $arrCacheUrlImages = array();
	private static $arrCacheWooAttributes = array();
	private static $db;
	private static $isSvgUploadAllowed = false;
	private static $arrUrlThumbCache = array();
	
	
	const THUMB_SMALL = "thumbnail";
	const THUMB_MEDIUM = "medium";
	const THUMB_LARGE = "large";
	const THUMB_MEDIUM_LARGE = "medium_large";
	const THUMB_FULL = "full";
	
	
	/**
	 * get DB
	 */
	public static function getDB(){
		
		if(empty(self::$db))
			self::$db = new SheetsPilot_PluginDB();
			
		return(self::$db);
	}
	
	/**
	 * get roles names
	 */
	public static function getRolesNames(){
		
		global $wp_roles;
		
		if(empty($wp_roles))
			$wp_roles = new WP_Roles();
		
		$arrNames = $wp_roles->get_names();
		
		return($arrNames);
	}
	
	
	/**
	 * get post record
	 */
	public static function getPostRecord($postID){
		
		$postID = (int)$postID;
		
		if(empty($postID))
			return(null);
		
		$db = SheetsPilotHelper::getDB();
		
		$response = $db->fetch(SheetsPilotGlobals::$tablePosts,array("ID"=>$postID));
		
		if(empty($response))
			return(null);
			
		$postRecord = $response[0];
		
		return($postRecord);
	}
	
	
	/**
	 * get post by name
	 */
	public static function getPostByPostName($name, $post_type){

		$args = array(
			'name'        => $name,
			'post_type'   => $post_type,
			'post_status' => 'any',
			'numberposts' => 1
		);

		$posts = get_posts($args);

		if (empty($posts)) {
			return null;
		}

		return $posts[0];
	}
	
	/**
	 * modify meta array
	 */
	public static function modifyArrMetaForOutput($arrMeta){
		
		$arrMetaOutput = array();
		
		foreach($arrMeta as $key=>$item){
			
			if(is_array($item) && count($item) == 1)
				$item = $item[0];
			
			$arrMetaOutput[$key] = $item;
		}
		
		return($arrMetaOutput);
	}
	
	/**
	 * get acf data types of post
	 */
	public static function getAcfPostDataTypes($postID){

		$isAcfExists = class_exists('ACF');
		
		if($isAcfExists == false)
			return(null);

		if(function_exists("get_field_objects") == false)
			return(null);
			
		$arrData = get_field_objects($postID, false, false);

		if(empty($arrData))
			return(null);
		
		$arrOutput = array();
		
		foreach($arrData as $key => $arrField){
			
			$type = SheetsPilotFunctions::getVal($arrField, "type");
			
			$arrOutput[$key] = $type;
		}
		
		return($arrOutput);
	}
	
	/**
	 * get post meta types
	 */
	public static function getPostMetaTypes($postID){
		
		$arrTypes = self::getAcfPostDataTypes($postID);
		
		if(!empty($arrTypes))
			return($arrTypes);
		
		//maybe add other types here
			
		return(array());
	}
	
	/**
	 * get post meta records
	 */
	public static function getPostMetaRecords($postID){
		
		$arrMeta = get_post_meta($postID);
		
		$arrMetaOutput = self::modifyArrMetaForOutput($arrMeta);
		
		return($arrMetaOutput);
	}
	
	/**
	 * get term meta records
	 */
	public static function getTermMetaRecords($termID){
		
		$arrMeta = get_term_meta($termID, '', true);
		
		$arrMetaOutput = self::modifyArrMetaForOutput($arrMeta);
		
		return($arrMetaOutput);
	}
	
	
	/**
	 * get post by name
	 */
	public static function getPostByName( $post_name, $post_type = 'post' ){
		
		$args = array();
		$args["post_name__in"] = array($post_name);
		$args["post_type"] = $post_type;
		$args["numberposts"] = 1;
				
		$arrPosts = get_posts($args);
		if(empty($arrPosts))
			return(null);
		
		$post = $arrPosts[0];
		return($post);
	}	
	
	/**
	 * get image url from image ID. if not found return null
	 */
	public static function getImageUrlByID($imageID){
		
		if(isset(self::$arrCacheUrlImages[$imageID]))
			return(self::$arrCacheUrlImages[$imageID]);
		
		$urlImage = wp_get_attachment_url($imageID);
		
		self::$arrCacheUrlImages[$imageID] = $urlImage;
		
		return($urlImage);
	}
	
	
	
	/**
	 * 
	 * get post type taxomonies
	 */
	public static function getPostTypeTaxomonies($postType){
		
		$arrTaxonomies = get_object_taxonomies(array( 'post_type' => $postType ), 'objects');
				
		$arrNames = array();
		foreach($arrTaxonomies as $key=>$objTax){
			$name = $objTax->labels->singular_name;
			if(empty($name))
				$name = $objTax->labels->name;
			
			$arrNames[$objTax->name] = $objTax->labels->singular_name;
		}
		
		return($arrNames);
	}
	
	
	/**
	 * get terms array parents
	 */
	public static function getArrTermsParents($arrTerms){

		$arrAllIDs = array();
		
		foreach($arrTerms as $term){
			
			$arrParents = array();
			
			$termID = $term->term_id;
			
			$parentID = $term->parent;
			
			if(empty($parentID))
				continue;
			
			$taxonomy = $term->taxonomy;
				
			$arrParentsIDs = get_ancestors( $termID, $taxonomy, 'taxonomy' );
			
			$arrAllIDs = array_merge($arrAllIDs, $arrParentsIDs);
		}
		
		if(empty($arrAllIDs))
			return(array());
			
		$arrAllIDs = array_unique($arrAllIDs);
		
		$arrTerms = self::getTermsByIDs($arrAllIDs);
		
		return($arrTerms);
	}
	
	
	/**
	 * get terms by ids, one by one
	 */
	public static function getTermsByIDs($arrTermIDs){
		
		$arrTerms = array();
		
		foreach($arrTermIDs as $id){
			
			$term = get_term($id);
			
			if(empty($term))
				continue;
			
			$arrTerms[] = $term;
		}
			
		return($arrTerms);
	}
	
	
	/**
	 * get post terms with all taxonomies
	 */
	public static function getPostTerms($post){
		
		if(empty($post))
			return(array());
		
		$postType = $post->post_type;
		$postID = $post->ID;
		
		if(empty($postID))
			return(array());
		
		$arrTaxonomies = self::getPostTypeTaxomonies($postType);
		
		if(empty($arrTaxonomies))
			return(array());
		
		$arrAllTerms = array();
		
		foreach($arrTaxonomies as $taxName => $taxTitle){
			
			$arrTerms = wp_get_post_terms($postID, $taxName);
			
			$isError = is_wp_error($arrTerms);
			if($isError == true)
				SheetsPilotFunctions::throwError("can't get post terms");
			
			if(empty($arrTerms))
				continue;
			
			$arrAllTerms = array_merge($arrAllTerms, $arrTerms);
		}
		
		
		return($arrAllTerms);
	}
	
	
	/**
	 * get posts objects from id's array
	 * with validation that every post exists
	 */
	public static function getPostsObjectsFromArrIDs($arrIDs){
		
		$arrPosts = array();
		foreach($arrIDs as $postID){
			
			$postID = (int)$postID;
			if(empty($postID))
				SheetsPilotFunctions::throwError("Post id not valid");
			
			$post = get_post($postID);
			
			if(empty($post))
				SheetsPilotFunctions::throwError("Post with id: $postID not found");
			
			$arrPosts[] = $post;
		}
		
		return($arrPosts);
	}
	
	
	
	/**
	 * update bulk post meta values
	 */
	public static function updatePostMetaBulk($postID, $arrMeta){
		
		if(empty($postID))
			return(false);
		
		if(empty($arrMeta))
			return(false);
		
		if(is_array($arrMeta) == false)
			return(false);
				
		foreach($arrMeta as $key=>$value){
			
			$value = SheetsPilotFunctions::maybeUnserialize($value);
			
			if(is_string($value)){
				$value = str_replace("\\", "\\\\", $value);
				$value = str_replace("\\\\\\", "\\\\", $value);
			}
			
			update_post_meta($postID, $key, $value);
			
		}
	}
	
	/**
	 * update bulk post meta values
	 */
	public static function updateTermMetaBulk($termID, $arrMeta){
		
		if(empty($termID))
			return(false);
			
		if(empty($arrMeta))
			return(false);
		
		if(is_array($arrMeta) == false)
			return(false);
				
		foreach($arrMeta as $key=>$value){
			update_term_meta($termID, $key, $value);
		}
	}
	
	
	/**
	 * delete term by slug
	 */
	public static function deleteTermBySlug($slug, $taxonomy){
		
		$objTerm = self::getTermBySlug($taxonomy, $slug);
		
		if(empty($objTerm))
			return(false);
			
		$termID = $objTerm->term_id;
		
		wp_delete_term($termID, $taxonomy);
		
	}
	
	
	/**
	 * get term by slug
	 */
	public static function getTermBySlug($taxonomy, $slug){
		
		$args = array();
		$args["slug"] = $slug;
		$args["taxonomy"] = $taxonomy;
		$args["hide_empty"] = false;
		
		$arrTerms = get_terms($args);
		
		if(is_wp_error($arrTerms))
			return(null);
		
		if(empty($arrTerms))
			return(null);
		
		$term = $arrTerms[0];
		
		return($term);
	}
	
	/**
	 * remove term from post
	 */
	public static function removeTermFromPost($postID, $term){
		
		if(empty($term))
			return(false);
		
		$termID = $term->term_id;
		$taxonomy = $term->taxonomy;
		
		wp_remove_object_terms($postID, array($termID), $taxonomy);		
	}
	
	
	/**
	 * update terms counts
	 */
	public static function updateTermsCounts($arrTerms){
		
		foreach($arrTerms as $term){
			
			$termTaxID = $term->term_taxonomy_id;
			$taxonomy = $term->taxonomy;
						
			wp_update_term_count_now(array($termTaxID), $taxonomy);
		}
		
	}
	
	/**
	 * update elementor data meta from content layout
	 */
	public static function updateElementorDataMeta($postID, $arrContent){
		
		$layoutData = wp_json_encode($arrContent);
		$layoutDataValueInsert = wp_slash($layoutData);
		
		update_post_meta($postID, "_elementor_data", $layoutDataValueInsert);
		
	}

	/**
	 * add svg type
	 */
	public static function uploadMimesAddSvgType($mimes) {
	  $mimes['svg'] = 'image/svg+xml';
	  return $mimes;
	}
	
	
	/**
	 * allow import svg
	 */
	public static function allowImportSVG(){
		
		if(self::$isSvgUploadAllowed == true)
			return(false);
		
		add_filter('upload_mimes', array('SheetsPilotUniteFunctionsWP',"uploadMimesAddSvgType"));
		
		self::$isSvgUploadAllowed = true;
	}
	
	/**
	 * check if the user administrator
	 */
	public static function isCurrentUserAdministrator(){
		
		$isAdmin = current_user_can( 'administrator' );
		
		return($isAdmin);
	}
	
	
	private static function _______ATTACHMENTS________(){}
	
	
	/**
	 * get wordpress images from content
	 */
	public static function getWPImagesFromContent($text){
		
		//protection
		
		$pos = strpos($text,"<img");
		
		if($pos === false)
			return(array());
		
		$dom = new DOMDocument();
		
		$text = @mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8');
		
		//methods to load HTML
		$isValidHtml = @$dom->loadHTML($text);
			
		if($isValidHtml == false)
			return(array());

    	$documentElement = $dom->documentElement;
		
    	$imageItemsList = $documentElement->getElementsByTagName('img');
    	
    	if(empty($imageItemsList))
    		return(array());
    	
    	$numImages = $imageItemsList->length;

    	if($numImages == 0)
    		return(array());
    	
    	$arrImagesData = array();
    	
    	foreach ($imageItemsList as $element) {
    		
    	  //check the class
    	  $attributes = $element->attributes;
    	  
    	  if(empty($attributes))
    	  	continue;
    	  
    	  //prepare attributes array
    	  
    	  $arrAttributes = array();
    	  foreach($attributes as $attribute){
    	  	    	  	
    	  	$arrAttributes[$attribute->name] = $attribute->value;
    	  }
    	  
    	  $class = SheetsPilotFunctions::getVal($arrAttributes, "class");
    	  
    	  $src = SheetsPilotFunctions::getVal($arrAttributes, "src");
    	  
    	  if(empty($src))
    	  	continue;
    	  
    	  if(empty($class))
    	  	continue;
    	  	
    	  if(strpos($class, "wp-image-") === false)
    	  	continue;
		  
    	  $arrClasses = explode(" ", $class);
    	  
    	  $size = self::THUMB_MEDIUM_LARGE;
    	  $imageID = null;
    	  
    	  foreach($arrClasses as $className){
			
    	  	  $className = trim($className);
    	  	  
    	  	  if(empty($className))
    	  	  	continue;
    	  	  	
    	  	  if(strpos($className, "size-") === 0)
    	  	  	$size = str_replace("size-", "", $className);

    	  	  if(strpos($className, "wp-image-") === 0)
    	  	  	$imageID = str_replace("wp-image-", "", $className);
    	  }
    	  
    	  if(empty($imageID))
    	  	continue;
    	  
      	  $html = $dom->saveHtml($element);
      	  
      	  if(empty($html))
      	  	continue;
      	  	
      	  $arrImage = array();
      	  $arrImage["id"] = $imageID;
      	  $arrImage["size"] = $size;
      	  $arrImage["src"] = $src;
      	  $arrImage["html"] = $html;
      	  
      	  $arrImagesData[] = $arrImage;
    	}	
    	
    	
		return($arrImagesData);
	}
	
	
	/**
	 * Get an attachment ID given a URL.
	*
	* @param string $url
	*
	* @return int Attachment ID on success, 0 on failure
	*/
	public static function getAttachmentIDFromImageUrl( $url , $returnWithSize = false) {
		
		if(empty($url))
			return(null);
		
		$attachment_id = 0;
	
		$dir = wp_upload_dir();
		
		if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) { // Is URL in uploads directory?
			
			$file = basename( $url );
	
			$query_args = array(
					'post_type'   => 'attachment',
					'post_status' => 'inherit',
					'fields'      => 'ids',

					'meta_query'  => array(
							array(
									'value'   => $file,
									'compare' => 'LIKE',
									'key'     => '_wp_attachment_metadata',
							),
					)
			);
			
			$query = new WP_Query( $query_args );
	
			if ( $query->have_posts() ) {
	
				foreach ( $query->posts as $post_id ) {
						
					$meta = wp_get_attachment_metadata( $post_id );
					
					$original_file       = basename( $meta['file'] );
					$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );
					
					
					//------- return object with size ----------
					
					if($returnWithSize == true){
						
						if($original_file === $file){
							
							$output = array();
							$output["id"] = $post_id;
							$output["size"] = self::THUMB_FULL;
							
							return($output);
						}
						
						foreach($cropped_image_files as $size => $filename){
							
							if($filename == $file){
								
								$output = array();
								$output["id"] = $post_id;
								$output["size"] = $size;
								
								return($output);
							}
								
						}
						
					}
					
					if ( $original_file === $file || in_array( $file, $cropped_image_files ) ) {
						$attachment_id = $post_id;
						
						break;
					}
	
				}
	
			}
	
		}
	
		if($returnWithSize == true)
			return(null);
			
		
		return $attachment_id;
	}		
	
		/**
		 *
		 * get attachment image url
		 */
		public static function getUrlAttachmentImage($thumbID, $size = self::THUMB_FULL){
			
			$handle = "thumb_{$thumbID}_{$size}";
			
			if(isset(self::$arrUrlThumbCache[$handle]))	
				return(self::$arrUrlThumbCache[$handle]);
			
			$arrImage = wp_get_attachment_image_src($thumbID, $size);
			if(empty($arrImage))
				return(false);
			
			$url = SheetsPilotFunctions::getVal($arrImage, 0);
			
			self::$arrUrlThumbCache[$handle] = $url;
			
			return($url);
		}
		
		
		
		/**
		 * check if terms exists by id's
		 */
		public static  function isTermsExistByIDs($arrIDs){
			
			$args = array();
			$args["include"] = $arrIDs;
			
			$term_query = new WP_Term_Query();
			$arrTerms = $term_query->query( $args );
			
			if(count($arrTerms) == 0 || empty($arrTerms))
				return(false);
			
			return(true);
		}
		
		/**
		 * get post type name
		 */
		public static function getPostTypeName($type){
			
			
			$name = $type;
			
			$objType = get_post_type_object($type);
			
			if(empty($objType))
				return($name);

			$arrLabels = get_post_type_labels($objType);
			
			if(empty($arrLabels))
				return($name);
			
			$arrLabels = (array)$arrLabels;
				
			$typeName = SheetsPilotFunctions::getVal($arrLabels, "singular_name");

			if(!empty($typeName))
				return($typeName);
				
			return($name);
		}
		
		/**
		 * 
		 * get woo attributes
		 */
		public static function getAllWooAttributesAssoc(){
			
			if(!empty(self::$arrCacheWooAttributes))
				return(self::$arrCacheWooAttributes);
			
			$db = self::getDB();
			
			$arrWooAttributes = $db->fetch(SheetsPilotGlobals::$dbPrefix."woocommerce_attribute_taxonomies");
			
			//convert to assoc
			$arrResponse = array();
			foreach($arrWooAttributes as $item){
				
				$name = SheetsPilotFunctions::getVal($item, "attribute_name");
								
				$arrResponse["pa_".$name] = $item;
			}
			
			self::$arrCacheWooAttributes = $arrResponse;
			
			return($arrResponse);
		}
		
		
		/**
		 * get woocommerce attribute taxonomy
		 */
		public static function getWooAttributeTaxonomyData($name){
			
			$arrWooAttributes = self::getAllWooAttributesAssoc();
			
			$arrData = SheetsPilotFunctions::getVal($arrWooAttributes, $name);
						
			if(empty($arrData))
				return(null);
			
			unset($arrData["attribute_id"]);
			
			return($arrData);
		}
		
		/**
		 * insert the attribute
		 */
		public static function insertWooAttribute($slug, $name, $type){

			if(function_exists("wc_create_attribute") == false)
				return(false);
			
			$arrInsert = array();
			$arrInsert["name"] = $name;
			$arrInsert["slug"] = $slug;
			$arrInsert["type"] = $type;
			
			wc_create_attribute($arrInsert);
			
		}
		
		
}