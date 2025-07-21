// Helper for express payment methods shared logic
export const getExpressPaymentMethodsState = ( {
	availableExpressPaymentMethods = {},
	expressPaymentMethodsInitialized = false,
	registeredExpressPaymentMethods = {},
} ) => {
	const registeredKeys = Object.keys( registeredExpressPaymentMethods || {} );
	const availableKeys = Object.keys( availableExpressPaymentMethods || {} );
	const registeredCount = registeredKeys.length;
	const availableCount = availableKeys.length;

	const hasRegistered = registeredCount > 0;

	// There are registered express payment methods, but they are not initialized.
	// We initialize after calling canMakePayment.
	const hasRegisteredNotInitialized =
		! expressPaymentMethodsInitialized && hasRegistered;

	// There are registered express payment methods, but none are available.
	// Because none passed the canMakePayment check.
	const hasNoValidRegistered =
		expressPaymentMethodsInitialized && hasRegistered && ! availableCount;

	// On first render we default to showing 2 placeholder buttons.
	// For partial updates, we show as many placeholder buttons as there are available express payment methods.
	const availableExpressPaymentsCount = availableCount || 2;

	return {
		hasRegisteredExpressPayments: hasRegistered,
		hasRegisteredNotInitializedExpressPayments: hasRegisteredNotInitialized,
		hasNoValidRegisteredExpressPayments: hasNoValidRegistered,
		availableExpressPaymentsCount,
	};
};
