/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DropdownMenu, MenuItem } from '@wordpress/components';
import { getAdminLink } from '@woocommerce/settings';
import { moreVertical } from '@wordpress/icons';
import { Product } from '@woocommerce/data';
import { registerPlugin } from '@wordpress/plugins';
import { useFormContext } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ClassicEditorIcon } from './images/classic-editor-icon';
import { FeedbackIcon } from './images/feedback-icon';
import { WooHeaderItem } from '~/header/utils';
import './product-more-menu.scss';

export const ProductMoreMenu = () => {
	const { values } = useFormContext< Product >();

	const classEditorUrl = values.id
		? getAdminLink( `post.php?post=${ values.id }&action=edit` )
		: getAdminLink( 'post-new.php?post_type=product' );

	return (
		<WooHeaderItem>
			<DropdownMenu
				className="woocommerce-product-form-more-menu"
				label={ __( 'More product options', 'woocommerce' ) }
				icon={ moreVertical }
				popoverProps={ { position: 'bottom left' } }
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
							// eslint-disable-next-line @typescript-eslint/ban-ts-comment
							// @ts-ignore The href prop exists as buttonProps.
							href={ classEditorUrl }
							icon={ <ClassicEditorIcon /> }
							iconPosition="right"
						>
							{ __( 'Use the classic editor', 'woocommerce' ) }
						</MenuItem>
					</>
				) }
			</DropdownMenu>
		</WooHeaderItem>
	);
};
