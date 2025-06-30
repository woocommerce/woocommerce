export const WOOCOMMERCE_ID = 'woocommerce/woocommerce';

export const getDefaultTemplateProps = ( templateTitle: string ) => {
	return {
		templateTitle,
		addedBy: WOOCOMMERCE_ID,
		hasActions: false,
	};
};
