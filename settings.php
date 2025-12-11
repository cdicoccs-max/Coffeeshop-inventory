<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    // Verify current password
    $username = $_SESSION['user'];
    $result = $conn->query("SELECT * FROM Users WHERE username='$username' AND password=MD5('$current') LIMIT 1");
    
    if ($result->num_rows === 1) {
        if ($new === $confirm) {
            if (strlen($new) >= 6) {
                $new_hash = md5($new);
                if ($conn->query("UPDATE Users SET password='$new_hash' WHERE username='$username'")) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Error updating password: " . $conn->error;
                }
            } else {
                $error = "New password must be at least 6 characters long!";
            }
        } else {
            $error = "New passwords do not match!";
        }
    } else {
        $error = "Current password is incorrect!";
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // You can extend this to update user profile information
    $success = "Profile updated successfully!";
}

// Get user information
$username = $_SESSION['user'];
$userInfo = $conn->query("SELECT username, role, created_at FROM Users WHERE username='$username'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | BrewFlow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(139,69,19,0.1);
            padding-bottom: 10px;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            color: rgba(44,24,16,0.6);
            transition: all 0.3s ease;
        }
        
        .tab:hover {
            background: rgba(212,165,116,0.1);
            color: var(--primary);
        }
        
        .tab.active {
            background: rgba(212,165,116,0.15);
            color: var(--accent);
            border-bottom: 2px solid var(--accent);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .profile-info {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            border: 1px solid rgba(139,69,19,0.1);
            margin-bottom: 25px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, var(--accent), #E6B894);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            font-weight: 700;
        }
        
        .profile-details h3 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .profile-role {
            color: rgba(44,24,16,0.6);
            margin-bottom: 10px;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(139,69,19,0.1);
        }
        
        .stat-box {
            text-align: center;
            padding: 20px;
            background: rgba(245,241,234,0.5);
            border-radius: 12px;
        }
        
        .stat-box .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-box .label {
            color: rgba(44,24,16,0.6);
            font-size: 0.9rem;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .setting-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            border: 1px solid rgba(139,69,19,0.1);
        }
        
        .system-info {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(139,69,19,0.1);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            padding: 12px 15px;
            background: rgba(245,241,234,0.5);
            border-radius: 8px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .info-value {
            color: rgba(44,24,16,0.6);
            font-size: 0.95rem;
        }
        
        @media (max-width: 1024px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="greeting">
                    <h1>Settings & Preferences</h1>
                    <p>Manage your account and system settings</p>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
                <div style="background: rgba(93,139,102,0.1); color: var(--success); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--success);">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div style="background: rgba(196,69,54,0.1); color: var(--error); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--error);">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="settings-tabs">
                <button class="tab active" onclick="openTab('profile')">
                    <i class="fas fa-user"></i> Profile
                </button>
                <button class="tab" onclick="openTab('security')">
                    <i class="fas fa-lock"></i> Security
                </button>
                <button class="tab" onclick="openTab('preferences')">
                    <i class="fas fa-cog"></i> Preferences
                </button>
                <button class="tab" onclick="openTab('system')">
                    <i class="fas fa-info-circle"></i> System Info
                </button>
            </div>
            
            <!-- Profile Tab -->
            <div id="profileTab" class="tab-content active">
                <div class="profile-info">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($userInfo['username'], 0, 1)); ?>
                        </div>
                        <div class="profile-details">
                            <h3><?php echo htmlspecialchars($userInfo['username']); ?></h3>
                            <div class="profile-role">
                                <i class="fas fa-shield-alt"></i> 
                                <?php echo ucfirst(htmlspecialchars($userInfo['role'])); ?> Role
                            </div>
                            <div style="color: rgba(44,24,16,0.6); font-size: 0.9rem;">
                                <i class="fas fa-calendar-plus"></i> 
                                Member since <?php echo date('F j, Y', strtotime($userInfo['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <h3 class="section-title">
                            <i class="fas fa-edit"></i>
                            Update Profile
                        </h3>
                        
                        <div class="settings-grid">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($userInfo['username']); ?>" disabled>
                                <small style="color: rgba(44,24,16,0.6);">Username cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" placeholder="your.email@example.com">
                                <small style="color: rgba(44,24,16,0.6);">Add your email for notifications</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" placeholder="Your full name">
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" placeholder="+1 (234) 567-8900">
                            </div>
                        </div>
                        
                        <div style="text-align: right; margin-top: 25px;">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Security Tab -->
            <div id="securityTab" class="tab-content">
                <div class="settings-grid">
                    <div class="setting-card">
                        <h3 class="section-title">
                            <i class="fas fa-key"></i>
                            Change Password
                        </h3>
                        
                        <form method="POST">
                            <div class="form-group">
                                <label>Current Password *</label>
                                <input type="password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label>New Password *</label>
                                <input type="password" name="new_password" required>
                                <small style="color: rgba(44,24,16,0.6);">Must be at least 6 characters long</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Confirm New Password *</label>
                                <input type="password" name="confirm_password" required>
                            </div>
                            
                            <div style="margin-top: 25px;">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="setting-card">
                        <h3 class="section-title">
                            <i class="fas fa-shield-alt"></i>
                            Security Settings
                        </h3>
                        
                        <div style="margin-top: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <div>
                                    <div style="font-weight: 600;">Two-Factor Authentication</div>
                                    <div style="color: rgba(44,24,16,0.6); font-size: 0.9rem;">
                                        Add an extra layer of security
                                    </div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <div>
                                    <div style="font-weight: 600;">Login Notifications</div>
                                    <div style="color: rgba(44,24,16,0.6); font-size: 0.9rem;">
                                        Get notified of new logins
                                    </div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight: 600;">Session Timeout</div>
                                    <div style="color: rgba(44,24,16,0.6); font-size: 0.9rem;">
                                        Auto-logout after 30 minutes
                                    </div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="setting-card" style="margin-top: 25px;">
                    <h3 class="section-title">
                        <i class="fas fa-history"></i>
                        Login History
                    </h3>
                    
                    <div style="text-align: center; padding: 30px; color: rgba(44,24,16,0.5);">
                        <i class="fas fa-clock" style="font-size: 2rem; margin-bottom: 10px; color: var(--accent);"></i>
                        <p>Login history feature would be implemented here</p>
                    </div>
                </div>
            </div>
            
            <!-- Preferences Tab -->
            <div id="preferencesTab" class="tab-content">
                <div class="settings-grid">
                    <div class="setting-card">
                        <h3 class="section-title">
                            <i class="fas fa-palette"></i>
                            Appearance
                        </h3>
                        
                        <div style="margin-top: 20px;">
                            <div class="form-group">
                                <label>Theme</label>
                                <select>
                                    <option value="light">Light Mode</option>
                                    <option value="dark" selected>Dark Mode</option>
                                    <option value="auto">Auto (System)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Language</label>
                                <select>
                                    <option value="en" selected>English</option>
                                    <option value="es">Español</option>
                                    <option value="fr">Français</option>
                                    <option value="de">Deutsch</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Timezone</label>
                                <select>
                                    <option value="auto" selected>Auto-detect</option>
                                    <option value="utc">UTC</option>
                                    <option value="est">Eastern Time</option>
                                    <option value="pst">Pacific Time</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-card">
                        <h3 class="section-title">
                            <i class="fas fa-bell"></i>
                            Notifications
                        </h3>
                        
                        <div style="margin-top: 20px;">
                            <div style="margin-bottom: 20px;">
                                <div style="font-weight: 600; margin-bottom: 10px;">Email Notifications</div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <span>Low Stock Alerts</span>
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <span>Order Updates</span>
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>Weekly Reports</span>
                                    <label class="switch">
                                        <input type="checkbox">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div>
                                <div style="font-weight: 600; margin-bottom: 10px;">In-App Notifications</div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>Desktop Notifications</span>
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="setting-card" style="margin-top: 25px;">
                    <h3 class="section-title">
                        <i class="fas fa-sliders-h"></i>
                        Dashboard Preferences
                    </h3>
                    
                    <div class="settings-grid" style="margin-top: 20px;">
                        <div class="form-group">
                            <label>Default Dashboard View</label>
                            <select>
                                <option value="overview" selected>Overview</option>
                                <option value="inventory">Inventory Focus</option>
                                <option value="sales">Sales Focus</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Items Per Page</label>
                            <select>
                                <option value="10">10 items</option>
                                <option value="25" selected>25 items</option>
                                <option value="50">50 items</option>
                                <option value="100">100 items</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 25px;">
                        <button class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Preferences
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- System Info Tab -->
            <div id="systemTab" class="tab-content">
                <div class="setting-card">
                    <h3 class="section-title">
                        <i class="fas fa-server"></i>
                        System Information
                    </h3>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">PHP Version</div>
                            <div class="info-value"><?php echo phpversion(); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Database</div>
                            <div class="info-value">MySQL <?php echo $conn->server_info; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Server Software</div>
                            <div class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">System Load</div>
                            <div class="info-value"><?php echo sys_getloadavg()[0]; ?> (1 min avg)</div>
                        </div>
                    </div>
                    
                    <div class="system-info">
                        <h3 class="section-title">
                            <i class="fas fa-database"></i>
                            Database Statistics
                        </h3>
                        
                        <div class="info-grid">
                            <?php
                            $tables = ['Product', 'Category', 'Supplier', 'InventoryItem', 'Users'];
                            foreach ($tables as $table) {
                                $count = $conn->query("SELECT COUNT(*) as cnt FROM $table")->fetch_assoc()['cnt'];
                                echo "<div class='info-item'>
                                    <div class='info-label'>$table</div>
                                    <div class='info-value'>$count records</div>
                                </div>";
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="system-info">
                        <h3 class="section-title">
                            <i class="fas fa-tools"></i>
                            Maintenance
                        </h3>
                        
                        <div style="display: flex; gap: 15px; margin-top: 20px;">
                            <button class="btn btn-warning" onclick="backupDatabase()">
                                <i class="fas fa-download"></i> Backup Database
                            </button>
                            <button class="btn" onclick="clearCache()" style="background: rgba(44,24,16,0.1); color: var(--primary);">
                                <i class="fas fa-broom"></i> Clear Cache
                            </button>
                            <button class="btn btn-error" onclick="resetSystem()">
                                <i class="fas fa-redo"></i> Reset Demo Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openTab(tabName) {
            // Hide all tabs
            var tabs = document.getElementsByClassName('tab-content');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            
            // Remove active class from all tab buttons
            var tabButtons = document.getElementsByClassName('tab');
            for (var i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove('active');
            }
            
            // Show selected tab
            document.getElementById(tabName + 'Tab').classList.add('active');
            
            // Add active class to clicked button
            event.currentTarget.classList.add('active');
        }
        
        function backupDatabase() {
            if (confirm('This will create a backup of your database. Continue?')) {
                alert('Database backup functionality would be implemented here.');
            }
        }
        
        function clearCache() {
            if (confirm('Clear all cached data?')) {
                alert('Cache cleared!');
            }
        }
        
        function resetSystem() {
            if (confirm('WARNING: This will reset all demo data. This action cannot be undone. Continue?')) {
                alert('System reset functionality would be implemented here.');
            }
        }
        
        // Switch toggle styles
        const style = document.createElement('style');
        style.textContent = `
            .switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }
            
            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 34px;
            }
            
            .slider:before {
                position: absolute;
                content: "";
                height: 16px;
                width: 16px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            
            input:checked + .slider {
                background-color: var(--accent);
            }
            
            input:checked + .slider:before {
                transform: translateX(26px);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>