/**
 * The line total for `quantity` while it saves: the unit price multiplied by the quantity. Only when the saved total
 * is exactly the unit price multiplied by the saved quantity; otherwise, such as with a bulk discount, it returns the
 * saved total and the line waits for the server. Amounts are in minor units; `unitPrice` has `precision` decimals.
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
