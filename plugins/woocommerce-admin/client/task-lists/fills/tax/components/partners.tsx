/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, CardHeader } from '@wordpress/components';
import { Children } from '@wordpress/element';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { TaxChildProps } from '../utils';
import './partners.scss';

export const Partners: React.FC< TaxChildProps > = ( {
	children,
	isPending,
	onManual,
	onDisable,
} ) => {
	const classes = clsx(
		'woocommerce-task-card',
		'woocommerce-tax-partners',
		`woocommerce-tax-partners__partners-count-${ Children.count(
			children
		) }`
	);
	return (
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
							{ __( 'I don’t charge sales tax', 'woocommerce' ) }
						</Button>
					</li>
				</ul>
			</CardBody>
		</Card>
	);
};
