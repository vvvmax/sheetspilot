<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if(!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilot_Prompts{

	const CONTENT_TYPE_AUTHOR = 'author';
	const CONTENT_TYPE_STATUS = 'status';
	const CONTENT_TYPE_ACF_REPEATER = 'acf_repeater';
	const CONTENT_TYPE_TERMS = 'terms';
	const CONTENT_TYPE_ACF_SELECT = 'acf_select';
	const CONTENT_TYPE_ACF_RADIO = 'acf_radio';
	const CONTENT_TYPE_ACF_CHECKBOX = 'acf_checkbox';

	public static $debugPrompt = false;
	private static $postTypeStructureCache = array();
	private static $lastPromptRequestMetadata = array();

	/**
	 * Output rules appended to the prompt when editing the post_content column.
	 * AI returns a general content-block JSON tree (not Gutenberg markup).
	 *
	 * @var string
	 */
	public static $postContentOutputRules = "The output MUST be valid JSON. In the \"data\" field put a content-block tree object (NOT HTML, NOT Gutenberg markup).\n\n"
		. "Allowed block types (use only these): paragraph, heading, list, separator, quote, table, code, preformatted, details, more.\n\n"
		. "Block tree format:\n"
		. "- \"data\" MUST be: {\"blocks\":[ ... ]}\n"
		. "- Each block MUST have \"type\" set to one of the allowed types.\n"
		. "- paragraph: {\"type\":\"paragraph\",\"text\":\"...\"}\n"
		. "- heading: {\"type\":\"heading\",\"level\":2,\"text\":\"...\"} (level 1-6, default 2)\n"
		. "- list: {\"type\":\"list\",\"ordered\":false,\"items\":[\"item 1\",\"item 2\"]}\n"
		. "- separator: {\"type\":\"separator\"}\n"
		. "- quote: {\"type\":\"quote\",\"text\":\"...\",\"citation\":\"...\"} (citation optional)\n"
		. "- table: {\"type\":\"table\",\"has_header\":true,\"rows\":[[\"H1\",\"H2\"],[\"A\",\"B\"]]}\n"
		. "- code: {\"type\":\"code\",\"text\":\"...\"}\n"
		. "- preformatted: {\"type\":\"preformatted\",\"text\":\"...\"}\n"
		. "- details: {\"type\":\"details\",\"summary\":\"...\",\"blocks\":[{\"type\":\"paragraph\",\"text\":\"...\"}]}\n"
		. "- more: {\"type\":\"more\"}\n\n"
		. "Output rules:\n"
		. "- Return plain text only inside block fields (no HTML tags, no markdown, no Gutenberg comments).\n"
		. "- Put the complete final content in \"data.blocks\" (all blocks in reading order).\n"
		. "- Add \"display_text\": a short plain-text preview (first few lines of the content).\n"
		. "- Do NOT wrap output in markdown code fences.\n"
		. "- Example:\n"
		. "{\"type\":\"data\",\"data\":{\"blocks\":[{\"type\":\"heading\",\"level\":2,\"text\":\"Title\"},{\"type\":\"paragraph\",\"text\":\"Intro text.\"},{\"type\":\"list\",\"ordered\":false,\"items\":[\"Point one\",\"Point two\"]},{\"type\":\"separator\"}]},\"display_text\":\"Title\\nIntro text.\",\"instruction_summary\":\"Rewrite content\"}";

	/**
	 * Extra system-message rules when the edited column is post_content.
	 *
	 * @return string
	 */
	public static function getPostContentAssistantRules() {
		return 'When editing the post_content column, override the default "data as string" rule: '
			. 'put in "data" a JSON object {"blocks":[...]} using the block types and format from the user message. '
			. 'Never return HTML or Gutenberg block comments in "data". '
			. 'Always include "display_text" with a short plain-text preview.';
	}

	public static $acfRepeaterOutputRules = "The output MUST be valid JSON for an ACF repeater update.\n\nOutput Rules:\n"
		. "- The \"data\" array MUST be the complete final repeater value for the column, not only the changed rows.\n"
		. "- Preserve every existing row that was not explicitly changed, in the same order, with all of its existing field values.\n"
		. "- If the instruction modifies one row, return all existing rows and include the modified row in its original position.\n"
		. "- Only remove or omit rows when the instruction explicitly asks to delete rows.\n"
		. "- In addition to \"data\" and \"instruction_summary\" output keys, add \"display_text\" key.\n"
		. "- \"display_text\" must be a short readable summary of the changed row data.\n"
		. "- If \"display_text\" has a summary followed by row details, put <br> before the first row and between each row, so each row is on a new line.\n"
		. "- Example display_text: \"Added 3 rows:<br>Name: Rex, Age: 18<br>Name: Zara, Age: 22<br>Name: Niko, Age: 25\".\n"
		. "- Include the actual fields that were added or modified.\n"
		. "- Do not put field metadata, JSON, HTML (other than <br>), or markdown inside \"display_text\".";

	/*
	public static $termsOutputRules = "Terms output rules:\n"
		. "- Choose only from these existing taxonomy terms. Format: slug,name,count,parent_slug\n{terms}\n"
		. "- Do not invent, create, rename, or return a term that is not in the existing terms list.\n"
		. "- Unless the instruction explicitly asks for multiple terms, return only one term.\n"
	. "- Return valid JSON only, with keys: type, data, display_text, instruction_summary.\n"
	. "- Use type=\"data\".\n"
	. "- In the \"data\" field, output only the selected term slug (example: \"blog\"). Never return \"slug,name\" in data.\n"
	. "- In the \"display_text\" field, output the selected term as \"name\" (example: \"Blog\").\n"
	. "- Example response: {\"type\":\"data\",\"data\":\"blog\",\"display_text\":\"Blog\",\"instruction_summary\":\"Assign blog category\"}.";
	*/
 
	public static $termsOutputRules = "Terms output rules:\n"
		. "- Choose only from these existing taxonomy terms. Format: slug,name,count,parent_slug\n{terms}\n"
		. "- Do not invent, create, rename, or return a term that is not in the existing terms list.\n"
		//. "- Unless the instruction explicitly asks for multiple terms, return only one term.\n"
		. "- You allowed to select multiple terms in case they have good fit. Separate values with comma.\n"
	. "- Return valid JSON only, with keys: type, data, display_text, instruction_summary.\n"
	. "- Use type=\"data\".\n"
	. "- In the \"data\" field, output only the selected term slug (example: \"blog\"). Never return \"slug,name\" in data. Separate values with comma, in case there will be more one. (example: \"blog,blogger\")\n"
	. "- In the \"display_text\" field, output the selected term as \"name\" (example: \"Blog\"). Separate values with comma in case there will be more one value  (example: \"Blog, Blogger\")\n"
	. "- Example response for single value: {\"type\":\"data\",\"data\":\"blog\",\"display_text\":\"Blog\",\"instruction_summary\":\"Assign blog category\"} for single value."
	. "- Example response for multiple values: {\"type\":\"data\",\"data\":\"blog,blogger\",\"display_text\":\"Blog,Blogger\",\"instruction_summary\":\"Assign blog category\"} for multiple values.";
	 

	private static function _________FIELD_HELPERS__________( ){}

	/**
	 * Strip leading "plugin_" from keys for prompt text only (lookup keys stay unchanged).
	 *
	 * @param string $key Field / column key.
	 * @return string
	 */
	private static function strip_plugin_prefix_from_field_key( $key ) {
		if ( ! is_string( $key ) || $key === '' ) {
			$output = $key;
			return $output;
		}
		if ( strlen( $key ) > 7 && strncmp( $key, 'plugin_', 7 ) === 0 ) {
			$stripped = substr( $key, 7 );
			if ( $stripped !== '' ) {
				$output = $stripped;
				return $output;
			}
		}
		$output = $key;
		return $output;
	}

	/**
	 * Build the prompt-facing column reference, including the table title when available.
	 *
	 * @param string $column_name Table column key.
	 * @param array  $cell_types  Cell metadata keyed by column name.
	 * @return string
	 */
	private static function getPromptColumnLabel( $column_name, $cell_types = array() ) {
		$column_label = self::strip_plugin_prefix_from_field_key( $column_name );
		$column_title = '';

		if ( isset( $cell_types[ $column_name ] ) && is_array( $cell_types[ $column_name ] ) ) {
			$column_title = trim( wp_strip_all_tags( (string) SheetsPilotFunctions::getVal( $cell_types[ $column_name ], 'title', '' ) ) );
		}

		$output = '"' . $column_label . '"';
		if ( $column_title !== '' && $column_title !== $column_name && $column_title !== $column_label ) {
			$output .= ' (' . $column_title . ')';
		}

		return $output;
	}


	/** Max length for inlining @field values into prompt text; longer values stay as "field" references. */
	const PROMPT_REFERENCE_INLINE_MAX_LENGTH = 200;

	/**
	 * Flat string value for a post field when substituting @references into prompt text.
	 *
	 * @param array<string,mixed> $post_data  Row fields.
	 * @param string              $field_key Normalized column key.
	 * @return string
	 */
	private static function getPostFieldValueForReferenceInline( $post_data, $field_key ) {
		if ( ! is_array( $post_data ) || ! array_key_exists( $field_key, $post_data ) ) {
			return '';
		}
		$val = $post_data[ $field_key ];
		if ( is_array( $val ) || is_object( $val ) ) {
			$encoded = wp_json_encode( $val );
			$val     = is_string( $encoded ) ? $encoded : '';
		} else {
			$val = SheetsPilotFunctions::toString( $val );
		}
		return trim( wp_strip_all_tags( (string) $val ) );
	}

	/**
	 * Replace @column_name: short values inline, long values as "column_name" field (empty → "").
	 *
	 * @param string              $prompt_text User prompt that may contain @column_name.
	 * @param array<string,mixed> $post_data   Row fields for substitution.
	 * @return array{text:string,inlined:array<string,bool>}
	 */
	private static function expandPromptColumnReferencesWithPostData( $prompt_text, $post_data ) {
		$inlined = array();
		if ( ! is_string( $prompt_text ) || $prompt_text === '' ) {
			return array(
				'text'    => $prompt_text,
				'inlined' => $inlined,
			);
		}
		if ( ! is_array( $post_data ) ) {
			$post_data = array();
		}
		$callback = function ( $matches ) use ( $post_data, &$inlined ) {
			$column_name = self::strip_plugin_prefix_from_field_key( $matches[1] );
			$field_key   = SheetsPilotFunctions::normalize_cell_rule_field_key( $column_name );
			if ( $field_key === '' || $field_key === 'bulk' ) {
				return $matches[0];
			}
			$field_value = self::getPostFieldValueForReferenceInline( $post_data, $field_key );
			$length      = function_exists( 'mb_strlen' )
				? mb_strlen( $field_value, 'UTF-8' )
				: strlen( $field_value );
			if ( $length < self::PROMPT_REFERENCE_INLINE_MAX_LENGTH ) {
				$inlined[ $field_key ] = true;
				return $field_value;
			}
			return '"' . $column_name . '" field';
		};
		$output = preg_replace_callback( '/@([a-zA-Z_][a-zA-Z0-9_]*)/', $callback, $prompt_text );
		return array(
			'text'    => $output,
			'inlined' => $inlined,
		);
	}

	/**
	 * Replace @column_name tokens when post data is unavailable (quoted field name only).
	 *
	 * @param string $prompt_text User prompt that may contain @column_name.
	 * @return string
	 */
	private static function expandPromptColumnReferences( $prompt_text ) {
		$result = self::expandPromptColumnReferencesWithPostData( $prompt_text, array() );
		return $result['text'];
	}

	/**
	 * Merge inlined-field flags from prompt and rule expansion.
	 *
	 * @param array<string,bool> ...$maps Inlined maps from expandPromptColumnReferencesWithPostData.
	 * @return array<string,bool>
	 */
	private static function mergeInlinedReferenceFields( ...$maps ) {
		$merged = array();
		foreach ( $maps as $map ) {
			if ( ! is_array( $map ) ) {
				continue;
			}
			foreach ( $map as $field => $was_inlined ) {
				if ( $was_inlined ) {
					$merged[ $field ] = true;
				}
			}
		}
		return $merged;
	}

	private static function _________CELL_RULES__________( ){}

	/**
	 * True when the client sent an apply_prompt table flag and it is enabled (checkbox visible and checked).
	 * If the key is absent (checkbox hidden), returns false.
	 *
	 * @param array  $table_data Apply prompt table payload.
	 * @param string $key        e.g. include_rules, use_current_cell_data.
	 * @return bool
	 */
	public static function isApplyPromptTableOptionEnabled( $table_data, $key ) {
		if ( ! is_array( $table_data ) || ! array_key_exists( $key, $table_data ) ) {
			return false;
		}
		$value = $table_data[ $key ];
		return ( $value === true || $value === 1 || $value === '1' || $value === 'true' );
	}

	/**
	 * Column rule prompt: prefer table_data.cell_rules.rule when sent by the client; else stored rules by column key.
	 *
	 * @param array       $table_data    Apply prompt table payload.
	 * @param string|null $post_type_key Sanitized post type.
	 * @param string      $column_name   Table column key.
	 * @return string Trimmed rule text or empty string.
	 */
	private static function getColumnRuleTextFromTableData( $table_data, $post_type_key, $column_name ) {
		
		$column_key = SheetsPilotFunctions::normalize_cell_rule_field_key( $column_name );
		if ( $column_key === '' ) {
			return '';
		}

		$payload_rules = SheetsPilotFunctions::getVal( $table_data, 'cell_rules', array() );
		if ( is_array( $payload_rules ) && array_key_exists( 'rule', $payload_rules ) ) {
			return trim( (string) $payload_rules['rule'] );
		}

		if ( $post_type_key === null ) {
			return '';
		}

		$stored_rules = SheetsPilot_PromptsUI::get_cell_rules( $post_type_key );
		return trim( (string) SheetsPilotFunctions::getVal( $stored_rules, $column_key, '' ) );
	}

	/**
	 * Column keys referenced in rule text (@field or "field" / "field" field after expansion).
	 *
	 * @param string $rule_text Raw or expanded column rule prompt.
	 * @return array<int,string>
	 */
	private static function parseColumnReferencesFromRuleText( $rule_text ) {
		if ( ! is_string( $rule_text ) || $rule_text === '' ) {
			return array();
		}
		$out = array();
		if ( preg_match_all( '/@([a-zA-Z_][a-zA-Z0-9_]*)/', $rule_text, $at_matches ) ) {
			foreach ( $at_matches[1] as $col ) {
				$k = SheetsPilotFunctions::normalize_cell_rule_field_key( self::strip_plugin_prefix_from_field_key( $col ) );
				if ( $k !== '' && $k !== 'bulk' ) {
					$out[] = $k;
				}
			}
		}
		if ( preg_match_all( '/"([a-zA-Z_][a-zA-Z0-9_]*)"\s+field/', $rule_text, $quoted_matches ) ) {
			foreach ( $quoted_matches[1] as $col ) {
				$k = SheetsPilotFunctions::normalize_cell_rule_field_key( self::strip_plugin_prefix_from_field_key( $col ) );
				if ( $k !== '' && $k !== 'bulk' ) {
					$out[] = $k;
				}
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * User prompt + column rule text combined for @field / "field" field target detection.
	 *
	 * @param string $prompt_text     User instruction (may already be expanded).
	 * @param string $column_rule_raw Column rule text (raw, before intro expansion).
	 * @return string
	 */
	private static function buildPostDataTargetsSourceText( $prompt_text, $column_rule_raw = '' ) {
		$source = trim( (string) $prompt_text );
		$rule   = trim( (string) $column_rule_raw );
		if ( $rule !== '' ) {
			$source = ( $source !== '' ) ? ( $source . "\n" . $rule ) : $rule;
		}
		return $source;
	}

	/**
	 * Post data fields for the prompt block: post_title by default (unless skipped), plus @refs in prompt and rule text only.
	 *
	 * @param string $source_text                 User prompt + column rule (not the assembled system intro/response).
	 * @param bool   $include_default_post_title  When false, only @-referenced fields are included (e.g. image edit).
	 * @return array<int,string>
	 */
	private static function resolvePostDataTargetsFromPromptSources( $source_text, $include_default_post_title = true ) {

		$targets = $include_default_post_title ? array( 'post_title' ) : array();

		$from_text = self::parseColumnReferencesFromRuleText( $source_text );
		if ( ! empty( $from_text ) ) {
			$targets = array_merge( $targets, $from_text );
		}

		return array_values( array_unique( $targets ) );
	}

	/**
	 * Post data block fields: referenced targets that were not inlined into the prompt/rule text.
	 *
	 * @param string              $source_text                 Raw user prompt + column rule (with @refs).
	 * @param array<string,bool>  $inlined_fields              Field keys substituted inline (short values).
	 * @param bool                $include_default_post_title  When false, post_title is not added unless @-referenced.
	 * @return array<int,string>
	 */
	private static function resolvePostDataContextTargets( $source_text, $inlined_fields, $include_default_post_title = true ) {
		
		$all_referenced = self::resolvePostDataTargetsFromPromptSources( $source_text, $include_default_post_title );

		$context        = array();
		foreach ( $all_referenced as $field ) {
			if ( empty( $inlined_fields[ $field ] ) ) {
				$context[] = $field;
			}
		}

		return $context;
	}

	/**
	 * Keep only keys listed in $targets, in that order.
	 *
	 * @param array<string,mixed> $post_data Flat post row for prompt.
	 * @param array<int,string>   $targets   Column keys to include.
	 * @return array<string,mixed>
	 */
	private static function filter_post_data_by_targets( $post_data, $targets ) {
		if ( ! is_array( $post_data ) ) {
			return array();
		}
		if ( empty( $targets ) || ! is_array( $targets ) ) {
			return array();
		}
		$filtered = array();
		foreach ( $targets as $field ) {
			if ( array_key_exists( $field, $post_data ) ) {
				$filtered[ $field ] = $post_data[ $field ];
			}
		}
		return $filtered;
	}

	/**
	 * Append "--- Post data ---" lines for non-empty fields (skips bulk).
	 *
	 * @param string              $response  Prompt fragment to append to.
	 * @param array<string,mixed> $post_data Fields to render.
	 * @return string
	 */
	private static function append_post_data_block( $response, $post_data ) {
		if ( empty( $post_data ) || ! is_array( $post_data ) ) {
			return $response;
		}
		$lines = '';
		foreach ( $post_data as $field_name => $field_value ) {
			if ( $field_name === 'bulk' ) {
				continue;
			}
			$field_value = trim( (string) $field_value );
			if ( $field_value !== '' ) {
				$display_key = self::strip_plugin_prefix_from_field_key( $field_name );
				$lines      .= $display_key . ': ' . $field_value . "\n";
			}
		}
		if ( $lines === '' ) {
			return $response;
		}
		$response .= "\n\nUse the following post data as context for this row.\n--- Post data ---\n";
		$response .= $lines;
		return $response;
	}
	
	private static function _________COLUMN_VALUE__________( ){}

	/**
	 * Resolve the current value for an ACF repeater column.
	 *
	 * @param mixed  $value       Current value from the table payload.
	 * @param string $column_name Table column key.
	 * @param array  $post_data   Flattened post data for the current row.
	 * @return mixed
	 */
	private static function getColumnValue__ACFRepeater( $value, $column_name, $post_data ) {
		if ( ! class_exists( 'SheetsPilotACFRepeaterProcessing' ) ) {
			return $value;
		}

		$post_id = (int) SheetsPilotFunctions::getVal( $post_data, 'id', 0 );
		if ( $post_id <= 0 ) {
			return $value;
		}

		$repeater_name = (string) $column_name;
		if ( strncmp( $repeater_name, 'acf_', 4 ) === 0 ) {
			$repeater_name = substr( $repeater_name, 4 );
		}
		$repeater_name = trim( $repeater_name );
		if ( $repeater_name === '' ) {
			return $value;
		}

		$repeater_processing = new SheetsPilotACFRepeaterProcessing();
		$repeater_values     = $repeater_processing->get_acf_repeater_values_clear( $repeater_name, $post_id );
		if ( empty( $repeater_values ) ) {
			return $value;
		}

		return $repeater_values;
	}

	/**
	 * Get current post terms for a taxonomy column.
	 *
	 * @param mixed  $value       Current value from the table payload.
	 * @param string $column_name Table column key, expected to match the taxonomy name.
	 * @param array  $post_data   Flattened post data for the current row.
	 * @return mixed
	 */
	private static function getColumnValue__Terms( $value, $column_name, $post_data ) {

		$post_id = (int) SheetsPilotFunctions::getVal( $post_data, 'id', 0 );
		if ( $post_id <= 0 ) {
			return $value;
		}

		$taxonomy = trim( (string) $column_name );
		if ( $taxonomy === '' || ! taxonomy_exists( $taxonomy ) ) {
			return $value;
		}

		$terms = wp_get_post_terms( $post_id, $taxonomy );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $value;
		}

		$output = array();
		foreach ( $terms as $term ) {
			$output[] = array(
				'id'    => $term->term_id,
				'slug'  => $term->slug,
				'name'  => $term->name,
				'count' => $term->count,
			);
		}

		return $output;
	}

	/**
	 * Modify terms value for prompt text.
	 *
	 * @param mixed $value Terms value.
	 * @return string
	 */
	private static function modifyForTermsPrompt( $value ) {

		if ( ! is_array( $value ) ) {
			return '';
		}

		$output = array();
		foreach ( $value as $term ) {
			if ( is_object( $term ) ) {
				$name = isset( $term->name ) ? $term->name : '';
				$slug = isset( $term->slug ) ? self::decodeTermSlugForPrompt( $term->slug ) : '';
			} else {
				$name = SheetsPilotFunctions::getVal( $term, 'name', '' );
				$slug = self::decodeTermSlugForPrompt( SheetsPilotFunctions::getVal( $term, 'slug', '' ) );
			}

			if ( $name === '' && $slug === '' ) {
				continue;
			}

			$output[] = 'Name:' . $name . ',slug:' . $slug;
		}

		$result = implode( ',', $output );

		return $result;
	}

	/**
	 * Decode URL-encoded term slugs for prompt readability.
	 *
	 * @param string $slug Term slug.
	 * @return string
	 */
	private static function decodeTermSlugForPrompt( $slug ) {

		$slug   = (string) $slug;
		$output = rawurldecode( $slug );

		return $output;
	}

	/**
	 * Format taxonomy terms as compact lines for the prompt allowed-terms list.
	 *
	 * @param array  $terms    Terms to format.
	 * @param string $taxonomy Taxonomy name, used when resolving parent terms outside the limited list.
	 * @return string
	 */
	private static function formatTermsForPromptRules( $terms, $taxonomy ) {

		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return '';
		}

		$terms_by_id = array();
		foreach ( $terms as $term ) {
			$terms_by_id[ (int) $term->term_id ] = $term;
		}

		$terms_prompt_lines = array();
		foreach ( $terms as $term ) {
			$parent_slug = '';
			$parent_id   = isset( $term->parent ) ? (int) $term->parent : 0;

			// Prefer parent data already loaded in the 100-term list to avoid extra queries.
			if ( $parent_id > 0 && isset( $terms_by_id[ $parent_id ] ) ) {
				$parent_slug = $terms_by_id[ $parent_id ]->slug;
			}

			// Resolve parents outside the limited list so child terms still include context.
			if ( $parent_id > 0 && $parent_slug === '' ) {
				$parent_term = get_term( $parent_id, $taxonomy );
				if ( $parent_term && ! is_wp_error( $parent_term ) ) {
					$parent_slug = $parent_term->slug;
				}
			}

			$slug        = self::decodeTermSlugForPrompt( $term->slug );
			$parent_slug = self::decodeTermSlugForPrompt( $parent_slug );

			// Keep each term compact: slug,name,count,parent_slug.
			$terms_prompt_lines[] = implode(
				',',
				array(
					$slug,
					$term->name,
					isset( $term->count ) ? (string) $term->count : '0',
					$parent_slug,
				)
			);
		}

		$output = implode( "\n", $terms_prompt_lines );

		return $output;
	}

	/**
	 * Build output rules for taxonomy terms.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return string
	 */
	private static function buildTermsOutputRules( $taxonomy ) {

		$taxonomy = trim( (string) $taxonomy );
		if ( $taxonomy === '' || ! taxonomy_exists( $taxonomy ) ) {
			return '';
		}

		$max_terms = 100;

		// Keep the terms list short enough for the prompt while still giving the AI valid choices.
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => $max_terms,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		// If the full list hits the cap, prioritize terms that are already used by posts.
		if ( count( $terms ) === $max_terms ) {
			$used_terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => true,
					'number'     => $max_terms,
				)
			);

			if ( ! is_wp_error( $used_terms ) && ! empty( $used_terms ) ) {
				$merged_terms = array();
				$seen_terms   = array();

				// Merge used terms first, then fill the rest from all terms without duplicates.
				foreach ( array( $used_terms, $terms ) as $terms_group ) {
					foreach ( $terms_group as $term ) {
						$term_id = (int) $term->term_id;
						if ( isset( $seen_terms[ $term_id ] ) ) {
							continue;
						}

						$merged_terms[]          = $term;
						$seen_terms[ $term_id ] = true;

						// Preserve the maximum prompt size after merging both lists.
						if ( count( $merged_terms ) === $max_terms ) {
							break 2;
						}
					}
				}

				$terms = $merged_terms;
			}
		}


		// Put the most-used terms first so the prompt favors terms that appear on more posts.
		usort(
			$terms,
			static function( $term_a, $term_b ) {
				$count_a = isset( $term_a->count ) ? (int) $term_a->count : 0;
				$count_b = isset( $term_b->count ) ? (int) $term_b->count : 0;

				$output = $count_b <=> $count_a;

				return $output;
			}
		);

		$terms_prompt = self::formatTermsForPromptRules( $terms, $taxonomy );
		if ( $terms_prompt === '' ) {
			return '';
		}

		$output = str_replace( '{terms}', $terms_prompt, self::$termsOutputRules );

		return $output;
	}

	/**
	 * Plain-text post_content for prompt context (strip Gutenberg markup; Elementor → text).
	 *
	 * @param mixed $value   Raw cell or post_content value.
	 * @param int   $post_id Post ID when available.
	 * @return string
	 */
	private static function getPostContentValueForPrompt( $value, $post_id = 0 ) {
		$post_id = (int) $post_id;

		if ( $post_id > 0 && class_exists( 'SheetsPilotHelperElementor' ) && SheetsPilotHelperElementor::isPostBuiltWithElementor( $post_id ) ) {
			$text = SheetsPilotHelperElementor::getPostContentDisplayForEditor( $post_id );
			if ( $text !== '' ) {
				return self::normalizeWhitespace( $text );
			}
		}

		$text = self::get_plain_text_for_prompt_display( SheetsPilotFunctions::toString( $value ) );
		return self::normalizeWhitespace( $text );
	}

	/**
	 * Resolve the column value used in the prompt intro.
	 *
	 * @param mixed       $value             Current value from the table payload.
	 * @param string      $column_name       Table column key.
	 * @param array       $post_data         Flattened post data for the current row.
	 * @param string|null $cell_content_type Cell content type from getCellContentType.
	 * @param int         $post_id           Current post ID.
	 * @return array
	 */
	private static function getColumnValue( $value, $column_name, $post_data, $cell_content_type, $post_id = 0 ) {

		switch($cell_content_type) {
			case self::CONTENT_TYPE_ACF_REPEATER:
				$value = self::getColumnValue__ACFRepeater( $value, $column_name, $post_data );
			break;
			case self::CONTENT_TYPE_TERMS:
				$value = self::getColumnValue__Terms( $value, $column_name, $post_data );
			break;
		}


		//modify the value for prompt

		$value_for_prompt = $value;
		if ( $column_name === 'post_content' ) {
			$value_for_prompt = self::getPostContentValueForPrompt( $value, $post_id );
		} elseif ( $cell_content_type === self::CONTENT_TYPE_TERMS ) {
			$value_for_prompt = self::modifyForTermsPrompt( $value );
		}

		$value_prompt = SheetsPilotFunctions::toString( $value_for_prompt );

		$output = array(
			'value_real'   => $value,
			'value_prompt' => $value_prompt,
		);

		return $output;
	}

	private static function _________PROMPT_FROM_TABLE__________( ){}

	/**
	 * Build prompt request metadata for request/response logging and debug UI.
	 *
	 * @param string              $column_name       Target column key.
	 * @param string|null         $cell_content_type Resolved content type.
	 * @param array<string,mixed> $table_data        Incoming table payload.
	 * @param string              $prompt_text       Prompt text before expansion.
	 * @param array<string,mixed> $post_data         Flattened row context.
	 * @param array<string,mixed> $cell_types        Column metadata map.
	 * @param int                 $post_id           Current post ID.
	 * @param string              $post_type         Current post type.
	 * @param bool                $is_image_column   Whether this is an image column flow.
	 * @return array<string,mixed>
	 */
	private static function buildPromptRequestMetadata( $column_name, $cell_content_type, $table_data, $prompt_text, $post_data, $cell_types, $post_id, $post_type, $is_image_column ) {
		$metadata = array(
			'event'             => 'getPromptFromTable',
			'column'            => (string) $column_name,
			'post_id'           => (int) $post_id,
			'post_type'         => (string) $post_type,
			'cell_content_type' => $cell_content_type ? (string) $cell_content_type : '',
			'is_image_column'   => (bool) $is_image_column,
			'prompt_text'       => (string) $prompt_text,
			'table_data'        => is_array( $table_data ) ? $table_data : array(),
			'post_data'         => is_array( $post_data ) ? $post_data : array(),
			'cell_types'        => is_array( $cell_types ) ? $cell_types : array(),
		);

		return $metadata;
	}

	/**
	 * Store last prompt request metadata in memory for current request lifecycle.
	 *
	 * @param array<string,mixed> $metadata Metadata payload.
	 * @return void
	 */
	private static function setLastPromptRequestMetadata( $metadata ) {
		self::$lastPromptRequestMetadata = is_array( $metadata ) ? $metadata : array();
	}

	/**
	 * Append a free-text note to the last prompt request metadata.
	 *
	 * @param string $text Note text.
	 * @return void
	 */
	private static function addStringToLastPromptRequestMetadata( $text ) {
		$text = trim( (string) $text );
		if ( $text === '' ) {
			return;
		}
		if ( ! is_array( self::$lastPromptRequestMetadata ) ) {
			self::$lastPromptRequestMetadata = array();
		}
		if ( ! isset( self::$lastPromptRequestMetadata['notes'] ) || ! is_array( self::$lastPromptRequestMetadata['notes'] ) ) {
			self::$lastPromptRequestMetadata['notes'] = array();
		}
		self::$lastPromptRequestMetadata['notes'][] = $text;
	}

	/**
	 * Append a free-text note to the last prompt request metadata (public wrapper).
	 *
	 * @param string $text Note text.
	 * @return void
	 */
	public static function addNoteToLastPromptRequestMetadata( $text ) {
		self::addStringToLastPromptRequestMetadata( $text );
	}

	/**
	 * Return last prompt request metadata collected during getPromptFromTable().
	 *
	 * @return array<string,mixed>
	 */
	public static function getLastPromptRequestMetadata() {
		$metadata = is_array( self::$lastPromptRequestMetadata ) ? self::$lastPromptRequestMetadata : array();
		if ( class_exists( 'SheetsPilot_RequestLog', false ) ) {
			$metadata = SheetsPilot_RequestLog::mergeTimingIntoMetadata( $metadata );
		}

		return $metadata;
	}

	/**
	 * Build a prompt from table data and prompt text.
	 * Collects post data from the server by postId (all table columns, including hidden)
	 * and appends them as key/value pairs to the prompt.
	 */
	public static function getPromptFromTable( $table_data, $prompt_text, &$cell_content_type_output = null ) {
		
		$showDebug = false;

		$value      = SheetsPilotFunctions::getVal( $table_data, 'value' );
		$columnName = SheetsPilotFunctions::getVal( $table_data, 'column' );
		$post_id    = SheetsPilotFunctions::getVal( $table_data, 'postId' );
		$post_type  = trim( (string) SheetsPilotFunctions::getVal( $table_data, 'post_type', '' ) );
		$cell_type  = SheetsPilotFunctions::getVal( $table_data, 'cellType' );

		$post_data = array();
		$cell_types = array();
		if ( ! empty( $post_id ) ) {
			$post_data = self::getPostDataForKeyValuePrompt( (int) $post_id );
			$cell_types = self::getPostFieldTypesForPrompt( (int) $post_id );
		}
		
	

		$cell_content_type_output = self::getCellContentType( $columnName, $cell_types );
		$cell_content_type = $cell_content_type_output;

	


		$prompt_text_raw = $prompt_text;

		$column_exists = ! empty( $columnName ) && array_key_exists( $columnName, $post_data );
		$post_title_available = ! empty( $post_data['post_title'] ) && trim( (string) $post_data['post_title'] ) !== '';
		$is_image_column = ( $columnName === 'post_image' || $cell_type === 'image' );

		if ( $post_type === '' && ! empty( $post_id ) ) {
			$post = get_post( (int) $post_id );
			if ( $post && ! empty( $post->post_type ) ) {
				$post_type = $post->post_type;
			}
		}

		$metadata = self::buildPromptRequestMetadata(
			$columnName,
			$cell_content_type,
			$table_data,
			$prompt_text,
			$post_data,
			$cell_types,
			(int) $post_id,
			$post_type,
			$is_image_column
		);
		self::setLastPromptRequestMetadata( $metadata );

		 

		 		
		if ( $showDebug || SheetsPilotGlobals::$showDebugByUrl) {
			dmp("getPromptFromTable metadata: " . print_r( $metadata, true ));

			if(SheetsPilotGlobals::$showDebugByUrl == false)
				exit();
		}

		$post_type_key = $post_type !== '' ? sanitize_key( $post_type ) : null;

		//build image column prompt

		$include_rules         = self::isApplyPromptTableOptionEnabled( $table_data, 'include_rules' );
		$use_current_cell_data = self::isApplyPromptTableOptionEnabled( $table_data, 'use_current_cell_data' );

		if ( $is_image_column ) {
			$result = self::buildPromptFromTableImageColumn( $prompt_text_raw, $columnName, $post_type_key, $post_data, $table_data );
			return $result;
		}


		//get the current column value
		$column_value = self::getColumnValue( $value, $columnName, $post_data, $cell_content_type, (int) $post_id );

		$valueReal = SheetsPilotFunctions::getVal( $column_value, 'value_real' );
		$value     = SheetsPilotFunctions::getVal( $column_value, 'value_prompt', '' );

		//some protection
		$value = is_string( $value ) ? trim( $value ) : '';

		//other column types prompt
		$column_label = self::getPromptColumnLabel( $columnName, $cell_types );
		if ( empty( $value ) || (  isset( $table_data['use_current_cell_data'] ) && !$use_current_cell_data )  ) {
			$intro = 'You are editing the column ' . $column_label . '. Return only the new value for this column.';
			
		}else if( in_array( $metadata['table_data']['cellType'] ,[ 'acf_woo_gallery', 'image']  ) ){
			// patch for image post type
			$intro = 'You are editing the column ' . $column_label . '.';
		}else {
			$intro = 'You are editing the column ' . $column_label . ' (current value: ' . $value . '). Return only the new value for this column.';
		}


		if ( $columnName === 'post_content' ) {
			$intro .= "\n\n" . self::$postContentOutputRules;
		}

		if ( $cell_content_type === self::CONTENT_TYPE_ACF_REPEATER ) {
			$intro .= "\n\n" . self::$acfRepeaterOutputRules;
		}
 
		if ( $cell_content_type === self::CONTENT_TYPE_TERMS ) {
			$terms_output_rules = self::buildTermsOutputRules( $columnName );
			if ( $terms_output_rules !== '' ) {
				$intro .= "\n\n" . $terms_output_rules;
			}
		}


		if ( $value === '' && $post_title_available && $use_current_cell_data ) {
			$intro .= "\n\nThe current value for this column is empty; use the post_title from the post data below as the source content to edit.";
		}


		// Column rule text (for intro and for post-data @field detection).
		$column_rule_raw = '';
		if ( $include_rules && $post_type_key !== null && $columnName !== '' ) {
			$column_rule_raw = self::getColumnRuleTextFromTableData( $table_data, $post_type_key, $columnName );
		}
		
		self::addStringToLastPromptRequestMetadata( 'column rule raw: ' . $column_rule_raw );
		if(SheetsPilotGlobals::$showDebugByUrl) {
			dmp("column rule raw: " . $column_rule_raw);
		}

		// @refs: detect targets from raw text; inline short values; context block for the rest.
		$targets_source_text = self::buildPostDataTargetsSourceText( $prompt_text_raw, $column_rule_raw );

		$prompt_expand   = self::expandPromptColumnReferencesWithPostData( $prompt_text_raw, $post_data );
		$prompt_text     = $prompt_expand['text'];
		$inlined_fields  = $prompt_expand['inlined'];

		if ( $column_rule_raw !== '' ) {
			$rule_expand    = self::expandPromptColumnReferencesWithPostData( $column_rule_raw, $post_data );
			$column_rule    = $rule_expand['text'];
			$inlined_fields = self::mergeInlinedReferenceFields( $inlined_fields, $rule_expand['inlined'] );
			$intro         .= "\n\nColumn rules (always apply when editing this column):\n" . $column_rule;
		}

		$targets = self::resolvePostDataContextTargets( $targets_source_text, $inlined_fields );

		//add intro

		$intro .= "\n\nInstruction: ";
		$response = trim( $intro . $prompt_text );

		//add specific cell type related instructions
				
		$cell_type_prompt_additions = self::buildCellTypePromptAdditions( $cell_content_type, $post_type_key, $columnName, $post_id );
		
		if ( $cell_type_prompt_additions !== '' ) {
			$response .= $cell_type_prompt_additions;
		}

		//add post data block to prompt
		$to_render = self::filter_post_data_by_targets( $post_data, $targets );
		$response  = self::append_post_data_block( $response, $to_render );
		
		$result = trim( $response );

		if ( self::$debugPrompt ) {
			dmpHTML( $result );
			exit;
		}

		return $result;
	}

	private static function _________CELL_TYPES__________( ){}

	/**
	 * get cell content type
	 */
	public static function getCellContentType( $cellName, $cell_types = array() ) {

		$showDebug = false;

		$output = null;

		switch($cellName) {
			case 'post_author':
				$output = self::CONTENT_TYPE_AUTHOR;
				return $output;
			case 'post_status':
				$output = self::CONTENT_TYPE_STATUS;
				return $output;
		}

		//get by cell types

		if (empty($output) && isset( $cell_types[ $cellName ] ) ) {
			$cell_type_data = $cell_types[ $cellName ];
			if ( is_array( $cell_type_data ) ) {
				$acf_type = SheetsPilotFunctions::getVal( $cell_type_data, 'acf_type', '' );
				if ( $acf_type !== '' ) {
					$output = 'acf_' . $acf_type;
				} else {
					$output = SheetsPilotFunctions::getVal( $cell_type_data, 'type', '' );
					if ( $output === 'taxonomy' || $output === 'tag' ) {
						$output = self::CONTENT_TYPE_TERMS;
					}
				}
			} else {
				$output = $cell_type_data;
			}
		}

		

		//pass only the acf repeater for now: 
			/*
			if($showDebug) {
				dmp("output");
				dmp("getCellContentType");
				dmp("cell name: " . $cellName);
				dmp("cell types: " . print_r($cell_types, true));
				dmp($output);
				exit();	
			}
			*/

	

		//pass only certain types for now, need to check each type

		switch($output) {
			case self::CONTENT_TYPE_AUTHOR:
			case self::CONTENT_TYPE_STATUS:
			case self::CONTENT_TYPE_ACF_REPEATER:
			case self::CONTENT_TYPE_TERMS:
			case self::CONTENT_TYPE_ACF_SELECT:
			case self::CONTENT_TYPE_ACF_RADIO:
			case self::CONTENT_TYPE_ACF_CHECKBOX:
				return($output);
			break;
			default:
				return(null);
			break;
		}

		
		return $null;
	}


	private static function _________IMAGE_PROMPT__________( ){}

	/**
	 * Builds the full prompt for an image column (featured image generation / edit instructions).
	 *
	 * Order: default framing line → optional column rules for this post type → user prompt text
	 * from `getPromptFromTable()` (menu string, etc.; @column refs already expanded).
	 * Appends a short “Post data” block when referenced fields exist (post_title for create by default;
	 * edit mode only includes fields explicitly @-referenced in the prompt or column rules).
	 *
	 * @param string      $prompt_text     Expanded user prompt (may be empty).
	 * @param string      $column_name     Table column key (e.g. post_image).
	 * @param string|null $post_type_key   Sanitized post type or null.
	 * @param array       $post_data       Row fields from getPostDataForKeyValuePrompt.
	 * @return string
	 */
	private static function buildPromptFromTableImageColumn( $prompt_text, $column_name, $post_type_key, $post_data, $table_data ) {

		// When true: dump the final prompt and exit (disable after debugging).
		$showDebug = false;

		$image_action = SheetsPilotFunctions::getVal( $table_data, 'image_action', 'create' );

		$ctx_action = trim( (string) SheetsPilotFunctions::getVal( $table_data, 'context_menu_action', '' ) );
		if ( class_exists( 'SheetsPilot_ImageProcessing' ) ) {
			$ratio_from_menu = SheetsPilot_ImageProcessing::parseRatioFromChangeImageRatioAction( $ctx_action );
			if ( $ratio_from_menu !== '' ) {
				$image_action = 'edit';
				$ratio_prompt = SheetsPilot_ImageProcessing::buildChangeImageRatioPrompt( $ratio_from_menu );
				if ( $ratio_prompt !== '' ) {
					return $ratio_prompt;
				}
			}
		}

		$suffix = "";
		if ( $image_action === 'edit' ) {
			$intro = "";
			$suffix = __( 'Keep everything else exactly the same. Preserve all existing elements, composition, lighting, colors, style, camera angle, background, and details. Make only the requested change.', 'sheetspilot' );
		} else {
			// Default instruction prefix for the Images API (scene/style; avoid text or watermarks in the image).
			$intro = __( 'Create a featured image for a blog post:', 'sheetspilot' ) . "\n";
		}

		$include_rules = SheetsPilotFunctions::getVal( $table_data, 'include_rules' );
		$include_rules = SheetsPilotFunctions::strtobool( $include_rules );

		if ( class_exists( 'SheetsPilot_ImageProcessing' )
			&& SheetsPilot_ImageProcessing::parseRatioFromChangeImageRatioAction( $ctx_action ) !== '' ) {
			$include_rules = false;
		}

		$prompt_text_raw = $prompt_text;

		if($image_action === 'edit') {
			$prompt_text_raw .= "\n".$suffix;
		}

		$column_rule_raw = '';
		if ( $include_rules && $post_type_key !== null && $column_name !== '' ) {
			$column_rule_raw = self::getColumnRuleTextFromTableData( $table_data, $post_type_key, $column_name );
		}

		$targets_source_text = self::buildPostDataTargetsSourceText( $prompt_text_raw, $column_rule_raw );

		$prompt_expand  = self::expandPromptColumnReferencesWithPostData( $prompt_text_raw, $post_data );
		$prompt_text    = $prompt_expand['text'];
		$inlined_fields = $prompt_expand['inlined'];

		if ( $column_rule_raw !== '' ) {
			$rule_expand    = self::expandPromptColumnReferencesWithPostData( $column_rule_raw, $post_data );
			$column_rule    = $rule_expand['text'];
			$inlined_fields = self::mergeInlinedReferenceFields( $inlined_fields, $rule_expand['inlined'] );
			$intro         .= "\n\nStyle Rules:\n" . $column_rule . "\n\n";
		}

		$addPostTitle = $image_action !== 'edit';

		$targets = self::resolvePostDataContextTargets(
			$targets_source_text,
			$inlined_fields,
			$addPostTitle
		);

		
		if ( $image_action === 'edit' ) {
			$prompt = trim( $intro . "\n" . __( 'Edit the image using these instructions:', 'sheetspilot' ) . ' ' . $prompt_text );
		} else {
			$prompt = trim( $intro . "\n Instruction for the image: " . $prompt_text );
		}

		//add post data block to prompt
		$data_for_block = self::filter_post_data_by_targets( $post_data, $targets );
		$prompt         = self::append_post_data_block( $prompt, $data_for_block );
		
		$prompt = trim( $prompt );

		if ( $showDebug ) {
			dmp("debug buildPromptFromTableImageColumn");
			dmp("column name: " . $column_name);
			dmp("post type key: " . $post_type_key);
			dmp("post data: " . print_r($post_data, true));
			dmp("prompt text: " . $prompt_text);
			dmp( $prompt );
			exit();
		}


		return $prompt;
	}

	private static function _________CELL_TYPE_PROMPTS__________( ){}

	/**
	 * Build prompt additions that depend on cell content type.
	 *
	 * @param string|null $cell_content_type Cell content type from getCellContentType.
	 * @param string|null $post_type_key     Sanitized post type or null.
	 * @param string      $column_name       Table column key.
	 * @param int|string  $post_id           Current post ID.
	 * @return string
	 */
	private static function buildCellTypePromptAdditions( $cell_content_type, $post_type_key, $column_name = '', $post_id = 0 ) {

	 

		$showDebug = false;

		$output = '';
		switch ( $cell_content_type ) {
			case self::CONTENT_TYPE_AUTHOR:
				$output = self::buildAuthorPromptAdditions( $post_type_key );
				break;
			case self::CONTENT_TYPE_STATUS:
				$output = self::buildStatusPromptAdditions( $post_type_key );
				break;
			case self::CONTENT_TYPE_ACF_REPEATER:
				$output = self::buildACFRepeaterPromptAdditions( $column_name, $post_id );
				break;
			case self::CONTENT_TYPE_ACF_SELECT:
				$output = self::buildSelectPromptAdditions( $post_type_key, $column_name );
				break;
			case self::CONTENT_TYPE_ACF_RADIO:
				$output = self::buildSelectPromptAdditions( $post_type_key, $column_name );
				break;
			case self::CONTENT_TYPE_ACF_CHECKBOX:
				$output = self::buildSelectPromptAdditions( $post_type_key, $column_name );
				break;
		}

		if($showDebug) {
			dmp("buildCellTypePromptAdditions");
			dmp("cell content type: " . $cell_content_type);
			dmp("output: " . $output);
			exit();
		}
		
		return $output;
	}

	/**
	 * Build repeater field structure instructions for ACF repeater cells.
	 *
	 * @param string $column_name Table column key, usually acf_{field_name}.
	 * @param int|string $post_id Current post ID.
	 * @return string
	 */
	private static function buildACFRepeaterPromptAdditions( $column_name, $post_id = 0 ) {
		if ( ! class_exists( 'SheetsPilotACFRepeaterProcessing' ) ) {
			$output = '';
			return $output;
		}

		$repeater_name = (string) $column_name;
		if ( strncmp( $repeater_name, 'acf_', 4 ) === 0 ) {
			$repeater_name = substr( $repeater_name, 4 );
		}
		$repeater_name = trim( $repeater_name );
		if ( $repeater_name === '' ) {
			$output = '';
			return $output;
		}

		$repeater_processing = new SheetsPilotACFRepeaterProcessing();
		$structure           = $repeater_processing->get_acf_repeater_structure( $repeater_name, (int) $post_id );
		$sub_fields          = (array) SheetsPilotFunctions::getVal( $structure, 'sub_fields', array() );
		if ( empty( $sub_fields ) ) {
			$output = '';
			return $output;
		}

		$field_lines = self::buildACFRepeaterFieldLinesForPrompt( $sub_fields );
		if ( empty( $field_lines ) ) {
			$output = '';
			return $output;
		}

		$output = "\n\nThe \"data\" array should contain the complete final list of repeater row objects. Each row object should use these fields:\n";
		$output .= implode( "\n", $field_lines );

		return $output;
	}

	/**
	 * Build a readable display_text example with two changed rows.
	 *
	 * @param array $fields Repeater subfield definitions.
	 * @return string
	 */
	private static function buildACFRepeaterDisplayTextExampleForPrompt( $fields ) {
		$field_parts = array();
		foreach ( (array) $fields as $field ) {
			$field_name = trim( (string) SheetsPilotFunctions::getVal( $field, 'name', '' ) );
			if ( $field_name === '' ) {
				continue;
			}

			$field_label = trim( (string) SheetsPilotFunctions::getVal( $field, 'label', '' ) );
			if ( $field_label === '' ) {
				$field_label = $field_name;
			}
			$field_label = str_replace( '"', "'", $field_label );

			$field_parts[] = $field_label;
		}

		if ( empty( $field_parts ) ) {
			$output = 'Row 1; Row 2.';
			return $output;
		}

		$row_examples = array();
		for ( $row_number = 1; $row_number <= 2; $row_number++ ) {
			$row_fields = array();
			foreach ( $field_parts as $field_label ) {
				$row_fields[] = $field_label . $row_number;
			}
			$row_text       = implode( ', ', $row_fields );
			$row_examples[] = $row_text;
		}

		$output = implode( '\n', $row_examples );
		return $output;
	}

	/**
	 * Format ACF repeater subfields as prompt lines.
	 *
	 * @param array $fields Repeater subfield definitions.
	 * @param int   $depth  Nesting depth.
	 * @return array
	 */
	private static function buildACFRepeaterFieldLinesForPrompt( $fields, $depth = 0 ) {
		$lines = array();
		foreach ( (array) $fields as $field ) {
			$field_name = trim( (string) SheetsPilotFunctions::getVal( $field, 'name', '' ) );
			if ( $field_name === '' ) {
				continue;
			}

			$field_label = trim( (string) SheetsPilotFunctions::getVal( $field, 'label', '' ) );
			$field_type  = trim( (string) SheetsPilotFunctions::getVal( $field, 'type', '' ) );
			$indent      = str_repeat( '  ', (int) $depth );
			$line        = $indent . '- ' . $field_name;

			$field_details = array();
			if ( $field_label !== '' ) {
				$field_details[] = 'label: ' . $field_label;
			}
			if ( $field_type !== '' ) {
				$field_details[] = 'type: ' . $field_type;
			}
			if ( ! empty( $field_details ) ) {
				$line .= ' (' . implode( ', ', $field_details ) . ')';
			}

			$lines[] = $line;

			$sub_fields = (array) SheetsPilotFunctions::getVal( $field, 'sub_fields', array() );
			if ( ! empty( $sub_fields ) ) {
				$child_lines = self::buildACFRepeaterFieldLinesForPrompt( $sub_fields, $depth + 1 );
				$lines       = array_merge( $lines, $child_lines );
			}
		}

		return $lines;
	}

	/**
	 * Get post type structure with in-request cache for reuse.
	 *
	 * @param string|null $post_type_key Sanitized post type or null.
	 * @return array
	 */
	private static function getPostTypeStructureForPrompt( $post_type_key ) {

		if ( ! class_exists( 'SheetsPilotCellEditor' ) || ! method_exists( 'SheetsPilotCellEditor', 'getPostTypeStructure' ) ) {
			$output = array();
			return $output;
		}

		$post_type_for_structure = $post_type_key;
		if ( $post_type_for_structure === null || $post_type_for_structure === '' ) {
			$post_type_for_structure = 'post';
		}

		$cache_key = (string) $post_type_for_structure;
		if ( isset( self::$postTypeStructureCache[ $cache_key ] ) ) {
			$output = (array) self::$postTypeStructureCache[ $cache_key ];
			return $output;
		}

		$post_type_structure                    = SheetsPilotCellEditor::getPostTypeStructure( $post_type_for_structure );
		self::$postTypeStructureCache[ $cache_key ] = (array) $post_type_structure;
		$output                                 = (array) self::$postTypeStructureCache[ $cache_key ];
		return $output;
	}

	/**
	 * Build author options block for prompt when editing post_author.
	 * Reuses the existing post-type structure source list and does nothing when unavailable.
	 *
	 * @param string|null $post_type_key Sanitized post type or null.
	 * @return string
	 */
	private static function buildAuthorPromptAdditions( $post_type_key ) {

		$post_type_structure = self::getPostTypeStructureForPrompt( $post_type_key );
		$author_options      = array();
		foreach ( (array) $post_type_structure as $column_definition ) {
			$column_name_value = SheetsPilotFunctions::getVal( $column_definition, 'name', '' );
			if ( $column_name_value !== 'post_author' ) {
				continue;
			}

			$author_options = (array) SheetsPilotFunctions::getVal( $column_definition, 'source', array() );
			break;
		}

		if ( empty( $author_options ) ) {
			$output = '';
			return $output;
		}

		$author_lines = array();
		foreach ( $author_options as $author_option ) {
			$author_id   = trim( (string) SheetsPilotFunctions::getVal( $author_option, 'id', '' ) );
			$author_name = trim( (string) SheetsPilotFunctions::getVal( $author_option, 'name', '' ) );
			if ( $author_id === '' || $author_name === '' ) {
				continue;
			}

			$author_lines[] = $author_id . ': ' . $author_name;
		}

		if ( empty( $author_lines ) ) {
			$output = '';
			return $output;
		}

		$output = "\n\nAllowed authors (choose one from this list and return only that author ID):\n" . implode( "\n", $author_lines );
		return $output;
	}

	/**
	 * Build author options block for prompt when editing post_author.
	 * Reuses the existing post-type structure source list and does nothing when unavailable.
	 *
	 * @param string|null $post_type_key Sanitized post type or null.
	 * @param string|null $column_name Column name or null.
	 * @return string
	 */
	private static function buildSelectPromptAdditions( $post_type_key, $column_name ) {

		$post_type_structure = self::getPostTypeStructureForPrompt( $post_type_key );
		$select_options      = array();

		$filtered_feild_name = '';

		if (substr( $column_name, 0, 4) == 'acf_') {
			$filtered_feild_name = str_replace('acf_', '', $column_name );

			$acf_extra_fields = SheetsPilotCellEditor::get_acf_fields_for_post_type( $post_type_key );
			foreach ($acf_extra_fields as $s_field_data) {
				if (in_array($s_field_data["type"], ['select', 'radio', 'checkbox'])) {
					if(  $s_field_data["name"] == $filtered_feild_name ){
						$select_options = SheetsPilotCellEditor::getSelectOptions( $filtered_feild_name );
					}
				}
			}
		}
	 
		

		if ( empty( $select_options ) ) {
			$output = '';
			return $output;
		}

		$select_lines = array();
		foreach ( $select_options as $single_option ) {
			$item_id   = sanitize_text_field( $single_option['id'] );
			$item_name = sanitize_text_field( $single_option['name'] );
			if ( $item_id === '' || $item_name === '' ) {
				continue;
			}

			$select_lines[] = $item_id . ': ' . $item_name;
		}

		if ( empty( $select_lines ) ) {
			$output = '';
			return $output;
		}
		
		$output = "\n\nAllowed options (choose one from this list and return only that Option ID):\n" . implode( "\n", $select_lines );
		return $output;
	}

	/**
	 * Build status options block for prompt when editing post_status.
	 *
	 * @param string|null $post_type_key Sanitized post type or null.
	 * @return string
	 */
	private static function buildStatusPromptAdditions( $post_type_key ) {

		$post_type_structure = self::getPostTypeStructureForPrompt( $post_type_key );
		$status_options      = array();
		foreach ( (array) $post_type_structure as $column_definition ) {
			$column_name_value = SheetsPilotFunctions::getVal( $column_definition, 'name', '' );
			if ( $column_name_value !== 'post_status' ) {
				continue;
			}

			$status_options = (array) SheetsPilotFunctions::getVal( $column_definition, 'source', array() );
			break;
		}

		if ( empty( $status_options ) ) {
			$output = '';
			return $output;
		}

		$status_lines = array();
		foreach ( $status_options as $status_option ) {
			$status_id   = trim( (string) SheetsPilotFunctions::getVal( $status_option, 'id', '' ) );
			$status_name = trim( (string) SheetsPilotFunctions::getVal( $status_option, 'name', '' ) );
			if ( $status_id === '' || $status_name === '' ) {
				continue;
			}

			$status_lines[] = $status_id . ': ' . $status_name;
		}

		if ( empty( $status_lines ) ) {
			$output = '';
			return $output;
		}

		$output = "\n\nAllowed statuses (choose one from this list and return only that status ID):\n" . implode( "\n", $status_lines );
		return $output;
	}

	private static function _________PROMPT_DISPLAY__________( ){}

	/**
	 * Extract readable display text from a prompt response.
	 *
	 * @param mixed $response_value Prompt response value.
	 * @return string
	 */
	public static function getDisplayTextFromPromptResponse( $response_value ) {
		if ( is_object( $response_value ) ) {
			$response_value = (array) $response_value;
		}

		if ( is_string( $response_value ) ) {
			$decoded = json_decode( $response_value, true );
			if ( is_array( $decoded ) ) {
				$response_value = $decoded;
			}
		}

		if ( ! is_array( $response_value ) ) {
			$output = '';
			return $output;
		}

		$display_text = SheetsPilotFunctions::getVal( $response_value, 'display_text', '' );
		$output       = SheetsPilotFunctions::toString( $display_text );
		return $output;
	}

	/**
	 * Prepare prompt display text for the replace dialog.
	 *
	 * @param mixed $display_text Display text.
	 * @return string
	 */
	public static function modifyDisplayTextForPromptDisplay( $display_text ) {
		$output = SheetsPilotFunctions::toString( $display_text );
		$output = str_replace( array( "\r\n", "\r", "\n", '\n' ), '<br>', $output );
		return $output;
	}

	/**
	 * Convert HTML AI response for post_content into plain text for display only.
	 * - Strips WordPress block comments (<!-- wp:paragraph --> etc).
	 * - Converts common block-level tags to newlines.
	 * - Removes remaining HTML tags.
	 * - Decodes common HTML entities and normalizes whitespace/newlines.
	 *
	 * Used only for the Prompt Result dialog "text to show" for content cells.
	 *
	 * @param string      $html_string       HTML string returned by AI.
	 * @param string|null $cell_content_type Cell content type.
	 * @return string Plain text suitable for preview in dialog.
	 */
	public static function get_plain_text_for_prompt_display( $html_string, $cell_content_type = null ) {

		//special case for content type

		switch ( $cell_content_type ) {
			case self::CONTENT_TYPE_AUTHOR:
				$author_display_name = SheetsPilotHelper::getAuthorDisplayNameById( $html_string );
				if ( $author_display_name !== '' ) {
					$output = $author_display_name;
					return $output;
				}
				SheetsPilotFunctions::throwError( 'Author not found.' );
				break;
			case self::CONTENT_TYPE_STATUS:
				$status_display_name = SheetsPilotHelper::getPostStatusDisplayNameById( $html_string );
				if ( $status_display_name !== '' ) {
					$output = $status_display_name;
					return $output;
				}
				SheetsPilotFunctions::throwError( 'Status not found.' );
				break;
		}


		//text output

		if(is_string($html_string) == false)
			return('');

		if(empty($html_string))
			return('');

		$allowed_html = array(
			'b' => array(),
			'ul' => array(),
			'li' => array(),
			'ol' => array(),
			'i' => array(),
			'b' => array(),
			'p' => array(),
		);
		$text = wp_kses( $html_string, $allowed_html);

		$text = is_string( $text ) ? trim( $text ) : '';

		if ( $text === '' ) {
			return '';
		}


		// Remove WordPress block comments such as <!-- wp:paragraph --> and <!-- /wp:paragraph -->.
		$text = preg_replace( '#<!--\s*\/?wp:[\s\S]*?-->#s', '', $text );

		// Convert common block-level closing tags to newlines before stripping tags.
		$block_closers = array(
			'#<\s*\/p\s*>#i',
			'#<\s*\/div\s*>#i',
			'#<\s*br\s*\/?>#i',
			'#<\s*\/li\s*>#i',
			'#<\s*\/h[1-6]\s*>#i',
		);

		$text = preg_replace( $block_closers, "\n", $text );

		// Strip all remaining HTML tags.
		$text = preg_replace( '#<[^>]+>#', '', $text );

		// Decode common entities for display. Normalize &nbsp; to a regular space first.
		$text = str_replace( '&nbsp;', ' ', $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

		// Trim each line and collapse excessive newlines (3+ -> 2).
		$lines = explode( "\n", $text );
		$lines = array_map(
			static function ( $line ) {
				$trimmed = trim( $line );
				return $trimmed;
			},
			$lines
		);

		$text = implode( "\n", $lines );
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );

		$output = trim( $text );
		return $output;
	}

	private static function _________CONTENT_RULES__________( ){}

	/**
	 * Get content rules block for system message. Only includes non-empty values.
	 * Skips contentTone when empty (Not Selected). Returns empty string if no rules.
	 *
	 * @return string Content guidelines block or empty string.
	 */
	public static function getContentRulesBlock() {
		$output = self::buildContentRulesBlock();
		return $output;
	}

	/**
	 * Build a content rules block from saved options. Only includes non-empty values.
	 *
	 * @return string Content guidelines block or empty string.
	 */
	private static function buildContentRulesBlock() {
		$output = '';
		$rules  = SheetsPilotHelper::getContentRules();
		$parts  = array();

		$tone = trim( (string) SheetsPilotFunctions::getVal( $rules, 'contentTone', '' ) );
		if ( $tone !== '' ) {
			$parts[] = 'Content tone: ' . $tone;
		}

		$lang = trim( (string) SheetsPilotFunctions::getVal( $rules, 'contentLanguage', '' ) );
		$customLang = trim( (string) SheetsPilotFunctions::getVal( $rules, 'customLanguage', '' ) );
		if ( $lang !== '' ) {

			if( $lang == 'Custom' ){
				$lang = $customLang;
			}

			$parts[] = 'Content language: ' . $lang;
		}

		$audience = trim( (string) SheetsPilotFunctions::getVal( $rules, 'targetAudience', '' ) );
		if ( $audience !== '' ) {
			$parts[] = 'Target audience: ' . $audience;
		}

		$voice = trim( (string) SheetsPilotFunctions::getVal( $rules, 'brandVoice', '' ) );
		if ( $voice !== '' ) {
			$parts[] = 'Brand voice: ' . $voice;
		}

		if ( ! empty( $parts ) ) {
			$output = 'Content guidelines: ' . implode( '. ', $parts );
		}

		return $output;
	}

	/**
	 * Combine column text with value.
	 */
	private static function getCellPromptText( $column_text, $value ) {
		$column_text = trim( (string) $column_text );
		$value      = trim( (string) $value );

		if ( $column_text === '' ) {
			return $value;
		}
		if ( $value === '' ) {
			return $column_text;
		}
		$column_display = self::strip_plugin_prefix_from_field_key( $column_text );
		$result         = '"' . $value . '" (value type: ' . $column_display . ')';
		return $result;
	}

	private static function _________POST_DATA__________( ){}

	/**
	 * Get one post's row data (same format as one row from getPostTypeArray).
	 *
	 * @param string $post_type Post type.
	 * @param int    $post_id   Post ID.
	 * @return array Row as array of single-key elements, or empty array if post not found.
	 */
	public static function getSinglePostRow( $post_type, $post_id ) {

		$showDebug = false;

		$postQueryObj = new SheetsPilotQueryProcessing();
		$postQueryObj->postType       = $post_type;
		$postQueryObj->single_post_id = (int) $post_id;

		$rows = $postQueryObj->getPostTypeArray();
		$row  = isset( $rows[0] ) ? $rows[0] : [];

		if($showDebug) {
			dmp("getSinglePostRow");
			dmp($rows);
			exit();
		}

		return $row;
	}
	
	private static function _________FLATTEN__________( ){}

	/**
	 * Parse attachment ID from the featured-image cell HTML (`data-id` on the img).
	 *
	 * @param string $html Cell HTML from the grid.
	 * @return int Attachment ID or 0.
	 */
	private static function flattenPromptPostImageAttachmentIdFromHtml( $html ) {
		if ( ! is_string( $html ) || $html === '' ) {
			return 0;
		}
		if ( preg_match( '/\bdata-id\s*=\s*["\']?(\d+)/', $html, $matches ) ) {
			$output = (int) $matches[1];
			return $output;
		}
		return 0;
	}

	/**
	 * Attachment title for prompt context, or alt text if the title is empty.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return string
	 */
	private static function flattenPromptPostImageTitleOrAlt( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		if ( $attachment_id <= 0 ) {
			return '';
		}
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
			return '';
		}
		$title = trim( (string) $attachment->post_title );
		$alt   = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		if ( $title !== '' ) {
			$output = $title;
			return $output;
		}
		if ( $alt !== '' ) {
			$output = $alt;
			return $output;
		}
		$output = '';
		return $output;
	}

	/**
	 * Flatten a table cell that uses the structured "values" / "posts" shape (non-taxonomy).
	 *
	 * @param string              $key Column key.
	 * @param array<string,mixed> $val Cell value (must have "values" key).
	 * @return string
	 */
	private static function flattenPromptCellStructuredValues( $key, $val ) {
		// Featured image: values[0] is attachment ID (if the cell is stored in structured form).
		if ( $key === 'post_image' ) {
			$attachment_id = 0;
			if ( ! empty( $val['values'][0] ) && is_numeric( $val['values'][0] ) ) {
				$attachment_id = (int) $val['values'][0];
			}
			$output = self::flattenPromptPostImageTitleOrAlt( $attachment_id );
			return $output;
		}

		// Author column: store user ID in values[0]; prompt text uses display name (fallback: raw ID).
		if ( $key === 'post_author' && ! empty( $val['values'][0] ) ) {
			$user   = get_user_by( 'id', $val['values'][0] );
			$output = $user ? $user->display_name : (string) $val['values'][0];
			return $output;
		}

		// Status column: single slug or label in values[0].
		if ( $key === 'post_status' && ! empty( $val['values'][0] ) ) {
			$output = (string) $val['values'][0];
			return $output;
		}

		// Post relation fields: posts[] holds linked rows; join their titles for context.
		if ( isset( $val['posts'] ) ) {
			$titles = [];
			foreach ( (array) $val['posts'] as $p ) {
				$titles[] = is_array( $p ) ? ( $p['post_title'] ?? '' ) : '';
			}
			$output = implode( ', ', array_filter( $titles ) );
			return $output;
		}

		// Default: any other structured cell — stringify every entry in values.
		$output = implode( ', ', array_map( 'strval', (array) $val['values'] ) );
		return $output;
	}

	/**
	 * One non-taxonomy cell → plain string for the prompt.
	 *
	 * @param string $key Column key.
	 * @param mixed  $val Raw cell value from the row.
	 * @return string
	 */
	private static function flattenPromptCellPlainString( $key, $val ) {
		if ( ! is_array( $val ) ) {
			// Featured image column is HTML with data-id; use attachment title or alt for the prompt.
			if ( $key === 'post_image' && is_string( $val ) ) {
				$attachment_id = self::flattenPromptPostImageAttachmentIdFromHtml( $val );
				$output        = self::flattenPromptPostImageTitleOrAlt( $attachment_id );
				return $output;
			}
			if ( is_string( $val ) && strpos( $val, '<' ) !== false ) {
				$val = wp_strip_all_tags( $val );
			}
			$output = self::normalizeWhitespace( (string) $val );
			return $output;
		}
		if ( isset( $val['values'] ) ) {
			$output = self::flattenPromptCellStructuredValues( $key, $val );
			return $output;
		}
		$output = wp_json_encode( $val );
		return $output;
	}

	/**
	 * Flatten one post row to key => string value for prompt context.
	 * Same columns as the table (including hidden); values as plain text.
	 *
	 * @param array  $row       One row from getPostTypeArray / getSinglePostRow.
	 * @param string $post_type Post type (for taxonomy/author resolution).
	 * @return array Associative array column_name => string value.
	 */
	public static function flattenRowForPrompt( $row, $post_type ) {

		$out        = [];
		$taxonomies = get_object_taxonomies( $post_type, 'names' );

		$post_id = null;
		foreach ( $row as $item ) {
			$k = array_key_first( $item );
			if ( $k === 'id' ) {
				$post_id = (int) $item[ $k ];
				break;
			}
		}

		foreach ( $row as $item ) {
			$key = array_key_first( $item );
			$val = $item[ $key ];
			
			if ( in_array( $key, $taxonomies, true ) ) {
				$term_names = [];
				if ( is_array( $val ) && ! empty( $val['values'] ) ) {
					foreach ( (array) $val['values'] as $term_id ) {
						$t = get_term( $term_id );
						if ( $t && ! is_wp_error( $t ) ) {
							$term_names[] = trim( $t->name );
						}
					}
				} elseif ( $post_id ) {
					$terms = wp_get_post_terms( $post_id, $key );
					if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
						foreach ( $terms as $t ) {
							$term_names[] = trim( $t->name );
						}
					}
				}
				$out[ $key ] = implode( ', ', array_filter( array_map( 'trim', $term_names ) ) );
				continue;
			}
			if ( $key === 'post_content' ) {
				$out[ $key ] = self::getPostContentValueForPrompt( $val, (int) $post_id );
				continue;
			}
			$out[ $key ] = self::flattenPromptCellPlainString( $key, $val );
		}
		return $out;
	}

	/**
	 * Collapse multiple spaces/newlines/tabs to a single space and trim.
	 * Used for post_content, post_excerpt and other text so the prompt has no extra whitespace.
	 *
	 * @param string $str Input string.
	 * @return string Normalized string.
	 */
	private static function normalizeWhitespace( $str ) {
		if ( ! is_string( $str ) ) {
			return $str;
		}
		$normalized = trim( preg_replace( '/\s+/', ' ', $str ) );
		return $normalized;
	}

	/**
	 * Get post data as key/value pairs for prompt (all table columns, from DB by post ID).
	 * Uses same fields as the table, including hidden columns.
	 *
	 * @param int $post_id Post ID.
	 * @return array Associative array column_name => string value, or empty if post not found.
	 */
	public static function getPostDataForKeyValuePrompt( $post_id ) {
		$post = get_post( (int) $post_id );
		if ( ! $post || ! $post->post_type ) {
			return [];
		}
		$row = self::getSinglePostRow( $post->post_type, $post_id );
		if ( empty( $row ) ) {
			return [];
		}

		$post_data = self::flattenRowForPrompt( $row, $post->post_type );
		
		return $post_data;
	}

	/**
	 * Get cell types keyed by column name for prompt debugging.
	 *
	 * @param int $post_id Post ID.
	 * @return array Associative array column_name => type data.
	 */
	public static function getPostFieldTypesForPrompt( $post_id ) {
		$post = get_post( (int) $post_id );
		if ( ! $post || ! $post->post_type ) {
			return [];
		}

		$structure = SheetsPilotCellEditor::getPostTypeStructure( $post->post_type );
		if ( empty( $structure ) || ! is_array( $structure ) ) {
			return [];
		}

		$cell_types = array();
		foreach ( $structure as $field ) {
			if ( empty( $field['name'] ) ) {
				continue;
			}

			$field_name = $field['name'];
			$field_type  = SheetsPilotFunctions::getVal( $field, 'type', '' );
			$acf_type    = SheetsPilotFunctions::getVal( $field, 'acf_type', '' );
			$field_title = SheetsPilotFunctions::getVal( $field, 'title', '' );

			if ( $acf_type !== '' ) {
				$cell_types[ $field_name ] = array(
					'type'     => $field_type,
					'acf_type' => $acf_type,
					'title'    => $field_title,
				);
				continue;
			}

			$cell_types[ $field_name ] = array(
				'type'  => $field_type,
				'title' => $field_title,
			);
		}

		return $cell_types;
	}

}
