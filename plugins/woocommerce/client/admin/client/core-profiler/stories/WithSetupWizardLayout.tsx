export const WithSetupWizardLayout = ( Story: React.ComponentType ) => {
	return (
		<div className="woocommerce-profile-wizard__body woocommerce-admin-full-screen">
			<Story />
		</div>
	);
};
