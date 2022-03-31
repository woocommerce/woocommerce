/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, RadioControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import {
	ITEMS_STORE_NAME,
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './product-template-modal.scss';
import { createNoticesFromResponse } from '../../../lib/notices';
import { getCountryCode } from '../../../dashboard/utils';

export const ONBOARDING_PRODUCT_TEMPLATES_FILTER =
	'woocommerce_admin_onboarding_product_templates';

const getProductTemplates = () => [
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
	{
		key: 'subscription',
		title: __( 'Subscription product', 'woocommerce-admin' ),
		subtitle: __(
			'Products that customers receive or gain access to regularly by paying in advance',
			'woocommerce-admin'
		),
	},
];

export default function ProductTemplateModal( { onClose } ) {
	const [ selectedTemplate, setSelectedTemplate ] = useState( null );
	const [ isRedirecting, setIsRedirecting ] = useState( false );
	const { createProductFromTemplate } = useDispatch( ITEMS_STORE_NAME );
	const { countryCode, profileItems } = useSelect( ( select ) => {
		const { getProfileItems } = select( ONBOARDING_STORE_NAME );
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { general: settings = {} } = getSettings( 'general' );

		return {
			countryCode: getCountryCode( settings.woocommerce_default_country ),
			profileItems: getProfileItems(),
		};
	} );
	const { installedPlugins } = useSelect( ( select ) => {
		const { getInstalledPlugins } = select( PLUGINS_STORE_NAME );

		return {
			installedPlugins: getInstalledPlugins(),
		};
	} );

	const createTemplate = () => {
		setIsRedirecting( true );
		recordEvent( 'tasklist_product_template_selection', {
			product_type: selectedTemplate,
		} );
		if ( selectedTemplate === 'subscription' ) {
			window.location = getAdminLink(
				'post-new.php?post_type=product&subscription_pointers=true'
			);
			return;
		}
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

	const removeSubscriptions =
		( window.wcAdminFeatures && ! window.wcAdminFeatures.subscriptions ) ||
		countryCode !== 'US' ||
		! profileItems.product_types?.includes( 'subscriptions' ) ||
		! installedPlugins.includes( 'woocommerce-payments' );

	const productTemplates = removeSubscriptions
		? getProductTemplates().filter(
				( template ) => template.key !== 'subscription'
		  )
		: getProductTemplates();

	/**
	 * An object defining a product template.
	 *
	 * @typedef {Object} template
	 * @property {string} key      Icon to render.
	 * @property {string} title    Url.
	 * @property {string} subtitle Link title.
	 */

	/**
	 * Store product templates.
	 *
	 * @filter woocommerce_admin_onboarding_product_templates
	 * @param {Array.<template>} templates Array of product templates.
	 */
	const templates = applyFilters(
		ONBOARDING_PRODUCT_TEMPLATES_FILTER,
		productTemplates
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
					<RadioControl
						selected={ selectedTemplate }
						options={ templates.map( ( item ) => {
							return {
								label: (
									<>
										<span className="woocommerce-product-template-modal__list-title">
											{ item.title }
										</span>
										<span className="woocommerce-product-template-modal__list-subtitle">
											{ item.subtitle }
										</span>
									</>
								),
								value: item.key,
							};
						} ) }
						onChange={ setSelectedTemplate }
					/>
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
