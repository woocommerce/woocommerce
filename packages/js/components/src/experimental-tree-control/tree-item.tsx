/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronDown, chevronUp } from '@wordpress/icons';
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
	const {
		item,
		treeItemProps,
		headingProps,
		treeProps,
		expander: { isExpanded, onToggleExpand },
		getLabel,
	} = useTreeItem( {
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
					{ typeof getLabel === 'function' ? (
						getLabel( item )
					) : (
						<span>{ item.data.label }</span>
					) }
				</div>

				{ Boolean( item.children?.length ) && (
					<div className="experimental-woocommerce-tree-item__expander">
						<Button
							icon={ isExpanded ? chevronUp : chevronDown }
							onClick={ onToggleExpand }
							className="experimental-woocommerce-tree-item__expander"
							aria-label={
								isExpanded
									? __( 'Collapse', 'woocommerce' )
									: __( 'Expand', 'woocommerce' )
							}
						/>
					</div>
				) }
			</div>

			{ Boolean( item.children.length ) && isExpanded && (
				<Tree { ...treeProps } />
			) }
		</li>
	);
} );
