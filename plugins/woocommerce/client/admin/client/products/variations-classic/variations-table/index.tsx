/**
 * External dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { DataViews } from '@wordpress/dataviews';
import { Button, Notice } from '@wordpress/components';
import { Stack } from '@wordpress/ui';
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
	fields: [ 'variant', 'values', 'price', 'stock' ],
	layout: { density: 'compact' },
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
	const [ selection, setSelection ] = useState< string[] >( [] );

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

	const handleChangeSelection = useCallback( ( items: string[] ) => {
		setSelection( items );
	}, [] );

	const actions: Action< Variation >[] = [
		{
			id: 'edit',
			label: __( 'Edit', 'woocommerce' ),
			isPrimary: true,
			callback: () => {},
		},
		{
			id: 'delete-variation',
			label: __( 'Delete variation', 'woocommerce' ),
			supportsBulk: true,
			callback: () => {},
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
			selection={ selection }
			onChangeSelection={ handleChangeSelection }
		>
			<Stack
				direction="row"
				justify="space-between"
				className="wc-variations-classic__toolbar"
			>
				<DataViews.Search
					label={ __( 'Search variations', 'woocommerce' ) }
				/>
				<Stack direction="row" gap="xs">
					<DataViews.ViewConfig />
					<Button variant="secondary" disabled>
						{ __( 'Edit options', 'woocommerce' ) }
					</Button>
				</Stack>
			</Stack>
			<DataViews.Layout />
			<DataViews.Footer />
		</DataViews>
	);
}
