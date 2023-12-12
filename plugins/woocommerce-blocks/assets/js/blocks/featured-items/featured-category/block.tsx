/**
 * External dependencies
 */
import { withCategory } from '@woocommerce/block-hocs';
import { withSpokenMessages } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { folderStarred } from '@woocommerce/icons';

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
	icon: folderStarred,
	label: __( 'Featured Category', 'woocommerce' ),
};

const BLOCK_CONTROL_CONFIG = {
	...GENERIC_CONFIG,
	cropLabel: __( 'Edit category image', 'woocommerce' ),
	editLabel: __( 'Edit selected category', 'woocommerce' ),
};

const CONTENT_CONFIG = {
	...GENERIC_CONFIG,
	emptyMessage: __( 'No product category is selected.', 'woocommerce' ),
	noSelectionButtonLabel: __( 'Select a category', 'woocommerce' ),
};

const EDIT_MODE_CONFIG = {
	...GENERIC_CONFIG,
	description: __(
		'Visually highlight a product category and encourage prompt action.',
		'woocommerce'
	),
	editLabel: __( 'Showing Featured Product block preview.', 'woocommerce' ),
};

export default compose( [
	withCategory,
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
