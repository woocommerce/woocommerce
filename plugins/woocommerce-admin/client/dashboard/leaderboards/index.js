/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment, useState } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { SelectControl } from '@wordpress/components';

/**
 * WooCommerce dependencies
 */
import {
	EllipsisMenu,
	MenuItem,
	MenuTitle,
	SectionHeader,
} from '@woocommerce/components';
import { useUserPreferences } from '@woocommerce/data';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import Leaderboard from 'analytics/components/leaderboard';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';
import './style.scss';

const renderLeaderboardToggles = ( { allLeaderboards, hiddenBlocks, onToggleHiddenBlock } ) => {
	return allLeaderboards.map( ( leaderboard ) => {
		const checked = ! hiddenBlocks.includes(
			leaderboard.id
		);
		return (
			<MenuItem
				checked={ checked }
				isCheckbox
				isClickable
				key={ leaderboard.id }
				onInvoke={ () => {
					onToggleHiddenBlock( leaderboard.id )();
					recordEvent(
						'dash_leaderboards_toggle',
						{
							status: checked ? 'off' : 'on',
							key: leaderboard.id,
						}
					);
				} }
			>
				{ leaderboard.label }
			</MenuItem>
		);
	} );
};

const renderLeaderboards = ( { allLeaderboards, hiddenBlocks, query, rowsPerTable } ) => {
	return allLeaderboards.map( ( leaderboard ) => {
		if ( hiddenBlocks.includes( leaderboard.id ) ) {
			return undefined;
		}

		return (
			<Leaderboard
				headers={ leaderboard.headers }
				id={ leaderboard.id }
				key={ leaderboard.id }
				query={ query }
				title={ leaderboard.label }
				totalRows={ rowsPerTable }
			/>
		);
	} );
};

const Leaderboards = ( props ) => {
	const {
		allLeaderboards,
		controls: Controls,
		isFirst,
		isLast,
		hiddenBlocks,
		onMove,
		onRemove,
		onTitleBlur,
		onTitleChange,
		onToggleHiddenBlock,
		query,
		title,
		titleInput,
	} = props;
	const { updateUserPreferences, ...userPrefs } = useUserPreferences();
	const [ rowsPerTable, setRowsPerTableState ] = useState( parseInt( userPrefs.dashboard_leaderboard_rows || 5, 10 ) );

	const setRowsPerTable = ( rows ) => {
		setRowsPerTableState( parseInt( rows, 10 ) );
		const userDataFields = {
			dashboard_leaderboard_rows: parseInt( rows, 10 ),
		};
		updateUserPreferences( userDataFields );
	};

	const renderMenu = () =>  (
		<EllipsisMenu
			label={ __(
				'Choose which leaderboards to display and other settings',
				'woocommerce-admin'
			) }
			renderContent={ ( { onToggle } ) => (
				<Fragment>
					<MenuTitle>
						{ __( 'Leaderboards', 'woocommerce-admin' ) }
					</MenuTitle>
					{ renderLeaderboardToggles( { allLeaderboards, hiddenBlocks, onToggleHiddenBlock } ) }
					<SelectControl
						className="woocommerce-dashboard__dashboard-leaderboards__select"
						label={ __(
							'Rows Per Table',
							'woocommerce-admin'
						) }
						value={ rowsPerTable }
						options={ Array.from(
							{ length: 20 },
							( v, key ) => ( {
								v: key + 1,
								label: key + 1,
							} )
						) }
						onChange={ setRowsPerTable }
					/>
					{ window.wcAdminFeatures[
						'analytics-dashboard/customizable'
					] && (
						<Controls
							onToggle={ onToggle }
							onMove={ onMove }
							onRemove={ onRemove }
							isFirst={ isFirst }
							isLast={ isLast }
							onTitleBlur={ onTitleBlur }
							onTitleChange={ onTitleChange }
							titleInput={ titleInput }
						/>
					) }
				</Fragment>
			) }
		/>
	);

	return (
		<Fragment>
			<div className="woocommerce-dashboard__dashboard-leaderboards">
				<SectionHeader
					title={
						title || __( 'Leaderboards', 'woocommerce-admin' )
					}
					menu={ renderMenu() }
				/>
				<div className="woocommerce-dashboard__columns">
					{ renderLeaderboards( { allLeaderboards, hiddenBlocks, query, rowsPerTable } ) }
				</div>
			</div>
		</Fragment>
	);
}

Leaderboards.propTypes = {
	query: PropTypes.object.isRequired,
};

export default compose(
	withSelect( ( select ) => {
		const {
			getItems,
			getItemsError,
			isGetItemsRequesting,
		} = select( 'wc-api' );
		const { leaderboards: allLeaderboards } = getSetting( 'dataEndpoints', {
			leaderboards: [],
		} );

		return {
			allLeaderboards,
			getItems,
			getItemsError,
			isGetItemsRequesting,
		};
	} )
)( Leaderboards );
