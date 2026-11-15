<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'TheaterLink') }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f6f5f1;
            color: #1b1b18;
        }
        main {
            width: min(680px, 100%);
            padding: clamp(2rem, 6vw, 4rem);
            border: 1px solid #e0ded7;
            border-radius: 1.25rem;
            background: #fff;
            box-shadow: 0 18px 50px rgba(27, 27, 24, 0.08);
        }
        .eyebrow {
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #a33a2b;
        }
        h1 { margin: .6rem 0 1rem; font-size: clamp(2rem, 6vw, 3.4rem); line-height: 1.05; }
        p { margin: 0 0 1.4rem; font-size: 1.05rem; line-height: 1.7; color: #5e5b53; }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; }
        a {
            display: inline-flex;
            align-items: center;
            padding: .8rem 1.1rem;
            border-radius: .7rem;
            background: #1b1b18;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
        a.secondary { background: #fff; color: #1b1b18; border: 1px solid #d9d6ce; }
        small { display: block; margin-top: 1.5rem; color: #858178; }
        @media (prefers-color-scheme: dark) {
            body { background: #11110f; color: #f2f0ea; }
            main { background: #1a1a17; border-color: #34322d; }
            p, small { color: #b5b0a4; }
            a.secondary { background: #1a1a17; color: #f2f0ea; border-color: #4a463e; }
        }
    </style>
</head>
<body>
    <main>
        <span class="eyebrow">TheaterLink API</span>
        <h1>One platform for theaters, groups, and audiences.</h1>
        <p>
            TheaterLink connects theater teams, performing groups, and individual audience members.
            The API covers workspaces, halls, schedules, reservations, events, ticket tiers, and bookings.
            Payments are handled outside the platform.
        </p>
        <div class="actions">
            <a href="{{ url('/docs') }}">Open API documentation</a>
            <a class="secondary" href="{{ url('/up') }}">Health check</a>
        </div>
        <small>{{ config('app.name') }} · {{ config('api.version') }}</small>
    </main>
</body>
</html>
