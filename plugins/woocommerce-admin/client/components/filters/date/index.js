/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Dropdown } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import DatePickerContent from './content';
import DropdownButton from 'components/dropdown-button';
import { getCurrentDates, getDateParamsFromQuery, isoDateFormat } from 'lib/date';
import { updateQueryString } from 'lib/nav-utils';
import './style.scss';

const shortDateFormat = __( 'MM/DD/YYYY', 'wc-admin' );

class DatePicker extends Component {
	constructor( props ) {
		super( props );
		this.state = this.getResetState();

		this.update = this.update.bind( this );
		this.onSelect = this.onSelect.bind( this );
		this.isValidSelection = this.isValidSelection.bind( this );
		this.resetCustomValues = this.resetCustomValues.bind( this );
	}

	getResetState() {
		const { period, compare, before, after } = getDateParamsFromQuery( this.props.query );
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

	onSelect( selectedTab, onClose ) {
		const { path, query } = this.props;
		return event => {
			const { period, compare, after, before } = this.state;
			const data = {
				period: 'custom' === selectedTab ? 'custom' : period,
				compare,
			};
			if ( 'custom' === selectedTab ) {
				data.after = after ? after.format( isoDateFormat ) : '';
				data.before = before ? before.format( isoDateFormat ) : '';
			} else {
				data.after = undefined;
				data.before = undefined;
			}
			updateQueryString( data, path, query );
			onClose( event );
		};
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
							onSelect={ this.onSelect }
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
	query: PropTypes.object,
};

DatePicker.defaultProps = {
	query: {},
};

export default DatePicker;
