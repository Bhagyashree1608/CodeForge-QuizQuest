<?php
session_start();
include 'api/db.php';
$message = "";

if(isset($_POST['login'])){
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email=?");
    if(!$stmt) die("Prepare failed: (" . $conn->errno . ") " . $conn->error);

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $username, $hashed_password);
    if($stmt->fetch()){
        if(password_verify($password, $hashed_password)){
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit;
        } else {
            $message = "Incorrect password!";
        }
    } else {
        $message = "Email not registered!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Gamified Learning</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body { background: #f4f6f8; }</style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h3 class="card-title mb-4 text-center">Login</h3>
                    <?php if($message) echo "<div class='alert alert-danger'>$message</div>"; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                    </form>
                    <p class="mt-3 text-center">Don't have an account? <a href="register.php">Register</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
