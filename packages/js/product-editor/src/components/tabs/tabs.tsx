/**
 * External dependencies
 */
import {
	createElement,
	Fragment,
	useEffect,
	useState,
} from '@wordpress/element';
import { ReactElement } from 'react';
import { Slot } from '@wordpress/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { navigateTo, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { TABS_SLOT_NAME } from './constants';

type TabsProps = {
	onChange: ( tabId: string | null ) => void;
};

export type TabsFillProps = {
	onClick: ( tabId: string ) => void;
};

export function Tabs( { onChange = () => {} }: TabsProps ) {
	const [ selected, setSelected ] = useState< string | null >( null );
	function onClick( tabId: string ) {
		window.document.documentElement.scrollTop = 0;
		navigateTo( {
			url: getNewPath( { tab: tabId } ),
		} );
		setSelected( tabId );
	}

	useEffect( () => {
		onChange( selected );
	}, [ selected ] );

	function maybeSetSelected( fills: readonly ( readonly ReactElement[] )[] ) {
		if ( selected ) {
			return;
		}

		for ( let i = 0; i < fills.length; i++ ) {
			if ( fills[ i ][ 0 ].props.disabled ) {
				continue;
			}
			// Remove the `.$` prefix on keys.  E.g., .$key => key
			const tabId = fills[ i ][ 0 ].key?.toString().slice( 2 ) || null;
			setSelected( tabId );
			return;
		}
	}

	return (
		<div className="woocommerce-product-tabs">
			<Slot
				fillProps={
					{
						onClick,
					} as TabsFillProps
				}
				name={ TABS_SLOT_NAME }
			>
				{ ( fills ) => {
					maybeSetSelected( fills );
					return <>{ fills }</>;
				} }
			</Slot>
		</div>
	);
}
