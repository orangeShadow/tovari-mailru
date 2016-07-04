# tovari-mailru
Создание XML Прайс листа для выгрузки в Товары mail.ru

Более подробную информацию о возможных параметрых читайте на http://torg.mail.ru/info/122/#torg_price
```php
$array = [
    "name"       => "Магазин на Диване",
    "company"    => 'ООО "Рога и копыта"',
    "url"        => 'http://example.ru',
    "currencies" => [
        ["id" => "RUR", "rate" => 1]
    ],
    "categories" => [
        [
            "name" => "Обувь",
            "id"   => 1,
        ],
        [
            "name"     => "Кроссовки",
            "id"       => 2,
            "parentId" => 1
        ]
    ],
    "offers"     => [
        [
            "id"        => "11",
            "model"     => "Samsung XD3343",
            "available" => true,
            "vendor"    => "Samsung",
            "url"       => "http://example.ru/catalog/samsung-XD3343",
            "price"     => 5000,
            "description"=>'<p>Некое описание товара</p>'
        ]
    ]
];

$cleanFunction = function($value,$key){
    return htmlentities($value);
};

$dom = use OrangeShadow\TovariMailRu\CreateXML($array,$cleanFunction);

//Вывод xml
echo $dom
```
