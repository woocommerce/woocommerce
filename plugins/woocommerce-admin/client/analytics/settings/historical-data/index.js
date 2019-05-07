/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import moment from 'moment';

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
			period: {
				date: moment().format( this.dateFormat ),
				label: 'all',
			},
			skipChecked: true,
		};

		this.onDateChange = this.onDateChange.bind( this );
		this.onPeriodChange = this.onPeriodChange.bind( this );
		this.onSkipChange = this.onSkipChange.bind( this );
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
			hasImportedData,
			importDate,
			inProgress,
			ordersProgress,
			ordersTotal,
		} = this.props;
		const { period, skipChecked } = this.state;
		const hasImportedAllData =
			! inProgress &&
			hasImportedData &&
			customersProgress === customersTotal &&
			ordersProgress === ordersTotal;

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
						<HistoricalDataStatus importDate={ importDate } status={ this.getStatus() } />
					</div>
				</div>
				<HistoricalDataActions
					customersProgress={ customersProgress }
					customersTotal={ customersTotal }
					hasImportedData={ hasImportedData }
					inProgress={ inProgress }
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
		inProgress: false,
		ordersProgress: 0,
		ordersTotal: 0,
	};
} )( HistoricalData );
