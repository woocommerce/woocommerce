/**
 * External dependencies
 */
import { UseSelectStateChangeOptions } from 'downshift';

export interface Item {
	key: string;
	name?: string;
	className?: string;
	style?: React.CSSProperties;
}

export interface ControlProps< ItemType > {
	name?: string;
	className?: string;
	label: string;
	describedBy?: string;
	options: ItemType[];
	value: ItemType;
	placeholder?: string;
	onChange: ( value: string ) => void;
	children?: ( item: ItemType ) => JSX.Element;
}

export interface UseSelectStateChangeOptionsProps< ItemType >
	extends UseSelectStateChangeOptions< ItemType > {
	props: {
		items: ItemType[];
	};
}
