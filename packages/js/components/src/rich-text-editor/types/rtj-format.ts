// See https://github.com/bloom/DayOne-iOS/wiki/Rich-Text-JSON-Specification
export type LineAttributes = {
	header: number;
	indentLevel: number;
	listStyle: string;
	checked: boolean;
	listIndex: number;
	quote: boolean;
	codeBlock: boolean;
	identifier: string;
};

export type EntryContent = {
	body: string;
	id: string;
	date: number;
	moments: Moment[];
	tags: string[];
	richTextJSON: string;
};

export type RichTextJSON = {
	meta?: Meta;
	contents: TextNode[];
};

export type Meta = {
	version: number;
	'small-lines-removed': boolean;
	created: MetaCreated;
};

export type MetaCreated = {
	platform: string;
	version: number;
};

export type TextNodeAttributes = {
	[ Property in keyof FormattingAttributes ]?: FormattingAttributes[ Property ];
} & {
	line?: Partial< LineAttributes > | undefined;
};

export type FormattingAttributes = {
	bold?: boolean;
	italic?: boolean;
	highlightedColor?: string;
	inlineCode?: boolean;
	strikethrough?: boolean;
	linkURL?: string;
	autolink?: boolean;
	cursorPlacement?: boolean;
};

export type TextNode = {
	attributes?: TextNodeAttributes;
	text: string;
};

// Designed to use as a type alias to describe specific node types.
type SpecificNodeType< K extends keyof LineAttributes > = {
	attributes: {
		[ Property in keyof FormattingAttributes ]?: FormattingAttributes[ Property ];
	} & { line: Pick< LineAttributes, K > };
	text: string;
};

export type HeaderNode = SpecificNodeType< 'header' >;
export type ChecklistNode = SpecificNodeType<
	'checked' | 'indentLevel' | 'identifier'
>;
export type ListNode = SpecificNodeType< 'listStyle' | 'indentLevel' >;
export type QuoteNode = SpecificNodeType< 'quote' >;
export type CodeBlockNode = SpecificNodeType< 'codeBlock' >;
export type CodeSpanNode = {
	attributes: {
		inlineCode: true;
	};
	text: string;
};

export type PlainTextNode = {
	attributes?: {
		[ Property in keyof FormattingAttributes ]?: FormattingAttributes[ Property ];
	} & {
		line?: {
			header?: number;
			identifier?: string;
		};
	};

	text: string;
};

export type EmbeddableNodeAttributes = {
	type:
		| 'photo'
		| 'video'
		| 'audio'
		| 'pdfAttachment'
		| 'externalVideo'
		| 'externalAudio'
		| 'renderableCodeBlock'
		| 'horizontalRuleLine'
		| 'dayOneEntry'
		| 'preview';
};

export type MomentThumbnail = {
	width: number;
	height: number;
	contentType: string;
	md5: string;
	fileSize?: number;
};

// Many props are optional right now, because we don't
// have separate types to describe the many types of media
// that might be a moment. We should add
// Audio,Video and Image moment types.
export type Moment = {
	contentType: string;
	creationDevice: string;
	creationDeviceIdentifier: string;
	date?: number;
	favorite: boolean;
	height?: number;
	id: string;
	isSketch?: boolean;
	md5: string;
	thumbnail?: MomentThumbnail;
	type: string;
	type_?: string;
	width?: number;
	duration?: number;
	title?: string;
	format?: string;
	timeZoneName?: string;
	sampleRate?: string;
	audioChannels?: string;
};

export type EmbeddableNodeDefinition< T extends EmbeddableNodeAttributes > = {
	[ Property in keyof T ]: T[ Property ];
} & { identifier?: string };

export type PhotoNode = EmbeddableNodeDefinition< { type: 'photo' } >;
export type AudioNode = EmbeddableNodeDefinition< { type: 'audio' } >;
export type VideoNode = EmbeddableNodeDefinition< { type: 'video' } >;
export type PdfNode = EmbeddableNodeDefinition< { type: 'pdfAttachment' } >;
export type ExternalVideoNode = EmbeddableNodeDefinition< {
	type: 'pdfAttachment';
	url: string;
} >;
export type ExternalAudioNode = EmbeddableNodeDefinition< {
	type: 'externalAudio';
	url: string;
} >;
export type EmbeddableCodeBlockNode = EmbeddableNodeDefinition< {
	type: 'renderableCodeBlock';
	contents: string;
} >;
export type DayOneEntryNode = EmbeddableNodeDefinition< {
	type: 'dayOneEntry';
	entryID: string;
} >;
export type PreviewNode = EmbeddableNodeDefinition< {
	type: 'preview';
	url: string;
} >;
export type HorizontalRuleLineNode = EmbeddableNodeDefinition< {
	type: 'horizontalRuleLine';
} >;

export type EmbeddableNode =
	| PhotoNode
	| AudioNode
	| VideoNode
	| PdfNode
	| ExternalAudioNode
	| ExternalVideoNode
	| EmbeddableCodeBlockNode
	| DayOneEntryNode
	| PreviewNode
	| HorizontalRuleLineNode;

export type EmbeddableContentNode = {
	embeddedObjects: EmbeddableNode[];
};

// From the spec:
// Although the RTJ format allows for specifying any arbitrary group of embedded objects within a single node,
// clients may place additional restrictions on which objects can actually be grouped together.
// For example, Day One only allows grouping photos and videos; any other kind of object must be the only embedded object in its node.
export type EmbeddedContentNode< T extends EmbeddableNode > = {
	embeddedObjects: T[];
};

export type RTJNode = TextNode | EmbeddableContentNode;
