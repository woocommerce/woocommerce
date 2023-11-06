/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { Disabled, Tooltip } from '@wordpress/components';
import { Skeleton } from '@woocommerce/base-components/skeleton';
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.scss';
import { useIsDescendentOfSingleProductBlock } from '../shared/use-is-descendent-of-single-product-block';
export interface Attributes {
	className?: string;
	isDescendentOfSingleProductBlock: boolean;
}

const Edit = ( props: BlockEditProps< Attributes > ) => {
	const { setAttributes } = props;
	const blockProps = useBlockProps( {
		className: 'wc-block-add-to-cart-form',
	} );
	const { isDescendentOfSingleProductBlock } =
		useIsDescendentOfSingleProductBlock( {
			blockClientId: blockProps?.id,
		} );

	useEffect( () => {
		setAttributes( {
			isDescendentOfSingleProductBlock,
		} );
	}, [ setAttributes, isDescendentOfSingleProductBlock ] );

	return (
		<div { ...blockProps }>
			<Tooltip
				text="Customer will see product add-to-cart options in this space, dependent on the product type. "
				position="bottom right"
			>
				<div className="wc-block-editor-add-to-cart-form-container">
					<Skeleton numberOfLines={ 3 } />
					<Disabled>
						<div className="quantity">
							<input
								type={ 'number' }
								value={ '1' }
								className={ 'input-text qty text' }
								readOnly
							/>
						</div>
						<button
							className={ `single_add_to_cart_button button alt wp-element-button` }
						>
							{ __(
								'Add to cart',
								'woo-gutenberg-products-block'
							) }
						</button>
					</Disabled>
				</div>
			</Tooltip>
		</div>
	);
};

export default Edit;
