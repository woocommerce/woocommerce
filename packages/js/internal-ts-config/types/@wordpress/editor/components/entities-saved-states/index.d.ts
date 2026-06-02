declare module '@wordpress/editor/build-types/components/entities-saved-states' {
	/**
	 * Renders the component for managing saved states of entities.
	 *
	 * @param {Object}   props              The component props.
	 * @param {Function} props.close        The function to close the dialog.
	 * @param {boolean}  props.renderDialog Whether to render the component with modal dialog behavior.
	 * @param {string}   props.variant      Changes the layout of the component. When an `inline` value is provided, the action buttons are rendered at the end of the component instead of at the start.
	 *
	 * @return {React.ReactNode} The rendered component.
	 */
	export default function EntitiesSavedStates({ close, renderDialog, variant, }: {
	    close: Function;
	    renderDialog: boolean;
	    variant: string;
	}): React.ReactNode;
	/**
	 * Renders a panel for saving entities with dirty records.
	 *
	 * @param {Object}   props                       The component props.
	 * @param {string}   props.additionalPrompt      Additional prompt to display.
	 * @param {Function} props.close                 Function to close the panel.
	 * @param {Function} props.onSave                Function to call when saving entities.
	 * @param {boolean}  props.saveEnabled           Flag indicating if save is enabled.
	 * @param {string}   props.saveLabel             Label for the save button.
	 * @param {boolean}  props.renderDialog          Whether to render the component with modal dialog behavior.
	 * @param {Array}    props.dirtyEntityRecords    Array of dirty entity records.
	 * @param {boolean}  props.isDirty               Flag indicating if there are dirty entities.
	 * @param {Function} props.setUnselectedEntities Function to set unselected entities.
	 * @param {Array}    props.unselectedEntities    Array of unselected entities.
	 * @param {string}   props.variant               Changes the layout of the component. When an `inline` value is provided, the action buttons are rendered at the end of the component instead of at the start.
	 *
	 * @return {React.ReactNode} The rendered component.
	 */
	export function EntitiesSavedStatesExtensible({ additionalPrompt, close, onSave, saveEnabled: saveEnabledProp, saveLabel, renderDialog, dirtyEntityRecords, isDirty, setUnselectedEntities, unselectedEntities, variant, }: {
	    additionalPrompt: string;
	    close: Function;
	    onSave: Function;
	    saveEnabled: boolean;
	    saveLabel: string;
	    renderDialog: boolean;
	    dirtyEntityRecords: any[];
	    isDirty: boolean;
	    setUnselectedEntities: Function;
	    unselectedEntities: any[];
	    variant: string;
	}): React.ReactNode;
}
