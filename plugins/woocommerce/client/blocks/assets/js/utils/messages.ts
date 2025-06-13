/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Messages providing guidance for HTML element usage in block editor controls.
 * These messages help developers and users choose the appropriate semantic HTML elements
 * for their blocks.
 *
 * @example
 * ```tsx
 * <SelectControl
 *   value={ TagName }
 *   onChange={ (value) => setAttributes({ tagName: value }) }
 *   help={ htmlElementMessages[TagName] }
 * />
 * ```
 */
export const htmlElementMessages = {
	article: __(
		'The <article> element should represent a self-contained, syndicatable portion of the document.',
		'woocommerce'
	),
	aside: __(
		"The <aside> element should represent a portion of a document whose content is only indirectly related to the document's main content.",
		'woocommerce'
	),
	div: __(
		'The <div> element should only be used if the block is a design element with no semantic meaning.',
		'woocommerce'
	),
	footer: __(
		'The <footer> element should represent a footer for its nearest sectioning element (e.g.: <section>, <article>, <main> etc.).',
		'woocommerce'
	),
	header: __(
		'The <header> element should represent introductory content, typically a group of introductory or navigational aids.',
		'woocommerce'
	),
	main: __(
		'The <main> element should be used for the primary content of your document only.',
		'woocommerce'
	),
	nav: __(
		'The <nav> element should be used to identify groups of links that are intended to be used for website or page content navigation.',
		'woocommerce'
	),
	section: __(
		"The <section> element should represent a standalone portion of the document that can't be better represented by another element.",
		'woocommerce'
	),
};
