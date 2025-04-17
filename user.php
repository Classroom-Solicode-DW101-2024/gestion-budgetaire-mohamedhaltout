<?php


function emailExists($email, $connection) {
    $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
    $stmt = $connection->prepare($sql);
    $stmt->execute([':email' => $email]);
    return $stmt->fetchColumn() > 0;
}

function addUser($user, $connection) {
    $nom = $user['nom'];
    $email = $user['email'];
    $password = password_hash($user['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (nom, email, password) VALUES (:nom, :email, :password)";
    $stmt = $connection->prepare($sql);

    try {
        $stmt->execute([
            ':nom' => $nom,
            ':email' => $email,
            ':password' => $password
        ]);
        return header("location:index.php");
        
    } catch (PDOException $e) {
        return "Problem In Connection " . $e->getMessage();
    }
}



function userLogin($email, $password, $connection) {
    $sql = "SELECT password FROM users WHERE email = ?";
    $stmt = $connection->prepare($sql);

    try {
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['id'] = $user['id'];


            header("location:index.php");
            exit;

        } else {
            return "Invalid email or password";
        }
    } catch (PDOException $e) {
        return "Problem In Connection: " . $e->getMessage();
    }
}


?>