/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
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
import phrases from './phrases';
import './style.scss';

const START_DATE = 'startDate';
const END_DATE = 'endDate';

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
		};

		this.onDatesChange = this.onDatesChange.bind( this );
		this.onFocusChange = this.onFocusChange.bind( this );
		this.onInputChange = this.onInputChange.bind( this );
		this.getOutsideRange = this.getOutsideRange.bind( this );
	}

	onDatesChange( { startDate, endDate } ) {
		this.setState( {
			afterText: startDate ? startDate.format( shortDateFormat ) : '',
			beforeText: endDate ? endDate.format( shortDateFormat ) : '',
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

	onInputChange( input, event ) {
		const value = event.target.value;
		this.setState( { [ input + 'Text' ]: value } );
		const date = toMoment( shortDateFormat, value );
		if ( date ) {
			this.props.onSelect( {
				[ input ]: date,
			} );
		}
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

	render() {
		const { focusedInput, afterText, beforeText } = this.state;
		const { after, before } = this.props;
		const isOutsideRange = this.getOutsideRange();
		const isMobile = isMobileViewport();
		return (
			<Fragment>
				<div className="woocommerce-date-picker__date-inputs">
					<input
						value={ afterText }
						type="text"
						onChange={ partial( this.onInputChange, 'after' ) }
						aria-label={ __( 'Start Date', 'wc-admin' ) }
						id="after-date-string"
						aria-describedby="after-date-string-message"
					/>
					<p className="screen-reader-text" id="after-date-string-message">
						{ sprintf(
							__(
								"Date input describing a selected date range's start date in format %s",
								'wc-admin'
							),
							shortDateFormat
						) }
					</p>
					<span>{ __( 'to', 'wc-admin' ) }</span>
					<input
						value={ beforeText }
						type="text"
						onChange={ partial( this.onInputChange, 'before' ) }
						aria-label={ __( 'End Date', 'wc-admin' ) }
						id="before-date-string"
						aria-describedby="before-date-string-message"
					/>
					<p className="screen-reader-text" id="before-date-string-message">
						{ sprintf(
							__(
								"Date input describing a selected date range's end date in format %s",
								'wc-admin'
							),
							shortDateFormat
						) }
					</p>
				</div>
				<div
					className={ classnames( 'woocommerce-calendar', {
						'is-mobile': isMobile,
					} ) }
				>
					<DayPickerRangeController
						onDatesChange={ this.onDatesChange }
						onFocusChange={ this.onFocusChange }
						focusedInput={ focusedInput }
						startDate={ after }
						startDateId={ START_DATE }
						startDatePlaceholderText={ 'Start Date' }
						endDate={ before }
						endDateId={ END_DATE }
						endDatePlaceholderText={ 'End Date' }
						orientation={ 'horizontal' }
						numberOfMonths={ 1 }
						isOutsideRange={ isOutsideRange }
						minimumNights={ 0 }
						hideKeyboardShortcutsPanel
						noBorder
						initialVisibleMonth={ () => after || moment() }
						phrases={ phrases }
						firstDayOfWeek={ Number( wcSettings.date.dow ) }
					/>
				</div>
			</Fragment>
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
