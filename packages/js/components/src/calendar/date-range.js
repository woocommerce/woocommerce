/**
 * External dependencies
 */
import 'core-js/features/object/assign';
import 'core-js/features/array/from';
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import { createElement, Component, createRef } from '@wordpress/element';
import { DayPickerRangeController } from 'react-dates';
import moment from 'moment';
import { partial } from 'lodash';
import PropTypes from 'prop-types';
import { withViewportMatch } from '@wordpress/viewport';

import { validateDateInputForRange } from '@woocommerce/date';
import 'react-dates/initialize';
// ^^ The above: Turn on react-dates classes/styles, see https://github.com/airbnb/react-dates#initialize.

/**
 * Internal dependencies
 */
import DateInput from './input';
import phrases from './phrases';

const isRTL = () => document.documentElement.dir === 'rtl';
// Blur event sources
const CONTAINER_DIV = 'container';
const NEXT_MONTH_CLICK = 'onNextMonthClick';
const PREV_MONTH_CLICK = 'onPrevMonthClick';

/**
 * This is wrapper for a [react-dates](https://github.com/airbnb/react-dates) powered calendar.
 */
class DateRange extends Component {
	constructor( props ) {
		super( props );

		this.onDatesChange = this.onDatesChange.bind( this );
		this.onFocusChange = this.onFocusChange.bind( this );
		this.onInputChange = this.onInputChange.bind( this );
		this.nodeRef = createRef();
		this.keepFocusInside = this.keepFocusInside.bind( this );
	}

	/*
	 * Todo: We should remove this function when possible.
	 * It is kept because focus is lost when we click on the previous and next
	 * month buttons or clicking on a date in the calendar.
	 * This focus loss closes the date picker popover.
	 * Ideally we should add an upstream commit on react-dates to fix this issue.
	 *
	 * See: https://github.com/WordPress/gutenberg/pull/17201.
	 */
	keepFocusInside( blurSource, e ) {
		if ( ! this.nodeRef.current ) {
			return;
		}

		const { losesFocusTo } = this.props;

		// Blur triggered internal to the DayPicker component.
		if (
			CONTAINER_DIV === blurSource &&
			e.target &&
			( e.target.classList.contains( 'DayPickerNavigation_button' ) ||
				e.target.classList.contains( 'CalendarDay' ) ) &&
			// Allow other DayPicker elements to take focus.
			( ! e.relatedTarget ||
				( ! e.relatedTarget.classList.contains(
					'DayPickerNavigation_button'
				) &&
					! e.relatedTarget.classList.contains( 'CalendarDay' ) ) )
		) {
			// Allow other DayPicker elements to take focus.
			if (
				e.relatedTarget &&
				( e.relatedTarget.classList.contains(
					'DayPickerNavigation_button'
				) ||
					e.relatedTarget.classList.contains( 'CalendarDay' ) )
			) {
				return;
			}

			// Allow elements inside a specified ref to take focus.
			if (
				e.relatedTarget &&
				losesFocusTo &&
				losesFocusTo.contains( e.relatedTarget )
			) {
				return;
			}

			// DayPickerNavigation or CalendarDay mouseUp() is blurring,
			// so switch focus to the DayPicker's focus region.
			const focusRegion = this.nodeRef.current.querySelector(
				'.DayPicker_focusRegion'
			);
			if ( focusRegion ) {
				focusRegion.focus();
			}

			return;
		}

		// Blur triggered after next/prev click callback props.
		if (
			PREV_MONTH_CLICK === blurSource ||
			NEXT_MONTH_CLICK === blurSource
		) {
			// DayPicker's updateStateAfterMonthTransition() is about to blur
			// the activeElement, so focus a DayPickerNavigation button so the next
			// blur event gets fixed by the above logic path.
			const focusRegion = this.nodeRef.current.querySelector(
				'.DayPickerNavigation_button'
			);
			if ( focusRegion ) {
				focusRegion.focus();
			}
		}
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
			isInvalidDate,
		} = this.props;
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
						label={ __( 'Start Date', 'woocommerce' ) }
						error={ afterError }
						describedBy={ sprintf(
							__(
								"Date input describing a selected date range's start date in format %s",
								'woocommerce'
							),
							shortDateFormat
						) }
						onFocus={ () => this.onFocusChange( 'startDate' ) }
					/>
					<div className="woocommerce-calendar__inputs-to">
						{ __( 'to', 'woocommerce' ) }
					</div>
					<DateInput
						value={ beforeText }
						onChange={ partial( this.onInputChange, 'before' ) }
						dateFormat={ shortDateFormat }
						label={ __( 'End Date', 'woocommerce' ) }
						error={ beforeError }
						describedBy={ sprintf(
							__(
								"Date input describing a selected date range's end date in format %s",
								'woocommerce'
							),
							shortDateFormat
						) }
						onFocus={ () => this.onFocusChange( 'endDate' ) }
					/>
				</div>
				<div
					className="woocommerce-calendar__react-dates"
					ref={ this.nodeRef }
					onBlur={ partial( this.keepFocusInside, CONTAINER_DIV ) }
					tabIndex={ -1 }
				>
					<DayPickerRangeController
						onNextMonthClick={ partial(
							this.keepFocusInside,
							NEXT_MONTH_CLICK
						) }
						onPrevMonthClick={ partial(
							this.keepFocusInside,
							PREV_MONTH_CLICK
						) }
						onDatesChange={ this.onDatesChange }
						onFocusChange={ this.onFocusChange }
						focusedInput={ focusedInput }
						startDate={ after }
						endDate={ before }
						orientation={ 'horizontal' }
						numberOfMonths={ isDoubleCalendar ? 2 : 1 }
						isOutsideRange={ ( date ) => {
							return (
								isInvalidDate && isInvalidDate( date.toDate() )
							);
						} }
						minimumNights={ 0 }
						hideKeyboardShortcutsPanel
						noBorder
						isRTL={ isRTL() }
						initialVisibleMonth={ this.setTnitialVisibleMonth(
							isDoubleCalendar,
							before
						) }
						phrases={ phrases }
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
	 * A function to determine if a day on the calendar is not valid
	 */
	isInvalidDate: PropTypes.func,
	/**
	 * A function called upon selection of a date.
	 */
	onUpdate: PropTypes.func.isRequired,
	/**
	 * The date format in moment.js-style tokens.
	 */
	shortDateFormat: PropTypes.string.isRequired,
	/**
	 * A ref that the DateRange can lose focus to.
	 * See: https://github.com/woocommerce/woocommerce-admin/pull/2929.
	 */
	// eslint-disable-next-line no-undef
	losesFocusTo: PropTypes.instanceOf( Element ),
};

export default withViewportMatch( {
	isViewportMobile: '< medium',
	isViewportSmall: '< small',
} )( DateRange );
