<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - VetCare Clinic</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
        }
        .register-container {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-header h1 {
            font-size: 1.875rem;
            color: #1f2937;
            margin: 0;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            width: 100%;
            padding: 0.75rem;
            background-color: #667eea;
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #5568d3;
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .password-requirements {
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            color: #6b7280;
        }
        .requirement:last-child {
            margin-bottom: 0;
        }
        .requirement-icon {
            margin-right: 0.5rem;
            font-weight: bold;
            width: 1.25rem;
            text-align: center;
        }
        .requirement.met {
            color: #059669;
        }
        .requirement.met .requirement-icon {
            color: #10b981;
        }
        .requirement.unmet .requirement-icon {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>VetCare Clinic</h1>
            <p>Create your account</p>
        </div>

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            </div>

            <div class="form-group">
                <label for="contact_no">Phone Number</label>
                <input type="tel" id="contact_no" name="contact_no" value="{{ old('contact_no') }}" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required oninput="validatePassword()">
                
                <div class="password-requirements">
                    <div class="requirement unmet" id="req-length">
                        <span class="requirement-icon">✗</span>
                        <span>At least 8 characters</span>
                    </div>
                    <div class="requirement unmet" id="req-uppercase">
                        <span class="requirement-icon">✗</span>
                        <span>At least one uppercase letter (A-Z)</span>
                    </div>
                    <div class="requirement unmet" id="req-lowercase">
                        <span class="requirement-icon">✗</span>
                        <span>At least one lowercase letter (a-z)</span>
                    </div>

    <script>
        function validatePassword() {
            const password = document.getElementById('password').value;
            
            // Check length (at least 8 characters)
            const lengthReq = document.getElementById('req-length');
            if (password.length >= 8) {
                lengthReq.classList.remove('unmet');
                lengthReq.classList.add('met');
            } else {
                lengthReq.classList.remove('met');
                lengthReq.classList.add('unmet');
            }
            
            // Check uppercase
            const uppercaseReq = document.getElementById('req-uppercase');
            if (/[A-Z]/.test(password)) {
                uppercaseReq.classList.remove('unmet');
                uppercaseReq.classList.add('met');
            } else {
                uppercaseReq.classList.remove('met');
                uppercaseReq.classList.add('unmet');
            }
            
            // Check lowercase
            const lowercaseReq = document.getElementById('req-lowercase');
            if (/[a-z]/.test(password)) {
                lowercaseReq.classList.remove('unmet');
                lowercaseReq.classList.add('met');
            } else {
                lowercaseReq.classList.remove('met');
                lowercaseReq.classList.add('unmet');
            }
            
            // Check number
            const numberReq = document.getElementById('req-number');
            if (/[0-9]/.test(password)) {
                numberReq.classList.remove('unmet');
                numberReq.classList.add('met');
            } else {
                numberReq.classList.remove('met');
                numberReq.classList.add('unmet');
            }
            
            // Check special characters
            const specialReq = document.getElementById('req-special');
            if (/[!@#$%^&*()_+\-=\[\]{};:'",.<>?\/\\|`~]/.test(password)) {
                specialReq.classList.remove('unmet');
                specialReq.classList.add('met');
            } else {
                specialReq.classList.remove('met');
                specialReq.classList.add('unmet');
            }
        }
    </script>
                    <div class="requirement unmet" id="req-number">
                        <span class="requirement-icon">✗</span>
                        <span>At least one number (0-9)</span>
                    </div>
                    <div class="requirement unmet" id="req-special">
                        <span class="requirement-icon">✗</span>
                        <span>At least one special character (!@#$%^&*)</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="{{ route('login') }}">Login here</a>
        </div>
    </div>
</body>
</html>
