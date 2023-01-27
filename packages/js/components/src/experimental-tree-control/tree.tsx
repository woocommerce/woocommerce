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
	const { level, items, treeProps, treeItemProps } = useTree( {
		...props,
		ref,
	} );

	if ( ! items.length ) return null;
	return (
		<ol
			{ ...treeProps }
			className={ classNames(
				treeProps.className,
				'experimental-woocommerce-tree',
				`experimental-woocommerce-tree--level-${ level }`
			) }
		>
			{ items.map( ( child ) => (
				<TreeItem
					{ ...treeItemProps }
					key={ child.data.value }
					item={ child }
				/>
			) ) }
		</ol>
	);
} );
