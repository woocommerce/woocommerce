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
const shortDateFormat = __( 'MM/DD/YYYY', 'woo-dash' );

class DateRange extends Component {
	constructor( props ) {
		super( props );

		const { start, end } = props;
		this.state = {
			focusedInput: START_DATE,
			startText: start ? start.format( shortDateFormat ) : '',
			endText: end ? end.format( shortDateFormat ) : '',
		};

		this.onDatesChange = this.onDatesChange.bind( this );
		this.onFocusChange = this.onFocusChange.bind( this );
		this.onInputChange = this.onInputChange.bind( this );
		this.getOutsideRange = this.getOutsideRange.bind( this );
	}

	onDatesChange( { startDate, endDate } ) {
		this.setState( {
			startText: startDate ? startDate.format( shortDateFormat ) : '',
			endText: endDate ? endDate.format( shortDateFormat ) : '',
		} );
		this.props.onSelect( {
			start: startDate,
			end: endDate,
		} );
	}

	onFocusChange( focusedInput ) {
		this.setState( {
			focusedInput: ! focusedInput ? START_DATE : focusedInput,
		} );
	}

	onInputChange( input, event ) {
		const value = event.target.value;
		this.setState( { [ input ]: value } );
		const date = toMoment( shortDateFormat, value );
		if ( date ) {
			const match = input.match( /.*(?=Text)/ );
			if ( match ) {
				this.props.onSelect( {
					[ match[ 0 ] ]: date,
				} );
			}
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
		const { focusedInput, startText, endText } = this.state;
		const { start, end } = this.props;
		const isOutsideRange = this.getOutsideRange();
		const isMobile = isMobileViewport();
		return (
			<Fragment>
				<div className="woocommerce-date-picker__date-inputs">
					<input
						value={ startText }
						type="text"
						onChange={ partial( this.onInputChange, 'startText' ) }
						aria-label={ __( 'Start Date', 'woo-dash' ) }
						id="start-date-string"
						aria-describedby="start-date-string-message"
					/>
					<p className="screen-reader-text" id="start-date-string-message">
						{ sprintf(
							__(
								"Date input describing a selected date range's start date in format %s",
								'woo-dash'
							),
							shortDateFormat
						) }
					</p>
					<span>{ __( 'to', 'woo-dash' ) }</span>
					<input
						value={ endText }
						type="text"
						onChange={ partial( this.onInputChange, 'endText' ) }
						aria-label={ __( 'End Date', 'woo-dash' ) }
						id="end-date-string"
						aria-describedby="end-date-string-message"
					/>
					<p className="screen-reader-text" id="start-date-string-message">
						{ sprintf(
							__(
								"Date input describing a selected date range's end date in format %s",
								'woo-dash'
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
						startDate={ start }
						startDateId={ START_DATE }
						startDatePlaceholderText={ 'Start Date' }
						endDate={ end }
						endDateId={ END_DATE }
						endDatePlaceholderText={ 'End Date' }
						orientation={ 'horizontal' }
						numberOfMonths={ 1 }
						isOutsideRange={ isOutsideRange }
						minimumNights={ 0 }
						hideKeyboardShortcutsPanel
						noBorder
						initialVisibleMonth={ () => start || moment() }
						phrases={ phrases }
					/>
				</div>
			</Fragment>
		);
	}
}

DateRange.propTypes = {
	start: PropTypes.object,
	end: PropTypes.object,
	onSelect: PropTypes.func.isRequired,
	inValidDays: PropTypes.oneOfType( [
		PropTypes.oneOf( [ 'past', 'future', 'none' ] ),
		PropTypes.func,
	] ),
};

export { DateRange };
