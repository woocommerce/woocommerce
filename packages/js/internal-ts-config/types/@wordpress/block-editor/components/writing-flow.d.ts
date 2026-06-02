declare module '@wordpress/block-editor/components/writing-flow' {
	import { ComponentType, ReactNode } from "react";

	declare namespace WritingFlow {
	    interface Props {
	        children: ReactNode;
	    }
	}
	declare const WritingFlow: ComponentType<WritingFlow.Props>;

	export default WritingFlow;
}
