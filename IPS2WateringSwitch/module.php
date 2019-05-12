<?
    // Klassendefinition
    class IPS2WateringSwitch extends IPSModule {
 
       
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();
            $this->RegisterPropertyBoolean("Open", false);
            $this->RegisterPropertyInteger("ActuatorID", 0);
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
        }
 
        
    }
?>
