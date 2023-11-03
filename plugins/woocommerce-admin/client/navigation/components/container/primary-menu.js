/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { NavigationMenu, NavigationGroup } from '@woocommerce/experimental';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import CategoryTitle from '../category-title';
import Item from '../../components/Item';

export const PrimaryMenu = ( {
	category,
	onBackClick,
	pluginItems,
	primaryItems,
} ) => {
	if ( ! primaryItems.length && ! pluginItems.length ) {
		return null;
	}

	/**
	 * Navigation's exit button WooCommerce label.
	 *
	 * @filter woocommerce_navigation_root_back_label
	 * @param {string} label Back button label.
	 */
	const rootBackLabel = applyFilters(
		'woocommerce_navigation_root_back_label',
		__( 'WordPress Dashboard', 'woocommerce' )
	);

	/**
	 * Navigation's exit button url.
	 *
	 * @filter woocommerce_navigation_root_back_url
	 * @param {string} url Back button url.
	 */
	const rootBackUrl = applyFilters(
		'woocommerce_navigation_root_back_url',
		window.wcNavigation.rootBackUrl
	);

	const isRootBackVisible = category.id === 'woocommerce' && rootBackUrl;

	return (
		<NavigationMenu
			title={ <CategoryTitle category={ category } /> }
			menu={ category.id }
			parentMenu={ category.parent }
			backButtonLabel={
				isRootBackVisible
					? rootBackLabel
					: category.backButtonLabel || null
			}
			onBackButtonClick={
				isRootBackVisible
					? () => {
							onBackClick( 'woocommerce' );
							window.location = rootBackUrl;
					  }
					: () => onBackClick( category.id )
			}
		>
			{ !! primaryItems.length && (
				<NavigationGroup>
					{ primaryItems.map( ( item ) => (
						<Item key={ item.id } item={ item } />
					) ) }
				</NavigationGroup>
			) }
			{ !! pluginItems.length && (
				<NavigationGroup
					title={
						category.id === 'woocommerce'
							? __( 'Extensions', 'woocommerce' )
							: null
					}
				>
					{ pluginItems.map( ( item ) => (
						<Item key={ item.id } item={ item } />
					) ) }
				</NavigationGroup>
			) }
		</NavigationMenu>
	);
};
