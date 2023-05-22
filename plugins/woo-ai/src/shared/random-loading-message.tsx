/**
 * External dependencies
 */
import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { shuffleArray } from './utils';

// Define the Property types for the RandomLoadingMessage component
interface RandomLoadingMessageProps {
	isLoading: boolean;
}

const earlyLoadingPhrases = [
	__( '🚀 Launching into the creative cosmos…', 'woocommerce' ),
	__( '🧠 Digging deep in our brain vaults…', 'woocommerce' ),
	__( '🔍 Searching high and low for the perfect words…', 'woocommerce' ),
	__( '🔮 Gazing into the future for better sales…', 'woocommerce' ),
	__( '🚀 Digital squirrels on a mission! Hang tight…', 'woocommerce' ),
	__( "🧙‍ Summoning shopping gurus' wisdom…", 'woocommerce' ),
	__( '🔮 Crystal ball magic in progress…', 'woocommerce' ),
	__( '🕰️ Time-traveling for AI inspiration…', 'woocommerce' ),
	__( '🦄 Unicorns sprinkling creative dust…', 'woocommerce' ),
	__( '🌪️ Whirling up a storm of ideas…', 'woocommerce' ),
	__( '🏎️ Speeding through idea highways…', 'woocommerce' ),
	__( '🌟 Shooting stars of inspiration…', 'woocommerce' ),
	__( '🍕 Cooking up a slice of AI genius…', 'woocommerce' ),
	__( "🎁 Unwrapping your store's surprise…", 'woocommerce' ),
	__( '🕵️‍♂️ AI undercover for the perfect idea…', 'woocommerce' ),
	__( '🧠 Tapping into the shopping hive mind…', 'woocommerce' ),
	__( '🧪 Mixing e-commerce potions, stand by…', 'woocommerce' ),
	__(
		"🍽️ AI's digesting the secrets of e-commerce success. Nom nom!",
		'woocommerce'
	),
	__( '🧪 Synthesizing secret conversion formula…', 'woocommerce' ),
	__( '🍳 Cooking up some sales-boosting word soufflé…', 'woocommerce' ),
	__( '🤖 Activating creative circuits…', 'woocommerce' ),
	__( '🔍 Analyzing your store like a boss…', 'woocommerce' ),
	__( '🔥 Fueling your e-commerce rocket for takeoff…', 'woocommerce' ),
	__( '🎩 Pulling fancy words out of my hat…', 'woocommerce' ),
	__(
		'🧙‍♂️ Summoning web wizards for an enchanting store experience…',
		'woocommerce'
	),
	__( '🔐 Unlocking the treasure trove of sales tips…', 'woocommerce' ),
	__( '💡 Powering up the idea generator…', 'woocommerce' ),
	__( '🦉 Channeling the wisdom of e-commerce owls…', 'woocommerce' ),
	__( '🔭 Scanning the galaxy for stellar sales ideas…', 'woocommerce' ),
	__( '📚 Flipping through the book of e-commerce tricks…', 'woocommerce' ),
	__( '🪄 Waving the wand of online sales success…', 'woocommerce' ),
	__( '🤹‍♂️ Juggling creative thoughts for an epic product…', 'woocommerce' ),
	__( '🧞‍♂️ Rubbing the e-commerce genie lamp…', 'woocommerce' ),
	__(
		'🔭 Aiming for top-notch ideas in the digital universe…',
		'woocommerce'
	),
	__( '📚 Cracking open the e-commerce encyclopedia…', 'woocommerce' ),
	__( '🐜 Sneakily inspecting your product attributes…', 'woocommerce' ),
	__( '🏋️‍♂️ Lifting your sales game, one byte at a time…', 'woocommerce' ),
	__( '🍰 Baking up a batch of conversion-boosting pies…', 'woocommerce' ),
	__( '🎩 Sprinkling a touch of magic…', 'woocommerce' ),
	__(
		'🛒 Shopping for the perfect phrases to lure in customers…',
		'woocommerce'
	),
];

const lateLoadingPhrases = [
	__( '🏎️ We’re speeding up, just a few more tweaks…', 'woocommerce' ),
	__( '🕰️ Taking our time to perfection, like a fine wine…', 'woocommerce' ),
	__( '⏳ Sands of time turning e-commerce ideas into gold…', 'woocommerce' ),
	__( "🍩 Donut worry, we're almost there!", 'woocommerce' ),
	__( "🤗 Thanks for bearing with us, you're the real MVP!", 'woocommerce' ),
	__( "🧘‍♂️ Breathe in, breathe out… we're almost done!", 'woocommerce' ),
	__( "🎉 You're so patient, we owe you a party!", 'woocommerce' ),
	__( '🐇 Just wrapping up. Rabbit speed engaged!', 'woocommerce' ),
	__( "🛀 Aaaand relax. We promise, we're close!", 'woocommerce' ),
	__(
		'⏲️ The secret sauce takes time…but it’s worth it, promise!',
		'woocommerce'
	),
	__(
		'🎶 Whistle while we work – your tune is our favorite!',
		'woocommerce'
	),
	__( '😬 Bear with me, almost there…', 'woocommerce' ),
	__( '💡 Waiting for the lightbulb moment…', 'woocommerce' ),
	__( '😅 Still here? We are too…', 'woocommerce' ),
	__( '🥁 Drum roll, please… still loading.', 'woocommerce' ),
	__( "🙈 Peek-a-boo! We're still working, promise.", 'woocommerce' ),
	__( '🙈 This is awkward… still working!', 'woocommerce' ),
	__( '⌛ Time flies when you’re waiting, huh?', 'woocommerce' ),
	__( '🔧 Grabbing a wrench to speed things up!', 'woocommerce' ),
	__( '🚶 Taking the scenic route, it seems…', 'woocommerce' ),
	__( '🐢 Oops, the turtle gained some speed!', 'woocommerce' ),
	__( '💤 Sorry for the yawn… We’re awake!', 'woocommerce' ),
	__( '🦥 Slow and steady wins… the sale?', 'woocommerce' ),
	__( '🔧 Fine-tuning our magic formula…', 'woocommerce' ),
	__( "🍰 We promise it's almost icing on the cake…", 'woocommerce' ),
	__( '🐘 Stomping through loads of digital data…', 'woocommerce' ),
	__( '🎯 Almost there, thanks for waiting!', 'woocommerce' ),
	__( '⏳ We appreciate your heroic patience.', 'woocommerce' ),
];

const veryLateLoadingPhrases = [
	__(
		'🎩 Please pardon our slowness; we’re fine-tuning our top hat tricks…',
		'woocommerce'
	),
	__( '😅 Still here, not frozen… Honest!', 'woocommerce' ),
	__( '🐢 I see the finish line! Hold on…', 'woocommerce' ),
	__( '😂 Who knew AI could get stage fright?', 'woocommerce' ),
	__( '🍿 Longer loading time? Popcorn, anyone?', 'woocommerce' ),
	__( '🍵 Grab a cuppa, this might take a sec…', 'woocommerce' ),
	__( '🐢 Quick as a…artificial turtle?', 'woocommerce' ),
	__( '⏳ Time is an illusion, while waiting is an art…', 'woocommerce' ),
	__( '😬 Might be going slower than dial-up…', 'woocommerce' ),
	__( '⏳ Patience is a virtue, right?…', 'woocommerce' ),
	__( '🤖 AI shame mode activated, optimizing faster…', 'woocommerce' ),
	__( '💤 Should I play some Lo-Fi beats while we wait?', 'woocommerce' ),
	__( '😅 You could bake a cake in this time…', 'woocommerce' ),
	__( '🐢 Not typically this slow, I promise…', 'woocommerce' ),
	__( '😬 So, how’s the weather?', 'woocommerce' ),
	__(
		"🏅 Congrats! You'll win gold in the waiting Olympics!",
		'woocommerce'
	),
	__( '🐌 Hitching a ride with a snail…', 'woocommerce' ),
	__( '🥶 Loading so slow, even glaciers are jealous…', 'woocommerce' ),
	__( '🌳 Entertaining sloths with our speed…', 'woocommerce' ),
	__( "♟️ Blame it on the AI's newfound love for chess.", 'woocommerce' ),
	__( '🕰️ Feeling a bit like the nineties in here…', 'woocommerce' ),
	__( '🐹 Maybe we need to upgrade our hamster wheel…', 'woocommerce' ),
];

// Function to return the shuffled phrases
const getShuffledPhrases = () => {
	return {
		early: shuffleArray( earlyLoadingPhrases ),
		late: shuffleArray( lateLoadingPhrases ),
		veryLate: shuffleArray( veryLateLoadingPhrases ),
	};
};

// Returns a random loading message from the list based on the elapsed time
const getRandomLoadingPhrase = (
	elapsedTime: number,
	phrasesStack: { [ x: string ]: string[] }
): string => {
	let key = 'early';
	if ( elapsedTime >= 6000 && elapsedTime < 16000 ) {
		key = 'late';
	} else if ( elapsedTime >= 16000 ) {
		key = 'veryLate';
	}

	// Pop the first message from the stack and push it back in
	const poppedMessage = phrasesStack[ key ].shift();

	if ( ! poppedMessage ) {
		return '';
	}

	phrasesStack[ key ].push( poppedMessage );

	return poppedMessage;
};

const RandomLoadingMessage: React.FC< RandomLoadingMessageProps > = ( {
	isLoading,
} ) => {
	const messageUpdateTimeout = useRef< number >();
	const startTimeRef = useRef< number >( 0 );
	const phrasesStack = getShuffledPhrases();
	const [ currentMessage, setCurrentMessage ] = useState(
		getRandomLoadingPhrase( 0, phrasesStack )
	);

	// Recursive function to update the message on an increasing time interval
	const updateMessage = useCallback(
		( delay: number ) => {
			messageUpdateTimeout.current = window.setTimeout( () => {
				const elapsedTime = Date.now() - startTimeRef.current;
				setCurrentMessage(
					getRandomLoadingPhrase( elapsedTime, phrasesStack )
				);

				updateMessage( delay * 1.5 );
			}, delay );
		},
		[ phrasesStack ]
	);

	useEffect( () => {
		if ( isLoading ) {
			startTimeRef.current = Date.now();

			updateMessage( 3000 );
		} else {
			clearTimeout( messageUpdateTimeout.current );
		}

		return () => {
			clearTimeout( messageUpdateTimeout.current );
		};
	}, [ isLoading ] );

	return <span>{ currentMessage }</span>;
};

export default RandomLoadingMessage;
