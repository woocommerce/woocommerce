/**
 * Internal dependencies
 */
import './CardHeaderDescription.scss';

export const CardHeaderDescription: React.FC = ( { children } ) => {
	return (
		<div className="woocommerce-marketing-card-header-description">
			{ children }
		</div>
	);
};
