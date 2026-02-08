import React from 'react';
import { getReact } from '../utils/getReact';
import { getElementorUI } from '../utils/getElementorUI';
import { useImportForm } from '../hooks/useImportForm';
import { useImportPolling } from '../hooks/useImportPolling';
import { useImportSubmit } from '../hooks/useImportSubmit';
import { ModalHeader } from './ModalHeader';
import { ModalContent } from './ModalContent';
import { ModalActions } from './ModalActions';
import { ImportModalProps } from '../types/components';

export const ImportModal = ({ isOpen, onClose, apiUrl, postId }: ImportModalProps) => {
	const ReactLib = getReact();
	if (!ReactLib) {
		return null;
	}

	const ui = getElementorUI();
	if (!ui) {
		return null;
	}

	const formState = useImportForm();
	if (!formState) {
		return null;
	}

	const {
		url,
		setUrl,
		selectors,
		setSelectors,
		timeout,
		setTimeout,
		isLoading,
		setIsLoading,
		statusMessage,
		setStatusMessage,
		statusType,
		setStatusType,
		resetForm,
	} = formState;

	const polling = useImportPolling({
		apiUrl,
		setStatusMessage,
		setStatusType,
		setIsLoading,
		onClose,
		isOpen,
	});

	if (!polling) {
		return null;
	}

	const { startPolling, stopPolling } = polling;

	const submit = useImportSubmit({
		url,
		selectors,
		timeout,
		apiUrl,
		postId,
		setIsLoading,
		setStatusMessage,
		setStatusType,
		startPolling,
	});

	const { handleSubmit } = submit;

	const handleClose = () => {
		stopPolling();
		resetForm();
		onClose();
	};

	if (!isOpen) {
		return null;
	}

	const { Dialog: DialogComponent } = ui;

	return (
		<DialogComponent
			open={isOpen}
			onClose={handleClose}
			maxWidth={false}
			fullWidth={true}
			sx={{
				'& .MuiDialog-paper': {
					maxWidth: '1200px',
					width: '100%',
					fontFamily: 'var(--e-a-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif)',
					backgroundColor: 'var(--e-a-bg-default, #fff)',
				},
			}}
		>
			<ModalHeader onClose={handleClose} />
			<ModalContent
				url={url}
				setUrl={setUrl}
				selectors={selectors}
				setSelectors={setSelectors}
				timeout={timeout}
				setTimeout={setTimeout}
				isLoading={isLoading}
				statusMessage={statusMessage}
				statusType={statusType}
				onSubmit={handleSubmit}
			/>
			<ModalActions
				onClose={handleClose}
				onSubmit={handleSubmit}
				isLoading={isLoading}
			/>
		</DialogComponent>
	);
};
