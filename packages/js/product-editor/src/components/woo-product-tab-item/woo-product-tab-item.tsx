/**
 * External dependencies
 */
import React, { ReactElement, ReactNode } from 'react';
import { Slot, Fill } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import {
	FillComponentProps,
	SlotComponentProps,
	Tab,
	TabPanelProps,
} from '@woocommerce/components/build-types/types';

/**
 * Internal dependencies
 */
import { createOrderedChildren } from '../../utils';

export type ProductFillLocationType = { name: string; order?: number };

type WooProductTabItemProps = {
	id: string;
	pluginId: string;
	tabProps:
		| Tab
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		| ( ( fillProps: Record< string, any > | undefined ) => Tab );
	templates?: Array< ProductFillLocationType >;
};

type WooProductFieldSlotProps = {
	template: string;
	children: (
		tabs: Tab[],
		tabChildren: Record< string, ReactNode >
	) => ReactElement | null;
};

const DEFAULT_TAB_ORDER = 20;

export const WooProductTabItem: React.FC<
	WooProductTabItemProps & { children: React.ReactNode }
> & {
	Slot: React.FC<
		Omit< SlotComponentProps, 'children' > & WooProductFieldSlotProps
	>;
} = ( { children, tabProps, templates } ) => {
	if ( ! templates ) {
		// eslint-disable-next-line no-console
		console.warn( 'WooProductTabItem fill is missing templates property.' );
		return null;
	}
	return (
		<>
			{ templates.map( ( templateData ) => (
				<Fill
					name={ `woocommerce_product_tab_${ templateData.name }` }
					key={ templateData.name }
				>
					{ ( fillProps: FillComponentProps ) => {
						return createOrderedChildren< FillComponentProps >(
							children,
							templateData.order || DEFAULT_TAB_ORDER,
							{},
							{
								tabProps,
								templateName: templateData.name,
								order: templateData.order || DEFAULT_TAB_ORDER,
								...fillProps,
							}
						);
					} }
				</Fill>
			) ) }
		</>
	);
};

WooProductTabItem.Slot = ( { fillProps, template, children } ) => (
	// @ts-expect-error - I think Slot props type issues need to be fixed in @wordpress/components.
	<Slot
		name={ `woocommerce_product_tab_${ template }` }
		fillProps={ fillProps }
	>
		{ ( fills ) => {
			const tabData = fills.reduce(
				( { childrenMap, tabs }, fill ) => {
					const props: WooProductTabItemProps & { order: number } =
						fill[ 0 ].props;
					if ( props && props.tabProps ) {
						childrenMap[ props.tabProps.name ] = fill[ 0 ];
						const tabProps =
							typeof props.tabProps === 'function'
								? props.tabProps( fillProps )
								: props.tabProps;
						tabs.push( {
							...tabProps,
							order: props.order ?? DEFAULT_TAB_ORDER,
						} );
					}
					return {
						childrenMap,
						tabs,
					};
				},
				{ childrenMap: {}, tabs: [] } as {
					childrenMap: Record< string, ReactElement >;
					tabs: Array< TabPanelProps[ 'tabs' ] & { order: number } >;
				}
			);
			const orderedTabs = tabData.tabs.sort( ( a, b ) => {
				return a.order - b.order;
			} );

			return children( orderedTabs, tabData.childrenMap );
		} }
	</Slot>
);
