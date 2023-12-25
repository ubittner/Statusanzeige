<?php

/**
 * @project       Statusanzeige/Statusanzeige/helper/
 * @file          SA_Signaling.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait SA_Signaling
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
        //Off
        if (!$State) {
            $result = $this->SetSignalling(false, $ForceSignaling);
            IPS_Sleep(250);
            $this->SetInvertedSignalling(true, $ForceSignaling);
        }
        //On
        else {
            $this->SetInvertedSignalling(false, $ForceSignaling);
            IPS_Sleep(250);
            $result = $this->SetSignalling(true, $ForceSignaling);
        }
        return $result;
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
        foreach ($variables as $key => $variable) {
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
            $this->SendDebug(__FUNCTION__, 'Listenschlüssel: ' . $key, 0);
            if (!$execute) {
                $this->SendDebug(__FUNCTION__, 'Abbruch, die Bedingungen wurden nicht erfüllt!', 0);
            } else {
                $this->SendDebug(__FUNCTION__, 'Die Bedingungen wurden erfüllt.', 0);
                //Signalling
                if ($ForceSignaling) {
                    $force = true;
                } else {
                    $force = false;
                    if (isset($variable['ForceSignaling'])) {
                        $force = $variable['ForceSignaling'];
                    }
                }
                $this->SendDebug(__FUNCTION__, 'Signalisierung erzwingen: ' . json_encode($force), 0);
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
     * Updates the state from the target signaling variable.
     *
     * @return void
     * @throws Exception
     */
    public function UpdateStateFromTargetSignalingVariable(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $id = $this->ReadPropertyInteger('SignallingVariable');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $this->SendDebug(__FUNCTION__, 'Variable ID: ' . $id, 0);
            $variableState = (bool) GetValue($id);
            $this->SendDebug(__FUNCTION__, 'Status: ' . json_encode($variableState), 0);
            $actualState = $this->GetValue('Signalling');
            //Set values, changes only
            if ($variableState != $actualState) {
                $this->SendDebug(__FUNCTION__, 'Neuer Status: ' . json_encode($variableState), 0);
                $this->SetValue('Signalling', $variableState);
            }
        }
    }

    ########## Private

    /**
     * Sets the signalling.
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
        $id = $this->ReadPropertyInteger('SignallingVariable');
        if ($id > 1 && @IPS_ObjectExists($id)) {
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
            $commandControl = $this->ReadPropertyInteger('CommandControl');
            if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                $commands = [];
                $commands[] = '@RequestAction(' . $id . ', ' . $value . ');';
                $this->SendDebug(__FUNCTION__, 'Befehl: ' . json_encode(json_encode($commands)), 0);
                $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                $result = @IPS_RunScriptText($scriptText);
            } else {
                IPS_Sleep($this->ReadPropertyInteger('SignallingDelay'));
                @RequestAction($id, $State);
            }
            if (!$result) {
                //Revert
                $this->SetValue('Signalling', $actualValue);
                $this->SendDebug(__FUNCTION__, 'Die Signalisierung konnte für die Statusanzeige ID ' . $id . ' nicht erfolgreich geschaltet werden!', 0);
                $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', die invertierte Signalisierung konnte für die Statusanzeige ID  . ' . $id . ' nicht erfolgreich geschaltet werden!', KL_ERROR);
            }
        }
        return $result;
    }

    /**
     * Sets the inverted signalling.
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
    private function SetInvertedSignalling(bool $State, bool $ForceSignaling): bool
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
        $id = $this->ReadPropertyInteger('InvertedSignallingVariable');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            if (!$ForceSignaling) {
                $actualVariableValue = (bool) GetValue($id);
                if ($actualVariableValue == $State) {
                    $this->SendDebug(__FUNCTION__, 'Es wird bereits der gleiche invertierte Status angezeigt!', 0);
                    return true;
                }
            }
            $commandControl = $this->ReadPropertyInteger('CommandControl');
            if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                $commands = [];
                $commands[] = '@RequestAction(' . $id . ', ' . $value . ');';
                $this->SendDebug(__FUNCTION__, 'Befehl: ' . json_encode(json_encode($commands)), 0);
                $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                $result = @IPS_RunScriptText($scriptText);
            } else {
                IPS_Sleep($this->ReadPropertyInteger('InvertedSignallingDelay'));
                @RequestAction($id, $State);
            }
            if (!$result) {
                $this->SendDebug(__FUNCTION__, 'Die invertierte Signalisierung konnte für die Statusanzeige ID ' . $id . ' nicht erfolgreich geschaltet werden!', 0);
                $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', die invertierte Signalisierung konnte für die Statusanzeige ID  . ' . $id . ' nicht erfolgreich geschaltet werden!', KL_ERROR);
            }
        }
        return $result;
    }
}