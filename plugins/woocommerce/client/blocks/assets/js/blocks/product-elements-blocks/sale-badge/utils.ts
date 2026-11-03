type SaleBadgePrices = {
	price?: string;
	regular_price?: string;
};

/**
 * Returns the discount in the currency's minor unit and as a rounded percentage, or null when the price is not discounted.
 *
 * Shared by the editor preview and the frontend script module, so it must not import packages that script modules cannot load.
 */
export const getSaleBadgeDiscount = (
	prices: SaleBadgePrices | undefined
): { amount: number; percentage: number } | null => {
	const regular = Number( prices?.regular_price );
	const amount = regular - Number( prices?.price );
	if ( ! ( regular > 0 && amount > 0 ) ) {
		return null;
	}
	return { amount, percentage: Math.round( ( amount / regular ) * 100 ) };
};
