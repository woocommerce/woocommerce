/**
 * External dependencies
 */
import React from 'react';
import { Slot, Fill } from '@wordpress/components';
import { createElement, Children } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { createOrderedChildren, sortFillsByOrder } from '../utils';

type WooProductFieldItemProps = {
	id: string;
	section: string;
	pluginId: string;
	order?: number;
};

type WooProductFieldSlotProps = {
	section: string;
};

export const WooProductFieldItem: React.FC< WooProductFieldItemProps > & {
	Slot: React.FC< Slot.Props & WooProductFieldSlotProps >;
} = ( { children, order = 1, section } ) => (
	<Fill name={ `woocommerce_product_field_${ section }` }>
		{ ( fillProps: Fill.Props ) => {
			return createOrderedChildren< Fill.Props >(
				children,
				order,
				fillProps
			);
		} }
	</Fill>
);

WooProductFieldItem.Slot = ( { fillProps, section } ) => (
	<Slot
		name={ `woocommerce_product_field_${ section }` }
		fillProps={ fillProps }
	>
		{ ( fills ) => {
			if ( ! sortFillsByOrder ) {
				return null;
			}

			return Children.map(
				sortFillsByOrder( fills )?.props.children,
				( child ) => (
					<div className="woocommerce-product-form__field">
						{ child }
					</div>
				)
			);
		} }
	</Slot>
);
