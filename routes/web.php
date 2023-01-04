<?php

use Weidner\Goutte\GoutteFacade;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CrawlDataController;
use App\Http\Controllers\AuthenticationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/login', [AuthenticationController::class, 'index'])->name("login");
Route::get('/logout', [AuthenticationController::class, 'logout'])->name("logout");
Route::post('/post-login', [AuthenticationController::class, 'postLogin'])->name("post-login");
Route::get('/', [CrawlDataController::class, 'index'])->middleware("authenticated")->name("crawl-data");
Route::post('/post-crawl-data', [CrawlDataController::class, 'handleCrawl'])->middleware("authenticated")->name('post-crawl-data');
Route::get('test', function() {
    $url = "https://nhachay360.com/bai-hat/co-nguoi-nonhanta-nguyen-ngoc-anh-khoa.UQVhEZbGcUw.html";
    preg_match('/([0-9a-zA-Z]*)[.]html/', $url, $unicId);
    $crawler = GoutteFacade::request('GET', $url);
    $rawData = $crawler->filter('#player .audio script')->html();
    preg_match('/playlist[=](.*[}]\])/s', $rawData, $contentA);
    $string = $contentA[1];
    $string = str_replace('title', "'title'", $string);
    $string = str_replace('artist', "'artist'", $string);
    $string = str_replace('cover', "'cover'", $string);
    $string = str_replace('mp3:', "'mp3':", $string);
    $string = str_replace("'", '"', $string);

    // lyrics;
    $lyrics = $crawler->filter('.content-lyrics')->html();

    $publicKey = $unicId[1];

    $data = json_decode($string);
    echo $data[0]->artist;
    // dd($data);

});