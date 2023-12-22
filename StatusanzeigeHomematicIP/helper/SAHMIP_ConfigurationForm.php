<?php

/**
 * @project       Statusanzeige/StatusanzeigeHomematicIP/helper/
 * @file          SAHMIP_ConfigurationForm.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait SAHMIP_ConfigurationForm
{
    /**
     * Reloads the configuration form.
     *
     * @return void
     */
    public function ReloadConfig(): void
    {
        $this->ReloadForm();
    }

    /**
     * Expands or collapses the expansion panels.
     *
     * @param bool $State
     * false =  collapse,
     * true =   expand
     *
     * @return void
     */
    public function ExpandExpansionPanels(bool $State): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->UpdateFormField('Panel' . $i, 'expanded', $State);
        }
    }

    /**
     * Modifies a configuration button.
     *
     * @param string $Field
     * @param string $Caption
     * @param int $ObjectID
     *
     * @return void
     */
    public function ModifyButton(string $Field, string $Caption, int $ObjectID): void
    {
        $state = false;
        if ($ObjectID > 1 && @IPS_ObjectExists($ObjectID)) {
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', $Caption);
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $ObjectID);
    }

    public function ModifyActualVariableStatesConfigurationButton(string $Field, int $VariableID): void
    {
        $state = false;
        if ($VariableID > 1 && @IPS_ObjectExists($VariableID)) {
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', 'ID ' . $VariableID . ' Bearbeiten');
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $VariableID);
    }

    /**
     * Modifies a trigger list configuration button
     *
     * @param string $Field
     * @param string $Condition
     *
     * @return void
     */
    public function ModifyTriggerListButton(string $Field, string $Condition): void
    {
        $id = 0;
        $state = false;
        //Get variable id
        $primaryCondition = json_decode($Condition, true);
        if (array_key_exists(0, $primaryCondition)) {
            if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                if ($id > 1 && @IPS_ObjectExists($id)) {
                    $state = true;
                }
            }
        }
        $this->UpdateFormField($Field, 'caption', 'ID ' . $id . ' Bearbeiten');
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $id);
        if ($Field == 'UpperLightUnitTriggerListConfigurationButton') {
            $this->UpdateFormField('UpperLightUnitTriggerListConfigurationButtonSpacer', 'visible', $state);
        }
        if ($Field == 'LowerLightUnitTriggerListConfigurationButton') {
            $this->UpdateFormField('LowerLightUnitTriggerListConfigurationButtonSpacer', 'visible', $state);
        }
    }

    /**
     * Gets the configuration form.
     *
     * @return false|string
     * @throws Exception
     */
    public function GetConfigurationForm()
    {
        $form = [];

        ########## Elements

        //Configuration buttons
        $form['elements'][0] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration ausklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, true);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration einklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, false);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration neu laden',
                        'onClick' => self::MODULE_PREFIX . '_ReloadConfig($id);'
                    ]
                ]
            ];

        //Info
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $module = IPS_GetModule(self::MODULE_GUID);
        $form['elements'][] = [
            'type'     => 'ExpansionPanel',
            'name'     => 'Panel1',
            'caption'  => 'Info',
            'expanded' => false,
            'items'    => [
                [
                    'type'    => 'Label',
                    'caption' => "ID:\t\t\t" . $this->InstanceID
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Modul:\t\tStatusanzeige Homematic IP"
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Präfix:\t\t" . $module['Prefix']
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Version:\t\t" . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date'])
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Entwickler:\t" . $library['Author']
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Note',
                    'caption' => 'Notiz',
                    'width'   => '600px'
                ]
            ]
        ];

        ##### Upper light unit

        //Upper light unit instance
        $upperLightUnitDeviceInstance = $this->ReadPropertyInteger('UpperLightUnit');
        $enableUpperLightUnitDeviceInstanceButton = false;
        if ($upperLightUnitDeviceInstance > 1 && @IPS_ObjectExists($upperLightUnitDeviceInstance)) {
            $enableUpperLightUnitDeviceInstanceButton = true;
        }

        //Upper light unit color
        $upperLightUnitDeviceColorVariable = $this->ReadPropertyInteger('UpperLightUnitDeviceColor');
        $enableUpperLightUnitDeviceColorButton = false;
        if ($upperLightUnitDeviceColorVariable > 1 && @IPS_ObjectExists($upperLightUnitDeviceColorVariable)) {
            $enableUpperLightUnitDeviceColorButton = true;
        }

        //Upper light unit brightness
        $upperLightUnitDeviceBrightnessVariable = $this->ReadPropertyInteger('UpperLightUnitDeviceBrightness');
        $enableUpperLightUnitDeviceBrightnessButton = false;
        if ($upperLightUnitDeviceBrightnessVariable > 1 && @IPS_ObjectExists($upperLightUnitDeviceBrightnessVariable)) {
            $enableUpperLightUnitDeviceBrightnessButton = true;
        }

        //Upper light unit trigger list
        $upperLightUnitTriggerListValues = [];
        $variables = json_decode($this->ReadPropertyString('UpperLightUnitTriggerList'), true);
        $amountRows = count($variables) + 1;
        if ($amountRows == 1) {
            $amountRows = 3;
        }
        $amountVariables = count($variables);
        foreach ($variables as $variable) {
            $sensorID = 0;
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $sensorID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                    }
                }
            }
            //Check conditions first
            $conditions = true;
            if ($sensorID <= 1 || !@IPS_ObjectExists($sensorID)) {
                $conditions = false;
            }
            if ($variable['SecondaryCondition'] != '') {
                $secondaryConditions = json_decode($variable['SecondaryCondition'], true);
                if (array_key_exists(0, $secondaryConditions)) {
                    if (array_key_exists('rules', $secondaryConditions[0])) {
                        $rules = $secondaryConditions[0]['rules']['variable'];
                        foreach ($rules as $rule) {
                            if (array_key_exists('variableID', $rule)) {
                                $id = $rule['variableID'];
                                if ($id <= 1 || !@IPS_ObjectExists($id)) {
                                    $conditions = false;
                                }
                            }
                        }
                    }
                }
            }
            $rowColor = '#FFC0C0'; //red
            if ($conditions) {
                $rowColor = '#C0FFC0'; //light green
                if (!$variable['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $upperLightUnitTriggerListValues[] = ['rowColor' => $rowColor];
        }

        $form['elements'][] =
            [
                'type'    => 'ExpansionPanel',
                'name'    => 'Panel2',
                'caption' => 'Obere Leuchteinheit',
                'items'   => [
                    [
                        'type'    => 'Label',
                        'caption' => 'Gerät',
                        'italic'  => true,
                        'bold'    => true
                    ],
                    [
                        'type'    => 'Select',
                        'name'    => 'UpperLightUnitDeviceType',
                        'caption' => 'Typ',
                        'options' => [
                            [
                                'caption' => 'Kein Gerät',
                                'value'   => 0
                            ],
                            [
                                'caption' => 'HmIP-BSL, Kanal 8',
                                'value'   => 1
                            ],
                            [
                                'caption' => 'HmIP-MP3P, Kanal 6',
                                'value'   => 2
                            ]
                        ]
                    ],
                    [
                        'type'  => 'RowLayout',
                        'items' => [
                            [
                                'type'     => 'SelectInstance',
                                'name'     => 'UpperLightUnit',
                                'caption'  => 'Instanz',
                                'width'    => '600px',
                                'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "UpperLightUnitDeviceInstanceConfigurationButton", "ID " . $UpperLightUnit . " konfigurieren", $UpperLightUnit);'
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'UpperLightUnitDeviceInstanceConfigurationButton',
                                'caption'  => 'ID ' . $upperLightUnitDeviceInstance . ' konfigurieren',
                                'visible'  => $enableUpperLightUnitDeviceInstanceButton,
                                'objectID' => $upperLightUnitDeviceInstance
                            ]
                        ]
                    ],
                    [
                        'type'  => 'RowLayout',
                        'items' => [
                            [
                                'type'     => 'SelectVariable',
                                'name'     => 'UpperLightUnitDeviceColor',
                                'caption'  => 'Variable COLOR (Farbe)',
                                'width'    => '600px',
                                'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "UpperLightUnitDeviceColorConfigurationButton", "ID " . $UpperLightUnitDeviceColor . " bearbeiten", $UpperLightUnitDeviceColor);'
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'UpperLightUnitDeviceColorConfigurationButton',
                                'caption'  => 'ID ' . $upperLightUnitDeviceColorVariable . ' bearbeiten',
                                'visible'  => $enableUpperLightUnitDeviceColorButton,
                                'objectID' => $upperLightUnitDeviceColorVariable
                            ]
                        ]
                    ],
                    [
                        'type'  => 'RowLayout',
                        'items' => [
                            [
                                'type'     => 'SelectVariable',
                                'name'     => 'UpperLightUnitDeviceBrightness',
                                'caption'  => 'Variable LEVEL (Helligkeit)',
                                'width'    => '600px',
                                'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "UpperLightUnitDeviceBrightnessConfigurationButton", "ID " . $UpperLightUnitDeviceBrightness . " bearbeiten", $UpperLightUnitDeviceBrightness);'
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'UpperLightUnitDeviceBrightnessConfigurationButton',
                                'caption'  => 'ID ' . $upperLightUnitDeviceBrightnessVariable . ' bearbeiten',
                                'visible'  => $enableUpperLightUnitDeviceBrightnessButton,
                                'objectID' => $upperLightUnitDeviceBrightnessVariable
                            ]
                        ]
                    ],
                    [
                        'type'    => 'NumberSpinner',
                        'name'    => 'UpperLightUnitSwitchingDelay',
                        'caption' => 'Schaltverzögerung',
                        'minimum' => 0,
                        'suffix'  => 'Millisekunden'
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => ' '
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => 'Auslöser',
                        'italic'  => true,
                        'bold'    => true
                    ],
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Aktueller Status',
                        'popup'   => [
                            'caption' => 'Aktueller Status',
                            'items'   => [
                                [
                                    'type'     => 'List',
                                    'name'     => 'UpperLightUnitActualVariableStateList',
                                    'caption'  => 'Variablen',
                                    'add'      => false,
                                    'visible'  => false,
                                    'rowCount' => 1,
                                    'sort'     => [
                                        'column'    => 'ActualStatus',
                                        'direction' => 'ascending'
                                    ],
                                    'columns' => [
                                        [
                                            'name'    => 'ActualStatus',
                                            'caption' => 'Aktueller Status',
                                            'width'   => '250px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'SensorID',
                                            'caption' => 'ID',
                                            'width'   => '80px',
                                            'onClick' => self::MODULE_PREFIX . '_ModifyActualVariableStatesConfigurationButton($id, "UpperLightUnitActualVariableStateConfigurationButton", $UpperLightUnitActualVariableStateList["SensorID"]);',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'Designation',
                                            'caption' => 'Bezeichnung',
                                            'width'   => '400px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'Color',
                                            'caption' => 'Farbe',
                                            'width'   => '120px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'Brightness',
                                            'caption' => 'Helligkeit',
                                            'width'   => '120px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'LastUpdate',
                                            'caption' => 'Letzte Aktualisierung',
                                            'width'   => '200px',
                                            'save'    => false
                                        ]
                                    ]
                                ],
                                [
                                    'type'     => 'OpenObjectButton',
                                    'name'     => 'UpperLightUnitActualVariableStateConfigurationButton',
                                    'caption'  => 'Bearbeiten',
                                    'visible'  => false,
                                    'objectID' => 0
                                ]
                            ]
                        ],
                        'onClick' => self::MODULE_PREFIX . '_GetUpperLightUnitActualVariableStates($id);'
                    ],
                    [
                        'type'     => 'List',
                        'name'     => 'UpperLightUnitTriggerList',
                        'caption'  => 'Auslöser',
                        'rowCount' => $amountRows,
                        'add'      => true,
                        'delete'   => true,
                        'sort'     => [
                            'column'    => 'Priority',
                            'direction' => 'descending'
                        ],
                        'columns' => [
                            [
                                'caption' => 'Aktiviert',
                                'name'    => 'Use',
                                'width'   => '100px',
                                'add'     => true,
                                'edit'    => [
                                    'type' => 'CheckBox'
                                ]
                            ],
                            [
                                'caption' => 'Priorität',
                                'name'    => 'Priority',
                                'width'   => '150px',
                                'add'     => 1,
                                'edit'    => [
                                    'type'    => 'Select',
                                    'options' => [
                                        [
                                            'caption' => '1 - niedrig',
                                            'value'   => 1
                                        ],
                                        [
                                            'caption' => '2',
                                            'value'   => 2
                                        ],
                                        [
                                            'caption' => '3',
                                            'value'   => 3
                                        ],
                                        [
                                            'caption' => '4 - mittel',
                                            'value'   => 4
                                        ],
                                        [
                                            'caption' => '5 - mittel',
                                            'value'   => 5
                                        ],
                                        [
                                            'caption' => '6',
                                            'value'   => 6
                                        ],
                                        [
                                            'caption' => '7',
                                            'value'   => 7
                                        ],
                                        [
                                            'caption' => '8 - hoch',
                                            'value'   => 8
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'caption' => 'Bezeichnung',
                                'name'    => 'Designation',
                                'onClick' => self::MODULE_PREFIX . '_ModifyTriggerListButton($id, "UpperLightUnitTriggerListConfigurationButton", $UpperLightUnitTriggerList["PrimaryCondition"]);',
                                'width'   => '300px',

                                'add'     => '',
                                'edit'    => [
                                    'type' => 'ValidationTextBox'
                                ]
                            ],
                            [
                                'caption' => 'Primäre Bedingung',
                                'name'    => 'PrimaryCondition',
                                'width'   => '1000px',
                                'add'     => '',
                                'edit'    => [
                                    'type' => 'SelectCondition'
                                ]
                            ],
                            [
                                'caption' => 'Weitere Bedingungen',
                                'name'    => 'SecondaryCondition',
                                'width'   => '1000px',
                                'add'     => '',
                                'edit'    => [
                                    'type'  => 'SelectCondition',
                                    'multi' => true
                                ]
                            ],
                            [
                                'caption' => 'Farbe',
                                'name'    => 'Color',
                                'width'   => '100px',
                                'add'     => 0,
                                'edit'    => [
                                    'type'    => 'Select',
                                    'options' => [
                                        [
                                            'caption' => 'Aus',
                                            'value'   => 0
                                        ],
                                        [
                                            'caption' => 'Blau',
                                            'value'   => 1
                                        ],
                                        [
                                            'caption' => 'Grün',
                                            'value'   => 2
                                        ],
                                        [
                                            'caption' => 'Türkis',
                                            'value'   => 3
                                        ],
                                        [
                                            'caption' => 'Rot',
                                            'value'   => 4
                                        ],
                                        [
                                            'caption' => 'Violett',
                                            'value'   => 5
                                        ],
                                        [
                                            'caption' => 'Gelb',
                                            'value'   => 6
                                        ],
                                        [
                                            'caption' => 'Weiß',
                                            'value'   => 7
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'caption' => 'Helligkeit',
                                'name'    => 'Brightness',
                                'width'   => '100px',
                                'add'     => 100,
                                'edit'    => [
                                    'type'    => 'NumberSpinner',
                                    'suffix'  => '%',
                                    'minimum' => 0,
                                    'maximum' => 100
                                ]
                            ],
                            [
                                'caption' => 'Signalisierung erzwingen',
                                'name'    => 'ForceSignaling',
                                'width'   => '200px',
                                'add'     => false,
                                'edit'    => [
                                    'type' => 'CheckBox'
                                ]
                            ]
                        ],
                        'values' => $upperLightUnitTriggerListValues
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => 'Anzahl Auslöser: ' . $amountVariables
                    ],
                    [
                        'type'    => 'CheckBox',
                        'name'    => 'UpdateLowerLightUnit',
                        'caption' => 'Untere Leuchteinheit aktualisieren',
                    ],
                    [
                        'type'     => 'OpenObjectButton',
                        'name'     => 'UpperLightUnitTriggerListConfigurationButton',
                        'caption'  => 'Bearbeiten',
                        'visible'  => false,
                        'objectID' => 0
                    ]
                ]
            ];

        ##### Lower light unit

        //Lower light unit instance
        $lowerLightUnitDeviceInstance = $this->ReadPropertyInteger('LowerLightUnit');
        $enableLowerLightUnitDeviceInstanceButton = false;
        if ($lowerLightUnitDeviceInstance > 1 && @IPS_ObjectExists($lowerLightUnitDeviceInstance)) {
            $enableLowerLightUnitDeviceInstanceButton = true;
        }

        //Lower light unit color
        $lowerLightUnitDeviceColorVariable = $this->ReadPropertyInteger('LowerLightUnitDeviceColor');
        $enableLowerLightUnitDeviceColorButton = false;
        if ($lowerLightUnitDeviceColorVariable > 1 && @IPS_ObjectExists($lowerLightUnitDeviceColorVariable)) {
            $enableLowerLightUnitDeviceColorButton = true;
        }

        //Lower light unit brightness
        $lowerLightUnitDeviceBrightnessVariable = $this->ReadPropertyInteger('LowerLightUnitDeviceBrightness');
        $enableLowerLightUnitDeviceBrightnessButton = false;
        if ($lowerLightUnitDeviceBrightnessVariable > 1 && @IPS_ObjectExists($lowerLightUnitDeviceBrightnessVariable)) {
            $enableLowerLightUnitDeviceBrightnessButton = true;
        }

        //Lower light unit trigger list
        $lowerLightUnitTriggerListValues = [];
        $variables = json_decode($this->ReadPropertyString('LowerLightUnitTriggerList'), true);
        $amountRows = count($variables) + 1;
        if ($amountRows == 1) {
            $amountRows = 3;
        }
        $amountVariables = count($variables);
        foreach ($variables as $variable) {
            $sensorID = 0;
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $sensorID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                    }
                }
            }
            //Check conditions first
            $conditions = true;
            if ($sensorID <= 1 || !@IPS_ObjectExists($sensorID)) {
                $conditions = false;
            }
            if ($variable['SecondaryCondition'] != '') {
                $secondaryConditions = json_decode($variable['SecondaryCondition'], true);
                if (array_key_exists(0, $secondaryConditions)) {
                    if (array_key_exists('rules', $secondaryConditions[0])) {
                        $rules = $secondaryConditions[0]['rules']['variable'];
                        foreach ($rules as $rule) {
                            if (array_key_exists('variableID', $rule)) {
                                $id = $rule['variableID'];
                                if ($id <= 1 || !@IPS_ObjectExists($id)) {
                                    $conditions = false;
                                }
                            }
                        }
                    }
                }
            }
            $rowColor = '#FFC0C0'; //red
            if ($conditions) {
                $rowColor = '#C0FFC0'; //light green
                if (!$variable['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $lowerLightUnitTriggerListValues[] = ['rowColor' => $rowColor];
        }

        $form['elements'][] =
            [
                'type'    => 'ExpansionPanel',
                'name'    => 'Panel3',
                'caption' => 'Untere Leuchteinheit',
                'items'   => [
                    [
                        'type'    => 'Label',
                        'caption' => 'Gerät',
                        'italic'  => true,
                        'bold'    => true
                    ],
                    [
                        'type'    => 'Select',
                        'name'    => 'LowerLightUnitDeviceType',
                        'caption' => 'Typ',
                        'options' => [
                            [
                                'caption' => 'Kein Gerät',
                                'value'   => 0
                            ],
                            [
                                'caption' => 'HmIP-BSL, Kanal 12',
                                'value'   => 1
                            ]
                        ]
                    ],
                    [
                        'type'  => 'RowLayout',
                        'items' => [
                            [
                                'type'     => 'SelectInstance',
                                'name'     => 'LowerLightUnit',
                                'caption'  => 'Instanz',
                                'width'    => '600px',
                                'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "LowerLightUnitDeviceInstanceConfigurationButton", "ID " . $LowerLightUnit . " konfigurieren", $LowerLightUnit);'
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'LowerLightUnitDeviceInstanceConfigurationButton',
                                'caption'  => 'ID ' . $lowerLightUnitDeviceInstance . ' konfigurieren',
                                'visible'  => $enableLowerLightUnitDeviceInstanceButton,
                                'objectID' => $lowerLightUnitDeviceInstance
                            ]
                        ]
                    ],
                    [
                        'type'  => 'RowLayout',
                        'items' => [
                            [
                                'type'     => 'SelectVariable',
                                'name'     => 'LowerLightUnitDeviceColor',
                                'caption'  => 'Variable COLOR (Farbe)',
                                'width'    => '600px',
                                'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "LowerLightUnitDeviceColorConfigurationButton", "ID " . $LowerLightUnitDeviceColor . " bearbeiten", $LowerLightUnitDeviceColor);'
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'LowerLightUnitDeviceColorConfigurationButton',
                                'caption'  => 'ID ' . $lowerLightUnitDeviceColorVariable . ' bearbeiten',
                                'visible'  => $enableLowerLightUnitDeviceColorButton,
                                'objectID' => $lowerLightUnitDeviceColorVariable
                            ]
                        ]
                    ],
                    [
                        'type'  => 'RowLayout',
                        'items' => [
                            [
                                'type'     => 'SelectVariable',
                                'name'     => 'LowerLightUnitDeviceBrightness',
                                'caption'  => 'Variable LEVEL (Helligkeit)',
                                'width'    => '600px',
                                'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "LowerLightUnitDeviceBrightnessConfigurationButton", "ID " . $LowerLightUnitDeviceBrightness . " bearbeiten", $LowerLightUnitDeviceBrightness);'
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'LowerLightUnitDeviceBrightnessConfigurationButton',
                                'caption'  => 'ID ' . $lowerLightUnitDeviceBrightnessVariable . ' bearbeiten',
                                'visible'  => $enableLowerLightUnitDeviceBrightnessButton,
                                'objectID' => $lowerLightUnitDeviceBrightnessVariable
                            ]
                        ]
                    ],
                    [
                        'type'    => 'NumberSpinner',
                        'name'    => 'LowerLightUnitSwitchingDelay',
                        'caption' => 'Schaltverzögerung',
                        'minimum' => 0,
                        'suffix'  => 'Millisekunden'
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => ' '
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => 'Auslöser',
                        'italic'  => true,
                        'bold'    => true
                    ],
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Aktueller Status',
                        'popup'   => [
                            'caption' => 'Aktueller Status',
                            'items'   => [
                                [
                                    'type'     => 'List',
                                    'name'     => 'LowerLightUnitActualVariableStateList',
                                    'caption'  => 'Variablen',
                                    'add'      => false,
                                    'visible'  => false,
                                    'rowCount' => 1,
                                    'sort'     => [
                                        'column'    => 'ActualStatus',
                                        'direction' => 'ascending'
                                    ],
                                    'columns' => [
                                        [
                                            'name'    => 'ActualStatus',
                                            'caption' => 'Aktueller Status',
                                            'width'   => '250px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'SensorID',
                                            'caption' => 'ID',
                                            'width'   => '80px',
                                            'onClick' => self::MODULE_PREFIX . '_ModifyActualVariableStatesConfigurationButton($id, "LowerLightUnitActualVariableStateConfigurationButton", $LowerLightUnitActualVariableStateList["SensorID"]);',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'Designation',
                                            'caption' => 'Bezeichnung',
                                            'width'   => '400px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'Color',
                                            'caption' => 'Farbe',
                                            'width'   => '120px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'Brightness',
                                            'caption' => 'Helligkeit',
                                            'width'   => '120px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'LastUpdate',
                                            'caption' => 'Letzte Aktualisierung',
                                            'width'   => '200px',
                                            'save'    => false
                                        ]
                                    ]
                                ],
                                [
                                    'type'     => 'OpenObjectButton',
                                    'name'     => 'LowerLightUnitActualVariableStateConfigurationButton',
                                    'caption'  => 'Bearbeiten',
                                    'visible'  => false,
                                    'objectID' => 0
                                ]
                            ]
                        ],
                        'onClick' => self::MODULE_PREFIX . '_GetLowerLightUnitActualVariableStates($id);'
                    ],
                    [
                        'type'     => 'List',
                        'name'     => 'LowerLightUnitTriggerList',
                        'caption'  => 'Auslöser',
                        'rowCount' => $amountRows,
                        'add'      => true,
                        'delete'   => true,
                        'sort'     => [
                            'column'    => 'Priority',
                            'direction' => 'descending'
                        ],
                        'columns' => [
                            [
                                'caption' => 'Aktiviert',
                                'name'    => 'Use',
                                'width'   => '100px',
                                'add'     => true,
                                'edit'    => [
                                    'type' => 'CheckBox'
                                ]
                            ],
                            [
                                'caption' => 'Priorität',
                                'name'    => 'Priority',
                                'width'   => '150px',
                                'add'     => 1,
                                'edit'    => [
                                    'type'    => 'Select',
                                    'options' => [
                                        [
                                            'caption' => '1 - niedrig',
                                            'value'   => 1
                                        ],
                                        [
                                            'caption' => '2',
                                            'value'   => 2
                                        ],
                                        [
                                            'caption' => '3',
                                            'value'   => 3
                                        ],
                                        [
                                            'caption' => '4 - mittel',
                                            'value'   => 4
                                        ],
                                        [
                                            'caption' => '5 - mittel',
                                            'value'   => 5
                                        ],
                                        [
                                            'caption' => '6',
                                            'value'   => 6
                                        ],
                                        [
                                            'caption' => '7',
                                            'value'   => 7
                                        ],
                                        [
                                            'caption' => '8 - hoch',
                                            'value'   => 8
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'caption' => 'Bezeichnung',
                                'name'    => 'Designation',
                                'onClick' => self::MODULE_PREFIX . '_ModifyTriggerListButton($id, "LowerLightUnitTriggerListConfigurationButton", $LowerLightUnitTriggerList["PrimaryCondition"]);',
                                'width'   => '300px',
                                'add'     => '',
                                'edit'    => [
                                    'type' => 'ValidationTextBox'
                                ]
                            ],
                            [
                                'caption' => 'Primäre Bedingung',
                                'name'    => 'PrimaryCondition',
                                'width'   => '1000px',
                                'add'     => '',
                                'edit'    => [
                                    'type' => 'SelectCondition'
                                ]
                            ],
                            [
                                'caption' => 'Weitere Bedingungen',
                                'name'    => 'SecondaryCondition',
                                'width'   => '1000px',
                                'add'     => '',
                                'edit'    => [
                                    'type'  => 'SelectCondition',
                                    'multi' => true
                                ]
                            ],
                            [
                                'caption' => 'Farbe',
                                'name'    => 'Color',
                                'width'   => '100px',
                                'add'     => 0,
                                'edit'    => [
                                    'type'    => 'Select',
                                    'options' => [
                                        [
                                            'caption' => 'Aus',
                                            'value'   => 0
                                        ],
                                        [
                                            'caption' => 'Blau',
                                            'value'   => 1
                                        ],
                                        [
                                            'caption' => 'Grün',
                                            'value'   => 2
                                        ],
                                        [
                                            'caption' => 'Türkis',
                                            'value'   => 3
                                        ],
                                        [
                                            'caption' => 'Rot',
                                            'value'   => 4
                                        ],
                                        [
                                            'caption' => 'Violett',
                                            'value'   => 5
                                        ],
                                        [
                                            'caption' => 'Gelb',
                                            'value'   => 6
                                        ],
                                        [
                                            'caption' => 'Weiß',
                                            'value'   => 7
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'caption' => 'Helligkeit',
                                'name'    => 'Brightness',
                                'width'   => '100px',
                                'add'     => 100,
                                'edit'    => [
                                    'type'    => 'NumberSpinner',
                                    'suffix'  => '%',
                                    'minimum' => 0,
                                    'maximum' => 100
                                ]
                            ],
                            [
                                'caption' => 'Signalisierung erzwingen',
                                'name'    => 'ForceSignaling',
                                'width'   => '200px',
                                'add'     => false,
                                'edit'    => [
                                    'type' => 'CheckBox'
                                ]
                            ]
                        ],
                        'values' => $lowerLightUnitTriggerListValues
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => 'Anzahl Auslöser: ' . $amountVariables
                    ],
                    [
                        'type'    => 'CheckBox',
                        'name'    => 'UpdateUpperLightUnit',
                        'caption' => 'Obere Leuchteinheit aktualisieren',

                    ],
                    [
                        'type'     => 'OpenObjectButton',
                        'name'     => 'LowerLightUnitTriggerListConfigurationButton',
                        'caption'  => 'Bearbeiten',
                        'visible'  => false,
                        'objectID' => 0
                    ]
                ]
            ];

        //Check status
        $form['elements'][] =
            [
                'type'    => 'ExpansionPanel',
                'name'    => 'Panel4',
                'caption' => 'Aktualisierung',
                'items'   => [
                    [
                        'type'    => 'CheckBox',
                        'name'    => 'AutomaticStatusUpdate',
                        'caption' => 'Automatische Aktualisierung'
                    ],
                    [
                        'type'    => 'NumberSpinner',
                        'name'    => 'CheckStatusInterval',
                        'caption' => 'Intervall',
                        'minimum' => 0,
                        'suffix'  => 'Sekunden'
                    ]
                ]
            ];

        //Command control
        $id = $this->ReadPropertyInteger('CommandControl');
        $enableButton = false;
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $enableButton = true;
        }

        $form['elements'][] =
            [
                'type'    => 'ExpansionPanel',
                'name'    => 'Panel5',
                'caption' => 'Ablaufsteuerung',
                'items'   => [
                    [
                        'type'  => 'RowLayout',
                        'items' => [
                            [
                                'type'     => 'SelectModule',
                                'name'     => 'CommandControl',
                                'caption'  => 'Instanz',
                                'moduleID' => self::ABLAUFSTEUERUNG_MODULE_GUID,
                                'width'    => '600px',
                                'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "CommandControlConfigurationButton", "ID " . $CommandControl . " konfigurieren", $CommandControl);'
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'caption'  => 'ID ' . $id . ' konfigurieren',
                                'name'     => 'CommandControlConfigurationButton',
                                'visible'  => $enableButton,
                                'objectID' => $id
                            ],
                            [
                                'type'    => 'Button',
                                'caption' => 'Neue Instanz erstellen',
                                'onClick' => self::MODULE_PREFIX . '_CreateCommandControlInstance($id);'
                            ]
                        ]
                    ]
                ]
            ];

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel6',
            'caption' => 'Deaktivierung',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Deaktivierung',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Nachfolgende Funktionen werden bei Deaktivierung verwendet.'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Obere Leuchteinheit',
                    'italic'  => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'DeactivateUpperLightUnitChangeColor',
                    'caption' => 'Farbe ändern'
                ],
                [
                    'type'    => 'Select',
                    'name'    => 'DeactivationUpperLightUnitColor',
                    'caption' => 'Farbe',
                    'options' => [
                        [
                            'caption' => '0 - Aus',
                            'value'   => 0
                        ],
                        [
                            'caption' => '1 - Blau',
                            'value'   => 1
                        ],
                        [
                            'caption' => '2 - Grün',
                            'value'   => 2
                        ],
                        [
                            'caption' => '3 - Türkis',
                            'value'   => 3
                        ],
                        [
                            'caption' => '4 - Rot',
                            'value'   => 4
                        ],
                        [
                            'caption' => '5 - Violett',
                            'value'   => 5
                        ],
                        [
                            'caption' => '6 - Gelb',
                            'value'   => 6
                        ],
                        [
                            'caption' => '7 - Weiß',
                            'value'   => 7
                        ]
                    ]
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'DeactivateUpperLightUnitChangeBrightness',
                    'caption' => 'Helligkeit ändern'
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'DeactivationUpperLightUnitBrightness',
                    'caption' => 'Helligkeit',
                    'suffix'  => '%',
                    'minimum' => 0,
                    'maximum' => 100
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Untere Leuchteinheit',
                    'italic'  => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'DeactivateLowerLightUnitChangeColor',
                    'caption' => 'Farbe ändern'
                ],
                [
                    'type'    => 'Select',
                    'name'    => 'DeactivationLowerLightUnitColor',
                    'caption' => 'Farbe',
                    'options' => [
                        [
                            'caption' => '0 - Aus',
                            'value'   => 0
                        ],
                        [
                            'caption' => '1 - Blau',
                            'value'   => 1
                        ],
                        [
                            'caption' => '2 - Grün',
                            'value'   => 2
                        ],
                        [
                            'caption' => '3 - Türkis',
                            'value'   => 3
                        ],
                        [
                            'caption' => '4 - Rot',
                            'value'   => 4
                        ],
                        [
                            'caption' => '5 - Violett',
                            'value'   => 5
                        ],
                        [
                            'caption' => '6 -Gelb',
                            'value'   => 6
                        ],
                        [
                            'caption' => '7 - Weiß',
                            'value'   => 7
                        ]
                    ]
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'DeactivateLowerLightUnitChangeBrightness',
                    'caption' => 'Helligkeit ändern'
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'DeactivationLowerLightUnitBrightness',
                    'caption' => 'Helligkeit',
                    'suffix'  => '%',
                    'minimum' => 0,
                    'maximum' => 100
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Reaktivierung',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Nachfolgende Funktionen werden nur verwendet, wenn keine Auslöser genutzt werden.'
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Ansonsten wird anhand der Auslöserliste geschaltet.'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Obere Leuchteinheit',
                    'italic'  => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'ReactivateUpperLightUnitLastColor',
                    'caption' => 'Letzte Farbe'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'ReactivateUpperLightUnitLastBrightness',
                    'caption' => 'Letzte Helligkeit'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Untere Leuchteinheit',
                    'italic'  => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'ReactivateLowerLightUnitLastColor',
                    'caption' => 'Letzte Farbe'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'ReactivateLowerLightUnitLastBrightness',
                    'caption' => 'Letzte Helligkeit'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Automatische Deaktivierung',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseAutomaticDeactivation',
                    'caption' => 'Automatische Deaktivierung'
                ],
                [
                    'type'    => 'SelectTime',
                    'name'    => 'AutomaticDeactivationStartTime',
                    'caption' => 'Startzeit'
                ],
                [
                    'type'    => 'SelectTime',
                    'name'    => 'AutomaticDeactivationEndTime',
                    'caption' => 'Endzeit'
                ]
            ]
        ];

        //Visualisation
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel7',
            'caption' => 'Visualisierung',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableActive',
                    'caption' => 'Aktiv'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Obere Leuchteinheit',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableUpperLightUnitColor',
                    'caption' => 'Farbauswahl'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableUpperLightUnitBrightness',
                    'caption' => 'Helligkeit'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Untere Leuchteinheit',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableLowerLightUnitColor',
                    'caption' => 'Farbauswahl'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableLowerLightUnitBrightness',
                    'caption' => 'Helligkeit'
                ]
            ]
        ];

        ########## Actions

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => 'Schaltelemente'
            ];

        $form['actions'][] =
            [
                'type' => 'TestCenter',
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        //Registered references
        $registeredReferences = [];
        $references = $this->GetReferenceList();
        $amountReferences = count($references);
        if ($amountReferences == 0) {
            $amountReferences = 3;
        }
        foreach ($references as $reference) {
            $name = 'Objekt #' . $reference . ' existiert nicht';
            $location = '';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($reference)) {
                $name = IPS_GetName($reference);
                $location = IPS_GetLocation($reference);
                $rowColor = '#C0FFC0'; //light green
            }
            $registeredReferences[] = [
                'ObjectID'         => $reference,
                'Name'             => $name,
                'VariableLocation' => $location,
                'rowColor'         => $rowColor];
        }

        //Registered messages
        $registeredMessages = [];
        $messages = $this->GetMessageList();
        $amountMessages = count($messages);
        if ($amountMessages == 0) {
            $amountMessages = 3;
        }
        foreach ($messages as $id => $messageID) {
            $name = 'Objekt #' . $id . ' existiert nicht';
            $location = '';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($id)) {
                $name = IPS_GetName($id);
                $location = IPS_GetLocation($id);
                $rowColor = '#C0FFC0'; //light green
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
            $registeredMessages[] = [
                'ObjectID'           => $id,
                'Name'               => $name,
                'VariableLocation'   => $location,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription,
                'rowColor'           => $rowColor];
        }

        //Developer area
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Entwicklerbereich',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Registrierte Referenzen',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredReferences',
                    'rowCount' => $amountReferences,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " bearbeiten", $RegisteredReferences["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                        ],
                        [
                            'caption' => 'Objektbaum',
                            'name'    => 'VariableLocation',
                            'width'   => '700px'
                        ]
                    ],
                    'values' => $registeredReferences
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredReferencesConfigurationButton',
                    'caption'  => 'Bearbeiten',
                    'visible'  => false,
                    'objectID' => 0
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Registrierte Nachrichten',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredMessages',
                    'rowCount' => $amountMessages,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " bearbeiten", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                        ],
                        [
                            'caption' => 'Objektbaum',
                            'name'    => 'VariableLocation',
                            'width'   => '700px'
                        ],
                        [
                            'caption' => 'Nachrichten ID',
                            'name'    => 'MessageID',
                            'width'   => '150px'
                        ],
                        [
                            'caption' => 'Nachrichten Bezeichnung',
                            'name'    => 'MessageDescription',
                            'width'   => '250px'
                        ]
                    ],
                    'values' => $registeredMessages
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredMessagesConfigurationButton',
                    'caption'  => 'Bearbeiten',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        //Dummy info message
        $form['actions'][] =
            [
                'type'    => 'PopupAlert',
                'name'    => 'InfoMessage',
                'visible' => false,
                'popup'   => [
                    'closeCaption' => 'OK',
                    'items'        => [
                        [
                            'type'    => 'Label',
                            'name'    => 'InfoMessageLabel',
                            'caption' => '',
                            'visible' => true
                        ]
                    ]
                ]
            ];

        ########## Status

        $form['status'][] = [
            'code'    => 101,
            'icon'    => 'active',
            'caption' => 'Statusanzeige Homematic IP wird erstellt',
        ];
        $form['status'][] = [
            'code'    => 102,
            'icon'    => 'active',
            'caption' => 'Statusanzeige Homematic IP ist aktiv',
        ];
        $form['status'][] = [
            'code'    => 103,
            'icon'    => 'active',
            'caption' => 'Statusanzeige Homematic IP wird gelöscht',
        ];
        $form['status'][] = [
            'code'    => 104,
            'icon'    => 'inactive',
            'caption' => 'Statusanzeige Homematic IP ist inaktiv',
        ];
        $form['status'][] = [
            'code'    => 200,
            'icon'    => 'inactive',
            'caption' => 'Es ist Fehler aufgetreten, weitere Informationen unter Meldungen, im Log oder Debug!',
        ];

        return json_encode($form);
    }
}