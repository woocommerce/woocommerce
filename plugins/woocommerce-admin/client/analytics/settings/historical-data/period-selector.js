/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import moment from 'moment';
import { SelectControl } from '@wordpress/components';
import { DatePicker } from '@woocommerce/components';
import { dateValidationMessages } from '@woocommerce/date';
import { IMPORT_STORE_NAME } from '@woocommerce/data';
import { withDispatch } from '@wordpress/data';

function HistoricalDataPeriodSelector( {
	dateFormat,
	disabled,
	setImportPeriod,
	value,
} ) {
	const onSelectChange = ( val ) => {
		setImportPeriod( val );
	};
	const onDatePickerChange = ( val ) => {
		const dateModified = true;
		if ( val.date && val.date.isValid ) {
			setImportPeriod( val.date.format( dateFormat ), dateModified );
		} else {
			setImportPeriod( val.text, dateModified );
		}
	};

	const getDatePickerError = ( momentDate ) => {
		if (
			! momentDate.isValid() ||
			value.date.length !== dateFormat.length
		) {
			return dateValidationMessages.invalid;
		}
		if ( momentDate.isAfter( new Date(), 'day' ) ) {
			return dateValidationMessages.future;
		}
		return null;
	};
	const getDatePicker = () => {
		const momentDate = moment( value.date, dateFormat );
		return (
			<div className="woocommerce-settings-historical-data__column">
				<div className="woocommerce-settings-historical-data__column-label">
					{ __( 'Beginning on', 'woocommerce-admin' ) }
				</div>
				<DatePicker
					date={ momentDate.isValid() ? momentDate.toDate() : null }
					dateFormat={ dateFormat }
					disabled={ disabled }
					error={ getDatePickerError( momentDate ) }
					isInvalidDate={ ( date ) =>
						moment( date ).isAfter( new Date(), 'day' )
					}
					onUpdate={ onDatePickerChange }
					text={ value.date }
				/>
			</div>
		);
	};

	return (
		<div className="woocommerce-settings-historical-data__columns">
			<div className="woocommerce-settings-historical-data__column">
				<SelectControl
					label={ __(
						'Import historical data',
						'woocommerce-admin'
					) }
					value={ value.label }
					disabled={ disabled }
					onChange={ onSelectChange }
					options={ [
						{ label: 'All', value: 'all' },
						{ label: 'Last 365 days', value: '365' },
						{ label: 'Last 90 days', value: '90' },
						{ label: 'Last 30 days', value: '30' },
						{ label: 'Last 7 days', value: '7' },
						{ label: 'Last 24 hours', value: '1' },
						{ label: 'Custom', value: 'custom' },
					] }
				/>
			</div>
			{ value.label === 'custom' && getDatePicker() }
		</div>
	);
}

export default withDispatch( ( dispatch ) => {
	const { setImportPeriod } = dispatch( IMPORT_STORE_NAME );
	return { setImportPeriod };
} )( HistoricalDataPeriodSelector );
