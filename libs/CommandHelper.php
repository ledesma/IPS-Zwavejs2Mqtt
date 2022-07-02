<?php

declare(strict_types=1);

trait CommandHelper
{

    public function SwitchMode($state) {
        if(topicContains("switch_multilevel")) {
            $this->sendEvent($state ? 100 : 0);
        } 
        else if(topicContains("switch_binary")) {
            $this->sendEvent($state ? "on" : "off");
        }
        else {
            $this->LogMessage("Instanz kann nicht switchen", KL_WARNING);
        }
    }

    public function DimSet($value) {
        if(topicContains("switch_multilevel")) {
            $this->sendEvent($value);
        } 
        else {
            $this->LogMessage("Instanz kann nicht dimmen", KL_WARNING);
        }
    }

    private function topicContains($keyword) {
        $topic = $this->ReadPropertyString('MQTTTopic');
        return preg_match('\b'.$keyword'\b');
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

        $this->notifyAssociations($newValue);
    }

}
