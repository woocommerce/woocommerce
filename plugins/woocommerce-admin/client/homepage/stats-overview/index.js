/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { TabPanel } from '@wordpress/components';
import { xor } from 'lodash';
import { withDispatch } from '@wordpress/data';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import {
	Card,
	EllipsisMenu,
	MenuItem,
	MenuTitle,
	Link,
} from '@woocommerce/components';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './style.scss';
import withSelect from 'wc-api/with-select';
import { DEFAULT_STATS, DEFAULT_HIDDEN_STATS } from './defaults';
import StatsList from './stats-list';
import { recordEvent } from 'lib/tracks';

const { performanceIndicators } = getSetting( 'dataEndpoints', {
	performanceIndicators: [],
} );
const stats = performanceIndicators.filter( ( indicator ) => {
	return DEFAULT_STATS.includes( indicator.stat );
} );

export const StatsOverview = ( { userPrefs, updateCurrentUserData } ) => {
	const userHiddenStats = userPrefs.hiddenStats;
	const hiddenStats = userHiddenStats
		? userHiddenStats
		: DEFAULT_HIDDEN_STATS;

	const toggleStat = ( stat ) => {
		const nextHiddenStats = xor( hiddenStats, [ stat ] );
		updateCurrentUserData( {
			homepage_stats: { hiddenStats: nextHiddenStats },
		} );
		recordEvent( 'statsoverview_indicators_toggle', {
			indicator_name: stat,
			status: nextHiddenStats.includes( stat ) ? 'off' : 'on',
		} );
	};

	const activeStats = stats.filter(
		( item ) => ! hiddenStats.includes( item.stat )
	);

	return (
		<Card
			className="woocommerce-analytics__card woocommerce-stats-overview"
			title={ __( 'Stats overview', 'woocommerce-admin' ) }
			menu={
				<EllipsisMenu
					label={ __(
						'Choose which values to display',
						'woocommerce-admin'
					) }
					renderContent={ () => (
						<Fragment>
							<MenuTitle>
								{ __( 'Display stats:', 'woocommerce-admin' ) }
							</MenuTitle>
							{ stats.map( ( item ) => {
								const checked = ! hiddenStats.includes(
									item.stat
								);

								return (
									<MenuItem
										checked={ checked }
										isCheckbox
										isClickable
										key={ item.stat }
										onInvoke={ () =>
											toggleStat( item.stat )
										}
									>
										{ item.label }
									</MenuItem>
								);
							} ) }
						</Fragment>
					) }
				/>
			}
		>
			<TabPanel
				className="woocommerce-stats-overview__tabs"
				tabs={ [
					{
						title: __( 'Today', 'woocommerce-admin' ),
						name: 'today',
					},
					{
						title: __( 'Week to date', 'woocommerce-admin' ),
						name: 'week',
					},
					{
						title: __( 'Month to date', 'woocommerce-admin' ),
						name: 'month',
					},
				] }
			>
				{ ( tab ) => (
					<StatsList
						query={ {
							period: tab.name,
							compare: 'previous_period',
						} }
						stats={ activeStats }
					/>
				) }
			</TabPanel>
			<div className="woocommerce-stats-overview__footer">
				<Link
					className="woocommerce-stats-overview__more-btn"
					href={ getNewPath( {}, '/analytics/overview' ) }
					type="wc-admin"
					onClick={ () => {
						recordEvent( 'statsoverview_indicators_click', {
							key: 'view_detailed_stats',
						} );
					} }
				>
					{ __( 'View detailed stats' ) }
				</Link>
			</div>
		</Card>
	);
};

StatsOverview.propTypes = {
	/**
	 * Homepage user preferences.
	 */
	userPrefs: PropTypes.object.isRequired,
	/**
	 * A method to update user meta.
	 */
	updateCurrentUserData: PropTypes.func.isRequired,
};

export default compose(
	withSelect( ( select ) => {
		const { getCurrentUserData } = select( 'wc-api' );

		return {
			userPrefs: getCurrentUserData().homepage_stats || {},
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( StatsOverview );
