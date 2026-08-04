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

class SheetsPilot_PluginViewLog {

	/**
	 * Max length for title (prompt) in table cell.
	 */
	const TITLE_MAX_LEN = 80;

	/**
	 * Max length for cell/response value preview in table row.
	 */
	const ROW_PREVIEW_LEN = 60;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->putViewHtml();
	}

	/**
	 * Truncate text for display as title.
	 *
	 * @param string $text
	 * @return string
	 */
	private function truncateTitle( $text ) {
		$text = trim( (string) $text );
		if ( strlen( $text ) <= self::TITLE_MAX_LEN ) {
			return $text;
		}
		return substr( $text, 0, self::TITLE_MAX_LEN ) . '…';
	}

	/**
	 * If stored cell value is JSON (e.g. old table_data), extract the 'value' key for display.
	 *
	 * @param string|null $raw Stored cell_value (may be plain string or JSON object with 'value').
	 * @return string Value to display (actual cell text or raw if not JSON / no 'value').
	 */
	private function getCellValueForDisplay( $raw ) {
		if ( $raw === null || $raw === '' ) {
			return '';
		}
		$text = trim( (string) $raw );
		$decoded = json_decode( $text, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) && array_key_exists( 'value', $decoded ) ) {
			return trim( (string) $decoded['value'] );
		}
		return $text;
	}

	/**
	 * Get a short plain-text preview for table row (cell value or response value).
	 *
	 * @param string|null $raw Stored value (may be JSON string).
	 * @return string
	 */
	private function previewForRow( $raw ) {
		if ( $raw === null || $raw === '' ) {
			return '—';
		}
		$text = (string) $raw;
		$decoded = json_decode( $text, true );
		if ( json_last_error() === JSON_ERROR_NONE && ( is_array( $decoded ) || is_object( $decoded ) ) ) {
			$text = wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE );
		}
		$text = preg_replace( '/\s+/', ' ', trim( $text ) );
		if ( strlen( $text ) <= self::ROW_PREVIEW_LEN ) {
			return $text;
		}
		return substr( $text, 0, self::ROW_PREVIEW_LEN ) . '…';
	}

	/**
	 * Format stored request/response for display (pretty-print JSON if possible).
	 *
	 * @param string|null $raw
	 * @return string
	 */
	private function formatForDisplay( $raw ) {
		if ( $raw === null || $raw === '' ) {
			return '—';
		}
		$decoded = json_decode( $raw, true );
		if ( json_last_error() === JSON_ERROR_NONE && ( is_array( $decoded ) || is_object( $decoded ) ) ) {
			// Image generation responses can include huge base64 payload under `data`.
			// Redact it to keep the log readable.
			if ( is_array( $decoded ) && array_key_exists( 'data', $decoded ) ) {
				$decoded['data'] = is_array( $decoded['data'] )
					? '[omitted image data (' . count( $decoded['data'] ) . ' item(s)]'
					: '[omitted image data]';
			}
			return wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}
		return $raw;
	}

	/**
	 * Extract the user message content from the stored API request.
	 *
	 * @param string|null $raw_request Stored JSON request.
	 * @param string      $fallback_prompt Stored prompt text fallback.
	 * @return string
	 */
	private function getRequestDataForDisplay( $raw_request, $fallback_prompt ) {
		$output = trim( (string) $fallback_prompt );

		if ( $raw_request === null || $raw_request === '' ) {
			return $output;
		}

		$decoded = json_decode( $raw_request, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) || ! isset( $decoded['messages'] ) || ! is_array( $decoded['messages'] ) ) {
			return $output;
		}

		foreach ( $decoded['messages'] as $message ) {
			if ( ! is_array( $message ) ) {
				continue;
			}

			$role = isset( $message['role'] ) ? (string) $message['role'] : '';
			if ( $role !== 'user' || ! array_key_exists( 'content', $message ) ) {
				continue;
			}

			$content = $message['content'];
			$output = is_string( $content ) ? trim( $content ) : wp_json_encode( $content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			break;
		}

		return $output;
	}

	private function shouldShowSessionLogUi() {
		if ( SheetsPilotGlobals::$enableAjaxSessionLog !== true ) {
			return false;
		}
		$settings = SheetsPilotHelper::getGeneralSettings();
		return isset( $settings['showSessionLog'] ) && '1' === (string) $settings['showSessionLog'];
	}

	/**
	 * Output the log view HTML.
	 */
	private function putViewHtml() {

		$action_filter = isset( $_GET['action_filter'] ) ? sanitize_key( wp_unslash( $_GET['action_filter'] ) ) : '';
		$orderby       = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
		$sort_by_action = ( $orderby === 'action' );

		$display_limit = (int) SheetsPilotGlobals::REQUEST_LOG_DISPLAY;
		$keep_limit    = (int) SheetsPilotGlobals::REQUEST_LOG_KEEP;
		if ( $display_limit < 1 ) {
			$display_limit = 100;
		}
		if ( $keep_limit < 1 ) {
			$keep_limit = 200;
		}

		// Default: newest DISPLAY rows. Action sort/filter: search the full KEEP pool (errors first when sorting).
		$query_limit = ( $sort_by_action || $action_filter !== '' ) ? $keep_limit : $display_limit;
		$order_mode  = $sort_by_action ? 'action_error_first' : 'id_desc';

		$action_types = SheetsPilot_RequestLog::getDistinctActions();
		$logs         = SheetsPilot_RequestLog::getLast( $query_limit, $action_filter, $order_mode );
		$title = __( 'Request / Response Log', 'sheetspilot' );
		$log_page = SheetsPilotGlobals::PLUGIN_SLUG . '_log';
		$prompt_history_url = SheetsPilotHelper::getViewUrl( SheetsPilotGlobals::VIEW_PROMPT_HISTORY );
		$show_session_log_ui = $this->shouldShowSessionLogUi();
		$session_logs = $show_session_log_ui ? SheetsPilot_AjaxSessionLog::getSessions() : array();

		$base_log_url = admin_url( 'admin.php?page=' . $log_page );
		$action_sort_only_url = add_query_arg( array( 'orderby' => 'action' ), $base_log_url );
		$action_sort_url = add_query_arg(
			array_filter(
				array(
					'orderby'       => 'action',
					'action_filter' => $action_filter !== '' ? $action_filter : null,
				)
			),
			$base_log_url
		);
		$action_sort_clear_url = add_query_arg(
			array_filter(
				array(
					'action_filter' => $action_filter !== '' ? $action_filter : null,
				)
			),
			$base_log_url
		);
		?>
		<div class="wrap unlimited-ai-log-wrap">
			<h1><?php echo esc_html( $title ); ?></h1>
			<p class="unlimited-ai-log-description">
				<?php
				printf(
					/* translators: 1: display limit, 2: keep limit */
					esc_html__( 'Showing the newest %1$d prompt requests (up to %2$d kept in the database). Click Show full to expand; click Hide to collapse.', 'sheetspilot' ),
					$display_limit,
					$keep_limit
				);
				?>
			</p>
			<p class="unlimited-ai-log-toolbar">
				<a href="<?php echo esc_url( $prompt_history_url ); ?>" class="button"><?php esc_html_e( 'Show prompt history table', 'sheetspilot' ); ?></a>
				<?php if ( $show_session_log_ui ) : ?>
				<button type="button" class="button unlimited-ai-session-log-toggle" aria-expanded="false"><?php esc_html_e( 'Show session log', 'sheetspilot' ); ?></button>
				<?php endif; ?>
				<form method="get" class="unlimited-ai-log-action-filter">
					<input type="hidden" name="page" value="<?php echo esc_attr( $log_page ); ?>">
					<?php if ( $sort_by_action ) : ?>
					<input type="hidden" name="orderby" value="action">
					<?php endif; ?>
					<label for="unlimited-ai-log-action-filter"><?php esc_html_e( 'Action', 'sheetspilot' ); ?></label>
					<select name="action_filter" id="unlimited-ai-log-action-filter">
						<option value=""><?php esc_html_e( 'All actions', 'sheetspilot' ); ?></option>
						<?php foreach ( $action_types as $action_type ) : ?>
						<option value="<?php echo esc_attr( $action_type ); ?>" <?php selected( $action_filter, $action_type ); ?>><?php echo esc_html( $action_type ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'sheetspilot' ); ?></button>
					<?php if ( $action_filter !== '' ) : ?>
					<a class="button" href="<?php echo esc_url( $sort_by_action ? $action_sort_only_url : $base_log_url ); ?>"><?php esc_html_e( 'Clear filter', 'sheetspilot' ); ?></a>
					<?php endif; ?>
				</form>
			</p>
			<?php if ( $sort_by_action ) : ?>
			<p class="unlimited-ai-log-description unlimited-ai-log-filter-note">
				<?php
				printf(
					/* translators: %d: keep limit */
					esc_html__( 'Sorted by action (errors first) across the last %d kept log entries.', 'sheetspilot' ),
					$keep_limit
				);
				?>
				<a href="<?php echo esc_url( $action_sort_clear_url ); ?>"><?php esc_html_e( 'Clear sort', 'sheetspilot' ); ?></a>
			</p>
			<?php elseif ( $action_filter !== '' ) : ?>
			<p class="unlimited-ai-log-description unlimited-ai-log-filter-note">
				<?php
				printf(
					/* translators: 1: action type slug, 2: keep limit */
					esc_html__( 'Showing log entries with action: %1$s (from the last %2$d kept entries).', 'sheetspilot' ),
					esc_html( $action_filter ),
					$keep_limit
				);
				?>
			</p>
			<?php endif; ?>
			<?php if ( $show_session_log_ui ) : ?>
			<div class="unlimited-ai-session-log-panel" style="display:none;">
				<div class="unlimited-ai-session-log-header">
					<div>
						<h2><?php esc_html_e( 'AJAX Session Log', 'sheetspilot' ); ?></h2>
						<p class="unlimited-ai-log-description"><?php esc_html_e( 'Last 15 AJAX sessions (when SheetsPilotGlobals::$enableAjaxSessionLog is enabled).', 'sheetspilot' ); ?></p>
					</div>
					<?php if ( ! empty( $session_logs ) ) : ?>
					<button type="button" class="button unlimited-ai-session-log-copy-all"><?php esc_html_e( 'Copy all', 'sheetspilot' ); ?></button>
					<?php endif; ?>
				</div>
				<table class="wp-list-table widefat fixed striped unlimited-ai-session-log-table">
					<thead>
						<tr>
							<th style="width:50px"><?php esc_html_e( '#', 'sheetspilot' ); ?></th>
							<th style="width:180px"><?php esc_html_e( 'Action', 'sheetspilot' ); ?></th>
							<th style="width:160px"><?php esc_html_e( 'Time', 'sheetspilot' ); ?></th>
							<th><?php esc_html_e( 'Data', 'sheetspilot' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $session_logs ) ) : ?>
						<tr>
							<td colspan="4"><?php esc_html_e( 'No AJAX session log entries yet.', 'sheetspilot' ); ?></td>
						</tr>
						<?php else : ?>
						<?php
						$session_index = 0;
						foreach ( $session_logs as $session ) {
							$session_index++;
							$session_action = isset( $session['action'] ) ? (string) $session['action'] : '—';
							$session_time   = isset( $session['time'] ) ? (string) $session['time'] : '—';
							$session_data   = isset( $session['data'] ) && is_array( $session['data'] ) ? $session['data'] : array();
							$data_blocks    = array();
							foreach ( $session_data as $session_entry ) {
								$data_blocks[] = SheetsPilot_AjaxSessionLog::formatSessionDataItem( $session_entry );
							}
							$data_text = ! empty( $data_blocks ) ? implode( "\n\n", $data_blocks ) : '—';
							?>
						<tr>
							<td><?php echo esc_html( (string) $session_index ); ?></td>
							<td><code><?php echo esc_html( $session_action ); ?></code></td>
							<td><?php echo esc_html( $session_time ); ?></td>
							<td>
								<div class="unlimited-ai-session-log-block">
									<div class="unlimited-ai-session-log-block-header">
										<button type="button" class="button button-small unlimited-ai-log-copy"><?php esc_html_e( 'Copy', 'sheetspilot' ); ?></button>
									</div>
									<pre class="unlimited-ai-session-log-data"><?php echo esc_html( $data_text ); ?></pre>
								</div>
							</td>
						</tr>
							<?php
						}
						?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>
			<table class="wp-list-table widefat fixed striped unlimited-ai-log-table">
				<thead>
					<tr>
						<th class="unlimited-ai-log-col-id" style="width:50px"><?php esc_html_e( 'ID', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-log-col-title"><?php esc_html_e( 'Title (prompt)', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-log-col-action" style="width:120px">
							<?php if ( $sort_by_action ) : ?>
							<a class="unlimited-ai-log-sort-link is-active" href="<?php echo esc_url( $action_sort_clear_url ); ?>" title="<?php esc_attr_e( 'Clear action sort', 'sheetspilot' ); ?>">
								<?php esc_html_e( 'Action', 'sheetspilot' ); ?>
								<span class="unlimited-ai-log-sort-indicator" aria-hidden="true">▼</span>
							</a>
							<?php else : ?>
							<a class="unlimited-ai-log-sort-link" href="<?php echo esc_url( $action_sort_url ); ?>" title="<?php esc_attr_e( 'Sort by action (errors first)', 'sheetspilot' ); ?>">
								<?php esc_html_e( 'Action', 'sheetspilot' ); ?>
							</a>
							<?php endif; ?>
						</th>
						<th class="unlimited-ai-log-col-cell"><?php esc_html_e( 'Cell value (before)', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-log-col-response"><?php esc_html_e( 'Response value (after)', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-log-col-date" style="width:140px"><?php esc_html_e( 'Date', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-log-col-toggle" style="width:120px"></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( empty( $logs ) ) {
						$empty_message = $action_filter !== ''
							? sprintf(
								/* translators: %s: action type slug */
								__( 'No log entries found for action: %s.', 'sheetspilot' ),
								$action_filter
							)
							: __( 'No log entries yet. Use "Apply prompt" in the Posts Editor to generate entries.', 'sheetspilot' );
						?>
						<tr>
							<td colspan="7"><?php echo esc_html( $empty_message ); ?></td>
						</tr>
						<?php
					} else {
						foreach ( $logs as $row ) {
							$id            = isset( $row['id'] ) ? (int) $row['id'] : 0;
							$prompt        = isset( $row['prompt'] ) ? $row['prompt'] : '';
							$date          = isset( $row['date'] ) ? $row['date'] : '';
							$action        = isset( $row['response_action'] ) ? $row['response_action'] : '—';
							$title_str     = $this->truncateTitle( $prompt );
							$cell_value    = isset( $row['cell_value'] ) ? $row['cell_value'] : null;
							$request       = isset( $row['request'] ) ? $row['request'] : null;
							$response      = isset( $row['response'] ) ? $row['response'] : null;
							$response_data = isset( $row['response_data'] ) ? $row['response_data'] : null;
							$metadata      = isset( $row['metadata'] ) ? $row['metadata'] : null;
							$cell_display  = $this->getCellValueForDisplay( $cell_value );
							$request_data  = $this->getRequestDataForDisplay( $request, $prompt );
							$cell_preview  = $cell_display !== '' ? $this->previewForRow( $cell_display ) : '—';
							$response_preview = $this->previewForRow( $response_data );
							$test_url = '';
							if ( SheetsPilotGlobals::$isPro && $id > 0 ) {
								$test_url = SheetsPilotHelper::getViewUrl(
									SheetsPilotGlobals::VIEW_PROMPT_TESTER,
									'from_log=' . $id
								);
							}
							?>
							<tr class="unlimited-ai-log-row" data-log-id="<?php echo esc_attr( (string) $id ); ?>" data-log-action="<?php echo esc_attr( (string) $action ); ?>">
								<td><?php echo esc_html( (string) $id ); ?></td>
								<td class="unlimited-ai-log-title" title="<?php echo esc_attr( $prompt ); ?>"><?php echo esc_html( $title_str ); ?></td>
								<td><code><?php echo esc_html( $action ); ?></code></td>
								<td class="unlimited-ai-log-preview" title="<?php echo esc_attr( $cell_display ); ?>"><?php echo esc_html( $cell_preview ); ?></td>
								<td class="unlimited-ai-log-preview" title="<?php echo esc_attr( $this->formatForDisplay( $response_data ) ); ?>"><?php echo esc_html( $response_preview ); ?></td>
								<td><?php echo esc_html( $date ); ?></td>
								<td>
									<button type="button" class="button button-small unlimited-ai-log-toggle unlimited-ai-log-show" aria-expanded="false"><?php esc_html_e( 'Show full', 'sheetspilot' ); ?></button>
									<button type="button" class="button button-small unlimited-ai-log-toggle unlimited-ai-log-hide" aria-expanded="true" style="display:none;"><?php esc_html_e( 'Hide', 'sheetspilot' ); ?></button>
								</td>
							</tr>
							<tr class="unlimited-ai-log-detail" id="unlimited-ai-log-detail-<?php echo esc_attr( (string) $id ); ?>" data-log-id="<?php echo esc_attr( (string) $id ); ?>" style="display:none;">
								<td colspan="7" class="unlimited-ai-log-detail-cell">
									<div class="unlimited-ai-log-detail-inner">
										<div class="unlimited-ai-log-block">
											<div class="unlimited-ai-log-block-header">
												<strong><?php esc_html_e( 'Request data (text)', 'sheetspilot' ); ?></strong>
												<div class="unlimited-ai-log-block-actions">
													<?php if ( $test_url !== '' ) : ?>
													<a href="<?php echo esc_url( $test_url ); ?>" class="button button-small" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Test', 'sheetspilot' ); ?></a>
													<?php endif; ?>
													<button type="button" class="button button-small unlimited-ai-log-copy"><?php esc_html_e( 'Copy', 'sheetspilot' ); ?></button>
												</div>
											</div>
											<pre class="unlimited-ai-log-pre"><?php echo esc_html( $request_data !== '' ? $request_data : '—' ); ?></pre>
										</div>
										<div class="unlimited-ai-log-block">
											<div class="unlimited-ai-log-block-header">
												<strong><?php esc_html_e( 'Response data (text)', 'sheetspilot' ); ?></strong>
												<button type="button" class="button button-small unlimited-ai-log-copy"><?php esc_html_e( 'Copy', 'sheetspilot' ); ?></button>
											</div>
											<pre class="unlimited-ai-log-pre"><?php echo esc_html( $this->formatForDisplay( $response_data ) ); ?></pre>
										</div>
										<div class="unlimited-ai-log-block">
											<div class="unlimited-ai-log-block-header">
												<strong><?php esc_html_e( 'Cell value', 'sheetspilot' ); ?></strong>
												<button type="button" class="button button-small unlimited-ai-log-copy"><?php esc_html_e( 'Copy', 'sheetspilot' ); ?></button>
											</div>
											<pre class="unlimited-ai-log-pre"><?php echo esc_html( $cell_display !== '' ? $cell_display : '—' ); ?></pre>
										</div>
										<div class="unlimited-ai-log-block">
											<div class="unlimited-ai-log-block-header">
												<strong><?php esc_html_e( 'Request', 'sheetspilot' ); ?></strong>
												<button type="button" class="button button-small unlimited-ai-log-copy"><?php esc_html_e( 'Copy', 'sheetspilot' ); ?></button>
											</div>
											<pre class="unlimited-ai-log-pre"><?php echo esc_html( $this->formatForDisplay( $request ) ); ?></pre>
										</div>
										<div class="unlimited-ai-log-block">
											<div class="unlimited-ai-log-block-header">
												<strong><?php esc_html_e( 'Response', 'sheetspilot' ); ?></strong>
												<button type="button" class="button button-small unlimited-ai-log-copy"><?php esc_html_e( 'Copy', 'sheetspilot' ); ?></button>
											</div>
											<pre class="unlimited-ai-log-pre"><?php echo esc_html( $this->formatForDisplay( $response ) ); ?></pre>
										</div>
										<div class="unlimited-ai-log-block">
											<div class="unlimited-ai-log-block-header">
												<strong><?php esc_html_e( 'Metadata', 'sheetspilot' ); ?></strong>
												<button type="button" class="button button-small unlimited-ai-log-copy"><?php esc_html_e( 'Copy', 'sheetspilot' ); ?></button>
											</div>
											<pre class="unlimited-ai-log-pre"><?php echo esc_html( $this->formatForDisplay( $metadata ) ); ?></pre>
										</div>
									</div>
								</td>
							</tr>
							<?php
						}
					}
					?>
				</tbody>
			</table>
		</div>
		<style>
			.unlimited-ai-log-wrap { margin-top: 12px; }
			.unlimited-ai-log-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
			.unlimited-ai-log-action-filter { display: inline-flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 0; }
			.unlimited-ai-log-action-filter label { font-weight: 600; }
			.unlimited-ai-log-filter-note { margin-top: -8px; }
			.unlimited-ai-session-log-panel { margin: 0 0 20px; }
			.unlimited-ai-session-log-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
			.unlimited-ai-session-log-header h2 { margin: 0 0 4px; }
			.unlimited-ai-session-log-header .unlimited-ai-log-description { margin-bottom: 0; }
			.unlimited-ai-session-log-block-header { display: flex; justify-content: flex-end; margin-bottom: 6px; }
			.unlimited-ai-session-log-data { margin: 0; padding: 8px 10px; background: #fff; border: 1px solid #dcdcde; border-radius: 4px; font-size: 12px; line-height: 1.5; white-space: pre-wrap; word-break: break-word; max-height: 280px; overflow: auto; font-family: Consolas, Monaco, monospace; }
			/* Keep request/response log readable even on RTL admin locales. */
			.unlimited-ai-log-wrap,
			.unlimited-ai-log-wrap table,
			.unlimited-ai-log-wrap th,
			.unlimited-ai-log-wrap td,
			.unlimited-ai-log-wrap pre {
				direction: ltr;
				text-align: left;
			}
			.unlimited-ai-log-description { margin-bottom: 16px; color: #50575e; }
			.unlimited-ai-log-table { table-layout: fixed; }
			.unlimited-ai-log-title { overflow: hidden; text-overflow: ellipsis; max-width: 0; }
			.unlimited-ai-log-preview { overflow: hidden; text-overflow: ellipsis; max-width: 0; font-size: 12px; }
			.unlimited-ai-log-sort-link { text-decoration: none; color: inherit; }
			.unlimited-ai-log-sort-link:hover { color: #2271b1; }
			.unlimited-ai-log-sort-link.is-active { color: #2271b1; font-weight: 600; }
			.unlimited-ai-log-sort-indicator { font-size: 10px; margin-left: 2px; }
			.unlimited-ai-log-detail-cell { background: #f6f7f7; vertical-align: top; padding: 0; }
			.unlimited-ai-log-detail-inner { padding: 12px 16px; }
			.unlimited-ai-log-block { margin-bottom: 16px; }
			.unlimited-ai-log-block:last-child { margin-bottom: 0; }
			.unlimited-ai-log-block-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
			.unlimited-ai-log-block-actions { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; }
			.unlimited-ai-log-copy { flex: 0 0 auto; }
			.unlimited-ai-log-pre { margin: 8px 0 0; padding: 12px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; overflow: auto; max-height: 320px; font-size: 12px; white-space: pre-wrap; word-break: break-all; }
		</style>
		<script>
		(function(){
			var sessionShowLabel = <?php echo wp_json_encode( __( 'Show session log', 'sheetspilot' ) ); ?>;
			var sessionHideLabel = <?php echo wp_json_encode( __( 'Hide session log', 'sheetspilot' ) ); ?>;
			var showLabel = <?php echo wp_json_encode( __( 'Show full', 'sheetspilot' ) ); ?>;
			var hideLabel = <?php echo wp_json_encode( __( 'Hide', 'sheetspilot' ) ); ?>;
			var copyLabel = <?php echo wp_json_encode( __( 'Copy', 'sheetspilot' ) ); ?>;
			var copiedLabel = <?php echo wp_json_encode( __( 'Copied', 'sheetspilot' ) ); ?>;
			var copyAllLabel = <?php echo wp_json_encode( __( 'Copy all', 'sheetspilot' ) ); ?>;

			function getCopyTextFromButton(btn) {
				var block = btn.closest('.unlimited-ai-log-block, .unlimited-ai-session-log-block');
				if (!block) return '';
				var pre = block.querySelector('.unlimited-ai-log-pre, .unlimited-ai-session-log-data');
				return pre ? (pre.textContent || '') : '';
			}

			function getSessionLogCopyAllText() {
				var lines = [];
				document.querySelectorAll('.unlimited-ai-session-log-table tbody tr').forEach(function(row) {
					var cells = row.querySelectorAll('td');
					if (cells.length < 4) return;
					var index = (cells[0].textContent || '').trim();
					var action = (cells[1].textContent || '').trim();
					var time = (cells[2].textContent || '').trim();
					var pre = row.querySelector('.unlimited-ai-session-log-data');
					var data = pre ? (pre.textContent || '').trim() : '';
					if (!index && !action && !data) return;
					lines.push(
						'#' + index + ' | ' + action + ' | ' + time + '\n' + data
					);
				});
				return lines.join('\n\n');
			}

			function toggleDetail(logId, open) {
				var detail = document.getElementById('unlimited-ai-log-detail-' + logId);
				var row = document.querySelector('.unlimited-ai-log-row[data-log-id="' + logId + '"]');
				if (!detail || !row) return;
				var showBtn = row.querySelector('.unlimited-ai-log-show');
				var hideBtn = row.querySelector('.unlimited-ai-log-hide');
				detail.style.display = open ? 'table-row' : 'none';
				if (showBtn) { showBtn.style.display = open ? 'none' : 'inline-block'; showBtn.setAttribute('aria-expanded', open ? 'true' : 'false'); }
				if (hideBtn) { hideBtn.style.display = open ? 'inline-block' : 'none'; hideBtn.setAttribute('aria-expanded', open ? 'true' : 'false'); }
			}

			function copyText(text) {
				if (navigator.clipboard && window.isSecureContext) {
					return navigator.clipboard.writeText(text);
				}

				var textarea = document.createElement('textarea');
				textarea.value = text;
				textarea.setAttribute('readonly', '');
				textarea.style.position = 'fixed';
				textarea.style.left = '-9999px';
				document.body.appendChild(textarea);
				textarea.select();

				return new Promise(function(resolve, reject) {
					try {
						document.execCommand('copy') ? resolve() : reject();
					} catch (err) {
						reject(err);
					} finally {
						document.body.removeChild(textarea);
					}
				});
			}

			function setCopiedState(btn) {
				btn.textContent = copiedLabel;
				window.setTimeout(function() {
					btn.textContent = copyLabel;
				}, 1200);
			}

			document.querySelectorAll('.unlimited-ai-session-log-toggle').forEach(function(btn){
				btn.addEventListener('click', function(){
					var panel = document.querySelector('.unlimited-ai-session-log-panel');
					if (!panel) return;
					var isOpen = panel.style.display !== 'none';
					panel.style.display = isOpen ? 'none' : 'block';
					btn.textContent = isOpen ? sessionShowLabel : sessionHideLabel;
					btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
				});
			});

			var copyAllBtn = document.querySelector('.unlimited-ai-session-log-copy-all');
			if (copyAllBtn) {
				copyAllBtn.addEventListener('click', function() {
					var text = getSessionLogCopyAllText();
					if (!text) return;
					copyText(text).then(function() {
						copyAllBtn.textContent = copiedLabel;
						window.setTimeout(function() {
							copyAllBtn.textContent = copyAllLabel;
						}, 1200);
					});
				});
			}

			var logWrap = document.querySelector('.unlimited-ai-log-wrap');
			if (logWrap) {
				logWrap.addEventListener('click', function(e) {
					var copyBtn = e.target.closest('.unlimited-ai-log-copy');
					if (!copyBtn) return;
					var text = getCopyTextFromButton(copyBtn);
					if (!text) return;
					copyText(text).then(function() {
						setCopiedState(copyBtn);
					});
				});
			}

			document.querySelectorAll('.unlimited-ai-log-table').forEach(function(table){
				table.addEventListener('click', function(e){
					var btn = e.target.closest('.unlimited-ai-log-toggle');
					if (btn) {
						var row = btn.closest('.unlimited-ai-log-row');
						var id = row && row.getAttribute('data-log-id');
						if (id) {
							var detail = document.getElementById('unlimited-ai-log-detail-' + id);
							var isOpen = detail && detail.style.display !== 'none';
							toggleDetail(id, !isOpen);
						}
					}
				});
			});
		})();
		</script>
		<?php
	}
}
