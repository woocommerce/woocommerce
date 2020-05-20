/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * WooCommerce dependencies
 */
import {
	SummaryNumber,
	SummaryNumberPlaceholder,
} from '@woocommerce/components';
import { getPersistedQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';
import { CurrencyContext } from 'lib/currency-context';
import {
	getIndicatorData,
	getIndictorValues,
} from 'dashboard/store-performance/utils';

export const StatsList = ( {
	stats,
	primaryData,
	secondaryData,
	primaryRequesting,
	secondaryRequesting,
	primaryError,
	secondaryError,
	query,
} ) => {
	const { formatCurrency, getCurrency } = useContext( CurrencyContext );
	if ( primaryError || secondaryError ) {
		return null;
	}
	const persistedQuery = getPersistedQuery( query );
	const currency = getCurrency();

	return (
		<ul
			className={ classnames( 'woocommerce-stats-overview__stats', {
				'is-even': stats.length % 2 === 0,
			} ) }
		>
			{ stats.map( ( item ) => {
				if ( primaryRequesting || secondaryRequesting ) {
					return (
						<SummaryNumberPlaceholder
							className="is-homepage"
							key={ item.stat }
						/>
					);
				}
				const {
					primaryValue,
					secondaryValue,
					delta,
					reportUrl,
				} = getIndictorValues( {
					indicator: item,
					primaryData,
					secondaryData,
					currency,
					formatCurrency,
					persistedQuery,
				} );

				return (
					<SummaryNumber
						isHomepage
						key={ item.stat }
						href={ reportUrl }
						label={ item.label }
						value={ primaryValue }
						prevLabel={ __(
							'Previous Period:',
							'woocommerce-admin'
						) }
						prevValue={ secondaryValue }
						delta={ delta }
						onLinkClickCallback={ () => {
							recordEvent( 'statsoverview_indicators_click', {
								key: item.stat,
							} );
						} }
					/>
				);
			} ) }
		</ul>
	);
};

export default withSelect( ( select, { stats, query } ) => {
	if ( stats.length === 0 ) {
		return;
	}
	return getIndicatorData( select, stats, query );
} )( StatsList );
