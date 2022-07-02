<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ColorHelper.php';
require_once __DIR__ . '/../libs/MQTTHelper.php';
require_once __DIR__ . '/../libs/VariableProfileHelper.php';
require_once __DIR__ . '/../libs/CommandHelper.php';

class Zwavejs2MqttDimmer extends IPSModule
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
        $this->RegisterVariableInteger('ZW2M_Brightness', $this->Translate('Brightness'), '~Intensity.100', 1);
        $this->EnableAction("ZW2M_Brightness");

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
                $intensity = $buffer->Payload;
                SetValue($this->GetIDForIdent('ZW2M_State'), $intensity > 0);
                SetValue($this->GetIDForIdent('ZW2M_Brightness'), $intensity);
            }
        }
    }

    public function RequestAction($ident, $value)
    {
        switch($ident) {
            case 'ZW2M_State': 
                $this->SwitchMode($value);
                break;
            case 'ZW2M_Brightness':
                $this->DimSet($value);
                break;
        }          
    }    

    private function notifyAssociations($newValue) {
        $associations = json_decode($this->ReadPropertyString('Associations'));
        if (is_array($associations) || is_object($associations)) {
            foreach($associations as $association) {
                $assVar = IPS_GetObjectIDByIdent('ZW2M_Brightness', $association->InstanceID);
                if($assVar !== false) RequestAction($assVar, $newValue);
            }
        }
    }

}
