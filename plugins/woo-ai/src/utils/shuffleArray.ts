export const shuffleArray = ( array: string[] ) => {
	let currentIndex = array.length;
	let temporaryValue: string;
	let randomIndex: number;

	while ( currentIndex !== 0 ) {
		randomIndex = Math.floor( Math.random() * currentIndex );
		currentIndex -= 1;

		temporaryValue = array[ currentIndex ];
		array[ currentIndex ] = array[ randomIndex ];
		array[ randomIndex ] = temporaryValue;
	}

	return array;
};
