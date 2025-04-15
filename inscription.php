<?php
$connection = require 'config.php';
require 'user.php';
session_start();
$error = '';
$success = '';

if (isset($_SESSION['id'])) {
    header("location: index.php");
    exit;
}

if (isset($_POST['submit'])) {
    $user = [
        'nom' => $_POST['nom'],
        'email' => $_POST['email'],
        'password' => $_POST['password']
    ];

    if (empty($user['nom']) || empty($user['email']) || empty($user['password'])) {
        $error = "All the Field In Important";
    } elseif (emailExists($user['email'], $connection)) {
        $error = "This Email Is Existe Before";
    } else {
        $success = addUser($user, $connection);
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f7f9fc;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 30px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
            margin-top: 8px;
        }
        
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-danger {
            background-color: #ffeaef;
            color: #d63031;
            border: 1px solid #ffccd5;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            border-color: #4c84ff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 132, 255, 0.1);
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #4c84ff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn:hover {
            background-color: #3b6fe6;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }
        
        .footer a {
            color: #4c84ff;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Create Account</h1>
            <p>Please fill in the details to register</p>
        </div>
        
        <?php if (isset($_POST['submit']) && $error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>


<?php if (isset($_POST['submit']) && $success): ?>
    <p style="color: green;"><?php echo $success; ?></p>
<?php endif; ?>


        <form method="POST">
            <div class="form-group">
                <label for="nom">Full Name</label>
                <input type="text" name="nom" id="nom" class="form-control">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" >
            </div>

            <button type="submit" name="submit" class="btn">Register</button>
        </form>
        
        <div class="footer">
        Déjà un compte ? <a href="login.php">Log in</a>
        </div>
    </div>
</body>
</html>
