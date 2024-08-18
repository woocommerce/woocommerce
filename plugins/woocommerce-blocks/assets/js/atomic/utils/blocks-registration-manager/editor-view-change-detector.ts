/**
 * External dependencies
 */
import { subscribe, select } from '@wordpress/data';
import { getPath, getQueryArg } from '@wordpress/url';

enum ContentType {
	WP_TEMPLATE = 'wp_template',
	WP_TEMPLATE_PART = 'wp_template_part',
	POST = 'post',
	PAGE = 'page',
	NONE = 'none',
}

interface EditorViewChangeDetectorSubject {
	add( observer: EditorViewChangeDetectorObserver ): void;
	getPreviousTemplateId(): string | undefined;
	getCurrentTemplateId(): string | undefined;
	notify(): void;
}

export interface EditorViewChangeDetectorObserver {
	run( subject: EditorViewChangeDetectorSubject ): void;
}

/**
 * This class implements the EditorViewChangeDetectorSubject interface and is responsible for detecting changes in the
 * current template or page and notifying any observers of these changes. It maintains a list of observers and provides methods
 * to add observers and notify them of changes.
 *
 * The class also provides methods to get the previous and current template IDs and whether the editor is in a post or page.
 *
 * The `checkIfTemplateHasChangedAndNotifySubscribers` method is the main method of the class. It checks if the current
 * template has changed and, if so, notifies all observers.
 */
export class EditorViewChangeDetector
	implements EditorViewChangeDetectorSubject
{
	private previousContentType: ContentType | undefined;
	private currentContentType: ContentType | undefined;
	private previousPageLocation = '';
	private currentPageLocation = '';

	private observers: EditorViewChangeDetectorObserver[] = [];

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
		const editedContentType =
			select( 'core/editor' ).getCurrentPostType< ContentType >() ||
			select( 'core/edit-site' )?.getEditedPostType<
				'wp_template' | 'wp_template_part'
			>();

		return editedContentType || ContentType.NONE;
	}

	private checkIfPageLocationHasChanged(): boolean {
		if ( this.currentContentType !== this.previousContentType ) {
			return true;
		}

		if (
			this.currentContentType === ContentType.POST ||
			this.currentContentType === ContentType.PAGE
		) {
			return (
				this.getPostOrPageIdFromUrl( this.currentPageLocation ) !==
				this.getPostOrPageIdFromUrl( this.previousPageLocation )
			);
		}

		if ( this.currentContentType === ContentType.WP_TEMPLATE ) {
			return (
				this.getCurrentTemplateIdFromUrl( this.currentPageLocation ) !==
				this.getCurrentTemplateIdFromUrl( this.previousPageLocation )
			);
		}

		return false;
	}

	public add( observer: EditorViewChangeDetectorObserver ): void {
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
		return this.getCurrentTemplateIdFromUrl( this.previousPageLocation );
	}

	public getCurrentTemplateId() {
		return this.getCurrentTemplateIdFromUrl( this.currentPageLocation );
	}

	private getCurrentTemplateIdFromUrl( url: string ): string | undefined {
		const path = getPath( url );
		const isTemplatePage = path
			? path.includes( 'site-editor.php' )
			: false;
		let templateId;

		if ( isTemplatePage ) {
			const fullTemplateId = getQueryArg( url, 'postId' ) as string;
			templateId = fullTemplateId?.split( '//' )[ 1 ];
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

	public getIsPostOrPage(): boolean {
		return (
			this.currentContentType === ContentType.POST ||
			this.currentContentType === ContentType.PAGE
		);
	}
}
