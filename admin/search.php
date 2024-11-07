<?php
session_start();
if (!isset($_SESSION['admin'])) {
    exit('Unauthorized');
}

require_once '../db_connect.php';
$conn = connectDB();

$type = isset($_GET['type']) ? $_GET['type'] : '';
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 15;
$offset = ($current_page - 1) * $items_per_page;

if ($type === 'anime') {
    // Count total animes matching the search
    $stmt = $conn->prepare("SELECT COUNT(*) FROM animes WHERE id LIKE ? OR title LIKE ? ");
    $search = '%' . $query . '%';
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $stmt->bind_result($total_animes);
    $stmt->fetch();
    $stmt->close();

    // Fetch animes for the current page
    $stmt = $conn->prepare("SELECT id, title, genre, status FROM animes WHERE id LIKE ? OR title LIKE ? LIMIT ? OFFSET ?");
    $stmt->bind_param("ssii", $search, $search, $items_per_page, $offset);
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

    // Generate pagination
    $total_pages = ceil($total_animes / $items_per_page);
    echo '<div class="anime-pagination">';
    if ($current_page > 1) {
        echo '<a href="javascript:void(0)" class="btn btn-secondary" data-page="' . ($current_page - 1) . '">Previous</a>';
    }
    if ($current_page < $total_pages) {
        echo '<a href="javascript:void(0)" class="btn btn-secondary" data-page="' . ($current_page + 1) . '">Next</a>';
    }
    echo '</div>';
}

if ($type === 'user') {
    // Count total users matching the search
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE id LIKE ? OR username LIKE ?");
    $search = '%' . $query . '%';
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $stmt->bind_result($total_users);
    $stmt->fetch();
    $stmt->close();

    // Fetch users for the current page
    $stmt = $conn->prepare("SELECT id, username, email, is_admin FROM users WHERE id LIKE ? OR username LIKE ? LIMIT ? OFFSET ?");
    $stmt->bind_param("ssii", $search, $search, $items_per_page, $offset);
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

    // Generate pagination
    $total_pages = ceil($total_users / $items_per_page);
    echo '<div class="user-pagination">';
    if ($current_page > 1) {
        echo '<a href="javascript:void(0)" class="btn btn-secondary" data-page="' . ($current_page - 1) . '">Previous</a>';
    }
    if ($current_page < $total_pages) {
        echo '<a href="javascript:void(0)" class="btn btn-secondary" data-page="' . ($current_page + 1) . '">Next</a>';
    }
    echo '</div>';
}

$conn->close();
