/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment, useState } from '@wordpress/element';
import { Card, CardBody } from '@wordpress/components';
import {
	Icon,
	sidebar,
	chevronRight,
	plusCircle,
	archive,
	download,
} from '@wordpress/icons';
import {
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { List, Pill } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import ProductTemplateModal from './product-template-modal';
import { getCountryCode } from '../../../dashboard/utils';

const getSubTasks = () => [
	{
		key: 'addProductTemplate',
		title: (
			<>
				{ __( 'Start with a template', 'woocommerce' ) }
				<Pill>{ __( 'Recommended', 'woocommerce' ) }</Pill>
			</>
		),
		content: __(
			'Use a template to add physical, digital, and variable products',
			'woocommerce'
		),
		before: <Icon icon={ sidebar }></Icon>,
		after: <Icon icon={ chevronRight } />,
		onClick: () =>
			recordEvent( 'tasklist_add_product', {
				method: 'product_template',
			} ),
	},
	{
		key: 'addProductManually',
		title: __( 'Add manually', 'woocommerce' ),
		content: __(
			'For small stores we recommend adding products manually',
			'woocommerce'
		),
		before: <Icon icon={ plusCircle } />,
		after: <Icon icon={ chevronRight } />,
		onClick: () =>
			recordEvent( 'tasklist_add_product', { method: 'manually' } ),
		href: getAdminLink(
			'post-new.php?post_type=product&wc_onboarding_active_task=products&tutorial=true'
		),
	},
	{
		key: 'importProducts',
		title: __( 'Import via CSV', 'woocommerce' ),
		content: __(
			'For larger stores we recommend importing all products at once via CSV file',
			'woocommerce'
		),
		before: <Icon icon={ archive } />,
		after: <Icon icon={ chevronRight } />,
		onClick: () =>
			recordEvent( 'tasklist_add_product', { method: 'import' } ),
		href: getAdminLink(
			'edit.php?post_type=product&page=product_importer&wc_onboarding_active_task=products'
		),
	},
	{
		key: 'migrateProducts',
		title: __( 'Import from another service', 'woocommerce' ),
		content: __(
			'For stores currently selling elsewhere we suggest using a product migration service',
			'woocommerce'
		),
		before: <Icon icon={ download } />,
		after: <Icon icon={ chevronRight } />,
		onClick: () =>
			recordEvent( 'tasklist_add_product', { method: 'migrate' } ),
		// @todo This should be replaced with the in-app purchase iframe when ready.
		href: 'https://woocommerce.com/products/cart2cart/?utm_medium=product',
		target: '_blank',
	},
];

const Products = () => {
	const [ selectTemplate, setSelectTemplate ] = useState( null );
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

	const subTasks = getSubTasks();

	if (
		window.wcAdminFeatures &&
		window.wcAdminFeatures.subscriptions &&
		countryCode === 'US' &&
		profileItems.product_types?.includes( 'subscriptions' ) &&
		installedPlugins.includes( 'woocommerce-payments' )
	) {
		const task = subTasks.find(
			( { key } ) => key === 'addProductTemplate'
		);
		task.content = __(
			'Use a template to add physical, digital, variable, and subscription products',
			'woocommerce'
		);
	}

	const onTaskClick = ( task ) => {
		task.onClick();
		if ( task.key === 'addProductTemplate' ) {
			setSelectTemplate( true );
		}
	};

	const listItems = subTasks.map( ( task ) => ( {
		...task,
		onClick: () => onTaskClick( task ),
	} ) );

	return (
		<Fragment>
			<Card className="woocommerce-task-card">
				<CardBody size={ null }>
					<List items={ listItems } />
				</CardBody>
			</Card>
			{ selectTemplate ? (
				<ProductTemplateModal
					onClose={ () => setSelectTemplate( null ) }
				/>
			) : null }
		</Fragment>
	);
};

registerPlugin( 'wc-admin-onboarding-task-products', {
	scope: 'woocommerce-tasks',
	render: () => (
		<WooOnboardingTask id="products" variant="control">
			<Products />
		</WooOnboardingTask>
	),
} );
