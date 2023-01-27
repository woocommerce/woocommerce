/**
 * External dependencies
 */
import React, { useEffect } from 'react';
import { Slot, Fill } from '@wordpress/components';
import { createElement, Children } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { createOrderedChildren, sortFillsByOrder } from '../utils';
import { useSlotContext, SlotContextHelpersType } from '../slot-context';

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
} = ( { children, order = 20, section, id } ) => {
	const { registerFill, getFillHelpers } = useSlotContext();

	useEffect( () => {
		registerFill( id );
	}, [] );

	return (
		<Fill name={ `woocommerce_product_field_${ section }` }>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren<
					Fill.Props & SlotContextHelpersType,
					{ _id: string }
				>(
					children,
					order,
					{
						...fillProps,
						...getFillHelpers(),
					},
					{ _id: id }
				);
			} }
		</Fill>
	);
};

WooProductFieldItem.Slot = ( { fillProps, section } ) => {
	// eslint-disable-next-line react-hooks/rules-of-hooks
	const { filterRegisteredFills } = useSlotContext();

	return (
		<Slot
			name={ `woocommerce_product_field_${ section }` }
			fillProps={ fillProps }
		>
			{ ( fills ) => {
				if ( ! sortFillsByOrder ) {
					return null;
				}

				return Children.map(
					sortFillsByOrder( filterRegisteredFills( fills ) )?.props
						.children,
					( child ) => (
						<div className="woocommerce-product-form__field">
							{ child }
						</div>
					)
				);
			} }
		</Slot>
	);
};
