<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trello Lite API</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #111827;
            --accent: #22d3ee;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --border: #1f2937;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, sans-serif;
            background: radial-gradient(circle at 20% 20%, rgba(34,211,238,0.12), transparent 25%),
                        radial-gradient(circle at 85% 15%, rgba(59,130,246,0.1), transparent 25%),
                        var(--bg);
            color: var(--text);
        }
        .container {
            max-width: 920px;
            margin: 0 auto;
            padding: 64px 20px 96px;
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(34,211,238,0.12);
            color: var(--accent);
            font-weight: 600;
            font-size: 14px;
            width: fit-content;
        }
        h1 {
            font-size: 40px;
            margin: 16px 0 0;
            letter-spacing: -0.02em;
        }
        .lead {
            margin: 16px 0 0;
            max-width: 700px;
            color: var(--muted);
            line-height: 1.6;
        }
        .actions {
            margin-top: 28px;
        }
        a.button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            color: var(--bg);
            background: linear-gradient(135deg, #22d3ee, #0ea5e9);
            box-shadow: 0 8px 26px rgba(14,165,233,0.35);
            transition: transform 160ms ease, box-shadow 160ms ease;
        }
        a.button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(14,165,233,0.45);
        }
        .grid {
            margin: 48px 0;
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 10px 28px rgba(0,0,0,0.35);
        }
        .card h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        .card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.55;
        }
        .meta {
            margin-top: 32px;
            font-size: 14px;
            color: var(--muted);
        }
        @media (max-width: 640px) {
            h1 { font-size: 32px; }
            .container { padding: 48px 16px 72px; }
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="eyebrow">Trello Lite API · Laravel + Sanctum</div>

        <h1>Backend API untuk Projects & Tasks</h1>
        <p class="lead">
            REST API dengan autentikasi token (Sanctum), CRUD Projects & Tasks, validasi kuat,
            policy access per-user, dan respons JSON yang konsisten. Swagger disediakan
            untuk eksplorasi endpoint secara langsung.
        </p>

        <div class="actions">
            <a class="button" href="/docs" target="_blank" rel="noopener">Buka Swagger UI</a>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Auth</h3>
                <p>Sanctum login/register/me dengan token aman dan session cleaning yang benar.</p>
            </div>
            <div class="card">
                <h3>Projects</h3>
                <p>CRUD Projects dengan ownership policy dan relasi tasks.</p>
            </div>
            <div class="card">
                <h3>Tasks</h3>
                <p>CRUD Tasks dengan status, description, due_date, dan validasi yang ketat.</p>
            </div>
            <div class="card">
                <h3>Error Handling</h3>
                <p>Format JSON seragam untuk 401, 403, 404, 422 termasuk detail error.</p>
            </div>
        </div>

        <div class="meta">
            Base URL: <code>http://127.0.0.1:8000</code> · Swagger UI: <code>/docs</code>
        </div>

    </div>
</body>
</html>
