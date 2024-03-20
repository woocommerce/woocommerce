/**
 * External dependencies
 */
import { type BlockInstance } from '@wordpress/blocks';

type TemplateDetail = {
	type: string;
	title: string;
	placeholder: string;
};

export type TemplateDetails = Record< string, TemplateDetail >;

export type InheritedAttributes = {
	align?: string;
};

export type OnClickCallbackParameter = {
	clientId: string;
	attributes: Record< string, unknown >;
	getBlocks: () => BlockInstance[];
	replaceBlock: ( clientId: string, blocks: BlockInstance[] ) => void;
	selectBlock: ( clientId: string ) => void;
};

type ConversionConfig = {
	onClickCallback: ( params: OnClickCallbackParameter ) => void;
	getButtonLabel: () => string;
	getBlockifiedTemplate: (
		inheritedAttributes: InheritedAttributes
	) => BlockInstance[];
};

export type BlockifiedTemplateConfig = {
	// Description of the template, shown in the block placeholder.
	getDescription: ( templateTitle: string, canConvert: boolean ) => string;
	// Returns the skeleton HTML for the template, or can be left blank to use the default fallback image.
	getSkeleton?: ( () => JSX.Element ) | undefined;
	// Is conversion possible for the template?
	isConversionPossible: () => boolean;
	// If conversion is possible, returns the config for the template to be blockified.
	blockifyConfig?: ConversionConfig | undefined;
};
