/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { getTerms } from '@woocommerce/editor-components/utils';
import { getSetting } from '@woocommerce/settings';
import { AttributeSetting } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { AttributeWithTerms } from './types';

export default function useProductAttributes( shouldLoadAttributes: boolean ) {
	const STORE_ATTRIBUTES = getSetting< AttributeSetting[] >(
		'attributes',
		[]
	);
	const [ isLoadingAttributes, setIsLoadingAttributes ] = useState( false );
	const [ productsAttributes, setProductsAttributes ] = useState<
		AttributeWithTerms[]
	>( [] );
	const hasLoadedAttributes = useRef( false );

	useEffect( () => {
		if (
			! shouldLoadAttributes ||
			isLoadingAttributes ||
			hasLoadedAttributes.current
		)
			return;

		async function fetchTerms() {
			setIsLoadingAttributes( true );

			for ( const attribute of STORE_ATTRIBUTES ) {
				const terms = await getTerms(
					Number( attribute.attribute_id )
				);

				setProductsAttributes( ( oldAttributes ) => [
					...oldAttributes,
					{
						...attribute,
						terms,
					},
				] );
			}

			hasLoadedAttributes.current = true;
			setIsLoadingAttributes( false );
		}

		fetchTerms();

		return () => {
			hasLoadedAttributes.current = true;
		};
	}, [ STORE_ATTRIBUTES, isLoadingAttributes, shouldLoadAttributes ] );

	return { isLoadingAttributes, productsAttributes };
}
