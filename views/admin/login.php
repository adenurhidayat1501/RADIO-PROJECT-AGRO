<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | <?= e(config('radio.station.name', 'Radio Agro')) ?> Control Panel</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bg-canvas: #04070d;
            --primary: #10b981;
            --primary-hover: #34d399;
            --cyan: #06b6d4;
            --gradient-aurora: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
            --shadow-glow: 0 10px 30px -5px rgba(16, 185, 129, 0.45);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-canvas);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
            position: relative;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Ambient Aurora Glow */
        .aurora-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.18;
            pointer-events: none;
            z-index: 0;
        }

        .aurora-orb-1 {
            width: 450px;
            height: 450px;
            top: -100px;
            left: -100px;
            background: radial-gradient(circle, #10b981 0%, transparent 70%);
        }

        .aurora-orb-2 {
            width: 400px;
            height: 400px;
            bottom: -80px;
            right: -80px;
            background: radial-gradient(circle, #06b6d4 0%, transparent 70%);
        }

        .login-card {
            background: rgba(11, 19, 36, 0.78);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 25px 60px -10px rgba(0, 0, 0, 0.8), 0 0 25px rgba(16, 185, 129, 0.15);
            max-width: 440px;
            width: 100%;
            position: relative;
            z-index: 1;
            transition: border-color 0.3s ease;
        }

        .login-card:hover {
            border-color: rgba(16, 185, 129, 0.35);
        }

        .brand-badge {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(6, 182, 212, 0.2));
            border: 1px solid rgba(16, 185, 129, 0.45);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #34d399;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
        }

        .form-control {
            background: rgba(7, 12, 23, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            background: rgba(7, 12, 23, 0.95);
            border-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .input-group-text {
            background: rgba(7, 12, 23, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-right: none;
            color: #94a3b8;
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .input-group .form-control {
            border-left: none;
        }

        .btn-aurora {
            background: var(--gradient-aurora);
            border: none;
            color: #ffffff;
            padding: 14px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-glow);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-aurora:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px -5px rgba(16, 185, 129, 0.6);
            color: #ffffff;
        }

        .btn-aurora:active {
            transform: translateY(0);
        }

        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body>

<div class="aurora-orb aurora-orb-1" aria-hidden="true"></div>
<div class="aurora-orb aurora-orb-2" aria-hidden="true"></div>

<div class="container p-3">
    <div class="login-card mx-auto p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="brand-badge mb-3">
                <i class="bi bi-broadcast fs-2"></i>
            </div>
            <h3 class="fw-bold mb-1 font-outfit text-white"><?= e(config('radio.station.name', 'Radio Agro')) ?></h3>
            <p class="text-muted small">Studio Control Center & Administration</p>
        </div>

        <?php if ($err = flash('error')): ?>
            <div class="alert alert-danger py-2 small d-flex align-items-center mb-3 bg-danger bg-opacity-10 border-danger text-danger">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i> <?= e($err) ?>
            </div>
        <?php endif; ?>

        <?php if ($succ = flash('success')): ?>
            <div class="alert alert-success py-2 small d-flex align-items-center mb-3 bg-success bg-opacity-10 border-success text-success">
                <i class="bi bi-check-circle-fill me-2 fs-6"></i> <?= e($succ) ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('admin/login') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label small text-muted text-uppercase fw-semibold" for="username">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" id="username" name="username" class="form-control" placeholder="admin" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small text-muted text-uppercase fw-semibold" for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-aurora w-100">
                Sign In to Panel <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top border-secondary border-opacity-25">
            <a href="<?= base_url() ?>" class="text-decoration-none text-muted small hover-white">
                <i class="bi bi-arrow-left me-1"></i> Back to Public Radio Portal
            </a>
        </div>
    </div>
</div>
</body>
</html>
