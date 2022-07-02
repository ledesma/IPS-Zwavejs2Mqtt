<?php

declare(strict_types=1);

trait Zwavejs2MqttHelper {

    public function ReceiveData($JSONString)
    {
        $this->SendDebug('JSON', $JSONString, 0);

    }

    protected function createVariableProfiles()
    {
    }

}