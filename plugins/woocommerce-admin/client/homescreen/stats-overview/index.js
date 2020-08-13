/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import {
	TabPanel,
	Card,
	CardHeader,
	CardBody,
	CardFooter,
	__experimentalText as Text,
} from '@wordpress/components';
import { get, xor } from 'lodash';
import {
	EllipsisMenu,
	MenuItem,
	MenuTitle,
	Link,
} from '@woocommerce/components';
import { useUserPreferences } from '@woocommerce/data';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './style.scss';
import { DEFAULT_STATS, DEFAULT_HIDDEN_STATS } from './defaults';
import StatsList from './stats-list';
import { recordEvent } from '../../lib/tracks';
import InstallJetpackCta from './install-jetpack-cta';

const { performanceIndicators } = getSetting( 'dataEndpoints', {
	performanceIndicators: [],
} );
const stats = performanceIndicators.filter( ( indicator ) => {
	return DEFAULT_STATS.includes( indicator.stat );
} );

export const StatsOverview = () => {
	const { updateUserPreferences, ...userPrefs } = useUserPreferences();
	const hiddenStats = get(
		userPrefs,
		[ 'homepage_stats', 'hiddenStats' ],
		DEFAULT_HIDDEN_STATS
	);

	const toggleStat = ( stat ) => {
		const nextHiddenStats = xor( hiddenStats, [ stat ] );
		updateUserPreferences( {
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
			size="large"
			className="woocommerce-stats-overview woocommerce-homescreen-card"
		>
			<CardHeader size="medium">
				<Text variant="title.small">
					{ __( 'Stats overview', 'woocommerce-admin' ) }
				</Text>
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
			</CardHeader>
			<CardBody>
				<TabPanel
					className="woocommerce-stats-overview__tabs"
					onSelect={ ( period ) => {
						recordEvent( 'statsoverview_date_picker_update', {
							period,
						} );
					} }
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
						<Fragment>
							<InstallJetpackCta />
							<StatsList
								query={ {
									period: tab.name,
									compare: 'previous_period',
								} }
								stats={ activeStats }
							/>
						</Fragment>
					) }
				</TabPanel>
			</CardBody>
			<CardFooter>
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
			</CardFooter>
		</Card>
	);
};

export default StatsOverview;
