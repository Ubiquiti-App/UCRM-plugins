<?php
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

$configManager = \Ubnt\UcrmPluginSdk\Service\PluginConfigManager::create();
$config = $configManager->loadConfig();
$optionsManager = \Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager::create();
$options = $optionsManager->loadOptions();

// Get UCRM log manager.
$log = \Ubnt\UcrmPluginSdk\Service\PluginLogManager::create();

$apiError = null;
$apiSuccess = false;

$publicUrl = str_replace('.php', '/', $options->pluginPublicUrl);

try {
    $client = new Client([
        'base_uri' => "api.isplink.net",
        'headers' => [
            'Authorization' => 'Bearer ' . $config['API_KEY'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ],
        'timeout' => 5,
        'http_errors' => true 
    ]);

    // Test API connection
    $response = $client->post('/plugin/verify');
    $apiSuccess = true;
    $body = (string) $response->getBody();
    $jsonData = json_decode($body, true);
    $clientDomain = $jsonData['client_domain'];
    
} catch (GuzzleHttp\Exception\ClientException $e) {
    // Handle 4xx client errors (like 401 Unauthorized)
    $response = $e->getResponse();
    $body = (string) $response->getBody();
    $jsonData = json_decode($body, true);
    $clientDomain = false;
    
    // ISP link is hyperlink to ISP Link website
    if (isset($jsonData['error']) && $jsonData['error'] === 'Invalid API Key') {
        $apiError = "An API key and active ISPLink account is required for this plugin. Please contact <a href='https://isplink.app'>ISPLink</a> to activate your account.";
    } else {
        $apiError = 'Client error: ' . $response->getStatusCode() . ' - ' . ($jsonData['error'] ?? 'Unknown error');
    }
    $log->appendLog('API error: ' . $apiError);
    
} catch (GuzzleException $e) {
    // Handle other Guzzle exceptions (connection issues, timeouts, etc.)
    $log->appendLog('API error: ' . $e->getMessage());
}

// If API validation successful, show iframe
if ($apiSuccess && $clientDomain != false) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo htmlspecialchars($config['PAGE_TITLE'] ?? 'ISPLink Client Signup'); ?></title>
    </head>
    <body>
        <iframe 
            src="https://<?php echo htmlspecialchars($clientDomain); ?>" 
            style="width: 100%; height: 100vh; border: none;">
        </iframe>
    </body>
    </html>
    <?php
    exit;
}

// Otherwise show the combined form with address check
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($config['PAGE_TITLE'] ?? 'Sign Up for Service'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $publicUrl . 'styles.css'?>" rel="stylesheet">
</head>
<body>
    <!-- Hero Section with Logo in Top Left -->
    <div class="simple-hero">
    <div class="hero-container">
        <!-- Logo positioned in top left -->
        <div class="hero-logo mb-5">
            <?php if (!empty($config['LOGO_URL'])): ?>
                <img src="<?php echo htmlspecialchars($config['LOGO_URL']); ?>" alt="<?php echo htmlspecialchars($config['COMPANY_NAME'] ?? 'Company Logo'); ?>">
            <?php elseif (!empty($config['COMPANY_NAME'])): ?>
                <h1><?php echo htmlspecialchars($config['COMPANY_NAME']); ?></h1>
            <?php else: ?>
                <img src="https://isplink.app/img/isplink_logo.png" alt="Default Logo">
            <?php endif; ?>
        </div>
        
        <div class="hero-content">
            <h1><?php echo htmlspecialchars($config['HEADING'] ?? ''); ?></h1>
            <p><?php echo htmlspecialchars($config['SUBHEADING'] ?? ''); ?></p>
        </div>
    </div>
</div>

    <!-- Stepper Navigation -->
    <div class="stepper-container mt-2">
        <div class="simplified-stepper">
            <div class="step active">
                <div class="step-circle">1</div>
                <div class="step-label">Availability</div>
            </div>
            
            <div class="step">
                <div class="step-circle">2</div>
                <div class="step-label">Service Plan</div>
            </div>
            
            <div class="step">
                <div class="step-circle">3</div>
                <div class="step-label">Account</div>
            </div>
            
            <div class="step">
                <div class="step-circle">4</div>
                <div class="step-label">Installation</div>
            </div>
            
            <div class="step">
                <div class="step-circle">5</div>
                <div class="step-label">Review</div>
            </div>
            
            <div class="step">
                <div class="step-circle">6</div>
                <div class="step-label">Confirmation</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-8 col-lg-6">
                <!-- API Error Alert (if there was an API error) -->
                <?php if ($apiError): ?>
                    <div class="alert alert-danger mb-4">
                        <?php echo $apiError; ?>
                    </div>
                <?php endif; ?>

                <!-- Main Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Service Address Check</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Please enter your service address below to check if our high-speed internet service is available at your location.</p>
                        
                        <!-- Address Check Form -->
                        <form method="POST" data-controller="accounts--status-address-search">
                            <div class="mb-3">
                                <label for="street_address" class="form-label">Service Address</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="street_address"
                                       name="street_address" 
                                       placeholder="Enter your complete address"  
                                       autocomplete="off"
                                       data-target="accounts--status-address-search.addressInput"
                                       required>
                                <input type="hidden" 
                                       name="address_id" 
                                       data-target="accounts--status-address-search.addressIdInput">
                                       
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="text-left loading-matches invisible" 
                                          data-target="accounts--status-address-search.loadingMessage">
                                        <small>Loading...</small>
                                    </span>
                                    <span class="text-right unknown-address invisible" 
                                          data-target="accounts--status-address-search.notInSystemMessage">
                                        <small>Address not found in system</small>
                                    </span>
                                </div>
                            </div>
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    Check Availability
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="text-center mt-4">
                    <p class="text-muted">
                        Need help? <a href="https://isplink.app/" class="text-decoration-none">Contact our support team</a>
                    </p>
                    <p>
                        <a href="<?php echo htmlspecialchars($config['HOMEPAGE_URL'] ?? '#'); ?>" class="text-decoration-none">Back to main site</a>
                    </p>
                </div>

            </div>
        </div>

        <footer>
                <!-- Powered by ISPLink footer -->
                <div class="text-center mt-5 mb-3">
                    <p class="small text-muted">
                        Powered by <a href="https://isplink.app/" class="text-decoration-none">ISPLink</a>
                    </p>
                </div>
        </footer>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>