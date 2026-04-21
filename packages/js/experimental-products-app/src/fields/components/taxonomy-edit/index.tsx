/**
 * External dependencies
 */
import { BaseControl, FormTokenField, Spinner } from '@wordpress/components';

import { store as coreStore, type Term } from '@wordpress/core-data';

import { dispatch } from '@wordpress/data';

import { decodeEntities } from '@wordpress/html-entities';

import {
	useState,
	useMemo,
	useCallback,
} from '@wordpress/element';

import { __, sprintf } from '@wordpress/i18n';

import type { DataFormControlProps } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { useElements } from './use-elements';

import { useAdaptiveTaxonomy } from './use-adaptive-taxonomy';

import type { Item, TaxonomyTermRef } from './types';

export type { Item, ItemImage, TaxonomyTermRef } from './types';

type TaxonomyEditProps< T > = {
	taxonomy: string;
	fieldProperty: keyof T;
	searchPlaceholder?: string;
	serverSearchThreshold?: number;
	termCount?: number;
};

function getErrorMessage( error: unknown ): string {
	if ( error instanceof Error ) {
		return error.message;
	}

	if ( typeof error === 'object' && error !== null && 'message' in error ) {
		const errorWithMessage = error as Record< string, unknown >;
		if ( typeof errorWithMessage.message === 'string' ) {
			return errorWithMessage.message;
		}
	}

	return String( error );
}

function itemsToTermRefs( selectedItems: Item[] ): TaxonomyTermRef[] {
	return selectedItems
		.map( ( item ) => ( { id: parseInt( item.value, 10 ) } ) )
		.filter( ( ref ) => ! Number.isNaN( ref.id ) );
}

function isTaxonomyTermRef( value: unknown ): value is TaxonomyTermRef {
	if ( typeof value !== 'object' || value === null || ! ( 'id' in value ) ) {
		return false;
	}

	return typeof ( value as { id: unknown } ).id === 'number';
}

function isTermRecord( value: unknown ): value is Term {
	if ( typeof value !== 'object' || value === null ) {
		return false;
	}

	if ( ! ( 'id' in value ) || ! ( 'name' in value ) ) {
		return false;
	}

	const term = value as Term;
	return typeof term.id === 'number' && typeof term.name === 'string';
}

function createFieldChange< T extends Record< string, unknown > >(
	fieldProperty: keyof T,
	value: TaxonomyTermRef[]
): Partial< T > {
	const change: Partial< T > = {};
	( change as Record< keyof T, TaxonomyTermRef[] > )[ fieldProperty ] = value;
	return change;
}

function mergeKnownItems( previousItems: Item[], newItems: Item[] ) {
	const knownValues = new Set( previousItems.map( ( item ) => item.value ) );

	return [
		...previousItems,
		...newItems.filter( ( item ) => ! knownValues.has( item.value ) ),
	];
}

export function TaxonomyEdit< T extends Record< string, unknown > >( {
	data,
	field,
	onChange,
	taxonomy,
	fieldProperty,
	searchPlaceholder,
	serverSearchThreshold,
	termCount,
}: DataFormControlProps< T > & TaxonomyEditProps< T > ) {
	const isAdaptiveMode = serverSearchThreshold !== undefined;
	const [ inputValue, setInputValue ] = useState( '' );
	const [ isCreating, setIsCreating ] = useState( false );
	const [ knownItems, setKnownItems ] = useState< Item[] >( () => {
		if ( ! isAdaptiveMode ) {
			return [];
		}

		const rawRefs = data?.[ fieldProperty ];
		if ( ! Array.isArray( rawRefs ) ) {
			return [];
		}

		return rawRefs
			.filter( isTaxonomyTermRef )
			.map( ( ref ) => {
				const name = ( ref as { id: number; name?: string } ).name;
				return name
					? {
							value: ref.id.toString(),
							label: decodeEntities( name ),
					  }
					: null;
			} )
			.filter( ( item ): item is Item => item !== null );
	} );

	const { elements: fieldItems, isLoading: isFieldLoading } = useElements( {
		elements: isAdaptiveMode
			? undefined
			: ( field.elements as Item[] | undefined ),
		getElements: isAdaptiveMode
			? undefined
			: ( field as { getElements?: () => Promise< Item[] > } )
					.getElements,
	} );

	const {
		items: adaptiveItems,
		isLoading: isAdaptiveLoading,
		isServerSearch,
	} = useAdaptiveTaxonomy( {
		taxonomy,
		inputValue: isAdaptiveMode ? inputValue : '',
		knownItems: isAdaptiveMode ? knownItems : [],
		threshold: serverSearchThreshold,
		termCount,
	} );

	const isLoading = isAdaptiveMode ? isAdaptiveLoading : isFieldLoading;
	const baseItems = isAdaptiveMode ? adaptiveItems : fieldItems;

	const items = useMemo( () => {
		const existingValues = new Set(
			baseItems.map( ( item ) => item.value )
		);
		const missingKnownItems = knownItems.filter(
			( item ) => ! existingValues.has( item.value )
		);

		return [ ...baseItems, ...missingKnownItems ];
	}, [ baseItems, knownItems ] );

	const itemsByLabel = useMemo(
		() => new Map( items.map( ( item ) => [ item.label, item ] ) ),
		[ items ]
	);

	const selectedItems = useMemo( () => {
		const rawRefs = data?.[ fieldProperty ];
		const termRefs: TaxonomyTermRef[] = Array.isArray( rawRefs )
			? rawRefs.filter( isTaxonomyTermRef )
			: [];

		const itemsById = new Map(
			items.map( ( item ) => [ item.value, item ] )
		);

		return termRefs
			.map( ( ref ) => itemsById.get( ref.id.toString() ) )
			.filter( ( item ): item is Item => item !== undefined );
	}, [ data, fieldProperty, items ] );

	const selectedTokens = useMemo(
		() => selectedItems.map( ( item ) => item.label ),
		[ selectedItems ]
	);

	const suggestions = useMemo( () => {
		const selectedLabels = new Set( selectedTokens );
		return items
			.filter( ( item ) => ! selectedLabels.has( item.label ) )
			.map( ( item ) => item.label );
	}, [ items, selectedTokens ] );

	const handleValueChange = useCallback(
		async ( nextTokens: string[] ) => {
			const matchedItems = nextTokens
				.map( ( token ) => itemsByLabel.get( token ) )
				.filter( ( item ): item is Item => item !== undefined );

			const creatableTokens = nextTokens
				.filter( ( token ) => ! itemsByLabel.has( token ) )
				.map( ( token ) => token.trim() )
				.filter( Boolean );

			if ( creatableTokens.length > 0 ) {
				const termName = creatableTokens[ 0 ];
				setIsCreating( true );

				try {
					const result: unknown = await dispatch(
						coreStore
					).saveEntityRecord(
						'taxonomy',
						taxonomy,
						{ name: termName },
						{ throwOnError: true }
					);

					if ( ! isTermRecord( result ) ) {
						return;
					}

					const newItem: Item = {
						value: result.id.toString(),
						label: decodeEntities( result.name ),
					};

					setKnownItems( ( previousItems ) =>
						mergeKnownItems( previousItems, [ newItem ] )
					);

					onChange(
						createFieldChange< T >(
							fieldProperty,
							itemsToTermRefs( [ ...matchedItems, newItem ] )
						)
					);

					setInputValue( '' );
				} catch ( error ) {
					// eslint-disable-next-line no-console
					console.error(
						sprintf(
							__( 'Failed to create term: %s', 'woocommerce' ),
							getErrorMessage( error )
						)
					);
				} finally {
					setIsCreating( false );
				}

				return;
			}

			if ( isServerSearch ) {
				setKnownItems( ( previousItems ) =>
					mergeKnownItems( previousItems, matchedItems )
				);
			}

			onChange(
				createFieldChange< T >(
					fieldProperty,
					itemsToTermRefs( matchedItems )
				)
			);
		},
		[ fieldProperty, isServerSearch, itemsByLabel, onChange, taxonomy ]
	);

	const help = isLoading ? (
		<span
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				gap: '8px',
			} }
		>
			<Spinner />
			{ field.description || __( 'Loading terms…', 'woocommerce' ) }
		</span>
	) : (
		field.description
	);

	return (
		<BaseControl label={ field.label } help={ help }>
			<FormTokenField
				value={ selectedTokens }
				suggestions={ suggestions }
				onInputChange={ setInputValue }
				onChange={ ( tokens ) => {
					void handleValueChange(
						tokens.map( ( token ) =>
							typeof token === 'string' ? token : token.value
						)
					);
				} }
				disabled={ isCreating }
				placeholder={
					isServerSearch && ! inputValue.trim()
						? __( 'Type to search…', 'woocommerce' )
						: searchPlaceholder ?? __( 'Search', 'woocommerce' )
				}
			/>
		</BaseControl>
	);
}
