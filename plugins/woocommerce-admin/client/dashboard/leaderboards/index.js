/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { isEqual, xor } from 'lodash';
import PropTypes from 'prop-types';
import { SelectControl, ToggleControl } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { EllipsisMenu, MenuItem, MenuTitle, SectionHeader } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';
import TopSellingProducts from './top-selling-products';
import './style.scss';

class Leaderboards extends Component {
	constructor( props ) {
		super( ...arguments );
		this.state = {
			hiddenLeaderboardKeys: props.userPrefLeaderboards || [],
			rowsPerTable: props.userPrefLeaderboardRows || 5,
		};

		this.toggle = this.toggle.bind( this );
	}

	componentDidUpdate( {
		userPrefLeaderboardRows: prevUserPrefLeaderboardRows,
		userPrefLeaderboards: prevUserPrefLeaderboards,
	} ) {
		const { userPrefLeaderboardRows, userPrefLeaderboards } = this.props;
		if ( userPrefLeaderboards && ! isEqual( userPrefLeaderboards, prevUserPrefLeaderboards ) ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				hiddenLeaderboardKeys: userPrefLeaderboards,
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
		if (
			userPrefLeaderboardRows &&
			parseInt( userPrefLeaderboardRows ) !== parseInt( prevUserPrefLeaderboardRows )
		) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				rowsPerTable: parseInt( userPrefLeaderboardRows ),
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	toggle( key ) {
		return () => {
			const hiddenLeaderboardKeys = xor( this.state.hiddenLeaderboardKeys, [ key ] );
			this.setState( { hiddenLeaderboardKeys } );
			const userDataFields = {
				[ 'dashboard_leaderboards' ]: hiddenLeaderboardKeys,
			};
			this.props.updateCurrentUserData( userDataFields );
		};
	}

	setRowsPerTable = rows => {
		this.setState( { rowsPerTable: parseInt( rows ) } );
		const userDataFields = {
			[ 'dashboard_leaderboard_rows' ]: parseInt( rows ),
		};
		this.props.updateCurrentUserData( userDataFields );
	};

	renderMenu() {
		const { hiddenLeaderboardKeys, rowsPerTable } = this.state;
		const allLeaderboards = [
			{
				key: 'top-products',
				label: __( 'Top Products', 'wc-admin' ),
			},
			{
				key: 'top-categories',
				label: __( 'Top Categories', 'wc-admin' ),
			},
			{
				key: 'top-coupons',
				label: __( 'Top Coupons', 'wc-admin' ),
			},
		];
		return (
			<EllipsisMenu label={ __( 'Choose which leaderboards to display', 'wc-admin' ) }>
				<Fragment>
					<MenuTitle>{ __( 'Leaderboards', 'wc-admin' ) }</MenuTitle>
					{ allLeaderboards.map( leaderboard => {
						return (
							<MenuItem onInvoke={ this.toggle( leaderboard.key ) } key={ leaderboard.key }>
								<ToggleControl
									label={ leaderboard.label }
									checked={ ! hiddenLeaderboardKeys.includes( leaderboard.key ) }
									onChange={ this.toggle( leaderboard.key ) }
								/>
							</MenuItem>
						);
					} ) }
					<MenuTitle>{ __( 'Rows Per Table', 'wc-admin' ) }</MenuTitle>
					<SelectControl
						className="woocommerce-ellipsis-menu__item"
						value={ rowsPerTable }
						options={ Array.from( { length: 20 }, ( v, key ) => ( {
							v: key + 1,
							label: key + 1,
						} ) ) }
						onChange={ this.setRowsPerTable }
					/>
				</Fragment>
			</EllipsisMenu>
		);
	}

	render() {
		const { hiddenLeaderboardKeys, rowsPerTable } = this.state;
		return (
			<Fragment>
				<div className="woocommerce-dashboard__dashboard-leaderboards">
					<SectionHeader title={ __( 'Leaderboards', 'wc-admin' ) } menu={ this.renderMenu() } />
					<div className="woocommerce-dashboard__columns">
						<div>
							{ ! hiddenLeaderboardKeys.includes( 'top-products' ) && (
								<TopSellingProducts query={ this.props.query } totalRows={ rowsPerTable } />
							) }
						</div>
					</div>
				</div>
			</Fragment>
		);
	}
}

Leaderboards.propTypes = {
	query: PropTypes.object.isRequired,
};

export default compose(
	withSelect( select => {
		const { getCurrentUserData } = select( 'wc-api' );
		const userData = getCurrentUserData();

		return {
			userPrefLeaderboards: userData.dashboard_leaderboards,
			userPrefLeaderboardRows: userData.dashboard_leaderboard_rows,
		};
	} ),
	withDispatch( dispatch => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( Leaderboards );
