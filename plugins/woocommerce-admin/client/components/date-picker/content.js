/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { TabPanel, Button } from '@wordpress/components';
import PropTypes from 'prop-types';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import ComparePeriods from './compare-periods';
import { H, Section } from 'layout/section';
import PresetPeriods from './preset-periods';
import Link from 'components/link';
import { DateRange } from 'components/calendar';

const isMobileViewport = () => window.innerWidth < 782;

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
			resetCustomValues,
		} = this.props;
		return (
			<div>
				<H className="screen-reader-text" tabIndex="0">
					{ __( 'Select date range and comparison', 'wc-admin' ) }
				</H>
				<Section component={ false }>
					<H className="woocommerce-date-picker__text">
						{ __( 'select a date range', 'wc-admin' ) }
					</H>
					<TabPanel
						tabs={ [
							{
								name: 'period',
								title: __( 'Presets', 'wc-admin' ),
								className: 'woocommerce-date-picker__tab',
							},
							{
								name: 'custom',
								title: __( 'Custom', 'wc-admin' ),
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
								<div
									className={ classnames( 'woocommerce-date-picker__content-controls', {
										'is-sticky-bottom': selectedTab === 'custom' && isMobileViewport(),
										'is-custom': selectedTab === 'custom',
									} ) }
								>
									<H className="woocommerce-date-picker__text">
										{ __( 'compare to', 'wc-admin' ) }
									</H>
									<ComparePeriods onSelect={ onSelect } compare={ compare } />
									<div className="woocommerce-date-picker__content-controls-btns">
										{ selectedTab === 'custom' && (
											<Button
												className="woocommerce-date-picker__content-controls-btn"
												isPrimary
												onClick={ resetCustomValues }
												disabled={ ! ( after || before ) }
											>
												{ __( 'Reset', 'wc-admin' ) }
											</Button>
										) }
										{ isValidSelection( selectedTab ) ? (
											<Link
												/* eslint-disable max-len */
												className="woocommerce-date-picker__content-controls-btn components-button is-button is-primary"
												/* eslint-enable max-len */
												href={ getUpdatePath( selectedTab ) }
												onClick={ onClose }
											>
												{ __( 'Update', 'wc-admin' ) }
											</Link>
										) : (
											<Button
												className="woocommerce-date-picker__content-controls-btn"
												isPrimary
												disabled
											>
												{ __( 'Update', 'wc-admin' ) }
											</Button>
										) }
									</div>
								</div>
							</Fragment>
						) }
					</TabPanel>
				</Section>
			</div>
		);
	}
}

DatePickerContent.propTypes = {
	period: PropTypes.string.isRequired,
	compare: PropTypes.string.isRequired,
	onSelect: PropTypes.func.isRequired,
	onClose: PropTypes.func.isRequired,
	getUpdatePath: PropTypes.func.isRequired,
	resetCustomValues: PropTypes.func.isRequired,
};

export default DatePickerContent;
