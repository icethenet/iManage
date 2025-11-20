/**
 * Admin Dashboard JavaScript
 */

// Switch admin tab
function switchAdminTab(tabName) {
    console.log('switchAdminTab called with:', tabName);
    
    // Update tab buttons
    document.querySelectorAll('.admin-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.admin-tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`admin-tab-${tabName}`).classList.add('active');
    
    // Load tab-specific data
    if (tabName === 'analytics' && typeof loadAnalytics === 'function') {
        console.log('Loading analytics...');
        loadAnalytics();
    } else if (tabName === 'users') {
        console.log('Loading users list...');
        loadUsersList();
    } else if (tabName === 'security' && typeof loadActiveSessions === 'function') {
        console.log('Loading security/sessions...');
        loadActiveSessions();
    } else if (tabName === 'storage' && typeof analyzeStorage === 'function') {
        console.log('Storage tab activated');
        // Storage tab loads on demand via buttons
    } else if (tabName === 'oauth') {
        console.log('Checking OAuth providers...');
        checkOAuthProviders();
    } else if (tabName === 'logs') {
        console.log('Loading activity log...');
        loadActivityLog();
    }
}

// Load admin dashboard data
async function loadAdmin() {
    try {
        // Load system statistics
        const statsResponse = await fetch('api.php?action=admin_stats');
        const statsData = await statsResponse.json();
        
        if (statsData.success) {
            document.getElementById('adminTotalUsers').textContent = statsData.data.total_users || '0';
            document.getElementById('adminTotalImages').textContent = statsData.data.total_images || '0';
            const totalStorageMB = ((statsData.data.total_storage || 0) / (1024 * 1024)).toFixed(2);
            document.getElementById('adminTotalStorage').textContent = totalStorageMB + ' MB';
            document.getElementById('adminTotalFolders').textContent = statsData.data.total_folders || '0';
        }

        // Load analytics dashboard
        if (typeof loadAnalytics === 'function') {
            loadAnalytics();
        }

        // Load system health
        if (typeof loadSystemHealth === 'function') {
            loadSystemHealth();
        }

        // Load users list
        await loadUsersList();

        // Check OAuth provider status
        checkOAuthProviders();

        // Load activity log
        loadActivityLog();
        
        // Load security overview (from admin-security.js)
        if (typeof loadActiveSessions === 'function') {
            loadActiveSessions();
        }
        
        // Load storage overview (from admin-security.js)
        if (typeof updateStorageOverview === 'function') {
            updateStorageOverview(null); // Load with mock data initially
        }
        
    } catch (error) {
        console.error('Error loading admin dashboard:', error);
    }
}

// Load users list
async function loadUsersList() {
    try {
        const response = await fetch('api.php?action=admin_users');
        const data = await response.json();
        
        const usersList = document.getElementById('usersList');
        
        if (data.success && data.data && data.data.length > 0) {
            let html = '<table class="users-table">';
            html += '<thead><tr><th>Username</th><th>Email</th><th>OAuth</th><th>Joined</th><th>Images</th><th>Storage</th><th>Actions</th></tr></thead>';
            html += '<tbody>';
            
            data.data.forEach(user => {
                const storageMB = ((user.total_storage || 0) / (1024 * 1024)).toFixed(2);
                const joinedDate = new Date(user.created_at).toLocaleDateString();
                const oauthProvider = user.oauth_provider ? `<span style="background: #e3f2fd; padding: 2px 8px; border-radius: 3px; font-size: 11px;">${escapeHtml(user.oauth_provider)}</span>` : '-';
                const isAdmin = user.username === 'admin';
                
                html += `<tr>
                    <td><strong>${escapeHtml(user.username)}</strong>${isAdmin ? ' <span style="background: #ffc107; color: #333; padding: 2px 8px; border-radius: 3px; font-size: 11px;">ADMIN</span>' : ''}</td>
                    <td>${escapeHtml(user.email || 'N/A')}</td>
                    <td>${oauthProvider}</td>
                    <td>${joinedDate}</td>
                    <td>${user.image_count || 0}</td>
                    <td>${storageMB} MB</td>
                    <td>
                        <button class="btn btn-sm" onclick="viewUserDetails(${user.id}, '${escapeHtml(user.username)}')">👁️ View</button>
                        ${!isAdmin ? `<button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id}, '${escapeHtml(user.username)}')">🗑️ Delete</button>` : ''}
                    </td>
                </tr>`;
            });
            
            html += '</tbody></table>';
            usersList.innerHTML = html;
        } else {
            usersList.innerHTML = '<p style="color: #666;">No users found.</p>';
        }
    } catch (error) {
        console.error('Error loading users:', error);
        document.getElementById('usersList').innerHTML = '<p style="color: #dc3545;">Failed to load users.</p>';
    }
}

// User search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.users-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});

// Check OAuth provider configuration
async function checkOAuthProviders() {
    try {
        const response = await fetch('api.php?action=oauth_status');
        const data = await response.json();
        
        if (data.success) {
            const providers = data.data;
            
            // Update status for each provider
            ['google', 'facebook', 'github', 'microsoft'].forEach(provider => {
                const statusEl = document.getElementById(`${provider}Status`);
                if (statusEl) {
                    const isConfigured = providers[provider] === true;
                    statusEl.textContent = isConfigured ? '✓ Configured' : '✗ Not Configured';
                    statusEl.className = 'provider-status ' + (isConfigured ? 'status-enabled' : 'status-disabled');
                }
            });
        }
    } catch (error) {
        console.error('Error checking OAuth providers:', error);
    }
}

// Test OAuth provider connection
async function testOAuthProvider(provider) {
    try {
        const response = await fetch(`api.php?action=test_oauth_provider&provider=${provider}`);
        const data = await response.json();
        
        if (data.success) {
            alert(`✓ ${provider.charAt(0).toUpperCase() + provider.slice(1)} OAuth is configured correctly!`);
        } else {
            alert(`✗ ${provider.charAt(0).toUpperCase() + provider.slice(1)} OAuth test failed:\n${data.error || 'Unknown error'}`);
        }
    } catch (error) {
        console.error('Error testing OAuth provider:', error);
        alert('Failed to test OAuth provider');
    }
}

// View OAuth config
function viewOAuthConfig(provider) {
    const configPath = 'config/oauth.php';
    alert(`OAuth Configuration for ${provider.charAt(0).toUpperCase() + provider.slice(1)}:\n\nEdit the file: ${configPath}\n\nYou'll need to add:\n- Client ID\n- Client Secret\n- Redirect URI\n\nSee config/oauth.php.example for reference.`);
}

// View user details
function viewUserDetails(userId, username) {
    // Create a modal or detailed view
    const modal = confirm(`View details for user: ${username}?\n\nUser ID: ${userId}\n\nThis feature can be expanded to show:\n- Full user information\n- Image gallery\n- Upload history\n- Activity log\n- Edit user details\n\nWould you like to implement this feature?`);
    
    if (modal) {
        console.log('User details view requested for:', userId, username);
        // TODO: Implement detailed user view
    }
}

// Delete user
async function deleteUser(userId, username) {
    if (!confirm(`⚠️ Delete user "${username}"?\n\nThis will permanently delete:\n- User account\n- All images (${await getUserImageCount(userId)})\n- All folders\n- All user data\n\nThis action CANNOT be undone!`)) {
        return;
    }
    
    if (!confirm(`FINAL CONFIRMATION\n\nType YES to delete "${username}"?\n\n(Click OK to proceed, Cancel to abort)`)) {
        return;
    }
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'admin_delete_user',
                user_id: userId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`✓ User "${username}" has been deleted successfully.`);
            loadUsersList(); // Reload the list
            loadAdmin(); // Update stats
        } else {
            alert('✗ Failed to delete user: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        alert('✗ Failed to delete user');
    }
}

// Get user image count (helper)
async function getUserImageCount(userId) {
    try {
        const response = await fetch('api.php?action=admin_users');
        const data = await response.json();
        if (data.success) {
            const user = data.data.find(u => u.id === userId);
            return user ? user.image_count : 0;
        }
    } catch (error) {
        console.error('Error getting user image count:', error);
    }
    return 0;
}

// Export users to CSV
function exportUsers() {
    fetch('api.php?action=admin_users')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                let csv = 'Username,Email,OAuth Provider,Joined,Images,Storage (MB)\n';
                
                data.data.forEach(user => {
                    const storageMB = ((user.total_storage || 0) / (1024 * 1024)).toFixed(2);
                    csv += `"${user.username}","${user.email || 'N/A'}","${user.oauth_provider || 'None'}","${user.created_at}",${user.image_count || 0},${storageMB}\n`;
                });
                
                // Download CSV
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `users_export_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                alert('✓ Users exported successfully!');
            }
        })
        .catch(error => {
            console.error('Error exporting users:', error);
            alert('✗ Failed to export users');
        });
}

// Save system settings
function saveSystemSettings() {
    const settings = {
        // Upload Settings
        maxUploadSize: document.getElementById('maxUploadSize').value,
        allowedFormats: document.getElementById('allowedFormats').value,
        maxImagesPerUser: document.getElementById('maxImagesPerUser').value,
        maxStoragePerUser: document.getElementById('maxStoragePerUser').value,
        
        // Image Processing
        thumbnailSize: document.getElementById('thumbnailSize').value,
        thumbnailQuality: document.getElementById('thumbnailQuality').value,
        pristineQuality: document.getElementById('pristineQuality').value,
        autoRotate: document.getElementById('autoRotate').checked,
        stripExif: document.getElementById('stripExif').checked,
        generateWebP: document.getElementById('generateWebP').checked,
        
        // Security & Access
        enableRegistration: document.getElementById('enableRegistration').checked,
        enableOAuth: document.getElementById('enableOAuth').checked,
        requireEmailVerification: document.getElementById('requireEmailVerification').checked,
        sessionTimeout: document.getElementById('sessionTimeout').value,
        minPasswordLength: document.getElementById('minPasswordLength').value,
        maxLoginAttempts: document.getElementById('maxLoginAttempts').value,
        
        // Sharing & Visibility
        enableSharing: document.getElementById('enableSharing').checked,
        enablePublicGallery: document.getElementById('enablePublicGallery').checked,
        defaultImageVisibility: document.getElementById('defaultImageVisibility').value,
        shareLinkExpiration: document.getElementById('shareLinkExpiration').value,
        
        // Performance & Cache
        enableCache: document.getElementById('enableCache').checked,
        cacheExpiration: document.getElementById('cacheExpiration').value,
        imagesPerPage: document.getElementById('imagesPerPage').value,
        enableLazyLoad: document.getElementById('enableLazyLoad').checked,
        
        // Email Configuration
        enableEmail: document.getElementById('enableEmail').checked,
        smtpHost: document.getElementById('smtpHost').value,
        smtpPort: document.getElementById('smtpPort').value,
        smtpUsername: document.getElementById('smtpUsername').value,
        smtpPassword: document.getElementById('smtpPassword').value,
        emailFrom: document.getElementById('emailFrom').value,
        
        // Maintenance & Cleanup
        enableMaintenance: document.getElementById('enableMaintenance').checked,
        autoCleanup: document.getElementById('autoCleanup').checked,
        cleanupInterval: document.getElementById('cleanupInterval').value,
        logRetention: document.getElementById('logRetention').value
    };
    
    // Save to backend
    fetch('api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'save_system_settings',
            settings: settings
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Settings saved successfully!');
        } else {
            alert('✗ Failed to save settings: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error saving settings:', error);
        // Save to localStorage as fallback
        localStorage.setItem('systemSettings', JSON.stringify(settings));
        alert('✓ Settings saved locally!\n\n(Note: Backend implementation pending)');
    });
}

// Reset system settings to defaults
function resetSystemSettings() {
    if (!confirm('⚠️ Reset all settings to default values?\n\nThis will:\n- Restore default configuration\n- Remove all custom settings\n- Require saving to apply changes\n\nContinue?')) {
        return;
    }
    
    // Upload Settings
    document.getElementById('maxUploadSize').value = 10;
    document.getElementById('allowedFormats').value = 'jpg, jpeg, png, gif, webp';
    document.getElementById('maxImagesPerUser').value = 1000;
    document.getElementById('maxStoragePerUser').value = 5;
    
    // Image Processing
    document.getElementById('thumbnailSize').value = 300;
    document.getElementById('thumbnailQuality').value = 85;
    document.getElementById('pristineQuality').value = 95;
    document.getElementById('autoRotate').checked = true;
    document.getElementById('stripExif').checked = false;
    document.getElementById('generateWebP').checked = false;
    
    // Security & Access
    document.getElementById('enableRegistration').checked = true;
    document.getElementById('enableOAuth').checked = true;
    document.getElementById('requireEmailVerification').checked = false;
    document.getElementById('sessionTimeout').value = 1440;
    document.getElementById('minPasswordLength').value = 8;
    document.getElementById('maxLoginAttempts').value = 5;
    
    // Sharing & Visibility
    document.getElementById('enableSharing').checked = true;
    document.getElementById('enablePublicGallery').checked = true;
    document.getElementById('defaultImageVisibility').value = 'public';
    document.getElementById('shareLinkExpiration').value = 0;
    
    // Performance & Cache
    document.getElementById('enableCache').checked = true;
    document.getElementById('cacheExpiration').value = 24;
    document.getElementById('imagesPerPage').value = 20;
    document.getElementById('enableLazyLoad').checked = true;
    
    // Email Configuration
    document.getElementById('enableEmail').checked = false;
    document.getElementById('smtpHost').value = '';
    document.getElementById('smtpPort').value = 587;
    document.getElementById('smtpUsername').value = '';
    document.getElementById('smtpPassword').value = '';
    document.getElementById('emailFrom').value = '';
    
    // Maintenance & Cleanup
    document.getElementById('enableMaintenance').checked = false;
    document.getElementById('autoCleanup').checked = true;
    document.getElementById('cleanupInterval').value = 30;
    document.getElementById('logRetention').value = 90;
    
    alert('✓ Settings reset to defaults!\n\nDon\'t forget to click "Save All Settings" to apply changes.');
}

// Export system settings to JSON file
function exportSystemSettings() {
    const settings = {
        exported_at: new Date().toISOString(),
        version: '1.0',
        settings: {
            upload: {
                maxUploadSize: document.getElementById('maxUploadSize').value,
                allowedFormats: document.getElementById('allowedFormats').value,
                maxImagesPerUser: document.getElementById('maxImagesPerUser').value,
                maxStoragePerUser: document.getElementById('maxStoragePerUser').value
            },
            imageProcessing: {
                thumbnailSize: document.getElementById('thumbnailSize').value,
                thumbnailQuality: document.getElementById('thumbnailQuality').value,
                pristineQuality: document.getElementById('pristineQuality').value,
                autoRotate: document.getElementById('autoRotate').checked,
                stripExif: document.getElementById('stripExif').checked,
                generateWebP: document.getElementById('generateWebP').checked
            },
            security: {
                enableRegistration: document.getElementById('enableRegistration').checked,
                enableOAuth: document.getElementById('enableOAuth').checked,
                requireEmailVerification: document.getElementById('requireEmailVerification').checked,
                sessionTimeout: document.getElementById('sessionTimeout').value,
                minPasswordLength: document.getElementById('minPasswordLength').value,
                maxLoginAttempts: document.getElementById('maxLoginAttempts').value
            },
            sharing: {
                enableSharing: document.getElementById('enableSharing').checked,
                enablePublicGallery: document.getElementById('enablePublicGallery').checked,
                defaultImageVisibility: document.getElementById('defaultImageVisibility').value,
                shareLinkExpiration: document.getElementById('shareLinkExpiration').value
            },
            performance: {
                enableCache: document.getElementById('enableCache').checked,
                cacheExpiration: document.getElementById('cacheExpiration').value,
                imagesPerPage: document.getElementById('imagesPerPage').value,
                enableLazyLoad: document.getElementById('enableLazyLoad').checked
            },
            email: {
                enableEmail: document.getElementById('enableEmail').checked,
                smtpHost: document.getElementById('smtpHost').value,
                smtpPort: document.getElementById('smtpPort').value,
                smtpUsername: document.getElementById('smtpUsername').value,
                emailFrom: document.getElementById('emailFrom').value
            },
            maintenance: {
                enableMaintenance: document.getElementById('enableMaintenance').checked,
                autoCleanup: document.getElementById('autoCleanup').checked,
                cleanupInterval: document.getElementById('cleanupInterval').value,
                logRetention: document.getElementById('logRetention').value
            }
        }
    };
    
    // Create and download JSON file
    const blob = new Blob([JSON.stringify(settings, null, 2)], { type: 'application/json' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `system_settings_${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    alert('✓ Settings exported successfully!');
}

// Import system settings from JSON file
function importSystemSettings() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'application/json';
    
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(event) {
            try {
                const data = JSON.parse(event.target.result);
                
                if (!data.settings) {
                    throw new Error('Invalid settings file format');
                }
                
                const s = data.settings;
                
                // Upload Settings
                if (s.upload) {
                    document.getElementById('maxUploadSize').value = s.upload.maxUploadSize || 10;
                    document.getElementById('allowedFormats').value = s.upload.allowedFormats || 'jpg, jpeg, png, gif, webp';
                    document.getElementById('maxImagesPerUser').value = s.upload.maxImagesPerUser || 1000;
                    document.getElementById('maxStoragePerUser').value = s.upload.maxStoragePerUser || 5;
                }
                
                // Image Processing
                if (s.imageProcessing) {
                    document.getElementById('thumbnailSize').value = s.imageProcessing.thumbnailSize || 300;
                    document.getElementById('thumbnailQuality').value = s.imageProcessing.thumbnailQuality || 85;
                    document.getElementById('pristineQuality').value = s.imageProcessing.pristineQuality || 95;
                    document.getElementById('autoRotate').checked = s.imageProcessing.autoRotate !== false;
                    document.getElementById('stripExif').checked = s.imageProcessing.stripExif === true;
                    document.getElementById('generateWebP').checked = s.imageProcessing.generateWebP === true;
                }
                
                // Security
                if (s.security) {
                    document.getElementById('enableRegistration').checked = s.security.enableRegistration !== false;
                    document.getElementById('enableOAuth').checked = s.security.enableOAuth !== false;
                    document.getElementById('requireEmailVerification').checked = s.security.requireEmailVerification === true;
                    document.getElementById('sessionTimeout').value = s.security.sessionTimeout || 1440;
                    document.getElementById('minPasswordLength').value = s.security.minPasswordLength || 8;
                    document.getElementById('maxLoginAttempts').value = s.security.maxLoginAttempts || 5;
                }
                
                // Sharing
                if (s.sharing) {
                    document.getElementById('enableSharing').checked = s.sharing.enableSharing !== false;
                    document.getElementById('enablePublicGallery').checked = s.sharing.enablePublicGallery !== false;
                    document.getElementById('defaultImageVisibility').value = s.sharing.defaultImageVisibility || 'public';
                    document.getElementById('shareLinkExpiration').value = s.sharing.shareLinkExpiration || 0;
                }
                
                // Performance
                if (s.performance) {
                    document.getElementById('enableCache').checked = s.performance.enableCache !== false;
                    document.getElementById('cacheExpiration').value = s.performance.cacheExpiration || 24;
                    document.getElementById('imagesPerPage').value = s.performance.imagesPerPage || 20;
                    document.getElementById('enableLazyLoad').checked = s.performance.enableLazyLoad !== false;
                }
                
                // Email
                if (s.email) {
                    document.getElementById('enableEmail').checked = s.email.enableEmail === true;
                    document.getElementById('smtpHost').value = s.email.smtpHost || '';
                    document.getElementById('smtpPort').value = s.email.smtpPort || 587;
                    document.getElementById('smtpUsername').value = s.email.smtpUsername || '';
                    document.getElementById('emailFrom').value = s.email.emailFrom || '';
                }
                
                // Maintenance
                if (s.maintenance) {
                    document.getElementById('enableMaintenance').checked = s.maintenance.enableMaintenance === true;
                    document.getElementById('autoCleanup').checked = s.maintenance.autoCleanup !== false;
                    document.getElementById('cleanupInterval').value = s.maintenance.cleanupInterval || 30;
                    document.getElementById('logRetention').value = s.maintenance.logRetention || 90;
                }
                
                alert('✓ Settings imported successfully!\n\nDon\'t forget to click "Save All Settings" to apply changes.');
                
            } catch (error) {
                console.error('Error importing settings:', error);
                alert('✗ Failed to import settings: ' + error.message);
            }
        };
        
        reader.readAsText(file);
    };
    
    input.click();
}

// Load activity log
async function loadActivityLog() {
    console.log('loadActivityLog() called');
    const activityLog = document.getElementById('activityLog');
    
    if (!activityLog) {
        console.error('Activity log element not found');
        return;
    }
    
    console.log('Activity log element found, loading data...');
    
    // Mock activity data (TODO: Implement real activity tracking)
    const activities = [
        { time: '2 minutes ago', text: 'User "john" uploaded 3 images', type: 'upload' },
        { time: '15 minutes ago', text: 'User "admin" logged in', type: 'login' },
        { time: '1 hour ago', text: 'User "sarah" deleted folder "Old Photos"', type: 'delete' },
        { time: '2 hours ago', text: 'New user "mike" registered', type: 'register' },
        { time: '3 hours ago', text: 'User "emma" shared image "vacation.jpg"', type: 'share' }
    ];
    
    let html = '';
    activities.forEach(activity => {
        html += `<div class="activity-item">
            <div class="activity-time">${activity.time}</div>
            <div class="activity-text">${activity.text}</div>
        </div>`;
    });
    
    console.log('Setting activity log HTML, length:', html.length);
    activityLog.innerHTML = html || '<p style="color: #666;">No recent activity.</p>';
    console.log('Activity log updated successfully');
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Check if current user is admin and show admin link
async function checkAdminAccess() {
    try {
        const response = await fetch('api.php?action=is_admin');
        const data = await response.json();
        
        if (data.success && data.data.is_admin === true) {
            // Show admin link in navigation
            const adminLinks = document.querySelectorAll('.admin-only');
            adminLinks.forEach(link => {
                link.style.display = 'inline-block';
            });
        }
    } catch (error) {
        console.error('Error checking admin access:', error);
    }
}

// Initialize admin check on page load
document.addEventListener('DOMContentLoaded', function() {
    checkAdminAccess();
});
