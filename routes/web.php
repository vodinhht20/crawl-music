<?php

use Illuminate\Support\Str;
use Weidner\Goutte\GoutteFacade;
use Illuminate\Support\Facades\Route;
use Symfony\Component\DomCrawler\Crawler;
use App\Http\Controllers\CrawlDataController;
use App\Http\Controllers\AuthenticationController;
use App\Models\Singer;

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
    // $url = "https://nhac.vn/album/nhung-bai-hat-hay-nhat-ve-tinh-yeu-mua-xuan-pl5jbyj?st=sonqeA7";
    // $crawler = GoutteFacade::request('GET', $url);
    // $content = $crawler->html();
    // $a = preg_match('/playlist[:](.*\])\,/s', $content, $contentA);
    // dd (json_decode($contentA[1], true));

    // crawl ca sỹ
    // dd();
    // $rawData = $crawler->filter('#player .audio script')->html();


});
