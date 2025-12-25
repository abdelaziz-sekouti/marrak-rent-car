<?php
require_once '../includes/init.php';
require_once '../src/models/User.php';

// Initialize User model
$user = new User();

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? ''
    ];
    
    // Validate input
    $errors = $user->validateLogin($data);
    
    if (empty($errors)) {
        // Attempt login
        $result = $user->login($data['email'], $data['password']);
        
        if ($result['success']) {
            // Set session variables
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['user_name'] = $result['user']['name'];
            $_SESSION['user_email'] = $result['user']['email'];
            $_SESSION['user_role'] = $result['user']['role'];
            
            // Redirect based on role
            if ($result['user']['role'] === 'admin') {
                $_SESSION['flash_message'] = 'Welcome back, ' . $result['user']['name'] . '!';
                $_SESSION['flash_type'] = 'success';
                header('Location: ../admin/');
                exit;
            } else {
                $_SESSION['flash_message'] = 'Welcome back, ' . $result['user']['name'] . '!';
                $_SESSION['flash_type'] = 'success';
                header('Location: ../views/index.php');
                exit;
            }
        } else {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'error';
        }
    } else {
        $_SESSION['flash_message'] = 'Please fix the errors below';
        $_SESSION['flash_type'] = 'error';
    }
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ../admin/');
        exit;
    } else {
        header('Location: index.php');
        exit;
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<main class="flex-grow min-h-screen flex items-center justify-center py-12 bg-gradient-to-br from-primary-50 to-secondary-50">
    <div class="max-w-md w-full mx-auto px-4">
        <div class="card animate-scale-in shadow-xl">
            <div class="card-body p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <i class="fas fa-sign-in-alt text-4xl text-primary-600 mb-4"></i>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h1>
                    <p class="text-gray-600">Sign in to your account</p>
                </div>
                
                <!-- Flash Messages -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> mb-6 animate-fade-in">
                        <?php 
                        echo htmlspecialchars($_SESSION['flash_message']);
                        unset($_SESSION['flash_message']);
                        unset($_SESSION['flash_type']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" class="space-y-6" data-validate>
                    <div class="flex flex-col">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" id="email" name="email" required
                               class="form-input p-2"
                               value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>"
                               placeholder="john@example.com"
                               autocomplete="email">
                        <?php if (isset($errors['email'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['email']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex flex-col">
                        <label for="password" class="form-label">Password *</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                   class="form-input py-2 pl-3 pr-30"
                                   placeholder="••••••••"
                                   autocomplete="current-password">
                            <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                    onclick="togglePassword('password')">
                                <i class="fas fa-eye text-gray-400" id="password-toggle"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['password']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember"
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">
                                Remember me
                            </label>
                        </div>
                        <a href="forgot-password.php" class="text-sm text-primary-600 hover:text-primary-500">
                            Forgot password?
                        </a>
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full bg-blue-500 text-white transition-all hover:bg-blue-600 hover:text-amber-200 py-3 text-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Sign In
                        </button>
                    </div>
                </form>
                
                <!-- Social Login -->
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">Or continue with</span>
                        </div>
                    </div>
                    
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <i class="fab fa-google text-red-500"></i>
                            <span class="ml-2">Google</span>
                        </button>
                        <button class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <i class="fab fa-facebook text-blue-600"></i>
                            <span class="ml-2">Facebook</span>
                        </button>
                    </div>
                </div>
                
                <!-- Register Link -->
                <div class="text-center mt-6">
                    <p class="text-gray-600">
                        Don't have an account? 
                        <a href="register.php" class="text-primary-600 hover:text-primary-500 font-medium">
                            Sign Up
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = document.getElementById(fieldId + '-toggle');
    
    if (field.type === 'password') {
        field.type = 'text';
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
    }
}

// Focus on email field when page loads
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('email').focus();
});
</script>

<?php require_once '../includes/footer.php'; ?>