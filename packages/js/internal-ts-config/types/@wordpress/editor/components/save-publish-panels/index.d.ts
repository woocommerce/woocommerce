declare module '@wordpress/editor/build-types/components/save-publish-panels' {
	export default function SavePublishPanels({ setEntitiesSavedStatesCallback, closeEntitiesSavedStates, isEntitiesSavedStatesOpen, forceIsDirtyPublishPanel, }: {
	    setEntitiesSavedStatesCallback: any;
	    closeEntitiesSavedStates: any;
	    isEntitiesSavedStatesOpen: any;
	    forceIsDirtyPublishPanel: any;
	}): import("react").JSX.Element;
	export const ActionsPanelFill: {
	    (props: Omit<import("@wordpress/components/build-types/slot-fill/types").FillComponentProps, "name">): import("react").JSX.Element;
	    displayName: string;
	};
}
