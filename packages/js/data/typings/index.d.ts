/**
 * External dependencies
 */
import { Entity } from '@wordpress/core-data';
import * as controls from '@wordpress/data-controls';


declare module '@wordpress/data' {
	// TODO: update @wordpress/data types to include this.
	const controls: {
		select: typeof controls.select;
		resolveSelect: typeof controls.resolveSelect;
		dispatch: typeof controls.dispatch;
	};
}

declare module 'rememo';
