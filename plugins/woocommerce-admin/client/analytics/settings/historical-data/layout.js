/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { isNil } from 'lodash';
import { SectionHeader } from '@woocommerce/components';
import { IMPORT_STORE_NAME } from '@woocommerce/data';
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { formatParams, getStatus } from './utils';
import HistoricalDataActions from './actions';
import HistoricalDataPeriodSelector from './period-selector';
import HistoricalDataProgress from './progress';
import HistoricalDataStatus from './status';
import HistoricalDataSkipCheckbox from './skip-checkbox';
import './style.scss';

class HistoricalDataLayout extends Component {
	render() {
		const {
			customersProgress,
			customersTotal,
			dateFormat,
			importDate,
			inProgress,
			lastImportStartTimestamp,
			clearStatusAndTotalsCache,
			ordersProgress,
			ordersTotal,
			onImportStarted,
			period,
			stopImport,
			skipChecked,
			status,
		} = this.props;

		return (
			<Fragment>
				<SectionHeader
					title={ __(
						'Import historical data',
						'woocommerce-admin'
					) }
				/>
				<div className="woocommerce-settings__wrapper">
					<div className="woocommerce-setting">
						<div className="woocommerce-setting__input">
							<span className="woocommerce-setting__help">
								{ __(
									'This tool populates historical analytics data by processing customers ' +
										'and orders created prior to activating WooCommerce Admin.',
									'woocommerce-admin'
								) }
							</span>
							{ status !== 'finished' && (
								<Fragment>
									<HistoricalDataPeriodSelector
										dateFormat={ dateFormat }
										disabled={ inProgress }
										value={ period }
									/>
									<HistoricalDataSkipCheckbox
										disabled={ inProgress }
										checked={ skipChecked }
									/>
									<HistoricalDataProgress
										label={ __(
											'Registered Customers',
											'woocommerce-admin'
										) }
										progress={ customersProgress }
										total={ customersTotal }
									/>
									<HistoricalDataProgress
										label={ __(
											'Orders and Refunds',
											'woocommerce-admin'
										) }
										progress={ ordersProgress }
										total={ ordersTotal }
									/>
								</Fragment>
							) }
							<HistoricalDataStatus
								importDate={ importDate }
								status={ status }
							/>
						</div>
					</div>
				</div>
				<HistoricalDataActions
					clearStatusAndTotalsCache={ clearStatusAndTotalsCache }
					dateFormat={ dateFormat }
					importDate={ importDate }
					lastImportStartTimestamp={ lastImportStartTimestamp }
					onImportStarted={ onImportStarted }
					stopImport={ stopImport }
					status={ status }
				/>
			</Fragment>
		);
	}
}

export default withSelect( ( select, props ) => {
	const { getImportError, getImportStatus, getImportTotals } = select(
		IMPORT_STORE_NAME
	);
	const {
		activeImport,
		cacheNeedsClearing,
		dateFormat,
		inProgress,
		onImportStarted,
		onImportFinished,
		period,
		startStatusCheckInterval,
		skipChecked,
	} = props;

	const params = formatParams( dateFormat, period, skipChecked );
	const { customers, orders, lastImportStartTimestamp } = getImportTotals(
		params
	);

	const {
		customers: customersStatus,
		imported_from: importDate,
		is_importing: isImporting,
		orders: ordersStatus,
	} = getImportStatus( lastImportStartTimestamp );
	const { imported: customersProgress, total: customersTotal } =
		customersStatus || {};
	const { imported: ordersProgress, total: ordersTotal } = ordersStatus || {};

	const isError = Boolean(
		getImportError( lastImportStartTimestamp ) || getImportError( params )
	);

	const hasImportStarted = Boolean(
		! lastImportStartTimestamp && ! inProgress && isImporting === true
	);
	if ( hasImportStarted ) {
		onImportStarted();
	}

	const hasImportFinished = Boolean(
		inProgress &&
			! cacheNeedsClearing &&
			isImporting === false &&
			( customersTotal > 0 || ordersTotal > 0 ) &&
			customersProgress === customersTotal &&
			ordersProgress === ordersTotal
	);

	let response = {
		customersTotal: customers,
		isError,
		ordersTotal: orders,
	};

	if ( activeImport ) {
		response = {
			cacheNeedsClearing,
			customersProgress,
			customersTotal: isNil( customersTotal )
				? customers
				: customersTotal,
			inProgress,
			isError,
			ordersProgress,
			ordersTotal: isNil( ordersTotal ) ? orders : ordersTotal,
		};
	}

	const status = getStatus( response );

	if ( status === 'initializing' ) {
		startStatusCheckInterval();
	}

	if ( hasImportFinished ) {
		onImportFinished();
	}

	return { ...response, importDate, status };
} )( HistoricalDataLayout );
