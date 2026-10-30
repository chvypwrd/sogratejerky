<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance in Progress | SOGRATE</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #F9E8CC;
            background-color: #2F2E2E;
            font-family: "Montserrat", "Segoe UI", system-ui, sans-serif;
        }
        .container { max-width: 520px; text-align: center; }
        .logo { width: min(100%, 360px); height: auto; margin: 0 auto 1.75rem; display: block; }
        h1 { margin-bottom: 0.75rem; color: #FFFFFF; font-size: clamp(1.75rem, 5vw, 2.4rem); line-height: 1.15; }
        p { margin-bottom: 1rem; color: #D6D8C1; font-size: 1.05rem; line-height: 1.65; }
        .tagline {
            margin-bottom: 1.75rem;
            color: #F9E8CC;
            font-family: "Caveat", "Segoe Script", cursive;
            font-size: 1.7rem;
            font-weight: 600;
            line-height: 1.1;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ asset('brand/sograte-logo-reversed.png') }}" alt="SOGRATE" class="logo">
        <h1>Maintenance in Progress</h1>
        <p>We'll be back shortly.</p>
        <p class="tagline">Texas Made. Seriously Good Jerky.</p>
    </div>
</body>
</html>
