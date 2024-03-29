<?
    // Klassendefinition
    class IPS2WateringSwitch extends IPSModule {
 	
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterMessage(0, IPS_KERNELSTARTED);
		
		$this->ConnectParent("{A229AF9F-E57D-A522-D69A-AFB01BB8109D}");
		$this->RegisterPropertyBoolean("Open", false);
            	$this->RegisterPropertyInteger("ActuatorID", 0);
            	$this->RegisterPropertyInteger("SensorID", 0);
		
		$this->RegisterProfileInteger("IPS2Watering.MaxWatering", "Clock", "", "", 0, 60, 1);
		
		$this->RegisterVariableBoolean("Automatic", "Automatik", "~Switch", 10);
		$this->EnableAction("Automatic");
            	$this->RegisterVariableBoolean("State", "Status", "~Switch", 20);
		$this->EnableAction("State");
		$this->RegisterVariableInteger("MaxWatering", "Maximale Bewässerungszeit (min)", "IPS2Watering.MaxWatering", 30);
		$this->EnableAction("MaxWatering");
		
		
        }
	    
	public function GetConfigurationForm() 
        { 
            	$arrayStatus = array(); 
            	$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
            	$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
            	$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");

            	$arrayElements = array(); 
            	$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
            	$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________");
            	$arrayElements[] = array("type" => "Label", "caption" => "Ventil-Aktor-Variable (Boolean)");
            	$arrayElements[] = array("type" => "SelectVariable", "name" => "ActuatorID", "caption" => "Aktor"); 
            	$arrayElements[] = array("type" => "Label", "caption" => "Bodenfeuchtigkeits-Sensor-Variable (Float)");
            	$arrayElements[] = array("type" => "SelectVariable", "name" => "SensorID", "caption" => "Aktor"); 
            	$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________");
		$arrayActions = array(); 
		$arrayActions[] = array("type" => "Label", "caption" => "Test Center"); 
		$arrayActions[] = array("type" => "TestCenter", "name" => "TestCenter");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 	 	 
        }  
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		// Registrierung für die Änderung des Aktor-Status
		If ($this->ReadPropertyInteger("ActuatorID") > 0) {
			$this->RegisterMessage($this->ReadPropertyInteger("ActuatorID"), VM_UPDATE);
		}
			
		If (GetValueBoolean($this->GetIDForIdent("Automatic")) == true) {
			$this->DisableAction("State");
		}
		else {
			$this->EnableAction("State");
		}
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$MaxWatering =  GetValueInteger($this->GetIDForIdent("MaxWatering"));
			if (IPS_GetKernelRunlevel() == KR_READY) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{86AFC5C5-7881-11BF-A513-46C91C174E10}", 
										  "Function" => "set_MaxWatering", "InstanceID" => $this->InstanceID, "MaxWatering" => $MaxWatering )));

				$this->SendDataToParent(json_encode(Array("DataID"=> "{86AFC5C5-7881-11BF-A513-46C91C174E10}", 
										  "Function" => "reset_Associations", "InstanceID" => $this->InstanceID )));
			}
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
		}
		else {
			$MaxWatering = 0;
			if (IPS_GetKernelRunlevel() == KR_READY) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{86AFC5C5-7881-11BF-A513-46C91C174E10}", 
										  "Function" => "set_MaxWatering", "InstanceID" => $this->InstanceID, "MaxWatering" => $MaxWatering )));
			}
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
		}
        }
 
        
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
			case "Automatic":
			    If ($this->ReadPropertyBoolean("Open") == true) {
				   SetValueBoolean($this->GetIDForIdent("Automatic"),  $Value);
				   If ($Value == true) {
				   	   $this->SendDebug("RequestAction", "State wird Disabled", 0);
					   $this->DisableAction("State");
				   }
				   else {
					   $this->SendDebug("RequestAction", "State wird Enabled", 0);
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
			case "MaxWatering":
			    If ($this->ReadPropertyBoolean("Open") == true) {
				    SetValueInteger($this->GetIDForIdent("MaxWatering"),  $Value);
				    $this->SendDataToParent(json_encode(Array("DataID"=> "{86AFC5C5-7881-11BF-A513-46C91C174E10}", 
										  "Function" => "set_MaxWatering", "InstanceID" => $this->InstanceID, "MaxWatering" => $Value )));
			    }
			    break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}    
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case VM_UPDATE:
				// Änderung der Vorlauf-Temperatur
				If ($SenderID == $this->ReadPropertyInteger("ActuatorID")) {
					$this->SendDebug("MessageSink", "Ausloeser Aenderung Aktor-Status", 0);
					
				}
				break;
			case IPS_KERNELSTARTED:
				// IPS_KERNELSTARTED
				$this->ApplyChanges();
				break;
			
		}
    	}        
	    
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "get_MaxWatering":
			   	If (($this->ReadPropertyBoolean("Open") == true) AND (GetValueBoolean($this->GetIDForIdent("Automatic")) == true)) {
					$MaxWatering =  GetValueInteger($this->GetIDForIdent("MaxWatering"));						
				}
				else {
					$MaxWatering = 0;
				}
				$this->SendDataToParent(json_encode(Array("DataID"=> "{86AFC5C5-7881-11BF-A513-46C91C174E10}", 
										  "Function" => "set_MaxWatering", "InstanceID" => $this->InstanceID, "MaxWatering" => $MaxWatering )));
				break;
			case "set_State":
				If ($this->ReadPropertyInteger("ActuatorID") > 0) { // Normale Aktion oder Programm
					If ($data->InstanceID == $this->InstanceID) {
						SetValueBoolean($this->GetIDForIdent("State"),  boolval($data->State));
						RequestAction($this->ReadPropertyInteger("ActuatorID"), boolval($data->State));
					}
					elseif ($data->InstanceID == 0) { // Entwässerung bzw. alles aus
						SetValueBoolean($this->GetIDForIdent("State"),  boolval($data->State));
						RequestAction($this->ReadPropertyInteger("ActuatorID"), boolval($data->State));
					}
					else { // Schließen wenn andere angesprochen werden
						SetValueBoolean($this->GetIDForIdent("State"), false);
						RequestAction($this->ReadPropertyInteger("ActuatorID"), false);
					}
				}
				break;
			
			
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
