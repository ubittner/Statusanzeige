<?php

/**
 * @project       Statusanzeige/StatusanzeigeHomematicIP/helper
 * @file          SAHMIP_Signaling.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpSwitchStatementWitSingleBranchInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait SAHMIP_Signaling
{
    /**
     * Sets the color and brightness for the light unit.
     *
     * @param int $LightUnit
     * 0 =  Upper light unit,
     * 1 =  Lower light unit
     *
     * @param int $Color
     * 0 =  black / off,
     * 1 =  blue,
     * 2 =  green,
     * 3 =  turquoise,
     * 4 =  red,
     * 5 =  violet,
     * 6 =  yellow,
     * 7 =  white,
     *
     * @param int $Brightness
     *
     * @param bool $ForceSignaling
     * false =  use configuration,
     * true =   always set color and brightness
     *
     * @return bool
     * false =  an error occurred,
     * true =   successful
     *
     * @throws Exception
     */
    public function SetDeviceSignaling(int $LightUnit, int $Color, int $Brightness, bool $ForceSignaling = false): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird  ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Leuchteinheit: ' . $LightUnit, 0);
        $this->SendDebug(__FUNCTION__, 'Farbe: ' . $Color, 0);
        $this->SendDebug(__FUNCTION__, 'Helligkeit: ' . $Brightness, 0);
        $this->SendDebug(__FUNCTION__, 'Forcieren: ' . json_encode($ForceSignaling), 0);
        if ($this->CheckMaintenance()) {
            return false;
        }
        $result = true;
        $color = $this->SetColor($LightUnit, $Color, $ForceSignaling);
        $brightness = $this->SetBrightness($LightUnit, $Brightness, $ForceSignaling);
        if (!$color || !$brightness) {
            $result = false;
        }
        return $result;
    }

    /**
     * Updates the light units.
     *
     * @param bool $ForceSignaling
     * false =  use configuration,
     * true =   force signaling
     *
     * @return void
     * @throws Exception
     */
    public function UpdateLightUnits(bool $ForceSignaling): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Forcieren: ' . json_encode($ForceSignaling), 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        //Upper light unit
        if ($this->ValidateTriggerList(0)) {
            $this->UpdateUpperLightUnit($ForceSignaling);
        } else {
            $this->UpdateColorFromDeviceColor(0);
            $this->UpdateBrightnessFromDeviceLevel(0);
        }
        //Lower light unit
        if ($this->ValidateTriggerList(1)) {
            $this->UpdateLowerLightUnit($ForceSignaling);
        } else {
            $this->UpdateColorFromDeviceColor(1);
            $this->UpdateBrightnessFromDeviceLevel(1);
        }
        //Set check status timer
        $milliseconds = $this->ReadPropertyInteger('CheckStatusInterval');
        if ($milliseconds > 0) {
            $milliseconds = $milliseconds * 1000;
        }
        $this->SetTimerInterval('CheckStatus', $milliseconds);
    }

    /**
     * Updates the color from the actual device color.
     *
     * @param int $LightUnit
     * 0 =  Upper light unit,
     * 1 =  Lower light unit
     *
     * @return void
     * @throws Exception
     */
    public function UpdateColorFromDeviceColor(int $LightUnit): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Leuchteinheit: ' . $LightUnit, 0);
        $deviceColorName = 'UpperLightUnitDeviceColor';
        $lightUnitColor = 'UpperLightUnitColor';
        if ($LightUnit == 1) {
            $deviceColorName = 'LowerLightUnitDeviceColor';
            $lightUnitColor = 'LowerLightUnitColor';
        }
        $id = $this->ReadPropertyInteger($deviceColorName);
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $this->SendDebug(__FUNCTION__, 'Variable ID: ' . $id, 0);
            $color = GetValueInteger($id);
            $this->SendDebug(__FUNCTION__, 'Farbe: ' . $color, 0);
            $actualColor = $this->GetValue($lightUnitColor);
            //Set values, changes only
            if ($actualColor != $color) {
                $this->SetValue($lightUnitColor, $color);
            }
        }
    }

    /**
     * Updates the brightness from the actual device level.
     *
     * @param int $LightUnit
     * 0 =  Upper light unit,
     * 1 =  Lower light unit
     *
     * @return void
     * @throws Exception
     */
    public function UpdateBrightnessFromDeviceLevel(int $LightUnit): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Leuchteinheit: ' . $LightUnit, 0);
        $deviceBrightnessName = 'UpperLightUnitDeviceBrightness';
        $lightUnitBrightness = 'UpperLightUnitBrightness';
        if ($LightUnit == 1) {
            $deviceBrightnessName = 'LowerLightUnitDeviceBrightness';
            $lightUnitBrightness = 'LowerLightUnitBrightness';
        }
        $id = $this->ReadPropertyInteger($deviceBrightnessName);
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $this->SendDebug(__FUNCTION__, 'Variable ID: ' . $id, 0);
            $brightness = GetValueFloat($id) * 100;
            $this->SendDebug(__FUNCTION__, 'Helligkeit: ' . $brightness, 0);
            $actualBrightness = $this->GetValue($lightUnitBrightness);
            //Set values, changes only
            if ($actualBrightness != $brightness) {
                $this->SetValue($lightUnitBrightness, $brightness);
            }
        }
    }

    /**
     * Updates the color and the brightness for the upper light unit from trigger list.
     *
     * @param bool $ForceSignaling
     * false =  use configuration,
     * true =   always set color and brightness
     *
     * @return void
     * @throws Exception
     */
    public function UpdateUpperLightUnit(bool $ForceSignaling): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Forcieren: ' . json_encode($ForceSignaling), 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $this->CheckTriggerConditions(0, $ForceSignaling);
    }

    /**
     * Updates the color and the brightness for the lower light unit from trigger list.
     *
     * @param bool $ForceSignaling
     *  false =  use configuration,
     *  true =   always set color and brightness
     *
     * @return void
     * @throws Exception
     */
    public function UpdateLowerLightUnit(bool $ForceSignaling): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Forcieren: ' . json_encode($ForceSignaling), 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $this->CheckTriggerConditions(1, $ForceSignaling);
    }

    #################### Private

    /**
     * Sets the device color of a light unit.
     *
     * @param int $LightUnit
     * 0 =  Upper light unit,
     * 1 =  Lower light unit
     *
     * @param int $Color
     * 0 =  black / off,
     * 1 =  blue,
     * 2 =  green,
     * 3 =  turquoise,
     * 4 =  red,
     * 5 =  violet,
     * 6 =  yellow,
     * 7 =  white,
     *
     * @param bool $ForceColor
     * false =  use configuration,
     * true =   always set color
     *
     * @return bool
     * false =  an error occurred
     * true =   successful
     *
     * @throws Exception
     */
    private function SetColor(int $LightUnit, int $Color, bool $ForceColor = false): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird  ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Leuchteinheit: ' . $LightUnit, 0);
        $this->SendDebug(__FUNCTION__, 'Farbe: ' . $Color, 0);
        $this->SendDebug(__FUNCTION__, 'Forcieren: ' . json_encode($ForceColor), 0);
        $result = false;
        //Upper light unit
        if ($LightUnit == 0) {
            $actualColor = $this->GetValue('UpperLightUnitColor');
            //Set values, changes only
            if ($actualColor != $Color) {
                $this->SetValue('UpperLightUnitColor', $Color);
            }
            if (!$ForceColor) {
                if ($actualColor == $Color) {
                    $this->SendDebug(__FUNCTION__, 'Es wird bereits der gleiche Farbwert angezeigt!', 0);
                    return true;
                }
            }
            $id = $this->ReadPropertyInteger('UpperLightUnit');
            if ($id > 1 && @IPS_ObjectExists($id)) {
                switch ($this->ReadPropertyInteger('UpperLightUnitDeviceType')) {
                    case 1: //HmIP-BSL Channel 8
                    case 2: //HmIP-MP3P Channel 6
                        $commandControl = $this->ReadPropertyInteger('CommandControl');
                        if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                            $commands = [];
                            $commands[] = '@HM_WriteValueInteger(' . $id . ", 'COLOR', '" . $Color . "');";
                            $this->SendDebug(__FUNCTION__, 'Befehle: ' . json_encode(json_encode($commands)), 0);
                            $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                            $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                            $result = @IPS_RunScriptText($scriptText);
                        } else {
                            $this->SendDebug(__FUNCTION__, 'Befehl: @HM_WriteValueInteger(' . $id . ", 'COLOR', " . $Color . ');', 0);
                            IPS_Sleep($this->ReadPropertyInteger('UpperLightUnitSwitchingDelay'));
                            $result = @HM_WriteValueInteger($id, 'COLOR', $Color);
                            if (!$result) {
                                IPS_Sleep($this->ReadPropertyInteger('UpperLightUnitSwitchingDelay'));
                                $result = @HM_WriteValueInteger($id, 'COLOR', $Color);
                            }
                        }
                        if ($result) {
                            $this->SendDebug(__FUNCTION__, 'Der Farbwert ' . $Color . ' wurde für die obere Leuchteinheit ID ' . $id . ' eingestellt.', 0);
                        } else {
                            $this->SendDebug(__FUNCTION__, 'Abbruch, der Farbwert ' . $Color . ' konnte für die obere Leuchteinheit ID ' . $id . ' nicht eingestellt werden!', 0);
                            //Revert color
                            $this->SetValue('UpperLightUnitColor', $actualColor);
                        }
                        break;

                }
            }
        }
        //Lower light unit
        if ($LightUnit == 1) {
            $actualColor = $this->GetValue('LowerLightUnitColor');
            //Set values, changes only
            if ($actualColor != $Color) {
                $this->SetValue('LowerLightUnitColor', $Color);
            }
            if (!$ForceColor) {
                if ($actualColor == $Color) {
                    $this->SendDebug(__FUNCTION__, 'Es wird bereits der gleiche Farbwert angezeigt!', 0);
                    return true;
                }
            }
            $id = $this->ReadPropertyInteger('LowerLightUnit');
            if ($id > 1 && @IPS_ObjectExists($id)) {
                switch ($this->ReadPropertyInteger('LowerLightUnitDeviceType')) {
                    case 1: //HmIP-BSL Channel 12
                        $commandControl = $this->ReadPropertyInteger('CommandControl');
                        if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                            $commands = [];
                            $commands[] = '@HM_WriteValueInteger(' . $id . ", 'COLOR', '" . $Color . "');";
                            $this->SendDebug(__FUNCTION__, 'Befehle: ' . json_encode(json_encode($commands)), 0);
                            $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                            $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                            $result = @IPS_RunScriptText($scriptText);
                        } else {
                            $this->SendDebug(__FUNCTION__, 'Befehl: @HM_WriteValueInteger(' . $id . ", 'COLOR', " . $Color . ');', 0);
                            IPS_Sleep($this->ReadPropertyInteger('LowerLightUnitSwitchingDelay'));
                            $result = @HM_WriteValueInteger($id, 'COLOR', $Color);
                            if (!$result) {
                                IPS_Sleep($this->ReadPropertyInteger('LowerLightUnitSwitchingDelay'));
                                $result = @HM_WriteValueInteger($id, 'LEVEL', $Color);
                            }
                        }
                        if ($result) {
                            $this->SendDebug(__FUNCTION__, 'Der Farbwert ' . $Color . ' wurde für die untere Leuchteinheit ID ' . $id . ' eingestellt.', 0);
                        } else {
                            $this->SendDebug(__FUNCTION__, 'Abbruch, der Farbwert ' . $Color . ' konnte für die untere Leuchteinheit ID ' . $id . ' nicht eingestellt werden!', 0);
                            //Revert color
                            $this->SetValue('LowerLightUnitBrightness', $actualColor);
                        }
                        break;

                }
            }
        }
        return $result;
    }

    /**
     * Sets the device brightness of a light unit.
     *
     * @param int $LightUnit
     * 0 =  Upper light unit,
     * 1 =  Lower light unit
     *
     * @param int $Brightness
     *
     * @param bool $ForceBrightness
     * false =  use configuration,
     * true =   always set color
     *
     * @return bool
     * false =  an error occurred
     * true =   successful
     *
     * @throws Exception
     */
    private function SetBrightness(int $LightUnit, int $Brightness, bool $ForceBrightness = false): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird  ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Leuchteinheit: ' . $LightUnit, 0);
        $this->SendDebug(__FUNCTION__, 'Helligkeit: ' . $Brightness, 0);
        $this->SendDebug(__FUNCTION__, 'Forcieren: ' . json_encode($ForceBrightness), 0);
        $result = false;
        //Upper light unit
        if ($LightUnit == 0) {
            $actualBrightness = $this->GetValue('UpperLightUnitBrightness');
            //Set values, changes only
            if ($actualBrightness != $Brightness) {
                $this->SetValue('UpperLightUnitBrightness', $Brightness);
            }
            if (!$ForceBrightness) {
                if ($actualBrightness == $Brightness) {
                    $this->SendDebug(__FUNCTION__, 'Es wird bereits die gleiche Helligkeit verwendet!', 0);
                    return true;
                }
            }
            $deviceBrightness = $this->GetValue('UpperLightUnitBrightness') / 100;
            $id = $this->ReadPropertyInteger('UpperLightUnit');
            if ($id > 1 && @IPS_ObjectExists($id)) {
                switch ($this->ReadPropertyInteger('UpperLightUnitDeviceType')) {
                    case 1: //HmIP-BSL Channel 8
                    case 2: //HmIP-MP3P Channel 6
                        $commandControl = $this->ReadPropertyInteger('CommandControl');
                        if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                            $commands = [];
                            $commands[] = '@HM_WriteValueFloat(' . $id . ", 'LEVEL', '" . $deviceBrightness . "');";
                            $this->SendDebug(__FUNCTION__, 'Befehle: ' . json_encode(json_encode($commands)), 0);
                            $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                            $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                            $result = @IPS_RunScriptText($scriptText);
                        } else {
                            $this->SendDebug(__FUNCTION__, 'Befehl: @HM_WriteValueFloat(' . $id . ", 'LEVEL', " . $deviceBrightness . ');', 0);
                            IPS_Sleep($this->ReadPropertyInteger('UpperLightUnitSwitchingDelay'));
                            $result = @HM_WriteValueFloat($id, 'LEVEL', $deviceBrightness);
                            if (!$result) {
                                IPS_Sleep($this->ReadPropertyInteger('UpperLightUnitSwitchingDelay'));
                                $result = @HM_WriteValueFloat($id, 'LEVEL', $deviceBrightness);
                            }
                        }
                        if ($result) {
                            $this->SendDebug(__FUNCTION__, 'Der Helligkeitswert ' . $deviceBrightness . ' wurde für die obere Leuchteinheit ID ' . $id . ' eingestellt.', 0);
                        } else {
                            $this->SendDebug(__FUNCTION__, 'Abbruch, der Helligkeitswert ' . $deviceBrightness . ' konnte für die obere Leuchteinheit ID ' . $id . ' nicht eingestellt werden!', 0);
                            //Revert brightness
                            $this->SetValue('UpperLightUnitBrightness', $actualBrightness);
                        }
                       break;
                }
            }
        }
        //Lower light unit
        if ($LightUnit == 1) {
            $actualBrightness = $this->GetValue('LowerLightUnitBrightness');
            //Set values, changes only
            if ($actualBrightness != $Brightness) {
                $this->SetValue('LowerLightUnitBrightness', $Brightness);
            }
            if (!$ForceBrightness) {
                if ($actualBrightness == $Brightness) {
                    $this->SendDebug(__FUNCTION__, 'Es wird bereits die gleiche Helligkeit verwendet!', 0);
                    return true;
                }
            }
            $deviceBrightness = $this->GetValue('LowerLightUnitBrightness') / 100;
            $id = $this->ReadPropertyInteger('LowerLightUnit');
            if ($id > 1 && @IPS_ObjectExists($id)) {
                switch ($this->ReadPropertyInteger('LowerLightUnitDeviceType')) {
                    case 1: //HmIP-BSL Channel 12
                        $commandControl = $this->ReadPropertyInteger('CommandControl');
                        if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                            $commands = [];
                            $commands[] = '@HM_WriteValueFloat(' . $id . ", 'LEVEL', '" . $deviceBrightness . "');";
                            $this->SendDebug(__FUNCTION__, 'Befehle: ' . json_encode(json_encode($commands)), 0);
                            $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                            $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                            $result = @IPS_RunScriptText($scriptText);
                        } else {
                            $this->SendDebug(__FUNCTION__, 'Befehl: @HM_WriteValueFloat(' . $id . ", 'LEVEL', " . $deviceBrightness . ');', 0);
                            IPS_Sleep($this->ReadPropertyInteger('LowerLightUnitSwitchingDelay'));
                            $result = @HM_WriteValueFloat($id, 'LEVEL', $deviceBrightness);
                            if (!$result) {
                                IPS_Sleep($this->ReadPropertyInteger('LowerLightUnitSwitchingDelay'));
                                $result = @HM_WriteValueFloat($id, 'LEVEL', $deviceBrightness);
                            }
                        }
                        if ($result) {
                            $this->SendDebug(__FUNCTION__, 'Der Helligkeitswert ' . $deviceBrightness . ' wurde für die untere Leuchteinheit ID ' . $id . ' eingestellt.', 0);
                        } else {
                            $this->SendDebug(__FUNCTION__, 'Abbruch, der Helligkeitswert ' . $deviceBrightness . ' konnte für die untere Leuchteinheit ID ' . $id . ' nicht eingestellt werden!', 0);
                            //Revert brightness
                            $this->SetValue('LowerLightUnitBrightness', $actualBrightness);
                        }
                        break;
                }
            }
        }
        return $result;
    }
}