/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	ButtonGroup,
	DropdownMenu,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import { chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './product-form-actions.scss';

export const ProductFormActions: React.FC = () => {
	const onSaveDraft = () => {};

	return (
		<div className="woocommerce-product-form-actions">
			<Button onClick={ onSaveDraft }>
				{ __( 'Save draft', 'woocommerce' ) }
			</Button>
			<Button onClick={ onSaveDraft }>
				{ __( 'Preview', 'woocommerce' ) }
			</Button>
			<ButtonGroup className="woocommerce-product-form-actions__publish-button-group">
				<Button onClick={ onSaveDraft } variant="primary">
					{ __( 'Publish', 'woocommerce' ) }
				</Button>
				<DropdownMenu
					className="woocommerce-product-form-actions__publish-dropdown"
					label={ __( 'Publish options', 'woocommerce' ) }
					icon={ chevronDown }
					toggleProps={ { variant: 'primary' } }
				>
					{ ( { onClose } ) => (
						<>
							<MenuGroup>
								<MenuItem onClick={ onClose }>
									{ __(
										'Publish & duplicate',
										'woocommerce'
									) }
								</MenuItem>
								<MenuItem onClick={ onClose }>
									{ __(
										'Copy to a new draft',
										'woocommerce'
									) }
								</MenuItem>
								<MenuItem onClick={ onClose } isDestructive>
									{ __( 'Move to trash', 'woocommerce' ) }
								</MenuItem>
							</MenuGroup>
						</>
					) }
				</DropdownMenu>
			</ButtonGroup>
		</div>
	);
};
