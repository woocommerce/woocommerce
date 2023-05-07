/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DropdownMenu } from '@wordpress/components';
import { useFormContext } from '@woocommerce/components';
import { useSelect } from '@wordpress/data';
import { WooHeaderItem } from '@woocommerce/admin-layout';
import { moreVertical } from '@wordpress/icons';
import { OPTIONS_STORE_NAME, Product } from '@woocommerce/data';
import { ALLOW_TRACKING_OPTION_NAME } from '@woocommerce/customer-effort-score';

/**
 * Internal dependencies
 */

import {
	FeedbackMenuItem,
	ClassicEditorMenuItem,
} from './fills/more-menu-items';

import './product-more-menu.scss';

export const ProductMoreMenu = () => {
	const { values } = useFormContext< Product >();
	const { resolving: isLoading } = useSelect( ( select ) => {
		const { hasFinishedResolution } = select( OPTIONS_STORE_NAME );

		const resolving = ! hasFinishedResolution( 'getOption', [
			ALLOW_TRACKING_OPTION_NAME,
		] );

		return {
			resolving,
		};
	} );

	if ( isLoading ) {
		return null;
	}

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
						<FeedbackMenuItem onClose={ onClose } />
						<ClassicEditorMenuItem
							productId={ values.id }
							onClose={ onClose }
						/>
					</>
				) }
			</DropdownMenu>
		</WooHeaderItem>
	);
};
