/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuItem } from '@wordpress/components';
import { info, Icon } from '@wordpress/icons';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import BlockEditorGuide from '~/products/tour/block-editor/block-editor-guide';

export const AboutTheEditorMenuItem = ( {
	onClose,
}: {
	onClose: () => void;
} ) => {
	const [ isGuideOpen, setIsGuideOpen ] = useState( false );
	return (
		<>
			<MenuItem
				onClick={ () => {
					setIsGuideOpen( true );
				} }
				icon={ <Icon icon={ info } /> }
				iconPosition="right"
			>
				{ __( 'About the editor…', 'woocommerce' ) }
			</MenuItem>
			{ isGuideOpen && (
				<BlockEditorGuide
					onCloseGuide={ () => {
						setIsGuideOpen( false );
						onClose();
					} }
				/>
			) }
		</>
	);
};
