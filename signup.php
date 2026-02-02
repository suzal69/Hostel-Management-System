<!DOCTYPE html>
<html lang="en">
<head>
    <title>HMS | SIGNUP PAGE</title>
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
        
        /* Custom Popup Styles */
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .popup-content {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 400px;
            width: 90%;
            animation: popupSlideIn 0.3s ease-out;
        }
        
        .popup-icon {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .popup-title {
            color: #dc3545;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .popup-message {
            color: #495057;
            font-size: 16px;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        
        .popup-button {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .popup-button:hover {
            background: linear-gradient(135deg, #ffcc00 0%, #ffdb4d 100%);
            color: #003366;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 204, 0, 0.3);
        }
        
        @keyframes popupSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
    
    <script>
        function showGenderPopup() {
            const popup = document.getElementById('genderPopup');
            popup.style.display = 'flex';
        }
        
        function hideGenderPopup() {
            const popup = document.getElementById('genderPopup');
            popup.style.display = 'none';
            document.getElementById('gender').value = '';
        }
        
        function validateGender() {
            const gender = document.getElementById('gender').value;
            
            if (gender === 'Female' || gender === 'Other') {
                showGenderPopup();
                return false;
            }
            return true;
        }
        
        function validateForm() {
            const gender = document.getElementById('gender').value;
            
            if (gender === 'Female' || gender === 'Other') {
                showGenderPopup();
                return false;
            }
            return true;
        }
        
        // Add event listener to gender field
        document.addEventListener('DOMContentLoaded', function() {
            const genderField = document.getElementById('gender');
            genderField.addEventListener('change', validateGender);
        });
    </script>
</head>

<body>

    <div class="overlay"></div>

    <div class="login-container">
        
        <div class="header-section">
            <h1>Hostel Management System</h1>
            <p>Sign Up Here</p>
        </div>

        <form action="includes/signup.inc.php" method="POST" class="login-form" onsubmit="return validateForm()">
            
            <div class="form-group">
                <label for="student_roll_no">Student Roll No</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-id-badge text-gray-400"></i>
                    </div>
                    <input type="text" id="student_roll_no" class="form-input with-icon" name="student_roll_no" placeholder="Roll No" required="required" />
                </div>
            </div>

            <div class="form-group">
                <label for="student_fname">First Name</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input type="text" id="student_fname" class="form-input with-icon" name="student_fname" placeholder="First Name" required="required" />
                </div>
            </div>

            <div class="form-group">
                <label for="student_lname">Last Name</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input type="text" id="student_lname" class="form-input with-icon" name="student_lname" placeholder="Last Name" required="required" />
                </div>
            </div>

            <div class="form-group">
                <label for="mobile_no">Mobile No</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-phone text-gray-400"></i>
                    </div>
                    <input type="text" id="mobile_no" class="form-input with-icon" name="mobile_no" placeholder="Mobile number" pattern="9(?!(\d)\1{8})\d{9}" title="Mobile number must start with 9, be 10 digits, and not have all identical digits" required="required" />
                </div>
            </div>

            <div class="form-group">
                <label for="department">Department</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-graduation-cap text-gray-400"></i>
                    </div>
                    <select id="department" class="form-input with-icon" name="department" required="required">
                        <option value="">Select Department</option>
                        <option value="BBA">BBA</option>
                        <option value="BIM">BIM</option>
                        <option value="BCA">BCA</option>
                        <option value="BBM">BBM</option>
                        <option value="CSIT">CSIT</option>
                        <option value="BBS">BBS</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="year_of_study">Year of Study</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-calendar text-gray-400"></i>
                    </div>
                    <select id="year_of_study" class="form-input with-icon" name="year_of_study" required="required">
                        <option value="">Select Year of Study</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="gender">Gender</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-venus-mars text-gray-400"></i>
                    </div>
                    <select id="gender" class="form-input with-icon" name="gender" required="required">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-gray-400"></i>
                    </div>
                    <input type="Email" id="email" class="form-input with-icon" name="email" placeholder="Email" required="required" />
                </div>
            </div> 
            
            <div class="form-group">
                <label for="pwd">Password</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" id="pwd" class="form-input with-icon" name="pwd" placeholder="Password" required="required" />
                </div>
            </div>

            <div class="form-group">
                <label for="confirmpwd">Confirm Password</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" id="confirmpwd" class="form-input with-icon" name="confirmpwd" placeholder="Confirm Password" required="required" />
                </div>
            </div>      
            
            <button type="submit" name="signup-submit" class="login-btn">
                Sign Up
            </button>
        </form>

        <div class="links-section">
            <p>Already a member?
                <a href="index.php">Login</a>
            </p>
        </div>
    </div>

    <footer class="page-footer">
        <p class="copyright-agileinfo"> &copy; 2025 Project. All Rights Reserved | Design by 
            <a href="#">Sujal Sthapit</a>
        </p>
    </footer>

    <!-- Custom Gender Validation Popup -->
    <div id="genderPopup" class="popup-overlay">
        <div class="popup-content">
            <div class="popup-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="popup-title">Gender Restriction</div>
            <div class="popup-message">
                This is a boys hostel, only boys allowed! Please select "Male" as gender to continue with registration.
            </div>
            <button class="popup-button" onclick="hideGenderPopup()">I Understand</button>
        </div>
    </div>

</body>
</html>