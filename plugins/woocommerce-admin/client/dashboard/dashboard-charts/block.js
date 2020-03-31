/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import { __, sprintf } from '@wordpress/i18n';

/**
 * WooCommerce dependencies
 */
import { Card } from '@woocommerce/components';
import {
	getHistory,
	getNewPath,
	getPersistedQuery,
} from '@woocommerce/navigation';
import { getAdminLink } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import ReportChart from 'analytics/components/report-chart';
import './block.scss';

class ChartBlock extends Component {
	handleChartClick = () => {
		const { selectedChart } = this.props;

		getHistory().push( this.getChartPath( selectedChart ) );
	};

	getChartPath( chart ) {
		return getNewPath(
			{ chart: chart.key },
			'/analytics/' + chart.endpoint,
			getPersistedQuery()
		);
	}

	render() {
		const {
			charts,
			endpoint,
			path,
			query,
			selectedChart,
		} = this.props;

		if ( ! selectedChart ) {
			return null;
		}

		return (
			<div
				role="presentation"
				className="woocommerce-dashboard__chart-block-wrapper"
				onClick={ this.handleChartClick }
			>
				<Card
					className="woocommerce-dashboard__chart-block woocommerce-analytics__card"
					title={ selectedChart.label }
				>
					<a
						className="screen-reader-text"
						href={ getAdminLink(
							this.getChartPath( selectedChart )
						) }
					>
						{ /* translators: %s is the chart type */
						sprintf(
							__( '%s Report', 'woocommerce-admin' ),
							selectedChart.label
						) }
					</a>
					<ReportChart
						charts={ charts }
						endpoint={ endpoint }
						query={ query }
						interactiveLegend={ false }
						legendPosition="bottom"
						path={ path }
						selectedChart={ selectedChart }
						showHeaderControls={ false }
					/>
				</Card>
			</div>
		);
	}
}

ChartBlock.propTypes = {
	charts: PropTypes.array,
	endpoint: PropTypes.string.isRequired,
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
	selectedChart: PropTypes.object.isRequired,
};

export default ChartBlock;
