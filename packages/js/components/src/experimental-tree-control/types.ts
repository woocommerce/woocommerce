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
	 * Return if the tree item passed in should be expanded.
	 *
	 * @example
	 * <Tree
	 * 	shouldItemBeExpanded={
	 * 		( item ) => checkExpanded( item, filter )
	 * 	}
	 * />
	 *
	 * @param  item The tree item to determine if should be expanded.
	 *
	 * @see {@link LinkedTree}
	 */
	shouldItemBeExpanded?( item: LinkedTree ): boolean;
};

export type TreeItemProps = React.DetailedHTMLProps<
	React.LiHTMLAttributes< HTMLLIElement >,
	HTMLLIElement
> & {
	level: number;
	item: LinkedTree;
	shouldItemBeExpanded?( item: LinkedTree ): boolean;
};

export type TreeControlProps = Omit< TreeProps, 'items' | 'level' > & {
	items: Item[];
};
