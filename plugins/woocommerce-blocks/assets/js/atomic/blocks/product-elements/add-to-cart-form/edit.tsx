/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { Button, Disabled, Notice } from '@wordpress/components';
/**
 * Internal dependencies
 */
import './editor.scss';
export interface Attributes {
	className?: string;
}

const Edit = () => {
	const blockProps = useBlockProps( {
		className: 'wc-block-add-to-cart-form',
	} );

	return (
		<div { ...blockProps }>
			<Disabled>
				<Notice
					className={ 'wc-block-add-to-cart-form__notice' }
					status={ 'warning' }
					isDismissible={ false }
				>
					<p>
						{ __(
							'Customers will see product add-to-cart options displayed here, dependent on the product type.',
							'woo-gutenberg-products-block'
						) }
					</p>
				</Notice>
				<input
					type={ 'number' }
					value={ '1' }
					className={ 'wc-block-add-to-cart-form__quantity' }
				/>
				<Button
					variant={ 'primary' }
					className={ 'wc-block-add-to-cart-form__button' }
				>
					{ __( 'Add to cart', 'woo-gutenberg-products-block' ) }
				</Button>
			</Disabled>
		</div>
	);
};

export default Edit;
