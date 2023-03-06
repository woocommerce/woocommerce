/**
 * External dependencies
 */
import React from 'react';
import { Slot, Fill } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { createOrderedChildren, sortFillsByOrder } from '../../utils';
import { ProductFillLocationType } from '../woo-product-tab-item';

type WooProductSectionItemProps = {
	id: string;
	tabs: ProductFillLocationType[];
	pluginId: string;
};

type WooProductSectionSlotProps = {
	tab: string;
};

const DEFAULT_SECTION_ORDER = 20;

export const WooProductSectionItem: React.FC< WooProductSectionItemProps > & {
	Slot: React.FC< Slot.Props & WooProductSectionSlotProps >;
} = ( { children, tabs } ) => {
	return (
		<>
			{ tabs.map( ( { name: tabName, order: sectionOrder } ) => (
				<Fill
					name={ `woocommerce_product_section_${ tabName }` }
					key={ tabName }
				>
					{ /* eslint-disable @typescript-eslint/ban-ts-comment */ }
					{
						// @ts-ignore It is okay to pass in a function as a render child of Fill
						( fillProps: Fill.Props ) => {
							return createOrderedChildren<
								Fill.Props & { tabName: string }
							>(
								children,
								sectionOrder || DEFAULT_SECTION_ORDER,
								{
									tabName,
									...fillProps,
								}
							);
						}
					}
					{ /* eslint-enable @typescript-eslint/ban-ts-comment */ }
				</Fill>
			) ) }
		</>
	);
};

WooProductSectionItem.Slot = ( { fillProps, tab } ) => (
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
