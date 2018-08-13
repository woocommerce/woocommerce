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
import DateRange from 'components/calendar';
import { H, Section } from 'layout/section';
import PresetPeriods from './preset-periods';

const isMobileViewport = () => window.innerWidth < 782;

class DatePickerContent extends Component {
	constructor() {
		super();
		this.onTabSelect = this.onTabSelect.bind( this );
	}
	onTabSelect( tab ) {
		const { onUpdate, period, refreshDropdown } = this.props;

		/**
		 * If the period is `custom` and the user switches tabs to view the presets,
		 * then a preset should be selected. This logic selects the default, otherwise
		 * `custom` value for period will result in no selection.
		 */
		if ( 'period' === tab && 'custom' === period ) {
			onUpdate( { period: 'today' } );
		}

		refreshDropdown();
	}

	render() {
		const {
			period,
			compare,
			after,
			before,
			onUpdate,
			onClose,
			onSelect,
			isValidSelection,
			resetCustomValues,
			focusedInput,
			afterText,
			beforeText,
			afterError,
			beforeError,
			shortDateFormat,
		} = this.props;
		return (
			<div>
				<H className="screen-reader-text" tabIndex="0">
					{ __( 'Select date range and comparison', 'wc-admin' ) }
				</H>
				<Section component={ false }>
					<H className="woocommerce-filters-date__text">
						{ __( 'select a date range', 'wc-admin' ) }
					</H>
					<TabPanel
						tabs={ [
							{
								name: 'period',
								title: __( 'Presets', 'wc-admin' ),
								className: 'woocommerce-filters-date__tab',
							},
							{
								name: 'custom',
								title: __( 'Custom', 'wc-admin' ),
								className: 'woocommerce-filters-date__tab',
							},
						] }
						className="woocommerce-filters-date__tabs"
						activeClass="is-active"
						initialTabName={ 'custom' === period ? 'custom' : 'period' }
						onSelect={ this.onTabSelect }
					>
						{ selectedTab => (
							<Fragment>
								{ selectedTab === 'period' && (
									<PresetPeriods onSelect={ onUpdate } period={ period } />
								) }
								{ selectedTab === 'custom' && (
									<DateRange
										after={ after }
										before={ before }
										onUpdate={ onUpdate }
										invalidDays="future"
										focusedInput={ focusedInput }
										afterText={ afterText }
										beforeText={ beforeText }
										afterError={ afterError }
										beforeError={ beforeError }
										shortDateFormat={ shortDateFormat }
									/>
								) }
								<div
									className={ classnames( 'woocommerce-filters-date__content-controls', {
										'is-sticky-bottom': selectedTab === 'custom' && isMobileViewport(),
										'is-custom': selectedTab === 'custom',
									} ) }
								>
									<H className="woocommerce-filters-date__text">
										{ __( 'compare to', 'wc-admin' ) }
									</H>
									<ComparePeriods onSelect={ onUpdate } compare={ compare } />
									<div className="woocommerce-filters-date__button-group">
										{ selectedTab === 'custom' && (
											<Button
												className="woocommerce-filters-date__button"
												isDefault
												onClick={ resetCustomValues }
												disabled={ ! ( after || before ) }
											>
												{ __( 'Reset', 'wc-admin' ) }
											</Button>
										) }
										{ isValidSelection( selectedTab ) ? (
											<Button
												className="woocommerce-filters-date__button"
												onClick={ onSelect( selectedTab, onClose ) }
												isPrimary
											>
												{ __( 'Update', 'wc-admin' ) }
											</Button>
										) : (
											<Button className="woocommerce-filters-date__button" isPrimary disabled>
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
	onUpdate: PropTypes.func.isRequired,
	onClose: PropTypes.func.isRequired,
	onSelect: PropTypes.func.isRequired,
	resetCustomValues: PropTypes.func.isRequired,
	focusedInput: PropTypes.string,
	afterText: PropTypes.string,
	beforeText: PropTypes.string,
	afterError: PropTypes.string,
	beforeError: PropTypes.string,
	shortDateFormat: PropTypes.string.isRequired,
};

export default DatePickerContent;
