export function getOrderIdFromUrl( page ) {
	const regex = /order-received\/(\d+)/;
	try {
		return page.url().match( regex )[ 1 ];
	} catch ( error ) {
		return undefined;
	}
}
