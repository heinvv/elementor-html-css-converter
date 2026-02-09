import { getReact } from '../utils/getReact';
import { getElementorUI } from '../utils/getElementorUI';
import { ModalActionsProps } from '../types/components';

export const ModalActions = ({ onClose, onSubmit, isLoading }: ModalActionsProps) => {
	const React = getReact();
	const ui = getElementorUI();

	if (!React || !ui) {
		return null;
	}

	const { DialogActions: DialogActionsComponent, Button: ButtonComponent } = ui;

	return (
		<DialogActionsComponent
			sx={{
				padding: '10px 20px 20px 20px',
				gap: '15px',
				justifyContent: 'flex-end',
			}}
		>
			<ButtonComponent
				onClick={onClose}
				disabled={isLoading}
				variant="outlined"
				sx={{
					color: 'rgb(12, 13, 14)',
					borderColor: 'rgb(12, 13, 14)',
					'&:hover': {
						color: 'rgb(12, 13, 14)',
						borderColor: 'rgb(12, 13, 14)',
					},
				}}
			>
				Cancel
			</ButtonComponent>
			<ButtonComponent
				type="submit"
				form="ehcc-import-form-react"
				variant="contained"
				color="primary"
				disabled={isLoading}
				onClick={onSubmit}
				sx={{
					backgroundColor: '#F3BAFD',
					color: 'rgb(12, 13, 14)',
					'&:hover': {
						backgroundColor: '#F3BAFD',
						color: 'rgb(12, 13, 14)',
					},
				}}
			>
				{isLoading ? 'Starting...' : 'Start Import'}
			</ButtonComponent>
		</DialogActionsComponent>
	);
};
