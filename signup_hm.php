<?php
  require 'includes/config.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>HMS | Manager SIGNUP PAGE</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="keywords" content="Sign Up Form" />
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="web_home/css_home/fontawesome-all.css">
    
    <link rel="stylesheet" href="web/css/login_style.css">
    
    <style>
        /* Note: Most styling is now in the external CSS file */
        body {
            font-family: 'Poppins', sans-serif;
            /* VERIFY THIS PATH IS CORRECT relative to the HTML file location */
            background-image: url('web_profile/images/3.jpg');
            background-size: cover;
            background-position: center;
        }
        
        /* Specific styles for the icon padding, required for .form-input.with-icon */
        .form-input.with-icon {
            padding-left: 2.5rem; /* 40px for the icon */
            box-sizing: border-box; /* Ensure it respects the container width */
        }
        /* Ensure the form containers have margin/spacing similar to the original Tailwind space-y-6 */
        .login-form > div {
            margin-bottom: 1.5rem; /* Equivalent to space-y-6 */
        }
        .login-form {
             margin-bottom: 0; /* Clear bottom margin added by the above rule */
        }
    </style>
</head>

<body>

    <div class="overlay"></div>

    <div class="login-container">
        
        <div class="header-section">
            <h1>Hostel Management System</h1>
            <p>Manager Sign Up Here</p>
            <p style="font-size:0.95rem;color:#6b7280;margin-top:6px;">A verification email will be sent to your address — please verify before an admin can approve your account.</p>
        </div>

        <?php if (!empty($_GET['error']) && $_GET['error'] === 'mailsendfailed'): ?>
            <div style="max-width:720px;margin:0 auto 1rem;padding:12px;background:#fff4f4;border-left:4px solid #f43f5e;color:#1f2937;">⚠️ We couldn't send the verification email right now. Your signup is saved and an admin can resend the verification email later. If you need help, please contact the site administrator.</div>
        <?php endif; ?>

        <?php if (!empty($_GET['token']) && (!empty($_GET['error']) && in_array($_GET['error'], ['mailsendfailed','toofast','maxattempts']) || !empty($_GET['resend']))): ?>
            <?php $token = htmlspecialchars($_GET['token']); ?>
            <div style="max-width:720px;margin:0 auto 1rem;padding:12px;background:#f8fafc;border-left:4px solid #0ea5e9;color:#0b1020;">
                <?php if (!empty($_GET['resend']) && $_GET['resend'] === 'success'): ?>
                    ✅ Verification email resent. Please check your inbox (and spam) and follow the link to verify your account.
                <?php elseif (!empty($_GET['resend']) && $_GET['resend'] === 'failed'): ?>
                    ⚠️ We tried to resend but it failed. An admin can still resend from the admin dashboard. Please contact the site administrator.
                <?php elseif (!empty($_GET['error']) && $_GET['error'] === 'toofast'): ?>
                    ⚠️ You're requesting resends too quickly. Please wait a moment and try again.
                <?php elseif (!empty($_GET['error']) && $_GET['error'] === 'maxattempts'): ?>
                    ⚠️ Maximum resend attempts reached. Please contact the site administrator for help.
                <?php else: ?>
                    If you'd like, you can request we resend your verification email now.
                <?php endif; ?>
                <form method="POST" action="includes/resend_hm_verification.php" style="display:inline-block;margin-left:1rem;">
                    <input type="hidden" name="token" value="<?= $token ?>">
                    <button type="submit" class="login-btn" style="display:inline-block;padding:6px 12px;margin-left:10px;">Resend verification email</button>
                </form>
            </div>
        <?php endif; ?>

        <form action="includes/hm_signup.php" method="POST" class="login-form">
            
            <div class="form-group">
                <label for="hm_uname">Username</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input type="text" id="hm_uname" class="form-input with-icon" name="hm_uname" placeholder="Username" required="required" />
                </div>
            </div>

            <div class="form-group">
                <label for="hm_fname">First Name</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input type="text" id="hm_fname" class="form-input with-icon" name="hm_fname" placeholder="First Name" required="required" />
                </div>
            </div>

            <div class="form-group">
                <label for="hm_lname">Last Name</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input type="text" id="hm_lname" class="form-input with-icon" name="hm_lname" placeholder="Last Name" required="required" />
                </div>
            </div>

            <div class="form-group">
                <label for="hm_mobile">Mobile No</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-phone text-gray-400"></i>
                    </div>
                    <input type="text" id="hm_mobile" class="form-input with-icon" name="hm_mobile" placeholder="Mobile No" required="required" />
                </div>
            </div>

            <div class="form-group">
                <label for="hostel_name">Hostel Name</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-hotel text-gray-400"></i>
                    </div>
                    <select id="hostel_name" class="form-input with-icon" name="hostel_id" required="required">
                        <option value="" disabled selected>Select Hostel</option>
                        <?php
                          $sql = "SELECT Hostel_id, Hostel_name FROM hostel WHERE Hostel_name != 'admin'";
                          $result = mysqli_query($conn, $sql);
                          if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                              echo '<option value="' . $row['Hostel_id'] . '">' . $row['Hostel_name'] . '</option>';
                            }
                          }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="Email">Email</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-gray-400"></i>
                    </div>
                    <input type="Email" id="Email" class="form-input with-icon" name="Email" placeholder="Email" required="required" />
                </div>
            </div> 
            
            <div class="form-group">
                <label for="pass">Password</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" id="pass" class="form-input with-icon" name="pass" placeholder="Password" required="required" />
                </div>
            </div>

            <div class="form-group">
                <label for="confpass">Confirm Password</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" id="confpass" class="form-input with-icon" name="confpass" placeholder="Confirm Password" required="required" />
                </div>
            </div>      
            
            <button type="submit" name="hm_signup_submit" class="login-btn">
                Sign Up
            </button>
        </form>

        <div class="links-section">
            <p>Already a member?
                <a href="login-hostel_manager.php">Login</a>
            </p>
        </div>
    </div>

    <footer class="page-footer">
        <p class="copyright-agileinfo"> &copy; 2025 Project. All Rights Reserved | Design by 
            <a href="#">Sujal Sthapit</a>
        </p>
    </footer>

</body>
</html>
