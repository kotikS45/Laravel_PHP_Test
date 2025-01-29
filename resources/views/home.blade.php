<html>
<head>
    <title>Home</title>
</head>

<body>

@if(auth()->check())
    <p>Welcome, {{ auth()->user()->name }}!</p>
    <p>Email: {{ auth()->user()->email }}</p>
    @if(auth()->user()->image)
        <img src="{{ asset('uploads/images/' . auth()->user()->image) }}" alt="User Avatar" width="100">
    @else
        <p>No avatar available</p>
    @endif
@else
    <p>You are not logged in. <a href="{{ route('login.form') }}">Login</a> or <a href="{{ route('register.form') }}">Register</a></p>
@endif
</body>

</html>
