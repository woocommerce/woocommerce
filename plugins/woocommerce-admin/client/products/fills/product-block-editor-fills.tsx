/**
 * External dependencies
 */
import {
	__experimentalProductMVPFeedbackModalContainer as ProductMVPFeedbackModalContainer,
	__experimentalWooProductMoreMenuItem as WooProductMoreMenuItem,
} from '@woocommerce/product-editor';
import { registerPlugin } from '@wordpress/plugins';
import { WooHeaderItem } from '@woocommerce/admin-layout';

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import {
	FeedbackMenuItem,
	ClassicEditorMenuItem,
} from '../fills/more-menu-items';

const MoreMenuFill = ( { onClose }: { onClose: () => void } ) => {
	const [ id ] = useEntityProp( 'postType', 'product', 'id' );

	return (
		<>
			<FeedbackMenuItem onClose={ onClose } />
			<ClassicEditorMenuItem productId={ id } onClose={ onClose } />
		</>
	);
};

const ProductHeaderFill = () => {
	const [ id ] = useEntityProp( 'postType', 'product', 'id' );

	return <ProductMVPFeedbackModalContainer productId={ id } />;
};

registerPlugin( 'wc-admin-more-menu', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-block-editor',
	render: () => (
		<>
			<WooProductMoreMenuItem>
				{ ( { onClose }: { onClose: () => void } ) => (
					<MoreMenuFill onClose={ onClose } />
				) }
			</WooProductMoreMenuItem>
			<WooHeaderItem name="product">
				<ProductHeaderFill />
			</WooHeaderItem>
		</>
	),
} );
