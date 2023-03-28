/**
 * External dependencies
 */
import { CheckboxControl, Icon } from '@wordpress/components';
import { useEffect, useState, createElement } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { chevronDown, chevronUp } from '@wordpress/icons';
import { ProductCategory } from '@woocommerce/data';
import { __experimentalSelectControlMenuItemProps as MenuItemProps } from '@woocommerce/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { ProductCategoryLinkedList } from './use-category-search';

export type CategoryTreeItem = {
	data: ProductCategory;
	children: CategoryTreeItem[];
	parentID: number;
	isOpen: boolean;
};

type CategoryFieldItemProps = {
	item: CategoryTreeItem;
	selectedIds: number[];
	items: ProductCategoryLinkedList[];
	highlightedIndex: number;
	openParent?: () => void;
} & Pick< MenuItemProps< ProductCategoryLinkedList >, 'getItemProps' >;

export const CategoryFieldItem: React.FC< CategoryFieldItemProps > = ( {
	item,
	selectedIds = [],
	items,
	highlightedIndex,
	openParent,
	getItemProps,
} ) => {
	const [ isOpen, setIsOpen ] = useState( item.isOpen || false );
	const index = items.findIndex( ( i ) => i.id === item.data.id );
	const children = item.children.filter( ( child ) =>
		items.includes( child.data )
	);

	useEffect( () => {
		if ( highlightedIndex === index && children.length > 0 && ! isOpen ) {
			setIsOpen( true );
		} else if ( highlightedIndex === index && openParent ) {
			// Make sure the parent is also open when the item is highlighted.
			openParent();
		}
	}, [ highlightedIndex ] );

	useEffect( () => {
		if ( item.isOpen !== isOpen ) {
			setIsOpen( item.isOpen );
		}
	}, [ item.isOpen ] );

	return (
		<li
			className={ classNames(
				'woocommerce-category-field-dropdown__item',
				{
					item_highlighted: index === highlightedIndex,
				}
			) }
		>
			<div
				className="woocommerce-category-field-dropdown__item-content"
				{ ...getItemProps( {
					item: item.data,
					index,
				} ) }
			>
				{ children.length > 0 ? (
					<Icon
						className="woocommerce-category-field-dropdown__toggle"
						icon={ isOpen ? chevronUp : chevronDown }
						size={ 20 }
						onClick={ ( e: React.MouseEvent ) => {
							e.stopPropagation();
							setIsOpen( ! isOpen );
						} }
					/>
				) : (
					<div className="woocommerce-category-field-dropdown__toggle-placeholder"></div>
				) }
				<CheckboxControl
					label={ decodeEntities( item.data.name ) }
					checked={ selectedIds.includes( item.data.id ) }
					onChange={ () => item.data }
				/>
			</div>
			{ children.length > 0 ? (
				<ul
					className={ classNames(
						'woocommerce-category-field-dropdown__item-children',
						{
							'woocommerce-category-field-dropdown__item-open':
								isOpen,
						}
					) }
				>
					{ children.map( ( child ) => (
						<CategoryFieldItem
							key={ child.data.id }
							item={ child }
							selectedIds={ selectedIds }
							items={ items }
							highlightedIndex={ highlightedIndex }
							openParent={ () => ! isOpen && setIsOpen( true ) }
							getItemProps={ getItemProps }
						/>
					) ) }
				</ul>
			) : null }
		</li>
	);
};
