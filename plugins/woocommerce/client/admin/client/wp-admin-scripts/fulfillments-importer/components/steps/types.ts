/**
 * Internal dependencies
 */
import type {
	ImporterAction,
	ImporterState,
} from '../../hooks/use-importer-state';

export interface StepComponentProps {
	state: ImporterState;
	dispatch: React.Dispatch< ImporterAction >;
	onClose: () => void;
}
