/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { isSiteEditorPage } from '@woocommerce/utils';
import { Disabled } from '@wordpress/components';

const AddToCartWithOptionsQuantitySelectorEdit = () => {
	const blockProps = useBlockProps( {
		className:
			'wc-block-add-to-cart-with-options__quantity-selector wc-block-add-to-cart-with-options__quantity-selector--stepper',
	} );

	const isSiteEditor = useSelect(
		( select ) => isSiteEditorPage( select( 'core/edit-site' ) ),
		[]
	);

	return (
		<div { ...blockProps }>
			<Disabled>
				<div className="quantity wc-block-components-quantity-selector">
					<button className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">
						-
					</button>
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
					<button className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">
						+
					</button>
				</div>
			</Disabled>
		</div>
	);
};

export default AddToCartWithOptionsQuantitySelectorEdit;
