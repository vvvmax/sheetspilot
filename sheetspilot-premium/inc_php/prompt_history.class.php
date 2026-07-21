<?php
/**
 * Saves each run prompt to the prompts table. Same text updates date.
 * Each insert/update sets is_latest=1 for that row. Rotation keeps the 30 most recent (is_favorite=0, is_saved=0); deletes only when count exceeds rotation.
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

class SheetsPilot_PromptHistory {

	/** Max length for description (VARCHAR 255). */
	const DESCRIPTION_MAX_LEN = 255;

	/** Number of words to show in label when no description (prompt preview). */
	const LABEL_PREVIEW_WORDS = 6;

	/**
	 * Record that a prompt was run: save or update by exact text, set is_latest=1 for this row, then rotate (keep N last).
	 *
	 * @param string      $text        Prompt text (exact match for update).
	 * @param string|null $description Instruction summary from API (instruction_summary); saved to description column, truncated to fit DB.
	 * @param string|null $post_type   Post type in which the prompt was used (if any).
	 * @param int|null    $postid      Post ID in which the prompt was used (if any).
	 */
	/**
	 * Normalize prompt text for deduplication: trim and collapse runs of whitespace to a single space.
	 *
	 * @param string $text Raw prompt text.
	 * @return string Normalized text.
	 */
	public static function normalizePromptText( $text ) {
		$text = trim( (string) $text );
		if ( $text === '' ) {
			return '';
		}
		return preg_replace( '/\s+/', ' ', $text );
	}

	public static function recordPromptRun( $text, $description = null, $post_type = null, $postid = null, $prompt_type = false ) {
		$text = self::normalizePromptText( $text );
		if ( $text === '' ) {
			return;
		}
		global $wpdb;
		$table = SheetsPilotGlobals::$tablePrompts;
		$userid = get_current_user_id();
		$userid = $userid ? $userid : null;

		$desc = $description !== null && $description !== '' ? trim( (string) $description ) : null;
		if ( $desc !== null && strlen( $desc ) > self::DESCRIPTION_MAX_LEN ) {
			$desc = substr( $desc, 0, self::DESCRIPTION_MAX_LEN );
		}

		$post_type_val = ( $post_type !== null && $post_type !== '' ) ? sanitize_key( (string) $post_type ) : null;
		$postid_val    = ( $postid !== null && $postid !== '' ) ? absint( $postid ) : null;
		if ( $postid_val === 0 ) {
			$postid_val = null;
		}

	 
		$existing_id = $wpdb->get_var(			
			$wpdb->prepare(				
				"SELECT id FROM {$table} WHERE text = %s LIMIT 1",
				$text
			)
		);
		 

		// Each time we record, mark this row as latest (is_latest=1). Do not clear others — every recorded prompt in the 30 is “latest”.
		if ( $existing_id ) {
			$update_data = array(
				'date'      => current_time( 'mysql' ),
				'post_type' => $post_type_val,
				'postid'    => $postid_val,
				'is_latest' => 1,
			);
			$update_format = array( '%s', '%s', '%d', '%d' );
			if ( $desc !== null ) {
				$update_data['description'] = $desc;
				$update_format[] = '%s';
			}

			 
			$wpdb->update(
				$table,
				$update_data,
				array( 'id' => (int) $existing_id ),
				$update_format,
				array( '%d' )
			);
			 
		} else {

			$wpdb->insert(
				$table,
				array(
					'text'        => $text,
					'description' => $desc,
					'is_latest'   => 1,
					'is_saved'    => 0,
					'is_favorite' => 0,
					'userid'      => $userid,
					'post_type'   => $post_type_val,
					'postid'      => $postid_val,
					'date'        => current_time( 'mysql' ),
					'prompt_type' => $prompt_type
				),
				array( '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s' )
			);
		 
		}

		self::rotateNonKept();
	}

	/**
	 * Get the last N prompt history rows for display (e.g. in Prompt History view).
	 *
	 * @param int $limit Max number of rows (default 100).
	 * @return array List of rows (associative arrays).
	 */
	public static function getLast( $limit = 100 ) {
		global $wpdb;
		$table = SheetsPilotGlobals::$tablePrompts;
		$limit = absint( $limit );
		if ( $limit < 1 ) {
			$limit = 100;
		}


		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, text, description, is_latest, is_saved, is_favorite, userid, post_type, postid, date, comments FROM {$table} ORDER BY date DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
		 
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Build label for dropdown: use description if present, else first N words of prompt + "…".
	 *
	 * @param string $text        Full prompt text.
	 * @param string $description Description from DB (may be empty).
	 * @return string Label string.
	 */
	private static function buildDropdownLabel( $text, $description ) {
		$desc = $description !== '' ? trim( (string) $description ) : '';
		if ( $desc !== '' ) {
			return $desc;
		}
		$text = trim( (string) $text );
		if ( $text === '' ) {
			return __( '(No description)',  'sheetspilot' );
		}
		$words = preg_split( '/\s+/', $text, self::LABEL_PREVIEW_WORDS + 1, PREG_SPLIT_NO_EMPTY );
		$preview = implode( ' ', array_slice( $words, 0, self::LABEL_PREVIEW_WORDS ) );
		return $preview . '…';
	}

	/**
	 * Convert raw rows to dropdown items (deduplicated, formatted).
	 *
	 * @param array $rows    Rows from DB (id, text, description, is_saved).
	 * @param bool  $is_saved Force is_saved for all items (e.g. saved dropdown).
	 * @return array List of { id, text, label, is_saved }.
	 */
	private static function rowsToDropdownItems( $rows, $is_saved = null ) {
		$list      = array();
		$seen_text = array();
		foreach ( $rows as $row ) {
			$text     = isset( $row['text'] ) ? $row['text'] : '';
			$text_key = self::normalizePromptText( $text );
			if ( $text_key === '' || isset( $seen_text[ $text_key ] ) ) {
				continue;
			}
			$seen_text[ $text_key ] = true;
			$id                     = isset( $row['id'] ) ? $row['id'] : '';
			$desc                   = isset( $row['description'] ) ? $row['description'] : '';
			$label                  = self::buildDropdownLabel( $text, $desc );
			$saved                  = $is_saved !== null ? (bool) $is_saved : ! empty( $row['is_saved'] );
			$prompt_type            = isset( $row['prompt_type'] ) ? $row['prompt_type'] : '';
			$list[]                 = array( 'id' => (string) $id, 'text' => $text, 'label' => $label, 'is_saved' => $saved, 'prompt_type' => $prompt_type );
		}
		return $list;
	}

	/**
	 * Get latest prompts formatted for Quick Actions dropdown (deduplicated by text, id/text/label).
	 * Uses SheetsPilotGlobals::PROMPT_HISTORY_ROTATION for limit.
	 *
	 * @return array List of associative arrays: id, text, label, is_saved.
	 */
	public static function getLastForDropdown() {
		$limit = (int) SheetsPilotGlobals::PROMPT_HISTORY_ROTATION;
		$rows  = self::getLast( $limit );
		return self::rowsToDropdownItems( $rows );
	}

	/**
	 * Get saved prompts (is_saved=1) formatted for Saved Prompts dropdown.
	 * Same format as getLastForDropdown: id, text, label, is_saved.
	 * Uses SheetsPilotGlobals::PROMPT_HISTORY_ROTATION for limit.
	 *
	 * @return array List of associative arrays: id, text, label, is_saved.
	 */
	public static function getSavedForDropdown() {
		global $wpdb;
		$table = SheetsPilotGlobals::$tablePrompts;
		$limit = (int) SheetsPilotGlobals::PROMPT_HISTORY_ROTATION;
		
		 
		$rows  = $wpdb->get_results(			
			$wpdb->prepare(				
				"SELECT id, text, description, is_saved, prompt_type FROM {$table} WHERE is_saved = 1 ORDER BY date DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return self::rowsToDropdownItems( is_array( $rows ) ? $rows : array(), true );
	}

	/**
	 * Render Quick Actions dropdown options as HTML (for use in AJAX refresh).
	 *
	 * @return string HTML markup for dropdown options.
	 */
	public static function renderDropdownOptionsHtml() {
		$items = self::getLastForDropdown();
		ob_start();
		foreach ( $items as $item ) {
			$heart_class = 'unlimitedai-plugin__quick-actions-option-heart' . ( ! empty( $item['is_saved'] ) ? ' is-filled' : '' );
			?>
			<div class="unlimitedai-plugin__quick-actions-option" role="option" tabindex="-1" data-id="<?php echo esc_attr( $item['id'] ); ?>">
				<span class="unlimitedai-plugin__quick-actions-option-label"><?php echo esc_html( $item['label'] ); ?></span>
				<span class="<?php echo esc_attr( $heart_class ); ?>" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg></span>
			</div>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * Render Saved Prompts dropdown options as HTML (for use in AJAX refresh).
	 *
	 * @return string HTML markup for dropdown options.
	 */
	public static function renderSavedDropdownOptionsHtml() {
		$items = self::getSavedForDropdown();
		ob_start();
		foreach ( $items as $item ) {
			?>
			<div class="unlimitedai-plugin__quick-actions-option" role="option" tabindex="-1" data-id="<?php echo esc_attr( $item['id'] ); ?>">
				<span class="unlimitedai-plugin__quick-actions-option-label"><?php echo esc_html( $item['label'] ); ?></span>
				<span class="unlimitedai-plugin__quick-actions-option-remove" aria-hidden="true" aria-label="<?php echo esc_attr__( 'Remove from saved',  'sheetspilot' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></span>
			</div>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * Saved prompts as cell/sidebar text context-menu sub-items (for AJAX refresh after save/remove).
	 *
	 * @return array<int, array{action: string, text: string, prompt: string}>
	 */
	public static function getSavedContextSubItemsForAjax() {
		$out   = array();
		$items = self::getSavedForDropdown();
		if ( ! is_array( $items ) ) {
			return $out;
		}
		foreach ( $items as $item ) {
			$id    = isset( $item['id'] ) ? (string) $item['id'] : '';
			$text  = isset( $item['text'] ) ? (string) $item['text'] : '';
			$label = isset( $item['label'] ) ? (string) $item['label'] : '';
			$prompt_type = isset( $item['prompt_type'] ) ? (string) $item['prompt_type'] : '';
			if ( $id === '' || $text === '' || $label === '' ) {
				continue;
			}
			$out[] = array(
				'id' => $id,
				'action' => 'saved-prompt-' . $id,
				'text'   => $label,
				'prompt' => $text,
				'prompt_type' => $prompt_type
			);
		}
		return $out;
	}

	/**
	 * Mark a prompt as saved (is_saved=1) by id.
	 *
	 * @param int|string $id Prompt row id.
	 * @return bool True if row was updated, false otherwise.
	 */
	public static function setSaved( $data ) {
 
		global $wpdb;
		$table = SheetsPilotGlobals::$tablePrompts;

		$userid = get_current_user_id();
		$userid = $userid ? $userid : null;

		$prompt_id = (int)$data['prompt_id'];

		$text = self::normalizePromptText( sanitize_text_field( $data['prompt_content'] ) );

		$description = sanitize_text_field( $data['prompt_title'] );
		$desc = $description !== null && $description !== '' ? trim( (string) $description ) : null;
		if ( $desc !== null && strlen( $desc ) > self::DESCRIPTION_MAX_LEN ) {
			$desc = substr( $desc, 0, self::DESCRIPTION_MAX_LEN );
		}

		$post_type = sanitize_text_field( $data['post_type'] );
		$post_type_val = ( $post_type !== null && $post_type !== '' ) ? sanitize_key( (string) $post_type ) : null;

		// check if prompt exists
		$prompt_exists  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE id = %d AND is_saved = 1", $prompt_id ) );
		$existed_prompt_data  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE id = %d AND is_saved = 1", $prompt_id ) );


		if( $prompt_exists == 0 ){

		 
			$wpdb->insert(
				$table,
				array(
					'text'        => $text,
					'description' => $desc,
					'is_latest'   => 0,
					'is_saved'    => 1,
					'is_favorite' => 0,
					'userid'      => $userid,
					'post_type'   => $post_type_val,
					'postid'      => null,
					'date'        => current_time( 'mysql' ),
					'prompt_type' => sanitize_text_field( $data['prompt_type'] ),
				),
				array( '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s' )
			);
		 
		}else{
			 
			$wpdb->update(
				$table,
				[
					'text'        => $text,
					'description' => $desc,
				],
				array( 'id' => (int) $prompt_id ),
				[ '%s', '%s' ],
				array( '%d' )
			);
			 
		}
		//return $updated !== false;
	}

	/**
	 * Mark a prompt as not saved (is_saved=0) by id.
	 *
	 * @param int|string $id Prompt row id.
	 * @return bool True if row was updated, false otherwise.
	 */
	public static function setUnsaved( $id ) {
		$id = absint( $id );
		if ( $id < 1 ) {
			return false;
		}
		global $wpdb;
		$table = SheetsPilotGlobals::$tablePrompts;

		$updated = $wpdb->delete(
			$table,
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);
		 
		return $updated !== false;
	}

	/**
	 * Get prompt history for the in-sidebar panel: supports filter (all|saved) and optional search on text/description.
	 * Returns items with id, text, date, is_saved, time_ago. Also returns totalRecent and totalSaved counts.
	 *
	 * @param int         $limit  Max rows (default 100).
	 * @param string     $filter 'all' or 'saved'.
	 * @param string|null $search Optional search string (matches text and description).
	 * @return array { items: array, totalRecent: int, totalSaved: int }
	 */
	public static function getForPanel( $limit = 100, $filter = 'all', $search = null ) {
		global $wpdb;
		$table  = SheetsPilotGlobals::$tablePrompts;
		$limit  = absint( $limit );
		if ( $limit < 1 ) {
			$limit = 100;
		}
		$search = $search !== null && $search !== '' ? trim( (string) $search ) : null;
		$where  = array( '1=1' );
		$params = array();

		if ( $filter === 'saved' ) {
			$where[] = 'is_saved = 1';
		}
		if ( $search !== null && $search !== '' ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(text LIKE %s OR (description IS NOT NULL AND description != "" AND description LIKE %s))';
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$order_sql = 'ORDER BY date DESC';

		// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total_recent = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_latest = 1" );// phpcs:ignore

		// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input, safe static query
		$total_saved  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_saved = 1" );// phpcs:ignore

		if ( count( $params ) > 0 ) {

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe (prefix-based)
			$query = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe (wpdb prefix)
				"SELECT id, text, description, is_saved, date, prompt_type FROM {$table} WHERE {$where_sql} {$order_sql} LIMIT %d",
				array_merge( $params, array( $limit ) )
			);
		} else {

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe (wpdb prefix)
			$query = $wpdb->prepare( "SELECT id, text, description, is_saved, date, prompt_type FROM {$table} WHERE {$where_sql} {$order_sql} LIMIT %d", $limit );// phpcs:ignore
		}


		$rows = $wpdb->get_results( $query, ARRAY_A );// phpcs:ignore


		$rows = is_array( $rows ) ? $rows : array();
		$items = array();
		$now   = time();
		foreach ( $rows as $row ) {
			$date_str = isset( $row['date'] ) ? $row['date'] : '';
			$time_ago = '';
			if ( $date_str ) {
				$ts = strtotime( $date_str );
				$time_ago = $ts ? human_time_diff( $ts, $now ) . ' ' . __( 'ago',  'sheetspilot' ) : $date_str;
			}
			$text = isset( $row['text'] ) ? $row['text'] : '';
			$prompt_type = isset( $row['prompt_type'] ) ? $row['prompt_type'] : '';
			$desc = isset( $row['description'] ) ? trim( (string) $row['description'] ) : '';
			$title = $desc !== '' ? $desc : ( strlen( $text ) > 50 ? substr( $text, 0, 50 ) . '…' : $text );
			$items[] = array(
				'id'       => isset( $row['id'] ) ? (string) $row['id'] : '',
				'text'     => $text,
				'title'    => $title,
				'date'     => $date_str,
				'is_saved' => ! empty( $row['is_saved'] ),
				'time_ago' => $time_ago,
				'prompt_type' => $prompt_type,
			);
		}
		return array(
			'items'        => $items,
			'totalRecent'  => $total_recent,
			'totalSaved'   => $total_saved,
		);
	}

	/**
	 * Delete a single prompt from history by id.
	 *
	 * @param int|string $id Prompt row id.
	 * @return bool True if deleted, false otherwise.
	 */
	public static function deleteById( $id ) {
		$id = absint( $id );
		if ( $id < 1 ) {
			return false;
		}
		global $wpdb;
		$table = SheetsPilotGlobals::$tablePrompts;
		
		$deleted = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore
		return $deleted !== false;
	}

	/**
	 * Delete all prompt history rows.
	 *
	 * @return bool True on success.
	 */
	public static function clearAll() {
		global $wpdb;
		$table = SheetsPilotGlobals::$tablePrompts;

		// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input, safe static query
		$wpdb->query( "DELETE FROM {$table}" );// phpcs:ignore
		return true;
	}

	/**
	 * Delete old rows only when count of non-kept (is_favorite=0, is_saved=0) exceeds rotation amount. Keep the 30 latest.
	 */
	private static function rotateNonKept() {
		global $wpdb;
		$table = SheetsPilotGlobals::$tablePrompts;
		$rotation = (int) SheetsPilotGlobals::PROMPT_HISTORY_ROTATION;

		// phpcs:disable
		$count = (int) $wpdb->get_var(			
			"SELECT COUNT(*) FROM {$table} WHERE is_favorite = 0 AND is_saved = 0"
		);
		
		if ( $count <= $rotation ) {
			return;
		}

		// phpcs:disable
		$cutoff_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT date FROM {$table} WHERE is_favorite = 0 AND is_saved = 0 ORDER BY date DESC LIMIT 1 OFFSET %d",
				$rotation - 1
			),
			ARRAY_A
		);
	
		if ( empty( $cutoff_row ) || empty( $cutoff_row['date'] ) ) {
			return;
		}
		$cutoff_date = $cutoff_row['date'];

		// phpcs:disable
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE is_favorite = 0 AND is_saved = 0 AND date < %s",
				$cutoff_date
			)
		);
	
	}
}
