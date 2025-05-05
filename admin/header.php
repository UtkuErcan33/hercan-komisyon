<?php
// Kullanıcı girişi yapılmamışsa veya admin değilse ana sayfaya yönlendir
if(!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "admin"){
    header("location: /web-sitesi/index.php");
    exit;
}
?>
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="/web-sitesi/index.php">
            <i class="fas fa-leaf"></i> Hercan Komisyon
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="/web-sitesi/admin/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Panel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/web-sitesi/orders.php">
                        <i class="fas fa-box"></i> Siparişler
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/web-sitesi/admin/daily_reports.php">
                        <i class="fas fa-chart-bar"></i> Gün Sonu Raporu
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link logout-link" href="/web-sitesi/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<style>
.navbar-custom {
    background-color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 0;
    padding: 0.5rem 1rem;
    width: 100%;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
}

.navbar-brand {
    color: #16a34a !important;
    font-weight: bold;
    font-size: 1.25rem;
    padding: 0.5rem 1rem;
}

.nav-link {
    color: #666;
    padding: 0.75rem 1rem;
    transition: color 0.3s;
    font-size: 1rem;
}

.nav-link:hover {
    color: #16a34a;
}

.nav-link.active {
    color: #16a34a;
    font-weight: bold;
}

.nav-link i {
    margin-right: 8px;
}

.logout-link {
    color: #dc3545;
    font-weight: 500;
}

.logout-link:hover {
    color: #bb2d3b;
}

.container-fluid {
    max-width: 1400px;
    margin: 0 auto;
}

/* Dashboard container için margin ayarı */
.dashboard-container {
    margin-top: 80px;
}

/* Mobil görünüm için responsive ayarlar */
@media (max-width: 991.98px) {
    .navbar-custom {
        padding: 0.5rem;
    }
    
    .navbar-nav {
        padding: 1rem 0;
    }
    
    .nav-link {
        padding: 0.5rem 1rem;
    }
}
</style>

<script>
// Aktif menü öğesini işaretle
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        if (currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
});
</script> 