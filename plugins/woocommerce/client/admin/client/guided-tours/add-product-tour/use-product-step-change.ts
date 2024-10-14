/**
 * External dependencies
 */
import { useCallback, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useActiveEditorType } from './use-active-editor-type';

export type ProductTourStepName =
	| 'product-name'
	| 'product-description'
	| 'product-data'
	| 'product-short-description'
	| 'product-image'
	| 'product-tags'
	| 'product-categories';

const getInputValue = ( id: string ) => {
	return ( document.querySelector( id ) as HTMLInputElement ).value;
};

const getTinyMceValue = ( id: string ) => {
	const iframe = document.querySelector< HTMLIFrameElement >( id );
	const tinymce =
		iframe?.contentWindow?.document.querySelector< HTMLElement >(
			'#tinymce'
		);
	return tinymce?.innerHTML || '';
};

const getTextareaValue = ( id: string ) => {
	return document.querySelector< HTMLTextAreaElement >( id )?.value || '';
};

const getProductDescriptionValue = ( isContentEditorTmceActive: boolean ) => {
	return isContentEditorTmceActive
		? getTinyMceValue( '#content_ifr' )
		: getTextareaValue( '#wp-content-editor-container > .wp-editor-area' );
};

const getProductShortDescriptionValue = (
	isExcerptEditorTmceActive: boolean
) => {
	return isExcerptEditorTmceActive
		? getTinyMceValue( '#excerpt_ifr' )
		: getTextareaValue( '#wp-excerpt-editor-container > .wp-editor-area' );
};

const getProductImageValue = () => {
	return (
		document.querySelector< HTMLImageElement >( '#set-post-thumbnail img' )
			?.src || ''
	);
};

// Parses categories into a string of true/false. Should be enough to catch any change.
const getProductCategoriesValue = () => {
	return Array.from(
		document.querySelectorAll< HTMLInputElement >(
			'#product_cat-all #product_catchecklist input'
		)
	)
		.map( ( x ) => x.checked )
		.join( ',' );
};

// Parses all tags as string of tags separated by comma.
const getProductTagsValue = () => {
	return Array.from(
		document.querySelectorAll< HTMLLIElement >( '#product_tag li' )
	)
		.map( ( x ) => ( x.lastChild as Text ).textContent )
		.join( ',' );
};

/**
 * Custom hook that is used to detect if the product form has any changes and isn't empty.
 * This hook returns two functions:
 * 1. setIsLoaded which is used to save initial product form values when form is ready.
 * 2. hasChanged which is used for querying for the step's input changes.
 */
export const useProductStepChange = () => {
	const { isTmce: isContentEditorTmceActive } = useActiveEditorType( {
		editorWrapSelector: '#wp-content-wrap',
	} );
	const { isTmce: isExcerptEditorTmceActive } = useActiveEditorType( {
		editorWrapSelector: '#wp-excerpt-wrap',
	} );
	const [ initialValues, setInitialValues ] = useState<
		Partial< Record< ProductTourStepName, string > >
	>( {} );
	const [ isLoaded, setIsLoaded ] = useState( false );
	const getValues: () => Partial< Record< ProductTourStepName, string > > =
		useCallback( () => {
			return {
				'product-name': getInputValue( '#title' ),
				'product-description': getProductDescriptionValue(
					isContentEditorTmceActive
				),
				// For product data, we're just going to detect change if price is changed.
				'product-data': getInputValue( '#_regular_price' ),
				'product-short-description': getProductShortDescriptionValue(
					isExcerptEditorTmceActive
				),
				'product-image': getProductImageValue(),
				'product-tags': getProductTagsValue(),
				'product-categories': getProductCategoriesValue(),
			};
		}, [ isContentEditorTmceActive, isExcerptEditorTmceActive ] );

	// If value has changed and isn't empty, returns as changed.
	const hasUpdatedInfo: ( key: ProductTourStepName ) => boolean = useCallback(
		( key ) => {
			const newValues = getValues();
			return (
				initialValues[ key ] !== newValues[ key ] &&
				newValues[ key ] !== ''
			);
		},
		[ getValues, initialValues ]
	);

	useEffect( () => {
		if ( isLoaded ) {
			setInitialValues( getValues() );
		}
	}, [ setInitialValues, isLoaded, getValues ] );

	return { setIsLoaded, hasUpdatedInfo };
};
