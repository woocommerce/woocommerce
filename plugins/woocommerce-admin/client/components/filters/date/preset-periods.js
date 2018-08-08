/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { filter } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import SegmentedSelection from 'components/segmented-selection';
import { presetValues } from 'lib/date';

class PresetPeriods extends Component {
	render() {
		const { onSelect, period } = this.props;
		return (
			<SegmentedSelection
				options={ filter( presetValues, preset => preset.value !== 'custom' ) }
				selected={ period }
				onSelect={ onSelect }
				name="period"
				legend={ __( 'select a preset period', 'wc-admin' ) }
			/>
		);
	}
}

PresetPeriods.propTypes = {
	onSelect: PropTypes.func.isRequired,
	period: PropTypes.string,
};

export default PresetPeriods;
