/**
 * External dependencies
 */
import { subscribe } from '@wordpress/data';
import { getPath, getQueryArg } from '@wordpress/url';

enum ContentType {
	POST_OR_PAGE = 'post-or-page',
	TEMPLATE = 'template',
	NONE = 'none',
}

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
	private previousContentType: ContentType | undefined;
	private currentContentType: ContentType | undefined;
	private previousPageLocation = '';
	private currentPageLocation = '';

	private observers: TemplateChangeDetectorObserver[] = [];

	constructor() {
		subscribe( () => {
			this.previousPageLocation = this.currentPageLocation;
			this.currentPageLocation = window.location.href;
			this.previousContentType = this.currentContentType;
			this.currentContentType = this.detectContentType();
			const isPageLocationUpdated = this.checkIfPageLocationHasChanged();

			if ( isPageLocationUpdated ) {
				this.notify();
			}
		}, 'core/edit-site' );
	}

	private detectContentType(): ContentType {
		const path = getPath( window.location.href );

		if ( ! path ) {
			return ContentType.NONE;
		}

		if ( path.includes( 'site-editor.php' ) ) {
			return ContentType.TEMPLATE;
		}

		if (
			path.includes( 'post.php' ) ||
			path.includes( 'post-new.php' ) ||
			path.includes( 'page-new.php' )
		) {
			return ContentType.POST_OR_PAGE;
		}

		return ContentType.NONE;
	}

	private checkIfPageLocationHasChanged(): boolean {
		if ( this.currentContentType !== this.previousContentType ) {
			return true;
		}

		if ( this.currentContentType === ContentType.POST_OR_PAGE ) {
			return (
				this.getPostOrPageIdFromUrl( this.currentPageLocation ) !==
				this.getPostOrPageIdFromUrl( this.previousPageLocation )
			);
		}

		if ( this.currentContentType === ContentType.TEMPLATE ) {
			return (
				this.getCurrentTemplateIdFromUrl( this.currentPageLocation ) !==
				this.getCurrentTemplateIdFromUrl( this.previousPageLocation )
			);
		}

		return false;
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

	private getCurrentTemplateIdFromUrl( url: string ): string | undefined {
		const path = getPath( url );
		const isTemplatePage = path
			? path.includes( 'site-editor.php' )
			: false;
		let templateId;

		if ( isTemplatePage ) {
			templateId = getQueryArg( url, 'postId' );
		}

		return templateId as string;
	}

	private getPostOrPageIdFromUrl( url: string ): string | undefined {
		const path = getPath( url );
		const isPostOrPage = path ? path.includes( 'post.php' ) : false;
		let postId;

		if ( isPostOrPage ) {
			postId = getQueryArg( url, 'post' );
		}

		return postId as string;
	}
}
