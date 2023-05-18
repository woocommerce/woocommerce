export type DefaultItem = {
	label: string;
	value: string | number;
};

export type getItemLabelType< Item > = ( item: Item ) => string;

export type getItemValueType< Item > = ( item: Item ) => string | number;
