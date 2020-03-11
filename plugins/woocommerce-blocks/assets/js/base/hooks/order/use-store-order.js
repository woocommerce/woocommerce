// @todo finish out this hook which will return a store draft order id and
// order loading (likely from payment data context).

export const useStoreOrder = () => {
	const orderId = 0;
	return {
		orderId,
		isLoading: false,
	};
};
