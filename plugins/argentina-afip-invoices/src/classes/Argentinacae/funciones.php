<?php

# Autor: FGAMPEL

use Ubnt\UcrmPluginSdk\Service\UcrmApi;

function verifyCustomAttributes($customattributesarray, $attributename, $attributerealname, $typeofattribute, $clientZoneVisible): void
{
    // Retrieve API connection.
    $api2 = UcrmApi::create();
    if (array_search($attributerealname, array_column($customattributesarray, 'key')) === false) {
        if (DEBUG) {
            echo '<br>' . $attributerealname . ' custom attribute not found <br>';
        }
        $api2->post(
            'custom-attributes',
            [
                'name' => $attributename,
                'attributeType' => $typeofattribute,
                'clientZoneVisible' => $clientZoneVisible,
            ]
        );
        if (DEBUG) {
            echo $attributerealname . ' custom attribute created <br>';
        }
    } else {
        if (DEBUG) {
            echo $attributerealname . ' custom attribute found <br>';
        }
    }
}

function getCustomAttributesId($customattributesarray, $attributerealname): int
{
    if (! is_null(array_search($attributerealname, array_column($customattributesarray, 'key')))) {
        if (DEBUG) {
            echo '<br>' . $attributerealname . ' custom attribute found <br>';
        }
        //Search for key where $attributerealname is
        $attributeKey = array_search($attributerealname, array_column($customattributesarray, 'key'));
        // search for Id Value
        $AttributeId = $customattributesarray[$attributeKey]['id'];
        return($AttributeId);
    }
    if (DEBUG) {
        echo $attributerealname . ' custom attribute NOT found <br>';
    }
}

function getCustomAttributeValue($customattributesarray, $attributerealname): ?string
{
    if (! (gettype(array_search($attributerealname, array_column($customattributesarray, 'key'))) == 'boolean' && array_search($attributerealname, array_column($customattributesarray, 'key')) == false)) {
        if (DEBUG) {
            echo '<br>' . $attributerealname . ' custom attribute found <br>';
        }
        //Search for key where $attributerealname is
        $attributeKey = array_search($attributerealname, array_column($customattributesarray, 'key'));
        // search for Id Value
        $AttributeValue = $customattributesarray[$attributeKey]['value'];
        $attributeValueString = (string) $AttributeValue;
        if (DEBUG) {
            echo 'valor en funcion getAttribute para requerimiento ' . $attributerealname . ' : ' . $attributeValueString . '<br>';
        }
        return($attributeValueString);
    }
    if (DEBUG) {
        echo $attributerealname . ' custom attribute NOT found <br>';
    }
    return(null);
}

function getInvoiceTemplateId($invoiceTemplateName): int
{
    $api3 = UcrmApi::create();
    $invoiceTemplates = $api3->get(
        'invoice-templates',
        [
        ]
    );
    if (! (gettype(array_search($invoiceTemplateName, array_column($invoiceTemplates, 'name'))) == 'boolean' && array_search($invoiceTemplateName, array_column($invoiceTemplates, 'name')) == false)) {
        if (DEBUG) {
            echo '<br>' . $invoiceTemplateName . ' custom attribute found <br>';
        }
        //Search for key where $invoiceTemplateName is
        $templateKey = array_search($invoiceTemplateName, array_column($invoiceTemplates, 'name'));
        // search for Id Value
        $nameValue = $invoiceTemplates[$templateKey]['id'];
        //$attributeValueString = (string)$nameValue;
        if (DEBUG) {
            echo 'valor en funcion getAttribute para requerimiento ' . $invoiceTemplateName . ' : ' . $nameValue . '<br>';
        }
        return($nameValue);
    }
    if (DEBUG) {
        echo $invoiceTemplateName . ' custom attribute NOT found <br>';
    }
    echo '<br><br><br>¡¡¡Atencion, Plantilla personalizada para las facturas AFIP no encontrada, asegurese de tenerla importada y correctamente configurada!!! <br><br><br>';
    return(1);
}

function getInvoiceConcept($invoiceItems): int
{
    $serviceItem = $productItem = 0;
    foreach ($invoiceItems as $item) {
        if ($productItem == 0) {
            if ($item['type'] == 'product') {
                $productItem = 1;
            }
        }
        if ($serviceItem == 0) {
            if ($item['type'] == 'service' || $item['type'] == 'fee' || $item['type'] == 'surcharge' || $item['type'] == 'other') {
                $serviceItem = 2;
            }
        }
    }
    return($serviceItem + $productItem);
}
