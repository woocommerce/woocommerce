/**
 * External dependencies
 */
import { BaseControl, FormTokenField, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { resolveSelect } from '@wordpress/data';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import type { ProductStatus } from '../types';

type ProductSelectorProps = {
	label?: string;
	description?: string;
	selectedProductIds: number[];
	onSelectedProductIdsChange: ( ids: number[] ) => void;
	excludeProductIds?: number[];
	includeProductStatuses?: ProductStatus[];
};

const SEARCH_DELAY_MS = 250;

function getProductToken( product: Pick< Product, 'id' | 'name' > ) {
	return `${ product.name || __( '(Untitled product)', 'woocommerce' ) } (#${
		product.id
	})`;
}

export function ProductSelector( {
	label,
	description,
	selectedProductIds,
	onSelectedProductIdsChange,
	excludeProductIds = [],
	includeProductStatuses,
}: ProductSelectorProps ) {
	const [ inputValue, setInputValue ] = useState( '' );
	const [ suggestions, setSuggestions ] = useState< Product[] >( [] );
	const [ selectedProducts, setSelectedProducts ] = useState< Product[] >(
		[]
	);
	const [ isLoading, setIsLoading ] = useState( false );

	useEffect( () => {
		let cancelled = false;

		if ( selectedProductIds.length === 0 ) {
			setSelectedProducts( [] );
			return;
		}

		setIsLoading( true );

		void resolveSelect( coreStore )
			.getEntityRecords( 'postType', 'product', {
				include: selectedProductIds,
				per_page: selectedProductIds.length,
				status: includeProductStatuses,
			} )
			.then( ( records: unknown ) => {
				if ( cancelled || ! Array.isArray( records ) ) {
					return;
				}

				const productsById = new Map(
					records.map( ( product ) => [
						product.id,
						product as Product,
					] )
				);
				setSelectedProducts(
					selectedProductIds
						.map( ( id ) => productsById.get( id ) )
						.filter(
							( product ): product is Product =>
								product !== undefined
						)
				);
			} )
			.finally( () => {
				if ( ! cancelled ) {
					setIsLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ includeProductStatuses, selectedProductIds ] );

	useEffect( () => {
		const query = inputValue.trim();

		if ( ! query ) {
			setSuggestions( [] );
			return;
		}

		let cancelled = false;
		setIsLoading( true );

		const timer = window.setTimeout( () => {
			void resolveSelect( coreStore )
				.getEntityRecords( 'postType', 'product', {
					search: query,
					per_page: 20,
					exclude: [ ...excludeProductIds, ...selectedProductIds ],
					status: includeProductStatuses,
				} )
				.then( ( records: unknown ) => {
					if ( ! cancelled ) {
						setSuggestions(
							Array.isArray( records )
								? ( records as Product[] )
								: []
						);
					}
				} )
				.finally( () => {
					if ( ! cancelled ) {
						setIsLoading( false );
					}
				} );
		}, SEARCH_DELAY_MS );

		return () => {
			cancelled = true;
			window.clearTimeout( timer );
		};
	}, [
		excludeProductIds,
		includeProductStatuses,
		inputValue,
		selectedProductIds,
	] );

	const productTokenMap = useMemo( () => {
		const allProducts = [ ...selectedProducts, ...suggestions ];
		return new Map(
			allProducts.map( ( product ) => [
				getProductToken( product ),
				product.id,
			] )
		);
	}, [ selectedProducts, suggestions ] );

	return (
		// eslint-disable-next-line @wordpress/no-base-control-with-label-without-id
		<BaseControl
			label={ label }
			help={
				isLoading ? (
					<span
						style={ {
							display: 'inline-flex',
							alignItems: 'center',
							gap: '8px',
						} }
					>
						<Spinner />
						{ description ||
							__( 'Loading products…', 'woocommerce' ) }
					</span>
				) : (
					description
				)
			}
		>
			<FormTokenField
				value={ selectedProducts.map( getProductToken ) }
				suggestions={ suggestions.map( getProductToken ) }
				onInputChange={ setInputValue }
				onChange={ ( tokens ) => {
					onSelectedProductIdsChange(
						tokens
							.map( ( token ) =>
								productTokenMap.get(
									typeof token === 'string'
										? token
										: token.value
								)
							)
							.filter(
								( id ): id is number => typeof id === 'number'
							)
					);
				} }
			/>
		</BaseControl>
	);
}
