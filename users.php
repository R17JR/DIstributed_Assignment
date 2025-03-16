<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Get the HTTP method and user ID if provided
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$userId = null;

// Extract user ID from URI if present
if (preg_match('/\/users\/(\d+)/', $uri, $matches)) {
    $userId = $matches[1];
}

switch ($method) {
    case 'GET':
        // Fetch all users
        $sql = "SELECT id, name, email, role FROM users";
        $result = $conn->query($sql);
        $users = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        echo json_encode(['status' => 'success', 'data' => $users]);
        break;

    case 'POST':
        // Create new user
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';

            if (empty($name) || empty($email) || empty($password) || empty($role)) {
                $_SESSION['message'] = 'All fields are required';
                $_SESSION['message_type'] = 'error';
                header('Location: admin_page.php');
                exit();
            }

            // Check if email already exists
            $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            $result = $checkEmail->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['message'] = 'Email already exists';
                $_SESSION['message_type'] = 'error';
                header('Location: admin_page.php');
                exit();
            }

            // Insert new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'User created successfully';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Failed to create user';
                $_SESSION['message_type'] = 'error';
            }
            header('Location: admin_page.php');
            exit();
        }
        break;

    case 'PUT':
        // Update user
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check if user exists
        $checkUser = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $checkUser->bind_param("i", $userId);
        $checkUser->execute();
        $result = $checkUser->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit();
        }

        // Build update query dynamically based on provided fields
        $updateFields = [];
        $types = "";
        $values = [];
        
        if (isset($data['name'])) {
            $updateFields[] = "name = ?";
            $types .= "s";
            $values[] = $data['name'];
        }
        if (isset($data['email'])) {
            $updateFields[] = "email = ?";
            $types .= "s";
            $values[] = $data['email'];
        }
        if (isset($data['password'])) {
            $updateFields[] = "password = ?";
            $types .= "s";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (isset($data['role'])) {
            $updateFields[] = "role = ?";
            $types .= "s";
            $values[] = $data['role'];
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
            exit();
        }

        // Add user ID to values array and types
        $types .= "i";
        $values[] = $userId;

        $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update user']);
        }
        break;

    case 'DELETE':
        // Delete user
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
            exit();
        }

        // Check if user exists
        $checkUser = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $checkUser->bind_param("i", $userId);
        $checkUser->execute();
        $result = $checkUser->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit();
        }

        // Delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete user']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}

$conn->close();
?> 