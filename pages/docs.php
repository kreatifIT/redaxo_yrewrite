<?php

/**
 * YREWRITE Addon.
 *
 * @author alexplusde
 *
 * @package redaxo\yrewrite
 *
 * @var rex_addon $this
 */


$content = '
## Anwendungsfall

Dieses Addon bietet eine Möglichkeit, Redaxo mit mehreren Domains zu betreiben. Mehrere Domains können dann sinnvoll sein, wenn

* mehrere Websites eines Kunden in einer Installation verwaltet werden,
* verschiedene Sprachen (`clang`) einer Website unter unterschiedlichen Domains oder Subdomains erreichbar sind,
* oder beides.

> Tipp: Wir empfehlen im ersten Fall, für jede einzelne Domain in der Struktur auf der obersten Ebene eine Kategorie anzulegen.

## Erste Schritte

### Installation

Voraussetzung für die aktuelle Version von YRewrite: REDAXO 5.5

Beim installieren und aktivieren des Addons wird die `.htaccess`-Datei von Redaxo aktualisiert. Auch eine virtuelle `robots.txt` und `sitemap.xml` werden erstellt.

Anschließend können ein oder mehrere Domains zu YRewrite hinzugefügt werden.

### Domain hinzufügen

1. In "YRewrite" unter "Domains" Auf das +-Zeichen klicken.
2. Domain eintragen, bspw. `https://www.meine-domain.de/`.
3. Mount-Artikel auswählen. Das ist der der Startartikel einer Kategorie, in der sich YRewrite einklinken soll. Alle Artikel unterhalb des Mount-Artikels sind dann über die Domain aufrufbar.
4. Startseiten-Artikel auswählen. Das kann der Mount-Artikel sein oder eine separate Artikelseite. Diese wird als Startseite der Domain aufgerufen.
5. Fehlerseiten-Artikel auswählen. Das ist der Artikel, der mit einem 404-Fehlercode ausgegeben wird, z.B., wenn eine Seite nicht gefunden werden kann oder ein Tippfehler in der Adresse vorliegt.
6. Spracheinstellungen: Hier können Sprachen ausgewählt werden, die mit der Domain verknüpft werden. So lassen sich bspw. unterschiedliche Domains pro Sprache umsetzen.
7. Titelschema eintragen, bspw. `%T - Meine Domain`. Dieses Titelschema kann dann im Website-Template ausgegeben werden.
8. robots.txt-Einstellungen hinzufügen. Siehe Tipp unten.
9. Domain hinzufügen.

Diese Vorgehensweise für alle gewünschten Domains wiederholen.

> Tipp: Um die Installation während der Entwicklung zuverlässig gegen ein Crawling von Bots und Suchmaschinen zu schützen, genügt die `robots.txt` nicht. Dazu gibt es das `maintanance`-Addon von https://friendsofredaxo.github.io

> Tipp: Die Domain auch in der Google Search Console hinterlegen und die `sitemap.xml` dort hinzufügen, um das Crawling zu beschleunigen. Die Domain sollte in allen vier Variationen hinterlegt werden, also mit/ohne `https` und mit/ohne `www.`. Die `sitemap.xml` jedoch nur in der Hauptdomain, am besten mit `https://` und `www.`

> Hinweis: Domains mit Umlauten bitte derzeit decodiert eintragen. Umwandlung bspw. mit https://www.punycoder.com

### Alias-Domain hinzufügen

Alias-Domains werden nur dann benötigt, wenn mehrere Domains auf den selben Ordner im Server zeigen, aber keine separaten Websites aufrufen. z.B. `www.meinedomain.de` und `www.meine-domain.de`.

Alias-Domains müssen nicht eingetragen werden, wenn die Domain nicht auf das Serververzeichnis zeigt. Einige Hoster bieten bspw. von sich aus die Möglichkeit, per Redirect von `www.meinedomain.de` auf `www.meine-domain.de` weiterzuleiten. Dann wird die Einstellung nicht benötigt.

1. In "YRewrite" unter "Domains" Auf das +-Zeichen klicken
2. Alias-Domain eintragen, bspw. `https://www.meine-domain.de/`
3. Ziel-Domain aus YRewrite auswählen
4. Alias-Domain hinzufügen

### Weiterleitungen

Unter Weiterleitungen können URLs definiert werden, die dann auf einen bestimmten Artikel oder eine andere Adresse umgeleitet werden.

> **Hinweis:** Mit dieser Einstellung können nicht bereits vorhandene Artikel / URLs umgeleitet werden, sondern nur URLs, die in der REDAXO-Installation nicht vorhanden sind. Das ist bspw. bei einem Relaunch der Fall, wenn alte URLs auf eine neue Zielseite umgeleitet werden sollen.

### Setup

Unter `Setup` kann die `.htaccess`-Datei neu überschrieben werden, die für die Verwendung von YRewrite benötigt wird. Außerdem sind die `sitemap.xml` und `robots.txt` je Domain einsehbar.


## Klassen-Referenz

### YRewrite-Objekt
Siehe auch: https://github.com/yakamara/redaxo_yrewrite/blob/master/lib/yrewrite.php

```
$yrewrite = new rex_yrewrite;
# dump($yrewrite); // optional alle Eigenschaften und Methoden anzeigen
```

**Methoden**

```
```

### YRewrite-Domain-Objekt

Siehe auch: https://github.com/yakamara/redaxo_yrewrite/blob/master/lib/domain.php

```
$domain = rex_yrewrite::getCurrentDomain();
dump($domain); // optional alle Eigenschaften und Methoden anzeigen
```

**Methoden**
```
init()
getScheme()
setScheme(rex_yrewrite_scheme $scheme)
addDomain(rex_yrewrite_domain $domain)
addAliasDomain($from_domain, $to_domain_id, $clang_start = 0)
getDomains()
getDomainByName($name)
getDomainById($id)
getDefaultDomain()
getCurrentDomain()
getFullUrlByArticleId($id, $clang = null, array $parameters = [], $separator = \'&amp;\')
getDomainByArticleId($aid, $clang = null)
getArticleIdByUrl($domain, $url)
isDomainStartArticle($aid, $clang = null)
isDomainMountpoint($aid, $clang = null)
getPathsByDomain($domain)
prepare()
rewrite($params = [], $yparams = [], $fullpath = false)
generatePathFile($params)
checkUrl($url)
generateConfig()
readConfig()
readPathFile()
copyHtaccess()
isHttps()
deleteCache()
getFullPath($link = \'\')
getHost()
```
### YRewrite-SEO-Objekt

Siehe auch: https://github.com/yakamara/redaxo_yrewrite/blob/master/lib/seo.php


```
$seo = new rex_yrewrite_seo();
dump($seo); // optional alle Eigenschaften und Methoden anzeigen
```

**Methoden**

```
```

## Beispiele

### ID der aktuellen Domain in YRewrite

```
rex_yrewrite::getCurrentDomain()->getId();

```

Beispiel-Rückgabewert: `1`

### Mount-ID der Domain

```
rex_yrewrite::getCurrentDomain()->getMountId();
```

Beispiel-Rückgabewert: `5`


### Startartikel-ID der Domain

```
rex_yrewrite::getCurrentDomain()->getStartArticleId();
```

Beispiel-Rückgabewert: `42`

### Fehler-Artikel-ID der Domain

```
rex_yrewrite::getCurrentDomain()->getNotfoundId();
```

Beispiel-Rückgabewert: `43`

### Name der aktuellen Domain

```
rex_yrewrite::getCurrentDomain()->getName();
```

Beispiel-Rückgabewert: `meine-domain.de`

### vollständige URL eines Artikels

```
rex_yrewrite::getFullUrlByArticleId(42);
```

Beispiel-Rückgabewert: `https://www.meine-domain.de/meine-kategorie/mein-artikel.html`

### Zu welcher Domain gehört der aktuelle Artikel?

```
rex_yrewrite::getDomainByArticleId(REX_ARTICLE_ID)->getName();
```

Beispiel-Rückgabewert: `meine-domain.de`

### Meta-Tags auslesen (`description`, `title`, usw.)

Diesen Codeabschnitt in den `<head>`-Bereich des Templates kopieren:

```
$seo = new rex_yrewrite_seo();
echo $seo->getTitleTag();
echo $seo->getDescriptionTag();
echo $seo->getRobotsTag();
echo $seo->getHreflangTags();
echo $seo->getCanonicalUrlTag();
```

### Navigation Factory in Abhängigkeit der gewählten Domain

Weitere Informaionen zur Navigation Factory des REDAXO-Cores in der API-Dokumentation unter https://redaxo.org/api/master/ und bei den Tricks von FriendsOfREDAXO: https://github.com/friendsofredaxo/tricks/

```
$nav = rex_navigation::factory();
echo $nav->get(rex_yrewrite::getCurrentDomain()->getMountId(), 1, TRUE, TRUE);
```

### Übersicht aller Domains ausgeben

```
$domains = array_filter(rex_sql::factory()->setDebug(0)->query(\'SELECT * FROM rex_yrewrite_domain\')
foreach($domains as $domain) {
    dump($domain);
}
```

# Weitere Informationen, Hilfe und Bugmeldungen

* Auf Github: https://github.com/yakamara/redaxo_yrewrite/issues/
* im Forum: https://www.redaxo.org/forum/
* im Slack-Channel: https://friendsofredaxo.slack.com/';


$miu = rex_markdown::factory();
$content = $miu->parse($content);

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('docs'));
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');