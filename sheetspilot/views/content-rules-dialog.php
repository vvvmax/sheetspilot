<?php
/**
 * Content Rules dialog template.
 * Configure AI settings, content rules, and custom actions.
 *
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! defined( 'SHEETSPILOT_INC' ) ) {
	die( 'restricted access' );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$sheetspilot_tones  = array(
	array( 'name' => '', 'text' => __( 'Not Selected', 'sheetspilot' ) ),
	array( 'name' => 'Professional', 'text' => __( 'Professional', 'sheetspilot' ) ),
	array( 'name' => 'Casual', 'text' => __( 'Casual', 'sheetspilot' ) ),
	array( 'name' => 'Friendly', 'text' => __( 'Friendly', 'sheetspilot' ) ),
	array( 'name' => 'Formal', 'text' => __( 'Formal', 'sheetspilot' ) ),
	array( 'name' => 'Persuasive', 'text' => __( 'Persuasive', 'sheetspilot' ) ),
	array( 'name' => 'Urgent', 'text' => __( 'Urgent', 'sheetspilot' ) ),
	array( 'name' => 'Informative', 'text' => __( 'Informative', 'sheetspilot' ) ),
	array( 'name' => 'Confident', 'text' => __( 'Confident', 'sheetspilot' ) ),
	array( 'name' => 'Humorous', 'text' => __( 'Humorous', 'sheetspilot' ) ),
	array( 'name' => 'Inspirational', 'text' => __( 'Inspirational', 'sheetspilot' ) ),
	array( 'name' => 'Sarcastic', 'text' => __( 'Sarcastic', 'sheetspilot' ) ),
	array( 'name' => 'Analytical', 'text' => __( 'Analytical', 'sheetspilot' ) ),
	array( 'name' => 'Concise', 'text' => __( 'Concise', 'sheetspilot' ) ),
);
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$sheetspilot_saved_rules      = SheetsPilotHelper::getContentRules();
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$sheetspilot_content_tone     = SheetsPilotFunctions::getVal( $sheetspilot_saved_rules, 'contentTone', '' );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$sheetspilot_content_language = SheetsPilotFunctions::getVal( $sheetspilot_saved_rules, 'contentLanguage', '' );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$sheetspilot_custom_language = SheetsPilotFunctions::getVal( $sheetspilot_saved_rules, 'customLanguage', '' );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$sheetspilot_target_audience  = SheetsPilotFunctions::getVal( $sheetspilot_saved_rules, 'targetAudience', '' );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$sheetspilot_brand_voice      = SheetsPilotFunctions::getVal( $sheetspilot_saved_rules, 'brandVoice', '' );
?>
<div id="ubai_contentrules_dialog" class="ubai-content-rules-dialog" role="dialog" aria-modal="true" aria-labelledby="ubai_contentrules_title">
	<div class="ubai-content-rules-dialog__backdrop"></div>
	<div class="ubai-content-rules-dialog__container">
		<button type="button" class="ubai-content-rules-dialog__close" aria-label="<?php echo esc_attr( __( 'Close', 'sheetspilot' ) ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
		</button>
		<div class="ubai-content-rules-dialog__header">
			<h2 id="ubai_contentrules_title" class="ubai-content-rules-dialog__title"><?php echo esc_html( __( 'Content Rules', 'sheetspilot' ) ); ?></h2>
			<p class="ubai-content-rules-dialog__description"><?php echo esc_html( __( 'Configure AI settings, content rules, and custom actions.', 'sheetspilot' ) ); ?></p>
		</div>
		<nav class="ubai-content-rules-dialog__tabs" style="display:none">
			<button type="button" class="ubai-content-rules-dialog__tab ubai-content-rules-dialog__tab--active" data-tab="general"><?php echo esc_html( __( 'General', 'sheetspilot' ) ); ?></button>
			<button type="button" class="ubai-content-rules-dialog__tab" data-tab="vocabulary" disabled><?php echo esc_html( __( 'Vocabulary', 'sheetspilot' ) ); ?></button>
			<button type="button" class="ubai-content-rules-dialog__tab" data-tab="custom" disabled><?php echo esc_html( __( 'Custom Actions', 'sheetspilot' ) ); ?></button>
			<button type="button" class="ubai-content-rules-dialog__tab" data-tab="debug" disabled><?php echo esc_html( __( 'Debug', 'sheetspilot' ) ); ?></button>
		</nav>
		<div class="ubai-content-rules-dialog__body">
			<div class="ubai-content-rules-dialog__panel ubai-content-rules-dialog__panel--general" data-tab="general">
				<div class="ubai-content-rules-dialog__form-group">
					<label for="ubai_contentrules_content_tone"><?php echo esc_html( __( 'Content Tone', 'sheetspilot' ) ); ?></label>
					<select id="ubai_contentrules_content_tone" class="ubai-content-rules-dialog__select">
						<?php
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
						foreach ( $sheetspilot_tones as $sheetspilot_tone ) {
							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
							$sheetspilot_selected = ( $sheetspilot_content_tone === $sheetspilot_tone['name'] ) ? ' selected' : '';
							echo '<option value="' . esc_attr( $sheetspilot_tone['name'] ) . '"' . esc_html( $sheetspilot_selected ) . '>' . esc_html( $sheetspilot_tone['text'] ) . '</option>';
						}
						?>
					</select>
				</div>
				<div class="ubai-content-rules-dialog__form-group">
					<label for="ubai_contentrules_content_language"><?php echo esc_html( __( 'Content Language', 'sheetspilot' ) ); ?></label>
					<div class="ubai-content-rules-dialog__language-wrapper">
						<input type="text" id="ubai_contentrules_content_language" class="ubai-content-rules-dialog__input" value="<?php echo esc_attr( $sheetspilot_content_language ); ?>" placeholder="<?php echo esc_attr( __( 'Site Language', 'sheetspilot' ) ); ?>">
						
						<input type="text" id="ubai_contentrules_custom_language" class="ubai-content-rules-dialog__input" value="<?php echo esc_attr( $sheetspilot_custom_language ); ?>" placeholder="<?php echo esc_attr( __( 'Site Language', 'sheetspilot' ) ); ?>">

						<button type="button" class="ubai-content-rules-dialog__language-choose-btn" id="ubai_contentrules_content_language_choose_btn" aria-haspopup="listbox" aria-expanded="false" aria-label="<?php echo esc_attr( __( 'Choose language', 'sheetspilot' ) ); ?>">
							<?php echo esc_html( __( 'Choose', 'sheetspilot') ); ?>
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"></path></svg>
						</button>
						<div id="ubai_contentrules_content_language_list" class="ubai-content-rules-dialog__language-list" role="listbox" aria-hidden="true">
							<?php
							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
							$sheetspilot_languages = SheetsPilotHelper::getSiteLanguages();
							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
							foreach ( $sheetspilot_languages as $sheetspilot_lang ) {
								echo '<div class="ubai-content-rules-dialog__language-item" role="option" data-lang="' . esc_attr( $sheetspilot_lang ) . '">' . esc_html( $sheetspilot_lang ) . '</div>';
							}
							?>
						</div>
					</div>
				</div>
				<div class="ubai-content-rules-dialog__form-group">
					<label for="ubai_contentrules_target_audience"><?php echo esc_html( __( 'Target Audience', 'sheetspilot' ) ); ?></label>
					<input type="text" id="ubai_contentrules_target_audience" class="ubai-content-rules-dialog__input" value="<?php echo esc_attr( $sheetspilot_target_audience ); ?>" placeholder="<?php echo esc_attr( __( 'Example: Wine enthusiasts and collectors aged 30-60', 'sheetspilot' ) ); ?>">
				</div>
				<div class="ubai-content-rules-dialog__form-group">
					<label for="ubai_contentrules_brand_voice"><?php echo esc_html( __( 'Brand Voice', 'sheetspilot' ) ); ?></label>
					<textarea id="ubai_contentrules_brand_voice" class="ubai-content-rules-dialog__textarea" rows="3" placeholder="<?php echo esc_attr( __( 'Example: Sophisticated, knowledgeable, and approachable', 'sheetspilot' ) ); ?>"><?php echo esc_textarea( $sheetspilot_brand_voice ); ?></textarea>
				</div>
			</div>
			<div class="ubai-content-rules-dialog__panel ubai-content-rules-dialog__panel--vocabulary" data-tab="vocabulary" style="display:none;">
				<div class="ubai-content-rules-dialog__vocab-section">
					<label class="ubai-content-rules-dialog__vocab-label"><?php echo esc_html( __( 'Words to Avoid', 'sheetspilot' ) ); ?></label>
					<div class="ubai-content-rules-dialog__vocab-input-row">
						<input type="text" id="ubai_contentrules_vocab_avoid_input" class="ubai-content-rules-dialog__vocab-input" placeholder="<?php echo esc_attr( __( 'Add word...', 'sheetspilot' ) ); ?>">
						<div class="ubai-content-rules-dialog__vocab-input-separator"></div>
						<button type="button" class="ubai-content-rules-dialog__vocab-add-btn" data-target="avoid"><?php echo esc_html( __( 'Add', 'sheetspilot' ) ); ?></button>
					</div>
					<div id="ubai_contentrules_vocab_avoid_tags" class="ubai-content-rules-dialog__vocab-tags ubai-content-rules-dialog__vocab-tags--avoid"></div>
				</div>
				<div class="ubai-content-rules-dialog__vocab-section">
					<label class="ubai-content-rules-dialog__vocab-label"><?php echo esc_html( __( 'Preferred Words', 'sheetspilot' ) ); ?></label>
					<div class="ubai-content-rules-dialog__vocab-input-row">
						<input type="text" id="ubai_contentrules_vocab_prefer_input" class="ubai-content-rules-dialog__vocab-input" placeholder="<?php echo esc_attr( __( 'Add word...', 'sheetspilot' ) ); ?>">
						<div class="ubai-content-rules-dialog__vocab-input-separator"></div>
						<button type="button" class="ubai-content-rules-dialog__vocab-add-btn" data-target="prefer"><?php echo esc_html( __( 'Add', 'sheetspilot' ) ); ?></button>
					</div>
					<div id="ubai_contentrules_vocab_prefer_tags" class="ubai-content-rules-dialog__vocab-tags ubai-content-rules-dialog__vocab-tags--prefer"></div>
				</div>
			</div>
			<div class="ubai-content-rules-dialog__panel ubai-content-rules-dialog__panel--custom" data-tab="custom" style="display:none;">
				<div class="ubai-content-rules-dialog__custom-section">
					<label class="ubai-content-rules-dialog__custom-label"><?php echo esc_html( __( 'Add Custom Action', 'sheetspilot' ) ); ?></label>
					<div class="ubai-content-rules-dialog__form-group">
						<input type="text" id="ubai_contentrules_custom_action_name" class="ubai-content-rules-dialog__input" placeholder="<?php echo esc_attr( __( 'Action name (e.g., Translate to Spanish)', 'sheetspilot' ) ); ?>">
					</div>
					<div class="ubai-content-rules-dialog__form-group">
						<textarea id="ubai_contentrules_custom_action_prompt" class="ubai-content-rules-dialog__textarea" rows="3" placeholder="<?php echo esc_attr( __( 'AI prompt for this action...', 'sheetspilot' ) ); ?>"></textarea>
					</div>
					<button type="button" class="ubai-content-rules-dialog__custom-add-btn ubai-content-rules-dialog__custom-add-btn--disabled" id="ubai_contentrules_custom_action_add_btn" disabled title="<?php echo esc_attr( __( 'Fill The Action', 'sheetspilot' ) ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
						<?php echo esc_html( __( 'Add Action', 'sheetspilot' ) ); ?>
					</button>
				</div>
				<div class="ubai-content-rules-dialog__custom-section">
					<label class="ubai-content-rules-dialog__custom-label"><?php echo esc_html( __( 'Your Custom Actions', 'sheetspilot' ) ); ?></label>
					<div class="ubai-content-rules-dialog__custom-actions-wrapper">
						<div id="ubai_contentrules_custom_actions_empty" class="ubai-content-rules-dialog__custom-actions-empty"><?php echo esc_html( __( 'No actions', 'sheetspilot' ) ); ?></div>
						<div id="ubai_contentrules_custom_actions_list" class="ubai-content-rules-dialog__custom-actions-list"></div>
					</div>
				</div>
			</div>
			<div class="ubai-content-rules-dialog__panel ubai-content-rules-dialog__panel--debug" data-tab="debug" style="display:none;">
				<div class="ubai-content-rules-dialog__debug-card">
					<span class="ubai-content-rules-dialog__debug-card-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21v-6"></path><path d="M12 9V3"></path><path d="M3 15h18"></path><path d="M3 9h18"></path><rect width="18" height="18" x="3" y="3" rx="2"></rect></svg>
					</span>
					<div class="ubai-content-rules-dialog__debug-card-content">
						<h4 class="ubai-content-rules-dialog__debug-card-title"><?php echo esc_html( __( 'RTL Mode', 'sheetspilot' ) ); ?></h4>
						<p class="ubai-content-rules-dialog__debug-card-desc"><?php echo esc_html( __( 'Enable right-to-left layout for testing Hebrew/Arabic content.', 'sheetspilot' ) ); ?></p>
					</div>
					<label class="ubai-content-rules-dialog__debug-toggle">
						<input type="checkbox" id="ubai_contentrules_debug_rtl_mode" class="ubai-content-rules-dialog__debug-toggle-input">
						<span class="ubai-content-rules-dialog__debug-toggle-slider"></span>
					</label>
				</div>
				<div class="ubai-content-rules-dialog__debug-info">
					<h4 class="ubai-content-rules-dialog__debug-info-title"><?php echo esc_html( __( 'Debug Mode', 'sheetspilot' ) ); ?></h4>
					<p class="ubai-content-rules-dialog__debug-info-text"><?php echo esc_html( __( 'These settings are for development and testing purposes. The RTL toggle helps test right-to-left language layouts without changing content language.', 'sheetspilot' ) ); ?></p>
				</div>
			</div>
		</div>
		<div class="ubai-content-rules-dialog__footer">
			<button type="button" class="ubai-content-rules-dialog__btn ubai-content-rules-dialog__btn--cancel"><?php echo esc_html( __( 'Cancel', 'sheetspilot' ) ); ?></button>
			<button type="button" class="ubai-content-rules-dialog__btn ubai-content-rules-dialog__btn--save"><?php echo esc_html( __( 'Save Rules', 'sheetspilot' ) ); ?></button>
		</div>
	</div>
</div>
