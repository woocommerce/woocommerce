/**
 * The line total to show for a cart item while its new quantity saves. Cart line totals do not wait for the server
 * (Skeleton reveal rule): the total is the unit price multiplied by the new quantity.
 *
 * This is only correct if the pricing of the item is linear. The item is linear if the saved line total is the unit
 * price multiplied by the saved quantity. If not, for example with a bulk discount or a fee per line, the saved
 * total is returned, and the line total waits for the server.
 *
 * @param args                The prices and quantities of the item.
 * @param args.unitPrice      The unit price, with `precision` decimals (`prices.raw_prices.price`).
 * @param args.precision      The decimals of `unitPrice` (`prices.raw_prices.precision`).
 * @param args.minorUnit      The decimals of the line total, from its currency.
 * @param args.savedQuantity  The quantity of the item on the server.
 * @param args.savedLineTotal The line total from the server for `savedQuantity`, in minor units, as it shows.
 * @param args.quantity       The quantity that shows, which can still save.
 * @return The line total for `quantity`, in minor units.
 */
export const getOptimisticLineTotal = ( {
	unitPrice,
	precision,
	minorUnit,
	savedQuantity,
	savedLineTotal,
	quantity,
}: {
	unitPrice: number;
	precision: number;
	minorUnit: number;
	savedQuantity: number;
	savedLineTotal: number;
	quantity: number;
} ): number => {
	if ( quantity === savedQuantity || ! Number.isFinite( unitPrice ) ) {
		return savedLineTotal;
	}
	const scale = 10 ** ( precision - minorUnit );
	const getLineTotal = ( lineQuantity: number ) =>
		Math.round( ( unitPrice * lineQuantity ) / scale );

	return getLineTotal( savedQuantity ) === savedLineTotal
		? getLineTotal( quantity )
		: savedLineTotal;
};
