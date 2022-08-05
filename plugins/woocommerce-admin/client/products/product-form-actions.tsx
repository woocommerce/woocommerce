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
import { useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './product-form-actions.scss';
import { useProductHelper } from './use-product-helper';

export const ProductFormActions: React.FC = () => {
	const {
		createProductWithStatus,
		updateProductWithStatus,
		deleteProductAndRedirect,
		copyProductWithStatus,
	} = useProductHelper();
	const { isDirty, values } = useFormContext< Product >();

	const onSaveDraft = () => {
		recordEvent( 'product_edit', {
			new_product_page: true,
		} );
		if ( ! values.id ) {
			createProductWithStatus( values, 'draft' );
		} else {
			updateProductWithStatus( values, 'draft' );
		}
	};

	const onPublish = () => {
		recordEvent( 'product_update', {
			new_product_page: true,
		} );
		if ( ! values.id ) {
			createProductWithStatus( values, 'publish' );
		} else {
			updateProductWithStatus( values, 'publish' );
		}
	};

	const onPublishAndDuplicate = async () => {
		recordEvent( 'product_update_and_duplicate', {
			new_product_page: true,
		} );
		if ( values.id ) {
			await updateProductWithStatus( values, 'publish' );
		} else {
			await createProductWithStatus( values, 'publish', false, true );
		}
		await copyProductWithStatus( values );
	};

	const onCopyToNewDraft = async () => {
		recordEvent( 'product_copy', {
			new_product_page: true,
		} );
		if ( values.id ) {
			await updateProductWithStatus( values, values.status || 'draft' );
		}
		await copyProductWithStatus( values );
	};

	const onTrash = () => {
		recordEvent( 'product_delete', {
			new_product_page: true,
		} );
		if ( values.id ) {
			deleteProductAndRedirect( values.id );
		}
	};

	const isPublished = values.id && values.status === 'publish';

	return (
		<div className="woocommerce-product-form-actions">
			{ values.status !== 'publish' ? (
				<Button
					onClick={ onSaveDraft }
					disabled={ ! isDirty && !! values.id }
				>
					{ __( 'Save draft', 'woocommerce' ) }
				</Button>
			) : null }
			<Button
				onClick={ () =>
					recordEvent( 'product_preview', {
						new_product_page: true,
					} )
				}
				href={ values.permalink + '?preview=true' }
				disabled={ ! values.permalink }
				target="_blank"
			>
				{ __( 'Preview', 'woocommerce' ) }
			</Button>
			<ButtonGroup className="woocommerce-product-form-actions__publish-button-group">
				<Button
					onClick={ onPublish }
					variant="primary"
					disabled={ ! isDirty && !! isPublished }
				>
					{ isPublished
						? __( 'Update', 'woocommerce' )
						: __( 'Publish', 'woocommerce' ) }
				</Button>
				<DropdownMenu
					className="woocommerce-product-form-actions__publish-dropdown"
					label={ __( 'Publish options', 'woocommerce' ) }
					icon={ chevronDown }
					popoverProps={ { position: 'bottom left' } }
					toggleProps={ { variant: 'primary' } }
				>
					{ () => (
						<>
							<MenuGroup>
								<MenuItem onClick={ onPublishAndDuplicate }>
									{ __(
										'Publish & duplicate',
										'woocommerce'
									) }
								</MenuItem>
								<MenuItem onClick={ onCopyToNewDraft }>
									{ __(
										'Copy to a new draft',
										'woocommerce'
									) }
								</MenuItem>
								<MenuItem onClick={ onTrash } isDestructive>
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
