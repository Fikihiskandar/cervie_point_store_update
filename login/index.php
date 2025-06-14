<?php
session_start();
include "../class/class.php";

if (isset($_POST['login'])) {
    $cek = $admin->login_admin($_POST['email'], $_POST['password']);
    if ($cek == true) {
        echo "<script>window.location='../index.php';</script>";
        exit();
    } else {
        $error_message = "Login Gagal, Password / Email Salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cevrie-Point-Inventory | Login</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2D5BFF',
                        secondary: '#FF6B6B'
                    },
                    borderRadius: {
                        'none': '0px',
                        'sm': '4px',
                        DEFAULT: '8px',
                        'md': '12px',
                        'lg': '16px',
                        'xl': '20px',
                        '2xl': '24px',
                        '3xl': '32px',
                        'full': '9999px',
                        'button': '8px'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="login/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&family=Roboto+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="login-container w-full max-w-5xl rounded-lg shadow-xl overflow-hidden flex flex-col md:flex-row">
        <div class="md:w-1/2 p-8 md:p-12 flex items-center justify-center bg-gradient-to-br from-primary/90 to-primary/70 text-white">
            <div class="text-center md:text-left">
                <h1 class="font-['Pacifico'] text-4xl md:text-5xl mb-4">Inventory Barang Cevrie Point Store</h1>
                <p class="text-lg md:text-xl mb-6">Sistem Administrasi Barang</p>
                <div class="hidden md:block">
                    <p class="mb-6">Kelola inventaris dengan mudah dan efisien dengan sistem administrasi barang.</p>
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center mr-4">
                                <i class="ri-dashboard-line text-white"></i>
                            </div>
                            <span>Dashboard analitik yang komprehensif</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center mr-4">
                                <i class="ri-store-line text-white"></i>
                            </div>
                            <span>Pengelolaan stok yang efisien</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center mr-4">
                                <i class="ri-time-line text-white"></i>
                            </div>
                            <span>Pelacakan barang masuk dan keluar</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="md:w-1/2">
            <div class="login-form h-full p-8 md:p-12">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Masuk ke Akun Anda</h2>
                    <p class="text-gray-600">Masukkan kredensial Anda untuk mengakses sistem</p>
                </div>
                <?php if(isset($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ri-mail-line text-gray-400"></i>
                            </div>
                            <input type="email" id="email" name="email" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary focus:border-primary" placeholder="nama@example.com" required>
                        </div>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ri-lock-line text-gray-400"></i>
                            </div>
                            <input type="password" id="password" name="password" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary focus:border-primary" placeholder="Masukkan password" required>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" id="togglePassword">
                                <i class="ri-eye-line text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="custom-checkbox">
                            <span class="text-sm text-gray-600">Ingat saya</span>
                            <input type="checkbox" checked="checked">
                            <span class="checkmark"></span>
                        </label>
                    </div>
                    <button type="submit" name="login" class="w-full bg-primary text-white py-2 px-4 rounded-button font-medium hover:bg-primary/90 transition-colors duration-300 !rounded-button whitespace-nowrap flex items-center justify-center">
                        <i class="ri-login-box-line mr-2"></i>
                        Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                togglePassword.innerHTML = type === 'password'
                    ? '<i class="ri-eye-line text-gray-400"></i>'
                    : '<i class="ri-eye-off-line text-gray-400"></i>';
            });
        });
    </script>
</body>
</html>
