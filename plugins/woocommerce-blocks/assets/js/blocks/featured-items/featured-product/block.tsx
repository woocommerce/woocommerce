/**
 * External dependencies
 */
import { withProduct } from '@woocommerce/block-hocs';
import { withSpokenMessages } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { starEmpty } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { withBlockControls } from '../block-controls';
import { withImageEditor } from '../image-editor';
import { withInspectorControls } from '../inspector-controls';
import { withApiError } from '../with-api-error';
import { withEditMode } from '../with-edit-mode';
import { withEditingImage } from '../with-editing-image';
import { withFeaturedItem } from '../with-featured-item';
import { withUpdateButtonAttributes } from '../with-update-button-attributes';

const GENERIC_CONFIG = {
	icon: starEmpty,
	label: __( 'Featured Product', 'woocommerce' ),
};

const BLOCK_CONTROL_CONFIG = {
	...GENERIC_CONFIG,
	cropLabel: __( 'Edit product image', 'woocommerce' ),
	editLabel: __( 'Edit selected product', 'woocommerce' ),
};

const CONTENT_CONFIG = {
	...GENERIC_CONFIG,
	emptyMessage: __( 'No product is selected.', 'woocommerce' ),
	noSelectionButtonLabel: __( 'Select a product', 'woocommerce' ),
};

const EDIT_MODE_CONFIG = {
	...GENERIC_CONFIG,
	description: __( 'Highlight a product or variation.', 'woocommerce' ),
	editLabel: __( 'Showing Featured Product block preview.', 'woocommerce' ),
};

export default compose( [
	withProduct,
	withSpokenMessages,
	withUpdateButtonAttributes,
	withEditingImage,
	withEditMode( EDIT_MODE_CONFIG ),
	withFeaturedItem( CONTENT_CONFIG ),
	withApiError,
	withImageEditor,
	withInspectorControls,
	withBlockControls( BLOCK_CONTROL_CONFIG ),
] )( () => <></> );
