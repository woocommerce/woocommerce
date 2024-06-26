/**
 * External dependencies
 */
import { withDispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './data/constants';
import './data';

function RemoteSpecValidator( { validate, message, setMessage } ) {
	const exampleText = JSON.stringify(
		[
			{
				type: 'plugin_version',
				plugin: 'woocommerce',
				version: '6.5.0-dev',
				operator: '>=',
			},
		],
		null,
		4
	);
	const [ spec, setSpec ] = useState( exampleText );
	return (
		<>
			<p>Paste your Remote Spec rule and click Validate button.</p>
			<div id="wc-admin-test-helper-remote-spec-validator">
				<textarea
					value={ spec }
					onChange={ ( e ) => setSpec( e.target.value ) }
				/>
				{ message && message.text && (
					<div className={ message.type }>{ message.text }</div>
				) }
				<input
					type="button"
					className="button btn-primary btn-validate"
					value="Validate"
					onClick={ () => {
						try {
							if ( JSON.parse( spec ) ) {
								setMessage( null, null );
							}
							validate( spec );
						} catch ( e ) {
							setMessage( 'error', 'Invalid JSON' );
						}
					} }
				/>
			</div>
		</>
	);
}

export default compose(
	withSelect( ( select ) => {
		const { getMessage } = select( STORE_KEY );
		return {
			message: getMessage(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { validate, setMessage } = dispatch( STORE_KEY );

		return {
			validate,
			setMessage,
		};
	} )
)( RemoteSpecValidator );
