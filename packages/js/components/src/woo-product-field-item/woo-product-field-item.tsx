/**
 * External dependencies
 */
import React, { useEffect } from 'react';
import { Slot, Fill } from '@wordpress/components';
import { createElement, Children, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { createOrderedChildren, sortFillsByOrder } from '../utils';
import { useSlotContext, SlotContextHelpersType } from '../slot-context';
import { ProductFillLocationType } from '../woo-product-tab-item';

type WooProductFieldItemProps = {
	id: string;
	sections: ProductFillLocationType[];
	pluginId: string;
};

type WooProductFieldSlotProps = {
	section: string;
};

export const WooProductFieldItem: React.FC< WooProductFieldItemProps > & {
	Slot: React.FC< Slot.Props & WooProductFieldSlotProps >;
} = ( { children, sections, id } ) => {
	const { registerFill, getFillHelpers } = useSlotContext();

	useEffect( () => {
		registerFill( id );
	}, [] );

	return (
		<>
			{ sections.map( ( { name: sectionName, order: sectionOrder } ) => (
				<Fill
					name={ `woocommerce_product_field_${ sectionName }` }
					key={ sectionName }
				>
					{ ( fillProps: Fill.Props ) => {
						return createOrderedChildren<
							Fill.Props &
								SlotContextHelpersType & {
									sectionName: string;
								},
							{ _id: string }
						>(
							children,
							sectionOrder || 20,
							{
								sectionName,
								...fillProps,
								...getFillHelpers(),
							},
							{ _id: id }
						);
					} }
				</Fill>
			) ) }
		</>
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
