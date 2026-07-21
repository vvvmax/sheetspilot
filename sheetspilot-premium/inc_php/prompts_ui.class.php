<?php

/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if (! defined('ABSPATH')) exit;
if (! defined('SHEETSPILOT_INC')) {
	die('restricted access');
}

class SheetsPilot_PromptsUI
{

	const OPTION_CELLRULES_PREFIX = 'unlimited_ui_cellrules_';

	/**
	 * Option name for cell rules of a post type.
	 *
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	public static function get_cell_rules_option_name($post_type)
	{
		$name = self::OPTION_CELLRULES_PREFIX . sanitize_key($post_type);
		return $name;
	}

	/**
	 * Get all cell rules for a post type (column => prompt), plus optional keys:
	 * "{column}__aspect_ratio", "{column}__quality", "{column}__format", "{column}__resolution", "{column}__targets" (JSON array of field keys).
	 *
	 * @param string $post_type Post type slug.
	 * @return array<string, string>
	 */
	public static function get_cell_rules($post_type)
	{
		$option_name = self::get_cell_rules_option_name($post_type);
		$option     = get_option($option_name, array());
		$rules      = is_array($option) ? $option : array();
		return $rules;
	}

	/**
	 * Normalize a column/field key for cell rules storage and lookups.
	 * Delegates to SheetsPilotFunctions::normalize_cell_rule_field_key().
	 *
	 * @param string $key Raw key from the editor or AJAX.
	 * @return string Empty string if nothing usable remains.
	 */
	public static function normalize_cell_rule_field_key($key)
	{
		$result = SheetsPilotFunctions::normalize_cell_rule_field_key($key);
		return $result;
	}

	/**
	 * Save one cell rule: load existing array, set/update the column prompt, save back.
	 * Optional image column meta uses keys "{column}__aspect_ratio", "{column}__quality", "{column}__format", and "{column}__resolution".
	 *
	 * @param string              $post_type      Post type slug.
	 * @param string              $column         Column name.
	 * @param string              $prompt         Prompt text for this column.
	 * @param array<string,mixed>|null $image_features Optional. Keys: aspect_ratio, quality, format, resolution (from AI Column Settings).
	 * @param array<int,string>|null  $target_columns Optional. Field keys for "{column}__targets" (JSON); omit or empty to clear.
	 * @return array{ok:bool,debug?:string} ok=true on success; on failure ok=false and debug names the step (for logging / AJAX).
	 */
	public static function save_cell_rule($post_type, $column, $prompt, $image_features = null, $target_columns = null, $apply_prompt_on_paste = null, $auto_apply_response = null)
	{

		$post_type = sanitize_key($post_type);
		$column    = SheetsPilotFunctions::normalize_cell_rule_field_key($column);

		if ($post_type === '' || $column === '') {
			$result = array(
				'ok'    => false,
				'debug' => 'save_cell_rule: validation (post_type or column empty after normalize)',
			);
			return $result;
		}

		$rules            = self::get_cell_rules($post_type);
		$rules[$column] = sanitize_textarea_field($prompt);

		$paste_key      = $column . '__prompt_on_paste';
		$paste_enabled  = ($apply_prompt_on_paste === true || $apply_prompt_on_paste === 'true' || $apply_prompt_on_paste === '1' || $apply_prompt_on_paste === 1);
		if ($paste_enabled) {
			$rules[$paste_key] = 'true';
		} else {
			unset($rules[$paste_key]);
		}



		$auto_apply_response_colname      = $column . '__auto_apply_response';
		$auto_apply_enabled  = ($auto_apply_response === true || $auto_apply_response === 'true' || $auto_apply_response === '1' || $auto_apply_response === 1);
		if ($auto_apply_enabled) {
			$rules[$auto_apply_response_colname] = 'true';
		} else {
			unset($rules[$auto_apply_response_colname]);
		}



		if (is_array($image_features)) {
			$ar_key      = $column . '__aspect_ratio';
			$q_key       = $column . '__quality';
			$f_key       = $column . '__format';
			$res_key     = $column . '__resolution';
			// Keep server-side whitelist in sync with the aspect ratio UI list.
			$ratio_allow = class_exists('SheetsPilot_PluginGeneralSettings')
				? SheetsPilot_ImageProcessing::getAllowedAspectRatios()
				: array('auto', '1:1', '3:2', '2:3');
			// OpenAI Images API supports quality: low|medium|high|auto; UI also allows "default".
			$quality_allow = array('default', 'low', 'medium', 'high');
			// Back-compat: old UI stored 0.5K/1K/1.5K/2K; normalize into low/medium/high.
			$quality_map = array(
				'0.5k' => 'low',
				'1k'   => 'medium',
				'1.5k' => 'high',
				'2k'   => 'high',
			);
			$format_allow = array('default', 'png', 'jpeg', 'webp');
			$resolution_allow = class_exists('SheetsPilot_ImageProcessing')
				? SheetsPilot_ImageProcessing::getAllowedImageResolutions()
				: array('default', '1k', '2k', '3k', '4k');

			if (array_key_exists('aspect_ratio', $image_features)) {
				$ar = sanitize_text_field((string) $image_features['aspect_ratio']);
				if ($ar === '') {
					unset($rules[$ar_key]);
				} elseif (in_array($ar, $ratio_allow, true)) {
					$rules[$ar_key] = $ar;
				}
			}

			if (array_key_exists('quality', $image_features)) {
				$q = sanitize_text_field((string) $image_features['quality']);
				if ($q === '') {
					unset($rules[$q_key]);
				} else {
					$q_norm = strtolower(trim((string) $q));
					if (isset($quality_map[$q_norm])) {
						$q_norm = $quality_map[$q_norm];
					}
					if ($q_norm === 'default') {
						unset($rules[$q_key]);
					} elseif (in_array($q_norm, $quality_allow, true)) {
						$rules[$q_key] = $q_norm;
					}
				}
			}
			if (array_key_exists('format', $image_features)) {
				$f = sanitize_text_field((string) $image_features['format']);
				if ($f === '') {
					unset($rules[$f_key]);
				} else {
					$f_norm = strtolower(trim((string) $f));
					if ($f_norm === 'jpg') {
						$f_norm = 'jpeg';
					}
					if ($f_norm === 'default') {
						unset($rules[$f_key]);
					} elseif (in_array($f_norm, $format_allow, true)) {
						$rules[$f_key] = $f_norm;
					}
				}
			}
			if (array_key_exists('resolution', $image_features)) {
				$res = sanitize_text_field((string) $image_features['resolution']);
				if ($res === '') {
					unset($rules[$res_key]);
				} else {
					$res_norm = strtolower(trim((string) $res));
					if ($res_norm === 'default') {
						unset($rules[$res_key]);
					} elseif (in_array($res_norm, $resolution_allow, true)) {
						$rules[$res_key] = $res_norm;
					}
				}
			}
		}

		$targets_key = $column . '__targets';
		if (is_array($target_columns) && ! empty($target_columns)) {
			$clean = array();
			foreach ($target_columns as $c) {
				$k = self::normalize_cell_rule_field_key((string) $c);
				if ($k !== '' && $k !== 'bulk') {
					$clean[] = $k;
				}
			}
			$clean = array_values(array_unique($clean));
			if (! empty($clean)) {
				$rules[$targets_key] = wp_json_encode($clean);
			} else {
				unset($rules[$targets_key]);
			}
		} else {
			unset($rules[$targets_key]);
		}

		$option_name      = self::get_cell_rules_option_name($post_type);

		// update_option() returns false when the value is unchanged (same serialization) — that is still success.
		$updated = update_option($option_name, $rules);

		if ($updated !== false) {
			$result = array('ok' => true);
			return $result;
		}

		$stored_raw = get_option($option_name);
		$stored     = is_array($stored_raw) ? $stored_raw : array();
		$want       = $rules;
		ksort($want);
		ksort($stored);
		$json_want   = wp_json_encode($want);
		$json_stored = wp_json_encode($stored);
		if (is_string($json_want) && is_string($json_stored) && $json_want === $json_stored) {
			$result = array('ok' => true);
			return $result;
		}

		global $wpdb;
		$db_err = (isset($wpdb) && is_object($wpdb) && ! empty($wpdb->last_error)) ? $wpdb->last_error : '';

		$result = array(
			'ok'    => false,
			'debug' => 'save_cell_rule: update_option returned false and stored option does not match expected rules.'
				. ($db_err !== '' ? ' DB: ' . $db_err : ''),
		);
		return $result;
	}

	/**
	 * Strings for prompts UI (cell rules and more). Passed to JS as g_ubaiPromptsStrings.
	 *
	 * @return array<string, string>
	 */
	public static function get_prompts_strings()
	{
		$strings = array(
			'cellRules'       => __('AI Column Settings', 'sheetspilot'),
			'applyForColumn'  => __('Apply for "%%s" column', 'sheetspilot'),
			'close'           => __('Close', 'sheetspilot'),
			'cancel'          => __('Cancel', 'sheetspilot'),
			'saveRules'       => __('Save Rules', 'sheetspilot'),
			'prompt'          => __('Prompt', 'sheetspilot'),
			'promptPlaceholder' => __('Enter the prompt for this column...', 'sheetspilot'),
			'description'     => __('Configure AI rules and behavior for this column.', 'sheetspilot'),
			'savePromptTitle' => __('Save Prompt', 'sheetspilot'),
			'savePromptInstruction' => __('Give this prompt a title and edit the prompt text if needed.', 'sheetspilot'),
			'savePromptLabelTitle'  => __('Title', 'sheetspilot'),
			'savePromptLabelPrompt' => __('Prompt', 'sheetspilot'),
			'savePromptBtnSave'     => __('Save', 'sheetspilot'),
			'promptHistoryStarSave' => __('Save', 'sheetspilot'),
			'promptHistoryStarRemoveFromSaved' => __('Remove From Saved', 'sheetspilot'),
			'reopenPromptResult'             => __('Reopen prompt result', 'sheetspilot'),
			'sidebarTabText'   => __('Text', 'sheetspilot'),
			'sidebarTabImage'  => __('Image', 'sheetspilot'),
			'sidebarImagePanelTitle' => __('Image Prompt Tools', 'sheetspilot'),
			'sidebarImagePanelDescription' => __('This sidebar tab is ready for image-focused prompt controls.', 'sheetspilot'),
			'imagePromptLabel' => __('Prompt for Image (optional)', 'sheetspilot'),
			'imagePromptPlaceholder' => __('Describe the image you want to generate... Type @ to insert field references', 'sheetspilot'),
			'imagePromptEditPlaceholder' => __('Describe how you want to edit this image... Type @ to insert field references', 'sheetspilot'),
			'imageActionLabel' => __('Image action', 'sheetspilot'),
			'imageActionCreate' => __('Create new image', 'sheetspilot'),
			'imageActionEdit' => __('Edit image', 'sheetspilot'),
			'imageAspect11' => __('1:1', 'sheetspilot'),
			'imageAspect1K' => __('1K', 'sheetspilot'),
			// Image generation quality selector (OpenAI Images API quality param).
			'imageQuality05K' => __('Low', 'sheetspilot'),
			'imageQuality1K' => __('Medium', 'sheetspilot'),
			'imageQuality15K' => __('High', 'sheetspilot'),
			'imageQuality2K' => __('High', 'sheetspilot'),
			// Default quality for image generation/editing UI (from general settings).
			'imageQualityDefault' => class_exists('SheetsPilot_PluginGeneralSettings')
				? SheetsPilot_PluginGeneralSettings::getResolvedImageQuality()
				: SheetsPilotGlobals::DEFAULT_IMAGE_QUALITY,
			'imageQuality05KSize' => __('Faster, cheaper, less detail', 'sheetspilot'),
			'imageQuality1KSize' => __('Balanced', 'sheetspilot'),
			'imageQuality15KSize' => __('Best quality, slower, more expensive', 'sheetspilot'),
			'imageQuality2KSize' => __('Best quality, slower, more expensive', 'sheetspilot'),
			'imageRatioAuto' => __('auto', 'sheetspilot'),
			'imageRatioAutoLabel' => __('Auto', 'sheetspilot'),
			'imageRatioSquare' => __('Square', 'sheetspilot'),
			'imageRatioHorizontal' => __('Horizontal', 'sheetspilot'),
			'imageRatioBanner' => __('Banner', 'sheetspilot'),
			'imageRatioUltrawide' => __('Ultrawide', 'sheetspilot'),
			'imageRatioWidescreen' => __('Widescreen', 'sheetspilot'),
			'imageRatioSocialStory' => __('Social Story', 'sheetspilot'),
			'imageRatioTraditional' => __('Traditional', 'sheetspilot'),
			'imageRatioSocialPost' => __('Social post', 'sheetspilot'),
			'imageRatioFlexible' => __('Flexible', 'sheetspilot'),
			'imageRatioClassic' => __('Classic', 'sheetspilot'),
			'imageRatioStandard' => __('Standard', 'sheetspilot'),
			'imageRatioPortrait' => __('Portrait', 'sheetspilot'),
			'imageFormatDefault' => class_exists('SheetsPilot_PluginGeneralSettings')
				? SheetsPilot_PluginGeneralSettings::getResolvedImageFormat()
				: SheetsPilotGlobals::DEFAULT_IMAGE_FORMAT,
			'imageFormatPng' => __('PNG', 'sheetspilot'),
			'imageFormatPngSize' => __('lossless, best quality (default)', 'sheetspilot'),
			'imageFormatJpeg' => __('JPEG', 'sheetspilot'),
			'imageFormatJpegSize' => __('smaller size, compressed', 'sheetspilot'),
			'imageFormatWebp' => __('WebP', 'sheetspilot'),
			'imageFormatWebpSize' => __('modern, very efficient', 'sheetspilot'),
			'imageResolutionDefault' => SheetsPilotGlobals::DEFAULT_IMAGE_RESOLUTION,
			'imageResolution1K' => __('1K', 'sheetspilot'),
			'imageResolution2K' => __('2K', 'sheetspilot'),
			'imageResolution3K' => __('3K', 'sheetspilot'),
			'imageResolution4K' => __('4K', 'sheetspilot'),
			'imageOptionDefault' => '---',
			'imageOptionDefaultHint' => __('Default settings or rules', 'sheetspilot'),
			'imageReferencesHeading' => __('References', 'sheetspilot'),
			'imageReferencesAdd' => __('+ Add', 'sheetspilot'),
			'imageRefStyle' => __('Style', 'sheetspilot'),
			'imageRefCharacter' => __('Character', 'sheetspilot'),
			'imageRefUpload' => __('Upload', 'sheetspilot'),
			'imageUseSelected' => __('Use Selected Image', 'sheetspilot'),
		);
		return $strings;
	}

	/**
	 * Icon/SVG markup for prompts UI (image panel and tab buttons). Passed to JS as g_ubaiPromptsIcons.
	 *
	 * @return array<string, string>
	 */
	public static function get_prompts_icons()
	{
		return array(
			'iconTabText'   => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" x2="15" y1="20" y2="20"></line><line x1="12" x2="12" y1="4" y2="20"></line></svg>',
			'iconTabImage'  => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path></svg>',
			'svgSquare'     => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/></svg>',
			'svgMonitor'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>',
			'svgStar'       => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
			'iconAuto'      => '<span class="ubai-ratio-dropdown__icon-a" aria-hidden="true">A</span>',
			'svgPerson'     => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>',
			'svgUpload'     => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>',
			'svgImage'      => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>',
		);
	}

	/**
	 * Output the cell rules dialog HTML (right-side panel).
	 */
	public static function render_cell_rules_dialog()
	{
		$strings = self::get_prompts_strings();
?>
		<div id="ubai_cell_rules_panel" class="ubai-side-dialog ubai-cell-rules-panel" role="dialog" aria-modal="true" aria-labelledby="ubai_cell_rules_title">
			<div class="ubai-side-dialog__backdrop ubai-cell-rules-panel__backdrop" aria-hidden="true"></div>
			<div class="ubai-side-dialog__container ubai-cell-rules-panel__container">
				<div class="ubai-side-dialog__header ubai-cell-rules-panel__header">
					<div class="ubai-side-dialog__header-text ubai-cell-rules-panel__header-text">
						<h2 id="ubai_cell_rules_title" class="ubai-side-dialog__title ubai-cell-rules-panel__title"><?php echo esc_html($strings['cellRules']); ?></h2>
						<p id="ubai_cell_rules_subtitle" class="ubai-side-dialog__subtitle ubai-cell-rules-panel__subtitle" aria-hidden="true"></p>
					</div>
					<button type="button" class="ubai-side-dialog__close ubai-cell-rules-panel__close unlimitedai-plugin__btn" aria-label="<?php echo esc_attr($strings['close']); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<path d="M18 6 6 18"></path>
							<path d="m6 6 12 12"></path>
						</svg>
					</button>
				</div>
				<div class="ubai-side-dialog__body ubai-cell-rules-panel__body">
					<p class="ubai-side-dialog__description ubai-cell-rules-panel__description"><?php echo esc_html($strings['description']); ?></p>
					<label for="ubai_cell_rules_prompt" class="ubai-side-dialog__label ubai-cell-rules-panel__label"><?php echo esc_html($strings['prompt']); ?></label>
					<textarea id="ubai_cell_rules_prompt" class="ubai-side-dialog__textarea ubai-cell-rules-panel__textarea " rows="6" placeholder="<?php echo esc_attr($strings['promptPlaceholder']); ?>"></textarea>
					<div class="ubai-cell-rules-panel__image-options">
						<?php echo wp_kses(self::build_ratio_quality_selector_html('cell_rules'), array(
							'div' => array(
								'class' => true,
								'id'    => true,
								'role'  => true,
								'data-ratio'      => true,
								'data-quality'    => true,
								'data-format'     => true,
								'data-resolution' => true,
							),

							'button' => array(
								'type'                 => true,
								'class'                => true,
								'title'                => true,
								'data-ratio'          => true,
								'data-quality'        => true,
								'data-format'         => true,
								'data-resolution'     => true,
								'aria-haspopup'       => true,
								'aria-expanded'       => true,
								'aria-controls'       => true,
							),

							'span' => array(
								'class'        => true,
								'aria-hidden'  => true,
							),

							'svg' => array(
								'xmlns'             => true,
								'width'             => true,
								'height'            => true,
								'viewbox'           => true,
								'fill'              => true,
								'stroke'            => true,
								'stroke-width'      => true,
							),

							'rect' => array(
								'width'  => true,
								'height' => true,
								'x'      => true,
								'y'      => true,
								'rx'     => true,
							),

							'line' => array(
								'x1' => true,
								'x2' => true,
								'y1' => true,
								'y2' => true,
							),

							'ul' => array(
								'class' => true,
							),

							'li' => array(
								'class' => true,
								'role'  => true,
								'data-ratio'      => true,
								'data-quality'    => true,
								'data-format'     => true,
								'data-resolution'  => true,
							),

							'label' => array(
								'class' => true,
							),

							'input' => array(
								'type'    => true,
								'name'    => true,
								'value'   => true,
								'id'      => true,
							),

							'span' => array(
								'class' => true,
							),
						)); 

						?>
					</div>
					<div class="ubai-cell-rules-panel__paste-option">
						<label class="ubai-cell-rules-panel__checkbox-label unlimitedai-plugin_label">
							<input type="checkbox" id="ubai_cell_rules_apply_on_paste" class="ubai-cell-rules-panel__checkbox" />
							<span><?php echo esc_html__('Apply Prompt on Paste', 'sheetspilot'); ?></span>
						</label>
					</div>
					<div class="ubai-cell-rules-panel__paste-option">
						<label class="ubai-cell-rules-panel__checkbox-label unlimitedai-plugin_label">
							<input type="checkbox" id="ubai_cell_rules_autoapply" class="ubai-cell-rules-panel__checkbox" />
							<span><?php echo esc_html__('Auto Apply Response', 'sheetspilot'); ?></span>
						</label>
					</div>
				</div>
				<div class="ubai-side-dialog__footer ubai-cell-rules-panel__footer">
					<button type="button" class="ubai-side-dialog__btn ubai-side-dialog__btn--cancel ubai-cell-rules-panel__btn ubai-cell-rules-panel__btn--cancel"><?php echo esc_html($strings['cancel']); ?></button>
					<button type="button" class="ubai-side-dialog__btn ubai-side-dialog__btn--save ubai-cell-rules-panel__btn ubai-cell-rules-panel__btn--save"><?php echo esc_html($strings['saveRules']); ?></button>
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Output the Save Prompt side dialog (title + prompt fields, Cancel/Save). Shown at start.
	 */
	public static function render_save_prompt_dialog()
	{
		$strings = self::get_prompts_strings();
		$title   = isset($strings['savePromptTitle']) ? $strings['savePromptTitle'] : __('Save Prompt',  'sheetspilot');
		$instruction = isset($strings['savePromptInstruction']) ? $strings['savePromptInstruction'] : __('Give this prompt a title and edit the prompt text if needed.',  'sheetspilot');
		$label_title  = isset($strings['savePromptLabelTitle']) ? $strings['savePromptLabelTitle'] : __('Title',  'sheetspilot');
		$label_prompt = isset($strings['savePromptLabelPrompt']) ? $strings['savePromptLabelPrompt'] : __('Prompt',  'sheetspilot');
		$cancel = isset($strings['cancel']) ? $strings['cancel'] : __('Cancel',  'sheetspilot');
		$save   = isset($strings['savePromptBtnSave']) ? $strings['savePromptBtnSave'] : __('Save',  'sheetspilot');
		$close_label = isset($strings['close']) ? $strings['close'] : __('Close',  'sheetspilot');
	?>
		<div id="ubai_save_prompt_dialog" class="ubai-side-dialog ubai-save-prompt-dialog" role="dialog" aria-modal="true" aria-labelledby="ubai_save_prompt_dialog_title">
			<div class="ubai-side-dialog__backdrop ubai-save-prompt-dialog__backdrop" aria-hidden="true"></div>
			<div class="ubai-side-dialog__container ubai-save-prompt-dialog__container">
				<div class="ubai-side-dialog__header ubai-save-prompt-dialog__header">
					<h2 id="ubai_save_prompt_dialog_title" class="ubai-side-dialog__title ubai-save-prompt-dialog__title"><?php echo esc_html($title); ?></h2>
					<button type="button" class="ubai-side-dialog__close ubai-save-prompt-dialog__close unlimitedai-plugin__btn" aria-label="<?php echo esc_attr($close_label); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<path d="M18 6 6 18"></path>
							<path d="m6 6 12 12"></path>
						</svg>
					</button>
				</div>
				<div class="ubai-side-dialog__body ubai-save-prompt-dialog__body">
					<p class="ubai-side-dialog__description ubai-save-prompt-dialog__instruction"><?php echo esc_html($instruction); ?></p>
					<label for="ubai_save_prompt_title" class="ubai-side-dialog__label ubai-save-prompt-dialog__label"><?php echo esc_html($label_title); ?></label>
					<input type="text" id="ubai_save_prompt_title" class="ubai-side-dialog__input ubai-save-prompt-dialog__input" placeholder="<?php echo esc_attr__('e.g. Shorten this text to under 150 characters',  'sheetspilot'); ?>" autocomplete="off">
					<label for="ubai_save_prompt_text" class="ubai-side-dialog__label ubai-save-prompt-dialog__label"><?php echo esc_html($label_prompt); ?></label>
					<textarea id="ubai_save_prompt_text" class="ubai-side-dialog__textarea ubai-save-prompt-dialog__textarea" rows="5" placeholder="<?php echo esc_attr__('Enter or paste the prompt text...',  'sheetspilot'); ?>"></textarea>
				</div>
				<div class="ubai-side-dialog__footer ubai-save-prompt-dialog__footer">
					<button type="button" class="ubai-side-dialog__btn ubai-side-dialog__btn--cancel ubai-save-prompt-dialog__btn ubai-save-prompt-dialog__btn--cancel"><?php echo esc_html($cancel); ?></button>
					<button type="button" class="ubai-side-dialog__btn ubai-side-dialog__btn--save ubai-save-prompt-dialog__btn ubai-save-prompt-dialog__btn--save"><?php echo esc_html($save); ?></button>
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Render HTML for one prompt history list item.
	 *
	 * @param array $item Keys: id, text, title, is_saved, time_ago.
	 * @return string HTML fragment.
	 */
	public static function render_prompt_history_item($item)
	{
		$id        = isset($item['id']) ? $item['id'] : '';
		$text      = isset($item['text']) ? $item['text'] : '';
		$title     = isset($item['title']) ? $item['title'] : '';
		if ($title === '' && $text !== '') {
			$title = strlen($text) > 50 ? substr($text, 0, 50) . '…' : $text;
		}
		$is_saved  = ! empty($item['is_saved']);
		$time_ago  = isset($item['time_ago']) ? $item['time_ago'] : '';
		$star_class = 'unlimitedai-plugin__prompt-history-item-star' . ($is_saved ? ' is-saved' : '');
		$data_saved = $is_saved ? '1' : '0';

		$prompt_type = isset($item['prompt_type']) ? $item['prompt_type'] : '';;

		$strings    = self::get_prompts_strings();
		$star_label = $is_saved
			? (isset($strings['promptHistoryStarRemoveFromSaved']) ? $strings['promptHistoryStarRemoveFromSaved'] : __('Remove From Saved',  'sheetspilot'))
			: (isset($strings['promptHistoryStarSave']) ? $strings['promptHistoryStarSave'] : __('Save',  'sheetspilot'));
		$star_label_attr = esc_attr($star_label);
		$out  = '<div class="unlimitedai-plugin__prompt-history-item" data-id="' . esc_attr($id) . '" data-is-saved="' . esc_attr($data_saved) . '" data-prompt-type="' . esc_attr($prompt_type) . '"  >';
		$out .= '<div class="unlimitedai-plugin__prompt-history-item-header">';

		if ($prompt_type == 'pending_image') {
			$out .= '<span class="unlimitedai-plugin__prompt-history-item-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.2647 15.9377L12.5473 14.2346C11.758 13.4519 11.3633 13.0605 10.9089 12.9137C10.5092 12.7845 10.079 12.7845 9.67922 12.9137C9.22485 13.0605 8.83017 13.4519 8.04082 14.2346L4.04193 18.2622M14.2647 15.9377L14.606 15.5991C15.412 14.7999 15.8149 14.4003 16.2773 14.2545C16.6839 14.1262 17.1208 14.1312 17.5244 14.2688C17.9832 14.4253 18.3769 14.834 19.1642 15.6515L20 16.5001M14.2647 15.9377L18.22 19.9628M18.22 19.9628C17.8703 20 17.4213 20 16.8 20H7.2C6.07989 20 5.51984 20 5.09202 19.782C4.7157 19.5903 4.40973 19.2843 4.21799 18.908C4.12583 18.7271 4.07264 18.5226 4.04193 18.2622M18.22 19.9628C18.5007 19.9329 18.7175 19.8791 18.908 19.782C19.2843 19.5903 19.5903 19.2843 19.782 18.908C20 18.4802 20 17.9201 20 16.8V13M11 4H7.2C6.07989 4 5.51984 4 5.09202 4.21799C4.7157 4.40973 4.40973 4.71569 4.21799 5.09202C4 5.51984 4 6.0799 4 7.2V16.8C4 17.4466 4 17.9066 4.04193 18.2622M18 9V6M18 6V3M18 6H21M18 6H15"></path></svg></span>';
		} else {
			$out .= '<span class="unlimitedai-plugin__prompt-history-item-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72"/><path d="m14 7 3 3"/><path d="M5 6v4"/><path d="M19 14v4"/><path d="M10 2v2"/><path d="M7 8H3"/><path d="M21 16h-4"/><path d="M11 3H9"/></svg></span>';
		}

		$out .= '<span class="unlimitedai-plugin__prompt-history-item-title">' . esc_html($title) . '</span>';
		$out .= '</div>';
		$out .= '<div class="unlimitedai-plugin__prompt-history-item-text">' . esc_html($text) . '</div>';
		$out .= '<div class="unlimitedai-plugin__prompt-history-item-meta">';
		$out .= '<span class="unlimitedai-plugin__prompt-history-item-time">' . esc_html($time_ago) . '</span>';
		$out .= '<span class="unlimitedai-plugin__prompt-history-item-actions">';
		$out .= '<button type="button" class="unlimitedai-plugin__btn ' . esc_attr($star_class) . '" aria-label="' . $star_label_attr . '" title="' . $star_label_attr . '"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg></button>';

		$out .= '<button type="button" class="unlimitedai-plugin__btn unlimitedai-plugin__prompt-history-item-edit" aria-label="' . esc_attr__('Edit prompt',  'sheetspilot') . '" title="' . esc_attr__('Edit prompt',  'sheetspilot') . '">';
		$out .= '<span class="unlimitedai-plugin__prompt-history-item-edit-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>';
		$out .= '<span class="unlimitedai-plugin__prompt-history-item-edit-check" aria-hidden="true" style="display:none;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></span>';
		$out .= '</button>';

		$out .= '<button type="button" class="unlimitedai-plugin__btn unlimitedai-plugin__prompt-history-item-copy" aria-label="' . esc_attr__('Copy to clipboard',  'sheetspilot') . '" title="' . esc_attr__('Copy to clipboard',  'sheetspilot') . '">';
		$out .= '<span class="unlimitedai-plugin__prompt-history-item-copy-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg></span>';
		$out .= '<span class="unlimitedai-plugin__prompt-history-item-copy-check" aria-hidden="true" style="display:none;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>';
		$out .= '</button>';

		$out .= '<button type="button" class="unlimitedai-plugin__btn unlimitedai-plugin__prompt-history-item-run" aria-label="' . esc_attr__('Run',  'sheetspilot') . '" title="' . esc_attr__('Run',  'sheetspilot') . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-play w-3 h-3"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg></button>';
		$out .= '<button type="button" class="unlimitedai-plugin__btn unlimitedai-plugin__prompt-history-item-delete" aria-label="' . esc_attr__('Delete',  'sheetspilot') . '" title="' . esc_attr__('Delete',  'sheetspilot') . '"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4343" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg></button>';
		$out .= '</span></div></div>';
		return $out;
	}

	/**
	 * Render the prompt history list (items or empty message). Echoes output.
	 *
	 * @param array $items Array of item arrays (id, text, is_saved, time_ago).
	 */
	public static function render_prompt_history_list($items)
	{
		if (! empty($items)) {
			foreach ($items as $ph_item) {
				echo wp_kses(self::render_prompt_history_item($ph_item),  array(
					'div' => array(
						'class'            => true,
						'data-id'          => true,
						'data-is-saved'    => true,
						'data-prompt-type'    => true,
					),

					'span' => array(
						'class'       => true,
						'aria-hidden' => true,
					),

					'button' => array(
						'type'  => true,
						'class' => true,
						'title' => true,
						'aria-label' => true,
					),

					'svg' => array(
						'xmlns'        => true,
						'width'        => true,
						'height'       => true,
						'viewbox'      => true,
						'fill'         => true,
						'stroke'       => true,
						'stroke-width' => true,
					),

					'path' => array(
						'd' => true,
					),

					'rect' => array(
						'width' => true,
						'height' => true,
						'x' => true,
						'y' => true,
						'rx' => true,
						'ry' => true,
					),

					'line' => array(
						'x1' => true,
						'x2' => true,
						'y1' => true,
						'y2' => true,
					),

					'polyline' => array(
						'points' => true,
					),

					'polygon' => array(
						'points' => true,
					),
				)); 


			}
		} else {
			echo '<div class="unlimitedai-plugin__prompt-history-empty">' . esc_html__('No prompts found.',  'sheetspilot') . '</div>';
		}
	}

	/**
	 * Return the prompt history list HTML (for AJAX response). Same structure as render_prompt_history_list but returns string.
	 *
	 * @param array $items Array of item arrays (id, text, title, is_saved, time_ago).
	 * @return string HTML fragment.
	 */
	public static function get_prompt_history_list_html($items)
	{
		if (empty($items)) {
			return '<div class="unlimitedai-plugin__prompt-history-empty">' . esc_html__('No prompts found.',  'sheetspilot') . '</div>';
		}
		$html = '';
		foreach ($items as $ph_item) {
			$html .= self::render_prompt_history_item($ph_item);
		}
		return $html;
	}

	/**
	 * Markup for aspect ratio + image quality + format controls.
	 *
	 * @param string $context 'sidebar' (default) or 'cell_rules' — unique element IDs per instance.
	 * @return string
	 */
	private static function build_ratio_quality_selector_html($context = 'sidebar')
	{
		$is_cell_rules       = ('cell_rules' === $context);
		$id_ratio_selector   = $is_cell_rules ? 'ubai_cell_rules_ratio_selector' : 'ubai_ratio_selector';
		$id_ratio_dropdown   = $is_cell_rules ? 'ubai_cell_rules_ratio_dropdown' : 'ubai_ratio_dropdown';
		$id_quality_selector = $is_cell_rules ? 'ubai_cell_rules_quality_selector' : 'ubai_quality_selector';
		$id_quality_dropdown = $is_cell_rules ? 'ubai_cell_rules_quality_dropdown' : 'ubai_quality_dropdown';
		$id_format_selector      = $is_cell_rules ? 'ubai_cell_rules_format_selector' : 'ubai_format_selector';
		$id_format_dropdown      = $is_cell_rules ? 'ubai_cell_rules_format_dropdown' : 'ubai_format_dropdown';
		$id_resolution_selector  = $is_cell_rules ? 'ubai_cell_rules_resolution_selector' : 'ubai_resolution_selector';
		$id_resolution_dropdown  = $is_cell_rules ? 'ubai_cell_rules_resolution_dropdown' : 'ubai_resolution_dropdown';
		$s   = self::get_prompts_strings();
		$ico = self::get_prompts_icons();
		// UI list: keep in sync with SheetsPilot_ImageProcessing::getImageSizeDefinitions().
		$ratio_list = class_exists('SheetsPilot_ImageProcessing')
			? SheetsPilot_ImageProcessing::getAllowedAspectRatios()
			: array('auto', '1:1', '3:2', '2:3');
		$ratio_labels = array(
			'auto'  => isset($s['imageRatioAutoLabel']) ? $s['imageRatioAutoLabel'] : '',
			'1:1'   => isset($s['imageRatioSquare']) ? $s['imageRatioSquare'] : '',
			'2:1'   => isset($s['imageRatioHorizontal']) ? $s['imageRatioHorizontal'] : '',
			'3:1'   => isset($s['imageRatioBanner']) ? $s['imageRatioBanner'] : '',
			'2:3'   => isset($s['imageRatioPortrait']) ? $s['imageRatioPortrait'] : '',
			'3:2'   => isset($s['imageRatioStandard']) ? $s['imageRatioStandard'] : '',
			'3:4'   => isset($s['imageRatioTraditional']) ? $s['imageRatioTraditional'] : '',
			'4:3'   => isset($s['imageRatioClassic']) ? $s['imageRatioClassic'] : '',
			'16:9'  => isset($s['imageRatioWidescreen']) ? $s['imageRatioWidescreen'] : '',
			'9:16'  => isset($s['imageRatioSocialStory']) ? $s['imageRatioSocialStory'] : '',
			'21:9'  => isset($s['imageRatioUltrawide']) ? $s['imageRatioUltrawide'] : '',
		);
		$svg_monitor = isset($ico['svgMonitor']) ? $ico['svgMonitor'] : '';
		$icon_auto   = isset($ico['iconAuto']) ? $ico['iconAuto'] : '';
		$dropdown_items = '';
		foreach ($ratio_list as $kr) {
			$is_auto = ($kr === 'auto');
			$label = isset($ratio_labels[$kr]) ? $ratio_labels[$kr] : $kr;
			$icon_cell = $is_auto
				? '<span class="ubai-ratio-dropdown__icon-wrap">' . $icon_auto . '</span>'
				: '<span class="ubai-ratio-dropdown__icon-wrap"><span class="ubai-ratio-icon" data-ratio-shape="' . esc_attr($kr) . '"></span></span>';
			$dropdown_items .= '<div class="ubai-ratio-dropdown__item" data-ratio="' . esc_attr($kr) . '" role="option">' . $icon_cell . '<span class="ubai-ratio-dropdown__ratio">' . esc_html($kr) . '</span><span class="ubai-ratio-dropdown__label">' . esc_html($label) . '</span></div>';
		}
		$ratio_box_default = isset($s['imageRatioAutoLabel']) ? $s['imageRatioAutoLabel'] : '';
		$ui_default_label    = isset($s['imageOptionDefault']) ? (string) $s['imageOptionDefault'] : '---';
		$ui_default_hint     = isset($s['imageOptionDefaultHint']) ? (string) $s['imageOptionDefaultHint'] : '';
		$quality_default_value = 'default';
		// When the underlying value is "default", show the resolved real default label (placeholder style in UI).
		$quality_default_label = $ui_default_label;
		$resolved_quality = isset($s['imageQualityDefault']) ? (string) $s['imageQualityDefault'] : '';
		if ($resolved_quality === 'low') {
			$quality_default_label = isset($s['imageQuality05K']) ? (string) $s['imageQuality05K'] : 'Low';
		} elseif ($resolved_quality === 'medium') {
			$quality_default_label = isset($s['imageQuality1K']) ? (string) $s['imageQuality1K'] : 'Medium';
		} elseif ($resolved_quality === 'high') {
			$quality_default_label = isset($s['imageQuality2K']) ? (string) $s['imageQuality2K'] : 'High';
		}
		$format_default_value  = 'default';
		$format_default_label  = $ui_default_label;
		$resolved_format = isset($s['imageFormatDefault']) ? (string) $s['imageFormatDefault'] : '';
		if ($resolved_format === 'png') {
			$format_default_label = isset($s['imageFormatPng']) ? (string) $s['imageFormatPng'] : 'PNG';
		} elseif ($resolved_format === 'jpeg') {
			$format_default_label = isset($s['imageFormatJpeg']) ? (string) $s['imageFormatJpeg'] : 'JPEG';
		} elseif ($resolved_format === 'webp') {
			$format_default_label = isset($s['imageFormatWebp']) ? (string) $s['imageFormatWebp'] : 'WebP';
		}
		$quality_items = array(
			array(
				'value' => 'default',
				'label' => $quality_default_label,
				'size'  => $ui_default_hint,
			),
			array(
				'value' => 'low',
				'label' => isset($s['imageQuality05K']) ? $s['imageQuality05K'] : 'Low',
				'size'  => isset($s['imageQuality05KSize']) ? $s['imageQuality05KSize'] : 'Faster, cheaper, less detail',
			),
			array(
				'value' => 'medium',
				'label' => isset($s['imageQuality1K']) ? $s['imageQuality1K'] : 'Medium',
				'size'  => isset($s['imageQuality1KSize']) ? $s['imageQuality1KSize'] : 'Balanced',
			),
			array(
				'value' => 'high',
				'label' => isset($s['imageQuality2K']) ? $s['imageQuality2K'] : 'High',
				'size'  => isset($s['imageQuality2KSize']) ? $s['imageQuality2KSize'] : 'Best quality, slower, more expensive',
			),
		);

		$quality_dropdown_items = '';
		foreach ($quality_items as $q_item) {
			$is_default_option = (isset($q_item['value']) && 'default' === $q_item['value']);
			$item_class          = 'ubai-quality-dropdown__item' . ($is_default_option ? ' ubai-image-option-default' : '');
			$quality_dropdown_items .= '<div class="' . esc_attr(trim($item_class)) . '" data-quality="' . esc_attr($q_item['value']) . '" role="option"><span class="ubai-quality-dropdown__label">' . esc_html($q_item['label']) . '</span><span class="ubai-quality-dropdown__size">' . esc_html($q_item['size']) . '</span></div>';
		}
		$ratio_aria  = esc_attr__('Aspect ratio', 'sheetspilot');
		$size_aria   = esc_attr__('Image quality', 'sheetspilot');
		$inner_open  = '<div class="ubai-ratio-selector" id="' . esc_attr($id_ratio_selector) . '">';
		$inner_open .= '<div class="ubai-ratio-selector__row ubai-image-prompt__aspect">';
		$inner_open .= '<button type="button" class="ubai-image-prompt__aspect-btn ubai-ratio-box" title="' . esc_attr__('Image Aspect Ratio', 'sheetspilot') . '" data-ratio="auto" aria-haspopup="listbox" aria-expanded="false" aria-controls="' . esc_attr($id_ratio_dropdown) . '"><span class="ubai-ratio-selector__icon">' . $icon_auto . '</span><span class="ubai-ratio-box__value">' . esc_html($ratio_box_default) . '</span></button>';
		$inner_open .= '<div class="ubai-quality-selector" id="' . esc_attr($id_quality_selector) . '">';
		$inner_open .= '<button type="button" class="ubai-image-prompt__aspect-btn ubai-quality-selector__btn ubai-default-placeholder" title="' . esc_attr__('Image Quality', 'sheetspilot') . '" data-quality="' . esc_attr((string) $quality_default_value) . '" aria-haspopup="listbox" aria-expanded="false">';
		$inner_open .= '<span class="ubai-ratio-selector__icon">' . $svg_monitor . '</span>';
		$inner_open .= '<span class="ubai-quality-selector__label">' . esc_html($quality_default_label) . '</span>';
		$inner_open .= '</button>';
		$inner_open .= '<div class="ubai-quality-selector__dropdown" id="' . esc_attr($id_quality_dropdown) . '" role="listbox" aria-label="' . $size_aria . '">';
		$inner_open .= $quality_dropdown_items;
		$inner_open .= '</div></div>';

		$format_aria = esc_attr__('Image format', 'sheetspilot');
		$format_items = array(
			array(
				'value' => 'default',
				'label' => $format_default_label,
				'size'  => $ui_default_hint,
			),
			array(
				'value' => 'png',
				'label' => isset($s['imageFormatPng']) ? $s['imageFormatPng'] : 'PNG',
				'size'  => isset($s['imageFormatPngSize']) ? $s['imageFormatPngSize'] : 'lossless, best quality',
			),
			array(
				'value' => 'jpeg',
				'label' => isset($s['imageFormatJpeg']) ? $s['imageFormatJpeg'] : 'JPEG',
				'size'  => isset($s['imageFormatJpegSize']) ? $s['imageFormatJpegSize'] : 'smaller size, compressed',
			),
			array(
				'value' => 'webp',
				'label' => isset($s['imageFormatWebp']) ? $s['imageFormatWebp'] : 'WebP',
				'size'  => isset($s['imageFormatWebpSize']) ? $s['imageFormatWebpSize'] : 'modern, very efficient',
			),
		);

		$format_dropdown_items = '';
		foreach ($format_items as $f_item) {
			$is_default_option = (isset($f_item['value']) && 'default' === $f_item['value']);
			$item_class          = 'ubai-format-dropdown__item' . ($is_default_option ? ' ubai-image-option-default' : '');
			$format_dropdown_items .= '<div class="' . esc_attr(trim($item_class)) . '" data-format="' . esc_attr($f_item['value']) . '" role="option">'
				. '<span class="ubai-format-dropdown__label">' . esc_html($f_item['label']) . '</span>'
				. '<span class="ubai-format-dropdown__size">' . esc_html($f_item['size']) . '</span>'
				. '</div>';
		}

		$inner_open .= '<div class="ubai-format-selector" id="' . esc_attr($id_format_selector) . '">';
		$inner_open .= '<button type="button" class="ubai-image-prompt__aspect-btn ubai-format-selector__btn ubai-default-placeholder" title="' . esc_attr__('Image Format', 'sheetspilot') . '" data-format="' . esc_attr((string) $format_default_value) . '" aria-haspopup="listbox" aria-expanded="false">';
		$inner_open .= '<span class="ubai-format-selector__label">' . esc_html($format_default_label) . '</span>';
		$inner_open .= '</button>';
		$inner_open .= '<div class="ubai-format-selector__dropdown" id="' . esc_attr($id_format_dropdown) . '" role="listbox" aria-label="' . $format_aria . '">';
		$inner_open .= $format_dropdown_items;
		$inner_open .= '</div></div>';

		$resolution_default_value = 'default';
		$resolution_default_label = $ui_default_label;
		$resolved_resolution = isset($s['imageResolutionDefault']) ? (string) $s['imageResolutionDefault'] : '';
		if ($resolved_resolution === '1k') {
			$resolution_default_label = isset($s['imageResolution1K']) ? (string) $s['imageResolution1K'] : '1K';
		} elseif ($resolved_resolution === '2k') {
			$resolution_default_label = isset($s['imageResolution2K']) ? (string) $s['imageResolution2K'] : '2K';
		} elseif ($resolved_resolution === '3k') {
			$resolution_default_label = isset($s['imageResolution3K']) ? (string) $s['imageResolution3K'] : '3K';
		} elseif ($resolved_resolution === '4k') {
			$resolution_default_label = isset($s['imageResolution4K']) ? (string) $s['imageResolution4K'] : '4K';
		}
		$resolution_items         = array(
			array(
				'value' => 'default',
				'label' => $resolution_default_label,
			),
			array(
				'value' => '1k',
				'label' => isset($s['imageResolution1K']) ? $s['imageResolution1K'] : '1K',
			),
			array(
				'value' => '2k',
				'label' => isset($s['imageResolution2K']) ? $s['imageResolution2K'] : '2K',
			),
			array(
				'value' => '3k',
				'label' => isset($s['imageResolution3K']) ? $s['imageResolution3K'] : '3K',
			),
			array(
				'value' => '4k',
				'label' => isset($s['imageResolution4K']) ? $s['imageResolution4K'] : '4K',
			),
		);
		$resolution_dropdown_items  = '';
		foreach ($resolution_items as $res_item) {
			$is_default_option = (isset($res_item['value']) && 'default' === $res_item['value']);
			$item_class          = 'ubai-resolution-dropdown__item' . ($is_default_option ? ' ubai-image-option-default' : '');
			$resolution_dropdown_items .= '<div class="' . esc_attr(trim($item_class)) . '" data-resolution="' . esc_attr($res_item['value']) . '" role="option">'
				. '<span class="ubai-resolution-dropdown__label">' . esc_html($res_item['label']) . '</span>'
				. '</div>';
		}
		$resolution_aria = esc_attr__('Resolution', 'sheetspilot');
		$inner_open     .= '<div class="ubai-resolution-selector" id="' . esc_attr($id_resolution_selector) . '">';
		$inner_open     .= '<button type="button" class="ubai-image-prompt__aspect-btn ubai-resolution-selector__btn ubai-default-placeholder" title="' . esc_attr__('Resolution', 'sheetspilot') . '" data-resolution="' . esc_attr((string) $resolution_default_value) . '" aria-haspopup="listbox" aria-expanded="false">';
		$inner_open     .= '<span class="ubai-resolution-selector__label">' . esc_html($resolution_default_label) . '</span>';
		$inner_open     .= '</button>';
		$inner_open     .= '<div class="ubai-resolution-selector__dropdown" id="' . esc_attr($id_resolution_dropdown) . '" role="listbox" aria-label="' . $resolution_aria . '">';
		$inner_open     .= $resolution_dropdown_items;
		$inner_open     .= '</div></div>';

		// Close the aspect row now that all selectors are rendered.
		$inner_open .= '</div>';

		$inner_open .= '<div class="ubai-ratio-selector__dropdown" id="' . esc_attr($id_ratio_dropdown) . '" role="listbox" aria-label="' . $ratio_aria . '">';
		$inner_open .= '<div class="ubai-ratio-dropdown__list">' . $dropdown_items . '</div></div></div>';
		return $inner_open;
	}

	/**
	 * Image action select (create new vs edit existing) for the sidebar Image tab.
	 *
	 * @return string
	 */
	private static function build_image_action_select_html()
	{
		$s      = self::get_prompts_strings();
		$label  = isset($s['imageActionLabel']) ? $s['imageActionLabel'] : '';
		$create = isset($s['imageActionCreate']) ? $s['imageActionCreate'] : '';
		$edit   = isset($s['imageActionEdit']) ? $s['imageActionEdit'] : '';

		$html  = '<div class="unlimitedai-plugin__sidebar-section ubai-image-prompt__section ubai-image-action__section">';
		$html .= '<label for="ubai_image_action_select">' . esc_html($label) . '</label>';
		$html .= '<select id="ubai_image_action_select" class="ubai-image-action-select">';
		$html .= '<option value="create">' . esc_html($create) . '</option>';
		$html .= '<option value="edit">' . esc_html($edit) . '</option>';
		$html .= '</select></div>';

		return $html;
	}

	/**
	 * Output the Image prompt panel HTML (sidebar Image tab content). Used by the view so most HTML comes from PHP, not JS.
	 */
	public static function render_image_prompt_panel()
	{
		$s = self::get_prompts_strings();
		$prompt_label = isset($s['imagePromptLabel']) ? $s['imagePromptLabel'] : '';
		$prompt_placeholder = isset($s['imagePromptPlaceholder']) ? $s['imagePromptPlaceholder'] : '';
		$prompt_edit_placeholder = isset($s['imagePromptEditPlaceholder']) ? $s['imagePromptEditPlaceholder'] : $prompt_placeholder;
	?>
		<div id="ubai_sidebar_image_panel" class="ubai-sidebar-mode-panel ubai-sidebar-mode-panel--image" role="tabpanel" aria-labelledby="ubai_sidebar_mode_tab_image" hidden>
			<?php echo wp_kses(self::build_image_action_select_html(),  array(
				'div' => array(
					'class' => true,
				),

				'label' => array(
					'for' => true,
				),

				'select' => array(
					'id'    => true,
					'class' => true,
				),

				'option' => array(
					'value' => true,
				),
			)); 

			?>
			<?php if (SheetsPilotGlobals::$isPro) : ?>
				<!--
				<div class="unlimitedai-plugin__sidebar-section unlimitedai-plugin__sidebar-section--quick-actions">
					<label class="unlimitedai-plugin__quick-actions-label"><?php esc_html_e('Quick Prompts', 'sheetspilot'); ?></label>
					<div class="unlimitedai-plugin__quick-actions-row">
						<div id="ubai_quick_actions_combo_images" class="unlimitedai-plugin__quick-actions-combo" role="button" tabindex="0" aria-haspopup="menu" aria-expanded="false" aria-label="<?php esc_attr_e('Select Prompt...', 'sheetspilot'); ?>">
							<span class="unlimitedai-plugin__quick-actions-combo-icon" aria-hidden="true">
								<svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wand-sparkles">
									<path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72"></path>
									<path d="m14 7 3 3"></path>
									<path d="M5 6v4"></path>
									<path d="M19 14v4"></path>
									<path d="M10 2v2"></path>
									<path d="M7 8H3"></path>
									<path d="M21 16h-4"></path>
									<path d="M11 3H9"></path>
								</svg>
							</span>
							<div id="ubai_quick_actions_trigger_images" class="unlimitedai-plugin__quick-actions-trigger"><?php esc_html_e('Select Prompt...', 'sheetspilot'); ?></div>
							<span class="unlimitedai-plugin__quick-actions-combo-chevron" aria-hidden="true">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="m6 9 6 6 6-6" />
								</svg>
							</span>
						</div>
					</div>
				</div>
			-->
			<?php endif; ?>

			<div class="unlimitedai-plugin__sidebar-section ubai-image-prompt__section">
				<label for="ubai_image_prompt_text"><?php echo esc_html($prompt_label); ?></label>
				<textarea id="ubai_image_prompt_text" class="ubai-image-prompt__textarea" rows="4" placeholder="<?php echo esc_attr($prompt_placeholder); ?>" data-placeholder-create="<?php echo esc_attr($prompt_placeholder); ?>" data-placeholder-edit="<?php echo esc_attr($prompt_edit_placeholder); ?>"></textarea>
			</div>
			<div class="unlimitedai-plugin__sidebar-section ubai-image-parameters__section">
				<?php echo wp_kses(self::build_ratio_quality_selector_html('sidebar'), array(
					'div' => array(
						'class' => true,
						'id'    => true,
						'role'  => true,
						'data-ratio'      => true,
						'data-quality'    => true,
						'data-format'     => true,
						'data-resolution' => true,
					),

					'button' => array(
						'type'              => true,
						'class'             => true,
						'title'             => true,
						'data-ratio'        => true,
						'data-quality'      => true,
						'data-format'       => true,
						'data-resolution'   => true,
						'aria-haspopup'     => true,
						'aria-expanded'     => true,
						'aria-controls'     => true,
					),

					'span' => array(
						'class'        => true,
						'aria-hidden'  => true,
					),

					'svg' => array(
						'xmlns'        => true,
						'width'        => true,
						'height'       => true,
						'viewbox'      => true,
						'fill'         => true,
						'stroke'       => true,
						'stroke-width' => true,
					),

					'rect' => array(
						'width'  => true,
						'height' => true,
						'x'      => true,
						'y'      => true,
						'rx'     => true,
					),

					'line' => array(
						'x1' => true,
						'x2' => true,
						'y1' => true,
						'y2' => true,
					),

					'ul' => array(
						'class' => true,
					),

					'li' => array(
						'class'        => true,
						'role'         => true,
						'data-ratio'   => true,
						'data-quality' => true,
						'data-format'  => true,
						'data-resolution' => true,
					),

					'label' => array(
						'for'   => true,
						'class' => true,
					),

					'select' => array(
						'id'    => true,
						'class' => true,
					),

					'option' => array(
						'value' => true,
						'class' => true,
					),

					'path' => array(
						'd' => true,
					),

					'polygon' => array(
						'points' => true,
					),

					'polyline' => array(
						'points' => true,
					),
				)); 
				?>
			</div>

		</div>
<?php
	}
}
