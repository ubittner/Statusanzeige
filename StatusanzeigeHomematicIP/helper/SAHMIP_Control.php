<?php

/**
 * @project       Statusanzeige/StatusanzeigeHomematicIP/helper/
 * @file          SAHMIP_Control.php
 * @author        Ulrich Bittner
 * @copyright     2023, 2024 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait SAHMIP_Control
{
    /**
     * Toggles the module inactive or active.
     *
     * @param bool $State
     * false =  inactive,
     * true =   active
     *
     * @return void
     * @throws Exception
     */
    public function ToggleActive(bool $State): void
    {
        ##### Inactive
        if (!$State) {

            ##### Upper Light Unit

            $this->WriteAttributeInteger('UpperLightUnitLastColor', $this->GetValue('UpperLightUnitColor'));
            $this->WriteAttributeInteger('UpperLightUnitLastBrightness', $this->GetValue('UpperLightUnitBrightness'));
            $this->WriteAttributeInteger('UpperLightUnitLastMode', $this->GetValue('UpperLightUnitMode'));

            $changeColor = $this->ReadPropertyBoolean('DeactivateUpperLightUnitChangeColor');
            $changeBrightness = $this->ReadPropertyBoolean('DeactivateUpperLightUnitChangeBrightness');
            $changeMode = $this->ReadPropertyBoolean('DeactivateUpperLightUnitChangeMode');

            $deactivationColor = $this->ReadPropertyInteger('DeactivationUpperLightUnitColor');
            $deactivationBrightness = $this->ReadPropertyInteger('DeactivationUpperLightUnitBrightness');
            $deactivationMode = $this->ReadPropertyInteger('DeactivationUpperLightUnitMode');

            //Combined parameters
            if ($this->ReadPropertyBoolean('UpperLightUnitUseCombinedParameter')) {
                if ($changeColor && $changeBrightness && $changeMode) {
                    $this->SetCombinedParameters(0, $deactivationColor, $deactivationBrightness, $deactivationMode, true);
                }
            }
            //Single parameter
            else {
                if ($changeColor) {
                    $this->SetColor(0, $deactivationColor, true);
                }

                if ($changeBrightness) {
                    $this->SetBrightness(0, $deactivationBrightness, true);
                }

                if ($changeMode) {
                    $this->SetMode(0, $deactivationMode, true);
                }
            }

            ##### Lower Light Unit

            $this->WriteAttributeInteger('LowerLightUnitLastColor', $this->GetValue('LowerLightUnitColor'));
            $this->WriteAttributeInteger('LowerLightUnitLastBrightness', $this->GetValue('LowerLightUnitBrightness'));
            $this->WriteAttributeInteger('LowerLightUnitLastMode', $this->GetValue('LowerLightUnitMode'));

            $changeColor = $this->ReadPropertyBoolean('DeactivateLowerLightUnitChangeColor');
            $changeBrightness = $this->ReadPropertyBoolean('DeactivateLowerLightUnitChangeBrightness');
            $changeMode = $this->ReadPropertyBoolean('DeactivateLowerLightUnitChangeMode');

            $deactivationColor = $this->ReadPropertyInteger('DeactivationLowerLightUnitColor');
            $deactivationBrightness = $this->ReadPropertyInteger('DeactivationLowerLightUnitBrightness');
            $deactivationMode = $this->ReadPropertyInteger('DeactivationLowerLightUnitMode');

            //Combined parameters
            if ($this->ReadPropertyBoolean('LowerLightUnitUseCombinedParameter')) {
                if ($changeColor && $changeBrightness && $changeMode) {
                    $this->SetCombinedParameters(1, $deactivationColor, $deactivationBrightness, $deactivationMode, true);
                }
            }
            //Single parameter
            else {
                if ($changeColor) {
                    $this->SetColor(1, $deactivationColor, true);
                }

                if ($changeBrightness) {
                    $this->SetBrightness(1, $deactivationBrightness, true);
                }

                if ($changeMode) {
                    $this->SetMode(1, $deactivationMode, true);
                }
            }

            $this->SetValue('Active', false);
        }

        ##### Active
        else {

            $this->SetValue('Active', true);

            ##### Upper Light Unit

            //Trigger list
            if ($this->ValidateTriggerList(0)) {
                $this->UpdateUpperLightUnit(true);
            }

            //Manual mode
            else {

                $reactivateColor = $this->ReadPropertyBoolean('ReactivateUpperLightUnitLastColor');
                $lastColor = $this->ReadAttributeInteger('UpperLightUnitLastColor');
                $reactivateBrightness = $this->ReadPropertyBoolean('ReactivateUpperLightUnitLastBrightness');
                $lastBrightness = $this->ReadAttributeInteger('UpperLightUnitLastBrightness');
                $reactivateMode = $this->ReadPropertyBoolean('ReactivateUpperLightUnitLastMode');
                $lastMode = $this->ReadAttributeInteger('UpperLightUnitLastMode');

                //Combined parameters
                if ($this->ReadPropertyBoolean('UpperLightUnitUseCombinedParameter')) {
                    if ($reactivateColor && $reactivateBrightness && $reactivateMode) {
                        $this->SetCombinedParameters(0, $lastColor, $lastBrightness, $lastMode, true);
                    }
                } else {
                    //Single parameter
                    if ($reactivateColor) {
                        $this->SetColor(0, $lastColor, true);
                    } else {
                        $this->UpdateColor(0);
                    }
                    if ($reactivateBrightness) {
                        $this->SetBrightness(0, $lastBrightness, true);
                    } else {
                        $this->UpdateBrightness(0);
                    }
                    if ($reactivateMode) {
                        $this->SetMode(0, $lastMode, true);
                    } else {
                        $this->UpdateMode(0);
                    }
                }
            }

            ##### Lower Light Unit

            //Trigger list
            if ($this->ValidateTriggerList(1)) {
                $this->UpdateLowerLightUnit(true);
            }

            //Manual mode
            else {

                $reactivateColor = $this->ReadPropertyBoolean('ReactivateLowerLightUnitLastColor');
                $lastColor = $this->ReadAttributeInteger('LowerLightUnitLastColor');
                $reactivateBrightness = $this->ReadPropertyBoolean('ReactivateLowerLightUnitLastBrightness');
                $lastBrightness = $this->ReadAttributeInteger('LowerLightUnitLastBrightness');
                $reactivateMode = $this->ReadPropertyBoolean('ReactivateLowerLightUnitLastMode');
                $lastMode = $this->ReadAttributeInteger('LowerLightUnitLastMode');

                //Combined parameters
                if ($this->ReadPropertyBoolean('LowerLightUnitUseCombinedParameter')) {
                    if ($reactivateColor && $reactivateBrightness && $reactivateMode) {
                        $this->SetCombinedParameters(0, $lastColor, $lastBrightness, $lastMode, true);
                    }
                } else {
                    //Single parameter
                    if ($reactivateColor) {
                        $this->SetColor(1, $lastColor, true);
                    } else {
                        $this->UpdateColor(1);
                    }
                    if ($reactivateBrightness) {
                        $this->SetBrightness(1, $lastBrightness, true);
                    } else {
                        $this->UpdateBrightness(1);
                    }
                    if ($reactivateMode) {
                        $this->SetMode(1, $lastMode, true);
                    } else {
                        $this->UpdateMode(1);
                    }
                }
            }
        }
    }

    /**
     * Updates the light units.
     * Either via the trigger list or manual mode.
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
        if ($this->CheckMaintenance()) {
            return;
        }

        $this->SendDebug(__FUNCTION__, 'Forcieren: ' . json_encode($ForceSignaling), 0);

        ###### Upper Light Unit

        //Trigger list
        if ($this->ValidateTriggerList(0)) {
            $this->UpdateUpperLightUnit($ForceSignaling);
        }

        //Manual mode
        else {
            $this->UpdateColor(0);
            $this->UpdateBrightness(0);
            $this->UpdateMode(0);
        }

        ##### Lower Light Unit

        //Trigger list
        if ($this->ValidateTriggerList(1)) {
            $this->UpdateLowerLightUnit($ForceSignaling);
        }
        //Manual mode
        else {
            $this->UpdateColor(1);
            $this->UpdateBrightness(1);
            $this->UpdateMode(1);
        }

        ##### Timer

        $milliseconds = 0;
        if ($this->ReadPropertyBoolean('AutomaticStatusUpdate')) {
            $milliseconds = $this->ReadPropertyInteger('CheckStatusInterval') * 1000;
        }
        $this->SetTimerInterval('CheckStatus', $milliseconds);
    }

    /**
     * Starts the automatic deactivation.
     *
     * @return void
     * @throws Exception
     */
    public function StartAutomaticDeactivation(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->ToggleActive(false);
        $this->SetAutomaticDeactivationTimer();
    }

    /**
     * Stops the automatic deactivation.
     *
     * @return void
     * @throws Exception
     */
    public function StopAutomaticDeactivation(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->ToggleActive(true);
        $this->SetAutomaticDeactivationTimer();
    }

    #################### Private

    /**
     * Updates the upper light unit from trigger list.
     *
     * @param bool $ForceSignaling
     * false =  use configuration,
     * true =   always set color, brightness and mode
     *
     * @return void
     * @throws Exception
     */
    private function UpdateUpperLightUnit(bool $ForceSignaling): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }
        $this->SendDebug(__FUNCTION__, 'Forcieren: ' . json_encode($ForceSignaling), 0);
        $this->CheckTriggerConditions(0, $ForceSignaling);
    }

    /**
     * Updates the lower light unit from trigger list.
     *
     * @param bool $ForceSignaling
     * false =  use configuration,
     * true =   always set color, brightness and mode
     *
     * @return void
     * @throws Exception
     */
    private function UpdateLowerLightUnit(bool $ForceSignaling): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }
        $this->SendDebug(__FUNCTION__, 'Forcieren: ' . json_encode($ForceSignaling), 0);
        $this->CheckTriggerConditions(1, $ForceSignaling);
    }

    /**
     * Sets the timer for the automatic deactivation.
     *
     * @return void
     * @throws Exception
     */
    private function SetAutomaticDeactivationTimer(): void
    {
        $use = $this->ReadPropertyBoolean('UseAutomaticDeactivation');
        //Start
        $milliseconds = 0;
        if ($use) {
            $milliseconds = $this->GetInterval('AutomaticDeactivationStartTime');
        }
        $this->SetTimerInterval('StartAutomaticDeactivation', $milliseconds);
        //End
        $milliseconds = 0;
        if ($use) {
            $milliseconds = $this->GetInterval('AutomaticDeactivationEndTime');
        }
        $this->SetTimerInterval('StopAutomaticDeactivation', $milliseconds);
    }

    /**
     * Gets the interval for a timer.
     *
     * @param string $TimerName
     * @return int
     * @throws Exception
     */
    private function GetInterval(string $TimerName): int
    {
        $timer = json_decode($this->ReadPropertyString($TimerName));
        $now = time();
        $hour = $timer->hour;
        $minute = $timer->minute;
        $second = $timer->second;
        $definedTime = $hour . ':' . $minute . ':' . $second;
        if (time() >= strtotime($definedTime)) {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
        } else {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j'), (int) date('Y'));
        }
        return ($timestamp - $now) * 1000;
    }

    /**
     * Checks the status of the automatic deactivation timer.
     *
     * @return bool
     * false =  timer is active,
     * true =   timer is inactive
     * @throws Exception
     */
    private function CheckAutomaticDeactivationTimer(): bool
    {
        if (!$this->ReadPropertyBoolean('UseAutomaticDeactivation')) {
            return false;
        }
        $start = $this->GetTimerInterval('StartAutomaticDeactivation');
        $stop = $this->GetTimerInterval('StopAutomaticDeactivation');
        if ($start > $stop) {
            //Deactivation timer is active, must be toggled to inactive
            $this->ToggleActive(false);
            return true;
        } else {
            //Deactivation timer is inactive, must be toggled to active
            $this->ToggleActive(true);
            return false;
        }
    }
}