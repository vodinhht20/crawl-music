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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel as FacadesExcel;

class CrawlDataController extends Controller
{
    const TYPE_ONLY = 1;
    const TYPE_COLLECTION = 2;
    const BASIS_SHOPLAZA = 1;
    const BASIS_CLOUDFRONT = 2;

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
            $fileName = "list-data-product.csv";
            $headers = [
                'Content-Type' => 'text/csv'
            ];

            $dataAttrHeader = [];
            if ($type == self::TYPE_ONLY) {
                $dataCaww = $this->baseCrawl($url, $basis);
                $countHeader = (count($dataCaww[0] ?? []) - count($this->headers)) / 5;
                if ($countHeader > 0) {
                    for ($i=1; $i <= $countHeader; $i++) {
                        $dataAttrHeader =[...$dataAttrHeader, ...[
                            "Attribute $i name",
                            "Attribute $i value(s)",
                            "Attribute $i visible",
                            "Attribute $i global",
                            "Attribute $i default"
                        ]];
                    }
                }
                $data[] = [...$this->headers, ...$dataAttrHeader];
                $data = [...$data, ...$dataCaww];
            } else {
                $dataCawwCllecion = $this->CrawlCollection($url, $basis);
                $countHeader = (count($dataCawwCllecion[0] ?? []) - count($this->headers)) / 5;
                if ($countHeader > 0) {
                    for ($i=1; $i <= $countHeader; $i++) {
                        $dataAttrHeader =[...$dataAttrHeader, ...[
                            "Attribute $i name",
                            "Attribute $i value(s)",
                            "Attribute $i visible",
                            "Attribute $i global",
                            "Attribute $i default"
                        ]];
                    }
                }
                $data[] = [...$this->headers, ...$dataAttrHeader];
                $data = [...$data, ...$dataCawwCllecion];
            }

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
        $basis = $request->basis;
        try {
            $dataAttrHeader = [];
            if ($type == self::TYPE_ONLY) {
                $dataCaww = $this->baseCrawl($url, $basis);
                $countHeader = (count($dataCaww[0] ?? []) - count($this->headers)) / 5;
                if ($countHeader > 0) {
                    for ($i=1; $i <= $countHeader; $i++) {
                        $dataAttrHeader =[...$dataAttrHeader, ...[
                            "Attribute $i name",
                            "Attribute $i value(s)",
                            "Attribute $i visible",
                            "Attribute $i global",
                            "Attribute $i default"
                        ]];
                    }
                }
                $data[] = [...$this->headers, ...$dataAttrHeader];
                $data = [...$data, ...$dataCaww];
            } else {
                $dataCawwCllecion = $this->CrawlCollection($url, $basis);
                $countHeader = (count($dataCawwCllecion[0] ?? []) - count($this->headers)) / 5;
                if ($countHeader > 0) {
                    for ($i=1; $i <= $countHeader; $i++) {
                        $dataAttrHeader =[...$dataAttrHeader, ...[
                            "Attribute $i name",
                            "Attribute $i value(s)",
                            "Attribute $i visible",
                            "Attribute $i global",
                            "Attribute $i default"
                        ]];
                    }
                }
                $data[] = [...$this->headers, ...$dataAttrHeader];
                $data = [...$data, ...$dataCawwCllecion];
            }
            return redirect()->route("crawl-data")->with("data", $data)->withInput($request->all());
        } catch (\Exception $e) {
            return redirect()->route("crawl-data")->with(["message.error" => $e->getMessage() . " | Line " . $e->getLine() ])->withInput($request->all());
        }
        return redirect()->route("crawl-data")->with(["message.success" => "Crawl Data Thành Công !"])->withInput($request->all());
    }

    private function baseCrawl($url, $basis): array
    {
        $crawler = GoutteFacade::request('GET', $url);
        $data = [];
        switch ((int) $basis) {
            case self::BASIS_SHOPLAZA :
                $data = $this->shoplaza($crawler);
                break;
            case self::BASIS_CLOUDFRONT :
                $data = $this->cloudfront($crawler);
                break;
            default:
                throw new Exception("Không tìm thấy nền tảng này", 1);
                break;
        }
        return $data;
    }

    private function CrawlCollection($url, $basis): array
    {
        $crawler = GoutteFacade::request('GET', $url);
        $linkProducts = [];
        $data = [];
        switch ((int) $basis) {
            case self::BASIS_SHOPLAZA :
                $linkProducts = $crawler->filter('.product-snippet .product-snippet__img-wrapper')
                    ->each(function (Crawler $node) {
                        return $node->link()->getUri();
                    });
                break;
            case self::BASIS_CLOUDFRONT :
                throw new Exception("Nền tảng này hiện không hỗ trợ crawl theo Collection", 1);
                $linkProducts = $crawler->filter('.product-snippet .product-snippet__img-wrapper')
                    ->each(function (Crawler $node) {
                        return $node->link()->getUri();
                    });
                break;

            default:
                throw new Exception("Không tìm thấy nền tảng này", 1);
                break;
        }

        foreach ($linkProducts as $link) {
           try {
            $data = [...$data, ...$this->baseCrawl($link, $basis)];
           } catch (\Exception $ex) {
                continue;
           }
        }

        return $data;
    }

    private function getDescription($crawler): string
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

    private function shoplaza($crawler)
    {
        $datas = $crawler->filter("script")->each(function (Crawler $node) {
            return $node->html();
        });
        $dataParser = [];
        foreach ($datas as $data) {
            $dataFormat = '{}';
            $resultA = preg_match('/[)][.]product_detail[(](.+?)[)][;]/s', $data, $contentA);
            // $resultB = preg_match('/var opt [=] (.+?)[}][;]/s', $data, $contentB);
            if ($resultA) {
                $dataFormat = str_replace('section_id', '"section_id"', $contentA[1]);
                $dataFormat = str_replace('default_img', '"default_img"', $dataFormat);
                $dataFormat = str_replace('product', '"product"', $dataFormat);
                $dataFormat = str_replace('initialSlide', '"initialSlide"', $dataFormat);
                $dataFormat = str_replace('ajax', '"ajax"', $dataFormat);
                $dataFormat = str_replace(', "detail", true', '', $dataFormat);
                $dataFormat = str_replace(', true, true', '', $dataFormat);
                $dataFormat = str_replace(', "detail", false', '', $dataFormat);
                $dataFormat = str_replace('view_thumb_size', '"view_thumb_size"', $dataFormat);
                $dataFormat = preg_replace('/["]url["][:](.+?)["][,]/s', '', $dataFormat);
                $dataFormat = preg_replace('/["]vendor_url["][:](.+?)["][,]/s', '', $dataFormat);
                $dataParser = @json_decode($dataFormat, true)['product'] ?? [];
                break;
            }
        }
        $description = $this->getDescription($crawler);

        $images = array_map(function($image) {
            return 'https:' . $image['src'];
        }, $dataParser['images'] ?? []);
        $images = implode(", ", $images);

        $dataAttributes = [];

        foreach ($dataParser['options'] ?? [] as $key => $options) {
            $dataAttributes = [...$dataAttributes, ...[
                    "attribute_" . $key + 1 . "_name" => $options['name'] ?? '',
                    "attribute_" . $key + 1 . "_value" => implode(", ", $options['values'] ?? []),
                    "attribute_" . $key + 1 . "_visible" => 1,
                    "attribute_" . $key + 1 . "_global" => 0,
                    "attribute_" . $key + 1 . "_default" => $options['values'][0] ?? ''
                ]
            ];
        }
        $salePrice = $dataParser['price'] ?? 0;
        $regularPrice = $dataParser['compare_at_price'] ?? 0 ?: $salePrice;

        $id = rand(100, 100000);
        $data = [
            "id" => $id, // ID
            "type" => "variable", // Type
            "sku" => "", // SKU
            "name" => $dataParser['title'] ?? 'Không có tên...', // Name
            "published" => $dataParser['published'] ?? 0 ? 1 : 0, // Published
            "is_featured" => 0, // Is featured?
            "visibility_in_catalog" => "visible", // Visibility in catalog
            "short_description" => "", // Short description
            "description" => $description, // Description
            "date_sale_price_starts" => "", // Date sale price starts
            "date_sale_price_ends" => "", // Date sale price ends
            "tax_status" => "taxable", // Tax status
            "tax_class" => "", // Tax class
            "in_stock" => 1, // In stock?
            "stock" => "", // Stock
            "low_stock_amount" => "", // Low stock amount
            "backorders_allowed" => 0, // Backorders allowed?
            "sold_individually" => 0, // Sold individually?
            "weight" => "", // Weight (kg)
            "length" => "", // Length (cm)
            "width" => "", // Width (cm)
            "height" => "", // Height (cm)
            "allow_customer_reviews" => 0, // Allow customer reviews?
            "purchase_note" => "", // Purchase note
            "sale_price" => "", // Sale price
            "regular_price" => "", // Regular price
            "categories" => "Uncategorized", // Categories
            "tags" => "", // Tags
            "shipping_class" => "", // Shipping class
            "images" => $images, // Images
            "download_limit" => "", // Download limit
            "download_expiry_days" => "", // Download expiry days
            "parent" => "", // Parent
            "grouped_products" => "", // Grouped products
            "upsells" => "", // Upsells
            "cross_sells" => "", // Cross-sells
            "external_url" => "", // External URL
            "button_text" => "", // Button text
            "position" => 0
            // , // Position
            // "attribute_1_name" => "Color", // Attribute 1 name
            // "attribute_1_value" => implode(", ", $colors), // Attribute 1 value(s)
            // "attribute_1_visible" => 1, // Attribute 1 visible
            // "attribute_1_global" => 0, // Attribute 1 global
            // "attribute_1_default" => $colors[0] ?? "", // Attribute 1 default
            // "attribute_2_name" => "Size", // Attribute 2 name
            // "attribute_2_value" => implode(", ", $sizes), // Attribute 2 value(s)
            // "attribute_2_visible" => 1, // Attribute 2 visible
            // "attribute_2_global" => 0, // Attribute 2 global
            // "attribute_2_default" => $sizes[0] ?? "", // Attribute 2 default
        ];
        $data = [...$data, ...$dataAttributes];

        $position = 0;
        $formatData[] = $data;
        foreach ($dataParser['variants'] ?? [] as $variant) {
            $dataAttrChild = [];
            ++$position;
            ++$id;
            $newData = $data;
            $img = $variant['image']['src'] ?? null;
            $dataChildrent = [
                ...$newData,
                "id" => $id,
                "type" => "variation",
                "name" => $variant["title"] ?? "Chưa có tên",
                "short_description" => "",
                "description" => "",
                "tax_class" => "parent",
                "sale_price" => $salePrice,
                "regular_price" => $regularPrice,
                "categories" => "",
                "images" => $img ? "https:" . $img : '',
                "parent" => "id:{$data['id']}",
                "position" => $position
                // ,
                // "attribute_1_value" => $indexColor == 1 ? $variant['option1'] ?? '' : $variant['option2'] ?? '',
                // "attribute_1_visible" => "",
                // "attribute_1_default" => "",
                // "attribute_2_value" => $indexColor == 2 ? $variant['option2'] ?? '' : $variant['option1'] ?? '',
                // "attribute_2_visible" => "",
                // "attribute_2_default" => ""
            ];
            $indexx = 1;
            foreach ($dataParser['options'] as $key => $item) {
                $newKey = "option".$indexx;
                if (isset($variant[$newKey])) {
                    $dataAttrChild = [...$dataAttrChild, ...[
                        "attribute_" . $indexx . "_value" => $variant[$newKey],
                        "attribute_" . $indexx . "_visible" => "",
                        "attribute_" . $indexx . "_default" => "",
                    ]];
                }
                $indexx++;
            }
            $formatData[] = [...$dataChildrent, ...$dataAttrChild];
        }
        return $formatData;
    }

    private function cloudfront($crawler)
    {
        $dataRaw = $crawler->filter("#config")->first()->html();
        $regex = '/\s[:]product[=][\"]([{].+.?[}])[\"]\s*[:][a-zA-Z]/';
        $dataFormat = '{}';
        $result = preg_match($regex, $dataRaw, $content);
        if ($result) {
            $dataFormat = htmlspecialchars_decode($content[1]);
        }
        $dataParser = json_decode($dataFormat, true);
        // dd($dataParser);
        $dataAttributes = [];
        foreach ($dataParser['variant_attrs'] ?? [] as $key => $options) {
            $dataAttributes = [...$dataAttributes, ...[
                    "attribute_" . $key + 1 . "_name" => $options['name'] ?? '',
                    "attribute_" . $key + 1 . "_value" => implode(", ", $options['value'] ?? []),
                    "attribute_" . $key + 1 . "_visible" => 1,
                    "attribute_" . $key + 1 . "_global" => 0,
                    "attribute_" . $key + 1 . "_default" => $options['value'][0] ?? ''
                ]
            ];
        }
        $images = implode( ", ", Arr::pluck($dataParser['gallery'] ?? [], 'url'));
        $id = rand(100, 100000);
        $data = [
            "id" => $id, // ID
            "type" => "variable", // Type
            "sku" => "", // SKU
            "name" => $dataParser['post_title'] ?? 'Không có tên...', // Name
            "published" => 1, // Published
            "is_featured" => 0, // Is featured?
            "visibility_in_catalog" => "visible", // Visibility in catalog
            "short_description" => "", // Short description
            "description" => $dataParser['post_content'], // Description
            "date_sale_price_starts" => "", // Date sale price starts
            "date_sale_price_ends" => "", // Date sale price ends
            "tax_status" => "taxable", // Tax status
            "tax_class" => "", // Tax class
            "in_stock" => 1, // In stock?
            "stock" => "", // Stock
            "low_stock_amount" => "", // Low stock amount
            "backorders_allowed" => 0, // Backorders allowed?
            "sold_individually" => 0, // Sold individually?
            "weight" => "", // Weight (kg)
            "length" => "", // Length (cm)
            "width" => "", // Width (cm)
            "height" => "", // Height (cm)
            "allow_customer_reviews" => 0, // Allow customer reviews?
            "purchase_note" => "", // Purchase note
            "sale_price" => "", // Sale price
            "regular_price" => "", // Regular price
            "categories" => "Uncategorized", // Categories
            "tags" => "", // Tags
            "shipping_class" => "", // Shipping class
            "images" => $images, // Images
            "download_limit" => "", // Download limit
            "download_expiry_days" => "", // Download expiry days
            "parent" => "", // Parent
            "grouped_products" => "", // Grouped products
            "upsells" => "", // Upsells
            "cross_sells" => "", // Cross-sells
            "external_url" => "", // External URL
            "button_text" => "", // Button text
            "position" => 0
            // , // Position
            // "attribute_1_name" => "Color", // Attribute 1 name
            // "attribute_1_value" => implode(", ", $colors), // Attribute 1 value(s)
            // "attribute_1_visible" => 1, // Attribute 1 visible
            // "attribute_1_global" => 0, // Attribute 1 global
            // "attribute_1_default" => $colors[0] ?? "", // Attribute 1 default
            // "attribute_2_name" => "Size", // Attribute 2 name
            // "attribute_2_value" => implode(", ", $sizes), // Attribute 2 value(s)
            // "attribute_2_visible" => 1, // Attribute 2 visible
            // "attribute_2_global" => 0, // Attribute 2 global
            // "attribute_2_default" => $sizes[0] ?? "", // Attribute 2 default
        ];
        $data = [...$data, ...$dataAttributes];
        $position = 0;
        $formatData[] = $data;
        foreach ($dataParser['variants'] ?? [] as $variant) {
            $dataAttrChild = [];
            ++$position;
            ++$id;
            $newData = $data;
            $dataChildrent = [
                ...$newData,
                "id" => $id,
                "type" => "variation",
                "name" => $variant["post_title"] ?? "Chưa có tên",
                "short_description" => "",
                "description" => "",
                "tax_class" => "parent",
                "sale_price" => $variant['sale_price'] ?? 0,
                "regular_price" => $variant['regular_price'] ?? 0,
                "categories" => "",
                "images" => $variant['image'] ?? '',
                "parent" => "id:{$data['id']}",
                "position" => $position
                // ,
                // "attribute_1_value" => $indexColor == 1 ? $variant['option1'] ?? '' : $variant['option2'] ?? '',
                // "attribute_1_visible" => "",
                // "attribute_1_default" => "",
                // "attribute_2_value" => $indexColor == 2 ? $variant['option2'] ?? '' : $variant['option1'] ?? '',
                // "attribute_2_visible" => "",
                // "attribute_2_default" => ""
            ];
            $indexx = 1;
            foreach ($variant['attrs'] as $item) {
                $dataAttrChild = [...$dataAttrChild, ...[
                    "attribute_" . $indexx . "_value" => $item['value'],
                    "attribute_" . $indexx . "_visible" => "",
                    "attribute_" . $indexx . "_default" => "",
                ]];
                $indexx++;
            }
            $formatData[] = [...$dataChildrent, ...$dataAttrChild];
        }
        return $formatData;
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
        "Position"
        // ,
        // "Attribute 1 name",
        // "Attribute 1 value(s)",
        // "Attribute 1 visible",
        // "Attribute 1 global",
        // "Attribute 1 default",
        // "Attribute 2 name",
        // "Attribute 2 value(s)",
        // "Attribute 2 visible",
        // "Attribute 2 global",
        // "Attribute 2 default",
    ];
}

