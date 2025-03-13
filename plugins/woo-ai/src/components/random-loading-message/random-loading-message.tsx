/**
 * External dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { shuffleArray } from '../../utils';

// Define the Property types for the RandomLoadingMessage component
type RandomLoadingMessageProps = {
	isLoading: boolean;
};

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
		'Create catchy titles, but keep the focus on your product…',
		'woocommerce'
	),
];

const getRandomLoadingPhrase = ( phrasesStack: string[] ): string => {
	// Pop the first message from the stack and push it back in
	const poppedMessage = phrasesStack.shift();

	if ( ! poppedMessage ) {
		return '';
	}

	phrasesStack.push( poppedMessage );

	return poppedMessage;
};

export const RandomLoadingMessage: React.FC< RandomLoadingMessageProps > = ( {
	isLoading,
} ) => {
	const messageUpdateTimeout = useRef< number >();
	const [ currentMessage, setCurrentMessage ] = useState(
		__( 'Brainstorming ideas… hold on tight.', 'woocommerce' )
	);

	useEffect( () => {
		const phrasesStack = shuffleArray( tipsAndTricksPhrases );

		// Recursive function to update the message on an increasing time interval
		const updateMessage = ( delay: number ) => {
			clearTimeout( messageUpdateTimeout.current );
			messageUpdateTimeout.current = window.setTimeout( () => {
				setCurrentMessage( getRandomLoadingPhrase( phrasesStack ) );

				// Updates the message after an increasing delay. It will update every 3s, 4.5s, 6.75s, 10.125s, etc.
				updateMessage( delay * 1.5 );
			}, delay );
		};

		if ( isLoading ) {
			updateMessage( 3000 );
		} else {
			clearTimeout( messageUpdateTimeout.current );
		}

		return () => {
			clearTimeout( messageUpdateTimeout.current );
		};
	}, [ isLoading ] );

	return (
		<>
			<span className="woo-ai-loading-message_spinner">
				<Spinner />
			</span>
			<span className="woo-ai-loading-message_content">
				{ currentMessage }
			</span>
		</>
	);
};
