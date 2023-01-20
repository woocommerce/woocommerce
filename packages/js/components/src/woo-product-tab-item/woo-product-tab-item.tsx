/**
 * External dependencies
 */
import React, { ReactElement, ReactNode } from 'react';
import { Slot, Fill, TabPanel } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { createOrderedChildren } from '../utils';

type WooProductTabItemProps = {
	id: string;
	location: string;
	pluginId: string;
	order?: number;
	tabProps: TabPanel.Tab;
};

type WooProductFieldSlotProps = {
	location: string;
	children: (
		tabs: TabPanel.Tab[],
		tabChildren: Record< string, ReactNode >
	) => ReactElement | null;
};

export const WooProductTabItem: React.FC< WooProductTabItemProps > & {
	Slot: React.VFC<
		Omit< Slot.Props, 'children' > & WooProductFieldSlotProps
	>;
} = ( { children, order = 20, location, tabProps } ) => {
	return (
		<Fill name={ `woocommerce_product_tab_${ location }` }>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren< Fill.Props >(
					children,
					order,
					fillProps,
					{ tabProps }
				);
			} }
		</Fill>
	);
};

WooProductTabItem.Slot = ( { fillProps, location, children } ) => (
	<Slot
		name={ `woocommerce_product_tab_${ location }` }
		fillProps={ fillProps }
	>
		{ ( fills ) => {
			const tabData = fills.reduce(
				( { childrenMap, tabs }, fill ) => {
					const props: WooProductTabItemProps = fill[ 0 ].props;
					if ( props && props.tabProps ) {
						childrenMap[ props.tabProps.name ] = fill[ 0 ];
						tabs.push( {
							...props.tabProps,
							order: props.order ?? 20,
						} );
					}
					return {
						childrenMap,
						tabs,
					};
				},
				{ childrenMap: {}, tabs: [] } as {
					childrenMap: Record< string, ReactElement >;
					tabs: Array< TabPanel.Tab & { order: number } >;
				}
			);
			const orderedTabs = tabData.tabs.sort( ( a, b ) => {
				return a.order - b.order;
			} );

			return children( orderedTabs, tabData.childrenMap );
		} }
	</Slot>
);
