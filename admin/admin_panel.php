<?php
// search.php
session_start();
if (!isset($_SESSION['admin'])) {
    exit('Unauthorized');
}

require_once '../db_connect.php';
$conn = connectDB();

$type = isset($_GET['type']) ? $_GET['type'] : '';
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if ($type === 'anime') {
    $stmt = $conn->prepare("SELECT id, title, genre, status FROM animes WHERE id LIKE ? OR title LIKE ? ");
    $search = '%' . $query . '%';
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $stmt->bind_result($id, $title, $genre, $status);

    $animes = [];
    while ($stmt->fetch()) {
        $animes[] = [
            'id' => $id,
            'title' => $title,
            'genre' => $genre,
            'status' => $status,
        ];
    }
    $stmt->close();

    // Generate HTML for the anime table rows
    if (count($animes) > 0) {
        foreach ($animes as $anime) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($anime['id']) . '</td>';
            echo '<td>' . htmlspecialchars($anime['title']) . '</td>';
            echo '<td>' . htmlspecialchars($anime['genre']) . '</td>';
            echo '<td>' . htmlspecialchars($anime['status']) . '</td>';
            echo '<td>';
            echo '<a href="edit_anime.php?id=' . $anime['id'] . '" class="btn btn-edit"><i class="fas fa-edit"></i></a>';
            echo '<a href="delete_anime.php?id=' . $anime['id'] . '" class="btn btn-delete" onclick="return confirm(\'Are you sure you want to delete this anime?\');"><i class="fas fa-trash"></i></a>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5">No animes found.</td></tr>';
    }
} elseif ($type === 'user') {
    $stmt = $conn->prepare("SELECT id, username, email, is_admin FROM users WHERE id LIKE ? OR username LIKE ?");
    $search = '%' . $query . '%';
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $stmt->bind_result($id, $username, $email, $is_admin);

    $users = [];
    while ($stmt->fetch()) {
        $users[] = [
            'id' => $id,
            'username' => $username,
            'email' => $email,
            'is_admin' => $is_admin,
        ];
    }
    $stmt->close();

    // Generate HTML for the user table rows
    if (count($users) > 0) {
        foreach ($users as $user) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($user['id']) . '</td>';
            echo '<td>' . htmlspecialchars($user['username']) . '</td>';
            echo '<td>' . htmlspecialchars($user['email']) . '</td>';
            echo '<td>' . ($user['is_admin'] ? 'Yes' : 'No') . '</td>';
            echo '<td>';
            echo '<a href="edit_user.php?id=' . $user['id'] . '" class="btn btn-edit"><i class="fas fa-edit"></i></a>';
            echo '<a href="delete_user.php?id=' . $user['id'] . '" class="btn btn-delete" onclick="return confirm(\'Are you sure you want to delete this user?\');"><i class="fas fa-trash"></i></a>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5">No users found.</td></tr>';
    }
}

$items_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;


$stmt = $conn->prepare("SELECT COUNT(*) FROM animes");
$stmt->execute();
$stmt->bind_result($total_animes);
$stmt->fetch();
$stmt->close();


$stmt = $conn->prepare("SELECT id, title, genre, status FROM animes LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $items_per_page, $offset);
$stmt->execute();
$stmt->bind_result($id, $title, $genre, $status);

$animes = [];
while ($stmt->fetch()) {
    $animes[] = [
        'id' => $id,
        'title' => $title,
        'genre' => $genre,
        'status' => $status,
    ];
}
$total_pages = ceil($total_animes / $items_per_page);

// Get total user count
$stmt = $conn->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$stmt->bind_result($total_users);
$stmt->fetch();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="admin_style.css?v=<?= time(); ?>">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>


    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <ul>
                <li><a href="#dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#anime-section"><i class="fas fa-film"></i> Anime Management</a></li>
                <li><a href="#user-section"><i class="fas fa-users"></i> User Management</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>



        <!-- Main Content -->
        <div class="main-content">
            <header>
                <h1>Welcome, Admin!</h1>
                <p>You are logged in as an administrator.</p>
            </header>

            <section id="dashboard">
                <h2>Dashboard</h2>
                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-film"></i>
                        </div>
                        <div class="card-info">
                            <h3><?= $total_animes; ?></h3>
                            <p>Total Animes</p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-info">
                            <h3><?= $total_users; ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Anime Management Section -->
            <section id="anime-section">
                <h2>Anime Management</h2>
                <div class="action-bar">
                    <a href="add_anime.php" class="btn btn-add"><i class="fas fa-plus"></i> Add New Anime</a>
                    <form method="get" action="#anime-section" class="search-form">
                        <input type="text" name="anime_search" id="animeSearch" placeholder="Search by ID or Title" value="<?= htmlspecialchars($anime_search); ?>">
                    </form>
                </div>
                <!-- Anime Table -->
                <table class="anime-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Genre</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </section>

            <!-- User Management Section -->
            <section id="user-section">
                <h2>User Management</h2>
                <div class="action-bar">
                    <a href="add_user.php" class="btn btn-add"><i class="fas fa-user-plus"></i> Add New User</a>
                    <form method="get" action="#user-section" class="search-form">
                        <input type="text" name="user_search" id="userSearch" placeholder="Search by ID or Username" value="<?= htmlspecialchars($user_search); ?>">
                    </form>
                </div>
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Admin Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </section>

            <!-- Footer -->
            <footer>
                <p>&copy; <?= date('Y'); ?> G-FLIX Admin Panel. All rights reserved.</p>
            </footer>
        </div>
    </div>

    <script>
        // Anime Live Search
        document.getElementById('animeSearch').addEventListener('input', function() {
            var query = this.value;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'search.php?type=anime&query=' + encodeURIComponent(query), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.querySelector('.anime-table tbody').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        });

        // User Live Search
        document.getElementById('userSearch').addEventListener('input', function() {
            var query = this.value;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'search.php?type=user&query=' + encodeURIComponent(query), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.querySelector('.user-table tbody').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        });


        // Anime Live Search and Pagination
        function fetchAnimeData(query = '', page = 1) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'search.php?type=anime&query=' + encodeURIComponent(query) + '&page=' + page, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.querySelector('.anime-table tbody').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }


        fetchAnimeData();



        document.getElementById('animeSearch').addEventListener('input', function() {
            fetchAnimeData(this.value, 1);
        });


        function fetchUserData(query = '', page = 1) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'search.php?type=user&query=' + encodeURIComponent(query) + '&page=' + page, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.querySelector('.user-table tbody').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }


        fetchUserData();


        document.getElementById('userSearch').addEventListener('input', function() {
            fetchUserData(this.value, 1);
        });

        function showToast(message, type = 'success') {
            var toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container';
                document.body.appendChild(toastContainer);
            }

            var toast = document.createElement('div');
            toast.className = 'toast ' + type;

            var toastMessage = document.createElement('div');
            toastMessage.className = 'toast-message';
            toastMessage.textContent = message;

            var toastClose = document.createElement('div');
            toastClose.className = 'toast-close';
            toastClose.innerHTML = '&times;';
            toastClose.addEventListener('click', function() {
                toastContainer.removeChild(toast);
            });

            toast.appendChild(toastMessage);
            toast.appendChild(toastClose);
            toastContainer.appendChild(toast);

            // Automatically remove toast after 5 seconds
            setTimeout(function() {
                if (toastContainer.contains(toast)) {
                    toastContainer.removeChild(toast);
                }
            }, 5000);
        }

        // Display toast if there is a message
        <?php if (isset($message)): ?>
            showToast("<?= addslashes($message); ?>", 'success');
        <?php endif; ?>

        // Function to load anime data with pagination and search
        function loadAnimeData(query = '', page = 1) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'search.php?type=anime&query=' + encodeURIComponent(query) + '&page=' + page, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.querySelector('.anime-table tbody').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        // Function to load user data with pagination and search
        function loadUserData(query = '', page = 1) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'search.php?type=user&query=' + encodeURIComponent(query) + '&page=' + page, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.querySelector('.user-table tbody').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        // Trigger data fetch on page load
        loadAnimeData();
        loadUserData();

        // Handle live search for animes
        document.getElementById('animeSearch').addEventListener('input', function() {
            var query = this.value;
            loadAnimeData(query);
        });

        // Handle live search for users
        document.getElementById('userSearch').addEventListener('input', function() {
            var query = this.value;
            loadUserData(query);
        });


        // Handle anime pagination
        document.addEventListener('click', function(event) {
            if (event.target.matches('.anime-pagination a')) { // Only anime pagination
                var page = event.target.getAttribute('data-page');
                var query = document.getElementById('animeSearch').value;
                fetchAnimeData(query, page);
            }
        });



        // Handle pagination click for users
        document.addEventListener('click', function(event) {
            if (event.target.matches('.user-pagination a')) { // Only user pagination
                var page = event.target.getAttribute('data-page');
                var query = document.getElementById('userSearch').value;
                fetchUserData(query, page);
            }
        });
    </script>
</body>

</html>