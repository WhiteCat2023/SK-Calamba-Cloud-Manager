<?php
require 'db.php';

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: default.php');
    exit();
}

// Variables for pagination
$rows_per_page = 50; // Number of rows per page
$currentPage = isset($_GET['page-nr']) ? (int)$_GET['page-nr'] : 1;
$start = ($currentPage - 1) * $rows_per_page;

// Retrieve the search term if it is set
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Base SQL query for counting total rows
$countSql = "SELECT COUNT(*) AS total FROM feedback" ;
$params = []; // Parameters for binding
$types = ""; // Types for bind_param

// Modify SQL query if search is applied
if ($searchTerm) {
    $countSql .= " WHERE feedback_title LIKE ? OR feedback_description LIKE ?";
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
$sql = "SELECT * FROM feedback";
if ($searchTerm) {
    $sql .= " WHERE feedback_title LIKE ? OR feedback_description LIKE ?";
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

$feedbacks = [];
while ($row = $result->fetch_assoc()) {
    $feedbacks[] = $row; 
}

    // Check if the form has been submitted for deleting an announcement
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
                    $feed_id = $_POST['id'];
                    
                    $sql = "DELETE FROM feedback WHERE feedback_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $feed_id);
                    
                    if ($stmt->execute()) {
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        echo "Error: " . $stmt->error;
                    }
                }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/205dbd136e.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.lineicons.com/5.0/lineicons.css" />
    <link href="./CSS/nav-aside.css" rel="stylesheet"/>
    <link rel="icon" href="./assets/logo.png" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Feedbacks</title>

</head>
<body class="d-flex">
    <?php include("./Components/nav-aside.html") ?>
    <div class="main">
        <?php include("./Components/nav-bar.html") ?>
        <main>
            <div>
                <div class="pt-4">
                    <div class="container mb-4">
                        <h1 class="text-center"><i class="lni lni-gemini small-star"></i><strong>Feedbacks</strong><i class="lni lni-gemini small-star"></i></h1>
                    </div>
                </div>
                <div class="container">
                    <div class="d-flex flex-wrap justify-content-between">
                        <h3 class="mb-1 me-3">Feedback List</h3>
                        <form method="get" class="d-flex align-items-center w-100" style="max-width:300px; flex:1;">
                            <input type="search" name="search" class="form-control me-2" placeholder="Search" value="<?= htmlspecialchars($searchTerm) ?>"/> <!-- Keep the value of search term -->
                            <button type="submit" class="btn btn-outline-secondary"><i class="lni lni-search-2"></i></button> <!-- Add a search button -->
                        </form>
                    </div>
                    <hr>
                    <div class=" border" style="height:50dvh; border-radius:20px;">
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
                            <?php if (!empty($feedbacks)): ?>
                                <?php foreach ($feedbacks as $index => $feedback): ?>
                                    <div class="list-group-item list-group-item-action" onclick="showContent(<?= $index ?>)" id="<?= htmlspecialchars($feedback['feedback_id']) ?>" style="cursor:pointer;">
                                        <div class="d-flex w-100 justify-content-between">
                                            <?php 
                                                // Prepare the SQL statement
                                                $stmt = $conn->prepare("
                                                    SELECT 
                                                        sk_users.user_name, 
                                                        sk_users.user_email 
                                                    FROM 
                                                        feedback 
                                                    JOIN 
                                                        sk_users ON feedback.feedback_by = sk_users.user_id 
                                                    WHERE 
                                                        feedback.feedback_id = ?
                                                ");
                                                        
                                                // Bind the post ID parameter
                                                $stmt->bind_param("i", $feedback['feedback_id']); 
                                                        
                                                // Execute the statement
                                                $stmt->execute();
                                                        
                                                // Get the result
                                                $result = $stmt->get_result();
                                                        
                                                // Fetch the result as an associative array
                                                $user = $result->fetch_assoc();
                                            ?>
                                            <small class="text-muted" id="created-by"><?= htmlspecialchars($user['user_name']) ?></small>
                                            <small style="display:none;" id="created-by-email"><?= htmlspecialchars($user['user_email']) ?></small>
                                            <h6 class="mb-1 overflow-hidden w-50" style="white-space:nowrap; text-overflow:ellipsis;"><?= htmlspecialchars($feedback['feedback_title']) ?></h6>
                                            <small class="text-muted overflow-hidden" id="time">
                                                <?php
                                                    $feedbackDate = new DateTime($feedback['feedback_created_at']);
                                                    $currentDate = new DateTime();
                                                            
                                                    $interval = $currentDate->diff($feedbackDate);
                                                            
                                                    if ($interval->d == 0 && $interval->h == 0 && $interval->i < 60) {
                                                        // Display time in h:i A format
                                                        echo htmlspecialchars($feedbackDate->format('h:i A')); 
                                                    } else {
                                                        // Display date in M. d, Y format
                                                        echo htmlspecialchars($feedbackDate->format('M. d, Y')); 
                                                    }
                                                ?>
                                            </small>
                                        </div>
                                        <p class="mb-1 overflow-hidden w-50" style="white-space:nowrap; text-overflow:ellipsis; display:none;"><?= htmlspecialchars($feedback['feedback_description']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class=" w-100 h-100 d-flex align-items-center justify-content-center">No Feedback found.</div>
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
                                        <button type="button" style="border:none; background:transparent;" onclick="confirmDelete()" id="delete-btn"><i class="fa-regular fa-trash-can text-danger"></i></button>
                                    </div>
                                </div>
                                <hr>
                                <div class="" >
                                    <p id="display-content" style="font-size:.90rem;">content</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="./JS/reload.js"></script>
    <script src="./JS/transitions.js"></script>
    <script>
        // Function to show content based on the clicked item
        function showContent(index) {
            console.log(index);
            const items = document.querySelectorAll('.list-group-item');
            
            const deleteBtn = document.getElementById('delete-btn');
                
            const displayTitle = document.getElementById('display-title');
            const displayContent = document.getElementById('display-content');
            const displayTime = document.getElementById('display-time');
            const displayName = document.getElementById('display-name');
            const displayEmail = document.getElementById('display-email');
            
            const list = document.getElementById('list');
            const contentContainer = document.getElementById('content-container');
                
            // Clear the current aria-current attribute from all items
            items.forEach(item => {
                item.removeAttribute("aria-current");
            });
                
            // Set the aria-current attribute for the clicked item
            const selectedItem = items[index];
            if (selectedItem) {
                selectedItem.setAttribute("aria-current", "true");
                deleteBtn.value = selectedItem.id;
                
                // Get the title and content from the selected item
                const title = selectedItem.querySelector('h6').textContent;
                const content = selectedItem.querySelector('p').textContent;
                
                // Hide the list and show the content container
                list.style.display = "none";
                contentContainer.style.display = "block";
                
                // Set the title and content in the display area
                displayTitle.innerHTML = title;
                displayContent.innerHTML = content;
                displayTime.innerHTML = smallItems(selectedItem, "time");
                displayName.innerHTML = smallItems(selectedItem, "created-by");
                displayEmail.innerHTML = smallItems(selectedItem, "created-by-email");
            }
        }
        
        function smallItems(selectedItem, id){
            const small = selectedItem.querySelectorAll('small');
             
            for (let smallItem of small) {
                if(smallItem.id === id){
                    return smallItem.textContent;
                }
            }
            
            return;
        }
        //function to return to list view
        function returnList(){
            const back = document.getElementById('backBtn');
        
            const list = document.getElementById('list');
            const contentContainer = document.getElementById('content-container');
        
            back.addEventListener('click', () => {
                contentContainer.style.display = "none";
                list.style.display = "block";
            });
        }
        function confirmDelete() {
            const deleteBtn = document.getElementById('delete-btn');
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create a new FormData object
                    const formData = new FormData();
                    formData.append('id', deleteBtn.value);
                    formData.append('action', 'delete');
                    
                    // Use fetch API to send the request
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.ok) {
                            // If the response is OK, show a success message
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'User deleted successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Refresh the page or remove the row from the table
                                location.reload(); // Reload the page
                            });
                        } else {
                            // Handle error response
                            Swal.fire('Error!', 'There was a problem deleting the user.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'There was a problem deleting the user.', 'error');
                    });
                }
            });
        }
    </script>
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
