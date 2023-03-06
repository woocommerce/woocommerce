/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';
import { plus } from '@wordpress/icons';
import classNames from 'classnames';
import { ProductCategory } from '@woocommerce/data';
import { __experimentalSelectControlMenuItemProps as MenuItemProps } from '@woocommerce/components';
import { createElement } from '@wordpress/element';

type CategoryFieldAddNewItemProps = {
	item: Pick< ProductCategory, 'id' | 'name' >;
	highlightedIndex: number;
	items: Pick< ProductCategory, 'id' | 'name' >[];
} & Pick<
	MenuItemProps< Pick< ProductCategory, 'id' | 'name' > >,
	'getItemProps'
>;

export const CategoryFieldAddNewItem: React.FC<
	CategoryFieldAddNewItemProps
> = ( { item, highlightedIndex, getItemProps, items } ) => {
	const index = items.findIndex( ( i ) => i.id === item.id );
	return (
		<li
			{ ...getItemProps( {
				item,
				index,
			} ) }
			className={ classNames(
				'woocommerce-category-field-dropdown__item is-new',
				{
					item_highlighted: highlightedIndex === index,
				}
			) }
		>
			<div className="woocommerce-category-field-dropdown__item-content">
				<Icon
					className="category-field-dropdown__toggle"
					icon={ plus }
					size={ 20 }
				/>
				{ sprintf( __( 'Create "%s"', 'woocommerce' ), item.name ) }
			</div>
		</li>
	);
};
