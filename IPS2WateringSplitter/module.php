<?
    // Klassendefinition
    class IPS2WateringSplitter extends IPSModule {
 	
       public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("WeekplanState", 0);
	}
	    
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
            	$this->RegisterPropertyInteger("ActuatorID", 0);
            	$this->RegisterPropertyInteger("SensorID", 0);
		$this->RegisterPropertyInteger("MaxWatering", 30);
            	$this->RegisterPropertyInteger("MinWaitTime", 180);
		
		$this->RegisterEvent("Wochenplan", "IPS2Watering_Event_".$this->InstanceID, 2, $this->InstanceID, 30);
		// Anlegen der Daten für den Wochenplan
		for ($i = 0; $i <= 6; $i++) {
			IPS_SetEventScheduleGroup($this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID), $i, pow(2, $i));
		}
		IPS_SetEventScheduleAction($this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID), 1, "Freigabe", 0x40FF00, "WateringSwitch_SetState(\$_IPS['TARGET'], 1);");	
		IPS_SetEventScheduleAction($this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID), 2, "Sperrzeit", 0xFF0040, "WateringSwitch_SetState(\$_IPS['TARGET'], 1);");	
		$this->RegisterTimer("WeekplanState", 0, 'WateringSwitch_TimerEventGetWeekplanState($_IPS["TARGET"]);'); 
		
            	$this->RegisterProfileInteger("IPS2Watering.WeekplanState", "Information", "", "", 0, 2, 1);
		IPS_SetVariableProfileAssociation("IPS2Watering.WeekplanState", 0, "Undefiniert", "Warning", 0xFF0040);
		IPS_SetVariableProfileAssociation("IPS2Watering.WeekplanState", 1, "Freigabe", "LockOpen", 0x40FF00);
		IPS_SetVariableProfileAssociation("IPS2Watering.WeekplanState", 2, "Sperrzeit", "LockClosed", 0xFF0040);
		
		$this->RegisterVariableBoolean("Automatic", "Automatik", "~Switch", 10);
		$this->EnableAction("Automatic");
            	$this->RegisterVariableBoolean("State", "Status", "~Switch", 20);	
		$this->RegisterVariableInteger("WeekplanState", "Wochenplanstatus", "IPS2Watering.WeekplanState", 30);
		
        }
	    
	public function GetConfigurationForm() 
        { 
            	$arrayStatus = array(); 
            	$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
            	$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
            	$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
            	$arrayElements = array(); 
            	$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
            	$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
            	$arrayElements[] = array("type" => "Label", "label" => "Ventil-Aktor-Variable (Boolean)");
            	$arrayElements[] = array("type" => "SelectVariable", "name" => "ActuatorID", "caption" => "Aktor"); 
            	$arrayElements[] = array("type" => "Label", "label" => "Bodenfeuchtigkeits-Sensor-Variable (Float)");
            	$arrayElements[] = array("type" => "SelectVariable", "name" => "SensorID", "caption" => "Aktor"); 
            	$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
            	$arrayElements[] = array("type" => "Label", "label" => "Maximale Bewässerungzeit im Automatik-Betrieb in Minuten:");
	    	$arrayElements[] = array("type" => "NumberSpinner", "name" => "MaxWatering",  "caption" => "Dauer (min)");
		$arrayElements[] = array("type" => "Label", "label" => "Minimale Pause zwischen Bewässerungphasen im Automatik-Betrieb in Minuten:");
	    	$arrayElements[] = array("type" => "NumberSpinner", "name" => "MinWaitTime",  "caption" => "Dauer (min)");
		
		$arrayActions = array();
            	$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
            	return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
        }  
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Registrierung für die Änderung des Aktor-Status
		If ($this->ReadPropertyInteger("ActuatorID") > 0) {
			$this->RegisterMessage($this->ReadPropertyInteger("ActuatorID"), 10603);
		}
		
		// Registrierung für die Änderung des Wochenplans
		$WeekplanID = $this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID);
		$this->RegisterMessage($WeekplanID, 10821);
		$this->RegisterMessage($WeekplanID, 10822);
		$this->RegisterMessage($WeekplanID, 10823);
		
		
		If (GetValueBoolean($this->GetIDForIdent("Automatic")) == true) {
			$this->DisableAction("State");
		}
		else {
			$this->EnableAction("State");
		}
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			
			$this->GetWeekplanState($WeekplanID);
			$this->SetTimerInterval("WeekplanState", (30 * 1000));
			$this->SetStatus(102);
		}
		else {
			$this->SetTimerInterval("WeekplanState", 0);
			$this->SetStatus(104);
		}
        }
 
        
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
			case "Automatic":
			    If ($this->ReadPropertyBoolean("Open") == true) {
				   SetValueBoolean($this->GetIDForIdent("Automatic"),  $Value);
				   If ($Value == true) {
					$this->DisableAction("State");
				   }
				   else {
					$this->EnableAction("State");
				   }
			    }
			    break;
				
			case "State":
			    If ($this->ReadPropertyBoolean("Open") == true) {
				    If (GetValueBoolean($this->GetIDForIdent("Automatic")) == false) {
					    SetValueBoolean($this->GetIDForIdent("State"),  $Value);
					    If ($this->ReadPropertyInteger("ActuatorID") > 0) {
						    RequestAction($this->ReadPropertyInteger("ActuatorID"), $Value);
					    }
				    }
			    }
			    break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}    
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case 10603:
				// Änderung der Vorlauf-Temperatur
				If ($SenderID == $this->ReadPropertyInteger("ActuatorID")) {
					$this->SendDebug("MessageSink", "Ausloeser Aenderung Aktor-Status", 0);
					
				}
			case 10821:
				// Änderung des Wochenplans
				$this->SendDebug("MessageSink", "Ausloeser Aenderung Wochenplan", 0);
				$WeekplanID = $this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID);
				$this->GetWeekplanState($WeekplanID);
				break;
			case 10822:
				// Änderung des Wochenplans
				$this->SendDebug("MessageSink", "Ausloeser Aenderung Wochenplan", 0);
				$WeekplanID = $this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID);
				$this->GetWeekplanState($WeekplanID);
				break;
			case 10823:
				// Änderung des Wochenplans
				$this->SendDebug("MessageSink", "Ausloeser Aenderung Wochenplan", 0);
				$WeekplanID = $this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID);
				$this->GetWeekplanState($WeekplanID);
				break;
		}
    	}        
	    
	public function SetState()
	{
		$this->SendDebug("SetState", "Ausloesung", 0);
		$WeekplanID = $this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID);
		$this->GetWeekplanState($WeekplanID);
	}
	
	public function TimerEventGetWeekplanState()
	{  
		$WeekplanID = $this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID);
		$this->GetWeekplanState($WeekplanID);
	}
	    
	private function GetWeekplanState(int $WeekplanID)
	{
		$this->SendDebug("GetWeekplanState", "Wochenplan Status einlesen", 0);
		$e = IPS_GetEvent($WeekplanID);
		$actionID = false;
		//Durch alle Gruppen gehen
		foreach($e['ScheduleGroups'] as $g) 
			{
			//Überprüfen ob die Gruppe für heute zuständig ist
		    	if(($g['Days'] & pow(2,date("N",time())-1)) > 0)  
			{
				//Aktuellen Schaltpunkt suchen. Wir nutzen die Eigenschaft, dass die Schaltpunkte immer aufsteigend sortiert sind.
				foreach($g['Points'] as $p) 
				{
			   		if(date("H") * 3600 + date("i") * 60 + date("s") >= $p['Start']['Hour'] * 3600 + $p['Start']['Minute'] * 60 + $p['Start']['Second']) 
					{
			      			$actionID = $p['ActionID'];
			   		} 
					else 
					{
			      			break; //Sobald wir drüber sind, können wir abbrechen.
			   		}
		       		}
				break; //Sobald wir unseren Tag gefunden haben, können wir die Schleife abbrechen. Jeder Tag darf nur in genau einer Gruppe sein.
		    	}
		}
		$this->SendDebug("GetWeekplanState", "Ergebnis: ".intval($actionID), 0);
		If (GetValueInteger($this->GetIDForIdent("WeekplanState")) <> intval($actionID)) {
			SetValueInteger($this->GetIDForIdent("WeekplanState"),  intval($actionID));
		}
	}
	
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);        
	}
  
	private function RegisterEvent($Name, $Ident, $Typ, $Parent, $Position)
	{
		$eid = @$this->GetIDForIdent($Ident);
		if($eid === false) {
		    	$eid = 0;
		} elseif(IPS_GetEvent($eid)['EventType'] <> $Typ) {
		    	IPS_DeleteEvent($eid);
		    	$eid = 0;
		}
		//we need to create one
		if ($eid == 0) {
			$EventID = IPS_CreateEvent($Typ);
		    	IPS_SetParent($EventID, $Parent);
		    	IPS_SetIdent($EventID, $Ident);
		    	IPS_SetName($EventID, $Name);
		    	IPS_SetPosition($EventID, $Position);
		    	IPS_SetEventActive($EventID, true);  
		}
	}  
    }
?>
