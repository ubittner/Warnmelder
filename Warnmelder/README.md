# Warnmelder

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
5. [PHP-Befehlsreferenz](#5-php-befehlsreferenz)
   1. [Status aktualisieren](#51-status-aktualisieren)

### 1. Modulbeschreibung

Dieses Modul überwacht Rauch-, Wasser- und sonstige Gefahrenmelder.

Das Modul ermittelt immer den aktuellen Status.  
Benachrichtigungen werden nur ausgelöst, wenn das Modul aktiv ist.

### 2. Voraussetzungen

- IP-Symcon ab Version 6.1

### 3. Schaubild

```
                      +-----------------------+
                      | Warnmelder (Modul)    |
                      |                       |
Auslöser------------->+ Status                |
                      +-----------------------+
```

### 4. Auslöser

Das Modul Warnmelder reagiert auf verschiedene Auslöser.

### 5. PHP-Befehlsreferenz

#### 5.1 Status aktualisieren

```text
WM_UpdateStatus(integer INSTANCE_ID);
```

Konnte der jeweilige Befehl erfolgreich ausgeführt werden, liefert er als Ergebnis:
**TRUE** für einen Alarm, andernfalls **FALSE**

| Parameter     | Beschreibung   | 
|---------------|----------------|
| `INSTANCE_ID` | ID der Instanz |


**Beispiel:**
```php
WM_UpdateStatus(12345);
```
