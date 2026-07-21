<?php

/**

 * Pro admin: Prompt Tester (OpenAI chat request/response).

 *

 * @package SheetsPilot

 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved.

 **/



if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

if ( ! defined( 'SHEETSPILOT_INC' ) ) {

	die( 'restricted access' );

}



/**

 * SheetsPilot Prompt Tester screen.

 */

class SheetsPilot_PluginViewPromptTester {



	/**

	 * Output the page.

	 */

	private function putViewHtml() {

		if ( SheetsPilotGlobals::$isPro !== true ) {

			echo '<div class="wrap"><p>' . esc_html__( 'Prompt Tester requires SheetsPilot Pro.', 'sheetspilot' ) . '</p></div>';

			return;

		}



		$nonce = SheetsPilotHelper::getNonce();

		$settings = SheetsPilotHelper::getGeneralSettings();

		$model_options = class_exists( 'SheetsPilot_PluginGeneralSettings' )

			? SheetsPilot_PluginGeneralSettings::getOpenAIModelOptions()

			: array();

		$default_model = class_exists( 'SheetsPilot_PluginGeneralSettings' )

			? SheetsPilot_PluginGeneralSettings::getDefaultOpenAIModel()

			: SheetsPilotGlobals::CHATGPT_MODEL;

		$selected_model = isset( $settings['openai_model'] ) && $settings['openai_model'] !== ''

			? sanitize_text_field( $settings['openai_model'] )

			: $default_model;

		$selected_in_options = in_array( $selected_model, array_values( $model_options ), true );



		$aspect_ratios = class_exists( 'SheetsPilot_ImageProcessing' )

			? SheetsPilot_ImageProcessing::getAllowedAspectRatios()

			: array( 'auto', '1:1', '3:2', '2:3' );

		$compress_library_status = class_exists( 'SheetsPilot_ImageProcessing' )
			? SheetsPilot_ImageProcessing::getCompressionLibraryStatus()
			: array(
				'gd_installed'      => false,
				'imagick_installed' => false,
				'has_any'           => false,
				'wp_preferred'      => '',
			);

		$prompt_tester_preload = null;
		$from_log_id           = SheetsPilotFunctions::getGetVar( 'from_log', '', SheetsPilotFunctions::SANITIZE_ID );
		if ( $from_log_id > 0 && class_exists( 'SheetsPilot_RequestLog', false ) ) {
			$log_row = SheetsPilot_RequestLog::getById( $from_log_id );
			if ( is_array( $log_row ) ) {
				$prompt_tester_preload = SheetsPilot_RequestLog::buildPromptTesterPreloadFromRow( $log_row );
			}
		}

		$ratio_labels = array(

			'auto' => __( 'Auto', 'sheetspilot' ),

			'1:1'  => __( 'Square (1:1)', 'sheetspilot' ),

			'2:1'  => __( 'Horizontal (2:1)', 'sheetspilot' ),

			'3:1'  => __( 'Banner (3:1)', 'sheetspilot' ),

			'3:2'  => __( 'Standard (3:2)', 'sheetspilot' ),

			'4:3'  => __( 'Classic (4:3)', 'sheetspilot' ),

			'16:9' => __( 'Widescreen (16:9)', 'sheetspilot' ),

			'21:9' => __( 'Ultrawide (21:9)', 'sheetspilot' ),

			'2:3'  => __( 'Portrait (2:3)', 'sheetspilot' ),

			'3:4'  => __( 'Traditional (3:4)', 'sheetspilot' ),

			'9:16' => __( 'Social Story (9:16)', 'sheetspilot' ),

		);



		?>

	<div class="wrap" id="uc_prompt_tester_wrap">

		<h1 class="ubai-prompt-tester-title">
			<?php echo esc_html__( 'Prompt Tester', 'sheetspilot' ); ?>
			<?php if ( SheetsPilotGlobals::$debug_prompt_request ) : ?>
				<span class="ubai-prompt-tester-debug-badge"><?php esc_html_e( 'Debug - ON', 'sheetspilot' ); ?></span>
			<?php endif; ?>
		</h1>

		<p class="description">

			<?php echo esc_html__( 'Send text or image requests using your OpenAI key from SheetsPilot Settings. Request and response payloads are shown below for debugging.', 'sheetspilot' ); ?>

		</p>



		<h2 class="nav-tab-wrapper ubai-prompt-tester-tabs">

			<a href="#ubai_prompt_tester_tab_text" class="nav-tab nav-tab-active" data-tab="text"><?php echo esc_html__( 'Text Requests', 'sheetspilot' ); ?></a>

			<a href="#ubai_prompt_tester_tab_image" class="nav-tab" data-tab="image"><?php echo esc_html__( 'Image Requests', 'sheetspilot' ); ?></a>

			<a href="#ubai_prompt_tester_tab_compress" class="nav-tab" data-tab="compress"><?php echo esc_html__( 'Compress Image', 'sheetspilot' ); ?></a>

		</h2>



		<div id="ubai_prompt_tester_tab_text" class="ubai-prompt-tester-tab-panel" data-tab-panel="text">

			<div style="margin: 16px 0; display: grid; gap: 12px; max-width: 980px;">

				<label for="ubai_prompt_tester_model"><strong><?php echo esc_html__( 'Model', 'sheetspilot' ); ?></strong></label>

				<select id="ubai_prompt_tester_model" class="large-text">

					<?php

					foreach ( $model_options as $label => $value ) {

						$is_selected = selected( $selected_model, $value, false );

						echo '<option value="' . esc_attr( $value ) . '"' . esc_html( $is_selected ) . '>' . esc_html( $label ) . '</option>';

					}

					if ( $selected_in_options === false && $selected_model !== '' ) {

						echo '<option value="' . esc_attr( $selected_model ) . '" selected="selected">' . esc_html( $selected_model ) . '</option>';

					}

					?>

				</select>



				<label for="ubai_prompt_tester_tool"><strong><?php echo esc_html__( 'Tool', 'sheetspilot' ); ?></strong></label>

				<select id="ubai_prompt_tester_tool" class="large-text">

					<option value=""><?php echo esc_html__( 'no extra tool', 'sheetspilot' ); ?></option>

					<option value="web_search"><?php echo esc_html__( 'web_search', 'sheetspilot' ); ?></option>

					<option value="file_search"><?php echo esc_html__( 'file_search', 'sheetspilot' ); ?></option>

					<option value="code_interpreter"><?php echo esc_html__( 'code_interpreter', 'sheetspilot' ); ?></option>

					<option value="computer_use"><?php echo esc_html__( 'computer_use', 'sheetspilot' ); ?></option>

				</select>

				<p class="description" style="margin-top:-6px;">

					<?php echo esc_html__( 'web_search: search the web for fresh information.', 'sheetspilot' ); ?><br />

					<?php echo esc_html__( 'file_search: search across uploaded/attached files.', 'sheetspilot' ); ?><br />

					<?php echo esc_html__( 'code_interpreter: run code for analysis, like analize html page.', 'sheetspilot' ); ?><br />

					<?php echo esc_html__( 'computer_use: perform browser/computer-like actions.', 'sheetspilot' ); ?>

				</p>



				<label for="ubai_prompt_tester_system"><strong><?php echo esc_html__( 'System message (optional)', 'sheetspilot' ); ?></strong></label>

				<textarea id="ubai_prompt_tester_system" rows="4" class="large-text code" spellcheck="false"></textarea>



				<label for="ubai_prompt_tester_user"><strong><?php echo esc_html__( 'User message', 'sheetspilot' ); ?></strong></label>

				<textarea id="ubai_prompt_tester_user" rows="8" class="large-text code" spellcheck="false" placeholder="<?php echo esc_attr( __( 'Your prompt…', 'sheetspilot' ) ); ?>"></textarea>



				<p>

					<button type="button" class="button button-primary" id="ubai_prompt_tester_send">

						<?php esc_html_e( 'Send request', 'sheetspilot' ); ?>

					</button>

					<span id="ubai_prompt_tester_loader" style="margin-left:8px;display:none;"><?php esc_html_e( 'Calling API…', 'sheetspilot' ); ?></span>

				</p>

			</div>

		</div>



		<div id="ubai_prompt_tester_tab_image" class="ubai-prompt-tester-tab-panel" data-tab-panel="image" style="display:none;">

			<div style="margin: 16px 0; display: grid; gap: 12px; max-width: 980px;">

				<label for="ubai_prompt_tester_image_mode"><strong><?php echo esc_html__( 'Image action', 'sheetspilot' ); ?></strong></label>

				<select id="ubai_prompt_tester_image_mode" class="large-text">

					<option value="generate"><?php echo esc_html__( 'Generate image', 'sheetspilot' ); ?></option>

					<option value="edit"><?php echo esc_html__( 'Edit image', 'sheetspilot' ); ?></option>

				</select>



				<div id="ubai_prompt_tester_image_generate_fields">

					<label for="ubai_prompt_tester_image_prompt"><strong><?php echo esc_html__( 'Image prompt', 'sheetspilot' ); ?></strong></label>

					<textarea id="ubai_prompt_tester_image_prompt" rows="6" class="large-text code" spellcheck="false" placeholder="<?php echo esc_attr( __( 'Describe the image to generate…', 'sheetspilot' ) ); ?>"></textarea>



					<label for="ubai_prompt_tester_image_ratio"><strong><?php echo esc_html__( 'Aspect ratio', 'sheetspilot' ); ?></strong></label>

					<select id="ubai_prompt_tester_image_ratio" class="large-text">

						<?php

						foreach ( $aspect_ratios as $ratio ) {

							$label = isset( $ratio_labels[ $ratio ] ) ? $ratio_labels[ $ratio ] : $ratio;

							echo '<option value="' . esc_attr( $ratio ) . '">' . esc_html( $label ) . '</option>';

						}

						?>

					</select>



					<label for="ubai_prompt_tester_image_quality"><strong><?php echo esc_html__( 'Quality', 'sheetspilot' ); ?></strong></label>

					<select id="ubai_prompt_tester_image_quality" class="large-text">

						<option value="default"><?php echo esc_html__( 'Default', 'sheetspilot' ); ?></option>

						<option value="low"><?php echo esc_html__( 'Low', 'sheetspilot' ); ?></option>

						<option value="medium"><?php echo esc_html__( 'Medium', 'sheetspilot' ); ?></option>

						<option value="high"><?php echo esc_html__( 'High', 'sheetspilot' ); ?></option>

					</select>



					<label for="ubai_prompt_tester_image_format"><strong><?php echo esc_html__( 'Output format', 'sheetspilot' ); ?></strong></label>

					<select id="ubai_prompt_tester_image_format" class="large-text">

						<option value="default"><?php echo esc_html__( 'Default', 'sheetspilot' ); ?></option>

						<option value="png"><?php echo esc_html__( 'PNG', 'sheetspilot' ); ?></option>

						<option value="jpeg"><?php echo esc_html__( 'JPEG', 'sheetspilot' ); ?></option>

						<option value="webp"><?php echo esc_html__( 'WebP', 'sheetspilot' ); ?></option>

					</select>

				</div>



				<div id="ubai_prompt_tester_image_edit_fields" style="display:none;">

					<label><strong><?php echo esc_html__( 'Source image', 'sheetspilot' ); ?></strong></label>

					<div class="ubai-prompt-tester-image-picker">

						<button type="button" class="button" id="ubai_prompt_tester_select_image">

							<?php esc_html_e( 'Select from Media Library', 'sheetspilot' ); ?>

						</button>

						<button type="button" class="button" id="ubai_prompt_tester_clear_image" style="display:none;">

							<?php esc_html_e( 'Clear', 'sheetspilot' ); ?>

						</button>

						<input type="hidden" id="ubai_prompt_tester_image_attachment_id" value="" />

						<input type="hidden" id="ubai_prompt_tester_image_url" value="" />

						<div id="ubai_prompt_tester_image_thumb_wrap" style="display:none;margin-top:10px;">

							<img id="ubai_prompt_tester_image_thumb" src="" alt="" style="max-width:240px;max-height:240px;border:1px solid #c3c4c7;border-radius:2px;background:#fff;" />

						</div>

					</div>



					<label for="ubai_prompt_tester_image_edit_prompt"><strong><?php echo esc_html__( 'Edit prompt', 'sheetspilot' ); ?></strong></label>

					<textarea id="ubai_prompt_tester_image_edit_prompt" rows="6" class="large-text code" spellcheck="false" placeholder="<?php echo esc_attr( __( 'Describe how to edit the image…', 'sheetspilot' ) ); ?>"></textarea>

				</div>



				<p>

					<button type="button" class="button button-primary" id="ubai_prompt_tester_image_send">

						<?php esc_html_e( 'Send image request', 'sheetspilot' ); ?>

					</button>

					<span id="ubai_prompt_tester_image_loader" style="margin-left:8px;display:none;"><?php esc_html_e( 'Calling API…', 'sheetspilot' ); ?></span>

				</p>

			</div>

		</div>



		<div id="ubai_prompt_tester_tab_compress" class="ubai-prompt-tester-tab-panel" data-tab-panel="compress" style="display:none;">

			<div style="margin: 16px 0; display: grid; gap: 12px; max-width: 980px;">

				<div id="ubai_prompt_tester_compress_libs" class="ubai-prompt-tester-compress-libs"></div>

				<div id="ubai_prompt_tester_compress_engine_wrap" style="display:none;">

					<label for="ubai_prompt_tester_compress_engine_select"><strong><?php echo esc_html__( 'Compression library', 'sheetspilot' ); ?></strong></label>

					<select id="ubai_prompt_tester_compress_engine_select" class="large-text">

						<option value="gd"><?php echo esc_html__( 'GD', 'sheetspilot' ); ?></option>

						<option value="imagick"><?php echo esc_html__( 'Imagick', 'sheetspilot' ); ?></option>

					</select>

					<p class="description"><?php echo esc_html__( 'Both GD and Imagick are installed. Choose which library to use for this compression.', 'sheetspilot' ); ?></p>

				</div>

				<div class="ubai-prompt-tester-image-picker">

					<label><strong><?php echo esc_html__( 'Image', 'sheetspilot' ); ?></strong></label>

					<p>

						<button type="button" class="button" id="ubai_prompt_tester_compress_select_image">

							<?php echo esc_html__( 'Select image from library', 'sheetspilot' ); ?>

						</button>

						<button type="button" class="button" id="ubai_prompt_tester_compress_clear_image" style="display:none;">

							<?php echo esc_html__( 'Clear', 'sheetspilot' ); ?>

						</button>

					</p>

					<input type="hidden" id="ubai_prompt_tester_compress_attachment_id" value="" />

					<input type="hidden" id="ubai_prompt_tester_compress_image_url" value="" />

					<div id="ubai_prompt_tester_compress_thumb_wrap" style="display:none;margin-top:10px;">

						<img id="ubai_prompt_tester_compress_thumb" src="" alt="" style="max-width:240px;max-height:240px;border:1px solid #c3c4c7;border-radius:2px;background:#fff;" />

						<p id="ubai_prompt_tester_compress_selected_size" class="description" style="margin-top:8px;"></p>

					</div>

				</div>

				<p>

					<button type="button" class="button button-primary" id="ubai_prompt_tester_compress_btn">

						<?php echo esc_html__( 'Compress', 'sheetspilot' ); ?>

					</button>

					<span id="ubai_prompt_tester_compress_loader" style="margin-left:8px;display:none;"><?php esc_html_e( 'Compressing…', 'sheetspilot' ); ?></span>

				</p>

			</div>

			<div id="ubai_prompt_tester_compress_result_wrap" class="ubai-prompt-tester-compress-result" style="display:none;margin-top:20px;">

				<h2><?php echo esc_html__( 'Compression result', 'sheetspilot' ); ?></h2>

				<p id="ubai_prompt_tester_compress_engine" class="description"></p>

				<p id="ubai_prompt_tester_compress_saved" class="description" style="font-weight:600;"></p>

				<div class="ubai-prompt-tester-compress-result__grid">

					<div class="ubai-prompt-tester-compress-result__col">

						<h3><?php echo esc_html__( 'Before', 'sheetspilot' ); ?></h3>

						<img id="ubai_prompt_tester_compress_before_img" src="" alt="" />

						<p id="ubai_prompt_tester_compress_before_size" class="description"></p>

					</div>

					<div class="ubai-prompt-tester-compress-result__col">

						<h3><?php echo esc_html__( 'After', 'sheetspilot' ); ?></h3>

						<img id="ubai_prompt_tester_compress_after_img" src="" alt="" />

						<p id="ubai_prompt_tester_compress_after_size" class="description"></p>

					</div>

				</div>

			</div>

		</div>



		<div id="ubai_prompt_tester_image_result_wrap" style="display:none;margin-top:12px;">

			<h2><?php echo esc_html__( 'Image result', 'sheetspilot' ); ?></h2>

			<img id="ubai_prompt_tester_image_result" src="" alt="" style="max-width:480px;max-height:480px;border:1px solid #c3c4c7;border-radius:2px;background:#fff;" />

		</div>



		<div id="ubai_prompt_tester_debug_wrap" style="display:none;margin-top:20px;">
			<h2><?php echo esc_html__( 'Debug', 'sheetspilot' ); ?></h2>
			<pre id="ubai_prompt_tester_debug" class="unlimited-ai-log-pre large-text code" style="max-height:480px;overflow:auto;background:#fff8e5;padding:12px;border:1px solid #dba617;border-radius:2px;white-space:pre-wrap;word-break:break-word;"></pre>
		</div>

		<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px;" class="ubai-prompt-tester-columns">

			<div>

				<h2><?php echo esc_html__( 'Request (JSON)', 'sheetspilot' ); ?></h2>

				<pre id="ubai_prompt_tester_request" class="unlimited-ai-log-pre large-text code" style="max-height:480px;overflow:auto;background:#fafafa;padding:12px;border:1px solid #c3c4c7;border-radius:2px;"></pre>

			</div>

			<div>

				<h2><?php echo esc_html__( 'Response', 'sheetspilot' ); ?></h2>

				<pre id="ubai_prompt_tester_response" class="unlimited-ai-log-pre large-text code" style="max-height:480px;overflow:auto;background:#fafafa;padding:12px;border:1px solid #c3c4c7;border-radius:2px;"></pre>

			</div>

		</div>

	</div>

	<style>

		.ubai-prompt-tester-title {
			display: flex;
			align-items: center;
			gap: 12px;
			flex-wrap: wrap;
		}

		.ubai-prompt-tester-debug-badge {
			color: #16a34a;
			font-size: 14px;
			font-weight: 600;
			line-height: 1;
			letter-spacing: 0.02em;
		}

		.ubai-prompt-tester-tabs { margin-top: 16px; }

		.ubai-prompt-tester-compress-libs {
			padding: 12px 14px;
			border: 1px solid #c3c4c7;
			border-radius: 2px;
			background: #f6f7f7;
			max-width: 980px;
		}

		.ubai-prompt-tester-compress-libs.is-warning {
			border-color: #d63638;
			background: #fcf0f1;
		}

		.ubai-prompt-tester-compress-libs__item { margin: 4px 0; }

		.ubai-prompt-tester-compress-libs__item.is-ok { color: #1d6f42; }

		.ubai-prompt-tester-compress-libs__item.is-missing { color: #8c8f94; }

		.ubai-prompt-tester-compress-result__grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
			max-width: 980px;
		}

		.ubai-prompt-tester-compress-result__col img {
			max-width: 100%;
			max-height: 360px;
			border: 1px solid #c3c4c7;
			border-radius: 2px;
			background: #fff;
		}

		@media(max-width: 960px){
			.ubai-prompt-tester-columns { grid-template-columns: 1fr !important; }
			.ubai-prompt-tester-compress-result__grid { grid-template-columns: 1fr; }
		}

	</style>



	<script>

	var g_doublyNonce = "<?php echo esc_js( $nonce ); ?>";

	var g_urlAjaxActionsSheetsPilot = "<?php echo esc_js( esc_url( SheetsPilotGlobals::$urlAjax ) ); ?>";

	var g_promptTesterPreload = <?php echo wp_json_encode( $prompt_tester_preload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE ); ?>;

	var g_promptTesterCompressLibs = <?php echo wp_json_encode( $compress_library_status, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE ); ?>;



	jQuery(document).ready(function(){

		if (typeof window.sheetspilotPromptTesterInit === "function") {

			window.sheetspilotPromptTesterInit();

		}

	});

	</script>

		<?php

	}



	/**

	 * Constructor — render HTML.

	 */

	public function __construct() {

		$this->putViewHtml();

	}

}



new SheetsPilot_PluginViewPromptTester();

