<?php

    ob_start();
    error_reporting(0);

    // references to Ubnt, Dompdf and App namespaces
    use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;
    use Ubnt\UcrmPluginSdk\Security\PermissionNames;
    use Ubnt\UcrmPluginSdk\Data\UcrmUser;
    use Dompdf\Dompdf;
    use Dompdf\Options;

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    $security = UcrmSecurity::create();
    $user = $security -> getUser();

    // Client Information
    $clientId = $user -> clientId;
    $cFName = (isset($_GET['firstName']) ? $_GET['firstName'] : null);
    $cLName = (isset($_GET['lastName']) ? $_GET['lastName'] : null);
    $cFAddress = (isset($_GET['fullAddress']) ? $_GET['fullAddress'] : null);
    $cCompanyTaxID = (isset($_GET['companyTaxId']) ? $_GET['companyTaxId'] : null);
    $cCompanyRegistrationNumber = (isset($_GET['companyRegistrationNumber']) ? $_GET['companyRegistrationNumber'] : null);
    $cCity = (isset($_GET['city']) ? $_GET['city'] : null);
    $cStreet1 = (isset($_GET['street1']) ? $_GET['street1'] : null);
    $cStreet2 = (isset($_GET['street2']) ? $_GET['street2'] : null);
    $cOrganizationName = (isset($_GET['organizationName']) ? $_GET['organizationName'] : null);
    $cType = (isset($_GET['clientType']) ? $_GET['clientType'] : null);
    $cInvoiceStreet1 = (isset($_GET['invoiceStreet1']) ? $_GET['invoiceStreet1'] : null);
    $cInvoiceStreet2 = (isset($_GET['invoiceStreet2']) ? $_GET['invoiceStreet2'] : null);
    $cInvoiceCity = (isset($_GET['invoiceCity']) ? $_GET['invoiceCity'] : null);
    $cInvoiceZipCode = (isset($_GET['invoiceZipCode']) ? $_GET['invoiceZipCode'] : null);
    $cAttributes = (isset($_GET['attributes']) ? $_GET['attributes'] : [
        $cAttrName = (isset($_GET['name']) ? $_GET['name'] : null),
        $cAttrKey = (isset($_GET['key']) ? $_GET['key'] : null),
        $cAttrValue = (isset($_GET['value']) ? $_GET['value'] : null),
        $cAttrClientID = $clientId,
    ]);

    // Client Contacts Information
    $cEmail = (isset($_GET['email']) ? $_GET['email'] : null);
    $cPhone = (isset($_GET['phone']) ? $_GET['phone'] : null);
    $cName = (isset($_GET['name']) ? $_GET['name'] : null);

    // Services Information
    $sClientId = $clientId;
    $sID = (isset($_GET['id']) ? $_GET['id'] : null);
    $sName = (isset($_GET['name']) ? $_GET['name'] : null);
    $sPrice = (isset($_GET['price']) ? $_GET['price'] : null);
    $sActiveFrom = (isset($_GET['activeFrom']) ? $_GET['activeFrom'] : null);
    $sActiveTo = (isset($_GET['activeTo']) ? $_GET['activeTo'] : null);
    $sStreet1 = (isset($_GET['street1']) ? $_GET['street1'] : null);
    $sStreet2 = (isset($_GET['street2']) ? $_GET['street2'] : null);
    $sCity = (isset($_GET['city']) ? $_GET['city'] : null);
    $sZIP = (isset($_GET['zipCode']) ? $_GET['zipCode'] : null);

    // Custom Attributes Information
    $cAttributeName = (isset($_GET['name']) ? $_GET['name'] : null);
    $cAttributeID = (isset($_GET['id']) ? $_GET['id'] : null);
    $cAttributeKey = (isset($_GET['key']) ? $_GET['key'] : null);
    $cAttributeType = (isset($_GET['attributeType']) ? $_GET['attributeType'] : null);

    // API doRequest - Client Information & Custom Attributes
    $response = UCRMAPIAccess::doRequest(sprintf('clients/%d', $clientId),
        'GET',
        [
            'fullAddress' => $cFAddress,
            'firstName' => $cFName,
            'lastName' => $cLName,
            'companyTaxId' => $cCompanyTaxID,
            'companyRegistrationNumber' => $cCompanyRegistrationNumber,
            'city' => $cCity,
            'street1' => $cStreet1,
            'street2' => $cStreet2,
            'organizationName' => $cOrganizationName,
            'invoiceStreet1' => $cInvoiceStreet1,
            'invoiceStreet2' => $cInvoiceStreet2,
            'invoiceCity' => $cInvoiceCity,
            'invoiceZipCode' => $cInvoiceZipCode,
            'attributes' => [
                'name' => $cAttrName,
                'value' => $cAttrValue,
                'key' => $cAttrKey,
                'id' => $cAttributeID
            ],
        ]
    );

    // Custom Attribute Values and Names
    $caUserValue = array_values($response['attributes'])[0]['value'] ?? null;

    $caParolaValue = array_values($response['attributes'])[1]['value'] ?? null;

    $caSerieCIValue = array_values($response['attributes'])[2]['value'] ?? null;

    $caNumarCIValue = array_values($response['attributes'])[3]['value'] ?? null;

    $caCNPValue = array_values($response['attributes'])[4]['value'] ?? null;

    $caEmisDeValue = array_values($response['attributes'])[5]['value'] ?? null;

    $caDataEmiteriiValue = array_values($response['attributes'])[6]['value'] ?? null;

    $caMACValue = array_values($response['attributes'])[7]['value'] ?? null;

    $caDen1Value = array_values($response['attributes'])[8]['value'] ?? null;
    $caDen2Value = array_values($response['attributes'])[9]['value'] ?? null;
    $caDen3Value = array_values($response['attributes'])[10]['value'] ?? null;

    $caSerie1Value = array_values($response['attributes'])[11]['value'] ?? null;
    $caSerie2Value = array_values($response['attributes'])[12]['value'] ?? null;
    $caSerie3Value = array_values($response['attributes'])[13]['value'] ?? null;

    $caUM1Value = array_values($response['attributes'])[14]['value'] ?? null;
    $caUM2Value = array_values($response['attributes'])[15]['value'] ?? null;
    $caUM3Value = array_values($response['attributes'])[16]['value'] ?? null;

    $caCant1Value = array_values($response['attributes'])[17]['value'] ?? null;
    $caCant2Value = array_values($response['attributes'])[18]['value'] ?? null;
    $caCant3Value = array_values($response['attributes'])[19]['value'] ?? null;

    $caSigned = array_values($response['attributes'])[20]['value'] ?? null;

    $fullName = $response['lastName'] . ' ' . $response['firstName'];

    // API doRequest - Client Contacts Information
    $contacts = UCRMAPIAccess::doRequest(sprintf('clients/%d/contacts', $clientId)) ?: [];
    foreach($contacts as $contact) {
        $responseC = UCRMAPIAccess::doRequest(sprintf('clients/%d/contacts', $clientId),
            'GET',
            [
                'email' => $cEmail,
                'phone' => $cPhone,
                'name' => $cName,
            ]
        );
    }

    // API doRequest - Client Services Information
    $services = UCRMAPIAccess::doRequest(sprintf('clients/services?clientId=%d', $sClientId)) ?: [];
    foreach($services as $service) {
        $responseS = UCRMAPIAccess::doRequest(sprintf('clients/services/%d', $service['id']),
            'GET',
            [
                'id' => $sID,
                'name' => $sName,
                'price' => $sPrice,
                'activeFrom' => $sActiveFrom,
                'activeTo' => $sActiveTo,
                'street1' => $sStreet1,
                'street2' => $sStreet2,
                'city' => $sCity,
                'zipCode' => $sZIP,
            ]
        );

        // Formatting dates into DD-MM-YYYY
        if($responseS['activeFrom']) {
            $responseS['activeFrom'] = new \DateTimeImmutable($responseS['activeFrom']);
            $responseS['activeFrom'] = $responseS['activeFrom'] -> format('d-m-Y');
        }
        if($responseS['activeTo']) {
            $responseS['activeTo'] = new \DateTimeImmutable($responseS['activeTo']);
            $responseS['activeTo'] = $responseS['activeTo'] -> format('d-m-Y');
        }
    }

    if(isset($_POST['signOutput'])) {

        $sign_output = $_POST['signOutput'];

        // Initialize Dompdf class
        $PDF = new Dompdf();
        $PDFOptions = $PDF -> getOptions();
        $PDFOptions -> set([
            "isRemoteEnabled" => true
        ]);
        $PDF -> setOptions($PDFOptions);

        $PDF -> loadHtml(file_get_contents('contract-template/sign.html'));

        // Set page size and orientation
        $PDF -> setPaper("A4", "portrait");

        // Render the HTML as PDF
        $PDF -> render();

        // Output the PDF file
        $PDFAtt = $PDF -> output();

        // PHPMailer
        $sendMail = new PHPMailer(true);
        $sendMail -> isSMTP();

        $sendMail -> CharSet = "UTF-8";
        $sendMail -> SMTPDebug = 0;
        $sendMail -> SMTPAuth = true;
        $sendMail -> SMTPSecure = 'ssl';
        $sendMail -> Host = "smtp.gmail.com";
        $sendMail -> Port = 465;
        $sendMail -> isHTML(true);
        $sendMail -> Username = "";
        $sendMail -> Password = "";
        $sendMail -> setFrom("no-reply@07internet.ro", $fromName = "norply - 07internet.ro");
        $sendMail -> Subject = 'Contract 07INTERNET - '.$fullName.'';
        $sendMail -> Body = "Puteti citi contractul dumneavoastra cu firma de internet 07INTERNET descarcand atasamentul pus in acest mail.";
        $sendMail -> addAddress($contact['email'], $contact['name']);
        
        $filename = 'Contract 07INTERNET - '.$fullName.'.pdf';
        $encoding = 'base64';
        $type = 'application/pdf';

        $fileEncoding = base64_encode($PDFAtt);

        $contactName = $contact['name'];
        $contactPhone = $contact['phone'];
        $contactEmail = $contact['email'];

        $sendMail -> AddStringAttachment($PDFAtt, $filename, $encoding, $type);

        if($sendMail -> send()) {

            // Update Custom Attribute for signed contracts
            $customAttr = curl_init();

            curl_setopt($customAttr, CURLOPT_URL, 'https://uisp.07internet.ro/crm/api/v1.0/clients/' . $clientId);
            curl_setopt($customAttr, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($customAttr, CURLOPT_HEADER, FALSE);
    
            curl_setopt($customAttr, CURLOPT_CUSTOMREQUEST, 'PATCH');
    
            curl_setopt($customAttr, CURLOPT_POSTFIELDS, "{
                \"attributes\": [
                    {
                    \"value\": \"1\",
                    \"customAttributeId\": 39
                    }
                ]
            }");
    
            curl_setopt($customAttr, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'X-Auth-App-Key: API_KEY'
            ));
    
            $dump = curl_exec($customAttr);
            curl_close($customAttr);

            // Create ticket with the signed PDF file
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://uisp.07internet.ro/crm/api/v1.0/ticketing/tickets");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);

            curl_setopt($ch, CURLOPT_POST, TRUE);

            curl_setopt($ch, CURLOPT_POSTFIELDS, "{
            \"subject\": \"$filename\",
            \"clientId\": $clientId,
            \"emailFromAddress\": \"'$contactEmail\",
            \"emailFromName\": \"$contactName\",
            \"phoneFrom\": \"$contactPhone\",
            \"assignedGroupId\": 1,
            \"assignedUserId\": 1000,
            \"status\": 3,
            \"public\": true,
            \"assignedJobIds\": [],
            \"activity\": [
                {
                \"userId\": null,
                \"public\": true,
                \"comment\": {
                    \"body\": \"In acest tichet aveti atasat contractul dumneavoastra semnat cu 07INTERNET.\",
                    \"attachments\": [
                    {
                        \"file\": \"$fileEncoding\",
                        \"filename\": \"$filename\"
                    }
                    ],
                    \"emailFromAddress\": \"$contactEmail\",
                    \"emailFromName\": \"$contactName\",
                    \"phoneFrom\": \"$contactPhone\"
                }
                }
            ]
            }");

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "X-Auth-App-Key: API_KEY"
            ));

            $response = curl_exec($ch);
            curl_close($ch);

            var_dump($response);
            
            echo '<p style = "font-weight: normal; text-align: center;">Un mail a fost trimis catre adresa dumneavoastra de mail. Asigurati-va ca ati verificat si folderul SPAM.</p>';
            return true;
        } else {
            $error = 'Mail error: '.$mail -> ErrorInfo;
            return false;
        }
    }
?>

<?php
   if(in_array("0", $response['attributes'][20])) {
?>

<!DOCTYPE html>
<html lang = "EN">
    <head>
        <meta charset = "UTF-8">
        <meta http-equiv = "X-UA-Compatible" content = "IE=Edge">
        <meta name = "viewport" content = "width=device-width, initial-scale=1">
        <style>
            <?php include __DIR__ . '/assets/jquery.signaturepad.css' ?>
        </style>
        <script src = "https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
    </head>

    <body>
        <form method = "POST" class = "sigPad">

            <label for = "name">Introdu numele tau in casuta de mai jos:</label>
            <input text = "text" name = "name" id = "name" class = "name">            

            <p class = "typeItDesc">Previzualizeaza-ti semnatura</p>
            <p class = "drawItDesc">Deseneaza semnatura</p>

            <ul class = "sigNav">
                <li class = "typeIt"><a href = "#type-it">Scrie</a></li>
                <li class = "drawIt"><a href = "#draw-it"  class = "current">Deseneaza</a></li>
                <li class = "clearButton"><a href = "#clear">Sterge</a></li>
            </ul>
            
            <div class = "sig sigWrapper">
                <div class = "typed"></div>
                <canvas class = "pad" width = "250" height = "100"></canvas>

                <input type = "hidden" name = "signOutput" class = "signOutput"/>
            </div>
            
            <button type = "submit">
                Sunt de acord cu termenii si conditiile!
            </button>
        </form>

        <script>
            <?php include __DIR__ . '/assets/jquery.signaturepad.js' ?>
        </script>

        <script>
            var sig;

            $(document).ready(function() {
                sig = $('.sigPad').signaturePad();
            });
            $('.sigPad').submit(function(evt) {
                $('.signOutput').val(sig.getSignatureImage({drawBackground: false}));
            });
        </script>

        <script>
            <?php include __DIR__ . '/assets/json2.min.js' ?>
        </script>
    </body>
</html>
<?php
   } else {
        echo '<p style = "Font-weight: bold; text-align: center;">Ai semnat deja acest contract!</p>' . PHP_EOL;       
   }