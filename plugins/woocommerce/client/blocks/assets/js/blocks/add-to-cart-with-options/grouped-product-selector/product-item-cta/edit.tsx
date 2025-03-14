/**
 * External dependencies
 */
import { useProductDataContext } from '@woocommerce/shared-context';
import { Disabled, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { isSiteEditorPage } from '@woocommerce/utils';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const CTA = () => {
	const { isLoading, product } = useProductDataContext();
	const isSiteEditor = useSelect(
		( select ) => isSiteEditorPage( select( 'core/edit-site' ) ),
		[]
	);

	if ( isLoading ) {
		return <Spinner />;
	}

	const {
		permalink,
		add_to_cart: productCartDetails,
		has_options: hasOptions,
		is_purchasable: isPurchasable,
		is_in_stock: isInStock,
		sold_individually: soldIndividually,
	} = product;

	if ( ! hasOptions && isPurchasable && isInStock ) {
		if ( soldIndividually ) {
			return (
				<input
					type="checkbox"
					value="1"
					className="wc-grouped-product-add-to-cart-checkbox"
				/>
			);
		}
		return (
			<div className="quantity">
				<input
					style={
						// In the post editor, the editor isn't in an iframe, so WordPress styles are applied. We need to remove them.
						! isSiteEditor
							? {
									backgroundColor: '#ffffff',
									lineHeight: 'normal',
									minHeight: 'unset',
									boxSizing: 'unset',
									borderRadius: 'unset',
							  }
							: {}
					}
					type="number"
					value="1"
					className="input-text qty text"
					readOnly
				/>
			</div>
		);
	}

	return (
		<a
			aria-label={ productCartDetails?.description || '' }
			className="button wp-element-button add_to_cart_button wc-block-components-product-button__button"
			href={ permalink }
		>
			{ productCartDetails?.text || __( 'Add to Cart', 'woocommerce' ) }
		</a>
	);
};

export default function ProductItemCTAEdit() {
	const blockProps = useBlockProps( {
		className:
			'wc-block-add-to-cart-with-options-grouped-product-selector-item-cta',
	} );

	return (
		<div { ...blockProps }>
			<Disabled>
				<CTA />
			</Disabled>
		</div>
	);
}
