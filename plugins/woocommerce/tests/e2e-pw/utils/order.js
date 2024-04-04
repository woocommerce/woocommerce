export function getOrderIdFromUrl( page ) {
	const regex = /order-received\/(\d+)/;
	return page.url().match( regex )[ 1 ];
}
