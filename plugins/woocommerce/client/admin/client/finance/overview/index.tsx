/**
 * External dependencies
 */
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	Notice,
	Spinner,
} from '@wordpress/components';
import { EmptyContent } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useFinanceProviders } from '../data/use-finance-providers';
import { ProviderTable } from './provider-table';
import { PAYMENTS_SETTINGS_PATH } from '../constants';
import './style.scss';

export const FinanceOverview = () => {
	const { providers, isLoading, error } = useFinanceProviders();
	const settingsUrl = getAdminLink( PAYMENTS_SETTINGS_PATH );

	let body;
	if ( isLoading ) {
		body = <Spinner />;
	} else if ( providers.length === 0 ) {
		body = (
			<EmptyContent
				title={ __( 'No payout providers', 'woocommerce' ) }
				message={ __(
					'Connect a payment provider that shares its balance and payouts to see them here.',
					'woocommerce'
				) }
				actionLabel={ __( 'Manage payment providers', 'woocommerce' ) }
				actionURL={ settingsUrl }
			/>
		);
	} else {
		body = <ProviderTable providers={ providers } />;
	}

	return (
		<div className="woocommerce-finance-overview">
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error.message }
				</Notice>
			) }
			<Card>
				<CardHeader>
					<h2 className="woocommerce-finance-overview__title">
						{ __( 'Payout providers', 'woocommerce' ) }
					</h2>
				</CardHeader>
				<CardBody>{ body }</CardBody>
				<CardFooter>
					<Button variant="link" href={ settingsUrl }>
						{ __( 'Add or manage providers', 'woocommerce' ) }
					</Button>
				</CardFooter>
			</Card>
		</div>
	);
};

/*
 * Exported as default to support code-splitting and lazy loading.
 */
export default FinanceOverview;
