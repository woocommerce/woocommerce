/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Tooltip } from '@wordpress/components';
import { getNewPath } from '@woocommerce/navigation';
import { help } from '@wordpress/icons';
import { Table } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { Subscription } from './types';
import './my-subscriptions.scss';

export default function MySubscriptions(): JSX.Element {
	const updateConnectionUrl = getNewPath( { page: 'wc-addons', section: 'helper', filter: 'all', 'wc-helper-refresh': 1, 'wc-helper-nonce': getAdminSetting( 'wc_helper_nonces' ).refresh }, '' );
	const updateConnectionHTML = sprintf( __( "If you don't see your subscription, try <a href=\"%s\">updating</a> your connection.", 'woocommerce' ), updateConnectionUrl );

	const tableHeadersInstalled = [
		{
			key: 'name',
			label: __( 'Name', 'woocommerce' ),
		},
		{
			key: 'status',
			label: __( 'Status', 'woocommerce' ),
		},
		{
			key: 'expiry',
			label: __( 'Expiry/Renewal date', 'woocommerce' ),
		},
		{
			key: 'autoRenew',
			label: __( 'Auto-renew', 'woocommerce' ),
		},
		{
			key: 'version',
			label: __( 'Version', 'woocommerce' ),
			isNumeric: true,
		},
		{
			key: 'activated',
			label: __( 'Activated', 'woocommerce' ),
		},
		{
			key: 'actions',
			label: __( 'Actions', 'woocommerce' ),
		},
	];
	const subscriptionsInstalled:Array<Subscription> = [];

	const tableHeadersAvailable = [
		{
			key: 'name',
			label: __( 'Name', 'woocommerce' ),
		},
		{
			key: 'status',
			label: __( 'Status', 'woocommerce' ),
		},
		{
			key: 'expiry',
			label: __( 'Expiry/Renewal date', 'woocommerce' ),
		},
		{
			key: 'autoRenew',
			label: __( 'Auto-renew', 'woocommerce' ),
		},
		{
			key: 'version',
			label: __( 'Version', 'woocommerce' ),
			isNumeric: true,
		},
		{
			key: 'install',
			label: __( 'Install', 'woocommerce' ),
		},
		{
			key: 'actions',
			label: __( 'Actions', 'woocommerce' ),
		},
	];
	const subscriptionsAvailable:Array<Subscription> = [];

	return (
		<div className="woocommerce-marketplace__my-subscriptions">
			<section>
				<h2>
					{ __( 'Installed on this store', 'woocommerce' ) }
				</h2>
				<p>
					<span dangerouslySetInnerHTML={{ __html: updateConnectionHTML }} />
					<Tooltip
						text={
							<>
								<h3>Still don't see your subscriptions?</h3>
								<p>To see all your subscriptions go to your account on WooCommerce.com.</p>
							</>
						}
					>
						<Button	icon={help}	iconSize={20}	isSmall={true} label={ __( 'Help', 'woocommerce' )} />
					</Tooltip>
				</p>
				<Table
					headers={ tableHeadersInstalled }
					rows={ subscriptionsInstalled.map( ( item ) => {
						return [
							{ display: item.name },
							{ display: item.status },
							{ display: item.expiry },
							{ display: item.autoRenew ? 'true' : 'false' },
							{ display: item.version },
							{ display: item.activated ? 'true' : 'false' },
							{ display: '...' },
						];
					} ) }
				/>
			</section>

			<section>
				<h2>
					{ __( 'Available', 'woocommerce' ) }
				</h2>
				<p>
					{ __( 'Your unused and free WooCommerce.com subscriptions.', 'woocommerce' ) }
				</p>
				<Table
					headers={ tableHeadersInstalled }
					rows={ subscriptionsAvailable.map( ( item ) => {
						return [
							{ display: item.name },
							{ display: item.status },
							{ display: item.expiry },
							{ display: item.autoRenew ? 'true' : 'false' },
							{ display: item.version },
							{ display: '...' },
							{ display: '...' },
						];
					} ) }
				/>
			</section>
		</div>
	);
}
