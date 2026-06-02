declare module '@wordpress/block-editor/components/observe-typing' {
	import { ComponentType, ReactNode } from "react";

	declare namespace ObserveTyping {
	    interface Props {
	        children: ReactNode;
	    }
	}
	declare const ObserveTyping: ComponentType<ObserveTyping.Props>;

	export default ObserveTyping;
}
