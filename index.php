<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config/users.php';
require_once __DIR__ . '/helpers/formatChoreDueDate.php';

$logged_in = $_SESSION['logged_in'] ?? false;
$logged_in_as = $_SESSION['logged_in_as'] ?? null;

$users = getUsers();

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>My Home</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<?php if ($logged_in): ?>

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
    <!-- Logged-in view -->

    <main class="container py-4">
        <h1>Welcome home</h1>
        <p>You are logged in as <?= htmlspecialchars($logged_in_as, ENT_QUOTES, 'UTF-8') ?>.</p>
    </main>

<?php else: ?>

    <main class="container py-4">
        <h1 class="text-center mb-4">Who's using My Home?</h1>
        <div class="row row-cols-1 md:row-cols-4 g-4">
            <?php foreach ($users as $user) { ?>
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title"><?php echo $user['username']; ?></h4>
                            <p class="card-text">Accesses go here.</p>
                            <button 
                                type="button" 
                                class="btn-solid theme-primary" 
                                data-bs-toggle="dialog" 
                                data-bs-target="#exampleDialogDark" 
                                data-username="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                Login
                            </button>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- PIN login modal -->
    <dialog class="dialog" id="exampleDialogDark" data-bs-theme="light">
        <div class="dialog-header">
            <h1 class="dialog-title">
                Login as <span id="modalUsername"></span>
            </h1>
        </div>

        <form id="loginForm">

            <div class="dialog-body">

                <input type="hidden" name="username" id="loginUsername">
                <input type="hidden" name="pin" id="loginPin">

                <div
                    id="loginError"
                    class="alert alert-danger d-none"
                    role="alert"
                >
                    Incorrect PIN. Please try again.
                </div>

                <div
                    id="loginLoading"
                    class="text-center d-none mb-3"
                >
                    Checking PIN...
                </div>

                <div class="text-center mb-4">

                    <div
                        id="pinDisplay"
                        class="fs-2"
                        aria-label="0 of 4 digits entered"
                    >
                        ○ ○ ○ ○
                    </div>

                    <p class="mt-2">Enter your 4-digit PIN</p>

                </div>

                <!-- Keypad -->
                <div class="row g-2 text-center" id="keypad">

                    <?php for ($i = 1; $i <= 9; $i++): ?>

                        <div class="col-4">
                            <button
                                type="button"
                                class="btn-solid theme-secondary w-100 pin-key"
                                data-digit="<?= $i ?>"
                            >
                                <?= $i ?>
                            </button>
                        </div>

                    <?php endfor; ?>

                    <div class="col-4">
                        <button
                            type="button"
                            class="btn-solid theme-secondary w-100"
                            id="clearPin"
                        >
                            Clear
                        </button>
                    </div>

                    <div class="col-4">
                        <button
                            type="button"
                            class="btn-solid theme-secondary w-100 pin-key"
                            data-digit="0"
                        >
                            0
                        </button>
                    </div>

                    <div class="col-4">
                        <button
                            type="button"
                            class="btn-solid theme-secondary w-100"
                            id="deletePin"
                        >
                            ⌫
                        </button>
                    </div>

                </div>

            </div>

            <div class="dialog-footer">

                <button
                    type="button"
                    class="btn-solid theme-secondary"
                    data-bs-dismiss="dialog"
                >
                    Cancel
                </button>

            </div>

        </form>

    </dialog>

<?php endif; ?>

<script
    type="module"
    src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js"
></script>

<script src="assets/js/login.js"></script>

</body>
</html>