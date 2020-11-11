<?php
//
// https://2kata.ru/_novikov/index.php
//


class carParser
{
    public $file_cookies = 'cookies_file.txt';
    public $curl_debug = 0;
    public $income_url = '';


    private $current_lot_id = '';
    private $current_cookies_file_content = '';
    private $current_cookies_from_nodejs = '';
    private $current_json_responce = '';

    function __construct($income_url)
    {

        // ЛОКАЛЬНАЯ КОПИЯ НА ВИНДЕ
        if ($_SERVER['HTTP_HOST'] === 'car-parse.loc') {
            $this->file_cookies = 'C:/OSPanel/domains/car-parse.loc/' . $this->file_cookies;
        }

        // проанализируем содержимео на предмет нужныъ нам ключевых ключей
        $this->income_url = $income_url;
        if (strpos($this->income_url, 'copart.com')) {
            $this->file_cookies = $this->file_cookies . '.copart.txt';
            $this->current_cookies_file_content = $this->cookies_parse_from_file();
            echo $this->copart();

        } else if (strpos($this->income_url, 'iaai.com')) {
            $this->file_cookies = $this->file_cookies . '.iaai.txt';
            echo $this->iaai();

        } else {
            echo json_encode([
                'error' => 1,
                'error_desc' => 'Wrong URL',
                'url' => $this->income_url,
            ]);
        }
    }


    function copart()
    {

        preg_match('~/lot/([0-9]+)~', $this->income_url, $d);
        $this->current_lot_id = $d[1];
        $all_requested_list_debug = [];


        // НЕ ВАЖНО ОТ ТОГО БЫЛИ ЛИ КУИКИ ЛИ НЕТ - ДЕЛАЕМ НУЛЕВОЙ ШАГ ИНИЦИИРУЕМ КУКИ или сразу получаем ответ еис они были
        // запос со старыми куками если они есть
        $this->current_json_responce = $this->send_http_request(
            "https://www.copart.com/public/data/lotdetails/solr/" . $this->current_lot_id,
            "GET",
            [
                'authority: www.copart.com',
                'pragma: no-cache',
                'cache-control: no-cache',
                'upgrade-insecure-requests: 1',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36',
                'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'sec-fetch-site: none',
                'sec-fetch-mode: navigate',
                'sec-fetch-user: ?1',
                'sec-fetch-dest: document',
                'accept-language: en,ru;q=0.9,uk;q=0.8',
                //'cookie: ' . $this->current_cookies, !!!!!!!!!!!!! куки в файле
            ],
            false // орати нимание этот же покси на NodeJS
        );


        $all_requested_list_debug[] = [
            'name' => 'first step result НЕВАЖНО БЫЛИ ЛИ КУКИ',
            'current_cookies_file_content' => $this->cookies_parse_from_file(),
            'current_cookies_from_nodejs' => 'пусто пока',
            'responce' => $this->current_json_responce
        ];

        // ЭТО НУЛЕВОЙ ЗАПРОС НА ПУСТОМ СЕРВЕРЕ ?? нет кук
        // ИЛИ ДАВНО НЕ ДАЛЕЛИ ЗАПРОСЫ?
        // ПЕРВЫЙ ЗАПРОС ХРЕНОВЫЙ - нам отказали ! ДЕЛАЕМ ВТОРОЙ С ПАПИТИРОМ
        // получаем НЕДОСТАЮЩИЕ КУКИ И ДЕЛАЕМ ИНЬТЕКЦИЮ К СУЩЕСТВУЮЩИМ В ФАЙЛЕ
        if (
            strpos($this->current_json_responce, '_Incapsula_Resource')
            or strpos($this->current_json_responce, 'Request unsuccessful')
            or strpos($this->current_json_responce, 'Hacking attempt')
            or trim($this->current_json_responce) == ''
            or strlen($this->current_json_responce) < 20
        ) {


            // возможно все хорошо и уже здесь есть ОТВЕТ !!!!!
            // возможно все хорошо и уже здесь есть ОТВЕТ !!!!!
            // возможно все хорошо и уже здесь есть ОТВЕТ !!!!!
            $this->current_json_responce = $this->nodeJsRequestGetCookiesORData();
            $json = json_decode($this->current_json_responce, true);
            if (isset($json["data"]["lotDetails"])) {

                $result = [
                    'year' => $json["data"]["lotDetails"]["lcy"],
                    'location' => $json["data"]["lotDetails"]["yn"],
                    'branchSeller' => $json["data"]["lotDetails"]["scn"],
                    'engine' => $json["data"]["lotDetails"]["egn"],
                    'fuel' => $json["data"]["lotDetails"]["ft"],
                    'error' => 0,
                    'url' => $this->income_url,
                ];

                $this->logs('copart_good.txt',
                    [
                        'comment' => 'NODEJS обработчик сработал',
                        'result' => $result,
                        'current_cookies_file_content' => $this->cookies_parse_from_file(),
                        'current_cookies_from_nodejs' => $this->current_cookies_from_nodejs,
                        'responce' => $this->current_json_responce,
                        'debug' => $all_requested_list_debug,
                    ]
                );
                return json_encode($result);

            }

            // только еслои куки имеют завтный ключ или два
            //#HttpOnly_www.copart.com	FALSE	/	TRUE	0	G2JSESSIONID	2A2E3C99BCD47187AA19A98A20EE1749-n2
            //#HttpOnly_.copart.com	TRUE	/	TRUE	1604949189	g2usersessionid	0b4f61da6613900ecce840bc5d774668
            if (strpos($this->current_cookies_from_nodejs, 'session')) {

                // ВСЕ НОРМ ПРОРВАЛИСЬ !!!!! G2JSESSIONID есть
                // ВСЕ НОРМ ПРОРВАЛИСЬ !!!!! G2JSESSIONID есть
                // ВСЕ НОРМ ПРОРВАЛИСЬ !!!!! G2JSESSIONID есть
                //сбрасываем все куки
                //unlink($this->file_cookies);

                // запрос иньекцией новых кук
                // делаем вторую попытку с новыми куками
                $this->current_json_responce = $this->send_http_request(
                    "https://www.copart.com/public/data/lotdetails/solr/" . $this->current_lot_id,
                    "GET",
                    [
                        'authority: www.copart.com',
                        'pragma: no-cache',
                        'cache-control: no-cache',
                        'upgrade-insecure-requests: 1',
                        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36',
                        'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                        'sec-fetch-site: none',
                        'sec-fetch-mode: navigate',
                        'sec-fetch-user: ?1',
                        'sec-fetch-dest: document',
                        'accept-language: en,ru;q=0.9,uk;q=0.8',
                        'cookie: ' . $this->current_cookies_from_nodejs, // иньекция порции кук !!!!!!!!!!!!!
                    ],
                    false // орати нимание этот же покси на NodeJS
                );

                $all_requested_list_debug[] = [
                    'name' => 'second step result с НУЖНЫМИ куками G2JSESSIONID все норм прорвались !!!!! ЧТО жЕ МЫ ПОЛУЧИЛИ ?',
                    'current_cookies_file_content' => $this->cookies_parse_from_file(),
                    'current_cookies_from_nodejs' => $this->current_cookies_from_nodejs,
                    'responce' => $this->current_json_responce
                ];


            }
            else {


                $all_requested_list_debug[] = [
                    'name' => 'СБРАСЫВАЕМ куки - МЫ НЕ ПОЛУЧИЛИ nodeJS НУЖНУЮ  G2JSESSIONID КУКУ - СКОРЕЙ ВСЕГО НАС СЕЙЧАС ЗАБАНЯТ за этот запрос',
                    'current_cookies_file_content' => $this->cookies_parse_from_file(),
                    'current_cookies_from_nodejs' => $this->current_cookies_from_nodejs,
                    'responce' => $this->current_json_responce
                ];

                //сбрасываем все куки
                unlink($this->file_cookies);


//                $this->nodeJsRequestGetCookies();
//
//                // только еслои куки имеют завтный ключ или два
//                //#HttpOnly_www.copart.com	FALSE	/	TRUE	0	G2JSESSIONID	2A2E3C99BCD47187AA19A98A20EE1749-n2
//                //#HttpOnly_.copart.com	TRUE	/	TRUE	1604949189	g2usersessionid	0b4f61da6613900ecce840bc5d774668
//                if (strpos($this->current_cookies_from_nodejs, 'session')) {
//
//                    die ("!!!!!!!!!!!!!!!!!!!!!!!!!!! БИНГО ");
//
//                }


// ОСТАНОВИТЕ МЕНЯ !
// ОСТАНОВИТЕ МЕНЯ !
// ОСТАНОВИТЕ МЕНЯ !
// ОСТАНОВИТЕ МЕНЯ !
// ОСТАНОВИТЕ МЕНЯ !
                $this->logs('copart_bad.txt',
                    [
                        'current_cookies_file_content' => $this->cookies_parse_from_file(),
                        'current_cookies_from_nodejs' => $this->current_cookies_from_nodejs,
                        'responce' => $this->current_json_responce,
                        'debug' => $all_requested_list_debug,
                    ]
                );
                return json_encode([
                    'error' => 1,
                    'error_desc' => 'Dont see data NO COOKIES',
                    'url' => $this->income_url,
                ]);

            }
        } // КОНЕЦ КОГДА НАС ПОСЛАЛИ!!!

        else {
            $all_requested_list_debug[] = [
                'name' => 'ЗАТАИЛИСЬ - возможно куки плохие ?? НО ВОЗМОЖНО И РЕЗУЛЬТАТИ ПОЙМАЛИ',
                'current_cookies_file_content' => $this->cookies_parse_from_file(),
                'current_cookies_from_nodejs' => $this->current_cookies_from_nodejs,
                'response' => $this->current_json_responce
            ];
        }

        $json = json_decode($this->current_json_responce, true);
        if (isset($json["data"]["lotDetails"])) {

            $result = [
                'year' => $json["data"]["lotDetails"]["lcy"],
                'location' => $json["data"]["lotDetails"]["yn"],
                'branchSeller' => $json["data"]["lotDetails"]["scn"],
                'engine' => $json["data"]["lotDetails"]["egn"],
                'fuel' => $json["data"]["lotDetails"]["ft"],
                'error' => 0,
                'url' => $this->income_url,
            ];

            $this->logs('copart_good.txt',
                [
                    'comment' => 'PHP обработчик сработал',
                    'result' => $result,
                    'current_cookies_file_content' => $this->cookies_parse_from_file(),
                    'current_cookies_from_nodejs' => $this->current_cookies_from_nodejs,
                    'responce' => $this->current_json_responce,
                    'debug' => $all_requested_list_debug,
                ]
            );
            return json_encode($result);

        }

        $this->logs('copart_bad.txt',
            [
                'url' => $this->income_url,
                'debug' => $all_requested_list_debug,
                'response' => $this->current_json_responce,
            ]
        );

        return json_encode([
            'error' => 1,
            'error_desc' => 'Dont see data EMPTY RESPONSE',
            'url' => $this->income_url,
        ]);


    }


    function send_http_request($url, $method, $headers, $use_proxy = false, $post_fields = '')
    {


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        if ($method == "POST") {

            if ($this->curl_debug) {
                echo "*************** POST ***************";
            }
            curl_setopt($ch, CURLOPT_POST, 1);

        }

        if ($use_proxy) {
            $proxy = "83.149.70.159:13012";
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }

        if ($post_fields)
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        if (!empty($headers))
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->file_cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->file_cookies);
        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        // DEBUG
        if ($this->curl_debug) {
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
            curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        }

        $server_output = curl_exec($ch);
        //$server_output = str_replace('src="', 'src="https://www.copart.com', $server_output);


        if ($this->curl_debug) {
            echo "<textarea>";
            echo "\r\n\r\n************************* Curl $url ответ:,n\n" . $server_output;
            echo "</textarea>";
            //echo " get_cs_rf {$this->get_cs_rf} \n";
            //echo " file_cookies ".file_get_contents($this->file_cookies);
            //echo " file_csrf ".file_get_contents($this->file_csrf);

        }

        // DEBUG
        if ($this->curl_debug) {
            echo "\r\n ************************ ответ на  " . $url . "************************\n\n";
            print_r(curl_getinfo($ch), true);
        }

        curl_close($ch);
        return $server_output;
    }


    function iaai()
    {
        if ($_SERVER['HTTP_HOST'] === 'car-parse.loc') {
            exec("cd " . __DIR__ . " && " . '"\Program Files\nodejs\node.exe" ' . "cookie.js 2>&1", $out, $err);
        } else {
            exec("cd " . __DIR__ . " &&  node cookie.js 2>&1", $out, $err);
        }
        $this->current_cookies = '';
        foreach (json_decode($out[0], true) as $item) {
            $this->current_cookies .= $item['name'] . '=' . $item['value'] . "; ";
        }

        $html_responce = $this->send_http_request(
            $this->income_url,
            "GET",
            [
                'Connection: keep-alive',
                'Pragma: no-cache',
                'Cache-Control: no-cache',
                'Upgrade-Insecure-Requests: 1',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Sec-Fetch-Site: same-origin',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-User: ?1',
                'Sec-Fetch-Dest: document',
                'Referer: https://www.iaai.com/VehicleSearch/SearchDetails?keyword=',
                'Accept-Language: en,ru;q=0.9,uk;q=0.8',
                // привязкав по IP - генерит хитрый JS этот ключ
                'cookie: ' . $this->current_cookies,
            ]
        );

//
//<html><head><title>Object moved</title></head><body>
//<h2>Object moved to <a href="/Vehiclelisting/Toyota/Highlander">here</a>.</h2>
//</body></html>

        if (preg_match('/Object moved to/', $html_responce, $m)) {
            return json_encode([
                'error' => 1,
                'error_desc' => 'Lot is not exist',
                'url' => $this->income_url,
            ]);
        }

        if (preg_match('/"heading-2">(\d+)/', $html_responce, $m)) {
            $year = trim($m[1]);
        }

        if (preg_match('/Vehicle Location:<\/span>\s+<div\sclass="data-list__value">\s+<span>([^<]{5,45})</m', $html_responce, $m)) {
            $location = trim($m[1]);
        }

        if (preg_match('/Selling Branch:<\/span>\s+<span class="data-list__value">([^<]{5,45})<\/span>/m', $html_responce, $m)) {
            $branchSeller = trim($m[1]);
        }


        if (preg_match('/>([^<]+)<\/span>\s+<\/li>\s+<li class="data-list__item">\s+<span class="data-list__label">Transmission/m', $html_responce, $m)) {
            $engine = trim($m[1]);
        }

        if (preg_match('/Fuel Type:<\/span>\s+<span class="data-list__value">\s+([^<]+)/m', $html_responce, $m)) {
            $fuel = trim($m[1]);
        }


        if (isset($year, $location, $branchSeller, $engine, $fuel)) {
            $result = [
                'year' => $year,
                'location' => $location,
                'branchSeller' => $branchSeller,
                'engine' => $engine,
                'fuel' => $fuel,
                'error' => 0,
                'url' => $this->income_url,
            ];
            $this->logs('iaai_good.txt',
                [
                    'result' => $result,
                ]
            );
            return json_encode($result);

        }

        $this->logs('iaai_dont_see.txt',
            [
                'url' => $this->income_url,
                'html' => $html_responce,
            ]
        );
        return json_encode([
            'error' => 1,
            'error_desc' => 'Dont see data',
            'url' => $this->income_url,
        ]);


    }

    public function logs($filelog_name, $message)
    {

        $fd = @fopen(__DIR__ . "/logs/" . $filelog_name, "a");
        @fwrite($fd, date("Ymd-G:i:s") . " -- " . print_r($message, true) . "\n");
        @fclose($fd);
    }


    public function cookies_parse_from_file()
    {
        $this->current_cookies_file_content = @file_get_contents($this->file_cookies);
        $cookie = '';
        preg_match_all('~([0-9a-z=/\-_+]*)\s+([0-9a-z=/\-_+]{20,})\s$~im', $this->current_cookies_file_content, $d);
        if (!sizeof($d[1])) {
            return $cookie;
        }
        foreach ($d[1] as $key => $item) {
            //if (preg_match('~session~i', $d[1][$key]))
            $cookie .= ' ' . $d[1][$key] . '=' . $d[2][$key] . '; ';
        }
        return trim($cookie, ' ;');
    }


    public function nodeJsRequestGetCookiesORData()
    {
        //
        if ($_SERVER['HTTP_HOST'] === 'car-parse.loc') {
            $nreq = 'cd ' . __DIR__ . ' && ' . '"\Program Files\nodejs\node.exe" ' . 'cookieCopart.js ' .
                $this->current_lot_id . ' "' .
                $this->cookies_parse_from_file() .
                '" 2>&1';
            exec($nreq, $out, $err);
        } else {
            $nreq = 'cd ' . __DIR__ . ' && node cookieCopart.js ' .
                $this->current_lot_id . ' "' .
                $this->cookies_parse_from_file() .
                '" 2>&1';
            exec($nreq, $out, $err);

        }

        //$out[0] - депрекейтет сообщение что то тап по паттитиру
        $responce = json_decode($out[1], true);
        //достаем и cookies и data

        //papyteear  $out[0]  - матюк о том что таймаут не поддержиается скооро в паттитире
        foreach ($responce['cookies'] as $item) {
            $this->current_cookies_from_nodejs .= $item['name'] . '=' . $item['value'] . "; ";
        }

        // контент выводим JSON если это он конечно
        preg_match('~>{(.*)}<~', $responce['data'], $d);
        return "{" . $d[1] . "}";

    }
}

