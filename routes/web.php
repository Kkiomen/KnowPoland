<?php

use App\Http\Controllers\ErrorPageController;
use App\Http\Controllers\LifeNowController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('/history', 'History')->name('history');
Route::inertia('/history/how-poland-began', 'HistoryOrigins')->name('history.origins');
Route::inertia('/history/lech-and-the-white-eagle', 'HistoryEagle')->name('history.eagle');
Route::inertia('/history/baptism-of-966', 'HistoryBaptism')->name('history.baptism');
Route::inertia('/history/gniezno-the-first-capital', 'HistoryGniezno')->name('history.gniezno');
Route::inertia('/history/casimir-the-great', 'HistoryCasimir')->name('history.casimir');
Route::inertia('/history/where-to-stand-in-it', 'HistoryWhere')->name('history.where');
Route::inertia('/history/grunwald-1410', 'HistoryGrunwald')->name('history.grunwald');
Route::inertia('/history/the-golden-age', 'HistoryGoldenAge')->name('history.golden-age');
Route::inertia('/history/one-state-many-faiths', 'HistoryFaiths')->name('history.faiths');
Route::inertia('/history/vienna-1683', 'HistoryVienna')->name('history.vienna');
Route::inertia('/history/serfdom', 'HistorySerfdom')->name('history.serfdom');
Route::inertia('/history/liberum-veto', 'HistoryVeto')->name('history.veto');
Route::inertia('/history/constitution-of-3-may-1791', 'HistoryConstitution')->name('history.constitution');
Route::inertia('/history/first-partition-1772', 'HistoryFirstPartition')->name('history.first-partition');
Route::inertia('/history/second-partition-1793', 'HistorySecondPartition')->name('history.second-partition');
Route::inertia('/history/kosciuszko-1794', 'HistoryKosciuszko')->name('history.kosciuszko');
Route::inertia('/history/third-partition-1795', 'HistoryThirdPartition')->name('history.third-partition');
Route::inertia('/history/uprisings-1830-1863', 'HistoryUprisings')->name('history.uprisings');
Route::inertia('/history/keeping-a-country', 'HistoryKeeping')->name('history.keeping');
Route::inertia('/history/three-empires-three-cities', 'HistoryThreeEmpires')->name('history.three-empires');
Route::inertia('/history/second-republic-1918', 'HistorySecondRepublic')->name('history.second-republic');
Route::inertia('/history/battle-of-warsaw-1920', 'HistoryWarsaw1920')->name('history.warsaw-1920');
Route::inertia('/history/september-1939', 'HistorySeptember1939')->name('history.september-1939');
Route::inertia('/history/free-city-of-danzig', 'HistoryDanzig')->name('history.danzig');
Route::inertia('/history/westerplatte', 'HistoryWesterplatte')->name('history.westerplatte');
Route::inertia('/history/molotov-ribbentrop-pact', 'HistoryPact')->name('history.pact');
Route::inertia('/history/government-in-exile', 'HistoryExile')->name('history.exile');
Route::inertia('/history/general-government', 'HistoryGeneralGovernment')->name('history.general-government');
Route::inertia('/history/katyn', 'HistoryKatyn')->name('history.katyn');
Route::inertia('/history/zamosc-expulsions', 'HistoryZamosc')->name('history.zamosc');
Route::inertia('/history/volhynia-1943', 'HistoryVolhynia')->name('history.volhynia');
Route::inertia('/history/the-ghettos', 'HistoryGhettos')->name('history.ghettos');
Route::inertia('/history/the-death-camps', 'HistoryCamps')->name('history.camps');
Route::inertia('/history/warsaw-ghetto-uprising', 'HistoryGhettoUprising')->name('history.ghetto-uprising');
Route::inertia('/history/underground-state', 'HistoryUnderground')->name('history.underground');
Route::inertia('/history/witold-pilecki', 'HistoryPilecki')->name('history.pilecki');
Route::inertia('/history/jan-karski', 'HistoryKarski')->name('history.karski');
Route::inertia('/history/enigma', 'HistoryEnigma')->name('history.enigma');
Route::inertia('/history/polish-forces-abroad', 'HistoryForces')->name('history.forces');
Route::inertia('/history/warsaw-uprising-1944', 'HistoryRising')->name('history.rising');
Route::inertia('/history/occupation-and-holocaust', 'HistoryOccupation')->name('history.occupation');
Route::inertia('/history/second-world-war', 'HistoryWar')->name('history.war');
Route::inertia('/history/the-borders-moved', 'HistoryBorders')->name('history.borders');
Route::inertia('/history/rebuilding-warsaw', 'HistoryRebuilding')->name('history.rebuilding');
Route::inertia('/history/solidarity-1980', 'HistorySolidarity')->name('history.solidarity');
Route::inertia('/history/june-1989', 'HistoryJune1989')->name('history.june-1989');
Route::inertia('/history/the-transition', 'HistoryTransition')->name('history.transition');
Route::inertia('/history/nato-and-the-eu', 'HistoryEurope')->name('history.europe');
Route::inertia('/history/poland-since-2004', 'HistoryToday')->name('history.today');

Route::inertia('/food', 'Food')->name('food');

Route::get('/poland-today', LifeNowController::class)->name('life-now');

Route::inertia('/everyday-life', 'Everyday')->name('everyday');

Route::inertia('/how-this-site-works', 'Method')->name('method');

Route::inertia('/start-here', 'Start')->name('start');

Route::inertia('/polish-roots', 'Roots')->name('roots');

Route::inertia('/places', 'Places')->name('places');
Route::inertia('/places/gdansk', 'PlaceGdansk')->name('places.gdansk');
Route::inertia('/places/warszawa', 'PlaceWarszawa')->name('places.warszawa');
Route::inertia('/places/krakow', 'PlaceKrakow')->name('places.krakow');
Route::inertia('/places/wroclaw', 'PlaceWroclaw')->name('places.wroclaw');
Route::inertia('/places/lodz', 'PlaceLodz')->name('places.lodz');
Route::inertia('/places/poznan', 'PlacePoznan')->name('places.poznan');
Route::inertia('/places/szczecin', 'PlaceSzczecin')->name('places.szczecin');
Route::inertia('/places/bydgoszcz', 'PlaceBydgoszcz')->name('places.bydgoszcz');
Route::inertia('/places/lublin', 'PlaceLublin')->name('places.lublin');
Route::inertia('/places/katowice', 'PlaceKatowice')->name('places.katowice');
Route::inertia('/places/zakopane', 'PlaceZakopane')->name('places.zakopane');
Route::inertia('/places/torun', 'PlaceTorun')->name('places.torun');
Route::inertia('/places/malbork', 'PlaceMalbork')->name('places.malbork');
Route::inertia('/places/wieliczka', 'PlaceWieliczka')->name('places.wieliczka');
Route::inertia('/places/bialowieza', 'PlaceBialowieza')->name('places.bialowieza');
Route::inertia('/places/mazury', 'PlaceMazury')->name('places.mazury');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

/**
 * Anything else.
 *
 * Search engines hold on to addresses long after a site changes, so a reader
 * landing on one that no longer exists is an ordinary event here. The fallback
 * sits inside the web group on purpose: it gets the language middleware and
 * the shared copy, so the page that answers is in the reader's own language.
 */
Route::fallback(ErrorPageController::class);
