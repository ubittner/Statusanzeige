# Statusanzeige  

Zur Verwendung dieses Moduls als Privatperson, Einrichter oder Integrator wenden Sie sich bitte zunächst an den Autor.  

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.  


### Inhaltsverzeichnis

1. [Modulbeschreibung](#1-modulbeschreibung)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Schaubild](#3-schaubild)
4. [Auslöser](#4-auslöser)
5. [Externe Aktion](#5-externe-aktion)
6. [PHP-Befehlsreferenz](#6-php-befehlsreferenz)
   1. [Signalisierung auslösen](#61-signalisierung-auslösen)

### 1. Modulbeschreibung

Dieses Modul schaltet eine Variable als Statusanzeige in [IP-Symcon](https://www.symcon.de).

### 2. Voraussetzungen

- IP-Symcon ab Version 6.1

Sollten mehrere Variablen geschaltet werden, so sollte zusätzlich das Modul Ablaufsteuerung genutzt werden.

### 3. Schaubild

```
                       +-----------------------+
                       | Statusanzeige (Modul) |
                       |                       |
Auslöser <-------------+ Statusanzeige         |<------------- externe Aktion
                       +-----------+--+--------+
                                   |  |
                                   |  |
                                   |  |    +---------------------------+
                                   |  +--->|  Ablaufsteuerung (Modul)  |
                                   |       +--------------+------------+
                                   |                      |
                                   |                      |
                                   v                      |
                       +----------------------+           |
                       |  Statusanzeige (HW)  |<----------+
                       +----------------------+
```

### 4. Auslöser

Das Modul Statusanzeige reagiert auf verschiedene Auslöser.  

### 5. Externe Aktion

Das Modul Statusanzeige kann über eine externe Aktion geschaltet werden.  
Nachfolgendes Beispiel schaltet die Statusanzeige an.

```php
SA_ToggleSignalling(12345, true, false);
```
### 6. PHP-Befehlsreferenz

#### 6.1 Signalisierung auslösen

```text
boolean SA_ToggleSignalling(integer INSTANCE_ID, bool STATE, bool FORCE_SIGNALING);
```

Konnte der Befehl erfolgreich ausgeführt werden, liefert er als Ergebnis **TRUE**, andernfalls **FALSE**.

| Parameter           | Wert       | Bezeichnung    | Beschreibung              |
|---------------------|------------|----------------|---------------------------|
| `INSTANCE_ID`       |            | ID der Instanz |                           |
|                     |            |                |                           |
| `STATE`             |            | Status         |                           |
|                     | false      | Aus            | Statusanzeige Aus         |
|                     | true       | An             | Statusanzeige An          |
|                     |            |                |                           |
| `FORCE_SIGNALING`   |            |                |                           |
|                     | false      | Aus            | Nur bei Änderung schalten |
|                     | true       | An             | Schalten erzwingen        |

**Beispiel**:
```php
SA_ToggleSignalling(12345, false, true);
```

---