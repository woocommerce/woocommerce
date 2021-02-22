/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { NavigationMenu, NavigationGroup } from '@woocommerce/experimental';

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

	const { rootBackLabel, rootBackUrl } = window.wcNavigation;
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
							? __( 'Extensions', 'woocommerce-admin' )
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
