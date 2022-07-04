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
        $this->RegisterPropertyString('MQTTTopic', 'zwave/_CLIENTS/ZWAVE_GATEWAY-Zwavejs2Mqtt');
        $this->RegisterAttributeString('Nodes', '[]');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        $topic = $this->ReadPropertyString('MQTTTopic');
        $filter = '.*"' . $topic . '.*';
        $this->SetReceiveDataFilter($filter);
    }

    public function GetConfigurationForm() {
        $this->getDevices();
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $nodes = json_decode($this->ReadAttributeString ('Nodes'));
        $this->SendDebug('Nodes', json_encode($nodes), 0);
        $formData = [];
        foreach($nodes as $node) {
            $entry = [
                'id' => $node->id,
                'name' => $node->name,
                'location' => $node->loc,
                'manufacturer' => $node->manufacturer,
                'productDescription' => $node->productDescription,
                'productLabel' => $node->productLabel,
                'expanded' => true
            ];
            array_push($formData, $entry);

            foreach($this->getAllEndpoints($node) as $ep) {
                $entry = [
                    'id' => $node->id . " - ". $ep,
                    'name' => '',
                    'location' => '',
                    'manufacturer' => '',
                    'productDescription' => '',
                    'productLabel' => '',
                    'parent' => $node->id,
                    'endpoint' => $ep,
                    'instanceID' => $this->getDeviceInstanceID($node->id, $ep),
                    'create' => [
                        'moduleID' => "{B549B80B-4E79-2D06-0D1D-02A19A457402}",
                        "name" => $node->loc . " - " . $node->name . " " . $ep,
                        'configuration' => [
                            'nodeId' => $node->id, 
                            'MQTTBaseTopic' => $this->ReadPropertyString('MQTTTopic'),
                            'endpoint' => $ep,
                            'name' => $node->name,
                            'location' => $node->loc,
                            'switch' => $this->hasCommandClass($node, 37),
                            'dimmer' => $this->hasCommandClass($node, 38),
                            'manufacturer' => $node->manufacturer,
                            'productLabel' => $node->productLabel
                        ]
                    ]
                ];
                array_push($formData, $entry);
            }
        }
        $this->SendDebug('FormData', json_encode($formData), 0);
        
        $form['actions'][0]['values'] = $formData;
        return json_encode($form);
    }

    public function ReceiveData($json) {
        $buffer = json_decode($json, true);
        if (array_key_exists('Topic', $buffer) && fnmatch('*/api/getNodes', $buffer['Topic'])) {
            $this->SendDebug('ReceiveData', print_r(json_decode($buffer['Payload']), true), 0);
            $nodes = json_decode($buffer['Payload']);
            $this->WriteAttributeString('Nodes', json_encode($nodes->result));
        }
    }

    private function getDevices() {
        $this->SendDebug('Query Nodes', '', true);
        $this->Command('api/getNodes/set','');
    }

    private function getDeviceInstanceID($nodeId, $endpoint) {
        $InstanceIDs = IPS_GetInstanceListByModuleID('{B549B80B-4E79-2D06-0D1D-02A19A457402}');
        // $this->LogMessage(print_r($InstanceIDs, true), KL_ERROR);
        foreach ($InstanceIDs as $id) {
            if (IPS_GetProperty($id, 'nodeId') == $nodeId && IPS_GetProperty($id, 'endpoint') == $endpoint) {
                return $id;
            }
        }
        return 0;
    }

    private function hasCommandClass($node, $cc) {
        $values = json_decode(json_encode($node->values), true);
        foreach($values as $value) {
            if($value["commandClass"] == $cc) {
                return true;
            }
        }
        return false;
    }

    private function getAllEndpoints($node) {
        $values = json_decode(json_encode($node->values), true);
        return array_unique(array_map(fn($value) => $value["endpoint"], $values));
    }

}