<?php

/**
 * @project       Statusanzeige/StatusanzeigeHomematic/
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/SAHM_autoload.php';

class StatusanzeigeHomematic extends IPSModule
{
    //Helper
    use SAHM_ConfigurationForm;
    use SAHM_Control;
    use SAHM_Signaling;
    use SAHM_TriggerCondition;

    //Constants
    private const LIBRARY_GUID = '{3E8B8394-FC34-8C9A-6324-A03FB7E64B29}';
    private const MODULE_GUID = '{17C9B00D-3C66-2B99-7F83-604DA32C91E6}';
    private const MODULE_NAME = 'Statusanzeige Homematic';
    private const MODULE_PREFIX = 'SAHM';
    private const ABLAUFSTEUERUNG_MODULE_GUID = '{0559B287-1052-A73E-B834-EBD9B62CB938}';
    private const ABLAUFSTEUERUNG_MODULE_PREFIX = 'AST';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Info
        $this->RegisterPropertyString('Note', '');

        //Signaling device
        $this->RegisterPropertyInteger('SignallingDeviceType', 0);
        $this->RegisterPropertyInteger('SignallingDeviceInstance', 0);
        $this->RegisterPropertyInteger('SignallingDeviceState', 0);
        $this->RegisterPropertyInteger('SignallingDelay', 0);

        //Trigger  list
        $this->RegisterPropertyString('TriggerList', '[]');

        //Automatic status update
        $this->RegisterPropertyBoolean('AutomaticStatusUpdate', false);
        $this->RegisterPropertyInteger('CheckStatusInterval', 1200);

        //Command control
        $this->RegisterPropertyInteger('CommandControl', 0);

        //Deactivation
        $this->RegisterPropertyBoolean('UseAutomaticDeactivation', false);
        $this->RegisterPropertyString('AutomaticDeactivationStartTime', '{"hour":22,"minute":0,"second":0}');
        $this->RegisterPropertyString('AutomaticDeactivationEndTime', '{"hour":6,"minute":0,"second":0}');

        //Visualisation
        $this->RegisterPropertyBoolean('EnableActive', false);
        $this->RegisterPropertyBoolean('EnableSignalling', true);

        ########## Variables

        //Active
        $id = @$this->GetIDForIdent('Active');
        $this->RegisterVariableBoolean('Active', 'Aktiv', '~Switch', 10);
        $this->EnableAction('Active');
        if (!$id) {
            $this->SetValue('Active', true);
        }

        //Signalling
        $id = @$this->GetIDForIdent('Signalling');
        $this->RegisterVariableBoolean('Signalling', 'Anzeige', '~Switch', 20);
        $this->EnableAction('Signalling');
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('Signalling'), 'Bulb');
        }

        ########## Timers

        $this->RegisterTimer('CheckStatus', 0, self::MODULE_PREFIX . '_UpdateState(' . $this->InstanceID . ', true);');
        $this->RegisterTimer('StartAutomaticDeactivation', 0, self::MODULE_PREFIX . '_StartAutomaticDeactivation(' . $this->InstanceID . ');');
        $this->RegisterTimer('StopAutomaticDeactivation', 0, self::MODULE_PREFIX . '_StopAutomaticDeactivation(' . $this->InstanceID . ',);');
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        //Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all update messages
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
            }
        }

        //Register references and update messages
        $names = [];
        $names[] = ['propertyName' => 'SignallingDeviceInstance', 'useUpdate' => false];
        $names[] = ['propertyName' => 'SignallingDeviceState', 'useUpdate' => true];
        $names[] = ['propertyName' => 'CommandControl', 'useUpdate' => false];
        foreach ($names as $name) {
            $id = $this->ReadPropertyInteger($name['propertyName']);
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $this->RegisterReference($id);
                if ($name['useUpdate']) {
                    $this->RegisterMessage($id, VM_UPDATE);
                }
            }
        }

        $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            //Primary condition
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($id > 1 && @IPS_ObjectExists($id)) {
                            $this->RegisterReference($id);
                            $this->RegisterMessage($id, VM_UPDATE);
                        }
                    }
                }
            }
            //Secondary condition, multi
            if ($variable['SecondaryCondition'] != '') {
                $secondaryConditions = json_decode($variable['SecondaryCondition'], true);
                if (array_key_exists(0, $secondaryConditions)) {
                    if (array_key_exists('rules', $secondaryConditions[0])) {
                        $rules = $secondaryConditions[0]['rules']['variable'];
                        foreach ($rules as $rule) {
                            if (array_key_exists('variableID', $rule)) {
                                $id = $rule['variableID'];
                                if ($id > 1 && @IPS_ObjectExists($id)) {
                                    $this->RegisterReference($id);
                                }
                            }
                        }
                    }
                }
            }
        }

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('Signalling'), !$this->ReadPropertyBoolean('EnableSignalling'));

        $this->SetAutomaticDeactivationTimer();

        //Status
        if (!$this->CheckAutomaticDeactivationTimer()) {
            $update = true;
            $commandControl = $this->ReadPropertyInteger('CommandControl');
            if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                $instance = @IPS_GetInstance($commandControl);
                if ($instance['InstanceStatus'] != 102) {
                    $this->LogMessage('Die Ablaufsteuerung ist noch nicht bereit!', KL_WARNING);
                    $update = false;
                }
            }
            if ($commandControl > 1 && @!IPS_ObjectExists($commandControl)) {
                $update = false;
            }
            if ($update) {
                $this->UpdateState(true);
            }
        } else {
            $this->ToggleActive(false);
        }

        //Set automatic status update timer
        $milliseconds = 0;
        if ($this->ReadPropertyBoolean('AutomaticStatusUpdate')) {
            $milliseconds = $this->ReadPropertyInteger('CheckStatusInterval') * 1000;
        }
        $this->SetTimerInterval('CheckStatus', $milliseconds);
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case VM_UPDATE:

                //$Data[0] = actual value
                //$Data[1] = value changed
                //$Data[2] = last value
                //$Data[3] = timestamp actual value
                //$Data[4] = timestamp value changed
                //$Data[5] = timestamp last value

                $trigger = true;
                if ($SenderID == $this->ReadPropertyInteger('SignallingDeviceState')) {
                    $trigger = false;
                    $this->UpdateStateFromDevice();
                }

                if ($this->CheckMaintenance()) {
                    return;
                }

                //Check trigger conditions
                if ($trigger) {
                    $this->CheckTriggerConditions($SenderID, $Data[1], false);
                }
                break;

        }
    }

    public function CreateCommandControlInstance(): void
    {
        $id = IPS_CreateInstance(self::ABLAUFSTEUERUNG_MODULE_GUID);
        if (is_int($id)) {
            IPS_SetName($id, 'Ablaufsteuerung');
            $infoText = 'Instanz mit der ID ' . $id . ' wurde erfolgreich erstellt!';
        } else {
            $infoText = 'Instanz konnte nicht erstellt werden!';
        }
        $this->UpdateFormField('InfoMessage', 'visible', true);
        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
    }

    public function UIShowMessage(string $Message): void
    {
        $this->UpdateFormField('InfoMessage', 'visible', true);
        $this->UpdateFormField('InfoMessageLabel', 'caption', $Message);
    }

    #################### Request Action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Active':
                $this->ToggleActive($Value);
                break;

            case 'Signalling':
                $this->ToggleSignalling($Value, true);
                break;

        }
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    private function CheckMaintenance(): bool
    {
        $result = false;
        if (!$this->GetValue('Active')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Instanz ist inaktiv!', 0);
            $result = true;
        }
        return $result;
    }
}