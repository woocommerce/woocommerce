/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { TabPanel, Button } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ComparePeriods from './compare-periods';
import { H, Section } from 'layout/section';
import PresetPeriods from './preset-periods';
import Link from 'components/link';
import { DateRange } from 'components/calendar';

class DatePickerContent extends Component {
	constructor() {
		super();
		this.onTabSelect = this.onTabSelect.bind( this );
	}
	onTabSelect( tab ) {
		const { onSelect, period } = this.props;

		/**
		 * If the period is `custom` and the user switches tabs to view the presets,
		 * then a preset should be selected. This logic selects the default, otherwise
		 * `custom` value for period will result in no selection.
		 */
		if ( 'period' === tab && 'custom' === period ) {
			onSelect( { period: 'today' } );
		}
	}

	render() {
		const {
			period,
			compare,
			after,
			before,
			onSelect,
			onClose,
			getUpdatePath,
			isValidSelection,
		} = this.props;
		return (
			<Fragment>
				<H className="screen-reader-text" tabIndex="0">
					{ __( 'Select date range and comparison', 'woo-dash' ) }
				</H>
				<Section component={ false }>
					<H className="woocommerce-date-picker__text">
						{ __( 'select a date range', 'woo-dash' ) }
					</H>
					<TabPanel
						tabs={ [
							{
								name: 'period',
								title: __( 'Presets', 'woo-dash' ),
								className: 'woocommerce-date-picker__tab',
							},
							{
								name: 'custom',
								title: __( 'Custom', 'woo-dash' ),
								className: 'woocommerce-date-picker__tab',
							},
						] }
						className="woocommerce-date-picker__tabs"
						activeClass="is-active"
						initialTabName={
							'custom' === period
								? 'custom'
								: 'period' /* Open to current tab https://github.com/WordPress/gutenberg/pull/6885 */
						}
						onSelect={ this.onTabSelect }
					>
						{ selectedTab => (
							<Fragment>
								{ selectedTab === 'period' && (
									<PresetPeriods onSelect={ onSelect } period={ period } />
								) }
								{ selectedTab === 'custom' && (
									<DateRange
										after={ after }
										before={ before }
										onSelect={ onSelect }
										inValidDays="future"
									/>
								) }
								<H className="woocommerce-date-picker__text">{ __( 'compare to', 'woo-dash' ) }</H>
								<ComparePeriods onSelect={ onSelect } compare={ compare } />
								{ isValidSelection( selectedTab ) ? (
									<Link
										className="woocommerce-date-picker__update-btn components-button is-button is-primary"
										to={ getUpdatePath( selectedTab ) }
										onClick={ onClose }
									>
										{ __( 'Update', 'woo-dash' ) }
									</Link>
								) : (
									<Button className="woocommerce-date-picker__update-btn" isPrimary disabled>
										{ __( 'Update', 'woo-dash' ) }
									</Button>
								) }
							</Fragment>
						) }
					</TabPanel>
				</Section>
			</Fragment>
		);
	}
}

DatePickerContent.propTypes = {
	period: PropTypes.string.isRequired,
	compare: PropTypes.string.isRequired,
	onSelect: PropTypes.func.isRequired,
	onClose: PropTypes.func.isRequired,
	getUpdatePath: PropTypes.func.isRequired,
};

export default DatePickerContent;
