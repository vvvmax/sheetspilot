<?php

/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if (! defined('ABSPATH')) exit;
if (!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilot_AjaxActions
{
	private $lastApplyPromptErrorLogId = 0;
	private $lastApplyPromptErrorDetailText = '';


	/**
	 * get data array from request
	 */
	private function getDataFromRequest()
	{
	
		$data = SheetsPilotFunctions::getPostGetVariable("data", "", SheetsPilotFunctions::SANITIZE_NOTHING);

		if (is_string($data)) {

			$arrData = (array)json_decode($data);

			if (empty($arrData)) {
				$arrData = stripslashes(trim($data));
				$arrData = (array)json_decode($arrData);
			}

			$data = $arrData;
		}

		$data = SheetsPilotFunctions::convertStdClassToArray($data);
		$data = SheetsPilotFunctions::normalizeAjaxInputData($data);				
		$data = self::deepSanitize($data);

		return ($data);
	
	}


	/**
	 * sanitize attributes
	 */
	private static function deepSanitize( $value, $key = null, $parent = null ) {

		if ( $key === 'value' && isset( $parent['type'] ) && $parent['type'] === 'wysiwyg' ) {
			return wp_unslash( $value );
		}

		if ( is_array( $value ) ) {
			$sanitized = array();
			foreach ( $value as $item_key => $item ) {
				$sanitized[ $item_key ] = self::deepSanitize(
					$item,
					is_string( $item_key ) ? $item_key : null,
					$value
				);
			}
			return $sanitized;
		}

		if ( is_string( $value ) ) {
			if (
				$key === 'elementor_data'
				|| $key === 'insert_value'
				|| self::shouldPreserveRawJsonString( $value )
				|| self::shouldPreserveRawJsonForSave( $value, $key, $parent )
			) {
				return wp_unslash( $value );
			}

			if ( $value !== wp_strip_all_tags( $value ) ) {
				return wp_kses_post( $value );
			}

			return sanitize_text_field( $value );
		}

		if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) ) {
			return $value;
		}

		return wp_kses_post( (string) $value );
	}

	/**
	 * Keep Elementor/Gutenberg JSON payloads intact (they often contain HTML in block fields).
	 *
	 * @param string $value Raw string value.
	 * @return bool
	 */
	private static function shouldPreserveRawJsonString( $value ) {
		if ( ! is_string( $value ) ) {
			return false;
		}

		$trimmed = ltrim( $value );
		if ( $trimmed === '' || ( $trimmed[0] !== '[' && $trimmed[0] !== '{' ) ) {
			return false;
		}

		$candidates = array( $trimmed, wp_unslash( $trimmed ), stripslashes( $trimmed ) );
		foreach ( $candidates as $candidate ) {
			$decoded = json_decode( $candidate, true );
			if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
				continue;
			}

			if ( class_exists( 'SheetsPilot_ContentBlocks' ) ) {
				if ( SheetsPilot_ContentBlocks::is_elementor_layout_tree( $decoded ) ) {
					return true;
				}
				if ( SheetsPilot_ContentBlocks::is_blocks_payload( $decoded ) ) {
					return true;
				}
			}

			if ( isset( $decoded[0]['elType'] ) || isset( $decoded['blocks'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Preserve Elementor layout JSON on save_edited_posts rows even when json_decode fails early.
	 *
	 * @param string     $value  Raw string value.
	 * @param string|null $key   Field key.
	 * @param array|null $parent Parent array from AJAX payload.
	 * @return bool
	 */
	private static function shouldPreserveRawJsonForSave( $value, $key, $parent ) {
		if ( $key !== 'value' && $key !== 'elementor_data' ) {
			return false;
		}

		if ( ! is_array( $parent ) || ! self::looksLikeJsonTreeString( $value ) ) {
			return false;
		}

		if ( ! empty( $parent['is_elementor'] ) ) {
			return true;
		}

		if ( $key === 'elementor_data' ) {
			return true;
		}

		// Gutenberg post_content saves content-blocks JSON in value before conversion.
		if ( $key === 'value' && strpos( $value, '"blocks"' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $value Raw string value.
	 * @return bool
	 */
	private static function looksLikeJsonTreeString( $value ) {
		if ( ! is_string( $value ) ) {
			return false;
		}

		$trimmed = ltrim( $value );
		if ( $trimmed === '' ) {
			return false;
		}

		$first = $trimmed[0];
		if ( $first !== '[' && $first !== '{' ) {
			return false;
		}

		return ( strpos( $trimmed, '"elType"' ) !== false || strpos( $trimmed, '"blocks"' ) !== false );
	}

	/**
	 * run this function on exception
	 */
	private function onException($e, $prefix = "", $bufferedOutput = '')
	{

		$message = $e->getMessage();

		if (!empty($prefix))
			$message = $prefix . $message;

		if (SheetsPilotGlobals::DEBUG_ERRORS == true)
			SheetsPilotHelper::outputExceptionBox($e);

		if (SheetsPilotGlobals::$showTrace) {
			$trace = $e->getTraceAsString();
			$message .= "<pre>" . $trace . "</pre>";
		}
		if (is_string($bufferedOutput) && trim($bufferedOutput) !== '') {
			$message .= "<pre>Buffered output before failure:\n" . esc_html($bufferedOutput) . "</pre>";
		}

		$error_payload = null;
		if ( $this->lastApplyPromptErrorLogId > 0 || $this->lastApplyPromptErrorDetailText !== '' ) {
			$error_payload = array();
			if ( $this->lastApplyPromptErrorLogId > 0 ) {
				$error_payload['log_id'] = (int) $this->lastApplyPromptErrorLogId;
			}
			if ( $this->lastApplyPromptErrorDetailText !== '' ) {
				$error_payload['error_detail_text'] = $this->lastApplyPromptErrorDetailText;
				$message = 'OpenAI error: ' . $this->lastApplyPromptErrorDetailText;
			}
			$this->lastApplyPromptErrorLogId = 0;
			$this->lastApplyPromptErrorDetailText = '';
		}

		SheetsPilotHelper::ajaxResponseError($message, $error_payload);
	}

	/**
	 * Register a shutdown handler to emit JSON on fatal errors (prevents empty AJAX response bodies).
	 *
	 * @param string $action AJAX client_action.
	 * @return void
	 */
	private function registerAjaxFatalShutdownHandler($action = '')
	{
		$action = sanitize_key((string)$action);
		register_shutdown_function(function () use ($action) {
			$error = error_get_last();
			if (!is_array($error)) {
				return;
			}

			$type = isset($error['type']) ? (int)$error['type'] : 0;
			$is_fatal = in_array($type, array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR), true);
			if (!$is_fatal) {
				return;
			}

			while (ob_get_level() > 0) {
				@ob_end_clean();
			}

			$message = 'Fatal PHP error during AJAX request.';
			if ($action !== '') {
				$message .= ' Action: ' . $action . '.';
			}

			$payload = array(
				'success'  => false,
				'message'  => $message,
				'is_fatal' => true,
				'action'   => $action,
			);

			$is_debug = (defined('WP_DEBUG') && WP_DEBUG) || SheetsPilotGlobals::DEBUG_ERRORS == true || SheetsPilotGlobals::$showTrace;
			if ($is_debug) {
				$payload['fatal'] = array(
					'type'    => $type,
					'message' => isset($error['message']) ? (string)$error['message'] : '',
					'file'    => isset($error['file']) ? (string)$error['file'] : '',
					'line'    => isset($error['line']) ? (int)$error['line'] : 0,
				);
			}

			if (!headers_sent()) {
				if (function_exists('status_header')) {
					status_header(500);
				}
				header('Content-Type: application/json; charset=UTF-8');
			}

			if (function_exists('wp_json_encode')) {
				echo wp_json_encode($payload);
			} else {
				echo json_encode($payload);
			}
		});
	}

	/**
	 * Read and clear currently buffered output for debugging error responses.
	 *
	 * @return string
	 */
	private function consumeBufferedOutput()
	{
		$output = '';
		while (ob_get_level() > 0) {
			$chunk = ob_get_clean();
			if (is_string($chunk) && $chunk !== '') {
				$output = $chunk . $output;
			}
		}
		return trim($output);
	}


	/**
	 * get action title
	 */
	private function getActionTitle($action)
	{

		$title = "";

		switch ($action) {
			case "paste_elementor_section":
				$title = __("Paste Elementor Section", 'sheetspilot');
				break;
		}


		return ($title);
	}

	/**
	 * Persist apply_prompt request/response to the logs table (success or failure).
	 *
	 * @param string       $prompt_text     Prompt text sent to the API.
	 * @param array|string $table_data      Table/cell payload from the client.
	 * @param string       $response_action Client action or "error" on failure.
	 * @param string|array $response_data   Result data or error message.
	 * @param array|null   $prompt_metadata Optional metadata from collectApplyPromptMetadata().
	 * @return int Inserted log row id, or 0 when logging is unavailable.
	 */
	private function persistApplyPromptRequestLog( $prompt_text, $table_data, $response_action, $response_data, $prompt_metadata = null ) {
		if ( ! class_exists( 'SheetsPilot_RequestLog' ) ) {
			return 0;
		}

		$log_cell_value = is_array( $table_data ) ? (string) SheetsPilotFunctions::getVal( $table_data, 'value', '' ) : $table_data;
		$raw_request    = null;
		$raw_response   = null;

		if ( SheetsPilotGlobals::$isPro == true && class_exists( 'SheetsPilot_UseChatGPT' ) ) {
			$debug_info   = SheetsPilot_UseChatGPT::getLastRequestResponse();
			$raw_request  = SheetsPilotFunctions::getVal( $debug_info, 'request' );
			$raw_response = SheetsPilotFunctions::getVal( $debug_info, 'response' );
		}
		if ( $prompt_metadata === null ) {
			$prompt_metadata = $this->collectApplyPromptMetadata();
		}

		$insert_id = SheetsPilot_RequestLog::insert(
			$prompt_text,
			$log_cell_value,
			$raw_request,
			$raw_response,
			$response_action,
			$response_data,
			$prompt_metadata
		);

		return $insert_id ? (int) $insert_id : 0;
	}

	/**
	 * Collect prompt/request metadata from the last apply_prompt API run.
	 *
	 * @return array<string,mixed>
	 */
	private function collectApplyPromptMetadata() {
		if ( SheetsPilotGlobals::$isPro != true || ! class_exists( 'SheetsPilot_Prompts', false ) ) {
			return array();
		}

		return SheetsPilot_Prompts::getLastPromptRequestMetadata();
	}

	/**
	 * Append apply_prompt debug entry to the AJAX session log.
	 *
	 * @param string              $label Session log label.
	 * @param array<string,mixed> $data  Payload fields.
	 * @return void
	 */
	private function logApplyPromptSession( $label, $data = array() ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		SheetsPilot_AjaxSessionLog::addData( $label, $data );
	}

	/**
	 * Append prompt request metadata to the AJAX session log (once per apply_prompt).
	 *
	 * @param array<string,mixed>|null $metadata Pre-collected metadata, or null to fetch.
	 * @return void
	 */
	private function logApplyPromptMetadataSession( $metadata = null ) {
		if ( $metadata === null ) {
			$metadata = $this->collectApplyPromptMetadata();
		}
		if ( empty( $metadata ) ) {
			return;
		}

		SheetsPilot_AjaxSessionLog::addData( 'applyPromptMetadata', $metadata );
	}

	/**
	 * Whether the normalized apply_prompt client payload has no usable data.
	 *
	 * @param mixed  $data   Result data returned to the client.
	 * @param string $action Client action (replace_text, show_message, pending_image, ...).
	 * @return bool
	 */
	private function isApplyPromptClientDataEmpty( $data, $action ) {
		if ( $action === 'pending_image' ) {
			return ! is_array( $data ) || empty( $data['request_id'] );
		}

		if ( $data === null || $data === '' ) {
			return true;
		}

		if ( is_string( $data ) ) {
			return trim( $data ) === '';
		}

		if ( ! is_array( $data ) ) {
			return empty( $data );
		}

		if ( ! empty( $data['blocks'] ) && is_array( $data['blocks'] ) ) {
			return false;
		}

		if ( $action === 'replace_text' ) {
			$insert = array_key_exists( 'insert', $data ) ? $data['insert'] : $data;
			if ( is_string( $insert ) ) {
				return trim( $insert ) === '';
			}
			if ( is_array( $insert ) ) {
				return empty( $insert );
			}

			return empty( $insert );
		}

		return empty( $data );
	}

	/**
	 * Short text preview for session log (avoid huge cell/JSON payloads).
	 *
	 * @param mixed $text  Raw value.
	 * @param int   $max   Max characters.
	 * @return string
	 */
	private function truncateSessionLogText( $text, $max = 120 ) {
		$text = is_scalar( $text ) ? (string) $text : wp_json_encode( $text );
		$text = preg_replace( '/\s+/', ' ', trim( $text ) );
		if ( $text === '' ) {
			return '';
		}
		if ( strlen( $text ) > $max ) {
			return substr( $text, 0, $max ) . '…';
		}

		return $text;
	}

	/**
	 * Compact value summary for apply_prompt session log tracing.
	 *
	 * @param mixed $value Value at a pipeline step.
	 * @return array<string,mixed>
	 */
	private function summarizeValueForApplyPromptSessionLog( $value ) {
		if ( $value === null ) {
			return array( 'type' => 'null' );
		}
		if ( is_string( $value ) ) {
			return array(
				'type'    => 'string',
				'len'     => strlen( $value ),
				'preview' => $this->truncateSessionLogText( $value ),
			);
		}
		if ( is_array( $value ) ) {
			$summary = array(
				'type'         => 'array',
				'keys'         => array_keys( $value ),
				'has_insert'   => array_key_exists( 'insert', $value ),
				'has_show'     => array_key_exists( 'show', $value ),
				'has_blocks'   => ! empty( $value['blocks'] ),
				'is_elementor' => ! empty( $value['is_elementor'] ),
			);
			if ( isset( $value['insert'] ) ) {
				$insert = $value['insert'];
				if ( is_string( $insert ) ) {
					$summary['insert_len'] = strlen( $insert );
					$summary['insert_preview'] = $this->truncateSessionLogText( $insert );
				} elseif ( is_array( $insert ) ) {
					$summary['insert'] = $this->summarizeValueForApplyPromptSessionLog( $insert );
				}
			}
			if ( isset( $value['show'] ) && is_string( $value['show'] ) ) {
				$summary['show_len'] = strlen( $value['show'] );
				$summary['show_preview'] = $this->truncateSessionLogText( $value['show'] );
			}

			return $summary;
		}

		return array( 'type' => gettype( $value ) );
	}

	/**
	 * Human-readable reason when apply_prompt client data is rejected as empty.
	 *
	 * @param mixed  $data   Result data returned to the client.
	 * @param string $action Client action.
	 * @return string
	 */
	private function getApplyPromptClientDataEmptyReason( $data, $action ) {
		if ( $action === 'pending_image' ) {
			return 'pending_image: missing request_id';
		}
		if ( $data === null || $data === '' ) {
			return $action . ': data is null or empty string';
		}
		if ( is_string( $data ) && trim( $data ) === '' ) {
			return $action . ': data is whitespace-only string';
		}
		if ( is_array( $data ) && $action === 'replace_text' ) {
			if ( ! empty( $data['blocks'] ) ) {
				return '';
			}
			$insert = array_key_exists( 'insert', $data ) ? $data['insert'] : null;
			if ( $insert === null ) {
				return 'replace_text: missing insert key';
			}
			if ( is_string( $insert ) && trim( $insert ) === '' ) {
				return 'replace_text: insert is empty after wp_kses/mapping';
			}
			if ( is_array( $insert ) && empty( $insert ) ) {
				return 'replace_text: insert array is empty';
			}
		}
		if ( is_array( $data ) && empty( $data ) ) {
			return $action . ': data array is empty';
		}

		return $action . ': data considered empty';
	}

	/**
	 * Whether a value is a non-empty sequential (0..n-1) list array.
	 *
	 * @param mixed $value Candidate value.
	 * @return bool
	 */
	private function isApplyPromptListArray( $value ) {
		if ( ! is_array( $value ) || $value === array() ) {
			return false;
		}
		if ( function_exists( 'array_is_list' ) ) {
			return array_is_list( $value );
		}

		return array_keys( $value ) === range( 0, count( $value ) - 1 );
	}

	/**
	 * Whether the apply_prompt target is an ACF repeater cell.
	 *
	 * @param string $cell_content_type Resolved content type from prompt builder.
	 * @param array  $table_data        Table/cell request data.
	 * @return bool
	 */
	private function isApplyPromptRepeaterCell( $cell_content_type, $table_data ) {
		if ( $cell_content_type === 'acf_repeater' ) {
			return true;
		}

		$cell_type = SheetsPilotFunctions::getVal( $table_data, 'cellType', '' );

		return in_array( $cell_type, array( 'repeater', 'acf_repeater' ), true );
	}

	/**
	 * Normalize GPT repeater output to a list of row arrays.
	 *
	 * @param mixed $data_value Raw API data value.
	 * @return array<int,array<string,mixed>>|null
	 */
	private function normalizeApplyPromptRepeaterData( $data_value ) {
		if ( is_string( $data_value ) ) {
			$decoded = json_decode( $data_value, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$data_value = $decoded;
			}
		}
		if ( is_object( $data_value ) ) {
			$data_value = (array) $data_value;
		}
		if ( ! is_array( $data_value ) ) {
			return null;
		}

		if ( $this->isApplyPromptListArray( $data_value ) ) {
			return $data_value;
		}
		if ( isset( $data_value['data'] ) && $this->isApplyPromptListArray( $data_value['data'] ) ) {
			return $data_value['data'];
		}
		if ( isset( $data_value['output'] ) && $this->isApplyPromptListArray( $data_value['output'] ) ) {
			return $data_value['output'];
		}

		return null;
	}

	/**
	 * Human-readable preview text for repeater rows in the replace dialog.
	 *
	 * @param array<int,array<string,mixed>> $rows Repeater row list.
	 * @return string
	 */
	private function formatApplyPromptRepeaterDisplayText( $rows ) {
		if ( ! is_array( $rows ) || $rows === array() ) {
			return '';
		}

		$row_count = count( $rows );
		$lines     = array(
			sprintf(
				/* translators: %d: number of repeater rows */
				_n( '%d repeater row:', '%d repeater rows:', $row_count, 'sheetspilot' ),
				$row_count
			),
			'',
		);

		foreach ( $rows as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$lines[] = sprintf(
				/* translators: %d: repeater row number */
				__( 'Row %d', 'sheetspilot' ),
				(int) $index + 1
			);

			foreach ( $row as $field_key => $field_value ) {
				if ( is_array( $field_value ) || is_object( $field_value ) ) {
					$field_value = wp_json_encode( $field_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
				} elseif ( $field_value === null ) {
					$field_value = '';
				} else {
					$field_value = (string) $field_value;
				}

				$lines[] = '  ' . (string) $field_key . ': ' . $field_value;
			}

			if ( (int) $index < $row_count - 1 ) {
				$lines[] = '';
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * Package repeater rows for the apply_prompt replace dialog.
	 *
	 * @param array<int,array<string,mixed>> $rows Repeater row list.
	 * @return array{insert:string,show:string}
	 */
	private function buildApplyPromptRepeaterClientData( $rows ) {
		return array(
			'insert' => SheetsPilotFunctions::toString( $rows ),
			'show'   => $this->formatApplyPromptRepeaterDisplayText( $rows ),
		);
	}


	/**
	 * Whether the apply_prompt target row is Elementor-built.
	 *
	 * @param int   $post_id    Post ID.
	 * @param array $table_data Table/cell request data.
	 * @return bool
	 */
	private function isPostContentElementorRow( $post_id, $table_data ) {
		$is_elementor = false;
		if ( $post_id > 0 && class_exists( 'SheetsPilotHelperElementor' ) ) {
			$is_elementor = SheetsPilotHelperElementor::isPostBuiltWithElementor( $post_id );
		}
		if ( ! $is_elementor && ! empty( $table_data['is_elementor'] ) ) {
			$is_elementor = filter_var( $table_data['is_elementor'], FILTER_VALIDATE_BOOLEAN );
		}

		return $is_elementor;
	}

	/**
	 * Package AI content-blocks for apply_prompt post_content cells.
	 *
	 * show   — dialog preview text.
	 * insert — Gutenberg HTML for standard posts, simple HTML fallback for Elementor.
	 * blocks — content-blocks array (Elementor only; saved as elementor_data on the client).
	 *
	 * @param mixed  $insert_value         Blocks payload or legacy insert value.
	 * @param string $display_text         Plain preview text.
	 * @param array  $table_data           Table/cell request data.
	 * @param bool   $is_post_content_cell Whether the target column is post_content.
	 * @return mixed Normalized data for the client (insert/show/blocks array or legacy value).
	 */
	private function convertPromptBlocksToClientData( $insert_value, $display_text, $table_data, $is_post_content_cell ) {

		if ( ! $is_post_content_cell || ! class_exists( 'SheetsPilot_ContentBlocks' ) ) {
			if ( $display_text !== '' ) {
				return array(
					'insert' => SheetsPilotFunctions::toString( $insert_value ),
					'show'   => $display_text,
				);
			}

			return $insert_value;
		}

		$post_id = absint( SheetsPilotFunctions::getVal( $table_data, 'postId', 0 ) );

		$blocks_payload = null;
		if ( SheetsPilot_ContentBlocks::is_blocks_payload( $insert_value ) ) {
			$blocks_payload = is_array( $insert_value ) ? $insert_value : json_decode( (string) $insert_value, true );
		} else {
			$blocks_payload = SheetsPilot_ContentBlocks::resolve_blocks_payload_from_prompt_data( $insert_value );
		}

		if ( is_array( $blocks_payload ) ) {
			$show = is_string( $display_text ) ? trim( $display_text ) : '';
			if ( $show === '' ) {
				$show = SheetsPilot_ContentBlocks::to_display_text( $blocks_payload );
			}
			if ( $show === '' && ! empty( $blocks_payload['display_text'] ) ) {
				$show = SheetsPilotFunctions::toString( $blocks_payload['display_text'] );
			}

			$blocks_list = SheetsPilot_ContentBlocks::normalize_blocks_payload( $blocks_payload );
			$is_elementor = $this->isPostContentElementorRow( $post_id, $table_data );

			if ( $is_elementor ) {
				$insert = SheetsPilot_ContentBlocks::to_simple_html( $blocks_payload );
				if ( $insert === '' ) {
					$insert = $show;
				}
			} else {
				$insert = SheetsPilot_ContentBlocks::to_gutenberg( $blocks_payload );
				if ( $show === '' ) {
					$show = SheetsPilot_Prompts::get_plain_text_for_prompt_display( $insert );
				}
			}

			SheetsPilot_AjaxSessionLog::addData(
				'applyPromptPostContent',
				array(
					'post_id'      => $post_id,
					'mode'         => $is_elementor ? 'content_blocks_elementor' : 'content_blocks_gutenberg',
					'blocks_count' => count( $blocks_list ),
					'show_len'     => strlen( $show ),
					'insert_len'   => strlen( (string) $insert ),
				)
			);

			if ( $is_elementor ) {
				return array(
					'insert'       => $insert,
					'show'         => $show,
					'blocks'       => array( 'blocks' => $blocks_list ),
					'is_elementor' => true,
				);
			}

			return array(
				'insert' => $insert,
				'show'   => $show,
			);
		}

		$is_elementor = $this->isPostContentElementorRow( $post_id, $table_data );

		if ( $is_elementor ) {
			$elementor_tree = SheetsPilot_ContentBlocks::normalize_elementor_layout( $insert_value );
			if ( is_array( $elementor_tree ) && ! empty( $elementor_tree ) ) {
				return array(
					'insert'       => wp_json_encode( $elementor_tree ),
					'show'         => $display_text !== '' ? $display_text : SheetsPilot_ContentBlocks::elementor_data_to_display_text( $elementor_tree ),
					'is_elementor' => true,
				);
			}
		}

		if ( $display_text !== '' ) {
			return array(
				'insert' => SheetsPilotFunctions::toString( $insert_value ),
				'show'   => $display_text,
			);
		}

		return $insert_value;
	}

	/**
	 * Handle apply prompt action: run prompt, attach debug info, log request/response, return results.
	 *
	 * @param array $data Request data with "prompt" and "table".
	 * @return array Results with "action", "data", "debugRequest", "debugResponse".
	 */
	private function handleApplyPrompt($data)
	{

		$isDebug = false;
		if ($isDebug) {
			dmp("handleApplyPrompt data");
			dmp($data);
			exit();
		}

		// Normalize and extract prompt text and table payload from request.
		$prompt_text = isset($data["prompt"]) ? trim($data["prompt"]) : "";
		$table_data  = isset($data["table"]) ? $data["table"] : array();
		$column_name = SheetsPilotFunctions::getVal($table_data, 'column', '');

		// Context menu: empty data-prompt on the client still sends data-action; fill from Globals map (e.g. cleared markup or generate-image follow-up).
		$ctx_action = isset($table_data['context_menu_action']) ? trim((string) $table_data['context_menu_action']) : '';
		if ($prompt_text === '' && $ctx_action !== '' && isset(SheetsPilotGlobals::$contextMenuPrompts[$ctx_action])) {
			$prompt_text = trim((string) SheetsPilotGlobals::$contextMenuPrompts[$ctx_action]);
		}

		// Require non-empty prompt unless image mode (create sends image_settings; edit/create image column allows empty prompt).
		$has_image_settings = isset($table_data['image_settings']) && is_array($table_data['image_settings']);
		$cell_type          = SheetsPilotFunctions::getVal($table_data, 'cellType', '');
		$is_image_column    = ( $column_name === 'post_image' || $cell_type === 'image' || $cell_type === 'acf_gallery' || $cell_type === 'acf_woo_gallery' );
		if ( $prompt_text === '' && ! $has_image_settings && ! $is_image_column ) {
			SheetsPilotFunctions::throwError(__('Please enter a prompt.', 'sheetspilot'));
		}
		if (!is_array($table_data)) {
			SheetsPilotFunctions::throwError(__("Table data is invalid.", 'sheetspilot'));
		}
		if (SheetsPilotGlobals::$isPro != true) {
			SheetsPilotFunctions::throwError(__('AI assistant is available in Pro version.', 'sheetspilot'));
		}
		if (! class_exists('SheetsPilot_UseChatGPT', false)) {
			SheetsPilotFunctions::throwError(__('Pro AI module is not loaded.', 'sheetspilot'));
		}

		if ( class_exists( 'SheetsPilot_RequestLog', false ) ) {
			SheetsPilot_RequestLog::startRequestTimer();
		}

		$this->logApplyPromptSession(
			'applyPromptStart',
			array(
				'column'              => $column_name,
				'post_id'             => (int) SheetsPilotFunctions::getVal( $table_data, 'postId', 0 ),
				'post_type'           => (string) SheetsPilotFunctions::getVal( $table_data, 'post_type', '' ),
				'cell_type'           => (string) SheetsPilotFunctions::getVal( $table_data, 'cellType', '' ),
				'context_menu_action' => $ctx_action,
				'is_post_content'     => ( $column_name === 'post_content' ),
				'is_image_column'     => $is_image_column,
				'has_image_settings'  => $has_image_settings,
				'prompt_len'          => strlen( $prompt_text ),
				'prompt_preview'      => $this->truncateSessionLogText( $prompt_text ),
				'value_len'           => strlen( (string) SheetsPilotFunctions::getVal( $table_data, 'value', '' ) ),
				'value_preview'       => $this->truncateSessionLogText( SheetsPilotFunctions::getVal( $table_data, 'value', '' ) ),
			)
		);

		// Run the AI prompt against the table (API call and cell logic).
		try {
			$results = SheetsPilotCellEditor::applyPromptToTable($table_data, $prompt_text);
			
			if( isset( $results['type'] ) && isset( $results['status'] ) ){
				if( $results['status'] == 'queued' || $results['status'] == 'in_progress' ){
					$this->logApplyPromptSession( 'applyPromptQueued', $results );
					return $results;
				}
			}			

			if ( class_exists( 'SheetsPilot_RequestLog', false ) ) {
				SheetsPilot_RequestLog::endRequestTimer();
			}

			$this->logApplyPromptSession( 'applyPromptApiResponse', $results );
		} catch ( Throwable $e ) {
			if ( class_exists( 'SheetsPilot_RequestLog', false ) ) {
				SheetsPilot_RequestLog::endRequestTimer();
			}
			$error_metadata = $this->collectApplyPromptMetadata();
			$this->logApplyPromptSession(
				'applyPromptError',
				array(
					'message'   => $e->getMessage(),
					'exception' => get_class( $e ),
					'file'      => $e->getFile(),
					'line'      => $e->getLine(),
				)
			);
			$this->logApplyPromptMetadataSession( $error_metadata );
			$this->lastApplyPromptErrorLogId = (int) $this->persistApplyPromptRequestLog(
				$prompt_text,
				$table_data,
				'error',
				$e->getMessage(),
				$error_metadata
			);
			$this->lastApplyPromptErrorDetailText = '';
			if ( SheetsPilotGlobals::$isPro == true && class_exists( 'SheetsPilot_UseChatGPT', false ) ) {
				$debug_info = SheetsPilot_UseChatGPT::getLastRequestResponse();
				$this->lastApplyPromptErrorDetailText = SheetsPilot_UseChatGPT::getImageGenerationFailureText(
					SheetsPilotFunctions::getVal( $debug_info, 'response' )
				);
			}
			throw $e;
		}

		
		$cell_content_type = SheetsPilotFunctions::getVal($results, 'cell_content_type');
		$is_post_content_cell = ($column_name === 'post_content');
		$action   = "show_message";
		$dataValue = "";
		$mapping_path = 'unknown';
		$used_fallback_raw = false;

		// Map cell-editor response type to client action and value (replace_text vs show_message).
		$responseType = SheetsPilotFunctions::getVal($results, "type");
		$instruction_summary = SheetsPilotFunctions::getVal($results, "instruction_summary", "");
		if ($responseType === "data") {
			$action = "replace_text";
			$mapping_path = 'response_type_data';
			$dataValue = SheetsPilotFunctions::getVal($results, "data");


			if (is_object($dataValue)) {
				$dataValue = (array) $dataValue;
			}
			if ( $this->isApplyPromptRepeaterCell( $cell_content_type, $table_data ) ) {
				$repeater_rows = $this->normalizeApplyPromptRepeaterData( $dataValue );
				if ( $repeater_rows !== null ) {
					$mapping_path = 'data_acf_repeater_list';
					$dataValue    = $this->buildApplyPromptRepeaterClientData( $repeater_rows );
				}
			} elseif ( is_string( $dataValue ) && class_exists( 'SheetsPilot_ContentBlocks' ) ) {
				$resolved_payload = SheetsPilot_ContentBlocks::resolve_blocks_payload_from_prompt_data( $dataValue );
				if ( is_array( $resolved_payload ) ) {
					$dataValue = $resolved_payload;
					$mapping_path = 'data_string_resolved_to_blocks';
				}
			}
			// GPT returns { "data": ... }; image generation returns { "insert", "show" }. Keep insert/show as-is.
			if ( is_array( $dataValue ) && ! array_key_exists( 'insert', $dataValue ) ) {
				$mapping_path = 'data_without_insert_key';
				$display_text = SheetsPilot_Prompts::getDisplayTextFromPromptResponse($dataValue);
				$insert_value  = null;
				if ( class_exists( 'SheetsPilot_ContentBlocks' ) ) {
					$blocks_payload = SheetsPilot_ContentBlocks::resolve_blocks_payload_from_prompt_data( $dataValue );
					if ( is_array( $blocks_payload ) ) {
						$insert_value = $blocks_payload;
						if ( $display_text === '' && ! empty( $blocks_payload['display_text'] ) ) {
							$display_text = SheetsPilotFunctions::toString( $blocks_payload['display_text'] );
						}
					}
				}
				if ( $insert_value === null || $insert_value === '' ) {
					$insert_value = SheetsPilotFunctions::getVal( $dataValue, 'data' );
				}
				if ( $is_post_content_cell && class_exists( 'SheetsPilot_ContentBlocks' ) ) {
					$mapping_path = 'data_blocks_post_content';
					$dataValue = $this->convertPromptBlocksToClientData( $insert_value, $display_text, $table_data, true );
				} elseif ($display_text !== '') {
					$mapping_path = 'data_insert_show_from_display';
					$dataValue = array(
						'insert' => SheetsPilotFunctions::toString($insert_value),
						'show'   => $display_text,
					);
				} else {
					$mapping_path = 'data_raw_insert_value';
					$dataValue = $insert_value;
				}
			} else {
				$mapping_path = 'data_has_insert_key';
			}
		} elseif ($responseType === "text") {
			$mapping_path = 'response_type_text';
			$text_value = SheetsPilotFunctions::getVal($results, "text");
			$dataValue    = $text_value;
			if ( $this->isApplyPromptRepeaterCell( $cell_content_type, $table_data ) ) {
				$repeater_rows = $this->normalizeApplyPromptRepeaterData( $text_value );
				if ( $repeater_rows !== null ) {
					$action       = 'replace_text';
					$mapping_path = 'text_acf_repeater_list';
					$dataValue    = $this->buildApplyPromptRepeaterClientData( $repeater_rows );
				}
			} elseif ( class_exists( 'SheetsPilot_ContentBlocks' ) ) {
				$blocks_payload = SheetsPilot_ContentBlocks::resolve_blocks_payload_from_prompt_data( $text_value );
				if ( is_array( $blocks_payload ) ) {
					$action    = 'replace_text';
					$mapping_path = 'text_resolved_to_blocks';
					$dataValue = $blocks_payload;
					$display_text = SheetsPilot_Prompts::getDisplayTextFromPromptResponse( $blocks_payload );
					if ( $display_text === '' && ! empty( $blocks_payload['display_text'] ) ) {
						$display_text = SheetsPilotFunctions::toString( $blocks_payload['display_text'] );
					}
					$dataValue = $this->convertPromptBlocksToClientData(
						$blocks_payload,
						$display_text,
						$table_data,
						$is_post_content_cell
					);
				}
			}
		} elseif ($responseType === "pending_image") {
			$mapping_path = 'response_type_pending_image';
			$action = "pending_image";
			$dataValue = array(
				'request_id'  => SheetsPilotFunctions::getVal($results, 'request_id'),
				'preview_url' => SheetsPilotFunctions::getVal($results, 'preview_url'),
				'post_id'     => (int) SheetsPilotFunctions::getVal($results, 'post_id'),
				'column'      => SheetsPilotFunctions::getVal($results, 'column'),
				'file_size'   => (int) SheetsPilotFunctions::getVal($results, 'file_size'),
				'file_type'   => SheetsPilotFunctions::getVal($results, 'file_type'),
				'width'       => (int) SheetsPilotFunctions::getVal($results, 'width'),
				'height'      => (int) SheetsPilotFunctions::getVal($results, 'height'),
			);
		}

		// Fallback: use raw message content when no structured value was returned.
		if ( empty( $dataValue ) ) {
			$mapping_path = 'data_value_empty_before_fallback';
			$raw_content = '';
			if ( isset( $results['choices'][0]['message']['content'] ) ) {
				$raw_content = (string) $results['choices'][0]['message']['content'];
			} elseif ( class_exists( 'SheetsPilot_UseChatGPT', false ) ) {
				$last = SheetsPilot_UseChatGPT::getLastRequestResponse();
				$last_response = SheetsPilotFunctions::getVal( $last, 'response' );
				if ( is_object( $last_response ) && isset( $last_response->choices[0]->message->content ) ) {
					$raw_content = (string) $last_response->choices[0]->message->content;
				} elseif ( is_array( $last_response ) && isset( $last_response['choices'][0]['message']['content'] ) ) {
					$raw_content = (string) $last_response['choices'][0]['message']['content'];
				}
			}

			if ( $raw_content !== '' ) {
				$used_fallback_raw = true;
				$mapping_path = 'fallback_raw_message_content';
				$blocks_payload = SheetsPilot_ContentBlocks::resolve_blocks_payload_from_prompt_data( $raw_content );
				if ( is_array( $blocks_payload ) ) {
					$action       = 'replace_text';
					$display_text = SheetsPilot_Prompts::getDisplayTextFromPromptResponse( $blocks_payload );
					$dataValue    = $this->convertPromptBlocksToClientData(
						$blocks_payload,
						$display_text,
						$table_data,
						$is_post_content_cell
					);
				} else {
					$mapping_path = 'fallback_raw_parse_failed';

					SheetsPilotFunctions::throwError(
						__( 'Error parsing response post content data. Please check with the devlopers', 'sheetspilot' )
					);
		
				}
			}
		}

		
		// Build normalized result for the client (action + data).
		// For typed cells (post_content/author/status), return both "insert" and "show" values.
		$is_typed_display_cell = ($is_post_content_cell || ! empty($cell_content_type));

		$display_value = '';
		$insert_value  = $dataValue;
		if ( is_array( $dataValue ) && array_key_exists( 'insert', $dataValue ) ) {
			$insert_value  = SheetsPilotFunctions::getVal( $dataValue, 'insert' );
			$display_value = SheetsPilotFunctions::getVal( $dataValue, 'show' );
		} else {
			$display_value = SheetsPilotFunctions::getVal( $dataValue, 'display_text' );
		}

		if ( empty( $display_value ) && $is_typed_display_cell && $action === 'replace_text' && is_string( $insert_value ) && $insert_value !== '' ) {
			$display_value = SheetsPilot_Prompts::get_plain_text_for_prompt_display( $insert_value, $cell_content_type );
		}

		if ( $action === 'replace_text' && is_array( $insert_value ) ) {
			$insert_value = SheetsPilotFunctions::toString( $insert_value );
		}

		// Output with display value.
		if ( ! empty( $display_value ) ) {
			$display_value = SheetsPilot_Prompts::modifyDisplayTextForPromptDisplay( $display_value );

			$results = array(
				'action' => $action,
				'data'   => array(
					'insert' => $insert_value,
					'show'   => $display_value,
				),
			);
			if ( is_array( $dataValue ) && isset( $dataValue['blocks'] ) && is_array( $dataValue['blocks'] ) ) {
				$results['data']['blocks'] = $dataValue['blocks'];
			}
			if ( is_array( $dataValue ) && ! empty( $dataValue['is_elementor'] ) ) {
				$results['data']['is_elementor'] = true;
			}
		} else {
			$results = array(
				'action' => $action,
				'data'   => $dataValue,
			);
		}

		$output_branch = ! empty( $display_value ) ? 'insert_show_pair' : 'raw_data_value';
		$this->logApplyPromptSession(
			'applyPromptPipeline',
			array(
				'response_type'         => (string) $responseType,
				'mapping_path'          => $mapping_path,
				'used_fallback_raw'       => $used_fallback_raw,
				'output_branch'         => $output_branch,
				'action'                => (string) $results['action'],
				'cell_content_type'     => (string) $cell_content_type,
				'is_post_content_cell'  => $is_post_content_cell,
				'is_typed_display_cell' => $is_typed_display_cell,
				'instruction_summary'   => $this->truncateSessionLogText( $instruction_summary, 80 ),
				'mapped_data_value'     => $this->summarizeValueForApplyPromptSessionLog( $dataValue ),
				'client_data'           => $this->summarizeValueForApplyPromptSessionLog( $results['data'] ),
				'empty_data_rejected'   => $this->isApplyPromptClientDataEmpty( $results['data'], $results['action'] ),
				'empty_data_reason'     => $this->getApplyPromptClientDataEmptyReason( $results['data'], $results['action'] ),
			)
		);


		// Attach last request/response for debug display (formatted in PHP for readability).
		$prompt_metadata = $this->collectApplyPromptMetadata();
		$results["debugRequest"]  = '';
		$results["debugResponse"] = '';
		if (SheetsPilotGlobals::$isPro == true && class_exists('SheetsPilot_UseChatGPT', false)) {
			$debugInfo = SheetsPilot_UseChatGPT::getLastRequestResponse();
			$raw_request  = SheetsPilotFunctions::getVal($debugInfo, "request");
			$raw_response = SheetsPilotFunctions::getVal($debugInfo, "response");
			$results["debugRequest"]  = SheetsPilot_UseChatGPT::formatDebugRequest($raw_request);
			$results["debugResponse"] = SheetsPilot_UseChatGPT::formatDebugResponse($raw_response);
		}

		$session_result = $results;
		unset( $session_result['debugRequest'], $session_result['debugResponse'] );

		$this->logApplyPromptSession( 'applyPromptClientResult', $session_result );

		// Persist to request/response log (log view) — class file lives in Pro add-on; only load when Pro folder is present.
		$log_id = $this->persistApplyPromptRequestLog(
			$prompt_text,
			$table_data,
			$results['action'],
			$results['data'],
			$prompt_metadata
		);
		if ( $log_id > 0 ) {
			$results['log_id'] = $log_id;
		}

	

		// Record prompt in history only on successful JSON (data) response, not plain text.
		if ($responseType === "data" && SheetsPilotGlobals::$isPro == true) {
			$postid    = isset($table_data['postId']) ? absint($table_data['postId']) : null;
			$post_type = null;
			if ($postid) {
				$post = get_post($postid);
				if ($post && ! empty($post->post_type)) {
					$post_type = $post->post_type;
				}
			}
			if (! $post_type && isset($table_data['post_type']) && $table_data['post_type'] !== '') {
				$post_type = sanitize_key((string) $table_data['post_type']);
			}
			SheetsPilot_PromptHistory::recordPromptRun(
				$prompt_text,
				$instruction_summary !== '' ? trim((string) $instruction_summary) : null,
				$post_type,
				$postid,
				$responseType
			);
		}

	 
		// Record image prompt in history 
		if ($responseType === "pending_image" && SheetsPilotGlobals::$isPro == true) {
			$postid    = isset($table_data['postId']) ? absint($table_data['postId']) : null;
			$post_type = null;
			if ($postid) {
				$post = get_post($postid);
				if ($post && ! empty($post->post_type)) {
					$post_type = $post->post_type;
				}
			}
			if (! $post_type && isset($table_data['post_type']) && $table_data['post_type'] !== '') {
				$post_type = sanitize_key((string) $table_data['post_type']);
			}
			SheetsPilot_PromptHistory::recordPromptRun(
				$prompt_text,
				$instruction_summary !== '' ? trim((string) $instruction_summary) : null,
				$post_type,
				$postid,
				$responseType
			);
		}

		// Fail before returning to the client when the AI response has no usable payload
		// (empty insert/show, missing blocks, etc.). Session and request logs are already written above.
		if ( $this->isApplyPromptClientDataEmpty( $results['data'], $results['action'] ) ) {
			$this->logApplyPromptSession(
				'applyPromptEmptyDataRejected',
				array(
					'action' => (string) $results['action'],
					'reason' => $this->getApplyPromptClientDataEmptyReason( $results['data'], $results['action'] ),
					'client_data' => $this->summarizeValueForApplyPromptSessionLog( $results['data'] ),
				)
			);
			$error_text = isset( SheetsPilotGlobals::$editorScriptLocalization['apply_prompt_text_1'] )
				? SheetsPilotGlobals::$editorScriptLocalization['apply_prompt_text_1']
				: __( 'Apply prompt did not return replacement text.', 'sheetspilot' );
			SheetsPilotFunctions::throwError( $error_text );
		}

		// Return latest prompts for Quick Actions dropdown (from table, via PromptHistory class).
		if (SheetsPilotGlobals::$isPro == true) {
			$results['latestPrompts'] = SheetsPilot_PromptHistory::getLastForDropdown();
			$results['latestPromptsHtml'] = SheetsPilot_PromptHistory::renderDropdownOptionsHtml();

			// Return prompt history (recent list) so the panel can update without a separate request.
			$panel_history = SheetsPilot_PromptHistory::getForPanel(100, 'all', null);
			$items_html = '';
			if (SheetsPilotGlobals::$isPro == true) {
				$items_html = SheetsPilot_PromptsUI::get_prompt_history_list_html(isset($panel_history['items']) ? $panel_history['items'] : array());
			}
			$results['promptHistory'] = array(
				'itemsHtml'    => $items_html,
				'totalRecent'  => isset($panel_history['totalRecent']) ? (int) $panel_history['totalRecent'] : 0,
				'totalSaved'   => isset($panel_history['totalSaved']) ? (int) $panel_history['totalSaved'] : 0,
			);
		} else {
			$results['latestPrompts'] = array();
			$results['latestPromptsHtml'] = '';
			$results['promptHistory'] = array(
				'itemsHtml'   => '',
				'totalRecent' => 0,
				'totalSaved'  => 0,
			);
		}

		$this->logApplyPromptMetadataSession( $prompt_metadata );

		return $results;
	}

	/**
	 * Sanitize Prompt Tester API request for logging/display (strip CURLFile uploads).
	 *
	 * @param mixed $request Last API request array.
	 * @return mixed
	 */
	private function sanitizePromptTesterLogRequest( $request ) {
		if ( ! is_array( $request ) ) {
			return $request;
		}

		$sanitized = $request;
		foreach ( array( 'image_generation', 'image_edit' ) as $bucket ) {
			if ( ! isset( $sanitized[ $bucket ] ) || ! is_array( $sanitized[ $bucket ] ) ) {
				continue;
			}
			foreach ( array( 'image', 'mask' ) as $file_key ) {
				if ( isset( $sanitized[ $bucket ][ $file_key ] ) ) {
					$sanitized[ $bucket ][ $file_key ] = '[file upload: ' . $file_key . ']';
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Make Prompt Tester request JSON safe for display (strip CURLFile uploads).
	 *
	 * @param mixed $request Last API request array.
	 * @return string
	 */
	private function formatPromptTesterRequestJson( $request ) {
		$sanitized = $this->sanitizePromptTesterLogRequest( $request );
		if ( ! is_array( $sanitized ) ) {
			return '';
		}

		return wp_json_encode( $sanitized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE );
	}

	/**
	 * Persist Prompt Tester request/response to the logs table.
	 *
	 * @param string              $prompt          Prompt text shown in the log title column.
	 * @param string|array        $cell_value      Source/context value (e.g. source image URL).
	 * @param string              $response_action tester_text|tester_create_image|tester_edit_image.
	 * @param string|array        $response_data   Result payload for the response value column.
	 * @param string|array        $metadata        Optional metadata.
	 * @return void
	 */
	private function persistPromptTesterRequestLog( $prompt, $cell_value, $response_action, $response_data = '', $metadata = array() ) {
		if ( ! class_exists( 'SheetsPilot_RequestLog', false ) ) {
			return;
		}

		$raw_request  = null;
		$raw_response = null;

		if ( class_exists( 'SheetsPilot_UseChatGPT', false ) ) {
			$info         = SheetsPilot_UseChatGPT::getLastRequestResponse();
			$raw_request  = $this->sanitizePromptTesterLogRequest( SheetsPilotFunctions::getVal( $info, 'request' ) );
			$raw_response = SheetsPilotFunctions::getVal( $info, 'response' );
			if ( is_string( $raw_response ) && $raw_response !== '' ) {
				$raw_response = $this->formatPromptTesterResponseJson( $raw_response );
			}
		}

		SheetsPilot_RequestLog::insert(
			$prompt,
			$cell_value,
			$raw_request,
			$raw_response,
			$response_action,
			$response_data,
			$metadata
		);
	}

	/**
	 * Log a failed Prompt Tester AJAX call when the handler throws before success logging.
	 *
	 * @param string $client_action prompt_tester_run|prompt_tester_image_run.
	 * @param string $error_message Exception message.
	 * @return void
	 */
	private function logPromptTesterFailure( $client_action, $error_message ) {
		if ( class_exists( 'SheetsPilot_RequestLog', false ) ) {
			SheetsPilot_RequestLog::endRequestTimer();
		}

		$raw_payload = SheetsPilotFunctions::getPostGetVariable( 'data', '', SheetsPilotFunctions::SANITIZE_NOTHING );
		if ( false === $raw_payload || ! is_string( $raw_payload ) || $raw_payload === '' ) {
			return;
		}

		$arr = json_decode( $raw_payload, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $arr ) ) {
			return;
		}

		$prompt = isset( $arr['prompt'] ) ? sanitize_textarea_field( (string) $arr['prompt'] ) : '';
		if ( $prompt === '' && isset( $arr['user_message'] ) ) {
			$prompt = sanitize_textarea_field( (string) $arr['user_message'] );
		}
		if ( $prompt === '' ) {
			$prompt = __( 'Prompt Tester request', 'sheetspilot' );
		}

		$log_action = 'tester_text';
		$cell_value = '';
		$metadata   = array( 'source' => 'prompt_tester' );

		if ( $client_action === 'prompt_tester_image_run' ) {
			$mode = isset( $arr['mode'] ) ? sanitize_key( (string) $arr['mode'] ) : 'generate';
			$log_action = ( $mode === 'edit' ) ? 'tester_edit_image' : 'tester_create_image';
			$metadata['mode'] = $mode;

			if ( $mode === 'edit' ) {
				$attachment_id = isset( $arr['attachment_id'] ) ? absint( $arr['attachment_id'] ) : 0;
				$image_url     = isset( $arr['image_url'] ) ? esc_url_raw( (string) $arr['image_url'] ) : '';
				if ( $attachment_id > 0 ) {
					$attachment_url = wp_get_attachment_url( $attachment_id );
					if ( is_string( $attachment_url ) && $attachment_url !== '' ) {
						$image_url = $attachment_url;
					}
				}
				$cell_value = $image_url;
				$metadata['attachment_id'] = $attachment_id;
			} else {
				$cell_value = array(
					'aspect_ratio' => isset( $arr['aspect_ratio'] ) ? sanitize_text_field( (string) $arr['aspect_ratio'] ) : 'auto',
					'quality'      => isset( $arr['quality'] ) ? sanitize_text_field( (string) $arr['quality'] ) : 'default',
					'format'       => isset( $arr['format'] ) ? sanitize_text_field( (string) $arr['format'] ) : 'default',
				);
			}
		} else {
			$metadata['model'] = isset( $arr['model'] ) ? sanitize_text_field( (string) $arr['model'] ) : '';
			$metadata['tool']  = isset( $arr['tool'] ) ? sanitize_key( (string) $arr['tool'] ) : '';
			if ( isset( $arr['system_message'] ) && trim( (string) $arr['system_message'] ) !== '' ) {
				$metadata['system_message'] = sanitize_textarea_field( (string) $arr['system_message'] );
			}
		}

		$this->persistPromptTesterRequestLog( $prompt, $cell_value, $log_action, (string) $error_message, $metadata );
	}

	/**
	 * Format Prompt Tester API response JSON for display (truncate huge base64 payloads).
	 *
	 * @param mixed $resp_raw Raw API response string.
	 * @return string
	 */
	private function formatPromptTesterResponseJson( $resp_raw ) {
		if ( ! is_string( $resp_raw ) || $resp_raw === '' ) {
			return '';
		}

		$decoded = json_decode( $resp_raw, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
			return $resp_raw;
		}

		if ( isset( $decoded['data'] ) && is_array( $decoded['data'] ) ) {
			foreach ( $decoded['data'] as $idx => $item ) {
				if ( ! is_array( $item ) || ! isset( $item['b64_json'] ) || ! is_string( $item['b64_json'] ) ) {
					continue;
				}
				$decoded['data'][ $idx ]['b64_json'] = '[base64 image data, ' . strlen( $item['b64_json'] ) . ' bytes]';
			}
		}

		return wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE );
	}

	/**
	 * Persist a Prompt Tester data-URL result as a small HTTP preview URL.
	 *
	 * @param string $image_result Image URL or data URL from the API handler.
	 * @return string Preview URL safe to return in AJAX JSON.
	 */
	private function resolvePromptTesterImagePreviewUrl( $image_result ) {
		if ( ! is_string( $image_result ) || $image_result === '' ) {
			return '';
		}
		if ( strpos( $image_result, 'data:' ) !== 0 ) {
			return $image_result;
		}
		if ( ! class_exists( 'SheetsPilot_ImageProcessing', false ) ) {
			return '';
		}
		if ( ! preg_match( '#^data:image/(\w+);base64,(.+)$#s', $image_result, $matches ) ) {
			return '';
		}

		$data = base64_decode( $matches[2], true );
		if ( $data === false || $data === '' ) {
			return '';
		}

		$ext = strtolower( (string) $matches[1] );
		if ( $ext === 'jpeg' ) {
			$ext = 'jpg';
		}
		if ( ! in_array( $ext, array( 'png', 'jpg', 'webp' ), true ) ) {
			$ext = 'png';
		}

		$dir      = SheetsPilot_ImageProcessing::getPendingDir();
		$filename = 'pt_preview_' . wp_generate_password( 12, false ) . '.' . $ext;
		$filepath = $dir . '/' . $filename;
		if ( file_put_contents( $filepath, $data ) === false ) {
			return '';
		}

		return SheetsPilot_ImageProcessing::getPendingUrl() . $filename;
	}

	/**
	 * AJAX: run OpenAI image generate/edit for the Prompt Tester (Pro + embedded pro module only).
	 */
	private function promptTesterImageRunAjax() {
		if ( SheetsPilotGlobals::$isPro !== true ) {
			SheetsPilotFunctions::throwError( __( 'Prompt Tester is available in SheetsPilot Pro only.', 'sheetspilot' ) );
		}
		if ( ! class_exists( 'SheetsPilot_UseChatGPT', false ) ) {
			SheetsPilotFunctions::throwError( __( 'Pro AI module is not loaded.', 'sheetspilot' ) );
		}

		$raw_payload = SheetsPilotFunctions::getPostGetVariable( 'data', '', SheetsPilotFunctions::SANITIZE_NOTHING );
		if ( false === $raw_payload || ! is_string( $raw_payload ) || $raw_payload === '' ) {
			SheetsPilotFunctions::throwError( __( 'Missing request payload.', 'sheetspilot' ) );
		}

		$arr = json_decode( $raw_payload, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $arr ) ) {
			SheetsPilotFunctions::throwError( __( 'Invalid JSON payload.', 'sheetspilot' ) );
		}

		$mode   = isset( $arr['mode'] ) ? sanitize_key( (string) $arr['mode'] ) : '';
		$prompt = isset( $arr['prompt'] ) ? sanitize_textarea_field( (string) $arr['prompt'] ) : '';

		if ( ! in_array( $mode, array( 'generate', 'edit' ), true ) ) {
			SheetsPilotFunctions::throwError( __( 'Invalid image request mode.', 'sheetspilot' ) );
		}
		if ( $prompt === '' ) {
			SheetsPilotFunctions::throwError( __( 'Enter an image prompt.', 'sheetspilot' ) );
		}

		if ( function_exists( 'ignore_user_abort' ) ) {
			ignore_user_abort( true );
		}

		if ( class_exists( 'SheetsPilot_RequestLog', false ) ) {
			SheetsPilot_RequestLog::startRequestTimer();
		}

		$gpt          = new SheetsPilot_UseChatGPT();
		$image_result = '';
		$image_url    = '';
		$attachment_id = 0;
		$log_cell_value = '';
		$log_metadata = array(
			'source' => 'prompt_tester',
			'mode'   => $mode,
		);
		$log_action = ( $mode === 'edit' ) ? 'tester_edit_image' : 'tester_create_image';

		if ( $mode === 'generate' ) {
			$aspect_ratio = isset( $arr['aspect_ratio'] ) ? sanitize_text_field( (string) $arr['aspect_ratio'] ) : 'auto';
			$quality      = isset( $arr['quality'] ) ? sanitize_text_field( (string) $arr['quality'] ) : 'default';
			$format       = isset( $arr['format'] ) ? sanitize_text_field( (string) $arr['format'] ) : 'default';

			$allowed_ratios = class_exists( 'SheetsPilot_ImageProcessing' )
				? SheetsPilot_ImageProcessing::getAllowedAspectRatios()
				: array( 'auto', '1:1', '3:2', '2:3' );
			if ( ! in_array( $aspect_ratio, $allowed_ratios, true ) ) {
				$aspect_ratio = 'auto';
			}

			$size = null;
			if ( $aspect_ratio === 'auto' ) {
				$size = 'auto';
			} elseif ( class_exists( 'SheetsPilot_ImageProcessing' ) ) {
				$ratio_map = SheetsPilot_ImageProcessing::getAspectRatioToSizeMap();
				$size      = isset( $ratio_map[ $aspect_ratio ] ) ? $ratio_map[ $aspect_ratio ] : null;
			}

			$log_cell_value = array(
				'aspect_ratio' => $aspect_ratio,
				'quality'      => $quality,
				'format'       => $format,
			);
			$log_metadata['aspect_ratio'] = $aspect_ratio;
			$log_metadata['quality']      = $quality;
			$log_metadata['format']       = $format;
			$image_result = $gpt->makeImageGenerationCall( $prompt, $size, $quality, $format );
		} else {
			$attachment_id = isset( $arr['attachment_id'] ) ? absint( $arr['attachment_id'] ) : 0;
			$image_url     = isset( $arr['image_url'] ) ? esc_url_raw( (string) $arr['image_url'] ) : '';

			if ( $attachment_id > 0 ) {
				$attachment_url = wp_get_attachment_url( $attachment_id );
				if ( is_string( $attachment_url ) && $attachment_url !== '' ) {
					$image_url = $attachment_url;
				}
			}

			if ( $image_url === '' ) {
				SheetsPilotFunctions::throwError( __( 'Select an image from the Media Library.', 'sheetspilot' ) );
			}

			$log_cell_value = $image_url;
			if ( $attachment_id > 0 ) {
				$log_metadata['attachment_id'] = $attachment_id;
			}

			$image_result = $gpt->makeImageEditCall( $image_url, $prompt, null, $attachment_id );
		}

		if ( class_exists( 'SheetsPilot_RequestLog', false ) ) {
			SheetsPilot_RequestLog::endRequestTimer();
		}

		$info       = SheetsPilot_UseChatGPT::getLastRequestResponse();
		$req        = SheetsPilotFunctions::getVal( $info, 'request' );
		$resp_raw   = SheetsPilotFunctions::getVal( $info, 'response' );

		if ( $mode === 'edit' && is_array( $req ) && isset( $req['image_edit'] ) && is_array( $req['image_edit'] ) ) {
			$req['image_edit']['source_image_url'] = $image_url;
			if ( isset( $arr['attachment_id'] ) && absint( $arr['attachment_id'] ) > 0 ) {
				$req['image_edit']['attachment_id'] = absint( $arr['attachment_id'] );
			}
		}

		$req_pretty = $this->formatPromptTesterRequestJson( $req );
		$resp_pretty = $this->formatPromptTesterResponseJson( (string) $resp_raw );

		$this->consumeBufferedOutput();

		$image_preview_url = $this->resolvePromptTesterImagePreviewUrl( $image_result );
		$log_response_data = $image_preview_url !== ''
			? array( 'image_url' => $image_preview_url )
			: $resp_pretty;

		$this->persistPromptTesterRequestLog(
			$prompt,
			$log_cell_value,
			$log_action,
			$log_response_data,
			$log_metadata
		);

		SheetsPilotHelper::ajaxResponseSuccess(
			__( 'Image request completed.', 'sheetspilot' ),
			array(
				'request_json'  => $req_pretty,
				'response_body' => $resp_pretty,
				'response_json' => $resp_pretty,
				'image_url'     => $image_preview_url,
			)
		);
	}

	/**
	 * AJAX: compress a media-library image for the Prompt Tester Compress Image tab.
	 */
	private function promptTesterCompressImageAjax() {
		if ( SheetsPilotGlobals::$isPro !== true ) {
			SheetsPilotFunctions::throwError( __( 'Prompt Tester is available in SheetsPilot Pro only.', 'sheetspilot' ) );
		}

		$raw_payload = SheetsPilotFunctions::getPostGetVariable( 'data', '', SheetsPilotFunctions::SANITIZE_NOTHING );
		if ( false === $raw_payload || ! is_string( $raw_payload ) || $raw_payload === '' ) {
			SheetsPilotFunctions::throwError( __( 'Missing request payload.', 'sheetspilot' ) );
		}

		$arr = json_decode( $raw_payload, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $arr ) ) {
			SheetsPilotFunctions::throwError( __( 'Invalid JSON payload.', 'sheetspilot' ) );
		}

		$attachment_id = isset( $arr['attachment_id'] ) ? absint( $arr['attachment_id'] ) : 0;
		if ( $attachment_id <= 0 ) {
			SheetsPilotFunctions::throwError( __( 'Please select an image from the Media Library.', 'sheetspilot' ) );
		}

		$preferred_engine = isset( $arr['preferred_engine'] ) ? sanitize_key( (string) $arr['preferred_engine'] ) : 'auto';

		if ( ! class_exists( 'SheetsPilot_ImageProcessing', false ) ) {
			SheetsPilotFunctions::throwError( __( 'Image processing module is not loaded.', 'sheetspilot' ) );
		}

		$result = SheetsPilot_ImageProcessing::compressAttachmentImageForPromptTester( $attachment_id, $preferred_engine );

		$request_json = wp_json_encode(
			array(
				'attachment_id'    => $attachment_id,
				'preferred_engine' => $preferred_engine,
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
		);
		$response_json = wp_json_encode(
			$result,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
		);

		$this->consumeBufferedOutput();

		SheetsPilotHelper::ajaxResponseSuccess(
			__( 'Image compressed.', 'sheetspilot' ),
			array(
				'request_json'  => $request_json,
				'response_json' => $response_json,
				'before_url'    => isset( $result['before_url'] ) ? (string) $result['before_url'] : '',
				'after_url'     => isset( $result['after_url'] ) ? (string) $result['after_url'] : '',
				'size_before'   => isset( $result['size_before'] ) ? (int) $result['size_before'] : 0,
				'size_after'    => isset( $result['size_after'] ) ? (int) $result['size_after'] : 0,
				'size_saved'    => isset( $result['size_saved'] ) ? (int) $result['size_saved'] : 0,
				'engine'        => isset( $result['engine'] ) ? (string) $result['engine'] : '',
				'preferred_engine' => isset( $result['preferred_engine'] ) ? (string) $result['preferred_engine'] : 'auto',
			)
		);
	}

	/**
	 * AJAX: run OpenAI Chat Completions for the Prompt Tester (Pro + embedded pro module only).
	 */
	private function promptTesterRunAjax()
	{

		if (SheetsPilotGlobals::$isPro !== true) {
			SheetsPilotFunctions::throwError(__('Prompt Tester is available in SheetsPilot Pro only.', 'sheetspilot'));
		}
		if (! class_exists('SheetsPilot_UseChatGPT', false)) {
			SheetsPilotFunctions::throwError(__('Pro AI module is not loaded.', 'sheetspilot'));
		}

		$raw_payload = SheetsPilotFunctions::getPostGetVariable( 'data', '', SheetsPilotFunctions::SANITIZE_NOTHING );
		if ( false === $raw_payload || ! is_string( $raw_payload ) || $raw_payload === '' ) {
			SheetsPilotFunctions::throwError(__('Missing request payload.', 'sheetspilot'));
		}

		$arr = json_decode($raw_payload, true);
		if (json_last_error() !== JSON_ERROR_NONE || ! is_array($arr)) {
			SheetsPilotFunctions::throwError(__('Invalid JSON payload.', 'sheetspilot'));
		}

		$user_msg   = isset($arr['user_message']) ? sanitize_textarea_field((string) $arr['user_message']) : '';
		$system_msg = isset($arr['system_message']) ? sanitize_textarea_field((string) $arr['system_message']) : '';
		$model_in   = isset($arr['model']) ? sanitize_text_field((string) $arr['model']) : '';
		$tool_in    = isset($arr['tool']) ? sanitize_key((string) $arr['tool']) : '';
		$allowed_tools = array('web_search', 'file_search', 'code_interpreter', 'computer_use');
		$selected_tool = in_array($tool_in, $allowed_tools, true) ? $tool_in : '';

		if ($user_msg === '') {
			SheetsPilotFunctions::throwError(__('Enter a user message.', 'sheetspilot'));
		}

		if (function_exists('ignore_user_abort')) {
			ignore_user_abort(true);
		}

		if ( class_exists( 'SheetsPilot_RequestLog', false ) ) {
			SheetsPilot_RequestLog::startRequestTimer();
		}

		$gpt = new SheetsPilot_UseChatGPT();
		$gpt->makePromptTesterChatCall($user_msg, $system_msg, $model_in, $selected_tool);

		if ( class_exists( 'SheetsPilot_RequestLog', false ) ) {
			SheetsPilot_RequestLog::endRequestTimer();
		}

		$info       = SheetsPilot_UseChatGPT::getLastRequestResponse();
		$req        = SheetsPilotFunctions::getVal($info, 'request');
		$resp_raw   = SheetsPilotFunctions::getVal($info, 'response');
		$req_pretty = is_array($req)
			? wp_json_encode($req, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)
			: '';

		$resp_pretty = $this->formatPromptTesterResponseJson( (string) $resp_raw );

		$log_metadata = array(
			'source' => 'prompt_tester',
			'model'  => $model_in,
			'tool'   => $selected_tool,
		);
		if ( $system_msg !== '' ) {
			$log_metadata['system_message'] = $system_msg;
		}

		$this->persistPromptTesterRequestLog(
			$user_msg,
			'',
			'tester_text',
			$resp_pretty,
			$log_metadata
		);

		$this->consumeBufferedOutput();

		SheetsPilotHelper::ajaxResponseSuccess(
			__('Request completed.', 'sheetspilot'),
			array(
				'request_json' => $req_pretty,
				'response_body' => (string) $resp_raw,
				'response_json' => $resp_pretty,
			)
		);
	}


	/**
	 * on ajax action
	 */
	public function onAjaxActions()
	{

		add_filter("wp_php_error_message", array("SheetsPilotHelper", "onPHPErrorMessage"), 100, 2);


		$actionType = SheetsPilotFunctions::getPostGetVariable("action", "", SheetsPilotFunctions::SANITIZE_KEY);

		if ($actionType != 'sheetspilot' . "_ajax_actions")
			return (false);

		$action = SheetsPilotFunctions::getPostGetVariable("client_action", "", SheetsPilotFunctions::SANITIZE_KEY);
		SheetsPilotHelper::applyShowTraceFromAjaxRequest();
		SheetsPilotHelper::registerAjaxInlineErrorTraceHandler();
		$this->registerAjaxFatalShutdownHandler($action);
		ob_start();

		if ($action === 'prompt_tester_run') {
			try {
			
				$nonce = SheetsPilotFunctions::getPostGetVariable("nonce", "", SheetsPilotFunctions::SANITIZE_NOTHING);
				SheetsPilotHelper::verifyNonce($nonce);
				$this->promptTesterRunAjax();
			} catch (Throwable $e) {
				$this->logPromptTesterFailure( 'prompt_tester_run', $e->getMessage() );
				$bufferedOutput = $this->consumeBufferedOutput();
				$this->onException($e, '', $bufferedOutput);
			}
			return;
		}

		if ($action === 'prompt_tester_image_run') {
			try {
				$nonce = SheetsPilotFunctions::getPostGetVariable("nonce", "", SheetsPilotFunctions::SANITIZE_NOTHING);
				SheetsPilotHelper::verifyNonce($nonce);
				$this->promptTesterImageRunAjax();
			} catch (Throwable $e) {
				$this->logPromptTesterFailure( 'prompt_tester_image_run', $e->getMessage() );
				$bufferedOutput = $this->consumeBufferedOutput();
				$this->onException($e, '', $bufferedOutput);
			}
			return;
		}

		if ($action === 'prompt_tester_compress_image') {
			try {
				$nonce = SheetsPilotFunctions::getPostGetVariable("nonce", "", SheetsPilotFunctions::SANITIZE_NOTHING);
				SheetsPilotHelper::verifyNonce($nonce);
				$this->promptTesterCompressImageAjax();
			} catch (Throwable $e) {
				$this->logPromptTesterFailure( 'prompt_tester_compress_image', $e->getMessage() );
				$bufferedOutput = $this->consumeBufferedOutput();
				$this->onException($e, '', $bufferedOutput);
			}
			return;
		}



		try {

			$nonce = SheetsPilotFunctions::getPostGetVariable("nonce", "", SheetsPilotFunctions::SANITIZE_NOTHING);
			SheetsPilotHelper::verifyNonce($nonce);

			$data = $this->getDataFromRequest();


			switch ($action) {

				case "generate_all_variations":
					SheetsPilotCellEditor::generateAllProductVariations($data);
					$result = SheetsPilotCellEditor::getPostMultidata($data);
					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess($result);
					break;
				case "manual_add_variation":
					SheetsPilotCellEditor::addSingleVariation($data);
					$result = SheetsPilotCellEditor::getPostMultidata($data);
					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess($result);
					break;
				case "search_products_action":
					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = 'product';
					$postQueryObj->search_string = $data['search_query'];

					$table_data_content = $postQueryObj->getPostTypeArray();

					$posts_per_page = -1;

					$output = array(
						"postslist" => $table_data_content,

					);
					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess($output);
					break;
				case "get_post_multidata":
					$result = SheetsPilotCellEditor::getPostMultidata($data);
					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess($result, $urlRedirect);
					break;
				case "save_post_multidata":

					SheetsPilotCellEditor::savePostMultidata($data);
					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess([], $urlRedirect);
					break;
				case "bulk_action":
					$result = SheetsPilotCellEditor::saveEditedPostsDataBulk($data);
					if ($result['save_result']) {
						$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
						SheetsPilotHelper::ajaxResponseSuccess(__("Posts Saved...", 'sheetspilot'), $urlRedirect);
					} else {
						SheetsPilotHelper::ajaxResponseError($result);
					}
					break;

				case "update_post_row":
					$new_row_data = SheetsPilotCellEditor::getTableEditedRow($data);
					SheetsPilotHelper::ajaxResponseSuccess($new_row_data);
					break;
				case "copy_acf_content":
					SheetsPilotCellEditor::duplicateACFFieldContent($data);
					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess([], $urlRedirect);
					break;
				case "copy_product_attribute":
					$attr_counter = SheetsPilotCellEditor::copyProductAttributes((int)$data['source_id'], (int)$data['target_id']);
					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess(['count' => $attr_counter], $urlRedirect);
					break;
				case "make_ajax_search":

					$current_page = 1;
					$offset = false;

					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = $data['post_type'];
					$postQueryObj->offset = $offset;
					$postQueryObj->search_string = $data['search_query'];

					$table_data_content = $postQueryObj->getPostTypeArray();
					$table_data_structure = SheetsPilotCellEditor::getPostTypeStructure($data['post_type']);

					$posts_per_page = (int)$data['rows_per_page'];
					// Updated Count Logic
					$total_count = $postQueryObj->getTotoalPostCount();

					$pages_number = intval(($total_count / $posts_per_page));
					if ($total_count % $posts_per_page > 0) {
						$pages_number++;
					}

					$output = array(
						"postslist" => $table_data_content,
						"table_structure" => $table_data_structure,
						"total_count" => $total_count,
						"total_pages" => $pages_number,
						"current_page" => $current_page,
						'post_from' => ($offset * $posts_per_page) + 1,
						'post_to' => $offset * $posts_per_page + count($table_data_content)
					);

					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess($output, $urlRedirect);
					break;
				case "make_column_ajax_search":


					$current_page = 1;
					$offset = false;

					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = $data['post_type'];
					$postQueryObj->offset = $offset;
					if (is_array($data['col_filtering_query'])) {
						$postQueryObj->column_query = $data['col_filtering_query'];
					} else {
						$postQueryObj->column_query = [];
					}


					$table_data_content = $postQueryObj->getPostTypeArray();
					$table_data_structure = SheetsPilotCellEditor::getPostTypeStructure($data['post_type']);

					$posts_per_page = (int)$data['rows_per_page'];
					// Updated Count Logic
					$total_count = $postQueryObj->getTotoalPostCount();

					$pages_number = intval(($total_count / $posts_per_page));
					if ($total_count % $posts_per_page > 0) {
						$pages_number++;
					}

					$count_posts = count(get_posts(
						[
							'post_type' => $data['post_type'],
							'showposts' => -1,
							'post_status' => 'any',
							'fields' => 'ids'
						]
					));

					$output = array(
						"global_posts_number" => $count_posts,
						"postslist" => $table_data_content,
						"table_structure" => $table_data_structure,
						"total_count" => $total_count,
						"total_pages" => $pages_number,
						"current_page" => $current_page,
						'post_from' => ($offset * $posts_per_page) + 1,
						'post_to' => $offset * $posts_per_page + count($table_data_content)
					);

					

					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess($output, $urlRedirect);
					break;
				case "make_column_filter_filtering":

					$current_page = 1;
					$offset = false;

					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = $data['post_type'];
					$postQueryObj->offset = $offset;


					$postQueryObj->column_name = $data['column_name'];
					$postQueryObj->column_type = $data['type'];
					$postQueryObj->filtering_values = $data['filtering_values'];

					$table_data_content = $postQueryObj->getPostTypeArray();
					$table_data_structure = SheetsPilotCellEditor::getPostTypeStructure($data['post_type']);

					$posts_per_page = (int)$data['rows_per_page'];
					// Updated Count Logic
					$total_count = $postQueryObj->getTotoalPostCount();

					$pages_number = intval(($total_count / $posts_per_page));
					if ($total_count % $posts_per_page > 0) {
						$pages_number++;
					}

					$output = array(
						"postslist" => $table_data_content,
						"table_structure" => $table_data_structure,
						"total_count" => $total_count,
						"total_pages" => $pages_number,
						"current_page" => $current_page,
						'post_from' => ($offset * $posts_per_page) + 1,
						'post_to' => $offset * $posts_per_page + count($table_data_content)
					);




					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess($output, $urlRedirect);
					break;
				case "make_column_sorting":

					$current_page = 1;
					$offset = false;

					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = $data['post_type'];
					$postQueryObj->offset = $offset;
					$postQueryObj->search_string = $data['search_query'];
					$postQueryObj->orderby = $data['order_by'];
					$postQueryObj->order = $data['order'];

					$table_data_content = $postQueryObj->getPostTypeArray();
					$table_data_structure = SheetsPilotCellEditor::getPostTypeStructure($data['post_type']);

					$posts_per_page = (int)$data['rows_per_page'];
					// Updated Count Logic
					$total_count = $postQueryObj->getTotoalPostCount();

					$pages_number = intval(($total_count / $posts_per_page));
					if ($total_count % $posts_per_page > 0) {
						$pages_number++;
					}

					$output = array(
						"postslist" => $table_data_content,
						"table_structure" => $table_data_structure,
						"total_count" => $total_count,
						"total_pages" => $pages_number,
						"current_page" => $current_page,
						'post_from' => ($offset * $posts_per_page) + 1,
						'post_to' => $offset * $posts_per_page + count($table_data_content)
					);

					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess($output, $urlRedirect);
					break;
				case "pagination_button_click":

					if ($data['direction'] == 'next') {
						$offset = (int)$data['current_page'];
						$current_page = (int)$data['current_page'] + 1;
					} else {
						$offset = (int)$data['current_page'] - 2;
						$current_page = (int)$data['current_page'] - 1;
					}

					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = $data['post_type'];
					$postQueryObj->offset = $offset;


					$table_data_content = $postQueryObj->getPostTypeArray();
					$table_data_structure = SheetsPilotCellEditor::getPostTypeStructure($data['post_type']);


					$posts_per_page = (int)$data['rows_per_page'];
					// Updated Count Logic
					$total_count = $postQueryObj->getTotoalPostCount();

					$pages_number = intval(($total_count / $posts_per_page));
					if ($total_count % $posts_per_page > 0) {
						$pages_number++;
					}

					if ($data['direction'] == 'next') {
						$output = array(
							"postslist" => $table_data_content,
							"table_structure" => $table_data_structure,
							"current_page" => $current_page,
							"total_count" => $total_count,
							'post_from' => ($offset * $posts_per_page) + 1,
							'post_to' => $offset * $posts_per_page + count($table_data_content)

						);
					} else {

						$output = array(
							"postslist" => $table_data_content,
							"table_structure" => $table_data_structure,
							"current_page" => $current_page,
							"total_count" => $total_count,
							'post_from' => ($current_page - 1) * $posts_per_page + 1,
							'post_to' => ($current_page - 1) * $posts_per_page  + $posts_per_page

						);
					}
					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess($output, $urlRedirect);
					break;
				case "save_rows_page_number":
					SheetsPilotHelper::saveEditorPageSettings($data, 'rows_per_page');

					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = $data['post_type'];


					$table_data_content = $postQueryObj->getPostTypeArray();
					$table_data_structure = SheetsPilotCellEditor::getPostTypeStructure($data['post_type']);
					$posts_per_page = (int)$data['rows_per_page'];
					// Updated Count Logic
					$count_posts = wp_count_posts($data['post_type']);
					$total_count = $postQueryObj->getTotoalPostCount();

					$pages_number = intval(($total_count / $posts_per_page));
					if ($total_count % $posts_per_page > 0) {
						$pages_number++;
					}

					$output = array(
						"postslist" => $table_data_content,
						"total_count" => $total_count,
						"posts_per_page" => $posts_per_page,
						"pages_number" => $pages_number,
						"table_structure" => $table_data_structure,

					);

					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess($output, $urlRedirect);
					break;
				case "remove_table_post":
					SheetsPilotCellEditor::removeTablePost($data);

					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = $data['post_type'];

					$table_data_content = $postQueryObj->getPostTypeArray();
					$table_data_structure = SheetsPilotCellEditor::getPostTypeStructure($data['post_type']);
					$posts_per_page = SheetsPilotHelper::getEditorPageSettings('rows_per_page');
					if (!$posts_per_page || $posts_per_page == '') {
						$posts_per_page = 10;
					}
					// Updated Count Logic

					$total_count = $postQueryObj->getTotoalPostCount();

					$pages_number = intval(($total_count / $posts_per_page));
					if ($total_count % $posts_per_page > 0) {
						$pages_number++;
					}

					$output = array(
						"postslist" => $table_data_content,
						"total_count" => $total_count,
						"posts_per_page" => $posts_per_page,
						"pages_number" => $pages_number,
						"table_structure" => $table_data_structure,
					);

					SheetsPilotHelper::ajaxResponseSuccess($output);
					break;
				case "remove_single_variation":
					SheetsPilotCellEditor::removeTablePost($data);

					SheetsPilotHelper::ajaxResponseSuccess([]);
					break;
				/*
				case "get_repeater_content":			
					$repeater_cell_content = SheetsPilotCellEditor::acf_repeater_get_items( substr( $data['field_name'], 4 ), $data['post_id']);	
					SheetsPilotHelper::ajaxResponseSuccess( $repeater_cell_content );					
				break;
				*/
				case "duplicate_table_post":
					$new_row_data = SheetsPilotCellEditor::getDuplicatedTableRow($data);

					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = $data['post_type'];

					$table_data_content = $postQueryObj->getPostTypeArray();
					$table_data_structure = SheetsPilotCellEditor::getPostTypeStructure($data['post_type']);
					$posts_per_page = SheetsPilotHelper::getEditorPageSettings('rows_per_page');
					if (!$posts_per_page || $posts_per_page == '') {
						$posts_per_page = 10;
					}
					// Updated Count Logic

					$total_count = $postQueryObj->getTotoalPostCount();

					$pages_number = intval(($total_count / $posts_per_page));
					if ($total_count % $posts_per_page > 0) {
						$pages_number++;
					}

					$output = array(
						"postslist" => $table_data_content,
						"total_count" => $total_count,
						"posts_per_page" => $posts_per_page,
						"pages_number" => $pages_number,
						"table_structure" => $table_data_structure,

					);


					SheetsPilotHelper::ajaxResponseSuccess($output);
					break;
				case "get_restored_post":
					/*
					$new_row_data = SheetsPilotCellEditor::restoreTablePosts($data);	
					SheetsPilotHelper::ajaxResponseSuccess( $new_row_data );
					*/
					############
					SheetsPilotCellEditor::restoreTablePosts($data);

					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = $data['post_type'];
					$postQueryObj->orderby = 'ID';

					$table_data_content = $postQueryObj->getPostTypeArray();
					$table_data_structure = SheetsPilotCellEditor::getPostTypeStructure($data['post_type']);
					$posts_per_page = SheetsPilotHelper::getEditorPageSettings('rows_per_page');
					if (!$posts_per_page || $posts_per_page == '') {
						$posts_per_page = 10;
					}
					// Updated Count Logic

					$total_count = $postQueryObj->getTotoalPostCount();

					$pages_number = intval(($total_count / $posts_per_page));
					if ($total_count % $posts_per_page > 0) {
						$pages_number++;
					}

					$output = array(
						"postslist" => $table_data_content,
						"total_count" => $total_count,
						"posts_per_page" => $posts_per_page,
						"pages_number" => $pages_number,
						"table_structure" => $table_data_structure,

					);

					SheetsPilotHelper::ajaxResponseSuccess($output);
					break;
				case "add_new_table_row":
					$empty_row_data = SheetsPilotCellEditor::getEmptyTableRow($data);

					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = $data['post_type'];
					$postQueryObj->orderby = 'ID';

					$table_data_content = $postQueryObj->getPostTypeArray();
					$table_data_structure = SheetsPilotCellEditor::getPostTypeStructure($data['post_type']);
					$posts_per_page = SheetsPilotHelper::getEditorPageSettings('rows_per_page');
					if (!$posts_per_page || $posts_per_page == '') {
						$posts_per_page = 10;
					}
					// Updated Count Logic

					$total_count = $postQueryObj->getTotoalPostCount();

					$pages_number = intval(($total_count / $posts_per_page));
					if ($total_count % $posts_per_page > 0) {
						$pages_number++;
					}

					$output = array(
						"postslist" => $table_data_content,
						"total_count" => $total_count,
						"posts_per_page" => $posts_per_page,
						"pages_number" => $pages_number,
						"table_structure" => $table_data_structure,

					);

					SheetsPilotHelper::ajaxResponseSuccess($output);
					break;
				case "paste_image_from_clipboard":

					$image_data = SheetsPilotCellEditor::createImageFromClipboard($data);
					SheetsPilotHelper::ajaxResponseSuccess($image_data);

					break;
				case "generate_posts_by_title":
					$empty_row_data = SheetsPilotCellEditor::generatePostsByTitles($data);

					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = $data['post_type'];
					$postQueryObj->orderby = 'ID';

					$table_data_content = $postQueryObj->getPostTypeArray();
					$table_data_structure = SheetsPilotCellEditor::getPostTypeStructure($data['post_type']);
					$posts_per_page = SheetsPilotHelper::getEditorPageSettings('rows_per_page');
					if (!$posts_per_page || $posts_per_page == '') {
						$posts_per_page = 10;
					}
					// Updated Count Logic

					$total_count = $postQueryObj->getTotoalPostCount();

					$pages_number = intval(($total_count / $posts_per_page));
					if ($total_count % $posts_per_page > 0) {
						$pages_number++;
					}

					$output = array(
						"postslist" => $table_data_content,
						"total_count" => $total_count,
						"posts_per_page" => $posts_per_page,
						"pages_number" => $pages_number,
						"table_structure" => $table_data_structure,

					);

					SheetsPilotHelper::ajaxResponseSuccess($output);
					break;
				case "drop_repeater_content":
					$save_result = SheetsPilotCellEditor::dropRepeaterContent($data);
					SheetsPilotHelper::ajaxResponseSuccess($save_result);
					break;
				case "save_wyswyg_content":
					$save_result = SheetsPilotCellEditor::saveWyswygContainer($data);
					SheetsPilotHelper::ajaxResponseSuccess($save_result);
					break;
				case "get_wyswyg_content":
					$editor_content = SheetsPilotCellEditor::getWyswygContainer($data);
					SheetsPilotHelper::ajaxResponseSuccess($editor_content);
					break;
				case "save_general_settings":
					SheetsPilotHelper::saveGeneralSettingsFromData($data);
					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_SETTINGS);
					SheetsPilotHelper::ajaxResponseSuccess(__("Settings Saved...", 'sheetspilot'), $urlRedirect);
					break;
				case "get_posts_select2":


					$posts_list = SheetsPilotCellEditor::getPostsList($data);

					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_SETTINGS);
					SheetsPilotHelper::ajaxResponseSuccess($posts_list, $urlRedirect);
					break;
				case "save_edited_posts":


					$result = SheetsPilotCellEditor::saveEditedPostsData($data);
					if ($result['save_result']) {
						$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
						SheetsPilotHelper::ajaxResponseSuccess(__("Posts Saved...", 'sheetspilot'), $urlRedirect);
					} else {

						SheetsPilotHelper::ajaxResponseError($result);
					}
					break;
				case "generate_slug_from_title":
					$slug_post_id = isset($data['post_id']) ? absint($data['post_id']) : 0;
					if (!$slug_post_id) {
						SheetsPilotHelper::ajaxResponseError(__('Missing post_id.', 'sheetspilot'));
					}
					$slug_result = SheetsPilotCellEditor::generateSlugFromTitle($slug_post_id);
					if (empty($slug_result['ok'])) {
						SheetsPilotHelper::ajaxResponseError(isset($slug_result['message']) ? $slug_result['message'] : __('Could not generate slug.', 'sheetspilot'));
					}
					SheetsPilotHelper::ajaxResponseSuccess(
						__('Slug generated.', 'sheetspilot'),
						array('slug' => isset($slug_result['slug']) ? $slug_result['slug'] : '')
					);
					break;
				case "process_posts_via_gpt":
					if (SheetsPilotGlobals::$isPro != true) {
						SheetsPilotHelper::ajaxResponseError(__('AI assistant is available in Pro version.', 'sheetspilot'));
					}
					if (! class_exists('SheetsPilot_UseChatGPT', false)) {
						SheetsPilotHelper::ajaxResponseError(__('Pro AI module is not loaded.', 'sheetspilot'));
					}
					// process all cells to remove html
					$reprocessedDataArray = SheetsPilot_UseChatGPT::replaceHtmlWithPlaceholders($data);

					// prepare prompt
					$prepared_prompt = SheetsPilot_UseChatGPT::preparePrompt(SheetsPilotGlobals::PROCESS_CONTENT_PROMPT, [wp_json_encode($reprocessedDataArray)]);
					$results = SheetsPilotCellEditor::processPostsWithGPT($data, $prepared_prompt);

					// replace old codes with html
					// replace cell value
					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess($results['data'], $urlRedirect);
					break;
				case "apply_prompt":
			 
					$results = $this->handleApplyPrompt($data);
					SheetsPilotHelper::ajaxResponseSuccess("Prompt applied.", $results);
					break;
				case "apply_pending_image":
					$request_id = isset($data['request_id']) ? sanitize_text_field($data['request_id']) : '';
					$post_id    = isset($data['post_id']) ? absint($data['post_id']) : 0;
					$column     = isset($data['column']) ? sanitize_text_field($data['column']) : 'post_image';

					$column_type    = isset($data['column_type']) ? sanitize_text_field($data['column_type']) : 'image';

					if ($request_id === '' || $post_id === 0) {
						SheetsPilotHelper::ajaxResponseError(__('Missing request_id or post_id.', 'sheetspilot'));
					}

					if( $column_type == 'acf_gallery' || $column_type == 'acf_woo_gallery'  ){
						$result = SheetsPilot_ImageProcessing::promotePendingToPostGallery($request_id, $post_id, $column, $column_type );
					}else{
						$result = SheetsPilot_ImageProcessing::promotePendingToPost($request_id, $post_id, $column);
					}

					SheetsPilotHelper::ajaxResponseSuccess(__('Image applied.', 'sheetspilot'), array('data' => $result));
					break;
				case "discard_pending_image":
					$request_id = isset($data['request_id']) ? sanitize_text_field($data['request_id']) : '';
					if ($request_id === '') {
						SheetsPilotHelper::ajaxResponseError(__('Missing request_id.', 'sheetspilot'));
					}
					SheetsPilot_ImageProcessing::deletePending($request_id);
					SheetsPilotHelper::ajaxResponseSuccess(__('Image discarded.', 'sheetspilot'));
					break;
				case "compress_image":
					$table_data = isset( $data['table'] ) ? $data['table'] : array();
					if ( ! is_array( $table_data ) ) {
						SheetsPilotHelper::ajaxResponseError( __( 'Table data is invalid.', 'sheetspilot' ) );
					}
					$result = SheetsPilotCellEditor::compressImageFromTable( $table_data );
					SheetsPilotHelper::ajaxResponseSuccess( __( 'Image compressed.', 'sheetspilot' ), array( 'data' => $result ) );
					break;
				case "return_post_type_table":
 
					$total_count = 0;

					SheetsPilotHelper::saveEditorPageSettings($data, 'post_type');

					$postQueryObj = new SheetsPilotQueryProcessing();
					$postQueryObj->postType = $data['post_type'];

					$table_data_content = $postQueryObj->getPostTypeArray();
					$table_data_structure = SheetsPilotCellEditor::getPostTypeStructure($data['post_type']);
					$posts_per_page = SheetsPilotHelper::getEditorPageSettings('rows_per_page');
					if (!$posts_per_page) {
						$posts_per_page = 10;
					}

					$post_type_obj = get_post_type_object($data['post_type']);
					$post_type_label = ($post_type_obj) ? $post_type_obj->label : $data['post_type'];

					// Updated Count Logic
					$count_posts = wp_count_posts($data['post_type']);

					if ($data['post_type'] == 'attachment') {
						// Media uses 'inherit' for standard uploads
						$total_count = $count_posts->inherit;
					} else {
						// Sum the standard statuses for other post types
						$total_count = $count_posts->publish + $count_posts->draft + $count_posts->private + $count_posts->pending;
					}

					//patch for new columns
					$item_columns = SheetsPilotHelper::getEditorPageSettings($data['post_type'] . '_columns');
					if ( isset( $item_columns[0] ) && $item_columns[0] != 'bulk') {
						SheetsPilotHelper::deleteEditorPageSettings($data, $data['post_type'] . '_columns');
						SheetsPilotHelper::deleteEditorPageSettings($data, $data['post_type'] . '_columns_order');
					}

					$item_columns = SheetsPilotHelper::getEditorPageSettings($data['post_type'] . '_columns');
					if (!is_array($item_columns)) {
						$item_columns = [];
					}

					$item_columns_order = SheetsPilotHelper::getEditorPageSettings($data['post_type'] . '_columns_order');
					if (!is_array($item_columns_order)) {
						$item_columns_order = [];
					}

					// patch in case order of columns got mixed
					$remove = 'id';
					$item_columns_order = array_values(array_filter($item_columns_order, function ($item) use ($remove) {
						return $item !== $remove;
					}));
					$remove = 'post_title';
					$item_columns_order = array_values(array_filter($item_columns_order, function ($item) use ($remove) {
						return $item !== $remove;
					}));
					$remove = 'bulk';
					$item_columns_order = array_values(array_filter($item_columns_order, function ($item) use ($remove) {
						return $item !== $remove;
					}));
					$newItems = ['bulk', 'id', 'post_title'];

					array_unshift($item_columns_order, ...$newItems);
					// patch in case order of columns got mixed END


					$pages_number = intval(($total_count / $posts_per_page));
					if ($total_count % $posts_per_page > 0) {
						$pages_number++;
					}

					// bulk actions for post type
					$bulk_actions = [
						'' => '',
						'bulk_trash' => __('Move to Trash', 'sheetspilot'),
						'bulk_status' => __('Change Status', 'sheetspilot'),
						'bulk_author' => __('Change Author', 'sheetspilot'),
					];

					$taxonomies = get_object_taxonomies($data['post_type'], 'objects');

					foreach ($taxonomies as $slug => $tax_data) {
						if ($tax_data->hierarchical) {
							$bulk_actions['bulk_tax_' . $slug] = __('Change', 'sheetspilot') . ' ' . $tax_data->label;
						} else {
							$bulk_actions['bulk_tag_' . $slug] = __('Change', 'sheetspilot') . ' ' . $tax_data->label;
						}
					}

					// add ACF fields to bulk editor
					$acf_extra_fields = SheetsPilotCellEditor::get_acf_fields_for_post_type($data['post_type']);
				
					foreach ($acf_extra_fields as $s_field_data) {
						if (in_array($s_field_data["type"], ['select', 'radio', 'checkbox'])) {
							$bulk_actions['bulk_' . $s_field_data["type"] . '_' . $s_field_data["name"]] = __('Change', 'sheetspilot') . ' ' . $s_field_data["label"];
						}
					}

					$cell_rules = array();
					if (SheetsPilotGlobals::$isPro == true) {
						$cell_rules = SheetsPilot_PromptsUI::get_cell_rules($data['post_type']);
					}
					$image_query_requests = class_exists('SheetsPilot_UseChatGPT', false)
						? SheetsPilot_UseChatGPT::imageQueueGetRequests()
						: array();
					$output = array(
						"structure" => $table_data_structure,
						"message" => $table_data_content,
						"count"   => $total_count,
						"label"   => $post_type_label,
						"columns"   => $item_columns,
						"columns_order"   => $item_columns_order,
						"cell_rules" => $cell_rules,

						"total_count" => $total_count,
						"posts_per_page" => $posts_per_page,
						"pages_number" => $pages_number,
						"bulk_actions" => $bulk_actions,
						'image_query_requests' => $image_query_requests
					);

					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);

					SheetsPilotHelper::ajaxResponseSuccess($output, $urlRedirect);
					break;
				case "save_editor_table_columns":
					SheetsPilotHelper::saveEditorPageSettings($data, 'columns');
					SheetsPilotHelper::saveEditorPageSettings($data, $data['post_type'] . '_columns');
					SheetsPilotHelper::saveEditorPageSettings($data, $data['post_type'] . '_columns_order');

					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess([], $urlRedirect);
					break;
				case "add_post_taxonomy":
					$output = SheetsPilotCellEditor::addPostTaxonomy($data['post_id'], $data['category_parent'], $data['category_name'], $data['taxonomy'], $data['row_id'], $data['col_id']);
					$urlRedirect = SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_POSTEDITOR);
					SheetsPilotHelper::ajaxResponseSuccess($output, $urlRedirect);
					break;

				case "save_content_rules":
					SheetsPilotHelper::saveContentRules($data);
					SheetsPilotHelper::ajaxResponseSuccess(__('Content rules saved.', 'sheetspilot'));
					break;

				case "save_prompt_to_saved":
					if (SheetsPilotGlobals::$isPro != true) {
						SheetsPilotHelper::ajaxResponseError(__('Prompt history is available only in Pro.', 'sheetspilot'));
					}

					SheetsPilot_PromptHistory::setSaved($data);
					$saved_list   = SheetsPilot_PromptHistory::getSavedForDropdown();
					$payload = array(
						'success'          => true,
						'totalSaved'       => is_array($saved_list) ? count($saved_list) : 0,
						'savedPromptsHtml' => SheetsPilot_PromptHistory::renderSavedDropdownOptionsHtml(),
						'savedPromptsSubItems' => SheetsPilot_PromptHistory::getSavedContextSubItemsForAjax(),
						'savedPromptsMenuLabel' => __('Saved Prompts', 'sheetspilot'),
					);
					SheetsPilotHelper::ajaxResponseSuccess(__('Saved.', 'sheetspilot'), $payload);

					break;

				case "remove_prompt_from_saved":
					if (SheetsPilotGlobals::$isPro != true) {
						SheetsPilotHelper::ajaxResponseError(__('Prompt history is available only in Pro.', 'sheetspilot'));
					}
					$prompt_id = isset($data['prompt_id']) ? absint($data['prompt_id']) : 0;
					if ($prompt_id < 1) {
						SheetsPilotHelper::ajaxResponseError(__('Invalid prompt ID.', 'sheetspilot'));
					} else {
						SheetsPilot_PromptHistory::setUnsaved($prompt_id);
						$saved_list  = SheetsPilot_PromptHistory::getSavedForDropdown();
						$payload = array(
							'success'               => true,
							'totalSaved'            => is_array($saved_list) ? count($saved_list) : 0,
							'savedPromptsSubItems'  => SheetsPilot_PromptHistory::getSavedContextSubItemsForAjax(),
							'savedPromptsMenuLabel' => __('Saved Prompts', 'sheetspilot'),
						);
						SheetsPilotHelper::ajaxResponseSuccess(__('Removed.', 'sheetspilot'), $payload);
					}
					break;

				case "get_prompt_history":
					if (SheetsPilotGlobals::$isPro != true) {
						$result = array(
							'items'       => array(),
							'itemsHtml'   => '',
							'totalRecent' => 0,
							'totalSaved'  => 0,
						);
						SheetsPilotHelper::ajaxResponseSuccess($result);
					}
					$limit  = isset($data['limit']) ? absint($data['limit']) : 100;
					$filter = isset($data['filter']) && $data['filter'] === 'saved' ? 'saved' : 'all';
					$search = isset($data['search']) ? $data['search'] : null;
					$result = SheetsPilot_PromptHistory::getForPanel($limit, $filter, $search);
					$result['itemsHtml'] = '';
					if (SheetsPilotGlobals::$isPro == true) {
						$result['itemsHtml'] = SheetsPilot_PromptsUI::get_prompt_history_list_html(isset($result['items']) ? $result['items'] : array());
					}
					SheetsPilotHelper::ajaxResponseSuccess($result);
					break;

				case "delete_prompt_from_history":
					if (SheetsPilotGlobals::$isPro != true) {
						SheetsPilotHelper::ajaxResponseError(__('Prompt history is available only in Pro.', 'sheetspilot'));
					}
					$prompt_id = isset($data['prompt_id']) ? absint($data['prompt_id']) : 0;
					if ($prompt_id < 1) {
						SheetsPilotHelper::ajaxResponseError(__('Invalid prompt ID.', 'sheetspilot'));
					} else {
						SheetsPilot_PromptHistory::deleteById($prompt_id);
						$payload = array('success' => true);
						SheetsPilotHelper::ajaxResponseSuccess(__('Deleted.', 'sheetspilot'), $payload);
					}
					break;

				case "clear_all_prompt_history":
					if (SheetsPilotGlobals::$isPro != true) {
						$payload = array(
							'success'           => true,
							'latestPrompts'     => array(),
							'latestPromptsHtml' => '',
							'savedPrompts'      => array(),
							'savedPromptsHtml'  => '',
						);
						SheetsPilotHelper::ajaxResponseSuccess(__('Cleared.', 'sheetspilot'), $payload);
					}
					SheetsPilot_PromptHistory::clearAll();
					$payload = array(
						'success'             => true,
						'latestPrompts'       => array(),
						'latestPromptsHtml'   => '',
						'savedPrompts'        => array(),
						'savedPromptsHtml'    => '',
					);
					SheetsPilotHelper::ajaxResponseSuccess(__('Cleared.', 'sheetspilot'), $payload);
					break;

				case "save_cell_rule":
					if (SheetsPilotGlobals::$isPro != true) {
						SheetsPilotHelper::ajaxResponseError(__('Cell rules are available only in Pro.', 'sheetspilot'));
					}


					$post_type = isset($data['post_type']) ? $data['post_type'] : '';
					$column    = isset($data['column']) ? $data['column'] : '';
					$prompt    = isset($data['prompt']) ? $data['prompt'] : '';
					$apply_prompt_on_paste  = isset($data['apply_prompt_on_paste']) ? $data['apply_prompt_on_paste'] : '';
					$auto_apply_response  = isset($data['auto_apply_response']) ? $data['auto_apply_response'] : '';
					$cell_img  = isset($data['cell_rule_image']) && is_array($data['cell_rule_image']) ? $data['cell_rule_image'] : null;
					$targets   = isset($data['target_columns']) && is_array($data['target_columns']) ? $data['target_columns'] : null;
					$save_res  = SheetsPilot_PromptsUI::save_cell_rule($post_type, $column, $prompt, $cell_img, $targets, $apply_prompt_on_paste, $auto_apply_response );
					if (! empty($save_res['ok'])) {
						SheetsPilotHelper::ajaxResponseSuccess(__('Cell rule saved.', 'sheetspilot'));
					} else {
						$err_dbg = isset($save_res['debug']) ? (string) $save_res['debug'] : '';
						$payload = array();
						if ($err_dbg !== '') {
							$payload['cell_rule_save_debug'] = $err_dbg;
						}
						SheetsPilotHelper::ajaxResponseError(__('Failed to save cell rule.', 'sheetspilot'), $payload);
					}
					break;

				case "set_pro_mode":
					$output = SheetsPilotHelper::saveProModeFromAjaxData($data);
					SheetsPilotHelper::ajaxResponseSuccess(__('Saved.', 'sheetspilot'), $output);
					break;

				default:
					SheetsPilotHelper::ajaxResponseError("wrong ajax action: <b>$action</b> ");
					break;
			}
		} catch (Throwable $e) {

			$actionTitle = $this->getActionTitle($action);
			$prefix = null;
			if (!empty($actionTitle))
				$prefix = "$actionTitle error: ";

			$bufferedOutput = $this->consumeBufferedOutput();
			$this->onException($e, $prefix, $bufferedOutput);
		}

		//it's an ajax action, so exit
		SheetsPilotHelper::ajaxResponseError("No response output on <b> $action </b> action. please check with the developer.");
		exit();
	}
}
