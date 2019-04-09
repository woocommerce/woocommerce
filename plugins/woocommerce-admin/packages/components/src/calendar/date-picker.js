/** @format */
/**
 * External dependencies
 */
import 'core-js/fn/object/assign';
import 'core-js/fn/array/from';
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Dropdown, DatePicker as WpDatePicker } from '@wordpress/components';
import { partial } from 'lodash';
import { TAB } from '@wordpress/keycodes';
import moment from 'moment';

/**
 * Internal dependencies
 */
import DateInput from './input';
import { toMoment } from '@woocommerce/date';
import { H, Section } from '../section';
import PropTypes from 'prop-types';

class DatePicker extends Component {
	constructor( props ) {
		super( props );

		this.onDateChange = this.onDateChange.bind( this );
		this.onInputChange = this.onInputChange.bind( this );
	}

	handleKeyDown( isOpen, onToggle, { keyCode } ) {
		if ( TAB === keyCode && isOpen ) {
			onToggle();
		}
	}

	handleFocus( isOpen, onToggle ) {
		if ( ! isOpen ) {
			onToggle();
		}
	}

	onDateChange( onToggle, dateString ) {
		const { onUpdate, dateFormat } = this.props;
		const date = moment( dateString );
		onUpdate( {
			date,
			text: dateString ? date.format( dateFormat ) : '',
			error: null,
		} );
		onToggle();
	}

	onInputChange( event ) {
		const value = event.target.value;
		const { dateFormat } = this.props;
		const date = toMoment( dateFormat, value );
		const error = date ? null : __( 'Invalid date', 'woocommerce-admin' );

		this.props.onUpdate( {
			date,
			text: value,
			error: value.length > 0 ? error : null,
		} );
	}

	render() {
		const { date, text, dateFormat, error, isInvalidDate } = this.props;

		return (
			<Dropdown
				position="bottom center"
				focusOnMount={ false }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<DateInput
						value={ text }
						onChange={ this.onInputChange }
						dateFormat={ dateFormat }
						label={ __( 'Choose a date', 'woocommerce-admin' ) }
						error={ error }
						describedBy={ sprintf(
							__( 'Date input describing a selected date in format %s', 'woocommerce-admin' ),
							dateFormat
						) }
						onFocus={ partial( this.handleFocus, isOpen, onToggle ) }
						aria-expanded={ isOpen }
						focusOnMount={ false }
						onKeyDown={ partial( this.handleKeyDown, isOpen, onToggle ) }
						errorPosition="top center"
					/>
				) }
				renderContent={ ( { onToggle } ) => (
					<Section component={ false }>
						<H className="woocommerce-calendar__date-picker-title">
							{ __( 'select a date', 'woocommerce-admin' ) }
						</H>
						<div className="woocommerce-calendar__react-dates is-core-datepicker">
							<WpDatePicker
								currentDate={ date }
								onChange={ partial( this.onDateChange, onToggle ) }
								isInvalidDate={ isInvalidDate }
							/>
						</div>
					</Section>
				) }
			/>
		);
	}
}

DatePicker.propTypes = {
	/**
	 * A moment date object representing the selected date. `null` for no selection.
	 */
	date: PropTypes.object,
	/**
	 * The date in human-readable format. Displayed in the text input.
	 */
	text: PropTypes.string,
	/**
	 * A string error message, shown to the user.
	 */
	error: PropTypes.string,
	/**
	 * A function called upon selection of a date or input change.
	 */
	onUpdate: PropTypes.func.isRequired,
	/**
	 * The date format in moment.js-style tokens.
	 */
	dateFormat: PropTypes.string.isRequired,
	/**
	 * A function to determine if a day on the calendar is not valid
	 */
	isInvalidDate: PropTypes.func,
};

export default DatePicker;
