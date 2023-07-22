/**
 * External dependencies
 */
import { css } from '@emotion/react';
import { Card, CardBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ImageProcessor from './ImageProcessor';

export const App = () => {
	const cardStyle = css( {
		minHeight: '500px',
	} );

	return (
		<Card elevation={ 3 } css={ cardStyle }>
			<CardBody>
				<ImageProcessor />
			</CardBody>
		</Card>
	);
};
