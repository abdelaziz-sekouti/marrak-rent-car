<?php
require_once '../includes/init.php';

// Process contact form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    if (!rateLimitCheck('contact_form', 3, 300)) {
        $_SESSION['flash_message'] = 'Too many contact attempts. Please try again later.';
        $_SESSION['flash_type'] = 'error';
        header('Location: contact.php');
        exit;
    }
    
    // CSRF validation
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Security validation failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header('Location: contact.php');
        exit;
    }
    
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'subject' => trim($_POST['subject'] ?? ''),
        'message' => trim($_POST['message'] ?? ''),
        'preferred_contact' => $_POST['preferred_contact'] ?? 'email'
    ];
    
    // Validate form data
    $errors = validateContactForm($data);
    
    if (empty($errors)) {
        // Save contact request to database (optional)
        $contactSaved = true; // Temporarily disabled until we create the table
        
        // Send email notification
        $emailSent = sendContactEmail($data);
        
        if ($emailSent || $contactSaved) {
            $_SESSION['flash_message'] = 'Thank you for contacting us! We will get back to you within 24 hours.';
            $_SESSION['flash_type'] = 'success';
            
            // Log contact submission
            logSecurityEvent('contact_form_submission', [
                'name' => $data['name'],
                'email' => $data['email']
            ]);
            
            header('Location: contact.php?sent=1');
            exit;
        } else {
            $_SESSION['flash_message'] = 'Sorry, there was an error sending your message. Please try again.';
            $_SESSION['flash_type'] = 'error';
        }
    } else {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $data;
        $_SESSION['flash_message'] = 'Please fix the errors below.';
        $_SESSION['flash_type'] = 'error';
    }
    
    header('Location: contact.php');
    exit;
}

function validateContactForm($data) {
    $errors = [];
    
    // Name validation
    if (empty($data['name'])) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($data['name']) < 2) {
        $errors['name'] = 'Name must be at least 2 characters';
    } elseif (strlen($data['name']) > 100) {
        $errors['name'] = 'Name must be less than 100 characters';
    }
    
    // Email validation
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!isValidEmail($data['email'])) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    // Phone validation (optional but must be valid if provided)
    if (!empty($data['phone']) && !isValidPhone($data['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number';
    }
    
    // Subject validation
    if (empty($data['subject'])) {
        $errors['subject'] = 'Subject is required';
    } elseif (strlen($data['subject']) < 5) {
        $errors['subject'] = 'Subject must be at least 5 characters';
    } elseif (strlen($data['subject']) > 200) {
        $errors['subject'] = 'Subject must be less than 200 characters';
    }
    
    // Message validation
    if (empty($data['message'])) {
        $errors['message'] = 'Message is required';
    } elseif (strlen($data['message']) < 10) {
        $errors['message'] = 'Message must be at least 10 characters';
    } elseif (strlen($data['message']) > 2000) {
        $errors['message'] = 'Message must be less than 2000 characters';
    }
    
    // Preferred contact validation
    $validContacts = ['email', 'phone', 'both'];
    if (!in_array($data['preferred_contact'], $validContacts)) {
        $errors['preferred_contact'] = 'Invalid preferred contact method';
    }
    
    return $errors;
}



function sendContactEmail($data) {
    $to = 'contact@marrakrentcar.com';
    $subject = 'New Contact Form Submission: ' . $data['subject'];
    
    $message = "
    <html>
    <head>
        <title>New Contact Form Submission</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #3b82f6; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9fafb; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #1f2937; }
            .footer { background: #1f2937; color: white; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2 class='text-blue-400'>New Contact Form Submission</h2>
            </div>
            <div class='content'>
                <div class='field'>
                    <span class='label'>Name:</span> " . htmlspecialchars($data['name']) . "
                </div>
                <div class='field'>
                    <span class='label'>Email:</span> " . htmlspecialchars($data['email']) . "
                </div>";
    
    if (!empty($data['phone'])) {
        $message .= "
                <div class='field'>
                    <span class='label'>Phone:</span> " . htmlspecialchars($data['phone']) . "
                </div>";
    }
    
    $message .= "
                <div class='field'>
                    <span class='label'>Subject:</span> " . htmlspecialchars($data['subject']) . "
                </div>
                <div class='field'>
                    <span class='label'>Preferred Contact:</span> " . ucfirst(htmlspecialchars($data['preferred_contact'])) . "
                </div>
                <div class='field'>
                    <span class='label'>Message:</span><br>
                    " . nl2br(htmlspecialchars($data['message'])) . "
                </div>
                <div class='field'>
                    <span class='label'>Date:</span> " . date('Y-m-d H:i:s') . "
                </div>
            </div>
            <div class='footer'>
                <p>This message was sent from the Marrak Rent Car contact form.</p>
            </div>
        </div>
    </body>
    </html>";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $data['name'] . ' <' . $data['email'] . '>',
        'Reply-To: ' . $data['email'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}
?>

<?php 
require_once '../includes/init.php';
require_once '../includes/header.php'; 
?>

<main class="flex-grow">
    <!-- Hero Section -->
    <section class="bg-linear-to-bl from-purple-500 to-pink-500 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-blue-400 text-4xl md:text-5xl font-bold mb-4 animate-fade-in">Contact Us</h1>
                <p class="text-blue-300 text-xl mb-8 max-w-2xl mx-auto animate-slide-up">
                    Have questions? We'd love to hear from you. Get in touch and we'll respond as soon as possible.
                </p>
            </div>
        </div>
    </section>
    
    <!-- Contact Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 p-2">
                <!-- Contact Form -->
                <div class="scroll-animate">
                    <div class="card shadow-lg p-2">
                        <div class="card-header">
                            <h2 class="text-2xl font-bold text-gray-900 text-center">Send us a Message</h2>
                            <p class="text-gray-600 mt-2">Fill out the form below and we'll get back to you</p>
                        </div>
                        <div class="card-body">
                            <?php if (isset($_GET['sent']) && $_GET['sent'] == 1): ?>
                                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md mb-6 animate-fade-in">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    Thank you for your message! We'll respond within 24 hours.
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_SESSION['flash_message'])): ?>
                                <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> mb-6 animate-fade-in">
                                    <?php 
                                    echo htmlspecialchars($_SESSION['flash_message']);
                                    unset($_SESSION['flash_message']);
                                    unset($_SESSION['flash_type']);
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="space-y-6" data-validate>
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" id="name" name="name" required
                                               class="form-input"
                                               value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>"
                                               placeholder="John Doe">
                                        <?php if (isset($form_errors['name'])): ?>
                                            <p class="text-red-500 text-sm mt-1"><?php echo $form_errors['name']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div>
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" id="email" name="email" required
                                               class="form-input"
                                               value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                                               placeholder="john@example.com">
                                        <?php if (isset($form_errors['email'])): ?>
                                            <p class="text-red-500 text-sm mt-1"><?php echo $form_errors['email']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" id="phone" name="phone"
                                           class="form-input"
                                           value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>"
                                           placeholder="+1 (555) 123-4567">
                                    <?php if (isset($form_errors['phone'])): ?>
                                        <p class="text-red-500 text-sm mt-1"><?php echo $form_errors['phone']; ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <label for="subject" class="form-label">Subject *</label>
                                    <input type="text" id="subject" name="subject" required
                                           class="form-input"
                                           value="<?php echo htmlspecialchars($form_data['subject'] ?? ''); ?>"
                                           placeholder="How can we help you?">
                                    <?php if (isset($form_errors['subject'])): ?>
                                        <p class="text-red-500 text-sm mt-1"><?php echo $form_errors['subject']; ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <label for="message" class="form-label">Message *</label>
                                    <textarea id="message" name="message" rows="5" required
                                              class="form-input"
                                              placeholder="Tell us more about how we can help you..."><?php echo htmlspecialchars($form_data['message'] ?? ''); ?></textarea>
                                    <?php if (isset($form_errors['message'])): ?>
                                        <p class="text-red-500 text-sm mt-1"><?php echo $form_errors['message']; ?></p>
                                    <?php endif; ?>
                                    <p class="text-gray-500 text-sm mt-1">
                                        <span id="char-count">0</span>/2000 characters
                                    </p>
                                </div>
                                
                                <div>
                                    <label class="form-label">Preferred Contact Method</label>
                                    <div class="flex space-x-4">
                                        <label class="flex items-center">
                                            <input type="radio" name="preferred_contact" value="email" 
                                                   class="mr-2" <?php echo (isset($form_data['preferred_contact']) && $form_data['preferred_contact'] == 'email') ? 'checked' : 'checked'; ?>>
                                            <span>Email</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="preferred_contact" value="phone" 
                                                   class="mr-2" <?php echo (isset($form_data['preferred_contact']) && $form_data['preferred_contact'] == 'phone') ? 'checked' : ''; ?>>
                                            <span>Phone</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="preferred_contact" value="both" 
                                                   class="mr-2" <?php echo (isset($form_data['preferred_contact']) && $form_data['preferred_contact'] == 'both') ? 'checked' : ''; ?>>
                                            <span>Both</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" id="privacy" name="privacy" required
                                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                    <label for="privacy" class="ml-2 block text-sm text-gray-700">
                                        I agree to the <a href="#" class="text-primary-600 hover:text-primary-500">Privacy Policy</a>
                                        and consent to being contacted
                                    </label>
                                </div>
                                
                                <button type="submit" class="w-full btn btn-primary py-3 text-lg">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    Send Message
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="space-y-8">
                    <!-- Contact Details -->
                    <div class="scroll-animate" style="animation-delay: 0.2s;">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">Get in Touch</h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-start group hover-lift">
                                <div class="bg-primary-100 rounded-full p-3 mr-4 group-hover:bg-primary-200 transition-colors">
                                    <i class="fas fa-map-marker-alt text-primary-600 text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Address</h4>
                                    <p class="text-gray-600">123 Rental Street, City, State 12345</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start group hover-lift">
                                <div class="bg-primary-100 rounded-full p-3 mr-4 group-hover:bg-primary-200 transition-colors">
                                    <i class="fas fa-phone text-primary-600 text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Phone</h4>
                                    <p class="text-gray-600">+1 (555) 123-4567</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start group hover-lift">
                                <div class="bg-primary-100 rounded-full p-3 mr-4 group-hover:bg-primary-200 transition-colors">
                                    <i class="fas fa-envelope text-primary-600 text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Email</h4>
                                    <p class="text-gray-600">contact@marrakrentcar.com</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start group hover-lift">
                                <div class="bg-primary-100 rounded-full p-3 mr-4 group-hover:bg-primary-200 transition-colors">
                                    <i class="fas fa-clock text-primary-600 text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Business Hours</h4>
                                    <p class="text-gray-600">Mon-Fri: 8AM-8PM</p>
                                    <p class="text-gray-600">Sat-Sun: 9AM-6PM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="scroll-animate" style="animation-delay: 0.4s;">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Follow Us</h3>
                        <div class="flex space-x-4">
                            <a href="#" class="bg-blue-600 text-white rounded-full p-3 hover:bg-blue-700 transition-colors hover-lift">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="bg-blue-400 text-white rounded-full p-3 hover:bg-blue-500 transition-colors hover-lift">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="bg-pink-600 text-white rounded-full p-3 hover:bg-pink-700 transition-colors hover-lift">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="bg-blue-700 text-white rounded-full p-3 hover:bg-blue-800 transition-colors hover-lift">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" class="bg-red-600 text-white rounded-full p-3 hover:bg-red-700 transition-colors hover-lift">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- FAQ Link -->
                    <div class="scroll-animate" style="animation-delay: 0.6s;">
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Have Questions?</h3>
                            <p class="text-gray-600 mb-4">
                                Check out our FAQ section for quick answers to common questions.
                            </p>
                            <a href="#" class="btn btn-outline">
                                <i class="fas fa-question-circle mr-2"></i>
                                View FAQ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Google Maps Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Find Us</h2>
                <p class="text-gray-600">Visit our location or use the map below for directions</p>
            </div>
            
            <div class="card shadow-xl overflow-hidden scroll-animate">
                <div id="map" style="height: 500px; width: 100%;"></div>
            </div>
            
            <!-- Map Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                <div class="text-center scroll-animate" style="animation-delay: 0.1s;">
                    <i class="fas fa-directions text-3xl text-primary-600 mb-2"></i>
                    <h4 class="font-semibold text-gray-900">Easy Access</h4>
                    <p class="text-gray-600 text-sm">Conveniently located near major highways</p>
                </div>
                <div class="text-center scroll-animate" style="animation-delay: 0.2s;">
                    <i class="fas fa-parking text-3xl text-primary-600 mb-2"></i>
                    <h4 class="font-semibold text-gray-900">Free Parking</h4>
                    <p class="text-gray-600 text-sm">Ample parking available on-site</p>
                </div>
                <div class="text-center scroll-animate" style="animation-delay: 0.3s;">
                    <i class="fas fa-shuttle-van text-3xl text-primary-600 mb-2"></i>
                    <h4 class="font-semibold text-gray-900">Airport Shuttle</h4>
                    <p class="text-gray-600 text-sm">Free shuttle to and from airport</p>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__.'/../includes/footer.php'; ?>

<script>
// Character counter for message field
document.getElementById('message')?.addEventListener('input', function(e) {
    const charCount = e.target.value.length;
    const counter = document.getElementById('char-count');
    counter.textContent = charCount;
    
    if (charCount > 2000) {
        counter.classList.add('text-red-500');
    } else if (charCount > 1800) {
        counter.classList.add('text-yellow-500');
    } else {
        counter.classList.remove('text-red-500', 'text-yellow-500');
    }
});

// Initialize Google Maps with fallback
function initMap() {
    try {
        // Marrak Rent Car location - Derb Bouaalam, N185, Marrakech, Morocco
        const marrakLocation = { lat: 31.6295, lng: -7.9811 };
        
        const map = new google.maps.Map(document.getElementById('map'), {
            zoom: 15,
            center: marrakLocation,
            styles: [
                {
                    featureType: 'water',
                    elementType: 'geometry',
                    stylers: [{ color: '#e9e9e9' }, { lightness: 17 }]
                },
                {
                    featureType: 'landscape',
                    elementType: 'geometry',
                    stylers: [{ color: '#f5f5f5' }, { lightness: 20 }]
                }
            ]
        });
        
        // Custom marker
        const markerContent = `
            <div style="
                background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 20px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            ">
                <i class="fas fa-car"></i>
            </div>
        `;
        
        const marker = new google.maps.Marker({
            position: marrakLocation,
            map: map,
            title: 'Marrak Rent Car',
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(markerContent),
                scaledSize: new google.maps.Size(40, 40)
            }
        });
        
        // Info window
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 10px; max-width: 250px;">
                    <h3 style="margin: 0 0 10px 0; color: #1f2937; font-size: 18px;">Marrak Rent Car</h3>
                    <p style="margin: 5px 0; color: #6b7280;">Derb Bouaalam, N185<br>Marrakech, Morocco</p>
                    <p style="margin: 5px 0; color: #6b7280;">Phone: +212 5XX-XXX-XXX</p>
                    <p style="margin: 5px 0; color: #6b7280;">Hours: Mon-Sat 8AM-8PM</p>
                    <a href="https://maps.google.com/?q=Derb+Bouaalam+N185+Marrakech+Morocco" 
                       target="_blank" 
                       style="color: #3b82f6; text-decoration: none;">Get Directions →</a>
                </div>
            `
        });
        
        marker.addListener('click', () => {
            infoWindow.open(map, marker);
        });
    } catch (error) {
        console.error('Google Maps initialization failed:', error);
        showMapFallback();
    }
}

// Show fallback map if Google Maps fails
function showMapFallback() {
    const mapElement = document.getElementById('map');
    if (mapElement) {
        mapElement.innerHTML = `
            <div class="bg-gradient-to-br from-blue-50 to-indigo-100 border-2 border-blue-200 rounded-lg p-8 text-center h-full flex flex-col justify-center">
                <i class="fas fa-map-marked-alt text-6xl text-blue-600 mb-4"></i>
                <h3 class="text-xl font-bold text-blue-800 mb-2">Marrak Rent Car Location</h3>
                <p class="text-blue-700 mb-2">
                    <i class="fas fa-map-marker-alt mr-2"></i>Derb Bouaalam, N185
                </p>
                <p class="text-blue-700 mb-4">Marrakech, Morocco</p>
                <p class="text-blue-600 text-sm mb-4">
                    <i class="fas fa-phone mr-2"></i>+212 5XX-XXX-XXX
                </p>
                <div class="space-x-3">
                    <a href="https://maps.google.com/?q=Derb+Bouaalam+N185+Marrakech+Morocco" 
                       target="_blank" 
                       class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors shadow-lg">
                        <i class="fas fa-directions mr-2"></i>Get Directions
                    </a>
                    <a href="tel:+2125XXXXXXXX" 
                       class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors shadow-lg">
                        <i class="fas fa-phone mr-2"></i>Call Now
                    </a>
                </div>
                <p class="text-blue-500 text-xs mt-4">
                    <i class="fas fa-info-circle mr-1"></i>Click to open in Google Maps
                </p>
            </div>
        `;
    }
}

// Load Google Maps API with better error handling
function loadGoogleMaps() {
    // Check if we have a valid API key
    const apiKey = '<?php echo defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : ''; ?>';
    
    if (!apiKey || apiKey === 'AIzaSyBF1nE8q7hKz7jX9e3mRf8Lq2vC6W3nG9Y') {
        // Use OpenStreetMap as fallback if no valid API key
        console.log('No valid Google Maps API key found, using OpenStreetMap');
        loadOpenStreetMap();
        return;
    }
    
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&callback=initMap&v=weekly`;
    script.async = true;
    script.defer = true;
    
    script.onload = function() {
        console.log('Google Maps loaded successfully');
    };
    
    script.onerror = function() {
        console.error('Failed to load Google Maps API, trying OpenStreetMap');
        loadOpenStreetMap();
    };
    
    // Set timeout for map loading
    setTimeout(() => {
        if (typeof google === 'undefined') {
            console.warn('Google Maps loading timeout, trying OpenStreetMap');
            loadOpenStreetMap();
        }
    }, 10000); // 10 seconds timeout
    
    document.head.appendChild(script);
}

// Load OpenStreetMap as fallback (no API key required)
function loadOpenStreetMap() {
    // Load Leaflet CSS and JS
    const leafletCSS = document.createElement('link');
    leafletCSS.rel = 'stylesheet';
    leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(leafletCSS);
    
    const leafletJS = document.createElement('script');
    leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    leafletJS.onload = function() {
        initOpenStreetMap();
    };
    document.head.appendChild(leafletJS);
}

// Initialize OpenStreetMap with Leaflet
function initOpenStreetMap() {
    try {
        const mapElement = document.getElementById('map');
        if (!mapElement) return;
        
        // Marrak Rent Car location - Derb Bouaalam, N185, Marrakech, Morocco
        const marrakLocation = [31.6295, -7.9811];
        
        // Create map
        const map = L.map('map').setView(marrakLocation, 15);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Create custom icon
        const carIcon = L.divIcon({
            html: `
                <div style="
                    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 20px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                    border: 3px solid white;
                ">
                    <i class="fas fa-car"></i>
                </div>
            `,
            className: 'custom-marker',
            iconSize: [40, 40],
            iconAnchor: [20, 20],
            popupAnchor: [0, -20]
        });
        
        // Add marker
        const marker = L.marker(marrakLocation, { icon: carIcon }).addTo(map);
        
        // Add popup
        marker.bindPopup(`
            <div style="padding: 10px; max-width: 250px;">
                <h3 style="margin: 0 0 10px 0; color: #1f2937; font-size: 18px;">Marrak Rent Car</h3>
                <p style="margin: 5px 0; color: #6b7280;">Derb Bouaalam, N185<br>Marrakech, Morocco</p>
                <p style="margin: 5px 0; color: #6b7280;">Phone: +212 5XX-XXX-XXX</p>
                <p style="margin: 5px 0; color: #6b7280;">Hours: Mon-Sat 8AM-8PM</p>
                <a href="https://maps.google.com/?q=Derb+Bouaalam+N185+Marrakech+Morocco" 
                   target="_blank" 
                   style="color: #3b82f6; text-decoration: none; display: inline-block; margin-top: 10px;">Get Directions →</a>
            </div>
        `).openPopup();
        
        console.log('OpenStreetMap loaded successfully');
    } catch (error) {
        console.error('OpenStreetMap initialization failed:', error);
        showMapFallback();
    }
}

// Initialize maps when page loads
window.addEventListener('load', loadGoogleMaps);
</script>