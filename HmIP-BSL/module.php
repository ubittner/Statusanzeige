<?php

/*
 * @author      Ulrich Bittner
 * @copyright   (c) 2020, 2021
 * @license     CC BY-NC-SA 4.0
 * @see         https://github.com/ubittner/Statusanzeige/tree/master/HmIP-BSL
 */

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);
include_once __DIR__ . '/helper/autoload.php';

class StatusanzeigeHmIPBSL extends IPSModule
{
    // Helper
    use SAHMIPBSL_backupRestore;
    use SAHMIPBSL_control;
    use SAHMIPBSL_nightMode;

    // Constants
    private const DELAY_MILLISECONDS = 100;

    public function Create()
    {
        // Never delete this line!
        parent::Create();

        // Properties
        // Functions
        $this->RegisterPropertyBoolean('MaintenanceMode', false);
        $this->RegisterPropertyBoolean('EnableUpperLightUnitColor', true);
        $this->RegisterPropertyBoolean('EnableUpperLightUnitBrightness', true);
        $this->RegisterPropertyBoolean('EnableLowerLightUnitColor', true);
        $this->RegisterPropertyBoolean('EnableLowerLightUnitBrightness', true);
        $this->RegisterPropertyBoolean('EnableNightMode', true);
        // Upper light unit
        $this->RegisterPropertyInteger('UpperLightUnit', 0);
        $this->RegisterPropertyInteger('UpperLightUnitSwitchingDelay', 0);
        $this->RegisterPropertyString('UpperLightUnitTriggerVariables', '[]');
        // Lower light unit
        $this->RegisterPropertyInteger('LowerLightUnit', 0);
        $this->RegisterPropertyInteger('LowerLightUnitSwitchingDelay', 0);
        $this->RegisterPropertyString('LowerLightUnitTriggerVariables', '[]');
        // Night mode
        $this->RegisterPropertyBoolean('ChangeNightModeColorUpperLightUnit', true);
        $this->RegisterPropertyInteger('NightModeColorUpperLightUnit', 0);
        $this->RegisterPropertyBoolean('ChangeNightModeBrightnessUpperLightUnit', false);
        $this->RegisterPropertyInteger('NightModeBrightnessUpperLightUnit', 0);
        $this->RegisterPropertyBoolean('ChangeNightModeColorLowerLightUnit', true);
        $this->RegisterPropertyInteger('NightModeColorLowerLightUnit', 0);
        $this->RegisterPropertyBoolean('ChangeNightModeBrightnessLowerLightUnit', false);
        $this->RegisterPropertyInteger('NightModeBrightnessLowerLightUnit', 0);
        $this->RegisterPropertyBoolean('UseAutomaticNightMode', false);
        $this->RegisterPropertyString('NightModeStartTime', '{"hour":22,"minute":0,"second":0}');
        $this->RegisterPropertyString('NightModeEndTime', '{"hour":6,"minute":0,"second":0}');

        // Variables
        // Upper light unit color
        $profile = 'SAHMIPBSL.' . $this->InstanceID . '.Color';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', 'Bulb', 0);
        IPS_SetVariableProfileAssociation($profile, 1, 'Blau', 'Bulb', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 2, 'Gr??n', 'Bulb', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, 'T??rkis', 'Bulb', 0x01DFD7);
        IPS_SetVariableProfileAssociation($profile, 4, 'Rot', 'Bulb', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 5, 'Violett', 'Bulb', 0xB40486);
        IPS_SetVariableProfileAssociation($profile, 6, 'Gelb', 'Bulb', 0xFFFF00);
        IPS_SetVariableProfileAssociation($profile, 7, 'Wei??', 'Bulb', 0xFFFFFF);
        $id = @$this->GetIDForIdent('UpperLightUnitColor');
        $this->RegisterVariableInteger('UpperLightUnitColor', 'Obere Leuchteinheit', $profile, 10);
        $this->EnableAction('UpperLightUnitColor');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('UpperLightUnitColor'), 'Bulb');
        }
        // Upper light unit brightness
        $this->RegisterVariableInteger('UpperLightUnitBrightness', 'Helligkeit', '~Intensity.100', 20);
        $this->EnableAction('UpperLightUnitBrightness');
        // Lower light unit color
        $id = @$this->GetIDForIdent('LowerLightUnitColor');
        $profile = 'SAHMIPBSL.' . $this->InstanceID . '.Color';
        $this->RegisterVariableInteger('LowerLightUnitColor', 'Untere Leuchteinheit', $profile, 30);
        $this->EnableAction('LowerLightUnitColor');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('LowerLightUnitColor'), 'Bulb');
        }
        // Lower light unit brightness
        $this->RegisterVariableInteger('LowerLightUnitBrightness', 'Helligkeit', '~Intensity.100', 40);
        $this->EnableAction('LowerLightUnitBrightness');
        // Night mode
        $id = @$this->GetIDForIdent('NightMode');
        $this->RegisterVariableBoolean('NightMode', 'Nachtmodus', '~Switch', 50);
        $this->EnableAction('NightMode');
        if ($id == false) {
            IPS_SetIcon($this->GetIDForIdent('NightMode'), 'Moon');
        }

        // Attributes
        $this->RegisterAttributeInteger('UpperLightUnitLastColor', 0);
        $this->RegisterAttributeInteger('UpperLightUnitLastBrightness', 0);
        $this->RegisterAttributeInteger('LowerLightUnitLastColor', 0);
        $this->RegisterAttributeInteger('LowerLightUnitLastBrightness', 0);

        // Timers
        $this->RegisterTimer('StartNightMode', 0, 'SAHMIPBSL_StartNightMode(' . $this->InstanceID . ');');
        $this->RegisterTimer('StopNightMode', 0, 'SAHMIPBSL_StopNightMode(' . $this->InstanceID . ',);');
    }

    public function ApplyChanges()
    {
        // Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        // Never delete this line!
        parent::ApplyChanges();

        // Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        // Options
        IPS_SetHidden($this->GetIDForIdent('UpperLightUnitColor'), !$this->ReadPropertyBoolean('EnableUpperLightUnitColor'));
        IPS_SetHidden($this->GetIDForIdent('UpperLightUnitBrightness'), !$this->ReadPropertyBoolean('EnableUpperLightUnitBrightness'));
        IPS_SetHidden($this->GetIDForIdent('LowerLightUnitColor'), !$this->ReadPropertyBoolean('EnableLowerLightUnitColor'));
        IPS_SetHidden($this->GetIDForIdent('LowerLightUnitBrightness'), !$this->ReadPropertyBoolean('EnableLowerLightUnitBrightness'));
        IPS_SetHidden($this->GetIDForIdent('NightMode'), !$this->ReadPropertyBoolean('EnableNightMode'));

        // Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        // Delete all registrations
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
            }
        }

        // Validation
        if (!$this->ValidateConfiguration()) {
            return;
        }

        // Register references and update messages
        $properties = ['UpperLightUnit', 'LowerLightUnit'];
        foreach ($properties as $property) {
            $id = $this->ReadPropertyInteger($property);
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $this->RegisterReference($id);
            }
        }
        $properties = ['UpperLightUnitTriggerVariables', 'LowerLightUnitTriggerVariables'];
        foreach ($properties as $property) {
            $variables = json_decode($this->ReadPropertyString($property));
            foreach ($variables as $variable) {
                if ($variable->Use) {
                    if ($variable->ID != 0 && @IPS_ObjectExists($variable->ID)) {
                        $this->RegisterReference($variable->ID);
                        $this->RegisterMessage($variable->ID, VM_UPDATE);
                    }
                }
            }
        }

        $this->SetNightModeTimer();
        if (!$this->CheckNightModeTimer()) {
            $this->WriteAttributeInteger('UpperLightUnitLastColor', 0);
            $this->WriteAttributeInteger('UpperLightUnitLastBrightness', 0);
            $this->WriteAttributeInteger('LowerLightUnitLastColor', 0);
            $this->WriteAttributeInteger('LowerLightUnitLastBrightness', 0);
            $this->CheckActualStatus();
        }
    }

    public function Destroy()
    {
        // Never delete this line!
        parent::Destroy();

        // Delete profiles
        $profiles = ['Color'];
        foreach ($profiles as $profile) {
            $profileName = 'SAHMIPBSL.' . $this->InstanceID . '.' . $profile;
            if (IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case VM_UPDATE:

                // $Data[0] = actual value
                // $Data[1] = value changed
                // $Data[2] = last value
                // $Data[3] = timestamp actual value
                // $Data[4] = timestamp value changed
                // $Data[5] = timestamp last value

                if ($Data[1]) {
                    $scriptText = 'SAHMIPBSL_CheckActualStatus(' . $this->InstanceID . ');';
                    IPS_RunScriptText($scriptText);
                }
                break;

        }
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        // Upper light unit
        $id = $this->ReadPropertyInteger('UpperLightUnit');
        $enabled = false;
        if ($id != 0 && @IPS_ObjectExists($id)) {
            $enabled = true;
        }
        $formData['elements'][1]['items'][0] = [
            'type'  => 'RowLayout',
            'items' => [$formData['elements'][1]['items'][0]['items'][0] = [
                'type'    => 'SelectInstance',
                'name'    => 'UpperLightUnit',
                'caption' => 'HmIP-BSL Instanz, Kanal 8',
                'width'   => '600px',
            ],
                $formData['elements'][1]['items'][0]['items'][1] = [
                    'type'    => 'Label',
                    'caption' => ' ',
                    'visible' => $enabled
                ],
                $formData['elements'][1]['items'][0]['items'][2] = [
                    'type'     => 'OpenObjectButton',
                    'caption'  => 'ID ' . $id . ' konfigurieren',
                    'visible'  => $enabled,
                    'objectID' => $id
                ]
            ]
        ];
        $formData['elements'][1]['items'][1] = [
            'type'    => 'NumberSpinner',
            'name'    => 'UpperLightUnitSwitchingDelay',
            'caption' => 'Schaltverz??gerung',
            'minimum' => 0,
            'suffix'  => 'Millisekunden'
        ];
        // Trigger variables upper light unit
        $variables = json_decode($this->ReadPropertyString('UpperLightUnitTriggerVariables'));
        if (!empty($variables)) {
            foreach ($variables as $variable) {
                $rowColor = '#C0FFC0'; # light green
                $use = $variable->Use;
                if (!$use) {
                    $rowColor = '';
                }
                $id = $variable->ID;
                if (@!IPS_ObjectExists($id)) {
                    $rowColor = '#FFC0C0'; # red
                }
                $formData['elements'][2]['items'][0]['values'][] = [
                    'Use'          => $use,
                    'Group'        => $variable->Group,
                    'ID'           => $id,
                    'TriggerType'  => $variable->TriggerType,
                    'TriggerValue' => $variable->TriggerValue,
                    'Color'        => $variable->Color,
                    'Brightness'   => $variable->Brightness,
                    'rowColor'     => $rowColor];
            }
        }
        // Lower light unit
        $id = $this->ReadPropertyInteger('LowerLightUnit');
        $enabled = false;
        if ($id != 0 && @IPS_ObjectExists($id)) {
            $enabled = true;
        }
        $formData['elements'][3]['items'][0] = [
            'type'  => 'RowLayout',
            'items' => [$formData['elements'][3]['items'][0]['items'][0] = [
                'type'    => 'SelectInstance',
                'name'    => 'LowerLightUnit',
                'caption' => 'HmIP-BSL Instanz, Kanal 12',
                'width'   => '600px',
            ],
                $formData['elements'][3]['items'][0]['items'][1] = [
                    'type'    => 'Label',
                    'caption' => ' ',
                    'visible' => $enabled
                ],
                $formData['elements'][3]['items'][0]['items'][2] = [
                    'type'     => 'OpenObjectButton',
                    'caption'  => 'ID ' . $id . ' konfigurieren',
                    'visible'  => $enabled,
                    'objectID' => $id
                ]
            ]
        ];
        $formData['elements'][3]['items'][1] = [
            'type'    => 'NumberSpinner',
            'name'    => 'LowerLightUnitSwitchingDelay',
            'caption' => 'Schaltverz??gerung',
            'minimum' => 0,
            'suffix'  => 'Millisekunden'
        ];
        // Trigger variables lower light unit
        $variables = json_decode($this->ReadPropertyString('LowerLightUnitTriggerVariables'));
        if (!empty($variables)) {
            foreach ($variables as $variable) {
                $rowColor = '#C0FFC0'; # light green
                $use = $variable->Use;
                if (!$use) {
                    $rowColor = '';
                }
                $id = $variable->ID;
                if (@!IPS_ObjectExists($id)) {
                    $rowColor = '#FFC0C0'; # red
                }
                $formData['elements'][4]['items'][0]['values'][] = [
                    'Use'          => $use,
                    'Group'        => $variable->Group,
                    'ID'           => $id,
                    'TriggerType'  => $variable->TriggerType,
                    'TriggerValue' => $variable->TriggerValue,
                    'Color'        => $variable->Color,
                    'Brightness'   => $variable->Brightness,
                    'rowColor'     => $rowColor];
            }
        }
        // Registered messages
        $messages = $this->GetMessageList();
        foreach ($messages as $senderID => $messageID) {
            $senderName = 'Objekt #' . $senderID . ' existiert nicht';
            $rowColor = '#FFC0C0'; # red
            if (@IPS_ObjectExists($senderID)) {
                $senderName = IPS_GetName($senderID);
                $rowColor = '#C0FFC0'; # light green
            }
            switch ($messageID) {
                case [10001]:
                    $messageDescription = 'IPS_KERNELSTARTED';
                    break;

                case [10603]:
                    $messageDescription = 'VM_UPDATE';
                    break;

                default:
                    $messageDescription = 'keine Bezeichnung';
            }
            $formData['actions'][1]['items'][0]['values'][] = [
                'SenderID'           => $senderID,
                'SenderName'         => $senderName,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription,
                'rowColor'           => $rowColor];
        }
        // Status
        $formData['status'][0] = [
            'code'    => 101,
            'icon'    => 'active',
            'caption' => 'Statusanzeige HmIP-BSL wird erstellt',
        ];
        $formData['status'][1] = [
            'code'    => 102,
            'icon'    => 'active',
            'caption' => 'Statusanzeige HmIP-BSL ist aktiv (ID ' . $this->InstanceID . ')',
        ];
        $formData['status'][2] = [
            'code'    => 103,
            'icon'    => 'active',
            'caption' => 'Statusanzeige HmIP-BSL wird gel??scht (ID ' . $this->InstanceID . ')',
        ];
        $formData['status'][3] = [
            'code'    => 104,
            'icon'    => 'inactive',
            'caption' => 'Statusanzeige HmIP-BSL ist inaktiv (ID ' . $this->InstanceID . ')',
        ];
        $formData['status'][4] = [
            'code'    => 200,
            'icon'    => 'inactive',
            'caption' => 'Es ist Fehler aufgetreten, weitere Informationen unter Meldungen, im Log oder Debug! (ID ' . $this->InstanceID . ')',
        ];
        return json_encode($formData);
    }

    public function ReloadConfiguration()
    {
        $this->ReloadForm();
    }

    public function EnableUpperLightUnitTriggerVariableConfigurationButton(int $ObjectID): void
    {
        $this->UpdateFormField('UpperLightUnitTriggerVariableConfigurationButton', 'caption', 'Variable ' . $ObjectID . ' Bearbeiten');
        $this->UpdateFormField('UpperLightUnitTriggerVariableConfigurationButton', 'visible', true);
        $this->UpdateFormField('UpperLightUnitTriggerVariableConfigurationButton', 'enabled', true);
        $this->UpdateFormField('UpperLightUnitTriggerVariableConfigurationButton', 'objectID', $ObjectID);
    }

    public function EnableLowerLightUnitTriggerVariableConfigurationButton(int $ObjectID): void
    {
        $this->UpdateFormField('LowerLightUnitTriggerVariableConfigurationButton', 'caption', 'Variable ' . $ObjectID . ' Bearbeiten');
        $this->UpdateFormField('LowerLightUnitTriggerVariableConfigurationButton', 'visible', true);
        $this->UpdateFormField('LowerLightUnitTriggerVariableConfigurationButton', 'enabled', true);
        $this->UpdateFormField('LowerLightUnitTriggerVariableConfigurationButton', 'objectID', $ObjectID);
    }

    public function ShowVariableDetails(int $VariableID): void
    {
        if ($VariableID == 0 || !@IPS_ObjectExists($VariableID)) {
            return;
        }
        if ($VariableID != 0) {
            // Variable
            echo 'ID: ' . $VariableID . "\n";
            echo 'Name: ' . IPS_GetName($VariableID) . "\n";
            $variable = IPS_GetVariable($VariableID);
            if (!empty($variable)) {
                $variableType = $variable['VariableType'];
                switch ($variableType) {
                    case 0:
                        $variableTypeName = 'Boolean';
                        break;

                    case 1:
                        $variableTypeName = 'Integer';
                        break;

                    case 2:
                        $variableTypeName = 'Float';
                        break;

                    case 3:
                        $variableTypeName = 'String';
                        break;

                    default:
                        $variableTypeName = 'Unbekannt';
                }
                echo 'Variablentyp: ' . $variableTypeName . "\n";
            }
            // Profile
            $profile = @IPS_GetVariableProfile($variable['VariableProfile']);
            if (empty($profile)) {
                $profile = @IPS_GetVariableProfile($variable['VariableCustomProfile']);
            }
            if (!empty($profile)) {
                $profileType = $variable['VariableType'];
                switch ($profileType) {
                    case 0:
                        $profileTypeName = 'Boolean';
                        break;

                    case 1:
                        $profileTypeName = 'Integer';
                        break;

                    case 2:
                        $profileTypeName = 'Float';
                        break;

                    case 3:
                        $profileTypeName = 'String';
                        break;

                    default:
                        $profileTypeName = 'Unbekannt';
                }
                echo 'Profilname: ' . $profile['ProfileName'] . "\n";
                echo 'Profiltyp: ' . $profileTypeName . "\n\n";
            }
            if (!empty($variable)) {
                echo "\nVariable:\n";
                print_r($variable);
            }
            if (!empty($profile)) {
                echo "\nVariablenprofil:\n";
                print_r($profile);
            }
        }
    }

    #################### Request Action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'UpperLightUnitColor':
                $this->SetColor(0, $Value);
                break;

            case 'UpperLightUnitBrightness':
                $this->SetBrightness(0, $Value);
                break;

            case 'LowerLightUnitColor':
                $this->SetColor(1, $Value);
                break;

            case 'LowerLightUnitBrightness':
                $this->SetBrightness(1, $Value);
                break;

            case 'NightMode':
                $this->ToggleNightMode($Value);
                break;

        }
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    private function ValidateConfiguration(): bool
    {
        $result = true;
        $status = 102;
        // Maintenance mode
        $maintenance = $this->CheckMaintenanceMode();
        if ($maintenance) {
            $result = false;
            $status = 104;
        }
        IPS_SetDisabled($this->InstanceID, $maintenance);
        $this->SetStatus($status);
        return $result;
    }

    private function CheckMaintenanceMode(): bool
    {
        $result = $this->ReadPropertyBoolean('MaintenanceMode');
        if ($result) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Wartungsmodus ist aktiv!', 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', Abbruch, der Wartungsmodus ist aktiv!', KL_WARNING);
        }
        return $result;
    }
}