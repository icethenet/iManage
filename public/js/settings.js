/**
 * Settings Page JavaScript
 * Handles user profile management, password changes, and account settings
 */

// Load settings when view is shown
async function loadSettings() {
    showLoading(true);
    
    try {
        // Load profile info
        const profileResponse = await fetch(`${API_BASE}?action=get_profile`);
        const profileData = await profileResponse.json();
        
        if (profileData.success) {
            const user = profileData.user;
            document.getElementById('settingsUsername').textContent = user.username || 'N/A';
            document.getElementById('settingsEmail').textContent = user.email || 'Not set';
            document.getElementById('settingsMemberSince').textContent = user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A';
            document.getElementById('settingsLastLogin').textContent = user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never';
            document.getElementById('settingsOAuthProvider').textContent = user.oauth_provider ? user.oauth_provider.charAt(0).toUpperCase() + user.oauth_provider.slice(1) : 'None';
            
            // Update current provider display
            const providerName = user.oauth_provider ? user.oauth_provider.charAt(0).toUpperCase() + user.oauth_provider.slice(1) : 'None';
            document.getElementById('currentProvider').textContent = providerName;
            
            // Pre-fill email field if exists
            if (user.email) {
                document.getElementById('newEmail').value = user.email;
            }
        }
        
        // Load account stats
        const statsResponse = await fetch(`${API_BASE}?action=get_account_stats`);
        const statsData = await statsResponse.json();
        
        if (statsData.success) {
            const stats = statsData.stats;
            document.getElementById('totalImages').textContent = stats.total_images || 0;
            document.getElementById('totalFolders').textContent = stats.total_folders || 0;
            document.getElementById('storageUsed').textContent = `${(stats.storage_used / 1024 / 1024).toFixed(2)} MB`;
            document.getElementById('sharedImages').textContent = stats.shared_images || 0;
        }
    } catch (error) {
        console.error('Error loading settings:', error);
    } finally {
        showLoading(false);
    }
}

// Handle email update
document.getElementById('updateEmailForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('newEmail').value.trim();
    const statusEl = document.getElementById('emailStatus');
    
    if (!email) {
        statusEl.textContent = 'Please enter an email address';
        statusEl.className = 'form-status error';
        return;
    }
    
    showLoading(true);
    
    try {
        const response = await fetch(`${API_BASE}?action=update_email`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            statusEl.textContent = 'Email updated successfully';
            statusEl.className = 'form-status success';
            document.getElementById('settingsEmail').textContent = email;
        } else {
            statusEl.textContent = data.error || 'Failed to update email';
            statusEl.className = 'form-status error';
        }
    } catch (error) {
        statusEl.textContent = 'Error updating email';
        statusEl.className = 'form-status error';
        console.error('Error:', error);
    } finally {
        showLoading(false);
    }
});

// Handle password change
document.getElementById('changePasswordForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmNewPassword').value;
    const statusEl = document.getElementById('passwordStatus');
    
    if (!currentPassword || !newPassword || !confirmPassword) {
        statusEl.textContent = 'All fields are required';
        statusEl.className = 'form-status error';
        return;
    }
    
    if (newPassword !== confirmPassword) {
        statusEl.textContent = 'New passwords do not match';
        statusEl.className = 'form-status error';
        return;
    }
    
    if (newPassword.length < 8) {
        statusEl.textContent = 'Password must be at least 8 characters';
        statusEl.className = 'form-status error';
        return;
    }
    
    showLoading(true);
    
    try {
        const response = await fetch(`${API_BASE}?action=change_password`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            statusEl.textContent = 'Password changed successfully';
            statusEl.className = 'form-status success';
            // Clear form
            document.getElementById('changePasswordForm').reset();
        } else {
            statusEl.textContent = data.error || 'Failed to change password';
            statusEl.className = 'form-status error';
        }
    } catch (error) {
        statusEl.textContent = 'Error changing password';
        statusEl.className = 'form-status error';
        console.error('Error:', error);
    } finally {
        showLoading(false);
    }
});

// Handle account deletion
document.getElementById('deleteAccountBtn')?.addEventListener('click', async function() {
    const confirmed = confirm('Are you sure you want to delete your account? This action cannot be undone and will permanently delete all your images and data.');
    
    if (!confirmed) return;
    
    const doubleConfirm = confirm('This is your last warning! All your data will be permanently deleted. Are you absolutely sure?');
    
    if (!doubleConfirm) return;
    
    showLoading(true);
    
    try {
        const response = await fetch(`${API_BASE}?action=delete_account`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Your account has been deleted. You will now be logged out.');
            window.location.href = 'index.php';
        } else {
            alert('Error deleting account: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error deleting account');
        console.error('Error:', error);
    } finally {
        showLoading(false);
    }
});

// Handle OAuth provider connections in settings
document.addEventListener('DOMContentLoaded', function() {
    const oauthButtons = document.querySelectorAll('.settings-section .oauth-btn');
    
    oauthButtons.forEach(button => {
        button.addEventListener('click', function() {
            const provider = this.dataset.provider;
            
            if (confirm(`Connect your ${provider.charAt(0).toUpperCase() + provider.slice(1)} account? This will allow you to sign in using this provider.`)) {
                // Redirect to OAuth login
                window.location.href = `oauth-login.php?provider=${provider}`;
            }
        });
    });
});
