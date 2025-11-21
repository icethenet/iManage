<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Management System</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1e88e5">
    <link rel="icon" type="image/png" href="img/icons/icon-192.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/gallery.css">
    <script defer src="js/offline.js"></script>
    <script defer src="js/upload.js"></script>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1>Image Management System</h1>
            <p>Upload, organize, and manipulate your images</p>
        </header>

        <!-- Navigation -->
        <nav class="nav">
            <div class="nav-left user-is-logged-in">
                <a href="#" class="nav-link active" data-view="gallery">Gallery</a>
                <a href="#" class="nav-link" data-view="upload">Upload</a>
                <a href="#" class="nav-link" data-view="folders">Folders</a>
                <a href="#" class="nav-link user-only" data-view="settings">Settings</a>
                <a href="#" class="nav-link admin-only" data-view="admin" style="display: none;">Admin</a>
            </div>
            <div class="nav-left user-is-logged-out">
                <a href="#" class="nav-link active" data-view="gallery">Gallery</a>
                <a href="#" class="nav-link" data-view="login">Login</a>
                <a href="#" class="nav-link" data-view="register">Register</a>
            </div>
            <div class="nav-right user-is-logged-in">
                <span class="username-display" id="username-display"></span>
                <a href="#" id="logout-link" class="nav-link">Logout</a>
            </div>
            <div class="nav-right">
                <input type="text" id="searchInput" placeholder="Search images..." class="search-input">
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Gallery View -->
            <section id="gallery-view" class="view active">
                <div class="gallery-header">
                    <h2>Image Gallery</h2>
                    <div class="folder-filter">
                        <label for="folderSelect">Folder:</label>
                        <select id="folderSelect">
                            <option value="">All Folders</option>
                        </select>
                    </div>
                </div>

                <div id="gallery" class="gallery">
                    <!-- Images will be loaded here -->
                </div>

                <div class="pagination">
                    <button id="prevBtn" class="btn btn-secondary">Previous</button>
                    <span id="pageInfo">Page 1</span>
                    <button id="nextBtn" class="btn btn-secondary">Next</button>
                </div>
            </section>

            <!-- Upload View -->
            <section id="upload-view" class="view">
                <div class="upload-container">
                    <h2>Upload Images</h2>
                    
                    <form id="uploadForm" class="upload-form">
                        <div class="form-group">
                            <label for="imageFile">Select Images or Videos:</label>
                            <div class="file-input-wrapper" id="dropZone">
                                <input type="file" id="imageFile" name="image" accept="image/*,.mp4,.mov,.mkv,.avi,.webm,video/mp4,video/quicktime,video/x-matroska,video/x-msvideo,video/webm" multiple>
                                <div class="file-input-display">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <span class="file-name">Click to select images or videos, or drag and drop</span>
                                    <small style="display: block; margin-top: 8px; color: #999;">Multiple files supported • Images: JPG, PNG, GIF, WebP • Videos: MP4, MOV, MKV, AVI, WebM</small>
                                </div>
                                <div id="filePreview" class="file-preview"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="imageFolder">Folder:</label>
                            <select id="imageFolder" name="folder">
                                <option value="default">Default</option>
                            </select>
                        </div>

                        <!-- <div class="form-group">
                            <label for="bulkTitle">Title (optional - applied to all images):</label>
                            <input type="text" id="bulkTitle" name="title" placeholder="Common title for all images">
                        </div>

                        <div class="form-group">
                            <label for="bulkDescription">Description (optional - applied to all images):</label>
                            <textarea id="bulkDescription" name="description" placeholder="Common description for all images" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="bulkTags">Tags (optional - applied to all images):</label>
                            <input type="text" id="bulkTags" name="tags" placeholder="Separate tags with commas">
                        </div> -->

                        <div id="uploadProgress" class="upload-progress"></div>

                        <button type="submit" class="btn btn-primary">Upload Images</button>
                        <div id="uploadStatus" class="upload-status"></div>
                    </form>

                    <!-- Hidden section to create a subfolder -->
                    <div id="createSubfolderSection" class="form-group" style="display: none; background-color: #f0f2ff; padding: 15px; border-radius: 6px; margin-top: 20px;">
                        <label for="newSubfolderName" style="font-weight: bold;">Create a new subfolder to upload into:</label>
                        <p style="font-size: 12px; margin-bottom: 10px;">You don't have any subfolders yet. Create one here.</p>
                        <input type="text" id="newSubfolderName" name="new_subfolder_name" placeholder="New subfolder name">
                        <input type="hidden" id="newSubfolderDesc" name="new_subfolder_description" value="">
                        <button type="button" id="createSubfolderBtn" class="btn btn-secondary btn-sm" style="margin-top: 10px;">Create Subfolder</button>
                        <div id="subfolderStatus" style="margin-top: 10px; font-size: 12px;"></div>
                    </div>
                </div>
            </section>

            <!-- Folders View -->
            <section id="folders-view" class="view">
                <div class="folders-container">
                    <h2>Manage Folders</h2>
                    
                    <div class="create-folder">
                        <input type="text" id="newFolderName" placeholder="Folder name">
                        <input type="text" id="newFolderDesc" placeholder="Folder description">
                        <button class="btn btn-primary" onclick="createFolder()">Create Folder</button>
                    </div>

                    <div id="foldersList" class="folders-list">
                        <!-- Folders will be loaded here -->
                    </div>
                </div>
            </section>

            <!-- Login View -->
            <section id="login-view" class="view">
                <div class="auth-container">
                    <h2>Login</h2>
                    <form id="loginForm" class="auth-form">
                        <div class="form-group">
                            <label for="loginUsername">Username</label>
                            <input type="text" id="loginUsername" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="loginPassword">Password</label>
                            <input type="password" id="loginPassword" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
                        <div id="loginStatus" class="auth-status"></div>
                    </form>
                    
                    <div class="oauth-separator">
                        <span>OR</span>
                    </div>
                    
                    <div class="oauth-buttons">
                        <button class="oauth-btn oauth-google" data-provider="google">
                            <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.875 2.684-6.615z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/><path fill="#FBBC05" d="M3.964 10.71c-.18-.54-.282-1.117-.282-1.71s.102-1.17.282-1.71V4.958H.957C.347 6.173 0 7.548 0 9s.348 2.827.957 4.042l3.007-2.332z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/></svg>
                            Continue with Google
                        </button>
                        <button class="oauth-btn oauth-facebook" data-provider="facebook">
                            <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            Continue with Facebook
                        </button>
                        <button class="oauth-btn oauth-github" data-provider="github">
                            <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#181717" d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                            Continue with GitHub
                        </button>
                        <button class="oauth-btn oauth-microsoft" data-provider="microsoft">
                            <svg width="18" height="18" viewBox="0 0 23 23"><path fill="#f3f3f3" d="M0 0h23v23H0z"/><path fill="#f35325" d="M1 1h10v10H1z"/><path fill="#81bc06" d="M12 1h10v10H12z"/><path fill="#05a6f0" d="M1 12h10v10H1z"/><path fill="#ffba08" d="M12 12h10v10H12z"/></svg>
                            Continue with Microsoft
                        </button>
                    </div>
                </div>
            </section>

            <!-- Register View -->
            <section id="register-view" class="view">
                <div class="auth-container">
                    <h2>Register</h2>
                    <form id="registerForm" class="auth-form">
                        <div class="form-group"><label for="registerUsername">Username</label><input type="text" id="registerUsername" name="username" required></div>
                        <div class="form-group"><label for="registerPassword">Password</label><input type="password" id="registerPassword" name="password" minlength="8" required></div>
                        <div class="form-group"><label for="registerConfirmPassword">Confirm Password</label><input type="password" id="registerConfirmPassword" name="confirm_password" minlength="8" required></div>
                        <button type="submit" class="btn btn-primary">Register</button>
                        <div id="registerStatus" class="auth-status"></div>
                    </form>
                </div>
            </section>

            <!-- Settings View (User Profile & 2FA) -->
            <section id="settings-view" class="view">
                <div class="settings-container">
                    <h2>Account Settings</h2>
                    
                    <!-- Profile Information -->
                    <div class="setting-section">
                        <h3>Profile Information</h3>
                        <div class="setting-content">
                            <div class="profile-info">
                                <div class="profile-info-item">
                                    <span class="info-label">Username</span>
                                    <span class="info-value" id="profileUsername">-</span>
                                </div>
                                <div class="profile-info-item">
                                    <span class="info-label">Email</span>
                                    <span class="info-value" id="profileEmail">-</span>
                                </div>
                                <div class="profile-info-item">
                                    <span class="info-label">Member Since</span>
                                    <span class="info-value" id="profileMemberSince">-</span>
                                </div>
                                <div class="profile-info-item">
                                    <span class="info-label">Last Login</span>
                                    <span class="info-value" id="profileLastLogin">-</span>
                                </div>
                                <div class="profile-info-item">
                                    <span class="info-label">Login Provider</span>
                                    <span class="info-value" id="profileProvider">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Email -->
                    <div class="setting-section">
                        <h3>Update Email</h3>
                        <div class="setting-content">
                            <form id="updateEmailForm">
                                <div class="form-group">
                                    <label for="newEmail">New Email Address</label>
                                    <input type="email" id="newEmail" name="newEmail" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Email</button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="setting-section">
                        <h3>Change Password</h3>
                        <div class="setting-content">
                            <form id="changePasswordForm">
                                <div class="form-group">
                                    <label for="currentPassword">Current Password</label>
                                    <input type="password" id="currentPassword" name="currentPassword" required>
                                </div>
                                <div class="form-group">
                                    <label for="newPassword">New Password</label>
                                    <input type="password" id="newPassword" name="newPassword" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirmPassword">Confirm New Password</label>
                                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>

                    <!-- Two-Factor Authentication -->
                    <div class="setting-section">
                        <h3>Two-Factor Authentication (2FA)</h3>
                        <div class="setting-content">
                            <div id="2fa-status">
                                <p>Loading 2FA status...</p>
                            </div>
                            
                            <!-- 2FA Setup Area (hidden by default) -->
                            <div id="2fa-setup" style="display: none;">
                                <div class="form-group">
                                    <label>Choose 2FA Method</label>
                                    <select id="2fa-method" class="form-control">
                                        <option value="totp">Authenticator App (Google Authenticator, Authy, etc.)</option>
                                        <option value="email">Email Code</option>
                                    </select>
                                </div>
                                
                                <div id="totp-setup" style="display: none;">
                                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0;">
                                        <h4>Step 1: Scan QR Code</h4>
                                        <div style="text-align: center; margin: 15px 0;">
                                            <img id="qr-code" src="" alt="QR Code" style="max-width: 200px;">
                                        </div>
                                        <p style="font-size: 12px; color: #666;">Or manually enter this secret key:</p>
                                        <code id="secret-key" style="display: block; background: #fff; padding: 10px; border-radius: 4px; text-align: center; font-size: 14px; letter-spacing: 2px;"></code>
                                        
                                        <h4 style="margin-top: 20px;">Step 2: Enter Verification Code</h4>
                                        <input type="text" id="totp-verify-code" class="form-control" placeholder="Enter 6-digit code" maxlength="6" style="text-align: center; font-size: 18px; letter-spacing: 4px;">
                                    </div>
                                    
                                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107;">
                                        <h4>Backup Codes</h4>
                                        <p style="margin: 10px 0; font-size: 14px;">Save these backup codes in a safe place. Each can be used once if you lose access to your authenticator app.</p>
                                        <div id="backup-codes" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-family: monospace; font-size: 14px;"></div>
                                        <button type="button" class="btn btn-sm" onclick="downloadBackupCodes()" style="margin-top: 10px;">📥 Download Codes</button>
                                    </div>
                                </div>
                                
                                <div id="email-setup" style="display: none;">
                                    <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #2196F3;">
                                        <p style="margin: 0;">📧 A 6-digit verification code will be sent to your email address whenever you log in.</p>
                                        <p style="margin: 10px 0 0 0; font-weight: 600;">Email: <span id="user-email-display">loading...</span></p>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 10px; margin-top: 20px;">
                                    <button type="button" class="btn btn-primary" onclick="enable2FA()">✓ Enable 2FA</button>
                                    <button type="button" class="btn btn-secondary" onclick="cancel2FASetup()">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Statistics -->
                    <div class="setting-section">
                        <h3>Account Statistics</h3>
                        <div class="setting-content">
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-value" id="statImages">0</div>
                                    <div class="stat-label">Total Images</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value" id="statFolders">0</div>
                                    <div class="stat-label">Folders</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value" id="statStorage">0 MB</div>
                                    <div class="stat-label">Storage Used</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value" id="statShared">0</div>
                                    <div class="stat-label">Shared Images</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="setting-section" style="border-left: 4px solid #dc3545;">
                        <h3 style="color: #dc3545;">Danger Zone</h3>
                        <div class="setting-content">
                            <p style="color: #666; margin-bottom: 15px;">Once you delete your account, there is no going back. This will permanently delete all your images and data.</p>
                            <button type="button" class="btn btn-danger" onclick="deleteAccount()">Delete My Account</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Admin View -->
            <section id="admin-view" class="view">
                <div class="admin-container">
                    <h2>Admin Dashboard</h2>
                    
                    <!-- Admin Navigation Tabs -->
                    <div class="admin-tabs">
                        <button class="admin-tab-btn active" onclick="switchAdminTab('overview')">📊 Overview</button>
                        <button class="admin-tab-btn" onclick="switchAdminTab('users')">👥 Users</button>
                        <button class="admin-tab-btn" onclick="switchAdminTab('security')">🔒 Security</button>
                        <button class="admin-tab-btn" onclick="switchAdminTab('storage')">💾 Storage</button>
                        <button class="admin-tab-btn" onclick="switchAdminTab('oauth')">🔑 OAuth</button>
                        <button class="admin-tab-btn" onclick="switchAdminTab('settings')">⚙️ Settings</button>
                        <button class="admin-tab-btn" onclick="switchAdminTab('logs')">📋 Activity</button>
                    </div>
                    
                    <!-- Overview Tab -->
                    <div id="admin-tab-overview" class="admin-tab-content active">
                    <!-- Quick Stats Overview -->
                    <div class="admin-section">
                        <h3>📊 System Overview</h3>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon">👥</div>
                                <div class="stat-content">
                                    <div class="stat-label">Total Users</div>
                                    <div class="stat-value" id="adminTotalUsers">0</div>
                                    <div class="stat-change" id="usersChange">+0 this week</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">🖼️</div>
                                <div class="stat-content">
                                    <div class="stat-label">Total Images</div>
                                    <div class="stat-value" id="adminTotalImages">0</div>
                                    <div class="stat-change" id="imagesChange">+0 this week</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">💾</div>
                                <div class="stat-content">
                                    <div class="stat-label">Total Storage</div>
                                    <div class="stat-value" id="adminTotalStorage">0 MB</div>
                                    <div class="stat-change" id="storageChange">+0 MB this week</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">📁</div>
                                <div class="stat-content">
                                    <div class="stat-label">Total Folders</div>
                                    <div class="stat-value" id="adminTotalFolders">0</div>
                                    <div class="stat-change" id="foldersChange">+0 this week</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Dashboard -->
                    <div class="admin-section">
                        <h3>📈 Analytics Dashboard</h3>
                        
                        <!-- Time Range Selector -->
                        <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                            <button class="btn btn-sm active" onclick="changeAnalyticsRange('7d')" id="range-7d">Last 7 Days</button>
                            <button class="btn btn-sm" onclick="changeAnalyticsRange('30d')" id="range-30d">Last 30 Days</button>
                            <button class="btn btn-sm" onclick="changeAnalyticsRange('90d')" id="range-90d">Last 90 Days</button>
                            <button class="btn btn-sm" onclick="changeAnalyticsRange('1y')" id="range-1y">Last Year</button>
                        </div>

                        <!-- Charts Grid -->
                        <div class="charts-grid">
                            <!-- Upload Activity Chart -->
                            <div class="chart-container">
                                <h4>📤 Upload Activity</h4>
                                <canvas id="uploadActivityChart"></canvas>
                                <div class="chart-summary">
                                    <span>Total Uploads: <strong id="totalUploads">0</strong></span>
                                    <span>Peak Day: <strong id="peakUploadDay">-</strong></span>
                                </div>
                            </div>

                            <!-- Storage Growth Chart -->
                            <div class="chart-container">
                                <h4>💾 Storage Growth</h4>
                                <canvas id="storageGrowthChart"></canvas>
                                <div class="chart-summary">
                                    <span>Growth Rate: <strong id="storageGrowthRate">+0%</strong></span>
                                    <span>Forecast: <strong id="storageForecast">-</strong></span>
                                </div>
                            </div>

                            <!-- User Activity Chart -->
                            <div class="chart-container">
                                <h4>👤 User Activity</h4>
                                <canvas id="userActivityChart"></canvas>
                                <div class="chart-summary">
                                    <span>Active Users: <strong id="activeUsers">0</strong></span>
                                    <span>Avg Session: <strong id="avgSession">-</strong></span>
                                </div>
                            </div>

                            <!-- Top Users Chart -->
                            <div class="chart-container">
                                <h4>🏆 Top Contributors</h4>
                                <div id="topUsersList" class="top-users-list">
                                    <p style="color: #666;">Loading...</p>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Analytics -->
                        <div class="analytics-details">
                            <div class="analytics-box">
                                <h5>📊 Image Statistics</h5>
                                <div class="analytics-grid">
                                    <div class="analytics-item">
                                        <span class="label">Most Popular Format:</span>
                                        <span class="value" id="popularFormat">JPG (0%)</span>
                                    </div>
                                    <div class="analytics-item">
                                        <span class="label">Average File Size:</span>
                                        <span class="value" id="avgFileSize">0 MB</span>
                                    </div>
                                    <div class="analytics-item">
                                        <span class="label">Total Views:</span>
                                        <span class="value" id="totalViews">0</span>
                                    </div>
                                    <div class="analytics-item">
                                        <span class="label">Total Downloads:</span>
                                        <span class="value" id="totalDownloads">0</span>
                                    </div>
                                </div>
                            </div>

                            <div class="analytics-box">
                                <h5>⏰ Peak Usage Times</h5>
                                <div id="peakTimesChart" class="peak-times-heatmap">
                                    <!-- Will be populated with JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    </div><!-- End Overview Tab -->

                    <!-- Analytics Tab -->

                    <!-- Users Tab -->
                    <div id="admin-tab-users" class="admin-tab-content">
                    <!-- User Management Section -->
                    <div class="admin-section">
                        <h3>👥 User Management</h3>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                            <input type="text" id="userSearchInput" placeholder="🔍 Search users..." style="flex: 1; min-width: 200px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <button class="btn" onclick="exportUsers()">📥 Export Users (CSV)</button>
                        </div>
                        
                        <div id="usersList">
                            <p>Loading users...</p>
                        </div>
                    </div>
                    </div><!-- End Users Tab -->

                    <!-- Security Tab -->
                    <div id="admin-tab-security" class="admin-tab-content">
                    <!-- Enhanced Security Section -->
                    <div class="admin-section">
                        <h3>🔒 Enhanced Security</h3>
                        
                        <!-- Security Overview Stats -->
                        <div class="stats-grid" style="margin-bottom: 25px;">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">🛡️</div>
                                <div class="stat-content">
                                    <div class="stat-label">Security Score</div>
                                    <div class="stat-value">85/100</div>
                                    <div class="stat-change">+5 this week</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">⚠️</div>
                                <div class="stat-content">
                                    <div class="stat-label">Threats Blocked</div>
                                    <div class="stat-value" id="threatsBlocked">47</div>
                                    <div class="stat-change">Last 24h</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">🚫</div>
                                <div class="stat-content">
                                    <div class="stat-label">Blocked IPs</div>
                                    <div class="stat-value" id="blockedIPs">12</div>
                                    <div class="stat-change">Active rules</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">🔐</div>
                                <div class="stat-content">
                                    <div class="stat-label">Active Sessions</div>
                                    <div class="stat-value" id="activeSessions">8</div>
                                    <div class="stat-change">Currently online</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Tabs -->
                        <div class="security-tabs">
                            <button class="security-tab active" onclick="switchSecurityTab('sessions')">Active Sessions</button>
                            <button class="security-tab" onclick="switchSecurityTab('failed-logins')">Failed Logins</button>
                            <button class="security-tab" onclick="switchSecurityTab('ip-management')">IP Management</button>
                            <button class="security-tab" onclick="switchSecurityTab('2fa')">2FA Settings</button>
                            <button class="security-tab" onclick="switchSecurityTab('audit-log')">Security Audit</button>
                        </div>
                        
                        <!-- Active Sessions Tab -->
                        <div id="tab-sessions" class="security-tab-content active">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h4>Active User Sessions</h4>
                                <button class="btn btn-danger btn-sm" onclick="killAllSessions()">🔴 Kill All Sessions</button>
                            </div>
                            <div id="sessionsList">
                                <p>Loading sessions...</p>
                            </div>
                        </div>
                        
                        <!-- Failed Logins Tab -->
                        <div id="tab-failed-logins" class="security-tab-content">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h4>Failed Login Attempts (Last 24 Hours)</h4>
                                <button class="btn btn-sm" onclick="exportFailedLogins()">📥 Export CSV</button>
                            </div>
                            <div id="failedLoginsList">
                                <p>Loading failed logins...</p>
                            </div>
                        </div>
                        
                        <!-- IP Management Tab -->
                        <div id="tab-ip-management" class="security-tab-content">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h4>IP Access Control</h4>
                                <button class="btn btn-sm" onclick="showAddIPModal()">➕ Add IP Rule</button>
                            </div>
                            
                            <div class="ip-management-grid">
                                <div class="ip-list-section">
                                    <h5 style="color: #dc3545; margin-bottom: 10px;">🚫 Blacklist</h5>
                                    <div id="blacklistIPs">
                                        <p>Loading...</p>
                                    </div>
                                </div>
                                
                                <div class="ip-list-section">
                                    <h5 style="color: #28a745; margin-bottom: 10px;">✅ Whitelist</h5>
                                    <div id="whitelistIPs">
                                        <p>Loading...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Add IP Rule Modal -->
                        <div id="addIPModal" class="modal" style="display: none;">
                            <div class="modal-content" style="max-width: 500px;">
                                <h3>Add IP Access Rule</h3>
                                <form id="addIPForm" onsubmit="submitIPRule(event)">
                                    <div class="form-group">
                                        <label for="ipAddress">IP Address *</label>
                                        <input type="text" id="ipAddress" class="form-control" 
                                               placeholder="e.g., 192.168.1.100 or 10.0.0.0/24" required>
                                        <small>Supports single IPs or CIDR notation</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="ipType">Rule Type *</label>
                                        <select id="ipType" class="form-control" required>
                                            <option value="blacklist">🚫 Blacklist (Block Access)</option>
                                            <option value="whitelist">✅ Whitelist (Allow Access)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="ipReason">Reason</label>
                                        <input type="text" id="ipReason" class="form-control" 
                                               placeholder="Optional reason for this rule">
                                    </div>
                                    
                                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                                        <button type="button" class="btn btn-secondary" onclick="closeAddIPModal()">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Add Rule</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- 2FA Settings Tab -->
                        <div id="tab-2fa" class="security-tab-content">
                            <h4>Two-Factor Authentication Settings</h4>
                            
                            <div style="padding: 15px; background: #e3f2fd; border-left: 4px solid #2196F3; margin: 15px 0; border-radius: 4px;">
                                <label style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" id="require2FA" style="width: 18px; height: 18px;">
                                    <span style="font-weight: 600;">Require 2FA for all users</span>
                                </label>
                                <p style="margin: 10px 0 0 28px; font-size: 14px; color: #666;">When enabled, all users must set up two-factor authentication to access their accounts.</p>
                            </div>
                            
                            <h5 style="margin: 25px 0 15px 0;">User 2FA Status</h5>
                            <div id="user2FAStatus">
                                <p>Loading user status...</p>
                            </div>
                        </div>
                        
                        <!-- Security Audit Tab -->
                        <div id="tab-audit-log" class="security-tab-content">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                                <h4>Security Audit Log</h4>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <select id="auditLogFilter" onchange="loadSecurityAudit()" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px;">
                                        <option value="all">All Events</option>
                                        <option value="login">Login Events</option>
                                        <option value="permission">Permission Changes</option>
                                        <option value="config">Config Changes</option>
                                        <option value="data">Data Access</option>
                                    </select>
                                    <button class="btn btn-sm" onclick="exportAuditLog()">📥 Export CSV</button>
                                </div>
                            </div>
                            
                            <div id="securityAuditLog">
                                <p>Loading audit log...</p>
                            </div>
                        </div>
                    </div>
                    </div><!-- End Security Tab -->

                    <!-- Storage Tab -->
                    <div id="admin-tab-storage" class="admin-tab-content">
                    <!-- Storage Management Section -->
                    <div class="admin-section">
                        <h3>💾 Storage Management</h3>
                        
                        <!-- Storage Overview -->
                        <div class="storage-overview">
                            <div class="storage-visual">
                                <svg viewBox="0 0 200 200" style="width: 200px; height: 200px;">
                                    <circle cx="100" cy="100" r="80" fill="none" stroke="#e0e0e0" stroke-width="20"/>
                                    <circle id="storageCircle" cx="100" cy="100" r="80" fill="none" stroke="#667eea" stroke-width="20" 
                                            stroke-dasharray="502.65" stroke-dashoffset="125.66" 
                                            transform="rotate(-90 100 100)" stroke-linecap="round"/>
                                    <text x="100" y="95" text-anchor="middle" font-size="24" font-weight="bold" fill="#333" id="storagePercent">0%</text>
                                    <text x="100" y="115" text-anchor="middle" font-size="12" fill="#666">Used</text>
                                </svg>
                            </div>
                            
                            <div class="storage-stats">
                                <div class="storage-stat-item">
                                    <div class="storage-stat-label">Total Space</div>
                                    <div class="storage-stat-value" id="totalSpace">0 GB</div>
                                </div>
                                <div class="storage-stat-item">
                                    <div class="storage-stat-label">Used Space</div>
                                    <div class="storage-stat-value" id="usedSpace">0 GB</div>
                                </div>
                                <div class="storage-stat-item">
                                    <div class="storage-stat-label">Available Space</div>
                                    <div class="storage-stat-value" id="availableSpace">0 GB</div>
                                </div>
                                <div class="storage-stat-item">
                                    <div class="storage-stat-label">Total Files</div>
                                    <div class="storage-stat-value" id="totalFiles">0</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Storage Actions -->
                        <div class="storage-actions">
                            <button class="btn" onclick="analyzeStorage()">🔍 Analyze Storage</button>
                            <button class="btn" onclick="findLargeFiles()">📦 Find Large Files</button>
                            <button class="btn" onclick="findDuplicates()">🔄 Find Duplicates</button>
                            <button class="btn" onclick="cleanupOrphaned()">🧹 Cleanup Orphaned</button>
                            <button class="btn" onclick="compressImages()">📉 Compress Images</button>
                        </div>
                        
                        <!-- Storage Breakdown -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 30px;">
                            <div>
                                <h4 style="margin-bottom: 15px;">Storage by User</h4>
                                <div id="storageByUser">
                                    <p style="color: #666;">Click "Analyze Storage" to view breakdown</p>
                                </div>
                            </div>
                            
                            <div>
                                <h4 style="margin-bottom: 15px;">Storage by File Type</h4>
                                <div style="max-width: 300px;">
                                    <canvas id="storageByTypeChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Large Files Section (Hidden by default) -->
                        <div id="largeFilesSection" style="display: none; margin-top: 30px;">
                            <h4 style="margin-bottom: 15px;">Large Files (> 5 MB)</h4>
                            <div id="largeFilesList"></div>
                        </div>
                        
                        <!-- Duplicates Section (Hidden by default) -->
                        <div id="duplicatesSection" style="display: none; margin-top: 30px;">
                            <h4 style="margin-bottom: 15px;">Duplicate Files</h4>
                            <div id="duplicatesList"></div>
                        </div>
                        
                        <!-- Orphaned Files Section (Hidden by default) -->
                        <div id="orphanedSection" style="display: none; margin-top: 30px;">
                            <h4 style="margin-bottom: 15px;">Orphaned Files</h4>
                            <div id="orphanedFilesList"></div>
                        </div>
                    </div>
                    </div><!-- End Storage Tab -->

                    <!-- OAuth Tab -->
                    <div id="admin-tab-oauth" class="admin-tab-content">
                    <!-- OAuth Provider Configuration -->
                    <div class="admin-section">
                        <h3>🔑 OAuth Provider Configuration</h3>
                        <div class="oauth-providers-grid" id="oauthProviders">
                            <!-- Google -->
                            <div class="oauth-provider-card">
                                <h4>Google</h4>
                                <div class="provider-status" id="googleStatus">Checking...</div>
                                <button class="btn btn-sm" onclick="testOAuthProvider('google')">Test Connection</button>
                                <button class="btn btn-sm" onclick="viewOAuthConfig('google')">View Config</button>
                            </div>
                            
                            <!-- Facebook -->
                            <div class="oauth-provider-card">
                                <h4>Facebook</h4>
                                <div class="provider-status" id="facebookStatus">Checking...</div>
                                <button class="btn btn-sm" onclick="testOAuthProvider('facebook')">Test Connection</button>
                                <button class="btn btn-sm" onclick="viewOAuthConfig('facebook')">View Config</button>
                            </div>
                            
                            <!-- GitHub -->
                            <div class="oauth-provider-card">
                                <h4>GitHub</h4>
                                <div class="provider-status" id="githubStatus">Checking...</div>
                                <button class="btn btn-sm" onclick="testOAuthProvider('github')">Test Connection</button>
                                <button class="btn btn-sm" onclick="viewOAuthConfig('github')">View Config</button>
                            </div>
                            
                            <!-- Microsoft -->
                            <div class="oauth-provider-card">
                                <h4>Microsoft</h4>
                                <div class="provider-status" id="microsoftStatus">Checking...</div>
                                <button class="btn btn-sm" onclick="testOAuthProvider('microsoft')">Test Connection</button>
                                <button class="btn btn-sm" onclick="viewOAuthConfig('microsoft')">View Config</button>
                            </div>
                        </div>
                        <div style="margin-top: 20px;">
                            <p><strong>Note:</strong> OAuth configuration is stored in <code>config/oauth.php</code>. Copy <code>config/oauth.php.example</code> to get started.</p>
                        </div>
                    </div>
                    </div><!-- End OAuth Tab -->

                    <!-- Settings Tab -->
                    <div id="admin-tab-settings" class="admin-tab-content">
                    
                    <!-- Upload Configuration Section -->
                    <div class="admin-section">
                        <h3>📤 Upload Configuration</h3>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                            <div class="form-group" style="max-width: 500px;">
                                <label for="maxFileSizeMB">Maximum File Size (MB)</label>
                                <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                                    <input type="number" id="maxFileSizeMB" class="form-control" min="1" max="50" step="0.5" value="5" style="flex: 0 0 120px;">
                                    <input type="range" id="maxFileSizeSlider" min="1" max="50" step="0.5" value="5" style="flex: 1;" oninput="document.getElementById('maxFileSizeMB').value = this.value">
                                    <span style="min-width: 60px; font-weight: bold;" id="maxFileSizeDisplay">5 MB</span>
                                </div>
                                <p style="margin-top: 10px; font-size: 12px; color: #666;">
                                    Controls the maximum size for individual image uploads. Range: 1MB to 50MB.
                                    <br><strong>Note:</strong> Changes take effect immediately but may require PHP restart for full effect depending on server configuration.
                                </p>
                                <button type="button" class="btn btn-primary" onclick="saveUploadSettings()" style="margin-top: 15px;">💾 Save Upload Settings</button>
                                <div id="uploadSettingsStatus" style="margin-top: 10px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Settings Section -->
                    
                    <!-- System Health -->
                    <div class="admin-section">
                        <h3>🏥 System Health</h3>
                        
                        <div class="health-grid">
                            <!-- Server Status -->
                            <div class="health-card">
                                <div class="health-header">
                                    <h4>🖥️ Server Status</h4>
                                    <span class="health-badge" id="serverStatus">Checking...</span>
                                </div>
                                <div class="health-metrics">
                                    <div class="metric">
                                        <span class="metric-label">CPU Usage</span>
                                        <div class="metric-bar">
                                            <div class="metric-fill" id="cpuUsage" style="width: 0%"></div>
                                        </div>
                                        <span class="metric-value" id="cpuValue">0%</span>
                                    </div>
                                    <div class="metric">
                                        <span class="metric-label">Memory Usage</span>
                                        <div class="metric-bar">
                                            <div class="metric-fill" id="memoryUsage" style="width: 0%"></div>
                                        </div>
                                        <span class="metric-value" id="memoryValue">0%</span>
                                    </div>
                                    <div class="metric">
                                        <span class="metric-label">Disk Usage</span>
                                        <div class="metric-bar">
                                            <div class="metric-fill" id="diskUsage" style="width: 0%"></div>
                                        </div>
                                        <span class="metric-value" id="diskValue">0%</span>
                                    </div>
                                    <div class="metric-info">
                                        <span>Uptime: <strong id="serverUptime">-</strong></span>
                                        <span>Load Average: <strong id="loadAverage">-</strong></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Database Health -->
                            <div class="health-card">
                                <div class="health-header">
                                    <h4>🗄️ Database Health</h4>
                                    <span class="health-badge" id="dbStatus">Checking...</span>
                                </div>
                                <div class="health-metrics">
                                    <div class="metric-info">
                                        <span>Database Size: <strong id="dbSize">-</strong></span>
                                        <span>Total Tables: <strong id="dbTables">-</strong></span>
                                        <span>Total Records: <strong id="dbRecords">-</strong></span>
                                        <span>Avg Query Time: <strong id="avgQueryTime">-</strong></span>
                                    </div>
                                    <button class="btn btn-sm" onclick="optimizeDatabase()">⚡ Optimize Database</button>
                                </div>
                            </div>

                            <!-- Application Health -->
                            <div class="health-card">
                                <div class="health-header">
                                    <h4>⚙️ Application Status</h4>
                                    <span class="health-badge" id="appStatus">Checking...</span>
                                </div>
                                <div class="health-metrics">
                                    <div class="metric-info">
                                        <span>PHP Version: <strong id="phpVersion">-</strong></span>
                                        <span>Memory Limit: <strong id="phpMemoryLimit">-</strong></span>
                                        <span>Max Upload: <strong id="phpMaxUpload">-</strong></span>
                                        <span>Execution Time: <strong id="phpMaxExec">-</strong></span>
                                    </div>
                                    <button class="btn btn-sm" onclick="viewPhpInfo()">ℹ️ View PHP Info</button>
                                </div>
                            </div>

                            <!-- Error Log Summary -->
                            <div class="health-card">
                                <div class="health-header">
                                    <h4>⚠️ Error Log</h4>
                                    <span class="health-badge" id="errorStatus">Checking...</span>
                                </div>
                                <div class="health-metrics">
                                    <div class="error-summary">
                                        <div class="error-stat">
                                            <span class="error-count" id="errorCount">0</span>
                                            <span class="error-label">Errors (24h)</span>
                                        </div>
                                        <div class="error-stat">
                                            <span class="error-count warning" id="warningCount">0</span>
                                            <span class="error-label">Warnings (24h)</span>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm" onclick="viewErrorLog()">📋 View Full Log</button>
                                </div>
                            </div>
                        </div>

                        <!-- Real-time Monitoring -->
                        <div class="realtime-monitor">
                            <h4>📡 Real-time Monitoring</h4>
                            <div class="monitor-toggle">
                                <label>
                                    <input type="checkbox" id="realtimeMonitor" onchange="toggleRealtimeMonitoring(this.checked)">
                                    Enable Real-time Updates (every 5 seconds)
                                </label>
                            </div>
                            <div id="realtimeStats" class="realtime-stats">
                                <div class="realtime-item">
                                    <span class="realtime-label">Active Users:</span>
                                    <span class="realtime-value" id="realtimeActiveUsers">0</span>
                                </div>
                                <div class="realtime-item">
                                    <span class="realtime-label">Current Uploads:</span>
                                    <span class="realtime-value" id="realtimeUploads">0</span>
                                </div>
                                <div class="realtime-item">
                                    <span class="realtime-label">API Requests/min:</span>
                                    <span class="realtime-value" id="realtimeAPIRequests">0</span>
                                </div>
                                <div class="realtime-item">
                                    <span class="realtime-label">Avg Response Time:</span>
                                    <span class="realtime-value" id="realtimeResponseTime">0ms</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- OAuth Provider Management -->
                    <div class="admin-section">
                        <h3>🔐 OAuth Provider Configuration</h3>
                        <p style="margin-bottom: 20px; color: #666; font-size: 14px;">
                            Enable or disable OAuth providers. Configuration is managed in <code>config/oauth.php</code>
                        </p>
                        
                        <div class="oauth-providers-list">
                            <!-- Google -->
                            <div class="provider-card">
                                <div class="provider-header">
                                    <svg width="24" height="24" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.875 2.684-6.615z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/><path fill="#FBBC05" d="M3.964 10.71c-.18-.54-.282-1.117-.282-1.71s.102-1.17.282-1.71V4.958H.957C.347 6.173 0 7.548 0 9s.348 2.827.957 4.042l3.007-2.332z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/></svg>
                                    <h4>Google</h4>
                                </div>
                                <div class="provider-info">
                                    <span class="provider-status" id="googleStatus">Checking...</span>
                                </div>
                                <div class="provider-actions">
                                    <button class="btn btn-sm btn-primary" onclick="testOAuthProvider('google')">Test Connection</button>
                                    <button class="btn btn-sm" onclick="viewOAuthConfig('google')">View Config</button>
                                </div>
                            </div>

                            <!-- Facebook -->
                            <div class="provider-card">
                                <div class="provider-header">
                                    <svg width="24" height="24" viewBox="0 0 24 24"><path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    <h4>Facebook</h4>
                                </div>
                                <div class="provider-info">
                                    <span class="provider-status" id="facebookStatus">Checking...</span>
                                </div>
                                <div class="provider-actions">
                                    <button class="btn btn-sm btn-primary" onclick="testOAuthProvider('facebook')">Test Connection</button>
                                    <button class="btn btn-sm" onclick="viewOAuthConfig('facebook')">View Config</button>
                                </div>
                            </div>

                            <!-- GitHub -->
                            <div class="provider-card">
                                <div class="provider-header">
                                    <svg width="24" height="24" viewBox="0 0 24 24"><path fill="#181717" d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                    <h4>GitHub</h4>
                                </div>
                                <div class="provider-info">
                                    <span class="provider-status" id="githubStatus">Checking...</span>
                                </div>
                                <div class="provider-actions">
                                    <button class="btn btn-sm btn-primary" onclick="testOAuthProvider('github')">Test Connection</button>
                                    <button class="btn btn-sm" onclick="viewOAuthConfig('github')">View Config</button>
                                </div>
                            </div>

                            <!-- Microsoft -->
                            <div class="provider-card">
                                <div class="provider-header">
                                    <svg width="24" height="24" viewBox="0 0 23 23"><path fill="#f3f3f3" d="M0 0h23v23H0z"/><path fill="#f35325" d="M1 1h10v10H1z"/><path fill="#81bc06" d="M12 1h10v10H12z"/><path fill="#05a6f0" d="M1 12h10v10H1z"/><path fill="#ffba08" d="M12 12h10v10H12z"/></svg>
                                    <h4>Microsoft</h4>
                                </div>
                                <div class="provider-info">
                                    <span class="provider-status" id="microsoftStatus">Checking...</span>
                                </div>
                                <div class="provider-actions">
                                    <button class="btn btn-sm btn-primary" onclick="testOAuthProvider('microsoft')">Test Connection</button>
                                    <button class="btn btn-sm" onclick="viewOAuthConfig('microsoft')">View Config</button>
                                </div>
                            </div>
                        </div>

                        <div class="info-box info-warning">
                            <strong>⚙️ Configuration Instructions:</strong>
                            <ol style="margin: 10px 0 0 20px; font-size: 14px; line-height: 1.6;">
                                <li>Copy <code>config/oauth.php.example</code> to <code>config/oauth.php</code></li>
                                <li>Register your application with each OAuth provider</li>
                                <li>Add your client IDs and secrets to the config file</li>
                                <li>Set the redirect URI in each provider's settings</li>
                                <li>Use the "Test Connection" button to verify configuration</li>
                            </ol>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="admin-section">
                        <h3>⚙️ System Settings</h3>
                        
                        <!-- Upload Settings -->
                        <div class="settings-category">
                            <h4>📤 Upload Settings</h4>
                            <div class="settings-grid">
                                <div class="setting-item">
                                    <label for="maxUploadSize">Max Upload Size (MB)</label>
                                    <input type="number" id="maxUploadSize" value="10" min="1" max="100">
                                    <small>Maximum file size for image uploads</small>
                                </div>
                                <div class="setting-item">
                                    <label for="allowedFormats">Allowed Formats</label>
                                    <input type="text" id="allowedFormats" value="jpg, jpeg, png, gif, webp">
                                    <small>Comma-separated list of allowed file extensions</small>
                                </div>
                                <div class="setting-item">
                                    <label for="maxImagesPerUser">Max Images Per User</label>
                                    <input type="number" id="maxImagesPerUser" value="1000" min="1" max="10000">
                                    <small>Maximum number of images per user (0 = unlimited)</small>
                                </div>
                                <div class="setting-item">
                                    <label for="maxStoragePerUser">Max Storage Per User (GB)</label>
                                    <input type="number" id="maxStoragePerUser" value="5" min="1" max="100" step="0.5">
                                    <small>Maximum storage space per user (0 = unlimited)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Image Processing Settings -->
                        <div class="settings-category">
                            <h4>🖼️ Image Processing</h4>
                            <div class="settings-grid">
                                <div class="setting-item">
                                    <label for="thumbnailSize">Thumbnail Size (px)</label>
                                    <input type="number" id="thumbnailSize" value="300" min="100" max="1000" step="50">
                                    <small>Size of generated thumbnails</small>
                                </div>
                                <div class="setting-item">
                                    <label for="thumbnailQuality">Thumbnail Quality (%)</label>
                                    <input type="number" id="thumbnailQuality" value="85" min="50" max="100" step="5">
                                    <small>JPEG quality for thumbnails (higher = better quality, larger size)</small>
                                </div>
                                <div class="setting-item">
                                    <label for="pristineQuality">Pristine Quality (%)</label>
                                    <input type="number" id="pristineQuality" value="95" min="80" max="100" step="5">
                                    <small>JPEG quality for pristine copies</small>
                                </div>
                                <div class="setting-item">
                                    <label for="autoRotate">
                                        <input type="checkbox" id="autoRotate" checked>
                                        Auto-Rotate Images
                                    </label>
                                    <small>Automatically rotate images based on EXIF orientation</small>
                                </div>
                                <div class="setting-item">
                                    <label for="stripExif">
                                        <input type="checkbox" id="stripExif">
                                        Strip EXIF Data
                                    </label>
                                    <small>Remove EXIF metadata from uploaded images (privacy)</small>
                                </div>
                                <div class="setting-item">
                                    <label for="generateWebP">
                                        <input type="checkbox" id="generateWebP">
                                        Generate WebP Versions
                                    </label>
                                    <small>Automatically create WebP versions for better compression</small>
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings -->
                        <div class="settings-category">
                            <h4>🔒 Security & Access</h4>
                            <div class="settings-grid">
                                <div class="setting-item">
                                    <label for="enableRegistration">
                                        <input type="checkbox" id="enableRegistration" checked>
                                        Enable User Registration
                                    </label>
                                    <small>Allow new users to register accounts</small>
                                </div>
                                <div class="setting-item">
                                    <label for="enableOAuth">
                                        <input type="checkbox" id="enableOAuth" checked>
                                        Enable OAuth Login
                                    </label>
                                    <small>Allow users to login with OAuth providers</small>
                                </div>
                                <div class="setting-item">
                                    <label for="requireEmailVerification">
                                        <input type="checkbox" id="requireEmailVerification">
                                        Require Email Verification
                                    </label>
                                    <small>Users must verify email before uploading</small>
                                </div>
                                <div class="setting-item">
                                    <label for="sessionTimeout">Session Timeout (minutes)</label>
                                    <input type="number" id="sessionTimeout" value="1440" min="30" max="10080" step="30">
                                    <small>Auto-logout after inactivity (1440 = 24 hours)</small>
                                </div>
                                <div class="setting-item">
                                    <label for="minPasswordLength">Min Password Length</label>
                                    <input type="number" id="minPasswordLength" value="8" min="6" max="32">
                                    <small>Minimum characters required for passwords</small>
                                </div>
                                <div class="setting-item">
                                    <label for="maxLoginAttempts">Max Login Attempts</label>
                                    <input type="number" id="maxLoginAttempts" value="5" min="3" max="10">
                                    <small>Lock account after this many failed login attempts</small>
                                </div>
                            </div>
                        </div>

                        <!-- Sharing & Visibility Settings -->
                        <div class="settings-category">
                            <h4>🔗 Sharing & Visibility</h4>
                            <div class="settings-grid">
                                <div class="setting-item">
                                    <label for="enableSharing">
                                        <input type="checkbox" id="enableSharing" checked>
                                        Enable Image Sharing
                                    </label>
                                    <small>Allow users to share images via links</small>
                                </div>
                                <div class="setting-item">
                                    <label for="enablePublicGallery">
                                        <input type="checkbox" id="enablePublicGallery" checked>
                                        Enable Public Gallery
                                    </label>
                                    <small>Show public/shared images to non-logged-in users</small>
                                </div>
                                <div class="setting-item">
                                    <label for="defaultImageVisibility">Default Image Visibility</label>
                                    <select id="defaultImageVisibility">
                                        <option value="private">Private</option>
                                        <option value="public" selected>Public</option>
                                        <option value="unlisted">Unlisted</option>
                                    </select>
                                    <small>Default visibility for new uploads</small>
                                </div>
                                <div class="setting-item">
                                    <label for="shareLinkExpiration">Share Link Expiration (days)</label>
                                    <input type="number" id="shareLinkExpiration" value="0" min="0" max="365">
                                    <small>Auto-expire share links after X days (0 = never)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Settings -->
                        <div class="settings-category">
                            <h4>⚡ Performance & Cache</h4>
                            <div class="settings-grid">
                                <div class="setting-item">
                                    <label for="enableCache">
                                        <input type="checkbox" id="enableCache" checked>
                                        Enable Image Cache
                                    </label>
                                    <small>Cache processed images for faster loading</small>
                                </div>
                                <div class="setting-item">
                                    <label for="cacheExpiration">Cache Expiration (hours)</label>
                                    <input type="number" id="cacheExpiration" value="24" min="1" max="720">
                                    <small>How long to cache images</small>
                                </div>
                                <div class="setting-item">
                                    <label for="imagesPerPage">Images Per Page</label>
                                    <input type="number" id="imagesPerPage" value="20" min="10" max="100" step="10">
                                    <small>Number of images to display per page</small>
                                </div>
                                <div class="setting-item">
                                    <label for="enableLazyLoad">
                                        <input type="checkbox" id="enableLazyLoad" checked>
                                        Enable Lazy Loading
                                    </label>
                                    <small>Load images only when they become visible</small>
                                </div>
                            </div>
                        </div>

                        <!-- Email Settings -->
                        <div class="settings-category">
                            <h4>📧 Email Configuration</h4>
                            <div class="settings-grid">
                                <div class="setting-item">
                                    <label for="enableEmail">
                                        <input type="checkbox" id="enableEmail">
                                        Enable Email Notifications
                                    </label>
                                    <small>Send emails for important events</small>
                                </div>
                                <div class="setting-item">
                                    <label for="smtpHost">SMTP Host</label>
                                    <input type="text" id="smtpHost" placeholder="smtp.example.com">
                                    <small>Email server hostname</small>
                                </div>
                                <div class="setting-item">
                                    <label for="smtpPort">SMTP Port</label>
                                    <input type="number" id="smtpPort" value="587" min="1" max="65535">
                                    <small>Email server port (587 for TLS, 465 for SSL)</small>
                                </div>
                                <div class="setting-item">
                                    <label for="smtpUsername">SMTP Username</label>
                                    <input type="text" id="smtpUsername" placeholder="user@example.com">
                                    <small>Email account username</small>
                                </div>
                                <div class="setting-item">
                                    <label for="smtpPassword">SMTP Password</label>
                                    <input type="password" id="smtpPassword" placeholder="••••••••">
                                    <small>Email account password</small>
                                </div>
                                <div class="setting-item">
                                    <label for="emailFrom">From Email Address</label>
                                    <input type="email" id="emailFrom" placeholder="noreply@example.com">
                                    <small>Email address shown as sender</small>
                                </div>
                            </div>
                        </div>

                        <!-- Maintenance Settings -->
                        <div class="settings-category">
                            <h4>🛠️ Maintenance & Cleanup</h4>
                            <div class="settings-grid">
                                <div class="setting-item">
                                    <label for="enableMaintenance">
                                        <input type="checkbox" id="enableMaintenance">
                                        Maintenance Mode
                                    </label>
                                    <small>Disable site access for non-admin users</small>
                                </div>
                                <div class="setting-item">
                                    <label for="autoCleanup">
                                        <input type="checkbox" id="autoCleanup" checked>
                                        Auto-Cleanup Deleted Files
                                    </label>
                                    <small>Automatically remove deleted files from disk</small>
                                </div>
                                <div class="setting-item">
                                    <label for="cleanupInterval">Cleanup Interval (days)</label>
                                    <input type="number" id="cleanupInterval" value="30" min="1" max="365">
                                    <small>How often to run cleanup tasks</small>
                                </div>
                                <div class="setting-item">
                                    <label for="logRetention">Log Retention (days)</label>
                                    <input type="number" id="logRetention" value="90" min="7" max="365">
                                    <small>How long to keep system logs</small>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 25px; border-top: 2px solid #e0e0e0; padding-top: 20px;">
                            <button class="btn btn-primary" onclick="saveSystemSettings()">💾 Save All Settings</button>
                            <button class="btn" onclick="resetSystemSettings()">🔄 Reset to Defaults</button>
                            <button class="btn" onclick="exportSystemSettings()">📥 Export Config</button>
                            <button class="btn" onclick="importSystemSettings()">📤 Import Config</button>
                        </div>
                    </div>
                        </div>
                    </div>
                    </div><!-- End Settings Tab -->

                    <!-- Activity Logs Tab -->
                    <div id="admin-tab-logs" class="admin-tab-content">
                    <!-- Activity Logs -->
                    <div class="admin-section">
                        <h3>📋 Recent Activity</h3>
                        <div id="activityLog" class="activity-log">
                            <p style="color: #666;">Loading activity...</p>
                        </div>
                    </div>
                    </div><!-- End Logs Tab -->

                </div><!-- End admin-container -->
            </section><!-- End Admin View -->
        </main>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-body">
                <div class="modal-image" style="position: relative; display: inline-block;">
                    <img id="modalImage" src="" alt="" style="display: block;">
                    <div id="cropCanvasContainer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: none; z-index: 10; pointer-events: none;"></div>
                </div>
                <div id="modalImageSpinner" class="spinner-overlay" style="display: none;">
                    <div class="spinner-inner"></div>
                </div>
                <div class="modal-details">
                    <div class="modal-metadata-section">
                        <div id="metadataViewMode">
                            <h2 id="modalTitle"></h2>
                            <p id="modalDescription"></p>
                            <p id="modalTags" style="color: #888; font-size: 14px;"></p>
                        </div>
                        <div style="margin-top: 10px;">
                            <button id="editMetadataBtn" class="btn btn-sm edit-metadata-btn">✏️ Edit Info</button>
                        </div>
                        <div id="metadataEditMode" style="display: none;">
                            <div class="form-group">
                                <label for="editTitle">Title:</label>
                                <input type="text" id="editTitle" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="editDescription">Description:</label>
                                <textarea id="editDescription" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="editTags">Tags:</label>
                                <input type="text" id="editTags" class="form-control" placeholder="Comma-separated tags">
                            </div>
                            <div class="edit-actions">
                                <button id="saveMetadataBtn" class="btn btn-sm">Save</button>
                                <button id="cancelMetadataBtn" class="btn btn-sm">Cancel</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-info">
                        <div class="info-item">
                            <span class="label">Dimensions:</span>
                            <span id="modalDimensions"></span>
                        </div>
                        <div class="info-item">
                            <span class="label">File Size:</span>
                            <span id="modalFileSize"></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Created:</span>
                            <span id="modalCreated"></span>
                        </div>
                        <div class="info-item" id="exifToggleWrapper" style="display:none; margin-top:8px;">
                            <button id="toggleExifBtn" class="btn btn-sm" style="background:#3949ab;color:#fff;">Show EXIF</button>
                        </div>
                        <div id="exifSection" style="display:none; margin-top:10px; max-height:220px; overflow:auto; border:1px solid #ddd; padding:8px; border-radius:4px; background:#fafafa;">
                            <h4 style="margin:0 0 6px 0; font-size:14px;">EXIF Metadata</h4>
                            <table id="exifTable" style="width:100%; border-collapse:collapse; font-size:12px;">
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="modal-share-section">
                        <label class="share-label">
                            <input type="checkbox" id="shareImageCheckbox">
                            <span class="share-text">Share this image publicly</span>
                        </label>
                        <small>Anyone with the link will be able to view a shared image.</small>
                        
                        <div id="shareLinkContainer" style="display: none; margin-top: 10px; padding: 10px; background: #e8f5e9; border-radius: 4px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 5px; color: #2e7d32;">Share Link:</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="text" id="shareLinkInput" readonly style="flex: 1; padding: 8px; border: 1px solid #4caf50; border-radius: 4px; font-size: 14px; background: white;">
                                <button id="copyShareLinkBtn" class="btn btn-sm" style="background: #4caf50; color: white; white-space: nowrap;">Copy Link</button>
                            </div>
                            <small style="color: #2e7d32; display: block; margin-top: 5px;">Share this link to let others view this image</small>
                        </div>
                    </div>

                    <div class="modal-tools">
                        <h3>Image Tools</h3>
                        <div class="tool-section">
                           <label>Resize:</label>
                            <div class="input-group resize-group">{
                                <div class="input-fields">
                                <input type="number" id="resizeWidth" placeholder="Width" min="1" style="width: 80px; font-size: 16px;">
                                <input type="number" id="resizeHeight" placeholder="Height" min="1" style="width: 80px; font-size: 16px;">
                                </div>
                                <a href="#" id="lockAspectRatio" class="aspect-ratio-lock" style="font-size: 12px;">
                                   <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.72"></path>
                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.72-1.72"></path>
                                   </svg>
                                </a>
                            </div>
                            <button id="resize-btn" class="btn btn-sm">Resize</button>
                        </div>

                       <div class="tool-section">
                            <label>Crop:</label>
                            <div class="input-group" style="margin-bottom: 10px;">
                                <button id="crop-interactive-btn" class="btn btn-sm">Select Area to Crop</button>
                                <button id="crop-cancel-btn" class="btn btn-sm" style="display: none; background-color: #f44336;">Cancel Crop</button>
                                <button id="crop-apply-btn" class="btn btn-sm" style="display: none; background-color: #4CAF50;">Apply Crop</button>
                            </div>
                        </div>

                        <div class="tool-section">
                            <label>Effects:</label>
                            <button id="rotate-btn" class="btn btn-sm">Rotate 90°</button>
                            <button id="grayscale-btn" class="btn btn-sm">Grayscale</button>
                            <button id="flip-h-btn" class="btn btn-sm">Flip H</button>
                            <button id="flip-v-btn" class="btn btn-sm">Flip V</button>
                            <button id="sharpen-btn" class="btn btn-sm">Sharpen</button>
                            <button id="sepia-btn" class="btn btn-sm">Sepia</button>
                            <button id="vignette-btn" class="btn btn-sm">Vignette</button>
                        </div>

                        <div class="tool-section">
                            <label>Blur:</label>
                            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                                <input type="range" id="blurSlider" min="1" max="10" value="2" style="flex: 1;">
                                <span id="blurValue" style="min-width: 30px;">2</span>
                            </div>
                            <button id="apply-blur-btn" class="btn btn-sm">Apply Blur</button>
                        </div>

                        <div class="tool-section">
                            <label>Adjust:</label>
                            <label>Brightness:</label>
                            <input type="range" id="brightnessSlider" min="-100" max="100" value="0">
                            <button id="apply-brightness-btn" class="btn btn-sm">Apply</button>

                            <label>Contrast:</label>
                            <input type="range" id="contrastSlider" min="-100" max="100" value="0">
                            <button id="apply-contrast-btn" class="btn btn-sm">Apply</button>
                        </div>

                        <div class="tool-section">
                            <label>Color Overlay:</label>
                            <div class="input-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <input type="color" id="overlayColorPicker" value="#ff0000" style="width: 50px; height: 30px; cursor: pointer;">
                                <input type="range" id="overlayOpacitySlider" min="0" max="100" value="30" style="flex: 1;">
                                <span id="overlayOpacityValue" style="min-width: 40px;">30%</span>
                            </div>
                            <button id="apply-overlay-btn" class="btn btn-sm">Apply Color Overlay</button>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button id="revert-image-btn" class="btn btn-warning">Revert to Original</button>
                        <button id="download-image-btn" class="btn btn-secondary">Download</button>
                        <button id="delete-image-btn" class="btn btn-danger">Delete</button>
                    </div>

                    <div class="status-message-container">
                        <!-- This paragraph will show status messages for various actions -->
                        <p id="modal-status-message" style="margin-top: 10px; font-weight: bold;"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="spinner">
        <div class="spinner-inner"></div>
    </div>

    <script src="js/app.js"></script>
    <script src="js/crop-tool.js"></script>
    <script src="js/gallery.js"></script>
    <script src="js/editor.js?v=<?php echo time(); ?>"></script>
    <script src="js/auth.js"></script>

    <script>
        /**
         * This script adds the "Revert to Original" functionality to the image modal.
         * It is designed to work with the existing functions in your other JS files.
         */

        // We need to modify the existing openImageModal function to activate our new button.
        // This code waits for the original function to be loaded, then wraps it.
        document.addEventListener('DOMContentLoaded', () => {
            // Ensure the original function exists before we try to wrap it.
            // This also ensures API_BASE is defined, likely in app.js
            if (typeof openImageModal === 'function') {
                const originalOpenImageModal = openImageModal;

                // Redefine the global function
                window.openImageModal = async function(imageId) {
                    // Call the original function first to populate the modal
                    await originalOpenImageModal(imageId);

                    // Set the imageId on the modal's main image element so other scripts (editor.js) can find it.
                    const modalImage = document.getElementById('modalImage');
                    if (modalImage) {
                        modalImage.dataset.imageId = imageId;
                    }

                    // Now, find our new button and prepare it
                    const revertButton = document.getElementById('revert-image-btn');
                    if (revertButton) {
                        revertButton.dataset.imageId = imageId;
                        // Re-attach listener to ensure it's fresh for the new imageId
                        attachRevertListener(revertButton); 
                    }

                    // Clear any old status messages
                    const statusMessage = document.getElementById('modal-status-message');
                    if (statusMessage) {
                        statusMessage.textContent = '';
                    }
                    const modalSpinner = document.getElementById('modalImageSpinner');
                    if (modalSpinner) {
                        statusMessage.textContent = '';
                    }
                };
            }

            // Lock aspect ratio
            function setupAspectRatioLock(lockButtonId, widthInputId, heightInputId) {
                const lockButton = document.getElementById(lockButtonId);
                if (!lockButton) return;

                lockButton.addEventListener('click', function handler(e) {
                    e.preventDefault();
                    const img = document.getElementById('modalImage');
                    const widthInput = document.getElementById(widthInputId);
                    const heightInput = document.getElementById(heightInputId);

                    const originalWidth = img.naturalWidth;
                    const originalHeight = img.naturalHeight;
                    if (originalWidth === 0 || originalHeight === 0) return; // Avoid division by zero
                    const aspectRatio = originalWidth / originalHeight;

                    const updateHeight = () => {
                        const newWidth = parseInt(widthInput.value);
                        if (!isNaN(newWidth) && newWidth > 0) {
                            heightInput.value = Math.round(newWidth / aspectRatio);
                        }
                    };

                    updateHeight();
                    widthInput.addEventListener('input', updateHeight);

                    // Visually disable the button after it's been clicked
                    this.style.pointerEvents = 'none';
                    this.style.opacity = '0.5';
                });
            }

            setupAspectRatioLock('lockAspectRatio', 'resizeWidth', 'resizeHeight');

        });

        /**
         * Attaches the revert logic to the button inside the modal.
         */
        function attachRevertListener(button) {
            // This is a crucial step: It removes any old click listeners to prevent
            // the event from firing multiple times if the modal is opened repeatedly.
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);

            newButton.addEventListener('click', async (event) => {
                const imageId = event.target.dataset.imageId;
                const statusMessage = document.getElementById('modal-status-message'); // Use the specific modal status message
                const modalSpinner = document.getElementById('modalImageSpinner');
                const imageElementInModal = document.getElementById('modalImage');

                if (!confirm('Are you sure you want to revert this image? All manipulations will be lost.')) {
                    return; // User clicked 'Cancel'
                }

                newButton.disabled = true;
                statusMessage.textContent = 'Reverting...';
                if (modalSpinner) modalSpinner.style.display = 'flex'; // Show spinner
                statusMessage.style.color = 'black';

                try {
                    const response = await fetch(`${API_BASE}?action=revert&id=${imageId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
                    });
                    const result = await response.json();

                    if (response.ok && result.success) {
                        statusMessage.textContent = 'Image successfully reverted!';
                        statusMessage.style.color = 'green';
                        // Force the browser to reload the image by adding a timestamp
                        if (imageElementInModal) {
                            imageElementInModal.src = `${imageElementInModal.src.split('?')[0]}?t=${new Date().getTime()}`;
                        }
                    } else {
                        statusMessage.textContent = `Error: ${result.error || 'An unknown error occurred.'}`;
                        statusMessage.style.color = 'red';
                    }
                } finally {
                    newButton.disabled = false;
                    if (modalSpinner) modalSpinner.style.display = 'none'; // Hide spinner
                }
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="js/settings.js"></script>
    <script src="js/2fa.js"></script>
    <script src="js/admin.js"></script>
    <script src="js/admin-analytics.js"></script>
    <script src="js/admin-security.js"></script>
</body>
</html>
