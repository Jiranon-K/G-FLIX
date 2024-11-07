<?php
// edit_anime.php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit;
}

require_once '../db_connect.php';
$conn = connectDB();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: admin_panel.php?message=Invalid anime ID.");
    exit;
}

// Initialize variables
$title = $episode_count = $release_year = $genre = $studio = $status = $synopsis = $rating = $trailer_video_id = "";
$title_err = $genre_err = $status_err = $image_err = $video_err = "";

// Fetch current anime data
$stmt = $conn->prepare("SELECT title, episode_count, release_year, genre, studio, status, synopsis, rating, image_url, background_video_url, trailer_url FROM animes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    $stmt->close();
    $conn->close();
    header("Location: admin_panel.php?message=Anime not found.");
    exit;
}

$stmt->bind_result($title, $episode_count, $release_year, $genre, $studio, $status, $synopsis, $rating, $image_url, $background_video_url, $trailer_url);
$stmt->fetch();
$stmt->close();

// Extract trailer video ID from trailer URL
if ($trailer_url) {
    $trailer_video_id = str_replace('https://www.youtube.com/embed/', '', $trailer_url);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    if (empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title.";
    } else {
        $title = trim($_POST["title"]);
    }

    if (empty(trim($_POST["genre"]))) {
        $genre_err = "Please enter the genre.";
    } else {
        $genre = trim($_POST["genre"]);
    }

    if (empty(trim($_POST["status"]))) {
        $status_err = "Please select the status.";
    } else {
        $status = trim($_POST["status"]);
    }

    $episode_count = !empty($_POST["episode_count"]) ? intval($_POST["episode_count"]) : $episode_count;
    $release_year = !empty($_POST["release_year"]) ? intval($_POST["release_year"]) : $release_year;
    $studio = !empty($_POST["studio"]) ? trim($_POST["studio"]) : $studio;
    $synopsis = !empty($_POST["synopsis"]) ? trim($_POST["synopsis"]) : $synopsis;
    $rating = !empty($_POST["rating"]) ? floatval($_POST["rating"]) : $rating;

    // Validate rating
    if ($rating !== null && ($rating < 0 || $rating > 5)) {
        $rating_err = "Rating must be between 0 and 5.";
    }

    $trailer_video_id = !empty($_POST["trailer_video_id"]) ? trim($_POST["trailer_video_id"]) : $trailer_video_id;
    if ($trailer_video_id) {
        $trailer_url = 'https://www.youtube.com/embed/' . $trailer_video_id;
    }

    // Handle Image Upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
        $image_extension = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));

        $allowed_image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($image_extension, $allowed_image_extensions)) {
            $image_err = "Invalid image file extension.";
        } elseif (!in_array($_FILES['image_file']['type'], $allowed_image_types)) {
            $image_err = "Invalid image file type.";
        } else {
            $image_filename = 'anime_' . time() . '.' . $image_extension;
            $image_upload_path = '../anime/' . $image_filename;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $image_upload_path)) {
                $image_url = 'https://www.comsci-rmutp.com/651113/LB9/anime/' . $image_filename;
            } else {
                $image_err = "Failed to upload image.";
            }
        }
    }

    // Handle Background Video Upload
    if (isset($_FILES['background_video_file']) && $_FILES['background_video_file']['error'] == UPLOAD_ERR_OK) {
        $allowed_video_types = ['video/mp4', 'video/webm', 'video/ogg'];
        $video_extension = strtolower(pathinfo($_FILES['background_video_file']['name'], PATHINFO_EXTENSION));

        $allowed_video_extensions = ['mp4', 'webm', 'ogg'];
        if (!in_array($video_extension, $allowed_video_extensions)) {
            $video_err = "Invalid video file extension.";
        } elseif (!in_array($_FILES['background_video_file']['type'], $allowed_video_types)) {
            $video_err = "Invalid video file type.";
        } else {
            $video_filename = 'background_' . time() . '.' . $video_extension;
            $video_upload_path = '../video/' . $video_filename;
            if (move_uploaded_file($_FILES['background_video_file']['tmp_name'], $video_upload_path)) {
                $background_video_url = 'https://www.comsci-rmutp.com/651113/LB9/video/' . $video_filename;
            } else {
                $video_err = "Failed to upload background video.";
            }
        }
    }

    // File size checks
    if (isset($_FILES['image_file']['size']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $max_image_size = 5 * 1024 * 1024; // 5 MB
        if ($_FILES['image_file']['size'] > $max_image_size) {
            $image_err = "Image file size exceeds 5 MB limit.";
        }
    }

    if (isset($_FILES['background_video_file']['size']) && $_FILES['background_video_file']['error'] == UPLOAD_ERR_OK) {
        $max_video_size = 100 * 1024 * 1024; // 100 MB
        if ($_FILES['background_video_file']['size'] > $max_video_size) {
            $video_err = "Video file size exceeds 100 MB limit.";
        }
    }

    if (empty($title_err) && empty($genre_err) && empty($status_err) && empty($image_err) && empty($video_err)) {
        // Prepare an update statement
        $stmt = $conn->prepare("UPDATE animes SET title = ?, episode_count = ?, release_year = ?, genre = ?, studio = ?, status = ?, synopsis = ?, rating = ?, trailer_url = ?, image_url = ?, background_video_url = ? WHERE id = ?");
        $stmt->bind_param("siissssdsssi", $title, $episode_count, $release_year, $genre, $studio, $status, $synopsis, $rating, $trailer_url, $image_url, $background_video_url, $id);

        if ($stmt->execute()) {
            // Redirect to admin panel with success message
            header("Location: admin_panel.php?message=Anime updated successfully.");
            exit;
        } else {
            echo "Something went wrong. Please try again later.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Anime</title>
    <link rel="stylesheet" href="admin_style.css?v=<?= time(); ?>">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
</head>

<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <ul>
                <li><a href="admin_panel.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="admin_panel.php#anime-section"><i class="fas fa-film"></i> Anime Management</a></li>
                <li><a href="admin_panel.php#user-section"><i class="fas fa-users"></i> User Management</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <h2>Edit Anime</h2>
            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" method="post" class="admin-form" enctype="multipart/form-data" id="animeForm">
                <div class="form-group">
                    <label>Title <span class="required">*</span></label>
                    <div class="input-field">
                        <input type="text" name="title" value="<?= htmlspecialchars($title); ?>" required>
                        <span class="error"><?= $title_err; ?></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Episode Count</label>
                    <div class="input-field">
                        <input type="number" name="episode_count" value="<?= htmlspecialchars($episode_count); ?>" min="1">
                    </div>
                </div>
                <div class="form-group">
                    <label>Release Year</label>
                    <div class="input-field">
                        <input type="number" name="release_year" value="<?= htmlspecialchars($release_year); ?>" min="1900" max="<?= date('Y') + 5; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Genre <span class="required">*</span></label>
                    <div class="input-field">
                        <input type="text" name="genre" value="<?= htmlspecialchars($genre); ?>" required>
                        <span class="error"><?= $genre_err; ?></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Studio</label>
                    <div class="input-field">
                        <input type="text" name="studio" value="<?= htmlspecialchars($studio); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Status <span class="required">*</span></label>
                    <div class="input-field">
                        <select name="status" required>
                            <option value="">Select Status</option>
                            <option value="ยังไม่ฉาย" <?= $status == 'ยังไม่ฉาย' ? 'selected' : ''; ?>>ยังไม่ฉาย</option>
                            <option value="กำลังฉาย" <?= $status == 'กำลังฉาย' ? 'selected' : ''; ?>>กำลังฉาย</option>
                            <option value="ฉายจบแล้ว" <?= $status == 'ฉายจบแล้ว' ? 'selected' : ''; ?>>ฉายจบแล้ว</option>
                        </select>
                        <span class="error"><?= $status_err; ?></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Synopsis</label>
                    <div class="input-field">
                        <textarea name="synopsis"><?= htmlspecialchars($synopsis); ?></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label>Rating</label>
                    <div class="input-field rating-input">
                        <input type="number" step="0.01" name="rating" value="<?= htmlspecialchars($rating); ?>" min="0" max="5" placeholder="ไม่เกิน 5">
                    </div>
                    <span class="error"><?= isset($rating_err) ? $rating_err : ''; ?></span>
                </div>
                <div class="form-group">
                    <label>Image File</label>
                    <div class="input-field">
                        <input type="file" name="image_file" accept="image/*" id="imageFile">
                        <span class="file-name" id="imageFileName"></span>
                        <span class="error"><?= $image_err; ?></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Background Video File</label>
                    <div class="input-field">
                        <input type="file" name="background_video_file" accept="video/*" id="videoFile">
                        <span class="file-name" id="videoFileName"></span>
                        <span class="error"><?= $video_err; ?></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Trailer Video ID(YOUTUBE)</label>
                    <div class="input-field">
                        <input type="text" name="trailer_video_id" value="<?= htmlspecialchars($trailer_video_id); ?>">
                    </div>
                </div>
                <div class="form-group form-actions">
                    <input type="submit" class="btn" value="Update Anime">
                    <a href="admin_panel.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <!-- JavaScript to display selected file names -->
    <script>
        document.getElementById('imageFile').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : '';
            document.getElementById('imageFileName').textContent = fileName;
        });

        document.getElementById('videoFile').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : '';
            document.getElementById('videoFileName').textContent = fileName;
        });

        // Client-side validation for rating
        document.getElementById('animeForm').addEventListener('submit', function(e) {
            const ratingInput = document.querySelector('input[name="rating"]');
            if (ratingInput.value !== '') {
                const ratingValue = parseFloat(ratingInput.value);
                if (ratingValue < 0 || ratingValue > 5) {
                    e.preventDefault();
                    alert('Rating must be between 0 and 5.');
                }
            }
        });
    </script>
</body>

</html>
