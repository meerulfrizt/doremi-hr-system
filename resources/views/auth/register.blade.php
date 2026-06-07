<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOREMi Register</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

<div class="container">

    <div class="left-panel">
        <div class="logo-box">
            <img src="{{ asset('img/Doremi logo.png') }}" class="logo">
            <h2>DOREMi Services & Rental</h2>
            <p>Professional Event Technology Solutions</p>
        </div>
    </div>

    <div class="right-panel">
        <div class="login-box">

            <h2>Create Account</h2>
            <p class="subtitle">Join the attendance system</p>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="John Doe" value="{{ old('name') }}" required autofocus>
                    @error('name')
                        <span style="color: red; font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="example@doremi.com" value="{{ old('email') }}" required>
                    @error('email')
                        <span style="color: red; font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Create a password" required>
                    @error('password')
                        <span style="color: red; font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" placeholder="Retype password" required>
                    @error('password_confirmation')
                        <span style="color: red; font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>

                <button class="login-btn" type="submit">Register</button>

                <div class="links" style="justify-content: center; margin-top: 20px;">
                    <a href="{{ route('login') }}">Already have an account? Login here</a>
                </div>
            </form>

        </div>
    </div>

</div>

</body>
</html>