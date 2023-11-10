<?php

/**
 * @project       Statusanzeige/StatusanzeigeHomematic/helper/
 * @file          SAHM_Signaling.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait SAHM_Signaling
{
    /**
     * Toggles the signalling.
     *
     * @param bool $State
     * false =  off,
     * true =   on
     *
     * @param bool $ForceSignaling
     * false =  changes only,
     * true =   always switch state
     *
     * @return bool
     * false =  an error occurred,
     * true =   successful
     *
     * @throws Exception
     */
    public function ToggleSignalling(bool $State, bool $ForceSignaling): bool
    {
        return $this->SetSignalling($State, $ForceSignaling);
    }

    /**
     * Updates the state.
     *
     * @param bool $ForceSignaling
     * false =  use configuration,
     * true =   always toggle
     *
     * @return void
     * @throws Exception
     */
    public function UpdateState(bool $ForceSignaling): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $execute = true;
            //Check primary condition
            if (!IPS_IsConditionPassing($variable['PrimaryCondition'])) {
                $execute = false;
            }
            //Check secondary condition
            if (!IPS_IsConditionPassing($variable['SecondaryCondition'])) {
                $execute = false;
            }
            if (!$execute) {
                $this->SendDebug(__FUNCTION__, 'Abbruch, die Bedingungen wurden nicht erfüllt!', 0);
            } else {
                $this->SendDebug(__FUNCTION__, 'Die Bedingungen wurden erfüllt.', 0);
                if ($ForceSignaling) {
                    $force = true;
                } else {
                    $force = false;
                    if (isset($variable['ForceSignaling'])) {
                        $force = $variable['ForceSignaling'];
                    }
                }
                $this->SendDebug(__FUNCTION__, 'Signalisierung forcieren: ' . json_encode($force), 0);
                //Signalling
                switch ($variable['Signalling']) {
                    case 0: //Off
                        $this->ToggleSignalling(false, $force);
                        break;

                    case 1: //On
                        $this->ToggleSignalling(true, $force);
                        break;

                }
            }
        }
    }

    /**
     * Updates the state from the device.
     *
     * @return void
     * @throws Exception
     */
    public function UpdateStateFromDevice(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $id = $this->ReadPropertyInteger('SignallingDeviceState');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $this->SendDebug(__FUNCTION__, 'Variable ID: ' . $id, 0);
            $deviceState = GetValueBoolean($id);
            $this->SendDebug(__FUNCTION__, 'Status: ' . json_encode($deviceState), 0);
            $actualState = $this->GetValue('Signalling');
            //Set values, changes only
            if ($deviceState != $actualState) {
                $this->SendDebug(__FUNCTION__, 'Neuer Status: ' . json_encode($deviceState), 0);
                $this->SetValue('Signalling', $deviceState);
            }
        }
    }

    #################### Private

    /**
     * Sets the signalling.
     *
     * @param bool $State
     * false =  off,
     * true =   on
     *
     * @param bool $ForceSignaling
     *  false =  changes only,
     *  true =   always switch state
     *
     * @return bool
     * false =  an error occurred,
     * true =   successful
     *
     * @throws Exception
     */
    private function SetSignalling(bool $State, bool $ForceSignaling): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $statusText = 'Aus';
        $value = 'false';
        if ($State) {
            $statusText = 'An';
            $value = 'true';
        }
        $this->SendDebug(__FUNCTION__, 'Status: ' . $statusText, 0);
        if ($this->CheckMaintenance()) {
            return false;
        }
        $result = false;
        $id = $this->ReadPropertyInteger('SignallingDeviceInstance');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $result = true;
            //Set values, changes only
            $actualValue = $this->GetValue('Signalling');
            if ($actualValue != $State) {
                $this->SetValue('Signalling', $State);
            }
            if (!$ForceSignaling) {
                if ($actualValue == $State) {
                    $this->SendDebug(__FUNCTION__, 'Es wird bereits der gleiche Status angezeigt!', 0);
                    return true;
                }
            }
            //HM-LC-Sw4-WM
            if ($this->ReadPropertyInteger('SignallingDeviceType') == 1) {
                $commandControl = $this->ReadPropertyInteger('CommandControl');
                if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                    $commands = [];
                    $commands[] = '@HM_WriteValueBoolean(' . $id . ", 'STATE', " . $value . ');';
                    $this->SendDebug(__FUNCTION__, 'Befehl: ' . json_encode(json_encode($commands)), 0);
                    $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                    $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                    $result = @IPS_RunScriptText($scriptText);
                } else {
                    IPS_Sleep($this->ReadPropertyInteger('SignallingDelay'));
                    $parameter = @HM_WriteValueBoolean($id, 'STATE', $State);
                    if (!$parameter) {
                        $this->SendDebug(__FUNCTION__, 'Bei der Signalisierung ist ein Fehler aufgetreten!', 0);
                        $this->SendDebug(__FUNCTION__, 'Der Schaltvorgang wird wiederholt.', 0);
                        IPS_Sleep($this->ReadPropertyInteger('SignallingDelay'));
                        $parameter = @HM_WriteValueBoolean($id, 'STATE', $State);
                        if (!$parameter) {
                            $result = false;
                        }
                    }
                }
                if (!$result) {
                    $this->SetValue('Signalling', $actualValue);
                    $this->SendDebug(__FUNCTION__, 'Die Signalisierung konnte für die Statusanzeige ID ' . $id . ' nicht erfolgreich geschaltet werden!', 0);
                    $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', die Signalisierung konnte für die Statusanzeige ID  . ' . $id . ' nicht erfolgreich geschaltet werden!', KL_ERROR);
                }
            }
        }
        return $result;
    }
}