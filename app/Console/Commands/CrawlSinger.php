<?php

namespace App\Console\Commands;

use App\Models\Singer;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Weidner\Goutte\GoutteFacade;
use Symfony\Component\DomCrawler\Crawler;

class CrawlSinger extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:singer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl ca sỹ';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        for ($i=1; $i <= 95; $i++) {
            $url = "https://nhac.vn/nghe-si?p=$i";
            $crawler = GoutteFacade::request('GET', $url);
            $artists = $crawler->filter('.list_playlist .artist-list-large-item')
                ->each(function (Crawler $node) {
                    $name = $node->filter('.name')->text();
                    $image = $node->filter('img')->attr('src');
                    if ($image == '/web-v2/new-image/default-avatar-artist.jpg') {
                        $image = null;
                    }
                    return [
                        'name' => $name,
                        'slug' => Str::slug($name),
                        'image' => $image,
                    ];
                });

            $this->info("-------------- Đang crawl page $i ---------------");
            foreach ($artists as $key => $signer) {
                Singer::updateOrCreate($signer);
                $this->info("Đang crawl item " . $key + 1);
            }
        }

        $this->info("Đã crawl xong !");
    }
}
