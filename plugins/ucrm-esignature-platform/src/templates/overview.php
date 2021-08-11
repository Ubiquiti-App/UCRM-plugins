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

    $HTML = '
    <head>
        <meta http-equiv = "Content-Type" content = "text/html; charset=utf-8"/>
        <style>
            @page {
                margin: 0cm 0cm;
            }
            
            body {
                margin-top: 1cm;
                margin-left: 1cm;
                margin-right: 1cm;
                margin-bottom: 2cm;
                position: relative;
                overflow-x: hidden;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }

            footer {
                position: fixed;
                bottom: 0cm;
                left: 0cm;
                right: 0cm;
                height: 2.5cm;
                clear: both;

                /** Extra personal styles **/
                line-height: 1.5cm;
            }

            .wrapper {
                font-size: 13px;
                line-height: 1.4;
                color: #3C444D;
                margin: 0 -20px;
            }
            
            .header__phone {
                text-align: right;
                padding-left: 225px;
            }
            
            .header__heading {
                text-align: left;
            }
            
            h1 {
                display: block;
                margin: 0;
                font-weight: bold;
                font-size: 18px;
                text-align: center;
            }
            
            h2 {
                display: block;
                font-size: 16px;
                text-align: center;
            }
            
            h3 {
                display: block;
                font-size: 14px;
                margin-bottom: 0;
            }
            
            p {
                margin: 0;
            }
            
            table {
                width: 100%;
            }  
            
            .paragraph th {
                color: #FFF;
                background: #7D7B7B;
                text-align: left;
                padding: 0 8px;
            }
            
            .paragraph th:first-child {
                width: 20px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class = "wrapper">
            <table>
                <tbody>
                <tr>
                    <td class = "header__heading" style = "text-align: center;">
                        <h1>URBAN NETWORK SOLUTIONS S.R.L</h1>
                        <a href = "https://07internet.ro">07INTERNET.RO</a>
                        <br>
                        17 ani de experienta în telecomunicatii...
                    </td>
                    <td class = "header__phone">
                        CALLCENTER: 0241 700 000
                        <br>
                        NON-STOP: 07INTERNET
                    </td>
                </tr>
                </tbody>
            </table>
            <h1>
                Contract de prestari servicii internet nr.:&nbsp;<strong>'.$clientId.'&nbsp;</strong>denumit si ANEXA A.
            </h1>
            <table class = "paragraph">
                <tbody>
                <tr>
                    <th>
                        1
                    </th>
                    <th>
                        Informatii despre prestator
                    </th>
                </tr>

                <tr>
                    <td colspan = "2">
                        <p>
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>

            <table class = "paragraph">
                <tbody>
                <tr>
                    <th>
                        2
                    </th>
                    <th>
                        Informatii despre client
                    </th>
                </tr>

                <tr>
                    <td colspan = "2">
                        <p>
                            <strong>Nume:&nbsp;</strong>'.$fullName.', <br>
                            <strong>domiciliat / cu sediul in:&nbsp;</strong>'.$response['fullAddress'].', <br>
                            <strong>email:&nbsp;</strong>'.$contact['email'].', <br>
                            <strong>persoana de contact:&nbsp;</strong>'.$contact['name'].', <br>
                            <strong>numar de telefon:&nbsp;</strong>'.$contact['phone'].'
                        </p>
                    </td>
                </tr>

                <tr>
                    <td colspan = "2">
                        <p>
                            <strong>
                                <u>
                                    Documente necesare la incheierea contractului:
                                </u>
                            </strong>
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>

            <table>
                <tbody>
                <tr>
                    <td>
                        <p>
                            C.I. / Pasaport
                            <br>
                            Certificat de inregistrare
                        </p>
                    </td>

                    <td>
                        <p>
                            <strong>Serie C.I.:&nbsp;</strong>'.$caSerieCIValue.'
                            <strong>Numar C.I.:&nbsp;</strong>'.$caNumarCIValue.' <br>
                            <strong>Cod Numeric Personal:&nbsp;</strong>'.$caCNPValue.' <br>
                            <strong>Cod fiscal:&nbsp;</strong>'.$response['companyTaxId'].' <br>
                            <strong>Nr. reg. comert:&nbsp;</strong>'.$response['companyRegistrationNumber'].'
                        </p>
                    </td>

                    <td>
                        <p>
                            <strong>Emis de:&nbsp;</strong>'.$caEmisDeValue.'
                            <strong>la data de:&nbsp;</strong>'.$caDataEmiteriiValue.'
                        </p>
                    </td>

                </tr>
                </tbody>
            </table>

            <table>
                <tbody>
                <tr>
                    <td>
                        <p>
                            Adresa de instalare:
                        </p>
                    </td>
                    <td>
                        <p>
                            <strong>Oras:&nbsp;</strong>'.$response['invoiceCity'].' <br>
                            <strong>sector / judet:&nbsp;</strong>Constanta <br>
                            <strong>Strada:&nbsp;</strong>'.$response['invoiceStreet1'] . ' '. $response['invoiceStreet2'].'
                        </p>
                    </td>

                    <td>
                        <p>
                            <strong>Telefon:&nbsp;</strong>'.$contact['phone'].' <br>
                            <strong>Username:&nbsp;</strong>'.$caUserValue.' <br>
                            <strong>Parola:&nbsp;</strong>'.$caParolaValue.'
                        </p>
                    </td>

                </tr>
                </tbody>
            </table>

            <table class = "paragraph">
                <tbody>

                <tr>
                    <th>
                        3
                    </th>
                    <th>
                        Serviciul contractat
                    </th>
                </tr>

                <tr>
                    <td colspan = "2">
                        <?php foreach($services as $service) { ?>
                            <ul>
                                <li>
                                    <p>
                                        <strong>Denumire:&nbsp;</strong>'.$responseS['name'].'
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <strong>Pret:&nbsp;</strong>'.$responseS['price'].' RON
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <strong>Activat la:&nbsp;</strong>'.$responseS['activeFrom'].'
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <strong>Activ pana la:&nbsp;</strong>'.$responseS['activeTo'].'
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <strong>Adresa:&nbsp;</strong>'.$responseS['street1'].',
                                        <strong>nr.:&nbsp;</strong>'.$responseS['street2'].',
                                        <strong>oras:&nbsp;</strong>'.$responseS['city'].', '.$responseS['zipCode'].'
                                    </p>
                                </li>
                            </ul>
                        <?php } ?>
                    </td>
                </tr>

                </tbody>
            </table>

            <table class = "paragraph">
                <tbody>
                <tr>
                    <th>
                        4
                    </th>
                    <th>
                        Obiectul contractului
                    </th>
                </tr>
                </tbody>
            </table>

            <table class = "paragraph">
                <tbody>
                <tr>
                    <th>
                        5
                    </th>
                    <th>
                        Perioada contractuala
                    </th>
                </tr>
                </tbody>
            </table>

            <table class = "paragraph">
                <tbody>
                <tr>
                    <th>
                        6
                    </th>

                    <th>
                        Din prezentul contract fac parte:
                    </th>
                </tr>
                </tbody>
            </table>

            <table>
                <tbody>
                <tr>
                    <td colspan = "2">
                        <p>
                            <strong>X ANEXA A&nbsp;</strong>– ABONAMENTE, PROMOTII si DISCOUNTURI
                        </p>
                    </td>
                </tr>

                <tr>
                    <td colspan = "2">
                        <p>
                            <strong>X ANEXA A.1&nbsp;</strong>– CONDITII GENERALE
                        </p>
                    </td>
                </tr>

                <tr>
                    <td colspan = "2">
                        <p>
                            <strong>X ANEXA B.1, B.2&nbsp;</strong>– CONDITII TEHNICE SI COMERCIALE SPECIFICE SERVICIILOR DE INTERNET / TELEVIZIUNE
                        </p>
                    </td>
                </tr>

                <tr>
                    <td colspan = "2">
                        <p>
                            <strong>X ANEXA C.1&nbsp;</strong>– PROCES VERBAL DE ACCEPTANTA SI PUNERE IN FUNCTIUNE
                        </p>
                    </td>
                </tr>

                <tr>
                    <td colspan = "2">
                        <p>
                            <strong>X ANEXA C.2&nbsp;</strong>– PROCES VERBAL DE PREDARE / PRIMIRE CUSTODIE ECHIPAMENTE
                        </p>
                    </td>
                </tr>

                <tr>
                    <td colspan = "2">
                        <p>
                            <strong>X ANEXA C.3&nbsp;</strong>– INFORMARE CU PRIVINTA LA PRELUCRAREA DATELOR DUMNEAVOASTRA CU CARACTER PERSONAL (DENUMITA IN CONTINUARE "INFORMAREA")
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>

            <h2>CONDITII GENERALE DE FURNIZARE A SERVICIILOR URBAN ("CONDITII GENERALE") – ANEXA A.1</h2>
            <h3>1. Definitii: </h3>
            <p>
                in cazul in care legea nu prevede altfel, termenii folositi vor avea urmatoarele definitii:
                <br>
                (a)&nbsp;<strong>Client:&nbsp;</strong>persoana fizica sau juridica parte a acestui contract;
                <br>
                (b)&nbsp;<strong>Contract:&nbsp;</strong>intelegerea scrisa a partilor cu privire la furnizarea serviciilor
                incheiata intre client si furnizor, incluzand: Formularul, Conditiile Generale, Conditiile Specifice
                corespunzatoare fiecarui Serviciu (constituie ca anexe ale Contractului), ANEXA A, denumita
                "Servicii, Promotii, Tarife si Discounturi", precum si orice alte documente incheiate in baza
                Contractului (contract de inchiriere echipamente, proces verbal - primire echipamente, proces verbal
                de punere in functiune si acceptanta, etc.);
                <br>
                (c)&nbsp;<strong>Conditii generale:&nbsp;</strong>document parte a contractului continand conditiile generale
                aplicabile furnizarii tuturor serviciilor care fac obiectul contractului;
                <br>
                (d)&nbsp;<strong>Conditii specifice:&nbsp;</strong>document parte a contractului continand conditiile
                particulare aplicabile furnizarii unui anumit serviciu, denumit "Anexa";
                <br>
                (e)&nbsp;<strong>Cont de utilizator:&nbsp;</strong>toate elementele, resursele si datele de identificare in
                reteaua de comunicatii a clientului, necesare accesului acestuia la serviciile asigurate de furnizor
                si utilizarii acestora;
                <br>
                (f)&nbsp;<strong>Data activarii serviciului:&nbsp;</strong>data la care serviciul este pentru prima data
                disponibil (poate fi utilizat de catre client) sau data la care clientul utilizeaza pentru prima
                data serviciul (sau o parte din acesta), oricare ar interveni prima; pentru stabilirea datei
                activarii vor fi luate in considerare inregistrarile interne ale furnizorului, afara de cazul in
                care data a fost consemnata intr-un document semnat de ambele parti; de regula, data instalarii
                coincide cu data activarii serviciului;
                <br>
                (g)&nbsp;<strong>Date de trafic:&nbsp;</strong>orice date prelucrate in scopul transmiterii unei comunicari
                prin reteaua clientului sau in scopul facturarii contravalorii acestei operatiuni (date referitoare
                la rutare, reteaua in care origineaza sau se termina comunicarea, durata sau momentul comunicarii,
                momentul de inceput sau se sfarsit al comunicarii);
                <br>
                (h)&nbsp;<strong>Echipament:&nbsp;</strong>echipament necesar furnizarii serviciilor puse la dispozitia
                clientului de catre furnizor;
                <br>
                (i)&nbsp;<strong>Furnizor:&nbsp;</strong>URBAN NETWORK SOLUTIONS S.R.L, persoana juridica parte a contractului
                care pune la dispozitia clientului serviciul solicitat;
                <br>
                (j)&nbsp;<strong>Informatie:&nbsp;</strong>datele obtinute sau detinute de furnizor despre client sau despre
                modul in care serviciile sunt utilizate, cum sunt datele cu caracter personal (nume, prenume, cod
                numeric personal, adresa, datele de identificare cuprinse in actul de identitate, etc.), datele de
                trafic si datele de localizare, astfel cum sunt definite de Legea nr. 506/2004 si Legea nr.
                677/2001, asupra prelucrarii si folosirii carora clientul si-a exprimat vointa;
                <br>
                (k)&nbsp;<strong>Lista de tarife:&nbsp;</strong>lista tarifelor aferente serviciilor si echipamentelor URBAN
                disponibila accesand site-ul URBAN, apeland Serviciul Relatii cu Clientii sau la magazinele URBAN;
                <br>
                (l)&nbsp;<strong>Locatie:&nbsp;</strong>adresa clientului unde va fi instalat echipamentul si unde clientul
                este autorizat sa receptioneze serviciul;
                <br>
                (m)&nbsp;<strong>Mediu de transmisii de date:&nbsp;</strong>cablu coaxial, fibra optica, unde radio, cablu de
                cupru aflat in proprietatea sau folosinta clientului necesar transmisiei TV, Internet si telefonie;
                <br>
                (n)&nbsp;<strong>Optiuni:&nbsp;</strong>servicii suplimentare furnizate de catre URBAN;
                <br>
                (o)&nbsp;<strong>Perioada de facturare:&nbsp;</strong>intervalul dintre doua date consecutive de facturare a
                serviciului contractat de client;
                <br>
                (p)&nbsp;<strong>Perioada initiala:&nbsp;</strong>perioada specificata in ANEXA A;
                <br>
                (q)&nbsp;<strong>Punct terminal de retea:&nbsp;</strong>punctul fizic la care clientului ii este
                furnizataccesul la reteaua furnizorului si care delimiteaza domeniul de responsabilitate al
                furnizorului de domeniu de responsabilitate al clientului; punctul terminal de retea va fi
                determinat de la caz la caz, in functie de conditiile tehnice de furnizare a serviciului;
                <br>
                (r)&nbsp;<strong>Posta electronica:&nbsp;</strong>un sistem postal care permite clientului sa schimbe mesaje /
                documente electronice in retele de comunicatii electronice;
                <br>
                (s)&nbsp;<strong>Retea internet:&nbsp;</strong>totalitatea resurselor accesibile in reteaua IP internationala;
                <br>     	
                (t)&nbsp;<strong>Site-ul URBAN:&nbsp;</strong>https://www.ctaonline.ro, precum si alte site-uri indicate de
                URBAN, dupa caz;
                <br>
                (u)&nbsp;<strong>Serviciu:&nbsp;</strong>orice serviciu furnizat de furnizor in baza contractului (linie
                telefonica, acces internet, TV); in situatia in care clientului ii sunt puse la dispozitie doua sau
                trei servicii, se va considera ca acestuia ii este furnizat un pachet de servicii &nbsp;<strong>("Pachet de Servicii")</strong>;
                <br>
                (v)&nbsp;<strong>Serviciul "Relatii cu Clientii":&nbsp;</strong>serviciul telefonic oferit de URBAN la numarul
                de telefon: 0241 700 000 intre orele 08:00 – 20:00 si 07INTERNET NON-STOP sau online prin e-mail:
                client@07internet.ro, are rolul de a prelua si solutiona cererile privind furnizarea serviciului;
            </p>
        
            <h3>2. Durata</h3>
            <p>
                2.1. Contractul se incheie pe perioada initiala specificata in ANEXA A, incepand de la data
                activarii serviciului si se prelungeste automat pe perioade de 12 luni si in aceleasi conditii
                contractuale prin notificarea clientului de catre furnizor in vederea formularii in scris de catre
                client a unei optiuni de prelungire a valabilitatii acestuia.
            </p>

            <h3>3. Instalarea si punerea in functiune a serviciului. Echipament.</h3>
            <p>
                3.1. Instalarea si punerea in functiune a serviciului se face in termenul de 5 (cinci) zile
                lucratoare (daca instalarea se face in aria de acoperire) din momentul semnarii prezentului
                contract, exceptand zilele in care, ca urmare a conditiilor meteo nefavorabile, nu se pot efectua
                lucrari de conectare (duminica nu se fac lucrari) si poate fi prelungit cu pana la 8 (opt) zile
                lucratoare datorate ca urmare a unor cauze de ordin tehnic constatate ulterior semnarii prezentului
                contract. Instalarea are loc daca clientul detine echipamente specifice necesare furnizarii
                serviciului. Furnizorul poate refuza conectarea acestora daca apreciaza ca acestea nu pot asigura
                conditii tehnice optime pentru furnizarea serviciului. Punerea in functiune se va face pe baza unui
                proces verbal de punere in functiune si acceptanta.
                <br>
                3.2. Furnizorul asigura intretinerea si reparatia echipamentelor din reteaua proprie necesare
                furnizarii serviciului (inlocuire echipamente de retransmisie, cabluri, recievere digitale) pana la
                intrarea in locatia clientului. Furnizorul asigura clientului, la cerere, servicii de intretinere si
                reparatii a echipamentelor aflate in locatia acestuia. Preturile si tarifele pentru efectuarea unor
                astfel de servicii sunt disponibile spre informarea clientului prin afisarea lor pe site-ul
                https://www.ctaonline.ro sau in casierie si sediu.
                <br>
                3.3. Daca URBAN preda in custodie clientului echipamente &nbsp;<strong>("Echipamente URBAN")</strong>,
                URBAN este proprietarul echipamentelor URBAN pe perioada custodiei, iar clientul nu va transfera
                catre terti dreptul de a folosi echipamentele URBAN. Daca cleintul produce deteriorari
                echipamentelor, va suporta contravaloarea reparatiilor efectuate de URBAN, iar daca acesta decide
                inlocuirea unuia / mai multor echipamente, clientul va plati contravaloarea acestuia / acestora la
                valoarea de la data inlocuirii (mentionata in "Lista de tarife") si orice tarife aplicabile.
                Clientul se obliga sa restituie echipamentele URBAN date in custodie, in stare de functionare, in
                termen de 3 zile lucratoare de la data incetarii furnizarii serviciilor, in caz contrar fiind
                obligat la plata lor la valoarea de la data incetarii furnizarii serviciilor (mentionata in "Lista
                de tarife"). Clientul suporta toate riscurile legate de pieirea echipamentelor URBAN pe durat
                custodiei, inclusiv pentru cazuri fortuite.
                <br>
                3.4. Daca instalarea serviciului depinde de actele sau faptele unui tert, furnizorul o va realiza
                numai dupa intrunirea tuturor conditiilor necesare, cum ar fi: obtinerea autorizatiilor sau
                aprobarilor necesare, instalarea conexiunilor care depind de tert; obtinerea avizelor / aprobarilor
                / autorizatiilor necesare cad in sarcina exclusiva a cientului.
            </p>
        
            <h3>4. Tarife si modalitati de plata</h3>
            <p>
                4.1. Serviciile sunt facturate lunar. Factura lunara contine contravaloarea serviciilor furnizate in
                perioada de facturare si abonamentul pentru luna in curs&nbsp;<strong>("Abonament")&nbsp;</strong>si va fi
                ridicata personal de catre client de la casieria sau sediul furnizorului daca nu se specifica
                altfel. In cazul in care factura nu va fi ridicata lunar ea va fi transmisa beneficiarului continand
                cumulul de luni neachitate precum si penalitatile de intarziere aferente. Tarifele sunt cele
                specificate in ANEXA A, ANEXA A.1, lista completa a tarifelor fiind disponibila
                <br>
                (i) pe site-ul URBAN;
                <br>
                (ii) apeland Serivicul "Relatii cu Clientii"
                <br>
                (iii) sau la sediile / caseriile URBAN.
                <br>
                4.2. Tariful Abonamentului este stabilit in lei si nu include TVA. Furnizorul nu va modifica pretul
                Abonamentului pe parcursul Perioadei initiale S.C. URBAN S.R.L. si va mentine acelasi tarif pe o
                perioada de 1 an de zile de la expirarea Perioadei initiale daca sunt indeplinite conditiile
                prevazute la Art. 2.1. In cazul devalorizarii semnificative a Leului in raport cu Euro, URBAN poate
                include diferenta de curs valutar in tarifele serviciilor, cu respectarea prevederilor.
                <br>
                4.3. Tarifele serviciilor telefonice excluzand abonamentul, sunt facturate in euro si nu contin TVA.
                Pentru prima luna calendaristica de furnizare a Serviciului, in ceea ce priveste plata
                Abonamentului, furnizorul va emite o factura pentru o suma calculata proportional cu numarul de zile
                de furnizare a Serviciului, daca nu se specifica altfel in ANEXA A.
                <br>
                4.4. Furnizorul va expedia la adresa indicata de client, factura in plic, fara confirmare de
                primire, in primele 7 zile ale lunii sau la adresa de e-mail mentionata in Contract, dar numai in
                cazul in care clientul isi da consimtamatul in scris. Furnizorul nu raspunde de neprimirea facturii
                de catre client din motive neimputabile URBAN. Clientul care pretinde ca nu a primit factura nu este
                exonerat de la plata contravalorii Serviciului, la cererea clientului, URBAN putand elibera o copie
                a facturilor.
                <br>
                4.5. Termenul de plata a facturii este de maxim 15 (cincisprezece) zile de la data emiterii acestia
                &nbsp;<strong>("Termenul de plata")</strong>.
                <br>
                4.6. Plata se va face, in lei, in contul bancar indicat pe factura sau in numerar la casieriile
                furnizorului. Plata va fi considerata facuta in terment daca suma datorata se regaseste in contul
                bancar al furnizorului la data scadentei sau a fost achitata la casieriile acestuia pana la acea
                data inclusiv.
                <br>
                4.7. Clientul poate contesta, in scris, sumele facturate pana cel tarziu la expirarea termenului de
                plata, fara a fi exonerat de la achitarea integrala a facturii in termenul mai sus mentionat, urmand
                ca eventualele diferentesa fie corectate in urmatoarea factura emisa de URBAN. Necontestarea
                facturii in termenul mai sus mentionat reprezinta acceptarea neconditionata a sumelor facturate,
                clientul pierzand dreptul de a contesta factura.
                <br>
                4.8. Pentru neplata facturii pana la expirarea termenului de plata, clientul este de drept in
                intarziere, fara indeplinirea vreunei formalitati, si datoreaza penalitati de intarziere panala data
                achitarii integrale a sumelor datorate, in valoare de 0,5%/zi. Totalul penalitatilor de intarziere
                poate depasi cuantumul sumei asupra careia sunt calculate.
                <br>
                4.9. Neplata totala a facturii peste 55 (cincizeci si cinci) de zile fata de termenul de plata, da
                dreptul furnizorului sa rezilieze contractul pentru toate serviciile pe plin drept, fara interventia
                instantei sau alte formalitati prealabile, pe baza unei notificari prealabile scrise de 15
                (cincisprezece) zile.
            </p>

            <h3>5. Drepturile si obligatiile furnizorului</h3>
            <p>
                5.1. Furnizorul este singurul autorizat sa execute lucrari de instalare, bransare, verificare,
                reparatii, intretinere, debransare, deconectare, rebransare, reconectare si orice interventii,
                pentru reteaua operata de acesta, pana la Punctul terminal al retelei, inclusiv. Furnizorul raspunde
                pentru furnizarea Serviciului numai pana la Punctul terminal al retelei.
                <br>
                5.2. Furnizorul se obliga sa remedieze deranjamentele aparute in reteaua sa, astfel incat furnizarea
                Serviciului sa fie restabilita intr-un interval de 72 ore de la inregistrarea acestora, cu exceptia
                situatiilor in care Conditiile Specifice de furnizare a fiecaruia dintre servicii preved un alt
                termen. In cazul in care restabilirea Serviciului in termenele mentionate mai sus, nu este posibila,
                furnizorul va acorda clientului discount pentru nefunctionare conform Conditiilor Specifice de
                furnizare ale fiecarui serviciu. Restituirea sumelor pentru perioada de nefunctionare a Serviciului
                se face prin creditarea cu valoarea in lei facturata la data nefunctionarii, cu evidentiere in
                factura urmatoare. In acelasi mod se face restituirea sumelor si in cazul in care nefunctionarea
                serviciilor nu se datoreaza furnizorului, ci unui tert, fara ca aceasta sa constituie cauza de
                incetare a Contractului.
                <br>
                5.3. Furnizorul are obligatia de a asigura securitatea retelei si confidentialitatea comunicarilor,
                cu exceptia situatiei in care legea/autoritatile/instantele judecatoresti ar prevedea/solicita
                altfel, ori legea ar impune o alta conduita din partea furnizorului; in aceste cazuri furnizorul
                este exonerat de orice raspundere fata de client.
                <br>
                5.4. Furnizorul isi rezerva dreptul sa refuze incheierea oricarui contract si sa refuze furnizarea
                oricariu serviciu, in cazul clientilor ori membrilor de familie ai acestora care locuiesc la aceeasi
                adresa cu clientul, daca acestia inregistreaza datorii catre URBAN, inclusivdaca acestea rezulta din
                alte contracte sau di orice alte raporturi juridice.
            </p>

            <h3>6. Drepturile si obligatiile clientului</h3>
            <p>
                6.1. Clientul nu va interveni asupra echipamentelor sau lucrarilor realizate de catre furnizor in
                vederea furnizarii serviciilor.
                <br>
                6.2. Clientul va permite numai reprezentatilor furnizorului accesul la locatiile unde sunt plasate
                sau urmeaza a fi instalate echipamente / elemente ale retelei in vederea furnizarii / mentinerii su
                / sau desfiintarii serviciului.
                <br>
                6.3. Clientul va suporta contravaloarea cheltuielilor determinate de refacerea sau repunerea in
                functiune a serviciului, datorate culpei sale, fiind instiintat, in prealabil, de catre furnizor
                asupra devizului estimativ al lucrarilor pentru repunerea in funcitune a serviciului.
                <br>
                6.4. Clientul se obliga sa foloseasca in retea numai echipamente terminale a caror conformitate este
                recunoscuta potrivit HG nr. 88/2003, privind echipamentele radio si echipamenteleterminale de
                telecomunicatii si recunoasterea mutuala a conformitatii acestora sau a altor acte normative
                incidente.
                <br>
                6.5. Clientul se obliga sa notifice deindata furnizorul in cazul in care devine subiect al
                procedurilor insolventei.
                <br>
                6.6. Clientul intelege si este de acord cu construirea retelei furnizorului si cu realizarea
                circuitului individual de cablu in si pe imobil.
            </p>
        
            <h3>7. Modificarea clauzelor contractului</h3>
            <p>
                7.1. URBAN poate modifica unilateral contractul (ex.: tarifele, modalitatea de aplicare a acestora,
                penalitatile de intarziere, caracteristicile serviciilor), cu conditia notificarii in scris a
                clientului, cu cel putin 30 (treizeci) de zile inainte. Modificarile vor fi aplicabile prin semnarea
                unui act aditional. Daca clientul nu este de acord cu o modificare, poate solicita denuntarea
                unilaterala a contractului pentru serviciul supus modificarii, pe baza unei notificari ce trebuie sa
                ajunga la furnizor inainte de data aplicarii modificarii, fara despagubiri.
                <br>
                7.2. Clientul poate solicita modificarea caracteristicilor serviciului sau a optiunilor numai daca
                are toate obligatiile contractuale achitate la zi.
                <br>
                7.3. Orice solicitare a clientului referitoare la modificarea abonamentului, a titularului sau a
                altor informatii privind clientul trebuie realizata personal sau prin mandatar imputernicit printr-o
                procura speciala, respectiv prin completarea unei cereri sau prin semnarea unui act aditional, dupa
                caz, cu cel putin 30 (treizeci) de zile inainte, urmand ca aceasta sa intre in vigoare in prima zi a
                lunii calendaristice urmatoare celei in care termenul de 30 (treizeci) de zile se implineste. Daca
                modificarea abonamentului presupune instalarea unui nou serviciu, se vor aplica prevederile
                punctului 4 din contract privind termenul de instalare a serviciilor.
                <br>
                7.4. In caz de migrare catre un pachet de servicii care are un pret de facturare mai mic decat cel
                anterior, si clientul a beneficiat de o oferta promotionala / discount pentru pachet de servicii
                respectiv, clientul va fi obligat la plata unor despagubiri in valoare de 50% din diferenta de preta
                tarifelor pachetelor de servicii pentru perioada ramasa din contract.
                <br>
                7.5. Daca, pe durata contractului, serviciul nu mai poate fi furnizat la parametrii contractati,
                <br>
                strong>a)&nbsp;</strong>fie partile vor agrea furnizarea unui serviciu inferior calitativ la tariful
                aferent acestuia, fara despagubiri,
                <br>
                <strong>b)&nbsp;</strong>fie contractul inceteaza de plin drept, fara despagubiri.
            </p>

            <h3>8. Cesiunea contractului</h3>
            <p>
                8.1. Drepturile si obligatiile furnizorului nascuta din sau in legatura cu prezentul contract pot fi
                cesionate, prevederile acestuia urmand a fi aplicabile in integralitatea sa oricarui tert care
                achizitioneaza in tot sau in parte reteaua furnizorului, de la data achizitiei.
                <br>
                8.2. Clientului ii este interzisa cesiunea, redistribuirea sau revanzarea serviciului /
                echipamentelor furnizate, fara acordul scris al furnizorului.
            </p>

            <h3>9. Suspendarea furnizarii serviciului</h3>
            <p>
                9.1. Neplata totala a facturii peste 15 (cincisprezece) zile fata de termenul scadent da dreptul
                furnizorului sa suspende furnizarea tuturor serviciilor. Furnizarea se reia in maxim 24 (douazeci si
                patru) de ore de la achitarea integrala a sumelor datorate.
                <br>
                9.2. Cu exceptia serviciului de internet, furnizorul are dreptul de a suspenda in tot sau in parte
                furnizarea serviciilor, pe o perioada de maxim 15 (cincisprezece) zile, in vederea efectuarii de
                lucrari de intretinere sau dezvoltare a retelei si va rambursa clientului numai abonamentul aferent
                serviciului afectat, in cota fractionara, corespunzator perioadei efective de nefunctionare. In
                toate cazurile, rambursarea se face prin creditare, cu evidentiere in factura emisa in luna
                urmatoare.
            </p>
            <p>
                <br>
                9.3. Clientul poate solicita suspendarea serviciului in baza unei cereri depuse la casieria
                furnizorului cu cel putin 15 (cincicsprezece) zile inainte de data la care se doreste suspendarea.
                Suspendarea serviciului poate fi solicitata o singura data in decursul perioadei initiale, pentru o
                durata de 1, 2 sau maxim 3 luni de zile, cu incepere din prima zi a lunii urmatoare solicitarii de
                suspendare. Perioada minima se va prelungi cu perioada de suspendare. Daca clientul a contractat mai
                multe servicii, suspendarea unui serviciu implica suspendarea tuturor serviciilor.
            </p>

            <h3>10. Incetarea contractului</h3>
            <p>
                10.1. URBAN are dreptul de a denunta unilateral contractul, cu o notificare prealabila scrisa de 2 (doua) zile, in urmatoarele situatii:
                <br>
                <strong>a)&nbsp;</strong>decesul clientului;
                <br>
                <strong>b)&nbsp;</strong>clientul vinde locatia;
                <br>
                <strong>c)&nbsp;</strong>un eveniment de forta majora ce dureaza mai mult de 30 (treizeci) de zile;
                <br>
                <strong>d)&nbsp;</strong>in orice alta situatie expres mentionata in contract.
                <br>
                10.2. Clientul are dreptul de a denunta unilateral contractul:
                <br>
                <strong>a)&nbsp;</strong>oricand si cu aplicarea prevederilor mentionate mai jos referitoare la despagubiri, cu o notificare prealabila scrisa de 30 (treizeci) de zile, contractul incetand de plin drept in prima zi a lunii urmatoare celei in care termenul de 30 (treizeci) de zile se implineste;
                <br>
                <strong>b)&nbsp;</strong>clientul are dreptul de a denunta unilateral contractul in cazul in care si-a indeplinit toate obligatiile conform &nbsp;<strong>ANEXA A&nbsp;</strong> si punctului 2 din contract;
                <br>
                <strong>c)&nbsp;</strong>notificarea intentiei de reziliere din partea beneficiarului, inainte de termenul prevazut in &nbsp;<strong>ANEXA A&nbsp;</strong> si conform punctului 2 va fi procesata numai daca acesta achita daune interese echivalente cu valoarea abonamentelor ramase de plata pana la sfarsitul perioadei contractuale.
                <br>
                10.3. Daca clientul doreste sa achizitioneze alte servicii ale furnizorului dupa rezilierea contractului, acesta va semna un nou contract, doar dupa achitarea tuturor obligatiilor ce decurg din anteriorul contract.
                <br>
                10.4. Daca o parte nu-si indeplineste obligatiile contractuale, cealalta parte este indreptatita sa rezilieze de plin drept contractul, fara interventia instantei, putan solicita despagubiri. Daca nu se prevede altfel in contract, rezilierea va opera pe baza unei notificari prealabilescrise de 30 (treizeci) de zile. Contractul va inceta de plin drept in prima zi a lunii urmatoare celei in care termenul de 30 (treizeci) de zile se implineste, daca partea in culpa nu a inlaturat, pana la implinirea termenului, situatia ce a atras notificarea de reziliere.
                <br>
                10.5. In situatia in care serviciile nu pot fi activate in termenul mentionat la punctul 4 din formular din motive de natura tehnica, atat clientul cat si URBAN au dreptul sa considere contractul incetat de drept, fara notificare prealabila, fara interventia instantei si alte formalitati si fara acordarea de daune interese. In aceasta situatie, URBAN va restitui clientului contravaloarea taxelor de instalare si activare a serviciilor deja achitate, daca este cazul.
                <br>
                10.6. Daca contractul inceteaza, pentru unul sau mai multe servicii, inainte de expirarea perioadei initiale, urmare a rezilierii de catre URBAN sau a denuntarii unilaterale de catre client (altfel decat in cazul in care nu este de acord cu majorarea tarifelor), si clientul a beneficiat de o oferta promotionala / discount pentru serviciul respectiv, clientul va fi obligat la plata unor despagubiri pentru incetarea contractului inainte de perioada minima, specificate in &nbsp;<strong>ANEXA A</strong>.
                <br>
                10.7. Clientul nu datoreaza despagubiri pentru incetare prematura in cazul schimbarii locatiei la care serviciile sunt furnizate, daca furnizorul nu are solutie tehnica pentru furnizarea serviciilor la noua locatie. Prin "locatie" in sensul acestui paragraf se intelege o noua locatie a clientului aflata in raza teritoriala de activitate a furnizorului.
            </p>

            <h3>11. Prelucrarea informatiei</h3>
            <p>
                11.1. Baza legala a prelucrarilor
                <br>
                In vederea executarii prezentului contract, a obligatiilor legale care revin 07INTERNET si a exercitari intereselor legitime ale acestuia, 07INTERNET va prelucra datele cu caracter personal ale clientului in conformitate cu aspectele detaliate in &nbsp;<strong>ANEXA C3 – Informare cu privire la prelucrarea datelor dumneavoastra cu caracter personal (denumita in continuare&nbsp;<strong>"Informarea"</strong>)</strong>, parte integranta a prezentului contract. Datele clientului vor fi prelucrate pe perioadele mentionate in &nbsp;<strong>"Informare".</strong>
                <br>
                11.2. Drepturile clientului
                <br>
                Totodata, in legatura cu prelucrarile datelor cu caracter personal ale clientului, aveti, dupa caz, drepturile prevazute in &nbsp;<strong>"Informare":&nbsp;</strong>si anume: accesarea datelor, rectificarea datelor, opozitia la prelucrarea datelor, stergerea datelor, portabilitatea datelor, restrictionarea prelucrarii datelor, retragerea consimtamantului, dreptul de a depune o plangere la Autoritatea Nationala pentru Supravegherea Prelucrarii Datelor cu Caracter Personal.
                <br>
                11.3. Comunicari in executarea contractului
                <br>
                Vom trimite comunicari legale de executarea contractului (spre exemplu, informari cu privire la probleme tehnice, financiare, raspunsuri la intrebarile adresate de client etc.) prin orice mijloc de comunicare (adresa de e-mail, apel telefonic, SMS, adresa de posta clasica) pe care clientul l-a comunicat la incheierea sau pe parcursul contractului.
                <br>
                11.4. Comunicari comerciale
                <br>
                In cazul in care clientul comunica adresa de e-mail cu ocazia vanzarii unui produssau serviciu, la incheierea sau pe parcursul contractului, comunicarile 07INTERNET, in exercitarea intereseului nostru legitim, in scop de reclama, marketing, publicitate si sondaje vor fi transmise la adresa de e-mail a clientului, clientul poate oricand sa se opuna la primirea acestor comunicari inclusiv la momentul incheierii contractului si comunicarii adresei de e-mail.
                <br>
                Va rugam sa bifati:
                <br>
                [] NU daca doriti sa primiti comunicari comerciale prin adresa de e-mail furnizata.
                <br>
                In baza consimtamantului dumneavoastra exprimat intr-un mod expres, pe care il puteti retrage oricand, va vom putea trimite comunicari in scop de reclama, marketing, publicitate si sondaje si prin alte canale de comunicare, potrivit optiunilor dumneavoastra exprimate prin bifarea variantelor de mai jos:
                <strong>[X]:&nbsp;</strong>Imi exprim consimtamantul de a primi comunicari in scop de reclama, marketing, publicitate si sondaje.
                <br>
                <strong>[X]:&nbsp;</strong>DA pe urmatoarele canale:&nbsp;<strong>[X]:&nbsp;</strong>SMS,&nbsp;<strong>[X]:&nbsp;</strong>apel telefonic,&nbsp;<strong>[X]:&nbsp;</strong>apel telefonic desfasurat cu ajutorul unui robot,&nbsp;<strong>[X]:&nbsp;</strong>posta clasica.
                <br>
                Daca alegeti sa nu va exprimati consimtamantul pentru niciunul dintre canalele de mai sus, nu veti primi comunicari in scop de reclama, marketing, publicitate si sondaje, pe oricare dintre canalele mentionate mai sus (SMS, apel telefonic, apel telefonic desfasurat cu ajutorul unui robot, posta clasica).
                <br>
                11.5. Subcontractori si afiliati
                <br>
                Pentru indeplinirea anumitor obligatii ale 07INTERNET (de exemplu, instalarea echipamentelor la adresa clientului, remedierea defectiunilor tehnice, relatia cu clienti, actiuni de reclama, marketing, publiciate si sondaje), se folosesc subcontractori. In selectarea acestor subcontractor, 07INTERNET se asigura ca acestia respecta la randul lor drepturile dumneavoastra cu privire la prelucrarea datelor cu caracter personal astfel cum sunt detaliate in &nbsp;<strong>"Informare"</strong>. Categorii de subcontractori: agentii de marketing, societati ce desfasoara activitati de instalare / reparatii, de procesare a platilor, distributie / curierat, de tipografie, de sondaj, de call-center, de recuperare / colectare debite. In conformitate cu&nbsp;<strong>"Informarea"</strong>, anumite activitati (de exemplu, efectuarea statisticilor si a analizelor, asigurarea functionarii serviciilor) vor presupune dezvaluirea datelor personale catre afiliatii nostri (de exemplu, companiei noastre mama, altor companii din cadrul grupului 07INTERNET).
            </p>

            <h3>12. Notificari</h3>
            <p>
                12.1. Clientul va trimite notificarile mentionate in contract la sediul furnizorului, prin scrisoare recomandata cu confirmare de primire. Notificarile comunicate la alte adrese nu vor fi opozabile.
                <br>
                12.2. Furnizorul poate notifica clientul prin afisare in casieriile proprii, prin afisare pe paginile online proprii, telefon sau scrisoare trimisa la adresele specificate de client in contract.
            </p>

            <h3>13. Lege. Litigii</h3>
            <p>
                13.1. Contractul este guvernat de legea romana.
                <br>
                13.2. Orice neintelegere privind executarea contractului va fi rezolvata amiabil. Daca o astfel de rezolvare nu e posibila, litigiul va fi inainte instantelor judecatoresti.
            </p>

            <h3>14. Fraude</h3>
            <p>
                14.1. Clientul declara in mod expres ca intelege ca serviciile ii sunt destinate si furnizate numai in calitatea sa de utilizator final si numai pentru scopurile mentionate in contract.
                <br>
                14.2. Serviciile / Echipamentele sunt furnizate clientului numai in calitatea sa de utilizator final si numai pentru uzul sau privat. SUnt considerate activitati frauduloase ale clientului:
                <br>
                <strong>a)&nbsp;</strong>furnizarea de informatii / documente eronate / falsificate care au stat la baza incheierii contractului;
                <br>
                <strong>b)&nbsp;</strong>mutarea echipamentelor in alta locatie, fara acordul URBAN;
                <br>
                <strong>c)&nbsp;</strong>furnizarea serviciilor catre treti (contra-cost sau gratuit);
                <br>
                <strong>d)&nbsp;</strong>utilizarea serviciilor in vederea furnizarii de servicii de comunicatii electronice catre terti sau in vederea transferului de trafic in reteaua furnizorului si / sau alte retele;
                <br>
                <strong>e)&nbsp;</strong>utilizarea serviciilor cu incalcarea legii.
                <br>
                14.3. De asemenea, clientul este responsabil pentru daunele rezultate din neindeplinirea obligatiilor asumate prin prezentul articol. Prevederi specifice serviciilor TV: orice incercare de a copia continutul canalelor TV este considerata activitate frauduloasa. Receptia in afara granitelor Romaniei, retransimisia in scopuri private sau comerciale si / sau copierea pentru multiplicare sau multiplicarea serviciilor sunt considerate activitati frauduloase.
                <br>
                14.4. Daca identifica o activitate frauduloasa, URBAN are dreptul:
                <br>
                <strong>a)&nbsp;</strong>sa suspende furnizarea tuturor serviciilor pe care le furnizeaza clientului sau sa rezilieze contractul de plin drept, fara interventia instantei sau alte informatii prealabile;
                <br>
                <strong>b)&nbsp;</strong>sa refuze furnizarea unui nou serviciu;
                <br>
                <strong>c)&nbsp;</strong>sa retraga clientului orice forma de beneficii;
                <br>
                <strong>d)&nbsp;</strong>sa ia masurile prevazute in politica de securitate a URBAN, de asemenea, clientul este responsabil pentru daunele rezultate din neindeplinirea obligatiilor asumate prin prezentul articol.
            </p>

            <h3>15. Forta majora</h3>
            <p>
                15.1. Daca nu se prevede altfel in contract, forta majora exonereaza de raspundere partea care o invoca, dar numai in masura si pentru perioada in care indeplinirea clauzelor contractuale este impiedicata sau intarziata de situatia de forta majora.
                <br>
                15.2. Prin caz de forta majora se inteleg toate evenimentele si / sau imprejurarile imprevizibile si inevitabile, independente de vointa partii care o invoca (ex.: razboaie, revolutii, inundatii, cutremure, epidemii, embargouri, restrictii de carantina, temperaturi foarte ridicate sau foarte scazute) si care, aparand dupa incheierea contractului, impiedica sau intarzie, total sau partial, executarea acestuia.
                <br>
                15.3. Partea care invoca forta majora este obligata sa notifice cealalta parte, prin scrisoare recomandata, in termen de 5 (cinci) zile de la inceperea evenimentelor sau imprejurarilor considerate drept forta majora, comunicand, totodata, si documente eliberate de o autoritate competenta care sa certifice cazul de forta majora.
                <br>
                15.4. Daca evenimentul de forta majora dureaza mai mult de 3 (trei) luni, oricare dintre parti va avea dreptul de a denunta unilateral contractul.
            </p>

            <h3>16. Limitarea raspunderii</h3>
            <p>
                16.1. Furnizorul raspunde de functionarea S.C. URBAN S.R.L. serviciilor pana la punctul terminal de retea.
                <br>
                16.2. Furnizorul nu raspunde pentru apelurile catre destinatii cu tarif ridicat (international, servicii cu valoarea adaugata) generate prin fenomentul de "modem hijacking".
                <br>
                16.3. Furnizorul nu este raspunzator daca transmiterea si receptionarea serviciului este alterata datorita unor factori de natura:
                <br>
                <strong>a)&nbsp;</strong>fenomene naturale care afecteaza receptionarea serviciilor;
                <br>
                <strong>b)&nbsp;</strong>intreruperi sau variatii mari de curent in locatie sau in imobilul unde este instalata reteaua de distributie a semnalului;
                <br>
                <strong>c)&nbsp;</strong>instalarea sau utilizarea de catre client de echipamente neautorizate;
                <br>
                <strong>d)&nbsp;</strong>interferentele cu alte sisteme electronice de comunicatie;
                <br>
                <strong>e)&nbsp;</strong>utilizarea necorespunzatoare sau frauduloasa a serviciilor.
                <br>
                16.4. Exceptand cazurile in care se prevede altfel in contract, niciuna din parti nu este raspunzatoare fata de cealalta parte pentru niciun fel de daune indirecte sau daune de orice natura cum ar fi, dar fara a se limita la acestea, beneficiul nerealizat, pierderi de clienti, pierderi de profit, afectare a reputatiei sau pierderea de oportunitati de afaceri etc.
            </p>

            <h3>17. Informatii si Relatii cu Clientii</h3>
            <p>
                17.1. Orice informatii suplimentare privind serviciile pot fi obtinute de la sediul / casieriile furnizorului, apeland gratuit &nbsp;<strong>Serviciul Vanzari si Relatii cu Clientii:&nbsp;</strong>sau accesand domeniul https://www.ctaonline.ro.
                <br>
                17.2. URBAN asigura serviciul de dispecerat Suport Tehnic, orice nefunctionare a serviciului va fi adusa de catre client la cunostinta URBAN in cel mai scurt timp posibil.
                <br>
                17.3. Clientul este de acord ca reclamatiile formulate sa fie inregistrate. Fiecare reclamatie va fi preluata, fie direct de la client, fie prin fax, impreuna cu datele de identificare si de contact ale acestuia sau ale reprezentantului acestuia. Reclamatia va fi inregistrata in baza de date pentru deranjamente, va primi un numar unic si va fi imediat transmisa spre verificare si solutionare a serviciului responsabil sa rezolve tipul reclamatiei respective. Imediat ce problema a fost identificate si diagnosticata, clientul va fi informat cu privire la perioada maxima in care va avea loc remedierea defectiunii reclamate. Odata cu solutionarea reclamatiei, reprezentantul URBAN care a primit si inregistrat reclamatia, va inregistra in baza de date modul de solutionare a reclamatiei / data solutionarii sau stadiul acesteia. Intocmit in doua exemplare, impreuna cu anexele, cate unul pentru fiecare parte, declara ca se afla in posesia unui exempler complet, inclusiv anexe si orice alte documente in legatura cu contractul.
            </p>

            <h1>CONDITII GENERALE DE FURNIZARE A SERVICIILOR URBAN ("CONDITII GENERALE") – ANEXA A.1</h1>
            <h3 style = "text-align: center;">LA CONTRACTUL NR.:&nbsp;'.$clientId.'</h3>
            <h3 style = "text-align: center;">PREVEDERI SPECIFICE SERVICIULUI DE INTERNET SI TELEVIZIUNE</h3>

            <h3>1. Definitii – in cazul in care legea nu prevede altfel, termenii folositi vor avea urmatoarele defintii: </h3>
            <p>
                (1) &nbsp;<strong>Adresa IP:&nbsp;</strong>identificator unic pentru un calculator personal sau pentru un echipament intr-o retea TCP / IP;
                <br>
                (2) &nbsp;<strong>TCP / IP:&nbsp;</strong>pachet de protocoale de comunicatie folosit pentru interconectarea resurselor din reteaua Internet;
                <br>
                (3) &nbsp;<strong>Adresa MAC:&nbsp;</strong>o adresa fizica ce identifica in mod unic un echipament de comunicatie intr-o retea, interfetele de comunicare dintre calculatoarele personale;
                <br>
                (4) &nbsp;<strong>Flood:&nbsp;</strong>atac informatic care consta in transmiterea intentionata de pachete IP catre o anumita destinatie din reteaua Internet care are ca scop blocarea accesului respectivei destinatii la Internet;
                <br>
                (5) &nbsp;<strong>Retea metropolitana:&nbsp;</strong>totalitatea resurselor electronice accesibile in reteaua IP a clientului pana la punctul de delimitare cu reteaua Internet.
            </p>

            <h3>2. Descrierea serviciului</h3>
            <p>
                2.1. Serviciul de acces la Internet, se refera la accesul la Internet prin asigurarea transmiterii pachetelor de date ale clientului din si catre reteaua Internet precum si asigurarea vizibilitatii spre Internet a adreselor atribuite clientului. Furnizorul va aloca un IP pe baza adresei MAC a interfete de conectare a echipamentului personal al clientului.
                <br>
                2.2. Furnizorul se obligasa furnizeze serviciul cu o rata de transfer mai mare sau cel putin egala cu rata de transfer minima aferenta tipului de abonament solicitat de catre client si precizat in &nbsp;<strong>ANEXA A</strong>.
            </p>

            <h3>3. Functionarea serviciului. Disfunctionalitati</h3>
            <p>
                3.1. Furnizorul asigura disponibilitatea serviciului 24 (douazeci si patru) de ore din 24 (douazeci si patru), 7 (sapte) zile din 7 (sapte) pe saptamana, 365 (treisutesaizecisicinci) de zile pe an, asigurand o disponibilitate minima a servicului de 95%.
                <br>
                3.2. Disponibilitatea serviciului nu include liniile de comunicatie si echipamentele furnizate de terti clientului pentru accesul la serviciu.
                <br>
                3.3. Disponibilitatea lunara efectiva a serviciului se calculeaza procentual, si reprezinta suma timpului de disponibilitate a serviciului raportata la timpul total lunar (720 de ore).
                <br>
                3.4. Tarifele lunare aferente serviciului se diminueaza procentual din valoarea facturii, in functie de disponibilitate lunara a serviciului, daca defectiunile anuntate nu sunt imputabile clientului. Daca intreruperea serviciului se datoreaza culpei furnizorului si aceasta intrerupere depaseste termenul prevazult la art. 3.5, tarifele lunare aferente serviciului se diminueaza proportional cu numarul de zile de neutilizare a acestuia. Suma dedusa va fi scazuta din valoarea facturii pentru luna urmatoare. Reducerea se va aplica procentual din valoarea abonamentului, astfel: disponibilitate lunara 95% sau mai mult – procent discount 0%; disponibilitate lunara 94,99% - 90% – procent discount 5%; disponibilitate lunara 89,99% - 85% – procent discount 10%; disponibilitate lunara 84,99% - 80% – procent discount 15%; disponibilitate lunara 79,99% - 75% – procent discount 20%.
                <br>
                3.5. Nu se considera intrerupere neanuntata:
                <br>
                <strong>a)&nbsp;</strong>intreruperea furnizarii in totalitate sau in parte a serviciului in vederea efectuarii de lucrari de intretinere sau dezvoltare a retelei, clientul fiind instiintat cu privire la o astfel de intrerupere cu minim 24 (douazeci si patru) de ore inainte, cu precizarea perioadei de intrerupere, care nu poate depasi 60 (saizeci) de ore pe saptamana. Furnizorul va urmari ca aceste interventii sa aiba loc in intervalul orar 8:00 – 16:00;
                <br>
                <strong>b)&nbsp;</strong>intreruperea datorata culpei clientului sau a unor terti pentru care furnizorul nu este raspunzator, ori datorita fortei majore.
                <br>
                3.6. In cazul in care clientul solicita , reprezentatii furnizorului se vor deplasa la locatia clientului in maxim 72 de ore. Personalul furnizorului va efectua strict doar operatiunile de repunere in functiune fara a efectua devirusari, reinstalari de sisteme de operare, etc.
            </p>

            <h3>4. Limitarea raspunderii</h3>
            <p>
                4.1. Clientul declara ca a fost informat ca dupa transmiterea pachetelor de date si mesajelor de posta electronica in reteaua Internet, Furnizorul nu mai detine controlul asupra traseului urmat de acestea, existand posibilitatea ca acestea sa nu soseasca la destinatie sau sa soseasca cu intarziere sau ca un anumit site nu este accesibil la momentul dat.
                <br>
                4.2. Avand in vedere ca furnizorul nu poate sa exercite controlul asupra informatiilor care circula in reteaua Internet, acesta nu isi asuma responsabilitatea privind:
                <br>
                <strong>a)&nbsp;</strong>receptionarea de catre client a informatiilor cu caracter ilegal sau prejudiciabil in orice alt mod pentru acesta sau pentru terti;
                <br>
                <strong>b)&nbsp;</strong>prejudiciile cauzate, incluzand, dar fara a se limita la, pierderile de date sau la cele aparute ca urmare a utilizarii datelor si informatiilor receptionate;
                <br>
                <strong>c)&nbsp;</strong>prejudiciile suferite de client ca urmare a accesului neautorizat al unor terte persoane, in reteaua sa de comunicatii.
            </p>

            <h3>5. Drepturile si obligatiile clientului</h3>
            <p>
                5.1. In plus, fata de drepturile si obligatiile mentionate in &nbsp;<strong>Conditiile Generale de Furnizare a Serviciilor URBAN</strong>, clientul se obliga:
                <br>
                – sa stabileasca si sa pregateasca spatiul interior si / sau exterior pentru instalarea echipamentelor furnizorului, intr-o zona care sa asigure integritatea si siguranta acestora, in urma recomandarilor acestuia, asigurand astfel securitatea accesului la serviciile furnizorului. Clientul va informa deindata furnizorul despre orice actiune de care are cunostinta care ar putea reprezenta un atentat la acestea;
                <br>
                – sa asigure procurarea, instalarea, configurarea si intretinerea propriilor sale echipamente de interconectare cu echipamentele furnizorului. Pentru asigurarea integritatii echipamentelor furnizorului, clientul este singurul si direct raspunzator pentru eventuala distrugere totala sau partiala a acestor echipamente, daca aceasta s-a intamplat din culpa sa, a presupusilor sai sau a persoanelor aflate in subordinea sa sau actionand la comanda / instructiunile / cererea acestuia. In nici o situatie, clientul nu aredrept de retentie asupra echipamentelor furnizorului.
                <br>
                – sa nu utilizeze si sa ia toate masurile necesare pentru a nu permite utilizare serviciului furnizat in scopuri ilegale conform legislatiei romane in vigoare sau in scopul prejudicierii, in orice mod, a unor terte persoane, fizice sau juridice, din tara sau din strainatate, prin diferite modalitati si in special prin expedierea de mesaje cu intentia de a hartui, ameninta, insulta, calomnia sau de a deranja in orice alt mod destinatarul, inclusiv prin atac la morala, prin distribuirea de materiale ce incalca drepturile de proprietate intelectuala sau dreptul la intimitate ori alte drepturi ale unor terte persoane, rin orice actiuni care aduc prejudicii altor utilizatori.
                <br>
                Raspunderea furnizorului nu va fi angajata in cazul infiltrarii unui tert in sistemul informatic al clientului, acesta ramanand singurul responsabil de protectia propriului sau sistem informatic.
            </p>

            <h3>6. Transmiterea si accesul informatiilor si serviciilor protejate</h3>
            <p>
                6.1. Clientului ii este interzis:
                <br>
                <strong>a)&nbsp;</strong>sa incerce sa acceseze servicii, informatii, site-uri, sisteme sau relee informatice pe care nu are dreptul sa le acceseze, precum si sa incerce sa identifice vulnerabilitatile unui astfel de sistem, retea sau serviciu;
                <br>
                <strong>b)&nbsp;</strong>sa transmita IP Flood in scopul incarcarii retelei destinatie si / sau a calculatoarelor destinati, determinand astfel o functionare necorespunzatoare a acestora;
                <br>
                <strong>c)&nbsp;</strong>sa utilizeze orice metoda se supraincarcare a sistemului informatic de tipul "denial of service" (atac informatic ce vizeaza blocarea anumitor servicii oferite de calculatorul destinatie);
                <br>
                <strong>d)&nbsp;</strong>sa utilizeze pe propria retea a unor alte adrese IP decat cele alocate de furnizor si transmiterea de pachete TCP / IP sau de mesaje avand header-ul (antetul) contrafacut, indiferent ca sunt anonime sau sub identitate de utilizator;
                <br>
                <strong>e)&nbsp;</strong>sa utilizeze si sa permita utilizarea abuziva a retelei furnizorului prin transmiterea in retea de spam posta electronica nesolicitata, cu continut comercial sau nu ori a mesajelor despre care cunoaste ca are virusi atasati.
                <br>
                6.2. Clientul nu are permisiunea sa utilizeze reteaua si serviciul pentru transmiterea, distribuirea sau stocarea de materiale ce incalca legi sau regulamente aplicabile.
            </p>

            <h3>7. Alte clauze</h3>
            <p>
                7.1. In scopul protejarii retelei furnizorului, dar si a sistemului informatic al clientului, furnizorul are dreptul sa deconecteze temporar adresele IP ale clientului, cu notificarea prealabila a acestuia, in cazul in care constata ca clientul este tinta unor atacuri de tip flood ori "denial of service", pana la solutionarea acestor probleme.
                <br>
                7.2. Furnizorul are dreptul sa stearga orice informatie pe care clientul a introdus-o in retea, care poate determina functionarea necorespunzatoare a retelei URBAN Communication.
                <br>
                7.3. Furnizorul are dreptul sa modifice adresa IP alocata acestuia in orice moment, atunci cand suspecteaza probleme de securitate a sistemului clientului, instiintandu-l deindata pe aceste despre modificare.
                <br>
                7.4. Furnizorul va putea investiga orice incalcare a obligatiilor de mai sus, putand sesiza autoritatile competente in cazul savarsirii oricari fapte ce angajeaza raspundere juridica a clientului.
                <br>
                7.5. Serviciul este furnizat cu respectarea parametrilor de calitate mentionati in Decizia Presedintelui ANRC nr. 138/2002.
            </p>

            <br>

            <h1>ANEXA C.1 – PROCES VERBAL DE ACCEPTANTA SI PUNERE IN FUNCTIUNE</h1>
            <h1>ANEXA C.2 – PROCES VERBAL DE PREDARE - PRIMIRE CUSTODIE ECHIPAMENTE</h1>
            <h5 class = "header__phone" style = "font-weight: bold;">CTR NR.:&nbsp;'.$clientId.'</h5>

            <p>
                ART. 1. – URBAN NETWORK SOLUTIONS S.R.L,<strong> 
            </p>

            <table>
                <tbody>
                    <tr>
                        <td style = "width: 45.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>NR. CRT</p>
                        </td>

                        <td style = "width: 261.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>Denumire echipament</p>
                        </td>

                        <td style = "width: 81.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>Seria</p>
                        </td>

                        <td style = "width: 60.3pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>U.M.</p>
                        </td>

                        <td style = "width: 53.6pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>Cantitate</p>
                        </td>
                    </tr>

                    <tr>
                        <td style = "width: 45.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>1</p>
                        </td>

                        <td style = "width: 261.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caDen1Value.'</p>
                        </td>

                        <td style = "width: 81.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caSerie1Value.'</p>
                        </td>

                        <td style = "width: 60.3pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caUM1Value.'</p>
                        </td>

                        <td style = "width: 53.6pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caCant1Value.'</p>
                        </td>
                    </tr>

                    <tr>
                        <td style = "width: 45.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>2</p>
                        </td>

                        <td style = "width: 261.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caDen2Value.'</p>
                        </td>

                        <td style = "width: 81.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caSerie2Value.'</p>
                        </td>

                        <td style = "width: 60.3pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caUM2Value.'</p>
                        </td>

                        <td style = "width: 53.6pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caCant2Value.'</p>
                        </td>
                    </tr>

                    <tr>
                        <td style = "width: 45.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>3</p>
                        </td>

                        <td style = "width: 261.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caDen3Value.'</p>
                        </td>

                        <td style = "width: 81.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caSerie3Value.'</p>
                        </td>

                        <td style = "width: 60.3pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caUM3Value.'</p>
                        </td>

                        <td style = "width: 53.6pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caCant3Value.'</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p>
                In perfecta stare de functionare d-lui (d-nei)&nbsp;<strong>'.$fullName.'</strong>, reprezentant al firmei&nbsp;<strong>'.$response['organizationName'].'</strong>, cu locatia in&nbsp;<strong>'.$response['city'] . ', '. $response['street1'] . ', '. $response['street2'].'</strong>, sector / judet&nbsp;<strong>Constanta</strong>, legitimat cu C.I.&nbsp;<strong>'.$caSerieCIValue.''.$caNumarCIValue.', C.N.P.: '.$caCNPValue.'</strong>, MAC ADDRESS: '.$caMACValue.'.
            </p>

            <p>
                ART. 2. – Prin prezentul proces verbal, beneficiarul atesta bransarea la reteaua URBAN si buna functionare a serviciilor:
            </p>

            <table>
                <tbody>
                    <tr>
                        <td style = "width: 45.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>NR. CRT</p>
                        </td>

                        <td style = "width: 396.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>Denumire echipament</p>
                        </td>

                        <td style = "width: 60.3pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>U.M.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style = "width: 45.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>1</p>
                        </td>

                        <td style = "width: 396.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caDen1Value.'</p>
                        </td>

                        <td style = "width: 60.3pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caUM1Value.'</p>
                        </td>
                    </tr>

                    <tr>
                        <td style = "width: 45.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>2</p>
                        </td>

                        <td style = "width: 396.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caDen2Value.'</p>
                        </td>

                        <td style = "width: 60.3pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caUM2Value.'</p>
                        </td>
                    </tr>

                    <tr>
                        <td style = "width: 45.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>3</p>
                        </td>

                        <td style = "width: 396.0pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caDen3Value.'</p>
                        </td>

                        <td style = "width: 60.3pt; border: solid windowtext 1.0pt; padding: 0cm 5.4pt 0cm 5.4pt;">
                            <p>'.$caUM3Value.'</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p style = "text-align: center;">
                <strong>Suport Tehnic:&nbsp;</strong> – 0241 700 000 intre orele 08:00 – 20:00, 07INTERNET – NONSTOP, E-MAIL – &nbsp;<strong>client@07internet.ro:&nbsp;</strong> – NONSTOP.
            </p>

            <h3 style = "text-align: center;">URBAN NETWORK SOLUTIONS S.R.L.</h3>
            <h3 style = "text-align: center;">ANEXA C.3 la Contract de prestari servicii internet nr.:&nbsp;<strong>'.$clientId.'&nbsp;</strong>.</h1>
            <h1>Informare cu privire la prelucrarea datelor cu caracter personal</h1>
            <p>
                Prelucram date cu caracter personal atunci cand folositi serviciile 07INTERNET, iar modul in care facem acestlucru este prevazut in aceasta Informare.
                <br>
                Prezenta Informare se va completa cu prevederile specifice din cadrul Termenilor si Conditiilor aplicabili fiecarui serviciu la care v-ati abonat / va veti abona.
            </p>
            
            <h2>Tipurile de date pe care le prelucram</h2>
            <p>
                <strong>Date de contact:&nbsp;</strong>nume, data nasterii, numar si serie carte de identitate sau pasaport, cod numeric personal, domiciliu, numar de telefon, adresa de e-mail,
                sex, limba, adresa de instalare.
                <br>
                <strong>Date cont de client:&nbsp;</strong>ID client, nume de utilizator, parola si intrebari de autentificare, metoda de plata, cont bancar, numar card bancar (in functie de metoda
                de plata aleasa), adresa de facturare, comportamentul de plata si status-ul platii, abonamente si produse achizitionate prin intermediul oricaruia dintre
                canalele de vanzare disponibile in cadrul companiei 07INTERNET, campanii/oferte/optiuni accesate, precum si detalii cu privire la tranzactiile efectuate.
                Acestea includ si detalii despre interactiunile desfasurate cu departamentul de Relatii cu clientii: cererile dumneavoastra de asistenta, canalul prin
                care au fost adresate, inregistrarile incidentelor si detaliile necesare pentru a le solutiona, plangerile si solicitarile formulate. In cazul in care interactiunea
                se desfasoara telefonic: ora, durata apelului, solutia gasita si inregistrarea convorbirii telefonice.
                <br>
                <strong>Date de telefonie:&nbsp;</strong>numarul de telefon, datele de voce necesare efectuarii unui apel telefonic si/sau facturarii contravalorii acestuia, inclusiv numerele de
                telefon pe care le apelati si de la care primiti apeluri, ora, durata si numarul total de apeluri, tara.
                <br>                
                <strong>Date de internet:&nbsp;</strong>datele de trafic necesare transmiterii comunicatiei in internet, dimensiunea volumului de date utilizat, durata sesiunii, detaliile referitoare la
                dispozitivului dumneavoastra personal, adresa IP dinamica/MAC, localizarea retelelor Wi-Fi accesate. Folosim adrese IP dinamice care se pot modifica la o
                anumita perioada de timp in functie de starea fizica a modemului. Nu pastram un istoric al adreselor IP dinamice folosite de dumneavoastra. Nu urmarim
                continutul navigarii dumneavoastra pe internet.
                <br>
                <strong>Date TV:&nbsp;</strong>In functie de modelul mediabox-ului, putem retine informatii despre modul in care folositi serviciile 07INTERNET si functionalitatile acestuia,
                informatii tehnice (modelul si ID-ul mediabox-ului, versiunea de software utilizata, ID-ul smartcard-ului), informatii despre calitatea conexiunii,
                informatii despre programele pe care le vizionati.
                <br>
                <strong>Date pentru serviciul de e-mail:&nbsp;</strong>daca folositi serviciile 07INTERNET de e-mail, putem colecta informatiile necesare in vederea transmiterii mesajelor,
                cum ar fi adresa de e-mail a destinatarului.
                <br>
                <strong>Date din aplicatiile 07INTERNET:&nbsp;</strong>pastram o evidenta a utilizarii aplicatiilor pe care le accesati in meniul TV si a frecventei utilizarii acestora.
                <br>
                <strong>Date pentru registrele publice ale abonatilor:&nbsp;</strong>numele, adresa si numarul dumneavoastra de telefon
            </p>

            <h2>Cum folosim datele dumneavoastra</h2>
            <h3>1. Pentru a va furniza serviciile contractate</h3>
            <p>
                In vederea furnizarii serviciilor 07INTERNET, realizam o serie de activitati ce implica prelucrarea datelor dumneavoastra personale:
                <br>   
                -	activitati ce tin direct de modul in care se desfasoara relatia contractuala pe care o avem cu dumneavoastra, inclusiv activitati ce tin de instalarea
                si activarea serviciilor, verificarea disponibilitatii serviciilor, precum si asigurarea asistentei tehnice pe toata durata prestarii serviciilor;
                <br>
                -	activitati desfasurate in vederea prevenirii fraudei, cum ar fi stabilirea gradului de risc de neplata pe care il are zona de abonare, verificarea istoricului
                de plata al clientului in relatia cu 07INTERNET, monitorizarea costurilor suplimentare determinate de utilizarea serviciilor de telefonie. Puteti solicita
                evaluarea oricarei astfel de prelucrari de catre persoane din cadrul 07INTERNET, in functie de situatia dumneavoastra;
                <br>
            </p>
            <p>
                -	activitati ce tin de facturarea si incasarea contravalorii serviciilor, inclusiv trimiterea de comunicari cu privire la obligatiile dumneavoastra de plata,
                personalizate in functie de comportamentul dumneavoastra de plata din ultimele 6 luni;  - transmiterea de comunicari referitoare la serviciile la care v-ati abonat;
                <br>
                -	activitati ce tin de administrarea si planificarea capacitatii retelelor de comunicatii electronice, de protectia securitatii infrastructurii (ex. protectia
                impotriva atacurilor cibernetice, soft-urilor malware, activitati pentru care colectam informatii privind model hardware/versiunea sistemului de operare
                a dispozitivului dumneavoastra) precum si cu privire la imbunatatirea calitatii acestora. In cazul serviciului de internet, analizam datele de trafic in
                vederea identificarii unor modele atipice de trafic, care pot afecta securitatea retelei 07INTERNET si implicit a serviciilor pe care vi le oferim.
                <br>
                -	administrarea platformei 07INTERNET si a contului dumneavoastra de client.
            </p>

            <ul>
                <li><h3>Ce date folosim pentru furnizarea serviciilor?</h3></li>
                Ori de cate ori va furnizam serviciile 07INTERNET, folosim:
                <br>   
                -	datele dumneavoastra de contact,
                <br>
                -	datele cu privire la contul dumneavoastra de client,
                <br>
                -	datele de trafic necesare transmiterii comunicatiei in retelele de comunicatii electronice (servicii de telefonie si internet) sau in scopul facturarii
                contravalorii acestei operatiuni (servicii de telefonie), iar in cazul serviciului de internet, informatii tehnice despre dispozitivul dumneavoastra personal;
                <br>
                -	date in legatura cu serviciul de e-mail, atunci cand furnizam acest serviciu.
                <br>
                Comunicatiile dumneavoastra transmise prin intermediul retelei si a serviciilor de comunicatii electronice sunt confidentiale. Astfel, in vederea furnizarii serviciilor de internet si telefonie achizitionate,
                prelucram doar datele necesare transmiterii comunicarii sau in scopul facturarii acestei operatiuni. Nu accesam continutul convorbirilor dumneavoastra telefonice, continutul e-mail-urilor, nu urmarim navigarea
                dumneavoastra pe internet sau navigarea in aplicatiile dumneavoastra. Convorbirile pe care le aveti cu departamentele de Relatii cu clientii/Service Desk sunt inregistrate pe baza acordului dumneavoastra,
                dat la momentul convorbirii si pot fi ulterior accesate pentru a face dovada solicitarilor sau a acordurilor dumneavoastra.
            </ul>
            
            <ul>
                <li><h3>Cat timp pastram datele dumneavoastra?</h3></li>
                Prelucram o categorie larga de date cu caracter personal in vederea incheierii si executarii contractului, iar durata de pastrare pentru fiecare tip de date
                difera in functie de necesitatea sau de obligatia pe care o avem, astfel:
                <br>

                    <strong>
                        a. 	<u>Furnizarea serviciilor de comunicatii electronice si prevenirea incidentelor de securitate</u>
                    </strong>
                    <br>
                    Datele tehnice colectate in vederea furnizarii serviciilor de comunicatii electronice achizitionate sunt pastrate atata timp cat sunt necesare in vederea furnizarii serviciilor respective.
                    <br>
                    Pastram datele de trafic necesare transmiterii comunicatiei in internet, inclusiv localizarea retelelor Wi-Fi accesate, timp de o luna pentru a ne ajuta sa
                    identificam si sa prevenim posibile incidente de securitate asupra retelei care va pot afecta inclusiv pe dumneavoastra.
                    <br>

                    <strong>
                        b. <u>Relatia contractuala si incasarea contravalorii serviciilor</u>
                    </strong>
                    <br>
                    Nu pastram informatii despre verificarile pe care le facem la incheierea contractului (verificarea gradului de risc de neplata pe care il are zona de abonare
                    si verificarea istoricului de client) ulterior stabilirii conditiilor suplimentare ce trebuie indeplinite pentru incheierea acestuia.
                    <br>
                    Pentru a putea face dovada relatiei comerciale pe care o avem cu dumneavoastra, pastram datele cu caracter personal prelucrate in vederea executarii contractului pe toata durata de derulare a acestuia,
                    si aditional pentru o perioada de trei ani de la data incetarii relatiei contractuale. In acest sens, pastram contractul, documentele emise in legatura cu incheierea si executarea contractului, precum si
                    informatii despre situatia platilor la data incetarii contractului (de ex. incetare cu stergere de datorie). Daca, intr-o perioada de trei ani de la data incetarii contractului, doriti sa redeveniti
                    clientul 07INTERNET, vom verifica situatia platilor mentionata mai sus, pentru a vedea gradul dumneavoastra de bonitate. In cazul in care, la incetarea relatiei contractuale pe care o avem cu dumneavoastra,
                    vor ramane debite de recuperat, vom pastra informatiile care sunt necesare recuperarii lor pana in momentul colectarii platilor aferente sau pana la expirarea termenului de prescriptie, oricare dintre acestea intervine primul.
                    <br>
                    De asemenea, din ratiuni fiscale, suntem obligati sa pastram toate informatiile aferente facturarii serviciilor furnizate pentru o perioada de 10 ani de la incetarea relatiei contractuale.
            </ul>

            <ul>
                <li><h3>Dezvaluim datele dumneavoastra?</h3></li>
                In functie de activitatea la care ne raportam, dezvaluim datele dumneavoastra altor companii din cadrul grupului 07INTERNET, respectiv companiei noastra mama, filialelor sale sau altor companii aflate sub control
                comun („Afiliatii”), pentru efectuarea statisticilor si a analizelor, asigurarea functionarii serviciilor, cat si unor parteneri contractuali/subcontractori ai nostri („Subcontractorii”), pentru instalarea
                echipamentelor la adresa Clientului, remedierea defectiunilor tehnice, relatia cu clientii, activitati de call-center, activitati de procesare a platilor, activitati de tipografie, distributie/curierat. Intr-o astfel
                de situatie, ne asiguram ca Afiliatii si Subcontractorii respecta prezenta Informare.
                <br>
                In situatia existentei unei datorii cu privire la relatia contractuala pe care o avem cu dumneavoastra, vom dezvalui datele dumneavoastra unor agenti de colectare debite/recuperare creante in vederea recuperarii
                datoriei. Acestia va vor contacta in numele nostru. De asemenea, atunci cand cesionam creanta, va vom informa despre asta.
            </ul>

            <ul>
                <li><h3>Baza legala pentru prelucrarea acestor date</h3></li>
                Incheierea si executarea contractului. In subsidiar, in baza interesului nostru legitim de a incasa complet si la timp contravaloarea serviciilor pe care vi le furnizam, precum si pentru a evalua gradul de
                risc de neplata al unei anumite zone, vom prelucra datele dumneavoastra cu caracter personal pentru: (i) a personaliza comunicarile privind sumele de plata in functie de comportamentul dumneavoastra de plata,
                (ii) a monitoriza costurile suplimentare ale serviciului de telefonie si (iii) a verifica gradul de risc de neplata al unei zone de abonare, cat si al unui client nou, in baza istoricului sau de plata in relatia
                cu 07INTERNET.
            </ul>

            <h3>2. Pentru marketing, publicitate si sondaj de satisfactie</h3>
            <p>
                Avem un interes legitim de a imbunatati experienta si calitatea serviciilor clientilor nostri, astfel incat sa beneficiati la maximum de produsele si de serviciile 07INTERNET. Utilizam datele dumneavoastra pentru
                dezvoltarea de noi produse sau servicii, imbunatatirea caracteristicilor si functionalitatilor viitoare ale acestora sau pentru crearea de oferte care sa raspunda nevoilor dumneavoastra.
                <br>
                Va vom contacta pentru a va prezenta noile noastre produse si servicii si pentru a lua parte la sondajele noastre de satisfactie. Cu cat va cunoastem mai bine nevoile, cu atat mai bine putem veni in intampinarea lor!               
                <br>
                Aveti la dispozitie mai multe canale pe care le puteti utiliza pentru a fi contactat (de exemplu: apel telefonic, curier, sms, apel telefonic desfasurat cu ajutorul unui robot). Prin contract,
                puteti alege oricare dintre aceste canale de comunicare. Daca nu alegeti specific unul dintre acestea, dar nu v-ati opus sa fiti contactat prin adresa de e-mail pe care ne-ati pus-o la dispozitie, vom folosi acest
                mod de comunicare cu dumneavoastra.
                <br>       
                De fiecare data cand va vom contacta, indiferent de canalul ales, veti putea, intr-un mod cat mai simplu sa alegeti sa nu mai primiti deloc comunicari comerciale din partea 07INTERNET.
                <br>            
                Totusi, chiar si in situatia in care optati pentru incetarea comunicarilor in scop de marketing, publicitate si/sau sondaj de satisfactie, veti primi in continuare comunicari generale din partea 07INTERNET
                referitoare la serviciile pe care le furnizam (de ex. modificari contractuale, detalii cu privire la functionalitatea serviciilor, etc.), comunicari care sunt necesare desfasurarii contractului.
                
                <ul>
                    <li><h3>Ce date folosim pentru acest scop?</h3></li>
                    Pentru a intelege cum sa imbunatatim experienta dumneavoastra la 07INTERNET, analizam datele dumneavoastra de contact si datele contului de client, acordand o atentie sporita abonamentelor si parerilor
                    dumneavoastra referitoare la produsele si serviciile 07INTERNET. De asemenea, convorbirile telefonice pe care le aveti cu departamentele companiei vor fi inregistrate numai cu acordul dumneavoastra, oferit la momentul convorbirii.
                </ul
                
                <ul>
                    <li><h3>Cat timp pastram datele dumneavoastra?</h3></li>
                    Datele dumneavoastra sunt pastrate pe durata contractului.
                </ul>
                
                <ul>
                    <li><h3>Dezvaluim datele dumneavoastra?</h3></li>
                    In functie de activitatea la care ne raportam, dezvaluim datele dumneavoastra altor companii din cadrul grupului 07INTERNET, respectiv companiei noastra mama, filialelor ei sau altor companii aflate sub control
                    comun („Afiliatii”), cat si unor parteneri contractuali/subcontractori ai nostri („Subcontractorii”) pentru desfasurarea activitatilor de marketing, publicitate si sondaj de satisfactie, cum ar fi:
                    agentii de marketing, societati ce desfasoara activitati de tipografie, distributie/curierat, societati ce desfasoara activitati de sondaj sau de call center. Intr-o astfel de situatie, ne asiguram ca
                    Afiliatii si Subcontractorii respecta prezenta Informare.
                    <br>
                    In situatia existentei unei datorii cu privire la relatia contractuala pe care o avem cu dumneavoastra, vom dezvalui datele dumneavoastra unor agenti de colectare debite/recuperare creante in vederea
                    recuperarii datoriei. Acestia va vor contacta in numele nostru. De asemenea, atunci cand cesionam creanta, va vom informa despre asta.
                </ul>

                <ul>
                    <li><h3>Baza legala pentru prelucrarea acestor date</h3></li>
                    Interesul legitim pentru prelucrari in scopuri de marketing, publicitate si sondaj de satisfactie si trimiterea de comunicari prin e-mail si consimtamant pentru comunicari trimise pe alte canale.
                </ul>
            </p>

            <h3>3. Pentru masurarea audientei</h3>
            <p>
                Daca folositi un mediabox Horizon, accesam, transformam automat informatiile in date cu caracter anonim si agregam datele privind comportamentul TV al clientilor 07INTERNET
                (de exemplu, posturile si programele urmarite, ora la care ati vizionat continutul si durata de vizionare), precum si utilizarea aplicatiilor din mediabox, pentru a genera un raport sumar de audienta.
                Facem acest lucru si cand accesati canalele TV in mediul online, prin platforma Horizon Go. Acest raport ne ajuta sa optimizam serviciile, grila de programe si varietatea de aplicatii disponibile,
                si in anumite situatii, sta la baza organizarii serviciilor altor entitati partenere, cum ar fi cei care difuzeaza continut TV sau continut publicitar.

                <ul>
                    <li><h3>Ce date folosim pentru a realiza aceste rapoarte combinate?</h3></li>
                    Datele TV, datele din aplicatiile 07INTERNET.
                </ul>

                <ul>
                    <li><h3>Cat timp pastram datele dumneavoastra?</h3></li>
                    Nu pastram datele dumneavoastra privinc comportamentul TV. Rapoartele de audienta se pastreaza cel mult 24 de luni.
                </ul>
                
                <ul>
                    <li></h3>Dezvaluim datele dumneavoastra?</h3></li>
                    Dezvaluim rapoartele de audienta companiilor din cadrul grupului 07INTERNET, companiei mama, filialelor ei sau altor companii aflate sub control comun, sau altor terti
                    (furnizorii de servicii de continut TV sau continut publicitar). Datele din cadrul rapoartelor nu pot duce la identificarea dumneavoastra.
                </ul>

                <ul>
                    <li><h3>Baza legala pentru prelucrarea acestor date</h3></li>
                    Legislatia cu privire la prelucrarea datelor personale permite prelucrarea datelor cu caracter anonim si agregate, acestea nefiind considerate date cu caracter personal.
                </ul>
            </p>

            <h3>4. Pentru recomandari TV personalizate</h4>
            <p>
                Daca aveti unul dintre ultimele modele de mediabox Horizon oferite de catre 07INTERNET, vom activa o noua functionalitate ce ne va permite sa va furnizam recomandari personalizate de televiziune:
                sa va amintim de show-urile preferate, sa va recomandam emisiuni/programe noi, adaptate gusturilor dumneavoastra, iar in baza istoricului dumneavoastra de vizionare, sa va sugeram cele mai vizionate canale.
                Vom face asta daca avem consimtamantul dumneavoastra (pe care vi-l puteti exprima prin intermediul mediabox-ului si, de asemenea, vi-l puteti retrage oricand). Detalii despre aceasta functionalitate vor fi
                disponibile in meniul mediabox-ului dumneavoastra. In aceasta activitate, nu analizam si nu luam in calcul vizionarea canalelor/emisiunilor dedicate entertainment-ului pentru adulti.

                <ul>
                    <li><h3>Ce date folosim?</h3></li>
                    Datele de contact, datele contului de client, datele TV.
                </ul>

                <ul>
                    <li><h3>Cat timp pastram datele dumneavoastra?</h3></li>
                    Recomandarile personalizate sunt bazate pe datele TV colectate in ultimele 3 luni de vizionare.
                </ul>

                <ul>
                    <li><h3>Dezvaluim datele dumneavoastra?</h3></li>
                    Pentru a analiza datele privind comportamentul si preferintele dumneavoastra TV si pentru a realiza rapoarte combinate si pseudonimizate pe care sa le furnizam altor entitati partenere,
                    dezvaluim datele dumneavoastra catre companiile din cadrul grupului 07INTERNET, companiei noastra mama, filialelor ei sau altor companii aflate sub control comun („Afiliatii”).
                    Facem aceasta dezvaluire pentru a imbunatati furnizarea serviciilor si functionarea platformei Horizon. Intr-o astfel de situatie, ne asiguram ca Afiliatii respecta prezenta Informare.
                    Datele pseudonimizate sunt acele date care nu pot fi atribuite unui titular fara a folosi/verifica informatii suplimentare, stocate separat.
                </ul>

                <ul>
                    <li><h3>Baza legala pentru prelucrarea acestor date</h3></li>
                    Consimtamantul dumneavoastra.
                </ul>
            </p>

            <h3>5. Pentru scopuri statistice si de analiza</h3>
            <p>
                Pentru a optimiza serviciile 07INTERNET si pentru a veni in intampinarea nevoilor clientilor 07INTERNET, extragem, transformam in date cu caracter anonim, combinam si agregam datele dumneavoastra
                cu cele ale altor clienti, pentru a avea o imagine statistica a bazei de clienti 07INTERNET. Parte din politica companiei 07INTERNET presupune analiza datelor agregate pe care le avem,
                in vederea dezvoltarii / optimizarii serviciilor si platformelor / produselor 07INTERNET in general, si nu pentru a lua actiuni privind o anume persoana.

                <ul>
                    <li><h3>Ce date folosim pentru a realiza aceste rapoarte?</h3></li>
                    Datele de contact, datele contului de client.
                </ul>

                <ul>
                    <li><h3>Cat timp pastram datele dumneavoastra?</h3></li>
                    Vom pastra rapoartele statistice pe durata necesara utilizarii lor.
                </ul>

                <ul>
                    <li><h3>Dezvaluim datele dumneavoastra?</h3></li>
                    Rezultatele rapoartelor statistice (care nu permit identificarea datelor dumneavoastra sau individualizarea dumneavoastra) si analizele bazate pe  aceste rapoarte pot fi dezvaluite companiei noastra mama
                    (07INTERNET), pentru evidentierea activitatii locale a companiei 07INTERNET, cat si pentru stabilirea unor directii de dezvoltare comerciale. Intr-o astfel de situatie, ne asiguram ca aceasta respecta
                    prezenta Informare.
                </ul>

                <ul>
                    <li><h3>Baza legala pentru prelucrarea acestor date</h3></li>
                    Scop de prelucrare compatibil cu scopul initial al prelucrarii datelor cu caracter personal.
                </ul>
            </p>

            <h3>6. Pentru a ne indeplini obligatiile legale</h3>
            <p>
                Avem obligatia ca, la solicitarea autoritatilor publice, intocmita in conformitate cu prevederile legale aplicabile, sa comunicam catre acestea datele dumneavoastra. In cazul in care primim o solicitare de
                informatii de la o autoritate publica, spre exemplu, in cadrul unei investigatii cu privire la piata pe care activam, vom accesa si trimite datele dumneavoastra catre aceasta.
                <br>
                De asemenea, in calitate de abonat al serviciilor de comunicatii electronice, aveti dreptul ca datele dumneavoastra cu caracter personal sa fie incluse in toate registrele publice ale abonatilor
                (in forma scrisa sau electronica). In acest sens, in cazul in care nu v-ati exprimat dezacordul in 45 de zile de la data semnarii contractului, v-am introdus numele, adresa si numarul dumneavoastra de telefon
                in registrul abonatilor tinut de 07INTERNET. Conform legislatiei in vigoare, in situatia in care aceste informatii vor fi solicitate de catre persoane care pun la dispozitie registre ale abonatilor
                care furnizeaza servicii de informatii privind abonatii, veti fi notificat de catre acestia. Compania 07INTERNET este obligata sa dezvaluie aceste date daca in termen de 45 de zile de la notificare
                nu v-ati exprimat dezacordul cu privire la dezvaluire.
            

                <ul>
                    <li><h3>Ce date trebuie sa pastram?</h3></li>
                    Datele de contact, datele contului de client, datele de telefonie, datele de internet si date pentru registrele abonatilor.
                </ul>

                <ul>
                    <li><h3>Cat timp pastram datele dumneavoastra?</h3></li>
                    Va vom pastra datele pe toata durata necesara folosirii acestora, astfel cum este indicat de catre autoritatea competenta si in conformitate cu prevederile legislatiei aplicabile in vigoare.
                </ul>

                <ul>
                    <li><h3>Baza legala pentru prelucrarea acestor date</h3></li>
                    Obligatie legala.
                </ul>
            </p>

            <h1>Detineti controlul asupra datelor dumneavoastra</h1>
            <p>
                Puteti controla modul in care prelucram datele dumneavoastra cu caracter personal, exercitand oricare dintre urmatoarele drepturi/optiuni, oricand doriti.
                <br>
                <strong>Accesarea datelor:&nbsp;</strong> puteti solicita o copie a datelor cu caracter personal pe care le detinem despre dumneavoastra.
                <br>
                <strong>Rectificarea datelor:&nbsp;</strong> daca datele pe care le detinem despre dumneavoastra sunt inexacte sau incomplete, puteti solicita rectificarea lor.
                <br>
                <strong>Opozitia la prelucrarea datelor:&nbsp;</strong> ne puteti solicita sa incetam anumite activitati de prelucrare. In functie de baza legala a prelucrarii, vom analiza cererea, situatia de fapt, precum si prevederile legale
                aplicabile si vom reveni in termenul legal cu detalii despre implementarea solicitarii.
                <br>
                In cazul in care veti alege sa nu va mai prelucram datele cu caracter personal in scopuri de marketing, publicitate si sondaj de satisfactie si nici sa mai primiti vreo comunicare comerciala in
                legatura cu aceste activitati, indiferent de canalul ales ca mijloc de comunicare, vom opri orice astfel de prelucrare. In cazul canalelor pentru care v-ati dat expres consimtamantul,
                vom echivala exercitarea dreptului dumneavoastra la opozitie cu o retragere de consimtamant.
                <br>
                <strong>Stergerea datelor:&nbsp;</strong> Ne puteti cere sa stergem datele cu caracter personal pe care le detinem despre dumneavoastra. Vom analiza cererea in conformitate cu motivele ce justifica solicitarea si
                vom reveni in termenul legal cu detalii despre implementarea ei. Ca abordare generala, la data expirarii perioadelor de timp de pastrare a detelor, astfel cum sunt identificate mai sus,
                datele dumneavoastra vor fi fie sterse, fie anonimizate.
                <br>
                <strong>Portabilitatea datelor:&nbsp;</strong> In situatia in care doriti sa va transferati datele catre un alt furnizor, ne puteti solicita sa vi le comunicam intr-un format electronic uzual (acesta este un drept nou conform Regulamentului).
            </p>
            <p>
                <strong>Restrictionarea prelucrarii:&nbsp;</strong> puteti sa solicitati restrictionarea prelucrarii. Vom analiza cererea in raport de cazurile de restrictionare prevazute de lege si vom revenim in termenul legal cu
                detalii despre implementarea ei (acesta este un drept nou conform Regulamentului).
                <br>
                <strong>Retragerea consimtamantului:&nbsp;</strong> in cazul in care v-ati dat consimtamantul, in mod expres, pentru o prelucrare de date, il veti putea retrage oricand. Aceasta retragere se va inregistra in
                sistemele 07INTERNET fara intarzieri nejustificate.
            </p>

            <br>

            <h1>Suntem aici pentru dumneavoastra</h1>
            <p>
                Compania care va prelucreaza datele este URBAN NETWORK SOLUTIONS S.R.L. denumita si 07INTERNET.
                <br>
                <strong>Responsabilul cu protectia datelor (DPO):&nbsp;</strong> Puteti adresa orice intrebare, comentariu sau orice solicitare privitoare la datele dumneavoastra, Responsabilului 07INTERNET cu Protectia Datelor la adresa de
                posta electronica: dpo@07internet.ro, la adresa companiei (Str Midiei, Nr 6, Navodari, Constanta, 905700) sau printr-o cerere scrisa in magazinele 07INTERNET. Reteaua de magazine este disponibila
                pe website-ul oficial al companiei: www.07internet.ro.
                <br>
                <strong>Autoritatea de Supraveghere:&nbsp;</strong> De asemenea, puteti sa inaintati o plangere in fata Autoritatii Nationale de Supraveghere a Prelucrarii Datelor cu Caracter Personal (http://www.dataprotection.ro/)
            </p>

            <br>

            <h1>Actualizari viitoare</h1>
            <p>
                Continutul acestei Informari poate suferi modificari ca urmare a evolutiei pietei sau actualizarii gamei de servicii pe care le prestam. Vom publica orice noua versiune a acestei Informari pe
                website-ul 07INTERNET si va vom anunta in avans, in timp util, cu privire  la orice schimbare ce ar putea afecta serviciile la care v-ati abonat.
            </p>
        </div>
    </body>
    ';

    // Mobile Detect
    $detect = new Mobile_Detect;
    $PDF -> loadHtml($HTML);

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