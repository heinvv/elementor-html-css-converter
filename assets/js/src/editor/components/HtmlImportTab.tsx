import { getReact } from '../utils/getReact';
import { getElementorUI } from '../utils/getElementorUI';
import { HtmlImportTabProps } from '../types/components';

export const HtmlImportTab = ({
	htmlContent,
	setHtmlContent,
	isLoading,
	statusMessage,
	statusType,
	onClose,
	onSubmit,
}: HtmlImportTabProps) => {
	const React = getReact();
	const ui = getElementorUI();

	if (!React || !ui) {
		return null;
	}

	const {
		DialogContent: DialogContentComponent,
		DialogActions: DialogActionsComponent,
		TextField: TextFieldComponent,
		Box: BoxComponent,
		Alert: AlertComponent,
		Button: ButtonComponent,
	} = ui;

	return (
		<>
			<DialogContentComponent
				sx={{
					padding: '20px',
					textAlign: 'start',
				}}
			>
				<TextFieldComponent
					fullWidth
					label="HTML Content"
					required
					multiline
					rows={12}
					value={htmlContent}
					onChange={(e: any) => setHtmlContent(e.target.value)}
					placeholder="Paste your HTML here..."
					disabled={isLoading}
				/>
				{statusMessage && (
					<BoxComponent sx={{ mt: 2 }}>
						<AlertComponent severity={statusType || 'info'}>
							{statusMessage}
						</AlertComponent>
					</BoxComponent>
				)}
			</DialogContentComponent>
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
					variant="contained"
					color="primary"
					disabled={isLoading || !htmlContent.trim()}
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
					{isLoading ? 'Importing...' : 'Import'}
				</ButtonComponent>
			</DialogActionsComponent>
		</>
	);
};
