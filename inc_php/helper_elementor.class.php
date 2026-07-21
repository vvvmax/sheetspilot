<?php
/**
 * Elementor integration helpers for SheetsPilot.
 *
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'SHEETSPILOT_INC' ) ) {
	die( 'restricted access' );
}

class SheetsPilotHelperElementor {

	/** @var array|null */
	private static $arr_global_typography;

	/**
	 * Elementor global typography presets from the data manager.
	 *
	 * @return array|false
	 */
	public static function getGlobalTypography() {
		if ( ! self::isInstalled() ) {
			return false;
		}

		if ( ! empty( self::$arr_global_typography ) ) {
			return self::$arr_global_typography;
		}

		$data_manager = self::getDataManager();
		if ( empty( $data_manager ) ) {
			return false;
		}

		$arr_typography = $data_manager->run( 'globals/typography' );
		if ( empty( $arr_typography ) ) {
			return false;
		}

		foreach ( $arr_typography as $typo_id => $typo ) {
			$value = SheetsPilotFunctions::getVal( $typo, 'value' );
			$arr_typography[ $typo_id ] = $value;
		}

		self::$arr_global_typography = $arr_typography;

		return self::$arr_global_typography;
	}

	/**
	 * Whether Elementor plugin is available.
	 *
	 * @return bool
	 */
	public static function isInstalled() {
		return defined( 'ELEMENTOR_VERSION' ) || class_exists( '\Elementor\Plugin' );
	}

	/**
	 * Elementor data manager (for globals API).
	 *
	 * @return object|null
	 */
	public static function getDataManager() {
		if ( ! self::isInstalled() || ! class_exists( '\Elementor\Plugin' ) ) {
			return null;
		}

		$plugin = \Elementor\Plugin::$instance;
		if ( isset( $plugin->data_manager_v2 ) ) {
			return $plugin->data_manager_v2;
		}

		return null;
	}

	/**
	 * Whether a post was built with Elementor.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function isPostBuiltWithElementor( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 || ! self::isInstalled() ) {
			return false;
		}

		$edit_mode = get_post_meta( $post_id, '_elementor_edit_mode', true );
		if ( $edit_mode === 'builder' ) {
			return true;
		}

		$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
		if ( is_string( $elementor_data ) ) {
			$elementor_data = trim( $elementor_data );
			return $elementor_data !== '' && $elementor_data !== '[]';
		}

		return false;
	}

	/**
	 * Elementor editor URL for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function getPostEditUrl( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return '';
		}

		if ( self::isInstalled() && class_exists( '\Elementor\Plugin' ) ) {
			$document = \Elementor\Plugin::$instance->documents->get( $post_id );
			if ( $document ) {
				return (string) $document->get_edit_url();
			}
		}

		return admin_url( 'post.php?post=' . $post_id . '&action=elementor' );
	}

	/**
	 * Clear Elementor CSS file cache and document object cache for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function clearPostCache( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return false;
		}

		self::removePostCacheCssFile( $post_id );
		self::clearPostObjectCache( $post_id );

		return true;
	}

	/**
	 * Remove Elementor generated CSS file and related post meta for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True when the CSS file existed and was removed.
	 */
	public static function removePostCacheCssFile( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return false;
		}

		delete_post_meta( $post_id, '_elementor_css' );

		$uploads_path = '';
		if ( class_exists( 'SheetsPilotGlobals' ) && ! empty( SheetsPilotGlobals::$pathUploads ) ) {
			$uploads_path = SheetsPilotGlobals::$pathUploads;
		} else {
			$upload_dir = wp_upload_dir();
			if ( ! empty( $upload_dir['basedir'] ) ) {
				$uploads_path = trailingslashit( $upload_dir['basedir'] );
			}
		}

		if ( $uploads_path === '' ) {
			return false;
		}

		$css_dir  = trailingslashit( $uploads_path ) . 'elementor/css/';
		$filepath = $css_dir . 'post-' . $post_id . '.css';

		if ( ! file_exists( $filepath ) ) {
			return false;
		}

		if ( function_exists( 'wp_delete_file' ) ) {
			wp_delete_file( $filepath );
		} else {
			@unlink( $filepath ); 
		}

		return ! file_exists( $filepath );
	}

	/**
	 * Clear Elementor document element cache meta for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function clearPostObjectCache( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 || ! self::isInstalled() ) {
			return false;
		}

		try {
			if ( ! class_exists( '\Elementor\Plugin' ) ) {
				return false;
			}

			$plugin = \Elementor\Plugin::$instance;
			if ( empty( $plugin ) || empty( $plugin->documents ) ) {
				return false;
			}

			if ( ! method_exists( $plugin->documents, 'get' ) ) {
				return false;
			}

			$document = $plugin->documents->get( $post_id, false );
			if ( empty( $document ) ) {
				return false;
			}

			if ( ! class_exists( '\Elementor\Core\Base\Document' ) ) {
				return false;
			}

			if ( ! method_exists( $document, 'delete_meta' ) ) {
				return false;
			}

			$document->delete_meta( \Elementor\Core\Base\Document::CACHE_META_KEY );
		} catch ( \Exception $e ) { 
			return false;
		} catch ( \Throwable $e ) { 
			return false;
		}

		return true;
	}

	/**
	 * Save Elementor page layout data and required builder meta.
	 *
	 * @param int    $post_id               Post ID.
	 * @param array  $arr_content           Elementor elements tree.
	 * @param string $post_content_fallback Optional plain-text fallback for post_content.
	 * @return bool
	 */
	public static function savePostData( $post_id, $arr_content, $post_content_fallback = null ) {
		
		$post_id = absint( $post_id );
		if ( $post_id <= 0 || ! self::isInstalled() || ! is_array( $arr_content ) || empty( $arr_content ) ) {
			return false;
		}

		SheetsPilotUniteFunctionsWP::updateElementorDataMeta( $post_id, $arr_content );

		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );

		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
		}
		$post = get_post( $post_id );
		if ( $post ) {
			$template_type = 'wp-' . $post->post_type;
			update_post_meta( $post_id, '_elementor_template_type', $template_type );
		}

		if ( $post_content_fallback === null && class_exists( 'SheetsPilot_ContentBlocks' ) ) {
			$post_content_fallback = SheetsPilot_ContentBlocks::elementor_data_to_display_text( $arr_content );
		}

		self::syncPostContentFallback( $post_id, (string) $post_content_fallback );
		self::clearPostCache( $post_id );

		return true;
	}

	/**
	 * Replace post_content with plain-text fallback (clears legacy block/JSON content).
	 *
	 * @param int    $post_id Post ID.
	 * @param string $text    Plain fallback text; empty string clears post_content.
	 */
	public static function syncPostContentFallback( $post_id, $text ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return;
		}

		$plain = self::plainTextForPostContent( $text );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => wp_unslash( $plain ),
			)
		);
	}

	/**
	 * Normalize preview/HTML text into plain post_content fallback.
	 *
	 * @param string $text Raw display or HTML text.
	 * @return string
	 */
	public static function plainTextForPostContent( $text ) {
		$text = is_string( $text ) ? $text : '';
		if ( $text === '' ) {
			return '';
		}

		if ( strpos( $text, '&' ) !== false ) {
			$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		if ( class_exists( 'SheetsPilot_ContentBlocks' ) ) {
			$text = SheetsPilot_ContentBlocks::decode_unicode_escapes_in_text( $text );
		}

		$text = preg_replace( '/\s*<br\s*\/?>\s*/i', "\n", $text );
		$text = wp_strip_all_tags( $text );
		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
		$text = preg_replace( '/[ \t]+/', ' ', $text );
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );

		return trim( $text );
	}

	/**
	 * Plain-text preview for the post_content column in the editor grid.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function getPostContentDisplayForEditor( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return '';
		}

		if ( self::isPostBuiltWithElementor( $post_id ) && class_exists( 'SheetsPilot_ContentBlocks' ) ) {
			$elementor_raw = get_post_meta( $post_id, '_elementor_data', true );
			$display       = SheetsPilot_ContentBlocks::elementor_data_to_display_text( $elementor_raw );
			if ( $display !== '' ) {
				return $display;
			}
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		$content = (string) $post->post_content;
		if ( class_exists( 'SheetsPilot_ContentBlocks' ) && SheetsPilot_ContentBlocks::is_elementor_insert_value( $content ) ) {
			return '';
		}

		return $content;
	}

	/**
	 * Bottom-manage icon HTML for the post_content column.
	 *
	 * @param bool $is_elementor Use Elementor icon and tooltip when true.
	 * @return string
	 */
	public static function getPostContentEditManageIconHtml( $is_elementor = false ) {
		if ( $is_elementor && self::isInstalled() ) {
			$tooltip = esc_attr( SheetsPilotGlobals::$editPostInElementorTooltipText );
			$svg     = '<svg class="sheetspilot-elementor-edit-icon" fill="#000000" width="24" height="24" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M 5 5 L 5 27 L 27 27 L 27 5 L 5 5 z M 7 7 L 25 7 L 25 25 L 7 25 L 7 7 z M 11 11 L 11 21 L 13 21 L 13 11 L 11 11 z M 15 11 L 15 13 L 21 13 L 21 11 L 15 11 z M 15 15 L 15 17 L 21 17 L 21 15 L 15 15 z M 15 19 L 15 21 L 21 21 L 21 19 L 15 19 z"/></svg>';
			return '<span class="post_manage_icon edit_in_new_window edit_in_elementor has-tooltip" data-title="' . $tooltip . '">' . $svg . '</span>';
		}

		$tooltip = esc_attr( SheetsPilotGlobals::$editPostInNewWindowTooltipText );
		return '<span class="post_manage_icon edit_in_new_window has-tooltip" data-title="' . $tooltip . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"></path><path d="m21 3-9 9"></path><path d="M15 3h6v6"></path></svg></span>';
	}
}
