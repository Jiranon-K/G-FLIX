<?php
// index.php
session_start();
include 'db_connect.php';

$conn = connectDB();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ดึงข้อมูลอนิเมะทั้งหมดจากฐานข้อมูล
$animes = getAllAnimes();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G-FLIX</title>
    <link rel="shortcut icon" href="img/favicon.png" type="image/x-icon">
    <!-- ลิงก์ CSS หลัก -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <!-- Font Awesome สำหรับไอคอน -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Swiper CSS สำหรับสไลด์ -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css" />
</head>


<body>
    <!-- Navbar -->
    <nav class="navbar">
        <a href="index.php">
            <!-- Logo -->
            <div class="logo">
                G-FLIX
            </div>
        </a>

        <!-- Hamburger icon สำหรับมือถือ -->
        <div class="hamburger">
            <i class="fa-solid fa-bars"></i>
        </div>

        <!-- ปุ่มเข้าสู่ระบบและสมัครสมาชิก หรือ แสดงชื่อผู้ใช้และไอคอนผู้ใช้ -->
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
            <a href="php/profile.php" class="user-info">
                <span class="username">' . htmlspecialchars($_SESSION['username']) . '</span>';
                if (!empty($navbar_profile_image)) {
                    echo '<img src="' . htmlspecialchars($navbar_profile_image) . '" alt="Profile Image" class="profile-image">';
                } else {
                    echo '<i class="fas fa-user-circle user-icon"></i>';
                }
                echo '</a>
            <a href="php/all_anime.php" class="nav-button all-anime-button">
                <i class="fas fa-film"></i> All Anime
            </a>
            <a href="php/mywhitelist.php" class="nav-button mywhitelist-button">
                <i class="fas fa-list"></i> My Whitelist
            </a>
            <a href="php/profile.php" class="nav-button profile-button">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="php/logout.php" class="nav-button logout-button">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </a>
            ';
            } else {
                echo '
            <a href="php/login.php" class="nav-button login-button">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </a>
            <a href="php/register.php" class="nav-button signup-button">
                <i class="fas fa-user-plus"></i> สมัครสมาชิก
            </a>
        ';
            }
            ?>
        </div>
    </nav>

    <!-- วิดีโอพื้นหลัง -->
    <video id="background-video" autoplay muted loop>
        <source src="video/SummerPocket-PV.mp4" type="video/mp4">
    </video>

    <!-- Layer เพื่อทำพื้นหลังมืด -->
    <div class="overlay"></div>

    <!-- ปุ่มควบคุมวิดีโอ -->
    <div id="video-controls">
        <button id="pause-button">
            <i id="pause-icon" class="fa-solid fa-pause"></i> Play/Pause
        </button>
        <button id="mute-button">
            <i id="mute-icon" class="fa-solid fa-volume-xmark"></i> Mute
        </button>
    </div>

    <div class="anime-info-card">
        <?php

        if (!empty($animes)) {
            $anime = $animes[0];


            $stmt = $conn->prepare("SELECT id FROM whitelist WHERE user_id = ? AND anime_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $_SESSION['user_id'], $anime['id']);
                $stmt->execute();
                $stmt->store_result();
                $is_whitelisted = $stmt->num_rows > 0;
                $stmt->close();
            } else {

                $is_whitelisted = false;
            }

            echo '
        <div class="anime-image">
            <img src="' . htmlspecialchars($anime['image_url']) . '" alt="' . htmlspecialchars($anime['title']) . '">
        </div>
        <div class="anime-details">
            <h2 class="anime-title">' . htmlspecialchars($anime['title']) . '</h2>
            <p class="anime-meta">
                <span>จำนวนตอน: ' . (!is_null($anime['episode_count']) ? htmlspecialchars($anime['episode_count']) : 'Unknown') . '</span> |
                <span>ปีที่ฉาย: ' . htmlspecialchars($anime['release_year']) . '</span> |
                <span>ประเภท: ' . htmlspecialchars($anime['genre']) . '</span>
            </p>
            <p class="anime-studio">
                สตูดิโอ: ' . htmlspecialchars($anime['studio']) . '
            </p>
            <p class="anime-rating">
                ' . displayStars($anime['rating']) . ' (' . htmlspecialchars($anime['rating']) . '/5)
            </p>
            <p class="anime-status">
                สถานะ: ' . htmlspecialchars($anime['status']) . '
            </p>
            <p class="anime-synopsis">
                ' . htmlspecialchars($anime['synopsis']) . '
            </p>
            <!-- เพิ่มปุ่มที่นี่ -->
            <div class="anime-buttons">
                <button class="btn whitelist-btn ' . ($is_whitelisted ? 'whitelisted' : '') . '" data-anime-id="' . htmlspecialchars($anime['id']) . '">
                    <i class="fas fa-heart"></i> ' . ($is_whitelisted ? 'Whitelisted' : 'Whitelist') . '
                </button>
                <button class="btn trailer-btn">
                    <i class="fas fa-play"></i> Watch Trailer
                </button>
            </div>
        </div>
        ';
        }
        ?>
    </div>

    <!-- โมดอลสำหรับแสดงวิดีโอ -->
    <div id="trailer-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>

            <iframe id="trailer-video" src="" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
        </div>
    </div>

    <!-- ส่วนอนิเมะ -->
    <div id="anime-content" class="anime-section">
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <?php
                foreach ($animes as $anime) {
                    // ตรวจสอบว่ามี image_url หรือไม่
                    if (!empty($anime['image_url'])) {
                        echo '
                        <div class="swiper-slide">
                            <div class="anime-card" data-id="' . htmlspecialchars($anime['id']) . '"
                                 data-title="' . htmlspecialchars($anime['title']) . '"
                                 data-episode_count="' . htmlspecialchars($anime['episode_count']) . '"
                                 data-release_year="' . htmlspecialchars($anime['release_year']) . '"
                                 data-genre="' . htmlspecialchars($anime['genre']) . '"
                                 data-studio="' . htmlspecialchars($anime['studio']) . '"
                                 data-status="' . htmlspecialchars($anime['status']) . '"
                                 data-synopsis="' . htmlspecialchars($anime['synopsis']) . '"
                                 data-image_url="' . htmlspecialchars($anime['image_url']) . '"
                                 data-background_video_url="' . htmlspecialchars($anime['background_video_url']) . '"
                                 data-trailer_url="' . htmlspecialchars($anime['trailer_url']) . '"
                                 data-rating="' . htmlspecialchars($anime['rating']) . '">
                                <div class="anime-card-image">
                                    <img src="' . htmlspecialchars($anime['image_url']) . '" alt="' . htmlspecialchars($anime['title']) . '">
                                </div>
                            </div>
                        </div>
                        ';
                    }
                }
                ?>
            </div>

            <div class="swiper-button-next" style="color: #ff007f;"></div>
            <div class="swiper-button-prev" style="color: #ff007f;"></div>
        </div>
    </div>
    <div id="toast-container"></div>
    <?php
    $conn->close();
    ?>

    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script>
        // ฟังก์ชันสำหรับแปลงคะแนนเป็นดาว
        function getStars(rating) {
            const fullStars = Math.floor(rating);
            const halfStar = (rating - fullStars) >= 0.5 ? 1 : 0;
            const emptyStars = 5 - fullStars - halfStar;

            let starsHtml = '';

            for (let i = 0; i < fullStars; i++) {
                starsHtml += '<i class="fas fa-star"></i>';
            }

            if (halfStar) {
                starsHtml += '<i class="fas fa-star-half-alt"></i>';
            }

            for (let i = 0; i < emptyStars; i++) {
                starsHtml += '<i class="far fa-star"></i>';
            }

            return starsHtml;
        }

        const video = document.getElementById('background-video');
        const pauseButton = document.getElementById('pause-button');
        const muteButton = document.getElementById('mute-button');
        const pauseIcon = document.getElementById('pause-icon');
        const muteIcon = document.getElementById('mute-icon');

        // ฟังก์ชันสำหรับปุ่ม Pause/Play
        pauseButton.addEventListener('click', () => {
            if (video.paused) {
                video.play().catch(error => {
                    console.error('Error attempting to play the video:', error);
                });
                pauseIcon.classList.remove('fa-play');
                pauseIcon.classList.add('fa-pause');
                pauseButton.innerHTML = '<i id="pause-icon" class="fa-solid fa-pause"></i> Play/Pause';
            } else {
                video.pause();
                pauseIcon.classList.remove('fa-pause');
                pauseIcon.classList.add('fa-play');
                pauseButton.innerHTML = '<i id="pause-icon" class="fa-solid fa-play"></i> Play/Pause';
            }
        });

        // ฟังก์ชันสำหรับปุ่ม Mute/Unmute
        muteButton.addEventListener('click', () => {
            if (video.muted) {
                video.muted = false;
                muteIcon.classList.remove('fa-volume-xmark');
                muteIcon.classList.add('fa-volume-high');
                muteButton.innerHTML = '<i id="mute-icon" class="fa-solid fa-volume-high"></i> Mute';
            } else {
                video.muted = true;
                muteIcon.classList.remove('fa-volume-high');
                muteIcon.classList.add('fa-volume-xmark');
                muteButton.innerHTML = '<i id="mute-icon" class="fa-solid fa-volume-xmark"></i> Unmute';
            }
        });

        // เล่นวิดีโออัตโนมัติเมื่อเปิดเว็บไซต์
        window.onload = () => {
            video.play().catch(error => {
                console.error('Error attempting to play the video:', error);
            });
            video.muted = true; // ตรวจสอบให้วิดีโอปิดเสียง
            pauseIcon.classList.add('fa-pause'); // เริ่มต้นด้วยไอคอน Pause
            muteIcon.classList.add('fa-volume-xmark'); // เริ่มต้นด้วยไอคอนปิดเสียง
        };

        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');

        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        // เริ่มต้น Swiper
        const swiper = new Swiper('.swiper-container', {
            loop: true,
            slidesPerView: 4,
            spaceBetween: 30,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            breakpoints: {

                320: {
                    slidesPerView: 1,
                    spaceBetween: 10
                },
                480: {
                    slidesPerView: 2,
                    spaceBetween: 10
                },
                640: {
                    slidesPerView: 3,
                    spaceBetween: 15
                },
                768: {
                    slidesPerView: 4,
                    spaceBetween: 20
                },
                1024: {
                    slidesPerView: 6,
                    spaceBetween: 30
                }
            },
        });

        function toggleWhitelist(animeId, button) {
            console.log('Sending whitelist request for Anime ID:', animeId); // ตรวจสอบ animeId ที่ส่ง
            const isWhitelisted = button.classList.contains('whitelisted');
            const action = isWhitelisted ? 'remove' : 'add';

            const formData = new FormData();
            formData.append('anime_id', animeId);
            formData.append('action', action);

            fetch('php/whitelist.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Response from whitelist.php:', data); // ตรวจสอบการตอบกลับ
                    if (data.status === 'success') {
                        if (action === 'add') {
                            button.classList.add('whitelisted');
                            button.innerHTML = '<i class="fas fa-heart"></i> Whitelisted';
                            // แสดง Toast สำหรับการเพิ่มสำเร็จ
                            showToast('เพิ่มอนิเมะลงใน Whitelist สำเร็จ!', 'success');
                        } else {
                            button.classList.remove('whitelisted');
                            button.innerHTML = '<i class="fas fa-heart"></i> Whitelist';
                            // แสดง Toast สำหรับการลบสำเร็จ
                            showToast('ลบอนิเมะออกจาก Whitelist สำเร็จ!', 'success');
                        }
                    } else {
                        // แสดง Toast สำหรับข้อผิดพลาด
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // แสดง Toast สำหรับข้อผิดพลาด
                    showToast('เกิดข้อผิดพลาด กรุณาลองใหม่ภายหลัง', 'error');
                });
        }

        // ฟังก์ชันสำหรับสร้างและแสดง Toast (ต้องแน่ใจว่าได้เพิ่มไว้ใน HTML แล้ว)
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toast-container');

            const toast = document.createElement('div');
            toast.classList.add('toast', type);
            toast.innerHTML = `
        <div class="toast-message">${message}</div>
        <span class="toast-close">&times;</span>
    `;

            toastContainer.appendChild(toast);

            // แสดง Toast ด้วยการเพิ่มคลาส 'show'
            setTimeout(() => {
                toast.classList.add('show');
            }, 100); // เล็กน้อยเพื่อให้ CSS transition ทำงาน

            // ซ่อน Toast หลังจาก 3 วินาที
            setTimeout(() => {
                toast.classList.remove('show');
                // ลบ Toast ออกจาก DOM หลังจากการซ่อน
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);

            // ปิด Toast เมื่อคลิกที่ปุ่มปิด
            toast.querySelector('.toast-close').addEventListener('click', () => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            });
        }

        // เมื่อผู้ใช้คลิกปุ่ม Whitelist ใน anime-info-card
        const whitelistBtnsMain = document.querySelectorAll('.anime-info-card .whitelist-btn');
        whitelistBtnsMain.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const animeId = btn.getAttribute('data-anime-id');
                console.log('Clicked Whitelist Button with Anime ID:', animeId); // ตรวจสอบ animeId ที่ถูกคลิก
                toggleWhitelist(animeId, btn);
            });
        });


        // โมดอลสำหรับแสดงวิดีโอ
        const modal = document.getElementById('trailer-modal');
        const closeBtn = document.querySelector('.close-btn');
        const trailerVideo = document.getElementById('trailer-video');

        // เมื่อผู้ใช้คลิกปุ่ม Watch Trailer
        const trailerBtns = document.querySelectorAll('.trailer-btn');
        trailerBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const animeInfoCard = document.querySelector('.anime-info-card');
                const trailerUrl = animeInfoCard.dataset.trailerUrl || 'https://www.youtube.com/embed/x3_Pcru8SS8';

                trailerVideo.src = trailerUrl + '?autoplay=1';

                // แสดงโมดอล
                modal.style.display = 'block';
            });
        });


        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            trailerVideo.src = '';
        });


        window.addEventListener('click', (event) => {
            if (event.target == modal) {
                modal.style.display = 'none';
                trailerVideo.src = '';
            }
        });


        function getStars(rating) {
            const fullStars = Math.floor(rating);
            const halfStar = (rating - fullStars) >= 0.5 ? 1 : 0;
            const emptyStars = 5 - fullStars - halfStar;

            let starsHtml = '';

            for (let i = 0; i < fullStars; i++) {
                starsHtml += '<i class="fas fa-star"></i>';
            }

            if (halfStar) {
                starsHtml += '<i class="fas fa-star-half-alt"></i>';
            }

            for (let i = 0; i < emptyStars; i++) {
                starsHtml += '<i class="far fa-star"></i>';
            }

            return starsHtml;
        }


        function updateAnimeInfo(anime) {

            const animeInfoCard = document.querySelector('.anime-info-card');
            const animeImage = animeInfoCard.querySelector('.anime-image img');
            const animeTitle = animeInfoCard.querySelector('.anime-title');
            const animeMeta = animeInfoCard.querySelector('.anime-meta');
            const animeStudio = animeInfoCard.querySelector('.anime-studio');
            const animeRating = animeInfoCard.querySelector('.anime-rating');
            const animeStatus = animeInfoCard.querySelector('.anime-status');
            const animeSynopsis = animeInfoCard.querySelector('.anime-synopsis');
            const whitelistBtn = animeInfoCard.querySelector('.whitelist-btn');
            const trailerBtn = animeInfoCard.querySelector('.trailer-btn');

            animeImage.src = anime.image_url;
            animeImage.alt = anime.title;
            animeTitle.textContent = anime.title;
            animeMeta.innerHTML = `
    <span>จำนวนตอน: ${anime.episode_count !== null ? anime.episode_count : 'Unknown'}</span> |
    <span>ปีที่ฉาย: ${anime.release_year}</span> |
    <span>ประเภท: ${anime.genre}</span>
`;
            animeStudio.textContent = `สตูดิโอ: ${anime.studio}`;
            animeRating.innerHTML = `คะแนน: ${getStars(anime.rating)} (${anime.rating}/5)`;
            animeStatus.textContent = `สถานะ: ${anime.status}`;
            animeSynopsis.textContent = anime.synopsis;

            if (anime.background_video_url) {
                video.src = anime.background_video_url;
                video.play().catch(error => {
                    console.error('Error attempting to play the background video:', error);
                });
            } else {
                video.src = 'video/SummerPocket-PV.mp4';
                video.play().catch(error => {
                    console.error('Error attempting to play the background video:', error);
                });
            }

            animeInfoCard.dataset.trailerUrl = anime.trailer_url;


            whitelistBtn.setAttribute('data-anime-id', anime.id);


            whitelistBtn.classList.remove('whitelisted');
            whitelistBtn.innerHTML = '<i class="fas fa-heart"></i> Whitelist';


            fetch('php/check_whitelist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'anime_id': anime.id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (data.whitelisted) {
                            whitelistBtn.classList.add('whitelisted');
                            whitelistBtn.innerHTML = '<i class="fas fa-heart"></i> Whitelisted';
                        } else {
                            whitelistBtn.classList.remove('whitelisted');
                            whitelistBtn.innerHTML = '<i class="fas fa-heart"></i> Whitelist';
                        }
                    } else {
                        console.error('Error checking whitelist:', data.message);

                        whitelistBtn.classList.remove('whitelisted');
                        whitelistBtn.innerHTML = '<i class="fas fa-heart"></i> Whitelist';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);

                    whitelistBtn.classList.remove('whitelisted');
                    whitelistBtn.innerHTML = '<i class="fas fa-heart"></i> Whitelist';
                });
        }



        const animeCards = document.querySelectorAll('.anime-card');
        animeCards.forEach(card => {
            card.addEventListener('click', () => {
                const anime = {
                    id: card.dataset.id,
                    title: card.dataset.title,
                    episode_count: card.dataset.episode_count,
                    release_year: card.dataset.release_year,
                    genre: card.dataset.genre,
                    studio: card.dataset.studio,
                    status: card.dataset.status,
                    synopsis: card.dataset.synopsis,
                    image_url: card.dataset.image_url,
                    background_video_url: card.dataset.background_video_url,
                    trailer_url: card.dataset.trailer_url,
                    rating: card.dataset.rating
                };
                updateAnimeInfo(anime);
            });
        });

        const animeImages = document.querySelectorAll('.swiper-slide .anime-card-image img');
        animeImages.forEach(img => {
            img.addEventListener('click', () => {
                const parentCard = img.closest('.anime-card');
                const anime = {
                    id: parentCard.getAttribute('data-id'),
                    title: parentCard.getAttribute('data-title'),
                    episode_count: parentCard.getAttribute('data-episode_count'),
                    release_year: parentCard.getAttribute('data-release_year'),
                    genre: parentCard.getAttribute('data-genre'),
                    studio: parentCard.getAttribute('data-studio'),
                    status: parentCard.getAttribute('data-status'),
                    synopsis: parentCard.getAttribute('data-synopsis'),
                    image_url: parentCard.getAttribute('data-image_url'),
                    background_video_url: parentCard.getAttribute('data-background_video_url'),
                    trailer_url: parentCard.getAttribute('data-trailer_url'),
                    rating: parentCard.getAttribute('data-rating')
                };
                updateAnimeInfo(anime);
            });
        });
    </script>

</body>

</html>