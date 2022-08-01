/**
 * External dependencies
 */
import { createElement } from 'react';

/**
 * Internal dependencies
 */
import { ChildrenType, ItemType, Props, getItemPropsType } from './types';

type MenuProps = {
	children?: ChildrenType;
	menuProps: Props;
	highlightedIndex: number;
	isOpen: boolean;
	items: ItemType[];
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore These are the types provided by Downshift.
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
	return (
		<ul { ...menuProps }>
			{ children &&
				children( { highlightedIndex, isOpen, items, getItemProps } ) }
			{ ! children &&
				isOpen &&
				items.map( ( item, index: number ) => (
					<li
						style={
							highlightedIndex === index
								? { backgroundColor: '#bde4ff' }
								: {}
						}
						key={ `${ item.value }${ index }` }
						{ ...getItemProps( { item, index } ) }
					>
						{ item.value }
					</li>
				) ) }
		</ul>
	);
};
