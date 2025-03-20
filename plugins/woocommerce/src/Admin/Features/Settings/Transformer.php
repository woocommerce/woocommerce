<?php
/**
 * WooCommerce Settings Data Transformer.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\Settings;

/**
 * Transforms WooCommerce settings data into a structured format with logical groupings.
 */
class Transformer {
	/**
	 * Current group being processed.
	 *
	 * @var array|null
	 */
	private ?array $current_group = null;

	/**
	 * Current checkbox group being processed.
	 *
	 * @var array|null
	 */
	private ?array $current_checkbox_group = null;

	/**
	 * Transform settings data.
	 *
	 * @param array $raw_settings Raw settings data.
	 *
	 * @return array Transformed settings data.
	 */
	public function transform( array $raw_settings ): array {
		$transformed = array();

		foreach ( $raw_settings as $tab_id => $tab ) {
			// If the tab doesn't have sections, or the sections aren't an array, skip it.
			if ( ! isset( $tab['sections'] ) || ! is_array( $tab['sections'] ) ) {
				$transformed[ $tab_id ] = $tab;
				continue;
			}

			$transformed[ $tab_id ]             = $tab;
			$transformed[ $tab_id ]['sections'] = $this->transform_sections( $tab['sections'] );
		}

		return $transformed;
	}

	/**
	 * Transform sections within a tab.
	 *
	 * @param array $sections Sections to transform.
	 *
	 * @return array Transformed sections.
	 */
	private function transform_sections( array $sections ): array {
		$transformed_sections = array();

		foreach ( $sections as $section_id => $section ) {
			// If the section doesn't have settings, or the settings aren't an array, skip it.
			if ( ! isset( $section['settings'] ) || ! is_array( $section['settings'] ) ) {
				$transformed_sections[ $section_id ] = $section;
				continue;
			}

			$transformed_sections[ $section_id ]             = $section;
			$transformed_sections[ $section_id ]['settings'] = $this->transform_section_settings( $section['settings'] );
		}

		return $transformed_sections;
	}

	/**
	 * Transform settings within a section.
	 *
	 * @param array $settings Settings to transform.
	 *
	 * @return array Transformed settings.
	 */
	private function transform_section_settings( array $settings ): array {
		$this->reset_state();
		$transformed_settings = array();

		foreach ( $settings as $setting ) {
			$this->process_setting( $setting, $transformed_settings );
		}
		$this->finalize_transformation( $transformed_settings );

		return $transformed_settings;
	}

	/**
	 * Process individual setting.
	 *
	 * @param array $setting Setting to process.
	 * @param array $transformed_settings Transformed settings array.
	 */
	private function process_setting( ?array $setting, array &$transformed_settings ): void {
		if ( ! isset( $setting ) ) {
			return;
		}

		$type = $setting['type'] ?? '';

		if ( $this->current_checkbox_group && 'checkbox' !== $type ) {
			// It's expected that a checkbox group will always be closed before a non-checkbox setting.
			// If not, it's likely a checkbox group was not closed properly so we flush the current checkbox group and add the setting as-is.
			$this->flush_current_checkbox_group();
		}

		switch ( $type ) {
			case 'title':
				$this->handle_group_start( $setting, $transformed_settings );
				break;

			case 'sectionend':
				$this->handle_group_end( $setting, $transformed_settings );
				break;

			case 'checkbox':
				$this->handle_checkbox_setting( $setting, $transformed_settings );
				break;

			case 'info':
				if ( ! empty( $setting['text'] ) ) {
					$setting['text'] = wp_kses_post( wpautop( wptexturize( $setting['text'] ) ) );
				}
				if ( ! empty( $setting['row_class'] ) && substr( $setting['row_class'], 0, 16 ) !== 'wc-settings-row-' ) {
					$setting['row_class'] = 'wc-settings-row-' . $setting['row_class'];
				}

				$this->add_setting( $setting, $transformed_settings );
				break;

			default:
				$this->add_setting( $setting, $transformed_settings );
				break;
		}
	}

	/**
	 * Handle the start of a new group.
	 *
	 * @param array $setting Setting to add.
	 * @param array $transformed_settings Transformed settings array.
	 */
	private function handle_group_start( array $setting, array &$transformed_settings ): void {
		// If we already have a group, flush it to settings before starting a new one.
		if ( $this->current_group ) {
			$this->flush_current_group( $transformed_settings );
		}

		$this->current_group = array( $setting );
	}

	/**
	 * Handle the end of a group.
	 *
	 * @param array $setting Setting to add.
	 * @param array $transformed_settings Transformed settings array.
	 */
	private function handle_group_end( array $setting, array &$transformed_settings ): void {
		$ids_match = $this->current_group &&
			isset( $this->current_group[0]['id'] ) &&
			isset( $setting['id'] ) &&
			$this->current_group[0]['id'] === $setting['id'];

		$ids_match_undefined = $this->current_group &&
			! isset( $this->current_group[0]['id'] ) &&
			! isset( $setting['id'] );

		// If IDs match, add the group and close it.
		if ( $ids_match || $ids_match_undefined ) {
			// Compose the group setting.
			$title_setting       = array_shift( $this->current_group );
			$title_setting['id'] = $title_setting['id'] ?? wp_unique_prefixed_id( 'setting_group' );

			$transformed_settings[] = array_merge(
				$title_setting,
				array(
					'type'     => 'group',
					'settings' => $this->current_group,
				)
			);
			$this->current_group    = null;
			return;
		}

		// If IDs don't match, we don't need to transform anything so flush the current group.
		$this->flush_current_group( $transformed_settings );
		$this->add_setting( $setting, $transformed_settings );
	}

	/**
	 * Flush current group to transformed settings.
	 *
	 * @param array $transformed_settings Transformed settings array.
	 */
	private function flush_current_group( array &$transformed_settings ): void {
		if ( is_array( $this->current_group ) && ! empty( $this->current_group ) ) {
			$this->current_group[0]['id'] = $this->current_group[0]['id'] ?? wp_unique_prefixed_id( 'setting_title' );
			$transformed_settings         = array_merge( $transformed_settings, $this->current_group );
		}

		$this->current_group = null;
	}

	/**
	 * Handle checkbox setting and grouping.
	 *
	 * @param array $setting Setting to add.
	 * @param array $transformed_settings Transformed settings array.
	 */
	private function handle_checkbox_setting( array $setting, array &$transformed_settings ): void {
		$checkboxgroup = $setting['checkboxgroup'] ?? '';

		switch ( $checkboxgroup ) {
			case 'start':
				$this->start_checkbox_group( $setting );
				break;

			case 'end':
				$this->end_checkbox_group( $setting, $transformed_settings );
				break;

			default:
				$this->handle_checkbox_group_item( $setting, $transformed_settings );
				break;
		}
	}

	/**
	 * Start a new checkbox group.
	 *
	 * @param array $setting Setting to add.
	 */
	private function start_checkbox_group( array $setting ): void {
		// If we already have an open checkbox group, flush it to settings before starting a new one.
		if ( is_array( $this->current_checkbox_group ) ) {
			$this->flush_current_checkbox_group();
		}

		$this->current_checkbox_group = array( $setting );
	}

	/**
	 * End current checkbox group.
	 *
	 * @param array $setting Setting to add.
	 * @param array $transformed_settings Transformed settings array.
	 */
	private function end_checkbox_group( array $setting, array &$transformed_settings ): void {
		if ( empty( $this->current_checkbox_group ) ) {
			// If we don't have an open checkbox group, add the setting as-is.
			$this->add_setting( $setting, $transformed_settings );
			return;
		}

		$this->current_checkbox_group[] = $setting;
		$first_setting                  = $this->current_checkbox_group[0];

		$checkbox_group_setting = array(
			'id'       => wp_unique_prefixed_id( 'setting_checkboxgroup' ),
			'type'     => 'checkboxgroup',
			'title'    => $first_setting['title'] ?? '',
			'settings' => $this->current_checkbox_group,
		);

		$this->add_setting( $checkbox_group_setting, $transformed_settings );
		$this->current_checkbox_group = null;
	}

	/**
	 * Handle checkbox within a group.
	 *
	 * @param array $setting Setting to add.
	 * @param array $transformed_settings Transformed settings array.
	 */
	private function handle_checkbox_group_item( array $setting, array &$transformed_settings ): void {
		if ( is_array( $this->current_checkbox_group ) ) {
			$this->current_checkbox_group[] = $setting;
			return;
		}

		// If we don't have an open checkbox group, add the setting as-is.
		$this->add_setting( $setting, $transformed_settings );
	}

	/**
	 * Flush current checkbox group to transformed settings.
	 */
	private function flush_current_checkbox_group(): void {
		if ( is_array( $this->current_checkbox_group ) ) {
			if ( is_array( $this->current_group ) ) {
				$this->current_group = array_merge( $this->current_group, $this->current_checkbox_group );
			} else {
				$this->current_group = $this->current_checkbox_group;
			}

			$this->current_checkbox_group = null;
		}
	}

	/**
	 * Add setting to current context (group or root).
	 *
	 * @param array $setting Setting to add.
	 * @param array $transformed_settings Transformed settings array.
	 */
	private function add_setting( array $setting, array &$transformed_settings ): void {
		$setting['id'] = $setting['id'] ?? wp_unique_prefixed_id( 'setting_field' );

		if ( is_array( $this->current_group ) ) {
			$this->current_group[] = $setting;
			return;
		}

		$transformed_settings[] = $setting;
	}

	/**
	 * Finalize the transformation process.
	 *
	 * @param array &$transformed_settings Transformed settings array.
	 */
	private function finalize_transformation( array &$transformed_settings ): void {
		$this->flush_current_checkbox_group();
		$this->flush_current_group( $transformed_settings );
	}

	/**
	 * Reset the state to its initial values.
	 */
	public function reset_state(): void {
		$this->current_group          = null;
		$this->current_checkbox_group = null;
	}
}
