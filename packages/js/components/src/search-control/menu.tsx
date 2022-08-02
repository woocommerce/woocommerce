/**
 * External dependencies
 */
import { createElement } from 'react';

/**
 * Internal dependencies
 */
import { ChildrenType, ItemType, Props, getItemPropsType } from './types';
import { MenuItem } from './menu-item';
import './menu.scss';

type MenuProps = {
	children?: ChildrenType;
	menuProps: Props;
	highlightedIndex: number;
	isOpen: boolean;
	items: ItemType[];
	getItemProps: getItemPropsType;
};

export const Menu = ( {
	children,
	menuProps,
	highlightedIndex,
	isOpen,
	items,
	getItemProps,
}: MenuProps ) => {
	if ( children ) {
		return (
			<ul { ...menuProps }>
				{ children( {
					highlightedIndex,
					isOpen,
					items,
					getItemProps,
				} ) }
			</ul>
		);
	}

	return (
		<ul { ...menuProps }>
			{ isOpen &&
				items.map( ( item, index: number ) => (
					<MenuItem
						key={ `${ item.value }${ index }` }
						index={ index }
						isActive={ highlightedIndex === index }
						item={ item }
						getItemProps={ getItemProps }
					>
						{ item.label }
					</MenuItem>
				) ) }
		</ul>
	);
};
