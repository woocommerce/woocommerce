/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createElement,
	Component,
	createRef,
	Fragment,
} from '@wordpress/element';
import { TabPanel, Button } from '@wordpress/components';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import moment from 'moment';

/**
 * Internal dependencies
 */
import ComparePeriods from './compare-periods';
import DateRange from '../calendar/date-range';
import { H, Section } from '../section';
import PresetPeriods from './preset-periods';

class DatePickerContent extends Component {
	constructor() {
		super();
		this.onTabSelect = this.onTabSelect.bind( this );
		this.controlsRef = createRef();
	}
	onTabSelect( tab ) {
		const { onUpdate, period } = this.props;

		/**
		 * If the period is `custom` and the user switches tabs to view the presets,
		 * then a preset should be selected. This logic selects the default, otherwise
		 * `custom` value for period will result in no selection.
		 */
		if ( tab === 'period' && period === 'custom' ) {
			onUpdate( { period: 'today' } );
		}
	}

	isFutureDate( dateString ) {
		return moment().isBefore( moment( dateString ), 'day' );
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
					{ __( 'Select date range and comparison', 'woocommerce' ) }
				</H>
				<Section component={ false }>
					<H className="woocommerce-filters-date__text">
						{ __( 'select a date range', 'woocommerce' ) }
					</H>
					<TabPanel
						tabs={ [
							{
								name: 'period',
								title: __( 'Presets', 'woocommerce' ),
								className: 'woocommerce-filters-date__tab',
							},
							{
								name: 'custom',
								title: __( 'Custom', 'woocommerce' ),
								className: 'woocommerce-filters-date__tab',
							},
						] }
						className="woocommerce-filters-date__tabs"
						activeClass="is-active"
						initialTabName={
							period === 'custom' ? 'custom' : 'period'
						}
						onSelect={ this.onTabSelect }
					>
						{ ( selected ) => (
							<Fragment>
								{ selected.name === 'period' && (
									<PresetPeriods
										onSelect={ onUpdate }
										period={ period }
									/>
								) }
								{ selected.name === 'custom' && (
									<DateRange
										after={ after }
										before={ before }
										onUpdate={ onUpdate }
										isInvalidDate={ this.isFutureDate }
										focusedInput={ focusedInput }
										afterText={ afterText }
										beforeText={ beforeText }
										afterError={ afterError }
										beforeError={ beforeError }
										shortDateFormat={ shortDateFormat }
										losesFocusTo={
											this.controlsRef.current
										}
									/>
								) }
								<div
									className={ classnames(
										'woocommerce-filters-date__content-controls',
										{
											'is-custom':
												selected.name === 'custom',
										}
									) }
									ref={ this.controlsRef }
								>
									<H className="woocommerce-filters-date__text">
										{ __( 'compare to', 'woocommerce' ) }
									</H>
									<ComparePeriods
										onSelect={ onUpdate }
										compare={ compare }
									/>
									<div className="woocommerce-filters-date__button-group">
										{ selected.name === 'custom' && (
											<Button
												className="woocommerce-filters-date__button"
												isSecondary
												onClick={ resetCustomValues }
												disabled={
													! ( after || before )
												}
											>
												{ __( 'Reset', 'woocommerce' ) }
											</Button>
										) }
										{ isValidSelection( selected.name ) ? (
											<Button
												className="woocommerce-filters-date__button"
												onClick={ onSelect(
													selected.name,
													onClose
												) }
												isPrimary
											>
												{ __(
													'Update',
													'woocommerce'
												) }
											</Button>
										) : (
											<Button
												className="woocommerce-filters-date__button"
												isPrimary
												disabled
											>
												{ __(
													'Update',
													'woocommerce'
												) }
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
