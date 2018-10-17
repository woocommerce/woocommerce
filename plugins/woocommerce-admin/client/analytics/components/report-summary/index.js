/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { map } from 'lodash';
import { withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { formatCurrency } from 'lib/currency';
import { getNewPath } from 'lib/nav-utils';
import { SummaryList, SummaryListPlaceholder, SummaryNumber } from '@woocommerce/components';
import { getDateParamsFromQuery } from 'lib/date';
import { getSummaryNumbers } from 'store/reports/utils';
import ReportError from 'analytics/components/report-error';
class ReportSummary extends Component {
	render() {
		const { selectedChart, charts } = this.props;

		if ( this.props.summaryNumbers.isError ) {
			return <ReportError isError />;
		}

		if ( this.props.summaryNumbers.isRequesting ) {
			return <SummaryListPlaceholder numberOfItems={ charts.length } />;
		}

		const totals = this.props.summaryNumbers.totals.primary || {};
		const secondaryTotals = this.props.summaryNumbers.totals.secondary || {};
		const { compare } = getDateParamsFromQuery( this.props.query );

		const summaryNumbers = map( charts, chart => {
			const { key, label, type } = chart;
			const isSelected = selectedChart.key === key;
			let value = parseFloat( totals[ key ] );
			let secondaryValue =
				( secondaryTotals[ key ] && parseFloat( secondaryTotals[ key ] ) ) || undefined;

			let delta = 0;
			if ( secondaryValue && secondaryValue !== 0 ) {
				delta = Math.round( ( value - secondaryValue ) / secondaryValue * 100 );
			}

			switch ( type ) {
				case 'average':
					value = Math.round( value );
					secondaryValue = secondaryValue && Math.round( secondaryValue );
					break;
				case 'currency':
					value = formatCurrency( value );
					secondaryValue = secondaryValue && formatCurrency( secondaryValue );
					break;
				case 'number':
					break;
			}

			const href = getNewPath( { chart: key } );

			return (
				<SummaryNumber
					key={ key }
					value={ value }
					label={ label }
					selected={ isSelected }
					prevValue={ secondaryValue }
					prevLabel={
						'previous_period' === compare
							? __( 'Previous Period:', 'wc-admin' )
							: __( 'Previous Year:', 'wc-admin' )
					}
					delta={ delta }
					href={ href }
				/>
			);
		} );

		return <SummaryList>{ summaryNumbers }</SummaryList>;
	}
}

ReportSummary.propTypes = {
	charts: PropTypes.array.isRequired,
	endpoint: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
	selectedChart: PropTypes.object.isRequired,
};

export default compose(
	withSelect( ( select, props ) => {
		const { query, endpoint } = props;
		const summaryNumbers = getSummaryNumbers( endpoint, query, select );

		return {
			summaryNumbers,
		};
	} )
)( ReportSummary );
