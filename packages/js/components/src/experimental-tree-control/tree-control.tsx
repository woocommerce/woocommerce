/**
 * External dependencies
 */
import { createElement, forwardRef } from 'react';

/**
 * Internal dependencies
 */
import { getLinkedTree } from './hooks/use-linked-tree';
import { Tree } from './tree';
import { TreeControlProps } from './types';

export const TreeControl = forwardRef( function ForwardedTree(
	{ items, ...props }: TreeControlProps,
	ref: React.ForwardedRef< HTMLOListElement >
) {
	const linkedTree = getLinkedTree( items );

	return <Tree { ...props } ref={ ref } items={ linkedTree } />;
} );
