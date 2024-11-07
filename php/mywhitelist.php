<?php
// mywhitelist.php
session_start();
include '../db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// เชื่อมต่อฐานข้อมูล
$conn = connectDB();

// เตรียมคำสั่ง SQL
$stmt = $conn->prepare("
    SELECT animes.id, animes.title, animes.image_url, animes.rating, animes.trailer_url
    FROM whitelist
    JOIN animes ON whitelist.anime_id = animes.id
    WHERE whitelist.user_id = ?
");

if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

if (!$stmt->bind_param("i", $user_id)) {
    die("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
}


$stmt->bind_result($id, $title, $image_url, $rating, $trailer_url);

$whitelist_animes = [];

// ดึงข้อมูลทีละแถว
while ($stmt->fetch()) {
    $whitelist_animes[] = [
        'id' => $id,
        'title' => $title,
        'image_url' => $image_url,
        'rating' => $rating,
        'trailer_url' => $trailer_url
    ];
}

$stmt->close();

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Whitelist - G-FLIX</title>
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo filemtime('../css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Swiper CSS สำหรับสไลด์ -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css" />
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar">
        <a href="../index.php">
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
                // ดึงข้อมูล profile_image จากฐานข้อมูล
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
                    echo '<img src="../img/default.png" alt="Default Profile Image" class="profile-image">';
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
            </a>
            ';
            } else {
                echo '
            <a href="login.php" class="nav-button login-button">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </a>
            <a href="register.php" class="nav-button signup-button">
                <i class="fas fa-user-plus"></i> สมัครสมาชิก
            </a>
        ';
            }
            ?>
        </div>
    </nav>



    <div class="my-whitelist-page">
        <div class="container">
            <h1>My Whitelist</h1>
            <?php if (!empty($whitelist_animes)): ?>
                <div class="whitelist-grid">
                    <?php foreach ($whitelist_animes as $anime): ?>
                        <div class="whitelist-card">
                            <img src="<?php echo htmlspecialchars($anime['image_url']); ?>" alt="<?php echo htmlspecialchars($anime['title']); ?>">
                            <h3><?php echo htmlspecialchars($anime['title']); ?></h3>
                            <p>คะแนน: <span class="whitelist-rating"><?php echo displayStars($anime['rating']); ?> (<?php echo htmlspecialchars($anime['rating']); ?>/5)</span></p>
                            <div class="whitelist-buttons">
                                <button class="btn remove-whitelist-btn" data-anime-id="<?php echo htmlspecialchars($anime['id']); ?>">
                                    <i class="fas fa-trash"></i> ลบ
                                </button>
                                <button class="btn trailer-btn" data-trailer-url="<?php echo htmlspecialchars($anime['trailer_url']); ?>">
                                    <i class="fas fa-play"></i> ดู Trailer
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>คุณยังไม่ได้เพิ่มอนิเมะใดๆ ใน Whitelist ของคุณ</p>
            <?php endif; ?>
        </div>
    </div>


    <!-- Delete Confirmation Modal -->
    <div id="delete-confirmation-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>ยืนยันการลบ</h2>
            <p>คุณต้องการลบอนิเมะนี้ออกจาก Whitelist ใช่หรือไม่?</p>
            <div class="modal-buttons">
                <button id="confirm-delete-btn" class="btn confirm-btn">ยืนยัน</button>
                <button id="cancel-delete-btn" class="btn cancel-btn">ยกเลิก</button>
            </div>
        </div>
    </div>

    <!-- โมดอลสำหรับแสดงวิดีโอ -->
    <div id="trailer-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <iframe id="trailer-video" src="" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
        </div>
    </div>
    <div id="toast-container"></div>
    <?php
    $conn->close();
    ?>
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script>
        // ฟังก์ชันสำหรับสร้างและแสดง Toast
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

        // ตัวแปรสำหรับเก็บ animeId และปุ่มที่ถูกคลิก
        let animeIdToDelete = null;
        let buttonToDelete = null;

        // ฟังก์ชันสำหรับส่งคำขอ AJAX
        function toggleWhitelist(animeId, button) {
            const action = 'remove';

            const formData = new FormData();
            formData.append('anime_id', animeId);
            formData.append('action', action);

            fetch('whitelist.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // ลบการ์ดออกจากหน้าจอ
                        button.closest('.whitelist-card').remove();
                        // แสดง Toast แทน alert
                        showToast(data.message, 'success');
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

        // เปิด Delete Confirmation Modal
        const deleteModal = document.getElementById('delete-confirmation-modal');
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
        const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
        const closeDeleteModalBtn = deleteModal.querySelector('.close-btn');

        // เมื่อผู้ใช้คลิกปุ่มลบ
        const removeBtns = document.querySelectorAll('.remove-whitelist-btn');
        removeBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                animeIdToDelete = btn.getAttribute('data-anime-id');
                buttonToDelete = btn;
                // เปิดโมดอลการยืนยันการลบ
                deleteModal.style.display = 'block';
            });
        });

        // เมื่อผู้ใช้คลิกปุ่มยืนยันการลบ
        confirmDeleteBtn.addEventListener('click', () => {
            if (animeIdToDelete && buttonToDelete) {
                toggleWhitelist(animeIdToDelete, buttonToDelete);
                // ปิดโมดอลหลังจากยืนยัน
                deleteModal.style.display = 'none';
                // รีเซ็ตตัวแปร
                animeIdToDelete = null;
                buttonToDelete = null;
            }
        });

        // เมื่อผู้ใช้คลิกปุ่มยกเลิกการลบ
        cancelDeleteBtn.addEventListener('click', () => {
            // ปิดโมดอล
            deleteModal.style.display = 'none';
            // รีเซ็ตตัวแปร
            animeIdToDelete = null;
            buttonToDelete = null;
        });

        // เมื่อผู้ใช้คลิกที่ปุ่มปิดโมดอล
        closeDeleteModalBtn.addEventListener('click', () => {
            deleteModal.style.display = 'none';
            // รีเซ็ตตัวแปร
            animeIdToDelete = null;
            buttonToDelete = null;
        });

        // เมื่อผู้ใช้คลิกนอกโมดอล ให้ปิดโมดอล
        window.addEventListener('click', (event) => {
            if (event.target == deleteModal) {
                deleteModal.style.display = 'none';
                // รีเซ็ตตัวแปร
                animeIdToDelete = null;
                buttonToDelete = null;
            }
            if (event.target == modal) {
                modal.style.display = 'none';
                trailerVideo.src = '';
            }
        });

        // โมดอลสำหรับแสดงวิดีโอ
        const trailerModal = document.getElementById('trailer-modal');
        const closeTrailerModalBtn = trailerModal.querySelector('.close-btn');
        const trailerVideo = document.getElementById('trailer-video');

        // เมื่อผู้ใช้คลิกปุ่ม Watch Trailer
        const trailerBtns = document.querySelectorAll('.trailer-btn');
        trailerBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const trailerUrl = btn.getAttribute('data-trailer-url');
                if (trailerUrl) {
                    trailerVideo.src = trailerUrl + '?autoplay=1';
                } else {
                    trailerVideo.src = 'https://www.youtube.com/embed/x3_Pcru8SS8'; // URL เริ่มต้น
                }
                trailerModal.style.display = 'block';
            });
        });

        // เมื่อผู้ใช้คลิกปุ่มปิดโมดอลวิดีโอ
        closeTrailerModalBtn.addEventListener('click', () => {
            trailerModal.style.display = 'none';
            trailerVideo.src = '';
        });

        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');

        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    </script>
</body>

</html>