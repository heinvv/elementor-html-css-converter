import { callConverterApi } from './rest-client';

export function initMcp() {
	const editorMcp = ( window as any ).elementorV2?.editorMcp;
	// Use Elementor's own bundled Zod instance to avoid version mismatch with the MCP SDK
	const z = ( window as any ).elementorV2?.schema?.z;

	if ( ! editorMcp?.getMCPByDomain || ! z ) {
		return;
	}

	const { setMCPDescription, addTool } = editorMcp.getMCPByDomain( 'html_css_converter' );

	setMCPDescription(
		`Convert HTML with CSS into Elementor atomic widgets, or import CSS variables/classes as Elementor Globals.
		IMPORTANT: Inline styles (style="...") are NOT supported — CSS must be in <style> tags using
		ID selectors (#id) or class selectors (.class). Variables in :root { } are imported as Global Variables.`
	);

	addTool( {
		name: 'convert-html',
		description: `Converts an HTML string (with embedded <style> tags) into Elementor atomic widgets
			and saves them in a WordPress post. Returns the post edit URL.
			Does NOT support inline styles — all CSS must be inside <style> blocks using ID or class selectors.`,
		schema: {
			html: z.string().describe( 'HTML content with <style> tags to convert' ),
			postTitle: z.string().optional().describe( 'Title for the post (default: "Converted HTML")' ),
			postId: z.number().optional().describe( 'Existing post ID to insert widgets into instead of creating a new post' ),
			importVariables: z.boolean().optional().describe( 'Import :root CSS variables as Elementor Global Variables (default: true)' ),
			importClasses: z.boolean().optional().describe( 'Import CSS classes as Elementor Global Classes (default: true)' ),
			importImages: z.boolean().optional().describe( 'Download and import external images into the media library (default: true)' ),
		},
		handler: async ( params: {
			html: string;
			postTitle?: string;
			postId?: number;
			importVariables?: boolean;
			importClasses?: boolean;
			importImages?: boolean;
		} ) => {
			const result = await callConverterApi( 'convert-html', 'POST', {
				html: params.html,
				postTitle: params.postTitle,
				postId: params.postId,
				import_variables: params.importVariables ?? true,
				import_classes: params.importClasses ?? true,
				import_images: params.importImages ?? true,
			} );

			if ( ! result.success ) {
				throw new Error( result.error || 'Conversion failed' );
			}

			// Insert widgets into the live editor canvas so they appear immediately.
			// The REST API writes to the DB, but the editor holds its own in-memory
			// state — without this step the editor would overwrite the DB on save
			// and the widgets would be lost.
			const doc = ( window as any ).elementor?.documents?.getCurrent();
			if ( doc && result.widgets?.length ) {
				const $e = ( window as any ).$e;

				// Atomic container types ('e-div-block', 'e-flexbox') are not registered
				// in elementor.config.elements, so getElementData() returns false for them.
				// This makes getModelLabel() return undefined, resulting in an element with
				// no valid title in the editor. Normalise to the classic 'container' type
				// so the command can resolve a proper label.
				const ATOMIC_CONTAINER_TYPES = [ 'e-div-block', 'e-flexbox' ];

				// Elements must be created with a minimal model (no settings, no children)
				// and then have settings applied via the internal set-settings command.
				// Passing settings inline in document/elements/create uses a different
				// initialisation path that does not correctly propagate $$type PropValues
				// (e.g. html-v2) to the atomic canvas store, leaving content empty.
				// The internal set-settings path is the same one used by configure-element.
				const insertElement = ( widget: Record< string, any >, parentContainer: any ): void => {
					if ( ! $e ) {
						return;
					}

					const isAtomicContainer = ATOMIC_CONTAINER_TYPES.includes( widget.elType );

					const minimalModel: Record< string, any > = {
						id: widget.id,
						elType: isAtomicContainer ? 'container' : widget.elType,
					};
					if ( ! isAtomicContainer && widget.widgetType ) {
						minimalModel.widgetType = widget.widgetType;
					}

					const created = $e.run( 'document/elements/create', {
						container: parentContainer,
						model: minimalModel,
					} );

					if ( ! created ) {
						return;
					}

					// Apply atomic settings through the internal command after creation.
					// This is the same path used by the configure-element MCP tool.
					if ( widget.settings && Object.keys( widget.settings ).length > 0 ) {
						$e.internal( 'document/elements/set-settings', {
							container: created,
							settings: widget.settings,
						} );
					}

					// Recursively create child elements.
					for ( const child of ( widget.elements ?? [] ) ) {
						insertElement( child, created );
					}
				};

				for ( const widget of result.widgets ) {
					insertElement( widget, doc.container );
				}

				// Reload the preview iframe so the newly added widgets are rendered
				// immediately via the atomic rendering path (reads from DB).
				$e?.run( 'preview/reload' );
			}

			const returnValue: Record<string, unknown> = {
				success: true,
				widgetCount: result.widgets?.length ?? 0,
				message: `Inserted ${ result.widgets?.length ?? 0 } widget(s) into the editor. Save the page to persist.`,
			};

			if ( result.body_page_settings ) {
				returnValue.body_page_settings = result.body_page_settings;
				returnValue.message = String( returnValue.message ) + ' Body styles (background, margin, padding) were applied to page settings.';
			}

			return returnValue;
		},
	} );

	addTool( {
		name: 'import-variables',
		description: 'Imports CSS custom properties from a CSS string as Elementor Global Variables. Pass the full CSS including :root { --variable-name: value; } declarations.',
		schema: {
			css: z.string().describe( 'CSS string containing :root { --variable: value; } declarations' ),
		},
		handler: async ( params: { css: string } ) => {
			const result = await callConverterApi( 'import-variables', 'POST', { css: params.css } );
			return {
				success: result.success,
				created: result.created ?? 0,
				reused: result.reused ?? 0,
				updated: result.updated ?? 0,
			};
		},
	} );

	addTool( {
		name: 'import-classes',
		description: 'Imports CSS class rules from a CSS string as Elementor Global Classes. Pass the full CSS including the class selector, e.g. ".my-class { color: red; }".',
		schema: {
			css: z.string().describe( 'CSS string containing .class-name { property: value; } rules' ),
		},
		handler: async ( params: { css: string } ) => {
			const result = await callConverterApi( 'import-classes', 'POST', { css: params.css } );
			return {
				success: result.success,
				detected: result.statistics?.detected ?? 0,
				converted: result.statistics?.converted ?? 0,
				registered: result.statistics?.registered ?? 0,
			};
		},
	} );
}
