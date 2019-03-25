/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { xor } from 'lodash';
import PropTypes from 'prop-types';
import { SelectControl } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { EllipsisMenu, MenuItem, MenuTitle, SectionHeader } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';
import TopSellingCategories from './top-selling-categories';
import TopSellingProducts from './top-selling-products';
import TopCoupons from './top-coupons';
import TopCustomers from './top-customers';
import './style.scss';

class Leaderboards extends Component {
	constructor( props ) {
		super( ...arguments );
		this.state = {
			hiddenLeaderboardKeys: props.userPrefLeaderboards || [],
			rowsPerTable: parseInt( props.userPrefLeaderboardRows ) || 5,
		};

		this.toggle = this.toggle.bind( this );
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
				label: __( 'Top Products - Items Sold', 'woocommerce-admin' ),
			},
			{
				key: 'top-categories',
				label: __( 'Top Categories - Items Sold', 'woocommerce-admin' ),
			},
			{
				key: 'top-coupons',
				label: __( 'Top Coupons', 'woocommerce-admin' ),
			},
			{
				key: 'top-customers',
				label: __( 'Top Customers', 'woocommerce-admin' ),
			},
		];
		return (
			<EllipsisMenu
				label={ __(
					'Choose which leaderboards to display and the number of rows',
					'woocommerce-admin'
				) }
			>
				<Fragment>
					<MenuTitle>{ __( 'Leaderboards', 'woocommerce-admin' ) }</MenuTitle>
					{ allLeaderboards.map( leaderboard => {
						return (
							<MenuItem
								checked={ ! hiddenLeaderboardKeys.includes( leaderboard.key ) }
								isCheckbox
								isClickable
								key={ leaderboard.key }
								onInvoke={ this.toggle( leaderboard.key ) }
							>
								{ leaderboard.label }
							</MenuItem>
						);
					} ) }
					<SelectControl
						className="woocommerce-dashboard__dashboard-leaderboards__select"
						label={ <MenuTitle>{ __( 'Rows Per Table', 'woocommerce-admin' ) }</MenuTitle> }
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
		const { query } = this.props;
		return (
			<Fragment>
				<div className="woocommerce-dashboard__dashboard-leaderboards">
					<SectionHeader
						title={ __( 'Leaderboards', 'woocommerce-admin' ) }
						menu={ this.renderMenu() }
					/>
					<div className="woocommerce-dashboard__columns">
						{ ! hiddenLeaderboardKeys.includes( 'top-products' ) && (
							<TopSellingProducts query={ query } totalRows={ rowsPerTable } />
						) }
						{ ! hiddenLeaderboardKeys.includes( 'top-categories' ) && (
							<TopSellingCategories query={ query } totalRows={ rowsPerTable } />
						) }
						{ ! hiddenLeaderboardKeys.includes( 'top-coupons' ) && (
							<TopCoupons query={ query } totalRows={ rowsPerTable } />
						) }
						{ ! hiddenLeaderboardKeys.includes( 'top-customers' ) && (
							<TopCustomers query={ query } totalRows={ rowsPerTable } />
						) }
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
