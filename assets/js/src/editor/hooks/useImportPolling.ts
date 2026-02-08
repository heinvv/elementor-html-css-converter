import { getReact } from '../utils/getReact';
import { UseImportPollingParams } from '../types/hooks';

export const useImportPolling = ({
	apiUrl,
	setStatusMessage,
	setStatusType,
	setIsLoading,
	onClose,
	isOpen,
}: UseImportPollingParams) => {
	const ReactLib = getReact();
	if (!ReactLib) {
		return null;
	}

	const { useRef, useEffect } = ReactLib;
	const pollIntervalRef = useRef<NodeJS.Timeout | null>(null);

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

	const startPolling = (jobId: string) => {
		let attempts = 0;
		const maxAttempts = 60;
		const interval = setInterval(async () => {
			attempts++;

			if (attempts > maxAttempts) {
				clearInterval(interval);
				setStatusMessage('Timeout waiting for results');
				setStatusType('error');
				setIsLoading(false);
				return;
			}

			try {
				const response = await fetch(apiUrl + 'import-results/' + jobId);
				
				if (response.status === 404) {
					return;
				}

				if (!response.ok) {
					throw new Error(`HTTP ${response.status}`);
				}

				const data = await response.json();

				if (data.success && data.data) {
					if (data.data.status === 'success') {
						clearInterval(interval);
						setStatusMessage('Import completed successfully!');
						setStatusType('success');
						setIsLoading(false);
						setTimeout(() => {
							onClose();
							if (window.elementor?.notifications) {
								window.elementor.notifications.showToast({
									message: 'Website imported successfully. Check results.',
									type: 'success',
								});
							}
						}, 2000);
					} else if (data.data.status === 'error') {
						clearInterval(interval);
						setStatusMessage(data.data.error || 'Import failed');
						setStatusType('error');
						setIsLoading(false);
					}
				}
			} catch (error) {
				const errorStatus = (error as { status?: number }).status;
				if (errorStatus && errorStatus !== 404) {
					clearInterval(interval);
					setStatusMessage('Failed to fetch results');
					setStatusType('error');
					setIsLoading(false);
				}
			}
		}, 2000);

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
