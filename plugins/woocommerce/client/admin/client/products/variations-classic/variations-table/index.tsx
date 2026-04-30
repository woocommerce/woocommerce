/**
 * External dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { DataViews } from '@wordpress/dataviews';
import { Button, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import type { View, Action } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { variationFields } from './fields';
import type { Variation } from '../types';

const DEFAULT_VIEW: View = {
	type: 'table',
	page: 1,
	perPage: 20,
	search: '',
	fields: [ 'variant', 'price', 'stock' ],
};

const EMPTY_ARRAY: Variation[] = [];

interface VariationsTableProps {
	productId: number;
}

export default function VariationsTable( { productId }: VariationsTableProps ) {
	const [ variations, setVariations ] =
		useState< Variation[] >( EMPTY_ARRAY );
	const [ totalItems, setTotalItems ] = useState( 0 );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState< string | null >( null );
	const [ view, setView ] = useState< View >( DEFAULT_VIEW );

	const page = view.page ?? 1;
	const perPage = view.perPage ?? 20;
	const search = view.search ?? '';

	useEffect( () => {
		let cancelled = false;

		setIsLoading( true );
		setError( null );

		const params = new URLSearchParams( {
			per_page: String( perPage ),
			page: String( page ),
		} );

		if ( search ) {
			params.set( 'search', search );
		}

		apiFetch< Response >( {
			path: `/wc/v3/products/${ productId }/variations?${ params }`,
			parse: false,
		} )
			.then( async ( response ) => {
				if ( cancelled ) return;
				const data: Variation[] = await response.json();
				const total = parseInt(
					response.headers.get( 'X-WP-Total' ) ?? '0',
					10
				);
				setVariations( data );
				setTotalItems( total );
				setIsLoading( false );
			} )
			.catch( () => {
				if ( cancelled ) return;
				setError( __( 'Failed to load variations.', 'woocommerce' ) );
				setIsLoading( false );
			} );

		return () => {
			cancelled = true;
		};
	}, [ productId, page, perPage, search ] );

	const handleChangeView = useCallback( ( newView: View ) => {
		setView( newView );
	}, [] );

	const actions: Action< Variation >[] = [
		{
			id: 'edit-legacy',
			label: __( 'Edit (legacy)', 'woocommerce' ),
			isPrimary: true,
			callback: ( items ) => {
				if ( ! items[ 0 ] ) return;
				const url = new URL( window.location.href );
				url.searchParams.set(
					'edit_variation',
					String( items[ 0 ].id )
				);
				window.location.href = url.toString();
			},
		},
	];

	const paginationInfo = {
		totalItems,
		totalPages: Math.ceil( totalItems / perPage ),
	};

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		);
	}

	return (
		<DataViews
			data={ variations }
			fields={ variationFields }
			view={ view }
			onChangeView={ handleChangeView }
			isLoading={ isLoading }
			paginationInfo={ paginationInfo }
			getItemId={ ( item: Variation ) => String( item.id ) }
			defaultLayouts={ { table: {} } }
			actions={ actions }
			search
			searchLabel={ __( 'Search variations', 'woocommerce' ) }
			header={
				<Button variant="secondary" disabled>
					{ __( 'Edit options', 'woocommerce' ) }
				</Button>
			}
		/>
	);
}
