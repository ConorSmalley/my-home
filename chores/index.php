<?php

require_once __DIR__ . '../../bootstrap.php';
require_once __DIR__ . '../../config/users.php';
require_once __DIR__ . '/grocy/chores.php';

$logged_in = $_SESSION['logged_in'] ?? false;


if(!$logged_in)
{
    header('Location: ../../');
}
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
// Get chores
try {
    $overdueChores = getOverdueGrocyChores($grocyApiKey);
    $todaysChores = getTodaysGrocyChores($grocyApiKey);
    $unassignedChores = getUnassignedGrocyChores($grocyApiKey);

} catch (RuntimeException $e) {
    error_log($e->getMessage());

    $overdueChores = [];
    $todaysChores = [];
    $unassignedChores = [];

    $choreError = 'Unable to load chores. Please try again later.';
}
// var_dump($chores);
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

        <?php elseif (empty($overdueChores) && empty($todaysChores) && empty($unassignedChores)): ?>

            <p>You have no chores assigned.</p>

        <?php else: ?>
            <div class="row border rounded">
                <div class="col-8 border rounded">
                    <h1 class="text-center">My Chores</h1>

                    <div class="row border rounded">
                        <div class="col-6 border rounded">
                            <h2 class="text-center">Overdue Chores</h2>
                            <?php foreach ($overdueChores as $chore): ?>
                                <?= htmlspecialchars($chore['chore_name'] ?? 'Unnamed chore') ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="col-6 border rounded">
                            <h2 class="text-center">Todays Chores</h2>
                            <?php foreach ($todaysChores as $chore): ?>
                                <?= htmlspecialchars($chore['chore_name'] ?? 'Unnamed chore') ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-4 border rounded">
                    <h1 class="text-center">Group Chores</h1>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>