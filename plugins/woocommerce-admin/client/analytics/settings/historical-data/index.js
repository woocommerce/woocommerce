/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Component } from '@wordpress/element';
import moment from 'moment';
import { withSpokenMessages } from '@wordpress/components';

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
			// Whether there is an active import (which might have been stopped)
			// that matches the period and skipChecked settings
			activeImport: null,
			lastImportStartTimestamp: 0,
			lastImportStopTimestamp: 0,
			period: {
				date: moment().format( this.dateFormat ),
				label: 'all',
			},
			skipChecked: true,
		};

		this.makeQuery = this.makeQuery.bind( this );
		this.onImportFinished = this.onImportFinished.bind( this );
		this.onImportStarted = this.onImportStarted.bind( this );
		this.onDeletePreviousData = this.onDeletePreviousData.bind( this );
		this.onReimportData = this.onReimportData.bind( this );
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
					this.setState( {
						activeImport: false,
						lastImportStopTimestamp: Date.now(),
					} );
				}
			} )
			.catch( error => {
				if ( error && error.message ) {
					addNotice( { status: 'error', message: error.message } );
					this.setState( {
						activeImport: false,
						lastImportStopTimestamp: Date.now(),
					} );
				}
			} );
	}

	onImportFinished() {
		const { debouncedSpeak } = this.props;
		debouncedSpeak( 'Import complete' );
		this.setState( {
			lastImportStopTimestamp: Date.now(),
		} );
	}

	onImportStarted() {
		this.setState( {
			activeImport: true,
			lastImportStartTimestamp: Date.now(),
		} );
	}

	onDeletePreviousData() {
		const path = '/wc/v4/reports/import/delete';
		const errorMessage = __(
			'There was a problem deleting your previous data.',
			'woocommerce-admin'
		);
		this.makeQuery( path, errorMessage );
		this.setState( {
			activeImport: false,
		} );
	}

	onReimportData() {
		this.setState( {
			activeImport: false,
		} );
	}

	onStartImport() {
		const { period, skipChecked } = this.state;
		const path =
			'/wc/v4/reports/import' +
			stringifyQuery( formatParams( this.dateFormat, period, skipChecked ) );
		const errorMessage = __(
			'There was a problem rebuilding your report data.',
			'woocommerce-admin'
		);
		this.makeQuery( path, errorMessage );
		this.onImportStarted();
	}

	onStopImport() {
		this.setState( {
			lastImportStopTimestamp: Date.now(),
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
			activeImport: false,
			period: {
				...this.state.period,
				label: val,
			},
		} );
	}

	onDateChange( val ) {
		this.setState( {
			activeImport: false,
			period: {
				date: val,
				label: 'custom',
			},
		} );
	}

	onSkipChange( val ) {
		this.setState( {
			activeImport: false,
			skipChecked: val,
		} );
	}

	render() {
		const {
			activeImport,
			lastImportStartTimestamp,
			lastImportStopTimestamp,
			period,
			skipChecked,
		} = this.state;

		return (
			<HistoricalDataLayout
				activeImport={ activeImport }
				dateFormat={ this.dateFormat }
				onImportFinished={ this.onImportFinished }
				onImportStarted={ this.onImportStarted }
				lastImportStartTimestamp={ lastImportStartTimestamp }
				lastImportStopTimestamp={ lastImportStopTimestamp }
				onPeriodChange={ this.onPeriodChange }
				onDateChange={ this.onDateChange }
				onSkipChange={ this.onSkipChange }
				onDeletePreviousData={ this.onDeletePreviousData }
				onReimportData={ this.onReimportData }
				onStartImport={ this.onStartImport }
				onStopImport={ this.onStopImport }
				period={ period }
				skipChecked={ skipChecked }
			/>
		);
	}
}

export default withSpokenMessages( HistoricalData );
