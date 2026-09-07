<?php
/**
 * Convert a general content-block JSON tree to Gutenberg block markup.
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

class SheetsPilot_ContentBlocks {

	const BLOCK_PARAGRAPH     = 'paragraph';
	const BLOCK_HEADING       = 'heading';
	const BLOCK_LIST          = 'list';
	const BLOCK_SEPARATOR     = 'separator';
	const BLOCK_QUOTE         = 'quote';
	const BLOCK_TABLE         = 'table';
	const BLOCK_CODE          = 'code';
	const BLOCK_PREFORMATTED  = 'preformatted';
	const BLOCK_DETAILS       = 'details';
	const BLOCK_MORE          = 'more';
	const BLOCK_ACCORDION     = 'accordion';
	const BLOCK_BUTTON        = 'button';

	/**
	 * Allowed block type names in the AI JSON tree.
	 *
	 * @var string[]
	 */
	public static $allowed_types = array(
		self::BLOCK_PARAGRAPH,
		self::BLOCK_HEADING,
		self::BLOCK_LIST,
		self::BLOCK_SEPARATOR,
		self::BLOCK_QUOTE,
		self::BLOCK_TABLE,
		self::BLOCK_CODE,
		self::BLOCK_PREFORMATTED,
		self::BLOCK_DETAILS,
		self::BLOCK_MORE,
		self::BLOCK_ACCORDION,
		self::BLOCK_BUTTON,
	);

	/**
	 * Ensure WordPress block serialization functions are loaded.
	 */
	private static function ensure_block_functions() {
		if ( ! function_exists( 'serialize_blocks' ) ) {
			require_once ABSPATH . WPINC . '/blocks.php';
		}
	}

	/**
	 * Whether the value is a content-blocks JSON payload.
	 *
	 * @param mixed $data Response data value.
	 * @return bool
	 */
	public static function is_blocks_payload( $data ) {
		if ( is_string( $data ) ) {
			$decoded = self::decode_prompt_json_payload( $data );
			if ( is_array( $decoded ) ) {
				$data = $decoded;
			} else {
				return false;
			}
		}

		if ( is_object( $data ) ) {
			$data = (array) $data;
		}

		if ( ! is_array( $data ) ) {
			return false;
		}

		if ( isset( $data['blocks'] ) && is_array( $data['blocks'] ) ) {
			return self::is_blocks_list( $data['blocks'] );
		}

		return self::is_blocks_list( $data );
	}

	/**
	 * @param mixed $list Candidate blocks list.
	 * @return bool
	 */
	private static function is_blocks_list( $list ) {
		if ( ! is_array( $list ) || empty( $list ) ) {
			return false;
		}

		foreach ( $list as $block ) {
			if ( ! is_array( $block ) ) {
				return false;
			}
			$type = isset( $block['type'] ) ? sanitize_key( (string) $block['type'] ) : '';
			if ( $type === '' || ! in_array( $type, self::$allowed_types, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Extract the blocks array from a payload.
	 *
	 * @param mixed $data Payload.
	 * @return array
	 */
	public static function normalize_blocks_payload( $data ) {
		if ( is_string( $data ) ) {
			$decoded = json_decode( $data, true );
			if ( is_array( $decoded ) ) {
				$data = $decoded;
			}
		}

		if ( is_object( $data ) ) {
			$data = (array) $data;
		}

		if ( ! is_array( $data ) ) {
			return array();
		}

		if ( isset( $data['blocks'] ) && is_array( $data['blocks'] ) ) {
			return $data['blocks'];
		}

		if ( self::is_blocks_list( $data ) ) {
			return $data;
		}

		return array();
	}

	/**
	 * Resolve a content-blocks payload from a prompt response envelope.
	 *
	 * Handles JSON strings, {"type":"data","data":{...}} wrappers, and legacy nested "data" keys.
	 *
	 * @param mixed $data Prompt response data.
	 * @return array|null Blocks payload suitable for process_post_content_response().
	 */
	public static function resolve_blocks_payload_from_prompt_data( $data ) {
		if ( is_string( $data ) ) {
			$decoded = self::decode_prompt_json_payload( $data );
			if ( ! is_array( $decoded ) ) {
				return null;
			}
			$data = $decoded;
		}

		if ( is_object( $data ) ) {
			$data = (array) $data;
		}

		if ( ! is_array( $data ) ) {
			return null;
		}

		if (
			isset( $data['type'], $data['data'] )
			&& (string) $data['type'] === 'data'
		) {
			$envelope = $data;
			$inner    = $envelope['data'];
			if ( is_object( $inner ) ) {
				$inner = (array) $inner;
			}
			if ( is_string( $inner ) ) {
				$inner_decoded = self::decode_prompt_json_payload( $inner );
				if ( is_array( $inner_decoded ) ) {
					$inner = $inner_decoded;
				}
			}
			if ( is_array( $inner ) ) {
				if ( ! empty( $envelope['display_text'] ) && empty( $inner['display_text'] ) ) {
					$inner['display_text'] = $envelope['display_text'];
				}
				if ( ! empty( $envelope['instruction_summary'] ) && empty( $inner['instruction_summary'] ) ) {
					$inner['instruction_summary'] = $envelope['instruction_summary'];
				}
				$data = $inner;
			}
		}

		if ( self::is_blocks_payload( $data ) ) {
			return $data;
		}

		if ( isset( $data['data'] ) ) {
			$nested = $data['data'];
			if ( is_string( $nested ) ) {
				$nested = self::decode_prompt_json_payload( $nested );
			} elseif ( is_object( $nested ) ) {
				$nested = (array) $nested;
			}
			if ( is_array( $nested ) && self::is_blocks_payload( $nested ) ) {
				return $nested;
			}
		}

		return null;
	}

	/**
	 * Decode a prompt JSON string with common AI/WordPress encoding fixes.
	 *
	 * @param string $value Raw JSON or JSON embedded in text/markdown.
	 * @return array|null
	 */
	public static function decode_prompt_json_payload( $value ) {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = preg_replace( '/^\xEF\xBB\xBF/', '', trim( $value ) );
		if ( $value === '' ) {
			return null;
		}

		if ( preg_match( '/```(?:json)?\s*([\s\S]*?)```/i', $value, $fence_match ) ) {
			$value = trim( $fence_match[1] );
		}

		if ( $value === '' || ( $value[0] !== '[' && $value[0] !== '{' ) ) {
			if ( preg_match( '/\{[\s\S]*\}/s', $value, $obj_match ) ) {
				$value = $obj_match[0];
			} elseif ( preg_match( '/\[[\s\S]*\]/s', $value, $arr_match ) ) {
				$value = $arr_match[0];
			} else {
				return null;
			}
		}

		$candidates = array(
			$value,
			function_exists( 'wp_unslash' ) ? wp_unslash( $value ) : stripslashes( $value ),
			stripslashes( $value ),
			html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			html_entity_decode(
				function_exists( 'wp_unslash' ) ? wp_unslash( $value ) : stripslashes( $value ),
				ENT_QUOTES | ENT_HTML5,
				'UTF-8'
			),
			self::repair_json_string( $value ),
			self::repair_json_string( function_exists( 'wp_unslash' ) ? wp_unslash( $value ) : stripslashes( $value ) ),
		);
		$candidates = array_values( array_unique( array_filter( $candidates, 'is_string' ) ) );

		foreach ( $candidates as $candidate ) {
			$decoded = self::decode_json_array( $candidate );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return null;
	}

	/**
	 * Convert a content-blocks payload to Gutenberg post_content HTML.
	 *
	 * @param mixed $data Blocks payload.
	 * @return string
	 */
	public static function to_gutenberg( $data ) {
		self::ensure_block_functions();

		$blocks = self::normalize_blocks_payload( $data );
		if ( empty( $blocks ) ) {
			SheetsPilotFunctions::throwError( __( 'Invalid content blocks response from AI.', 'sheetspilot' ) );
		}

		$wp_blocks = array();
		foreach ( $blocks as $block_def ) {
			$wp_block = self::convert_block_definition( $block_def );
			if ( $wp_block !== null ) {
				$wp_blocks[] = $wp_block;
			}
		}

		if ( empty( $wp_blocks ) ) {
			SheetsPilotFunctions::throwError( __( 'No valid content blocks in AI response.', 'sheetspilot' ) );
		}

		$html = serialize_blocks( $wp_blocks );
		$html = trim( (string) $html );

		if ( $html === '' ) {
			SheetsPilotFunctions::throwError( __( 'Failed to produce Gutenberg content.', 'sheetspilot' ) );
		}

		$parsed = parse_blocks( $html );
		$has_named_block = false;
		foreach ( $parsed as $parsed_block ) {
			if ( ! empty( $parsed_block['blockName'] ) ) {
				$has_named_block = true;
				break;
			}
		}

		if ( ! $has_named_block ) {
			SheetsPilotFunctions::throwError( __( 'Generated content is not valid Gutenberg block markup.', 'sheetspilot' ) );
		}

		return $html;
	}

	/**
	 * Build plain-text from a blocks payload.
	 *
	 * @param mixed $data      Blocks payload.
	 * @param int   $max_lines Max lines to keep. 0 or less = all blocks (no ellipsis).
	 * @return string
	 */
	public static function to_display_text( $data, $max_lines = 8 ) {
		$blocks = self::normalize_blocks_payload( $data );
		if ( empty( $blocks ) ) {
			return '';
		}

		$max_lines = (int) $max_lines;
		$unlimited = $max_lines <= 0;
		$lines     = array();
		foreach ( $blocks as $block_def ) {
			$line = self::block_to_display_line( $block_def, $unlimited );
			if ( $line !== '' ) {
				$lines[] = $line;
			}
			if ( ! $unlimited && count( $lines ) >= $max_lines ) {
				break;
			}
		}

		$text = implode( "\n", $lines );
		if ( ! $unlimited && count( $blocks ) > $max_lines ) {
			$text .= "\n…";
		}

		return trim( $text );
	}

	/**
	 * Full article text from all blocks (dialog / apply result). Never uses AI display_text.
	 *
	 * @param mixed $data Blocks payload.
	 * @return string
	 */
	public static function to_full_display_text( $data ) {
		return self::to_display_text( $data, 0 );
	}

	/**
	 * Convert a content-blocks payload to simple HTML (Elementor post_content fallback).
	 *
	 * @param mixed $data Blocks payload.
	 * @return string
	 */
	public static function to_simple_html( $data ) {
		$blocks = self::normalize_blocks_payload( $data );
		if ( empty( $blocks ) ) {
			return '';
		}

		$parts = array();
		foreach ( $blocks as $block_def ) {
			$html = self::block_to_simple_html( $block_def );
			if ( $html !== '' ) {
				$parts[] = $html;
			}
		}

		return trim( implode( "\n", $parts ) );
	}

	/**
	 * @param array $block_def Block definition.
	 * @return string
	 */
	private static function block_to_simple_html( $block_def ) {
		if ( ! is_array( $block_def ) ) {
			return '';
		}

		$type = isset( $block_def['type'] ) ? sanitize_key( (string) $block_def['type'] ) : '';
		if ( ! in_array( $type, self::$allowed_types, true ) ) {
			return '';
		}

		switch ( $type ) {
			case self::BLOCK_PARAGRAPH:
				return '<p>' . self::escape_text( self::get_block_text( $block_def ) ) . '</p>';

			case self::BLOCK_HEADING:
				$level = isset( $block_def['level'] ) ? (int) $block_def['level'] : 2;
				$level = max( 1, min( 6, $level ) );
				$tag   = 'h' . $level;
				return '<' . $tag . '>' . self::escape_text( self::get_block_text( $block_def ) ) . '</' . $tag . '>';

			case self::BLOCK_LIST:
				$ordered = ! empty( $block_def['ordered'] );
				$items   = self::get_block_list_items( $block_def );
				$tag     = $ordered ? 'ol' : 'ul';
				$html    = '<' . $tag . '>';
				foreach ( $items as $item ) {
					$html .= '<li>' . self::escape_text( $item ) . '</li>';
				}
				return $html . '</' . $tag . '>';

			case self::BLOCK_SEPARATOR:
				return '<hr />';

			case self::BLOCK_QUOTE:
				$text     = self::escape_text( self::get_block_text( $block_def ) );
				$citation = isset( $block_def['citation'] ) ? self::sanitize_plain_text( (string) $block_def['citation'] ) : '';
				$html     = '<blockquote><p>' . $text . '</p>';
				if ( $citation !== '' ) {
					$html .= '<cite>' . self::escape_text( $citation ) . '</cite>';
				}
				return $html . '</blockquote>';

			case self::BLOCK_TABLE:
				return self::build_table_html( $block_def );

			case self::BLOCK_CODE:
				$safe = esc_html( self::get_block_text( $block_def ) );
				return '<pre><code>' . $safe . '</code></pre>';

			case self::BLOCK_PREFORMATTED:
				$safe = esc_html( self::get_block_text( $block_def ) );
				return '<pre>' . $safe . '</pre>';

			case self::BLOCK_DETAILS:
				$summary = isset( $block_def['summary'] ) ? self::sanitize_plain_text( (string) $block_def['summary'] ) : '';
				$nested  = isset( $block_def['blocks'] ) && is_array( $block_def['blocks'] ) ? $block_def['blocks'] : array();
				$inner   = array();
				foreach ( $nested as $nested_def ) {
					$nested_html = self::block_to_simple_html( $nested_def );
					if ( $nested_html !== '' ) {
						$inner[] = $nested_html;
					}
				}
				return '<details><summary>' . self::escape_text( $summary ) . '</summary>' . implode( '', $inner ) . '</details>';

			case self::BLOCK_MORE:
				return '<!--more-->';

			case self::BLOCK_ACCORDION:
				$items = self::get_block_accordion_items( $block_def );
				$html  = '';
				foreach ( $items as $item ) {
					$inner = array();
					foreach ( $item['blocks'] as $nested_def ) {
						$nested_html = self::block_to_simple_html( $nested_def );
						if ( $nested_html !== '' ) {
							$inner[] = $nested_html;
						}
					}
					$html .= '<details><summary>' . self::escape_text( $item['title'] ) . '</summary>' . implode( '', $inner ) . '</details>';
				}
				return $html;

			case self::BLOCK_BUTTON:
				$text = self::escape_text( self::get_block_text( $block_def ) );
				$url  = self::get_block_button_url( $block_def );
				$attrs = ' href="' . esc_url( $url ) . '"';
				if ( self::get_block_button_open_in_new_tab( $block_def ) ) {
					$attrs .= ' target="_blank" rel="noreferrer noopener"';
				}
				return '<p><a class="wp-block-button__link"' . $attrs . '>' . $text . '</a></p>';

			default:
				return '';
		}
	}

	/**
	 * Process post_content AI response: convert blocks tree to Gutenberg or Elementor content.
	 *
	 * @param mixed  $insert_value Value from response "data" field.
	 * @param string $display_text Optional display_text from response.
	 * @param bool   $is_elementor When true, produce Elementor layout JSON for insert.
	 * @param int    $post_id      Post ID (used to match existing Elementor layout type).
	 * @return array{insert:string,show:string,is_elementor?:bool}|null Null when not a blocks payload.
	 */
	public static function process_post_content_response( $insert_value, $display_text = '', $is_elementor = false, $post_id = 0 ) {
		if ( ! self::is_blocks_payload( $insert_value ) ) {
			return null;
		}

		$show = self::to_full_display_text( $insert_value );
		if ( $show === '' && is_string( $display_text ) ) {
			$show = trim( $display_text );
		}

		if ( $is_elementor && class_exists( 'SheetsPilotHelperElementor' ) && SheetsPilotHelperElementor::isInstalled() ) {
			$elementor = self::to_elementor( $insert_value, $post_id );
			$insert    = wp_json_encode( $elementor );
			if ( $show === '' ) {
				$show = self::to_full_display_text( $insert_value );
			}

			return array(
				'insert'       => $insert,
				'show'         => $show,
				'is_elementor' => true,
			);
		}

		$gutenberg = self::to_gutenberg( $insert_value );
		if ( $show === '' ) {
			$show = class_exists( 'SheetsPilot_Prompts' )
				? SheetsPilot_Prompts::get_plain_text_for_prompt_display( $gutenberg )
				: self::to_full_display_text( $insert_value );
		}

		return array(
			'insert' => $gutenberg,
			'show'   => $show,
		);
	}

	/**
	 * Whether the value is Elementor layout JSON or an elements tree.
	 *
	 * @param mixed $value Candidate value.
	 * @return bool
	 */
	public static function is_elementor_insert_value( $value ) {
		return self::normalize_elementor_layout( $value ) !== null;
	}

	/**
	 * Whether an array is an Elementor elements tree.
	 *
	 * @param mixed $data Candidate tree.
	 * @return bool
	 */
	public static function is_elementor_layout_tree( $data ) {
		if ( ! is_array( $data ) || empty( $data ) ) {
			return false;
		}

		foreach ( $data as $element ) {
			if ( is_array( $element ) && ! empty( $element['elType'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize Elementor layout from JSON string or elements array.
	 *
	 * @param mixed $value Raw value.
	 * @return array|null
	 */
	public static function normalize_elementor_layout( $value ) {
		if ( is_array( $value ) ) {
			return self::coalesce_elementor_layout_tree( $value );
		}

		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = preg_replace( '/^\xEF\xBB\xBF/', '', trim( $value ) );
		if ( $value === '' || ( $value[0] !== '[' && $value[0] !== '{' ) ) {
			return null;
		}

		$candidates = array(
			$value,
			wp_unslash( $value ),
			stripslashes( $value ),
			html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			html_entity_decode( wp_unslash( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			self::repair_json_string( $value ),
			self::repair_json_string( wp_unslash( $value ) ),
		);
		$candidates = array_values( array_unique( array_filter( $candidates, 'is_string' ) ) );

		foreach ( $candidates as $candidate ) {
			$decoded = self::decode_json_array( $candidate );
			$tree    = self::coalesce_elementor_layout_tree( $decoded );
			if ( $tree !== null ) {
				return $tree;
			}
		}

		return null;
	}

	/**
	 * @param mixed $decoded Decoded JSON value.
	 * @return array|null
	 */
	private static function coalesce_elementor_layout_tree( $decoded ) {
		if ( ! is_array( $decoded ) || empty( $decoded ) ) {
			return null;
		}

		if ( self::is_elementor_layout_tree( $decoded ) ) {
			return $decoded;
		}

		if ( isset( $decoded['elType'] ) ) {
			$wrapped = array( $decoded );
			return self::is_elementor_layout_tree( $wrapped ) ? $wrapped : null;
		}

		return null;
	}

	/**
	 * @param string $json Raw JSON string.
	 * @return array|null
	 */
	private static function decode_json_array( $json ) {
		$flags = defined( 'JSON_INVALID_UTF8_SUBSTITUTE' ) ? JSON_INVALID_UTF8_SUBSTITUTE : 0;
		$decoded = json_decode( $json, true, 512, $flags );
		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * Apply lightweight fixes for common AI JSON syntax issues.
	 *
	 * @param string $json Raw JSON string.
	 * @return string
	 */
	public static function repair_json_string( $json ) {
		$json = str_replace(
			array( "\xc2\x94", "\xc2\x93", "\xc2\x91", "\xc2\x92" ),
			array( '"', '"', "'", "'" ),
			$json
		);

		$json = preg_replace( '/,\s*([}\]])/', '$1', $json );

		return self::repair_missing_array_closers( (string) $json );
	}

	/**
	 * Insert missing "]" when the model closes an object while an array is still open.
	 *
	 * Common AI mistake for list blocks:
	 *   {"type":"list","items":["a","b"},{"type":"heading"...
	 * should be:
	 *   {"type":"list","items":["a","b"]},{"type":"heading"...
	 *
	 * @param string $json Raw JSON string.
	 * @return string
	 */
	private static function repair_missing_array_closers( $json ) {
		if ( $json === '' ) {
			return $json;
		}

		$out    = '';
		$len    = strlen( $json );
		$in_str = false;
		$esc    = false;
		$stack  = array();

		for ( $i = 0; $i < $len; $i++ ) {
			$ch = $json[ $i ];

			if ( $in_str ) {
				$out .= $ch;
				if ( $esc ) {
					$esc = false;
					continue;
				}
				if ( $ch === '\\' ) {
					$esc = true;
					continue;
				}
				if ( $ch === '"' ) {
					$in_str = false;
				}
				continue;
			}

			if ( $ch === '"' ) {
				$in_str = true;
				$out   .= $ch;
				continue;
			}

			if ( $ch === '{' || $ch === '[' ) {
				$stack[] = $ch;
				$out    .= $ch;
				continue;
			}

			if ( $ch === '}' || $ch === ']' ) {
				// Model closed an object while an array was still open: insert "]" first.
				if ( $ch === '}' && ! empty( $stack ) && end( $stack ) === '[' ) {
					$out .= ']';
					array_pop( $stack );
				}
				if ( ! empty( $stack ) ) {
					array_pop( $stack );
				}
				$out .= $ch;
				continue;
			}

			$out .= $ch;
		}

		while ( ! empty( $stack ) ) {
			$open = array_pop( $stack );
			$out .= ( $open === '[' ) ? ']' : '}';
		}

		return $out;
	}

	/**
	 * Build a minimal Elementor layout with one text-editor widget (fallback when JSON layout is invalid).
	 *
	 * @param string $text    Plain text or basic HTML.
	 * @param int    $post_id Post ID (matches section vs container wrapper).
	 * @return array|null
	 */
	public static function fallback_elementor_layout_from_text( $text, $post_id = 0 ) {
		$text = is_string( $text ) ? trim( $text ) : '';
		if ( $text === '' ) {
			return null;
		}

		$html   = self::text_to_elementor_editor_html( $text );
		$widget = self::make_elementor_text_editor_widget( $html );
		if ( empty( $widget ) ) {
			return null;
		}

		return self::wrap_elementor_widgets( array( $widget ), $post_id );
	}

	/**
	 * @param string $text Plain text or basic HTML for a text-editor widget.
	 * @return string
	 */
	private static function text_to_elementor_editor_html( $text ) {
		$text = (string) $text;
		if ( $text === '' ) {
			return '<p></p>';
		}

		if ( strpos( $text, '&' ) !== false ) {
			$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		$text = self::decode_unicode_escapes_in_text( $text );

		if ( preg_match( '/<\s*(p|br|div|h[1-6]|ul|ol|blockquote|strong|em|a)\b/i', $text ) ) {
			$allowed = array(
				'p'          => array(),
				'br'         => array(),
				'div'        => array(),
				'h1'         => array(),
				'h2'         => array(),
				'h3'         => array(),
				'h4'         => array(),
				'h5'         => array(),
				'h6'         => array(),
				'strong'     => array(),
				'b'          => array(),
				'em'         => array(),
				'i'          => array(),
				'a'          => array(
					'href'   => array(),
					'target' => array(),
				),
				'ul'         => array(),
				'ol'         => array(),
				'li'         => array(),
				'blockquote' => array(),
				'cite'       => array(),
			);
			$text = wp_kses( $text, $allowed );
			if ( stripos( $text, '<p' ) === false && stripos( $text, '<h' ) === false ) {
				$text = wpautop( $text );
			}
			return $text;
		}

		return wpautop( esc_html( $text ) );
	}

	/**
	 * Build a plain-text preview from stored Elementor layout data.
	 *
	 * @param mixed $elementor_data Elementor elements tree or JSON string.
	 * @param int   $max_lines      Max lines to keep. 0 or less = all widgets.
	 * @return string
	 */
	public static function elementor_data_to_display_text( $elementor_data, $max_lines = 8 ) {
		if ( is_string( $elementor_data ) ) {
			$elementor_data = json_decode( $elementor_data, true );
		}

		if ( ! is_array( $elementor_data ) || empty( $elementor_data ) ) {
			return '';
		}

		$lines = array();
		self::collect_elementor_display_lines( $elementor_data, $lines, (int) $max_lines );
		$text  = implode( "\n", $lines );

		return trim( $text );
	}

	/**
	 * @param array $elements  Elementor elements.
	 * @param array $lines     Output lines.
	 * @param int   $max_lines Max lines (0 = unlimited).
	 */
	private static function collect_elementor_display_lines( $elements, &$lines, $max_lines = 8 ) {
		$unlimited = (int) $max_lines <= 0;

		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( isset( $element['elType'] ) && $element['elType'] === 'widget' ) {
				$line = self::elementor_widget_to_display_line( $element );
				if ( $line !== '' ) {
					$lines[] = $line;
				}
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				self::collect_elementor_display_lines( $element['elements'], $lines, $max_lines );
			}

			if ( ! $unlimited && count( $lines ) >= $max_lines ) {
				break;
			}
		}
	}

	/**
	 * @param array $widget Elementor widget element.
	 * @return string
	 */
	private static function elementor_widget_to_display_line( $widget ) {
		$widget_type = isset( $widget['widgetType'] ) ? (string) $widget['widgetType'] : '';
		$settings    = isset( $widget['settings'] ) && is_array( $widget['settings'] ) ? $widget['settings'] : array();

		switch ( $widget_type ) {
			case 'heading':
				return isset( $settings['title'] ) ? self::sanitize_plain_text( (string) $settings['title'] ) : '';

			case 'text-editor':
				if ( empty( $settings['editor'] ) ) {
					return '';
				}
				return self::sanitize_plain_text( (string) $settings['editor'] );

			case 'icon-list':
				if ( empty( $settings['icon_list'] ) || ! is_array( $settings['icon_list'] ) ) {
					return '';
				}
				$items = array();
				foreach ( $settings['icon_list'] as $item ) {
					if ( is_array( $item ) && ! empty( $item['text'] ) ) {
						$items[] = self::sanitize_plain_text( (string) $item['text'] );
					}
				}
				return implode( ', ', array_slice( $items, 0, 4 ) );

			case 'divider':
				return '---';

			case 'toggle':
			case 'accordion':
				if ( empty( $settings['tabs'] ) || ! is_array( $settings['tabs'] ) ) {
					return '';
				}
				$titles = array();
				foreach ( $settings['tabs'] as $tab ) {
					if ( is_array( $tab ) && ! empty( $tab['tab_title'] ) ) {
						$titles[] = self::sanitize_plain_text( (string) $tab['tab_title'] );
					}
				}
				return implode( ', ', array_slice( $titles, 0, 2 ) );

			case 'button':
				return isset( $settings['text'] ) ? self::sanitize_plain_text( (string) $settings['text'] ) : '';

			case 'html':
				if ( empty( $settings['html'] ) ) {
					return '';
				}
				return self::sanitize_plain_text( (string) $settings['html'] );

			default:
				return '';
		}
	}

	/**
	 * Convert a content-blocks payload to Elementor _elementor_data layout.
	 *
	 * @param mixed $data    Blocks payload.
	 * @param int   $post_id Optional post ID to match existing layout type.
	 * @return array
	 */
	public static function to_elementor( $data, $post_id = 0 ) {
		$blocks = self::normalize_blocks_payload( $data );
		if ( empty( $blocks ) ) {
			SheetsPilotFunctions::throwError( __( 'Invalid content blocks response from AI.', 'sheetspilot' ) );
		}

		$widgets = array();
		foreach ( $blocks as $block_def ) {
			$widget = self::convert_block_to_elementor_widget( $block_def );
			if ( $widget !== null ) {
				$widgets[] = $widget;
			}
		}

		if ( empty( $widgets ) ) {
			SheetsPilotFunctions::throwError( __( 'No valid content blocks in AI response.', 'sheetspilot' ) );
		}

		return self::wrap_elementor_widgets( $widgets, $post_id );
	}

	/**
	 * @param array $block_def Block definition.
	 * @param bool  $full      When true, include all list items, table rows, and nested blocks.
	 * @return string
	 */
	private static function block_to_display_line( $block_def, $full = false ) {
		if ( ! is_array( $block_def ) ) {
			return '';
		}

		$type = isset( $block_def['type'] ) ? sanitize_key( (string) $block_def['type'] ) : '';

		switch ( $type ) {
			case self::BLOCK_HEADING:
			case self::BLOCK_PARAGRAPH:
			case self::BLOCK_QUOTE:
			case self::BLOCK_CODE:
			case self::BLOCK_PREFORMATTED:
				return self::get_block_text( $block_def );

			case self::BLOCK_LIST:
				$items = self::get_block_list_items( $block_def );
				if ( $full ) {
					$prefixed = array();
					foreach ( $items as $item ) {
						$prefixed[] = '• ' . $item;
					}
					return implode( "\n", $prefixed );
				}
				return implode( ', ', array_slice( $items, 0, 4 ) );

			case self::BLOCK_SEPARATOR:
				return '---';

			case self::BLOCK_TABLE:
				$rows = self::get_block_table_rows( $block_def );
				if ( empty( $rows ) ) {
					return '';
				}
				if ( $full ) {
					$row_lines = array();
					foreach ( $rows as $row ) {
						$row_lines[] = implode( ' | ', $row );
					}
					return implode( "\n", $row_lines );
				}
				return implode( ' | ', $rows[0] );

			case self::BLOCK_DETAILS:
				$summary = isset( $block_def['summary'] ) ? self::sanitize_plain_text( (string) $block_def['summary'] ) : '';
				if ( $full && ! empty( $block_def['blocks'] ) && is_array( $block_def['blocks'] ) ) {
					$inner = self::to_full_display_text( array( 'blocks' => $block_def['blocks'] ) );
					return trim( $summary . "\n" . $inner );
				}
				return $summary !== '' ? $summary : '[Details]';

			case self::BLOCK_MORE:
				return '[Read more]';

			case self::BLOCK_ACCORDION:
				$items = self::get_block_accordion_items( $block_def );
				if ( $full ) {
					$parts = array();
					foreach ( $items as $item ) {
						if ( $item['title'] !== '' ) {
							$parts[] = $item['title'];
						}
						if ( ! empty( $item['blocks'] ) ) {
							$inner = self::to_full_display_text( array( 'blocks' => $item['blocks'] ) );
							if ( $inner !== '' ) {
								$parts[] = $inner;
							}
						}
					}
					return ! empty( $parts ) ? implode( "\n", $parts ) : '[Accordion]';
				}
				$titles = array();
				foreach ( $items as $item ) {
					if ( $item['title'] !== '' ) {
						$titles[] = $item['title'];
					}
				}
				return ! empty( $titles ) ? implode( ', ', array_slice( $titles, 0, 4 ) ) : '[Accordion]';

			case self::BLOCK_BUTTON:
				$text = self::get_block_text( $block_def );
				return $text !== '' ? $text : '[Button]';

			default:
				return '';
		}
	}

	/**
	 * @param array $block_def Block definition.
	 * @return array|null WP block array.
	 */
	private static function convert_block_definition( $block_def ) {
		if ( ! is_array( $block_def ) ) {
			return null;
		}

		$type = isset( $block_def['type'] ) ? sanitize_key( (string) $block_def['type'] ) : '';
		if ( ! in_array( $type, self::$allowed_types, true ) ) {
			return null;
		}

		switch ( $type ) {
			case self::BLOCK_PARAGRAPH:
				return self::make_paragraph_block( self::get_block_text( $block_def ) );

			case self::BLOCK_HEADING:
				$level = isset( $block_def['level'] ) ? (int) $block_def['level'] : 2;
				$level = max( 1, min( 6, $level ) );
				return self::make_heading_block( self::get_block_text( $block_def ), $level );

			case self::BLOCK_LIST:
				$ordered = ! empty( $block_def['ordered'] );
				$items   = self::get_block_list_items( $block_def );
				return self::make_list_block( $items, $ordered );

			case self::BLOCK_SEPARATOR:
				return self::make_separator_block();

			case self::BLOCK_QUOTE:
				$text      = self::get_block_text( $block_def );
				$citation  = isset( $block_def['citation'] ) ? self::sanitize_plain_text( (string) $block_def['citation'] ) : '';
				return self::make_quote_block( $text, $citation );

			case self::BLOCK_TABLE:
				$rows       = self::get_block_table_rows( $block_def );
				$has_header = ! empty( $block_def['has_header'] );
				return self::make_table_block( $rows, $has_header );

			case self::BLOCK_CODE:
				return self::make_code_block( self::get_block_text( $block_def ) );

			case self::BLOCK_PREFORMATTED:
				return self::make_preformatted_block( self::get_block_text( $block_def ) );

			case self::BLOCK_DETAILS:
				$summary = isset( $block_def['summary'] ) ? self::sanitize_plain_text( (string) $block_def['summary'] ) : '';
				$nested  = isset( $block_def['blocks'] ) && is_array( $block_def['blocks'] ) ? $block_def['blocks'] : array();
				return self::make_details_block( $summary, $nested );

			case self::BLOCK_MORE:
				return self::make_more_block();

			case self::BLOCK_ACCORDION:
				return self::make_accordion_block( self::get_block_accordion_items( $block_def ) );

			case self::BLOCK_BUTTON:
				return self::make_button_block(
					self::get_block_text( $block_def ),
					self::get_block_button_url( $block_def ),
					self::get_block_button_open_in_new_tab( $block_def )
				);

			default:
				return null;
		}
	}

	/**
	 * @param array $block_def Block definition.
	 * @return string
	 */
	private static function get_block_text( $block_def ) {
		$text = '';
		if ( isset( $block_def['text'] ) ) {
			$text = (string) $block_def['text'];
		} elseif ( isset( $block_def['content'] ) ) {
			$text = (string) $block_def['content'];
		}
		return self::sanitize_plain_text( $text );
	}

	/**
	 * @param array $block_def Block definition.
	 * @return string[]
	 */
	private static function get_block_list_items( $block_def ) {
		$items = array();
		if ( ! isset( $block_def['items'] ) || ! is_array( $block_def['items'] ) ) {
			return $items;
		}
		foreach ( $block_def['items'] as $item ) {
			$items[] = self::sanitize_plain_text( (string) $item );
		}
		return $items;
	}

	/**
	 * @param array $block_def Block definition.
	 * @return array
	 */
	private static function get_block_table_rows( $block_def ) {
		$rows = array();
		if ( ! isset( $block_def['rows'] ) || ! is_array( $block_def['rows'] ) ) {
			return $rows;
		}
		foreach ( $block_def['rows'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$cells = array();
			foreach ( $row as $cell ) {
				$cells[] = self::sanitize_plain_text( (string) $cell );
			}
			if ( ! empty( $cells ) ) {
				$rows[] = $cells;
			}
		}
		return $rows;
	}

	/**
	 * @param array $block_def Accordion block definition.
	 * @return array<int,array{title:string,blocks:array}>
	 */
	private static function get_block_accordion_items( $block_def ) {
		$raw   = isset( $block_def['items'] ) && is_array( $block_def['items'] ) ? $block_def['items'] : array();
		$items = array();

		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$title  = isset( $item['title'] ) ? self::sanitize_plain_text( (string) $item['title'] ) : '';
			$blocks = isset( $item['blocks'] ) && is_array( $item['blocks'] ) ? $item['blocks'] : array();

			if ( empty( $blocks ) && isset( $item['text'] ) ) {
				$blocks = array(
					array(
						'type' => self::BLOCK_PARAGRAPH,
						'text' => (string) $item['text'],
					),
				);
			}

			$items[] = array(
				'title'  => $title,
				'blocks' => $blocks,
			);
		}

		if ( empty( $items ) ) {
			$items[] = array(
				'title'  => '',
				'blocks' => array(),
			);
		}

		return $items;
	}

	/**
	 * @param array $block_def Button block definition.
	 * @return string
	 */
	private static function get_block_button_url( $block_def ) {
		$url = '';
		if ( isset( $block_def['url'] ) ) {
			$url = trim( (string) $block_def['url'] );
		} elseif ( isset( $block_def['href'] ) ) {
			$url = trim( (string) $block_def['href'] );
		}

		$url = esc_url_raw( $url );
		return $url !== '' ? $url : '#';
	}

	/**
	 * @param array $block_def Button block definition.
	 * @return bool
	 */
	private static function get_block_button_open_in_new_tab( $block_def ) {
		if ( ! empty( $block_def['open_in_new_tab'] ) ) {
			return true;
		}
		$target = isset( $block_def['linkTarget'] ) ? (string) $block_def['linkTarget'] : '';
		return $target === '_blank';
	}

	/**
	 * Flatten nested block defs to HTML for Elementor toggle/accordion panels.
	 *
	 * @param array $nested_blocks Nested block definitions.
	 * @return string
	 */
	private static function nested_blocks_to_html( $nested_blocks ) {
		$parts = array();
		if ( is_array( $nested_blocks ) ) {
			foreach ( $nested_blocks as $nested_def ) {
				$html = self::block_to_simple_html( $nested_def );
				if ( $html !== '' ) {
					$parts[] = $html;
				}
			}
		}
		return empty( $parts ) ? '<p></p>' : implode( '', $parts );
	}

	/**
	 * Decode literal \uXXXX or uXXXX sequences (common when JSON backslashes are lost).
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	public static function decode_unicode_escapes_in_text( $text ) {
		if ( ! is_string( $text ) || $text === '' ) {
			return $text;
		}

		$to_char = static function ( $hex ) {
			$code = hexdec( $hex );
			if ( $code <= 0 || $code > 0x10FFFF ) {
				return null;
			}
			if ( function_exists( 'mb_chr' ) ) {
				$char = mb_chr( $code, 'UTF-8' );
				return $char !== false ? $char : null;
			}
			return html_entity_decode( '&#x' . $hex . ';', ENT_QUOTES, 'UTF-8' );
		};

		if ( strpos( $text, '\\u' ) !== false ) {
			$text = preg_replace_callback(
				'/\\\\u([0-9a-fA-F]{4})/',
				static function ( $matches ) use ( $to_char ) {
					$char = $to_char( $matches[1] );
					return $char !== null ? $char : $matches[0];
				},
				$text
			);
		}

		if ( preg_match( '/u[0-9a-fA-F]{4}/', $text ) ) {
			$text = preg_replace_callback(
				'/u([0-9a-fA-F]{4})/',
				static function ( $matches ) use ( $to_char ) {
					$code = hexdec( $matches[1] );
					if ( $code < 0x80 ) {
						return $matches[0];
					}
					$char = $to_char( $matches[1] );
					return $char !== null ? $char : $matches[0];
				},
				$text
			);
		}

		return $text;
	}

	/**
	 * @param string $text Plain text.
	 * @return string
	 */
	private static function sanitize_plain_text( $text ) {
		$text = wp_strip_all_tags( (string) $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = self::decode_unicode_escapes_in_text( $text );
		return trim( $text );
	}

	/**
	 * @param string $text Escaped text for HTML output.
	 * @return string
	 */
	private static function escape_text( $text ) {
		return esc_html( self::sanitize_plain_text( $text ) );
	}

	/**
	 * @param string $text Paragraph text.
	 * @return array
	 */
	private static function make_paragraph_block( $text ) {
		$safe = self::escape_text( $text );
		$html = '<p>' . $safe . '</p>';
		return array(
			'blockName'    => 'core/paragraph',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/**
	 * @param string $text Heading text.
	 * @param int    $level Heading level 1-6.
	 * @return array
	 */
	private static function make_heading_block( $text, $level ) {
		$safe = self::escape_text( $text );
		$tag  = 'h' . $level;
		$html = '<' . $tag . ' class="wp-block-heading">' . $safe . '</' . $tag . '>';
		return array(
			'blockName'    => 'core/heading',
			'attrs'        => array( 'level' => $level ),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/**
	 * @param string[] $items List items.
	 * @param bool     $ordered Ordered list.
	 * @return array
	 */
	private static function make_list_block( $items, $ordered ) {
		if ( empty( $items ) ) {
			$items = array( '' );
		}

		$inner_blocks = array();
		foreach ( $items as $item ) {
			$inner_blocks[] = self::make_list_item_block( $item );
		}

		$tag            = $ordered ? 'ol' : 'ul';
		$inner_content  = array( '<' . $tag . ' class="wp-block-list">' );
		$inner_html     = '<' . $tag . ' class="wp-block-list">';
		foreach ( $inner_blocks as $inner_block ) {
			$inner_content[] = null;
			$inner_html     .= $inner_block['innerHTML'];
		}
		$inner_content[] = '</' . $tag . '>';
		$inner_html     .= '</' . $tag . '>';

		return array(
			'blockName'    => 'core/list',
			'attrs'        => array( 'ordered' => (bool) $ordered ),
			'innerBlocks'  => $inner_blocks,
			'innerHTML'    => $inner_html,
			'innerContent' => $inner_content,
		);
	}

	/**
	 * @param string $text List item text.
	 * @return array
	 */
	private static function make_list_item_block( $text ) {
		$safe = self::escape_text( $text );
		$html = '<li>' . $safe . '</li>';
		return array(
			'blockName'    => 'core/list-item',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/**
	 * @return array
	 */
	private static function make_separator_block() {
		$html = '<hr class="wp-block-separator has-alpha-channel-opacity"/>';
		return array(
			'blockName'    => 'core/separator',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/**
	 * @param string $text Quote text.
	 * @param string $citation Optional citation.
	 * @return array
	 */
	private static function make_quote_block( $text, $citation = '' ) {
		$safe       = self::escape_text( $text );
		$inner_html = '<blockquote class="wp-block-quote"><p>' . $safe . '</p>';
		if ( $citation !== '' ) {
			$inner_html .= '<cite>' . self::escape_text( $citation ) . '</cite>';
		}
		$inner_html .= '</blockquote>';

		return array(
			'blockName'    => 'core/quote',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => $inner_html,
			'innerContent' => array( $inner_html ),
		);
	}

	/**
	 * @param array $rows Table rows.
	 * @param bool  $has_header Whether first row is header.
	 * @return array
	 */
	private static function make_table_block( $rows, $has_header ) {
		if ( empty( $rows ) ) {
			$rows = array(
				array( '' ),
			);
		}

		$table_html = '<figure class="wp-block-table"><table><tbody>';
		if ( $has_header && ! empty( $rows ) ) {
			$header_row = array_shift( $rows );
			$table_html .= '<thead><tr>';
			foreach ( $header_row as $cell ) {
				$table_html .= '<th>' . self::escape_text( $cell ) . '</th>';
			}
			$table_html .= '</tr></thead><tbody>';
		}

		foreach ( $rows as $row ) {
			$table_html .= '<tr>';
			foreach ( $row as $cell ) {
				$table_html .= '<td>' . self::escape_text( $cell ) . '</td>';
			}
			$table_html .= '</tr>';
		}
		$table_html .= '</tbody></table></figure>';

		return array(
			'blockName'    => 'core/table',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => $table_html,
			'innerContent' => array( $table_html ),
		);
	}

	/**
	 * @param string $text Code text.
	 * @return array
	 */
	private static function make_code_block( $text ) {
		$safe = esc_html( $text );
		$html = '<pre class="wp-block-code"><code>' . $safe . '</code></pre>';
		return array(
			'blockName'    => 'core/code',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/**
	 * @param string $text Preformatted text.
	 * @return array
	 */
	private static function make_preformatted_block( $text ) {
		$safe = esc_html( $text );
		$html = '<pre class="wp-block-preformatted">' . $safe . '</pre>';
		return array(
			'blockName'    => 'core/preformatted',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/**
	 * @param string $summary Summary text.
	 * @param array  $nested_blocks Nested block definitions.
	 * @return array
	 */
	private static function make_details_block( $summary, $nested_blocks ) {
		$wp_inner_blocks = array();
		if ( empty( $nested_blocks ) ) {
			$wp_inner_blocks[] = self::make_paragraph_block( '' );
		} else {
			foreach ( $nested_blocks as $nested_def ) {
				$nested = self::convert_block_definition( $nested_def );
				if ( $nested !== null ) {
					$wp_inner_blocks[] = $nested;
				}
			}
			if ( empty( $wp_inner_blocks ) ) {
				$wp_inner_blocks[] = self::make_paragraph_block( '' );
			}
		}

		$safe_summary  = self::escape_text( $summary );
		$inner_content = array( '<details class="wp-block-details"><summary>' . $safe_summary . '</summary>' );
		foreach ( $wp_inner_blocks as $inner_block ) {
			unset( $inner_block );
			$inner_content[] = null;
		}
		$inner_content[] = '</details>';

		return array(
			'blockName'    => 'core/details',
			'attrs'        => array( 'summary' => self::sanitize_plain_text( $summary ) ),
			'innerBlocks'  => $wp_inner_blocks,
			'innerHTML'    => '',
			'innerContent' => $inner_content,
		);
	}

	/**
	 * @return array
	 */
	private static function make_more_block() {
		$html = '<!--more-->';
		return array(
			'blockName'    => 'core/more',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/**
	 * @param array<int,array{title:string,blocks:array}> $items Accordion items.
	 * @return array
	 */
	private static function make_accordion_block( $items ) {
		if ( self::has_core_accordion_block() ) {
			return self::make_core_accordion_block( $items );
		}

		$details_blocks = array();
		foreach ( $items as $item ) {
			$details_blocks[] = self::make_details_block( $item['title'], $item['blocks'] );
		}

		$inner_content = array( '<div class="wp-block-group">' );
		foreach ( $details_blocks as $details_block ) {
			unset( $details_block );
			$inner_content[] = null;
		}
		$inner_content[] = '</div>';

		return array(
			'blockName'    => 'core/group',
			'attrs'        => array(
				'layout' => array(
					'type' => 'constrained',
				),
			),
			'innerBlocks'  => $details_blocks,
			'innerHTML'    => '',
			'innerContent' => $inner_content,
		);
	}

	/**
	 * Whether core/accordion is registered (WordPress 6.9+).
	 *
	 * @return bool
	 */
	private static function has_core_accordion_block() {
		return class_exists( 'WP_Block_Type_Registry' )
			&& WP_Block_Type_Registry::get_instance()->is_registered( 'core/accordion' );
	}

	/**
	 * Build a native core/accordion block tree.
	 *
	 * @param array<int,array{title:string,blocks:array}> $items Accordion items.
	 * @return array
	 */
	private static function make_core_accordion_block( $items ) {
		$item_blocks = array();
		foreach ( $items as $item ) {
			$item_blocks[] = self::make_core_accordion_item_block( $item['title'], $item['blocks'] );
		}

		$inner_content = array( '<div class="wp-block-accordion" role="group">' );
		foreach ( $item_blocks as $item_block ) {
			unset( $item_block );
			$inner_content[] = null;
		}
		$inner_content[] = '</div>';

		return array(
			'blockName'    => 'core/accordion',
			'attrs'        => array(
				'autoclose'    => true,
				'headingLevel' => 3,
				'iconPosition' => 'right',
				'showIcon'     => true,
			),
			'innerBlocks'  => $item_blocks,
			'innerHTML'    => '',
			'innerContent' => $inner_content,
		);
	}

	/**
	 * @param string $title         Item title.
	 * @param array  $nested_blocks Nested content blocks.
	 * @return array
	 */
	private static function make_core_accordion_item_block( $title, $nested_blocks ) {
		$heading = self::make_core_accordion_heading_block( $title );
		$panel   = self::make_core_accordion_panel_block( $nested_blocks );

		return array(
			'blockName'    => 'core/accordion-item',
			'attrs'        => array(
				'openByDefault' => false,
			),
			'innerBlocks'  => array( $heading, $panel ),
			'innerHTML'    => '',
			'innerContent' => array(
				'<div class="wp-block-accordion-item">',
				null,
				null,
				'</div>',
			),
		);
	}

	/**
	 * @param string $title Heading title.
	 * @return array
	 */
	private static function make_core_accordion_heading_block( $title ) {
		$safe = self::escape_text( $title );
		$html = '<h3 class="wp-block-accordion-heading">'
			. '<button type="button" class="wp-block-accordion-heading__toggle">'
			. '<span class="wp-block-accordion-heading__toggle-title">' . $safe . '</span>'
			. '<span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span>'
			. '</button></h3>';

		return array(
			'blockName'    => 'core/accordion-heading',
			'attrs'        => array(
				'title'        => self::sanitize_plain_text( $title ),
				'level'        => 3,
				'iconPosition' => 'right',
				'showIcon'     => true,
			),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/**
	 * @param array $nested_blocks Nested content blocks.
	 * @return array
	 */
	private static function make_core_accordion_panel_block( $nested_blocks ) {
		$wp_inner_blocks = array();
		if ( empty( $nested_blocks ) ) {
			$wp_inner_blocks[] = self::make_paragraph_block( '' );
		} else {
			foreach ( $nested_blocks as $nested_def ) {
				$nested = self::convert_block_definition( $nested_def );
				if ( $nested !== null ) {
					$wp_inner_blocks[] = $nested;
				}
			}
			if ( empty( $wp_inner_blocks ) ) {
				$wp_inner_blocks[] = self::make_paragraph_block( '' );
			}
		}

		$inner_content = array( '<div class="wp-block-accordion-panel" role="region">' );
		foreach ( $wp_inner_blocks as $inner_block ) {
			unset( $inner_block );
			$inner_content[] = null;
		}
		$inner_content[] = '</div>';

		return array(
			'blockName'    => 'core/accordion-panel',
			'attrs'        => array(),
			'innerBlocks'  => $wp_inner_blocks,
			'innerHTML'    => '',
			'innerContent' => $inner_content,
		);
	}

	/**
	 * @param string $text     Button label.
	 * @param string $url      Button URL.
	 * @param bool   $open_new Open in new tab.
	 * @return array
	 */
	private static function make_button_block( $text, $url, $open_new = false ) {
		$safe_text = self::escape_text( $text );
		$safe_url  = esc_url( $url );
		$attrs     = ' href="' . $safe_url . '"';
		$block_attrs = array(
			'url'  => $url,
			'text' => self::sanitize_plain_text( $text ),
		);

		if ( $open_new ) {
			$attrs                  .= ' target="_blank" rel="noreferrer noopener"';
			$block_attrs['linkTarget'] = '_blank';
			$block_attrs['rel']        = 'noreferrer noopener';
		}

		$button_html = '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"' . $attrs . '>' . $safe_text . '</a></div>';
		$button      = array(
			'blockName'    => 'core/button',
			'attrs'        => $block_attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => $button_html,
			'innerContent' => array( $button_html ),
		);

		return array(
			'blockName'    => 'core/buttons',
			'attrs'        => array(),
			'innerBlocks'  => array( $button ),
			'innerHTML'    => '',
			'innerContent' => array(
				'<div class="wp-block-buttons">',
				null,
				'</div>',
			),
		);
	}

	/**
	 * @param array $widgets Elementor widgets.
	 * @param int   $post_id Post ID.
	 * @return array
	 */
	private static function wrap_elementor_widgets( $widgets, $post_id = 0 ) {
		$layout_type = self::get_elementor_layout_type( $post_id );

		if ( $layout_type === 'section' ) {
			return array(
				self::make_elementor_element(
					'section',
					array(),
					array(
						self::make_elementor_element(
							'column',
							array( '_column_size' => 100 ),
							$widgets
						),
					)
				),
			);
		}

		return array(
			self::make_elementor_element( 'container', array(), $widgets ),
		);
	}

	/**
	 * @param int $post_id Post ID.
	 * @return string container|section
	 */
	private static function get_elementor_layout_type( $post_id = 0 ) {
		$post_id = absint( $post_id );
		if ( $post_id > 0 ) {
			$existing = get_post_meta( $post_id, '_elementor_data', true );
			if ( is_string( $existing ) && $existing !== '' && $existing !== '[]' ) {
				$decoded = json_decode( $existing, true );
				if ( is_array( $decoded ) && ! empty( $decoded[0]['elType'] ) ) {
					return $decoded[0]['elType'] === 'section' ? 'section' : 'container';
				}
			}
		}

		if ( class_exists( 'SheetsPilotHelperElementor' ) && SheetsPilotHelperElementor::isInstalled() && class_exists( '\Elementor\Plugin' ) ) {
			$experiments = \Elementor\Plugin::$instance->experiments;
			if ( $experiments && method_exists( $experiments, 'is_feature_active' ) && $experiments->is_feature_active( 'container' ) ) {
				return 'container';
			}
		}

		return 'section';
	}

	/**
	 * @param string $el_type  section|column|container|widget.
	 * @param array  $settings Element settings.
	 * @param array  $elements Child elements.
	 * @param string $widget_type Widget type when elType is widget.
	 * @return array
	 */
	private static function make_elementor_element( $el_type, $settings = array(), $elements = array(), $widget_type = '' ) {
		$element = array(
			'id'       => self::generate_elementor_id(),
			'elType'   => $el_type,
			'settings' => $settings,
			'elements' => $elements,
		);

		if ( $el_type === 'widget' ) {
			$element['widgetType'] = $widget_type;
		} else {
			$element['isInner'] = false;
		}

		return $element;
	}

	/**
	 * @return string
	 */
	private static function generate_elementor_id() {
		return substr( md5( uniqid( 'sp', true ) ), 0, 7 );
	}

	/**
	 * @param array $block_def Block definition.
	 * @return array|null Elementor widget element.
	 */
	private static function convert_block_to_elementor_widget( $block_def ) {
		if ( ! is_array( $block_def ) ) {
			return null;
		}

		$type = isset( $block_def['type'] ) ? sanitize_key( (string) $block_def['type'] ) : '';
		if ( ! in_array( $type, self::$allowed_types, true ) ) {
			return null;
		}

		switch ( $type ) {
			case self::BLOCK_PARAGRAPH:
				return self::make_elementor_text_editor_widget( '<p>' . self::escape_text( self::get_block_text( $block_def ) ) . '</p>' );

			case self::BLOCK_HEADING:
				$level = isset( $block_def['level'] ) ? (int) $block_def['level'] : 2;
				$level = max( 1, min( 6, $level ) );
				return self::make_elementor_heading_widget( self::get_block_text( $block_def ), $level );

			case self::BLOCK_LIST:
				$ordered = ! empty( $block_def['ordered'] );
				$items   = self::get_block_list_items( $block_def );
				return self::make_elementor_list_widget( $items, $ordered );

			case self::BLOCK_SEPARATOR:
				return self::make_elementor_divider_widget();

			case self::BLOCK_QUOTE:
				$text     = self::escape_text( self::get_block_text( $block_def ) );
				$citation = isset( $block_def['citation'] ) ? self::sanitize_plain_text( (string) $block_def['citation'] ) : '';
				$html     = '<blockquote class="wp-block-quote"><p>' . $text . '</p>';
				if ( $citation !== '' ) {
					$html .= '<cite>' . self::escape_text( $citation ) . '</cite>';
				}
				$html .= '</blockquote>';
				return self::make_elementor_text_editor_widget( $html );

			case self::BLOCK_TABLE:
				return self::make_elementor_text_editor_widget( self::build_table_html( $block_def ) );

			case self::BLOCK_CODE:
				$safe = esc_html( self::get_block_text( $block_def ) );
				return self::make_elementor_html_widget( '<pre class="wp-block-code"><code>' . $safe . '</code></pre>' );

			case self::BLOCK_PREFORMATTED:
				$safe = esc_html( self::get_block_text( $block_def ) );
				return self::make_elementor_text_editor_widget( '<pre class="wp-block-preformatted">' . $safe . '</pre>' );

			case self::BLOCK_DETAILS:
				$summary = isset( $block_def['summary'] ) ? self::sanitize_plain_text( (string) $block_def['summary'] ) : '';
				$nested  = isset( $block_def['blocks'] ) && is_array( $block_def['blocks'] ) ? $block_def['blocks'] : array();
				return self::make_elementor_toggle_widget( $summary, $nested );

			case self::BLOCK_MORE:
				return null;

			case self::BLOCK_ACCORDION:
				return self::make_elementor_accordion_widget( self::get_block_accordion_items( $block_def ) );

			case self::BLOCK_BUTTON:
				return self::make_elementor_button_widget(
					self::get_block_text( $block_def ),
					self::get_block_button_url( $block_def ),
					self::get_block_button_open_in_new_tab( $block_def )
				);

			default:
				return null;
		}
	}

	/**
	 * @param string $html Editor HTML.
	 * @return array
	 */
	private static function make_elementor_text_editor_widget( $html ) {
		return self::make_elementor_element(
			'widget',
			array( 'editor' => $html ),
			array(),
			'text-editor'
		);
	}

	/**
	 * @param string $html Raw HTML for html widget.
	 * @return array
	 */
	private static function make_elementor_html_widget( $html ) {
		return self::make_elementor_element(
			'widget',
			array( 'html' => $html ),
			array(),
			'html'
		);
	}

	/**
	 * @param string $text  Heading text.
	 * @param int    $level Heading level 1-6.
	 * @return array
	 */
	private static function make_elementor_heading_widget( $text, $level ) {
		return self::make_elementor_element(
			'widget',
			array(
				'title'       => self::sanitize_plain_text( $text ),
				'header_size' => 'h' . $level,
			),
			array(),
			'heading'
		);
	}

	/**
	 * @param string[] $items   List items.
	 * @param bool     $ordered Ordered list.
	 * @return array
	 */
	private static function make_elementor_list_widget( $items, $ordered ) {
		if ( empty( $items ) ) {
			$items = array( '' );
		}

		if ( $ordered ) {
			$html = '<ol class="wp-block-list">';
			foreach ( $items as $item ) {
				$html .= '<li>' . self::escape_text( $item ) . '</li>';
			}
			$html .= '</ol>';
			return self::make_elementor_text_editor_widget( $html );
		}

		$icon_list = array();
		foreach ( $items as $item ) {
			$icon_list[] = array(
				'text'          => self::sanitize_plain_text( $item ),
				'selected_icon' => array(
					'value'   => 'fas fa-check',
					'library' => 'fa-solid',
				),
				'_id'           => self::generate_elementor_id(),
			);
		}

		return self::make_elementor_element(
			'widget',
			array( 'icon_list' => $icon_list ),
			array(),
			'icon-list'
		);
	}

	/**
	 * @return array
	 */
	private static function make_elementor_divider_widget() {
		return self::make_elementor_element(
			'widget',
			array(),
			array(),
			'divider'
		);
	}

	/**
	 * @param string $summary      Toggle title.
	 * @param array  $nested_blocks Nested block definitions.
	 * @return array
	 */
	private static function make_elementor_toggle_widget( $summary, $nested_blocks ) {
		return self::make_elementor_element(
			'widget',
			array(
				'tabs' => array(
					array(
						'tab_title'   => $summary !== '' ? $summary : __( 'Details', 'sheetspilot' ),
						'tab_content' => self::nested_blocks_to_html( $nested_blocks ),
						'_id'         => self::generate_elementor_id(),
					),
				),
			),
			array(),
			'toggle'
		);
	}

	/**
	 * @param array<int,array{title:string,blocks:array}> $items Accordion items.
	 * @return array
	 */
	private static function make_elementor_accordion_widget( $items ) {
		$tabs = array();
		foreach ( $items as $item ) {
			$tabs[] = array(
				'tab_title'   => $item['title'] !== '' ? $item['title'] : __( 'Accordion', 'sheetspilot' ),
				'tab_content' => self::nested_blocks_to_html( $item['blocks'] ),
				'_id'         => self::generate_elementor_id(),
			);
		}

		return self::make_elementor_element(
			'widget',
			array(
				'tabs' => $tabs,
			),
			array(),
			'accordion'
		);
	}

	/**
	 * @param string $text     Button label.
	 * @param string $url      Button URL.
	 * @param bool   $open_new Open in new tab.
	 * @return array
	 */
	private static function make_elementor_button_widget( $text, $url, $open_new = false ) {
		$link = array(
			'url'         => $url !== '' ? $url : '#',
			'is_external' => $open_new ? 'on' : '',
			'nofollow'    => '',
		);

		return self::make_elementor_element(
			'widget',
			array(
				'text' => $text !== '' ? $text : __( 'Click here', 'sheetspilot' ),
				'link' => $link,
			),
			array(),
			'button'
		);
	}

	/**
	 * @param array $block_def Table block definition.
	 * @return string
	 */
	private static function build_table_html( $block_def ) {
		$rows       = self::get_block_table_rows( $block_def );
		$has_header = ! empty( $block_def['has_header'] );
		if ( empty( $rows ) ) {
			$rows = array( array( '' ) );
		}

		$html = '<figure class="wp-block-table"><table><tbody>';
		if ( $has_header && ! empty( $rows ) ) {
			$header_row = array_shift( $rows );
			$html      .= '<thead><tr>';
			foreach ( $header_row as $cell ) {
				$html .= '<th>' . self::escape_text( $cell ) . '</th>';
			}
			$html .= '</tr></thead><tbody>';
		}

		foreach ( $rows as $row ) {
			$html .= '<tr>';
			foreach ( $row as $cell ) {
				$html .= '<td>' . self::escape_text( $cell ) . '</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody></table></figure>';

		return $html;
	}
}
