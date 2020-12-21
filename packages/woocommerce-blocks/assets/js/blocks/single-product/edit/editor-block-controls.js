/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import { Toolbar } from '@wordpress/components';

/**
 * Adds controls to the editor toolbar.
 *
 * @param {Object} props Incoming props for the component.
 * @param {boolean} props.isEditing
 * @param {function(boolean):any} props.setIsEditing
 */
const EditorBlockControls = ( { isEditing, setIsEditing } ) => {
	return (
		<BlockControls>
			<Toolbar
				controls={ [
					{
						icon: 'edit',
						title: __( 'Edit', 'woocommerce' ),
						onClick: () => setIsEditing( ! isEditing ),
						isActive: isEditing,
					},
				] }
			/>
		</BlockControls>
	);
};

export default EditorBlockControls;
