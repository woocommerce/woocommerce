/**
 * External dependencies
 */
import { H } from '@woocommerce/components';

export const Benefit = ( { description, icon, title } ) => {
	return (
		<div className="woocommerce-profile-wizard__benefit-card" key={ title }>
			{ icon }
			<div className="woocommerce-profile-wizard__benefit-card-content">
				<H className="woocommerce-profile-wizard__benefit-card-title">
					{ title }
				</H>
				<p>{ description }</p>
			</div>
		</div>
	);
};
