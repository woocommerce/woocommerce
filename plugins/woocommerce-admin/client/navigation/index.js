/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { withSelect } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './style.scss';

class Navigation extends Component {
	renderMenuItem( item, depth = 0 ) {
		const { slug, title, url } = item;

		return (
			<li
				key={ slug }
				className={ `woocommerce-navigation__menu-item woocommerce-navigation__menu-item-depth-${ depth }` }
			>
				<a href={ url }>{ title }</a>
				{ item.children && item.children.length && (
					<ul className="woocommerce-navigation__submenu">
						{ item.children.map( ( childItem ) => {
							return this.renderMenuItem( childItem, depth + 1 );
						} ) }
					</ul>
				) }
			</li>
		);
	}

	render() {
		const { items } = this.props;

		return (
			<div className="woocommerce-navigation">
				<ul className="woocommerce-navigation__menu">
					{ items.map( ( item ) => {
						return this.renderMenuItem( item );
					} ) }
				</ul>
			</div>
		);
	}
}

export default withSelect( ( select ) => {
	const items = select( SETTINGS_STORE_NAME ).getSetting(
		'wc_admin',
		'wcNavigation'
	);
	return { items };
} )( Navigation );
