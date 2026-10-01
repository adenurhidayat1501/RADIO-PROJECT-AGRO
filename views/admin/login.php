<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | <?= e(config('radio.station.name', 'Radio Agro')) ?> Control Panel</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
        }
        .login-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 420px;
            width: 100%;
        }
        .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
        }
        .form-control:focus {
            background: rgba(15, 23, 42, 0.85);
            border-color: #3b82f6;
            color: #fff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
        }
        .btn-primary {
            background: #2563eb;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
<div class="container p-3">
    <div class="login-card mx-auto p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-25 text-primary mb-3">
                <i class="bi bi-broadcast-pin fs-1"></i>
            </div>
            <h3 class="fw-bold mb-1 font-outfit"><?= e(config('radio.station.name', 'Radio Agro')) ?></h3>
            <p class="text-muted small">Broadcast Control Center & Administration</p>
        </div>

        <?php if ($err = flash('error')): ?>
            <div class="alert alert-danger py-2 small d-flex align-items-center mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($err) ?>
            </div>
        <?php endif; ?>

        <?php if ($succ = flash('success')): ?>
            <div class="alert alert-success py-2 small d-flex align-items-center mb-3">
                <i class="bi bi-check-circle-fill me-2"></i> <?= e($succ) ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('admin/login') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label small text-muted text-uppercase fw-semibold">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent text-secondary border-secondary border-opacity-25"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small text-muted text-uppercase fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent text-secondary border-secondary border-opacity-25"><i class="bi bi-key"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 shadow-sm">
                Sign In to Panel <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top border-secondary border-opacity-25">
            <a href="<?= base_url() ?>" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to Public Radio Portal
            </a>
        </div>
    </div>
</div>
</body>
</html>
