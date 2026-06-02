declare module '@wordpress/editor/build-types/components/preferences-modal/enable-plugin-document-setting-panel' {
	export default EnablePluginDocumentSettingPanelOption;
	declare function EnablePluginDocumentSettingPanelOption({ label, panelName }: {
	    label: any;
	    panelName: any;
	}): import("react").JSX.Element;
	declare namespace EnablePluginDocumentSettingPanelOption {
	    export { Slot };
	}
	declare const Slot: {
	    (props: import("@wordpress/components/build-types/slot-fill/types").DistributiveOmit<import("@wordpress/components/build-types/slot-fill/types").SlotComponentProps, "name">): import("react").JSX.Element;
	    displayName: string;
	    __unstableName: import("@wordpress/components/build-types/slot-fill/types").SlotKey;
	};
}
