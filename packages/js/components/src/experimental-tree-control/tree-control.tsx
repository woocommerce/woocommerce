/**
 * External dependencies
 */
import { createElement, forwardRef } from 'react';

/**
 * Internal dependencies
 */
import { useLinkedTree } from './hooks/use-linked-tree';
import { Tree } from './tree';
import { TreeControlProps } from './types';

export const TreeControl = forwardRef( function ForwardedTree(
	{ items, ...props }: TreeControlProps,
	ref: React.ForwardedRef< HTMLOListElement >
) {
	const linkedTree = useLinkedTree( items );

	return <Tree { ...props } ref={ ref } items={ linkedTree } />;
} );
