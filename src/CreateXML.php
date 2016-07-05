<?php namespace OrangeShadow\TovariMailRu;

use DOMDocument;
use DOMElement;


/**
 * Class CreateXML
 * Подробнее на http://torg.mail.ru/info/122/#torg_price
 * @package OrangeShadow\TovariMailRu
 */
class CreateXML
{

    protected $dom;
    protected $shopElement;
    protected $xmlElements = [];
    protected $cleanFunction;


    /**
     * CreateXML constructor.
     * @param array $properties can have key: "name","company","url","currencies","categories","offers"
     * @param $cleanFunction closure should return string
     */
    function __construct($properties = [], $cleanFunction = null)
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;

        if (is_callable($cleanFunction)) {
            $this->cleanFunction = $cleanFunction;
        }

        if (!empty($properties)) {
            foreach ($properties as $key => $value) {
                $this->{$key}($value);
            }
        }


    }


    /**
     * Функция для запуска методов установки тегов по ключам массива через конструктор
     * @param $name
     * @param $parametrs
     */
    public function __call($name, $parametrs)
    {
        if (method_exists($this, 'set' . ucfirst(strtolower($name)))) {
            if (is_array($parametrs) && count($parametrs) > 1)
                $this->{'set' . ucfirst(strtolower($name))}($parametrs);
            else
                $this->{'set' . ucfirst(strtolower($name))}($parametrs[0]);
        }
    }

    /**
     * Устанавливаем название сайта
     * @param $value
     * @throws \Exception
     */
    public function setName($value)
    {
        if(!is_string($value)) throw new \Exception('Ожидается строка, передано: '. print_r($value,true));

        array_push($this->xmlElements, $this->dom->createElement('name', $this->clean($value, 'name')));
    }

    /**
     * Название фирмы
     * @param $value
     * @throws \Exception
     */
    public function setCompany($value)
    {
        if(!is_string($value)) throw new \Exception('Ожидается строка, передано: '.print_r($value,true));
        array_push($this->xmlElements, $this->dom->createElement('company', $this->clean($value, 'company')));
    }

    /**
     * Устанавливаем url сайта
     * @param $value
     * @throws \Exception
     */
    public function setUrl($value)
    {
        if(!is_string($value)) throw new \Exception('Ожидается строка, передано: '.print_r($value,true));
        array_push($this->xmlElements, $this->dom->createElement('url', $this->clean($value, 'url')));
    }

    /**
     * Указываем валюты магазина, ожидает приема массива с валютами id,rate,plus
     * @param $currencies
     * @throws \Exception
     */
    public function setCurrencies($currencies)
    {
        $currenciesElement = $this->dom->createElement('currencies');
        foreach ($currencies as $currency) {
            if (!isset($currency['id']) || !isset($currency['rate'])) throw new \Exception('Неверно передан массив валют, отсутствует id или rate' . print_r($currency, true));
            $currencyElement = $this->dom->createElement('currency');
            foreach ($currency as $key => $value) {
                $currencyElement->setAttribute($key, $this->clean($value, "currency." . $key));
            }
            $currenciesElement->appendChild($currencyElement);
        }
        array_push($this->xmlElements, $currenciesElement);
    }

    /**
     * Создаем категории товаров
     * @param $categories  содержит сущность(массив) с ключами: name,id,parentId
     * @throws \Exception
     */
    public function setCategories($categories)
    {
        $categoriesElement = $this->dom->createElement('categories');
        foreach ($categories as $category) {
            $errors = [];
            if (!isset($category['name'])) $errors[] = 'Каталог не содержит названия';
            if (!isset($category['id'])) $errors[] = 'Каталог не содержит id';

            if (isset($category['parent_id'])) {
                $category['parentId'] = $category['parent_id'];
                unset($category['parent_id']);
            }

            if (!empty($errors))
                throw new \Exception('Ошибка при создании каталога' . print_r($errors, true) . "\nПередан массив: " . print_r($category, true));

            $categoryElement = $this->dom->createElement('category', $this->clean($category['name'],'category.name'));
            unset($category["name"]);
            foreach ($category as $key => $value) {
                $categoryElement->setAttribute($key, $value);
            }
            $categoriesElement->appendChild($categoryElement);
        }
        array_push($this->xmlElements, $categoriesElement);
    }

    /**
     * Создание предложений
     * @param $offers должен содержать массив сущностей: id,available,cbid,url,price,currencyId,categoryId,picture,typePrefix,vendor,model,description,delivery,pickup,local_delivery_cost
     * @throws \Exception
     */
    public function setOffers($offers)
    {
        $offersElement = $this->dom->createElement('offers');
        foreach ($offers as $offer) {

            if (!isset($offer['id'])) throw new \Exception('Ошибка при создании торгового предложения, нет id элемента' . print_r($offer, true));
            if (!isset($offer['url'])) throw new \Exception('Ошибка при создании торгового предложения, нет url' . print_r($offer, true));
            if (!isset($offer['model'])) throw new \Exception('Ошибка при создании торгового предложения, нет model' . print_r($offer, true));
            if (!isset($offer['price'])) throw new \Exception('Ошибка при создании торгового предложения, нет price' . print_r($offer, true));

            $offerElement = $this->dom->createElement('offer');

            $offerElement->setAttribute('id', $offer['id']);
            unset($offer['id']);

            if (isset($offer['available'])) {
                $offerElement->setAttribute('available', $offer['available'] ? "true" : "false");
                unset($offer['available']);
            }

            if (isset($offer['cbid'])) {
                $offerElement->setAttribute('id', $offer['cbid']);
                unset($offer['cbid']);
            }

            foreach ($offer as $key => $property) {
                if($key=="description"){
                    $propertyElement = $this->dom->createCDATASection($key, $this->clean($property, "offer." . $key));
                }else{
                    $propertyElement = $this->dom->createElement($key, $this->clean($property, "offer." . $key));

                }
                $offerElement->appendChild($propertyElement);
            }
            $offersElement->appendChild($offerElement);
        }

        array_push($this->xmlElements, $offersElement);
    }


    public function setCleanFunction($function){
        if(is_callable($function)) {
            $this->cleanFunction = $function;
        }
    }

    /**
     * Собираем документ из имеющихся элементов xmlElements
     * @return DOMElement
     */
    protected function setShop()
    {

        $shopElement = $this->dom->createElement('shop');

        foreach ($this->xmlElements as $element) {
            $shopElement->appendChild($element);
        }

        return $shopElement;
    }


    /**
     * Чистим код от html символов или используем функцию которую передал пользователь
     * @value значение
     * @key ключ
     */
    protected function clean($value, $key)
    {
        if (!empty($this->cleanFunction) && is_callable($this->cleanFunction)) {
            return call_user_func($this->cleanFunction,$value, $key);
        }

        if(empty($value)) return $value;

        if (!is_string($value) && !is_numeric($value)) throw new \Exception('Ожидается строка, передан ' . gettype($value) . ' ' . print_r($value, true));

        if($key=="offer.description"){
            return $value;
        }

        return htmlentities($value);
    }

    /**
     * Конечное создание всего XML дерева
     */
    protected function createTorgPrice()
    {

        $dt = new \DateTime();
        $torgPrice = $this->dom->createElement('torg_price');
        $torgPrice->setAttribute('date', $dt->format('Y-m-d H:i'));


        $torgPrice->appendChild($this->setShop());

        $this->dom->appendChild($torgPrice);

    }

    public function __toString()
    {
        header("Content-Type: text/xml");
        $this->createTorgPrice();

        return $this->dom->saveXML();
    }


}