=== Integration for OTP eBiz & WooCommerce ===
Contributors: passatgt
Tags: otp, ebiz, woocommerce, szamlazo, magyar
Requires at least: 5.0
Tested up to: 5.3.2
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

OTP eBiz összeköttetés WooCommerce-hez.

== Description ==

> **PRO verzió**
> A bővítménynek elérhető a PRO verziója 10.000 Ft-ért, amelyet itt vásárolhatsz meg: [https://szamlazz.visztpeter.me/ebiz](https://szamlazz.visztpeter.me/ebiz)
> A licensz kulcs egy weboldalon aktiválható és 1 év emailes support is jár hozzá beállításhoz, testreszabáshoz, konfiguráláshoz.
> A vásárlással támogathatod a fejlesztést akkor is, ha esetleg a PRO verzióban elérhető funkciókra nincs is szükséged.

= Funkciók =

* **Manuális számlakészítés**
Minden rendelésnél a jobb oldalon megjelenik egy új gomb, rákattintáskor elküldi az adatokat az OTP eBiz-nek és legenerálja a számlát.
* **Automata számlakészítés** _PRO_
Lehetőség van a számlát automatikusan elkészíteni bizonyos fizetési módoknál, vagy ha a rendelés teljesítve lett. Automatikusan létrehozhatsz díjbekérőt például átutalásos fizetési módhoz és beállíthatod, hogy a rendelés lezárásakor jelölje meg teljesítettnek a számlát.
* **Mennyiségi egység**
A tételek mellett a mennyiségi egységet is feltüntetni a számlát, amelyet a beállításokban minden termékhez külön-külön meg tudod adni és megjegyzést is tudsz megadni a tételhez
* **Számlaértesítő**
Alapértelmezett beállításokkal az eBiz küldi ki a számlaértesítőt a vásárlónak. Lehet a számlákat csatolni a WooCommerce által küldött emailekhez, így nem fontos használni az eBiz számlaértesítőjét és a vásárlód egyel kevesebb emailt fog kapni
* **Nemzetközi számla**
Ha külföldre értékesítesz például euróban, lehetőség van a számla nyelv átállítására és az aktuális MNB árfolyam feltüntetésére a számlán. Kompatibilis WPML-el és Polylang-al is.
* **Díjbekérő készítés**
Ha a rendelés állapota átállítódik függőben lévőre, automatán legenerálja a díjbekérő számlát. Lehet kézzel egy-egy rendeléshez külön díjbekérőt is csinálni.
* **Naplózás**
Minden számlakészítésnél létrehoz egy megjegyzést a rendeléshoz, hogy mikor, milyen néven készült el a számla
* **Sztornózás**
A számla sztornózható a rendelés oldalon, vagy kikapcsolható 1-1 rendeléshez
* **Adószám mező**
A WooCommerce-ben alapértelmezetten nincs adószám mező. Ezzel az opcióval bekapcsolható, hogy a számlázási adatok között megjelenjen. Az adószámot a rendszer eltárolja, a vásárlónak küldött emailben és a rendelés adatai között is megjelenik. Lehetőség van arra, hogy csak 100.000 Ft áfatartalom felett látszódjon.
* **És még sok más**
Papír és elektronikus számla állítás, Áfakulcs állítás, Számlasorszám formátum módosítása, Letölthető számlák a vásárló profiljában, Hibás számlakészítésről e-mailes értesítő stb...

= Fontos kiemelni =
* A generált számlát letölti saját weboldalra is és a wp-content/uploads/wc_ebiz mappában tárolja
* Fizetési határidő és megjegyzés írható a számlákhoz
* Kuponokkal is működik
* Szállítást is ráírja a számlára
* A PDF fájl letölthető egyből a Rendelések oldalról is(táblázat utolsó oszlopa)

= Használat =
Részletes dokumentációt [itt](https://szamlazz.visztpeter.me/dokumentacio/ebiz) találsz.
Telepítés után a WooCommerce / Beállítások oldalon meg kell adni az eBiz technikai felhasználónevet és jelszót, API kulcsot és relációs azonosítót(ezeket mind az eBIz support-tól lehet kérni) illetve az ott található többi beállításokat igény szerint.
Minden rendelésnél jobb oldalon megjelenik egy új doboz, ahol egy gombnyomással létre lehet hozni a számlát. Az Opciók gombbal felül lehet írni a beállításokban megadott értékeket 1-1 számlához.
Ha az automata számlakészítés be van kapcsolva, akkor a rendelés lezárásakor(Teljesített rendelés státuszra állítás, vagy bármilyen egyéb státusz beállítható) automatikusan létrehozza a számlát a rendszer.
A számlakészítés kikapcsolható 1-1 rendelésnél az Opciók legördülőn belül.
Az elkészült számla a rendelés aloldalán és a rendelés listában az utolsó oszlopban található PDF ikonra kattintva letölthető.

**FONTOS:** Mindenen esetben ellenőrizd le, hogy a számlakészítés megfelelő e és konzultálj a könyvelőddel, neki is megfelelnek e a generált számlák. Sajnos minden esetet nem tudok tesztelni, különböző áfakulcsok, termékvariációk, kuponok stb..., így mindenképp teszteld le éles használat előtt és ha valami gond van, jelezd felém és megpróbálom javítani.

= Fejlesztőknek =

A plugin egy XML fájlt generál, ezt küldi el az eBiz-nek, majd az egy pdf-ben visszaküldi az elkészített számlát. Az XML fájl generálás előtt módosítható a `wc_ebiz_xml` filterrel. Ez minden esetben az éppen aktív téma functions.php fájlban történjen, hogy az esetleges plugin frissítés ne törölje ki a módosításokat!

    <?php
    add_filter('wc_ebiz_xml', 'szamla_modositasa',10,2);
    function szamla_modositasa($xml, $order) {
      //...
      return $xml;
    }
    ?>

Lehetőség van sikeres és sikertelen számlakészítés után egyedi funckiók meghívására a bővítmény módosítása nélkül:

   <?php
   add_action('wc_ebiz_after_invoice_success', 'sikeres_szamlakeszites',10,3);
   function sikeres_szamlakeszites($order, $response, $pdf_url) {
     //...
   }

   add_action('wc_ebiz_after_invoice_error', 'sikertelen_szamlakeszites',10,3);
   function sikertelen_szamlakeszites($order, $response, $error_object) {
     //...
   }
   ?>

= GDPR =

A bővítmény HTTP hívásokkal kommunikál az OTP eBiz [API rendszerével](http://dokuwikipub.otpebiz.hu). Az API hívások akkor futnak le, ha számla készül(pl rendelés létrehozásánál automatikus számlázás esetén, vagy manuális számlakészítéskor a Számlakészítés gombra nyomva).
Az OTP eBiz egy külső szolgáltatás, saját [adatvédelmi nyilatkozattal](https://www.otpebiz.hu/altalanos-adatkezelesi-szabalyzat) és [felhasználási feltételekkel](https://www.otpebiz.hu/altalanos-szerzodesi-feltetelek/).
This extension relies on making HTTP requests to the OTP [eBiz API](http://dokuwikipub.otpebiz.hu). API calls are made when an invoice is generated(for example on order creation in case of automatic invoicing, or when you press the create invoice button manually).
[Identibyte API](https://identibyte.com).
OTP eBiz is an external service and has it's own [Terms of Service](https://www.otpebiz.hu/altalanos-szerzodesi-feltetelek) and [Privacy Policy](https://www.otpebiz.hu/altalanos-adatkezelesi-szabalyzat), which you can review at those links.

== Installation ==

1. Töltsd le a bővítményt
2. Wordpress-ben bővítmények / új hozzáadása menüben fel kell tölteni
3. WooCommerce / Integráció menüpontban találhatod ez eBiz beállításokat
4. Működik

== Frequently Asked Questions ==

= Nem akar működni a számlakészítés =

A WordPress / Eszközök / Webhely egészség menüpontban nézd meg, ír e valamilyen hibát a bővítmény. Ha nem segít, akkor az Info fülön másold ki az eBiz-es részt és küldj nekem egy e-mailt!

= Mi a különbség a PRO verzió és az ingyenes között? =

A PRO verzió néhány hasznos funckiót tud, amiről [itt](https://szamlazz.visztpeter.me/ebiz) olvashatsz. Például az automata számlakészítés, díjbekérő létrehozás. Továbbá 1 éves emailes support is jár hozzá.

= Hogyan lehet tesztelni a számlakészítést? =

Az eBiz-től lehet kérni teszt fiókot. A bővítmény beállításaiban pedig kapcsold be a fejlesztői módot.

== Screenshots ==

== Changelog ==

= 1.1 =
* Élesben is használható verzió

= 1.0 =
* WordPress.org-ra feltöltött plugin első verziója
