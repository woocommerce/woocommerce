/**
 * External dependencies
 */
import { Button, CheckboxControl } from '@wordpress/components';
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
		selection,
		highlighter,
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
				'experimental-woocommerce-tree-item',
				{
					'experimental-woocommerce-tree-item--highlighted':
						highlighter.highlighted,
				}
			) }
		>
			<div
				{ ...headingProps }
				className="experimental-woocommerce-tree-item__heading"
			>
				{ /* eslint-disable-next-line jsx-a11y/label-has-for */ }
				<label className="experimental-woocommerce-tree-item__label">
					{ selection.multiple ? (
						<CheckboxControl
							indeterminate={
								selection.checkedStatus === 'indeterminate'
							}
							checked={ selection.checkedStatus === 'checked' }
							onChange={ selection.onSelectChild }
						/>
					) : (
						<input
							type="radio"
							checked={ selection.checkedStatus === 'checked' }
							className="components-radio-control__input"
							onChange={ ( event ) =>
								selection.onSelectChild(
									event.currentTarget.checked
								)
							}
						/>
					) }

					{ typeof getLabel === 'function' ? (
						getLabel( item )
					) : (
						<span>{ item.data.label }</span>
					) }
				</label>

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
