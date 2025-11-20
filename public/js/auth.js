document.addEventListener('DOMContentLoaded', () => {
    // Attach event listeners for auth forms
    document.getElementById('loginForm')?.addEventListener('submit', handleLogin);
    document.getElementById('registerForm')?.addEventListener('submit', handleRegister);
    document.getElementById('logout-link')?.addEventListener('click', handleLogout);

    // Attach OAuth button listeners
    document.querySelectorAll('.oauth-btn').forEach(btn => {
        btn.addEventListener('click', handleOAuthLogin);
    });

    // Check login status when the app loads
    checkLoginStatus();
    
    // Display OAuth error if present
    displayOAuthError();
});

/**
 * Checks if a user is currently logged in by checking the session.
 */
async function checkLoginStatus() {
    try {
        const response = await fetch(`${API_BASE}?action=check_status`);
        const data = await response.json();

        if (data.success && data.logged_in) {
            updateUIAfterLogin(data.user);
        } else {
            updateUIAfterLogout();
        }
    } catch (error) {
        console.error('Error checking login status:', error);
        updateUIAfterLogout();
    }
}

/**
 * Handles the login form submission.
 * @param {Event} e The form submission event.
 */
async function handleLogin(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    const statusDiv = document.getElementById('loginStatus');

    try {
        const response = await fetch(`${API_BASE}?action=login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            statusDiv.className = 'auth-status success';
            statusDiv.textContent = 'Login successful! Redirecting...';
            updateUIAfterLogin(result.user);
            // Redirect to gallery after a short delay
            setTimeout(() => {
                switchView('gallery');
                loadFolders(); // Reload folders for the logged-in user
                loadImages(); // Reload images for the logged-in user
            }, 1000);
        } else {
            statusDiv.className = 'auth-status error';
            statusDiv.textContent = result.error || 'An unknown error occurred.';
        }
    } catch (error) {
        console.error('Login error:', error);
        statusDiv.className = 'auth-status error';
        statusDiv.textContent = 'A network error occurred.';
    }
}

/**
 * Handles the registration form submission.
 * @param {Event} e The form submission event.
 */
async function handleRegister(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    const statusDiv = document.getElementById('registerStatus');

    if (data.password !== data.confirm_password) {
        statusDiv.className = 'auth-status error';
        statusDiv.textContent = 'Passwords do not match.';
        return;
    }

    try {
        const response = await fetch(`${API_BASE}?action=register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: data.username, password: data.password })
        });

        const result = await response.json();

        if (result.success) {
            statusDiv.className = 'auth-status success';
            statusDiv.textContent = 'Registration successful! Please log in.';
            // Switch to login view after successful registration
            setTimeout(() => switchView('login'), 1500);
        } else {
            statusDiv.className = 'auth-status error';
            statusDiv.textContent = result.error || 'An unknown error occurred.';
        }
    } catch (error) {
        console.error('Registration error:', error);
        statusDiv.className = 'auth-status error';
        statusDiv.textContent = 'A network error occurred.';
    }
}

/**
 * Handles user logout.
 */
async function handleLogout(e) {
    e.preventDefault();
    await fetch(`${API_BASE}?action=logout`, { method: 'POST' });
    // After logging out, show the public gallery view
    updateUIAfterLogout();
    loadFolders(); // Clear user-specific folders
    loadImages(); // Load public images
    switchView('gallery');
}

function updateUIAfterLogin(user) {
    document.body.classList.add('logged-in');
    document.body.classList.remove('logged-out');
    document.getElementById('username-display').textContent = user.username;
}

function updateUIAfterLogout() {
    document.body.classList.add('logged-out');
    document.body.classList.remove('logged-in');
    document.getElementById('username-display').textContent = '';
}

/**
 * Handle OAuth login button clicks
 */
function handleOAuthLogin(e) {
    e.preventDefault();
    const provider = e.currentTarget.dataset.provider;
    const returnUrl = encodeURIComponent(window.location.pathname);
    window.location.href = `oauth-login.php?provider=${provider}&return_url=${returnUrl}`;
}

/**
 * Display OAuth error message if present in session
 */
async function displayOAuthError() {
    try {
        const response = await fetch(`${API_BASE}?action=get_oauth_error`);
        const data = await response.json();
        
        if (data.error) {
            const statusDiv = document.getElementById('loginStatus');
            if (statusDiv) {
                statusDiv.className = 'auth-status error';
                statusDiv.textContent = data.error;
            }
        }
    } catch (error) {
        console.error('Error checking OAuth error:', error);
    }
}