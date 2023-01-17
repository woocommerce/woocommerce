/**
 * External dependencies
 */
import React from 'react';
import { Slot, Fill } from '@wordpress/components';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { createOrderedChildren, sortFillsByOrder } from '../utils';

type WooProductSectionItemProps = {
	id: string;
	location: string;
	pluginId: string;
	order?: number;
};

type WooProductFieldSlotProps = {
	location: string;
};

export const WooProductSectionItem: React.FC< WooProductSectionItemProps > & {
	Slot: React.FC< Slot.Props & WooProductFieldSlotProps >;
} = ( { children, order = 20, location } ) => (
	<Fill name={ `woocommerce_product_section_${ location }` }>
		{ ( fillProps: Fill.Props ) => {
			return createOrderedChildren< Fill.Props >(
				children,
				order,
				fillProps
			);
		} }
	</Fill>
);

WooProductSectionItem.Slot = ( { fillProps, location } ) => (
	<Slot
		name={ `woocommerce_product_section_${ location }` }
		fillProps={ fillProps }
	>
		{ ( fills ) => {
			if ( ! sortFillsByOrder ) {
				return null;
			}

			return sortFillsByOrder( fills );
		} }
	</Slot>
);
