<?
    // Klassendefinition
    class IPS2WateringSwitch extends IPSModule {
 
       
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
            	$this->RegisterPropertyInteger("ActuatorID", 0);
            	$this->RegisterPropertyInteger("SensorID", 0);
		$this->RegisterPropertyInteger("MaxWatering", 30);
            	$this->RegisterPropertyInteger("MinWaitTime", 180);
            
            	$this->RegisterVariableBoolean("Automatic", "Automatik", "~Switch", 10);
		$this->EnableAction("Automatic");
            	$this->RegisterVariableBoolean("State", "Status", "~Switch", 20);
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
		
		
		$this->RegisterEvent("Wochenplan", "IPS2Watering_Event_".$this->InstanceID, 2, $this->InstanceID, 30);
		// Anlegen der Daten für den Wochenplan
		for ($i = 0; $i <= 6; $i++) {
			IPS_SetEventScheduleGroup($this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID), $i, pow(2, $i));
		}
		
		IPS_SetEventScheduleAction($this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID), 0, "Freigabe", 0x40FF00, "IPS2Watering_SetState(\$_IPS['TARGET'], 1);");	
		IPS_SetEventScheduleAction($this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID), 0, "Sperrzeit", 0xFF0040, "IPS2Watering_SetState(\$_IPS['TARGET'], 1);");	

		
		If (GetValueBoolean($this->GetIDForIdent("Automatic")) == true) {
			$this->DisableAction("State");
		}
		else {
			$this->EnableAction("State");
		}
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SetStatus(102);
		}
		else {
			$this->SetStatus(104);
		}
        }
 
        
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "Automatic":
	            If ($this->ReadPropertyBoolean("Open") == true) {
		    	
		    }
	            break;
		switch($Ident) {
	        case "State":
	            If ($this->ReadPropertyBoolean("Open") == true) {
		    	
		    }
	            break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}    
	    
	public function SetState()
	{
		
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
