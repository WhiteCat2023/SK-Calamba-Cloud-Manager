<?php
    require 'db.php';

    session_start();
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: default.php');
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $request_id = $_POST['file_id'] ?? null;
        $action = $_POST['action'] ?? null;
    
        if (!$request_id || !$action) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request. Missing file_id or action.']);
            exit;
        }
    
        $stmt = $conn->prepare('SELECT file_name, file_path FROM deletion_requests WHERE file_id = ?');
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            $file = $result->fetch_assoc();
            $file_name = $file['file_name'];
            $file_path = $file['file_path']; 
    
            if (!file_exists($file_path)) {
                echo json_encode(['status' => 'error', 'message' => 'File does not exist on the server.']);
                exit;
            }
    
            if ($action === 'approve') {
                if (unlink($file_path)) {
                    $stmt = $conn->prepare('UPDATE deletion_requests SET file_request_status = "approved" WHERE file_id = ?');
                    $stmt->bind_param('i', $request_id);
                    
                    if ($stmt->execute()) {
                        echo json_encode(['status' => 'success', 'message' => 'File deleted successfully and request approved.']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to update request status to approved.']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to delete the file from the server.']);
                }
            } elseif ($action === 'reject') {
                $stmt = $conn->prepare('UPDATE deletion_requests SET file_request_status = "rejected" WHERE file_id = ?');
                $stmt->bind_param('i', $request_id);
        
                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Deletion request rejected.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update request status to rejected.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid action provided.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Request not found.']);
        }
    
        $stmt->close();
        $conn->close();
    }


// Variables for pagination
$rows_per_page = 50; // Number of rows per page
$currentPage = isset($_GET['page-nr']) ? (int)$_GET['page-nr'] : 1;
$start = ($currentPage - 1) * $rows_per_page;

// Retrieve the search term if it is set
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Base SQL query for counting total rows
$countSql = "SELECT COUNT(*) AS total FROM deletion_requests WHERE file_request_status = 'pending'";
$params = []; // Parameters for binding
$types = ""; // Types for bind_param

// Modify SQL query if search is applied
if ($searchTerm) {
    $countSql .= " AND (requested_by LIKE ? OR file_name LIKE ?)";
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
$sql = "SELECT * FROM deletion_requests WHERE file_request_status = 'pending'";
if ($searchTerm) {
    $sql .= " AND (requested_by LIKE ? OR file_name LIKE ?)";
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

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row; 
}

$stmt->close();
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
    <title>Deletion Request</title>
</head>
<body class="d-flex">
        <?php include("./Components/nav-aside.html")?>

        <!-- Main Content -->
        <div class="main">
            <?php include("./Components/nav-bar.html") ?>
            <main>
                <div>
                    <div class="pt-4">
                        <div class="container mb-4">
                            <h1 class="text-center"><i class="lni lni-gemini small-star"></i><strong>Announcements</strong><i class="lni lni-gemini small-star"></i></h1>
                        </div>
                    </div>
                    <div class="container">
                        <div class="d-flex flex-wrap justify-content-between">
                            <h3 class="mb-0 me-3">Request List</h3>
                            <div class="d-flex flex-grow-1 justify-content-end align-items-center">
                                <form method="get" class="d-flex align-items-center w-100" style="max-width:300px; flex:1;">
                                    <input type="search" name="search" class="form-control me-2" placeholder="Search" value="<?= htmlspecialchars($searchTerm) ?>"/> <!-- Keep the value of search term -->
                                    <button type="submit" class="btn btn-outline-secondary"><i class="lni lni-search-2"></i></button> <!-- Add a search button -->
                                </form>
                            </div>
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
                                <?php if (!empty($requests)): ?>
                                    <?php foreach ($requests as $index => $request): ?>
                                        <div class="list-group-item list-group-item-action" onclick="showContent(<?= $index ?>)" id="<?= htmlspecialchars($request['file_id']) ?>" style="cursor:pointer;">
                                            <div class="d-flex w-100 justify-content-between">
                                                <?php 
                                                    // Prepare the SQL statement
                                                    $stmt = $conn->prepare("
                                                        SELECT 
                                                            sk_users.user_name, 
                                                            sk_users.user_email 
                                                        FROM 
                                                            deletion_requests 
                                                        JOIN 
                                                            sk_users ON deletion_requests.requested_by = sk_users.user_id 
                                                        WHERE 
                                                            deletion_requests.file_id = ?
                                                    ");
                                                        
                                                    // Bind the post ID parameter
                                                    $stmt->bind_param("i", $request['file_id']); 
                                                        
                                                    // Execute the statement
                                                    $stmt->execute();
                                                        
                                                    // Get the result
                                                    $result = $stmt->get_result();
                                                        
                                                    // Fetch the result as an associative array
                                                    $user = $result->fetch_assoc();
                                                ?>
                                                <small id="itemId" style="display:none;"><?= htmlspecialchars($request['file_id']) ?></small>
                                                <small class="text-muted" id="created-by"><?= htmlspecialchars($user['user_name']) ?></small>
                                                <small style="display:none;" id="created-by-email"><?= htmlspecialchars($user['user_email']) ?></small>
                                                <h6 class="mb-1 overflow-hidden w-50" style="white-space:nowrap; text-overflow:ellipsis;"><?= htmlspecialchars($request['file_name']) ?></h6>
                                                <small class="text-muted overflow-hidden" id="time">
                                                        
                                                    <?php
                                                        $requestDate = new DateTime($request['file_request_date']);
                                                        $currentDate = new DateTime();
                                                            
                                                        $interval = $currentDate->diff($requestDate);
                                                            
                                                        if ($interval->d == 0 && $interval->h == 0 && $interval->i < 60) {
                                                            // Display time in h:i A format
                                                            echo htmlspecialchars($requestDate->format('h:i A')); 
                                                        } else {
                                                            // Display date in M. d, Y format
                                                            echo htmlspecialchars($requestDate->format('M. d, Y')); 
                                                        }
                                                    ?>
                                                </small>
                                            </div>
                                            <p class="mb-1 overflow-hidden w-50" style="white-space:nowrap; text-overflow:ellipsis; display:none;"><?= htmlspecialchars($request['reason']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class=" w-100 h-100 d-flex align-items-center justify-content-center">No Request found.</div>
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
                                        <p style="display:none;" id="hidden-itemId"></p>
                                        <h5 id="display-title" style="overflow-wrap:break-word; font-size:1rem;" class="w-75">Title</h5>
                                        <div class="d-flex">
                                            <button type="button" onclick="confirmAction('approve')" class="btn btn-sm me-2"><i class="fa-solid fa-thumbs-up text-success"></i></button>
                                            <button type="button" onclick="confirmAction('reject')" class="btn  btn-sm"><i class="fa-solid fa-thumbs-down text-danger"></i></button>
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
        function showContent(index) {
            console.log(index);
            const items = document.querySelectorAll('.list-group-item');
                
            const displayTitle = document.getElementById('display-title');
            const displayContent = document.getElementById('display-content');
            const displayTime = document.getElementById('display-time');
            const displayName = document.getElementById('display-name');
            const displayEmail = document.getElementById('display-email');
            
            const hiddenItemId = document.getElementById('hidden-itemId');
            
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
                
                hiddenItemId.innerHTML = smallItems(selectedItem, 'itemId');
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

        function confirmAction(action) {
            const title = action === 'approve' ? 'Are you sure you want to approve this request?' : 'Are you sure you want to reject this request?';
            const icon = action === 'approve' ? 'warning' : 'info';
            
            const hiddenItemId = document.getElementById('hidden-itemId');
            
            Swal.fire({
                title: title,
                text: "You won't be able to revert this!",
                icon: icon,
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create a new FormData object
                    const formData = new FormData();
                    formData.append('file_id', hiddenItemId.id);
                    formData.append('action', action);
    
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
                                text: 'Announcement deleted successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Refresh the page or remove the row from the table
                                location.reload(); // Reload the page
                            });
                        } else {
                            // Handle error response
                            Swal.fire('Error!', 'There was a problem deleting the announcement.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'There was a problem deleting the announcement.', 'error');
                    });
                }
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
