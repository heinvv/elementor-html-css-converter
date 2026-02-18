import { z } from 'zod';
import { callConverterApi } from './rest-client';

export function initMcp() {
	const editorMcp = ( window as any ).elementorV2?.[ 'editor-mcp' ];

	if ( ! editorMcp?.getMCPByDomain ) {
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
				post_title: params.postTitle,
				postId: params.postId,
				import_variables: params.importVariables ?? true,
				import_classes: params.importClasses ?? true,
				import_images: params.importImages ?? true,
			} );
			return {
				success: result.success,
				postId: result.post_id,
				editUrl: result.edit_url,
				widgetCount: result.widgets?.length ?? 0,
				importedVariables: Object.keys( result.imported_variables ?? {} ).length,
				importedClasses: Object.keys( result.imported_classes ?? {} ).length,
				importedImages: result.imported_images?.length ?? 0,
			};
		},
	} );

	addTool( {
		name: 'import-variables',
		description: 'Imports CSS custom properties from a CSS string as Elementor Global Variables. Expects :root { --variable-name: value; } declarations.',
		schema: {
			css: z.string().describe( 'CSS string containing :root { --variable: value; } declarations' ),
		},
		handler: async ( params: { css: string } ) => {
			const result = await callConverterApi( 'import-variables', 'POST', { css: params.css } );
			return { success: result.success, imported: result.imported ?? {} };
		},
	} );

	addTool( {
		name: 'import-classes',
		description: 'Imports CSS class rules from a CSS string as Elementor Global Classes.',
		schema: {
			css: z.string().describe( 'CSS string containing .class-name { property: value; } rules' ),
		},
		handler: async ( params: { css: string } ) => {
			const result = await callConverterApi( 'import-classes', 'POST', { css: params.css } );
			return { success: result.success, imported: result.imported ?? {} };
		},
	} );
}
