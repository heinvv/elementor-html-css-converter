import { getReact } from '../utils/getReact';
import { UseImportPollingParams } from '../types/hooks';

export const useImportPolling = ({
	apiUrl,
	setStatusMessage,
	setStatusType,
	setIsLoading,
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
			setIsLoading(false);
		}
	}, [isOpen, setStatusMessage, setStatusType, setIsLoading]);

	const startPolling = (jobId: string, scraperEndpoint: string) => {
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
					return;
				}

				const data = await response.json();

				if (data.status === 'pending') {
					return;
				}

				if (data.status === 'error') {
					clearInterval(interval);
					pollIntervalRef.current = null;
					setStatusMessage(data.error || 'Import failed');
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
						setStatusMessage(
							'No elements matched the selectors. Check the URL and CSS selectors. ' +
								'Try inspecting the page to verify the selector exists, or use a broader selector (e.g. header, main).'
						);
						setStatusType('error');
						setIsLoading(false);
						return;
					}

					setStatusMessage('Scrape complete. Converting to Elementor...');
					setStatusType('info');

					try {
						const convertResponse = await fetch(apiUrl + 'convert-html', {
							method: 'POST',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify({
								html: data.results.html,
								save_as_template: true,
								import_variables: true,
								import_classes: false,
							}),
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

										fetch(apiUrl + 'template/' + templateId, { method: 'DELETE' }).catch(() => {
										});

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
				setStatusMessage('Failed to fetch results');
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
