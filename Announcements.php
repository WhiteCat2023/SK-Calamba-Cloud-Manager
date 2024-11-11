<?php
require 'db.php';

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: default.php');
    exit();
}

                // Check if the form has been submitted for creating an announcement
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
                    $title = $_POST['title'];
                    $description = $_POST['description'];
                    $created_by = $_SESSION['adminId'];
                    
                    $sql = "INSERT INTO announcements (ann_title, ann_content, ann_created_at, created_by) VALUES (?, ?, NOW(), ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $title, $description, $created_by);
                    
                    if ($stmt->execute()) {
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        echo "<script>
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'New Post failed to create'
                                });
                            </script>";
                    }
                }
                
                // Check if the form has been submitted for updating an announcement
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
                    $editTitle = $_POST['edit-title'];
                    $editContent = $_POST['edit-content'];
                    $editId = $_POST['edit-id'];
                    
                    $sql = "UPDATE announcements SET ann_title = ?, ann_content = ?, ann_created_at = NOW() WHERE ann_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssi", $editTitle, $editContent, $editId);
                    
                    if ($stmt->execute()) {
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        echo "<script>
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'New Post failed to update'
                                });
                            </script>";
                    }
                }
                
                // Check if the form has been submitted for deleting an announcement
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
                    $ann_id = $_POST['annsid'];
                    
                    $sql = "DELETE FROM announcements WHERE ann_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $ann_id);
                    
                    if ($stmt->execute()) {
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        echo "Error: " . $stmt->error;
                    }
                }
function time_ago($timestamp){
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes = round($seconds/60);
    $hours = round($seconds/3600);
    $days = round($seconds/86400);
    $weeks = round($seconds/604800);
    $months = round($seconds/2629440);
    $years = round($seconds/31553280);
    
    if($seconds <= 60){
        return "Just now";
    } else if($minutes <= 60){
        if($minutes == 1){
            return "1 " . "minute ago";
        }else{
            return "$minutes " . "minutes ago";
        }
    } else if($hours <= 24){
        return $hours == 1 ? "1 " . "hour ago" : "$hours " . "hours ago";
    } else if($days <= 30){
        return $days == 1 ? "1 " . "day ago" : "$days " . "days ago";
    } else if($months <= 12){
        return $months == 1 ? "1 " . "month ago" : "$months " . "months ago";
    } else{
        return $years == 1 ? "1 " . "year ago" : "$years " . "years ago";
    }
    

}


// Variables for pagination
$rows_per_page = 50; // Number of rows per page
$currentPage = isset($_GET['page-nr']) ? (int)$_GET['page-nr'] : 1;
$start = ($currentPage - 1) * $rows_per_page;

// Retrieve the search term if it is set
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Base SQL query for counting total rows
$countSql = "SELECT COUNT(*) AS total FROM announcements";
$params = []; // Parameters for binding
$types = ""; // Types for bind_param

// Modify SQL query if search is applied
if ($searchTerm) {
    $countSql .= " WHERE ann_title LIKE ? OR ann_content LIKE ?";
    $likeTerm = '%' . $searchTerm . '%';
    $params[] = $likeTerm;
    $params[] = $likeTerm;
    $types .= "ss";
}

// Prepare and execute count statement
$countStmt = $conn->prepare($countSql);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = $countResult->fetch_assoc()['total'];
$countStmt->close();

$pages = ceil($totalRows / $rows_per_page); // Total number of pages

// Retrieve announcements with pagination and search
$sql = "SELECT * FROM announcements";
if ($searchTerm) {
    $sql .= " WHERE ann_title LIKE ? OR ann_content LIKE ?";
}
$sql .= " LIMIT ?, ?";
$stmt = $conn->prepare($sql);

if ($searchTerm) {
    $types .= "ii";
    $params[] = $start;
    $params[] = $rows_per_page;
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $start, $rows_per_page);
}

$stmt->execute();
$result = $stmt->get_result();

$announcements = [];
while ($row = $result->fetch_assoc()) {
    $announcements[] = $row; 
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.lineicons.com/5.0/lineicons.css" />
    <link href="./CSS/nav-aside.css" rel="stylesheet"/>
    <link rel="icon" href="./assets/logo.png" type="image/x-icon">
    <script src="https://kit.fontawesome.com/205dbd136e.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Announcements</title>
</head>
<body>
    <div class="d-flex w-100 h-100">
        <?php include("./Components/nav-aside.html") ?>
        <div class="main">
            <?php include("./Components/nav-bar.html") ?>   
            <main>
                <div>
                    <div class="pt-4">
                        <div class="container mb-4">
                            <h1 class="text-center"><i class="lni lni-gemini small-star"></i><strong>Announcements</strong><i class="lni lni-gemini small-star"></i></h1>
                        </div>
                    </div>
                    
                    <div class="modal fade" id="exampleModal" aria-hidden="true" aria-labelledby="exampleModalLabel" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalToggleLabel">New Post</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="title">Title:</label>
                                            <input type="text" class="form-control" id="title" name="title" placeholder="Enter announcement title" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="description">Description:</label>
                                            <textarea rows="8" class="form-control" id="description" name="description" placeholder="Enter announcement description" required></textarea>
                                        </div>       
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-primary" data-bs-target="#exampleModalToggle2" data-bs-toggle="modal" data-bs-dismiss="modal" type="submit" name="action" value="create">Create Announcement</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="container" >
                        <div class="d-flex flex-wrap justify-content-between">
                                    <h3 class="mb-1 me-3">Announcements List</h3>
                                    <div class="d-flex flex-grow-1 justify-content-between align-items-center">
                                        <button type="button" class="btn  btn-outline-secondary new-btn me-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                            <i class="lni lni-plus"></i>
                                            <span>New Post</span>
                                        </button>
                                        <form method="get" class="d-flex align-items-center w-100" style="max-width:300px; flex:1;">
                                            <input type="search" name="search" class="form-control me-2" placeholder="Search" value="<?= htmlspecialchars($searchTerm) ?>"/> <!-- Keep the value of search term -->
                                            <button type="submit" class="btn btn-outline-secondary"><i class="lni lni-search-2"></i></button> <!-- Add a search button -->
                                        </form>
                                    </div>
                                    
                                </div>
                        <hr>
                        <div class="overflow-auto position-relative border" style="height:50dvh; border-radius:20px;">
                            <div id="list" class="list-group overflow-auto position-relative h-100">
                                <div class="d-flex justify-content-between position-sticky top-0 start-0 py-2 px-3 border-bottom" style="background:#f9f9f9; z-index:99; border-top-left-radius:20px; border-top-right-radius:20px;">
                                    <button class="border-0" style="background:transparent;" onclick="reloadPage()"><i class="fa-solid fa-rotate-right"></i></button>
                                    <!-- Pagination Links -->
                                    <div class="pagination">
                                        <span> <?= $currentPage ?> of <?= $pages ?></span>
                                        <a href="?page-nr=1&search=<?= urlencode($searchTerm) ?>"><i class="lni lni-angle-double-left pagination-icon"></i></a>
                                        <?php if ($currentPage > 1): ?>
                                            <a href="?page-nr=<?= $currentPage - 1 ?>&search=<?= urlencode($searchTerm) ?>"><i class="fa-solid fa-chevron-left pagination-icon"></i></a>
                                        <?php else: ?>
                                            <span><i class="fa-solid fa-chevron-left"></i></span>
                                        <?php endif; ?>
                                        <?php if ($currentPage < $pages): ?>
                                            <a href="?page-nr=<?= $currentPage + 1 ?>&search=<?= urlencode($searchTerm) ?>"><i class="fa-solid fa-chevron-right pagination-icon"></i></a>
                                        <?php else: ?>
                                            <span><i class="fa-solid fa-chevron-right"></i></span>
                                        <?php endif; ?>
                                        <a href="?page-nr=<?= $pages ?>&search=<?= urlencode($searchTerm) ?>"><i class="lni lni-angle-double-right pagination-icon"></i></a>
                                    </div>
                                </div>
                                <?php if (!empty($announcements)): ?>
                                    <?php foreach ($announcements as $index => $announcement): ?>
                                        <div class="list-group-item list-group-item-action" onclick="showContent(<?= $index ?>)" id="<?= htmlspecialchars($announcement['ann_id']) ?>" style="cursor:pointer;">
                                            <div class="d-flex w-100 justify-content-between">
                                                <?php 
                                                    // Prepare the SQL statement
                                                    $stmt = $conn->prepare("
                                                        SELECT 
                                                            sk_users.user_name, 
                                                            sk_users.user_email 
                                                        FROM 
                                                            announcements 
                                                        JOIN 
                                                            sk_users ON announcements.created_by = sk_users.user_id 
                                                        WHERE 
                                                            announcements.ann_id = ?
                                                    ");
                                                    
                                                    // Bind the post ID parameter
                                                    $stmt->bind_param("i", $announcement['ann_id']); 
                                                    
                                                    // Execute the statement
                                                    $stmt->execute();
                                                    
                                                    // Get the result
                                                    $result = $stmt->get_result();
                                                    
                                                    // Fetch the result as an associative array
                                                    $user = $result->fetch_assoc();
                                                ?>
                                                <small class="text-muted" id="created-by"><?= htmlspecialchars($user['user_name']) ?></small>
                                                <small style="display:none;" id="created-by-email"><?= htmlspecialchars($user['user_email']) ?></small>
                                                <h6 class="mb-1 overflow-hidden w-50" style="white-space:nowrap; text-overflow:ellipsis;"><?= htmlspecialchars($announcement['ann_title']) ?></h6>
                                                <small class="text-muted overflow-hidden" id="time">
                                                    
                                                    <?=
                                                        htmlspecialchars(time_ago($announcement['ann_created_at']))
                                                        
                                                        // $announcementDate = new DateTime($announcement['ann_created_at']);
                                                        // $currentDate = new DateTime();
                                                        
                                                        // $interval = $currentDate->diff($announcementDate);
                                                        
                                                        // if ($interval->d == 0 && $interval->h == 0 && $interval->i < 60) {
                                                        //     // Display time in h:i A format
                                                        //     echo htmlspecialchars($announcementDate->format('h:i A')); 
                                                        // } else {
                                                        //     // Display date in M. d, Y format
                                                        //     echo htmlspecialchars($announcementDate->format('M. d, Y')); 
                                                        // }
                                                    ?>
                                                </small>
                                            </div>
                                            <p class="mb-1 overflow-hidden w-50" style="white-space:nowrap; text-overflow:ellipsis; display:none;"><?= htmlspecialchars($announcement['ann_content']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class=" w-100 h-100 d-flex align-items-center justify-content-center">No Announcement found.</div>
                                <?php endif; ?>
                            </div>
                            <div id="content-container" class="h-100 position-relative overflow-auto" style="display: none;" >
                                <div class="w-100 position-sticky top-0 start-0" style="background-color:#f9f9f9; z-index:1; border-top-left-radius:20px; border-top-right-radius:20px;">
                                    <button id="backBtn" onclick="returnList()" class="btn mt-2"><i class="lni lni-chevron-left" style="font-size: 30px;"></i></button>
                                </div>
                                <div class="px-3 ">
                                    <div class="d-flex justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <img class="profile-logo me-2" style="padding:0 !important;"  src="https://i.pinimg.com/originals/f1/0f/f7/f10ff70a7155e5ab666bcdd1b45b726d.jpg">
                                            <div class="p-2">
                                                <p id="display-name" class="m-0" style="font-size:.80rem;"></p>
                                                <small id="display-email" class="m-0 small-faded-text"></small>
                                            </div>
                                        </div>
                                        
                                        <small id="display-time" class="p-2" style="font-size:.80rem;"></small>
                                    </div>
                                    <div class="d-flex justify-content-between mt-4 w-100">
                                        <h5 id="display-title" style="overflow-wrap:break-word; font-size:1rem;" class="w-75">Title</h5>
                                        <div>
                                            <button type="button" style="border:none; background:transparent;" data-bs-toggle="modal" data-bs-target="#editModal" id="edit-btn"><i class="fa-regular fa-pen-to-square text-success"></i></button>
                                            <button type="button" style="border:none; background:transparent;" onclick="confirmDelete()" id="delete-btn"><i class="fa-regular fa-trash-can text-danger"></i></button>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="" >
                                        <p id="display-content" style="font-size:.90rem;">content</p>
                                    </div>
                                </div>
                                <div class="modal fade" id="editModal" aria-hidden="true" aria-labelledby="editModalLabel" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalToggleLabel">Edit Post</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label for="edit-title">Title:</label>
                                                        <input type="text" class="form-control" id="edit-title" name="edit-title">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="edit-content">Description:</label>
                                                        <textarea rows="8" class="form-control" id="edit-content" name="edit-content" style="resize:none;"></textarea>
                                                    </div>       
                                                </div>
                                                <div class="modal-footer">
                                                    <input id="edit-id" name="edit-id" style="display:none;">
                                                    <button class="btn btn-primary" data-bs-target="#exampleModalToggle2" data-bs-toggle="modal" data-bs-dismiss="modal" type="submit" name="action" value="update">Edit Announcement</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="./JS/reload.js"></script>
    <script src="./JS/announcements_content.js"></script>
    <script src="./JS/transitions.js"></script>
    <script src="./JS/confirmDelete_Announcements.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
