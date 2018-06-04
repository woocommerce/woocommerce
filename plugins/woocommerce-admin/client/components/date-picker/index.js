/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { Button, Dropdown } from '@wordpress/components';
import { stringify as stringifyQueryObject } from 'qs';
import moment from 'moment';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import DatePickerContent from './content';
import { getDateValues } from 'lib/date-utils';

class DatePicker extends Component {
	constructor( props ) {
		super( props );
		this.state = this.addQueryDefaults( props.query );
		this.select = this.select.bind( this );
		this.getUpdatePath = this.getUpdatePath.bind( this );
	}

	addQueryDefaults( { period, compare, start, end } ) {
		// @TODO: What are the default `start` and `end` for custom dates, if at all?
		return {
			period: period || 'today',
			compare: compare || 'previous_period',
			start: start ? moment( start ) : undefined,
			end: end ? moment( end ) : undefined,
		};
	}

	select( key, value ) {
		this.setState( { [ key ]: value } );
	}

	getUpdatePath( selectedTab ) {
		const { path, query } = this.props;
		const { period, compare, start, end, ...otherQueries } = query; // eslint-disable-line no-unused-vars
		const data = {
			period: 'custom' === selectedTab ? 'custom' : this.state.period,
			compare: this.state.compare,
		};
		// `start` and `end` would be added as a result of the custom date picker. Adding here as an example
		if ( 'custom' === selectedTab ) {
			Object.assign( data, { start: '2018-04-15', end: '2018-04-29' } );
		}
		const queryString = stringifyQueryObject( Object.assign( otherQueries, data ) );
		return `${ path }?${ queryString }`;
	}

	getButtonLabel() {
		const queryWithDefaults = this.addQueryDefaults( this.props.query );
		const { primary, secondary } = getDateValues( queryWithDefaults );
		return (
			<div className="woocommerce-date-picker__toggle-label">
				<p>
					{ primary.label } ({ primary.range })
				</p>
				<p>
					vs. { secondary.label } ({ secondary.range })
				</p>
			</div>
		);
	}

	render() {
		const { period, compare, start, end } = this.state;
		return (
			<Dropdown
				className="woocommerce-date-picker"
				contentClassName="woocommerce-date-picker__content"
				position="bottom"
				expandOnMobile
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						className="woocommerce-date-picker__toggle"
						onClick={ onToggle }
						aria-expanded={ isOpen }
					>
						{ this.getButtonLabel() }
					</Button>
				) }
				renderContent={ ( { onClose } ) => (
					<DatePickerContent
						period={ period }
						compare={ compare }
						start={ start }
						end={ end }
						onSelect={ this.select }
						onClose={ onClose }
						getUpdatePath={ this.getUpdatePath }
					/>
				) }
			/>
		);
	}
}

DatePicker.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};

export default DatePicker;
