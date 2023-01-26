/**
 * External dependencies
 */
import React, { ReactNode } from 'react';
import { Slot, Fill } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { createOrderedChildren, sortFillsByOrder } from '../utils';
import { ProductFillLocationType } from '../woo-product-tab-item';

type WooProductSectionItemProps< T > = {
	id: string;
	tabs: ProductFillLocationType< T >[];
	pluginId: string;
	children?: ReactNode | undefined;
};

type WooProductSectionSlotProps = {
	tab: string;
};

export const WooProductSectionItem = < T, >( {
	children,
	tabs,
}: WooProductSectionItemProps< T > ) => (
	<>
		{ tabs.map(
			( {
				name: tabName,
				order: tabOrder,
				fillProps: sectionFillProps,
			} ) => (
				<Fill
					name={ `woocommerce_product_section_${ tabName }` }
					key={ tabName }
				>
					{ ( fillProps: Fill.Props ) => {
						return createOrderedChildren< Fill.Props >(
							children,
							tabOrder || 20,
							{ ...fillProps, ...sectionFillProps }
						);
					} }
				</Fill>
			)
		) }
	</>
);

WooProductSectionItem.Slot = ( {
	fillProps,
	tab,
}: Slot.Props & WooProductSectionSlotProps ) => (
	<Slot
		name={ `woocommerce_product_section_${ tab }` }
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
