<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! defined( "SHEETSPILOT_INC" ) ) {
	die( "restricted access" );
}

/**
 * Collects optional debug strings during an AJAX request and stores the last 15 sessions in wp_options.
 */
class SheetsPilot_AjaxSessionLog {

	const OPTION_NAME = 'sheetspilot_ajax_session_log';
	const MAX_SESSIONS = 15;

	/** @var array<string,mixed>|null */
	private static $current_session = null;

	/** @var bool */
	private static $hooks_registered = false;

	/**
	 * Register AJAX hooks when logging is enabled.
	 */
	public static function registerHooks() {
		if ( self::$hooks_registered || ! self::isEnabled() ) {
			return;
		}

		self::$hooks_registered = true;
		$ajax_action = 'sheetspilot_ajax_actions';
		add_action( 'wp_ajax_' . $ajax_action, array( __CLASS__, 'onAjaxStart' ), 0 );
		add_action( 'wp_ajax_nopriv_' . $ajax_action, array( __CLASS__, 'onAjaxStart' ), 0 );
	}

	/**
	 * @return bool
	 */
	public static function isEnabled() {
		return SheetsPilotGlobals::$enableAjaxSessionLog === true;
	}

	/**
	 * Start collecting data for the current AJAX request.
	 */
	public static function onAjaxStart() {
		if ( ! self::isEnabled() ) {
			return;
		}

		$action_type = SheetsPilotFunctions::getPostGetVariable( 'action', '', SheetsPilotFunctions::SANITIZE_KEY );
		if ( $action_type !== 'sheetspilot_ajax_actions' ) {
			return;
		}

		$client_action = SheetsPilotFunctions::getPostGetVariable( 'client_action', '', SheetsPilotFunctions::SANITIZE_KEY );
		self::$current_session = array(
			'action' => sanitize_key( (string) $client_action ),
			'time'   => current_time( 'mysql' ),
			'data'   => array(),
		);

		register_shutdown_function( array( __CLASS__, 'commitSession' ) );
	}

	/**
	 * Append a string (or key/value pair) to the current AJAX session log.
	 *
	 * @param string       $key   Label or plain data string.
	 * @param string|mixed $value Optional value when $key is a label.
	 */
	public static function addData( $key, $value = null ) {
		if ( ! self::isEnabled() || self::$current_session === null ) {
			return;
		}

		if ( $value === null ) {
			self::$current_session['data'][] = array(
				'label' => '',
				'value' => (string) $key,
			);
			return;
		}

		self::$current_session['data'][] = array(
			'label' => sanitize_text_field( (string) $key ),
			'value' => $value,
		);
	}

	/**
	 * Format one session log entry for display in the admin table.
	 *
	 * @param mixed $entry Stored entry (structured array or legacy string).
	 * @return string
	 */
	public static function formatSessionDataItem( $entry ) {
		$label = '';
		$value = null;

		if ( is_array( $entry ) && array_key_exists( 'value', $entry ) ) {
			$label = isset( $entry['label'] ) ? (string) $entry['label'] : '';
			$value = $entry['value'];
		} elseif ( is_string( $entry ) ) {
			$parsed = self::parseLegacySessionDataItem( $entry );
			$label  = $parsed['label'];
			$value  = $parsed['value'];
		} else {
			return self::stringifyValue( $entry );
		}

		if ( $label === 'savePostContentValue' ) {
			return self::formatSavePostContentValueLog( $value );
		}

		if ( $label === 'applyPromptPostContent' ) {
			return self::formatApplyPromptPostContentLog( $value );
		}

		if ( $label === 'applyPromptStart' ) {
			return self::formatStructuredSessionLog( $label, $value, array(
				'column'              => 'Column',
				'post_id'             => 'Post ID',
				'post_type'           => 'Post type',
				'cell_type'           => 'Cell type',
				'context_menu_action' => 'Context menu action',
				'is_post_content'     => 'Post content cell',
				'is_image_column'     => 'Image column',
				'has_image_settings'  => 'Image settings sent',
				'prompt_len'          => 'Prompt length',
				'prompt_preview'      => 'Prompt preview',
				'value_len'           => 'Cell value length',
				'value_preview'       => 'Cell value preview',
			) );
		}

		if ( $label === 'applyPromptPipeline' ) {
			return self::formatStructuredSessionLog( $label, $value, array(
				'response_type'         => 'API response type',
				'mapping_path'          => 'Mapping path',
				'used_fallback_raw'     => 'Used raw message fallback',
				'output_branch'         => 'Output branch',
				'action'                => 'Client action',
				'cell_content_type'     => 'Cell content type',
				'is_post_content_cell'  => 'Post content cell',
				'is_typed_display_cell' => 'Typed display cell',
				'instruction_summary'   => 'Instruction summary',
				'empty_data_rejected'   => 'Empty data rejected',
				'empty_data_reason'     => 'Empty data reason',
			), array( 'mapped_data_value', 'client_data' ) );
		}

		if ( $label === 'applyPromptEmptyDataRejected' ) {
			return self::formatStructuredSessionLog( $label, $value, array(
				'action' => 'Client action',
				'reason' => 'Rejection reason',
			), array( 'client_data' ) );
		}

		if ( $label === 'applyPromptError' ) {
			return self::formatStructuredSessionLog( $label, $value, array(
				'message'   => 'Message',
				'exception' => 'Exception',
				'file'      => 'File',
				'line'      => 'Line',
			) );
		}

		if ( $label !== '' ) {
			return $label . "\n" . self::formatSessionValue( $value, '  ' );
		}

		return self::formatSessionValue( $value, '' );
	}

	/**
	 * @param string $entry Legacy "label: value" string.
	 * @return array{label:string,value:mixed}
	 */
	private static function parseLegacySessionDataItem( $entry ) {
		$entry = (string) $entry;
		$colon = strpos( $entry, ': ' );
		if ( $colon === false ) {
			return array(
				'label' => '',
				'value' => $entry,
			);
		}

		$label = substr( $entry, 0, $colon );
		$raw   = substr( $entry, $colon + 2 );
		$value = json_decode( $raw, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			return array(
				'label' => $label,
				'value' => $value,
			);
		}

		return array(
			'label' => $label,
			'value' => $raw,
		);
	}

	/**
	 * Human-readable structured session log block.
	 *
	 * @param string               $title           Block title.
	 * @param mixed                $value           Log payload.
	 * @param array<string,string> $scalar_labels   Scalar field labels.
	 * @param string[]             $nested_keys     Keys rendered as nested JSON below scalars.
	 * @return string
	 */
	private static function formatStructuredSessionLog( $title, $value, $scalar_labels, $nested_keys = array() ) {
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$value = $decoded;
			}
		}

		if ( ! is_array( $value ) ) {
			return $title . "\n" . self::formatSessionValue( $value, '  ' );
		}

		$lines = array( $title );
		foreach ( $scalar_labels as $key => $label ) {
			if ( ! array_key_exists( $key, $value ) ) {
				continue;
			}
			$field_value = $value[ $key ];
			if ( is_bool( $field_value ) ) {
				$field_value = $field_value ? 'yes' : 'no';
			} elseif ( $field_value === '' || $field_value === null ) {
				$field_value = '—';
			}
			$lines[] = '  ' . $label . ': ' . (string) $field_value;
		}

		foreach ( $nested_keys as $nested_key ) {
			if ( ! array_key_exists( $nested_key, $value ) ) {
				continue;
			}
			$lines[] = '  ' . $nested_key . ':';
			$lines[] = self::formatSessionValue( $value[ $nested_key ], '    ' );
		}

		return implode( "\n", $lines );
	}

	/**
	 * Human-readable savePostContentValue log block.
	 *
	 * @param mixed $value Log payload.
	 * @return string
	 */
	private static function formatSavePostContentValueLog( $value ) {
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$value = $decoded;
			}
		}

		if ( ! is_array( $value ) ) {
			return "savePostContentValue\n" . self::formatSessionValue( $value, '  ' );
		}

		$lines   = array( 'savePostContentValue' );
		$labels  = array(
			'post_id'       => 'Post ID',
			'mode'          => 'Save mode',
			'success'       => 'Success',
			'is_elementor'  => 'Elementor page',
			'value_len'     => 'Value length',
			'elementor_len' => 'Elementor JSON length',
			'post_content_len' => 'Post content fallback length',
			'value_type'    => 'Value type',
			'value_preview' => 'Value preview',
			'value_json_error'     => 'Value JSON error',
			'elementor_json_error' => 'Elementor JSON error',
			'value_has_eltype'     => 'Value contains elType',
			'fallback'             => 'Used text fallback',
		);

		foreach ( $labels as $key => $title ) {
			if ( ! array_key_exists( $key, $value ) ) {
				continue;
			}
			$field_value = $value[ $key ];
			if ( $key === 'value_preview' ) {
				$field_value = self::formatSavePostContentPreview( $field_value );
			} elseif ( is_bool( $field_value ) ) {
				$field_value = $field_value ? 'yes' : 'no';
			} elseif ( $key === 'value_has_eltype' || $key === 'fallback' ) {
				$field_value = ! empty( $field_value ) ? 'yes' : 'no';
			}
			$lines[] = '  ' . $title . ': ' . (string) $field_value;
		}

		return implode( "\n", $lines );
	}

	/**
	 * Human-readable applyPromptPostContent log block.
	 *
	 * @param mixed $value Log payload.
	 * @return string
	 */
	private static function formatApplyPromptPostContentLog( $value ) {
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$value = $decoded;
			}
		}

		if ( ! is_array( $value ) ) {
			return "applyPromptPostContent\n" . self::formatSessionValue( $value, '  ' );
		}

		$lines  = array( 'applyPromptPostContent' );
		$labels = array(
			'post_id'      => 'Post ID',
			'is_elementor' => 'Elementor page',
			'mode'         => 'Output mode',
			'blocks_count' => 'Blocks count',
			'insert_len'   => 'Insert length',
			'show_len'     => 'Show length',
		);

		foreach ( $labels as $key => $title ) {
			if ( ! array_key_exists( $key, $value ) ) {
				continue;
			}
			$field_value = $value[ $key ];
			if ( is_bool( $field_value ) ) {
				$field_value = $field_value ? 'yes' : 'no';
			}
			$lines[] = '  ' . $title . ': ' . (string) $field_value;
		}

		return implode( "\n", $lines );
	}

	/**
	 * @param mixed $preview Raw preview value.
	 * @return string
	 */
	private static function formatSavePostContentPreview( $preview ) {
		$text = is_scalar( $preview ) ? (string) $preview : wp_json_encode( $preview );
		$text = trim( $text );
		if ( $text === '' ) {
			return '—';
		}

		if ( $text[0] === '[' || $text[0] === '{' ) {
			$decoded = json_decode( $text, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				return '[Elementor / JSON payload, ' . strlen( $text ) . ' chars]';
			}
		}

		$text = preg_replace( '/\s+/', ' ', $text );
		if ( strlen( $text ) > 120 ) {
			return substr( $text, 0, 120 ) . '…';
		}

		return $text;
	}

	/**
	 * @param mixed  $value   Value to format.
	 * @param string $indent  Line prefix.
	 * @return string
	 */
	private static function formatSessionValue( $value, $indent = '' ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return $indent . wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}

		if ( is_bool( $value ) ) {
			return $indent . ( $value ? 'true' : 'false' );
		}

		if ( $value === null ) {
			return $indent . 'null';
		}

		$text = (string) $value;
		$decoded = json_decode( $text, true );
		if ( json_last_error() === JSON_ERROR_NONE && ( is_array( $decoded ) || is_object( $decoded ) ) ) {
			return $indent . wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}

		return $indent . $text;
	}

	/**
	 * Persist the current session to wp_options (keeps only the last MAX_SESSIONS entries).
	 */
	public static function commitSession() {
		if ( ! self::isEnabled() || self::$current_session === null ) {
			return;
		}

		$sessions = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $sessions ) ) {
			$sessions = array();
		}

		array_unshift( $sessions, self::$current_session );
		if ( count( $sessions ) > self::MAX_SESSIONS ) {
			$sessions = array_slice( $sessions, 0, self::MAX_SESSIONS );
		}

		update_option( self::OPTION_NAME, $sessions, false );
		self::$current_session = null;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function getSessions() {
		$sessions = get_option( self::OPTION_NAME, array() );
		return is_array( $sessions ) ? $sessions : array();
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	private static function stringifyValue( $value ) {
		if ( is_string( $value ) ) {
			return $value;
		}
		if ( is_scalar( $value ) || $value === null ) {
			return (string) $value;
		}
		if ( is_array( $value ) || is_object( $value ) ) {
			return wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}
		return (string) $value;
	}
}

SheetsPilot_AjaxSessionLog::registerHooks();
