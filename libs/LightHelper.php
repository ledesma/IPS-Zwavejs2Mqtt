<?php

declare(strict_types=1);

trait LightHelper
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
        $buffer = json_decode($json);
        // buffer decodieren und in eine Variable schreiben
        $this->SendDebug('ReceiveData', 'MQTT Topic: '.$buffer->Topic, 0);
        $this->SendDebug('ReceiveData', 'MQTT Payload: '. $buffer->Payload, 0);
        if(fnmatch('*/switch_multilevel/*/currentValue', $buffer->Topic)){
            $value = $buffer->Payload;
            SetValue($this->GetIDForIdent('ZW2M_State'), $value > 0);
            SetValue($this->GetIDForIdent('ZW2M_Brightness'), $value);
            $this->notifyAssociations(function($id, $val) {
                $this->SendDebug('ReceiveData', "calling association: ZW2M_DimSet($id, $val)", 0);
                ZW2M_DimSet($id, $val);
            }, $value);            
        }
        else if(fnmatch('*/switch_binary/*/currentValue', $buffer->Topic)){
            $value = $buffer->Payload;
            SetValue($this->GetIDForIdent('ZW2M_State'), $value);
            $this->notifyAssociations(function($id, $val) {
                $this->SendDebug('ReceiveData', "calling association: ZW2M_SwitchMode($id, $val)", 0);
                ZW2M_SwitchMode($id, $val);
            }, $value);            
        }        
    }

    public function SwitchMode(bool $state) {
        if($this->isSwitch()) {
            $this->sendEvent('switch_binary', $state ? "on" : "off");
        }
        else if($this->isDimmer()) {
            $this->sendEvent('switch_multilevel', $state ? 100 : 0);
        }
        else {
            $this->LogMessage("Instanz kann nicht switchen", KL_WARNING);
        }
        $this->notifyAssociations(function($id, $val) {
            $this->SendDebug('SwitchMode', "calling association: ZW2M_SwitchMode($id, $val)", 0);
            ZW2M_SwitchMode($id, $val);
        }, $state);
    }

    public function DimSet(int $value) {
        if($this->isDimmer()) {
            $this->sendEvent('switch_multilevel', $value);
        } 
        else {
            $this->LogMessage("Instanz kann nicht dimmen", KL_WARNING);
        }
        $this->notifyAssociations(function($id, $val) {
            $this->SendDebug('DimSet', "calling association: ZW2M_DimSet($id, $val)", 0);
            ZW2M_DimSet($id, $val);
        }, $value);
    }

    private function sendEvent($type, $newValue)
    {
        $data = [
            'DataID' => '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}',
            'PacketType' => 3,
            'QualityOfService' => 0,
            'Retain' => false,
            'Topic' => $this->topic($type) . '/targetValue/set',
            'Payload' => strval($newValue)
        ];
        $DataJSON = json_encode($data, JSON_UNESCAPED_SLASHES);
        $this->SendDebug(__FUNCTION__ . ' Topic', $data['Topic'], 0);
        $this->SendDebug(__FUNCTION__ . ' Payload', $data['Payload'], 0);
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
        if($this->isDimmer()) {
            $this->RegisterVariableInteger('ZW2M_Brightness', $this->Translate('Brightness'), '~Intensity.100', 1);
            $this->EnableAction("ZW2M_Brightness");
        } 
        if($this->isSwitch() || $this->isDimmer()) {
            $this->RegisterVariableBoolean('ZW2M_State', $this->Translate('State'), '~Switch', 0);
            $this->EnableAction("ZW2M_State");
        }
    }


    private function isSwitch() {
        return $this->ReadPropertyBoolean('switch');
    }
    private function isDimmer() {
        return $this->ReadPropertyBoolean('dimmer');
    }

    private function topic($type) {
        $topic =   'zwave/' 
                    . $this->ReadPropertyString('location') . '/' 
                    . $this->ReadPropertyString('name') . '/' 
                    . $type 
                    . '/endpoint_' . strval($this->ReadPropertyInteger('endpoint'));
        return str_replace(' ','_', $topic);
    }

}
