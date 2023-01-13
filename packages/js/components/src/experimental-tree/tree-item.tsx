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
import './tree-item.scss';

export const TreeItem = forwardRef( function ForwardedTreeItem(
	props: TreeItemProps,
	ref: React.ForwardedRef< HTMLLIElement >
) {
	const {
		item,
		multiple,
		expanded,
		checkedStatus,
		highlighted,
		getLabel,
		onToggleExpand,
		onSelectChild,

		treeItem,
		headingProps,
		treeProps,
	} = useTreeItem( { ...props, ref } );

	return (
		<li
			{ ...treeItem }
			className={ classNames(
				treeItem.className,
				'experimental-woocommerce-tree-item',
				{
					'experimental-woocommerce-tree-item--highlighted':
						highlighted,
				}
			) }
			role="none"
		>
			<div
				{ ...headingProps }
				className="experimental-woocommerce-tree-item__heading"
				role="treeitem"
			>
				{ /* eslint-disable-next-line jsx-a11y/label-has-for */ }
				<label className="experimental-woocommerce-tree-item__label">
					{ multiple ? (
						<CheckboxControl
							indeterminate={ checkedStatus === 'indeterminate' }
							checked={ checkedStatus === 'checked' }
							onChange={ onSelectChild }
						/>
					) : (
						<input
							type="radio"
							checked={ checkedStatus === 'checked' }
							className="components-radio-control__input"
							onChange={ ( event ) =>
								onSelectChild( event.currentTarget.checked )
							}
						/>
					) }

					<div>
						{ typeof getLabel === 'function' ? (
							getLabel( item )
						) : (
							<span>{ item.label }</span>
						) }
					</div>
				</label>

				{ Boolean( item.children?.length ) && (
					<div className="experimental-woocommerce-tree-item__expander">
						<Button
							icon={ expanded ? chevronUp : chevronDown }
							onClick={ onToggleExpand }
							className="experimental-woocommerce-tree-item__expander"
							aria-label={
								expanded
									? __( 'Collapse', 'woocommerce' )
									: __( 'Expand', 'woocommerce' )
							}
						/>
					</div>
				) }
			</div>

			{ Boolean( item.children?.length ) && expanded && (
				<Tree { ...treeProps } role="group" />
			) }
		</li>
	);
} );
