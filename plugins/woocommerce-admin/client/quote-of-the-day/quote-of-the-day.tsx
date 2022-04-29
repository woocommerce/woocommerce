/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { Card, CardBody, CardFooter } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './quote-of-the-day.scss';

type Quote = {
	q: string;
	a: string;
	h: string;
};

async function getQuoteOfTheDay(): Promise< Quote > {
	return apiFetch( {
		path: 'wc-admin/quote-of-the-day',
		method: 'GET',
	} );
}

export const QuoteOfTheDay: React.FC< Record< string, never > > = () => {
	const [ quote, setQuote ] = useState< Quote | null >( null );

	useEffect( () => {
		getQuoteOfTheDay().then( ( q: Quote ) => {
			setQuote( q );
		} );
	}, [] );

	if ( quote === null ) {
		return null;
	}
	return (
		<Card className="quote-of-the-day">
			<CardBody>
				<h2>{ quote.q }</h2>
			</CardBody>
			<CardFooter>{ quote.a }</CardFooter>
		</Card>
	);
};
