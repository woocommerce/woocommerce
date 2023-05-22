/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

// Define the Property types for the RandomLoadingMessage component
interface ErrorMessageProps {
	error?: string;
}

// The list of possible error messages
const errorMessages = [
	__(
		'🧙‍♂️ Eh, magic wand malfunction! Please retry your enchanting request.',
		'woocommerce'
	),
	__( "🤖 Robo-hiccup! Let's retry.", 'woocommerce' ),
	__(
		'🤦‍♂️ Oopsie Daisy! Seems our AI genius took a lunch break. Kindly retry.',
		'woocommerce'
	),
	__( '🙈 Oops! Our crystal ball cracked. Please try again.', 'woocommerce' ),
	__(
		"⏰ Oops! Time-travel hiccup! Let's go back and try once more.",
		'woocommerce'
	),
	__( '🪄 Bummer! A misfired spell. Please try again.', 'woocommerce' ),
	__( '🥺 Oops! My AI brain had a hiccup. Please try again.', 'woocommerce' ),
	__(
		"🤖 Oops! My circuits got a little tangled, let's try that again.",
		'woocommerce'
	),
	__( '🚧 Oops! A bump in the road. Try again!', 'woocommerce' ),
	__( '💥 Oops! Something went awry. Give it another shot!', 'woocommerce' ),
	__( "😵 Dizzy AI… Let's regroup and try again!", 'woocommerce' ),
	__(
		"🦄 Rare hiccup spotted! Fear not, let's retry for that magic.",
		'woocommerce'
	),
	__(
		"🙈 This didn't go as planned… let's give it another whirl!",
		'woocommerce'
	),
	__(
		'🙈 Whoops! Our AI slipped on a banana peel. Please try again.',
		'woocommerce'
	),
	__(
		'🪄 Oops, our magic wand went wonky. Give it another whirl!',
		'woocommerce'
	),
	__( '🦄 Oops, our unicorns tripped! Try again?', 'woocommerce' ),
	__(
		'🐌 Darn! Our digital squirrels got distracted. Another shot?',
		'woocommerce'
	),
	__( '💥 Uh-oh! Creative circuit overload. Care to retry?', 'woocommerce' ),
	__(
		'🔌 Oops! Our AI hamsters tripped on the wheel. Please try again.',
		'woocommerce'
	),
	__( "🧠 Brain freeze! Let's try that again.", 'woocommerce' ),
	__(
		'🦖 Seems like a T-Rex stomped on our idea machine… Try again?',
		'woocommerce'
	),
	__(
		'🪄 Our magic wand glitched! Mind giving it another try?',
		'woocommerce'
	),
];

const getRandomErrorMessage = () =>
	errorMessages[ Math.floor( Math.random() * errorMessages.length ) ];

function ErrorMessage( { error }: ErrorMessageProps ) {
	return (
		<span>
			{ error && error.length > 0 ? error : getRandomErrorMessage() }
		</span>
	);
}

export default ErrorMessage;
