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
            document.getElementById('profileUsername').textContent = user.username || 'N/A';
            document.getElementById('profileEmail').textContent = user.email || 'Not set';
            document.getElementById('profileMemberSince').textContent = user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A';
            document.getElementById('profileLastLogin').textContent = user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never';
            document.getElementById('profileProvider').textContent = user.oauth_provider ? user.oauth_provider.charAt(0).toUpperCase() + user.oauth_provider.slice(1) : 'None';
            
            // Update current provider display if element exists
            const currentProviderEl = document.getElementById('currentProvider');
            if (currentProviderEl) {
                const providerName = user.oauth_provider ? user.oauth_provider.charAt(0).toUpperCase() + user.oauth_provider.slice(1) : 'None';
                currentProviderEl.textContent = providerName;
            }
            
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
            document.getElementById('statImages').textContent = stats.total_images || 0;
            document.getElementById('statFolders').textContent = stats.total_folders || 0;
            document.getElementById('statStorage').textContent = `${(stats.storage_used / 1024 / 1024).toFixed(2)} MB`;
            document.getElementById('statShared').textContent = stats.shared_images || 0;
        }
        
        // Load 2FA status
        if (typeof load2FAStatus === 'function') {
            load2FAStatus();
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
    
    if (!email) {
        alert('Please enter an email address');
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
            alert('Email updated successfully');
            document.getElementById('profileEmail').textContent = email;
        } else {
            alert(data.error || 'Failed to update email');
        }
    } catch (error) {
        alert('Error updating email');
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
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (!currentPassword || !newPassword || !confirmPassword) {
        alert('All fields are required');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        alert('New passwords do not match');
        return;
    }
    
    if (newPassword.length < 8) {
        alert('Password must be at least 8 characters');
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
            alert('Password changed successfully');
            // Clear form
            document.getElementById('changePasswordForm').reset();
        } else {
            alert(data.error || 'Failed to change password');
        }
    } catch (error) {
        alert('Error changing password');
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
