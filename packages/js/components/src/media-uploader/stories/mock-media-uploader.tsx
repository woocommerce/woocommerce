/**
 * External dependencies
 */
import React, { createElement } from 'react';
import { Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';

export const MockMediaUpload = ( { onSelect, render } ) => {
	const [ isOpen, setOpen ] = useState( false );

	return (
		<>
			{ render( {
				open: () => setOpen( true ),
			} ) }
			{ isOpen && (
				<Modal
					title="Media Modal"
					onRequestClose={ ( event ) => {
						setOpen( false );
						event.stopPropagation();
					} }
				>
					<p>
						Use the default built-in{ ' ' }
						<code>MediaUploadComponent</code> prop to render the WP
						Media Modal.
					</p>
					{ Array( ...Array( 3 ) ).map( ( n, i ) => {
						return (
							<button
								key={ i }
								onClick={ ( event ) => {
									onSelect( {
										alt: 'Random',
										url: `https://picsum.photos/200?i=${ i }`,
									} );
									setOpen( false );
									event.stopPropagation();
								} }
								style={ {
									marginRight: '16px',
								} }
							>
								<img
									src={ `https://picsum.photos/200?i=${ i }` }
									alt="Random"
									style={ {
										maxWidth: '100px',
									} }
								/>
							</button>
						);
					} ) }
				</Modal>
			) }
		</>
	);
};
