/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { xor } from 'lodash';
import { withDispatch } from '@wordpress/data';
import PropTypes from 'prop-types';
import { recordEvent } from 'lib/tracks';

/**
 * WooCommerce dependencies
 */
import {
	Card,
	EllipsisMenu,
	MenuItem,
	MenuTitle,
} from '@woocommerce/components';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';
import { DEFAULT_STATS, DEFAULT_HIDDEN_STATS } from './defaults';

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

	return (
		<Card
			className="woocommerce-analytics__card"
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
			Content Here
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
