export interface Item {
	value: string;
	label: string;
	children?: Item[];
}

export type CheckedStatus = 'checked' | 'unchecked' | 'indeterminate';

type BaseTreeProps = {
	selected?: Item | Item[];
	level?: number;
	multiple?: boolean;
	onSelect?( value: Item | Item[] ): void;
	onRemove?( value: Item | Item[] ): void;
};

export type TreeItemLabelProps = {
	item: Item;
};

export type TreeProps = BaseTreeProps &
	Omit<
		React.DetailedHTMLProps<
			React.OlHTMLAttributes< HTMLOListElement >,
			HTMLOListElement
		>,
		'onSelect'
	> & {
		items: Item[];
		getItemLabel?( item: Item ): JSX.Element;
		isItemExpanded?( item: Item ): boolean;
	};

export type TreeItemProps = BaseTreeProps &
	Omit<
		React.DetailedHTMLProps<
			React.LiHTMLAttributes< HTMLLIElement >,
			HTMLLIElement
		>,
		'onSelect'
	> & {
		item: Item;
		level: number;
		getLabel?( item: Item ): JSX.Element;
		isExpanded?( item: Item ): boolean;
	};
