/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Dropdown } from '@wordpress/components';
import { stringify as stringifyQueryObject } from 'qs';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import DropdownButton from 'components/dropdown-button';
import DatePickerContent from './content';
import { getCurrentDates, getDateParamsFromQuery, isoDateFormat } from 'lib/date';

const shortDateFormat = __( 'MM/DD/YYYY', 'wc-admin' );

class DatePicker extends Component {
	constructor( props ) {
		super( props );
		this.state = this.getResetState( props );

		this.update = this.update.bind( this );
		this.getUpdatePath = this.getUpdatePath.bind( this );
		this.isValidSelection = this.isValidSelection.bind( this );
		this.resetCustomValues = this.resetCustomValues.bind( this );
	}

	getResetState( props ) {
		const { period, compare, before, after } = getDateParamsFromQuery( props.query );
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

	getOtherQueries( query ) {
		const { period, compare, after, before, ...otherQueries } = query; // eslint-disable-line no-unused-vars
		return otherQueries;
	}

	getUpdatePath( selectedTab ) {
		const { path, query } = this.props;
		const otherQueries = this.getOtherQueries( query );
		const { period, compare, after, before } = this.state;
		const data = {
			period: 'custom' === selectedTab ? 'custom' : period,
			compare,
		};
		if ( 'custom' === selectedTab ) {
			Object.assign( data, {
				after: after ? after.format( isoDateFormat ) : '',
				before: before ? before.format( isoDateFormat ) : '',
			} );
		}
		const queryString = stringifyQueryObject( Object.assign( otherQueries, data ) );
		return `${ path }?${ queryString }`;
	}

	getButtonLabel() {
		const { primary, secondary } = getCurrentDates( this.props.query );
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
			<div className="woocommerce-date-picker">
				<p>{ __( 'Date Range', 'wc-admin' ) }:</p>
				<Dropdown
					contentClassName="woocommerce-date-picker__content"
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

DatePicker.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};

export default DatePicker;
