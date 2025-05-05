<?php
session_start();
require_once "config.php";

if (isset($_POST['register'])) {
    $display_name = trim($_POST['display_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    $error = false;
    $register_err = "";
    
    // Validasyon
    if (empty($display_name)) {
        $error = true;
        $register_err = "Lütfen görünen adınızı girin.";
    } elseif (empty($email)) {
        $error = true;
        $register_err = "Lütfen email adresinizi girin.";
    } elseif (empty($password)) {
        $error = true;
        $register_err = "Lütfen şifrenizi girin.";
    } elseif ($password !== $confirm_password) {
        $error = true;
        $register_err = "Şifreler eşleşmiyor.";
    }
    
    // Email kontrolü
    if (!$error) {
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $error = true;
                    $register_err = "Bu email adresi zaten kullanılıyor.";
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Kayıt işlemi
    if (!$error) {
        $sql = "INSERT INTO users (display_name, email, password, user_type) VALUES (?, ?, ?, 'customer')";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sss", $display_name, $email, $password);
            
            if (mysqli_stmt_execute($stmt)) {
                header("location: login.php");
                exit;
            } else {
                $register_err = "Bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Hercan Komisyon</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .register-container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header .logo {
            font-size: 2rem;
            color: #16a34a;
            margin-bottom: 1rem;
        }
        
        .register-header h2 {
            color: #1f2937;
            font-size: 1.5rem;
            margin: 0;
        }
        
        .register-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-weight: 500;
            color: #4b5563;
            font-size: 0.875rem;
        }
        
        .form-group input {
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }
        
        .register-btn {
            background: #16a34a;
            color: white;
            padding: 0.875rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .register-btn:hover {
            background: #15803d;
            transform: translateY(-1px);
        }
        
        .register-btn:active {
            transform: translateY(0);
        }
        
        .error-message {
            color: #dc2626;
            background: #fee2e2;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .error-message i {
            font-size: 1rem;
        }

        .back-to-home {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.875rem;
            margin-top: 1.5rem;
            transition: color 0.2s;
        }

        .back-to-home:hover {
            color: #16a34a;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.875rem;
            color: #4b5563;
        }
        
        .login-link a {
            color: #16a34a;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="logo">
                <i class="fas fa-leaf"></i>
            </div>
            <h2>Hercan Komisyon</h2>
        </div>
        
        <?php 
        if (!empty($register_err)) {
            echo '<div class="error-message"><i class="fas fa-exclamation-circle"></i>' . $register_err . '</div>';
        }        
        ?>

        <form class="register-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Görünen Ad</label>
                <input type="text" name="display_name" required placeholder="Adınız ve Soyadınız">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="ornek@email.com">
            </div>    
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Şifre Tekrar</label>
                <input type="password" name="confirm_password" required placeholder="••••••••">
            </div>
            <button type="submit" name="register" class="register-btn">Kayıt Ol</button>
        </form>
        
        <div class="login-link">
            Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a>
        </div>
        
        <a href="index.html" class="back-to-home">
            <i class="fas fa-arrow-left"></i>
            Ana Sayfaya Dön
        </a>
    </div>
</body>
</html> 