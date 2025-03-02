<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "php_project";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize user input
function cleanInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Handle User Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["username"], $_POST["email"], $_POST["password"], $_POST["confirm_password"])) {
    $username = cleanInput($_POST["username"]);
    $email = cleanInput($_POST["email"]);
    $password = cleanInput($_POST["password"]);
    $confirm_password = cleanInput($_POST["confirm_password"]);

    // 1. Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("<script>alert('Username already exists. Please choose another one.'); window.history.back();</script>");
    }
    $stmt->close();

    // 2. Check if email is valid & contains '@'
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("<script>alert('Invalid email format. Please enter a valid email with \"@\".'); window.history.back();</script>");
    }

    // 3. Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("<script>alert('Email already exists. Please use a different email.'); window.history.back();</script>");
    }
    $stmt->close();

    // 4. Check if passwords match
    if ($password !== $confirm_password) {
        die("<script>alert('Passwords do not match!'); window.history.back();</script>");
    }

    // 5. Encrypt password before storing it
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // 6. Insert new user into the database
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! Please log in.'); window.location.href='login.html';</script>";
    } else {
        echo "<script>alert('Error during registration. Please try again.'); window.history.back();</script>";
    }
    $stmt->close();
}

$conn->close();
?>

