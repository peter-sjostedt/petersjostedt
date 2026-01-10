<?php
/**
 * Exempel på hur du inkluderar Modal-systemet i ditt ramverk
 */
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Demo</title>
    
    <!-- Inkludera Modal CSS -->
    <link rel="stylesheet" href="/assets/css/modal.css">
</head>
<body>

    <h1>Modal System Demo</h1>

    <button onclick="showInfo()">Info</button>
    <button onclick="showSuccess()">Success</button>
    <button onclick="showWarning()">Warning</button>
    <button onclick="showError()">Error</button>
    <button onclick="showConfirm()">Confirm</button>
    <button onclick="showPrompt()">Prompt</button>
    <button onclick="showLoading()">Loading</button>
    <button onclick="deleteUser(123)">Radera användare</button>

    <!-- Inkludera Modal JS (före </body>) -->
    <script src="/assets/js/modal.js"></script>
    
    <script>
        // Enkla modaler
        function showInfo() {
            Modal.info('Information', 'Detta är ett informationsmeddelande.');
        }

        function showSuccess() {
            Modal.success('Sparat!', 'Dina ändringar har sparats.');
        }

        function showWarning() {
            Modal.warning('Varning', 'Kontrollera uppgifterna innan du fortsätter.');
        }

        function showError() {
            Modal.error('Fel uppstod', 'Kunde inte spara. Försök igen senare.');
        }

        // Confirm med async/await
        async function showConfirm() {
            const confirmed = await Modal.confirm(
                'Bekräfta', 
                'Vill du verkligen fortsätta?'
            );
            
            if (confirmed) {
                Modal.success('Bekräftat', 'Du valde att fortsätta.');
            }
        }

        // Prompt för användarinput
        async function showPrompt() {
            const name = await Modal.prompt(
                'Ange namn',
                'Vad heter du?',
                { placeholder: 'Ditt namn...', inputValue: '' }
            );
            
            if (name) {
                Modal.success('Hej!', `Välkommen ${name}!`);
            }
        }

        // Loading med simulerad async operation
        async function showLoading() {
            Modal.loading('Laddar...', 'Hämtar data från servern...');
            
            // Simulera API-anrop
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            Modal.close();
            Modal.success('Klart!', 'Data har laddats.');
        }

        // Praktiskt exempel: Radera användare
        async function deleteUser(userId) {
            const confirmed = await Modal.confirm(
                'Radera användare',
                'Är du säker på att du vill radera denna användare? Detta kan inte ångras.',
                {
                    confirmText: 'Radera',
                    cancelText: 'Avbryt',
                    danger: true
                }
            );

            if (!confirmed) return;

            Modal.loading('Raderar...', 'Tar bort användaren...');

            try {
                const response = await fetch('/api/users/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: userId })
                });

                const data = await response.json();
                Modal.close();

                if (data.success) {
                    Modal.success('Raderad', 'Användaren har tagits bort.');
                } else {
                    Modal.error('Fel', data.message || 'Kunde inte radera användaren.');
                }
            } catch (error) {
                Modal.close();
                Modal.error('Fel', 'Ett oväntat fel uppstod. Försök igen.');
            }
        }

        // Exempel: Form-validering med modal
        async function submitForm(form) {
            const email = form.querySelector('[name="email"]').value;
            
            if (!email.includes('@')) {
                await Modal.warning('Ogiltig e-post', 'Ange en giltig e-postadress.');
                return false;
            }

            const confirmed = await Modal.confirm(
                'Skicka formulär',
                `Skicka till ${email}?`
            );

            if (confirmed) {
                Modal.loading('Skickar...', 'Vänligen vänta...');
                // ... submit logic
            }

            return confirmed;
        }
    </script>
</body>
</html>