<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Nette\Forms\Form;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;

$ucrmApi = UcrmApi::create();

$servicePlans = array_filter(
    $ucrmApi->get('service-plans'),
    function (array $servicePlan): bool {
        return $servicePlan['public'];
    }
);

$servicePlanItems = [];
foreach ($servicePlans as $servicePlan) {
    $servicePlanItems[$servicePlan['id']] = $servicePlan['name'];
}

$form = new Form();

$form->addText('firstName', 'First name')->setRequired();
$form->addText('lastName', 'Last name')->setRequired();
$form->addEmail('email', 'Email')->setRequired();
$form->addText('phone', 'Phone');
$form->addText('address', 'Address');

if ($servicePlanItems !== []) {
    $form->addSelect(
        'tariff',
        'Service Plan',
        $servicePlanItems,
    );
}

$form->addSubmit('send', 'Register');

if ($form->isSuccess()) {
    echo 'Form was filled and submitted successfully.';

    $values = $form->getValues();

    $ucrmApi->post(
        'clients',
        [
            'isLead' => true,
            'firstName' => $values['firstName'],
            'lastName' => $values['lastName'],
            'fullAddress' => $values['address'],
            'contacts' => [
                'email' => $values['email'],
                'phone' => $values['phone'],
            ]
        ]
    );
} else {
    echo $form;
}
