/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { formatParams } from './utils';
import HistoricalDataActions from './actions';
import HistoricalDataPeriodSelector from './period-selector';
import HistoricalDataProgress from './progress';
import HistoricalDataStatus from './status';
import HistoricalDataSkipCheckbox from './skip-checkbox';
import withSelect from 'wc-api/with-select';
import './style.scss';

class HistoricalDataLayout extends Component {
	getStatus() {
		const {
			customersProgress,
			customersTotal,
			inProgress,
			ordersProgress,
			ordersTotal,
		} = this.props;

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
			dateFormat,
			hasImportedData,
			importDate,
			inProgress,
			onPeriodChange,
			onDateChange,
			onSkipChange,
			onDeletePreviousData,
			onStartImport,
			onStopImport,
			ordersProgress,
			ordersTotal,
			period,
			skipChecked,
		} = this.props;
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
									dateFormat={ dateFormat }
									disabled={ inProgress }
									onPeriodChange={ onPeriodChange }
									onDateChange={ onDateChange }
									value={ period }
								/>
								<HistoricalDataSkipCheckbox
									disabled={ inProgress }
									checked={ skipChecked }
									onChange={ onSkipChange }
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
					onDeletePreviousData={ onDeletePreviousData }
					onStartImport={ onStartImport }
					onStopImport={ onStopImport }
					ordersProgress={ ordersProgress }
					ordersTotal={ ordersTotal }
				/>
			</Fragment>
		);
	}
}

export default withSelect( ( select, props ) => {
	const { getImportTotals } = select( 'wc-api' );
	const { period, skipChecked } = props;

	const { customers: customersTotal, orders: ordersTotal } = getImportTotals(
		formatParams( period, skipChecked )
	);

	return {
		customersProgress: 0,
		customersTotal,
		hasImportedData: false,
		importDate: '2019-04-01',
		ordersProgress: 0,
		ordersTotal,
	};
} )( HistoricalDataLayout );
