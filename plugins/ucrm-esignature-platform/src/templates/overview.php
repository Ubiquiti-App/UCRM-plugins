<?php

    // references to Ubnt, Dompdf and App namespaces
    use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;
    use Ubnt\UcrmPluginSdk\Security\PermissionNames;
    use Ubnt\UcrmPluginSdk\Data\UcrmUser;
    use Dompdf\Dompdf;
    use Dompdf\Options;

    $security = UcrmSecurity::create();
    $user = $security -> getUser();

    $message = '';

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

    // Initialize Dompdf class
    $PDF = new Dompdf();
    $PDFOptions = $PDF -> getOptions();
    $PDFOptions -> set([
        "isRemoteEnabled" => true,
        "isHtml5ParserEnabled" => true
    ]);
    $PDF -> setOptions($PDFOptions);

    // Header
    header('Content-Type: application/pdf');

    // Mobile Detect
    $detect = new Mobile_Detect;
    $PDF -> loadHtml(file_get_contents('contract-template/overview.html'));

    // Set page size and orientation
    $PDF -> setPaper("A4", "portrait");

    // Render the HTML as PDF
    $PDF -> render();

    // Output the generated PDF to phone and browser ( 1 = Download, 0 = Preview )
    // If phone is detected, the file must be downloaded.
    if($detect -> isMobile() || $detect -> isTablet()) {
        $PDF -> stream("Contract 07INTERNET - '$fullName'.pdf", [
            "Attachment" => true
        ]);
    } else {
        $PDF -> stream("Contract 07INTERNET - '$fullName'.pdf", [
            "Attachment" => false
        ]);
    }

?>