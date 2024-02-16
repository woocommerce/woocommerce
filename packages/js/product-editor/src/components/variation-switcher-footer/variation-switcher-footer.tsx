/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { arrowLeft, arrowRight, Icon } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { SwitcherLoadingPlaceholder } from './switcher-loading-placeholder';
import { VariationImagePlaceholder } from './variation-image-placeholder';
import { useVariationSwitcher } from '../../hooks/use-variation-switcher';

export type VariationSwitcherProps = {
	parentProductType?: string;
	variationId?: number;
	parentId?: number;
};

export function VariationSwitcherFooter( {
	parentProductType,
	variationId,
	parentId,
}: VariationSwitcherProps ) {
	const {
		numberOfVariations,
		nextVariationId,
		previousVariationId,
		activeVariationIndex,
		nextVariationIndex,
		previousVariationIndex,
		goToNextVariation,
		goToPreviousVariation,
	} = useVariationSwitcher( {
		variationId,
		parentId,
		parentProductType,
	} );
	const { previousVariation, nextVariation } = useSelect(
		( select ) => {
			const { getEntityRecord } = select( 'core' );
			if ( numberOfVariations && numberOfVariations > 0 ) {
				return {
					previousVariation:
						previousVariationId !== null &&
						getEntityRecord(
							'postType',
							'product_variation',
							previousVariationId
						),
					nextVariation:
						nextVariationId !== null &&
						getEntityRecord(
							'postType',
							'product_variation',
							nextVariationId
						),
				};
			}
			return {};
		},
		[ nextVariationId, previousVariationId, numberOfVariations ]
	);
	function onPrevious() {
		if ( previousVariation ) {
			recordEvent( 'product_variation_switch_previous', {
				variation_length: numberOfVariations,
				variation_id: previousVariation?.id,
				variation_index: activeVariationIndex,
				previous_variation_index: previousVariationIndex,
			} );
			goToPreviousVariation();
		}
	}

	function onNext() {
		if ( nextVariation ) {
			recordEvent( 'product_variation_switch_next', {
				variation_length: numberOfVariations,
				variation_id: nextVariation?.id,
				variation_index: activeVariationIndex,
				next_variation_index: nextVariationIndex,
			} );
			goToNextVariation();
		}
	}

	if ( ! numberOfVariations || numberOfVariations < 2 ) {
		return null;
	}

	return (
		<div className="woocommerce-product-variation-switcher-footer">
			{ previousVariation && (
				<Button
					className="woocommerce-product-variation-switcher-footer__button woocommerce-product-variation-switcher-footer__button-previous"
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
					{ previousVariation.name }
				</Button>
			) }
			{ ! previousVariation && previousVariationId !== null && (
				<SwitcherLoadingPlaceholder position="left" />
			) }
			{ nextVariation && (
				<Button
					className="woocommerce-product-variation-switcher-footer__button woocommerce-product-variation-switcher-footer__button-next"
					label={ __( 'Next', 'woocommerce' ) }
					onClick={ onNext }
				>
					{ nextVariation.name }
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
			) }
			{ ! nextVariation && nextVariationId !== null && (
				<SwitcherLoadingPlaceholder position="right" />
			) }
		</div>
	);
}
