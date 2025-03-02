<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

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

// Handle Event Creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_event"])) {
  if (!empty($_POST["event_title"]) && !empty($_POST["event_date"]) && !empty($_POST["event_location"])) {
      $user_id = $_SESSION["user_id"];
      $event_title = cleanInput($_POST["event_title"]);
      $event_date = cleanInput($_POST["event_date"]);
      $event_location = cleanInput($_POST["event_location"]);

      $sql = "INSERT INTO events (user_id, event_title, event_date, event_location) VALUES (?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("isss", $user_id, $event_title, $event_date, $event_location);

      if ($stmt->execute()) {
          header("Location: dashboard.php");
          exit();
      }
      $stmt->close();
  } else {
      echo "<script>alert('All fields are required!');</script>";
  }
}

// Handle Event Deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_event"]) && isset($_POST["event_id"])) {
  $event_id = $_POST["event_id"];
  $user_id = $_SESSION["user_id"];

  $sql = "DELETE FROM events WHERE event_id = ? AND user_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $event_id, $user_id);

  if ($stmt->execute()) {
      header("Location: dashboard.php");
      exit();
  }
  $stmt->close();
}

// Handle Event Editing
$edit_event_id = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_event"]) && isset($_POST["event_id"])) {
    $event_id = $_POST["event_id"];
    $new_title = cleanInput($_POST["new_title"]);
    $new_date = cleanInput($_POST["new_date"]);
    $new_location = cleanInput($_POST["new_location"]);
    $user_id = $_SESSION["user_id"];

    $sql = "UPDATE events SET event_title = ?, event_date = ?, event_location = ? WHERE event_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $new_title, $new_date, $new_location, $event_id, $user_id);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    }
    $stmt->close();
}

// Set event ID for editing (if edit button is clicked)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["show_edit_form"]) && isset($_POST["event_id"])) {
  $edit_event_id = $_POST["event_id"];
}


// Fetch all events for the user
$user_id = $_SESSION["user_id"];
$events = [];
$sql = "SELECT event_id, event_title, event_date, event_location FROM events WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();

$username = $_SESSION["username"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background: url("towfiqu-barbhuiya-bwOAixLG0uc-unsplash.jpg") no-repeat center center/cover;
            height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            /* padding: 20px; */
        }

        /* Blurry Background */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: inherit;
            filter: blur(10px);
            z-index: -1;
        }

        /* Header */
        .navbar {
          width: 100%; /* Stretch across full width */
          display: flex;
          justify-content: center; /* Space out logo and button */
          align-items: center;
          padding: 15px 0px;
          background-color: white;
          box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
          position: fixed;
          top: 0;
          left: 0; /* Ensure it starts from the very left */
          z-index: 100;
        }

        .nav-content {
            width: 80%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 20px;
            font-weight: bold;
        }

        .logo i {
            color: #007bff;
            font-size: 22px;
            margin-right: 8px;
        }

        .welcome-text {
            font-size: 18px;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 10px;
        }

        .about-btn, .logout-btn {
            font-size: 16px;
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid black;
            border-radius: 5px;
            transition: 0.3s;
            color: black;
        }

        .logout-btn:hover {
            background-color: black;
            color: white;
        }

        .about-btn:hover {
            background-color: black;
            color: white;
        }

        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            justify-content: space-evenly;
            align-items: flex-start;
            margin: 100px;
            width: 80%;
            gap: 40px;
        }

        /* Event Form */
        .event-form {
            width: 40%;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .event-form h2 {
            margin-top: 15px;
            margin-bottom: 15px;
        }

        .event-form input,
        .event-form button {
            width: 100%; /* Make sure all elements take full width */
            padding: 12px; /* Equal padding for uniform height */
            margin: 5px 0; /* Keep margin consistent */
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            display: block; /* Ensures block-level elements align properly */
            box-sizing: border-box; /* Prevents width inconsistencies */
        }

        /* Fix button alignment */
        .event-form button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        .event-form button:hover {
            background-color: #0056b3;
        }

        /* Event Cards */
        .event-cards {
            width: 35%;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .event-cards h2 {
            font-size: 22px;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .event-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .event-card h3 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .event-icons {
            display: flex;
            gap: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-radius: 5px;
        }

        .edit-btn:hover {
            color: #ffc107;
        }

        .delete-btn:hover {
            color: #dc3545;
        }

        .event-card p {
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Footer */
        .footer {
            width: 100%;
            text-align: center;
            padding: 10px 0;
            background-color: white;
            font-size: 14px;
            color: #333;
            box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            bottom: 0;
            left: 0;
        }
        
    </style>
    <link
      rel="stylesheet"
      href="css/all.min.css"
    />
</head>
<body>

    <!-- Header -->
    <header class="navbar">
        <div class="nav-content">
            <div class="logo">
                <i class="fas fa-calendar-alt"></i>
                <span>EventFlow</span>
            </div>
            <div class="welcome-text">Welcome, <?php echo $username; ?></div>
            <div class="nav-links">
                <a href="#" class="about-btn"><strong>About</strong></a>
                <a href="login.html" class="logout-btn"><strong>Logout</strong></a>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Left: Event Cards -->
        <div class="event-form">
            <h2>Create Event</h2>
            <form method="POST">
                <label for="title">Title</label>
                <input type="text" id="title" name="event_title" required>

                <label for="event_date">Date</label>
                <input type="date" id="event_date" name="event_date" required>

                <label for="location">Location</label>
                <input type="text" id="location" name="event_location" required>

                <button type="submit" name="create_event" class="create-btn">+ Create Event</button>            </form>
        </div>

        <!-- Right: Event Form -->
        <div class="event-cards">
        <h2>My Events</h2>
        <?php foreach ($events as $event) { ?>
            <div class="event-card">
                <div class="event-header">
                    <h3><?php echo htmlspecialchars($event['event_title']); ?></h3>
                    <div class="event-icons">
                        <form method="POST">
                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                            <button type="submit" name="show_edit_form" class="edit-btn"><i class="fa-solid fa-pen-to-square"></i></button>
                            
                        </form>
                        <form method="POST">
                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                            <button type="submit" name="delete_event" class="delete-btn"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                </div>
                <p><strong>📅</strong> <?php echo htmlspecialchars($event['event_date']); ?></p>
                <p><strong>📍</strong> <?php echo htmlspecialchars($event['event_location']); ?></p>

                <!-- Edit Form (Shown Only When Editing) -->
                <?php if ($edit_event_id == $event['event_id']) { ?>
                <form method="POST">
                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                    <input type="text" name="new_title" placeholder="New Title" required>
                    <input type="date" name="new_date" required>
                    <input type="text" name="new_location" placeholder="New Location" required>
                    <button type="submit" name="edit_event" class="edit-btn">Save</button>
                </form>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <footer class="footer">
        <p>© 2025 EventFlow. All rights reserved.</p>
    </footer>

</body>
</html>
