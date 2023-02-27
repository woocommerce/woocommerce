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
	 * It gives a way to render a different Element as the
	 * tree item label.
	 *
	 * @example
	 * <Tree
	 * 	getItemLabel={ ( item ) => <span>${ item.data.label }</span> }
	 * />
	 *
	 * @param  item The current rendering tree item
	 *
	 * @see {@link LinkedTree}
	 */
	getItemLabel?( item: LinkedTree ): JSX.Element;
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
	getLabel?( item: LinkedTree ): JSX.Element;
	shouldItemBeExpanded?( item: LinkedTree ): boolean;
};

export type TreeControlProps = Omit< TreeProps, 'items' | 'level' > & {
	items: Item[];
};
