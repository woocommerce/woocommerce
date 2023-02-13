/**
 * External dependencies
 */
import React, { ReactElement, ReactNode } from 'react';
import { Slot, Fill, TabPanel } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { createOrderedChildren } from '../../utils';

export type ProductFillLocationType = { name: string; order?: number };

type WooProductTabItemProps = {
	id: string;
	pluginId: string;
	tabProps:
		| TabPanel.Tab
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		| ( ( fillProps: Record< string, any > | undefined ) => TabPanel.Tab );
	templates?: Array< ProductFillLocationType >;
};

type WooProductFieldSlotProps = {
	template: string;
	children: (
		tabs: TabPanel.Tab[],
		tabChildren: Record< string, ReactNode >
	) => ReactElement | null;
};

export const WooProductTabItem: React.FC< WooProductTabItemProps > & {
	Slot: React.VFC<
		Omit< Slot.Props, 'children' > & WooProductFieldSlotProps
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
					{ ( fillProps: Fill.Props ) => {
						return createOrderedChildren< Fill.Props >(
							children,
							templateData.order || 20,
							{},
							{
								tabProps,
								templateName: templateData.name,
								order: templateData.order || 20,
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
