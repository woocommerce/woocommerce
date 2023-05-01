/**
 * External dependencies
 */
import 'core-js/features/object/assign';
import 'core-js/features/array/from';
import { __, sprintf } from '@wordpress/i18n';
import { createElement, Component } from '@wordpress/element';
import { Dropdown, DatePicker as WpDatePicker } from '@wordpress/components';
import { partial, noop } from 'lodash';
import moment from 'moment';
import PropTypes from 'prop-types';
import { dateValidationMessages, toMoment } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import DateInput from './input';
import { H, Section } from '../section';

class DatePicker extends Component {
	constructor( props ) {
		super( props );

		this.onDateChange = this.onDateChange.bind( this );
		this.onInputChange = this.onInputChange.bind( this );
	}

	handleFocus( isOpen, onToggle ) {
		if ( ! isOpen ) {
			onToggle();
		}
	}

	handleBlur( isOpen, onToggle, event ) {
		if ( ! isOpen ) {
			return;
		}

		const relatedTargetParent = event.relatedTarget?.closest(
			'.components-dropdown'
		);
		const currentTargetParent = event.currentTarget?.closest(
			'.components-dropdown'
		);

		if (
			! relatedTargetParent ||
			relatedTargetParent !== currentTargetParent
		) {
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
		const error = date ? null : dateValidationMessages.invalid;

		this.props.onUpdate( {
			date,
			text: value,
			error: value.length > 0 ? error : null,
		} );
	}

	render() {
		const { date, disabled, text, dateFormat, error, isInvalidDate } =
			this.props;

		return (
			<Dropdown
				position="bottom center"
				focusOnMount={ false }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<DateInput
						disabled={ disabled }
						value={ text }
						onChange={ this.onInputChange }
						onBlur={ partial( this.handleBlur, isOpen, onToggle ) }
						dateFormat={ dateFormat }
						label={ __( 'Choose a date', 'woocommerce' ) }
						error={ error }
						describedBy={ sprintf(
							__(
								'Date input describing a selected date in format %s',
								'woocommerce'
							),
							dateFormat
						) }
						onFocus={ partial(
							this.handleFocus,
							isOpen,
							onToggle
						) }
						aria-expanded={ isOpen }
						focusOnMount={ false }
						errorPosition="top center"
					/>
				) }
				renderContent={ ( { onToggle } ) => (
					<Section component={ false }>
						<H className="woocommerce-calendar__date-picker-title">
							{ __( 'select a date', 'woocommerce' ) }
						</H>
						<div className="woocommerce-calendar__react-dates is-core-datepicker">
							<WpDatePicker
								currentDate={
									date instanceof moment
										? date.toDate()
										: date
								}
								onChange={ partial(
									this.onDateChange,
									onToggle
								) }
								// onMonthPreviewed is required to prevent a React error from happening.
								onMonthPreviewed={ noop }
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
	 * Whether the input is disabled.
	 */
	disabled: PropTypes.bool,
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
