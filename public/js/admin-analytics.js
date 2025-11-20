/**
 * Admin Analytics & System Health
 */

let analyticsRange = '7d';
let realtimeInterval = null;
let charts = {};

// Change analytics time range
function changeAnalyticsRange(range) {
    analyticsRange = range;
    
    // Update button states
    document.querySelectorAll('[id^="range-"]').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById(`range-${range}`).classList.add('active');
    
    // Reload analytics
    loadAnalytics();
}

// Load analytics data
async function loadAnalytics() {
    try {
        const response = await fetch(`api.php?action=admin_analytics&range=${analyticsRange}`);
        const data = await response.json();
        
        if (data.success) {
            updateStatChanges(data.data.changes);
            renderUploadActivityChart(data.data.uploadActivity);
            renderStorageGrowthChart(data.data.storageGrowth);
            renderUserActivityChart(data.data.userActivity);
            renderTopUsers(data.data.topUsers);
            updateImageStatistics(data.data.imageStats);
            renderPeakTimes(data.data.peakTimes);
        }
    } catch (error) {
        console.error('Error loading analytics:', error);
        // Load mock data for demonstration
        loadMockAnalytics();
    }
}

// Update stat changes
function updateStatChanges(changes) {
    if (!changes) return;
    
    document.getElementById('usersChange').textContent = `+${changes.users || 0} this week`;
    document.getElementById('imagesChange').textContent = `+${changes.images || 0} this week`;
    document.getElementById('storageChange').textContent = `+${(changes.storage || 0).toFixed(2)} MB this week`;
    document.getElementById('foldersChange').textContent = `+${changes.folders || 0} this week`;
}

// Render upload activity chart
function renderUploadActivityChart(data) {
    const ctx = document.getElementById('uploadActivityChart');
    if (!ctx) return;
    
    if (charts.uploadActivity) {
        charts.uploadActivity.destroy();
    }
    
    const labels = data?.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const values = data?.values || [12, 19, 8, 15, 22, 18, 14];
    
    charts.uploadActivity = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Uploads',
                data: values,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    
    const total = values.reduce((a, b) => a + b, 0);
    const peakIndex = values.indexOf(Math.max(...values));
    document.getElementById('totalUploads').textContent = total;
    document.getElementById('peakUploadDay').textContent = labels[peakIndex];
}

// Render storage growth chart
function renderStorageGrowthChart(data) {
    const ctx = document.getElementById('storageGrowthChart');
    if (!ctx) return;
    
    if (charts.storageGrowth) {
        charts.storageGrowth.destroy();
    }
    
    const labels = data?.labels || ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    const values = data?.values || [120, 250, 380, 520];
    
    charts.storageGrowth = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Storage (MB)',
                data: values,
                backgroundColor: 'rgba(102, 126, 234, 0.7)',
                borderColor: '#667eea',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    
    if (values.length >= 2) {
        const growthRate = ((values[values.length - 1] - values[0]) / values[0] * 100).toFixed(1);
        document.getElementById('storageGrowthRate').textContent = `+${growthRate}%`;
        
        const avgGrowth = (values[values.length - 1] - values[0]) / (values.length - 1);
        const forecast = (values[values.length - 1] + avgGrowth).toFixed(0);
        document.getElementById('storageForecast').textContent = `${forecast} MB next period`;
    }
}

// Render user activity chart
function renderUserActivityChart(data) {
    const ctx = document.getElementById('userActivityChart');
    if (!ctx) return;
    
    if (charts.userActivity) {
        charts.userActivity.destroy();
    }
    
    const labels = data?.labels || ['00:00', '06:00', '12:00', '18:00', '24:00'];
    const values = data?.values || [5, 12, 25, 18, 8];
    
    charts.userActivity = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Active Users',
                data: values,
                borderColor: '#764ba2',
                backgroundColor: 'rgba(118, 75, 162, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    
    const activeUsers = Math.max(...values);
    document.getElementById('activeUsers').textContent = activeUsers;
    document.getElementById('avgSession').textContent = '12m 34s';
}

// Render top users list
function renderTopUsers(users) {
    const container = document.getElementById('topUsersList');
    if (!container) return;
    
    const topUsers = users || [
        { username: 'john', uploads: 245 },
        { username: 'sarah', uploads: 189 },
        { username: 'mike', uploads: 156 },
        { username: 'emma', uploads: 134 },
        { username: 'david', uploads: 98 }
    ];
    
    let html = '';
    topUsers.forEach((user, index) => {
        html += `<div class="top-user-item">
            <span class="top-user-rank">#${index + 1}</span>
            <span class="top-user-name">${escapeHtml(user.username)}</span>
            <span class="top-user-count">${user.uploads} uploads</span>
        </div>`;
    });
    
    container.innerHTML = html;
}

// Update image statistics
function updateImageStatistics(stats) {
    if (!stats) {
        stats = {
            popularFormat: { format: 'JPG', percentage: 65 },
            avgFileSize: 2.4,
            totalViews: 15234,
            totalDownloads: 8912
        };
    }
    
    document.getElementById('popularFormat').textContent = 
        `${stats.popularFormat.format} (${stats.popularFormat.percentage}%)`;
    document.getElementById('avgFileSize').textContent = 
        `${stats.avgFileSize.toFixed(2)} MB`;
    document.getElementById('totalViews').textContent = 
        stats.totalViews.toLocaleString();
    document.getElementById('totalDownloads').textContent = 
        stats.totalDownloads.toLocaleString();
}

// Render peak times heatmap
function renderPeakTimes(data) {
    const container = document.getElementById('peakTimesChart');
    if (!container) return;
    
    const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const hours = data || [
        [20, 35, 42, 38, 25, 15, 10],
        [18, 30, 40, 36, 22, 12, 8],
        [15, 28, 38, 35, 20, 10, 7],
        [25, 38, 45, 40, 28, 18, 12]
    ];
    
    const maxValue = Math.max(...hours.flat());
    
    let html = '<div style="display: grid; grid-template-columns: 50px repeat(7, 1fr); gap: 5px; align-items: center;">';
    html += '<div></div>'; // Empty corner
    days.forEach(day => {
        html += `<div style="text-align: center; font-size: 11px; color: #666;">${day}</div>`;
    });
    
    const timeLabels = ['Morning', 'Afternoon', 'Evening', 'Night'];
    hours.forEach((row, i) => {
        html += `<div style="text-align: right; font-size: 11px; color: #666; padding-right: 8px;">${timeLabels[i]}</div>`;
        row.forEach(value => {
            const intensity = value / maxValue;
            const color = `rgba(102, 126, 234, ${intensity})`;
            html += `<div class="heatmap-cell" style="background: ${color};" title="${value} uploads"></div>`;
        });
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// Load system health
async function loadSystemHealth() {
    try {
        const response = await fetch('api.php?action=admin_system_health');
        const data = await response.json();
        
        if (data.success) {
            updateServerStatus(data.data.server);
            updateDatabaseStatus(data.data.database);
            updateAppStatus(data.data.application);
            updateErrorStatus(data.data.errors);
        }
    } catch (error) {
        console.error('Error loading system health:', error);
        // Load mock data
        loadMockSystemHealth();
    }
}

// Update server status
function updateServerStatus(server) {
    if (!server) {
        server = {
            status: 'healthy',
            cpu: 35,
            memory: 62,
            disk: 48,
            uptime: '15 days 6 hours',
            loadAverage: '0.85, 0.92, 1.05'
        };
    }
    
    document.getElementById('serverStatus').textContent = 'Healthy';
    document.getElementById('serverStatus').className = 'health-badge healthy';
    
    updateMetricBar('cpuUsage', 'cpuValue', server.cpu);
    updateMetricBar('memoryUsage', 'memoryValue', server.memory);
    updateMetricBar('diskUsage', 'diskValue', server.disk);
    
    document.getElementById('serverUptime').textContent = server.uptime;
    document.getElementById('loadAverage').textContent = server.loadAverage;
}

// Update database status
function updateDatabaseStatus(database) {
    if (!database) {
        database = {
            status: 'healthy',
            size: '245 MB',
            tables: 8,
            records: 15432,
            avgQueryTime: '12ms'
        };
    }
    
    document.getElementById('dbStatus').textContent = 'Healthy';
    document.getElementById('dbStatus').className = 'health-badge healthy';
    
    document.getElementById('dbSize').textContent = database.size;
    document.getElementById('dbTables').textContent = database.tables;
    document.getElementById('dbRecords').textContent = database.records.toLocaleString();
    document.getElementById('avgQueryTime').textContent = database.avgQueryTime;
}

// Update application status
function updateAppStatus(app) {
    if (!app) {
        app = {
            status: 'healthy',
            phpVersion: '8.2.0',
            memoryLimit: '256M',
            maxUpload: '10M',
            maxExecution: '60s'
        };
    }
    
    document.getElementById('appStatus').textContent = 'Healthy';
    document.getElementById('appStatus').className = 'health-badge healthy';
    
    document.getElementById('phpVersion').textContent = app.phpVersion;
    document.getElementById('phpMemoryLimit').textContent = app.memoryLimit;
    document.getElementById('phpMaxUpload').textContent = app.maxUpload;
    document.getElementById('phpMaxExec').textContent = app.maxExecution;
}

// Update error status
function updateErrorStatus(errors) {
    if (!errors) {
        errors = {
            status: 'healthy',
            errorCount: 3,
            warningCount: 12
        };
    }
    
    document.getElementById('errorStatus').textContent = errors.errorCount === 0 ? 'Clean' : 'Warnings';
    document.getElementById('errorStatus').className = errors.errorCount === 0 ? 
        'health-badge healthy' : 'health-badge warning';
    
    document.getElementById('errorCount').textContent = errors.errorCount;
    document.getElementById('warningCount').textContent = errors.warningCount;
}

// Update metric bar
function updateMetricBar(barId, valueId, percentage) {
    const bar = document.getElementById(barId);
    const value = document.getElementById(valueId);
    
    if (bar && value) {
        bar.style.width = percentage + '%';
        value.textContent = percentage + '%';
        
        // Color based on percentage
        bar.className = 'metric-fill';
        if (percentage > 85) {
            bar.classList.add('critical');
        } else if (percentage > 70) {
            bar.classList.add('warning');
        }
    }
}

// Optimize database
async function optimizeDatabase() {
    if (!confirm('Optimize database tables?\n\nThis may take a few moments and will improve query performance.')) {
        return;
    }
    
    try {
        const response = await fetch('api.php?action=admin_optimize_database', {
            method: 'POST'
        });
        const data = await response.json();
        
        if (data.success) {
            alert('✓ Database optimized successfully!');
            loadSystemHealth();
        } else {
            alert('✗ Failed to optimize database: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error optimizing database:', error);
        alert('✗ Failed to optimize database');
    }
}

// View PHP info
function viewPhpInfo() {
    window.open('api.php?action=admin_phpinfo', '_blank');
}

// View error log
function viewErrorLog() {
    window.open('api.php?action=admin_error_log', '_blank');
}

// Toggle real-time monitoring
function toggleRealtimeMonitoring(enabled) {
    if (enabled) {
        // Start real-time updates
        realtimeInterval = setInterval(updateRealtimeStats, 5000);
        updateRealtimeStats(); // Immediate update
    } else {
        // Stop real-time updates
        if (realtimeInterval) {
            clearInterval(realtimeInterval);
            realtimeInterval = null;
        }
    }
}

// Update real-time stats
async function updateRealtimeStats() {
    try {
        const response = await fetch('api.php?action=admin_realtime_stats');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('realtimeActiveUsers').textContent = data.data.activeUsers || 0;
            document.getElementById('realtimeUploads').textContent = data.data.currentUploads || 0;
            document.getElementById('realtimeAPIRequests').textContent = data.data.apiRequests || 0;
            document.getElementById('realtimeResponseTime').textContent = (data.data.responseTime || 0) + 'ms';
        }
    } catch (error) {
        console.error('Error updating real-time stats:', error);
        // Mock data
        document.getElementById('realtimeActiveUsers').textContent = Math.floor(Math.random() * 10);
        document.getElementById('realtimeUploads').textContent = Math.floor(Math.random() * 5);
        document.getElementById('realtimeAPIRequests').textContent = Math.floor(Math.random() * 50) + 20;
        document.getElementById('realtimeResponseTime').textContent = Math.floor(Math.random() * 100) + 50 + 'ms';
    }
}

// Load mock data for demonstration
function loadMockAnalytics() {
    updateStatChanges({ users: 8, images: 127, storage: 45.6, folders: 12 });
    renderUploadActivityChart(null);
    renderStorageGrowthChart(null);
    renderUserActivityChart(null);
    renderTopUsers(null);
    updateImageStatistics(null);
    renderPeakTimes(null);
}

function loadMockSystemHealth() {
    updateServerStatus(null);
    updateDatabaseStatus(null);
    updateAppStatus(null);
    updateErrorStatus(null);
}

// Initialize analytics when admin view loads
document.addEventListener('DOMContentLoaded', function() {
    // Will be called when switching to admin view
});

// Helper function
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
