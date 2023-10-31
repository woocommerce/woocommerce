/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Alert from '../../assets/images/alert.svg';
import {
	InstallingStateError,
	installingStore,
} from '../../contexts/install-store';

export default function Notices() {
	const errors: InstallingStateError[] = useSelect(
		( select ) => select( installingStore ).errors(),
		[]
	);

	const removeError = ( productKey: string ) => {
		dispatch( installingStore ).removeError( productKey );
	};

	const errorNotices = [];
	for ( const error of errors ) {
		errorNotices.push(
			<Notice
				status="error"
				onRemove={ () => removeError( error.productKey ) }
				key={ error.productKey }
			>
				<img src={ Alert } alt="" width={ 24 } height={ 24 } />
				{ error.error }
			</Notice>
		);
	}
	return <>{ errorNotices }</>;
}
