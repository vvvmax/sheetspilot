<?php

/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if (! defined('ABSPATH')) exit;
if (!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilot_PluginViewWelcome
{

	var $save_posts_prefix = "sheetspilot_postseditor";
	var $process_content_prefix = "sheetspilot_chatgpt_processing";

	var $savedOption;
	var $savedColumns;
	var $postTypeSavedColumns;
	var $postTypeSavedColumnsOrder;
	var $rowsPerPage;

	/**
	 * constructor
	 */
	public function __construct()
	{
		$this->savedOption = SheetsPilotHelper::getEditorPageSettings('post_type');
		if (!$this->savedOption || $this->savedOption == '') {
			$this->savedOption = 'post';
		}
		$this->rowsPerPage = SheetsPilotHelper::getEditorPageSettings('rows_per_page');
		$this->postTypeSavedColumns = SheetsPilotHelper::getEditorPageSettings($this->savedOption . '_columns');
		if (!$this->postTypeSavedColumns) {
			$this->postTypeSavedColumns = [];
		}
		$this->postTypeSavedColumnsOrder = SheetsPilotHelper::getEditorPageSettings($this->savedOption . '_columns_order');
		if (!$this->postTypeSavedColumnsOrder) {
			$this->postTypeSavedColumnsOrder = [];
		}
	 
		// patch in case order of columns got mixed
		$remove = 'id';
		$this->postTypeSavedColumnsOrder = array_values(array_filter($this->postTypeSavedColumnsOrder, function ($item) use ($remove) {
			return $item !== $remove;
		}));
		$remove = 'post_title';
		$this->postTypeSavedColumnsOrder = array_values(array_filter($this->postTypeSavedColumnsOrder, function ($item) use ($remove) {
			return $item !== $remove;
		}));
		$remove = 'bulk';
		$this->postTypeSavedColumnsOrder = array_values(array_filter($this->postTypeSavedColumnsOrder, function ($item) use ($remove) {
			return $item !== $remove;
		}));
		$newItems = ['bulk', 'id', 'post_title'];
		array_unshift($this->postTypeSavedColumnsOrder, ...$newItems);

		// patch in case order of columns got mixed END

		if (!is_array($this->savedColumns)) {
			$this->savedColumns = [];
		}
		if (!is_array($this->postTypeSavedColumns)) {
			$this->postTypeSavedColumns = [];
		}
		if (!is_array($this->postTypeSavedColumnsOrder)) {
			$this->postTypeSavedColumnsOrder = [];
		}

		$this->putViewHtml();

		add_action('admin_footer', [$this, 'callJSOutput']);
	}

	/**
	 * Get current post type title
	 */
	private function getCurrentPostTypeTitle()
	{
		$postTypeObject = get_post_type_object($this->savedOption);
		if ($postTypeObject && !empty($postTypeObject->labels->singular_name)) {
			return $postTypeObject->labels->singular_name;
		}

		return __('Row', 'sheetspilot');
	}

	/**
	 * draw save settings button
	 */
	protected function drawSaveSettingsButton()
	{

		$prefix = $this->save_posts_prefix;

		$buttonText = esc_html__("Save Posts", 'sheetspilot');

?>
		<div class="uc-button-action-wrapper">

			<a id="<?php echo esc_attr($prefix) ?>_button_save_settings" data-prefix="<?php echo esc_attr($prefix) ?>" class="unite-button-primary doubly-button-save-settings" href="javascript:void(0)"><?php echo esc_html($buttonText) ?></a>

			<div style="padding-top:6px;">

				<span id="<?php echo esc_attr($prefix) ?>_loader_save" class="loader_text" style="display:none"><?php esc_html_e("Saving...", 'sheetspilot') ?></span>
				<span id="<?php echo esc_attr($prefix) ?>_message_saved" class="unite-color-green" style="display:none"></span>

			</div>
		</div>

	<?php
	}

	/**
	 * Draw Button that will process content
	 */
	protected function drawProcessContentButton()
	{

		$prefix = $this->process_content_prefix;

		$buttonText = esc_html__("Translate Content", 'sheetspilot');

	?>
		<div class="uc-button-action-wrapper">
			<a id="<?php echo esc_attr($prefix) ?>_button_translate" data-prefix="<?php echo esc_attr($prefix) ?>" class="unite-button-secondary doubly-button-save-settings" href="javascript:void(0)"><?php echo esc_html($buttonText) ?></a>

			<div style="padding-top:6px;">
				<span id="<?php echo esc_attr($prefix) ?>_loader_save" class="loader_text" style="display:none"><?php esc_html_e("Saving...", 'sheetspilot') ?></span>
				<span id="<?php echo esc_attr($prefix) ?>_message_saved" class="unite-color-green" style="display:none"></span>
			</div>
		</div>

	<?php
	}


	/**
	 * error handling html block
	 */
	private function returnErrorHandlerHtml($prefix)
	{
		return '<div id="' . esc_attr($prefix) . '_save_settings_error" class="unite_error_message" style="display:none"></div>';
	}

	/**
	 * Render panel control buttons (close/expand).
	 */
	private function renderPanelControls($base_class, $close_label, $expand_label, $stroke_color)
	{
		$close_class = $base_class . '__close unlimitedai-plugin__panel-control unlimitedai-plugin__panel-control--close';
		$expand_class = $base_class . '__expand unlimitedai-plugin__panel-control unlimitedai-plugin__panel-control--expand';

		$this->renderPanelControlButton('close', $close_class, $close_label, $stroke_color);
		$this->renderPanelControlButton('expand', $expand_class, $expand_label, $stroke_color);
	}

	/**
	 * Free version sidebar upsell (AI Cockpit is visible but not functional).
	 */
	private function renderFreeSidebarUpsell()
	{
		$features = array(
			__('Generate or edit SEO titles & meta in bulk', 'sheetspilot'),
			__('Bulk edit WooCommerce products with AI', 'sheetspilot'),
			__('Translate content into any language', 'sheetspilot'),
			__('Create & enhance images with prompts', 'sheetspilot'),
			__('Rewrite copy across hundreds of rows', 'sheetspilot'),
			__('Save reusable prompts & brand voice', 'sheetspilot'),
		);
		?>
		<div class="unlimitedai-plugin__sidebar-free-upsell">
			<div class="unlimitedai-plugin__sidebar-free-upsell__icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.019a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path>
					<path d="M5 21h14"></path>
				</svg>
			</div>
			<h3 class="unlimitedai-plugin__sidebar-free-upsell__title"><?php esc_html_e('Unlock AI with Pro', 'sheetspilot'); ?></h3>
			<p class="unlimitedai-plugin__sidebar-free-upsell__description"><?php esc_html_e('Edit thousands of posts and products with AI, uploading a spreadsheet with live inline editing directly inside your WordPress admin.', 'sheetspilot'); ?></p>
			<a class="unlimitedai-plugin__sidebar-free-upsell__btn" href="<?php echo esc_url(SheetsPilotGlobals::PRO_VERSION_URL); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Upgrade to Pro', 'sheetspilot'); ?></a>
			<p class="unlimitedai-plugin__sidebar-free-upsell__guarantee"><span aria-hidden="true">&#9733;</span> <?php esc_html_e('30-Day Money-Back Guarantee', 'sheetspilot'); ?> <span aria-hidden="true">&#9733;</span></p>
			<ul class="unlimitedai-plugin__sidebar-free-upsell__features">
				<?php foreach ($features as $feature) : ?>
					<li class="unlimitedai-plugin__sidebar-free-upsell__feature">
						<span class="unlimitedai-plugin__sidebar-free-upsell__feature-icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
								<path d="M20 6 9 17l-5-5"></path>
							</svg>
						</span>
						<span class="unlimitedai-plugin__sidebar-free-upsell__feature-text"><?php echo esc_html($feature); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render a single panel control button.
	 */
	private function renderPanelControlButton($type, $button_class, $aria_label, $stroke_color)
	{
		$button_class = esc_attr($button_class);
		$aria_label = esc_attr($aria_label);
		$stroke_color = esc_attr($stroke_color);

		if ($type === "close") {
			$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($stroke_color) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>';
		} else {
			$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($stroke_color) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6"></path><path d="M9 21H3v-6"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path></svg>';
		}

		echo '<button type="button" class="' . esc_attr($button_class) . '" aria-label="' . esc_attr($aria_label) . '">' . $svg . '</button>'; 
	}

	/**
	 * Demo options for the Latest Prompts dropdown (static, no server fetch).
	 *
	 * @return array<int, array{id: string, label: string, text: string}>
	 */
	private function get_demo_latest_prompts()
	{
		return array(
			array(
				'id'    => 'demo_summarize',
				'label' => __('Summarize this text', 'sheetspilot'),
				'text'  => __('Summarize the following text in 2-3 sentences.', 'sheetspilot'),
			),
			array(
				'id'    => 'demo_translate',
				'label' => __('Translate to Spanish', 'sheetspilot'),
				'text'  => __('Translate the following text to Spanish.', 'sheetspilot'),
			),
			array(
				'id'    => 'demo_grammar',
				'label' => __('Improve grammar', 'sheetspilot'),
				'text'  => __('Improve the grammar and clarity of the following text. Keep the same meaning.', 'sheetspilot'),
			),
			array(
				'id'    => 'demo_shorter',
				'label' => __('Make it shorter', 'sheetspilot'),
				'text'  => __('Condense the following text into a shorter version while keeping the main points.', 'sheetspilot'),
			),
		);
	}

	/**
	 * put view html
	 */
	private function putViewHtml()
	{

		$title = __("Unlimited AI Plugin", 'sheetspilot');
		$latest_prompts_demo = $this->get_demo_latest_prompts();
		$saved_prompts_dropdown = array();
		$prompt_history_initial = array(
			'items' => array(),
		);
		if (SheetsPilotGlobals::$isPro == true) {
			$saved_prompts_dropdown = SheetsPilot_PromptHistory::getSavedForDropdown();
			$prompt_history_initial = SheetsPilot_PromptHistory::getForPanel(100, 'all', null);
		}

		$enable_debug_tool = '0';

		if( SHEETSPILOT_PRO_PLUGIN_ACTIVE ){
			$settings = SheetsPilotHelper::getGeneralSettings();
			$enable_debug_tool = isset($settings['enable_debug_prompt_tool']) ? $settings['enable_debug_prompt_tool'] : '0';
		}
	?>

		<div id="unlimitedai-plugin" class="unlimitedai-plugin wrap unlimitedai-welcome-view-welcome<?php echo SheetsPilotGlobals::$isPro ? '' : ' unlimitedai-plugin--free'; ?>">

			<h1></h1>

			<div id="div_debug" class="unlimitedai-plugin__debug unite-div-debug" style="display:none"></div>

			<div class="unlimitedai-plugin__intro">
				<div class="unlimitedai-plugin__intro-item">
					<div class="unlimitedai-plugin__intro-descr">
						<div class="unlimitedai-plugin__intro-logo">
							<svg width="92" height="24" viewBox="0 0 92 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<g clip-path="url(#clip0_94_4)">
									<path d="M15.794 0H7.897C3.53561 0 0 3.58172 0 8V16C0 20.4183 3.53561 24 7.897 24H15.794C20.1554 24 23.691 20.4183 23.691 16V8C23.691 3.58172 20.1554 0 15.794 0Z" fill="#296BFF" />
									<path d="M16.9785 3.85863V9.74986L14.5798 9.76378C14.5829 9.7731 14.5767 9.7545 14.5798 9.76378C13.6082 9.9259 12.8724 10.7834 12.8724 11.7995C12.8724 12.546 12.6723 13.2466 12.3227 13.8496C12.1857 14.0861 12.0254 14.3075 11.8454 14.5109C11.3878 15.0293 10.8015 15.4309 10.1355 15.6659C9.70734 15.8171 9.24607 15.8992 8.76574 15.8992V17.9493C8.99736 17.9493 9.22594 17.9364 9.45033 17.9117C10.3154 17.8161 11.1262 17.542 11.8454 17.1264C12.4127 16.7991 12.9225 16.384 13.3563 15.8997C13.4246 15.8238 13.4913 15.7459 13.5554 15.6664C13.9919 15.1284 14.3388 14.5156 14.5746 13.8501C14.5777 13.8408 14.5813 13.8315 14.5844 13.8217C14.8052 13.1888 14.9252 12.5083 14.9252 11.8H16.9785C16.9785 11.8175 16.9785 11.8351 16.978 11.8531C16.9738 12.5424 16.8844 13.211 16.7194 13.8501C16.5452 14.528 16.2856 15.1723 15.9531 15.7707C15.9293 15.814 15.9045 15.8574 15.8797 15.9002C15.6056 16.3737 15.2856 16.8166 14.9252 17.224C14.7106 17.4666 14.482 17.6964 14.2406 17.9122C13.5399 18.5384 12.7312 19.0459 11.8454 19.4037C10.8945 19.7878 9.8547 20 8.76574 20C8.05683 20 7.36908 19.9101 6.7124 19.7413V13.8501L9.11112 13.8362C9.11423 13.8455 9.10804 13.8269 9.11112 13.8362C10.0827 13.6741 10.8185 12.8165 10.8185 11.8005C10.8185 11.054 11.0186 10.3534 11.3682 9.75037C11.5052 9.5139 11.6655 9.29242 11.8454 9.08902C12.3031 8.57066 12.8894 8.16902 13.5554 7.9341C13.9836 7.78286 14.4448 7.70074 14.9252 7.70074V5.65062C14.6935 5.65062 14.465 5.66354 14.2406 5.6883C13.3755 5.78382 12.5647 6.05798 11.8454 6.47358C11.2782 6.8009 10.7684 7.21598 10.3346 7.70022C10.2663 7.77614 10.1996 7.8541 10.1355 7.93358C9.69905 8.47154 9.35209 9.08438 9.11629 9.74986C9.11321 9.75914 9.10957 9.76842 9.10649 9.77826C8.88569 10.4112 8.76574 11.0917 8.76574 11.8H6.71292C6.71292 11.7824 6.71292 11.7649 6.71343 11.7468C6.71757 11.0576 6.80701 10.389 6.97198 9.74986C7.14622 9.07198 7.4058 8.42766 7.7383 7.8293C7.76207 7.78594 7.78691 7.74258 7.8117 7.69974C8.08577 7.2263 8.40583 6.78334 8.76625 6.37598C8.98081 6.13334 9.20939 5.90362 9.45084 5.68778C10.1515 5.06154 10.9602 4.55406 11.846 4.19626C12.7969 3.81165 13.8362 3.59998 14.9252 3.59998C15.6341 3.59998 16.3218 3.68981 16.9785 3.85863Z" fill="white" />
									<path d="M90.838 16.9664C90.0629 16.9664 89.4607 16.7531 89.0311 16.3264C88.6098 15.8912 88.3994 15.2811 88.3994 14.496V11.424H87.2117V9.92637H87.338C87.6748 9.92637 87.9358 9.83677 88.1214 9.65757C88.3066 9.47837 88.3994 9.21809 88.3994 8.87677V8.33917H90.0546V9.92637H91.634V11.424H90.0546V14.4064C90.0546 14.6368 90.0925 14.8331 90.1683 14.9952C90.2524 15.1573 90.3788 15.2811 90.5474 15.3664C90.7243 15.4517 90.9473 15.4944 91.217 15.4944C91.2759 15.4944 91.3434 15.4901 91.4192 15.4816C91.5033 15.4731 91.5834 15.4645 91.6593 15.456V16.8896C91.5412 16.9067 91.4066 16.9237 91.2549 16.9408C91.1033 16.9579 90.9643 16.9664 90.838 16.9664Z" fill="white" />
									<path d="M83.4514 17.0432C82.7778 17.0432 82.1626 16.8853 81.6067 16.5696C81.0594 16.2539 80.6212 15.8229 80.2926 15.2768C79.9728 14.7307 79.8125 14.1077 79.8125 13.408C79.8125 12.7083 79.9728 12.0853 80.2926 11.5392C80.6212 10.9931 81.0594 10.5621 81.6067 10.2464C82.1544 9.93071 82.7691 9.77283 83.4514 9.77283C84.1254 9.77283 84.7363 9.93071 85.2835 10.2464C85.8312 10.5621 86.2651 10.9931 86.585 11.5392C86.9135 12.0768 87.0777 12.6997 87.0777 13.408C87.0777 14.1077 86.9135 14.7307 86.585 15.2768C86.2564 15.8229 85.8186 16.2539 85.2709 16.5696C84.7236 16.8853 84.1172 17.0432 83.4514 17.0432ZM83.4514 15.5072C83.8222 15.5072 84.1464 15.4176 84.4243 15.2384C84.711 15.0592 84.9341 14.8117 85.094 14.496C85.2626 14.1717 85.3467 13.8091 85.3467 13.408C85.3467 12.9984 85.2626 12.64 85.094 12.3328C84.9341 12.0171 84.711 11.7696 84.4243 11.5904C84.1464 11.4027 83.8222 11.3088 83.4514 11.3088C83.0724 11.3088 82.7399 11.4027 82.4533 11.5904C82.167 11.7696 81.9396 12.0171 81.771 12.3328C81.611 12.64 81.5309 12.9984 81.5309 13.408C81.5309 13.8091 81.611 14.1717 81.771 14.496C81.9396 14.8117 82.167 15.0592 82.4533 15.2384C82.7399 15.4176 83.0724 15.5072 83.4514 15.5072Z" fill="white" />
									<path d="M77.4126 16.8896V7.20001H79.0678V16.8896H77.4126Z" fill="white" />
									<path d="M74.7344 16.8896V9.92638H76.3896V16.8896H74.7344ZM74.7344 9.14558V7.35358H76.3896V9.14558H74.7344Z" fill="white" />
									<path d="M67.1855 16.8896V7.35358H70.6981C71.3386 7.35358 71.9028 7.47306 72.3912 7.71198C72.8884 7.94238 73.2757 8.2837 73.5537 8.73598C73.8317 9.1797 73.9706 9.72158 73.9706 10.3616C73.9706 10.9931 73.8277 11.5349 73.5411 11.9872C73.2631 12.4309 72.8801 12.7723 72.3912 13.0112C71.9028 13.2501 71.3386 13.3696 70.6981 13.3696H68.9039V16.8896H67.1855ZM68.9039 11.8336H70.7234C71.0353 11.8336 71.3046 11.7739 71.5321 11.6544C71.7595 11.5264 71.9364 11.3515 72.0627 11.1296C72.1891 10.9077 72.2523 10.6517 72.2523 10.3616C72.2523 10.0629 72.1891 9.8069 72.0627 9.59358C71.9364 9.3717 71.7595 9.20106 71.5321 9.08158C71.3046 8.95358 71.0353 8.88958 70.7234 8.88958H68.9039V11.8336Z" fill="white" />
									<path d="M63.6775 17.0432C62.9446 17.0432 62.3042 16.8683 61.7569 16.5184C61.2176 16.16 60.8472 15.6779 60.645 15.072L61.8833 14.4704C62.0602 14.8629 62.3042 15.1701 62.6161 15.392C62.9359 15.6139 63.2897 15.7248 63.6775 15.7248C63.9807 15.7248 64.2208 15.6565 64.3977 15.52C64.5746 15.3835 64.663 15.2043 64.663 14.9824C64.663 14.8459 64.6251 14.7349 64.5493 14.6496C64.4818 14.5557 64.385 14.4789 64.2587 14.4192C64.1406 14.3509 64.0099 14.2955 63.867 14.2528L62.7425 13.9328C62.1612 13.7621 61.719 13.5019 61.4158 13.152C61.1208 12.8021 60.9735 12.3883 60.9735 11.9104C60.9735 11.4837 61.0786 11.1125 61.2894 10.7968C61.5082 10.4725 61.8075 10.2208 62.1865 10.0416C62.5739 9.86243 63.0161 9.77283 63.5132 9.77283C64.1616 9.77283 64.7345 9.93071 65.2316 10.2464C65.7283 10.5621 66.0821 11.0059 66.293 11.5776L65.0294 12.1792C64.9114 11.8635 64.7136 11.6117 64.4356 11.424C64.1576 11.2363 63.8457 11.1424 63.5006 11.1424C63.2226 11.1424 63.0035 11.2064 62.8435 11.3344C62.6832 11.4624 62.6035 11.6288 62.6035 11.8336C62.6035 11.9616 62.637 12.0725 62.7046 12.1664C62.7717 12.2603 62.8645 12.3371 62.9825 12.3968C63.1089 12.4565 63.2518 12.512 63.4121 12.5632L64.5114 12.896C65.0756 13.0667 65.5096 13.3227 65.8128 13.664C66.1243 14.0053 66.2803 14.4235 66.2803 14.9184C66.2803 15.3365 66.1705 15.7077 65.9518 16.032C65.7327 16.3477 65.4294 16.5952 65.0421 16.7744C64.6543 16.9536 64.1995 17.0432 63.6775 17.0432Z" fill="white" />
									<path d="M59.4813 16.9664C58.7062 16.9664 58.104 16.7531 57.6744 16.3264C57.2531 15.8912 57.0427 15.2811 57.0427 14.496V11.424H55.855V9.92637H55.9813C56.3181 9.92637 56.5795 9.83677 56.7647 9.65757C56.9499 9.47837 57.0427 9.21809 57.0427 8.87677V8.33917H58.6979V9.92637H60.2773V11.424H58.6979V14.4064C58.6979 14.6368 58.7358 14.8331 58.8116 14.9952C58.8957 15.1573 59.0221 15.2811 59.1907 15.3664C59.3676 15.4517 59.5907 15.4944 59.8603 15.4944C59.9192 15.4944 59.9867 15.4901 60.0625 15.4816C60.1466 15.4731 60.2268 15.4645 60.3026 15.456V16.8896C60.1845 16.9067 60.0499 16.9237 59.8982 16.9408C59.7466 16.9579 59.6076 16.9664 59.4813 16.9664Z" fill="white" />
									<path d="M52.3765 17.0432C51.6689 17.0432 51.0498 16.8811 50.5191 16.5568C49.9884 16.2325 49.5758 15.7931 49.2809 15.2384C48.9863 14.6837 48.8386 14.0693 48.8386 13.3952C48.8386 12.6955 48.9863 12.0768 49.2809 11.5392C49.5841 10.9931 49.9928 10.5621 50.5065 10.2464C51.0289 9.93071 51.6101 9.77283 52.2501 9.77283C52.7895 9.77283 53.2609 9.86243 53.6653 10.0416C54.0783 10.2208 54.4277 10.4683 54.714 10.784C55.0006 11.0997 55.2194 11.4624 55.371 11.872C55.5226 12.2731 55.5985 12.7083 55.5985 13.1776C55.5985 13.2971 55.5902 13.4208 55.5732 13.5488C55.5649 13.6768 55.544 13.7877 55.51 13.8816H50.2032V12.6016H54.575L53.7916 13.2032C53.8674 12.8107 53.8465 12.4608 53.7284 12.1536C53.6191 11.8464 53.4339 11.6032 53.1725 11.424C52.9198 11.2448 52.6126 11.1552 52.2501 11.1552C51.905 11.1552 51.5974 11.2448 51.3278 11.424C51.0585 11.5947 50.852 11.8507 50.7086 12.192C50.574 12.5248 50.5234 12.9301 50.557 13.408C50.5234 13.8347 50.5783 14.2144 50.7213 14.5472C50.8729 14.8715 51.092 15.1232 51.3783 15.3024C51.6732 15.4816 52.0101 15.5712 52.3891 15.5712C52.7682 15.5712 53.0884 15.4901 53.3494 15.328C53.6191 15.1659 53.8295 14.9483 53.9811 14.6752L55.3205 15.3408C55.1858 15.6736 54.9754 15.968 54.6887 16.224C54.4025 16.48 54.0613 16.6805 53.6653 16.8256C53.2779 16.9707 52.8483 17.0432 52.3765 17.0432Z" fill="white" />
									<path d="M45.145 17.0432C44.4375 17.0432 43.8183 16.8811 43.2877 16.5568C42.757 16.2325 42.3444 15.7931 42.0494 15.2384C41.7549 14.6837 41.6072 14.0693 41.6072 13.3952C41.6072 12.6955 41.7549 12.0768 42.0494 11.5392C42.3527 10.9931 42.7613 10.5621 43.275 10.2464C43.7974 9.93071 44.3786 9.77283 45.0187 9.77283C45.558 9.77283 46.0295 9.86243 46.4338 10.0416C46.8468 10.2208 47.1963 10.4683 47.4825 10.784C47.7692 11.0997 47.9879 11.4624 48.1396 11.872C48.2912 12.2731 48.367 12.7083 48.367 13.1776C48.367 13.2971 48.3587 13.4208 48.3417 13.5488C48.3334 13.6768 48.3125 13.7877 48.2786 13.8816H42.9718V12.6016H47.3436L46.5602 13.2032C46.636 12.8107 46.6151 12.4608 46.497 12.1536C46.3876 11.8464 46.2024 11.6032 45.941 11.424C45.6883 11.2448 45.3812 11.1552 45.0187 11.1552C44.6736 11.1552 44.366 11.2448 44.0963 11.424C43.827 11.5947 43.6205 11.8507 43.4772 12.192C43.3425 12.5248 43.292 12.9301 43.3256 13.408C43.292 13.8347 43.3469 14.2144 43.4898 14.5472C43.6414 14.8715 43.8606 15.1232 44.1469 15.3024C44.4418 15.4816 44.7786 15.5712 45.1577 15.5712C45.5367 15.5712 45.8569 15.4901 46.1179 15.328C46.3876 15.1659 46.5981 14.9483 46.7497 14.6752L48.089 15.3408C47.9544 15.6736 47.7439 15.968 47.4573 16.224C47.171 16.48 46.8299 16.6805 46.4338 16.8256C46.0465 16.9707 45.6169 17.0432 45.145 17.0432Z" fill="white" />
									<path d="M34.9011 16.8896V7.20001H36.5563V11.296L36.3289 11.0528C36.489 10.6347 36.7501 10.3189 37.1123 10.1056C37.4829 9.88373 37.9125 9.77281 38.4011 9.77281C38.9065 9.77281 39.3529 9.88373 39.7405 10.1056C40.1362 10.3275 40.4437 10.6389 40.6629 11.04C40.8816 11.4325 40.9914 11.8891 40.9914 12.4096V16.8896H39.3361V12.8064C39.3361 12.4992 39.2771 12.2347 39.1592 12.0128C39.0413 11.7909 38.877 11.6203 38.6664 11.5008C38.4642 11.3728 38.2242 11.3088 37.9462 11.3088C37.6767 11.3088 37.4366 11.3728 37.226 11.5008C37.0154 11.6203 36.8512 11.7909 36.7332 12.0128C36.6153 12.2347 36.5563 12.4992 36.5563 12.8064V16.8896H34.9011Z" fill="white" />
									<path d="M30.7257 17.0432C30.1276 17.0432 29.5717 16.9323 29.0578 16.7104C28.5524 16.4885 28.1144 16.1813 27.7438 15.7888C27.3816 15.3877 27.1162 14.9227 26.9478 14.3936L28.3755 13.7664C28.603 14.3125 28.9315 14.7435 29.3611 15.0592C29.7907 15.3664 30.275 15.52 30.8141 15.52C31.1174 15.52 31.3785 15.4731 31.5975 15.3792C31.8249 15.2768 31.9976 15.1403 32.1155 14.9696C32.2419 14.7989 32.3051 14.5941 32.3051 14.3552C32.3051 14.0736 32.2209 13.8432 32.0524 13.664C31.8923 13.4763 31.648 13.3312 31.3195 13.2288L29.5127 12.64C28.7799 12.4096 28.2281 12.064 27.8575 11.6032C27.4868 11.1424 27.3015 10.6005 27.3015 9.97761C27.3015 9.43149 27.4321 8.94933 27.6932 8.53121C27.9628 8.11309 28.3334 7.78881 28.8051 7.55841C29.2853 7.31949 29.8328 7.20001 30.4477 7.20001C31.0121 7.20001 31.5301 7.30241 32.0018 7.50721C32.4736 7.70349 32.8779 7.98081 33.2148 8.33921C33.5602 8.68909 33.8171 9.10293 33.9856 9.58081L32.5704 10.2208C32.3851 9.74293 32.1071 9.37601 31.7365 9.12001C31.3658 8.85549 30.9362 8.72321 30.4477 8.72321C30.1613 8.72321 29.9086 8.77441 29.6896 8.87681C29.4706 8.97069 29.2979 9.10721 29.1715 9.28641C29.0536 9.45709 28.9947 9.66189 28.9947 9.90081C28.9947 10.1653 29.0789 10.4 29.2474 10.6048C29.4158 10.8011 29.6727 10.9547 30.0181 11.0656L31.7491 11.616C32.4988 11.8635 33.059 12.2048 33.4296 12.64C33.8087 13.0752 33.9982 13.6128 33.9982 14.2528C33.9982 14.7989 33.8592 15.2811 33.5812 15.6992C33.3033 16.1173 32.92 16.4459 32.4314 16.6848C31.9429 16.9237 31.3743 17.0432 30.7257 17.0432Z" fill="white" />
								</g>
								<defs>
									<clipPath id="clip0_94_4">
										<rect width="92" height="24" fill="white" />
									</clipPath>
								</defs>
							</svg>
						</div>
						<div class="unlimitedai-plugin__intro-naming">
							<div class="unlimitedai-plugin__intro-version-group">
								<div class="unlimitedai-plugin__intro-version">v<?php echo esc_html(SHEETSPILOT_VERSION); ?></div>
								<?php if (SheetsPilotGlobals::$hasProFolder == true) : ?>
									<?php if (SheetsPilotGlobals::$isPro) : ?>
										<span class="unlimitedai-plugin__intro-license-badge unlimitedai-plugin__intro-license-badge--pro"><?php esc_html_e('PRO', 'sheetspilot'); ?></span>
									<?php else : ?>
										<span class="unlimitedai-plugin__intro-license-badge unlimitedai-plugin__intro-license-badge--free"><?php esc_html_e('FREE', 'sheetspilot'); ?></span>
									<?php endif; ?>
								<?php endif; ?>
								<?php if ( SheetsPilotGlobals::$debug_prompt_request ) : ?>
									<a class="unlimitedai-plugin__intro-debug-request" href="<?php echo esc_url( SheetsPilotHelper::getViewUrl( SheetsPilotGlobals::VIEW_SETTINGS ) ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr__( 'Open General Settings to turn off debug request mode', 'sheetspilot' ); ?>"><?php esc_html_e( 'Debug Request', 'sheetspilot' ); ?></a>
								<?php endif; ?>
							</div>
						</div>
					</div>
					<div class="unlimitedai-plugin__intro-status">
						<div class="unlimitedai-plugin__intro-status__icon unlimitedai-plugin__intro-status__icon--saved  uai-hidden"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
								<path d="M20 6 9 17l-5-5"></path>
							</svg></div>
						<div class="unlimitedai-plugin__intro-status__icon unlimitedai-plugin__intro-status__icon--saving uai-hidden"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span></div>
						<div class="unlimitedai-plugin__intro-status__icon unlimitedai-plugin__intro-status__icon--error uai-hidden"></div>
						<div class="unlimitedai-plugin__intro-status__text unlimitedai-plugin__intro-status__text--saved uai-hidden">Saved</div>
						<div class="unlimitedai-plugin__intro-status__text unlimitedai-plugin__intro-status__text--saving uai-hidden">Saving...</div>
						<div class="unlimitedai-plugin__intro-status__text unlimitedai-plugin__intro-status__text--error uai-hidden">Error</div>

						<div id="uba_loader_saving" class="" style="display:none;">
							<div class="unlimitedai-plugin__intro-status__icon unlimitedai-plugin__intro-status__icon--saving"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span></div>
							<div class="unlimitedai-plugin__intro-status__text unlimitedai-plugin__intro-status__text--saving">Saving...</div>
						</div>
						<div id="uba_loader_processing" class="" style="display:none;">
							<div class="unlimitedai-plugin__intro-status__icon unlimitedai-plugin__intro-status__icon--saving"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span></div>
						</div>
						<div id="uba_loader_saved" class="" style="display:none;">
							<div class="unlimitedai-plugin__intro-status__icon unlimitedai-plugin__intro-status__icon--saved  "><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
									<path d="M20 6 9 17l-5-5"></path>
								</svg></div>
							<div class="unlimitedai-plugin__intro-status__text unlimitedai-plugin__intro-status__text--saved ">Saved</div>
						</div>
						<div id="uba_loader_error" class="" style="display:none;">
							<div class="unlimitedai-plugin__intro-status__text unlimitedai-plugin__intro-status__text--error">Error</div>
						</div>

					</div>
				</div>

				<?php SheetsPilotHelper::echo_escape_editor_html($this->generate_type_selector()); ?>

				<div class="unlimitedai-plugin__intro-tools">
					<span class="unlimitedai-plugin__container">
						<button class="unlimitedai-plugin__main-menu-btn unlimitedai-plugin__btn">
							<span class="unlimitedai-plugin__intro-tools__item-icon">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fafafacc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<line x1="4" x2="20" y1="12" y2="12"></line>
									<line x1="4" x2="20" y1="6" y2="6"></line>
									<line x1="4" x2="20" y1="18" y2="18"></line>
								</svg>
							</span>
							<span class="unlimitedai-plugin__main-menu-btn--text"><?php esc_html_e('Menu', 'sheetspilot'); ?></span>
						</button>
						<span class="unlimitedai-plugin__dropdown unlimitedai-plugin__main-menu__dropdown">
							<?php if (SheetsPilotGlobals::$isPro == true) : ?>
								<a class="unlimitedai-plugin__dropdown-item" href="admin.php?page=sheetspilot_settings">
									<span class="unlimitedai-plugin__intro-tools__item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
											<circle cx="12" cy="12" r="3"></circle>
										</svg></span>
									<span class="unlimitedai-plugin__intro-tools__item-text"><?php esc_html_e('Settings', 'sheetspilot'); ?></span>
								</a>
								<a class="unlimitedai-plugin__dropdown-item" href="admin.php?page=sheetspilot_log">
									<span class="unlimitedai-plugin__intro-tools__item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
											<path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
											<path d="M10 9H8"></path>
											<path d="M16 13H8"></path>
											<path d="M16 17H8"></path>
										</svg></span>
									<span class="unlimitedai-plugin__intro-tools__item-text"><?php esc_html_e('Logs', 'sheetspilot'); ?></span>
								</a>
							<?php endif; ?>
							<a class="unlimitedai-plugin__dropdown-item" href="https://unitecms.ticksy.com/" target="_black">
								<span class="unlimitedai-plugin__intro-tools__item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<circle cx="12" cy="12" r="10"></circle>
										<path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
										<path d="M12 17h.01"></path>
									</svg></span>
								<span class="unlimitedai-plugin__intro-tools__item-text"><?php esc_html_e('Help & Support', 'sheetspilot'); ?></span>
							</a>
							<?php do_action( 'sheetspilot_postseditor_main_menu' ); ?>
							<div class="unlimitedai-plugin__main-menu__footer<?php echo SheetsPilotGlobals::$hasProFolder == true ? ' unlimitedai-plugin__main-menu__footer--with-switch' : ''; ?>">
								<?php if (SheetsPilotGlobals::$hasProFolder == true) : ?>
									<?php if (SheetsPilotGlobals::$isPro) : ?>
										<button type="button" class="unlimitedai-plugin__dropdown-item unlimitedai-plugin__main-menu__version-btn" data-ubai-version="free">
											<span class="unlimitedai-plugin__intro-tools__item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
													<rect width="20" height="14" x="2" y="5" rx="2"></rect>
													<path d="M2 10h20"></path>
												</svg></span>
											<span class="unlimitedai-plugin__intro-tools__item-text"><?php esc_html_e('To Free Version', 'sheetspilot'); ?></span>
										</button>
									<?php else : ?>
										<button type="button" class="unlimitedai-plugin__dropdown-item unlimitedai-plugin__main-menu__version-btn" data-ubai-version="pro">
											<span class="unlimitedai-plugin__intro-tools__item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
													<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path>
												</svg></span>
											<span class="unlimitedai-plugin__intro-tools__item-text"><?php esc_html_e('To Pro Version', 'sheetspilot'); ?></span>
										</button>
									<?php endif; ?>
								<?php endif; ?>
								<a class="unlimitedai-plugin__dropdown-item" target="_blank" href="<?php echo esc_url(home_url('/')); ?>">
									<span class="unlimitedai-plugin__intro-tools__item-icon">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
											<polyline points="9 22 9 12 15 12 15 22"></polyline>
										</svg>
									</span>
									<span class="unlimitedai-plugin__intro-tools__item-text"><?php esc_html_e('Go to Homepage', 'sheetspilot'); ?></span>
								</a>
								<a class="unlimitedai-plugin__dropdown-item unlimitedai-plugin__dropdown-item--section-footer" href="<?php echo esc_url(admin_url()); ?>">
									<span class="unlimitedai-plugin__intro-tools__item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M15 3h6v6"></path>
											<path d="M10 14 21 3"></path>
											<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
										</svg></span>
									<span class="unlimitedai-plugin__intro-tools__item-text"><?php esc_html_e('Exit to WordPress', 'sheetspilot'); ?></span>
								</a>
							</div>
						</span>
					</span>
					<span class="unlimitedai-plugin__buttons-separator" style="display: none"></span>
					<a class="unlimitedai-plugin__intro-tools__item unlimitedai-plugin__intro-tools__item--wp" href="<?php echo esc_url(admin_url()); ?>" >
						<span class="unlimitedai-plugin__intro-tools__item-icon unlimitedai-plugin__intro-tools__item-icon--wp">
							<svg class="size-28" width="62" height="62" viewBox="0 0 62 62" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_137_2)"><g clip-path="url(#clip1_137_2)"><rect width="62" height="62" rx="31" fill="#fafafacc"/><path d="M4.3793 31.001C4.3793 41.5586 10.445 50.6558 19.3185 54.924L6.73939 20.2195C5.27896 23.5881 4.3793 27.1806 4.3793 31.001ZM48.9731 29.6495C48.9731 26.3919 47.7365 24.0339 46.7259 22.3476C45.3784 20.1005 44.1439 18.3033 44.1439 16.0582C44.1439 13.5873 46.0542 11.3382 48.6362 11.3382H48.9731C44.2529 6.96509 37.9633 4.37915 31.0202 4.37915C21.6968 4.49211 13.6119 9.20812 8.78078 16.3971H10.4651C13.273 16.3971 17.5414 16.0603 17.5414 16.0603C19.0018 15.9473 19.1148 18.0814 17.7431 18.3073C17.7431 18.3073 16.2827 18.509 14.7113 18.509L24.3937 47.263L30.2335 29.8553L26.0781 18.511L23.2702 18.3093C21.8097 18.1964 22.0336 16.0623 23.3831 16.0623C23.3831 16.0623 27.7644 16.3991 30.3464 16.3991C33.1543 16.3991 37.4227 16.0623 37.4227 16.0623C38.8831 15.9493 38.9961 18.0834 37.6244 18.3093C37.6244 18.3093 36.164 18.511 34.5926 18.511L44.2751 47.154L46.97 38.2788C48.0936 34.5713 48.9912 31.9894 48.9912 29.6294L48.9731 29.6495ZM31.4498 33.361L23.4759 56.4973C25.836 57.171 28.418 57.6208 31 57.6208C34.1468 57.6208 37.0657 57.0601 39.8756 56.1605C39.7626 56.0475 39.7626 55.9587 39.6739 55.8236L31.4498 33.361ZM54.365 18.3134L54.5667 21.0082C54.5667 23.7031 54.1169 26.7368 52.5455 30.5552L44.4768 54.0284C52.3397 49.4233 57.7296 40.8869 57.7296 31.003C57.6167 26.398 56.3822 22.1277 54.361 18.3114L54.365 18.3134ZM31 0C13.9266 0 0 13.9282 0 30.999C0 48.0698 13.9266 62 31 62C48.0734 62 62 48.0718 62 31.001C62 13.9302 48.0734 0 31 0ZM31 60.6505C14.7133 60.6505 1.46044 47.3981 1.46044 30.999C1.46044 14.6926 14.7133 1.46039 31 1.46039C47.2867 1.46039 60.5396 14.7128 60.5396 30.999C60.5396 47.3981 47.2867 60.6505 31 60.6505Z" fill="#171717"/></g></g><defs><clipPath id="clip0_137_2"><rect width="62" height="62" fill="#fafafacc"/></clipPath><clipPath id="clip1_137_2"><rect width="62" height="62" rx="31" fill="#fafafacc"/></clipPath></defs></svg>
						</span>
					</a>
				</div>
			</div>

			<div class="unlimitedai-plugin__inner">

				<div class="unlimitedai-plugin__sidebar ue-active<?php echo SheetsPilotGlobals::$isPro ? '' : ' unlimitedai-plugin__sidebar--free-upsell'; ?>" id="ubai_ai_sidebar">
						<div class="unlimitedai-plugin__sidebar-header">
							<div class="unlimitedai-plugin__sidebar-title">
								<span class="unlimitedai-plugin__btn-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path>
										<path d="M20 3v4"></path>
										<path d="M22 5h-4"></path>
										<path d="M4 17v2"></path>
										<path d="M5 18H3"></path>
									</svg></span>
								<span class="unlimitedai-plugin__sidebar-header-title-text"><?php esc_html_e('AI Cockpit', 'sheetspilot'); ?></span>
							</div>
							<div class="unlimitedai-plugin__sidebar-header-actions">
								<?php if (SheetsPilotGlobals::$isPro) : ?>
								<button type="button" class="unlimitedai-plugin__sidebar-header-icon unlimitedai-plugin__btn" id="ubai_sidebar_history" aria-label="<?php echo esc_attr__('Prompt History', 'sheetspilot'); ?>" data-prompt-history-url="<?php echo esc_url(SheetsPilotHelper::getViewUrl(SheetsPilotGlobals::VIEW_PROMPT_HISTORY)); ?>">
									<span class="unlimitedai-plugin__btn-icon has-tooltip" data-title="<?php echo esc_attr__('Prompt History', 'sheetspilot'); ?>">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
											<path d="M3 3v5h5"></path>
											<path d="M12 7v5l4 2"></path>
										</svg>
									</span>
								</button>
								<?php endif; ?>
								<button class="unlimitedai-plugin__sidebar-toggle unlimitedai-plugin__btn" id="ubai_sidebar_toggle" aria-label="<?php echo esc_attr__('Collapse Sidebar', 'sheetspilot'); ?>">
									<span class="unlimitedai-plugin__btn-icon has-tooltip" data-title="<?php echo esc_attr__('Collapse Sidebar', 'sheetspilot'); ?>" data-title-expanded="<?php echo esc_attr__('Collapse Sidebar', 'sheetspilot'); ?>" data-title-collapsed="<?php echo esc_attr__('Expand Sidebar', 'sheetspilot'); ?>">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<rect width="18" height="18" x="3" y="3" rx="2"></rect>
											<path d="M9 3v18"></path>
											<path d="m16 15-3-3 3-3"></path>
										</svg>
									</span>
								</button>
							</div>
						</div>

						<div class="unlimitedai-plugin__sidebar-body" id="ubai_sidebar_body">
							<?php if (SheetsPilotGlobals::$isPro) : ?>
							<div class="unlimitedai-plugin__sidebar-content">
								<div class="unlimitedai-plugin__sidebar-tabs-wrapper">
									<div class="unlimitedai-plugin__sidebar-tabs">
										<button class="unlimitedai-plugin__sidebar-tabs-item active">
											<span class="unlimitedai-plugin__btn-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
													<polyline points="4 7 4 4 20 4 20 7"></polyline>
													<line x1="9" x2="15" y1="20" y2="20"></line>
													<line x1="12" x2="12" y1="4" y2="20"></line>
												</svg></span>
											<span class="unlimitedai-plugin__sidebar-tabs-item-text">Text</span>
										</button>
										<button class="unlimitedai-plugin__sidebar-tabs-item">
											<span class="unlimitedai-plugin__btn-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
													<rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
													<circle cx="9" cy="9" r="2"></circle>
													<path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
												</svg></span>
											<span class="unlimitedai-plugin__sidebar-tabs-item-text">Image</span>
										</button>
									</div>
								</div>

								<?php if (SheetsPilotGlobals::$isPro) : ?>
									<div class="unlimitedai-plugin__sidebar-section unlimitedai-plugin__sidebar-section--quick-actions">
										<label class="unlimitedai-plugin__quick-actions-label"><?php esc_html_e('Quick Prompts', 'sheetspilot'); ?></label>
										<div class="unlimitedai-plugin__quick-actions-row">
											<div id="ubai_quick_actions_combo" class="unlimitedai-plugin__quick-actions-combo" role="button" tabindex="0" aria-haspopup="menu" aria-expanded="false" aria-label="<?php esc_attr_e('Select Prompt...', 'sheetspilot'); ?>">
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
												<div id="ubai_quick_actions_trigger" class="unlimitedai-plugin__quick-actions-trigger"><?php esc_html_e('Select Prompt...', 'sheetspilot'); ?></div>
												<span class="unlimitedai-plugin__quick-actions-combo-chevron" aria-hidden="true">
													<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
														<path d="m6 9 6 6 6-6" />
													</svg>
												</span>
											</div>
										</div>
									</div>
								<?php endif; ?>

								<div class="unlimitedai-plugin__sidebar-section">
									<label><?php esc_html_e('Prompt', 'sheetspilot'); ?></label>
									<textarea id="ubai_prompt_input" class="unlimitedai-plugin__sidebar-textarea unlimitedai-plugin__sidebar-textarea--prompt" placeholder="<?php esc_attr_e("Write your own prompt... Type @ to insert references", 'sheetspilot'); ?>"></textarea>
								</div>

								<div class="unlimitedai-plugin__sidebar-section unlimitedai-plugin__sidebar-section--references">
									<label class="unlimitedai-plugin__sidebar-section--references-label"><?php esc_html_e('References', 'sheetspilot'); ?></label>
									<div class="unlimitedai-plugin__sidebar-section__references unlimitedai-plugin__dropdown-parent">
										<button id="ubai_references_btn" class="unlimitedai-plugin__sidebar-section__references-btn unlimitedai-plugin__btn unlimitedai-plugin__dropdown-btn" aria-haspopup="menu" aria-expanded="false" aria-controls="ubai_references_dropdown">
											<span class="unlimitedai-plugin__btn-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
													<path d="M5 12h14"></path>
													<path d="M12 5v14"></path>
												</svg></span>
											<span>Add</span>
										</button>
										<div id="ubai_references_dropdown" class="unlimitedai-plugin__sidebar-section__references-dropdown unlimitedai-plugin__dropdown unlimitedai-plugin__dropdown-container" role="menu" aria-labelledby="ubai_references_btn">
											<ul class="unlimitedaiunlimitedai-plugin__sidebar-section__references-list">
												<li class="unlimitedai-plugin__sidebar-section__references-list__item">
													<span class="unlimitedai-plugin__btn-icon">
														<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
															<path d="M12 21v-6"></path>
															<path d="M12 9V3"></path>
															<path d="M3 15h18"></path>
															<path d="M3 9h18"></path>
															<rect width="18" height="18" x="3" y="3" rx="2"></rect>
														</svg>
													</span>
													<span class="unlimitedai-plugin__sidebar-section__references-list__item-title"><?php esc_html_e('Cell', 'sheetspilot'); ?></span>
												</li>
												<li class="unlimitedai-plugin__sidebar-section__references-list__item">
													<span class="unlimitedai-plugin__btn-icon">
														<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
															<path d="M12 17v5"></path>
															<path d="M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8a2 2 0 0 0 0 4 1 1 0 0 1 1 1z"></path>
														</svg>
													</span>
													<span class="unlimitedai-plugin__sidebar-section__references-list__item-title"><?php esc_html_e('Field', 'sheetspilot'); ?></span>
												</li>
												<li class="unlimitedai-plugin__sidebar-section__references-list__item">
													<span class="unlimitedai-plugin__btn-icon">
														<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
															<rect width="18" height="18" x="3" y="3" rx="2"></rect>
															<path d="M21 9H3"></path>
															<path d="M21 15H3"></path>
														</svg>
													</span>
													<span class="unlimitedai-plugin__sidebar-section__references-list__item-title"><?php esc_html_e('Row', 'sheetspilot'); ?></span>
												</li>
												<li class="unlimitedai-plugin__sidebar-section__references-list__item">
													<span class="unlimitedai-plugin__btn-icon">
														<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
															<path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"></path>
														</svg>
													</span>
													<span class="unlimitedai-plugin__sidebar-section__references-list__item-title"><?php esc_html_e('Column', 'sheetspilot'); ?></span>
												</li>
												<li class="unlimitedai-plugin__sidebar-section__references-list__item">
													<span class="unlimitedai-plugin__btn-icon">
														<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
															<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
															<path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
															<path d="M10 9H8"></path>
															<path d="M16 13H8"></path>
															<path d="M16 17H8"></path>
														</svg>
													</span>
													<span class="unlimitedai-plugin__sidebar-section__references-list__item-title"><?php esc_html_e('Text', 'sheetspilot'); ?></span>
												</li>
												<li class="unlimitedai-plugin__sidebar-section__references-list__item">
													<span class="unlimitedai-plugin__btn-icon">
														<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
															<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
															<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
														</svg>
													</span>
													<span class="unlimitedai-plugin__sidebar-section__references-list__item-title"><?php esc_html_e('Link', 'sheetspilot'); ?></span>
												</li>
											</ul>
										</div>
									</div>
								</div>
								<div class="unlimitedai-plugin__sidebar-section unlimitedai-plugin__sidebar-section--extra_rules">
									<label id="ubai_include_rules_label" class="unlimitedai-plugin__sidebar-section--references-label unlimitedai-plugin_label">
										<input type="checkbox" id="ubai_include_rules" checked="checked" />
										<?php esc_html_e('Include rules', 'sheetspilot'); ?>
									</label>
									<label id="ubai_use_current_cell_data_label" class="unlimitedai-plugin__sidebar-section--references-label unlimitedai-plugin_label">
										<input type="checkbox" id="ubai_use_current_cell_data" checked="checked" />
										<?php esc_html_e('Use current cell data', 'sheetspilot'); ?>
									</label>

								</div>

								<?php if (SheetsPilotGlobals::$isPro == true) {
									SheetsPilot_PromptsUI::render_image_prompt_panel();
								} ?>

								<button class="unlimitedai-plugin__sidebar-apply-btn" disabled>
									<span class=""><svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915" />
											<circle cx="12" cy="12" r="3" />
										</svg></span> <?php esc_html_e('Apply Prompt', 'sheetspilot'); ?>
								</button>
								<div class="prompt_text_response unlimitedai-plugin__panel">
									<div class="prompt_text_response__title"><?php esc_html_e('Prompt Text Response', 'sheetspilot'); ?></div>
									<?php $this->renderPanelControls(
										'prompt_text_response',
										__('Close prompt text response panel', 'sheetspilot'),
										__('Expand prompt text response panel', 'sheetspilot'),
										'#8a8a8a'
									); ?>
									<div class="prompt_text_response__content"><?php esc_html_e('Response: [prompt text response]', 'sheetspilot'); ?></div>
								</div>
								<div id="error_message" class="unlimitedai-general-error-message unlimitedai-plugin__panel" style="display:none">
									<?php $this->renderPanelControls(
										'unlimitedai-general-error-message',
										__('Close error message', 'sheetspilot'),
										__('Expand error message', 'sheetspilot'),
										'#c00'
									); ?>
									<span class="unlimitedai-general-error-message__text"></span>
								</div>
								<div class="unlimitedai-plugin__sidebar-cache-notice">
									<?php
									if (SheetsPilotGlobals::$isPro == true && class_exists('SheetsPilot_UseChatGPT', false)) {
										SheetsPilotHelper::echo_escape_editor_html(SheetsPilot_UseChatGPT::getDebugCacheNoticeHtml());
									}
									?>
								</div>
							</div>

							<?php 
              	if ($enable_debug_tool === '1') : 
              ?>
								<div class="unlimitedai-plugin__sidebar-debug unlimitedai-plugin__panel">
									<div class="unlimitedai-plugin__sidebar-debug__header d-flex aling-items-center justify-content-between">
										<div class="unlimitedai-plugin__sidebar-debug__header-inner d-flex align-items-center">

											<span class="unlimitedai-plugin__sidebar-debug__header-title"><?php esc_html_e('Debug Prompt', 'sheetspilot'); ?></span>
										</div>
										<span class="unlimitedai-plugin__sidebar-debug__header-expand-icon d-flex align-items-center"><svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<path d="m6 9 6 6 6-6"></path>
											</svg></span>
									</div>
									<div class="unlimitedai-plugin__sidebar-debug__wrap">
										<div class="unlimitedai-plugin__sidebar-debug__title"><?php esc_html_e('Apply Prompt Debug', 'sheetspilot'); ?></div>
										<?php $this->renderPanelControls(
											'unlimitedai-plugin__sidebar-debug',
											__('Close debug panel', 'sheetspilot'),
											__('Expand debug panel', 'sheetspilot'),
											'#737373'
										); ?>
										<div class="unlimitedai-plugin__sidebar-debug__block unlimitedai-plugin__sidebar-debug__request">
											<div class="unlimitedai-plugin__sidebar-debug__block-title"><?php esc_html_e('Request', 'sheetspilot'); ?></div>
											<div class="unlimitedai-plugin__sidebar-debug__block-body"></div>
										</div>
										<div class="unlimitedai-plugin__sidebar-debug__block unlimitedai-plugin__sidebar-debug__response">
											<div class="unlimitedai-plugin__sidebar-debug__block-title"><?php esc_html_e('Response', 'sheetspilot'); ?></div>
											<div class="unlimitedai-plugin__sidebar-debug__block-body"></div>
										</div>
									</div>
								</div>
							<?php endif; ?>	

							<div class="unlimitedai-plugin__prompt-history-panel" id="ubai_prompt_history_panel" role="dialog" aria-labelledby="ubai_prompt_history_title" aria-hidden="true">
								<div class="unlimitedai-plugin__prompt-history-header">
									<button type="button" class="unlimitedai-plugin__prompt-history-back unlimitedai-plugin__btn" id="ubai_prompt_history_back" aria-label="<?php echo esc_attr__('Back', 'sheetspilot'); ?>">
										<span class="unlimitedai-plugin__btn-icon"><svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<path d="m12 19-7-7 7-7"></path>
												<path d="M19 12H5"></path>
											</svg></span>
									</button>
									<span class="unlimitedai-plugin__prompt-history-header-icon">
										<svg class="size-16" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<circle cx="12" cy="12" r="10"></circle>
											<polyline points="12 6 12 12 16 14"></polyline>
										</svg>
									</span>
									<h2 class="unlimitedai-plugin__prompt-history-title" id="ubai_prompt_history_title"><?php esc_html_e('Prompt History', 'sheetspilot'); ?></h2>
									<?php
									$ph_items = isset($prompt_history_initial['items']) ? $prompt_history_initial['items'] : array();
									$ph_total_recent = isset($prompt_history_initial['totalRecent']) ? (int) $prompt_history_initial['totalRecent'] : 0;
									$ph_total_saved  = isset($prompt_history_initial['totalSaved']) ? (int) $prompt_history_initial['totalSaved'] : 0;
									?>
									<span class="unlimitedai-plugin__prompt-history-count" id="ubai_prompt_history_count"><?php echo esc_html(count($ph_items)); ?></span>
								</div>
								<div class="unlimitedai-plugin__prompt-history-search-wrap">
									<span class="unlimitedai-plugin__prompt-history-search-icon"><svg class="size-14" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<circle cx="11" cy="11" r="8"></circle>
											<path d="m21 21-4.3-4.3"></path>
										</svg></span>
									<input type="search" class="unlimitedai-plugin__prompt-history-search" id="ubai_prompt_history_search" placeholder="<?php echo esc_attr__('Search prompts...', 'sheetspilot'); ?>" autocomplete="off">
								</div>
								<div class="unlimitedai-plugin__prompt-history-filters">
									<button type="button" class="unlimitedai-plugin__prompt-history-filter active" data-filter="all" id="ubai_prompt_history_filter_all"><span class="unlimitedai-plugin__prompt-history-filter-label"><?php esc_html_e('Recent', 'sheetspilot'); ?> (<span class="unlimitedai-plugin__prompt-history-filter-num" data-num="all"><?php echo esc_html($ph_total_recent); ?></span>)</span></button>
									<button type="button" class="unlimitedai-plugin__prompt-history-filter" data-filter="saved" id="ubai_prompt_history_filter_saved"><span class="unlimitedai-plugin__prompt-history-filter-icon d-flex align-items-center"><svg class="size-12" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
											</svg></span><span class="unlimitedai-plugin__prompt-history-filter-label"><?php esc_html_e('Saved', 'sheetspilot'); ?> (<span class="unlimitedai-plugin__prompt-history-filter-num" data-num="saved"><?php echo esc_html($ph_total_saved); ?></span>)</span></button>
								</div>
								<div class="unlimitedai-plugin__prompt-history-list-wrap" id="ubai_prompt_history_list_wrap">
									<div class="unlimitedai-plugin__prompt-history-loader" id="ubai_prompt_history_loader" aria-hidden="true" role="status" aria-label="<?php echo esc_attr__('Loading…', 'sheetspilot'); ?>">
										<span class="unlimitedai-plugin__prompt-history-loader-spinner"></span>
									</div>
									<div class="unlimitedai-plugin__prompt-history-list" id="ubai_prompt_history_list">
										<?php if (SheetsPilotGlobals::$isPro == true) {
											SheetsPilot_PromptsUI::render_prompt_history_list($ph_items);
										} ?>
									</div>
								</div>
								<div class="unlimitedai-plugin__prompt-history-footer">
									<button type="button" class="unlimitedai-plugin__prompt-history-clear-btn unlimitedai-plugin__btn" id="ubai_prompt_history_clear_all" aria-label="<?php echo esc_attr__('Clear all history', 'sheetspilot'); ?>">
										<span class="unlimitedai-plugin__btn-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4343" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<path d="M3 6h18" />
												<path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
												<path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
												<line x1="10" x2="10" y1="11" y2="17" />
												<line x1="14" x2="14" y1="11" y2="17" />
											</svg></span>
										<span class="unlimitedai-plugin__prompt-history-clear-btn-text"><?php esc_html_e('Clear all history', 'sheetspilot'); ?></span>
									</button>
								</div>
							</div>
							<?php else : ?>
								<?php $this->renderFreeSidebarUpsell(); ?>
							<?php endif; ?>
						</div>
					</div>

				<div class="unlimitedai-plugin__content">

					<div class="unlimitedai-plugin__tools unlimitedai-plugin__tools--row">
						<div class="unlimitedai-plugin__tools--row__item">
							<?php SheetsPilotHelper::echo_escape_editor_html($this->generate_quick_search()); ?>
							<?php SheetsPilotHelper::echo_escape_editor_html($this->generate_bulk_edit_dropdown()); ?>
						</div>
						<?php SheetsPilotHelper::echo_escape_editor_html($this->generate_columns_selector()); ?>
					</div>

					<div class="unlimitedai-plugin__tools unlimitedai-plugin__tools--row unlimitedai-plugin__tools-filters" style="display: none">
						<div class="unlimitedai-plugin__tools--row__item">
							<span class="unlimitedai-plugin__tools-filters__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
								</svg></span>
							<span class="unlimitedai-plugin__tools-filters__post-count">
								<span class="unlimitedai-plugin__tools-filters__post-count__before-text">Showing</span>
								<span class="unlimitedai-plugin__tools-filters__post-count__nums">
									<span class="unlimitedai-plugin__tools-filters__post-count__nums-current">20</span><span class="unlimitedai-plugin__tools-filters__post-count__nums-divider">/</span><span class="unlimitedai-plugin__tools-filters__post-count__nums-total">20</span>
								</span>
								<span class="unlimitedai-plugin__tools-filters__post-count__after-text">posts</span>
							</span>
							<span class="unlimitedai-plugin__buttons-separator"></span>
							<span class="unlimitedai-plugin__tools-filters-container">
								<span class="unlimitedai-plugin__tools-filters__item">
									<span class="unlimitedai-plugin__tools-filters__item-name">Categories</span>
									<span class="unlimitedai-plugin__tools-filters__item-val">13 values (OR)</span>
									<span class="unlimitedai-plugin__tools-filters__item-del-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M18 6 6 18"></path>
											<path d="m6 6 12 12"></path>
										</svg></span>
								</span>
							</span>
						</div>
						<span class="unlimitedai-plugin__tools-filters__clear-all">Clear all</span>
					</div>

					<?php $this->generate_js_table();
					?>

					<div class="unlimitedai-plugin__tools unlimitedai-plugin__tools--row unlimitedai-plugin__tools--footer">
						<div class="unlimitedai-plugin__rows-selector">
							<div class="unlimitedai-plugin__rows-selector__title">Rows per page:</div>
							<div class="unlimitedai-plugin__rows-selector__select">
								<select id="ubai_rows_selector" class="unlimitedai-plugin__rows-selector__select-dropdown" name="rows-selector">
									<option value="2" <?php if ($this->rowsPerPage == 2) {
															echo ' selected ';
														} ?>>2</option>
									<option value="3" <?php if ($this->rowsPerPage == 3) {
															echo ' selected ';
														} ?>>3</option>
									<option value="5" <?php if ($this->rowsPerPage == 5) {
															echo ' selected ';
														} ?>>5</option>
									<option value="10" <?php if ($this->rowsPerPage == 10  || !$this->rowsPerPage) {
															echo ' selected ';
														} ?>>10</option>
									<option value="25" <?php if ($this->rowsPerPage == 25) {
															echo ' selected ';
														} ?>>25</option>
									<option value="50" <?php if ($this->rowsPerPage == 50) {
															echo ' selected ';
														} ?>>50</option>
									<option value="100" <?php if ($this->rowsPerPage == 100) {
															echo ' selected ';
														} ?>>100</option>
								</select>
							</div>
						</div>
						<div class="unlimitedai-plugin__pagination">
							<div class="unlimitedai-plugin__pagination-rows">
								<div class="unlimitedai-plugin__pagination-rows__current">
									<span class="unlimitedai-plugin__pagination-rows__current--first">1</span>-<span class="unlimitedai-plugin__pagination-rows__current--last">10</span>
								</div>
								<div class="unlimitedai-plugin__pagination-rows--text">of</div>
								<div class="unlimitedai-plugin__pagination-rows--total">20</div>
							</div>
							<div class="unlimitedai-plugin__pagination-pages">
								<div class="unlimitedai-plugin__pagination-pages__prev">
									<button class="unlimitedai-plugin__pagination-pages__btn unlimitedai-plugin__pagination-pages__btn--prev" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="m15 18-6-6 6-6"></path>
										</svg></button>
								</div>
								<div class="unlimitedai-plugin__pagination-pages__current">Page <span class="unlimitedai-plugin__pagination-pages__current--first">1</span> of <span class="unlimitedai-plugin__pagination-pages__current--last">2</span></div>
								<div class="unlimitedai-plugin__pagination-pages__next">
									<button class="unlimitedai-plugin__pagination-pages__btn unlimitedai-plugin__pagination-pages__btn--next" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="m9 18 6-6-6-6"></path>
										</svg></button>
								</div>
							</div>
						</div>
					</div>

					<!--
					<div class="button-group-row posteditor_button_group">
						<?php $this->drawSaveSettingsButton(); 
						?>
						<?php $this->drawProcessContentButton(); 
						?>
					</div>
					-->
					<?php
					$allowed_html = array(
						'div' => array(
							'id'    => true,
							'class' => true,
							'style' => true,
						),
					);
					?>

					<div class="errorHandlerHTML">
						<?php echo wp_kses($this->returnErrorHandlerHtml($this->save_posts_prefix), $allowed_html); 
						?>
						<?php echo wp_kses($this->returnErrorHandlerHtml($this->process_content_prefix), $allowed_html); 
						?>
					</div>

				</div>

			</div>

			<div class="unlimitedai-plugin__overlay"></div>
			<div class="unlimitedai-plugin__side-drawer">


				<div class="unlimitedai-plugin__side-drawer__header">
					<div class="unlimitedai-plugin__side-drawer__header-title"></div>
					<button class="unlimitedai-plugin__side-drawer__header-close-btn unlimitedai-plugin__btn"><svg class="unlimitedai-plugin__btn-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M18 6 6 18"></path>
							<path d="m6 6 12 12"></path>
						</svg></button>
				</div>


				<div class="unlimitedai-plugin__side-drawer__body"></div>

				<div class="unlimitedai-plugin__side-drawer__footer"></div>

			</div>

			<div class="unlimitedai-plugin__notification">
				<div class="unlimitedai-plugin__notification-inner">
					<span class="unlimitedai-plugin__notification-inner__icon"></span>
					<p class="unlimitedai-plugin__notification-inner__text"></p>
				</div>
			</div>

			<div id="ubai_ajax_error_dialog" class="unlimitedai-plugin__popup ubai-ajax-error-dialog" role="alertdialog" aria-modal="true" aria-labelledby="ubai_ajax_error_title">
				<div class="unlimitedai-plugin__popup-container">
					<div class="unlimitedai-plugin__popup-header">
						<h2 id="ubai_ajax_error_title" class="unlimitedai-plugin__popup-header__title"><?php esc_html_e( 'Error', 'sheetspilot' ); ?></h2>
						<button type="button" class="unlimitedai-plugin__popup-close ubai-ajax-error-dialog__close" aria-label="<?php echo esc_attr__( 'Close', 'sheetspilot' ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
						</button>
					</div>
					<div class="unlimitedai-plugin__popup-body">
						<div class="ubai-ajax-error-dialog__message-row">
							<div class="unlimitedai-plugin__popup-error-message unlimitedai-general-error-message__text"></div>
							<button type="button" class="ubai-ajax-error-dialog__copy" aria-label="<?php echo esc_attr__( 'Copy error to clipboard', 'sheetspilot' ); ?>" title="<?php echo esc_attr__( 'Copy to clipboard', 'sheetspilot' ); ?>">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path></svg>
							</button>
						</div>
					</div>
					<div class="unlimitedai-plugin__popup__buttons">
						<button type="button" class="unlimitedai-plugin__popup__button cancel ubai-ajax-error-dialog__ok"><?php esc_html_e( 'OK', 'sheetspilot' ); ?></button>
					</div>
				</div>
			</div>

		</div>
		</div>

		</div>

		<div id="sheetspilot_upgrade_to_pro_text">
			<div class=" text-center mb-3">
				<div class="bg-light rounded-circle p-3 d-sm-inline-block">
					<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path>
						<path d="M20 3v4"></path>
						<path d="M22 5h-4"></path>
						<path d="M4 17v2"></path>
						<path d="M5 18H3"></path>
					</svg>
				</div>
			</div>
			<div class="h4 text-center mb-3">Pro Feature</div>
			<div class="text-center text-secondary mb-4">
				<p>This feature is only avaliable in the Pro version.</p>
				<p>Unlock all features and take your content management to next level.</p>
			</div>
			<div class="">
				<a target="_blank" href="<?php echo esc_attr(SheetsPilotGlobals::PRO_VERSION_URL); ?>" class="btn btn-dark w-100">Upgrade to Pro</a>
			</div>
		</div>

	<?php

	}

	/**
	 * Generate HTML for the quick search input
	 */
	public function generate_quick_search()
	{
		$placeholder = esc_attr__("Search items...", 'sheetspilot');

		$out_html = '<div class="unlimitedai-plugin__search-wrapper">';
		$out_html .= '<span class="unlimitedai-plugin__quick_search-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg></span>';
		$out_html .= '<span class="unlimitedai-plugin__quick_search"><input type="search" id="ubai_quick_search" placeholder="Search posts..."></span>';
		$out_html .= '<span class="unlimitedai-plugin__container">';
		$out_html .= '<button class="unlimitedai-plugin__quick_search-locate_btn unlimitedai-plugin__text-btn">' . esc_html(__('Locate column', 'sheetspilot')) . '</button>';
		$out_html .= '<span class="unlimitedai-plugin__quick_search__dropdown unlimitedai-plugin__dropdown">
						<span class="unlimitedai-plugin__quick_search__dropdown-item__search">
							<input id="ubai_quick_search_locate_input" class="unlimitedai-plugin__quick_search__dropdown-item__search-input" type="search" placeholder="Search columns..." />
							<span class="unlimitedai-plugin__quick_search__dropdown-item__search-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg></span>
						</span>
						<span class="unlimitedai-plugin__quick_search__dropdown__list" role="listbox" aria-label="Quick Search List">
							<span class="unlimitedai-plugin__quick_search__dropdown-item unlimitedai-plugin__dropdown-item">
								<span class="unlimitedai-plugin__quick_search__dropdown-item__name">Post Title</span>
								<span class="unlimitedai-plugin__quick_search__dropdown-item__type">Text</span>
							</span>
							<span class="unlimitedai-plugin__quick_search__dropdown-item unlimitedai-plugin__dropdown-item">
								<span class="unlimitedai-plugin__quick_search__dropdown-item__name">Post Title</span>
								<span class="unlimitedai-plugin__quick_search__dropdown-item__type">Text</span>
							</span>
							<span class="unlimitedai-plugin__quick_search__dropdown-item unlimitedai-plugin__dropdown-item">
								<span class="unlimitedai-plugin__quick_search__dropdown-item__name">Post Title</span>
								<span class="unlimitedai-plugin__quick_search__dropdown-item__type">Text</span>
							</span>
							<span class="unlimitedai-plugin__quick_search__dropdown-item unlimitedai-plugin__dropdown-item">
								<span class="unlimitedai-plugin__quick_search__dropdown-item__name">Post Title</span>
								<span class="unlimitedai-plugin__quick_search__dropdown-item__type">Text</span>
							</span>
						</span>
						
					</span>';
		$out_html .= '</span>';
		$out_html .= '<button class="unlimitedai-plugin__quick_search-close-btn unlimitedai-plugin__btn"><span class="unlimitedai-plugin__btn-icon unlimitedai-plugin__quick_search-icon--close"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg></span></button>';
		$out_html .= '<div id="uba_loader_searching" class="" style="display:none;"><div class="unlimitedai-plugin__intro-status__icon unlimitedai-plugin__intro-status__icon--saving"><span class="loader_round"></span></div></div>';
		$out_html .= '</div>';

		return $out_html;
	}

	/**
	 * Generate HTML for the bulk edit
	 */
	public function generate_bulk_edit_dropdown()
	{
		$out_html = '<div class="unlimitedai-plugin__bulk-edit__wrapper">';
		$out_html .= '<span class="unlimitedai-plugin__buttons-separator"></span>';

		$out_html .= '<span class="unlimitedai-plugin__bulk-edit__select_wrapper">';
		$out_html .= '<select class="unlimitedai-plugin__bulk-edit__select">
			</select>';
		$out_html .= '</span>';
		if ( SheetsPilotGlobals::$isPro && SheetsPilotGlobals::$enableAutomateWorkflow ) {
			$out_html .= '<button type="button" class="unlimitedai-plugin__automate-workflow-btn unlimitedai-plugin__text-btn unlimitedai-plugin__text-accent-btn" id="ubai_automate_workflow_btn" title="' . esc_attr__( 'Automate Workflow', 'sheetspilot' ) . '">';
			$out_html .= '<span class="unlimitedai-plugin__automate-workflow-btn__icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg></span>';
			$out_html .= '<span class="unlimitedai-plugin__automate-workflow-btn__text">' . esc_html__( 'Automate Workflow', 'sheetspilot' ) . '</span>';
			$out_html .= '</button>';
		}
		$out_html .= '<span class="unlimitedai-plugin__bulk-edit__counter">';
		$out_html .= '<span class="unlimitedai-plugin__bulk-edit__counter-num"></span>';
		$out_html .= '<span class="unlimitedai-plugin__bulk-edit__counter-text"> ' . __('selected',  'sheetspilot') . '</span>';
		$out_html .= '</span>';
		$out_html .= '</div>';

		return $out_html;
	}

	/**
	 * return post type selector
	 */
	function generate_type_selector()
	{

		$post_types = get_post_types(array('public' => true), 'objects');
		$count_posts = wp_count_posts($this->savedOption);

		// Handle Media (inherit) vs Regular Posts (publish + others)
		if ($this->savedOption == 'attachment') {
			$total_posts = isset($count_posts->inherit) ? $count_posts->inherit : 0;
		} else {
			$total_posts = (isset($count_posts->publish) ? $count_posts->publish : 0) +
				(isset($count_posts->draft) ? $count_posts->draft : 0) +
				(isset($count_posts->private) ? $count_posts->private : 0) +
				(isset($count_posts->pending) ? $count_posts->pending : 0);
		}

		$out_html = '<div class="unlimitedai-plugin__post-type-selector-wrapper" data-title="Select Post Type">';
		$out_html .= '<select id="ubai_post_type_selector" class="unlimitedai-plugin__post-type-selector">';
		foreach ($post_types as $pt) {
			$slug           = sanitize_key($pt->name);
			$url            = esc_url(admin_url('admin.php?page=sheetspilot#' . $slug));
			$selected_attr  = ($this->savedOption === $slug) ? ' selected="selected"' : '';
			$out_html      .= '<option value="' . esc_attr($slug) . '"' . $selected_attr
				. ' data-posttypeurl="' . $url . '"'
				. ' data-posttypeicon="XXXXX"'
				. ' data-url="' . $url . '">'
				. esc_html($pt->label)
				. '</option>';
		}
		$out_html .= '</select>';
		$out_html .= '<span class="unlimitedai-plugin__post-type-selector__count">';
		$out_html .= '<span class="unlimitedai-plugin__post-type-selector__count-number">(' . esc_html((string) $total_posts) . ')</span>';
		$out_html .= '</span>';
		$out_html .= '<span id="post_type_change_loader_save" class="unlimitedai-plugin__post-type-selector__count-loader" style="display:none;"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span></span>';
		$out_html .= '<span id="global_processing_loader_container" style="display:none;">';
		$out_html .= '<span id="global_processing_loader" class="unlimitedai-plugin__post-type-selector__count-loader"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span></span>';
		$out_html .= esc_html__('Saving...', 'sheetspilot');
		$out_html .= '</span>';
		$out_html .= '</div>';

		return $out_html;
	}

	/**
	 * return post type selector
	 */
	function generate_columns_selector()
	{



		// Main wrapper for the tool
		$out_html = '<div class="unlimitedai-plugin__columns-selector-wrapper">';

		// The Trigger Icon Button
		$out_html .= '
			<div class="unlimitedai-plugin__buttons_wrap">';
		if ( SheetsPilotGlobals::$isPro && SheetsPilotGlobals::$showPromptResultsToolbarButton ) {
			$out_html .= '
				<button type="button" id="ubai_pending_prompt_results_trigger" class="unlimitedai-plugin__btn ubai-pending-prompt-results-trigger" aria-label="' . esc_attr__( 'Open pending prompt results', 'sheetspilot' ) . '">
					<span class="unlimitedai-plugin__btn-icon has-tooltip" data-title="' . esc_attr__( 'Open pending prompt results', 'sheetspilot' ) . '">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72"></path>
							<path d="m14 7 3 3"></path><path d="M5 6v4"></path><path d="M19 14v4"></path><path d="M10 2v2"></path>
							<path d="M7 8H3"></path><path d="M21 16h-4"></path><path d="M11 3H9"></path>
						</svg>
						<span class="ubai-pending-prompt-results-trigger__count" aria-hidden="true"></span>
					</span>
				</button>
				<span class="unlimitedai-plugin__buttons-separator"></span>';
		}
		$out_html .= '
				<button type="button" id="ubai_undo_trigger" class=" unlimitedai-plugin__btn"  ><span data-title="' . esc_attr(SheetsPilotGlobals::$undoActionText) . '" class="has-tooltip"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5"></path><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5a5.5 5.5 0 0 1-5.5 5.5H11"></path></svg></span></button>
				<span class="unlimitedai-plugin__buttons-separator"></span>
				<button id="ubai_column_manager_trigger" class="unlimitedai-plugin__columns-selector-btn unlimitedai-plugin__btn" aria-haspopup="dialog" aria-expanded="false"><span class="unlimitedai-plugin__columns-selector-btn__icon unlimitedai-plugin__btn-icon has-tooltip" data-title="' . esc_attr(SheetsPilotGlobals::$editColumnsOrderAndVisisbility) . '" ><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-columns3 w-4 h-4"><rect width="18" height="18" x="3" y="3" rx="2"></rect><path d="M9 3v18"></path><path d="M15 3v18"></path></svg></span></button>
				<button type="button" class="ubai_content_rules_trigger unlimitedai-plugin__btn" aria-label="' . esc_attr__('Settings', 'sheetspilot') . '" title="' . esc_attr__('Settings', 'sheetspilot') . '"><span class="unlimitedai-plugin__btn-icon has-tooltip" data-title="' . esc_attr__('Settings', 'sheetspilot') . '"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7h-9"></path><path d="M14 17H5"></path><circle cx="17" cy="17" r="3"></circle><circle cx="7" cy="7" r="3"></circle></svg></span></button>
				<span class="unlimitedai-plugin__buttons-separator"></span>
				<span class="unlimitedai-plugin__add-row__container unlimitedai-plugin__container">
					<button id="ubai_add_new_row_trigger" class=" unlimitedai-plugin__btn unlimitedai-plugin__dropdown-button"  ><span class="has-tooltip" data-title="' . esc_attr(SheetsPilotGlobals::$addNewRow) . '"  ><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg></span></button>
					<span class="unlimitedai-plugin__add-row__dropdown unlimitedai-plugin__dropdown">

						<span class="unlimitedai-plugin__add-row__dropdown-item unlimitedai-plugin__dropdown-item border-bottom" data-value="1">' . esc_html(__('Add row', 'sheetspilot')) . '</span>

						<span class="unlimitedai-plugin__add-row__dropdown-item unlimitedai-plugin__dropdown-item unlimitedai-plugin__add-row__dropdown-item--custom" data-value="custom" aria-haspopup="dialog" aria-expanded="false">' . esc_html(__('Custom number...', 'sheetspilot')) . '</span>
						
						<span class="unlimitedai-plugin__add-row__dropdown-item unlimitedai-plugin__dropdown-item unlimitedai-plugin__paste_list" data-value="paste_list" aria-haspopup="dialog" aria-expanded="false"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 12H3"></path><path d="M16 6H3"></path><path d="M16 18H3"></path><path d="M18 9v6"></path><path d="M21 12h-6"></path></svg>' . esc_html(__('Paste list...', 'sheetspilot')) . '</span>

						<span class="unlimitedai-plugin__add-row__dropdown-item unlimitedai-plugin__dropdown-item" data-value="5">' . esc_html(__('Add 5 rows', 'sheetspilot')) . '</span>
						<span class="unlimitedai-plugin__add-row__dropdown-item unlimitedai-plugin__dropdown-item" data-value="10">' . esc_html(__('Add 10 rows', 'sheetspilot')) . '</span>
						<span class="unlimitedai-plugin__add-row__dropdown-item unlimitedai-plugin__dropdown-item" data-value="25">' . esc_html(__('Add 25 rows', 'sheetspilot')) . '</span>
						<span class="unlimitedai-plugin__add-row__dropdown-item unlimitedai-plugin__dropdown-item" data-value="50">' . esc_html(__('Add 50 rows', 'sheetspilot')) . '</span>
					</span>';

		$out_html .= '
					<div id="uai-add-new-row-popup" class="unlimitedai-plugin__popup" role="dialog" aria-modal="true" aria-labelledby="ubai_popup_title">
								<div class="unlimitedai-plugin__popup-container">
										<div class="unlimitedai-plugin__popup-header">
												<h2 class="unlimitedai-plugin__popup-header__title">' . esc_html(__('Add Rows', 'sheetspilot')) . '</h2>
												<p class="unlimitedai-plugin__popup-header__subtitle">' . esc_html(__('Number of rows', 'sheetspilot')) . '</p>
												<button class="unlimitedai-plugin__popup-close" aria-label="Close">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
												</button>
										</div>
										<div class="unlimitedai-plugin__popup-body">
										  <div class="unlimitedai-plugin__add-row__number">
										    <input class="unlimitedai-plugin__add-row__number-input" type="number" placeholder="' . esc_attr(__('Enter number...', 'sheetspilot')) . '" min="1">
											</div>
											<div class="unlimitedai-plugin__add-row__buttons">
												<button class="unlimitedai-plugin__add-row__button cancel">Cancel</button>
												<button class="unlimitedai-plugin__add-row__button add">Add Rows</button>
											</div>
										</div>
								</div>
						</div>';
		$out_html .= '
				</span>
			</div>	
		';

		// Hidden Data & Original Select
		$out_html .= '
        <div >
            <input type="hidden" value="' . htmlentities(wp_json_encode($this->postTypeSavedColumns)) . '" id="ubai_selected_columns" /> 
            <input type="hidden" value="' . htmlentities(wp_json_encode($this->postTypeSavedColumnsOrder)) . '" id="ubai_selected_columns_order" /> 
        </div>';

		// The Popup Modal for columns in table
		$out_html .= '
        <div id="ubai_column_popup" class="unlimitedai-plugin__columns-selector__drawer" role="dialog" aria-modal="true" aria-labelledby="ubai_popup_title">
            <div class="unlimitedai-plugin__columns-selector__drawer-content">
                <div class="unlimitedai-plugin__columns-selector__drawer-header">
                    <h2 id="ubai_popup_title" class="unlimitedai-plugin__columns-selector__title">' . esc_html(__('Field Visibility & Order', 'sheetspilot')) . '</h2>
										<p class="unlimitedai-plugin__columns-selector__subtitle">' . esc_html(__('Manage field visibility, ordering, and views', 'sheetspilot')) . '</p>
                    <button class="unlimitedai-plugin__columns-selector__drawer-close" aria-label="Close">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x h-4 w-4"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
										</button>
                </div>
                <div id="ubai_popup_fields_list" class="unlimitedai-plugin__columns-selector__drawer-list"></div>
            </div>
        </div>';

		// The Popup Modal for posts editing
		$out_html .= '
        <div id="ubai_post_editing_popup" class="unlimitedai-plugin__post_editor__popup" role="dialog" aria-modal="true" aria-labelledby="ubai_popup_title_posts">
            <div class="unlimitedai-plugin__post_editor__popup-content">
                <div class="unlimitedai-plugin__post_editor__popup-header">
                    <h2 id="ubai_popup_title_posts" class="unlimitedai-plugin__post_editor__title">' . esc_html(__('Edit Post', 'sheetspilot')) . '</h2>
										<p class="unlimitedai-plugin__post_editor__subtitle"></p>
                    <button class="unlimitedai-plugin__post_editor__popup-close" aria-label="Close">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x h-4 w-4"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
										</button>
                </div>
                <div class="unlimitedai-plugin__post_editor__popup-body"></div>
            </div>
        </div>';

		// The Popup Modal for various context menus content
		$out_html .= '
    <div id="ubai_context_menus_content_popup" class="unlimitedai-plugin__popup" role="dialog" aria-modal="true" aria-labelledby="ubai_popup_title">
					<div class="unlimitedai-plugin__popup-container">
							<div class="unlimitedai-plugin__popup-header">
									<h2 class="unlimitedai-plugin__popup-header__title">Duplicate Row</h2>
									<p class="unlimitedai-plugin__popup-header__subtitle">Number of copies</p>
									<button class="unlimitedai-plugin__popup-close" aria-label="Close">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
									</button>
							</div>
							<div class="unlimitedai-plugin__popup-body">
								<div class="unlimitedai-plugin__popup__number">
									<input class="unlimitedai-plugin__popup-input" type="number" placeholder="Enter number..." min="1">
								</div>
								<div class="unlimitedai-plugin__popup__buttons">
									<button class="unlimitedai-plugin__popup__button reset">' . esc_html(__('Reset', 'sheetspilot')) . '</button>
									<button class="unlimitedai-plugin__popup__button cancel">' . esc_html(__('Cancel', 'sheetspilot')) . '</button>
									<button class="unlimitedai-plugin__popup__button add">' . esc_html(__('Duplicate', 'sheetspilot')) . '</button>
								</div>
							</div>
					</div>
	</div>';

		$out_html .= '</div>';

		return $out_html;
	}

	/**
	 * Return SVG icon markup by name (DRY).
	 *
	 * @param string $name    Icon name: close, regenerate, copy, check, change-length, translate, improve-text, optimize-seo, fix-grammar, restore-previous, chevron-right, column-rules, add-row.
	 * @param array  $options Optional. size (int), stroke (string), class (string).
	 * @return string SVG markup or empty string if unknown.
	 */
	private function get_svg_icon($name, $options = array())
	{
		$size   = isset($options['size']) ? (int) $options['size'] : 24;
		$stroke = isset($options['stroke']) ? $options['stroke'] : '#171717';
		$class  = isset($options['class']) ? trim($options['class']) : '';
		$style  = isset($options['style']) ? trim($options['style']) : '';

		$icons = array(
			'close'            => '<path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>',
			'regenerate'       => '<path d="M21 12a9 9 0 1 1-3-6.7"></path><path d="M21 3v6h-6"></path>',
			'copy'             => '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>',
			'check'            => '<polyline points="20 6 9 17 4 12"></polyline>',
			'change-length'    => '<path d="M15 3h6v6"></path><path d="M9 21H3v-6"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path>',
			'translate'        => '<path d="m5 8 6 6"></path><path d="m4 14 6-6 2-3"></path><path d="M2 5h12"></path><path d="M7 2h1"></path><path d="m22 22-5-10-5 10"></path><path d="M14 18h6"></path>',
			'improve-text'     => '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path><path d="M4 17v2"></path><path d="M5 18H3"></path>',
			'optimize-seo'     => '<circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path>',
			'fix-grammar'      => '<path d="M21.801 10A10 10 0 1 1 17 3.335"></path><path d="m9 11 3 3L22 4"></path>',
			'restore-previous' => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path>',
			'chevron-right'    => '<path d="m9 18 6-6-6-6"></path>',
			'column-rules'     => '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path><path d="M4 17v2"></path><path d="M5 18H3"></path>',
			'add-row'          => '<path d="M3 6h18"></path><path d="M3 12h18"></path><path d="M3 18h18"></path><path d="M12 9v6"></path><path d="M9 12h6"></path>',
			'pencil'    			 => '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path>',
			'heart'            => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>',

			'copy-action'      => '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect><path d="M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"></path><path d="M16 4h2a2 2 0 0 1 2 2v4"></path><path d="M21 14H11"></path><path d="m15 10-4 4 4 4"></path>',
			'paste-action'      => '<path d="M15 2H9a1 1 0 0 0-1 1v2c0 .6.4 1 1 1h6c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1Z"></path><path d="M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2M16 4h2a2 2 0 0 1 2 2v2M11 14h10"></path><path d="m17 10 4 4-4 4"></path>',
			'autofill-from-title'      => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path>',
			'generate-image'    => '<path d="M14.2647 15.9377L12.5473 14.2346C11.758 13.4519 11.3633 13.0605 10.9089 12.9137C10.5092 12.7845 10.079 12.7845 9.67922 12.9137C9.22485 13.0605 8.83017 13.4519 8.04082 14.2346L4.04193 18.2622M14.2647 15.9377L14.606 15.5991C15.412 14.7999 15.8149 14.4003 16.2773 14.2545C16.6839 14.1262 17.1208 14.1312 17.5244 14.2688C17.9832 14.4253 18.3769 14.834 19.1642 15.6515L20 16.5001M14.2647 15.9377L18.22 19.9628M18.22 19.9628C17.8703 20 17.4213 20 16.8 20H7.2C6.07989 20 5.51984 20 5.09202 19.782C4.7157 19.5903 4.40973 19.2843 4.21799 18.908C4.12583 18.7271 4.07264 18.5226 4.04193 18.2622M18.22 19.9628C18.5007 19.9329 18.7175 19.8791 18.908 19.782C19.2843 19.5903 19.5903 19.2843 19.782 18.908C20 18.4802 20 17.9201 20 16.8V13M11 4H7.2C6.07989 4 5.51984 4 5.09202 4.21799C4.7157 4.40973 4.40973 4.71569 4.21799 5.09202C4 5.51984 4 6.0799 4 7.2V16.8C4 17.4466 4 17.9066 4.04193 18.2622M18 9V6M18 6V3M18 6H21M18 6H15"/>',
			'enhance-image'     => '<path fill="currentColor" stroke="none" d="M180.355,213.668l40.079,40.085L42.526,431.661L2.446,391.576L180.355,213.668z M228.877,245.316l-40.079-40.085l68.905-68.911l40.091,40.079L228.877,245.316z"/><polygon fill="currentColor" stroke="none" points="380.066,218.525 391.999,218.519 391.999,181.309 429.215,181.309 429.215,169.376 391.999,169.376 391.999,132.166 380.066,132.166 380.066,169.376 342.862,169.376 342.862,181.309 380.066,181.309"/><polygon fill="currentColor" stroke="none" points="393.282,260.424 393.282,248.49 356.073,248.49 356.073,211.281 344.145,211.281 344.145,248.49 306.93,248.49 306.93,260.424 344.145,260.424 344.145,297.633 356.073,297.633 356.073,260.424"/><polygon fill="currentColor" stroke="none" points="302.956,37.209 265.741,37.209 265.741,0 253.807,0 253.807,37.209 216.603,37.209 216.603,49.143 253.807,49.143 253.807,86.353 265.741,86.353 265.741,49.143 302.956,49.143"/><polygon fill="currentColor" stroke="none" points="223.853,73.148 186.638,73.148 186.638,35.932 174.71,35.932 174.71,73.148 137.495,73.148 137.495,85.076 174.71,85.076 174.71,122.291 186.638,122.291 186.638,85.076 223.853,85.076"/>',
			'change-image-ratio' => '<path d="M4 6V12H6V8L10 8V6H4Z" fill="currentColor" stroke="none"/><path d="M20 18H14V16H18V12H20V18Z" fill="currentColor" stroke="none"/><path fill-rule="evenodd" clip-rule="evenodd" d="M4 2C1.79086 2 0 3.79086 0 6V18C0 20.2091 1.79086 22 4 22H20C22.2091 22 24 20.2091 24 18V6C24 3.79086 22.2091 2 20 2H4ZM20 4H4C2.89543 4 2 4.89543 2 6V18C2 19.1046 2.89543 20 4 20H20C21.1046 20 22 19.1046 22 18V6C22 4.89543 21.1046 4 20 4Z" fill="currentColor" stroke="none"/>',
			'compress-image'    => '<path d="M4 9V4h5"></path><path d="M4 4l5 5"></path><path d="M20 9V4h-5"></path><path d="M20 4l-5 5"></path><path d="M4 15v5h5"></path><path d="M9 20l-5-5"></path><path d="M20 15v5h-5"></path><path d="M15 20l5-5"></path>',
		);

		if (! isset($icons[$name])) {
			return '';
		}

		$view_boxes = array(
			'enhance-image' => '0 0 432 432',
		);
		$view_box = isset($options['viewBox']) ? $options['viewBox'] : (isset($view_boxes[$name]) ? $view_boxes[$name] : '0 0 24 24');

		$class_attr = $class ? ' class="' . esc_attr($class) . '"' : '';
		$style_attr = $style ? ' style="' . esc_attr($style) . '"' : '';
		return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="' . esc_attr($view_box) . '" fill="none" stroke="' . esc_attr($stroke) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"' . $class_attr . $style_attr . '>' . $icons[$name] . '</svg>';
	}

	/**
	 * Return a small flag image for a country code (ISO 3166-1 alpha-2, e.g. gb, es, fr).
	 * Uses local SVG files from assets/images/flags/ (no external requests).
	 *
	 * @param string $code Country code (lowercase, 2 letters).
	 * @param int    $w    Width in pixels (height auto from SVG aspect ratio).
	 * @return string HTML img tag or empty string if code invalid.
	 */
	private function get_flag_icon($code, $w = 24)
	{
		$code = strtolower(preg_replace('/[^a-z]/', '', $code));
		if (strlen($code) !== 2) {
			return '';
		}
		$url = SheetsPilotGlobals::$urlImages . 'flags/' . $code . '.svg';
		return '<img src="' . esc_url($url) . '" width="' . (int) $w . '" alt="" class="unlimitedai-plugin__context-menu__item-flag" loading="lazy" decoding="async" />';
	}

	/**
	 * Output the prompt replace dialog markup.
	 */
	function render_prompt_replace_dialog()
	{
	?>
		<div id="ubai_prompt_replace_dialog" class="unlimitedai-plugin__prompt-replace-dialog" role="dialog" aria-modal="true" aria-labelledby="ubai_prompt_replace_title" style="display:none;">
			<div class="unlimitedai-plugin__prompt-replace-dialog__content">
				<button type="button" class="unlimitedai-plugin__prompt-replace-dialog__close" aria-label="<?php echo esc_attr(__('Close prompt replace dialog', 'sheetspilot')); ?>" title="<?php echo esc_attr(__('Close (reopen from cell icon)', 'sheetspilot')); ?>">
					<?php echo wp_kses( SheetsPilotHelper::sanitize_svg($this->get_svg_icon('close', array('size' => 16, 'stroke' => '#111111'))), array(
								'svg' => array(
									'xmlns'             => true,
									'width'             => true,
									'height'            => true,
									'viewbox'           => true,
									'fill'              => true,
									'stroke'            => true,
									'stroke-width'      => true,
									'stroke-linecap'    => true,
									'stroke-linejoin'   => true,
									'aria-hidden'       => true,
									'class'           => true,
            			'style'           => true,
								),
								'path' => array(
									'd'         => true,
									'fill'      => true,
									'stroke'    => true,
									'fill-rule' => true,
									'clip-rule' => true,
								),
								'circle' => array(
										'cx'   => true,
										'cy'   => true,
										'r'    => true,
										'fill' => true,
								),
								'rect' => array(
										'x'            => true,
										'y'            => true,
										'width'        => true,
										'height'       => true,
										'rx'           => true,
										'ry'           => true,
										'stroke'       => true,
										'stroke-width' => true,
										'fill'         => true,
								),
							) ); 
					?>
				</button>
				<div class="unlimitedai-plugin__prompt-replace-dialog__header">
					<h3 id="ubai_prompt_replace_title" class="unlimitedai-plugin__prompt-replace-dialog__title"><?php echo esc_html(__('Prompt Result', 'sheetspilot')); ?></h3>
				</div>
				<div class="unlimitedai-plugin__prompt-replace-dialog__body">
					<div class="unlimitedai-plugin__prompt-replace-dialog__text"></div>
					<div class="unlimitedai-plugin__prompt-replace-dialog__image-preview" style="display:none;">
						<img class="unlimitedai-plugin__prompt-replace-dialog__preview-img" src="" alt="" />
					</div>
				</div>
				<div class="unlimitedai-plugin__prompt-replace-dialog__footer">
					<div class="unlimitedai-plugin__prompt-replace-dialog__actions unlimitedai-plugin__prompt-replace-dialog__actions--text">
						<button type="button" class="unlimitedai-plugin__prompt-replace-dialog__icon-btn unlimitedai-plugin__prompt-replace-dialog__icon-btn--regenerate" aria-label="<?php echo esc_attr(__('Regenerate response', 'sheetspilot')); ?>" title="<?php echo esc_attr(__('Run the same prompt again', 'sheetspilot')); ?>">
							<?php
							echo wp_kses(SheetsPilotHelper::sanitize_svg($this->get_svg_icon('regenerate', array('size' => 16, 'stroke' => '#111111'))), array(
								'svg' => array(
									'xmlns'             => true,
									'width'             => true,
									'height'            => true,
									'viewbox'           => true,
									'fill'              => true,
									'stroke'            => true,
									'stroke-width'      => true,
									'stroke-linecap'    => true,
									'stroke-linejoin'   => true,
									'aria-hidden'       => true,
									'class'           => true,
            			'style'           => true,
								),
								'path' => array(
									'd'         => true,
									'fill'      => true,
									'stroke'    => true,
									'fill-rule' => true,
									'clip-rule' => true,
								),
								'circle' => array(
										'cx'   => true,
										'cy'   => true,
										'r'    => true,
										'fill' => true,
								),
								'rect' => array(
										'x'            => true,
										'y'            => true,
										'width'        => true,
										'height'       => true,
										'rx'           => true,
										'ry'           => true,
										'stroke'       => true,
										'stroke-width' => true,
										'fill'         => true,
								),
							)); 

							?>
						</button>
						<button type="button" class="unlimitedai-plugin__prompt-replace-dialog__icon-btn unlimitedai-plugin__prompt-replace-dialog__icon-btn--copy" aria-label="<?php echo esc_attr(__('Copy response', 'sheetspilot')); ?>" title="<?php echo esc_attr(__('Copy result to clipboard', 'sheetspilot')); ?>">
							<?php echo


							wp_kses(SheetsPilotHelper::sanitize_svg($this->get_svg_icon('copy', array('size' => 16, 'stroke' => '#111111', 'class' => 'unlimitedai-plugin__prompt-replace-dialog__icon-copy'))),  array(
								'svg' => array(
									'xmlns'             => true,
									'width'             => true,
									'height'            => true,
									'viewbox'           => true,
									'fill'              => true,
									'stroke'            => true,
									'stroke-width'      => true,
									'stroke-linecap'    => true,
									'stroke-linejoin'   => true,
									'aria-hidden'       => true,
									'class'           => true,
            			'style'           => true,
								),
								'path' => array(
									'd'         => true,
									'fill'      => true,
									'stroke'    => true,
									'fill-rule' => true,
									'clip-rule' => true,
								),
								'circle' => array(
										'cx'   => true,
										'cy'   => true,
										'r'    => true,
										'fill' => true,
								),
								'rect' => array(
										'x'            => true,
										'y'            => true,
										'width'        => true,
										'height'       => true,
										'rx'           => true,
										'ry'           => true,
										'stroke'       => true,
										'stroke-width' => true,
										'fill'         => true,
								),
								'polyline' => array(
									'points' => true
								),
							)); 
							?>
							<?php echo wp_kses(SheetsPilotHelper::sanitize_svg($this->get_svg_icon('check', array('size' => 16, 'stroke' => '#111111', 'class' => 'unlimitedai-plugin__prompt-replace-dialog__icon-check', 'style' => 'display:none;'))), array(
								'svg' => array(
									'xmlns'             => true,
									'width'             => true,
									'height'            => true,
									'viewbox'           => true,
									'fill'              => true,
									'stroke'            => true,
									'stroke-width'      => true,
									'stroke-linecap'    => true,
									'stroke-linejoin'   => true,
									'aria-hidden'       => true,
									'class'           => true,
            			'style'           => true,
								),
								'path' => array(
									'd'         => true,
									'fill'      => true,
									'stroke'    => true,
									'fill-rule' => true,
									'clip-rule' => true,
								),
								'circle' => array(
										'cx'   => true,
										'cy'   => true,
										'r'    => true,
										'fill' => true,
								),
								'rect' => array(
										'x'            => true,
										'y'            => true,
										'width'        => true,
										'height'       => true,
										'rx'           => true,
										'ry'           => true,
										'stroke'       => true,
										'stroke-width' => true,
										'fill'         => true,
								),
								'polyline' => array('points' => true),
							)); 
							?>
						</button>
					</div>
					<div class="unlimitedai-plugin__prompt-replace-dialog__buttons unlimitedai-plugin__prompt-replace-dialog__buttons--text">
						<button type="button" class="unlimitedai-plugin__prompt-replace-dialog__btn unlimitedai-plugin__prompt-replace-dialog__btn--insert" title="<?php echo esc_attr(__('Append result below existing cell content', 'sheetspilot')); ?>"><?php echo esc_html(__('Insert Below', 'sheetspilot')); ?></button>
						<button type="button" class="unlimitedai-plugin__prompt-replace-dialog__btn unlimitedai-plugin__prompt-replace-dialog__btn--replace" title="<?php echo esc_attr(__('Replace cell content with this result', 'sheetspilot')); ?>"><?php echo esc_html(__('Replace', 'sheetspilot')); ?></button>
					</div>
					<div class="unlimitedai-plugin__prompt-replace-dialog__buttons unlimitedai-plugin__prompt-replace-dialog__buttons--image" style="display:none;">
						<button type="button" class="unlimitedai-plugin__prompt-replace-dialog__btn unlimitedai-plugin__prompt-replace-dialog__btn--apply-image" title="<?php echo esc_attr(__('Use this image in the cell', 'sheetspilot')); ?>"><?php echo esc_html(__('Apply', 'sheetspilot')); ?></button>
						<button type="button" class="unlimitedai-plugin__prompt-replace-dialog__btn unlimitedai-plugin__prompt-replace-dialog__btn--discard-image" title="<?php echo esc_attr(__('Close without applying (reopen from cell icon)', 'sheetspilot')); ?>"><?php echo esc_html(__('Discard', 'sheetspilot')); ?></button>
						<button type="button" class="unlimitedai-plugin__prompt-replace-dialog__btn unlimitedai-plugin__prompt-replace-dialog__btn--compare-image" title="<?php echo esc_attr(__('Compare with prev image', 'sheetspilot')); ?>"><?php echo esc_html(__('Compare', 'sheetspilot')); ?></button>
					</div>
					<button type="button" class="ubai-prompt-replace-dialog__queue-counter unlimitedai-plugin__prompt-replace-dialog__queue-counter" style="display:none;" aria-live="polite" title="<?php echo esc_attr(__('Next pending prompt result', 'sheetspilot')); ?>"></button>
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Output the Content Rules dialog markup.
	 */
	function render_content_rules_dialog()
	{
		$path = SheetsPilotGlobals::$pathViews . 'content-rules-dialog.php';
		SheetsPilotFunctions::validateFilepath($path, 'content-rules-dialog');
		include $path;
	}

	/**
	 * Text context menu definition: action, text (translation key), icon, optional disabled, optional sub_items.
	 * Prompt text for each action is taken from SheetsPilotGlobals::$contextMenuPrompts.
	 *
	 * @return array
	 */
	private function get_text_context_menu_items()
	{
		if (! SheetsPilotGlobals::$isPro) {
			return array(
				array(
					'action'                 => 'copy-action',
					'id'                     => 'ubai_context_menu_copy_action',
					'text'                   => 'Copy',
					'icon'                   => 'copy-action',
					'visible_for_cell_types' => 'all',
				),
				array(
					'action'                 => 'paste-action',
					'id'                     => 'ubai_context_menu_paste_action',
					'text'                   => 'Paste',
					'icon'                   => 'paste-action',
					'visible_for_cell_types' => 'all',
				),
			);
		}

		$prompts = SheetsPilotGlobals::$contextMenuPrompts;

		// Change length submenu.
		$sub_change_length = array(
			array('action' => 'change-length-shorten', 'text' => 'Make Shorter'),
			array('action' => 'change-length-expand', 'text' => 'Make Longer'),
		);
		foreach ($sub_change_length as $i => $sub) {
			$sub_change_length[$i]['prompt'] = isset($prompts[$sub['action']]) ? $prompts[$sub['action']] : '';
		}

		// Translate submenu.
		$sub_translate = array(
			array('action' => 'translate-en', 'text' => 'English', 'flag' => 'gb'),
			array('action' => 'translate-es', 'text' => 'Spanish', 'flag' => 'es'),
			array('action' => 'translate-fr', 'text' => 'French', 'flag' => 'fr'),
			array('action' => 'translate-de', 'text' => 'German', 'flag' => 'de'),
			array('action' => 'translate-it', 'text' => 'Italian', 'flag' => 'it'),
			array('action' => 'translate-pt', 'text' => 'Portuguese', 'flag' => 'pt'),
			array('action' => 'translate-zh', 'text' => 'Chinese', 'flag' => 'cn'),
			array('action' => 'translate-ja', 'text' => 'Japanese', 'flag' => 'jp'),
			array('action' => 'translate-ko', 'text' => 'Korean', 'flag' => 'kr'),
			array('action' => 'translate-ar', 'text' => 'Arabic', 'flag' => 'sa'),
			array('action' => 'translate-he', 'text' => 'Hebrew', 'flag' => 'il'),
			array('action' => 'translate-ru', 'text' => 'Russian', 'flag' => 'ru'),
		);
		foreach ($sub_translate as $i => $sub) {
			$sub_translate[$i]['prompt'] = isset($prompts[$sub['action']]) ? $prompts[$sub['action']] : '';
		}

		$sub_change_image_ratio = class_exists('SheetsPilot_ImageProcessing')
			? SheetsPilot_ImageProcessing::getChangeImageRatioContextSubItems()
			: array();

		// Saved Prompts submenu (titles only, like prompt history list).
		$sub_saved_prompts = array();
		if (SheetsPilotGlobals::$isPro == true) {
			$saved_items = SheetsPilot_PromptHistory::getSavedForDropdown();
 	
			if (is_array($saved_items)) {
				foreach ($saved_items as $item) {
					$id    = isset($item['id']) ? (string) $item['id'] : '';
					$text  = isset($item['text']) ? (string) $item['text'] : '';
					$label = isset($item['label']) ? (string) $item['label'] : '';
					$prompt_type = isset($item['prompt_type']) ? (string) $item['prompt_type'] : '';
					if ($id === '' || $text === '' || $label === '') {
						continue;
					}
					$sub_saved_prompts[] = array(
						'action' => 'saved-prompt-' . $id,
						'text'   => $label,
						'prompt' => $text,
						'prompt_type' => $prompt_type,
						'visible_for_cell_types' => ( $prompt_type == 'pending_image' ? 'image' : 'text'),
					);
				}
			}
		}

		$items = array(
			array(
				'action'                   => 'apply_column_rules',
				'id'                       => 'ubai_context_menu_apply_column_rules',
				'text'                     => 'Apply Cell Rules',
				'icon'                     => 'column-rules',
				'prompt'                   => 'Apply cell rules',
				'visible_for_cell_types'   => 'all',
			),
			array(
				'action'                 => 'add-repeater-row-1',
				'text'                   => 'Add 1 Row',
				'icon'                   => 'add-row',
				'prompt'                 => 'Add 1 row',
				'visible_for_cell_types' => 'repeater',
			),
			array(
				'action'                 => 'add-repeater-row-2',
				'text'                   => 'Add 2 Rows',
				'icon'                   => 'add-row',
				'prompt'                 => 'Add 2 rows',
				'visible_for_cell_types' => 'repeater',
			),
			array(
				'action'                 => 'add-repeater-row-5',
				'text'                   => 'Add 5 Rows',
				'icon'                   => 'add-row',
				'prompt'                 => 'Add 5 rows',
				'visible_for_cell_types' => 'repeater',
			),
		);

		$items_after_repeater = array(
			array(
				'action'                 => 'generate-image',
				'id'                     => 'ubai_context_menu_generate_image',
				'text'                   => 'Generate Image',
				'icon'                   => 'generate-image',
				'prompt'                 => isset($prompts['generate-image']) ? $prompts['generate-image'] : '',
				'visible_for_cell_types' => 'image',
			),
		);
		if (! empty($sub_change_image_ratio)) {
			$items_after_repeater[] = array(
				'action'                 => 'change-image-ratio',
				'text'                   => __('Change Image Ratio', 'sheetspilot'),
				'icon'                   => 'change-image-ratio',
				'sub_items'              => $sub_change_image_ratio,
				'visible_for_cell_types' => 'image',
			);
		}
		$items_after_repeater[] = array(
			'action'                 => 'enhance-image',
			'id'                     => 'ubai_context_menu_enhance_image',
			'text'                   => __('Auto Enhance', 'sheetspilot'),
			'icon'                   => 'enhance-image',
			'prompt'                 => isset($prompts['enhance-image']) ? $prompts['enhance-image'] : '',
			'visible_for_cell_types' => 'image',
		);
		$items_after_repeater[] = array(
			'action'                 => 'compress-image',
			'id'                     => 'ubai_context_menu_compress_image',
			'text'                   => __('Compress Image', 'sheetspilot'),
			'icon'                   => 'compress-image',
			'visible_for_cell_types' => 'image',
		);

		$items = array_merge(
			$items,
			$items_after_repeater,
			array(
			array(
				'action'                 => 'change-length',
				'text'                   => 'Change Length',
				'icon'                   => 'change-length',
				'sub_items'              => $sub_change_length,
				'visible_for_cell_types' => 'text',
			),
			array(
				'action'                 => 'translate',
				'text'                   => 'Translate',
				'icon'                   => 'translate',
				'sub_items'              => $sub_translate,
				'visible_for_cell_types' => 'text',
			),
			array(
				'action'                 => 'improve-text',
				'text'                   => 'Improve Text',
				'icon'                   => 'improve-text',
				'prompt'                 => isset($prompts['improve-text']) ? $prompts['improve-text'] : '',
				'visible_for_cell_types' => 'text',
			),
			array(
				'action'                 => 'optimize-seo',
				'text'                   => 'Optimize SEO',
				'icon'                   => 'optimize-seo',
				'prompt'                 => isset($prompts['optimize-seo']) ? $prompts['optimize-seo'] : '',
				'visible_for_cell_types' => 'text',
			),
			array(
				'action'                 => 'fix-grammar',
				'text'                   => 'Fix Grammar',
				'icon'                   => 'fix-grammar',
				'prompt'                 => isset($prompts['fix-grammar']) ? $prompts['fix-grammar'] : '',
				'visible_for_cell_types' => 'text',
			),
			)
		);

		// Insert "Saved Prompts" submenu right after "Fix Grammar", only if there are saved prompts.
		if (! empty($sub_saved_prompts)) {
			$items[] = array(
				'action'                 => 'saved-prompts',
				'text'                   => 'Saved Prompts',
				// Use a heart-style icon to visually match saved prompts.
				'icon'                   => 'heart',
				'sub_items'              => $sub_saved_prompts,
				'visible_for_cell_types' => 'all',
			);
		}

		$items = array_merge(
			$items,
			array(
				array('separator' => true),
				array(
					'action'                 => 'copy-action',
					'id'                     => 'ubai_context_menu_copy_action',
					'text'                   => 'Copy',
					'icon'                   => 'copy-action',
					'visible_for_cell_types' => 'all',
				),
				array(
					'action'                 => 'paste-action',
					'id'                     => 'ubai_context_menu_paste_action',
					'text'                   => 'Paste',
					'icon'                   => 'paste-action',
					'visible_for_cell_types' => 'all',
				),
				array(
					'action'                 => 'autofill-from-title',
					'id'                     => 'ubai_context_menu_autofill_from_title',
					'text'                   => 'Autofill From Post Title',
					'icon'                   => 'autofill-from-title',
					'visible_for_cell_types' => 'all',
					'visible_in_columns'    => array('post_name'),
					'has_separator'          => true,
				),
			)
		);

		return $items;
	}

	/**
	 * Render a single context menu item (or separator).
	 *
	 * @param array $item  Item definition (action, text, icon, disabled, sub_items) or separator.
	 * @param bool  $is_sub Whether this is a sub-menu item (no chevron, smaller).
	 */
	private function render_context_menu_item($item, $is_sub = false)
	{
 
		if (! empty($item['separator'])) {
			echo '<div class="unlimitedai-plugin__context-menu__separator"></div>';
			return;
		}

		$action                   = isset($item['action']) ? $item['action'] : '';
		$item_id                  = isset($item['id']) ? $item['id'] : '';
		$text                     = isset($item['text']) ? $item['text'] : '';
		$icon                     = isset($item['icon']) ? $item['icon'] : '';
		$flag                     = isset($item['flag']) ? $item['flag'] : '';
		$disabled                 = ! empty($item['disabled']);
		$prompt                   = isset($item['prompt']) ? $item['prompt'] : '';
		$sub_items                = isset($item['sub_items']) && is_array($item['sub_items']) ? $item['sub_items'] : array();
		$visible_for_cell_types  = isset($item['visible_for_cell_types']) ? $item['visible_for_cell_types'] : 'all';
		$invisible_for_cell_type = isset($item['invisible_for_cell_type']) ? $item['invisible_for_cell_type'] : 'all';
		$visible_in_columns       = isset($item['visible_in_columns']) && is_array($item['visible_in_columns']) ? $item['visible_in_columns'] : array();
		$has_separator            = isset($item['has_separator']) ? $item['has_separator'] : false;

		$icon_content = '';
		if ($flag) {
			$icon_content = $this->get_flag_icon($flag, 20);
		} elseif ($icon) {
			$icon_content = $this->get_svg_icon($icon, array('size' => 18));
		}

		$item_class = 'unlimitedai-plugin__context-menu__item';
		if ($disabled) {
			$item_class .= ' unlimitedai-plugin__context-menu__item--disabled';
		}
		if ($is_sub) {
			$item_class .= ' unlimitedai-plugin__context-menu__item--sub';
		}
	?>
		<div class="<?php echo esc_attr($item_class); ?>"
		<?php if ($item_id) : ?> id="<?php echo esc_attr($item_id); ?>"
			<?php endif; ?> data-action="<?php echo esc_attr($action); ?>"
			 data-prompt="<?php echo esc_attr($prompt); ?>" 
			 data-visible-for="<?php echo esc_attr($visible_for_cell_types); ?>" 
			 data-invisible-for-cell-type="<?php echo esc_attr($invisible_for_cell_type); ?>" 
			 data-visible-in-columns="<?php echo esc_attr(implode(',', $visible_in_columns)); ?>">
			<span class="unlimitedai-plugin__context-menu__item-icon"><?php 
			echo wp_kses( SheetsPilotHelper::sanitize_svg($icon_content), array(
								'svg' => array(
									'xmlns'             => true,
									'width'             => true,
									'height'            => true,
									'viewbox'           => true,
									'fill'              => true,
									'stroke'            => true,
									'stroke-width'      => true,
									'stroke-linecap'    => true,
									'stroke-linejoin'   => true,
									'aria-hidden'       => true,
								),
								'path' => array(
									'd'         => true,
									'fill'      => true,
									'stroke'    => true,
									'fill-rule' => true,
									'clip-rule' => true,
								),
								'polygon' => array(
									'points' => true,
									'fill'   => true,
									'stroke' => true,
								),
								'circle' => array(
										'cx'   => true,
										'cy'   => true,
										'r'    => true,
										'fill' => true,
								),
							) ); 
																		?></span>
			<span class="unlimitedai-plugin__context-menu__item-text "><?php echo esc_html($text); ?></span>
			<?php if (! $disabled && ! empty($sub_items)) : ?>
				<span class="unlimitedai-plugin__context-menu__item-chevron"><?php echo wp_kses( SheetsPilotHelper::sanitize_svg($this->get_svg_icon('chevron-right', array('size' => 16))), array(
								'svg' => array(
									'xmlns'             => true,
									'width'             => true,
									'height'            => true,
									'viewbox'           => true,
									'fill'              => true,
									'stroke'            => true,
									'stroke-width'      => true,
									'stroke-linecap'    => true,
									'stroke-linejoin'   => true,
									'aria-hidden'       => true,
								),
								'path' => array(
									'd'         => true,
									'fill'      => true,
									'stroke'    => true,
									'fill-rule' => true,
									'clip-rule' => true,
								),
								'circle' => array(
										'cx'   => true,
										'cy'   => true,
										'r'    => true,
										'fill' => true,
								),
								'rect' => array(
										'x'            => true,
										'y'            => true,
										'width'        => true,
										'height'       => true,
										'rx'           => true,
										'ry'           => true,
										'stroke'       => true,
										'stroke-width' => true,
										'fill'         => true,
								),
							) ); 
																				?></span>
				<div class="unlimitedai-plugin__context-menu__sub" role="menu" aria-hidden="true">
					<?php
					foreach ($sub_items as $sub_item) {
						$this->render_context_menu_item($sub_item, true);
					}
					?>
				</div>
			<?php endif; ?>
		</div>

	<?php
	}

	/**
	 * Output the text context menu template (cloned and positioned via JS).
	 */
	function render_text_context_menu_template()
	{
		$items = $this->get_text_context_menu_items();

	?>
		<div id="ubai_text_context_menu" class="unlimitedai-plugin__context-menu-wrapper" style="display:none;">
			<div id="ubai_text_context_menu_template" class="unlimitedai-plugin__context-menu unlimitedai-plugin__context-menu--text">
				<?php
				foreach ($items as $item) {
					$this->render_context_menu_item($item, false);
				}
				?>
			</div>
		</div>
	<?php
	}

	function generate_js_table()
	{

		$nonce = SheetsPilotHelper::getNonce();
		$latest_prompts_demo    = $this->get_demo_latest_prompts();
		$latest_prompts_text_map = array();
		foreach ($latest_prompts_demo as $item) {
			$latest_prompts_text_map[$item['id']] = isset($item['text']) ? $item['text'] : '';
		}
		$saved_prompts_dropdown = array();
		if (SheetsPilotGlobals::$isPro == true) {
			$saved_prompts_dropdown = SheetsPilot_PromptHistory::getSavedForDropdown();
		}
		$saved_prompts_text_map  = array();
		foreach ($saved_prompts_dropdown as $item) {
			$saved_prompts_text_map[$item['id']] = isset($item['text']) ? $item['text'] : '';
		}

	?>

		<div id="spreadsheet_temporary" class="unlimitedai-plugin__table">
			<table id="new_output_table">
				<thead>
				</thead>
				<tbody>
				</tbody>
			</table>

			<?php $this->render_text_context_menu_template(); ?>
			<?php $this->render_prompt_replace_dialog(); ?>
		</div>

		<?php $this->render_content_rules_dialog(); ?>
		<?php if (SheetsPilotGlobals::$isPro == true) {
			SheetsPilot_PromptsUI::render_cell_rules_dialog();
		} ?>
		<?php if (SheetsPilotGlobals::$isPro == true) {
			SheetsPilot_PromptsUI::render_save_prompt_dialog();
		} ?>



	<?php
	}


	function callJSOutput()
	{


		$nonce = SheetsPilotHelper::getNonce();
		$latest_prompts_demo    = $this->get_demo_latest_prompts();
		$latest_prompts_text_map = array();
		foreach ($latest_prompts_demo as $item) {
			$latest_prompts_text_map[$item['id']] = isset($item['text']) ? $item['text'] : '';
		}
		$saved_prompts_dropdown = array();
		if (SheetsPilotGlobals::$isPro == true) {
			$saved_prompts_dropdown = SheetsPilot_PromptHistory::getSavedForDropdown();
		}
		$saved_prompts_text_map  = array();
		foreach ($saved_prompts_dropdown as $item) {
			$saved_prompts_text_map[$item['id']] = isset($item['text']) ? $item['text'] : '';
		}

		$prompts_strings = array();
		$prompts_icons = array();
		$cell_rules = array();
		if (SheetsPilotGlobals::$isPro == true) {
			$prompts_strings = SheetsPilot_PromptsUI::get_prompts_strings();
			$prompts_icons = SheetsPilot_PromptsUI::get_prompts_icons();
			$cell_rules = SheetsPilot_PromptsUI::get_cell_rules($this->savedOption);
		}
	?>
		<input type="hidden" id="g_doublyNonce" value="<?php echo esc_attr($nonce); ?>" />

		<input type="hidden" id="g_latestPromptsText" value="<?php echo esc_attr(wp_json_encode($latest_prompts_text_map)); ?> " />
		<input type="hidden" id="g_savedPromptsText" value="<?php echo esc_attr(wp_json_encode($saved_prompts_text_map)); ?>" />
		<input type="hidden" id="g_ubaiPromptsStrings" value="<?php echo esc_attr(wp_json_encode($prompts_strings)); ?>" />
		<input type="hidden" id="g_ubaiPromptsIcons" value="<?php echo esc_attr(wp_json_encode($prompts_icons)); ?>" />
		<input type="hidden" id="g_ubaiCellRules" value="<?php echo esc_attr(wp_json_encode($cell_rules)); ?>" />



<?php
	}
}


new SheetsPilot_PluginViewWelcome();
