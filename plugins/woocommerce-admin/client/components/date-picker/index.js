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

class DatePicker extends Component {
	constructor( props ) {
		super( props );
		this.state = getDateParamsFromQuery( props.query );
		this.select = this.select.bind( this );
		this.getUpdatePath = this.getUpdatePath.bind( this );
		this.isValidSelection = this.isValidSelection.bind( this );
		this.resetCustomValues = this.resetCustomValues.bind( this );
	}

	// @TODO change this to `getDerivedStateFromProps` in React 16.4
	UNSAFE_componentWillReceiveProps( nextProps ) {
		this.setState( getDateParamsFromQuery( nextProps.query ) );
	}

	select( update ) {
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
		} );
	}

	render() {
		const { period, compare, after, before } = this.state;
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
							onSelect={ this.select }
							onClose={ onClose }
							getUpdatePath={ this.getUpdatePath }
							isValidSelection={ this.isValidSelection }
							resetCustomValues={ this.resetCustomValues }
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
