<?php
require_once '../includes/init.php';
require_once '../src/models/User.php';

// Initialize User model
$user = new User();

// Process registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'phone' => trim($_POST['phone'] ?? '')
    ];
    
    // Validate input
    $errors = $user->validateRegistration($data);
    
    if (empty($errors)) {
        // Attempt registration
        $result = $user->register($data['name'], $data['email'], $data['password'], $data['phone']);
        
        if ($result['success']) {
            $_SESSION['flash_message'] = 'Registration successful! Please log in.';
            $_SESSION['flash_type'] = 'success';
            header('Location: login.php');
            exit;
        } else {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'error';
        }
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<main class="flex-grow min-h-screen flex items-center justify-center py-12">
    <div class="max-w-md w-full mx-auto px-4">
        <div class="card animate-scale-in shadow-xl">
            <div class="card-body p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <i class="fas fa-user-plus text-4xl text-blue-600 mb-4"></i>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Create Account</h1>
                    <p class="text-gray-600">Join Marrak Rent Car today</p>
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
                
                <!-- Registration Form -->
                <form method="POST" class="space-y-6" data-validate>
                    <div class="flex flex-col">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" id="name" name="name" required
                               class="form-input p-2"
                               value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>"
                               placeholder="John Doe">
                        <?php if (isset($errors['name'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['name']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex flex-col">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" id="email" name="email" required
                               class="form-input p-2"
                               value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>"
                               placeholder="john@example.com">
                        <?php if (isset($errors['email'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['email']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex flex-col">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                               class="form-input p-2"
                               value="<?php echo htmlspecialchars($data['phone'] ?? ''); ?>"
                               placeholder="+1 (555) 123-4567">
                        <?php if (isset($errors['phone'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['phone']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="password" class="form-label">Password *</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                   class="form-input py-2 pl-2 pr-30"
                                   placeholder="••••••••">
                            <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                    onclick="togglePassword('password')">
                                <i class="fas fa-eye text-gray-400" id="password-toggle"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['password']; ?></p>
                        <?php endif; ?>
                        <p class="text-gray-500 text-xs mt-1">Minimum 8 characters</p>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="form-input  py-2 pl-2 pr-30"
                                   placeholder="••••••••">
                            <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                    onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye text-gray-400" id="confirm_password-toggle"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['confirm_password']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="terms" name="terms" required
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="terms" class="ml-2 block text-sm text-gray-700">
                            I agree to the <a href="#" class="text-blue-600 hover:text-blue-500">Terms and Conditions</a>
                            and <a href="#" class="text-blue-600 hover:text-blue-500">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full text-white bg-blue-500 rounded-lg py-3 text-lg hover:bg-blue-600 hover:text-amber-300 transition-all">
                            <i class="fas fa-user-plus mr-2"></i>
                            Create Account
                        </button>
                    </div>
                </form>
                
                <!-- Login Link -->
                <div class="text-center mt-6">
                    <p class="text-gray-600">
                        Already have an account? 
                        <a href="login.php" class="text-blue-600 hover:text-blue-500 font-medium">
                            Sign In
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

// Password strength indicator
document.getElementById('password')?.addEventListener('input', function(e) {
    const password = e.target.value;
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    // Update strength indicator (implementation would go here)
});
</script>

<?php require_once '../includes/footer.php'; ?>