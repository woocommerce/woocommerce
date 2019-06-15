/** @format */
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
import { getAdminLink, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportChart from 'analytics/components/report-chart';
import './block.scss';

class ChartBlock extends Component {
	handleChartClick = () => {
		const { charts } = this.props;

		if ( ! charts || ! charts.length ) {
			return null;
		}

		getHistory().push( 'analytics/' + charts[ 0 ].endpoint + '?chart=' + charts[ 0 ].key );
	};

	render() {
		const { charts, endpoint, path, query } = this.props;

		if ( ! charts || ! charts.length ) {
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
					title={ charts[ 0 ].label }
				>
					<a
						className="screen-reader-text"
						href={ getAdminLink(
							'admin.php?page=wc-admin#/analytics/' +
								charts[ 0 ].endpoint +
								'?chart=' +
								charts[ 0 ].key
						) }
					>
						{ /* translators: %s is the chart type */
						sprintf( __( '%s Report', 'woocommerce-admin' ), charts[ 0 ].label ) }
					</a>
					<ReportChart
						endpoint={ endpoint }
						query={ query }
						interactiveLegend={ false }
						legendPosition="bottom"
						path={ path }
						selectedChart={ charts[ 0 ] }
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
};

export default ChartBlock;
