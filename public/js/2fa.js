/**
 * Two-Factor Authentication Management
 */

let currentSecret = '';
let currentBackupCodes = [];

// Load 2FA status
async function load2FAStatus() {
    try {
        const response = await fetch('api.php?action=get_2fa_status');
        const data = await response.json();
        
        if (data.success) {
            render2FAStatus(data.data);
        } else {
            document.getElementById('2fa-status').innerHTML = '<p style="color: #dc3545;">Failed to load 2FA status</p>';
        }
    } catch (error) {
        console.error('Error loading 2FA status:', error);
        document.getElementById('2fa-status').innerHTML = '<p style="color: #dc3545;">Error loading 2FA status</p>';
    }
}

// Render 2FA status
function render2FAStatus(status) {
    const statusDiv = document.getElementById('2fa-status');
    
    if (status.enabled) {
        const methodLabel = status.method === 'totp' ? 'Authenticator App' : 'Email';
        statusDiv.innerHTML = `
            <div style="background: #d4edda; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                <p style="margin: 0; font-weight: 600; color: #155724;">✓ 2FA is enabled</p>
                <p style="margin: 5px 0 0 0; color: #155724;">Method: ${methodLabel}</p>
            </div>
            <button type="button" class="btn btn-danger" onclick="disable2FA()" style="margin-top: 15px;">Disable 2FA</button>
            ${status.method === 'totp' ? '<button type="button" class="btn btn-secondary" onclick="regenerateBackupCodes()" style="margin-top: 15px; margin-left: 10px;">🔄 Regenerate Backup Codes</button>' : ''}
        `;
    } else {
        statusDiv.innerHTML = `
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                <p style="margin: 0; color: #856404;">⚠️ 2FA is not enabled</p>
                <p style="margin: 5px 0 0 0; color: #856404;">Add an extra layer of security to your account.</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="start2FASetup()" style="margin-top: 15px;">Enable 2FA</button>
        `;
    }
}

// Start 2FA setup
async function start2FASetup() {
    document.getElementById('2fa-status').style.display = 'none';
    document.getElementById('2fa-setup').style.display = 'block';
    
    // Set up method selector
    const methodSelector = document.getElementById('2fa-method');
    methodSelector.addEventListener('change', function() {
        if (this.value === 'totp') {
            document.getElementById('totp-setup').style.display = 'block';
            document.getElementById('email-setup').style.display = 'none';
            if (!currentSecret) {
                generate2FASecret();
            }
        } else {
            document.getElementById('totp-setup').style.display = 'none';
            document.getElementById('email-setup').style.display = 'block';
            loadUserEmail();
        }
    });
    
    // Default to TOTP and generate secret
    methodSelector.value = 'totp';
    document.getElementById('totp-setup').style.display = 'block';
    generate2FASecret();
}

// Generate 2FA secret
async function generate2FASecret() {
    try {
        const response = await fetch('api.php?action=generate_2fa_secret');
        const data = await response.json();
        
        if (data.success) {
            currentSecret = data.secret;
            currentBackupCodes = data.backup_codes;
            
            document.getElementById('qr-code').src = data.qr_code_url;
            document.getElementById('secret-key').textContent = data.secret;
            
            // Render backup codes
            const backupCodesDiv = document.getElementById('backup-codes');
            backupCodesDiv.innerHTML = data.backup_codes.map(code => 
                `<div style="background: #fff; padding: 8px; border-radius: 4px; text-align: center;">${code}</div>`
            ).join('');
        } else {
            alert('Failed to generate 2FA secret');
        }
    } catch (error) {
        console.error('Error generating 2FA secret:', error);
        alert('Error generating 2FA secret');
    }
}

// Load user email
async function loadUserEmail() {
    try {
        const response = await fetch('api.php?action=check_status');
        const data = await response.json();
        
        if (data.success && data.user) {
            document.getElementById('user-email-display').textContent = data.user.email || 'No email set';
        }
    } catch (error) {
        console.error('Error loading user email:', error);
    }
}

// Enable 2FA
async function enable2FA() {
    const method = document.getElementById('2fa-method').value;
    
    // Verify TOTP code if using authenticator app
    if (method === 'totp') {
        const code = document.getElementById('totp-verify-code').value.trim();
        
        if (!code || code.length !== 6) {
            alert('Please enter the 6-digit code from your authenticator app');
            return;
        }
    }
    
    try {
        const payload = {
            method: method,
            secret: currentSecret,
            backup_codes: currentBackupCodes
        };
        
        if (method === 'totp') {
            payload.verification_code = document.getElementById('totp-verify-code').value.trim();
        }
        
        const response = await fetch('api.php?action=enable_2fa', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✓ 2FA enabled successfully!');
            cancel2FASetup();
            load2FAStatus();
        } else {
            alert('✗ Failed to enable 2FA: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error enabling 2FA:', error);
        alert('✗ Error enabling 2FA');
    }
}

// Disable 2FA
async function disable2FA() {
    if (!confirm('Are you sure you want to disable 2FA?\n\nThis will make your account less secure.')) {
        return;
    }
    
    try {
        const response = await fetch('api.php?action=disable_2fa', {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✓ 2FA disabled successfully');
            load2FAStatus();
        } else {
            alert('✗ Failed to disable 2FA: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error disabling 2FA:', error);
        alert('✗ Error disabling 2FA');
    }
}

// Cancel 2FA setup
function cancel2FASetup() {
    document.getElementById('2fa-status').style.display = 'block';
    document.getElementById('2fa-setup').style.display = 'none';
    document.getElementById('totp-verify-code').value = '';
    currentSecret = '';
    currentBackupCodes = [];
}

// Download backup codes
function downloadBackupCodes() {
    const codes = currentBackupCodes.join('\n');
    const blob = new Blob([`iManage 2FA Backup Codes\n\nGenerated: ${new Date().toLocaleString()}\n\nKeep these codes safe!\n\n${codes}\n\nEach code can only be used once.`], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `imanage-backup-codes-${Date.now()}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Regenerate backup codes
async function regenerateBackupCodes() {
    if (!confirm('Generate new backup codes?\n\nYour old backup codes will no longer work.')) {
        return;
    }
    
    try {
        const response = await fetch('api.php?action=regenerate_backup_codes', {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const codes = data.backup_codes.join('\n');
            const message = `New backup codes generated!\n\n${codes}\n\nPlease save these codes now.`;
            alert(message);
            
            // Offer to download
            if (confirm('Download backup codes as a text file?')) {
                const blob = new Blob([`iManage 2FA Backup Codes\n\nGenerated: ${new Date().toLocaleString()}\n\n${codes}\n\nEach code can only be used once.`], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `imanage-backup-codes-${Date.now()}.txt`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }
        } else {
            alert('Failed to regenerate backup codes: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error regenerating backup codes:', error);
        alert('Error regenerating backup codes');
    }
}
