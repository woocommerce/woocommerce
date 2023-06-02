/**
 * Internal dependencies
 */
import BlockEditorTour from './block-editor-tour';
import { useBlockEditorTourOptions } from './use-block-editor-tour-options';

const BlockEditorTourWrapper = () => {
	const blockEditorTourProps = useBlockEditorTourOptions();
	return <BlockEditorTour { ...blockEditorTourProps } />;
};

export default BlockEditorTourWrapper;
