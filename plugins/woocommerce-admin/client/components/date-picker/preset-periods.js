/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SegmentedSelection from 'components/segmented-selection';

class PresetPeriods extends Component {
	render() {
		const { onSelect, period } = this.props;
		return (
			<SegmentedSelection
				options={ [
					{ value: 'today', label: __( 'Today', 'woo-dash' ) },
					{ value: 'yesterday', label: __( 'Yesterday', 'woo-dash' ) },
					{ value: 'week', label: __( 'Week to Date', 'woo-dash' ) },
					{ value: 'last_week', label: __( 'Last Week', 'woo-dash' ) },
					{ value: 'month', label: __( 'Month to Date', 'woo-dash' ) },
					{ value: 'last_month', label: __( 'Last Month', 'woo-dash' ) },
					{ value: 'quarter', label: __( 'Quarter to Date', 'woo-dash' ) },
					{ value: 'last_quarter', label: __( 'Last Quarter', 'woo-dash' ) },
					{ value: 'year', label: __( 'Year to Date', 'woo-dash' ) },
					{ value: 'last_year', label: __( 'Last Year', 'woo-dash' ) },
				] }
				selected={ period }
				onSelect={ onSelect }
				name="period"
				legend={ __( 'select a preset period', 'woo-dash' ) }
			/>
		);
	}
}

export default PresetPeriods;
