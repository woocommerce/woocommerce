/**
 * Internal dependencies
 */
import './CardHeaderTitle.scss';

export const CardHeaderTitle = ( { children }: React.PropsWithChildren ) => {
	return (
		<div className="woocommerce-marketing-card-header-title">
			{ children }
		</div>
	);
};
