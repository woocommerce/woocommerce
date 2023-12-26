/**
 * External dependencies
 */
import { MouseEvent } from 'react';

export type TableEmptyOrErrorStateProps = {
	onActionClick( event: MouseEvent< HTMLButtonElement > ): void;
	isError: boolean;
};
