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
	diagnostics,
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
						placeholder={`.hero, .card
[aria-label="Producten"], label:Zoeken
xpath://a[contains(text(),"Vacatures")]`}
						helperText={'Comma-separated. CSS: .hero, [aria-label="X"]. Shortcuts: label:Zoeken = [aria-label]. xpath://a[text()="Link"] for text/hierarchy.'}
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
				{diagnostics?.botDetection?.detected && statusType === 'error' && (
					<BoxComponent sx={{ mt: 2 }}>
						<AlertComponent 
							severity={diagnostics.botDetection.confidence === 'high' ? 'error' : 'warning'}
							sx={{ mb: 2 }}
						>
							<BoxComponent component="div" sx={{ fontWeight: 600, mb: 1 }}>
								Bot Protection Detected
								{diagnostics.botDetection.type && (
									<BoxComponent component="span" sx={{ ml: 1, fontWeight: 'normal', textTransform: 'capitalize' }}>
										({diagnostics.botDetection.type.replace(/-/g, ' ')})
									</BoxComponent>
								)}
							</BoxComponent>
							<BoxComponent component="div" sx={{ mb: 1, fontSize: '0.875rem' }}>
								{diagnostics.botDetection.suggestion}
							</BoxComponent>
							{diagnostics.botDetection.evidence.length > 0 && (
								<BoxComponent sx={{ mt: 1.5 }}>
									<BoxComponent component="div" sx={{ fontWeight: 600, fontSize: '0.875rem', mb: 0.5 }}>
										Evidence (confidence: {diagnostics.botDetection.confidence}):
									</BoxComponent>
									<BoxComponent component="ul" sx={{ m: 0, pl: 2.5, fontSize: '0.875rem' }}>
										{diagnostics.botDetection.evidence.map((item: string, idx: number) => (
											<BoxComponent component="li" key={idx} sx={{ mb: 0.5 }}>
												{item}
											</BoxComponent>
										))}
									</BoxComponent>
								</BoxComponent>
							)}
						</AlertComponent>
					</BoxComponent>
				)}
				{diagnostics && statusType === 'error' && (
					<BoxComponent
						sx={{
							mt: 2,
							p: 2,
							border: '1px solid',
							borderColor: 'error.main',
							borderRadius: 1,
							backgroundColor: 'action.hover',
							maxHeight: 400,
							overflow: 'auto',
							fontSize: '0.875rem',
						}}
					>
						<BoxComponent component="div" sx={{ mb: 2, fontWeight: 600 }}>
							{diagnostics.botDetection?.detected ? 'Technical Details' : 'Scrape diagnostics'}
						</BoxComponent>
						{diagnostics.pageTitle && (
							<BoxComponent sx={{ mb: 1 }}>
								<BoxComponent component="span" sx={{ fontWeight: 600 }}>Page title: </BoxComponent>
								<BoxComponent component="span">{diagnostics.pageTitle}</BoxComponent>
							</BoxComponent>
						)}
						{diagnostics.requestedUrl && (
							<BoxComponent sx={{ mb: 1 }}>
								<BoxComponent component="span" sx={{ fontWeight: 600 }}>Requested URL: </BoxComponent>
								<BoxComponent component="span" sx={{ wordBreak: 'break-all' }}>{diagnostics.requestedUrl}</BoxComponent>
							</BoxComponent>
						)}
						{diagnostics.finalUrl && diagnostics.finalUrl !== diagnostics.requestedUrl && (
							<BoxComponent 
								sx={{ 
									mb: 1, 
									p: 1, 
									bgcolor: 'warning.light', 
									borderRadius: 1,
									border: '1px solid',
									borderColor: 'warning.main',
								}}
							>
								<BoxComponent component="div" sx={{ fontWeight: 600, mb: 0.5, display: 'flex', alignItems: 'center' }}>
									<BoxComponent component="span" sx={{ mr: 0.5 }}>⚠️</BoxComponent>
									URL Redirect Detected
								</BoxComponent>
								<BoxComponent component="div" sx={{ fontSize: '0.875rem' }}>
									<BoxComponent component="span" sx={{ fontWeight: 600 }}>Final URL: </BoxComponent>
									<BoxComponent component="span" sx={{ wordBreak: 'break-all' }}>{diagnostics.finalUrl}</BoxComponent>
								</BoxComponent>
							</BoxComponent>
						)}
						{diagnostics.selectors?.length ? (
							<BoxComponent sx={{ mb: 1 }}>
								<BoxComponent component="span" sx={{ fontWeight: 600 }}>Selectors: </BoxComponent>
								<BoxComponent component="span">{diagnostics.selectors.join(', ')}</BoxComponent>
							</BoxComponent>
						) : null}
						{diagnostics.screenshotBase64 && (
							<BoxComponent sx={{ mb: 2 }}>
								<BoxComponent component="div" sx={{ fontWeight: 600, mb: 0.5 }}>Screenshot</BoxComponent>
								<BoxComponent
									component="img"
									src={`data:image/png;base64,${diagnostics.screenshotBase64}`}
									alt="Page screenshot"
									onError={(e: any) => {
										e.target.style.display = 'none';
										const parent = e.target.parentElement;
										if (parent) {
											const errorMsg = document.createElement('div');
											errorMsg.textContent = 'Screenshot failed to load';
											errorMsg.style.padding = '8px';
											errorMsg.style.backgroundColor = '#f5f5f5';
											errorMsg.style.border = '1px solid #ddd';
											errorMsg.style.borderRadius = '4px';
											errorMsg.style.color = '#666';
											parent.appendChild(errorMsg);
										}
									}}
									sx={{
										maxWidth: '100%',
										height: 'auto',
										border: '1px solid',
										borderColor: 'divider',
										borderRadius: 1,
										display: 'block',
										cursor: 'pointer',
										'&:hover': {
											opacity: 0.9,
										},
									}}
									onClick={(e: any) => {
										window.open(e.target.src, '_blank');
									}}
									title="Click to view full size in new tab"
								/>
							</BoxComponent>
						)}
						{diagnostics.htmlPreview && (
							<BoxComponent sx={{ mb: 2 }}>
								<BoxComponent component="div" sx={{ fontWeight: 600, mb: 0.5 }}>HTML preview (first 1000 chars)</BoxComponent>
								<BoxComponent
									component="pre"
									sx={{
										mt: 0.5,
										p: 1.5,
										bgcolor: 'grey.900',
										color: 'grey.100',
										fontSize: '0.75rem',
										overflow: 'auto',
										maxHeight: 150,
										m: 0,
										borderRadius: 1,
									}}
								>
									{diagnostics.htmlPreview}
								</BoxComponent>
							</BoxComponent>
						)}
						{diagnostics.consoleMessages?.length ? (
							<BoxComponent>
								<BoxComponent component="div" sx={{ fontWeight: 600, mb: 0.5 }}>Console messages ({diagnostics.consoleMessages.length})</BoxComponent>
								<BoxComponent
									component="pre"
									sx={{
										mt: 0.5,
										p: 1.5,
										bgcolor: 'grey.900',
										color: 'grey.100',
										fontSize: '0.75rem',
										overflow: 'auto',
										maxHeight: 100,
										m: 0,
										borderRadius: 1,
									}}
								>
									{diagnostics.consoleMessages.slice(0, 10).join('\n')}
								</BoxComponent>
							</BoxComponent>
						) : null}
					</BoxComponent>
				)}
			</form>
		</DialogContentComponent>
	);
};
