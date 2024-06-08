<?php

/**
 * @project       Statusanzeige/StatusanzeigeHomematicIP/
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2022,2023,2024 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/SAHMIP_autoload.php';

class StatusanzeigeHomematicIP extends IPSModule
{
    //Helper
    use SAHMIP_ConfigurationForm;
    use SAHMIP_Control;
    use SAHMIP_Signaling;
    use SAHMIP_TriggerCondition;

    //Constants
    private const LIBRARY_GUID = '{3E8B8394-FC34-8C9A-6324-A03FB7E64B29}';
    private const MODULE_GUID = '{B811C5C6-4DB9-2E1E-D8F8-1532D1A2CFCD}';
    private const MODULE_NAME = 'Statusanzeige Homematic IP';
    private const MODULE_PREFIX = 'SAHMIP';
    private const ABLAUFSTEUERUNG_MODULE_GUID = '{0559B287-1052-A73E-B834-EBD9B62CB938}';
    private const ABLAUFSTEUERUNG_MODULE_PREFIX = 'AST';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Info
        $this->RegisterPropertyString('Note', '');

        //Upper light unit
        $this->RegisterPropertyInteger('UpperLightUnitDeviceType', 0);
        $this->RegisterPropertyInteger('UpperLightUnit', 0);
        $this->RegisterPropertyInteger('UpperLightUnitSwitchingDelay', 0);
        $this->RegisterPropertyBoolean('UpperLightUnitUseCombinedParameter', false);
        $this->RegisterPropertyInteger('UpperLightUnitDeviceColor', 0);
        $this->RegisterPropertyInteger('UpperLightUnitDeviceBrightness', 0);
        $this->RegisterPropertyInteger('UpperLightUnitDeviceColorBehaviour', 0);
        $this->RegisterPropertyString('UpperLightUnitTriggerList', '[]');
        $this->RegisterPropertyBoolean('UpdateLowerLightUnit', false);

        //Lower light unit
        $this->RegisterPropertyInteger('LowerLightUnitDeviceType', 0);
        $this->RegisterPropertyInteger('LowerLightUnit', 0);
        $this->RegisterPropertyInteger('LowerLightUnitSwitchingDelay', 0);
        $this->RegisterPropertyBoolean('LowerLightUnitUseCombinedParameter', false);
        $this->RegisterPropertyInteger('LowerLightUnitDeviceColor', 0);
        $this->RegisterPropertyInteger('LowerLightUnitDeviceBrightness', 0);
        $this->RegisterPropertyInteger('LowerLightUnitDeviceColorBehaviour', 0);
        $this->RegisterPropertyString('LowerLightUnitTriggerList', '[]');
        $this->RegisterPropertyBoolean('UpdateUpperLightUnit', false);

        //Automatic status update
        $this->RegisterPropertyBoolean('AutomaticStatusUpdate', false);
        $this->RegisterPropertyInteger('CheckStatusInterval', 1200);
        $this->RegisterPropertyBoolean('ForceSignaling', true);

        //Command control
        $this->RegisterPropertyInteger('CommandControl', 0);

        //Deactivation
        $this->RegisterPropertyBoolean('DeactivateUpperLightUnitChangeColor', false);
        $this->RegisterPropertyInteger('DeactivationUpperLightUnitColor', 0);
        $this->RegisterPropertyBoolean('DeactivateUpperLightUnitChangeBrightness', true);
        $this->RegisterPropertyInteger('DeactivationUpperLightUnitBrightness', 0);
        $this->RegisterPropertyBoolean('DeactivateUpperLightUnitChangeMode', false);
        $this->RegisterPropertyInteger('DeactivationUpperLightUnitMode', 0);

        $this->RegisterPropertyBoolean('DeactivateLowerLightUnitChangeColor', false);
        $this->RegisterPropertyInteger('DeactivationLowerLightUnitColor', 0);
        $this->RegisterPropertyBoolean('DeactivateLowerLightUnitChangeBrightness', true);
        $this->RegisterPropertyInteger('DeactivationLowerLightUnitBrightness', 0);
        $this->RegisterPropertyBoolean('DeactivateLowerLightUnitChangeMode', false);
        $this->RegisterPropertyInteger('DeactivationLowerLightUnitMode', 0);

        $this->RegisterPropertyBoolean('ReactivateUpperLightUnitLastColor', true);
        $this->RegisterPropertyBoolean('ReactivateUpperLightUnitLastBrightness', true);
        $this->RegisterPropertyBoolean('ReactivateUpperLightUnitLastMode', false);

        $this->RegisterPropertyBoolean('ReactivateLowerLightUnitLastColor', true);
        $this->RegisterPropertyBoolean('ReactivateLowerLightUnitLastBrightness', true);
        $this->RegisterPropertyBoolean('ReactivateLowerLightUnitLastMode', false);

        $this->RegisterPropertyBoolean('UseAutomaticDeactivation', false);
        $this->RegisterPropertyString('AutomaticDeactivationStartTime', '{"hour":22,"minute":0,"second":0}');
        $this->RegisterPropertyString('AutomaticDeactivationEndTime', '{"hour":6,"minute":0,"second":0}');

        //Visualisation
        $this->RegisterPropertyBoolean('EnableActive', false);
        $this->RegisterPropertyBoolean('EnableUpperLightUnitColor', true);
        $this->RegisterPropertyBoolean('EnableUpperLightUnitBrightness', true);
        $this->RegisterPropertyBoolean('EnableUpperLightUnitMode', true);
        $this->RegisterPropertyBoolean('EnableLowerLightUnitColor', true);
        $this->RegisterPropertyBoolean('EnableLowerLightUnitBrightness', true);
        $this->RegisterPropertyBoolean('EnableLowerLightUnitMode', true);

        ########## Variables

        //Active
        $id = @$this->GetIDForIdent('Active');
        $this->RegisterVariableBoolean('Active', 'Aktiv', '~Switch', 10);
        $this->EnableAction('Active');
        if (!$id) {
            $this->SetValue('Active', true);
        }

        ##### Upper Light Unit

        //Color
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Color';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', 'Bulb', 0);
        IPS_SetVariableProfileAssociation($profile, 1, 'Blau', 'Bulb', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 2, 'Grün', 'Bulb', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, 'Türkis', 'Bulb', 0x01DFD7);
        IPS_SetVariableProfileAssociation($profile, 4, 'Rot', 'Bulb', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 5, 'Violett', 'Bulb', 0xB40486);
        IPS_SetVariableProfileAssociation($profile, 6, 'Gelb', 'Bulb', 0xFFFF00);
        IPS_SetVariableProfileAssociation($profile, 7, 'Weiß', 'Bulb', 0xFFFFFF);
        $id = @$this->GetIDForIdent('UpperLightUnitColor');
        $this->RegisterVariableInteger('UpperLightUnitColor', 'Obere Leuchteinheit - Farbe', $profile, 20);
        $this->EnableAction('UpperLightUnitColor');
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('UpperLightUnitColor'), 'Bulb');
            $this->SetValue('UpperLightUnitColor', 0);
        }

        // Brightness
        $id = @$this->GetIDForIdent('UpperLightUnitBrightness');
        $this->RegisterVariableInteger('UpperLightUnitBrightness', 'Obere Leuchteinheit - Helligkeit', '~Intensity.100', 30);
        $this->EnableAction('UpperLightUnitBrightness');
        if (!$id) {
            $this->SetValue('UpperLightUnitBrightness', 0);
        }

        //Mode
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Mode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, 0, 'Beleuchtung aus', '', 0);
        IPS_SetVariableProfileAssociation($profile, 1, 'Dauerhaft ein', '', 0);
        IPS_SetVariableProfileAssociation($profile, 2, 'Langsames Blinken', '', 0);
        IPS_SetVariableProfileAssociation($profile, 3, 'Mittleres Blinken', '', 0);
        IPS_SetVariableProfileAssociation($profile, 4, 'Schnelles Blinken', '', 0);
        IPS_SetVariableProfileAssociation($profile, 5, 'Langsames Blitzen', '', 0);
        IPS_SetVariableProfileAssociation($profile, 6, 'Mittleres Blitzen', '', 0);
        IPS_SetVariableProfileAssociation($profile, 7, 'Schnelles Blitzen', '', 0);
        IPS_SetVariableProfileAssociation($profile, 8, 'Langsames Pulsieren', '', 0);
        IPS_SetVariableProfileAssociation($profile, 9, 'Mittleres Pulsieren', '', 0);
        IPS_SetVariableProfileAssociation($profile, 10, 'Schnelles Pulsieren', '', 0);
        IPS_SetVariableProfileAssociation($profile, 11, 'Vorheriger Wert', '', 0);
        IPS_SetVariableProfileAssociation($profile, 12, 'Ohne Berücksichtigung', '', 0);
        $id = @$this->GetIDForIdent('UpperLightUnitMode');
        $this->RegisterVariableInteger('UpperLightUnitMode', 'Obere Leuchteinheit - Modus', $profile, 35);
        $this->EnableAction('UpperLightUnitMode');
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('UpperLightUnitMode'), 'Gear');
            $this->SetValue('UpperLightUnitMode', 1);
        }

        ##### Lower Light Unit

        //Color
        $id = @$this->GetIDForIdent('LowerLightUnitColor');
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Color';
        $this->RegisterVariableInteger('LowerLightUnitColor', 'Untere Leuchteinheit - Farbe', $profile, 40);
        $this->EnableAction('LowerLightUnitColor');
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('LowerLightUnitColor'), 'Bulb');
            $this->SetValue('LowerLightUnitColor', 0);
        }

        //Brightness
        $id = @$this->GetIDForIdent('LowerLightUnitBrightness');
        $this->RegisterVariableInteger('LowerLightUnitBrightness', 'Untere Leuchteinheit - Helligkeit', '~Intensity.100', 50);
        $this->EnableAction('LowerLightUnitBrightness');
        if (!$id) {
            $this->SetValue('LowerLightUnitBrightness', 0);
        }

        //Mode
        $id = @$this->GetIDForIdent('LowerLightUnitMode');
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Mode';
        $this->RegisterVariableInteger('LowerLightUnitMode', 'Untere Leuchteinheit - Modus', $profile, 55);
        $this->EnableAction('LowerLightUnitMode');
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('LowerLightUnitMode'), 'Gear');
            $this->SetValue('LowerLightUnitMode', 1);
        }

        ########## Attributes

        $this->RegisterAttributeInteger('UpperLightUnitLastColor', 0);
        $this->RegisterAttributeInteger('UpperLightUnitLastBrightness', 0);
        $this->RegisterAttributeInteger('UpperLightUnitLastMode', 0);
        $this->RegisterAttributeInteger('LowerLightUnitLastColor', 0);
        $this->RegisterAttributeInteger('LowerLightUnitLastBrightness', 0);
        $this->RegisterAttributeInteger('LowerLightUnitLastMode', 0);

        ########## Timers

        $this->RegisterTimer('StartAutomaticDeactivation', 0, self::MODULE_PREFIX . '_StartAutomaticDeactivation(' . $this->InstanceID . ');');
        $this->RegisterTimer('StopAutomaticDeactivation', 0, self::MODULE_PREFIX . '_StopAutomaticDeactivation(' . $this->InstanceID . ');');
        $this->RegisterTimer('CheckStatus', 0, self::MODULE_PREFIX . '_CheckStatus(' . $this->InstanceID . ');');
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
        $references = ['UpperLightUnit',
            'UpperLightUnitDeviceColor',
            'UpperLightUnitDeviceBrightness',
            'UpperLightUnitDeviceColorBehaviour',
            'LowerLightUnit',
            'LowerLightUnitDeviceColor',
            'LowerLightUnitDeviceBrightness',
            'LowerLightUnitDeviceColorBehaviour',
            'CommandControl'
        ];

        foreach ($references as $reference) {
            $id = $this->ReadPropertyInteger($reference);
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $this->RegisterReference($id);
            }
        }

        $triggerLists = ['UpperLightUnitTriggerList', 'LowerLightUnitTriggerList'];
        foreach ($triggerLists as $list) {
            $variables = json_decode($this->ReadPropertyString($list), true);
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
        }

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('UpperLightUnitColor'), !$this->ReadPropertyBoolean('EnableUpperLightUnitColor'));
        IPS_SetHidden($this->GetIDForIdent('UpperLightUnitBrightness'), !$this->ReadPropertyBoolean('EnableUpperLightUnitBrightness'));
        IPS_SetHidden($this->GetIDForIdent('UpperLightUnitMode'), !$this->ReadPropertyBoolean('EnableUpperLightUnitMode'));
        IPS_SetHidden($this->GetIDForIdent('LowerLightUnitColor'), !$this->ReadPropertyBoolean('EnableLowerLightUnitColor'));
        IPS_SetHidden($this->GetIDForIdent('LowerLightUnitBrightness'), !$this->ReadPropertyBoolean('EnableLowerLightUnitBrightness'));
        IPS_SetHidden($this->GetIDForIdent('LowerLightUnitMode'), !$this->ReadPropertyBoolean('EnableLowerLightUnitMode'));

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
                $this->UpdateLightUnits(true);
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

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['Color', 'Mode'];
        foreach ($profiles as $profile) {
            $profileName = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
            if (IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
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

                if ($this->CheckMaintenance()) {
                    return;
                }

                ##### Upper Light Unit

                //Checks if the trigger is assigned to the light unit
                if ($this->CheckTrigger($SenderID, 0)) {
                    $this->UpdateUpperLightUnit(false);
                    if ($this->ReadPropertyBoolean('UpdateLowerLightUnit')) {
                        $this->UpdateLowerLightUnit(false);
                    }
                }

                ##### Lower Light Unit

                if ($this->CheckTrigger($SenderID, 1)) {
                    $this->UpdateLowerLightUnit(false);
                    if ($this->ReadPropertyBoolean('UpdateUpperLightUnit')) {
                        $this->UpdateUpperLightUnit(false);
                    }
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

    #################### Request Action

    public function RequestAction($Ident, $Value)
    {
        $upperColor = $this->GetValue('UpperLightUnitColor');
        $upperBrightness = $this->GetValue('UpperLightUnitBrightness');
        $upperMode = $this->GetValue('UpperLightUnitMode');

        $lowerColor = $this->GetValue('LowerLightUnitColor');
        $lowerBrightness = $this->GetValue('LowerLightUnitBrightness');
        $lowerMode = $this->GetValue('LowerLightUnitMode');

        switch ($Ident) {
            case 'Active':
                $this->ToggleActive($Value);
                break;

            case 'UpperLightUnitColor':
                if (!$this->CheckMaintenance()) {
                    if ($this->ReadPropertyBoolean('UpperLightUnitUseCombinedParameter')) {
                        $this->SetCombinedParameters(0, $Value, $upperBrightness, $upperMode);
                    } else {
                        $this->SetColor(0, $Value);
                    }
                }
                break;

            case 'UpperLightUnitBrightness':
                if (!$this->CheckMaintenance()) {
                    if ($this->ReadPropertyBoolean('UpperLightUnitUseCombinedParameter')) {
                        $this->SetCombinedParameters(0, $upperColor, $Value, $upperMode);
                    } else {
                        $this->SetBrightness(0, $Value);
                    }
                }
                break;

            case 'UpperLightUnitMode':
                if (!$this->CheckMaintenance()) {
                    if ($this->ReadPropertyBoolean('UpperLightUnitUseCombinedParameter')) {
                        $this->SetCombinedParameters(0, $upperColor, $upperBrightness, $Value);
                    } else {
                        $this->SetMode(0, $Value);
                    }
                }
                break;

            case 'LowerLightUnitColor':
                if (!$this->CheckMaintenance()) {
                    if ($this->ReadPropertyBoolean('LowerLightUnitUseCombinedParameter')) {
                        $this->SetCombinedParameters(1, $Value, $upperBrightness, $upperMode);
                    } else {
                        $this->SetColor(1, $Value);
                    }
                }
                break;

            case 'LowerLightUnitBrightness':
                if (!$this->CheckMaintenance()) {
                    if ($this->ReadPropertyBoolean('LowerLightUnitUseCombinedParameter')) {
                        $this->SetCombinedParameters(1, $upperColor, $Value, $upperMode);
                    } else {
                        $this->SetBrightness(1, $Value);
                    }
                }
                break;

            case 'LowerLightUnitMode':
                if (!$this->CheckMaintenance()) {
                    if ($this->ReadPropertyBoolean('LowerLightUnitUseCombinedParameter')) {
                        $this->SetCombinedParameters(1, $upperColor, $upperBrightness, $Value);
                    } else {
                        $this->SetMode(1, $Value);
                    }
                }
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