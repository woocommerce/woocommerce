export const moveIndex = (
	fromIndex: number,
	toIndex: number,
	arr: unknown[]
) => {
	const newArr = [ ...arr ];
	const item = arr[ fromIndex ];
	newArr.splice( fromIndex, 1 );
	newArr.splice( toIndex, 0, item );
	return newArr;
};
