/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { CheckboxControl } from '@wordpress/components';
import { ProductCatalogVisibility } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { CatalogVisibilityProps } from './types';
import { TRACKS_SOURCE } from '../../constants';

export function CatalogVisibility( {
	catalogVisibility,
	onCheckboxChange,
}: CatalogVisibilityProps ) {
	function handleVisibilityChange(
		selected: boolean,
		visibility: ProductCatalogVisibility
	) {
		if ( selected ) {
			if ( catalogVisibility === 'visible' ) {
				onCheckboxChange( visibility );
				recordEvent( 'product_catalog_visibility', {
					source: TRACKS_SOURCE,
					visibility: catalogVisibility,
				} );
				return;
			}
			onCheckboxChange( 'hidden' );
		} else {
			if ( catalogVisibility === 'hidden' ) {
				if ( visibility === 'catalog' ) {
					onCheckboxChange( 'search' );
					recordEvent( 'product_catalog_visibility', {
						source: TRACKS_SOURCE,
						visibility: catalogVisibility,
					} );
					return;
				}
				if ( visibility === 'search' ) {
					onCheckboxChange( 'catalog' );
					recordEvent( 'product_catalog_visibility', {
						source: TRACKS_SOURCE,
						visibility: catalogVisibility,
					} );
					return;
				}
				return;
			}
			onCheckboxChange( 'visible' );
			recordEvent( 'product_catalog_visibility', {
				source: TRACKS_SOURCE,
				visibility: catalogVisibility,
			} );
		}
	}

	return (
		<>
			<CheckboxControl
				label={ __( 'Hide in product catalog', 'woocommerce' ) }
				checked={
					catalogVisibility === 'search' ||
					catalogVisibility === 'hidden'
				}
				onChange={ ( selected ) =>
					handleVisibilityChange( selected, 'search' )
				}
			/>
			<CheckboxControl
				label={ __( 'Hide from search results', 'woocommerce' ) }
				checked={
					catalogVisibility === 'catalog' ||
					catalogVisibility === 'hidden'
				}
				onChange={ ( selected ) =>
					handleVisibilityChange( selected, 'catalog' )
				}
			/>
		</>
	);
}
