export const isTryingToDisplayLegacySaleBadge = ( showSaleBadge?: boolean ) => {
	// If the block is pristine, it doesn't have a showSaleBadge attribute
	// but it is supposed to be `true` by default.
	if ( showSaleBadge === undefined ) {
		return true;
	}

	// If the block was edited, it will have a showSaleBadge attribute
	// that we should respect.
	return showSaleBadge;
};
