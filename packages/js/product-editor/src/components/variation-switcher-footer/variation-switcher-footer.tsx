/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { arrowLeft, arrowRight, Icon } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { Product, ProductVariation } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { getNewPath, navigateTo } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { SwitcherLoadingPlaceholder } from './switcher-loading-placeholder';
import { VariationImagePlaceholder } from './variation-image-placeholder';

export type VariationSwitcherProps = {
	productType?: string;
	variationId: number;
	parentId: number;
};

function getVariationName( variation: ProductVariation ): string {
	return variation.attributes.map( ( attr ) => attr.option ).join( ', ' );
}

export function VariationSwitcherFooter( {
	variationId,
	parentId,
}: VariationSwitcherProps ) {
	const {
		previousVariation,
		nextVariation,
		numberOfVariations,
		...variationIndexes
	} = useSelect(
		( select ) => {
			const { getEntityRecord } = select( 'core' );
			const parentProduct = getEntityRecord< Product >(
				'postType',
				'product',
				parentId
			);
			if ( parentProduct && parentProduct.variations ) {
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
				const previousVariationId =
					parentProduct.variations[ previousVariationIndex ];
				const nextVariationId =
					parentProduct.variations[ nextVariationIndex ];

				return {
					activeVariationIndex,
					nextVariationIndex,
					previousVariationIndex,
					numberOfVariations: parentProduct.variations.length,
					previousVariation: getEntityRecord< ProductVariation >(
						'postType',
						'product_variation',
						previousVariationId
					),
					nextVariation: getEntityRecord< ProductVariation >(
						'postType',
						'product_variation',
						nextVariationId
					),
				};
			}
			return {};
		},
		[ variationId, parentId ]
	);
	function onPrevious() {
		recordEvent( 'product_variation_switch_previous', {
			variation_length: numberOfVariations,
			variation_id: previousVariation?.id,
			variation_index: variationIndexes.activeVariationIndex,
			previous_variation_index: variationIndexes.previousVariationIndex,
		} );
		navigateTo( {
			url: getNewPath(
				{},
				`/product/${ parentId }/variation/${ previousVariation?.id }`
			),
		} );
	}

	function onNext() {
		recordEvent( 'product_variation_switch_next', {
			variation_length: numberOfVariations,
			variation_id: nextVariation?.id,
			variation_index: variationIndexes.activeVariationIndex,
			next_variation_index: variationIndexes.nextVariationIndex,
		} );
		navigateTo( {
			url: getNewPath(
				{},
				`/product/${ parentId }/variation/${ nextVariation?.id }`
			),
		} );
	}

	if ( ! numberOfVariations || numberOfVariations < 2 ) {
		return null;
	}

	return (
		<div className="woocommerce-product-variation-switcher-footer">
			{ previousVariation ? (
				<Button
					className="woocommerce-product-variation-switcher-footer__button"
					label={ __( 'Previous', 'woocommerce' ) }
					onClick={ onPrevious }
				>
					<Icon icon={ arrowLeft } size={ 16 } />
					{ previousVariation.image ? (
						<img
							alt={ previousVariation.image.alt || '' }
							src={ previousVariation.image.src }
							className="woocommerce-product-variation-switcher-footer__product-image"
						/>
					) : (
						<VariationImagePlaceholder className="woocommerce-product-variation-switcher-footer__product-image" />
					) }
					{ getVariationName( previousVariation ) }
				</Button>
			) : (
				<SwitcherLoadingPlaceholder position="left" />
			) }
			{ nextVariation ? (
				<Button
					className="woocommerce-product-variation-switcher-footer__button"
					label={ __( 'Next', 'woocommerce' ) }
					onClick={ onNext }
				>
					{ getVariationName( nextVariation ) }
					{ nextVariation.image ? (
						<img
							alt={ nextVariation.image.alt || '' }
							src={ nextVariation.image.src }
							className="woocommerce-product-variation-switcher-footer__product-image"
						/>
					) : (
						<VariationImagePlaceholder className="woocommerce-product-variation-switcher-footer__product-image" />
					) }
					<Icon icon={ arrowRight } size={ 16 } />
				</Button>
			) : (
				<SwitcherLoadingPlaceholder position="right" />
			) }
		</div>
	);
}
