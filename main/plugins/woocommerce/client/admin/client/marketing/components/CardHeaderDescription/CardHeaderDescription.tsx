/**
 * Internal dependencies
 */
import './CardHeaderDescription.scss';

export const CardHeaderDescription = ( {
	children,
}: React.PropsWithChildren ) => {
	return (
		<div className="woocommerce-marketing-card-header-description">
			{ children }
		</div>
	);
};
