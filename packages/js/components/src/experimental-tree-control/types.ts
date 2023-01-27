export interface Item {
	parent?: string;
	value: string;
	label: string;
}

export interface LinkedTree {
	parent?: LinkedTree;
	data: Item;
	children: LinkedTree[];
}

export type TreeProps = React.DetailedHTMLProps<
	React.OlHTMLAttributes< HTMLOListElement >,
	HTMLOListElement
> & {
	level?: number;
	items: LinkedTree[];
	/**
	 * Control the tree item expand/collapse from outside the tree.
	 * Cache the function to improve performance.
	 *
	 * @example
	 * <Tree
	 * 	isItemExpanded={ useCallback(
	 * 		( item ) => checkExpanded( item, text ),
	 * 		[ text ]
	 * 	) }
	 * />
	 *
	 * @param  item The tree item to expand/collapse.
	 *
	 * @see {@link LinkedTree}
	 */
	isItemExpanded?( item: LinkedTree ): boolean;
};

export type TreeItemProps = React.DetailedHTMLProps<
	React.LiHTMLAttributes< HTMLLIElement >,
	HTMLLIElement
> & {
	level: number;
	item: LinkedTree;
	isExpanded?( item: LinkedTree ): boolean;
};

export type TreeControlProps = Omit< TreeProps, 'items' | 'level' > & {
	items: Item[];
};
