<?php

    declare(strict_types = 1);

    // references to Ubnt, Dompdf and App namespaces
    use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;
    use Ubnt\UcrmPluginSdk\Data\UcrmUser;
    use App\Service\TemplateRenderer;
    use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;

    chdir(__DIR__);

    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/main.php';

    $security = UcrmSecurity::create();
    $user = $security -> getUser();

    // Check if user is client.
    if(!$user -> isClient) {
        \App\Http::forbidden();
    }
    
    // Render form
    $optionsManager = UcrmOptionsManager::create();

    $renderer = new TemplateRenderer();

    // Set the default template and data.
    $template = "overview.php";
    $data = [    'ucrmPublicUrl' => $optionsManager -> loadOptions() -> ucrmPublicUrl    ];

    if(isset($_GET["hook"])) {
        // Handle the possible "hook" parameters...
        switch($_GET["hook"]) {
            case "overview":
                $template = "overview.php";
                break;
            
            case "sign":
                $template = "sign.php";  
                break;
                
            default:
                // Maybe die here or use the above default?!
                die("Unsupported 'hook' parameter.");
        }
    }

    $renderer -> render(__DIR__ . "/templates/$template", $data);