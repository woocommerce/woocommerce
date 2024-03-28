export function getOrderIdFromUrl( page ) {
	const regex = /order-received\/(\d+)/;
	const match = page.url().match( regex )[ 1 ];
	console.log( `Order ID: ${ match }` );
	return match;
}
