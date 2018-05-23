/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Button, TabPanel } from '@wordpress/components';
import { partial } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';
import SegmentedSelection from 'components/segmented-selection';
import PresetPeriods from './preset-periods';

class DatePicker extends Component {
	constructor( props ) {
		super( props );
		/**
		 * Getting initial data from props here, but they will come from the url
		 * Defaults can be added here
		 */
		const { period, compare, start, end } = props;
		const state = {
			period,
			compare: compare,
		};
		if ( 'custom' === period ) {
			Object.assign( state, { start, end } );
		}
		this.state = state;
		this.update = this.update.bind( this );
		this.select = this.select.bind( this );
	}

	select( key, value ) {
		this.setState( { [ key ]: value } );
	}

	update( selectedTab ) {
		const data = {
			period: 'custom' === selectedTab ? 'custom' : this.state.period,
			compare: this.state.compare,
		};
		// This would be set as a result of the custom date picker, placing here as an example
		if ( 'custom' === selectedTab ) {
			Object.assign( data, { start: 'a date', end: 'another date' } );
		}
		console.log( data );
	}

	render() {
		const { period, compare } = this.state;
		const compareToString = __( 'compare to', 'woo-dash' );
		return (
			<div className="woo-dash__datepicker">
				<h3 className="woo-dash__datepicker-text">{ __( 'select a date range', 'woo-dash' ) }</h3>
				<TabPanel
					tabs={ [
						{
							name: 'period',
							title: __( 'Presets', 'woo-dash' ),
							className: 'woo-dash__datepicker-tab',
						},
						{
							name: 'custom',
							title: __( 'Custom', 'woo-dash' ),
							className: 'woo-dash__datepicker-tab',
						},
					] }
					className="woo-dash__datepicker-tabs"
					activeClass="is-active"
				>
					{ selectedTab => (
						<Fragment>
							{ selectedTab === 'period' && (
								<PresetPeriods onSelect={ this.select } period={ period } />
							) }
							{ selectedTab === 'custom' && <div>Custom Date Picker Goes here</div> }
							<h3 className="woo-dash__datepicker-text">{ compareToString }</h3>
							<SegmentedSelection
								options={ [
									{ value: 'previous_period', label: __( 'Previous Period', 'woo-dash' ) },
									{ value: 'previous_year', label: __( 'Previous Year', 'woo-dash' ) },
								] }
								selected={ compare }
								onSelect={ this.select }
								name="compare"
								legend={ compareToString }
							/>
							<Button
								className="woo-dash__datepicker-update-btn"
								onClick={ partial( this.update, selectedTab ) }
							>
								{ __( 'Update', 'woo-dash' ) }
							</Button>
						</Fragment>
					) }
				</TabPanel>
			</div>
		);
	}
}

export default DatePicker;
