/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, CardHeader } from '@wordpress/components';
import { Children } from '@wordpress/element';
import clsx from 'clsx';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { TaxChildProps } from '../utils';
import { TrackedLink } from '~/components/tracked-link/tracked-link';
import './partners.scss';

export const Partners = ( {
	children,
	isPending,
	onManual,
	onDisable,
}: TaxChildProps ) => {
	const classes = clsx(
		'woocommerce-task-card',
		'woocommerce-tax-partners',
		`woocommerce-tax-partners__partners-count-${ Children.count(
			children
		) }`
	);
	return (
		<>
			<Card className={ classes }>
				<CardHeader>
					{ __( 'Choose a tax partner', 'woocommerce' ) }
				</CardHeader>
				<CardBody>
					<div className="woocommerce-tax-partners__partners">
						{ children }
					</div>
					<ul className="woocommerce-tax-partners__other-actions">
						<li>
							<Button
								isTertiary
								disabled={ isPending }
								isBusy={ isPending }
								onClick={ () => {
									onManual();
								} }
							>
								{ __( 'Set up taxes manually', 'woocommerce' ) }
							</Button>
						</li>
						<li>
							<Button
								isTertiary
								disabled={ isPending }
								isBusy={ isPending }
								onClick={ () => {
									onDisable();
								} }
							>
								{ __(
									'I don’t charge sales tax',
									'woocommerce'
								) }
							</Button>
						</li>
					</ul>
				</CardBody>
			</Card>
			<TrackedLink
				textProps={ {
					as: 'div',
					className:
						'woocommerce-task-dashboard__container woocommerce-task-marketplace-link',
				} }
				message={ __(
					// translators: {{Link}} is a placeholder for a html element.
					'Visit {{Link}}the WooCommerce Marketplace{{/Link}} to find more tax solutions.',
					'woocommerce'
				) }
				eventName="tasklist_tax_visit_marketplace_click"
				targetUrl={ getAdminLink(
					'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=operations'
				) }
				linkType="wc-admin"
			/>
		</>
	);
};
