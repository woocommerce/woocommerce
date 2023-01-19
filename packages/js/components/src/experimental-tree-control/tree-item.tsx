/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { createElement, forwardRef } from 'react';

/**
 * Internal dependencies
 */
import { useTreeItem } from './hooks/use-tree-item';
import { Tree } from './tree';
import { TreeItemProps } from './types';

export const TreeItem = forwardRef( function ForwardedTreeItem(
	props: TreeItemProps,
	ref: React.ForwardedRef< HTMLLIElement >
) {
	const { item, treeItemProps, headingProps, treeProps } = useTreeItem( {
		...props,
		ref,
	} );

	return (
		<li
			{ ...treeItemProps }
			className={ classNames(
				treeItemProps.className,
				'experimental-woocommerce-tree-item'
			) }
		>
			<div
				{ ...headingProps }
				className="experimental-woocommerce-tree-item__heading"
			>
				<div className="experimental-woocommerce-tree-item__label">
					<span>{ item.data.label }</span>
				</div>
			</div>

			{ Boolean( item.children.length ) && <Tree { ...treeProps } /> }
		</li>
	);
} );
