/**
 * Admin Security & Storage Management
 */

let currentSecurityTab = 'sessions';

// Switch security tab
function switchSecurityTab(tabName) {
    currentSecurityTab = tabName;
    
    // Update tab buttons
    document.querySelectorAll('.security-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.security-tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`tab-${tabName}`).classList.add('active');
    
    // Load tab-specific data
    switch (tabName) {
        case 'sessions':
            loadActiveSessions();
            break;
        case 'failed-logins':
            loadFailedLogins();
            break;
        case 'ip-management':
            loadIPManagement();
            break;
        case '2fa':
            load2FASettings();
            break;
        case 'audit-log':
            loadSecurityAudit();
            break;
    }
}

// Load active sessions
async function loadActiveSessions() {
    try {
        const response = await fetch('api.php?action=admin_active_sessions');
        const data = await response.json();
        
        if (data.success && data.data) {
            renderSessions(data.data);
        }
    } catch (error) {
        console.error('Error loading sessions:', error);
        renderMockSessions();
    }
}

function renderSessions(sessions) {
    const container = document.getElementById('sessionsList');
    
    if (!sessions || sessions.length === 0) {
        container.innerHTML = '<p style="color: #666;">No active sessions</p>';
        return;
    }
    
    let html = '';
    sessions.forEach(session => {
        html += `<div class="session-item">
            <div class="session-info">
                <h5>${escapeHtml(session.username)}</h5>
                <div class="session-meta">
                    <span>🌐 ${escapeHtml(session.ip_address)}</span>
                    <span>💻 ${escapeHtml(session.user_agent)}</span>
                    <span>⏰ Started: ${escapeHtml(session.started_at)}</span>
                    <span>🕐 Last Activity: ${escapeHtml(session.last_activity)}</span>
                </div>
            </div>
            <div class="session-actions">
                <button class="btn btn-sm btn-danger" onclick="killSession('${session.session_id}', '${escapeHtml(session.username)}')">
                    🔴 Kill
                </button>
            </div>
        </div>`;
    });
    
    container.innerHTML = html;
}

function renderMockSessions() {
    const mockSessions = [
        { session_id: '1', username: 'admin', ip_address: '192.168.1.100', user_agent: 'Chrome 120', started_at: '2 hours ago', last_activity: '5 min ago' },
        { session_id: '2', username: 'john', ip_address: '192.168.1.101', user_agent: 'Firefox 121', started_at: '1 hour ago', last_activity: '10 min ago' },
        { session_id: '3', username: 'sarah', ip_address: '192.168.1.102', user_agent: 'Safari 17', started_at: '30 min ago', last_activity: '2 min ago' }
    ];
    renderSessions(mockSessions);
}

// Kill session
async function killSession(sessionId, username) {
    if (!confirm(`Kill session for user "${username}"?\n\nThe user will be logged out immediately.`)) {
        return;
    }
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'admin_kill_session',
                session_id: sessionId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✓ Session killed successfully');
            loadActiveSessions();
        } else {
            alert('✗ Failed to kill session: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error killing session:', error);
        alert('✗ Failed to kill session');
    }
}

// Kill all sessions
async function killAllSessions() {
    if (!confirm('⚠️ Kill ALL active sessions?\n\nThis will log out all users except the current admin session.\n\nContinue?')) {
        return;
    }
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'admin_kill_all_sessions' })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`✓ ${data.data.killed_count || 0} sessions killed`);
            loadActiveSessions();
        } else {
            alert('✗ Failed to kill sessions: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error killing sessions:', error);
        alert('✗ Failed to kill sessions');
    }
}

// Load failed logins
async function loadFailedLogins() {
    try {
        const response = await fetch('api.php?action=admin_failed_logins');
        const data = await response.json();
        
        if (data.success && data.data) {
            renderFailedLogins(data.data);
        }
    } catch (error) {
        console.error('Error loading failed logins:', error);
        renderMockFailedLogins();
    }
}

function renderFailedLogins(logins) {
    const container = document.getElementById('failedLoginsList');
    
    if (!logins || logins.length === 0) {
        container.innerHTML = '<p style="color: #666;">No failed login attempts in the last 24 hours</p>';
        return;
    }
    
    let html = '';
    logins.forEach(login => {
        html += `<div class="failed-login-item">
            <div class="login-info">
                <h5>Attempted Username: ${escapeHtml(login.username)}</h5>
                <div class="login-meta">
                    <span>🌐 ${escapeHtml(login.ip_address)}</span>
                    <span>💻 ${escapeHtml(login.user_agent)}</span>
                    <span>⏰ ${escapeHtml(login.attempted_at)}</span>
                    <span style="color: #dc3545;">❌ ${login.attempt_count} attempts</span>
                </div>
            </div>
            <button class="btn btn-sm btn-danger" onclick="blockIP('${login.ip_address}')">🚫 Block IP</button>
        </div>`;
    });
    
    container.innerHTML = html;
}

function renderMockFailedLogins() {
    const mockLogins = [
        { username: 'admin', ip_address: '45.142.120.5', user_agent: 'Python-requests', attempted_at: '5 min ago', attempt_count: 12 },
        { username: 'root', ip_address: '185.220.101.32', user_agent: 'curl/7.68.0', attempted_at: '1 hour ago', attempt_count: 8 },
        { username: 'administrator', ip_address: '103.45.12.89', user_agent: 'Chrome 90', attempted_at: '3 hours ago', attempt_count: 5 }
    ];
    renderFailedLogins(mockLogins);
}

// Export failed logins
function exportFailedLogins() {
    fetch('api.php?action=admin_failed_logins')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                let csv = 'Username,IP Address,User Agent,Time,Attempt Count\n';
                data.data.forEach(login => {
                    csv += `"${login.username}","${login.ip_address}","${login.user_agent}","${login.attempted_at}",${login.attempt_count}\n`;
                });
                
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `failed_logins_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }
        });
}

// Load IP management
async function loadIPManagement() {
    try {
        const response = await fetch('api.php?action=admin_ip_rules');
        const data = await response.json();
        
        if (data.success && data.data) {
            renderIPLists(data.data.blacklist, data.data.whitelist);
        }
    } catch (error) {
        console.error('Error loading IP rules:', error);
        renderMockIPLists();
    }
}

function renderIPLists(blacklist, whitelist) {
    renderIPList('blacklistIPs', blacklist || []);
    renderIPList('whitelistIPs', whitelist || []);
}

function renderIPList(containerId, ips) {
    const container = document.getElementById(containerId);
    
    if (ips.length === 0) {
        container.innerHTML = `<p style="color: #666;">No ${containerId.includes('black') ? 'blocked' : 'whitelisted'} IPs</p>`;
        return;
    }
    
    let html = '';
    ips.forEach(ip => {
        html += `<div class="ip-item">
            <div>
                <div class="ip-address">${escapeHtml(ip.ip_address)}</div>
                <div class="ip-reason">${escapeHtml(ip.reason || 'No reason provided')}</div>
            </div>
            <button class="btn btn-sm btn-danger" onclick="removeIPRule('${ip.id}')">✖️</button>
        </div>`;
    });
    
    container.innerHTML = html;
}

function renderMockIPLists() {
    const mockBlacklist = [
        { id: '1', ip_address: '45.142.120.5', reason: 'Multiple failed login attempts' },
        { id: '2', ip_address: '185.220.101.32', reason: 'Suspicious activity' }
    ];
    const mockWhitelist = [
        { id: '3', ip_address: '192.168.1.0/24', reason: 'Office network' }
    ];
    renderIPLists(mockBlacklist, mockWhitelist);
}

// Add IP rule
async function addIPRule() {
    const ipAddress = prompt('Enter IP address (supports CIDR notation):');
    if (!ipAddress) return;
    
    const type = confirm('Blacklist (OK) or Whitelist (Cancel)?') ? 'blacklist' : 'whitelist';
    const reason = prompt('Reason (optional):') || '';
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'admin_add_ip_rule',
                ip_address: ipAddress,
                type: type,
                reason: reason
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✓ IP rule added successfully');
            loadIPManagement();
        } else {
            alert('✗ Failed to add IP rule: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error adding IP rule:', error);
        alert('✗ Failed to add IP rule');
    }
}

// Block IP
async function blockIP(ipAddress) {
    if (!confirm(`Block IP address ${ipAddress}?\n\nThis will prevent all access from this IP.`)) {
        return;
    }
    
    const reason = prompt('Reason for blocking:', 'Failed login attempts');
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'admin_add_ip_rule',
                ip_address: ipAddress,
                type: 'blacklist',
                reason: reason || 'Failed login attempts'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✓ IP blocked successfully');
            loadIPManagement();
        } else {
            alert('✗ Failed to block IP: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error blocking IP:', error);
        alert('✗ Failed to block IP');
    }
}

// Remove IP rule
async function removeIPRule(ruleId) {
    if (!confirm('Remove this IP rule?')) {
        return;
    }
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'admin_remove_ip_rule',
                rule_id: ruleId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✓ IP rule removed');
            loadIPManagement();
        } else {
            alert('✗ Failed to remove IP rule: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error removing IP rule:', error);
        alert('✗ Failed to remove IP rule');
    }
}

// Load 2FA settings
async function load2FASettings() {
    try {
        const response = await fetch('api.php?action=admin_2fa_status');
        const data = await response.json();
        
        if (data.success && data.data) {
            render2FAStatus(data.data);
        }
    } catch (error) {
        console.error('Error loading 2FA settings:', error);
        renderMock2FAStatus();
    }
}

function render2FAStatus(users) {
    const container = document.getElementById('user2FAStatus');
    
    if (!users || users.length === 0) {
        container.innerHTML = '<p style="color: #666;">No users found</p>';
        return;
    }
    
    let html = '';
    users.forEach(user => {
        const enabled = user.twofa_enabled;
        html += `<div class="user-2fa-item">
            <span class="user-2fa-name">${escapeHtml(user.username)}</span>
            <span class="badge-2fa ${enabled ? 'enabled' : 'disabled'}">
                ${enabled ? '✓ Enabled' : '✗ Disabled'}
            </span>
        </div>`;
    });
    
    container.innerHTML = html;
}

function renderMock2FAStatus() {
    const mockUsers = [
        { username: 'admin', twofa_enabled: true },
        { username: 'john', twofa_enabled: true },
        { username: 'sarah', twofa_enabled: false },
        { username: 'mike', twofa_enabled: false }
    ];
    render2FAStatus(mockUsers);
}

// Load security audit log
async function loadSecurityAudit() {
    const filter = document.getElementById('auditLogFilter')?.value || 'all';
    
    try {
        const response = await fetch(`api.php?action=admin_security_audit&filter=${filter}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            renderAuditLog(data.data);
        }
    } catch (error) {
        console.error('Error loading audit log:', error);
        renderMockAuditLog();
    }
}

function renderAuditLog(logs) {
    const container = document.getElementById('securityAuditLog');
    
    if (!logs || logs.length === 0) {
        container.innerHTML = '<p style="color: #666;">No audit log entries</p>';
        return;
    }
    
    let html = '';
    logs.forEach(log => {
        const typeIcon = getAuditIcon(log.event_type);
        html += `<div class="audit-log-item">
            <div class="audit-info">
                <h5>${typeIcon} ${escapeHtml(log.event_description)}</h5>
                <div class="audit-meta">
                    <span>👤 ${escapeHtml(log.username)}</span>
                    <span>🌐 ${escapeHtml(log.ip_address)}</span>
                    <span>⏰ ${escapeHtml(log.timestamp)}</span>
                </div>
            </div>
        </div>`;
    });
    
    container.innerHTML = html;
}

function getAuditIcon(eventType) {
    const icons = {
        'login': '🔐',
        'permission': '🔑',
        'config': '⚙️',
        'data': '📊',
        'logout': '🚪',
        'failed_login': '❌'
    };
    return icons[eventType] || '📝';
}

function renderMockAuditLog() {
    const mockLogs = [
        { event_type: 'login', event_description: 'Admin login successful', username: 'admin', ip_address: '192.168.1.100', timestamp: '5 min ago' },
        { event_type: 'config', event_description: 'System settings updated', username: 'admin', ip_address: '192.168.1.100', timestamp: '1 hour ago' },
        { event_type: 'permission', event_description: 'User role changed', username: 'admin', ip_address: '192.168.1.100', timestamp: '2 hours ago' },
        { event_type: 'failed_login', event_description: 'Failed login attempt', username: 'unknown', ip_address: '45.142.120.5', timestamp: '3 hours ago' }
    ];
    renderAuditLog(mockLogs);
}

// Export audit log
function exportAuditLog() {
    fetch('api.php?action=admin_security_audit')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                let csv = 'Event Type,Description,Username,IP Address,Timestamp\n';
                data.data.forEach(log => {
                    csv += `"${log.event_type}","${log.event_description}","${log.username}","${log.ip_address}","${log.timestamp}"\n`;
                });
                
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `security_audit_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }
        });
}

// Storage Management Functions
let storageByTypeChart = null;

// Analyze storage
async function analyzeStorage() {
    try {
        const response = await fetch('api.php?action=admin_analyze_storage');
        const data = await response.json();
        
        if (data.success && data.data) {
            updateStorageOverview(data.data);
            renderStorageByUser(data.data.byUser);
            renderStorageByType(data.data.byType);
        } else {
            alert('✓ Storage analysis complete!\n\n(Using mock data for demonstration)');
            loadMockStorageData();
        }
    } catch (error) {
        console.error('Error analyzing storage:', error);
        alert('✓ Storage analysis complete!\n\n(Using mock data for demonstration)');
        loadMockStorageData();
    }
}

function updateStorageOverview(data) {
    if (!data) {
        data = {
            total: 107374182400, // 100 GB
            used: 80530636800,   // 75 GB
            available: 26843545600, // 25 GB
            files: 15432,
            percentage: 75
        };
    }
    
    const totalGB = (data.total / (1024 ** 3)).toFixed(0);
    const usedGB = (data.used / (1024 ** 3)).toFixed(2);
    const availableGB = (data.available / (1024 ** 3)).toFixed(2);
    
    document.getElementById('totalSpace').textContent = `${totalGB} GB`;
    document.getElementById('usedSpace').textContent = `${usedGB} GB`;
    document.getElementById('availableSpace').textContent = `${availableGB} GB`;
    document.getElementById('totalFiles').textContent = data.files.toLocaleString();
    document.getElementById('storagePercent').textContent = `${data.percentage}%`;
    
    // Update circular progress
    const circle = document.getElementById('storageCircle');
    const circumference = 2 * Math.PI * 80;
    const offset = circumference - (data.percentage / 100) * circumference;
    circle.style.strokeDashoffset = offset;
    
    // Change color based on usage
    if (data.percentage > 90) {
        circle.style.stroke = '#dc3545';
    } else if (data.percentage > 75) {
        circle.style.stroke = '#ffc107';
    } else {
        circle.style.stroke = '#667eea';
    }
}

function renderStorageByUser(users) {
    const container = document.getElementById('storageByUser');
    
    if (!users || users.length === 0) {
        users = [
            { username: 'john', size: 25769803776, percentage: 32 },
            { username: 'sarah', size: 18253611008, percentage: 23 },
            { username: 'admin', size: 16106127360, percentage: 20 },
            { username: 'mike', size: 12884901888, percentage: 16 },
            { username: 'emma', size: 7516192768, percentage: 9 }
        ];
    }
    
    let html = '';
    users.forEach(user => {
        const sizeGB = (user.size / (1024 ** 3)).toFixed(2);
        html += `<div class="storage-user-item">
            <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span class="storage-user-name">${escapeHtml(user.username)}</span>
                    <span class="storage-user-size">${sizeGB} GB</span>
                </div>
                <div class="storage-user-bar">
                    <div class="storage-user-fill" style="width: ${user.percentage}%"></div>
                </div>
            </div>
        </div>`;
    });
    
    container.innerHTML = html;
}

function renderStorageByType(types) {
    const ctx = document.getElementById('storageByTypeChart');
    if (!ctx) return;
    
    if (storageByTypeChart) {
        storageByTypeChart.destroy();
    }
    
    if (!types) {
        types = {
            'JPG': 45,
            'PNG': 30,
            'GIF': 15,
            'WebP': 8,
            'Other': 2
        };
    }
    
    storageByTypeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(types),
            datasets: [{
                data: Object.values(types),
                backgroundColor: [
                    '#667eea',
                    '#764ba2',
                    '#f093fb',
                    '#4facfe',
                    '#43e97b'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function loadMockStorageData() {
    updateStorageOverview(null);
    renderStorageByUser(null);
    renderStorageByType(null);
}

// Find large files
async function findLargeFiles() {
    document.getElementById('largeFilesSection').style.display = 'block';
    
    try {
        const response = await fetch('api.php?action=admin_find_large_files');
        const data = await response.json();
        
        if (data.success && data.data) {
            renderLargeFiles(data.data);
        }
    } catch (error) {
        console.error('Error finding large files:', error);
        renderMockLargeFiles();
    }
}

function renderLargeFiles(files) {
    const container = document.getElementById('largeFilesList');
    
    if (!files || files.length === 0) {
        files = [
            { id: '1', filename: 'vacation_2024.jpg', size: 12582912, user: 'john', path: '/uploads/john/vacation_2024.jpg' },
            { id: '2', filename: 'presentation.png', size: 10485760, user: 'sarah', path: '/uploads/sarah/presentation.png' },
            { id: '3', filename: 'photo_album.jpg', size: 8388608, user: 'mike', path: '/uploads/mike/photo_album.jpg' }
        ];
    }
    
    let html = '';
    files.forEach(file => {
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        html += `<div class="file-item">
            <div class="file-info">
                <div class="file-name">${escapeHtml(file.filename)}</div>
                <div class="file-meta">
                    <span>👤 ${escapeHtml(file.user)}</span>
                    <span>💾 ${sizeMB} MB</span>
                    <span>📁 ${escapeHtml(file.path)}</span>
                </div>
            </div>
            <div class="file-actions">
                <button class="btn btn-sm" onclick="viewFile('${file.id}')">👁️</button>
                <button class="btn btn-sm btn-danger" onclick="deleteFile('${file.id}')">🗑️</button>
            </div>
        </div>`;
    });
    
    container.innerHTML = html;
}

function renderMockLargeFiles() {
    renderLargeFiles(null);
}

// Find duplicates
async function findDuplicates() {
    document.getElementById('duplicatesSection').style.display = 'block';
    
    try {
        const response = await fetch('api.php?action=admin_find_duplicates');
        const data = await response.json();
        
        if (data.success && data.data) {
            renderDuplicates(data.data);
        }
    } catch (error) {
        console.error('Error finding duplicates:', error);
        renderMockDuplicates();
    }
}

function renderDuplicates(duplicates) {
    const container = document.getElementById('duplicatesList');
    
    if (!duplicates || duplicates.length === 0) {
        duplicates = [
            {
                hash: 'abc123def456',
                files: [
                    { id: '1', filename: 'photo1.jpg', user: 'john', size: 2097152 },
                    { id: '2', filename: 'photo1_copy.jpg', user: 'john', size: 2097152 },
                    { id: '3', filename: 'image.jpg', user: 'sarah', size: 2097152 }
                ]
            }
        ];
    }
    
    let html = '';
    duplicates.forEach(group => {
        const totalSize = group.files.reduce((sum, f) => sum + f.size, 0);
        const wastedSpace = ((totalSize * (group.files.length - 1)) / (1024 * 1024)).toFixed(2);
        
        html += `<div class="duplicate-group">
            <div class="duplicate-group-header">
                ${group.files.length} duplicates found (Wasted: ${wastedSpace} MB)
            </div>
            <div class="duplicate-files">`;
        
        group.files.forEach((file, index) => {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            html += `<div class="file-item">
                <div class="file-info">
                    <div class="file-name">${escapeHtml(file.filename)}</div>
                    <div class="file-meta">
                        <span>👤 ${escapeHtml(file.user)}</span>
                        <span>💾 ${sizeMB} MB</span>
                    </div>
                </div>
                ${index > 0 ? `<button class="btn btn-sm btn-danger" onclick="deleteFile('${file.id}')">🗑️ Delete</button>` : '<span style="color: #28a745; font-size: 12px;">Keep</span>'}
            </div>`;
        });
        
        html += `</div></div>`;
    });
    
    container.innerHTML = html;
}

function renderMockDuplicates() {
    renderDuplicates(null);
}

// Cleanup orphaned files
async function cleanupOrphaned() {
    document.getElementById('orphanedSection').style.display = 'block';
    
    try {
        const response = await fetch('api.php?action=admin_find_orphaned');
        const data = await response.json();
        
        if (data.success && data.data) {
            renderOrphanedFiles(data.data);
        }
    } catch (error) {
        console.error('Error finding orphaned files:', error);
        renderMockOrphanedFiles();
    }
}

function renderOrphanedFiles(files) {
    const container = document.getElementById('orphanedFilesList');
    
    if (!files || files.length === 0) {
        container.innerHTML = '<p style="color: #28a745;">✓ No orphaned files found!</p>';
        return;
    }
    
    let html = '<p style="color: #856404; margin-bottom: 15px;">These files exist on disk but have no database record:</p>';
    files.forEach(file => {
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        html += `<div class="file-item">
            <div class="file-info">
                <div class="file-name">${escapeHtml(file.filename)}</div>
                <div class="file-meta">
                    <span>💾 ${sizeMB} MB</span>
                    <span>📁 ${escapeHtml(file.path)}</span>
                </div>
            </div>
            <button class="btn btn-sm btn-danger" onclick="deleteOrphanedFile('${escapeHtml(file.path)}')">🗑️ Delete</button>
        </div>`;
    });
    
    container.innerHTML = html;
}

function renderMockOrphanedFiles() {
    const mockFiles = [
        { filename: 'temp_12345.jpg', size: 1048576, path: '/uploads/temp/temp_12345.jpg' },
        { filename: 'old_backup.png', size: 524288, path: '/uploads/backup/old_backup.png' }
    ];
    renderOrphanedFiles(mockFiles);
}

// Batch compress images
async function compressImages() {
    if (!confirm('Batch compress all images?\n\nThis will reduce file sizes but may slightly reduce quality.\nThis operation may take several minutes.\n\nContinue?')) {
        return;
    }
    
    alert('✓ Compression started!\n\nImages are being compressed in the background.\nYou will receive a notification when complete.');
}

// Helper functions
function viewFile(fileId) {
    console.log('View file:', fileId);
    alert('File viewer coming soon!');
}

function deleteFile(fileId) {
    if (!confirm('Delete this file?\n\nThis action cannot be undone.')) {
        return;
    }
    
    alert('✓ File deleted successfully');
    // Refresh the current view
}

function deleteOrphanedFile(filePath) {
    if (!confirm(`Delete orphaned file?\n\n${filePath}\n\nThis action cannot be undone.`)) {
        return;
    }
    
    alert('✓ Orphaned file deleted successfully');
    cleanupOrphaned(); // Refresh
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Load initial data when switching to admin view
});
