<?php

/**
 * @project       Warnmelder/Warnmelder
 * @file          FS_MonitoredVariables.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait WM_MonitoredVariables
{
    /**
     * Creates links of monitored variables.
     *
     * @param int $LinkCategory
     * @return void
     * @throws Exception
     */
    public function CreateVariableLinks(int $LinkCategory): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($LinkCategory == 1 || @!IPS_ObjectExists($LinkCategory)) {
            echo 'Abbruch, bitte wählen Sie eine Kategorie aus!';
            return;
        }
        $icon = 'Warning';
        //Get all monitored variables
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        $targetIDs = [];
        $i = 0;
        foreach ($monitoredVariables as $variable) {
            if ($variable['Use']) {
                //Primary condition
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                                $targetIDs[$i] = ['name' => $variable['Designation'], 'targetID' => $id];
                                $i++;
                            }
                        }
                    }
                }
            }
        }
        //Sort array alphabetically by device name
        sort($targetIDs);
        //Get all existing links (links have not an ident field, so we use the object info field)
        $existingTargetIDs = [];
        $links = @IPS_GetLinkList();
        if (!empty($links)) {
            $i = 0;
            foreach ($links as $link) {
                $linkInfo = @IPS_GetObject($link)['ObjectInfo'];
                if ($linkInfo == self::MODULE_PREFIX . '.' . $this->InstanceID) {
                    //Get target id
                    $existingTargetID = @IPS_GetLink($link)['TargetID'];
                    $existingTargetIDs[$i] = ['linkID' => $link, 'targetID' => $existingTargetID];
                    $i++;
                }
            }
        }
        //Delete dead links
        $deadLinks = array_diff(array_column($existingTargetIDs, 'targetID'), array_column($targetIDs, 'targetID'));
        if (!empty($deadLinks)) {
            foreach ($deadLinks as $targetID) {
                $position = array_search($targetID, array_column($existingTargetIDs, 'targetID'));
                $linkID = $existingTargetIDs[$position]['linkID'];
                if (@IPS_LinkExists($linkID)) {
                    @IPS_DeleteLink($linkID);
                }
            }
        }
        //Create new links
        $newLinks = array_diff(array_column($targetIDs, 'targetID'), array_column($existingTargetIDs, 'targetID'));
        if (!empty($newLinks)) {
            foreach ($newLinks as $targetID) {
                $linkID = @IPS_CreateLink();
                @IPS_SetParent($linkID, $LinkCategory);
                $position = array_search($targetID, array_column($targetIDs, 'targetID'));
                @IPS_SetPosition($linkID, $position);
                $name = $targetIDs[$position]['name'];
                @IPS_SetName($linkID, $name);
                @IPS_SetLinkTargetID($linkID, $targetID);
                @IPS_SetInfo($linkID, self::MODULE_PREFIX . '.' . $this->InstanceID);
                @IPS_SetIcon($linkID, $icon);
            }
        }
        //Edit existing links
        $existingLinks = array_intersect(array_column($existingTargetIDs, 'targetID'), array_column($targetIDs, 'targetID'));
        if (!empty($existingLinks)) {
            foreach ($existingLinks as $targetID) {
                $position = array_search($targetID, array_column($targetIDs, 'targetID'));
                $targetID = $targetIDs[$position]['targetID'];
                $index = array_search($targetID, array_column($existingTargetIDs, 'targetID'));
                $linkID = $existingTargetIDs[$index]['linkID'];
                @IPS_SetPosition($linkID, $position);
                $name = $targetIDs[$position]['name'];
                @IPS_SetName($linkID, $name);
                @IPS_SetInfo($linkID, self::MODULE_PREFIX . '.' . $this->InstanceID);
                @IPS_SetIcon($linkID, $icon);
            }
        }
        echo 'Die Variablenverknüpfungen wurden erfolgreich erstellt!';
    }

    /**
     * Determines the trigger variables automatically.
     *
     * @param string $SelectIdents
     * @param string $ObjectIdents
     * @return void
     * @throws Exception
     */
    public function DetermineTriggerVariables(string $SelectIdents, string $ObjectIdents): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Auswahl: ' . $SelectIdents, 0);
        $this->SendDebug(__FUNCTION__, 'Identifikator: ' . $ObjectIdents, 0);
        //Determine variables first
        $determinedVariables = [];
        foreach (@IPS_GetVariableList() as $variable) {
            if ($SelectIdents == '') {
                if ($ObjectIdents == '') {
                    echo 'Abbruch, es wurde kein Identifikator angegeben!';
                    return;
                }
            } else {
                $ObjectIdents = $SelectIdents;
            }
            $objectIdents = str_replace(' ', '', $ObjectIdents);
            $objectIdents = explode(',', $objectIdents);
            foreach ($objectIdents as $objectIdent) {
                $object = @IPS_GetObject($variable);
                if ($object['ObjectIdent'] == $objectIdent) {
                    $name = @IPS_GetName($variable);
                    $address = '';
                    $parent = @IPS_GetParent($variable);
                    if ($parent > 1 && @IPS_ObjectExists($parent)) { //0 = main category, 1 = none
                        $parentObject = @IPS_GetObject($parent);
                        if ($parentObject['ObjectType'] == 1) { //1 = instance
                            $name = strstr(@IPS_GetName($parent), ':', true);
                            if (!$name) {
                                $name = @IPS_GetName($parent);
                            }
                            $address = @IPS_GetProperty($parent, 'Address');
                            if (!$address) {
                                $address = '';
                            }
                        }
                    }
                    $value = true;
                    if (IPS_GetVariable($variable)['VariableType'] == 1) {
                        $value = 1;
                    }
                    $primaryCondition[0] = [
                        'id'        => 0,
                        'parentID'  => 0,
                        'operation' => 0,
                        'rules'     => [
                            'variable' => [
                                '0' => [
                                    'id'         => 0,
                                    'variableID' => $variable,
                                    'comparison' => 0,
                                    'value'      => $value,
                                    'type'       => 0
                                ]
                            ],
                            'date'         => [],
                            'time'         => [],
                            'dayOfTheWeek' => []
                        ]
                    ];
                    $determinedVariables[] = [
                        'Use'                => true,
                        'Designation'        => $name,
                        'Comment'            => $address,
                        'PrimaryCondition'   => json_encode($primaryCondition),
                        'SecondaryCondition' => '[]'];
                }
            }
        }
        //Get already listed variables
        $listedVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($determinedVariables as $determinedVariable) {
            if (array_key_exists('PrimaryCondition', $determinedVariable)) {
                $primaryCondition = json_decode($determinedVariable['PrimaryCondition'], true);
                if ($primaryCondition != '') {
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $determinedVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($determinedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                                //Check variable id with already listed variable ids
                                $add = true;
                                foreach ($listedVariables as $listedVariable) {
                                    if (array_key_exists('PrimaryCondition', $listedVariable)) {
                                        $primaryCondition = json_decode($listedVariable['PrimaryCondition'], true);
                                        if ($primaryCondition != '') {
                                            if (array_key_exists(0, $primaryCondition)) {
                                                if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                                    $listedVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                                    if ($listedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                                                        if ($determinedVariableID == $listedVariableID) {
                                                            $add = false;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                //Add new variable to already listed variables
                                if ($add) {
                                    $listedVariables[] = $determinedVariable;
                                }
                            }
                        }
                    }
                }
            }
        }
        //Sort variables by name
        array_multisort(array_column($listedVariables, 'Designation'), SORT_ASC, $listedVariables);
        @IPS_SetProperty($this->InstanceID, 'TriggerList', json_encode(array_values($listedVariables)));
        if (@IPS_HasChanges($this->InstanceID)) {
            @IPS_ApplyChanges($this->InstanceID);
        }
        if (!empty($determinedVariables)) {
            echo 'Die Auslöser wurden erfolgreich hinzugefügt!';
        } else {
            echo 'Es wurden keinen Auslöser gefunden!';
        }
    }

    /**
     * Restes the attribute for critical variables.
     *
     * @return void
     * @throws Exception
     */
    public function ResetCriticalVariables(): void
    {
        $this->WriteAttributeString('CriticalVariables', '[]');
        echo 'Die kritischen Variablen wurden erfolgreich zurückgesetzt!';
    }

    /**
     * Updates the status.
     *
     * @return bool
     * false    = OK
     * true     = Alarm
     *
     * @throws Exception
     */
    public function UpdateStatus(): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if (!$this->CheckForExistingVariables()) {
            return false;
        }

        ##### Update overall status

        $variables = json_decode($this->GetMonitoredVariables(), true);
        $actualOverallStatus = false;
        foreach ($variables as $variable) {
            if ($variable['ActualStatus'] == 1) {
                $actualOverallStatus = true;
            }
        }
        $this->SetValue('Status', $actualOverallStatus);

        $this->SetValue('LastUpdate', date('d.m.Y H:i:s'));

        ##### Update overview list for WebFront

        $string = '';
        if ($this->ReadPropertyBoolean('EnableAlarmSensorList')) {
            $string .= "<table style='width: 100%; border-collapse: collapse;'>";
            $string .= '<tr><td><b>Status</b></td><td><b>Name</b></td><td><b>Bemerkung</b></td><td><b>ID</b></td></tr>';
            //Sort variables by name
            array_multisort(array_column($variables, 'Name'), SORT_ASC, $variables);
            //Rebase array
            $variables = array_values($variables);
            $separator = false;
            if (!empty($variables)) {
                //Show sensors with alarm first
                $separator = false;
                if ($this->ReadPropertyBoolean('EnableAlarm')) {
                    foreach ($variables as $variable) {
                        $id = $variable['ID'];
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            if ($variable['ActualStatus'] == 1) {
                                $separator = true;
                                $string .= '<tr><td>' . $variable['StatusText'] . '</td><td>' . $variable['Name'] . '</td><td>' . $variable['Comment'] . '</td><td>' . $id . '</td></tr>';
                            }
                        }
                    }
                }
                //Sensors with no alarm are next
                if ($this->ReadPropertyBoolean('EnableOK')) {
                    //Check if we have an active element for a spacer
                    $existingElement = false;
                    foreach ($variables as $variable) {
                        $id = $variable['ID'];
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            if ($variable['ActualStatus'] == 0) {
                                $existingElement = true;
                            }
                        }
                    }
                    //Add spacer
                    if ($separator && $existingElement) {
                        $string .= '<tr><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td></tr>';
                    }
                    //Add sensors
                    foreach ($variables as $variable) {
                        $id = $variable['ID'];
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            if ($variable['ActualStatus'] == 0) {
                                $string .= '<tr><td>' . $variable['StatusText'] . '</td><td>' . $variable['Name'] . '</td><td>' . $variable['Comment'] . '</td><td>' . $id . '</td></tr>';
                            }
                        }
                    }
                }
            }
            $string .= '</table>';
        }
        $this->SetValue('AlarmSensorList', $string);

        ##### Last triggering detector

        $triggeringDetectorName = '';
        foreach ($variables as $variable) {
            $id = $variable['ID'];
            if ($id != 0 && IPS_ObjectExists($id)) {
                if ($variable['ActualStatus'] == 1) {
                    $triggeringDetectorName = $variable['Name'];
                }
            }
        }
        $this->SetValue('TriggeringDetector', $triggeringDetectorName);

        ##### Notification

        $criticalVariables = json_decode($this->ReadAttributeString('CriticalVariables'), true);

        foreach ($variables as $variable) {
            $id = $variable['ID'];
            if ($id != 0 && IPS_ObjectExists($id)) {

                //Alarm
                if ($variable['ActualStatus'] == 1) {
                    if (!in_array($id, $criticalVariables)) {
                        //Add to critical variables
                        $criticalVariables[] = $id;
                        //Notification
                        $this->SendNotification(1, $variable['Name']);
                        //Push notification
                        $this->SendPushNotification(1, $variable['Name']);
                        //Mailer notification
                        $this->SendMailerNotification(1, $variable['Name']);
                    }
                }

                //OK
                if ($variable['ActualStatus'] == 0) {
                    if (in_array($id, $criticalVariables)) {
                        //Remove from critical variables
                        $criticalVariables = array_diff($criticalVariables, [$id]);
                        //Notification
                        $this->SendNotification(0, $variable['Name']);
                        //Push notification
                        $this->SendPushNotification(0, $variable['Name']);
                        //Mailer notification
                        $this->SendMailerNotification(0, $variable['Name']);
                    }
                }
            }
        }
        $this->WriteAttributeString('CriticalVariables', json_encode(array_values($criticalVariables)));
        return $actualOverallStatus;
    }

    /**
     * Gets the monitored variables and their status.
     *
     * @return string
     * @throws Exception
     */
    public function GetMonitoredVariables(): string
    {
        $result = [];
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($monitoredVariables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $id = 0;
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($id <= 1 || @!IPS_ObjectExists($id)) { //0 = main category, 1 = none
                            continue;
                        }
                    }
                }
            }
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $actualStatus = 0; //OK
                $statusText = $this->ReadPropertyString('StatusTextOK');
                if (IPS_IsConditionPassing($variable['PrimaryCondition']) && IPS_IsConditionPassing($variable['SecondaryCondition'])) {
                    $actualStatus = 1; //Alarm
                    $statusText = $this->ReadPropertyString('StatusTextAlarm');
                }
                $result[] = [
                    'ID'           => $id,
                    'Name'         => $variable['Designation'],
                    'Comment'      => $variable['Comment'],
                    'ActualStatus' => $actualStatus,
                    'StatusText'   => $statusText];
            }
        }
        if (!empty($result)) {
            //Sort variables by name
            array_multisort(array_column($result, 'Name'), SORT_ASC, $result);
        }
        return json_encode($result);
    }

    #################### Private

    /**
     * Checks for monitored variables.
     *
     * @return bool
     * false =  There are no monitored variables
     * true =   There are monitored variables
     * @throws Exception
     */
    private function CheckForExistingVariables(): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($monitoredVariables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                            return true;
                        }
                    }
                }
            }
        }
        $this->SendDebug(__FUNCTION__, 'Abbruch, Es werden keine Variablen überwacht!', 0);
        return false;
    }
}