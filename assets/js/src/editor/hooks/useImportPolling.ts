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

	const startPolling = (jobId: string) => {
		let attempts = 0;
		const maxAttempts = 60;
		const pollInterval = 3000;

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
				const response = await fetch(apiUrl + 'import-results/' + jobId);

				if (!response.ok) {
					return;
				}

				const data = await response.json();

				if (data.status === 'pending') {
					return;
				}

				if (data.status === 'complete' && data.data) {
					clearInterval(interval);
					pollIntervalRef.current = null;

					if (data.data.status === 'success') {
						setStatusMessage('Import completed successfully!');
						setStatusType('success');
						setIsLoading(false);
						setTimeout(() => {
							onClose();
							if ((window as any).elementor?.notifications) {
								(window as any).elementor.notifications.showToast({
									message: 'Website imported successfully. Check results.',
									type: 'success',
								});
							}
							if (postId && (window as any).elementor) {
								const elementor = (window as any).elementor;
								const $e = (window as any).$e;
								
								if (elementor.documents) {
									elementor.documents.invalidateCache(postId);
								}
								
								if ($e && $e.run) {
									$e.run('editor/documents/open', { id: postId }).then(() => {
										if (elementor.reloadPreview) {
											elementor.reloadPreview();
										}
									}).catch(() => {
										if (elementor.reloadPreview) {
											elementor.reloadPreview();
										} else {
											window.location.reload();
										}
									});
								} else if (elementor.reloadPreview) {
									elementor.reloadPreview();
								} else {
									window.location.reload();
								}
							}
						}, 2000);
					} else if (data.data.status === 'error') {
						setStatusMessage(data.data.error || 'Import failed');
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
