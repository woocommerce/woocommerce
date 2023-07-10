/**
 * External dependencies
 */
import { MenuGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	__experimentalProductMVPFeedbackModalContainer as ProductMVPFeedbackModalContainer,
	__experimentalWooProductMoreMenuItem as WooProductMoreMenuItem,
} from '@woocommerce/product-editor';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { registerPlugin } from '@wordpress/plugins';
import { useSelect } from '@wordpress/data';
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
	AboutTheEditorMenuItem,
} from '../fills/more-menu-items';

const MoreMenuFill = ( { onClose }: { onClose: () => void } ) => {
	const [ id ] = useEntityProp( 'postType', 'product', 'id' );

	const { type, status } = useSelect(
		( select ) => {
			const { getEntityRecord } = select( 'core' );
			return getEntityRecord( 'postType', 'product', id ) as Product;
		},
		[ id ]
	);

	const recordClick = ( optionName: string ) => {
		recordEvent( 'product_dropdown_option_click', {
			selected_option: optionName,
			product_type: type,
			product_status: status,
		} );
	};

	return (
		<>
			<MenuGroup label={ __( 'New product form (Beta)', 'woocommerce' ) }>
				<AboutTheEditorMenuItem
					onClick={ () => {
						recordClick( 'about' );
					} }
					onCloseGuide={ () => {
						onClose();
					} }
				/>
				<FeedbackMenuItem
					onClick={ () => {
						recordClick( 'feedback' );
						onClose();
					} }
				/>
			</MenuGroup>
			<MenuGroup>
				<ClassicEditorMenuItem
					productId={ id }
					onClick={ () => {
						recordClick( 'classic_editor' );
						onClose();
					} }
				/>
			</MenuGroup>
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
