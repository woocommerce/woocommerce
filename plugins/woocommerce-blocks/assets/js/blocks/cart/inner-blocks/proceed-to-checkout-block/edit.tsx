/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import Button from '@woocommerce/base-components/button';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import PageSelector from '@woocommerce/editor-components/page-selector';
import { CART_PAGE_ID } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { defaultButtonLabel } from './constants';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		checkoutPageId: number;
		className: string;
		buttonLabel: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const blockProps = useBlockProps();
	const { checkoutPageId = 0, buttonLabel } = attributes;
	const { current: savedCheckoutPageId } = useRef( checkoutPageId );

	const currentPostId = useSelect(
		( select ) => {
			if ( ! savedCheckoutPageId ) {
				const store = select( 'core/editor' );
				return store.getCurrentPostId();
			}
			return savedCheckoutPageId;
		},
		[ savedCheckoutPageId ]
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				{ ! (
					currentPostId === CART_PAGE_ID && savedCheckoutPageId === 0
				) && (
					<PageSelector
						pageId={ checkoutPageId }
						setPageId={ ( id: number ) =>
							setAttributes( { checkoutPageId: id } )
						}
						labels={ {
							title: __(
								'Proceed to Checkout button',
								'woo-gutenberg-products-block'
							),
							default: __(
								'WooCommerce Checkout Page',
								'woo-gutenberg-products-block'
							),
						} }
					/>
				) }
			</InspectorControls>
			<Button className="wc-block-cart__submit-button">
				<RichText
					multiline={ false }
					allowedFormats={ [] }
					value={ buttonLabel }
					placeholder={ defaultButtonLabel }
					onChange={ ( content ) => {
						setAttributes( {
							buttonLabel: content,
						} );
					} }
				/>
			</Button>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
