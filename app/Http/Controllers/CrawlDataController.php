<?php

namespace App\Http\Controllers;

use Excel;
use Exception;
use DOMElement;
use App\Models\Song;
use App\Models\Singer;
use GuzzleHttp\Client;
use App\Models\SongSinger;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Exports\ExportDataCrawl;
use Weidner\Goutte\GoutteFacade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Auth\Events\Validated;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Image;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\DomCrawler\Crawler;
use Maatwebsite\Excel\Excel as ExcelExcel;
use App\Repositories\ProductImageRepository;
use Maatwebsite\Excel\Facades\Excel as FacadesExcel;
use GuzzleHttp\TransferStats;

class CrawlDataController extends Controller
{
    public const MULTIPLE = 2;

    public const ONLY = 1;

    public function __construct(
        private ProductRepository $productRepo,
        private ProductImageRepository $productImageRepo
    )
    {
        set_time_limit(10000);
    }

    public function index(Request $request)
    {
        return view('main.index');
    }

    public function handleCrawl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "domain" => "required",
            "type" => "required",
            "basis" => "required",
        ], [
            "domain.required" => "Vui lòng nhập domain",
            "type.required" => "Vui lòng chọn loại hình crawl",
            "basis.required" => "Vui lòng chọn loại nền tảng",
        ]);

        if ($validator->fails()) {
            return redirect()->route("crawl-data")->with(["message.error" => $validator->messages()->first() ])->withInput();
        }

        $url = $request->domain;
        $type = $request->type;
        $basis = $request->basis;

        try {
            $urls = [$url];
            if ($type == self::MULTIPLE) {
                $urls = $this->getAllSongLinks($url);
            }

            foreach ($urls as $url) {
                $this->baseCrawlSong($url);
            }
        } catch (\Exception $e) {
            return redirect()->route("crawl-data")->with(["message.error" => $e->getMessage() . " | Line " . $e->getLine() ])->withInput($request->all());
        }

        return redirect()->route("crawl-data")->with(["message.success" => "Crawl Data Thành Công !"])->withInput($request->all());
    }

    private function tranferData($crawler) {
        $rawData = $crawler->filter('#player .audio script')->html();
        preg_match('/playlist[=](.*[}]\])/s', $rawData, $contentA);
        $string = $contentA[1];
        $string = str_replace('title', "'title'", $string);
        $string = str_replace('artist', "'artist'", $string);
        $string = str_replace('cover', "'cover'", $string);
        $string = str_replace('mp3:', "'mp3':", $string);
        $string = str_replace("'", '"', $string);

        // lyrics;
        try {
            $lyrics = $crawler->filter('.content-lyrics')?->html();
        } catch (\Throwable $th) {
            $lyrics = "";
        }

        $data = json_decode($string);

        $urlmp3 = $data[0]->mp3;
        $client = new Client;

        $client->get($urlmp3, [
            'query'   => ['get' => 'params'],
            'on_stats' => function (TransferStats $stats) use (&$url) {
                $url = $stats->getEffectiveUri();
                dd($url);
            }
        ])->getBody()->getContents();

        $singers = $this->getSingers($data[0]->artist);
        $singers = array_map(function($singer) {
                return html_entity_decode($singer);
        }, $singers);
        $title = html_entity_decode($data[0]->title);
        return [
            "lyrics" => $lyrics,
            "name" => $title,
            "slug" => Str::slug($title),
            "singer" => $singers,
            "thumbnail" => $data[0]->cover,
            "media_file" => $url,
        ];
    }

    private function getSingers(string $rawSinger)
    {
        return explode(',', $rawSinger);
    }

    private function getRawData($url)
    {
        return GoutteFacade::request('GET', $url);
    }

    private function validateSong($publicKey): bool
    {
        return (bool) Song::wherePublicKey($publicKey)->first();
    }

    private function getPublicKey($url)
    {
        preg_match('/([0-9a-zA-Z]*)[.]html/', $url, $result);

        return $result[1];
    }

    public function createSinger(array $singers) {
        $singerFormat = array_map(function($singer) {
            return Str::slug($singer);
        }, $singers);
        $singerExists = Singer::whereIn('slug', $singerFormat)->select('id', 'slug')->get()->keyBy('slug');
        $singerData = [];

        foreach($singers as $singer) {
            $slug = Str::slug($singer);
            $idSinger = null;
            if (empty($singerExists[$slug])) {
                $newSinger = Singer::create([
                    "name" => $singer,
                    "slug" => $slug,
                    "image" => "",
                ]);
                $idSinger = $newSinger->id;
            } else {
                $idSinger = $singerExists[$slug]->id;
            }

            array_push($singerData, $idSinger);
        };

        return $singerData;
    }

    public function createSong(array $data)
    {
        return Song::create([
            "public_key" => $data['public_key'],
            "slug" => $data['slug'],
            "name" => $data['name'],
            "thumbnail" => $data['thumbnail'],
            "lyrics" => $data['lyrics'],
            "total_time" => 0,
            "file_type" => Song::FILE_ONLINE,
            "media_file" => $data['media_file'],
        ]);
    }

    public function createSongSingers($song, $singerIds): void
    {
        foreach($singerIds as $singerId) {
            SongSinger::create([
                "song_id" => $song->id,
                "singer_id" => $singerId,
            ]);
        }
    }

    public function baseCrawlSong($url): void
    {
        $publicKey = $this->getPublicKey($url);
        if ($this->validateSong($publicKey)) {
            return;
        }

        try {
            DB::beginTransaction();
            $crawler = $this->getRawData($url);
            $data = [...$this->tranferData($crawler), "public_key" => $publicKey];
            $singerIds = $this->createSinger($data['singer']);
            $song = $this->createSong($data);
            $this->createSongSingers($song, $singerIds);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
        }
    }

    public function getAllSongLinks(string $url): array
    {
        $crawler = $this->getRawData($url);
        return $crawler->filter('.list-content-music a')
            ->each(function (Crawler $node) {
                return $node->attr('href');
            });
    }
}
