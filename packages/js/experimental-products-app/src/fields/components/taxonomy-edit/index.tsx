/**
 * External dependencies
 */
import type { ReactNode } from 'react';
import { FormTokenField, Spinner } from '@wordpress/components';
import { store as coreStore, type Term } from '@wordpress/core-data';
import { dispatch, useDispatch } from '@wordpress/data';
import type { DataFormControlProps } from '@wordpress/dataviews';
import { useCallback, useMemo, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __, sprintf } from '@wordpress/i18n';
import { Stack } from '@wordpress/ui';

/**
 * Internal dependencies
 */
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

type TokenValue = string | { value: string };

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
	const term = value as Record< string, unknown >;
	return typeof term.id === 'number' && typeof term.name === 'string';
}

function createFieldChange< T extends Record< string, unknown > >(
	fieldProperty: keyof T,
	value: TaxonomyTermRef[]
): Partial< T > {
	return { [ fieldProperty ]: value } as Partial< T >;
}

function getTokenValue( token: TokenValue ): string {
	return typeof token === 'string' ? token : token.value;
}

function normalizeItemLabel( label: string ): string {
	return label.trim().toLocaleLowerCase();
}

function getHelpContent(
	description: ReactNode,
	isLoading: boolean,
	isServerSearch: boolean,
	inputValue: string
) {
	const shouldPromptToSearch = isServerSearch && ! inputValue.trim();

	if ( ! description && ! isLoading && ! shouldPromptToSearch ) {
		return null;
	}

	return (
		<div className="components-base-control__help">
			<Stack direction="column" style={ { gap: '4px' } }>
				{ description && <span>{ description }</span> }
				{ isLoading && (
					<Stack
						direction="row"
						align="center"
						style={ { gap: '4px' } }
					>
						<Spinner />
						<span>{ __( 'Loading results…', 'woocommerce' ) }</span>
					</Stack>
				) }
				{ shouldPromptToSearch && (
					<span>{ __( 'Type to search…', 'woocommerce' ) }</span>
				) }
			</Stack>
		</div>
	);
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
	const { createErrorNotice } = useDispatch(
		// eslint-disable-next-line @wordpress/data-no-store-string-literals -- @wordpress/notices types are unavailable in this package.
		'core/notices'
	) as {
		createErrorNotice: (
			message: string,
			options?: {
				type?: string;
			}
		) => void | Promise< unknown >;
	};

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

	const itemsByLabel = useMemo( () => {
		return new Map(
			items.map( ( item ) => [ normalizeItemLabel( item.label ), item ] )
		);
	}, [ items ] );

	const selectedItems: Item[] = useMemo( () => {
		const rawRefs = data?.[ fieldProperty ];
		const termRefs: TaxonomyTermRef[] = Array.isArray( rawRefs )
			? rawRefs.filter( isTaxonomyTermRef )
			: [];
		return termRefs
			.map( ( ref ) => itemsMap.get( ref.id.toString() ) )
			.filter( ( item ): item is Item => item !== undefined );
	}, [ data, fieldProperty, itemsMap ] );

	const value = useMemo(
		() => selectedItems.map( ( item ) => item.value ),
		[ selectedItems ]
	);

	const suggestions = useMemo(
		() => items.map( ( item ) => item.value ),
		[ items ]
	);

	const hasImages = useMemo(
		() => items.some( ( item ) => item.image?.src ),
		[ items ]
	);

	const displayTransform = useCallback(
		( token: string ) => itemsMap.get( token )?.label ?? token,
		[ itemsMap ]
	);

	const saveTransform = useCallback(
		( token: string ) => {
			const trimmedToken = token.trim();
			return itemsMap.get( trimmedToken )?.label ?? trimmedToken;
		},
		[ itemsMap ]
	);

	const handleValueChange = useCallback(
		async ( tokens: TokenValue[] ) => {
			const tokenValues = tokens.map( getTokenValue );

			const createdToken = tokenValues.find(
				( token ) =>
					! itemsMap.has( token ) &&
					! itemsByLabel.has( normalizeItemLabel( token ) )
			);

			const resolveSelectedItems = () =>
				tokenValues
					.map(
						( token ) =>
							itemsMap.get( token ) ??
							itemsByLabel.get( normalizeItemLabel( token ) )
					)
					.filter( ( item ): item is Item => item !== undefined );

			if ( createdToken ) {
				const termName = createdToken.trim();
				if ( ! termName ) {
					return;
				}

				const resolvedItems = resolveSelectedItems();

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

						const updatedItems = [ ...resolvedItems, newItem ];

						onChange(
							createFieldChange< T >(
								fieldProperty,
								itemsToTermRefs( updatedItems )
							)
						);

						setInputValue( '' );

						if ( ! isServerSearch ) {
							void (
								dispatch( coreStore ) as unknown as {
									invalidateResolution: (
										selectorName: string,
										args: unknown[]
									) => void | Promise< unknown >;
								}
							 ).invalidateResolution( 'getEntityRecords', [
								'taxonomy',
								taxonomy,
								{ per_page: -1 },
							] );
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
				const resolvedItems = resolveSelectedItems();

				// Track selected items so chips persist across search changes.
				if ( isServerSearch ) {
					setKnownItems( ( prev ) => {
						const known = new Set( prev.map( ( i ) => i.value ) );
						const added = resolvedItems.filter(
							( item ) => ! known.has( item.value )
						);
						return added.length > 0 ? [ ...prev, ...added ] : prev;
					} );
				}

				onChange(
					createFieldChange< T >(
						fieldProperty,
						itemsToTermRefs( resolvedItems )
					)
				);
			}
		},
		[
			itemsByLabel,
			itemsMap,
			taxonomy,
			fieldProperty,
			onChange,
			createErrorNotice,
			isServerSearch,
		]
	);

	const renderSuggestionItem = useCallback(
		( { item: token }: { item: string } ) => {
			const suggestionItem = itemsMap.get( token );

			if ( ! suggestionItem ) {
				return token;
			}

			if ( ! hasImages ) {
				return suggestionItem.label;
			}

			return (
				<Stack
					direction="row"
					align="center"
					style={ { gap: '12px' } }
					className="woocommerce-next-taxonomy-edit__option"
				>
					{ suggestionItem.image?.src ? (
						<img
							src={ suggestionItem.image.src }
							alt={ suggestionItem.image.alt ?? '' }
							className="woocommerce-next-taxonomy-edit__option-thumbnail"
						/>
					) : (
						<span className="woocommerce-next-taxonomy-edit__option-thumbnail woocommerce-next-taxonomy-edit__option-thumbnail--empty" />
					) }
					<span className="woocommerce-next-taxonomy-edit__option-label">
						{ suggestionItem.label }
					</span>
				</Stack>
			);
		},
		[ hasImages, itemsMap ]
	);

	return (
		<div className="woocommerce-next-taxonomy-edit">
			<FormTokenField
				__next40pxDefaultSize
				__experimentalAutoSelectFirstMatch
				__experimentalExpandOnFocus
				__experimentalRenderItem={ renderSuggestionItem }
				__experimentalShowHowTo={ false }
				label={ field.label }
				disabled={ isCreating }
				displayTransform={ displayTransform }
				maxSuggestions={ suggestions.length }
				onChange={ handleValueChange }
				onInputChange={ setInputValue }
				placeholder={
					searchPlaceholder ?? __( 'Search', 'woocommerce' )
				}
				saveTransform={ saveTransform }
				suggestions={ suggestions }
				value={ value }
			/>
			{ getHelpContent(
				field.description,
				isLoading,
				isServerSearch,
				inputValue
			) }
		</div>
	);
}
