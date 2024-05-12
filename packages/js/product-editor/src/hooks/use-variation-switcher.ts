/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	Product,
} from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';

type VariationSwitcherProps = {
	parentProductType?: string;
	variationId?: number;
	parentId?: number;
};

export function useVariationSwitcher( {
	variationId,
	parentId,
	parentProductType,
}: VariationSwitcherProps ) {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	const { invalidateResolution } = useDispatch( 'core' );
	const { invalidateResolutionForStoreSelector } = useDispatch(
		EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
	);
	const variationValues = useSelect(
		( select ) => {
			if ( parentId === undefined ) {
				return {};
			}
			const { getEntityRecord } = select( 'core' );
			const parentProduct = getEntityRecord< Product >(
				'postType',
				parentProductType || 'product',
				parentId
			);
			if (
				variationId !== undefined &&
				parentProduct &&
				parentProduct.variations
			) {
				const activeVariationIndex =
					parentProduct.variations.indexOf( variationId );
				const previousVariationIndex =
					activeVariationIndex > 0 ? activeVariationIndex - 1 : null;
				const nextVariationIndex =
					activeVariationIndex !== parentProduct.variations.length - 1
						? activeVariationIndex + 1
						: null;

				return {
					activeVariationIndex,
					nextVariationIndex,
					previousVariationIndex,
					numberOfVariations: parentProduct.variations.length,
					previousVariationId:
						previousVariationIndex !== null
							? parentProduct.variations[ previousVariationIndex ]
							: null,
					nextVariationId:
						nextVariationIndex !== null
							? parentProduct.variations[ nextVariationIndex ]
							: null,
				};
			}
			return {};
		},
		[ variationId, parentId ]
	);

	function invalidateVariationList() {
		invalidateResolution( 'getEntityRecord', [
			'postType',
			parentProductType || 'product',
			parentId,
		] );
		invalidateResolutionForStoreSelector( 'getProductVariations' );
		invalidateResolutionForStoreSelector(
			'getProductVariationsTotalCount'
		);
	}

	function goToVariation( id: number ) {
		navigateTo( {
			url: getNewPath( {}, `/product/${ parentId }/variation/${ id }` ),
		} );
	}

	function goToNextVariation() {
		if (
			variationValues.nextVariationId === undefined ||
			variationValues.nextVariationId === null
		) {
			return false;
		}
		goToVariation( variationValues.nextVariationId );
		return true;
	}

	function goToPreviousVariation() {
		if (
			variationValues.previousVariationId === undefined ||
			variationValues.previousVariationId === null
		) {
			return false;
		}
		goToVariation( variationValues.previousVariationId );
		return true;
	}

	return {
		...variationValues,
		invalidateVariationList,
		goToVariation,
		goToNextVariation,
		goToPreviousVariation,
	};
}
