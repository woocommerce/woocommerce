export interface AutocompleteItem {
	value: string;
	label: string;
	children?: AutocompleteItem[];
}

export type AutocompleteProps = Omit<
	React.DetailedHTMLProps<
		React.HTMLAttributes< HTMLDivElement >,
		HTMLDivElement
	>,
	'onSelect' | 'onRemove'
> & {
	items: AutocompleteItem[];
	selected?: AutocompleteItem | AutocompleteItem[];
	multiple?: boolean;
	allowCreate?: boolean;
	onSelect( value: AutocompleteItem | AutocompleteItem[] ): void;
	onRemove( value: AutocompleteItem | AutocompleteItem[] ): void;
	onInputChange?( value: string ): void;
	onCreateClick?( value?: string ): void;
};

export type UseAllowCreateProps = Pick<
	AutocompleteProps,
	'allowCreate' | 'onCreateClick' | 'items'
> & {
	inputValue: string;
};
