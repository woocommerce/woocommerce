/**
 * Internal dependencies
 */
import { UserProfileEvent } from '../index';

export const UserProfile = ( {
	sendEvent,
}: {
	sendEvent: ( event: UserProfileEvent ) => void;
} ) => {
	return (
		<>
			<div>User Profile</div>
			<button
				onClick={ () =>
					sendEvent( {
						type: 'USER_PROFILE_COMPLETED',
						payload: {
							userProfile: {
								foo: { bar: 'qux' },
								skipped: false,
							},
						},
					} )
				}
			>
				Next
			</button>
			<button
				onClick={ () =>
					sendEvent( {
						type: 'USER_PROFILE_SKIPPED',
						payload: { userProfile: { skipped: true } },
					} )
				}
			>
				Skip
			</button>
		</>
	);
};
