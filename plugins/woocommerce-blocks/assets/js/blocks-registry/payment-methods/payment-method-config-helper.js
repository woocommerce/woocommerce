// Filter out payment methods by supported features and cart requirement.
export const canMakePaymentWithFeaturesCheck = ( canMakePayment, features ) => (
	canPayArgument
) => {
	const requirements = canPayArgument.paymentRequirements || [];
	const featuresSupportRequirements = requirements.every( ( requirement ) =>
		features.includes( requirement )
	);
	return featuresSupportRequirements && canMakePayment( canPayArgument );
};
