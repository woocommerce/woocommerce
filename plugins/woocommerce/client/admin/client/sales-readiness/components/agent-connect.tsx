/**
 * External dependencies
 */
import { Card, Text } from '@wordpress/ui';
import { Button, Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { trackReadiness } from '../tracks';

/** The connection card's data, from AgenticCommerceSettings::connect_view(). */
export type ConnectView = {
	connected: boolean;
	/** After a connection attempt: connected, error, or empty. */
	notice: '' | 'connected' | 'error';
	/** Starts the connection; empty once connected. */
	url: string;
};

/**
 * Manage your store from your own agent: connects the store to WordPress.com, so AI apps can reach
 * it through MCP, and says what to do next once it is connected.
 */
export const AgentConnect = ( { connect }: { connect: ConnectView } ) => {
	const [ notice, setNotice ] = useState( connect.notice );

	return (
		<Card.Root className="wc-sales-readiness-card">
			<Card.Header>
				<Card.Title className="wc-sales-readiness-card__title">
					{ __(
						'Manage your store from your own agent',
						'woocommerce'
					) }
				</Card.Title>
			</Card.Header>
			<Card.Content>
				{ notice === 'connected' && (
					<Notice status="success" onRemove={ () => setNotice( '' ) }>
						<strong>
							{ __( 'Connection successful', 'woocommerce' ) }
						</strong>
						<br />
						{ __(
							'Your store is connected to WordPress.com.',
							'woocommerce'
						) }
					</Notice>
				) }
				{ notice === 'error' && (
					<Notice status="error" onRemove={ () => setNotice( '' ) }>
						{ __(
							'Your store couldn’t connect to WordPress.com. Try again.',
							'woocommerce'
						) }
					</Notice>
				) }
				{ connect.connected ? (
					<Text
						variant="body-md"
						className="wc-sales-readiness__muted"
					>
						{ __(
							'Supported AI apps can now connect to this store through MCP. To get started, add WordPress in the AI app you want to use and select this store.',
							'woocommerce'
						) }
					</Text>
				) : (
					<>
						<Text
							variant="body-md"
							className="wc-sales-readiness__muted"
						>
							{ __(
								'Connect this store to WordPress.com so supported AI apps can access your WooCommerce store.',
								'woocommerce'
							) }
						</Text>
						<div className="wc-sales-readiness-card__actions">
							<Button
								variant="primary"
								href={ connect.url }
								onClick={ () =>
									trackReadiness( 'link_clicked', {
										surface: 'overview',
										area: 'agent_connect',
									} )
								}
							>
								{ __(
									'Connect to WordPress.com',
									'woocommerce'
								) }
							</Button>
						</div>
					</>
				) }
			</Card.Content>
		</Card.Root>
	);
};
