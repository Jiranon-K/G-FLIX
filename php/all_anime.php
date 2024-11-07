<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db_connect.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}


$conn = connectDB();


$selected_genre = isset($_GET['genre']) ? $_GET['genre'] : '';


$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;


$genre_condition = '';
if (!empty($selected_genre)) {
    $genre_condition = "WHERE genre LIKE ?";
}


$total_sql = "SELECT COUNT(*) AS total FROM animes $genre_condition";
$stmt_total = $conn->prepare($total_sql);

if (!empty($selected_genre)) {
    $genre_param = "%$selected_genre%";
    $stmt_total->bind_param('s', $genre_param);
}

$stmt_total->execute();
$stmt_total->bind_result($total_animes);
$stmt_total->fetch();
$stmt_total->close();

$total_pages = ceil($total_animes / $limit);


$sql = "SELECT id, title, image_url, rating, synopsis, episode_count, release_year, genre, studio, status, trailer_url 
        FROM animes 
        $genre_condition
        ORDER BY id DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

if (!empty($selected_genre)) {
    $stmt->bind_param('sii', $genre_param, $limit, $offset);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();


$stmt->bind_result($id, $title, $image_url, $rating, $synopsis, $episode_count, $release_year, $genre, $studio, $status, $trailer_url);

$animes = [];
while ($stmt->fetch()) {
    $animes[] = [
        'id' => $id,
        'title' => $title,
        'image_url' => $image_url,
        'rating' => $rating,
        'synopsis' => $synopsis,
        'episode_count' => $episode_count,
        'release_year' => $release_year,
        'genre' => $genre,
        'studio' => $studio,
        'status' => $status,
        'trailer_url' => $trailer_url,
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Anime - G-FLIX</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo filemtime('../css/style.css'); ?>">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="all-a">

    <!-- Navbar -->
    <nav class="navbar">
        <a href="../index.php">
            <div class="logo">G-FLIX</div>
        </a>

        <div class="hamburger">
            <i class="fa-solid fa-bars"></i>
        </div>

        <div class="nav-links">
            <?php
            if (isset($_SESSION['user_id'])) {
                $stmt_nav = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
                $stmt_nav->bind_param("i", $_SESSION['user_id']);
                $stmt_nav->execute();
                $stmt_nav->bind_result($navbar_profile_image);
                $stmt_nav->fetch();
                $stmt_nav->close();

                echo '
            <a href="profile.php" class="user-info">
                <span class="username">' . htmlspecialchars($_SESSION['username']) . '</span>';
                if (!empty($navbar_profile_image)) {
                    echo '<img src="../' . htmlspecialchars($navbar_profile_image) . '" alt="Profile Image" class="profile-image">';
                } else {
                    echo '<i class="fas fa-user-circle user-icon"></i>';
                }
                echo '</a>
            <a href="all_anime.php" class="nav-button all-anime-button">
                <i class="fas fa-film"></i> All Anime
            </a>
            <a href="mywhitelist.php" class="nav-button mywhitelist-button">
                <i class="fas fa-list"></i> My Whitelist
            </a>
             <a href="profile.php" class="nav-button profile-button">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="logout.php" class="nav-button logout-button">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </a>';
            } else {
                echo '
            <a href="login.php" class="nav-button login-button">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </a>
            <a href="register.php" class="nav-button signup-button">
                <i class="fas fa-user-plus"></i> สมัครสมาชิก
            </a>';
            }
            ?>
        </div>
    </nav>

    <video id="background-video" autoplay muted loop>
        <source src="../img/bglin.mp4" type="video/mp4">
    </video>

    <div class="overlay"></div>

    <!-- Modal for Watch Trailer -->
    <div id="trailer-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <iframe id="trailer-video" src="" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
        </div>
    </div>

    <!-- Modal for Anime Info -->
    <div id="anime-modal" class="modal">
        <div class="modal-content">
            <div class="anime-info-card">
                <span class="close-btn">&times;</span>
                <div class="anime-image">
                    <img src="default_image.jpg" alt="Anime Title">
                </div>
                <div class="anime-details">
                    <h2 class="anime-title">Anime Title</h2>
                    <p class="anime-meta">จำนวนตอน: Unknown | ปีที่ฉาย: Unknown | ประเภท: Unknown</p>
                    <p class="anime-studio">สตูดิโอ: Unknown</p>
                    <p class="anime-rating">คะแนน: 0/5</p>
                    <p class="anime-status">สถานะ: Unknown</p>
                    <p class="anime-synopsis">เรื่องย่อ</p>
                    <div class="anime-buttons">
                        <button class="btn whitelist-btn"><i class="fas fa-heart"></i> Whitelist</button>
                        <button class="btn trailer-btn"><i class="fas fa-play"></i> Watch Trailer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <!-- Grid of Anime Cards -->
    <div class="all-anime-page">
        <div class="container">
            <h1 class="h1">All Anime</h1>
            <form method="GET" action="all_anime.php">
                <label for="genre" style="color:white;">เลือกประเภท:</label>
                <select name="genre" id="genre" onchange="this.form.submit()">
                    <option value="">ทุกประเภท</option>
                    <option value="Action" <?php if ($selected_genre == 'Action') echo 'selected'; ?>>Action</option>
                    <option value="Drama" <?php if ($selected_genre == 'Drama') echo 'selected'; ?>>Drama</option>
                    <option value="Comedy" <?php if ($selected_genre == 'Comedy') echo 'selected'; ?>>Comedy</option>
                    <option value="Slice of Life" <?php if ($selected_genre == 'Slice of Life') echo 'selected'; ?>>Slice of Life</option>
                    <option value="Adventure" <?php if ($selected_genre == 'Adventure') echo 'selected'; ?>>Adventure</option>
                    <option value="Fantasy" <?php if ($selected_genre == 'Fantasy') echo 'selected'; ?>>Fantasy</option>
                    <option value="Sci-Fi" <?php if ($selected_genre == 'Sci-Fi') echo 'selected'; ?>>Sci-Fi</option>
                    <option value="Mystery" <?php if ($selected_genre == 'Mystery') echo 'selected'; ?>>Mystery</option>
                    <option value="Supernatural" <?php if ($selected_genre == 'Supernatural') echo 'selected'; ?>>Supernatural</option>
                    <option value="Romance" <?php if ($selected_genre == 'Romance') echo 'selected'; ?>>Romance</option>
                    <option value="Suspense" <?php if ($selected_genre == 'Suspense') echo 'selected'; ?>>Suspense</option>
                </select>
            </form>
            <div class="anime-grid">
                <?php foreach ($animes as $anime): ?>
                    <div class="card" data-id="<?php echo htmlspecialchars($anime['id']); ?>"
                        data-title="<?php echo htmlspecialchars($anime['title']); ?>"
                        data-episode_count="<?php echo htmlspecialchars($anime['episode_count']); ?>"
                        data-release_year="<?php echo htmlspecialchars($anime['release_year']); ?>"
                        data-genre="<?php echo htmlspecialchars($anime['genre']); ?>"
                        data-studio="<?php echo htmlspecialchars($anime['studio']); ?>"
                        data-status="<?php echo htmlspecialchars($anime['status']); ?>"
                        data-synopsis="<?php echo htmlspecialchars($anime['synopsis']); ?>"
                        data-image_url="<?php echo htmlspecialchars($anime['image_url']); ?>"
                        data-rating="<?php echo htmlspecialchars($anime['rating']); ?>"
                        data-trailer_url="<?php echo htmlspecialchars($anime['trailer_url']); ?>">
                        <img src="<?php echo htmlspecialchars($anime['image_url']); ?>" alt="<?php echo htmlspecialchars($anime['title']); ?>">
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="btn prev-page">Previous</a>
                <?php endif; ?>

                <span id="page-info" class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="btn next-page">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    $conn->close();
    ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Modal related elements for Anime Info
            const animeModal = document.getElementById('anime-modal');
            const animeCloseBtn = animeModal.querySelector('.close-btn');
            const animeInfoCard = animeModal.querySelector('.anime-info-card');
            const animeImage = animeInfoCard.querySelector('.anime-image img');
            const animeTitle = animeInfoCard.querySelector('.anime-title');
            const animeMeta = animeInfoCard.querySelector('.anime-meta');
            const animeStudio = animeInfoCard.querySelector('.anime-studio');
            const animeRating = animeInfoCard.querySelector('.anime-rating');
            const animeStatus = animeInfoCard.querySelector('.anime-status');
            const animeSynopsis = animeInfoCard.querySelector('.anime-synopsis');
            const whitelistBtn = animeInfoCard.querySelector('.whitelist-btn');
            const trailerBtn = animeInfoCard.querySelector('.trailer-btn');

            // Modal related elements for Trailer
            const trailerModal = document.getElementById('trailer-modal');
            const trailerCloseBtn = trailerModal.querySelector('.close-btn');
            const trailerVideo = trailerModal.querySelector('#trailer-video');

            // Function to convert rating into stars
            function getStars(rating) {
                let fullStars = Math.floor(rating);
                let halfStar = rating % 1 !== 0 ? 1 : 0;
                let emptyStars = 5 - fullStars - halfStar;
                let stars = '';

                for (let i = 0; i < fullStars; i++) stars += '<i class="fas fa-star"></i>';
                if (halfStar) stars += '<i class="fas fa-star-half-alt"></i>';
                for (let i = 0; i < emptyStars; i++) stars += '<i class="far fa-star"></i>';

                return stars;
            }

            // Function to update the anime-info-card content
            function updateAnimeInfo(anime) {
                animeImage.src = anime.image_url;
                animeImage.alt = anime.title;
                animeTitle.textContent = anime.title;
                animeMeta.innerHTML = `จำนวนตอน: ${anime.episode_count} | ปีที่ฉาย: ${anime.release_year} | ประเภท: ${anime.genre}`;
                animeStudio.textContent = `สตูดิโอ: ${anime.studio}`;
                animeRating.innerHTML = `คะแนน: ${getStars(anime.rating)} (${anime.rating}/5)`;
                animeStatus.textContent = `สถานะ: ${anime.status}`;
                animeSynopsis.textContent = anime.synopsis;

                // Update Whitelist button
                whitelistBtn.setAttribute('data-anime-id', anime.id);

                // Set data-trailer-url for the Watch Trailer button
                trailerBtn.setAttribute('data-trailer-url', anime.trailer_url);

                // Show the Anime Info modal
                animeModal.style.display = "block";
            }

            // Listen for clicks on anime cards
            const animeCards = document.querySelectorAll('.card');
            animeCards.forEach(card => {
                card.addEventListener('click', function() {
                    const anime = {
                        id: card.getAttribute('data-id'),
                        title: card.getAttribute('data-title'),
                        episode_count: card.getAttribute('data-episode_count'),
                        release_year: card.getAttribute('data-release_year'),
                        genre: card.getAttribute('data-genre'),
                        studio: card.getAttribute('data-studio'),
                        status: card.getAttribute('data-status'),
                        synopsis: card.getAttribute('data-synopsis'),
                        image_url: card.getAttribute('data-image_url'),
                        trailer_url: card.getAttribute('data-trailer_url'),
                        rating: card.getAttribute('data-rating')
                    };

                    // Update and display the modal with anime info
                    updateAnimeInfo(anime);
                });
            });

            // Close the Anime Info modal
            animeCloseBtn.addEventListener('click', function() {
                animeModal.style.display = "none";
            });

            // Close the Anime Info modal when clicking outside the modal content
            window.addEventListener('click', function(event) {
                if (event.target == animeModal) {
                    animeModal.style.display = "none";
                }
            });

            // Close the Trailer modal
            trailerCloseBtn.addEventListener('click', function() {
                trailerModal.style.display = "none";
                trailerVideo.src = '';
            });

            // Close the Trailer modal when clicking outside the trailer content
            window.addEventListener('click', function(event) {
                if (event.target == trailerModal) {
                    trailerModal.style.display = "none";
                    trailerVideo.src = '';
                }
            });

            // Hamburger menu toggle
            const hamburger = document.querySelector('.hamburger');
            const navLinks = document.querySelector('.nav-links');

            hamburger.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        });
    </script>
</body>

</html>
