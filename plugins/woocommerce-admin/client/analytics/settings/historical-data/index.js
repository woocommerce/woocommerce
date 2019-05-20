/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Component } from '@wordpress/element';
import moment from 'moment';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { formatParams } from './utils';
import HistoricalDataLayout from './layout';

class HistoricalData extends Component {
	constructor() {
		super( ...arguments );

		this.dateFormat = __( 'MM/DD/YYYY', 'woocommerce-admin' );

		this.state = {
			inProgress: false,
			period: {
				date: moment().format( this.dateFormat ),
				label: 'all',
			},
			skipChecked: true,
		};

		this.makeQuery = this.makeQuery.bind( this );
		this.onDeletePreviousData = this.onDeletePreviousData.bind( this );
		this.onStartImport = this.onStartImport.bind( this );
		this.onStopImport = this.onStopImport.bind( this );
		this.onDateChange = this.onDateChange.bind( this );
		this.onPeriodChange = this.onPeriodChange.bind( this );
		this.onSkipChange = this.onSkipChange.bind( this );
	}

	makeQuery( path, errorMessage ) {
		const { addNotice } = this.props;
		apiFetch( { path, method: 'POST' } )
			.then( response => {
				if ( 'success' === response.status ) {
					addNotice( { status: 'success', message: response.message } );
				} else {
					addNotice( { status: 'error', message: errorMessage } );
				}
			} )
			.catch( error => {
				if ( error && error.message ) {
					addNotice( { status: 'error', message: error.message } );
				}
			} );
	}

	onDeletePreviousData() {
		const path = '/wc/v4/reports/import/delete';
		const errorMessage = __(
			'There was a problem deleting your previous data.',
			'woocommerce-admin'
		);
		this.makeQuery( path, errorMessage );
	}

	onStartImport() {
		const { period, skipChecked } = this.state;
		this.setState( {
			inProgress: true,
		} );
		const path = '/wc/v4/reports/import' + stringifyQuery( formatParams( period, skipChecked ) );
		const errorMessage = __(
			'There was a problem rebuilding your report data.',
			'woocommerce-admin'
		);
		this.makeQuery( path, errorMessage );
	}

	onStopImport() {
		this.setState( {
			inProgress: false,
		} );
		const path = '/wc/v4/reports/import/cancel';
		const errorMessage = __(
			'There was a problem stopping your current import.',
			'woocommerce-admin'
		);
		this.makeQuery( path, errorMessage );
	}

	onPeriodChange( val ) {
		this.setState( {
			period: {
				...this.state.period,
				label: val,
			},
		} );
	}

	onDateChange( val ) {
		this.setState( {
			period: {
				date: val,
				label: 'custom',
			},
		} );
	}

	onSkipChange( val ) {
		this.setState( {
			skipChecked: val,
		} );
	}

	render() {
		const { inProgress, period, skipChecked } = this.state;

		return (
			<HistoricalDataLayout
				dateFormat={ this.dateFormat }
				inProgress={ inProgress }
				onPeriodChange={ this.onPeriodChange }
				onDateChange={ this.onDateChange }
				onSkipChange={ this.onSkipChange }
				onDeletePreviousData={ this.onDeletePreviousData }
				onStartImport={ this.onStartImport }
				onStopImport={ this.onStopImport }
				period={ period }
				skipChecked={ skipChecked }
			/>
		);
	}
}

export default HistoricalData;
