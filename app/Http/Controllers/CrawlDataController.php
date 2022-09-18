<?php

namespace App\Http\Controllers;

use App\Exports\ExportDataCrawl;
use App\Repositories\ProductImageRepository;
use App\Repositories\ProductRepository;
use DOMElement;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Image;
use Weidner\Goutte\GoutteFacade;
use Excel;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel as FacadesExcel;

class CrawlDataController extends Controller
{
    const TYPE_ONLY = 1;
    const TYPE_COLLECTION = 2;

    public function __construct(
        private ProductRepository $productRepo,
        private ProductImageRepository $productImageRepo
    )
    {

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
        ], [
            "domain.required" => "Vui lòng nhập domain",
            "type.required" => "Vui lòng nhập loại hình crawl",
        ]);

        if ($validator->fails()) {
            return redirect()->route("crawl-data")->with(["message.error" => $validator->messages()->first() ])->withInput();
        }

        $url = $request->domain;
        $type = $request->type;

        try {
            $fileName = "list-data-product.csv";
            $data[] = $this->headers;
            $headers = [
                'Content-Type' => 'text/csv'
            ];
            if ($type == self::TYPE_ONLY) {
                $data[] = $this->baseCrawl($url);
            } else {
                $data = [...$data, ...$this->CrawlCollection($url)];
            }
            // dd($data);
            // $contents = FacadesExcel::raw(new ExportDataCrawl($data, $headers), \Maatwebsite\Excel\Excel::CSV);
            // dd($contents);
            return FacadesExcel::download(new ExportDataCrawl($data, $headers), "test.csv");
        } catch (\Exception $e) {
            return redirect()->route("crawl-data")->with(["message.error" => $e->getMessage() . " | Line " . $e->getLine() ])->withInput(["domain" => $url]);
        }
        return redirect()->route("crawl-data")->with(["message.success" => "Crawl Data Thành Công !"])->withInput(["domain" => $url])->with(compact("data"));
    }

    public function viewData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "domain" => "required",
            "type" => "required",
        ], [
            "domain.required" => "Vui lòng nhập domain",
            "type.required" => "Vui lòng nhập loại hình crawl",
        ]);

        if ($validator->fails()) {
            return redirect()->route("crawl-data")->with(["message.error" => $validator->messages()->first() ])->withInput();
        }

        $url = $request->domain;
        $type = $request->type;

        try {
            $data[] = $this->headers;
            if ($type == self::TYPE_ONLY) {
                $data[] = $this->baseCrawl($url);
            } else {
                $data = [...$data, ...$this->CrawlCollection($url)];
            }
            return redirect()->route("crawl-data")->with("data", $data)->withInput($request->all());
        } catch (\Exception $e) {
            return redirect()->route("crawl-data")->with(["message.error" => $e->getMessage() . " | Line " . $e->getLine() ])->withInput($request->all());
        }
        return redirect()->route("crawl-data")->with(["message.success" => "Crawl Data Thành Công !"])->withInput($request->all());
    }

    public function baseCrawl($url): array
    {
        $crawler = GoutteFacade::request('GET', $url);

        // get title
        $title = $this->getTitle($crawler);

        // get size - get color
        $positionAttribute = $this->getPosition($crawler);

        // get size
        $sizes = $this->getSize($crawler);

        // get color
        $colors = $this->getColor($crawler);

        // get price
        list($regularPrice, $salePrice) = $this->getPrice($crawler);

        // get description
        $description = $this->getDescription($crawler);

        // get images
        $images = $this->getImage($crawler);

        $data = [
            "id" => rand(100, 100000),
            "title" => $title ?? '',
            "regular_price" => $regularPrice,
            "sale_price" => $salePrice,
            "images" => $images,
        ];

        if ($positionAttribute == "size") {
            $data["sizes"] = $colors;
            $data["colors"] = $sizes;
        } else {
            $data["sizes"] = $sizes;
            $data["colors"] = $colors;
        }

        $formatData = [
            $data['id'], // ID
            "external", // Type
            "", // SKU
            $data['title'], // Name
            1, // Published
            0, // Is featured?
            "visible", // Visibility in catalog
            $data['title'] . "...", // Short description
            $description, // Description
            "", // Date sale price starts
            "", // Date sale price ends
            "taxable", // Tax status
            "", // Tax class
            1, // In stock?
            "", // Stock
            "", // Low stock amount
            0, // Backorders allowed?
            0, // Sold individually?
            "", // Weight (kg)
            "", // Length (cm)
            "", // Width (cm)
            "", // Height (cm)
            1, // Allow customer reviews?
            "", // Purchase note
            $data['sale_price'] == $data['sale_price'] ? "" : $data['sale_price'], // Sale price
            $data['regular_price'], // Regular price
            "Uncategorized", // Categories
            "", // Tags
            "", // Shipping class
            $data['images'], // Images
            "", // Download limit
            "", // Download expiry days
            "", // Parent
            "", // Grouped products
            "", // Upsells
            "", // Cross-sells
            "", // External URL
            "", // Button text
            0, // Position
            "", // Attribute 1 name
            "", // Attribute 1 value(s)
            "", // Attribute 1 visible
            "", // Attribute 1 global
            "", // Meta: _et_pb_post_hide_nav
            "", // Meta: _et_pb_page_layout
            "", // Meta: _et_pb_side_nav
            "", // Meta: _et_pb_use_builder
            "", // Meta: _et_pb_first_image
            "", // Meta: _et_pb_truncate_post
            "", // Meta: _et_pb_truncate_post_date
            "", // Meta: _et_pb_old_content
            "", // Attribute 1 default
            "Color", // Attribute 2 name
            $data['colors'], // Attribute 2 value(s)
            1, // Attribute 2 visible
            1, // Attribute 2 global
            "", // Attribute 2 default
            "Size", // Attribute 3 name
            $data['sizes'], // Attribute 3 value(s)
            1, // Attribute 3 visible
            1, // Attribute 3 global
            "", // Attribute 3 default
        ];
        return $formatData;
    }

    public function CrawlCollection($url): array
    {
        $crawler = GoutteFacade::request('GET', $url);
        $linkProducts = $crawler->filter('.product-snippet .product-snippet__img-wrapper')
            ->each(function (Crawler $node) {
                return $node->link()->getUri();
            });
        $data = [];
        foreach ($linkProducts as $link) {
           try {
                $data[] = $this->baseCrawl($link);
           } catch (\Exception $ex) {
                continue;
           }
        }

        return $data;
    }

    public function getTitle($crawler): string
    {
        $arrClassTitles = [
            ".product-info__header_title",
            ".product-info__body .product-info__header_title"
        ];
        $title = "";
        foreach ($arrClassTitles as $class) {
            try {
                $title = $crawler->filter($class)->first()->text();
                if (empty($title)) {
                    continue;
                }
                break;
            } catch (\Exception $ex) {
                continue;
            }
        }

        if (empty($title)) {
            throw new Exception("Không thể crawl được trang này !", 1);
        }
        return $title;
    }

    public function getImage($crawler): string
    {
        $images = [];
        $arrClassImage = [
            ".product-image .product-info__slide img",
            ".product-image .swiper-slide img"
        ];
        foreach ($arrClassImage as $class) {
            try {
                $images = $crawler->filter($class)->each(function (Crawler $node) {
                    $element = $node->first();
                    return $element->attr("data-lazy") ?: $element->attr("data-src") ?: $element->attr("src");
                });

                if (is_array($images)) {
                    $images = array_unique(array_filter($images, fn($item) => !empty($item)));
                }

                if (empty($images)) {
                    continue;
                }
                $images = array_unique($images);
                break;
            } catch (\Exception $ex) {
                continue;
            }
        }
        $images = array_map(function($image) {
            return 'https:' . $image;
        }, $images);
        return implode(", ", $images);
    }

    public function getDescription($crawler): string
    {
        $description = "";
        try {
            $description = $crawler->filter('.product-info__desc-tab-content')->first()->html();
        } catch (\Exception $ex) {
            //
        }
        $description =  str_replace('"', "'", $description);
        $description =  str_replace('data-src', "src", $description);
        $description =  str_replace('origin-src', "src", $description);
        $description =  str_replace('{width}', "500", $description);
        $description =  str_replace('padding-bottom', "", $description);

        return $description;
    }

    public function getPrice($crawler): array
    {
        $regularPrice = 0;
        $salePrice = 0;
        try {
            $regularPrice = $crawler->filter('.product-info__header_price-wrapper .product-info__header_price')->first()->text();
            $regularPrice = (float) ltrim($regularPrice, "$");
        } catch (\Exception $e) {
            //
        }

        try {
            $salePrice = $crawler->filter('.product-info__header_price-wrapper .product-info__header_compare-at-price')->first()->text();
            $salePrice = (float) ltrim($salePrice, "$");
        } catch (\Exception $e) {
            //
        }

        return array($regularPrice, $salePrice);
    }

    public function getColor($crawler): string
    {
        $colors = [];
        $arrClassColor = [
            ".container .product-info__variants_value-wrapper",
            ".product-info__variants-wrapper",
            ".product-info__variants_items"
        ];
        foreach ($arrClassColor as $class) {
            try {
                $colors = $crawler->filter($class)
                    ->first()
                    ->filter('.product-info__variants_value')
                    ->each(function ($node) {
                        return [
                            "name" => $node->attr('value'),
                            "img" => $node->attr('value')
                        ];
                    });

                if (is_array($colors)) {
                    $colors = array_unique(array_filter($colors, fn($item) => !empty($item)));
                }

                if (empty($colors)) {
                    continue;
                }
                break;
            } catch (\Exception $e) {
                continue;
            }
        }
        return implode(", ", $colors);
    }

    public function getSize($crawler): string
    {
        $sizes = [];
        $arrClassSizes = [
            ".container .product-info__variants_value-wrapper",
            ".product-info__variants-wrapper",
            ".product-info__variants_items"
        ];
        foreach ($arrClassSizes as $class) {
            try {
                $sizes = $crawler->filter($class)
                    ->last()
                    ->filter('input')
                    ->each(function ($node) {
                        return $node->attr('value');
                    });

                if (is_array($sizes)) {
                    $sizes = array_unique(array_filter($sizes, fn($item) => !empty($item)));
                }

                if (empty($sizes)) {
                    continue;
                }
                break;
            } catch (\Exception $ex) {
                continue;
            }
        }
        return implode(", ", $sizes);
    }

    public function getPosition($crawler, $positionAttribute = "size"): string
    {
        try {
            $positionAttribute = strtolower($crawler
            ->filter(".product-info__variants_title")
            ->first()
            ->text());
        } catch (\Exception $e) {
            //
        }
        return $positionAttribute;
    }

    private $headers = [
        "ID",
        "Type",
        "SKU",
        "Name",
        "Published",
        "Is featured?",
        "Visibility in catalog",
        "Short description",
        "Description",
        "Date sale price starts",
        "Date sale price ends",
        "Tax status",
        "Tax class",
        "In stock?",
        "Stock",
        "Low stock amount",
        "Backorders allowed?",
        "Sold individually?",
        "Weight (kg)",
        "Length (cm)",
        "Width (cm)",
        "Height (cm)",
        "Allow customer reviews?",
        "Purchase note",
        "Sale price",
        "Regular price",
        "Categories",
        "Tags",
        "Shipping class",
        "Images",
        "Download limit",
        "Download expiry days",
        "Parent",
        "Grouped products",
        "Upsells",
        "Cross-sells",
        "External URL",
        "Button text",
        "Position",
        "Attribute 1 name",
        "Attribute 1 value(s)",
        "Attribute 1 visible",
        "Attribute 1 global",
        "Meta: _et_pb_post_hide_nav",
        "Meta: _et_pb_page_layout",
        "Meta: _et_pb_side_nav",
        "Meta: _et_pb_use_builder",
        "Meta: _et_pb_first_image",
        "Meta: _et_pb_truncate_post",
        "Meta: _et_pb_truncate_post_date",
        "Meta: _et_pb_old_content",
        "Attribute 1 default",
        "Attribute 2 name",
        "Attribute 2 value(s)",
        "Attribute 2 visible",
        "Attribute 2 global",
        "Attribute 2 default",
        "Attribute 3 name",
        "Attribute 3 value(s)",
        "Attribute 3 visible",
        "Attribute 3 global",
        "Attribute 3 default",
    ];
}

