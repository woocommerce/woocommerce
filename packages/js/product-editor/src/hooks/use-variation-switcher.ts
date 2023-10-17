/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { Product } from '@woocommerce/data';
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
	const { invalidateResolution } = useDispatch( 'core' );
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
					activeVariationIndex > 0
						? activeVariationIndex - 1
						: parentProduct.variations.length - 1;
				const nextVariationIndex =
					activeVariationIndex !== parentProduct.variations.length - 1
						? activeVariationIndex + 1
						: 0;

				return {
					activeVariationIndex,
					nextVariationIndex,
					previousVariationIndex,
					numberOfVariations: parentProduct.variations.length,
					previousVariationId:
						parentProduct.variations[ previousVariationIndex ],
					nextVariationId:
						parentProduct.variations[ nextVariationIndex ],
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
	}

	function goToNextVariation() {
		if ( variationValues.nextVariationId === undefined ) {
			return false;
		}
		navigateTo( {
			url: getNewPath(
				{},
				`/product/${ parentId }/variation/${ variationValues.nextVariationId }`
			),
		} );
	}

	function goToPreviousVariation() {
		if ( variationValues.previousVariationId === undefined ) {
			return false;
		}
		navigateTo( {
			url: getNewPath(
				{},
				`/product/${ parentId }/variation/${ variationValues.previousVariationId }`
			),
		} );
	}

	return {
		...variationValues,
		invalidateVariationList,
		goToNextVariation,
		goToPreviousVariation,
	};
}
