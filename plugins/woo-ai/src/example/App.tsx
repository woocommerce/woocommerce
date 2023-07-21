/**
 * External dependencies
 */
import { css } from '@emotion/react';
import { Card, CardBody } from '@wordpress/components';

export const App = () => {
	const cardStyle = css( {
		minHeight: '500px',
	} );

	return (
		<Card elevation={ 3 } css={ cardStyle }>
			<CardBody>
				<p>testing</p>
			</CardBody>
		</Card>
	);
};
