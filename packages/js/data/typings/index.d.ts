import { select, resolveSelect, dispatch } from '@wordpress/data';
import { Entity } from '@wordpress/core-data';


declare module '@wordpress/data' {
	// TODO: update @wordpress/data types to include this.
	const controls: {
		select: select;
		resolveSelect: resolveSelect;
		dispatch: dispatch;
	};


}
