/**
 * External dependencies
 */
import { subscribe, select } from '@wordpress/data';
import { getQueryArg } from '@wordpress/url';

export enum EditorViewContentType {
	WP_TEMPLATE = 'wp_template',
	WP_TEMPLATE_PART = 'wp_template_part',
	POST = 'post',
	PAGE = 'page',
	NONE = 'none',
}

interface EditorViewChangeDetectorSubject {
	add( observer: EditorViewChangeDetectorObserver ): void;
	getCurrentContentId(): string | undefined;
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
	private previousContentType: EditorViewContentType | undefined;
	private currentContentType: EditorViewContentType | undefined;
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

	private detectContentType(): EditorViewContentType {
		const editedContentType =
			select(
				'core/editor'
			).getCurrentPostType< EditorViewContentType >() ||
			select( 'core/edit-site' )?.getEditedPostType<
				'wp_template' | 'wp_template_part'
			>();

		return editedContentType || EditorViewContentType.NONE;
	}

	private checkIfPageLocationHasChanged(): boolean {
		if ( this.currentContentType !== this.previousContentType ) {
			return true;
		}

		if (
			this.currentContentType === EditorViewContentType.POST ||
			this.currentContentType === EditorViewContentType.PAGE ||
			this.currentContentType === EditorViewContentType.WP_TEMPLATE ||
			this.currentContentType === EditorViewContentType.WP_TEMPLATE_PART
		) {
			return (
				this.getContentIdFromUrl( this.currentPageLocation ) !==
				this.getContentIdFromUrl( this.previousPageLocation )
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
		return this.getContentIdFromUrl( this.previousPageLocation );
	}

	public getCurrentContentId() {
		return this.getContentIdFromUrl( this.currentPageLocation );
	}

	public getCurrentContentType() {
		return this.currentContentType;
	}

	private getContentIdFromUrl( url: string ): string | undefined {
		let contentId;

		if ( this.getIsPostOrPage() ) {
			contentId = getQueryArg( url, 'post' );
		}

		if ( this.getIsTemplateOrTemplatePart() ) {
			const fullTemplateId = getQueryArg( url, 'postId' ) as string;
			contentId = fullTemplateId?.split( '//' )[ 1 ];
		}

		return contentId as string;
	}

	public getIsPostOrPage(): boolean {
		return (
			this.currentContentType === EditorViewContentType.POST ||
			this.currentContentType === EditorViewContentType.PAGE
		);
	}

	public getIsTemplateOrTemplatePart(): boolean {
		return (
			this.currentContentType === EditorViewContentType.WP_TEMPLATE ||
			this.currentContentType === EditorViewContentType.WP_TEMPLATE_PART
		);
	}
}
