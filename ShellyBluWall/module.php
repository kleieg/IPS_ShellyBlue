<?php

class ShellyBluWall extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Address', '');

        // variables
        $this->RegisterVariableInteger("Button4", "Button4");
        $this->RegisterVariableInteger("Button1", "Button1");
        $this->RegisterVariableInteger("Button2", "Button2");
        $this->RegisterVariableInteger("Button3", "Button3");
        $this->RegisterVariableInteger("Battery", "Battery", '~Battery.100');

        $this->SetBuffer('pid', serialize(255));
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $topic = $this->ReadPropertyString('Address');
        $this->SetReceiveDataFilter('.*' . $topic . '.*');
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug('JSON', $JSONString, 0);
        if (empty($this->ReadPropertyString('Address'))) return;

        $Buffer = json_decode($JSONString, true);
        $Payload = json_decode($Buffer['Payload'], true);
        if(!isset($Payload['pid'])) return;

        // deduplicate packages (e.g., if multiple gateways are receiving..)
        // packet id must be larger/newer than previous.. but allow for rollover if difference is large enough (e.g., 30 = 5m, assuming 1 packet every ~10s)
        $lastPID = unserialize($this->GetBuffer('pid'));
        $pid = intval($Payload['pid']);
        if($pid <= $lastPID && ($lastPID - $pid < 30)) return;
        $this->SetBuffer('pid', serialize($pid));


        if(isset($Payload['Button1'])) {
            $this->SetValue('Button1', $Payload['Button1']);
        }
        if(isset($Payload['Button2'])) {
            $this->SetValue('Button2', $Payload['Button2']);
        }
        if(isset($Payload['Button3'])) {
            $this->SetValue('Button3', $Payload['Button3']);
        }
        if(isset($Payload['Button4'])) {
            $this->SetValue('Button4', $Payload['Button4']);
        }
        if(isset($Payload['Battery'])) {
            $this->SetValue('Battery', $Payload['Battery']);
        }
    }

}