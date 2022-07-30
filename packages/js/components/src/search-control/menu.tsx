/**
 * External dependencies
 */
import { createElement } from 'react';

/**
 * Internal dependencies
 */
import { ItemType, Props } from './types';

type MenuProps = {
	menuProps: Props;
	highlightedIndex: number;
	isOpen: boolean;
	items: ItemType[];
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore These are the types provided by Downshift.
	getItemProps: ( { item: any, index: any } ) => Props;
};

export const Menu = ( {
	menuProps,
	highlightedIndex,
	isOpen,
	items,
	getItemProps,
}: MenuProps ) => {
	return (
		<ul { ...menuProps }>
			{ isOpen &&
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
