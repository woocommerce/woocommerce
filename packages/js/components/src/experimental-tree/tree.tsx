/**
 * External dependencies
 */
import classNames from 'classnames';
import { createElement, forwardRef } from 'react';

/**
 * Internal dependencies
 */
import { useTree } from './hooks/use-tree';
import { TreeItem } from './tree-item';
import { TreeProps } from './types';

export const Tree = forwardRef( function ForwardedTree(
	props: TreeProps,
	ref: React.ForwardedRef< HTMLOListElement >
) {
	const {
		items,
		selected,
		multiple,
		className,
		level = 1,
		role = 'tree',
		onSelect,
		onRemove,
		getItemLabel,
		isItemExpanded,
		isItemHighlighted,
		...olProps
	} = useTree( { ...props, ref } );

	if ( ! items?.length ) return null;
	return (
		<ol
			{ ...olProps }
			role={ role }
			className={ classNames(
				className,
				'experimental-woocommerce-tree',
				`experimental-woocommerce-tree--level-${ level }`
			) }
		>
			{ items.map( ( item, index ) => (
				<TreeItem
					key={ item.value }
					item={ item }
					index={ index }
					selected={ selected }
					level={ level }
					multiple={ multiple }
					onSelect={ onSelect }
					onRemove={ onRemove }
					getLabel={ getItemLabel }
					isExpanded={ isItemExpanded }
					isHighlighted={ isItemHighlighted }
				/>
			) ) }
		</ol>
	);
} );
