/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Component, Fragment } from '@wordpress/element';
import moment from 'moment';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import HistoricalDataActions from './actions';
import HistoricalDataPeriodSelector from './period-selector';
import HistoricalDataProgress from './progress';
import HistoricalDataStatus from './status';
import HistoricalDataSkipCheckbox from './skip-checkbox';
import withSelect from 'wc-api/with-select';
import './style.scss';

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
		const params = {};
		if ( skipChecked ) {
			params.skip_existing = true;
		}
		if ( period.label !== 'all' ) {
			if ( period.label === 'custom' ) {
				const daysDifference = moment().diff(
					moment( period.date, this.dateFormat ),
					'days',
					true
				);
				params.days = Math.ceil( daysDifference );
			} else {
				params.days = parseInt( period.label, 10 );
			}
		}
		const path = '/wc/v4/reports/import' + stringifyQuery( params );
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

	getStatus() {
		const { customersProgress, customersTotal, ordersProgress, ordersTotal } = this.props;
		const { inProgress } = this.state;

		if ( inProgress ) {
			if ( customersProgress < customersTotal ) {
				return 'customers';
			}
			if ( ordersProgress < ordersTotal ) {
				return 'orders';
			}
			return 'finalizing';
		}
		if (
			( customersTotal > 0 || ordersTotal > 0 ) &&
			customersProgress === customersTotal &&
			ordersProgress === ordersTotal
		) {
			return 'finished';
		}
		return 'ready';
	}

	render() {
		const {
			customersProgress,
			customersTotal,
			hasImportedData,
			importDate,
			ordersProgress,
			ordersTotal,
		} = this.props;
		const { inProgress, period, skipChecked } = this.state;
		const hasImportedAllData =
			! inProgress &&
			hasImportedData &&
			customersProgress === customersTotal &&
			ordersProgress === ordersTotal;
		// @todo When the import status endpoint is hooked up,
		// this bool should be removed and assume it's true.
		const showImportStatus = false;

		return (
			<Fragment>
				<div className="woocommerce-setting">
					<div className="woocommerce-setting__label" id="import-historical-data-label">
						{ __( 'Import Historical Data:', 'woocommerce-admin' ) }
					</div>
					<div className="woocommerce-setting__input">
						<span className="woocommerce-setting__help">
							{ __(
								'This tool populates historical analytics data by processing customers ' +
									'and orders created prior to activating WooCommerce Admin.',
								'woocommerce-admin'
							) }
						</span>
						{ ! hasImportedAllData && (
							<Fragment>
								<HistoricalDataPeriodSelector
									dateFormat={ this.dateFormat }
									disabled={ inProgress }
									onPeriodChange={ this.onPeriodChange }
									onDateChange={ this.onDateChange }
									value={ period }
								/>
								<HistoricalDataSkipCheckbox
									disabled={ inProgress }
									checked={ skipChecked }
									onChange={ this.onSkipChange }
								/>
								{ showImportStatus && (
									<Fragment>
										<HistoricalDataProgress
											label={ __( 'Registered Customers', 'woocommerce-admin' ) }
											progress={ customersProgress }
											total={ customersTotal }
										/>
										<HistoricalDataProgress
											label={ __( 'Orders', 'woocommerce-admin' ) }
											progress={ ordersProgress }
											total={ ordersTotal }
										/>
									</Fragment>
								) }
							</Fragment>
						) }
						{ showImportStatus && (
							<HistoricalDataStatus importDate={ importDate } status={ this.getStatus() } />
						) }
					</div>
				</div>
				<HistoricalDataActions
					customersProgress={ customersProgress }
					customersTotal={ customersTotal }
					hasImportedData={ hasImportedData }
					inProgress={ inProgress }
					onDeletePreviousData={ this.onDeletePreviousData }
					onStartImport={ this.onStartImport }
					onStopImport={ this.onStopImport }
					ordersProgress={ ordersProgress }
					ordersTotal={ ordersTotal }
				/>
			</Fragment>
		);
	}
}

export default withSelect( () => {
	return {
		customersProgress: 0,
		customersTotal: 0,
		hasImportedData: false,
		importDate: '2019-04-01',
		ordersProgress: 0,
		ordersTotal: 0,
	};
} )( HistoricalData );
