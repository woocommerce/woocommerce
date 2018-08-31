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
import DateInput from './input';
import { isMobileViewport } from 'lib/ui';
import phrases from './phrases';
import { validateDateInputForRange } from 'lib/date';
import './style.scss';

class DateRange extends Component {
	constructor( props ) {
		super( props );

		this.onDatesChange = this.onDatesChange.bind( this );
		this.onFocusChange = this.onFocusChange.bind( this );
		this.onInputChange = this.onInputChange.bind( this );
		this.getOutsideRange = this.getOutsideRange.bind( this );
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

	getOutsideRange() {
		const { invalidDays } = this.props;
		if ( 'string' === typeof invalidDays ) {
			switch ( invalidDays ) {
				case 'past':
					return day => isInclusivelyBeforeDay( day, moment() );
				case 'future':
					return day => isInclusivelyAfterDay( day, moment() );
				case 'none':
				default:
					return undefined;
			}
		}
		return 'function' === typeof invalidDays ? invalidDays : undefined;
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
		} = this.props;
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
	onUpdate: PropTypes.func.isRequired,
	invalidDays: PropTypes.oneOfType( [
		PropTypes.oneOf( [ 'past', 'future', 'none' ] ),
		PropTypes.func,
	] ),
	focusedInput: PropTypes.string,
	afterText: PropTypes.string,
	beforeText: PropTypes.string,
	afterError: PropTypes.string,
	beforeError: PropTypes.string,
	shortDateFormat: PropTypes.string.isRequired,
};

export default DateRange;
