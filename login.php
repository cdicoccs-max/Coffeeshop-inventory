<?php
session_start();
include 'db.php';
$error = '';
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $result = $conn->query("SELECT * FROM Users WHERE username='$username' AND password=MD5('$password') LIMIT 1");
    if ($result && $result->num_rows === 1) {
        $_SESSION['user'] = $username;
        $_SESSION['role'] = $result->fetch_assoc()['role'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BrewFlow | Coffee Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2C1810;
            --secondary: #8B4513;
            --accent: #D4A574;
            --light: #F5F1EA;
            --dark: #1A0F0A;
            --success: #5D8B66;
            --error: #C44536;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1A0F0A 0%, #2C1810 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--light);
        }
        
        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            max-width: 1100px;
            width: 90%;
            min-height: 650px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
        }
        
        .left-panel {
            background: linear-gradient(45deg, rgba(44,24,16,0.9) 0%, rgba(26,15,10,0.95) 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="%23D4A574" opacity="0.03"/></svg>');
            background-size: cover;
        }
        
        .branding {
            margin-bottom: 50px;
            z-index: 2;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--accent);
        }
        
        .logo i {
            background: linear-gradient(45deg, var(--accent), #E6B894);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.5rem;
        }
        
        .tagline {
            font-size: 1.1rem;
            color: rgba(245,241,234,0.7);
            line-height: 1.6;
            max-width: 320px;
        }
        
        .coffee-animation {
            position: absolute;
            bottom: -50px;
            left: -30px;
            opacity: 0.05;
            font-size: 18rem;
            color: var(--accent);
            z-index: 1;
        }
        
        .right-panel {
            background: var(--light);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-container {
            max-width: 380px;
            width: 100%;
            margin: 0 auto;
        }
        
        .welcome-text {
            color: var(--primary);
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: rgba(44,24,16,0.6);
            font-size: 1rem;
            margin-bottom: 40px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            font-size: 1.1rem;
        }
        
        input {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 2px solid rgba(139,69,19,0.1);
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            color: var(--primary);
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(212,165,116,0.1);
        }
        
        input::placeholder {
            color: rgba(44,24,16,0.4);
        }
        
        .error-message {
            background: rgba(196,69,54,0.1);
            color: var(--error);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--error);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(45deg, var(--secondary), var(--primary));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(139,69,19,0.3);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(139,69,19,0.1);
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .feature i {
            color: var(--secondary);
            font-size: 1.2rem;
        }
        
        .feature-text {
            color: rgba(44,24,16,0.7);
            font-size: 0.9rem;
        }
        
        .copyright {
            text-align: center;
            margin-top: 40px;
            color: rgba(44,24,16,0.5);
            font-size: 0.9rem;
        }
        
        @media (max-width: 900px) {
            .login-wrapper {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
            
            .left-panel {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="left-panel">
            <div class="coffee-animation">
                <i class="fas fa-mug-hot"></i>
            </div>
            <div class="branding">
                <div class="logo">
                    <i class="fas fa-mug-hot"></i>
                    BrewFlow
                </div>
                <p class="tagline">
                    Precision coffee management system for specialty cafes. 
                    Track inventory, manage suppliers, and optimize your brew operations.
                </p>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="login-container">
                <h1 class="welcome-text">Welcome Back</h1>
                <p class="subtitle">Sign in to access your coffee management dashboard</p>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" placeholder="Username" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" placeholder="Password" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="login" class="login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-boxes"></i>
                        <span class="feature-text">Inventory Tracking</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <span class="feature-text">Analytics Dashboard</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-truck"></i>
                        <span class="feature-text">Supplier Management</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-cogs"></i>
                        <span class="feature-text">Brew Operations</span>
                    </div>
                </div>
                
                <p class="copyright">
                    &copy; <?php echo date('Y'); ?> BrewFlow Coffee Systems. All beans reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>