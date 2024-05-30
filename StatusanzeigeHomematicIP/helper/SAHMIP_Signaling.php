<?php

/**
 * @project       Statusanzeige/StatusanzeigeHomematicIP/helper/
 * @file          SAHMIP_Signaling.php
 * @author        Ulrich Bittner
 * @copyright     2023,2024 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpSwitchStatementWitSingleBranchInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait SAHMIP_Signaling
{
    ########## Action

    /**
     * Sets the color.
     *
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower light unit
     *
     * @param int $Color
     * 0 =  black or off,
     * 1 =  blue,
     * 2 =  green,
     * 3 =  turquoise,
     * 4 =  red,
     * 5 =  violet,
     * 6 =  yellow,
     * 7 =  white
     *
     * @param bool $ForceColor
     * false =  use configuration,
     * true =   always set color on device
     *
     * @return bool
     * false =  an error occurred
     * true =   successful
     *
     * @throws Exception
     */
    public function SetColor(int $LightUnit, int $Color, bool $ForceColor = false): bool
    {
        if ($this->CheckMaintenance()) {
            return false;
        }

        $result = false;

        $unit = 'UpperLightUnit';
        $lightUnitDescription = 'obere Leuchteinheit';
        if ($LightUnit == 1) {
            $unit = 'LowerLightUnit';
            $lightUnitDescription = 'untere Leuchteinheit';
        }

        //Check device variable
        $id = $this->ReadPropertyInteger($unit . 'DeviceColor');
        if ($id <= 1 || @!IPS_ObjectExists($id)) {
            return false;
        }

        $this->SendDebug(__FUNCTION__, 'Einheit: ' . $LightUnit . ' = ' . $lightUnitDescription . ', Farbe: ' . $Color . ', Forcieren: ' . $ForceColor, 0);

        $actualColor = $this->GetValue($unit . 'Color');

        //Set values, changes only!
        if ($actualColor != $Color) {
            $this->SetValue($unit . 'Color', $Color);
        } else {
            //Verify device color
            $verifyColor = $this->VerifyDeviceColor($LightUnit, $Color);
            if (!$verifyColor) {
                $ForceColor = true;
            }
        }

        if (!$ForceColor) {
            if ($actualColor == $Color) {
                $this->SendDebug(__FUNCTION__, 'Es wird bereits der gleiche Farbwert verwendet!', 0);
                return true;
            }
        } else {
            $this->SendDebug(__FUNCTION__, 'Die Gerätesignalisierung wird erzwungen!', 0);
        }

        //Set color on device
        $id = $this->ReadPropertyInteger($unit);
        if ($id > 1 && @IPS_ObjectExists($id)) {
            switch ($this->ReadPropertyInteger($unit . 'DeviceType')) {
                case 1: //HmIP-BSL Channel 8
                case 2: //HmIP-MP3P Channel 6
                    $commandControl = $this->ReadPropertyInteger('CommandControl');
                    if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                        $commands = [];
                        $commands[] = '@HM_WriteValueInteger(' . $id . ", 'COLOR', '" . $Color . "');";
                        $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                        $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                        $result = @IPS_RunScriptText($scriptText);
                    } else {
                        IPS_Sleep($this->ReadPropertyInteger($unit . 'SwitchingDelay'));
                        $this->SendDebug(__FUNCTION__, 'Befehl: @HM_WriteValueInteger(' . $id . ", 'COLOR', " . $Color . ');', 0);
                        $result = @HM_WriteValueInteger($id, 'COLOR', $Color);
                        if (!$result) {
                            IPS_Sleep($this->ReadPropertyInteger($unit . 'SwitchingDelay'));
                            $result = @HM_WriteValueInteger($id, 'COLOR', $Color);
                        }
                    }
                    if ($result) {
                        $this->SendDebug(__FUNCTION__, 'Der Farbwert ' . $Color . ' wurde für die ' . $lightUnitDescription . ' (ID ' . $id . ') eingestellt.', 0);
                    } else {
                        $this->SendDebug(__FUNCTION__, 'Abbruch, der Farbwert ' . $Color . ' konnte für die ' . $lightUnitDescription . ' (ID ' . $id . ') nicht eingestellt werden!', 0);
                    }
                    break;

            }
        }

        return $result;
    }

    /**
     * Sets the brightness.
     *
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower light unit
     *
     * @param int $Brightness
     *
     * @param bool $ForceBrightness
     * false =  use configuration,
     * true =   always set brightness on device
     *
     * @return bool
     * false =  an error occurred
     * true =   successful
     *
     * @throws Exception
     */
    public function SetBrightness(int $LightUnit, int $Brightness, bool $ForceBrightness = false): bool
    {
        if ($this->CheckMaintenance()) {
            return false;
        }

        $result = false;

        $unit = 'UpperLightUnit';
        $lightUnitDescription = 'obere Leuchteinheit';
        if ($LightUnit == 1) {
            $unit = 'LowerLightUnit';
            $lightUnitDescription = 'untere Leuchteinheit';
        }

        //Check device variable
        $id = $this->ReadPropertyInteger($unit . 'DeviceBrightness');
        if ($id <= 1 || @!IPS_ObjectExists($id)) {
            return false;
        }

        $this->SendDebug(__FUNCTION__, 'Einheit: ' . $LightUnit . ' = ' . $lightUnitDescription . ', Helligkeit: ' . $Brightness . ', Forcieren: ' . $ForceBrightness, 0);

        $actualBrightness = $this->GetValue($unit . 'Brightness');

        //Set values, changes only!
        if ($actualBrightness != $Brightness) {
            $this->SetValue($unit . 'Brightness', $Brightness);
        } else {
            //Verify device brightness
            $verifiyBrightness = $this->VerifyDeviceBrightness($LightUnit, $Brightness);
            if (!$verifiyBrightness) {
                $ForceBrightness = true;
            }
        }

        if (!$ForceBrightness) {
            if ($actualBrightness == $Brightness) {
                $this->SendDebug(__FUNCTION__, 'Es wird bereits die gleiche Helligkeit verwendet!', 0);
                return true;
            }
        } else {
            $this->SendDebug(__FUNCTION__, 'Die Gerätesignalisierung wird erzwungen!', 0);
        }

        //Set brightness on device
        $id = $this->ReadPropertyInteger($unit);
        if ($id > 1 && @IPS_ObjectExists($id)) {
            switch ($this->ReadPropertyInteger($unit . 'DeviceType')) {
                case 1: //HmIP-BSL Channel 8
                case 2: //HmIP-MP3P Channel 6
                    $commandControl = $this->ReadPropertyInteger('CommandControl');
                    if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                        $commands = [];
                        $commands[] = '@HM_WriteValueFloat(' . $id . ", 'LEVEL', '" . $Brightness / 100 . "');";
                        $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                        $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                        $result = @IPS_RunScriptText($scriptText);
                    } else {
                        IPS_Sleep($this->ReadPropertyInteger($unit . 'SwitchingDelay'));
                        $this->SendDebug(__FUNCTION__, 'Befehl: @HM_WriteValueFloat(' . $id . ", 'LEVEL', " . $Brightness / 100 . ');', 0);
                        $result = @HM_WriteValueFloat($id, 'LEVEL', $Brightness / 100);
                        if (!$result) {
                            IPS_Sleep($this->ReadPropertyInteger($unit . 'SwitchingDelay'));
                            $result = @HM_WriteValueFloat($id, 'LEVEL', $Brightness / 100);
                        }
                    }
                    if ($result) {
                        $this->SendDebug(__FUNCTION__, 'Die Helligkeit ' . $Brightness . ' wurde für die ' . $lightUnitDescription . ' (ID ' . $id . ') eingestellt.', 0);
                    } else {
                        $this->SendDebug(__FUNCTION__, 'Abbruch, die Helligkeit ' . $Brightness . ' konnte für die ' . $lightUnitDescription . ' (ID ' . $id . ') nicht eingestellt werden!', 0);
                    }
                    break;

            }
        }

        return $result;
    }

    /**
     * Sets the mode.
     *
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower light unit
     *
     * @param int $Mode
     * 0 =  off,
     * 1 =  on
     * 2 =  blinking slow,
     * 3 =  blinking middle,
     * 4 =  blinking fast,
     * 5 =  flash slow,
     * 6 =  flash middle,
     * 7 =  flash fast,
     * 8 =  billow slow,
     * 9 =  billow middle
     * 10 = billow falst
     * 11 = old value,
     * 12 = do not care
     *
     * @param bool $ForceMode
     * false =  use configuration,
     * true =   always set mode on device
     *
     * @return bool
     * false =  an error occurred
     * true =   successful
     *
     * @throws Exception
     */
    public function SetMode(int $LightUnit, int $Mode, bool $ForceMode = false): bool
    {
        if ($this->CheckMaintenance()) {
            return false;
        }

        $result = false;

        $unit = 'UpperLightUnit';
        $lightUnitDescription = 'obere Leuchteinheit';
        if ($LightUnit == 1) {
            $unit = 'LowerLightUnit';
            $lightUnitDescription = 'untere Leuchteinheit';
        }

        //Check device variable
        $id = $this->ReadPropertyInteger($unit . 'DeviceColorBehaviour');
        if ($id <= 1 || @!IPS_ObjectExists($id)) {
            return false;
        }

        $this->SendDebug(__FUNCTION__, 'Einheit: ' . $LightUnit . ' = ' . $lightUnitDescription . ', Modus: ' . $Mode . ', Forcieren: ' . $ForceMode, 0);

        $actualMode = $this->GetValue($unit . 'Mode');

        //Set values, changes only!
        if ($actualMode != $Mode) {
            $this->SetValue($unit . 'Mode', $Mode);
        } else {
            //Verify device mode
            $verifyMode = $this->VerifyDeviceMode($LightUnit, $Mode);
            if (!$verifyMode) {
                $ForceMode = true;
            }
        }

        if (!$ForceMode) {
            if ($actualMode == $Mode) {
                $this->SendDebug(__FUNCTION__, 'Es wird bereits der gleiche Modus verwendet!', 0);
                return true;
            }
        } else {
            $this->SendDebug(__FUNCTION__, 'Die Gerätesignalisierung wird erzwungen!', 0);
        }

        //Set mode on device
        $id = $this->ReadPropertyInteger($unit);
        if ($id > 1 && @IPS_ObjectExists($id)) {
            switch ($this->ReadPropertyInteger($unit . 'DeviceType')) {
                case 1: //HmIP-BSL Channel 8
                case 2: //HmIP-MP3P Channel 6
                    $commandControl = $this->ReadPropertyInteger('CommandControl');
                    if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                        $commands = [];
                        $commands[] = '@HM_WriteValueInteger(' . $id . ", 'COLOR_BEHAVIOUR', '" . $Mode . "');";
                        $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                        $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                        $result = @IPS_RunScriptText($scriptText);
                    } else {
                        IPS_Sleep($this->ReadPropertyInteger($unit . 'SwitchingDelay'));
                        $this->SendDebug(__FUNCTION__, 'Befehl: @HM_WriteValueInteger(' . $id . ", 'COLOR_BEHAVIOUR', " . $Mode . ');', 0);
                        $result = @HM_WriteValueInteger($id, 'COLOR_BEHAVIOUR', $Mode);
                        if (!$result) {
                            IPS_Sleep($this->ReadPropertyInteger($unit . 'SwitchingDelay'));
                            $result = @HM_WriteValueInteger($id, 'COLOR_BEHAVIOUR', $Mode);
                        }
                    }
                    if ($result) {
                        $this->SendDebug(__FUNCTION__, 'Der Modus ' . $Mode . ' wurde für die ' . $lightUnitDescription . ' (ID ' . $id . ') eingestellt.', 0);
                    } else {
                        $this->SendDebug(__FUNCTION__, 'Abbruch, der Modus ' . $Mode . ' konnte für die ' . $lightUnitDescription . ' (ID ' . $id . ') nicht eingestellt werden!', 0);
                    }
                    break;

            }
        }

        return $result;
    }

    /**
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower light unit
     *
     * @param int $Color
     * 0 =  black or off,
     * 1 =  blue,
     * 2 =  green,
     * 3 =  turquoise,
     * 4 =  red,
     * 5 =  violet,
     * 6 =  yellow,
     * 7 =  white
     *
     * @param int $Brightness
     *
     * @param int $Mode
     * 0 =  off,
     * 1 =  on
     * 2 =  blinking slow,
     * 3 =  blinking middle,
     * 4 =  blinking fast,
     * 5 =  flash slow,
     * 6 =  flash middle,
     * 7 =  flash fast,
     * 8 =  billow slow,
     * 9 =  billow middle
     * 10 = billow falst
     * 11 = old value,
     * 12 = do not care
     *
     * @param bool $Force
     * false =  use configuration,
     * true =   always set mode on device
     *
     * @return bool
     * false =  an error occurred
     * true =   successful
     *
     * @throws Exception
     */
    public function SetCombinedParameters(int $LightUnit, int $Color, int $Brightness, int $Mode, bool $Force = false): bool
    {
        if ($this->CheckMaintenance()) {
            return false;
        }

        $result = false;

        $unit = 'UpperLightUnit';
        $lightUnitDescription = 'obere Leuchteinheit';
        if ($LightUnit == 1) {
            $unit = 'LowerLightUnit';
            $lightUnitDescription = 'untere Leuchteinheit';
        }

        //Check device instance
        $id = $this->ReadPropertyInteger($unit);
        if ($id <= 1 || @!IPS_ObjectExists($id)) {
            return false;
        }

        //Check combined parameters
        if (!$this->ReadPropertyBoolean($unit . 'UseCombinedParameter')) {
            return false;
        }

        $this->SendDebug(__FUNCTION__, 'Einheit: ' . $LightUnit . ' = ' . $lightUnitDescription . ', Farbe: ' . $Color . ', Helligkeit: ' . $Brightness . ', Modus: ' . $Mode . ', Forcieren: ' . $Force, 0);

        $actualColor = $this->GetValue($unit . 'Color');
        $actualBrightness = $this->GetValue($unit . 'Brightness');
        $actualMode = $this->GetValue($unit . 'Mode');

        //Set values, changes only!
        if ($actualColor != $Color) {
            $this->SetValue($unit . 'Color', $Color);
        } else {
            //Verify device color
            $verifyColor = $this->VerifyDeviceColor($LightUnit, $Color);
            if (!$verifyColor) {
                $Force = true;
            }
        }

        if ($actualBrightness != $Brightness) {
            $this->SetValue($unit . 'Brightness', $Brightness);
        } else {
            //Verify device brightness
            $verifyMode = $this->VerifyDeviceBrightness($LightUnit, $Brightness);
            if (!$verifyMode) {
                $Force = true;
            }
        }

        if ($actualMode != $Mode) {
            $this->SetValue($unit . 'Mode', $Mode);
        } else {
            //Verify device mode
            $verifyMode = $this->VerifyDeviceMode($LightUnit, $Mode);
            if (!$verifyMode) {
                $Force = true;
            }
        }

        if (!$Force) {
            if ($actualColor == $Color && $actualBrightness == $Brightness && $actualMode == $Mode) {
                $this->SendDebug(__FUNCTION__, 'Es werden bereits die gleichen Werte verwendet!', 0);
                return true;
            }
        } else {
            $this->SendDebug(__FUNCTION__, 'Die Gerätesignalisierung wird erzwungen!', 0);
        }

        //Set mode on device
        $id = $this->ReadPropertyInteger($unit);
        if ($id > 1 && @IPS_ObjectExists($id)) {
            switch ($this->ReadPropertyInteger($unit . 'DeviceType')) {
                case 1: //HmIP-BSL Channel 8
                case 2: //HmIP-MP3P Channel 6
                    $commandControl = $this->ReadPropertyInteger('CommandControl');
                    if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                        $commands = [];
                        //C = color, L = level, CB = color behaviour, DV = duration value, DU = duration unit, RTV = ramp time value, RTU = ramp time unit
                        $commands[] = '@HM_WriteValueString(' . $id . ", 'COMBINED_PARAMETER', 'C=" . $Color . ',L=' . $Brightness . ',CB=' . $Mode . "');";
                        $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                        $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                        $result = @IPS_RunScriptText($scriptText);
                    }
                    else {
                        IPS_Sleep($this->ReadPropertyInteger($unit . 'SwitchingDelay'));
                        $result = @HM_WriteValueString($id, 'COMBINED_PARAMETER', 'C=' . $Color . ',L=' . $Brightness . ',CB=' . $Mode);
                        if (!$result) {
                            IPS_Sleep($this->ReadPropertyInteger($unit . 'SwitchingDelay'));
                            $result = @HM_WriteValueString($id, 'COMBINED_PARAMETER', 'C=' . $Color . ',L=' . $Brightness . ',CB=' . $Mode);
                        }
                    }
                    if ($result) {
                        $this->SendDebug(__FUNCTION__, 'Die kombinierten Parameter ' . $Mode . ' wurden für die ' . $lightUnitDescription . ' (ID ' . $id . ') eingestellt.', 0);
                    } else {
                        $this->SendDebug(__FUNCTION__, 'Abbruch, die kombinierten Parameter ' . $Mode . ' konnten für die ' . $lightUnitDescription . ' (ID ' . $id . ') nicht eingestellt werden!', 0);
                    }
                    break;

            }
        }

        return $result;
    }

    ########## Device Updates

    /**
     * Updates the color from the device.
     *
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower ligth unit
     *
     * @return void
     * @throws Exception
     */
    protected function UpdateColor(int $LightUnit): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }

        $unit = 'UpperLightUnit';
        if ($LightUnit == 1) {
            $unit = 'LowerLightUnit';
        }

        $id = $this->ReadPropertyInteger($unit . 'DeviceColor');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $actualColor = $this->GetValue($unit . 'Color');
            $actualDeviceColor = GetValueInteger($id);
            if ($actualDeviceColor != $actualColor) {
                $this->SetValue($unit . 'Color', $actualDeviceColor);
            }
        }
    }

    /**
     * Updates the brightness from the device.
     *
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower ligth unit
     *
     * @return void
     * @throws Exception
     */
    protected function UpdateBrightness(int $LightUnit): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }

        $unit = 'UpperLightUnit';
        if ($LightUnit == 1) {
            $unit = 'LowerLightUnit';
        }

        $id = $this->ReadPropertyInteger($unit . 'DeviceBrightness');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $actualBrightness = $this->GetValue($unit . 'Brightness');
            $actualDeviceBrightness = GetValueFloat($id) * 100;
            if ($actualDeviceBrightness != $actualBrightness) {
                $this->SetValue($unit . 'Brightness', $actualDeviceBrightness);
            }
        }
    }

    /**
     * Updates the mode (color behaviour) from the device.
     *
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower ligth unit
     *
     * @return void
     * @throws Exception
     */
    protected function UpdateMode(int $LightUnit): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }

        $unit = 'UpperLightUnit';
        if ($LightUnit == 1) {
            $unit = 'LowerLightUnit';
        }

        $id = $this->ReadPropertyInteger($unit . 'DeviceColorBehaviour');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $actualMode = $this->GetValue($unit . 'Mode');
            $actualDeviceMode = GetValueInteger($id);
            if ($actualDeviceMode != $actualMode) {
                $this->SetValue($unit . 'Mode', $actualDeviceMode);
            }
        }
    }

    ########## Device Verification

    /**
     * Verifies whether the device has the correct color.
     *
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower ligth unit
     *
     * @param int $Color
     *
     * @return bool
     * false =  verification failed,
     * true =   verifiation successful
     *
     * @throws Exception
     */
    private function VerifyDeviceColor(int $LightUnit, int $Color): bool
    {
        $unit = 'UpperLightUnit';
        if ($LightUnit == 1) {
            $unit = 'LowerLightUnit';
        }

        $result = false;

        $id = $this->ReadPropertyInteger($unit . 'DeviceColor');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            if (GetValueInteger($id) != $Color) {
                $this->SendDebug(__FUNCTION__, 'Die Gerätefarbe entspricht nicht der aktuellen Farbe!', 0);
            } else {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Verifies whether the device has the correct brightness.
     *
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower ligth unit
     *
     * @param int $Brightness
     *
     * @return bool
     * false =  verification failed,
     * true =   verification successful
     *
     * @throws Exception
     */
    private function VerifyDeviceBrightness(int $LightUnit, int $Brightness): bool
    {
        $unit = 'UpperLightUnit';
        if ($LightUnit == 1) {
            $unit = 'LowerLightUnit';
        }

        $result = false;

        $id = $this->ReadPropertyInteger($unit . 'DeviceBrightness');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            if ((GetValueFloat($id) * 100) != $Brightness) {
                $this->SendDebug(__FUNCTION__, 'Die Gerätehelligkeit entspricht nicht der aktuellen Helligkeit!', 0);
            } else {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Verifies whether the device has the correct mode (color behaviour).
     *
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower ligth unit
     *
     * @param int $Mode
     *
     * @return bool
     * false =  verification failed,
     * true =   verification successful
     *
     * @throws Exception
     */
    private function VerifyDeviceMode(int $LightUnit, int $Mode): bool
    {
        $unit = 'UpperLightUnit';
        if ($LightUnit == 1) {
            $unit = 'LowerLightUnit';
        }

        $result = false;

        $id = $this->ReadPropertyInteger($unit . 'DeviceColorBehaviour');
        if ($id > 1 && @IPS_ObjectExists($id)) {
            if (GetValueInteger($id) != $Mode) {
                $this->SendDebug(__FUNCTION__, 'Der Gerätemodus entspricht nicht dem aktuellen Modus!', 0);
            } else {
                $result = true;
            }
        }

        return $result;
    }
}