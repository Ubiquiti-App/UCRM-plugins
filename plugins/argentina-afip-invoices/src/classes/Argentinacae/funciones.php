<?php
# Autor: FGAMPEL

use Ubnt\UcrmPluginSdk\Service\UcrmApi;



function verifyCustomAttributes($customattributesarray,$attributename,$attributerealname,$typeofattribute):void {
// Retrieve API connection.
$api2 = UcrmApi::create();
if (array_search($attributerealname, array_column($customattributesarray, 'key')) === false){
	if(DEBUG) echo '<br>' . $attributerealname .' custom attribute not found <br>';
	$api2->post(
		'custom-attributes',
			[
			'name'  => $attributename,
			'attributeType'  => $typeofattribute,
			]
		);
	if(DEBUG) echo $attributerealname . ' custom attribute created <br>';
	} else {
	if(DEBUG) echo $attributerealname . ' custom attribute found <br>';
	}
}

function getCustomAttributesId($customattributesarray,$attributerealname):int {
if (!is_null (array_search($attributerealname, array_column($customattributesarray, 'key')))){
	if(DEBUG) echo '<br>' . $attributerealname .' custom attribute found <br>';
	//Search for key where $attributerealname is
	$attributeKey = array_search($attributerealname, array_column($customattributesarray, 'key'));
	// search for Id Value
	$AttributeId= $customattributesarray[$attributeKey]['id'];	
	return($AttributeId);
	} else {
	if(DEBUG) echo $attributerealname . ' custom attribute NOT found <br>';
	}
}

function getCustomAttributeValue($customattributesarray,$attributerealname):?string {
if (!(gettype(array_search($attributerealname, array_column($customattributesarray, 'key')))=='boolean' && array_search($attributerealname, array_column($customattributesarray, 'key')) == false)){

	if(DEBUG) echo '<br>' . $attributerealname .' custom attribute found <br>';
	//Search for key where $attributerealname is
	$attributeKey = array_search($attributerealname, array_column($customattributesarray, 'key'));
	// search for Id Value
	$AttributeValue= $customattributesarray[$attributeKey]['value'];	
	$attributeValueString = (string)$AttributeValue;
	if (DEBUG) echo 'valor en funcion getAttribute para requerimiento ' .$attributerealname . ' : ' . $attributeValueString . '<br>';
	return($attributeValueString);
	} else {
	if(DEBUG) echo $attributerealname . ' custom attribute NOT found <br>';
	return(null);
	}
}

?>
