<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/MQTTHelper.php';
require_once __DIR__ . '/../libs/LightHelper.php';

class Zwavejs2MqttLight extends IPSModule {
    use MQTTHelper;
    use LightHelper;

    public function Create() {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        $this->RegisterPropertyInteger('nodeId', '0');
        $this->RegisterPropertyInteger('endpoint', '0');
        $this->RegisterPropertyString('name', '');
        $this->RegisterPropertyString('location', '');
        $this->RegisterPropertyString('MQTTBaseTopic', '');
        $this->RegisterPropertyBoolean('switch', true);
        $this->RegisterPropertyBoolean('dimmer', false);
        $this->RegisterPropertyString('manufacturer', '');
        $this->RegisterPropertyString('productLabel', '');
        $this->RegisterPropertyString("Associations", '[]');
        // $this->createVariableProfiles();
    }

    public function ApplyChanges() {
        //Never delete this line!
        parent::ApplyChanges();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
        //Setze Filter für ReceiveData
        $nodeId = $this->ReadPropertyInteger('nodeId');        
        $filter = '.*'. $this->topic('.*') . '.*';
        $this->SetReceiveDataFilter($filter);
        $this->initVariables();
    }

}
