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
		items.findIndex( ( i ) => i.data.label === props.createValue ) !== -1;

	return (
		<>
			<ol
				{ ...treeProps }
				{ ...( props.getMenuProps && props.getMenuProps() ) }
				className={ classNames(
					treeProps.className,
					'experimental-woocommerce-tree',
					`experimental-woocommerce-tree--level-${ level }`
				) }
			>
				{ items.map( ( child, index ) => (
					<TreeItem
						{ ...treeItemProps }
						{ ...( props.getItemProps &&
							props.getItemProps( {
								item: {
									value: child.data.value,
									label: child.data.label,
								},
								index: props.listToFindOriginalIndex.findIndex(
									// eslint-disable-next-line @typescript-eslint/no-explicit-any
									( a: any ) => a.id === +child.data.value
								),
							} ) ) }
						getMenuProps={ props.getMenuProps }
						getItemProps={ props.getItemProps }
						listToFindOriginalIndex={
							props.listToFindOriginalIndex
						}
						isFocused={
							props.highlightedIndex ===
							props.listToFindOriginalIndex.findIndex(
								// eslint-disable-next-line @typescript-eslint/no-explicit-any
								( a: any ) => a.id === +child.data.value
							)
						}
						key={ child.data.value }
						item={ child }
						index={ index }
					/>
				) ) }
			</ol>
			{ props.allowCreate && ! createValueExists && (
				<Button
					className="experimental-woocommerce-tree__button"
					onClick={ () => props.onCreateNew && props.onCreateNew() }
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
