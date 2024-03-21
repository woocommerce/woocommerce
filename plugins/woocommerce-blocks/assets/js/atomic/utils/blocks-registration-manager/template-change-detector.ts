/**
 * External dependencies
 */
import { subscribe, select } from '@wordpress/data';
import { isNumber } from '@woocommerce/types';

interface TemplateChangeDetectorSubject {
	add( observer: TemplateChangeDetectorObserver ): void;
	getPreviousTemplateId(): string | undefined;
	getCurrentTemplateId(): string | undefined;
	notify(): void;
}

export interface TemplateChangeDetectorObserver {
	run( subject: TemplateChangeDetectorSubject ): void;
}

/**
 * This class implements the TemplateChangeDetectorSubject interface and is responsible for detecting changes in the
 * current template or page and notifying any observers of these changes. It maintains a list of observers and provides methods
 * to add observers and notify them of changes.
 *
 * The class also provides methods to get the previous and current template IDs and whether the editor is in a post or page.
 *
 * The `checkIfTemplateHasChangedAndNotifySubscribers` method is the main method of the class. It checks if the current
 * template has changed and, if so, notifies all observers.
 */
export class TemplateChangeDetector implements TemplateChangeDetectorSubject {
	private previousTemplateId: string | undefined;
	private currentTemplateId: string | undefined;
	private isPostOrPage: boolean;

	private observers: TemplateChangeDetectorObserver[] = [];

	constructor() {
		this.isPostOrPage = false;
		subscribe( () => {
			this.checkIfTemplateHasChangedAndNotifySubscribers();
		}, 'core/edit-site' );
	}

	public add( observer: TemplateChangeDetectorObserver ): void {
		this.observers.push( observer );
	}

	/**
	 * Trigger an update in each subscriber.
	 */
	public notify(): void {
		for ( const observer of this.observers ) {
			observer.run( this );
		}
	}

	public getPreviousTemplateId() {
		return this.previousTemplateId;
	}

	public getCurrentTemplateId() {
		return this.currentTemplateId;
	}

	public getIsPostOrPage() {
		return this.isPostOrPage;
	}

	/**
	 * Parses the template ID.
	 *
	 * This method takes a template or a post ID and returns it parsed in the expected format.
	 *
	 * @param {string | number | undefined} templateId - The template ID to parse.
	 * @return {string | undefined} The parsed template ID.
	 */
	private parseTemplateId(
		templateId: string | number | undefined
	): string | undefined {
		if ( isNumber( templateId ) ) {
			return String( templateId );
		}
		return templateId?.split( '//' )[ 1 ];
	}

	/**
	 * Checks if the current template or page has changed and notifies subscribers.
	 *
	 * If the current template ID has changed and is not undefined (which means that it is not a page, post or template), it notifies all subscribers.
	 */
	public checkIfTemplateHasChangedAndNotifySubscribers(): void {
		this.previousTemplateId = this.currentTemplateId;

		const postOrPageId = select( 'core/editor' )?.getCurrentPostId<
			string | number | undefined
		>();

		this.isPostOrPage = Boolean( postOrPageId );

		const editedPostId =
			postOrPageId ||
			select( 'core/edit-site' )?.getEditedPostId<
				string | number | undefined
			>();
		this.currentTemplateId = this.parseTemplateId( editedPostId );

		const hasChangedTemplate =
			this.previousTemplateId !== this.currentTemplateId;
		const hasTemplateId = Boolean( this.currentTemplateId );

		if ( ! hasChangedTemplate || ! hasTemplateId ) {
			return;
		}

		this.notify();
	}
}
