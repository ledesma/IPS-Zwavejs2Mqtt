<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/MQTTHelper.php';

class Zwavejs2MqttConfigurator extends IPSModule
{
    use MQTTHelper;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
        $this->RegisterPropertyString('MQTTTopic', 'bridge');
        $this->SetBuffer('Devices', '{}');
        $this->SetBuffer('Groups', '{}');

        //$this->RegisterAttributeBoolean('ReceiveDataFilterActive', true);
        $this->RegisterTimer('ZW2M_ActivateReceiveDataFilter', 0, 'ZW2M_ActivateReceiveDataFilter($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        //Setze Filter fuer ReceiveData
        $topic = $this->ReadPropertyString('MQTTTopic');
        $this->SetReceiveDataFilter('.*' . $topic . '.*');
        $this->getDevices();
        $this->getGroups();
    }

    public function GetConfigurationForm()
    {
        $this->getDevices();
        $this->getGroups();
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        //Devices
        $Devices = json_decode($this->GetBuffer('Devices'), true);
        $this->SendDebug('Buffer Devices', json_encode($Devices), 0);
        $ValuesDevices = [];
        if($Devices != null) {
            foreach ($Devices['result'] as $device) {
                $instanceID = $this->getDeviceInstanceID($device['name']);
                $Value['name'] = $device['name'];
                $Value['ieee_address'] = $device['id'];
                $Value['type'] = $device['type'];
                if ($device['type'] != 'Coordinator') {
                    $Value['vendor'] = $device['manufacturer'];
                    $Value['modelID'] = (array_key_exists('productId', $device) == true ? $device['productId'] : $this->Translate('Unknown'));
                    $Value['description'] = $device['name'];
                    $Value['power_source'] = (array_key_exists('powerSource', $device) == true ? $this->Translate($device['powerSource']) : $this->Translate('Unknown'));
                }
                $Value['instanceID'] = $instanceID;

                $Value['create'] =
                    [
                        'moduleID'      => '{2f484aaa-c1e1-4d2e-9635-68ff0361bc44}',
                        'configuration' => [
                            'MQTTTopic'    => $device['loc'].'/'.$device['name']
                        ]
                    ];
                array_push($ValuesDevices, $Value);
            }
            $Form['actions'][0]['items'][0]['values'] = $ValuesDevices;
        }
        //Groups
        $Groups = json_decode($this->GetBuffer('Groups'), true);
        $ValuesGroups = [];

        foreach ($Groups as $group) {
            $instanceID = $this->getGroupInstanceID($group['friendly_name']);
            $Value['ID'] = $group['ID'];
            $Value['name'] = $group['friendly_name'];
            $Value['instanceID'] = $instanceID;

            $Value['create'] =
                [
                    'moduleID'      => '{5e111dcd-06fd-43c0-a0e4-306fa64b6269}',
                    'configuration' => [
                        'MQTTTopic'    => $group['friendly_name']
                    ]
                ];
            array_push($ValuesGroups, $Value);
        }
        $Form['actions'][1]['items'][0]['values'] = $ValuesGroups;
        return json_encode($Form);
    }

    public function ReceiveData($JSONString)
    {
        $Buffer = json_decode($JSONString, true);

        if (array_key_exists('Topic', $Buffer) && fnmatch('zwave/*', $Buffer['Topic']))  {

            $this->SendDebug('JSON', $JSONString, 0);

            if (fnmatch('*/getNodes', $Buffer['Topic'])) {
                $Payload = json_decode($Buffer['Payload'], true);
                $this->SetBuffer('Devices', json_encode($Payload));
            }
            if (fnmatch('*/log', $Buffer['Topic'])) {
                $Payload = json_decode($Buffer['Payload'], true);

                if ($Payload['type'] == 'groups') {
                    $this->SetBuffer('Groups', json_encode($Payload['message']));
                }
            }
        }
    }

    public function getDeviceVariables($FriendlyName)
    {
        $Payload['from'] = $FriendlyName;
        $Payload['to'] = $FriendlyName . 'Z2MSymcon';
        $Payload['homeassistant_rename'] = false;
        $this->Command('request/device/rename', json_encode($Payload));

        $Payload['from'] = $FriendlyName . 'Z2MSymcon';
        $Payload['to'] = $FriendlyName;
        $Payload['homeassistant_rename'] = false;
        $this->Command('request/device/rename', json_encode($Payload));
    }

    public function ActivateReceiveDataFilter()
    {
        $topic = $this->ReadPropertyString('MQTTTopic');
        $this->SetReceiveDataFilter('.*' . $topic . '.*');
        $this->SetTimerInterval('ZW2M_ActivateReceiveDataFilter', 0);
    }

    private function getDevices()
    {
        $this->SetReceiveDataFilter('');
        $this->Command('_CLIENTS/ZWAVE_GATEWAY-Zwavejs2Mqtt/api/getNodes/set', '');
        $this->SetTimerInterval('ZW2M_ActivateReceiveDataFilter', 30000);
    }

    private function getGroups()
    {
        $this->Command('config/groups/', '');
    }

    private function getDeviceInstanceID($FriendlyName)
    {
        $InstanceIDs = IPS_GetInstanceListByModuleID('{2f484aaa-c1e1-4d2e-9635-68ff0361bc44}');
        foreach ($InstanceIDs as $id) {
            if (IPS_GetProperty($id, 'MQTTTopic') == $FriendlyName) {
                return $id;
            }
        }
        return 0;
    }

    private function getGroupInstanceID($FriendlyName)
    {
        $InstanceIDs = IPS_GetInstanceListByModuleID('{5e111dcd-06fd-43c0-a0e4-306fa64b6269}');
        foreach ($InstanceIDs as $id) {
            if (IPS_GetProperty($id, 'MQTTTopic') == $FriendlyName) {
                return $id;
            }
        }
        return 0;
    }
}