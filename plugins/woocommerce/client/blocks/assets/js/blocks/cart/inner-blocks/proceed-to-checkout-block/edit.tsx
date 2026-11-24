/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import EditableButton from '@woocommerce/editor-components/editable-button';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import PageSelector from '@woocommerce/editor-components/page-selector';
import { CART_PAGE_ID } from '@woocommerce/block-settings';
import { useStyleProps } from '@woocommerce/base-hooks';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { defaultButtonLabel } from './constants';
import type { ButtonStyleAttributes } from '../../../cart-checkout-shared/types';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: ButtonStyleAttributes & {
		checkoutPageId: number;
		className: string;
		buttonLabel: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const blockProps = useBlockProps();
	const buttonStyleProps = useStyleProps( attributes );
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
								'woocommerce'
							),
							default: __(
								'WooCommerce Checkout Page',
								'woocommerce'
							),
						} }
					/>
				) }
			</InspectorControls>
			<EditableButton
				className={ clsx(
					'wc-block-cart__submit-button',
					buttonStyleProps.className
				) }
				style={ buttonStyleProps.style }
				value={ buttonLabel }
				placeholder={ defaultButtonLabel }
				onChange={ ( content ) => {
					setAttributes( {
						buttonLabel: content,
					} );
				} }
			/>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
