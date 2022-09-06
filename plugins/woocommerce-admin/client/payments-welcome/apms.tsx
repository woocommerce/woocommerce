/**
 * External dependencies
 */
import { List } from '@woocommerce/components';
import {
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import strings from './strings';
import { WC_ASSET_URL } from '~/utils/admin-settings';

const apms = [
	{
		id: 'paypal',
		title: strings.apms.paypal.title,
		icon: `${ WC_ASSET_URL }images/payment_methods/72x72/paypal.png`,
		description: strings.apms.paypal.description,
		link: 'https://woocommerce.com/products/woocommerce-paypal-payments/',
	},
	{
		id: 'amazonpay',
		title: strings.apms.amazonpay.title,
		icon: `${ WC_ASSET_URL }images/payment_methods/72x72/amazonpay.png`,
		description: strings.apms.amazonpay.description,
		link: 'https://woocommerce.com/products/pay-with-amazon/',
	},
	{
		id: 'klarna',
		title: strings.apms.klarna.title,
		icon: `${ WC_ASSET_URL }images/payment_methods/72x72/klarna.png`,
		description: strings.apms.klarna.description,
		link: 'https://woocommerce.com/products/klarna-payments/',
	},
	{
		id: 'affirm',
		title: strings.apms.affirm.title,
		icon: `${ WC_ASSET_URL }images/payment_methods/72x72/affirm.png`,
		description: strings.apms.affirm.description,
		link: 'https://woocommerce.com/products/woocommerce-gateway-affirm/',
	},
];

export interface ToggleState {
	[ key: string ]: boolean;
}

interface ApmsProps {
	toggleState: ToggleState;
	setToggleState: ( value: ToggleState ) => void;
}

const APMs: React.FunctionComponent< ApmsProps > = ( {
	toggleState,
	setToggleState,
} ) => {
	const handleToggleChange = ( id: string ) => {
		const newValue = ! toggleState[ id ];
		setToggleState( { ...toggleState, [ id ]: newValue } );
	};

	const apmsList = apms.map( ( apm ) => ( {
		key: apm.id,
		title: apm.title,
		content: (
			<>
				{ apm.description }
				<a href={ apm.link }> { strings.learnMore }</a>
			</>
		),
		before: <img src={ apm.icon } alt="" />,
		after: (
			<ToggleControl
				checked={ toggleState[ apm.id ] || false }
				onChange={ () => handleToggleChange( apm.id ) }
			/>
		),
	} ) );
	return (
		<Card size="large" className="connect-account__apms">
			<CardHeader>
				<h1>{ strings.apms.addMoreWaysToPay }</h1>
			</CardHeader>
			<CardBody>
				<List items={ apmsList } />
			</CardBody>
			<CardFooter>
				<a href="https://woocommerce.com/products/">
					{ strings.apms.seeMore }
				</a>
			</CardFooter>
		</Card>
	);
};

export default APMs;
