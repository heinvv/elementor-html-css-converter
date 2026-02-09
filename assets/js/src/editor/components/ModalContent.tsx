import { getReact } from '../utils/getReact';
import { getElementorUI } from '../utils/getElementorUI';
import { ModalContentProps } from '../types/components';

export const ModalContent = ({
	url,
	setUrl,
	selectors,
	setSelectors,
	timeout,
	setTimeout,
	isLoading,
	statusMessage,
	statusType,
	onSubmit,
}: ModalContentProps) => {
	const React = getReact();
	const ui = getElementorUI();

	if (!React || !ui) {
		return null;
	}

	const { DialogContent: DialogContentComponent, Stack: StackComponent, TextField: TextFieldComponent, Box: BoxComponent, Alert: AlertComponent } = ui;

	return (
		<DialogContentComponent
			sx={{
				padding: '20px',
				textAlign: 'start',
			}}
		>
			<form
				id="ehcc-import-form-react"
				onSubmit={onSubmit}
			>
				<StackComponent
					direction="column"
					spacing={2.5}
				>
					<TextFieldComponent
						fullWidth
						label="URL to Import"
						required
						type="url"
						value={url}
						onChange={(e: any) => setUrl(e.target.value)}
						placeholder="https://example.com/page"
						disabled={isLoading}
					/>
					<TextFieldComponent
						fullWidth
						label="CSS Selectors"
						required
						multiline
						rows={3}
						value={selectors}
						onChange={(e: any) => setSelectors(e.target.value)}
						placeholder=".hero, .card, #main-content"
						helperText="Comma-separated CSS selectors"
						disabled={isLoading}
					/>
					<TextFieldComponent
						fullWidth
						label="Timeout (seconds)"
						type="number"
						value={timeout}
						onChange={(e: any) => setTimeout(e.target.value)}
						inputProps={{ min: 10, max: 300 }}
						disabled={isLoading}
					/>
				</StackComponent>
				{statusMessage && (
					<BoxComponent sx={{ mt: 2 }}>
						<AlertComponent severity={statusType || 'info'}>
							{statusMessage}
						</AlertComponent>
					</BoxComponent>
				)}
			</form>
		</DialogContentComponent>
	);
};
