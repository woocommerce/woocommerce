export const settings = {
	alignWide: false,
	allowedBlockTypes: true,
	allowedMimeTypes: {
		'jpg|jpeg|jpe': 'image/jpeg',
		gif: 'image/gif',
		png: 'image/png',
		bmp: 'image/bmp',
		'tiff|tif': 'image/tiff',
		webp: 'image/webp',
		ico: 'image/x-icon',
		heic: 'image/heic',
		'asf|asx': 'video/x-ms-asf',
		wmv: 'video/x-ms-wmv',
		wmx: 'video/x-ms-wmx',
		wm: 'video/x-ms-wm',
		avi: 'video/avi',
		divx: 'video/divx',
		flv: 'video/x-flv',
		'mov|qt': 'video/quicktime',
		'mpeg|mpg|mpe': 'video/mpeg',
		'mp4|m4v': 'video/mp4',
		ogv: 'video/ogg',
		webm: 'video/webm',
		mkv: 'video/x-matroska',
		'3gp|3gpp': 'video/3gpp',
		'3g2|3gp2': 'video/3gpp2',
		'txt|asc|c|cc|h|srt': 'text/plain',
		csv: 'text/csv',
		tsv: 'text/tab-separated-values',
		ics: 'text/calendar',
		rtx: 'text/richtext',
		css: 'text/css',
		'htm|html': 'text/html',
		vtt: 'text/vtt',
		dfxp: 'application/ttaf+xml',
		'mp3|m4a|m4b': 'audio/mpeg',
		aac: 'audio/aac',
		'ra|ram': 'audio/x-realaudio',
		wav: 'audio/wav',
		'ogg|oga': 'audio/ogg',
		flac: 'audio/flac',
		'mid|midi': 'audio/midi',
		wma: 'audio/x-ms-wma',
		wax: 'audio/x-ms-wax',
		mka: 'audio/x-matroska',
		rtf: 'application/rtf',
		js: 'application/javascript',
		pdf: 'application/pdf',
		class: 'application/java',
		tar: 'application/x-tar',
		zip: 'application/zip',
		'gz|gzip': 'application/x-gzip',
		rar: 'application/rar',
		'7z': 'application/x-7z-compressed',
		psd: 'application/octet-stream',
		xcf: 'application/octet-stream',
		doc: 'application/msword',
		'pot|pps|ppt': 'application/vnd.ms-powerpoint',
		wri: 'application/vnd.ms-write',
		'xla|xls|xlt|xlw': 'application/vnd.ms-excel',
		mdb: 'application/vnd.ms-access',
		mpp: 'application/vnd.ms-project',
		docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		docm: 'application/vnd.ms-word.document.macroEnabled.12',
		dotx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
		dotm: 'application/vnd.ms-word.template.macroEnabled.12',
		xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		xlsm: 'application/vnd.ms-excel.sheet.macroEnabled.12',
		xlsb: 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
		xltx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
		xltm: 'application/vnd.ms-excel.template.macroEnabled.12',
		xlam: 'application/vnd.ms-excel.addin.macroEnabled.12',
		pptx: 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		pptm: 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
		ppsx: 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
		ppsm: 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
		potx: 'application/vnd.openxmlformats-officedocument.presentationml.template',
		potm: 'application/vnd.ms-powerpoint.template.macroEnabled.12',
		ppam: 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
		sldx: 'application/vnd.openxmlformats-officedocument.presentationml.slide',
		sldm: 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
		'onetoc|onetoc2|onetmp|onepkg': 'application/onenote',
		oxps: 'application/oxps',
		xps: 'application/vnd.ms-xpsdocument',
		odt: 'application/vnd.oasis.opendocument.text',
		odp: 'application/vnd.oasis.opendocument.presentation',
		ods: 'application/vnd.oasis.opendocument.spreadsheet',
		odg: 'application/vnd.oasis.opendocument.graphics',
		odc: 'application/vnd.oasis.opendocument.chart',
		odb: 'application/vnd.oasis.opendocument.database',
		odf: 'application/vnd.oasis.opendocument.formula',
		'wp|wpd': 'application/wordperfect',
		key: 'application/vnd.apple.keynote',
		numbers: 'application/vnd.apple.numbers',
		pages: 'application/vnd.apple.pages',
		woff2: 'font/woff2',
		woff: 'font/woff',
		ttf: 'font/ttf',
		eot: 'application/vnd.ms-fontobject',
		otf: 'application/x-font-opentype',
	},
	defaultEditorStyles: [
		{
			css: ':root{\n  --wp-admin-theme-color:#007cba;\n  --wp-admin-theme-color--rgb:0, 124, 186;\n  --wp-admin-theme-color-darker-10:#006ba1;\n  --wp-admin-theme-color-darker-10--rgb:0, 107, 161;\n  --wp-admin-theme-color-darker-20:#005a87;\n  --wp-admin-theme-color-darker-20--rgb:0, 90, 135;\n  --wp-admin-border-width-focus:2px;\n  --wp-block-synced-color:#7a00df;\n  --wp-block-synced-color--rgb:122, 0, 223;\n}\n@media (-webkit-min-device-pixel-ratio:2),(min-resolution:192dpi){\n  :root{\n    --wp-admin-border-width-focus:1.5px;\n  }\n}\nbody{\n  --wp--style--block-gap:2em;\n  font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Oxygen-Sans,Ubuntu,Cantarell,Helvetica Neue,sans-serif;\n  font-size:18px;\n  line-height:1.5;\n}\n\np{\n  line-height:1.8;\n}\n\n.editor-post-title__block{\n  font-size:2.5em;\n  font-weight:800;\n  margin-bottom:1em;\n  margin-top:2em;\n}',
		},
	],
	blockCategories: [
		{
			slug: 'text',
			title: 'Text',
			icon: null,
		},
		{
			slug: 'media',
			title: 'Media',
			icon: null,
		},
		{
			slug: 'design',
			title: 'Design',
			icon: null,
		},
		{
			slug: 'widgets',
			title: 'Widgets',
			icon: null,
		},
		{
			slug: 'theme',
			title: 'Theme',
			icon: null,
		},
		{
			slug: 'embed',
			title: 'Embeds',
			icon: null,
		},
		{
			slug: 'reusable',
			title: 'Reusable Blocks',
			icon: null,
		},
	],
	isRTL: false,
	imageDefaultSize: 'large',
	imageDimensions: {
		thumbnail: {
			width: 150,
			height: 150,
			crop: true,
		},
		medium: {
			width: 300,
			height: 300,
			crop: false,
		},
		large: {
			width: 1024,
			height: 1024,
			crop: false,
		},
	},
	imageEditing: true,
	imageSizes: [
		{
			slug: 'thumbnail',
			name: 'Thumbnail',
		},
		{
			slug: 'medium',
			name: 'Medium',
		},
		{
			slug: 'large',
			name: 'Large',
		},
		{
			slug: 'full',
			name: 'Full Size',
		},
	],
	maxUploadFileSize: 2097152,
	__unstableGalleryWithImageBlocks: true,
	disableCustomColors: false,
	disableCustomFontSizes: false,
	disableCustomGradients: false,
	disableLayoutStyles: false,
	enableCustomLineHeight: true,
	enableCustomSpacing: true,
	enableCustomUnits: [ '%', 'px', 'em', 'rem', 'vh', 'vw' ],
	siteUrl: 'http://localhost:8888',
	postsPerPage: '10',
	styles: [
		{
			assets: '\n\t\t<svg\n\t\t\txmlns="http://www.w3.org/2000/svg"\n\t\t\tviewBox="0 0 0 0"\n\t\t\twidth="0"\n\t\t\theight="0"\n\t\t\tfocusable="false"\n\t\t\trole="none"\n\t\t\tstyle="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;"\n\t\t>\n\t\t\t<defs>\n\t\t\t\t<filter id="wp-duotone-dark-grayscale">\n\t\t\t\t\t<feColorMatrix\n\t\t\t\t\t\tcolor-interpolation-filters="sRGB"\n\t\t\t\t\t\ttype="matrix"\n\t\t\t\t\t\tvalues="\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t"\n\t\t\t\t\t/>\n\t\t\t\t\t<feComponentTransfer color-interpolation-filters="sRGB" >\n\t\t\t\t\t\t<feFuncR type="table" tableValues="0 0.49803921568627" />\n\t\t\t\t\t\t<feFuncG type="table" tableValues="0 0.49803921568627" />\n\t\t\t\t\t\t<feFuncB type="table" tableValues="0 0.49803921568627" />\n\t\t\t\t\t\t<feFuncA type="table" tableValues="1 1" />\n\t\t\t\t\t</feComponentTransfer>\n\t\t\t\t\t<feComposite in2="SourceGraphic" operator="in" />\n\t\t\t\t</filter>\n\t\t\t</defs>\n\t\t</svg>\n\n\t\t\n\t\t<svg\n\t\t\txmlns="http://www.w3.org/2000/svg"\n\t\t\tviewBox="0 0 0 0"\n\t\t\twidth="0"\n\t\t\theight="0"\n\t\t\tfocusable="false"\n\t\t\trole="none"\n\t\t\tstyle="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;"\n\t\t>\n\t\t\t<defs>\n\t\t\t\t<filter id="wp-duotone-grayscale">\n\t\t\t\t\t<feColorMatrix\n\t\t\t\t\t\tcolor-interpolation-filters="sRGB"\n\t\t\t\t\t\ttype="matrix"\n\t\t\t\t\t\tvalues="\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t"\n\t\t\t\t\t/>\n\t\t\t\t\t<feComponentTransfer color-interpolation-filters="sRGB" >\n\t\t\t\t\t\t<feFuncR type="table" tableValues="0 1" />\n\t\t\t\t\t\t<feFuncG type="table" tableValues="0 1" />\n\t\t\t\t\t\t<feFuncB type="table" tableValues="0 1" />\n\t\t\t\t\t\t<feFuncA type="table" tableValues="1 1" />\n\t\t\t\t\t</feComponentTransfer>\n\t\t\t\t\t<feComposite in2="SourceGraphic" operator="in" />\n\t\t\t\t</filter>\n\t\t\t</defs>\n\t\t</svg>\n\n\t\t\n\t\t<svg\n\t\t\txmlns="http://www.w3.org/2000/svg"\n\t\t\tviewBox="0 0 0 0"\n\t\t\twidth="0"\n\t\t\theight="0"\n\t\t\tfocusable="false"\n\t\t\trole="none"\n\t\t\tstyle="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;"\n\t\t>\n\t\t\t<defs>\n\t\t\t\t<filter id="wp-duotone-purple-yellow">\n\t\t\t\t\t<feColorMatrix\n\t\t\t\t\t\tcolor-interpolation-filters="sRGB"\n\t\t\t\t\t\ttype="matrix"\n\t\t\t\t\t\tvalues="\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t"\n\t\t\t\t\t/>\n\t\t\t\t\t<feComponentTransfer color-interpolation-filters="sRGB" >\n\t\t\t\t\t\t<feFuncR type="table" tableValues="0.54901960784314 0.98823529411765" />\n\t\t\t\t\t\t<feFuncG type="table" tableValues="0 1" />\n\t\t\t\t\t\t<feFuncB type="table" tableValues="0.71764705882353 0.25490196078431" />\n\t\t\t\t\t\t<feFuncA type="table" tableValues="1 1" />\n\t\t\t\t\t</feComponentTransfer>\n\t\t\t\t\t<feComposite in2="SourceGraphic" operator="in" />\n\t\t\t\t</filter>\n\t\t\t</defs>\n\t\t</svg>\n\n\t\t\n\t\t<svg\n\t\t\txmlns="http://www.w3.org/2000/svg"\n\t\t\tviewBox="0 0 0 0"\n\t\t\twidth="0"\n\t\t\theight="0"\n\t\t\tfocusable="false"\n\t\t\trole="none"\n\t\t\tstyle="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;"\n\t\t>\n\t\t\t<defs>\n\t\t\t\t<filter id="wp-duotone-blue-red">\n\t\t\t\t\t<feColorMatrix\n\t\t\t\t\t\tcolor-interpolation-filters="sRGB"\n\t\t\t\t\t\ttype="matrix"\n\t\t\t\t\t\tvalues="\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t"\n\t\t\t\t\t/>\n\t\t\t\t\t<feComponentTransfer color-interpolation-filters="sRGB" >\n\t\t\t\t\t\t<feFuncR type="table" tableValues="0 1" />\n\t\t\t\t\t\t<feFuncG type="table" tableValues="0 0.27843137254902" />\n\t\t\t\t\t\t<feFuncB type="table" tableValues="0.5921568627451 0.27843137254902" />\n\t\t\t\t\t\t<feFuncA type="table" tableValues="1 1" />\n\t\t\t\t\t</feComponentTransfer>\n\t\t\t\t\t<feComposite in2="SourceGraphic" operator="in" />\n\t\t\t\t</filter>\n\t\t\t</defs>\n\t\t</svg>\n\n\t\t\n\t\t<svg\n\t\t\txmlns="http://www.w3.org/2000/svg"\n\t\t\tviewBox="0 0 0 0"\n\t\t\twidth="0"\n\t\t\theight="0"\n\t\t\tfocusable="false"\n\t\t\trole="none"\n\t\t\tstyle="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;"\n\t\t>\n\t\t\t<defs>\n\t\t\t\t<filter id="wp-duotone-midnight">\n\t\t\t\t\t<feColorMatrix\n\t\t\t\t\t\tcolor-interpolation-filters="sRGB"\n\t\t\t\t\t\ttype="matrix"\n\t\t\t\t\t\tvalues="\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t"\n\t\t\t\t\t/>\n\t\t\t\t\t<feComponentTransfer color-interpolation-filters="sRGB" >\n\t\t\t\t\t\t<feFuncR type="table" tableValues="0 0" />\n\t\t\t\t\t\t<feFuncG type="table" tableValues="0 0.64705882352941" />\n\t\t\t\t\t\t<feFuncB type="table" tableValues="0 1" />\n\t\t\t\t\t\t<feFuncA type="table" tableValues="1 1" />\n\t\t\t\t\t</feComponentTransfer>\n\t\t\t\t\t<feComposite in2="SourceGraphic" operator="in" />\n\t\t\t\t</filter>\n\t\t\t</defs>\n\t\t</svg>\n\n\t\t\n\t\t<svg\n\t\t\txmlns="http://www.w3.org/2000/svg"\n\t\t\tviewBox="0 0 0 0"\n\t\t\twidth="0"\n\t\t\theight="0"\n\t\t\tfocusable="false"\n\t\t\trole="none"\n\t\t\tstyle="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;"\n\t\t>\n\t\t\t<defs>\n\t\t\t\t<filter id="wp-duotone-magenta-yellow">\n\t\t\t\t\t<feColorMatrix\n\t\t\t\t\t\tcolor-interpolation-filters="sRGB"\n\t\t\t\t\t\ttype="matrix"\n\t\t\t\t\t\tvalues="\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t"\n\t\t\t\t\t/>\n\t\t\t\t\t<feComponentTransfer color-interpolation-filters="sRGB" >\n\t\t\t\t\t\t<feFuncR type="table" tableValues="0.78039215686275 1" />\n\t\t\t\t\t\t<feFuncG type="table" tableValues="0 0.94901960784314" />\n\t\t\t\t\t\t<feFuncB type="table" tableValues="0.35294117647059 0.47058823529412" />\n\t\t\t\t\t\t<feFuncA type="table" tableValues="1 1" />\n\t\t\t\t\t</feComponentTransfer>\n\t\t\t\t\t<feComposite in2="SourceGraphic" operator="in" />\n\t\t\t\t</filter>\n\t\t\t</defs>\n\t\t</svg>\n\n\t\t\n\t\t<svg\n\t\t\txmlns="http://www.w3.org/2000/svg"\n\t\t\tviewBox="0 0 0 0"\n\t\t\twidth="0"\n\t\t\theight="0"\n\t\t\tfocusable="false"\n\t\t\trole="none"\n\t\t\tstyle="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;"\n\t\t>\n\t\t\t<defs>\n\t\t\t\t<filter id="wp-duotone-purple-green">\n\t\t\t\t\t<feColorMatrix\n\t\t\t\t\t\tcolor-interpolation-filters="sRGB"\n\t\t\t\t\t\ttype="matrix"\n\t\t\t\t\t\tvalues="\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t"\n\t\t\t\t\t/>\n\t\t\t\t\t<feComponentTransfer color-interpolation-filters="sRGB" >\n\t\t\t\t\t\t<feFuncR type="table" tableValues="0.65098039215686 0.40392156862745" />\n\t\t\t\t\t\t<feFuncG type="table" tableValues="0 1" />\n\t\t\t\t\t\t<feFuncB type="table" tableValues="0.44705882352941 0.4" />\n\t\t\t\t\t\t<feFuncA type="table" tableValues="1 1" />\n\t\t\t\t\t</feComponentTransfer>\n\t\t\t\t\t<feComposite in2="SourceGraphic" operator="in" />\n\t\t\t\t</filter>\n\t\t\t</defs>\n\t\t</svg>\n\n\t\t\n\t\t<svg\n\t\t\txmlns="http://www.w3.org/2000/svg"\n\t\t\tviewBox="0 0 0 0"\n\t\t\twidth="0"\n\t\t\theight="0"\n\t\t\tfocusable="false"\n\t\t\trole="none"\n\t\t\tstyle="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;"\n\t\t>\n\t\t\t<defs>\n\t\t\t\t<filter id="wp-duotone-blue-orange">\n\t\t\t\t\t<feColorMatrix\n\t\t\t\t\t\tcolor-interpolation-filters="sRGB"\n\t\t\t\t\t\ttype="matrix"\n\t\t\t\t\t\tvalues="\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t"\n\t\t\t\t\t/>\n\t\t\t\t\t<feComponentTransfer color-interpolation-filters="sRGB" >\n\t\t\t\t\t\t<feFuncR type="table" tableValues="0.098039215686275 1" />\n\t\t\t\t\t\t<feFuncG type="table" tableValues="0 0.66274509803922" />\n\t\t\t\t\t\t<feFuncB type="table" tableValues="0.84705882352941 0.41960784313725" />\n\t\t\t\t\t\t<feFuncA type="table" tableValues="1 1" />\n\t\t\t\t\t</feComponentTransfer>\n\t\t\t\t\t<feComposite in2="SourceGraphic" operator="in" />\n\t\t\t\t</filter>\n\t\t\t</defs>\n\t\t</svg>\n\n\t\t\n\t\t<svg\n\t\t\txmlns="http://www.w3.org/2000/svg"\n\t\t\tviewBox="0 0 0 0"\n\t\t\twidth="0"\n\t\t\theight="0"\n\t\t\tfocusable="false"\n\t\t\trole="none"\n\t\t\tstyle="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;"\n\t\t>\n\t\t\t<defs>\n\t\t\t\t<filter id="wp-duotone-default-filter">\n\t\t\t\t\t<feColorMatrix\n\t\t\t\t\t\tcolor-interpolation-filters="sRGB"\n\t\t\t\t\t\ttype="matrix"\n\t\t\t\t\t\tvalues="\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t\t.299 .587 .114 0 0\n\t\t\t\t\t\t"\n\t\t\t\t\t/>\n\t\t\t\t\t<feComponentTransfer color-interpolation-filters="sRGB" >\n\t\t\t\t\t\t<feFuncR type="table" tableValues="0.13333333333333 0.61960784313725" />\n\t\t\t\t\t\t<feFuncG type="table" tableValues="0.15686274509804 0.97647058823529" />\n\t\t\t\t\t\t<feFuncB type="table" tableValues="0.15686274509804 0.9921568627451" />\n\t\t\t\t\t\t<feFuncA type="table" tableValues="1 1" />\n\t\t\t\t\t</feComponentTransfer>\n\t\t\t\t\t<feComposite in2="SourceGraphic" operator="in" />\n\t\t\t\t</filter>\n\t\t\t</defs>\n\t\t</svg>\n\n\t\t',
			__unstableType: 'svgs',
			isGlobalStyles: false,
		},
		{
			css: 'body{--wp--preset--duotone--dark-grayscale:url(#wp-duotone-dark-grayscale);--wp--preset--duotone--grayscale:url(#wp-duotone-grayscale);--wp--preset--duotone--purple-yellow:url(#wp-duotone-purple-yellow);--wp--preset--duotone--blue-red:url(#wp-duotone-blue-red);--wp--preset--duotone--midnight:url(#wp-duotone-midnight);--wp--preset--duotone--magenta-yellow:url(#wp-duotone-magenta-yellow);--wp--preset--duotone--purple-green:url(#wp-duotone-purple-green);--wp--preset--duotone--blue-orange:url(#wp-duotone-blue-orange);--wp--preset--duotone--default-filter:url(#wp-duotone-default-filter);}',
			__unstableType: 'presets',
			isGlobalStyles: false,
		},
		{
			css: "body{--wp--preset--color--black: #000000;--wp--preset--color--cyan-bluish-gray: #abb8c3;--wp--preset--color--white: #ffffff;--wp--preset--color--pale-pink: #f78da7;--wp--preset--color--vivid-red: #cf2e2e;--wp--preset--color--luminous-vivid-orange: #ff6900;--wp--preset--color--luminous-vivid-amber: #fcb900;--wp--preset--color--light-green-cyan: #7bdcb5;--wp--preset--color--vivid-green-cyan: #00d084;--wp--preset--color--pale-cyan-blue: #8ed1fc;--wp--preset--color--vivid-cyan-blue: #0693e3;--wp--preset--color--vivid-purple: #9b51e0;--wp--preset--color--base: #ffffff;--wp--preset--color--contrast: #000000;--wp--preset--color--primary: #9DFF20;--wp--preset--color--secondary: #345C00;--wp--preset--color--tertiary: #F6F6F6;--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple: linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%);--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan: linear-gradient(135deg,rgb(122,220,180) 0%,rgb(0,208,130) 100%);--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange: linear-gradient(135deg,rgba(252,185,0,1) 0%,rgba(255,105,0,1) 100%);--wp--preset--gradient--luminous-vivid-orange-to-vivid-red: linear-gradient(135deg,rgba(255,105,0,1) 0%,rgb(207,46,46) 100%);--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray: linear-gradient(135deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%);--wp--preset--gradient--cool-to-warm-spectrum: linear-gradient(135deg,rgb(74,234,220) 0%,rgb(151,120,209) 20%,rgb(207,42,186) 40%,rgb(238,44,130) 60%,rgb(251,105,98) 80%,rgb(254,248,76) 100%);--wp--preset--gradient--blush-light-purple: linear-gradient(135deg,rgb(255,206,236) 0%,rgb(152,150,240) 100%);--wp--preset--gradient--blush-bordeaux: linear-gradient(135deg,rgb(254,205,165) 0%,rgb(254,45,45) 50%,rgb(107,0,62) 100%);--wp--preset--gradient--luminous-dusk: linear-gradient(135deg,rgb(255,203,112) 0%,rgb(199,81,192) 50%,rgb(65,88,208) 100%);--wp--preset--gradient--pale-ocean: linear-gradient(135deg,rgb(255,245,203) 0%,rgb(182,227,212) 50%,rgb(51,167,181) 100%);--wp--preset--gradient--electric-grass: linear-gradient(135deg,rgb(202,248,128) 0%,rgb(113,206,126) 100%);--wp--preset--gradient--midnight: linear-gradient(135deg,rgb(2,3,129) 0%,rgb(40,116,252) 100%);--wp--preset--duotone--dark-grayscale: url( '#wp-duotone-dark-grayscale' );--wp--preset--duotone--grayscale: url( '#wp-duotone-grayscale' );--wp--preset--duotone--purple-yellow: url( '#wp-duotone-purple-yellow' );--wp--preset--duotone--blue-red: url( '#wp-duotone-blue-red' );--wp--preset--duotone--midnight: url( '#wp-duotone-midnight' );--wp--preset--duotone--magenta-yellow: url( '#wp-duotone-magenta-yellow' );--wp--preset--duotone--purple-green: url( '#wp-duotone-purple-green' );--wp--preset--duotone--blue-orange: url( '#wp-duotone-blue-orange' );--wp--preset--shadow--natural: 6px 6px 9px rgba(0, 0, 0, 0.2);--wp--preset--shadow--deep: 12px 12px 50px rgba(0, 0, 0, 0.4);--wp--preset--shadow--sharp: 6px 6px 0px rgba(0, 0, 0, 0.2);--wp--preset--shadow--outlined: 6px 6px 0px -3px rgba(255, 255, 255, 1), 6px 6px rgba(0, 0, 0, 1);--wp--preset--shadow--crisp: 6px 6px 0px rgba(0, 0, 0, 1);--wp--preset--font-size--small: 13px;--wp--preset--font-size--medium: clamp(14px, 0.875rem + ((1vw - 3.2px) * 0.469), 20px);--wp--preset--font-size--large: clamp(22.041px, 1.378rem + ((1vw - 3.2px) * 1.091), 36px);--wp--preset--font-size--x-large: clamp(25.014px, 1.563rem + ((1vw - 3.2px) * 1.327), 42px);--wp--preset--font-size--small: clamp(0.875rem, 0.875rem + ((1vw - 0.2rem) * 0.156), 1rem);--wp--preset--font-size--medium: clamp(1rem, 1rem + ((1vw - 0.2rem) * 0.156), 1.125rem);--wp--preset--font-size--large: clamp(1.75rem, 1.75rem + ((1vw - 0.2rem) * 0.156), 1.875rem);--wp--preset--font-size--x-large: 2.25rem;--wp--preset--font-size--xx-large: clamp(4rem, 4rem + ((1vw - 0.2rem) * 7.5), 10rem);--wp--preset--font-family--dm-sans: \"DM Sans\", sans-serif;--wp--preset--font-family--ibm-plex-mono: 'IBM Plex Mono', monospace;--wp--preset--font-family--inter: \"Inter\", sans-serif;--wp--preset--font-family--system-font: -apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Oxygen-Sans,Ubuntu,Cantarell,\"Helvetica Neue\",sans-serif;--wp--preset--font-family--source-serif-pro: \"Source Serif Pro\", serif;--wp--preset--spacing--30: clamp(1.5rem, 5vw, 2rem);--wp--preset--spacing--40: clamp(1.8rem, 1.8rem + ((1vw - 0.48rem) * 2.885), 3rem);--wp--preset--spacing--50: clamp(2.5rem, 8vw, 4.5rem);--wp--preset--spacing--60: clamp(3.75rem, 10vw, 7rem);--wp--preset--spacing--70: clamp(5rem, 5.25rem + ((1vw - 0.48rem) * 9.096), 8rem);--wp--preset--spacing--80: clamp(7rem, 14vw, 11rem);}",
			isGlobalStyles: true,
		},
		{
			css: 'body {margin: 0; --wp--style--global--content-size: 650px; --wp--style--global--wide-size: 1200px;padding-right: 0; padding-left: 0; padding-top: var(--wp--style--root--padding-top); padding-bottom: var(--wp--style--root--padding-bottom) }\n\t\t\t.has-global-padding { padding-right: var(--wp--style--root--padding-right); padding-left: var(--wp--style--root--padding-left); }\n\t\t\t.has-global-padding :where(.has-global-padding) { padding-right: 0; padding-left: 0; }\n\t\t\t.has-global-padding > .alignfull { margin-right: calc(var(--wp--style--root--padding-right) * -1); margin-left: calc(var(--wp--style--root--padding-left) * -1); }\n\t\t\t.has-global-padding :where(.has-global-padding) > .alignfull { margin-right: 0; margin-left: 0; }\n\t\t\t.has-global-padding > .alignfull:where(:not(.has-global-padding)) > :where(.wp-block:not(.alignfull),p,h1,h2,h3,h4,h5,h6,ul,ol) { padding-right: var(--wp--style--root--padding-right); padding-left: var(--wp--style--root--padding-left); }\n\t\t\t.has-global-padding :where(.has-global-padding) > .alignfull:where(:not(.has-global-padding)) > :where(.wp-block:not(.alignfull),p,h1,h2,h3,h4,h5,h6,ul,ol) { padding-right: 0; padding-left: 0;}:where(body .is-layout-flow) > :first-child:first-child { margin-block-start: 0; }:where(body .is-layout-flow) > :last-child:last-child { margin-block-end: 0; }:where(body .is-layout-flow) > * { margin-block-start: 1.5rem; margin-block-end: 0; }:where(body .is-layout-constrained) > :first-child:first-child { margin-block-start: 0; }:where(body .is-layout-constrained) > :last-child:last-child { margin-block-end: 0; }:where(body .is-layout-constrained) > * { margin-block-start: 1.5rem; margin-block-end: 0; }:where(body .is-layout-flex) { gap: 1.5rem; }:where(body .is-layout-grid) { gap: 1.5rem; }body { --wp--style--block-gap: 1.5rem; }body .is-layout-flow > .alignleft { float: left; margin-inline-start: 0; margin-inline-end: 2em; }body .is-layout-flow > .alignright { float: right; margin-inline-start: 2em; margin-inline-end: 0; }body .is-layout-flow > .aligncenter { margin-left: auto !important; margin-right: auto !important; }body .is-layout-constrained > .alignleft { float: left; margin-inline-start: 0; margin-inline-end: 2em; }body .is-layout-constrained > .alignright { float: right; margin-inline-start: 2em; margin-inline-end: 0; }body .is-layout-constrained > .aligncenter { margin-left: auto !important; margin-right: auto !important; }body .is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)) { max-width: var(--wp--style--global--content-size); margin-left: auto !important; margin-right: auto !important; }body .is-layout-constrained > .alignwide { max-width: var(--wp--style--global--wide-size); }body .is-layout-flex { display:flex; }body .is-layout-flex { flex-wrap: wrap; align-items: center; }body .is-layout-flex > * { margin: 0; }body .is-layout-grid { display:grid; }body .is-layout-grid > * { margin: 0; }body{--wp--style--root--padding-top: var(--wp--preset--spacing--40);--wp--style--root--padding-right: var(--wp--preset--spacing--30);--wp--style--root--padding-bottom: var(--wp--preset--spacing--40);--wp--style--root--padding-left: var(--wp--preset--spacing--30);color: var(--wp--preset--color--contrast);background-color: var(--wp--preset--color--base);font-family: var(--wp--preset--font-family--system-font);font-size: var(--wp--preset--font-size--medium);line-height: 1.6;}a{color: var(--wp--preset--color--contrast);text-decoration: underline;}a:hover{text-decoration: none;}a:focus{text-decoration: underline dashed;}a:active{color: var(--wp--preset--color--secondary);text-decoration: none;}h1, h2, h3, h4, h5, h6{font-weight: 400;line-height: 1.4;}h1{font-size: clamp(2.032rem, 2.032rem + ((1vw - 0.2rem) * 1.991), 3.625rem);line-height: 1.2;}h2{font-size: clamp(2.625rem, calc(2.625rem + ((1vw - 0.48rem) * 8.4135)), 3.25rem);line-height: 1.2;}h3{font-size: var(--wp--preset--font-size--x-large);}h4{font-size: var(--wp--preset--font-size--large);}h5{font-size: var(--wp--preset--font-size--medium);font-weight: 700;text-transform: uppercase;}h6{font-size: var(--wp--preset--font-size--medium);text-transform: uppercase;}.wp-element-button, .wp-block-button__link{border-width: 0;border-radius: 0;color: var(--wp--preset--color--contrast);background-color: var(--wp--preset--color--primary);padding: calc(0.667em + 2px) calc(1.333em + 2px);font-family: inherit;font-size: inherit;line-height: inherit;text-decoration: none;}.wp-element-button:hover, .wp-block-button__link:hover{color: var(--wp--preset--color--base);background-color: var(--wp--preset--color--contrast);}.wp-element-button:focus, .wp-block-button__link:focus{color: var(--wp--preset--color--base);background-color: var(--wp--preset--color--contrast);}.wp-element-button:active, .wp-block-button__link:active{color: var(--wp--preset--color--base);background-color: var(--wp--preset--color--secondary);}.wp-element-button:visited, .wp-block-button__link:visited{color: var(--wp--preset--color--contrast);}.wp-block-pullquote{border-style: solid;border-width: 1px 0;margin-top: var(--wp--preset--spacing--40) !important;margin-bottom: var(--wp--preset--spacing--40) !important;font-size: clamp(0.984em, 0.984rem + ((1vw - 0.2em) * 0.645), 1.5em);line-height: 1.3;}.wp-block-pullquote cite{font-size: var(--wp--preset--font-size--small);font-style: normal;text-transform: none;}.wp-block-navigation{font-size: var(--wp--preset--font-size--small);}.wp-block-navigation a{color: inherit;text-decoration: none;}.wp-block-navigation a:hover{text-decoration: underline;}.wp-block-navigation a:focus{text-decoration: underline dashed;}.wp-block-navigation a:active{text-decoration: none;}.wp-block-post-author{font-size: var(--wp--preset--font-size--small);}.wp-block-post-content a{color: var(--wp--preset--color--secondary);}.wp-block-post-excerpt{font-size: var(--wp--preset--font-size--medium);}.wp-block-post-date{font-size: var(--wp--preset--font-size--small);font-weight: 400;}.wp-block-post-date a{text-decoration: none;}.wp-block-post-date a:hover{text-decoration: underline;}.wp-block-post-terms{font-size: var(--wp--preset--font-size--small);}.wp-block-post-title{margin-top: 1.25rem;margin-bottom: 1.25rem;font-weight: 400;}.wp-block-post-title a{text-decoration: none;}.wp-block-post-title a:hover{text-decoration: underline;}.wp-block-post-title a:focus{text-decoration: underline dashed;}.wp-block-post-title a:active{color: var(--wp--preset--color--secondary);text-decoration: none;}.wp-block-comments-title{margin-bottom: var(--wp--preset--spacing--40);font-size: var(--wp--preset--font-size--large);}.wp-block-comment-author-name a{text-decoration: none;}.wp-block-comment-author-name a:hover{text-decoration: underline;}.wp-block-comment-author-name a:focus{text-decoration: underline dashed;}.wp-block-comment-author-name a:active{color: var(--wp--preset--color--secondary);text-decoration: none;}.wp-block-comment-date{font-size: var(--wp--preset--font-size--small);}.wp-block-comment-date a{text-decoration: none;}.wp-block-comment-date a:hover{text-decoration: underline;}.wp-block-comment-date a:focus{text-decoration: underline dashed;}.wp-block-comment-date a:active{color: var(--wp--preset--color--secondary);text-decoration: none;}.wp-block-comment-edit-link{font-size: var(--wp--preset--font-size--small);}.wp-block-comment-reply-link{font-size: var(--wp--preset--font-size--small);}.wp-block-comments-pagination{margin-top: var(--wp--preset--spacing--40);}.wp-block-comments-pagination a{text-decoration: none;}.wp-block-query h2{font-size: var(--wp--preset--font-size--x-large);}.wp-block-query-pagination{font-size: var(--wp--preset--font-size--small);font-weight: 400;}.wp-block-query-pagination a{text-decoration: none;}.wp-block-query-pagination a:hover{text-decoration: underline;}.wp-block-quote{border-width: 1px;padding-right: var(--wp--preset--spacing--30);padding-left: var(--wp--preset--spacing--30);}.wp-block-quote cite{font-size: var(--wp--preset--font-size--small);font-style: normal;}.wp-block-site-title{font-size: var(--wp--preset--font-size--medium);font-weight: normal;line-height: 1.4;}.wp-block-site-title a{text-decoration: none;}.wp-block-site-title a:hover{text-decoration: underline;}.wp-block-site-title a:focus{text-decoration: underline dashed;}.wp-block-site-title a:active{color: var(--wp--preset--color--secondary);text-decoration: none;}.wp-site-blocks > .alignleft { float: left; margin-right: 2em; }.wp-site-blocks > .alignright { float: right; margin-left: 2em; }.wp-site-blocks > .aligncenter { justify-content: center; margin-left: auto; margin-right: auto; }:where(.wp-site-blocks) > * { margin-block-start: 1.5rem; margin-block-end: 0; }:where(.wp-site-blocks) > :first-child:first-child { margin-block-start: 0; }:where(.wp-site-blocks) > :last-child:last-child { margin-block-end: 0; }.has-black-color{color: var(--wp--preset--color--black) !important;}.has-black-background-color{background-color: var(--wp--preset--color--black) !important;}.has-black-border-color{border-color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-color{color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-cyan-bluish-gray-background-color{background-color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-cyan-bluish-gray-border-color{border-color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-color{color: var(--wp--preset--color--white) !important;}.has-white-background-color{background-color: var(--wp--preset--color--white) !important;}.has-white-border-color{border-color: var(--wp--preset--color--white) !important;}.has-pale-pink-color{color: var(--wp--preset--color--pale-pink) !important;}.has-pale-pink-background-color{background-color: var(--wp--preset--color--pale-pink) !important;}.has-pale-pink-border-color{border-color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-color{color: var(--wp--preset--color--vivid-red) !important;}.has-vivid-red-background-color{background-color: var(--wp--preset--color--vivid-red) !important;}.has-vivid-red-border-color{border-color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-color{color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-orange-background-color{background-color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-orange-border-color{border-color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-color{color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-luminous-vivid-amber-background-color{background-color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-luminous-vivid-amber-border-color{border-color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-color{color: var(--wp--preset--color--light-green-cyan) !important;}.has-light-green-cyan-background-color{background-color: var(--wp--preset--color--light-green-cyan) !important;}.has-light-green-cyan-border-color{border-color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-color{color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-vivid-green-cyan-background-color{background-color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-vivid-green-cyan-border-color{border-color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-color{color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-pale-cyan-blue-background-color{background-color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-pale-cyan-blue-border-color{border-color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-color{color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-cyan-blue-background-color{background-color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-cyan-blue-border-color{border-color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-color{color: var(--wp--preset--color--vivid-purple) !important;}.has-vivid-purple-background-color{background-color: var(--wp--preset--color--vivid-purple) !important;}.has-vivid-purple-border-color{border-color: var(--wp--preset--color--vivid-purple) !important;}.has-base-color{color: var(--wp--preset--color--base) !important;}.has-base-background-color{background-color: var(--wp--preset--color--base) !important;}.has-base-border-color{border-color: var(--wp--preset--color--base) !important;}.has-contrast-color{color: var(--wp--preset--color--contrast) !important;}.has-contrast-background-color{background-color: var(--wp--preset--color--contrast) !important;}.has-contrast-border-color{border-color: var(--wp--preset--color--contrast) !important;}.has-primary-color{color: var(--wp--preset--color--primary) !important;}.has-primary-background-color{background-color: var(--wp--preset--color--primary) !important;}.has-primary-border-color{border-color: var(--wp--preset--color--primary) !important;}.has-secondary-color{color: var(--wp--preset--color--secondary) !important;}.has-secondary-background-color{background-color: var(--wp--preset--color--secondary) !important;}.has-secondary-border-color{border-color: var(--wp--preset--color--secondary) !important;}.has-tertiary-color{color: var(--wp--preset--color--tertiary) !important;}.has-tertiary-background-color{background-color: var(--wp--preset--color--tertiary) !important;}.has-tertiary-border-color{border-color: var(--wp--preset--color--tertiary) !important;}.has-vivid-cyan-blue-to-vivid-purple-gradient-background{background: var(--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple) !important;}.has-light-green-cyan-to-vivid-green-cyan-gradient-background{background: var(--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan) !important;}.has-luminous-vivid-amber-to-luminous-vivid-orange-gradient-background{background: var(--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange) !important;}.has-luminous-vivid-orange-to-vivid-red-gradient-background{background: var(--wp--preset--gradient--luminous-vivid-orange-to-vivid-red) !important;}.has-very-light-gray-to-cyan-bluish-gray-gradient-background{background: var(--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray) !important;}.has-cool-to-warm-spectrum-gradient-background{background: var(--wp--preset--gradient--cool-to-warm-spectrum) !important;}.has-blush-light-purple-gradient-background{background: var(--wp--preset--gradient--blush-light-purple) !important;}.has-blush-bordeaux-gradient-background{background: var(--wp--preset--gradient--blush-bordeaux) !important;}.has-luminous-dusk-gradient-background{background: var(--wp--preset--gradient--luminous-dusk) !important;}.has-pale-ocean-gradient-background{background: var(--wp--preset--gradient--pale-ocean) !important;}.has-electric-grass-gradient-background{background: var(--wp--preset--gradient--electric-grass) !important;}.has-midnight-gradient-background{background: var(--wp--preset--gradient--midnight) !important;}.has-small-font-size{font-size: var(--wp--preset--font-size--small) !important;}.has-medium-font-size{font-size: var(--wp--preset--font-size--medium) !important;}.has-large-font-size{font-size: var(--wp--preset--font-size--large) !important;}.has-x-large-font-size{font-size: var(--wp--preset--font-size--x-large) !important;}.has-small-font-size{font-size: var(--wp--preset--font-size--small) !important;}.has-medium-font-size{font-size: var(--wp--preset--font-size--medium) !important;}.has-large-font-size{font-size: var(--wp--preset--font-size--large) !important;}.has-x-large-font-size{font-size: var(--wp--preset--font-size--x-large) !important;}.has-xx-large-font-size{font-size: var(--wp--preset--font-size--xx-large) !important;}.has-dm-sans-font-family{font-family: var(--wp--preset--font-family--dm-sans) !important;}.has-ibm-plex-mono-font-family{font-family: var(--wp--preset--font-family--ibm-plex-mono) !important;}.has-inter-font-family{font-family: var(--wp--preset--font-family--inter) !important;}.has-system-font-font-family{font-family: var(--wp--preset--font-family--system-font) !important;}.has-source-serif-pro-font-family{font-family: var(--wp--preset--font-family--source-serif-pro) !important;}',
			isGlobalStyles: true,
		},
		{
			css: '',
			isGlobalStyles: true,
		},
		{
			assets: [
				'<svg xmlns-xlink="http://www.w3.org/1999/xlink" viewBox="0 0 0 0" width="0" height="0" role="none" style="visibility:hidden;position:absolute;left:-9999px;overflow:hidden" aria-hidden="true"><defs><filter id="wp-duotone-dark-grayscale"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 "></feColorMatrix><feComponentTransfer color-interpolation-filters="sRGB"><feFuncR type="table" tableValues="0 0.4980392156862745"></feFuncR><feFuncG type="table" tableValues="0 0.4980392156862745"></feFuncG><feFuncB type="table" tableValues="0 0.4980392156862745"></feFuncB><feFuncA type="table" tableValues="1 1"></feFuncA></feComponentTransfer><feComposite in2="SourceGraphic" operator="in"></feComposite></filter></defs></svg><svg xmlns-xlink="http://www.w3.org/1999/xlink" viewBox="0 0 0 0" width="0" height="0" role="none" style="visibility:hidden;position:absolute;left:-9999px;overflow:hidden" aria-hidden="true"><defs><filter id="wp-duotone-grayscale"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 "></feColorMatrix><feComponentTransfer color-interpolation-filters="sRGB"><feFuncR type="table" tableValues="0 1"></feFuncR><feFuncG type="table" tableValues="0 1"></feFuncG><feFuncB type="table" tableValues="0 1"></feFuncB><feFuncA type="table" tableValues="1 1"></feFuncA></feComponentTransfer><feComposite in2="SourceGraphic" operator="in"></feComposite></filter></defs></svg><svg xmlns-xlink="http://www.w3.org/1999/xlink" viewBox="0 0 0 0" width="0" height="0" role="none" style="visibility:hidden;position:absolute;left:-9999px;overflow:hidden" aria-hidden="true"><defs><filter id="wp-duotone-purple-yellow"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 "></feColorMatrix><feComponentTransfer color-interpolation-filters="sRGB"><feFuncR type="table" tableValues="0.5490196078431373 0.9882352941176471"></feFuncR><feFuncG type="table" tableValues="0 1"></feFuncG><feFuncB type="table" tableValues="0.7176470588235294 0.2549019607843137"></feFuncB><feFuncA type="table" tableValues="1 1"></feFuncA></feComponentTransfer><feComposite in2="SourceGraphic" operator="in"></feComposite></filter></defs></svg><svg xmlns-xlink="http://www.w3.org/1999/xlink" viewBox="0 0 0 0" width="0" height="0" role="none" style="visibility:hidden;position:absolute;left:-9999px;overflow:hidden" aria-hidden="true"><defs><filter id="wp-duotone-blue-red"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 "></feColorMatrix><feComponentTransfer color-interpolation-filters="sRGB"><feFuncR type="table" tableValues="0 1"></feFuncR><feFuncG type="table" tableValues="0 0.2784313725490196"></feFuncG><feFuncB type="table" tableValues="0.592156862745098 0.2784313725490196"></feFuncB><feFuncA type="table" tableValues="1 1"></feFuncA></feComponentTransfer><feComposite in2="SourceGraphic" operator="in"></feComposite></filter></defs></svg><svg xmlns-xlink="http://www.w3.org/1999/xlink" viewBox="0 0 0 0" width="0" height="0" role="none" style="visibility:hidden;position:absolute;left:-9999px;overflow:hidden" aria-hidden="true"><defs><filter id="wp-duotone-midnight"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 "></feColorMatrix><feComponentTransfer color-interpolation-filters="sRGB"><feFuncR type="table" tableValues="0 0"></feFuncR><feFuncG type="table" tableValues="0 0.6470588235294118"></feFuncG><feFuncB type="table" tableValues="0 1"></feFuncB><feFuncA type="table" tableValues="1 1"></feFuncA></feComponentTransfer><feComposite in2="SourceGraphic" operator="in"></feComposite></filter></defs></svg><svg xmlns-xlink="http://www.w3.org/1999/xlink" viewBox="0 0 0 0" width="0" height="0" role="none" style="visibility:hidden;position:absolute;left:-9999px;overflow:hidden" aria-hidden="true"><defs><filter id="wp-duotone-magenta-yellow"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 "></feColorMatrix><feComponentTransfer color-interpolation-filters="sRGB"><feFuncR type="table" tableValues="0.7803921568627451 1"></feFuncR><feFuncG type="table" tableValues="0 0.9490196078431372"></feFuncG><feFuncB type="table" tableValues="0.35294117647058826 0.47058823529411764"></feFuncB><feFuncA type="table" tableValues="1 1"></feFuncA></feComponentTransfer><feComposite in2="SourceGraphic" operator="in"></feComposite></filter></defs></svg><svg xmlns-xlink="http://www.w3.org/1999/xlink" viewBox="0 0 0 0" width="0" height="0" role="none" style="visibility:hidden;position:absolute;left:-9999px;overflow:hidden" aria-hidden="true"><defs><filter id="wp-duotone-purple-green"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 "></feColorMatrix><feComponentTransfer color-interpolation-filters="sRGB"><feFuncR type="table" tableValues="0.6509803921568628 0.403921568627451"></feFuncR><feFuncG type="table" tableValues="0 1"></feFuncG><feFuncB type="table" tableValues="0.4470588235294118 0.4"></feFuncB><feFuncA type="table" tableValues="1 1"></feFuncA></feComponentTransfer><feComposite in2="SourceGraphic" operator="in"></feComposite></filter></defs></svg><svg xmlns-xlink="http://www.w3.org/1999/xlink" viewBox="0 0 0 0" width="0" height="0" role="none" style="visibility:hidden;position:absolute;left:-9999px;overflow:hidden" aria-hidden="true"><defs><filter id="wp-duotone-blue-orange"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 "></feColorMatrix><feComponentTransfer color-interpolation-filters="sRGB"><feFuncR type="table" tableValues="0.09803921568627451 1"></feFuncR><feFuncG type="table" tableValues="0 0.6627450980392157"></feFuncG><feFuncB type="table" tableValues="0.8470588235294118 0.4196078431372549"></feFuncB><feFuncA type="table" tableValues="1 1"></feFuncA></feComponentTransfer><feComposite in2="SourceGraphic" operator="in"></feComposite></filter></defs></svg>',
			],
			__unstableType: 'svg',
			isGlobalStyles: true,
		},
	],
	defaultTemplateTypes: [
		{
			title: 'Index',
			description:
				'Used as a fallback template for all pages when a more specific template is not defined.',
			slug: 'index',
		},
		{
			title: 'Home',
			description:
				'Displays the latest posts as either the site homepage or a custom page defined under reading settings. If it exists, the Front Page template overrides this template when posts are shown on the front page.',
			slug: 'home',
		},
		{
			title: 'Front Page',
			description:
				"Displays your site's front page, whether it is set to display latest posts or a static page. The Front Page template takes precedence over all templates.",
			slug: 'front-page',
		},
		{
			title: 'Singular',
			description:
				'Displays any single entry, such as a post or a page. This template will serve as a fallback when a more specific template (e.g., Single Post, Page, or Attachment) cannot be found.',
			slug: 'singular',
		},
		{
			title: 'Single',
			description:
				'Displays single posts on your website unless a custom template has been applied to that post or a dedicated template exists.',
			slug: 'single',
		},
		{
			title: 'Page',
			description:
				'Display all static pages unless a custom template has been applied or a dedicated template exists.',
			slug: 'page',
		},
		{
			title: 'Archive',
			description:
				'Displays any archive, including posts by a single author, category, tag, taxonomy, custom post type, and date. This template will serve as a fallback when more specific templates (e.g., Category or Tag) cannot be found.',
			slug: 'archive',
		},
		{
			title: 'Author',
			description:
				"Displays a single author's post archive. This template will serve as a fallback when a more a specific template (e.g., Author: Admin) cannot be found.",
			slug: 'author',
		},
		{
			title: 'Category',
			description:
				'Displays a post category archive. This template will serve as a fallback when more specific template (e.g., Category: Recipes) cannot be found.',
			slug: 'category',
		},
		{
			title: 'Taxonomy',
			description:
				'Displays a custom taxonomy archive. Like categories and tags, taxonomies have terms which you use to classify things. For example: a taxonomy named "Art" can have multiple terms, such as "Modern" and "18th Century." This template will serve as a fallback when a more specific template (e.g, Taxonomy: Art) cannot be found.',
			slug: 'taxonomy',
		},
		{
			title: 'Date',
			description:
				'Displays a post archive when a specific date is visited (e.g., example.com/2023/).',
			slug: 'date',
		},
		{
			title: 'Tag',
			description:
				'Displays a post tag archive. This template will serve as a fallback when more specific template (e.g., Tag: Pizza) cannot be found.',
			slug: 'tag',
		},
		{
			title: 'Media',
			description:
				'Displays when a visitor views the dedicated page that exists for any media attachment.',
			slug: 'attachment',
		},
		{
			title: 'Search',
			description:
				'Displays when a visitor performs a search on your website.',
			slug: 'search',
		},
		{
			title: 'Privacy Policy',
			description: "Displays your site's Privacy Policy page.",
			slug: 'privacy-policy',
		},
		{
			title: '404',
			description:
				'Displays when a visitor views a non-existent page, such as a dead link or a mistyped URL.',
			slug: '404',
		},
	],
	defaultTemplatePartAreas: [
		{
			area: 'uncategorized',
			label: 'General',
			description:
				'General templates often perform a specific role like displaying post content, and are not tied to any particular area.',
			icon: 'layout',
			area_tag: 'div',
		},
		{
			area: 'header',
			label: 'Header',
			description:
				'The Header template defines a page area that typically contains a title, logo, and main navigation.',
			icon: 'header',
			area_tag: 'header',
		},
		{
			area: 'footer',
			label: 'Footer',
			description:
				'The Footer template defines a page area that typically contains site credits, social links, or any other combination of blocks.',
			icon: 'footer',
			area_tag: 'footer',
		},
	],
	supportsLayout: true,
	supportsTemplatePartsMode: false,
	__experimentalAdditionalBlockPatterns: [
		{
			title: 'Empty Mini Cart Message',
			inserter: false,
			content:
				'<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>Your cart is currently empty!</strong></p><!-- /wp:paragraph -->',
			name: 'woocommerce/mini-cart-empty-cart-message',
		},
	],
	__experimentalAdditionalBlockPatternCategories: [],
	__experimentalFeatures: {
		appearanceTools: false,
		useRootPaddingAwareAlignments: true,
		border: {
			color: true,
			radius: true,
			style: true,
			width: true,
		},
		color: {
			background: true,
			button: true,
			caption: true,
			custom: true,
			customDuotone: true,
			customGradient: true,
			defaultDuotone: true,
			defaultGradients: true,
			defaultPalette: true,
			duotone: {
				default: [
					{
						name: 'Dark grayscale',
						colors: [ '#000000', '#7f7f7f' ],
						slug: 'dark-grayscale',
					},
					{
						name: 'Grayscale',
						colors: [ '#000000', '#ffffff' ],
						slug: 'grayscale',
					},
					{
						name: 'Purple and yellow',
						colors: [ '#8c00b7', '#fcff41' ],
						slug: 'purple-yellow',
					},
					{
						name: 'Blue and red',
						colors: [ '#000097', '#ff4747' ],
						slug: 'blue-red',
					},
					{
						name: 'Midnight',
						colors: [ '#000000', '#00a5ff' ],
						slug: 'midnight',
					},
					{
						name: 'Magenta and yellow',
						colors: [ '#c7005a', '#fff278' ],
						slug: 'magenta-yellow',
					},
					{
						name: 'Purple and green',
						colors: [ '#a60072', '#67ff66' ],
						slug: 'purple-green',
					},
					{
						name: 'Blue and orange',
						colors: [ '#1900d8', '#ffa96b' ],
						slug: 'blue-orange',
					},
				],
			},
			gradients: {
				default: [
					{
						name: 'Vivid cyan blue to vivid purple',
						gradient:
							'linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%)',
						slug: 'vivid-cyan-blue-to-vivid-purple',
					},
					{
						name: 'Light green cyan to vivid green cyan',
						gradient:
							'linear-gradient(135deg,rgb(122,220,180) 0%,rgb(0,208,130) 100%)',
						slug: 'light-green-cyan-to-vivid-green-cyan',
					},
					{
						name: 'Luminous vivid amber to luminous vivid orange',
						gradient:
							'linear-gradient(135deg,rgba(252,185,0,1) 0%,rgba(255,105,0,1) 100%)',
						slug: 'luminous-vivid-amber-to-luminous-vivid-orange',
					},
					{
						name: 'Luminous vivid orange to vivid red',
						gradient:
							'linear-gradient(135deg,rgba(255,105,0,1) 0%,rgb(207,46,46) 100%)',
						slug: 'luminous-vivid-orange-to-vivid-red',
					},
					{
						name: 'Very light gray to cyan bluish gray',
						gradient:
							'linear-gradient(135deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%)',
						slug: 'very-light-gray-to-cyan-bluish-gray',
					},
					{
						name: 'Cool to warm spectrum',
						gradient:
							'linear-gradient(135deg,rgb(74,234,220) 0%,rgb(151,120,209) 20%,rgb(207,42,186) 40%,rgb(238,44,130) 60%,rgb(251,105,98) 80%,rgb(254,248,76) 100%)',
						slug: 'cool-to-warm-spectrum',
					},
					{
						name: 'Blush light purple',
						gradient:
							'linear-gradient(135deg,rgb(255,206,236) 0%,rgb(152,150,240) 100%)',
						slug: 'blush-light-purple',
					},
					{
						name: 'Blush bordeaux',
						gradient:
							'linear-gradient(135deg,rgb(254,205,165) 0%,rgb(254,45,45) 50%,rgb(107,0,62) 100%)',
						slug: 'blush-bordeaux',
					},
					{
						name: 'Luminous dusk',
						gradient:
							'linear-gradient(135deg,rgb(255,203,112) 0%,rgb(199,81,192) 50%,rgb(65,88,208) 100%)',
						slug: 'luminous-dusk',
					},
					{
						name: 'Pale ocean',
						gradient:
							'linear-gradient(135deg,rgb(255,245,203) 0%,rgb(182,227,212) 50%,rgb(51,167,181) 100%)',
						slug: 'pale-ocean',
					},
					{
						name: 'Electric grass',
						gradient:
							'linear-gradient(135deg,rgb(202,248,128) 0%,rgb(113,206,126) 100%)',
						slug: 'electric-grass',
					},
					{
						name: 'Midnight',
						gradient:
							'linear-gradient(135deg,rgb(2,3,129) 0%,rgb(40,116,252) 100%)',
						slug: 'midnight',
					},
				],
			},
			heading: true,
			link: true,
			palette: {
				default: [
					{
						name: 'Black',
						slug: 'black',
						color: '#000000',
					},
					{
						name: 'Cyan bluish gray',
						slug: 'cyan-bluish-gray',
						color: '#abb8c3',
					},
					{
						name: 'White',
						slug: 'white',
						color: '#ffffff',
					},
					{
						name: 'Pale pink',
						slug: 'pale-pink',
						color: '#f78da7',
					},
					{
						name: 'Vivid red',
						slug: 'vivid-red',
						color: '#cf2e2e',
					},
					{
						name: 'Luminous vivid orange',
						slug: 'luminous-vivid-orange',
						color: '#ff6900',
					},
					{
						name: 'Luminous vivid amber',
						slug: 'luminous-vivid-amber',
						color: '#fcb900',
					},
					{
						name: 'Light green cyan',
						slug: 'light-green-cyan',
						color: '#7bdcb5',
					},
					{
						name: 'Vivid green cyan',
						slug: 'vivid-green-cyan',
						color: '#00d084',
					},
					{
						name: 'Pale cyan blue',
						slug: 'pale-cyan-blue',
						color: '#8ed1fc',
					},
					{
						name: 'Vivid cyan blue',
						slug: 'vivid-cyan-blue',
						color: '#0693e3',
					},
					{
						name: 'Vivid purple',
						slug: 'vivid-purple',
						color: '#9b51e0',
					},
				],
				theme: [
					{
						color: '#ffffff',
						name: 'Base',
						slug: 'base',
					},
					{
						color: '#000000',
						name: 'Contrast',
						slug: 'contrast',
					},
					{
						color: '#9DFF20',
						name: 'Primary',
						slug: 'primary',
					},
					{
						color: '#345C00',
						name: 'Secondary',
						slug: 'secondary',
					},
					{
						color: '#F6F6F6',
						name: 'Tertiary',
						slug: 'tertiary',
					},
				],
			},
			text: true,
		},
		shadow: {
			defaultPresets: true,
			presets: {
				default: [
					{
						name: 'Natural',
						slug: 'natural',
						shadow: '6px 6px 9px rgba(0, 0, 0, 0.2)',
					},
					{
						name: 'Deep',
						slug: 'deep',
						shadow: '12px 12px 50px rgba(0, 0, 0, 0.4)',
					},
					{
						name: 'Sharp',
						slug: 'sharp',
						shadow: '6px 6px 0px rgba(0, 0, 0, 0.2)',
					},
					{
						name: 'Outlined',
						slug: 'outlined',
						shadow: '6px 6px 0px -3px rgba(255, 255, 255, 1), 6px 6px rgba(0, 0, 0, 1)',
					},
					{
						name: 'Crisp',
						slug: 'crisp',
						shadow: '6px 6px 0px rgba(0, 0, 0, 1)',
					},
				],
			},
		},
		layout: {
			definitions: {
				default: {
					name: 'default',
					slug: 'flow',
					className: 'is-layout-flow',
					baseStyles: [
						{
							selector: ' > .alignleft',
							rules: {
								float: 'left',
								'margin-inline-start': '0',
								'margin-inline-end': '2em',
							},
						},
						{
							selector: ' > .alignright',
							rules: {
								float: 'right',
								'margin-inline-start': '2em',
								'margin-inline-end': '0',
							},
						},
						{
							selector: ' > .aligncenter',
							rules: {
								'margin-left': 'auto !important',
								'margin-right': 'auto !important',
							},
						},
					],
					spacingStyles: [
						{
							selector: ' > :first-child:first-child',
							rules: {
								'margin-block-start': '0',
							},
						},
						{
							selector: ' > :last-child:last-child',
							rules: {
								'margin-block-end': '0',
							},
						},
						{
							selector: ' > *',
							rules: {
								'margin-block-start': null,
								'margin-block-end': '0',
							},
						},
					],
				},
				constrained: {
					name: 'constrained',
					slug: 'constrained',
					className: 'is-layout-constrained',
					baseStyles: [
						{
							selector: ' > .alignleft',
							rules: {
								float: 'left',
								'margin-inline-start': '0',
								'margin-inline-end': '2em',
							},
						},
						{
							selector: ' > .alignright',
							rules: {
								float: 'right',
								'margin-inline-start': '2em',
								'margin-inline-end': '0',
							},
						},
						{
							selector: ' > .aligncenter',
							rules: {
								'margin-left': 'auto !important',
								'margin-right': 'auto !important',
							},
						},
						{
							selector:
								' > :where(:not(.alignleft):not(.alignright):not(.alignfull))',
							rules: {
								'max-width':
									'var(--wp--style--global--content-size)',
								'margin-left': 'auto !important',
								'margin-right': 'auto !important',
							},
						},
						{
							selector: ' > .alignwide',
							rules: {
								'max-width':
									'var(--wp--style--global--wide-size)',
							},
						},
					],
					spacingStyles: [
						{
							selector: ' > :first-child:first-child',
							rules: {
								'margin-block-start': '0',
							},
						},
						{
							selector: ' > :last-child:last-child',
							rules: {
								'margin-block-end': '0',
							},
						},
						{
							selector: ' > *',
							rules: {
								'margin-block-start': null,
								'margin-block-end': '0',
							},
						},
					],
				},
				flex: {
					name: 'flex',
					slug: 'flex',
					className: 'is-layout-flex',
					displayMode: 'flex',
					baseStyles: [
						{
							selector: '',
							rules: {
								'flex-wrap': 'wrap',
								'align-items': 'center',
							},
						},
						{
							selector: ' > *',
							rules: {
								margin: '0',
							},
						},
					],
					spacingStyles: [
						{
							selector: '',
							rules: {
								gap: null,
							},
						},
					],
				},
				grid: {
					name: 'grid',
					slug: 'grid',
					className: 'is-layout-grid',
					displayMode: 'grid',
					baseStyles: [
						{
							selector: ' > *',
							rules: {
								margin: '0',
							},
						},
					],
					spacingStyles: [
						{
							selector: '',
							rules: {
								gap: null,
							},
						},
					],
				},
			},
			contentSize: '650px',
			wideSize: '1200px',
		},
		spacing: {
			blockGap: true,
			margin: true,
			padding: true,
			customSpacingSize: true,
			units: [ '%', 'px', 'em', 'rem', 'vh', 'vw' ],
			spacingScale: {
				operator: '*',
				increment: 1.5,
				steps: 0,
				mediumStep: 1.5,
				unit: 'rem',
			},
			spacingSizes: {
				theme: [
					{
						size: 'clamp(1.5rem, 5vw, 2rem)',
						slug: '30',
						name: '1',
					},
					{
						size: 'clamp(1.8rem, 1.8rem + ((1vw - 0.48rem) * 2.885), 3rem)',
						slug: '40',
						name: '2',
					},
					{
						size: 'clamp(2.5rem, 8vw, 4.5rem)',
						slug: '50',
						name: '3',
					},
					{
						size: 'clamp(3.75rem, 10vw, 7rem)',
						slug: '60',
						name: '4',
					},
					{
						size: 'clamp(5rem, 5.25rem + ((1vw - 0.48rem) * 9.096), 8rem)',
						slug: '70',
						name: '5',
					},
					{
						size: 'clamp(7rem, 14vw, 11rem)',
						slug: '80',
						name: '6',
					},
				],
			},
		},
		typography: {
			customFontSize: true,
			dropCap: false,
			fontSizes: {
				default: [
					{
						name: 'Small',
						slug: 'small',
						size: '13px',
					},
					{
						name: 'Medium',
						slug: 'medium',
						size: '20px',
					},
					{
						name: 'Large',
						slug: 'large',
						size: '36px',
					},
					{
						name: 'Extra Large',
						slug: 'x-large',
						size: '42px',
					},
				],
				theme: [
					{
						fluid: {
							min: '0.875rem',
							max: '1rem',
						},
						size: '1rem',
						slug: 'small',
						name: 'Small',
					},
					{
						fluid: {
							min: '1rem',
							max: '1.125rem',
						},
						size: '1.125rem',
						slug: 'medium',
						name: 'Medium',
					},
					{
						fluid: {
							min: '1.75rem',
							max: '1.875rem',
						},
						size: '1.75rem',
						slug: 'large',
						name: 'Large',
					},
					{
						fluid: false,
						size: '2.25rem',
						slug: 'x-large',
						name: 'Extra Large',
					},
					{
						fluid: {
							min: '4rem',
							max: '10rem',
						},
						size: '10rem',
						slug: 'xx-large',
					},
				],
			},
			fontStyle: true,
			fontWeight: true,
			letterSpacing: true,
			lineHeight: true,
			textColumns: false,
			textDecoration: true,
			textTransform: true,
			fluid: true,
			fontFamilies: {
				theme: [
					{
						fontFace: [
							{
								fontFamily: 'DM Sans',
								fontStretch: 'normal',
								fontStyle: 'normal',
								fontWeight: '400',
								src: [
									'file:./assets/fonts/dm-sans/DMSans-Regular.woff2',
								],
							},
							{
								fontFamily: 'DM Sans',
								fontStretch: 'normal',
								fontStyle: 'italic',
								fontWeight: '400',
								src: [
									'file:./assets/fonts/dm-sans/DMSans-Regular-Italic.woff2',
								],
							},
							{
								fontFamily: 'DM Sans',
								fontStretch: 'normal',
								fontStyle: 'normal',
								fontWeight: '700',
								src: [
									'file:./assets/fonts/dm-sans/DMSans-Bold.woff2',
								],
							},
							{
								fontFamily: 'DM Sans',
								fontStretch: 'normal',
								fontStyle: 'italic',
								fontWeight: '700',
								src: [
									'file:./assets/fonts/dm-sans/DMSans-Bold-Italic.woff2',
								],
							},
						],
						fontFamily: '"DM Sans", sans-serif',
						name: 'DM Sans',
						slug: 'dm-sans',
					},
					{
						fontFace: [
							{
								fontDisplay: 'block',
								fontFamily: 'IBM Plex Mono',
								fontStretch: 'normal',
								fontStyle: 'normal',
								fontWeight: '300',
								src: [
									'file:./assets/fonts/ibm-plex-mono/IBMPlexMono-Light.woff2',
								],
							},
							{
								fontDisplay: 'block',
								fontFamily: 'IBM Plex Mono',
								fontStretch: 'normal',
								fontStyle: 'normal',
								fontWeight: '400',
								src: [
									'file:./assets/fonts/ibm-plex-mono/IBMPlexMono-Regular.woff2',
								],
							},
							{
								fontDisplay: 'block',
								fontFamily: 'IBM Plex Mono',
								fontStretch: 'normal',
								fontStyle: 'italic',
								fontWeight: '400',
								src: [
									'file:./assets/fonts/ibm-plex-mono/IBMPlexMono-Italic.woff2',
								],
							},
							{
								fontDisplay: 'block',
								fontFamily: 'IBM Plex Mono',
								fontStretch: 'normal',
								fontStyle: 'normal',
								fontWeight: '700',
								src: [
									'file:./assets/fonts/ibm-plex-mono/IBMPlexMono-Bold.woff2',
								],
							},
						],
						fontFamily: "'IBM Plex Mono', monospace",
						name: 'IBM Plex Mono',
						slug: 'ibm-plex-mono',
					},
					{
						fontFace: [
							{
								fontFamily: 'Inter',
								fontStretch: 'normal',
								fontStyle: 'normal',
								fontWeight: '200 900',
								src: [
									'file:./assets/fonts/inter/Inter-VariableFont_slnt,wght.ttf',
								],
							},
						],
						fontFamily: '"Inter", sans-serif',
						name: 'Inter',
						slug: 'inter',
					},
					{
						fontFamily:
							'-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif',
						name: 'System Font',
						slug: 'system-font',
					},
					{
						fontFace: [
							{
								fontFamily: 'Source Serif Pro',
								fontStretch: 'normal',
								fontStyle: 'normal',
								fontWeight: '200 900',
								src: [
									'file:./assets/fonts/source-serif-pro/SourceSerif4Variable-Roman.ttf.woff2',
								],
							},
							{
								fontFamily: 'Source Serif Pro',
								fontStretch: 'normal',
								fontStyle: 'italic',
								fontWeight: '200 900',
								src: [
									'file:./assets/fonts/source-serif-pro/SourceSerif4Variable-Italic.ttf.woff2',
								],
							},
						],
						fontFamily: '"Source Serif Pro", serif',
						name: 'Source Serif Pro',
						slug: 'source-serif-pro',
					},
				],
			},
		},
		blocks: {
			'core/button': {
				border: {
					radius: true,
				},
			},
			'core/pullquote': {
				border: {
					color: true,
					radius: true,
					style: true,
					width: true,
				},
			},
			'core/image': {
				behaviors: {
					lightbox: true,
				},
			},
		},
		dimensions: {
			minHeight: true,
		},
		position: {
			fixed: true,
			sticky: true,
		},
	},
	colors: [
		{
			color: '#222828',
			name: 'Base',
			slug: 'base',
		},
		{
			color: '#ffffff',
			name: 'Contrast',
			slug: 'contrast',
		},
		{
			color: '#53ED85',
			name: 'Primary',
			slug: 'primary',
		},
		{
			color: '#9EF9FD',
			name: 'Secondary',
			slug: 'secondary',
		},
		{
			color: '#D8E202',
			name: 'Tertiary',
			slug: 'tertiary',
		},
	],
	gradients: [
		{
			gradient:
				'linear-gradient(180deg, var(--wp--preset--color--primary) 0%,var(--wp--preset--color--secondary) 100%)',
			name: 'Primary to Secondary',
			slug: 'primary-secondary',
		},
		{
			gradient:
				'linear-gradient(180deg, var(--wp--preset--color--secondary) 0%,var(--wp--preset--color--primary) 100%)',
			name: 'Secondary to Primary',
			slug: 'secondary-primary',
		},
		{
			gradient:
				'linear-gradient(180deg, var(--wp--preset--color--primary) 0%,var(--wp--preset--color--tertiary) 100%)',
			name: 'Tertiary to Secondary',
			slug: 'tertiary-secondary',
		},
		{
			gradient:
				'linear-gradient(180deg, var(--wp--preset--color--tertiary) 0%,var(--wp--preset--color--primary) 100%)',
			name: 'Tertiary to Primary',
			slug: 'tertiary-primary',
		},
		{
			gradient:
				'linear-gradient(180deg, var(--wp--preset--color--base) 0%,var(--wp--preset--color--primary) 350%)',
			name: 'Base to Primary',
			slug: 'base-primary',
		},
		{
			gradient:
				'radial-gradient(circle at 5px 5px,#0c0d0d70 2px,#ffffff00 0px,#ffffff00 0px) 0 0 / 8px 8px, linear-gradient(180deg, var(--wp--preset--color--base) 0%,#000000 200%)',
			name: 'Dots',
			slug: 'dots',
		},
	],
	fontSizes: [
		{
			fluid: {
				min: '0.875rem',
				max: '1rem',
			},
			size: '1rem',
			slug: 'small',
			name: 'Small',
		},
		{
			fluid: {
				min: '1rem',
				max: '1.125rem',
			},
			size: '1.125rem',
			slug: 'medium',
			name: 'Medium',
		},
		{
			fluid: {
				min: '1.75rem',
				max: '1.875rem',
			},
			size: '1.75rem',
			slug: 'large',
			name: 'Large',
		},
		{
			fluid: false,
			size: '2.25rem',
			slug: 'x-large',
			name: 'Extra Large',
		},
		{
			fluid: {
				min: '4rem',
				max: '10rem',
			},
			size: '10rem',
			slug: 'xx-large',
		},
	],
	disableCustomSpacingSizes: false,
	spacingSizes: [
		{
			size: 'clamp(1.5rem, 5vw, 2rem)',
			slug: '30',
			name: '1',
		},
		{
			size: 'clamp(1.8rem, 1.8rem + ((1vw - 0.48rem) * 2.885), 3rem)',
			slug: '40',
			name: '2',
		},
		{
			size: 'clamp(2.5rem, 8vw, 4.5rem)',
			slug: '50',
			name: '3',
		},
		{
			size: 'clamp(3.75rem, 10vw, 7rem)',
			slug: '60',
			name: '4',
		},
		{
			size: 'clamp(5rem, 5.25rem + ((1vw - 0.48rem) * 9.096), 8rem)',
			slug: '70',
			name: '5',
		},
		{
			size: 'clamp(7rem, 14vw, 11rem)',
			slug: '80',
			name: '6',
		},
	],
	__unstableResolvedAssets: {
		styles: "<style>\nimg.wp-smiley,\nimg.emoji {\n\tdisplay: inline !important;\n\tborder: none !important;\n\tbox-shadow: none !important;\n\theight: 1em !important;\n\twidth: 1em !important;\n\tmargin: 0 0.07em !important;\n\tvertical-align: -0.1em !important;\n\tbackground: none !important;\n\tpadding: 0 !important;\n}\n</style>\n\t<link rel='stylesheet' id='dashicons-css' href='http://localhost:8888/wp-includes/css/dashicons.css?ver=6.2.2' media='all' />\n<link rel='stylesheet' id='wp-components-css' href='http://localhost:8888/wp-content/plugins/gutenberg/build/components/style.css?ver=1686827301' media='all' />\n<link rel='stylesheet' id='wp-block-editor-content-css' href='http://localhost:8888/wp-content/plugins/gutenberg/build/block-editor/content.css?ver=1686827301' media='all' />\n<link rel='stylesheet' id='wp-block-library-css' href='http://localhost:8888/wp-content/plugins/gutenberg/build/block-library/style.css?ver=1686827301' media='all' />\n<link rel='stylesheet' id='wc-blocks-vendors-style-css' href='http://localhost:8888/wp-content/plugins/woocommerce/packages/woocommerce-blocks/build/wc-blocks-vendors-style.css?ver=1686319989' media='all' />\n<link rel='stylesheet' id='wc-blocks-style-css' href='http://localhost:8888/wp-content/plugins/woocommerce/packages/woocommerce-blocks/build/wc-blocks-style.css?ver=1686319989' media='all' />\n<style id='wp-fonts-local'>\n@font-face{font-family:\"DM Sans\";font-style:normal;font-weight:400;font-display:fallback;src:url('http://localhost:8888/wp-content/themes/twentytwentythree/assets/fonts/dm-sans/DMSans-Regular.woff2') format('woff2');font-stretch:normal;}@font-face{font-family:\"DM Sans\";font-style:italic;font-weight:400;font-display:fallback;src:url('http://localhost:8888/wp-content/themes/twentytwentythree/assets/fonts/dm-sans/DMSans-Regular-Italic.woff2') format('woff2');font-stretch:normal;}@font-face{font-family:\"DM Sans\";font-style:normal;font-weight:700;font-display:fallback;src:url('http://localhost:8888/wp-content/themes/twentytwentythree/assets/fonts/dm-sans/DMSans-Bold.woff2') format('woff2');font-stretch:normal;}@font-face{font-family:\"DM Sans\";font-style:italic;font-weight:700;font-display:fallback;src:url('http://localhost:8888/wp-content/themes/twentytwentythree/assets/fonts/dm-sans/DMSans-Bold-Italic.woff2') format('woff2');font-stretch:normal;}@font-face{font-family:\"IBM Plex Mono\";font-style:normal;font-weight:300;font-display:block;src:url('http://localhost:8888/wp-content/themes/twentytwentythree/assets/fonts/ibm-plex-mono/IBMPlexMono-Light.woff2') format('woff2');font-stretch:normal;}@font-face{font-family:\"IBM Plex Mono\";font-style:normal;font-weight:400;font-display:block;src:url('http://localhost:8888/wp-content/themes/twentytwentythree/assets/fonts/ibm-plex-mono/IBMPlexMono-Regular.woff2') format('woff2');font-stretch:normal;}@font-face{font-family:\"IBM Plex Mono\";font-style:italic;font-weight:400;font-display:block;src:url('http://localhost:8888/wp-content/themes/twentytwentythree/assets/fonts/ibm-plex-mono/IBMPlexMono-Italic.woff2') format('woff2');font-stretch:normal;}@font-face{font-family:\"IBM Plex Mono\";font-style:normal;font-weight:700;font-display:block;src:url('http://localhost:8888/wp-content/themes/twentytwentythree/assets/fonts/ibm-plex-mono/IBMPlexMono-Bold.woff2') format('woff2');font-stretch:normal;}@font-face{font-family:Inter;font-style:normal;font-weight:200 900;font-display:fallback;src:url('http://localhost:8888/wp-content/themes/twentytwentythree/assets/fonts/inter/Inter-VariableFont_slnt,wght.ttf') format('truetype');font-stretch:normal;}@font-face{font-family:\"Source Serif Pro\";font-style:normal;font-weight:200 900;font-display:fallback;src:url('http://localhost:8888/wp-content/themes/twentytwentythree/assets/fonts/source-serif-pro/SourceSerif4Variable-Roman.ttf.woff2') format('woff2');font-stretch:normal;}@font-face{font-family:\"Source Serif Pro\";font-style:italic;font-weight:200 900;font-display:fallback;src:url('http://localhost:8888/wp-content/themes/twentytwentythree/assets/fonts/source-serif-pro/SourceSerif4Variable-Italic.ttf.woff2') format('woff2');font-stretch:normal;}\n</style>\n",
		scripts:
			"<script src='http://localhost:8888/wp-content/plugins/gutenberg/build/vendors/inert-polyfill.js?ver=6.2.2' id='wp-inert-polyfill-js'></script>\n<script src='http://localhost:8888/wp-includes/js/dist/vendor/wp-polyfill-inert.js?ver=3.1.2' id='wp-polyfill-inert-js'></script>\n<script src='http://localhost:8888/wp-includes/js/dist/vendor/regenerator-runtime.js?ver=0.13.11' id='regenerator-runtime-js'></script>\n<script src='http://localhost:8888/wp-includes/js/dist/vendor/wp-polyfill.js?ver=3.15.0' id='wp-polyfill-js'></script>\n",
	},
	__unstableIsBlockBasedTheme: true,
	localAutosaveInterval: 15,
	__experimentalDiscussionSettings: {
		commentOrder: 'asc',
		commentsPerPage: '50',
		defaultCommentsPage: 'newest',
		pageComments: '0',
		threadComments: '1',
		threadCommentsDepth: '5',
		defaultCommentStatus: 'open',
		avatarURL: 'http://0.gravatar.com/avatar/?s=96&d=mm&f=y&r=g',
	},
	behaviors: {
		blocks: {
			'core/image': {
				lightbox: false,
			},
		},
	},
	outlineMode: true,
	focusMode: false,
	hasFixedToolbar: false,
	keepCaretInsideBlock: false,
	showIconLabels: false,
	__experimentalReusableBlocks: [],
	__experimentalPreferPatternsOnRoot: true,
};
