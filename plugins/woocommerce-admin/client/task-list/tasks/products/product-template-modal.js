/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { ITEMS_STORE_NAME } from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import { recordEvent } from '@woocommerce/tracks';
import { List } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './product-template-modal.scss';
import { createNoticesFromResponse } from '../../../lib/notices';

export const ONBOARDING_PRODUCT_TEMPLATES_FILTER =
	'woocommerce_admin_onboarding_product_templates';

const PRODUCT_TEMPLATES = [
	{
		key: 'physical',
		title: __( 'Physical product', 'woocommerce-admin' ),
		subtitle: __(
			'Tangible items that get delivered to customers',
			'woocommerce-admin'
		),
	},
	{
		key: 'digital',
		title: __( 'Digital product', 'woocommerce-admin' ),
		subtitle: __(
			'Items that customers download or access through your website',
			'woocommerce-admin'
		),
	},
	{
		key: 'variable',
		title: __( 'Variable product', 'woocommerce-admin' ),
		subtitle: __(
			'Products with several versions that customers can choose from',
			'woocommerce-admin'
		),
	},
];

export default function ProductTemplateModal( { onClose } ) {
	const [ selectedTemplate, setSelectedTemplate ] = useState();
	const [ isRedirecting, setIsRedirecting ] = useState( false );
	const { createProductFromTemplate } = useDispatch( ITEMS_STORE_NAME );

	const createTemplate = () => {
		setIsRedirecting( true );
		recordEvent( 'tasklist_product_template_selection', {
			product_type: selectedTemplate,
		} );
		if ( selectedTemplate ) {
			createProductFromTemplate(
				{
					template_name: selectedTemplate,
					status: 'draft',
				},
				{ _fields: [ 'id' ] }
			).then(
				( data ) => {
					if ( data && data.id ) {
						const link = getAdminLink(
							`post.php?post=${ data.id }&action=edit&wc_onboarding_active_task=products&tutorial=true`
						);
						window.location = link;
					}
				},
				( error ) => {
					// failed creating product with template
					createNoticesFromResponse( error );
					setIsRedirecting( false );
				}
			);
		} else if ( onClose ) {
			recordEvent( 'tasklist_product_template_dismiss' );
			onClose();
		}
	};

	const onSelectTemplateClick = ( event ) => {
		const val = event.target && event.target.value;
		setSelectedTemplate( val );
	};

	const templates = applyFilters(
		ONBOARDING_PRODUCT_TEMPLATES_FILTER,
		PRODUCT_TEMPLATES
	);

	return (
		<Modal
			title={ __( 'Start with a template' ) }
			isDismissible={ true }
			onRequestClose={ () => onClose() }
			className="woocommerce-product-template-modal"
		>
			<div className="woocommerce-product-template-modal__wrapper">
				<div className="woocommerce-product-template-modal__list">
					<List items={ templates }>
						{ ( item, index ) => (
							<div className="woocommerce-list__item-inner">
								<input
									id={ `product-templates-${
										item.key || index
									}` }
									className="components-radio-control__input"
									type="radio"
									name="product-template-options"
									value={ item.key }
									onChange={ onSelectTemplateClick }
									checked={ item.key === selectedTemplate }
								/>
								<label
									className="woocommerce-list__item-text"
									htmlFor={ `product-templates-${
										item.key || index
									}` }
								>
									<div className="woocommerce-list__item-label">
										{ item.title }
									</div>
									<div className="woocommerce-list__item-subtitle">
										{ item.subtitle }
									</div>
								</label>
							</div>
						) }
					</List>
				</div>
				<div className="woocommerce-product-template-modal__actions">
					<Button
						isPrimary
						isBusy={ isRedirecting }
						disabled={ ! selectedTemplate || isRedirecting }
						onClick={ createTemplate }
					>
						{ __( 'Go' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
}
