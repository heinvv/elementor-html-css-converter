import { UseImportSubmitParams } from '../types/hooks';

export const useImportSubmit = ({
	url,
	selectors,
	timeout,
	apiUrl,
	postId,
	setIsLoading,
	setStatusMessage,
	setStatusType,
	startPolling,
}: UseImportSubmitParams) => {
	const handleSubmit = async (e: any) => {
		e.preventDefault();

		if (!url || !selectors) {
			setStatusMessage('Please fill in all required fields');
			setStatusType('error');
			return;
		}

		setIsLoading(true);
		setStatusMessage(null);
		setStatusType(null);

		const timings: Record<string, number> = {};
		timings._start = performance.now();
		let t0 = performance.now();

		try {
			t0 = performance.now();
			const response = await fetch(apiUrl + 'trigger-import', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					url: url.trim(),
					selectors: selectors.trim(),
					timeout: timeout || '60',
					post_id: postId || null,
				}),
			});

			const data = await response.json();
			timings.trigger_import_ms = Math.round(performance.now() - t0);

			if (data.success && data.job_id && data.scraper_endpoint) {
				setStatusMessage('Import started. Waiting for results...');
				setStatusType('info');
				startPolling(data.job_id, data.scraper_endpoint, url?.trim(), selectors?.trim(), timings);
			} else {
				setStatusMessage(
					!data.scraper_endpoint
						? 'Invalid scraper response. Please update the plugin.'
						: data.message || 'Failed to start import'
				);
				setStatusType('error');
				setIsLoading(false);
			}
		} catch (error) {
			timings.trigger_import_ms = Math.round(performance.now() - t0);
			delete timings._start;
			if (Object.keys(timings).length > 0) {
				console.table(timings);
			}
			setStatusMessage('Failed to start import: ' + (error instanceof Error ? error.message : 'Unknown error'));
			setStatusType('error');
			setIsLoading(false);
		}
	};

	return {
		handleSubmit,
	};
};
