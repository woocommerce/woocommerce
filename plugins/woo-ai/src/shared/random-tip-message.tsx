/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const tipsAndTricksPhrases = [
	__(
		'Make your product title descriptive for better results.',
		'woocommerce'
	),
	__( 'Tailor your product names to your target audience.', 'woocommerce' ),
	__(
		"Focus on your product's unique features and benefits in descriptions.",
		'woocommerce'
	),
	__(
		'Add relevant categories and tags to make products easy to find.',
		'woocommerce'
	),
	__(
		'Including precise product attributes helps us provide better suggestions.',
		'woocommerce'
	),
	__(
		'Know your audience and speak their language in descriptions.',
		'woocommerce'
	),
	__(
		'️Organize your products with clear categories and tags for a user-friendly experience.',
		'woocommerce'
	),
	__(
		'Get creative with product titles, but stay on topic for the best suggestions.',
		'woocommerce'
	),
	__(
		'Enhance your suggestions further by adding important features to your product titles.',
		'woocommerce'
	),
	__(
		'Balance accurate information & creativity for optimal titles…',
		'woocommerce'
	),
	__(
		'Keep refining your product information for better suggestions…',
		'woocommerce'
	),
	__(
		'Remember to showcase the benefits of your products in descriptions…',
		'woocommerce'
	),
	__(
		'Consider your target audience while crafting product names…',
		'woocommerce'
	),
	__(
		'Add specific tags and attributes for better suggestions…',
		'woocommerce'
	),
	__(
		'Use keywords in titles and descriptions that customers search for…',
		'woocommerce'
	),
	__(
		'Highlight unique features of your product for better suggestions…',
		'woocommerce'
	),
	__(
		'Optimize descriptions and titles for mobile devices too…',
		'woocommerce'
	),
	__(
		'Add relevant categories and tags for improved search…',
		'woocommerce'
	),
	__(
		'Create catchy titles, but keep the focus on your product…',
		'woocommerce'
	),
];

const getRandomTip = (): string => {
	return tipsAndTricksPhrases[
		Math.floor( Math.random() * tipsAndTricksPhrases.length )
	];
};

const RandomTipMessage = () => {
	return <span>💡 Tip: { getRandomTip() }</span>;
};

export default RandomTipMessage;
