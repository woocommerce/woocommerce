/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Dropdown } from '@wordpress/components';

/**
 * Internal dependencies
 */
import DatePickerContent from './content';
import DropdownButton from 'components/dropdown-button';
import { getCurrentDates, getDateParamsFromQuery, isoDateFormat } from 'lib/date';
import { getNewPath, getQuery } from 'lib/nav-utils';
import './style.scss';

const shortDateFormat = __( 'MM/DD/YYYY', 'wc-admin' );

class DatePicker extends Component {
	constructor( props ) {
		super( props );
		this.state = this.getResetState();

		this.update = this.update.bind( this );
		this.getUpdatePath = this.getUpdatePath.bind( this );
		this.isValidSelection = this.isValidSelection.bind( this );
		this.resetCustomValues = this.resetCustomValues.bind( this );
	}

	getResetState() {
		const { period, compare, before, after } = getDateParamsFromQuery( getQuery() );
		return {
			period,
			compare,
			before,
			after,
			focusedInput: 'startDate',
			afterText: after ? after.format( shortDateFormat ) : '',
			beforeText: before ? before.format( shortDateFormat ) : '',
			afterError: null,
			beforeError: null,
		};
	}

	update( update ) {
		this.setState( update );
	}

	getUpdatePath( selectedTab ) {
		const { period, compare, after, before } = this.state;
		const data = {
			period: 'custom' === selectedTab ? 'custom' : period,
			compare,
		};
		if ( 'custom' === selectedTab ) {
			data.after = after ? after.format( isoDateFormat ) : '';
			data.before = before ? before.format( isoDateFormat ) : '';
		}
		return getNewPath( data );
	}

	getButtonLabel() {
		const { primary, secondary } = getCurrentDates( getQuery() );
		return [
			`${ primary.label } (${ primary.range })`,
			`${ __( 'vs.', 'wc-admin' ) } ${ secondary.label } (${ secondary.range })`,
		];
	}

	isValidSelection( selectedTab ) {
		const { compare, after, before } = this.state;
		if ( 'custom' === selectedTab ) {
			return compare && after && before;
		}
		return true;
	}

	resetCustomValues() {
		this.setState( {
			after: null,
			before: null,
			focusedInput: 'startDate',
			afterText: '',
			beforeText: '',
			afterError: null,
			beforeError: null,
		} );
	}

	render() {
		const {
			period,
			compare,
			after,
			before,
			focusedInput,
			afterText,
			beforeText,
			afterError,
			beforeError,
		} = this.state;
		return (
			<div className="woocommerce-filters-date">
				<p>{ __( 'Date Range', 'wc-admin' ) }:</p>
				<Dropdown
					contentClassName="woocommerce-filters-date__content"
					position="bottom"
					expandOnMobile
					renderToggle={ ( { isOpen, onToggle } ) => (
						<DropdownButton
							onClick={ onToggle }
							isOpen={ isOpen }
							labels={ this.getButtonLabel() }
						/>
					) }
					renderContent={ ( { onClose } ) => (
						<DatePickerContent
							period={ period }
							compare={ compare }
							after={ after }
							before={ before }
							onUpdate={ this.update }
							onClose={ onClose }
							getUpdatePath={ this.getUpdatePath }
							isValidSelection={ this.isValidSelection }
							resetCustomValues={ this.resetCustomValues }
							focusedInput={ focusedInput }
							afterText={ afterText }
							beforeText={ beforeText }
							afterError={ afterError }
							beforeError={ beforeError }
							shortDateFormat={ shortDateFormat }
						/>
					) }
				/>
			</div>
		);
	}
}

export default DatePicker;
