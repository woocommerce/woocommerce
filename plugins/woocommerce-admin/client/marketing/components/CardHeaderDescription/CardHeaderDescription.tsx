/**
 * Internal dependencies
 */
import './CardHeaderDescription.scss';

export const CardHeaderDescription: React.FC< {
	children: React.ReactNode;
} > = ( { children } ) => {
	return (
		<div className="woocommerce-marketing-card-header-description">
			{ children }
		</div>
	);
};
