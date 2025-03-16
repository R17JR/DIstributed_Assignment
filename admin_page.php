<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once 'config.php';

// Fetch all users
$sql = "SELECT id, name, email, role FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .users-table th, .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .users-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 500;
        }

        .users-table tr:hover {
            background-color: #f8f9ff;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #667eea;
            color: white;
        }

        .btn-delete {
            background: #ff7e5f;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .add-user-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .add-user-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .close-btn {
            float: right;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .success-message, .error-message {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease;
        }

        .success-message {
            background: #4CAF50;
            color: white;
        }

        .error-message {
            background: #f44336;
            color: white;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>Admin Dashboard</h1>
        <button class="add-user-btn" onclick="showModal('add-user-modal')">Add New User</button>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="<?php echo $_SESSION['message_type']; ?>-message">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <table class="users-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                        <td class="action-buttons">
                            <button class="btn btn-edit" onclick="showEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>', '<?php echo htmlspecialchars($row['email']); ?>', '<?php echo htmlspecialchars($row['role']); ?>')">Edit</button>
                            <button class="btn btn-delete" onclick="deleteUser(<?php echo $row['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add User Modal -->
    <div id="add-user-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="hideModal('add-user-modal')">&times;</span>
            <h2>Add New User</h2>
            <form action="users.php" method="POST">
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role" required>
                    <option value="">--Select Role--</option>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" class="btn btn-edit">Add User</button>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="edit-user-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="hideModal('edit-user-modal')">&times;</span>
            <h2>Edit User</h2>
            <form id="edit-user-form">
                <input type="hidden" id="edit-user-id" name="id">
                <input type="text" id="edit-name" name="name" placeholder="Name" required>
                <input type="email" id="edit-email" name="email" placeholder="Email" required>
                <input type="password" id="edit-password" name="password" placeholder="New Password (leave empty to keep current)">
                <select id="edit-role" name="role" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" class="btn btn-edit">Update User</button>
            </form>
        </div>
    </div>

    <script>
        // Show modal
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        // Hide modal
        function hideModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Show edit modal with user data
        function showEditModal(id, name, email, role) {
            document.getElementById('edit-user-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-role').value = role;
            showModal('edit-user-modal');
        }

        // Delete user
        function deleteUser(id) {
            if (confirm('Are you sure you want to delete this user?')) {
                fetch(`users.php/users/${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to delete user');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete user');
                });
            }
        }

        // Handle edit form submission
        document.getElementById('edit-user-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('edit-user-id').value;
            const formData = {
                name: document.getElementById('edit-name').value,
                email: document.getElementById('edit-email').value,
                role: document.getElementById('edit-role').value
            };

            // Only include password if it's not empty
            const password = document.getElementById('edit-password').value;
            if (password) {
                formData.password = password;
            }

            fetch(`users.php/users/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update user');
            });
        });
    </script>
</body>
</html>