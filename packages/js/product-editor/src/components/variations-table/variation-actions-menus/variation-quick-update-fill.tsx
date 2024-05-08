/**
 * External dependencies
 */

import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';
import { PartialProductVariation, ProductVariation } from '@woocommerce/data';
import { Slot, Fill } from '@wordpress/components';
import { createElement } from '@wordpress/element';

export const VariationQuickUpdateFill: React.FC< {
	name?: string;
	children?: ( asdasd: {
		selection: ProductVariation[];
		onChange: (
			values: PartialProductVariation[],
			showSuccess?: boolean | undefined
		) => void;
		onClose: () => void;
	} ) => React.ReactNode;
	order?: number;
} > & {
	Slot: React.FC< Slot.Props & { name?: string } >;
} = ( { children, order = 1, name = '' } ) => {
	return (
		<Fill name={ name }>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

VariationQuickUpdateFill.Slot = ( { fillProps, name = '' } ) => (
	<Slot name={ name } fillProps={ fillProps }>
		{ sortFillsByOrder }
	</Slot>
);
