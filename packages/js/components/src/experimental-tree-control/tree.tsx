/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';
import { createElement, forwardRef, Fragment, useRef } from 'react';
import { plus } from '@wordpress/icons';
import { useMergeRefs } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { useTree } from './hooks/use-tree';
import { TreeItem } from './tree-item';
import { TreeProps } from './types';

export const Tree = forwardRef( function ForwardedTree(
	props: TreeProps,
	forwardedRef: React.ForwardedRef< HTMLOListElement >
) {
	const rootListRef = useRef< HTMLOListElement >( null );
	const ref = useMergeRefs( [ rootListRef, forwardedRef ] );

	const { level, items, treeProps, treeItemProps } = useTree( {
		...props,
		ref,
	} );

	const isCreateButtonVisible =
		props.shouldShowCreateButton &&
		props.shouldShowCreateButton( props.createValue );

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
						key={ child.data.value }
						item={ child }
						index={ index }
						// Button ref is not working, so need to use CSS directly
						onLastItemLoop={ () => {
							(
								rootListRef.current
									?.closest( 'ol[role="tree"]' )
									?.parentElement?.querySelector(
										'.experimental-woocommerce-tree__button'
									) as HTMLButtonElement
							 )?.focus();
						} }
						onFirstTreeItemBack={ props.onFirstTreeItemBack }
					/>
				) ) }
			</ol>
			{ isCreateButtonVisible && (
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
					// Component's event type definition is not working
					// eslint-disable-next-line @typescript-eslint/no-explicit-any
					onKeyDown={ ( event: any ) => {
						if (
							event.key === 'ArrowUp' ||
							event.key === 'ArrowDown'
						) {
							event.preventDefault();
							if ( event.key === 'ArrowUp' ) {
								const allHeadings =
									event.nativeEvent.srcElement.previousSibling.querySelectorAll(
										'.experimental-woocommerce-tree-item > .experimental-woocommerce-tree-item__heading'
									);

								allHeadings[ allHeadings.length - 1 ]
									?.querySelector(
										'.experimental-woocommerce-tree-item__label'
									)
									?.focus();
							}
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
