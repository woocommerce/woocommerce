/**
 * Internal dependencies
 */
import '../../style.scss';

export const WithCustomizeYourStoreLayout = ( Story: React.ComponentType ) => {
	return (
		<div className="woocommerce-customize-store woocommerce-admin-full-screen">
			<Story />
		</div>
	);
};
