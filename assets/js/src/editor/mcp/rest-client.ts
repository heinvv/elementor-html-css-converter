export async function callConverterApi(
	endpoint: string,
	method: 'GET' | 'POST',
	data?: Record<string, unknown>
) {
	const baseUrl = window.location.origin + '/index.php';
	const url = new URL( baseUrl );
	url.searchParams.set( 'rest_route', `/html-css-converter/v1/${ endpoint }` );

	const response = await fetch( url.toString(), {
		method,
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': ( window as any ).wpApiSettings?.nonce || '',
		},
		...( data && method === 'POST' ? { body: JSON.stringify( data ) } : {} ),
	} );

	if ( ! response.ok ) {
		throw new Error( `HTTP ${ response.status }: ${ await response.text() }` );
	}

	return response.json();
}
