<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if(!defined("SHEETSPILOT_INC")) die("restricted access");

use Orhanerday\OpenAi\OpenAi;

class SheetsPilot_UseChatGPT{
	
	var $api_key;
	var $model;
	var $debug_request = false;
	var $debug_response = false;
	var $send_image_edit_mask = false;

	static $last_request = null;
	static $last_response = null;

	const IMAGE_RESPONSE_CACHE_TRANSIENT = 'ubai_image_response_cache';

	/**
	 * HTML for debug panel: red notice when image response caching is enabled.
	 *
	 * @return string Empty string or HTML (safe to output).
	 */
	public static function getDebugCacheNoticeHtml() {
		if ( ! SheetsPilotGlobals::$enable_cache_image_response ) {
			return '';
		}
		$msg = __( 'Image response caching is ON', 'sheetspilot' ) . ' (SheetsPilotGlobals::$enable_cache_image_response)';
		return '<p class="ubai-debug-cache-notice" style="color:#c00;margin:0 0 8px 0;font-weight:600;">' . esc_html( $msg ) . '</p>';
	}

	public function __construct(  ){
		$settings = SheetsPilotHelper::getGeneralSettings();
		$default_model = SheetsPilotGlobals::CHATGPT_MODEL;
		if ( class_exists( 'SheetsPilot_PluginGeneralSettings' ) ) {
			$default_model = SheetsPilot_PluginGeneralSettings::getDefaultOpenAIModel();
		}
		$this->api_key = $settings["openai_key"];
		$this->model = isset( $settings["openai_model"] ) && $settings["openai_model"] !== ''
			? $settings["openai_model"]
			: $default_model;

		if(SheetsPilotGlobals::$debug_prompt_request == true){
			$this->debug_request = true;
		}
		
		if(SheetsPilotGlobals::$debug_prompt_response == true){
			$this->debug_response = true;
		}


	}
	
	/**
	 * get data array from request
	 */
	public function makeChatGTPCall( $data, $prompt ){

		$this->validateApiKey($this->api_key);
		
		$open_ai = new OpenAi($this->api_key);

		$system_content = SheetsPilotGlobals::APPLY_PROMPT_ASSISTANT_MESSAGE;
		$content_rules  = '';
		if ( SheetsPilotGlobals::$isPro == true ) {
			$content_rules = SheetsPilot_Prompts::getContentRulesBlock();
		}
		if ( $content_rules !== '' ) {
			$system_content .= "\n\n" . $content_rules;
		}

		$column_name = is_array( $data ) ? (string) SheetsPilotFunctions::getVal( $data, 'column', '' ) : '';
		if ( $column_name === 'post_content' && class_exists( 'SheetsPilot_Prompts' ) ) {
			$system_content .= "\n\n" . SheetsPilot_Prompts::getPostContentAssistantRules();
		}
		
		$arrRequest = [
			'model' => $this->model,
			'messages' => [
				[
					"role" => "system",
					"content" => $system_content
				],
				[
					"role" => "user",
					"content" => $prompt
				],
			],
			// Apply-prompt always expects a JSON object payload.
			'response_format' => array(
				'type' => 'json_object',
			),
		];
		
		self::$last_request = $arrRequest;
		
		if($this->debug_request){
			dmp("debug gpt request");
			SheetsPilotFunctions::dmpArrayFull(array(
				"api_key" => $this->api_key,
				"request" => $arrRequest
			));
			if($this->debug_response == false)
				exit();
		}

		// SheetsPilotFunctions::writeDebugFile( dirname( __FILE__ ) . '/$arrRequest.txt', array( $arrRequest ) );

		$complete = $open_ai->chat($arrRequest);

		// SheetsPilotFunctions::writeDebugFile( dirname( __FILE__ ) . '/$complete.txt', array( $complete ) );
 
		self::$last_response = $complete;
		
		if($this->debug_response){
			dmp("debug gpt response");
			SheetsPilotFunctions::dmpArrayFull(array(
				"api_key" => $this->api_key,
				"request" => $arrRequest,
				"response" => $complete
			));
			exit();
		}

		$decoded_response = json_decode( $complete );

		return $this->processGptResponse( $decoded_response );
	}

	/**
	 * Run a single chat completion for the admin Prompt Tester (raw request/response, no post-editor JSON contract).
	 *
	 * @param string $user_message   User message content.
	 * @param string $system_message Optional system message (empty = user-only turn).
	 * @param string $model_override Optional model id; empty uses plugin default from settings.
	 * @param string $tool_type      Optional tool type: web_search|file_search|code_interpreter|computer_use.
	 * @return string Raw JSON body from the OpenAI API.
	 */
	public function makePromptTesterChatCall( $user_message, $system_message = '', $model_override = '', $tool_type = '' ) {
		$this->validateApiKey( $this->api_key );

		$model   = is_string( $model_override ) && trim( $model_override ) !== ''
			? trim( $model_override )
			: $this->model;

		$messages = array();
		if ( trim( (string) $system_message ) !== '' ) {
			$messages[] = array(
				'role'    => 'system',
				'content' => $system_message,
			);
		}
		$messages[] = array(
			'role'    => 'user',
			'content' => $user_message,
		);

		$arr_request = array(
			'model'    => $model,
			'messages' => $messages,
		);
		$tool_type = is_string( $tool_type ) ? trim( $tool_type ) : '';
		$tool_map = array(
			'web_search'       => 'web_search_preview',
			'file_search'      => 'file_search',
			'code_interpreter' => 'code_interpreter',
			'computer_use'     => 'computer_use_preview',
		);

		if ( $tool_type !== '' && isset( $tool_map[ $tool_type ] ) ) {
			$tool_payload = array(
				'type' => $tool_map[ $tool_type ],
			);
			// Responses API requires a container definition for code_interpreter.
			if ( $tool_type === 'code_interpreter' ) {
				$tool_payload['container'] = array(
					'type' => 'auto',
				);
			}

			$arr_request = array(
				'model' => $model,
				'input' => $messages,
				'tools' => array(
					$tool_payload,
				),
			);

			self::$last_request  = $arr_request;
			$complete            = $this->sendResponsesApiRequest( $arr_request );
			self::$last_response = $complete;
		} else {
			$open_ai = new OpenAi( $this->api_key );
			self::$last_request  = $arr_request;
			$complete            = $open_ai->chat( $arr_request );
			self::$last_response = $complete;
		}

		if ( $complete === false || $complete === '' ) {
			SheetsPilotFunctions::throwError( __( 'Empty response from OpenAI.', 'sheetspilot' ) );
		}

		$decoded = json_decode( (string) $complete, true );
		if ( is_array( $decoded ) && isset( $decoded['error']['message'] ) ) {
			SheetsPilotFunctions::throwError( (string) $decoded['error']['message'] );
		}

		return (string) $complete;
	}

	/**
	 * Send raw request to OpenAI Responses API (used by Prompt Tester tools mode).
	 *
	 * @param array $payload Responses API payload.
	 * @return string Raw response body.
	 */
	private function sendResponsesApiRequest( $payload ) {
		$url = 'https://api.openai.com/v1/responses';

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 180,
			'body'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE ),
		);

		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			SheetsPilotFunctions::throwError( (string) $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! is_string( $body ) || trim( $body ) === '' ) {
			SheetsPilotFunctions::throwError( __( 'Empty response from OpenAI.', 'sheetspilot' ) );
		}

		return (string) $body;
	}

	/**
	 * Map source image URL / file to OpenAI Images API output_format (edit: match original type).
	 *
	 * @param string $image_url  Original image URL.
	 * @param string $file_path  Optional temp file path (for mime detection).
	 * @param string $mime       Optional mime type (e.g. image/webp).
	 * @return string png|jpeg|webp
	 */
	private static function resolveOutputFormatFromSourceImage( $image_url, $file_path = '', $mime = '' ) {
		$allowed = array( 'png', 'jpeg', 'webp' );

		$path_part = parse_url( (string) $image_url, PHP_URL_PATH );
		$ext       = strtolower( pathinfo( (string) $path_part, PATHINFO_EXTENSION ) );
		if ( $ext === 'jpg' ) {
			$ext = 'jpeg';
		}
		if ( in_array( $ext, $allowed, true ) ) {
			return $ext;
		}

		if ( ( ! is_string( $mime ) || $mime === '' ) && $file_path !== '' && is_file( $file_path ) ) {
			$mime = @mime_content_type( $file_path );
		}

		if ( is_string( $mime ) && $mime !== '' ) {
			$mime = strtolower( $mime );
			if ( strpos( $mime, 'jpeg' ) !== false || $mime === 'image/jpg' ) {
				return 'jpeg';
			}
			if ( strpos( $mime, 'webp' ) !== false ) {
				return 'webp';
			}
			if ( strpos( $mime, 'png' ) !== false ) {
				return 'png';
			}
		}

		return SheetsPilotGlobals::DEFAULT_IMAGE_FORMAT;
	}

	/**
	 * Resolve size / quality / format for Images API calls (saved settings + per-request overrides).
	 *
	 * @param string|null $size                     Explicit size from editor.
	 * @param string|null $quality_override         Explicit quality from editor.
	 * @param string|null $output_format_override   Explicit format from editor.
	 * @return array{size:string,quality:string,format:string}
	 */
	private static function resolveImageApiDefaults( $size, $quality_override, $output_format_override ) {
		$settings = SheetsPilotHelper::getGeneralSettings();

		if ( $size !== null && $size !== '' ) {
			$image_size = (string) $size;
		} elseif ( class_exists( 'SheetsPilot_PluginGeneralSettings' ) ) {
			$image_size = SheetsPilot_PluginGeneralSettings::getResolvedImageSize( $settings );
		} else {
			$image_size = SheetsPilotGlobals::OPENAI_IMAGE_SIZE;
		}

		if ( class_exists( 'SheetsPilot_PluginGeneralSettings' ) ) {
			$image_quality = SheetsPilot_PluginGeneralSettings::getResolvedImageQuality( $settings );
			$output_format = SheetsPilot_PluginGeneralSettings::getResolvedImageFormat( $settings );
		} else {
			$image_quality = SheetsPilotGlobals::DEFAULT_IMAGE_QUALITY;
			$output_format = SheetsPilotGlobals::DEFAULT_IMAGE_FORMAT;
		}

		if ( class_exists( 'SheetsPilot_PluginGeneralSettings' ) ) {
			if ( $quality_override !== null && $quality_override !== '' ) {
				$image_quality = SheetsPilot_PluginGeneralSettings::resolveSidebarImageQuality( $quality_override, $settings );
			}
			if ( $output_format_override !== null && $output_format_override !== '' ) {
				$output_format = SheetsPilot_PluginGeneralSettings::resolveSidebarImageFormat( $output_format_override, $settings );
			}
		} else {
			if ( $quality_override !== null && $quality_override !== '' ) {
				$q = strtolower( trim( (string) $quality_override ) );
				if ( $q !== 'default' && in_array( $q, array( 'low', 'medium', 'high', 'auto' ), true ) ) {
					$image_quality = $q;
				}
			}

			if ( $output_format_override !== null && $output_format_override !== '' ) {
				$of = strtolower( trim( (string) $output_format_override ) );
				if ( $of === 'jpg' ) {
					$of = 'jpeg';
				}
				if ( $of !== 'default' && in_array( $of, array( 'png', 'jpeg', 'webp' ), true ) ) {
					$output_format = $of;
				}
			}
		}

		return array(
			'size'    => $image_size,
			'quality' => $image_quality,
			'format'  => $output_format,
		);
	}

	/**
	 * Extract image URL or data URL from a completed OpenAI Responses API payload.
	 *
	 * @param array|null $response  API response array.
	 * @param string     $data_mime MIME type for base64 payloads (e.g. image/png).
	 * @return string|null
	 */
	private static function extractImageResultFromResponseOutput( $response, $data_mime ) {
		if ( ! is_array( $response ) ) {
			return null;
		}

		$output = isset( $response['output'] ) && is_array( $response['output'] ) ? $response['output'] : array();
		foreach ( $output as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( ! isset( $item['type'] ) || $item['type'] !== 'image_generation_call' ) {
				continue;
			}
			if ( empty( $item['result'] ) || ! is_string( $item['result'] ) ) {
				continue;
			}
			if ( preg_match( '#^https?://#i', $item['result'] ) ) {
				return $item['result'];
			}

			return 'data:' . $data_mime . ';base64,' . $item['result'];
		}

		return null;
	}

	/**
	 * Extract assistant text returned alongside/after a failed image-generation call.
	 *
	 * @param array|null $response Responses API payload.
	 * @return string
	 */
	private static function extractOutputTextFromResponseOutput( $response ) {
		if ( ! is_array( $response ) ) {
			return '';
		}

		$output = isset( $response['output'] ) && is_array( $response['output'] ) ? $response['output'] : array();
		foreach ( $output as $item ) {
			if ( ! is_array( $item ) || ( $item['type'] ?? '' ) !== 'message' ) {
				continue;
			}
			$content = isset( $item['content'] ) && is_array( $item['content'] ) ? $item['content'] : array();
			$parts   = array();
			foreach ( $content as $block ) {
				if ( ! is_array( $block ) || ( $block['type'] ?? '' ) !== 'output_text' ) {
					continue;
				}
				$text = isset( $block['text'] ) ? trim( (string) $block['text'] ) : '';
				if ( $text !== '' ) {
					$parts[] = $text;
				}
			}
			if ( ! empty( $parts ) ) {
				return trim( implode( "\n\n", $parts ) );
			}
		}

		return '';
	}

	/**
	 * Public helper for failed image-generation responses stored in debug logs.
	 *
	 * @param mixed $response Raw stored response.
	 * @return string
	 */
	public static function getImageGenerationFailureText( $response ) {
		if ( is_string( $response ) ) {
			$decoded = json_decode( $response, true );
			if ( is_array( $decoded ) ) {
				$response = $decoded;
			}
		}
		if ( ! is_array( $response ) ) {
			return '';
		}
		if ( isset( $response['poll_response'] ) && is_array( $response['poll_response'] ) ) {
			$text = self::extractOutputTextFromResponseOutput( $response['poll_response'] );
			if ( $text !== '' ) {
				return $text;
			}
		}
		if ( isset( $response['create_response'] ) && is_array( $response['create_response'] ) ) {
			$text = self::extractOutputTextFromResponseOutput( $response['create_response'] );
			if ( $text !== '' ) {
				return $text;
			}
		}
		return self::extractOutputTextFromResponseOutput( $response );
	}

	/**
	 * Resolve in-flight OpenAI image job id from transient or per-cell queue option.
	 *
	 * @param string $image_query_key Transient key for this cell + prompt.
	 * @param array  $table_data      Editor table snapshot.
	 * @return string
	 */
	private static function resolveImageGenerationJobId( $image_query_key, $table_data ) {
		$job_id = get_transient( $image_query_key );
		if ( is_string( $job_id ) && $job_id !== '' ) {
			return $job_id;
		}

		if ( ! isset( $table_data['postId'] ) || ! isset( $table_data['columnIndex'] ) ) {
			return '';
		}

		$queue = self::imageQueueGetRequests();
		$cell_key = 'cell_' . $table_data['postId'] . '_' . $table_data['columnIndex'];
		if ( isset( $queue[ $cell_key ] ) && is_string( $queue[ $cell_key ] ) && $queue[ $cell_key ] !== '' ) {
			return $queue[ $cell_key ];
		}

		return '';
	}

	/**
	 * Persist job id for polling and return client status token.
	 *
	 * @param string $job_id            OpenAI response id.
	 * @param string $image_query_key   Transient key.
	 * @param array  $table_data        Editor table snapshot.
	 * @param string $status            queued|in_progress.
	 * @return string
	 */
	private static function storeImageGenerationJob( $job_id, $image_query_key, $table_data, $status ) {
		if ( isset( $table_data['postId'] ) && isset( $table_data['columnIndex'] ) ) {
			self::imageQueueAddRequest( $table_data['postId'], $table_data['columnIndex'], $job_id );
		}
		set_transient( $image_query_key, $job_id, 60 * 5 );

		return ( $status === 'queued' ) ? 'queued' : 'in_progress';
	}

	/**
	 * Clear tracking state and return a finished image payload to the caller.
	 *
	 * @param string $image_result      Image URL or data URL.
	 * @param string $image_query_key   Transient key.
	 * @param array  $table_data        Editor table snapshot.
	 * @return string
	 */
	private static function finalizeImageGenerationResult( $image_result, $image_query_key, $table_data ) {
		if ( SheetsPilotGlobals::$enable_cache_image_response ) {
			set_transient( self::IMAGE_RESPONSE_CACHE_TRANSIENT, $image_result, HOUR_IN_SECONDS );
		}
		delete_transient( $image_query_key );
		if ( isset( $table_data['postId'] ) && isset( $table_data['columnIndex'] ) ) {
			self::imageQueueRemoveRequest( $table_data['postId'], $table_data['columnIndex'] );
		}

		return $image_result;
	}

	/**
	 * Persist raw image-generation API responses for debug/log views.
	 *
	 * @param array|null  $create_response Initial Responses API create payload/response.
	 * @param array|null  $poll_response   Optional poll/check response.
	 * @param string|null $job_id          Optional OpenAI response id.
	 * @return void
	 */
	private static function setImageGenerationLastResponse( $create_response = null, $poll_response = null, $job_id = null ) {
		$payload = array();
		if ( $job_id !== null && $job_id !== '' ) {
			$payload['job_id'] = (string) $job_id;
		}
		if ( is_array( $create_response ) && ! empty( $create_response ) ) {
			$payload['create_response'] = $create_response;
		}
		if ( is_array( $poll_response ) && ! empty( $poll_response ) ) {
			$payload['poll_response'] = $poll_response;
		}

		if ( empty( $payload ) ) {
			self::$last_response = null;
			return;
		}

		self::$last_response = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Generate an image via OpenAI Images API (DALL·E). Returns image URL or throws on error.
	 *
	 * @param string      $prompt            Text prompt for image generation.
	 * @param string|null $size              Optional. When provided, overrides SheetsPilotGlobals::OPENAI_IMAGE_SIZE.
	 * @param string|null $quality_override Optional. When set to low|medium|high|auto, used instead of DEFAULT_IMAGE_QUALITY.
	 * @param string|null $output_format_override Optional. When set to png|jpeg|webp, used instead of DEFAULT_IMAGE_FORMAT.
	 * @return string Image URL or data URL.
	 */
	public function makeImageGenerationCall( $prompt, $size = null, $quality_override = null, $output_format_override = null, $table_data = [] ) {
		
		$this->validateApiKey( $this->api_key );

		$settings = SheetsPilotHelper::getGeneralSettings();

		$default_model = class_exists( 'SheetsPilot_PluginGeneralSettings' )
			? SheetsPilot_PluginGeneralSettings::getDefaultOpenAIModel()
			: SheetsPilotGlobals::CHATGPT_MODEL;



		$selected_model = isset( $settings['openai_model'] ) && $settings['openai_model'] !== ''
			? sanitize_text_field( $settings['openai_model'] )
			: $default_model;

		 
		$image_defaults = self::resolveImageApiDefaults( $size, $quality_override, $output_format_override );
		$image_model    = SheetsPilotGlobals::OPENAI_RESPONSE_IMAGE_MODEL;
		$image_size     = $image_defaults['size'];
		$image_quality  = $image_defaults['quality'];
		$output_format  = $image_defaults['format'];
		$data_mime      = 'image/' . ( $output_format === 'jpeg' ? 'jpeg' : (string) $output_format );

		$opts = array(
			'model'          => $image_model,
			'prompt'         => $prompt,
			'n'              => 1,
			'quality'        => $image_quality,
			'output_format'  => $output_format,
		);
		$responseOpts = [
			'model' => $selected_model,
			'background' => true,
			'input' => $prompt,
			'tools' => [
				[
					'type' => 'image_generation',
					"quality" => $image_quality,
            		"output_format" => $output_format
				]
			]
		];
		// When UI selects "auto", don't pass a `size` field at all.
		if ( (string) $image_size !== 'auto' ) {
			$responseOpts['tools'][0]['size'] = $image_size;
		}

		if ( SheetsPilotGlobals::$enable_cache_image_response ) {
			$cache_key = self::IMAGE_RESPONSE_CACHE_TRANSIENT;
			$cached = get_transient( $cache_key );
			if ( is_string( $cached ) && $cached !== '' ) {
				return $cached;
			}
		}

		self::$last_request = array( 'image_generation' => $responseOpts );

		if ( $this->debug_request ) {
			dmp( 'debug image generation request' );
			dmp( array(
				'api_key' => $this->api_key,
				'request' => $responseOpts,
			) );
			if ( $this->debug_response === false ) {
				exit();
			}
		}

		// Track the in-flight OpenAI image job per CELL, not just per prompt.
		$image_query_cell_key = '';
		if ( isset( $table_data['postId'] ) && isset( $table_data['columnIndex'] ) ) {
			$image_query_cell_key = $table_data['postId'] . '_' . $table_data['columnIndex'] . '_';
		}
		$image_query_key = 'image_gen_query_' . $image_query_cell_key . md5( $responseOpts['input'] );

		$imageGenObject = new SheetsPilotOpenAIResponseAPI( $this->api_key );
		$job_id         = self::resolveImageGenerationJobId( $image_query_key, $table_data );

		if ( $job_id !== '' ) {
			$currentImageCreationStatus = $imageGenObject->checkImageCreationQuery( $job_id );
			self::setImageGenerationLastResponse( null, is_array( $currentImageCreationStatus ) ? $currentImageCreationStatus : null, $job_id );

			if ( ! is_array( $currentImageCreationStatus ) ) {
				SheetsPilotFunctions::throwError( __( 'Image generation error. Please try again.', 'sheetspilot' ) );
			}

			$poll_status = isset( $currentImageCreationStatus['status'] ) ? (string) $currentImageCreationStatus['status'] : '';

			if ( $poll_status === 'completed' ) {
				$image_result = self::extractImageResultFromResponseOutput( $currentImageCreationStatus, $data_mime );
				if ( $image_result !== null ) {
					return self::finalizeImageGenerationResult( $image_result, $image_query_key, $table_data );
				}

				delete_transient( $image_query_key );
				if ( isset( $table_data['postId'] ) && isset( $table_data['columnIndex'] ) ) {
					self::imageQueueRemoveRequest( $table_data['postId'], $table_data['columnIndex'] );
				}
				SheetsPilotFunctions::throwError( __( 'Image generation error. Please try again.', 'sheetspilot' ) );
			}

			if ( $poll_status === 'failed' ) {
				delete_transient( $image_query_key );
				if ( isset( $table_data['postId'] ) && isset( $table_data['columnIndex'] ) ) {
					self::imageQueueRemoveRequest( $table_data['postId'], $table_data['columnIndex'] );
				}
				$error_message = __( 'Image generation error. Try again.', 'sheetspilot' );
				if ( isset( $currentImageCreationStatus['error']['message'] ) && is_string( $currentImageCreationStatus['error']['message'] ) ) {
					$error_message = $currentImageCreationStatus['error']['message'];
				}
				SheetsPilotFunctions::throwError( $error_message );
			}

			return self::storeImageGenerationJob( $job_id, $image_query_key, $table_data, ( $poll_status === 'queued' ) ? 'queued' : 'in_progress' );
		}

		$api_response = $imageGenObject->runImageCreationQuery( $responseOpts );
		self::setImageGenerationLastResponse( is_array( $api_response ) ? $api_response : null, null, isset( $api_response['id'] ) ? $api_response['id'] : null );

		if ( ! is_array( $api_response ) ) {
			SheetsPilotFunctions::throwError( __( 'Image generation error. Please try again.', 'sheetspilot' ) );
		}

		if ( ! empty( $api_response['error'] ) ) {
			$error_message = __( 'Image generation error. Try again.', 'sheetspilot' );
			if ( isset( $api_response['error']['message'] ) && is_string( $api_response['error']['message'] ) ) {
				$error_message = $api_response['error']['message'];
			}
			SheetsPilotFunctions::throwError( $error_message );
		}

		if ( ! isset( $api_response['id'] ) || ! isset( $api_response['status'] ) ) {
			SheetsPilotFunctions::throwError( __( 'Image generation error. Please try again.', 'sheetspilot' ) );
		}

		$create_status = (string) $api_response['status'];

		if ( $create_status === 'completed' ) {
			$image_result = self::extractImageResultFromResponseOutput( $api_response, $data_mime );
			if ( $image_result !== null ) {
				return self::finalizeImageGenerationResult( $image_result, $image_query_key, $table_data );
			}
			SheetsPilotFunctions::throwError( __( 'Image generation error. Please try again.', 'sheetspilot' ) );
		}

		if ( $create_status === 'failed' ) {
			if ( isset( $table_data['postId'] ) && isset( $table_data['columnIndex'] ) ) {
				self::imageQueueRemoveRequest( $table_data['postId'], $table_data['columnIndex'] );
			}
			SheetsPilotFunctions::throwError( __( 'Image generation error. Try again.', 'sheetspilot' ) );
		}

		if ( $create_status === 'queued' || $create_status === 'in_progress' ) {
			return self::storeImageGenerationJob( $api_response['id'], $image_query_key, $table_data, $create_status );
		}

		SheetsPilotFunctions::throwError( __( 'Image generation error. Please try again.', 'sheetspilot' ) );
	}

	/**
	 * Build a fully transparent PNG mask (same dimensions as the source) for OpenAI image edits.
	 * Transparent regions tell the API the entire image may be replaced.
	 *
	 * @param string $img_bytes Raw image bytes.
	 * @return \CURLFile|null Multipart mask file, or null when GD is unavailable or creation fails.
	 */
	private static function createFullImageEditMaskCurlFile( $img_bytes ) {
		if ( ! function_exists( 'imagecreatefromstring' ) || ! function_exists( 'imagecreatetruecolor' ) ) {
			return null;
		}

		$imgRes = @imagecreatefromstring( $img_bytes );
		if ( $imgRes === false ) {
			return null;
		}

		$maskFile = null;
		$w        = imagesx( $imgRes );
		$h        = imagesy( $imgRes );
		if ( $w > 0 && $h > 0 ) {
			$maskRes = imagecreatetruecolor( $w, $h );
			if ( $maskRes !== false ) {
				imagealphablending( $maskRes, false );
				imagesavealpha( $maskRes, true );
				$transparent = imagecolorallocatealpha( $maskRes, 0, 0, 0, 127 );
				imagefill( $maskRes, 0, 0, $transparent );

				$tmpMaskBase = tempnam( sys_get_temp_dir(), 'ubai_mask_' );
				if ( $tmpMaskBase !== false ) {
					$tmpMaskFile = $tmpMaskBase . '.png';
					imagepng( $maskRes, $tmpMaskFile );
					$maskMime = @mime_content_type( $tmpMaskFile );
					if ( ! is_string( $maskMime ) || $maskMime === '' ) {
						$maskMime = 'image/png';
					}

					$maskFile = curl_file_create( $tmpMaskFile, $maskMime, 'mask' );
				}
				imagedestroy( $maskRes );
			}
		}

		imagedestroy( $imgRes );

		return $maskFile;
	}

	/**
	 * Map a media library URL to a local uploads path (supports UTF-8 filenames).
	 *
	 * @param string $url Image URL.
	 * @return string Absolute file path or empty string.
	 */
	private static function resolveLocalUploadPathFromUrl( $url ) {
		$upload_dir = wp_upload_dir();
		if ( empty( $upload_dir['baseurl'] ) || empty( $upload_dir['basedir'] ) ) {
			return '';
		}

		$url_no_query = strtok( (string) $url, '?' );
		if ( ! is_string( $url_no_query ) || $url_no_query === '' ) {
			return '';
		}

		$baseurl  = untrailingslashit( $upload_dir['baseurl'] );
		$decoded_url  = urldecode( $url_no_query );
		$decoded_base = urldecode( $baseurl );

		$relative = '';
		if ( strpos( $decoded_url, $decoded_base ) === 0 ) {
			$relative = substr( $decoded_url, strlen( $decoded_base ) );
		} elseif ( strpos( $url_no_query, $baseurl ) === 0 ) {
			$relative = urldecode( substr( $url_no_query, strlen( $baseurl ) ) );
		} else {
			return '';
		}

		$relative = '/' . ltrim( str_replace( '\\', '/', $relative ), '/' );
		$path     = wp_normalize_path( $upload_dir['basedir'] . $relative );
		$basedir  = wp_normalize_path( $upload_dir['basedir'] );

		if ( strpos( $path, $basedir ) !== 0 || ! is_file( $path ) || ! is_readable( $path ) ) {
			return '';
		}

		return $path;
	}

	/**
	 * Percent-encode URL path segments for HTTP fetch (non-ASCII filenames, etc.).
	 *
	 * @param string $url Image URL.
	 * @return string
	 */
	private static function encodeUrlPathForHttpFetch( $url ) {
		$parts = wp_parse_url( (string) $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return (string) $url;
		}

		$path = isset( $parts['path'] ) ? (string) $parts['path'] : '';
		if ( $path !== '' ) {
			$segments = explode( '/', $path );
			$encoded  = array();
			foreach ( $segments as $segment ) {
				$encoded[] = ( $segment === '' ) ? '' : rawurlencode( urldecode( $segment ) );
			}
			$parts['path'] = implode( '/', $encoded );
		}

		$out = $parts['scheme'] . '://' . $parts['host'];
		if ( ! empty( $parts['port'] ) ) {
			$out .= ':' . $parts['port'];
		}
		$out .= $parts['path'];
		if ( ! empty( $parts['query'] ) ) {
			$out .= '?' . $parts['query'];
		}
		if ( ! empty( $parts['fragment'] ) ) {
			$out .= '#' . $parts['fragment'];
		}

		return $out;
	}

	/**
	 * Load raw image bytes for image edit — local attachment/uploads file first, then HTTP.
	 *
	 * @param string $image_url     Image URL from the cell or media library.
	 * @param int    $attachment_id Optional attachment post ID.
	 * @return string|false
	 */
	private static function loadImageBytesForEdit( $image_url, $attachment_id = 0 ) {
		$attachment_id = absint( $attachment_id );
		$image_url     = trim( (string) $image_url );

		$try_read_file = static function ( $file ) {
			if ( ! is_string( $file ) || $file === '' || ! is_readable( $file ) ) {
				return false;
			}
			$bytes = file_get_contents( $file );
			return ( is_string( $bytes ) && $bytes !== '' ) ? $bytes : false;
		};

		if ( $attachment_id > 0 ) {
			$bytes = $try_read_file( get_attached_file( $attachment_id ) );
			if ( $bytes !== false ) {
				return $bytes;
			}
		}

		if ( $attachment_id === 0 && class_exists( 'SheetsPilotHelper' ) ) {
			$resolved_id = SheetsPilotHelper::getAttachmentIDFromUrl( $image_url );
			if ( ! empty( $resolved_id ) ) {
				$bytes = $try_read_file( get_attached_file( (int) $resolved_id ) );
				if ( $bytes !== false ) {
					return $bytes;
				}
			}

			$arr_image = SheetsPilotHelper::getAttachmentDataFromUrl( $image_url );
			if ( is_array( $arr_image ) && ! empty( $arr_image['id'] ) ) {
				$bytes = $try_read_file( get_attached_file( (int) $arr_image['id'] ) );
				if ( $bytes !== false ) {
					return $bytes;
				}
			}
		}

		$local_path = self::resolveLocalUploadPathFromUrl( $image_url );
		if ( $local_path !== '' ) {
			$bytes = $try_read_file( $local_path );
			if ( $bytes !== false ) {
				return $bytes;
			}
		}

		$fetch_url = self::encodeUrlPathForHttpFetch( $image_url );
		$response  = wp_remote_get(
			$fetch_url,
			array(
				'timeout'     => 60,
				'redirection' => 5,
			)
		);
		if ( ! is_wp_error( $response ) ) {
			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( $code >= 200 && $code < 300 ) {
				$bytes = wp_remote_retrieve_body( $response );
				if ( is_string( $bytes ) && $bytes !== '' ) {
					return $bytes;
				}
			}
		}

		$bytes = @file_get_contents( $fetch_url );
		return ( is_string( $bytes ) && $bytes !== '' ) ? $bytes : false;
	}

	/**
	 * Edit an existing image via OpenAI Images API (image edits).
	 * Returns edited image URL or throws on error.
	 *
	 * Quality is never sent. Size is optional — pass it for change-image-ratio (outpaint) only;
	 * omit for regular prompt edits so the API keeps the source dimensions.
	 *
	 * @param string      $image_url     Original image URL (must be accessible server-side).
	 * @param string      $prompt        Edit instruction.
	 * @param string|null $size          Optional API size (e.g. 1536x1024) for ratio-change edits only.
	 * @param int         $attachment_id Optional media attachment ID (preferred for local read).
	 * @return string Image URL or data URL.
	 */
	public function makeImageEditCall( $image_url, $prompt, $size = null, $attachment_id = 0 ) {
		
		$this->validateApiKey( $this->api_key );

		$image_model = SheetsPilotGlobals::OPENAI_IMAGE_MODEL;

		$img_bytes = self::loadImageBytesForEdit( $image_url, $attachment_id );
		if ( $img_bytes === false ) {
			SheetsPilotFunctions::throwError( __( 'Image edit failed: unable to download original image.', 'sheetspilot' ) );
		}

		$tmpImgBase = tempnam( sys_get_temp_dir(), 'ubai_img_' );
		if ( $tmpImgBase === false ) {
			SheetsPilotFunctions::throwError( __( 'Image edit failed: unable to create temp file.', 'sheetspilot' ) );
		}

		$pathPart = parse_url( $image_url, PHP_URL_PATH );
		$ext = strtolower( pathinfo( (string) $pathPart, PATHINFO_EXTENSION ) );
		if ( $ext === '' ) {
			$ext = 'png';
		}

		$tmpImgFile = $tmpImgBase . '.' . $ext;
		$written    = file_put_contents( $tmpImgFile, $img_bytes );
		if ( $written === false || ! is_file( $tmpImgFile ) ) {
			SheetsPilotFunctions::throwError( __( 'Image edit failed: unable to write temp file.', 'sheetspilot' ) );
		}

		$mime = @mime_content_type( $tmpImgFile );
		if ( ! is_string( $mime ) || $mime === '' ) {
			$mime = 'application/octet-stream';
		}

		// Edit: keep output type aligned with the source image (jpg → jpeg, webp → webp, etc.).
		$output_format = self::resolveOutputFormatFromSourceImage( $image_url, $tmpImgFile, $mime );
		$data_mime       = 'image/' . ( $output_format === 'jpeg' ? 'jpeg' : (string) $output_format );

		$maskFile = null;
		if ( $this->send_image_edit_mask ) {
			$maskFile = self::createFullImageEditMaskCurlFile( $img_bytes );
		}

		$imgCurlFile = curl_file_create( $tmpImgFile, $mime, 'image' );

		$opts = array(
			'model'         => $image_model,
			'image'         => $imgCurlFile,
			'prompt'        => (string) $prompt,
			'n'             => 1,
			'output_format' => $output_format,
		);
		if ( $size !== null && $size !== '' && (string) $size !== 'auto' ) {
			$opts['size'] = (string) $size;
		}
		if ( $maskFile !== null ) {
			$opts['mask'] = $maskFile;
		}

		$open_ai = new OpenAi( $this->api_key );

		self::$last_request = array( 'image_edit' => $opts );

		// Image edits are not cached (unlike generate_image); inputs differ by current image.

		if ( $this->debug_request ) {
			dmp( 'debug image edit request' );
			dmp( array(
				'api_key' => $this->api_key,
				'request' => $opts,
			) );
			if ( $this->debug_response === false ) {
				exit();
			}
		}


		// SheetsPilotFunctions::writeDebugFile( dirname( __FILE__ ) . '/$opts2_img_1.txt', array( $opts ) );

		$complete = $open_ai->imageEdit( $opts );

		// SheetsPilotFunctions::writeDebugFile( dirname( __FILE__ ) . '/$opts2_img_2.txt', array( $complete ) );

		self::$last_response = $complete;

		if ( $this->debug_response ) {
			dmp( 'debug image edit response' );
			dmp( array(
				'api_key'  => $this->api_key,
				'request'  => $opts,
				'response' => $complete,
			) );
			exit();
		}

		$decoded = json_decode( $complete, true );
		$api_error_message = self::resolveOpenAiImageErrorMessage( $decoded );
		if ( $api_error_message !== null ) {
			SheetsPilotFunctions::throwError( $api_error_message );
		}

		$first = isset( $decoded['data'][0] ) ? $decoded['data'][0] : null;
		if ( ! $first ) {
			SheetsPilotFunctions::throwError( esc_html( __( 'Image edit failed: no URL or image data in response.', 'sheetspilot' ) ) );
		}

		$image_result = null;
		if ( ! empty( $first['url'] ) ) {
			$image_result = $first['url'];
		} elseif ( ! empty( $first['b64_json'] ) ) {
			$image_result = 'data:' . $data_mime . ';base64,' . $first['b64_json'];
		}

		@unlink( $tmpImgFile );
		@unlink( $tmpImgBase );

		if ( isset( $tmpMaskFile ) && is_string( $tmpMaskFile ) ) {
			@unlink( $tmpMaskFile );
		}

		if ( $image_result !== null ) {
			return $image_result;
		}

		SheetsPilotFunctions::throwError( __( 'Image edit failed.', 'sheetspilot' ) );
	 
	}

	/**
	 * return last request and response
	 */
	public static function getLastRequestResponse(){
		return array(
			"request" => self::$last_request,
			"response" => self::$last_response
		);
	}

	/**
	 * Format API request for debug display: system and user messages only, readable HTML with bold headers.
	 *
	 * @param array|null $request Request array with "messages" (array of role/content).
	 * @return string HTML for the debug panel (safe to output).
	 */
	public static function formatDebugRequest( $request ) {
		if ( $request === null || ! is_array( $request ) ) {
			return '<span class="ubai-debug-content">[empty]</span>';
		}

		// Image generation/edit requests use a different shape than chat/completions.
		// makeImageGenerationCall sets: self::$last_request = [ 'image_generation' => $opts ].
		// makeImageEditCall sets: self::$last_request = [ 'image_edit' => $opts ].
		if (
			( isset( $request['image_generation'] ) && is_array( $request['image_generation'] ) )
			|| ( isset( $request['image_edit'] ) && is_array( $request['image_edit'] ) )
		) {
			$img = isset( $request['image_generation'] ) ? $request['image_generation'] : $request['image_edit'];
			$model = isset( $img['model'] ) ? (string) $img['model'] : '';
			$prompt = isset( $img['prompt'] ) ? (string) $img['prompt'] : '';
			$size = isset( $img['size'] ) ? (string) $img['size'] : '';
			$quality = isset( $img['quality'] ) ? (string) $img['quality'] : '';
			$output_format = isset( $img['output_format'] ) ? (string) $img['output_format'] : '';

			$prompt = str_replace( array( '\\n', '\\"' ), array( "\n", '"' ), $prompt );
			$prompt = htmlspecialchars( $prompt, ENT_QUOTES, 'UTF-8' );

			$parts = array();
			if ( $model !== '' ) {
				$parts[] = '<strong>Model:</strong> ' . htmlspecialchars( $model, ENT_QUOTES, 'UTF-8' );
			}
			if ( $size !== '' ) {
				$parts[] = '<strong>Size:</strong> ' . htmlspecialchars( $size, ENT_QUOTES, 'UTF-8' );
			}
			if ( $quality !== '' ) {
				$parts[] = '<strong>Quality:</strong> ' . htmlspecialchars( $quality, ENT_QUOTES, 'UTF-8' );
			}
			if ( $output_format !== '' ) {
				$parts[] = '<strong>Output format:</strong> ' . htmlspecialchars( $output_format, ENT_QUOTES, 'UTF-8' );
			}
			if ( $prompt !== '' ) {
				$parts[] = '<strong>Prompt:</strong> ' . "\n" . $prompt;
			}
			return '<span class="ubai-debug-content">' . implode( "\n\n", $parts ) . '</span>';
		}

		$messages = isset( $request['messages'] ) ? $request['messages'] : array();
		if ( ! is_array( $messages ) || empty( $messages ) ) {
			return '<span class="ubai-debug-content">[empty]</span>';
		}
		$parts = array();
		foreach ( $messages as $msg ) {
			$role    = isset( $msg['role'] ) ? $msg['role'] : '';
			$content = isset( $msg['content'] ) ? (string) $msg['content'] : '';
			$content = str_replace( array( '\\n', '\\"' ), array( "\n", '"' ), $content );
			$content = htmlspecialchars( $content, ENT_QUOTES, 'UTF-8' );
			$label   = $role === 'system' ? 'System message:' : ( $role === 'user' ? 'User message:' : $role . ':' );
			$parts[] = '<strong>' . htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' ) . '</strong>' . "\n" . $content;
		}
		return '<span class="ubai-debug-content">' . implode( "\n\n", $parts ) . '</span>';
	}

	/**
	 * Format API response for debug display: content and instruction summary only, readable HTML with bold headers.
	 *
	 * @param string|null $response Raw JSON response string from the API.
	 * @return string HTML for the debug panel (safe to output).
	 */
	public static function formatDebugResponse( $response ) {
		if ( $response === null || $response === '' ) {
			return '<span class="ubai-debug-content">[empty]</span>';
		}
		$response = trim( (string) $response );
		if ( $response === '' ) {
			return '<span class="ubai-debug-content">[empty]</span>';
		}
		$parsed = json_decode( $response, true );
		if ( is_array( $parsed ) && ( isset( $parsed['create_response'] ) || isset( $parsed['poll_response'] ) ) ) {
			$parts = array();
			if ( ! empty( $parsed['job_id'] ) ) {
				$parts[] = '<strong>Job ID:</strong> ' . htmlspecialchars( (string) $parsed['job_id'], ENT_QUOTES, 'UTF-8' );
			}
			if ( isset( $parsed['create_response'] ) && is_array( $parsed['create_response'] ) ) {
				$create_status = isset( $parsed['create_response']['status'] ) ? (string) $parsed['create_response']['status'] : '';
				$create_id     = isset( $parsed['create_response']['id'] ) ? (string) $parsed['create_response']['id'] : '';
				$create_line   = '<strong>Create response:</strong>';
				if ( $create_status !== '' ) {
					$create_line .= ' status=' . htmlspecialchars( $create_status, ENT_QUOTES, 'UTF-8' );
				}
				if ( $create_id !== '' ) {
					$create_line .= ', id=' . htmlspecialchars( $create_id, ENT_QUOTES, 'UTF-8' );
				}
				$parts[] = $create_line;
			}
			if ( isset( $parsed['poll_response'] ) && is_array( $parsed['poll_response'] ) ) {
				$poll_status = isset( $parsed['poll_response']['status'] ) ? (string) $parsed['poll_response']['status'] : '';
				$poll_line   = '<strong>Poll response:</strong>';
				if ( $poll_status !== '' ) {
					$poll_line .= ' status=' . htmlspecialchars( $poll_status, ENT_QUOTES, 'UTF-8' );
				}
				$error_message = '';
				if ( isset( $parsed['poll_response']['error']['message'] ) ) {
					$error_message = (string) $parsed['poll_response']['error']['message'];
				}
				if ( $error_message !== '' ) {
					$poll_line .= "\n" . '<strong>Error:</strong> ' . htmlspecialchars( $error_message, ENT_QUOTES, 'UTF-8' );
				}
				$image_result = self::extractImageResultFromResponseOutput(
					$parsed['poll_response'],
					'image/' . SheetsPilotGlobals::DEFAULT_IMAGE_FORMAT
				);
				if ( $image_result !== null ) {
					$poll_line .= "\n" . '<strong>Image:</strong> present';
				}
				$parts[] = $poll_line;
			}
			if ( ! empty( $parts ) ) {
				return '<span class="ubai-debug-content">' . implode( "\n\n", $parts ) . '</span>';
			}
		}
		if ( is_array( $parsed ) && isset( $parsed['data'] ) && is_array( $parsed['data'] ) ) {
			// Image generation responses have { created, data: [ { url/b64_json, ... } ] }
			$created = isset( $parsed['created'] ) ? (string) $parsed['created'] : '';
			$first = isset( $parsed['data'][0] ) && is_array( $parsed['data'][0] ) ? $parsed['data'][0] : array();
			$url = isset( $first['url'] ) ? (string) $first['url'] : '';
			$b64 = isset( $first['b64_json'] ) ? (string) $first['b64_json'] : '';

			$parts = array();
			if ( $created !== '' ) {
				$parts[] = '<strong>Created:</strong> ' . htmlspecialchars( $created, ENT_QUOTES, 'UTF-8' );
			}
			if ( $url !== '' ) {
				$parts[] = '<strong>URL:</strong> ' . htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
			} elseif ( $b64 !== '' ) {
				$parts[] = '<strong>b64_json:</strong> ' . htmlspecialchars( 'present (' . strlen( $b64 ) . ' chars)', ENT_QUOTES, 'UTF-8' );
			}
			return '<span class="ubai-debug-content">' . implode( "\n\n", $parts ) . '</span>';
		}

		if ( ! is_array( $parsed ) || empty( $parsed['choices'][0]['message']['content'] ) ) {
			return '<span class="ubai-debug-content">' . htmlspecialchars( $response, ENT_QUOTES, 'UTF-8' ) . '</span>';
		}
		$content = trim( (string) $parsed['choices'][0]['message']['content'] );
		if ( $content === '' ) {
			return '<span class="ubai-debug-content">[empty]</span>';
		}
		$content_json = json_decode( $content, true );
		if ( is_array( $content_json ) ) {
			$data    = isset( $content_json['data'] ) ? (string) $content_json['data'] : '';
			$summary = isset( $content_json['instruction_summary'] ) ? (string) $content_json['instruction_summary'] : '';
			if ( $summary === '' && isset( $content_json['instruction summary'] ) ) {
				$summary = (string) $content_json['instruction summary'];
			}
			$data    = str_replace( array( '\\n', '\\"' ), array( "\n", '"' ), $data );
			$parts   = array();
			if ( $data !== '' ) {
				$parts[] = '<strong>Content:</strong>' . "\n" . htmlspecialchars( $data, ENT_QUOTES, 'UTF-8' );
			}
			if ( $summary !== '' ) {
				$parts[] = '<strong>Instruction summary:</strong>' . "\n" . htmlspecialchars( $summary, ENT_QUOTES, 'UTF-8' );
			}
			return '<span class="ubai-debug-content">' . implode( "\n\n", $parts ) . '</span>';
		}
		$content = str_replace( array( '\\n', '\\"' ), array( "\n", '"' ), $content );
		return '<span class="ubai-debug-content">' . htmlspecialchars( $content, ENT_QUOTES, 'UTF-8' ) . '</span>';
	}

	/**
	 * Whether an OpenAI API error is a safety / moderation rejection.
	 *
	 * Detects error.code (e.g. moderation_blocked), moderation_details, and
	 * message text such as "rejected by the safety system" (request ID is only in the message).
	 *
	 * @param array  $error   Decoded error object from the API response.
	 * @param string $message Error message (optional; read from $error when empty).
	 * @return bool
	 */
	private static function isOpenAiSafetyError( $error, $message = '' ) {
		if ( ! is_array( $error ) ) {
			return false;
		}

		if ( ! is_string( $message ) || $message === '' ) {
			$message = isset( $error['message'] ) ? (string) $error['message'] : '';
		}

		$code = isset( $error['code'] ) ? strtolower( (string) $error['code'] ) : '';
		if ( in_array( $code, array( 'moderation_blocked', 'content_policy_violation' ), true ) ) {
			return true;
		}

		if ( isset( $error['moderation_details'] ) && is_array( $error['moderation_details'] ) ) {
			return true;
		}

		$msg_lower = strtolower( $message );
		$markers   = array(
			'safety system',
			'safety check',
			'content policy',
			'content_policy_violation',
			'did not meet safety',
		);

		foreach ( $markers as $marker ) {
			if ( strpos( $msg_lower, $marker ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * User-facing message when OpenAI blocks an image request for safety reasons.
	 *
	 * @return string
	 */
	private static function getOpenAiImageSafetyErrorMessage() {
		return __( 'The image could not be generated because the request was blocked by the AI content safety filter. Please review your prompt to ensure it complies with content guidelines, then try again.', 'sheetspilot' );
	}

	/**
	 * Resolve an OpenAI image API error for display (friendly text for safety blocks).
	 *
	 * @param array|null $decoded Full decoded API response.
	 * @return string|null Error message, or null when the response has no error.
	 */
	private static function resolveOpenAiImageErrorMessage( $decoded ) {
		if ( ! is_array( $decoded ) || empty( $decoded['error']['message'] ) ) {
			return null;
		}

		$error   = $decoded['error'];
		$message = (string) $error['message'];

		if ( self::isOpenAiSafetyError( $error, $message ) ) {
			return self::getOpenAiImageSafetyErrorMessage();
		}

		return $message;
	}

	/**
	 * Validate OpenAI API key before request
	 */
	private function validateApiKey($api_key){
		
		$api_key = trim((string)$api_key);
				
		if(empty($api_key)){
			SheetsPilotFunctions::throwError(__("OpenAI API key is empty. Please set it in Settings. Key: ",'sheetspilot').$api_key);
		}
		
		if(strlen($api_key) < 20){
			SheetsPilotFunctions::throwError(__("OpenAI API key looks too short. Please check Settings. Key: ",'sheetspilot').$api_key);
		}
		
		if(preg_match('/\s/', $api_key)){
			SheetsPilotFunctions::throwError(__("OpenAI API key contains spaces. Please check Settings. Key: ",'sheetspilot').$api_key);
		}
		
		if(strpos($api_key, "sk-") !== 0){
			SheetsPilotFunctions::throwError(__("OpenAI API key should start with \"sk-\". Please check Settings. Key: ",'sheetspilot').$api_key);
		}
	}

	/**
	 * Parse GPT response, return text or data with type.
	 */
	private function processGptResponse( $decodedResponse ){
		
		if( empty($decodedResponse) ){
			SheetsPilotFunctions::throwError(__("Sorry, AI return wrong data",'sheetspilot'));
		}

		if( isset( $decodedResponse->error ) ){
			SheetsPilotFunctions::throwError($decodedResponse->error->message);
		}

		$responseText = $decodedResponse->choices[0]->message->content;
		
		$responseText = $this->cleanGptResponse( $responseText );

		if( empty($responseText) ){
			SheetsPilotFunctions::throwError(__("Sorry, AI return wrong data",'sheetspilot'));
		}

		$decodedResponseJson = json_decode( $responseText, true );
		if ( ! is_array( $decodedResponseJson ) && class_exists( 'SheetsPilot_ContentBlocks' ) ) {
			$decodedResponseJson = SheetsPilot_ContentBlocks::decode_prompt_json_payload( $responseText );
		}
		
		if( is_array( $decodedResponseJson ) ){
			$responseType = "data";
			$instruction_summary = SheetsPilotFunctions::getVal( $decodedResponseJson, 'instruction_summary', '' );

			if (
				isset( $decodedResponseJson['type'], $decodedResponseJson['data'] )
				&& (string) $decodedResponseJson['type'] === 'data'
			) {
				$inner = $decodedResponseJson['data'];
				if ( is_string( $inner ) && class_exists( 'SheetsPilot_ContentBlocks' ) ) {
					$inner_decoded = SheetsPilot_ContentBlocks::decode_prompt_json_payload( $inner );
					if ( is_array( $inner_decoded ) ) {
						$inner = $inner_decoded;
					}
				}
				if ( is_array( $inner ) ) {
					if ( $instruction_summary === '' && ! empty( $inner['instruction_summary'] ) ) {
						$instruction_summary = (string) $inner['instruction_summary'];
					}
					$decodedResponseJson = $inner;
				}
			}

			$output = array(
				'type' => $responseType,
				'data' => $decodedResponseJson
			);
			if ( $instruction_summary !== '' ) {
				$output['instruction_summary'] = trim( (string) $instruction_summary );
			}
		}else{
			$responseType = "text";
			$responseValue = $decodedResponseJson;
			if( $responseValue === null ){
				$responseValue = $responseText;
			}

			$output = array(
				'type' => $responseType,
				'text' => (string)$responseValue
			);
			 	
		}

		return $output;
	}

	/**
	 * If return is json - extract JSON object or array and clean from trash
	 */
	private function cleanGptResponse( $response_input ){
		// Prefer extracting a JSON object { ... }; fallback to array [ ... ] (legacy)
		if ( preg_match( '/\{[\s\S]*\}/s', $response_input, $obj_match ) ) {
			$response_input = $obj_match[0];
		} elseif ( preg_match( '/\[[\s\S]*\]/s', $response_input, $arr_match ) ) {
			$response_input = $arr_match[0];
		}
		$response_input = str_replace( "\\'", "'", $response_input );
		$response_input = str_replace( "\'", "'", $response_input );
		$response_input = str_replace( '\\\\\"', '\"', $response_input );
		return $response_input;
	}

	/**
	 * helper function to replace all html blocks to placeholder
	 */
	public static function replaceHtmlWithPlaceholders( $inner_array ){
		$replacement_array = [];
		$new_data_array = [];
		foreach( $inner_array as $s_row ){
			$tmp_row = [];
			foreach( $s_row as $s_col ){
							
				if($s_col != wp_strip_all_tags($s_col)) {
					// contains HTML
					$rand_integer = wp_rand(1, 10000).wp_rand(1, 10000);
					$replacement_array[$rand_integer] = $s_col;
					$tmp_row[] = $rand_integer;
				}else{
					$tmp_row[] = $s_col;
				}
			}
			$new_data_array[] = $tmp_row;
		}

		return $new_data_array;
	}


	public static function preparePrompt( $prompt, $replacers ){
		return vsprintf($prompt, $replacers);
	}

	public static function imageQueueAddRequest( $row, $cell, $request_id ){		
		$current_image_queries = get_option('sheetspilot_image_queue_list');
		if( !$current_image_queries ){
			$current_image_queries = [];
		}
		$current_image_queries["cell_".$row."_".$cell] = $request_id;		
		update_option('sheetspilot_image_queue_list', $current_image_queries );
	}
	public static function imageQueueRemoveRequest(  $row, $cell ){
		$current_image_queries = get_option('sheetspilot_image_queue_list');
		if( !$current_image_queries ){
			$current_image_queries = [];
		}
		if( isset($current_image_queries["cell_".$row."_".$cell]) ){
			unset( $current_image_queries["cell_".$row."_".$cell] );
		}		
		update_option('sheetspilot_image_queue_list', $current_image_queries );
	}
	public static function imageQueueGetRequests(){
		$current_image_queries = get_option('sheetspilot_image_queue_list');
		if( !$current_image_queries ){
			$current_image_queries = [];
		}

		return $current_image_queries;		
	}
	 	
}