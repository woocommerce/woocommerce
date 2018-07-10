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

const presetValues = [
	{ value: 'today', label: __( 'Today', 'wc-admin' ) },
	{ value: 'yesterday', label: __( 'Yesterday', 'wc-admin' ) },
	{ value: 'week', label: __( 'Week to Date', 'wc-admin' ) },
	{ value: 'last_week', label: __( 'Last Week', 'wc-admin' ) },
	{ value: 'month', label: __( 'Month to Date', 'wc-admin' ) },
	{ value: 'last_month', label: __( 'Last Month', 'wc-admin' ) },
	{ value: 'quarter', label: __( 'Quarter to Date', 'wc-admin' ) },
	{ value: 'last_quarter', label: __( 'Last Quarter', 'wc-admin' ) },
	{ value: 'year', label: __( 'Year to Date', 'wc-admin' ) },
	{ value: 'last_year', label: __( 'Last Year', 'wc-admin' ) },
	{ value: 'custom', label: __( 'Custom', 'wc-admin' ) },
];

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

export { presetValues };
export default PresetPeriods;
