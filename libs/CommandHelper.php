<?php

declare(strict_types=1);

trait CommandHelper
{

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

    public function ReceiveData($json)
    {
        $this->SendDebug('JSON', $json, 0);
        if (!empty($this->ReadPropertyString('MQTTTopic'))) {
            $buffer = json_decode($json);
            // buffer decodieren und in eine Variable schreiben
            $this->SendDebug('MQTT Topic', $buffer->Topic, 0);
            $this->SendDebug('MQTT Payload', $buffer->Payload, 0);
            if(fnmatch('*/currentValue', $buffer->Topic)){
                $value = $buffer->Payload;
                if($this->topicContains("switch_multilevel")) {
                    SetValue($this->GetIDForIdent('ZW2M_State'), $value > 0);
                    SetValue($this->GetIDForIdent('ZW2M_Brightness'), $value);
                }
                else if($this->topicContains("switch_binary")) {
                    SetValue($this->GetIDForIdent('ZW2M_State'), $value);
                }
            }
        }
    }

    public function SwitchMode(bool $state) {
        if($this->topicContains("switch_multilevel")) {
            $this->sendEvent($state ? 100 : 0);
        } 
        else if($this->topicContains("switch_binary")) {
            $this->sendEvent($state ? "on" : "off");
        }
        else {
            $this->LogMessage("Instanz kann nicht switchen", KL_WARNING);
        }
        $this->notifyAssociations(function($id, $val) {
            ZW2M_SwitchMode($id, $val);
        }, $state);
    }

    public function DimSet(int $value) {
        if($this->topicContains("switch_multilevel")) {
            $this->sendEvent($value);
        } 
        else {
            $this->LogMessage("Instanz kann nicht dimmen", KL_WARNING);
        }
        $this->notifyAssociations(function($id, $val) {
            ZW2M_DimSet($id, $val);
        }, $value);
    }

    private function topicContains($keyword) {
        $topic = $this->ReadPropertyString('MQTTTopic');
        return preg_match("/\b".$keyword."\b/", $topic);
    }

    private function sendEvent($newValue)
    {
        $Data['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Data['PacketType'] = 3;
        $Data['QualityOfService'] = 0;
        $Data['Retain'] = false;
        $Data['Topic'] =  'zwave/' . $this->ReadPropertyString('MQTTTopic') . '/targetValue/set';
        $Data['Payload'] = strval($newValue);
        $DataJSON = json_encode($Data, JSON_UNESCAPED_SLASHES);
        $this->SendDebug(__FUNCTION__ . ' Topic', $Data['Topic'], 0);
        $this->SendDebug(__FUNCTION__ . ' Payload', $Data['Payload'], 0);
        $this->SendDataToParent($DataJSON);
    }


    private function notifyAssociations(callable $callback, $newValue) {
        $associations = json_decode($this->ReadPropertyString('Associations'));
        if (is_array($associations) || is_object($associations)) {
            foreach($associations as $association) {
                $callback($association->InstanceID, $newValue);
            }
        }
    }

    private function initVariables() {
        if($this->topicContains("switch_multilevel")) {
            // DIMMER
            $this->RegisterVariableBoolean('ZW2M_State', $this->Translate('State'), '~Switch', 0);
            $this->EnableAction("ZW2M_State");
            $this->RegisterVariableInteger('ZW2M_Brightness', $this->Translate('Brightness'), '~Intensity.100', 1);
            $this->EnableAction("ZW2M_Brightness");
        } 
        else if($this->topicContains("switch_binary")) {
            // SWITCH
            $this->RegisterVariableBoolean('ZW2M_State', $this->Translate('State'), '~Switch', 0);
            $this->EnableAction("ZW2M_State");
        }
    }


}
