/**
 * External dependencies
 */
import { Spinner } from '@wordpress/components';
import { store as coreStore, type Term } from '@wordpress/core-data';
import { dispatch, useDispatch } from '@wordpress/data';
import type { DataFormControlProps } from '@wordpress/dataviews';
import { useCallback, useMemo, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { Stack } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import {
	SearchableChipSelectControl,
	Combobox,
} from '../searchable-chip-select';
import { useElements } from './use-elements';
import { useAdaptiveTaxonomy } from './use-adaptive-taxonomy';
import type { Item, ItemImage, TaxonomyTermRef } from './types';

export type { Item, ItemImage, TaxonomyTermRef };

const CREATABLE_VALUE = '__create__';

type TaxonomyEditProps< T > = {
	taxonomy: string;
	fieldProperty: keyof T;
	searchPlaceholder?: string;
	/**
	 * When set, fetches up to this many terms initially. If the store has
	 * more, switches to server-side search. If omitted, uses the field's
	 * elements/getElements for client-side filtering.
	 */
	serverSearchThreshold?: number;
	/**
	 * Known total term count for this taxonomy (e.g. from hydrated boot data).
	 * Used with serverSearchThreshold to decide search mode without extra requests.
	 */
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
		.filter( ( item ) => item.value !== CREATABLE_VALUE )
		.map( ( item ) => ( { id: parseInt( item.value, 10 ) } ) )
		.filter( ( ref ) => ! Number.isNaN( ref.id ) );
}

function isTaxonomyTermRef( value: unknown ): value is TaxonomyTermRef {
	if ( typeof value !== 'object' || value === null ) {
		return false;
	}
	if ( ! ( 'id' in value ) ) {
		return false;
	}
	const { id } = value as { id: unknown };
	return typeof id === 'number';
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

function getEmptyContent(
	isLoading: boolean,
	isServerSearch: boolean,
	inputValue: string
) {
	if ( isLoading ) {
		return <Spinner />;
	}
	if ( isServerSearch && ! inputValue.trim() ) {
		return __( 'Type to search…', 'woocommerce' );
	}
	return __( 'No results found.', 'woocommerce' );
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
	// Tracks items the user has selected or created, so chips persist
	// even when search results change. Initialized from entity data.
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
	const { createErrorNotice } = useDispatch( noticesStore );

	// Legacy mode: load all elements from field definition.
	const { elements: fieldItems, isLoading: isFieldLoading } = useElements( {
		elements: isAdaptiveMode
			? undefined
			: ( field.elements as Item[] | undefined ),
		getElements: isAdaptiveMode
			? undefined
			: ( field.getElements as ( () => Promise< Item[] > ) | undefined ),
	} );

	// Adaptive mode: probes term count, uses client-side or server search.
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

	const items: Item[] = useMemo( () => {
		const existingValues = new Set( baseItems.map( ( i ) => i.value ) );
		const newItems = knownItems.filter(
			( i ) => ! existingValues.has( i.value )
		);
		return [ ...baseItems, ...newItems ];
	}, [ baseItems, knownItems ] );

	const itemsMap = useMemo( () => {
		return new Map( items.map( ( item ) => [ item.value, item ] ) );
	}, [ items ] );

	const value: Item[] = useMemo( () => {
		const rawRefs = data?.[ fieldProperty ];
		const termRefs: TaxonomyTermRef[] = Array.isArray( rawRefs )
			? rawRefs.filter( isTaxonomyTermRef )
			: [];
		return termRefs
			.map( ( ref ) => itemsMap.get( ref.id.toString() ) )
			.filter( ( item ): item is Item => item !== undefined );
	}, [ data, fieldProperty, itemsMap ] );

	const creatableItem: Item | undefined = useMemo( () => {
		const termName = inputValue.trim();
		if ( ! termName || isCreating ) {
			return undefined;
		}
		return {
			value: CREATABLE_VALUE,
			label: sprintf(
				/* translators: %s: the name of the new term to create */
				__( 'Create "%s"', 'woocommerce' ),
				termName
			),
		};
	}, [ inputValue, isCreating ] );

	const hasImages = useMemo(
		() => items.some( ( item ) => item.image?.src ),
		[ items ]
	);

	const handleValueChange = useCallback(
		async ( newItems: Item[] ) => {
			const creatableSelected = newItems.find(
				( item ) => item.value === CREATABLE_VALUE
			);

			if ( creatableSelected ) {
				const termName = inputValue.trim();
				if ( ! termName ) {
					return;
				}

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
						// eslint-disable-next-line no-console
						console.error(
							'[TaxonomyEdit] Unexpected response from saveEntityRecord:',
							result
						);
						return;
					}

					const newTerm = result;

					if ( newTerm.id ) {
						const newItem: Item = {
							value: newTerm.id.toString(),
							label: decodeEntities( newTerm.name ),
						};

						setKnownItems( ( prev ) => [ ...prev, newItem ] );

						const updatedItems = [
							...newItems.filter(
								( item ) => item.value !== CREATABLE_VALUE
							),
							newItem,
						];

						onChange(
							createFieldChange< T >(
								fieldProperty,
								itemsToTermRefs( updatedItems )
							)
						);

						setInputValue( '' );

						if ( ! isServerSearch ) {
							void dispatch( coreStore ).invalidateResolution(
								'getEntityRecords',
								[ 'taxonomy', taxonomy, { per_page: -1 } ]
							);
						}
					}
				} catch ( error ) {
					void createErrorNotice(
						sprintf(
							/* translators: %s: error message */
							__( 'Failed to create term: %s', 'woocommerce' ),
							getErrorMessage( error )
						),
						{
							type: 'snackbar',
						}
					);
				} finally {
					setIsCreating( false );
				}
			} else {
				// Track selected items so chips persist across search changes.
				if ( isServerSearch ) {
					setKnownItems( ( prev ) => {
						const known = new Set( prev.map( ( i ) => i.value ) );
						const added = newItems.filter(
							( i ) =>
								i.value !== CREATABLE_VALUE &&
								! known.has( i.value )
						);
						return added.length > 0 ? [ ...prev, ...added ] : prev;
					} );
				}

				onChange(
					createFieldChange< T >(
						fieldProperty,
						itemsToTermRefs( newItems )
					)
				);
			}
		},
		[
			inputValue,
			taxonomy,
			fieldProperty,
			onChange,
			createErrorNotice,
			isServerSearch,
		]
	);

	return (
		<SearchableChipSelectControl
			label={ field.label }
			description={ field.description }
			items={ items }
			value={ value }
			onValueChange={ handleValueChange }
			inputValue={ inputValue }
			onInputValueChange={ setInputValue }
			creatableItem={ isLoading ? undefined : creatableItem }
			placeholderChip={
				value.length === 0 ? field.placeholder : undefined
			}
			searchPlaceholder={
				searchPlaceholder ?? __( 'Search', 'woocommerce' )
			}
			disabled={ isCreating }
			emptyContent={ getEmptyContent(
				isLoading,
				isServerSearch,
				inputValue
			) }
			// Disable client-side filtering when using server-side search.
			{ ...( isServerSearch ? { filter: null } : {} ) }
			chipsContent={
				hasImages
					? ( selectedItems: Item[] ) =>
							selectedItems.map( ( item ) => (
								<Combobox.ChipWithRemove
									key={ item.value }
									prefix={
										item.image?.src ? (
											<img
												src={ item.image.src }
												alt={ item.image.alt ?? '' }
												className="woocommerce-next-taxonomy-edit__chip-thumbnail"
											/>
										) : (
											<span className="woocommerce-next-taxonomy-edit__chip-thumbnail woocommerce-next-taxonomy-edit__chip-thumbnail--empty" />
										)
									}
								>
									{ item.label }
								</Combobox.ChipWithRemove>
							) )
					: undefined
			}
		>
			{ hasImages
				? ( item: Item ) => (
						<Combobox.Item
							key={ item.value }
							value={ item }
							disabled={ item.disabled }
						>
							<Stack
								direction="row"
								align="center"
								style={ { gap: '12px' } }
								className="woocommerce-next-taxonomy-edit__option"
							>
								{ item.image?.src ? (
									<img
										src={ item.image.src }
										alt={ item.image.alt ?? '' }
										className="woocommerce-next-taxonomy-edit__option-thumbnail"
									/>
								) : (
									<span className="woocommerce-next-taxonomy-edit__option-thumbnail woocommerce-next-taxonomy-edit__option-thumbnail--empty" />
								) }
								<span className="woocommerce-next-taxonomy-edit__option-label">
									{ item.label }
								</span>
							</Stack>
						</Combobox.Item>
				  )
				: undefined }
		</SearchableChipSelectControl>
	);
}
