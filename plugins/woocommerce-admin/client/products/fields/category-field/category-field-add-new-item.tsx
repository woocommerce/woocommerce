/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';
import { plus } from '@wordpress/icons';
import classNames from 'classnames';
import { ProductCategory } from '@woocommerce/data';
import { __experimentalSelectControlMenuItemProps as MenuItemProps } from '@woocommerce/components';

type CategoryFieldAddNewItemProps = {
	item: Pick< ProductCategory, 'id' | 'name' >;
	highlighted: boolean;
	index: number;
} & Pick<
	MenuItemProps< Pick< ProductCategory, 'id' | 'name' > >,
	'getItemProps'
>;

export const CategoryFieldAddNewItem: React.FC<
	CategoryFieldAddNewItemProps
> = ( { item, highlighted, getItemProps, index } ) => {
	return (
		<div
			{ ...getItemProps( {
				item,
				index,
			} ) }
			className={ classNames(
				'category-field-dropdown__item category-field-dropdown__new-item',
				{
					item_highlighted: highlighted,
				}
			) }
		>
			<Icon
				className="category-field-dropdown__toggle"
				icon={ plus }
				size={ 24 }
			/>
			{ sprintf( __( 'Create "%s"', 'woocommerce' ), item.name ) }
		</div>
	);
};
