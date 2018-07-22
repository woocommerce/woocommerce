/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import moment from 'moment';
import {
	DayPickerRangeController,
	isInclusivelyAfterDay,
	isInclusivelyBeforeDay,
} from 'react-dates';
import { partial } from 'lodash';
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import PropTypes from 'prop-types';
import 'react-dates/lib/css/_datepicker.css';

/**
 * Internal dependencies
 */
import { toMoment } from 'lib/date';
import DateInput from './input';
import phrases from './phrases';
import './style.scss';

const START_DATE = 'startDate';

// 782px is the width designated by Gutenberg's `</ Popover>` component.
// * https://github.com/WordPress/gutenberg/blob/c8f8806d4465a83c1a0bc62d5c61377b56fa7214/components/popover/utils.js#L6
const isMobileViewport = () => window.innerWidth < 782;
const shortDateFormat = __( 'MM/DD/YYYY', 'wc-admin' );

class DateRange extends Component {
	constructor( props ) {
		super( props );

		const { after, before } = props;
		this.state = {
			focusedInput: START_DATE,
			afterText: after ? after.format( shortDateFormat ) : '',
			beforeText: before ? before.format( shortDateFormat ) : '',
			afterError: null,
			beforeError: null,
		};

		this.onDatesChange = this.onDatesChange.bind( this );
		this.onFocusChange = this.onFocusChange.bind( this );
		this.onInputChange = this.onInputChange.bind( this );
		this.getOutsideRange = this.getOutsideRange.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { after, before } = this.props;
		/**
		 * Check if props have been reset. If so, reset internal state. Disabling
		 * eslint here because this setState cannot cause infinte loop
		 */
		/* eslint-disable react/no-did-update-set-state */
		if ( ( prevProps.before || prevProps.after ) && ( null === after && null === before ) ) {
			this.setState( {
				focusedInput: START_DATE,
				afterText: '',
				beforeText: '',
				afterError: null,
				beforeError: null,
			} );
		}
		/* eslint-enable react/no-did-update-set-state */
	}

	onDatesChange( { startDate, endDate } ) {
		this.setState( {
			afterText: startDate ? startDate.format( shortDateFormat ) : '',
			beforeText: endDate ? endDate.format( shortDateFormat ) : '',
			afterError: null,
			beforeError: null,
		} );
		this.props.onSelect( {
			after: startDate,
			before: endDate,
		} );
	}

	onFocusChange( focusedInput ) {
		this.setState( {
			focusedInput: ! focusedInput ? START_DATE : focusedInput,
		} );
	}

	getValidatedDate( input, value ) {
		const { after, before } = this.props;
		const date = toMoment( shortDateFormat, value );
		if ( ! date ) {
			return {
				date: null,
				error: __( 'Invalid date', 'wc-admin' ),
			};
		}
		if ( moment().isBefore( date, 'day' ) ) {
			return {
				date: null,
				error: __( 'Select a date in the past', 'wc-admin' ),
			};
		}
		if ( 'after' === input && before && date.isAfter( before, 'day' ) ) {
			return {
				date: null,
				error: __( 'Start date must be before end date', 'wc-admin' ),
			};
		}
		if ( 'before' === input && after && date.isBefore( after, 'day' ) ) {
			return {
				date: null,
				error: __( 'Start date must be before end date', 'wc-admin' ),
			};
		}
		return { date };
	}

	onInputChange( input, event ) {
		const value = event.target.value;
		const { date, error } = this.getValidatedDate( input, value );
		this.setState( {
			[ input + 'Text' ]: value,
			[ input + 'Error' ]: value.length > 0 ? error : null,
		} );
		this.props.onSelect( {
			[ input ]: date,
		} );
	}

	getOutsideRange() {
		const { inValidDays } = this.props;
		if ( 'string' === typeof inValidDays ) {
			switch ( inValidDays ) {
				case 'past':
					return day => isInclusivelyBeforeDay( day, moment() );
				case 'future':
					return day => isInclusivelyAfterDay( day, moment() );
				case 'none':
				default:
					return undefined;
			}
		}
		return 'function' === typeof inValidDays ? inValidDays : undefined;
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
		const { focusedInput, afterText, beforeText, afterError, beforeError } = this.state;
		const { after, before } = this.props;
		const isOutsideRange = this.getOutsideRange();
		const isMobile = isMobileViewport();
		const isDoubleCalendar = isMobile && window.innerWidth > 624;
		return (
			<div
				className={ classnames( 'woocommerce-calendar', {
					'is-mobile': isMobile,
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
	after: PropTypes.object,
	before: PropTypes.object,
	onSelect: PropTypes.func.isRequired,
	inValidDays: PropTypes.oneOfType( [
		PropTypes.oneOf( [ 'past', 'future', 'none' ] ),
		PropTypes.func,
	] ),
};

export { DateRange };
