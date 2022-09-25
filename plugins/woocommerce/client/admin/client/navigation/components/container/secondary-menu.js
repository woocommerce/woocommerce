/**
 * External dependencies
 */
import { NavigationMenu, NavigationGroup } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import CategoryTitle from '../category-title';
import Item from '../../components/Item';

export const SecondaryMenu = ( { category, items, onBackClick } ) => {
	if ( ! items.length ) {
		return null;
	}

	const isRoot = category.id === 'woocommerce';

	return (
		<NavigationMenu
			className="components-navigation__menu-secondary"
			title={ ! isRoot && <CategoryTitle category={ category } /> }
			menu={ category.id }
			parentMenu={ category.parent }
			backButtonLabel={ category.backButtonLabel || null }
			onBackButtonClick={
				isRoot ? null : () => onBackClick( category.id )
			}
		>
			<NavigationGroup
				onBackButtonClick={ () => onBackClick( category.id ) }
			>
				{ items.map( ( item ) => (
					<Item key={ item.id } item={ item } />
				) ) }
			</NavigationGroup>
		</NavigationMenu>
	);
};
