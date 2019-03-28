/** @format */

const updateItem = operations => ( itemType, id, itemData ) => {
	operations.update( [ itemType ], { [ itemType ]: { id, ...itemData } } );
};

export default {
	updateItem,
};
