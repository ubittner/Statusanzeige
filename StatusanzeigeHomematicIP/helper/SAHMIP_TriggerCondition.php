<?php

/**
 * @project       Statusanzeige/StatusanzeigeHomematicIP/helper/
 * @file          SAHMIP_TriggerCondition.php
 * @author        Ulrich Bittner
 * @copyright     2023,2024 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnusedPrivateMethodInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait SAHMIP_TriggerCondition
{
    /**
     * Gets the actual variable states for the upper light unit.
     *
     * @return void
     * @throws Exception
     */
    public function GetUpperLightUnitActualVariableStates(): void
    {
        $this->UpdateUpperLightUnit(true);
        $this->UpdateFormField('UpperLightUnitActualVariableStateConfigurationButton', 'visible', false);
        $actualVariableStates = [];
        $variables = json_decode($this->ReadPropertyString('UpperLightUnitTriggerList'), true);
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $conditions = true;
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $sensorID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($sensorID <= 1 || @!IPS_ObjectExists($sensorID)) {
                            $conditions = false;
                        }
                    }
                }
            }
            if ($variable['SecondaryCondition'] != '') {
                $secondaryConditions = json_decode($variable['SecondaryCondition'], true);
                if (array_key_exists(0, $secondaryConditions)) {
                    if (array_key_exists('rules', $secondaryConditions[0])) {
                        $rules = $secondaryConditions[0]['rules']['variable'];
                        foreach ($rules as $rule) {
                            if (array_key_exists('variableID', $rule)) {
                                $id = $rule['variableID'];
                                if ($id <= 1 || @!IPS_ObjectExists($id)) {
                                    $conditions = false;
                                }
                            }
                        }
                    }
                }
            }
            if ($conditions && isset($sensorID)) {
                $stateName = '❌ Bedingung nicht erfüllt!';
                if (IPS_IsConditionPassing($variable['PrimaryCondition']) && IPS_IsConditionPassing($variable['SecondaryCondition'])) {
                    $stateName = '✅ Bedingung erfüllt';
                }
                $colorName = '';
                $color = $variable['Color'];
                switch ($color) {
                    case 0:
                        $colorName = 'Aus';
                        break;

                    case 1:
                        $colorName = 'Blau';
                        break;

                    case 2:
                        $colorName = 'Grün';
                        break;

                    case 3:
                        $colorName = 'Türkis';
                        break;

                    case 4:
                        $colorName = 'Rot';
                        break;

                    case 5:
                        $colorName = 'Violett';
                        break;

                    case 6:
                        $colorName = 'Gelb';
                        break;

                    case 7:
                        $colorName = 'Weiß';
                        break;
                }
                //Mode
                $modeName = '';
                if (array_key_exists('Mode', $variable)) {
                    $mode = $variable['Mode'];
                    switch ($mode) {
                        case 0:
                            $modeName = 'Beleuchtung aus';
                            break;

                        case 1:
                            $modeName = 'Dauerhaft ein';
                            break;

                        case 2:
                            $modeName = 'Langsames Blinken';
                            break;

                        case 3:
                            $modeName = 'Mittleres Blinken';
                            break;

                        case 4:
                            $modeName = 'Schnelles Blinken';
                            break;

                        case 5:
                            $modeName = 'Langsames Blitzen';
                            break;

                        case 6:
                            $modeName = 'Mittleres Blitzen';
                            break;

                        case 7:
                            $modeName = 'Schnelles Blitzen';
                            break;

                        case 8:
                            $modeName = 'Langsames Pulsieren';
                            break;

                        case 9:
                            $modeName = 'Mittleres Pulsieren';
                            break;

                        case 10:
                            $modeName = 'Schnelles Pulsieren';
                            break;

                        case 11:
                            $modeName = 'Vorheriger Wert';
                            break;

                        case 12:
                            $modeName = 'Ohne Funktion';
                            break;
                    }
                }
                $variableUpdate = IPS_GetVariable($sensorID)['VariableUpdated']; //timestamp or 0 = never
                $lastUpdate = 'Nie';
                if ($variableUpdate != 0) {
                    $lastUpdate = date('d.m.Y H:i:s', $variableUpdate);
                }
                $actualVariableStates[] = ['ActualStatus' => $stateName, 'SensorID' => $sensorID, 'Designation' =>  $variable['Designation'], 'Color' =>  $colorName, 'Brightness' =>  $variable['Brightness'], 'Mode' =>  $modeName, 'LastUpdate' => $lastUpdate];
            }
        }
        $amount = count($actualVariableStates);
        if ($amount == 0) {
            $amount = 1;
        }
        $this->UpdateFormField('UpperLightUnitActualVariableStateList', 'rowCount', $amount);
        $this->UpdateFormField('UpperLightUnitActualVariableStateList', 'values', json_encode($actualVariableStates));
        $this->UpdateFormField('UpperLightUnitActualVariableStateList', 'visible', true);
    }

    /**
     * Gets the actual variable states for the lower light unit.
     *
     * @return void
     * @throws Exception
     */
    public function GetLowerLightUnitActualVariableStates(): void
    {
        $this->UpdateLowerLightUnit(true);
        $this->UpdateFormField('LowerLightUnitActualVariableStateConfigurationButton', 'visible', false);
        $actualVariableStates = [];
        $variables = json_decode($this->ReadPropertyString('LowerLightUnitTriggerList'), true);
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $conditions = true;
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $sensorID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($sensorID <= 1 || @!IPS_ObjectExists($sensorID)) {
                            $conditions = false;
                        }
                    }
                }
            }
            if ($variable['SecondaryCondition'] != '') {
                $secondaryConditions = json_decode($variable['SecondaryCondition'], true);
                if (array_key_exists(0, $secondaryConditions)) {
                    if (array_key_exists('rules', $secondaryConditions[0])) {
                        $rules = $secondaryConditions[0]['rules']['variable'];
                        foreach ($rules as $rule) {
                            if (array_key_exists('variableID', $rule)) {
                                $id = $rule['variableID'];
                                if ($id <= 1 || @!IPS_ObjectExists($id)) {
                                    $conditions = false;
                                }
                            }
                        }
                    }
                }
            }
            if ($conditions && isset($sensorID)) {
                $stateName = '❌ Bedingung nicht erfüllt!';
                if (IPS_IsConditionPassing($variable['PrimaryCondition']) && IPS_IsConditionPassing($variable['SecondaryCondition'])) {
                    $stateName = '✅ Bedingung erfüllt';
                }
                $colorName = '';
                $color = $variable['Color'];
                switch ($color) {
                    case 0:
                        $colorName = 'Aus';
                        break;

                    case 1:
                        $colorName = 'Blau';
                        break;

                    case 2:
                        $colorName = 'Grün';
                        break;

                    case 3:
                        $colorName = 'Türkis';
                        break;

                    case 4:
                        $colorName = 'Rot';
                        break;

                    case 5:
                        $colorName = 'Violett';
                        break;

                    case 6:
                        $colorName = 'Gelb';
                        break;

                    case 7:
                        $colorName = 'Weiß';
                        break;
                }
                //Mode
                $modeName = '';
                if (array_key_exists('Mode', $variable)) {
                    $mode = $variable['Mode'];
                    switch ($mode) {
                        case 0:
                            $modeName = 'Beleuchtung aus';
                            break;

                        case 1:
                            $modeName = 'Dauerhaft ein';
                            break;

                        case 2:
                            $modeName = 'Langsames Blinken';
                            break;

                        case 3:
                            $modeName = 'Mittleres Blinken';
                            break;

                        case 4:
                            $modeName = 'Schnelles Blinken';
                            break;

                        case 5:
                            $modeName = 'Langsames Blitzen';
                            break;

                        case 6:
                            $modeName = 'Mittleres Blitzen';
                            break;

                        case 7:
                            $modeName = 'Schnelles Blitzen';
                            break;

                        case 8:
                            $modeName = 'Langsames Pulsieren';
                            break;

                        case 9:
                            $modeName = 'Mittleres Pulsieren';
                            break;

                        case 10:
                            $modeName = 'Schnelles Pulsieren';
                            break;

                        case 11:
                            $modeName = 'Vorheriger Wert';
                            break;

                        case 12:
                            $modeName = 'Ohne Funktion';
                            break;
                    }
                }
                $variableUpdate = IPS_GetVariable($sensorID)['VariableUpdated']; //timestamp or 0 = never
                $lastUpdate = 'Nie';
                if ($variableUpdate != 0) {
                    $lastUpdate = date('d.m.Y H:i:s', $variableUpdate);
                }
                $actualVariableStates[] = ['ActualStatus' => $stateName, 'SensorID' => $sensorID, 'Designation' =>  $variable['Designation'], 'Color' =>  $colorName, 'Brightness' =>  $variable['Brightness'], 'Mode' =>  $modeName, 'LastUpdate' => $lastUpdate];
            }
        }
        $amount = count($actualVariableStates);
        if ($amount == 0) {
            $amount = 1;
        }
        $this->UpdateFormField('LowerLightUnitActualVariableStateList', 'rowCount', $amount);
        $this->UpdateFormField('LowerLightUnitActualVariableStateList', 'values', json_encode($actualVariableStates));
        $this->UpdateFormField('LowerLightUnitActualVariableStateList', 'visible', true);
    }

    /**
     * Checks if the trigger is assigned to the light unit.
     *
     * @param int $VariableID
     *
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower light unit
     *
     * @return bool
     * false =  trigger is not assigned
     * true =   trigger is assigned
     *
     * @throws Exception
     */
    public function CheckTrigger(int $VariableID, int $LightUnit): bool
    {
        $triggerListName = 'UpperLightUnitTriggerList';
        $lightUnitDescription = 'obere Leuchteinheit';
        if ($LightUnit == 1) {
            $triggerListName = 'LowerLightUnitTriggerList';
            $lightUnitDescription = 'untere Leuchteinheit';
        }
        $this->SendDebug(__FUNCTION__, 'Variable ID: ' . $VariableID . ', Einheit: ' . $LightUnit . ' = ' . $lightUnitDescription, 0);
        $result = false;
        $variables = json_decode($this->ReadPropertyString($triggerListName), true);
        if (!empty($variables)) {
            foreach ($variables as $variable) {
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($id == $VariableID) {
                                if ($id > 1 && @IPS_ObjectExists($id)) {
                                    if ($variable['Use']) {
                                        $result = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    #################### Private

    /**
     * Checks the trigger conditions of the light unit and sets the color and brightness.
     *
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower light unit
     *
     * @param bool $ForceSignaling
     * false =  use configuration,
     * true =   always set color, brightness and mode
     *
     * @return void
     * @throws Exception
     */
    private function CheckTriggerConditions(int $LightUnit, bool $ForceSignaling): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }

        $triggerList = 'UpperLightUnitTriggerList';
        $lightUnitDescription = 'obere Leuchteinheit';
        if ($LightUnit == 1) {
            $triggerList = 'LowerLightUnitTriggerList';
            $lightUnitDescription = 'untere Leuchteinheit';
        }

        $this->SendDebug(__FUNCTION__, 'Einheit: ' . $LightUnit . ' = ' . $lightUnitDescription . ', Forcieren: ' . json_encode($ForceSignaling), 0);

        $variables = json_decode($this->ReadPropertyString($triggerList), true);
        if (!empty($variables)) {
            //Sort priority descending, highest priority first
            array_multisort(array_column($variables, 'Priority'), SORT_DESC, $variables);
            foreach ($variables as $variable) {
                $execute = false;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($id > 1 && @IPS_ObjectExists($id)) {
                                if ($variable['Use']) {
                                    $condition = true;
                                    //Check primary condition
                                    if (!IPS_IsConditionPassing($variable['PrimaryCondition'])) {
                                        $condition = false;
                                    }
                                    //Check secondary condition
                                    if (!IPS_IsConditionPassing($variable['SecondaryCondition'])) {
                                        $condition = false;
                                    }
                                    if ($condition) {
                                        $execute = true;
                                    }
                                }
                            }
                        }
                    }
                }

                if ($execute) {
                    if ($ForceSignaling) {
                        $force = true;
                    } else {
                        $force = $variable['ForceSignaling'];
                    }

                    $this->SendDebug(__FUNCTION__, 'Einheit: ' . $lightUnitDescription . ' = ' . $LightUnit . ', Farbe: ' . $variable['Color'], 0);
                    $this->SendDebug(__FUNCTION__, 'Einheit: ' . $lightUnitDescription . ' = ' . $LightUnit . ', Helligkeit: ' . $variable['Brightness'], 0);
                    if (array_key_exists('Mode', $variable)) {
                        $this->SendDebug(__FUNCTION__, 'Einheit: ' . $lightUnitDescription . ' = ' . $LightUnit . ', Modus: ' . $variable['Mode'], 0);
                    }
                    $this->SendDebug(__FUNCTION__, 'Einheit: ' . $lightUnitDescription . ' = ' . $LightUnit . ', Forcieren: ' . json_encode($force), 0);

                    $unit = 'UpperLightUnit';
                    if ($LightUnit == 1) {
                        $unit = 'LowerLightUnit';
                    }

                    if ($this->ReadPropertyBoolean($unit . 'UseCombinedParameter')) {
                        if (array_key_exists('Mode', $variable)) {
                            $this->SetCombinedParameters($LightUnit, $variable['Color'], $variable['Brightness'], $variable['Mode'], $force);
                        } else {
                            $this->SetCombinedParameters($LightUnit, $variable['Color'], $variable['Brightness'], 1, $force);
                        }
                        break;
                    } else {
                        $this->SetColor($LightUnit, $variable['Color'], $force);
                        $this->SetBrightness($LightUnit, $variable['Brightness'], $force);
                        if (array_key_exists('Mode', $variable)) {
                            $this->SetMode($LightUnit, $variable['Mode'], $force);
                        }
                    }
                    break;

                }
            }
        }
    }

    /**
     * Validates the trigger list of the light unit for an existing and activated trigger.
     *
     * @param int $LightUnit
     * 0 =  upper light unit,
     * 1 =  lower light unit
     *
     * @return bool
     * false =  no activated trigger,
     * true =   activated trigger
     *
     *
     * @throws Exception
     */
    private function ValidateTriggerList(int $LightUnit): bool
    {
        $triggerList = 'UpperLightUnitTriggerList';
        $lightUnitDescription = 'obere Leuchteinheit';
        if ($LightUnit == 1) {
            $triggerList = 'LowerLightUnitTriggerList';
            $lightUnitDescription = 'untere Leuchteinheit';
        }
        $this->SendDebug(__FUNCTION__, 'Einheit: ' . $LightUnit . ' = ' . $lightUnitDescription, 0);
        $result = false;
        $variables = json_decode($this->ReadPropertyString($triggerList), true);
        if (!empty($variables)) {
            foreach ($variables as $variable) {
                if (!$variable['Use']) {
                    continue;
                }
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($id > 1 && @IPS_ObjectExists($id)) {
                                $result = true;
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
}