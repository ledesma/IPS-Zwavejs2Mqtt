<?php

declare(strict_types=1);
// require_once __DIR__ . '/../libs/ColorHelper.php';
require_once __DIR__ . '/../libs/MQTTHelper.php';
// require_once __DIR__ . '/../libs/VariableProfileHelper.php';
require_once __DIR__ . '/../libs/CommandHelper.php';

class Zwavejs2MqttDevice extends IPSModule
{
    // use ColorHelper;
    use MQTTHelper;
    // use VariableProfileHelper;
    use CommandHelper;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        $this->RegisterPropertyString('MQTTTopic', '');
        $this->RegisterPropertyString("Associations", '[]');
        // $this->createVariableProfiles();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
        //Setze Filter für ReceiveData
        $topic = $this->ReadPropertyString('MQTTTopic');
        $filter = '.*"zwave/' . $topic . '.*';
        $this->SetReceiveDataFilter($filter);

        $this->initVariables();
    }

}
