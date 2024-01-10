/**
 * External dependencies
 */
import React from 'react';
import { Slot, Fill } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import {
	FillComponentProps,
	SlotComponentProps,
} from '@woocommerce/components/build-types/types';

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

export const WooProductSectionItem: React.FC<
	WooProductSectionItemProps & { children: React.ReactNode }
> & {
	Slot: React.FC< SlotComponentProps & WooProductSectionSlotProps >;
} = ( { children, tabs } ) => {
	return (
		<>
			{ tabs.map( ( { name: tabName, order: sectionOrder } ) => (
				<Fill
					name={ `woocommerce_product_section_${ tabName }` }
					key={ tabName }
				>
					{ ( fillProps: FillComponentProps ) => {
						return createOrderedChildren<
							FillComponentProps & { tabName: string }
						>( children, sectionOrder || DEFAULT_SECTION_ORDER, {
							tabName,
							...fillProps,
						} );
					} }
				</Fill>
			) ) }
		</>
	);
};

WooProductSectionItem.Slot = ( { fillProps, tab } ) => (
	// @ts-expect-error - I think Slot props type issues need to be fixed in @wordpress/components.
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
