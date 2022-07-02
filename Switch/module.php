<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ColorHelper.php';
require_once __DIR__ . '/../libs/MQTTHelper.php';
require_once __DIR__ . '/../libs/VariableProfileHelper.php';
require_once __DIR__ . '/../libs/CommandHelper.php';

class ZWavejs2MQTTSwitch extends IPSModule
{
    use ColorHelper;
    use MQTTHelper;
    use VariableProfileHelper;
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

        $this->RegisterVariableBoolean('ZW2M_State', $this->Translate('State'), '~Switch', 0);
        $this->EnableAction("ZW2M_State");

        $this->SendDebug('Associations',  $this->ReadPropertyString('Associations'), 0);

    }

    public function ReceiveData($json)
    {
        $this->SendDebug('JSON', $json, 0);
        if (!empty($this->ReadPropertyString('MQTTTopic'))) {
            $buffer = json_decode($json);
            // buffer decodieren und in eine Variable schreiben
            $this->SendDebug('MQTT Topic', $buffer->Topic, 0);
            $this->SendDebug('MQTT Payload', $buffer->Payload, 0);
            if(fnmatch('*/currentValue', $buffer->Topic)){
                $state = $buffer->Payload;
                SetValue($this->GetIDForIdent('ZW2M_State'), $state);
            }
        }
    }

    public function RequestAction($ident, $value)
    {
        switch($ident) {
            case 'ZW2M_State': 
                $this->SwitchMode($value);
                break;
        }          
    }    

    private function notifyAssociations($newValue) {
        $associations = json_decode($this->ReadPropertyString('Associations'));
        if (is_array($associations) || is_object($associations)) {
            foreach($associations as $association) {
                $assVar = IPS_GetObjectIDByIdent('ZW2M_State', $association->InstanceID);
                if($assVar !== false) RequestAction($assVar, $newValue);
            }
        }
    }

}
