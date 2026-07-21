<?php
/**
 * Prompt History view: table of last 100 prompts (text, description, is_latest, is_saved, is_favorite, date, etc.).
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

class SheetsPilot_PluginViewPromptHistory {

	const TEXT_PREVIEW_LEN = 80;

	private function truncate( $text, $len = self::TEXT_PREVIEW_LEN ) {
		$text = trim( (string) $text );
		if ( $text === '' ) {
			return '—';
		}
		if ( strlen( $text ) <= $len ) {
			return $text;
		}
		return substr( $text, 0, $len ) . '…';
	}

	public function __construct() {
		$this->putViewHtml();
	}

	private function putViewHtml() {
		$prompts = SheetsPilot_PromptHistory::getLast( 100 );
		$title = __( 'Prompt History', 'sheetspilot' );
		$log_url = SheetsPilotHelper::getViewUrl( SheetsPilotGlobals::VIEW_LOG );
		?>
		<div class="wrap unlimited-ai-prompt-history-wrap">
			<h1><?php echo esc_html( $title ); ?></h1>
			<p class="unlimited-ai-prompt-history-description"><?php esc_html_e( 'Last 100 prompts saved from successful runs. Description is the short label from the AI.', 'sheetspilot' ); ?></p>
			<p><a href="<?php echo esc_url( $log_url ); ?>" class="button"><?php esc_html_e( 'Back to Log', 'sheetspilot' ); ?></a></p>
			<table class="wp-list-table widefat fixed striped unlimited-ai-prompt-history-table">
				<thead>
					<tr>
						<th class="unlimited-ai-ph-col-no" style="width:40px"><?php esc_html_e( 'No.', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-ph-col-id" style="width:50px"><?php esc_html_e( 'ID', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-ph-col-text"><?php esc_html_e( 'Prompt', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-ph-col-description"><?php esc_html_e( 'Short description', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-ph-col-posttype" style="width:90px"><?php esc_html_e( 'Post type', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-ph-col-postid" style="width:70px"><?php esc_html_e( 'Post ID', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-ph-col-flags" style="width:100px"><?php esc_html_e( 'Latest', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-ph-col-flags" style="width:80px"><?php esc_html_e( 'Saved', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-ph-col-flags" style="width:80px"><?php esc_html_e( 'Favorite', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-ph-col-date" style="width:140px"><?php esc_html_e( 'Date', 'sheetspilot' ); ?></th>
						<th class="unlimited-ai-ph-col-comments" style="width:120px"><?php esc_html_e( 'Comments', 'sheetspilot' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( empty( $prompts ) ) {
						?>
						<tr>
							<td colspan="11"><?php esc_html_e( 'No prompt history yet. Use "Apply prompt" in the Posts Editor (with a successful JSON response) to generate entries.', 'sheetspilot' ); ?></td>
						</tr>
						<?php
					} else {
						$serial = 0;
						foreach ( $prompts as $row ) {
							$serial++;
							$id          = isset( $row['id'] ) ? (int) $row['id'] : 0;
							$text        = isset( $row['text'] ) ? $row['text'] : '';
							$description = isset( $row['description'] ) ? $row['description'] : '';
							$is_latest   = isset( $row['is_latest'] ) ? (int) $row['is_latest'] : 0;
							$is_saved    = isset( $row['is_saved'] ) ? (int) $row['is_saved'] : 0;
							$is_favorite = isset( $row['is_favorite'] ) ? (int) $row['is_favorite'] : 0;
							$userid      = isset( $row['userid'] ) ? $row['userid'] : '';
							$post_type   = isset( $row['post_type'] ) ? $row['post_type'] : '';
							$postid      = isset( $row['postid'] ) ? $row['postid'] : '';
							$date        = isset( $row['date'] ) ? $row['date'] : '';
							$comments    = isset( $row['comments'] ) ? $row['comments'] : '';
							?>
							<tr>
								<td><?php echo esc_html( (string) $serial ); ?></td>
								<td><?php echo esc_html( (string) $id ); ?></td>
								<td class="unlimited-ai-ph-text" title="<?php echo esc_attr( $text ); ?>"><?php echo esc_html( $this->truncate( $text ) ); ?></td>
								<td class="unlimited-ai-ph-description" title="<?php echo esc_attr( $description ); ?>"><?php echo esc_html( $this->truncate( $description, 60 ) ); ?></td>
								<td><?php echo $post_type !== '' ? esc_html( $post_type ) : '—'; ?></td>
								<td><?php echo $postid !== '' && $postid !== null ? esc_html( (string) $postid ) : '—'; ?></td>
								<td><?php echo $is_latest ? '✓' : '—'; ?></td>
								<td><?php echo $is_saved ? '✓' : '—'; ?></td>
								<td><?php echo $is_favorite ? '✓' : '—'; ?></td>
								<td><?php echo esc_html( $date ); ?></td>
								<td class="unlimited-ai-ph-comments" title="<?php echo esc_attr( $comments ); ?>"><?php echo esc_html( $this->truncate( $comments, 40 ) ); ?></td>
							</tr>
							<?php
						}
					}
					?>
				</tbody>
			</table>
		</div>
		<style>
			.unlimited-ai-prompt-history-wrap { margin-top: 12px; }
			.unlimited-ai-prompt-history-description { margin-bottom: 12px; color: #50575e; }
			.unlimited-ai-prompt-history-table { table-layout: fixed; }
			.unlimited-ai-ph-description, .unlimited-ai-ph-text { overflow: hidden; text-overflow: ellipsis; max-width: 0; }
			.unlimited-ai-ph-comments { overflow: hidden; text-overflow: ellipsis; max-width: 0; font-size: 12px; }
		</style>
		<?php
	}
}

new SheetsPilot_PluginViewPromptHistory();
