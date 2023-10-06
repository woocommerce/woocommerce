/**
 * External dependencies
 */
import { MenuGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { __experimentalProductMVPFeedbackModalContainer as ProductMVPFeedbackModalContainer } from '@woocommerce/product-editor';
import { recordEvent } from '@woocommerce/tracks';

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

export const MoreMenuFill = ( { onClose }: { onClose: () => void } ) => {
	const [ id ] = useEntityProp( 'postType', 'product', 'id' );
	const [ type ] = useEntityProp( 'postType', 'product', 'type' );
	const [ status ] = useEntityProp( 'postType', 'product', 'status' );

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

export const ProductHeaderFill = () => {
	const [ id ] = useEntityProp( 'postType', 'product', 'id' );

	return <ProductMVPFeedbackModalContainer productId={ id } />;
};
