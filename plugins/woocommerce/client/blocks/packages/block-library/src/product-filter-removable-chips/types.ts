/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export type Color = {
	slug?: string;
	name?: string;
	class?: string;
	color: string;
};

export type BlockAttributes = {
	className: string;
	chipText?: string;
	customChipText?: string;
	chipBackground?: string;
	customChipBackground?: string;
	chipBorder?: string;
	customChipBorder?: string;
	layout: {
		orientation: string;
	};
};

type RemovableItemsBlockContext = {
	// eslint-disable-next-line @typescript-eslint/naming-convention
	'woocommerce/removableItems': {
		items: {
			id: string;
			type: string;
			value: string;
			label: string;
		}[];
		storeNamespace: string;
	};
};

export type EditProps = BlockEditProps< BlockAttributes > & {
	style: Record< string, string >;
	context: RemovableItemsBlockContext;
	chipText: Color;
	setChipText: ( value: string ) => void;
	chipBackground: Color;
	setChipBackground: ( value: string ) => void;
	chipBorder: Color;
	setChipBorder: ( value: string ) => void;
	name: string;
};
