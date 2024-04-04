/**
 * External dependencies
 */
import { List } from '@woocommerce/components';
import { download, Icon } from '@wordpress/icons';

import {
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	ExternalLink,
	Notice,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import strings from './strings';
import { WC_ASSET_URL } from '~/utils/admin-settings';

export interface Apm {
	id: string;
	title: string;
	icon: string;
	description: string;
	link: string;
	extension: string;
}

export const apms: Apm[] = [
	{
		id: 'paypal',
		title: strings.apms.paypal.title,
		icon: `${ WC_ASSET_URL }images/payment_methods/72x72/paypal.png`,
		description: strings.apms.paypal.description,
		link: 'woocommerce.com/products/woocommerce-paypal-payments/',
		extension: 'woocommerce-paypal-payments',
	},
	{
		id: 'amazonpay',
		title: strings.apms.amazonpay.title,
		icon: `${ WC_ASSET_URL }images/payment_methods/72x72/amazonpay.png`,
		description: strings.apms.amazonpay.description,
		link: 'woocommerce.com/products/pay-with-amazon/',
		extension: 'woocommerce-gateway-amazon-payments-advanced',
	},
	{
		id: 'klarna',
		title: strings.apms.klarna.title,
		icon: `${ WC_ASSET_URL }images/payment_methods/72x72/klarna.png`,
		description: strings.apms.klarna.description,
		link: 'woocommerce.com/products/klarna-payments/',
		extension: 'klarna-payments-for-woocommerce',
	},
];

interface ApmListProps {
	enabledApms: Set< Apm >;
	setEnabledApms: ( value: Set< Apm > ) => void;
}

const ApmNotice = ( { enabledApms }: { enabledApms: Set< Apm > } ) => {
	if ( ! enabledApms.size ) {
		return null;
	}

	const extensions = [ ...enabledApms ]
		.map( ( apm ) => apm.title )
		.join( ', ' );
	return (
		<Notice
			status={ 'info' }
			isDismissible={ false }
			className="woopayments-welcome-page__apms-notice"
		>
			<Icon icon={ download } />
			<div>{ strings.apms.installText( extensions ) }</div>
		</Notice>
	);
};

const ApmList: React.FunctionComponent< ApmListProps > = ( {
	enabledApms,
	setEnabledApms,
} ) => {
	const handleToggleChange = ( apm: Apm ) => {
		if ( enabledApms.has( apm ) ) {
			enabledApms.delete( apm );
		} else {
			enabledApms.add( apm );
		}
		setEnabledApms( new Set< Apm >( enabledApms ) );
	};

	const apmsList = apms.map( ( apm ) => ( {
		key: apm.id,
		title: apm.title,
		content: (
			<>
				{ apm.description }{ ' ' }
				<a href={ apm.link } target="_blank" rel="noreferrer">
					{ strings.learnMore }
				</a>
			</>
		),
		before: <img src={ apm.icon } alt="" />,
		after: (
			<ToggleControl
				checked={ enabledApms.has( apm ) }
				onChange={ () => handleToggleChange( apm ) }
			/>
		),
	} ) );
	return (
		<>
			<ApmNotice enabledApms={ enabledApms } />
			<Card size="large" className="woopayments-welcome-page__apms">
				<CardHeader>
					<h1>{ strings.apms.addMoreWaysToPay }</h1>
				</CardHeader>
				<CardBody>
					<List items={ apmsList } />
				</CardBody>
				<CardFooter>
					<ExternalLink href="woocommerce.com/product-category/woocommerce-extensions/payment-gateways/wallets/?categoryIds=28682&collections=product&page=1">
						{ strings.apms.seeMore }
					</ExternalLink>
				</CardFooter>
			</Card>
		</>
	);
};

export default ApmList;
