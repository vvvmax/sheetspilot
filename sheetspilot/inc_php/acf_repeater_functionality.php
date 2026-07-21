<?php
/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) exit;
if (!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilotACFRepeaterProcessing
{


	/**
	 * Получить структуру repeater по имени
	 */
	function get_acf_repeater_structure($repeater_name, $post_id = null)
	{

		$field_groups = acf_get_field_groups(
			$post_id ? ['post_id' => $post_id] : []
		);

		foreach ($field_groups as $group) {

			$fields = acf_get_fields($group['key']);

			$found = $this->find_repeater_field($fields, $repeater_name);

			if ($found) {
				return $this->normalize_acf_fields([$found])[0];
			}
		}

		return null;
	}


	/**
	 * Рекурсивный поиск repeater
	 */
	function find_repeater_field($fields, $repeater_name)
	{

		foreach ($fields as $field) {

			if ($field['type'] === 'repeater' && $field['name'] === $repeater_name) {
				return $field;
			}

			if (!empty($field['sub_fields'])) {
				$found = $this->find_repeater_field($field['sub_fields'], $repeater_name);
				if ($found) return $found;
			}

			if (!empty($field['layouts'])) {
				foreach ($field['layouts'] as $layout) {
					$found = $this->find_repeater_field($layout['sub_fields'], $repeater_name);
					if ($found) return $found;
				}
			}
		}

		return null;
	}


	/**
	 * Нормализация структуры + генерация ID
	 */
	function normalize_acf_fields($fields, $parent_path = '')
	{

		$result = [];

		foreach ($fields as $index => $field) {

			$id = $this->generate_field_id($field, $parent_path, $index);

			$item = [
				'id'    => $id, 
				'key'   => $field['key'],
				'name'  => $field['name'],
				'label' => $field['label'],
				'type'  => $field['type'],
			];
			// если это select / radio / checkbox — добавляем choices
			if (in_array($field['type'], ['select', 'radio', 'checkbox'])) {
				$item['choices'] = isset($field['choices']) ? $field['choices'] : [];

				// полезно: multiple для select
				if ($field['type'] === 'select') {
					$item['multiple'] = !empty($field['multiple']);
				}
			}

			// new root
			$current_path = $parent_path . '/' . $field['name'];

			// repeater / group
			if (!empty($field['sub_fields'])) {
				$item['sub_fields'] = $this->normalize_acf_fields(
					$field['sub_fields'],
					$current_path
				);
			}

			// flexible content
			if (!empty($field['layouts'])) {
				$item['layouts'] = [];

				foreach ($field['layouts'] as $layout_index => $layout) {

					$layout_path = $current_path . '/layout_' . $layout['name'];

					$item['layouts'][] = [
						'layout_id'    => md5($layout_path), // 🔥 ID для layout
						'layout_key'   => $layout['key'],
						'layout_name'  => $layout['name'],
						'layout_label' => $layout['label'],
						'sub_fields'   => $this->normalize_acf_fields(
							$layout['sub_fields'],
							$layout_path
						),
					];
				}
			}

			$result[] = $item;
		}

		return $result;
	}


	/**
	 * Генерация уникального ID поля
	 */
	function generate_field_id($field, $parent_path, $index)
	{

		// можно менять стратегию ID тут
		$base = $parent_path . '|' . $field['name'] . '|' . $field['key'] . '|' . $index;

		return md5($base);
	}


	function get_acf_repeater_values_clear($repeater_name, $post_id){
		
		$data = get_field($repeater_name, $post_id);

		if (!$data || !is_array($data)) {
			return [];
		}

		return($data);
	}



	function get_acf_repeater_values($repeater_name, $post_id)
	{

		$data = $this->get_acf_repeater_values_clear($repeater_name, $post_id);

		return $this->val_normalize_acf_values($data);
	}


	/**
	 * Рекурсивная нормализация значений
	 */
	function val_normalize_acf_values($data)
	{

		$result = [];

		foreach ($data as $row_index => $row) {

			$row_result = [
				'row_index' => $row_index,
				'fields' => []
			];

			foreach ($row as $key => $value) {

				// пропускаем служебное
				if ($key === 'acf_fc_layout') {
					$row_result['layout'] = $value;
					continue;
				}

				// если вложенный массив — идём глубже
				if (is_array($value)) {

					// проверяем: это массив строк (repeater) или просто массив значений
					if (isset($value[0]) && is_array($value[0])) {

						// это вложенный repeater / flexible
						$row_result['fields'][$key] = $this->val_normalize_acf_values($value);
					} else {

						// это group или просто массив
						$row_result['fields'][$key] = $this->val_normalize_group_values($value);
					}
				} else {

					// простое поле
					$row_result['fields'][$key] = $value;
				}
			}

			$result[] = $row_result;
		}

		return $result;
	}


	/**
	 * Для group (ассоциативный массив)
	 */
	function val_normalize_group_values($data)
	{

		$result = [];

		foreach ($data as $key => $value) {

			if (is_array($value)) {

				if (isset($value[0]) && is_array($value[0])) {
					$result[$key] = $this->val_normalize_acf_values($value);
				} else {
					$result[$key] = $this->val_normalize_group_values($value);
				}
			} else {
				$result[$key] = $value;
			}
		}

		return $result;
	}
}
