document.addEventListener('DOMContentLoaded', () => {

    const modal = document.getElementById('exampleDialogDark');

    // The login modal isn't present when the user is already logged in.
    if (!modal) return;

    const usernameElement = document.getElementById('modalUsername');
    const usernameInput = document.getElementById('loginUsername');
    const pinInput = document.getElementById('loginPin');
    const pinDisplay = document.getElementById('pinDisplay');

    const loginForm = document.getElementById('loginForm');
    const loginError = document.getElementById('loginError');
    const loginLoading = document.getElementById('loginLoading');

    let pin = '';
    let submitting = false;

    // Update the PIN display.
    function updatePinDisplay() {

        pinDisplay.textContent = Array.from({ length: 4 }, (_, i) =>
            i < pin.length ? '●' : '○'
        ).join(' ');

        pinDisplay.setAttribute(
            'aria-label',
            `${pin.length} of 4 digits entered`
        );

        pinInput.value = pin;
    }

    // Reset the PIN.
    function resetPin() {
        pin = '';
        updatePinDisplay();
    }

    // Show an error.
    function showError(message) {
        loginError.textContent = message;
        loginError.classList.remove('d-none');
    }

    // Hide an error.
    function hideError() {
        loginError.classList.add('d-none');
    }

    // Handle user selection.
    document.querySelectorAll('[data-bs-target="#exampleDialogDark"]').forEach(button => {

        button.addEventListener('click', () => {

            const username = button.dataset.username;

            usernameElement.textContent = username;
            usernameInput.value = username;

            resetPin();
            hideError();

        });

    });

    // Submit the login.
    async function submitLogin() {

        if (pin.length !== 4 || submitting) return;

        submitting = true;

        hideError();
        loginLoading.classList.remove('d-none');

        try {

            const formData = new FormData(loginForm);

            const response = await fetch('api/login.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {

                showError(result.message || 'Incorrect PIN. Please try again.');
                resetPin();

                return;
            }

            // Successful login.
            window.location.href = result.redirect || 'index.php';

        } catch (error) {

            showError('Unable to log in. Please try again.');
            resetPin();

        } finally {

            submitting = false;
            loginLoading.classList.add('d-none');

            updatePinDisplay();

        }

    }

    // Number buttons.
    document.querySelectorAll('.pin-key').forEach(button => {

        button.addEventListener('click', () => {

            if (pin.length >= 4 || submitting) return;

            pin += button.dataset.digit;

            updatePinDisplay();
            hideError();

            // Automatically submit when the fourth digit is entered.
            if (pin.length === 4) {
                submitLogin();
            }

        });

    });

    // Delete the last digit.
    document.getElementById('deletePin').addEventListener('click', () => {

        if (submitting) return;

        pin = pin.slice(0, -1);

        hideError();
        updatePinDisplay();

    });

    // Clear the entire PIN.
    document.getElementById('clearPin').addEventListener('click', () => {

        if (submitting) return;

        resetPin();
        hideError();

    });

    // Prevent normal form submission.
    loginForm.addEventListener('submit', event => {

        event.preventDefault();
        submitLogin();

    });

    // Reset the PIN when the modal closes.
    modal.addEventListener('close', () => {

        resetPin();
        hideError();

    });

    // Initialise the display.
    updatePinDisplay();

});