/**
 * External dependencies
 */
import {
	createElement,
	useEffect,
	useState,
	Fragment,
} from '@wordpress/element';
import { ReactElement, useMemo } from 'react';
import { NavigableMenu, Slot } from '@wordpress/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { select } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { navigateTo, getNewPath, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getTabTracksData } from './utils/get-tab-tracks-data';
import { TABS_SLOT_NAME } from './constants';

type TabsProps = {
	onChange?: ( tabId: string | null ) => void;
};

export type TabsFillProps = {
	onClick: ( tabId: string ) => void;
};

function TabFills( {
	fills,
	onDefaultSelection,
}: {
	fills: readonly ( readonly ReactElement[] )[];
	onDefaultSelection( tabId: string ): void;
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
		for ( let i = 0; i < sortedFills.length; i++ ) {
			const [ { props } ] = fills[ i ];
			if ( ! props.disabled ) {
				const tabId = props.children?.key;
				if ( tabId ) {
					onDefaultSelection( tabId );
				}
				return;
			}
		}
	}, [ sortedFills ] );

	return <>{ sortedFills }</>;
}

export function Tabs( { onChange = () => {} }: TabsProps ) {
	const [ selected, setSelected ] = useState< string | null >( null );
	const query = getQuery() as Record< string, string >;
	const [ productId ] = useEntityProp< number >(
		'postType',
		'product',
		'id'
	);

	useEffect( () => {
		if ( query.tab ) {
			setSelected( query.tab );
			onChange( query.tab );
		}
	}, [ query.tab ] );

	function selectTabOnNavigate(
		_childIndex: number,
		child: HTMLButtonElement
	) {
		child.focus();
	}

	function renderFills( fills: readonly ( readonly ReactElement[] )[] ) {
		return (
			<TabFills
				fills={ fills }
				onDefaultSelection={ ( tabId ) => {
					if ( selected ) {
						return;
					}
					setSelected( tabId );
					onChange( tabId );
				} }
			/>
		);
	}

	return (
		<NavigableMenu
			role="tablist"
			onNavigate={ selectTabOnNavigate }
			className="woocommerce-product-tabs"
			orientation="horizontal"
		>
			<Slot
				fillProps={
					{
						onClick: ( tabId ) => {
							navigateTo( {
								url: getNewPath( { tab: tabId } ),
							} );

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
