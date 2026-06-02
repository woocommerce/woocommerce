declare module '@wordpress/editor/build-types/components/editor-interface/content-slot-fill' {
	export default EditorContentSlotFill;
	declare const EditorContentSlotFill: {
	    name: import("@wordpress/components/build-types/slot-fill/types").SlotKey;
	    Fill: {
	        (props: Omit<import("@wordpress/components/build-types/slot-fill/types").FillComponentProps, "name">): import("react").JSX.Element;
	        displayName: string;
	    };
	    Slot: {
	        (props: import("@wordpress/components/build-types/slot-fill/types").DistributiveOmit<import("@wordpress/components/build-types/slot-fill/types").SlotComponentProps, "name">): import("react").JSX.Element;
	        displayName: string;
	        __unstableName: import("@wordpress/components/build-types/slot-fill/types").SlotKey;
	    };
	};
}
