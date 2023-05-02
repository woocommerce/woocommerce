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

export type CheckedStatus = 'checked' | 'unchecked' | 'indeterminate';

type BaseTreeProps = {
	/**
	 * It contains one item if `multiple` value is false or
	 * a list of items if it is true.
	 */
	selected?: Item | Item[];
	/**
	 * Whether the tree items are single or multiple selected.
	 */
	multiple?: boolean;
	/**
	 * In `multiple` mode, when this flag is also set, selecting children does
	 * not select their parents and selecting parents does not select their children.
	 */
	shouldNotRecursivelySelect?: boolean;
	/**
	 * The value to be used for comparison to determine if 'create new' button should be shown.
	 */
	createValue?: string;
	/**
	 * Called when the 'create new' button is clicked.
	 */
	onCreateNew?: () => void;
	/**
	 * If passed, shows create button if return from callback is true
	 */
	shouldShowCreateButton?( value?: string ): boolean;
	isExpanded?: boolean;
	/**
	 * When `multiple` is true and a child item is selected, all its
	 * ancestors and its descendants are also selected. If it's false
	 * only the clicked item is selected.
	 *
	 * @param  value The selection
	 */
	onSelect?( value: Item | Item[] ): void;
	/**
	 * When `multiple` is true and a child item is unselected, all its
	 * ancestors (if no sibblings are selected) and its descendants
	 * are also unselected. If it's false only the clicked item is
	 * unselected.
	 *
	 * @param  value The unselection
	 */
	onRemove?( value: Item | Item[] ): void;
	/**
	 * It provides a way to determine whether the current rendering
	 * item is highlighted or not from outside the tree.
	 *
	 * @example
	 * <Tree
	 * 	shouldItemBeHighlighted={ isFirstChild }
	 * />
	 *
	 * @param  item The current linked tree item, useful to
	 *              traverse the entire linked tree from this item.
	 *
	 * @see {@link LinkedTree}
	 */
	shouldItemBeHighlighted?( item: LinkedTree ): boolean;
	/**
	 * Called when the create button is clicked to help closing any related popover.
	 */
	onTreeBlur?(): void;
	/**
	 * Called when user presses shift + tab and the focus is on the first item.
	 */
	onFirstTreeItemBack?(): void;
};

export type TreeProps = BaseTreeProps &
	Omit<
		React.DetailedHTMLProps<
			React.OlHTMLAttributes< HTMLOListElement >,
			HTMLOListElement
		>,
		'onSelect'
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

export type TreeItemProps = BaseTreeProps &
	Omit<
		React.DetailedHTMLProps<
			React.LiHTMLAttributes< HTMLLIElement >,
			HTMLLIElement
		>,
		'onSelect'
	> & {
		level: number;
		item: LinkedTree;
		index: number;
		isFocused?: boolean;
		getLabel?( item: LinkedTree ): JSX.Element;
		shouldItemBeExpanded?( item: LinkedTree ): boolean;
		onLastItemLoop?( event: React.KeyboardEvent< HTMLDivElement > ): void;
		onFirstTreeItemBack?(): void;
	};

export type TreeControlProps = Omit< TreeProps, 'items' | 'level' > & {
	items: Item[];
};
