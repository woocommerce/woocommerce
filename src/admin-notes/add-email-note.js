/**
 * External dependencies.
 */
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

export const AddEmailNote = () => {
	const [isAdding, setIsAdding] = useState(false);
	const [hasAdded, setHasAdded] = useState(false);
	const [errorMessage, setErrorMessage] = useState(false);

	async function triggerAddEmailNote() {
		setIsAdding(true);
		setHasAdded(false);
		setErrorMessage(false);

		const name = prompt('Enter the note name');
		if (!name) {
			setIsAdding(false);
			return;
		}

		const title = prompt('Enter the note title');
		if (!title) {
			setIsAdding(false);
			return;
		}

		try {
			await apiFetch({
				path: '/wc-admin-test-helper/admin-notes/add-email-note/v1',
				method: 'POST',
				data: {
					name,
					title,
				},
			});
			setHasAdded(true);
		} catch (ex) {
			setErrorMessage(ex.message);
		}

		setIsAdding(false);
	}

	return (
		<>
			<p>
				<strong>
					Add an <u>email</u> note
				</strong>
			</p>
			<p>
				This will add a new <strong>email</strong> note. Enable email
				insights{' '}
				<a href="/wp-admin/admin.php?page=wc-settings&tab=email">
					here
				</a>{' '}
				and run the cron to send the note by email.
				<br />
				<Button
					onClick={triggerAddEmailNote}
					disabled={isAdding}
					isPrimary
				>
					Add email note
				</Button>
				<br />
				<span className="woocommerce-admin-test-helper__action-status">
					{isAdding && 'Adding, please wait'}
					{hasAdded && 'Email note added'}
					{errorMessage && (
						<>
							<strong>Error:</strong> {errorMessage}
						</>
					)}
				</span>
			</p>
		</>
	);
};
