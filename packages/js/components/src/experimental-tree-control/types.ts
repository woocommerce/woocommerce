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
};

export type TreeItemProps = React.DetailedHTMLProps<
	React.LiHTMLAttributes< HTMLLIElement >,
	HTMLLIElement
> & {
	level: number;
	item: LinkedTree;
};

export type TreeControlProps = Omit< TreeProps, 'items' | 'level' > & {
	items: Item[];
};
