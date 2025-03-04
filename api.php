<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'config.php';

// API key validation
$headers = apache_request_headers();
if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'Bearer ' . API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get the HTTP method and requested endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request = explode("/", trim($_SERVER['PATH_INFO'], "/"));
$resource = $request[0] ?? null;
$id = $request[1] ?? null;

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if ($resource !== 'tasks') {
    http_response_code(404);
    echo json_encode(['error' => 'Resource not found']);
    exit;
}

switch ($method) {
    case 'GET': // Read
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->bind_param("i", $id);
        } else {
            $stmt = $conn->prepare("SELECT * FROM tasks");
        }
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST': // Create
        if (!isset($data['title'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Title is required']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO tasks (title, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $data['title'], $data['description']);
        $stmt->execute();
        echo json_encode(['id' => $stmt->insert_id, 'message' => 'Task created']);
        break;

    case 'PUT': // Update
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Task ID is required']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssi", $data['title'], $data['description'], $data['status'], $id);
        $stmt->execute();
        echo json_encode(['message' => 'Task updated']);
        break;

    case 'DELETE': // Delete
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Task ID is required']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['message' => 'Task deleted']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
