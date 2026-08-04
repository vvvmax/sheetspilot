<?php
/**
 * Request/response log for apply_prompt: insert and retrieve log entries.
 *
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! defined( "SHEETSPILOT_INC" ) ) {
	die( "restricted access" );
}

class SheetsPilot_RequestLog {

	/** @var float|null Request timer start (microtime). */
	private static $request_time_start = null;

	/** @var float|null Request timer end (microtime). */
	private static $request_time_end = null;

	/**
	 * Start measuring request duration (call before the API/work begins).
	 *
	 * @return void
	 */
	public static function startRequestTimer() {
		self::$request_time_start = microtime( true );
		self::$request_time_end   = null;
	}

	/**
	 * Stop measuring request duration (call when the API/work completes).
	 *
	 * @return void
	 */
	public static function endRequestTimer() {
		if ( self::$request_time_start !== null ) {
			self::$request_time_end = microtime( true );
		}
	}

	/**
	 * Format a microtime value for metadata display.
	 *
	 * @param float $microtime Unix timestamp with microseconds.
	 * @return string
	 */
	private static function formatRequestTime( $microtime ) {
		$seconds  = (int) $microtime;
		$fraction = $microtime - $seconds;
		$ms       = (int) round( $fraction * 1000 );

		return wp_date( 'Y-m-d H:i:s', $seconds ) . '.' . str_pad( (string) $ms, 3, '0', STR_PAD_LEFT );
	}

	/**
	 * Timing fields for the current request lifecycle.
	 *
	 * @return array<string,mixed>
	 */
	public static function getRequestTimingMetadata() {
		if ( self::$request_time_start === null ) {
			return array();
		}

		$ended    = self::$request_time_end !== null ? self::$request_time_end : microtime( true );
		$started  = self::$request_time_start;
		$duration = $ended - $started;

		return array(
			'request_time_start'         => self::formatRequestTime( $started ),
			'request_time_end'           => self::formatRequestTime( $ended ),
			'request_duration_ms'        => round( $duration * 1000, 2 ),
			'request_duration_seconds'   => round( $duration, 3 ),
		);
	}

	/**
	 * Merge timing fields into a metadata array.
	 *
	 * @param array<string,mixed> $metadata Existing metadata.
	 * @return array<string,mixed>
	 */
	public static function mergeTimingIntoMetadata( $metadata ) {
		$timing = self::getRequestTimingMetadata();
		if ( empty( $timing ) ) {
			return is_array( $metadata ) ? $metadata : array();
		}
		if ( ! is_array( $metadata ) ) {
			$metadata = array();
		}

		return array_merge( $metadata, $timing );
	}

	/**
	 * Insert a request/response log entry (e.g. after apply_prompt).
	 *
	 * @param string       $prompt          Prompt text (or title).
	 * @param string|array $cell_value      Cell/table value sent (will be JSON-encoded if array).
	 * @param string|array $request        Raw request (will be JSON-encoded if array).
	 * @param string|array $response        Raw API response (will be JSON-encoded if array).
	 * @param string       $response_action One of 'replace_text', 'show_message', etc.
	 * @param string|array $response_data   Extracted response text/data returned to user (will be JSON-encoded if array).
	 * @param string|array $metadata        Prompt/build metadata payload (will be JSON-encoded if array).
	 * @return int|false Insert id or false on failure.
	 */
	public static function insert( $prompt, $cell_value, $request, $response, $response_action = '', $response_data = '', $metadata = '' ) {
		global $wpdb;
		$table = SheetsPilotGlobals::$tableLogs;

		if ( is_array( $cell_value ) || is_object( $cell_value ) ) {
			$cell_value = wp_json_encode( $cell_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}
		$cell_value = (string) $cell_value;

		if ( is_array( $request ) ) {
			$request = wp_json_encode( $request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}
		if ( is_array( $response ) ) {
			$response = wp_json_encode( $response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}
		if ( is_array( $response_data ) || is_object( $response_data ) ) {
			$response_data = wp_json_encode( $response_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}
		$response_data = (string) $response_data;

		$metadata_arr = array();
		if ( is_array( $metadata ) ) {
			$metadata_arr = $metadata;
		} elseif ( is_object( $metadata ) ) {
			$metadata_arr = (array) $metadata;
		} elseif ( is_string( $metadata ) && $metadata !== '' ) {
			$decoded = json_decode( $metadata, true );
			if ( is_array( $decoded ) ) {
				$metadata_arr = $decoded;
			}
		}
		$metadata_arr = self::mergeTimingIntoMetadata( $metadata_arr );
		$metadata     = ! empty( $metadata_arr )
			? wp_json_encode( $metadata_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			: '';

		$user_id = get_current_user_id();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$table,
			array(
				'prompt'          => $prompt,
				'cell_value'      => $cell_value,
				'request'         => $request,
				'response'        => $response,
				'response_data'   => $response_data,
				'response_action' => $response_action ? sanitize_text_field( $response_action ) : null,
				'metadata'        => $metadata,
				'userid'          => $user_id ? $user_id : null,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		if ( ! $inserted ) {
			return false;
		}

		self::rotate();

		return (int) $wpdb->insert_id;
	}

	/**
	 * Keep the newest REQUEST_LOG_KEEP rows. Runs only when the table has reached
	 * keep + REQUEST_LOG_ROTATION_BUFFER (e.g. 210), then deletes the oldest excess.
	 *
	 * @return void
	 */
	public static function rotate() {
		global $wpdb;

		$table  = SheetsPilotGlobals::$tableLogs;
		$keep   = (int) SheetsPilotGlobals::REQUEST_LOG_KEEP;
		$buffer = (int) SheetsPilotGlobals::REQUEST_LOG_ROTATION_BUFFER;

		if ( $keep < 1 ) {
			$keep = 200;
		}
		if ( $buffer < 1 ) {
			$buffer = 10;
		}

		$threshold = $keep + $buffer;
		$table_sql = esc_sql( $table );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_sql}" );

		if ( $count < $threshold ) {
			return;
		}

		$delete_count = $count - $keep;
		if ( $delete_count < 1 ) {
			return;
		}

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_sql} ORDER BY id ASC LIMIT %d",
				$delete_count
			)
		);
		// phpcs:enable
	}

	/**
	 * Get the last N log entries for the request/response log view.
	 *
	 * @param int    $limit           Max number of rows (default REQUEST_LOG_DISPLAY).
	 * @param string $response_action Optional action filter (e.g. error, pending_image).
	 * @param string $order_mode      id_desc (default) or action_error_first.
	 * @return array List of rows (associative arrays).
	 */
	public static function getLast( $limit = null, $response_action = '', $order_mode = 'id_desc' ) {
		global $wpdb;
		$table = SheetsPilotGlobals::$tableLogs;
		$table = esc_sql( $table );

		if ( $limit === null ) {
			$limit = (int) SheetsPilotGlobals::REQUEST_LOG_DISPLAY;
		}
		$limit = absint( $limit );
		if ( $limit < 1 ) {
			$limit = (int) SheetsPilotGlobals::REQUEST_LOG_DISPLAY;
		}

		$response_action = sanitize_key( (string) $response_action );
		$order_mode      = sanitize_key( (string) $order_mode );
		if ( $order_mode !== 'action_error_first' ) {
			$order_mode = 'id_desc';
		}

		if ( $order_mode === 'action_error_first' ) {
			// Errors first, then other actions A–Z, newest within each group.
			$order_sql = "ORDER BY CASE WHEN response_action = 'error' THEN 0 ELSE 1 END ASC, response_action ASC, id DESC";
		} else {
			$order_sql = 'ORDER BY id DESC';
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $response_action !== '' ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, prompt, cell_value, request, response, response_data, response_action, metadata, userid, date FROM {$table} WHERE response_action = %s {$order_sql} LIMIT %d",
					$response_action,
					$limit
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, prompt, cell_value, request, response, response_data, response_action, metadata, userid, date FROM {$table} {$order_sql} LIMIT %d",
					$limit
				),
				ARRAY_A
			);
		}
		// phpcs:enable

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Distinct response_action values present in the log (for filter dropdown).
	 *
	 * @return string[]
	 */
	public static function getDistinctActions() {
		global $wpdb;
		$table = esc_sql( SheetsPilotGlobals::$tableLogs );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			"SELECT DISTINCT response_action FROM {$table} WHERE response_action IS NOT NULL AND response_action <> '' ORDER BY response_action ASC"
		);
		// phpcs:enable

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'strval', $rows ),
				static function ( $action ) {
					return $action !== '';
				}
			)
		);
	}

	/**
	 * Get one log row by ID.
	 *
	 * @param int $id Log entry ID.
	 * @return array<string,mixed>|null
	 */
	public static function getById( $id ) {
		global $wpdb;
		$table = SheetsPilotGlobals::$tableLogs;
		$table = esc_sql( $table );
		$id    = absint( $id );

		if ( $id <= 0 ) {
			return null;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, prompt, cell_value, request, response, response_data, response_action, metadata, userid, date FROM {$table} WHERE id = %d LIMIT 1",
				$id
			),
			ARRAY_A
		);
		// phpcs:enable

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Extract image URL / attachment ID from a stored cell value.
	 *
	 * @param string $cell_value Raw cell value from the log.
	 * @return array{url:string,attachment_id:int}
	 */
	private static function extractImageSourceFromCellValue( $cell_value ) {
		$out = array(
			'url'            => '',
			'attachment_id'  => 0,
		);
		$cell_value = trim( (string) $cell_value );
		if ( $cell_value === '' ) {
			return $out;
		}

		$decoded = json_decode( $cell_value, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
			if ( isset( $decoded['value'] ) ) {
				$cell_value = (string) $decoded['value'];
			}
			if ( isset( $decoded['image_attachment_id'] ) ) {
				$out['attachment_id'] = absint( $decoded['image_attachment_id'] );
			}
		}

		if ( preg_match( '/src\s*=\s*["\']([^"\']+)["\']/i', $cell_value, $m ) ) {
			$out['url'] = esc_url_raw( $m[1] );
		} elseif ( filter_var( $cell_value, FILTER_VALIDATE_URL ) ) {
			$out['url'] = esc_url_raw( $cell_value );
		}

		if ( preg_match( '/data-id\s*=\s*["\'](\d+)["\']/i', $cell_value, $id_match ) ) {
			$out['attachment_id'] = absint( $id_match[1] );
		} elseif ( preg_match( '/^\d+$/', trim( $cell_value ) ) ) {
			$out['attachment_id'] = absint( trim( $cell_value ) );
		}

		if ( $out['attachment_id'] > 0 && $out['url'] === '' ) {
			$attachment_url = wp_get_attachment_url( $out['attachment_id'] );
			if ( is_string( $attachment_url ) && $attachment_url !== '' ) {
				$out['url'] = $attachment_url;
			}
		}

		return $out;
	}

	/**
	 * Extract system message from a stored OpenAI request JSON blob.
	 *
	 * @param mixed $request Stored request.
	 * @return string
	 */
	private static function extractSystemMessageFromRequest( $request ) {
		if ( ! is_string( $request ) || $request === '' ) {
			return '';
		}
		$decoded = json_decode( $request, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) || ! isset( $decoded['messages'] ) || ! is_array( $decoded['messages'] ) ) {
			return '';
		}
		foreach ( $decoded['messages'] as $message ) {
			if ( ! is_array( $message ) || ( isset( $message['role'] ) ? (string) $message['role'] : '' ) !== 'system' ) {
				continue;
			}
			$content = isset( $message['content'] ) ? $message['content'] : '';
			return is_string( $content ) ? $content : '';
		}
		return '';
	}

	/**
	 * Extract user message from stored request JSON, with prompt fallback.
	 *
	 * @param mixed  $request Stored request.
	 * @param string $fallback_prompt Prompt column fallback.
	 * @return string
	 */
	private static function extractUserMessageFromRequest( $request, $fallback_prompt ) {
		$output = trim( (string) $fallback_prompt );
		if ( ! is_string( $request ) || $request === '' ) {
			return $output;
		}

		$decoded = json_decode( $request, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
			return $output;
		}

		if ( isset( $decoded['image_generation']['prompt'] ) ) {
			return (string) $decoded['image_generation']['prompt'];
		}
		if ( isset( $decoded['image_edit']['prompt'] ) ) {
			return (string) $decoded['image_edit']['prompt'];
		}

		if ( ! isset( $decoded['messages'] ) || ! is_array( $decoded['messages'] ) ) {
			return $output;
		}

		foreach ( $decoded['messages'] as $message ) {
			if ( ! is_array( $message ) || ( isset( $message['role'] ) ? (string) $message['role'] : '' ) !== 'user' ) {
				continue;
			}
			$content = isset( $message['content'] ) ? $message['content'] : '';
			return is_string( $content ) ? trim( $content ) : $output;
		}

		return $output;
	}

	/**
	 * Build Prompt Tester preload payload from a log row.
	 *
	 * @param array<string,mixed> $row Log row.
	 * @return array<string,mixed>
	 */
	public static function buildPromptTesterPreloadFromRow( $row ) {
		$action     = isset( $row['response_action'] ) ? sanitize_key( (string) $row['response_action'] ) : '';
		$prompt     = isset( $row['prompt'] ) ? (string) $row['prompt'] : '';
		$cell_value = isset( $row['cell_value'] ) ? (string) $row['cell_value'] : '';
		$request    = isset( $row['request'] ) ? $row['request'] : '';
		$metadata   = isset( $row['metadata'] ) ? $row['metadata'] : '';

		$meta_arr = array();
		if ( is_string( $metadata ) && $metadata !== '' ) {
			$decoded_meta = json_decode( $metadata, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_meta ) ) {
				$meta_arr = $decoded_meta;
			}
		} elseif ( is_array( $metadata ) ) {
			$meta_arr = $metadata;
		}

		$preload = array(
			'tab'    => 'text',
			'log_id' => isset( $row['id'] ) ? absint( $row['id'] ) : 0,
		);

		if ( $action === 'tester_create_image' ) {
			$preload['tab']          = 'image';
			$preload['image_mode']   = 'generate';
			$preload['image_prompt'] = $prompt;
		} elseif ( $action === 'tester_edit_image' ) {
			$preload['tab']              = 'image';
			$preload['image_mode']       = 'edit';
			$preload['image_edit_prompt'] = $prompt;
			$image_source                = self::extractImageSourceFromCellValue( $cell_value );
			$preload['image_url']        = $image_source['url'];
			$preload['attachment_id']    = $image_source['attachment_id'];
		} elseif ( $action === 'tester_text' ) {
			$preload['tab']           = 'text';
			$preload['user_message']  = $prompt;
			$preload['system_message'] = self::extractSystemMessageFromRequest( $request );
		} elseif ( $action === 'pending_image' ) {
			$preload['tab'] = 'image';
			$image_source   = self::extractImageSourceFromCellValue( $cell_value );
			if ( $image_source['url'] !== '' ) {
				$preload['image_mode']        = 'edit';
				$preload['image_edit_prompt'] = $prompt;
				$preload['image_url']         = $image_source['url'];
				$preload['attachment_id']     = $image_source['attachment_id'];
			} else {
				$preload['image_mode']   = 'generate';
				$preload['image_prompt'] = $prompt;
			}
		} else {
			$preload['tab']            = 'text';
			$preload['user_message']   = self::extractUserMessageFromRequest( $request, $prompt );
			$preload['system_message'] = self::extractSystemMessageFromRequest( $request );
		}

		if ( isset( $meta_arr['model'] ) && is_string( $meta_arr['model'] ) && $meta_arr['model'] !== '' ) {
			$preload['model'] = sanitize_text_field( $meta_arr['model'] );
		}
		if ( isset( $meta_arr['tool'] ) && is_string( $meta_arr['tool'] ) && $meta_arr['tool'] !== '' ) {
			$preload['tool'] = sanitize_key( $meta_arr['tool'] );
		}
		if ( isset( $meta_arr['system_message'] ) && is_string( $meta_arr['system_message'] ) && $meta_arr['system_message'] !== '' ) {
			$preload['system_message'] = $meta_arr['system_message'];
		}
		if ( isset( $meta_arr['aspect_ratio'] ) && is_string( $meta_arr['aspect_ratio'] ) && $meta_arr['aspect_ratio'] !== '' ) {
			$preload['aspect_ratio'] = sanitize_text_field( $meta_arr['aspect_ratio'] );
		}
		if ( isset( $meta_arr['quality'] ) && is_string( $meta_arr['quality'] ) && $meta_arr['quality'] !== '' ) {
			$preload['quality'] = sanitize_text_field( $meta_arr['quality'] );
		}
		if ( isset( $meta_arr['format'] ) && is_string( $meta_arr['format'] ) && $meta_arr['format'] !== '' ) {
			$preload['format'] = sanitize_text_field( $meta_arr['format'] );
		}

		return $preload;
	}

	/**
	 * Mark an existing log row as an error (for failures after a success-path insert).
	 *
	 * @param int         $id            Log row id.
	 * @param string|null $error_message Optional error text for response_data.
	 * @return bool True when the row was updated.
	 */
	public static function markAsError( $id, $error_message = null ) {
		global $wpdb;

		$id = absint( $id );
		if ( $id <= 0 ) {
			return false;
		}

		$data   = array( 'response_action' => 'error' );
		$format = array( '%s' );

		if ( $error_message !== null ) {
			if ( is_array( $error_message ) || is_object( $error_message ) ) {
				$error_message = wp_json_encode( $error_message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			}
			$data['response_data'] = (string) $error_message;
			$format[]              = '%s';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			SheetsPilotGlobals::$tableLogs,
			$data,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		return false !== $updated;
	}
}
