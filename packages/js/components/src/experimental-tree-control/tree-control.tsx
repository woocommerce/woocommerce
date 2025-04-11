/**
 * External dependencies
 */
import { createElement, forwardRef } from 'react';

/**
 * Internal dependencies
 */
import { createLinkedTree } from './linked-tree-utils';
import { Tree } from './tree';
import { TreeControlProps } from './types';

export const TreeControl = forwardRef( function ForwardedTree(
	{ items, ...props }: TreeControlProps,
	ref: React.ForwardedRef< HTMLOListElement >
) {
	const linkedTree = createLinkedTree( items, props.createValue );

	return <Tree { ...props } ref={ ref } items={ linkedTree } />;
} );
