<?php

/**
 * @project       Statusanzeige/StatusanzeigeHomematicIP
 * @file          SAHMIP_TriggerCondition.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnusedPrivateMethodInspection */
/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait SAHMIP_TriggerCondition
{
    /**
     * Checks if the trigger is assigned to the light unit.
     *
     * @param int $VariableID
     * @param int $LightUnit
     * 0 =  Upper light unit,
     * 1 =  Lower light unit
     *
     * @return bool
     * @throws Exception
     */
    public function CheckTrigger(int $VariableID, int $LightUnit): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird  ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Variable: ' . $VariableID, 0);
        $this->SendDebug(__FUNCTION__, 'Leuchteinheit: ' . $LightUnit, 0);
        $result = false;
        $triggerListName = 'UpperLightUnitTriggerList';
        if ($LightUnit == 1) {
            $triggerListName = 'LowerLightUnitTriggerList';
        }
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
     * 0 =  Upper light unit,
     * 1 =  Lower light unit
     *
     * @param bool $ForceSignaling
     * false =  use configuration,
     * true =   always set color and brightness
     *
     * @return void
     * @throws Exception
     */
    private function CheckTriggerConditions(int $LightUnit, bool $ForceSignaling): void
    {
        $this->SendDebug(__FUNCTION__, 'wird  ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Leuchteinheit: ' . $LightUnit, 0);
        $this->SendDebug(__FUNCTION__, 'Forcieren: ' . json_encode($ForceSignaling), 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $triggerListName = 'UpperLightUnitTriggerList';
        if ($LightUnit == 1) {
            $triggerListName = 'LowerLightUnitTriggerList';
        }
        $variables = json_decode($this->ReadPropertyString($triggerListName), true);
        if (!empty($variables)) {
            //Sort priority descending for highest priority first
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
                                    $this->SendDebug(__FUNCTION__, 'Die Variable ' . $id . ' ist aktiviert.', 0);
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
                    //Color
                    $this->SetColor($LightUnit, $variable['Color'], $ForceSignaling);
                    $this->SendDebug(__FUNCTION__, 'Leuchteinheit: ' . $LightUnit . ', Farbe: ' . $variable['Color'], 0);
                    //Brightness
                    $this->SetBrightness($LightUnit, $variable['Brightness'], $ForceSignaling);
                    $this->SendDebug(__FUNCTION__, 'Leuchteinheit: ' . $LightUnit . ', Helligkeit: ' . $variable['Brightness'], 0);
                    break;
                }
            }
        }
    }

    /**
     * Validates the trigger list of the light unit for an existing and activated trigger.
     *
     * @param int $LightUnit
     * 0 =  Upper light unit,
     * 1 =  Lower light unit
     *
     * @return bool
     * @throws Exception
     */
    private function ValidateTriggerList(int $LightUnit): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird  ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Leuchteinheit: ' . $LightUnit, 0);
        $result = false;
        $triggerListName = 'UpperLightUnitTriggerList';
        if ($LightUnit == 1) {
            $triggerListName = 'LowerLightUnitTriggerList';
        }
        $variables = json_decode($this->ReadPropertyString($triggerListName), true);
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