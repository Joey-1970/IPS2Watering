<?
    // Klassendefinition
    class IPS2WateringSplitter extends IPSModule {
 	
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterMessage(0, IPS_KERNELSTARTED);
		
		$this->RegisterPropertyBoolean("Open", false);
            	$this->RegisterPropertyInteger("TemperatureSensorID", 0);
		$this->RegisterPropertyInteger("MinTemperature", 10);
		
		$this->RegisterEvent("Wochenplan", "IPS2Watering_Event_".$this->InstanceID, 2, $this->InstanceID, 110);
		// Anlegen der Daten für den Wochenplan
		for ($i = 0; $i <= 6; $i++) {
			IPS_SetEventScheduleGroup($this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID), $i, pow(2, $i));
		}
		IPS_SetEventScheduleAction($this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID), 1, "Freigabe", 0x00FF00, "IPS2WateringSplitter_TimerEventGetWeekplanState(\$_IPS['TARGET']);");	
		IPS_SetEventScheduleAction($this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID), 2, "Sperrzeit", 0xFF0000, "IPS2WateringSplitter_TimerEventGetWeekplanState(\$_IPS['TARGET']);");	

		$this->RegisterTimer("WeekplanState", 0, 'IPS2WateringSplitter_TimerEventGetWeekplanState($_IPS["TARGET"]);'); 
		
		$this->RegisterTimer("WateringTimer", 0, 'IPS2WateringSplitter_WateringTimerEvent($_IPS["TARGET"]);'); 
		
		$this->RegisterTimer("WateringTimerSingle", 0, 'IPS2WateringSplitter_WateringTimerEventSingle($_IPS["TARGET"]);');
		
            	$this->RegisterProfileInteger("IPS2Watering.WeekplanState", "Information", "", "", 0, 2, 1);
		IPS_SetVariableProfileAssociation("IPS2Watering.WeekplanState", 0, "Undefiniert", "Warning", 0xFF0000);
		IPS_SetVariableProfileAssociation("IPS2Watering.WeekplanState", 1, "Freigabe", "LockOpen", 0x00FF00);
		IPS_SetVariableProfileAssociation("IPS2Watering.WeekplanState", 2, "Sperrzeit", "LockClosed", 0xFF0000);
		
		$this->RegisterProfileInteger("IPS2Watering.RadioButton_".$this->InstanceID, "Power", "", "", 0, 2, 0);
		IPS_SetVariableProfileAssociation("IPS2Watering.RadioButton_".$this->InstanceID, 0, "Aus", "Power", 0xFF0000);
		IPS_SetVariableProfileAssociation("IPS2Watering.RadioButton_".$this->InstanceID, 1, "Programm", "Power", 0x00FF00);
		IPS_SetVariableProfileAssociation("IPS2Watering.RadioButton_".$this->InstanceID, 3, "Entwässern", "Power", 0x00FF00);
		
		$this->RegisterVariableBoolean("Active", "Aktiv", "~Switch", 10);
		$this->EnableAction("Active");
		$this->RegisterVariableBoolean("Release", "Freigabe", "~Switch", 20);
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 30);
		$this->RegisterVariableFloat("MaxTemperature", "Max-Temperatur", "~Temperature", 40);
		$this->RegisterVariableFloat("MinTemperature", "Min-Temperatur", "~Temperature", 50);
		$this->RegisterVariableInteger("RadioButton", "Manuelle Auswahl", "IPS2Watering.RadioButton_".$this->InstanceID, 60);
		$this->EnableAction("RadioButton");
		$this->RegisterVariableBoolean("ProgramActive", "Programm aktiv", "~Switch", 70);
		$this->RegisterVariableString("ProgramStep", "Programm Schritt", "", 80);
		$this->RegisterVariableString("ProgramStepTime", "Programm Schritt Zeit", "", 90);
		$this->RegisterVariableInteger("WeekplanState", "Wochenplanstatus", "IPS2Watering.WeekplanState", 100);
		$this->RegisterVariableInteger("ActiveChildren", "Aktive Wasserkreise", "", 110);
		$this->RegisterVariableInteger("StepCounter", "Schrittzähler", "", 120);
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
            	$arrayElements[] = array("type" => "Label", "label" => "Temperatur-Sensor-Variable (Float, geloggt)");
            	$arrayElements[] = array("type" => "SelectVariable", "name" => "TemperatureSensorID", "caption" => "Aktor"); 
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
            	$arrayElements[] = array("type" => "Label", "label" => "Minimum-Temperatur");
            	$arrayElements[] = array("type" => "NumberSpinner", "name" => "MinTemperature",  "caption" => "Grad (C°)");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayActions = array(); 
		$arrayActions[] = array("type" => "Label", "label" => "Test Center"); 
		$arrayActions[] = array("type" => "TestCenter", "name" => "TestCenter");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 	
        }  
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Registrierung für die Änderung des Wochenplans
		$WeekplanID = $this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID);
		$this->RegisterMessage($WeekplanID, EM_ADDSCHEDULEGROUPPOINT);
		$this->RegisterMessage($WeekplanID, EM_REMOVESCHEDULEGROUPPOINT);
		$this->RegisterMessage($WeekplanID, EM_CHANGESCHEDULEGROUPPOINT);
		
		// Registrierung für die Änderung des Aktor-Status
		If ($this->ReadPropertyInteger("TemperatureSensorID") > 0) {
			$this->RegisterMessage($this->ReadPropertyInteger("TemperatureSensorID"), 10603);
		}
		
		SetValueInteger($this->GetIDForIdent("StepCounter"),  0);
		$this->SetBuffer("WateringProgramm", 0);
		$this->SetTimerInterval("WateringTimer", 0);
		$this->SetTimerInterval("WateringTimerSingle", 0);
		SetValueBoolean($this->GetIDForIdent("ProgramActive"), false);
		SetValueString($this->GetIDForIdent("ProgramStep"), "---");
		if (IPS_GetKernelRunlevel() == KR_READY) {
			$this->SendDataToChildren(json_encode(Array("DataID" => "{3AB3B462-743D-EA60-16E1-6EECEDD9BF16}", 
				"Function"=>"set_State", "InstanceID" => 0, "State"=>false)));
		}
	
		If (($this->ReadPropertyBoolean("Open") == true) AND (IPS_GetKernelRunlevel() == KR_READY)) {
			// Wochenplan auslesem
			$this->GetWeekplanState($WeekplanID);
			$this->SetTimerInterval("WeekplanState", (30 * 1000));
			// Assoziationen löschen und neu aufbauen
			$this->GetChildren();
			
			// Maximale Bewässerungszeit einlesen
			$this->GetChildrenMaxWatering();
			
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
		}
		else {
			$this->SetTimerInterval("WeekplanState", 0);
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
		}
        }
 
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
			case "Active":
			    	If ($this->ReadPropertyBoolean("Open") == true) {
				  	$this->SetValue($Ident, $Value);
			    	}
			    	break;
			case "RadioButton":
			    	If ($this->ReadPropertyBoolean("Open") == true) {
				  	$this->SetValue($Ident, $Value);
				  	// Zunächst erst einmal alles zurücksetzen
					$this->SetBuffer("WateringProgramm", 0);
					SetValueBoolean($this->GetIDForIdent("ProgramActive"), false);
					SetValueString($this->GetIDForIdent("ProgramStep"), "---");
					$this->SetTimerInterval("WateringTimer", 0);
					$this->SendDataToChildren(json_encode(Array("DataID" => "{3AB3B462-743D-EA60-16E1-6EECEDD9BF16}", 
											    "Function"=>"set_State", "InstanceID" => 0, "State"=>false)));
					
			    	  	If ($Value == 1) {
						// Programm
						$this->StartWateringProgram();
					}
					elseif ($Value == 2) {
						// Entwässerung
						$this->SendDataToChildren(json_encode(Array("DataID" => "{3AB3B462-743D-EA60-16E1-6EECEDD9BF16}", 
							"Function"=>"set_State", "InstanceID" => 0, "State"=>true)));

					}
					elseif (($Value >= 10000) AND ($Value < 100000)) {
						// bestimmte Instanz
						$this->SendDataToChildren(json_encode(Array("DataID" => "{3AB3B462-743D-EA60-16E1-6EECEDD9BF16}", 
											    "Function"=>"set_State", "InstanceID" =>$Value, "State"=>true)));
					}
					elseif ($Value >= 100000) {
						// bestimmte Instanz
						$MaxWateringArray = array();
						$MaxWateringArray = unserialize($this->GetBuffer("WateringArray"));
						$InstanceID = $Value - 100000;
						// aus dem Array die maximale Bewässerungszeit finden
						$Duration = $MaxWateringArray[$InstanceID];
						// Timer Setzen
						$this->SetTimerInterval("WateringTimerSingle", 1000 * 60 * $Duration);
						$this->SendDataToChildren(json_encode(Array("DataID" => "{3AB3B462-743D-EA60-16E1-6EECEDD9BF16}", 
											    "Function"=>"set_State", "InstanceID" => $InstanceID, "State"=>true)));
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
			case IPS_KERNELSTARTED:
				// IPS_KERNELSTARTED
				$this->ApplyChanges();
				break;
			case VM_UPDATE:
				// Änderung der Temperatur
				If ($SenderID == $this->ReadPropertyInteger("TemperatureSensorID")) {
					$this->SendDebug("MessageSink", "Ausloeser Aenderung Temperatur-Status", 0);
					$Temperature = GetValueFloat($SenderID);
					If (GetValueFloat($this->GetIDForIdent("Temperature")) <> $Temperature) {
						SetValueFloat($this->GetIDForIdent("Temperature"),  $Temperature);
					}
				}
				break;
			case EM_ADDSCHEDULEGROUPPOINT:
				// Änderung des Wochenplans
				$this->SendDebug("MessageSink", "Ausloeser Aenderung Wochenplan", 0);
				$WeekplanID = $this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID);
				$this->GetWeekplanState($WeekplanID);
				break;
			case EM_REMOVESCHEDULEGROUPPOINT:
				// Änderung des Wochenplans
				$this->SendDebug("MessageSink", "Ausloeser Aenderung Wochenplan", 0);
				$WeekplanID = $this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID);
				$this->GetWeekplanState($WeekplanID);
				break;
			case EM_CHANGESCHEDULEGROUPPOINT:
				// Änderung des Wochenplans
				$this->SendDebug("MessageSink", "Ausloeser Aenderung Wochenplan", 0);
				$WeekplanID = $this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID);
				$this->GetWeekplanState($WeekplanID);
				break;
		}
    	} 
	
	public function ForwardData($JSONString) 
	 {
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	$Result = -999;
	 	switch ($data->Function) {
		    	// Maximale Bewässerungszeit
			case "set_MaxWatering":
				$MaxWateringArray = array();
				$MaxWateringArray = unserialize($this->GetBuffer("WateringArray"));
				$MaxWateringArray[$data->InstanceID] = intval($data->MaxWatering);
				$this->SetBuffer("MaxWateringChilds", array_sum($MaxWateringArray));
				$this->SendDebug("MaxWateringChilds", array_sum($MaxWateringArray), 0);
				$this->SetBuffer("WateringArray", serialize($MaxWateringArray));
				$this->SendDebug("WateringArray", serialize($MaxWateringArray), 0);		 
				break;
			case "reset_Associations":
				$this->GetChildren();		 
				break;
		}
	return $Result;
	}
	    
	public function TimerEventGetWeekplanState()
	{  
		$WeekplanID = $this->GetIDForIdent("IPS2Watering_Event_".$this->InstanceID);
		$this->GetWeekplanState($WeekplanID);
	}
	    
	private function GetChildren()
	{
		$this->ClearProfilAssociations();
		$ChildArray = array();
	    	
		$InstanceIDs = IPS_GetInstanceList();
	    	foreach($InstanceIDs as $IID)
		{
		    	if ((IPS_GetInstance($IID)['ConnectionID'] == $this->InstanceID) AND (IPS_GetInstance($IID)['InstanceStatus'] == 102)) {
				$InstanceID = $IID.PHP_EOL;
				$ChildArray[] = intval($InstanceID);
				// Assozistion ohne Zeitlimit
				IPS_SetVariableProfileAssociation("IPS2Watering.RadioButton_".$this->InstanceID, $InstanceID, IPS_GetName($InstanceID), "Drops", -1);
				// Assozistion mit Zeitlimit
				IPS_SetVariableProfileAssociation("IPS2Watering.RadioButton_".$this->InstanceID, (100000 + intval($InstanceID)), IPS_GetName($InstanceID)." (Zeitlimit)", "Drops", -1);

				// Nachrichten abonnieren
				$this->RegisterMessage($InstanceID, 10505); // Statusänderung
				$this->RegisterMessage($InstanceID, 10506); // Einstellungen Veränderung
				$this->RegisterMessage($InstanceID, 10404); // Namesänderung
				$this->RegisterMessage($InstanceID, 10402); // Obejekt wurde entfernt
		    	}
		}
		SetValueInteger($this->GetIDForIdent("ActiveChildren"),  count($ChildArray));
		$this->SendDebug("ApplyChanges", serialize($ChildArray), 0);
		
	return  $ChildArray;
	}
	    
	private function GetChildrenMaxWatering()
	{
		$MaxWateringArray = array();
		$this->SetBuffer("MaxWateringArray", serialize($MaxWateringArray));
		$this->SendDataToChildren(json_encode(Array("DataID" => "{3AB3B462-743D-EA60-16E1-6EECEDD9BF16}", "Function"=>"get_MaxWatering")));
	}
	    
	private function GetWeekplanState(int $WeekplanID)
	{
		$this->SendDebug("GetWeekplanState", "Wochenplan Status einlesen", 0);
		$e = IPS_GetEvent($WeekplanID);
		$Starttime = 0;
		//$Endtime = 0;
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
						$Starttime = mktime (0 , 0 , 0, date("n") , date("j") , date("Y")) + $p['Start']['Hour'] * 3600 + $p['Start']['Minute'] * 60 + $p['Start']['Second'];
						$NextRun = IPS_GetEvent($WeekplanID)['NextRun'];
						
						$actionID = $p['ActionID'];
			   		} 
					else 
					{
			      			//$Endtime = $p['Start']['Hour'] * 3600 + $p['Start']['Minute'] * 60 + $p['Start']['Second'];
						break; //Sobald wir drüber sind, können wir abbrechen.
			   		}
		       		}
				break; //Sobald wir unseren Tag gefunden haben, können wir die Schleife abbrechen. Jeder Tag darf nur in genau einer Gruppe sein.
		    	}
		}
		
		
		
		If (intval($actionID) == 1) {
			$this->SetBuffer("MaxWateringTime", ($NextRun - $Starttime) / 60);
			$this->SendDebug("GetWeekplanState", "MaxWateringTime: ".(($NextRun - $Starttime) / 60)." Minuten", 0);
		}
		else {
			$this->SetBuffer("MaxWateringTime", 0);
			$this->SendDebug("GetWeekplanState", "MaxWateringTime: 0 Minuten", 0);
		}
		
		$this->SendDebug("GetWeekplanState", "Ergebnis: ".intval($actionID), 0);
		If (GetValueInteger($this->GetIDForIdent("WeekplanState")) <> intval($actionID)) {
			SetValueInteger($this->GetIDForIdent("WeekplanState"),  intval($actionID));
		}
	}    
	
	private function StartWateringProgram()
	{
		If (intval($this->GetBuffer("WateringProgramm") == 0)) {
			$this->GetChildrenMaxWatering();
			$this->SendDebug("StartWateringProgram", "Ausfuehrung", 0);
			$this->SetBuffer("WateringProgramm", 1);
			SetValueBoolean($this->GetIDForIdent("ProgramActive"), true);
			// Schrittzähler zurücksetzen
			SetValueInteger($this->GetIDForIdent("StepCounter"),  0);
			// alle Ventile schließen
			$this->SendDataToChildren(json_encode(Array("DataID" => "{3AB3B462-743D-EA60-16E1-6EECEDD9BF16}", 
								"Function"=>"set_State", "InstanceID" => 0, "State"=>false)));
			$this->WateringProgram();
		}
	}
	    
	private function WateringProgram()
	{
		$this->SendDebug("WateringProgram", "Ausfuehrung", 0);
		$MaxWateringArray = array();
		$MaxWateringArray = unserialize($this->GetBuffer("WateringArray"));
		
		$StepCounter = GetValueInteger($this->GetIDForIdent("StepCounter"));
		
		If ($StepCounter < count($MaxWateringArray)) {
			// Schlüssel (Instanz)
			$Instance = array_keys($MaxWateringArray)[$StepCounter];
			// Element (Dauer)
			$Duration = array_values($MaxWateringArray)[$StepCounter];
			If ($Duration > 0) {
				// Wasserkreis öffnen
				SetValueString($this->GetIDForIdent("ProgramStep"), IPS_GetName($Instance));
				$this->SendDataToChildren(json_encode(Array("DataID" => "{3AB3B462-743D-EA60-16E1-6EECEDD9BF16}", 
						"Function"=>"set_State", "InstanceID" =>$Instance, "State"=>true)));

				// Timer Setzen
				$this->SetTimerInterval("WateringTimer", 1000 * 60 * $Duration);
			}
			else {
				// Schrittzähler um einen hochsetzen
				SetValueInteger($this->GetIDForIdent("StepCounter"),  (GetValueInteger($this->GetIDForIdent("StepCounter")) + 1));
				$this->WateringProgram();
			}
		}
		else {
			$this->SetBuffer("WateringProgramm", 0);
			SetValueBoolean($this->GetIDForIdent("ProgramActive"), false);
			// Schrittzähler zurücksetzen
			SetValueInteger($this->GetIDForIdent("StepCounter"), 0);
			SetValueInteger($this->GetIDForIdent("RadioButton"), 0);
			SetValueString($this->GetIDForIdent("ProgramStep"), "---");
		}	
	}
	
	public function WateringTimerEvent()
	{
		$this->SendDebug("WateringTimerEvent", "Ausfuehrung", 0);
		$this->SetTimerInterval("WateringTimer", 0);
		// Schrittzähler um einen hochsetzen
		SetValueInteger($this->GetIDForIdent("StepCounter"),  (GetValueInteger($this->GetIDForIdent("StepCounter")) + 1));
		// alle Ventile schließen
		$this->SendDataToChildren(json_encode(Array("DataID" => "{3AB3B462-743D-EA60-16E1-6EECEDD9BF16}", 
							"Function"=>"set_State", "InstanceID" => 0, "State"=>false)));
		$this->WateringProgram();
	}
	
	public function WateringTimerEventSingle()
	{
		$this->SendDebug("WateringTimerEventSingle", "Ausfuehrung", 0);
		$this->SetTimerInterval("WateringTimerSingle", 0);
		// alle Ventile schließen
		$this->SendDataToChildren(json_encode(Array("DataID" => "{3AB3B462-743D-EA60-16E1-6EECEDD9BF16}", 
							"Function"=>"set_State", "InstanceID" => 0, "State"=>false)));
		SetValueInteger($this->GetIDForIdent("RadioButton"), 0);
	}
	    
	private function ClearProfilAssociations()
	{
		$ProfilArray = Array();
		$ProfilArray = IPS_GetVariableProfile("IPS2Watering.RadioButton_".$this->InstanceID);
		foreach ($ProfilArray["Associations"] as $Association)
		{
    			If (intval($Association["Value"]) >= 10000) {
				IPS_SetVariableProfileAssociation("IPS2Watering.RadioButton_".$this->InstanceID, intval($Association["Value"]), "", "", -1);
			}
		}
	}
	    
	private function PenmanMonteith ($temp,$tmax,$tmin,$relfeu,$luftdruck,$unixzeit,$breite,$n,$albedo,$zm,$v,$rsc,$LAI,$effWH) 
	{ 

	/** 

	/** Evapotranspiration nach Penman-Monteith ***************************************************** 
	/* 
	Aufrufbeispiel: 
	$a=PenmanMonteith(20,26,16,60,1025,time(),51,7,0.23,2.1,5,40,3.5,0.31); 
	Parameter:  


	$temp, Temperatur in °C 
	$tmax, max.Temp in °C 
	$tmin, min.Temp in °C 
	$relfeu, Rel.Luftfeuchte in % 
	$luftdruck, Luftdruck in hPa 
	$unixzeit, Zeitstempel des Tages, für den die Verdunstung berechnet werden soll 
	$breite, geogr. Breite in Grad 
	$n, tatsächliche Zahl der Sonnenstunden  
	$albedo, Albedo/Strahlungsreflexionsfaktor / 0.23 für grüne Flächen ? 
	$zm, Höhe der Windmessung in Metern 
	$v, Windgeschwindigkeit in km/h 
	$rsc, Stomatawiderstand der Vegetation (30-90, ausgetrockneter Böden bis bis 600) 
	$LAI, Blattflächenindex (LAI = Leaf Area Index, Blattfläche pro Bodeneinheit) 
	$effWH, effektive Wuchshöhe 

	Quelle der verwendeten Formel: 
	http://www.geogr.uni-jena.de/fileadmin/Geoinformatik/Lehre/SoSe_2010/GEO241/Folien/GEO241_S08_ABC_Modell_Erweiterung.pdf 
	http://www.fao.org/docrep/X0490E/x0490e07.htm 

	Beispielsangaben für Stomatawiderstand, LAI und effektive Wuchshöhe nach:   
	http://ilms.uni-jena.de/ilmswiki/de/index.php/Hydrologisches_Modell_J2000g 
	Jan Feb Mär Apr Mai Jun Jul Aug Sep Okt Nov Dez 
	LAI 2 2 2 2.1 3.3 4 4 4 3.4 2.1 2 2 
	rsc 80 80 70 60 40 45 45 45 50 60 80 80 
	Eff.WH 0.15 0.15 0.15 0.16 0.31 0.38 0.35 0.32 0.3 0.2 0.15 0.15 

	http://www.uni-kassel.de/fb14/geohydraulik/Lehre/Hydrologie_I/skript/IngHydro5.pdf 
	**/ 

	$J=1+date("z",$unixzeit); // Nr. des Tages in Jahr 
	$breite=deg2rad($breite); // Umrechnung der Breite von Grad in rad 
	$v=$v/3.6; // Umrechnung der Windgeschwindigkeit von km/h in m/s 
	if ($v==0) {$v=0.001;} // aerodynamische Rauhigkeit erzeugt sonst Fehler - Division durch Null 
	$luftdruck=$luftdruck/10; // Umrechnung hPa in kPa 
	$t=1; // Zeitschritt (?)  


	// mit L latente Verdunstungswärme L = 2.501- 0.002361·T [MJ/kg]
	$L=0.002361*$temp; 
	$L=2.501-$L; 

	// mit s Steigung der Sättigungsdampfdruckkurve 
	$s=(17.27*$temp)/(237.3+$temp); 
	$s=4098*0.6108*exp($s); 
	$s=$s/((237.3+$temp)*(237.3+$temp)); 

	// mit e_s Sättigungsdampfdruck 
	$es=(17.27*$temp)/(237.3+$temp); 
	$es=6.108*exp($es); 

	// mit e aktueller Dampfdruck [kPa]
	$e=$es*($relfeu/100); 

	// mit gamma Psychrometerkonstante [kPa / °C]
	$y=0.001013*$luftdruck/(0.622*$L); 

	// d_r relative Distanz Erde-Sonne[rad]
	$dr=2*$J*pi()/365; 
	$dr=1+0.033*cos($dr); 

	// dekli Deklination der Sonne[rad] 
	$dekli=2*$J*pi()/365; 
	$dekli=$dekli-1.39; 
	$dekli=0.409*sin($dekli); 

	// stuwi Stundenwinkel bei Sonnenuntergang[rad]
	$stuwi=-tan($breite) * tan($dekli); 
	$stuwi=acos($stuwi); 

	// R_a= Extraterrestrische Strahlung [MJ / m² d] 
	$Ra=cos($breite)*cos($dekli)*sin($stuwi); 
	$Ra=($stuwi*sin($breite)*sin($dekli))+$Ra; 
	$Ra=$Ra*24*60/pi(); 
	$Ra=$Ra*0.0820*$dr; // Solarkonstante 

	// N astronomisch mögliche Sonnenscheindauer[h]
	$N=$stuwi*24/pi(); 

	// R_s tatsächliche Globalstrahlunh [MJ / m² d] 
	$Rs=0.25+(0.5*$n/$N); 
	$Rs=$Rs*$Ra; 

	// R_so Globalstrahlung bei unbedecktem Himmel Clear Sky Radiation [MJ / m² d] 
	$rso=$Ra*(0.25+0.5); 

	// R_ns kurzwellige Nettostrahlung [MJ / m² d]
	$rns=(1-$albedo)*$Rs; 

	// R_nl Langwellige Nettostrahlung 
	$tmax=273.16+$tmax; 
	$tmax=$tmax*$tmax*$tmax*$tmax; 
	$tmin=273.16+$tmin; 
	$tmin=$tmin*$tmin*$tmin*$tmin; 
	$rnl=($tmax+$tmin)/2; 
	$rnl=0.000000004903*$rnl*(0.34-0.14*sqrt($e)); // *10-9; // Stefan Boltzmann Konstante 
	$rnl=$rnl*(1.35*($Rs/$rso) -0.35); 

	// R_n Nettostrahlung/Strahlungsbilanz [MJ / m² d] 
	$Rn=$rns-$rnl; 

	// G Bodenwärmestrom 
	$G=0.2*$rns; 

	//p Dichte der Luft bei konstantem Druck 
	$p=$luftdruck/(1.01*($temp+273)*0.287); 

	//r_s Oberflächenwiderstand der Bodenbececkung s/m 
	$rss=150; // Oberflächenwiderstand von unbewachsenem Boden 
	$LAI=pow(0.7,$LAI); 
	$rs=((1-$LAI)/$rsc)+($LAI/$rss); 
	$rs=1/$rs; 

	// r_a Aerodynamische Rauhigkeit
	$ra=($zm-2*$effWH/3); 
	$ra=log($ra/(0.123*$effWH))*log($ra/(0.0123*$effWH)); 
	$ra=$ra/(0.41*0.41*$v); 

	// ETP Verdunstung nach Penman-Monteith
	$etp=$t*($es-$e)/$ra; 
	$etp=$etp*0.001013*$p; 
	$etp=$s*($Rn - $G)+$etp; 
	$nenner=$y*(1+$rs/$ra); 
	$nenner=$s+$nenner; 
	$etp=$etp/$nenner; 
	$etp=$etp*1/$L; 

	// return $etp; 
	return number_format($etp,3); //." mm/d"; //etp; 
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
