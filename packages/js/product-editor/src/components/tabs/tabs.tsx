/**
 * External dependencies
 */
import { createElement, useEffect, Fragment } from '@wordpress/element';
import { KeyboardEvent, ReactElement, useMemo } from 'react';
import { NavigableMenu, Slot } from '@wordpress/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { select } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { getTabTracksData } from './utils/get-tab-tracks-data';
import { TABS_SLOT_NAME } from './constants';

type TabsProps = {
	selected: string | null;
	onChange: ( tabId: string ) => void;
};

export type TabsFillProps = {
	onClick: ( tabId: string ) => void;
};

function TabFills( {
	fills,
	selected,
	onChange,
}: {
	fills: readonly ( readonly ReactElement[] )[];
	selected: string | null;
	onChange: ( tabId: string ) => void;
} ) {
	const sortedFills = useMemo(
		function sortFillsByOrder() {
			return [ ...fills ].sort(
				( [ { props: a } ], [ { props: b } ] ) => a.order - b.order
			);
		},
		[ fills ]
	);

	useEffect( () => {
		// If a tab is already selected, do nothing
		if ( selected ) {
			return;
		}

		// Select the first tab that is not disabled
		const firstEnabledTab = sortedFills.find( ( element ) => {
			const [ { props } ] = element;
			return ! props.disabled;
		} );

		const tabIdToSelect = firstEnabledTab?.[ 0 ]?.props?.children?.key;

		if ( tabIdToSelect ) {
			onChange( tabIdToSelect );
		}
	}, [ sortedFills, selected, onChange ] );

	return <>{ sortedFills }</>;
}

export function Tabs( { selected, onChange }: TabsProps ) {
	const [ productId ] = useEntityProp< number >(
		'postType',
		'product',
		'id'
	);

	function selectTabOnNavigate(
		_childIndex: number,
		child: HTMLButtonElement
	) {
		child.focus();
	}

	function handleKeyDown( event: KeyboardEvent< HTMLDivElement > ) {
		const tabs =
			event.currentTarget.querySelectorAll< HTMLButtonElement >(
				'[role="tab"]'
			);

		switch ( event.key ) {
			case 'Home':
				event.preventDefault();
				event.stopPropagation();

				const [ firstTab ] = tabs;
				firstTab?.focus();
				break;
			case 'End':
				event.preventDefault();
				event.stopPropagation();

				const lastTab = tabs[ tabs.length - 1 ];
				lastTab?.focus();
				break;
		}
	}

	function renderFills( fills: readonly ( readonly ReactElement[] )[] ) {
		return (
			<TabFills
				fills={ fills }
				selected={ selected }
				onChange={ onChange }
			/>
		);
	}

	return (
		<NavigableMenu
			role="tablist"
			onNavigate={ selectTabOnNavigate }
			onKeyDown={ handleKeyDown }
			className="woocommerce-product-tabs"
			orientation="horizontal"
		>
			<Slot
				fillProps={
					{
						onClick: ( tabId ) => {
							onChange( tabId );

							// eslint-disable-next-line @typescript-eslint/ban-ts-comment
							// @ts-ignore
							const { getEditedEntityRecord } = select( 'core' );

							const product: Product = getEditedEntityRecord(
								'postType',
								'product',
								productId
							);

							recordEvent(
								'product_tab_click',
								getTabTracksData( tabId, product )
							);
						},
					} as TabsFillProps
				}
				name={ TABS_SLOT_NAME }
			>
				{ renderFills }
			</Slot>
		</NavigableMenu>
	);
}
