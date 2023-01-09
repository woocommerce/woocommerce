export interface AutocompleteItem {
	value: string;
	label: string;
	children?: AutocompleteItem[];
}

export type CheckedStatus = 'checked' | 'unchecked' | 'indeterminate';

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

export type MenuProps = Omit<
	React.DetailedHTMLProps<
		React.OlHTMLAttributes< HTMLOListElement >,
		HTMLOListElement
	>,
	'onSelect'
> & {
	items: AutocompleteItem[];
	selected?: AutocompleteItem | AutocompleteItem[];
	inputValue?: string;
	level?: number;
	multiple?: boolean;
	onSelect( value: AutocompleteItem | AutocompleteItem[] ): void;
	onRemove( value: AutocompleteItem | AutocompleteItem[] ): void;
};

export type MenuItemProps = Omit<
	React.DetailedHTMLProps<
		React.LiHTMLAttributes< HTMLLIElement >,
		HTMLLIElement
	>,
	'onSelect'
> & {
	item: AutocompleteItem;
	selected?: AutocompleteItem | AutocompleteItem[];
	inputValue?: string;
	level: number;
	multiple?: boolean;
	onSelect( value: AutocompleteItem | AutocompleteItem[] ): void;
	onRemove( value: AutocompleteItem | AutocompleteItem[] ): void;
};
