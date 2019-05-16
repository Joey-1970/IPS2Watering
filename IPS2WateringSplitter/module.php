<?
    // Klassendefinition
    class IPS2WateringSplitter extends IPSModule {
 	    
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
            	
		
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
            	
		
		$arrayActions = array();
            	$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
            	return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
        }  
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
	
		If ($this->ReadPropertyBoolean("Open") == true) {
			
			$this->SetStatus(102);
		}
		else {
			
			$this->SetStatus(104);
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
	return number_format($etp,3) //." mm/d"; //etp; 
	}     

}
?>