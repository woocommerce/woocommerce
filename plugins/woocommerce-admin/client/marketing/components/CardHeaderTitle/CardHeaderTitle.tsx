/**
 * Internal dependencies
 */
import './CardHeaderTitle.scss';

export const CardHeaderTitle: React.FC< { children: React.ReactNode } > = ( {
	children,
} ) => {
	return (
		<div className="woocommerce-marketing-card-header-title">
			{ children }
		</div>
	);
};
