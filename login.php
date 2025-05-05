<?php
session_start();
require_once "config.php";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Kullanıcıyı veritabanında kontrol et
    $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $email, $password);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                
                // Oturumu başlat
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $row['id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['user_type'] = $row['user_type'];
                $_SESSION['display_name'] = $row['display_name'];
                
                // Kullanıcı türüne göre yönlendirme
                if ($row['user_type'] === 'admin') {
                    header("location: admin/dashboard.php");
                } else {
                    header("location: index.php");
                }
                exit;
            } else {
                $login_err = "Geçersiz email veya şifre.";
            }
        } else {
            $login_err = "Bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
        }
        
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - Hercan Komisyon</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #a8e6cf, #dcedc1);
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .floating-fruits {
            position: fixed;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .fruit {
            position: absolute;
            opacity: 0.6;
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.6;
            }
            90% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            position: relative;
            z-index: 2;
            animation: container-appear 0.6s ease-out;
        }

        @keyframes container-appear {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-header .logo {
            font-size: 3rem;
            color: #16a34a;
            margin-bottom: 1rem;
            animation: logo-spin 1s ease-out;
        }

        @keyframes logo-spin {
            from {
                transform: rotate(-180deg) scale(0);
            }
            to {
                transform: rotate(0) scale(1);
            }
        }

        .login-header h2 {
            color: #1f2937;
            font-size: 1.8rem;
            margin: 0;
            animation: fade-in 0.6s ease-out 0.3s both;
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
            animation: fade-in 0.6s ease-out both;
        }

        .form-group:nth-child(1) { animation-delay: 0.4s; }
        .form-group:nth-child(2) { animation-delay: 0.5s; }

        .form-group label {
            display: block;
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            transition: transform 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus {
            outline: none;
            border-color: #16a34a;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.1);
            transform: translateY(-2px);
        }

        .form-group input:focus + label {
            transform: translateY(-2px);
            color: #16a34a;
        }

        .login-btn {
            width: 100%;
            background: #16a34a;
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            animation: fade-in 0.6s ease-out 0.6s both;
            position: relative;
            overflow: hidden;
        }

        .login-btn:hover {
            background: #15803d;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%) scale(0);
            border-radius: 50%;
            transition: transform 0.5s;
        }

        .login-btn:active::after {
            transform: translate(-50%, -50%) scale(2);
            opacity: 0;
        }

        .error-message {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: shake 0.5s ease-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .register-link, .back-to-home {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #4b5563;
            animation: fade-in 0.6s ease-out 0.7s both;
        }

        .register-link a, .back-to-home {
            color: #16a34a;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .register-link a:hover, .back-to-home:hover {
            color: #15803d;
            text-decoration: underline;
        }

        .back-to-home {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="floating-fruits">
        <?php
        $fruits = [
            '🍎', '🍐', '🍊', '🍋', '🍇', '🍉', '🍓', '🥝'
        ];
        for($i = 0; $i < 15; $i++) {
            $fruit = $fruits[array_rand($fruits)];
            $delay = rand(0, 10000) / 1000;
            $duration = rand(10000, 20000) / 1000;
            $size = rand(20, 40);
            $left = rand(0, 100);
            echo "<div class='fruit' style='
                left: {$left}%;
                font-size: {$size}px;
                animation: float {$duration}s infinite linear;
                animation-delay: {$delay}s;
            '>{$fruit}</div>";
        }
        ?>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-leaf"></i>
            </div>
            <h2>Hercan Komisyon</h2>
        </div>
        
        <?php 
        if (!empty($login_err)) {
            echo '<div class="error-message"><i class="fas fa-exclamation-circle"></i>' . $login_err . '</div>';
        }        
        ?>

        <form class="login-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <input type="email" name="email" id="email" required>
                <label for="email">Email</label>
            </div>    
            <div class="form-group">
                <input type="password" name="password" id="password" required>
                <label for="password">Şifre</label>
            </div>
            <button type="submit" name="login" class="login-btn">
                <span>Giriş Yap</span>
            </button>
        </form>
        
        <div class="register-link">
            Hesabınız yok mu? <a href="register.php">Kayıt Ol</a>
        </div>
        
        <a href="index.php" class="back-to-home">
            <i class="fas fa-arrow-left"></i>
            Ana Sayfaya Dön
        </a>
    </div>
</body>
</html> 