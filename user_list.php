<?php
require 'db.php';

// Fetch users from the database (same query as in the original file)
$userRole = "user";
$sql = "SELECT user_id, user_name, user_email, user_position, isVerified, isActive FROM sk_users WHERE role = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userRole);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

if (count($users) > 0) {
    foreach ($users as $user) {
        echo "<tr>
                <td>{$user['user_id']}</td>
                <td>{$user['user_name']}</td>
                <td>{$user['user_email']}</td>
                <td>{$user['user_position']}</td>
                <td>" . ($user['isVerified'] ? 'Yes' : 'No') . "</td>
                <td>" . ($user['isActive'] ? 'Yes' : 'No') . "</td>
                <td>
                    <form method='POST'>
                        <input type='hidden' name='user_id' value='{$user['user_id']}'>
                        <button type='submit' name='action' value='activate' class='btn btn-success'>Activate</button>
                    </form>
                </td>
                <td>
                    <form method='POST'>
                        <input type='hidden' name='user_id' value='{$user['user_id']}'>
                        <button type='submit' name='action' value='delete' class='btn btn-danger delete-btn'>Delete</button>
                    </form>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='8' class='text-center'>No users found.</td></tr>";
}
?>
