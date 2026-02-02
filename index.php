<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hostel Management System - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="web/css/login_style.css"> 
    <script type="application/x-javascript">
        addEventListener("load", function () {
            setTimeout(hideURLbar, 0);
        }, false);

        function hideURLbar() {
            window.scrollTo(0, 1);
        }
    </script>
    <link rel="stylesheet" href="web_home/css_home/bootstrap.css"> 
    <link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> 
    <link rel="stylesheet" href="web_profile/css/font-awesome.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f8f9fa;
    }
    .navbar {
        background-color: #003366;
        padding: 15px 0;
    }
    .navbar-brand, .nav-link {
        color: #ffffffff !important;
        font-weight: 600;
    }
    .navbar-brand {
        font-size: 1.5rem;
    }
    .nav-link:hover {
        color: #ffcc00 !important;
    }
    .dropdown-menu {
        background-color: #003366;
    }
    .dropdown-menu .dropdown-item {
        color: #ffffff;
    }
    .dropdown-menu .dropdown-item:hover {
        background-color: #001f4d;
        color: #ffcc00;
    }
    .notification-badge {
        background-color: #ffc107; /* Yellow color for notifications */
        color: #212529; /* Dark text for contrast */
        font-size: 0.75em;
        padding: .2em .6em;
        border-radius: .25rem;
        margin-left: 5px;
        vertical-align: super;
    }
    .heading {
        text-align: center;
        color: #003366;
        font-weight: 600;
        margin-bottom: 50px;
        font-size: 2.5rem;
    }
    .hostel-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 30px;
    }
    .hostel-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }
    .hostel-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-bottom: 3px solid #003366;
    }
    .hostel-content {
        padding: 25px;
    }
    .hostel-title {
        color: #003366;
        font-weight: 600;
        font-size: 1.5rem;
        margin-bottom: 15px;
        text-align: center;
    }
    .hostel-details {
        color: #666;
        font-size: 14px;
        line-height: 1.6;
    }
    .hostel-details p {
        margin-bottom: 10px;
    }
    .hostel-details strong {
        color: #003366;
        font-weight: 600;
    }
    .hostel-features {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e9ecef;
    }
    .feature-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        font-size: 13px;
    }
    .feature-icon {
        color: #28a745;
        margin-right: 8px;
    }
    
</style>
    <style>
        /* Note: The rest of the body layout (flex, min-h-screen) is now in login_style.css */
        body {
            font-family: 'Poppins', sans-serif;
            /* VERIFY THIS PATH IS CORRECT relative to the HTML file location */
            background-image: url('web_profile/images/3.jpg'); 
            background-size: cover;
            background-position: center;
        }
        /* Error text style - Kept for visibility */
        .error-msg {
            color: #ef4444; /* red-500 */
            font-size: 0.875rem; /* text-sm */
            display: none;
            margin-top: 0.25rem; /* mt-1 */
        }
    </style>
</head>

<body> 
     <header style="margin:0; padding:0; position:fixed; top:0; left:0; right:0; z-index:1000;">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="home1.php">PLYS</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                    <span class="navbar-toggler-icon" style="color:white;"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item active">
                            <a class="nav-link" href="home1.php">Home <span class="sr-only">(current)</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="hostel_details.php">Hostels</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Login</a>
                        </li>
        </nav>
    </header>
    <div class="overlay"></div>

    <div class="login-container">
        
        <div class="header-section">
            <h1>Hostel Management System</h1>
            <p>Student Login</p>
        </div>

        <?php
        // Server-side messages (from redirect params)
        $serverMsg = '';
        $serverType = 'error';
        if (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'emptyfields': $serverMsg = 'Please fill in all fields.'; break;
                case 'invalidroll': $serverMsg = 'Invalid Roll Number format.'; break;
                case 'wrongpwd': $serverMsg = 'Incorrect password. Please try again.'; break;
                case 'nouser': $serverMsg = 'No user found with that Roll Number.'; break;
                case 'sqlerror': $serverMsg = 'Unexpected database error. Try again later.'; break;
                default: $serverMsg = 'An error occurred.'; break;
            }
            $serverType = 'error';
        } elseif (isset($_GET['signup']) && $_GET['signup'] === 'success') {
            $serverMsg = 'Signup successful! Please check your email for verification.';
            $serverType = 'success';
        }
        ?>

        <?php if (!empty($serverMsg)) : ?>
            <div id="serverAlert" class="alert <?php echo htmlspecialchars($serverType); ?>" role="alert">
                <div class="icon"><?php echo $serverType === 'success' ? '✅' : '⚠️'; ?></div>
                <div class="content"><?php echo htmlspecialchars($serverMsg); ?></div>
                <button class="close" aria-label="Close" onclick="document.getElementById('serverAlert').classList.add('fade-out'); setTimeout(function(){document.getElementById('serverAlert').remove();},450)">&times;</button>
            </div>
            <script>
                // Auto-dismiss after 6 seconds
                setTimeout(function(){
                    var el = document.getElementById('serverAlert');
                    if (el) { el.classList.add('fade-out'); setTimeout(function(){ if(el.parentNode) el.parentNode.removeChild(el); },450); }
                }, 6000);
            </script>
        <?php endif; ?>

        <form id="loginForm" action="includes/login.inc.php" method="POST" class="login-form">
            
            <div class="form-group">
                <label for="student_roll_no">Student Roll No:</label>
                <div>
                    <input type="text" id="student_roll_no" class="form-input" name="student_roll_no" placeholder="Roll No" required />
                </div>
                <small id="rollError" class="error-msg"></small>
            </div>

            <div class="form-group">
                <label for="pwd">Password:</label>
                <div>
                    <input type="password" id="pwd" class="form-input" name="pwd" placeholder="Password" required />
                </div>
                <small id="pwdError" class="error-msg"></small>
            </div>

            <button type="submit" name="login-submit" class="login-btn">
                Login
            </button>
        </form>

        <div class="links-section">
            <p>
                Login as 
                <a href="login-hostel_manager.php">Hostel-Manager/Admin</a>
            </p>
            <p>
                Don't have an account? 
                <a href="signup.php">Sign up</a>
            </p>
        </div>
    </div>

    <footer class="page-footer">
        <p class="copyright-agileinfo"> &copy; 2025 Project. All Rights Reserved | Design by 
            <a href="#">Sujal Sthapit</a>
        </p>
    </footer>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function (event) {
            let roll = document.getElementById('student_roll_no').value.trim();
            let pwd = document.getElementById('pwd').value.trim();

            let rollError = document.getElementById('rollError');
            let pwdError = document.getElementById('pwdError');

            let valid = true;

            // Reset error messages
            rollError.style.display = "none";
            pwdError.style.display = "none";

            // Validate roll number
            if (roll === "") {
                rollError.innerText = "Roll number is required.";
                rollError.style.display = "block";
                valid = false;
            } else if (!/^[0-9]+$/.test(roll)) {
                rollError.innerText = "Roll number must contain only digits.";
                rollError.style.display = "block";
                valid = false;
            }

            // Validate password
            if (pwd === "") {
                pwdError.innerText = "Password is required.";
                pwdError.style.display = "block";
                valid = false;
            }

            // Stop form submission if invalid
            if (!valid) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>