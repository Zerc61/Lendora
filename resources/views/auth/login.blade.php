{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Lendora</title>
    <style>
        *{box-sizing:border-box}
        body{font-family:sans-serif;background:#0B0E14;color:#F5F7FA;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0}
        .card{background:#121722;padding:36px;border-radius:14px;border:1px solid #252B38;width:340px}
        h1{margin:0;color:#7C5CFC}
        label{color:#8B93A7;font-size:13px;display:block;margin-top:10px}
        input{width:100%;padding:10px;margin:5px 0;background:#0B0E14;color:#F5F7FA;border:1px solid #252B38;border-radius:6px}
        button{width:100%;background:#7C5CFC;color:#fff;border:0;padding:12px;border-radius:6px;cursor:pointer;margin-top:14px}
        .err{background:#7f1d1d;color:#fee2e2;padding:8px 10px;border-radius:6px;margin-top:12px;font-size:13px}
    </style>
</head>
<body>
<div class="card">
    <h1>Lendora</h1>
    <p style="color:#8B93A7;font-size:14px">Manage. Reserve. Maintain.</p>
    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        <label>Password</label>
        <input type="password" name="password" required>
        <label style="display:flex;align-items:center;gap:6px">
            <input type="checkbox" name="remember" style="width:auto"> Ingat saya
        </label>
        <button type="submit">Masuk</button>
    </form>
    @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
</div>
</body>
</html>