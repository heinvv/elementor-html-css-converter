import { getReact } from '../utils/getReact';
import { UseImportPollingParams } from '../types/hooks';

function normalizeScreenshotBase64(value: unknown): string | undefined {
	if (typeof value === 'string' && value.length > 0) {
		return value;
	}
	
	if (value && typeof value === 'object') {
		const obj = value as { type?: string; data?: number[] };
		
		if ('type' in obj && obj.type === 'Buffer' && 'data' in obj && Array.isArray(obj.data)) {
			try {
				const arr = obj.data;
				if (arr.length === 0) {
					return undefined;
				}
				
				const chunk = 8192;
				let binary = '';
				for (let i = 0; i < arr.length; i += chunk) {
					binary += String.fromCharCode.apply(null, arr.slice(i, i + chunk) as unknown as number[]);
				}
				
				const base64 = btoa(binary);
				if (base64.length === 0) {
					return undefined;
				}
				
				return base64;
			} catch {
				return undefined;
			}
		}
		
		if ('data' in obj && Array.isArray(obj.data)) {
			try {
				const arr = obj.data;
				if (arr.length === 0) {
					return undefined;
				}
				
				const chunk = 8192;
				let binary = '';
				for (let i = 0; i < arr.length; i += chunk) {
					binary += String.fromCharCode.apply(null, arr.slice(i, i + chunk) as unknown as number[]);
				}
				
				const base64 = btoa(binary);
				return base64.length > 0 ? base64 : undefined;
			} catch {
				return undefined;
			}
		}
	}
	
	return undefined;
}

export const useImportPolling = ({
	apiUrl,
	setStatusMessage,
	setStatusType,
	setIsLoading,
	setDiagnostics,
	onClose,
	isOpen,
	postId,
}: UseImportPollingParams) => {
	const ReactLib = getReact();
	if (!ReactLib) {
		return null;
	}

	const { useRef, useEffect } = ReactLib;
	const pollIntervalRef = useRef(null);

	useEffect(() => {
		if (!isOpen) {
			if (pollIntervalRef.current) {
				clearInterval(pollIntervalRef.current);
				pollIntervalRef.current = null;
			}
			setStatusMessage(null);
			setStatusType(null);
			setDiagnostics(null);
			setIsLoading(false);
		}
	}, [isOpen, setStatusMessage, setStatusType, setDiagnostics, setIsLoading]);

	const startPolling = (jobId: string, scraperEndpoint: string, url?: string, selectors?: string) => {
		let attempts = 0;
		const maxAttempts = 60;
		const pollInterval = 3000;
		const resultsUrl = scraperEndpoint.replace(/\/+$/, '') + '/results/' + jobId;

		const interval = setInterval(async () => {
			attempts++;

			if (attempts > maxAttempts) {
				clearInterval(interval);
				pollIntervalRef.current = null;
				setStatusMessage('Timeout waiting for results');
				setStatusType('error');
				setIsLoading(false);
				return;
			}

			try {
				const response = await fetch(resultsUrl);

				if (!response.ok) {
					const errorBody = await response.text();
					let errorDetail = `HTTP ${response.status}`;
					try {
						const errJson = JSON.parse(errorBody);
						if (errJson.error) {
							errorDetail = errJson.error;
						}
					} catch {
						if (errorBody) {
							errorDetail = errorBody.slice(0, 200);
						}
					}
					clearInterval(interval);
					pollIntervalRef.current = null;
					setStatusMessage(`Scraper error: ${errorDetail}`);
					setStatusType('error');
					setIsLoading(false);
					return;
				}

				const data = await response.json();

				if (data.status === 'pending') {
					return;
				}

				if (data.status === 'error') {
					clearInterval(interval);
					pollIntervalRef.current = null;
					const errorParts = [data.error || 'Import failed'];
					if (url) errorParts.push(`URL: ${url}`);
					setStatusMessage(errorParts.join('. '));
					setStatusType('error');
					setIsLoading(false);
					return;
				}

				if (data.status === 'success' && data.results) {
					clearInterval(interval);
					pollIntervalRef.current = null;

					const scrapeData = data.results.scrape;
					const elementCount = scrapeData?.elements?.length ?? 0;
					const hasHtml = typeof data.results.html === 'string' && data.results.html.trim().length > 0;

					if (elementCount === 0 && !hasHtml) {
						const rawDiag = data.diagnostics;
						const screenshotStr = rawDiag ? normalizeScreenshotBase64(rawDiag.screenshotBase64) : undefined;

						const diagnostics = rawDiag
							? {
									...rawDiag,
									screenshotBase64: screenshotStr,
							  }
							: undefined;

						let statusMessage = 'No elements matched.';
						
						if (diagnostics?.botDetection?.detected) {
							const botType = diagnostics.botDetection.type;
							if (botType === 'captcha') {
								statusMessage = 'Bot protection detected: The website is blocking automated access with CAPTCHA or challenge page.';
							} else if (botType === 'access-denied') {
								statusMessage = 'Access denied: The website is blocking automated requests.';
							} else if (botType === 'rate-limit') {
								statusMessage = 'Rate limited: Too many requests. Please wait before trying again.';
							} else if (botType === 'redirect') {
								statusMessage = 'Unexpected redirect detected: The website redirected to a different page than requested.';
							} else {
								statusMessage = 'Possible bot protection detected: The website may be blocking automated access.';
							}
							statusMessage += ' See details below.';
							setDiagnostics(diagnostics);
						} else if (diagnostics?.finalUrl && diagnostics?.requestedUrl && diagnostics.finalUrl !== diagnostics.requestedUrl) {
							statusMessage = `URL redirected: Expected ${diagnostics.requestedUrl} but got ${diagnostics.finalUrl}. No matching elements found. See diagnostics below.`;
							setDiagnostics(diagnostics);
						} else if (diagnostics && (diagnostics.screenshotBase64 || diagnostics.htmlPreview)) {
							statusMessage = 'No elements matched the selectors. See diagnostics below for page details.';
							setDiagnostics(diagnostics);
						} else {
							setDiagnostics(diagnostics || null);
							const parts = [
								'No elements matched the selectors.',
								url ? `URL: ${url}` : null,
								selectors ? `Selectors: ${selectors}` : null,
								(rawDiag as { hint?: string })?.hint || 'Try inspecting the page to verify the selector exists, or use a broader selector (e.g. header, main).',
							].filter(Boolean);
							statusMessage = parts.join(' ');
						}
						
						setStatusMessage(statusMessage);
						setStatusType('error');
						setIsLoading(false);
						return;
					}

					setStatusMessage('Scrape complete. Converting to Elementor...');
					setStatusType('info');

					try {
						const convertBody: Record<string, unknown> = {
							html: data.results.html,
							save_as_template: true,
							import_variables: true,
							import_classes: false,
						};
						const cssVariables = data.results.scrape?.cssVariables;
						if (typeof cssVariables === 'string' && cssVariables.trim().length > 0) {
							convertBody.css_variables = cssVariables;
						}
						const convertResponse = await fetch(apiUrl + 'convert-html', {
							method: 'POST',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify(convertBody),
						});

						if (!convertResponse.ok) {
							const errorText = await convertResponse.text();
							setStatusMessage(`Failed to convert: ${errorText}`);
							setStatusType('error');
							setIsLoading(false);
							return;
						}

						const convertData = await convertResponse.json();
						const templateId = convertData.template_id;

						if (templateId) {
							const elementorCommon = (window as any).elementorCommon;
							const $e = (window as any).$e;

							if (elementorCommon && elementorCommon.ajax && $e && $e.run) {
								elementorCommon.ajax.addRequest('get_template_data', {
									data: {
										source: 'local',
										edit_mode: true,
										display: true,
										template_id: templateId,
									},
									success: (templateData: any) => {
										$e.run('document/elements/import', {
											model: new (window as any).Backbone.Model({ title: 'Imported' }),
											data: templateData,
											options: { withPageSettings: false },
										});

										// TODO: Reactivate the template removal after testing
										// fetch(apiUrl + 'template/' + templateId, { method: 'DELETE' }).catch(() => {});

										setStatusMessage('Import completed successfully!');
										setStatusType('success');
										setIsLoading(false);
										setTimeout(() => {
											onClose();
											if ((window as any).elementor?.notifications) {
												(window as any).elementor.notifications.showToast({
													message: 'Website imported successfully.',
													type: 'success',
												});
											}
										}, 2000);
									},
									error: () => {
										setStatusMessage('Failed to load template data');
										setStatusType('error');
										setIsLoading(false);
									},
								});
							} else {
								setStatusMessage('Elementor editor not available');
								setStatusType('error');
								setIsLoading(false);
							}
						} else {
							setStatusMessage('Template created but failed to retrieve ID');
							setStatusType('error');
							setIsLoading(false);
						}
					} catch (error) {
						setStatusMessage('Failed to convert scraped content: ' + (error instanceof Error ? error.message : 'Unknown error'));
						setStatusType('error');
						setIsLoading(false);
					}
				}
			} catch (error) {
				clearInterval(interval);
				pollIntervalRef.current = null;
				const errMsg = error instanceof Error ? error.message : String(error);
				setStatusMessage(`Failed to fetch results: ${errMsg}`);
				setStatusType('error');
				setIsLoading(false);
			}
		}, pollInterval);

		pollIntervalRef.current = interval;
	};

	const stopPolling = () => {
		if (pollIntervalRef.current) {
			clearInterval(pollIntervalRef.current);
			pollIntervalRef.current = null;
		}
	};

	return {
		startPolling,
		stopPolling,
		pollIntervalRef,
	};
};
