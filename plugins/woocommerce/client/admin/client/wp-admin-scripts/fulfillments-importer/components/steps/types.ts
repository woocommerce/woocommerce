/**
 * External dependencies
 */
import type { Dispatch } from 'react';

/**
 * Internal dependencies
 */
import type {
	ImporterAction,
	ImporterState,
} from '../../hooks/use-importer-state';

export interface StepComponentProps {
	state: ImporterState;
	dispatch: Dispatch< ImporterAction >;
	onClose: () => void;
}
