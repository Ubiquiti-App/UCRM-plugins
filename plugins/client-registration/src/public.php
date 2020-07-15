<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Nette\Forms\Form;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;

$ucrmApi = UcrmApi::create();

$servicePlans = $ucrmApi->get('service-plans');

$servicePlanItems = [];
$servicePlanDetails = [];
foreach ($servicePlans as $servicePlan) {
    if (! $servicePlan['public']) {
        continue;
    }

    foreach ($servicePlan['periods'] as $period) {
        if ($period['enabled']) {
            $servicePlanItems[$servicePlan['id']] = $servicePlan['name'];
            $servicePlanDetails[$servicePlan['id']] = [
                'servicePlanPeriodId' => $period['id'],
                'setupFeePrice' => $servicePlan['setupFee'],
                'earlyTerminationFeePrice' => $servicePlan['earlyTerminationFee'],
                'minimumContractLengthMonths' => $servicePlan['minimumContractLengthMonths'],
            ];
            break;
        }
    }
}

$form = new Form();

$form->addText('firstName', 'First name')->setRequired();
$form->addText('lastName', 'Last name')->setRequired();
$form->addEmail('email', 'Email')->setRequired();
$form->addText('phone', 'Phone');
$form->addText('address', 'Address');

if ($servicePlanItems !== []) {
    $form->addSelect(
        'servicePlan',
        'Service Plan',
        $servicePlanItems,
    );
}

$form->addSubmit('send', 'Register');

if ($form->isSuccess()) {
    echo 'Form was filled and submitted successfully.';

    $values = $form->getValues();

    $client = $ucrmApi->post(
        'clients',
        [
            'isLead' => true,
            'firstName' => $values['firstName'],
            'lastName' => $values['lastName'],
            'fullAddress' => $values['address'],
            'contacts' => [
                [
                    'email' => $values['email'],
                    'phone' => $values['phone'],
                ],
            ],
        ]
    );

    if ($values['servicePlan'] !== null) {
        $ucrmApi->post(
            'clients/services',
            array_merge(
                $servicePlanDetails[$values['servicePlan']],
                [
                    'clientId' => $client['id'],
                    'servicePlanId' => $values['servicePlan'],
                    'isQuoted' => true,
                ]
            )
        );
    }
} else {
    echo $form;
}

