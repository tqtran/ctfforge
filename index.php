<?php
$audiences = [
    [
        'title' => 'Challenge Authors',
        'description' => 'Turn your ideas into memorable challenges, earn badges, unlock commissions, and build a reputation for crafting great learning experiences.',
    ],
    [
        'title' => 'Organizers',
        'description' => 'Launch polished CTFs quickly with a platform designed to simplify setup, manage events smoothly, and keep players engaged from start to finish.',
    ],
    [
        'title' => 'Participants',
        'description' => 'Learn new skills, solve creative problems, team up with friends, and enjoy the thrill of hands-on cybersecurity practice.',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTFForge</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #081122;
            --panel: rgba(10, 22, 44, 0.86);
            --panel-border: rgba(148, 163, 184, 0.18);
            --accent: #5eead4;
            --accent-strong: #22d3ee;
            --text: #e2e8f0;
            --muted: #cbd5e1;
            --shadow: rgba(8, 17, 34, 0.32);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(34, 211, 238, 0.22), transparent 32%),
                radial-gradient(circle at bottom right, rgba(94, 234, 212, 0.18), transparent 28%),
                linear-gradient(160deg, #020617 0%, #0f172a 50%, #111827 100%);
        }

        .page {
            width: min(1120px, calc(100% - 3rem));
            margin: 0 auto;
            padding: 4rem 0 5rem;
        }

        .hero,
        .audience-card,
        .highlights {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 24px;
            box-shadow: 0 24px 60px var(--shadow);
        }

        .hero {
            padding: 4rem;
            text-align: center;
        }

        .eyebrow {
            margin: 0 0 1rem;
            color: var(--accent);
            font-size: 0.95rem;
            font-weight: bold;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        h1 {
            margin-bottom: 1rem;
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            line-height: 1.05;
        }

        .hero p {
            max-width: 760px;
            margin: 0 auto 1.5rem;
            font-size: 1.15rem;
            line-height: 1.7;
            color: var(--muted);
        }

        .hero strong {
            color: #ffffff;
        }

        .cta-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.95rem 1.4rem;
            border-radius: 999px;
            color: #001018;
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            font-weight: bold;
            text-decoration: none;
        }

        .cta.secondary {
            color: var(--text);
            background: rgba(148, 163, 184, 0.1);
            border: 1px solid rgba(148, 163, 184, 0.28);
        }

        .section-title {
            margin: 3rem 0 1.25rem;
            font-size: 2rem;
            text-align: center;
        }

        .section-copy {
            max-width: 760px;
            margin: 0 auto 2rem;
            text-align: center;
            line-height: 1.7;
            color: var(--muted);
        }

        .audiences {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
        }

        .audience-card {
            padding: 2rem;
        }

        .audience-card h3 {
            margin-bottom: 0.75rem;
            font-size: 1.3rem;
        }

        .audience-card p {
            margin-bottom: 0;
            line-height: 1.7;
            color: var(--muted);
        }

        .highlights {
            margin-top: 2rem;
            padding: 2rem;
        }

        .highlight-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem 1.5rem;
            padding: 0;
            margin: 1rem 0 0;
            list-style: none;
        }

        .highlight-list li {
            padding: 1rem 1.15rem;
            border-radius: 18px;
            background: rgba(15, 23, 42, 0.68);
            border: 1px solid rgba(148, 163, 184, 0.16);
            color: var(--muted);
            line-height: 1.6;
        }

        @media (max-width: 720px) {
            .page {
                width: min(100% - 1.5rem, 1120px);
                padding: 1.5rem 0 3rem;
            }

            .hero,
            .audience-card,
            .highlights {
                border-radius: 20px;
            }

            .hero {
                padding: 2.25rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero">
            <p class="eyebrow">Create. Host. Learn. Compete.</p>
            <h1>Welcome to CTFForge</h1>
            <p>
                CTFForge is where <strong>authors</strong> shape standout challenges, <strong>organizers</strong> launch events with less friction,
                and <strong>participants</strong> grow their skills through playful, practical security experiences.
            </p>
            <p>
                Whether you want to earn recognition, streamline your next competition, or simply have fun while learning, CTFForge is built to make the journey rewarding.
            </p>
            <div class="cta-row">
                <a class="cta" href="#audiences">See what you can do</a>
                <a class="cta secondary" href="#highlights">Explore the highlights</a>
            </div>
        </section>

        <section id="audiences">
            <h2 class="section-title">Something exciting for every audience</h2>
            <p class="section-copy">
                From first-time players to experienced event teams, CTFForge helps everyone join the same ecosystem with clear value and a welcoming experience.
            </p>
            <div class="audiences">
                <?php foreach ($audiences as $audience): ?>
                    <article class="audience-card">
                        <h3><?= htmlspecialchars($audience['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($audience['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="highlights" class="highlights">
            <h2>Why people choose CTFForge</h2>
            <ul class="highlight-list">
                <li>Authors can earn badges, visibility, and commission opportunities for the challenges they create.</li>
                <li>Organizers can stand up compelling CTF events quickly without getting buried in setup complexity.</li>
                <li>Participants can practice, learn, and celebrate progress in a community built around curiosity and fun.</li>
                <li>Teams get a polished home for challenge creation, event delivery, and memorable security education.</li>
            </ul>
        </section>
    </main>
</body>
</html>
