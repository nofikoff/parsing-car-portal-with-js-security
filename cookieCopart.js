/**
 * Подключим библиотеку puppeteer.
 */
const puppeteer = require('puppeteer');
if (typeof process.argv[3] == 'undefined') {
    console.log("Нет параметров", process.argv);
    return "НЕ ВИЖУ ПАРАМЕТРЫ для файла cookieCompart.js";
}

const url = 'https://www.copart.com/public/data/lotdetails/solr/' + process.argv[2];


async function ssr() {
//, '--proxy-server=http://176.9.119.170:3128'
    const browser = await puppeteer.launch({headless: true, args: ['--disable-dev-shm-usage', '--no-sandbox']});
    const page = await browser.newPage();
    await page.setExtraHTTPHeaders({
        'authority': 'www.copart.com',
        'pragma': 'no-cache',
        'cache-control': 'no-cache',
        'upgrade-insecure-requests': '1',
        'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'sec-fetch-site': 'none',
        'sec-fetch-mode': 'navigate',
        'sec-fetch-user': '?1',
        'sec-fetch-dest': 'document',
        'accept-language': 'en,ru;q=0.9,uk;q=0.8',
        'cookie': process.argv[3]
    });
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36');
    await page.goto(url, {waitUntil: 'networkidle2'});

    //
    // если отключить - то ошибка в консоли удйет и сдвинется анализатор ответа  nodeJsRequestGetCookiesORData
    // если отключить - то ошибка в консоли удйет и сдвинется анализатор ответа  nodeJsRequestGetCookiesORData
    // если отключить - то ошибка в консоли удйет и сдвинется анализатор ответа  nodeJsRequestGetCookiesORData
    // кидается варнингами в консоли а мы из консоли берем данные
    // НЕ ТРОГАЙ ЭТО МАГИЯ нужна для отработки сложного JS скрипта
    // НЕ ТРОГАЙ ЭТО МАГИЯ нужна для отработки сложного JS скрипта
    // НЕ ТРОГАЙ ЭТО МАГИЯ нужна для отработки сложного JS скрипта
    // НЕ ТРОГАЙ ЭТО МАГИЯ нужна для отработки сложного JS скрипта
    await page.waitFor(1000); // НЕ ТРОГАЙ ЭТО МАГИЯ нужна для отработки сложного JS скрипта

    // для отлоадки - пок система не стабильна иповеряем мало ли что!!!!!!!!!!
     await page.screenshot({
         path: `xxxxxxxxxxxxxxx.png`,
         fullPage: true,
     });


    // хватило и этой записи для куки одной исчтоник https://stackoverflow.com/questions/49389775/puppeteers-page-cookies-not-retrieving-all-cookies-shown-in-the-chrome-dev-to
    //var data = await page._client.send('Network.getAllCookies');
    // можно и так больше кукиес
    const cookies = await page.cookies();
    const data = await page.evaluate(() => document.querySelector('*').outerHTML);
    console.log(JSON.stringify(
        {
            'cookies': cookies,
            'data': data
        }
    ));
    await browser.close();
}

ssr();

