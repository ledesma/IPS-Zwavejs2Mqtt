# Zwavejs2Mqtt Device
   Bildet ein [Zwavejs2Mqtt](https://zwave-js.github.io/zwavejs2mqtt) Gerät ab.
   (Zur Zeit nur Switch und Dimmer)
     
   ## 1. Konfiguration
   
   Feld | Beschreibung
   ------------ | -------------
   MQTT Topic   | Hier wird das Topic vom Device eingetragen (der prefix ```zwave/``` wird implizit davorgestellt).
   Assoziation  | Hier kann man andere _Zwavejs2Mqtt Device_ instanzen eintragen, diese werden dann mitgeschaltet.

   ## 2. Funktionen
   
   **ZQ2M_SwitchMode($InstanceID, $Value)**\
   Mit dieser Funktion ist es möglich das Gerät ein- bzw. auszuschalten.
   ```php
   Z2M_SwitchMode(25537, true) //Einschalten;
   Z2M_SwitchMode(25537, false) //Ausschalten;
   ```
   
   **ZQ2M_DimSet($InstanceID, $Value)**\
   Mit dieser Funktion ist es möglich das Gerät zu dimmen.
   ```php
   ZQ2M_DimSet(25537,50) //auf 50% dimmen;
   ```
