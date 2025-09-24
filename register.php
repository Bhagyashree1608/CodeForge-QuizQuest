<?php
session_start();
include 'api/db.php';

$message = "";

if(isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Handle profile picture
    $profile_pic = 'default.png';
    if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0){
        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $profile_pic = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'uploads/'.$profile_pic);
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR username=?");
    if(!$stmt) die("Prepare failed: (" . $conn->errno . ") " . $conn->error);

    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0){
        $message = "Username or Email already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, profile_pic) VALUES (?, ?, ?, ?)");
        if(!$stmt) die("Prepare failed: (" . $conn->errno . ") " . $conn->error);

        $stmt->bind_param("ssss", $username, $email, $password, $profile_pic);
        if($stmt->execute()){
            $message = "Registration successful! <a href='login.php'>Login here</a>";
        } else {
            $message = "Something went wrong!";
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - Gamified Learning</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f4f6f8; }
.card { border-radius: 15px; }
</style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h3 class="card-title mb-4 text-center">Sign Up</h3>
                    <?php if($message) echo "<div class='alert alert-info'>$message</div>"; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Profile Picture</label>
                            <input type="file" name="profile_pic" class="form-control">
                        </div>
                        <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
                    </form>
                    <p class="mt-3 text-center">Already have an account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
