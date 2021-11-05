/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Bullet } from './bullet';
import './partner-card.scss';

export const PartnerCard: React.FC< {
	name: string;
	logo: string;
	description: string;
	benefits: string[];
	terms: string;
	actionText: string;
	onClick: () => void;
	isPending: boolean;
} > = ( {
	name,
	logo,
	description,
	benefits,
	terms,
	actionText,
	onClick,
	isPending,
} ) => {
	return (
		<div className="woocommerce-tax-partner-card">
			<div className="woocommerce-tax-partner-card__logo">
				<img src={ logo } alt={ name } />
			</div>

			<div className="woocommerce-tax-partner-card__description">
				{ description }
			</div>
			<ul className="woocommerce-tax-partner-card__benefits">
				{ benefits.map( ( benefit, i ) => {
					return (
						<li
							className="woocommerce-tax-partner-card__benefit"
							key={ i }
						>
							<span className="woocommerce-tax-partner-card__benefit-bullet">
								<Bullet />
							</span>
							<span className="woocommerce-tax-partner-card__benefit-text">
								{ benefit }
							</span>
						</li>
					);
				} ) }
			</ul>

			<div className="woocommerce-tax-partner-card__action">
				<div className="woocommerce-tax-partner-card__terms">
					{ terms }
				</div>
				<Button
					isSecondary
					onClick={ onClick }
					isBusy={ isPending }
					disabled={ isPending }
				>
					{ actionText }
				</Button>
			</div>
		</div>
	);
};
