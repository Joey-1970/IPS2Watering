<?
    // Klassendefinition
    class IPS2WateringSwitch extends IPSModule {
 	
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->ConnectParent("{A229AF9F-E57D-A522-D69A-AFB01BB8109D}");
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
		
		// Registrierung für die Änderung des Aktor-Status
		If ($this->ReadPropertyInteger("ActuatorID") > 0) {
			$this->RegisterMessage($this->ReadPropertyInteger("ActuatorID"), 10603);
		}
		
		
		
		
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
			
		}
    	}        
	    
	public function SetState()
	{
		$this->SendDebug("SetState", "Ausloesung", 0);
		$WeekplanID = $this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID);
		$this->GetWeekplanState($WeekplanID);
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
  
	
}
?>
