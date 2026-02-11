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

		try {
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

			if (data.success && data.job_id) {
				setStatusMessage('Import started. Waiting for results...');
				setStatusType('info');
				startPolling(data.job_id);
			} else {
				setStatusMessage(data.message || 'Failed to start import');
				setStatusType('error');
				setIsLoading(false);
			}
		} catch (error) {
			setStatusMessage('Failed to start import: ' + (error instanceof Error ? error.message : 'Unknown error'));
			setStatusType('error');
			setIsLoading(false);
		}
	};

	return {
		handleSubmit,
	};
};
