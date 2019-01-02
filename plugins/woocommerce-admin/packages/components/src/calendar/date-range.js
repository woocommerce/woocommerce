/** @format */
/**
 * External dependencies
 */
import 'core-js/fn/object/assign';
import 'core-js/fn/array/from';
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { DayPickerRangeController } from 'react-dates';
import moment from 'moment';
import { partial } from 'lodash';
import PropTypes from 'prop-types';
import { withViewportMatch } from '@wordpress/viewport';

/**
 * WooCommerce dependencies
 */
import { validateDateInputForRange } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import DateInput from './input';
import phrases from './phrases';
import { getOutsideRange } from './utils';

/**
 * This is wrapper for a [react-dates](https://github.com/airbnb/react-dates) powered calendar.
 */
class DateRange extends Component {
	constructor( props ) {
		super( props );

		this.onDatesChange = this.onDatesChange.bind( this );
		this.onFocusChange = this.onFocusChange.bind( this );
		this.onInputChange = this.onInputChange.bind( this );
	}

	onDatesChange( { startDate, endDate } ) {
		const { onUpdate, shortDateFormat } = this.props;
		onUpdate( {
			after: startDate,
			before: endDate,
			afterText: startDate ? startDate.format( shortDateFormat ) : '',
			beforeText: endDate ? endDate.format( shortDateFormat ) : '',
			afterError: null,
			beforeError: null,
		} );
	}

	onFocusChange( focusedInput ) {
		this.props.onUpdate( {
			focusedInput: ! focusedInput ? 'startDate' : focusedInput,
		} );
	}

	onInputChange( input, event ) {
		const value = event.target.value;
		const { after, before, shortDateFormat } = this.props;
		const { date, error } = validateDateInputForRange(
			input,
			value,
			before,
			after,
			shortDateFormat
		);
		this.props.onUpdate( {
			[ input ]: date,
			[ input + 'Text' ]: value,
			[ input + 'Error' ]: value.length > 0 ? error : null,
		} );
	}

	setTnitialVisibleMonth( isDoubleCalendar, before ) {
		return () => {
			const visibleDate = before || moment();
			if ( isDoubleCalendar ) {
				return visibleDate.clone().subtract( 1, 'month' );
			}
			return visibleDate;
		};
	}

	render() {
		const {
			after,
			before,
			focusedInput,
			afterText,
			beforeText,
			afterError,
			beforeError,
			shortDateFormat,
			isViewportMobile,
			isViewportSmall,
			invalidDays,
		} = this.props;
		const isOutsideRange = getOutsideRange( invalidDays );
		const isDoubleCalendar = isViewportMobile && ! isViewportSmall;
		return (
			<div
				className={ classnames( 'woocommerce-calendar', {
					'is-mobile': isViewportMobile,
				} ) }
			>
				<div className="woocommerce-calendar__inputs">
					<DateInput
						value={ afterText }
						onChange={ partial( this.onInputChange, 'after' ) }
						dateFormat={ shortDateFormat }
						label={ __( 'Start Date', 'wc-admin' ) }
						error={ afterError }
						describedBy={ sprintf(
							__(
								"Date input describing a selected date range's start date in format %s",
								'wc-admin'
							),
							shortDateFormat
						) }
					/>
					<div className="woocommerce-calendar__inputs-to">{ __( 'to', 'wc-admin' ) }</div>
					<DateInput
						value={ beforeText }
						onChange={ partial( this.onInputChange, 'before' ) }
						dateFormat={ shortDateFormat }
						label={ __( 'End Date', 'wc-admin' ) }
						error={ beforeError }
						describedBy={ sprintf(
							__(
								"Date input describing a selected date range's end date in format %s",
								'wc-admin'
							),
							shortDateFormat
						) }
					/>
				</div>
				<div className="woocommerce-calendar__react-dates">
					<DayPickerRangeController
						onDatesChange={ this.onDatesChange }
						onFocusChange={ this.onFocusChange }
						focusedInput={ focusedInput }
						startDate={ after }
						endDate={ before }
						orientation={ 'horizontal' }
						numberOfMonths={ isDoubleCalendar ? 2 : 1 }
						isOutsideRange={ isOutsideRange }
						minimumNights={ 0 }
						hideKeyboardShortcutsPanel
						noBorder
						initialVisibleMonth={ this.setTnitialVisibleMonth( isDoubleCalendar, before ) }
						phrases={ phrases }
						firstDayOfWeek={ Number( wcSettings.date.dow ) }
					/>
				</div>
			</div>
		);
	}
}

DateRange.propTypes = {
	/**
	 * A moment date object representing the selected start. `null` for no selection.
	 */
	after: PropTypes.object,
	/**
	 * A string error message, shown to the user.
	 */
	afterError: PropTypes.string,
	/**
	 * The start date in human-readable format. Displayed in the text input.
	 */
	afterText: PropTypes.string,
	/**
	 * A moment date object representing the selected end. `null` for no selection.
	 */
	before: PropTypes.object,
	/**
	 * A string error message, shown to the user.
	 */
	beforeError: PropTypes.string,
	/**
	 * The end date in human-readable format. Displayed in the text input.
	 */
	beforeText: PropTypes.string,
	/**
	 * String identifying which is the currently focused input (start or end).
	 */
	focusedInput: PropTypes.string,
	/**
	 * Optionally invalidate certain days. `past`, `future`, `none`, or function are accepted.
	 * A function will be passed to react-dates' `isOutsideRange` prop
	 */
	invalidDays: PropTypes.oneOfType( [
		PropTypes.oneOf( [ 'past', 'future', 'none' ] ),
		PropTypes.func,
	] ),
	/**
	 * A function called upon selection of a date.
	 */
	onUpdate: PropTypes.func.isRequired,
	/**
	 * The date format in moment.js-style tokens.
	 */
	shortDateFormat: PropTypes.string.isRequired,
};

export default withViewportMatch( {
	isViewportMobile: '< medium',
	isViewportSmall: '< small',
} )( DateRange );
