/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DropdownMenu, MenuItem } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { ClassicEditorIcon } from './images/classic-editor-icon';
import { FeedbackIcon } from './images/feedback-icon';
import { WelcomeGuideIcon } from './images/welcome-guide-icon';
import { WooHeaderItem } from '~/header/utils';

export const ProductMoreMenu = () => {
	return (
		<WooHeaderItem>
			<DropdownMenu
				className="woocommerce-product-form-actions__publish-dropdown"
				label={ __( 'Publish options', 'woocommerce' ) }
				icon={ moreVertical }
				popoverProps={ { position: 'bottom right' } }
			>
				{ ( { onClose } ) => (
					<>
						<MenuItem
							onClick={ () => {
								// @todo This should open the CES modal.
								onClose();
							} }
							icon={ <FeedbackIcon /> }
							iconPosition="right"
						>
							{ __( 'Share feedback', 'woocommerce' ) }
						</MenuItem>
						<MenuItem
							onClick={ () => {
								onClose();
							} }
							icon={ <ClassicEditorIcon /> }
							iconPosition="right"
						>
							{ __( 'Use the classic editor', 'woocommerce' ) }
						</MenuItem>
						<MenuItem
							onClick={ () => {
								onClose();
							} }
							// eslint-disable-next-line @typescript-eslint/ban-ts-comment
							// @ts-ignore The href prop exists as buttonProps.
							href="#"
							icon={ <WelcomeGuideIcon /> }
							iconPosition="right"
							target="_blank"
						>
							{ __( 'Welcome guide', 'woocommerce' ) }
						</MenuItem>
					</>
				) }
			</DropdownMenu>
		</WooHeaderItem>
	);
};

registerPlugin( 'woocommerce-product-more-menu', {
	render: ProductMoreMenu,
	icon: 'admin-generic',
} );
