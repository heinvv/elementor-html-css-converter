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
						const templateId = data.data.results?.converter?.template_id;
						
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
							setStatusMessage('Import completed but no template ID found');
							setStatusType('error');
							setIsLoading(false);
						}
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
