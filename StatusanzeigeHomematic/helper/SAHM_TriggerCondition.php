<?php

/**
 * @project       Statusanzeige/StatusanzeigeHomematic/helper/
 * @file          SAHM_TriggerCondition.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait SAHM_TriggerCondition
{
    /**
     * Checks the trigger conditions.
     *
     * @param int $SenderID
     * @param bool $ValueChanged
     * false =  same value,
     * true =   new value
     *
     * @param bool $ForceSignaling
     * false =  use configuration,
     * true =   always toggle
     *
     * @return void
     * @throws Exception
     */
    public function CheckTriggerConditions(int $SenderID, bool $ValueChanged, bool $ForceSignaling): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Sender: ' . $SenderID, 0);
        $valueChangedText = 'nicht ';
        if ($ValueChanged) {
            $valueChangedText = '';
        }
        $this->SendDebug(__FUNCTION__, 'Der Wert hat sich ' . $valueChangedText . 'geändert', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($variables as $key => $variable) {
            if (!$variable['Use']) {
                continue;
            }
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($SenderID == $id) {
                            $this->SendDebug(__FUNCTION__, 'Listenschlüssel: ' . $key, 0);
                            if (!$variable['UseMultipleAlerts'] && !$ValueChanged) {
                                $this->SendDebug(__FUNCTION__, 'Abbruch, die Mehrfachauslösung ist nicht aktiviert!', 0);
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
                                $this->SendDebug(__FUNCTION__, 'Signalisierung erzwingen: ' . json_encode($force), 0);
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
                }
            }
        }
    }
}