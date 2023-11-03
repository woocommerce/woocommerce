/**
 * External dependencies
 */
import { MenuGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';
import { Product, ProductVariation } from '@woocommerce/data';
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

export type MoreMenuFillProps = { productType?: string; onClose: () => void };

export const MoreMenuFill = ( {
	productType = 'product',
	onClose,
}: MoreMenuFillProps ) => {
	const [ id ] = useEntityProp( 'postType', productType, 'id' );

	const product = useSelect< Product | ProductVariation >(
		( select ) => {
			const { getEntityRecord } = select( 'core' );

			return getEntityRecord( 'postType', productType, id ) as
				| Product
				| ProductVariation;
		},
		[ id, productType ]
	);

	const recordClick = ( optionName: string ) => {
		recordEvent( 'product_dropdown_option_click', {
			selected_option: optionName,
			product_type: product.type,
			product_status: product.status,
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
					productId={
						( product as ProductVariation ).parent_id ?? product.id
					}
					onClick={ () => {
						recordClick( 'classic_editor' );
						onClose();
					} }
				/>
			</MenuGroup>
		</>
	);
};
