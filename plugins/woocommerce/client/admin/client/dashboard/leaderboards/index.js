/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment, useState } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { SelectControl } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import {
	EllipsisMenu,
	MenuItem,
	MenuTitle,
	SectionHeader,
} from '@woocommerce/components';
import { useUserPreferences, ITEMS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import Leaderboard from '../../analytics/components/leaderboard';
import { getAdminSetting } from '~/utils/admin-settings';
import './style.scss';

const renderLeaderboardToggles = ( {
	allLeaderboards,
	hiddenBlocks,
	onToggleHiddenBlock,
} ) => {
	return allLeaderboards.map( ( leaderboard ) => {
		const checked = ! hiddenBlocks.includes( leaderboard.id );
		return (
			<MenuItem
				checked={ checked }
				isCheckbox
				isClickable
				key={ leaderboard.id }
				onInvoke={ () => {
					onToggleHiddenBlock( leaderboard.id )();
					recordEvent( 'dash_leaderboards_toggle', {
						status: checked ? 'off' : 'on',
						key: leaderboard.id,
					} );
				} }
			>
				{ leaderboard.label }
			</MenuItem>
		);
	} );
};

const renderLeaderboards = ( {
	allLeaderboards,
	hiddenBlocks,
	query,
	rowsPerTable,
	filters,
} ) => {
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
				filters={ filters }
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
		filters,
	} = props;
	const { updateUserPreferences, ...userPrefs } = useUserPreferences();
	const [ rowsPerTable, setRowsPerTableState ] = useState(
		parseInt( userPrefs.dashboard_leaderboard_rows || 5, 10 )
	);

	const setRowsPerTable = ( rows ) => {
		setRowsPerTableState( parseInt( rows, 10 ) );
		const userDataFields = {
			dashboard_leaderboard_rows: parseInt( rows, 10 ),
		};
		updateUserPreferences( userDataFields );
	};

	const renderMenu = () => (
		<EllipsisMenu
			label={ __(
				'Choose which leaderboards to display and other settings',
				'woocommerce'
			) }
			placement={ 'bottom-end' }
			renderContent={ ( { onToggle } ) => (
				<Fragment>
					<MenuTitle>
						{ __( 'Leaderboards', 'woocommerce' ) }
					</MenuTitle>
					{ renderLeaderboardToggles( {
						allLeaderboards,
						hiddenBlocks,
						onToggleHiddenBlock,
					} ) }
					<MenuItem>
						<SelectControl
							className="woocommerce-dashboard__dashboard-leaderboards__select"
							label={ __( 'Rows per table', 'woocommerce' ) }
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
					</MenuItem>
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
				</Fragment>
			) }
		/>
	);

	return (
		<Fragment>
			<div className="woocommerce-dashboard__dashboard-leaderboards">
				<SectionHeader
					title={ title || __( 'Leaderboards', 'woocommerce' ) }
					menu={ renderMenu() }
				/>
				<div className="woocommerce-dashboard__columns">
					{ renderLeaderboards( {
						allLeaderboards,
						hiddenBlocks,
						query,
						rowsPerTable,
						filters,
					} ) }
				</div>
			</div>
		</Fragment>
	);
};

Leaderboards.propTypes = {
	query: PropTypes.object.isRequired,
};

export default compose(
	withSelect( ( select ) => {
		const { getItems, getItemsError } = select( ITEMS_STORE_NAME );
		const { leaderboards: allLeaderboards } = getAdminSetting(
			'dataEndpoints',
			{
				leaderboards: [],
			}
		);

		return {
			allLeaderboards,
			getItems,
			getItemsError,
		};
	} )
)( Leaderboards );
