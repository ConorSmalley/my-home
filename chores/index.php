<?php

require_once __DIR__ . '../../bootstrap.php';
require_once __DIR__ . '../../config/users.php';
require_once __DIR__ . '../../api/grocy/chores.php';
require_once __DIR__ . '../../helpers/formatChoreDueDate.php';

$logged_in = $_SESSION['logged_in'] ?? false;


if(!$logged_in)
{
    header('Location: ../../');
}
$_SESSION['using_mock_grocy'] = false;
$logged_in_as = $_SESSION['logged_in_as'] ?? null;
$username = $logged_in_as;

$user = $username ? getUserByUsername($username) : null;

if (!$user) {
    throw new RuntimeException('User not found');
}

$grocyApiKey = $user['api_keys']['grocy'] ?? null;

if (empty($grocyApiKey)) {
    throw new RuntimeException('Grocy API key not configured');
}


$completionMessage = null;
$completionError = null;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['complete_chore_id'])
) {
    try {
        $choreId = (int) $_POST['complete_chore_id'];

        if ($choreId <= 0) {
            throw new RuntimeException('Invalid chore ID');
        }

        completeGrocyChore($choreId, $grocyApiKey);

        $completionMessage = 'Chore completed successfully!';

    } catch (Throwable $e) {
        $completionError = $e->getMessage();
    }
}



// Get chores
try {
    $overdueChores = getOverdueGrocyChores($grocyApiKey);
    $todaysChores = getTodaysGrocyChores($grocyApiKey);
    $upcomingChores = getUpcommingGrocyChores($grocyApiKey);
    $unassignedChores = getUnassignedGrocyChores($grocyApiKey);

} catch (RuntimeException $e) {
    error_log($e->getMessage());

    $overdueChores = [];
    $todaysChores = [];
    $upcomingChores = [];
    $unassignedChores = [];

    $choreError = 'Unable to load chores. Please try again later.';
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Chores</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <style>
        .mock-warning {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 9999;
            background: #842029;
            color: #fff;
            border-top: 4px solid #dc3545;
            padding: 12px 20px;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.25);
        }

        .mock-warning-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            max-width: 1200px;
            margin: auto;
        }

        .mock-warning-icon {
            font-size: 30px;
            color: #ffc107;
        }

        .mock-warning strong {
            font-size: 14px;
            letter-spacing: 1px;
        }

        .mock-warning-description {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 2px;
        }

        .mock-indicator {
            width: 11px;
            height: 11px;
            background: #ffc107;
            border-radius: 50%;
            margin-left: 10px;
            flex-shrink: 0;
            animation: mock-pulse 1.5s infinite;
        }

        @keyframes mock-pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.3;
            }
        }

        body {
            padding-bottom: 75px;
        }

        @media (max-width: 576px) {
            .mock-warning-content {
                justify-content: flex-start;
            }

            .mock-warning {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar md:navbar-expand bg-1">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">My-Home</a>
            <button class="btn-icon navbar-toggler" type="button" data-bs-toggle="drawer" data-bs-target="#navbarDrawer" aria-controls="navbarDrawer" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon" aria-hidden="true"></span>
            </button>
            <dialog class="drawer drawer-end" tabindex="-1" id="navbarDrawer" aria-labelledby="navbarDrawerLabel">
            <div class="drawer-header">
                <h5 class="drawer-title" id="navbarDrawerLabel">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
            </div>
            <div class="drawer-body mb-2 md:mb-0">
                <ul class="nav navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/chores">Chores</a>
                </li>
                <li class="nav-item">
                    <button class="nav-link" type="button" data-bs-toggle="menu" aria-expanded="false">
                    Menu
                    </button>
                    <div class="menu">
                    <a class="menu-item" href="#">Action</a>
                    <a class="menu-item" href="#">Another action</a>
                    <hr class="menu-divider">
                    <a class="menu-item" href="#">Something else here</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link disabled" aria-disabled="true">Disabled</a>
                </li>
                </ul>

                <div class=d-flex" role="search">
                    <span class="navbar-text">
                        Welcome, <?= htmlspecialchars($logged_in_as, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    
                     <a href="api/logout.php" class="btn-solid theme-secondary">
                        Logout
                    </a>
                </div>
            </div>
            </dialog>
        </div>
    </nav>
    <main class="container py-4">
        <!-- <h1>My Chores</h1> -->

        <?php if (!empty($choreError)): ?>

            <p><?= htmlspecialchars($choreError) ?></p>

        <?php elseif (empty($overdueChores) && empty($todaysChores) && empty($upcomingChores) && empty($unassignedChores)): ?>

            <p>You have no chores assigned.</p>

        <?php else: ?>
            <div class="row">
                <div class="col-8">
                    <h1 class="text-center">My Chores</h1>

                    <div class="row">
                        <div class="col-4">
                            <h2 class="text-center">Overdue Chores</h2>
                            <?php foreach ($overdueChores as $chore): ?>
                                <?php require __DIR__ . '../../partials/chore-card.php'; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="col-4">
                            <h2 class="text-center">Todays Chores</h2>
                            <?php foreach ($todaysChores as $chore): ?>
                                <?php require __DIR__ . '../../partials/chore-card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-4">
                            <h2 class="text-center">Upcoming Chores</h2>
                            <?php foreach ($upcomingChores as $chore): ?>
                                <?php require __DIR__ . '../../partials/chore-card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-4">
                    <h1 class="text-center">Group Chores</h1>
                    <h2>&nbsp;</h2>
                    <?php foreach ($unassignedChores as $chore): ?>
                        <?php require __DIR__ . '../../partials/chore-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <?php if (!empty($_SESSION['using_mock_grocy'])): ?>
        <div class="mock-warning">
            <div class="mock-warning-content">
                <span class="mock-warning-icon">⚠</span>
                <div>
                    <strong>WARNING: MOCK DATA IN USE</strong>
                    <div class="mock-warning-description">
                        Grocy is unavailable. You are viewing dummy data.
                        Changes will not be saved to your real Grocy instance.
                    </div>
                </div>
                <span class="mock-indicator"></span>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>