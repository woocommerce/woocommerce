/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';
import { createElement, forwardRef, Fragment } from 'react';
import { plus } from '@wordpress/icons';

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

	const createValueExists =
		items.findIndex( ( i ) => i.data.name === props.createValue ) !== -1;

	return (
		<>
			<ol
				{ ...treeProps }
				className={ classNames(
					treeProps.className,
					'experimental-woocommerce-tree',
					`experimental-woocommerce-tree--level-${ level }`
				) }
			>
				{ items.map( ( child, index ) => (
					<TreeItem
						{ ...treeItemProps }
						isExpanded={ props.isExpanded }
						key={ child.data.id }
						item={ child }
						index={ index }
					/>
				) ) }
			</ol>
			{ props.allowCreate && ! createValueExists && (
				<Button
					className="experimental-woocommerce-tree__button"
					onClick={ () => {
						if ( props.onCreateNew ) {
							props.onCreateNew();
						}
						if ( props.onTreeBlur ) {
							props.onTreeBlur();
						}
					} }
					// Prevents the up/down arrow keys from scrolling the page.
					// eslint-disable-next-line @typescript-eslint/no-explicit-any
					onKeyDown={ ( event: any ) => {
						if (
							event.key === 'ArrowUp' ||
							event.key === 'ArrowDown'
						) {
							event.preventDefault();
						}
					} }
				>
					<Icon icon={ plus } size={ 20 } />
					{ props.createValue
						? sprintf(
								__( 'Create "%s"', 'woocommerce' ),
								props.createValue
						  )
						: __( 'Create new', 'woocommerce' ) }
				</Button>
			) }
		</>
	);
} );
