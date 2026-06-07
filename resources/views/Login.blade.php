<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOREMi Login</title>
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

            <h2>Welcome Back</h2>
            <p class="subtitle">Login to access the attendance system</p>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="example@doremi.com" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <span style="color: red; font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                    @error('password')
                        <span style="color: red; font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>

                <button class="login-btn" type="submit">Login</button>

                <div class="links">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">Forgot Password?</a>
                    @endif
                    
                    <a href="{{ route('register') }}">Register</a>
                </div>
            </form>

        </div>
    </div>

</div>

</body>
</html>